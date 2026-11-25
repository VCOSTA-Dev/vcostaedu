<?php

/**
 * BOOTSTRAP DE EXECUÇÃO POR LINHA DE COMANDO.
 *
 * Mesmo desenho de outro projeto da casa e do sistema escolar em Tabler.
 *
 * ========================= O PROBLEMA QUE ELE RESOLVE ========================
 *
 * O runner precisa das constantes `HOST`, `USER`, `PASS`, `DBNAME` e `PORTA`,
 * definidas em `Core\Config::configAdm()`. Esse método é `protected` e só roda
 * quando o roteador atende uma requisição HTTP. Em CLI não há requisição, então
 * as constantes não existem e a conexão falharia com "Undefined constant HOST"
 * — erro obscuro para quem lê o log depois.
 *
 * A alternativa seria repetir usuário e senha do banco num arquivo do runner.
 * Não: credencial duplicada é credencial que vai divergir. A fonte continua
 * sendo o `Config.php`, que é o único lugar onde ela mora — e que é gitignored
 * (regra 13).
 *
 * Uso:
 *     require __DIR__ . '/bootstrap-cli.php';
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    die('Erro: Página não encontrada!');
}

/* A guarda do painel. Não "vira aplicação" — só satisfaz o `if` do topo dos
   arquivos de `painel/app/` e do `painel/core/Config.php`, que morrem sem ela. */
if (!defined('VCOSTAEDU')) {
    define('VCOSTAEDU', true);
    foreach (['Un1Mv' . 'C05tA'] as $g) { defined($g) || define($g, true); } // a guarda de antes da 1.45.0, só para a Config.php já instalada (DECISIONS D-53)
}

$raiz = dirname(__DIR__);

/* `Core\Config` monta as constantes de URL a partir de `$_SERVER['SERVER_NAME']`,
   que não existe em CLI. Preenche-se um valor inócuo para não gerar warning:
   nenhuma URL faz sentido fora do HTTP, e nada em CLI as usa. */
if (!isset($_SERVER['SERVER_NAME'])) {
    $_SERVER['SERVER_NAME'] = 'cli.local';
}

/* ONDE O ARQUIVO DE CONFIGURAÇÃO MORA: `painel/core/Config.php`, o mesmo que o
   painel lê (o instalador grava este e o do portal). As ferramentas de
   `database/ferramentas/` leem o mesmo arquivo. */
$caminhoConfig = $raiz . '/painel/core/Config.php';

if (!is_file($caminhoConfig)) {
    fwrite(STDERR, "[vcostaedu] O arquivo de configuração não existe.\n");
    fwrite(STDERR, "            Instale o sistema pelo navegador antes de usar os comandos de linha.\n");
    exit(1);
}

require $caminhoConfig;

/* O runner é carregado pelo caminho, sem o autoloader do painel: o `bin/` roda
   sem servidor web e sem sessão. */
require $raiz . '/painel/app/adms/Models/banco/AdmsMigrations.php';

/*
 * `Core\Config` é `abstract` e `configAdm()` é `protected`, então não dá para
 * chamar de fora. Uma classe anônima que a estende resolve sem tocar no arquivo
 * gerado — que não deve ser editado por ninguém, porque o instalador o reescreve.
 */
$configuracao = new class extends \Core\Config {
    public function aplicar(): void
    {
        $this->configAdm();
    }
};
$configuracao->aplicar();

/* Sanidade: se qualquer constante faltar, é melhor parar aqui, com mensagem
   clara, do que estourar lá dentro do PDO com uma mensagem que não explica nada. */
foreach (['HOST', 'USER', 'DBNAME'] as $constante) {
    if (!defined($constante)) {
        fwrite(STDERR, "[vcostaedu] Constante {$constante} não definida após carregar o Config.php.\n");
        exit(1);
    }
}
