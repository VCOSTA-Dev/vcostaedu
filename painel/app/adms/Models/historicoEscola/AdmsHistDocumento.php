<?php

namespace App\adms\Models\historicoEscola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\historicoEscola\AdmsHistBase as B;
use App\adms\Models\historicoEscola\AdmsHistCadastros as C;
use App\adms\Models\historicoEscola\AdmsHistVida as V;

/**
 * O DOCUMENTO DO HISTÓRICO ESCOLAR (Parte D; D-113): a montagem do conteúdo (tudo o que é impresso, também o que vai na
 * cópia guardada da emissão) e a renderização no desenho do modelo do Fundamental e do Médio (Parte G; D-116), frente e
 * verso. No Médio, as linhas levam a área, o grupo e o itinerário da matriz do ano, e a carga de cada série; do grupo do
 * itinerário formativo, só o itinerário que o aluno cursou (o da matrícula do ano).
 *
 * O DESENHO vem do modelo da escola (a ordem dos blocos, os títulos, as mesclas, os rótulos verticais, os números soltos
 * antes dos títulos: 10, 30, 40, 50, 60, 70); TODO NÚMERO vem do sistema (as médias pela função única, a carga pela
 * matriz do ano, as datas). Série não cursada: em branco. Série em curso ou da transferência: sem nota e sem carga na
 * frente; o que foi cursado vai para o bloco de transferência do verso. A retenção é uma coluna a mais. O tamanho da
 * letra: o primeiro degrau que dá exatamente duas páginas (frente e verso), contadas no PDF já com o rodapé.
 */
class AdmsHistDocumento
{
    /** os degraus: a altura da linha do quadro (mm) e o corpo da letra (pt), do maior para o menor */
    public const DEGRAUS = [[7.2, 10], [6.6, 9.5], [6.0, 9.0], [5.6, 8.5], [5.2, 8.0], [4.9, 7.8], [4.6, 7.5], [3.9, 6.8], [3.4, 6.2], [3.0, 5.6]];
    /** a margem direita da folha A4 (595,28 pt − 13 mm), com meio ponto de folga: nada pode passar dela */
    public const LIMITE_X = 559.0;
    public const TARJA_PREVIA = 'PRÉVIA — documento não emitido, sem validade.';

    /**
     * O conteúdo do documento e os avisos. `$op`: data (o documento), publicacao, dias_letivos, aulas (aulas dadas por
     * disciplina no bloco de transferência).
     * @return array{dados: array, avisos: list<string>, bloqueios: list<string>}
     */
    public static function montar(int $alunoId, string $nivel, array $op = []): array
    {
        $avisos = [];
        $bloqueios = [];
        $data = B::data((string) ($op['data'] ?? '')) ?: date('Y-m-d');
        $a = C::aluno($alunoId);
        if (!$a) {
            return ['dados' => [], 'avisos' => [], 'bloqueios' => ['Aluno não encontrado.']];
        }
        $t = C::resolvido($nivel, $data);
        if ($t['_semCadastro']) {
            $avisos[] = 'Os textos do histórico (' . B::NIVEIS[$nivel] . ') não estão cadastrados para a data do documento: saem os dados da instituição e o texto de partida.';
        }
        $vida = V::vida($alunoId, $nivel);
        if (!$vida) {
            $bloqueios[] = 'O aluno não tem nenhuma série no ' . B::NIVEIS[$nivel] . ': nada a emitir.';
        }
        $max = B::SERIES[$nivel];
        $minima = (float) str_replace(',', '.', (string) (\App\adms\Models\notas\AdmsCalculoNotas::confEscola()['mediaMinima'] ?? '6'));

        /* as colunas: as séries 1..9 (ou 1..3), com a retenção como coluna a mais, em ordem cronológica */
        $porSerie = [];
        foreach ($vida as $s) {
            $porSerie[(int) $s['serie']][] = $s;
        }
        $colunas = [];
        $linhas = [];
        $religiosoLinha = ['nome' => 'ENSINO RELIGIOSO', 'cel' => []];
        for ($n = 1; $n <= $max; $n++) {
            foreach ($porSerie[$n] ?? [null] as $s) {
                $k = count($colunas);
                if (!$s) {
                    $colunas[] = ['serie' => $n, 'ano' => '', 'cursada' => false, 'situacao' => '', 'carga_bnc' => '', 'carga_pd' => '', 'carga_total' => '', 'transferese' => false]
                        + ($nivel === 'EM' ? ['em' => []] : []);
                    continue;
                }
                $semNota = in_array($s['resultado'], ['em_curso', 'transferido'], true);
                $col = ['serie' => $n, 'ano' => (int) $s['ano'], 'cursada' => true, 'situacao' => (string) $s['resultado'], 'transferese' => $s['resultado'] === 'transferido',
                    'em_curso' => $s['resultado'] === 'em_curso', 'carga_bnc' => '', 'carga_pd' => '', 'carga_total' => ''];
                $titulo = B::nomeSerie($nivel, $n) . ' de ' . $s['ano'];
                if (!$s['id']) {
                    $avisos[] = "{$titulo}: a série ainda é proposta — confirme na vida escolar antes de emitir.";
                }
                if ($s['resultado'] === '') {
                    $avisos[] = "{$titulo}: o resultado está a confirmar" . (!empty($s['motivo']) ? ' (' . $s['motivo'] . ')' : '') . '.';
                }
                if (!$s['escola_id']) {
                    $avisos[] = "{$titulo}: a série está sem escola.";
                }
                $q = V::disciplinas($s);
                foreach ($q['avisos'] as $x) {
                    $avisos[] = "{$titulo}: {$x}.";
                }
                $minutos = (int) ($q['matriz']['minutos'] ?? 50);
                $somaBnc = 0;
                $somaPd = 0;
                $abaixo = false;
                $itCol = (int) ($s['itinerario'] ?? 0);
                foreach ($q['linhas'] as $l) {
                    if ($l['religioso']) {
                        $religiosoLinha['cel'][$k] = $semNota ? '' : (string) ($l['media'] ?? '');
                        continue;
                    }
                    /* D-116: do itinerário formativo, só o que o aluno cursou (o da matrícula do ano); sem ele, saem todos e a prévia avisa */
                    if ($nivel === 'EM' && $l['grupo'] === 'IT_FORMATIVO') {
                        if ($itCol > 0 && (int) $l['itinerario'] !== $itCol) {
                            continue;
                        }
                        if ($itCol === 0) {
                            $avisos[] = "{$titulo}: sem o itinerário formativo na matrícula do ano (saem as disciplinas de todos os itinerários).";
                        }
                    }
                    $pd = in_array($l['grupo'], ['PD', 'IT_COMUM', 'IT_FORMATIVO'], true);
                    $chave = ($l['disciplina_id'] ? 'd' . $l['disciplina_id'] : 'm' . $l['materia_id']);
                    $ordNivel = $nivel === 'EM' ? (int) $l['ordem_em'] : (int) $l['ordem_ef'];
                    $linhas[$chave] ??= ['nome' => $l['nome'], 'pd' => $pd, 'ordem' => $ordNivel > 0 ? $ordNivel : 100000 + $l['ordem_matriz'], 'cel' => []];
                    $linhas[$chave]['ordem'] = min($linhas[$chave]['ordem'], $ordNivel > 0 ? $ordNivel : 100000 + $l['ordem_matriz']);
                    $linhas[$chave]['cel'][$k] = $semNota ? '' : (string) ($l['media'] ?? '');
                    if ($nivel === 'EM') {
                        $linhas[$chave] += ['grupo' => (string) $l['grupo'], 'area' => (string) $l['area'], 'area_ordem' => (int) $l['area_ordem'], 'itinerario' => (int) $l['itinerario'], 'carga' => []];
                        if (!$semNota) {
                            $linhas[$chave]['carga'][$k] = (int) $l['aulas_anuais'];
                        }
                    }
                    if (!$semNota && is_numeric(str_replace(',', '.', (string) $l['media'])) && (float) str_replace(',', '.', (string) $l['media']) < $minima) {
                        $abaixo = true;
                    }
                    $pd ? $somaPd += (int) $l['aulas_anuais'] : $somaBnc += (int) $l['aulas_anuais'];
                }
                if (!isset($religiosoLinha['cel'][$k])) {
                    $religiosoLinha['cel'][$k] = $semNota ? '' : (string) $s['religioso'];
                }
                if (!$semNota) {
                    $fmt = fn(int $aulas) => $aulas ? ($t['unidade_carga'] === 'horas' ? AdmsHistMatriz::horasTexto($aulas, $minutos) : number_format($aulas, 0, ',', '.')) : '';
                    $col['carga_bnc'] = $fmt($somaBnc);
                    $col['carga_pd'] = $fmt($somaPd);
                    $col['carga_total'] = (int) $s['carga_total'] ? number_format((int) $s['carga_total'], 0, ',', '.') : $fmt($somaBnc + $somaPd);
                }
                if ($nivel === 'EM') {
                    /* D-116: os totais do Médio (aulas da matriz; as horas com a fração; as semanais tiradas das anuais) */
                    $semanas = max(1, (int) ($q['matriz']['semanas'] ?? 40));
                    $col['em'] = $semNota ? [] : ['carga_fgb' => number_format($somaBnc, 0, ',', '.'), 'carga_it' => number_format($somaPd, 0, ',', '.'),
                        'sem_it' => (string) intdiv($somaPd + intdiv($semanas, 2), $semanas), 'sem_total' => (string) intdiv($somaBnc + $somaPd + intdiv($semanas, 2), $semanas),
                        'anuais' => number_format($somaBnc + $somaPd, 0, ',', '.'), 'horas' => AdmsHistMatriz::horasTexto($somaBnc + $somaPd, $minutos),
                        '_fgb' => $somaBnc, '_it' => $somaPd, '_semanas' => $semanas, '_horas10' => intdiv(($somaBnc + $somaPd) * $minutos * 10 + 30, 60)];
                }
                if ($abaixo && $s['resultado'] === 'aprovado' && !$s['conselho']) {
                    $avisos[] = "{$titulo}: aprovado com média abaixo da mínima sem a marca do conselho de classe.";
                }
                $colunas[] = $col;
            }
        }
        uasort($linhas, fn($x, $y) => $x['ordem'] <=> $y['ordem'] ?: strcmp($x['nome'], $y['nome']));
        $bnc = array_values(array_filter($linhas, fn($l) => !$l['pd']));
        $pd = array_values(array_filter($linhas, fn($l) => $l['pd']));

        /* os estudos realizados: uma linha por coluna */
        $estudos = [];
        $esta = C::estaEscola();
        foreach ($colunas as $k => $col) {
            $s = null;
            foreach ($porSerie[$col['serie']] ?? [] as $x) {
                if ((int) $x['ano'] === (int) $col['ano']) {
                    $s = $x;
                }
            }
            $estudos[] = ['serie' => B::nomeSerie($nivel, $col['serie']), 'ano' => $col['ano'] ?: '', 'escola' => $s ? (string) ($s['escola'] ?? '') . ($col['transferese'] ? ' (transfere-se)' : '') : '',
                'municipio' => $s ? (string) ($s['municipio'] ?? '') : '', 'uf' => $s ? (string) ($s['uf'] ?? '') : ''];
        }

        /* o bloco de transferência: a última série cursada aqui, em curso ou da saída */
        $ultima = $vida ? $vida[count($vida) - 1] : null;
        $transf = self::transferencia($ultima, $nivel, $data, $op, $avisos);

        /* as observações */
        $obs = [];
        $modelos = C::observacoes($nivel, true);
        foreach ($modelos as $o) {
            if ($o['tipo'] === 'padrao') {
                $obs[] = C::limpar((string) $o['texto']);
            }
        }
        $porChave = array_column($modelos, null, 'chave');
        foreach ($vida as $s) {
            $marcadas = array_filter(explode(',', (string) $s['obs_chaves']));
            if ($s['conselho'] && !in_array('conselho', $marcadas, true)) {
                $marcadas[] = 'conselho';
            }
            foreach ($marcadas as $ch) {
                if (isset($porChave[$ch]) && $porChave[$ch]['tipo'] === 'selecionavel') {
                    $obs[] = C::preencher((string) $porChave[$ch]['texto'], ['{serie}' => B::nomeSerie($nivel, (int) $s['serie']), '{ano}' => (string) $s['ano']]);
                } elseif ($ch === 'conselho' && !isset($porChave['conselho'])) {
                    $avisos[] = B::nomeSerie($nivel, (int) $s['serie']) . ' de ' . $s['ano'] . ': promovido pelo conselho, mas a observação "conselho" não está cadastrada.';
                }
            }
        }
        if (trim((string) $a['certidao']) !== '') {
            $obs[] = 'Certidão de Nascimento: ' . trim((string) $a['certidao']) . '.';
        }
        if (trim((string) $a['obs_livre']) !== '') {
            $obs[] = C::limpar((string) $a['obs_livre']);
        }

        /* D-116: o Médio (a frente por área e itinerário; o curso anterior no verso) */
        $medio = $nivel === 'EM' ? self::medio($linhas, $colunas, $vida, $a, $transf) : null;

        /* o fecho: certificado na conclusão do ciclo, aprovado; declaração nas demais */
        $fecho = self::fecho($ultima, $nivel, $max, $a, $t, $avisos);

        foreach (['secretaria_nome' => 'secretária', 'diretora_nome' => 'diretora'] as $c => $rot) {
            if (trim((string) $t[$c]) === '') {
                $avisos[] = "A assinatura da {$rot} não está cadastrada (Cadastros do histórico → Textos).";
            }
        }
        $nasc = (string) ($a['nasc_data'] ?? '');
        $dados = [
            'nivel' => $nivel, 'data' => $data, 'publicacao' => trim((string) ($op['publicacao'] ?? '')),
            'texto' => array_diff_key($t, ['_semCadastro' => 1]),
            'aluno' => ['id' => (int) $a['id'], 'rm' => (string) $a['rm'], 'nome' => (string) $a['nome'], 'ra' => (string) $a['ra'], 'rg' => trim((string) ($a['rg'] ?? '')),
                'nasc_local' => (string) ($a['nasc_local'] ?? ''), 'nasc_uf' => (string) ($a['nasc_uf'] ?? ''), 'nacionalidade' => self::nacionalidade((string) ($a['nasc_nac'] ?? '')),
                'dia' => $nasc ? substr($nasc, 8, 2) : '', 'mes' => $nasc ? substr($nasc, 5, 2) : '', 'ano' => $nasc ? substr($nasc, 0, 4) : '', 'prova' => (bool) $a['prova']],
            'colunas' => $colunas, 'bnc' => $bnc, 'pd' => $pd, 'religioso' => $religiosoLinha, 'estudos' => $estudos,
            'transferencia' => $transf, 'observacoes' => $obs, 'fecho' => $fecho,
            'logo' => C::logoDataUri($nivel),
        ];
        if ($medio !== null) {
            $dados['medio'] = $medio;
        }
        if (trim((string) $a['ra']) === '' && trim((string) $t['rotulo_registro']) !== '') {
            $avisos[] = 'O ' . trim((string) $t['rotulo_registro']) . ' do aluno está vazio (Dados do aluno para o histórico).';
        }
        if ($fecho['tipo'] === 'certificado' && $dados['aluno']['rg'] === '') {
            $avisos[] = 'O RG do aluno está vazio (o certificado o usa).';
        }
        /* D-133 (1.103.0, C10): no Médio, sem o registro da conclusão do Fundamental (o ano), a tela avisa quem emite e
           diz onde registrar; é aviso (não impede a emissão) e o documento não muda */
        if ($nivel === 'EM' && (int) ($a['ef_conclusao_ano'] ?? 0) === 0) {
            $avisos[] = 'Não há registro da conclusão do Ensino Fundamental deste aluno: registre o ano e a escola em Dados do aluno para o histórico ("Conclusão do Fundamental").';
        }
        if ($a['prova']) {
            $avisos[] = 'O aluno é dado de PROVA.';
        }
        return ['dados' => $dados, 'avisos' => array_values(array_unique($avisos)), 'bloqueios' => $bloqueios];
    }

    public static function nacionalidade(string $n): string
    {
        $n = trim($n);
        return preg_match('/^brasileir[oa]$/iu', $n) ? 'Brasileira' : ($n === '' ? '' : mb_convert_case($n, MB_CASE_TITLE));
    }

    /**
     * O bloco 50: preenchido para a última série cursada aqui quando ela está em curso ou é a da saída. As notas por etapa
     * (as etapas parametrizadas do ano: decisão 2), só das etapas iniciadas até a data; as faltas (as da disciplina, até
     * hoje); as aulas dadas propostas pela matriz (aulas semanais × semanas decorridas) e editáveis na prévia; os dias
     * letivos (os dias úteis do início do ano até a data), editáveis. Sem isso, o bloco sai em branco com as disciplinas
     * da última série (como nos modelos).
     */
    private static function transferencia(?array $s, string $nivel, string $data, array $op, array &$avisos): array
    {
        $vazio = ['preenchido' => false, 'ano' => '', 'ate' => '', 'turno' => '', 'serie' => '', 'turma' => '', 'chamada' => '', 'etapas' => [], 'bnc' => [], 'pd' => [], 'faltas' => '', 'dias' => ''];
        if (!$s) {
            return $vazio;
        }
        $q = V::disciplinas($s);
        $preencher = (int) $s['turma_id'] > 0 && in_array($s['resultado'], ['em_curso', 'transferido'], true);
        $etapas = B::todos('SELECT id, ordem, nome, inicio, fim FROM esc_etapas WHERE ano = ? ORDER BY ordem', [(int) $s['ano']]);
        $ate = $preencher ? min($data, (string) ($s['data_saida'] ?: $data), (int) $s['ano'] . '-12-31') : '';
        $r = $vazio;
        $r['etapas'] = array_map(fn($e) => self::abreviaEtapa((string) $e['nome'], (int) $e['ordem']), $etapas ?: [['nome' => '1º', 'ordem' => 1], ['nome' => '2º', 'ordem' => 2], ['nome' => '3º', 'ordem' => 3], ['nome' => '4º', 'ordem' => 4]]);
        $diasProp = 0;
        if ($preencher && $etapas) {
            $ini = strtotime((string) $etapas[0]['inicio']);
            $fim = strtotime(min($ate, (string) $etapas[count($etapas) - 1]['fim']));
            for ($d = $ini; $d <= $fim; $d += 86400) {
                if ((int) date('N', $d) <= 5) {
                    $diasProp++;
                }
            }
        }
        $semanas = intdiv($diasProp, 5);
        $totalFaltas = 0;
        foreach ($q['linhas'] as $l) {
            if ($l['religioso']) {
                continue;
            }
            if ($nivel === 'EM' && $l['grupo'] === 'IT_FORMATIVO' && (int) ($s['itinerario'] ?? 0) > 0 && (int) $l['itinerario'] !== (int) $s['itinerario']) {
                continue; // D-116: só o itinerário que o aluno cursa
            }
            $pd = in_array($l['grupo'], ['PD', 'IT_COMUM', 'IT_FORMATIVO'], true);
            $ordNivel = $nivel === 'EM' ? (int) $l['ordem_em'] : (int) $l['ordem_ef'];
            $lin = ['nome' => $l['nome'], 'notas' => array_fill(0, count($r['etapas']), ''), 'faltas' => '', 'aulas' => '', 'chave' => (string) ($l['disciplina_id'] ?: 'm' . $l['materia_id']), 'ordem' => $ordNivel > 0 ? $ordNivel : 100000 + $l['ordem_matriz']]
                + ($nivel === 'EM' ? ['area' => (string) $l['area'], 'area_ordem' => (int) $l['area_ordem'], 'grupo' => (string) $l['grupo'], 'itinerario' => (int) $l['itinerario']] : []);
            if ($preencher) {
                $c = $l['calc'] ?? null;
                foreach ($etapas as $i => $e) {
                    if ((string) $e['inicio'] > $ate) {
                        continue;
                    }
                    $et = $c['etapas'][(int) $e['ordem']] ?? null;
                    if ($et && $et['nota'] !== null) {
                        $lin['notas'][$i] = (string) V::nota((string) $et['nota']) . (!empty($et['transferencia']) ? '*' : '');
                    }
                }
                $f = $c ? max(0, (int) ($c['faltas'] ?? 0) - (int) ($c['compensadas'] ?? 0)) : 0;
                $lin['faltas'] = (string) $f;
                $totalFaltas += $f;
                $digitadas = (array) ($op['aulas'] ?? []);
                $lin['aulas'] = isset($digitadas[$lin['chave']]) && preg_match('/^\d{1,4}$/', (string) $digitadas[$lin['chave']]) ? (string) (int) $digitadas[$lin['chave']] : (string) ($l['aulas_semanais'] * $semanas);
            }
            $pd ? $r['pd'][] = $lin : $r['bnc'][] = $lin;
        }
        if ($nivel === 'EM') {
            usort($r['bnc'], fn($x, $y) => [($x['area'] ?? '') === '' ? 1 : 0, (int) ($x['area_ordem'] ?? 0), (string) ($x['area'] ?? ''), $x['ordem']] <=> [($y['area'] ?? '') === '' ? 1 : 0, (int) ($y['area_ordem'] ?? 0), (string) ($y['area'] ?? ''), $y['ordem']]);
            usort($r['pd'], fn($x, $y) => [($x['grupo'] ?? '') === 'IT_FORMATIVO' ? 1 : 0, (int) ($x['itinerario'] ?? 0), $x['ordem']] <=> [($y['grupo'] ?? '') === 'IT_FORMATIVO' ? 1 : 0, (int) ($y['itinerario'] ?? 0), $y['ordem']]);
        } else {
            usort($r['bnc'], fn($x, $y) => $x['ordem'] <=> $y['ordem']);
            usort($r['pd'], fn($x, $y) => $x['ordem'] <=> $y['ordem']);
        }
        if (!$preencher) {
            return $r;
        }
        $t = $s['turma_id'] ? B::um('SELECT t.nome, m.codnome, p.nome AS turno FROM esc_turmas t INNER JOIN rm_cursos_turma_series m ON m.id = t.modulo_id LEFT JOIN rm_cursos_periodos p ON p.id = t.turno_id WHERE t.id = ?', [(int) $s['turma_id']]) : null;
        $chamada = B::valor('SELECT me.ordem_chamada FROM rm_matriculas_ensino me WHERE me.usuario_id = ? AND me.ano = ? ORDER BY me.id DESC LIMIT 1', [(int) $s['aluno_id'], (int) $s['ano']]);
        $dias = isset($op['dias_letivos']) && preg_match('/^\d{1,3}$/', (string) $op['dias_letivos']) ? (int) $op['dias_letivos'] : $diasProp;
        if ($s['resultado'] === 'transferido' && !$s['data_saida']) {
            $avisos[] = B::nomeSerie($nivel, (int) $s['serie']) . ' de ' . $s['ano'] . ': transferido sem a data de saída (o bloco vai até a data do documento).';
        }
        return array_merge($r, ['preenchido' => true, 'ano' => (string) $s['ano'], 'ate' => B::br($ate), 'turno' => (string) ($t['turno'] ?? ''), 'serie' => B::nomeSerie($nivel, (int) $s['serie']),
            'turma' => $t ? $t['codnome'] . ' ' . $t['nome'] : '', 'chamada' => $chamada ? (string) $chamada : '', 'faltas' => (string) $totalFaltas, 'dias' => (string) $dias, 'diasPropostos' => $diasProp]);
    }

    /**
     * D-116: o Médio — as linhas da formação geral básica (por área, na ordem das áreas) e as do itinerário (o comum, depois
     * o formativo do aluno), com a carga de cada série e o total da linha; os totais gerais; o itinerário do aluno (o da
     * matrícula do último ano que o tem, com o nome da matriz daquele ano); o curso anterior (a conclusão do Fundamental).
     */
    private static function medio(array $linhas, array $colunas, array $vida, array $a, array $transf): array
    {
        $fgb = [];
        $it = [];
        foreach ($linhas as $l) {
            $l += ['grupo' => '', 'area' => '', 'area_ordem' => 0, 'itinerario' => 0, 'carga' => []];
            $l['total'] = array_sum(array_map('intval', $l['carga']));
            $l['carga'] = array_map(fn($x) => number_format((int) $x, 0, ',', '.'), $l['carga']);
            in_array($l['grupo'], ['IT_COMUM', 'IT_FORMATIVO', 'PD'], true) ? $it[] = $l : $fgb[] = $l;
        }
        usort($fgb, fn($x, $y) => [$x['area'] === '' ? 1 : 0, $x['area_ordem'], $x['area'], $x['ordem'], $x['nome']] <=> [$y['area'] === '' ? 1 : 0, $y['area_ordem'], $y['area'], $y['ordem'], $y['nome']]);
        usort($it, fn($x, $y) => [$x['grupo'] === 'IT_FORMATIVO' ? 1 : 0, $x['itinerario'], $x['ordem'], $x['nome']] <=> [$y['grupo'] === 'IT_FORMATIVO' ? 1 : 0, $y['itinerario'], $y['ordem'], $y['nome']]);
        $tot = ['fgb' => 0, 'it' => 0, 'sem_it' => 0, 'sem_total' => 0, 'anuais' => 0, 'horas10' => 0];
        foreach ($colunas as $c) {
            if (empty($c['em'])) {
                continue;
            }
            $tot['fgb'] += $c['em']['_fgb'];
            $tot['it'] += $c['em']['_it'];
            $tot['sem_it'] += (int) $c['em']['sem_it'];
            $tot['sem_total'] += (int) $c['em']['sem_total'];
            $tot['anuais'] += $c['em']['_fgb'] + $c['em']['_it'];
            $tot['horas10'] += $c['em']['_horas10'];
        }
        $num = 0;
        $anoIt = 0;
        foreach (array_reverse($vida) as $s) {
            if ((int) ($s['itinerario'] ?? 0) > 0) {
                $num = (int) $s['itinerario'];
                $anoIt = (int) $s['ano'];
                break;
            }
        }
        $nomes = static fn(int $ano) => $ano ? array_column(B::todos("SELECT i.numero, i.nome FROM esc_hist_itinerario i INNER JOIN esc_hist_matriz m ON m.id = i.matriz_id
            INNER JOIN rm_cursos_grades g ON g.id = m.grade_id INNER JOIN esc_hist_segmento sg ON sg.curso_id = g.curso_id AND sg.nivel = 'EM' WHERE m.ano = ? ORDER BY m.id", [$ano]), 'nome', 'numero') : [];
        $escolas = array_column(C::escolas(), null, 'id');
        $e = $escolas[(int) ($a['ef_conclusao_escola_id'] ?? 0)] ?? null;
        $fmt = fn(int $x) => $x ? number_format($x, 0, ',', '.') : '';
        $h10 = $tot['horas10'];
        return ['fgb' => $fgb, 'it' => $it, 'itinerario' => ['numero' => $num, 'nome' => (string) ($nomes($anoIt)[$num] ?? '')],
            'itinerarios' => $nomes((int) ($transf['ano'] ?: $anoIt)),
            'totais' => ['carga_fgb' => $fmt($tot['fgb']), 'carga_it' => $fmt($tot['it']), 'sem_it' => $tot['sem_it'] ? (string) $tot['sem_it'] : '', 'sem_total' => $tot['sem_total'] ? (string) $tot['sem_total'] : '',
                'anuais' => $fmt($tot['anuais']), 'horas' => $h10 ? ($h10 % 10 === 0 ? number_format(intdiv($h10, 10), 0, ',', '.') : number_format($h10 / 10, 1, ',', '.')) : ''],
            'conclusao_ef' => ['ano' => (int) ($a['ef_conclusao_ano'] ?? 0) ?: '', 'escola' => (string) ($e['nome'] ?? ''), 'municipio' => (string) ($e['municipio'] ?? ''), 'uf' => (string) ($e['uf'] ?? ''),
                'publicacao' => (string) ($a['ef_publicacao'] ?? '')]];
    }

    public static function abreviaEtapa(string $nome, int $ordem): string
    {
        if (preg_match('/bimestre/i', $nome)) {
            return $ordem . 'º Bim';
        }
        if (preg_match('/trimestre/i', $nome)) {
            return $ordem . 'º Tri';
        }
        return mb_substr($nome, 0, 8);
    }

    /** O fecho: o tipo, a série e o ano, e o texto com os marcadores trocados (limpo). */
    private static function fecho(?array $s, string $nivel, int $max, array $a, array $t, array &$avisos): array
    {
        if (!$s) {
            return ['tipo' => 'declaracao', 'titulo' => 'DECLARAÇÃO', 'texto' => '', 'serie' => '', 'ano' => ''];
        }
        $serie = (int) $s['serie'];
        $ano = (int) $s['ano'];
        if ($s['resultado'] === 'aprovado' && $serie === $max) {
            $tipo = 'certificado';
        } else {
            $tipo = 'declaracao';
            if ($s['resultado'] === 'aprovado') {
                $serie++;
                $ano++;
            } elseif ($s['resultado'] === 'retido') {
                $ano++;
            }
        }
        $etapa = $nivel === 'EF' ? ($serie <= 5 ? 'anos iniciais' : 'anos finais') : '';
        $modelo = (string) ($tipo === 'certificado' ? $t['fecho_certificado'] : $t['fecho_declaracao']);
        if (trim($modelo) === '') {
            $modelo = (string) C::padrao($nivel)[$tipo === 'certificado' ? 'fecho_certificado' : 'fecho_declaracao'];
            $avisos[] = 'O texto do ' . ($tipo === 'certificado' ? 'certificado' : 'da declaração') . ' não está cadastrado: sai o texto de partida.';
        }
        $texto = C::preencher($modelo, ['{escola}' => (string) $t['escola_nome'], '{aluno}' => mb_strtoupper((string) $a['nome']), '{ra}' => (string) $a['ra'], '{rg}' => trim((string) ($a['rg'] ?? '')),
            '{serie}' => B::nomeSerie($nivel, $serie), '{ano}' => (string) $ano, '{etapa}' => $etapa]);
        return ['tipo' => $tipo, 'titulo' => $tipo === 'certificado' ? 'CERTIFICADO' : 'DECLARAÇÃO', 'texto' => $texto, 'serie' => $serie, 'ano' => $ano, 'etapa' => $etapa,
            'nomes' => ['escola' => (string) $t['escola_nome'], 'aluno' => mb_strtoupper((string) $a['nome']), 'ra' => (string) $a['ra'], 'rg' => trim((string) ($a['rg'] ?? ''))]];
    }

    /* ---------------------------------------------------------------- a renderização */

    /** O HTML no degrau dado (0 = o maior). `$verificacao`: ['codigo' => …, 'url' => …] (na prévia, o código "PRÉVIA"). */
    public static function html(array $dados, int $degrau, array $verificacao): string
    {
        [$alt, $pt] = self::DEGRAUS[max(0, min(count(self::DEGRAUS) - 1, $degrau))];
        $qr = $verificacao['url'] !== '' ? \App\adms\Models\historico\AdmsHistoricoDocumento::qr((string) $verificacao['url']) : null;
        ob_start();
        $d = $dados;
        $v = $verificacao + ['qr' => $qr];
        include dirname(__DIR__, 2) . '/Views/historicoEscola/pdf/' . (($dados['nivel'] ?? 'EF') === 'EM' ? 'medio.php' : 'fundamental.php'); // D-116
        return (string) ob_get_clean();
    }

    /**
     * Os degraus: do maior para o menor, renderiza e conta as páginas (com o rodapé); fica o primeiro que dá exatamente
     * duas. @return array{pdf: ?string, html: string, degrau: int, paginas: int}
     */
    public static function renderizar(array $dados, array $verificacao, array $rodape): array
    {
        $ultimo = ['pdf' => null, 'html' => '', 'degrau' => -1, 'paginas' => 0];
        foreach (array_keys(self::DEGRAUS) as $i) {
            $html = self::html($dados, $i, $verificacao);
            $pdf = AdmsHistPdf::gerar($html, 'portrait', $rodape);
            if ($pdf === null) {
                return $ultimo;
            }
            $n = AdmsHistPdf::paginas($pdf);
            $ultimo = ['pdf' => $pdf, 'html' => $html, 'degrau' => $i, 'paginas' => $n, 'largura' => AdmsHistPdf::maiorX()];
            if ($n === 2 && AdmsHistPdf::maiorX() <= self::LIMITE_X) {
                return $ultimo;
            }
        }
        return ['pdf' => null, 'html' => $ultimo['html'], 'degrau' => -1, 'paginas' => $ultimo['paginas'], 'largura' => $ultimo['largura'] ?? 0];
    }
}
