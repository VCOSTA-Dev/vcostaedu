<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
if(isset($this->dados['dados_info'])){
  $info = $this->dados['dados_info'];
}
?>
<div class="vc-pagina">
  <?php ob_start(); ?><a href="<?= htmlspecialchars((string) (URLADM . "horario-aula/listar/{$info['info'][0]['ano']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary rounded-circle" title="Voltar para os horários de aula"><i class="fas fa-arrow-left"></i></a> <?= htmlspecialchars((string) ("{$info['info'][0]['materia']} - {$info['info'][0]['codtur']}"), ENT_QUOTES, 'UTF-8') ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <span id="msgAlertaCaixa"></span>
      <div class="row">
        <div class="col-12">
          <div class="card shadow">
            <div class="card-body">
              <table class="table table-bordered table-hover" style="overflow-x:auto;" id="prof" name="prof">
                <thead class="table-dark">
                  <tr>
                    <th id="horario">Horário Diurno</th>
                    <th class="text-center">Seg</th>
                    <th class="text-center">Ter</th>
                    <th class="text-center">Qua</th>
                    <th class="text-center">Qui</th>
                    <th class="text-center">Sex</th>
                    <th class="text-center">Sáb</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  if(!empty($info['horariosDiurno'])){
                    foreach($info['horariosDiurno'] as $hor){
                      ?>
                    <tr>
                      <td rowspan="1"> Das <?= htmlspecialchars((string) ("{$hor['inicio']} até às {$hor['termino']}"), ENT_QUOTES, 'UTF-8') ?></td>
                      <td><input class="form-control" type="checkbox" id="ipt2<?= htmlspecialchars((string) ($hor['id']), ENT_QUOTES, 'UTF-8') ?>" onclick="btnHorarioAula(<?= htmlspecialchars((string) ($info['info'][0]['id']), ENT_QUOTES, 'UTF-8') ?>, 2, <?= htmlspecialchars((string) ($hor['id']), ENT_QUOTES, 'UTF-8') ?>)" value="2" <?= htmlspecialchars((string) ((!empty($hor['diaSegunda']) ? 'checked' : '' )), ENT_QUOTES, 'UTF-8') ?>></td>
                      <td><input class="form-control" type="checkbox" id="ipt3<?= htmlspecialchars((string) ($hor['id']), ENT_QUOTES, 'UTF-8') ?>" onclick="btnHorarioAula(<?= htmlspecialchars((string) ($info['info'][0]['id']), ENT_QUOTES, 'UTF-8') ?>, 3, <?= htmlspecialchars((string) ($hor['id']), ENT_QUOTES, 'UTF-8') ?>)" value="3" <?= htmlspecialchars((string) ((!empty($hor['diaTerca']) ? 'checked' : '' )), ENT_QUOTES, 'UTF-8') ?>></td>
                      <td><input class="form-control" type="checkbox" id="ipt4<?= htmlspecialchars((string) ($hor['id']), ENT_QUOTES, 'UTF-8') ?>" onclick="btnHorarioAula(<?= htmlspecialchars((string) ($info['info'][0]['id']), ENT_QUOTES, 'UTF-8') ?>, 4, <?= htmlspecialchars((string) ($hor['id']), ENT_QUOTES, 'UTF-8') ?>)" value="4" <?= htmlspecialchars((string) ((!empty($hor['diaQuarta']) ? 'checked' : '' )), ENT_QUOTES, 'UTF-8') ?>></td>
                      <td><input class="form-control" type="checkbox" id="ipt5<?= htmlspecialchars((string) ($hor['id']), ENT_QUOTES, 'UTF-8') ?>" onclick="btnHorarioAula(<?= htmlspecialchars((string) ($info['info'][0]['id']), ENT_QUOTES, 'UTF-8') ?>, 5, <?= htmlspecialchars((string) ($hor['id']), ENT_QUOTES, 'UTF-8') ?>)" value="5" <?= htmlspecialchars((string) ((!empty($hor['diaQuinta']) ? 'checked' : '' )), ENT_QUOTES, 'UTF-8') ?>></td>
                      <td><input class="form-control" type="checkbox" id="ipt6<?= htmlspecialchars((string) ($hor['id']), ENT_QUOTES, 'UTF-8') ?>" onclick="btnHorarioAula(<?= htmlspecialchars((string) ($info['info'][0]['id']), ENT_QUOTES, 'UTF-8') ?>, 6, <?= htmlspecialchars((string) ($hor['id']), ENT_QUOTES, 'UTF-8') ?>)" value="6" <?= htmlspecialchars((string) ((!empty($hor['diaSexta']) ? 'checked' : '' )), ENT_QUOTES, 'UTF-8') ?>></td>
                      <td><input class="form-control" type="checkbox" id="ipt7<?= htmlspecialchars((string) ($hor['id']), ENT_QUOTES, 'UTF-8') ?>" onclick="btnHorarioAula(<?= htmlspecialchars((string) ($info['info'][0]['id']), ENT_QUOTES, 'UTF-8') ?>, 7, <?= htmlspecialchars((string) ($hor['id']), ENT_QUOTES, 'UTF-8') ?>)" value="7" <?= htmlspecialchars((string) ((!empty($hor['diaSabado']) ? 'checked' : '' )), ENT_QUOTES, 'UTF-8') ?>></td>
                    </tr>
                      <?php
                    }
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>