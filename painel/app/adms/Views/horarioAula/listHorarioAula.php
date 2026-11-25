<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Horário das Disciplinas<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <div class="row">
        <div class="col-12">
          <div class="card shadow">
            <div class="card-body">
              <form class="form-horizontal" method="POST" action="">
                <div class="mb-3 row">
                  <label class="h4 col-sm-6 col-md-3">Filtro por Ano</label>
                  <div class="col-sm-6 col-md-9">
                    <select name="pesqAno" id="pesqAno" class="form-select select2">
                      <option value="">Selecione o Ano</option>
                      <?php
                      foreach ($this->dados['select']['periodos'] as $perio) {
                        if ((isset($this->dados['listaDisciplinas']['anoPeriodo']) && !empty($this->dados['listaDisciplinas']['anoPeriodo'])) && ($this->dados['listaDisciplinas']['anoPeriodo'] == $perio['ano'])) {
                          echo "<option value='{$perio['ano']}' selected>{$perio['ano']}</option>";
                        } else {
                          echo "<option value='{$perio['ano']}'>{$perio['ano']}</option>";
                        }
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <input type="submit" name="PesqPeriodo" class="btn btn-primary botaoEnvia" value="Pesquisar">
              </form>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-12">
          <div class="card shadow">
            <div class="card-body">
              <?php
              if ($this->dados['botao']['list_disc']) {
              ?>
                <a href="<?= URLADM . "disciplinas/listar" ?>" class="btn btn-outline-info me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-university"></i> Disciplinas</a>
              <?php
              }
              if ($this->dados['botao']['list_sala']) {
              ?>
                <a href="<?= URLADM . "salas-de-aula/listar" ?>" class="btn btn-outline-primary me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-laptop-house"></i> Salas de Aula</a>
              <?php
              }
              ?>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-12">
          <div class="card shadow">
            <input type="hidden" id="paginaConsulta" value="<?= URLADM . "horario-aula/visualizar/" ?>">
            <div class="card-body">
              <table id="tabelaDisciplinas" class="table table-bordered table-hover table-sm table-striped table-compacta lista-dados" data-ordem='[[0,"asc"]]'>
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Codmat</th>
                    <th>Materia</th>
                    <th>Curso</th>
                    <th>Turma</th>
                    <th>Grade</th>
                    <th>Professores</th>
                    <th>Sala</th>
                    <th>Turno</th>
                    <th>Dia Semana</th>
                    <th class="text-center">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  if (!empty($this->dados['listaDisciplinas']['disciplinas'])) {
                    foreach ($this->dados['listaDisciplinas']['disciplinas'] as $disc) {
                  ?>
                      <tr>
                        <td><?= htmlspecialchars((string) ($disc['id']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($disc['codmat']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($disc['materia']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($disc['curso']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($disc['codtur']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($disc['grade']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= implode('<br>', array_map(fn($x) => htmlspecialchars($x, ENT_QUOTES, 'UTF-8'), explode('<br>', (string) ($disc['professores'])))) ?></td>
                        <td><span id="salasDisc<?= htmlspecialchars((string) ($disc['id']), ENT_QUOTES, 'UTF-8') ?>"><?= implode('<br>', array_map(fn($x) => htmlspecialchars($x, ENT_QUOTES, 'UTF-8'), explode('<br>', (string) ($disc['salas'] ?? '')))) ?></span><?= htmlspecialchars((string) ((!empty($disc['salas']) ? $disc['salas'] : '')), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= \App\adms\Models\modalidades\AdmsModalidades::ehEad((int) ($disc['modalidadeOferta'] ?? 0)) ? '<span class="text-muted" title="Não se aplica: oferta EaD">—</span>' : htmlspecialchars((string) $disc['periodoAula'], ENT_QUOTES, 'UTF-8') /* E8d (P-2) */ ?></td>
                        <td><span id="horarioDias<?= htmlspecialchars((string) ($disc['id']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($disc['dia']), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="text-center">
                          <?php
                          if ($this->dados['botao']['cad_sala']) {
                          ?>
                            <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#horarioSala" data-corpos="<?= htmlspecialchars((string) ((!empty($disc['salas']) ? 'Alterar sala da ' : 'Adicionar sala na ') . "disciplina: {$disc['materia']}"), ENT_QUOTES, 'UTF-8') ?>" data-identidade="<?= htmlspecialchars((string) ($disc['id']), ENT_QUOTES, 'UTF-8') ?>" data-alunos="<?= htmlspecialchars((string) ($disc['alunos']), ENT_QUOTES, 'UTF-8') ?>" data-acao="1" data-sala="[<?= htmlspecialchars((string) ($disc['salasId']), ENT_QUOTES, 'UTF-8') ?>]"><i class='fab fa-chromecast'></i> Sala</button>
                          <?php
                          }
                          if ($this->dados['botao']['edit_hora']) {
                          ?>
                            <button type="button" data-ttip="tooltip" data-bs-placement="top" title="Horário de Aula" class="btn btn-sm btn-outline-primary" onclick="horarioDisciplina(<?= htmlspecialchars((string) ($disc['id']), ENT_QUOTES, 'UTF-8') ?>)"><i class="fas fa-calendar-alt"></i> Horário</button>
                          <?php
                          }
                          ?>
                        </td>
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

<div class="modal fade" id="horarioSala" tabindex="-1" aria-labelledby="horarioSalaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">ATUALIZAR A SALA</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= URLADM . "horario-aula/cadastrar-sala/" . (isset($this->dados['listaDisciplinas']['anoPeriodo']) ? $this->dados['listaDisciplinas']['anoPeriodo'] : ''); ?>" enctype="multipart/form-data" novalidate>
        <div class="modal-body">
          <span class="corpoItem"></span>
          <input type="hidden" name="rm_prof_mat_id" value required>
          <input type="hidden" name="alunos" value required>
          <div class="mb-3">
            <label class="form-label" for="sala">Sala</label>
            <select name="sala[]" id="sala" class="form-select select2" multiple="multiple">
              <option value="">Selecione as salas</option>
              <?php
              if (!empty($this->dados['select']['salas'])) {
                foreach ($this->dados['select']['salas'] as $sa) {
                  echo "<option value='{$sa['id']}'>{$sa['nome']}</option>";
                }
              }
              ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <input type="submit" name="AddSalaDisc" class="btn btn-primary botaoEnvia" value="Atualizar">
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="cadDiaSemanaAula" tabindex="-1" aria-labelledby="cadDiaSemanaAulaLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">HORÁRIO DA DISCIPLINA</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
        <div class="modal-body">
          <span id="msgErro"></span>
          <span id="DiscHorario"></span>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </div>
  </div>
</div>

<script>
  /* E8c: a ordem da lista está em data-ordem (lista-dados) */
</script>