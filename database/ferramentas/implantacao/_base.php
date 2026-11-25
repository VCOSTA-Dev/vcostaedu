<?php
/*
 * Base das ferramentas de implantação (E8). Só linha de comando.
 *
 * - Saída padronizada: OK / ALERTA / FALHA, com resumo e código de saída 1 se houver FALHA.
 * - Pergunta no terminal sem eco (senhas nunca vão para arquivo nem para a linha de comando).
 * - Cliente HTTP simples (curl) com cookies em memória de arquivo temporário apagado no fim.
 *
 * Não carrega a Config: quem precisa do banco inclui ../comum.php.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const VCOSTAEDU_RAIZ = __DIR__ . '/../../..';
date_default_timezone_set('America/Sao_Paulo'); // o mesmo fuso das Config (datas de pacote e backup)

final class Saida
{
    public static int $ok = 0;
    public static int $alertas = 0;
    public static int $falhas = 0;

    public static function secao(string $t): void
    {
        echo "\n== {$t}\n";
    }

    public static function ok(string $t): void
    {
        self::$ok++;
        echo "  [OK]     {$t}\n";
    }

    public static function alerta(string $t): void
    {
        self::$alertas++;
        echo "  [ALERTA] {$t}\n";
    }

    public static function falha(string $t): void
    {
        self::$falhas++;
        echo "  [FALHA]  {$t}\n";
    }

    public static function info(string $t): void
    {
        echo "           {$t}\n";
    }

    /** Resumo e código de saída: 1 se houve FALHA; 0 caso contrário (ALERTA não reprova). */
    public static function fim(): never
    {
        echo "\nResumo: " . self::$ok . ' OK, ' . self::$alertas . ' ALERTA, ' . self::$falhas . " FALHA\n";
        exit(self::$falhas > 0 ? 1 : 0);
    }
}

/** Opções --nome=valor e --flag; o resto são argumentos posicionais. */
function vcostaedu_args(array $argv): array
{
    $o = ['_' => []];
    foreach (array_slice($argv, 1) as $a) {
        if (preg_match('/^--([a-z0-9-]+)(?:=(.*))?$/i', $a, $m)) {
            $o[$m[1]] = $m[2] ?? true;
        } else {
            $o['_'][] = $a;
        }
    }
    return $o;
}

/** URL base do sistema, com barra no fim (ex.: https://portal.exemplo.invalid/). */
function vcostaedu_url_base(?string $u): string
{
    $u = trim((string) $u);
    if (!preg_match('#^https?://[^/\s]+#i', $u)) {
        fwrite(STDERR, "Informe a URL base do sistema (a pasta que contém painel/ e portal/), ex.: https://escola.exemplo.invalid/\n");
        exit(2);
    }
    return rtrim($u, '/') . '/';
}

/**
 * Lê uma linha do terminal. Com $oculto, sem eco (Windows: PowerShell Read-Host -AsSecureString; Unix: stty).
 * Se a entrada não é um terminal (redirecionada), lê a linha tal como vem.
 */
function vcostaedu_perguntar(string $rotulo, bool $oculto = false): string
{
    fwrite(STDOUT, $rotulo);
    $tty = function_exists('stream_isatty') ? @stream_isatty(STDIN) : true;
    if (!$oculto || !$tty) {
        $l = fgets(STDIN);
        if (!$tty) {
            fwrite(STDOUT, "\n");
        }
        return $l === false ? '' : rtrim($l, "\r\n");
    }
    if (DIRECTORY_SEPARATOR === '\\') {
        $cmd = 'powershell -NoProfile -Command "$s = Read-Host -AsSecureString; '
            . '$p = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($s); '
            . '[Console]::Out.Write([Runtime.InteropServices.Marshal]::PtrToStringAuto($p))"';
        $v = (string) shell_exec($cmd);
        fwrite(STDOUT, "\n");
        return rtrim($v, "\r\n");
    }
    shell_exec('stty -echo 2>/dev/null');
    $l = fgets(STDIN);
    shell_exec('stty echo 2>/dev/null');
    fwrite(STDOUT, "\n");
    return $l === false ? '' : rtrim($l, "\r\n");
}

/** Cliente HTTP: GET/POST com cookies próprios; devolve [código, cabeçalhos (texto), corpo, url final]. */
final class Http
{
    private string $jar;

    public function __construct(private bool $inseguro = false)
    {
        $this->jar = tempnam(sys_get_temp_dir(), 'e1c');
    }

    public function __destruct()
    {
        @unlink($this->jar);
    }

    public function req(string $url, ?array $post = null, bool $seguir = true): array
    {
        $c = curl_init($url);
        curl_setopt_array($c, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => $seguir, CURLOPT_MAXREDIRS => 8,
            CURLOPT_COOKIEJAR => $this->jar, CURLOPT_COOKIEFILE => $this->jar, CURLOPT_TIMEOUT => 60, CURLOPT_USERAGENT => 'vcostaedu-implantacao',
            CURLOPT_SSL_VERIFYPEER => !$this->inseguro, CURLOPT_SSL_VERIFYHOST => $this->inseguro ? 0 : 2,
        ]);
        if ($post !== null) {
            curl_setopt($c, CURLOPT_POST, true);
            curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        $r = curl_exec($c);
        if ($r === false) {
            $e = curl_error($c);
            curl_close($c);
            return [0, '', "erro de conexão: {$e}", $url];
        }
        $tam = (int) curl_getinfo($c, CURLINFO_HEADER_SIZE);
        $codigo = (int) curl_getinfo($c, CURLINFO_HTTP_CODE);
        $final = (string) curl_getinfo($c, CURLINFO_EFFECTIVE_URL);
        curl_close($c);
        $cab = substr((string) $r, 0, $tam);
        /* com redirecionamentos, fica só o bloco de cabeçalhos da última resposta */
        $blocos = preg_split("/\r\n\r\n(?=HTTP\/)/", trim($cab));
        return [$codigo, (string) end($blocos), substr((string) $r, $tam), $final];
    }
}

/** Erros de PHP visíveis numa página. */
function vcostaedu_erros_php(string $html): string
{
    preg_match_all('/(Warning|Notice|Deprecated|Fatal error|Uncaught)\b[^<]{0,160}/', $html, $m);
    return implode(' | ', array_slice($m[0], 0, 2));
}

/** A página é a tela de login? (o formulário de login tem o botão SendLogin) */
function vcostaedu_eh_login(string $html): bool
{
    return str_contains($html, 'name="SendLogin"');
}

/** Cliente mysql/mysqldump: o do XAMPP, o do PATH, ou o indicado em VCOSTAEDU_MYSQL / VCOSTAEDU_MYSQLDUMP. */
function vcostaedu_bin(string $nome): string
{
    $env = getenv($nome === 'mysql' ? 'VCOSTAEDU_MYSQL' : 'VCOSTAEDU_MYSQLDUMP');
    if ($env) {
        return $env;
    }
    $xampp = "C:/xampp/mysql/bin/{$nome}.exe";
    return is_file($xampp) ? $xampp : $nome;
}

/** Tamanho legível. */
function vcostaedu_tam(int $b): string
{
    return $b >= 1048576 ? number_format($b / 1048576, 1, ',', '.') . ' MB' : number_format($b / 1024, 1, ',', '.') . ' KB';
}
