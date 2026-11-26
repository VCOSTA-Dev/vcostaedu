<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 4) . '/core/Banco.php';
require_once dirname(__DIR__, 4) . '/core/AcessoFamilia.php';

/**
 * O PORTAL DO RESPONSÁVEL (Onda 7, Etapas 4 e 5; docs/RESPONSAVEIS.md) — só na escola, para o nível "Responsável".
 *   index       os filhos (com um só, entra direto nele);
 *   filho       a página do filho no ano: o cabeçalho (o nome, a turma, o ano) e os atalhos;
 *   boletim     o boletim do filho no ano (a mesma regra de liberação do aluno);
 *   notas       as notas das atividades;
 *   frequencia  as faltas e a frequência de cada componente;
 *   horario     o horário;
 *   turma       os dados da turma (a série, a turma, o turno, a sala, os componentes e os professores);
 *   atestado    o atestado de matrícula do filho (ver, emitir — POST —, o PDF).
 * TODA rota (menos index) recebe o aluno e o ano na URL (/familia/<rota>/<aluno>?ano=…) e passa por exigir(), que
 * pergunta ao PONTO ÚNICO (VcAcessoFamilia::pode) a cada requisição: a escolha guardada na sessão não é confiada. Nada
 * do Moodle, da senha do aluno, dos outros responsáveis ou do financeiro aparece aqui.
 */
class Familia
{
    private array $dados = [];
    private ?\VcAcessoFamilia $acesso = null;

    private function acesso(): \VcAcessoFamilia
    {
        return $this->acesso ??= new \VcAcessoFamilia(\VcBanco::daRequisicao());
    }

    /** O responsável da sessão (0: não é um responsável — volta ao login). */
    private function responsavel(): int
    {
        $id = (int) ($_SESSION['vc_responsavel_id'] ?? 0);
        if ($id <= 0 || (int) ($_SESSION['academico_nivel'] ?? 0) !== 2 || !$this->acesso()->ativo($id)) {
            unset($_SESSION['vc_responsavel_id'], $_SESSION['vc_familia_filho'], $_SESSION['academico_nivel'], $_SESSION['academico_nome']);
            header('Location: ' . URLACAD . 'login/acesso');
            exit;
        }
        return $id;
    }

    /**
     * A BARREIRA: o aluno e o ano da URL, conferidos a cada requisição. Recusado: a página de recusa (403), sem dizer
     * se o aluno existe. @return array [responsável, aluno (linha), ano]
     */
    private function exigir($alunoId): array
    {
        $resp = $this->responsavel();
        $aluno = (int) $alunoId;
        $anos = $aluno > 0 ? $this->acesso()->anos($resp, $aluno) : [];
        $ano = (int) ($_GET['ano'] ?? ($anos[0] ?? 0));
        if (!$anos || !$this->acesso()->pode($resp, $aluno, $ano, \VcAcessoFamilia::ACADEMICO)) {
            $this->recusar();
        }
        $st = \VcBanco::daRequisicao()->prepare('SELECT id, nome, nome_social, rm FROM col_usuarios WHERE id = ? AND col_niveis_acesso_id = 1');
        $st->execute([$aluno]);
        $a = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$a) {
            $this->recusar();
        }
        $_SESSION['vc_familia_filho'] = $aluno; // só a lembrança da escolha (para o menu); nunca a decisão
        $this->dados['responsavelId'] = $resp;
        $this->dados['filho'] = $a;
        $this->dados['ano'] = $ano;
        $this->dados['anos'] = $anos;
        $this->dados['filhos'] = $this->acesso()->filhos($resp);
        $this->dados['turma'] = self::turmaDoAno((string) $a['rm'], $ano);
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        return [$resp, $a, $ano];
    }

    private function recusar(): void
    {
        http_response_code(403);
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        (new \Core\ConfigView('adms/Views/familia/recusado', $this->dados))->renderizar('tabler');
        exit;
    }

    /** A turma do aluno no ano (a escola): a série, o nome, o turno, a sala. */
    public static function turmaDoAno(string $rm, int $ano): ?array
    {
        $st = \VcBanco::daRequisicao()->prepare('SELECT t.id, t.nome, t.sala, m.codnome AS serie, p.nome AS turno, c.nome AS segmento
              FROM esc_turma_alunos a INNER JOIN esc_turmas t ON t.id = a.turma_id INNER JOIN rm_cursos_turma_series m ON m.id = t.modulo_id
              INNER JOIN rm_cursos c ON c.id = t.curso_id LEFT JOIN rm_cursos_periodos p ON p.id = t.turno_id
             WHERE a.rm = ? AND t.ano = ? ORDER BY a.saida IS NULL DESC, a.id DESC LIMIT 1');
        $st->execute([$rm, $ano]);
        return $st->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    private function ver(string $view, int $pagina): void
    {
        $this->dados['paginaAtiva'] = $pagina;
        $this->dados['rotaFamilia'] = $view;
        (new \Core\ConfigView('adms/Views/familia/' . $view, $this->dados))->renderizar('tabler');
    }

    public function index(): void
    {
        $resp = $this->responsavel();
        $filhos = $this->acesso()->filhos($resp);
        if (count($filhos) === 1) {
            header('Location: ' . URLACAD . 'familia/filho/' . (int) $filhos[0]['id']);
            return;
        }
        $this->dados['filhos'] = $filhos;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->ver('index', 32);
    }

    /**
     * O RESUMO do filho no ano (Onda 8, correção, Parte 4; D-83): a turma (o segmento, a série, o turno e a sala, que
     * subiram da antiga tela "A turma"), os números do ano tirados do boletim (a média geral, com a mínima da regra da
     * instalação; a frequência; as faltas; quantos componentes estão abaixo da mínima), as aulas de hoje e os
     * componentes com o professor, a média, as faltas e a frequência.
     */
    public function filho($aluno = null): void
    {
        [$resp, $a, $ano] = $this->exigir($aluno);
        /* Onda 8, financeiro, Parte 3 (D-98): o cartão "Ficha financeira" no Resumo, só para o responsável financeiro */
        $this->dados['cartaoFinanceiro'] = \App\adms\Models\helper\AdmsTipoPortal::financeiroLigado() // D-129: desligado, sem o cartão
            && $this->acesso()->podeVerFinanceiro($resp, (int) $a['id'], $ano)
            ? ['vencidos' => \App\adms\Models\financeiro\AdmsFichaFinanceira::vencidos((int) $a['id']), 'url' => URLACAD . 'familia/financeiro/' . (int) $a['id'] . '?ano=' . (int) $ano]
            : null;
        $b = \App\adms\Models\notas\AdmsBoletimCurso::filtrar(\App\adms\Models\notas\AdmsBoletimCurso::doAluno((string) $a['rm']), 0, $ano);
        /* o portal da família só existe na escola: a mínima é a das Regras da escola */
        $min = (string) \App\adms\Models\notas\AdmsCalculoNotas::confEscola()['mediaMinima'];
        $comps = [];
        foreach ($b['blocos'] ?? [] as $bl) {
            foreach ($bl['modulos'] ?? [] as $mod) {
                foreach ($mod['disciplinas'] ?? [] as $d) {
                    $comps[] = ['oferta' => (int) ($d['oferta'] ?? 0), 'materia' => (string) $d['materia'], 'media' => $d['media'], 'frequencia' => $d['frequencia'],
                        'faltas' => (int) ($d['faltas'] ?? 0)];
                }
            }
        }
        $ids = array_values(array_filter(array_column($comps, 'oferta')));
        $prof = [];
        if ($ids) {
            $st = \VcBanco::daRequisicao()->prepare("SELECT l.rm_prof_mat_id, GROUP_CONCAT(u.nome ORDER BY u.nome SEPARATOR ', ') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id = l.usuario_id
                WHERE l.situacao = 1 AND l.rm_prof_mat_id IN (" . implode(',', array_map('intval', $ids)) . ') GROUP BY l.rm_prof_mat_id');
            $st->execute();
            $prof = $st->fetchAll(\PDO::FETCH_KEY_PAIR);
        }
        /* correção da escola, Parte 1 (D-100): sem a média final do ano liberada (Liberação do boletim), nenhuma média
           anual aqui: nem a geral, nem a de cada disciplina, nem a conta de quantas estão abaixo */
        if (!\App\adms\Models\notas\AdmsCalculoNotas::finalLiberado((int) $ano, 'media_final')) {
            foreach ($comps as &$x) {
                $x['media'] = null;
            }
            unset($x);
            $this->dados['mediaOculta'] = true;
        }
        $c = static fn($v) => \Core\MotorMedia::centesimosDe((string) $v);
        $medias = array_values(array_filter(array_column($comps, 'media'), static fn($v) => $v !== null && $v !== ''));
        $freqs = array_values(array_filter(array_column($comps, 'frequencia'), static fn($v) => $v !== null && $v !== ''));
        $this->dados['resumo'] = [
            'componentes' => array_map(static fn($x) => $x + ['professores' => (string) ($prof[$x['oferta']] ?? '')], $comps),
            'mediaMinima' => $min,
            'mediaGeral' => $medias ? number_format(array_sum(array_map($c, $medias)) / count($medias) / 100, 1, '.', '') : null,
            'frequencia' => $freqs ? number_format(array_sum(array_map($c, $freqs)) / count($freqs) / 100, 1, '.', '') : null,
            'faltas' => array_sum(array_column($comps, 'faltas')),
            'abaixo' => count(array_filter($medias, static fn($v) => $c($v) < $c($min))),
            'hoje' => array_values(array_filter((new \App\adms\Models\academico\AdmsHorarioAluno())->listarHorarioPorRmAno((int) $a['rm'], $ano) ?? [],
                static fn($x) => (int) ($x['dia_id'] ?? 0) === (int) date('N') + 1)),
        ];
        $this->ver('filho', 33);
    }

    public function boletim($aluno = null): void
    {
        [, $a, $ano] = $this->exigir($aluno);
        $b = \App\adms\Models\notas\AdmsBoletimCurso::doAluno((string) $a['rm']);
        $this->dados['boletimCurso'] = \App\adms\Models\notas\AdmsBoletimCurso::filtrar($b, 0, $ano); // só o ano em que tem acesso
        $this->ver('boletim', 34);
    }

    public function notas($aluno = null): void
    {
        [, $a, $ano] = $this->exigir($aluno);
        $m = new \App\adms\Models\academico\AdmsNotasAtividades();
        $disc = $m->listarDisciplinasPorRmAno((int) $a['rm'], $ano);
        $ids = array_values(array_filter(array_map(static fn($d) => (int) ($d['rm_prof_mat_id'] ?? 0), $disc)));
        $this->dados['disciplinas'] = $disc;
        $this->dados['notas'] = $ids ? $m->listarNotasPorRmDisciplinas((int) $a['rm'], $ids) : [];
        $this->ver('notas', 35);
    }

    public function frequencia($aluno = null): void
    {
        [, $a, $ano] = $this->exigir($aluno);
        $b = \App\adms\Models\notas\AdmsBoletimCurso::filtrar(\App\adms\Models\notas\AdmsBoletimCurso::doAluno((string) $a['rm']), 0, $ano);
        $linhas = [];
        foreach ($b['blocos'] ?? [] as $bl) {
            foreach ($bl['modulos'] as $mod) {
                foreach ($mod['disciplinas'] as $d) {
                    $linhas[] = ['materia' => $d['materia'], 'faltas' => (int) $d['faltas'], 'frequencia' => $d['frequencia']];
                }
            }
        }
        $this->dados['frequencia'] = $linhas;
        $this->ver('frequencia', 36);
    }

    public function horario($aluno = null): void
    {
        [, $a, $ano] = $this->exigir($aluno);
        $this->dados['horario'] = (new \App\adms\Models\academico\AdmsHorarioAluno())->listarHorarioPorRmAno((int) $a['rm'], $ano) ?? [];
        $this->ver('horario', 37);
    }

    /** Onda 8, correção (D-83): a turma subiu para o Resumo; o endereço antigo leva a ele (o mesmo filho e o mesmo ano). */
    public function turma($aluno = null): void
    {
        [, $a, $ano] = $this->exigir($aluno);
        header('Location: ' . URLACAD . 'familia/filho/' . (int) $a['id'] . '?ano=' . (int) $ano);
    }

    public function atestado($aluno = null): void
    {
        [$resp, $a] = $this->exigir($aluno);
        $M = \App\adms\Models\academico\AdmsAtestadoAluno::class;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $m = new $M();
            if ($m->emitir((int) $a['id'], (string) $a['nome'], (int) filter_input(INPUT_POST, 'matricula', FILTER_VALIDATE_INT))) {
                require_once dirname(__DIR__, 4) . '/core/ContaFamilia.php';
                (new \VcContaFamilia(\VcBanco::daRequisicao()))->auditar('atestado_emitido', $resp, ['aluno' => (int) $a['id'], 'ano' => (int) $this->dados['ano']]);
                $_SESSION['msg'] = "<div class='alert alert-success' id='atestadoEmitido'>Atestado de matrícula emitido. Abra o PDF na lista abaixo.</div>";
            } else {
                $_SESSION['msg'] = "<div class='alert alert-danger' id='atestadoErro'>" . htmlspecialchars((string) ($m->getErros()[0] ?? 'O atestado não foi emitido.'), ENT_QUOTES, 'UTF-8') . '</div>';
            }
            header('Location: ' . URLACAD . 'familia/atestado/' . (int) $a['id'] . '?ano=' . (int) $this->dados['ano']);
            return;
        }
        $this->dados['matriculas'] = $M::matriculas((int) $a['id']);
        $this->dados['emissoes'] = $M::emissoes((int) $a['id']);
        $this->ver('atestado', 39);
    }

    public function atestadoPdf($aluno = null): void
    {
        [, $a] = $this->exigir($aluno);
        $p = \App\adms\Models\academico\AdmsAtestadoAluno::pdf((int) $a['id'], (int) ($_GET['emissao'] ?? 0));
        if (!$p) {
            $this->recusar();
        }
        $pdf = (string) file_get_contents($p['arquivo']);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9_.-]/', '', $p['nome']) . '"');
        header('Content-Length: ' . strlen($pdf));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        echo $pdf;
    }

    /**
     * Onda 8, financeiro, Parte 3 (D-98): a ficha financeira do filho, só de leitura — só para o responsável FINANCEIRO
     * dele naquele ano (o titular e o acesso adicional, nunca: VcAcessoFamilia::podeVerFinanceiro); o filho de outra
     * família não passa nem do exigir (403).
     */
    public function financeiro($aluno = null): void
    {
        [$resp, $a, $ano] = $this->exigir($aluno);
        if (!$this->acesso()->podeVerFinanceiro($resp, (int) $a['id'], $ano)) {
            $this->recusar();
        }
        $filtro = (string) ($_GET['filtro'] ?? 'todos');
        $M = \App\adms\Models\financeiro\AdmsFichaFinanceira::class;
        $this->dados['filtroFinanceiro'] = isset($M::FILTROS[$filtro]) ? $filtro : 'todos';
        $this->dados['fichaFinanceira'] = $M::ficha((int) $a['id'], $this->dados['filtroFinanceiro']);
        /* D-99: o botão do recibo, só nos anos em que a pessoa é a responsável financeira (a mesma barreira do Familia::recibo) */
        $anos = array_unique(array_filter(array_map(static fn($t) => (int) ($t['recibo_ano'] ?? 0), $this->dados['fichaFinanceira']['titulos'])));
        $this->dados['anosRecibo'] = array_values(array_filter($anos, fn($x) => $this->acesso()->podeVerFinanceiro($resp, (int) $a['id'], $x)));
        $this->ver('financeiro', 42);
    }

    /**
     * D-99: o recibo de um título do filho, para baixar (col_paginas 44; familia/recibo/{aluno}?recibo={id}) — começa pela
     * barreira, como toda rota da família (o ponto único: VcBarreiraFamilia confere); depois, o recibo tem de ser daquele
     * filho, e a pessoa, a responsável financeira dele no ano do recibo (a mesma regra da ficha).
     */
    public function recibo($aluno = null): void
    {
        [$resp, $a] = $this->exigir($aluno);
        $r = \App\adms\Models\financeiro\AdmsFichaFinanceira::recibo((int) $a['id'], (int) ($_GET['recibo'] ?? 0));
        if (!$r || !$this->acesso()->podeVerFinanceiro($resp, (int) $a['id'], (int) $r['ano'])) {
            $this->recusar();
        }
        \App\adms\Models\financeiro\AdmsFichaFinanceira::mostrarRecibo($r, URLACAD . 'familia/financeiro/' . (int) $a['id'] . '?ano=' . (int) $r['ano']);
    }
}
