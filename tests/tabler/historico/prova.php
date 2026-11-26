<?php
/*
 * O AJUDANTE DAS PROVAS DO HISTÓRICO ESCOLAR (D-110 em diante): cria, no banco de PROVA, os alunos fictícios dos casos
 * (nome "PROVA HIST …", documentos só com zeros e um dígito), todos marcados em esc_hist_dado_prova. Nada aqui passa
 * pelas telas: é a montagem do cenário; o que a prova confere passa pelas telas.
 *   php tests/tabler/historico/prova.php aluno <rm> "<nome>"          um aluno (cópia das colunas de um aluno da demonstração)
 *   php tests/tabler/historico/prova.php sem-historico-demo           tira o histórico da demonstração (D-115)
 *   php tests/tabler/historico/prova.php linha <tabela> <id>          o sha256 da linha (para conferir que não mudou)
 * Ambiente: VCOSTAEDU_BANCO_TESTE (termina em _teste).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$banco = (string) getenv('VCOSTAEDU_BANCO_TESTE');
if (!preg_match('/^[a-z0-9_]+_teste$/', $banco)) {
    fwrite(STDERR, "VCOSTAEDU_BANCO_TESTE precisa terminar em _teste\n");
    exit(2);
}
$pdo = new PDO("mysql:host=localhost;dbname={$banco};charset=utf8mb4", getenv('VCOSTAEDU_MYSQL_USUARIO') ?: 'root', getenv('VCOSTAEDU_MYSQL_SENHA') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$cmd = $argv[1] ?? '';

/** copia a linha `$id` de `$tabela` trocando os campos de `$troca`; devolve o id novo */
$copiar = static function (string $tabela, int $id, array $troca) use ($pdo): int {
    $l = $pdo->query("SELECT * FROM `{$tabela}` WHERE id = " . (int) $id)->fetch(PDO::FETCH_ASSOC);
    if (!$l) {
        throw new RuntimeException("{$tabela} {$id} não existe");
    }
    unset($l['id']);
    $l = array_merge($l, $troca);
    $cols = array_keys($l);
    $pdo->prepare("INSERT INTO `{$tabela}` (`" . implode('`, `', $cols) . '`) VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')')->execute(array_values($l));
    return (int) $pdo->lastInsertId();
};
$marcar = static function (string $tabela, int $id, string $rotulo) use ($pdo): void {
    $pdo->prepare('INSERT IGNORE INTO esc_hist_dado_prova (tabela, registro_id, rotulo) VALUES (?, ?, ?)')->execute([$tabela, $id, $rotulo]);
};

/* D-115: a demonstração do colégio traz o histórico (os cadastros, as matrizes, a vida escolar de três alunos); as provas
   das Partes A a E montam o próprio cenário pela tela, a partir do histórico vazio — este comando tira o da demonstração
   (no banco de prova; a emissão da demonstração fica, e as provas contam as emissões a partir do que já havia) */
if ($cmd === 'sem-historico-demo') {
    foreach (['esc_hist_vida_nota', 'esc_hist_vida_serie', 'esc_hist_aluno', 'esc_hist_matriz_item', 'esc_hist_itinerario', 'esc_hist_area', 'esc_hist_matriz',
        'esc_hist_serie', 'esc_hist_segmento', 'esc_hist_escola', 'esc_hist_texto', 'esc_hist_observacao', 'esc_hist_logo', 'esc_hist_disciplina', 'esc_hist_alteracao'] as $t) {
        $pdo->exec("DELETE FROM `{$t}`");
    }
    echo "ok
";
    exit(0);
}
/* D-116: it-pior <vida_serie_id> <n>: n disciplinas de itinerário (criadas na vida escolar, parte diversificada) com
   média e carga na série digitada — o pior caso do desenho do Médio */
if ($cmd === 'it-pior') {
    [$vs, $n] = [(int) ($argv[2] ?? 0), (int) ($argv[3] ?? 0)];
    for ($i = 1; $i <= $n; $i++) {
        $nome = "PROVA Itinerário {$i}";
        $d = (int) $pdo->query('SELECT id FROM esc_hist_disciplina WHERE materia_id = 0 AND nome = ' . $pdo->quote($nome))->fetchColumn();
        if (!$d) {
            $pdo->prepare("INSERT INTO esc_hist_disciplina (materia_id, nome, base) VALUES (0, ?, 'diversificada')")->execute([$nome]);
            $d = (int) $pdo->lastInsertId();
            $marcar('esc_hist_disciplina', $d, $nome);
        }
        $pdo->prepare("REPLACE INTO esc_hist_vida_nota (vida_serie_id, disciplina_id, media, aulas_anuais, origem, por, em) VALUES (?, ?, '7,5', 80, 'digitado', 0, NOW())")->execute([$vs, $d]);
    }
    echo "ok\n";
    exit(0);
}
if ($cmd === 'aluno') {
    $rm = (string) ($argv[2] ?? '');
    $nome = (string) ($argv[3] ?? '');
    if (!preg_match('/^\d{3,9}$/', $rm) || !str_starts_with($nome, 'PROVA HIST')) {
        fwrite(STDERR, "aluno <rm> \"PROVA HIST …\"\n");
        exit(2);
    }
    $modelo = (int) $pdo->query('SELECT MIN(id) FROM col_usuarios WHERE col_niveis_acesso_id = 1')->fetchColumn();
    $id = $copiar('col_usuarios', $modelo, ['rm' => $rm, 'nome' => $nome, 'usuario' => 'prova' . $rm, 'email' => 'prova' . $rm . '@exemplo.invalid', 'userid' => null, 'imagem' => null]);
    $inf = (int) $pdo->query('SELECT id FROM col_usuarios_informacoes WHERE col_user_id = ' . $modelo)->fetchColumn();
    if ($inf) {
        $copiar('col_usuarios_informacoes', $inf, ['col_user_id' => $id, 'cpf' => null, 'rg' => '00.000.00' . substr($rm, -1) . '-' . substr($rm, -1), 'rg_escolar' => null]);
    }
    $marcar('col_usuarios', $id, $nome);
    echo json_encode(['id' => $id]);
    exit(0);
}
/*
 * clonar <rm_origem> <rm_novo> "PROVA HIST …": um aluno de prova com a mesma vida acadêmica de um aluno da demonstração
 * (as matrículas, as turmas, as enturmações, as notas das atividades, os fechamentos, as recuperações, as faltas, o
 * conselho, as notas de transferência): o cálculo da plataforma dá para ele exatamente o que dá para o original.
 */
if ($cmd === 'clonar') {
    [$rmO, $rm, $nome] = [(string) ($argv[2] ?? ''), (string) ($argv[3] ?? ''), (string) ($argv[4] ?? '')];
    if (!preg_match('/^\d{3,9}$/', $rm) || !str_starts_with($nome, 'PROVA HIST')) {
        fwrite(STDERR, "clonar <rm_origem> <rm_novo> \"PROVA HIST …\"\n");
        exit(2);
    }
    $orig = (int) $pdo->query('SELECT id FROM col_usuarios WHERE rm = ' . $pdo->quote($rmO))->fetchColumn();
    if (!$orig) {
        fwrite(STDERR, "o aluno {$rmO} não existe\n");
        exit(2);
    }
    $pdo->beginTransaction();
    $id = $copiar('col_usuarios', $orig, ['rm' => $rm, 'nome' => $nome, 'usuario' => 'prova' . $rm, 'email' => 'prova' . $rm . '@exemplo.invalid', 'userid' => null, 'imagem' => null]);
    $inf = (int) $pdo->query('SELECT id FROM col_usuarios_informacoes WHERE col_user_id = ' . $orig)->fetchColumn();
    if ($inf) {
        $copiar('col_usuarios_informacoes', $inf, ['col_user_id' => $id, 'cpf' => null, 'rg' => '00.000.00' . substr($rm, -1) . '-' . substr($rm, -1), 'rg_escolar' => null]);
    }
    $mcs = [];
    foreach ($pdo->query('SELECT id FROM rm_matriculas_curso WHERE usuario_id = ' . $orig)->fetchAll(PDO::FETCH_COLUMN) as $mc) {
        $mcs[(int) $mc] = $copiar('rm_matriculas_curso', (int) $mc, ['usuario_id' => $id]);
    }
    foreach ($pdo->query('SELECT id, matricula_curso_id FROM rm_matriculas_ensino WHERE usuario_id = ' . $orig)->fetchAll(PDO::FETCH_ASSOC) as $me) {
        $copiar('rm_matriculas_ensino', (int) $me['id'], ['usuario_id' => $id, 'matricula_curso_id' => $mcs[(int) $me['matricula_curso_id']] ?? null]);
    }
    foreach ($pdo->query('SELECT id FROM esc_turma_alunos WHERE usuario_id = ' . $orig)->fetchAll(PDO::FETCH_COLUMN) as $x) {
        $copiar('esc_turma_alunos', (int) $x, ['usuario_id' => $id, 'rm' => $rm]);
    }
    /* as tabelas ligadas pelo número do aluno */
    foreach (['portal_disciplinas_alunos', 'rm_disciplina_atividade_notas', 'esc_fechamento_notas', 'esc_recuperacoes', 'rm_disciplina_faltas', 'esc_faltas_etapa', 'esc_conselho',
        'esc_notas_transferencia', 'rm_disciplina_media_final', 'esc_lancamentos_trazidos'] as $t) {
        $temAluno = (bool) $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '{$t}' AND column_name = 'aluno_id'")->fetchColumn();
        $temNome = (bool) $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '{$t}' AND column_name = 'nome_aluno'")->fetchColumn();
        foreach ($pdo->query("SELECT * FROM `{$t}` WHERE rm = " . $pdo->quote($rmO))->fetchAll(PDO::FETCH_ASSOC) as $l) {
            unset($l['id']);
            $l = array_merge($l, ['rm' => $rm] + ($temAluno ? ['aluno_id' => $id] : []) + ($temNome ? ['nome_aluno' => $nome] : []));
            $pdo->prepare("INSERT INTO `{$t}` (`" . implode('`, `', array_keys($l)) . '`) VALUES (' . implode(', ', array_fill(0, count($l), '?')) . ')')->execute(array_values($l));
        }
    }
    $pdo->commit();
    $marcar('col_usuarios', $id, $nome);
    echo json_encode(['id' => $id]);
    exit(0);
}
/*
 * serie <aluno_id> <EF|EM> <serie> <ano> <escola_id> <resultado> [média…]: uma série digitada (ano cursado em outra
 * escola), com as médias das disciplinas da grade do nível (a ordem da grade), como a tela grava — para montar depressa
 * os cenários longos (o pior caso do desenho). A série fica marcada como dado de prova.
 */
if ($cmd === 'serie') {
    [$aluno, $nivel, $serie, $ano, $escola, $res] = [(int) $argv[2], (string) $argv[3], (int) $argv[4], (int) $argv[5], (int) $argv[6], (string) $argv[7]];
    $medias = array_slice($argv, 8);
    $pdo->prepare("INSERT INTO esc_hist_vida_serie (aluno_id, nivel, serie, ano, escola_id, resultado, turma_id, origem, por, em) VALUES (?, ?, ?, ?, ?, ?, 0, 'digitado', 0, NOW())")
        ->execute([$aluno, $nivel, $serie, $ano, $escola, $res]);
    $vs = (int) $pdo->lastInsertId();
    $mats = $pdo->query("SELECT l.materia_id, m.nome, MIN(l.ordem) AS o FROM uni_rh_cursos_disciplinas_grade l JOIN rm_materias m ON m.id = l.materia_id JOIN rm_cursos_grades g ON g.id = l.grade_id
        JOIN esc_hist_segmento s ON s.curso_id = g.curso_id AND s.nivel = " . $pdo->quote($nivel) . " WHERE m.nome NOT LIKE '%eligios%' GROUP BY l.materia_id, m.nome ORDER BY o, l.materia_id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($mats as $i => $m) {
        if (!isset($medias[$i])) {
            break;
        }
        $d = (int) $pdo->query('SELECT id FROM esc_hist_disciplina WHERE materia_id = ' . (int) $m['materia_id'] . ' ORDER BY id LIMIT 1')->fetchColumn();
        if (!$d) {
            $pdo->prepare("INSERT INTO esc_hist_disciplina (materia_id, nome, base) VALUES (?, ?, 'comum')")->execute([(int) $m['materia_id'], $m['nome']]);
            $d = (int) $pdo->lastInsertId();
        }
        $pdo->prepare("INSERT INTO esc_hist_vida_nota (vida_serie_id, disciplina_id, media, aulas_anuais, origem, por, em) VALUES (?, ?, ?, 0, 'digitado', 0, NOW())")->execute([$vs, $d, $medias[$i]]);
    }
    $marcar('esc_hist_vida_serie', $vs, 'série de prova');
    echo json_encode(['id' => $vs]);
    exit(0);
}
if ($cmd === 'linha') {
    $t = preg_replace('/[^a-z0-9_]/', '', (string) ($argv[2] ?? ''));
    $l = $pdo->query("SELECT * FROM `{$t}` WHERE id = " . (int) ($argv[3] ?? 0))->fetch(PDO::FETCH_ASSOC);
    echo hash('sha256', json_encode($l));
    exit(0);
}
fwrite(STDERR, "comando desconhecido\n");
exit(2);
