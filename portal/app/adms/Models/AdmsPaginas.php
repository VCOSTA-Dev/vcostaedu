<?php

namespace App\adms\Models;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsPaginas
{
    private $resultado;
    private $urlController;
    private $urlMetodo;

    public function listarPaginas($urlController = null, $urlMetodo = null)
    {
        if(!isset($_SESSION['academico_nivel'])){
            $_SESSION['academico_nivel'] = null;
        }
        $this->urlController = (string) $urlController;
        $this->urlMetodo = (string) $urlMetodo;
        $listar = new \App\adms\Models\helper\AdmsRead();
        $listar->fullRead("SELECT pg.id,
                tpg.tipo tipo_tpg
                FROM col_paginas pg
                INNER JOIN adms_tps_pgs tpg ON tpg.id=pg.adms_tps_pg_id
                LEFT JOIN col_nivacs_pgs nivpg ON nivpg.adms_pagina_id=pg.id AND nivpg.adms_niveis_acesso_id =:adms_niveis_acesso_id
                WHERE (pg.controller =:controller
                AND pg.metodo =:metodo) AND ((pg.lib_pub =:lib_pub) OR (nivpg.permissao =:permissao))
                LIMIT :limit", "adms_niveis_acesso_id={$_SESSION['academico_nivel']}&controller={$this->urlController}&metodo={$this->urlMetodo}&lib_pub=1&permissao=1&limit=1");
        $this->resultado = $listar->getResultado();
        return $this->resultado;        
    }

}