<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * A AUDITORIA DE NOTAS E FALTAS — Onda 2, Etapa 3 (docs/AUDITORIA.md).
 *
 * Quem grava nota, falta, presença, compensação ou o fechamento chama este helper NO MESMO PONTO e DENTRO da mesma
 * transação (\Core\Transacao): se o registro da auditoria falhar, a gravação toda é desfeita. Salvar sem mudar o
 * valor não gera registro.
 *
 * Cada linha guarda o valor anterior e o novo, quem (usuário e o perfil no momento), quando, o IP (de um ponto só:
 * \Core\Seguranca::ipCliente, que só aceita o cabeçalho de proxy da lista PROXIES_CONFIAVEIS), o navegador resumido
 * e a ORIGEM: a tela do painel, a tela do professor, a importação ou a API (comOrigem()).
 *
 * Só acréscimo: não há aqui (nem em tela ou rota nenhuma) função que altere ou apague a auditoria. Até a migration
 * 0006 ser aplicada (D-28), nada é registrado e a gravação segue como antes.
 */
final class AdmsAuditoria
{
    public const PAINEL = 'painel';
    public const PROFESSOR = 'professor';
    public const IMPORTACAO = 'importacao';
    public const API = 'api';

    private static ?string $origem = null;
    /** @var array{id:?int, nome:string, perfil:string}|null */
    private static ?array $autor = null;
    private static array $ofertas = [];

    public static function ativa(): bool
    {
        return AdmsAtualizacaoPendente::exige('0006');
    }

    /** Roda $fn com a origem dada (a importação, a API): as gravações lá dentro levam essa origem. */
    public static function comOrigem(string $origem, callable $fn)
    {
        $antes = self::$origem;
        self::$origem = $origem;
        try {
            return $fn();
        } finally {
            self::$origem = $antes;
        }
    }

    /** Roda $fn em nome de um autor que não é o usuário da sessão (a chave de API). */
    public static function comAutor(?int $usuarioId, string $nome, string $perfil, callable $fn)
    {
        $antes = self::$autor;
        self::$autor = ['id' => $usuarioId, 'nome' => $nome, 'perfil' => $perfil];
        try {
            return $fn();
        } finally {
            self::$autor = $antes;
        }
    }

    public static function nota(int $ofertaId, int $atividadeId, string $rm, ?string $aluno, ?string $antes, ?string $depois): void
    {
        /* Onda 4, Etapa 3: na escola, a etapa da atividade vai no registro */
        $etapa = class_exists(\App\adms\Models\escola\AdmsEtapaNotas::class) ? \App\adms\Models\escola\AdmsEtapaNotas::etapaDaAtividade($atividadeId) : null;
        self::gravar(['tipo' => 'nota', 'campo' => 'nota', 'oferta_id' => $ofertaId, 'atividade_id' => $atividadeId, 'rm' => $rm, 'aluno_nome' => $aluno]
            + ($etapa ? ['detalhe' => 'etapa: ' . $etapa['nome']] : []), $antes, $depois);
    }

    /** Onda 4, Etapa 3: a nota da recuperação de uma etapa (escola). */
    public static function recuperacao(int $ofertaId, string $rm, ?string $aluno, ?string $antes, ?string $depois, string $etapa): void
    {
        self::gravar(['tipo' => 'nota', 'campo' => 'recuperacao', 'oferta_id' => $ofertaId, 'rm' => $rm, 'aluno_nome' => $aluno, 'detalhe' => 'recuperação — etapa: ' . $etapa], $antes, $depois);
    }

    /** Correção da escola, Parte 3 (A2; D-102): a nota (ou as faltas) trazida da escola de origem, numa etapa. */
    public static function transferencia(int $ofertaId, string $rm, ?string $aluno, ?string $antes, ?string $depois, string $etapa, string $campo = 'transferencia'): void
    {
        self::gravar(['tipo' => 'nota', 'campo' => $campo, 'oferta_id' => $ofertaId, 'rm' => $rm, 'aluno_nome' => $aluno, 'detalhe' => ($campo === 'transferencia' ? 'nota de transferência' : 'faltas de transferência') . ' — etapa: ' . $etapa], $antes, $depois);
    }

    /** As faltas do aluno na oferta (ou as compensadas, $campo = 'faltas_compensadas'). Sem linha antes = 0. */
    public static function falta(int $ofertaId, string $rm, ?string $aluno, ?int $antes, int $depois, string $campo = 'faltas', ?string $detalhe = null): void
    {
        self::gravar(['tipo' => $campo === 'faltas' ? 'falta' : 'compensacao', 'campo' => $campo, 'oferta_id' => $ofertaId, 'rm' => $rm, 'aluno_nome' => $aluno, 'detalhe' => $detalhe],
            (string) (int) ($antes ?? 0), (string) $depois, true);
    }

    /** A presença do aluno num encontro da chamada (true presente, false ausente, null sem registro). */
    public static function presenca(int $ofertaId, int $encontroId, ?string $dataAula, string $rm, ?string $aluno, ?bool $antes, ?bool $depois): void
    {
        $t = static fn(?bool $v): ?string => $v === null ? null : ($v ? 'presente' : 'ausente');
        self::gravar(['tipo' => 'presenca', 'campo' => 'presenca', 'oferta_id' => $ofertaId, 'encontro_id' => $encontroId, 'data_aula' => $dataAula, 'rm' => $rm, 'aluno_nome' => $aluno],
            $t($antes), $t($depois));
    }

    /** O encerramento e a reabertura da disciplina (com a justificativa da reabertura). */
    public static function fechamento(int $ofertaId, string $acao, ?string $detalhe = null): void
    {
        self::gravar(['tipo' => 'fechamento', 'campo' => 'situacao', 'oferta_id' => $ofertaId, 'acao' => $acao, 'detalhe' => $detalhe],
            $acao === 'encerrou' ? 'aberta' : 'encerrada', $acao === 'encerrou' ? 'encerrada' : 'aberta');
    }

    private static function oferta(int $ofertaId): array
    {
        if (!isset(self::$ofertas[$ofertaId])) {
            $ler = new AdmsRead();
            $ler->fullRead("SELECT o.curso_id, (SELECT COUNT(*) FROM rm_media_fechamentos f WHERE f.rm_prof_mat_id = o.id) AS fechamentos FROM rm_professores_materias o WHERE o.id =:id LIMIT :limit", "id={$ofertaId}&limit=1");
            $r = $ler->getResultado()[0] ?? [];
            self::$ofertas[$ofertaId] = ['curso_id' => isset($r['curso_id']) ? (int) $r['curso_id'] : null, 'fechamentos' => (int) ($r['fechamentos'] ?? 0)];
        }
        return self::$ofertas[$ofertaId];
    }

    /** "Chrome 131 · Windows": o navegador e o sistema, sem a assinatura inteira (dado pessoal desnecessário). */
    public static function navegador(?string $ua = null): string
    {
        $ua = (string) ($ua ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if ($ua === '') {
            return '';
        }
        $nav = 'outro';
        foreach (['Edg' => 'Edge', 'OPR' => 'Opera', 'Firefox' => 'Firefox', 'Chrome' => 'Chrome', 'Version' => 'Safari', 'curl' => 'curl'] as $marca => $nome) {
            if (preg_match('#' . $marca . '/(\d+)#', $ua, $m)) {
                $nav = $nome . ' ' . $m[1];
                break;
            }
        }
        $so = 'outro sistema';
        foreach (['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iOS', 'iPad' => 'iOS', 'Mac OS' => 'macOS', 'Linux' => 'Linux'] as $marca => $nome) {
            if (stripos($ua, $marca) !== false) {
                $so = $nome;
                break;
            }
        }
        return mb_substr($nav . ' · ' . $so, 0, 120);
    }

    private static function autor(): array
    {
        if (self::$autor !== null) {
            return self::$autor;
        }
        $id = (int) ($_SESSION['colUsuario_id'] ?? 0);
        $nome = (string) ($_SESSION['colUsuario_nome'] ?? '');
        $perfil = (string) ($_SESSION['colUsuario_nivel_nome'] ?? '');
        if ($id > 0 && ($nome === '' || $perfil === '')) {
            $ler = new AdmsRead();
            $ler->fullRead("SELECT u.nome, n.nome AS perfil FROM adms_usuarios u LEFT JOIN adms_niveis_acessos n ON n.id = u.adms_niveis_acesso_id WHERE u.id =:id LIMIT :limit", "id={$id}&limit=1");
            $r = $ler->getResultado()[0] ?? [];
            $nome = $nome !== '' ? $nome : (string) ($r['nome'] ?? '');
            $perfil = $perfil !== '' ? $perfil : (string) ($r['perfil'] ?? '');
        }
        return ['id' => $id ?: null, 'nome' => $nome !== '' ? $nome : 'sem sessão', 'perfil' => $perfil !== '' ? $perfil : '—'];
    }

    private static function gravar(array $linha, ?string $antes, ?string $depois, bool $numerico = false): void
    {
        if (!self::ativa()) {
            return;
        }
        $antes = $antes === null || $antes === '' ? null : (string) $antes;
        $depois = $depois === null || $depois === '' ? null : (string) $depois;
        if ($antes === $depois || ($numerico && (int) $antes === (int) $depois)) {
            return; // salvar sem mudar o valor não gera registro
        }
        if (!isset($linha['acao'])) {
            /* nota e presença: vazio → valor lançou, valor → vazio apagou; faltas e compensadas (número, sem linha = 0):
               de 0 lançou, senão alterou */
            if ($numerico) {
                $linha['acao'] = (int) $antes === 0 ? 'lancou' : 'alterou';
            } else {
                $linha['acao'] = $antes === null ? 'lancou' : ($depois === null ? 'apagou' : 'alterou');
            }
        }
        $o = self::oferta((int) $linha['oferta_id']);
        $autor = self::autor();
        $origem = self::$origem ?? (AdmsNivel::papel() === AdmsNivel::PROFESSOR ? self::PROFESSOR : self::PAINEL);
        $dados = $linha + [
            'apos_fechamento' => ($linha['tipo'] !== 'fechamento' && $o['fechamentos'] > 0) || ($linha['tipo'] === 'fechamento' && $o['fechamentos'] > 1) ? 1 : 0,
            'curso_id' => $o['curso_id'],
            'valor_anterior' => $antes,
            'valor_novo' => $depois,
            'usuario_id' => $autor['id'],
            'usuario_nome' => mb_substr($autor['nome'], 0, 255),
            'perfil' => mb_substr($autor['perfil'], 0, 80),
            'origem' => $origem,
            'ip' => mb_substr(\Core\Seguranca::ipCliente(), 0, 45),
            'navegador' => self::navegador(),
            'ambiente' => AdmsAmbiente::atual(),
        ];
        $dados = array_filter($dados, static fn($v) => $v !== null);
        $c = new AdmsCreate();
        $c->exeCreate('rm_auditoria_lancamentos', $dados);
        if (!$c->getResultado()) {
            /* na mesma transação: sem o registro, a gravação não vale */
            if (\Core\Transacao::ativa()) {
                \Core\Transacao::falhar('não foi possível registrar a auditoria');
            }
            error_log('[vcostaedu][auditoria] não foi possível registrar: ' . json_encode($linha, JSON_UNESCAPED_UNICODE));
        }
    }
}
