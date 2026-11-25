<?php

namespace App\adms\Models\horarioAula;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsVerHorarioAula
{
    private $dadosId;
    private $horario;
    private $diasAula;
    private $resultado;

    public function verHorarios($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $this->buscaHorario();
        $this->buscaDiasdeAula();
        $this->montaHorario();
        return $this->resultado;
    }

    private function buscaHorario()
    {
        $iconeDiurno = '<i class="fas fa-cloud-sun"></i> Diurno';
        $iconeNoturno = '<i class="fas fa-cloud-moon"></i> Noturno';
        $verPeriodoHorario = new \App\adms\Models\helper\AdmsRead();
        $verPeriodoHorario->fullRead("SELECT a.id, b.periodo as espaco, a.materia, a.codtur, IF(b.periodo = 1, '{$iconeDiurno}', '{$iconeNoturno}')  as detalhePeriodo, (SELECT GROUP_CONCAT(c.horario_id,'-',c.dia_semana SEPARATOR ' | ') FROM rm_professores_horarios c WHERE c.rm_prof_mat_id=a.id AND c.situacao=:sit) as aula FROM rm_professores_materias a INNER JOIN rm_cursos b ON b.id=a.curso_id WHERE a.id =:disc ", "disc={$this->dadosId}&sit=1");
        $horarioPeriodo = $verPeriodoHorario->getResultado();
        $this->horario = $horarioPeriodo;
    }

    private function buscaDiasdeAula()
    {
        $listarSemanas = new \App\adms\Models\helper\AdmsRead();
        $listarSemanas->fullRead("SELECT a.id, a.nome, a.periodo, a.inicio, a.termino FROM rm_professores_horario_aula a WHERE a.periodo =:per AND a.situacao = :sit", "per=" . urlencode((string) $this->horario[0]['espaco']) . "&sit=1");
        $this->diasAula = $listarSemanas->getResultado();
    }

    private function montaHorario()
    {
        $cronograma = "<h3>{$this->horario[0]['materia']} - {$this->horario[0]['codtur']}</h3><br><table class='table table-bordered table-hover'><h2>{$this->horario[0]['detalhePeriodo']}</h2><thead class='table-dark'><tr><th id='horario'>Horário</th><th class='text-center'>Seg</th><th class='text-center'>Ter</th><th class='text-center'>Qua</th><th class='text-center'>Qui</th><th class='text-center'>Sex</th><th class='text-center'>Sáb</th></tr></thead><tbody>";
        $arrayDias = explode(' | ', $this->horario[0]['aula']);
        foreach ($this->diasAula as $tem) {
            $cronograma .= "<tr><td rowspan='1' id='horario'> Das {$tem['inicio']} até às {$tem['termino']} </td>";
            $cronograma .= "<td><input class='form-control' type='checkbox' id='ipt2{$tem['id']}' name='ipt2{$tem['id']}' value='2' ".(array_search($tem['id'] . '-2', $arrayDias) !== false ? 'checked' : '')." onclick='btnHorarioAula({$this->horario[0]['id']}, 2, {$tem['id']})'></td>";
            $cronograma .= "<td><input class='form-control' type='checkbox' id='ipt3{$tem['id']}' name='ipt3{$tem['id']}' value='3' ".(array_search($tem['id'] . '-3', $arrayDias) !== false ? 'checked' : '')." onclick='btnHorarioAula({$this->horario[0]['id']}, 3, {$tem['id']})'></td>";
            $cronograma .= "<td><input class='form-control' type='checkbox' id='ipt4{$tem['id']}' name='ipt4{$tem['id']}' value='4' ".(array_search($tem['id'] . '-4', $arrayDias) !== false ? 'checked' : '')." onclick='btnHorarioAula({$this->horario[0]['id']}, 4, {$tem['id']})'></td>";
            $cronograma .= "<td><input class='form-control' type='checkbox' id='ipt5{$tem['id']}' name='ipt5{$tem['id']}' value='5' ".(array_search($tem['id'] . '-5', $arrayDias) !== false ? 'checked' : '')." onclick='btnHorarioAula({$this->horario[0]['id']}, 5, {$tem['id']})'></td>";
            $cronograma .= "<td><input class='form-control' type='checkbox' id='ipt6{$tem['id']}' name='ipt6{$tem['id']}' value='6' ".(array_search($tem['id'] . '-6', $arrayDias) !== false ? 'checked' : '')." onclick='btnHorarioAula({$this->horario[0]['id']}, 6, {$tem['id']})'></td>";
            $cronograma .= "<td><input class='form-control' type='checkbox' id='ipt7{$tem['id']}' name='ipt7{$tem['id']}' value='7' ".(array_search($tem['id'] . '-7', $arrayDias) !== false ? 'checked' : '')." onclick='btnHorarioAula({$this->horario[0]['id']}, 7, {$tem['id']})'></td>";
            $cronograma .= "</tr>";
        }
        $cronograma .= "</tbody></table>";

        $this->resultado['status'] = true; 
        $this->resultado['msg'] = ''; 
        $this->resultado['dados'] = $cronograma; 
    }
    // private function disciplinas()
    // {   
    //     $verInfo = new \App\adms\Models\helper\AdmsRead();
    //     $verInfo->fullRead("SELECT id, codmat, codtur, materia, ano, codfilial FROM rm_professores_materias WHERE id =:rm LIMIT :limit", "rm={$this->dadosId}&limit=1");
    //     $this->resultado['info'] = $verInfo->getResultado();
    // }

    // private function horariosAula()
    // {
    //     $verHorarios = new \App\adms\Models\helper\AdmsRead();
    //     $verHorarios->fullRead("SELECT h.id, h.nome, h.periodo, h.inicio, h.termino, (SELECT d.id FROM rm_professores_horarios d WHERE d.rm_prof_mat_id=:rmprof AND d.horario_id=h.id AND d.dia_semana=2) as diaSegunda, (SELECT d.id FROM rm_professores_horarios d WHERE d.rm_prof_mat_id=:rmprof AND d.horario_id=h.id AND d.dia_semana=3) as diaTerca, (SELECT d.id FROM rm_professores_horarios d WHERE d.rm_prof_mat_id=:rmprof AND d.horario_id=h.id AND d.dia_semana=4) as diaQuarta, (SELECT d.id FROM rm_professores_horarios d WHERE d.rm_prof_mat_id=:rmprof AND d.horario_id=h.id AND d.dia_semana=5) as diaQuinta,(SELECT d.id FROM rm_professores_horarios d WHERE d.rm_prof_mat_id=:rmprof AND d.horario_id=h.id AND d.dia_semana=6) as diaSexta, (SELECT d.id FROM rm_professores_horarios d WHERE d.rm_prof_mat_id=:rmprof AND d.horario_id=h.id AND d.dia_semana=7) as diaSabado FROM rm_professores_horario_aula h WHERE h.periodo =:per AND h.situacao =:sit", "rmprof={$this->dadosId}&per=1&sit=1");
    //     $this->resultado['horariosDiurno'] = $verHorarios->getResultado();
    // }

}