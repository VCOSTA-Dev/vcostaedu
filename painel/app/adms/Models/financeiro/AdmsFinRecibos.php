<?php

namespace App\adms\Models\financeiro;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 5) . '/core/ReciboFinanceiro.php'; // o recibo, o mesmo no painel e no portal (D-99)

use App\adms\Models\financeiro\AdmsFinanceiro as F;

/**
 * O RECEBER E O RECIBO (Onda 8, financeiro refeito, Parte 1; D-99). Como no sistema de origem, um recebimento é um recibo:
 * um ou vários títulos do mesmo aluno e do mesmo ano letivo, a data (padrão hoje; pode ser antes), o acréscimo e o desconto
 * digitados (a justificativa é obrigatória com algum deles; o acerto é rateado entre os títulos pelo devido de cada um,
 * fechando ao centavo) e as formas de pagamento (uma, ou de 2 a 6 no "Múltiplos", que só grava quando a soma fecha ao
 * centavo). Diferente de lá (os defeitos do levantamento): tudo numa transação, os títulos travados e conferidos em aberto,
 * os encargos recalculados aqui pela data (a tela só mostra a conta), e o número do envio contra o clique duplo.
 *
 * O desfazer (estornar) é do recibo inteiro: a data, o motivo (da lista) e a justificativa; nada é apagado — o recibo, as
 * linhas e as formas ficam "estornado" e os títulos voltam a aberto. O estorno conta no caixa do dia dele.
 */
final class AdmsFinRecibos
{
    public const MAX_FORMAS = 6;

    private static function falha(string $erro): array
    {
        return ['ok' => false, 'erro' => $erro, 'recibo' => 0];
    }

    /** O próximo número do recibo, travado na transação (sem buraco: a transação desfeita não gasta número). */
    private static function proximoNumero(): int
    {
        $st = F::pdo()->query("SELECT ultimo FROM fin_sequencias WHERE nome = 'recibo' FOR UPDATE");
        $n = (int) $st->fetchColumn() + 1;
        F::pdo()->prepare("UPDATE fin_sequencias SET ultimo = ? WHERE nome = 'recibo'")->execute([$n]);
        return $n;
    }

    /**
     * De quem se recebe: na escola, o responsável financeiro do aluno no ano (a designação em vigor); no ensino superior,
     * ou sem designação, o próprio aluno. @return array{nome: string, responsavel_id: ?int, aluno: bool}
     */
    public static function pagador(int $alunoId, ?int $ano): array
    {
        if ($ano && \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola()) {
            $st = F::pdo()->prepare("SELECT r.id, r.nome FROM esc_responsavel_designacoes d JOIN esc_responsaveis r ON r.id = d.responsavel_id
                WHERE d.aluno_id = ? AND d.ano = ? AND d.papel = 'financeiro' AND d.fim IS NULL ORDER BY d.id DESC LIMIT 1");
            $st->execute([$alunoId, $ano]);
            if ($r = $st->fetch(\PDO::FETCH_ASSOC)) {
                return ['nome' => (string) $r['nome'], 'responsavel_id' => (int) $r['id'], 'aluno' => false];
            }
        }
        $st = F::pdo()->prepare('SELECT COALESCE(NULLIF(nome_social, \'\'), nome) FROM col_usuarios WHERE id = ?');
        $st->execute([$alunoId]);
        return ['nome' => (string) $st->fetchColumn(), 'responsavel_id' => null, 'aluno' => true];
    }

    /**
     * O rateio: $valor dividido entre as partes na proporção de $pesos, em centavos, somando exatamente $valor (o maior
     * resto leva o centavo; no empate, a primeira parte). @param list<int> $pesos @return list<int>
     */
    public static function ratear(array $pesos, int $valor): array
    {
        $soma = array_sum($pesos);
        $n = count($pesos);
        if ($n === 0) {
            return [];
        }
        if ($valor === 0 || $soma <= 0) {
            return array_fill(0, $n, 0);
        }
        $partes = [];
        $restos = [];
        foreach ($pesos as $i => $p) {
            $partes[$i] = intdiv($valor * $p, $soma);
            $restos[$i] = ($valor * $p) % $soma;
        }
        $falta = $valor - array_sum($partes);
        $ordem = array_keys($restos);
        usort($ordem, static fn($a, $b) => $restos[$b] <=> $restos[$a] ?: $a <=> $b);
        for ($k = 0; $k < $falta; $k++) {
            $partes[$ordem[$k % $n]]++;
        }
        return array_values($partes);
    }

    /** Uma data AAAA-MM-DD válida, ou null. */
    private static function data(string $v): ?string
    {
        $dt = \DateTime::createFromFormat('!Y-m-d', $v);
        return $dt && $dt->format('Y-m-d') === $v ? $v : null;
    }

    /**
     * Os títulos escolhidos, com os encargos na data: a conta da tela e a do gravar (o servidor refaz sempre).
     * @return array{ok: bool, erro: string, titulos: list<array>, original: int, multa: int, juros: int, pontualidade: int, devido: int, ano: ?int}
     */
    public static function conta(int $alunoId, array $ids, string $data, bool $travar = false): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        sort($ids); // a ordem de travar é sempre a mesma (sem impasse entre duas pessoas)
        $r = ['ok' => false, 'erro' => '', 'titulos' => [], 'original' => 0, 'multa' => 0, 'juros' => 0, 'pontualidade' => 0, 'devido' => 0, 'ano' => null, 'anos' => []];
        if (!$ids) {
            $r['erro'] = 'Escolha ao menos um título em aberto.';
            return $r;
        }
        $st = F::pdo()->prepare('SELECT * FROM fin_titulos WHERE id = ?' . ($travar ? ' FOR UPDATE' : ''));
        $anos = [];
        foreach ($ids as $id) {
            $st->execute([$id]);
            $t = $st->fetch(\PDO::FETCH_ASSOC);
            if (!$t || (int) $t['aluno_id'] !== $alunoId) {
                $r['erro'] = 'Um dos títulos não é deste aluno.';
                return $r;
            }
            if ($t['situacao'] !== 'aberto') {
                $r['erro'] = 'O título ' . $t['id'] . ' (' . $t['descricao'] . ') já está ' . mb_strtolower(F::rotulo($t['situacao'])) . ': nada foi gravado.';
                return $r;
            }
            $e = F::encargosTitulo($t, $data);
            $t['e'] = $e;
            $r['titulos'][] = $t;
            $r['original'] += $e['original'];
            $r['multa'] += $e['multa'];
            $r['juros'] += $e['juros'];
            $r['pontualidade'] += $e['desconto'];
            $r['devido'] += $e['total'];
            $anos[(int) $t['ano']] = true;
        }
        /* o ano do recibo (e o de quem se recebe), D-99: com todos os títulos do mesmo ano, aquele ano; com títulos de anos
           diferentes, o ano letivo corrente do aluno (o que a ficha abre), e a tela avisa */
        ksort($anos);
        $r['anos'] = array_keys($anos);
        $r['ano'] = count($anos) === 1 ? (int) array_key_first($anos) : AdmsFinFicha::anoPadrao(AdmsFinFicha::anos($alunoId));
        $r['ok'] = true;
        return $r;
    }

    /**
     * As formas enviadas, conferidas: cada uma com os dados que ela pede. $total é o valor a receber (o do servidor).
     * @return array{ok: bool, erro: string, formas: list<array>}
     */
    public static function formas(array $d, int $total, string $dataRecebimento): array
    {
        $modo = (string) ($d['modo'] ?? 'unica');
        $linhas = array_values(array_filter((array) ($d['formas'] ?? []), 'is_array'));
        $erro = static fn(string $e) => ['ok' => false, 'erro' => $e, 'formas' => []];
        if ($modo === 'multiplo') {
            if (count($linhas) < 2 || count($linhas) > self::MAX_FORMAS) {
                return $erro('No pagamento em mais de uma forma, use de 2 a ' . self::MAX_FORMAS . ' formas.');
            }
        } else {
            $linhas = array_slice($linhas, 0, 1);
            if (!$linhas) {
                return $erro('Escolha a forma de pagamento.');
            }
            if (trim((string) ($linhas[0]['valor'] ?? '')) === '') {
                $linhas[0]['valor'] = F::brl($total); // uma forma só: o valor é o total
            }
        }
        $bandeiras = array_column(F::pdo()->query('SELECT id, nome FROM fin_bandeiras WHERE situacao = 1')->fetchAll(\PDO::FETCH_ASSOC), 'nome', 'id');
        $saida = [];
        $soma = 0;
        foreach ($linhas as $i => $l) {
            $n = $i + 1;
            $q = $modo === 'multiplo' ? " (forma {$n})" : '';
            $forma = (string) ($l['forma'] ?? '');
            if (!in_array($forma, F::FORMAS_DO_RECEBER, true)) {
                return $erro("Escolha a forma de pagamento{$q}.");
            }
            $valor = F::centavos((string) ($l['valor'] ?? ''));
            if ($valor === null || $valor < 1) {
                return $erro("Informe o valor{$q}.");
            }
            $f = ['ordem' => $n, 'forma' => $forma, 'valor_centavos' => $valor, 'pagador_documento' => null, 'cartao_tipo' => null, 'cartao_parcelas' => null, 'bandeira_id' => null,
                'cartao_final' => null, 'cartao_documento' => null, 'cartao_autorizacao' => null];
            if ($forma === 'pix') {
                $doc = preg_replace('/\D+/', '', (string) ($l['documento'] ?? ''));
                if (!F::documentoValido($doc)) {
                    return $erro("No Pix, informe o CPF ou o CNPJ de quem pagou{$q}.");
                }
                $f['pagador_documento'] = $doc;
            } elseif ($forma === 'cartao') {
                $tipo = (string) ($l['cartao_tipo'] ?? '');
                $parc = (int) ($l['cartao_parcelas'] ?? 1);
                $band = (int) ($l['bandeira_id'] ?? 0);
                $final = preg_replace('/\D+/', '', (string) ($l['cartao_final'] ?? ''));
                $docC = trim((string) ($l['cartao_documento'] ?? ''));
                $aut = trim((string) ($l['cartao_autorizacao'] ?? ''));
                if (!in_array($tipo, ['credito', 'debito'], true)) {
                    return $erro("No cartão, escolha crédito ou débito{$q}.");
                }
                if ($tipo === 'debito') {
                    $parc = 1;
                }
                if ($parc < 1 || $parc > 12) {
                    return $erro("No cartão, as parcelas vão de 1 a 12{$q}.");
                }
                if (!isset($bandeiras[$band])) {
                    return $erro("No cartão, escolha a bandeira{$q}.");
                }
                if (strlen($final) !== 4) {
                    return $erro("No cartão, informe os 4 últimos números{$q}.");
                }
                if ($docC === '' || mb_strlen($docC) > 40 || $aut === '' || mb_strlen($aut) > 40) {
                    return $erro("No cartão, informe o número do documento e o da autorização (do comprovante da máquina){$q}.");
                }
                $f = array_merge($f, ['cartao_tipo' => $tipo, 'cartao_parcelas' => $parc, 'bandeira_id' => $band, 'cartao_final' => $final, 'cartao_documento' => $docC, 'cartao_autorizacao' => $aut]);
            }
            $saida[] = $f;
            $soma += $valor;
        }
        if ($soma !== $total) {
            return $erro($soma < $total
                ? 'As formas somam R$ ' . F::brl($soma) . ': falta R$ ' . F::brl($total - $soma) . ' para fechar o valor a receber (R$ ' . F::brl($total) . '). Nada foi gravado.'
                : 'As formas somam R$ ' . F::brl($soma) . ': passou R$ ' . F::brl($soma - $total) . ' do valor a receber (R$ ' . F::brl($total) . '). Nada foi gravado.');
        }
        return ['ok' => true, 'erro' => '', 'formas' => $saida];
    }

    /** O recibo de um envio já gravado (o clique duplo devolve o mesmo recibo). */
    private static function doEnvio(string $envio): ?int
    {
        if ($envio === '') {
            return null;
        }
        $st = F::pdo()->prepare('SELECT id FROM fin_recibos WHERE envio = ?');
        $st->execute([$envio]);
        $id = $st->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /**
     * RECEBER: grava o recibo. $d: titulos[], data, acrescimo, desconto, justificativa, observacao, modo (unica|multiplo),
     * formas[] e envio. @return array{ok: bool, erro: string, recibo: int}
     */
    public static function receber(int $alunoId, array $d, int $usuario): array
    {
        $envio = trim((string) ($d['envio'] ?? ''));
        if (($ja = self::doEnvio($envio)) !== null) {
            return ['ok' => true, 'erro' => '', 'recibo' => $ja];
        }
        $data = self::data(trim((string) ($d['data'] ?? '')));
        if (!$data) {
            return self::falha('Informe a data do recebimento.');
        }
        if ($data > date('Y-m-d')) {
            return self::falha('A data do recebimento não pode ser depois de hoje.');
        }
        $acr = trim((string) ($d['acrescimo'] ?? '')) === '' ? 0 : F::centavos((string) $d['acrescimo']);
        $desc = trim((string) ($d['desconto'] ?? '')) === '' ? 0 : F::centavos((string) $d['desconto']);
        if ($acr === null || $desc === null) {
            return self::falha('O acréscimo e o desconto são valores em reais (por exemplo, 15,00), ou vazios.');
        }
        $just = mb_substr(trim((string) ($d['justificativa'] ?? '')), 0, 500);
        if (($acr > 0 || $desc > 0) && mb_strlen($just) < 5) {
            return self::falha('Com acréscimo ou desconto, escreva a justificativa (ao menos 5 letras).');
        }
        $obs = mb_substr(trim((string) ($d['observacao'] ?? '')), 0, 500);
        $ids = (array) ($d['titulos'] ?? []);
        $r = null;
        $ok = \Core\Transacao::executar(static function () use ($alunoId, $ids, $data, $acr, $desc, $just, $obs, $d, $envio, $usuario, &$r) {
            $c = self::conta($alunoId, $ids, $data, true);
            if (!$c['ok']) {
                $r = self::falha($c['erro']);
                \Core\Transacao::falhar($c['erro']);
            }
            if ($desc >= $c['devido'] + $acr) {
                $r = self::falha('O desconto tem de ser menor que o total com os encargos (R$ ' . F::brl($c['devido'] + $acr) . ').');
                \Core\Transacao::falhar($r['erro']);
            }
            $total = $c['devido'] + $acr - $desc;
            $fm = self::formas($d, $total, $data);
            if (!$fm['ok']) {
                $r = self::falha($fm['erro']);
                \Core\Transacao::falhar($fm['erro']);
            }
            $pesos = array_map(static fn($t) => $t['e']['total'], $c['titulos']);
            $acrs = self::ratear($pesos, $acr);
            $descs = self::ratear($pesos, $desc);
            $pag = self::pagador($alunoId, $c['ano']);
            $numero = self::proximoNumero();
            F::pdo()->prepare('INSERT INTO fin_recibos (numero, aluno_id, ano, pagador_nome, pagador_responsavel_id, data_recebimento, original_centavos, multa_centavos, juros_centavos,
                pontualidade_centavos, acrescimo_centavos, desconto_centavos, total_centavos, justificativa, observacao, situacao, usuario_id, envio, criado_em)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'valido\', ?, ?, NOW())')
                ->execute([$numero, $alunoId, $c['ano'], $pag['nome'], $pag['responsavel_id'], $data, $c['original'], $c['multa'], $c['juros'], $c['pontualidade'], $acr, $desc, $total,
                    $just ?: null, $obs ?: null, $usuario, $envio ?: null]);
            $recibo = (int) F::pdo()->lastInsertId();
            $formaOp = count($fm['formas']) === 1 ? $fm['formas'][0]['forma'] : 'multiplo';
            $linha = F::pdo()->prepare('INSERT INTO fin_recibo_titulos (recibo_id, titulo_id, original_centavos, multa_centavos, juros_centavos, pontualidade_centavos, acrescimo_centavos,
                desconto_centavos, recebido_centavos, situacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'valido\')');
            foreach ($c['titulos'] as $i => $t) {
                $e = $t['e'];
                $recebido = $e['total'] + $acrs[$i] - $descs[$i];
                $linha->execute([$recibo, (int) $t['id'], $e['original'], $e['multa'], $e['juros'], $e['desconto'], $acrs[$i], $descs[$i], $recebido]);
                $st = F::pdo()->prepare("UPDATE fin_titulos SET situacao = 'recebido' WHERE id = ? AND situacao = 'aberto'");
                $st->execute([(int) $t['id']]);
                if ($st->rowCount() !== 1) {
                    $r = self::falha('Outra pessoa acabou de mudar um dos títulos: nada foi gravado.');
                    \Core\Transacao::falhar($r['erro']);
                }
                F::registrar(['titulo_id' => (int) $t['id'], 'aluno_id' => $alunoId, 'tipo' => 'receber', 'data_operacao' => $data, 'forma' => $formaOp,
                    'original_centavos' => $e['original'], 'multa_centavos' => $e['multa'], 'juros_centavos' => $e['juros'], 'desconto_centavos' => $e['desconto'] + $descs[$i],
                    'recebido_centavos' => $recebido, 'observacao' => "Recibo nº {$numero}" . ($obs ? ' · ' . $obs : ''), 'situacao_antes' => 'aberto', 'situacao_depois' => 'recebido',
                    'recibo_id' => $recibo, 'usuario_id' => $usuario]);
            }
            $ins = F::pdo()->prepare('INSERT INTO fin_recibo_formas (recibo_id, ordem, forma, valor_centavos, pagador_documento, cartao_tipo, cartao_parcelas, bandeira_id, cartao_final,
                cartao_documento, cartao_autorizacao, situacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'valido\')');
            foreach ($fm['formas'] as $f) {
                $ins->execute([$recibo, $f['ordem'], $f['forma'], $f['valor_centavos'], $f['pagador_documento'], $f['cartao_tipo'], $f['cartao_parcelas'], $f['bandeira_id'], $f['cartao_final'],
                    $f['cartao_documento'], $f['cartao_autorizacao']]);
            }
            $r = ['ok' => true, 'erro' => '', 'recibo' => $recibo];
            return true;
        });
        if ($ok === false) {
            return $r && !$r['ok'] ? $r : self::falha('Nada foi gravado: ' . \Core\Transacao::ultimoErro() . '.');
        }
        return $r;
    }

    /** Os motivos em uso de uma operação. @return array<int, string> */
    public static function motivos(string $operacao): array
    {
        $st = F::pdo()->prepare('SELECT id, nome FROM fin_motivos WHERE operacao = ? AND situacao = 1 ORDER BY ordem, id');
        $st->execute([$operacao]);
        return array_column($st->fetchAll(\PDO::FETCH_ASSOC), 'nome', 'id');
    }

    /**
     * DESFAZER RECEBIMENTO: o recibo inteiro. $d: data (de quando foi recebido até hoje), motivo_id (da lista), justificativa
     * e envio. Os títulos voltam a aberto; o recibo, as linhas e as formas ficam estornados (nada é apagado).
     */
    public static function estornar(int $reciboId, array $d, int $usuario): array
    {
        $envio = trim((string) ($d['envio'] ?? ''));
        if ($envio !== '') {
            $st = F::pdo()->prepare("SELECT recibo_id FROM fin_operacoes WHERE envio = ? AND tipo = 'desfazer_recebimento'");
            $st->execute([$envio]);
            if (($ja = $st->fetchColumn()) !== false) {
                return ['ok' => true, 'erro' => '', 'recibo' => (int) $ja];
            }
        }
        $data = self::data(trim((string) ($d['data'] ?? '')));
        $motivos = self::motivos('desfazer_recebimento');
        $motivoId = (int) ($d['motivo_id'] ?? 0);
        $just = mb_substr(trim((string) ($d['justificativa'] ?? '')), 0, 500);
        if (!$data || $data > date('Y-m-d')) {
            return self::falha('Informe a data do desfazer (até hoje).');
        }
        if (!isset($motivos[$motivoId])) {
            return self::falha('Escolha o motivo.');
        }
        if (mb_strlen($just) < 5) {
            return self::falha('Escreva a justificativa (ao menos 5 letras): o desfazer fica no histórico.');
        }
        $r = null;
        $ok = \Core\Transacao::executar(static function () use ($reciboId, $data, $motivoId, $motivos, $just, $envio, $usuario, &$r) {
            $st = F::pdo()->prepare('SELECT * FROM fin_recibos WHERE id = ? FOR UPDATE');
            $st->execute([$reciboId]);
            $rec = $st->fetch(\PDO::FETCH_ASSOC);
            if (!$rec) {
                $r = self::falha('Recibo não encontrado.');
                \Core\Transacao::falhar($r['erro']);
            }
            if ($rec['situacao'] !== 'valido') {
                $r = self::falha('O recibo nº ' . $rec['numero'] . ' já foi desfeito: não há o que desfazer.');
                \Core\Transacao::falhar($r['erro']);
            }
            if ($data < $rec['data_recebimento']) {
                $r = self::falha('A data do desfazer não pode ser antes do recebimento (' . date('d/m/Y', strtotime($rec['data_recebimento'])) . ').');
                \Core\Transacao::falhar($r['erro']);
            }
            $st = F::pdo()->prepare('SELECT t.* FROM fin_recibo_titulos l JOIN fin_titulos t ON t.id = l.titulo_id WHERE l.recibo_id = ? ORDER BY t.id FOR UPDATE');
            $st->execute([$reciboId]);
            $titulos = $st->fetchAll(\PDO::FETCH_ASSOC);
            $motivo = $motivos[$motivoId];
            $primeira = 0;
            foreach ($titulos as $t) {
                $u = F::pdo()->prepare("UPDATE fin_titulos SET situacao = 'aberto' WHERE id = ? AND situacao = 'recebido'");
                $u->execute([(int) $t['id']]);
                if ($u->rowCount() !== 1) {
                    $r = self::falha('O título ' . $t['id'] . ' (' . $t['descricao'] . ') não está recebido: nada foi desfeito.');
                    \Core\Transacao::falhar($r['erro']);
                }
                $op = F::registrar(['titulo_id' => (int) $t['id'], 'aluno_id' => (int) $rec['aluno_id'], 'tipo' => 'desfazer_recebimento', 'data_operacao' => $data,
                    'original_centavos' => (int) $t['valor_centavos'], 'motivo' => $motivo . ': ' . $just, 'situacao_antes' => 'recebido', 'situacao_depois' => 'aberto',
                    'recibo_id' => $reciboId, 'envio' => $primeira ? null : ($envio ?: null), 'usuario_id' => $usuario]);
                $primeira = $primeira ?: $op;
                F::pdo()->prepare("UPDATE fin_operacoes SET desfeita_em = NOW(), desfeita_por_operacao = ? WHERE recibo_id = ? AND titulo_id = ? AND tipo = 'receber' AND desfeita_em IS NULL")
                    ->execute([$op, $reciboId, (int) $t['id']]);
            }
            F::pdo()->prepare("UPDATE fin_recibos SET situacao = 'estornado', estorno_data = ?, estorno_motivo_id = ?, estorno_motivo = ?, estorno_justificativa = ?, estornado_por = ?, estornado_em = NOW() WHERE id = ?")
                ->execute([$data, $motivoId, $motivo, $just, $usuario, $reciboId]);
            F::pdo()->prepare("UPDATE fin_recibo_titulos SET situacao = 'estornado' WHERE recibo_id = ?")->execute([$reciboId]);
            F::pdo()->prepare("UPDATE fin_recibo_formas SET situacao = 'estornado' WHERE recibo_id = ?")->execute([$reciboId]);
            $r = ['ok' => true, 'erro' => '', 'recibo' => $reciboId];
            return true;
        });
        if ($ok === false) {
            return $r && !$r['ok'] ? $r : self::falha('Nada foi desfeito: ' . \Core\Transacao::ultimoErro() . '.');
        }
        return $r;
    }

    /** O recibo, com as linhas, as formas, o aluno (e a série do ano) e quem recebeu; null se não existe (o do core, D-99). */
    public static function recibo(int $id): ?array
    {
        return \VcReciboFinanceiro::dados(F::pdo(), $id);
    }

    /** A forma por extenso, como no recibo (a do core). */
    public static function formaPorExtenso(array $f): string
    {
        return \VcReciboFinanceiro::formaPorExtenso($f);
    }

    /** Os recibos de um título (o válido e os estornados), o mais novo primeiro. */
    public static function doTitulo(int $tituloId): array
    {
        $st = F::pdo()->prepare('SELECT r.id, r.numero, r.data_recebimento, r.total_centavos, r.situacao, r.estorno_data, r.estorno_motivo, l.recebido_centavos, u.nome AS recebedor
            FROM fin_recibo_titulos l JOIN fin_recibos r ON r.id = l.recibo_id LEFT JOIN adms_usuarios u ON u.id = r.usuario_id WHERE l.titulo_id = ? ORDER BY r.id DESC');
        $st->execute([$tituloId]);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }
}
