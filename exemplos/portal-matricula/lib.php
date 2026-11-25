<?php
/*
 * O PORTAL DE MATRÍCULA DE EXEMPLO — o que as páginas usam. Só conversa com a API do VCOSTAEDU (nunca com o banco, nunca
 * com o Moodle), do lado do servidor: a chave fica na configuração (config.local.php), nunca no navegador.
 */
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'lib.php') {
    http_response_code(404);
    exit;
}
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
session_name('PORTAL_MATRICULA_EXEMPLO');
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

function token(): string
{
    return $_SESSION['token'] ??= bin2hex(random_bytes(16));
}

/**
 * Uma chamada à API: ['status', 'json'] (status 0 = a API não respondeu). A chave de repetição (Idempotency-Key) vem de
 * quem chama, derivada do ENVIO do formulário: o clique duplo e a nova tentativa mandam a mesma, e a API devolve a mesma
 * resposta, sem gravar duas vezes (Onda 8: antes era sorteada a cada chamada, e assim não protegia nada).
 */
function api(string $metodo, string $rota, ?array $corpo = null, ?string $repeticao = null): array
{
    $c = cfg();
    $local = in_array(parse_url((string) ($c['api'] ?? ''), PHP_URL_HOST), ['localhost', '127.0.0.1', '::1'], true);
    $ch = curl_init(rtrim((string) ($c['api'] ?? ''), '/') . $rota);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => !$local,
        CURLOPT_HTTPHEADER => array_merge(['Authorization: Bearer ' . ($c['chave'] ?? ''), 'Accept: application/json'],
            $corpo !== null ? ['Content-Type: application/json'] : [], $repeticao !== null ? ['Idempotency-Key: ' . $repeticao] : []),
        CURLOPT_POSTFIELDS => $corpo !== null ? json_encode($corpo, JSON_UNESCAPED_UNICODE) : null,
    ]);
    $texto = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    return ['status' => $texto === false ? 0 : $status, 'json' => is_string($texto) ? json_decode($texto, true) : null];
}

/** A mensagem de erro da API, para mostrar à pessoa. */
function motivo(array $r): string
{
    if ($r['status'] === 0) {
        return 'O sistema acadêmico não respondeu agora. Tente de novo em alguns minutos.';
    }
    return (string) ($r['json']['erro']['mensagem'] ?? ('O sistema acadêmico recusou (' . $r['status'] . ').'));
}

function topo(string $titulo): void
{
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . h($titulo) . '</title><style>
        body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f4f6f8;color:#1d273b;margin:0}
        main{max-width:560px;margin:0 auto;padding:16px}
        .aviso{background:#fff3cd;border:1px solid #e9c46a;border-radius:6px;padding:10px 12px;margin:12px 0;font-size:.95rem}
        .cartao{background:#fff;border:1px solid #dce1e7;border-radius:8px;padding:16px 18px;margin:12px 0}
        label{display:block;font-weight:600;margin:12px 0 4px}
        input,select{width:100%;box-sizing:border-box;padding:9px;border:1px solid #c5ccd6;border-radius:6px;font:inherit}
        button,.botao{display:inline-block;margin-top:16px;background:#1d5fbf;color:#fff;border:0;border-radius:6px;padding:10px 16px;font:inherit;cursor:pointer;text-decoration:none}
        .erro{background:#fde8e8;border:1px solid #e4a1a1;border-radius:6px;padding:10px 12px;margin:12px 0}
        ol.andamento{list-style:none;padding:0} ol.andamento li{padding:8px 0;border-bottom:1px solid #eef1f4}
        .feito::before{content:"✔ ";color:#1d7a35} .falta::before{content:"○ ";color:#8a95a3} .recusado::before{content:"✘ ";color:#b42318}
        small{color:#5f6b7a}
        @media (prefers-color-scheme: dark){
          body{background:#12161f;color:#e7eaf0} .cartao{background:#1a2030;border-color:#2a3244}
          input,select{background:#12161f;color:#e7eaf0;border-color:#4b5563}
          .aviso{background:#3a2e10;border-color:#8a6d1f;color:#f6e7c1} .erro{background:#3b1d1d;border-color:#8a3a3a;color:#f5c7c7}
          button,.botao{background:#2563eb} small{color:#a3adbb} ol.andamento li{border-color:#2a3244}
          .feito::before{color:#4ade80} .falta::before{color:#a3adbb} .recusado::before{color:#f5a3a3}
        }</style></head><body><main>'
        . '<h1 style="font-size:1.4rem">' . h(cfg()['instituicao'] ?? 'Portal de matrícula') . '</h1>'
        . '<div class="aviso" id="avisoExemplo"><b>Isto é um EXEMPLO.</b> Um portal de matrícula mínimo, para mostrar como um site de inscrições conversa com a API do VCOSTAEDU. Cada instituição tem o seu.</div>';
}

function rodape(): void
{
    echo '</main></body></html>';
}
