<?php

namespace App\adms\Models\horarioAula;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsEditarHorarioAula
{
    private $dadosId;
    private $dados;
    private $dia;
    private $horario;
    private $situacao;
    private $logDescricao;
    private $logCodigo;
    private $logAcao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function mudaHorario($dadosId, $dia, $horario, $situacao)
    {
        $this->dadosId = (int) $dadosId;
        $this->dia = (int) $dia;
        $this->horario = (int) $horario;
        $this->situacao = (int) $situacao;
        if($this->situacao == 1){
            $this->addHorario();
        }elseif($this->situacao == 2){
            $this->delHorario();
        }else{
            $this->verDiaSemana();
            $this->resultado['status'] = false;
            $this->resultado['msg'] = "<div class='alert alert-danger'>ERRO: Falha na comunicação com o sistema!</div>";
        }
    }

    private function buscaPeriodo()
    {
        $verPeriodoHorario = new \App\adms\Models\helper\AdmsRead();
        $verPeriodoHorario->fullRead("SELECT periodo FROM rm_professores_horario_aula WHERE id =:hor LIMIT :limit", "hor=" . urlencode((string) $this->horario) . "&limit=1");
        $horarioPeriodo = $verPeriodoHorario->getResultado();
        $this->dados['periodo'] = $horarioPeriodo[0]['periodo'];
    }

    private function addHorario()
    {
        $this->buscaPeriodo();
        $this->dados['rm_prof_mat_id'] = $this->dadosId;
        $this->dados['dia_semana'] = $this->dia;
        $this->dados['horario_id'] = $this->horario;
        $this->dados['usuario_cre'] = $_SESSION['colUsuario_id'];
        $this->dados['situacao'] = 1;
        $addProf = new \App\adms\Models\helper\AdmsCreate();
        $addProf->exeCreate("rm_professores_horarios", $this->dados);
        if($addProf->getResultado()){
            $this->logCodigo = 200;
            $this->logAcao = 2;
            $this->logDescricao = "Horário de aula cadastrado com sucesso!--".json_encode($this->dados, true);
            $this->salvarLog();
            $this->verDiaSemana();
            $this->resultado['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Horário de aula adicionado com sucesso!</div>";
            $this->resultado['status'] = true;
        }else{
            $this->logCodigo = 400;
            $this->logAcao = 2;
            $this->logDescricao = "Erro: Não foi possível adicionar o horário da aula!--".json_encode($this->dados, true);
            $this->salvarLog();
            $this->verDiaSemana();
            $this->resultado['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível adicionar o horário de aula!</div>";
            $this->resultado['status'] = false;
        }
    }

    private function delHorario()
    {
        $this->dados['rm_prof_mat_id'] = $this->dadosId;
        $this->dados['dia_semana'] = $this->dia;
        $this->dados['horario_id'] = $this->horario;
        $delHorario = new \App\adms\Models\helper\AdmsDeletar();
        $delHorario->exeDelete("rm_professores_horarios", "WHERE rm_prof_mat_id=:rmprof AND horario_id=:horario AND dia_semana=:dia ", "rmprof={$this->dados['rm_prof_mat_id']}&horario={$this->dados['horario_id']}&dia=" . urlencode((string) $this->dados['dia_semana']) . "");
        if($delHorario->getResultado()){
            $this->logCodigo = 200;
            $this->logAcao = 4;
            $this->logDescricao = "Horário de aula apagado com sucesso!--".json_encode($this->dados, true);
            $this->salvarLog();
            $this->verDiaSemana();
            $this->resultado['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Horário de aula retirado com sucesso!</div>";
            $this->resultado['status'] = true;
        }else{
            $this->logCodigo = 400;
            $this->logAcao = 4;
            $this->logDescricao = "Error: Não foi possível apagar o horário de aula!--".json_encode($this->dados, true);
            $this->salvarLog();
            $this->verDiaSemana();
            $this->resultado['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>ERRO: Não foi possível retirar o horário de aula!</div>";
            $this->resultado['status'] = false;
        }
    }

    private function verDiaSemana()
    {
        $verDiasSe = new \App\adms\Models\helper\AdmsRead();
        $verDiasSe->fullRead("SELECT GROUP_CONCAT(DISTINCT(b.dia) SEPARATOR '|') as diaSemana FROM rm_professores_horarios a INNER JOIN adms_dia_semana b ON b.id=a.dia_semana WHERE a.rm_prof_mat_id =:rmprof AND a.situacao = :sit", "rmprof={$this->dados['rm_prof_mat_id']}&sit=1");
        $horaDiaAtual = $verDiasSe->getResultado();
        if(isset($horaDiaAtual[0]['diaSemana']) && !empty($horaDiaAtual[0]['diaSemana'])){
            $this->resultado['semana'] = $horaDiaAtual[0]['diaSemana'];
        }else{
            $this->resultado['semana'] = "";
        }

    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(118, $this->logCodigo, $this->logAcao, $this->logDescricao);
    }

}