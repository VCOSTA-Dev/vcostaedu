<?php

namespace App\adms\Models\helper;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsValAcesso
{
    private $dados;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function acessoDisciplina()
    {
        /* E5: administrador (1) e secretaria (4) veem TODAS as disciplinas. Antes, só quem
           estava em rm_professores_materias_administradores (vazia no recorte): a lista
           "Todas as Disciplinas" vinha vazia para eles. */
        /* Onda 2, Etapa 2: pelo papel — administrador, direção, supervisão e secretaria (antes: os níveis 1, 2 e 4) */
        if (\App\adms\Models\helper\AdmsNivel::ehPapel('administrador', 'direcao', 'supervisao_secretaria', 'secretaria')) {
            $this->resultado = true;
            return;
        }
        $this->listaAdmDisciplina();
    }

    private function listaAdmDisciplina()
    {
        $listUsuarios = new \App\adms\Models\helper\AdmsRead();
        $listUsuarios->fullRead("SELECT usuario_id FROM rm_professores_materias_administradores WHERE situacao=:sit ", "sit=1");
        if($listUsuarios->getResultado()){
            $this->dados = $listUsuarios->getResultado();
            $this->checarLista();
        }else{
            $this->resultado = false;
        }
    }

    private function checarLista()
    {
        if(in_array($_SESSION['colUsuario_id'], array_column($this->dados, 'usuario_id'), true)){
            $this->resultado = true;
        }else{
            $this->resultado = false;
        }
    }

    public function acessoCoordenacao()
    {
        $listCoordeSistema = new \App\adms\Models\helper\AdmsRead();
        $listCoordeSistema->fullRead("SELECT usuario_id FROM rm_cursos_coordenacao WHERE situacao=:sit", "sit=1");
        if($listCoordeSistema->getResultado()){
            $coordenadores = $listCoordeSistema->getResultado();
            /* Onda 2: compara como número (a sessão e o banco não devolvem o mesmo tipo; com o === estrito o coordenador nunca era achado) */
            if(in_array((int) $_SESSION['colUsuario_id'], array_map('intval', array_column($coordenadores, 'usuario_id')), true)){
                $this->resultado = true;
            }else{
                $this->resultado = false;
            }
        }else{
            $this->resultado = false;
        }
    }

}
?>