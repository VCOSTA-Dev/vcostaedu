<?php

namespace App\adms\Models;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsMenus
{
    private $resultado;

    public function itemMenu()
    {
        $listItemMenu = new \App\adms\Models\helper\AdmsRead();
        $listItemMenu->fullRead("SELECT nivpg.dropdown,
                men.id id_men, men.nome nome_men, men.icone icone_men,
                pg.id id_pg, pg.menu_controller, pg.menu_metodo, pg.menu_pagina, pg.nome_pagina, pg.icone icone_pg
                FROM col_nivacs_pgs nivpg
                INNER JOIN col_menus men ON men.id=nivpg.adms_menu_id
                INNER JOIN col_paginas pg ON pg.id=nivpg.adms_pagina_id
                WHERE nivpg.adms_niveis_acesso_id =:adms_niveis_acesso_id
                AND nivpg.permissao =:permissao
                AND nivpg.lib_menu =:lib_menu
                ORDER BY men.ordem, nivpg.ordem ASC", "adms_niveis_acesso_id=".$_SESSION['academico_nivel']."&permissao=1&lib_menu=1");
        $this->resultado = $listItemMenu->getResultado();
        /* Fechamento (D-106): o item de uma página que não vale neste tipo de instituição sai do menu (no curso livre, o
           aluno tem a Página Inicial, o Moodle e a Ajuda); o endereço direto leva à Página Inicial (ConfigController) */
        if (is_array($this->resultado)) {
            $this->resultado = array_values(array_filter($this->resultado, fn($i) => \App\adms\Models\helper\AdmsTipoPortal::paginaLigada((int) $i['id_pg'])));
        }
        return $this->resultado;       
    }

}