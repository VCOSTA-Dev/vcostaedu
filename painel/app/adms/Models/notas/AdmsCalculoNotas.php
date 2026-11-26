<?php

namespace App\adms\Models\notas;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 5) . '/core/MotorMedia.php'; // E3 (D-391): o motor, num lugar só (painel e portal)
require_once dirname(__DIR__, 5) . '/core/MediaEscola.php'; // Onda 4, Etapa 3: a média da escola em dois níveis (por cima do motor)

use Core\MotorMedia as Motor;

/**
 * =============================================================================
 * MOTOR DE NOTAS, FREQUÊNCIA E RESULTADO (E4) — a FACHADA do cálculo.
 *
 * 1.13.0 (E3, D-391): a conta passou para o motor PURO `core/MotorMedia.php` (um arquivo, painel e portal). Esta
 * classe lê o banco (atividades, notas, faltas, a regra efetiva da oferta em AdmsRegraMedia) e chama o motor; as
 * funções públicas e o retorno são os de antes (+ `memoria`, `casas` e `regraEfetiva`). Com a regra padrão
 * (um componente aritmético), a conta é a de sempre — régua de ouro, 0 diferença.
 * =============================================================================
 *
 * Usado pelo boletim do painel, pelo PDF, pelo portal, pela tela de notas da
 * oferta e pelo encerramento. Arquivo IDÊNTICO no painel e no portal.
 *
 * Regras (D-120 a D-127):
 * - Etapa única por oferta.
 * - Média = média aritmética simples das atividades da oferta.
 *   - Oferta ABERTA: média PARCIAL, só das atividades com nota lançada.
 *   - ENCERRAMENTO: atividade sem nota conta 0; o denominador é o total de
 *     atividades da oferta.
 * - Arredondamento só na média, meio para cima, com 1 ou 2 casas (parâmetro).
 *   As notas das atividades ficam como lançadas.
 * - Frequência = 1 − (faltas − compensadas) ÷ encontros, em %, com 2 casas.
 *   Sem total de encontros: "não informada".
 * - Aprovado se média ≥ mínimo E frequência ≥ mínima (valores arredondados, os
 *   mesmos que a tela mostra); senão Reprovado, com o motivo.
 * - Oferta encerrada: vale o que foi GRAVADO no encerramento
 *   (rm_disciplina_media_final), não o recálculo.
 *
 * E2 (D-360, D-361): a frequência mínima pode ser do CURSO (rm_cursos.frequencia_minima; vazio = a da instituição),
 * e o encerramento grava a EFETIVA. FS/FI é decidido SÓ aqui (frequenciaSuficiente / siglaFrequencia): com o mínimo
 * GRAVADO no fechamento, nunca com o parâmetro de hoje; oferta aberta ou sem encontros = em branco.
 *
 * Toda a conta é feita em INTEIROS (centésimos), para que 6,95 vire 7,0 e não 6,9
 * por erro de ponto flutuante.
 * =============================================================================
 */
class AdmsCalculoNotas
{
    public const APROVADO = 1;
    public const REPROVADO = 2;

    /**
     * Regras vigentes: casas (1|2), mediaMinima e frequenciaMinima (strings com ponto). E2 (D-360): com o curso, a
     * frequência mínima do CURSO, se ele tiver uma; `origemFrequencia` diz de onde veio (curso | instituicao).
     */
    public static function regras(?int $cursoId = null): array
    {
        $r = \App\adms\Models\helper\AdmsParametro::regrasNotas() + ['origemFrequencia' => 'instituicao'];
        /* E3 (D-393): casas e nota mínima da REGRA de média que vale (a do curso, se tiver; senão a da instituição) */
        self::carregarRegra();
        $ef = $cursoId ? AdmsRegraMedia::efetivaCurso($cursoId) : AdmsRegraMedia::instituicao();
        $r['casas'] = (int) $ef['regra']['casas'];
        $r['mediaMinima'] = (string) $ef['regra']['notaMinima'];
        if ($cursoId) {
            static $porCurso = [];
            if (!array_key_exists($cursoId, $porCurso)) {
                $ler = new \App\adms\Models\helper\AdmsRead();
                $ler->fullRead("SELECT frequencia_minima FROM rm_cursos WHERE id = :c LIMIT :limit", "c={$cursoId}&limit=1");
                $porCurso[$cursoId] = $ler->getResultado()[0]['frequencia_minima'] ?? null;
            }
            $f = $porCurso[$cursoId];
            if ($f !== null && $f !== '' && (float) $f >= 0 && (float) $f <= 100) {
                $r['frequenciaMinima'] = number_format((float) $f, 2, '.', '');
                $r['origemFrequencia'] = 'curso';
            }
        }
        return $r;
    }

    /**
     * E2 (D-361): FREQUÊNCIA SUFICIENTE? true (FS), false (FI) ou null (em branco). Só com a oferta ENCERRADA e o
     * resultado GRAVADO (`gravado`, `frequencia` e `frequenciaMinima` de rm_disciplina_media_final): o mínimo que vale
     * é o do fechamento, não o parâmetro de hoje. Compara os valores já arredondados (D-137), em centésimos.
     */
    public static function frequenciaSuficiente(array $d): ?bool
    {
        if (empty($d['gravado']) || ($d['frequencia'] ?? null) === null || ($d['frequenciaMinima'] ?? null) === null || (int) ($d['encontros'] ?? 0) <= 0) {
            return null;
        }
        return Motor::frequenciaSuficiente((string) $d['frequencia'], (string) $d['frequenciaMinima']); // E3: a MESMA comparação do motor
    }

    /** E2 (D-361): a sigla (FS/FI, dos parâmetros) ou '' — o único lugar que escreve a sigla. */
    public static function siglaFrequencia(array $d): string
    {
        $s = self::frequenciaSuficiente($d);
        if ($s === null) {
            return '';
        }
        $t = \App\adms\Models\helper\AdmsParametro::textosFrequencia();
        return $s ? $t['siglaSuficiente'] : $t['siglaInsuficiente'];
    }

    /**
     * Nota lançada (texto do banco ou digitado, com vírgula ou ponto) em centésimos.
     * Aceita 0 a 10 com até 2 casas; qualquer outra coisa → null.
     */
    public static function centesimos($nota): ?int
    {
        if ($nota === null) {
            return null;
        }
        $v = str_replace(',', '.', trim((string) $nota));
        if (!preg_match('/^(\d{1,2})(?:\.(\d{1,2}))?$/', $v, $m)) {
            return null;
        }
        $c = (int) $m[1] * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
        return $c <= 1000 ? $c : null;
    }

    /** num/den (≥ 0) arredondado meio para cima, com `$casas` casas, como texto com ponto. */
    public static function arredondar(int $num, int $den, int $casas): string
    {
        return Motor::arredondarFracao($num, $den, $casas, 'meio_para_cima'); // E3: o arredondamento do motor
    }

    /** Parâmetro ("7.00", "75.50") para leitura: sem zeros sobrando, mínimo de `$minCasas` casas ("7,0", "75,5", "75"). */
    public static function numero(string $v, int $minCasas = 0): string
    {
        [$i, $d] = array_pad(explode('.', number_format((float) $v, 2, '.', '')), 2, '');
        $d = rtrim($d, '0');
        $d = str_pad($d, $minCasas, '0');
        return $d === '' ? $i : $i . ',' . $d;
    }

    /** "8.20" → "8,2" / "8,20" conforme as casas; null → "—". */
    public static function formatar(?string $valor, int $casas): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }
        return number_format((float) $valor, $casas, ',', '.');
    }

    /**
     * O cálculo, puro (fachada do motor). `$notas`: centésimos (int) ou null por atividade; `$totalAtividades` é o
     * total delas. Com `$regras['regraMotor']` (formato do motor), usa essa regra; senão, a padrão de hoje (um
     * componente aritmético, com as casas e a média mínima de `$regras`). Devolve média, frequência, resultado, a conta
     * e a memória de cálculo.
     */
    public static function calcular(array $notas, int $totalAtividades, ?int $encontros, int $faltas, int $compensadas, bool $final, array $regras): array
    {
        $atividades = [];
        foreach (array_values($notas) as $i => $n) {
            $atividades[] = is_array($n) ? $n : ['id' => $i + 1, 'nome' => 'Atividade ' . ($i + 1), 'nota' => $n, 'peso' => '10', 'componente' => null];
        }
        for ($i = count($atividades); $i < $totalAtividades; $i++) {
            $atividades[] = ['id' => $i + 1, 'nome' => 'Atividade ' . ($i + 1), 'nota' => null, 'peso' => '10', 'componente' => null];
        }
        $regra = $regras['regraMotor'] ?? Motor::regraPadrao((int) $regras['casas'], (string) $regras['mediaMinima']);
        $r = Motor::calcular($atividades, $regra, ['final' => $final, 'encontros' => $encontros, 'faltas' => $faltas, 'compensadas' => $compensadas,
            'frequenciaMinima' => (string) $regras['frequenciaMinima']]);
        $r['casas'] = (int) $regra['casas'];
        return $r;
    }

    /** O portal lê a regra pela ponte (a classe é do painel; não há cópia). */
    private static function carregarRegra(): void
    {
        if (!class_exists(AdmsRegraMedia::class) && class_exists(\App\adms\Models\helper\AdmsPainelPonte::class)) {
            \App\adms\Models\helper\AdmsPainelPonte::carregar();
        }
    }

    /** As atividades da oferta no formato do motor, com as notas de um aluno. */
    private static function paraMotor(array $atividades, array $notasDoAluno): array
    {
        $l = [];
        foreach ($atividades as $a) {
            $id = (int) $a['id'];
            $l[] = ['id' => $id, 'nome' => (string) $a['nomeAtividade'], 'nota' => $notasDoAluno[$id] ?? null,
                'peso' => AdmsRegraMedia::pesoTexto((string) ($a['peso'] ?? '10')), 'componente' => $a['componente_codigo'] ?? null]
                + ((int) ($a['tipo'] ?? 0) === 6 ? ['acrescimo' => true] : []); // Onda 4, Etapa 5: a atividade de acréscimo (fora dos componentes)
        }
        return $l;
    }

    /** As regras de leitura da oferta (casas, mínimos) a partir da regra efetiva dela. */
    private static function regrasDaOferta(array $oferta, array $ef): array
    {
        $r = self::regras((int) $oferta['curso_id']); // E2 (D-360): a frequência mínima do curso, se houver
        $r['casas'] = (int) $ef['regra']['casas'];
        $r['mediaMinima'] = (string) $ef['regra']['notaMinima'];
        $r['regraMotor'] = $ef['regra'];
        $r['origemRegra'] = $ef['origem'];
        $r['regraVersaoId'] = $ef['versaoId'];
        $r['regraVersao'] = $ef['versao'];
        return $r;
    }

    private static function ctx(array $oferta, int $f, int $c, bool $final, array $regras): array
    {
        return ['final' => $final, 'encontros' => $oferta['encontros'] !== null ? (int) $oferta['encontros'] : null, 'faltas' => $f, 'compensadas' => $c,
            'frequenciaMinima' => (string) $regras['frequenciaMinima']];
    }

    /** "7.00" / "75.5" → centésimos (para comparar sem ponto flutuante). */
    private static function centesimosDe(string $v): int
    {
        return Motor::centesimosDe($v);
    }

    /** E10 (D-422) / 1.15.2 (D-431): `$pendencia` = os motivos do motor (`motivosPendencia`) → "Pendente — falta Prova". */
    public static function rotuloResultado(?int $resultado, ?string $motivo, array $pendencia = []): string
    {
        if ($pendencia) {
            return 'Pendente — ' . implode('; ', $pendencia);
        }
        if ($resultado === self::APROVADO) {
            return $motivo === 'conselho' ? 'Aprovado pelo conselho' : 'Aprovado'; // Onda 4, Etapa 3
        }
        if ($resultado === self::REPROVADO) {
            return trim('Reprovado ' . (['nota' => 'por nota', 'frequencia' => 'por frequência', 'nota_frequencia' => 'por nota e frequência'][(string) $motivo] ?? ''));
        }
        return 'Em curso';
    }

    /**
     * TRAVA da oferta encerrada (E4): nota, falta e atividade não mudam. Devolve true
     * (e deixa a recusa em $_SESSION['msg']) se a oferta está encerrada.
     */
    public static function travaEncerrada(int $ofertaId): bool
    {
        $o = $ofertaId > 0 ? self::dadosOferta($ofertaId) : null;
        if ($o && !empty($o['encerrada_em'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
                . "Erro: a oferta está ENCERRADA — notas, faltas e atividades não podem mudar. Só o administrador reabre, com observação.</div>";
            return true;
        }
        return false;
    }

    public static function ofertaDaAtividade(int $atividadeId): int
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT rm_prof_mat_id FROM rm_disciplina_atividade WHERE id = :id LIMIT :limit", "id={$atividadeId}&limit=1");
        return (int) ($ler->getResultado()[0]['rm_prof_mat_id'] ?? 0);
    }

    public static function ofertaDaNota(int $notaId): int
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT rm_prof_mat_id FROM rm_disciplina_atividade_notas WHERE id = :id LIMIT :limit", "id={$notaId}&limit=1");
        return (int) ($ler->getResultado()[0]['rm_prof_mat_id'] ?? 0);
    }

    /** Dados da oferta usados no cálculo. */
    public static function dadosOferta(int $ofertaId): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT o.id, o.materia, o.codtur, o.ano, o.curso_id, o.encontros, o.frequencia_fonte, o.encerrada_em, o.encerrada_por, o.matriz_id,
                    COALESCE(l.cargahoraria, o.ch_considerada) AS ch, l.modulo_id, m.codnome AS modulo, m.ordem AS ordemModulo
               FROM rm_professores_materias o
               LEFT JOIN uni_rh_cursos_disciplinas_grade l ON l.id = o.matriz_id
               LEFT JOIN rm_cursos_turma_series m ON m.id = l.modulo_id
              WHERE o.id = :id LIMIT :limit",
            "id={$ofertaId}&limit=1"
        );
        return $ler->getResultado()[0] ?? null;
    }

    /** Ids das atividades ativas da oferta (o denominador). */
    public static function atividades(int $ofertaId): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nomeAtividade, peso, componente_codigo, tipo FROM rm_disciplina_atividade WHERE rm_prof_mat_id = :o AND situacao = :s ORDER BY data_inicial, id", "o={$ofertaId}&s=1"); // E3: + peso e componente; Onda 4: + o tipo (o acréscimo)
        return $ler->getResultado() ?: [];
    }

    /**
     * A oferta inteira: um item por aluno enturmado, com as notas por atividade, a média
     * (parcial, ou final se `$final`), a frequência e — no modo final — o resultado.
     * Se a oferta está encerrada e `$final` é false, devolve o que foi GRAVADO.
     */
    public static function oferta(int $ofertaId, bool $final = false): array
    {
        $oferta = self::dadosOferta($ofertaId);
        if (!$oferta) {
            return [];
        }
        self::carregarRegra();
        $ef = AdmsRegraMedia::efetivaOferta($ofertaId); // E3 (D-393): oferta → curso → instituição
        $regras = self::regrasDaOferta($oferta, $ef);
        /* Onda 4, Etapa 3: a oferta de uma turma da escola — os dois níveis (\Core\MediaEscola), no mesmo formato */
        if ($turmaEscola = self::turmaDaOferta($ofertaId)) {
            $conf = self::confEscola();
            $regras['mediaMinima'] = $conf['mediaMinima'];
            $regras['frequenciaMinima'] = $conf['frequenciaMinima'];
            return ['oferta' => $oferta, 'atividades' => self::atividades($ofertaId), 'regras' => $regras, 'alunos' => self::escola($oferta, $turmaEscola, $final, null, $ef), 'escola' => true,
                'regraEfetiva' => ['origem' => $ef['origem'], 'versaoId' => $ef['versaoId'], 'versao' => $ef['versao'], 'regraId' => $ef['regraId'], 'regra' => $ef['regra'], 'nome' => (string) ($ef['nome'] ?? '')]];
        }
        $atividades = self::atividades($ofertaId);
        $idsAtiv = array_map('intval', array_column($atividades, 'id'));

        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT rm, nome_aluno FROM portal_disciplinas_alunos WHERE rm_prof_mat_id = :o AND situacao = :s ORDER BY nome_aluno", "o={$ofertaId}&s=1");
        $alunos = $ler->getResultado() ?: [];

        $notas = [];
        if ($idsAtiv) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT rm, atividade_id, nota FROM rm_disciplina_atividade_notas WHERE rm_prof_mat_id = :o AND atividade_id IN (" . implode(',', $idsAtiv) . ")", "o={$ofertaId}");
            foreach ($ler->getResultado() ?: [] as $n) {
                $notas[(string) $n['rm']][(int) $n['atividade_id']] = self::centesimos($n['nota']);
            }
        }
        $faltas = [];
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT rm, faltas, faltas_compensadas FROM rm_disciplina_faltas WHERE rm_prof_mat_id = :o", "o={$ofertaId}");
        foreach ($ler->getResultado() ?: [] as $f) {
            $faltas[(string) $f['rm']] = [(int) $f['faltas'], (int) $f['faltas_compensadas']];
        }
        $gravados = [];
        if (!empty($oferta['encerrada_em']) && !$final) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT * FROM rm_disciplina_media_final WHERE rm_prof_mat_id = :o", "o={$ofertaId}");
            foreach ($ler->getResultado() ?: [] as $g) {
                $gravados[(string) $g['rm']] = $g;
            }
        }

        $saida = [];
        foreach ($alunos as $a) {
            $rm = (string) $a['rm'];
            $porAtividade = [];
            foreach ($idsAtiv as $id) {
                $porAtividade[$id] = $notas[$rm][$id] ?? null;
            }
            [$f, $c] = $faltas[$rm] ?? [0, 0];
            if (isset($gravados[$rm])) {
                $calc = self::deGravado($gravados[$rm]);
            } else {
                $calc = Motor::calcular(self::paraMotor($atividades, $porAtividade), $ef['regra'], self::ctx($oferta, $f, $c, $final, $regras)) + ['casas' => (int) $ef['regra']['casas']];
            }
            $saida[] = ['rm' => $rm, 'nome' => $a['nome_aluno'], 'notas' => $porAtividade] + $calc;
        }
        return ['oferta' => $oferta, 'atividades' => $atividades, 'regras' => $regras, 'alunos' => $saida,
            'regraEfetiva' => ['origem' => $ef['origem'], 'versaoId' => $ef['versaoId'], 'versao' => $ef['versao'], 'regraId' => $ef['regraId'], 'regra' => $ef['regra'], 'nome' => (string) ($ef['nome'] ?? '')]];
    }

    /** Um aluno numa oferta (boletim, portal, PDF): gravado se encerrada; senão parcial. */
    public static function alunoNaOferta(int $ofertaId, string $rm): ?array
    {
        $oferta = self::dadosOferta($ofertaId);
        if (!$oferta) {
            return null;
        }
        /* Onda 4, Etapa 3: a escola — o mesmo cálculo da tela das etapas (um aluno só) */
        if ($turmaEscola = self::turmaDaOferta($ofertaId)) {
            self::carregarRegra();
            $linha = self::escola($oferta, $turmaEscola, false, $rm, AdmsRegraMedia::efetivaOferta($ofertaId))[0] ?? null;
            return $linha ? ['oferta' => $oferta, 'frequenciaMinima' => self::confEscola()['frequenciaMinima']] + $linha : null;
        }
        if (!empty($oferta['encerrada_em'])) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT * FROM rm_disciplina_media_final WHERE rm_prof_mat_id = :o AND rm = :rm LIMIT :limit", "o={$ofertaId}&rm={$rm}&limit=1");
            if ($g = $ler->getResultado()[0] ?? null) {
                return ['oferta' => $oferta] + self::deGravado($g);
            }
        }
        $atividades = self::atividades($ofertaId);
        $idsAtiv = array_map('intval', array_column($atividades, 'id'));
        $porAtividade = [];
        if ($idsAtiv) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT atividade_id, nota FROM rm_disciplina_atividade_notas WHERE rm_prof_mat_id = :o AND rm = :rm AND atividade_id IN (" . implode(',', $idsAtiv) . ")", "o={$ofertaId}&rm={$rm}");
            foreach ($ler->getResultado() ?: [] as $n) {
                $porAtividade[(int) $n['atividade_id']] = self::centesimos($n['nota']);
            }
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT faltas, faltas_compensadas FROM rm_disciplina_faltas WHERE rm_prof_mat_id = :o AND rm = :rm LIMIT :limit", "o={$ofertaId}&rm={$rm}&limit=1");
        $f = $ler->getResultado()[0] ?? ['faltas' => 0, 'faltas_compensadas' => 0];
        self::carregarRegra();
        $ef = AdmsRegraMedia::efetivaOferta($ofertaId);
        $regras = self::regrasDaOferta($oferta, $ef);
        return ['oferta' => $oferta] + Motor::calcular(self::paraMotor($atividades, $porAtividade), $ef['regra'],
            self::ctx($oferta, (int) $f['faltas'], (int) $f['faltas_compensadas'], false, $regras)) + ['casas' => (int) $ef['regra']['casas']];
    }

    /** Linha gravada no encerramento, no mesmo formato do cálculo. */
    private static function deGravado(array $g): array
    {
        return [
            'lancadas' => (int) $g['notas_lancadas'],
            'atividades' => (int) $g['atividades'],
            'soma' => null,
            'media' => number_format((float) $g['media_final'], (int) $g['casas'], '.', ''),
            'parcial' => false,
            'conta' => 'gravada no encerramento',
            'faltas' => (int) $g['faltas'],
            'compensadas' => (int) $g['faltas_compensadas'],
            'encontros' => (int) $g['encontros'],
            'frequencia' => number_format((float) $g['frequencia'], 2, '.', ''),
            'resultado' => (int) $g['resultado'],
            'motivo' => $g['motivo'],
            'gravado' => true,
            'casas' => (int) $g['casas'],
            /* E2 (D-361): os mínimos DO FECHAMENTO, para FS/FI e para a legenda */
            'frequenciaMinima' => number_format((float) $g['frequencia_minima'], 2, '.', ''),
            'mediaMinima' => number_format((float) $g['media_minima'], 2, '.', ''),
            /* E4 (D-396): a memória de cálculo e o fechamento (null num encerramento anterior à 1.13.0) */
            'memoria' => isset($g['memoria']) && $g['memoria'] !== null ? json_decode((string) $g['memoria'], true) : null,
            'fechamentoId' => isset($g['fechamento_id']) ? (int) $g['fechamento_id'] : null,
        ];
    }

    /* =============================================================================================================
     * A ESCOLA (Onda 4, Etapa 3; docs/ESCOLA.md, D-48): a oferta de uma turma é calculada em dois níveis por
     * \Core\MediaEscola (o motor uma vez por etapa, a recuperação, a média anual, a frequência, o conselho). Esta
     * fachada só lê o banco. A oferta sem turma (o ensino superior) nunca passa por aqui.
     * ============================================================================================================= */

    /** @var array<int, ?int> oferta → turma */
    private static array $turmaDe = [];

    /** A turma da oferta (escola), ou null (ensino superior). */
    public static function turmaDaOferta(int $ofertaId): ?int
    {
        if (!array_key_exists($ofertaId, self::$turmaDe)) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead('SELECT turma_id FROM esc_turma_ofertas WHERE oferta_id = :o LIMIT :limit', "o={$ofertaId}&limit=1");
            $t = $ler->getResultado()[0]['turma_id'] ?? null;
            self::$turmaDe[$ofertaId] = $t === null ? null : (int) $t;
        }
        return self::$turmaDe[$ofertaId];
    }

    /** As regras da escola: o padrão (\Core\MediaEscola::PADRAO) e o que a escola mudou (esc_configuracao). */
    public static function confEscola(): array
    {
        static $c = null;
        if ($c !== null) {
            return $c;
        }
        $v = \Core\MediaEscola::PADRAO;
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead('SELECT chave, valor FROM esc_configuracao');
        foreach ($ler->getResultado() ?: [] as $l) {
            if (array_key_exists((string) $l['chave'], $v)) {
                $v[(string) $l['chave']] = (string) $l['valor'];
            }
        }
        $pesos = [];
        foreach (array_values(array_filter(array_map('trim', explode(',', $v['anual.pesos'])), 'strlen')) as $i => $p) {
            $pesos[$i + 1] = max(1, (int) $p);
        }
        return $c = ['mediaMinima' => $v['media.minima'], 'recuperacao' => $v['recuperacao.regra'], 'composicao' => $v['anual.composicao'], 'pesos' => $pesos,
            'frequenciaMinima' => $v['frequencia.minima'], 'frequenciaBase' => $v['frequencia.base']];
    }

    /** As etapas do ano letivo, na ordem. */
    public static function etapasDoAno(int $ano): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead('SELECT id, ordem, nome, inicio, fim, conselho FROM esc_etapas WHERE ano = :a ORDER BY ordem', "a={$ano}");
        return $ler->getResultado() ?: [];
    }

    /** A etapa de uma data: a última que começou até ela (antes da primeira, a primeira). Sem data, a de hoje. */
    public static function etapaDaData(array $etapas, ?string $data): ?array
    {
        if (!$etapas) {
            return null;
        }
        $d = substr((string) ($data ?: date('Y-m-d')), 0, 10);
        $achada = $etapas[0];
        foreach ($etapas as $e) {
            if ((string) $e['inicio'] <= $d) {
                $achada = $e;
            }
        }
        return $achada;
    }

    /** Os fechamentos vigentes da oferta: etapa_id → [id, notas por RA]. */
    public static function fechamentosEscola(int $ofertaId): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, etapa_id FROM esc_fechamentos WHERE oferta_id = :o AND situacao = 'vigente'", "o={$ofertaId}");
        $f = [];
        foreach ($ler->getResultado() ?: [] as $r) {
            $f[(int) $r['etapa_id']] = ['id' => (int) $r['id'], 'notas' => []];
        }
        if ($f) {
            $ids = implode(',', array_map(fn($x) => $x['id'], $f));
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT n.*, f.etapa_id FROM esc_fechamento_notas n INNER JOIN esc_fechamentos f ON f.id = n.fechamento_id WHERE n.fechamento_id IN ({$ids})");
            foreach ($ler->getResultado() ?: [] as $n) {
                $f[(int) $n['etapa_id']]['notas'][(string) $n['rm']] = ['media' => $n['media'], 'recuperacao' => $n['recuperacao'], 'nota' => $n['nota'], 'lancadas' => (int) $n['lancadas']];
            }
        }
        return $f;
    }

    /**
     * A ESCOLA: o cálculo dos alunos da oferta (todos, ou só `$soRm`), no mesmo formato do motor, com as etapas a mais.
     * `$final` = o fechamento do ano (o encerramento da oferta).
     */
    private static function escola(array $oferta, int $turmaId, bool $final, ?string $soRm, array $ef): array
    {
        require_once dirname(__DIR__, 5) . '/core/MediaEscola.php';
        $id = (int) $oferta['id'];
        $conf = self::confEscola();
        $etapas = self::etapasDoAno((int) $oferta['ano']);
        $fech = self::fechamentosEscola($id);
        $atividades = self::atividades($id);
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead('SELECT id, data_inicial, data_final FROM rm_disciplina_atividade WHERE rm_prof_mat_id = :o AND situacao = :s', "o={$id}&s=1");
        $etapaDe = [];
        foreach ($ler->getResultado() ?: [] as $a) {
            $e = self::etapaDaData($etapas, $a['data_final'] ?: $a['data_inicial']);
            $etapaDe[(int) $a['id']] = $e ? (int) $e['id'] : 0;
        }
        $filtroRm = $soRm !== null ? ' AND rm = :rm' : '';
        $par = $soRm !== null ? '&rm=' . $soRm : '';
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT rm, nome_aluno FROM portal_disciplinas_alunos WHERE rm_prof_mat_id = :o AND situacao = :s{$filtroRm} ORDER BY nome_aluno", "o={$id}&s=1{$par}");
        $alunos = $ler->getResultado() ?: [];
        /* Histórico escolar, Parte H (D-117): o aluno que saiu da turma no meio do ano sem ir para outra turma (a
           transferência para outra escola). Só no cálculo de um aluno (o histórico, a vida escolar): o ano parcial fica
           legível, com as etapas até a data da saída; a etapa que começou depois dela fica fora da média (como a de antes
           da matrícula). */
        $saidaDe = [];
        if (!$alunos && $soRm !== null && $turmaId > 0) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead('SELECT x.saida, u.nome FROM esc_turma_alunos x INNER JOIN col_usuarios u ON u.id = x.usuario_id
                WHERE x.turma_id = :t AND x.rm = :rm AND x.saida IS NOT NULL AND x.destino_turma_id IS NULL ORDER BY x.id DESC LIMIT :limit', "t={$turmaId}&rm={$soRm}&limit=1");
            if ($sx = $ler->getResultado()[0] ?? null) {
                $alunos = [['rm' => $soRm, 'nome_aluno' => (string) $sx['nome']]];
                $saidaDe[$soRm] = substr((string) $sx['saida'], 0, 10);
            }
        }
        $notas = [];
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT rm, atividade_id, nota FROM rm_disciplina_atividade_notas WHERE rm_prof_mat_id = :o{$filtroRm}", "o={$id}{$par}");
        foreach ($ler->getResultado() ?: [] as $n) {
            $notas[(string) $n['rm']][(int) $n['atividade_id']] = self::centesimos($n['nota']);
        }
        $rec = [];
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT etapa_id, rm, nota FROM esc_recuperacoes WHERE oferta_id = :o{$filtroRm}", "o={$id}{$par}");
        foreach ($ler->getResultado() ?: [] as $r) {
            $rec[(int) $r['etapa_id']][(string) $r['rm']] = self::centesimos($r['nota']);
        }
        $faltas = [];
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT rm, faltas, faltas_compensadas FROM rm_disciplina_faltas WHERE rm_prof_mat_id = :o{$filtroRm}", "o={$id}{$par}");
        foreach ($ler->getResultado() ?: [] as $f) {
            $faltas[(string) $f['rm']] = [(int) $f['faltas'], (int) $f['faltas_compensadas']];
        }
        /* o trazido de outra turma (D-47): as notas como atividades a mais, na etapa da data delas; as faltas somadas */
        $trazidas = [];
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT rm, notas, faltas, compensadas FROM esc_lancamentos_trazidos WHERE oferta_destino_id = :o{$filtroRm}", "o={$id}{$par}");
        foreach ($ler->getResultado() ?: [] as $t) {
            $rm = (string) $t['rm'];
            foreach ((array) json_decode((string) $t['notas'], true) as $i => $a) {
                $e = self::etapaDaData($etapas, $a['data'] ?? null);
                $trazidas[$rm][$e ? (int) $e['id'] : 0][] = ['id' => -1000 - count($trazidas[$rm] ?? []) - $i, 'nome' => (string) ($a['nome'] ?? 'Atividade trazida') . ' (de outra turma)',
                    'nota' => self::centesimos($a['nota'] ?? null), 'peso' => AdmsRegraMedia::pesoTexto((string) ($a['peso'] ?? '10')), 'componente' => $a['componente'] ?? null];
            }
            $faltas[$rm] = [($faltas[$rm][0] ?? 0) + (int) $t['faltas'], ($faltas[$rm][1] ?? 0) + (int) $t['compensadas']];
        }
        /* Correção da escola, Parte 3 (A2; D-102): o aluno que entra no meio do ano. A data é a da matrícula no segmento
           (rm_matriculas_curso.data_ingresso, a "Data da matrícula" digitada), a mais nova até o fim do ano da oferta; a
           etapa que terminou antes dela fica sem nota (fora da média). As notas trazidas da escola de origem, por etapa,
           valem como a nota da etapa; as faltas trazidas somam às da disciplina. */
        $ingresso = [];
        $transf = [];
        $faltasTransf = [];
        if (\App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0083')) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT d.rm, (SELECT MAX(mc.data_ingresso) FROM rm_matriculas_curso mc INNER JOIN col_usuarios u ON u.id = mc.usuario_id
                    WHERE u.rm = d.rm AND mc.curso_id = :c AND mc.data_ingresso <= :fim) AS ingresso
                  FROM portal_disciplinas_alunos d WHERE d.rm_prof_mat_id = :o AND d.situacao = 1" . ($soRm !== null ? ' AND d.rm = :rm' : ''),
                'c=' . (int) ($oferta['curso_id'] ?? 0) . '&fim=' . (int) $oferta['ano'] . "-12-31&o={$id}{$par}");
            foreach ($ler->getResultado() ?: [] as $r) {
                if (!empty($r['ingresso'])) {
                    $ingresso[(string) $r['rm']] = (string) $r['ingresso'];
                }
            }
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT etapa_id, rm, nota, faltas, escola_origem FROM esc_notas_transferencia WHERE oferta_id = :o AND situacao = 'valida'{$filtroRm}", "o={$id}{$par}");
            foreach ($ler->getResultado() ?: [] as $r) {
                $rm = (string) $r['rm'];
                if ($r['nota'] !== null && $r['nota'] !== '') {
                    $transf[(int) $r['etapa_id']][$rm] = ['nota' => (string) $r['nota'], 'escola' => (string) $r['escola_origem']];
                }
                $faltasTransf[$rm] = ($faltasTransf[$rm] ?? 0) + (int) $r['faltas'];
            }
            foreach ($faltasTransf as $rm => $n) {
                $faltas[$rm] = [($faltas[$rm][0] ?? 0) + $n, $faltas[$rm][1] ?? 0];
            }
        }
        /* a frequência no total de horas do ano (a base padrão): todas as ofertas da turma, com o trazido */
        $freqAno = [];
        if ($conf['frequenciaBase'] === 'ano') {
            $ler = new \App\adms\Models\helper\AdmsRead();
            /* Onda 6, Etapa 3: a falta de cada oferta é lida por linha (na tabela derivada) e só depois somada por aluno —
               com o modo ONLY_FULL_GROUP_BY, a subconsulta que olhava a oferta (o.id) ou o aluno dentro do agrupamento era
               recusada; o trazido de outra turma vem somado por aluno numa segunda tabela derivada */
            $ler->fullRead("SELECT q.rm, SUM(q.enc) AS enc, SUM(q.fal) + IFNULL(MAX(tz.fal), 0) AS fal
                  FROM (SELECT e.rm, IFNULL(o.encontros, 0) AS enc,
                               IFNULL((SELECT f.faltas - f.faltas_compensadas FROM rm_disciplina_faltas f WHERE f.rm = e.rm AND f.rm_prof_mat_id = o.id LIMIT 1), 0) AS fal
                          FROM esc_turma_ofertas x INNER JOIN rm_professores_materias o ON o.id = x.oferta_id
                          INNER JOIN portal_disciplinas_alunos e ON e.rm_prof_mat_id = o.id
                         WHERE x.turma_id = :t" . ($soRm !== null ? ' AND e.rm = :rm' : '') . ') q
                  LEFT JOIN (SELECT t.rm, SUM(t.faltas - t.compensadas) AS fal FROM esc_lancamentos_trazidos t
                               INNER JOIN esc_turma_ofertas x2 ON x2.oferta_id = t.oferta_destino_id
                              WHERE x2.turma_id = :t2 GROUP BY t.rm) tz ON tz.rm = q.rm COLLATE utf8mb4_unicode_ci
                 GROUP BY q.rm', "t={$turmaId}&t2={$turmaId}{$par}");
            /* D-102: as faltas trazidas da escola de origem, de todas as disciplinas da turma */
            $transfTurma = [];
            if (\App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0083')) {
                $lt = new \App\adms\Models\helper\AdmsRead();
                $lt->fullRead("SELECT t.rm, SUM(t.faltas) AS fal FROM esc_notas_transferencia t INNER JOIN esc_turma_ofertas x ON x.oferta_id = t.oferta_id WHERE x.turma_id = :t AND t.situacao = 'valida' GROUP BY t.rm", "t={$turmaId}");
                foreach ($lt->getResultado() ?: [] as $x) {
                    $transfTurma[(string) $x['rm']] = (int) $x['fal'];
                }
            }
            foreach ($ler->getResultado() ?: [] as $r) {
                $enc = (int) $r['enc'];
                if ($enc > 0) {
                    $ef2 = max(0, min($enc, (int) $r['fal'] + ($transfTurma[(string) $r['rm']] ?? 0)));
                    $freqAno[(string) $r['rm']] = \Core\MotorMedia::arredondarFracao(($enc - $ef2) * 100, $enc, 2, 'meio_para_cima');
                }
            }
        }
        /* o conselho do fim do ano: aprovar o aluno (no componente, ou em todos) */
        $conselho = [];
        $ler = new \App\adms\Models\helper\AdmsRead();
        /* D-100: a decisão retirada (o autor ou a direção) não vale mais; fica no histórico */
        $valida = \App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0081') ? " AND c.situacao = 'valida'" : '';
        $ler->fullRead("SELECT c.rm, c.observacao, c.em, u.nome AS por FROM esc_conselho c LEFT JOIN adms_usuarios u ON u.id = c.por
              WHERE c.turma_id = :t AND c.etapa_id IS NULL AND c.decisao = 'aprovar' AND (c.oferta_id IS NULL OR c.oferta_id = :o){$valida} ORDER BY c.id", "t={$turmaId}&o={$id}");
        foreach ($ler->getResultado() ?: [] as $c) {
            $conselho[(string) $c['rm']] = ['observacao' => $c['observacao'], 'por' => $c['por'], 'em' => substr((string) $c['em'], 0, 10)];
        }
        $final = $final || !empty($oferta['encerrada_em']);
        $idsAtiv = array_map('intval', array_column($atividades, 'id'));
        $saida = [];
        foreach ($alunos as $a) {
            $rm = (string) $a['rm'];
            $porAtividade = [];
            foreach ($idsAtiv as $aid) {
                $porAtividade[$aid] = $notas[$rm][$aid] ?? null;
            }
            $lista = [];
            foreach ($etapas as $e) {
                $eid = (int) $e['id'];
                $ativEt = array_values(array_filter($atividades, fn($x) => ($etapaDe[(int) $x['id']] ?? 0) === $eid));
                $motor = array_merge(self::paraMotor($ativEt, $porAtividade), $trazidas[$rm][$eid] ?? []);
                $forcada = self::$forcarEtapa === $eid;
                $lista[] = ['id' => $eid, 'ordem' => (int) $e['ordem'], 'nome' => (string) $e['nome'], 'fechada' => isset($fech[$eid]) || $forcada,
                    'atividades' => $motor, 'recuperacao' => $rec[$eid][$rm] ?? null, 'gravada' => $forcada ? null : ($fech[$eid]['notas'][$rm] ?? null),
                    'transferencia' => $transf[$eid][$rm] ?? null, 'antesDaMatricula' => isset($ingresso[$rm]) && (string) $e['fim'] < $ingresso[$rm],
                    'depoisDaSaida' => isset($saidaDe[$rm]) && (string) $e['inicio'] > $saidaDe[$rm]]; // D-117
            }
            [$f, $c] = $faltas[$rm] ?? [0, 0];
            $calc = \Core\MediaEscola::calcular($lista, $ef['regra'], $conf, ['final' => $final, 'faltas' => $f, 'compensadas' => $c,
                'encontros' => $oferta['encontros'] !== null ? (int) $oferta['encontros'] : null, 'frequenciaAno' => $freqAno[$rm] ?? null, 'conselho' => $conselho[$rm] ?? null]);
            $saida[] = ['rm' => $rm, 'nome' => $a['nome_aluno'], 'notas' => $porAtividade] + $calc + ['casas' => (int) $ef['regra']['casas']];
        }
        return $saida;
    }

    /** A etapa fechada à força, no cálculo do fechamento dela (as anteriores, pelo que foi guardado). */
    private static ?int $forcarEtapa = null;

    /** O cálculo da escola com a etapa no modo final (o fechamento da etapa: AdmsEtapaNotas::fechar). */
    public static function escolaComEtapaFechada(int $ofertaId, int $etapaId): array
    {
        $oferta = self::dadosOferta($ofertaId);
        $turma = self::turmaDaOferta($ofertaId);
        if (!$oferta || !$turma) {
            return [];
        }
        self::carregarRegra();
        $ef = AdmsRegraMedia::efetivaOferta($ofertaId);
        self::$forcarEtapa = $etapaId;
        try {
            return self::escola($oferta, $turma, false, null, $ef);
        } finally {
            self::$forcarEtapa = null;
        }
    }

    /**
     * As etapas cujo boletim a escola liberou (o portal do aluno e o da família só mostram essas). Correção da escola,
     * Parte 1 (D-100): a liberação é a da tela "Liberação do boletim" — exibir no portal e, se houver, a partir da data e
     * hora (antes dela, não aparece). Antes da 0081, a tabela antiga.
     */
    public static function etapasLiberadas(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        if (!\App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0081')) {
            $ler->fullRead('SELECT etapa_id FROM esc_boletim_liberado');
        } else {
            $ler->fullRead("SELECT etapa_id FROM esc_boletim_liberacao WHERE item = 'etapa' AND exibir = 1 AND (a_partir IS NULL OR a_partir <= NOW())");
        }
        return array_map('intval', array_column($ler->getResultado() ?: [], 'etapa_id'));
    }

    /** @var array<string, bool> */
    private static array $finalLiberado = [];

    /**
     * D-100: a média final (item media_final) ou a situação final (situacao_final) do ano está liberada no portal?
     * Antes da 0081, a regra de antes: com todas as etapas do ano liberadas.
     */
    public static function finalLiberado(int $ano, string $item): bool
    {
        $k = $ano . ':' . $item;
        if (isset(self::$finalLiberado[$k])) {
            return self::$finalLiberado[$k];
        }
        if (!\App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0081')) {
            $ids = array_map('intval', array_column(self::etapasDoAno($ano), 'id'));
            return self::$finalLiberado[$k] = $ids !== [] && !array_diff($ids, self::etapasLiberadas());
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead('SELECT id FROM esc_boletim_liberacao WHERE ano = :a AND item = :i AND etapa_id = 0 AND exibir = 1 AND (a_partir IS NULL OR a_partir <= NOW()) LIMIT :limit',
            "a={$ano}&i={$item}&limit=1");
        return self::$finalLiberado[$k] = !empty($ler->getResultado());
    }
}
