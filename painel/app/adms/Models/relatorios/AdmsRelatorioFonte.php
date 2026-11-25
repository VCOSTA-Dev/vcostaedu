<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 5) . '/core/MotorMedia.php';

use App\adms\Models\notas\AdmsCalculoNotas as Calc;
use Core\MotorMedia as Motor;

/**
 * 1.17.0 (D-456) — A FONTE ÚNICA DOS RELATÓRIOS. Os relatórios não fazem conta de média, resultado, FS/FI, nota mínima
 * nem pendência: leem daqui, e daqui só se chama o que o sistema já usa (a fachada `AdmsCalculoNotas`, o motor, os
 * valores gravados no encerramento e `AdmsVidaAcademica`). O SQL daqui só LISTA (matrículas, ofertas, enturmações).
 *
 * Cada cálculo de oferta é feito uma vez por requisição (cache), para não repetir o motor por aluno (sem N+1).
 * Só leitura: nada aqui grava.
 */
class AdmsRelatorioFonte
{
    private static $parcial = [];
    private static $final = [];

    /** Leitura com parâmetros nomeados (http_build_query: valor com & ou = não quebra o parse do AdmsRead). */
    public static function ler(string $sql, array $p = []): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead($sql, $p ? http_build_query($p) : null);
        return $ler->getResultado() ?: [];
    }

    /**
     * A oferta como as telas a mostram: encerrada = o GRAVADO; aberta = a média PARCIAL (só das notas lançadas), a da
     * tela de médias e do boletim. `AdmsCalculoNotas::oferta($id)`.
     */
    public static function parcial(int $ofertaId): array
    {
        if (self::$coletadas !== null && !isset(self::$parcial[$ofertaId])) {
            self::$coletadas['p:' . $ofertaId] = true; // a seco: anotada, não calculada (vazia, como uma oferta que não existe)
            return [];
        }
        return self::naEtapa(self::$parcial[$ofertaId] ??= self::anualDaEscola($ofertaId, Calc::oferta($ofertaId)));
    }

    /**
     * Onda 8, correção, Parte 3.2 (D-77): na escola, o resultado da oferta encerrada é o RESULTADO ANUAL (as etapas
     * fechadas, a média anual, a frequência e o conselho de classe), que a fachada devolve sem a marca "gravado" do
     * fechamento do ensino superior. Para os relatórios (o painel acadêmico, o resultado por disciplina), o aluno da
     * oferta encerrada com resultado conta como gravado. No ensino superior, nada muda.
     */
    private static function anualDaEscola(int $ofertaId, array $calc): array
    {
        if (empty($calc['oferta']['encerrada_em']) || !Calc::turmaDaOferta($ofertaId)) {
            return $calc;
        }
        foreach ($calc['alunos'] ?? [] as $i => $a) {
            if (empty($a['gravado']) && in_array((int) ($a['resultado'] ?? 0), [Calc::APROVADO, Calc::REPROVADO], true)) {
                $calc['alunos'][$i]['gravado'] = true;
            }
        }
        return $calc;
    }

    /* ---- Onda 6, Etapa 5: a etapa da escola como filtro (D-56) ---- */
    private static ?int $etapa = null;

    /** Os filtros da escola valem para os cálculos lidos daqui (a etapa); a turma vai nas consultas (ofertas, matrículas). */
    public static function filtrosEscola(array $f): void
    {
        self::$etapa = !empty($f['etapa']) ? (int) $f['etapa'] : null;
    }

    /**
     * Com a etapa escolhida, o cálculo de uma oferta da escola é o DA ETAPA: as atividades com data na etapa, a nota de
     * cada aluno é a da etapa (com a recuperação), as notas lançadas são as da etapa e as faltas são as da etapa. A
     * frequência, a situação e o resultado continuam os do ano (o que a escola decide no fim). Fora da escola, ou sem a
     * etapa, o cálculo vem como está.
     */
    private static function naEtapa(array $calc): array
    {
        if (self::$etapa === null || empty($calc['escola']) || empty($calc['oferta']['id'])) {
            return $calc;
        }
        $oid = (int) $calc['oferta']['id'];
        $etapas = Calc::etapasDoAno((int) ($calc['oferta']['ano'] ?? 0));
        $daEtapa = [];
        foreach (self::ler('SELECT id, data_inicial, data_final FROM rm_disciplina_atividade WHERE rm_prof_mat_id = :o AND situacao = 1', ['o' => $oid]) as $a) {
            $e = Calc::etapaDaData($etapas, $a['data_final'] ?: $a['data_inicial']);
            if ($e && (int) $e['id'] === self::$etapa) {
                $daEtapa[(int) $a['id']] = true;
            }
        }
        $faltas = [];
        foreach (self::ler('SELECT rm, faltas FROM esc_faltas_etapa WHERE oferta_id = :o AND etapa_id = :e', ['o' => $oid, 'e' => self::$etapa]) as $f) {
            $faltas[(string) $f['rm']] = (int) $f['faltas'];
        }
        $calc['atividades'] = array_values(array_filter($calc['atividades'] ?? [], fn($a) => isset($daEtapa[(int) $a['id']])));
        foreach ($calc['alunos'] as &$a) {
            $et = null;
            foreach ($a['etapas'] ?? [] as $x) {
                if ((int) $x['id'] === self::$etapa) {
                    $et = $x;
                }
            }
            $a['notas'] = array_intersect_key($a['notas'] ?? [], $daEtapa);
            $a['media'] = $et['nota'] ?? null;
            $a['atividades'] = count($daEtapa);
            $a['lancadas'] = count(array_filter($a['notas'], fn($n) => $n !== null));
            $a['faltas'] = $faltas[(string) $a['rm']] ?? 0;
            $a['compensadas'] = 0;
            $a['etapaFiltro'] = $et;
        }
        unset($a);
        $calc['etapaFiltro'] = self::$etapa;
        return $calc;
    }

    /* ---- Onda 6, Etapa 4: o cálculo em passos (AdmsRelatorioCalculo) ---- */
    private static ?array $coletadas = null;

    /** Roda o relatório a seco e devolve as chaves das ofertas que ele calcularia ("p:<id>" parcial, "v:<id>" provisório). */
    public static function coletar(callable $dados): array
    {
        self::$coletadas = [];
        set_error_handler(fn() => true); // a seco, os cálculos vêm vazios: um aviso do relatório não importa aqui
        try {
            \Core\Transacao::executar(function () use ($dados) {
                $dados();
                return true;
            });
        } catch (\Throwable $e) {
            // o que foi anotado até aqui vale; o resto, a página calcula na hora
        } finally {
            restore_error_handler();
            $k = array_keys(self::$coletadas);
            self::$coletadas = null;
        }
        return $k;
    }

    /** Uma chave de coletar(), calculada como a página do relatório a calcularia. */
    public static function calcular(string $chave): array
    {
        $id = (int) substr($chave, 2);
        return str_starts_with($chave, 'v:') ? self::provisorio($id) : self::parcial($id);
    }

    /** Os cálculos guardados por uma execução em passos: a página usa estes, sem calcular de novo. */
    public static function carregar(array $valores): void
    {
        foreach ($valores as $k => $v) {
            if (str_starts_with((string) $k, 'p:')) {
                self::$parcial[(int) substr($k, 2)] = $v;
            } elseif (str_starts_with((string) $k, 'v:')) {
                self::$final[(int) substr($k, 2)] = $v;
            }
        }
    }

    /**
     * PROVISÓRIO: a oferta ABERTA calculada como no encerramento (sem nota conta como a regra manda), com resultado.
     * `AdmsCalculoNotas::oferta($id, true)`. Encerrada: o gravado (nunca recalculada).
     */
    public static function provisorio(int $ofertaId): array
    {
        if (self::$coletadas !== null && !isset(self::$final[$ofertaId])) {
            self::$coletadas['v:' . $ofertaId] = true;
            return [];
        }
        if (!isset(self::$final[$ofertaId])) {
            $etapa = self::$etapa;
            self::$etapa = null; // o provisório guarda o cálculo do ano; a etapa entra na leitura
            $p = self::parcial($ofertaId);
            self::$etapa = $etapa;
            self::$final[$ofertaId] = !empty($p['oferta']['encerrada_em']) ? $p : Calc::oferta($ofertaId, true);
        }
        return self::naEtapa(self::$final[$ofertaId]);
    }

    /** Um aluno dentro do cálculo da oferta (por RA), ou null. */
    public static function alunoDe(array $calc, string $rm): ?array
    {
        foreach ($calc['alunos'] ?? [] as $a) {
            if ((string) $a['rm'] === $rm) {
                return $a;
            }
        }
        return null;
    }

    /**
     * FS/FI de uma linha: encerrada = `AdmsCalculoNotas::frequenciaSuficiente` (com o mínimo gravado); aberta = o motor
     * com o mínimo efetivo de hoje, marcado provisório. Devolve ['sigla' => 'FS'|'FI'|'', 'provisorio' => bool].
     */
    public static function fsfi(array $aluno, array $calc): array
    {
        if (!empty($aluno['gravado'])) {
            return ['sigla' => Calc::siglaFrequencia($aluno), 'provisorio' => false];
        }
        if (($aluno['frequencia'] ?? null) === null || (int) ($aluno['encontros'] ?? 0) <= 0) {
            return ['sigla' => '', 'provisorio' => false];
        }
        $t = \App\adms\Models\helper\AdmsParametro::textosFrequencia();
        $ok = Motor::frequenciaSuficiente((string) $aluno['frequencia'], (string) ($calc['regras']['frequenciaMinima'] ?? '0'));
        return ['sigla' => $ok ? $t['siglaSuficiente'] : $t['siglaInsuficiente'], 'provisorio' => true];
    }

    /**
     * FALTAS QUE AINDA PODE TER (R-FRE-02): a maior quantidade de faltas efetivas (faltas − compensadas) com que a
     * frequência, arredondada pelo MOTOR (2 casas, meio para cima), ainda fica ≥ a mínima, menos as faltas efetivas de
     * hoje. É o piso(E × (100 − m) ÷ 100) − (f − c) da ficha, com o arredondamento do motor. null sem encontros.
     */
    public static function podeFaltar(int $encontros, string $minima, int $faltas, int $compensadas): ?int
    {
        if ($encontros <= 0) {
            return null;
        }
        $max = -1;
        for ($k = $encontros; $k >= 0; $k--) {
            if (Motor::frequenciaSuficiente(Motor::arredondarFracao(($encontros - $k) * 100, $encontros, 2, 'meio_para_cima'), $minima)) {
                $max = $k;
                break;
            }
        }
        return $max - max(0, $faltas - $compensadas);
    }

    private static $previstos = null;

    /** 1.17.2 (D-462): os ENCONTROS PREVISTOS das ofertas por chamada que os têm: [ofertaId => previstos]. */
    public static function previstos(): array
    {
        if (self::$previstos === null) {
            self::$previstos = [];
            foreach (self::ler("SELECT id, encontros_previstos FROM rm_professores_materias WHERE frequencia_fonte = 'chamada' AND encontros_previstos IS NOT NULL") as $r) {
                self::$previstos[(int) $r['id']] = (int) $r['encontros_previstos'];
            }
        }
        return self::$previstos;
    }

    /**
     * 1.17.2 (D-462): as faltas que ainda pode ter NA OFERTA, para os relatórios e o alerta de risco (só eles). Oferta por
     * chamada com encontros previstos: piso(previstos × (100 − m) ÷ 100) − (faltas − compensadas), sobre os PREVISTOS.
     * Sem previstos: a de sempre, sobre os encontros registrados (`podeFaltar`). A frequência oficial não usa isto.
     * Devolve ['pode' => ?int, 'base' => int (encontros da conta), 'previstos' => bool].
     */
    public static function margemOferta(int $ofertaId, ?int $encontros, string $minima, int $faltas, int $compensadas): array
    {
        $p = self::previstos()[$ofertaId] ?? null;
        if ($p !== null) {
            return ['pode' => intdiv($p * (10000 - Motor::centesimosDe($minima)), 10000) - max(0, $faltas - $compensadas), 'base' => $p, 'previstos' => true];
        }
        return ['pode' => self::podeFaltar((int) $encontros, $minima, $faltas, $compensadas), 'base' => (int) $encontros, 'previstos' => false];
    }

    /**
     * A CLASSIFICAÇÃO da frequência, a mesma no R-FRE-02 e no sinal de frequência do alerta de risco: abaixo | limite |
     * perto | '' (sem alerta). 1.17.3 (D-466): dispara se ABAIXO do mínimo (pode < 0) OU (faltas não compensadas ≥ 1 E
     * pode ≤ margem) — sem nenhuma falta efetiva, o aluno não entra (em disciplina curta, "pode ter mais 2" ≤ margem 2
     * acusaria todos). Vale com e sem encontros previstos.
     */
    public static function situacaoFalta(?int $pode, int $margem, int $faltasEfetivas): string
    {
        if ($pode === null) {
            return '';
        }
        if ($pode < 0) {
            return 'abaixo';
        }
        if ($faltasEfetivas < 1 || $pode > $margem) {
            return '';
        }
        return $pode === 0 ? 'limite' : 'perto';
    }

    /** Os parâmetros do alerta de risco (adms_parametros risco.*). */
    public static function risco(): array
    {
        $p = \App\adms\Models\helper\AdmsParametro::class;
        return [
            'faltas_margem' => max(0, (int) $p::ler('risco.faltas_margem', '2')),
            'min_notas' => max(1, (int) $p::ler('risco.min_notas', '1')),
            'sem_nota_pct' => min(100, max(1, (int) $p::ler('risco.sem_nota_pct', '50'))),
        ];
    }

    /**
     * Ofertas, com o curso, a grade e o rótulo. Filtros: curso, grade, oferta, situacao_oferta (aberta|encerrada),
     * de/ate (sobre `encerrada_em`, só se `$porEncerramento`). O professor só vê as dele (rm_professores_mat_liga).
     */
    public static function ofertas(array $f, bool $porEncerramento = false): array
    {
        $w = ['1 = 1'];
        $p = [];
        if (!empty($f['curso'])) {
            $w[] = 'o.curso_id = :curso';
            $p['curso'] = (int) $f['curso'];
        }
        if (!empty($f['grade'])) {
            $w[] = 'l.grade_id = :grade';
            $p['grade'] = (int) $f['grade'];
        }
        if (!empty($f['oferta'])) {
            $w[] = 'o.id = :oferta';
            $p['oferta'] = (int) $f['oferta'];
        }
        if (!empty($f['turma'])) { // Onda 6, Etapa 5: a turma da escola
            $w[] = 'o.id IN (SELECT tx.oferta_id FROM esc_turma_ofertas tx WHERE tx.turma_id = :turma)';
            $p['turma'] = (int) $f['turma'];
        }
        if (($f['situacao_oferta'] ?? '') === 'aberta') {
            $w[] = 'o.encerrada_em IS NULL';
        } elseif (($f['situacao_oferta'] ?? '') === 'encerrada') {
            $w[] = 'o.encerrada_em IS NOT NULL';
        }
        if ($porEncerramento) {
            $w[] = 'o.encerrada_em IS NOT NULL';
            if (!empty($f['de'])) {
                $w[] = 'o.encerrada_em >= :de';
                $p['de'] = $f['de'] . ' 00:00:00';
            }
            if (!empty($f['ate'])) {
                $w[] = 'o.encerrada_em <= :ate';
                $p['ate'] = $f['ate'] . ' 23:59:59';
            }
        }
        if (AdmsRelatorios::ehProfessor()) {
            $w[] = 'o.id IN (SELECT lg.rm_prof_mat_id FROM rm_professores_mat_liga lg WHERE lg.usuario_id = :prof AND lg.situacao = 1)';
            $p['prof'] = (int) ($_SESSION['colUsuario_id'] ?? 0);
        }
        /* Onda 2, Etapa 2: a Coordenação de curso só vê os cursos que coordena (AdmsEscopo, a função única) */
        if (($recorte = \App\adms\Models\helper\AdmsEscopo::sql('o.curso_id')) !== '') {
            $w[] = substr($recorte, 5);
        }
        return self::ler(
            "SELECT o.id, o.materia, o.codmat, o.codtur, o.ano, o.curso_id, o.encontros, o.frequencia_fonte, o.encerrada_em, o.data_inicio, o.data_fim,
                    o.matriz_id, l.grade_id, c.nome AS curso, g.versao AS grade
               FROM rm_professores_materias o
               LEFT JOIN rm_cursos c ON c.id = o.curso_id
               LEFT JOIN uni_rh_cursos_disciplinas_grade l ON l.id = o.matriz_id
               LEFT JOIN rm_cursos_grades g ON g.id = l.grade_id
              WHERE " . implode(' AND ', $w) . "
              ORDER BY o.ano DESC, o.materia, o.codtur, o.id",
            $p
        );
    }

    /** "Matéria — TURMA ANO" */
    public static function rotuloOferta(array $o): string
    {
        return \App\adms\Models\notas\AdmsRegraMedia::rotuloOferta($o);
    }

    /**
     * Matrículas (uma linha por matrícula), com o aluno, o curso, a grade, o polo (o da matrícula; vazio = o do curso,
     * D-115) e a situação. Filtros: curso, grade, situacao, polo, coorte (AAAA/1|2 de data_ingresso), de/ate (data de
     * ingresso), matricula. `$extra`: condições a mais (SQL fixo do relatório).
     */
    public static function matriculas(array $f, array $extra = [], array $pExtra = []): array
    {
        $w = array_merge(['1 = 1'], $extra);
        $p = $pExtra;
        foreach (['curso' => 'mc.curso_id', 'grade' => 'mc.grade_id', 'situacao' => 'mc.situacao_id', 'matricula' => 'mc.id'] as $k => $col) {
            if (!empty($f[$k])) {
                $w[] = "{$col} = :{$k}";
                $p[$k] = (int) $f[$k];
            }
        }
        /* Onda 2, Etapa 2: o recorte do coordenador (AdmsEscopo) */
        if (($recorte = \App\adms\Models\helper\AdmsEscopo::sql('mc.curso_id')) !== '') {
            $w[] = substr($recorte, 5);
        }
        if (!empty($f['turma'])) { // Onda 6, Etapa 5: os alunos que estão na turma da escola
            $w[] = 'mc.usuario_id IN (SELECT ta.usuario_id FROM esc_turma_alunos ta WHERE ta.turma_id = :turma AND ta.saida IS NULL)';
            $p['turma'] = (int) $f['turma'];
        }
        if (!empty($f['polo'])) {
            $w[] = 'COALESCE(mc.polo_id, c.localidade) = :polo';
            $p['polo'] = (int) $f['polo'];
        }
        if (!empty($f['coorte']) && preg_match('/^(\d{4})\/([12])$/', (string) $f['coorte'], $m)) {
            $w[] = 'mc.data_ingresso BETWEEN :cIni AND :cFim';
            $p['cIni'] = $m[1] . ($m[2] === '1' ? '-01-01' : '-07-01');
            $p['cFim'] = $m[1] . ($m[2] === '1' ? '-06-30' : '-12-31');
        }
        if (!empty($f['de'])) {
            $w[] = 'mc.data_ingresso >= :de';
            $p['de'] = $f['de'];
        }
        if (!empty($f['ate'])) {
            $w[] = 'mc.data_ingresso <= :ate';
            $p['ate'] = $f['ate'];
        }
        return self::ler(
            "SELECT mc.id, mc.usuario_id, mc.curso_id, mc.grade_id, mc.situacao_id, mc.data_ingresso, mc.previsao_conclusao, mc.data_conclusao,
                    mc.polo_id, COALESCE(mc.polo_id, c.localidade) AS polo_efetivo,
                    u.rm, u.nome, u.genero_id, u.genero_confirmado_em, i.cpf, i.nasc_data, i.nasc_local, i.nasc_nac, i.rg, i.nome_mae, i.nome_pai,
                    c.nome AS curso, c.exige_tcc, g.versao AS grade, s.nome AS situacao, po.nome AS polo
               FROM rm_matriculas_curso mc
               INNER JOIN col_usuarios u ON u.id = mc.usuario_id
               LEFT JOIN col_usuarios_informacoes i ON i.col_user_id = u.id
               INNER JOIN rm_cursos c ON c.id = mc.curso_id
               LEFT JOIN rm_cursos_grades g ON g.id = mc.grade_id
               LEFT JOIN rm_matriculas_situacao s ON s.id = mc.situacao_id
               LEFT JOIN rm_polos po ON po.id = COALESCE(mc.polo_id, c.localidade)
              WHERE " . implode(' AND ', $w) . "
              ORDER BY u.nome, c.nome, mc.id",
            $p
        );
    }

    /** Enturmações ativas em ofertas ABERTAS, por RA e curso: [rm][curso_id] => [ofertaId, …]. */
    public static function enturmacoesAbertas(): array
    {
        $por = [];
        foreach (self::ler(
            "SELECT e.rm, o.curso_id, o.id FROM portal_disciplinas_alunos e INNER JOIN rm_professores_materias o ON o.id = e.rm_prof_mat_id
              WHERE e.situacao = 1 AND o.encerrada_em IS NULL ORDER BY o.id"
        ) as $r) {
            $por[(string) $r['rm']][(int) $r['curso_id']][] = (int) $r['id'];
        }
        return $por;
    }

    /**
     * Reprovações GRAVADAS (fechamento vigente) por RA e curso: [rm][curso_id] => [['materia', 'turma', 'ano', 'motivo'], …].
     */
    public static function reprovacoes(): array
    {
        $por = [];
        foreach (self::ler(
            "SELECT f.rm, o.curso_id, o.materia, o.codtur, o.ano, f.motivo FROM rm_disciplina_media_final f
               INNER JOIN rm_professores_materias o ON o.id = f.rm_prof_mat_id
              WHERE f.resultado = 2 AND o.encerrada_em IS NOT NULL ORDER BY o.ano, o.materia"
        ) as $r) {
            $por[(string) $r['rm']][(int) $r['curso_id']][] = $r;
        }
        return $por;
    }

    /**
     * R-EVA-01 (D-458): os SINAIS de risco de cada matrícula ATIVA (Cursando) do filtro. Sem pontuação: cada sinal tem o
     * porquê. Devolve [mcId => ['mc' => linha, 'sinais' => [chave => [porquês]]]] só das que têm sinal.
     */
    public static function sinaisRisco(array $f): array
    {
        $par = self::risco();
        $ent = self::enturmacoesAbertas();
        $rep = self::reprovacoes();
        $saida = [];
        foreach (self::matriculas(['situacao' => 1] + $f) as $mc) {
            $rm = (string) $mc['rm'];
            $curso = (int) $mc['curso_id'];
            $s = [];
            $ofertas = $ent[$rm][$curso] ?? [];
            if (!$ofertas) {
                $s['sem_enturmacao'][] = 'nenhuma enturmação ativa em oferta aberta do curso';
            }
            $semNota = 0;
            $totalAtiv = 0;
            foreach ($ofertas as $oid) {
                $calc = self::parcial($oid);
                $a = self::alunoDe($calc, $rm);
                if (!$a) {
                    continue;
                }
                $rot = self::rotuloOferta($calc['oferta']);
                $pode = self::margemOferta($oid, isset($calc['oferta']['encontros']) ? (int) $calc['oferta']['encontros'] : null, (string) $calc['regras']['frequenciaMinima'], (int) $a['faltas'], (int) $a['compensadas'])['pode']; // 1.17.2 (D-462): com previstos, sobre eles
                if (self::situacaoFalta($pode, $par['faltas_margem'], max(0, (int) $a['faltas'] - (int) $a['compensadas'])) !== '') { // 1.17.3 (D-466): a mesma classificação do R-FRE-02
                    $s['frequencia'][] = "{$rot}: " . ($pode < 0 ? 'já abaixo do mínimo (' . abs($pode) . ' falta(s) além)' : "pode ter só mais {$pode} falta(s)");
                }
                $min = (string) $calc['regras']['mediaMinima'];
                if ($a['media'] !== null && (int) $a['lancadas'] >= $par['min_notas'] && Motor::centesimosDe((string) $a['media']) < Motor::centesimosDe($min)) {
                    $s['media'][] = "{$rot}: média parcial " . Calc::formatar((string) $a['media'], (int) $a['casas']) . ' < ' . Calc::formatar($min, (int) $a['casas']) . " ({$a['lancadas']} nota(s) lançada(s))";
                }
                $semNota += max(0, (int) $a['atividades'] - (int) $a['lancadas']);
                $totalAtiv += (int) $a['atividades'];
            }
            if ($totalAtiv > 0 && $semNota * 100 >= $par['sem_nota_pct'] * $totalAtiv) {
                $s['sem_nota'][] = "{$semNota} de {$totalAtiv} atividade(s) sem nota nas ofertas abertas (" . self::pct($semNota, $totalAtiv) . ')';
            }
            foreach ($rep[$rm][$curso] ?? [] as $r) {
                $s['reprovacao'][] = "{$r['materia']} ({$r['codtur']}/{$r['ano']}): " . Calc::rotuloResultado(2, $r['motivo']);
            }
            if ($s) {
                $saida[(int) $mc['id']] = ['mc' => $mc, 'sinais' => $s];
            }
        }
        return $saida;
    }

    /** Os alunos (col_usuarios) por RA: [rm => [usuario_id, nome, cpf]] — para os links e o CPF das listas por oferta. */
    public static function alunosPorRm(array $rms): array
    {
        $rms = array_values(array_unique(array_filter(array_map('strval', $rms), fn($r) => $r !== '')));
        if (!$rms) {
            return [];
        }
        $p = [];
        foreach ($rms as $i => $r) {
            $p["r{$i}"] = $r;
        }
        $por = [];
        foreach (self::ler("SELECT u.id AS usuario_id, u.rm, u.nome, i.cpf FROM col_usuarios u LEFT JOIN col_usuarios_informacoes i ON i.col_user_id = u.id
                             WHERE u.rm IN (:" . implode(', :', array_keys($p)) . ")", $p) as $u) {
            $por[(string) $u['rm']] = $u;
        }
        return $por;
    }

    /**
     * Os RESULTADOS GRAVADOS (fechamento vigente) das ofertas encerradas: um item por aluno × oferta, lido pela fachada
     * (`parcial()` devolve o gravado da encerrada). Só as linhas `gravado`. [[oferta => linha, aluno => linha do cálculo]].
     */
    public static function resultadosGravados(array $ofertas): array
    {
        $saida = [];
        foreach ($ofertas as $o) {
            if (empty($o['encerrada_em'])) {
                continue;
            }
            foreach (self::parcial((int) $o['id'])['alunos'] ?? [] as $a) {
                if (!empty($a['gravado'])) {
                    $saida[] = ['oferta' => $o, 'aluno' => $a];
                }
            }
        }
        return $saida;
    }

    public const SINAIS = [
        'frequencia' => 'Frequência perto do mínimo', 'media' => 'Média parcial abaixo da mínima', 'sem_nota' => 'Atividades sem nota',
        'reprovacao' => 'Reprovação anterior', 'sem_enturmacao' => 'Sem enturmação',
    ];

    /** "40,0%" (1 casa) — só para exibir; a comparação é feita em inteiros. */
    public static function pct(int $n, int $d, int $casas = 1): string
    {
        return $d > 0 ? number_format($n * 100 / $d, $casas, ',', '.') . '%' : '—';
    }

    public static function data(?string $d): string
    {
        return $d ? date('d/m/Y', strtotime($d)) : '';
    }
}
