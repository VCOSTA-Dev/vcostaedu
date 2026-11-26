<?php
/*
 * A BATERIA: todas as provas por HTTP de tests/tabler (as da primeira onda e as da onda 2), em sequência, sobre a
 * instalação atual (instalada e com a demonstração — rode antes o ciclo.php). Imprime uma linha de resumo por prova
 * e termina com código 1 se alguma falhou.
 *
 *   php tests/tabler/bateria.php [--sem-iframe] [--so=<prefixo>]
 *
 * A prova do iframe pede ORIGENS_PERMITIDAS_IFRAME no portal/core/Config.php local: a bateria põe o valor, roda e
 * devolve o arquivo como estava. Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE, NODE_PATH.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$aqui = __DIR__;
$raiz = dirname(__DIR__, 2);
$opcoes = array_slice($argv, 1);
$semIframe = in_array('--sem-iframe', $opcoes, true);
$so = '';
foreach ($opcoes as $o) {
    if (str_starts_with($o, '--so=')) {
        $so = substr($o, 5);
    }
}
$php = escapeshellarg(PHP_BINARY);
$node = 'node';
$provas = [];
foreach (glob("{$aqui}/telas/*.json") as $lista) {
    $e = str_replace('onda2-', 'onda2/', basename($lista, '.json')); // as da onda 2 ficam em provas/tabler/onda2/<etapa>/
    $provas["telas {$e}"] = "{$node} " . escapeshellarg("{$aqui}/telas.js") . ' ' . escapeshellarg($lista) . " {$e} depois";
}
foreach (glob("{$aqui}/comportamento/*.json") as $lista) {
    $e = str_replace('onda2-', 'onda2/', basename($lista, '.json'));
    $provas["comportamento {$e}"] = "{$node} " . escapeshellarg("{$aqui}/comportamento.js") . ' ' . escapeshellarg($lista) . " {$e}";
}
$provas['menu'] = "{$node} " . escapeshellarg("{$aqui}/menu.js");
$provas['sessão'] = "{$node} " . escapeshellarg("{$aqui}/sessao.js");
$provas['fumaça'] = "{$php} " . escapeshellarg("{$aqui}/fumaca.php");
foreach (['2', '3', '4', '6', '7', '8'] as $onda) { // Onda 3, Etapa 1: as provas de cada onda, na ordem (a 4 desde a Onda 4; a 6 desde a Onda 6; a 7 desde a Onda 7)
    foreach (glob("{$aqui}/onda{$onda}/etapa*.js") as $js) {
        $provas["onda {$onda} " . basename($js, '.js')] = "{$node} " . escapeshellarg($js);
    }
}
/* O histórico escolar da educação básica (D-110 em diante): uma prova por parte, cada uma monta a demonstração do colégio */
foreach (glob("{$aqui}/historico/parte*.js") as $js) {
    $provas['historico ' . basename($js, '.js')] = "{$node} " . escapeshellarg($js);
}
/* Onda 6, Etapa 10: a 1.42.0 atualizada pela tela, também numa escola (a mesma prova, com o tipo) */
$provas['onda 6 etapa10 escola'] = "{$node} " . escapeshellarg("{$aqui}/onda6/etapa10.js") . ' escola';
/* Onda 7, Etapa 7: a 1.50.0 atualizada pela tela, também numa escola */
$provas['onda 7 etapa7 escola'] = "{$node} " . escapeshellarg("{$aqui}/onda7/etapa7.js") . ' escola';
if (!$semIframe) {
    $provas['iframe'] = "{$node} " . escapeshellarg("{$aqui}/iframe.js");
}
/* Onda 4, Etapa 6: a escola do zero (a instalação de escola, a demonstração do colégio, a régua da escola e a fumaça) */
$provas['onda 4 escola do zero'] = "{$php} " . escapeshellarg("{$aqui}/onda4/escola_do_zero.php");
/* As jornadas do zero (apagam e reinstalam o banco de prova): por último. Depois delas, rode o ciclo de novo. */
foreach (['2', '3', '4'] as $onda) {
    foreach (glob("{$aqui}/onda{$onda}/jornada*.js") as $js) {
        $provas["onda {$onda} " . basename($js, '.js')] = "{$node} " . escapeshellarg($js);
    }
}

$cfg = "{$raiz}/portal/core/Config.php";
/* Onda 4, Etapa 6: as provas da onda 4 (e o iframe, que vem depois delas) comparam o banco que encontram com a
   demonstração do ensino superior; as provas anteriores mudam dados e a Etapa 1 deixa o banco como escola. Antes de
   cada uma, o ciclo (o banco vazio, o instalador, a demonstração e a régua contra a da 1.37.0). */
$cicloCmd = "{$php} " . escapeshellarg("{$aqui}/ciclo.php") . ' comparar ' . escapeshellarg("{$aqui}/saida/regua-onda3-etapa11.json")
    . ' --config=adms_versoes,adms_novidades,adms_paginas,adms_nivacs_pgs,adms_acoes,adms_instalacao_estado,adms_paginas_modulo,adms_fases,adms_paginas_recurso,col_paginas_modulo,'
    . 'rm_media_regras,rm_media_regra_versoes,rm_media_regra_componentes,rm_media_regra_acrescimo,adms_parametros,col_niveis_acessos,col_paginas,col_nivacs_pgs';
    /* Onda 8, fechamento da correção (D-86): os turnos e o horário de aulas saíram desta lista (a D-71 os tinha posto): a
       grafia "Vespertino" passa pela decisão registrada (tests/tabler/regua_decisoes.json), linha a linha, e o horário
       padrão não nasce no ensino superior — a régua do superior compara as duas tabelas inteiras */
$comCiclo = static fn(string $nome): bool => str_starts_with($nome, 'onda 4 etapa') || str_starts_with($nome, 'onda 6 etapa') || str_starts_with($nome, 'onda 7 etapa') || str_starts_with($nome, 'onda 8 etapa') || $nome === 'iframe';
$falhou = [];
/* Onda 8, fechamento da correção: a conferência do grupo. As provas escolhidas vão para a saída antes da primeira;
   no fim, cada uma tem de ter dado resultado (ok ou FALHOU): a que não deu, a bateria nomeia e reprova. Um processo
   morto de fora (o php inteiro) não chega ao fim: quem chama confere a linha "BATERIA:" final e esta lista. */
/* correção da escola (D-100): --so=a|b|c escolhe várias; o nome que termina em "$" é exato (sem ele, o começo do nome) */
$escolhe = static function (string $nome) use ($so): bool {
    if ($so === '') {
        return true;
    }
    foreach (explode('|', $so) as $s) {
        if (str_ends_with($s, '$') ? $nome === substr($s, 0, -1) : str_starts_with($nome, $s)) {
            return true;
        }
    }
    return false;
};
$escolhidas = array_values(array_filter(array_keys($provas), static fn($n) => $escolhe((string) $n)));
echo 'BATERIA: ' . count($escolhidas) . ' prova(s): ' . implode(', ', $escolhidas) . "\n";
$comResultado = [];
register_shutdown_function(static function () use (&$escolhidas, &$comResultado) {
    $faltaram = array_values(array_diff($escolhidas, $comResultado));
    if ($faltaram !== []) {
        echo "\nBATERIA: faltaram resultados de: " . implode(', ', $faltaram) . "\n";
    }
});
foreach ($provas as $nome => $cmd) {
    if (!$escolhe((string) $nome)) {
        continue;
    }
    $original = null;
    if ($comCiclo($nome)) { // antes de trocar a Config: o instalador do ciclo a grava de novo
        $saidaCiclo = [];
        exec($cicloCmd . ' 2>&1', $saidaCiclo, $rcCiclo);
        if ($rcCiclo !== 0) {
            printf("%-26s FALHOU (o ciclo antes dela)\n", $nome);
            $falhou[] = $nome;
            $comResultado[] = $nome;
            continue;
        }
    }
    if ($nome === 'iframe') {
        $original = (string) file_get_contents($cfg);
        file_put_contents($cfg, preg_replace("/define\\('ORIGENS_PERMITIDAS_IFRAME', '[^']*'\\);/", "define('ORIGENS_PERMITIDAS_IFRAME', 'https://127.0.0.1:9443');", $original));
    }
    $t = microtime(true);
    $saida = [];
    exec($cmd . ' 2>&1', $saida, $rc);
    if ($original !== null) {
        file_put_contents($cfg, $original);
    }
    $resumo = '';
    foreach (array_reverse($saida) as $l) {
        if (preg_match('/\d+ OK, (\d+ ALERTA, )?\d+ FALHA/', $l)) {
            $resumo = trim($l);
            break;
        }
    }
    printf("%-26s %s (%.0f s)%s\n", $nome, $rc === 0 ? 'ok' : 'FALHOU', microtime(true) - $t, $resumo !== '' ? '  ' . $resumo : '');
    $comResultado[] = $nome;
    if ($rc !== 0) {
        $falhou[] = $nome;
        $mostrou = false;
        foreach ($saida as $l) {
            if (str_contains($l, '[FALHA]') || str_contains($l, 'Error')) {
                echo '    ' . trim($l) . "\n";
                $mostrou = true;
            }
        }
        if (!$mostrou) { // a prova parou sem linha [FALHA] (antes do resumo): as últimas linhas dela
            echo "    (sem linha [FALHA]; as últimas linhas da saída, código {$rc}:)\n";
            foreach (array_slice($saida, -12) as $l) {
                echo '    | ' . rtrim($l) . "\n";
            }
        }
    }
}
if (array_diff($escolhidas, $comResultado) !== []) {
    exit(1); // a mensagem sai pela função de encerramento
}
echo $falhou === [] ? "\nBATERIA: tudo verde.\n" : "\nBATERIA: falhou em " . implode(', ', $falhou) . "\n";
exit($falhou === [] ? 0 : 1);
