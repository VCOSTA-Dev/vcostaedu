<?php
/*
 * ONDA 6, ETAPA 4 — a oferta GRANDE da prova (só no banco de prova, terminado em _teste; nenhum dado real):
 *   php tests/tabler/onda6/oferta_grande.php montar <oferta> <n>   n alunos fictícios (RA a partir de 960001) enturmados
 *                                                                    na oferta, com nota em cada atividade e faltas
 *   php tests/tabler/onda6/oferta_grande.php medir <oferta>        o encerramento pelo model, medido (o mesmo da tela), e o
 *                                                                    gravado conferido contra o cálculo, aluno a aluno
 *   php tests/tabler/onda6/oferta_grande.php limpar <oferta>       tira os alunos fictícios (o resto, o ciclo da bateria refaz)
 * Responde JSON.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$raiz = dirname(__DIR__, 3);
[$_, $modo, $oferta, $n] = $argv + [null, '', 0, 0];
$oferta = (int) $oferta;
$banco = (string) getenv('VCOSTAEDU_BANCO_TESTE');
if (!str_ends_with($banco, '_teste') || $oferta < 1 || !in_array($modo, ['montar', 'medir', 'limpar'], true)) {
    fwrite(STDERR, "uso: php oferta_grande.php montar|medir|limpar <oferta> [n] (VCOSTAEDU_BANCO_TESTE terminando em _teste)\n");
    exit(2);
}
define('VCOSTAEDU', true);
$_SERVER += ['SERVER_NAME' => 'localhost', 'HTTP_HOST' => 'localhost', 'REQUEST_URI' => '/', 'SCRIPT_NAME' => '/painel/index.php'];
chdir($raiz . '/painel');
require $raiz . '/painel/vendor/autoload.php';
(new class extends \Core\Config {
    public function __construct()
    {
        $this->configAdm();
    }
});
if (DBNAME !== $banco) {
    fwrite(STDERR, "a Config do painel não aponta para o banco de prova\n");
    exit(3);
}
require_once $raiz . '/core/Requisicao.php';
require_once $raiz . '/core/Banco.php';
require_once $raiz . '/core/Transacao.php';
\Core\Requisicao::definir();
$_SESSION = ['colUsuario_id' => 1, 'colUsuario_nivel' => 1, 'colUsuario_ordem' => 1];
$pdo = \VcBanco::daRequisicao();
$RA0 = 960000;

if ($modo === 'montar') {
    $n = max(1, min(5000, (int) $n));
    $o = $pdo->query("SELECT * FROM rm_professores_materias WHERE id = {$oferta}")->fetch(PDO::FETCH_ASSOC);
    /* os modelos: uma linha que já existe na oferta (só o RA, o nome e a nota mudam) */
    $mEnt = $pdo->query("SELECT * FROM portal_disciplinas_alunos WHERE rm_prof_mat_id = {$oferta} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$mEnt) {
        fwrite(STDERR, "a oferta não tem aluno para servir de modelo\n");
        exit(4);
    }
    $mUsu = $pdo->query('SELECT * FROM col_usuarios WHERE rm = ' . $pdo->quote((string) $mEnt['rm']) . ' LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $mFal = $pdo->query("SELECT * FROM rm_disciplina_faltas WHERE rm_prof_mat_id = {$oferta} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $mNot = [];
    foreach ($pdo->query("SELECT * FROM rm_disciplina_atividade_notas WHERE rm_prof_mat_id = {$oferta} AND rm = " . $pdo->quote((string) $mEnt['rm']))->fetchAll(PDO::FETCH_ASSOC) as $x) {
        $mNot[] = $x;
    }
    $linha = function (array $modelo, array $troca) use ($pdo): string {
        unset($modelo['id']);
        $v = array_map(fn($x) => $x === null ? 'NULL' : $pdo->quote((string) $x), array_merge($modelo, $troca));
        return '(' . implode(', ', $v) . ')';
    };
    $cols = function (array $modelo): string {
        unset($modelo['id']);
        return '(`' . implode('`, `', array_keys($modelo)) . '`)';
    };
    $t = microtime(true);
    $pdo->beginTransaction();
    foreach (array_chunk(range(1, $n), 200) as $parte) {
        $u = $e = $nt = $f = [];
        foreach ($parte as $i) {
            $ra = (string) ($RA0 + $i);
            $nome = sprintf('Aluno Oferta Grande %04d Exemplo', $i);
            $u[] = $linha($mUsu, ['rm' => $ra, 'nome' => $nome, 'usuario' => 'grande' . $ra, 'email' => null, 'senha' => '-inexistente']);
            $e[] = $linha($mEnt, ['rm' => $ra, 'nome_aluno' => $nome]);
            foreach ($mNot as $k => $mn) {
                $nt[] = $linha($mn, ['rm' => $ra, 'nome_aluno' => $nome, 'nota' => number_format((($i * 7 + $k * 3) % 101) / 10, 1, '.', '')]);
            }
            if ($mFal) {
                $f[] = $linha($mFal, ['rm' => $ra, 'faltas' => $i % 9, 'faltas_compensadas' => 0]);
            }
        }
        $pdo->exec('INSERT INTO col_usuarios ' . $cols($mUsu) . ' VALUES ' . implode(', ', $u));
        $pdo->exec('INSERT INTO portal_disciplinas_alunos ' . $cols($mEnt) . ' VALUES ' . implode(', ', $e));
        if ($nt) {
            $pdo->exec('INSERT INTO rm_disciplina_atividade_notas ' . $cols($mNot[0]) . ' VALUES ' . implode(', ', $nt));
        }
        if ($f) {
            $pdo->exec('INSERT INTO rm_disciplina_faltas ' . $cols($mFal) . ' VALUES ' . implode(', ', $f));
        }
    }
    $pdo->commit();
    $atv = $mNot;
    echo json_encode(['oferta' => $oferta, 'alunos' => $n, 'atividades' => count($atv), 'aberta' => empty($o['encerrada_em']), 's' => round(microtime(true) - $t, 2)]), "\n";
    exit(0);
}
if ($modo === 'medir') {
    $t = microtime(true);
    $calc = \App\adms\Models\notas\AdmsCalculoNotas::oferta($oferta, true);
    $tc = microtime(true) - $t;
    $t = microtime(true);
    $enc = new \App\adms\Models\notas\AdmsEncerrarOferta();
    $enc->encerrar($oferta);
    $te = microtime(true) - $t;
    $gravados = (int) $pdo->query("SELECT COUNT(*) FROM rm_disciplina_media_final WHERE rm_prof_mat_id = {$oferta}")->fetchColumn();
    /* o gravado é o calculado: RA, média, frequência, resultado e motivo de cada aluno, na mesma ordem */
    $num = fn($v) => $v === null || $v === '' ? '' : (string) round((float) $v, 4); // 7.5 e 7.50 são o mesmo número
    $doCalculo = array_map(fn($a) => implode('|', [$a['rm'], $num($a['media']), $num($a['frequencia']), (int) $a['resultado'], (string) $a['motivo']]), $calc['alunos'] ?? []);
    sort($doCalculo);
    $doBanco = array_map(fn($g) => implode('|', [$g['rm'], $num($g['media_final']), $num($g['frequencia']), (int) $g['resultado'], (string) $g['motivo']]),
        $pdo->query("SELECT rm, media_final, frequencia, resultado, motivo FROM rm_disciplina_media_final WHERE rm_prof_mat_id = {$oferta}")->fetchAll(PDO::FETCH_ASSOC));
    sort($doBanco);
    echo json_encode(['alunos' => count($calc['alunos'] ?? []), 'calculo_s' => round($tc, 2), 'encerrar_s' => round($te, 2), 'gravados' => $gravados,
        'igual_ao_calculo' => hash('sha256', implode("\n", $doCalculo)) === hash('sha256', implode("\n", $doBanco)),
        'ok' => (bool) $enc->getResultado(), 'msg' => trim(strip_tags((string) ($_SESSION['msg'] ?? '')))], JSON_UNESCAPED_UNICODE), "\n";
    exit(0);
}
/* limpar */
$pdo->exec("DELETE FROM rm_disciplina_media_final WHERE rm_prof_mat_id = {$oferta} AND rm BETWEEN '" . ($RA0 + 1) . "' AND '" . ($RA0 + 5000) . "'");
foreach (['rm_disciplina_atividade_notas', 'rm_disciplina_faltas', 'portal_disciplinas_alunos'] as $tab) {
    $pdo->exec("DELETE FROM {$tab} WHERE rm_prof_mat_id = {$oferta} AND rm BETWEEN '" . ($RA0 + 1) . "' AND '" . ($RA0 + 5000) . "'");
}
$pdo->exec("DELETE FROM col_usuarios WHERE col_niveis_acesso_id = 1 AND rm BETWEEN '" . ($RA0 + 1) . "' AND '" . ($RA0 + 5000) . "'");
echo json_encode(['limpo' => true]), "\n";
