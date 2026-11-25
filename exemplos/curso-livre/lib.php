<?php
/*
 * O SITE DE EXEMPLO DE UMA INSTITUIÇÃO QUE VENDE UM CURSO LIVRE — o que as páginas usam. Fica fora do produto e do
 * pacote (como exemplos/portal-matricula). Só conversa com a interface de integração da plataforma, do lado do servidor:
 * a chave fica em config.local.php, que a web não serve, e nunca vai para o navegador.
 */
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'lib.php') {
    http_response_code(404);
    exit;
}
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
session_name('CURSO_LIVRE_EXEMPLO');
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);

function cfg(): array
{
    static $c = null;
    if ($c === null) {
        $arq = __DIR__ . '/config.local.php';
        $c = is_file($arq) ? (array) require $arq : [];
    }
    return $c;
}

function h($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** O token do formulário (contra o envio forjado por outro site). */
function token(): string
{
    return $_SESSION['token'] ??= bin2hex(random_bytes(16));
}

/**
 * Uma chamada à interface: ['status', 'json'] (status 0 = não respondeu). $repeticao é a chave de repetição
 * (Idempotency-Key): a MESMA para o mesmo envio do formulário — o clique duplo repete a resposta, não a inscrição.
 */
function api(string $metodo, string $rota, ?array $corpo = null, ?string $repeticao = null): array
{
    $c = cfg();
    $local = in_array(parse_url((string) ($c['api'] ?? ''), PHP_URL_HOST), ['localhost', '127.0.0.1', '::1'], true);
    $cab = ['Authorization: Bearer ' . ($c['chave'] ?? ''), 'Accept: application/json'];
    if ($corpo !== null) {
        $cab[] = 'Content-Type: application/json';
    }
    if ($repeticao !== null) {
        $cab[] = 'Idempotency-Key: ' . $repeticao;
    }
    $ch = curl_init(rtrim((string) ($c['api'] ?? ''), '/') . $rota);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => !$local,
        CURLOPT_HTTPHEADER => $cab,
        CURLOPT_POSTFIELDS => $corpo !== null ? json_encode($corpo, JSON_UNESCAPED_UNICODE) : null,
    ]);
    $texto = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    return ['status' => $texto === false ? 0 : $status, 'json' => is_string($texto) ? json_decode($texto, true) : null];
}

/** O motivo de uma recusa, em linguagem de quem está se inscrevendo. */
function motivo(array $r): string
{
    if ($r['status'] === 0) {
        return 'Nosso sistema não respondeu agora. Tente de novo em alguns minutos.';
    }
    $m = (string) ($r['json']['erro']['mensagem'] ?? '');
    if (stripos($m, 'completa') !== false) {
        return 'As vagas desta turma acabaram. Deixe seu e-mail com a nossa equipe para a próxima.';
    }
    return $m !== '' ? $m : 'Não foi possível concluir a inscrição agora.';
}

/**
 * O número de matrícula da demonstração: um contador (não um sorteio), guardado em dados/, que a web não serve. O
 * mesmo envio do formulário fica sempre com o mesmo número (o clique duplo não gasta dois).
 */
function numeroDaMatricula(string $envio): string
{
    $c = cfg();
    $pasta = __DIR__ . '/dados';
    if (!is_dir($pasta)) {
        mkdir($pasta, 0700, true);
    }
    $f = fopen($pasta . '/contador.json', 'c+');
    flock($f, LOCK_EX);
    $estado = json_decode((string) stream_get_contents($f), true) ?: ['proximo' => (int) ($c['matricula_inicio'] ?? 970001), 'envios' => []];
    if (!isset($estado['envios'][$envio])) {
        $estado['envios'][$envio] = (string) $estado['proximo']++;
        $estado['envios'] = array_slice($estado['envios'], -500, null, true);
    }
    ftruncate($f, 0);
    rewind($f);
    fwrite($f, json_encode($estado));
    fflush($f);
    flock($f, LOCK_UN);
    fclose($f);
    return $estado['envios'][$envio];
}

/** O endereço está na lista (um endereço exato ou uma faixa IPv4 em notação CIDR: o endereço, a barra e o tamanho do prefixo)? */
function ipNaLista(string $ip, array $lista): bool
{
    foreach ($lista as $item) {
        $item = trim((string) $item);
        if ($item === $ip) {
            return true;
        }
        if (str_contains($item, '/') && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            [$rede, $bits] = explode('/', $item, 2);
            $bits = (int) $bits;
            if (filter_var($rede, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $bits >= 0 && $bits <= 32) {
                $mascara = $bits === 0 ? 0 : (-1 << (32 - $bits)) & 0xFFFFFFFF;
                if ((ip2long($ip) & $mascara) === (ip2long($rede) & $mascara)) {
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * O endereço de quem acessa. Atrás de um proxy listado como confiável (proxies_confiaveis, na configuração), o endereço
 * real vem do X-Forwarded-For, lido da direita para a esquerda até o primeiro que não é proxy confiável — a mesma regra
 * da plataforma. Sem a lista, vale só o endereço da conexão (o cabeçalho, que qualquer um forja, é ignorado).
 */
function ipCliente(): string
{
    $remoto = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $proxies = (array) (cfg()['proxies_confiaveis'] ?? []);
    if ($remoto === '' || !$proxies || !ipNaLista($remoto, $proxies)) {
        return $remoto;
    }
    $xff = array_map('trim', explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')));
    $ultimo = $remoto;
    for ($i = count($xff) - 1; $i >= 0; $i--) {
        if (filter_var($xff[$i], FILTER_VALIDATE_IP) === false) {
            return $ultimo;
        }
        if (!ipNaLista($xff[$i], $proxies)) {
            return $xff[$i];
        }
        $ultimo = $xff[$i];
    }
    return $ultimo;
}

/**
 * O limite contra abuso: quantas inscrições NOVAS um endereço faz numa janela de tempo (limite_por_endereco e
 * janela_minutos, na configuração; o padrão, conservador, é 10 em 10 minutos). Uma sala inteira atrás da mesma rede
 * precisa de um teto maior: ajuste na configuração. O clique duplo (o mesmo envio) não conta duas vezes.
 * @return bool true = cabe (e já conta esta)
 */
function cabeNoLimite(string $envio): bool
{
    $c = cfg();
    $teto = max(1, (int) ($c['limite_por_endereco'] ?? 10));
    $janela = 60 * max(1, (int) ($c['janela_minutos'] ?? 10));
    $pasta = __DIR__ . '/dados';
    if (!is_dir($pasta)) {
        mkdir($pasta, 0700, true);
    }
    $f = fopen($pasta . '/limites.json', 'c+');
    flock($f, LOCK_EX);
    $l = json_decode((string) stream_get_contents($f), true) ?: [];
    $agora = time();
    $ip = hash('sha256', ipCliente()); // o endereço não fica guardado em claro
    foreach ($l as $k => $marcas) {
        $l[$k] = array_filter((array) $marcas, fn($t) => $t > $agora - $janela);
        if (!$l[$k]) {
            unset($l[$k]);
        }
    }
    $chave = $ip . ':' . $envio;
    $ja = isset($l[$ip][$chave]);
    $cabe = $ja || count($l[$ip] ?? []) < $teto;
    if ($cabe && !$ja) {
        $l[$ip][$chave] = $agora;
    }
    ftruncate($f, 0);
    rewind($f);
    fwrite($f, json_encode($l));
    fflush($f);
    flock($f, LOCK_UN);
    fclose($f);
    return $cabe;
}

function topo(string $titulo, bool $atualizar = false): void
{
    $nome = cfg()['instituicao'] ?? 'Instituto Ventoalto';
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . ($atualizar ? '<meta http-equiv="refresh" content="3">' : '')
        . '<title>' . h($titulo) . '</title><link rel="stylesheet" href="estilo.css"></head><body>'
        . '<header class="marca"><a class="logo" href="index.php"><svg viewBox="0 0 40 40" width="30" height="30" aria-hidden="true"><path d="M4 30c8-14 24-14 32 0" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/><path d="M11 30c5-8 13-8 18 0" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/><circle cx="20" cy="30" r="3" fill="currentColor"/></svg>'
        . '<span>' . h($nome) . '</span></a></header>';
}

function rodape(): void
{
    $nome = cfg()['instituicao'] ?? 'Instituto Ventoalto';
    echo '<footer class="rodape" id="rodapeDemonstracao"><p><b>' . h($nome) . '</b> é uma instituição fictícia. Este site é uma demonstração: os cursos, os textos e os dados são fictícios.</p></footer></body></html>';
}
