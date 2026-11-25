<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Os responsáveis do aluno, por ano letivo: o financeiro, o titular, os acessos adicionais, os contatos (escola). */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$a = $d['aluno'];
$r = $d['r'];
$ano = (int) $d['ano'];
$M = \App\adms\Models\escola\AdmsResponsaveis::class;
$pode = (new \App\adms\Models\AdmsBotao())->valBotao([
  'designar' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'designar'],
  'encerrar' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'encerrar'],
  'liberar' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'liberar'],
  'contato' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'contato'],
  'copiar' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'copiar'],
  'cadastrar' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'cadastrar'],
]);
$dt = fn($x) => $x ? date('d/m/Y', strtotime((string) $x)) : '';
$ocultos = '<input type="hidden" name="aluno" value="' . (int) $a['id'] . '"><input type="hidden" name="ano" value="' . $ano . '">';
/* a pessoa: uma das já ligadas ao aluno, ou uma nova (o sistema procura pelo e-mail e pelo CPF antes de criar) */
$escolherPessoa = function (string $prefixo, ?int $padrao = null) use ($h, $d, $M): string {
  $o = '<div class="mb-2"><label class="form-label" for="' . $prefixo . 'Pessoa">A pessoa</label><select name="responsavel_id" id="' . $prefixo . 'Pessoa" class="form-select vc-escolher-pessoa">';
  foreach ($d['r']['pessoas'] as $p) {
    $o .= '<option value="' . (int) $p['id'] . '"' . ((int) $p['id'] === (int) $padrao ? ' selected' : '') . '>' . $h($p['nome'] . ' (' . $p['parentesco'] . ')') . '</option>';
  }
  $o .= '<option value="0"' . (!$d['r']['pessoas'] ? ' selected' : '') . '>Outra pessoa (cadastrar agora)</option></select></div>';
  $o .= '<div class="vc-pessoa-nova border rounded p-2 mb-2' . ($d['r']['pessoas'] ? ' d-none' : '') . '"><div class="row g-2">'
    . '<div class="col-12"><label class="form-label" for="' . $prefixo . 'Nome">Nome</label><input type="text" name="nome" id="' . $prefixo . 'Nome" class="form-control" maxlength="150"></div>'
    . '<div class="col-md-6"><label class="form-label" for="' . $prefixo . 'Email">E-mail</label><input type="email" name="email" id="' . $prefixo . 'Email" class="form-control"></div>'
    . '<div class="col-md-3"><label class="form-label" for="' . $prefixo . 'Celular">Celular</label><input type="text" inputmode="tel" name="celular" id="' . $prefixo . 'Celular" class="form-control"></div>'
    . '<div class="col-md-3"><label class="form-label" for="' . $prefixo . 'Cpf">CPF</label><input type="text" inputmode="numeric" name="cpf" id="' . $prefixo . 'Cpf" class="form-control"></div>'
    . '<div class="col-md-5"><label class="form-label" for="' . $prefixo . 'Parentesco">Parentesco</label><select name="parentesco" id="' . $prefixo . 'Parentesco" class="form-select">';
  foreach ($M::PARENTESCOS as $x) {
    $o .= '<option>' . $h($x) . '</option>';
  }
  return $o . '</select></div></div><div class="form-hint mt-1">Se já existir alguém com o mesmo e-mail ou CPF, o sistema usa esse cadastro.</div></div>';
};
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Responsáveis de <?= $h($a['nome_social'] ?: $a['nome']) ?><?php $paginaTitulo = ob_get_clean(); $paginaPretitulo = 'RM ' . $a['rm'];
  $paginaAcoesHtml = '<a href="' . URLADM . 'academicos/visualizar/' . (int) $a['id'] . '" class="btn btn-outline-secondary" id="voltarFicha"><i class="fas fa-id-card"></i> Ficha do aluno</a>';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <form method="get" class="mb-3 d-flex flex-wrap gap-2 align-items-end" id="filtroAnoResponsaveis">
        <div><label class="form-label" for="ano">Ano letivo</label>
          <select name="ano" id="ano" class="form-select" onchange="this.form.submit()">
            <?php foreach ($d['anos'] as $x) : ?><option value="<?= $h((int) $x) ?>"<?= (int) $x === $ano ? ' selected' : '' ?>><?= $h((int) $x) ?></option><?php endforeach; ?>
          </select></div>
        <?php if (!empty($pode['copiar']) && in_array($ano - 1, $d['anos'], true)) : ?><a href="?ano=<?= $h($ano) ?>&amp;copiar=1" class="btn btn-outline-secondary" id="copiarAnoAnterior"><i class="fas fa-copy"></i> Copiar do ano anterior</a><?php endif; ?>
      </form>
      <p class="text-secondary">Os papéis valem só para <?= $h($ano) ?>. O responsável financeiro é sempre um só; o acadêmico titular é o do contrato e pode ser a mesma pessoa.</p>

      <?php if ($d['copia']) : ?>
        <div class="card mb-3 border-primary" id="previaCopia"><div class="card-header"><h3 class="card-title">Copiar de <?= $h($ano - 1) ?>: confira antes de gravar</h3></div><div class="card-body">
          <ul class="mb-2">
          <?php foreach ($d['copia'] as $papel => $x) : ?>
            <li data-papel="<?= $h($papel) ?>"><?= $h($M::PAPEIS[$papel]) ?>: <?php if (!$x['antes']) : ?><span class="text-secondary">ninguém em <?= $h($ano - 1) ?></span><?php elseif ($x['agora']) : ?><?= $h($x['antes']['nome']) ?> <span class="text-secondary">— <?= $h($ano) ?> já tem <?= $h($x['agora']['nome']) ?>; fica como está</span><?php else : ?><b><?= $h($x['antes']['nome']) ?></b> <span class="badge bg-blue-lt">será copiado</span><?php endif; ?></li>
          <?php endforeach; ?>
          </ul>
          <p class="small text-secondary mb-2">Os acessos acadêmicos adicionais não são copiados: valem só para o ano em que foram liberados.</p>
          <?php if (array_filter(array_column($d['copia'], 'copia'))) : ?>
            <form method="post" action="<?= URLADM ?>responsaveis/copiar" class="d-inline"><?= $ocultos ?><button type="submit" class="btn btn-primary" id="confirmarCopia">Confirmar e gravar</button></form>
          <?php endif; ?>
          <a href="?ano=<?= $h($ano) ?>" class="btn btn-link">Cancelar</a>
        </div></div>
      <?php endif; ?>

      <div class="row row-cards">
        <?php foreach (['financeiro', 'titular'] as $papel) : $x = $r[$papel]; ?>
        <div class="col-lg-6">
          <div class="card" id="papel-<?= $h($papel) ?>"><div class="card-header"><h3 class="card-title"><?= $h($M::PAPEIS[$papel]) ?> de <?= $h($ano) ?></h3></div><div class="card-body">
            <?php if ($x) : ?>
              <div class="vc-em-vigor" data-responsavel="<?= $h((int) $x['responsavel_id']) ?>"><a href="<?= URLADM ?>responsaveis/ver/<?= $h((int) $x['responsavel_id']) ?>"><b><?= $h($x['nome']) ?></b></a> <span class="text-secondary">(<?= $h($x['parentesco'] ?? '') ?>)</span>
                <div class="small text-secondary">desde <?= $h($dt($x['inicio'])) ?><?= $x['criado_por_nome'] ? ', por ' . $h($x['criado_por_nome']) : '' ?><?= $x['origem'] !== 'tela' ? ' (pela ' . $h($x['origem'] === 'api' ? 'interface de integração' : 'planilha') . ')' : '' ?></div></div>
            <?php else : ?>
              <div class="text-secondary vc-vazio" id="vazio-<?= $h($papel) ?>">Ninguém designado em <?= $h($ano) ?>.</div>
            <?php endif; ?>
            <?php if (!empty($pode['designar'])) : ?>
              <details class="mt-3" id="designar-<?= $h($papel) ?>"<?= $x ? '' : ' open' ?>><summary class="text-primary"><?= $x ? 'Trocar' : 'Designar' ?></summary>
                <form method="post" action="<?= URLADM ?>responsaveis/designar" class="mt-2 vc-form-designar"><?= $ocultos ?><input type="hidden" name="papel" value="<?= $h($papel) ?>">
                  <?= $escolherPessoa($papel, $papel === 'titular' && $r['financeiro'] ? (int) $r['financeiro']['responsavel_id'] : null) ?>
                  <div class="row g-2">
                    <div class="col-md-5"><label class="form-label" for="<?= $h($papel) ?>Data"><?= $x ? 'A partir de' : 'Desde' ?></label><input type="date" name="data" id="<?= $h($papel) ?>Data" class="form-control" value="<?= $h($M::inicioDoAno($ano)) ?>"></div>
                    <?php if ($x) : ?><div class="col-md-7"><label class="form-label required" for="<?= $h($papel) ?>Motivo">Motivo da troca</label><input type="text" name="motivo" id="<?= $h($papel) ?>Motivo" class="form-control" maxlength="255" required></div><?php endif; ?>
                  </div>
                  <?php if ($x) : ?><div class="form-hint mt-1">A troca encerra <?= $h($x['nome']) ?> nesta data, com o motivo, e começa a nova pessoa na mesma data. Nunca ficam dois ao mesmo tempo.</div><?php endif; ?>
                  <button type="submit" class="btn btn-primary mt-2"><?= $x ? 'Trocar' : 'Designar' ?></button>
                </form>
              </details>
            <?php endif; ?>
            <?php if ($x && !empty($pode['encerrar'])) : ?>
              <details class="mt-2"><summary class="text-danger">Encerrar sem pôr outra pessoa</summary>
                <form method="post" action="<?= URLADM ?>responsaveis/encerrar" class="mt-2"><?= $ocultos ?><input type="hidden" name="designacao" value="<?= $h((int) $x['id']) ?>">
                  <div class="row g-2"><div class="col-md-5"><label class="form-label">Data</label><input type="date" name="data" class="form-control" value="<?= $h(date('Y-m-d')) ?>"></div>
                    <div class="col-md-7"><label class="form-label required">Motivo</label><input type="text" name="motivo" class="form-control" maxlength="255" required></div></div>
                  <button type="submit" class="btn btn-outline-danger mt-2">Encerrar</button></form></details>
            <?php endif; ?>
          </div></div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="card mt-3" id="acessosAdicionais"><div class="card-header"><h3 class="card-title">Acessos acadêmicos adicionais de <?= $h($ano) ?></h3></div><div class="card-body">
        <p class="text-secondary small">Outras pessoas a quem a escola libera o acompanhamento acadêmico do aluno neste ano, mesmo sem estarem no contrato. Elas veem só o acadêmico: nunca o financeiro.<?= $d['adicionalLimite'] ? ' O limite da escola: ' . $h($d['adicionalLimite']) . ' por aluno.' : '' ?></p>
        <?php if (!$d['adicionalPermitido']) : ?><div class="alert alert-secondary" id="adicionalDesligado">A escola não permite o acesso acadêmico adicional (Regras da escola). <?= $r['adicionais'] ? 'As liberações abaixo estão sem efeito enquanto a regra estiver desligada.' : '' ?></div><?php endif; ?>
        <?php if (!$r['adicionais']) : ?><div class="text-secondary" id="semAdicionais">Nenhum acesso adicional em <?= $h($ano) ?>.</div><?php endif; ?>
        <?php foreach ($r['adicionais'] as $x) : ?>
          <div class="border rounded p-2 mb-2 vc-adicional" data-designacao="<?= $h((int) $x['id']) ?>" data-responsavel="<?= $h((int) $x['responsavel_id']) ?>">
            <a href="<?= URLADM ?>responsaveis/ver/<?= $h((int) $x['responsavel_id']) ?>"><b><?= $h($x['nome']) ?></b></a> <span class="text-secondary">(<?= $h($x['parentesco'] ?? '') ?>)</span>
            <div class="small">Motivo: <?= $h($x['motivo']) ?><?= $x['referencia'] ? ' · Termo ou documento: ' . $h($x['referencia']) : '' ?></div>
            <div class="small text-secondary">Liberado em <?= $h($dt($x['inicio'])) ?><?= $x['criado_por_nome'] ? ' por ' . $h($x['criado_por_nome']) : '' ?></div>
            <?php if (!empty($pode['encerrar'])) : ?>
              <details class="mt-1"><summary class="text-danger small">Encerrar a liberação</summary>
                <form method="post" action="<?= URLADM ?>responsaveis/encerrar" class="mt-2"><?= $ocultos ?><input type="hidden" name="designacao" value="<?= $h((int) $x['id']) ?>">
                  <div class="row g-2"><div class="col-md-4"><label class="form-label">Data</label><input type="date" name="data" class="form-control" value="<?= $h(date('Y-m-d')) ?>"></div>
                    <div class="col-md-8"><label class="form-label required">Motivo</label><input type="text" name="motivo" class="form-control" maxlength="255" required></div></div>
                  <button type="submit" class="btn btn-sm btn-outline-danger mt-2">Encerrar a liberação</button></form></details>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php if ($d['adicionalPermitido'] && !empty($pode['liberar'])) : ?>
          <details class="mt-2" id="liberarAdicional"><summary class="text-primary">Liberar um acesso adicional</summary>
            <form method="post" action="<?= URLADM ?>responsaveis/liberar" class="mt-2" style="max-width: 44rem"><?= $ocultos ?>
              <?= $escolherPessoa('adicional') ?>
              <div class="mb-2"><label class="form-label required" for="adicionalMotivo">Motivo</label><input type="text" name="motivo" id="adicionalMotivo" class="form-control" maxlength="255" required placeholder="Por exemplo: a avó acompanha as tarefas durante a semana"></div>
              <div class="mb-2"><label class="form-label" for="adicionalReferencia">Termo ou documento que apoia a liberação</label><input type="text" name="referencia" id="adicionalReferencia" class="form-control" maxlength="190" placeholder="Por exemplo: termo assinado pela mãe em 10/02"></div>
              <button type="submit" class="btn btn-primary">Liberar</button>
            </form></details>
        <?php endif; ?>
      </div></div>

      <div class="card mt-3" id="contatosAluno"><div class="card-header"><h3 class="card-title">Responsáveis para contato</h3></div><div class="card-body">
        <p class="text-secondary small">Quem a escola pode chamar. Fica na ficha do aluno e não entra no portal.</p>
        <?php if (!$r['contatos']) : ?><div class="text-secondary" id="semContatos">Nenhum contato cadastrado.</div><?php endif; ?>
        <?php foreach ($r['contatos'] as $c) : ?>
          <div class="d-flex justify-content-between align-items-center border-bottom py-1 vc-contato"><div><b><?= $h($c['nome']) ?></b> <span class="text-secondary">(<?= $h($c['parentesco']) ?>)</span> · <?= $h($M::celularFormatado($c['telefone'])) ?><?= $c['observacao'] ? ' <span class="text-secondary small">— ' . $h($c['observacao']) . '</span>' : '' ?></div>
            <?php if (!empty($pode['contato'])) : ?><form method="post" action="<?= URLADM ?>responsaveis/contato"><?= $ocultos ?><input type="hidden" name="retirar" value="<?= $h((int) $c['id']) ?>"><button type="submit" class="btn btn-sm btn-link text-danger">Retirar</button></form><?php endif; ?></div>
        <?php endforeach; ?>
        <?php if (!empty($pode['contato'])) : ?>
          <details class="mt-2" id="novoContato"><summary class="text-primary">Cadastrar um contato</summary>
            <form method="post" action="<?= URLADM ?>responsaveis/contato" class="mt-2 row g-2" style="max-width: 46rem"><?= $ocultos ?>
              <div class="col-md-5"><label class="form-label required" for="contatoNome">Nome</label><input type="text" name="nome" id="contatoNome" class="form-control" maxlength="150" required></div>
              <div class="col-md-3"><label class="form-label required" for="contatoParentesco">Parentesco</label><input type="text" name="parentesco" id="contatoParentesco" class="form-control" maxlength="40" required></div>
              <div class="col-md-4"><label class="form-label required" for="contatoTelefone">Telefone</label><input type="text" inputmode="tel" name="telefone" id="contatoTelefone" class="form-control" required></div>
              <div class="col-12"><label class="form-label" for="contatoObs">Observação</label><input type="text" name="observacao" id="contatoObs" class="form-control" maxlength="255"></div>
              <div class="col-12"><button type="submit" class="btn btn-primary">Cadastrar</button></div>
            </form></details>
        <?php endif; ?>
      </div></div>

      <?php if ($r['encerradas']) : ?>
      <div class="card mt-3" id="encerradasAno"><div class="card-header"><h3 class="card-title">Papéis encerrados em <?= $h($ano) ?></h3></div>
        <div class="table-responsive"><table class="table table-sm card-table"><thead><tr><th>Papel</th><th>Pessoa</th><th>De</th><th>Até</th><th>Motivo</th></tr></thead><tbody>
        <?php foreach ($r['encerradas'] as $x) : ?>
          <tr><td><?= $h($M::PAPEIS[$x['papel']] ?? $x['papel']) ?></td><td><?= $h($x['nome']) ?></td><td><?= $h($dt($x['inicio'])) ?></td><td><?= $h($dt($x['fim'])) ?></td><td><?= $h($x['motivo_fim']) ?><?= $x['encerrado_por_nome'] ? ' <span class="text-secondary small">— ' . $h($x['encerrado_por_nome']) . '</span>' : '' ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div></div>
      <?php endif; ?>

      <div class="card mt-3" id="historicoAluno"><div class="card-header"><h3 class="card-title">Histórico</h3></div>
        <?php if (!$d['historico']) : ?><div class="card-body text-secondary">Nada registrado ainda.</div><?php else : ?>
        <div class="table-responsive"><table class="table table-sm card-table"><thead><tr><th>Quando</th><th>O quê</th><th>Por</th><th>Motivo</th></tr></thead><tbody>
        <?php foreach ($d['historico'] as $x) : ?>
          <tr><td class="text-nowrap"><?= $h(date('d/m/Y H:i', strtotime($x['em']))) ?></td><td><?= $h(\App\adms\Models\escola\AdmsResponsaveisTela::acao($x)) ?></td><td><?= $h($x['por_nome'] ?: $x['origem']) ?></td><td><?= $h($x['motivo'] ?? '') ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
      </div>
    </div>
  </section>
</div>
<script>
document.querySelectorAll('.vc-escolher-pessoa').forEach(function (s) {
  var nova = s.closest('form').querySelector('.vc-pessoa-nova');
  function ver() { nova.classList.toggle('d-none', s.value !== '0'); }
  s.addEventListener('change', ver); ver();
});
</script>
