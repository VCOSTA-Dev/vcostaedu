<?php

/**
 * AS PASTAS DE EXECUÇÃO — VCOSTAEDU.
 *
 * Três pastas que o sistema usa e que NÃO vêm no repositório (o .gitignore as deixa de fora, porque o conteúdo é
 * dado de execução): a dos arquivos privados (PDFs do histórico emitido, imagens de assinatura, cache de fontes do
 * Dompdf), a do logo da instituição e a dos vídeos dos guias. O instalador cria cada uma com o mesmo .htaccess que
 * a versão de origem versionava ali.
 *
 * A dos arquivos privados é CONFIGURÁVEL (PASTA_STORAGE): o padrão é `storage/` do projeto, negado à web pelo
 * .htaccess; quem controla o servidor deve apontá-la para fora da pasta publicada.
 */

declare(strict_types=1);

/* Só o index.php do instalador carrega este arquivo; pedido direto não responde nada (o .htaccess da pasta já o
   manda para o index.php, que recusa quando o sistema está instalado). */
if (!defined('VCOSTAEDU_INSTALADOR')) {
    http_response_code(404);
    exit;
}

const HTACCESS_STORAGE = "# Arquivos privados (PDFs emitidos, imagens de assinatura, cache de fontes). Nada aqui é\n"
    . "# servido pela web: o acesso é só pelos controllers, com permissão. Criado pelo instalador.\n"
    . "Require all denied\n";

const HTACCESS_INSTITUICAO = "# Logo da instituição. Só imagens; nada aqui executa nem é listado. Criado pelo instalador.\n"
    . "Options -Indexes -ExecCGI\n"
    . "RemoveHandler .php .phtml .phar .pl .py .cgi .sh\n"
    . "RemoveType .php .phtml .phar\n"
    . "<FilesMatch \"(?i)\\.(php\\d?|phtml|phar|pl|py|cgi|sh|html?|js|svg|htaccess)$\">\n"
    . "    Require all denied\n"
    . "</FilesMatch>\n";

const HTACCESS_VIDEOS = "# Vídeos dos guias da Ajuda. O Apache serve com pedido de trecho (Range, 206), que o player usa para avançar e\n"
    . "# voltar. As legendas e os capítulos precisam do tipo text/vtt. Criado pelo instalador.\n"
    . "AddType video/mp4 .mp4\n"
    . "AddType text/vtt .vtt\n"
    . "AddType text/plain .txt\n"
    . "AddDefaultCharset utf-8\n"
    . "Options -Indexes\n"
    . "<IfModule mod_headers.c>\n"
    . "  Header set X-Content-Type-Options \"nosniff\"\n"
    . "  Header set Cache-Control \"public, max-age=86400\"\n"
    . "</IfModule>\n";

/** O padrão da pasta dos arquivos privados. */
function storagePadrao(): string
{
    return raizDoProjeto() . '/storage';
}

/**
 * Cria as pastas e os .htaccess. Devolve a lista do que não deu para criar (vazia = tudo certo). Não lança.
 *
 * @return string[]
 */
function criarPastasDeExecucao(string $storage): array
{
    $storage = rtrim(str_replace('\\', '/', $storage), '/');
    $raiz = raizDoProjeto();
    $falhas = [];

    $pastas = [
        $storage                         => HTACCESS_STORAGE,
        $storage . '/historico'          => null,
        $storage . '/historico/emissoes' => null,
        $storage . '/historico/assinaturas' => null,
        $storage . '/fontes'             => null,
        $raiz . '/assets/instituicao'    => HTACCESS_INSTITUICAO,
        $raiz . '/assets/videos/guias'   => HTACCESS_VIDEOS,
    ];

    foreach ($pastas as $pasta => $htaccess) {
        if (!is_dir($pasta) && !@mkdir($pasta, 0755, true) && !is_dir($pasta)) {
            $falhas[] = $pasta;
            continue;
        }

        if ($htaccess !== null && !is_file($pasta . '/.htaccess') && @file_put_contents($pasta . '/.htaccess', $htaccess) === false) {
            $falhas[] = $pasta . '/.htaccess';
        }
    }

    return $falhas;
}

/** O caminho informado serve como pasta dos arquivos privados? Devolve o motivo, ou '' quando serve. */
function validarStorage(string $caminho): string
{
    $caminho = trim($caminho);

    if ($caminho === '') {
        return 'Informe a pasta dos arquivos privados.';
    }

    $absoluto = preg_match('#^([A-Za-z]:[\\\\/]|/)#', $caminho) === 1;

    if (!$absoluto) {
        return 'Informe o caminho completo da pasta (a partir da raiz do disco).';
    }

    return '';
}
