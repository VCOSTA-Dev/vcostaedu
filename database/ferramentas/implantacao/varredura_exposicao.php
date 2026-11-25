<?php
/*
 * VARREDURA DE EXPOSIÇÃO (E6c/E7, generalizada na E8). Pede pela web cada arquivo PRIVADO desta cópia do
 * sistema e lista o que responde 200 onde não devia (esperado: 403 ou 404).
 *
 * Uso: php database/ferramentas/implantacao/varredura_exposicao.php <URL base> [--inseguro] [--saida=arquivo.tsv]
 *   <URL base>: onde o sistema responde (a pasta que tem painel/ e portal/), ex.: https://escola.exemplo.invalid/
 *   --inseguro: aceita certificado inválido/autoassinado (só em teste).
 *
 * Público por desenho: assets/, painel/assets/, portal/assets/ (menos painel/assets/docs/alunos/) e os três
 * index.php. Mesmo em assets, .md/.sql/.log/.bak/.sh/.lock e composer/package.json são privados. Rode NO
 * servidor (a lista sai dos arquivos que estão lá, inclusive Config.php e o que houver em storage/).
 */
require __DIR__ . '/_base.php';

$o = vcostaedu_args($argv);
$B = vcostaedu_url_base($o['_'][0] ?? null);
$raiz = str_replace('\\', '/', realpath(VCOSTAEDU_RAIZ)) . '/';
$priv = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($raiz)));
    if (str_starts_with($rel, '.git/') && !in_array($rel, ['.git/config', '.git/HEAD', '.git/index', '.git/logs/HEAD', '.git/COMMIT_EDITMSG'], true)) {
        continue;
    }
    if (str_starts_with($rel, 'node_modules/') || str_contains($rel, '/node_modules/')) {
        continue;
    }
    $sensivel = preg_match('#(\.(md|sql|log|bak|sh|lock|swp|dist|yml|yaml|ini|env|gz|zip|tsv)$|(^|/)(composer|package|package-lock|bower)\.json$|(^|/)\.gitignore$|(^|/)\.htaccess$)#i', $rel);
    $publico = preg_match('#^(assets/|painel/assets/|portal/assets/)#', $rel) && !str_starts_with($rel, 'painel/assets/docs/alunos/') && !$sensivel;
    if (in_array($rel, ['index.php', 'painel/index.php', 'portal/index.php'], true)) {
        $publico = true;
    }
    if (!$publico) {
        $priv[] = $rel;
    }
}
foreach (['.git/', '.git/HEAD', 'painel/vendor/', 'portal/vendor/', 'database/', 'database/ferramentas/', 'docs/', 'docs/guia/', 'storage/', 'storage/historico/emissoes/', 'painel/app/', 'painel/core/', 'painel/core/Config.php', 'portal/app/', 'portal/core/', 'portal/core/Config.php', 'painel/vendor/autoload.php'] as $d) {
    $priv[] = $d;
}
$priv = array_values(array_unique($priv));
echo count($priv) . " caminhos privados a pedir em {$B}\n";

$res = [];
foreach (array_chunk($priv, 40) as $lote) {
    $mh = curl_multi_init();
    $hs = [];
    foreach ($lote as $p) {
        $c = curl_init($B . str_replace('%2F', '/', rawurlencode($p)));
        curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 30, CURLOPT_FOLLOWLOCATION => 0, CURLOPT_SSL_VERIFYPEER => empty($o['inseguro']), CURLOPT_SSL_VERIFYHOST => empty($o['inseguro']) ? 2 : 0]);
        curl_multi_add_handle($mh, $c);
        $hs[$p] = $c;
    }
    do {
        curl_multi_exec($mh, $run);
        curl_multi_select($mh);
    } while ($run);
    foreach ($hs as $p => $c) {
        $res[$p] = [(int) curl_getinfo($c, CURLINFO_HTTP_CODE), strlen((string) curl_multi_getcontent($c))];
        curl_multi_remove_handle($mh, $c);
    }
    curl_multi_close($mh);
}
$por = [];
$abertos = [];
$linhas = [];
foreach ($res as $p => [$c, $l]) {
    $por[$c] = ($por[$c] ?? 0) + 1;
    $linhas[] = "{$c}\t{$l}\t{$p}";
    if ($c === 200) {
        $abertos[] = $p;
    }
}
ksort($por);
if (!empty($o['saida'])) {
    sort($linhas);
    file_put_contents((string) $o['saida'], implode("\n", $linhas) . "\n");
}
Saida::secao('Resultado');
Saida::info('por código HTTP: ' . json_encode($por));
if (isset($por[0])) {
    Saida::falha("{$por[0]} pedido(s) sem resposta (conexão/certificado; teste use --inseguro)");
}
if ($abertos) {
    Saida::falha(count($abertos) . ' caminho(s) privado(s) respondem 200:');
    foreach (array_slice($abertos, 0, 40) as $p) {
        Saida::info($p);
    }
} else {
    Saida::ok(count($res) . ' caminhos privados testados: nenhum responde 200');
}
Saida::fim();
