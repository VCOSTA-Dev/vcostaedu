<?php
/*
 * CENTRAL DE GUIAS (E6c). Gera, a partir dos guias curtos por tarefa em docs/guia/tarefas/*.md
 * (e das imagens de docs/guia/img/):
 *   - painel/app/adms/Views/ajuda/guias/<slug>.php e portal/app/adms/Views/ajuda/guias/<slug>.php:
 *     o HTML de cada guia (protegido pela constante do app), já ESCAPADO aqui — o controller só o
 *     inclui; nada de Markdown interpretado em tempo de requisição;
 *   - .../guias/_indice.php de cada app: título, resumo, perfis, ordem, "Comece por aqui",
 *     relacionados, telas (mapa tela → guia), versões e imagens de cada guia;
 *   - docs/guia/pdf/guia_<perfil>.pdf: o PDF de cada perfil, compilado dos guias curtos, com capa
 *     e "Comece por aqui" (Chrome headless).
 *
 * Cabeçalho de cada guia (entre linhas "---"):
 *   titulo, resumo, perfis (administrador, secretaria, professor, aluno), ordem, entrou (versão),
 *   mudou (versão, opcional), comece (ex.: "secretaria=3, administrador=5"), relacionados (slugs),
 *   telas (rotas controller/metodo em que o ícone de ajuda leva a este guia; "?tipo=FINAL" distingue a
 *   mesma tela por tipo, D-217), tipo (Onda 4: escola ou superior — o guia só aparece na instalação desse tipo de
 *   instituição; vazio = nos dois).
 * Corpo: "## Para que serve", "## Antes de começar", "## Passo a passo", "## O que pode dar errado".
 * Marcas: ![legenda](img/arquivo.png) · [texto](guia:slug) · [PRINT: descrição] (print a fazer, D-157).
 *
 * O gerador RECUSA (sai com erro e não grava nada) guia sem as 4 seções, perfil desconhecido,
 * relacionado ou link guia: inexistente ou de outro perfil, imagem que não existe.
 *
 * Uso: php database/ferramentas/guias/gerar_guias.php [caminho do chrome.exe]
 */

require dirname(__DIR__) . '/comum.php';

$raiz = str_replace('\\', '/', dirname(__DIR__, 3));
$chrome = $argv[1] ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe';
$versao = (string) (vcostaedu_pdo()->query('SELECT numero FROM adms_versoes WHERE situacao = 1 ORDER BY id DESC LIMIT 1')->fetchColumn() ?: '—');
$data = date('d/m/Y');
const PERFIS = ['administrador' => 'do Administrador', 'secretaria' => 'da Secretaria', 'professor' => 'do Professor', 'aluno' => 'do Aluno'];
const SECOES = ['Para que serve', 'Antes de começar', 'Passo a passo', 'O que pode dar errado'];

/* ------------------------------------------------------------------ leitura */
$guias = [];
$erros = [];
foreach (glob("{$raiz}/docs/guia/tarefas/*.md") ?: [] as $arq) {
    $slug = basename($arq, '.md');
    if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug)) {
        $erros[] = "{$slug}: nome de arquivo deve ser só minúsculas, números e hífen";
        continue;
    }
    $txt = str_replace("\r\n", "\n", (string) file_get_contents($arq));
    if (!preg_match('/^---\n(.*?)\n---\n(.*)$/s', $txt, $m)) {
        $erros[] = "{$slug}: falta o cabeçalho entre linhas ---";
        continue;
    }
    $c = [];
    foreach (explode("\n", $m[1]) as $l) {
        if (preg_match('/^([a-z]+):\s*(.*)$/', trim($l), $x)) {
            $c[$x[1]] = trim($x[2]);
        }
    }
    $lista = fn($k) => array_values(array_filter(array_map('trim', explode(',', $c[$k] ?? '')), 'strlen'));
    $comece = [];
    foreach ($lista('comece') as $par) {
        [$p, $n] = array_pad(explode('=', $par, 2), 2, '');
        $comece[trim($p)] = (int) $n;
    }
    $guias[$slug] = [
        'titulo' => $c['titulo'] ?? '', 'resumo' => $c['resumo'] ?? '', 'perfis' => $lista('perfis'), 'ordem' => (int) ($c['ordem'] ?? 999),
        'entrou' => $c['entrou'] ?? '', 'mudou' => $c['mudou'] ?? '', 'comece' => $comece, 'relacionados' => $lista('relacionados'),
        'telas' => $lista('telas'), 'corpo' => $m[2], 'imagens' => [], 'links' => [],
        'tipo' => trim((string) ($c['tipo'] ?? '')), // Onda 4: escola | superior (vazio = os dois tipos de instituição)
    ];
    foreach (['titulo', 'resumo', 'entrou'] as $k) {
        if (($c[$k] ?? '') === '') {
            $erros[] = "{$slug}: falta \"{$k}\" no cabeçalho";
        }
    }
    foreach ($guias[$slug]['perfis'] as $p) {
        if (!isset(PERFIS[$p])) {
            $erros[] = "{$slug}: perfil desconhecido \"{$p}\"";
        }
    }
    if (!$guias[$slug]['perfis']) {
        $erros[] = "{$slug}: nenhum perfil";
    }
    foreach ($comece as $p => $n) {
        if (!in_array($p, $guias[$slug]['perfis'], true) || $n < 1) {
            $erros[] = "{$slug}: \"comece\" de {$p} inválido (o perfil precisa estar em perfis; posição a partir de 1)";
        }
    }
    foreach (SECOES as $s) {
        if (!preg_match('/^## ' . preg_quote($s, '/') . '\s*$/m', $m[2])) {
            $erros[] = "{$slug}: falta a seção \"## {$s}\"";
        }
    }
    /* D-106: o tipo é escola, superior, livre ou uma lista com vírgula deles ("escola,superior": todos, menos o curso livre) */
    if ($guias[$slug]['tipo'] !== '' && array_diff(array_map('trim', explode(',', $guias[$slug]['tipo'])), ['escola', 'superior', 'livre'])) {
        $erros[] = "{$slug}: tipo \"{$guias[$slug]['tipo']}\" deve ser escola, superior, livre (ou uma lista com vírgula) ou vazio";
    }
    foreach ($guias[$slug]['telas'] as $t) {
        if (!preg_match('#^[a-z0-9-]+/[a-z0-9-]+(\?tipo=[A-Z]+)?$#', $t)) {
            $erros[] = "{$slug}: tela \"{$t}\" deve ser controller/metodo (com ?tipo=X opcional), como na URL";
        }
    }
    preg_match_all('/\(img\/([a-z0-9_-]+\.png)\)/', $m[2], $im);
    foreach ($im[1] as $img) {
        $guias[$slug]['imagens'][] = $img;
        if (!is_file("{$raiz}/docs/guia/img/{$img}")) {
            $erros[] = "{$slug}: imagem inexistente img/{$img}";
        }
    }
    preg_match_all('/\]\(guia:([a-z0-9-]+)\)/', $m[2], $lk);
    $guias[$slug]['links'] = $lk[1];
}
$posicoes = [];
foreach ($guias as $slug => $g) {
    foreach (array_merge($g['relacionados'], $g['links']) as $alvo) {
        if (!isset($guias[$alvo])) {
            $erros[] = "{$slug}: aponta para o guia inexistente \"{$alvo}\"";
        } elseif (!array_intersect($g['perfis'], $guias[$alvo]['perfis'])) {
            $erros[] = "{$slug}: aponta para \"{$alvo}\", que nenhum perfil deste guia pode ver";
        }
    }
    foreach ($g['comece'] as $p => $n) {
        if (isset($posicoes[$p][$n])) {
            $erros[] = "{$slug}: posição {$n} do \"Comece por aqui\" de {$p} repetida (também em {$posicoes[$p][$n]})";
        }
        $posicoes[$p][$n] = $slug;
    }
}
/* o ícone de ajuda de uma tela leva a UM guia por perfil */
$telaPerfil = [];
foreach ($guias as $slug => $g) {
    foreach ($g['telas'] as $t) {
        foreach ($g['perfis'] as $p) {
            if (isset($telaPerfil["{$t}|{$p}"])) {
                $erros[] = "{$slug}: a tela {$t} já leva a \"{$telaPerfil["{$t}|{$p}"]}\" para {$p}";
            }
            $telaPerfil["{$t}|{$p}"] = $slug;
        }
    }
}
if (!$guias) {
    $erros[] = 'nenhum guia em docs/guia/tarefas/';
}
if ($erros) {
    fwrite(STDERR, "Guias RECUSADOS (nada foi gravado):\n  - " . implode("\n  - ", $erros) . "\n");
    exit(1);
}

/* ---------------------------------------------------- Markdown (subconjunto) → HTML */
/** Tudo é escapado; só as marcas abaixo viram HTML. $img(nome) e $guia(slug) dão as URLs. */
function md(string $texto, callable $img, callable $guia): string
{
    $inline = function (string $t) use ($guia): string {
        $t = htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
        $t = preg_replace('/`([^`]+)`/', '<code>$1</code>', $t);
        $t = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $t);
        $t = preg_replace('/(?<![\w*])\*([^*\n]+)\*(?![\w*])/', '<em>$1</em>', $t);
        $t = preg_replace_callback('/\[([^\]]+)\]\(guia:([a-z0-9-]+)\)/', fn($m) => '<a class="guia-interno" data-guia="' . $m[2] . '" href="' . $guia($m[2]) . '">' . $m[1] . '</a>', $t);
        return preg_replace('/\[([^\]]+)\]\((https?:[^)\s]+)\)/', '<a href="$2" rel="noopener" target="_blank">$1</a>', $t);
    };
    $linhas = preg_split('/\r?\n/', $texto);
    $html = '';
    $par = [];
    $fechaPar = function () use (&$par, &$html, $inline) {
        if ($par) {
            $html .= '<p>' . $inline(implode(' ', $par)) . "</p>\n";
            $par = [];
        }
    };
    for ($i = 0; $i < count($linhas); $i++) {
        $l = $linhas[$i];
        if (trim($l) === '') {
            $fechaPar();
            continue;
        }
        if (str_starts_with($l, '```')) {
            $fechaPar();
            $bloco = [];
            for ($i++; $i < count($linhas) && !str_starts_with($linhas[$i], '```'); $i++) {
                $bloco[] = $linhas[$i];
            }
            $html .= '<pre><code>' . htmlspecialchars(implode("\n", $bloco), ENT_QUOTES, 'UTF-8') . "</code></pre>\n";
            continue;
        }
        if (preg_match('/^\[PRINT: (.+)\]$/', trim($l), $m)) {
            $fechaPar();
            $html .= '<div class="print-pendente"><i class="fas fa-camera"></i> Print a fazer antes do treinamento (D-157): ' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . "</div>\n";
            continue;
        }
        if (preg_match('/^(#{2,4}) (.+)$/', $l, $m)) {
            $fechaPar();
            $n = strlen($m[1]);
            $html .= "<h{$n}>" . $inline($m[2]) . "</h{$n}>\n";
            continue;
        }
        if (preg_match('/^!\[([^\]]*)\]\(img\/([a-z0-9_-]+\.png)\)$/', trim($l), $m)) {
            $fechaPar();
            $html .= '<figure><img src="' . $img($m[2]) . '" alt="' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '"><figcaption>' . $inline($m[1]) . "</figcaption></figure>\n";
            continue;
        }
        if (str_starts_with($l, '|')) {
            $fechaPar();
            $rows = [];
            for (; $i < count($linhas) && str_starts_with($linhas[$i], '|'); $i++) {
                $rows[] = array_map('trim', explode('|', trim($linhas[$i], " |")));
            }
            $i--;
            $html .= "<table>\n<thead><tr>" . implode('', array_map(fn($c) => '<th>' . $inline($c) . '</th>', $rows[0])) . "</tr></thead>\n<tbody>\n";
            foreach (array_slice($rows, 2) as $r) {
                $html .= '<tr>' . implode('', array_map(fn($c) => '<td>' . $inline($c) . '</td>', $r)) . "</tr>\n";
            }
            $html .= "</tbody>\n</table>\n";
            continue;
        }
        if (str_starts_with($l, '> ')) {
            $fechaPar();
            $bloco = [];
            for (; $i < count($linhas) && str_starts_with($linhas[$i], '> '); $i++) {
                $bloco[] = substr($linhas[$i], 2);
            }
            $i--;
            $html .= '<blockquote>' . $inline(implode(' ', $bloco)) . "</blockquote>\n";
            continue;
        }
        if (preg_match('/^(\d+\.|-) /', $l, $m)) {
            $fechaPar();
            $tag = $m[1] === '-' ? 'ul' : 'ol';
            $html .= "<{$tag}>\n";
            $aberto = false;
            for (; $i < count($linhas); $i++) {
                $x = $linhas[$i];
                if (preg_match('/^  (?:-|\d+\.) (.+)$/', $x, $s)) {
                    if (!$aberto) {
                        $html .= "<ul>\n";
                        $aberto = true;
                    }
                    $html .= '<li>' . $inline($s[1]) . "</li>\n";
                    continue;
                }
                if (!preg_match('/^(?:\d+\.|-) (.+)$/', $x, $s)) {
                    break;
                }
                if ($aberto) {
                    $html .= "</ul>\n";
                    $aberto = false;
                }
                $html .= '<li>' . $inline($s[1]) . "</li>\n";
            }
            if ($aberto) {
                $html .= "</ul>\n";
            }
            $i--;
            $html .= "</{$tag}>\n";
            continue;
        }
        $par[] = trim($l);
    }
    $fechaPar();
    return $html;
}

/* ------------------------------------------------------ Ajuda do painel e do portal */
$videos = (array) (json_decode((string) @file_get_contents("{$raiz}/docs/guia/videos.json"), true)['videos'] ?? []); // 1.18.0
$apps = ['painel' => ['VCOSTAEDU', ['administrador', 'secretaria', 'professor']], 'portal' => ['VCOSTAEDU', ['aluno']]];
foreach ($apps as $app => [$constante, $perfisApp]) {
    $dir = "{$raiz}/{$app}/app/adms/Views/ajuda/guias/";
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    foreach (glob($dir . '*.php') ?: [] as $velho) {
        unlink($velho);
    }
    $indice = ['versao' => $versao, 'gerado' => $data, 'guias' => []];
    $n = 0;
    foreach ($guias as $slug => $g) {
        if (!array_intersect($g['perfis'], $perfisApp)) {
            continue;
        }
        $html = md($g['corpo'], fn($i) => '{{IMG}}' . $i, fn($s) => '{{GUIA}}' . $s);
        file_put_contents($dir . $slug . '.php', "<?php if (!defined('{$constante}')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>\n" . $html);
        $meta = $g;
        unset($meta['corpo'], $meta['links']);
        $meta['perfis'] = array_values(array_intersect($g['perfis'], $perfisApp));
        $meta['relacionados'] = array_values(array_filter($g['relacionados'], fn($r) => array_intersect($guias[$r]['perfis'], $perfisApp)));
        $meta['comece'] = array_intersect_key($g['comece'], array_flip($perfisApp));
        /* 1.18.0 (D-475): o vídeo do guia, do manifesto docs/guia/videos.json (gravado por tests/videos/gravar.js). Os
           arquivos ficam em assets/videos/guias/ (fora do git); a tela só mostra o player se o mp4 existir lá. */
        if (isset($videos[$slug]['arquivo'])) {
            $v = $videos[$slug];
            $meta['video'] = ['arquivo' => (string) $v['arquivo'], 'poster' => (string) ($v['poster'] ?? ''), 'legenda' => (string) ($v['legenda'] ?? ''),
                'capitulos_vtt' => (string) ($v['capitulos_vtt'] ?? ''), 'duracao' => (float) ($v['duracao'] ?? 0), 'versao' => (string) ($v['versao'] ?? ''),
                'capitulos' => array_map(fn($c) => ['inicio' => (float) $c['inicio'], 'titulo' => (string) $c['titulo']], (array) ($v['capitulos'] ?? [])),
                'transcricao' => (string) ($v['transcricao'] ?? '')];
        }
        $indice['guias'][$slug] = $meta;
        $n++;
    }
    file_put_contents($dir . '_indice.php', "<?php\n/* GERADO por database/ferramentas/guias/gerar_guias.php a partir de docs/guia/tarefas/ — não editar. */\n"
        . "if (!defined('{$constante}')) { header('Location: /'); die('Erro: Página não encontrada!'); }\nreturn " . var_export($indice, true) . ";\n");
    echo "Ajuda do {$app}: {$n} guias em {$app}/app/adms/Views/ajuda/guias/\n";
}

/* ------------------------------------------------------------- PDF por perfil */
$css = <<<CSS
body{font-family:"Segoe UI",Roboto,Arial,sans-serif;color:#1f2933;line-height:1.5;font-size:12.5px;margin:0}
main{padding:0 4px}h1{font-size:30px;margin:0 0 8px}h2{font-size:17px;margin:18px 0 6px}h3{font-size:15px}
section.guia{break-before:page}section.guia>h1{font-size:22px;border-bottom:2px solid #3c8dbc;padding-bottom:4px}
.meta{color:#52606d;font-size:11px;margin-bottom:8px}
table{border-collapse:collapse;width:100%;margin:10px 0;font-size:12px;break-inside:avoid}th,td{border:1px solid #cfd6dd;padding:5px 7px;text-align:left;vertical-align:top}th{background:#eef1f4}
figure{margin:12px 0;break-inside:avoid}figure img{max-width:100%;border:1px solid #cfd6dd}figcaption{font-size:11px;color:#52606d}
blockquote{margin:10px 0;padding:8px 12px;background:#eaf4ff;border-left:4px solid #3c8dbc}
code,pre{font-family:Consolas,monospace;background:#f0f2f5}pre{padding:8px}
.print-pendente{border:2px dashed #adb5bd;background:#f8f9fa;color:#6c757d;padding:10px;margin:10px 0}
.capa{height:250mm;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center}
.capa p{font-size:15px;color:#52606d;margin:4px}.comece{break-before:page}.comece ol li{margin:6px 0}
.rel{font-size:11px;color:#52606d;border-top:1px solid #e3e7eb;margin-top:12px;padding-top:6px}
@page{size:A4;margin:14mm 12mm}
CSS;
$chromeOk = is_file($chrome);
foreach (PERFIS as $perfil => $rotulo) {
    $meus = array_filter($guias, fn($g) => in_array($perfil, $g['perfis'], true));
    $comece = array_filter($meus, fn($g) => isset($g['comece'][$perfil]));
    uasort($comece, fn($a, $b) => $a['comece'][$perfil] <=> $b['comece'][$perfil]);
    $resto = array_diff_key($meus, $comece);
    uasort($resto, fn($a, $b) => [$a['ordem'], $a['titulo']] <=> [$b['ordem'], $b['titulo']]);
    $ordem = $comece + $resto;
    $titulo = 'VCOSTAEDU — Guia ' . $rotulo;
    $img = fn($n) => 'file:///' . str_replace('\\', '/', realpath("{$raiz}/docs/guia/img/{$n}"));
    $ancora = fn($s) => '#g-' . $s;
    $html = "<!doctype html><html lang=\"pt-BR\"><head><meta charset=\"utf-8\"><title>" . htmlspecialchars($titulo) . "</title><style>{$css}</style></head><body><main>"
        . '<section class="capa"><h1>' . htmlspecialchars($titulo) . "</h1><p>Guias curtos por tarefa</p><p>Versão {$versao}</p><p>{$data}</p><p style=\"margin-top:40px;font-size:12px\">Telas da demonstração, com dados fictícios. No sistema: menu Ajuda.</p></section>"
        . '<section class="comece"><h1>Comece por aqui</h1><ol>' . implode('', array_map(fn($s, $g) => '<li><a href="' . $ancora($s) . '">' . htmlspecialchars($g['titulo']) . '</a> — ' . htmlspecialchars($g['resumo']) . '</li>', array_keys($comece), $comece)) . '</ol>'
        . ($resto ? '<h2>Outros guias</h2><ul>' . implode('', array_map(fn($s, $g) => '<li><a href="' . $ancora($s) . '">' . htmlspecialchars($g['titulo']) . '</a></li>', array_keys($resto), $resto)) . '</ul>' : '') . '</section>';
    foreach ($ordem as $slug => $g) {
        $rel = array_filter($g['relacionados'], fn($r) => in_array($perfil, $guias[$r]['perfis'], true));
        $html .= '<section class="guia" id="g-' . $slug . '"><h1>' . htmlspecialchars($g['titulo']) . '</h1><div class="meta">desde a versão ' . htmlspecialchars($g['entrou']) . ($g['mudou'] ? ' · atualizado na ' . htmlspecialchars($g['mudou']) : '') . '</div>'
            . preg_replace_callback('#<a class="guia-interno" data-guia="([a-z0-9-]+)" href="[^"]*">(.*?)</a>#s', fn($m) => isset($ordem[$m[1]]) ? $m[0] : $m[2] . ' (guia de outro perfil)', md($g['corpo'], $img, $ancora))
            . ($rel ? '<div class="rel">Relacionados: ' . implode(' · ', array_map(fn($r) => '<a href="' . $ancora($r) . '">' . htmlspecialchars($guias[$r]['titulo']) . '</a>', $rel)) . '</div>' : '') . '</section>';
    }
    $html .= '</main></body></html>';
    if (!$chromeOk) {
        echo "  PDF de {$perfil} não gerado: Chrome não encontrado em {$chrome}\n";
        continue;
    }
    $tmp = sys_get_temp_dir() . "/vcostaedu_guia_{$perfil}.html";
    file_put_contents($tmp, $html);
    $pdf = "{$raiz}/docs/guia/pdf/guia_{$perfil}.pdf";
    @unlink($pdf);
    $cmd = escapeshellarg($chrome) . ' --headless=new --disable-gpu --no-pdf-header-footer --allow-file-access-from-files'
        . ' --print-to-pdf=' . escapeshellarg($pdf) . ' ' . escapeshellarg('file:///' . str_replace('\\', '/', $tmp)) . ' 2>&1';
    exec($cmd, $saida, $rc);
    @unlink($tmp);
    echo is_file($pdf) ? "  PDF: docs/guia/pdf/guia_{$perfil}.pdf (" . count($ordem) . ' guias, ' . round(filesize($pdf) / 1024) . " KB)\n" : "  FALHA no PDF de {$perfil} (código {$rc})\n";
}
