<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$termo = (string) ($this->dados['listUsers']['termo'] ?? '');
$lista = $this->dados['listUsers']['lista'] ?? [];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Alunos<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
              <form class="d-flex flex-wrap align-items-center gap-2" method="GET" action="<?= URLADM . 'academicos/listar' ?>">
                <input type="text" name="busca" class="form-control me-2 mb-2" style="min-width: 320px;" placeholder="Nome, <?= \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero() ?>, CPF ou e-mail" value="<?= htmlspecialchars($termo, ENT_QUOTES) ?>">
                <button type="submit" class="btn btn-primary me-2 mb-2"><i class="fas fa-search"></i> Buscar</button>
                <?php if ($termo !== '') { ?>
                  <a href="<?= URLADM . 'academicos/listar' ?>" class="btn btn-outline-secondary me-2 mb-2">Limpar</a>
                <?php } ?>
                <?php if (!empty($this->dados['botao']['cad_user'])) { ?>
                  <a href="<?= URLADM . 'academicos/cadastrar' ?>" class="btn btn-outline-success mb-2 ms-md-auto"><i class="fas fa-user-plus"></i> Cadastrar aluno</a>
                <?php } ?>
              </form>
              <?php /* E1b (D-356): atalho para a lista de pendência do sexo, só quando há pendente */
              $nPend = \App\adms\Models\academicos\AdmsPendenciaSexo::contar();
              if ($nPend > 0) : ?>
                <a href="<?= URLADM . 'academicos/pendencia-sexo' ?>" class="btn btn-sm btn-outline-warning mb-2" id="linkPendenciaSexo"><i class="fas fa-user-tag"></i> Alunos sem sexo informado <span class="badge bg-warning-lt"><?= htmlspecialchars((string) ($nPend), ENT_QUOTES, 'UTF-8') ?></span></a><br>
              <?php endif; ?>
              <small class="text-muted"><?= $termo === '' ? 'Mostrando os alunos cadastrados mais recentemente (até 200).' : \App\adms\Models\helper\AdmsUi::plural(count($lista), 'aluno encontrado.', 'alunos encontrados.') ?></small>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-12">
          <div class="card shadow">
            <div class="card-body">
              <?php $Ui = \App\adms\Models\helper\AdmsUi::class; /* E8c (R2/R3): lista padrão */ ?>
              <table id="tabelaAlunos" class="table table-sm table-striped table-hover table-compacta lista-dados" data-titulo="Alunos" data-ordem='[[1,"asc"]]'>
                <thead>
                  <tr>
                    <th><?= \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero() ?></th>
                    <th>Nome</th>
                    <?php $naEscolaLista = \App\adms\Models\helper\AdmsTipoInstituicao::porTurma(); /* D-106: e o curso livre */ /* D-102 (C8) */ ?>
                    <?php $livreLista = \App\adms\Models\helper\AdmsTipoInstituicao::ehLivre(); /* D-106: no curso livre, só a turma */ ?><?php if ($naEscolaLista) : ?><?php if (!$livreLista) : ?><th>Série</th><?php endif; ?><th>Turma</th><?php endif; ?>
                    <th>E-mail</th>
                    <th>Situação</th>
                    <th class="nao-exportar nao-ordenar text-end">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($lista as $use) { ?>
                    <tr>
                      <td><?= htmlspecialchars((string) $use['rm']) ?></td>
                      <td><?= htmlspecialchars((string) $use['nome']) ?><?= !empty($use['nome_social']) ? ' <small class="text-muted">(' . htmlspecialchars($use['nome_social']) . ')</small>' : '' ?></td>
                      <?php if ($naEscolaLista) : $tA = $use['turmaAtual'] ? explode('|', (string) $use['turmaAtual']) : null; $sA = $use['serieAtual'] ? explode('|', (string) $use['serieAtual']) : null; ?>
                        <?php if (!$livreLista) : ?><td class="vc-serie-lista"><?= $sA ? htmlspecialchars(trim($sA[1] . ' · ' . $sA[0], ' ·')) : '<span class="text-secondary">—</span>' ?></td><?php endif; ?>
                        <td class="vc-turma-lista"><?= $tA ? htmlspecialchars(trim(($livreLista ? '' : $tA[1] . ' ') . $tA[2]) . ' · ' . $tA[0]) : '<span class="text-secondary">sem turma</span>' ?></td>
                      <?php endif; ?>
                      <td><?= htmlspecialchars((string) ($use['email'] ?? '')) ?></td>
                      <td class="text-center"><span class="badge bg-<?= htmlspecialchars((string) $use['situacaoCor']) ?>-lt"><?= htmlspecialchars((string) $use['situacaoUsuario']) ?></span></td>
                      <td class="acoes-linha">
                        <?php if (!empty($this->dados['botao']['boletim']) && !empty($use['rm'])) : ?>
                          <?= $Ui::acao('boletim', URLADM . "academicos/boletim/{$use['rm']}", 'Ver boletim do aluno', null, ['nova_aba' => true]) ?>
                        <?php endif; ?>
                        <?php if (!empty($this->dados['botao']['emitir_doc'])) : /* E6 (D-373) */ ?>
                          <?= $Ui::acao('atestado', URLADM . 'historico-emissoes/emitir/' . (int) $use['mcAtivo'] . '?tipo=ATESTADO', 'Emitir atestado de matrícula', empty($use['mcAtivo']) ? 'Atestado: o aluno não tem matrícula em "Cursando"' : null) ?>
                        <?php endif; ?>
                        <?php if (!empty($this->dados['botao']['vis_user'])) : ?>
                          <?= $Ui::acao('ver', URLADM . "academicos/visualizar/{$use['id']}", 'Abrir a ficha do aluno') ?>
                        <?php endif; ?>
                        <?php if (!empty($this->dados['botao']['edit_user'])) : ?>
                          <?= $Ui::acao('editar', URLADM . "academicos/editar/{$use['id']}", 'Editar os dados do aluno') ?>
                        <?php endif; ?>
                        <?php if (!empty($this->dados['botao']['del_user'])) : ?>
                          <?= $Ui::acaoModal('excluir', '#apagarItem', ['titulo' => 'EXCLUIR - ' . $use['nome'], 'confirm' => 'Tem certeza de que deseja excluir o aluno ' . $use['nome'] . '?', 'identidade' => (int) $use['id']], 'Apagar (desliga o aluno)') ?>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php } ?>
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
        <h5 class="modal-title" id="cabecalhoItem">EXCLUIR ALUNO</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= URLADM . "academicos/apagar"; ?>" novalidate>
        <div class="modal-body">
          <input type="hidden" name="id" class="form-control" value required>
          <p class="corpoItem"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <input type="submit" name="ApagarUsuarioAcad" class="btn btn-danger botaoEnvia" value="Apagar">
        </div>
      </form>
    </div>
  </div>
</div>


