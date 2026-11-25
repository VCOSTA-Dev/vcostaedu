<?php
/*
 * Roda o database/ferramentas/implantacao/fumaca.php da origem com os sete perfis da demonstração (Onda 2)
 * (administrador, secretaria, professor, aluno), alimentando os logins pela entrada padrão a partir de
 * tests/tabler/saida/credenciais.json (gravado pelo montar_demo.php). Sai com o código da ferramenta.
 *
 * Uso: VCOSTAEDU_BASE=<url> php tests/tabler/fumaca.php [--limite=N]
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$base = (string) getenv('VCOSTAEDU_BASE');
/* Onda 4, Etapa 6: VCOSTAEDU_CREDENCIAIS aponta outro arquivo (a demonstração da escola) */
$cred = json_decode((string) @file_get_contents(getenv('VCOSTAEDU_CREDENCIAIS') ?: __DIR__ . '/saida/credenciais.json'), true);
if ($base === '' || !$cred) {
    fwrite(STDERR, "Defina VCOSTAEDU_BASE e monte a demonstração (tests/tabler/montar_demo.php) antes.\n");
    exit(2);
}
$admin = getenv('VCOSTAEDU_ADMIN_USUARIO') ?: 'administrador';
$entrada = implode("\n", [
    $admin, $cred['painel'][$admin],
    'direcao.demo', $cred['painel']['direcao.demo'],
    'supervisao.demo', $cred['painel']['supervisao.demo'],
    'secretaria.demo', $cred['painel']['secretaria.demo'],
    'coord.demo', $cred['painel']['coord.demo'],
    'prof.laura.demo', $cred['painel']['prof.laura.demo'],
    ...(isset($cred['portal']['260101']) ? ['260101', $cred['portal']['260101']] : [(string) array_key_first($cred['portal']), (string) reset($cred['portal'])]), // a escola: o primeiro aluno
]) . "\n";
$limite = '--limite=200';
foreach ($argv as $a) {
    if (str_starts_with($a, '--limite=')) {
        $limite = $a;
    }
}
$cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__, 2) . '/database/ferramentas/implantacao/fumaca.php') . ' ' . escapeshellarg($base) . ' ' . $limite;
$p = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pp);
fwrite($pp[0], $entrada);
fclose($pp[0]);
$saida = stream_get_contents($pp[1]) . stream_get_contents($pp[2]);
fclose($pp[1]);
fclose($pp[2]);
$rc = proc_close($p);
/* Sem os prompts de senha (a entrada não é terminal; a senha não aparece, mas o eco do rótulo polui). */
echo preg_replace('/^(Usuário do perfil|Senha \(não aparece\)).*$\R?/m', '', $saida);
exit($rc);
