<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* A pessoa responsável: o cadastro, os alunos e os papéis por ano, o acesso ao portal, o histórico. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$p = $d['pessoa'];
$M = \App\adms\Models\escola\AdmsResponsaveis::class;
$pode = (new \App\adms\Models\AdmsBotao())->valBotao(['editar' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'editar']]);
$cor = ['sem_convite' => 'secondary', 'convite' => 'blue', 'ativo' => 'green', 'bloqueado' => 'red'];
$dt = fn($x) => $x ? date('d/m/Y', strtotime((string) $x)) : '';
?>
<div class="vc-pagina">
  <?php ob_start(); ?><?= $h($p['nome']) ?><?php $paginaTitulo = ob_get_clean();
  $paginaPretitulo = 'Responsáveis';
  $paginaAcoesHtml = !empty($pode['editar']) ? '<a href="' . URLADM . 'responsaveis/editar/' . (int) $p['id'] . '" class="btn btn-outline-primary" id="editarResponsavel"><i class="fas fa-pen"></i> Editar</a>' : '';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="row row-cards">
        <div class="col-lg-4">
          <div class="card" id="cadastroResponsavel"><div class="card-header"><h3 class="card-title">Cadastro</h3></div><div class="card-body">
            <dl class="row mb-0">
              <dt class="col-5">E-mail</dt><dd class="col-7"><?= $h($p['email'] ?: '—') ?></dd>
              <dt class="col-5">Celular</dt><dd class="col-7"><?= $h($M::celularFormatado($p['celular']) ?: '—') ?></dd>
              <dt class="col-5">CPF</dt><dd class="col-7"><?= $h($M::cpfFormatado($p['cpf']) ?: '—') ?></dd>
              <dt class="col-5">Endereço</dt><dd class="col-7" id="enderecoResponsavel"><?= $h($M::enderecoEmLinha($p) ?: '—') ?></dd>
              <dt class="col-5">Acesso ao portal</dt><dd class="col-7"><span class="badge bg-<?= $h($cor[$p['situacao']] ?? 'secondary') ?>-lt" id="situacaoAcesso" data-situacao="<?= $h($p['situacao']) ?>"><?= $h($M::SITUACOES[$p['situacao']] ?? '') ?></span></dd>
            </dl>
            <?php if (!$p['email'] && !$p['cpf']) : ?><div class="alert alert-warning mt-3 mb-0" id="semIdentificador">Sem e-mail e sem CPF, a pessoa não consegue entrar no portal: um dos dois é o identificador de entrada.</div><?php endif; ?>
          </div></div>
          <?php $extraAcesso = __DIR__ . '/_acesso.php'; if (is_file($extraAcesso)) { include $extraAcesso; } ?>
        </div>
        <div class="col-lg-8">
          <div class="card" id="alunosDoResponsavel"><div class="card-header"><h3 class="card-title">Alunos e papéis</h3></div>
            <?php if (!$d['alunos']) : ?><div class="card-body text-secondary">Ainda não é responsável por nenhum aluno. Designe pela ficha do aluno (Responsáveis).</div><?php endif; ?>
            <?php foreach ($d['alunos'] as $a) : ?>
              <div class="card-body border-bottom">
                <div class="d-flex justify-content-between flex-wrap"><div><a href="<?= URLADM ?>responsaveis/aluno/<?= $h((int) $a['id']) ?>"><b><?= $h($a['nome']) ?></b></a> <span class="text-secondary">· <?= $h($a['parentesco']) ?> · RM <?= $h($a['rm']) ?></span></div></div>
                <?php if (!$a['designacoes']) : ?><div class="text-secondary small">sem papel em nenhum ano</div><?php endif; ?>
                <ul class="mb-0 small">
                <?php foreach ($a['designacoes'] as $x) : ?>
                  <li><?= $h($x['ano']) ?>: <?= $h($M::PAPEIS[$x['papel']] ?? $x['papel']) ?>, desde <?= $h($dt($x['inicio'])) ?><?= $x['fim'] ? ' <span class="text-secondary">— até ' . $h($dt($x['fim'])) . ' (' . $h($x['motivo_fim']) . ')</span>' : ' <span class="badge bg-green-lt">em vigor</span>' ?></li>
                <?php endforeach; ?>
                </ul>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="card mt-3" id="historicoResponsavel"><div class="card-header"><h3 class="card-title">Histórico</h3></div>
            <div class="table-responsive"><table class="table table-sm card-table"><thead><tr><th>Quando</th><th>O quê</th><th>Por</th><th>Motivo</th></tr></thead><tbody>
            <?php foreach ($d['historico'] as $x) : ?>
              <tr><td class="text-nowrap"><?= $h(date('d/m/Y H:i', strtotime($x['em']))) ?></td><td><?= $h(\App\adms\Models\escola\AdmsResponsaveisTela::acao($x)) ?></td><td><?= $h($x['por_nome'] ?: ($x['origem'] === 'portal' ? 'o próprio responsável' : $x['origem'])) ?></td><td><?= $h($x['motivo'] ?? '') ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
