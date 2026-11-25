<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* A aba "Responsáveis" da ficha do aluno (escola; Onda 7, Etapa 2): o resumo do ano letivo e o caminho para a tela dos responsáveis. */
$M = \App\adms\Models\escola\AdmsResponsaveis::class;
$hR = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$anosR = $M::anos();
$anoR = in_array((int) date('Y'), $anosR, true) ? (int) date('Y') : $anosR[0];
$rR = $M::doAluno((int) $info['id'], $anoR);
?>
<div id="resumoResponsaveis">
  <p class="text-secondary mb-2">Em <?= $hR($anoR) ?>:</p>
  <dl class="row mb-2">
    <?php foreach (['financeiro', 'titular'] as $papelR) : ?>
      <dt class="col-sm-5"><?= $hR($M::PAPEIS[$papelR]) ?></dt>
      <dd class="col-sm-7" data-papel="<?= $hR($papelR) ?>"><?= $rR[$papelR] ? $hR($rR[$papelR]['nome']) . ' <span class="text-secondary">(' . $hR($rR[$papelR]['parentesco'] ?? '') . ')</span>' : '<span class="text-secondary">ninguém designado</span>' ?></dd>
    <?php endforeach; ?>
    <dt class="col-sm-5">Acessos acadêmicos adicionais</dt><dd class="col-sm-7"><?= $rR['adicionais'] ? $hR(implode(', ', array_column($rR['adicionais'], 'nome'))) : '<span class="text-secondary">nenhum</span>' ?></dd>
    <dt class="col-sm-5">Para contato</dt><dd class="col-sm-7"><?= $rR['contatos'] ? $hR(implode(', ', array_map(fn($c) => $c['nome'] . ' (' . $c['parentesco'] . ')', $rR['contatos']))) : '<span class="text-secondary">nenhum</span>' ?></dd>
  </dl>
  <a href="<?= URLADM ?>responsaveis/aluno/<?= $hR((int) $info['id']) ?>?ano=<?= $hR($anoR) ?>" class="btn btn-primary" id="abrirResponsaveisAluno"><i class="fas fa-people-roof"></i> Abrir os responsáveis do aluno</a>
</div>
