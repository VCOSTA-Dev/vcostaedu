<?php

namespace App\adms\Models\historicoEscola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\historicoEscola\AdmsHistBase as B;
use App\adms\Models\historicoEscola\AdmsHistCadastros as C;
use App\adms\Models\notas\AdmsCalculoNotas as Calc;

/**
 * A VIDA ESCOLAR (Parte C; D-112): uma linha por série cursada. O sistema PROPÕE as séries a partir das turmas do aluno
 * (uma por ano: a turma em que ele está, ou a última); a secretaria CONFIRMA, EDITA, DIGITA um ano cursado em outra
 * escola, ou TIRA. A vida = as séries gravadas + as propostas ainda não gravadas (a gravada vence), em ordem
 * cronológica; a mesma série num ano posterior é a retenção.
 *
 * A média de cada disciplina cursada aqui é LIDA na hora, pela função única da plataforma (AdmsCalculoNotas, a mesma
 * do boletim; a decisão 1): nunca é copiada. A secretaria pode SOBREPOR a média (fica registrado: quem, quando, a do
 * sistema e a nova). O ano fechado: enquanto o marcador do ano letivo não existe, um ano é fechado quando todas as
 * disciplinas da turma estão encerradas (aí valem a média final e a situação final); senão, "em curso".
 */
class AdmsHistVida
{
    public const RESULTADOS = ['aprovado' => 'Aprovado', 'retido' => 'Retido', 'transferido' => 'Transferido', 'em_curso' => 'Em curso', '' => 'A confirmar'];

    /** "7.50" → "7,5"; "7.25" → "7,25"; "7.00" → "7,0"; null → null. */
    public static function nota(?string $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (!is_numeric($v)) {
            return $v;
        }
        $t = rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        if (!str_contains($t, '.')) {
            $t .= '.0';
        }
        return str_replace('.', ',', $t);
    }

    /** Uma média digitada válida: de 0 a 10 com até duas casas, ou F, NF, "-". @return string|null a forma gravada */
    public static function mediaValida(string $v): ?string
    {
        $v = strtoupper(trim($v));
        if (in_array($v, ['F', 'NF', '-'], true)) {
            return $v;
        }
        $v = str_replace(',', '.', $v);
        if (!preg_match('/^\d{1,2}(\.\d{1,2})?$/', $v) || (float) $v > 10) {
            return null;
        }
        return self::nota($v);
    }

    /* ---------------------------------------------------------------- o aluno e a busca */

    /**
     * A busca: os alunos ativos e os ex-alunos, por nome ou RM, com a situação real ("ativo em AAAA", "saiu em
     * dd/mm/aaaa", "ex-aluno (último ano AAAA)", "sem turma").
     */
    public static function buscar(string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return [];
        }
        $r = B::todos("SELECT u.id, u.rm, u.nome FROM col_usuarios u WHERE u.col_niveis_acesso_id = 1 AND (u.rm = ? OR u.nome LIKE ?) ORDER BY u.nome LIMIT 100",
            [$q, '%' . addcslashes($q, '%_\\') . '%']);
        $provas = B::provas('col_usuarios');
        foreach ($r as &$a) {
            $a['situacao'] = self::situacaoReal((int) $a['id']);
            $a['prova'] = isset($provas[(int) $a['id']]);
        }
        return $r;
    }

    public static function situacaoReal(int $alunoId): string
    {
        $t = B::um('SELECT t.ano, x.saida, x.destino_turma_id FROM esc_turma_alunos x INNER JOIN esc_turmas t ON t.id = x.turma_id
            WHERE x.usuario_id = ? ORDER BY t.ano DESC, (x.saida IS NULL) DESC, x.id DESC LIMIT 1', [$alunoId]);
        if (!$t) {
            return 'sem turma';
        }
        $anoAtual = (int) date('Y');
        if ($t['saida'] === null) {
            return (int) $t['ano'] >= $anoAtual ? 'ativo em ' . (int) $t['ano'] : 'ex-aluno (último ano ' . (int) $t['ano'] . ')';
        }
        if (!$t['destino_turma_id']) {
            return 'saiu em ' . B::br(substr((string) $t['saida'], 0, 10));
        }
        return 'ativo em ' . (int) $t['ano'];
    }

    /* ---------------------------------------------------------------- a proposta e a vida */

    /** As turmas do aluno, uma por ano (a em que está; senão, a última), com o nível e o número da série. */
    public static function propostas(int $alunoId): array
    {
        $rm = (string) B::valor('SELECT rm FROM col_usuarios WHERE id = ?', [$alunoId]);
        $porAno = [];
        foreach (B::todos('SELECT t.id AS turma_id, t.ano, t.modulo_id, t.grade_id, t.curso_id, t.nome AS turma, m.codnome, x.entrada, x.saida, x.destino_turma_id
                FROM esc_turma_alunos x INNER JOIN esc_turmas t ON t.id = x.turma_id INNER JOIN rm_cursos_turma_series m ON m.id = t.modulo_id
                WHERE x.usuario_id = ? ORDER BY t.ano, (x.saida IS NULL), x.id', [$alunoId]) as $t) {
            $porAno[(int) $t['ano']] = $t; // a última da ordem vence: a sem saída, a mais nova
        }
        $esta = C::estaEscola();
        $r = [];
        foreach ($porAno as $ano => $t) {
            $s = C::serie((int) $t['modulo_id']);
            if (!$s) {
                continue;
            }
            [$resultado, $motivo, $conselho] = self::resultadoProposto((int) $t['turma_id'], $rm, $t);
            $r[] = ['id' => 0, 'aluno_id' => $alunoId, 'nivel' => $s['nivel'], 'serie' => $s['numero'], 'ano' => $ano, 'escola_id' => (int) ($esta['id'] ?? 0),
                'resultado' => $resultado, 'motivo' => $motivo, 'conselho' => $conselho ? 1 : 0, 'turma_id' => (int) $t['turma_id'], 'origem' => 'proposta',
                'religioso' => '', 'obs_chaves' => '', 'carga_total' => 0, 'itinerario' => 0, 'itinerario_confirmado' => 0,
                'data_saida' => $t['saida'] !== null && !$t['destino_turma_id'] ? substr((string) $t['saida'], 0, 10) : null, // D-117
                'turma' => $t['codnome'] . ' ' . $t['turma'], 'grade_id' => (int) $t['grade_id'], 'modulo_id' => (int) $t['modulo_id']];
        }
        return $r;
    }

    /**
     * O ano fechado: o marcador do ano letivo (D-117: fechado pela secretaria ou pela direção; reaberto, com o motivo);
     * sem o marcador, o critério de antes: todas as disciplinas da turma encerradas.
     */
    public static function anoFechado(int $turmaId): bool
    {
        $m = AdmsHistAnoLetivo::marcador((int) B::valor('SELECT ano FROM esc_turmas WHERE id = ?', [$turmaId]));
        if ($m !== null) {
            return $m === 'fechado';
        }
        $l = B::um('SELECT COUNT(*) AS n, SUM(o.encerrada_em IS NOT NULL) AS e FROM esc_turma_ofertas x INNER JOIN rm_professores_materias o ON o.id = x.oferta_id WHERE x.turma_id = ?', [$turmaId]);
        return $l && (int) $l['n'] > 0 && (int) $l['n'] === (int) $l['e'];
    }

    /** @return array{0: string, 1: string, 2: bool} o resultado, o motivo do "a confirmar" e o conselho */
    public static function resultadoProposto(int $turmaId, string $rm, array $t): array
    {
        if ($t['saida'] !== null && !$t['destino_turma_id']) {
            /* D-117: saiu da turma antes do fim do ano letivo, sem ir para outra turma: transferido, na data digitada da
               saída (o ano parcial vai para o bloco de transferência). Saiu depois do fim do ano: a confirmar. */
            $saiu = substr((string) $t['saida'], 0, 10);
            $fim = (string) B::valor('SELECT MAX(fim) FROM esc_etapas WHERE ano = ?', [(int) $t['ano']]);
            if ($fim === '' || $saiu <= $fim) {
                return ['transferido', 'saiu da turma em ' . B::br($saiu) . ', antes do fim do ano letivo', false];
            }
            return ['', 'saiu da turma em ' . B::br($saiu) . ', depois do fim do ano letivo: confirme o resultado', false];
        }
        if (!self::anoFechado($turmaId)) {
            return ['em_curso', '', false];
        }
        $sits = [];
        foreach (self::ofertasDaTurma($turmaId) as $o) {
            $c = Calc::alunoNaOferta((int) $o['id'], $rm);
            $sits[] = (string) ($c['situacao'] ?? '');
        }
        if (!$sits || in_array('', $sits, true) || in_array('pendente', $sits, true) || in_array('em_curso', $sits, true)) {
            return ['', 'há disciplina sem a situação final', false];
        }
        if (array_filter($sits, fn($s) => str_starts_with($s, 'reprovado'))) {
            return ['retido', '', false];
        }
        return ['aprovado', '', in_array('aprovado_conselho', $sits, true)];
    }

    public static function ofertasDaTurma(int $turmaId): array
    {
        return B::todos('SELECT o.id, o.materia, o.matriz_id, l.materia_id, o.encerrada_em FROM esc_turma_ofertas x INNER JOIN rm_professores_materias o ON o.id = x.oferta_id
            LEFT JOIN uni_rh_cursos_disciplinas_grade l ON l.id = o.matriz_id WHERE x.turma_id = ? ORDER BY l.ordem, o.materia', [$turmaId]);
    }

    /**
     * A vida do aluno num nível: as gravadas + as propostas sem gravação para (ano, série), em ordem cronológica, com a
     * marca da retenção (a mesma série de novo num ano posterior).
     */
    public static function vida(int $alunoId, string $nivel): array
    {
        $gravadas = B::todos('SELECT v.*, e.nome AS escola, e.municipio, e.uf FROM esc_hist_vida_serie v LEFT JOIN esc_hist_escola e ON e.id = v.escola_id WHERE v.aluno_id = ? AND v.nivel = ?', [$alunoId, $nivel]);
        $chaves = [];
        foreach ($gravadas as &$g) {
            $g['motivo'] = '';
            $chaves[$g['ano'] . ':' . $g['serie']] = true;
            if ((int) $g['turma_id']) {
                $t = B::um('SELECT t.grade_id, t.modulo_id, m.codnome, t.nome FROM esc_turmas t INNER JOIN rm_cursos_turma_series m ON m.id = t.modulo_id WHERE t.id = ?', [(int) $g['turma_id']]);
                $g += ['turma' => $t ? $t['codnome'] . ' ' . $t['nome'] : '', 'grade_id' => (int) ($t['grade_id'] ?? 0), 'modulo_id' => (int) ($t['modulo_id'] ?? 0)];
            } else {
                $g += ['turma' => '', 'grade_id' => 0, 'modulo_id' => 0];
            }
        }
        unset($g);
        $escolas = array_column(C::escolas(), null, 'id');
        foreach (self::propostas($alunoId) as $p) {
            if ($p['nivel'] !== $nivel || isset($chaves[$p['ano'] . ':' . $p['serie']])) {
                continue;
            }
            $e = $escolas[$p['escola_id']] ?? null;
            $gravadas[] = $p + ['escola' => $e['nome'] ?? null, 'municipio' => $e['municipio'] ?? null, 'uf' => $e['uf'] ?? null];
        }
        /* D-116: o itinerário formativo da série cursada aqui é o da matrícula do ano (sem proposta a confirmar) */
        if ($nivel === 'EM') {
            foreach ($gravadas as &$g) {
                $im = (int) $g['turma_id'] ? self::itinerarioDaMatricula($alunoId, (int) $g['turma_id']) : 0;
                if ($im > 0) {
                    $g['itinerario'] = $im;
                    $g['itinerario_confirmado'] = 1;
                    $g['itinerario_fonte'] = 'matricula';
                }
            }
            unset($g);
        }
        usort($gravadas, fn($a, $b) => [(int) $a['ano'], (int) $a['serie']] <=> [(int) $b['ano'], (int) $b['serie']]);
        $vistas = [];
        foreach ($gravadas as &$g) {
            $g['retencao'] = isset($vistas[(int) $g['serie']]);
            if ($g['retencao']) {
                $vistas[(int) $g['serie']]['retido_antes'] = true;
            }
            $vistas[(int) $g['serie']] = &$g;
        }
        unset($g, $vistas);
        return $gravadas;
    }

    /** D-116: o itinerário formativo do aluno na matrícula do ano (a linha dele na turma; a de agora, senão a última). */
    public static function itinerarioDaMatricula(int $alunoId, int $turmaId): int
    {
        return (int) B::valor('SELECT i.itinerario FROM esc_turma_alunos x INNER JOIN esc_hist_itinerario_aluno i ON i.turma_aluno_id = x.id
            WHERE x.usuario_id = ? AND x.turma_id = ? ORDER BY (x.saida IS NULL) DESC, x.id DESC LIMIT 1', [$alunoId, $turmaId]);
    }

    /**
     * D-116: os itinerários formativos da turma do Médio no ano (os da matriz do histórico do ano, numero > 0) e o de cada
     * aluno na matrícula. Vazio quando a turma não é do Médio ou o ano não tem itinerário formativo.
     * @return array{itinerarios: array<int, string>, alunos: array<int, int>}
     */
    public static function itinerariosDaTurma(int $turmaId): array
    {
        $t = B::um("SELECT t.id, t.ano, t.grade_id FROM esc_turmas t INNER JOIN esc_hist_segmento sg ON sg.curso_id = t.curso_id AND sg.nivel = 'EM' WHERE t.id = ?", [$turmaId]);
        if (!$t) {
            return ['itinerarios' => [], 'alunos' => []];
        }
        $mz = AdmsHistMatriz::doAno((int) $t['grade_id'], (int) $t['ano']);
        $its = [];
        foreach ((array) ($mz['matriz']['itinerarios'] ?? []) as $i) {
            if ((int) $i['numero'] > 0) {
                $its[(int) $i['numero']] = (string) $i['nome'];
            }
        }
        if (!$its) {
            return ['itinerarios' => [], 'alunos' => []];
        }
        $alunos = array_map('intval', array_column(B::todos('SELECT x.id, i.itinerario FROM esc_turma_alunos x INNER JOIN esc_hist_itinerario_aluno i ON i.turma_aluno_id = x.id WHERE x.turma_id = ? AND x.saida IS NULL', [$turmaId]), 'itinerario', 'id'));
        ksort($its);
        return ['itinerarios' => $its, 'alunos' => $alunos];
    }

    /** D-116: grava o itinerário de cada aluno da turma (`$post['itinerario'][turma_aluno_id]`), com o registro. @return int|string */
    public static function salvarItinerarios(int $turmaId, array $post)
    {
        $x = self::itinerariosDaTurma($turmaId);
        if (!$x['itinerarios']) {
            return 'A turma não é do Médio ou o ano não tem itinerário formativo na matriz do histórico.';
        }
        $mud = [];
        foreach (B::todos('SELECT x.id, x.usuario_id, u.nome FROM esc_turma_alunos x INNER JOIN col_usuarios u ON u.id = x.usuario_id WHERE x.turma_id = ? AND x.saida IS NULL', [$turmaId]) as $l) {
            if (!array_key_exists((string) $l['id'], (array) ($post['itinerario'] ?? []))) {
                continue;
            }
            $novo = (int) $post['itinerario'][(string) $l['id']];
            if ($novo !== 0 && !isset($x['itinerarios'][$novo])) {
                return 'Itinerário inválido para ' . $l['nome'] . '.';
            }
            $antes = (int) ($x['alunos'][(int) $l['id']] ?? 0);
            if ($novo !== $antes) {
                $mud[] = [$l, $antes, $novo];
            }
        }
        if (!$mud) {
            return 0;
        }
        $its = $x['itinerarios'];
        $ok = \Core\Transacao::executar(function () use ($mud, $its) {
            foreach ($mud as [$l, $antes, $novo]) {
                if ($novo === 0) {
                    B::exec('DELETE FROM esc_hist_itinerario_aluno WHERE turma_aluno_id = ?', [(int) $l['id']]);
                } else {
                    B::exec('REPLACE INTO esc_hist_itinerario_aluno (turma_aluno_id, itinerario, por, em) VALUES (?, ?, ?, NOW())', [(int) $l['id'], $novo, B::usuario()]);
                }
                $rot = fn(int $n) => $n ? $n . ' — ' . ($its[$n] ?? '') : 'nenhum';
                B::registrar('esc_hist_itinerario_aluno', (int) $l['id'], (int) $l['usuario_id'], 'Itinerário formativo na matrícula do ano', ['itinerario' => $rot($antes)], ['itinerario' => $rot($novo)], ['itinerario' => 'Itinerário']);
            }
            return true;
        });
        return $ok ? count($mud) : 'Não foi gravado (' . \Core\Transacao::ultimoErro() . ').';
    }

    /* ---------------------------------------------------------------- gravar */

    public const ROTULOS = ['serie' => 'Série', 'ano' => 'Ano', 'escola' => 'Escola', 'resultado' => 'Resultado', 'religioso' => 'Ensino Religioso', 'conselho' => 'Conselho de classe',
        'obs_chaves' => 'Observações', 'carga_total' => 'Carga total digitada', 'itinerario' => 'Itinerário', 'data_saida' => 'Data da saída'];

    private static function legivel(array $s, string $nivel): array
    {
        $esc = $s['escola_id'] ? (string) B::valor('SELECT nome FROM esc_hist_escola WHERE id = ?', [(int) $s['escola_id']]) : '';
        return ['serie' => B::nomeSerie($nivel, (int) $s['serie']), 'ano' => (string) $s['ano'], 'escola' => $esc, 'resultado' => self::RESULTADOS[(string) $s['resultado']] ?? '',
            'religioso' => (string) $s['religioso'], 'conselho' => $s['conselho'] ? 'sim' : 'não', 'obs_chaves' => (string) $s['obs_chaves'],
            'carga_total' => $s['carga_total'] ? (string) $s['carga_total'] : '', 'itinerario' => $s['itinerario'] ? (string) $s['itinerario'] : '']
            + (!empty($s['data_saida']) ? ['data_saida' => B::br((string) $s['data_saida'])] : []); // D-117: só quando há (o registro de antes não muda)
    }

    /** Confirma a proposta da turma (grava com origem "sistema"). @return int|string */
    public static function confirmar(int $alunoId, int $turmaId)
    {
        $p = null;
        foreach (self::propostas($alunoId) as $x) {
            if ((int) $x['turma_id'] === $turmaId) {
                $p = $x;
            }
        }
        if (!$p) {
            return 'Não há proposta desta turma para o aluno.';
        }
        if (B::valor('SELECT id FROM esc_hist_vida_serie WHERE aluno_id = ? AND nivel = ? AND ano = ? AND serie = ?', [$alunoId, $p['nivel'], $p['ano'], $p['serie']])) {
            return 'Esta série já está gravada na vida escolar.';
        }
        return \Core\Transacao::executar(function () use ($alunoId, $p) {
            B::exec("INSERT INTO esc_hist_vida_serie (aluno_id, nivel, serie, ano, escola_id, resultado, turma_id, origem, conselho, data_saida, por, em) VALUES (?, ?, ?, ?, ?, ?, ?, 'sistema', ?, ?, ?, NOW())",
                [$alunoId, $p['nivel'], $p['serie'], $p['ano'], $p['escola_id'], $p['resultado'], $p['turma_id'], $p['conselho'], $p['resultado'] === 'transferido' ? $p['data_saida'] : null, B::usuario()]);
            $id = (int) B::pdo()->lastInsertId();
            B::registrar('esc_hist_vida_serie', $id, $alunoId, 'Vida escolar: ' . B::nomeSerie($p['nivel'], $p['serie']) . ' de ' . $p['ano'] . ' confirmada', null, self::legivel($p, $p['nivel']), self::ROTULOS);
            return $id;
        }) ?: 'A série não foi gravada (' . \Core\Transacao::ultimoErro() . ').';
    }

    /**
     * Edita uma série gravada (`$id`), digita uma nova (`$id` = 0, turma 0), ou grava a proposta já editada
     * (`$turmaId`). @return int|string o id, ou o motivo
     */
    public static function salvarSerie(int $id, int $alunoId, array $post)
    {
        $antes = $id ? B::um('SELECT * FROM esc_hist_vida_serie WHERE id = ? AND aluno_id = ?', [$id, $alunoId]) : null;
        if ($id && !$antes) {
            return 'A série não foi encontrada.';
        }
        $nivel = $antes['nivel'] ?? (string) ($post['nivel'] ?? '');
        if (!B::nivelValido($nivel)) {
            return 'Escolha o nível.';
        }
        $turma = (int) ($antes['turma_id'] ?? ($post['turma_id'] ?? 0));
        $proposta = null;
        if (!$antes && $turma) {
            foreach (self::propostas($alunoId) as $x) {
                if ((int) $x['turma_id'] === $turma) {
                    $proposta = $x;
                }
            }
            if (!$proposta) {
                return 'Não há proposta desta turma para o aluno.';
            }
        }
        $obs = array_values(array_filter(array_map('strval', (array) ($post['obs'] ?? [])), fn($k) => preg_match('/^[a-z0-9_]{2,40}$/', $k)));
        $d = [
            'serie' => $turma ? (int) ($antes['serie'] ?? $proposta['serie']) : (int) ($post['serie'] ?? 0),
            'ano' => $turma ? (int) ($antes['ano'] ?? $proposta['ano']) : (int) ($post['ano'] ?? 0),
            'escola_id' => (int) ($post['escola_id'] ?? 0),
            'resultado' => (string) ($post['resultado'] ?? ''),
            'religioso' => trim((string) ($post['religioso'] ?? '')),
            'conselho' => !empty($post['conselho']) ? 1 : 0,
            'obs_chaves' => implode(',', $obs),
            'carga_total' => (int) ($post['carga_total'] ?? 0),
            'itinerario' => (int) ($post['itinerario'] ?? 0),
            'data_saida' => trim((string) ($post['data_saida'] ?? '')),
        ];
        /* D-117: a data da saída (a da transferência): só no transferido; vazia, a da saída da turma (na proposta) */
        if ($d['resultado'] !== 'transferido') {
            $d['data_saida'] = null;
        } elseif ($d['data_saida'] === '') {
            $d['data_saida'] = $antes['data_saida'] ?? ($proposta['data_saida'] ?? null);
        } else {
            $ds = \DateTime::createFromFormat('!Y-m-d', $d['data_saida']);
            if (!$ds || $ds->format('Y-m-d') !== $d['data_saida'] || (int) substr($d['data_saida'], 0, 4) !== $d['ano']) {
                return 'A data da saída é um dia do ano letivo da série.';
            }
        }
        if ($d['serie'] < 1 || $d['serie'] > B::SERIES[$nivel]) {
            return 'A série vai de 1 a ' . B::SERIES[$nivel] . ' (' . B::NIVEIS[$nivel] . ').';
        }
        if ($d['ano'] < 1950 || $d['ano'] > (int) date('Y') + 1) {
            return 'Informe o ano letivo.';
        }
        if (!array_key_exists($d['resultado'], self::RESULTADOS)) {
            return 'Resultado inválido.';
        }
        if ($d['religioso'] !== '' && self::mediaValida($d['religioso']) === null) {
            return 'O Ensino Religioso aceita uma nota de 0 a 10, F, NF ou "-".';
        }
        $d['religioso'] = $d['religioso'] === '' ? '' : (string) self::mediaValida($d['religioso']);
        if (!$turma && !$d['escola_id']) {
            return 'A série cursada em outra escola precisa da escola (o nome, o município e a UF vêm do cadastro de escolas).';
        }
        if ($d['escola_id'] && !B::valor('SELECT id FROM esc_hist_escola WHERE id = ?', [$d['escola_id']])) {
            return 'A escola não está no cadastro.';
        }
        if ($d['carga_total'] < 0 || $d['carga_total'] > 5000) {
            return 'A carga total vai de 0 a 5.000.';
        }
        if (B::valor('SELECT id FROM esc_hist_vida_serie WHERE aluno_id = ? AND nivel = ? AND ano = ? AND serie = ? AND id <> ?', [$alunoId, $nivel, $d['ano'], $d['serie'], $id])) {
            return 'Esta série, neste ano, já está na vida escolar.';
        }
        $titulo = 'Vida escolar: ' . B::nomeSerie($nivel, $d['serie']) . ' de ' . $d['ano'];
        return \Core\Transacao::executar(function () use ($id, $alunoId, $nivel, $turma, $d, $antes, $proposta, $titulo) {
            if ($antes) {
                B::exec('UPDATE esc_hist_vida_serie SET serie = ?, ano = ?, escola_id = ?, resultado = ?, religioso = ?, conselho = ?, obs_chaves = ?, carga_total = ?, itinerario = ?, itinerario_confirmado = ?, data_saida = ? WHERE id = ?',
                    [$d['serie'], $d['ano'], $d['escola_id'], $d['resultado'], $d['religioso'], $d['conselho'], $d['obs_chaves'], $d['carga_total'], $d['itinerario'], $d['itinerario'] ? 1 : 0, $d['data_saida'], $id]);
                B::registrar('esc_hist_vida_serie', $id, $alunoId, $titulo, self::legivel($antes, $nivel), self::legivel($d, $nivel), self::ROTULOS);
                return $id;
            }
            B::exec('INSERT INTO esc_hist_vida_serie (aluno_id, nivel, serie, ano, escola_id, resultado, turma_id, origem, religioso, conselho, obs_chaves, carga_total, itinerario, itinerario_confirmado, data_saida, por, em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [$alunoId, $nivel, $d['serie'], $d['ano'], $d['escola_id'], $d['resultado'], $turma, $turma ? 'sistema' : 'digitado', $d['religioso'], $d['conselho'], $d['obs_chaves'], $d['carga_total'], $d['itinerario'], $d['itinerario'] ? 1 : 0, $d['data_saida'], B::usuario()]);
            $novo = (int) B::pdo()->lastInsertId();
            B::registrar('esc_hist_vida_serie', $novo, $alunoId, $titulo . ($turma ? ' confirmada' : ' digitada'), $proposta ? self::legivel($proposta, $nivel) : null, self::legivel($d, $nivel), self::ROTULOS);
            return $novo;
        }) ?: 'A série não foi gravada (' . \Core\Transacao::ultimoErro() . ').';
    }

    /** Tira a série: a confirmada volta a ser proposta; a digitada sai com as notas dela. @return true|string */
    public static function tirar(int $id, int $alunoId)
    {
        $s = B::um('SELECT * FROM esc_hist_vida_serie WHERE id = ? AND aluno_id = ?', [$id, $alunoId]);
        if (!$s) {
            return 'A série não foi encontrada.';
        }
        return \Core\Transacao::executar(function () use ($s, $id, $alunoId) {
            $notas = B::todos('SELECT n.*, d.nome FROM esc_hist_vida_nota n LEFT JOIN esc_hist_disciplina d ON d.id = n.disciplina_id WHERE n.vida_serie_id = ?', [$id]);
            B::exec('DELETE FROM esc_hist_vida_nota WHERE vida_serie_id = ?', [$id]);
            B::exec('DELETE FROM esc_hist_vida_serie WHERE id = ?', [$id]);
            $leg = self::legivel($s, $s['nivel']);
            $leg['notas'] = implode('; ', array_map(fn($n) => $n['nome'] . ' ' . $n['media'], $notas));
            B::registrar('esc_hist_vida_serie', $id, $alunoId, 'Vida escolar: ' . B::nomeSerie($s['nivel'], (int) $s['serie']) . ' de ' . $s['ano'] . ' tirada' . ($s['origem'] === 'sistema' ? ' (volta a ser proposta)' : ''),
                $leg, null, self::ROTULOS + ['notas' => 'Notas']);
            return true;
        }) ? true : 'A série não foi tirada.';
    }

    /* ---------------------------------------------------------------- as notas da série */

    /** Uma série da vida pelo id gravado, ou a proposta da turma. */
    public static function serie(int $alunoId, int $id, int $turmaId = 0): ?array
    {
        foreach (B::NIVEIS as $nivel => $x) {
            foreach (self::vida($alunoId, $nivel) as $s) {
                if (($id && (int) $s['id'] === $id) || (!$id && $turmaId && !$s['id'] && (int) $s['turma_id'] === $turmaId)) {
                    return $s;
                }
            }
        }
        return null;
    }

    /**
     * As disciplinas de uma série, com a média e a carga:
     *  - cursada aqui: as linhas da matriz do ano (aulas semanais > 0), com a média do sistema (a função única) e a
     *    sobreposta; as disciplinas com nota no sistema e fora da matriz vão para os avisos;
     *  - digitada: as médias digitadas, com a carga digitada ou a da matriz da escola naquele ano.
     * @return array{linhas: list<array>, avisos: list<string>, matriz: ?array, calc: array}
     */
    public static function disciplinas(array $s): array
    {
        $rm = (string) B::valor('SELECT rm FROM col_usuarios WHERE id = ?', [(int) $s['aluno_id']]);
        $gravadas = [];
        if ((int) $s['id']) {
            foreach (B::todos('SELECT * FROM esc_hist_vida_nota WHERE vida_serie_id = ?', [(int) $s['id']]) as $n) {
                $gravadas[(int) $n['disciplina_id']] = $n;
            }
        }
        $disc = [];
        foreach (B::todos('SELECT * FROM esc_hist_disciplina') as $d) {
            $disc[(int) $d['id']] = $d;
        }
        $discDaMateria = [];
        foreach ($disc as $d) {
            if ((int) $d['materia_id'] && !isset($discDaMateria[(int) $d['materia_id']])) {
                $discDaMateria[(int) $d['materia_id']] = $d;
            }
        }
        $avisos = [];
        $linhas = [];
        $calcs = [];
        $gradeId = (int) ($s['grade_id'] ?? 0);
        $moduloId = (int) ($s['modulo_id'] ?? 0);
        if (!$gradeId) { // série digitada: a grade de um segmento do nível com a série deste número
            $g = B::um('SELECT m.grade_id, m.id FROM rm_cursos_turma_series m INNER JOIN esc_hist_segmento sg ON sg.curso_id = m.curso_id AND sg.nivel = ? INNER JOIN esc_hist_serie n ON n.modulo_id = m.id AND n.numero = ? ORDER BY m.id LIMIT 1', [$s['nivel'], (int) $s['serie']]);
            $gradeId = (int) ($g['grade_id'] ?? 0);
            $moduloId = (int) ($g['id'] ?? 0);
        }
        $mz = $gradeId ? AdmsHistMatriz::doAno($gradeId, (int) $s['ano']) : ['matriz' => null, 'aviso' => 'não há matriz do histórico para esta série'];
        $m = $mz['matriz'];
        if ($mz['aviso'] !== '') {
            $avisos[] = $mz['aviso'];
        }
        $itens = [];
        if ($m) {
            foreach ($m['itens'] as $i) {
                if ((int) $i['modulo_id'] === $moduloId && (int) $i['aulas_semanais'] > 0) {
                    $itens[(int) $i['materia_id']] = $i;
                }
            }
        }
        $semanas = (int) ($m['semanas'] ?? 40);
        $mkLinha = function (array $d, ?array $i) use ($semanas) {
            return ['disciplina_id' => (int) ($d['id'] ?? 0), 'materia_id' => (int) ($d['materia_id'] ?? ($i['materia_id'] ?? 0)), 'nome' => (string) ($d['nome'] ?? ($i['materia'] ?? '')),
                'religioso' => (int) ($d['religioso'] ?? 0) === 1, 'base' => (string) ($d['base'] ?? 'comum'), 'ordem_ef' => (int) ($d['ordem_ef'] ?? 0), 'ordem_em' => (int) ($d['ordem_em'] ?? 0),
                'grupo' => (string) ($i['grupo'] ?? ''), 'area' => (string) ($i['area'] ?? ''), 'area_ordem' => (int) ($i['area_ordem'] ?? 0), 'itinerario' => (int) ($i['itinerario'] ?? 0),
                'ordem_matriz' => $i ? ((int) $i['ordem'] ?: 1000 + (int) $i['ordem_grade']) : 5000, 'aulas_semanais' => (int) ($i['aulas_semanais'] ?? 0),
                'aulas_anuais' => $i ? (int) $i['aulas_semanais'] * $semanas : 0, 'media_sistema' => null, 'media' => null, 'origem_media' => '', 'oferta_id' => 0, 'calc' => null];
        };
        if ((int) $s['turma_id']) {
            foreach (self::ofertasDaTurma((int) $s['turma_id']) as $o) {
                $mat = (int) $o['materia_id'];
                $c = Calc::alunoNaOferta((int) $o['id'], $rm);
                $calcs[$mat] = $c;
                $d = $discDaMateria[$mat] ?? ['id' => 0, 'materia_id' => $mat, 'nome' => (string) B::valor('SELECT nome FROM rm_materias WHERE id = ?', [$mat]) ?: $o['materia'], 'religioso' => preg_match('/religios/i', (string) $o['materia']) ? 1 : 0];
                $i = $itens[$mat] ?? null;
                $media = $c ? self::nota($c['media'] ?? null) : null;
                if (!$i && !(int) ($d['religioso'] ?? 0)) {
                    if ($media !== null) {
                        $avisos[] = $d['nome'] . ': tem nota no sistema e está fora da matriz do ano (não sai no histórico)';
                    }
                    continue;
                }
                $l = $mkLinha($d, $i);
                $l['oferta_id'] = (int) $o['id'];
                $l['calc'] = $c;
                $l['media_sistema'] = $media;
                $l['media'] = $media;
                $l['origem_media'] = 'sistema';
                $g = $l['disciplina_id'] ? ($gravadas[$l['disciplina_id']] ?? null) : null;
                if ($g) {
                    $l['media'] = $g['media'];
                    $l['origem_media'] = 'sobreposto';
                }
                if (!empty($c['pendencias']) && $s['resultado'] !== 'em_curso') {
                    $avisos[] = $d['nome'] . ': média pendente (' . implode('; ', (array) $c['pendencias']) . ')';
                }
                $linhas[] = $l;
            }
            foreach ($itens as $mat => $i) {
                if (!isset($calcs[$mat])) {
                    $d = $discDaMateria[$mat] ?? ['id' => 0, 'materia_id' => $mat, 'nome' => $i['materia']];
                    $l = $mkLinha($d, $i);
                    $g = $l['disciplina_id'] ? ($gravadas[$l['disciplina_id']] ?? null) : null;
                    if ($g) {
                        $l['media'] = $g['media'];
                        $l['origem_media'] = 'sobreposto';
                    }
                    $linhas[] = $l;
                }
            }
        } else {
            foreach ($gravadas as $did => $g) {
                $d = $disc[$did] ?? null;
                if (!$d) {
                    continue;
                }
                $i = (int) $d['materia_id'] ? ($itens[(int) $d['materia_id']] ?? null) : null;
                $l = $mkLinha($d, $i);
                if (!$i) {
                    $l['grupo'] = $d['base'] === 'diversificada' ? ($s['nivel'] === 'EM' ? 'IT_COMUM' : 'PD') : ($s['nivel'] === 'EM' ? 'FGB' : 'BNC');
                    $l['ordem_matriz'] = 3000 + $did;
                }
                $l['media'] = $g['media'];
                $l['origem_media'] = 'digitado';
                if ((int) $g['aulas_anuais'] > 0) {
                    $l['aulas_anuais'] = (int) $g['aulas_anuais'];
                }
                $linhas[] = $l;
            }
        }
        foreach ($linhas as $l) {
            if ($l['media'] === null && !in_array($s['resultado'], ['em_curso', 'transferido'], true) && !$l['religioso']) {
                $avisos[] = $l['nome'] . ': sem nota';
            }
        }
        return ['linhas' => $linhas, 'avisos' => array_values(array_unique($avisos)), 'matriz' => $m, 'calc' => $calcs];
    }

    /**
     * Grava as notas da série: na digitada, a média e a carga de cada disciplina (e a disciplina criada na hora); na
     * cursada aqui, a média sobreposta (vazia = volta a valer a do sistema). @return int|string quantas mudaram
     */
    public static function salvarNotas(int $alunoId, int $vidaId, array $post)
    {
        $s = self::serie($alunoId, $vidaId);
        if (!$s || !(int) $s['id']) {
            return 'Confirme a série antes de lançar ou sobrepor notas.';
        }
        $aqui = (int) $s['turma_id'] > 0;
        $q = self::disciplinas($s);
        $mud = [];
        $porDisc = [];
        foreach ($q['linhas'] as $l) {
            $porDisc[$l['disciplina_id'] ?: 'm' . $l['materia_id']] = $l;
        }
        $novasDisc = [];
        if (!$aqui && trim((string) ($post['nova_nome'] ?? '')) !== '') {
            $nome = trim((string) $post['nova_nome']);
            $media = self::mediaValida((string) ($post['nova_media'] ?? ''));
            if ($media === null || mb_strlen($nome) > 160) {
                return 'A disciplina nova: o nome (até 160) e a média (0 a 10, F, NF ou -).';
            }
            $novasDisc[] = ['nome' => $nome, 'base' => ($post['nova_base'] ?? '') === 'diversificada' ? 'diversificada' : 'comum', 'media' => $media, 'aulas' => max(0, (int) ($post['nova_aulas'] ?? 0)),
                'existente' => (int) ($post['nova_disciplina'] ?? 0)];
        } elseif (!$aqui && (int) ($post['nova_disciplina'] ?? 0)) {
            $media = self::mediaValida((string) ($post['nova_media'] ?? ''));
            if ($media === null) {
                return 'A média da disciplina incluída: de 0 a 10, F, NF ou -.';
            }
            $novasDisc[] = ['nome' => '', 'base' => 'comum', 'media' => $media, 'aulas' => max(0, (int) ($post['nova_aulas'] ?? 0)), 'existente' => (int) $post['nova_disciplina']];
        }
        foreach ((array) ($post['media'] ?? []) as $k => $v) {
            $l = $porDisc[$k] ?? null;
            if (!$l) {
                continue;
            }
            $v = trim((string) $v);
            $nova = $v === '' ? null : self::mediaValida($v);
            if ($v !== '' && $nova === null) {
                return 'Média inválida em ' . $l['nome'] . ': de 0 a 10 com até duas casas, F, NF ou "-".';
            }
            $aulas = $aqui ? 0 : max(0, (int) ($post['aulas'][$k] ?? 0));
            if ($aqui) {
                $atual = $l['origem_media'] === 'sobreposto' ? $l['media'] : null;
                if ($nova === $l['media_sistema']) {
                    $nova = null; // igual à do sistema: não é sobreposição
                }
                if ($nova !== $atual) {
                    $mud[] = [$l, $atual, $nova, 0];
                }
            } else {
                $g = B::um('SELECT * FROM esc_hist_vida_nota WHERE vida_serie_id = ? AND disciplina_id = ?', [(int) $s['id'], (int) $l['disciplina_id']]);
                if ($nova !== ($g['media'] ?? null) || ($g && $aulas !== (int) $g['aulas_anuais'])) {
                    $mud[] = [$l, $g['media'] ?? null, $nova, $aulas, (int) ($g['aulas_anuais'] ?? 0)];
                }
            }
        }
        if (!$mud && !$novasDisc) {
            return 0;
        }
        $titulo = 'Notas: ' . B::nomeSerie($s['nivel'], (int) $s['serie']) . ' de ' . $s['ano'];
        $ok = \Core\Transacao::executar(function () use ($s, $alunoId, $aqui, $mud, $novasDisc, $titulo) {
            foreach ($novasDisc as $n) {
                $did = $n['existente'];
                if (!$did) {
                    B::exec('INSERT INTO esc_hist_disciplina (materia_id, nome, base) VALUES (0, ?, ?)', [$n['nome'], $n['base']]);
                    $did = (int) B::pdo()->lastInsertId();
                    B::registrar('esc_hist_disciplina', $did, $alunoId, 'Disciplina criada na vida escolar', null, ['nome' => $n['nome'], 'base' => $n['base'] === 'comum' ? 'base comum' : 'parte diversificada'], ['nome' => 'Nome', 'base' => 'Base']);
                }
                $nome = (string) B::valor('SELECT nome FROM esc_hist_disciplina WHERE id = ?', [$did]);
                B::exec("REPLACE INTO esc_hist_vida_nota (vida_serie_id, disciplina_id, media, aulas_anuais, origem, por, em) VALUES (?, ?, ?, ?, 'digitado', ?, NOW())", [(int) $s['id'], $did, $n['media'], $n['aulas'], B::usuario()]);
                B::registrar('esc_hist_vida_nota', (int) $s['id'], $alunoId, $titulo . ' · ' . $nome, null, ['media' => $n['media'], 'aulas' => $n['aulas'] ? (string) $n['aulas'] : ''], ['media' => 'Média', 'aulas' => 'Aulas anuais']);
            }
            foreach ($mud as $x) {
                [$l, $antes, $nova, $aulas] = $x;
                $did = $l['disciplina_id'] ?: C::disciplinaDaMateria($l['materia_id']);
                if ($nova === null) {
                    B::exec('DELETE FROM esc_hist_vida_nota WHERE vida_serie_id = ? AND disciplina_id = ?', [(int) $s['id'], $did]);
                } else {
                    B::exec('REPLACE INTO esc_hist_vida_nota (vida_serie_id, disciplina_id, media, aulas_anuais, origem, media_sistema, por, em) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
                        [(int) $s['id'], $did, $nova, $aulas, $aqui ? 'sobreposto' : 'digitado', $aqui ? (string) $l['media_sistema'] : '', B::usuario()]);
                }
                if ($aqui) {
                    B::registrar('esc_hist_vida_nota', (int) $s['id'], $alunoId, $titulo . ' · ' . $l['nome'] . ' (média do sistema: ' . ($l['media_sistema'] ?? 'sem média') . ')',
                        ['media' => $antes ?? 'a do sistema'], ['media' => $nova ?? 'a do sistema'], ['media' => 'Média sobreposta']);
                } else {
                    B::registrar('esc_hist_vida_nota', (int) $s['id'], $alunoId, $titulo . ' · ' . $l['nome'], ['media' => (string) $antes, 'aulas' => (string) ($x[4] ?: '')],
                        ['media' => (string) $nova, 'aulas' => $aulas ? (string) $aulas : ''], ['media' => 'Média', 'aulas' => 'Aulas anuais']);
                }
            }
            return true;
        });
        return $ok ? count($mud) + count($novasDisc) : 'As notas não foram gravadas (' . \Core\Transacao::ultimoErro() . ').';
    }
}
