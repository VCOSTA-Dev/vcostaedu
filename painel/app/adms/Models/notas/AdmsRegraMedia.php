<?php

namespace App\adms\Models\notas;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 5) . '/core/MotorMedia.php';

use Core\MotorMedia as Motor;

/**
 * REGRAS DE MÉDIA — o CATÁLOGO (1.16.0, D-436 a D-446). O cálculo é do motor (core/MotorMedia.php).
 *
 * - Cada regra tem NOME (e descrição) e vale para as disciplinas a que for associada. Exatamente UMA é o "Padrão da
 *   instituição" (`padrao` = 1): vale para toda disciplina sem regra. Situação: 1 ativa, 2 inativa.
 * - RESOLUÇÃO: a oferta (se a regra foi trocada nela, `rm_professores_materias.regra_media_id`) → a disciplina da
 *   matriz (`uni_rh_cursos_disciplinas_grade.regra_media_id`) → o padrão. O nível "curso" saiu: agrupar por curso, tipo
 *   ou módulo é associar a mesma regra em lote.
 * - Por baixo, o de antes (D-392, D-394, D-396): `rm_media_regra_versoes` + `rm_media_regra_componentes`; versão já usada
 *   num fechamento não muda (salvar cria a seguinte, só por dentro: a tela não fala em versão); o fechamento copia a
 *   regra (regra_json + hash). escopo/escopo_id ficam só como origem histórica (1.13.0 a 1.15.2).
 * - A regra PADRÃO espelha as casas e a nota mínima nos parâmetros `nota.casas` e `nota.media_minima` (o TCC, a legenda
 *   e o boletim leem deles), na mesma transação.
 * O portal carrega esta classe pela ponte (AdmsPainelPonte); não há cópia.
 */
class AdmsRegraMedia
{
    /** De onde vem a regra que vale para a oferta (o texto das telas). */
    public const ORIGENS = ['oferta' => 'própria desta oferta', 'disciplina' => 'da disciplina', 'padrao' => 'padrão da instituição', 'parametros' => 'dos parâmetros',
        'curso' => 'do curso (TCC e documentos)'];

    private static array $cache = [];
    private array $erros = [];
    private array $avisos = [];
    private ?array $confirmacao = null;
    private int $novoId = 0;

    public function getErros(): array
    {
        return $this->erros;
    }

    /** E10 (D-421, D-423): o que a regra salva avisa (nenhum obrigatório; ofertas abertas fora dos limites). */
    public function getAvisos(): array
    {
        return $this->avisos;
    }

    /** 1.16.0 (D-439): o que precisa de confirmação antes de gravar (null = nada). */
    public function getConfirmacao(): ?array
    {
        return $this->confirmacao;
    }

    /** O id da regra criada (cadastrar, duplicar). */
    public function getNovoId(): int
    {
        return $this->novoId;
    }

    /* ================================================================== LEITURA */

    /**
     * A regra que vale para a oferta: ['regra' => (formato do motor), 'origem' => oferta|disciplina|padrao|parametros,
     * 'regraId', 'versaoId', 'versao', 'nome'].
     */
    public static function efetivaOferta(int $ofertaId): array
    {
        $k = "o{$ofertaId}";
        if (!isset(self::$cache[$k])) {
            $l = null;
            try {
                $ler = new \App\adms\Models\helper\AdmsRead();
                $ler->fullRead("SELECT o.regra_media_id AS ro, l.regra_media_id AS rd FROM rm_professores_materias o
                                  LEFT JOIN uni_rh_cursos_disciplinas_grade l ON l.id = o.matriz_id WHERE o.id = :id LIMIT :limit", "id={$ofertaId}&limit=1");
                $l = $ler->getResultado()[0] ?? null;
            } catch (\Throwable $e) {
                $l = null; /* base anterior à 1.16.0 */
            }
            self::$cache[$k] = (!empty($l['ro']) ? self::daRegra((int) $l['ro'], 'oferta') : null)
                ?? (!empty($l['rd']) ? self::daRegra((int) $l['rd'], 'disciplina') : null)
                ?? self::padrao();
        }
        return self::$cache[$k];
    }

    /** A regra de uma disciplina da matriz (vazio = o padrão). */
    public static function efetivaDisciplina(int $linhaId): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT regra_media_id FROM uni_rh_cursos_disciplinas_grade WHERE id = :id LIMIT :limit", "id={$linhaId}&limit=1");
        $r = (int) ($ler->getResultado()[0]['regra_media_id'] ?? 0);
        return ($r ? self::daRegra($r, 'disciplina') : null) ?? self::padrao();
    }

    /**
     * 1.16.1 (D-447, decisão 1 do responsável pelo projeto sobre a 1.16.0): o que é do CURSO — a nota mínima da aprovação do TCC e a legenda
     * dos documentos (NOTA_MINIMA e afins) — segue a regra escolhida no curso (`rm_cursos.regra_media_id`); vazio = o
     * Padrão da instituição. Não é herança: a média de cada disciplina continua oferta → disciplina → padrão.
     */
    public static function efetivaCurso(int $cursoId): array
    {
        $k = "c{$cursoId}";
        if (!isset(self::$cache[$k])) {
            $r = 0;
            if ($cursoId > 0) {
                try {
                    $ler = new \App\adms\Models\helper\AdmsRead();
                    $ler->fullRead("SELECT regra_media_id FROM rm_cursos WHERE id = :id LIMIT :limit", "id={$cursoId}&limit=1");
                    $r = (int) ($ler->getResultado()[0]['regra_media_id'] ?? 0);
                } catch (\Throwable $e) {
                    $r = 0; /* base anterior à 1.16.1 */
                }
            }
            self::$cache[$k] = ($r ? self::daRegra($r, 'curso') : null) ?? self::padrao();
        }
        return self::$cache[$k];
    }

    /* ------------------------------------------------------------------ 1.16.2 (D-450): DIVERGÊNCIA DA NOTA MÍNIMA */

    /**
     * A função ÚNICA da divergência: os itens (disciplinas) cuja nota mínima efetiva é DIFERENTE da `$minimaLegenda` (a
     * nota mínima que a legenda dos documentos dirá). Cada item: ['disciplina', 'minima' ("7.00"), 'regraId', 'regraNome',
     * 'origem' (oferta|disciplina|padrao|gravada)]. Comparação em centésimos. A tela do curso e o histórico usam esta.
     */
    public static function divergenciasNotaMinima(string $minimaLegenda, array $itens): array
    {
        $ref = Motor::centesimosDe($minimaLegenda);
        return array_values(array_filter($itens, fn($i) => Motor::centesimosDe((string) $i['minima']) !== $ref));
    }

    /** "3 disciplinas exigem 7,0, e a legenda dos documentos dirá 6,0" (agrupado por nota mínima). */
    public static function resumoDivergencia(array $div, string $minimaLegenda): string
    {
        $por = [];
        foreach ($div as $d) {
            $por[AdmsCalculoNotas::numero((string) $d['minima'], 1)][] = $d;
        }
        $partes = array_map(fn($v, $l) => count($l) . (count($l) > 1 ? ' disciplinas exigem ' : ' disciplina exige ') . $v, array_keys($por), $por);
        return implode('; ', $partes) . ', e a legenda dos documentos dirá ' . AdmsCalculoNotas::numero($minimaLegenda, 1);
    }

    /**
     * Item A: as disciplinas das grades do CURSO, com a regra efetiva de cada uma (disciplina da matriz → padrão), contra
     * a regra do TCC e dos documentos do curso — a de hoje, ou a `$candidata` (o que se vai salvar; 0 = o padrão).
     * ['legenda' => "6.00", 'divergencias' => [...], 'resumo' => texto ou ''].
     */
    public static function divergenciasDoCurso(int $cursoId, ?int $candidata = null): array
    {
        $legenda = $candidata === null ? self::efetivaCurso($cursoId) : (($candidata > 0 ? self::daRegra($candidata, 'curso') : null) ?? self::padrao());
        $min = (string) $legenda['regra']['notaMinima'];
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT l.id, mt.nome FROM uni_rh_cursos_disciplinas_grade l JOIN rm_materias mt ON mt.id = l.materia_id
                          JOIN rm_cursos_grades g ON g.id = l.grade_id WHERE g.curso_id = :c ORDER BY g.versao, l.ordem, mt.nome", "c={$cursoId}");
        $itens = [];
        foreach ($ler->getResultado() ?: [] as $l) {
            $ef = self::efetivaDisciplina((int) $l['id']);
            $itens[] = ['disciplina' => $l['nome'], 'minima' => (string) $ef['regra']['notaMinima'], 'regraId' => (int) $ef['regraId'], 'regraNome' => (string) ($ef['nome'] ?? ''), 'origem' => $ef['origem']];
        }
        $div = self::divergenciasNotaMinima($min, $itens);
        return ['legenda' => $min, 'divergencias' => $div, 'resumo' => $div ? self::resumoDivergencia($div, $min) : ''];
    }

    /**
     * O aviso do item A em HTML (tela do curso e confirmação ao salvar o campo): o resumo e a lista das disciplinas, com o
     * link para a regra de cada uma. `''` quando não há divergência.
     */
    public static function divergenciaHtml(array $r, string $idHtml = 'avisoDivergencia'): string
    {
        if (!$r['divergencias']) {
            return '';
        }
        $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $base = defined('URLADM') ? URLADM : '';
        $li = array_map(fn($d) => '<li>' . $h($d['disciplina']) . ': exige ' . $h(AdmsCalculoNotas::numero((string) $d['minima'], 1)) . ' — '
            . ($d['regraId'] > 0 ? '<a href="' . $h($base . 'regra-media/editar/' . (int) $d['regraId']) . '">' . $h($d['regraNome']) . '</a>' : $h($d['regraNome']))
            . ' (' . $h(self::ORIGENS[$d['origem']] ?? $d['origem']) . ')</li>', $r['divergencias']);
        return '<div class="alert alert-warning small mb-0 mt-1" id="' . $h($idHtml) . '"><i class="fas fa-exclamation-triangle me-1"></i><b>' . $h($r['resumo']) . '.</b>'
            . ' A legenda dos documentos não pode dizer uma nota mínima diferente da exigida: alinhe a regra das disciplinas ou a regra do TCC e dos documentos.'
            . '<ul class="mb-0 mt-1">' . implode('', $li) . '</ul></div>';
    }

    /**
     * Item B: o alerta do HISTÓRICO (prévia e emissão, parcial e final) — as disciplinas DO DOCUMENTO com nota mínima
     * efetiva diferente da legenda (NOTA_MINIMA). Oferta ENCERRADA: a nota mínima GRAVADA no fechamento; oferta aberta:
     * a regra da oferta (oferta → disciplina → padrão); disciplina sem oferta: a da disciplina da matriz (→ padrão).
     * `$disciplinas`: [['nome', 'linha_id', 'oferta_id' (?int), 'encerrada' (bool)]]. Devolve o texto do alerta ou null.
     */
    public static function alertaDivergenciaDocumento(string $minimaLegenda, array $disciplinas, string $rm): ?string
    {
        $itens = [];
        foreach ($disciplinas as $d) {
            $min = null;
            if (!empty($d['oferta_id']) && !empty($d['encerrada'])) {
                $ler = new \App\adms\Models\helper\AdmsRead();
                $ler->fullRead("SELECT media_minima FROM rm_disciplina_media_final WHERE rm_prof_mat_id = :o AND rm = :rm LIMIT :limit", "o={$d['oferta_id']}&rm={$rm}&limit=1");
                $g = $ler->getResultado()[0]['media_minima'] ?? null;
                if ($g !== null) {
                    $itens[] = ['disciplina' => $d['nome'], 'minima' => number_format((float) $g, 2, '.', ''), 'regraId' => 0, 'regraNome' => '', 'origem' => 'gravada'];
                    continue;
                }
            }
            $ef = !empty($d['oferta_id']) ? self::efetivaOferta((int) $d['oferta_id']) : self::efetivaDisciplina((int) $d['linha_id']);
            $itens[] = ['disciplina' => $d['nome'], 'minima' => (string) $ef['regra']['notaMinima'], 'regraId' => (int) $ef['regraId'], 'regraNome' => (string) ($ef['nome'] ?? ''), 'origem' => $ef['origem']];
        }
        $div = self::divergenciasNotaMinima($minimaLegenda, $itens);
        if (!$div) {
            return null;
        }
        $lista = array_map(fn($x) => $x['disciplina'] . ' (' . AdmsCalculoNotas::numero($x['minima'], 1)
            . ($x['origem'] === 'gravada' ? ', gravada no encerramento' : ', regra "' . $x['regraNome'] . '"') . ')', $div);
        return 'Nota mínima divergente: a legenda do documento diz ' . AdmsCalculoNotas::numero($minimaLegenda, 1) . ', mas ' . implode('; ', $lista)
            . '. Corrija a regra da disciplina (Regras de média) ou a "Regra de média do TCC e dos documentos" na tela do curso.'
            . (array_filter($div, fn($x) => $x['origem'] === 'gravada') ? ' Disciplina encerrada guarda a nota mínima do encerramento: para ela, alinhe a regra do curso ou reabra a disciplina.' : '');
    }

    /**
     * 1.16.1 (D-447): grava a regra do TCC e dos documentos do curso (`$regraId` null = o padrão). Só regra ATIVA.
     * Uma tabela só (o registro no log é à parte, como nas outras telas).
     */
    public function definirRegraDoCurso(int $cursoId, ?int $regraId, int $usuarioId): bool
    {
        $this->erros = [];
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, regra_media_id FROM rm_cursos WHERE id = :id LIMIT :limit", "id={$cursoId}&limit=1");
        $c = $ler->getResultado()[0] ?? null;
        if (!$c) {
            $this->erros[] = 'Curso não encontrado.';
            return false;
        }
        if ($regraId) {
            $r = self::cabecalho($regraId);
            if (!$r || (int) $r['situacao'] !== 1) {
                $this->erros[] = 'Escolha uma regra de média ATIVA.';
                return false;
            }
        }
        if ((int) ($c['regra_media_id'] ?? 0) === (int) $regraId) {
            return true;
        }
        $u = new \App\adms\Models\helper\AdmsUpdate();
        $u->exeUpdate('rm_cursos', ['regra_media_id' => $regraId ?: null, 'usuario_mod' => $usuarioId], 'WHERE id = :id', "id={$cursoId}");
        if ($u->getResultado() === null) {
            $this->erros[] = 'Nada foi gravado.';
            return false;
        }
        self::limparCache();
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(418, 200, 3, "Regra do TCC e dos documentos do curso {$cursoId}: " . ($regraId ?: 'padrão'));
        return true;
    }

    /** O padrão da instituição (nome antigo, para quem já chama). */
    public static function instituicao(): array
    {
        return self::padrao();
    }

    /** A regra "Padrão da instituição"; sem catálogo (base antiga), a regra dos parâmetros. */
    public static function padrao(): array
    {
        if (!isset(self::$cache['p'])) {
            $id = 0;
            try {
                $ler = new \App\adms\Models\helper\AdmsRead();
                $ler->fullRead("SELECT id FROM rm_media_regras WHERE padrao = :p ORDER BY id LIMIT :limit", "p=1&limit=1");
                $id = (int) ($ler->getResultado()[0]['id'] ?? 0);
            } catch (\Throwable $e) {
                $id = 0;
            }
            self::$cache['p'] = ($id ? self::daRegra($id, 'padrao') : null) ?? self::dosParametros();
        }
        return self::$cache['p'];
    }

    /** O cabeçalho da regra (catálogo), ou null. */
    public static function cabecalho(int $regraId): ?array
    {
        try {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT * FROM rm_media_regras WHERE id = :i LIMIT :limit", "i={$regraId}&limit=1");
            return $ler->getResultado()[0] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** A regra do catálogo na versão atual, no formato do motor, com a origem dada. */
    public static function daRegra(int $regraId, string $origem): ?array
    {
        $c = self::cabecalho($regraId);
        if (!$c) {
            return null;
        }
        $v = self::versao((int) $c['id'], (int) $c['versao_atual'], $origem, 0);
        return $v ? $v + ['nome' => (string) ($c['nome'] ?? ''), 'padrao' => (int) ($c['padrao'] ?? 0) === 1, 'situacao' => (int) $c['situacao']] : null;
    }

    /** Uma versão da regra, no formato do motor. */
    public static function versao(int $regraId, int $versao, string $escopo, int $escopoId): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT * FROM rm_media_regra_versoes WHERE regra_id = :r AND versao = :v LIMIT :limit", "r={$regraId}&v={$versao}&limit=1");
        $v = $ler->getResultado()[0] ?? null;
        if (!$v) {
            return null;
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT * FROM rm_media_regra_componentes WHERE versao_id = :v ORDER BY ordem, id", "v={$v['id']}");
        $comps = [];
        foreach ($ler->getResultado() ?: [] as $c) {
            $comps[] = ['codigo' => $c['codigo'], 'nome' => $c['nome'], 'peso' => self::pesoTexto((string) $c['peso']), 'modo' => $c['modo'],
                'n' => $c['parametro_n'] !== null ? (string) (int) $c['parametro_n'] : null, 'tipoN' => $c['parametro_tipo'], 'ordem' => (int) $c['ordem'],
                /* E10 (D-420): obrigatório (mínimo) e limite (máximo); base anterior à 1.15.0 = opcional e sem limite */
                'min' => (int) ($c['min_atividades'] ?? 0), 'max' => isset($c['max_atividades']) && $c['max_atividades'] !== null ? (int) $c['max_atividades'] : null];
        }
        if (!$comps) {
            return null;
        }
        /* Onda 4, Etapa 5: o acréscimo da versão (rm_media_regra_acrescimo); sem a linha, a regra não ganha a chave */
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT permitir, limite FROM rm_media_regra_acrescimo WHERE versao_id = :v LIMIT :limit", "v={$v['id']}&limit=1");
        $acr = $ler->getResultado()[0] ?? null;
        return [
            'regra' => ['componentes' => $comps, 'casas' => (int) $v['casas'], 'arredondamento' => $v['arredondamento'], 'momento' => $v['momento'],
                'semNota' => $v['sem_nota'], 'componenteVazio' => $v['componente_vazio'], 'notaMinima' => number_format((float) $v['nota_minima'], 2, '.', '')]
                + ($acr && (int) $acr['permitir'] === 1 ? ['acrescimo' => ['permitir' => true, 'limite' => $acr['limite'] === null ? null : number_format((float) $acr['limite'], 2, '.', '')]] : []),
            'origem' => $escopo, 'regraId' => $regraId, 'versaoId' => (int) $v['id'], 'versao' => (int) $v['versao'], 'escopoId' => $escopoId,
        ];
    }

    /** Sem regra gravada (base antiga): a de hoje, dos parâmetros. */
    private static function dosParametros(): array
    {
        $p = \App\adms\Models\helper\AdmsParametro::regrasNotas();
        return ['regra' => Motor::regraPadrao((int) $p['casas'], (string) $p['mediaMinima']), 'origem' => 'parametros', 'regraId' => 0, 'versaoId' => 0, 'versao' => 0, 'escopoId' => 0, 'nome' => 'Regra dos parâmetros'];
    }

    /** "100.0000" → "100"; "33.3333" → "33.3333"; "2.5000" → "2.5". */
    public static function pesoTexto(string $p): string
    {
        return str_contains($p, '.') ? rtrim(rtrim($p, '0'), '.') : $p;
    }

    /** A versão já foi usada num fechamento? (então não muda: salvar cria a seguinte) */
    public static function versaoUsada(int $versaoId): bool
    {
        if ($versaoId <= 0) {
            return false;
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id FROM rm_media_fechamentos WHERE regra_versao_id = :v LIMIT :limit", "v={$versaoId}&limit=1");
        return !empty($ler->getResultado());
    }

    /** Esquece o cache (depois de salvar, na mesma requisição). */
    public static function limparCache(): void
    {
        self::$cache = [];
    }

    /** Os componentes da regra que vale para a oferta (a lista do cadastro de atividade). */
    public static function componentesDaOferta(int $ofertaId): array
    {
        return self::efetivaOferta($ofertaId)['regra']['componentes'];
    }

    /**
     * CADASTRO DE ATIVIDADE (D-394): confere e normaliza `componente_codigo` (um código da regra que vale para a oferta;
     * vazio = o componente padrão, o primeiro) e `peso` (decimal > 0, até 999,9999; padrão 10). Devolve a lista de
     * erros (vazia = ok) e deixa `$dados` pronto para gravar. Recusa também o POST direto com código inexistente.
     */
    public static function validarAtividade(int $ofertaId, array &$dados, int $atividadeId = 0): array
    {
        $erros = [];
        $comps = self::componentesDaOferta($ofertaId);
        if ((int) ($dados['tipo'] ?? 0) === 6) { // Onda 4, Etapa 5: o acréscimo fica fora dos componentes (sem componente, sem o máximo)
            $dados['componente_codigo'] = null;
            $comps = [];
        }
        $cod = trim((string) ($dados['componente_codigo'] ?? ''));
        $codigos = array_column($comps, 'codigo');
        if ($cod !== '' && !in_array($cod, $codigos, true)) {
            $erros[] = 'Componente inválido: escolha um dos componentes da regra de média desta oferta.';
        } else {
            /* E10 (D-423): o MÁXIMO do componente na oferta — cadastrar, ou mover a atividade para um componente cheio,
               é recusado (também no POST direto). Editar sem trocar de componente não conta. */
            $destino = $cod === '' ? (string) ($codigos[0] ?? '') : $cod;
            $comp = array_values(array_filter($comps, fn($c) => $c['codigo'] === $destino))[0] ?? null;
            $max = $comp ? Motor::maximo($comp) : null;
            if ($comp && self::componenteCheio($comp, self::contagemOferta($ofertaId, $codigos)[$destino] ?? 0,
                    $destino === ($atividadeId > 0 ? self::componenteDaAtividade($atividadeId, $codigos) : null))) {
                $erros[] = 'O componente ' . $comp['nome'] . ' aceita no máximo ' . $max . ' atividade' . ($max > 1 ? 's' : '') . ' nesta disciplina.';
            }
        }
        $dados['componente_codigo'] = ($cod === '' || $cod === ($codigos[0] ?? '')) ? null : $cod;
        $peso = str_replace(',', '.', trim((string) ($dados['peso'] ?? '10'))) ?: '10';
        $p = Motor::pesoInt($peso);
        if ($p === null || $p <= 0) {
            $erros[] = 'Peso da atividade: um número maior que 0, com até 4 casas (ex.: 2,5).';
        } else {
            $dados['peso'] = number_format($p / 10000, 4, '.', '');
        }
        return $erros;
    }

    /* ================================================================== LIMITES POR OFERTA (E10, D-423) */

    /**
     * Quantas atividades ATIVAS a oferta tem em cada componente (código → n). Atividade sem componente, ou com um
     * código que a regra não tem, conta no primeiro — como no motor.
     */
    public static function contagemOferta(int $ofertaId, ?array $codigos = null): array
    {
        $codigos = $codigos ?? array_column(self::componentesDaOferta($ofertaId), 'codigo');
        $n = array_fill_keys($codigos, 0);
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT componente_codigo AS c, COUNT(*) AS n FROM rm_disciplina_atividade WHERE rm_prof_mat_id = :o AND situacao = :s AND tipo <> 6 GROUP BY componente_codigo", "o={$ofertaId}&s=1"); // Onda 4, Etapa 5: o acréscimo (tipo 6) fica fora dos componentes
        foreach ($ler->getResultado() ?: [] as $l) {
            $c = (string) ($l['c'] ?? '');
            $c = isset($n[$c]) ? $c : (string) ($codigos[0] ?? '');
            if ($c !== '') {
                $n[$c] += (int) $l['n'];
            }
        }
        return $n;
    }

    /**
     * 1.15.2 (D-430): a TRAVA do máximo, num lugar só — cadastro, edição de atividade e simulador. A atividade pode ir
     * para o componente? `$jaNele` = quantas atividades JÁ estão nele, sem contar esta; o componente em que ela já está
     * (`$proprio`) nunca trava (a que sobra aparece como excedente, não some).
     */
    public static function componenteCheio(array $comp, int $jaNele, bool $proprio = false): bool
    {
        $max = Motor::maximo($comp);
        return !$proprio && $max !== null && $jaNele >= $max;
    }

    /**
     * 1.15.2 (D-430): as travas do SIMULADOR, pela mesma `componenteCheio`: por atividade de teste, os componentes que ela
     * não pode escolher e se ela é EXCEDENTE (passa do máximo, na ordem da tela); para uma atividade nova, os cheios.
     */
    public static function travasSimulador(array $comps, array $ativs): array
    {
        $codigos = array_column($comps, 'codigo');
        $porCod = array_column($comps, null, 'codigo');
        $de = array_map(fn($a) => in_array((string) ($a['componente'] ?? ''), $codigos, true) ? (string) $a['componente'] : (string) ($codigos[0] ?? ''), $ativs);
        $cont = array_fill_keys($codigos, 0);
        foreach ($de as $c) {
            $cont[$c] = ($cont[$c] ?? 0) + 1;
        }
        $linhas = [];
        $visto = array_fill_keys($codigos, 0);
        foreach ($de as $i => $meu) {
            $visto[$meu]++;
            $max = isset($porCod[$meu]) ? Motor::maximo($porCod[$meu]) : null;
            $linhas[$i] = ['excedente' => $max !== null && $visto[$meu] > $max,
                'cheios' => array_values(array_filter($codigos, fn($c) => self::componenteCheio($porCod[$c], $cont[$c] - ($c === $meu ? 1 : 0), $c === $meu)))];
        }
        return ['linhas' => $linhas, 'contagem' => $cont, 'max' => array_map(fn($c) => Motor::maximo($c), $porCod),
            'cheiosNova' => array_values(array_filter($codigos, fn($c) => self::componenteCheio($porCod[$c], $cont[$c])))];
    }

    /** O componente em que a atividade está hoje (vazio ou desconhecido = o primeiro). */
    private static function componenteDaAtividade(int $atividadeId, array $codigos): ?string
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT componente_codigo FROM rm_disciplina_atividade WHERE id = :id LIMIT :limit", "id={$atividadeId}&limit=1");
        $l = $ler->getResultado()[0] ?? null;
        if (!$l) {
            return null;
        }
        $c = (string) ($l['componente_codigo'] ?? '');
        return in_array($c, $codigos, true) ? $c : (string) ($codigos[0] ?? '');
    }

    /**
     * A oferta contra os limites da regra que vale para ela: ['contagem' => [código => n], 'faltam' => ['Prova
     * (mínimo 1)', …], 'sobram' => ['Prova: 3 atividades, o máximo é 2', …], 'regraId' => …]. Vazio = em ordem.
     */
    public static function limitesOferta(int $ofertaId, ?array $regra = null): array
    {
        $ef = self::efetivaOferta($ofertaId);
        $comps = ($regra ?? $ef['regra'])['componentes']; /* 1.16.0: com $regra, a prévia de uma regra ainda não salva */
        $cont = self::contagemOferta($ofertaId, array_column($comps, 'codigo'));
        $faltam = [];
        $sobram = [];
        foreach ($comps as $c) {
            $n = $cont[$c['codigo']] ?? 0;
            $min = Motor::minimo($c);
            $max = Motor::maximo($c);
            if ($min > 0 && $n < $min) {
                $faltam[] = $c['nome'] . ' (mínimo ' . $min . ($n > 0 ? '; há ' . $n : '') . ')';
            }
            if ($max !== null && $n > $max) {
                $sobram[] = $c['nome'] . ': ' . $n . ' atividades, o máximo é ' . $max;
            }
        }
        return ['contagem' => $cont, 'faltam' => $faltam, 'sobram' => $sobram, 'regraId' => (int) $ef['regraId'], 'componentes' => $comps];
    }

    /**
     * O aviso das telas da disciplina (disciplina, atividades, notas) enquanto faltar atividade num componente
     * obrigatório ou sobrar num com máximo. Oferta encerrada: nada (vale a regra guardada no fechamento).
     */
    public static function avisoLimitesHtml(int $ofertaId): string
    {
        $o = $ofertaId > 0 ? AdmsCalculoNotas::dadosOferta($ofertaId) : null;
        if (!$o || !empty($o["encerrada_em"])) {
            return '';
        }
        $l = self::limitesOferta($ofertaId);
        if (!$l['faltam'] && !$l['sobram']) {
            return '';
        }
        $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        return "<div class='alert alert-warning' id='avisoLimites' role='status'><i class='fas fa-exclamation-triangle me-1'></i>"
            . ($l['faltam'] ? '<b>Falta cadastrar:</b> ' . $h(implode(', ', $l['faltam'])) . '.' : '')
            . ($l['faltam'] && $l['sobram'] ? '<br>' : '')
            . ($l['sobram'] ? '<b>Acima do máximo:</b> ' . $h(implode('; ', $l['sobram'])) . '.' : '')
            . ' <span class="small">A disciplina só encerra com a regra de média atendida.</span></div>';
    }

    /**
     * Depois de salvar uma regra (D-423): as ofertas ABERTAS que seguem essa regra e ficaram fora dos limites
     * (a regra salva; o encerramento delas fica bloqueado até ajustar as atividades). Texto por oferta.
     */
    public static function ofertasForaDosLimites(int $regraId, ?array $regraNova = null): array
    {
        self::limparCache();
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, materia, codtur, ano FROM rm_professores_materias WHERE encerrada_em IS NULL ORDER BY ano DESC, materia, id");
        $saida = [];
        foreach ($ler->getResultado() ?: [] as $o) {
            $l = self::limitesOferta((int) $o['id'], $regraNova); /* 1.16.0 (D-439): com $regraNova, como ficaria */
            if ($l['regraId'] !== $regraId || (!$l['faltam'] && !$l['sobram'])) {
                continue;
            }
            $saida[] = trim($o['materia'] . ' — ' . $o['codtur'] . ' ' . $o['ano']) . ': '
                . implode('; ', array_merge($l['faltam'] ? ['falta cadastrar ' . implode(', ', $l['faltam'])] : [], $l['sobram']));
        }
        return $saida;
    }

    /* ================================================================== FORMULÁRIO E SIMULADOR */

    /**
     * O formulário da tela → regra no formato do motor. `regra[...]` (casas, arredondamento, momento, semNota,
     * componenteVazio, notaMinima) e `comp[i][...]` (codigo, nome, peso, modo, n, tipoN; a ordem é a da tela).
     * Componente sem código ganha um a partir do nome (estável dali em diante).
     */
    public static function doFormulario(array $post): array
    {
        $r = (array) ($post['regra'] ?? []);
        $comps = [];
        $usados = [];
        $ordem = 0;
        foreach ((array) ($post['comp'] ?? []) as $c) {
            if (!is_array($c) || (trim((string) ($c['nome'] ?? '')) === '' && trim((string) ($c['peso'] ?? '')) === '')) {
                continue;
            }
            $cod = trim((string) ($c['codigo'] ?? ''));
            if ($cod === '') {
                $cod = self::codigoDe((string) $c['nome'], $usados);
            }
            $usados[$cod] = true;
            $modo = (string) ($c['modo'] ?? 'aritmetica');
            $comNovo = in_array($modo, Motor::MODOS_COM_N, true);
            /* E10 (D-420): na tela, "Obrigatório" (caixa) liga o mínimo (vazio = 1); desmarcada, o mínimo é 0. Fora da
               tela (cópia de uma regra, simulador sem formulário), `min` e `max` vêm como estão. */
            if (array_key_exists('tela', $c)) {
                $minTxt = trim((string) ($c['min'] ?? ''));
                $min = array_key_exists('obrig', $c) ? ($minTxt === '' ? '1' : $minTxt) : '0';
            } else {
                $min = (string) (int) ($c['min'] ?? 0);
            }
            $max = trim((string) ($c['max'] ?? ''));
            $comps[] = ['codigo' => $cod, 'nome' => trim((string) $c['nome']), 'peso' => str_replace(',', '.', trim((string) ($c['peso'] ?? ''))), 'modo' => $modo,
                'n' => $comNovo ? trim((string) ($c['n'] ?? '')) : null, 'tipoN' => $comNovo ? (string) ($c['tipoN'] ?? 'quantidade') : null, 'ordem' => ++$ordem,
                'min' => preg_match('/^\d{1,2}$/', $min) ? (int) $min : $min, 'max' => $max === '' ? null : (preg_match('/^\d{1,2}$/', $max) ? (int) $max : $max)];
        }
        $nm = str_replace(',', '.', trim((string) ($r['notaMinima'] ?? '')));
        /* Onda 4, Etapa 5: permitir o acréscimo (padrão: não) e o limite (vazio = sem limite) */
        $acrLim = str_replace(',', '.', trim((string) ($r['acrescimoLimite'] ?? '')));
        $acrescimo = !empty($r['acrescimo']) ? ['acrescimo' => ['permitir' => true, 'limite' => $acrLim === '' ? null : (preg_match('/^\d{1,2}(\.\d{1,2})?$/', $acrLim) ? number_format((float) $acrLim, 2, '.', '') : $acrLim)]] : [];
        return $acrescimo + [
            'componentes' => $comps, 'casas' => (int) ($r['casas'] ?? 0), 'arredondamento' => (string) ($r['arredondamento'] ?? ''),
            'momento' => (string) ($r['momento'] ?? ''), 'semNota' => (string) ($r['semNota'] ?? ''), 'componenteVazio' => (string) ($r['componenteVazio'] ?? ''),
            'notaMinima' => preg_match('/^\d{1,2}(\.\d{1,2})?$/', $nm) ? number_format((float) $nm, 2, '.', '') : $nm,
        ];
    }

    private static function codigoDe(string $nome, array $usados): string
    {
        $base = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '_', iconv('UTF-8', 'ASCII//TRANSLIT', $nome) ?: $nome), '_')) ?: 'componente';
        $base = substr($base, 0, 34);
        $cod = $base;
        for ($i = 2; isset($usados[$cod]); $i++) {
            $cod = $base . '_' . $i;
        }
        return $cod;
    }

    /**
     * SIMULADOR (D-399): a regra do formulário (não salva) e as notas de teste (`sim[i][componente|nota|peso]`),
     * calculadas pelo MESMO motor. `final` = como no encerramento; `encontros`/`faltas` para a frequência.
     */
    public static function simular(array $post, string $frequenciaMinima): array
    {
        $regra = self::doFormulario($post);
        $erros = Motor::validar($regra);
        $ativs = [];
        foreach ((array) ($post['sim'] ?? []) as $i => $s) {
            if (!is_array($s)) {
                continue;
            }
            $txt = trim((string) ($s['nota'] ?? ''));
            $nota = $txt === '' ? null : AdmsCalculoNotas::centesimos($txt);
            if ($txt !== '' && $nota === null) {
                $erros[] = 'Nota de teste ' . ((int) $i + 1) . ': use um número de 0 a 10, com até 2 casas.';
            }
            $peso = str_replace(',', '.', trim((string) ($s['peso'] ?? '10'))) ?: '10';
            if (Motor::pesoInt($peso) === null) {
                $erros[] = 'Peso da atividade de teste ' . ((int) $i + 1) . ': número de 0 a 999, com até 4 casas.';
            }
            $ativs[] = ['id' => (int) $i + 1, 'nome' => trim((string) ($s['nome'] ?? '')) ?: 'Atividade ' . ((int) $i + 1), 'nota' => $nota, 'peso' => $peso,
                'componente' => trim((string) ($s['componente'] ?? '')) ?: null];
        }
        /* Onda 4, Etapa 5: o acréscimo de teste (uma atividade de acréscimo, fora dos componentes) */
        $acrTxt = trim((string) ($post['simAcrescimo'] ?? ''));
        if ($acrTxt !== '') {
            $acrNota = AdmsCalculoNotas::centesimos($acrTxt);
            if ($acrNota === null) {
                $erros[] = 'Acréscimo de teste: use um número de 0 a 10, com até 2 casas.';
            } else {
                $ativs[] = ['id' => count($ativs) + 1, 'nome' => 'Acréscimo', 'nota' => $acrNota, 'peso' => '0', 'componente' => null, 'acrescimo' => true];
            }
        }
        if ($erros) {
            return ['ok' => false, 'erros' => array_values(array_unique($erros))];
        }
        $enc = trim((string) ($post['encontros'] ?? ''));
        $r = Motor::calcular($ativs, $regra, ['final' => !empty($post['final']), 'encontros' => $enc === '' ? null : max(1, (int) $enc),
            'faltas' => (int) ($post['faltas'] ?? 0), 'compensadas' => 0, 'frequenciaMinima' => $frequenciaMinima]);
        return ['ok' => true, 'media' => $r['media'], 'mediaTexto' => $r['media'] === null ? '—' : str_replace('.', ',', $r['media']),
            'frequencia' => $r['frequencia'], 'resultado' => ($r['resultado'] === null && !$r['motivosPendencia']) ? null : AdmsCalculoNotas::rotuloResultado($r['resultado'], $r['motivo'], $r['motivosPendencia']),
            'pendente' => (bool) $r['motivosPendencia'], 'mediaProvisoria' => $r['mediaProvisoria'], 'avisosRegra' => Motor::avisosRegra($regra), // E10 (D-421, D-422); 1.15.2 (D-431)
            'travas' => self::travasSimulador($regra['componentes'], $ativs), // 1.15.2 (D-430)
            'linhas' => $r['memoria']['linhas'], 'final' => !empty($post['final'])];
    }

    /* ================================================================== CATÁLOGO (1.16.0) */

    /** "Atividades 30% · Prova 70% obrigatória (máx. 2)" — o resumo dos componentes. */
    public static function resumo(array $regra): string
    {
        return implode(' · ', array_map(fn($c) => $c['nome'] . ' ' . str_replace('.', ',', self::pesoTexto((string) $c['peso'])) . '%'
            . (Motor::minimo($c) > 0 ? ' obrigatória' . (Motor::minimo($c) > 1 ? ' (mín. ' . Motor::minimo($c) . ')' : '') : '')
            . (Motor::maximo($c) !== null ? ' (máx. ' . Motor::maximo($c) . ')' : ''), $regra['componentes'] ?? []));
    }

    /** As ofertas ABERTAS e a regra que vale para cada uma: [ofertaId => ['oferta' => linha, 'ef' => efetiva]]. */
    public static function ofertasAbertas(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT o.id, o.materia, o.codtur, o.ano, o.curso_id, c.nome AS curso FROM rm_professores_materias o LEFT JOIN rm_cursos c ON c.id = o.curso_id
                         WHERE o.encerrada_em IS NULL ORDER BY o.ano DESC, o.materia, o.id");
        $saida = [];
        foreach ($ler->getResultado() ?: [] as $o) {
            $saida[(int) $o['id']] = ['oferta' => $o, 'ef' => self::efetivaOferta((int) $o['id'])];
        }
        return $saida;
    }

    /** "Matéria — TURMA ANO" (o rótulo de uma oferta nos avisos). */
    public static function rotuloOferta(array $o): string
    {
        return trim($o['materia'] . ' — ' . $o['codtur'] . ' ' . $o['ano']);
    }

    /** O catálogo: uma linha por regra, com o resumo e o uso. `$soAtivas` = só as que podem ser escolhidas. */
    public static function catalogo(bool $soAtivas = false): array
    {
        try {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT r.*, (SELECT COUNT(*) FROM uni_rh_cursos_disciplinas_grade l WHERE l.regra_media_id = r.id) AS disciplinas
                              FROM rm_media_regras r " . ($soAtivas ? "WHERE r.situacao = 1 " : '') . "ORDER BY r.padrao DESC, r.situacao, r.nome");
            $regras = $ler->getResultado() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
        $abertas = $soAtivas ? [] : self::ofertasAbertas();
        $usoAbertas = [];
        foreach ($abertas as $a) {
            $usoAbertas[(int) $a['ef']['regraId']] = ($usoAbertas[(int) $a['ef']['regraId']] ?? 0) + 1;
        }
        $semRegra = 0;
        if (!$soAtivas) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT COUNT(*) AS n FROM uni_rh_cursos_disciplinas_grade WHERE regra_media_id IS NULL");
            $semRegra = (int) ($ler->getResultado()[0]['n'] ?? 0);
        }
        $saida = [];
        foreach ($regras as $r) {
            $v = self::versao((int) $r['id'], (int) $r['versao_atual'], 'catalogo', 0);
            $saida[] = ['id' => (int) $r['id'], 'nome' => (string) $r['nome'], 'descricao' => (string) ($r['descricao'] ?? ''), 'padrao' => (int) $r['padrao'] === 1,
                'situacao' => (int) $r['situacao'], 'resumo' => $v ? self::resumo($v['regra']) : '',
                'disciplinas' => (int) $r['disciplinas'] + ((int) $r['padrao'] === 1 ? $semRegra : 0), 'disciplinasProprias' => (int) $r['disciplinas'],
                'ofertasAbertas' => $usoAbertas[(int) $r['id']] ?? 0];
        }
        return $saida;
    }

    /** Onde a regra é usada: as disciplinas da matriz associadas e as ofertas abertas que a seguem (com a origem). */
    public static function ondeUsada(int $regraId): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT l.id, mt.nome AS materia, c.nome AS curso, g.versao AS grade, g.id AS grade_id
                          FROM uni_rh_cursos_disciplinas_grade l JOIN rm_materias mt ON mt.id = l.materia_id
                          JOIN rm_cursos_grades g ON g.id = l.grade_id JOIN rm_cursos c ON c.id = g.curso_id
                         WHERE l.regra_media_id = :r ORDER BY c.nome, g.versao, l.ordem", "r={$regraId}");
        $disc = $ler->getResultado() ?: [];
        $cursos = []; /* 1.16.1 (D-447): os cursos que usam a regra no TCC e nos documentos */
        try {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT id, nome FROM rm_cursos WHERE regra_media_id = :r ORDER BY nome", "r={$regraId}");
            $cursos = $ler->getResultado() ?: [];
        } catch (\Throwable $e) {
            $cursos = [];
        }
        $ofertas = [];
        foreach (self::ofertasAbertas() as $id => $a) {
            if ((int) $a['ef']['regraId'] === $regraId) {
                $ofertas[] = ['id' => $id, 'rotulo' => self::rotuloOferta($a['oferta']), 'curso' => (string) ($a['oferta']['curso'] ?? ''), 'origem' => $a['ef']['origem']];
            }
        }
        return ['disciplinas' => $disc, 'ofertas' => $ofertas, 'cursos' => $cursos];
    }

    /** Quantos encerramentos usaram alguma versão da regra (guardam a regra da época e não mudam). */
    public static function encerramentos(int $regraId): int
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT COUNT(*) AS n FROM rm_media_fechamentos f JOIN rm_media_regra_versoes v ON v.id = f.regra_versao_id WHERE v.regra_id = :r", "r={$regraId}");
        return (int) ($ler->getResultado()[0]['n'] ?? 0);
    }

    /** A última alteração: data e usuário (do cabeçalho ou da versão mais nova). */
    public static function ultimaAlteracao(int $regraId): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT x.quando, u.nome AS usuario FROM (
                          SELECT COALESCE(r.modified, r.created) AS quando, COALESCE(r.usuario_mod, r.usuario_cre) AS uid FROM rm_media_regras r WHERE r.id = :r
                          UNION ALL SELECT v.created, v.usuario_cre FROM rm_media_regra_versoes v WHERE v.regra_id = :r2) x
                          LEFT JOIN adms_usuarios u ON u.id = x.uid ORDER BY x.quando DESC LIMIT :limit", "r={$regraId}&r2={$regraId}&limit=1");
        $l = $ler->getResultado()[0] ?? [];
        return ['quando' => $l['quando'] ?? null, 'usuario' => (string) ($l['usuario'] ?? '')];
    }

    /* ================================================================== GRAVAÇÃO */

    private static function texto(array $post, string $k, int $max): string
    {
        return mb_substr(trim((string) preg_replace('/\s+/u', ' ', (string) ($post[$k] ?? ''))), 0, $max);
    }

    /**
     * SALVAR uma regra do catálogo (`$regraId` = 0: nova). Recusa o que `Motor::validar` recusa e nome vazio. Regra EM
     * USO (associada ou seguida por oferta aberta) e alterada: sem `$confirmado`, nada é gravado e `getConfirmacao()` traz
     * as disciplinas e as ofertas ABERTAS afetadas e as que ficariam fora dos limites (D-439). Numa transação: cabeçalho
     * (nome, descrição), versão (a atual é corrigida no lugar se ainda não foi usada num fechamento; senão nasce a
     * seguinte — só por dentro) e componentes; se for o padrão, espelha casas e nota mínima nos parâmetros.
     */
    public function salvar(int $regraId, array $post, int $usuarioId, bool $confirmado = false): bool
    {
        $this->erros = [];
        $this->avisos = [];
        $this->confirmacao = null;
        $cab = $regraId > 0 ? self::cabecalho($regraId) : null;
        if ($regraId > 0 && !$cab) {
            $this->erros[] = 'Regra de média não encontrada.';
            return false;
        }
        $nome = self::texto($post, 'nome', 120);
        $descricao = self::texto($post, 'descricao', 500);
        $regra = self::doFormulario($post);
        $this->erros = array_merge($nome === '' ? ['Nome da regra: obrigatório (até 120 caracteres).'] : [], Motor::validar($regra));
        if ($nome !== '' && self::nomeEmUso($nome, $regraId)) {
            $this->erros[] = "Já existe uma regra com o nome \"{$nome}\".";
        }
        if ($this->erros) {
            return false;
        }
        $atual = $cab ? self::versao((int) $cab['id'], (int) $cab['versao_atual'], 'catalogo', 0) : null;
        $mudouRegra = !$atual || self::canonica($atual['regra']) !== self::canonica($regra);
        $mudouCab = !$cab || $nome !== (string) $cab['nome'] || $descricao !== (string) ($cab['descricao'] ?? '');
        if (!$mudouRegra && !$mudouCab) {
            $this->avisarDepoisDeSalvar($regra, $regraId);
            return true; /* nada mudou */
        }
        if ($cab && $mudouRegra && !$confirmado) {
            $onde = self::ondeUsada($regraId);
            $padrao = (int) $cab['padrao'] === 1;
            if ($onde['disciplinas'] || $onde['ofertas'] || $padrao) {
                $this->confirmacao = ['disciplinas' => $onde['disciplinas'], 'ofertas' => $onde['ofertas'], 'padrao' => $padrao,
                    'fora' => self::ofertasForaDosLimites($regraId, $regra), 'encerramentos' => self::encerramentos($regraId)];
                return false;
            }
        }
        $novaVersao = $atual && $mudouRegra && self::versaoUsada((int) $atual['versaoId']);
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $cab, $atual, $novaVersao, $mudouRegra, $regra, $nome, $descricao, $usuarioId) {
            $agora = date('Y-m-d H:i:s');
            if (!$cab) {
                $c = new \App\adms\Models\helper\AdmsCreate();
                $c->exeCreate('rm_media_regras', ['nome' => $nome, 'descricao' => $descricao ?: null, 'padrao' => 0, 'escopo' => 'catalogo', 'escopo_id' => 0,
                    'versao_atual' => 1, 'situacao' => 1, 'usuario_cre' => $usuarioId, 'created' => $agora]);
                $regraId = (int) $T::exigir($c->getResultado(), 'regra');
                $versao = 1;
            } else {
                $regraId = (int) $cab['id'];
                $versao = $novaVersao ? (int) $cab['versao_atual'] + 1 : (int) $cab['versao_atual'];
                $u = new \App\adms\Models\helper\AdmsUpdate();
                $u->exeUpdate('rm_media_regras', ['nome' => $nome, 'descricao' => $descricao ?: null, 'versao_atual' => $versao, 'usuario_mod' => $usuarioId, 'modified' => $agora], 'WHERE id = :id', "id={$regraId}");
                $T::exigir($u->getResultado() === null ? null : true, 'regra');
            }
            $this->novoId = $regraId;
            if (!$mudouRegra) {
                return true;
            }
            $campos = ['casas' => $regra['casas'], 'arredondamento' => $regra['arredondamento'], 'momento' => $regra['momento'], 'sem_nota' => $regra['semNota'],
                'componente_vazio' => $regra['componenteVazio'], 'nota_minima' => $regra['notaMinima']];
            if ($novaVersao || !$atual) {
                $c = new \App\adms\Models\helper\AdmsCreate();
                $c->exeCreate('rm_media_regra_versoes', $campos + ['regra_id' => $regraId, 'versao' => $versao, 'usuario_cre' => $usuarioId, 'created' => $agora]);
                $versaoId = (int) $T::exigir($c->getResultado(), 'versão da regra');
            } else {
                $versaoId = (int) $atual['versaoId'];
                $u = new \App\adms\Models\helper\AdmsUpdate();
                $u->exeUpdate('rm_media_regra_versoes', $campos, 'WHERE id = :id', "id={$versaoId}");
                $T::exigir($u->getResultado() === null ? null : true, 'versão da regra');
                $d = new \App\adms\Models\helper\AdmsDeletar();
                $d->exeDelete('rm_media_regra_componentes', 'WHERE versao_id = :v', "v={$versaoId}");
                $T::exigir($d->getResultado() === false ? null : true, 'componentes antigos');
            }
            self::gravarComponentes($T, $versaoId, $regra['componentes']);
            self::gravarAcrescimo($T, $versaoId, $regra);
            if ($cab && (int) $cab['padrao'] === 1) {
                self::espelharParametros($T, $regra, $usuarioId);
            }
            return true;
        });
        self::limparCache();
        if (!$ok) {
            $this->erros[] = 'Nada foi salvo (' . $T::ultimoErro() . ').';
            return false;
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs($cab ? 427 : 428, 200, 3, "Regra de média salva: id={$this->novoId}" . ($novaVersao ? ' (versão nova)' : ''));
        $this->avisarDepoisDeSalvar($regra, $this->novoId);
        return true;
    }

    private static function nomeEmUso(string $nome, int $menosId): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id FROM rm_media_regras WHERE nome = :n AND id <> :i LIMIT :limit", "n=" . urlencode($nome) . "&i={$menosId}&limit=1");
        return !empty($ler->getResultado());
    }

    /** Onda 4, Etapa 5: o acréscimo da versão (só a linha quando permitido; sem permitir, nenhuma linha). */
    private static function gravarAcrescimo(string $T, int $versaoId, array $regra): void
    {
        $pdo = \Core\Transacao::pdo();
        $pdo->prepare('DELETE FROM rm_media_regra_acrescimo WHERE versao_id = ?')->execute([$versaoId]);
        if (!empty($regra['acrescimo']['permitir'])) {
            $T::exigir($pdo->prepare('INSERT INTO rm_media_regra_acrescimo (versao_id, permitir, limite) VALUES (?, 1, ?)')
                ->execute([$versaoId, $regra['acrescimo']['limite'] ?? null]) ? true : null, 'o acréscimo da regra');
        }
    }

    /** Onda 4, Etapa 5: a regra que vale para a oferta permite o acréscimo? */
    public static function permiteAcrescimo(int $ofertaId): bool
    {
        return !empty(self::efetivaOferta($ofertaId)['regra']['acrescimo']['permitir']);
    }

    private static function gravarComponentes(string $T, int $versaoId, array $componentes): void
    {
        foreach ($componentes as $comp) {
            $c = new \App\adms\Models\helper\AdmsCreate();
            $c->exeCreate('rm_media_regra_componentes', ['versao_id' => $versaoId, 'codigo' => $comp['codigo'], 'nome' => $comp['nome'],
                'peso' => number_format(Motor::pesoInt((string) $comp['peso']) / 10000, 4, '.', ''), 'modo' => $comp['modo'],
                'parametro_n' => ($comp['n'] ?? null) !== null && $comp['n'] !== '' ? (int) $comp['n'] : null, 'parametro_tipo' => $comp['tipoN'] ?? null, 'ordem' => $comp['ordem'],
                'min_atividades' => Motor::minimo($comp), 'max_atividades' => Motor::maximo($comp)]); // E10 (D-420)
            $T::exigir($c->getResultado(), 'componente ' . $comp['codigo']);
        }
    }

    /** O padrão espelha casas e nota mínima nos parâmetros que o resto do sistema lê (TCC, legenda, boletim). */
    private static function espelharParametros(string $T, array $regra, int $usuarioId): void
    {
        $P = \App\adms\Models\helper\AdmsParametro::class;
        $P::gravar('nota.casas', (string) $regra['casas'], $usuarioId) || $T::falhar('parâmetro nota.casas');
        $P::gravar('nota.media_minima', (string) $regra['notaMinima'], $usuarioId) || $T::falhar('parâmetro nota.media_minima');
    }

    /**
     * E10 (D-421, D-423): salvar é PERMITIDO com avisos — nenhum componente obrigatório; ofertas abertas que seguem
     * esta regra e ficaram abaixo do mínimo ou acima do máximo (o encerramento delas fica bloqueado até ajustar).
     * Ofertas encerradas não entram: o fechamento guardou a regra usada.
     */
    private function avisarDepoisDeSalvar(array $regra, int $regraId): void
    {
        $this->avisos = Motor::avisosRegra($regra);
        $fora = $regraId > 0 ? self::ofertasForaDosLimites($regraId) : [];
        if ($fora) {
            $this->avisos[] = 'Disciplinas abertas fora dos limites desta regra (o encerramento delas fica bloqueado até ajustar as atividades): ' . implode(' | ', $fora) . '.';
        }
    }

    /** DUPLICAR (D-441): "Cópia de <nome>", com a regra atual, SEM associações, ativa. Numa transação. */
    public function duplicar(int $regraId, int $usuarioId): bool
    {
        $this->erros = [];
        $cab = self::cabecalho($regraId);
        $v = $cab ? self::versao($regraId, (int) $cab['versao_atual'], 'catalogo', 0) : null;
        if (!$v) {
            $this->erros[] = 'Regra de média não encontrada.';
            return false;
        }
        $base = mb_substr('Cópia de ' . $cab['nome'], 0, 112);
        $nome = $base;
        for ($i = 2; self::nomeEmUso($nome, 0); $i++) {
            $nome = $base . ' (' . $i . ')';
        }
        $r = $v['regra'];
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $cab, $r, $nome, $usuarioId) {
            $agora = date('Y-m-d H:i:s');
            $c = new \App\adms\Models\helper\AdmsCreate();
            $c->exeCreate('rm_media_regras', ['nome' => $nome, 'descricao' => $cab['descricao'] ?? null, 'padrao' => 0, 'escopo' => 'catalogo', 'escopo_id' => 0,
                'versao_atual' => 1, 'situacao' => 1, 'usuario_cre' => $usuarioId, 'created' => $agora]);
            $id = (int) $T::exigir($c->getResultado(), 'regra');
            $c = new \App\adms\Models\helper\AdmsCreate();
            $c->exeCreate('rm_media_regra_versoes', ['regra_id' => $id, 'versao' => 1, 'casas' => $r['casas'], 'arredondamento' => $r['arredondamento'], 'momento' => $r['momento'],
                'sem_nota' => $r['semNota'], 'componente_vazio' => $r['componenteVazio'], 'nota_minima' => $r['notaMinima'], 'usuario_cre' => $usuarioId, 'created' => $agora]);
            self::gravarComponentes($T, (int) $T::exigir($c->getResultado(), 'versão'), $r['componentes']);
            $this->novoId = $id;
            return true;
        });
        if (!$ok) {
            $this->erros[] = 'Nada foi gravado (' . $T::ultimoErro() . ').';
            return false;
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(429, 200, 3, "Regra de média duplicada: {$regraId} -> {$this->novoId}");
        return true;
    }

    /**
     * INATIVAR (D-442): só sem uso — nenhuma disciplina da matriz associada e nenhuma oferta aberta seguindo a regra.
     * O padrão não pode. Recusa com a lista de onde está. `$reativar` = volta a ativa.
     */
    public function inativar(int $regraId, int $usuarioId, bool $reativar = false): bool
    {
        $this->erros = [];
        $cab = self::cabecalho($regraId);
        if (!$cab) {
            $this->erros[] = 'Regra de média não encontrada.';
            return false;
        }
        if (!$reativar) {
            if ((int) $cab['padrao'] === 1) {
                $this->erros[] = 'O Padrão da instituição não pode ser inativado. Escolha outro padrão antes.';
                return false;
            }
            $onde = self::ondeUsada($regraId);
            $proprias = array_filter($onde['ofertas'], fn($o) => true);
            if ($onde['disciplinas'] || $proprias || $onde['cursos']) {
                $this->erros[] = 'A regra está em uso e não pode ser inativada.';
                foreach ($onde['disciplinas'] as $d) {
                    $this->erros[] = 'Disciplina da matriz: ' . $d['materia'] . ' — ' . $d['curso'] . ', grade ' . (int) $d['grade'];
                }
                foreach ($proprias as $o) {
                    $this->erros[] = 'Oferta aberta: ' . $o['rotulo'] . ' (' . (self::ORIGENS[$o['origem']] ?? $o['origem']) . ')';
                }
                foreach ($onde['cursos'] as $c) { // 1.16.1 (D-447)
                    $this->erros[] = 'Curso (regra do TCC e dos documentos): ' . $c['nome'];
                }
                return false;
            }
        }
        $u = new \App\adms\Models\helper\AdmsUpdate();
        $u->exeUpdate('rm_media_regras', ['situacao' => $reativar ? 1 : 2, 'usuario_mod' => $usuarioId, 'modified' => date('Y-m-d H:i:s')], 'WHERE id = :id', "id={$regraId}");
        self::limparCache();
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(430, 200, 3, 'Regra de média ' . ($reativar ? 'reativada' : 'inativada') . ": id={$regraId}");
        return true;
    }

    /**
     * TROCAR O PADRÃO (D-440): ação explícita, só administrador. Sem `$confirmado`, `getConfirmacao()` conta as
     * disciplinas da matriz sem regra (que passam a seguir o novo padrão) e lista as ofertas abertas que mudam; as que
     * têm regra própria (da disciplina ou da oferta) não mudam. Numa transação: o padrão anterior deixa de ser, o novo
     * passa a ser, e os parâmetros espelham a regra nova.
     */
    public function definirPadrao(int $regraId, int $usuarioId, bool $confirmado = false): bool
    {
        $this->erros = [];
        $this->confirmacao = null;
        $novo = self::daRegra($regraId, 'padrao');
        if (!$novo || (int) $novo['situacao'] !== 1) {
            $this->erros[] = 'Escolha uma regra ATIVA para ser o padrão.';
            return false;
        }
        if ($novo['padrao']) {
            return true;
        }
        if (!$confirmado) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT COUNT(*) AS n FROM uni_rh_cursos_disciplinas_grade WHERE regra_media_id IS NULL");
            $padraoAtual = self::padrao();
            $ofertas = [];
            foreach (self::ofertasAbertas() as $id => $a) {
                if ($a['ef']['origem'] === 'padrao') {
                    $ofertas[] = self::rotuloOferta($a['oferta']);
                }
            }
            $this->confirmacao = ['disciplinas' => (int) ($ler->getResultado()[0]['n'] ?? 0), 'ofertas' => $ofertas,
                'de' => $padraoAtual['nome'] ?? '', 'para' => $novo['nome']];
            return false;
        }
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $regraId, $novo, $usuarioId) {
            $agora = date('Y-m-d H:i:s');
            $u = new \App\adms\Models\helper\AdmsUpdate();
            $u->exeUpdate('rm_media_regras', ['padrao' => 0, 'usuario_mod' => $usuarioId, 'modified' => $agora], 'WHERE padrao = :p AND id <> :id', "p=1&id={$regraId}");
            $T::exigir($u->getResultado() === null ? null : true, 'padrão anterior');
            $u = new \App\adms\Models\helper\AdmsUpdate();
            $u->exeUpdate('rm_media_regras', ['padrao' => 1, 'usuario_mod' => $usuarioId, 'modified' => $agora], 'WHERE id = :id', "id={$regraId}");
            $T::exigir($u->getResultado() === null ? null : true, 'novo padrão');
            self::espelharParametros($T, $novo['regra'], $usuarioId);
            return true;
        });
        self::limparCache();
        if (!$ok) {
            $this->erros[] = 'Nada foi gravado (' . $T::ultimoErro() . ').';
            return false;
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(431, 200, 3, "Padrão da instituição: regra id={$regraId}");
        return true;
    }

    /** As disciplinas da matriz para o lote (filtro por curso e grade), com a regra de cada uma. */
    public static function disciplinasParaLote(int $cursoId = 0, int $gradeId = 0): array
    {
        $onde = [];
        $par = [];
        if ($cursoId > 0) {
            $onde[] = 'g.curso_id = :c';
            $par[] = "c={$cursoId}";
        }
        if ($gradeId > 0) {
            $onde[] = 'g.id = :g';
            $par[] = "g={$gradeId}";
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT l.id, mt.nome AS materia, c.nome AS curso, c.id AS curso_id, g.id AS grade_id, g.versao AS grade, l.regra_media_id, r.nome AS regra
                          FROM uni_rh_cursos_disciplinas_grade l JOIN rm_materias mt ON mt.id = l.materia_id JOIN rm_cursos_grades g ON g.id = l.grade_id
                          JOIN rm_cursos c ON c.id = g.curso_id LEFT JOIN rm_media_regras r ON r.id = l.regra_media_id"
            . ($onde ? ' WHERE ' . implode(' AND ', $onde) : '') . " ORDER BY c.nome, g.versao, l.ordem, l.id", $par ? implode('&', $par) : null);
        return $ler->getResultado() ?: [];
    }

    /**
     * ASSOCIAR (D-438): a regra (ativa) ou o padrão (`$regraId` = null) às disciplinas da matriz dadas — na tela da
     * matriz (uma) e em lote (várias). Sem `$confirmado`, `getConfirmacao()` lista o que muda (disciplina, regra de
     * hoje → a nova). Numa transação.
     */
    public function associarDisciplinas(?int $regraId, array $linhas, int $usuarioId, bool $confirmado = false): bool
    {
        $this->erros = [];
        $this->confirmacao = null;
        $linhas = array_values(array_unique(array_filter(array_map('intval', $linhas))));
        if (!$linhas) {
            $this->erros[] = 'Escolha ao menos uma disciplina.';
            return false;
        }
        $nova = $regraId ? self::cabecalho($regraId) : null;
        if ($regraId && (!$nova || (int) $nova['situacao'] !== 1)) {
            $this->erros[] = 'Escolha uma regra de média ATIVA.';
            return false;
        }
        $todas = array_column(self::disciplinasParaLote(), null, 'id');
        $muda = [];
        foreach ($linhas as $l) {
            if (!isset($todas[$l])) {
                $this->erros[] = "Disciplina da matriz {$l} não encontrada.";
                continue;
            }
            if ((int) ($todas[$l]['regra_media_id'] ?? 0) !== (int) $regraId) {
                $muda[] = $todas[$l];
            }
        }
        if ($this->erros) {
            return false;
        }
        if (!$muda) {
            return true;
        }
        $nomeNova = $nova ? (string) $nova['nome'] : 'Padrão da instituição';
        if (!$confirmado) {
            $this->confirmacao = ['regra' => $nomeNova, 'linhas' => array_map(fn($d) => ['id' => (int) $d['id'], 'texto' => $d['materia'] . ' — ' . $d['curso'] . ', grade ' . (int) $d['grade'],
                'de' => $d['regra'] ?: 'Padrão da instituição', 'para' => $nomeNova], $muda)];
            return false;
        }
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $muda, $regraId, $usuarioId) {
            foreach ($muda as $d) {
                $u = new \App\adms\Models\helper\AdmsUpdate();
                $u->exeUpdate('uni_rh_cursos_disciplinas_grade', ['regra_media_id' => $regraId ?: null, 'usuario_mod' => $usuarioId], 'WHERE id = :id', "id={$d['id']}");
                $T::exigir($u->getResultado() === null ? null : true, 'disciplina ' . $d['id']);
            }
            return true;
        });
        self::limparCache();
        if (!$ok) {
            $this->erros[] = 'Nada foi gravado (' . $T::ultimoErro() . ').';
            return false;
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(432, 200, 3, 'Regra de média ' . ($regraId ?: 'padrão') . ' associada a ' . count($muda) . ' disciplina(s) da matriz');
        $fora = $regraId ? self::ofertasForaDosLimites($regraId) : [];
        $this->avisos = $fora ? ['Disciplinas abertas fora dos limites desta regra (o encerramento delas fica bloqueado até ajustar as atividades): ' . implode(' | ', $fora) . '.'] : [];
        return true;
    }

    /** O histórico de trocas de regra da oferta (mais nova primeiro). */
    public static function trocasDaOferta(int $ofertaId): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT t.*, ra.nome AS de, rn.nome AS para, u.nome AS usuario FROM rm_media_regra_trocas t
                          LEFT JOIN rm_media_regras ra ON ra.id = t.regra_anterior_id LEFT JOIN rm_media_regras rn ON rn.id = t.regra_nova_id
                          LEFT JOIN adms_usuarios u ON u.id = t.usuario_id WHERE t.rm_prof_mat_id = :o ORDER BY t.id DESC", "o={$ofertaId}");
        return $ler->getResultado() ?: [];
    }

    /**
     * TROCAR A REGRA DA OFERTA (D-438 c): só com a oferta ABERTA (também no POST forjado) e com justificativa.
     * `$regraId` null = volta a seguir a regra da disciplina. Numa transação: a oferta e o registro da troca.
     */
    public function trocarRegraOferta(int $ofertaId, ?int $regraId, string $justificativa, int $usuarioId): bool
    {
        $this->erros = [];
        $this->avisos = [];
        $o = AdmsCalculoNotas::dadosOferta($ofertaId);
        if (!$o) {
            $this->erros[] = 'Oferta não encontrada.';
            return false;
        }
        if (!empty($o['encerrada_em'])) {
            $this->erros[] = 'A oferta está ENCERRADA: a regra não muda (o encerramento guardou a regra que valeu). Só o administrador reabre, com observação.';
            return false;
        }
        $justificativa = mb_substr(trim((string) preg_replace('/\s+/u', ' ', $justificativa)), 0, 500);
        if (mb_strlen($justificativa) < 5) {
            $this->erros[] = 'Justificativa: escreva o motivo da troca (ao menos 5 caracteres).';
        }
        if ($regraId) {
            $c = self::cabecalho($regraId);
            if (!$c || (int) $c['situacao'] !== 1) {
                $this->erros[] = 'Escolha uma regra de média ATIVA.';
            }
        }
        if ($this->erros) {
            return false;
        }
        $antes = self::efetivaOferta($ofertaId);
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $ofertaId, $regraId, $antes, $justificativa, $usuarioId) {
            $u = new \App\adms\Models\helper\AdmsUpdate();
            $u->exeUpdate('rm_professores_materias', ['regra_media_id' => $regraId ?: null], 'WHERE id = :id', "id={$ofertaId}");
            $T::exigir($u->getResultado() === null ? null : true, 'oferta');
            $c = new \App\adms\Models\helper\AdmsCreate();
            $c->exeCreate('rm_media_regra_trocas', ['rm_prof_mat_id' => $ofertaId, 'regra_anterior_id' => $antes['regraId'] ?: null, 'regra_nova_id' => $regraId ?: null,
                'justificativa' => $justificativa, 'usuario_id' => $usuarioId, 'created' => date('Y-m-d H:i:s')]);
            $T::exigir($c->getResultado(), 'registro da troca');
            return true;
        });
        self::limparCache();
        if (!$ok) {
            $this->erros[] = 'Nada foi gravado (' . $T::ultimoErro() . ').';
            return false;
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(419, 200, 3, "Regra de média da oferta {$ofertaId}: " . ($regraId ?: 'a da disciplina'));
        $l = self::limitesOferta($ofertaId);
        if ($l['faltam'] || $l['sobram']) {
            $this->avisos[] = 'A oferta ficou fora dos limites da regra (o encerramento fica bloqueado até ajustar as atividades): '
                . implode('; ', array_merge($l['faltam'] ? ['falta cadastrar ' . implode(', ', $l['faltam'])] : [], $l['sobram'])) . '.';
        }
        return true;
    }

    /** Forma canônica para comparar duas regras (sem a ordem numérica; os componentes na ordem dada). */
    public static function canonica(array $r): string
    {
        $c = array_map(fn($x) => [(string) $x['codigo'], trim((string) $x['nome']), (string) (Motor::pesoInt((string) $x['peso']) ?? $x['peso']), (string) $x['modo'],
            (string) ($x['n'] ?? ''), (string) ($x['tipoN'] ?? ''), Motor::minimo($x), (string) (Motor::maximo($x) ?? '')], $r['componentes']); // E10: + mínimo e máximo
        /* Onda 4, Etapa 5: o acréscimo entra só quando permitido — a regra sem ele tem a mesma assinatura de antes */
        $acr = !empty($r['acrescimo']['permitir']) ? [['acrescimo', ($r['acrescimo']['limite'] ?? null) === null ? '' : number_format((float) $r['acrescimo']['limite'], 2, '.', '')]] : [];
        return json_encode(array_merge([$c, (int) $r['casas'], $r['arredondamento'], $r['momento'], $r['semNota'], $r['componenteVazio'], number_format((float) $r['notaMinima'], 2, '.', '')], $acr));
    }
}
