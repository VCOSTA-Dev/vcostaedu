<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* O RECIBO no painel (D-99): a página de imprimir é a do core (core/ReciboFinanceiro.php), a mesma do portal. */
$r = $this->dados['recibo'];
$msg = (string) ($_SESSION['msg'] ?? '');
unset($_SESSION['msg']);
\VcReciboFinanceiro::pagina($r, $this->dados['instituicao'] ?: [], \App\adms\Models\financeiro\AdmsFinFicha::rotuloNumero(),
  \App\adms\Models\helper\AdmsInstituicao::temLogo() ? \App\adms\Models\helper\AdmsInstituicao::logoUrl() : '',
  ['Voltar à ficha financeira', URLADM . 'financeiro/ficha/' . (int) $r['aluno_id'] . '?ano=' . (int) $r['ano']], $msg);
