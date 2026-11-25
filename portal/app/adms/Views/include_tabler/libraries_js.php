<?php
if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
/*
 * O RODAPÉ DO LAYOUT TABLER DO PORTAL (VCOSTAEDU, Etapa 6; o do painel, com os scripts do portal): fecha o .page-wrapper e o .page que o rail abriu e
 * carrega os scripts.
 *
 * Sempre (o que toda tela da origem carregava e usa): jQuery, Tabler (com o Bootstrap 5 embutido), DataTables com a
 * integração Bootstrap 5 e os botões de exportar (Excel, CSV, PDF, Imprimir), Select2, Inputmask ANTES do
 * validarForm.js (sem o plugin ele lança TypeError e mata o resto do arquivo — achado do projeto de referência), funcoes.js, as
 * listas e o comportamento comum.
 * Por flag do controller, como na origem: carregarRelatorio (colunas visíveis e gráfico), carregarOrdenacao (arrastar),
 * carregarEditor (o editor padrão, Summernote no build Bootstrap 5) e carregarEditInt (edição em linha das notas).
 *
 * ATENÇÃO: o tabler.min.js expõe `window.tabler`, NÃO `window.bootstrap` (medido no bundle). Ele traz a ponte jQuery
 * do Bootstrap 5, registrada no DOMContentLoaded. Quem abre modal por JS usa abrirModal() (funcoes.js).
 * Os plugins que nenhuma tela usava saíram (D-05).
 */
require_once dirname(__DIR__, 5) . '/core/asset.php';
$tblJs = static fn(string $relativo): string => htmlspecialchars(assetUrl($relativo, URL), ENT_QUOTES, 'UTF-8');
?>
    <footer class="footer footer-transparent d-print-none vc-rodape-legal">
      <div class="container-xl"><?= \VcLegal::rodape(\App\adms\Models\helper\AdmsVersao::numero(), URL, 'text-center') ?></div>
    </footer>
  </div><?php /* .page-wrapper */ ?>
</div><?php /* .page */ ?>
<script src="<?= $tblJs('assets/plugins/jquery/jquery.min.js') ?>"></script>
<script src="<?= $tblJs('assets/plugins/tabler/js/tabler.min.js') ?>"></script>
<script src="<?= $tblJs('assets/plugins/select2/js/select2.full.min.js') ?>"></script>
<script src="<?= $tblJs('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= $tblJs('assets/plugins/datatables-bs5/js/dataTables.bootstrap5.min.js') ?>"></script>
<script src="<?= $tblJs('assets/plugins/datatables-buttons/js/dataTables.buttons.min.js') ?>"></script>
<script src="<?= $tblJs('assets/plugins/datatables-buttons/js/buttons.bootstrap5.min.js') ?>"></script>
<script src="<?= $tblJs('assets/plugins/jszip/jszip.min.js') ?>"></script>
<script src="<?= $tblJs('assets/plugins/pdfmake/pdfmake.min.js') ?>"></script>
<script src="<?= $tblJs('assets/plugins/pdfmake/vfs_fonts.js') ?>"></script>
<script src="<?= $tblJs('assets/plugins/datatables-buttons/js/buttons.html5.min.js') ?>"></script>
<script src="<?= $tblJs('assets/plugins/datatables-buttons/js/buttons.print.min.js') ?>"></script>
<script src="<?= $tblJs('assets/plugins/inputmask/jquery.inputmask.min.js') ?>"></script>
<script> const pgUrlAdm = '<?= URLACAD ?>'; </script>
<script src="<?= $tblJs('portal/assets/js/validarForm.js') ?>"></script>
<script src="<?= $tblJs('portal/assets/js/funcoes.js') ?>"></script>
<?php if (!empty($this->dados['carregarRelatorio'])): ?>
<script src="<?= $tblJs('assets/plugins/datatables-buttons/js/buttons.colVis.min.js') ?>"></script>
<script src="<?= $tblJs('assets/plugins/chart.js/Chart.min.js') ?>"></script>
<?php endif; ?>
<script src="<?= $tblJs('assets/vcostaedu/vcostaedu-listas.js') ?>"></script>
<script src="<?= $tblJs('assets/vcostaedu/vcostaedu-ui.js') ?>"></script>
<script src="<?= $tblJs('assets/vcostaedu/vcostaedu-tabler.js') ?>"></script>
<?php if (!empty($this->dados['carregarRelatorio'])): ?>
<script src="<?= $tblJs('assets/vcostaedu/vcostaedu-relatorios.js') ?>"></script>
<?php endif; ?>
<?php if (!empty($this->dados['carregarOrdenacao'])): ?>
<script src="<?= $tblJs('assets/plugins/jquery-ui/jquery-ui.min.js') ?>"></script>
<script src="<?= $tblJs('assets/vcostaedu/vcostaedu-ordenar.js') ?>"></script>
<?php endif; ?>
<?php if (!empty($this->dados['carregarEditor'])): ?>
<script src="<?= $tblJs('assets/plugins/summernote/summernote-bs5.min.js') ?>"></script>
<script src="<?= $tblJs('assets/plugins/summernote/lang/summernote-pt-BR.min.js') ?>"></script>
<script src="<?= $tblJs('assets/vcostaedu/vcostaedu-editor.js') ?>"></script>
<?php endif; ?>
<script src="<?= htmlspecialchars(assetUrl('assets/js/vcostaedu-senhas-comuns.js', URL), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars(assetUrl('assets/js/vcostaedu-tema.js', URL), ENT_QUOTES, 'UTF-8') ?>"></script><?php /* Onda 3, Etapa 10: o tema */ ?>
<script src="<?= htmlspecialchars(assetUrl('assets/js/vcostaedu-componentes.js', URL), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
