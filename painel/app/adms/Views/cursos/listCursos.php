<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Cursos<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
              if ($this->dados['botao']['cad_cur']) {
              ?>
                <a href="<?= URLADM . "cursos/cadastrar" ?>" class="btn btn-outline-success me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-keyboard"></i> Cadastrar</a>
              <?php
              }
              ?>
              <a href="<?= URLADM . "materias/listar" ?>" class="btn btn-outline-info me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-chalkboard"></i> Matérias</a>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-12">
          <div class="card shadow">
            <div class="card-body">
              <?php $Ui = \App\adms\Models\helper\AdmsUi::class; $turnos = [1 => 'Diurno', 2 => 'Vespertino', 3 => 'Noturno']; /* E8c (R2/R3/R4) */ ?>
              <table id="tabelaNivel" class="table table-sm table-striped table-hover table-compacta lista-dados" data-titulo="Cursos" data-ordem='[[1,"asc"]]'>
                <thead>
                  <tr>
                    <th class="num">#</th>
                    <th>Cursos</th>
                    <th>Grade</th>
                    <th>Coordenador</th>
                    <th>Turno</th>
                    <th>Situação</th>
                    <th class="nao-exportar nao-ordenar text-end">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  if (!empty($this->dados['listaCursos'])) {
                    foreach ($this->dados['listaCursos'] as $cur) {
                  ?>
                      <tr>
                        <td class="num"><?= htmlspecialchars((string) ((int) $cur['id']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($cur['nome']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($cur['grade']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= implode('<br>', array_map(fn($x) => htmlspecialchars($x, ENT_QUOTES, 'UTF-8'), explode('<br>', (string) ($cur['coordenadores'])))) ?></td>
                        <td><?= \App\adms\Models\modalidades\AdmsModalidades::ehEad((int) $cur['modalidade_id']) ? '<span class="text-muted" title="Não se aplica: curso EaD">—</span>' : htmlspecialchars(\App\adms\Models\modalidades\AdmsModalidades::turnos()[(int) $cur['periodo']] ?? '—', ENT_QUOTES, 'UTF-8') /* E8d (P-2) */ ?></td>
                        <td><span class="badge bg-<?= htmlspecialchars((string) ($cur['corSituacao']), ENT_QUOTES, 'UTF-8') ?>-lt"><?= htmlspecialchars((string) ($cur['nomeSituacao']), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="acoes-linha">
                          <?php if ($this->dados['botao']['add_coord']) : ?>
                            <?= $Ui::acaoModal('acao', '#addCoordenadorCurso', ['curso' => (int) $cur['id'], 'coordenador' => '[' . $cur['coordenadoresId'] . ']', 'corpo' => $cur['nome']], 'Coordenador do curso', null, ['icone' => 'fas fa-user-plus', 'classe' => 'btn-outline-success']) ?>
                          <?php endif; ?>
                          <?php if ($this->dados['botao']['vis_cur']) : ?>
                            <?= $Ui::acao('ver', URLADM . "cursos/visualizar/{$cur['id']}", 'Abrir o curso') ?>
                          <?php endif; ?>
                          <?php if ($this->dados['botao']['edit_cur']) : ?>
                            <?= $Ui::acao('editar', URLADM . "cursos/editar/{$cur['id']}", 'Editar o curso') ?>
                          <?php endif; ?>
                          <?php /* E3a: o botão "Modelo" saiu (clicar em "Cadastrar" APAGAVA o curso). A grade é feita na Matriz. */ ?>
                          <?php if ($this->dados['botao']['ver_matriz']) : ?>
                            <?= $Ui::acao('acao', URLADM . 'matriz/listar#curso-' . (int) $cur['id'], 'Matriz curricular do curso', null, ['icone' => 'fas fa-sitemap', 'classe' => 'btn-outline-success']) ?>
                          <?php endif; ?>
                          <?php if (!empty($this->dados['botao']['sit_cur'])) : /* E8d (P-8): inativar/reativar */
                            $inativo = (int) ($cur['situacaoId'] ?? 1) === 2; ?>
                            <?= $inativo
                              ? $Ui::acaoPost('reativar', URLADM . 'cursos/situacao/' . (int) $cur['id'], ['situacao' => 1], 'Reativar o curso: volta às listas de escolha')
                              : $Ui::acaoPost('inativar', URLADM . 'cursos/situacao/' . (int) $cur['id'], ['situacao' => 2], 'Inativar o curso: sai das listas de escolha; o que já foi feito não muda', null, ['confirmar' => 'Inativar o curso ' . $cur['nome'] . '? Ele sai das listas de nova grade, nova oferta e vínculo. Ofertas, notas e históricos não mudam.']) ?>
                          <?php endif; ?>
                          <?php if ($this->dados['botao']['del_cur']) : /* E8c (R4, D-270): curso em uso não é apagado */ ?>
                            <?= $Ui::acaoModal('excluir', '#apagarItem', ['titulo' => 'EXCLUIR - CURSO', 'confirm' => 'Tem certeza de que deseja excluir o curso: ' . $cur['nome'] . '?', 'identidade' => (int) $cur['id']], 'Apagar o curso', \App\adms\Models\helper\AdmsUsos::curso((int) $cur['id'])) ?>
                          <?php endif; ?>
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

<div class="modal fade" id="apagarItem" tabindex="-1" aria-labelledby="apagarItemLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="cabecalhoItem">DESATIVAR EMPRESA</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= URLADM . "cursos/apagar"; ?>" enctype="multipart/form-data" novalidate>
        <div class="modal-body">
          <div class="mb-3">
            <input type="hidden" name="id" class="form-control" value required>
          </div>
          <p class="corpoItem"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <input type="submit" name="ApagarCurso" class="btn btn-danger botaoEnvia" value="Apagar">
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="addCoordenadorCurso" tabindex="-1" aria-labelledby="addCoordenadorCursoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addCoordenadorCursotitle">ADICIONAR COORDENADOR</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= URLADM . "cursos/cadastrar-usuario/"; ?>" enctype="multipart/form-data" novalidate>
      <div class="modal-body">
          <input type="hidden" name="curso_id" id="curso_id" required>
          <div class="mb-3 col-12">
            <p class="corpoItem"></p>
          </div>
          <div class="mb-3 col-12">
            <select name="usuario_id" id="usuario_id" class="form-select select2" required>
              <option value="">Selecione o usuário</option>
              <?php
              foreach ($this->dados['select']['coordenadores'] as $educa) {
                echo "<option value='{$educa['id']}'>{$educa['nome']}</option>";
              }
              ?>
            </select>
          </div>
          <div class="mb-3 col-12">
            <select name="posicao_id" id="posicao_id" class="form-select select2" required>
              <option value="0">Selecione a posição</option>
              <?php
              foreach ($this->dados['select']['posicoes'] as $pos) {
                echo "<option value='{$pos['id']}'>{$pos['nome']}</option>";
              }
              ?>
            </select>
          </div>
      </div>
      <div class="modal-footer">
        <input name="CadCoordenadorCurso" type="submit" class="btn btn-primary BotaoEnvia" value="Salvar">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
      </form>
    </div>
  </div>
</div>
