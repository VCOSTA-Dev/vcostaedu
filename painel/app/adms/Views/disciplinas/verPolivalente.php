<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
if ($this->dados['botao']['list_disc']) {
  $btnDisciplinaVoltar = "disciplinas/listar/";
  $textDisciplinaVoltar = "Todas as Disciplinas";
} else {
  if ($this->dados['botao']['my_disc']) {
    $btnDisciplinaVoltar = "minhas-disciplinas/listar/";
    $textDisciplinaVoltar = "Minhas Disciplinas";
  }
}
if (!empty($this->dados['info_dado']['info'])) {
  $info = $this->dados['info_dado'];

?>
  <div class="vc-pagina">
    <?php ob_start(); ?><a href="<?= htmlspecialchars((string) (URLADM . "disciplinas/visualizar/{$info['info'][0]['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary rounded-circle" title="Voltar"><i class="fas fa-arrow-left"></i></a> <?= htmlspecialchars((string) ("{$info['info'][0]['materia']} - {$info['info'][0]['codtur']}"), ENT_QUOTES, 'UTF-8') ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
                <?php
                if ($this->dados['botao']['vis_disc']) {
                ?>
                  <a href="<?= htmlspecialchars((string) (URLADM . "disciplinas/visualizar/{$info['info'][0]['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-dark me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-arrow-left"></i> Voltar disciplina</a>
                <?php
                }
                ?>
                <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#addDisciplinaPoliv"><i class="fas fa-link"></i> Vincular Disciplinas</button>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="card shadow">
              <div class="card-body">
                <div class="row">
                  <div class="col-12 col-md-12 col-lg-8 order-2 order-md-1">
                    <div class="row">
                      <div class="col-12 col-sm-4">
                        <div class="card card-sm vc-indicador bg-light">
                          <div class="vc-indicador-conteudo">
                            <span class="vc-indicador-texto text-center text-muted"><button type="button" class="border-0 bg-light" data-bs-toggle="modal" data-bs-target="#listaBuscar"> Alunos na Turma <span class="vc-indicador-numero text-center text-muted mb-0"><?= COUNT($this->dados['info_dado']['qtdeAluno']) ?> </span></button></span>
                          </div>
                          <span class="align-bottom">
                            <br>
                            <button type="button" class="border-0 bg-light" data-bs-toggle="modal" data-bs-target="#listaBuscar"><i class="fas fa-search"></i></button>
                          </span>
                        </div>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-12">
                        <div class="card">
                          <div class="card-body p-0">
                            <table class="table table-sm tbale-hover table-striped">
                              <thead>
                                <tr>
                                  <th>#</th>
                                  <th>Matéria</th>
                                  <th>Turma</th>
                                  <th>Curso</th>
                                  <th class="text-end">Ações</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php
                                if (!empty($this->dados['list_disc']['polivalentes'])) {
                                  foreach ($this->dados['list_disc']['polivalentes'] as $dis) {
                                ?>
                                  <tr>
                                    <td><?= htmlspecialchars((string) ($dis['rm_prof_mat_id']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($dis['materia']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($dis['codtur']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($dis['ensino']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end">
                                      <a href="<?= htmlspecialchars((string) (URLADM."disciplinas/visualizar/{$dis['rm_prof_mat_id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-eye"></i> Visualizar</a>
                                      <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#apagarItem" data-titulo="EXCLUIR - DISCIPLINA POLIVALENTE" data-confirm="Tem certeza de que deseja excluir o vínculo: <?= htmlspecialchars((string) ($dis['materia']), ENT_QUOTES, 'UTF-8') ?> com a <?= htmlspecialchars((string) ($info['info'][0]['materia']), ENT_QUOTES, 'UTF-8') ?> ?" data-identidade="<?= htmlspecialchars((string) ($dis['id']), ENT_QUOTES, 'UTF-8') ?>"><i class="far fa-trash-alt"></i> Apagar</button>
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
                  <div class="col-12 col-md-12 col-lg-4 order-1 order-md-2">
                    <h3 class="text-secondary"><i class="fas fa-university"></i><?= htmlspecialchars((string) ($info['info'][0]['materia']), ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="text-muted">
                      Sala: <?= implode('<br>', array_map(fn($x) => htmlspecialchars($x, ENT_QUOTES, 'UTF-8'), explode('<br>', (string) ($info['info'][0]['salas'])))) ?><br></p>
                    <div class="text-muted">
                      <p class="text-sm"><?= htmlspecialchars(\App\adms\Models\helper\AdmsInstituicao::nome()) ?>
                      </p>
                      <p class="text-sm">Coordenador(a) do curso
                        <b class="d-block"><?= htmlspecialchars((string) ($info['info'][0]['coordenacao']), ENT_QUOTES, 'UTF-8') ?></b>
                      </p>
                      <p class="text-sm">Professor
                        <b class="d-block"><?= implode('<br>', array_map(fn($x) => htmlspecialchars($x, ENT_QUOTES, 'UTF-8'), explode('<br>', (string) ($info['info'][0]['professores'])))) ?></b>
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <div class="modal fade" id="listaBuscar" tabindex="-1" aria-labelledby="listaBuscarLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="listaBuscarTitulo">Lista de Alunos da Disciplina</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <span id="msgErro"></span>
          <table class="table table-bordered table-striped table-sm table-hover table-compacta lista-dados" data-paginar="nao" data-ordem='[]'>
            <thead>
              <th>Nº</th>
              <th>RM</th>
              <th>Nome</th>
              <th>Situação</th>
            </thead>
            <tbody>
              <?php
              foreach ($this->dados['info_dado']['qtdeAluno'] as $aluno) {
                extract($aluno);
              ?>
                <tr>
                  <td><?= htmlspecialchars((string) ($ordem), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($rm), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($nome_aluno), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($observacao), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
              <?php
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addDisciplinaPoliv" tabindex="-1" aria-labelledby="addDisciplinaPolivLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addDisciplinaPolivTitulo">ADICIONAR DISCIPLINAS POLIVALENTE</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="<?= htmlspecialchars((string) (URLADM . "disciplinas/adiciona-polivalencia/{$info['info'][0]['id']}"), ENT_QUOTES, 'UTF-8') ?>" method="POST" enctype="multipart/form-data" novalidate>
          <div class="modal-body">
            <input type="hidden" name="rm_polivalente" value="<?= htmlspecialchars((string) ($info['info'][0]['id']), ENT_QUOTES, 'UTF-8') ?>">
            <div class="row g-2">
              <div class="mb-3 col-12">
                <label class="form-label" for="rm_prof_validos">Curso</label>
                <select name="rm_prof_validos[]" id="rm_prof_valido" class="form-select select2" multiple="multiple" required>
                  <option value="">Escolha o curso</option>
                  <?php
                  foreach ($this->dados['list_disc']['selecoes'] as $sec) {
                    echo "<option value='{$sec['id']}'>{$sec['materia']} - {$sec['codtur']} - {$sec['professores']}</option>";
                  }
                  ?>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <input type="submit" name="AddDisciPoliva" class="btn btn-primary botaoEnvia" value="Salvar">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

<div class="modal fade" id="apagarItem" tabindex="-1" aria-labelledby="apagarItemLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="cabecalhoItem">DESATIVAR DISCIPLINA</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= htmlspecialchars((string) (URLADM . "disciplinas/apagar-polivalencia/{$info['info'][0]['id']}"), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" novalidate>
        <div class="modal-body">
          <div class="mb-3">
            <input type="hidden" name="id" class="form-control" value required>
          </div>
          <p class="corpoItem"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <input type="submit" name="ApagarPolivalente" class="btn btn-danger botaoEnvia" value="Apagar">
        </div>
      </form>
    </div>
  </div>
</div>
<?php
} else {
  $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-hidden='true'></button>Erro: Não foi possível encontrar a Disciplina!</div>";
  $urlDestino = URLADM . "{$btnDisciplinaVoltar}{$info['info'][0]['ano']}";
  header("Location: $urlDestino");
}
?>