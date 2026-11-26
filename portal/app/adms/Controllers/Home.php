<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/*
 * Portal do aluno. Na VCOSTAEDU o USUÁRIO É O PRÓPRIO ALUNO (D-00b): o aluno
 * exibido é sempre o da sessão, e não há seletor de filhos nem perfil de
 * responsável. Nenhum parâmetro de URL escolhe outro aluno.
 */
class Home
{
    private $pagina = 2;
    private $dados;

    public function index($pagina = '')
    {
        $this->renderizarPagina((string) $pagina);
    }

    public function notasAtividades()
    {
        $this->renderizarPagina('notas-atividades');
    }

    public function horarioAluno()
    {
        $this->renderizarPagina('horario-aluno');
    }

    public function boletimAluno()
    {
        $this->renderizarPagina('boletim-aluno');
    }

    /** Onda 8, financeiro, Parte 3 (D-98): a ficha financeira do aluno da sessão, só de leitura (col_paginas 41). */
    public function financeiroAluno()
    {
        $this->renderizarPagina('financeiro-aluno');
    }

    /** D-99: o recibo de um título do aluno da sessão, para baixar (col_paginas 43); o recibo de outro aluno dá 403. */
    public function reciboAluno($id = null)
    {
        $aluno = (int) ($_SESSION['academico_id'] ?? 0);
        $r = $aluno > 0 ? \App\adms\Models\financeiro\AdmsFichaFinanceira::recibo($aluno, (int) $id) : null;
        if (!$r) {
            http_response_code(403);
            echo 'Recibo não encontrado.';
            return;
        }
        \App\adms\Models\financeiro\AdmsFichaFinanceira::mostrarRecibo($r, URLACAD . 'home/financeiro-aluno');
    }

    /** E6c: CENTRAL DE AJUDA do aluno (col_paginas 14): "Comece por aqui" e os guias do aluno. */
    public function ajuda()
    {
        $perfil = \App\adms\Models\helper\AdmsCentralGuias::perfil();
        $this->renderizarAjuda('adms/Views/ajuda/indice', [
            'perfil' => $perfil,
            'comece' => \App\adms\Models\helper\AdmsCentralGuias::comece($perfil),
            'guias' => \App\adms\Models\helper\AdmsCentralGuias::visiveis($perfil),
        ]);
    }

    /** E6c: um guia do aluno (col_paginas 15). Guia de outro perfil volta ao índice com a recusa. */
    public function guia($slug = null)
    {
        $G = \App\adms\Models\helper\AdmsCentralGuias::class;
        $slug = $G::slug($slug);
        $perfil = $G::perfil();
        if (!$G::podeVer($slug, $perfil)) {
            $_SESSION['msg'] = "<div class='alert alert-danger' id='guiaRecusado'>Este guia não é do seu perfil (ou não existe). Veja abaixo os guias que você pode abrir.</div>";
            header('Location: ' . URLACAD . 'home/ajuda');
            return;
        }
        $todos = $G::visiveis($perfil);
        $comece = array_keys($G::comece($perfil));
        $pos = array_search($slug, $comece, true);
        $this->renderizarAjuda('adms/Views/ajuda/guia', [
            'slug' => $slug, 'meta' => $todos[$slug], 'perfil' => $perfil,
            'html' => $G::conteudo($slug, URLACAD . 'home/guia-imagem/', URLACAD . 'home/guia/'),
            'relacionados' => array_intersect_key($todos, array_flip($todos[$slug]['relacionados'] ?? [])),
            'anterior' => $pos !== false && $pos > 0 ? [$comece[$pos - 1], $todos[$comece[$pos - 1]]['titulo']] : null,
            'proximo' => $pos !== false && isset($comece[$pos + 1]) ? [$comece[$pos + 1], $todos[$comece[$pos + 1]]['titulo']] : null,
            'passo' => $pos === false ? null : $pos + 1,
        ]);
    }

    /** 1.18.0 (D-475; col_paginas 25): os vídeos dos guias do aluno, com busca. */
    public function videos()
    {
        $perfil = \App\adms\Models\helper\AdmsCentralGuias::perfil();
        $this->renderizarAjuda('adms/Views/ajuda/videos', [
            'perfil' => $perfil,
            'videos' => \App\adms\Models\helper\AdmsCentralGuias::comVideo($perfil),
        ]);
    }

    /** E6c: imagem de um guia do aluno (col_paginas 16); docs/ não responde pela web. */
    public function guiaImagem($nome = null)
    {
        $f = \App\adms\Models\helper\AdmsCentralGuias::imagem((string) $nome, \App\adms\Models\helper\AdmsCentralGuias::perfil());
        if ($f === null) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Imagem não encontrada.';
            return;
        }
        header('Content-Type: image/png');
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        readfile($f);
    }

    /** E6c: NOVIDADES do aluno (col_paginas 17): só as que interessam ao aluno. */
    public function novidades()
    {
        $perfil = \App\adms\Models\helper\AdmsCentralGuias::perfil();
        $this->renderizarAjuda('adms/Views/novidades/listar', [
            'perfil' => $perfil,
            'versoes' => \App\adms\Models\helper\AdmsNovidades::listar($perfil),
            'guias' => \App\adms\Models\helper\AdmsCentralGuias::visiveis($perfil),
        ]);
    }

    /** E7 (D-218): "Entendi" ou fechar o aviso — marca como visto (col_paginas 18, POST). */
    public function novidadesVisto()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            \App\adms\Models\helper\AdmsNovidades::marcarVisto();
        }
        header('Location: ' . URLACAD . 'home/index');
    }

    private function renderizarAjuda(string $view, array $extra)
    {
        $this->dados['paginaAtiva'] = 14;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->carregarAluno();
        $this->definirLinksAcademicos();
        $this->dados['rotas'] = ['indice' => URLACAD . 'home/ajuda', 'guia' => URLACAD . 'home/guia/', 'novidades' => URLACAD . 'home/novidades', 'videos' => URLACAD . 'home/videos'];
        $this->dados = array_merge($this->dados, $extra);
        (new \Core\ConfigView($view, $this->dados))->renderizar('tabler');
    }

    private function renderizarPagina($pagina = '')
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $this->carregarAluno();

        // Links do card Acadêmico respeitando o ano escolhido
        $this->definirLinksAcademicos();

        $secao = strtolower(trim((string)$pagina));
        if($secao === ''){
            // Compatibilidade com link antigo, caso ainda exista cache no navegador.
            $secao = strtolower((string) (filter_input(INPUT_GET, 'secao', FILTER_DEFAULT) ?? ''));
        }
        $view = "adms/Views/home/aluno";

        if($secao === 'notas' || $secao === 'notas-atividades'){
            $this->carregarNotasAtividades();
            $view = "adms/Views/home/notas_atividades";
        } elseif($secao === 'boletim' || $secao === 'boletim-aluno'){
            $this->carregarBoletimAluno();
            $view = "adms/Views/home/boletim_aluno";
        } elseif($secao === 'horario-aluno'){
            $this->carregarHorarioAluno();
            $view = "adms/Views/home/horario_aluno";
        } elseif ($secao === 'financeiro-aluno') { // Onda 8, financeiro, Parte 3 (D-98)
            $M = \App\adms\Models\financeiro\AdmsFichaFinanceira::class;
            $filtro = (string) ($_GET['filtro'] ?? 'todos');
            $this->dados['filtroFinanceiro'] = isset($M::FILTROS[$filtro]) ? $filtro : 'todos';
            $this->dados['fichaFinanceira'] = $M::ficha((int) $this->dados['aluno_id'], $this->dados['filtroFinanceiro']);
            $view = "adms/Views/home/financeiro_aluno";
        } elseif ((int) $this->dados['aluno_id'] > 0) {
            /* Onda 8, financeiro, Parte 3 (D-98): o cartão "Ficha financeira" na página inicial, para o aluno com algum título */
            $M = \App\adms\Models\financeiro\AdmsFichaFinanceira::class;
            $temTitulo = (bool) \VcBanco::daRequisicao()->query('SELECT 1 FROM fin_titulos WHERE aluno_id = ' . (int) $this->dados['aluno_id'] . ' LIMIT 1')->fetchColumn();
            $temTitulo = $temTitulo && \App\adms\Models\helper\AdmsTipoPortal::financeiroLigado(); // D-129: desligado, sem o cartão
            $this->dados['cartaoFinanceiro'] = $temTitulo ? ['vencidos' => $M::vencidos((int) $this->dados['aluno_id']), 'url' => URLACAD . 'home/financeiro-aluno'] : null;
        }

        $carregarView = new \Core\ConfigView($view, $this->dados);
        $carregarView->renderizar('tabler');
    }

    private function definirLinksAcademicos()
    {
        $query = http_build_query(['ano' => (int) ($this->dados['ano'] ?? date('Y'))]);

        $this->dados['academico'] = [
            'boletim' => URLACAD . 'home/boletim-aluno?' . $query,
            'notas' => URLACAD . 'home/notas-atividades?' . $query,
            'horario' => URLACAD . 'home/horario-aluno?' . $query
        ];
    }

    /**
     * O aluno exibido é o da sessão. `alunos` continua sendo uma lista (com um
     * elemento) porque é o formato que as views já leem.
     */
    private function carregarAluno()
    {
        $ano = (int) (filter_input(INPUT_GET, 'ano', FILTER_VALIDATE_INT) ?? date('Y'));
        $this->dados['ano'] = $ano;
        $this->dados['alunos'] = [];
        $this->dados['aluno_id'] = 0;

        $usuarioId = (int) ($_SESSION['academico_id'] ?? 0);
        if($usuarioId <= 0){
            return;
        }

        $this->dados['alunos'] = $this->getAlunoDoEstudante($usuarioId, $ano);
        $this->dados['aluno_id'] = $usuarioId;
    }

    /** E4: boletim POR CURSO do aluno da sessão (AdmsBoletimCurso, motor único). */
    private function carregarBoletimAluno()
    {
        $aluno = $this->dados['alunos'][0] ?? null;
        $rm = preg_replace('/\D/', '', (string) ($aluno['rm'] ?? ''));
        $b = $rm !== '' ? \App\adms\Models\notas\AdmsBoletimCurso::doAluno($rm) : [];
        /* E8c (A11): o mesmo seletor de curso e ano do painel ("ano" da URL é o da página do portal, não filtra o boletim) */
        $this->dados['filtroBoletim'] = ['curso' => (int) (filter_input(INPUT_GET, 'filtro_curso', FILTER_VALIDATE_INT) ?: 0), 'ano' => (int) (filter_input(INPUT_GET, 'filtro_ano', FILTER_VALIDATE_INT) ?: 0)];
        $this->dados['opcoesBoletim'] = \App\adms\Models\notas\AdmsBoletimCurso::opcoes($b);
        $this->dados['boletimCurso'] = \App\adms\Models\notas\AdmsBoletimCurso::filtrar($b, $this->dados['filtroBoletim']['curso'], $this->dados['filtroBoletim']['ano']);
    }

    private function getAlunoDoEstudante($usuarioId, $ano)
    {
        $usuarioId = (int) $usuarioId;
        $ano = (int) $ano;

        if($usuarioId <= 0 || $ano <= 0){
            return [];
        }

        $leitor = new \App\adms\Models\helper\AdmsRead();
        $leitor->fullRead(
            "SELECT
                cu.id as aluno_id,
                cu.rm,
                cu.nome as nome_aluno,
                mme.id as matricula_id,
                COALESCE((SELECT c.nome FROM rm_cursos c WHERE c.id = mme.ensino_id), 'Aluno') as ensino_abrev,
                mme.ensino_id,
                (SELECT d.codnome FROM rm_cursos_turma_series d WHERE d.id = mme.modulo_id) as serie
            FROM col_usuarios cu
            LEFT JOIN rm_matriculas_ensino mme
                ON mme.usuario_id = cu.id
                AND mme.ano = :ano
                AND mme.situacao = 1
            WHERE cu.id = :usuario_id
            LIMIT :limit",
            "ano={$ano}&usuario_id={$usuarioId}&limit=1"
        );

        return $leitor->getResultado() ?? [];
    }

    private function carregarNotasAtividades()
    {
        $aluno = $this->dados['alunos'][0] ?? null;
        $ano = (int) ($this->dados['ano'] ?? date('Y'));

        $this->dados['notasAtividades'] = [
            'aluno' => null,
            'disciplinas' => [],
            'disciplinasSelecionadas' => [],
            'notasPorDisciplina' => [],
            'mensagem' => null
        ];

        if(empty($aluno)){
            $this->dados['notasAtividades']['mensagem'] = 'Não foi possível identificar o aluno para consultar as notas.';
            return;
        }

        $rm = (int) ($aluno['rm'] ?? 0);
        if($rm <= 0){
            $this->dados['notasAtividades']['mensagem'] = 'Aluno sem RM válido para consultar as notas.';
            return;
        }

        $modelNotas = new \App\adms\Models\academico\AdmsNotasAtividades();
        $disciplinas = $modelNotas->listarDisciplinasPorRmAno($rm, $ano);

        $dadosPost = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?? [];
        $postDisciplinas = $dadosPost['disciplinas'] ?? [];
        $filtroTodas = empty($postDisciplinas) || in_array('all', (array)$postDisciplinas, true);
        $disciplinasSelecionadas = $this->normalizarDisciplinasSelecionadas($postDisciplinas, $disciplinas);
        $notas = $modelNotas->listarNotasPorRmDisciplinas($rm, $disciplinasSelecionadas);

        $this->dados['notasAtividades']['aluno'] = $aluno;
        $this->dados['notasAtividades']['disciplinas'] = $disciplinas;
        $this->dados['notasAtividades']['disciplinasSelecionadas'] = $disciplinasSelecionadas;
        $this->dados['notasAtividades']['filtroTodas'] = $filtroTodas;
        $this->dados['notasAtividades']['notasPorDisciplina'] = $this->agruparNotasPorDisciplina($notas);
    }

    private function normalizarDisciplinasSelecionadas($disciplinasPost, $disciplinasDisponiveis)
    {
        if(!is_array($disciplinasPost)){
            $disciplinasPost = [];
        }

        $todasIds = array_map(function($disciplina){
            return (int) ($disciplina['rm_prof_mat_id'] ?? 0);
        }, $disciplinasDisponiveis);

        if(empty($disciplinasPost) || in_array('all', $disciplinasPost, true)){
            return array_values(array_filter($todasIds));
        }

        $selecionadas = array_map('intval', $disciplinasPost);
        $selecionadas = array_values(array_filter($selecionadas));

        // Garante que só IDs válidos das disciplinas do aluno serão usados na consulta.
        return array_values(array_intersect($todasIds, $selecionadas));
    }

    private function agruparNotasPorDisciplina($notas)
    {
        $resultado = [];

        foreach($notas as $nota){
            $disciplinaId = (int) ($nota['rm_prof_mat_id'] ?? 0);
            if($disciplinaId <= 0){
                continue;
            }

            if(!isset($resultado[$disciplinaId])){
                $resultado[$disciplinaId] = [
                    'materia' => $nota['materia'] ?? 'Disciplina',
                    'bimestres' => []
                ];
            }

            /* E4: etapa única — as notas não se agrupam mais por bimestre. */
            $bimestre = 'Atividades';
            if(!isset($resultado[$disciplinaId]['bimestres'][$bimestre])){
                $resultado[$disciplinaId]['bimestres'][$bimestre] = [];
            }

            $resultado[$disciplinaId]['bimestres'][$bimestre][] = [
                'nomeAtividade' => $nota['nomeAtividade'] ?? '',
                'nota' => $nota['nota'] ?? null
            ];
        }

        return $resultado;
    }

    private function carregarHorarioAluno()
    {
        $aluno = $this->dados['alunos'][0] ?? null;
        $ano = (int) ($this->dados['ano'] ?? date('Y'));

        $this->dados['horarioAluno'] = [
            'aluno' => null,
            'disciplinas' => [],
            'mensagem' => null
        ];

        if(empty($aluno)){
            $this->dados['horarioAluno']['mensagem'] = 'Não foi possível identificar o aluno.';
            return;
        }

        $rm = (int) ($aluno['rm'] ?? 0);
        if($rm <= 0){
            $this->dados['horarioAluno']['mensagem'] = 'Aluno sem RM válido.';
            return;
        }

        $model = new \App\adms\Models\academico\AdmsHorarioAluno();
        $disciplinas = $model->listarHorarioPorRmAno($rm, $ano);

        $this->dados['horarioAluno']['aluno'] = $aluno;
        $this->dados['horarioAluno']['disciplinas'] = $disciplinas ?? [];
    }
}

?>
