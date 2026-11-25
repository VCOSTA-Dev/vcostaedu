<?php

namespace App\adms\Models\perfil;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsMeuPerfil
{
    private $resultado;

    public function verPerfil()
    {

        $verPerfil = new \App\adms\Models\helper\AdmsRead();
        $verPerfil->fullRead("SELECT a.id, a.nome, a.email, a.telefone, a.imagem, a.celular, DATE_FORMAT(a.created, '%d/%m/%Y %H:%i') as criacao, DATE_FORMAT(a.modified, '%d/%m/%Y %H:%i') as modificado, (SELECT n.nome FROM adms_niveis_acessos n WHERE n.id=a.adms_niveis_acesso_id) as nivelAcesso, i.data_nascimento, i.rg, i.cpf, i.cep, i.endereco, i.numero, i.complemento, i.bairro, i.cidade, i.estado, a.adms_sits_usuario_id situacao, s.nome nomeSituacao, (SELECT c.cor FROM adms_cors c WHERE c.id=s.adms_cor_id) as corSituacao FROM adms_usuarios a LEFT JOIN adms_usuarios_informacoes i ON i.user_id=a.id INNER JOIN adms_sits_usuarios s ON s.id=a.adms_sits_usuario_id WHERE a.id=:id LIMIT :limit", "id={$_SESSION['colUsuario_id']}&limit=1");
        $this->resultado = $verPerfil->getResultado();

        return $this->resultado;

    }

}