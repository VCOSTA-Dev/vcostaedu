<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E1b (D-356): a lista de pendência do sexo. Editar a ficha e escolher o sexo tira o aluno da lista. */
$lista = $this->dados['pendentes'] ?? [];
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
?>
<div class="vc-pagina">
  <?php ob_start(); ?><a href="<?= URLADM . 'academicos/listar' ?>" class="btn btn-outline-primary rounded-circle" title="Voltar para Alunos"><i class="fas fa-arrow-left"></i></a> Alunos sem sexo informado<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <div class="alert alert-warning">
        Até a versão 1.10.0, o cadastro gravava <b>Outros</b> quando ninguém escolhia o sexo. Os documentos agora concordam com ele
        (ALUNA/ALUNO, matriculada/matriculado). Confira cada aluno abaixo: <b>Editar</b>, escolha o sexo e salve. Quem é mesmo
        "Outros" também sai da lista ao ser salvo com essa escolha.
      </div>
      <div class="card shadow">
        <div class="card-body">
          <?php if (!$lista) : ?>
            <p class="text-success mb-0" id="semPendencia"><i class="fas fa-check-circle"></i> Nenhum aluno pendente.</p>
          <?php else : ?>
            <table id="tabelaPendenciaSexo" class="table table-sm table-striped table-hover table-compacta lista-dados" data-titulo="Alunos sem sexo informado" data-ordem='[[1,"asc"]]'>
              <thead>
                <tr><th class="num"><?= \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero() ?></th><th>Nome</th><th>Sexo gravado</th><th>Situação</th><th>Cadastrado em</th><th class="nao-exportar nao-ordenar text-end">Ações</th></tr>
              </thead>
              <tbody>
                <?php foreach ($lista as $a) : ?>
                  <tr data-aluno="<?= htmlspecialchars((string) ((int) $a['id']), ENT_QUOTES, 'UTF-8') ?>">
                    <td class="num"><?= $h($a['rm']) ?></td>
                    <td><?= $h($a['nome']) ?></td>
                    <td><?= $h($a['genero'] ?? '—') ?> <small class="text-muted">(padrão antigo)</small></td>
                    <td><?= $h($a['situacao'] ?? '—') ?></td>
                    <td><?= $a['created'] ? $h(date('d/m/Y', strtotime($a['created']))) : '—' ?></td>
                    <td class="acoes-linha text-end">
                      <?php if (!empty($this->dados['botao']['edit_user'])) : ?>
                        <a href="<?= htmlspecialchars((string) (URLADM . 'academicos/editar/' . (int) $a['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-acao btn-outline-warning" title="Editar e escolher o sexo"><i class="fas fa-edit"></i> Editar</a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
</div>
