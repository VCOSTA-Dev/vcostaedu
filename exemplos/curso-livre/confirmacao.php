<?php
/*
 * A CONFIRMAÇÃO: os passos da inscrição se cumprindo, na linguagem de quem acabou de se inscrever. O estado vem da
 * plataforma (a resposta da inscrição e, a cada recarga, a consulta do aluno); a página se recarrega sozinha enquanto
 * o ambiente de estudo é preparado (sem JavaScript). Nenhuma chave, nenhum endereço nem identificador do ambiente.
 */
require __DIR__ . '/lib.php';
$ra = preg_replace('/\D/', '', (string) ($_GET['m'] ?? ''));
$ins = $_SESSION['inscricoes'][$ra] ?? null;
if (!$ins) {
    header('Location: index.php', true, 303);
    exit;
}
$resp = (array) ($ins['resposta'] ?? []);
$turma = (array) ($resp['dados']['turma'] ?? []);
$total = (int) ($resp['moodle']['disciplinas_total'] ?? ($turma['componentes'] ?? 0));
$m = (array) ($resp['moodle'] ?? []);
$v = api('GET', '/alunos/' . $ra);
if ($v['status'] === 200 && isset($v['json']['dados']['moodle'])) {
    $m = $v['json']['dados']['moodle'] + ['disciplinas_total' => $total];
}
$noAmbiente = !empty($m['enviado']);
$disc = (int) ($m['disciplinas'] ?? 0);
$pronto = $noAmbiente && $total > 0 && $disc >= $total;
if ($pronto && empty($ins['pronto'])) {
    $_SESSION['inscricoes'][$ra]['pronto'] = microtime(true);
    $ins['pronto'] = $_SESSION['inscricoes'][$ra]['pronto'];
}
$segundos = $pronto ? max(0.1, $ins['pronto'] - $ins['inicio']) : null;
$esperando = !$pronto && (microtime(true) - $ins['inicio']) < 180;
$primeiroNome = strtok((string) ($resp['dados']['aluno']['nome'] ?? ''), ' ') ?: '';
topo('Sua inscrição', $esperando);
?>
<main class="estreito">
  <section class="bloco confirmacao" aria-labelledby="tituloConfirmacao">
    <h1 id="tituloConfirmacao"><?= $primeiroNome !== '' ? h($primeiroNome) . ', que bom ter você aqui.' : 'Que bom ter você aqui.' ?></h1>
    <ol class="passos" id="passosInscricao" data-pronto="<?= $pronto ? '1' : '0' ?>">
      <li class="feito" id="passoVaga"><b>Sua vaga está garantida.</b><br>Seu número de matrícula é <b id="numeroMatricula"><?= h($ra) ?></b> — guarde: ele é o seu login.</li>
      <li class="feito" id="passoTurma"><b>Você está na turma.</b><br><?= h($turma['curso'] ?? 'IA na prática para professores') ?><?= $total ? ', com ' . $total . ' módulos' : '' ?>.</li>
      <?php if ($pronto) : ?>
        <li class="feito" id="passoAmbiente"><b>Seu ambiente de estudo está pronto.</b><br>Os <?= $total ?> módulos já estão lá esperando por você.</li>
      <?php elseif (($m['falhas'] ?? 0) > 0 && !$esperando) : ?>
        <li class="falta" id="passoAmbiente"><b>Estamos terminando de preparar seu ambiente de estudo.</b><br>Nossa equipe já foi avisada e conclui em breve. Você pode voltar a esta página mais tarde.</li>
      <?php else : ?>
        <li class="andamento" id="passoAmbiente" aria-live="polite"><b>Preparando seu ambiente de estudo…</b><br><?= $noAmbiente ? $disc . ' de ' . $total . ' módulos prontos.' : 'Criando o seu acesso.' ?></li>
      <?php endif; ?>
    </ol>
    <?php if ($pronto) : ?>
      <p class="tempo" id="tempoInscricao">Tudo pronto em <?= h(number_format($segundos, 1, ',', '')) ?> segundos.</p>
    <?php elseif ($esperando) : ?>
      <p class="nota">Esta página se atualiza sozinha. <a href="confirmacao.php?m=<?= h(rawurlencode($ra)) ?>">Atualizar agora</a></p>
    <?php endif; ?>
    <?php if (!empty($ins['link'])) : ?>
      <p class="nota" id="senhaAgora">Crie a sua senha para entrar. O mesmo link também foi para o seu e-mail.</p>
      <a class="botao" href="<?= h($ins['link']) ?>" id="criarSenhaAgora" rel="noreferrer">Criar minha senha agora</a>
    <?php elseif (!empty($ins['boas_vindas'])) : ?>
      <p class="nota" id="emailEnviado">Enviamos para <b><?= h($resp['dados']['aluno']['email'] ?? 'o seu e-mail') ?></b> uma mensagem de boas-vindas, com o link para criar a sua senha. Confira também a caixa de spam.</p>
    <?php elseif (array_key_exists('boas_vindas', $ins) && $ins['boas_vindas'] === false) : ?>
      <p class="nota" id="emailNaoEnviado">Não conseguimos enviar agora a mensagem de boas-vindas, com o link para criar a sua senha. Nossa equipe vai mandá-la para <b><?= h($resp['dados']['aluno']['email'] ?? 'o seu e-mail') ?></b> em breve.</p>
    <?php endif; ?>
    <?php if ($pronto) : ?>
      <p><a class="botao botao-secundario" href="<?= h((string) (cfg()['portal'] ?? '#')) ?>" id="entrarNoCurso">Entrar no curso</a></p>
      <p class="nota">Depois de criar a senha, entre com o número de matrícula e a senha, e clique em "Entrar no Moodle".</p>
    <?php endif; ?>
  </section>
</main>
<?php rodape();
