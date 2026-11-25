<?php

namespace App\adms\Models\manutencao;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * O REGISTRO DA INSTALAÇÃO (Onda 6, Etapa 7; DECISIONS D-59), voluntário, no modelo do Moodle.
 *
 * Vai SEMPRE (quando a pessoa registra): um identificador aleatório desta instalação (não diz quem é), a versão, o tipo
 * de instituição, o país e a UF, os módulos ligados e três contagens (alunos, cursos, turmas). Vai SÓ SE A PESSOA
 * QUISER: o nome da instituição, o endereço do site, um e-mail para avisos de segurança e aparecer na lista de
 * instituições. NUNCA vai dado de aluno, professor ou responsável (nem nome, nem RA, nem e-mail deles).
 *
 * O envio é um POST em JSON para VcLegal::URL_REGISTRO (ou a constante URL_REGISTRO da Config), com 3 segundos de
 * tempo: o endereço fora do ar deixa o registro "pendente de envio", sem erro na tela. O reenvio é periódico (uma vez
 * por mês, ou quando está pendente, no máximo uma tentativa por dia), pela tela dos Avisos do sistema; se o que a
 * pessoa escolheu mandar mudou (o nome, o site), o reenvio espera uma nova confirmação. "Cancelar registro" avisa o
 * endereço e para os reenvios. O estado fica em adms_instalacao_estado ('registro.instalacao').
 */
require_once dirname(__DIR__, 5) . '/core/Legal.php';

class AdmsRegistroInstalacao
{
    public const CHAVE = 'registro.instalacao';
    private const TEMPO = 3;
    private const REENVIO_DIAS = 30;
    private const TENTATIVA_HORAS = 24;

    public static function endereco(): string
    {
        return defined('URL_REGISTRO') ? (string) URL_REGISTRO : \VcLegal::URL_REGISTRO;
    }

    private static function pdo(): \PDO
    {
        return \VcBanco::daRequisicao();
    }

    public static function estado(): array
    {
        $v = \App\adms\Models\helper\AdmsAmbiente::estado(self::CHAVE);
        $e = $v ? json_decode($v, true) : null;
        return is_array($e) ? $e : ['situacao' => 'nao'];
    }

    private static function gravar(array $e): void
    {
        \App\adms\Models\helper\AdmsAmbiente::gravarEstado(self::CHAVE, json_encode($e, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** Os valores de agora dos campos opcionais (o que a pessoa pode escolher mandar). */
    public static function opcionaisDeAgora(): array
    {
        $emp = self::pdo()->query('SELECT nome, site FROM adms_empresas ORDER BY id LIMIT 1')->fetch(\PDO::FETCH_ASSOC) ?: [];
        return ['nome' => trim((string) ($emp['nome'] ?? '')), 'site' => trim((string) ($emp['site'] ?? ''))];
    }

    /**
     * O que vai, exatamente (a tela mostra este mesmo conteúdo antes de enviar).
     * $escolhas: ['nome' => bool, 'site' => bool, 'email' => string, 'listar' => bool].
     */
    public static function conteudo(array $escolhas, ?string $id = null): array
    {
        $pdo = self::pdo();
        $versao = (string) $pdo->query('SELECT numero FROM adms_versoes WHERE situacao = 1 ORDER BY id DESC LIMIT 1')->fetchColumn();
        $tipo = \App\adms\Models\helper\AdmsTipoInstituicao::atual();
        $uf = (string) ($pdo->query('SELECT uf FROM adms_empresas ORDER BY id LIMIT 1')->fetchColumn() ?: '');
        $T = \App\adms\Models\helper\AdmsTipoInstituicao::class;
        $modulos = array_values(array_filter(array_unique(array_values($T::modulos())), fn($m) => $T::moduloLigado($m)));
        sort($modulos);
        $conta = fn(string $sql) => (int) $pdo->query($sql)->fetchColumn();
        $turmas = in_array($tipo, ['escola', 'livre'], true) // D-106
            ? $conta('SELECT COUNT(*) FROM esc_turmas')
            : $conta("SELECT COUNT(*) FROM (SELECT DISTINCT codtur, ano FROM rm_professores_materias WHERE codtur IS NOT NULL AND codtur <> '') t");
        $c = [
            'identificador' => $id ?? '(gerado no envio)',
            'versao' => $versao,
            'tipo_instituicao' => $tipo,
            'pais' => 'BR',
            'uf' => $uf,
            'modulos' => $modulos,
            'contagens' => ['alunos' => $conta('SELECT COUNT(*) FROM col_usuarios WHERE col_niveis_acesso_id = 1'), 'cursos' => $conta('SELECT COUNT(*) FROM rm_cursos'), 'turmas' => $turmas],
        ];
        $op = self::opcionaisDeAgora();
        if (!empty($escolhas['nome']) && $op['nome'] !== '') {
            $c['nome_instituicao'] = $op['nome'];
        }
        if (!empty($escolhas['site']) && $op['site'] !== '') {
            $c['site'] = $op['site'];
        }
        if (!empty($escolhas['email'])) {
            $c['email_seguranca'] = (string) $escolhas['email'];
        }
        $c['aparecer_na_lista'] = !empty($escolhas['listar']);
        return $c;
    }

    /** A marca dos opcionais escolhidos: mudou (o nome, o site, o e-mail), o reenvio espera a confirmação. */
    private static function marca(array $escolhas): string
    {
        $op = self::opcionaisDeAgora();
        return hash('sha256', json_encode([!empty($escolhas['nome']) ? $op['nome'] : null, !empty($escolhas['site']) ? $op['site'] : null,
            (string) ($escolhas['email'] ?? ''), !empty($escolhas['listar'])], JSON_UNESCAPED_UNICODE));
    }

    /** Registrar (ou confirmar de novo): guarda as escolhas e tenta enviar. @return string|true a recusa, ou true */
    public static function registrar(array $escolhas)
    {
        $email = trim((string) ($escolhas['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'O e-mail para avisos de segurança não é um endereço válido (ou deixe em branco).';
        }
        $escolhas = ['nome' => !empty($escolhas['nome']), 'site' => !empty($escolhas['site']), 'email' => $email, 'listar' => !empty($escolhas['listar'])];
        $e = self::estado();
        $id = (string) ($e['identificador'] ?? '');
        if (!preg_match('/^[0-9a-f]{32}$/', $id) || ($e['situacao'] ?? '') === 'cancelado') {
            $id = bin2hex(random_bytes(16));
        }
        $e = ['situacao' => 'pendente', 'identificador' => $id, 'escolhas' => $escolhas, 'marca' => self::marca($escolhas), 'registrado_em' => date('Y-m-d H:i:s'),
            'enviado_em' => $e['enviado_em'] ?? null, 'tentativa_em' => null];
        self::gravar($e);
        self::tentar();
        return true;
    }

    /** Tenta enviar agora (o registro pendente, ou o cancelamento pendente). */
    public static function tentar(): array
    {
        $e = self::estado();
        $e['tentativa_em'] = date('Y-m-d H:i:s');
        if (($e['situacao'] ?? '') === 'cancelar_pendente') {
            $ok = self::enviar(['identificador' => $e['identificador'] ?? '', 'acao' => 'cancelar']);
            $e['situacao'] = $ok ? 'cancelado' : 'cancelar_pendente';
            self::gravar($e);
            return $e;
        }
        if (!in_array($e['situacao'] ?? '', ['pendente', 'enviado'], true)) {
            return $e;
        }
        $ok = self::enviar(self::conteudo($e['escolhas'] ?? [], (string) $e['identificador']) + ['acao' => 'registrar']);
        if ($ok) {
            $e['situacao'] = 'enviado';
            $e['enviado_em'] = date('Y-m-d H:i:s');
        } else {
            $e['situacao'] = 'pendente';
        }
        self::gravar($e);
        return $e;
    }

    /** O reenvio periódico (a tela dos Avisos do sistema): no máximo uma tentativa por dia. */
    public static function periodico(): array
    {
        $e = self::estado();
        $sit = $e['situacao'] ?? 'nao';
        if (!in_array($sit, ['pendente', 'enviado', 'cancelar_pendente'], true)) {
            return $e;
        }
        if (!empty($e['tentativa_em']) && time() - strtotime((string) $e['tentativa_em']) < self::TENTATIVA_HORAS * 3600) {
            return $e;
        }
        if ($sit === 'enviado') {
            if (!empty($e['enviado_em']) && time() - strtotime((string) $e['enviado_em']) < self::REENVIO_DIAS * 86400) {
                return $e;
            }
            if (($e['marca'] ?? '') !== self::marca($e['escolhas'] ?? [])) {
                $e['situacao'] = 'confirmar'; // o que a pessoa escolheu mandar mudou: só com a confirmação dela
                self::gravar($e);
                return $e;
            }
        }
        return self::tentar();
    }

    /** Cancelar: avisa o endereço (se não responder, tenta de novo depois) e para os reenvios. */
    public static function cancelar(): array
    {
        $e = self::estado();
        if (empty($e['identificador']) || ($e['situacao'] ?? 'nao') === 'nao') {
            return ['situacao' => 'nao'];
        }
        $e['situacao'] = 'cancelar_pendente';
        self::gravar($e);
        return self::tentar();
    }

    /** O POST em JSON; true só com a resposta 2xx. Nunca lança. */
    private static function enviar(array $corpo): bool
    {
        $url = self::endereco();
        if ($url === '' || !preg_match('#^https?://#i', $url) || !function_exists('curl_init')) {
            return false;
        }
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($corpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => self::TEMPO,
                CURLOPT_TIMEOUT => self::TEMPO,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_USERAGENT => 'VCOSTAEDU',
            ]);
            curl_exec($ch);
            $cod = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            return $cod >= 200 && $cod < 300;
        } catch (\Throwable $ex) {
            error_log('[vcostaedu][registro] ' . $ex->getMessage());
            return false;
        }
    }

    /** O texto da situação, para a tela. */
    public const SITUACOES = [
        'nao' => 'Esta instalação não está registrada.',
        'pendente' => 'Registrada aqui, pendente de envio: o endereço do projeto ainda não respondeu. O sistema tenta de novo sozinho.',
        'enviado' => 'Registrada e enviada.',
        'confirmar' => 'O que você escolheu mandar mudou (o nome ou o site da instituição): confira abaixo e confirme para o envio seguinte.',
        'cancelar_pendente' => 'Cancelamento pedido, pendente de envio: o endereço do projeto ainda não respondeu. Nada mais é enviado.',
        'cancelado' => 'O registro foi cancelado. Nada mais é enviado.',
    ];
}
