<?php

namespace App\adms\Models\atividades;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsVisualizarAtividades
{
    private $dadosId;
    private $dados;
    private $alunos;
    private $dadosAlunos;
    private $disciplina;
    private $resultado;

    public function visAtividadeNota($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $this->infoDisciplina();
        $this->verTabelaAtividade();
        $this->listAlunos();
        return $this->resultado;
    }

    private function infoDisciplina()
    {
        $infoMateria = new \App\adms\Models\helper\AdmsRead();
        $infoMateria->fullRead("SELECT a.id, a.nomeAtividade, a.tipo, a.peso, a.rm_prof_mat_id, m.materia, m.codtur, m.encerrada_em FROM rm_disciplina_atividade a INNER JOIN rm_professores_materias m ON m.id=a.rm_prof_mat_id WHERE a.id =:atividade LIMIT :limit", "atividade={$this->dadosId}&limit=1");
        $this->disciplina = $infoMateria->getResultado();
        $this->resultado['info'] = $this->disciplina;
    }

    private function listAlunos()
    {
        $listaFaltas = new \App\adms\Models\helper\AdmsRead();
        $listaFaltas->fullRead("SELECT a.id, a.ordenacao, a.rm, a.nome_aluno, (SELECT b.observacao FROM portal_disciplinas_alunos b WHERE b.rm=a.rm AND b.ordem=a.ordenacao AND b.rm_prof_mat_id=a.rm_prof_mat_id) as observacao, a.atividade_id, a.tipo, a.nota, a.alteracao FROM rm_disciplina_atividade_notas a WHERE a.atividade_id =:atividade AND a.situacao =:sit AND a.alteracao =:al ORDER BY a.ordenacao Asc", "atividade={$this->dadosId}&sit=1&al=1");
        $this->resultado['listAlunos'] = $listaFaltas->getResultado();
    }

    private function verTabelaAtividade()
    {
        $listarJornada = new \App\adms\Models\helper\AdmsRead();
        $listarJornada->fullRead("SELECT rm FROM rm_disciplina_atividade_notas WHERE atividade_id =:atividade ", "atividade={$this->dadosId}");
        if($listarJornada->getResultado()){
            $this->alunos = $listarJornada->getResultado();
            $this->criarLista();
            if(!empty($this->dadosAlunos)){
                $this->comparaListasAtividade();
            }
        }else{
            $this->criarLista();
            if(!empty($this->dadosAlunos)){
            $this->preparaAtividade();
            }
        }
    }

    private function criarLista()
    {

        $verAlunos = new \App\adms\Models\helperApi\AdmsAlunosDisciplinas;
        $this->dadosAlunos = $verAlunos->disciplinaAlunos($this->disciplina[0]['rm_prof_mat_id']);
    }

    private function preparaAtividade()
    {
        foreach($this->dadosAlunos as $disc){
            extract($disc);
            $this->dados['rm'] = $rm;
            $this->dados['nome_aluno'] = $nome_aluno;
            $this->dados['ordenacao'] = $ordem;
            $this->dados['rm_prof_mat_id'] = $this->disciplina[0]['rm_prof_mat_id'];
            $this->dados['atividade_id'] = $this->disciplina[0]['id'];
            $this->dados['peso'] = $this->disciplina[0]['peso'];
            $this->dados['alteracao'] = 1;
            $this->dados['tipo'] = $this->disciplina[0]['tipo'];
            $this->adicionaAlunosChamada();
        }
    }

    private function comparaListasAtividade()
    {   
        $rm = (array_column($this->dadosAlunos, 'rm'));
        $tabela = (array_column($this->alunos, 'rm'));
        $result = array_diff($rm, $tabela);
        foreach($this->dadosAlunos as $disc){
            extract($disc);
            foreach($result as $novos){
                if($novos == $rm){
                    $this->dados['rm'] = $rm;
                    $this->dados['nome_aluno'] = $nome_aluno;
                    $this->dados['ordenacao'] = $ordem;
                    $this->dados['rm_prof_mat_id'] = $this->disciplina[0]['rm_prof_mat_id'];
                    $this->dados['atividade_id'] = $this->disciplina[0]['id'];
                    $this->dados['peso'] = $this->disciplina[0]['peso'];
                    $this->dados['alteracao'] = 1;
                    $this->dados['tipo'] = $this->disciplina[0]['tipo'];
                    $this->adicionaAlunosChamada();
                }
            }
        }
    }

    private function adicionaAlunosChamada()
    {
        $this->dados['situacao'] = 1;
        $this->dados['usuario_cre'] = $_SESSION['colUsuario_id'];
        $this->dados['created'] = date("Y-m-d H:i:s");
        $cadFaltas = new \App\adms\Models\helper\AdmsCreate;
        $cadFaltas->exeCreate("rm_disciplina_atividade_notas", $this->dados);
    }

}