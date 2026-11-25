<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 5) . '/core/Tema.php';

/** O tema de quem entrou no painel (Onda 3, Etapa 10); null sem conta ou antes da atualização que cria a coluna. */
class AdmsTema
{
    public static function atual(): ?string
    {
        static $t = false;
        if ($t !== false) {
            return $t;
        }
        $id = (int) ($_SESSION['colUsuario_id'] ?? 0);
        if ($id <= 0) {
            return $t = null;
        }
        try {
            $st = (new AdmsConexaoAvulsa())->pdo()->prepare('SELECT tema FROM adms_usuarios WHERE id = ?');
            $st->execute([$id]);
            return $t = \VcTema::valido((string) $st->fetchColumn()) ?? 'claro';
        } catch (\Throwable $e) {
            return $t = null; // a coluna ainda não existe (atualização pendente): o cookie, ou claro
        }
    }

    /** O <script> do <head>. */
    public static function script(): string
    {
        return \VcTema::script(self::atual());
    }
}
