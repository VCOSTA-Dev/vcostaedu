<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 5) . '/core/Banco.php';
require_once dirname(__DIR__, 5) . '/core/TipoLivre.php';

/**
 * Fechamento (D-106): o tipo da instituição, no portal, e a marca do tipo nas páginas do portal (col_paginas_modulo,
 * migration 0087). A página de um módulo desligado sai do menu (AdmsMenus) e o endereço direto leva à Página Inicial
 * (ConfigController). No curso livre, o aluno tem a Página Inicial, o Moodle e a Ajuda; nos outros tipos, nada muda.
 */
class AdmsTipoPortal
{
    private static ?string $tipo = null;

    /** @var array<int, string>|null */
    private static ?array $modulos = null;

    public static function atual(): string
    {
        try {
            return self::$tipo ??= \VcTipoLivre::tipo(\VcBanco::daRequisicao());
        } catch (\Throwable $e) {
            return \VcVocabulario::SUPERIOR;
        }
    }

    public static function ehLivre(): bool
    {
        return self::atual() === \VcVocabulario::LIVRE;
    }

    /** A página do portal vale neste tipo? (sem a marca, o núcleo: sempre) */
    public static function paginaLigada(int $paginaId): bool
    {
        try {
            self::$modulos ??= \VcTipoLivre::modulosDoPortal(\VcBanco::daRequisicao());
        } catch (\Throwable $e) {
            self::$modulos = [];
        }
        return \VcVocabulario::moduloNoTipo(self::$modulos[$paginaId] ?? null, self::atual());
    }

    /** O guia do portal vale neste tipo? ('' = todos; senão a lista com vírgula, como no painel) */
    public static function guiaLigado(string $tipoGuia): bool
    {
        return \VcVocabulario::moduloNoTipo($tipoGuia === '' ? null : $tipoGuia, self::atual());
    }
}
