<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$escolaLista = \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola(); // Onda 8, correção (D-72)
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Minhas Disciplinas<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
              <?php $h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); /* E8c (R2/R3): lista padrão; atalhos para as telas da disciplina */
              /* D-122 (a correção do ensaio, B3.9): Ativas, Encerradas e Todas; o ano escolhido continua no endereço */
              $sitF = (string) ($this->dados['situacaoFiltro'] ?? 'ativas');
              $baseF = URLADM . 'minhas-disciplinas/listar/' . (int) ($this->dados['listaDisciplinas']['anoPeriodo'] ?? 0) . '?situacao=';
              ?>
              <div class="btn-group mb-3" role="group" aria-label="Situação das disciplinas" id="filtroSituacao">
                <?php foreach (['ativas' => 'Ativas', 'encerradas' => 'Encerradas', 'todas' => 'Todas'] as $chaveF => $rotuloF) : ?>
                  <a href="<?= $h($baseF . $chaveF) ?>" class="btn btn-sm <?= $sitF === $chaveF ? 'btn-primary active' : 'btn-outline-primary' ?>" data-situacao="<?= $h($chaveF) ?>"<?= $sitF === $chaveF ? ' aria-current="true"' : '' ?>><?= $h($rotuloF) ?></a>
                <?php endforeach; ?>
              </div>
              <table id="tabelaDisciplinas" class="table table-sm table-striped table-hover table-compacta lista-dados" data-titulo="Minhas disciplinas" data-ordem='[[1,"asc"]]'>
                <thead><tr><th>Código</th><th><?= $escolaLista ? 'Disciplina' : 'Matéria' ?></th><th><?= $escolaLista ? 'Segmento' : 'Curso' ?></th><th>Turma</th><th class="num">Alunos</th><th>Situação</th><th class="nao-exportar nao-ordenar text-end">Ações</th></tr></thead>
                <tbody>
                  <?php foreach ($this->dados['listaDisciplinas']['disciplinas'] ?? [] as $disc) : $id = (int) $disc['id']; $soConsulta = !empty($disc['so_consulta']); ?>
                    <tr data-oferta="<?= htmlspecialchars((string) ($id), ENT_QUOTES, 'UTF-8') ?>" data-so-consulta="<?= $soConsulta ? '1' : '0' ?>">
                      <td><?= $h($disc['codmat']) ?></td>
                      <td><a href="<?= URLADM . 'disciplinas/visualizar/' . $id ?>"><?= $h($disc['materia']) ?></a><?php if ($soConsulta) : ?> <span class="badge bg-azure-lt vc-so-consulta" title="<?= $escolaLista ? 'Disciplina de um segmento que você coordena' : 'Disciplina de um curso que você coordena' ?>: você consulta, não lança">só consulta</span><?php endif; ?></td>
                      <td><?= $h($disc['curso']) ?></td>
                      <td><?= $h($disc['codtur']) ?></td>
                      <td class="num"><?= htmlspecialchars((string) ((int) ($disc['alunos'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= \App\adms\Models\helper\AdmsUi::etiqueta(!empty($disc['encerrada_em']) ? 'Encerrada' : 'Aberta') ?></td>
                      <td class="acoes-linha">
                        <?= \App\adms\Models\helper\AdmsUi::acao('ver', URLADM . 'disciplinas/visualizar/' . $id, 'Abrir a disciplina') ?>
                        <?php if (!$soConsulta) : /* os atalhos de lançar: só nas disciplinas dele */ ?>
                        <?= \App\adms\Models\helper\AdmsUi::acao('acao', URLADM . 'atividades/listar/' . $id, 'Atividades', null, ['icone' => 'fas fa-tasks', 'classe' => 'btn-outline-primary']) ?>
                        <?php endif; ?>
                        <?= \App\adms\Models\helper\AdmsUi::acao('acao', URLADM . 'medias/final/' . $id, 'Notas e resultado', null, ['icone' => 'fas fa-calculator', 'classe' => 'btn-outline-primary']) ?>
                        <?php if (!$soConsulta) : ?>
                        <?= \App\adms\Models\helper\AdmsUi::acao('faltas', URLADM . 'faltas/visualizar/' . $id, 'Faltas') ?>
                        <?php endif; ?>
                        <?php if ($escolaLista) : /* Onda 8, correção (D-72): na escola, as etapas do componente (a recuperação, fechar a etapa) */ ?>
                        <?= \App\adms\Models\helper\AdmsUi::acao('acao', URLADM . 'escola-notas/etapas/' . $id, 'Etapas', null, ['icone' => 'fas fa-layer-group', 'classe' => 'btn-outline-primary vc-etapas-componente']) ?>
                        <?php endif; ?>
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

<script>
  /* E8c: a ordem da lista está em data-ordem (lista-dados) */
</script>