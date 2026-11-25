<?php
/*
 * Atualização 1.9.0 (E8d, A24): converte os MODELOS DE HISTÓRICO do formato antigo (seções) para o
 * formato do editor novo (um documento só, com blocos), como NOVA VERSÃO de cada modelo.
 *
 *   Conferir sem gravar: php database/ferramentas/converter_modelos_190.php --simular
 *   Converter:           php database/ferramentas/converter_modelos_190.php
 *
 * - Só a VERSÃO ATUAL de cada modelo é lida; ela e as versões anteriores NÃO mudam (as emissões apontam
 *   para elas e guardam o próprio HTML).
 * - O resultado é o mesmo documento: cada seção ativa vira o mesmo invólucro que o sistema montava
 *   (AdmsHistoricoEditor::converter).
 * - Pode rodar de novo: modelo cuja versão atual já está no formato novo é pulado.
 * - Roda depois do database/atualizacoes/1.9.0_*.sql (que já converte os modelos padrão nunca editados).
 */

require __DIR__ . '/comum.php';
require __DIR__ . '/../../painel/vendor/autoload.php';

use App\adms\Models\historico\AdmsHistoricoEditor as Editor;
use App\adms\Models\historico\AdmsJsonCanonico as Json;

$simular = in_array('--simular', $argv, true);
$pdo = vcostaedu_pdo();
$modelos = $pdo->query('SELECT id, tipo, nome, versao_atual FROM rm_historico_modelos ORDER BY id')->fetchAll();
$convertidos = 0;
foreach ($modelos as $m) {
    $st = $pdo->prepare('SELECT id, secoes FROM rm_historico_modelo_versoes WHERE modelo_id = ? AND versao = ?');
    $st->execute([$m['id'], $m['versao_atual']]);
    $v = $st->fetch();
    if (!$v) {
        echo "Modelo {$m['id']}: sem a versão atual (v{$m['versao_atual']}); pulado.\n";
        continue;
    }
    $atual = json_decode((string) $v['secoes'], true);
    if (!is_array($atual)) {
        echo "Modelo {$m['id']}: versão atual ilegível; pulado.\n";
        continue;
    }
    if (Editor::ehFormato2($atual)) {
        echo "Modelo {$m['id']} (v{$m['versao_atual']}): já no formato novo.\n";
        continue;
    }
    $novo = Json::codificar(Editor::converter($atual, (string) $m['tipo']));
    $proxima = (int) $pdo->query('SELECT MAX(versao) FROM rm_historico_modelo_versoes WHERE modelo_id = ' . (int) $m['id'])->fetchColumn() + 1;
    echo "Modelo {$m['id']} ({$m['tipo']}): v{$m['versao_atual']} (seções) → v{$proxima} (documento único)" . ($simular ? ' [simulação: nada gravado]' : '') . ".\n";
    if ($simular) {
        continue;
    }
    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO rm_historico_modelo_versoes (modelo_id, versao, secoes, usuario_cre, created) VALUES (?, ?, ?, NULL, NOW())')->execute([$m['id'], $proxima, $novo]);
    $pdo->prepare('UPDATE rm_historico_modelos SET versao_atual = ?, modified = NOW() WHERE id = ?')->execute([$proxima, $m['id']]);
    $pdo->commit();
    $convertidos++;
}
echo $simular ? "Simulação concluída.\n" : "{$convertidos} modelo(s) convertido(s).\n";
