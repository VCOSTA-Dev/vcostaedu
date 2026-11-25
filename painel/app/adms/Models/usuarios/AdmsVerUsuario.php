<?php

namespace App\adms\Models\usuarios;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsVerUsuario
{
    private $dadosId;
    private $resultado;

    public function visUsuario($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $verUser = new \App\adms\Models\helper\AdmsRead();
        $verUser->fullRead("SELECT a.id, a.nome, a.email, a.telefone, a.celular, DATE_FORMAT(a.created, '%d/%m/%Y %H:%i') as criacao, DATE_FORMAT(a.modified, '%d/%m/%Y %H:%i') as atualizado, (SELECT n.nome FROM adms_niveis_acessos n WHERE n.id=a.adms_niveis_acesso_id) as nivelAcesso, a.imagem, s.nome nomeSituacao, (SELECT c.cor FROM adms_cors c WHERE c.id=s.adms_cor_id) as corSituacao, i.data_nascimento, i.rg, i.cpf, i.cep, i.endereco, i.numero, i.complemento, i.bairro, i.cidade, i.estado FROM adms_usuarios a INNER JOIN adms_sits_usuarios s ON s.id=a.adms_sits_usuario_id LEFT JOIN adms_usuarios_informacoes i ON i.user_id=a.id WHERE a.id=:id LIMIT :limit", "id={$this->dadosId}&limit=1");
        $this->resultado = $verUser->getResultado();

        return $this->resultado;
    }

}