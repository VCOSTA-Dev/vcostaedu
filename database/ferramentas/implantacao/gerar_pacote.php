<?php
/*
 * GERAR O PACOTE DE IMPLANTAÇÃO (E8, D-242). Roda no computador de quem entrega, dentro do repositório.
 *
 * Uso: php database/ferramentas/implantacao/gerar_pacote.php [--saida=PASTA] [--permitir-sujo] [--seguranca]
 *   --seguranca: a versão corrige falha de segurança (vai no versao.json; a instalação mais antiga mostra o destaque).
 *
 * Gera vcostaedu-<versão>-<data>.zip e, ao lado, o .sha256 do zip. Dentro do zip, na pasta vcostaedu-<versão>-<data>/:
 * sistema/ (os arquivos que vão para a pasta publicada), PACOTE.txt (versão, commit, data) e MANIFESTO.sha256
 * (sha256 de cada arquivo de sistema/). PACOTE.txt e MANIFESTO.sha256 ficam FORA de sistema/: não vão para a web.
 *
 * Base: os arquivos VERSIONADOS no git (git ls-files), lidos da pasta de trabalho — por isso recusa se houver
 * mudança não commitada (a não ser com --permitir-sujo). Assim, Config.php, storage com dados, node_modules,
 * certificados e o que mais estiver fora do git não entram. Ficam fora, também, por regra:
 *   - .git*, retorno.md, composer.json/composer.lock;
 *   - docs/ de trabalho (DECISIONS, PROJECT_STATE, …), as fontes dos guias (docs/guia/tarefas) e os PDFs dos guias;
 *     entram docs/.htaccess, docs/guia/img/ (a Ajuda mostra essas imagens), docs/IMPLANTACAO.md e docs/nginx/ (o exemplo do Nginx);
 *   - a demonstração (database/seed_demo.sql, limpar_demo.php, seed_demo_processar.php), as ferramentas de guia
 *     (database/ferramentas/guias/) e tests/ (roteiros e o gravador dos vídeos), que só servem ao desenvolvimento.
 * 1.18.0 (D-476): entram TAMBÉM os vídeos dos guias (assets/videos/guias/: mp4, poster, legendas e transcrição), que
 * ficam fora do git: lidos da pasta, conferidos contra o manifesto docs/guia/videos.json (faltando, ALERTA).
 * Uma última conferência recusa o pacote se algum caminho proibido escapar (Config.php, .git, chaves, etc.).
 * Onda 6, Etapa 7 (docs/VERSAO_JSON.md): a versão é a dos arquivos (VcLegal::versaoDoPacote, a mesma da tela Atualizar o
 * sistema); ao lado do zip, NOTAS-<versão>.md (as novidades da etapa do banco daquela versão, com o código de conferência)
 * e <nome>.versao.json (o formato que o site publica); as notas vão também dentro do zip, ao lado do PACOTE.txt.
 */
require __DIR__ . '/_base.php';

$o = vcostaedu_args($argv);

/**
 * As notas da versão: o resumo e as novidades (título e texto) da etapa do banco que registra a versão. Sem a etapa,
 * só o título. @return array{md: string, data: string, quantas: int}
 */
function vcostaedu_notas(string $raiz, string $versao): array
{
    $q = "'((?:[^']|'')*)'";
    foreach (glob("$raiz/database/migrations/*.sql") ?: [] as $arq) {
        $s = (string) file_get_contents($arq);
        if (!preg_match("/SELECT (\\d+), '" . preg_quote($versao, '/') . "', \\d+, \\d+, \\d+, '([0-9-]+)', {$q}/", $s, $v)) {
            continue;
        }
        [$id, $data, $resumo] = [$v[1], $v[2], str_replace("''", "'", $v[3])];
        preg_match_all("/SELECT \\d+, {$id}, {$q}, {$q}, \\d+, {$q}/", $s, $nov, PREG_SET_ORDER);
        $md = "# VCOSTAEDU {$versao}\n\n{$data} · {$resumo}\n\n## O que muda\n\n";
        foreach ($nov as $n) {
            $md .= '- **' . str_replace("''", "'", $n[1]) . ':** ' . str_replace("''", "'", $n[2]) . "\n";
        }
        return ['md' => $md, 'data' => $data, 'quantas' => count($nov)];
    }
    return ['md' => "# VCOSTAEDU {$versao}\n", 'data' => '', 'quantas' => 0];
}
$raiz = realpath(VCOSTAEDU_RAIZ);
$saida = rtrim(str_replace('\\', '/', (string) ($o['saida'] ?? dirname($raiz) . '/vcostaedu_pacotes')), '/');

Saida::secao('Origem');
$git = fn(string $c) => trim((string) shell_exec('git -C ' . escapeshellarg($raiz) . ' ' . $c . ' 2>&1'));
$commit = $git('rev-parse --short HEAD');
if (!preg_match('/^[0-9a-f]{7,}$/', $commit)) {
    Saida::falha("não é um repositório git utilizável ({$commit})");
    Saida::fim();
}
$sujo = $git('status --porcelain --untracked-files=no');
if ($sujo !== '' && empty($o['permitir-sujo'])) {
    Saida::falha("há mudanças não commitadas em arquivos versionados; faça o commit ou use --permitir-sujo:\n" . $sujo);
    Saida::fim();
}
$sujo !== '' ? Saida::alerta('pacote gerado com mudanças não commitadas (--permitir-sujo)') : Saida::ok("commit {$commit}, sem mudanças pendentes");

require_once "$raiz/core/Legal.php";
$versao = (string) (\VcLegal::versaoDoPacote() ?: '0.0.0');
Saida::ok("versão {$versao} (a dos arquivos: a etapa do banco mais nova)");

$fora = [
    '#^\.git#', '#(^|/)\.gitignore$#', '#^retorno\.md$#', '#(^|/)composer\.(json|lock)$#',
    '#^docs/(?!\.htaccess$|guia/img/|IMPLANTACAO\.md$|nginx/)#', // Onda 8, 2.10 (D-96): o guia do servidor e o exemplo do Nginx entram
    '#^database/seed_demo\.sql$#', '#^database/ferramentas/(limpar_demo|seed_demo_processar)\.php$#', '#^database/ferramentas/guias/#',
    '#^tests/#', // 1.18.0: roteiros de teste e o gravador dos vídeos só servem ao desenvolvimento
    '#^exemplos/#', // Onda 3, Etapa 9: os exemplos de integração (o portal de matrícula) ficam fora do produto
];
$proibidos = '#(^|/)(\.git/|Config\.php$|node_modules/|\.env$)|\.(pem|key|crt|p12|pfx|sql\.gz|zip|bak|log)$#i';

$lista = array_filter(explode("\n", str_replace("\r", '', $git('ls-files'))), 'strlen');
$arquivos = [];
foreach ($lista as $rel) {
    foreach ($fora as $re) {
        if (preg_match($re, $rel)) {
            continue 2;
        }
    }
    if (!is_file("$raiz/$rel")) {
        continue;
    }
    $arquivos[] = $rel;
}
sort($arquivos);
$escapou = array_values(array_filter($arquivos, fn($r) => preg_match($proibidos, $r)));
if ($escapou) {
    Saida::falha('caminho proibido no pacote: ' . implode(', ', array_slice($escapou, 0, 10)));
    Saida::fim();
}
Saida::ok(count($arquivos) . ' arquivos versionados entram (de ' . count($lista) . '); nenhum caminho proibido');

/* 1.18.0 (D-476): os vídeos dos guias, da pasta (fora do git), conferidos contra o manifesto */
Saida::secao('Vídeos dos guias (assets/videos/guias/)');
$manVideos = (array) (json_decode((string) @file_get_contents("$raiz/docs/guia/videos.json"), true)['videos'] ?? []);
$videos = [];
$faltam = [];
foreach ($manVideos as $slug => $v) {
    foreach (['arquivo', 'poster', 'legenda', 'capitulos_vtt'] as $k) {
        $rel = 'assets/videos/guias/' . basename((string) ($v[$k] ?? ''));
        if (($v[$k] ?? '') === '') {
            continue;
        }
        is_file("$raiz/$rel") ? $videos[] = $rel : $faltam[] = $rel;
    }
}
$videos = array_values(array_unique($videos));
$tamVideos = array_sum(array_map(fn($r) => filesize("$raiz/$r"), $videos));
$faltam ? Saida::alerta(count($faltam) . ' arquivo(s) do manifesto não estão na pasta (grave com tests/videos/gravar.js): ' . implode(', ', array_slice($faltam, 0, 8)))
    : Saida::ok(count($manVideos) . ' vídeo(s) do manifesto, ' . count($videos) . ' arquivo(s), ' . vcostaedu_tam($tamVideos));
$arquivos = array_merge($arquivos, $videos);

Saida::secao('Pacote');
if (!is_dir($saida) && !@mkdir($saida, 0775, true)) {
    Saida::falha("não foi possível criar a pasta de saída {$saida}");
    Saida::fim();
}
if (str_starts_with(str_replace('\\', '/', realpath($saida)) . '/', str_replace('\\', '/', $raiz) . '/')) {
    Saida::falha('a pasta de saída não pode ficar dentro do repositório');
    Saida::fim();
}
$nome = "vcostaedu-{$versao}-" . date('Ymd-His');
$zipArq = "{$saida}/{$nome}.zip";
$zip = new ZipArchive();
if ($zip->open($zipArq, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
    Saida::falha("não foi possível criar {$zipArq}");
    Saida::fim();
}
$manifesto = [];
$total = 0;
foreach ($arquivos as $rel) {
    $manifesto[] = hash_file('sha256', "$raiz/$rel") . "  sistema/{$rel}";
    $total += filesize("$raiz/$rel");
    $zip->addFile("$raiz/$rel", "{$nome}/sistema/{$rel}");
}
$info = "VCOSTAEDU — pacote de implantação\nversão: {$versao}\ncommit: {$commit}\ngerado em: " . date('Y-m-d H:i:s') . "\narquivos: " . count($arquivos)
    . "\n\nSiga sistema/README.md (Instalar e Atualizar). Só a pasta sistema/ vai para o servidor web. Conferir: sha256sum -c MANIFESTO.sha256 (nesta pasta).\n";
$zip->addFromString("{$nome}/PACOTE.txt", $info);
$zip->addFromString("{$nome}/MANIFESTO.sha256", implode("\n", $manifesto) . "\n");
/* Onda 6, Etapa 7: as notas da versão (as novidades da etapa do banco dela) */
$notas = vcostaedu_notas($raiz, $versao);
$zip->addFromString("{$nome}/NOTAS-{$versao}.md", $notas['md']);
$zip->close();
$hz = hash_file('sha256', $zipArq);
file_put_contents("{$zipArq}.sha256", "{$hz}  {$nome}.zip\n");
$seguranca = !empty($o['seguranca']);
file_put_contents("{$saida}/NOTAS-{$versao}.md", $notas['md'] . "\nCódigo de conferência do pacote (sha256 de {$nome}.zip): `{$hz}`\n" . ($seguranca ? "\n**Esta versão corrige uma falha de segurança.**\n" : ''));
$vj = ['versao' => $versao, 'data' => $notas['data'] ?: date('Y-m-d'), 'notas' => "https://vcostaedu.com/versoes/{$versao}",
    'pacote' => "https://vcostaedu.com/pacotes/{$nome}.zip", 'sha256' => $hz, 'seguranca' => $seguranca];
file_put_contents("{$saida}/{$nome}.versao.json", json_encode($vj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
Saida::ok("NOTAS-{$versao}.md (" . $notas['quantas'] . ' novidade(s)) e ' . "{$nome}.versao.json" . ($seguranca ? ' — marcada: corrige falha de segurança' : ''));
Saida::ok("{$zipArq} (" . vcostaedu_tam(filesize($zipArq)) . '; conteúdo ' . vcostaedu_tam($total) . ')');
Saida::ok("sha256 do zip: {$hz} (em {$nome}.zip.sha256)");
Saida::info('Conferir no servidor: sha256sum -c ' . $nome . '.zip.sha256 ; depois, na pasta ' . $nome . '/: sha256sum -c MANIFESTO.sha256 (a pasta sistema/ é a que sobe)');

/* amostra do que ficou fora, para conferência */
$foraLista = array_values(array_diff($lista, $arquivos));
Saida::info(count($foraLista) . ' versionados ficaram fora; por exemplo: ' . implode(', ', array_slice(array_values(array_filter($foraLista, fn($r) => !str_starts_with($r, 'docs/guia/tarefas/'))), 0, 12)));
Saida::fim();
