<?php
/**
 * O RECIBO DO FINANCEIRO (Onda 8, financeiro refeito, Parte 1; D-99), num lugar só para o painel e o portal: os dados de
 * um recibo (as linhas, as formas, o aluno e a série do ano, quem recebeu), o texto por extenso, a forma por extenso e a
 * página de imprimir, em duas vias numa folha, como o de origem. O recibo desfeito sai com a faixa de estornado.
 */
final class VcReciboFinanceiro
{
    public const FORMAS = ['dinheiro' => 'Dinheiro', 'pix' => 'Pix manual', 'cartao' => 'Cartão', 'transferencia' => 'Transferência', 'outro' => 'Outro']; // a transferência e o outro: só os recebimentos da 1.92.0

    /** 123456 → "1.234,56". */
    public static function brl(int $c): string
    {
        $neg = $c < 0;
        $c = abs($c);
        return ($neg ? '-' : '') . number_format(intdiv($c, 100), 0, ',', '.') . ',' . str_pad((string) ($c % 100), 2, '0', STR_PAD_LEFT);
    }

    /** 123456 → "um mil, duzentos e trinta e quatro reais e cinquenta e seis centavos". */
    public static function porExtenso(int $centavos): string
    {
        $reais = intdiv($centavos, 100);
        $cent = $centavos % 100;
        $partes = [];
        if ($reais > 0) {
            $partes[] = self::numero($reais) . ($reais === 1 ? ' real' : (($reais % 1000000) === 0 ? ' de reais' : ' reais'));
        }
        if ($cent > 0) {
            $partes[] = self::numero($cent) . ($cent === 1 ? ' centavo' : ' centavos');
        }
        return $partes ? implode(' e ', $partes) : 'zero real';
    }

    private static function numero(int $n): string
    {
        $u = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove', 'dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
        $d = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
        $c = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];
        $ate999 = static function (int $x) use ($u, $d, $c): string {
            if ($x === 100) {
                return 'cem';
            }
            $p = [];
            if ($x >= 100) {
                $p[] = $c[intdiv($x, 100)];
                $x %= 100;
            }
            if ($x >= 20) {
                $p[] = $d[intdiv($x, 10)] . ($x % 10 ? ' e ' . $u[$x % 10] : '');
            } elseif ($x > 0) {
                $p[] = $u[$x];
            }
            return implode(' e ', $p);
        };
        if ($n === 0) {
            return 'zero';
        }
        $escalas = [['', ''], ['mil', 'mil'], ['milhão', 'milhões'], ['bilhão', 'bilhões']];
        $grupos = [];
        $i = 0;
        while ($n > 0) {
            $g = $n % 1000;
            if ($g > 0) {
                array_unshift($grupos, [$g, ($i === 1 && $g === 1) ? 'mil' : $ate999($g) . ($i > 0 ? ' ' . $escalas[$i][$g === 1 ? 0 : 1] : '')]);
            }
            $n = intdiv($n, 1000);
            $i++;
        }
        $s = '';
        foreach ($grupos as $k => [$g, $txt]) {
            /* "e" antes do último grupo menor que cem ou de centena redonda ("mil e duzentos"); antes do último grupo com
               centena e dezena, só o espaço ("mil trezentos e nove"); entre os grupos maiores, a vírgula ("dois milhões, trezentos mil") */
            $ultimo = $k === count($grupos) - 1;
            $s .= $k === 0 ? $txt : (($ultimo && ($g < 100 || $g % 100 === 0)) ? ' e ' : ($ultimo ? ' ' : ', ')) . $txt;
        }
        return $s;
    }

    /** "12345678901" → "123.456.789-01"; 14 dígitos → o CNPJ formatado; o resto como veio. */
    public static function documentoFormatado(?string $doc): string
    {
        $n = preg_replace('/\D+/', '', (string) $doc);
        if (strlen($n) === 11) {
            return substr($n, 0, 3) . '.' . substr($n, 3, 3) . '.' . substr($n, 6, 3) . '-' . substr($n, 9);
        }
        if (strlen($n) === 14) {
            return substr($n, 0, 2) . '.' . substr($n, 2, 3) . '.' . substr($n, 5, 3) . '/' . substr($n, 8, 4) . '-' . substr($n, 12);
        }
        return (string) $doc;
    }

    /** A forma por extenso: "Cartão de crédito Visa, final 1234, em 3 parcelas (autorização X)"; "Pix de 000.265.022-xx". */
    public static function formaPorExtenso(array $f): string
    {
        switch ($f['forma']) {
            case 'cartao':
                return 'Cartão de ' . ($f['cartao_tipo'] === 'debito' ? 'débito' : 'crédito') . (!empty($f['bandeira']) ? ' ' . $f['bandeira'] : '') . ($f['cartao_final'] ? ', final ' . $f['cartao_final'] : '')
                    . ($f['cartao_tipo'] === 'credito' && (int) $f['cartao_parcelas'] > 1 ? ', em ' . (int) $f['cartao_parcelas'] . ' parcelas' : '')
                    . ($f['cartao_autorizacao'] ? ' (autorização ' . $f['cartao_autorizacao'] . ')' : '');
            case 'pix':
                return 'Pix' . ($f['pagador_documento'] ? ' de ' . self::documentoFormatado($f['pagador_documento']) : '');
            default: // o dinheiro; a transferência e o "outro" só nos recebimentos da 1.92.0
                return self::FORMAS[$f['forma']] ?? (string) $f['forma'];
        }
    }

    /** O recibo com as linhas, as formas, o aluno, a série do ano e quem recebeu; null se não existe. */
    public static function dados(\PDO $pdo, int $id): ?array
    {
        $q = static function (string $sql, array $p) use ($pdo): array {
            $st = $pdo->prepare($sql);
            $st->execute($p);
            return $st->fetchAll(\PDO::FETCH_ASSOC);
        };
        $r = $q('SELECT r.*, u.nome AS recebedor, e.nome AS estornador FROM fin_recibos r LEFT JOIN adms_usuarios u ON u.id = r.usuario_id
            LEFT JOIN adms_usuarios e ON e.id = r.estornado_por WHERE r.id = ?', [$id])[0] ?? null;
        if (!$r) {
            return null;
        }
        $r['linhas'] = $q('SELECT l.*, t.descricao, t.parcela, t.parcelas_total, t.vencimento FROM fin_recibo_titulos l JOIN fin_titulos t ON t.id = l.titulo_id WHERE l.recibo_id = ? ORDER BY t.vencimento, t.id', [$id]);
        $r['formas'] = $q('SELECT f.*, b.nome AS bandeira FROM fin_recibo_formas f LEFT JOIN fin_bandeiras b ON b.id = f.bandeira_id WHERE f.recibo_id = ? ORDER BY f.ordem, f.id', [$id]);
        $r['aluno'] = $q('SELECT id, nome, nome_social, rm FROM col_usuarios WHERE id = ?', [(int) $r['aluno_id']])[0] ?? ['id' => 0, 'nome' => '', 'nome_social' => '', 'rm' => ''];
        $r['serie'] = $r['ano'] ? ($q('SELECT me.id AS contrato, c.nome AS segmento, m.codnome AS serie FROM rm_matriculas_ensino me LEFT JOIN rm_cursos c ON c.id = me.ensino_id
            LEFT JOIN rm_cursos_turma_series m ON m.id = me.modulo_id WHERE me.usuario_id = ? AND me.ano = ? ORDER BY (me.substituido_em IS NULL) DESC, me.id DESC LIMIT 1', [(int) $r['aluno_id'], (int) $r['ano']])[0] ?? null) : null;
        return $r;
    }

    /**
     * A página de imprimir (inteira, sem o menu). $inst: o cadastro da instituição; $rotNum: "RM" ou "RA"; $logo: o endereço
     * do logotipo ou ''; $voltar: [rótulo, endereço] do botão de voltar; $msg: o aviso de quem acabou de receber.
     */
    public static function pagina(array $r, array $inst, string $rotNum, string $logo, array $voltar, string $msg = ''): void
    {
        $h = static fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
        $brl = static fn($v) => 'R$ ' . self::brl((int) $v);
        $dt = static fn($x) => $x ? date('d/m/Y', strtotime((string) $x)) : '';
        $meses = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
        $dataExtenso = static fn($x) => (int) date('j', strtotime($x)) . ' de ' . $meses[(int) date('n', strtotime($x))] . ' de ' . date('Y', strtotime($x));
        $i = $inst;
        $estornado = $r['situacao'] === 'estornado';
        $endereco = implode(' — ', array_filter([implode(', ', array_filter([(string) ($i['logradouro'] ?? ''), (string) ($i['numero'] ?? ''), (string) ($i['complemento'] ?? '')], 'strlen')),
            (string) ($i['bairro'] ?? ''), trim((string) ($i['cidade'] ?? '') . (!empty($i['uf']) ? '/' . $i['uf'] : '')), !empty($i['cep']) ? 'CEP ' . $i['cep'] : ''], 'strlen'));
        $serie = $r['serie'] ? trim(($r['serie']['serie'] ?? '') . (!empty($r['serie']['segmento']) ? ' — ' . $r['serie']['segmento'] : '')) : '';
        $formas = array_map(static fn($f) => self::formaPorExtenso($f) . (count($r['formas']) > 1 ? ': R$ ' . self::brl((int) $f['valor_centavos']) : ''), $r['formas']);
        $nomeInst = ($i['razao_social'] ?? '') ?: ($i['nome'] ?? '');
        ?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recibo nº <?= $h($r['numero']) ?></title>
<style>
  :root { --tinta: #1f2937; --linha: #9ca3af; --suave: #6b7280; }
  * { box-sizing: border-box; }
  body { margin: 0; background: #e5e7eb; color: var(--tinta); font: 13px/1.4 Arial, Helvetica, sans-serif; }
  .barra { max-width: 210mm; margin: 12px auto; display: flex; gap: 8px; align-items: center; padding: 0 8px; }
  .barra a, .barra button { font: inherit; padding: 6px 12px; border-radius: 6px; border: 1px solid #94a3b8; background: #fff; color: #1e293b; text-decoration: none; cursor: pointer; }
  .barra .principal { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
  .aviso { max-width: 210mm; margin: 0 auto 8px; padding: 8px 12px; border-radius: 6px; background: #dcfce7; border: 1px solid #86efac; }
  .folha { width: 210mm; max-width: 100%; margin: 0 auto 16px; background: #fff; padding: 10mm 12mm; }
  .via { position: relative; min-height: 128mm; border: 1px solid var(--linha); padding: 6mm 7mm; overflow: hidden; }
  .corte { border-top: 1px dashed var(--linha); margin: 6mm 0; position: relative; }
  .corte span { position: absolute; top: -8px; left: 50%; transform: translateX(-50%); background: #fff; padding: 0 6px; color: var(--suave); font-size: 10px; }
  .cab { display: flex; gap: 10px; align-items: flex-start; border-bottom: 1px solid var(--linha); padding-bottom: 6px; margin-bottom: 8px; }
  .cab img { max-height: 15mm; max-width: 30mm; }
  .cab .inst { flex: 1; }
  .cab .inst b { font-size: 14px; }
  .cab .inst div { font-size: 11px; color: var(--suave); }
  .num { text-align: right; }
  .num .n { font-size: 18px; font-weight: bold; }
  .num .v { font-size: 16px; font-weight: bold; border: 1px solid var(--tinta); padding: 2px 8px; margin-top: 4px; display: inline-block; }
  .texto { margin: 6px 0; }
  table { width: 100%; border-collapse: collapse; margin: 6px 0; font-size: 11.5px; }
  th, td { border: 1px solid var(--linha); padding: 3px 5px; text-align: left; }
  th { background: #f3f4f6; }
  td.r, th.r { text-align: right; white-space: nowrap; }
  .rodape { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 12px; gap: 12px; }
  .assina { text-align: center; width: 70mm; border-top: 1px solid var(--tinta); padding-top: 3px; font-size: 11px; }
  .via-rot { position: absolute; top: 2mm; right: 7mm; font-size: 10px; color: var(--suave); }
  .estornado { position: absolute; top: 42%; left: -10%; width: 120%; transform: rotate(-12deg); text-align: center; background: rgba(220, 38, 38, .12);
    border-top: 2px solid #dc2626; border-bottom: 2px solid #dc2626; color: #b91c1c; font-weight: bold; font-size: 15px; padding: 6px 0; }
  @media print {
    body { background: #fff; }
    .barra, .aviso { display: none; }
    .folha { margin: 0; padding: 8mm 10mm; width: auto; }
    @page { size: A4; margin: 0; }
  }
</style>
</head>
<body>
<div class="barra">
  <button type="button" class="principal" onclick="window.print()" id="imprimirRecibo">Imprimir</button>
  <a href="<?= $h($voltar[1]) ?>" id="voltarRecibo"><?= $h($voltar[0]) ?></a>
  <span style="margin-left:auto;color:#475569">Recibo nº <?= $h($r['numero']) ?><?= $estornado ? ' — estornado' : '' ?></span>
</div>
<?php if ($msg !== '') : ?><div class="aviso" id="avisoRecibo"><?= $h(trim(strip_tags($msg))) ?></div><?php endif; ?>
<div class="folha" id="recibo" data-numero="<?= $h($r['numero']) ?>" data-situacao="<?= $h($r['situacao']) ?>">
<?php foreach (['1ª via — de quem pagou', '2ª via — da instituição'] as $k => $via) : ?>
  <div class="via">
    <span class="via-rot"><?= $h($via) ?></span>
    <?php if ($estornado) : ?><div class="estornado vc-faixa-estornado">ESTORNADO em <?= $h($dt($r['estorno_data'])) ?> — não vale como comprovante</div><?php endif; ?>
    <div class="cab">
      <?php if ($logo !== '') : ?><img src="<?= $h($logo) ?>" alt=""><?php endif; ?>
      <div class="inst"><b><?= $h($nomeInst) ?></b>
        <?php if (!empty($i['cnpj'])) : ?><div>CNPJ <?= $h(self::documentoFormatado($i['cnpj'])) ?></div><?php endif; ?>
        <?php if ($endereco !== '') : ?><div><?= $h($endereco) ?></div><?php endif; ?>
        <?php if (!empty($i['telefone']) || !empty($i['email'])) : ?><div><?= $h(trim(($i['telefone'] ?? '') . (!empty($i['email']) ? ' · ' . $i['email'] : ''), ' ·')) ?></div><?php endif; ?></div>
      <div class="num"><div>RECIBO</div><div class="n">Nº <?= $h($r['numero']) ?></div><div class="v"><?= $h($brl($r['total_centavos'])) ?></div></div>
    </div>
    <div class="texto">Recebemos de <b class="vc-pagador"><?= $h($r['pagador_nome'] ?: '—') ?></b> a importância de <b><?= $h($brl($r['total_centavos'])) ?></b>
      (<span class="vc-extenso"><?= $h(self::porExtenso((int) $r['total_centavos'])) ?></span>), referente a:</div>
    <div class="texto">Aluno: <b><?= $h($r['aluno']['nome_social'] ?: $r['aluno']['nome']) ?></b> (<?= $h($rotNum) ?> <?= $h($r['aluno']['rm']) ?>)<?= $serie !== '' ? ' · ' . $h($serie) : '' ?><?= $r['ano'] ? ' · ano letivo ' . $h($r['ano']) : '' ?></div>
    <table>
      <thead><tr><th>Descrição</th><th>Título</th><th>Parcela</th><th>Vencimento</th><th class="r">Valor atualizado</th></tr></thead>
      <tbody>
      <?php foreach ($r['linhas'] as $l) : $atual = (int) $l['original_centavos'] + (int) $l['multa_centavos'] + (int) $l['juros_centavos'] - (int) $l['pontualidade_centavos']; ?>
        <tr><td><?= $h($l['descricao']) ?></td><td><?= $h($l['titulo_id']) ?></td><td><?= $h($l['parcela'] !== null ? $l['parcela'] . ($l['parcelas_total'] ? '.' . $l['parcelas_total'] : '') : '—') ?></td>
          <td><?= $h($dt($l['vencimento'])) ?></td><td class="r"><?= $h($brl($atual)) ?></td></tr>
      <?php endforeach; ?>
      <?php if ((int) $r['acrescimo_centavos']) : ?><tr><td colspan="4">Acréscimo<?= $r['justificativa'] ? ' — ' . $h($r['justificativa']) : '' ?></td><td class="r">+ <?= $h($brl($r['acrescimo_centavos'])) ?></td></tr><?php endif; ?>
      <?php if ((int) $r['desconto_centavos']) : ?><tr><td colspan="4">Desconto<?= $r['justificativa'] ? ' — ' . $h($r['justificativa']) : '' ?></td><td class="r">− <?= $h($brl($r['desconto_centavos'])) ?></td></tr><?php endif; ?>
      <tr><th colspan="4">Total recebido</th><th class="r"><?= $h($brl($r['total_centavos'])) ?></th></tr>
      </tbody>
    </table>
    <div class="texto">Forma de pagamento: <span class="vc-forma"><?= $h(implode('; ', $formas)) ?></span>.</div>
    <?php if ($r['observacao']) : ?><div class="texto">Observação: <?= $h($r['observacao']) ?></div><?php endif; ?>
    <?php if ($estornado) : ?><div class="texto" style="color:#b91c1c">Estornado em <?= $h($dt($r['estorno_data'])) ?>: <?= $h($r['estorno_motivo'] . ($r['estorno_justificativa'] ? ' — ' . $r['estorno_justificativa'] : '')) ?></div><?php endif; ?>
    <div class="rodape">
      <div><?= $h(trim((string) ($i['cidade'] ?? ''))) ?><?= !empty($i['cidade']) ? ', ' : '' ?><?= $h($dataExtenso($r['data_recebimento'])) ?>.</div>
      <div class="assina"><?= $h($r['recebedor'] ?: '') ?><br>quem recebeu</div>
    </div>
  </div>
  <?php if ($k === 0) : ?><div class="corte"><span>corte aqui</span></div><?php endif; ?>
<?php endforeach; ?>
</div>
</body>
</html>
<?php
    }
}
