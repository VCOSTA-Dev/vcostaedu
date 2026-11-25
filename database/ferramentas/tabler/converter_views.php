<?php
/*
 * O DE-PARA MECÂNICO DAS VIEWS: AdminLTE 3 / Bootstrap 4 → Tabler 1.4 / Bootstrap 5 (VCOSTAEDU).
 *
 *   php database/ferramentas/tabler/converter_views.php <arquivo.php> [...]            aplica e relata
 *   php database/ferramentas/tabler/converter_views.php --conferir <arquivo.php> [...] só relata (não grava)
 *
 * É o dicionário da §2 do docs/LEVANTAMENTO_DEPARA_TABLER.md, aplicado como ficou escrito em docs/DEPARA_APLICADO.md.
 * Mexe SÓ em marcação (casca da página, classes, atributos data-*); não toca em PHP, SQL nem texto. Idempotente:
 * rodar de novo num arquivo convertido não muda nada.
 *
 * O que ele NÃO resolve sozinho (sai no relatório como "manual", por arquivo): info-box, small-box, os widgets do
 * AdminLTE (data-widget, data-card-widget), custom-file, embed-responsive, media, jumbotron, e qualquer token do
 * dicionário que sobrar depois das trocas.
 *
 * Depois de converter uma view, o controller dela passa a chamar renderizar('tabler') (ou renderizarLogin('tabler')).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$conferir = in_array('--conferir', $argv, true);
$arquivos = array_values(array_filter(array_slice($argv, 1), static fn($a) => !str_starts_with($a, '--')));
if (!$arquivos) {
    fwrite(STDERR, "Uso: php database/ferramentas/tabler/converter_views.php [--conferir] <arquivo.php> [...]\n");
    exit(2);
}

/** Acha o fim (índice depois do fechamento) do elemento que abre em $inicio, contando tags do mesmo nome. */
function fimDoElemento(string $s, int $inicio, string $tag): ?int
{
    $profundidade = 0;
    $pos = $inicio;
    $re = '#<(/?)' . $tag . '\b[^>]*>#i';
    while (preg_match($re, $s, $m, PREG_OFFSET_CAPTURE, $pos)) {
        $profundidade += $m[1][0] === '/' ? -1 : 1;
        $pos = $m[0][1] + strlen($m[0][0]);
        if ($profundidade === 0) {
            return $pos;
        }
    }
    return null;
}

/** Tira um elemento invólucro (abertura e o fechamento correspondente), mantendo o conteúdo. */
function tirarInvolucro(string $s, string $reAbertura, string $tag, int &$n): string
{
    while (preg_match($reAbertura, $s, $m, PREG_OFFSET_CAPTURE)) {
        $ini = $m[0][1];
        $fim = fimDoElemento($s, $ini, $tag);
        if ($fim === null) {
            break;
        }
        $abertura = $m[0][0];
        $fechamento = '</' . $tag . '>';
        $miolo = substr($s, $ini + strlen($abertura), $fim - $ini - strlen($abertura) - strlen($fechamento));
        $s = substr($s, 0, $ini) . $miolo . substr($s, $fim);
        $n++;
    }
    return $s;
}

/** A casca da página: content-wrapper, content-header (com o h1 e o breadcrumb), section.content, container-fluid. */
function converterCasca(string $s, array &$rel): string
{
    $n = 0;
    $s = preg_replace('#<div class="content-wrapper[^"]*">#', '<div class="vc-pagina">', $s, -1, $n);
    $rel['casca'] += $n;

    while (preg_match('#<(div|section) class="content-header[^"]*">#', $s, $m, PREG_OFFSET_CAPTURE)) {
        $ini = $m[0][1];
        $fim = fimDoElemento($s, $ini, $m[1][0]);
        $bloco = $fim === null ? '' : substr($s, $ini, $fim - $ini);
        if ($fim === null || !preg_match('#<h1[^>]*>(.*?)</h1>#s', $bloco, $h)) {
            $rel['manual'][] = 'content-header sem <h1> reconhecível';
            break;
        }
        $titulo = trim($h[1]);
        $novo = "<?php ob_start(); ?>{$titulo}<?php \$paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>";
        $s = substr($s, 0, $ini) . $novo . substr($s, $fim);
        $rel['casca']++;
    }

    $s = preg_replace('#<section class="content">#', '<section class="page-body">', $s, -1, $n);
    $rel['casca'] += $n;
    $s = preg_replace('#\bcontainer-fluid\b#', 'container-xl', $s, -1, $n);
    $rel['casca'] += $n;
    return $s;
}

/** As trocas de classe e de atributo. */
function converterClasses(string $s, array &$rel): string
{
    $cores = 'primary|secondary|success|danger|warning|info|dark|teal|purple|indigo|navy|lightblue|olive|lime|fuchsia|maroon|orange|cyan|gray|light';
    $regras = [
        /* atributos do Bootstrap 4 → 5 */
        '#\bdata-(toggle|target|dismiss|placement|parent|spy|offset|ride|slide|interval)=#' => 'data-bs-$1=',
        /* botão de fechar (modal e alerta), com o × de dentro */
        '#<button([^>]*?)class="close"([^>]*)>\s*(?:<span[^>]*>\s*(?:&times;|×)\s*</span>|&times;|×)\s*</button>#u' => '<button$1class="btn-close"$2></button>',
        "#<button([^>]*?)class='close'([^>]*)>\s*(?:<span[^>]*>\s*(?:&times;|×)\s*</span>|&times;|×)\s*</button>#u" => "<button$1class='btn-close'$2></button>",
        /* espaçamento, alinhamento, flutuação */
        '#\b([mp])l-((?:sm-|md-|lg-|xl-)?(?:auto|n?[0-5]))\b#' => '$1s-$2',
        '#\b([mp])r-((?:sm-|md-|lg-|xl-)?(?:auto|n?[0-5]))\b#' => '$1e-$2',
        '#\bfloat-((?:sm-|md-|lg-|xl-)?)left\b#' => 'float-$1start',
        '#\bfloat-((?:sm-|md-|lg-|xl-)?)right\b#' => 'float-$1end',
        '#\btext-((?:sm-|md-|lg-|xl-)?)left\b#' => 'text-$1start',
        '#\btext-((?:sm-|md-|lg-|xl-)?)right\b#' => 'text-$1end',
        '#\bborder-left\b#' => 'border-start', '#\bborder-right\b#' => 'border-end',
        '#\brounded-left\b#' => 'rounded-start', '#\brounded-right\b#' => 'rounded-end',
        /* tipografia e utilitários */
        '#\bfont-weight-(bold|bolder|normal|light|lighter)\b#' => 'fw-$1',
        '#\bfont-italic\b#' => 'fst-italic',
        '#\bsr-only\b#' => 'visually-hidden',
        '#\bno-gutters\b#' => 'g-0',
        '#\bbtn-block\b#' => 'w-100',
        '#\bbtn-xs\b#' => 'btn-sm',
        '#\bthead-(light|dark)\b#' => 'table-$1',
        '#\bimg-circle\b#' => 'rounded-circle',
        '#\belevation-[1-5]\b#' => 'shadow-sm',
        '#\bbg-gradient-(' . $cores . ')\b#' => 'bg-$1',
        /* badges (D-08: tom claro) */
        '#\bbadge-pill\b#' => 'rounded-pill',
        /* badge com a cor vinda do banco: badge badge-<?= $x ?> → badge bg-<?= $x ?>-lt */
        '#\bbadge badge-(<\?=.*?\?>)#s' => 'badge bg-$1-lt',
        /* … com o badge-pill (já rounded-pill) no meio: badge rounded-pill badge-<?= $x ?> (Etapa 7) */
        '#\bbadge rounded-pill badge-(<\?=.*?\?>)#s' => 'badge rounded-pill bg-$1-lt',
        /* … montada por concatenação em PHP: 'badge badge-' . $x . '"' → 'badge bg-' . $x . '-lt"' */
        '#\bbadge badge-\' \. (.+?) \. \'"#' => 'badge bg-\' . $1 . \'-lt"',
        /* … por interpolação: badge-{$cor} → bg-{$cor}-lt */
        '#\bbadge-(\{\$[^}]+\})#' => 'bg-$1-lt',
        /* o recolher do cartão do AdminLTE: um atributo próprio, tratado pelo assets/vcostaedu/vcostaedu-tabler.js */
        '#\bdata-card-widget="collapse"#' => 'data-vc-recolher',
        '#\bbadge-light\b#' => 'bg-light text-dark',
        '#\bbadge-(primary|secondary|success|danger|warning|info|dark)\b#' => 'bg-$1-lt',
        /* formulário */
        '#\bform-row\b#' => 'row g-2',
        '#\bform-group\b#' => 'mb-3',
        '#\bcustom-select\b#' => 'form-select',
        '#\bcustom-control custom-switch\b#' => 'form-check form-switch',
        '#\bcustom-control custom-(?:checkbox|radio)\b#' => 'form-check',
        '#\bcustom-control-input\b#' => 'form-check-input',
        '#\bcustom-control-label\b#' => 'form-check-label',
        /* (Etapa 7) a tag pode ter <?= … ?> antes do class= (o "?>" não fecha a tag) */
        '#(<select\b(?:<\?(?:(?!\?>).)*\?>|[^>])*?class=(["\'])(?:(?!\2).)*?)\bform-control\b(?!-)#s' => '$1form-select',
        /* form-inline não existe no BS5 (Etapa 7) */
        '#\bform-inline\b#' => 'd-flex flex-wrap align-items-center gap-2',
        '#\bform-control-file\b#' => 'form-control',
        '#\btext-monospace\b#' => 'font-monospace',
        '#<label(?![^>]*\bclass=)([^>]*)>#' => '<label class="form-label"$1>',
        /* cartões (D-08: card-outline some; a cor de cabeçalho do AdminLTE também) */
        '#\s*\bcard-outline\b#' => '',
        '#(\bcard\b[^"\']*?)\s+card-(' . $cores . ')\b#' => '$1',
        '#\bcard-tools\b#' => 'card-actions',
        '#\bbtn btn-tool\b#' => 'btn btn-action',
        '#\bbtn-tool\b#' => 'btn-action',
        '#\bbtn-default\b#' => 'btn-outline-secondary',
        /* info-box do AdminLTE → o indicador desta casa (card do Tabler; desenho em assets/css/vcostaedu-tabler.css) */
        '#\binfo-box-icon\b#' => 'vc-indicador-icone',
        '#\binfo-box-content\b#' => 'vc-indicador-conteudo',
        '#\binfo-box-text\b#' => 'vc-indicador-texto',
        '#\binfo-box-number\b#' => 'vc-indicador-numero',
        '#\binfo-box\b#' => 'card card-sm vc-indicador',
        '#\bcustom-control-inline\b#' => 'form-check-inline',
        /* callout do AdminLTE → alerta */
        '#\bcallout callout-(' . $cores . ')\b#' => 'alert alert-$1',
        /* o realce de item de lista em grupo do AdminLTE */
        '#\bjustify-content-sm-(start|end)\b#' => 'justify-content-sm-$1',
    ];
    foreach ($regras as $de => $para) {
        $n = 0;
        $novo = preg_replace($de, $para, $s, -1, $n);
        if ($novo === null) {
            /* erro do PCRE (limite de retrocesso, por exemplo): pára sem gravar nada — um null aqui virava arquivo vazio */
            fwrite(STDERR, "ERRO: a regra {$de} falhou (" . preg_last_error_msg() . "); nada foi gravado.\n");
            exit(1);
        }
        $s = $novo;
        $rel['classes'] += $n;
    }

    /* input-group-append / -prepend: no Bootstrap 5 o invólucro some e os filhos ficam direto no input-group */
    $n = 0;
    $s = tirarInvolucro($s, '#<div class="input-group-(?:append|prepend)[^"]*">#', 'div', $n);
    $rel['classes'] += $n;
    return $s;
}

/** O que sobrou do dicionário (para o relatório). */
function sobras(string $s): array
{
    $padroes = [
        'content-wrapper|content-header' => '#\bcontent-(wrapper|header)\b#',
        'breadcrumb' => '#\bbreadcrumb\b#',
        'info-box' => '#\binfo-box#',
        'small-box' => '#\bsmall-box#',
        'callout' => '#\bcallout\b#',
        'data-widget/data-card-widget' => '#\bdata-(card-)?widget=#',
        'custom-*' => '#\bcustom-(control|select|file|checkbox|radio|switch)#',
        'ml-/mr-/pl-/pr-' => '#\b[mp][lr]-(sm-|md-|lg-|xl-)?(auto|[0-5])\b#',
        'float/text left/right' => '#\b(float|text)-(sm-|md-|lg-|xl-)?(left|right)\b#',
        'badge-*' => '#\bbadge-(pill|primary|secondary|success|danger|warning|info|light|dark\b|<\?=)#',
        'form-group/form-row' => '#\bform-(group|row)\b#',
        'input-group-append/prepend' => '#\binput-group-(append|prepend)\b#',
        'close' => '#class=["\']close["\']#',
        'data-toggle/target/dismiss' => '#\bdata-(toggle|target|dismiss|placement)=#',
        'font-weight/sr-only/btn-block' => '#\b(font-weight-|sr-only|btn-block|no-gutters)#',
        'embed/media/jumbotron' => '#\b(embed-responsive|media-body|jumbotron)\b#',
    ];
    $achados = [];
    foreach ($padroes as $nome => $re) {
        $n = preg_match_all($re, $s);
        if ($n) {
            $achados[] = "{$nome} ({$n})";
        }
    }
    return $achados;
}

$totalCasca = $totalClasses = 0;
$comSobra = 0;
foreach ($arquivos as $arq) {
    if (!is_file($arq)) {
        echo "  [NÃO ACHEI] {$arq}\n";
        continue;
    }
    $orig = file_get_contents($arq);
    $rel = ['casca' => 0, 'classes' => 0, 'manual' => []];
    $s = converterCasca($orig, $rel);
    $s = converterClasses($s, $rel);
    if (!$conferir && $s !== $orig) {
        if (trim($s) === '' && trim($orig) !== '') {
            fwrite(STDERR, "ERRO: a conversão de {$arq} saiu vazia; nada foi gravado.\n");
            exit(1);
        }
        file_put_contents($arq, $s);
    }
    $sobra = sobras($s);
    $totalCasca += $rel['casca'];
    $totalClasses += $rel['classes'];
    if ($sobra || $rel['manual']) {
        $comSobra++;
    }
    printf("  %-62s casca %2d · classes %3d%s\n", $arq, $rel['casca'], $rel['classes'],
        ($sobra || $rel['manual']) ? ' · MANUAL: ' . implode(', ', array_merge($rel['manual'], $sobra)) : '');
}
printf("%s %d arquivo(s): casca %d, classes/atributos %d; %d com sobra para o trabalho manual.\n",
    $conferir ? 'Conferidos' : 'Convertidos', count($arquivos), $totalCasca, $totalClasses, $comSobra);
