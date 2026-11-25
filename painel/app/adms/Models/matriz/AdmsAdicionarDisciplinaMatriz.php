<?php

namespace App\adms\Models\matriz;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\matriz\AdmsMatrizRegras as Regras;

/**
 * Inclui uma disciplina do catálogo (rm_materias, D-03) num módulo da grade,
 * com a CH da linha (padrão: a do catálogo). Do sistema de origem de ensino superior (AdmsAdicionarDiscModelo).
 *
 * A mesma matéria não se repete no mesmo módulo da mesma grade: a tela recusa,
 * e o índice único `grade_modulo_materia` segura o que escapar.
 */
class AdmsAdicionarDisciplinaMatriz
{
    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    public function adicionar(int $gradeId, array $dados)
    {
        $grade = Regras::grade($gradeId);
        if ($motivo = Regras::recusaEdicao($grade)) {
            return $this->resultado = Regras::recusar($motivo);
        }
        $semModulo = \App\adms\Models\helper\AdmsModuloInterno::ativo();
        /* E8c (D-268): sem módulos, a disciplina entra no módulo INTERNO da grade (criado se preciso) */
        $modulo = Regras::modulo($semModulo ? \App\adms\Models\helper\AdmsModuloInterno::garantir($gradeId) : (int) ($dados['modulo_id'] ?? 0));
        if (!$modulo || (int) $modulo['grade_id'] !== $gradeId) {
            return $this->resultado = Regras::recusar('Escolha um módulo desta grade.');
        }
        $materia = Regras::materia((int) ($dados['materia_id'] ?? 0));
        if (!$materia) {
            return $this->resultado = Regras::recusar('Escolha a disciplina no catálogo.');
        }
        $ch = Regras::ch($dados['cargahoraria'] ?? '', $materia);
        if ($ch === null) {
            return $this->resultado = Regras::recusar('A carga horária deve ser um número inteiro de 1 a 9999.');
        }
        if ($semModulo && Regras::jaNaGrade($gradeId, (int) $materia['id'], 0)) {
            return $this->resultado = Regras::recusar("«{$materia['nome']}» já está nesta grade.");
        }
        if (Regras::jaNoModulo($gradeId, (int) $modulo['id'], (int) $materia['id'], 0)) {
            return $this->resultado = Regras::recusar("«{$materia['nome']}» já está no módulo «{$modulo['codnome']}» desta grade.");
        }

        /* E1b (D-353): a disciplina nova entra no FIM da ordem da grade */
        $lerOrdem = new \App\adms\Models\helper\AdmsRead();
        $lerOrdem->fullRead("SELECT IFNULL(MAX(ordem), 0) AS mx FROM uni_rh_cursos_disciplinas_grade WHERE grade_id = :g", "g={$gradeId}");
        $ordem = (int) ($lerOrdem->getResultado()[0]['mx'] ?? 0) + 1;
        $add = new \App\adms\Models\helper\AdmsCreate();
        $add->exeCreate('uni_rh_cursos_disciplinas_grade', [
            'grade_id' => $gradeId,
            'modulo_id' => (int) $modulo['id'],
            'materia_id' => (int) $materia['id'],
            'cargahoraria' => $ch,
            'ordem' => $ordem,
            'usuario_cre' => $_SESSION['colUsuario_id'],
        ]);
        $id = (int) $add->getResultado();
        if ($id < 1) {
            return $this->resultado = Regras::recusar('Não foi possível incluir a disciplina (a mesma disciplina não se repete no módulo).');
        }
        Regras::log(372, 1, "Disciplina na matriz: linha={$id}; grade_id={$gradeId}; modulo_id={$modulo['id']}; materia_id={$materia['id']}; ch={$ch}");
        $_SESSION['msg'] = Regras::aviso('success', $semModulo ? "«{$materia['nome']}» ({$ch} h) incluída na grade." : "«{$materia['nome']}» ({$ch} h) incluída no módulo «{$modulo['codnome']}».");
        return $this->resultado = $id;
    }
}
