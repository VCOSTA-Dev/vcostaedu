<?php

namespace App\adms\Models\usuarios;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsEditarUsuario
{
    private $titulacao; // E6a
    private $dados;
    private $dadosId;
    private $logCodigo;
    private $logDescricao;
    private $foto;
    private $imgAntiga;
    private $telefone;
    private $celular;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function verUsuario($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $visUser = new \App\adms\Models\helper\AdmsRead();
        $visUser->fullRead("SELECT a.*, b.nome nomeSituacao, (SELECT n.nome FROM adms_niveis_acessos n WHERE n.id=a.adms_niveis_acesso_id) as nivelAcesso, (SELECT c.cor FROM adms_cors c WHERE c.id=b.adms_cor_id) as corSituacao FROM adms_usuarios a INNER JOIN adms_sits_usuarios b ON b.id=a.adms_sits_usuario_id WHERE a.id=:id LIMIT :limit", "id={$this->dadosId}&limit=1");
        $this->resultado = $visUser->getResultado();

        return $this->resultado;
    }

    public function editUsuarios(array $dados)
    {
        /* 1.18.3 (D-497/D-498): só os campos do formulário, e só para usuário e nível ABAIXO do seu (vale para o POST forjado) */
        $this->dados = array_intersect_key($dados, array_flip(['id', 'nome', 'email', 'genero_id', 'usuario', 'adms_niveis_acesso_id', 'adms_sits_usuario_id', 'titulacao_id', 'imagem_antiga', 'imagem_nova', 'telefone', 'celular']));
        if (!\App\adms\Models\helper\AdmsHierarquia::podeAlterarUsuario((int) ($this->dados['id'] ?? 0)) || !in_array((int) ($this->dados['adms_niveis_acesso_id'] ?? 0), \App\adms\Models\helper\AdmsHierarquia::niveisAtribuiveis(), true)) {
            \App\adms\Models\helper\AdmsHierarquia::recusar();
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
        $valEmailSingle->validarEmailUnico($this->dados['email'], 'adms_usuarios', true, $this->dados['id']);
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
            $uploadImg->uploadImagem($this->foto, 'assets/img/usuario/' . $this->dados['id'] . '/', $this->dados['imagem'], 150, 150);
            if ($uploadImg->getResultado()) {
                $apagarImg = new \App\adms\Models\helper\AdmsApagarImg();
                $apagarImg->apagarImg('assets/img/usuario/' . $this->dados['id'] . '/' . $this->imgAntiga);
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
        $this->dados['titulacao_id'] = $this->titulacao; // E6a
        $this->dados['usuario_mod'] = $_SESSION['colUsuario_id'];
        $this->dados['modified'] = date("Y-m-d H:i:s");
        $altPg = new \App\adms\Models\helper\AdmsUpdate();
        $altPg->exeUpdate("adms_usuarios", $this->dados, "WHERE id=:id", "id={$this->dados['id']}");
        if($altPg->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Usuário atualizado com sucesso! id={$this->dados['id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Usuário editado com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível editar usuário! id={$this->dados['id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: ao tentar editar o usuário!</div>";
            $this->resultado = false;
        }

    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(11, $this->logCodigo, 3, $this->logDescricao);
    }

}