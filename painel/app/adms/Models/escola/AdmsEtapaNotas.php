<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsRead;
use App\adms\Models\notas\AdmsCalculoNotas as Calc;

/**
 * AS ETAPAS DE UMA OFERTA DA ESCOLA (Onda 4, Etapa 3; docs/ESCOLA.md, D-48): fechar e reabrir a etapa, a nota da
 * recuperação, as faltas da etapa e a trava do lançamento.
 *   - FECHAR a etapa: o cálculo da etapa no modo final (o motor, com a regra da oferta), guardado por aluno (a média, a
 *     recuperação, a nota da etapa) com a regra copiada em JSON e o sha256 — como no encerramento da oferta. Depois de
 *     fechada, a etapa não recebe nota, falta nem recuperação.
 *   - REABRIR (com o motivo): o fechamento vigente vira "substituído" e fica guardado, com quem reabriu e quando.
 *   - O ANO só fecha (o encerramento da oferta) com todas as etapas fechadas.
 */
class AdmsEtapaNotas
{
    public static function etapa(int $etapaId): ?array
    {
        $l = new AdmsRead();
        $l->fullRead('SELECT id, ano, ordem, nome, inicio, fim, conselho FROM esc_etapas WHERE id = :id LIMIT :limit', "id={$etapaId}&limit=1");
        return $l->getResultado()[0] ?? null;
    }

    /** O fechamento vigente da etapa na oferta (ou null). */
    public static function fechamento(int $ofertaId, int $etapaId): ?array
    {
        $l = new AdmsRead();
        $l->fullRead("SELECT f.*, u.nome AS fechado_por_nome FROM esc_fechamentos f LEFT JOIN adms_usuarios u ON u.id = f.fechado_por
            WHERE f.oferta_id = :o AND f.etapa_id = :e AND f.situacao = 'vigente' LIMIT :limit", "o={$ofertaId}&e={$etapaId}&limit=1");
        return $l->getResultado()[0] ?? null;
    }

    /**
     * C6 (D-100): o nome da etapa com o artigo e a concordância certos — "o 3º bimestre ... fechado", "a 1ª etapa ...
     * fechada"; sem como saber, "a etapa X ... fechada". @return array{0: string, 1: string} [com o artigo, 'o' | 'a']
     */
    public static function comArtigo(string $nome): array
    {
        $n = mb_strtolower($nome);
        if (preg_match('/\b(bimestre|trimestre|semestre|quadrimestre|per[ií]odo|ciclo|m[oó]dulo)\b/u', $n)) {
            return ['o ' . $nome, 'o'];
        }
        if (preg_match('/\b(etapa|unidade|avalia[çc][ãa]o|fase)\b/u', $n)) {
            return ['a ' . $nome, 'a'];
        }
        if (str_contains($nome, 'º')) {
            return ['o ' . $nome, 'o'];
        }
        if (str_contains($nome, 'ª')) {
            return ['a ' . $nome, 'a'];
        }
        return ['a etapa ' . $nome, 'a'];
    }

    /** "O 3º bimestre" (no começo da frase). */
    private static function inicio(string $nome): string
    {
        $c = self::comArtigo($nome)[0];
        return mb_strtoupper(mb_substr($c, 0, 1)) . mb_substr($c, 1);
    }

    /** Quem reabre a etapa (a ação 108): a supervisão de secretaria, a direção acadêmica e o administrador. */
    public static function podeReabrir(): bool
    {
        return \App\adms\Models\helper\AdmsNivel::ehPapel('administrador', 'direcao', 'supervisao_secretaria');
    }

    /** A razão da trava (a etapa fechada), ou null. */
    public static function trava(int $ofertaId, int $etapaId): ?string
    {
        $f = self::fechamento($ofertaId, $etapaId);
        if (!$f) {
            return null;
        }
        $e = self::etapa($etapaId);
        [, $g] = self::comArtigo((string) ($e['nome'] ?? ''));
        return self::inicio((string) ($e['nome'] ?? '')) . ' está fechad' . $g . ' desde ' . date('d/m/Y', strtotime((string) $f['fechado_em'])) . ($f['fechado_por_nome'] ? ' (por ' . $f['fechado_por_nome'] . ')' : '')
            . ': nota, falta e recuperação não mudam. ' . (self::podeReabrir() ? 'Para corrigir, reabra a etapa, com o motivo.' : 'Para corrigir, peça a reabertura à supervisão de secretaria ou à direção acadêmica.');
    }

    /** A etapa de uma atividade da escola (pela data dela), ou null fora da escola. */
    public static function etapaDaAtividade(int $atividadeId): ?array
    {
        $l = new AdmsRead();
        $l->fullRead('SELECT a.rm_prof_mat_id, a.data_inicial, a.data_final, o.ano FROM rm_disciplina_atividade a INNER JOIN rm_professores_materias o ON o.id = a.rm_prof_mat_id
            WHERE a.id = :id LIMIT :limit', "id={$atividadeId}&limit=1");
        $a = $l->getResultado()[0] ?? null;
        if (!$a || !Calc::turmaDaOferta((int) $a['rm_prof_mat_id'])) {
            return null;
        }
        $e = Calc::etapaDaData(Calc::etapasDoAno((int) $a['ano']), $a['data_final'] ?: $a['data_inicial']);
        return $e ? $e + ['oferta_id' => (int) $a['rm_prof_mat_id']] : null;
    }

    /**
     * Onda 8, correção, acréscimo B (D-81): as atividades da oferta por etapa (a etapa da data da atividade, como no
     * fechamento), na ordem da data. @return array<int, list<array{id: int, nome: string}>> etapa => atividades
     */
    public static function atividadesPorEtapa(int $ofertaId, int $ano): array
    {
        $etapas = Calc::etapasDoAno($ano);
        $l = new AdmsRead();
        $l->fullRead('SELECT id, nomeAtividade, data_inicial, data_final FROM rm_disciplina_atividade WHERE rm_prof_mat_id = :o ORDER BY IFNULL(data_final, data_inicial), id', "o={$ofertaId}");
        $saida = [];
        foreach ($l->getResultado() ?: [] as $a) {
            $e = Calc::etapaDaData($etapas, $a['data_final'] ?: $a['data_inicial']);
            if ($e) {
                $saida[(int) $e['id']][] = ['id' => (int) $a['id'], 'nome' => (string) $a['nomeAtividade']];
            }
        }
        return $saida;
    }

    /**
     * A TRAVA DA ETAPA para uma atividade (a nota, editar a atividade): true (e a recusa em $_SESSION['msg']) se a etapa
     * da atividade está fechada. Fora da escola, sempre false.
     */
    public static function travaAtividade(int $atividadeId): bool
    {
        $e = self::etapaDaAtividade($atividadeId);
        $r = $e ? self::trava((int) $e['oferta_id'], (int) $e['id']) : null;
        if ($r !== null) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: "
                . htmlspecialchars($r, ENT_QUOTES, 'UTF-8') . '</div>';
            return true;
        }
        return false;
    }

    /** A trava para uma data (a atividade nova, ou a data nova de uma atividade): a razão, ou null. */
    public static function travaData(int $ofertaId, ?string $data): ?string
    {
        if (!Calc::turmaDaOferta($ofertaId)) {
            return null;
        }
        $o = Calc::dadosOferta($ofertaId);
        $e = Calc::etapaDaData(Calc::etapasDoAno((int) ($o['ano'] ?? 0)), $data);
        return $e ? self::trava($ofertaId, (int) $e['id']) : null;
    }

    /**
     * Fecha a etapa: guarda a nota da etapa de cada aluno, com a regra copiada.
     * @return true|string
     */
    public static function fechar(int $ofertaId, int $etapaId)
    {
        $e = self::etapa($etapaId);
        $o = Calc::dadosOferta($ofertaId);
        if (!$e || !$o || !Calc::turmaDaOferta($ofertaId) || (int) $e['ano'] !== (int) $o['ano']) {
            return 'Etapa não encontrada nesta disciplina.';
        }
        if (self::fechamento($ofertaId, $etapaId)) {
            return self::inicio((string) $e['nome']) . ' já está fechad' . self::comArtigo((string) $e['nome'])[1] . '.';
        }
        foreach (Calc::etapasDoAno((int) $o['ano']) as $antes) {
            if ((int) $antes['ordem'] < (int) $e['ordem'] && !self::fechamento($ofertaId, (int) $antes['id'])) {
                return 'Feche antes ' . self::comArtigo((string) $antes['nome'])[0] . ': as etapas fecham na ordem.';
            }
        }
        $calc = Calc::escolaComEtapaFechada($ofertaId, $etapaId);
        $ef = \App\adms\Models\notas\AdmsRegraMedia::efetivaOferta($ofertaId);
        $copia = ['origem' => $ef['origem'], 'versao' => $ef['versao'], 'regra' => $ef['regra'], 'escola' => Calc::confEscola()];
        $json = json_encode($copia, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $ofertaId, $etapaId, $calc, $json, $e) {
            $pdo = AdmsEscolaBanco::pdo();
            $n = (int) $pdo->query("SELECT COUNT(*) FROM esc_fechamentos WHERE oferta_id = {$ofertaId} AND etapa_id = {$etapaId}")->fetchColumn() + 1;
            $pdo->prepare("INSERT INTO esc_fechamentos (oferta_id, etapa_id, numero, regra_json, regra_hash, situacao, fechado_por, fechado_em) VALUES (?, ?, ?, ?, ?, 'vigente', ?, NOW())")
                ->execute([$ofertaId, $etapaId, $n, $json, hash('sha256', $json), (int) ($_SESSION['colUsuario_id'] ?? 0)]);
            $fid = (int) $pdo->lastInsertId();
            $st = $pdo->prepare('INSERT INTO esc_fechamento_notas (fechamento_id, rm, media, recuperacao, nota, lancadas, memoria) VALUES (?, ?, ?, ?, ?, ?, ?)');
            foreach ($calc as $a) {
                $et = null;
                foreach ($a['etapas'] as $x) {
                    if ((int) $x['id'] === $etapaId) {
                        $et = $x;
                    }
                }
                $linha = '';
                foreach ($a['memoria']['linhas'] as $l) {
                    if (str_starts_with($l, $e['nome'] . ':')) {
                        $linha = $l;
                    }
                }
                $T::exigir($st->execute([$fid, $a['rm'], $et['media'] ?? null, $et['recuperacao'] ?? null, $et['nota'] ?? null, (int) ($a['lancadas'] ?? 0), $linha]) ? true : null, 'a nota de ' . $a['rm']);
            }
            return true;
        });
        if (!$ok) {
            return 'A etapa não foi fechada (' . $T::ultimoErro() . '). Nada foi gravado.';
        }
        \App\adms\Models\helper\AdmsAuditoria::fechamento($ofertaId, 'encerrou', 'etapa: ' . $e['nome']);
        return true;
    }

    /** Reabre a etapa: o fechamento vigente fica guardado como "substituído". @return true|string */
    public static function reabrir(int $ofertaId, int $etapaId, string $motivo)
    {
        $f = self::fechamento($ofertaId, $etapaId);
        $e = self::etapa($etapaId);
        if (!$f || !$e) {
            return 'A etapa não está fechada.';
        }
        $motivo = trim($motivo);
        if (mb_strlen($motivo) < 10) {
            return 'Escreva o motivo da reabertura (ao menos 10 letras).';
        }
        $o = Calc::dadosOferta($ofertaId);
        if (!empty($o['encerrada_em'])) {
            return 'O ano desta disciplina já foi encerrado: reabra antes o encerramento da disciplina.';
        }
        foreach (Calc::etapasDoAno((int) $o['ano']) as $depois) {
            if ((int) $depois['ordem'] > (int) $e['ordem'] && self::fechamento($ofertaId, (int) $depois['id'])) {
                return 'Reabra antes ' . self::comArtigo((string) $depois['nome'])[0] . ': as etapas reabrem da última para a primeira.';
            }
        }
        AdmsEscolaBanco::pdo()->prepare("UPDATE esc_fechamentos SET situacao = 'substituido', reaberto_por = ?, reaberto_em = NOW(), motivo = ? WHERE id = ?")
            ->execute([(int) ($_SESSION['colUsuario_id'] ?? 0), mb_substr($motivo, 0, 500), (int) $f['id']]);
        \App\adms\Models\helper\AdmsAuditoria::fechamento($ofertaId, 'reabriu', 'etapa: ' . $e['nome'] . ' — ' . mb_substr($motivo, 0, 400));
        return true;
    }

    /** Os alunos da oferta na etapa: a média, se está em recuperação, a nota da recuperação, as faltas da etapa. */
    public static function quadro(int $ofertaId): array
    {
        $calc = Calc::oferta($ofertaId);
        $faltas = [];
        $l = new AdmsRead();
        $l->fullRead('SELECT etapa_id, rm, faltas FROM esc_faltas_etapa WHERE oferta_id = :o', "o={$ofertaId}");
        foreach ($l->getResultado() ?: [] as $f) {
            $faltas[(int) $f['etapa_id']][(string) $f['rm']] = (int) $f['faltas'];
        }
        return ['calc' => $calc, 'faltas' => $faltas];
    }

    /**
     * Lança a nota da recuperação da etapa (só de quem está em recuperação; vazio apaga).
     * @param array<string, string> $notas RA → nota
     * @return true|string
     */
    public static function recuperacao(int $ofertaId, int $etapaId, array $notas)
    {
        $e = self::etapa($etapaId);
        if (!$e || !Calc::turmaDaOferta($ofertaId)) {
            return 'Etapa não encontrada.';
        }
        if ($r = self::trava($ofertaId, $etapaId)) {
            return $r;
        }
        $emRec = [];
        foreach ((Calc::oferta($ofertaId)['alunos'] ?? []) as $a) {
            foreach ($a['etapas'] ?? [] as $x) {
                if ((int) $x['id'] === $etapaId && $x['emRecuperacao']) {
                    $emRec[(string) $a['rm']] = $a['nome'];
                }
            }
        }
        $limpas = [];
        foreach ($notas as $rm => $v) {
            $rm = (string) $rm;
            $v = trim((string) $v);
            if (!isset($emRec[$rm])) {
                if ($v !== '') {
                    return 'Há nota de recuperação para quem não está em recuperação nesta etapa.';
                }
                continue;
            }
            if ($v !== '' && Calc::centesimos($v) === null) {
                return "Nota de recuperação inválida para {$emRec[$rm]}: use um número de 0 a 10.";
            }
            $limpas[$rm] = $v === '' ? null : number_format(Calc::centesimos($v) / 100, 2, '.', '');
        }
        $pdo = AdmsEscolaBanco::pdo();
        foreach ($limpas as $rm => $v) {
            $st = $pdo->prepare('SELECT nota FROM esc_recuperacoes WHERE oferta_id = ? AND etapa_id = ? AND rm = ?');
            $st->execute([$ofertaId, $etapaId, $rm]);
            $antes = $st->fetchColumn();
            $antes = $antes === false ? null : (string) $antes;
            if ($antes === $v) {
                continue;
            }
            if ($v === null) {
                $pdo->prepare('DELETE FROM esc_recuperacoes WHERE oferta_id = ? AND etapa_id = ? AND rm = ?')->execute([$ofertaId, $etapaId, $rm]);
            } else {
                $pdo->prepare('INSERT INTO esc_recuperacoes (oferta_id, etapa_id, rm, nota, por, em) VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE nota = VALUES(nota), por = VALUES(por), em = NOW()')
                    ->execute([$ofertaId, $etapaId, $rm, $v, (int) ($_SESSION['colUsuario_id'] ?? 0)]);
            }
            \App\adms\Models\helper\AdmsAuditoria::recuperacao($ofertaId, $rm, $emRec[$rm], $antes, $v, $e['nome']);
        }
        return true;
    }

    /**
     * Lança as faltas da etapa; o total da oferta (rm_disciplina_faltas) é a soma das etapas.
     * @param array<string, string> $faltas RA → número
     * @return true|string
     */
    public static function faltas(int $ofertaId, int $etapaId, array $faltas)
    {
        $e = self::etapa($etapaId);
        if (!$e || !Calc::turmaDaOferta($ofertaId)) {
            return 'Etapa não encontrada.';
        }
        if ($r = self::trava($ofertaId, $etapaId)) {
            return $r;
        }
        if (\App\adms\Models\faltas\AdmsChamada::fonte($ofertaId) === 'chamada') { // Onda 6, Etapa 5: uma fonte só
            return 'As faltas desta disciplina vêm da chamada por aula: faça a chamada do dia (Chamada) e a falta da etapa se soma sozinha.';
        }
        $alunos = [];
        $l = new AdmsRead();
        $l->fullRead('SELECT rm, nome_aluno FROM portal_disciplinas_alunos WHERE rm_prof_mat_id = :o', "o={$ofertaId}");
        foreach ($l->getResultado() ?: [] as $a) {
            $alunos[(string) $a['rm']] = $a['nome_aluno'];
        }
        foreach ($faltas as $rm => $v) {
            if (!isset($alunos[(string) $rm]) || !preg_match('/^\d{0,3}$/', trim((string) $v))) {
                return 'Faltas inválidas: um número inteiro de 0 a 999 para cada aluno da turma.';
            }
        }
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($ofertaId, $etapaId, $faltas, $alunos, $e) {
            $pdo = AdmsEscolaBanco::pdo();
            foreach ($faltas as $rm => $v) {
                $rm = (string) $rm;
                $n = (int) trim((string) $v);
                $pdo->prepare('INSERT INTO esc_faltas_etapa (oferta_id, etapa_id, rm, faltas) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE faltas = VALUES(faltas)')
                    ->execute([$ofertaId, $etapaId, $rm, $n]);
                $st = $pdo->prepare('SELECT SUM(faltas) FROM esc_faltas_etapa WHERE oferta_id = ? AND rm = ?');
                $st->execute([$ofertaId, $rm]);
                $total = (int) $st->fetchColumn();
                $st = $pdo->prepare('SELECT faltas FROM rm_disciplina_faltas WHERE rm_prof_mat_id = ? AND rm = ?');
                $st->execute([$ofertaId, $rm]);
                $antes = $st->fetchColumn();
                if ($antes === false) {
                    $pdo->prepare('INSERT INTO rm_disciplina_faltas (rm, rm_prof_mat_id, faltas, faltas_compensadas, usuario_cre) VALUES (?, ?, ?, 0, ?)')
                        ->execute([$rm, $ofertaId, $total, (int) ($_SESSION['colUsuario_id'] ?? 0)]);
                } elseif ((int) $antes !== $total) {
                    $pdo->prepare('UPDATE rm_disciplina_faltas SET faltas = ? WHERE rm_prof_mat_id = ? AND rm = ?')->execute([$total, $ofertaId, $rm]);
                }
                if ($antes === false ? $total !== 0 : (int) $antes !== $total) {
                    \App\adms\Models\helper\AdmsAuditoria::falta($ofertaId, $rm, $alunos[$rm], $antes === false ? null : (int) $antes, $total, 'faltas', 'etapa: ' . $e['nome'] . " ({$n} na etapa)");
                }
            }
            return true;
        });
        return $ok ? true : 'As faltas não foram gravadas (' . $T::ultimoErro() . ').';
    }
}
