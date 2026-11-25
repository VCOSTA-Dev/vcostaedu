<?php

namespace App\adms\Models\perfil;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsEditarPerfil
{
    private $dados;
    private $dadosCol;
    private $dadoslogs;
    private $foto;
    private $imgAntiga;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function verPerfil()
    {

        $verPerfil = new \App\adms\Models\helper\AdmsRead();
        $verPerfil->fullRead("SELECT a.id, a.nome, a.usuario, a.imagem, a.email, a.telefone, a.celular, DATE_FORMAT(a.created, '%d/%m/%Y') as criacao, (SELECT n.nome FROM col_niveis_acessos n WHERE n.id=a.col_niveis_acesso_id) as nivelAcesso, a.genero_id, i.nasc_data, i.nasc_local, i.nasc_uf,i.nasc_nac,i.rg_escolar,i.rg,i.rg_org,i.rg_uf,i.rg_data_exp,i.cpf,i.estado_civil,i.raca,i.ocupacao,i.conjuge, a.col_niveis_acesso_id as acessoId, a.col_sits_usuario_id, d.endereco, d.numero, d.complemento, d.bairro, d.cidade, d.uf, d.cep FROM col_usuarios a LEFT JOIN col_usuarios_informacoes i ON i.col_user_id=a.id LEFT JOIN col_usuarios_enderecos d ON d.usuario_id=a.id AND d.situacao=1 WHERE a.id=:id", "id={$_SESSION['academico_id']}");
        $this->resultado = $verPerfil->getResultado();

        return $this->resultado;

    }

    public function altPerfil(array $dados)
    {
        $this->dados = $dados;
        /* Onda 2 (achado herdado): o aluno só edita o PRÓPRIO perfil — o id vinha do formulário e podia ser trocado;
           e o nome da foto antiga, que vai para o apagar, não pode carregar caminho. */
        $this->dados['id'] = (int) ($_SESSION['academico_id'] ?? 0);
        $this->foto = $this->dados['imagem_nova'];
        $this->imgAntiga = basename((string) ($this->dados['imagem_antiga'] ?? ''));
        unset($this->dados['imagem_nova'], $this->dados['imagem_antiga']);
        $this->valInput();
    }

    private function valInput()
    {
        if(!empty($this->dados['email'])){
            $valEmail = new \App\adms\Models\helper\AdmsValEmail();
            $valEmail->validarEmail($this->dados['email']);
            $valEmailSingle = new \App\adms\Models\helper\AdmsValEmailUnico();
            $valEmailSingle->validarEmailUnico($this->dados['email'], 'col_usuarios', true, $this->dados['id']);
            
            if($valEmail->getResultado() AND $valEmailSingle->getResultado()){
                $this->valFoto();
            }else{
                $this->resultado = false;
            }
        }else{
            $this->valFoto();
        }

    }

    private function valFoto()
    {
        if (empty($this->foto['name'])) {
            $this->separacaoUsuario();
        } else {
            $slugImg = new \App\adms\Models\helper\AdmsSlug();
            $this->dadosCol['imagem'] = $slugImg->nomeSlug($this->foto['name']);
            $uploadImg = new \App\adms\Models\helper\AdmsUploadImgRed();
            $uploadImg->uploadImagem($this->foto, 'assets/img/academicos/' . $this->dados['id'] . '/', $this->dadosCol['imagem'], 150, 150);
            if ($uploadImg->getResultado()) {
                $apagarImg = new \App\adms\Models\helper\AdmsApagarImg();
                $apagarImg->apagarImg('assets/img/academicos/' . $this->dados['id'] . '/' . $this->imgAntiga);
                $this->separacaoUsuario();
            } else {
                $this->separacaoUsuario();
            }    
        }
    }

    private function separacaoUsuario()
    {
        
        $this->dadosCol['id'] = $this->dados['id'];
        $this->dadosCol['nome'] = $this->dados['nome']; 
        $this->dadosCol['email'] = $this->dados['email'];
        /* Onda 3, Etapa 2 (o modo estrito): o formulário não manda "usuario" (o login legado, que espelha o RA) — gravar o
           que não veio punha NULL numa coluna obrigatória, e o banco sem o modo estrito o trocava por vazio em silêncio.
           O usuário não muda pelo perfil; o sexo só muda com um valor válido. */
        if (in_array((int) ($this->dados['genero_id'] ?? 0), [1, 2, 3], true)) {
            $this->dadosCol['genero_id'] = (int) $this->dados['genero_id'];
        }
        $this->dadosCol['telefone'] = (!empty($this->dados['telefone']) ? preg_replace('/[^0-9]/', '', $this->dados['telefone']) : NULL);
        $this->dadosCol['celular'] = (!empty($this->dados['celular']) ? preg_replace('/[^0-9]/', '', $this->dados['celular']) : NULL);
        $this->altUsuario();
        
    }

    private function altUsuario()
    {
        $this->dadosCol['modified'] = date("Y-m-d H:i:s");
        $altUser = new \App\adms\Models\helper\AdmsUpdate();
        $altUser->exeUpdate("col_usuarios", $this->dadosCol, "WHERE id=:id", "id={$this->dadosCol['id']}");
        if($altUser->getResultado()){
            $this->dadoslogs['codigo'] = 200;
            $this->dadoslogs['observacao'] = "Aluno Atualizado com sucesso!";
            $this->dadoslogs['descricao'] = json_encode($this->dadosCol, true);
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Perfil atualizado com sucesso!</div>";
            $this->resultado = true;
            \App\adms\Models\moodle\AdmsMoodlePortal::anotarAluno((int) $this->dadosCol['id']); // Onda 3, Etapa 8: o nome e o e-mail novos, pela fila
        }else{
            $this->dadoslogs['codigo'] = 400;
            $this->dadoslogs['observacao'] = "Não foi possível atualizar os dados!";
            $this->dadoslogs['descricao'] = json_encode($this->dadosCol, true);
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não é possível atualizar os dados" . (($m = (class_exists('VcBanco', false) ? \VcBanco::motivo() : '')) !== '' ? ': ' . htmlspecialchars($m, ENT_QUOTES, 'UTF-8') : '') . ".</div>"; // Onda 3, Etapa 2: o motivo
            $this->resultado = false;
        }

    }

    private function salvarLog()
    {
        $this->dadoslogs['pagina_id'] = 7;
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLogs();
        $valLogsalvar->salvarLogs($this->dadoslogs);
    }

    public function listarCadastro()
    {

        $listGen = new \App\adms\Models\helper\AdmsRead();
        $listGen->fullRead("SELECT id, nome FROM adms_generos WHERE situacao=:situacao" , "situacao=1");
        $this->resultado['generos'] = $listGen->getResultado();

        return $this->resultado;
    }

}