<?php
if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
/* Fecha o .page do layout de acesso. O envio: formulário inválido não sai (was-validated do Bootstrap 5, o mesmo
   da origem) e o botão .botaoEnvia fica "Processando..." para não enviar duas vezes. */
require_once dirname(__DIR__, 5) . '/core/asset.php';
?>
</div>
<script src="<?= htmlspecialchars(assetUrl('assets/plugins/tabler/js/tabler.min.js', URL), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      } else {
        form.querySelectorAll('.botaoEnvia').forEach(function (b) { b.classList.add('disabled'); b.value = 'Processando...'; });
      }
      form.classList.add('was-validated');
    });
  });
</script>
<script src="<?= htmlspecialchars(assetUrl('assets/js/vcostaedu-senhas-comuns.js', URL), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars(assetUrl('assets/js/vcostaedu-componentes.js', URL), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
