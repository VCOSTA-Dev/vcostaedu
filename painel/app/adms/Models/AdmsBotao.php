<?php

namespace App\adms\Models;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsBotao
{
    private $Resultado;
    private $Botao;
    private $BotaoValido = [];
   
    function getResultado()
    {
        return $this->Resultado;
    }
            
    public function valBotao(array $Botao)
    {
        $this->Botao = $Botao;
        foreach ($this->Botao as $key => $botao_unico) {
            extract($botao_unico);
            $verBotao = new \App\adms\Models\helper\AdmsRead();
            $verBotao->fullRead("SELECT pg.id id_pg, pg.controller
                    FROM adms_paginas pg
                    LEFT JOIN adms_nivacs_pgs nivpg ON nivpg.adms_pagina_id=pg.id
                    WHERE pg.menu_controller =:menu_controller
                    AND pg.menu_metodo =:menu_metodo
                    AND pg.adms_sits_pg_id = 1
                    AND nivpg.adms_niveis_acesso_id =:adms_niveis_acesso_id
                    AND nivpg.permissao= 1 LIMIT :limit", "menu_controller=$menu_controller&menu_metodo=$menu_metodo&adms_niveis_acesso_id=".$_SESSION['colUsuario_nivel']."&limit=1");
            
            $achou = $verBotao->getResultado();
            /* Onda 2, Etapa 2: a mesma trava da rota — o botão que grava some para quem só consulta, e o sensível para
               quem não tem as ações sensíveis */
            /* Onda 8, correção, segunda leva, 2.1 (D-87): e o módulo da página — o botão de uma página do outro tipo de
               instituição some (a rota já a recusa; antes, o botão aparecia e levava à página sem acesso) */
            if ($achou && (!\App\adms\Models\AdmsPaginas::nivelPode(\App\adms\Models\AdmsPaginas::marcasDaPagina((int) $achou[0]['id_pg']))
                || !\App\adms\Models\helper\AdmsTipoInstituicao::paginaLigada((int) $achou[0]['id_pg']))) {
                $achou = [];
            }
            if($achou){
                $this->BotaoValido[$key] = true;
            }else{
                $this->BotaoValido[$key] = false;
            }
        }    
        return $this->BotaoValido;
    }
}
