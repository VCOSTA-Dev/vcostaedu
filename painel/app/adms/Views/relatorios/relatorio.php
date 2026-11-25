<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/*
 * 1.17.0 (D-454) — A TELA PADRÃO de todo relatório: filtros na URL, cartões, gráfico, tabela (DataTables com Excel / CSV /
 * PDF / Imprimir, colunas visíveis, totais no rodapé; data e hora e filtros no cabeçalho do PDF e da impressão; cada
 * exportação registrada antes de sair), "Como é calculado" (a ficha técnica), salvar e o drill-down (?detalhe=chave).
 */
use App\adms\Models\relatorios\AdmsRelatorios as Reg;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$def = $this->dados['def'];
/* correção da escola (C5, D-100): na escola, o código interno do relatório não aparece na tela */
$codTela = \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? '' : $def::CODIGO;
$defs = $this->dados['defsFiltros'];
$f = $this->dados['filtros'];
$r = $this->dados['r'];
$detalhe = $this->dados['detalhe'];
$visiveis = $this->dados['colunasVisiveis'];
$urlBase = URLADM . 'relatorios/' . $def::SLUG;
$qFiltros = array_filter($f, fn($v) => $v !== '' && $v !== null);
$link = fn(array $mais = []) => $urlBase . (($q = http_build_query($qFiltros + $mais)) ? '?' . $q : '');

$celula = function ($c) use ($h, $link) {
  if (!is_array($c)) {
    return [$h($c), null, ''];
  }
  $t = $h($c['t'] ?? '');
  if (!empty($c['detalhe'])) {
    $t = '<a href="' . $h($link(['detalhe' => $c['detalhe']])) . '" title="Abrir a lista">' . $t . '</a>';
  } elseif (!empty($c['url'])) {
    $t = '<a href="' . $h($c['url']) . '">' . $t . '</a>';
  }
  return [$t, $c['o'] ?? null, (string) ($c['classe'] ?? '')];
};

$tabela = function (array $t, string $titulo) use ($h, $celula, $def, $visiveis, $qFiltros, $detalhe) {
  $cols = $t['colunas'] ?? [];
  ?>
  <div class="table-responsive">
    <table class="table table-sm table-striped table-bordered lista-dados" data-relatorio="<?= $h($def::CODIGO) ?>" data-titulo="<?= $h($titulo) ?>"
           data-filtros="<?= $h(http_build_query($qFiltros + ($detalhe !== '' ? ['detalhe' => $detalhe] : []))) /* 1.17.4 (D-468): os filtros EFETIVOS (com os padrões) para a auditoria */ ?>"
           data-cabecalho="<?= $h('Filtros: ' . $this->dados['textoFiltros']) ?>" data-auditoria="<?= URLADM ?>relatorios/exportar" data-linhas="<?= htmlspecialchars((string) ((int) ($t['linhasPorPagina'] ?? 50)), ENT_QUOTES, 'UTF-8') ?>"
           <?= isset($t['ordem']) ? "data-ordem='" . $h(json_encode($t['ordem'])) . "'" : '' ?>>
      <thead>
        <tr>
          <?php foreach ($cols as $c) {
            $oculta = $visiveis ? !in_array($c['chave'], $visiveis, true) : !empty($c['oculta']);
            $cls = trim((!empty($c['num']) ? 'num ' : '') . ($oculta ? 'oculta ' : '') . ($c['classe'] ?? '')); ?>
            <th data-chave="<?= $h($c['chave']) ?>" class="<?= $h($cls) ?>"<?= !empty($c['dica']) ? ' title="' . $h($c['dica']) . '"' : '' ?>><?= $h($c['titulo']) ?></th>
          <?php } ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($t['linhas'] ?? [] as $l) { ?>
          <tr<?= !empty($l['_classe']) ? ' class="' . $h($l['_classe']) . '"' : '' ?>>
            <?php foreach ($cols as $c) {
              [$html, $ordem, $cls] = $celula($l[$c['chave']] ?? ''); ?>
              <td<?= $ordem !== null ? ' data-order="' . $h($ordem) . '"' : '' ?><?= $cls ? ' class="' . $h($cls) . '"' : '' ?>><?= $html ?></td>
            <?php } ?>
          </tr>
        <?php } ?>
      </tbody>
      <?php if (!empty($t['rodape'])) { ?>
        <tfoot>
          <tr>
            <?php foreach ($cols as $c) { [$html] = $celula($t['rodape'][$c['chave']] ?? ''); ?>
              <th><?= $html ?></th>
            <?php } ?>
          </tr>
        </tfoot>
      <?php } ?>
    </table>
  </div>
  <?php
};
$det = $detalhe !== '' ? ($r['detalhes'][$detalhe] ?? null) : null;
?>
<div class="vc-pagina">
  <?php ob_start(); ?><?= $h($def::NOME) ?><?php if ($codTela !== '') : ?> <small class="text-muted" style="font-size:.55em"><?= $h($codTela) ?></small><?php endif; ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php /* Onda 8, correção, segunda leva, 2.11 (D-97): a pergunta do relatório no topo, como o guia diz */ ?>
      <p class="lead mb-3" id="perguntaRelatorio"><?= $h($def::PERGUNTA) ?></p>

      <div class="card shadow-sm">
        <div class="card-body pb-1">
          <form method="GET" action="<?= $h($urlBase) ?>" class="d-flex flex-wrap align-items-center gap-2 flex-wrap" id="filtrosRelatorio">
            <?php foreach ($defs as $k => $d) { $id = 'f_' . $k; ?>
              <div class="mb-3 me-2 mb-2">
                <label class="form-label" for="<?= $h($id) ?>" class="me-1 small text-muted"><?= $h($d['rotulo']) ?><?= htmlspecialchars((string) (!empty($d['obrigatorio']) ? ' *' : ''), ENT_QUOTES, 'UTF-8') ?></label>
                <?php if ($d['tipo'] === 'select') { ?>
                  <select name="<?= $h($k) ?>" id="<?= $h($id) ?>" class="form-select form-control-sm" style="max-width:22rem">
                    <?php if (!isset($d['padrao'])) { ?><option value=""><?= htmlspecialchars((string) (!empty($d['obrigatorio']) ? 'Escolha…' : 'Todos'), ENT_QUOTES, 'UTF-8') ?></option><?php } ?>
                    <?php foreach ($d['opcoes'] ?? [] as $v => $rot) { ?>
                      <option value="<?= $h($v) ?>"<?= htmlspecialchars((string) (isset($f[$k]) && (string) $f[$k] === (string) $v ? ' selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($rot) ?></option>
                    <?php } ?>
                  </select>
                <?php } elseif ($d['tipo'] === 'data') { ?>
                  <input type="date" name="<?= $h($k) ?>" id="<?= $h($id) ?>" class="form-control form-control-sm" value="<?= $h($f[$k] ?? '') ?>">
                <?php } else { ?>
                  <input type="number" min="0" max="999" name="<?= $h($k) ?>" id="<?= $h($id) ?>" class="form-control form-control-sm" style="width:6rem" value="<?= $h($f[$k] ?? '') ?>">
                <?php } ?>
              </div>
            <?php } ?>
            <div class="mb-3 mb-2">
              <button type="submit" class="btn btn-sm btn-primary me-1"><i class="fas fa-filter"></i> Filtrar</button>
              <a href="<?= $h($urlBase) ?>" class="btn btn-sm btn-outline-secondary me-3">Limpar</a>
              <button type="button" class="btn btn-sm btn-outline-info me-1" data-bs-toggle="modal" data-bs-target="#modalFicha"><i class="fas fa-calculator"></i> Como é calculado</button>
              <button type="button" class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal" data-bs-target="#modalSalvar" id="btnSalvarRelatorio"><i class="far fa-bookmark"></i> Salvar</button>
              <?php if ($this->dados['salvos']) { ?>
                <div class="dropdown d-inline-block">
                  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">Salvos</button>
                  <div class="dropdown-menu">
                    <?php foreach ($this->dados['salvos'] as $s) { ?>
                      <a class="dropdown-item" href="<?= $h(Reg::urlSalvo($s)) ?>"><?= $h($s['nome']) ?><?= $s['meu'] ? '' : ' <small class="text-muted">(' . $h($s['dono']) . ')</small>' ?></a>
                    <?php } ?>
                  </div>
                </div>
              <?php } ?>
            </div>
          </form>
        </div>
      </div>

      <?php foreach ($this->dados['avisos'] as $a) { ?>
        <div class="alert alert-warning py-2"><?= $h($a) ?></div>
      <?php } ?>

      <?php if ($this->dados['faltam']) { ?>
        <div class="alert alert-light border"><i class="fas fa-info-circle"></i> Escolha: <strong><?= $h(implode(', ', $this->dados['faltam'])) ?></strong> e clique em Filtrar.</div>
      <?php } elseif ($det) { ?>
        <div class="card shadow-sm">
          <div class="card-header">
            <h3 class="card-title"><a href="<?= $h($link()) ?>" class="btn btn-sm btn-outline-secondary me-2"><i class="fas fa-arrow-left"></i> Voltar ao relatório</a>
              <?= $h($det['titulo']) ?> <span class="badge bg-secondary-lt"><?= htmlspecialchars((string) (count($det['linhas'])), ENT_QUOTES, 'UTF-8') ?></span></h3>
          </div>
          <div class="card-body">
            <?php $tabela($det, trim($codTela . ' ' . $def::NOME) . ' — ' . $det['titulo']); ?>
          </div>
        </div>
      <?php } else { ?>
        <?php if ($detalhe !== '') { ?>
          <div class="alert alert-light border">A lista pedida não existe com estes filtros. <a href="<?= $h($link()) ?>">Voltar ao relatório</a>.</div>
        <?php } ?>
        <?php if (!empty($r['cartoes'])) { ?>
          <div class="row">
            <?php foreach ($r['cartoes'] as $c) {
              /* o número do cartão só é link quando É a contagem da lista; percentual/média abre a lista pelo rodapé, com o tamanho dela */
              $nLista = !empty($c['detalhe']) ? count($r['detalhes'][$c['detalhe']]['linhas'] ?? []) : 0;
              $ehContagem = !empty($c['detalhe']) && (string) $c['valor'] === (string) $nLista; ?>
              <div class="col-6 col-md-4 col-xl-3">
                <?php /* VCOSTAEDU (Etapa 7): o cartão de número do AdminLTE virou um card do Tabler; a cor do model (bg-*) vai na
                   faixa do topo, o número no h1; o link do número e o "Ver a lista" ficam */ ?>
                <div class="card card-sm mb-3 vc-cartao-rel">
                  <?php if (($c['cor'] ?? 'bg-light') !== 'bg-light') { ?><div class="card-status-top <?= $h($c['cor']) ?>"></div><?php } ?>
                  <div class="card-body">
                    <div class="h1 mb-1"><?php if ($ehContagem && $nLista > 0) { ?><a href="<?= $h($link(['detalhe' => $c['detalhe']])) ?>" class="text-reset" title="Abrir a lista"><?= $h($c['valor']) ?></a><?php } else { ?><?= $h($c['valor']) ?><?php } ?></div>
                    <div class="text-secondary"><?= $h($c['rotulo']) ?></div>
                    <?php if (!empty($c['nota'])) { ?><small class="text-muted"><?= $h($c['nota']) ?></small><?php } ?>
                  </div>
                  <?php if ($nLista > 0) { ?>
                    <a href="<?= $h($link(['detalhe' => $c['detalhe']])) ?>" class="card-footer py-2 text-reset text-decoration-none small">Ver a lista (<?= htmlspecialchars((string) ($nLista), ENT_QUOTES, 'UTF-8') ?>) <i class="fas fa-arrow-circle-right"></i></a>
                  <?php } ?>
                </div>
              </div>
            <?php } ?>
          </div>
        <?php } ?>
        <?php if (!empty($r['grafico']['rotulos'])) { ?>
          <div class="card shadow-sm">
            <div class="card-header"><h3 class="card-title"><?= $h($r['grafico']['titulo'] ?? 'Gráfico') ?></h3></div>
            <div class="card-body"><div style="position:relative;height:<?= htmlspecialchars((string) ((int) ($r['grafico']['altura'] ?? 300)), ENT_QUOTES, 'UTF-8') ?>px"><canvas id="graficoRelatorio" data-grafico="<?= $h(json_encode($r['grafico'])) ?>"></canvas></div></div>
          </div>
        <?php } ?>
        <?php foreach (array_merge(isset($r['tabela']) ? [$r['tabela']] : [], $r['tabelas'] ?? []) as $t) { ?>
          <div class="card shadow-sm">
            <?php if (!empty($t['titulo'])) { ?><div class="card-header"><h3 class="card-title"><?= $h($t['titulo']) ?></h3></div><?php } ?>
            <div class="card-body">
              <?php $tabela($t, trim($codTela . ' ' . $def::NOME) . (!empty($t['titulo']) ? ' — ' . $t['titulo'] : '')); ?>
            </div>
          </div>
        <?php } ?>
      <?php } ?>
      <p class="small text-muted">Gerado em <?= date('d/m/Y H:i') ?> por <?= $h($_SESSION['colUsuario_nome'] ?? '') ?> · filtros: <?= $h($this->dados['textoFiltros']) ?> · CPF <?= $this->dados['cpfCompleto'] ? 'completo (permissão do perfil)' : 'mascarado' ?> · <?= number_format($this->dados['tempo'], 2, ',', '') ?> s<?= !empty($this->dados['calculadoEm']) ? ' · <span id="calculadoEmPartes">as notas calculadas em partes às ' . $h(date('H:i', strtotime((string) $this->dados['calculadoEm']))) . '</span>' : '' ?></p>
    </div>
  </section>
</div>

<div class="modal fade" id="modalFicha" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Como é calculado — <?= $h(trim($codTela . ' ' . $def::NOME)) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <p><strong>Pergunta:</strong> <?= $h($def::PERGUNTA) ?><br><strong>Quem usa:</strong> <?= $h($def::QUEM) ?></p>
        <ul class="mb-2">
          <?php foreach ($def->ficha() as $linha) { ?><li><?= $h($linha) ?></li><?php } ?>
        </ul>
        <p class="small text-muted mb-0">Média, resultado, FS/FI, nota mínima e pendência vêm do motor de cálculo e dos valores gravados no encerramento — os mesmos das telas de notas, do boletim e do histórico. Oferta aberta: valores <strong>provisórios</strong>. As definições estão no guia "Relatórios: visão geral".</p>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalSalvar" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form method="POST" action="<?= URLADM ?>relatorios/salvar" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Salvar este relatório</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="relatorio" value="<?= $h($def::CODIGO) ?>">
        <input type="hidden" name="filtros" value="<?= $h(http_build_query($qFiltros)) ?>">
        <input type="hidden" name="colunas" id="salvarColunas" value="">
        <?php $atual = null; foreach ($this->dados['salvos'] as $s) { if ((int) $s['id'] === $this->dados['salvoAtual'] && $s['meu']) { $atual = $s; } } ?>
        <div class="mb-3">
          <label class="form-label" for="salvarNome">Nome</label>
          <input type="text" maxlength="120" class="form-control" id="salvarNome" name="nome" required value="<?= $h($atual['nome'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label" for="salvarPerfil">Compartilhar com o perfil</label>
          <select class="form-select" id="salvarPerfil" name="nivel_acesso_id">
            <option value="">Ninguém (só eu)</option>
            <?php foreach ($this->dados['perfis'] as $id => $nome) { ?>
              <option value="<?= (int) $id ?>"<?= htmlspecialchars((string) ($atual && (int) $atual['nivel_acesso_id'] === (int) $id ? ' selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($nome) ?></option>
            <?php } ?>
          </select>
          <small class="form-text text-muted">Quem é do perfil vê e abre o relatório salvo, mas não altera nem apaga. Cada um vê só os dados que o próprio perfil permite (o CPF, por exemplo).</small>
        </div>
        <?php if ($atual) { ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="id" value="<?= htmlspecialchars((string) ((int) $atual['id']), ENT_QUOTES, 'UTF-8') ?>" id="salvarSobre" checked>
            <label class="form-check-label" for="salvarSobre">Atualizar "<?= $h($atual['nome']) ?>" (desmarque para salvar como novo)</label>
          </div>
        <?php } ?>
        <p class="small text-muted mt-2 mb-0">Guarda os filtros de agora (<?= $h($this->dados['textoFiltros']) ?>) e as colunas visíveis.</p>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save"></i> Salvar</button></div>
    </form>
  </div>
</div>
