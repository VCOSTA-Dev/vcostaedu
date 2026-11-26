<?php

/**
 * O GERADOR DOS ARQUIVOS DE CONFIGURAÇÃO, e a verificação que paga a D12.
 *
 * Veio do instalador do vcostaedu. A diferença, VCOSTAEDU: aqui são DOIS
 * arquivos (painel e portal), e eles não são escritos do zero — saem dos moldes
 * versionados `painel/core/Config.example.php` e `portal/core/Config.example.php`,
 * trocando só os valores que a instalação conhece. Assim toda constante que o
 * sistema lê (e o comentário que explica cada uma) chega ao arquivo gerado, e
 * molde e arquivo não divergem.
 *
 * ATENÇÃO ao nome deste arquivo: ele NÃO pode se chamar `config.php` (o
 * `.gitignore` ignora `Config.php` por nome).
 */

declare(strict_types=1);

/* Só o index.php do instalador carrega este arquivo; pedido direto não responde nada (o .htaccess da pasta já o
   manda para o index.php, que recusa quando o sistema está instalado). */
if (!defined('VCOSTAEDU_INSTALADOR')) {
    http_response_code(404);
    exit;
}

/** As aplicações que têm arquivo de configuração. */
const APLICACOES = ['painel', 'portal'];

/**
 * Os valores que a instalação conhece, por constante. O resto do molde fica como está.
 *
 * - banco: o que a pessoa informou e o instalador testou;
 * - HOSTS_PERMITIDOS: o host pelo qual o navegador chegou aqui (sem porta: aceita qualquer porta);
 * - CAMINHO_BASE: vazio, descoberto sozinho (core/Requisicao.php);
 * - EXIGIR_HTTPS: conforme o acesso — instalado por HTTPS, exige HTTPS; por HTTP, não (sem isso o primeiro
 *   acesso por HTTP entraria em laço de redirecionamento para um HTTPS que não existe);
 * - AMBIENTE: produção;
 * - PASTA_STORAGE: a pasta escolhida na conferência (padrão: storage/ do projeto), com barra no fim.
 *
 * @return array<string, mixed>
 */
function valoresDaInstalacao(array $dados): array
{
    $host = (string) parse_url(urlDaRaiz(), PHP_URL_HOST);

    return [
        'HOST'             => $dados['host'],
        'USER'             => $dados['usuario'],
        'PASS'             => $dados['senha'],
        'DBNAME'           => $dados['dbname'],
        'PORTA'            => (int) ($dados['porta'] ?? 3306),
        'HOSTS_PERMITIDOS' => $host,
        'CAMINHO_BASE'     => '',
        'EXIGIR_HTTPS'     => acessoPorHttps(),
        'AMBIENTE'         => 'producao',
        'PASTA_STORAGE'    => rtrim(str_replace('\\', '/', (string) $dados['storage']), '/') . '/',
    ];
}

function acessoPorHttps(): bool
{
    return parse_url(urlDaRaiz(), PHP_URL_SCHEME) === 'https';
}

/**
 * Monta o conteúdo de um arquivo de configuração a partir do molde.
 *
 * Cada `define('NOME', <valor>);` do molde tem o valor trocado por `var_export()` — uma senha com aspa ou
 * barra invertida não quebra o arquivo. Uma constante esperada que não esteja no molde é ERRO (lança): é o
 * molde que mudou, e gerar um arquivo sem ela seria uma instalação que falha longe daqui.
 */
function montarConfig(string $app, array $dados): string
{
    $molde = (string) @file_get_contents(raizDoProjeto() . "/{$app}/core/Config.example.php");

    if ($molde === '') {
        throw new RuntimeException("Molde de configuração ausente: {$app}/core/Config.example.php");
    }

    foreach (valoresDaInstalacao($dados) as $constante => $valor) {
        $trocas = 0;
        $molde = preg_replace_callback(
            "/define\\('" . $constante . "',\\s*.*?\\);/s",
            static fn(): string => "define('{$constante}', " . var_export($valor, true) . ');',
            $molde,
            1,
            $trocas
        );

        if ($trocas !== 1) {
            throw new RuntimeException("O molde {$app}/core/Config.example.php não define {$constante}.");
        }
    }

    $aviso = "/*\n * ARQUIVO GERADO PELO INSTALADOR em " . date('d/m/Y H:i') . " — e GITIGNORED.\n"
        . " * Guarda a senha do banco: não versione, não publique, não deixe acessível pelo navegador.\n"
        . " * Para reinstalar do zero: apague este arquivo e o do outro app (painel/portal) e esvazie o banco.\n */\n";

    return preg_replace('/^<\?php\s*\n/', "<?php\n\n" . $aviso, $molde, 1);
}

/** @return array<string, string> app => conteúdo */
function montarConfigs(array $dados): array
{
    $conteudos = [];

    foreach (APLICACOES as $app) {
        $conteudos[$app] = montarConfig($app, $dados);
    }

    return $conteudos;
}

/**
 * Tenta gravar os dois. NÃO lança exceção quando falha: falhar a gravação é caminho PRINCIPAL (hospedagem
 * compartilhada), e quem decide o que mostrar é a tela.
 *
 * @return array{ok:bool, motivo:string}
 */
function gravarConfigs(array $conteudos): array
{
    foreach ($conteudos as $app => $conteudo) {
        if (@file_put_contents(caminhoDoConfig($app), $conteudo, LOCK_EX) === false) {
            return ['ok' => false, 'motivo' => "o servidor web não tem permissão de escrita em {$app}/core/"];
        }

        @chmod(caminhoDoConfig($app), 0640);
    }

    return ['ok' => true, 'motivo' => ''];
}

/**
 * Os arquivos que a pessoa criou à mão servem? Confere as constantes de conexão e o banco certo, nos dois.
 *
 * @return array{ok:bool, motivo:string}
 */
function conferirConfigManual(string $dbnameEsperado): array
{
    foreach (APLICACOES as $app) {
        $caminho = caminhoDoConfig($app);

        if (!is_file($caminho)) {
            return ['ok' => false, 'motivo' => "o arquivo {$app}/core/Config.php ainda não apareceu"];
        }

        $conteudo = (string) @file_get_contents($caminho);

        foreach (['HOST', 'USER', 'PASS', 'DBNAME', 'PASTA_STORAGE', 'AMBIENTE'] as $constante) {
            if (!str_contains($conteudo, "define('" . $constante . "'")) {
                return ['ok' => false, 'motivo' => "o arquivo {$app}/core/Config.php não define a constante {$constante}"];
            }
        }

        if (!str_contains($conteudo, "define('DBNAME', " . var_export($dbnameEsperado, true) . ');')) {
            return ['ok' => false, 'motivo' => "o arquivo {$app}/core/Config.php aponta para um banco diferente do que você informou"];
        }
    }

    return ['ok' => true, 'motivo' => ''];
}

// =============================================================================
// A VERIFICAÇÃO DA EXPOSIÇÃO (D12 do vcostaedu)
//
// Os dois arquivos de configuração e a pasta dos arquivos privados ficam (no
// padrão) DENTRO da pasta publicada, e a proteção depende de .htaccess e da
// guarda de constante — configuração, não código. Então o instalador PEDE cada
// um por HTTP e olha o CORPO: o que condena é o conteúdo vir junto, não o
// código de resposta.
// =============================================================================

/**
 * @return array<int, array{alvo:string, veredito:string, detalhe:string, url:string, status:?int}>
 *   veredito: 'protegido' | 'exposto' | 'executado' | 'fora_da_web' | 'nao_medido'
 */
function verificarExposicao(string $pastaStorage): array
{
    $resultados = [];

    foreach (APLICACOES as $app) {
        $resultados[] = verificarUmArquivo(
            "{$app}/core/Config.php",
            caminhoDoConfig($app),
            static fn(string $corpo): bool => str_contains($corpo, '<?php')
                || str_contains($corpo, "define('PASS'")
                || str_contains($corpo, "define('DBNAME'")
        );
    }

    /* A prova do storage: um arquivo com um marcador aleatório. Se o marcador voltar no corpo, a pasta dos PDFs
       emitidos está servida pela web. O arquivo é apagado logo depois. */
    $marcador = 'vcostaedu-prova-' . bin2hex(random_bytes(12));
    $prova = rtrim($pastaStorage, '/') . '/prova-de-exposicao.txt';

    if (@file_put_contents($prova, $marcador) === false) {
        $resultados[] = [
            'alvo' => 'pasta dos arquivos privados',
            'veredito' => 'nao_medido',
            'detalhe' => 'Não foi possível gravar o arquivo de teste na pasta dos arquivos privados.',
            'url' => '',
            'status' => null,
        ];
    } else {
        $resultados[] = verificarUmArquivo(
            'pasta dos arquivos privados',
            str_replace('\\', '/', $prova),
            static fn(string $corpo): bool => str_contains($corpo, $marcador)
        );
        @unlink($prova);
    }

    return $resultados;
}

/** @param callable(string):bool $vazou */
function verificarUmArquivo(string $alvo, string $caminho, callable $vazou): array
{
    $raiz = raizDoProjeto();
    $caminho = str_replace('\\', '/', $caminho);

    if (!str_starts_with($caminho, $raiz . '/')) {
        return [
            'alvo' => $alvo,
            'veredito' => 'fora_da_web',
            'detalhe' => 'Está fora da pasta publicada pelo servidor web: não há endereço que leve a ele.',
            'url' => '',
            'status' => null,
        ];
    }

    $url = urlDaRaiz() . implode('/', array_map('rawurlencode', explode('/', ltrim(substr($caminho, strlen($raiz)), '/'))));
    $resposta = pedirPorHttp($url);

    if ($resposta === null) {
        return [
            'alvo' => $alvo,
            'veredito' => 'nao_medido',
            'detalhe' => 'Não foi possível fazer a requisição de verificação a partir deste servidor. Abra o endereço no navegador: se aparecer o conteúdo, ele está público.',
            'url' => $url,
            'status' => null,
        ];
    }

    [$status, $corpo] = $resposta;

    if ($vazou($corpo)) {
        return [
            'alvo' => $alvo,
            'veredito' => 'exposto',
            'detalhe' => 'O servidor web entregou o conteúdo pelo endereço abaixo.',
            'url' => $url,
            'status' => $status,
        ];
    }

    if ($status === 403 || $status === 404) {
        return [
            'alvo' => $alvo,
            'veredito' => 'protegido',
            'detalhe' => 'O servidor web recusou a requisição (HTTP ' . $status . ').',
            'url' => $url,
            'status' => $status,
        ];
    }

    return [
        'alvo' => $alvo,
        'veredito' => 'executado',
        'detalhe' => 'O conteúdo não foi entregue (HTTP ' . $status . '): o PHP processou o arquivo e a proteção do início do arquivo bloqueou o acesso direto.',
        'url' => $url,
        'status' => $status,
    ];
}

/** Algum alvo foi exposto? */
function algumExposto(array $resultados): bool
{
    foreach ($resultados as $r) {
        if ($r['veredito'] === 'exposto') {
            return true;
        }
    }

    return false;
}

/**
 * Uma requisição HTTP curta ao próprio servidor (tempo limite baixo de propósito: um servidor de um processo
 * só travaria pedindo a si mesmo).
 *
 * @return array{0:int, 1:string}|null
 */
function pedirPorHttp(string $url): ?array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $corpo  = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return $corpo === false ? null : [$status, (string) $corpo];
    }

    if (!ini_get('allow_url_fopen')) {
        return null;
    }

    $contexto = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true, 'follow_location' => 0]]);
    $corpo = @file_get_contents($url, false, $contexto);

    if ($corpo === false) {
        return null;
    }

    $status = 0;

    foreach ($http_response_header ?? [] as $cabecalho) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $cabecalho, $achado)) {
            $status = (int) $achado[1];
        }
    }

    return [$status, $corpo];
}
