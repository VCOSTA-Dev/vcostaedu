<?php

namespace App\adms\Models\atividades;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsListarAtividades
{
    private $dadosId;
    private $resultado;

    public function listAtividadesNota($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $this->informacao();
        $this->tiposAtividades();
        $this->qtdeAlunos();
        $this->atividades();
        $this->provas();
        $this->bonus();
        $this->recuperacao();
        return $this->resultado;
    }

    private function informacao()
    {
        $listCurso = new \App\adms\Models\helper\AdmsRead();
        $listCurso->fullRead("SELECT m.id, m.periodo, m.codmat, m.codtur, m.materia, m.bimestre, m.ano, m.codfilial, m.tipo, (SELECT GROUP_CONCAT(uo.nome SEPARATOR '<br>') FROM rm_cursos_coordenacao co INNER JOIN adms_usuarios uo ON uo.id=co.usuario_id WHERE co.curso_id=m.curso_id AND co.posicao_id=:ace AND co.situacao=:sit) as coordenacao, (SELECT GROUP_CONCAT(u.nome SEPARATOR '<br>') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id=l.usuario_id WHERE l.rm_prof_mat_id=m.id AND l.acesso=:ace AND l.situacao=:sit) as professores FROM rm_professores_materias m WHERE m.id =:id LIMIT :limit", "id={$this->dadosId}&ace=1&sit=1&limit=1");
        $this->resultado['info'] = $listCurso->getResultado();
    }

    private function atividades()
    {
        $listExercicios = new \App\adms\Models\helper\AdmsRead();
        $listExercicios->fullRead("SELECT a.id, t.nome nomeTipo, a.nomeAtividade, a.data_inicial, DATE_FORMAT(a.data_inicial, '%d/%m/%Y') as datasatividade, t.grupo FROM rm_disciplina_atividade a INNER JOIN rm_disciplina_atividade_tipo t ON t.id=a.tipo WHERE a.rm_prof_mat_id =:rm AND a.situacao =:sit AND t.grupo=:grupo  ORDER BY a.data_inicial asc", "rm={$this->dadosId}&sit=1&grupo=2");
        $this->resultado['exercicios'] = $listExercicios->getResultado();
    }

    private function bonus()
    {
        $listExercicios = new \App\adms\Models\helper\AdmsRead();
        $listExercicios->fullRead("SELECT a.id, t.nome nomeTipo, a.nomeAtividade, a.data_inicial, DATE_FORMAT(a.data_inicial, '%d/%m/%Y') as datasatividade, t.grupo FROM rm_disciplina_atividade a INNER JOIN rm_disciplina_atividade_tipo t ON t.id=a.tipo WHERE a.rm_prof_mat_id =:rm AND a.situacao =:sit AND t.grupo=:grupo  ORDER BY a.data_inicial asc", "rm={$this->dadosId}&sit=1&grupo=3");
        $this->resultado['bonus'] = $listExercicios->getResultado();
    }

    private function provas()
    {
        $listProvas = new \App\adms\Models\helper\AdmsRead();
        $listProvas->fullRead("SELECT a.id, t.nome nomeTipo, a.nomeAtividade, a.data_inicial, DATE_FORMAT(a.data_inicial, '%d/%m/%Y') as datasatividade FROM rm_disciplina_atividade a INNER JOIN rm_disciplina_atividade_tipo t ON t.id=a.tipo WHERE a.rm_prof_mat_id =:rm AND a.situacao =:sit AND t.grupo=:grupo  ORDER BY a.data_inicial asc", "rm={$this->dadosId}&sit=1&grupo=1");
        $this->resultado['avaliacoes'] = $listProvas->getResultado();
    }

    private function recuperacao()
    {
        $listRecupera = new \App\adms\Models\helper\AdmsRead();
        $listRecupera->fullRead("SELECT a.id, t.nome nomeTipo, a.nomeAtividade, a.data_inicial, DATE_FORMAT(a.data_inicial, '%d/%m/%Y') as datasatividade FROM rm_disciplina_atividade a INNER JOIN rm_disciplina_atividade_tipo t ON t.id=a.tipo WHERE a.rm_prof_mat_id =:rm AND a.situacao =:sit AND t.grupo=:grupo  ORDER BY a.data_inicial asc", "rm={$this->dadosId}&sit=1&grupo=4");
        $this->resultado['recuperacaoes'] = $listRecupera->getResultado();
    }

    private function tiposAtividades()
    {
        $verTipoDisciplinas = new \App\adms\Models\helper\AdmsRead();
        $verTipoDisciplinas->fullRead("SELECT id, nome FROM rm_disciplina_atividade_tipo WHERE grupo in (:gru, :bon) AND (situacao = :sit OR (id = 6 AND :acr = 1))",
            "gru=2&bon=3&sit=1&acr=" . (\App\adms\Models\notas\AdmsRegraMedia::permiteAcrescimo((int) $this->dadosId) ? 1 : 0)); // Onda 4, Etapa 5: o acréscimo, se a regra permite
        $this->resultado['tipos'] = $verTipoDisciplinas->getResultado();

        $verTipoDisciplinas = new \App\adms\Models\helper\AdmsRead();
        $verTipoDisciplinas->fullRead("SELECT id, nome FROM rm_disciplina_atividade_tipo WHERE grupo in (:gru, :bon) AND situacao = :sit", "gru=1&bon=4&sit=1");
        $this->resultado['provas'] = $verTipoDisciplinas->getResultado();
        
    }

    private function qtdeAlunos()
    {
        $verAlunos = new \App\adms\Models\helperApi\AdmsAlunosDisciplinas;
        $this->resultado['qtdeAluno'] = $verAlunos->disciplinaAlunos($this->dadosId);
    }

}