<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Bibliotecas: as de terceiros, com a versão e a licença de cada uma (VcLegal::bibliotecas). */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Bibliotecas<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <div class="card">
        <div class="table-responsive">
          <table class="table table-vcenter card-table" id="tabelaBibliotecas">
            <thead><tr><th>Biblioteca</th><th>Versão</th><th>Licença</th></tr></thead>
            <tbody>
            <?php foreach ($this->dados['bibliotecas'] as $b) : ?>
              <tr><td><?= $h($b['nome']) ?></td><td><?= $h($b['versao']) ?></td><td><?= $h($b['licenca']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer text-secondary small">Cada biblioteca mantém a própria licença, que acompanha os arquivos dela. A licença do sistema está em <a href="<?= URL ?>sobre.php#licenca">Sobre</a>.</div>
      </div>
    </div>
  </section>
</div>
