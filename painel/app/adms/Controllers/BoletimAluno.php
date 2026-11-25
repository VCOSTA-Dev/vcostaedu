<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Pagina nao encontrada!");
}

/**
 * E4: `boletim-aluno/listar?rm=` (rota 273) leva ao boletim POR CURSO do painel
 * (academicos/boletim/{RA}). Antes esta rota embrulhava a tela do portal, com os 4
 * bimestres e o controle de exibição por bimestre.
 */
class BoletimAluno
{
    public function listar()
    {
        $rm = preg_replace('/\D/', '', (string) (filter_input(INPUT_GET, 'rm') ?? ''));
        header('Location: ' . URLADM . ($rm !== '' ? 'academicos/boletim/' . $rm : 'academicos/listar'));
    }
}
