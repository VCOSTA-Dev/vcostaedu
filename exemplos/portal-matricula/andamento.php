<?php
/* O ANDAMENTO, em linguagem de uso: aluno criado, matriculado, enturmado, enviado ao Moodle, acesso pronto. */
require __DIR__ . '/lib.php';
$ra = preg_replace('/\D/', '', (string) ($_GET['ra'] ?? ''));
$p = $_SESSION['inscricoes'][$ra] ?? null; /* só quem fez a inscrição nesta sessão vê o andamento dela */
topo('Andamento da inscrição');
if (!$p) {
    echo '<div class="erro">Inscrição não encontrada nesta sessão.</div><a class="botao" href="index.php">Nova inscrição</a>';
    rodape();
    exit;
}
$r = api('GET', '/alunos/' . $ra);
$m = $r['status'] === 200 ? ($r['json']['dados']['moodle'] ?? null) : null;
$passo = static function (string $id, ?bool $feito, string $texto, string $detalhe = '') {
    $classe = $feito === true ? 'feito' : ($feito === false ? 'recusado' : 'falta');
    echo '<li id="' . h($id) . '" class="' . $classe . '" data-situacao="' . $classe . '">' . h($texto) . ($detalhe !== '' ? ' <small>' . h($detalhe) . '</small>' : '') . '</li>';
};
?>
<div class="cartao">
  <p>Inscrição recebida. O seu número de matrícula é <b id="raInscricao"><?= h($ra) ?></b>.</p>
  <ol class="andamento" id="andamento">
    <?php
    $passo('passoAluno', true, 'Cadastro feito');
    $passo('passoMatricula', $p['matricula'], 'Matrícula no curso', $p['matricula'] === false ? $p['erro'] : '');
    $passo('passoEnturmacao', $p['enturmacao'] === null ? null : ($p['enturmacao'] !== false), 'Disciplinas do ano', $p['enturmacao'] === false ? $p['erro'] : ($p['enturmacao'] ? $p['enturmacao'] . ' disciplina(s)' : ''));
    if ($m === null || empty($m['conectado'])) {
        $passo('passoMoodle', null, 'Ambiente virtual (Moodle)', 'a instituição ainda não ligou o Moodle');
    } else {
        $passo('passoMoodle', $m['enviado'] && $m['disciplinas'] > 0 ? true : ($m['falhas'] > 0 ? false : null), 'Enviado ao ambiente virtual (Moodle)',
            $m['enviado'] ? $m['disciplinas'] . ' disciplina(s) no Moodle' : ($m['falhas'] > 0 ? 'a secretaria vai conferir' : 'em andamento'));
        $passo('passoAcesso', $m['acesso_pronto'] ? true : null, 'Acesso pronto', $m['acesso_pronto'] ? 'entre no portal do aluno e clique em "Entrar no Moodle"' : 'aguarde alguns minutos');
    }
    ?>
  </ol>
  <p><small>O acesso ao portal do aluno chega pelo e-mail informado.</small></p>
  <a class="botao" href="andamento.php?ra=<?= h($ra) ?>" id="atualizarAndamento">Atualizar o andamento</a>
</div>
<?php rodape();
