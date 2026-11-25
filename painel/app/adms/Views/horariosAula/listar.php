<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Os horários de aula (Onda 8, D-71): as aulas do dia de cada turno; cadastrar, editar, ativar e desativar. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$hm = fn($x) => substr((string) $x, 0, 5);
$pode = (new \App\adms\Models\AdmsBotao())->valBotao([
  'salvar' => ['menu_controller' => 'horarios-aula', 'menu_metodo' => 'salvar'],
  'situacao' => ['menu_controller' => 'horarios-aula', 'menu_metodo' => 'situacao'],
]);
$ed = $d['editando'];
$form = $d['form'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Horários de aula<?php $paginaTitulo = ob_get_clean();
  $paginaPretitulo = 'As aulas do dia de cada turno';
  $paginaAcoesHtml = '<a href="' . URLADM . 'ajuda/guia/montar-o-horario-da-turma" class="btn btn-outline-secondary" id="guiaHorario"><i class="fas fa-circle-question"></i> Como montar o horário</a>';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <p class="text-secondary">Cada turno tem as suas aulas, com o início e o fim. São elas que formam as linhas do horário da semana de cada turma. Desativar um horário tira ele da grade, sem apagar o que já foi montado.</p>
      <div class="row row-cards">
      <?php foreach ($d['turnos'] as $tu) : $tid = (int) $tu['id']; $lista = $d['aulas'][$tid] ?? []; ?>
        <div class="col-lg-4" id="turno<?= $h($tid) ?>">
          <div class="card vc-turno" data-turno="<?= $h($tid) ?>">
            <div class="card-header"><h3 class="card-title"><?= $h($tu['nome']) ?></h3><?php if ((int) $tu['situacao'] !== 1) : ?><span class="badge bg-secondary-lt ms-2">turno inativo</span><?php endif; ?></div>
            <div class="table-responsive">
              <table class="table table-vcenter card-table">
                <thead><tr><th>Aula</th><th>Início</th><th>Fim</th><th></th></tr></thead>
                <tbody>
                <?php if (!$lista) : ?><tr><td colspan="4" class="text-secondary">Nenhum horário neste turno.</td></tr><?php endif; ?>
                <?php foreach ($lista as $a) : $ativo = (int) $a['situacao'] === 1; ?>
                  <tr class="vc-aula<?= $ativo ? '' : ' text-secondary' ?>" data-aula="<?= $h($a['id']) ?>">
                    <td><?= $h($a['nome']) ?><?= $ativo ? '' : ' <span class="badge bg-secondary-lt">desativado</span>' ?></td>
                    <td><?= $h($hm($a['inicio'])) ?></td><td><?= $h($hm($a['termino'])) ?></td>
                    <td class="text-end text-nowrap">
                      <?php if (!empty($pode['salvar'])) : ?><a class="btn btn-sm btn-outline-primary vc-editar-aula" href="<?= URLADM ?>horarios-aula/listar?editar=<?= $h($a['id']) ?>#turno<?= $h($tid) ?>">Editar</a><?php endif; ?>
                      <?php if (!empty($pode['situacao'])) : ?>
                        <form method="post" action="<?= URLADM ?>horarios-aula/situacao/<?= $h($a['id']) ?>" class="d-inline">
                          <input type="hidden" name="situacao" value="<?= $ativo ? 2 : 1 ?>">
                          <button type="submit" class="btn btn-sm <?= $ativo ? 'btn-outline-secondary' : 'btn-outline-success' ?> vc-situacao-aula"><?= $ativo ? 'Desativar' : 'Ativar' ?></button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php if (!empty($pode['salvar'])) :
              $esta = $ed && (int) $ed['periodo'] === $tid;
              $vf = $form && (int) $form['periodo'] === $tid ? $form : ($esta ? ['id' => $ed['id'], 'nome' => $ed['nome'], 'inicio' => $hm($ed['inicio']), 'termino' => $hm($ed['termino'])] : ['id' => 0, 'nome' => '', 'inicio' => '', 'termino' => '']); ?>
            <div class="card-footer">
              <form method="post" action="<?= URLADM ?>horarios-aula/salvar" class="vc-form-aula">
                <input type="hidden" name="periodo" value="<?= $h($tid) ?>">
                <input type="hidden" name="id" value="<?= $h((int) ($vf['id'] ?? 0)) ?>">
                <div class="fw-bold mb-2"><?= (int) ($vf['id'] ?? 0) ? 'Editar o horário' : 'Novo horário neste turno' ?></div>
                <div class="row g-2">
                  <div class="col-12"><label class="form-label" for="nome<?= $h($tid) ?>">Nome</label><input type="text" class="form-control" id="nome<?= $h($tid) ?>" name="nome" maxlength="40" placeholder="Ex.: 7ª aula" value="<?= $h($vf['nome'] ?? '') ?>" required></div>
                  <div class="col-6"><label class="form-label" for="inicio<?= $h($tid) ?>">Início</label><input type="time" class="form-control" id="inicio<?= $h($tid) ?>" name="inicio" value="<?= $h($vf['inicio'] ?? '') ?>" required></div>
                  <div class="col-6"><label class="form-label" for="termino<?= $h($tid) ?>">Fim</label><input type="time" class="form-control" id="termino<?= $h($tid) ?>" name="termino" value="<?= $h($vf['termino'] ?? '') ?>" required></div>
                </div>
                <div class="mt-3"><button type="submit" class="btn btn-primary vc-salvar-aula"><?= (int) ($vf['id'] ?? 0) ? 'Salvar a alteração' : 'Cadastrar' ?></button>
                  <?php if ((int) ($vf['id'] ?? 0)) : ?><a class="btn btn-link" href="<?= URLADM ?>horarios-aula/listar#turno<?= $h($tid) ?>">Cancelar</a><?php endif; ?></div>
              </form>
            </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>
