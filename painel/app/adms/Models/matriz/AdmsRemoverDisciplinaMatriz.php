<?php

namespace App\adms\Models\matriz;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\matriz\AdmsMatrizRegras as Regras;

/**
 * Tira a disciplina da matriz — só se nenhuma oferta a usar
 * (AdmsMatrizRegras::ofertasDaLinha — regra preparada para a E3b).
 */
class AdmsRemoverDisciplinaMatriz
{
    private $resultado;
    private int $gradeId = 0;

    public function getResultado()
    {
        return $this->resultado;
    }

    public function getGradeId(): int
    {
        return $this->gradeId;
    }

    public function remover(int $linhaId)
    {
        $linha = Regras::linha($linhaId);
        if (!$linha) {
            return $this->resultado = Regras::recusar('Disciplina da matriz não encontrada.');
        }
        $this->gradeId = (int) $linha['grade_id'];
        if ($motivo = Regras::recusaEdicao(Regras::grade($this->gradeId))) {
            return $this->resultado = Regras::recusar($motivo);
        }
        $ofertas = Regras::ofertasDaLinha($linhaId);
        if ($ofertas > 0) {
            return $this->resultado = Regras::recusar("«{$linha['materia']}» não pode sair da matriz: {$ofertas} oferta(s) a usam.");
        }
        $del = new \App\adms\Models\helper\AdmsDeletar();
        $del->exeDelete('uni_rh_cursos_disciplinas_grade', 'WHERE id = :id', "id={$linhaId}");
        if (!$del->getResultado()) {
            return $this->resultado = Regras::recusar('Não foi possível remover a disciplina da matriz.');
        }
        Regras::log(374, 4, "Disciplina removida da matriz: linha={$linhaId}; grade_id={$this->gradeId}; materia_id={$linha['materia_id']}");
        $_SESSION['msg'] = Regras::aviso('success', "«{$linha['materia']}» saiu da matriz.");
        return $this->resultado = true;
    }
}
