<?php
/*
 * CABEÇALHOS DE SEGURANÇA (E7, generalizada na E8): a tabela de CSP, X-Frame-Options, HSTS e Set-Cookie do
 * portal, do painel e da verificação pública, contra qualquer URL, com a avaliação de cada linha.
 *
 * Uso: php database/ferramentas/implantacao/cabecalhos.php <URL base> [--inseguro]
 *
 * Esperado (D-223 a D-225): painel e verificação com frame-ancestors 'none' + X-Frame-Options DENY; cookies
 * HttpOnly, com Secure em HTTPS; HSTS em HTTPS (EXIGIR_HTTPS). O portal mostra as origens do Moodle, se houver.
 */
require __DIR__ . '/_base.php';

$o = vcostaedu_args($argv);
$B = vcostaedu_url_base($o['_'][0] ?? null);
$https = str_starts_with($B, 'https://');
$pag = ['portal' => 'portal/login/acesso', 'painel' => 'painel/login/acesso', 'verificação' => 'painel/verificar/codigo'];

Saida::secao("Cabeçalhos em {$B}");
if (!$https) {
    Saida::alerta('URL em HTTP: no servidor, use a URL HTTPS (o HSTS e o cookie Secure só aparecem em HTTPS)');
}
$tab = ['| Página | Content-Security-Policy | X-Frame-Options | Strict-Transport-Security | Set-Cookie (valor omitido) |', '|---|---|---|---|---|'];
$res = [];
foreach ($pag as $nome => $rota) {
    [$cod, $cab] = (new Http(!empty($o['inseguro'])))->req($B . $rota, null, false); // cliente novo: cada página mostra o seu Set-Cookie
    $h = ['csp' => '—', 'xfo' => '—', 'hsts' => '—', 'cookie' => '—'];
    foreach (explode("\r\n", $cab) as $l) {
        [$k, $v] = array_pad(explode(':', $l, 2), 2, '');
        $k = strtolower(trim($k));
        $v = trim($v);
        if ($k === 'content-security-policy') { $h['csp'] = $v; }
        if ($k === 'x-frame-options') { $h['xfo'] = $v; }
        if ($k === 'strict-transport-security') { $h['hsts'] = $v; }
        if ($k === 'set-cookie' && preg_match('/^VCOSTAEDU_/', $v)) { $h['cookie'] = preg_replace('/=[^;]*;/', '=…;', $v, 1); }
    }
    $h['codigo'] = $cod;
    $res[$nome] = $h;
    $tab[] = "| {$nome} (HTTP {$cod}) | `{$h['csp']}` | {$h['xfo']} | {$h['hsts']} | `{$h['cookie']}` |";
}
echo "\n" . implode("\n", $tab) . "\n";

Saida::secao('Avaliação');
foreach ($res as $nome => $h) {
    if ($h['codigo'] === 0) {
        Saida::falha("{$nome}: sem resposta (conexão ou certificado; em teste, --inseguro)");
        continue;
    }
    if ($h['codigo'] >= 300 && $h['codigo'] < 400) {
        Saida::alerta("{$nome}: HTTP {$h['codigo']} (redirecionamento; se for HTTP → HTTPS, rode com a URL HTTPS)");
    }
    if ($nome !== 'portal') {
        str_contains($h['csp'], "frame-ancestors 'none'") && strtoupper($h['xfo']) === 'DENY'
            ? Saida::ok("{$nome}: não embutível (frame-ancestors 'none' + DENY)") : Saida::falha("{$nome}: devia ter frame-ancestors 'none' e X-Frame-Options DENY");
    } else {
        preg_match("/frame-ancestors ([^;]+)/", $h['csp'], $m);
        isset($m[1]) ? Saida::ok("portal: embutível por {$m[1]}" . ($h['xfo'] !== '—' ? " (X-Frame-Options {$h['xfo']})" : '')) : Saida::falha('portal: sem frame-ancestors');
    }
    $c = $h['cookie'];
    if ($c === '—') {
        Saida::alerta("{$nome}: nenhum cookie de sessão nesta resposta");
        continue;
    }
    stripos($c, 'httponly') !== false ? Saida::ok("{$nome}: cookie HttpOnly") : Saida::falha("{$nome}: cookie sem HttpOnly");
    if ($https) {
        stripos($c, 'secure') !== false ? Saida::ok("{$nome}: cookie Secure") : Saida::falha("{$nome}: cookie sem Secure em HTTPS");
    }
    if (stripos($c, 'samesite=none') !== false) {
        Saida::info("{$nome}: SameSite=None" . (stripos($c, 'partitioned') !== false ? '; Partitioned' : '; SEM Partitioned (COOKIE_PARTICIONADO = false)') . ' — modo iframe');
    }
}
if ($https) {
    $res['painel']['hsts'] !== '—' ? Saida::ok('HSTS presente (' . $res['painel']['hsts'] . ')') : Saida::alerta('sem HSTS: EXIGIR_HTTPS = false na Config?');
}
Saida::fim();
