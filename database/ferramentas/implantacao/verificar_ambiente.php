<?php
/*
 * VERIFICAR O AMBIENTE (E8). Roda NO SERVIDOR, na pasta do sistema, depois de criar as duas Config.php.
 *
 * Uso: php database/ferramentas/implantacao/verificar_ambiente.php [--raiz-publica=PASTA] [--tela]
 *
 * --tela (Onda 3, Etapa 4): o texto para a tela do instalador, em português de uso — sem os nomes das constantes da
 * configuração nem as opções da linha de comando. As conferências são as mesmas.
 *
 * --raiz-publica: a pasta que o servidor web publica (DocumentRoot). Serve para conferir que PASTA_STORAGE
 * está FORA dela. Sem a opção, compara com a pasta do sistema e avisa.
 *
 * Confere: PHP e extensões usadas pelo código, fuso, sessão, PASTA_STORAGE (existe, gravável, fora da raiz
 * pública), AMBIENTE, EXIGIR_HTTPS, ORIGENS_PERMITIDAS_IFRAME, COOKIE_PARTICIONADO, PROXIES_CONFIAVEIS, as
 * duas Configs coerentes, banco (conexão, tabelas, privilégio TRIGGER e gatilhos), VERSAODEV = versão do
 * banco, SMTP (sem mostrar senha) e administrador. Saída OK/ALERTA/FALHA; código 1 se houver FALHA.
 *
 * Atenção: isto é o PHP de LINHA DE COMANDO. O PHP do servidor web pode ter outra versão/extensões/php.ini;
 * o fumaca.php, pela web, confirma o resto.
 */
require __DIR__ . '/_base.php';

$o = vcostaedu_args($argv);
$tela = isset($o['tela']);
$t = static fn(string $cli, string $paraTela): string => $tela ? $paraTela : $cli;
$NOMES = ['HOST' => 'o servidor do banco de dados', 'USER' => 'o usuário do banco de dados', 'DBNAME' => 'o nome do banco de dados', 'VERSAODEV' => 'a versão anotada na configuração',
    'EXIGIR_HTTPS' => 'a exigência de HTTPS', 'PROXIES_CONFIAVEIS' => 'os servidores intermediários (proxy)', 'HOSTS_PERMITIDOS' => 'os endereços aceitos',
    'CAMINHO_BASE' => 'a pasta do endereço', 'PASTA_STORAGE' => 'a pasta dos arquivos privados', 'AMBIENTE' => 'o tipo de instalação'];
$nome = static fn(string $k): string => $tela ? ($NOMES[$k] ?? $k) : $k;
/* a escrita é testada gravando e apagando um arquivo de verdade: is_writable erra no Windows (ACL, pasta herdada) */
$gravavel = static function (string $dir): bool {
    if (!is_dir($dir)) {
        return false;
    }
    $f = rtrim(str_replace('\\', '/', $dir), '/') . '/.vcostaedu_escrita_' . bin2hex(random_bytes(4));
    $ok = @file_put_contents($f, 'ok') === 2;
    @unlink($f);
    return $ok;
};
$raiz = str_replace('\\', '/', realpath(VCOSTAEDU_RAIZ));
$ler = function (string $app) {
    $j = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/_config.php') . ' ' . $app . ' 2>&1');
    $c = json_decode((string) $j, true);
    return is_array($c) ? $c : ['existe' => false, 'erro' => trim((string) $j)];
};
$cp = $ler('painel');
$ca = $ler('portal');

Saida::secao($t('PHP (linha de comando)', 'PHP'));
version_compare(PHP_VERSION, '8.1.0', '>=') ? Saida::ok('PHP ' . PHP_VERSION . ' (mínimo 8.1; desenvolvido e testado em 8.2)') : Saida::falha('PHP ' . PHP_VERSION . ': o sistema exige 8.1 ou mais novo');
$ext = [
    'pdo_mysql' => 'banco', 'mbstring' => 'textos, Dompdf, QR', 'gd' => 'imagens do PDF, logo e QR', 'dom' => 'Dompdf e HTML', 'json' => 'todo o sistema',
    'ctype' => 'PHPMailer', 'filter' => 'PHPMailer', 'hash' => 'hashes das emissões', 'iconv' => 'Dompdf (CSS)', 'openssl' => 'SMTP com TLS/SSL', 'session' => 'login',
];
foreach ($ext as $e => $uso) {
    extension_loaded($e) ? Saida::ok("extensão {$e}") : Saida::falha("falta a extensão {$e} ({$uso})");
}
foreach (['curl' => 'as ferramentas por URL (cabecalhos, fumaça, varredura)', 'zip' => 'backup, restauração e pacote', 'zlib' => 'backup comprimido'] as $e => $uso) {
    extension_loaded($e) ? Saida::ok("extensão {$e}") : Saida::alerta("falta a extensão {$e} (só para {$uso})");
}
$mem = ini_get('memory_limit');
$bytes = (int) $mem * (stripos($mem, 'G') ? 1073741824 : (stripos($mem, 'M') ? 1048576 : 1));
($mem === '-1' || $bytes >= 128 * 1048576) ? Saida::ok($t("memory_limit {$mem}", "a memória do PHP: {$mem}")) : Saida::alerta($t("memory_limit {$mem}: o PDF do histórico pede ao menos 128M", "a memória do PHP ({$mem}) é pouca: o PDF do histórico pede ao menos 128M"));

Saida::secao($t('Configurações (painel/core/Config.php e portal/core/Config.php)', 'Configuração'));
if (empty($cp['existe']) || empty($ca['existe'])) {
    Saida::falha('falta ' . (empty($cp['existe']) ? 'painel/core/Config.php ' : '') . (empty($ca['existe']) ? 'portal/core/Config.php' : '') . ' (copie do Config.example.php e preencha)' . (!empty($cp['erro']) ? ': ' . $cp['erro'] : ''));
    Saida::fim();
}
/* endereço local (localhost, 127.0.0.1): o HTTPS desligado e a pasta dos arquivos dentro da publicada são o normal */
$hostUrl = strtolower(trim((string) parse_url((string) ($cp['URL'] ?? ''), PHP_URL_HOST), '[]'));
$local = in_array($hostUrl, ['localhost', '::1'], true) || str_starts_with($hostUrl, '127.') || str_ends_with($hostUrl, '.localhost');
$LOCAL = 'normal em instalação local; resolva antes de publicar';
foreach (['HOST', 'USER', 'DBNAME', 'VERSAODEV', 'EXIGIR_HTTPS', 'PROXIES_CONFIAVEIS', 'HOSTS_PERMITIDOS', 'CAMINHO_BASE'] as $k) {
    $cp[$k] === $ca[$k] ? Saida::ok($t("{$k} igual nas duas", ucfirst($nome($k)) . ': igual no painel e no portal')) : Saida::falha($t("{$k} diferente entre painel e portal", ucfirst($nome($k)) . ': diferente entre o painel e o portal (as duas configurações precisam do mesmo valor)'));
}
$cp['PASS_preenchida'] === $ca['PASS_preenchida'] ? null : Saida::falha($t('a senha do banco está preenchida numa Config e vazia na outra', 'a senha do banco de dados está preenchida numa configuração e vazia na outra'));
/* 1.14.0 (E9, D-413): desde a 1.12.0 o portal emite o atestado com o motor do painel — a pasta dos arquivos privados e o
   ambiente têm de ser OS MESMOS nas duas Configs (senão o PDF emitido pelo portal vai para outra pasta, ou sai sem a tarja) */
$pasta = fn($p) => $p === null ? null : rtrim(str_replace('\\', '/', (realpath((string) $p) ?: (string) $p)), '/');
if ($ca['PASTA_STORAGE'] === null || $ca['AMBIENTE'] === null) {
    Saida::falha($t('falta ' . ($ca['PASTA_STORAGE'] === null ? 'PASTA_STORAGE ' : '') . ($ca['AMBIENTE'] === null ? 'AMBIENTE ' : '') . 'no portal/core/Config.php (copie do painel: os dois iguais)',
        'falta ' . ($ca['PASTA_STORAGE'] === null ? $nome('PASTA_STORAGE') . ' ' : '') . ($ca['AMBIENTE'] === null ? $nome('AMBIENTE') . ' ' : '') . 'na configuração do portal (o mesmo valor do painel)'));
} else {
    $pasta($cp['PASTA_STORAGE']) === $pasta($ca['PASTA_STORAGE']) ? Saida::ok($t('PASTA_STORAGE igual nas duas', 'A pasta dos arquivos privados: a mesma no painel e no portal'))
        : Saida::falha($t('PASTA_STORAGE diferente entre painel e portal (o atestado do portal grava o PDF em outra pasta): ponha o mesmo caminho nas duas', 'A pasta dos arquivos privados é diferente no painel e no portal (o atestado do portal iria para outra pasta): use a mesma nas duas configurações'));
    $cp['AMBIENTE'] === $ca['AMBIENTE'] ? Saida::ok($t('AMBIENTE igual nas duas', 'O tipo de instalação: o mesmo no painel e no portal'))
        : Saida::falha($t("AMBIENTE diferente entre painel ({$cp['AMBIENTE']}) e portal ({$ca['AMBIENTE']}): ponha o mesmo nas duas", 'O tipo de instalação é diferente no painel e no portal: use o mesmo nas duas configurações'));
}
$cp['URL'] === $ca['URL'] ? Saida::ok($t("endereço (URL) {$cp['URL']}", "o endereço do sistema: {$cp['URL']}")) : Saida::falha($t('URL diferente entre painel e portal', 'o endereço do sistema é diferente no painel e no portal'));
$cp['fuso'] === 'America/Sao_Paulo' ? Saida::ok($t('fuso America/Sao_Paulo', 'o fuso horário é o de Brasília')) : Saida::alerta($t("fuso {$cp['fuso']} (esperado America/Sao_Paulo)", "o fuso horário é {$cp['fuso']} (o esperado é o de Brasília)"));

$amb = (string) $cp['AMBIENTE'];
if (!in_array($amb, ['producao', 'homologacao', 'demonstracao'], true)) {
    Saida::falha($t("AMBIENTE = '{$amb}' (use producao, homologacao ou demonstracao)", 'o tipo de instalação não é reconhecido (produção, homologação ou demonstração)'));
} elseif ($amb === 'demonstracao') {
    Saida::alerta($t("AMBIENTE = 'demonstracao': documentos com tarja e limpeza da demonstração liberada — nunca num servidor da instituição", 'instalação de demonstração: documentos com tarja e a limpeza da demonstração liberada — nunca num servidor da instituição'));
} else {
    Saida::ok($t("AMBIENTE = '{$amb}'", 'instalação de ' . ($amb === 'producao' ? 'produção' : 'homologação')) . ($amb === 'homologacao' ? ' (documentos com a tarja DOCUMENTO DE HOMOLOGAÇÃO)' : ''));
}
$serio = $amb !== 'demonstracao';
if ($cp['EXIGIR_HTTPS']) {
    Saida::ok($t('EXIGIR_HTTPS = true (HTTP redireciona para HTTPS, com HSTS)', 'o sistema exige HTTPS (o acesso por HTTP vai para HTTPS)'));
} elseif ($local || !$serio) {
    Saida::alerta($t('EXIGIR_HTTPS = false', 'o sistema não exige HTTPS') . ($local ? ': ' . $LOCAL : ''));
} else {
    Saida::falha($t('EXIGIR_HTTPS = false: no servidor, deixe true', 'o sistema não exige HTTPS: num servidor publicado, ligue a exigência de HTTPS na configuração'));
}

$orig = preg_split('/[\s,]+/', (string) $ca['ORIGENS_PERMITIDAS_IFRAME'], -1, PREG_SPLIT_NO_EMPTY);
$valid = array_filter($orig, fn($x) => preg_match('#^https://([a-z0-9.-]+|\[[0-9a-f:]+\])(:\d{1,5})?/?$#i', $x));
if (!$orig) {
    Saida::ok($t('ORIGENS_PERMITIDAS_IFRAME vazio: o portal não aparece dentro de outro site (sem Moodle)', 'o portal do aluno não aparece dentro de outro site (sem Moodle)'));
} else {
    foreach (array_diff($orig, $valid) as $x) {
        Saida::alerta($t("origem de iframe ignorada (só vale https://host[:porta]): {$x}", "site ignorado para mostrar o portal (só vale um endereço https://): {$x}"));
    }
    $valid ? Saida::ok($t('portal embutível por: ' . implode(' ', $valid) . '; COOKIE_PARTICIONADO = ' . ($ca['COOKIE_PARTICIONADO'] ? 'true' : 'false'), 'o portal do aluno pode aparecer dentro de: ' . implode(' ', $valid)))
        : Saida::falha($t('nenhuma origem de iframe válida', 'nenhum dos sites informados para mostrar o portal é válido'));
}
$prox = preg_split('/[\s,]+/', (string) $cp['PROXIES_CONFIAVEIS'], -1, PREG_SPLIT_NO_EMPTY);
$proxOk = true;
foreach ($prox as $x) {
    [$ip, $bits] = array_pad(explode('/', $x, 2), 2, null);
    $bin = @inet_pton($ip);
    if ($bin === false || ($bits !== null && (!ctype_digit($bits) || (int) $bits > strlen($bin) * 8))) {
        Saida::alerta($t("PROXIES_CONFIAVEIS: entrada inválida, ignorada: {$x}", "servidor intermediário (proxy) inválido, ignorado: {$x}"));
        $proxOk = false;
    }
}
if (!$prox) {
    Saida::ok($t('PROXIES_CONFIAVEIS vazio: o IP do cliente é o REMOTE_ADDR (se houver proxy/balanceador na frente, preencha)', 'sem servidor intermediário (proxy): o endereço de quem acessa é o da própria conexão'));
} elseif ($proxOk) {
    Saida::ok($t('PROXIES_CONFIAVEIS: ', 'servidores intermediários (proxy) confiáveis: ') . implode(' ', $prox));
}
/* 1.9.1: host do endereço (links de e-mail, QR e verificação do histórico) */
$hosts = preg_split('/[\s,]+/', (string) $cp['HOSTS_PERMITIDOS'], -1, PREG_SPLIT_NO_EMPTY);
if (!$hosts) {
    $serio ? Saida::falha($t('HOSTS_PERMITIDOS vazio: o endereço aceita qualquer Host (links e QR podem sair com host forjado); informe o domínio', 'nenhum endereço aceito definido: os links e o QR dos documentos podem sair com um endereço forjado; informe o domínio'))
        : Saida::alerta($t('HOSTS_PERMITIDOS vazio: aceita o Host da requisição (só desenvolvimento)', 'nenhum endereço aceito definido: vale o endereço de cada acesso (só para teste)'));
} elseif (!array_filter($hosts, fn($h) => strpos($h, '*') === false)) {
    Saida::falha($t('HOSTS_PERMITIDOS só com curinga: o primeiro host SEM curinga é o de reserva; inclua o domínio principal', 'os endereços aceitos só têm curinga: inclua o domínio principal'));
} else {
    Saida::ok($t('HOSTS_PERMITIDOS: ', 'endereços aceitos: ') . implode(' ', $hosts));
}
Saida::ok($t('CAMINHO_BASE: ', 'a pasta do endereço: ') . ((string) $cp['CAMINHO_BASE'] !== '' ? $cp['CAMINHO_BASE'] : $t('automático', 'automática')));

Saida::secao('Sessão do PHP');
$sess = (string) ini_get('session.save_path');
$sessDir = $sess === '' ? sys_get_temp_dir() : preg_replace('/^\d+;/', '', $sess);
$gravavel($sessDir) ? Saida::ok($t("session.save_path gravável ({$sessDir})", 'a pasta das sessões aceita gravação')) : Saida::falha($t("session.save_path não gravável: {$sessDir}", "a pasta das sessões não aceita gravação: {$sessDir}"));
Saida::info('(é o valor da linha de comando; confira também o do servidor web)');

Saida::secao($t('PASTA_STORAGE (PDFs do histórico e assinaturas)', 'A pasta dos arquivos privados (os PDFs do histórico e as assinaturas)'));
$st = rtrim(str_replace('\\', '/', (string) $cp['PASTA_STORAGE']), '/');
$stReal = is_dir($st) ? str_replace('\\', '/', realpath($st)) : '';
if ($stReal === '') {
    Saida::falha($t("PASTA_STORAGE não existe: {$st}", "a pasta dos arquivos privados não existe: {$st}"));
} else {
    $gravavel($stReal) ? Saida::ok($t("existe e é gravável: {$stReal}", "existe e aceita gravação: {$stReal}")) : Saida::falha($t("sem permissão de escrita: {$stReal}", "a pasta não aceita gravação: {$stReal}"));
    foreach (['historico/emissoes', 'historico/assinaturas'] as $sub) {
        if (!is_dir("{$stReal}/{$sub}")) {
            Saida::falha($t("falta {$sub}/ dentro da PASTA_STORAGE", "falta a pasta {$sub}/ dentro dela"));
        } else {
            $gravavel("{$stReal}/{$sub}") ? Saida::ok($t("{$sub}/ existe e é gravável", "{$sub}/ existe e aceita gravação")) : Saida::falha($t("{$sub}/ sem escrita dentro da PASTA_STORAGE", "a pasta {$sub}/ não aceita gravação"));
        }
    }
    $pub = isset($o['raiz-publica']) ? str_replace('\\', '/', (string) realpath((string) $o['raiz-publica'])) : $raiz;
    if (isset($o['raiz-publica']) && $pub === '') {
        Saida::falha('--raiz-publica não existe: ' . $o['raiz-publica']);
    } elseif (str_starts_with($stReal . '/', rtrim($pub, '/') . '/')) {
        $m = $t("PASTA_STORAGE está DENTRO da raiz pública ({$pub}): os PDFs ficam protegidos só pelo .htaccess", 'a pasta dos arquivos privados está dentro da pasta publicada pelo servidor: os PDFs ficam protegidos só pelo .htaccess');
        $local ? Saida::alerta($m . '; ' . $LOCAL) : ($serio ? Saida::falha($m) : Saida::alerta($m));
    } else {
        Saida::ok($t("fora da raiz pública ({$pub})", 'fora da pasta publicada pelo servidor'));
    }
    if (!isset($o['raiz-publica']) && !$tela) {
        Saida::alerta('sem --raiz-publica: comparei só com a pasta do sistema. Informe o DocumentRoot do servidor.');
    }
}

Saida::secao('Banco');
require __DIR__ . '/../comum.php';
try {
    $pdo = vcostaedu_pdo();
    Saida::ok('conectado a ' . DBNAME . ' em ' . HOST . ' (usuário ' . USER . ')');
    /* Onda 3, Etapa 2: o modo de SQL que o sistema fixa em toda conexão (core/Banco.php) */
    $modo = VcBanco::modoEmUso($pdo);
    VcBanco::modoCompleto($modo) ? Saida::ok($t("modo do banco estrito, fixado pelo sistema ({$modo})", 'o banco de dados recusa dado inválido em vez de gravar pela metade (o modo estrito, fixado pelo sistema)'))
        : Saida::falha($t("modo do banco incompleto ({$modo}): o sistema fixa " . VcBanco::MODO, 'o banco de dados não ficou no modo estrito que o sistema fixa: confira com o provedor'));
} catch (Throwable $e) {
    Saida::falha('não conectou ao banco: ' . $e->getMessage());
    Saida::fim();
}
/* VCOSTAEDU (Etapa 2): a tabela de controle do runner de migrations (adms_migrations) não conta entre as 88 do sistema */
$tab = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME <> 'adms_migrations'")->fetchColumn();
/* Onda 2: 88 é o piso da base (1.18.4); as migrations novas acrescentam tabelas, e quem diz se falta alguma é o
   runner (as pendentes abaixo). */
if ($tab < 88) { // 1.17.0 (D-457, D-454): + rm_relatorios_exportacoes e rm_relatorios_salvos
    Saida::falha("{$tab} tabelas (esperadas 88; num banco vazio, instale pelo navegador ou rode php bin/migrar.php aplicar)");
    Saida::info('as conferências que dependem das tabelas (gatilhos, versão, SMTP, administrador) ficam para depois da instalação');
    Saida::fim();
}
Saida::ok("{$tab} tabelas");
$pend = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'adms_migrations'")->fetchColumn();
$pend === 1 ? Saida::ok($t('controle de migrations presente (php bin/migrar.php status mostra o estado)', 'o controle das atualizações do banco está presente')) : Saida::alerta($t('sem a tabela adms_migrations: este banco não foi instalado pelo runner', 'este banco não foi instalado pelo instalador (falta o controle das atualizações)'));
$diff = (int) $pdo->query('SELECT TIMESTAMPDIFF(SECOND, NOW(), ' . $pdo->quote(date('Y-m-d H:i:s')) . ')')->fetchColumn();
abs($diff) <= 120 ? Saida::ok('hora do banco igual à do PHP (diferença ' . $diff . ' s)') : Saida::alerta("hora do banco difere da do PHP em {$diff} s (fuso do MariaDB/MySQL?)");
$grants = implode("\n", $pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN));
preg_match('/GRANT (ALL PRIVILEGES|[^\n]*\bTRIGGER\b)[^\n]* ON (\*\.\*|`?' . preg_quote(str_replace('_', '\\_', DBNAME), '/') . '`?\.\*|`?' . preg_quote(DBNAME, '/') . '`?\.\*)/i', $grants)
    ? Saida::ok($t('o usuário do banco tem o privilégio TRIGGER', 'o usuário do banco pode criar a proteção do histórico emitido (gatilhos)')) : Saida::alerta($t('não achei o privilégio TRIGGER nos GRANTs do usuário (confira com o provedor)', 'o usuário do banco parece não poder criar a proteção do histórico emitido (gatilhos): confira com o provedor'));
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/conferir_gatilhos.php'), $gs, $rcG);
$rcG === 0 ? Saida::ok($t('os 6 gatilhos de imutabilidade estão no banco (conferir_gatilhos.php)', 'a proteção do histórico emitido está no banco (os 6 gatilhos)')) : Saida::falha($t('faltam gatilhos: ', 'falta parte da proteção do histórico emitido: ') . implode(' ', array_filter($gs, fn($l) => str_contains($l, 'FALTA'))));
$vb = (string) $pdo->query('SELECT numero FROM adms_versoes WHERE situacao = 1 ORDER BY id DESC LIMIT 1')->fetchColumn();
/* Onda 2: a versão dos ARQUIVOS é a das migrations (core/Legal.php); a constante VERSAODEV da configuração
   ficou da origem e não é mais conferida (ela não acompanha as atualizações). */
require_once dirname(__DIR__, 3) . '/core/Legal.php';
$vp = \VcLegal::versaoDoPacote(dirname(__DIR__, 3));
$vb === $vp ? Saida::ok($t("versão dos arquivos = versão do banco ({$vb})", "a versão dos arquivos e a do banco são a mesma ({$vb})"))
    : Saida::falha($t("versão dos arquivos ({$vp}) diferente da versão do banco ({$vb}): há atualização pendente (php bin/migrar.php aplicar)", "a versão dos arquivos ({$vp}) é mais nova que a do banco ({$vb}): entre no painel e use Atualizar o sistema"));
/* Onda 4, Etapa 1: o tipo de instituição (sem a chave, ou antes da 0025, ensino superior) */
$tipoInst = '';
try {
    $tipoInst = (string) $pdo->query("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'")->fetchColumn();
} catch (\Throwable $e) {
}
Saida::ok($t('tipo de instituição: ' . ($tipoInst === 'escola' ? 'escola' : 'superior'), 'o tipo de instituição: ' . ($tipoInst === 'escola' ? 'escola (educação básica)' : 'ensino superior')));
$smtp = $pdo->query('SELECT host, porta, username, smtpsecure, email, senhas FROM adms_confs_emails ORDER BY id LIMIT 1')->fetch();
if ($smtp && trim((string) $smtp['host']) !== '') {
    $u = (string) $smtp['username'];
    Saida::ok($t('SMTP configurado: ', 'o e-mail de envio está configurado: ') . "{$smtp['host']}:{$smtp['porta']} ({$smtp['smtpsecure']}), usuário " . mb_substr($u, 0, 2) . '…' . ', remetente ' . $smtp['email'] . ', senha ' . ($smtp['senhas'] !== '' ? 'preenchida' : 'VAZIA'));
} else {
    Saida::alerta($t('SMTP não configurado (Configurações → Configurações de e-mail): os links de acesso não saem por e-mail', 'o e-mail de envio não está configurado (no painel, Configurações → E-mail): os links de acesso não saem por e-mail'));
}
$adm = (int) $pdo->query('SELECT COUNT(*) FROM adms_usuarios WHERE adms_niveis_acesso_id = 1 AND adms_sits_usuario_id = 1')->fetchColumn();
$adm > 0 ? Saida::ok("{$adm} administrador(es) ativo(s)") : Saida::alerta($t('nenhum administrador ativo: crie pelo instalador ou rode criar_admin.php', 'nenhum administrador ativo: crie a conta pelo instalador'));
/* o teto das ações (core/Teto.php, a mesma conferência do instalador) */
require_once __DIR__ . '/../../../core/Teto.php';
if (VcTeto::temAcoes($pdo)) {
    $acima = VcTeto::acimaDoTeto($pdo);
    $acima === [] ? Saida::ok('nenhum perfil recebe uma ação acima do que pode')
        : Saida::falha(count($acima) . ' permissão(ões) acima do perfil: ' . implode('; ', array_slice($acima, 0, 4)) . ' (tire a permissão do perfil)');
    $semAcao = VcTeto::semAcao($pdo);
    $semAcao === [] ? Saida::ok('todas as telas do painel têm a ação delas') : Saida::falha(count($semAcao) . ' tela(s) sem ação: ' . implode(', ', array_slice($semAcao, 0, 6)));
} else {
    Saida::alerta('a permissão por ação ainda não está no banco (atualize o sistema)');
}
/* 1.18.0 (D-476): os vídeos que o índice da Ajuda lista e não estão na pasta (fora do git: copie assets/videos/guias/). ALERTA:
   o guia sem o arquivo só não mostra o player. Onda 3, Etapa 4: os vídeos não vêm no pacote — sem NENHUM (a instalação
   nova), não é alerta; o alerta é para o conjunto pela metade. */
Saida::secao('Vídeos dos guias');
$listados = [];
foreach (['painel', 'portal'] as $app) {
    preg_match_all("/'arquivo' => '([a-z0-9-]+\.mp4)'/", (string) @file_get_contents(VCOSTAEDU_RAIZ . "/{$app}/app/adms/Views/ajuda/guias/_indice.php"), $mm);
    $listados = array_merge($listados, $mm[1]);
}
$listados = array_values(array_unique($listados));
$semArquivo = array_values(array_filter($listados, fn($f) => !is_file(VCOSTAEDU_RAIZ . '/assets/videos/guias/' . $f)));
if (!$listados) {
    Saida::alerta('o índice da Ajuda não lista nenhum vídeo (gere os guias com o manifesto docs/guia/videos.json)');
} elseif (count($semArquivo) === count($listados)) {
    Saida::ok($t('os vídeos dos guias não vêm no pacote (são opcionais: copie-os para assets/videos/guias/ se quiser o player nos guias)', 'os vídeos dos guias não vêm no pacote: os guias funcionam com o texto e as imagens (os vídeos são opcionais)'));
} elseif ($semArquivo) {
    Saida::alerta(count($semArquivo) . ' de ' . count($listados) . $t(' vídeo(s) listados não estão em assets/videos/guias/ (o guia fica sem o player): ', ' vídeo(s) dos guias faltam na pasta dos vídeos (o guia fica sem o player): ') . implode(', ', array_slice($semArquivo, 0, 8)));
} else {
    Saida::ok(count($listados) . ' vídeo(s) listados no índice da Ajuda, todos na pasta assets/videos/guias/');
}
$demo = (int) $pdo->query("SELECT COUNT(*) FROM rm_historico_emissoes WHERE ambiente = 'demonstracao'")->fetchColumn();
/* Onda 3, Etapa 4: a demonstração ligada PELA TELA (adms_instalacao_estado) é a escolha do administrador, não um
   defeito: aviso, com o caminho para tirá-la. Sem ela ligada, as emissões fictícias num banco de produção são falha. */
$demoPelaTela = vcostaedu_demonstracao_ativa($pdo);
if ($demoPelaTela) {
    Saida::alerta($t('a demonstração está ligada (dados fictícios): antes de usar de verdade, use "Começar com os dados da minha instituição"', 'a demonstração está ligada (dados fictícios): antes de usar de verdade, use "Começar com os dados da minha instituição"'));
} elseif ($demo > 0 && $serio) {
    Saida::falha("{$demo} emissão(ões) da DEMONSTRAÇÃO neste banco de {$amb}");
}
Saida::fim();
