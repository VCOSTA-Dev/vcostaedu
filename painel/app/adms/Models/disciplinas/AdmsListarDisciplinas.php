<?php

namespace App\adms\Models\disciplinas;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsListarDisciplinas
{
    private $dadosId;
    private $ano;
    private $resultado;

    public function listaDisciplinas($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $this->anoPeriodo();
        $this->disciplinas();
        return $this->resultado;
    }

    private function anoPeriodo()
    {   
        if(!empty($this->dadosId)){
            $this->ano = $this->dadosId;
        }else{
            $listCurso = new \App\adms\Models\helper\AdmsRead();
            $listCurso->fullRead("SELECT ano FROM adms_periodos_letivos WHERE situacaodisciplina =:sit ORDER by id DESC LIMIT :limit", "sit=1&limit=1");
            $buscaAno = $listCurso->getResultado();
            $this->ano = ((isset($buscaAno) && !empty($buscaAno)) ? $buscaAno[0]['ano'] : date("Y"));
        }
        $this->resultado['anoPeriodo'] = $this->ano;
    }

    private function disciplinas()
    {
        $ano = $this->ano;
        $listCurso = new \App\adms\Models\helper\AdmsRead();
        $verAcesso = new \App\adms\Models\helper\AdmsValAcesso();
        $verAcesso->acessoDisciplina();
        if($verAcesso->getResultado()){
            $listCurso->fullRead("SELECT m.id, (SELECT c.nome FROM rm_cursos c WHERE c.id=m.curso_id) as curso, 'Padrão' as grade, m.periodo, m.codmat, m.codtur, m.materia, m.encerrada_em, (SELECT COUNT(*) FROM portal_disciplinas_alunos pa WHERE pa.rm_prof_mat_id = m.id) AS alunos, m.bimestre, m.situacao, s.nome nomeSituacao, (SELECT o.cor FROM adms_cors o WHERE o.id=s.cor_id) as corSituacao, (SELECT GROUP_CONCAT(u.id SEPARATOR ',') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id=l.usuario_id WHERE l.rm_prof_mat_id=m.id AND l.acesso=:ace AND l.situacao=:sit) as professoresId , (SELECT GROUP_CONCAT(u.nome SEPARATOR '<br>') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id=l.usuario_id WHERE l.rm_prof_mat_id=m.id AND l.acesso=:ace AND l.situacao=:sit) as professores FROM rm_professores_materias m INNER JOIN adms_sits_pgs s ON s.id=m.situacao WHERE m.ano =:ano", "ace=1&sit=1&ano={$ano}");
        }else{
            $verCoordenadoresAcesso = new \App\adms\Models\helper\AdmsValAcesso();
            $verCoordenadoresAcesso->acessoCoordenacao();
            if($verCoordenadoresAcesso->getResultado()){
                $listCurso->fullRead("SELECT m.id, (SELECT c.nome FROM rm_cursos c WHERE c.id=m.curso_id) as curso, 'Padrão' as grade, m.periodo, m.codmat, m.codtur, m.materia, m.encerrada_em, (SELECT COUNT(*) FROM portal_disciplinas_alunos pa WHERE pa.rm_prof_mat_id = m.id) AS alunos,  m.bimestre, m.situacao, s.nome nomeSituacao, (SELECT o.cor FROM adms_cors o WHERE o.id=s.cor_id) as corSituacao, (SELECT GROUP_CONCAT(u.id SEPARATOR ',') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id=l.usuario_id WHERE l.rm_prof_mat_id=m.id AND l.acesso=:ace AND l.situacao=:sit) as professoresId , (SELECT GROUP_CONCAT(u.nome SEPARATOR '<br>') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id=l.usuario_id WHERE l.rm_prof_mat_id=m.id AND l.acesso=:ace AND l.situacao=:sit) as professores FROM rm_professores_materias m INNER JOIN adms_sits_pgs s ON s.id=m.situacao INNER JOIN rm_cursos_coordenacao d ON d.curso_id=m.curso_id WHERE m.ano =:ano AND d.usuario_id=:user AND d.situacao=:sitprof", "ace=1&sit=1&ano={$ano}&user={$_SESSION['colUsuario_id']}&sitprof=1");
            }else{
                $listCurso->fullRead("SELECT m.id, (SELECT c.nome FROM rm_cursos c WHERE c.id=m.curso_id) as curso, 'Padrão' as grade, m.periodo, m.codmat, m.codtur, m.materia, m.encerrada_em, (SELECT COUNT(*) FROM portal_disciplinas_alunos pa WHERE pa.rm_prof_mat_id = m.id) AS alunos,  m.bimestre, m.situacao, s.nome nomeSituacao, (SELECT o.cor FROM adms_cors o WHERE o.id=s.cor_id) as corSituacao, (SELECT GROUP_CONCAT(u.id SEPARATOR ',') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id=l.usuario_id WHERE l.rm_prof_mat_id=m.id AND l.acesso=:ace AND l.situacao=:sit) as professoresId , (SELECT GROUP_CONCAT(u.nome SEPARATOR '<br>') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id=l.usuario_id WHERE l.rm_prof_mat_id=m.id AND l.acesso=:ace AND l.situacao=:sit) as professores FROM rm_professores_materias m INNER JOIN adms_sits_pgs s ON s.id=m.situacao INNER JOIN rm_professores_mat_liga p ON p.rm_prof_mat_id=m.id WHERE m.ano =:ano AND p.usuario_id=:user AND p.acesso =:ace AND p.situacao=:sitprof", "ace=1&sit=1&ano={$ano}&user={$_SESSION['colUsuario_id']}&sitprof=1");
            }
        }
        $this->resultado['disciplinas'] = $listCurso->getResultado();
    }

    public function listarCadastro()
    {
        $listAnoPeriodos = new \App\adms\Models\helper\AdmsRead();
        $listAnoPeriodos->fullRead("SELECT id, ano FROM adms_periodos_letivos WHERE situacaodisciplina=:sit ORDER BY ano asc", "sit=1");
        $this->resultado['periodos'] = $listAnoPeriodos->getResultado();
        
        $listProfessores = new \App\adms\Models\helper\AdmsRead();
        $listProfessores->fullRead("SELECT id, nome FROM adms_usuarios WHERE adms_sits_usuario_id=:sit AND id>:adm ORDER BY nome asc", "adm=1&sit=1");
        $this->resultado['professores'] = $listProfessores->getResultado();

        return $this->resultado;
    }

}