<?php

namespace App\adms\Models\disciplinas;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsListarMinhasDisciplinas
{
    private $dadosId;
    private $userid;
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
        /* Onda 2, Etapa 2: a Coordenação de curso vê as ofertas dos cursos que coordena (AdmsEscopo) */
        if($verAcesso->getResultado() || \App\adms\Models\helper\AdmsEscopo::recortado()){
            $listCurso->fullRead("SELECT m.id, (SELECT c.nome FROM rm_cursos c WHERE c.id=m.curso_id) as curso, 'Padrão' as grade, m.periodo, m.codmat, m.codtur, m.materia, m.encerrada_em, (SELECT COUNT(*) FROM portal_disciplinas_alunos pa WHERE pa.rm_prof_mat_id = m.id AND pa.situacao = 1) AS alunos, m.bimestre, m.situacao, s.nome nomeSituacao, (SELECT o.cor FROM adms_cors o WHERE o.id=s.cor_id) as corSituacao, (SELECT GROUP_CONCAT(u.id SEPARATOR ',') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id=l.usuario_id WHERE l.rm_prof_mat_id=m.id AND l.acesso=:ace AND l.situacao=:sit) as professoresId , (SELECT GROUP_CONCAT(u.nome SEPARATOR '<br>') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id=l.usuario_id WHERE l.rm_prof_mat_id=m.id AND l.acesso=:ace AND l.situacao=:sit) as professores FROM rm_professores_materias m INNER JOIN adms_sits_pgs s ON s.id=m.situacao WHERE m.ano =:ano" . \App\adms\Models\helper\AdmsEscopo::sql('m.curso_id'), "ace=1&sit=1&ano={$ano}");
        }else{
            $cursosCoord = \App\adms\Models\helper\AdmsEscopo::professor() ? \App\adms\Models\helper\AdmsEscopo::cursosCoordenados() : [];
            $coordenados = $cursosCoord ? ' OR m.curso_id IN (' . implode(',', array_map('intval', $cursosCoord)) . ')' : '';
            $listCurso->fullRead("SELECT m.id, (SELECT c.nome FROM rm_cursos c WHERE c.id=m.curso_id) as curso, 'Padrão' as grade, m.periodo, m.codmat, m.codtur, m.materia, m.encerrada_em, (SELECT COUNT(*) FROM portal_disciplinas_alunos pa WHERE pa.rm_prof_mat_id = m.id AND pa.situacao = 1) AS alunos, m.bimestre, m.situacao, s.nome nomeSituacao, (SELECT o.cor FROM adms_cors o WHERE o.id=s.cor_id) as corSituacao, (SELECT GROUP_CONCAT(u.id SEPARATOR ',') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id=l.usuario_id WHERE l.rm_prof_mat_id=m.id AND l.acesso=:ace AND l.situacao=:sit) as professoresId , (SELECT GROUP_CONCAT(u.nome SEPARATOR '<br>') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id=l.usuario_id WHERE l.rm_prof_mat_id=m.id AND l.acesso=:ace AND l.situacao=:sit) as professores FROM rm_professores_materias m INNER JOIN adms_sits_pgs s ON s.id=m.situacao WHERE m.ano =:ano AND (EXISTS (SELECT 1 FROM rm_professores_mat_liga p WHERE p.rm_prof_mat_id=m.id AND p.usuario_id=:user AND p.acesso =:ace AND p.situacao=:sitprof){$coordenados})", "ace=1&sit=1&ano={$ano}&user={$_SESSION['colUsuario_id']}&sitprof=1");
        }
        $this->resultado['disciplinas'] = $listCurso->getResultado() ?: [];
        /* Onda 3, Etapa 2: o professor que coordena um curso vê também as ofertas do curso, marcadas "só consulta" (a
           conferência é a do escopo: ele lê, não grava, nas que não são dele) */
        $professor = \App\adms\Models\helper\AdmsEscopo::professor();
        $minhas = $professor ? \App\adms\Models\helper\AdmsEscopo::ofertasLigadas() : [];
        foreach ($this->resultado['disciplinas'] as $i => $d) {
            $this->resultado['disciplinas'][$i]['so_consulta'] = $professor && !in_array((int) $d['id'], $minhas, true);
        }
    }

    public function listarCadastro()
    {
        $listAnoPeriodos = new \App\adms\Models\helper\AdmsRead();
        $listAnoPeriodos->fullRead("SELECT id, ano FROM adms_periodos_letivos WHERE situacao=:sit ORDER BY ano asc", "sit=1");
        $this->resultado['periodos'] = $listAnoPeriodos->getResultado();
        
        $listProfessores = new \App\adms\Models\helper\AdmsRead();
        $listProfessores->fullRead("SELECT id, nome FROM adms_usuarios WHERE adms_sits_usuario_id=:sit AND id>:adm ORDER BY nome asc", "adm=1&sit=1");
        $this->resultado['professores'] = $listProfessores->getResultado();

        return $this->resultado;
    }

}