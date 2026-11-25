<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\escola\AdmsResponsaveis as M;

/** Os textos das telas dos responsáveis (o histórico em palavras). */
class AdmsResponsaveisTela
{
    public const ACOES = [
        'cadastrar' => 'cadastro da pessoa',
        'editar' => 'cadastro alterado',
        'designar' => 'designação',
        'trocar' => 'troca',
        'encerrar' => 'papel encerrado',
        'liberar' => 'acesso acadêmico adicional liberado',
        'encerrar_liberacao' => 'acesso acadêmico adicional encerrado',
        'contato' => 'responsável para contato cadastrado',
        'contato_retirado' => 'responsável para contato retirado',
        'convite_gerado' => 'convite gerado',
        'convite_enviado' => 'convite enviado por e-mail',
        'convite_usado' => 'convite usado (senha definida)',
        'convite_cancelado' => 'convite cancelado',
        'bloquear' => 'acesso bloqueado',
        'desbloquear' => 'acesso desbloqueado',
        'senha_trocada' => 'senha trocada pelo "Esqueci a senha"',
        'atestado_emitido' => 'atestado de matrícula emitido pelo portal',
        'login_definido' => 'login do portal definido no convite',
        'login_trocado' => 'login do portal trocado',
    ];

    /** "Troca: Responsável financeiro de 2026 (Fulana → Beltrano)". */
    public static function acao(array $x): string
    {
        $txt = self::ACOES[$x['acao']] ?? $x['acao'];
        $depois = json_decode((string) ($x['depois'] ?? ''), true) ?: [];
        $antes = json_decode((string) ($x['antes'] ?? ''), true) ?: [];
        $papel = $depois['papel'] ?? ($antes['papel'] ?? null);
        if ($papel) {
            $txt .= ': ' . mb_strtolower(M::PAPEIS[$papel] ?? $papel) . ($x['ano'] ? ' de ' . $x['ano'] : '');
        }
        if (in_array($x['acao'], ['designar', 'trocar', 'liberar', 'encerrar', 'encerrar_liberacao'], true) && !empty($x['pessoa'])) {
            $txt .= ' — ' . $x['pessoa'];
        }
        if ($x['acao'] === 'trocar' && !empty($antes['responsavel_id']) && ($a = M::pessoa((int) $antes['responsavel_id']))) {
            $txt .= ' (no lugar de ' . $a['nome'] . ')';
        }
        return ucfirst($txt);
    }
}
