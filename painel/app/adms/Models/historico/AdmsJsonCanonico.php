<?php

namespace App\adms\Models\historico;

if (!defined('VCOSTAEDU')) { // E6 (D-376): o portal também carrega (atestado do aluno)
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * JSON CANÔNICO (E6b): chaves de objeto em ordem, listas na ordem original, UTF-8 e barras
 * sem escape. Duas codificações do MESMO conteúdo dão a mesma string — e o mesmo hash.
 */
class AdmsJsonCanonico
{
    public static function codificar($dados): string
    {
        return json_encode(self::ordenar($dados), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private static function ordenar($v)
    {
        if (!is_array($v)) {
            return $v;
        }
        $lista = array_is_list($v);
        $v = array_map([self::class, 'ordenar'], $v);
        if (!$lista) {
            ksort($v, SORT_STRING);
        }
        return $v;
    }
}
