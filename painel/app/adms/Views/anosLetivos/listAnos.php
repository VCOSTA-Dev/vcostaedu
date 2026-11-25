<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$anos = $this->dados['anos'] ?? [];
$bandeiras = $this->dados['bandeiras'] ?? [];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Anos Letivos<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>

  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>

      <!-- ⚠️ O que esta tela liga não é óbvio, e por isso está escrito. Sem
           esta caixa, "Ano ativo" e "Ano corrente" parecem a mesma coisa. -->
      <div class="alert alert-info">
        <h5>O ano letivo é a porta de tudo o que muda de ano.</h5>
        <p class="mb-0">
          As telas de Matrículas, Disciplinas, Minhas Disciplinas e Horário
          de Aula leem esta lista. Um ano que não
          estiver aqui <b>não aparece em nenhum filtro</b>, mesmo que já tenha
          matrícula.
        </p>
        <p class="mb-0 mt-2">
          ⚠️ <b>"Ano corrente" é um só.</b> Marcar um desmarca o anterior — as
          telas escolhem um, e dois marcados fariam a tela mostrar duas verdades
          com o sistema obedecendo a uma.
        </p>
      </div>

      <div class="card shadow">
        <div class="card-header">
          <?php if (!empty($this->dados['botao']['cadastrar'])) : ?>
            <a href="<?= URLADM . 'anos-letivos/cadastrar' ?>" class="btn btn-sm btn-success">
              <i class="fas fa-plus"></i> Novo ano letivo
            </a>
          <?php endif; ?>
        </div>
        <div class="card-body table-responsive">
          <?php $Ui = \App\adms\Models\helper\AdmsUi::class; /* E8c (R2/R3/R4) */ ?>
          <table id="tabelaAnos" class="table table-sm table-striped table-hover table-compacta lista-dados" data-titulo="Anos letivos" data-ordem='[[0,"desc"]]' data-paginar="nao">
            <thead>
              <tr>
                <th class="num">Ano</th>
                <?php foreach ($bandeiras as $b) : ?>
                  <th class="text-center" data-ttip="tooltip" data-bs-placement="top"
                      title="<?= htmlspecialchars($b['ajuda'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($b['rotulo'], ENT_QUOTES, 'UTF-8') ?>
                  </th>
                <?php endforeach; ?>
                <th class="num">Matrículas</th>
                <th class="nao-exportar nao-ordenar text-end">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($anos as $a) : ?>
                <tr>
                  <td class="num"><b><?= htmlspecialchars((string) ((int) $a['ano']), ENT_QUOTES, 'UTF-8') ?></b></td>
                  <?php foreach (array_keys($bandeiras) as $campo) : ?>
                    <td class="text-center">
                      <?php if ((int) $a[$campo] === 1) : ?>
                        <span class="badge rounded-pill bg-success-lt">sim</span>
                      <?php else : ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                  <?php endforeach; ?>
                  <td class="num"><?= htmlspecialchars((string) ((int) $a['matriculas']), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="acoes-linha">
                    <?php if (!empty($this->dados['botao']['editar'])) : ?>
                      <?= $Ui::acao('editar', URLADM . 'anos-letivos/editar/' . (int) $a['id'], 'Editar o ano letivo') ?>
                    <?php endif; ?>
                    <?php if (!empty($this->dados['botao']['apagar'])) : ?>
                      <?= $Ui::acaoPost('excluir', URLADM . 'anos-letivos/apagar/' . (int) $a['id'], ['ApagarAno' => '1'], 'Apagar o ano letivo (só sem matrícula)',
                        (int) $a['matriculas'] > 0 ? 'Tem ' . (int) $a['matriculas'] . ' matrícula(s): não pode ser apagado' : null,
                        ['classe' => 'btn-outline-danger', 'confirmar' => 'Apagar o ano letivo ' . (int) $a['ano'] . '? Ele sai dos filtros das sete telas que o leem.']) ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

