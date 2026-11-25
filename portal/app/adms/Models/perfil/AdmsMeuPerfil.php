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
        $verPerfil->fullRead("SELECT a.id, a.nome, a.email, a.imagem, a.telefone, a.celular, DATE_FORMAT(a.created, '%d/%m/%Y') as criacao, (SELECT n.nome FROM col_niveis_acessos n WHERE n.id=a.col_niveis_acesso_id) as nivelAcesso, i.nasc_data, i.nasc_local, i.nasc_uf,i.nasc_nac,i.rg_escolar,i.rg,i.rg_org,i.rg_uf,i.rg_data_exp,i.cpf,i.estado_civil,i.raca,i.ocupacao,i.conjuge, a.col_niveis_acesso_id as acessoId, a.col_sits_usuario_id, d.endereco, d.numero, d.complemento, d.bairro, d.cidade, d.uf, d.cep FROM col_usuarios a LEFT JOIN col_usuarios_informacoes i ON i.col_user_id=a.id LEFT JOIN col_usuarios_enderecos d ON d.usuario_id=a.id AND d.situacao=1 WHERE a.id=:id LIMIT :limit", "id={$_SESSION['academico_id']}&limit=1");
        $this->resultado = $verPerfil->getResultado();

        return $this->resultado;

    }

}