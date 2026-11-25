<?php

/**
 * A CONTA DO PRIMEIRO ADMINISTRADOR — e a conferência final do acesso.
 *
 * Veio do instalador do vcostaedu (D13 de lá: a senha nasce na tela, não em linha de comando). A diferença,
 * VCOSTAEDU: aqui nenhuma migration cria conta trancada — a base de origem nasce sem usuário nenhum. O instalador
 * CRIA a conta, no nível mais alto (1, o Administrador), pela MESMA função que o
 * `database/ferramentas/criar_admin.php` usa (`database/ferramentas/admin_comum.php`): as mesmas recusas, o mesmo
 * INSERT. A única diferença entre as duas portas é de onde vem a senha: lá ela é gerada e mostrada uma vez; aqui
 * a pessoa escolhe.
 *
 * Nenhuma tela daqui mostra id de usuário, de perfil ou de página.
 */

declare(strict_types=1);

/* Só o index.php do instalador carrega este arquivo; pedido direto não responde nada (o .htaccess da pasta já o
   manda para o index.php, que recusa quando o sistema está instalado). */
if (!defined('VCOSTAEDU_INSTALADOR')) {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/database/ferramentas/admin_comum.php';

/**
 * O TAMANHO MÍNIMO DA SENHA: doze, e não regra de composição (o motivo está na D13 do vcostaedu — comprimento é
 * o que custa caro quebrar, e composição empurra para `Senha@2026`).
 */
const SENHA_TAMANHO_MINIMO = VcSenha::MINIMO;

/**
 * Valida o que a pessoa digitou.
 *
 * @return string[] lista de erros; vazia quando está tudo certo
 */
function validarAdministrador(array $dados): array
{
    $erros = [];

    if (trim($dados['usuario']) === '' || !preg_match('/^[A-Za-z0-9._-]{3,60}$/', trim($dados['usuario']))) {
        $erros[] = 'O nome de usuário deve ter de 3 a 60 caracteres: letras sem acento, números, ponto, hífen ou sublinhado.';
    }

    if (trim($dados['nome']) === '') {
        $erros[] = 'Informe o nome do administrador.';
    }

    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido. Ele é usado para redefinir a senha.';
    }

    $senha = $dados['senha'];

    /* Onda 2, Etapa 1: a regra da senha é a única do sistema (core/Senha.php): o mínimo, a lista local de senhas
       comuns e os dados pessoais digitados aqui. */
    foreach (VcSenha::problemas($senha, ['usuario' => $dados['usuario'], 'nome' => $dados['nome'], 'email' => $dados['email']]) as $problema) {
        $erros[] = $problema;
    }

    if ($senha !== $dados['confirmacao']) {
        $erros[] = 'A senha e a confirmação não conferem.';
    }

    return $erros;
}

/**
 * Cria a conta pela função comum.
 *
 * @return array{ok:bool, erro:string}
 */
function criarAdministrador(PDO $pdo, array $dados): array
{
    try {
        $r = vcostaedu_criar_admin($pdo, $dados['usuario'], $dados['email'], $dados['nome'], $dados['senha']);
    } catch (Throwable $e) {
        error_log('[vcostaedu][instalador] falha ao criar o administrador: ' . $e->getMessage());

        return ['ok' => false, 'erro' => 'O banco de dados recusou a gravação da conta.'];
    }

    return ['ok' => $r['ok'], 'erro' => $r['erro']];
}

/** Já existe algum Administrador (nível 1) ativo? Usado para não pedir a conta duas vezes. */
function existeAdministrador(PDO $pdo): bool
{
    return (int) $pdo->query('SELECT COUNT(*) FROM adms_usuarios WHERE adms_niveis_acesso_id = 1 AND adms_sits_usuario_id = 1')->fetchColumn() > 0;
}

// =============================================================================
// A CONFERÊNCIA FINAL DO ACESSO
//
// A tela final NÃO declara sucesso por ter chegado até aqui: confere, contra o banco e com a MESMA consulta que
// o roteador do painel faz, que os dados de acesso resolvem.
// =============================================================================

/**
 * @return array{ok:bool, itens:array<int, array{rotulo:string, ok:bool, detalhe:string}>}
 */
function conferirAcesso(PDO $pdo): array
{
    $itens = [];

    $itens[] = [
        'rotulo'  => 'A tela de entrada do painel abre',
        'ok'      => rotaResolve($pdo, 'Login', 'acesso', null),
        'detalhe' => 'É a entrada do sistema; sem ela nenhuma tela pode ser acessada.',
    ];

    $itens[] = [
        'rotulo'  => 'A Página Inicial abre para o administrador',
        'ok'      => rotaResolve($pdo, 'Home', 'index', 1),
        'detalhe' => 'Sem ela, o administrador voltaria à tela de entrada após o login.',
    ];

    $itens[] = [
        'rotulo'  => 'A tela de entrada do portal do aluno abre',
        'ok'      => rotaPortalResolve($pdo, 'Login', 'acesso'),
        'detalhe' => 'É por ela que o aluno acessa o portal, inclusive dentro do Moodle.',
    ];

    /* Onda 2, Etapa 1: volta a ser EXIGÊNCIA (a D18 do vcostaedu). As 4 páginas mortas herdadas da base saíram na
       migration 0004; página sem perfil é defeito de migration e reprova a instalação. */
    $orfas = paginasOrfas($pdo);
    $itens[] = [
        'rotulo'  => 'Todas as telas do painel têm um perfil com acesso',
        'ok'      => empty($orfas),
        'detalhe' => empty($orfas)
            ? 'Nenhuma tela está sem perfil com acesso.'
            : count($orfas) . ' tela(s) sem nenhum perfil com acesso: ' . implode(', ', array_slice($orfas, 0, 6)) . '.',
    ];

    /* o teto das ações (core/Teto.php): nenhuma ação dada a um perfil abaixo do teto dela, e toda tela com a ação */
    require_once dirname(__DIR__) . '/core/Teto.php';
    $acima = VcTeto::acimaDoTeto($pdo);
    $itens[] = [
        'rotulo'  => 'Nenhum perfil recebe uma ação acima do que pode',
        'ok'      => empty($acima),
        'detalhe' => empty($acima)
            ? 'Cada ação está só nos perfis que podem recebê-la.'
            : count($acima) . ' permissão(ões) acima do perfil: ' . implode('; ', array_slice($acima, 0, 4)) . '.',
    ];
    $semAcao = VcTeto::semAcao($pdo);
    $itens[] = [
        'rotulo'  => 'Todas as telas do painel têm a ação delas',
        'ok'      => empty($semAcao),
        'detalhe' => empty($semAcao)
            ? 'Cada tela diz a ação que permite.'
            : count($semAcao) . ' tela(s) sem ação: ' . implode(', ', array_slice($semAcao, 0, 6)) . '.',
    ];

    $itens[] = [
        'rotulo'  => 'A conta de administrador foi gravada',
        'ok'      => existeAdministrador($pdo),
        'detalhe' => 'Há uma conta de administrador ativa, com a senha cadastrada.',
    ];

    $ok = true;

    foreach ($itens as $item) {
        $ok = $ok && ($item['ok'] || !empty($item['aviso']));
    }

    return ['ok' => $ok, 'itens' => $itens];
}

/** A consulta do roteador do painel (painel/app/adms/Models/AdmsPaginas.php), sem o roteador. */
function rotaResolve(PDO $pdo, string $controller, string $metodo, ?int $nivel): bool
{
    $consulta = $pdo->prepare(
        'SELECT pg.id
           FROM adms_paginas pg
           INNER JOIN adms_tps_pgs tpg ON tpg.id = pg.adms_tps_pg_id
           LEFT JOIN adms_nivacs_pgs nivpg ON nivpg.adms_pagina_id = pg.id
                 AND nivpg.adms_niveis_acesso_id = :nivel
          WHERE pg.controller = :controller AND pg.metodo = :metodo
            AND ((pg.lib_pub = 1) OR (nivpg.permissao = 1))
          LIMIT 1'
    );
    $consulta->execute([':nivel' => $nivel, ':controller' => $controller, ':metodo' => $metodo]);

    return $consulta->fetch() !== false;
}

/** A página pública do portal (col_paginas, lib_pub = 1). */
function rotaPortalResolve(PDO $pdo, string $controller, string $metodo): bool
{
    $consulta = $pdo->prepare('SELECT id FROM col_paginas WHERE controller = :c AND metodo = :m AND lib_pub = 1 LIMIT 1');
    $consulta->execute([':c' => $controller, ':m' => $metodo]);

    return $consulta->fetch() !== false;
}

/** @return array<int, string> */
function paginasOrfas(PDO $pdo): array
{
    $linhas = $pdo->query(
        "SELECT CONCAT(p.controller, '/', p.metodo) AS rota
           FROM adms_paginas p
          WHERE p.lib_pub <> 1
            AND NOT EXISTS (
                SELECT 1 FROM adms_nivacs_pgs n
                 WHERE n.adms_pagina_id = p.id AND n.permissao = 1
            )"
    )->fetchAll(PDO::FETCH_COLUMN);

    return $linhas ?: [];
}

// =============================================================================
// A CONFERÊNCIA DO AMBIENTE — reusa o database/ferramentas/implantacao/verificar_ambiente.php
//
// A ferramenta é de linha de comando (é a mesma que o roteiro de implantação manda rodar no servidor). O
// instalador a executa pelo PHP de linha de comando do próprio servidor e mostra o resultado, linha a linha. Onde
// não dá para executar (shell desligado, PHP de linha de comando ausente), a tela diz isso e mostra o comando.
// =============================================================================

/** O PHP de linha de comando deste servidor, ou '' quando não se acha. */
function phpDeLinhaDeComando(): string
{
    $candidatos = [];

    if (PHP_BINARY !== '' && preg_match('/php(-cli)?(\.exe)?$/i', PHP_BINARY)) {
        $candidatos[] = PHP_BINARY;
    }

    $exe = DIRECTORY_SEPARATOR === '\\' ? 'php.exe' : 'php';

    /* No Windows o PHP_BINDIR é o caminho da compilação, não o da máquina: procura também ao lado do php.ini
       carregado (onde o XAMPP, por exemplo, guarda o php.exe). */
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

/**
 * @return array{executou:bool, linhas:array<int, array{tipo:string, texto:string}>, resumo:string, comando:string}
 */
function verificarAmbienteDoServidor(): array
{
    $ferramenta = raizDoProjeto() . '/database/ferramentas/implantacao/verificar_ambiente.php';
    $comando = 'php database/ferramentas/implantacao/verificar_ambiente.php';
    $php = phpDeLinhaDeComando();

    if ($php === '' || !function_exists('shell_exec') || in_array('shell_exec', array_map('trim', explode(',', (string) ini_get('disable_functions'))), true)) {
        return ['executou' => false, 'linhas' => [], 'resumo' => '', 'comando' => $comando];
    }

    /* --tela: o texto em português de uso, sem os nomes das constantes nem as opções da linha de comando */
    $saida = (string) @shell_exec(escapeshellarg($php) . ' ' . escapeshellarg($ferramenta) . ' --tela 2>&1');

    if ($saida === '') {
        return ['executou' => false, 'linhas' => [], 'resumo' => '', 'comando' => $comando];
    }

    $linhas = [];
    $resumo = '';

    foreach (preg_split('/\R/', $saida) as $l) {
        if (preg_match('/^\s*\[(OK|ALERTA|FALHA)\]\s+(.*)$/u', $l, $m)) {
            $linhas[] = ['tipo' => $m[1], 'texto' => $m[2]];
        } elseif (preg_match('/^== (.*)$/u', $l, $m)) {
            $linhas[] = ['tipo' => 'SECAO', 'texto' => $m[1]];
        } elseif (str_starts_with($l, 'Resumo:')) {
            $resumo = $l;
        }
    }

    return ['executou' => true, 'linhas' => $linhas, 'resumo' => $resumo, 'comando' => $comando];
}
