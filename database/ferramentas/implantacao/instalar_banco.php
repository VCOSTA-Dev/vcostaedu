<?php
/*
 * INSTALAR O BANCO PELA LINHA DE COMANDO (a outra porta do instalador pelo navegador).
 *
 * Uso: php database/ferramentas/implantacao/instalar_banco.php
 *
 * VCOSTAEDU (Etapa 2): o banco nasce pelas migrations (database/migrations/), com o MESMO runner que o instalador
 * pelo navegador e o php bin/migrar.php usam. Antes, esta ferramenta rodava database/schema.sql e conteudo.sql pelo
 * cliente mysql; os dois viraram as migrations 0001 (estrutura) e 0002 (conteúdo), e os gatilhos a 0003.
 *
 * Recusa se o banco já tiver tabelas. Sem o privilégio TRIGGER, a 0003 fica ADIADA (ALERTA) e o resto é aplicado:
 * o sistema funciona, mas o histórico emitido fica protegido só pelo código (D-201 da origem).
 */
require __DIR__ . '/_base.php';
require __DIR__ . '/../comum.php';
require VCOSTAEDU_RAIZ . '/painel/app/adms/Models/banco/AdmsMigrations.php';

Saida::secao('Banco');
try {
    $pdo = vcostaedu_pdo();
} catch (Throwable $e) {
    Saida::falha('não conectou ao banco ' . DBNAME . ' em ' . HOST . ' com o usuário ' . USER . ': ' . $e->getMessage());
    Saida::fim();
}
$n = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->fetchColumn();
if ($n > 0) {
    Saida::falha("o banco " . DBNAME . " já tem {$n} tabela(s). A instalação só roda num banco VAZIO (para voltar um backup, use restaurar.php).");
    Saida::fim();
}
Saida::ok('conectado a ' . DBNAME . ' (vazio)');

Saida::secao('Migrations');
$runner = new \App\adms\Models\banco\AdmsMigrations(VCOSTAEDU_RAIZ . '/database/migrations', $pdo);
try {
    $runner->aplicar(false);
} catch (Throwable $e) {
    foreach ($runner->mensagens() as $m) {
        Saida::info($m);
    }
    Saida::falha('a instalação parou: ' . $e->getMessage());
    Saida::fim();
}
foreach ($runner->resultados() as $r) {
    $r['situacao'] === 'ADIADA'
        ? Saida::alerta("{$r['arquivo']} ADIADA: o usuário do banco não tem o privilégio TRIGGER (os gatilhos do histórico não foram criados; peça o privilégio e rode php bin/migrar.php aplicar)")
        : Saida::ok("{$r['arquivo']} ({$r['duracao_ms']} ms)");
}
$tabelas = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME <> 'adms_migrations'")->fetchColumn();
$tabelas >= 88 ? Saida::ok("{$tabelas} tabelas") : Saida::falha("{$tabelas} tabelas (esperadas ao menos as 88 da base)");
$paginas = (int) $pdo->query('SELECT COUNT(*) FROM adms_paginas')->fetchColumn();
$versao = (string) $pdo->query('SELECT numero FROM adms_versoes WHERE situacao = 1 ORDER BY id DESC LIMIT 1')->fetchColumn();
Saida::ok("conteúdo carregado: {$paginas} páginas do painel, versão {$versao} no banco");
require_once dirname(__DIR__, 3) . '/core/Legal.php';
$versaoArquivos = \VcLegal::versaoDoPacote(dirname(__DIR__, 3));
$versao === $versaoArquivos ? Saida::ok("versão dos arquivos = versão do banco ({$versao})") : Saida::alerta("versão dos arquivos ({$versaoArquivos}) difere da versão do banco ({$versao})");

Saida::secao('Gatilhos');
passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/conferir_gatilhos.php'), $rcG);
$rcG === 0 ? Saida::ok('os 6 gatilhos conferidos') : Saida::alerta('gatilhos ausentes (ver acima)');

Saida::info('Próximo passo: php database/ferramentas/criar_admin.php <usuario> <email> (a senha aparece uma vez, só no terminal).');
Saida::fim();
