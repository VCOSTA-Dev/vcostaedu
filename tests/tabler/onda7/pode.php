<?php
/*
 * Ajudante da prova da Onda 7, Etapa 5: pergunta ao PONTO ÚNICO (core/AcessoFamilia.php) no banco de PROVA.
 * Entrada (stdin): JSON, uma lista de perguntas [responsável, aluno, ano, "academico"|"financeiro", data?].
 * Saída: JSON, a lista das respostas (true/false), na mesma ordem; e, com "--anos r a" no lugar, os anos daquele par.
 * Ambiente: VCOSTAEDU_BANCO_TESTE (termina em _teste).
 */
$banco = (string) getenv('VCOSTAEDU_BANCO_TESTE');
if (!preg_match('/_teste$/', $banco)) {
    fwrite(STDERR, "VCOSTAEDU_BANCO_TESTE precisa terminar em _teste\n");
    exit(2);
}
require dirname(__DIR__, 3) . '/core/AcessoFamilia.php';
$pdo = new PDO('mysql:host=localhost;dbname=' . $banco . ';charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$a = new VcAcessoFamilia($pdo);
$perguntas = json_decode((string) stream_get_contents(STDIN), true) ?: [];
$saida = [];
foreach ($perguntas as $q) {
    if (($q[0] ?? '') === 'anos') {
        $saida[] = $a->anos((int) $q[1], (int) $q[2]);
    } elseif (($q[0] ?? '') === 'filhos') {
        $saida[] = array_map(static fn($f) => (int) $f['id'], $a->filhos((int) $q[1]));
    } elseif (($q[3] ?? '') === 'financeiro_direto') {
        $saida[] = $a->podeVerFinanceiro((int) $q[0], (int) $q[1], (int) $q[2], $q[4] ?? null);
    } else {
        $saida[] = $a->pode((int) $q[0], (int) $q[1], (int) $q[2], (string) $q[3], $q[4] ?? null);
    }
}
echo json_encode($saida);
