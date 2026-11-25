<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Rodar uma ferramenta de database/ferramentas/ pelo PHP de linha de comando, a partir de uma tela (Onda 2, Etapa 4).
 * O mesmo jeito do instalador (instalar/administrador.php, phpDeLinhaDeComando): o PHP do servidor web pode não ser o
 * de linha de comando (no Windows, o Apache carrega o PHP como módulo). Sem o PHP de linha de comando ou com
 * proc_open desligado, devolve o comando para a pessoa rodar à mão.
 */
class AdmsLinhaDeComando
{
    public static function php(): string
    {
        $candidatos = [];
        if (PHP_BINARY !== '' && preg_match('/php(-cli)?(\.exe)?$/i', PHP_BINARY)) {
            $candidatos[] = PHP_BINARY;
        }
        $exe = DIRECTORY_SEPARATOR === '\\' ? 'php.exe' : 'php';
        $ini = php_ini_loaded_file();
        if (is_string($ini) && $ini !== '') {
            $candidatos[] = dirname($ini) . DIRECTORY_SEPARATOR . $exe;
        }
        $candidatos[] = PHP_BINDIR . DIRECTORY_SEPARATOR . $exe;
        foreach ($candidatos as $c) {
            if (is_file($c)) {
                return $c;
            }
        }
        return '';
    }

    public static function disponivel(): bool
    {
        $desligadas = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        return self::php() !== '' && function_exists('proc_open') && !in_array('proc_open', $desligadas, true);
    }

    /** @return array{0:int, 1:string} o código de saída e a saída (as duas correntes, juntas) */
    public static function rodar(string $ferramenta, array $args = [], string $entrada = ''): array
    {
        $raiz = dirname(__DIR__, 5);
        $cmd = escapeshellarg(self::php()) . ' ' . escapeshellarg($raiz . '/database/ferramentas/' . $ferramenta);
        foreach ($args as $a) {
            $cmd .= ' ' . escapeshellarg((string) $a);
        }
        $p = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pp, $raiz);
        if (!is_resource($p)) {
            return [127, 'Não foi possível rodar a ferramenta.'];
        }
        fwrite($pp[0], $entrada);
        fclose($pp[0]);
        $saida = stream_get_contents($pp[1]) . stream_get_contents($pp[2]);
        fclose($pp[1]);
        fclose($pp[2]);
        return [proc_close($p), (string) $saida];
    }
}
