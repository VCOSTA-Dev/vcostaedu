<?php

namespace App\adms\Models\materias;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsListarMaterias
{
    private $resultado;

    public function listMaterias()
    {
        $listMaterias = new \App\adms\Models\helper\AdmsRead();
        $listMaterias->fullRead("SELECT m.id, m.nome, m.abreviacao, m.carga_horaria, m.situacao AS situacaoId, s.nome nomeSituacao, (SELECT c.cor FROM adms_cors c WHERE c.id=s.cor_id) as corSituacao FROM rm_materias m INNER JOIN adms_sits_pgs s ON s.id=m.situacao WHERE m.situacao <>:sit ORDER BY m.nome", "sit=5"); // 1.18.1 (D-482): a ordem do servidor também (a collation ignora acento)
        $this->resultado = $listMaterias->getResultado();
        return $this->resultado;
    }

}