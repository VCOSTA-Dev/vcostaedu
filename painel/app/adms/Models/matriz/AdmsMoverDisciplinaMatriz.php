<?php

namespace App\adms\Models\matriz;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\matriz\AdmsMatrizRegras as Regras;

/**
 * Move a disciplina para outro módulo da mesma grade e/ou altera a CH dela.
 * Mudar de módulo só se nenhuma oferta usar a disciplina
 * (AdmsMatrizRegras::ofertasDaLinha — regra preparada para a E3b).
 */
class AdmsMoverDisciplinaMatriz
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

    public function mover(int $linhaId, array $dados)
    {
        $linha = Regras::linha($linhaId);
        if (!$linha) {
            return $this->resultado = Regras::recusar('Disciplina da matriz não encontrada.');
        }
        $this->gradeId = (int) $linha['grade_id'];
        if ($motivo = Regras::recusaEdicao(Regras::grade($this->gradeId))) {
            return $this->resultado = Regras::recusar($motivo);
        }
        /* E8c (D-268): sem módulos, só a CH muda; o módulo (interno) fica o da linha */
        $destino = Regras::modulo(\App\adms\Models\helper\AdmsModuloInterno::ativo() ? (int) $linha['modulo_id'] : (int) ($dados['modulo_id'] ?? 0));
        if (!$destino || (int) $destino['grade_id'] !== $this->gradeId) {
            return $this->resultado = Regras::recusar('Escolha um módulo desta grade.');
        }
        $ch = Regras::ch($dados['cargahoraria'] ?? $linha['cargahoraria']);
        if ($ch === null) {
            return $this->resultado = Regras::recusar('A carga horária deve ser um número inteiro de 1 a 9999.');
        }
        $mudaModulo = (int) $destino['id'] !== (int) $linha['modulo_id'];
        if ($mudaModulo) {
            if (Regras::jaNoModulo($this->gradeId, (int) $destino['id'], (int) $linha['materia_id'], $linhaId)) {
                return $this->resultado = Regras::recusar("«{$linha['materia']}» já está no módulo «{$destino['codnome']}».");
            }
            $ofertas = Regras::ofertasDaLinha($linhaId);
            if ($ofertas > 0) {
                return $this->resultado = Regras::recusar("«{$linha['materia']}» não pode mudar de módulo: {$ofertas} oferta(s) a usam.");
            }
        }

        $alt = new \App\adms\Models\helper\AdmsUpdate();
        $alt->exeUpdate('uni_rh_cursos_disciplinas_grade', [
            'modulo_id' => (int) $destino['id'],
            'cargahoraria' => $ch,
            'usuario_mod' => $_SESSION['colUsuario_id'],
        ], 'WHERE id = :id', "id={$linhaId}");
        if ($alt->getResultado() === null) {
            return $this->resultado = Regras::recusar('Não foi possível alterar a disciplina na matriz.');
        }
        Regras::log(373, 3, "Disciplina da matriz alterada: linha={$linhaId}; modulo {$linha['modulo_id']} -> {$destino['id']}; ch={$ch}");
        $_SESSION['msg'] = Regras::aviso('success', $mudaModulo
            ? "«{$linha['materia']}» agora está no módulo «{$destino['codnome']}» ({$ch} h)."
            : "Carga horária de «{$linha['materia']}»: {$ch} h.");
        return $this->resultado = $linhaId;
    }
}
