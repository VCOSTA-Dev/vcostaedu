<?php

namespace App\adms\Models\matriz;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\matriz\AdmsMatrizRegras as Regras;

/**
 * Edita número e descrição de uma grade ABERTA. O número não repete no curso, e
 * não muda se alguma oferta (rm_professores_materias.grade) já o usa.
 */
class AdmsEditarGrade
{
    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    public function editar(int $id, array $dados)
    {
        $grade = Regras::grade($id);
        if ($motivo = Regras::recusaEdicao($grade)) {
            return $this->resultado = Regras::recusar($motivo);
        }
        $numero = Regras::numeroGrade($dados['versao'] ?? '');
        if ($numero === null) {
            return $this->resultado = Regras::recusar('Informe o número da grade (inteiro de 1 a 9999).');
        }
        if (Regras::numeroGradeOcupado((int) $grade['curso_id'], $numero, $id)) {
            return $this->resultado = Regras::recusar("A grade {$numero} já existe no curso {$grade['curso']}.");
        }
        if ($numero !== (int) $grade['versao']) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead(
                "SELECT COUNT(*) AS n FROM rm_professores_materias p INNER JOIN uni_rh_cursos_disciplinas_grade l ON l.id = p.matriz_id WHERE l.grade_id = :g",
                "g={$id}"
            );
            $ofertas = (int) ($ler->getResultado()[0]['n'] ?? 0);
            if ($ofertas > 0) {
                return $this->resultado = Regras::recusar("O número da grade {$grade['versao']} não pode mudar: {$ofertas} oferta(s) a acham por ele.");
            }
        }
        $alt = new \App\adms\Models\helper\AdmsUpdate();
        $alt->exeUpdate('rm_cursos_grades', [
            'versao' => $numero,
            'descricao' => trim((string) ($dados['descricao'] ?? '')),
            'usuario_mod' => $_SESSION['colUsuario_id'],
        ], 'WHERE id = :id', "id={$id}");
        if ($alt->getResultado() === null) {
            return $this->resultado = Regras::recusar('Não foi possível salvar a grade.');
        }
        Regras::log(370, 3, "Grade atualizada: id={$id}; numero={$numero}");
        $_SESSION['msg'] = Regras::aviso('success', "Grade {$numero} atualizada.");
        return $this->resultado = $id;
    }
}
