<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 2, Etapa 4: os Primeiros passos — a tela do motor de fases (as fases vêm do banco: adms_fases). */
$d = $this->dados;
$e = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$p = $d['progresso'];
$porGuia = \App\adms\Models\helper\AdmsFacaComigo::class;
/* Onda 3, Etapa 7: "Depois do passo 5", "Depois dos passos 4 e 5", "Depois dos passos 3, 4 e 5" */
$lista = static function (array $n): string {
  $u = array_pop($n);
  return $n ? implode(', ', $n) . ' e ' . $u : (string) $u;
};
$depois = static fn(array $n): string => (count($n) === 1 ? 'Depois do passo ' : 'Depois dos passos ') . $lista($n);
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Primeiros passos<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php /* Onda 4, Etapa 1: o tipo de instituição, e o caminho para trocá-lo enquanto ainda pode (sem dado próprio) */
      if (\App\adms\Models\helper\AdmsNivel::ehAdministrador() && \App\adms\Models\helper\AdmsTipoInstituicao::podeTrocar()['pode']) : ?>
        <div class="alert alert-info" id="tipoNosPrimeirosPassos">Tipo de instituição: <strong><?= htmlspecialchars(\App\adms\Models\helper\AdmsTipoInstituicao::nome(), ENT_QUOTES, 'UTF-8') ?></strong>. Ainda dá para trocar, enquanto não houver dados próprios: <a href="<?= URLADM ?>demonstracao/instituicao" id="trocarTipo">trocar o tipo</a>.</div>
      <?php endif; ?>
      <?php if (!empty($d['anuncio'])) : ?>
        <div class="alert alert-success alert-dismissible" id="faseConcluida" role="status"><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button><i class="fas fa-circle-check me-1"></i><?= $e($d['anuncio']) ?></div>
      <?php endif; ?>
      <?php if (empty($d['demonstracao'])) : /* D-103 (B17): o caminho para conhecer com a demonstração, sempre à mão */ ?>
        <div class="alert alert-secondary" id="primeirosComecar">Ainda não decidiu como começar? <a href="<?= URLADM ?>demonstracao/comecar">Como você quer começar?</a>: conheça o sistema com uma instituição fictícia, ou siga com os dados da sua.</div>
      <?php endif; ?>
      <?php if (!empty($d['demonstracao'])) : ?>
        <div class="alert alert-info" id="primeirosComDemonstracao">A demonstração está ligada: os dados fictícios já cumprem as fases. Para configurar a sua instituição, use <a href="<?= URLADM ?>demonstracao/instituicao">Começar com os dados da minha instituição</a>.</div>
      <?php endif; ?>
      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
              <h3 class="card-title mb-1" id="progressoPrimeiros"><?= htmlspecialchars((string) ((int) $p['feitas']), ENT_QUOTES, 'UTF-8') ?> de <?= htmlspecialchars((string) ((int) $p['total']), ENT_QUOTES, 'UTF-8') ?> fases concluídas<?= htmlspecialchars((string) ($p['puladas'] ? ' (' . (int) $p['puladas'] . ' pulada(s))' : ''), ENT_QUOTES, 'UTF-8') ?></h3>
              <div class="text-secondary">Cada fase se marca sozinha quando o que ela pede está gravado. Pode pular uma fase e voltar a ela quando quiser.</div>
            </div>
            <form method="POST" action="<?= URLADM ?>primeiros-passos/fase">
              <input type="hidden" name="acao" value="<?= htmlspecialchars((string) (!empty($d['dispensado']) ? 'voltar' : 'dispensar'), ENT_QUOTES, 'UTF-8') ?>">
              <button type="submit" class="btn btn-outline-secondary btn-sm" id="<?= htmlspecialchars((string) (!empty($d['dispensado']) ? 'voltarPrimeiros' : 'dispensarPrimeiros'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) (!empty($d['dispensado']) ? 'Mostrar de novo na Página Inicial' : 'Dispensar os Primeiros passos'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
          </div>
          <div class="progress progress-sm mt-3"><div class="progress-bar bg-success" style="width: <?= htmlspecialchars((string) ($p['total'] ? (int) round(100 * $p['feitas'] / $p['total']) : 0), ENT_QUOTES, 'UTF-8') ?>%"></div></div>
        </div>
      </div>
      <div class="list-group list-group-flush" id="listaFases">
        <?php foreach ($d['fases'] as $i => $f) :
          $estado = $f['concluida'] ? 'concluida' : ($f['pulada'] ? 'pulada' : ($f['bloqueada'] ? 'bloqueada' : 'pendente'));
          $rotulo = ['concluida' => ['Concluída', 'bg-success-lt'], 'pulada' => ['Pulada', 'bg-secondary-lt'], 'bloqueada' => [$f['depois'] ? $depois($f['depois']) : 'Depende de outra fase', 'bg-secondary-lt'], 'pendente' => [$f['proxima'] ? 'A próxima' : 'A fazer', $f['proxima'] ? 'bg-primary-lt' : 'bg-warning-lt']][$estado];
          /* travada: "Abrir…" e "Faça comigo" não parecem prontos — apagados, com o motivo ao passar o mouse e ao tocar */
          $motivo = $f['bloqueada'] ? 'Antes, conclua ' . (count($f['depois']) === 1 ? 'o passo ' : 'os passos ') . $lista($f['depois']) . ' (' . implode('; ', $f['faltam']) . ').' : '';
          $travado = static fn(string $classe, string $html): string => '<span class="acao-desabilitada ' . $classe . '-travado" tabindex="0" title="' . htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8')
            . '" data-bs-toggle="tooltip"><button type="button" class="btn btn-sm ' . $classe . '" disabled aria-disabled="true">' . $html . '</button></span>';
          ?>
          <div class="card mb-2 vc-fase<?= htmlspecialchars((string) ($f['proxima'] ? ' border-primary' : ''), ENT_QUOTES, 'UTF-8') ?>" data-fase="<?= $e($f['codigo']) ?>" data-estado="<?= htmlspecialchars((string) ($estado), ENT_QUOTES, 'UTF-8') ?>">
            <div class="card-body">
              <div class="d-flex align-items-start gap-3">
                <span class="avatar <?= htmlspecialchars((string) ($f['concluida'] ? 'bg-success text-white' : 'bg-secondary-lt'), ENT_QUOTES, 'UTF-8') ?>"><?= $f['concluida'] ? '<i class="fas fa-check"></i>' : (int) ($i + 1) ?></span>
                <div class="flex-fill">
                  <h4 class="mb-1"><?= $e($f['titulo']) ?> <span class="badge <?= htmlspecialchars((string) ($rotulo[1]), ENT_QUOTES, 'UTF-8') ?> ms-1 vc-fase-estado"><?= $e($rotulo[0]) ?></span></h4>
                  <div class="text-secondary"><?= $e($f['concluida'] ? $f['feito'] : $f['texto']) ?></div>
                  <?php if ($f['bloqueada']) : ?><div class="small text-secondary mt-1">Antes: <?= $e(implode(', ', $f['faltam'])) ?>.</div><?php endif; ?>
                  <?php if ($f['codigo'] === 'instituicao' && $f['concluida'] && !empty($d['instituicao']['logo'])) :
                    $logo = (string) \App\adms\Models\helper\AdmsInstituicao::logoUrl(); /* o mesmo endereço do menu */ ?>
                    <div class="row g-2 mt-2" id="previaLogo">
                      <div class="col-sm-4"><div class="border rounded p-2 text-center bg-dark"><div class="small text-white-50 mb-1">No menu</div><img src="<?= $e($logo) ?>" alt="Logo no menu" style="max-height:36px;max-width:100%;background:#fff;border-radius:4px;padding:2px"></div></div>
                      <div class="col-sm-4"><div class="border rounded p-2 text-center"><div class="small text-secondary mb-1">Na tela de entrada</div><img src="<?= $e($logo) ?>" alt="Logo na entrada" style="max-height:48px;max-width:100%"></div></div>
                      <div class="col-sm-4"><div class="border rounded p-2"><div class="small text-secondary mb-1">No cabeçalho do documento</div><div class="d-flex align-items-center gap-2"><img src="<?= $e($logo) ?>" alt="Logo no documento" style="max-height:32px"><span class="small fw-bold text-truncate"><?= $e($d['instituicao']['nome']) ?></span></div></div></div>
                    </div>
                  <?php endif; ?>
                  <div class="d-flex flex-wrap gap-2 mt-2">
                    <?php if ($f['bloqueada']) : ?>
                      <?= $travado('vc-fase-abrir', $e($f['rotulo_rota'])) ?>
                    <?php else : ?>
                      <a href="<?= URLADM . $e($f['rota']) ?>" class="btn btn-sm <?= htmlspecialchars((string) ($f['proxima'] ? 'btn-primary' : 'btn-outline-primary'), ENT_QUOTES, 'UTF-8') ?> vc-fase-abrir"><?= $e($f['rotulo_rota']) ?></a>
                    <?php endif; ?>
                    <?php if (!empty($f['roteiro']) && \App\adms\Models\helper\AdmsFacaComigo::carregar((string) $f['roteiro'])) : ?>
                      <?php if ($f['bloqueada']) : ?>
                        <?= $travado('vc-faca-comigo', '<i class="fas fa-handshake-angle"></i> Faça comigo') ?>
                      <?php else : ?>
                        <a href="<?= URLADM . 'faca-comigo/iniciar/' . $e($f['roteiro']) ?>" class="btn btn-sm btn-outline-primary vc-faca-comigo" data-roteiro="<?= $e($f['roteiro']) ?>"><i class="fas fa-handshake-angle"></i> Faça comigo</a>
                      <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!$f['concluida']) : ?>
                      <form method="POST" action="<?= URLADM ?>primeiros-passos/fase" class="d-inline">
                        <input type="hidden" name="codigo" value="<?= $e($f['codigo']) ?>">
                        <?php if ($f['pulada']) : ?>
                          <input type="hidden" name="acao" value="retomar"><button type="submit" class="btn btn-sm btn-outline-secondary vc-fase-retomar">Retomar</button>
                        <?php elseif ((int) $f['pode_pular'] === 1) : ?>
                          <input type="hidden" name="acao" value="pular"><button type="submit" class="btn btn-sm btn-link text-secondary vc-fase-pular">Pular por agora</button>
                        <?php endif; ?>
                      </form>
                    <?php endif; ?>
                  </div>
                  <?php if ($f['codigo'] === 'alunos' && !$f['concluida']) : ?>
                    <?php /* os caminhos que existem nesta instalação: a importação e a API só quando as páginas delas existem */
                    $pend = \App\adms\Models\helper\AdmsAtualizacaoPendente::class;
                    $caminhos = ['<a href="' . URLADM . 'academicos/cadastrar">cadastrar na tela</a>'];
                    if ($pend::paginaExiste('ImportacaoAlunos', 'listar')) { $caminhos[] = '<a href="' . URLADM . 'importacao-alunos/listar">importar de uma planilha</a>'; }
                    if ($pend::paginaExiste('Integracoes', 'documentacao')) { $caminhos[] = '<a href="' . URLADM . 'integracoes/documentacao">integrar com outro sistema pela API</a>'; }
                    if (count($caminhos) > 1) : ?>
                    <?php $ultimo = array_pop($caminhos); ?>
                    <div class="small text-secondary mt-2" id="caminhosAlunos"><?= count($caminhos) === 2 ? 'Três caminhos' : 'Dois caminhos' ?>: <?= implode(', ', $caminhos) ?> ou <?= htmlspecialchars((string) ($ultimo), ENT_QUOTES, 'UTF-8') ?>.</div>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>
