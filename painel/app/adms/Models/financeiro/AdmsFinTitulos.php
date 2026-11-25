<?php

namespace App\adms\Models\financeiro;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\financeiro\AdmsFinanceiro as F;

/**
 * OS TÍTULOS (Onda 8, financeiro, Parte 1; D-98): as regras, os planos de pagamento, as parcelas geradas na matrícula, o
 * título avulso e a ficha financeira do aluno (os títulos com os encargos de hoje e os filtros com as contagens).
 */
final class AdmsFinTitulos
{
    /* ---------------------------------------------------------------- as regras */

    /** @return string '' quando gravou; senão, o motivo */
    public static function salvarRegras(array $d, int $usuario): string
    {
        $multa = F::pontos((string) ($d['multa'] ?? ''));
        $juros = F::pontos((string) ($d['juros'] ?? ''));
        $desc = F::pontos((string) ($d['desconto'] ?? ''));
        if ($multa === null || $juros === null || $desc === null || $multa > 2000 || $juros > 1000 || $desc > 5000) {
            return 'Use números em percentual, com até duas casas (por exemplo, 2 ou 2,5): a multa até 20%, os juros até 10% ao mês e o desconto até 50%.';
        }
        $antes = F::regras();
        F::pdo()->prepare('UPDATE fin_regras SET multa_bp = ?, juros_mes_bp = ?, desconto_bp = ?, atualizado_por = ?, atualizado_em = NOW() WHERE id = 1')->execute([$multa, $juros, $desc, $usuario]);
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(561, 200, 3, 'Regras financeiras: antes=' . json_encode($antes) . '; depois=' . json_encode(['multa_bp' => $multa, 'juros_mes_bp' => $juros, 'desconto_bp' => $desc]));
        return '';
    }

    /* ---------------------------------------------------------------- os planos */

    /** @return list<array> */
    public static function planos(bool $soAtivos = false): array
    {
        return F::pdo()->query('SELECT * FROM fin_planos' . ($soAtivos ? ' WHERE situacao = 1' : '') . ' ORDER BY situacao, nome')->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function plano(int $id): ?array
    {
        $st = F::pdo()->prepare('SELECT * FROM fin_planos WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /** Cadastra (sem id) ou edita. @return string '' quando gravou; senão, o motivo */
    public static function salvarPlano(array $d, int $usuario): string
    {
        $id = (int) ($d['id'] ?? 0);
        $nome = trim((string) ($d['nome'] ?? ''));
        $parcelas = (int) ($d['parcelas'] ?? 0);
        $valor = F::centavos((string) ($d['valor'] ?? ''));
        $dia = (int) ($d['dia_vencimento'] ?? 0);
        $mes = (int) ($d['mes_primeira'] ?? 0);
        if ($nome === '' || mb_strlen($nome) > 120) {
            return 'Dê um nome ao plano (até 120 letras).';
        }
        if ($parcelas < 1 || $parcelas > 24) {
            return 'O número de parcelas vai de 1 a 24.';
        }
        if ($valor === null || $valor < 1) {
            return 'Informe o valor da parcela (por exemplo, 650,00).';
        }
        if ($dia < 1 || $dia > 28) {
            return 'O dia de vencimento vai de 1 a 28 (o mesmo em todos os meses).';
        }
        if ($mes < 1 || $mes > 12) {
            return 'Escolha o mês da primeira parcela.';
        }
        if ($id) {
            if (!self::plano($id)) {
                return 'Plano não encontrado.';
            }
            /* o plano já usado muda só para as matrículas de depois: as parcelas já geradas ficam como estão */
            F::pdo()->prepare('UPDATE fin_planos SET nome = ?, parcelas = ?, valor_centavos = ?, dia_vencimento = ?, mes_primeira = ? WHERE id = ?')->execute([$nome, $parcelas, $valor, $dia, $mes, $id]);
        } else {
            F::pdo()->prepare('INSERT INTO fin_planos (nome, parcelas, valor_centavos, dia_vencimento, mes_primeira, situacao, criado_por, criado_em) VALUES (?, ?, ?, ?, ?, 1, ?, NOW())')
                ->execute([$nome, $parcelas, $valor, $dia, $mes, $usuario]);
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(563, 200, $id ? 3 : 2, "Plano de pagamento: id={$id}; nome={$nome}; parcelas={$parcelas}; valor={$valor}; dia={$dia}; mes={$mes}");
        return '';
    }

    public static function situacaoPlano(int $id, int $sit): string
    {
        if (!self::plano($id) || !in_array($sit, [1, 2], true)) {
            return 'Plano não encontrado.';
        }
        F::pdo()->prepare('UPDATE fin_planos SET situacao = ? WHERE id = ?')->execute([$sit, $id]);
        return '';
    }

    /**
     * As parcelas do plano para a matrícula: a primeira no mês do plano, no ano da matrícula, no dia de vencimento; uma
     * por mês. Repetível: a parcela que já existe (a mesma matrícula, o mesmo plano, a mesma parcela) não nasce de novo.
     * @return array{criadas: int, erro: string}
     */
    public static function gerarDoPlano(int $matriculaId, int $planoId, int $usuario, ?int $ano = null, int $aPartir = 1): array
    {
        $p = self::plano($planoId);
        $st = F::pdo()->prepare('SELECT mc.id, mc.usuario_id, mc.data_ingresso, c.nome AS curso FROM rm_matriculas_curso mc INNER JOIN rm_cursos c ON c.id = mc.curso_id WHERE mc.id = ?');
        $st->execute([$matriculaId]);
        $mc = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$p || (int) $p['situacao'] !== 1) {
            return ['criadas' => 0, 'erro' => 'Escolha um plano de pagamento em uso.'];
        }
        if (!$mc) {
            return ['criadas' => 0, 'erro' => 'Matrícula não encontrada.'];
        }
        $ano = $ano ?: (int) substr((string) ($mc['data_ingresso'] ?: date('Y-m-d')), 0, 4);
        $criadas = 0;
        $ok = \Core\Transacao::executar(static function () use ($p, $mc, $ano, $usuario, $aPartir, &$criadas) {
            $ins = F::pdo()->prepare('INSERT IGNORE INTO fin_titulos (aluno_id, matricula_curso_id, plano_id, parcela, parcelas_total, ano, descricao, valor_centavos, vencimento, situacao, criado_por, criado_em)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'aberto\', ?, NOW())');
            for ($i = max(1, $aPartir); $i <= (int) $p['parcelas']; $i++) { // D-102 (A1): a partir da parcela escolhida
                $mes = (int) $p['mes_primeira'] + $i - 1;
                $a = $ano + intdiv($mes - 1, 12);
                $m = (($mes - 1) % 12) + 1;
                $venc = sprintf('%04d-%02d-%02d', $a, $m, (int) $p['dia_vencimento']);
                $ins->execute([(int) $mc['usuario_id'], (int) $mc['id'], (int) $p['id'], $i, (int) $p['parcelas'], $ano, "{$p['nome']} — parcela {$i} de {$p['parcelas']}", (int) $p['valor_centavos'], $venc, $usuario]);
                if ($ins->rowCount() > 0) {
                    $criadas++;
                    F::registrar(['titulo_id' => (int) F::pdo()->lastInsertId(), 'aluno_id' => (int) $mc['usuario_id'], 'tipo' => 'gerar', 'original_centavos' => (int) $p['valor_centavos'],
                        'situacao_depois' => 'aberto', 'observacao' => "Plano {$p['nome']}", 'usuario_id' => $usuario]);
                }
            }
            return true;
        });
        if ($ok === false) {
            return ['criadas' => 0, 'erro' => 'Nada foi gravado: ' . \Core\Transacao::ultimoErro() . '.'];
        }
        return ['criadas' => $criadas, 'erro' => ''];
    }

    /**
     * Correção da escola, Parte 3 (A1; D-102): o vencimento de cada parcela do plano no ano, e a primeira que ainda não
     * venceu (0 quando todas já venceram). @return array{vencimentos: array<int, string>, primeira: int}
     */
    public static function vencimentosDoPlano(int $planoId, int $ano): array
    {
        $p = self::plano($planoId);
        $v = [];
        $primeira = 0;
        for ($i = 1; $p && $i <= (int) $p['parcelas']; $i++) {
            $mes = (int) $p['mes_primeira'] + $i - 1;
            $v[$i] = sprintf('%04d-%02d-%02d', $ano + intdiv($mes - 1, 12), (($mes - 1) % 12) + 1, (int) $p['dia_vencimento']);
            if (!$primeira && $v[$i] >= date('Y-m-d')) {
                $primeira = $i;
            }
        }
        return ['vencimentos' => $v, 'primeira' => $primeira];
    }

    /** O título avulso. @return string '' quando gravou; senão, o motivo */
    public static function avulso(int $alunoId, array $d, int $usuario): string
    {
        $desc = trim((string) ($d['descricao'] ?? ''));
        $valor = F::centavos((string) ($d['valor'] ?? ''));
        $venc = trim((string) ($d['vencimento'] ?? ''));
        $dt = \DateTime::createFromFormat('Y-m-d', $venc);
        if ($desc === '' || mb_strlen($desc) > 160) {
            return 'Escreva a descrição (até 160 letras).';
        }
        if ($valor === null || $valor < 1) {
            return 'Informe o valor (por exemplo, 120,00).';
        }
        if (!$dt || $dt->format('Y-m-d') !== $venc) {
            return 'Informe o vencimento.';
        }
        $st = F::pdo()->prepare('SELECT id FROM col_usuarios WHERE id = ?');
        $st->execute([$alunoId]);
        if (!$st->fetchColumn()) {
            return 'Aluno não encontrado.';
        }
        $mc = (int) ($d['matricula_curso_id'] ?? 0) ?: null;
        /* o ano letivo do título (D-99): o escolhido no formulário (o da ficha aberta); sem ele, o ano do vencimento */
        $ano = (int) ($d['ano'] ?? 0);
        $ano = $ano >= 2000 && $ano <= 2100 ? $ano : (int) substr($venc, 0, 4);
        $ok = \Core\Transacao::executar(static function () use ($alunoId, $mc, $ano, $desc, $valor, $venc, $usuario) {
            F::pdo()->prepare('INSERT INTO fin_titulos (aluno_id, matricula_curso_id, ano, descricao, valor_centavos, vencimento, situacao, criado_por, criado_em) VALUES (?, ?, ?, ?, ?, ?, \'aberto\', ?, NOW())')
                ->execute([$alunoId, $mc, $ano, $desc, $valor, $venc, $usuario]);
            F::registrar(['titulo_id' => (int) F::pdo()->lastInsertId(), 'aluno_id' => $alunoId, 'tipo' => 'gerar', 'original_centavos' => $valor, 'situacao_depois' => 'aberto', 'observacao' => 'Título avulso', 'usuario_id' => $usuario]);
            return true;
        });
        return $ok === false ? 'Nada foi gravado: ' . \Core\Transacao::ultimoErro() . '.' : '';
    }

    /* ---------------------------------------------------------------- a ficha */

    /** As matrículas do aluno (para aplicar um plano e para o avulso). */
    public static function matriculas(int $alunoId): array
    {
        $st = F::pdo()->prepare('SELECT mc.id, c.nome AS curso, mc.data_ingresso, s.nome AS situacao FROM rm_matriculas_curso mc INNER JOIN rm_cursos c ON c.id = mc.curso_id
            LEFT JOIN rm_matriculas_situacao s ON s.id = mc.situacao_id WHERE mc.usuario_id = ? ORDER BY mc.id DESC');
        $st->execute([$alunoId]);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }
}
