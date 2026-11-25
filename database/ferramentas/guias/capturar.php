<?php
/*
 * Captura as telas da DEMONSTRAÇÃO para os guias (docs/guia/img/<perfil>_*.png).
 *
 * Depois de seed_demo.sql + seed_demo_processar.php. Para entrar como cada perfil sem
 * conhecer as senhas da demonstração, troca por um instante o hash do administrador, da
 * secretaria, da professora e de um aluno por uma senha aleatória EM MEMÓRIA, roda o
 * navegador (Playwright + Chromium do Playwright; E8) e RESTAURA os hashes originais no fim, mesmo
 * com erro. Nenhuma senha ou token vai para arquivo. O link de acesso mostrado na tela
 * da secretaria é mascarado antes do print e invalidado no fim.
 *
 * Uso:
 *   npm i --prefix <pasta> playwright-core && npx --prefix <pasta> playwright-core install chromium   (uma vez)
 *   (VCOSTAEDU_CANAL=chrome usa o Chrome instalado no lugar do Chromium do Playwright)
 *   php database/ferramentas/guias/capturar.php http://localhost/vcostaedu/ <pasta>/node_modules
 */

require dirname(__DIR__) . '/comum.php';

$base = rtrim($argv[1] ?? '', '/') . '/';
$nodeModules = $argv[2] ?? '';
if (!preg_match('#^https?://#', $base) || !is_dir($nodeModules . '/playwright-core')) {
    fwrite(STDERR, "Uso: php database/ferramentas/guias/capturar.php <URL base> <pasta node_modules com playwright-core>\n");
    exit(1);
}
$pdo = vcostaedu_pdo();
$um = function (string $sql, array $p = []) use ($pdo) { $s = $pdo->prepare($sql); $s->execute($p); $v = $s->fetchColumn(); return $v === false ? null : $v; };

$curso = (int) $um("SELECT id FROM rm_cursos WHERE nome = 'Pós-graduação em Gestão de Projetos'") ?: exit("Rode a demonstração antes.\n");
$ids = ['curso' => $curso, 'grade' => (int) $um('SELECT id FROM rm_cursos_grades WHERE curso_id = ?', [$curso]),
        'polo' => (int) $um("SELECT id FROM rm_polos WHERE nome = 'Polo Central Fictício'")];
foreach (['FAP' => 'Fundamentos de Gestão de Projetos', 'MPA' => 'Métodos Ágeis', 'GPS' => 'Gestão de Riscos',
          'OFP' => 'Planejamento e Cronograma', 'GOC' => 'Liderança de Equipes', 'STF' => 'Trabalho de Conclusão de Curso'] as $k => $nome) {
    $ids['oferta'][$k] = (int) $um('SELECT id FROM rm_professores_materias WHERE curso_id = ? AND materia = ?', [$curso, $nome]);
    $ids['atividade'][$k] = array_map('intval', $pdo->query("SELECT id FROM rm_disciplina_atividade WHERE rm_prof_mat_id = {$ids['oferta'][$k]} ORDER BY data_inicial, id")->fetchAll(PDO::FETCH_COLUMN));
}
foreach ([260101, 260103, 260104, 260107, 260108, 250101, 250102] as $ra) {
    $ids['aluno'][$ra] = (int) $um('SELECT id FROM col_usuarios WHERE rm = ?', [$ra]);
    $ids['matricula'][$ra] = (int) $um('SELECT mc.id FROM rm_matriculas_curso mc WHERE mc.usuario_id = ? ORDER BY mc.id LIMIT 1', [$ids['aluno'][$ra]]);
}
/* E8: as emissões da demonstração (FINAL da Irene, PARCIAL do Diego) para os prints do histórico */
$ids['emissao']['final'] = (int) $um("SELECT id FROM rm_historico_emissoes WHERE tipo = 'FINAL' ORDER BY id LIMIT 1");
$ids['emissao']['parcial'] = (int) $um("SELECT id FROM rm_historico_emissoes WHERE tipo = 'PARCIAL' AND aluno_id = ? ORDER BY id LIMIT 1", [$ids['aluno'][260104]]);
$ids['emissao']['codigo_final'] = (string) $um('SELECT codigo FROM rm_historico_emissoes WHERE id = ?', [$ids['emissao']['final']]);

$contas = ['administrador' => 'adms', 'secretaria.demo' => 'adms', 'prof.laura.demo' => 'adms', '260104' => 'col'];
$originais = [];
$senhas = [];
try {
    foreach ($contas as $login => $tab) {
        $sqlLer = $tab === 'adms' ? 'SELECT senha FROM adms_usuarios WHERE usuario = ?' : 'SELECT senha FROM col_usuarios WHERE rm = ?';
        $originais[$login] = $um($sqlLer, [$login]) ?? exit("Conta {$login} não existe.\n");
        $senhas[$login] = vcostaedu_senha_aleatoria();
        $sqlGravar = $tab === 'adms' ? 'UPDATE adms_usuarios SET senha = ? WHERE usuario = ?' : 'UPDATE col_usuarios SET senha = ? WHERE rm = ?';
        $pdo->prepare($sqlGravar)->execute([password_hash($senhas[$login], PASSWORD_DEFAULT), $login]);
    }
    $env = getenv();
    $env['NODE_PATH'] = realpath($nodeModules);
    $env['VCOSTAEDU_BASE'] = $base;
    $env['VCOSTAEDU_SAIDA'] = realpath(dirname(__DIR__, 3) . '/docs/guia/img');
    $env['VCOSTAEDU_IDS'] = json_encode($ids);
    $env['VCOSTAEDU_CONTAS'] = json_encode($senhas); // só no ambiente do processo filho
    $proc = proc_open(['node', __DIR__ . '/capturar.js'], [1 => STDOUT, 2 => STDERR], $pipes, null, $env);
    $rc = is_resource($proc) ? proc_close($proc) : 1;
} finally {
    foreach ($originais as $login => $hash) {
        $sql = $contas[$login] === 'adms' ? 'UPDATE adms_usuarios SET senha = ? WHERE usuario = ?' : 'UPDATE col_usuarios SET senha = ? WHERE rm = ?';
        $pdo->prepare($sql)->execute([$hash, $login]);
    }
    /* o link emitido para o aluno sem senha (print da secretaria e do primeiro acesso) deixa de valer */
    $pdo->prepare('UPDATE col_usuarios_tokens SET invalidado_em = NOW() WHERE col_usuario_id = ? AND usado_em IS NULL AND invalidado_em IS NULL')
        ->execute([$ids['aluno'][260108] ?? 0]);
    /* E8: as falhas de login provocadas para o print da tela Tentativas de acesso não ficam */
    $pdo->exec("DELETE FROM seg_tentativas");
    $pdo->exec("DELETE FROM col_tentativas_acesso WHERE observacao = 'login_painel'");
    echo "Senhas originais restauradas; links abertos do aluno sem senha invalidados; contagens de tentativas apagadas.\n";
}
exit($rc ?? 1);
