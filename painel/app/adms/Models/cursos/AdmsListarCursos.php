<?php

namespace App\adms\Models\cursos;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsListarCursos
{
    private $resultado;

    public function listCursos()
    {
        $listCurso = new \App\adms\Models\helper\AdmsRead();
        $listCurso->fullRead("SELECT c.id, c.nome, c.descricao, c.periodo, c.modalidade_id, c.situacao AS situacaoId, s.nome nomeSituacao, 'padrão' as grade, (SELECT GROUP_CONCAT(u.id SEPARATOR ',') FROM rm_cursos_coordenacao co INNER JOIN adms_usuarios u ON u.id=co.usuario_id WHERE co.curso_id=c.id AND co.posicao_id=1 AND co.situacao=1) as coordenadoresId, (SELECT GROUP_CONCAT(u.nome SEPARATOR '<br>') FROM rm_cursos_coordenacao co INNER JOIN adms_usuarios u ON u.id=co.usuario_id WHERE co.curso_id=c.id AND co.posicao_id=1 AND co.situacao=1) as coordenadores, (SELECT o.cor FROM adms_cors o WHERE o.id=s.cor_id) as corSituacao FROM rm_cursos c INNER JOIN adms_sits_pgs s ON s.id=c.situacao WHERE c.situacao <>:sit" . \App\adms\Models\helper\AdmsEscopo::sql('c.id'), "sit=5"); // Onda 2: o recorte do coordenador
        $this->resultado = $listCurso->getResultado();
        return $this->resultado;
    }

    public function listCadastro()
    {
        $listProf = new \App\adms\Models\helper\AdmsRead();
        $listProf->fullRead("SELECT id, nome FROM adms_usuarios WHERE adms_sits_usuario_id=:sit ORDER BY nome asc", "sit=1");
        $this->resultado['coordenadores'] = $listProf->getResultado();

        $listPosicoes = new \App\adms\Models\helper\AdmsRead();
        $listPosicoes->fullRead("SELECT id, nome FROM rm_cursos_posicao WHERE situacao=:sit", "sit=1");
        $this->resultado['posicoes'] = $listPosicoes->getResultado();

        return $this->resultado;
    }

}