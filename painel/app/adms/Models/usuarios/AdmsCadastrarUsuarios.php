<?php

namespace App\adms\Models\usuarios;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsCadastrarUsuarios
{
    private $titulacao; // E6a
    private $dados;
    private $primeiroNome;
    private $logDescricao;
    private $foto;
    private $idUsuario;
    private $imgAntiga;
    private $logCodigo;
    private $emailDados;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function cadUsuarios(array $dados)
    {
        /* 1.18.3 (D-497/D-498): só os campos do formulário, e só com nível ABAIXO do seu */
        $this->dados = array_intersect_key($dados, array_flip(['nome', 'email', 'genero_id', 'usuario', 'senha', 'adms_niveis_acesso_id', 'adms_sits_usuario_id', 'titulacao_id', 'imagem_antiga', 'imagem_nova']));
        if (!in_array((int) ($this->dados['adms_niveis_acesso_id'] ?? 0), \App\adms\Models\helper\AdmsHierarquia::niveisAtribuiveis(), true)) {
            \App\adms\Models\helper\AdmsHierarquia::recusar('Você só pode cadastrar usuários com nível de acesso abaixo do seu.');
            $this->resultado = false;
            return;
        }
        $titulacao = \App\adms\Models\helper\AdmsTitulacao::extrair($this->dados); // E6a
        if ($titulacao === false) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Erro: titulação inválida.</div>";
            $this->resultado = false;
            return;
        }
        $this->titulacao = $titulacao;
        $this->foto = $this->dados['imagem_nova'];
        $this->imgAntiga = $this->dados['imagem_antiga'];
        unset($this->dados['imagem_nova'], $this->dados['imagem_antiga']);
        $valCampoVazio = new \App\adms\Models\helper\AdmsValCampoVazio();
        $valCampoVazio->validarDados($this->dados);
        if($valCampoVazio->getResultado()){
            $this->valInput();
        }else{
            $this->resultado = false;
        }

    }

    private function valInput()
    {
        $valEmail = new \App\adms\Models\helper\AdmsValEmail();
        $valEmail->validarEmail($this->dados['email']);
        
        $valEmailSingle = new \App\adms\Models\helper\AdmsValEmailUnico();
        $valEmailSingle->validarEmailUnico($this->dados['email'], 'adms_usuarios');
        /* Onda 2: a regra da senha é a única do sistema (core/Senha.php) — antes o cadastro não conferia nada. */
        $valSenha = new \App\adms\Models\helper\AdmsValSenha();
        if($valEmail->getResultado() AND $valEmailSingle->getResultado()
            AND $valSenha->valSenha((string) $this->dados['senha'], ['nome' => (string) $this->dados['nome'], 'usuario' => (string) $this->dados['usuario'], 'email' => (string) $this->dados['email']])){
            $this->valFoto();
        }else{
            $this->resultado = false;
        }

    }

    private function valFoto()
    {
        if (empty($this->foto['name'])) {
            $this->addUsuarios();
        } else {
            $slugImg = new \App\adms\Models\helper\AdmsSlug();
            $this->dados['imagem'] = $slugImg->nomeSlug($this->foto['name']);
            $this->addUsuarios();
            $uploadImg = new \App\adms\Models\helper\AdmsUploadImgRed();
            $uploadImg->uploadImagem($this->foto, 'assets/img/usuario/' . $this->idUsuario . '/', $this->dados['imagem'], 150, 150);
            if ($uploadImg->getResultado()) {
                $apagarImg = new \App\adms\Models\helper\AdmsApagarImg();
                $apagarImg->apagarImg('assets/img/usuario/' . $this->idUsuario . '/' . $this->imgAntiga);
            } else {
                $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Usuário acadêmico cadastrado com sucesso!</div>";
                $this->resultado = true; 
            }
        }
    }

    private function addUsuarios()
    {
        $this->dados['usuario_cre'] = $_SESSION['colUsuario_id'];
        $this->dados['senha'] = password_hash($this->dados['senha'], PASSWORD_DEFAULT);
        $this->dados['titulacao_id'] = $this->titulacao ?? null; // E6a
        $addUsuario = new \App\adms\Models\helper\AdmsCreate();
        $addUsuario->exeCreate("adms_usuarios", $this->dados);
        if($addUsuario->getResultado()){
            $this->idUsuario = $addUsuario->getResultado();
            $this->logCodigo = 200;
            $this->logDescricao = "Usuário cadastrado com sucesso! id={$this->idUsuario}&nome={$this->dados['nome']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Usuário cadastrado com sucesso!</div>";
            $this->resultado = true; 
            // $this->enviarEmail();
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível criar o usuário! nome={$this->dados['nome']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro:Não foi possível cadastrar o usuário!</div>";
            $this->resultado = false; 
        }
    }

    private function enviarEmail()
    {
        $sendEmail = new \App\adms\Models\helper\AdmsSendPhpMailer();
        $this->emailHtml();
        $sendEmail->enviarEmail($this->emailDados,2);
        if($sendEmail->getResultado()){
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Usuário cadastrado com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro:Não foi possível cadastrar o usuário!</div>";
            $this->resultado = false; 
        }
    }

    private function emailHtml()
    {
        $name = explode(" ", $this->dados['nome']);
        $this->primeiroNome = $name[0];

        $this->emailDados['toEmail'] = $this->dados['email'];
        $this->emailDados['toName'] = $this->primeiroNome;
        $this->emailDados['toSubject'] = "Confirmar sua conta.";
        
        $this->emailDados['contentHtml'] = "Prezado (a) {$this->primeiroNome}<br><br>";
        $this->emailDados['contentHtml'] .= "Cadastro do usuário realizado com sucesso.<br>";
        
        $this->emailDados['contentText'] = "Prezado (a) {$this->primeiroNome}\n\n";
        $this->emailDados['contentText'] .= "Cadastro do usuário realizado com sucesso.\n";

    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(9, $this->logCodigo, 2, $this->logDescricao);
    }

    public function listarCadastro()
    {
        $listar = new \App\adms\Models\helper\AdmsRead();
        $listar->fullRead("SELECT id, nome FROM adms_niveis_acessos WHERE situacao=:sit ORDER BY ordem asc", "sit=1");
        /* 1.18.3 (D-497): a escolha oferece só os níveis abaixo do seu */
        $atribuiveis = \App\adms\Models\helper\AdmsHierarquia::niveisAtribuiveis();
        $this->resultado['niveis_acessos'] = array_values(array_filter($listar->getResultado() ?: [], fn($n) => in_array((int) $n['id'], $atribuiveis, true)));

        $listarTp = new \App\adms\Models\helper\AdmsRead();
        $listarTp->fullRead("SELECT id, nome FROM adms_sits_usuarios WHERE id<:sit", "sit=5");
        $this->resultado['situacoes'] = $listarTp->getResultado();

        $listGen = new \App\adms\Models\helper\AdmsRead();
        $listGen->fullRead("SELECT id, nome FROM adms_generos WHERE situacao=:situacao" , "situacao=1");
        $this->resultado['generos'] = $listGen->getResultado();

        return $this->resultado;
    }

}