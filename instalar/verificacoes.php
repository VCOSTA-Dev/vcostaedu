<?php

/**
 * AS VERIFICAÇÕES — ambiente e banco.
 *
 * Cada uma devolve DADO, nunca HTML: quem decide o que mostrar é a tela. Isso
 * é o que permite medir uma verificação sem renderizar uma página.
 *
 * Regra que vale para o arquivo inteiro: nenhuma mensagem daqui carrega caminho
 * de servidor, usuário de banco ou trecho de SQL. Mensagem de erro é direção —
 * diz o que aconteceu e o que fazer.
 */

declare(strict_types=1);

/* Só o index.php do instalador carrega este arquivo; pedido direto não responde nada (o .htaccess da pasta já o
   manda para o index.php, que recusa quando o sistema está instalado). */
if (!defined('VCOSTAEDU_INSTALADOR')) {
    http_response_code(404);
    exit;
}

/** A versão mínima. O código deste projeto usa `str_contains` e `str_starts_with`. */
/* VCOSTAEDU: 8.1, o mínimo que o verificar_ambiente.php da origem exige (desenvolvido e testado em 8.2). */
const PHP_MINIMO = '8.1.0';

// =============================================================================
// AMBIENTE — tela 1
// =============================================================================

/**
 * Todos os requisitos de uma vez.
 *
 * Nunca um item por recarga: o instalador que revela um requisito de cada vez
 * faz a pessoa descobrir o segundo depois de ter resolvido o primeiro. É o
 * mesmo desperdício que a sequência evita ao pôr o ambiente ANTES do banco —
 * sem `pdo_mysql` não há teste de conexão que faça sentido.
 *
 * @return array<int, array{rotulo:string, critico:bool, ok:bool, achado:string, resolver:string}>
 */
function verificarAmbiente(): array
{
    $itens = [];

    $itens[] = [
        'rotulo'   => 'PHP ' . PHP_MINIMO . ' ou superior',
        'critico'  => true,
        'ok'       => version_compare(PHP_VERSION, PHP_MINIMO, '>='),
        'achado'   => 'encontrado: ' . PHP_VERSION,
        'resolver' => 'Atualize o PHP do servidor. Em hospedagem compartilhada, isso costuma ser feito no painel de controle ou por solicitação ao suporte.',
    ];

    $itens[] = [
        'rotulo'   => 'Extensão pdo_mysql',
        'critico'  => true,
        'ok'       => extension_loaded('pdo_mysql'),
        'achado'   => extension_loaded('pdo_mysql') ? 'carregada' : 'ausente',
        'resolver' => 'É o componente do PHP que acessa o banco de dados; sem ele o sistema não funciona. Em servidor próprio, habilite pdo_mysql no php.ini e reinicie o serviço; em hospedagem compartilhada, solicite ao suporte.',
    ];

    /* VCOSTAEDU: as extensões que o código de origem usa (a mesma lista do verificar_ambiente.php): textos e PDF
       (mbstring, dom, iconv), imagens do PDF, do logo e do QR (gd). */
    foreach (['mbstring' => 'textos, PDF e QR', 'gd' => 'imagens do PDF, do logo e do QR', 'dom' => 'PDF e HTML', 'iconv' => 'PDF'] as $extensao => $uso) {
        $itens[] = [
            'rotulo'   => 'Extensão ' . $extensao,
            'critico'  => true,
            'ok'       => extension_loaded($extensao),
            'achado'   => extension_loaded($extensao) ? 'carregada' : 'ausente',
            'resolver' => "Usada em: {$uso}. Em servidor próprio, habilite {$extensao} no php.ini e reinicie o serviço; em hospedagem compartilhada, solicite ao suporte.",
        ];
    }


    $gravavel = conseguiGravarDeVerdade();

    $itens[] = [
        'rotulo'   => 'Gravar arquivos nesta pasta',
        /* NÃO é crítico, e é a decisão mais importante desta lista: quando o
           servidor web não consegue gravar, o instalador MOSTRA o conteúdo
           para a pessoa criar à mão e continua do ponto certo. É a saída da
           hospedagem compartilhada, e ela é caminho principal — não exceção.
           Recusar aqui fecharia a porta para boa parte do público-alvo. */
        'critico'  => false,
        'ok'       => $gravavel,
        'achado'   => $gravavel
            ? 'sim: o instalador grava os arquivos de configuração'
            : 'não: o instalador mostrará o conteúdo para que você crie os arquivos manualmente',
        /* Dizia "o passo final". É o passo 4, Configuração — e mandar a pessoa
           esperar até o fim por uma coisa que acontece no meio é o tipo de
           imprecisão que faz alguém desistir antes da hora. */
        'resolver' => 'Não impede a instalação. Na etapa Configuração, o instalador mostra o conteúdo dos arquivos para cópia.',
    ];

    /* mod_rewrite: `apache_get_modules()` só existe sob mod_php. Sob FPM ou CGI
       ele não existe, e AFIRMAR que o módulo falta seria inventar um defeito.
       Onde não dá para medir, a tela diz que não deu para medir. */
    $reescrita = detectarReescrita();

    $itens[] = [
        'rotulo'   => 'Endereços amigáveis (mod_rewrite)',
        'critico'  => false,
        'ok'       => $reescrita !== false,
        'achado'   => $reescrita === true
            ? 'ativa'
            : ($reescrita === false
                ? 'não encontrada'
                : 'não verificado: o servidor não informa a lista de módulos'),
        'resolver' => 'Sem este recurso, os endereços do sistema não abrem após a instalação. A instalação pode continuar; confira este ponto no primeiro acesso.',
    ];

    return $itens;
}

/**
 * O servidor web consegue gravar na pasta de destino? Medido GRAVANDO.
 *
 * ================= POR QUE NÃO É `is_writable()` ============================
 *
 * DEFEITO MEDIDO nesta máquina, e é o motivo desta função existir: com
 * `is_writable()` sobre o diretório, a tela 1 anunciou "o servidor web não
 * consegue gravar" — e o passo 4, na mesma rodada, GRAVOU o arquivo sem
 * problema nenhum.
 *
 * No Windows o `is_writable()` de um diretório olha o atributo somente-leitura,
 * que ali não significa o que significa em sistemas de arquivos POSIX, e ignora
 * a lista de permissões que de fato decide. O resultado é um falso negativo:
 * uma verificação que reprova o ambiente CERTO e empurra todo mundo para o
 * caminho manual sem necessidade. É a regra 5 em forma de checagem — ela aponta
 * um culpado que não fez nada.
 *
 * A saída é medir contra o sistema e não contra um atributo (regra 9): grava um
 * arquivo temporário e o apaga. Custa um `write` e um `unlink`, e responde a
 * pergunta que a tela faz.
 *
 * O arquivo de prova tem prefixo próprio e é removido no mesmo passo. Se ele
 * sobrar por algum motivo, é inofensivo — nasce vazio.
 */
function conseguiGravarDeVerdade(): bool
{
    $diretorio = diretorioDoConfig();

    if (!is_dir($diretorio)) {
        /* A pasta ainda não existe: o que importa é conseguir criá-la. Cria,
           testa e desfaz — sem deixar pasta órfã num ambiente que reprovou. */
        if (!@mkdir($diretorio, 0755, true) && !is_dir($diretorio)) {
            return false;
        }

        $conseguiu = escreverArquivoDeProva($diretorio);
        @rmdir($diretorio);

        return $conseguiu;
    }

    return escreverArquivoDeProva($diretorio);
}

function escreverArquivoDeProva(string $diretorio): bool
{
    $prova = $diretorio . '/.vcostaedu-prova-de-escrita';
    $ok    = @file_put_contents($prova, '') !== false;

    if (is_file($prova)) {
        @unlink($prova);
    }

    return $ok;
}

/** @return true|false|null  null = não foi possível medir neste servidor. */
function detectarReescrita(): ?bool
{
    if (function_exists('apache_get_modules')) {
        return in_array('mod_rewrite', apache_get_modules(), true);
    }

    return null;
}

/** Há algum requisito CRÍTICO reprovado? */
function ambienteReprovado(array $itens): bool
{
    foreach ($itens as $item) {
        if ($item['critico'] && !$item['ok']) {
            return true;
        }
    }

    return false;
}

// =============================================================================
// BANCO — telas 2 e 3
// =============================================================================

/**
 * Conecta para TESTAR.
 *
 * PDO próprio, e não o `AdmsConn` da aplicação — decisão D11. A conexão daquela
 * camada é `static` por requisição: guardaria a primeira credencial e ignoraria
 * a segunda, em silêncio, que é exatamente o que não pode acontecer numa tela
 * onde a pessoa corrige o formulário e tenta de novo. É a mesma saída que o
 * `AdmsMigrations` já usa pelo mesmo motivo.
 *
 * @return array{ok:bool, pdo:?PDO, erro:string}
 */
function conectarParaTeste(array $dados): array
{
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $dados['host'],
        (int) $dados['porta'],
        $dados['dbname']
    );

    try {
        require_once dirname(__DIR__) . '/core/Banco.php'; // Onda 3, Etapa 2: o modo de SQL do sistema
        $pdo = VcBanco::abrir($dsn, $dados['usuario'], $dados['senha'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 5,
        ]);
    } catch (PDOException $e) {
        /* O detalhe vai para o log; a tela recebe a causa traduzida. A mensagem
           crua do PDO cita host, usuário e banco. */
        error_log('[vcostaedu][instalador] falha ao conectar: ' . $e->getMessage());

        return ['ok' => false, 'pdo' => null, 'erro' => traduzirErroDeConexao($e)];
    }

    return ['ok' => true, 'pdo' => $pdo, 'erro' => ''];
}

/**
 * A causa em uma frase, sem vazar nada.
 *
 * Os códigos vêm do driver e são estáveis; o texto é nosso.
 */
function traduzirErroDeConexao(PDOException $e): string
{
    $texto = $e->getMessage();

    if (str_contains($texto, '1045')) {
        return 'O banco recusou o usuário ou a senha informados.';
    }

    if (str_contains($texto, '1049')) {
        return 'O banco informado não existe. Crie-o vazio e informe o nome aqui; o instalador não cria bancos de dados.';
    }

    if (str_contains($texto, '2002') || str_contains($texto, '2005')) {
        return 'Não foi possível acessar o servidor de banco de dados no endereço informado. Confira se o serviço está em funcionamento.';
    }

    return 'Não foi possível conectar ao banco de dados com os dados informados.';
}

/**
 * O retrato do banco: conjunto de caracteres, collation e se está vazio.
 *
 * @return array{charset:string, collation:string, tabelas:int, exemplo:string}
 */
function retratoDoBanco(PDO $pdo, string $dbname): array
{
    $consulta = $pdo->prepare(
        'SELECT DEFAULT_CHARACTER_SET_NAME AS charset, DEFAULT_COLLATION_NAME AS collation
           FROM information_schema.SCHEMATA
          WHERE SCHEMA_NAME = :nome'
    );
    $consulta->execute([':nome' => $dbname]);
    $linha = $consulta->fetch() ?: ['charset' => '', 'collation' => ''];

    $tabelas = $pdo->prepare(
        'SELECT TABLE_NAME FROM information_schema.TABLES
          WHERE TABLE_SCHEMA = :nome ORDER BY TABLE_NAME'
    );
    $tabelas->execute([':nome' => $dbname]);
    $nomes = $tabelas->fetchAll(PDO::FETCH_COLUMN) ?: [];

    require_once dirname(__DIR__) . '/core/Banco.php';
    return [
        'charset'   => (string) $linha['charset'],
        'collation' => (string) $linha['collation'],
        'modo'      => VcBanco::modoEmUso($pdo), // Onda 3, Etapa 2: o modo de SQL que o sistema fixa na conexão
        'tabelas'   => count($nomes),
        'exemplo'   => $nomes[0] ?? '',
    ];
}

const CHARSET_EXIGIDO   = 'utf8mb4';
const COLLATION_DESEJADA = 'utf8mb4_unicode_ci';

/**
 * O comando que a pessoa precisa rodar quando o charset está errado.
 *
 * Vem pronto, com o nome do banco que ela digitou já no lugar — é a diferença
 * entre uma recusa e uma recusa útil.
 */
function comandoCreateDatabase(string $dbname): string
{
    /* Crase é o que o MySQL usa para delimitar identificador. Uma crase dentro
       do nome se escapa dobrando — sem isso, um nome estranho produziria um
       comando quebrado que a pessoa copiaria e colaria. */
    $seguro = str_replace('`', '``', $dbname);

    return "CREATE DATABASE `{$seguro}`\n"
        . '  CHARACTER SET ' . CHARSET_EXIGIDO . "\n"
        . '  COLLATE ' . COLLATION_DESEJADA . ';';
}
