<?php
/*
 * ENVIAR AO MOODLE O QUE FALTA, pela linha de comando (Onda 3, Etapa 8) — para quem quiser agendar o reenvio (não é
 * obrigatório: a tela "Envios ao Moodle" faz o mesmo). É o mesmo lote da tela (AdmsMoodleLote): confere o que já
 * existia, envia a fila em passos e para se o Moodle não responder (o que falta fica para a próxima vez).
 *
 * Uso: php database/ferramentas/moodle_enviar.php
 * Saída: o resumo; código 1 se o lote parou (o Moodle fora do ar, a chave recusada).
 */
require dirname(__DIR__, 2) . '/bin/bootstrap-cli.php';
require dirname(__DIR__, 2) . '/painel/vendor/autoload.php';

use App\adms\Models\moodle\AdmsMoodle as M;
use App\adms\Models\moodle\AdmsMoodleLote as Lote;

if (!M::disponivel()) {
    fwrite(STDERR, "A integração com o Moodle ainda não está no banco: aplique a atualização do sistema primeiro.\n");
    exit(1);
}
if (!M::conectado()) {
    echo "O Moodle não está conectado (Integrações → Conectar ao Moodle). Nada a enviar.\n";
    exit(0);
}
$l = new Lote();
[$ok, $msg] = $l->iniciar();
if (!$ok) {
    fwrite(STDERR, $msg . "\n");
    exit(1);
}
do {
    $r = $l->passo();
    if (!empty($r['ocupado'])) {
        echo "Outro envio está em andamento (na tela ou noutro comando). Tente mais tarde.\n";
        exit(0);
    }
    if (!empty($r['erro'])) {
        fwrite(STDERR, $r['erro'] . "\n");
        exit(1);
    }
    echo '  ' . (int) $r['passo'] . ' de ' . (int) $r['total'] . "\n";
} while (empty($r['concluida']) && empty($r['falhou']));
if (!empty($r['falhou'])) {
    fwrite(STDERR, 'O envio parou: ' . $r['falhou']['motivo'] . "\n");
    exit(1);
}
$l->limparConcluido();
echo 'Pronto: ' . (int) $r['enviados'] . ' enviado(s), ' . (int) $r['recusados'] . " recusado(s) (o motivo de cada um está em Envios ao Moodle).\n";
