<?php
/*
 * A INSCRIÇÃO ENVIADA: tudo pela API do VCOSTAEDU, do lado do servidor — cadastrar o aluno, matricular no curso e
 * enturmar nas disciplinas abertas do ano. O resto (o envio ao Moodle) o VCOSTAEDU faz sozinho; o andamento mostra.
 */
require __DIR__ . '/lib.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !hash_equals(token(), (string) ($_POST['token'] ?? ''))) {
    header('Location: index.php', true, 303);
    exit;
}
$c = cfg();
/* o envio do formulário (um número sorteado na página): dele saem as chaves de repetição de cada passo e o número de
   matrícula deste envio — o clique duplo e a nova tentativa não fazem uma segunda inscrição */
$envio = preg_replace('/[^a-f0-9]/', '', (string) ($_POST['envio'] ?? ''));
$chave = static fn(string $passo): string => 'portal-matricula-' . hash('sha256', $envio . '|' . $passo);
$d = ['nome' => trim((string) ($_POST['nome'] ?? '')), 'email' => trim((string) ($_POST['email'] ?? '')), 'cpf' => preg_replace('/\D/', '', (string) ($_POST['cpf'] ?? '')),
    'sexo' => (string) ($_POST['sexo'] ?? '')];
$curso = $c['cursos'][(int) ($_POST['curso'] ?? -1)] ?? null;
$voltar = static function (string $msg) use ($d) {
    $_SESSION['erro'] = $msg;
    $_SESSION['antes'] = $d;
    header('Location: index.php', true, 303);
    exit;
};
if (strlen($envio) !== 24 || $d['nome'] === '' || !filter_var($d['email'], FILTER_VALIDATE_EMAIL) || strlen($d['cpf']) !== 11 || !$curso) {
    $voltar('Preencha o nome, um e-mail válido, o CPF (11 dígitos), o sexo e o curso.');
}

/* 1. o aluno: um RA novo da faixa do exemplo (se o número já existe, a API devolve o existente — tenta outro) */
$ra = (string) ($_SESSION['envios'][$envio] ?? '');
for ($i = 0; $i < 6 && $ra === ''; $i++) {
    $tentativa = (string) random_int((int) $c['ra_inicio'], (int) $c['ra_fim']);
    $r = api('POST', '/alunos', ['ra' => $tentativa, 'nome' => $d['nome'], 'email' => $d['email'], 'cpf' => $d['cpf'], 'sexo' => $d['sexo']], $chave('aluno-' . $tentativa));
    if ($r['status'] === 201) {
        $ra = $tentativa;
        $_SESSION['envios'][$envio] = $ra;
    } elseif ($r['status'] !== 200) {
        $voltar('Não foi possível fazer a inscrição: ' . motivo($r));
    }
}
if ($ra === '') {
    $voltar('Não foi possível gerar um número de matrícula livre. Tente de novo.');
}
$passos = ['ra' => $ra, 'aluno' => true, 'matricula' => null, 'enturmacao' => null, 'erro' => ''];

/* 2. a matrícula no curso */
$r = api('POST', '/matriculas', ['ra' => $ra, 'curso_id' => (int) $curso['id'], 'ano' => (int) $curso['ano'], 'forma_ingresso_id' => (int) $curso['forma_ingresso_id'], 'data_ingresso' => date('Y-m-d')], $chave('matricula'));
if ($r['status'] !== 201) {
    $passos['matricula'] = false;
    $passos['erro'] = motivo($r);
} else {
    $mc = (int) $r['json']['dados']['id'];
    $passos['matricula'] = true;
    /* 3. a enturmação: as disciplinas abertas do ano */
    $r = api('GET', "/matriculas/{$mc}/enturmacao");
    $ofertas = $r['status'] === 200 ? array_values(array_map(fn($o) => (int) $o['oferta_id'], array_filter((array) $r['json']['dados']['ofertas'], fn($o) => empty($o['encerrada'])))) : [];
    $r = $ofertas ? api('POST', "/matriculas/{$mc}/enturmacao", ['ofertas' => $ofertas], $chave('enturmacao')) : $r;
    if ($ofertas && $r['status'] === 200) {
        $passos['enturmacao'] = count($ofertas);
    } else {
        $passos['enturmacao'] = false;
        $passos['erro'] = $ofertas ? motivo($r) : ($r['status'] === 200 ? 'O curso ainda não tem disciplinas abertas neste ano.' : motivo($r));
    }
}
$_SESSION['inscricoes'][$ra] = $passos;
header('Location: andamento.php?ra=' . rawurlencode($ra), true, 303);
