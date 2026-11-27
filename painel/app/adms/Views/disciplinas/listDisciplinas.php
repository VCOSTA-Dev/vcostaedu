<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Disciplinas<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
                      /* 1.18.1 (D-483): o ano que a lista mostra está SEMPRE no filtro, marcado — inclusive depois de salvar o
                         professor ou com o ano fora da bandeira "corrente do acadêmico" (antes, o filtro voltava a "Selecione o Ano") */
                      $periodosFiltro = (array) ($this->dados['select']['periodos'] ?? []);
                      $anoMostrado = (int) ($this->dados['listaDisciplinas']['anoPeriodo'] ?? 0);
                      if ($anoMostrado > 0 && !in_array($anoMostrado, array_map('intval', array_column($periodosFiltro, 'ano')), true)) {
                        $periodosFiltro[] = ['ano' => $anoMostrado];
                        usort($periodosFiltro, fn($a, $b) => (int) $a['ano'] <=> (int) $b['ano']);
                      }
                      foreach ($periodosFiltro as $perio) {
                        if ($anoMostrado > 0 && $anoMostrado === (int) $perio['ano']) {
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
              if (!empty($this->dados['botao']['gerar_oferta'])) {
                echo '<a href="' . URLADM . 'disciplinas/gerar-oferta" class="btn btn-outline-success me-1"><i class="fas fa-calendar-plus"></i> Gerar oferta do ano</a>';
              }
              if ($this->dados['botao']['cad_disc']) {
              ?>
                <a href="<?= URLADM . "disciplinas/cadastrar" ?>" class="btn btn-outline-success me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-keyboard"></i> Cadastrar</a>
              <?php
              }
              ?>
              <button class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#configCursos"><i class="fas fa-cogs"></i> Configuração</button>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-12">
          <div class="card shadow">
            <div class="card-body">
              <?php $ano = $this->dados['listaDisciplinas']['anoPeriodo'] ?? ''; $h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); /* E8c (R2/R3/R4) */ ?>
              <table id="tabelaDisciplinas" class="table table-sm table-striped table-hover table-compacta lista-dados" data-titulo="Disciplinas <?= $h($ano) ?>" data-ordem='[[2,"asc"]]'><?php /* 1.103.1 (D-138): "#" na primeira coluna; a ordem de partida continua pela disciplina */ ?>
                <thead>
                  <?php $escolaLista = \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola(); /* 2.5 (D-91): na escola, disciplina e segmento */ ?>
                  <tr><th class="num">#</th><th>Código</th><th><?= $escolaLista || \App\adms\Models\helper\AdmsTipoInstituicao::ehLivre() ? 'Disciplina' : 'Matéria' /* D-106 */ ?></th><th><?= $escolaLista ? 'Segmento' : 'Curso' ?></th><th>Turma</th><th class="num">Alunos</th><th>Situação</th><th>Professores</th><th class="nao-exportar nao-ordenar text-end">Ações</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($this->dados['listaDisciplinas']['disciplinas'] ?? [] as $disc) :
                    $enc = !empty($disc['encerrada_em']); $n = (int) ($disc['alunos'] ?? 0); ?>
                    <tr data-oferta="<?= (int) $disc['id'] ?>">
                      <td class="num"><?= (int) $disc['id'] ?></td>
                      <td><?= $h($disc['codmat']) ?></td>
                      <td><?= !empty($this->dados['botao']['vis_disc']) ? '<a href="' . URLADM . 'disciplinas/visualizar/' . (int) $disc['id'] . '">' . $h($disc['materia']) . '</a>' : $h($disc['materia']) ?></td>
                      <td><?= $h($disc['curso']) ?></td>
                      <td><?= $h($disc['codtur']) ?></td>
                      <td class="num"><?= htmlspecialchars((string) ($n), ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= \App\adms\Models\helper\AdmsUi::etiqueta($enc ? 'Encerrada' : 'Aberta') ?></td>
                      <td><?= $disc['professores'] ? implode('<br>', array_map(fn($x) => htmlspecialchars($x, ENT_QUOTES, 'UTF-8'), explode('<br>', (string) $disc['professores']))) : '<span class="text-muted">sem professor</span>' ?></td>
                      <td class="acoes-linha">
                        <?php if (!empty($this->dados['botao']['add_prof'])) : ?>
                          <button type="button" class="btn btn-sm btn-acao btn-outline-success" title="Professores da disciplina" data-bs-toggle="modal" data-bs-target="#adicionarProfessor" data-identidade="<?= htmlspecialchars((string) ((int) $disc['id']), ENT_QUOTES, 'UTF-8') ?>" data-professores="[<?= $h($disc['professoresId']) ?>]" data-titulo="<?= $h($disc['materia']) ?>"><i class="fas fa-user-plus"></i></button>
                        <?php endif; ?>
                        <?= !empty($this->dados['botao']['vis_disc']) ? \App\adms\Models\helper\AdmsUi::acao('ver', URLADM . 'disciplinas/visualizar/' . (int) $disc['id'], 'Abrir a disciplina (atividades, notas, faltas, encerramento)') : '' ?>
                        <?= !empty($this->dados['botao']['edit_disc']) ? \App\adms\Models\helper\AdmsUi::acao('editar', URLADM . 'disciplinas/editar/' . (int) $disc['id'], 'Editar (datas de realização, encontros)', $enc ? 'Disciplina encerrada: reabra antes de editar' : null) : '' ?>
                        <?= !empty($this->dados['botao']['del_disc']) ? \App\adms\Models\helper\AdmsUi::acaoPost('excluir', URLADM . 'disciplinas/apagar/' . $h($ano), ['id' => (int) $disc['id'], 'ApagarDisc' => 'Apagar'], 'Apagar a disciplina', $n > 0 ? "Tem {$n} aluno(s) enturmado(s): não pode apagar" : null, ['confirmar' => 'Apagar a disciplina ' . $disc['materia'] . ' (' . $disc['codtur'] . ')?']) : '' ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" id="adicionarProfessor" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Adicionar Professor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <span id="msgCad"></span>
        <form method="POST" action="<?= htmlspecialchars((string) (URLADM . "disciplinas/adiciona-professor/{$this->dados['listaDisciplinas']['anoPeriodo']}"), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" novalidate>
          <div class="mb-3">
            <input type="hidden" name="rm_prof_mat_id" id="rm_prof_mat_id" required>
          </div>
          <p class="corpoItem"></p>
          <div class="mb-3">
            <div class="mb-3 col-md-12">
              <select name="professores[]" id="professores" class="form-select select2" multiple="multiple">
                <?php
                foreach ($this->dados['select']['professores'] as $educa) {
                  echo "<option value='{$educa['id']}'>{$educa['nome']}</option>";
                }
                ?>
              </select>
              <small>Importante: sempre insira todos os os professores da matéria para a edição</small>
            </div>
          </div>
      </div>
      <div class="modal-footer">
        <input name="AdicionaProfessor" type="submit" class="btn btn-primary BotaoEnvia" value="Salvar">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
      </form>
    </div>
  </div>
</div>
<div class="modal fade" id="configCursos" tabindex="-1" aria-labelledby="configCursosLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Configuração</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <a href="<?= URLADM . "home/index" ?>" class="btn btn-lg w-100 btn-outline-dark" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-tachometer-alt"></i> Página Inicial</a>
        <?php
        if ($this->dados['botao']['list_horas']) {
        ?>
          <a href="<?= htmlspecialchars((string) (URLADM . "horario-aula/listar/{$this->dados['listaDisciplinas']['anoPeriodo']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-lg w-100 btn-outline-dark" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fa-solid fa-stopwatch"></i> Horários aula</a>
        <?php
        }
        if ($this->dados['botao']['list_alunos']) {
        ?>
          <a href="<?= htmlspecialchars((string) (URLADM . "portal-alunos/listar/{$this->dados['listaDisciplinas']['anoPeriodo']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-lg w-100 btn-outline-dark" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-user-graduate"></i> Portal Alunos</a>
        <?php
        }
        ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">fechar</button>
      </div>
    </div>
  </div>
</div>
<script>
  /* E8c: a ordem da lista está em data-ordem (lista-dados) */
</script>