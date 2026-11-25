<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * MATRIZ CURRICULAR (E3a): curso → grade → módulo (com CH) → disciplina (D-33).
 * A lógica vem do módulo de matriz do sistema de origem de ensino superior (ModeloCursos / Cursos::cadastrarGrade),
 * reescrita sobre as tabelas do sistema escolar de origem: grade = rm_cursos_grades, módulo =
 * rm_cursos_turma_series, linha = uni_rh_cursos_disciplinas_grade.
 *
 * Rotas 367-374. Tudo que grava é POST.
 */
class Matriz
{
    private $dados;
    private $pagina = 367;

    public function listar(): void
    {
        $this->comum();
        $listar = new \App\adms\Models\matriz\AdmsListarMatriz();
        $this->dados['cursos'] = $listar->listar();
        $this->dados['botao'] = $this->botoes();
        $this->render('listMatriz');
    }

    public function visualizar($dadosId = null): void
    {
        $matriz = (new \App\adms\Models\matriz\AdmsVerMatriz())->ver((int) $dadosId);
        if ($matriz === null) {
            $_SESSION['msg'] = \App\adms\Models\matriz\AdmsMatrizRegras::aviso('danger', 'Erro: grade não encontrada.');
            header('Location: ' . URLADM . 'matriz/listar');
            return;
        }
        $this->comum();
        $this->dados['matriz'] = $matriz;
        /* E3b: só as situações que o usuário pode escolher (secretaria: Aberta → Em análise). */
        $this->dados['situacoes'] = \App\adms\Models\matriz\AdmsMatrizRegras::situacoesPermitidas((int) $matriz['grade']['situacao'], (new \App\adms\Models\matriz\AdmsListarMatriz())->situacoes());
        $this->dados['botao'] = $this->botoes();
        $this->dados['carregarOrdenacao'] = true; // E1b (D-353)
        $this->dados['regrasMedia'] = \App\adms\Models\notas\AdmsRegraMedia::catalogo(true); // 1.16.0 (D-438 a): a regra de cada disciplina
        $this->render('verMatriz');
    }

    public function cadastrarGrade(): void
    {
        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        if (!empty($form['SalvarGrade'])) {
            $cad = new \App\adms\Models\matriz\AdmsCadastrarGrade();
            if ($cad->cadastrar($form)) {
                header('Location: ' . URLADM . 'matriz/visualizar/' . (int) $cad->getResultado());
                return;
            }
            $this->dados['grade'] = $form;
        } else {
            $this->dados['grade'] = ['curso_id' => (int) filter_input(INPUT_GET, 'curso', FILTER_VALIDATE_INT)];
        }
        $this->comum();
        $this->dados['id'] = 0;
        $this->dados['cursos'] = (new \App\adms\Models\matriz\AdmsListarMatriz())->cursos();
        $this->render('formGrade');
    }

    public function editarGrade($dadosId = null): void
    {
        $id = (int) $dadosId;
        $grade = \App\adms\Models\matriz\AdmsMatrizRegras::grade($id);
        if (!$grade) {
            header('Location: ' . URLADM . 'matriz/listar');
            return;
        }
        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        if (!empty($form['SalvarGrade'])) {
            $edit = new \App\adms\Models\matriz\AdmsEditarGrade();
            if ($edit->editar($id, $form)) {
                header('Location: ' . URLADM . 'matriz/visualizar/' . $id);
                return;
            }
            $this->dados['grade'] = array_merge($grade, $form);
        } else {
            $this->dados['grade'] = $grade;
        }
        $this->comum();
        $this->dados['id'] = $id;
        $this->dados['cursos'] = (new \App\adms\Models\matriz\AdmsListarMatriz())->cursos((int) $grade['curso_id']); // 1.18.1: o curso da grade fica
        $this->render('formGrade');
    }

    public function situacaoGrade($dadosId = null): void
    {
        if ($this->post()) {
            (new \App\adms\Models\matriz\AdmsSituacaoGrade())->alterar((int) $dadosId, $this->form());
        }
        $this->voltar((int) $dadosId);
    }

    public function adicionarDisciplina($dadosId = null): void
    {
        if ($this->post()) {
            (new \App\adms\Models\matriz\AdmsAdicionarDisciplinaMatriz())->adicionar((int) $dadosId, $this->form());
        }
        $this->voltar((int) $dadosId);
    }

    public function moverDisciplina($dadosId = null): void
    {
        $gradeId = 0;
        if ($this->post()) {
            $mover = new \App\adms\Models\matriz\AdmsMoverDisciplinaMatriz();
            $mover->mover((int) $dadosId, $this->form());
            $gradeId = $mover->getGradeId();
        }
        $this->voltar($gradeId);
    }

    /** E1b (D-353): nova ordem das disciplinas da grade (POST `ordem` = ids das linhas, separados por vírgula). Rota 415. */
    public function ordenarDisciplinas($dadosId = null): void
    {
        if ($this->post()) {
            (new \App\adms\Models\matriz\AdmsOrdenarDisciplinasMatriz())->ordenar((int) $dadosId, $this->form());
        }
        $this->voltar((int) $dadosId);
    }

    public function removerDisciplina($dadosId = null): void
    {
        $gradeId = 0;
        if ($this->post()) {
            $remover = new \App\adms\Models\matriz\AdmsRemoverDisciplinaMatriz();
            $remover->remover((int) $dadosId);
            $gradeId = $remover->getGradeId();
        }
        $this->voltar($gradeId);
    }

    private function botoes(): array
    {
        $botao = [
            'ver' => ['menu_controller' => 'matriz', 'menu_metodo' => 'visualizar'],
            'cad_grade' => ['menu_controller' => 'matriz', 'menu_metodo' => 'cadastrar-grade'],
            'edit_grade' => ['menu_controller' => 'matriz', 'menu_metodo' => 'editar-grade'],
            'sit_grade' => ['menu_controller' => 'matriz', 'menu_metodo' => 'situacao-grade'],
            'add_disc' => ['menu_controller' => 'matriz', 'menu_metodo' => 'adicionar-disciplina'],
            'mov_disc' => ['menu_controller' => 'matriz', 'menu_metodo' => 'mover-disciplina'],
            'rem_disc' => ['menu_controller' => 'matriz', 'menu_metodo' => 'remover-disciplina'],
            'ord_disc' => ['menu_controller' => 'matriz', 'menu_metodo' => 'ordenar-disciplinas'], // E1b (D-353)
            'regra_disc' => ['menu_controller' => 'regra-media', 'menu_metodo' => 'disciplina'], // 1.16.0 (D-438 a)
            'cad_mod' => ['menu_controller' => 'modulos', 'menu_metodo' => 'cadastrar'],
            'edit_mod' => ['menu_controller' => 'modulos', 'menu_metodo' => 'editar'],
            'del_mod' => ['menu_controller' => 'modulos', 'menu_metodo' => 'apagar'],
            'gerar_oferta' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'gerar-oferta'],
        ];
        return (new \App\adms\Models\AdmsBotao())->valBotao($botao);
    }

    private function post(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    private function form(): array
    {
        return filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
    }

    private function voltar(int $gradeId): void
    {
        header('Location: ' . URLADM . ($gradeId > 0 ? 'matriz/visualizar/' . $gradeId : 'matriz/listar'));
    }

    private function comum(): void
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
    }

    private function render(string $view): void
    {
        $carregarView = new \Core\ConfigView('adms/Views/matriz/' . $view, $this->dados);
        $carregarView->renderizar('tabler');
    }
}
