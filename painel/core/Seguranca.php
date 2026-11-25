<?php

namespace Core;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 2) . '/core/Requisicao.php';

/**
 * SESSÃO E CABEÇALHOS DE SEGURANÇA (E7). O mesmo arquivo existe no painel e no portal; o
 * index.php de cada app chama iniciar() depois de carregar a Config e antes de rotear.
 *
 * PORTAL (pode ficar dentro do Moodle, em iframe):
 *   - Content-Security-Policy: frame-ancestors 'self' + ORIGENS_PERMITIDAS_IFRAME (só 'self' sem origens);
 *     X-Frame-Options SAMEORIGIN só quando não há origens (o XFO não aceita lista);
 *   - cookie de sessão: HttpOnly; Secure com HTTPS; SameSite=None SÓ com HTTPS e origem configurada
 *     (senão Lax); com iframe ativo e COOKIE_PARTICIONADO, também Partitioned (CHIPS): o cookie fica
 *     guardado por site de cima, e continua valendo quando o navegador bloqueia cookie de terceiros.
 * PAINEL (inclusive a verificação pública): nunca embutível — frame-ancestors 'none' + X-Frame-Options
 *   DENY; cookie SameSite=Lax, HttpOnly, Secure com HTTPS.
 * Os dois: EXIGIR_HTTPS redireciona HTTP → HTTPS (e liga o HSTS); nosniff e Referrer-Policy.
 * 1.9.1: protocolo, host e proxies confiáveis vêm de Core\Requisicao (core/Requisicao.php, na raiz do projeto).
 */
final class Seguranca
{
    public static function https(): bool
    {
        if (defined('HTTPS_ATIVO')) {
            return (bool) HTTPS_ATIVO;
        }
        return Requisicao::https();
    }

    /** Origens válidas de ORIGENS_PERMITIDAS_IFRAME (só https://host[:porta]); as inválidas são ignoradas. */
    public static function origensIframe(): array
    {
        $cfg = defined('ORIGENS_PERMITIDAS_IFRAME') ? (string) ORIGENS_PERMITIDAS_IFRAME : '';
        $o = [];
        foreach (preg_split('/[\s,]+/', $cfg, -1, PREG_SPLIT_NO_EMPTY) as $x) {
            $x = strtolower(rtrim($x, '/'));
            if (preg_match('#^https://([a-z0-9.-]+|\[[0-9a-f:]+\])(:\d{1,5})?$#', $x)) {
                $o[] = $x;
            }
        }
        return array_values(array_unique($o));
    }

    /** O portal está configurado para funcionar em iframe de outro site (HTTPS + origem)? */
    public static function iframeAtivo(): bool
    {
        return self::https() && (bool) self::origensIframe();
    }

    /**
     * E8 (D-236): o IP do CLIENTE — a função única usada pelo limite de tentativas e pelos logs.
     * Só confia no X-Forwarded-For quando REMOTE_ADDR é um dos PROXIES_CONFIAVEIS; aí lê a lista da direita
     * para a esquerda e devolve o primeiro IP que NÃO é proxy confiável. Entrada inválida na lista: fica o
     * REMOTE_ADDR (um cabeçalho forjado não escolhe o IP).
     */
    public static function ipCliente(): string
    {
        $remoto = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if ($remoto === '') {
            return 'cli';
        }
        $proxies = Requisicao::proxiesConfiaveis();
        if (!$proxies || !Requisicao::ipNaLista($remoto, $proxies)) {
            return $remoto;
        }
        $xff = array_map('trim', explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')));
        $ultimo = $remoto;
        for ($i = count($xff) - 1; $i >= 0; $i--) {
            $ip = $xff[$i];
            if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                return $ultimo;
            }
            if (!Requisicao::ipNaLista($ip, $proxies)) {
                return $ip;
            }
            $ultimo = $ip;
        }
        return $ultimo;
    }

    /**
     * Onda 6, Etapa 6: o POST veio deste mesmo endereço? Pelo Origin (os navegadores o mandam em todo POST) e, sem ele,
     * pelo Referer. Sem nenhum dos dois (um programa, não um navegador), aceita: o cookie de sessão (SameSite) e a
     * permissão da rota continuam valendo.
     */
    public static function mesmaOrigem(): bool
    {
        $de = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
        if ($de === '' || $de === 'null') {
            $de = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        }
        if ($de === '') {
            return true;
        }
        $p = parse_url($de);
        if (!$p || empty($p['host'])) {
            return false;
        }
        $origem = strtolower($p['host']) . (isset($p['port']) && !in_array((int) $p['port'], [80, 443], true) ? ':' . (int) $p['port'] : '');
        $daqui = preg_replace('/:(80|443)$/', '', strtolower(Requisicao::host()));
        return $origem === $daqui;
    }

    /**
     * Onda 6, Etapa 6: a sessão sem uso há mais de SESSAO_INATIVA_MINUTOS (padrão 120; 0 desliga) é encerrada: a
     * próxima tela pede para entrar de novo, com o motivo.
     */
    private static function inatividade(): void
    {
        $limite = defined('SESSAO_INATIVA_MINUTOS') ? (int) SESSAO_INATIVA_MINUTOS : 120;
        $agora = time();
        if ($limite > 0 && isset($_SESSION['vc_uso']) && $agora - (int) $_SESSION['vc_uso'] > $limite * 60) {
            $tinhaLogin = count($_SESSION) > 1;
            $_SESSION = [];
            session_regenerate_id(true);
            if ($tinhaLogin) {
                $_SESSION['msg'] = "<div class='alert alert-info alert-dismissible' id='sessaoExpirada'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
                    . "Você ficou mais de {$limite} minutos sem usar o sistema: por segurança, entre de novo.</div>";
            }
        }
        $_SESSION['vc_uso'] = $agora;
    }

    public static function iniciar(string $app): void
    {
        $portal = $app === 'portal';
        session_name($portal ? 'VCOSTAEDU_PORTAL' : 'VCOSTAEDU_PAINEL');
        if (PHP_SAPI === 'cli') {
            session_start();
            return;
        }
        $https = self::https();
        if (defined('EXIGIR_HTTPS') && EXIGIR_HTTPS && !$https) {
            /* 1.9.1: o host passa por HOSTS_PERMITIDOS; atrás de proxy confiável com X-Forwarded-Proto https, não chega aqui */
            header('Location: https://' . Requisicao::host() . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
            exit;
        }
        $origens = $portal ? self::origensIframe() : [];
        $iframe = $portal && $https && $origens;
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => $iframe ? 'None' : 'Lax']);
        if ($iframe && (!defined('COOKIE_PARTICIONADO') || COOKIE_PARTICIONADO)) {
            /* o PHP 8.2 não tem a opção "partitioned": acrescenta no cabeçalho, na hora de enviar */
            header_register_callback(function () {
                $cookies = [];
                foreach (headers_list() as $h) {
                    if (stripos($h, 'Set-Cookie:') === 0) {
                        $cookies[] = trim(substr($h, 11));
                    }
                }
                if (!$cookies) {
                    return;
                }
                header_remove('Set-Cookie');
                foreach ($cookies as $c) {
                    if (stripos($c, session_name() . '=') === 0 && stripos($c, 'partitioned') === false) {
                        $c .= '; Partitioned';
                    }
                    header('Set-Cookie: ' . $c, false);
                }
            });
        }
        session_start();
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()'); // Onda 6, Etapa 6
        /* Onda 6, Etapa 6 (docs/SEGURANCA_REVISAO.md): o envio forjado — um POST que vem de outro site é recusado */
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !self::mesmaOrigem()) {
            http_response_code(403);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Envio recusado</title></head><body style="font-family:sans-serif;max-width:40rem;margin:3rem auto">'
                . '<h1>Envio recusado</h1><p>Este formulário veio de outro site e não foi aceito, por segurança. Se foi você, abra o sistema pelo endereço dele e faça de novo.</p></body></html>';
            exit;
        }
        self::inatividade();
        if ($portal) {
            header("Content-Security-Policy: frame-ancestors 'self'" . ($origens ? ' ' . implode(' ', $origens) : ''));
            if (!$origens) {
                header('X-Frame-Options: SAMEORIGIN');
            }
        } else {
            header("Content-Security-Policy: frame-ancestors 'none'");
            header('X-Frame-Options: DENY');
        }
        if ($https && defined('EXIGIR_HTTPS') && EXIGIR_HTTPS) {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }
}
