<?php

namespace App\adms\Models\modulos;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\matriz\AdmsMatrizRegras as Regras;

/** Apaga só o módulo vazio de grade aberta: sem disciplina na matriz, sem matrícula e sem oferta. */
class AdmsApagarModulo
{
    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    public function apagar(int $id)
    {
        $modulo = Regras::modulo($id);
        if (!$modulo) {
            return $this->resultado = Regras::recusar('Módulo não encontrado.');
        }
        if ($motivo = Regras::recusaEdicao(Regras::grade((int) $modulo['grade_id']))) {
            return $this->resultado = Regras::recusar($motivo);
        }
        $usos = Regras::usosDoModulo($modulo);
        if ($usos['disciplinas'] + $usos['matriculas'] + $usos['ofertas'] > 0) {
            return $this->resultado = Regras::recusar("O módulo «{$modulo['codnome']}» não pode ser apagado: tem "
                . "{$usos['disciplinas']} disciplina(s) na matriz, {$usos['matriculas']} matrícula(s) e "
                . "{$usos['ofertas']} oferta(s). Tire as disciplinas antes; se ele tem matrícula ou oferta, desative-o.");
        }
        $del = new \App\adms\Models\helper\AdmsDeletar();
        $del->exeDelete('rm_cursos_turma_series', 'WHERE id = :id', "id={$id}");
        if (!$del->getResultado()) {
            return $this->resultado = Regras::recusar('Não foi possível apagar o módulo.');
        }
        Regras::log(335, 4, "Módulo apagado: id={$id}; grade_id={$modulo['grade_id']}");
        $_SESSION['msg'] = Regras::aviso('success', "Módulo «{$modulo['codnome']}» apagado.");
        return $this->resultado = true;
    }
}
