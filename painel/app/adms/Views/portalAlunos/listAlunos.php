<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
if(isset($this->dados['listPortalAlunos'])){
  $infos = $this->dados['listPortalAlunos'];
}
?>
<div class="vc-pagina">
  <?php ob_start(); ?><a href="<?= URLADM . "disciplinas/listar" ?>" class="btn btn-outline-primary rounded-circle" data-ttip="tooltip" data-bs-placement="top" title="Voltar"><i class="fas fa-arrow-left"></i></a> Portal Alunos<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
              <form method="POST" action="" enctype="multipart/form-data" novalidate>
                <div class="row">
                  <div class="col-md-3">
                    <div class="mb-3">
                      <label class="form-label" for="ano">Ano</label>
                      <select class="form-select select2" name="ano" id="ano" required>
                        <option value="0">Selecione o Ano</option>
                        <?php
                        foreach ($this->dados['select']['periodos'] as $perio) {
                          if ((isset($infos['pesAno']) && !empty($infos['pesAno'])) && ($infos['pesAno'] == $perio['ano'])) {
                            echo "<option value='{$perio['ano']}' selected>{$perio['ano']}</option>";
                          } else {
                            echo "<option value='{$perio['ano']}'>{$perio['ano']}</option>";
                          }
                        }
                        ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label" for="ensino">Curso</label>
                      <select name="ensino" id="ensino" class="form-select select2" onchange="buscaSerie('ensino','turma')">
                      <option value="0">Selecione o curso</option>
                      <?php
                      foreach ($this->dados['select']['cursos'] as $cur) {
                        if ((isset($infos['pesEnsino']) && !empty($infos['pesEnsino'])) && ($infos['pesEnsino'] == $cur['id'])) {
                          echo "<option value='{$cur['id']}' selected>{$cur['nome']}</option>";
                        } else {
                          echo "<option value='{$cur['id']}'>{$cur['nome']}</option>";
                        }
                      }
                      ?>
                    </select>
                    </div>
                  </div>
                  <?php $semMod = \App\adms\Models\helper\AdmsModuloInterno::ativo(); /* E8c (D-268/D-269): sem módulo e sem "período" */ ?>
                  <?php if (!$semMod) : ?>
                  <div class="col-md-3">
                    <div class="mb-3">
                      <label class="form-label" for="turma">Módulo</label>
                      <select name="turma" id="turma" class="form-select select2">
                      <option value="0">Selecione o Módulo</option>
                      <?php
                      foreach ($this->dados['select']['turmas'] as $perio) {
                        if ((isset($infos['pesTurma']) && !empty($infos['pesTurma'])) && ($infos['pesTurma'] == $perio['id'])) {
                          echo "<option value='{$perio['id']}' selected>{$perio['codnome']}</option>";
                        } else {
                          echo "<option value='{$perio['id']}'>{$perio['codnome']}</option>";
                        }
                      }
                      ?>
                    </select>
                    </div>
                  </div>
                  <?php endif; ?>
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
              <table class="table table-bordered table-sm table-striped table-hover table-compacta lista-dados" data-ordem='[[1,"asc"]]'>
                <thead>
                  <tr>
                    <th>Nº</th>
                    <th>RM</th>
                    <th>Nome Aluno</th>
                    <th>Curso</th>
                    <?php if (!$semMod) : ?><th>Módulo</th><?php endif; ?>
                    <th>Turma</th>
                    <th>Observação</th>
                    <th>Qtds Disciplinas</th>
                    <th class="text-center">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  if (!empty($this->dados['listPortalAlunos']['portal'])) {
                    foreach ($this->dados['listPortalAlunos']['portal'] as $pa) {
                  ?>
                      <tr>
                        <td><?= htmlspecialchars((string) ($pa['ordem']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($pa['rm']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($pa['nome']), ENT_QUOTES, 'UTF-8') ?><?php if ((int) ($pa['qtdMatriculas'] ?? 1) > 1) : ?> <span class="badge bg-info-lt" title="O aluno tem <?= htmlspecialchars((string) ((int) $pa['qtdMatriculas']), ENT_QUOTES, 'UTF-8') ?> matrículas neste ano: a linha mostra a mais recente">mais recente de <?= htmlspecialchars((string) ((int) $pa['qtdMatriculas']), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></td>
                        <td><?= htmlspecialchars((string) ($pa['ensino']), ENT_QUOTES, 'UTF-8') ?></td>
                        <?php if (!$semMod) : ?><td><?= htmlspecialchars((string) ($pa['turma']), ENT_QUOTES, 'UTF-8') ?></td><?php endif; ?>
                        <td><?= htmlspecialchars((string) ($pa['grupo']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($pa['observacao']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($pa['qtdDisciplinas']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                          <?php
                          if ($this->dados['botao']['ord_al']) {
                          ?>
                              <button type="button" class="btn btn-sm btn-outline-dark" data-ttip="tooltip" data-bs-placement="top" title="Editar a ordem do aluno na lista" data-bs-toggle="modal" data-bs-target="#ordemPortalAlunos" data-corpo="<h5><strong>RM:</strong> <?= htmlspecialchars((string) ($pa['rm']), ENT_QUOTES, 'UTF-8') ?><br><strong>Nome:</strong> <?= htmlspecialchars((string) ($pa['nome']), ENT_QUOTES, 'UTF-8') ?><br><strong>Ensino:</strong> <?= htmlspecialchars((string) ($pa['ensino']), ENT_QUOTES, 'UTF-8') ?><br><br>Alterar o número de chamada do(a) Aluno(a) ?" data-rm="<?= htmlspecialchars((string) ($pa['rm']), ENT_QUOTES, 'UTF-8') ?>" data-ano="<?= htmlspecialchars((string) ($pa['ano']), ENT_QUOTES, 'UTF-8') ?>" data-ordem="<?= htmlspecialchars((string) ($pa['ordem']), ENT_QUOTES, 'UTF-8') ?>" data-curso="<?= htmlspecialchars((string) ($pa['ensino_id']), ENT_QUOTES, 'UTF-8') ?>" data-periodo="<?= htmlspecialchars((string) ($pa['periodo']), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-list-ol"></i> Nº</button>
                            <?php
                          }
                          if ($this->dados['botao']['edit_obs']) {
                          ?>
                          <button type="button" class="btn btn-sm btn-outline-warning" data-ttip="tooltip" data-bs-placement="top" title="Observação" data-bs-toggle="modal" data-bs-target="#cadObsPortalAlunos" data-corpo="<h5><strong>RM:</strong> <?= htmlspecialchars((string) ($pa['rm']), ENT_QUOTES, 'UTF-8') ?><br><strong>Nome:</strong> <?= htmlspecialchars((string) ($pa['nome']), ENT_QUOTES, 'UTF-8') ?><br><strong>Ensino:</strong> <?= htmlspecialchars((string) ($pa['ensino']), ENT_QUOTES, 'UTF-8') ?></h5>" data-rm="<?= htmlspecialchars((string) ($pa['rm']), ENT_QUOTES, 'UTF-8') ?>" data-ano="<?= htmlspecialchars((string) ($pa['ano']), ENT_QUOTES, 'UTF-8') ?>" data-obs="<?= htmlspecialchars((string) ($pa['observacao']), ENT_QUOTES, 'UTF-8') ?>" data-curso="<?= htmlspecialchars((string) ($pa['ensino_id']), ENT_QUOTES, 'UTF-8') ?>" data-periodo="<?= htmlspecialchars((string) ($pa['periodo']), ENT_QUOTES, 'UTF-8') ?>"><i class="far fa-edit"></i> Observação</button>
                            <?php
                          }
                          if ($this->dados['botao']['vis_aluno']) {
                          ?>
                          <a href="<?= htmlspecialchars((string) (URLADM . "portal-alunos/visualizar/{$pa['rm']}?ano={$pa['ano']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary" data-ttip="tooltip" data-bs-placement="top" title="Visualizar Aluno" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-eye"></i> Visualizar</a>
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
              <p class="text-secondary small mt-2 mb-0" id="notaEnturmacaoRecente">Uma linha por aluno. Quando o aluno tem mais de uma matrícula ou enturmação no ano, a lista mostra a mais recente.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" id="ordemPortalAlunos" tabindex="-1" aria-labelledby="ordemPortalAlunosLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cabecalhoItem">ORDEM DO ALUNO NA LISTA</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= URLADM . "portal-alunos/ordenacao/"; ?>" enctype="multipart/form-data" novalidate>
        <div class="modal-body">
            <input type="hidden" name="pagina" value="<?= htmlspecialchars((string) ("portal-alunos/listar/".(isset($infos['pesAno']) && !empty($infos['pesAno']) ? $infos['pesAno'] : 0)."?ensino=".(isset($infos['pesEnsino']) && !empty($infos['pesEnsino']) ? $infos['pesEnsino'] : 0)."&turma=".(isset($infos['pesTurma']) && !empty($infos['pesTurma']) ? $infos['pesTurma'] : 0)), ENT_QUOTES, 'UTF-8') ?>" required>
            <input type="hidden" name="rm" value required>
            <input type="hidden" name="ano" value required>
            <input type="hidden" name="curso_id" value required>
            <input type="hidden" name="periodo" value required>
          <p class="corpoItem"></p>
          <div class="mb-3">
            <label class="form-label" for="ordem">ORDEM</label>
            <input type="number" name="ordem" id="ordem" min="1" class="form-control" value required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <input type="submit" name="OrdenarPortalAluno" class="btn btn-primary botaoEnvia" value="Atualizar">
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="cadObsPortalAlunos" tabindex="-1" aria-labelledby="cadObsPortalAlunosLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">EDITAR OBSERVAÇÃO DO(A) ALUNO(A)</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= URLADM . "portal-alunos/add-observacao/"; ?>" enctype="multipart/form-data" novalidate>
        <div class="modal-body">
            <input type="hidden" name="pagina" value="<?= htmlspecialchars((string) ("portal-alunos/listar/".(isset($infos['pesAno']) && !empty($infos['pesAno']) ? $infos['pesAno'] : 0)."?ensino=".(isset($infos['pesEnsino']) && !empty($infos['pesEnsino']) ? $infos['pesEnsino'] : 0)."&turma=".(isset($infos['pesTurma']) && !empty($infos['pesTurma']) ? $infos['pesTurma'] : 0)), ENT_QUOTES, 'UTF-8') ?>" required>
            <input type="hidden" name="rm" value required>
            <input type="hidden" name="ano" value required>
            <input type="hidden" name="curso_id" value required>
            <input type="hidden" name="periodo" value required>
          <p class="corpoItem"></p>
          <div class="mb-3">
            <label class="form-label" for="observacao">Observação</label>
            <input type="text" name="observacao" id="cadobservacao" class="form-control" value required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <input type="submit" name="EditObservaAluno" class="btn btn-primary botaoEnvia" value="Atualizar">
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  /* E8c: a ordem da lista está em data-ordem (lista-dados) */
</script>