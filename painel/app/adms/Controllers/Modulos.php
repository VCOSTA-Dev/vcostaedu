<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * MÓDULOS da matriz (E3a). Rotas 332-335, que eram de `series/*`: a série do
 * sistema escolar de origem virou o módulo da grade. Ver AdmsListarModulos.
 */
class Modulos
{
    private $dados;
    private $dadosForm;
    private $pagina = 332;

    /**
     * E8c (D-268): com "Organizar a matriz em módulos" em Não, a tela de módulos não existe para o usuário
     * (a URL leva à matriz, com o aviso). Devolve true quando redirecionou.
     */
    private function semModulos(): bool
    {
        if (!\App\adms\Models\helper\AdmsModuloInterno::ativo()) {
            return false;
        }
        $_SESSION['msg'] = "<div class='alert alert-info alert-dismissible' id='avisoSemModulos'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
            . 'A instituição não organiza a matriz em módulos (Configurações da instituição): o curso tem grade e a lista de disciplinas. Monte a grade na Matriz.</div>';
        header('Location: ' . URLADM . 'matriz/listar');
        return true;
    }

    public function listar(): void
    {
        if ($this->semModulos()) {
            return;
        }
        $this->comum();
        $gradeId = (int) filter_input(INPUT_GET, 'grade', FILTER_VALIDATE_INT);
        $listar = new \App\adms\Models\modulos\AdmsListarModulos();
        $this->dados['modulos'] = $listar->listar($gradeId);
        $this->dados['grades'] = $listar->grades();
        $this->dados['gradeId'] = $gradeId;

        $botao = [
            'cadastrar' => ['menu_controller' => 'modulos', 'menu_metodo' => 'cadastrar'],
            'editar' => ['menu_controller' => 'modulos', 'menu_metodo' => 'editar'],
            'apagar' => ['menu_controller' => 'modulos', 'menu_metodo' => 'apagar'],
            'matriz' => ['menu_controller' => 'matriz', 'menu_metodo' => 'visualizar'],
        ];
        $this->dados['botao'] = (new \App\adms\Models\AdmsBotao())->valBotao($botao);

        $carregarView = new \Core\ConfigView('adms/Views/modulos/listModulos', $this->dados);
        $carregarView->renderizar('tabler');
    }

    public function cadastrar(): void
    {
        if ($this->semModulos()) {
            return;
        }
        $this->formulario(0);
    }

    public function editar($dadosId = null): void
    {
        if ($this->semModulos()) {
            return;
        }
        $this->formulario((int) $dadosId);
    }

    private function formulario(int $id): void
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        $atual = $id > 0 ? \App\adms\Models\matriz\AdmsMatrizRegras::modulo($id) : null;
        if ($id > 0 && $atual === null) {
            header('Location: ' . URLADM . 'modulos/listar');
            return;
        }

        if (!empty($this->dadosForm['SalvarModulo'])) {
            unset($this->dadosForm['SalvarModulo']);
            $salvar = new \App\adms\Models\modulos\AdmsSalvarModulo();
            if ($salvar->salvar($this->dadosForm, $id) !== false) {
                $salvo = \App\adms\Models\matriz\AdmsMatrizRegras::modulo((int) $salvar->getResultado());
                header('Location: ' . URLADM . 'matriz/visualizar/' . (int) ($salvo['grade_id'] ?? 0));
                return;
            }
            $this->dados['modulo'] = $this->dadosForm;
        } else {
            $this->dados['modulo'] = $atual ?? [];
        }

        if ($atual) {
            $this->dados['modulo']['grade_id'] = $atual['grade_id'];
        } elseif (empty($this->dados['modulo']['grade_id'])) {
            $this->dados['modulo']['grade_id'] = (int) filter_input(INPUT_GET, 'grade', FILTER_VALIDATE_INT);
        }

        $this->comum();
        $this->dados['id'] = $id;
        $this->dados['grades'] = (new \App\adms\Models\modulos\AdmsListarModulos())->grades();

        $carregarView = new \Core\ConfigView('adms/Views/modulos/formModulo', $this->dados);
        $carregarView->renderizar('tabler');
    }

    /** Só por POST: apagar por link é apagar por engano. */
    public function apagar($dadosId = null): void
    {
        if ($this->semModulos()) {
            return;
        }
        $destino = 'modulos/listar';
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $modulo = \App\adms\Models\matriz\AdmsMatrizRegras::modulo((int) $dadosId);
            (new \App\adms\Models\modulos\AdmsApagarModulo())->apagar((int) $dadosId);
            if ($modulo) {
                $destino = 'matriz/visualizar/' . (int) $modulo['grade_id'];
            }
        }
        header('Location: ' . URLADM . $destino);
    }

    private function comum(): void
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
    }
}
