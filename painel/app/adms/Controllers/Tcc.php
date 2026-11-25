<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/** TCC (E6a, P-5): salva o TCC de uma matrícula no curso (rota 389, POST; administrador e secretaria). */
class Tcc
{
    public function salvar($dadosId = null): void
    {
        $mcId = (int) $dadosId;
        $mc = \App\adms\Models\vinculo\AdmsVinculoRegras::matriculaCurso($mcId);
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $mc) {
            (new \App\adms\Models\tcc\AdmsTcc())->salvar($mcId, filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: []);
        }
        header('Location: ' . URLADM . ($mc ? 'academicos/visualizar/' . (int) $mc['usuario_id'] . '?tab=tabeMatricula' : 'academicos/listar'));
    }
}
