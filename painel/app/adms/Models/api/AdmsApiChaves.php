<?php

namespace App\adms\Models\api;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * AS CHAVES DA API (Onda 2, Etapa 6). A chave é "vce_" + 40 caracteres aleatórios; fica gravada só como sha256 —
 * quem a cria a vê uma vez. Validade opcional; revogar é para sempre. A API grava em nome de quem criou a chave.
 *
 * Onda 3, Etapa 9: o LIMITE POR ASSUNTO. Cada chave diz, assunto por assunto, se pode consultar ou gravar (gravar inclui
 * consultar o mesmo assunto); o padrão é o mais restrito (nada marcado = recusada a criação). A coluna "permissoes"
 * guarda "alunos:gravar cadastros:ler …"; o "escopo" antigo fica preenchido só para quem ainda o lê.
 */
class AdmsApiChaves
{
    public const LEITURA = 'leitura';
    public const ESCRITA = 'escrita';
    /** os assuntos da API: o nome que a tela mostra e o que cada um cobre */
    public const ASSUNTOS = [
        'alunos' => ['Alunos', 'o cadastro do aluno (consultar, cadastrar, atualizar)'],
        'matriculas' => ['Matrículas', 'a matrícula no curso e a situação (matricular, trancar, reativar, cancelar)'],
        'enturmacao' => ['Enturmação', 'as disciplinas do aluno no ano (consultar e gravar a enturmação)'],
        'responsaveis' => ['Responsáveis', 'o responsável financeiro e o acadêmico titular de cada aluno, por ano letivo (consultar e designar)'],
        'cadastros' => ['Cadastros de apoio', 'cursos, grades, anos letivos, polos, modalidades, formas de ingresso, disciplinas e ofertas (só consulta)'],
    ];
    /** assuntos que só existem numa instalação de escola (Onda 7, Etapa 2): no ensino superior, a tela das chaves não os mostra */
    public const SO_ESCOLA = ['responsaveis'];
    /** assuntos que só têm consulta */
    public const SO_LEITURA = ['cadastros'];

    /** As permissões do texto gravado: ['alunos' => 'gravar', 'cadastros' => 'ler', …]. */
    public static function permissoes(?string $texto): array
    {
        $p = [];
        foreach (preg_split('/\s+/', trim((string) $texto)) ?: [] as $item) {
            [$a, $acao] = array_pad(explode(':', $item, 2), 2, '');
            if (isset(self::ASSUNTOS[$a]) && in_array($acao, ['ler', 'gravar'], true) && (($p[$a] ?? '') !== 'gravar')) {
                $p[$a] = $acao;
            }
        }
        return $p;
    }

    /** A chave pode fazer isso? (gravar inclui consultar o mesmo assunto) */
    public static function pode(array $chave, string $assunto, bool $gravar): bool
    {
        $acao = self::permissoes($chave['permissoes'] ?? null)[$assunto] ?? '';
        return $gravar ? $acao === 'gravar' : in_array($acao, ['ler', 'gravar'], true);
    }

    /** O limite em palavras, para a tela e para a recusa ("Alunos: gravar · Cadastros de apoio: consultar"). */
    public static function descrever(?string $texto): string
    {
        $p = self::permissoes($texto);
        $partes = [];
        foreach (self::ASSUNTOS as $a => [$nome]) {
            if (isset($p[$a])) {
                $partes[] = $nome . ': ' . ($p[$a] === 'gravar' ? 'consultar e gravar' : 'consultar');
            }
        }
        return $partes ? implode(' · ', $partes) : 'nada';
    }

    private string $erro = '';

    public function getErro(): string
    {
        return $this->erro;
    }

    private static function pdo(): \PDO
    {
        static $pdo = null;
        if ($t = \Core\Transacao::pdo()) {
            return $t;
        }
        return $pdo ??= \VcBanco::daRequisicao(); // Onda 6, Etapa 1: a conexão da requisição
    }

    public static function hash(string $chave): string
    {
        return hash('sha256', $chave);
    }

    /** Cria a chave e devolve o texto dela (a única vez), ou null com o erro. */
    /** @param array $permissoes ['alunos' => 'ler'|'gravar'|'', …] (o formulário) */
    public function criar(string $nome, array $permissoes, string $validade, int $usuarioId): ?string
    {
        $nome = trim($nome);
        if ($nome === '' || mb_strlen($nome) > 100) {
            $this->erro = 'Dê um nome à chave (até 100 caracteres), para saber depois quem a usa.';
            return null;
        }
        $itens = [];
        foreach (self::ASSUNTOS as $a => $x) {
            $acao = (string) ($permissoes[$a] ?? '');
            if ($acao === 'gravar' && in_array($a, self::SO_LEITURA, true)) {
                $acao = 'ler';
            }
            if (in_array($acao, ['ler', 'gravar'], true)) {
                $itens[] = $a . ':' . $acao;
            }
        }
        if (!$itens) {
            $this->erro = 'Marque o que a chave pode fazer, assunto por assunto — só o que o outro sistema precisa.';
            return null;
        }
        $texto = implode(' ', $itens);
        $escopo = str_contains($texto, ':gravar') ? self::ESCRITA : self::LEITURA;
        $validade = trim($validade);
        if ($validade !== '') {
            $d = \DateTime::createFromFormat('!Y-m-d', $validade);
            if (!$d || $d->format('Y-m-d') !== $validade || $validade < date('Y-m-d')) {
                $this->erro = 'A validade precisa ser uma data de hoje em diante (ou fique vazia, sem prazo).';
                return null;
            }
        }
        $chave = 'vce_' . bin2hex(random_bytes(20));
        $st = self::pdo()->prepare('INSERT INTO adms_api_chaves (nome, prefixo, hash, escopo, permissoes, validade, situacao, usuario_id) VALUES (?, ?, ?, ?, ?, ?, 1, ?)');
        $st->execute([$nome, substr($chave, 0, 12), self::hash($chave), $escopo, $texto, $validade === '' ? null : $validade, $usuarioId]);
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(484, 200, 1, 'Chave de API criada: ' . substr($chave, 0, 12) . " ({$nome}, {$texto})");
        return $chave;
    }

    public function revogar(int $id, int $usuarioId): bool
    {
        $st = self::pdo()->prepare('UPDATE adms_api_chaves SET situacao = 2, revogada_por = ?, revogada_em = NOW() WHERE id = ? AND situacao = 1');
        $st->execute([$usuarioId, $id]);
        if ($st->rowCount() !== 1) {
            $this->erro = 'Chave não encontrada ou já revogada.';
            return false;
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(485, 200, 1, "Chave de API revogada: id {$id}");
        return true;
    }

    public static function listar(): array
    {
        return self::pdo()->query('SELECT k.*, u.nome AS criador, r.nome AS revogador,
                (SELECT COUNT(*) FROM adms_api_chamadas c WHERE c.chave_id = k.id) AS chamadas
              FROM adms_api_chaves k LEFT JOIN adms_usuarios u ON u.id = k.usuario_id LEFT JOIN adms_usuarios r ON r.id = k.revogada_por
             ORDER BY k.situacao, k.id DESC')->fetchAll();
    }

    /**
     * A chave do cabeçalho Authorization: a linha dela, ou [null, código, mensagem].
     * @return array{0: ?array, 1: ?string, 2: ?string}
     */
    public static function autenticar(string $cabecalho): array
    {
        if (!preg_match('/^Bearer\s+(\S+)$/i', trim($cabecalho), $m)) {
            return [null, 'nao_autenticado', 'Envie a chave da API no cabeçalho Authorization: Bearer <chave>.'];
        }
        $st = self::pdo()->prepare('SELECT * FROM adms_api_chaves WHERE hash = ? LIMIT 1');
        $st->execute([self::hash($m[1])]);
        $k = $st->fetch();
        if (!$k) {
            return [null, 'nao_autenticado', 'Chave da API inválida.'];
        }
        if ((int) $k['situacao'] !== 1) {
            return [null, 'chave_revogada', 'Esta chave da API foi revogada.'];
        }
        if ($k['validade'] !== null && $k['validade'] < date('Y-m-d')) {
            return [null, 'chave_vencida', 'Esta chave da API venceu em ' . date('d/m/Y', strtotime((string) $k['validade'])) . '.'];
        }
        return [$k, null, null];
    }

    public static function registrarUso(int $id, string $ip): void
    {
        self::pdo()->prepare('UPDATE adms_api_chaves SET ultimo_uso_em = NOW(), ultimo_uso_ip = ? WHERE id = ?')->execute([$ip, $id]);
    }
}
