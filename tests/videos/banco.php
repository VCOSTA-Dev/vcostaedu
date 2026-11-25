<?php
/*
 * O lado do BANCO do gravador dos vídeos dos guias (1.18.0). Chamado por gravar.js; só linha de comando.
 *
 * Carrega a Config da RAIZ servida (VCOSTAEDU_VIDEOS_RAIZ: o worktree da demonstração), pela própria ferramenta comum
 * dessa raiz, e SÓ age se a GUARDA passar:
 *   - a instituição do banco em uso é a fictícia da demonstração (o nome);
 *   - AMBIENTE = 'demonstracao' e BANCO_DEMONSTRACAO declarado igual ao DBNAME (o banco separado, D-471);
 *   - nenhuma emissão fora da demonstração;
 *   - a URL a gravar (VCOSTAEDU_VIDEOS_BASE) aponta para essa mesma raiz (o último trecho do caminho é o nome da pasta).
 * Falhando qualquer uma, sai com o código 9 ANTES de qualquer coisa (nenhuma tela é aberta, nada é gravado).
 *
 * Ações:
 *   guarda                 confere e devolve {instituicao, versao, banco} em JSON
 *   retrato <arquivo>      mysqldump do banco da demonstração e a lista dos arquivos enviados (storage, logos, fotos)
 *   restaurar <arquivo>    volta o banco ao retrato e apaga os arquivos que surgiram depois dele
 *   senhas <json>          troca por um instante a senha das contas pedidas (["admin.demo", "260104"]) por senhas
 *                          aleatórias e devolve-as em JSON; o retrato tirado ANTES devolve os hashes originais
 *   ids                    os ids da demonstração que os roteiros usam (curso, ofertas, alunos, matrículas, emissões)
 *   sql <arquivo>          roda o SQL de preparação de um roteiro (só depois do retrato)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$raiz = rtrim(str_replace('\\', '/', (string) getenv('VCOSTAEDU_VIDEOS_RAIZ')), '/');
$base = rtrim((string) getenv('VCOSTAEDU_VIDEOS_BASE'), '/');
$parar = function (string $m) {
    fwrite(STDERR, "GUARDA: {$m} Nada foi aberto nem gravado.\n");
    exit(9);
};
if ($raiz === '' || !is_file("{$raiz}/database/ferramentas/comum.php") || !is_file("{$raiz}/painel/core/Config.php")) {
    $parar('VCOSTAEDU_VIDEOS_RAIZ não aponta para uma instalação com Config (' . ($raiz ?: 'vazio') . ').');
}
if ($base === '' || strtolower(basename(parse_url($base, PHP_URL_PATH) ?: '')) !== strtolower(basename($raiz))) {
    $parar("a URL ({$base}) não aponta para a raiz {$raiz}.");
}
$argvOriginal = $argv;
$argv = [$argv[0]]; // a ferramenta comum não recebe as opções daqui
require "{$raiz}/database/ferramentas/comum.php";
$argv = $argvOriginal;

$pdo = vcostaedu_pdo();
$um = function (string $sql, array $p = []) use ($pdo) { $s = $pdo->prepare($sql); $s->execute($p); $v = $s->fetchColumn(); return $v === false ? null : $v; };
$instituicao = (string) $um('SELECT nome FROM adms_empresas WHERE instituicao = 1 ORDER BY id LIMIT 1');
/* Onda 5: VOLTAR ao retrato (tirado antes, com a guarda inteira) só pede o banco da demonstração — o roteiro pode ter
   trocado o nome da instituição ou removido a demonstração, e é justamente isso que o retrato desfaz */
$soBanco = ($argv[1] ?? '') === 'restaurar' && (preg_match('/_demo$/', (string) DBNAME)
    || (defined('AMBIENTE') && AMBIENTE === 'demonstracao' && defined('BANCO_DEMONSTRACAO') && (string) BANCO_DEMONSTRACAO === (string) DBNAME));
/* Onda 5: a faculdade ou o colégio fictícios (a demonstração atual dos dois tipos de instituição) */
if (!$soBanco && !in_array($instituicao, ['Faculdade Horizonte (fictícia)', 'Colégio Horizonte (fictício)'], true)) {
    $parar('a instituição do banco em uso não é a fictícia da demonstração.');
}
/* a Config declara o banco de demonstração; OU (Onda 5, a instalação da gravação, montada pela tela como qualquer
   instalação) o banco tem o nome terminado em "_demo" e a demonstração está ligada nele */
$declarada = defined('AMBIENTE') && AMBIENTE === 'demonstracao' && defined('BANCO_DEMONSTRACAO') && (string) BANCO_DEMONSTRACAO === (string) DBNAME;
$ligada = preg_match('/_demo$/', (string) DBNAME) && $um("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.ativa'") === '1';
if (!$soBanco && !$declarada && !$ligada) {
    $parar("o banco não é o de demonstração (nem declarado na Config, nem um banco \"_demo\" com a demonstração ligada).");
}
if ((int) $um("SELECT COUNT(*) FROM rm_historico_emissoes WHERE ambiente <> 'demonstracao'") > 0) {
    $parar('há emissão que não é da demonstração.');
}

$acao = $argv[1] ?? '';
$pastas = ['storage', 'assets/instituicao', 'painel/assets/img/usuario', 'painel/assets/img/academicos', 'portal/assets/img/academicos', 'painel/assets/docs/alunos'];
$arquivos = function () use ($raiz, $pastas): array {
    $l = [];
    foreach ($pastas as $p) {
        if (!is_dir("{$raiz}/{$p}")) {
            continue;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$raiz}/{$p}", FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            $l[] = str_replace('\\', '/', $f->getPathname());
        }
    }
    sort($l);
    return $l;
};
$cliente = function (string $prog): string {
    $c = "C:/xampp/mysql/bin/{$prog}.exe";
    return getenv('VCOSTAEDU_MYSQL_BIN') ? rtrim(getenv('VCOSTAEDU_MYSQL_BIN'), '/') . "/{$prog}" : (is_file($c) ? $c : $prog);
};
$rodar = function (string $cmd, ?string $entrada = null): array {
    $env = array_merge(getenv(), ['MYSQL_PWD' => PASS]);
    $p = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pp, null, $env);
    if ($entrada !== null) {
        $h = fopen($entrada, 'rb');
        stream_copy_to_stream($h, $pp[0]);
        fclose($h);
    }
    fclose($pp[0]);
    $out = stream_get_contents($pp[1]);
    $err = stream_get_contents($pp[2]);
    fclose($pp[1]);
    fclose($pp[2]);
    return [proc_close($p), $out, $err];
};
$conexao = ' --default-character-set=utf8mb4 -h ' . escapeshellarg(HOST) . ' -u ' . escapeshellarg(USER) . ' ';

switch ($acao) {
    case 'guarda':
        echo json_encode(['instituicao' => $instituicao, 'banco' => DBNAME,
            'versao' => (string) $um('SELECT numero FROM adms_versoes WHERE situacao = 1 ORDER BY id DESC LIMIT 1')], JSON_UNESCAPED_UNICODE) . "\n";
        break;

    case 'retrato':
        $arq = $argv[2] ?? '';
        [$rc, , $err] = $rodar(escapeshellarg($cliente('mysqldump')) . $conexao . '--routines --triggers --single-transaction --result-file=' . escapeshellarg($arq) . ' ' . escapeshellarg(DBNAME));
        if ($rc !== 0 || !is_file($arq) || filesize($arq) < 1000) {
            fwrite(STDERR, "retrato falhou: {$err}\n");
            exit(1);
        }
        file_put_contents($arq . '.arquivos.json', json_encode($arquivos()));
        echo "ok\n";
        break;

    case 'restaurar':
        $arq = $argv[2] ?? '';
        if (!is_file($arq) || !is_file($arq . '.arquivos.json')) {
            fwrite(STDERR, "restaurar: retrato {$arq} não existe\n");
            exit(1);
        }
        [$rc, , $err] = $rodar(escapeshellarg($cliente('mysql')) . $conexao . escapeshellarg(DBNAME), $arq);
        if ($rc !== 0) {
            fwrite(STDERR, "restaurar falhou: {$err}\n");
            exit(1);
        }
        $antes = array_flip(json_decode((string) file_get_contents($arq . '.arquivos.json'), true) ?: []);
        $n = 0;
        foreach ($arquivos() as $f) {
            if (!isset($antes[$f]) && basename($f) !== '.gitkeep' && @unlink($f)) {
                $n++;
            }
        }
        echo "ok ({$n} arquivo(s) novo(s) apagado(s))\n";
        break;

    case 'senhas':
        $contas = json_decode((string) ($argv[2] ?? '[]'), true) ?: [];
        $senhas = [];
        foreach ($contas as $login) {
            $login = (string) $login;
            $col = ctype_digit($login);
            $senhas[$login] = vcostaedu_senha_aleatoria();
            $s = $pdo->prepare($col ? 'UPDATE col_usuarios SET senha = ? WHERE rm = ?' : 'UPDATE adms_usuarios SET senha = ? WHERE usuario = ?');
            $s->execute([password_hash($senhas[$login], PASSWORD_DEFAULT), $login]);
            if ($s->rowCount() !== 1) {
                fwrite(STDERR, "conta {$login} não existe na demonstração\n");
                exit(1);
            }
        }
        $pdo->exec('DELETE FROM seg_tentativas'); // tentativas de login de uma gravação anterior não bloqueiam a próxima
        echo json_encode($senhas) . "\n";
        break;

    case 'ids':
        $curso = (int) $um("SELECT id FROM rm_cursos WHERE nome = 'Pós-graduação em Gestão de Projetos'");
        $curso2 = (int) $um("SELECT id FROM rm_cursos WHERE nome = 'Pós-graduação em Docência no Ensino Superior'");
        $ids = ['curso' => $curso, 'curso2' => $curso2, 'grade' => (int) $um('SELECT id FROM rm_cursos_grades WHERE curso_id = ? ORDER BY id LIMIT 1', [$curso]),
            'polo' => (int) $um("SELECT id FROM rm_polos WHERE nome = 'Polo Central Fictício'")];
        foreach (['FAP' => 'Fundamentos de Gestão de Projetos', 'MPA' => 'Métodos Ágeis', 'GPS' => 'Gestão de Riscos',
                  'OFP' => 'Planejamento e Cronograma', 'GOC' => 'Liderança de Equipes', 'STF' => 'Trabalho de Conclusão de Curso'] as $k => $nome) {
            $ids['oferta'][$k] = (int) $um('SELECT id FROM rm_professores_materias WHERE curso_id = ? AND materia = ? ORDER BY id LIMIT 1', [$curso, $nome]);
            $ids['materia'][$k] = (int) $um('SELECT id FROM rm_materias WHERE nome = ? ORDER BY id LIMIT 1', [$nome]);
            $ids['atividade'][$k] = array_map('intval', $pdo->query('SELECT id FROM rm_disciplina_atividade WHERE rm_prof_mat_id = ' . (int) $ids['oferta'][$k] . ' ORDER BY data_inicial, id')->fetchAll(PDO::FETCH_COLUMN));
            /* 1.18.2: a linha da disciplina na matriz do curso principal (os roteiros não usam id fixo: a demonstração pode ser remontada) */
            $ids['linha'][$k] = (int) $um('SELECT id FROM uni_rh_cursos_disciplinas_grade WHERE grade_id = ? AND materia_id = ? ORDER BY id LIMIT 1', [$ids['grade'], $ids['materia'][$k]]);
        }
        foreach ($pdo->query('SELECT u.rm, u.id, (SELECT mc.id FROM rm_matriculas_curso mc WHERE mc.usuario_id = u.id ORDER BY mc.id LIMIT 1) AS mc FROM col_usuarios u ORDER BY u.rm') as $r) {
            $ids['aluno'][$r['rm']] = (int) $r['id'];
            $ids['matricula'][$r['rm']] = (int) $r['mc'];
        }
        foreach ($pdo->query('SELECT e.id, e.tipo, e.codigo, u.rm FROM rm_historico_emissoes e JOIN col_usuarios u ON u.id = e.aluno_id ORDER BY e.id') as $r) {
            $ids['emissao'][] = ['id' => (int) $r['id'], 'tipo' => $r['tipo'], 'codigo' => $r['codigo'], 'ra' => (string) $r['rm']];
        }
        foreach ($pdo->query('SELECT id, nome FROM rm_historico_modelos ORDER BY id') as $r) {
            $ids['modelo'][(int) $r['id']] = $r['nome'];
        }
        $ids['usuario'] = array_map('intval', $pdo->query('SELECT usuario, id FROM adms_usuarios')->fetchAll(PDO::FETCH_KEY_PAIR));
        echo json_encode($ids, JSON_UNESCAPED_UNICODE) . "\n";
        break;

    case 'sql':
        $sql = (string) file_get_contents($argv[2] ?? '');
        foreach (array_filter(array_map('trim', preg_split('/;\s*\n/', $sql))) as $cmd) {
            $pdo->exec($cmd);
        }
        echo "ok\n";
        break;

    default:
        fwrite(STDERR, "Uso: php banco.php guarda|retrato <arq>|restaurar <arq>|senhas <json>|ids|sql <arq>\n");
        exit(1);
}
