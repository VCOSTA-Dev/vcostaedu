<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsRead;
use App\adms\Models\notas\AdmsCalculoNotas as Calc;

/**
 * O CONSELHO DE CLASSE (Onda 4, Etapa 3; docs/ESCOLA.md, D-48): por turma e etapa (o registro do que o conselho
 * observou), e no fim do ano (a decisão). A decisão do fim do ano pode APROVAR o aluno reprovado, com a observação
 * obrigatória, quem decidiu e quando (esc_conselho). A nota não muda: muda a situação ("Aprovado pelo conselho"), e a
 * memória de cálculo mostra a decisão.
 */
class AdmsConselho
{
    /** Os alunos da turma, com cada componente (a média anual e a situação, como se o ano fechasse agora) e as decisões. */
    public static function quadro(int $turmaId): array
    {
        $ofertas = AdmsTurmas::ofertas($turmaId);
        $alunos = [];
        foreach (AdmsTurmaAlunos::alunos($turmaId) as $a) {
            $alunos[(string) $a['rm']] = ['rm' => (string) $a['rm'], 'nome' => $a['nome'], 'componentes' => [], 'reprovado' => false];
        }
        foreach ($ofertas as $o) {
            foreach ((Calc::oferta((int) $o['id'], true)['alunos'] ?? []) as $c) {
                $rm = (string) $c['rm'];
                if (!isset($alunos[$rm])) {
                    continue;
                }
                $alunos[$rm]['componentes'][] = ['oferta' => (int) $o['id'], 'materia' => $o['materia'], 'media' => $c['media'], 'situacao' => $c['situacao'] ?? 'em_curso',
                    'frequencia' => $c['frequencia']];
                if (str_starts_with((string) ($c['situacao'] ?? ''), 'reprovado')) {
                    $alunos[$rm]['reprovado'] = true;
                }
            }
        }
        return ['ofertas' => $ofertas, 'alunos' => array_values($alunos), 'decisoes' => self::decisoes($turmaId)];
    }

    /** As decisões e observações da turma: [etapa_id ou 0 => [rm => [linhas]]]. */
    public static function decisoes(int $turmaId): array
    {
        $l = new AdmsRead();
        /* D-100: a observação retirada sai da tela (fica no histórico); a corrigida mostra quem corrigiu e quando */
        $com = \App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0081');
        $l->fullRead('SELECT c.*, u.nome AS por_nome' . ($com ? ', ua.nome AS alterado_por_nome' : '') . ' FROM esc_conselho c LEFT JOIN adms_usuarios u ON u.id = c.por'
            . ($com ? ' LEFT JOIN adms_usuarios ua ON ua.id = c.alterado_por' : '') . ' WHERE c.turma_id = :t' . ($com ? " AND c.situacao = 'valida'" : '') . ' ORDER BY c.id', "t={$turmaId}");
        $d = [];
        foreach ($l->getResultado() ?: [] as $r) {
            $d[(int) ($r['etapa_id'] ?? 0)][(string) $r['rm']][] = $r;
        }
        return $d;
    }

    /** Quem corrige ou retira a observação: o autor dela, a direção acadêmica ou o administrador. */
    public static function podeMudar(array $registro): bool
    {
        return (int) ($registro['por'] ?? 0) === (int) ($_SESSION['colUsuario_id'] ?? -1)
            || \App\adms\Models\helper\AdmsNivel::ehPapel('administrador', 'direcao');
    }

    private static function registro(int $turmaId, int $id): ?array
    {
        $l = new AdmsRead();
        $l->fullRead("SELECT * FROM esc_conselho WHERE id = :id AND turma_id = :t AND situacao = 'valida' LIMIT :limit", "id={$id}&t={$turmaId}&limit=1");
        return $l->getResultado()[0] ?? null;
    }

    /**
     * B15 (D-100): corrige o texto da observação (o autor ou a direção). O texto de antes fica no histórico.
     * @return true|string
     */
    public static function corrigir(int $turmaId, int $id, string $observacao)
    {
        $r = self::registro($turmaId, $id);
        if (!$r) {
            return 'Registro do conselho não encontrado.';
        }
        if (!self::podeMudar($r)) {
            return 'Só quem registrou a observação ou a direção acadêmica a corrige.';
        }
        $observacao = trim($observacao);
        if (mb_strlen($observacao) < 10) {
            return 'A observação do conselho é obrigatória (ao menos 10 letras).';
        }
        if ($observacao === (string) $r['observacao']) {
            return true;
        }
        $por = (int) ($_SESSION['colUsuario_id'] ?? 0);
        $pdo = AdmsEscolaBanco::pdo();
        $pdo->prepare('INSERT INTO esc_conselho_hist (conselho_id, acao, observacao_antes, observacao_depois, por, em) VALUES (?, ?, ?, ?, ?, NOW())')
            ->execute([$id, 'corrigiu', $r['observacao'], mb_substr($observacao, 0, 1000), $por ?: null]);
        $pdo->prepare('UPDATE esc_conselho SET observacao = ?, alterado_por = ?, alterado_em = NOW() WHERE id = ?')->execute([mb_substr($observacao, 0, 1000), $por ?: null, $id]);
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(590, 200, 1, "Conselho de classe corrigido: turma={$turmaId}; registro={$id}; rm={$r['rm']}");
        return true;
    }

    /**
     * B15 (D-100): retira a observação (ou a decisão de aprovar): fica marcada como retirada, no histórico; nada é apagado.
     * @return true|string
     */
    public static function retirar(int $turmaId, int $id)
    {
        $r = self::registro($turmaId, $id);
        if (!$r) {
            return 'Registro do conselho não encontrado.';
        }
        if (!self::podeMudar($r)) {
            return 'Só quem registrou a observação ou a direção acadêmica a retira.';
        }
        $por = (int) ($_SESSION['colUsuario_id'] ?? 0);
        $pdo = AdmsEscolaBanco::pdo();
        $pdo->prepare('INSERT INTO esc_conselho_hist (conselho_id, acao, observacao_antes, observacao_depois, por, em) VALUES (?, ?, ?, NULL, ?, NOW())')
            ->execute([$id, 'retirou', $r['observacao'], $por ?: null]);
        $pdo->prepare("UPDATE esc_conselho SET situacao = 'retirada', alterado_por = ?, alterado_em = NOW() WHERE id = ?")->execute([$por ?: null, $id]);
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(591, 200, 1, "Conselho de classe retirado: turma={$turmaId}; registro={$id}; rm={$r['rm']}; decisao={$r['decisao']}");
        return true;
    }

    /**
     * Registra a decisão ou a observação do conselho.
     * @param int $etapaId 0 = o fim do ano
     * @return true|string
     */
    public static function registrar(int $turmaId, int $etapaId, string $rm, string $decisao, string $observacao)
    {
        $t = AdmsTurmas::ver($turmaId);
        if (!$t) {
            return 'Turma não encontrada.';
        }
        $na = array_filter(AdmsTurmaAlunos::alunos($turmaId), static fn($a) => (string) $a['rm'] === $rm);
        if (!$na) {
            return 'O aluno não está nesta turma.';
        }
        $observacao = trim($observacao);
        if (mb_strlen($observacao) < 10) {
            return 'A observação do conselho é obrigatória (ao menos 10 letras): ela fica registrada com a decisão.';
        }
        if (!in_array($decisao, ['aprovar', 'observacao'], true)) {
            return 'Decisão inválida.';
        }
        if ($decisao === 'aprovar') {
            if ($etapaId !== 0) {
                return 'A aprovação pelo conselho é a do fim do ano; numa etapa, o conselho registra a observação.';
            }
            $rep = false;
            foreach (self::quadro($turmaId)['alunos'] as $a) {
                if ($a['rm'] === $rm) {
                    $rep = $a['reprovado'];
                }
            }
            if (!$rep) {
                return 'O conselho aprova quem ficou reprovado: este aluno não está reprovado em nenhuma disciplina.';
            }
        }
        if ($etapaId !== 0) {
            $l = new AdmsRead();
            $l->fullRead('SELECT id FROM esc_etapas WHERE id = :e AND ano = :a LIMIT :limit', "e={$etapaId}&a={$t['ano']}&limit=1");
            if (!$l->getResultado()) {
                return 'Etapa não encontrada no ano da turma.';
            }
        }
        AdmsEscolaBanco::pdo()->prepare('INSERT INTO esc_conselho (turma_id, etapa_id, rm, oferta_id, decisao, observacao, por, em) VALUES (?, ?, ?, NULL, ?, ?, ?, NOW())')
            ->execute([$turmaId, $etapaId ?: null, $rm, $decisao, mb_substr($observacao, 0, 1000), (int) ($_SESSION['colUsuario_id'] ?? 0)]);
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(526, 200, 1, "Conselho de classe: turma={$turmaId}; etapa={$etapaId}; rm={$rm}; decisao={$decisao}");
        return true;
    }
}
