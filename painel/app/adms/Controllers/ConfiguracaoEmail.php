<?php
namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class ConfiguracaoEmail
{
    private $pagina = 29;
    private $dados;
    private $dadosId;
    private $dadosForm;
    
    public function listar()
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $botao = ['cad_email' => ['menu_controller' => 'configuracao-email', 'menu_metodo' => 'cadastrar'],
        'vis_email' => ['menu_controller' => 'configuracao-email', 'menu_metodo' => 'visualizar'],
        'edit_email' => ['menu_controller' => 'configuracao-email', 'menu_metodo' => 'editar'],
        'del_email' => ['menu_controller' => 'configuracao-email', 'menu_metodo' => 'apagar'],
        'testar' => ['menu_controller' => 'configuracao-email', 'menu_metodo' => 'testar'],]; // Onda 2, Etapa 4
        $visBotao = new \App\adms\Models\AdmsBotao();
        $this->dados['botao'] = $visBotao->valBotao($botao);

        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $listConfigEmail = new \App\adms\Models\configEmail\AdmsListarConfigEmail();
        $this->dados['listEmails'] = $listConfigEmail->listEmails();

        $carregarView = new \Core\ConfigView("adms/Views/configEmail/listConfigEmail", $this->dados);
        $carregarView->renderizar('tabler');
    }

    /**
     * Onda 2, Etapa 4: o ENVIO DE TESTE — manda um e-mail para o endereço do usuário logado pela configuração gravada
     * (a mesma função que manda o link de nova senha e o de acesso do aluno: AdmsSendPhpMailer). POST.
     */
    public function testar()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT nome, email FROM adms_usuarios WHERE id = :id LIMIT :limit", "id=" . (int) $_SESSION['colUsuario_id'] . "&limit=1");
            $eu = $ler->getResultado()[0] ?? null;
            $envio = new \App\adms\Models\helper\AdmsSendPhpMailer();
            $ok = false;
            if ($eu && filter_var($eu['email'], FILTER_VALIDATE_EMAIL)) {
                $envio->enviarEmail([
                    'toEmail' => $eu['email'], 'toName' => $eu['nome'], 'toSubject' => 'VCOSTAEDU: e-mail de teste',
                    'contentHtml' => 'Este é um e-mail de teste do VCOSTAEDU. Se você o recebeu, o envio de e-mail está funcionando.',
                    'contentText' => "Este é um e-mail de teste do VCOSTAEDU. Se você o recebeu, o envio de e-mail está funcionando.",
                ], 0);
                $ok = (bool) $envio->getResultado();
            }
            (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(474, $ok ? 200 : 400, 2, 'Teste de envio de e-mail');
            $_SESSION['msg'] = $ok
                ? "<div class='alert alert-success alert-dismissible' id='testeEmailOk'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>E-mail de teste enviado para " . htmlspecialchars((string) $eu['email'], ENT_QUOTES, 'UTF-8') . '. Confira a caixa de entrada (e a de spam).</div>'
                : "<div class='alert alert-danger alert-dismissible' id='testeEmailFalhou'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>O e-mail de teste não saiu. Confira o servidor, a porta, a segurança, o usuário e a senha da configuração, e se o seu cadastro tem um e-mail válido.</div>";
        }
        header('Location: ' . URLADM . 'configuracao-email/listar');
    }

    public function cadastrar()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['CadConfigEmail'])){
            unset($this->dadosForm['CadConfigEmail']);
            $cadUsuario = new \App\adms\Models\configEmail\AdmsCadastrarConfigEmail();
            $cadUsuario->cadConfigEmail($this->dadosForm);
            if($cadUsuario->getResultado()){
                $urlDestino = URLADM . 'configuracao-email/listar';
                header("Location: $urlDestino");
            }else{
                $this->dados['form'] = $this->dadosForm;
                $this->cadastrarView();
            }
        }else{
            $this->cadastrarView();
        }
    }

    private function cadastrarView()
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $carregarView = new \Core\ConfigView("adms/Views/configEmail/cadConfigEmail", $this->dados);
        $carregarView->renderizar('tabler');
    }

    public function visualizar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        if(!empty($this->dadosId)){
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['edit_email' => ['menu_controller' => 'configuracao-email', 'menu_metodo' => 'editar'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);

            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();

            $visConfig = new \App\adms\Models\configEmail\AdmsVerConfigEmail();
            $this->dados['info_pagina'] = $visConfig->visConfigEmail($this->dadosId);

            $carregarView = new \Core\ConfigView("adms/Views/configEmail/verConfigEmail", $this->dados);
            $carregarView->renderizar('tabler');

        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro:Dados não encontrado!</div>";
            $urlDestino = URLADM . 'configuracao-email/listar';
            header("Location: $urlDestino"); 
        }
    }

    public function editar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosId)){
            $this->editPrev();
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro:Dados não encontrados!</div>";
            $urlDestino = URLADM . 'configuracao-email/listar';
            header("Location: $urlDestino"); 
        }
    }

    private function editPrev()
    {
        if(!empty($this->dadosForm['EditConfigEmail'])){
            unset($this->dadosForm['EditConfigEmail']);
            $altPg = new \App\adms\Models\configEmail\AdmsEditarConfigEmail();
            $altPg->editConfigEmail($this->dadosForm);
            if($altPg->getResultado()){
                $urlDestino = URLADM . 'configuracao-email/visualizar/'.$this->dadosId;
                header("Location: $urlDestino"); 
            }else{
                $this->dados['form'] = $this->dadosForm;
                $this->editView();
            }
        }else{
            $visUs = new \App\adms\Models\configEmail\AdmsEditarConfigEmail();
            $this->dados['form'] = $visUs->verConfigEmail($this->dadosId);
            $this->editView();
        }

    }

    private function editView()
    {
        if(!empty($this->dados['form'])){
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['vis_email' => ['menu_controller' => 'configuracao-email', 'menu_metodo' => 'visualizar'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);

            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();
            
            $carregarView = new \Core\ConfigView("adms/Views/configEmail/editConfigEmail", $this->dados);
            $carregarView->renderizar('tabler');
        
        }else{

            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro:Página de configuração não encontrado!</div>";
            $urlDestino = URLADM . 'configuracao-email/listar';
            header("Location: $urlDestino"); 
        }

    }

    public function apagar()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['ApagarConfigEmail'])){
            unset($this->dadosForm['ApagarConfigEmail']);
            $apagarConfig = new \App\adms\Models\configEmail\AdmsApagarConfigEmail();
            $apagarConfig->delConfigEmail($this->dadosForm);
            if($apagarConfig->getResultado()){
                $urlDestino = URLADM . 'configuracao-email/listar';
                header("Location: $urlDestino"); 
            }else{
                $urlDestino = URLADM . 'configuracao-email/listar';
                header("Location: $urlDestino"); 
            }
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Configuração de e-mail não encontrada!</div>";
            $urlDestino = URLADM . 'configuracao-email/listar';
            header("Location: $urlDestino"); 
        }

    }

}
?>