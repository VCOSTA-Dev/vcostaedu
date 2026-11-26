<?php

namespace App\adms\Models\notas;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\notas\AdmsCalculoNotas as Calc;

/**
 * BOLETIM POR CURSO (E4): um boletim por MATRÍCULA NO CURSO (rm_matriculas_curso).
 * Lista TODAS as ofertas em que o aluno está enturmado naquele curso e grade,
 * agrupadas por MÓDULO, com disciplina, CH, média (parcial ou final), frequência e
 * resultado — sempre pelo motor único (AdmsCalculoNotas). Usado pelo boletim do
 * painel, pelo PDF e pelo portal: o mesmo número nos três. Arquivo IDÊNTICO no
 * painel e no portal.
 *
 * Enturmações que não caem em nenhuma matrícula (curso sem vínculo) vão num bloco
 * "Sem matrícula no curso", para nada sumir.
 */
class AdmsBoletimCurso
{
    public static function doAluno(string $rm): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, rm, nome, nome_social FROM col_usuarios WHERE rm = :rm AND col_niveis_acesso_id = :n LIMIT :limit", "rm={$rm}&n=1&limit=1");
        $aluno = $ler->getResultado()[0] ?? null;
        if (!$aluno) {
            return [];
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT mc.id, mc.curso_id, mc.grade_id, mc.data_ingresso, c.nome AS curso, g.versao,
                    s.nome AS situacao, t.nome AS formaIngresso
               FROM rm_matriculas_curso mc
               INNER JOIN rm_cursos c ON c.id = mc.curso_id
               INNER JOIN rm_cursos_grades g ON g.id = mc.grade_id
               LEFT JOIN rm_matriculas_situacao s ON s.id = mc.situacao_id
               LEFT JOIN rm_matricula_tipos t ON t.id = mc.forma_ingresso_id
              WHERE mc.usuario_id = :u ORDER BY mc.data_ingresso, mc.id",
            "u={$aluno['id']}"
        );
        $matriculas = $ler->getResultado() ?: [];

        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT o.id, o.curso_id, l.grade_id
               FROM portal_disciplinas_alunos e
               INNER JOIN rm_professores_materias o ON o.id = e.rm_prof_mat_id
               LEFT JOIN uni_rh_cursos_disciplinas_grade l ON l.id = o.matriz_id
              WHERE e.rm = :rm AND e.situacao = :s
              ORDER BY o.ano, o.materia",
            "rm={$rm}&s=1"
        );
        $ofertas = $ler->getResultado() ?: [];

        $blocos = [];
        $usadas = [];
        foreach ($matriculas as $m) {
            $doCurso = array_filter($ofertas, fn($o) => (int) $o['curso_id'] === (int) $m['curso_id']
                && ($o['grade_id'] === null || (int) $o['grade_id'] === (int) $m['grade_id']));
            $blocos[] = ['matricula' => $m, 'modulos' => self::porModulo(array_column($doCurso, 'id'), $rm)];
            foreach ($doCurso as $o) {
                $usadas[(int) $o['id']] = true;
            }
        }
        $soltas = array_filter($ofertas, fn($o) => !isset($usadas[(int) $o['id']]));
        if ($soltas) {
            $blocos[] = ['matricula' => null, 'modulos' => self::porModulo(array_column($soltas, 'id'), $rm)];
        }
        return ['aluno' => $aluno, 'blocos' => $blocos, 'regras' => Calc::regras()];
    }

    /** Disciplinas agrupadas por módulo (ordem do módulo; "Sem módulo" por último). */
    private static function porModulo(array $ofertaIds, string $rm): array
    {
        $modulos = [];
        foreach ($ofertaIds as $id) {
            $d = Calc::alunoNaOferta((int) $id, $rm);
            if (!$d) {
                continue;
            }
            $o = $d['oferta'];
            $chave = $o['modulo_id'] !== null ? str_pad((string) $o['ordemModulo'], 4, '0', STR_PAD_LEFT) . '-' . $o['modulo_id'] : '9999-sem';
            $modulos[$chave] ??= ['nome' => $o['modulo'] ?? 'Sem módulo', 'ch' => 0, 'disciplinas' => []];
            $modulos[$chave]['ch'] += (int) $o['ch'];
            $modulos[$chave]['disciplinas'][] = [
                'oferta' => (int) $o['id'],
                'materia' => $o['materia'],
                'ch' => (int) $o['ch'],
                'ano' => (int) $o['ano'],
                'turma' => $o['codtur'],
                'media' => $d['media'],
                'parcial' => $d['parcial'],
                'casas' => (int) ($d['casas'] ?? Calc::regras()['casas']),
                'frequencia' => $d['frequencia'],
                /* E2 (D-361): FS/FI do motor (só encerrada) e o mínimo que vale para esta disciplina (o gravado, ou o do curso) */
                'siglaFreq' => Calc::siglaFrequencia($d),
                'frequenciaMinima' => $d['frequenciaMinima'] ?? Calc::regras((int) $o['curso_id'])['frequenciaMinima'],
                'faltas' => (int) ($d['faltas'] ?? 0),
                'compensadas' => (int) ($d['compensadas'] ?? 0),
                'resultado' => $d['resultado'],
                'motivo' => $d['motivo'],
                'rotulo' => Calc::rotuloResultado($d['resultado'], $d['motivo']),
                'encerrada' => !empty($o['encerrada_em']),
                /* 1.13.0 (D-400): a memória de cálculo do motor ("Como foi calculado", no portal) */
                'memoria' => $d['memoria']['linhas'] ?? null,
                'conta' => $d['conta'] ?? '',
            ] + (isset($d['etapas']) ? [
                /* Onda 4, Etapa 3: só na escola, as etapas (a nota de cada uma, a recuperação) e a situação do ano — no
                   ensino superior, o boletim continua exatamente o de antes */
                'etapas' => $d['etapas'],
                'situacao' => $d['situacao'] ?? null,
            ] : []);
        }
        ksort($modulos);
        return array_values($modulos);
    }

    /**
     * 1.13.0 (D-400): "Como foi calculado" — a memória de cálculo de cada disciplina do bloco, RECOLHIDA (<details>).
     * Disciplina aberta com a média parcial escondida do aluno (`$ocultarParcial`): não mostra a conta.
     */
    public static function comoFoiCalculado(array $bl, bool $ocultarParcial = false): string
    {
        $itens = '';
        foreach ($bl['modulos'] as $mod) {
            foreach ($mod['disciplinas'] as $d) {
                if ($d['media'] === null || ($ocultarParcial && !$d['encerrada'])) {
                    continue;
                }
                $linhas = $d['memoria'] ?: [$d['conta'] !== '' && $d['conta'] !== 'gravada no encerramento' ? $d['conta'] : 'Média gravada no encerramento.'];
                $resumo = self::h(Calc::formatar($d['media'], $d['casas'])) . ($d['parcial'] ? ' (parcial)' : '');
                if (isset($d['etapas']) && defined('VCOSTAEDU_APP_PORTAL')) {
                    /* Onda 7, Etapa 1: na escola, o portal mostra a conta só das etapas que a escola liberou. Correção
                       da escola, Parte 1 (D-100): a média anual só com a média final liberada; a situação e o conselho,
                       só com a situação final liberada (a mesma regra da tabela) */
                    $liberadas = Calc::etapasLiberadas();
                    $comMedia = Calc::finalLiberado((int) $d['ano'], 'media_final');
                    $comSituacao = Calc::finalLiberado((int) $d['ano'], 'situacao_final');
                    $fora = [];
                    foreach ((array) $d['etapas'] as $e) {
                        if (!in_array((int) $e['id'], $liberadas, true)) {
                            $fora[] = (string) $e['nome'];
                        }
                    }
                    $linhas = array_values(array_filter($linhas, function ($l) use ($fora, $comMedia, $comSituacao) {
                        foreach ($fora as $nome) {
                            if (str_starts_with((string) $l, $nome . ':')) {
                                return false;
                            }
                        }
                        if (!$comMedia && preg_match('/^Média anual/u', (string) $l)) {
                            return false;
                        }
                        if (!$comSituacao && preg_match('/^(Situação|Conselho de classe)/u', (string) $l)) {
                            return false;
                        }
                        return !$fora || !preg_match('/^Frequência/u', (string) $l);
                    }));
                    if (!$linhas) {
                        continue;
                    }
                    $resumo = $fora || !$comMedia ? 'as etapas liberadas' : $resumo;
                }
                $itens .= '<details class="memoria-disciplina"><summary>' . self::h($d['materia']) . ': ' . $resumo . '</summary><ul class="mb-1 ps-3">'
                    . implode('', array_map(fn($l) => '<li>' . self::h($l) . '</li>', $linhas)) . '</ul></details>';
            }
        }
        return $itens === '' ? '' : '<details class="como-calculado small mt-1" id="comoFoiCalculado"><summary><b>Como foi calculado</b></summary><div class="ps-2">' . $itens . '</div></details>';
    }

    /** Texto das células (mesmo formato nas três saídas). */
    public static function celulas(array $d): array
    {
        return [
            'media' => Calc::formatar($d['media'], $d['casas']) . ($d['parcial'] && $d['media'] !== null ? ' (parcial)' : ''),
            'frequencia' => $d['frequencia'] === null ? 'não informada' : trim(Calc::formatar($d['frequencia'], 2) . '% ' . ($d['siglaFreq'] ?? '')), // E2: percentual e sigla
            'resultado' => $d['rotulo'],
        ];
    }

    /* ---------------- E8c (A11): o MESMO desenho no painel, no PDF e no portal ---------------- */

    private static function h($t): string
    {
        return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
    }

    /** Cursos (matrícula) e anos presentes no boletim, para o seletor do topo. */
    public static function opcoes(array $b): array
    {
        $cursos = [];
        $anos = [];
        foreach ($b['blocos'] ?? [] as $bl) {
            $m = $bl['matricula'];
            /* Onda 7, Etapa 1: o nome do curso não leva a versão da grade; ela só aparece, com rótulo, quando o curso tem mais de uma */
            $cursos[$m ? (int) $m['id'] : -1] = $m ? $m['curso'] . (self::gradesDoCurso((int) $m['curso_id']) > 1 ? ' (grade ' . (int) $m['versao'] . ')' : '') : 'Outras disciplinas';
            foreach ($bl['modulos'] as $mod) {
                foreach ($mod['disciplinas'] as $d) {
                    $anos[(int) $d['ano']] = true;
                }
            }
        }
        $anos = array_keys($anos);
        sort($anos);
        return ['cursos' => $cursos, 'anos' => $anos];
    }

    /** Aplica o seletor: $curso = id da matrícula (-1 = outras; 0 = todos), $ano (0 = todos). A CH do grupo segue o filtro. */
    public static function filtrar(array $b, int $curso, int $ano): array
    {
        if (empty($b['blocos'])) {
            return $b;
        }
        $blocos = [];
        foreach ($b['blocos'] as $bl) {
            $id = $bl['matricula'] ? (int) $bl['matricula']['id'] : -1;
            if ($curso !== 0 && $id !== $curso) {
                continue;
            }
            $mods = [];
            foreach ($bl['modulos'] as $mod) {
                $ds = $ano ? array_values(array_filter($mod['disciplinas'], fn($d) => (int) $d['ano'] === $ano)) : $mod['disciplinas'];
                if ($ds) {
                    $mod['disciplinas'] = $ds;
                    $mod['ch'] = array_sum(array_column($ds, 'ch'));
                    $mods[] = $mod;
                }
            }
            if ($mods || !$ano) {
                $bl['modulos'] = $mods;
                $blocos[] = $bl;
            }
        }
        $b['blocos'] = $blocos;
        return $b;
    }

    /** Quantas grades o curso tem (a versão da grade só importa ao leitor quando há mais de uma). */
    public static function gradesDoCurso(int $cursoId): int
    {
        static $n = [];
        if (!isset($n[$cursoId])) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead('SELECT COUNT(*) AS n FROM rm_cursos_grades WHERE curso_id = :c', "c={$cursoId}");
            $n[$cursoId] = (int) (($ler->getResultado()[0]['n'] ?? 0));
        }
        return $n[$cursoId];
    }

    /** Faixa colorida com os dados do aluno e do curso (tabela: funciona também no PDF). */
    public static function faixaAluno(array $aluno, ?array $m): string
    {
        $cel = fn($rotulo, $valor) => '<td><span class="rotulo">' . self::h($rotulo) . '</span>' . self::h($valor) . '</td>';
        $html = '<table class="faixa-aluno"><tr>' . $cel('Aluno(a)', ($aluno['nome_social'] ?? '') ?: $aluno['nome']) . $cel(\App\adms\Models\helper\AdmsParametro::naEscola() ? 'RM' : 'RA', $aluno['rm']);
        if ($m) {
            /* Onda 7, Etapa 1: o curso pelo nome; a versão da grade num campo próprio, só quando o curso tem mais de uma */
            $html .= $cel(self::rotuloCurso(), $m['curso']) . (self::gradesDoCurso((int) $m['curso_id']) > 1 ? $cel('Grade', (string) (int) $m['versao']) : '') . $cel('Situação', $m['situacao'] ?? '—')
                . $cel('Ingresso', $m['data_ingresso'] ? date('d/m/Y', strtotime($m['data_ingresso'])) : '—');
        } else {
            $html .= $cel(self::rotuloCurso(), 'Disciplinas fora de uma matrícula no ' . self::rotuloCurso(false));
        }
        return $html . '</tr></table>';
    }

    /**
     * Tabela compacta de um bloco (curso): números alinhados, abaixo do mínimo em vermelho, "parcial" em etiqueta
     * pequena, faltas e compensadas como número. $o: 'ano' => mostra a coluna Ano; 'ocultarParcial' => média de
     * disciplina aberta vira "—" (parâmetro do portal); 'modulos' => linhas de grupo por módulo com a CH (padrão:
     * o parâmetro "Organizar a matriz em módulos"); sem módulos, uma lista simples de disciplinas.
     */
    public static function tabela(array $bl, array $rg, array $o = []): string
    {
        foreach ($bl['modulos'] as $mod) {
            foreach ($mod['disciplinas'] as $d) {
                if (!empty($d['etapas'])) {
                    return self::tabelaEscola($bl, $o); // Onda 4, Etapa 3: o boletim da escola, por etapa
                }
            }
        }
        $comAno = !empty($o['ano']);
        $comModulos = $o['modulos'] ?? self::usaModulos();
        $cols = $comAno ? 8 : 7;
        $min = (float) $rg['mediaMinima'];
        $minF = (float) $rg['frequenciaMinima'];
        $sm = ' class="num d-none d-md-table-cell"';
        /* E8d (P-7): abaixo de 576 px o boletim vira CARTÕES (um por disciplina); a tabela fica para as telas maiores.
           No PDF ('pdf' => true) não há CSS do Bootstrap: sai só a tabela. */
        $pdf = !empty($o['pdf']);
        $linhas = [];
        foreach ($bl['modulos'] as $mod) {
            $itens = [];
            foreach ($mod['disciplinas'] as $d) {
                $oculta = !empty($o['ocultarParcial']) && empty($d['encerrada']);
                $itens[] = $d + [
                    'txtMedia' => $oculta ? '—' : Calc::formatar($d['media'], $d['casas']),
                    'mAbaixo' => !$oculta && $d['media'] !== null && (float) $d['media'] < $min,
                    'fAbaixo' => $d['frequencia'] !== null && (float) $d['frequencia'] < (float) ($d['frequenciaMinima'] ?? $minF), // E2 (D-360)
                    'txtFreq' => $d['frequencia'] === null ? '—' : trim(Calc::formatar($d['frequencia'], 2) . '% ' . ($d['siglaFreq'] ?? '')), // E2 (D-361)
                    'eParcial' => !$oculta && $d['parcial'] && $d['media'] !== null,
                    'cor' => $d['resultado'] === 1 ? 'success' : ($d['resultado'] === 2 ? 'danger' : 'secondary'),
                ];
            }
            $linhas[] = ['nome' => $mod['nome'], 'ch' => (int) $mod['ch'], 'itens' => $itens];
        }
        $parcial = '<span class="etiqueta-parcial" title="Disciplina aberta: média só das atividades com nota">parcial</span>';
        $chTotal = array_sum(array_column($bl['modulos'], 'ch'));

        /* ---- tabela */
        $html = '<table class="table table-sm table-striped table-compacta tabela-boletim mb-0"><thead><tr><th>Disciplina</th><th' . $sm . '>CH</th>'
            . ($comAno ? '<th' . $sm . '>Ano</th>' : '') . '<th class="num">Média</th><th class="num">Faltas</th><th class="num" title="Faltas compensadas">Comp.</th>'
            . '<th class="num">Frequência</th><th>Resultado</th></tr></thead><tbody>';
        if (!$linhas) {
            $html .= '<tr><td colspan="' . $cols . '" class="text-muted">Nenhuma disciplina.</td></tr>';
        }
        foreach ($linhas as $g) {
            if ($comModulos) {
                $html .= '<tr class="grupo"><td colspan="' . $cols . '">' . self::h($g['nome']) . ' <span class="ch">' . $g['ch'] . ' h</span></td></tr>';
            }
            foreach ($g['itens'] as $d) {
                $html .= '<tr class="linhaBoletim" data-oferta="' . (int) $d['oferta'] . '"><td>' . self::h($d['materia']) . '</td>'
                    . '<td' . $sm . '>' . (int) $d['ch'] . '</td>' . ($comAno ? '<td' . $sm . '>' . (int) $d['ano'] . '</td>' : '')
                    . '<td class="num media' . ($d['mAbaixo'] ? ' abaixo' : '') . '">' . self::h($d['txtMedia']) . ($d['eParcial'] ? ' ' . $parcial : '') . '</td>'
                    . '<td class="num faltas">' . (int) $d['faltas'] . '</td><td class="num compensadas">' . (int) $d['compensadas'] . '</td>'
                    . '<td class="num frequencia' . ($d['fAbaixo'] ? ' abaixo' : '') . '">' . self::h($d['txtFreq']) . '</td>'
                    . '<td class="resultado"><span class="badge bg-' . $d['cor'] . '-lt">' . self::h($d['rotulo']) . '</span></td></tr>';
            }
        }
        if (!$comModulos && $linhas) {
            $html .= '<tr class="grupo total"><td colspan="' . $cols . '">Carga horária total <span class="ch">' . (int) $chTotal . ' h</span></td></tr>';
        }
        $html .= '</tbody></table>';
        if ($pdf) {
            return $html;
        }

        /* ---- cartões (celular) */
        $c = '<div class="boletim-cartoes d-sm-none">';
        if (!$linhas) {
            $c .= '<p class="text-muted p-2 mb-0">Nenhuma disciplina.</p>';
        }
        foreach ($linhas as $g) {
            if ($comModulos) {
                $c .= '<div class="cartoes-grupo">' . self::h($g['nome']) . ' <span class="ch">' . $g['ch'] . ' h</span></div>';
            }
            foreach ($g['itens'] as $d) {
                $c .= '<div class="cartao-boletim" data-oferta="' . (int) $d['oferta'] . '">'
                    . '<div class="cb-topo"><span class="cb-nome">' . self::h($d['materia']) . '</span><span class="badge bg-' . $d['cor'] . '-lt">' . self::h($d['rotulo']) . '</span></div>'
                    . '<dl class="cb-dados">'
                    . '<div><dt>CH</dt><dd>' . (int) $d['ch'] . ' h</dd></div>'
                    . '<div><dt>Média</dt><dd class="cb-media' . ($d['mAbaixo'] ? ' abaixo' : '') . '">' . self::h($d['txtMedia']) . ($d['eParcial'] ? ' ' . $parcial : '') . '</dd></div>'
                    . '<div><dt>Faltas</dt><dd>' . (int) $d['faltas'] . ((int) $d['compensadas'] ? ' <small>(' . (int) $d['compensadas'] . ' comp.)</small>' : '') . '</dd></div>'
                    . '<div><dt>Frequência</dt><dd class="cb-frequencia' . ($d['fAbaixo'] ? ' abaixo' : '') . '">' . self::h($d['txtFreq']) . '</dd></div>'
                    . '</dl></div>';
            }
        }
        if (!$comModulos && $linhas) {
            $c .= '<div class="cartoes-total">Carga horária total <span class="ch">' . (int) $chTotal . ' h</span></div>';
        }
        $c .= '</div>';
        return '<div class="d-none d-sm-block">' . $html . '</div>' . $c;
    }

    /**
     * O BOLETIM DA ESCOLA (Onda 4, Etapa 3; docs/ESCOLA.md): as etapas em colunas (a nota da etapa; "R" quando houve
     * recuperação), a média anual, as faltas, a frequência e a situação. No portal (o aluno e a família), só as etapas que a escola
     * liberou; a média anual e a situação, cada uma pela liberação dela (D-100). Mesmos números da tela das etapas e do PDF: todos
     * vêm de AdmsCalculoNotas (o mesmo cálculo).
     */
    private static function tabelaEscola(array $bl, array $o): string
    {
        $portal = defined('VCOSTAEDU_APP_PORTAL');
        $liberadas = $portal ? Calc::etapasLiberadas() : null;
        $etapas = [];
        foreach ($bl['modulos'] as $mod) {
            foreach ($mod['disciplinas'] as $d) {
                foreach ((array) $d['etapas'] as $e) {
                    $etapas[(int) $e['ordem']] = ['id' => (int) $e['id'], 'nome' => (string) $e['nome']];
                }
            }
        }
        ksort($etapas);
        /* D-100: a média anual e a situação, cada uma pela liberação dela no ano (a média final, a situação final); no
           painel, tudo */
        $comMedia = static fn(array $d): bool => !$portal || Calc::finalLiberado((int) $d['ano'], 'media_final');
        $comSituacao = static fn(array $d): bool => !$portal || Calc::finalLiberado((int) $d['ano'], 'situacao_final');
        $v = static fn($n) => $n === null || $n === '' ? '—' : str_replace('.', ',', (string) $n);
        $sit = \Core\MediaEscola::SITUACOES;
        $html = '<div class="table-responsive"><table class="table table-sm table-striped table-compacta tabela-boletim tabela-boletim-escola mb-0"><thead><tr><th>Disciplina</th>';
        foreach ($etapas as $e) {
            $html .= '<th class="num" data-etapa="' . $e['id'] . '">' . self::h($e['nome']) . '</th>';
        }
        $html .= '<th class="num">Média anual</th><th class="num">Faltas</th><th class="num">Frequência</th><th>Situação</th></tr></thead><tbody>';
        foreach ($bl['modulos'] as $mod) {
            $html .= '<tr class="grupo"><td colspan="' . (count($etapas) + 5) . '">' . self::h($mod['nome']) . '</td></tr>';
            foreach ($mod['disciplinas'] as $d) {
                $porOrdem = [];
                foreach ((array) $d['etapas'] as $e) {
                    $porOrdem[(int) $e['ordem']] = $e;
                }
                $html .= '<tr class="linhaBoletim" data-oferta="' . (int) $d['oferta'] . '"><td>' . self::h($d['materia']) . '</td>';
                foreach ($etapas as $ordem => $e) {
                    $x = $porOrdem[$ordem] ?? null;
                    $mostra = $x && ($liberadas === null || in_array((int) $x['id'], $liberadas, true));
                    $rec = $mostra && $x['recuperacao'] !== null;
                    /* D-102 (A2): a nota trazida da escola de origem, marcada "T"; a etapa antes da matrícula, sem nota */
                    $transf = $mostra && isset($x['transferencia']);
                    $antesMat = $mostra && !empty($x['antesDaMatricula']);
                    $html .= '<td class="num etapa" data-etapa="' . $e['id'] . '">' . ($antesMat ? '<span class="text-secondary vc-antes-matricula" title="Antes da matrícula do aluno: sem nota, fora da média anual">—</span>' : ($mostra ? self::h($v($x['nota'])) . ($rec ? ' <span class="badge bg-orange-lt" title="Com recuperação: média ' . self::h($v($x['media'])) . ', recuperação ' . self::h($v($x['recuperacao'])) . '">R</span>' : '')
                        . ($transf ? ' <span class="badge bg-purple-lt vc-nota-transferencia" title="Nota de transferência' . ($x['transferencia'] !== '' ? ': ' . self::h($x['transferencia']) : '') . '">T</span>' : '') : '—')) . '</td>';
                }
                $situacao = $comSituacao($d) ? (string) ($d['situacao'] ?? 'em_curso') : 'em_curso';
                $cor = in_array($situacao, ['aprovado', 'aprovado_conselho'], true) ? 'success' : (str_starts_with($situacao, 'reprovado') ? 'danger' : 'secondary');
                $html .= '<td class="num media">' . ($comMedia($d) ? self::h($v($d['media'])) . ($d['parcial'] && $d['media'] !== null ? ' <span class="etiqueta-parcial" title="Ano em curso: só as etapas com nota">parcial</span>' : '') : '—') . '</td>'
                    . '<td class="num faltas">' . (int) $d['faltas'] . '</td>'
                    . '<td class="num frequencia">' . ($d['frequencia'] === null ? '—' : self::h($v($d['frequencia'])) . '%') . '</td>'
                    . '<td class="resultado"><span class="badge bg-' . $cor . '-lt">' . self::h($sit[$situacao] ?? 'Em curso') . '</span></td></tr>';
            }
        }
        return $html . '</tbody></table></div>';
    }

    /** Parâmetro "Organizar a matriz em módulos" (E8c, complemento da instituição de origem); sem o parâmetro, Sim (comportamento 1.7.2). */
    public static function usaModulos(): bool
    {
        return !method_exists(\App\adms\Models\helper\AdmsParametro::class, 'usaModulos') || \App\adms\Models\helper\AdmsParametro::usaModulos();
    }

    /** As regras, num quadro discreto. */
    /**
     * E5 (decisão 5 do responsável pelo projeto, ponto 8 da E1b): os mínimos são os DO CURSO do aluno — `Calc::regras(curso)`, a mesma
     * função do quadro e do fechamento. `$blocos`: os blocos mostrados (com a matrícula). Um mínimo só: a frase de
     * sempre; cursos com mínimos diferentes: cada mínimo com os seus cursos. Sem blocos: a regra da instituição ($rg).
     */
    public static function regrasTexto(array $rg, bool $mostraParcial = true, array $blocos = []): string
    {
        foreach ($blocos as $bl) {
            foreach ($bl['modulos'] ?? [] as $mod) {
                foreach ($mod['disciplinas'] ?? [] as $d) {
                    if (isset($d['etapas'])) {
                        /* Onda 7, Etapa 1: o boletim da escola lê com as regras da escola (Regras da escola) */
                        $ce = Calc::confEscola();
                        $mn = \Core\MediaEscola::v((string) $ce['mediaMinima']);
                        return '<div class="regras-boletim" id="comoLerEscola"><b>Como ler:</b> em cada etapa, abaixo de ' . self::h($mn) . ' o aluno faz a recuperação; '
                            . '<b>R</b> marca a etapa em que ela foi lançada (passe o mouse para ver a média e a recuperação). Aprovado com média anual a partir de '
                            . self::h($mn) . ' e frequência a partir de ' . self::h(preg_replace('/,0$/', '', \Core\MediaEscola::v((string) $ce['frequenciaMinima']))) . '%.</div>';
                    }
                }
            }
        }
        $media = [];
        $freq = [];
        foreach ($blocos as $bl) {
            $cid = (int) ($bl['matricula']['curso_id'] ?? 0);
            $r = $cid ? Calc::regras($cid) : $rg;
            $nome = $cid ? (string) $bl['matricula']['curso'] : 'sem matrícula no curso';
            $media[Calc::numero($r['mediaMinima'], 1)][$nome] = true;
            $freq[Calc::numero($r['frequenciaMinima'])][$nome] = true;
        }
        $lista = function (array $grupos, string $sufixo, string $padrao): string {
            if (count($grupos) <= 1) {
                return self::h(count($grupos) ? array_key_first($grupos) : $padrao) . $sufixo;
            }
            return implode('; ', array_map(fn($v, $cursos) => self::h($v) . $sufixo . ' em ' . self::h(implode(', ', array_keys($cursos))), array_keys($grupos), $grupos));
        };
        return '<div class="regras-boletim"><b>Como ler:</b> aprovado com média a partir de ' . $lista($media, '', Calc::numero($rg['mediaMinima'], 1))
            . ' e frequência a partir de ' . $lista($freq, '%', Calc::numero($rg['frequenciaMinima'])) . '. Frequência = 1 − (faltas − compensadas) ÷ encontros. '
            . 'Em vermelho, o que está abaixo do mínimo. '
            . ($mostraParcial ? 'Com a disciplina aberta, a média é <b>parcial</b> (só das atividades com nota).' : 'Com a disciplina aberta, a média não é mostrada: aparece no encerramento.')
            . '</div>';
    }

    /** Seletor de curso e ano (GET). $manter: parâmetros que a URL precisa carregar (o portal leva o ano da página). */
    /** D-125 (1.102.1, C3.3): "Curso" no vocabulário do tipo (na escola, "Segmento"). */
    private static function rotuloCurso(bool $maiuscula = true): string
    {
        return \VcVocabulario::termo('curso', \App\adms\Models\helper\AdmsTipoPortal::atual(), false, $maiuscula);
    }

    public static function seletor(array $op, int $curso, int $ano, string $acao, array $manter = []): string
    {
        $html = '<form method="GET" action="' . self::h($acao) . '" class="d-flex flex-wrap align-items-center gap-1 seletor-boletim" id="seletorBoletim">';
        foreach ($manter as $k => $v) {
            $html .= '<input type="hidden" name="' . self::h($k) . '" value="' . self::h($v) . '">';
        }
        $html .= '<label class="me-1 small" for="filtroCurso">' . self::rotuloCurso() . '</label><select name="filtro_curso" id="filtroCurso" class="form-select form-select-sm w-auto me-2" onchange="this.form.submit()"><option value="0">Todos</option>';
        foreach ($op['cursos'] as $id => $nome) {
            $html .= '<option value="' . (int) $id . '"' . ((int) $id === $curso ? ' selected' : '') . '>' . self::h($nome) . '</option>';
        }
        $html .= '</select><label class="me-1 small" for="filtroAno">Ano</label><select name="filtro_ano" id="filtroAno" class="form-select form-select-sm w-auto me-2" onchange="this.form.submit()"><option value="0">Todos</option>';
        foreach ($op['anos'] as $a) {
            $html .= '<option value="' . (int) $a . '"' . ((int) $a === $ano ? ' selected' : '') . '>' . (int) $a . '</option>';
        }
        return $html . '</select><noscript><button type="submit" class="btn btn-sm btn-outline-primary">Filtrar</button></noscript></form>';
    }
}
