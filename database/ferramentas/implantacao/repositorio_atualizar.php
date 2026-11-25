<?php
/*
 * O REPOSITÓRIO DO PRODUTO (Fechamento; D-108; docs/REPOSITORIO.md) — cria ou atualiza, numa pasta à parte, o
 * repositório git do produto: sem o histórico de trabalho, um commit por versão entregue.
 *
 *   php database/ferramentas/implantacao/repositorio_atualizar.php <pasta do repositório> [--sem-commit]
 *
 * O que faz, nesta ordem (para no primeiro problema, sem tocar no repositório):
 *   1. exporta o último commit desta pasta pela PASTA LIMPA (pasta_limpa.php: git archive, sem o retorno, as notas de
 *      trabalho e as provas) numa pasta temporária, e exige a varredura de higiene sem achado;
 *   2. na pasta do repositório (criada, com `git init`, se não existir): apaga tudo o que não é .git e copia a exportação
 *      (o que saiu do produto sai também do repositório);
 *   3. `git add -A` e um commit "VCOSTAEDU <versão>" (a versão do índice dos guias, a mesma da tela Versão), com a tag
 *      v<versão>. Nada é enviado: o push é do responsável (docs/REPOSITORIO.md).
 * Recusa a própria pasta do sistema como destino e a pasta que tem arquivos mas não é um repositório git.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$raiz = realpath(dirname(__DIR__, 3));
$destino = rtrim((string) ($argv[1] ?? ''), '/\\');
$semCommit = in_array('--sem-commit', $argv, true);
if ($destino === '') {
    fwrite(STDERR, "Uso: php database/ferramentas/implantacao/repositorio_atualizar.php <pasta do repositório> [--sem-commit]\n");
    exit(2);
}
if (realpath($destino) === $raiz || (realpath($destino) !== false && str_starts_with((string) realpath($destino), $raiz . DIRECTORY_SEPARATOR))) {
    fwrite(STDERR, "Recusado: o repositório fica fora da pasta do sistema.\n");
    exit(2);
}
$git = static function (string $pasta, string $args, ?int &$rc = null): string {
    exec('git -C ' . escapeshellarg($pasta) . ' ' . $args . ' 2>&1', $saida, $rc);
    return implode("\n", $saida);
};
if (is_dir($destino) && count(scandir($destino)) > 2 && !is_dir($destino . '/.git')) {
    fwrite(STDERR, "Recusado: a pasta tem arquivos e não é um repositório git.\n");
    exit(2);
}

/* 1. a exportação e a varredura */
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vcostaedu-exportacao-' . bin2hex(random_bytes(4));
passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/pasta_limpa.php') . ' ' . escapeshellarg($tmp), $rc);
$apagar = static function (string $p) use (&$apagar): void {
    if (is_dir($p) && !is_link($p)) {
        foreach (array_diff(scandir($p), ['.', '..']) as $f) {
            $apagar($p . DIRECTORY_SEPARATOR . $f);
        }
        @rmdir($p);
    } elseif (file_exists($p) || is_link($p)) {
        @chmod($p, 0666);
        @unlink($p);
    }
};
if ($rc !== 0) {
    fwrite(STDERR, "Parado: a varredura da pasta limpa achou algo (o relatório em {$tmp}-varredura.txt). O repositório não mudou.\n");
    $apagar($tmp);
    exit(1);
}
/* lido como texto: o índice tem a guarda das telas (fora do sistema, ele encerra) */
$versao = preg_match("/^\s*'versao'\s*=>\s*'([^']+)'/m", (string) @file_get_contents($raiz . '/painel/app/adms/Views/ajuda/guias/_indice.php'), $mv) ? $mv[1] : '';
if (!preg_match('/^\d+\.\d+\.\d+$/', $versao)) {
    fwrite(STDERR, "Parado: a versão não foi encontrada no índice dos guias.\n");
    $apagar($tmp);
    exit(1);
}

/* 2. o espelho */
if (!is_dir($destino)) {
    mkdir($destino, 0775, true);
}
if (!is_dir($destino . '/.git')) {
    $git($destino, 'init -q -b main', $rc);
    if ($rc !== 0) {
        fwrite(STDERR, "Parado: o git init falhou.\n");
        exit(1);
    }
}
foreach (array_diff(scandir($destino), ['.', '..', '.git']) as $f) {
    $apagar($destino . DIRECTORY_SEPARATOR . $f);
}
$copiar = static function (string $de, string $para) use (&$copiar): int {
    $n = 0;
    foreach (array_diff(scandir($de), ['.', '..']) as $f) {
        $a = $de . DIRECTORY_SEPARATOR . $f;
        $b = $para . DIRECTORY_SEPARATOR . $f;
        if (is_dir($a)) {
            @mkdir($b, 0775, true);
            $n += $copiar($a, $b);
        } else {
            copy($a, $b);
            $n++;
        }
    }
    return $n;
};
$n = $copiar($tmp, $destino);
$apagar($tmp);
@unlink($tmp . '-varredura.txt');
echo "Exportados {$n} arquivos para o repositório (versão {$versao}).\n";

/* 3. o commit */
if ($semCommit) {
    echo "Sem commit (--sem-commit): confira e rode de novo sem a opção.\n";
    exit(0);
}
$git($destino, 'add -A', $rc);
$mudou = trim($git($destino, 'status --porcelain'));
if ($mudou === '') {
    echo "Nada mudou desde o último commit: nenhum commit novo.\n";
    exit(0);
}
$msg = tempnam(sys_get_temp_dir(), 'vcmsg');
file_put_contents($msg, "VCOSTAEDU {$versao}\n\nA versão {$versao} do VCOSTAEDU, exportada do repositório de trabalho (docs/REPOSITORIO.md).\n");
$autor = (string) (getenv('VCOSTAEDU_REPO_AUTOR') ?: 'VCOSTAEDU <noreply@vcostaedu.invalid>');
/* o autor e quem grava o commit são o mesmo, neutro (sem o nome e o e-mail de uma pessoa); troque pelo VCOSTAEDU_REPO_AUTOR */
$nomeAutor = trim((string) preg_replace('/\s*<.*$/', '', $autor));
$emailAutor = preg_match('/<([^>]+)>/', $autor, $m) ? $m[1] : 'noreply@vcostaedu.invalid';
echo $git($destino, '-c user.name=' . escapeshellarg($nomeAutor) . ' -c user.email=' . escapeshellarg($emailAutor) . ' commit -q -F ' . escapeshellarg($msg), $rc) . "\n";
@unlink($msg);
if ($rc !== 0) {
    fwrite(STDERR, "Parado: o commit falhou (confira user.name e user.email do git).\n");
    exit(1);
}
$git($destino, 'tag -f v' . $versao, $rc);
echo $git($destino, 'log --oneline -1') . "\n";
echo "Pronto. O envio (push) é do responsável: docs/REPOSITORIO.md.\n";
