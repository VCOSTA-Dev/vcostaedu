<?php

namespace App\adms\Models\financeiro;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\financeiro\AdmsFinanceiro as F;

/**
 * AS LISTAS DO FINANCEIRO EM CONFIGURAÇÕES (Onda 8, financeiro refeito, Parte 1; D-99): os motivos de cada operação e as
 * bandeiras de cartão. Vêm com uma lista padrão (a migration 0080) e se editam aqui: incluir, renomear, mudar a ordem e
 * tirar de uso. Nada se apaga: o que já foi usado num desfazer ou num recibo continua aparecendo nele.
 */
final class AdmsFinListas
{
    public const OPERACOES = ['desfazer_recebimento' => 'Desfazer recebimento', 'baixar' => 'Baixar', 'cancelar' => 'Cancelar', 'prescrever' => 'Prescrever',
        'renegociar' => 'Renegociar', 'desfazer_baixa' => 'Desfazer baixa', 'desfazer_cancelamento' => 'Desfazer cancelamento', 'desfazer_prescricao' => 'Desfazer prescrição',
        'desfazer_renegociacao' => 'Desfazer renegociação'];

    public static function motivos(): array
    {
        return F::pdo()->query('SELECT * FROM fin_motivos ORDER BY FIELD(operacao, ' . implode(', ', array_map(static fn($k) => "'{$k}'", array_keys(self::OPERACOES))) . '), situacao, ordem, id')
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function bandeiras(): array
    {
        return F::pdo()->query('SELECT * FROM fin_bandeiras ORDER BY situacao, ordem, nome')->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Inclui (sem id) ou edita um motivo. @return string '' quando gravou; senão, o motivo da recusa */
    public static function salvarMotivo(array $d): string
    {
        $id = (int) ($d['id'] ?? 0);
        $op = (string) ($d['operacao'] ?? '');
        $nome = trim(preg_replace('/\s+/', ' ', (string) ($d['nome'] ?? '')));
        $sit = (int) ($d['situacao'] ?? 1) === 2 ? 2 : 1;
        $ordem = max(0, min(999, (int) ($d['ordem'] ?? 0)));
        if (!$id && !isset(self::OPERACOES[$op])) {
            return 'Escolha a operação.';
        }
        if (mb_strlen($nome) < 3 || mb_strlen($nome) > 120) {
            return 'O motivo tem de 3 a 120 letras.';
        }
        if ($id) {
            $st = F::pdo()->prepare('UPDATE fin_motivos SET nome = ?, situacao = ?, ordem = ? WHERE id = ?');
            $st->execute([$nome, $sit, $ordem, $id]);
        } else {
            F::pdo()->prepare('INSERT INTO fin_motivos (operacao, nome, situacao, ordem) VALUES (?, ?, 1, ?)')->execute([$op, $nome, $ordem ?: 50]);
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(584, 200, $id ? 3 : 2, "Motivo do financeiro: id={$id}; operacao={$op}; nome={$nome}; situacao={$sit}");
        return '';
    }

    /** Inclui (sem id) ou edita uma bandeira. @return string '' quando gravou; senão, o motivo da recusa */
    public static function salvarBandeira(array $d): string
    {
        $id = (int) ($d['id'] ?? 0);
        $nome = trim(preg_replace('/\s+/', ' ', (string) ($d['nome'] ?? '')));
        $sit = (int) ($d['situacao'] ?? 1) === 2 ? 2 : 1;
        $ordem = max(0, min(999, (int) ($d['ordem'] ?? 0)));
        if (mb_strlen($nome) < 2 || mb_strlen($nome) > 60) {
            return 'O nome da bandeira tem de 2 a 60 letras.';
        }
        if ($id) {
            F::pdo()->prepare('UPDATE fin_bandeiras SET nome = ?, situacao = ?, ordem = ? WHERE id = ?')->execute([$nome, $sit, $ordem, $id]);
        } else {
            F::pdo()->prepare('INSERT INTO fin_bandeiras (nome, situacao, ordem) VALUES (?, 1, ?)')->execute([$nome, $ordem ?: 50]);
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(586, 200, $id ? 3 : 2, "Bandeira de cartão: id={$id}; nome={$nome}; situacao={$sit}");
        return '';
    }
}
