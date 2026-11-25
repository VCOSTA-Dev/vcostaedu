<?php

namespace App\adms\Models\academicos;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsVerUsuarioAcademico
{
    private $dadosId;
    private $dadosUsuario;
    private $resultado;

    public function visUsuario($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $this->informacao();
        /* Na VCOSTAEDU o usuário do portal é o próprio aluno (D-00b): sem
           parentes, responsáveis nem prontuário. */
        $this->resultado['info'] = $this->dadosUsuario;
        return $this->resultado;
    }

    private function informacao()
    {
        $verUser = new \App\adms\Models\helper\AdmsRead();
        $verUser->fullRead("SELECT a.id, a.rm, a.nome, a.nome_social, a.email, a.telefone, a.genero_id as genero, a.imagem, a.celular, DATE_FORMAT(a.created, '%d/%m/%Y') as criacao, DATE_FORMAT(a.modified, '%d/%m/%Y') as modificado, a.col_niveis_acesso_id acessoId, (SELECT n.nome FROM col_niveis_acessos n WHERE n.id=a.col_niveis_acesso_id) as nivelAcesso, i.nasc_data, i.nasc_local, i.nasc_uf, i.nasc_nac, i.rg_escolar, i.rg, i.rg_org, i.rg_uf, i.rg_data_exp, i.cpf, i.estado_civil, i.raca, i.ocupacao, i.conjuge, i.nome_mae, i.nome_pai, e.endereco, e.numero, e.complemento, e.bairro, e.cidade, e.uf, e.cep, s.nome nomeSituacao, s.cores as corSituacao FROM col_usuarios a INNER JOIN col_situacao s ON s.id=a.col_sits_usuario_id LEFT JOIN col_usuarios_informacoes i ON i.col_user_id=a.id LEFT JOIN col_usuarios_enderecos e ON e.usuario_id=a.id AND e.situacao =:sit WHERE a.id=:id LIMIT :limit", "id={$this->dadosId}&sit=1&limit=1");
        $this->dadosUsuario = $verUser->getResultado();
        
    }

    /* E3b: a lista de matrículas por (curso, ordem) saiu; a ficha lê o vínculo por id
       (AdmsListarVinculos: rm_matriculas_curso + rm_matriculas_ensino.modulo_id). */
}
