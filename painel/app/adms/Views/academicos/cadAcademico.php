<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$valorForm = $this->dados['form'] ?? [];
?>
<div class="vc-pagina">
  <?php ob_start(); ?><a href="<?= URLADM . "academicos/listar" ?>" class="btn btn-outline-primary rounded-circle" title="Voltar para a lista de alunos"><i class="fas fa-arrow-left"></i></a> Cadastrar aluno<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
            <form action="" method="POST" novalidate>
              <div class="card-body">
                <p class="text-muted">A senha não se define neste cadastro: depois de salvar, use <b>Enviar link de acesso</b> para o aluno definir a própria senha (ou, na ficha, <b>Definir uma senha</b>). O cadastro não cria matrícula nem enturmação.</p>
                <?php include __DIR__ . '/formAluno.php'; ?>
              </div>
              <div class="card-footer">
                <input type="submit" class="btn btn-primary botaoEnvia" name="CadUsuarioAcad" value="Salvar">
                <a href="<?= URLADM . "academicos/listar" ?>" class="btn btn-secondary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Cancelar</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
