<?php

namespace App\adms\Models\matriz;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\matriz\AdmsMatrizRegras as Regras;

/**
 * ORDEM DAS DISCIPLINAS NA GRADE (E1b, D-353). A tela manda os ids das linhas da matriz na ordem em que ficaram
 * depois de arrastar (`ordem` = "12,5,7,…"). Só vale a lista EXATA das linhas da grade (nem a mais, nem a menos,
 * sem repetir) e só numa grade Aberta, como qualquer mudança na matriz. As linhas são regravadas 1, 2, 3… numa
 * transação só (\Core\Transacao): ou a ordem nova inteira, ou nada. É a ordem que o histórico usa.
 */
class AdmsOrdenarDisciplinasMatriz
{
    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    public function ordenar(int $gradeId, array $dados)
    {
        $grade = Regras::grade($gradeId);
        if ($motivo = Regras::recusaEdicao($grade)) {
            return $this->resultado = Regras::recusar($motivo);
        }
        $ids = array_map('intval', array_filter(explode(',', (string) ($dados['ordem'] ?? '')), fn($x) => trim($x) !== ''));
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id FROM uni_rh_cursos_disciplinas_grade WHERE grade_id = :g", "g={$gradeId}");
        $daGrade = array_map('intval', array_column($ler->getResultado() ?: [], 'id'));
        $a = $ids;
        $b = $daGrade;
        sort($a);
        sort($b);
        if (!$ids || count($ids) !== count(array_unique($ids)) || $a !== $b) {
            return $this->resultado = Regras::recusar('A ordem enviada não confere com as disciplinas desta grade (a página pode estar desatualizada). Recarregue e arraste de novo. Nada foi salvo.');
        }
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $ids, $gradeId) {
            foreach ($ids as $i => $id) {
                $up = new \App\adms\Models\helper\AdmsUpdate();
                $up->exeUpdate('uni_rh_cursos_disciplinas_grade', ['ordem' => $i + 1, 'usuario_mod' => $_SESSION['colUsuario_id']], 'WHERE id = :id AND grade_id = :g', "id={$id}&g={$gradeId}");
                if ($up->getResultado() === null) {
                    $T::falhar('ordem da disciplina');
                }
            }
            return true;
        });
        if (!$ok) {
            return $this->resultado = Regras::recusar('Não foi possível gravar a ordem (' . \Core\Transacao::ultimoErro() . '). Nada foi salvo.');
        }
        Regras::log(415, 1, "Ordem das disciplinas da grade {$gradeId}: " . implode(',', $ids));
        $_SESSION['msg'] = Regras::aviso('success', 'Ordem das disciplinas salva (' . count($ids) . ' disciplinas). É a ordem do histórico.');
        return $this->resultado = true;
    }
}
