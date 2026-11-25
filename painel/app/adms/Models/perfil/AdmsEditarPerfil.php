<?php

namespace App\adms\Models\perfil;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsEditarPerfil
{
    private $dados;
    private $foto;
    private $telefone;
    private $celular;
    private $imgAntiga;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function verPerfil()
    {

        $verPerfil = new \App\adms\Models\helper\AdmsRead();
        $verPerfil->fullRead("SELECT a.id, a.nome, a.email, a.genero_id, a.usuario, a.telefone, a.imagem, a.celular, DATE_FORMAT(a.created, '%d/%m/%Y %H:%i') as criacao, DATE_FORMAT(a.modified, '%d/%m/%Y %H:%i') as modificado, (SELECT n.nome FROM adms_niveis_acessos n WHERE n.id=a.adms_niveis_acesso_id) as nivelAcesso, i.data_nascimento, i.rg, i.cpf, i.cep, i.endereco, i.numero, i.complemento, i.bairro, i.cidade, i.estado, a.adms_sits_usuario_id situacao, s.nome nomeSituacao, (SELECT c.cor FROM adms_cors c WHERE c.id=s.adms_cor_id) as corSituacao FROM adms_usuarios a LEFT JOIN adms_usuarios_informacoes i ON i.user_id=a.id INNER JOIN adms_sits_usuarios s ON s.id=a.adms_sits_usuario_id WHERE a.id=:id LIMIT :limit", "id={$_SESSION['colUsuario_id']}&limit=1");
        $this->resultado = $verPerfil->getResultado();

        return $this->resultado;

    }

    public function altPerfil(array $dados)
    {
        /* 1.18.3 (D-498): só os campos do formulário. Antes o POST inteiro ia para o UPDATE: um campo forjado
           (adms_niveis_acesso_id, adms_sits_usuario_id…) mudava o próprio nível. */
        $this->dados = array_intersect_key($dados, array_flip(['nome', 'email', 'genero_id', 'usuario', 'imagem_antiga', 'imagem_nova', 'telefone', 'celular']));
        $this->foto = $this->dados['imagem_nova'];
        $this->imgAntiga = $this->dados['imagem_antiga'];
        $this->telefone = $this->dados['telefone'];
        $this->celular = $this->dados['celular'];
        unset($this->dados['imagem_nova'], $this->dados['imagem_antiga'], $this->dados['telefone'], $this->dados['celular']);
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
        $valEmailSingle->validarEmailUnico($this->dados['email'], 'adms_usuarios', true, $_SESSION['colUsuario_id']); // Onda 3, Etapa 2: faltava a tabela (a consulta falhava e o e-mail repetido passava)
        if($valEmail->getResultado() AND $valEmailSingle->getResultado()){
            $this->valFoto();
        }else{
            $this->resultado = false;
        }

    }

    private function valFoto()
    {
        if (empty($this->foto['name'])) {
            $this->altUsuario();
        } else {
            $slugImg = new \App\adms\Models\helper\AdmsSlug();
            $this->dados['imagem'] = $slugImg->nomeSlug($this->foto['name']);

            $uploadImg = new \App\adms\Models\helper\AdmsUploadImgRed();
            $uploadImg->uploadImagem($this->foto, 'assets/img/usuario/' . $_SESSION['colUsuario_id'] . '/', $this->dados['imagem'], 150, 150);
            if ($uploadImg->getResultado()) {
                $apagarImg = new \App\adms\Models\helper\AdmsApagarImg();
                $apagarImg->apagarImg('assets/img/usuario/' . $_SESSION['colUsuario_id'] . '/' . $this->imgAntiga);
                $this->altUsuario();
            } else {
                $this->altUsuario();
            }
        }
    }

    private function altUsuario()
    {
        $this->dados['telefone'] = (!empty($this->telefone) ? preg_replace('/[^0-9]/', '', $this->telefone) : NULL);
        $this->dados['celular'] = (!empty($this->celular) ? preg_replace('/[^0-9]/', '', $this->celular) : NULL);
        $this->dados['usuario_mod'] = $_SESSION['colUsuario_id'];
        $this->dados['modified'] = date("Y-m-d H:i:s");
        $altPg = new \App\adms\Models\helper\AdmsUpdate();
        $altPg->exeUpdate("adms_usuarios", $this->dados, "WHERE id=:id", "id={$_SESSION['colUsuario_id']}");
        if($altPg->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Perfil editado com sucesso! id={$_SESSION['colUsuario_id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Perfil editado com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível atualizar dados do perfil! id={$_SESSION['colUsuario_id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: ao tentar editar o perfil!</div>";
            $this->resultado = false;
        }

    } 

    public function listarCadastro()
    {

        $listGen = new \App\adms\Models\helper\AdmsRead();
        $listGen->fullRead("SELECT id, nome FROM adms_generos WHERE situacao=:situacao" , "situacao=1");
        $this->resultado['generos'] = $listGen->getResultado();

        return $this->resultado;
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(37, $this->logCodigo, 3, $this->logDescricao);
    }

}