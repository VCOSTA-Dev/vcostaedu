<?php

namespace App\adms\Models\horarioAula;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsListarHorarioAula
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
            $listCurso->fullRead("SELECT m.id, (SELECT c.nome FROM rm_cursos c WHERE c.id=m.curso_id) as curso, 'Padrão' as grade, m.periodo, m.codmat, m.codtur, m.materia, m.bimestre, (SELECT GROUP_CONCAT(r.nome SEPARATOR ',') FROM rm_professores_materias_salas z INNER JOIN rm_salas r ON r.id=z.sala_id WHERE z.rm_prof_mat_id=m.id AND z.situacao=:sit) as salas, (SELECT GROUP_CONCAT(v.sala_id SEPARATOR ',') FROM rm_professores_materias_salas v WHERE v.rm_prof_mat_id=m.id AND v.situacao=:sit) as salasId, m.situacao, s.nome nomeSituacao, (SELECT o.cor FROM adms_cors o WHERE o.id=s.cor_id) as corSituacao, (SELECT GROUP_CONCAT(u.id SEPARATOR ',') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id=l.usuario_id WHERE l.rm_prof_mat_id=m.id AND l.acesso=:ace AND l.situacao=:sit) as professoresId , (SELECT GROUP_CONCAT(u.nome SEPARATOR '<br>') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id=l.usuario_id WHERE l.rm_prof_mat_id=m.id AND l.acesso=:ace AND l.situacao=:sit) as professores, (SELECT GROUP_CONCAT(DISTINCT d.dia ORDER BY d.id ASC SEPARATOR ' | ') FROM rm_professores_horarios h INNER JOIN adms_dia_semana d ON d.id=h.dia_semana WHERE h.rm_prof_mat_id=m.id) as dia, (SELECT f.nome FROM rm_cursos g INNER JOIN rm_cursos_periodos f ON f.id=g.periodo WHERE g.id=m.curso_id) as periodoAula, IFNULL(m.modalidade_id, (SELECT g2.modalidade_id FROM rm_cursos g2 WHERE g2.id=m.curso_id)) as modalidadeOferta, (SELECT COUNT(a.rm) FROM portal_disciplinas_alunos a WHERE a.rm_prof_mat_id =m.id AND a.situacao =:sit) as alunos FROM rm_professores_materias m INNER JOIN adms_sits_pgs s ON s.id=m.situacao WHERE m.ano =:ano", "ace=1&sit=1&ano={$ano}");
        }else{
            $listCurso->fullRead("SELECT m.id, (SELECT c.nome FROM rm_cursos c WHERE c.id=m.curso_id) as curso, 'Padrão' as grade, m.periodo, m.codmat, m.codtur, m.materia, m.bimestre, (SELECT GROUP_CONCAT(r.nome SEPARATOR ',') FROM rm_professores_materias_salas z INNER JOIN rm_salas r ON r.id=z.sala_id WHERE z.rm_prof_mat_id=m.id AND z.situacao=:sit) as salas, (SELECT GROUP_CONCAT(v.sala_id SEPARATOR ',') FROM rm_professores_materias_salas v WHERE v.rm_prof_mat_id=m.id AND v.situacao=:sit) as salasId, m.situacao, s.nome nomeSituacao, (SELECT o.cor FROM adms_cors o WHERE o.id=s.cor_id) as corSituacao, (SELECT GROUP_CONCAT(u.id SEPARATOR ',') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id=l.usuario_id WHERE l.rm_prof_mat_id=m.id AND l.acesso=:ace AND l.situacao=:sit) as professoresId , (SELECT GROUP_CONCAT(u.nome SEPARATOR '<br>') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id=l.usuario_id WHERE l.rm_prof_mat_id=m.id AND l.acesso=:ace AND l.situacao=:sit) as professores, (SELECT GROUP_CONCAT(DISTINCT d.dia ORDER BY d.id ASC SEPARATOR ' | ') FROM rm_professores_horarios h INNER JOIN adms_dia_semana d ON d.id=h.dia_semana WHERE h.rm_prof_mat_id=m.id) as dia, (SELECT f.nome FROM rm_cursos g INNER JOIN rm_cursos_periodos f ON f.id=g.periodo WHERE g.id=m.curso_id) as periodoAula, IFNULL(m.modalidade_id, (SELECT g2.modalidade_id FROM rm_cursos g2 WHERE g2.id=m.curso_id)) as modalidadeOferta, (SELECT COUNT(a.rm) FROM portal_disciplinas_alunos a WHERE a.rm_prof_mat_id =m.id AND a.situacao =:sit) as alunos FROM rm_professores_materias m INNER JOIN adms_sits_pgs s ON s.id=m.situacao INNER JOIN rm_professores_mat_liga p ON p.rm_prof_mat_id=m.id WHERE m.ano =:ano AND p.usuario_id=:user AND p.acesso =:ace AND p.situacao=:sitprof", "ace=1&sit=1&ano={$ano}&user={$_SESSION['colUsuario_id']}&sitprof=1");
        }
        $this->resultado['disciplinas'] = $listCurso->getResultado();
    }

    public function listarCadastro()
    {
        $listAnoPeriodos = new \App\adms\Models\helper\AdmsRead();
        $listAnoPeriodos->fullRead("SELECT id, ano FROM adms_periodos_letivos WHERE situacao=:sit ORDER BY ano DESC", "sit=1");
        $this->resultado['periodos'] = $listAnoPeriodos->getResultado();
        
        $listSalas = new \App\adms\Models\helper\AdmsRead();
        $listSalas->fullRead("SELECT id, nome FROM rm_salas WHERE situacao=:sit ", "sit=1");
        $this->resultado['salas'] = $listSalas->getResultado();

        return $this->resultado;
    }

}