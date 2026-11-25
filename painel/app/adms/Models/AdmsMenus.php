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
                pg.id id_pg, pg.controller, pg.menu_controller, pg.menu_metodo, pg.menu_pagina, pg.nome_pagina, pg.icone icone_pg
                FROM adms_nivacs_pgs nivpg
                INNER JOIN adms_menus men ON men.id=nivpg.adms_menu_id
                INNER JOIN adms_paginas pg ON pg.id=nivpg.adms_pagina_id
                WHERE nivpg.adms_niveis_acesso_id =:adms_niveis_acesso_id
                AND nivpg.permissao =:permissao
                AND nivpg.lib_menu =:lib_menu
                ORDER BY men.ordem, nivpg.ordem ASC", "adms_niveis_acesso_id={$_SESSION['colUsuario_nivel']}&permissao=1&lib_menu=1");
        $this->resultado = $listItemMenu->getResultado();
        /* E8c (D-268): sem módulos, "Módulos" sai do menu (e do Menu Rápido e dos cartões da Página Inicial, que leem daqui) */
        if (is_array($this->resultado) && \App\adms\Models\helper\AdmsModuloInterno::ativo()) {
            $this->resultado = array_values(array_filter($this->resultado, fn($i) => $i['menu_controller'] !== 'modulos'));
        }
        /* o item cuja ação está acima do teto do perfil sai do menu (a rota também recusa: AdmsPaginas::nivelPode) */
        if (is_array($this->resultado)) {
            $tetos = \App\adms\Models\AdmsPaginas::tetos();
            $this->resultado = array_values(array_filter($this->resultado, fn($i) => !\App\adms\Models\helper\AdmsNivel::abaixoDoTeto($tetos[(int) $i['id_pg']] ?? null)));
        }
        /* Onda 4, Etapa 1: o item de um módulo desligado sai do menu (a rota também recusa: AdmsPaginas); na escola, os
           rótulos no vocabulário dela (VcVocabulario: "Cursos" → "Segmentos"…); no superior, como estão */
        if (is_array($this->resultado)) {
            $this->resultado = array_values(array_filter($this->resultado, fn($i) => \App\adms\Models\helper\AdmsTipoInstituicao::paginaLigada((int) $i['id_pg'])));
            if (\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() || \App\adms\Models\helper\AdmsTipoInstituicao::ehLivre()) { // D-106: e o curso livre
                foreach ($this->resultado as &$i) {
                    $i['menu_pagina'] = \App\adms\Models\helper\AdmsTipoInstituicao::traduzir((string) $i['menu_pagina']);
                    $i['nome_pagina'] = \App\adms\Models\helper\AdmsTipoInstituicao::traduzir((string) $i['nome_pagina']);
                    $i['nome_men'] = \App\adms\Models\helper\AdmsTipoInstituicao::traduzir((string) $i['nome_men']);
                }
                unset($i);
            }
        }
        /* Onda 2, Etapa 4: "Começar com os dados da minha instituição" só com a demonstração ligada; "Primeiros passos"
           só enquanto houver fase a fazer e não dispensados */
        if (is_array($this->resultado)) {
            $this->resultado = array_values(array_filter($this->resultado, static function ($i) {
                $r = strtolower($i['menu_controller'] . '/' . $i['menu_metodo']);
                if ($r === 'demonstracao/instituicao') {
                    return \App\adms\Models\helper\AdmsAmbiente::demonstracaoAtiva();
                }
                if ($r === 'primeiros-passos/index') {
                    return \App\adms\Models\helper\AdmsPrimeirosPassos::ativo();
                }
                return true;
            }));
        }
        return $this->resultado;
    }

}