<?php

namespace App\adms\Models\portalAlunos;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsListaPortalAlunos
{
    private $dadosId;
    private $ensino;
    private $turma;
    private $resultado;

    public function listarAlunos($dadosId, $ensino, $turma)
    {
        $this->dadosId = (int) $dadosId;
        $this->ensino = (int) $ensino;
        $this->turma = (int) $turma;
        $this->resultado['pesAno'] = $this->dadosId;
        $this->resultado['pesEnsino'] = $this->ensino;
        $this->resultado['pesTurma'] = $this->turma;
        $this->alunosMatriculados();
        return $this->resultado;
    }

    private function alunosMatriculados()
    {
        /* Onda 6, Etapa 3 (DUVIDAS 138): uma linha por aluno, sem GROUP BY com colunas soltas (o modo do banco agora
           recusa): a matrícula MAIS RECENTE do ano (com os mesmos filtros) e, dela, a enturmação MAIS RECENTE. Antes, a
           linha mostrada era a que o banco devolvesse primeiro. "qtdMatriculas" > 1 avisa na tela que há mais de uma. */
        $filtro = fn(string $t): string => ((isset($this->ensino) && !empty($this->ensino) && $this->ensino > 0) ? " AND {$t}.ensino_id=" . (int) $this->ensino : '')
            . ((isset($this->turma) && !empty($this->turma) && $this->turma > 0) ? " AND {$t}.modulo_id=" . (int) $this->turma : '');
        $listCurso = new \App\adms\Models\helper\AdmsRead();
        $listCurso->fullRead("SELECT a.id, a.usuario_id, eo.ordem, b.rm, b.nome, a.ano, a.ensino_id, (SELECT c.nome FROM rm_cursos c WHERE c.id=a.ensino_id) as ensino, a.periodo, (SELECT d.codnome FROM rm_cursos_turma_series d WHERE d.id=a.modulo_id) as turma, (SELECT COUNT(e.id) FROM portal_disciplinas_alunos e WHERE e.rm=b.rm AND e.curso_id=a.ensino_id AND e.ano=a.ano) as qtdDisciplinas, eo.observacao, IFNULL(eo.grupo, 1) as grupo,"
            . " (SELECT COUNT(*) FROM rm_matriculas_ensino a3 WHERE a3.usuario_id=a.usuario_id AND a3.ano=a.ano AND a3.situacao=a.situacao" . $filtro('a3') . ") as qtdMatriculas"
            . " FROM rm_matriculas_ensino a INNER JOIN col_usuarios b ON b.id=a.usuario_id"
            . " LEFT JOIN portal_disciplinas_alunos eo ON eo.id = (SELECT MAX(e2.id) FROM portal_disciplinas_alunos e2 WHERE e2.rm=b.rm AND e2.curso_id=a.ensino_id AND e2.ano=a.ano)"
            . " WHERE a.ano=:ano AND a.situacao =:sit" . $filtro('a')
            . " AND a.id = (SELECT MAX(a2.id) FROM rm_matriculas_ensino a2 WHERE a2.usuario_id=a.usuario_id AND a2.ano=a.ano AND a2.situacao=a.situacao" . $filtro('a2') . ")", "ano={$this->dadosId}&sit=1");
        $this->resultado['portal'] = $listCurso->getResultado();

    }

    public function listarCadastro()
    {
        $listAnos = new \App\adms\Models\helper\AdmsRead();
        $listAnos->fullRead("SELECT id, ano FROM adms_periodos_letivos WHERE situacaodisciplina=:sit ORDER BY ano asc", "sit=1");
        $this->resultado['periodos'] = $listAnos->getResultado();

        $listCursos = new \App\adms\Models\helper\AdmsRead();
        /* 1.18.1 (D-479): o ";" antes do WHERE fazia o filtro de curso ativo não valer — curso inativo aparecia na escolha */
        $listCursos->fullRead("SELECT a.id, a.nome, b.nome as periodo FROM rm_cursos a INNER JOIN rm_cursos_periodos b ON b.id=a.periodo WHERE a.situacao=:sit", "sit=1");
        $this->resultado['cursos'] = $listCursos->getResultado();

        $listTurmas = new \App\adms\Models\helper\AdmsRead();
        $listTurmas->fullRead("SELECT m.id, IF((SELECT COUNT(*) FROM rm_cursos_grades g2 WHERE g2.curso_id = g.curso_id) > 1, CONCAT(m.codnome, ' (grade ', g.versao, ')'), m.codnome) AS codnome, m.curso_id FROM rm_cursos_turma_series m INNER JOIN rm_cursos_grades g ON g.id = m.grade_id WHERE m.situacao=:sit ORDER BY m.curso_id, g.versao, m.ordem", "sit=1");
        $this->resultado['turmas'] = $listTurmas->getResultado();

        return $this->resultado;
    }

}