<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class Login
{
    private $dados;
    private $url;
    private $dadosId;
    private $dadosForm;
    
    public function acesso()
    {
        $this->url = filter_input(INPUT_GET, 'url', FILTER_DEFAULT);
        /* Onda 2, Etapa 4: a escolha da última tela do instalador (conhecer ou configurar) segue até depois do login */
        $comecar = (string) filter_input(INPUT_GET, 'comecar', FILTER_DEFAULT);
        if (in_array($comecar, ['demonstracao', 'instituicao'], true)) {
            $_SESSION['vc_comecar'] = $comecar;
        }
        /* Onda 6, Etapa 7: o convite do registro, marcado na última tela do instalador (nunca vem marcado) */
        if (filter_input(INPUT_GET, 'registrar', FILTER_DEFAULT) === '1') {
            $_SESSION['vc_convite_registro'] = true;
        }
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['SendLogin'])){
            unset($this->dadosForm['SendLogin']);
            $valLogin = new \App\adms\Models\acesso\AdmsLogin();
            $valLogin->login($this->dadosForm);
            if($valLogin->getResultado()){
                if(!empty($this->url) && ($this->url != 'login/acesso')){
                    $UrlDestino = URLADM . $this->url;
                    header("Location: $UrlDestino");
                }else{
                    $UrlDestino = URLADM . 'home/index';
                    header("Location: $UrlDestino");
                }
            }else{
                $this->dados['form'] = $this->dadosForm;
                $this->acessoView();
            }
        }else{
            $this->acessoView();
        }

    }

    private function acessoView()
    {
        $carregarView = new \Core\ConfigView("adms/Views/acesso/login", $this->dados);
        $carregarView->renderizarLogin('tabler');
    }

    public function logout()
    {
        unset($_SESSION['colUsuario_id'], $_SESSION['colUsuario_nome'], $_SESSION['colUsuario_email'], $_SESSION['colUsuario_imagem'], $_SESSION['colUsuario_nivel'], $_SESSION['colUsuario_situacao'], $_SESSION['colUsuario_ordem'], $_SESSION['colUsuario_nivel_nome']);
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Usuário deslogado com sucesso!</div>";
        $urlDestino = URLADM . "login/acesso";
        header("Location: $urlDestino");
    }

    public function esqueceuSenha()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if(!empty($this->dadosForm['RecupLogin'])){
            unset($this->dadosForm['RecupLogin']);
            $recLogin = new \App\adms\Models\acesso\AdmsEsqueceuSenha();
            $recLogin->recuperarLogin($this->dadosForm);
            if($recLogin->getResultado()){
                $urlDestino = URLADM . 'login/acesso';
                header("Location: $urlDestino");
            }else{
                $this->dados['form'] = $this->dadosForm;
                $this->esqueceuSenhaView();
            }
        }else{
            $this->esqueceuSenhaView();
        }

    }

    private function esqueceuSenhaView()
    {
        $carregarView = new \Core\ConfigView("adms/Views/acesso/esqueceuSenha", $this->dados);
        $carregarView->renderizarLogin('tabler');
    }

    /** E7 (D-70): a página do link novo (…/login/definir-senha#t=TOKEN). Pública (adms_paginas 409). */
    public function definirSenha()
    {
        $carregarView = new \Core\ConfigView("adms/Views/acesso/definirSenha", $this->dados ?? []);
        $carregarView->renderizarLogin('tabler');
    }

    /**
     * Trocar a senha pelo link do "Esqueci a senha". E7 (D-70): o token chega por POST (campo recuperar_senha),
     * vindo da página definir-senha; link no formato antigo (…/atualizar-senha/TOKEN) é recusado desde a 1.7.1 (E8, D-239).
     */
    public function atualizarSenha($dadosId = null)
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        /* E8 (D-239): link no FORMATO ANTIGO (…/atualizar-senha/TOKEN) é RECUSADO, sem usar o token: pede-se um novo. */
        if ((string) $dadosId !== '') {
            $_SESSION['msg'] = "<div class='alert alert-warning' id='linkAntigo'>Este link está num formato antigo e não vale mais. Peça um novo em <b>Esqueci a senha</b>.</div>";
            header('Location: ' . URLADM . 'login/acesso');
            return;
        }
        $this->dadosId = preg_replace('/[^A-Za-z0-9]/', '', (string) ($this->dadosForm['recuperar_senha'] ?? ''));
        if ($this->dadosId === '') {
            header('Location: ' . URLADM . 'login/acesso');
            return;
        }
        $verToken = new \App\adms\Models\acesso\AdmsAtualizaSenha();
        $this->dados['info'] = $verToken->checarPermissao($this->dadosId);
        if (empty($this->dados['info'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Este link é inválido ou já foi usado. Peça um novo em <b>Esqueci a senha</b>.</div>";
            header('Location: ' . URLADM . 'login/acesso');
            return;
        }
        if (!empty($this->dadosForm['AtualizaSenha'])) {
            $MudarSenha = new \App\adms\Models\acesso\AdmsAtualizaSenha();
            $MudarSenha->trocarSenha(['recuperar_senha' => $this->dadosId, 'senha' => (string) ($this->dadosForm['senha'] ?? '')]);
            if ($MudarSenha->getResultado()) {
                header('Location: ' . URLADM . 'login/acesso');
                return;
            }
        }
        $this->atualizaSenhaView();
    }

    private function atualizaSenhaView()
    {
        $carregarView = new \Core\ConfigView("adms/Views/acesso/atualizaSenha", $this->dados);
        $carregarView->renderizarLogin('tabler');
    }

}