<?php
/*
 * Base comum das ferramentas de linha de comando da VCOSTAEDU.
 *
 * Lê a conexão da MESMA configuração do painel (painel/core/Config.php, fora do
 * git), para que nenhuma credencial precise existir em outro lugar. Só roda em
 * linha de comando.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('VCOSTAEDU', true);
foreach (['Un1Mv' . 'C05tA'] as $g) { defined($g) || define($g, true); } // a guarda de antes da 1.45.0, só para a Config.php já instalada (DECISIONS D-53)
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';

$configPainel = __DIR__ . '/../../painel/core/Config.php';
if (!is_file($configPainel)) {
    fwrite(STDERR, "Falta painel/core/Config.php (copie de Config.example.php e preencha).\n");
    exit(1);
}
require $configPainel;

final class VcostaeduConfig extends \Core\Config
{
    public function carregar(): void
    {
        $this->configAdm();
    }
}
(new VcostaeduConfig())->carregar();
require_once __DIR__ . '/../../core/Requisicao.php';
\Core\Requisicao::definir();

function vcostaedu_pdo(): PDO
{
    require_once __DIR__ . '/../../core/Banco.php'; // Onda 3, Etapa 2: o modo de SQL do sistema
    $pdo = \VcBanco::abrir(
        'mysql:host=' . HOST . ';dbname=' . DBNAME . ';charset=utf8mb4',
        USER,
        PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    return $pdo;
}

/* Senha aleatória legível: 16 caracteres sem ambíguos (0/O, 1/l/I). */
/**
 * Onda 2, Etapa 4: a demonstração ligada PELA TELA (adms_instalacao_estado, "demonstracao.ativa" = 1, migration 0008).
 * Vale como a declaração de AMBIENTE = 'demonstracao' e de BANCO_DEMONSTRACAO para as ferramentas da demonstração:
 * só o Administrador a liga, e só pela tela "Como você quer começar".
 */
function vcostaedu_demonstracao_ativa(PDO $pdo): bool
{
    try {
        $t = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'adms_instalacao_estado'")->fetchColumn();
        return $t === 1 && (string) $pdo->query("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.ativa'")->fetchColumn() === '1';
    } catch (Throwable $e) {
        return false;
    }
}

function vcostaedu_senha_aleatoria(int $tamanho = 16): string
{
    $alfabeto = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $senha = '';
    for ($i = 0; $i < $tamanho; $i++) {
        $senha .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
    }
    return $senha;
}

/*
 * E1b (D-352): TRAVA DA INSTITUIÇÃO CONFIGURADA. A demonstração (limpar_demo.php + seed_demo_processar.php) grava a
 * instituição FICTÍCIA por cima da que estiver na base, troca o logo e devolve os modelos de histórico à versão 1.
 * Se a instituição já não é a fictícia (foi configurada pela tela), as duas ferramentas recusam, a não ser com a
 * confirmação explícita VCOSTAEDU_FLAG_SOBRESCREVER. Critério: o nome. O seed entra como administrador, então quem
 * editou (usuario_mod) não distingue a instituição real da fictícia.
 */
const VCOSTAEDU_INSTITUICAO_DEMO = 'Faculdade Horizonte (fictícia)';
const VCOSTAEDU_INSTITUICOES_DEMO = ['Faculdade Horizonte (fictícia)', 'Escola de Governo Exemplar (fictícia)', 'Colégio Horizonte (fictício)', 'Instituto Ventoalto (fictício)']; // o de antes da 1.31.0; o colégio (Onda 4)
const VCOSTAEDU_FLAG_SOBRESCREVER = '--sobrescrever-instituicao-configurada';

/** O que seria perdido, ou [] se a instituição é a da demonstração (ou não existe). */
function vcostaedu_instituicao_configurada(PDO $pdo): array
{
    $i = $pdo->query('SELECT nome, logo, cabecalho_1, telefone, email FROM adms_empresas WHERE instituicao = 1 ORDER BY id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if (!$i || in_array($i['nome'], VCOSTAEDU_INSTITUICOES_DEMO, true)) {
        return [];
    }
    $perde = ['os dados da instituição (nome, mantenedora, CNPJ, e-MEC, endereço, site' . ($i['telefone'] ? ', telefone' : '') . ($i['email'] ? ', e-mail' : '') . ')'];
    if ($i['cabecalho_1']) {
        $perde[] = 'as linhas do cabeçalho dos documentos';
    }
    if ($i['logo']) {
        $perde[] = 'o logo enviado pela tela';
    }
    $versoes = (int) $pdo->query('SELECT COUNT(*) FROM rm_historico_modelo_versoes WHERE modelo_id IN (1, 2) AND versao > 1')->fetchColumn();
    if ($versoes > 0) {
        $perde[] = "as {$versoes} versão(ões) dos modelos de histórico depois da 1ª (inclusive nome e cargo de quem assina)";
    }
    return $perde;
}

/** Recusa (sai com código 6) se a instituição foi configurada e a confirmação não veio na linha de comando. */
function vcostaedu_trava_instituicao(PDO $pdo, array $argv, string $ferramenta): void
{
    $perde = vcostaedu_instituicao_configurada($pdo);
    if (!$perde) {
        return;
    }
    if (in_array(VCOSTAEDU_FLAG_SOBRESCREVER, $argv, true)) {
        fwrite(STDERR, "AVISO: " . VCOSTAEDU_FLAG_SOBRESCREVER . " informado — a instituição configurada será SOBRESCRITA pela da demonstração.\n");
        return;
    }
    fwrite(STDERR, "RECUSADO ({$ferramenta}): a instituição desta base foi configurada pela tela (não é a da demonstração).\n"
        . "Rodar a demonstração apagaria:\n  - " . implode("\n  - ", $perde) . "\n"
        . "Nada foi alterado. Para sobrescrever assim mesmo, rode de novo com " . VCOSTAEDU_FLAG_SOBRESCREVER . ".\n");
    exit(6);
}
