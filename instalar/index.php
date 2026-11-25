<?php

/**
 * O INSTALADOR DO VCOSTAEDU — as sete telas.
 *
 * Veio do instalador do vcostaedu (D10 a D15 e D21 de lá; a sequência e o porquê de cada desvio em relação ao
 * Moodle estão no LEVANTAMENTO_INSTALADOR.md de lá):
 *
 *   1. Requisitos     PHP, extensões, escrita, reescrita de URL
 *   2. Banco          servidor, banco, usuário, senha — e o TESTE aqui mesmo
 *   3. Conferência    charset (recusa), collation (aviso), banco vazio (recusa); a pasta dos arquivos privados
 *   4. Configuração   grava as DUAS configurações e as pastas; se não conseguir, mostra o conteúdo; confere a
 *                     exposição por HTTP
 *   5. Tabelas        as migrations, à vista (os gatilhos sem o privilégio TRIGGER ficam adiados, com aviso)
 *   6. Sua conta      o primeiro Administrador, pela mesma lógica do criar_admin.php
 *   7. Pronto         confere o acesso contra o banco e o ambiente pelo verificar_ambiente.php
 *
 * O que mudou para o VCOSTAEDU está marcado "VCOSTAEDU:" ao longo do arquivo.
 */

declare(strict_types=1);

const VCOSTAEDU_INSTALADOR = true;

require dirname(__DIR__) . '/core/asset.php';
require dirname(__DIR__) . '/core/Senha.php';
require dirname(__DIR__) . '/core/Legal.php';
require __DIR__ . '/comum.php';
require __DIR__ . '/layout.php';
require __DIR__ . '/verificacoes.php';
require_once dirname(__DIR__) . '/core/Banco.php'; // Onda 3, Etapa 2: o modo de SQL (a conferência o mostra)
require_once dirname(__DIR__) . '/core/Vocabulario.php'; // Onda 4, Etapa 1: os tipos de instituição
require_once dirname(__DIR__) . '/core/HorarioPadrao.php'; // Onda 8 (D-86): o horário padrão, só na escola
require_once dirname(__DIR__) . '/core/AtestadoEscola.php'; // D-102: o atestado de matrícula da escola, só na escola
require_once dirname(__DIR__) . '/core/TipoLivre.php'; // D-106: o curso livre
require __DIR__ . '/gerador.php';
require __DIR__ . '/pastas.php';
require __DIR__ . '/migracoes.php';
require __DIR__ . '/administrador.php';

/* A guarda da D10, antes de qualquer tela. */
recusarSeJaInstalado();

$passo = (string) ($_GET['passo'] ?? 'ambiente');
$envio = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

if (!array_key_exists($passo, PASSOS)) {
    $passo = 'ambiente';
}

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(16));
}

if ($envio && !hash_equals((string) $_SESSION['token'], (string) ($_POST['token'] ?? ''))) {
    http_response_code(400);
    telaRecusa(
        'A sessão do instalador expirou.',
        ['Reinicie a partir da primeira etapa. Isso acontece quando a página fica aberta por muito tempo.'],
        'Nenhuma alteração foi feita.'
    );
    exit;
}

if (in_array($passo, ['conferencia', 'arquivo', 'tabelas', 'administrador', 'pronto'], true) && empty($_SESSION['banco'])) {
    header('Location: ?passo=banco');
    exit;
}

match ($passo) {
    'ambiente'      => telaAmbiente(),
    'banco'         => $envio ? processarBanco() : telaBanco(),
    'conferencia'   => telaConferencia(),
    'arquivo'       => telaArquivo($envio),
    'tabelas'       => telaTabelas($envio),
    'administrador' => telaAdministrador($envio),
    'pronto'        => header('Location: ?passo=administrador'),
};

// =============================================================================
// TELA 1 — AMBIENTE
// =============================================================================

function telaAmbiente(): void
{
    $itens     = verificarAmbiente();
    $reprovado = ambienteReprovado($itens);

    abrirPagina('Requisitos', 'ambiente');
    ?>
    <div class="card card-md">
      <div class="card-body">
        <h2 class="card-title">Requisitos do servidor</h2>
        <p class="text-secondary">
          Nada foi instalado ainda. Esta etapa verifica o servidor: os itens <strong>obrigatórios</strong> precisam estar atendidos; os opcionais não impedem a instalação.
        </p>

        <div class="list-group list-group-flush mb-3">
          <?php foreach ($itens as $item): ?>
          <div class="list-group-item px-0">
            <div class="row align-items-center">
              <div class="col-auto"><?= $item['ok'] ? iconeOk() : ($item['critico'] ? iconeErro() : iconeAviso()) ?></div>
              <div class="col">
                <div class="fw-medium"><?= e($item['rotulo']) ?>
                  <?php if (!$item['critico']): ?>
                  <span class="badge bg-secondary-lt ms-1">opcional</span>
                  <?php endif; ?>
                </div>
                <div class="text-secondary small"><?= e($item['achado']) ?></div>
                <?php if (!$item['ok']): ?>
                <div class="text-secondary small mt-1"><?= e($item['resolver']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if ($reprovado): ?>
        <div class="alert alert-danger" role="alert">
          <h4 class="alert-title">Este servidor não atende os requisitos.</h4>
          <div class="text-secondary">
            Corrija os itens marcados e verifique novamente.
            <strong>Nenhuma alteração foi feita.</strong>
          </div>
        </div>
        <a href="?passo=ambiente" class="btn btn-outline-secondary w-100">Verificar novamente</a>
        <?php else: ?>
        <p class="text-secondary">
          <strong>Próxima etapa:</strong> a conexão com o banco de dados. Tenha em mãos o endereço do servidor, o nome do banco, o usuário e a senha.
        </p>
        <a href="?passo=banco" class="btn btn-primary w-100">Continuar para o banco de dados</a>
        <?php endif; ?>
      </div>
    </div>
    <?php
    fecharPagina();
}

// =============================================================================
// TELA 2 — DADOS DO BANCO
//
// VCOSTAEDU: sem o campo Porta. O sistema de origem conecta sempre pela porta padrão (3306): a constante PORTA
// não tem leitor nas duas aplicações, e oferecer o campo seria prometer o que a conexão não cumpre.
// =============================================================================

function telaBanco(array $dados = [], string $erro = ''): void
{
    $dados += ['host' => 'localhost', 'dbname' => '', 'usuario' => '', 'senha' => ''];

    abrirPagina('Banco de dados', 'banco');
    ?>
    <div class="card card-md">
      <div class="card-body">
        <h2 class="card-title">Banco de dados</h2>
        <p class="text-secondary">
          O servidor atende aos requisitos. Informe agora o banco de dados em que o sistema vai guardar as informações.
        </p>
        <p class="text-secondary">
          O banco precisa <strong>existir e estar vazio</strong>. O instalador não cria bancos de dados, porque isso exigiria uma conta com privilégios sobre todo o servidor.
        </p>

        <details class="mb-3">
          <summary class="text-secondary" style="cursor: pointer">
            Como criar o banco de dados
          </summary>
          <div class="mt-2">
            <p class="text-secondary mb-2">
              Crie um banco vazio com o comando abaixo, no phpMyAdmin, no painel da hospedagem ou pela linha de comando. O nome pode ser alterado:
            </p>
            <pre class="bg-dark text-white p-3 rounded mb-2 vc-comando" data-vc-copiar id="comandoCriarBanco"><code>CREATE DATABASE vcostaedu
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;</code></pre>
            <p class="text-secondary mb-0">
              Em seguida, informe o nome escolhido no formulário abaixo.
            </p>
          </div>
        </details>

        <?php if ($erro !== ''): ?>
        <div class="alert alert-danger" role="alert">
          <h4 class="alert-title">Não foi possível conectar ao banco de dados</h4>
          <div class="text-secondary"><?= e($erro) ?></div>
          <div class="text-secondary mt-2">
            Confira os dados abaixo e tente novamente.
            <strong>Nenhuma alteração foi feita.</strong>
          </div>
        </div>
        <?php endif; ?>

        <form method="post" action="?passo=banco" autocomplete="off">
          <input type="hidden" name="token" value="<?= e($_SESSION['token']) ?>">

          <div class="mb-3">
            <label class="form-label required" for="host">Servidor</label>
            <input type="text" id="host" name="host" class="form-control" required
                   value="<?= e($dados['host']) ?>">
            <div class="form-hint">Na maioria das hospedagens é <code>localhost</code>. A conexão usa a porta padrão (3306).</div>
          </div>

          <div class="mb-3">
            <label class="form-label required" for="dbname">Nome do banco</label>
            <input type="text" id="dbname" name="dbname" class="form-control" required
                   value="<?= e($dados['dbname']) ?>">
            <div class="form-hint">O nome do banco vazio criado para o sistema.</div>
          </div>

          <div class="mb-3">
            <label class="form-label required" for="usuario">Usuário</label>
            <input type="text" id="usuario" name="usuario" class="form-control" required
                   value="<?= e($dados['usuario']) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label" for="senha">Senha</label>
            <input type="password" id="senha" name="senha" class="form-control" data-vc-senha="atual" autocomplete="off">
          </div>

          <div class="form-footer">
            <button type="submit" class="btn btn-primary w-100">Testar a conexão</button>
            <div class="form-hint text-center mt-2">
              Nesta etapa nada é gravado: o instalador apenas testa a conexão.
            </div>
          </div>
        </form>

        <div class="text-center text-secondary mt-3 small">
          <a href="?passo=ambiente">Voltar aos requisitos</a>
        </div>
      </div>
    </div>
    <?php
    fecharPagina();
}

function processarBanco(): void
{
    $dados = [
        'host'    => trim((string) ($_POST['host'] ?? '')),
        'porta'   => '3306',
        'dbname'  => trim((string) ($_POST['dbname'] ?? '')),
        'usuario' => trim((string) ($_POST['usuario'] ?? '')),
        'senha'   => (string) ($_POST['senha'] ?? ''),
    ];

    if ($dados['host'] === '' || $dados['dbname'] === '' || $dados['usuario'] === '') {
        telaBanco($dados, 'Preencha o servidor, o nome do banco e o usuário.');

        return;
    }

    $conexao = conectarParaTeste($dados);

    if (!$conexao['ok']) {
        telaBanco($dados, $conexao['erro']);

        return;
    }

    $_SESSION['banco']   = $dados;
    $_SESSION['retrato'] = retratoDoBanco($conexao['pdo'], $dados['dbname']);

    header('Location: ?passo=conferencia');
}

// =============================================================================
// TELA 3 — CONFERÊNCIA DO BANCO, e a pasta dos arquivos privados
//
// charset diferente de utf8mb4 -> RECUSA; collation diferente -> AVISO; banco com tabelas -> RECUSA.
// VCOSTAEDU: a pasta dos arquivos privados (PASTA_STORAGE) é escolhida aqui, antes de gravar.
// =============================================================================

function telaConferencia(string $erroStorage = '', string $storage = ''): void
{
    $dados   = $_SESSION['banco'];
    $retrato = $_SESSION['retrato'];
    $storage = $storage !== '' ? $storage : (string) ($_SESSION['storage'] ?? storagePadrao());

    if (strtolower($retrato['charset']) !== CHARSET_EXIGIDO) {
        http_response_code(409);
        telaRecusa(
            'O banco não usa o conjunto de caracteres exigido',
            [
                'O banco foi criado com <code>' . e($retrato['charset'] ?: 'desconhecido') . '</code>, '
                    . 'e o VCOSTAEDU exige <code>' . CHARSET_EXIGIDO . '</code>. Com outro conjunto de caracteres, nomes com acento e cedilha seriam gravados de forma incorreta, sem correção posterior.',
                'Alterar a configuração de um banco existente não corrige o que já está gravado. Crie um banco novo com o comando abaixo e informe-o na etapa anterior:',
            ],
            'Nenhuma alteração foi feita no banco informado.',
            comandoCreateDatabase($dados['dbname']),
            '?passo=banco',
            'Voltar e informar outro banco'
        );

        return;
    }

    if ($retrato['tabelas'] > 0) {
        http_response_code(409);
        telaRecusa(
            'O banco informado não está vazio',
            [
                'Ele contém <strong>' . (int) $retrato['tabelas'] . '</strong> ' . ((int) $retrato['tabelas'] === 1 ? 'tabela' : 'tabelas') . '; ' . ((int) $retrato['tabelas'] === 1 ? 'ela se chama ' : 'a primeira é ') . '<code>' . e($retrato['exemplo']) . '</code>.',
                'Pode ser outro sistema ou uma instalação anterior. Instalar sobre ele apagaria esses dados, por isso a instalação foi interrompida.',
                'Informe um banco <strong>vazio</strong> na etapa anterior ou, se tiver certeza de que nada nele está em uso, esvazie este banco.',
            ],
            'Nenhuma alteração foi feita.',
            '',
            '?passo=banco',
            'Voltar e informar outro banco'
        );

        return;
    }

    $collationDiferente = strtolower($retrato['collation']) !== COLLATION_DESEJADA;

    abrirPagina('Conferência', 'conferencia');
    ?>
    <div class="card card-md">
      <div class="card-body">
        <h2 class="card-title">Conferência do banco de dados</h2>
        <p class="text-secondary">
          A conexão foi estabelecida. Resultado da conferência:
        </p>

        <div class="datagrid mb-3">
          <div class="datagrid-item">
            <div class="datagrid-title">Conjunto de caracteres</div>
            <div class="datagrid-content"><code><?= e($retrato['charset']) ?></code></div>
          </div>
          <div class="datagrid-item">
            <div class="datagrid-title">Ordenação de texto <span class="text-secondary">(collation)</span></div>
            <div class="datagrid-content"><code><?= e($retrato['collation']) ?></code></div>
          </div>
          <div class="datagrid-item" id="modoDoBanco">
            <div class="datagrid-title">Modo do banco</div>
            <div class="datagrid-content"><?= VcBanco::modoCompleto((string) ($retrato['modo'] ?? '')) ? 'estrito, fixado pelo sistema' : 'incompleto' ?> <span class="text-secondary small"><code><?= e((string) ($retrato['modo'] ?? '')) ?></code></span></div>
          </div>
          <div class="datagrid-item">
            <div class="datagrid-title">Tabelas</div>
            <div class="datagrid-content">nenhuma (o banco está vazio)</div>
          </div>
        </div>

        <?php if ($collationDiferente): ?>
        <div class="alert alert-warning" role="alert">
          <h4 class="alert-title">Observação</h4>
          <div class="text-secondary">
            O banco usa a ordenação <code><?= e($retrato['collation']) ?></code>; o VCOSTAEDU foi projetado para <code><?= COLLATION_DESEJADA ?></code>. As tabelas do sistema são criadas com a ordenação própria, e a instalação pode continuar.
          </div>
        </div>
        <?php endif; ?>

        <form method="post" action="?passo=arquivo">
          <input type="hidden" name="token" value="<?= e($_SESSION['token']) ?>">
          <input type="hidden" name="acao" value="gravar">

          <div class="mb-3">
            <label class="form-label required" for="storage">Pasta dos arquivos privados</label>
            <input type="text" id="storage" name="storage" class="form-control font-monospace" required
                   value="<?= e($storage) ?>">
            <div class="form-hint">
              Guarda os PDFs dos documentos emitidos e as imagens de assinatura. O padrão é a pasta <code>storage</code> do sistema, criada pelo instalador e bloqueada para acesso pelo navegador. Se você administra o servidor, prefira uma pasta <strong>fora</strong> da pasta publicada.
            </div>
            <?php if ($erroStorage !== ''): ?>
            <div class="invalid-feedback d-block"><?= e($erroStorage) ?></div>
            <?php endif; ?>
          </div>

          <p class="text-secondary">
            <strong>Próxima etapa:</strong> o instalador grava os dois arquivos de configuração (painel e portal do aluno) e cria as pastas do sistema. É a primeira etapa que faz alterações no servidor.
          </p>

          <button type="submit" class="btn btn-primary w-100">Gravar a configuração</button>
        </form>

        <div class="text-center text-secondary mt-3 small">
          <a href="?passo=banco">Usar outro banco</a>
        </div>
      </div>
    </div>
    <?php
    fecharPagina();
}

// =============================================================================
// TELA 4 — OS ARQUIVOS DE CONFIGURAÇÃO E AS PASTAS
//
// Dois caminhos, e o segundo NÃO é exceção: quando o servidor web não consegue gravar, o instalador MOSTRA o
// conteúdo dos dois arquivos para a pessoa criar à mão e continua do ponto certo quando eles aparecerem.
// =============================================================================

function telaArquivo(bool $envio): void
{
    $acao = (string) ($_POST['acao'] ?? '');

    if ($envio && $acao === 'gravar') {
        $storage = trim((string) ($_POST['storage'] ?? ''));
        $erro = validarStorage($storage);

        if ($erro !== '') {
            telaConferencia($erro, $storage);

            return;
        }

        $_SESSION['storage'] = rtrim(str_replace('\\', '/', $storage), '/');
    }

    if (empty($_SESSION['storage'])) {
        header('Location: ?passo=conferencia');
        exit;
    }

    $_SESSION['instalacao_em_curso'] = true;

    $dados = $_SESSION['banco'];
    $dados['storage'] = $_SESSION['storage'];
    $conteudos = montarConfigs($dados);
    $faltamPastas = criarPastasDeExecucao($dados['storage']);

    if ($envio && $acao === 'conferir') {
        $conferencia = conferirConfigManual($dados['dbname']);
        $faltamPastas = criarPastasDeExecucao($dados['storage']);

        if ($conferencia['ok'] && $faltamPastas === []) {
            telaArquivoPronto(false);

            return;
        }

        telaArquivoManual($conteudos, $faltamPastas, $conferencia['ok'] ? 'ainda faltam pastas' : $conferencia['motivo']);

        return;
    }

    $gravacao = gravarConfigs($conteudos);

    if (!$gravacao['ok'] || $faltamPastas !== []) {
        telaArquivoManual($conteudos, $faltamPastas, '', $gravacao['ok'] ? 'não foi possível criar todas as pastas' : $gravacao['motivo']);

        return;
    }

    telaArquivoPronto();
}

function telaArquivoManual(array $conteudos, array $faltamPastas, string $erroConferencia = '', string $motivo = ''): void
{
    abrirPagina('Configuração', 'arquivo');
    ?>
    <div class="card card-md">
      <div class="card-body">
        <h2 class="card-title">Criação manual dos arquivos de configuração</h2>
        <p class="text-secondary">
          O servidor não permitiu que o instalador gravasse os arquivos. Isso é comum em hospedagem compartilhada e não impede a instalação: crie os arquivos abaixo e continue.
          <?php if ($motivo !== ''): ?>
          <br><span class="small">Detalhe: <?= e($motivo) ?>.</span>
          <?php endif; ?>
        </p>

        <?php if ($erroConferencia !== ''): ?>
        <div class="alert alert-danger" role="alert">
          <h4 class="alert-title">Ainda há pendências</h4>
          <div class="text-secondary"><?= e($erroConferencia) ?>.</div>
        </div>
        <?php endif; ?>

        <?php foreach ($conteudos as $app => $conteudo): ?>
        <?php if (is_file(caminhoDoConfig($app)) && (string) @file_get_contents(caminhoDoConfig($app)) === $conteudo) continue; ?>
        <p class="text-secondary mb-1">
          Crie o arquivo <code><?= e($app) ?>/core/Config.php</code> com o conteúdo abaixo, sem alterações:
        </p>
        <div class="mb-3">
          <pre class="bg-dark text-white p-3 rounded vc-comando mb-0" data-vc-copiar style="max-height:18rem;overflow:auto"><code><?= e($conteudo) ?></code></pre>
        </div>
        <?php endforeach; ?>

        <?php if ($faltamPastas !== []): ?>
        <p class="text-secondary mb-1">Crie também as pastas abaixo, com permissão de escrita para o servidor web:</p>
        <ul class="text-secondary small font-monospace">
          <?php foreach ($faltamPastas as $p): ?>
          <li><?= e($p) ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <div class="alert alert-warning" role="alert">
          <h4 class="alert-title">Estes arquivos contêm a senha do banco</h4>
          <div class="text-secondary">
            Eles já estão excluídos do controle de versão (<code>.gitignore</code>). Não os publique e não permita o acesso a eles pelo navegador.
          </div>
        </div>

        <form method="post" action="?passo=arquivo">
          <input type="hidden" name="token" value="<?= e($_SESSION['token']) ?>">
          <input type="hidden" name="acao" value="conferir">
          <button type="submit" class="btn btn-primary w-100">Arquivos criados, continuar</button>
        </form>
      </div>
    </div>
    <?php
    fecharPagina();
}

/**
 * Os arquivos estão no lugar — e é aqui que a verificação da exposição acontece. VCOSTAEDU: três alvos (as duas
 * configurações e a pasta dos arquivos privados). Se QUALQUER um vier com conteúdo, a instalação PARA aqui: não
 * há botão para seguir até o servidor ser corrigido.
 */
function telaArquivoPronto(bool $gravadoPeloInstalador = true): void
{
    $resultados = verificarExposicao((string) $_SESSION['storage']);
    $exposto = algumExposto($resultados);

    abrirPagina('Configuração', 'arquivo');
    ?>
    <div class="card card-md">
      <div class="card-status-top bg-<?= $exposto ? 'danger' : 'success' ?>"></div>
      <div class="card-body">
        <h2 class="card-title"><?= $exposto ? 'Instalação interrompida: há arquivo privado acessível pela web'
            : ($gravadoPeloInstalador ? 'Configuração gravada' : 'Configuração encontrada') ?></h2>
        <p class="text-secondary">
          Os dados do banco foram gravados em dois arquivos (painel e portal do aluno) e as pastas do sistema foram criadas. Em seguida, o instalador solicitou cada arquivo privado pelo endereço da web para verificar se o servidor os entrega:
        </p>

        <div class="list-group list-group-flush mb-3" id="exposicao">
          <?php foreach ($resultados as $r): ?>
          <div class="list-group-item px-0" data-veredito="<?= e($r['veredito']) ?>">
            <div class="row align-items-center">
              <div class="col-auto"><?= $r['veredito'] === 'exposto' ? iconeErro() : ($r['veredito'] === 'nao_medido' ? iconeAviso() : iconeOk()) ?></div>
              <div class="col">
                <div class="fw-medium"><?= e($r['alvo']) ?> —
                  <?= e(match ($r['veredito']) {
                      'exposto' => 'PÚBLICO',
                      'protegido' => 'protegido',
                      'executado' => 'conteúdo protegido',
                      'fora_da_web' => 'fora da pasta publicada',
                      default => 'não foi possível verificar',
                  }) ?></div>
                <div class="text-secondary small"><?= e($r['detalhe']) ?></div>
                <?php if ($r['url'] !== '' && $r['veredito'] !== 'protegido'): ?>
                <div class="small font-monospace text-break"><?= e($r['url']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if ($exposto): ?>
        <div class="alert alert-danger" role="alert">
          <h4 class="alert-title">Não continue: há um arquivo privado acessível pela web</h4>
          <div class="text-secondary">
            <ol class="mb-0">
              <li>Se for um arquivo de configuração, troque imediatamente a senha do usuário do banco.</li>
              <li>Confira se o PHP está processando arquivos <code>.php</code> nessa pasta.</li>
              <li>Confira se os arquivos <code>.htaccess</code> do sistema estão em vigor (a diretiva <code>AllowOverride</code> precisa permitir).</li>
              <li>Ou escolha, para os arquivos privados, uma pasta fora da pasta publicada.</li>
            </ol>
          </div>
        </div>
        <a href="?passo=arquivo" class="btn btn-outline-danger w-100">Verificar novamente</a>
        <?php else: ?>
        <p class="text-secondary mt-3">
          <strong>Próxima etapa:</strong> a criação das tabelas do sistema no banco de dados. Leva alguns segundos.
        </p>
        <a href="?passo=tabelas" class="btn btn-primary w-100">Criar as tabelas</a>
        <?php endif; ?>
      </div>
    </div>
    <?php
    fecharPagina();
}

// =============================================================================
// TELA 5 — CRIAR AS TABELAS, À VISTA
// =============================================================================

function telaTabelas(bool $envio): void
{
    $preparo = prepararRunner();

    if (!$preparo['ok']) {
        http_response_code(500);
        telaRecusa('Não foi possível continuar.', [e($preparo['erro'])], 'As tabelas não foram criadas.');

        return;
    }

    $runner = $preparo['runner'];

    if (!$envio) {
        telaTabelasConvite($runner);

        return;
    }

    try {
        $quantas = $runner->aplicar(false);
    } catch (Throwable $e) {
        error_log('[vcostaedu][instalador] migration falhou: ' . $e->getMessage());
        http_response_code(500);
        telaMigrationFalhou($runner, $e);

        return;
    }

    telaTabelasPronto($runner, $quantas);
}

function telaTabelasConvite(object $runner): void
{
    $pendentes = $runner->pendentes();

    abrirPagina('Tabelas', 'tabelas');
    ?>
    <div class="card card-md">
      <div class="card-body">
        <h2 class="card-title">Criar as tabelas do sistema</h2>

        <?php if (empty($pendentes)): ?>
        <p class="text-secondary">
          As tabelas já existem. Isso acontece quando esta etapa já foi executada.
        </p>
        <?php else: ?>
        <p class="text-secondary">
          A configuração foi gravada. O instalador vai criar as tabelas no banco de dados em <strong><?= count($pendentes) ?></strong> <?= count($pendentes) === 1 ? 'etapa' : 'etapas' ?>, nesta ordem. O processo leva alguns segundos; aguarde até a página exibir o resultado.
        </p>
        <ul class="list-group list-group-flush mb-3">
          <?php foreach ($pendentes as $m): ?>
          <li class="list-group-item px-0 font-monospace small"><?= e($m['arquivo']) ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <form method="post" action="?passo=tabelas">
          <input type="hidden" name="token" value="<?= e($_SESSION['token']) ?>">
          <button type="submit" class="btn btn-primary w-100">
            <?= empty($pendentes) ? 'Continuar' : 'Criar as tabelas' ?>
          </button>
        </form>
      </div>
    </div>
    <?php
    fecharPagina();
}

function telaMigrationFalhou(object $runner, Throwable $e): void
{
    $feitas = $runner->resultados();

    abrirPagina('Tabelas', 'tabelas');
    ?>
    <div class="card card-md">
      <div class="card-status-top bg-danger"></div>
      <div class="card-body">
        <h2 class="card-title text-danger">A criação das tabelas foi interrompida</h2>

        <div class="alert alert-danger" role="alert">
          <div class="text-secondary"><?= e(primeiraFrase($e->getMessage())) ?></div>
        </div>

        <?php if (!empty($feitas)): ?>
        <p class="text-secondary"><strong>Etapas aplicadas antes da falha:</strong></p>
        <ul class="list-group list-group-flush mb-3">
          <?php foreach ($feitas as $r): ?>
          <li class="list-group-item px-0 font-monospace small"><?= e($r['arquivo']) ?></li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p class="text-secondary">Nenhuma etapa chegou a ser aplicada.</p>
        <?php endif; ?>

        <p class="text-secondary">
          A etapa que falhou <strong>pode ter sido aplicada parcialmente</strong>: o banco de dados não desfaz automaticamente as alterações de estrutura.
        </p>
        <p class="text-secondary mb-0">
          <strong>Procedimento recomendado:</strong> apague o banco, crie-o novamente vazio, remova os dois arquivos de configuração e reinicie a instalação.
        </p>
      </div>
    </div>
    <?php
    fecharPagina();
}

function primeiraFrase(string $mensagem): string
{
    $corte = strpos($mensagem, ': SQLSTATE');

    return $corte === false ? $mensagem : substr($mensagem, 0, $corte) . '.';
}

function telaTabelasPronto(object $runner, int $quantas): void
{
    $resultados = $runner->resultados();
    $adiadas = array_filter($resultados, static fn(array $r): bool => $r['situacao'] === 'ADIADA');

    abrirPagina('Tabelas', 'tabelas');
    ?>
    <div class="card card-md">
      <div class="card-status-top bg-<?= $adiadas ? 'warning' : 'success' ?>"></div>
      <div class="card-body">
        <h2 class="card-title"><?= $quantas > 0 ? 'Tabelas criadas' : 'O banco já estava atualizado' ?></h2>

        <?php if (!empty($resultados)): ?>
        <div class="table-responsive mb-3">
          <table class="table table-sm" id="etapas">
            <thead>
              <tr><th>Etapa</th><th class="text-end">Tempo</th></tr>
            </thead>
            <tbody>
              <?php foreach ($resultados as $r): ?>
              <tr data-situacao="<?= e($r['situacao']) ?>">
                <td class="font-monospace small"><?= e($r['arquivo']) ?></td>
                <td class="text-end text-secondary"><?= $r['situacao'] === 'ADIADA'
                    ? '<span class="badge bg-warning-lt">adiada</span>'
                    : (int) $r['duracao_ms'] . ' ms' ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <?php if ($adiadas): ?>
        <div class="alert alert-warning" role="alert" id="avisoGatilhos">
          <h4 class="alert-title">Os gatilhos de proteção do histórico não foram instalados</h4>
          <div class="text-secondary">
            O usuário do banco não tem o privilégio <code>TRIGGER</code>, o que é comum em hospedagem compartilhada. <strong>O sistema funciona normalmente</strong>: os documentos emitidos continuam protegidos pelo próprio sistema, mas não pelo banco. Solicite o privilégio ao provedor e execute <code>php bin/migrar.php aplicar</code> na pasta do sistema.
          </div>
        </div>
        <?php endif; ?>

        <p class="text-secondary">
          As tabelas foram criadas, com os perfis de acesso, o menu, os parâmetros e os modelos de documento.
        </p>
        <p class="text-secondary">
          <strong>Próxima etapa:</strong> a criação da sua conta de administrador.
        </p>

        <a href="?passo=administrador" class="btn btn-primary w-100">Criar a conta de administrador</a>
      </div>
    </div>
    <?php
    fecharPagina();
}

// =============================================================================
// TELA 6 — A CONTA DO PRIMEIRO ADMINISTRADOR
// =============================================================================

function telaAdministrador(bool $envio): void
{
    $conexao = conexaoDaAplicacao();

    if (!$conexao['ok']) {
        http_response_code(500);
        telaRecusa('Não foi possível continuar.', [e($conexao['erro'])], 'A conta de administrador não foi criada.');

        return;
    }

    /* Recarga depois de criar: a conta já existe, então o passo que falta é o tipo da instituição (Onda 4, Etapa 1),
       se ainda não foi escolhido nesta instalação, e o "Pronto". */
    if (existeAdministrador($conexao['pdo'])) {
        if (!empty($_SESSION['instalacao_em_curso']) && empty($_SESSION['tipo_escolhido'])) {
            if ($envio && isset($_POST['tipo'])) {
                $tipo = (string) $_POST['tipo'];
                if (!VcVocabulario::valido($tipo)) {
                    telaTipo('Escolha um dos tipos.');

                    return;
                }
                gravarTipoInstituicao($conexao['pdo'], $tipo);
                if ($tipo === VcVocabulario::LIVRE) { // D-106: o financeiro e a matrícula pela secretaria, como marcados
                    $recursos = (array) ($_POST['recurso'] ?? []);
                    VcTipoLivre::gravarRecursos($conexao['pdo'], array_map(static fn($r) => isset($recursos[$r]), array_combine(array_keys(VcVocabulario::RECURSOS_LIVRE), array_keys(VcVocabulario::RECURSOS_LIVRE))));
                }
                $_SESSION['tipo_escolhido'] = $tipo;
            } else {
                telaTipo();

                return;
            }
        }
        telaPronto($conexao['pdo']);

        return;
    }

    if (!$envio) {
        telaAdministradorForm();

        return;
    }

    $dados = [
        'usuario'     => trim((string) ($_POST['usuario'] ?? '')),
        'nome'        => (string) ($_POST['nome'] ?? ''),
        'email'       => trim((string) ($_POST['email'] ?? '')),
        'senha'       => (string) ($_POST['senha'] ?? ''),
        'confirmacao' => (string) ($_POST['confirmacao'] ?? ''),
    ];

    $erros = validarAdministrador($dados);

    if (!empty($erros)) {
        telaAdministradorForm($dados, $erros);

        return;
    }

    $gravacao = criarAdministrador($conexao['pdo'], $dados);

    if (!$gravacao['ok']) {
        telaAdministradorForm($dados, [$gravacao['erro']]);

        return;
    }

    $_SESSION['usuario_criado'] = $dados['usuario'];
    telaTipo(); // Onda 4, Etapa 1: o tipo da instituição, antes de "Como você quer começar?"
}

/**
 * O TIPO DA INSTITUIÇÃO (Onda 4, Etapa 1, docs/ESCOLA.md): ensino superior ou escola, uma frase de cada. Grava a
 * chave "instituicao.tipo" (a migration 0025 já pôs "superior"); depois, a tela "Pronto".
 */
function telaTipo(string $erro = ''): void
{
    abrirPagina('Tipo de instituição', 'administrador'); // dentro do passo "Sua conta" (a régua de passos continua com sete)
    ?>
    <div class="card card-md">
      <div class="card-body">
        <h2 class="card-title" id="tituloTipo">Que tipo de instituição vai usar o sistema?</h2>
        <p class="text-secondary">A escolha decide as telas, as palavras e as regras que vêm prontas. Ela pode ser trocada depois só enquanto a instalação não tiver dados próprios.</p>
        <?php if ($erro !== ''): ?><div class="alert alert-danger" role="alert"><?= e($erro) ?></div><?php endif; ?>
        <form method="post" action="?passo=administrador" id="tipoInstituicao">
          <input type="hidden" name="token" value="<?= e((string) $_SESSION['token']) ?>">
          <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column gap-2 mb-3">
            <?php foreach (VcVocabulario::TIPOS as $valor => [$nome, $frase]): ?>
            <label class="form-selectgroup-item flex-fill">
              <input type="radio" name="tipo" value="<?= e($valor) ?>" class="form-selectgroup-input" id="tipo_<?= e($valor) ?>"<?= $valor === VcVocabulario::SUPERIOR ? ' checked' : '' ?>>
              <div class="form-selectgroup-label d-flex align-items-center p-3">
                <div class="me-3"><span class="form-selectgroup-check"></span></div>
                <div class="form-selectgroup-label-content text-start">
                  <div class="fw-medium"><?= e($nome) ?></div>
                  <div class="text-secondary small"><?= e($frase) ?></div>
                </div>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
          <?php /* Fechamento (D-106): os recursos do curso livre — ligar e desligar agora; depois, em Configurações → Recursos do curso livre */ ?>
          <fieldset class="border rounded p-3 mb-3" id="recursosLivre">
            <legend class="fs-5 fw-medium float-none w-auto px-1 mb-1">No curso livre</legend>
            <p class="text-secondary small mb-2">O curso livre nasce enxuto. Ligue o que a sua instituição vai usar (dá para mudar depois, em Configurações → Recursos do curso livre). Nos outros tipos, estas caixas não contam.</p>
            <?php foreach (VcVocabulario::RECURSOS_LIVRE as $r => [$nomeR, $fraseR, $padraoR]): ?>
            <label class="form-check mb-2">
              <input class="form-check-input" type="checkbox" name="recurso[<?= e($r) ?>]" value="1" id="recurso_<?= e($r) ?>"<?= $padraoR ? ' checked' : '' ?>>
              <span class="form-check-label"><span class="fw-medium"><?= e($nomeR) ?></span> <span class="text-secondary small d-block"><?= e($fraseR) ?></span></span>
            </label>
            <?php endforeach; ?>
          </fieldset>
          <script>
            (function () {
              var caixa = document.getElementById('recursosLivre');
              var mostrar = function () { var l = document.getElementById('tipo_livre'); caixa.hidden = !(l && l.checked); };
              document.querySelectorAll('#tipoInstituicao input[name="tipo"]').forEach(function (r) { r.addEventListener('change', mostrar); });
              mostrar();
            })();
          </script>
          <button type="submit" class="btn btn-primary w-100" id="confirmarTipo">Continuar</button>
        </form>
      </div>
    </div>
    <?php
    fecharPagina();
}

/** O tipo gravado (sem a chave, ensino superior). */
function tipoInstituicao(PDO $pdo): string
{
    try {
        $v = $pdo->query("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'")->fetchColumn();
    } catch (Throwable $e) {
        $v = false;
    }

    return is_string($v) && VcVocabulario::valido($v) ? $v : VcVocabulario::SUPERIOR;
}

/** A demonstração existe para o tipo? (desde a 1.41.0, nos dois: a faculdade e o colégio fictícios) */
function demonstracaoDisponivel(string $tipo): bool
{
    return VcVocabulario::valido($tipo);
}

/** Grava o tipo escolhido no estado da instalação. */
function gravarTipoInstituicao(PDO $pdo, string $tipo): void
{
    $pdo->prepare('INSERT INTO adms_instalacao_estado (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)')
        ->execute(['instituicao.tipo', $tipo]);
    VcHorarioPadrao::aplicar($pdo, $tipo); // D-86: as aulas do dia padrão nascem só na escola
    VcAtestadoEscola::aplicar($pdo, $tipo); // D-102: o atestado de matrícula da escola
    VcTipoLivre::aplicar($pdo, $tipo); // D-106: no curso livre, o Moodle no menu do aluno
}

function telaAdministradorForm(array $dados = [], array $erros = []): void
{
    $dados += ['usuario' => 'administrador', 'nome' => '', 'email' => ''];

    abrirPagina('Sua conta', 'administrador');
    ?>
    <div class="card card-md">
      <div class="card-body">
        <h2 class="card-title">Conta de administrador</h2>
        <p class="text-secondary">
          Crie a primeira conta do sistema. Ela terá acesso completo (perfil Administrador).
        </p>
        <p class="text-secondary">
          O VCOSTAEDU <strong>não tem senha padrão</strong>: nenhuma conta existe até que este formulário seja enviado.
        </p>

        <?php if (!empty($erros)): ?>
        <div class="alert alert-danger" role="alert">
          <h4 class="alert-title">Corrija os itens abaixo</h4>
          <ul class="text-secondary mb-0">
            <?php foreach ($erros as $erro): ?>
            <li><?= e($erro) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>

        <form method="post" action="?passo=administrador" autocomplete="off">
          <input type="hidden" name="token" value="<?= e($_SESSION['token']) ?>">

          <div class="mb-3">
            <label class="form-label required" for="usuario">Nome de usuário</label>
            <input type="text" id="usuario" name="usuario" class="form-control" required
                   value="<?= e($dados['usuario']) ?>">
            <div class="form-hint">Usado para entrar no sistema, assim como o e-mail.</div>
          </div>

          <div class="mb-3">
            <label class="form-label required" for="nome">Nome</label>
            <input type="text" id="nome" name="nome" class="form-control" required
                   value="<?= e($dados['nome']) ?>">
          </div>

          <div class="mb-3">
            <label class="form-label required" for="email">E-mail</label>
            <input type="email" id="email" name="email" class="form-control" required
                   value="<?= e($dados['email']) ?>">
            <div class="form-hint">
              Usado para enviar o link de redefinição de senha, depois que o envio de e-mail for configurado (Configurações → Configurações de e-mail).
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label required" for="senha">Senha</label>
            <input type="password" id="senha" name="senha" class="form-control" required
                   <?= VcSenha::atributos('nova', [], '#nome,#usuario,#email') ?>>
            <div class="form-hint">
              <?= e(VcSenha::ajuda()) ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label required" for="confirmacao">Repita a senha</label>
            <input type="password" id="confirmacao" name="confirmacao" class="form-control" required
                   <?= VcSenha::atributos('confirmar', [], '', '#senha') ?>>
          </div>

          <div class="form-footer">
            <button type="submit" class="btn btn-primary w-100">Criar a conta e concluir</button>
          </div>
        </form>
      </div>
    </div>
    <?php
    fecharPagina();
}

// =============================================================================
// TELA 7 — O FIM
//
// Não declara sucesso por ter chegado até aqui: confere o acesso contra o banco e o ambiente pelo
// verificar_ambiente.php, a mesma ferramenta do roteiro de implantação.
// =============================================================================

function telaPronto(PDO $pdo): void
{
    $conferencia = conferirAcesso($pdo);
    $ambiente = verificarAmbienteDoServidor();
    $usuario = (string) ($_SESSION['usuario_criado'] ?? '');

    unset($_SESSION['instalacao_em_curso'], $_SESSION['banco'], $_SESSION['retrato'], $_SESSION['storage'], $_SESSION['usuario_criado'], $_SESSION['tipo_escolhido']);

    abrirPagina('Pronto', 'pronto');
    ?>
    <div class="card card-md">
      <div class="card-status-top bg-<?= $conferencia['ok'] ? 'success' : 'danger' ?>"></div>
      <div class="card-body">
        <h2 class="card-title" id="tituloPronto">
          <?= $conferencia['ok'] ? 'Instalação concluída' : 'A instalação terminou com pendências' ?>
        </h2>

        <p class="text-secondary">
          <?= $conferencia['ok']
              ? 'A conta foi criada. O instalador verificou no banco de dados se o sistema está pronto para uso:'
              : 'As etapas foram executadas, mas a verificação abaixo encontrou problemas:' ?>
        </p>

        <div class="list-group list-group-flush mb-3" id="conferenciaAcesso">
          <?php foreach ($conferencia['itens'] as $item): ?>
          <div class="list-group-item px-0" data-ok="<?= $item['ok'] ? '1' : (!empty($item['aviso']) ? 'aviso' : '0') ?>">
            <div class="row align-items-center">
              <div class="col-auto"><?= $item['ok'] ? iconeOk() : (!empty($item['aviso']) ? iconeAviso() : iconeErro()) ?></div>
              <div class="col">
                <div class="fw-medium"><?= e($item['rotulo']) ?></div>
                <div class="text-secondary small"><?= e($item['detalhe']) ?></div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if (!$conferencia['ok']): ?>
        <div class="alert alert-danger" role="alert">
          <h4 class="alert-title">Não utilize o sistema neste estado</h4>
          <div class="text-secondary">
            Um item acima falhou. Recomenda-se apagar os dois arquivos de configuração e o banco, criar o banco novamente vazio e reiniciar a instalação.
          </div>
        </div>
        <?php else: ?>
        <div class="alert alert-success" role="alert">
          <h4 class="alert-title">Acesso liberado</h4>
          <div class="text-secondary">
            <p class="mb-2">
              No painel, entre com<?= $usuario !== '' ? ' o usuário <code>' . e($usuario) . '</code>' : ' o seu usuário' ?> e a senha cadastrada.
            </p>
            <?php /* Onda 2, Etapa 4: a escolha do começo — ela segue até depois do login (painel/Login, Home) */ ?>
            <p class="mb-2 mt-3"><strong>Como você quer começar?</strong></p>
            <?php $tipo = tipoInstituicao($pdo); $comDemo = demonstracaoDisponivel($tipo); ?>
            <p class="small text-secondary mb-2" id="tipoEscolhido">Tipo de instituição: <strong><?= e(VcVocabulario::nome($tipo)) ?></strong>.</p>
            <div class="d-flex flex-wrap gap-2">
              <?php if ($comDemo): ?>
              <a href="<?= e(urlDaRaiz()) ?>painel/login/acesso?comecar=demonstracao" class="btn btn-primary" id="conhecerDemonstracao">Conhecer com dados de demonstração</a>
              <?php endif; ?>
              <a href="<?= e(urlDaRaiz()) ?>painel/login/acesso?comecar=instituicao" class="btn btn-<?= $comDemo ? 'outline-secondary' : 'primary' ?>" id="abrirPainel">Configurar a minha instituição agora</a>
            </div>
            <?php /* Onda 6, Etapa 7 (D-59): o convite do registro voluntário — nunca vem marcado */ ?>
            <label class="form-check mt-3 mb-1" id="conviteRegistroInstalador"><input class="form-check-input" type="checkbox" id="registrarInstalacao">
              <span class="form-check-label">Quero registrar esta instalação no projeto (voluntário). No painel, você vê exatamente o que vai antes de enviar, e nunca vai dado de aluno, professor ou responsável.</span></label>
            <script>
              document.getElementById('registrarInstalacao').addEventListener('change', function (ev) {
                document.querySelectorAll('#conhecerDemonstracao, #abrirPainel').forEach(function (a) {
                  var u = new URL(a.href);
                  ev.target.checked ? u.searchParams.set('registrar', '1') : u.searchParams.delete('registrar');
                  a.href = u.toString();
                });
              });
            </script>
            <?php if ($comDemo): ?>
            <?php if ($tipo === VcVocabulario::LIVRE): /* D-106: o que vem ligado no curso livre */
              $recLigados = [];
              foreach (VcVocabulario::RECURSOS_LIVRE as $r => [$nomeR, , $padraoR]) {
                  $vR = $pdo->query("SELECT valor FROM adms_instalacao_estado WHERE chave = " . $pdo->quote('livre.' . $r))->fetchColumn();
                  $recLigados[] = $nomeR . ': ' . (($vR === false ? $padraoR : $vR === '1') ? 'ligado' : 'desligado');
              } ?>
            <p class="small text-secondary mb-2" id="recursosEscolhidos"><?= e(implode('; ', $recLigados)) ?>.</p>
            <?php endif; ?>
            <p class="small text-secondary mt-2 mb-0">A demonstração monta <?= $tipo === VcVocabulario::ESCOLA ? 'um colégio fictício completo' : ($tipo === VcVocabulario::LIVRE ? 'um curso livre fictício (o curso, a turma aberta e as quatro disciplinas)' : 'uma instituição de ensino superior fictícia completa') ?>, que você remove depois com um botão, sem reinstalar. A escolha também aparece na Página Inicial.</p>
            <?php else: ?>
            <p class="small text-secondary mt-2 mb-0" id="semDemonstracao">A demonstração com dados de escola ainda não está disponível nesta versão.</p>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <h3 class="h4 mt-4">Verificação do servidor</h3>
        <?php if (!$ambiente['executou']): ?>
        <p class="text-secondary">
          Não foi possível executar a verificação a partir do instalador. Execute na pasta do sistema:
        </p>
        <pre class="bg-dark text-white p-3 rounded vc-comando" data-vc-copiar><code><?= e($ambiente['comando']) ?></code></pre>
        <?php else: ?>
        <?php
        /* o resumo: o que a pessoa precisa fazer, em ordem de importância (as falhas, depois os avisos), poucos itens */
        $afazer = array_merge(
            array_values(array_filter($ambiente['linhas'], fn($l) => $l['tipo'] === 'FALHA')),
            array_values(array_filter($ambiente['linhas'], fn($l) => $l['tipo'] === 'ALERTA'))
        );
        ?>
        <div id="resumoAFazer" data-itens="<?= count($afazer) ?>">
          <?php if (!$afazer): ?>
          <p class="text-success mb-2"><?= iconeOk() ?> Nada a fazer: o servidor está pronto.</p>
          <?php else: ?>
          <p class="mb-2">O que fazer<?= count($afazer) > 5 ? ' (os ' . min(5, count($afazer)) . ' mais importantes de ' . count($afazer) . ')' : '' ?>:</p>
          <ol class="mb-2">
            <?php foreach (array_slice($afazer, 0, 5) as $l): ?>
            <li data-tipo="<?= e($l['tipo']) ?>"><span class="badge bg-<?= $l['tipo'] === 'FALHA' ? 'danger' : 'warning' ?>-lt me-1"><?= $l['tipo'] === 'FALHA' ? 'Resolver' : 'Aviso' ?></span><?= e($l['texto']) ?></li>
            <?php endforeach; ?>
          </ol>
          <p class="text-secondary small">Nenhum item impede o uso; resolva os marcados antes de liberar o acesso às pessoas.</p>
          <?php endif; ?>
        </div>
        <details class="mb-2" id="detalheAmbiente">
        <summary class="text-secondary">Ver todas as conferências (<?= count(array_filter($ambiente['linhas'], fn($l) => $l['tipo'] !== 'SECAO')) ?>)</summary>
        <div class="list-group list-group-flush small mb-2" id="conferenciaAmbiente">
          <?php foreach ($ambiente['linhas'] as $l): ?>
          <?php if ($l['tipo'] === 'SECAO'): ?>
          <div class="list-group-item px-0 fw-bold pt-3"><?= e($l['texto']) ?></div>
          <?php else: ?>
          <div class="list-group-item px-0 py-1" data-tipo="<?= e($l['tipo']) ?>">
            <span class="badge bg-<?= $l['tipo'] === 'OK' ? 'success' : ($l['tipo'] === 'ALERTA' ? 'warning' : 'danger') ?>-lt me-2"><?= e($l['tipo']) ?></span><?= e($l['texto']) ?>
          </div>
          <?php endif; ?>
          <?php endforeach; ?>
        </div>
        </details>
        <p class="text-secondary small" id="resumoAmbiente"><?= e($ambiente['resumo']) ?></p>
        <?php endif; ?>

      </div>
    </div>
    <?php
    fecharPagina();
}

// =============================================================================
// Ícones em SVG embutido (sem Font Awesome no instalador: três símbolos não pedem o pacote inteiro).
// =============================================================================

function iconeOk(): string
{
    return '<span class="text-success">'
        . '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" '
        . 'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
        . '<path stroke="none" d="M0 0h24v24H0z" fill="none"/>'
        . '<path d="M5 12l5 5l10 -10"/></svg></span>';
}

function iconeErro(): string
{
    return '<span class="text-danger">'
        . '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" '
        . 'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
        . '<path stroke="none" d="M0 0h24v24H0z" fill="none"/>'
        . '<path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg></span>';
}

function iconeAviso(): string
{
    return '<span class="text-warning">'
        . '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" '
        . 'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
        . '<path stroke="none" d="M0 0h24v24H0z" fill="none"/>'
        . '<path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 '
        . '1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/>'
        . '<path d="M12 16h.01"/></svg></span>';
}
