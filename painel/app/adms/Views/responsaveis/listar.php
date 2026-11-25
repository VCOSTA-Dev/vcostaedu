<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Os responsáveis (escola): a busca, os alunos de cada pessoa, o papel por ano e a situação do acesso. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$M = \App\adms\Models\escola\AdmsResponsaveis::class;
$pode = (new \App\adms\Models\AdmsBotao())->valBotao([
  'cadastrar' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'cadastrar'],
  'importar' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'importar'],
  'convidar' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'convidar'],
  'turma' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'convidar-turma'],
]);
$cor = ['sem_convite' => 'secondary', 'convite' => 'blue', 'ativo' => 'green', 'bloqueado' => 'red'];
$curto = ['financeiro' => 'financeiro', 'titular' => 'titular', 'adicional' => 'acesso adicional'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Responsáveis<?php $paginaTitulo = ob_get_clean();
  $paginaAcoesHtml = (!empty($pode['turma']) ? '<a href="' . URLADM . 'responsaveis/convidar-turma" class="btn btn-outline-secondary" id="convidarTurma"><i class="fas fa-paper-plane"></i> Convidar uma turma</a> ' : '')
    . (!empty($pode['importar']) ? '<a href="' . URLADM . 'responsaveis/importar" class="btn btn-outline-secondary" id="importarResponsaveis"><i class="fas fa-file-import"></i> Importar planilha</a> ' : '')
    . (!empty($pode['cadastrar']) ? '<a href="' . URLADM . 'responsaveis/cadastrar" class="btn btn-primary" id="novoResponsavel"><i class="fas fa-plus"></i> Novo responsável</a>' : '');
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <form method="get" class="mb-3 d-flex flex-wrap gap-2 align-items-end" id="buscaResponsaveis">
        <div class="flex-grow-1" style="max-width: 28rem"><label class="form-label" for="busca">Procurar</label>
          <input type="search" name="busca" id="busca" class="form-control" value="<?= $h($d['busca']) ?>" placeholder="Nome, e-mail, CPF, celular, ou o nome ou o RA do aluno"></div>
        <button type="submit" class="btn btn-outline-primary">Procurar</button>
      </form>
      <div class="card">
        <?php if (!$d['pessoas']) : ?>
          <div class="card-body text-secondary" id="semResponsaveis"><?= $d['busca'] !== '' ? 'Ninguém encontrado com essa busca.' : 'Nenhum responsável cadastrado ainda. Cadastre pela ficha do aluno (Responsáveis), por aqui ou pela planilha.' ?></div>
        <?php else : ?>
        <div class="table-responsive">
          <table class="table table-vcenter card-table" id="tabelaResponsaveis">
            <thead><tr><th>Responsável</th><th>Alunos e papel por ano</th><th>Acesso ao portal</th></tr></thead>
            <tbody>
            <?php foreach ($d['pessoas'] as $p) : ?>
              <tr data-responsavel="<?= $h((int) $p['id']) ?>">
                <td><a href="<?= URLADM ?>responsaveis/ver/<?= $h((int) $p['id']) ?>"><?= $h($p['nome']) ?></a>
                  <div class="small text-secondary"><?= $h($p['email'] ?? '') ?><?= $p['celular'] ? ' · ' . $h($M::celularFormatado($p['celular'])) : '' ?></div></td>
                <td>
                  <?php if (!$p['alunos']) : ?><span class="text-secondary">nenhum aluno</span><?php endif; ?>
                  <?php foreach ($p['alunos'] as $a) : ?>
                    <div><a href="<?= URLADM ?>responsaveis/aluno/<?= $h((int) $a['id']) ?>"><?= $h($a['nome']) ?></a> <span class="text-secondary small">(<?= $h($a['parentesco']) ?>)</span>
                      <?php foreach ($p['papeis'][(int) $a['id']] ?? [] as $ano => $ps) : ?>
                        <span class="badge bg-azure-lt ms-1"><?= $h($ano) ?>: <?= $h(implode(' e ', array_map(fn($x) => $curto[$x] ?? $x, $ps))) ?></span>
                      <?php endforeach; ?></div>
                  <?php endforeach; ?>
                </td>
                <td><span class="badge bg-<?= $h($cor[$p['situacao']] ?? 'secondary') ?>-lt vc-situacao-acesso" data-situacao="<?= $h($p['situacao']) ?>"><?= $h($M::SITUACOES[$p['situacao']] ?? '') ?></span>
                  <?php if (!empty($pode['convidar']) && in_array($p['situacao'], ['sem_convite', 'convite'], true)) : ?>
                    <form method="post" action="<?= URLADM ?>responsaveis/convidar" class="d-inline ms-1"><input type="hidden" name="responsavel_id" value="<?= $h((int) $p['id']) ?>"><button type="submit" class="btn btn-sm btn-outline-primary vc-gerar-convite"<?= $p['situacao'] === 'convite' ? ' title="O convite anterior deixa de valer"' : '' ?>><?= $p['situacao'] === 'convite' ? 'Gerar outro convite' : 'Gerar convite' ?></button></form>
                  <?php endif; ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>
