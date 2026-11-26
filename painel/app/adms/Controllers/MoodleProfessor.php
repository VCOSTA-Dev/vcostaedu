<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\moodle\AdmsMoodleProfessor as Prof;

/**
 * O PROFESSOR ENTRA NO MOODLE (a correção do ensaio, B2.6; D-121; as páginas 599 e 600, a ação 111). Como o aluno no
 * portal: o servidor pede o link de uso único e redireciona na hora (303, sem cache, sem Referer); o link não é
 * mostrado e não vai para registro nenhum.
 *   entrar        a página inicial do professor no Moodle
 *   abrir/<id>    o curso de uma disciplina dele (enviada ao Moodle)
 */
class MoodleProfessor
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
        $usuario = (int) ($_SESSION['colUsuario_id'] ?? 0);
        [$link, $msg] = $ofertaId === -1 ? [null, 'Disciplina não encontrada.'] : Prof::link($usuario, $ofertaId);
        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');
        if ($link) {
            header('Location: ' . $link, true, 303);
            return;
        }
        $_SESSION['msg'] = "<div class='alert alert-warning alert-dismissible' id='moodleIndisponivel'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
            . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</div>';
        header('Location: ' . URLADM . 'home/index', true, 303);
    }
}
