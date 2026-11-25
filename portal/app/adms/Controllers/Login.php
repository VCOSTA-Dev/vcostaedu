<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class Login
{
    private $dados;
    private $dadosId;
    private $dadosForm;
    
    /**
     * E7 — SONDA do plano B (col_paginas 19, pública). 1ª chamada grava um número na sessão e o devolve;
     * a 2ª (?n=) diz se a sessão voltou com ele. Dentro do iframe, "não" = o navegador não guarda o cookie.
     */
    public function sonda()
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $n = (string) (filter_input(INPUT_GET, 'n') ?? '');
        if ($n === '') {
            $_SESSION['sonda_iframe'] = bin2hex(random_bytes(8));
            echo json_encode(['n' => $_SESSION['sonda_iframe']]);
            return;
        }
        echo json_encode(['ok' => isset($_SESSION['sonda_iframe']) && hash_equals((string) $_SESSION['sonda_iframe'], $n)]);
    }

    public function acesso()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['SendLogin'])){
            unset($this->dadosForm['SendLogin']);
            $valLogin = new \App\adms\Models\acesso\AdmsLogin();
            $valLogin->login($this->dadosForm);
            if($valLogin->getResultado()){
                /* Onda 7: o responsável vai para os filhos; o aluno, para a Página Inicial dele */
                $urlDestino = URLACAD . (!empty($_SESSION['vc_responsavel_id']) ? 'familia/index' : 'home/index');
                header("Location: $urlDestino");
            }else{
                $this->dados['form'] = $this->dadosForm;
                $this->acessoView();
            }
        }elseif(!empty($_SESSION['vc_responsavel_id'])){
            /* Onda 7: o responsável que já entrou e abre uma página que não é dele (a do aluno) volta aos filhos */
            header('Location: ' . URLACAD . 'familia/index');
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
        unset($_SESSION['academico_id'], $_SESSION['academico_nome'], $_SESSION['academico_email'], $_SESSION['academico_imagem'], $_SESSION['academico_nivel'], $_SESSION['academico_situacao'], $_SESSION['academico_ordem'],
            $_SESSION['vc_responsavel_id'], $_SESSION['vc_familia_filho']); // Onda 7: o responsável também sai
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Usuário deslogado com sucesso!</div>";
        $urlDestino = URLACAD . "login/acesso";
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
                $urlDestino = URLACAD . 'login/acesso';
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
        $this->dados['campoLogin'] = \App\adms\Models\helper\AdmsParametro::campoLoginPortal();
        $carregarView = new \Core\ConfigView("adms/Views/acesso/esqueceuSenha", $this->dados);
        $carregarView->renderizarLogin('tabler');
    }

    /** E7 (D-70): a página do link novo (…/login/definir-senha#t=TOKEN). Pública (col_paginas 20). */
    public function definirSenha()
    {
        $carregarView = new \Core\ConfigView("adms/Views/acesso/definirSenha", $this->dados ?? []);
        $carregarView->renderizarLogin('tabler');
    }

    /**
     * Definir senha pelo link (primeiro acesso ou esqueci a senha). E7 (D-70): o token chega por POST (campo
     * "token"), vindo da página definir-senha, que o lê do fragmento. Link no FORMATO ANTIGO
     * (…/atualizar-senha/TOKEN, emitido até a 1.6.0): recusado desde a 1.7.1 (E8, D-239). Link inválido, usado ou expirado volta ao login com aviso.
     */
    public function atualizarSenha($dadosId = null)
    {
        $post = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        /* E8 (D-239): link no FORMATO ANTIGO (…/atualizar-senha/TOKEN) é RECUSADO — o token no caminho vai para o
           log do servidor. Não se usa nem se confere o token: pede-se um link novo. */
        if ((string) $dadosId !== '') {
            $_SESSION['msg'] = "<div class='alert alert-warning alert-dismissible' id='linkAntigo'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Este link está num formato antigo e não vale mais. Peça um novo em <b>Esqueci a senha</b> ou procure a secretaria.</div>";
            header("Location: " . URLACAD . 'login/acesso');
            return;
        }
        $token = preg_replace('/[^A-Za-z0-9]/', '', (string) ($post['token'] ?? ''));
        $modelo = new \App\adms\Models\acesso\AdmsAtualizaSenha();
        $this->dados['info'] = ($token !== '') ? $modelo->checarPermissao($token) : null;
        if (empty($this->dados['info']) && $token !== '' && (new \App\adms\Models\helper\AdmsTokenAcesso())->jaCriouASenha($token)) {
            /* Onda 8 (D-70): o link que já criou a senha, aberto de novo (o do e-mail, depois do botão "Criar minha senha
               agora" do site; ou o clique duplo): não é erro — a senha já existe, e o caminho é a entrada */
            $_SESSION['msg'] = "<div class='alert alert-info alert-dismissible' id='senhaJaCriada'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button><b>A sua senha já foi criada.</b> Este link já foi usado para isso. Entre com o seu login e a senha que você criou. Se não lembrar dela, use <b>Esqueci a senha</b>.</div>";
            header("Location: " . URLACAD . 'login/acesso');
            return;
        }
        if (empty($this->dados['info'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Este link é inválido, já foi usado ou expirou. Peça um novo em <b>Esqueci a senha</b> ou procure a secretaria.</div>";
            header("Location: " . URLACAD . 'login/acesso');
            return;
        }
        $this->dados['token'] = $token;
        if (!empty($post['AtualizaSenha'])) {
            $modelo->trocarSenha($post, $token);
            if ($modelo->getResultado()) {
                header("Location: " . URLACAD . 'login/acesso');
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