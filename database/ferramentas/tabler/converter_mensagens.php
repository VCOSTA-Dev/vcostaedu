<?php
/*
 * A MARCAÇÃO GERADA NO SERVIDOR (VCOSTAEDU, Etapa 3, D-12): as mensagens de retorno ($_SESSION['msg']) e os badges
 * que controllers e models montam em texto, no painel e no portal.
 *
 *   php database/ferramentas/tabler/converter_mensagens.php            aplica (idempotente) e conta
 *   php database/ferramentas/tabler/converter_mensagens.php --conferir só conta o que ainda falta (sai com 1 se falta)
 *   php database/ferramentas/tabler/converter_mensagens.php --final    Etapa 8: tira os atributos do Bootstrap 4
 *
 * O que troca (só a marcação; nenhum texto, nenhuma regra):
 *   - o botão de fechar da mensagem: class='close' data-dismiss='alert' + × → class='btn-close' data-bs-dismiss='alert'.
 *     ENQUANTO AS DUAS PILHAS CONVIVEM o botão leva também o data-dismiss='alert' (o Bootstrap 4 das telas ainda
 *     AdminLTE fecha por ele) e o personalizado.css do AdminLTE desenha o × — a mensagem fecha nas duas pilhas;
 *   - badge badge-pill badge-<cor> → badge rounded-pill bg-<cor>-lt; badge badge-' . $cor . ' → badge bg-' . $cor . '-lt
 *     (D-08, tom claro). O PDF do boletim (Dompdf) acompanha: o seletor do CSS dele muda junto, a cor não.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$raiz = dirname(__DIR__, 3);
$conferir = in_array('--conferir', $argv, true);
$final = in_array('--final', $argv, true);

$pastas = ['painel/app/adms/Controllers', 'painel/app/adms/Models', 'portal/app/adms/Controllers', 'portal/app/adms/Models'];
$trocas = [
    'fechar (Bootstrap 4)' => [
        "class='close' data-dismiss='alert' aria-hidden='true'>×</button>",
        "class='btn-close' data-bs-dismiss='alert' data-dismiss='alert' aria-label='Fechar'></button>",
    ],
    'badge pill' => ['/badge badge-pill badge-(primary|secondary|success|danger|warning|info|light|dark)\b/', 'badge rounded-pill bg-$1-lt'],
    'badge dinâmico' => ["badge badge-' . \$d['cor'] . '", "badge bg-' . \$d['cor'] . '-lt"],
    'badge fixo' => ['/\bbadge badge-(primary|secondary|success|danger|warning|info|light|dark)\b/', 'badge bg-$1-lt'],
];
if ($final) {
    $trocas = ['fechar (só Bootstrap 5)' => [" data-bs-dismiss='alert' data-dismiss='alert' ", " data-bs-dismiss='alert' "]];
}

$arquivos = [];
foreach ($pastas as $p) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$raiz}/{$p}", FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->getExtension() === 'php') {
            $arquivos[] = $f->getPathname();
        }
    }
}
sort($arquivos);

$total = array_fill_keys(array_keys($trocas), 0);
$mexidos = 0;
foreach ($arquivos as $arq) {
    $s = file_get_contents($arq);
    $novo = $s;
    foreach ($trocas as $nome => [$de, $para]) {
        $n = 0;
        $novo = str_starts_with($de, '/') ? preg_replace($de, $para, $novo, -1, $n) : str_replace($de, $para, $novo, $n);
        $total[$nome] += $n;
    }
    if ($novo !== $s) {
        $mexidos++;
        if (!$conferir) {
            file_put_contents($arq, $novo);
        }
    }
}

/* O PDF do boletim: o seletor acompanha a classe nova; a cor do documento (fundo sólido, texto branco) não muda. */
$pdf = "{$raiz}/painel/app/adms/Models/academicos/AdmsGerarBoletimPdf.php";
$s = file_get_contents($pdf);
$novo = str_replace(['.badge-success{', '.badge-danger{', '.badge-secondary{'], ['.bg-success-lt{', '.bg-danger-lt{', '.bg-secondary-lt{'], $s);
if ($novo !== $s) {
    $mexidos++;
    $total['PDF do boletim'] = 3;
    if (!$conferir) {
        file_put_contents($pdf, $novo);
    }
}

foreach ($total as $nome => $n) {
    printf("  %-26s %d\n", $nome, $n);
}
printf("%s em %d arquivo(s).\n", $conferir ? 'Faltam' : 'Trocados', $mexidos);

/* O que ainda sobra da marcação do Bootstrap 4 nessas pastas (fora o modo final, que só tira o atributo antigo). */
$sobra = 0;
foreach ($arquivos as $arq) {
    $c = file_get_contents($arq);
    $sobra += preg_match_all("/class='close'|\bbadge-(pill|primary|secondary|success|danger|warning|info|light|dark)\b/", $c);
    if ($final) {
        $sobra += substr_count($c, "data-dismiss='alert'");
    }
}
echo "Sobra (marcação do Bootstrap 4 gerada no servidor): {$sobra}\n";
exit($conferir && $mexidos > 0 ? 1 : 0);
