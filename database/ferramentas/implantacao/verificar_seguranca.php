<?php
/*
 * AS VERIFICAÇÕES DE SEGURANÇA pela linha de comando (Onda 3, Etapa 6) — a mesma implementação da tela Manutenção →
 * Verificações de segurança (core/Verificacoes.php). Roda no servidor, na pasta do sistema.
 *
 * Uso: php database/ferramentas/implantacao/verificar_seguranca.php
 * Saída: OK / ALERTA / FALHA, item a item, com o que fazer; código 1 se houver FALHA.
 * (Na linha de comando, o HTTPS é julgado pela configuração: a requisição não existe aqui.)
 */
require __DIR__ . '/_base.php';
require __DIR__ . '/../comum.php';
require_once dirname(__DIR__, 3) . '/core/Verificacoes.php';

Saida::secao('Verificações de segurança');
try {
    $pdo = vcostaedu_pdo();
} catch (Throwable $e) {
    Saida::falha('não conectou ao banco: ' . $e->getMessage());
    Saida::fim();
}
$https = defined('EXIGIR_HTTPS') && EXIGIR_HTTPS;
foreach (VcVerificacoes::todas($pdo, dirname(__DIR__, 3), $https) as $i) {
    $texto = "{$i['titulo']}: {$i['detalhe']}" . ($i['fazer'] !== '' ? " O que fazer: {$i['fazer']}" : '');
    $i['situacao'] === 'OK' ? Saida::ok($texto) : ($i['situacao'] === 'ALERTA' ? Saida::alerta($texto) : Saida::falha($texto));
}
Saida::fim();
