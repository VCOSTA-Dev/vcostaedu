<?php
/*
 * A DEMONSTRAÇÃO PELA TELA (Onda 2, Etapa 4) — montar e limpar, pelas ferramentas que existem e com as travas delas.
 *
 *   php database/ferramentas/demonstracao.php montar <URL base>   (lê o usuário e a senha do Administrador da entrada)
 *   php database/ferramentas/demonstracao.php limpar
 *
 * montar: recusa se já há dado acadêmico (curso ou aluno que não é da demonstração); liga a demonstração
 *         (adms_instalacao_estado "demonstracao.ativa" = 1 — é o que libera as travas de ambiente e de banco das
 *         ferramentas, sem editar a configuração); roda o limpar_demo.php e o seed_demo_processar.php (a demonstração
 *         montada PELAS TELAS); guarda as senhas fictícias para o cartão "Conheça o sistema".
 * limpar: só com a demonstração ligada; roda o limpar_demo.php (recusa se houver emissão real ou a instituição
 *         configurada pela tela, dizendo o quê); desliga a demonstração e apaga as senhas fictícias.
 * A saída: mensagens em português; a última linha é "OK" ou "RECUSADO: …" / "FALHA: …". Só linha de comando.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/comum.php';

$acao = $argv[1] ?? '';
$pdo = vcostaedu_pdo();
$php = escapeshellarg(PHP_BINARY);
$estado = static function (string $chave, ?string $valor) use ($pdo): void {
    if ($valor === null) {
        $pdo->prepare('DELETE FROM adms_instalacao_estado WHERE chave = ?')->execute([$chave]);
    } else {
        $pdo->prepare('INSERT INTO adms_instalacao_estado (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)')->execute([$chave, $valor]);
    }
};
$rodar = static function (string $cmd, string $entrada = '') : array {
    $p = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pp, dirname(__DIR__, 2));
    fwrite($pp[0], $entrada);
    fclose($pp[0]);
    $saida = stream_get_contents($pp[1]);
    $erro = stream_get_contents($pp[2]);
    fclose($pp[1]);
    fclose($pp[2]);
    return [proc_close($p), $saida, $erro];
};

if ((int) $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'adms_instalacao_estado'")->fetchColumn() !== 1) {
    echo "RECUSADO: a atualização do banco está pendente (migration 0008). Rode php bin/migrar.php aplicar.\n";
    exit(2);
}

if ($acao === 'montar') {
    $base = rtrim((string) ($argv[2] ?? ''), '/') . '/';
    if (!preg_match('#^https?://#', $base)) {
        echo "RECUSADO: informe o endereço do sistema.\n";
        exit(2);
    }
    $usuario = trim((string) fgets(STDIN));
    $senha = rtrim((string) fgets(STDIN), "\r\n");
    /* dado acadêmico que não é da demonstração: a demonstração se misturaria a ele */
    $cursos = (int) $pdo->query("SELECT COUNT(*) FROM rm_cursos WHERE nome NOT IN ('Pós-graduação em Gestão de Projetos', 'Pós-graduação em Docência no Ensino Superior', 'Graduação Fictícia de Demonstração', 'Especialização em Gestão Pública Exemplar', 'Especialização em Dados Públicos Exemplar', 'Ensino Fundamental — Anos Finais (demonstração)', 'Ensino Médio (demonstração)')")->fetchColumn();
    $alunos = (int) $pdo->query("SELECT COUNT(*) FROM col_usuarios WHERE col_niveis_acesso_id = 1 AND rm NOT BETWEEN 250000 AND 269999")->fetchColumn();
    if ($cursos > 0 || $alunos > 0) {
        echo "RECUSADO: esta instalação já tem dados da instituição ({$cursos} curso(s) e {$alunos} aluno(s)). A demonstração só é montada numa instalação ainda vazia.\n";
        exit(3);
    }
    $estado('demonstracao.ativa', '1');
    [$rc, $saida, $erro] = $rodar("{$php} " . escapeshellarg(__DIR__ . '/limpar_demo.php'));
    if ($rc !== 0) {
        $estado('demonstracao.ativa', null);
        echo trim($erro !== '' ? $erro : $saida) . "\nRECUSADO: a demonstração não foi montada.\n";
        exit(4);
    }
    [$rc, $saida, $erro] = $rodar("{$php} " . escapeshellarg(__DIR__ . '/seed_demo_processar.php') . ' ' . escapeshellarg($base), "{$usuario}\n{$senha}\n");
    if ($rc !== 0) {
        echo trim($erro) . "\nFALHA: a montagem parou no meio. A demonstração ficou ligada: use \"Começar com os dados da minha instituição\" para limpar o que foi criado.\n";
        exit(5);
    }
    $cred = ['painel' => [], 'portal' => []];
    foreach (preg_split('/\R/', $saida) as $l) {
        if (preg_match('/^\s+painel\s+(\S+)\s+(\S+)\s*$/', $l, $m)) {
            $cred['painel'][$m[1]] = $m[2];
        } elseif (preg_match('/^\s+portal\s+RA\s+(\d+)\s+.*?\s{2,}(\S{8,})\s*$/', $l, $m)) {
            $cred['portal'][$m[1]] = $m[2];
        } elseif (preg_match('/^\s+familia\s+(\S+@\S+)\s+(\S{8,})\s*$/', $l, $m)) { // Onda 7
            $cred['familia'][$m[1]] = $m[2];
        }
    }
    $estado('demonstracao.credenciais', json_encode($cred, JSON_UNESCAPED_UNICODE));
    $estado('comeco.escolhido', 'demonstracao');
    echo "A demonstração foi montada: " . count($cred['painel']) . " usuário(s) da equipe e " . count($cred['portal']) . " aluno(s) fictícios.\nOK\n";
    exit(0);
}

if ($acao === 'limpar') {
    if (!vcostaedu_demonstracao_ativa($pdo)) {
        echo "RECUSADO: a demonstração não está ligada.\n";
        exit(2);
    }
    [$rc, $saida, $erro] = $rodar("{$php} " . escapeshellarg(__DIR__ . '/limpar_demo.php'));
    if ($rc !== 0) {
        echo trim($erro !== '' ? $erro : $saida) . "\nRECUSADO: a demonstração não foi removida.\n";
        exit(3);
    }
    /* os anos letivos que a demonstração criou e que nada mais usa (nenhuma disciplina ofertada nem enturmação no ano):
       sem isso, a fase "o primeiro ano letivo" começaria concluída pelo ano fictício */
    $anos = $pdo->exec('DELETE p FROM adms_periodos_letivos p
        WHERE NOT EXISTS (SELECT 1 FROM rm_professores_materias m WHERE m.ano = p.ano)
          AND NOT EXISTS (SELECT 1 FROM portal_disciplinas_alunos d WHERE d.ano = p.ano)');
    if ($anos) {
        echo "{$anos} ano(s) letivo(s) da demonstração sem uso removido(s).
";
    }
    $estado('demonstracao.ativa', null);
    $estado('demonstracao.credenciais', null);
    $estado('comeco.escolhido', 'instituicao');
    echo "A demonstração foi removida. A instalação está pronta para os dados da instituição.\nOK\n";
    exit(0);
}

echo "Uso: php database/ferramentas/demonstracao.php montar <URL base> | limpar\n";
exit(2);
