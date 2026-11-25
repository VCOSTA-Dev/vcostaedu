<?php

/**
 * A PONTE ENTRE O INSTALADOR E O RUNNER DE MIGRATIONS.
 *
 * ============== POR QUE ELE CONECTA PELO Config.php, E NÃO PELA SESSÃO ======
 *
 * O instalador já tem host, usuário e senha na sessão desde a tela 2, e usá-los
 * aqui seria mais curto. Não é o que se faz, por duas razões:
 *
 *   1. **É a primeira vez que o artefato é exercitado.** O `Config.php` é o
 *      produto do passo anterior. Conectar por ele prova que o arquivo gerado
 *      funciona — e prova ANTES de a pessoa ir embora achando que instalou.
 *
 *   2. **Fecha o buraco da hospedagem compartilhada.** No caminho manual quem
 *      digita o arquivo é a pessoa, e ela pode errar a senha ao copiar. A
 *      conferência da tela 4 checa que as constantes existem e que o banco é o
 *      mesmo, mas não sabe se a senha está certa — só uma conexão sabe. Sem
 *      isto, o erro apareceria no primeiro login, longe da causa.
 *
 * ==================== POR QUE DEFINE A GUARDA DA APLICAÇÃO ==================
 *
 * O `Config.php` e o runner conferem a guarda do painel (`VCOSTAEDU`) no topo e morrem sem ela.
 * Definir aqui NÃO "vira aplicação" — é o mesmo que o `bin/bootstrap-cli.php`
 * faz, e pelo mesmo motivo: satisfazer o `if` do topo dos arquivos que vamos
 * carregar. O instalador continua sem autoloader, sem sessão de aplicação e sem
 * roteador.
 */

declare(strict_types=1);

/* Só o index.php do instalador carrega este arquivo; pedido direto não responde nada (o .htaccess da pasta já o
   manda para o index.php, que recusa quando o sistema está instalado). */
if (!defined('VCOSTAEDU_INSTALADOR')) {
    http_response_code(404);
    exit;
}

/**
 * Carrega o `Config.php` gerado e define as constantes de conexão.
 *
 * Separado de `prepararRunner()` porque os passos seguintes — a conta de
 * administrador e a conferência final — precisam da conexão e não do runner.
 *
 * @return string  '' quando deu certo; a mensagem de erro quando não
 */
function carregarConfiguracao(): string
{
    static $feito = null;

    if ($feito !== null) {
        return $feito;
    }

    /* VCOSTAEDU: a configuração do PAINEL (a do portal tem os mesmos dados de banco e declara a mesma classe
       Core\Config, então as duas não cabem no mesmo processo). */
    $caminhoConfig = caminhoDoConfig('painel');

    if (!is_file($caminhoConfig)) {
        return $feito = 'O arquivo de configuração não foi encontrado.';
    }

    if (!defined('VCOSTAEDU')) {
        define('VCOSTAEDU', true);
        foreach (['Un1Mv' . 'C05tA'] as $g) { defined($g) || define($g, true); } // a guarda de antes da 1.45.0, só para a Config.php já instalada (DECISIONS D-53)
    }

    /* O `Config.php` monta as constantes de URL a partir de SERVER_NAME, que
       aqui existe — estamos atendendo uma requisição. */
    require_once $caminhoConfig;

    if (!class_exists('\Core\Config')) {
        return $feito = 'O arquivo de configuração não declara a classe esperada.';
    }

    try {
        /* `configAdm()` é `protected`; a classe anônima é o contorno que a casa
           já usa no bootstrap de linha de comando, e não exige tocar no arquivo
           gerado — que o instalador reescreve e ninguém deve editar. */
        $configuracao = new class extends \Core\Config {
            public function aplicar(): void
            {
                $this->configAdm();
            }
        };
        $configuracao->aplicar();
    } catch (Throwable $e) {
        error_log('[vcostaedu][instalador] falha ao carregar a configuração: ' . $e->getMessage());

        return $feito = 'O arquivo de configuração não pôde ser carregado.';
    }

    return $feito = '';
}

/**
 * A conexão da aplicação, pelas constantes do `Config.php` gerado.
 *
 * PDO próprio, e não a camada de conexão da aplicação: a D11 registra que
 * aquela é `static` por requisição, e o instalador precisa poder reconectar.
 *
 * @return array{ok:bool, pdo:?PDO, erro:string}
 */
function conexaoDaAplicacao(): array
{
    $erro = carregarConfiguracao();

    if ($erro !== '') {
        return ['ok' => false, 'pdo' => null, 'erro' => $erro];
    }

    $porta = defined('PORTA') ? (int) constant('PORTA') : 3306;

    try {
        require_once dirname(__DIR__) . '/core/Banco.php'; // Onda 3, Etapa 2: o modo de SQL do sistema
        $pdo = VcBanco::abrir(
            'mysql:host=' . HOST . ';port=' . $porta . ';dbname=' . DBNAME . ';charset=utf8mb4',
            USER,
            PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) {
        error_log('[vcostaedu][instalador] conexão pela configuração: ' . $e->getMessage());

        return [
            'ok' => false,
            'pdo' => null,
            'erro' => 'Não foi possível conectar ao banco com os dados do arquivo de configuração. '
                . 'Se você criou o arquivo à mão, confira principalmente a senha.',
        ];
    }

    return ['ok' => true, 'pdo' => $pdo, 'erro' => ''];
}

/**
 * Carrega o `Config.php` gerado e devolve o runner pronto.
 *
 * @return array{ok:bool, runner:?object, erro:string}
 */
function prepararRunner(): array
{
    $erro = carregarConfiguracao();

    if ($erro !== '') {
        return ['ok' => false, 'runner' => null, 'erro' => $erro];
    }

    require_once raizDoProjeto() . '/painel/app/adms/Models/banco/AdmsMigrations.php';

    try {
        $runner = new \App\adms\Models\banco\AdmsMigrations(raizDoProjeto() . '/database/migrations');
    } catch (Throwable $e) {
        error_log('[vcostaedu][instalador] runner: ' . $e->getMessage());

        return [
            'ok' => false,
            'runner' => null,
            /* A causa mais comum tem nome: a conexão do `Config.php` não abriu.
               A mensagem crua do PDO cita host, usuário e banco — nunca vai
               para a tela. */
            'erro' => 'Não foi possível conectar ao banco com os dados do arquivo de configuração. '
                . 'Se você criou o arquivo à mão, confira principalmente a senha.',
        ];
    }

    return ['ok' => true, 'runner' => $runner, 'erro' => ''];
}
