<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * =============================================================================
 * O ANO LETIVO — o CRUD que faltava (P-33).
 * =============================================================================
 *
 * `adms_periodos_letivos` e' lida por sete telas e nao era escrita por nenhuma.
 * Sem esta tela, "criar 2028" comeca precisando de SQL, antes de qualquer plano.
 * =============================================================================
 */
class AnosLetivos
{
    private $dados;
    private $dadosForm;
    private $pagina = 'anos-letivos';

    public function listar(): void
    {
        $this->dados['paginaAtiva'] = $this->pagina;

        $botao = [
            'cadastrar' => ['menu_controller' => 'anos-letivos', 'menu_metodo' => 'cadastrar'],
            'editar'    => ['menu_controller' => 'anos-letivos', 'menu_metodo' => 'editar'],
            'apagar'    => ['menu_controller' => 'anos-letivos', 'menu_metodo' => 'apagar'],
        ];
        $visBotao = new \App\adms\Models\AdmsBotao();
        $this->dados['botao'] = $visBotao->valBotao($botao);

        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $anos = new \App\adms\Models\anosLetivos\AdmsAnosLetivos();
        $this->dados['anos'] = $anos->listar();
        $this->dados['bandeiras'] = \App\adms\Models\anosLetivos\AdmsAnosLetivos::BANDEIRAS;

        $carregarView = new \Core\ConfigView('adms/Views/anosLetivos/listAnos', $this->dados);
        $carregarView->renderizar('tabler');
    }

    public function cadastrar(): void
    {
        $this->formulario(0);
    }

    public function editar($dadosId = null): void
    {
        $this->formulario((int) $dadosId);
    }

    private function formulario(int $id): void
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (!empty($this->dadosForm['SalvarAnoLetivo'])) {
            unset($this->dadosForm['SalvarAnoLetivo']);
            $this->dadosForm['id'] = $id;

            $anos = new \App\adms\Models\anosLetivos\AdmsAnosLetivos();

            if ($anos->salvar($this->dadosForm)) {
                header('Location: ' . URLADM . 'anos-letivos/listar');

                return;
            }

            /* Recusa: volta com o que a pessoa digitou, e nao com o banco. */
            $this->dados['ano'] = $this->dadosForm;
        }

        $this->dados['paginaAtiva'] = $this->pagina;

        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        if (!isset($this->dados['ano'])) {
            $anos = new \App\adms\Models\anosLetivos\AdmsAnosLetivos();
            $this->dados['ano'] = $id > 0 ? $anos->ver($id) : null;

            if ($id > 0 && $this->dados['ano'] === null) {
                $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'>"
                    . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
                    . 'Erro: Ano letivo não encontrado!</div>';
                header('Location: ' . URLADM . 'anos-letivos/listar');

                return;
            }
        }

        $this->dados['bandeiras'] = \App\adms\Models\anosLetivos\AdmsAnosLetivos::BANDEIRAS;

        $carregarView = new \Core\ConfigView('adms/Views/anosLetivos/formAno', $this->dados);
        $carregarView->renderizar('tabler');
    }

    public function apagar($dadosId = null): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') { // Onda 6, Etapa 6: apagar só por POST (um link não apaga)
            $anos = new \App\adms\Models\anosLetivos\AdmsAnosLetivos();
            $anos->apagar((int) $dadosId);
        }
        header('Location: ' . URLADM . 'anos-letivos/listar');
    }
}
