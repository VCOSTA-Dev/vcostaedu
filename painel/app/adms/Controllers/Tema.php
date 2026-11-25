<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/** O TEMA (Onda 3, Etapa 10): grava a escolha (claro, escuro, auto) de quem entrou. POST, JSON. */
class Tema
{
    public function salvar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        require_once dirname(__DIR__, 4) . '/core/Tema.php';
        $t = \VcTema::valido((string) ($_POST['tema'] ?? ''));
        $id = (int) ($_SESSION['colUsuario_id'] ?? 0);
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !$t || $id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false]);
            return;
        }
        $pdo = (new \App\adms\Models\helper\AdmsConexaoAvulsa())->pdo();
        $pdo->prepare('UPDATE adms_usuarios SET tema = ? WHERE id = ?')->execute([$t, $id]);
        echo json_encode(['ok' => true, 'tema' => $t]);
    }
}
