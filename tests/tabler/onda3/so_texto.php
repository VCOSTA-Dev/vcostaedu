<?php
/*
 * ONDA 3, ETAPA 5 — a troca dos nomes da demonstração mexeu SÓ em texto?
 *
 *   php tests/tabler/onda3/so_texto.php <retrato antes> <retrato depois>
 *
 * Compara dois retratos da régua (tests/tabler/regua.php gravar): em cada tabela, a mesma quantidade de linhas, e cada
 * linha (pela chave id, ou pela posição) com os mesmos números (inteiros, decimais, datas); só os campos de texto podem
 * diferir. Nos boletins e nas vidas acadêmicas, a mesma estrutura e os mesmos números. Imprime as colunas de texto que
 * mudaram e reprova (código 1) em qualquer diferença que não é de texto.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
[$_, $a, $b] = $argv + [null, '', ''];
$A = json_decode((string) @file_get_contents($a), true);
$B = json_decode((string) @file_get_contents($b), true);
if (!is_array($A) || !is_array($B)) {
    fwrite(STDERR, "uso: php so_texto.php <antes.json> <depois.json>\n");
    exit(2);
}
/* número: inteiro, decimal ou data (o que a troca de nomes não pode mudar) */
$numero = static fn($v): bool => is_int($v) || is_float($v) || (is_string($v) && preg_match('/^-?\d+(\.\d+)?$|^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}(:\d{2})?)?$/', $v));
$problemas = [];
$textos = [];
foreach (array_unique(array_merge(array_keys($A['conteudo'] ?? []), array_keys($B['conteudo'] ?? []))) as $t) {
    $la = array_map(fn($x) => json_decode($x, true), $A['conteudo'][$t] ?? []);
    $lb = array_map(fn($x) => json_decode($x, true), $B['conteudo'][$t] ?? []);
    if (count($la) !== count($lb)) {
        $problemas[] = "{$t}: " . count($la) . ' linha(s) antes, ' . count($lb) . ' depois';
        continue;
    }
    $porId = static function (array $ls): array {
        $o = [];
        foreach ($ls as $i => $l) {
            $o[isset($l['id']) ? 'id' . $l['id'] : (isset($l['chave']) ? 'k' . $l['chave'] : 'p' . $i)] = $l;
        }
        ksort($o);
        return $o;
    };
    $ia = $porId($la);
    $ib = $porId($lb);
    if (array_keys($ia) !== array_keys($ib)) {
        $problemas[] = "{$t}: as chaves das linhas mudaram";
        continue;
    }
    foreach ($ia as $k => $ra) {
        $rb = $ib[$k];
        foreach (array_unique(array_merge(array_keys($ra), array_keys($rb))) as $c) {
            $va = $ra[$c] ?? null;
            $vb = $rb[$c] ?? null;
            if ($va === $vb) {
                continue;
            }
            if ($numero($va) || $numero($vb) || $va === null || $vb === null) {
                $problemas[] = "{$t}.{$c} ({$k}): " . json_encode($va, JSON_UNESCAPED_UNICODE) . ' → ' . json_encode($vb, JSON_UNESCAPED_UNICODE);
            } else {
                $textos["{$t}.{$c}"] = ($textos["{$t}.{$c}"] ?? 0) + 1;
            }
        }
    }
}
/* boletins e vidas: a mesma árvore, os mesmos números */
$andar = static function ($x, $y, string $onde) use (&$andar, &$problemas, &$textos, $numero): void {
    if (is_array($x) && is_array($y)) {
        if (array_keys($x) !== array_keys($y)) {
            $problemas[] = "{$onde}: a estrutura mudou";
            return;
        }
        foreach ($x as $k => $v) {
            $andar($v, $y[$k], "{$onde}/{$k}");
        }
        return;
    }
    if ($x === $y) {
        return;
    }
    if ($numero($x) || $numero($y) || is_array($x) || is_array($y) || $x === null || $y === null) {
        $problemas[] = "{$onde}: " . json_encode($x, JSON_UNESCAPED_UNICODE) . ' → ' . json_encode($y, JSON_UNESCAPED_UNICODE);
        return;
    }
    $chave = preg_replace('#/\d+#', '/n', $onde);
    $textos[$chave] = ($textos[$chave] ?? 0) + 1;
};
foreach (['boletins', 'vidas'] as $parte) {
    $andar($A[$parte] ?? [], $B[$parte] ?? [], $parte);
}
ksort($textos);
echo 'Campos de TEXTO que mudaram (' . count($textos) . "):\n";
foreach ($textos as $c => $n) {
    echo "  {$c} ({$n})\n";
}
if ($problemas) {
    echo "\nDIFERENÇA QUE NÃO É DE TEXTO (" . count($problemas) . "):\n  " . implode("\n  ", array_slice($problemas, 0, 400)) . "\n";
    exit(1);
}
echo "\nSó texto: os números, as quantidades e os ids são os mesmos.\n";
