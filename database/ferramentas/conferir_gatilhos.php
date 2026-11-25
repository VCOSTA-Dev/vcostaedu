<?php
/*
 * Confere se os 6 gatilhos de imutabilidade estão no banco: os 4 do histórico emitido (E6c, D-201) e, desde a 1.13.0,
 * os 2 dos resultados de fechamento substituídos (D-397).
 * Uso: php database/ferramentas/conferir_gatilhos.php
 * Sem eles (usuário do banco sem o privilégio TRIGGER na instalação), o sistema funciona, mas a
 * emissão fica protegida só pelo código. Peça ao provedor o privilégio TRIGGER e rode de novo o
 * a migration database/migrations/0003_gatilhos.sql: php bin/migrar.php aplicar (VCOSTAEDU).
 */
require __DIR__ . '/comum.php';

$esperados = ['rm_historico_emissoes_imutavel', 'rm_historico_emissoes_sem_apagar', 'rm_historico_emissao_vias_imutavel', 'rm_historico_emissao_vias_sem_apagar',
    'rm_media_fechamento_resultados_imutavel', 'rm_media_fechamento_resultados_sem_apagar'];
$pdo = vcostaedu_pdo();
$ha = $pdo->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND (TRIGGER_NAME LIKE 'rm\\_historico%' OR TRIGGER_NAME LIKE 'rm\\_media%')")->fetchAll(PDO::FETCH_COLUMN);
$faltam = array_values(array_diff($esperados, $ha));
foreach ($esperados as $g) {
    echo (in_array($g, $ha, true) ? '  [ok]    ' : '  [FALTA] ') . $g . "\n";
}
if ($faltam) {
    echo "\nATENÇÃO: " . count($faltam) . " gatilho(s) ausente(s). O histórico emitido e os fechamentos guardados estão protegidos só pelo código.\n";
    exit(1);
}
echo "\nOs 6 gatilhos estão no banco: emissões, vias e resultados de fechamento guardados não mudam de conteúdo nem são apagados.\n";
