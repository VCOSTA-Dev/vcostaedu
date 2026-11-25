<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * E8d (P-8) — INATIVAR e REATIVAR curso e matéria. Inativo (situação 2) some das listas de ESCOLHA (nova grade, nova
 * oferta, vínculo, inclusão na matriz) e continua nas listas de CONSULTA com a etiqueta "Inativo". Nada do passado muda
 * (ofertas, notas, boletins, históricos e emissões): só a situação do cadastro. Apagar continua só para o que nunca foi
 * usado (D-270). Rotas 413 (cursos/situacao, administrador) e 414 (materias/situacao, administrador e secretaria).
 */
class AdmsInativar
{
    public const ATIVO = 1;
    public const INATIVO = 2;
    private const TABELAS = ['curso' => ['rm_cursos', 'Curso', 413], 'materia' => ['rm_materias', 'Matéria', 414]];

    public static function alternar(string $tipo, int $id, int $nova): bool
    {
        if (!isset(self::TABELAS[$tipo]) || !in_array($nova, [self::ATIVO, self::INATIVO], true)) {
            return self::aviso('danger', 'Pedido inválido.');
        }
        [$tabela, $rotulo, $pagina] = self::TABELAS[$tipo];
        $ler = new AdmsRead();
        $ler->fullRead("SELECT id, nome, situacao FROM {$tabela} WHERE id = :id LIMIT :limit", "id={$id}&limit=1");
        $reg = $ler->getResultado()[0] ?? null;
        if (!$reg) {
            return self::aviso('danger', "{$rotulo} não encontrado(a).");
        }
        if ((int) $reg['situacao'] === $nova) {
            return self::aviso('info', "{$rotulo} «{$reg['nome']}» já está " . ($nova === self::ATIVO ? 'ativo(a)' : 'inativo(a)') . '.');
        }
        $up = new AdmsUpdate();
        $up->exeUpdate($tabela, ['situacao' => $nova, 'usuario_mod' => (int) ($_SESSION['colUsuario_id'] ?? 0)], 'WHERE id = :id', "id={$id}");
        if ($up->getResultado() === null) {
            return self::aviso('danger', "Não foi possível alterar a situação de «{$reg['nome']}».");
        }
        (new AdmsSalvarLog())->salvarLogs($pagina, 200, 3, "{$rotulo} " . ($nova === self::ATIVO ? 'reativado(a)' : 'inativado(a)') . ": id={$id}; nome={$reg['nome']}");
        return self::aviso('success', $nova === self::ATIVO
            ? "{$rotulo} «{$reg['nome']}» reativado(a): volta às listas de escolha."
            : "{$rotulo} «{$reg['nome']}» inativado(a): sai das listas de escolha (nova grade, nova oferta, vínculo). O que já foi feito com ele(a) não muda.", true);
    }

    public static function ativo(string $tipo, int $id): bool
    {
        [$tabela] = self::TABELAS[$tipo];
        $ler = new AdmsRead();
        $ler->fullRead("SELECT situacao FROM {$tabela} WHERE id = :id LIMIT :limit", "id={$id}&limit=1");
        return (int) ($ler->getResultado()[0]['situacao'] ?? 0) === self::ATIVO;
    }

    private static function aviso(string $tipo, string $texto, bool $ok = false): bool
    {
        $_SESSION['msg'] = "<div class='alert alert-{$tipo} alert-dismissible' id='avisoSituacaoCadastro'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
            . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</div>';
        return $ok;
    }
}
