<?php

namespace Core;

/**
 * ENDEREÇO DA REQUISIÇÃO (1.9.1) — o ponto único que decide protocolo, host e caminho do sistema, e em que
 * proxies se confia. Usado pelo index.php da raiz, pelo painel e pelo portal (ConfigController e Seguranca de
 * cada app) e pelas ferramentas de linha de comando. A Config só traz os PARÂMETROS:
 *   PROXIES_CONFIAVEIS  IPs/CIDR dos proxies na frente do servidor. Só de um deles (REMOTE_ADDR) se aceita
 *                       X-Forwarded-For (IP do cliente, D-236) e X-Forwarded-Proto (protocolo). Vazio = nenhum.
 *   HOSTS_PERMITIDOS    hosts aceitos no endereço ('escola.exemplo.invalid *.exemplo.invalid'). Host fora da lista
 *                       vira o primeiro host da lista. Vazio = aceita o Host da requisição (só desenvolvimento).
 *   CAMINHO_BASE        caminho do sistema no domínio ('/vcostaedu/'). Vazio = descoberto pela localização da pasta.
 * Não depende de outra classe: o index.php da raiz o carrega sem autoload. Este arquivo só declara a classe.
 */
final class Requisicao
{
    /** Define HTTPS_ATIVO, URL, URLACAD e URLADM (as que a Config já tiver definido ficam como estão). */
    public static function definir(): void
    {
        defined('HTTPS_ATIVO') || define('HTTPS_ATIVO', self::https());
        defined('URL') || define('URL', self::base());
        defined('URLACAD') || define('URLACAD', URL . 'portal/');
        defined('URLADM') || define('URLADM', URL . 'painel/');
    }

    /** URL base, com barra no fim: protocolo://host[:porta]/caminho/. */
    public static function base(): string
    {
        return (self::https() ? 'https' : 'http') . '://' . self::host() . self::caminho();
    }

    /**
     * HTTPS? O próprio servidor (HTTPS=on) vale sempre. Vindo de proxy confiável, vale o X-Forwarded-Proto
     * (o primeiro valor, o que o usuário usou) e a porta NÃO conta (o Apache atrás do túnel diz 80). Sem proxy
     * confiável, o cabeçalho é ignorado e fica a porta 443.
     */
    public static function https(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if (self::viaProxyConfiavel()) {
            $proto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
            return $proto === 'https';
        }
        return (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
    }

    /** O pedido chegou por um dos PROXIES_CONFIAVEIS? */
    public static function viaProxyConfiavel(): bool
    {
        $remoto = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $proxies = self::proxiesConfiaveis();
        return $remoto !== '' && $proxies && self::ipNaLista($remoto, $proxies);
    }

    /**
     * Host[:porta] do endereço. Com HOSTS_PERMITIDOS, só um host da lista: entrada sem porta aceita o host com
     * qualquer porta; '*.dominio' aceita qualquer subdomínio (não o próprio domínio); fora da lista, o primeiro
     * host da lista sem curinga. Sem a lista, o Host da requisição, se for um nome válido.
     */
    public static function host(): string
    {
        $pedido = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')));
        if (!preg_match('/^([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*|\[[0-9a-f:.]+\])(?::(\d{1,5}))?$/', $pedido, $m)) {
            $pedido = '';
        }
        $permitidos = self::hostsPermitidos();
        if (!$permitidos) {
            return $pedido !== '' ? $pedido : 'localhost';
        }
        if ($pedido !== '') {
            $nome = $m[1];
            foreach ($permitidos as $p) {
                if (strpos($p, '*.') === 0) {
                    $sufixo = substr($p, 1);
                    if (strlen($nome) > strlen($sufixo) && substr($nome, -strlen($sufixo)) === $sufixo) {
                        return $pedido;
                    }
                } elseif ($p === $pedido || $p === $nome) {
                    return $pedido;
                }
            }
        }
        foreach ($permitidos as $p) {
            if (strpos($p, '*') === false) {
                return $p;
            }
        }
        return 'localhost';
    }

    /** Entradas de HOSTS_PERMITIDOS, em minúsculas (host[:porta] ou *.dominio); as inválidas são ignoradas. */
    public static function hostsPermitidos(): array
    {
        $cfg = defined('HOSTS_PERMITIDOS') ? (string) HOSTS_PERMITIDOS : '';
        $o = [];
        foreach (preg_split('/[\s,]+/', strtolower($cfg), -1, PREG_SPLIT_NO_EMPTY) as $x) {
            if (preg_match('/^(\*\.)?[a-z0-9-]+(\.[a-z0-9-]+)*(:\d{1,5})?$|^\[[0-9a-f:.]+\](:\d{1,5})?$/', $x) && !preg_match('/^\*\.[^.]+$/', $x)) {
                $o[] = $x;
            }
        }
        return array_values(array_unique($o));
    }

    /**
     * Caminho do sistema no domínio, com barra no início e no fim. CAMINHO_BASE, se preenchido. Senão, o
     * SCRIPT_NAME sem a parte que fica dentro da pasta do projeto (ex.: '/vcostaedu/painel/index.php' com o script
     * em <projeto>/painel/index.php dá '/vcostaedu/'); vale para /index.php, /painel/ e /portal/, em subpasta, na
     * raiz de um vhost ou por Alias. Na linha de comando, '/'.
     */
    public static function caminho(): string
    {
        $cfg = defined('CAMINHO_BASE') ? trim((string) CAMINHO_BASE) : '';
        if ($cfg !== '') {
            return self::normalizarCaminho($cfg);
        }
        if (PHP_SAPI === 'cli') {
            return '/';
        }
        $raiz = self::barras((string) realpath(dirname(__DIR__)));
        $script = self::barras((string) realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')));
        $nome = self::barras((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($raiz !== '' && $script !== '' && stripos($script, $raiz . '/') === 0) {
            $dentro = substr($script, strlen($raiz));
            if ($dentro !== '' && strlen($nome) >= strlen($dentro) && strcasecmp(substr($nome, -strlen($dentro)), $dentro) === 0) {
                return self::normalizarCaminho(substr($nome, 0, -strlen($dentro)));
            }
        }
        $doc = self::barras((string) realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? '')));
        if ($raiz !== '' && $doc !== '' && ($raiz === $doc || stripos($raiz, $doc . '/') === 0)) {
            return self::normalizarCaminho(substr($raiz, strlen($doc)));
        }
        return '/';
    }

    /** Entradas válidas de PROXIES_CONFIAVEIS (IP ou CIDR, IPv4 ou IPv6). */
    public static function proxiesConfiaveis(): array
    {
        $cfg = defined('PROXIES_CONFIAVEIS') ? (string) PROXIES_CONFIAVEIS : '';
        $o = [];
        foreach (preg_split('/[\s,]+/', $cfg, -1, PREG_SPLIT_NO_EMPTY) as $x) {
            [$ip, $bits] = array_pad(explode('/', $x, 2), 2, null);
            $bin = @inet_pton($ip);
            if ($bin === false || ($bits !== null && (!ctype_digit($bits) || (int) $bits > strlen($bin) * 8))) {
                continue;
            }
            $o[] = $x;
        }
        return $o;
    }

    public static function ipNaLista(string $ip, array $lista): bool
    {
        $b = @inet_pton($ip);
        if ($b === false) {
            return false;
        }
        foreach ($lista as $item) {
            [$rede, $bits] = array_pad(explode('/', $item, 2), 2, null);
            $r = @inet_pton($rede);
            if ($r === false || strlen($r) !== strlen($b)) {
                continue;
            }
            $bits = $bits === null ? strlen($b) * 8 : (int) $bits;
            $bytes = intdiv($bits, 8);
            $resto = $bits % 8;
            if (substr($b, 0, $bytes) !== substr($r, 0, $bytes)) {
                continue;
            }
            if ($resto === 0 || ((ord($b[$bytes]) ^ ord($r[$bytes])) & (0xFF << (8 - $resto)) & 0xFF) === 0) {
                return true;
            }
        }
        return false;
    }

    private static function barras(string $c): string
    {
        return rtrim(str_replace('\\', '/', $c), '/');
    }

    /** Só segmentos seguros (letras, dígitos, . _ ~ -), com barra no início e no fim. */
    private static function normalizarCaminho(string $c): string
    {
        $partes = array_filter(explode('/', str_replace('\\', '/', $c)), fn($p) => $p !== '' && $p !== '.' && $p !== '..' && preg_match('/^[A-Za-z0-9._~-]+$/', $p));
        return $partes ? '/' . implode('/', $partes) . '/' : '/';
    }
}
