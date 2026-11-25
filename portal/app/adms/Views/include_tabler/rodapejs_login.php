<?php
if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
/* Fecha o .page do layout de acesso do portal. O envio continua no funcaologin.js da origem (formulário inválido
   não sai, was-validated do Bootstrap; "Processando..." no botão; as duas senhas iguais em definir/atualizar a
   senha) — ele só usa jQuery e as classes de validação, iguais no Bootstrap 5. */
require_once dirname(__DIR__, 5) . '/core/asset.php';
$tblJs = static fn(string $relativo): string => htmlspecialchars(assetUrl($relativo, URL), ENT_QUOTES, 'UTF-8');
?>
</div>
<script src="<?= $tblJs('assets/plugins/jquery/jquery.min.js') ?>"></script>
<script src="<?= $tblJs('assets/plugins/tabler/js/tabler.min.js') ?>"></script>
<script src="<?= $tblJs('portal/assets/js/funcaologin.js') ?>"></script>
<script src="<?= htmlspecialchars(assetUrl('assets/js/vcostaedu-senhas-comuns.js', URL), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars(assetUrl('assets/js/vcostaedu-componentes.js', URL), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
