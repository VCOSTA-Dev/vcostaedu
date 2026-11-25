<?php
/*
 * O INVENTÁRIO DO ESQUELETO HERDADO (Onda 6, Etapa 8) — gera docs/ORIGEM_DO_CODIGO.md.
 *
 * A base do sistema descende de um esqueleto MVC de material de curso (o roteador, a montagem da tela, os ajudantes de
 * banco, o login e o menu), que o sistema de origem estendeu e esta plataforma reescreveu aos poucos. Este comando lista,
 * arquivo por arquivo, o que ainda descende desse esqueleto e quanto já foi reescrito.
 *
 * Como mede, sem adivinhar:
 *   - o ponto de partida é a cópia do sistema de origem importada no começo deste repositório (a 1.18.4);
 *   - DESCENDE do esqueleto o arquivo PHP de painel/ e portal/ que, naquela cópia, trazia a guarda de acesso do esqueleto
 *     (o "if (!defined(...))" do começo de cada arquivo) — o sinal comum a todo arquivo nascido dele;
 *   - REESCRITO = a parte das linhas de hoje com conteúdo próprio (sem as vazias, as só de símbolo e as curtas) que não
 *     existe na cópia de origem; "100%" é um arquivo todo
 *     novo, "0%" é o arquivo como veio. A conta é por linha (a troca de um nome numa linha conta a linha como reescrita).
 *   - Arquivo que existia na origem e saiu do repositório é listado à parte.
 * Não altera nenhum arquivo além do documento; não muda crédito nem aviso.
 *
 *   php database/ferramentas/origem_do_codigo.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$raiz = dirname(__DIR__, 2);
chdir($raiz);
/* a cópia de origem: o commit que trouxe o roteador do esqueleto para o repositório */
$origem = strtok(trim((string) shell_exec('git log --diff-filter=A --reverse --format=%H -- painel/core/ConfigController.php')), "\n");
if (!$origem) {
    fwrite(STDERR, "Sem o histórico do git: o inventário precisa do commit da cópia de origem.\n");
    exit(1);
}
$naOrigem = array_filter(explode("\n", (string) shell_exec('git ls-tree -r --name-only ' . escapeshellarg($origem))));
/* as linhas com conteúdo próprio: as só de símbolo, as curtas e as de abertura do PHP existem em todo arquivo e não contam */
$linhas = static fn(string $t): array => array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $t)),
    fn($l) => strlen($l) >= 6 && preg_match('/[A-Za-z]{3}/', $l) && !preg_match('/^(<\?php|\} ?else ?\{|return;|break;)$/', $l)));
$descende = [];
$sairam = [];
foreach ($naOrigem as $arq) {
    if (!preg_match('#^(painel|portal)/.+\.php$#', $arq) || preg_match('#/vendor/#', $arq)) {
        continue;
    }
    $antes = (string) shell_exec('git show ' . escapeshellarg("{$origem}:{$arq}"));
    if (!preg_match("/if\s*\(\s*!\s*defined\(\s*['\"][A-Za-z0-9]+['\"]\s*\)/", substr($antes, 0, 600))) {
        continue;
    }
    if (!is_file($arq)) {
        $sairam[] = $arq;
        continue;
    }
    $hoje = $linhas((string) file_get_contents($arq));
    $conjunto = array_flip($linhas($antes));
    $iguais = count(array_filter($hoje, fn($l) => isset($conjunto[$l])));
    $total = max(1, count($hoje));
    $descende[] = ['arq' => $arq, 'linhas' => count($hoje), 'reescrito' => (int) round(100 * (1 - $iguais / $total))];
}
usort($descende, fn($a, $b) => [$a['reescrito'], $a['arq']] <=> [$b['reescrito'], $b['arq']]);
$faixa = static fn(int $r): string => $r >= 90 ? 'reescrito' : ($r >= 50 ? 'mais da metade reescrita' : ($r >= 10 ? 'em parte' : 'como veio'));
$conta = [];
foreach ($descende as $d) {
    $conta[$faixa($d['reescrito'])] = ($conta[$faixa($d['reescrito'])] ?? 0) + 1;
}
$somaLinhas = array_sum(array_column($descende, 'linhas'));
$somaNovas = array_sum(array_map(fn($d) => $d['linhas'] * $d['reescrito'] / 100, $descende));
$porPasta = [];
foreach ($descende as $d) {
    $p = implode('/', array_slice(explode('/', $d['arq']), 0, 4));
    $porPasta[$p][] = $d['reescrito'];
}
ksort($porPasta);
$md = "# O esqueleto herdado: o que ainda descende do material de curso\n\n"
    . "Gerado por `php database/ferramentas/origem_do_codigo.php`; não edite à mão. É só o inventário: nenhum crédito ou aviso foi\n"
    . "mudado por ele. A origem do código, por pasta e por licença, está em `docs/ORIGEM.md`.\n\n"
    . "## Como foi medido\n\n"
    . "- **O ponto de partida** é a cópia do sistema de origem importada no começo deste repositório (a versão 1.18.4).\n"
    . "- **Descende do esqueleto** o arquivo PHP do painel ou do portal que, naquela cópia, trazia a guarda de acesso do\n"
    . "  esqueleto MVC do material de curso no começo do arquivo: o roteador, a montagem da tela, os ajudantes de banco, o\n"
    . "  login, o menu, e os controllers, models e views escritos sobre ele.\n"
    . "- **Reescrito** é a parte das linhas de hoje com conteúdo próprio (sem as vazias, as só de símbolo e as curtas) que\n"
    . "  não existe na cópia de origem. 100% é um arquivo todo novo; 0%, o arquivo como veio. A conta é por linha: trocar\n"
    . "  um nome numa linha conta a linha como reescrita.\n\n"
    . "## O resumo\n\n"
    . '- **' . count($descende) . " arquivos** ainda descendem do esqueleto, com " . number_format($somaLinhas, 0, ',', '.') . ' linhas hoje; '
    . (int) round(100 * $somaNovas / max(1, $somaLinhas)) . "% dessas linhas já foram reescritas.\n"
    . '- Por faixa: ' . implode('; ', array_map(fn($k) => "{$k}: " . ($conta[$k] ?? 0), ['reescrito', 'mais da metade reescrita', 'em parte', 'como veio'])) . ".\n"
    . '- ' . count($sairam) . " arquivos que vinham do esqueleto saíram do repositório.\n\n"
    . "## Por pasta\n\n| pasta | arquivos | reescrito (média) |\n|---|---|---|\n";
foreach ($porPasta as $p => $v) {
    $md .= "| `{$p}` | " . count($v) . ' | ' . (int) round(array_sum($v) / count($v)) . "% |\n";
}
$md .= "\n## Arquivo por arquivo\n\nDo menos ao mais reescrito.\n\n| arquivo | linhas hoje | reescrito | faixa |\n|---|---|---|---|\n";
foreach ($descende as $d) {
    $md .= "| `{$d['arq']}` | {$d['linhas']} | {$d['reescrito']}% | " . $faixa($d['reescrito']) . " |\n";
}
if ($sairam) {
    $md .= "\n## Os que saíram\n\n" . implode("\n", array_map(fn($a) => "- `{$a}`", $sairam)) . "\n";
}
file_put_contents($raiz . '/docs/ORIGEM_DO_CODIGO.md', $md);
echo 'docs/ORIGEM_DO_CODIGO.md: ' . count($descende) . ' arquivos que descendem do esqueleto; ' . count($sairam) . " saíram.\n";
