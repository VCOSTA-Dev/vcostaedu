<?php
/*
 * A INSCRIÇÃO, do lado do servidor do site: UMA chamada à operação única da interface (POST /inscricoes), que faz o
 * cadastro, a matrícula e a turma de uma vez, e devolve o estado no ambiente de estudo. A chave de repetição vem do
 * envio do formulário: o clique duplo manda a mesma, e a plataforma devolve a mesma resposta, sem segunda inscrição.
 * Funciona sem JavaScript.
 */
require __DIR__ . '/lib.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !hash_equals(token(), (string) ($_POST['token'] ?? ''))) {
    header('Location: index.php', true, 303);
    exit;
}
$c = cfg();
$envio = preg_replace('/[^a-f0-9]/', '', (string) ($_POST['envio'] ?? ''));
$d = ['nome' => trim((string) ($_POST['nome'] ?? '')), 'email' => trim((string) ($_POST['email'] ?? '')), 'celular' => trim((string) ($_POST['celular'] ?? ''))];
$voltar = static function (string $msg) use ($d) {
    $_SESSION['erro'] = $msg;
    $_SESSION['antes'] = $d;
    header('Location: index.php#inscricao', true, 303);
    exit;
};
$digitos = preg_replace('/\D/', '', $d['celular']);
if (strlen($envio) !== 24 || mb_strlen($d['nome']) < 3 || !filter_var($d['email'], FILTER_VALIDATE_EMAIL) || strlen($digitos) < 10 || strlen($digitos) > 11) {
    $voltar('Confira os campos: o nome, um e-mail válido e o celular com DDD.');
}

/* o limite contra abuso, por endereço (o teto fica na configuração local) */
if (!cabeNoLimite($envio)) {
    $voltar('Muitas inscrições a partir da mesma rede em pouco tempo. Espere alguns minutos e tente de novo.');
}

/* o mesmo envio fica com o mesmo número de matrícula, e com a mesma chave de repetição */
$ra = numeroDaMatricula($envio);
$inicio = microtime(true);
$r = api('POST', '/inscricoes', [
    'aluno' => ['ra' => $ra, 'nome' => $d['nome'], 'email' => $d['email'], 'celular' => $digitos],
    'destino' => (array) ($c['destino'] ?? []),
], 'curso-livre-' . hash('sha256', $envio));
if (!in_array($r['status'], [200, 201], true)) {
    $voltar('Não foi possível concluir a inscrição: ' . motivo($r));
}
$antes = $_SESSION['inscricoes'][$ra] ?? [];
$ins = ['inicio' => $antes['inicio'] ?? $inicio, 'resposta' => $r['json'], 'pronto' => $antes['pronto'] ?? null,
    'boas_vindas' => $antes['boas_vindas'] ?? null, 'link' => $antes['link'] ?? null];

/* o e-mail de boas-vindas, com o link do primeiro acesso: a plataforma manda (o mesmo link de uso único que a secretaria
   manda), uma vez por pessoa nova. Com a opção botao_criar_senha, o site também recebe o link, para o botão "Criar
   minha senha agora" (o link fica só na sessão do site, do lado do servidor). O clique duplo não manda dois: a sessão
   guarda que já foi. */
if ($ins['boas_vindas'] === null && ($r['json']['feito']['aluno'] ?? '') === 'criado') {
    $botao = !empty($c['botao_criar_senha']);
    $b = api('POST', '/alunos/' . $ra . '/primeiro-acesso', ['enviar_email' => true, 'devolver_link' => $botao]);
    $ins['boas_vindas'] = $b['status'] === 200 && !empty($b['json']['dados']['email_enviado']);
    $ins['link'] = $botao ? ((string) ($b['json']['dados']['link'] ?? '') ?: null) : null;
}
$_SESSION['inscricoes'][$ra] = $ins;
header('Location: confirmacao.php?m=' . rawurlencode($ra), true, 303);
