<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsRead;
use App\adms\Models\notas\AdmsCalculoNotas as Calc;

/**
 * AS NOTAS DE TRANSFERÊNCIA (correção da escola, Parte 3, A2; D-102): o aluno que entra no meio do ano traz da escola de
 * origem a nota e as faltas de cada disciplina, por etapa, com o nome da escola e o documento apresentado. Só a
 * secretaria (a supervisão e o administrador) lança: o professor não muda. Valem mesmo na etapa já fechada, contam na
 * média anual, aparecem marcadas no boletim ("T") e vão para a auditoria. Nada é apagado: a nota tirada fica "retirada",
 * e cada mudança fica no histórico (esc_notas_transferencia_hist).
 */
class AdmsNotasTransferencia
{
    /** O aluno (col_usuarios) e a turma dele no ano (a de agora, ou a última). */
    public static function aluno(int $alunoId): ?array
    {
        $l = new AdmsRead();
        $l->fullRead('SELECT id, rm, nome FROM col_usuarios WHERE id = :id AND col_niveis_acesso_id = 1 LIMIT :limit', "id={$alunoId}&limit=1");
        return $l->getResultado()[0] ?? null;
    }

    public static function turma(int $alunoId, int $ano): ?array
    {
        $l = new AdmsRead();
        $l->fullRead('SELECT t.id FROM esc_turma_alunos x INNER JOIN esc_turmas t ON t.id = x.turma_id
            WHERE x.usuario_id = :u AND t.ano = :a ORDER BY (x.saida IS NULL) DESC, x.id DESC LIMIT :limit', "u={$alunoId}&a={$ano}&limit=1");
        $id = (int) ($l->getResultado()[0]['id'] ?? 0);
        return $id ? AdmsTurmas::ver($id) : null; // o nome da turma (codtur) é montado pela própria AdmsTurmas
    }

    /** Os anos em que o aluno esteve numa turma (o mais novo primeiro). @return list<int> */
    public static function anos(int $alunoId): array
    {
        $l = new AdmsRead();
        $l->fullRead('SELECT DISTINCT t.ano FROM esc_turma_alunos x INNER JOIN esc_turmas t ON t.id = x.turma_id WHERE x.usuario_id = :u ORDER BY t.ano DESC', "u={$alunoId}");
        return array_map('intval', array_column($l->getResultado() ?: [], 'ano'));
    }

    /**
     * O quadro: as disciplinas da turma × as etapas do ano, com o que já foi trazido, e a data da matrícula.
     * @return array{turma: ?array, ofertas: list<array>, etapas: list<array>, valores: array<int, array<int, array>>, ingresso: ?string, escola: string, documento: string}
     */
    public static function quadro(int $alunoId, int $ano): array
    {
        $aluno = self::aluno($alunoId);
        $turma = self::turma($alunoId, $ano);
        $saida = ['turma' => $turma, 'ofertas' => [], 'etapas' => Calc::etapasDoAno($ano), 'valores' => [], 'ingresso' => null, 'escola' => '', 'documento' => ''];
        if (!$aluno || !$turma) {
            return $saida;
        }
        $saida['ofertas'] = AdmsTurmas::ofertas((int) $turma['id']);
        $ids = array_map('intval', array_column($saida['ofertas'], 'id'));
        if ($ids) {
            $l = new AdmsRead();
            $l->fullRead("SELECT * FROM esc_notas_transferencia WHERE rm = :rm AND situacao = 'valida' AND oferta_id IN (" . implode(',', $ids) . ') ORDER BY id', 'rm=' . $aluno['rm']);
            foreach ($l->getResultado() ?: [] as $r) {
                $saida['valores'][(int) $r['oferta_id']][(int) $r['etapa_id']] = $r;
                $saida['escola'] = (string) $r['escola_origem'];
                $saida['documento'] = (string) $r['documento'];
            }
        }
        $l = new AdmsRead();
        $l->fullRead('SELECT MAX(data_ingresso) AS d FROM rm_matriculas_curso WHERE usuario_id = :u AND data_ingresso <= :fim', "u={$alunoId}&fim={$ano}-12-31");
        $saida['ingresso'] = $l->getResultado()[0]['d'] ?? null;
        return $saida;
    }

    /**
     * Grava a tela: $notas[oferta][etapa] e $faltas[oferta][etapa]; a escola de origem e o documento (obrigatórios quando
     * há algo trazido). Só muda a célula que mudou; a célula esvaziada (sem nota e sem falta) é retirada.
     * @return int|string quantas células mudaram, ou o motivo da recusa (nada é gravado)
     */
    public static function salvar(int $alunoId, int $ano, array $notas, array $faltas, string $escola, string $documento)
    {
        $aluno = self::aluno($alunoId);
        $q = self::quadro($alunoId, $ano);
        if (!$aluno || !$q['turma']) {
            return 'O aluno não está numa turma neste ano: enturme-o antes de lançar as notas de transferência.';
        }
        $escola = trim($escola);
        $documento = trim($documento);
        $nomeEt = array_column($q['etapas'], 'nome', 'id');
        $mudancas = [];
        $algo = false;
        foreach ($q['ofertas'] as $o) {
            $of = (int) $o['id'];
            foreach ($q['etapas'] as $e) {
                $et = (int) $e['id'];
                $n = trim((string) ($notas[$of][$et] ?? ''));
                $f = trim((string) ($faltas[$of][$et] ?? ''));
                if ($n !== '' && Calc::centesimos($n) === null) {
                    return "Nota inválida em {$o['materia']}, {$e['nome']}: use um número de 0 a 10 com até 2 casas.";
                }
                if ($f !== '' && !preg_match('/^\d{1,3}$/', $f)) {
                    return "Faltas inválidas em {$o['materia']}, {$e['nome']}: um número inteiro de 0 a 999.";
                }
                $nota = $n === '' ? null : number_format(Calc::centesimos($n) / 100, 2, '.', '');
                $fal = $f === '' ? 0 : (int) $f;
                $antes = $q['valores'][$of][$et] ?? null;
                if ($nota !== null || $fal > 0) {
                    $algo = true;
                }
                $notaAntes = $antes && $antes['nota'] !== null ? number_format((float) $antes['nota'], 2, '.', '') : null;
                $faltasAntes = $antes ? (int) $antes['faltas'] : 0;
                $mesmaOrigem = !$antes || ((string) $antes['escola_origem'] === $escola && (string) $antes['documento'] === $documento);
                if ($nota === $notaAntes && $fal === $faltasAntes && ($mesmaOrigem || ($nota === null && $fal === 0))) {
                    continue;
                }
                $mudancas[] = ['oferta' => $of, 'etapa' => $et, 'nota' => $nota, 'faltas' => $fal, 'antes' => $antes, 'notaAntes' => $notaAntes, 'faltasAntes' => $faltasAntes,
                    'materia' => (string) $o['materia'], 'nomeEtapa' => (string) ($nomeEt[$et] ?? '')];
            }
        }
        if ($algo && mb_strlen($escola) < 3) {
            return 'Informe a escola de origem (o nome da escola de onde o aluno veio).';
        }
        if ($algo && mb_strlen($documento) < 3) {
            return 'Informe o documento apresentado (por exemplo, a declaração de transferência ou o histórico parcial).';
        }
        if (!$mudancas) {
            return 0;
        }
        $por = (int) ($_SESSION['colUsuario_id'] ?? 0);
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($mudancas, $aluno, $escola, $documento, $por) {
            $pdo = AdmsEscolaBanco::pdo();
            foreach ($mudancas as $m) {
                $vazia = $m['nota'] === null && $m['faltas'] === 0;
                if ($m['antes']) {
                    $id = (int) $m['antes']['id'];
                    if ($vazia) {
                        $pdo->prepare("UPDATE esc_notas_transferencia SET situacao = 'retirada', alterado_por = ?, alterado_em = NOW() WHERE id = ?")->execute([$por ?: null, $id]);
                    } else {
                        $pdo->prepare('UPDATE esc_notas_transferencia SET nota = ?, faltas = ?, escola_origem = ?, documento = ?, alterado_por = ?, alterado_em = NOW() WHERE id = ?')
                            ->execute([$m['nota'], $m['faltas'], mb_substr($escola, 0, 160), mb_substr($documento, 0, 160), $por ?: null, $id]);
                    }
                } else {
                    $pdo->prepare("INSERT INTO esc_notas_transferencia (aluno_id, rm, oferta_id, etapa_id, nota, faltas, escola_origem, documento, situacao, por, em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'valida', ?, NOW())")
                        ->execute([(int) $aluno['id'], (string) $aluno['rm'], $m['oferta'], $m['etapa'], $m['nota'], $m['faltas'], mb_substr($escola, 0, 160), mb_substr($documento, 0, 160), $por ?: null]);
                    $id = (int) $pdo->lastInsertId();
                }
                $pdo->prepare('INSERT INTO esc_notas_transferencia_hist (transferencia_id, nota_antes, faltas_antes, nota, faltas, escola_origem, documento, por, em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())')
                    ->execute([$id, $m['notaAntes'], $m['faltasAntes'], $m['nota'], $m['faltas'], mb_substr($escola, 0, 160), mb_substr($documento, 0, 160), $por ?: null]);
            }
            return true;
        });
        if (!$ok) {
            return 'As notas de transferência não foram gravadas (' . $T::ultimoErro() . '). Nada mudou.';
        }
        foreach ($mudancas as $m) {
            if ($m['nota'] !== $m['notaAntes']) {
                \App\adms\Models\helper\AdmsAuditoria::transferencia($m['oferta'], (string) $aluno['rm'], (string) $aluno['nome'], $m['notaAntes'], $m['nota'], $m['nomeEtapa']);
            }
            if ($m['faltas'] !== $m['faltasAntes']) {
                \App\adms\Models\helper\AdmsAuditoria::transferencia($m['oferta'], (string) $aluno['rm'], (string) $aluno['nome'], (string) $m['faltasAntes'], (string) $m['faltas'], $m['nomeEtapa'], 'faltas_transferencia');
            }
        }
        return count($mudancas);
    }
}
