<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * ENTURMAR EM LOTE, EM PASSOS (docs/OPERACOES_LONGAS.md): vários alunos numa turma. O estado fica no banco
 * (adms_instalacao_estado, "escola.lote.turma.<id>": quem falta, quantos entraram, as recusas); cada passo põe alguns
 * alunos (até 4 segundos), e a janela de progresso pede o próximo. A página fechada no meio retoma de onde parou; uma
 * trava (GET_LOCK) impede dois passos ao mesmo tempo.
 */
class AdmsTurmaLote
{
    public const ORCAMENTO = 4.0;

    private static function chave(int $turmaId): string
    {
        return 'escola.lote.turma.' . $turmaId;
    }

    public static function estado(int $turmaId): ?array
    {
        $v = \App\adms\Models\helper\AdmsAmbiente::estado(self::chave($turmaId));
        $e = $v ? json_decode($v, true) : null;
        return is_array($e) ? $e : null;
    }

    private static function gravar(int $turmaId, ?array $e): void
    {
        \App\adms\Models\helper\AdmsAmbiente::gravarEstado(self::chave($turmaId), $e === null ? null : json_encode($e, JSON_UNESCAPED_UNICODE));
    }

    /** Começa (ou retoma, se houver lote desta turma) com os alunos marcados. @return array o estado público */
    public static function iniciar(int $turmaId, array $usuarios): array
    {
        $atual = self::estado($turmaId);
        if ($atual && empty($atual['concluida'])) {
            return self::publico($atual);
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $usuarios))));
        $e = ['turma' => $turmaId, 'total' => count($ids), 'pendentes' => $ids, 'feitos' => 0, 'entraram' => 0, 'recusas' => [], 'concluida' => $ids === []];
        self::gravar($turmaId, $e);
        return self::publico($e);
    }

    /** Um passo: alguns alunos, até o orçamento de tempo. */
    public static function passo(int $turmaId): array
    {
        $pdo = AdmsEscolaBanco::pdo();
        if ((int) $pdo->query("SELECT GET_LOCK('vcostaedu_lote_turma_{$turmaId}', 0)")->fetchColumn() !== 1) {
            return ['ocupado' => true];
        }
        try {
            $e = self::estado($turmaId);
            if (!$e) {
                return ['erro' => 'Não há enturmação em andamento nesta turma.'];
            }
            $t0 = microtime(true);
            while ($e['pendentes'] && microtime(true) - $t0 < self::ORCAMENTO) {
                $u = (int) array_shift($e['pendentes']);
                $r = AdmsTurmaAlunos::entrar($turmaId, $u);
                $e['feitos']++;
                if ($r === true) {
                    $e['entraram']++;
                } else {
                    $e['recusas'][] = $r;
                }
                self::gravar($turmaId, $e); // aluno a aluno: a página fechada retoma do seguinte
            }
            if (!$e['pendentes']) {
                $e['concluida'] = true;
                self::gravar($turmaId, $e);
            }
            return self::publico($e);
        } finally {
            $pdo->query("SELECT RELEASE_LOCK('vcostaedu_lote_turma_{$turmaId}')");
        }
    }

    /** Depois de mostrar o resultado, o lote concluído sai do banco. */
    public static function esquecer(int $turmaId): void
    {
        $e = self::estado($turmaId);
        if ($e && !empty($e['concluida'])) {
            self::gravar($turmaId, null);
        }
    }

    /** O estado para a janela de progresso: passo, total, concluída, e o resumo. */
    public static function publico(?array $e): array
    {
        if (!$e) {
            return ['nada' => true];
        }
        return ['passo' => (int) $e['feitos'], 'total' => max(1, (int) $e['total']), 'concluida' => !empty($e['concluida']),
            'entraram' => (int) $e['entraram'], 'recusas' => array_slice((array) $e['recusas'], 0, 20),
            'grupoAtual' => !empty($e['concluida']) ? 'Pronto' : 'aluno ' . min((int) $e['feitos'] + 1, (int) $e['total']) . ' de ' . (int) $e['total']];
    }
}
