<?php

namespace App\adms\Models\api;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * O SERVIDOR DA API /api/v1 (Onda 2, Etapa 6) — a terceira porta para as MESMAS funções das telas.
 *
 * Cada chamada: a rota (ROTAS, a lista que a especificação docs/api/openapi.json tem de espelhar — a prova confere);
 * a chave (Authorization: Bearer); o escopo (a chave de leitura não grava); o limite por chave e por minuto
 * (adms_parametros "api.limite_por_minuto"); nas escritas, a Idempotency-Key (a mesma chave com o mesmo corpo repete a
 * resposta guardada; com outro corpo, 409); o registro em adms_api_chamadas. O erro tem um formato só:
 * {"erro": {"codigo", "mensagem", "campo"}} — sem caminho de servidor nem SQL. Sem CORS.
 */
class AdmsApiServidor
{
    /** [método, padrão, operação, escrita?, assunto] — {ra} só dígitos, {id} número; o assunto é o do limite da chave
        (Onda 3, Etapa 9: AdmsApiChaves::ASSUNTOS) */
    public const ROTAS = [
        ['GET', '/alunos', 'alunosListar', false, 'alunos'],
        ['POST', '/alunos', 'alunosCriar', true, 'alunos'],
        ['GET', '/alunos/{ra}', 'alunosVer', false, 'alunos'],
        ['PATCH', '/alunos/{ra}', 'alunosAtualizar', true, 'alunos'],
        ['GET', '/cursos', 'cursosListar', false, 'cadastros'],
        ['GET', '/cursos/{id}/grades', 'gradesListar', false, 'cadastros'],
        ['GET', '/anos-letivos', 'anosListar', false, 'cadastros'],
        ['GET', '/polos', 'polosListar', false, 'cadastros'],
        ['GET', '/modalidades', 'modalidadesListar', false, 'cadastros'],
        ['GET', '/formas-ingresso', 'formasListar', false, 'cadastros'],
        ['GET', '/disciplinas', 'disciplinasListar', false, 'cadastros'],
        ['GET', '/ofertas', 'ofertasListar', false, 'cadastros'],
        ['GET', '/matriculas', 'matriculasListar', false, 'matriculas'],
        ['POST', '/matriculas', 'matriculasCriar', true, 'matriculas'],
        ['GET', '/matriculas/{id}', 'matriculasVer', false, 'matriculas'],
        ['POST', '/matriculas/{id}/trancar', 'matriculasTrancar', true, 'matriculas'],
        ['POST', '/matriculas/{id}/reativar', 'matriculasReativar', true, 'matriculas'],
        ['POST', '/matriculas/{id}/cancelar', 'matriculasCancelar', true, 'matriculas'],
        ['GET', '/matriculas/{id}/enturmacao', 'enturmacaoVer', false, 'enturmacao'],
        ['POST', '/matriculas/{id}/enturmacao', 'enturmacaoGravar', true, 'enturmacao'],
        /* Onda 4, Etapa 2: só numa instalação de escola (o 6º elemento é o módulo; desligado, a rota não existe) */
        ['GET', '/turmas', 'turmasListar', false, 'cadastros', 'escola,livre'], // D-106
        ['GET', '/turmas/{id}/alunos', 'turmaAlunosVer', false, 'enturmacao', 'escola,livre'],
        ['POST', '/turmas/{id}/alunos', 'turmaAlunosEntrar', true, 'enturmacao', 'escola,livre'],
        /* Onda 7, Etapa 2: o responsável financeiro e o acadêmico titular do aluno, por ano letivo (só na escola) */
        ['GET', '/alunos/{ra}/responsaveis', 'responsaveisVer', false, 'responsaveis', 'escola'],
        ['POST', '/alunos/{ra}/responsaveis', 'responsaveisDesignar', true, 'responsaveis', 'escola'],
        /* Onda 8 (D-70): a inscrição numa chamada só (o cadastro, a matrícula e a turma ou as ofertas): o 7º elemento são
           os assuntos que a chave também precisa gravar; e o primeiro acesso do aluno (o link de criar a senha) */
        ['POST', '/inscricoes', 'inscricoesCriar', true, 'alunos', null, ['matriculas', 'enturmacao']],
        ['POST', '/alunos/{ra}/primeiro-acesso', 'primeiroAcesso', true, 'alunos'],
    ];
    /** As operações cuja resposta não é guardada para a repetição: o link de criar a senha nunca vai ao banco em claro. */
    public const SEM_REPETICAO = ['primeiroAcesso'];

    private float $inicio;
    private ?array $chave = null;
    private string $metodo = '';
    private string $rota = '';
    private ?string $operacao = null;
    private ?string $idem = null;
    private string $ip = '';
    private bool $https = true;

    /** Atende a requisição e devolve [status, corpo (array), cabeçalhos extras]. */
    public function atender(string $metodo, string $caminho, array $query, string $corpo, array $cabecalhos, string $ip, bool $https = true): array
    {
        $this->https = $https;
        $this->inicio = microtime(true);
        $this->metodo = strtoupper($metodo);
        $this->rota = '/' . trim(preg_replace('#/+#', '/', $caminho), '/');
        $this->ip = $ip;
        $cab = array_change_key_case($cabecalhos, CASE_LOWER);
        try {
            $r = $this->processar($query, $corpo, $cab);
        } catch (\Throwable $e) {
            error_log('[vcostaedu][api] ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
            $r = self::erro(500, 'erro_interno', 'Erro interno. A chamada foi registrada; tente de novo ou avise o administrador.');
        }
        $this->registrar($r[0], $r[1]['erro']['codigo'] ?? null);
        return $r;
    }

    private function processar(array $query, string $corpo, array $cab): array
    {
        /* Onda 3, Etapa 1: a instalação que exige HTTPS (EXIGIR_HTTPS na Config) recusa a API por HTTP — antes de olhar
           a rota e a chave, sem redirecionar (um cliente que segue o redirecionamento reenviaria a chave) */
        if (defined('EXIGIR_HTTPS') && EXIGIR_HTTPS && !$this->https) {
            return self::erro(403, 'https_obrigatorio', 'Esta instalação exige HTTPS: chame a API por https://. Nada foi lido nem gravado; se a chave foi enviada por HTTP, revogue-a e crie outra.');
        }
        /* a rota */
        [$rota, $params, $metodosDaRota] = self::casar($this->metodo, $this->rota);
        if (!$rota) {
            if ($metodosDaRota) {
                return self::erro(405, 'metodo_nao_permitido', 'Método não permitido nesta rota. Use: ' . implode(', ', $metodosDaRota) . '.', null, ['Allow' => implode(', ', $metodosDaRota)]);
            }
            return self::erro(404, 'rota_inexistente', 'Rota inexistente. A documentação está em Integrações → Documentação da API.');
        }
        $this->operacao = $rota[2];
        if (!\App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0010')) {
            return self::erro(503, 'atualizacao_pendente', 'O sistema tem uma atualização pendente: a API volta quando o administrador aplicá-la.');
        }

        /* a chave */
        [$chave, $cod, $msg] = AdmsApiChaves::autenticar((string) ($cab['authorization'] ?? ''));
        if (!$chave) {
            return self::erro(401, $cod, $msg, null, ['WWW-Authenticate' => 'Bearer']);
        }
        $this->chave = $chave;
        AdmsApiChaves::registrarUso((int) $chave['id'], $this->ip);
        /* o limite da chave, por assunto (Onda 3, Etapa 9): gravar inclui consultar o mesmo assunto */
        if (!AdmsApiChaves::pode($chave, $rota[4], $rota[3])) {
            $nomeAssunto = AdmsApiChaves::ASSUNTOS[$rota[4]][0];
            return self::erro(403, 'sem_permissao', 'Esta chave não pode ' . ($rota[3] ? 'gravar' : 'consultar') . ' "' . $nomeAssunto . '". O limite dela: '
                . AdmsApiChaves::descrever($chave['permissoes'] ?? null) . '. Peça ao administrador uma chave com o limite certo.');
        }
        /* Onda 8 (D-70): a operação que grava em mais de um assunto pede a gravação em todos */
        foreach ((array) ($rota[6] ?? []) as $outro) {
            if (!AdmsApiChaves::pode($chave, $outro, true)) {
                return self::erro(403, 'sem_permissao', 'Esta chave não pode gravar "' . AdmsApiChaves::ASSUNTOS[$outro][0] . '", que esta operação também grava. O limite dela: '
                    . AdmsApiChaves::descrever($chave['permissoes'] ?? null) . '. Peça ao administrador uma chave com o limite certo.');
            }
        }

        /* o limite por minuto */
        $limite = max(1, (int) (self::um("SELECT valor FROM adms_parametros WHERE chave = 'api.limite_por_minuto'") ?: 120));
        $usadas = (int) self::um('SELECT COUNT(*) FROM adms_api_chamadas WHERE chave_id = ? AND created > (NOW(3) - INTERVAL 60 SECOND)', [(int) $chave['id']]);
        if ($usadas >= $limite) {
            return self::erro(429, 'limite_excedido', "Limite de {$limite} chamadas por minuto desta chave excedido. Espere um pouco e tente de novo.", null, ['Retry-After' => '60']);
        }

        /* o corpo */
        $dados = [];
        if ($rota[3]) {
            $t = trim($corpo);
            if ($t !== '') {
                $dados = json_decode($t, true);
                if (!is_array($dados) || array_is_list($dados) && $dados !== []) {
                    return self::erro(400, 'json_invalido', 'O corpo da requisição precisa ser um objeto JSON.');
                }
            }
        }

        /* a idempotência das escritas */
        if ($rota[3] && !in_array($rota[2], self::SEM_REPETICAO, true) && isset($cab['idempotency-key']) && trim((string) $cab['idempotency-key']) !== '') {
            $this->idem = mb_substr(trim((string) $cab['idempotency-key']), 0, 100);
            $hashCorpo = hash('sha256', $this->metodo . ' ' . $this->rota . ' ' . json_encode($dados));
            /* Onda 8 (D-70): o clique duplo — duas chamadas AO MESMO TEMPO com a mesma chave: a segunda espera a primeira
               terminar (a trava é da conexão da requisição: sai sozinha no fim) e devolve a mesma resposta */
            self::travar('idem', (int) $chave['id'] . ':' . $this->idem);
            $ant = self::linha('SELECT * FROM adms_api_idempotencia WHERE chave_id = ? AND idem_key = ?', [(int) $chave['id'], $this->idem]);
            if ($ant) {
                if ($ant['hash_corpo'] !== $hashCorpo) {
                    return self::erro(409, 'idempotencia_conflito', 'Esta Idempotency-Key já foi usada com outra requisição. Use uma chave nova para uma operação nova.');
                }
                return [(int) $ant['status'], json_decode((string) $ant['resposta'], true) ?: [], ['Idempotent-Replayed' => 'true']];
            }
        }

        /* a operação: a chave grava em nome de quem a criou, com a origem "API" na auditoria */
        $this->sessao((int) $chave['usuario_id']);
        $recursos = new AdmsApiRecursos();
        $r = $recursos->{$rota[2]}($params, $query, $dados);

        if ($this->idem !== null && $r[0] < 500 && $r[0] !== 429) {
            try {
                self::exec('INSERT INTO adms_api_idempotencia (chave_id, idem_key, metodo, rota, hash_corpo, status, resposta) VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [(int) $chave['id'], $this->idem, $this->metodo, $this->rota, $hashCorpo, $r[0], json_encode($r[1], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            } catch (\PDOException $e) {
                /* duas chamadas simultâneas com a mesma chave: vale a primeira */
            }
        }
        return $r;
    }

    /** A rota que casa com o método e o caminho: [rota, parâmetros, métodos aceitos naquele caminho]. */
    public static function casar(string $metodo, string $caminho): array
    {
        $metodos = [];
        foreach (self::ROTAS as $r) {
            if (isset($r[5]) && !\App\adms\Models\helper\AdmsTipoInstituicao::moduloLigado($r[5])) {
                continue; // a rota de um módulo desligado (a escola, numa instalação de ensino superior) não existe
            }
            $re = '#^' . str_replace(['{ra}', '{id}'], ['(?P<ra>\d{1,9})', '(?P<id>\d{1,10})'], $r[1]) . '$#';
            if (preg_match($re, $caminho, $m)) {
                $metodos[] = $r[0];
                if ($r[0] === $metodo) {
                    return [$r, array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY), []];
                }
            }
        }
        return [null, [], array_values(array_unique($metodos))];
    }

    /** A sessão que os modelos das telas esperam: o usuário dono da chave, com o nível dele. */
    private function sessao(int $usuarioId): void
    {
        $u = self::linha('SELECT u.id, u.nome, u.email, u.adms_niveis_acesso_id, n.ordem, n.nome AS nivel FROM adms_usuarios u JOIN adms_niveis_acessos n ON n.id = u.adms_niveis_acesso_id WHERE u.id = ?', [$usuarioId]) ?: [];
        $_SESSION = [
            'colUsuario_id' => (int) ($u['id'] ?? $usuarioId), 'colUsuario_nome' => (string) ($u['nome'] ?? 'API'), 'colUsuario_email' => (string) ($u['email'] ?? ''),
            'colUsuario_nivel' => (int) ($u['adms_niveis_acesso_id'] ?? 0), 'colUsuario_ordem' => (int) ($u['ordem'] ?? 0), 'colUsuario_nivel_nome' => (string) ($u['nivel'] ?? ''),
            'colUsuario_situacao' => 1, 'vc_origem' => 'api',
        ];
    }

    private function registrar(int $status, ?string $codigo): void
    {
        try {
            self::exec('INSERT INTO adms_api_chamadas (chave_id, prefixo, metodo, rota, operacao, status, codigo_erro, ip, idempotency_key, duracao_ms) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $this->chave ? (int) $this->chave['id'] : null, $this->chave['prefixo'] ?? null, mb_substr($this->metodo, 0, 8), mb_substr($this->rota, 0, 255),
                $this->operacao, $status, $codigo, mb_substr($this->ip, 0, 45), $this->idem, (int) round((microtime(true) - $this->inicio) * 1000),
            ]);
        } catch (\Throwable $e) {
            error_log('[vcostaedu][api] registro da chamada: ' . $e->getMessage());
        }
    }

    /**
     * Onda 8 (D-70): uma trava com nome (GET_LOCK) na conexão da requisição, até 15 segundos de espera: duas requisições
     * com o mesmo nome andam uma depois da outra. Sai sozinha quando a conexão fecha (o fim da requisição).
     */
    public static function travar(string $tipo, string $nome): bool
    {
        $pdo = \VcBanco::daRequisicao();
        $n = 'vce_' . $tipo . '_' . substr(sha1((string) $pdo->query('SELECT DATABASE()')->fetchColumn() . '|' . $nome), 0, 40);
        $st = $pdo->prepare('SELECT GET_LOCK(?, 15)');
        $st->execute([$n]);
        return (int) $st->fetchColumn() === 1;
    }

    public static function erro(int $status, string $codigo, string $mensagem, ?string $campo = null, array $cabecalhos = []): array
    {
        return [$status, ['erro' => ['codigo' => $codigo, 'mensagem' => $mensagem, 'campo' => $campo]], $cabecalhos];
    }

    // ------------------------------------------------------------------ o banco

    public static function pdo(): \PDO
    {
        static $pdo = null;
        if ($t = \Core\Transacao::pdo()) {
            return $t;
        }
        return $pdo ??= \VcBanco::daRequisicao(); // Onda 6, Etapa 1: a conexão da requisição
    }

    public static function exec(string $sql, array $p = []): \PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($p);
        return $st;
    }

    public static function linha(string $sql, array $p = []): ?array
    {
        $r = self::exec($sql, $p)->fetch(\PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public static function todas(string $sql, array $p = []): array
    {
        return self::exec($sql, $p)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function um(string $sql, array $p = [])
    {
        return self::exec($sql, $p)->fetchColumn();
    }
}
