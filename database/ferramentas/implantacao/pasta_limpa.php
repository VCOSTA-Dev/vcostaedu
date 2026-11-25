<?php
/*
 * A PASTA LIMPA (Onda 6, Etapa 8) — o estado atual do repositório, pronto para abrir o código, numa pasta vizinha:
 * só os arquivos versionados do último commit (git archive: sem o histórico), sem o retorno.md e sem as pastas de prova
 * e de retomada (provas/). Depois, a VARREDURA DE HIGIENE sobre o resultado.
 *
 *   php database/ferramentas/implantacao/pasta_limpa.php [pasta de destino]
 *       padrão: "vcostaedu-publico", ao lado da pasta do sistema. Recusa destino que não esteja vazio ou que seja a
 *       própria pasta do sistema. O relatório da varredura vai para "<destino>-varredura.txt" (fora da pasta exportada).
 *
 * A varredura procura, em todo arquivo de texto exportado:
 *   - o que nunca entra num repositório público: chave privada, senha escrita no código, chave de API, token;
 *   - endereço IP, porta local, caminho de servidor ou de disco de uma máquina;
 *   - o nome de código da pasta de trabalho;
 *   - os nomes reais e os segredos que o ambiente informar (nunca escritos aqui): VCOSTAEDU_VARRER_NOMES (lista separada
 *     por vírgula) e VCOSTAEDU_VARRER_SEGREDOS (idem: os nomes de banco, o endereço e a chave de um Moodle…).
 * O que é exemplo de propósito (o domínio exemplo.invalid, o endereço local nos exemplos de configuração) é aceito pela
 * lista ACEITOS. Sai com código 1 se achar algo.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$raiz = realpath(dirname(__DIR__, 3));
$destino = $argv[1] ?? (dirname($raiz) . DIRECTORY_SEPARATOR . 'vcostaedu-publico');
$destino = rtrim($destino, '/\\');
if (realpath($destino) === $raiz) {
    fwrite(STDERR, "Recusado: o destino é a própria pasta do sistema.\n");
    exit(2);
}
if (is_dir($destino) && count(scandir($destino)) > 2) {
    fwrite(STDERR, "Recusado: o destino já tem arquivos. Apague-o ou escolha outro.\n");
    exit(2);
}
@mkdir($destino, 0775, true);

/* 1. a exportação: git archive do último commit, sem o retorno e sem as provas */
$zip = tempnam(sys_get_temp_dir(), 'vcpub') . '.zip';
$rc = 0;
passthru('git -C ' . escapeshellarg($raiz) . ' archive --format=zip -o ' . escapeshellarg($zip) . ' HEAD', $rc);
if ($rc !== 0 || !is_file($zip)) {
    fwrite(STDERR, "Falhou o git archive (o sistema precisa ser um repositório git).\n");
    exit(1);
}
$z = new ZipArchive();
$z->open($zip);
/* Fechamento (D-108): também as notas de trabalho das sessões (a passagem de sessão, a próxima tarefa, a lista do material a
   regravar) — são do dia a dia do projeto, não do produto */
const FORA_DO_PACOTE = ['retorno.md', 'docs/SESSION_HANDOFF.md', 'docs/NEXT_TASK.md', 'docs/guia/material-pendencias.md'];
$fora = static fn(string $n): bool => in_array($n, FORA_DO_PACOTE, true) || str_starts_with($n, 'provas/');
$n = 0;
for ($i = 0; $i < $z->numFiles; $i++) {
    $nome = $z->getNameIndex($i);
    if (str_ends_with($nome, '/') || $fora($nome)) {
        continue;
    }
    $alvo = $destino . '/' . $nome;
    @mkdir(dirname($alvo), 0775, true);
    file_put_contents($alvo, $z->getFromIndex($i));
    $n++;
}
$z->close();
@unlink($zip);
$commit = trim((string) shell_exec('git -C ' . escapeshellarg($raiz) . ' rev-parse --short HEAD'));

/* 2. a varredura */
$lista = static fn(string $v): array => array_values(array_filter(array_map('trim', explode(',', (string) getenv($v))), fn($s) => strlen($s) >= 4));
$regras = [
    'chave privada' => '/-----BEGIN [A-Z ]*PRIVATE KEY-----/',
    'senha escrita no código' => '/\b(senha|password|passwd|pwd)\b\s*[:=]>?\s*[\'"](?![$#{])[^\'"\s$]{6,}[\'"]/i',
    'chave de API ou token' => '/\b(vce_(?!0{16})[A-Za-z0-9]{16,}|sk_live_[A-Za-z0-9]{8,}|ghp_[A-Za-z0-9]{20,}|AKIA[0-9A-Z]{16}|xox[baprs]-[A-Za-z0-9-]{10,})\b/',
    'endereço IP' => '/(?<![\w.])(?!127\.0\.0\.1(?![\w.]))(?!0\.0\.0\.0(?![\w.]))(25[0-5]|2[0-4]\d|1?\d?\d)(\.(25[0-5]|2[0-4]\d|1?\d?\d)){3}(?![\w.])/',
    'porta local' => '/\blocalhost:\d{2,5}\b|127\.0\.0\.1:\d{2,5}/',
    'caminho de disco ou de servidor' => '#\b[A-Z]:[\\\\/](Users|xampp|wamp|laragon|inetpub)[\\\\/]|(?<![\w/])/home/[a-z][a-z0-9_-]+/|(?<![\w/])/var/www/[a-z]#i',
    'nome de código da pasta de trabalho' => '/\becm\b/i',
];
/* as regras de IP, senha e chave não valem dentro das bibliotecas de terceiros (o código delas é o publicado por elas:
   número de versão parece IP, e a documentação delas tem exemplos de senha); os nomes, os segredos e o resto valem */
const SO_NOSSO = ['senha escrita no código', 'chave de API ou token', 'endereço IP'];
$deTerceiros = static fn(string $rel): bool => str_starts_with($rel, 'assets/plugins/') || str_contains($rel, '/vendor/');
foreach ($lista('VCOSTAEDU_VARRER_NOMES') as $s) {
    $regras['nome real (do ambiente)'][] = $s;
}
foreach ($lista('VCOSTAEDU_VARRER_SEGREDOS') as $s) {
    $regras['segredo ou nome de banco (do ambiente)'][] = $s;
}
/* o que é exemplo ou valor padrão de propósito, com o motivo */
const ACEITOS = [
    '/exemplo\.invalid|prova\.invalid/',                          // os domínios de exemplo
    '/data-vc-senha/',                                            // o atributo do componente de senha
    '/chave-somente-local-123/',                                  // a senha só de prova, da instalação de prova local
    '#C:[\\\\/]xampp[\\\\/](mysql|php)[\\\\/]#i',                 // o padrão do XAMPP como valor de reserva das ferramentas
    '#/home/conta/#',                                             // o exemplo de pasta nas Configs de exemplo
    '#/home/guia/#',                                              // a rota do guia no portal (home/guia/<guia>), não uma pasta
    '/127\.0\.0\.1:9443/',                                        // a origem fictícia da prova do iframe (nenhum servidor)
    '/localhost:8080/',                                           // o texto da sabotagem da varredura dos vídeos
    "/'-inexistente'|'errada'/",                                    // a marca de "sem senha" e a senha errada de propósito, nas provas
    '/\bEx\.:|\bex\.:/',                                          // os exemplos de endereço nos comentários das Configs
];
$achados = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($destino, FilesystemIterator::SKIP_DOTS));
$binario = '/\.(png|jpe?g|gif|ico|webp|pdf|zip|gz|woff2?|ttf|eot|otf|mp4|webm|jar|exe|dll|so)$/i';
foreach ($it as $f) {
    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($destino) + 1));
    if (preg_match($binario, $rel) || $f->getSize() > 3_000_000) {
        continue;
    }
    $txt = (string) file_get_contents($f->getPathname());
    if (str_contains($txt, "\0")) {
        continue;
    }
    foreach (preg_split('/\r?\n/', $txt) as $num => $linha) {
        foreach ($regras as $nome => $re) {
            if (in_array($nome, SO_NOSSO, true) && $deTerceiros($rel)) {
                continue;
            }
            /* um nome só de letras casa como palavra inteira ("Ana" não acha "banana"); o resto, como trecho */
            $bateu = is_array($re)
                ? (bool) array_filter($re, fn($s) => preg_match('/^[\p{L} ]+$/u', $s) ? preg_match('/(?<![\p{L}])' . preg_quote($s, '/') . '(?![\p{L}])/iu', $linha) : stripos($linha, $s) !== false)
                : (bool) preg_match($re, $linha);
            if (!$bateu) {
                continue;
            }
            foreach (ACEITOS as $ok) {
                if (preg_match($ok, $linha)) {
                    continue 2;
                }
            }
            /* a linha do achado vai ao relatório sem o segredo: só onde está */
            $achados[$nome][] = $rel . ':' . ($num + 1);
        }
    }
}
$total = array_sum(array_map('count', $achados));
$rel = "A VARREDURA DA PASTA LIMPA\nCommit exportado: {$commit}\nArquivos exportados: {$n} (sem " . implode(', ', FORA_DO_PACOTE) . " e sem provas/)\n"
    . "Nomes reais procurados (do ambiente): " . count($lista('VCOSTAEDU_VARRER_NOMES')) . "; segredos procurados (do ambiente): "
    . count($lista('VCOSTAEDU_VARRER_SEGREDOS')) . "\n\n";
foreach (array_keys($regras) as $nome) {
    $q = count($achados[$nome] ?? []);
    $rel .= sprintf("%-40s %d\n", $nome, $q);
    foreach (array_slice($achados[$nome] ?? [], 0, 40) as $onde) {
        $rel .= "    {$onde}\n";
    }
}
$rel .= "\nTotal: {$total} achado(s)\n";
file_put_contents($destino . '-varredura.txt', $rel);
echo $rel;
exit($total > 0 ? 1 : 0);
