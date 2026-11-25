<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 5) . '/core/Tema.php';

/** O tema do aluno que entrou no portal (Onda 3, Etapa 10); null sem conta ou antes da atualização que cria a coluna. */
class AdmsTemaPortal
{
    public static function pdo(): \PDO
    {
        static $pdo = null;
        if ($pdo === null) {
            require_once dirname(__DIR__, 5) . '/core/Banco.php';
            $pdo = \VcBanco::daRequisicao(); // Onda 6, Etapa 1: a conexão da requisição
        }
        return $pdo;
    }

    public static function atual(): ?string
    {
        static $t = false;
        if ($t !== false) {
            return $t;
        }
        $id = (int) ($_SESSION['academico_id'] ?? 0);
        if ($id <= 0) {
            return $t = null;
        }
        try {
            $st = self::pdo()->prepare('SELECT tema FROM col_usuarios WHERE id = ?');
            $st->execute([$id]);
            return $t = \VcTema::valido((string) $st->fetchColumn()) ?? 'claro';
        } catch (\Throwable $e) {
            return $t = null;
        }
    }

    public static function script(): string
    {
        return \VcTema::script(self::atual());
    }
}
