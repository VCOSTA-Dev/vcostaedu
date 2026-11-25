<?php

namespace App\adms\Models\moodle;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/** Uma recusa do Moodle (ou da regra do envio), com o código do glossário (VcMoodle::GLOSSARIO). */
class FalhaMoodle extends \RuntimeException
{
    /** @param int[] $contas na pendência de várias contas com o mesmo e-mail: os ids, para o operador escolher */
    public function __construct(public string $codigo, string $mensagem = '', public array $contas = [])
    {
        parent::__construct($mensagem !== '' ? $mensagem : $codigo);
    }
}
