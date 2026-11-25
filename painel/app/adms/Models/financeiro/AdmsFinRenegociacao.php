<?php

namespace App\adms\Models\financeiro;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\financeiro\AdmsFinanceiro as F;
use App\adms\Models\financeiro\AdmsFinRecibos as R;

/**
 * RENEGOCIAR (Onda 8, financeiro refeito, Parte 2; D-105; docs/FINANCEIRO.md), no desenho do sistema de origem.
 *   - a CONTA: os títulos em aberto escolhidos, com os encargos na data da renegociação (de hoje para trás), mais o
 *     acréscimo, menos o desconto (com algum deles, a justificativa) = o valor a renegociar. O servidor refaz a conta
 *     sempre, e recusa se ela não bate com a que a tela mostrou;
 *   - as duas CONDIÇÕES: a parcelada (a entrada em R$ ou em %, o vencimento dela, as parcelas e o dia; a entrada é a 01;
 *     as parcelas vencem a partir do mês seguinte, no dia; o vencimento de cada uma pode mudar, o valor não; a última leva
 *     os centavos da divisão) e a à vista (o desconto em R$ ou em %, o vencimento e o valor a pagar);
 *   - SALVAR a proposta: as duas condições guardadas com os valores do dia; vale até a data da renegociação; a nova
 *     proposta dos mesmos títulos substitui a salva;
 *   - EFETIVAR (uma das condições, na hora ou a proposta salva depois, sem refazer a conta): os originais ficam
 *     "renegociado", ligados ao acordo (número próprio, sequencial); nascem as parcelas do acordo, que pagam juros e não
 *     pagam multa. A proposta vencida, o título que não está mais em aberto e o 1º vencimento que já passou são recusados.
 * Tudo numa transação, com os títulos travados; nada é apagado; o número do envio contra o clique duplo.
 */
final class AdmsFinRenegociacao
{
    public const MAX_PARCELAS = 24;
    public const CONDICOES = ['parcelada' => 'Parcelada', 'avista' => 'À vista'];

    private static function falha(string $erro): array
    {
        return ['ok' => false, 'erro' => $erro, 'proposta' => 0, 'renegociacao' => 0];
    }

    /** a × b ÷ d, arredondado (o meio para cima), em inteiros (o % em centavos, nunca em reais). */
    private static function mult(int $a, int $b, int $d): int
    {
        return intdiv(2 * $a * $b + $d, 2 * $d);
    }

    private static function data(string $v): ?string
    {
        $dt = \DateTime::createFromFormat('!Y-m-d', $v);
        return $dt && $dt->format('Y-m-d') === $v ? $v : null;
    }

    /** O mesmo dia, $meses depois de $base (o dia que não existe no mês vira o último dia dele). */
    public static function noMes(string $base, int $meses, int $dia): string
    {
        $b = new \DateTimeImmutable(substr($base, 0, 7) . '-01');
        $m = $b->modify("+{$meses} month");
        $dia = min($dia, (int) $m->format('t'));
        return $m->format('Y-m-') . str_pad((string) $dia, 2, '0', STR_PAD_LEFT);
    }

    /** O valor em R$ ou em % (de $sobre): [centavos, bp|null] ou null se inválido. */
    private static function valorOuPct(string $tipo, string $v, int $sobre): ?array
    {
        if (trim($v) === '') {
            return [0, $tipo === 'pct' ? 0 : null];
        }
        if ($tipo === 'pct') {
            $bp = F::pontos($v);
            if ($bp === null || $bp > 10000) {
                return null;
            }
            return [self::mult($sobre, $bp, 10000), $bp];
        }
        $c = F::centavos($v);
        return $c === null ? null : [$c, null];
    }

    /**
     * A conta da tela e do gravar: os títulos com os encargos na data, o acréscimo, o desconto e o valor a renegociar.
     * @return array{ok: bool, erro: string, conta: array, acrescimo: int, desconto: int, justificativa: string, valor: int, data: string}
     */
    public static function conta(int $alunoId, array $ids, string $data, array $d = [], bool $travar = false): array
    {
        $erro = static fn(string $e) => ['ok' => false, 'erro' => $e];
        if (!($data = self::data($data))) {
            return $erro('Informe a data da renegociação.');
        }
        if ($data > date('Y-m-d')) {
            return $erro('A data da renegociação não pode ser depois de hoje.');
        }
        $c = R::conta($alunoId, $ids, $data, $travar);
        if (!$c['ok']) {
            return $erro(str_replace('nada foi gravado', 'só os títulos em aberto entram na renegociação', $c['erro']));
        }
        $acr = trim((string) ($d['acrescimo'] ?? '')) === '' ? 0 : F::centavos((string) $d['acrescimo']);
        $desc = trim((string) ($d['desconto'] ?? '')) === '' ? 0 : F::centavos((string) $d['desconto']);
        if ($acr === null || $desc === null) {
            return $erro('O acréscimo e o desconto são valores em reais (por exemplo, 15,00), ou vazios.');
        }
        $just = mb_substr(trim((string) ($d['justificativa'] ?? '')), 0, 500);
        if (($acr > 0 || $desc > 0) && mb_strlen($just) < 5) {
            return $erro('Com acréscimo ou desconto, escreva a justificativa (ao menos 5 letras).');
        }
        if ($desc >= $c['devido'] + $acr) {
            return $erro('O desconto tem de ser menor que o total com os encargos (R$ ' . F::brl($c['devido'] + $acr) . ').');
        }
        return ['ok' => true, 'erro' => '', 'conta' => $c, 'acrescimo' => $acr, 'desconto' => $desc, 'justificativa' => $just, 'valor' => $c['devido'] + $acr - $desc, 'data' => $data];
    }

    /**
     * As duas condições sobre o valor a renegociar. $d: p_entrada_tipo (reais|pct), p_entrada, p_entrada_vencimento,
     * p_parcelas, p_dia, p_venc[numero] (o vencimento mudado na tela), v_desconto_tipo, v_desconto, v_vencimento,
     * p_interesse, v_interesse. @return array{ok: bool, erro: string, parcelada: array, avista: array}
     */
    public static function condicoes(int $valor, string $data, array $d): array
    {
        $erro = static fn(string $e) => ['ok' => false, 'erro' => $e];
        /* a parcelada */
        $ent = self::valorOuPct((string) ($d['p_entrada_tipo'] ?? 'reais'), (string) ($d['p_entrada'] ?? ''), $valor);
        if ($ent === null) {
            return $erro('A entrada é um valor em reais (por exemplo, 100,00) ou uma porcentagem de 0 a 100.');
        }
        [$entrada, $entradaBp] = $ent;
        $n = (int) ($d['p_parcelas'] ?? 0);
        $dia = (int) ($d['p_dia'] ?? 0);
        if ($n < 1 || $n > self::MAX_PARCELAS) {
            return $erro('O número de parcelas vai de 1 a ' . self::MAX_PARCELAS . '.');
        }
        if ($dia < 1 || $dia > 28) {
            return $erro('O dia do vencimento das parcelas vai de 1 a 28.');
        }
        if ($entrada < 0 || $entrada >= $valor) {
            return $erro('A entrada tem de ser menor que o valor a renegociar (R$ ' . F::brl($valor) . ').');
        }
        $entVenc = $entrada > 0 ? self::data(trim((string) ($d['p_entrada_vencimento'] ?? ''))) : null;
        if ($entrada > 0 && (!$entVenc || $entVenc < $data)) {
            return $erro('Informe o vencimento da entrada (da data da renegociação em diante).');
        }
        $saldo = $valor - $entrada;
        $vp = intdiv($saldo, $n);
        if ($vp < 1) {
            return $erro('O saldo não dá para ' . $n . ' parcelas.');
        }
        $total = $n + ($entrada > 0 ? 1 : 0);
        $base = $entrada > 0 ? $entVenc : $data;
        $mudados = (array) ($d['p_venc'] ?? []);
        $parc = [];
        if ($entrada > 0) {
            $parc[] = ['numero' => 1, 'total' => $total, 'vencimento' => $entVenc, 'valor' => $entrada, 'entrada' => true];
        }
        $antes = $entVenc ?? $data;
        for ($k = 1; $k <= $n; $k++) {
            $numero = $k + ($entrada > 0 ? 1 : 0);
            $venc = self::noMes($base, $k, $dia);
            $dig = trim((string) ($mudados[$numero] ?? ''));
            if ($dig !== '' && $dig !== $venc) {
                $venc = self::data($dig);
                if (!$venc) {
                    return $erro("O vencimento da parcela {$numero} não é uma data.");
                }
            }
            if ($venc < $antes) {
                return $erro("O vencimento da parcela {$numero} (" . date('d/m/Y', strtotime($venc)) . ') não pode ser antes do da parcela anterior.');
            }
            $antes = $venc;
            $parc[] = ['numero' => $numero, 'total' => $total, 'vencimento' => $venc, 'valor' => $k === $n ? $saldo - $vp * ($n - 1) : $vp, 'entrada' => false]; // a última leva os centavos
        }
        /* a à vista */
        $vd = self::valorOuPct((string) ($d['v_desconto_tipo'] ?? 'reais'), (string) ($d['v_desconto'] ?? ''), $valor);
        if ($vd === null) {
            return $erro('O desconto à vista é um valor em reais ou uma porcentagem de 0 a 100.');
        }
        [$vDesc, $vBp] = $vd;
        if ($vDesc >= $valor) {
            return $erro('O desconto à vista tem de ser menor que o valor a renegociar (R$ ' . F::brl($valor) . ').');
        }
        $vVenc = self::data(trim((string) ($d['v_vencimento'] ?? ''))) ?? $data;
        if ($vVenc < $data) {
            return $erro('O vencimento à vista não pode ser antes da data da renegociação.');
        }
        $marca = static fn($k) => !isset($d[$k]) || (string) $d[$k] === '1' || $d[$k] === 'on';
        return ['ok' => true, 'erro' => '',
            'parcelada' => ['interesse' => $marca('p_interesse'), 'entrada' => $entrada, 'entrada_bp' => $entradaBp, 'entrada_vencimento' => $entVenc, 'n' => $n, 'dia' => $dia,
                'saldo' => $saldo, 'valor_parcela' => $vp, 'total' => $valor, 'parcelas' => $parc],
            'avista' => ['interesse' => $marca('v_interesse'), 'desconto' => $vDesc, 'desconto_bp' => $vBp, 'vencimento' => $vVenc, 'total' => $valor - $vDesc,
                'parcelas' => [['numero' => 1, 'total' => 1, 'vencimento' => $vVenc, 'valor' => $valor - $vDesc, 'entrada' => false]]]];
    }

    /** O motivo da lista (o padrão: o primeiro). @return array{0: ?int, 1: ?string} */
    private static function motivo(string $operacao, $id): array
    {
        $m = R::motivos($operacao);
        $id = (int) $id;
        if (!isset($m[$id])) {
            return [null, null];
        }
        return [$id, $m[$id]];
    }

    private static function jaEnviado(string $envio): ?array
    {
        if ($envio === '') {
            return null;
        }
        $st = F::pdo()->prepare('SELECT id, renegociacao_id FROM fin_propostas WHERE envio = ?');
        $st->execute([$envio]);
        if ($p = $st->fetch(\PDO::FETCH_ASSOC)) {
            return ['ok' => true, 'erro' => '', 'proposta' => (int) $p['id'], 'renegociacao' => (int) $p['renegociacao_id']];
        }
        $st = F::pdo()->prepare("SELECT renegociacao_id FROM fin_operacoes WHERE envio = ? AND tipo = 'renegociar'");
        $st->execute([$envio]);
        $r = $st->fetchColumn();
        return $r === false ? null : ['ok' => true, 'erro' => '', 'proposta' => 0, 'renegociacao' => (int) $r];
    }

    private static function executar(callable $f): array
    {
        $r = null;
        $ok = \Core\Transacao::executar(static function () use ($f, &$r) {
            $r = $f();
            if (!$r['ok']) {
                \Core\Transacao::falhar($r['erro']);
            }
            return true;
        });
        if ($ok === false) {
            return $r && !$r['ok'] ? $r : self::falha('Nada foi gravado: ' . \Core\Transacao::ultimoErro() . '.');
        }
        return $r;
    }

    private static function proximoNumero(): int
    {
        $st = F::pdo()->query("SELECT ultimo FROM fin_sequencias WHERE nome = 'acordo' FOR UPDATE");
        $n = (int) $st->fetchColumn() + 1;
        F::pdo()->prepare("UPDATE fin_sequencias SET ultimo = ? WHERE nome = 'acordo'")->execute([$n]);
        return $n;
    }

    /**
     * POST da tela: acao = salvar | avista | parcelada. Salvar grava a proposta; avista e parcelada efetivam a condição
     * na hora (a proposta fica "formalizada", ligada ao acordo). @return array{ok: bool, erro: string, proposta: int, renegociacao: int}
     */
    public static function gravar(int $alunoId, array $d, int $usuario): array
    {
        $envio = trim((string) ($d['envio'] ?? ''));
        if (($ja = self::jaEnviado($envio)) !== null) {
            return $ja;
        }
        $acao = (string) ($d['acao'] ?? '');
        if (!in_array($acao, ['salvar', 'avista', 'parcelada'], true)) {
            return self::falha('Escolha: salvar a proposta ou efetivar uma das condições.');
        }
        [$motivoId, $motivo] = self::motivo('renegociar', $d['motivo_id'] ?? 0);
        if ($motivoId === null) {
            return self::falha('Escolha o motivo do acordo.');
        }
        $obs = mb_substr(trim((string) ($d['observacao'] ?? '')), 0, 500);
        $hoje = date('Y-m-d');
        $ids = (array) ($d['titulos'] ?? []);
        return self::executar(static function () use ($alunoId, $ids, $d, $acao, $motivoId, $motivo, $obs, $envio, $usuario, $hoje) {
            $k = self::conta($alunoId, $ids, (string) ($d['data'] ?? ''), $d, true);
            if (!$k['ok']) {
                return self::falha($k['erro']);
            }
            /* a conta da tela tem de ser a do servidor (a tela pode ter ficado aberta enquanto o dia, ou um título, mudou) */
            $tela = trim((string) ($d['valor_tela'] ?? ''));
            if ($tela !== '' && (int) $tela !== $k['valor']) {
                return self::falha('A conta mudou desde que a tela foi montada: o valor a renegociar, na conta de ' . date('d/m/Y', strtotime($k['data'])) . ', é R$ ' . F::brl($k['valor']) . '. Confira e gere a proposta de novo. Nada foi gravado.');
            }
            $cond = self::condicoes($k['valor'], $k['data'], $d);
            if (!$cond['ok']) {
                return self::falha($cond['erro']);
            }
            if ($acao === 'salvar' && $k['data'] < $hoje) {
                return self::falha('A proposta vale até a data da renegociação: com uma data passada, ela já nasceria vencida. Use a data de hoje para salvar. Nada foi gravado.');
            }
            if ($acao !== 'salvar' && $cond[$acao]['parcelas'][0]['vencimento'] < $hoje) {
                return self::falha('O primeiro vencimento da condição ' . mb_strtolower(self::CONDICOES[$acao]) . ' (' . date('d/m/Y', strtotime($cond[$acao]['parcelas'][0]['vencimento'])) . ') já passou: escolha um de hoje em diante.');
            }
            $prop = self::gravarProposta($alunoId, $k, $cond, $motivoId, $motivo, $obs, $acao === 'salvar' ? 'salva' : 'formalizada', $acao === 'salvar' ? ($envio ?: null) : null, $usuario);
            if ($acao === 'salvar') {
                F::registrar(['aluno_id' => $alunoId, 'tipo' => 'proposta', 'data_operacao' => $k['data'], 'original_centavos' => $k['conta']['original'],
                    'multa_centavos' => $k['conta']['multa'], 'juros_centavos' => $k['conta']['juros'], 'desconto_centavos' => $k['desconto'],
                    'observacao' => mb_substr('Proposta nº ' . $prop . ' salva: R$ ' . F::brl($k['valor']) . ' a renegociar (' . \App\adms\Models\helper\AdmsUi::plural(count($k['conta']['titulos']), 'título', 'títulos')
                        . '), válida até ' . date('d/m/Y', strtotime($k['data'])) . ' · ' . $motivo, 0, 500), 'envio' => $envio ?: null, 'usuario_id' => $usuario]);
                return ['ok' => true, 'erro' => '', 'proposta' => $prop, 'renegociacao' => 0];
            }
            $titulos = array_map(static fn($t) => ['t' => $t, 'e' => $t['e']], $k['conta']['titulos']);
            $ren = self::efetivar($alunoId, (int) $k['conta']['ano'], $titulos, $acao, $cond[$acao], $k['conta']['devido'] + $k['acrescimo'], $k['desconto'], $prop, $k['data'], $envio, $usuario);
            if (!$ren['ok']) {
                return $ren;
            }
            return ['ok' => true, 'erro' => '', 'proposta' => $prop, 'renegociacao' => $ren['renegociacao']];
        });
    }

    /** A proposta e as linhas dela; as salvas do aluno com algum dos mesmos títulos ficam substituídas. */
    private static function gravarProposta(int $alunoId, array $k, array $cond, int $motivoId, string $motivo, string $obs, string $situacao, ?string $envio, int $usuario): int
    {
        $c = $k['conta'];
        $p = $cond['parcelada'];
        $v = $cond['avista'];
        F::pdo()->prepare('INSERT INTO fin_propostas (aluno_id, ano, data_renegociacao, original_centavos, juros_centavos, multa_centavos, acrescimo_centavos, desconto_centavos, justificativa,
            total_centavos, p_interesse, p_entrada_centavos, p_entrada_bp, p_entrada_vencimento, p_parcelas, p_dia, p_total_centavos, v_interesse, v_desconto_centavos, v_desconto_bp,
            v_vencimento, v_total_centavos, motivo_id, motivo, observacao, situacao, envio, criado_por, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())')
            ->execute([$alunoId, $c['ano'], $k['data'], $c['original'], $c['juros'], $c['multa'] - $c['pontualidade'], $k['acrescimo'], $k['desconto'], $k['justificativa'] ?: null,
                $k['valor'], $p['interesse'] ? 1 : 0, $p['entrada'], $p['entrada_bp'], $p['entrada_vencimento'], $p['n'], $p['dia'], $p['total'], $v['interesse'] ? 1 : 0,
                $v['desconto'], $v['desconto_bp'], $v['vencimento'], $v['total'], $motivoId, $motivo, $obs ?: null, $situacao, $envio, $usuario]);
        $prop = (int) F::pdo()->lastInsertId();
        $lt = F::pdo()->prepare('INSERT INTO fin_proposta_titulos (proposta_id, titulo_id, original_centavos, juros_centavos, multa_centavos, total_centavos) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($c['titulos'] as $t) {
            $lt->execute([$prop, (int) $t['id'], $t['e']['original'], $t['e']['juros'], $t['e']['multa'] - $t['e']['desconto'], $t['e']['total']]);
        }
        $lp = F::pdo()->prepare('INSERT INTO fin_proposta_parcelas (proposta_id, condicao, numero, total, vencimento, valor_centavos) VALUES (?, ?, ?, ?, ?, ?)');
        foreach (['parcelada', 'avista'] as $q) {
            foreach ($cond[$q]['parcelas'] as $x) {
                $lp->execute([$prop, $q, $x['numero'], $x['total'], $x['vencimento'], $x['valor']]);
            }
        }
        /* a proposta nova dos mesmos títulos substitui a salva */
        $ids = implode(',', array_map(static fn($t) => (int) $t['id'], $c['titulos']));
        F::pdo()->prepare("UPDATE fin_propostas SET situacao = 'substituida', substituida_por = ? WHERE aluno_id = ? AND situacao = 'salva' AND id <> ?
            AND id IN (SELECT proposta_id FROM fin_proposta_titulos WHERE titulo_id IN ({$ids}))")->execute([$prop, $alunoId, $prop]);
        return $prop;
    }

    /**
     * O acordo: os originais "renegociado" (ligados a ele), as parcelas novas (sem multa) e o histórico. $titulos: [['t' =>
     * o título travado, 'e' => a conta dele]]; $total: os títulos com os encargos e o acréscimo; $desconto: o do alto.
     */
    private static function efetivar(int $alunoId, int $ano, array $titulos, string $condicao, array $cond, int $total, int $desconto, int $proposta, string $data, string $envio, int $usuario): array
    {
        $numero = self::proximoNumero();
        $parc = $cond['parcelas'];
        $descTotal = $desconto + ($condicao === 'avista' ? $cond['desconto'] : 0);
        F::pdo()->prepare("INSERT INTO fin_renegociacoes (numero, proposta_id, condicao, aluno_id, total_centavos, desconto_centavos, entrada_centavos, parcelas, primeiro_vencimento, situacao, criado_por, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'vigente', ?, NOW())")
            ->execute([$numero, $proposta ?: null, $condicao, $alunoId, $total, $descTotal, $condicao === 'parcelada' ? $cond['entrada'] : 0, count($parc), $parc[0]['vencimento'], $usuario]);
        $ren = (int) F::pdo()->lastInsertId();
        $primeiraOp = 0;
        foreach ($titulos as ['t' => $t, 'e' => $e]) {
            $st = F::pdo()->prepare("UPDATE fin_titulos SET situacao = 'renegociado', renegociado_por = ? WHERE id = ? AND situacao = 'aberto'");
            $st->execute([$ren, (int) $t['id']]);
            if ($st->rowCount() !== 1) {
                return self::falha('Outra pessoa acabou de mudar o título ' . $t['id'] . ': nada foi gravado.');
            }
            $op = F::registrar(['titulo_id' => (int) $t['id'], 'aluno_id' => $alunoId, 'tipo' => 'renegociar', 'data_operacao' => $data, 'original_centavos' => (int) $e['original'],
                'multa_centavos' => (int) $e['multa'], 'juros_centavos' => (int) $e['juros'], 'situacao_antes' => 'aberto', 'situacao_depois' => 'renegociado', 'renegociacao_id' => $ren,
                'observacao' => "Acordo nº {$numero} (" . mb_strtolower(self::CONDICOES[$condicao]) . ')', 'envio' => $primeiraOp ? null : ($envio ?: null), 'usuario_id' => $usuario]);
            $primeiraOp = $primeiraOp ?: $op;
        }
        $primeiro = $titulos[0]['t']['descricao'];
        $nome = mb_substr(($condicao === 'avista' ? 'Acordo à Vista de ' : 'Acordo Parcelado de ') . $primeiro, 0, 140) . " - n. {$numero}";
        $ins = F::pdo()->prepare("INSERT INTO fin_titulos (aluno_id, ano, descricao, valor_centavos, vencimento, situacao, renegociacao_id, sem_multa, parcela, parcelas_total, criado_por, criado_em)
            VALUES (?, ?, ?, ?, ?, 'aberto', ?, 1, ?, ?, ?, NOW())");
        foreach ($parc as $x) {
            $ins->execute([$alunoId, $ano, $nome, $x['valor'], $x['vencimento'], $ren, $x['numero'], $x['total'], $usuario]);
            F::registrar(['titulo_id' => (int) F::pdo()->lastInsertId(), 'aluno_id' => $alunoId, 'tipo' => 'gerar', 'original_centavos' => $x['valor'], 'situacao_depois' => 'aberto',
                'renegociacao_id' => $ren, 'observacao' => "Acordo nº {$numero}" . ($x['entrada'] ? ' (a entrada)' : ''), 'usuario_id' => $usuario]);
        }
        if ($proposta) {
            F::pdo()->prepare("UPDATE fin_propostas SET situacao = 'formalizada', renegociacao_id = ? WHERE id = ?")->execute([$ren, $proposta]);
        }
        return ['ok' => true, 'erro' => '', 'proposta' => $proposta, 'renegociacao' => $ren];
    }

    /**
     * EFETIVAR ACORDO: a proposta salva, numa das condições, sem refazer a conta (os valores são os do dia dela). Recusa a
     * proposta que não está salva, a vencida (a data dela passou), o título que não está mais em aberto e o 1º vencimento
     * que já passou.
     */
    public static function efetivarProposta(int $propostaId, array $d, int $usuario): array
    {
        $envio = trim((string) ($d['envio'] ?? ''));
        if (($ja = self::jaEnviado($envio)) !== null) {
            return $ja;
        }
        $condicao = (string) ($d['condicao'] ?? '');
        if (!isset(self::CONDICOES[$condicao])) {
            return self::falha('Escolha a condição: parcelada ou à vista.');
        }
        $hoje = date('Y-m-d');
        return self::executar(static function () use ($propostaId, $condicao, $envio, $usuario, $hoje) {
            $st = F::pdo()->prepare('SELECT * FROM fin_propostas WHERE id = ? FOR UPDATE');
            $st->execute([$propostaId]);
            $p = $st->fetch(\PDO::FETCH_ASSOC);
            if (!$p) {
                return self::falha('Proposta não encontrada.');
            }
            if ($p['situacao'] !== 'salva') {
                return self::falha('A proposta nº ' . $p['id'] . ' está ' . self::rotuloProposta($p, $hoje) . ': só a proposta salva e dentro da validade pode ser efetivada.');
            }
            if ($p['data_renegociacao'] < $hoje) {
                return self::falha('A proposta nº ' . $p['id'] . ' venceu em ' . date('d/m/Y', strtotime($p['data_renegociacao'])) . ' (vale até a data da renegociação): gere uma proposta nova, com a conta de hoje.');
            }
            $st = F::pdo()->prepare('SELECT * FROM fin_proposta_parcelas WHERE proposta_id = ? AND condicao = ? ORDER BY numero');
            $st->execute([$propostaId, $condicao]);
            $parc = $st->fetchAll(\PDO::FETCH_ASSOC);
            if (!$parc) {
                return self::falha('A proposta não tem a condição ' . mb_strtolower(self::CONDICOES[$condicao]) . '.');
            }
            if ($parc[0]['vencimento'] < $hoje) {
                return self::falha('O primeiro vencimento da proposta (' . date('d/m/Y', strtotime($parc[0]['vencimento'])) . ') já passou: gere uma proposta nova.');
            }
            $st = F::pdo()->prepare('SELECT l.*, t.id AS tid FROM fin_proposta_titulos l JOIN fin_titulos t ON t.id = l.titulo_id WHERE l.proposta_id = ? ORDER BY t.id');
            $st->execute([$propostaId]);
            $titulos = [];
            $trava = F::pdo()->prepare('SELECT * FROM fin_titulos WHERE id = ? FOR UPDATE');
            foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $l) {
                $trava->execute([(int) $l['titulo_id']]);
                $t = $trava->fetch(\PDO::FETCH_ASSOC);
                if (!$t || $t['situacao'] !== 'aberto') {
                    return self::falha('O título ' . $l['titulo_id'] . ($t ? ' (' . $t['descricao'] . ') está ' . mb_strtolower(F::rotulo($t['situacao'])) : '') . ': a proposta não pode mais ser efetivada.');
                }
                $titulos[] = ['t' => $t, 'e' => ['original' => (int) $l['original_centavos'], 'juros' => (int) $l['juros_centavos'], 'multa' => (int) $l['multa_centavos']]];
            }
            $cond = $condicao === 'avista'
                ? ['desconto' => (int) $p['v_desconto_centavos']]
                : ['entrada' => (int) $p['p_entrada_centavos']];
            $cond['parcelas'] = array_map(static fn($x) => ['numero' => (int) $x['numero'], 'total' => (int) $x['total'], 'vencimento' => (string) $x['vencimento'], 'valor' => (int) $x['valor_centavos'],
                'entrada' => $condicao === 'parcelada' && (int) $p['p_entrada_centavos'] > 0 && (int) $x['numero'] === 1], $parc);
            $total = (int) $p['total_centavos'] + (int) $p['desconto_centavos'];
            return self::efetivar((int) $p['aluno_id'], (int) $p['ano'], $titulos, $condicao, $cond, $total, (int) $p['desconto_centavos'], (int) $p['id'], $hoje, $envio, $usuario);
        });
    }

    /** A situação da proposta (a vencida é a salva com a data passada). */
    public static function situacaoProposta(array $p, ?string $hoje = null): string
    {
        if ($p['situacao'] === 'salva') {
            return (string) $p['data_renegociacao'] < ($hoje ?? date('Y-m-d')) ? 'salva_vencida' : 'salva';
        }
        return (string) $p['situacao'];
    }

    public const ROTULOS = ['efetivado' => 'Efetivado', 'desfeito' => 'Desfeito', 'salva' => 'Salvo', 'salva_vencida' => 'Salvo vencido', 'substituida' => 'Substituído', 'formalizada' => 'Formalizado'];

    private static function rotuloProposta(array $p, string $hoje): string
    {
        return mb_strtolower(self::ROTULOS[self::situacaoProposta($p, $hoje)]);
    }

    /**
     * A aba Renegociações: os acordos (efetivado, desfeito) e as propostas que não viraram acordo (salvo, salvo vencido,
     * substituído), o mais novo primeiro. Cada linha: tipo, id, numero, situacao, descricao, total, validade, data, usuario.
     */
    public static function lista(int $alunoId): array
    {
        $hoje = date('Y-m-d');
        $out = [];
        $st = F::pdo()->prepare('SELECT r.*, u.nome AS usuario, p.data_renegociacao FROM fin_renegociacoes r LEFT JOIN adms_usuarios u ON u.id = r.criado_por
            LEFT JOIN fin_propostas p ON p.id = r.proposta_id WHERE r.aluno_id = ? ORDER BY r.id DESC');
        $st->execute([$alunoId]);
        foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            /* os originais pelo histórico do acordo (o título renegociado de novo, depois de um desfazer, aponta para o novo) */
            $orig = F::pdo()->prepare("SELECT titulo_id AS id FROM fin_operacoes WHERE tipo = 'renegociar' AND renegociacao_id = ? ORDER BY titulo_id");
            $orig->execute([(int) $r['id']]);
            $os = $orig->fetchAll(\PDO::FETCH_ASSOC);
            $out[] = ['tipo' => 'acordo', 'id' => (int) $r['id'], 'numero' => (int) ($r['numero'] ?: $r['id']), 'situacao' => $r['situacao'] === 'desfeita' ? 'desfeito' : 'efetivado',
                'condicao' => $r['condicao'], 'descricao' => ($r['condicao'] ? self::CONDICOES[$r['condicao']] . ': ' : '') . $r['parcelas'] . ($r['parcelas'] > 1 ? ' parcelas' : ' parcela')
                    . ' a partir de ' . date('d/m/Y', strtotime($r['primeiro_vencimento'])) . ' · títulos ' . implode(', ', array_column($os, 'id')),
                'total' => (int) $r['total_centavos'] - (int) $r['desconto_centavos'], 'validade' => null, 'data' => $r['data_renegociacao'] ?: substr((string) $r['criado_em'], 0, 10),
                'criado_em' => $r['criado_em'], 'usuario' => $r['usuario'], 'motivo_desfazer' => $r['motivo_desfazer'], 'proposta_id' => $r['proposta_id'] ? (int) $r['proposta_id'] : null];
        }
        $st = F::pdo()->prepare("SELECT p.*, u.nome AS usuario FROM fin_propostas p LEFT JOIN adms_usuarios u ON u.id = p.criado_por WHERE p.aluno_id = ? AND p.situacao <> 'formalizada' ORDER BY p.id DESC");
        $st->execute([$alunoId]);
        foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $p) {
            $t = F::pdo()->prepare('SELECT titulo_id FROM fin_proposta_titulos WHERE proposta_id = ? ORDER BY titulo_id');
            $t->execute([(int) $p['id']]);
            $out[] = ['tipo' => 'proposta', 'id' => (int) $p['id'], 'numero' => (int) $p['id'], 'situacao' => self::situacaoProposta($p, $hoje), 'condicao' => null,
                'descricao' => 'Proposta: parcelada (' . ((int) $p['p_entrada_centavos'] > 0 ? 'entrada de R$ ' . F::brl((int) $p['p_entrada_centavos']) . ' + ' : '') . $p['p_parcelas'] . 'x) ou à vista (R$ '
                    . F::brl((int) $p['v_total_centavos']) . ') · títulos ' . implode(', ', $t->fetchAll(\PDO::FETCH_COLUMN)),
                'total' => (int) $p['total_centavos'], 'validade' => $p['data_renegociacao'], 'data' => $p['data_renegociacao'], 'criado_em' => $p['criado_em'], 'usuario' => $p['usuario'],
                'motivo_desfazer' => null, 'proposta_id' => (int) $p['id']];
        }
        usort($out, static fn($a, $b) => strcmp((string) $b['criado_em'], (string) $a['criado_em']) ?: $b['id'] <=> $a['id']);
        return $out;
    }

    /** A proposta inteira (para o impresso e para a tela de efetivar). */
    public static function proposta(int $id): ?array
    {
        $st = F::pdo()->prepare('SELECT * FROM fin_propostas WHERE id = ?');
        $st->execute([$id]);
        $p = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$p) {
            return null;
        }
        $st = F::pdo()->prepare('SELECT l.*, t.descricao, t.vencimento, t.parcela, t.parcelas_total FROM fin_proposta_titulos l JOIN fin_titulos t ON t.id = l.titulo_id WHERE l.proposta_id = ? ORDER BY t.id');
        $st->execute([$id]);
        $p['titulos'] = $st->fetchAll(\PDO::FETCH_ASSOC);
        $st = F::pdo()->prepare('SELECT * FROM fin_proposta_parcelas WHERE proposta_id = ? ORDER BY condicao, numero');
        $st->execute([$id]);
        $p['parcelas'] = ['parcelada' => [], 'avista' => []];
        foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $x) {
            $p['parcelas'][$x['condicao']][] = $x;
        }
        $p['mostrada'] = self::situacaoProposta($p);
        return $p;
    }

    /** O acordo inteiro: os originais (com a conta), as parcelas novas, a proposta (se houve). */
    public static function acordo(int $id): ?array
    {
        $st = F::pdo()->prepare('SELECT r.*, u.nome AS usuario FROM fin_renegociacoes r LEFT JOIN adms_usuarios u ON u.id = r.criado_por WHERE r.id = ?');
        $st->execute([$id]);
        $r = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$r) {
            return null;
        }
        $st = F::pdo()->prepare("SELECT t.id, t.descricao, t.vencimento, o.original_centavos, o.juros_centavos, o.multa_centavos FROM fin_operacoes o
            JOIN fin_titulos t ON t.id = o.titulo_id WHERE o.tipo = 'renegociar' AND o.renegociacao_id = ? ORDER BY t.id");
        $st->execute([$id]);
        $r['originais'] = $st->fetchAll(\PDO::FETCH_ASSOC);
        $st = F::pdo()->prepare('SELECT id, descricao, valor_centavos, vencimento, situacao, parcela, parcelas_total FROM fin_titulos WHERE renegociacao_id = ? ORDER BY parcela, id');
        $st->execute([$id]);
        $r['parcelas'] = $st->fetchAll(\PDO::FETCH_ASSOC);
        $r['proposta'] = $r['proposta_id'] ? self::proposta((int) $r['proposta_id']) : null;
        $r['numero'] = (int) ($r['numero'] ?: $r['id']);
        return $r;
    }

    /** "01.03": o número da parcela do acordo e o total (a entrada é a 01), como no sistema de origem. */
    public static function rotuloParcela(int $numero, int $total): string
    {
        return str_pad((string) $numero, 2, '0', STR_PAD_LEFT) . '.' . str_pad((string) $total, 2, '0', STR_PAD_LEFT);
    }
}
