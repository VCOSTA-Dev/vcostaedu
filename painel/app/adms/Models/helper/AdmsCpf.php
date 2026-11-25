<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * CPF: normalização (só dígitos) e validação dos dois dígitos verificadores.
 * O CPF é gravado só com dígitos; a máscara é coisa da tela.
 */
class AdmsCpf
{
    public static function digitos(?string $cpf): string
    {
        return preg_replace('/\D/', '', (string) $cpf);
    }

    public static function valido(?string $cpf): bool
    {
        $c = self::digitos($cpf);
        if (strlen($c) !== 11 || preg_match('/^(\d)\1{10}$/', $c)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $c[$i] * (($t + 1) - $i);
            }
            $dv = ((10 * $soma) % 11) % 10;
            if ((int) $c[$t] !== $dv) {
                return false;
            }
        }
        return true;
    }
}
