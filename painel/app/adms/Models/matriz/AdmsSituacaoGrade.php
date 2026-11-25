<?php

namespace App\adms\Models\matriz;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\matriz\AdmsMatrizRegras as Regras;

/**
 * Troca a situação da grade (Aberta, Em análise, Finalizada, Desativada), com
 * observação obrigatória gravada no histórico. E3b: a secretaria só leva Aberta →
 * Em análise; finalizar, desativar e reabrir são do administrador. Do sistema de origem de ensino superior
 * (AdmsFinalizacaoSitModelo), sem o envio de e-mail.
 */
class AdmsSituacaoGrade
{
    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    public function alterar(int $id, array $dados)
    {
        $grade = Regras::grade($id);
        if (!$grade) {
            return $this->resultado = Regras::recusar('Grade não encontrada.');
        }
        $nova = (int) ($dados['situacao'] ?? 0);
        $obs = trim((string) ($dados['observacao'] ?? ''));
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT nome FROM uni_rh_cursos_grades_situacao WHERE id = :id LIMIT :limit", "id={$nova}&limit=1");
        $nome = $ler->getResultado()[0]['nome'] ?? null;
        if ($nome === null || $nova === (int) $grade['situacao']) {
            return $this->resultado = Regras::recusar('Escolha uma situação diferente da atual.');
        }
        if (!Regras::podeTrocarSituacao((int) $grade['situacao'], $nova)) {
            return $this->resultado = Regras::recusar("A secretaria só pode mandar uma grade Aberta para «Em análise». Finalizar, desativar e reabrir são do administrador.");
        }
        // E8c (D-266): a JUSTIFICATIVA é obrigatória para reabrir uma grade Finalizada ou Desativada; nas outras trocas é opcional
        if (Regras::exigeJustificativa((int) $grade['situacao'], $nova) && mb_strlen($obs) < 5) {
            return $this->resultado = Regras::recusar('Para reabrir uma grade «' . $grade['nomeSituacao'] . '», escreva a justificativa (ao menos 5 caracteres). Ela fica no registro de alterações da grade.');
        }
        $alt = new \App\adms\Models\helper\AdmsUpdate();
        $alt->exeUpdate('rm_cursos_grades', ['situacao' => $nova, 'usuario_mod' => $_SESSION['colUsuario_id']], 'WHERE id = :id', "id={$id}");
        if (!$alt->getResultado()) {
            return $this->resultado = Regras::recusar('Não foi possível alterar a situação da grade.');
        }
        Regras::historico($id, $nova, $obs);
        Regras::log(371, 3, "Situação da grade id={$id}: {$grade['situacao']} -> {$nova}");
        $_SESSION['msg'] = Regras::aviso('success', "Grade {$grade['versao']} agora está «{$nome}».");
        return $this->resultado = $id;
    }
}
