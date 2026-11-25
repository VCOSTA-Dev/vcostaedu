<?php

/**
 * O BOOTSTRAP DO INSTALADOR — e ele não é o bootstrap da aplicação.
 *
 * Veio do instalador do vcostaedu (D10 de lá: entrada própria, fora do
 * roteador). O que mudou para o VCOSTAEDU está marcado "VCOSTAEDU:".
 *
 * ===================== POR QUE FORA DO ROTEADOR MVC =========================
 *
 * Decisão D10. O instalador roda ANTES de existir `Config.php`, banco, sessão
 * de aplicação ou linha em `adms_paginas`. O roteador do sistema consulta
 * `adms_paginas` antes de instanciar qualquer classe, e está medido no
 * `LEVANTAMENTO_INSTALADOR.md` (§1.4.2) que, com banco vazio, ele não cai em
 * tela nenhuma: morre com HTTP 500 e uma frase de 207 bytes. Não há ponto de
 * enxerto ali.
 *
 * Este arquivo herda o desenho do `webhook/comum.php` de outro projeto da casa, que
 * resolve o mesmo problema pelo mesmo motivo, e traz de lá as três coisas que
 * ele já pagou:
 *
 *   1. nenhum erro sai com caminho de servidor, usuário de banco ou SQL no corpo;
 *   2. a raiz do projeto é PROCURADA, não presumida;
 *   3. `display_errors` desligado — warning impresso no meio do `<head>` quebra
 *      a página e ainda vaza caminho absoluto.
 *
 * ========================== O QUE ELE NÃO FAZ ===============================
 *
 * Não carrega o autoloader da aplicação, não define a constante-guarda do
 * projeto e não toca em nada de `app/`. Se um dia precisar, é sinal de que
 * alguma coisa do instalador foi parar no lugar errado.
 */

declare(strict_types=1);

/* Só o index.php do instalador carrega este arquivo; pedido direto não responde nada (o .htaccess da pasta já o
   manda para o index.php, que recusa quando o sistema está instalado). */
if (!defined('VCOSTAEDU_INSTALADOR')) {
    http_response_code(404);
    exit;
}

/* Warning impresso antes do `<!DOCTYPE>` quebra o HTML e vaza caminho de
   arquivo. O erro vai para o log do servidor, onde é útil e não é público. */
ini_set('display_errors', '0');
error_reporting(E_ALL);

/* Nome de sessão PRÓPRIO. A sessão da aplicação (a que o `index.php` abre) não
   existe aqui e não pode ser reaproveitada: usar o mesmo nome faria o
   instalador e o sistema instalado brigarem pelo mesmo arquivo de sessão, e o
   sintoma — sessão que "some" depois de instalar — apareceria longe da causa. */
session_name('vcostaedu_instalador');
session_start();

/* Nem cache nem histórico: um passo do instalador servido do cache do
   navegador mostra estado que já mudou no servidor. */
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

/**
 * QUALQUER exceção não tratada vira uma página de erro do instalador.
 *
 * O detalhe técnico vai para o log; a tela recebe uma frase que diz o que
 * fazer. É a regra do webhook aplicada a uma tela em vez de a um JSON.
 */
set_exception_handler(static function (Throwable $e): void {
    error_log('[vcostaedu][instalador] ' . $e->getMessage()
        . ' em ' . $e->getFile() . ':' . $e->getLine());

    if (!headers_sent()) {
        http_response_code(500);
    }

    if (function_exists('telaErroFatal')) {
        telaErroFatal(mensagemCurta($e));

        return;
    }

    echo 'Erro interno do instalador. Veja o log do servidor.';
});

/**
 * O erro em uma frase, sem caminho de arquivo, sem SQL e sem credencial.
 *
 * `getMessage()` cru NUNCA vai para a tela: a mensagem do PDO cita host,
 * usuário e banco, e é exatamente o que o `AdmsConn` desse outro projeto da casa
 * registra que não pode vazar.
 */
function mensagemCurta(Throwable $e): string
{
    $texto = $e->getMessage();

    if (stripos($texto, 'SQLSTATE') !== false || $e instanceof PDOException) {
        return 'O banco de dados recusou a operação. Confira os dados informados e tente novamente.';
    }

    if (stripos($texto, 'permission') !== false || stripos($texto, 'denied') !== false) {
        return 'O servidor web não tem permissão para essa operação.';
    }

    return 'Não foi possível concluir a operação. Os detalhes foram registrados no log do servidor.';
}

/**
 * A RAIZ DO PROJETO — procurada, não presumida.
 *
 * Aqui `instalar/` mora dentro do projeto, e o pai é a raiz. Mas o caminho
 * presumido é o defeito que o `webhook/comum.php` documenta: quando o layout é
 * outro, a leitura falha CALADA e o instalador grava o arquivo no lugar errado
 * — sintoma que não aponta para cá.
 *
 * O critério são DOIS marcos, porque um só casaria com qualquer pasta ao lado.
 */
function raizDoProjeto(): string
{
    static $raiz = null;

    if ($raiz !== null) {
        return $raiz;
    }

    foreach ([dirname(__DIR__), dirname(__DIR__, 2)] as $candidato) {
        if (is_file($candidato . '/index.php') && is_dir($candidato . '/assets')) {
            return $raiz = rtrim(str_replace('\\', '/', $candidato), '/');
        }
    }

    throw new RuntimeException('Raiz do projeto não encontrada a partir de instalar/.');
}

/**
 * ONDE FICAM OS ARQUIVOS DE CONFIGURAÇÃO — dois, um por aplicação.
 *
 * O sistema tem duas aplicações (o painel e o portal do aluno), e cada uma lê
 * o seu `core/Config.php`, no caminho fixo que o `index.php` de cada uma já
 * usa. O instalador grava os dois a partir dos moldes versionados
 * (`Config.example.php`), com os mesmos dados de banco.
 *
 * O caminho não é configurável aqui (diferente da D12 do vcostaedu): as duas
 * aplicações carregam a configuração por caminho relativo, e mudar isso é
 * mexer no esqueleto herdado, o que esta onda não faz. A contrapartida da D12
 * continua valendo: depois de gravar, o instalador PEDE os dois arquivos por
 * HTTP e reprova se algum vier com conteúdo.
 */
function caminhoDoConfig(string $app = 'painel'): string
{
    return raizDoProjeto() . '/' . ($app === 'portal' ? 'portal' : 'painel') . '/core/Config.php';
}

/** O diretório onde o arquivo deve ser gravado. */
function diretorioDoConfig(string $app = 'painel'): string
{
    return dirname(caminhoDoConfig($app));
}

/** Os arquivos ficam dentro da pasta publicada: a verificação por HTTP sempre tem o que verificar. */
function configDentroDaRaizServida(string $app = 'painel'): bool
{
    return str_starts_with(caminhoDoConfig($app), raizDoProjeto() . '/');
}

/**
 * A URL da raiz do projeto, derivada do que o navegador acabou de pedir.
 *
 * NÃO existe a constante `URL` aqui — ela nasce dentro do `Config.php`, que é o
 * arquivo que este instalador ainda vai criar. Toda URL desta pasta sai daqui
 * ou é relativa.
 */
function urlDaRaiz(): string
{
    $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

    /* O diretório de `instalar/index.php`, menos o último segmento. */
    $base = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/instalar/index.php')));
    $base = rtrim(dirname($base), '/');

    return $esquema . '://' . $host . $base . '/';
}

// =============================================================================
// A RECUSA QUE NASCE PRIMEIRO — §4.1 do LEVANTAMENTO_INSTALADOR.md
//
// Ela roda em TODA requisição de `instalar/`, antes de qualquer tela, e é o que
// impede esta pasta de virar porta aberta depois que o sistema estiver de pé.
//
// NÃO HÁ MODO DE TESTE, e é deliberado (regra 8): uma recusa que se pode
// desligar não responde a pergunta que ela existe para responder.
//
// A ÚNICA passagem é uma INSTALAÇÃO EM CURSO — o instalador já gravou (ou
// pediu que a pessoa criasse) o arquivo de configuração, e ainda tem passos
// pela frente. E esse estado é INALCANÇÁVEL numa instalação pronta: para
// chegar nele é preciso ter passado por esta mesma guarda quando o arquivo
// ainda não existia.
// =============================================================================

function recusarSeJaInstalado(): void
{
    $existentes = array_filter(['painel', 'portal'], static fn(string $app): bool => is_file(caminhoDoConfig($app)));

    if ($existentes === []) {
        return;
    }

    if (instalacaoEmCurso(caminhoDoConfig('painel'))) {
        return;
    }

    /* Onda 2, Etapa 1: a recusa NÃO revela nada do servidor — nem caminho de arquivo, nem nome de banco, nem como
       reinstalar. Quem administra o servidor sabe onde estão as configurações; quem não administra não precisa saber.
       É a resposta de TODO endereço de instalar/ (o .htaccess desta pasta manda tudo para o index.php). */
    http_response_code(409);
    telaRecusa(
        'Este sistema já está instalado',
        [
            'O instalador não é executado sobre um sistema já instalado, para que uma instalação em uso não seja apagada por engano.',
        ],
        'Nenhuma alteração foi feita.',
        '',
        urlDaRaiz(),
        'Ir para o sistema'
    );

    exit;
}

/**
 * O instalador está no meio da própria instalação, e este arquivo é o dele?
 *
 * Duas condições, e as duas precisam valer: a sessão do instalador diz que há
 * instalação em curso, E o arquivo em disco declara o mesmo banco que a sessão
 * guarda. A segunda existe para que um arquivo qualquer com o nome certo não
 * abra a passagem.
 *
 * A marca é posta quando o passo do arquivo COMEÇA, e é limpa quando a etapa
 * termina. Cobre os dois caminhos com a mesma regra: o normal (o instalador
 * gravou, e os passos seguintes precisam continuar) e o da hospedagem
 * compartilhada (a pessoa vai criar o arquivo à mão, e o instalador espera).
 */
function instalacaoEmCurso(string $caminho): bool
{
    if (empty($_SESSION['instalacao_em_curso'])) {
        return false;
    }

    $esperado = (string) ($_SESSION['banco']['dbname'] ?? '');

    if ($esperado === '') {
        return false;
    }

    $conteudo = (string) @file_get_contents($caminho);

    return str_contains($conteudo, "define('DBNAME', " . var_export($esperado, true) . ");");
}
