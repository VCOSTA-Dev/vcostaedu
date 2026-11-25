<?php
if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
/*
 * O MENU LATERAL (rail) DO PAINEL — VCOSTAEDU, Etapa 3.
 *
 * Veio do rail do vcostaedu (app/adms/Views/include/rail.php de lá), com o vocabulário de classes `vc-side-*` do
 * vcostable-tabler.css. Adaptado pelos sete pontos da §6.1 do LEVANTAMENTO_DEPARA_TABLER.md:
 *
 *   1. o menu sai do AdmsMenus DAQUI, com os dois filtros dele (Módulos interno e a trava dos níveis da VCOSTA);
 *      nenhuma lista de itens neste arquivo — perfil com permissao = 1 e lib_menu = 1 vê o item;
 *   2. o agrupamento é pelo GRUPO DO BANCO (id_men), como o menu de origem fazia (D-09): grupo com um item só vira
 *      link direto; com mais, vira um grupo que abre e fecha. A coluna `dropdown` não decide nada aqui;
 *   3. o item ATIVO sai da ROTA da página (controller/método da URL), e só sem ela vale o paginaAtiva do controller
 *      (1.18.1 da origem, D-485);
 *   4. versão pelo AdmsVersao daqui (na alça); as novidades e as mensagens continuam nas telas, como na origem;
 *   5. o nome do nível vem da sessão (colUsuario_nivel_nome, gravado no login);
 *   6. no topo, numa linha: o selo do VCOSTAEDU e, ao lado, a instituição (o nome ou o logo, à escolha; sem ela,
 *      VCOSTAEDU); recolhido, só o selo;
 *   7. o localStorage do recolher vai em try/catch (navegador em modo restrito lança ao ler).
 *
 * O recolher (vc-side-mini) e o telefone (navbar-toggler) são os do vcostaedu (D-10).
 * Tudo aqui começa com `rail`: este arquivo é incluído no mesmo escopo da view, e uma variável dele vazaria.
 */
$railMenus = (new \App\adms\Models\AdmsMenus())->itemMenu();
$railH = static fn($s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

/* 3. O item ativo pela rota. */
$railAtiva = (int) ($this->dados['paginaAtiva'] ?? 0);
$railPartes = explode('/', strtolower(trim((string) ($_GET['url'] ?? ''), '/')));
$railRota = ($railPartes[0] ?? '') !== '' ? $railPartes[0] . '/' . (($railPartes[1] ?? '') !== '' ? $railPartes[1] : 'index') : '';
foreach ($railMenus as $railM) {
    if ($railRota !== '' && strtolower($railM['menu_controller'] . '/' . $railM['menu_metodo']) === $railRota) {
        $railAtiva = (int) $railM['id_pg'];
        break;
    }
}

/* 2. Os blocos, na ordem do banco: o grupo entra na posição do seu primeiro item. O grupo da alça (3, "Sair") vai
   para o rodapé do rail. */
const RAIL_GRUPO_DA_ALCA = 3;
$railBlocos = [];
$railOnde = [];
$railAlca = [];
foreach ($railMenus as $railM) {
    $railG = (int) $railM['id_men'];
    if ($railG === RAIL_GRUPO_DA_ALCA) {
        $railAlca[(int) $railM['id_pg']] = $railM;
        continue;
    }
    if (!isset($railOnde[$railG])) {
        $railOnde[$railG] = count($railBlocos);
        $railBlocos[] = ['id' => $railG, 'nome' => (string) $railM['nome_men'], 'icone' => (string) $railM['icone_men'], 'itens' => []];
    }
    $railBlocos[$railOnde[$railG]]['itens'][(int) $railM['id_pg']] = $railM;
}

$railNome = (string) ($_SESSION['colUsuario_nome'] ?? '');
$railPrimeiroNome = explode(' ', trim($railNome))[0] ?? '';
$railInicial = mb_strtoupper(mb_substr($railNome !== '' ? $railNome : '?', 0, 1, 'UTF-8'), 'UTF-8');
$railLogoInst = \App\adms\Models\helper\AdmsInstituicao::logoUrl();
$railTemLogoInst = \App\adms\Models\helper\AdmsInstituicao::temLogo();
$railTopo = \App\adms\Models\helper\AdmsInstituicao::topoMenu();
$railNomeInst = \App\adms\Models\helper\AdmsInstituicao::nome();

$railDesenhar = static function (array $item) use ($railAtiva, $railH): void {
    $ativo = $railAtiva > 0 && $railAtiva === (int) $item['id_pg'];
    $href = URLADM . $item['menu_controller'] . '/' . $item['menu_metodo'] . '/';
    $rotulo = (string) ($item['menu_pagina'] ?: $item['nome_pagina']);
    $icone = (string) ($item['icone_pg'] ?: 'fas fa-circle');
    ?>
    <li class="nav-item">
      <a class="nav-link<?= $ativo ? ' active' : '' ?>" href="<?= $railH($href) ?>" title="<?= $railH($rotulo) ?>"<?= $ativo ? ' aria-current="page"' : '' ?>>
        <span class="nav-link-icon"><i class="<?= $railH($icone) ?>" aria-hidden="true"></i></span>
        <span class="nav-link-title"><?= $railH($rotulo) ?></span>
      </a>
    </li>
    <?php
};
?>
<div class="page">
  <aside class="navbar navbar-vertical navbar-expand-lg vc-side" data-bs-theme="dark" id="railMenu">
    <div class="container-fluid vc-side-inner">

      <div class="vc-side-topo">
        <h1 class="navbar-brand navbar-brand-autodark m-0">
          <a href="<?= URLADM ?>home/index" class="vc-side-marca" id="topoMenu" data-tipo="<?= $railH($railTopo['tipo']) ?>" aria-label="<?= $railH($railTopo['texto']) ?> — ir para a Página Inicial">
            <span class="vc-side-badge vc-side-badge-placa">
              <img src="<?= URL ?>assets/marca/vcostaedu-lockup.svg" alt="" class="vc-side-vcosta">
            </span>
            <?php /* Onda 3, Etapa 5: ao lado do selo, a instituição (o nome ou o logo); sem ela, VCOSTAEDU. Nome longo: reticências, inteiro no title */ ?>
            <?php if ($railTopo['tipo'] === 'logo'): ?>
            <span class="vc-side-nome vc-side-nome-logo" title="<?= $railH($railTopo['texto']) ?>"><img src="<?= $railH($railTopo['logo']) ?>" alt="<?= $railH($railTopo['texto']) ?>"></span>
            <?php else: ?>
            <span class="vc-side-nome" title="<?= $railH($railTopo['texto']) ?>"><?= $railH($railTopo['texto']) ?></span>
            <?php endif; ?>
          </a>
        </h1>

        <button type="button" class="vc-side-recolher d-none d-lg-flex" id="vc-side-recolher"
                title="Recolher o menu" aria-label="Recolher o menu" aria-pressed="false">
          <i class="fas fa-angle-double-left" aria-hidden="true"></i>
        </button>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#sidebar-menu" aria-controls="sidebar-menu"
                aria-expanded="false" aria-label="Abrir ou fechar o menu">
          <span class="navbar-toggler-icon"></span>
        </button>
      </div>

      <div class="collapse navbar-collapse" id="sidebar-menu">
        <ul class="navbar-nav navbar-nav-scroll pt-lg-3" id="railItens">
          <?php foreach ($railBlocos as $railBloco): ?>
            <?php $railItens = array_values($railBloco['itens']); ?>
            <?php if (count($railItens) === 1): ?>
              <?php $railDesenhar($railItens[0]); ?>
            <?php else: ?>
              <?php $railTemAtivo = in_array($railAtiva, array_map(static fn($i) => (int) $i['id_pg'], $railItens), true); ?>
          <li class="vc-side-grupo">
            <button type="button" class="vc-side-grupo-toggle<?= $railTemAtivo ? ' tem-ativo' : '' ?>"
                    aria-expanded="<?= $railTemAtivo ? 'true' : 'false' ?>"
                    aria-controls="vc-grupo-<?= htmlspecialchars((string) ((int) $railBloco['id']), ENT_QUOTES, 'UTF-8') ?>" title="<?= $railH($railBloco['nome']) ?>">
              <?php if ($railBloco['icone'] !== ''): ?>
              <i class="<?= $railH($railBloco['icone']) ?> vc-side-grupo-icone" aria-hidden="true"></i>
              <?php endif; ?>
              <span class="vc-side-grupo-nome"><?= $railH($railBloco['nome']) ?></span>
              <i class="fas fa-chevron-down vc-side-grupo-seta" aria-hidden="true"></i>
            </button>
            <ul class="navbar-nav vc-side-grupo-itens<?= $railTemAtivo ? ' aberta' : '' ?>" id="vc-grupo-<?= htmlspecialchars((string) ((int) $railBloco['id']), ENT_QUOTES, 'UTF-8') ?>">
              <?php foreach ($railItens as $railItem) {
                  $railDesenhar($railItem);
              } ?>
            </ul>
          </li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>

        <div class="vc-side-rodape">
          <ul class="navbar-nav vc-side-acoes" id="vc-side-acoes">
            <?php /* Onda 3, Etapa 10: o tema (claro, escuro, automático) — só depois da atualização que guarda a escolha */
            if (($railTema = \App\adms\Models\helper\AdmsTema::atual()) !== null) : ?>
            <li class="nav-item vc-tema-item"><?= \VcTema::seletor(URLADM . 'tema/salvar', $railTema) ?></li>
            <?php endif; ?>
            <?php /* "Meu perfil" sempre, como o painel do usuário da origem fazia (menu.php, sem passar por permissão). */ ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= URLADM ?>perfil/visualizar" data-vc-fixo>
                <span class="nav-link-icon"><i class="fas fa-user" aria-hidden="true"></i></span>
                <span class="nav-link-title">Meu perfil</span>
              </a>
            </li>
            <?php $railTemSair = false; ?>
            <?php foreach ($railAlca as $railItem): ?>
              <?php $railTemSair = $railTemSair || strtolower($railItem['menu_controller'] . '/' . $railItem['menu_metodo']) === 'login/logout'; ?>
              <?php $railDesenhar($railItem); ?>
            <?php endforeach; ?>
            <?php if (!$railTemSair): /* sair do sistema é a única porta que a matriz não pode fechar */ ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= URLADM ?>login/logout">
                <span class="nav-link-icon"><i class="fas fa-right-from-bracket" aria-hidden="true"></i></span>
                <span class="nav-link-title">Sair</span>
              </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= URLADM ?>novidades/listar" title="Novidades desta versão" data-vc-fixo>
                <span class="nav-link-icon"><i class="fas fa-code-branch" aria-hidden="true"></i></span>
                <span class="nav-link-title d-flex align-items-center w-100">
                  <span>Versão <?= $railH(\App\adms\Models\helper\AdmsVersao::numero()) ?></span>
                  <i class="fas fa-chevron-right ms-auto small" aria-hidden="true"></i>
                </span>
              </a>
            </li>
          </ul>

          <button type="button" class="vc-side-alca nav-link" id="vc-side-alca" aria-expanded="false" aria-controls="vc-side-acoes">
            <span class="nav-link-icon vc-side-user-avatar" aria-hidden="true"><?= $railH($railInicial) ?></span>
            <span class="nav-link-title">
              <span class="d-block" id="usuario-logado"><?= $railH($railPrimeiroNome) ?></span>
              <span class="d-block small text-secondary" id="perfil-logado"><?= $railH((string) ($_SESSION['colUsuario_nivel_nome'] ?? '')) ?></span>
            </span>
            <i class="fas fa-chevron-up vc-side-alca-seta" aria-hidden="true"></i>
          </button>
        </div>
      </div>
    </div>
  </aside>

  <?php /* 7. O estado recolhido, aplicado antes do primeiro desenho (sem pisca), com o localStorage em try/catch. */ ?>
  <script>
    (function () {
      var pagina = document.currentScript.parentElement;
      while (pagina && !pagina.classList.contains('page')) { pagina = pagina.parentElement; }
      if (!pagina) { return; }
      var salvo = null;
      try { salvo = window.localStorage.getItem('vc-side-mini'); } catch (e) { salvo = null; }
      if (salvo === '1') { pagina.classList.add('vc-side-mini'); }
    }());
  </script>

  <div class="page-wrapper">
  <?php include 'app/adms/Views/include_tabler/_aviso_atualizacao.php'; ?>
  <?php include 'app/adms/Views/include_tabler/_faixa_demo.php'; /* Onda 2, Etapa 4 */ ?>
