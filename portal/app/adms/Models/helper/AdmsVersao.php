<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Versão do sistema para o rodapé do portal (E3a). Lê a mesma tabela do painel
 * (`adms_versoes`); antes o portal mostrava a constante VERSAODEV da Config.
 * Falhando a leitura, mostra o valor de recuo — o rodapé nunca derruba a página.
 */
class AdmsVersao
{
    private const RECUO = '1.0.0';

    public static function numero(): string
    {
        try {
            $ler = new AdmsRead();
            $ler->fullRead(
                'SELECT numero FROM adms_versoes ORDER BY major DESC, minor DESC, patch DESC, id DESC LIMIT :limit',
                'limit=1'
            );
            return $ler->getResultado()[0]['numero'] ?? self::RECUO;
        } catch (\Throwable $e) {
            return self::RECUO;
        }
    }
}
