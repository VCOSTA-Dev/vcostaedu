<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\moodle\AdmsMoodlePortal as Portal;

/**
 * ENTRAR NO MOODLE (Onda 3, Etapa 8; col_paginas 26 e 27, só o Estudante). O servidor pede o link de uso único e
 * redireciona na hora; o link não é mostrado, não fica em endereço guardado (a resposta não vai para o histórico: é um
 * redirecionamento 303 sem cache) e não vai para registro nenhum.
 */
class Moodle
{
    public function entrar(): void
    {
        $this->ir(null);
    }

    public function abrir($ofertaId = null): void
    {
        $this->ir((int) $ofertaId > 0 ? (int) $ofertaId : -1);
    }

    private function ir(?int $ofertaId): void
    {
        $aluno = (int) ($_SESSION['academico_id'] ?? 0);
        [$link, $msg] = $ofertaId === -1 ? [null, 'Disciplina não encontrada.'] : Portal::link($aluno, $ofertaId);
        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');
        if ($link) {
            header('Location: ' . $link, true, 303);
            return;
        }
        $_SESSION['msg'] = "<div class='alert alert-warning alert-dismissible' id='moodleIndisponivel'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
            . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</div>';
        header('Location: ' . URLACAD . 'home/index', true, 303);
    }
}
