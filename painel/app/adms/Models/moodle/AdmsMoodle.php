<?php

namespace App\adms\Models\moodle;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 5) . '/core/Moodle.php';

/**
 * A INTEGRAÇÃO COM O MOODLE (Onda 3, Etapa 8; docs/INTEGRACAO_MOODLE.md): a conexão, a fila de envios e o envio de
 * cada item.
 *
 * Regra de ouro: salvar aluno, matrícula ou enturmação NUNCA falha por causa do Moodle. Os pontos de gravação chamam
 * anotar() depois que gravaram; anotar() põe o item na fila (adms_moodle_envios) e, com o envio automático, tenta
 * enviar na hora, dentro de um orçamento curto de tempo por requisição. O que não foi fica pendente, com o motivo.
 * Qualquer exceção aqui é engolida e vai para o registro do PHP — o acadêmico segue.
 */
class AdmsMoodle
{
    public const ORCAMENTO = 6.0; // segundos de envio "na hora" por requisição; o resto fica na fila

    private static ?\PDO $pdo = null;
    private static array $daRequisicao = [];
    private static float $gasto = 0.0;
    private static ?array $conexaoCache = null;

    public static function pdo(): \PDO
    {
        if (self::$pdo === null) {
            self::$pdo = (new \App\adms\Models\helper\AdmsConexaoAvulsa())->pdo();
        }
        return self::$pdo;
    }

    /** As tabelas da integração existem (a migration 0020 foi aplicada)? */
    public static function disponivel(): bool
    {
        return \App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0020');
    }

    /** A conexão (a linha 1), com a chave decifrada em 'chave' (só para uso interno: nunca vai para a tela). */
    public static function conexao(bool $recarregar = false): array
    {
        if (self::$conexaoCache !== null && !$recarregar) {
            return self::$conexaoCache;
        }
        $c = self::disponivel() ? (self::pdo()->query('SELECT * FROM adms_moodle_conexao WHERE id = 1')->fetch(\PDO::FETCH_ASSOC) ?: []) : [];
        try {
            $c['chave'] = \VcMoodle::decifrar($c['chave_cifrada'] ?? null);
        } catch (\Throwable $e) {
            error_log('[vcostaedu][moodle] a chave não decifrou: ' . $e->getMessage());
            $c['chave'] = '';
        }
        return self::$conexaoCache = $c;
    }

    /** Conectado = endereço, chave e um teste que deu certo. */
    public static function conectado(): bool
    {
        $c = self::conexao();
        return !empty($c['url']) && ($c['chave'] ?? '') !== '' && !empty($c['testada_em']);
    }

    /** O domínio do e-mail montado: letras, números, hífen e ponto, com o sufixo (escola.exemplo.edu.br). */
    public const DOMINIO = '/^(?=.{4,190}$)([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/';

    /**
     * Onda 8, Etapa 2 (D-68): o e-mail com que o aluno vai ao Moodle (Conectar ao Moodle), em três modos:
     *   escola          — sempre o e-mail da escola (a matrícula no domínio): o padrão na escola;
     *   aluno_ou_escola — o e-mail do aluno; sem ele, ou repetido em outro aluno, o da escola (a opção da Onda 6, D-57);
     *   aluno           — só o e-mail do aluno; sem ele, ou repetido, o aluno fica fora com o motivo: o padrão no superior.
     * O e-mail da escola é montado na hora do envio: nunca é gravado no cadastro e não recebe mensagens.
     */
    public const MODOS = ['escola', 'aluno_ou_escola', 'aluno'];

    /** O modo gravado; sem escolha gravada (a instalação nova), o padrão do tipo de instituição. */
    public static function emailModo(): string
    {
        $v = self::parametro('moodle.email_modo', '');
        if (in_array($v, self::MODOS, true)) {
            return $v;
        }
        try {
            $tipo = (string) self::pdo()->query("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo' LIMIT 1")->fetchColumn();
        } catch (\Throwable $e) {
            $tipo = '';
        }
        return $tipo === 'escola' ? 'escola' : 'aluno';
    }

    /** O e-mail da escola: a matrícula no domínio informado ('' sem um domínio válido). Nunca vai para o cadastro. */
    public static function emailMontado(string $rm): string
    {
        $dominio = mb_strtolower(trim(self::parametro('moodle.email_dominio', '')));
        $local = preg_replace('/[^a-z0-9._-]/', '', mb_strtolower(trim($rm)));
        return preg_match(self::DOMINIO, $dominio) && $local !== '' ? $local . '@' . $dominio : '';
    }

    /**
     * O e-mail do aluno para o Moodle, pelo modo: [e-mail, código do motivo quando não há]. O e-mail do cadastro que outro
     * aluno também tem (irmãos com o e-mail da mãe) é tratado como "sem e-mail": a conta do Moodle é de uma pessoa só.
     */
    public static function emailParaMoodle(int $alunoId, string $rm, string $doCadastro): array
    {
        $modo = self::emailModo();
        if ($modo !== 'escola') {
            $proprio = trim($doCadastro);
            if ($proprio === '') {
                $motivo = 'sem_email';
            } elseif (self::emailRepetido($alunoId, $proprio)) {
                $motivo = 'email_repetido';
            } else {
                return [$proprio, ''];
            }
            if ($modo === 'aluno') {
                return ['', $motivo];
            }
        }
        $montado = self::emailMontado($rm);
        return $montado !== '' ? [$montado, ''] : ['', 'sem_dominio'];
    }

    /** Outro aluno tem o mesmo e-mail no cadastro? */
    public static function emailRepetido(int $alunoId, string $email): bool
    {
        $st = self::pdo()->prepare('SELECT COUNT(*) FROM col_usuarios WHERE col_niveis_acesso_id = 1 AND id <> ? AND LOWER(TRIM(email)) = ?');
        $st->execute([$alunoId, mb_strtolower(trim($email))]);
        return (int) $st->fetchColumn() > 0;
    }

    /** Os alunos cuja conta do Moodle o sistema criou voltam à fila (o modo ou o domínio mudou: o e-mail deles muda). */
    public static function reenviarCriados(): int
    {
        if (!self::disponivel() || !self::conectado()) {
            return 0;
        }
        $ids = self::pdo()->query("SELECT DISTINCT v.local_id FROM adms_moodle_vinculos v JOIN col_usuarios u ON u.id = v.local_id WHERE v.tipo = 'aluno' AND v.criado = 1")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($ids as $id) {
            self::anotar('aluno', (int) $id);
        }
        return count($ids);
    }

    public static function parametro(string $chave, string $padrao): string
    {
        $st = self::pdo()->prepare('SELECT valor FROM adms_parametros WHERE chave = ?');
        $st->execute([$chave]);
        $v = $st->fetchColumn();
        return $v === false ? $padrao : (string) $v;
    }

    public static function cliente(?array $c = null): \VcMoodle
    {
        $c ??= self::conexao();
        $pdo = self::pdo();
        return new \VcMoodle((string) $c['url'], (string) $c['chave'], static function ($funcao, $ref, $res, $cod, $ms) use ($pdo) {
            try {
                $pdo->prepare('INSERT INTO adms_moodle_registro (funcao, referencia, resultado, codigo, ms) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$funcao, mb_substr($ref, 0, 60), $res, $cod !== '' ? mb_substr($cod, 0, 60) : null, $ms]);
                if ($res === 'ok') {
                    $pdo->exec('UPDATE adms_moodle_conexao SET ultima_ok_em = NOW() WHERE id = 1');
                }
            } catch (\Throwable $e) {
                error_log('[vcostaedu][moodle] o registro falhou: ' . $e->getMessage());
            }
        });
    }

    /* ======================================================== a fila ========================================== */

    /**
     * Anota o que mudou e, com o envio automático, tenta enviar na hora. Chamado DEPOIS da gravação, fora de transação.
     * Tipos: aluno (aluno_id), oferta (oferta_id), enturmar e desenturmar (aluno_id, oferta_id), situacao (aluno_id),
     * professor (oferta_id). Nunca lança.
     */
    public static function anotar(string $tipo, ?int $alunoId = null, ?int $ofertaId = null): void
    {
        if (self::$segurando) {
            return; // uma operação em passos avisa o Moodle ela mesma, no fim (Onda 6, Etapa 4)
        }
        /* só a intenção: a fila e o envio vêm no FIM da requisição, depois que a gravação (e a transação de fora, como a
           da importação por planilha) terminou — nada na fila aponta para um registro que não ficou gravado */
        if (!self::$aAnotar) {
            register_shutdown_function([self::class, 'aoTerminar']);
        }
        self::$aAnotar[$tipo . ':' . $alunoId . ':' . $ofertaId] = [$tipo, $alunoId, $ofertaId];
    }

    private static array $aAnotar = [];
    private static bool $segurando = false;

    /** Enquanto true, anotar() não anota: a operação em passos põe na fila, ela mesma, o que gravou (só com tudo gravado). */
    public static function segurar(bool $sim): void
    {
        self::$segurando = $sim;
    }

    /** A oferta mudou de nome: só a que já está no Moodle vai para a fila (editar não cria a disciplina lá). Nunca lança. */
    public static function anotarNome(int $ofertaId): void
    {
        try {
            if (self::disponivel() && self::conectado()) {
                $st = self::pdo()->prepare('SELECT idMoodle FROM rm_professores_materias WHERE id = ?');
                $st->execute([$ofertaId]);
                if ((int) $st->fetchColumn() > 0) {
                    self::anotar('oferta', null, $ofertaId);
                }
            }
        } catch (\Throwable $e) {
            error_log('[vcostaedu][moodle] anotar o nome: ' . $e->getMessage());
        }
    }

    private static bool $soFila = false;

    /**
     * Enquanto true, o fim da requisição só põe na fila: o envio automático fica para a janela de passos da tela (Onda 6,
     * Etapa 4: confirmar a enturmação não espera o Moodle dentro da página).
     */
    public static function soFila(bool $sim): void
    {
        self::$soFila = $sim;
    }

    /**
     * O fim da requisição: o que foi anotado (e existe) entra na fila; com o envio automático, vai na hora (ou, com soFila,
     * fica para a tela). @return int[] os itens da fila que o envio automático mandaria agora
     */
    public static function aoTerminar(): array
    {
        $lista = self::$aAnotar;
        self::$aAnotar = [];
        try {
            if (!$lista || !self::disponivel() || !self::conectado()) {
                return [];
            }
            $pdo = self::pdo();
            foreach ($lista as [$tipo, $alunoId, $ofertaId]) {
                if ($alunoId !== null && !(int) $pdo->query('SELECT COUNT(*) FROM col_usuarios WHERE id = ' . (int) $alunoId)->fetchColumn()) {
                    continue;
                }
                if ($ofertaId !== null && !(int) $pdo->query('SELECT COUNT(*) FROM rm_professores_materias WHERE id = ' . (int) $ofertaId)->fetchColumn()) {
                    continue;
                }
                $id = self::enfileirar($tipo, $alunoId, $ofertaId);
                if ($id && (self::conexao()['envio'] ?? 'automatico') === 'automatico') {
                    self::$daRequisicao[] = $id;
                }
            }
            $ids = self::$daRequisicao;
            if (self::$soFila) {
                self::$daRequisicao = [];
                return $ids;
            }
            self::enviarDaRequisicao();
            return $ids;
        } catch (\Throwable $e) {
            error_log('[vcostaedu][moodle] ao terminar: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * A janela de passos do envio automático (Onda 6, Etapa 4): os itens dados, até o orçamento de tempo; o Moodle fora do
     * ar para o envio (o resto fica na fila, como sempre). @return array ['restam' => int[], 'enviados', 'recusados', 'parou' => ?string]
     */
    public static function enviarItens(array $ids, float $orcamento = 5.0): array
    {
        $ini = microtime(true);
        $r = ['restam' => array_values(array_map('intval', $ids)), 'enviados' => 0, 'recusados' => 0, 'parou' => null];
        while ($r['restam'] && microtime(true) - $ini < $orcamento) {
            $id = array_shift($r['restam']);
            $sit = self::enviar($id);
            if ($sit === 'ocupado') {
                continue; // outra requisição está mandando este item agora (D-70)
            }
            if ($sit === 'falhou') {
                $st = self::pdo()->prepare('SELECT codigo FROM adms_moodle_envios WHERE id = ?');
                $st->execute([$id]);
                $codigo = (string) $st->fetchColumn();
                if (in_array($codigo, AdmsMoodleLote::PARA, true)) {
                    $r['parou'] = implode(' ', \VcMoodle::explicar($codigo));
                    break;
                }
                $r['recusados']++;
            } else {
                $r['enviados']++;
            }
        }
        return $r;
    }

    /** Põe o item na fila (um pendente por chave: o repetido só volta a "pendente"). Devolve o id. */
    public static function enfileirar(string $tipo, ?int $alunoId, ?int $ofertaId): int
    {
        $chave = $tipo . ':' . ($alunoId ?? '') . ':' . ($ofertaId ?? '');
        $pdo = self::pdo();
        $st = $pdo->prepare("SELECT id FROM adms_moodle_envios WHERE chave = ? AND situacao IN ('pendente', 'falhou') ORDER BY id DESC LIMIT 1");
        $st->execute([$chave]);
        $id = (int) $st->fetchColumn();
        if ($id) {
            $pdo->prepare("UPDATE adms_moodle_envios SET situacao = 'pendente' WHERE id = ?")->execute([$id]);
            return $id;
        }
        $pdo->prepare('INSERT INTO adms_moodle_envios (tipo, chave, aluno_id, oferta_id) VALUES (?, ?, ?, ?)')->execute([$tipo, $chave, $alunoId, $ofertaId]);
        return (int) $pdo->lastInsertId();
    }

    /** Envia os itens anotados nesta requisição, enquanto houver orçamento de tempo. */
    private static function enviarDaRequisicao(): void
    {
        while (self::$daRequisicao && self::$gasto < self::ORCAMENTO) {
            $id = array_shift(self::$daRequisicao);
            $t = microtime(true);
            self::enviar($id);
            self::$gasto += microtime(true) - $t;
        }
        self::$daRequisicao = [];
    }

    /**
     * Envia UM item da fila; grava a situação, o motivo e a tentativa. Devolve a situação. Onda 8 (D-70): o item tem uma
     * trava só dele (GET_LOCK), para que duas requisições — a do envio na hora e a de uma retomada, por exemplo — nunca
     * mandem o mesmo item juntas: a segunda o deixa como está e segue (devolve "ocupado").
     */
    public static function enviar(int $id): string
    {
        $pdo = self::pdo();
        $trava = 'vce_envio_' . substr(sha1((string) $pdo->query('SELECT DATABASE()')->fetchColumn() . '|' . $id), 0, 40);
        $st = $pdo->prepare('SELECT GET_LOCK(?, 0)');
        $st->execute([$trava]);
        if ((int) $st->fetchColumn() !== 1) {
            return 'ocupado';
        }
        try {
            return self::enviarComTrava($id);
        } finally {
            $pdo->prepare('SELECT RELEASE_LOCK(?)')->execute([$trava]);
        }
    }

    private static function enviarComTrava(int $id): string
    {
        $pdo = self::pdo();
        $st = $pdo->prepare('SELECT * FROM adms_moodle_envios WHERE id = ?');
        $st->execute([$id]);
        $item = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$item || $item['situacao'] === 'enviado') {
            return $item['situacao'] ?? '';
        }
        try {
            if (!self::conectado()) {
                $r = ['falhou', 'nao_conectado'];
            } else {
                $r = (new AdmsMoodleEnvio(self::cliente(), self::conexao()))->processar($item);
            }
        } catch (\Throwable $e) {
            error_log('[vcostaedu][moodle] envio ' . $id . ': ' . $e->getMessage());
            $r = ['falhou', 'erro_interno'];
        }
        [$situacao, $codigo] = $r;
        $doMoodle = trim((string) ($r[2] ?? ''));
        $contas = !empty($r[3]) ? json_encode(array_values(array_map('intval', $r[3]))) : null;
        $motivo = $codigo !== '' ? \VcMoodle::explicar($codigo)[0] : null;
        if ($motivo !== null && $doMoodle !== '') {
            /* a recusa registrada com a mensagem do Moodle (sem a chave: o Moodle não a devolve na mensagem) */
            $motivo = mb_substr($motivo . ' O Moodle respondeu: "' . preg_replace('/\s+/', ' ', strip_tags($doMoodle)) . '"', 0, 255);
        }
        $pdo->prepare('UPDATE adms_moodle_envios SET situacao = ?, codigo = ?, motivo = ?, contas = ?, tentativas = tentativas + 1, enviado_em = IF(? IN (\'enviado\', \'aviso\'), NOW(), enviado_em) WHERE id = ?')
            ->execute([$situacao, $codigo !== '' ? $codigo : null, $motivo, $contas, $situacao, $id]);
        /* Onda 8 (D-70): o usuário do aluno chegou ao Moodle (o e-mail corrigido, por exemplo): as inscrições dele que
           tinham falhado pelo mesmo motivo voltam à fila sozinhas — e, nesta requisição, vão logo depois, se o orçamento
           deixar */
        if ($item['tipo'] === 'aluno' && in_array($situacao, ['enviado', 'aviso'], true) && (int) $item['aluno_id'] > 0) {
            foreach (self::inscricoesDeVolta((int) $item['aluno_id']) as $volta) {
                self::$daRequisicao[] = $volta;
            }
        }
        return $situacao;
    }

    /* ================================================= o que sobrou anda sozinho (Onda 8, D-70) ================== */

    /** As falhas passageiras: voltam sozinhas, com espera crescente. As outras (sem e-mail, várias contas…) esperam uma pessoa. */
    public const PASSAGEIRAS = ['sem_resposta', 'tempo', 'erro_interno', 'resposta_invalida'];
    public const RETOMADA_ORCAMENTO = 1.5; // segundos, no fim de uma requisição (no máximo isto a mais, numa requisição a cada intervalo)
    public const RETOMADA_INTERVALO = 6;   // segundos entre duas retomadas, em todas as requisições juntas
    public const RETOMADA_IDADE = 8;       // o pendente mais novo que isto é da requisição que o pôs na fila (ou da janela de passos de uma tela): fica para ela

    /**
     * No fim de qualquer requisição (o painel, o portal, a interface): se há pendentes antigos, ou falhas passageiras
     * cuja espera já passou, envia alguns, dentro de um orçamento curto. No máximo uma vez a cada RETOMADA_INTERVALO
     * segundos (a marca é um arquivo: a requisição comum não abre conexão nenhuma por isso), uma de cada vez (a mesma
     * trava do "Enviar tudo o que falta"), só com o envio automático. A falha que só uma pessoa resolve não é tentada de
     * novo aqui. Nunca lança.
     */
    public static function retomar(): void
    {
        try {
            if (self::$gasto > 0) {
                return; // esta requisição já mandou o que ela mesma pôs na fila: não gasta mais tempo
            }
            $marca = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vcostaedu_moodle_retomada_' . substr(sha1(__FILE__), 0, 16);
            if (is_file($marca) && time() - (int) @filemtime($marca) < self::RETOMADA_INTERVALO) {
                return;
            }
            @touch($marca);
            if (!self::disponivel() || !self::conectado() || (self::conexao()['envio'] ?? 'automatico') !== 'automatico') {
                return;
            }
            $pdo = self::pdo();
            $trava = 'vcostaedu_moodle_' . substr(sha1((string) $pdo->query('SELECT DATABASE()')->fetchColumn()), 0, 32);
            if (!(int) $pdo->query("SELECT GET_LOCK('{$trava}', 0)")->fetchColumn()) {
                return; // o "Enviar tudo o que falta" (ou outra retomada) está rodando
            }
            try {
                $idade = (int) self::RETOMADA_IDADE;
                $ids = $pdo->query("SELECT id FROM adms_moodle_envios
                                     WHERE (situacao = 'pendente' AND created < NOW() - INTERVAL {$idade} SECOND)
                                        OR (situacao = 'falhou' AND codigo IN ('" . implode("','", self::PASSAGEIRAS) . "')
                                            AND IFNULL(modified, created) < NOW() - INTERVAL LEAST(3600, 30 * POW(2, GREATEST(0, LEAST(tentativas, 8) - 1))) SECOND)
                                     ORDER BY id LIMIT 30")->fetchAll(\PDO::FETCH_COLUMN);
                if ($ids) {
                    if (function_exists('fastcgi_finish_request')) {
                        fastcgi_finish_request(); // onde existe (PHP-FPM), a resposta já foi: ninguém espera
                    } else {
                        while (ob_get_level() > 0) {
                            @ob_end_flush(); // a página já sai inteira para o navegador; só a conexão espera o envio
                        }
                        @flush();
                    }
                    self::enviarItens($ids, self::RETOMADA_ORCAMENTO);
                }
            } finally {
                $pdo->query("SELECT RELEASE_LOCK('{$trava}')");
            }
        } catch (\Throwable $e) {
            error_log('[vcostaedu][moodle] retomar: ' . $e->getMessage());
        }
    }

    /** As falhas que o usuário do aluno resolve: com o usuário no Moodle, as inscrições delas voltam à fila. */
    public const DEPENDEM_DO_USUARIO = ['sem_email', 'email_repetido', 'sem_dominio', 'vcostaedu_usernotfound'];

    /**
     * O aluno foi ao Moodle: as inscrições dele nas disciplinas que tinham falhado pelo mesmo motivo (sem e-mail, por
     * exemplo) voltam à fila; na mesma requisição, vão logo depois, se o orçamento deixar. @return int[] os itens
     */
    private static function inscricoesDeVolta(int $alunoId): array
    {
        $pdo = self::pdo();
        $st = $pdo->prepare("SELECT id FROM adms_moodle_envios WHERE tipo = 'enturmar' AND aluno_id = ? AND situacao = 'falhou' AND codigo IN ('" . implode("','", self::DEPENDEM_DO_USUARIO) . "')");
        $st->execute([$alunoId]);
        $ids = array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));
        if ($ids) {
            $pdo->exec("UPDATE adms_moodle_envios SET situacao = 'pendente' WHERE id IN (" . implode(',', $ids) . ')');
        }
        return $ids;
    }

    /** A saúde: a última chamada que deu certo, quantos pendentes e quantos falharam. */
    public static function saude(): array
    {
        if (!self::disponivel()) {
            return ['conectado' => false, 'ultima_ok' => null, 'pendentes' => 0, 'falhas' => 0, 'avisos' => 0];
        }
        $n = self::pdo()->query("SELECT SUM(situacao = 'pendente') p, SUM(situacao = 'falhou') f, SUM(situacao = 'aviso') a FROM adms_moodle_envios")->fetch(\PDO::FETCH_ASSOC);
        $c = self::conexao();
        return ['conectado' => self::conectado(), 'ultima_ok' => $c['ultima_ok_em'] ?? null, 'pendentes' => (int) $n['p'], 'falhas' => (int) $n['f'], 'avisos' => (int) $n['a']];
    }

    /** O estado de uma oferta para a tela: "Ligada ao Moodle", "Ainda não enviada", "Moodle não conectado". */
    public static function estadoOferta(?int $idMoodle): array
    {
        if (!self::disponivel() || !self::conectado()) {
            return ['codigo' => 'desconectado', 'texto' => 'Moodle não conectado'];
        }
        return $idMoodle ? ['codigo' => 'ligada', 'texto' => 'Ligada ao Moodle'] : ['codigo' => 'pendente', 'texto' => 'Ainda não enviada'];
    }

    /**
     * O aluno no Moodle, para a API (Onda 3, Etapa 9): ['conectado', 'enviado' (o aluno está ligado a um usuário lá),
     * 'disciplinas' (em quantas disciplinas do Moodle a enturmação dele já foi), 'pendentes', 'falhas', 'acesso_pronto'
     * (o portal mostra o botão: conectado, enviado, ativo, com matrícula cursando e ao menos uma disciplina)]. Nunca o
     * id, o nome de usuário nem o link.
     */
    public static function estadoAluno(int $alunoId): array
    {
        $nada = ['conectado' => false, 'enviado' => false, 'disciplinas' => 0, 'pendentes' => 0, 'falhas' => 0, 'acesso_pronto' => false];
        try {
            if (!self::disponivel() || !self::conectado()) {
                return $nada;
            }
            $pdo = self::pdo();
            $enviado = self::vinculo('aluno', $alunoId) > 0;
            $st = $pdo->prepare("SELECT SUM(situacao = 'pendente') p, SUM(situacao = 'falhou') f FROM adms_moodle_envios WHERE aluno_id = ?");
            $st->execute([$alunoId]);
            $fila = $st->fetch(\PDO::FETCH_ASSOC) ?: [];
            $st = $pdo->prepare("SELECT COUNT(DISTINCT o.id) FROM portal_disciplinas_alunos d JOIN col_usuarios c ON c.rm = d.rm JOIN rm_professores_materias o ON o.id = d.rm_prof_mat_id
                                  JOIN rm_matriculas_curso m ON m.usuario_id = c.id AND m.curso_id = o.curso_id AND m.situacao_id = 1
                                 WHERE c.id = ? AND c.col_sits_usuario_id = 1 AND o.idMoodle > 0
                                   AND EXISTS (SELECT 1 FROM adms_moodle_envios e WHERE e.chave = CONCAT('enturmar:', c.id, ':', o.id) AND e.situacao IN ('enviado', 'aviso'))");
            $st->execute([$alunoId]);
            $disc = (int) $st->fetchColumn();
            return ['conectado' => true, 'enviado' => $enviado, 'disciplinas' => $disc, 'pendentes' => (int) ($fila['p'] ?? 0), 'falhas' => (int) ($fila['f'] ?? 0),
                'acesso_pronto' => $enviado && $disc > 0];
        } catch (\Throwable $e) {
            error_log('[vcostaedu][moodle] estado do aluno: ' . $e->getMessage());
            return $nada;
        }
    }

    /** O vínculo de um aluno ou professor (o id do usuário no Moodle), ou 0. */
    public static function vinculo(string $tipo, int $localId): int
    {
        if (!self::disponivel()) {
            return 0;
        }
        $st = self::pdo()->prepare('SELECT moodle_id FROM adms_moodle_vinculos WHERE tipo = ? AND local_id = ? ORDER BY id LIMIT 1');
        $st->execute([$tipo, $localId]);
        return (int) $st->fetchColumn();
    }
}
