<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$btnMenuRapido = "col-12 col-sm-6 col-md-3 col-lg-2"
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Página Inicial<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php /* E7/E8b (D-219, D-253): aviso de novidades ou boas-vindas, a cada abertura, até o Entendi ou o X */
      $avisoNov = \App\adms\Models\helper\AdmsNovidades::avisoPendente(\App\adms\Models\helper\AdmsCentralGuias::perfil());
      $rotasNov = ['indice' => URLADM . 'ajuda/index', 'guia' => URLADM . 'ajuda/guia/', 'novidades' => URLADM . 'novidades/listar', 'visto' => URLADM . 'novidades/visto'];
      /* Onda 3, Etapa 5: logo depois de montar a demonstração, um aviso só — as boas-vindas dizem que ela está pronta
         (a mensagem separada "A demonstração está pronta" não aparece junto) */
      if (!empty($avisoNov) && isset($_SESSION['msg']) && str_contains((string) $_SESSION['msg'], 'demonstracaoMontada')) {
          $avisoPronta = 'A demonstração está pronta. Explore à vontade: os dados são fictícios.';
          unset($_SESSION['msg']);
      }
      include __DIR__ . '/../novidades/aviso.php'; ?>

      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      if (!empty($this->dados['conviteRegistro'])) { /* Onda 6, Etapa 7: o convite marcado no instalador */
        echo '<div class="alert alert-info" id="conviteRegistro"><i class="fas fa-paper-plane"></i> Você pediu para registrar esta instalação. É voluntário: '
          . '<a href="' . URLADM . 'registro-instalacao/index" class="alert-link">veja exatamente o que vai e envie</a>.</div>';
      }
      include __DIR__ . '/_comecar.php'; /* Onda 2, Etapa 4: começar, conhecer, primeiros passos */
      include __DIR__ . '/_diaProfessor.php'; /* Onda 8, correção, Parte 4 (D-82): na escola, o dia a dia do professor */
      include __DIR__ . '/_livreProfessor.php'; /* D-121 (a correção do ensaio, B2.7): no curso livre, as disciplinas e o Moodle */
      ?>
      <?php
      /* E8b (D-256): os atalhos da Página Inicial saem dos MESMOS itens do menu do usuário (rótulo, ícone, link e
         permissão do perfil). Antes eram fixos no código, com ícones trocados e rótulos diferentes do menu. */
      $hAt = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
      $doMenu = [];
      foreach (($this->dados['menus'] ?? []) as $m) {
        $doMenu[(int) $m['id_pg']] = $m;
      }
      $cartoes = [
        ['Menu Rápido', 'fas fa-table', [91, 115, 46]],      // Todas as Disciplinas, Minhas Disciplinas, Usuários Acadêmico
        ['Ano Letivo', 'fas fa-school', [332, 305, 367]],     // Módulos, Anos Letivos, Matriz
      ];
      /* Onda 8, correção, segunda leva, 2.4 (D-90): na escola, a Página Inicial de cada perfil com o que ele usa no dia a
         dia — a direção, o painel acadêmico, os relatórios e as turmas; a secretaria e a supervisão, os alunos, as turmas,
         os responsáveis e a importação; a coordenação, as turmas e os relatórios do segmento dela (o recorte é o das telas).
         O professor segue com "O meu dia". Cada atalho só aparece com a permissão do perfil (a mesma do menu e da rota);
         a página que não está no menu (o painel acadêmico) entra pela página, com o nome e o ícone dela. */
      $papelHome = \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() && \App\adms\Models\helper\AdmsNivel::comPapeis() ? \App\adms\Models\helper\AdmsNivel::papel() : null;
      $porPapel = [
        \App\adms\Models\helper\AdmsNivel::DIRECAO => ['A direção', 'fas fa-compass', [441, 435, 512]],
        /* Onda 8, financeiro, Partes 1 e 5 (D-98): e o financeiro — os planos de pagamento (562) e a ficha do caixa (573) */
        \App\adms\Models\helper\AdmsNivel::SECRETARIA => ['A secretaria', 'fas fa-id-card', [46, 512, 532, 475, 562, 573]],
        \App\adms\Models\helper\AdmsNivel::SUPERVISAO => ['A secretaria', 'fas fa-id-card', [46, 512, 532, 475, 562, 573]],
        \App\adms\Models\helper\AdmsNivel::COORDENACAO => ['A coordenação do segmento', 'fas fa-sitemap', [512, 435]],
      ];
      if ($papelHome !== null && isset($porPapel[$papelHome])) {
        [$tituloPapel, $iconePapel, $paginasPapel] = $porPapel[$papelHome];
        foreach ($paginasPapel as $pg) {
          if (isset($doMenu[$pg])) {
            continue;
          }
          $lerPg = new \App\adms\Models\helper\AdmsRead();
          $lerPg->fullRead('SELECT id AS id_pg, menu_controller, menu_metodo, menu_pagina, icone AS icone_pg FROM adms_paginas WHERE id = :id AND adms_sits_pg_id = 1 LIMIT :limit', "id={$pg}&limit=1");
          $pagina = $lerPg->getResultado()[0] ?? null;
          if ($pagina && !empty((new \App\adms\Models\AdmsBotao())->valBotao(['p' => ['menu_controller' => $pagina['menu_controller'], 'menu_metodo' => $pagina['menu_metodo']]])['p'])) {
            $pagina['menu_pagina'] = \App\adms\Models\helper\AdmsTipoInstituicao::traduzir((string) $pagina['menu_pagina']);
            $doMenu[$pg] = $pagina;
          }
        }
        $cartoes = [[$tituloPapel, $iconePapel, $paginasPapel]];
      }
      /* 1.18.3 (D-499): nenhum perfil fica com a Página Inicial vazia. Se nenhum dos cartões tem item no menu do
         perfil (um nível criado na tela, por exemplo), um cartão "Atalhos" mostra os itens do menu dele (menos Página Inicial e Sair). */
      $temCartao = false;
      foreach ($cartoes as [, , $paginas]) {
        $temCartao = $temCartao || (bool) array_filter(array_map(fn($id) => $doMenu[$id] ?? null, $paginas));
      }
      if (!$temCartao) {
        $atalhos = array_keys(array_filter($doMenu, fn($m) => !in_array($m['menu_controller'] ?? '', ['home', 'login'], true)));
        if (!$atalhos) { // só Perfil e Sair: o atalho é o próprio perfil
          $doMenu[-1] = ['menu_controller' => 'perfil', 'menu_metodo' => 'visualizar', 'icone_pg' => 'fas fa-user', 'menu_pagina' => 'Meu perfil'];
          $atalhos = [-1];
        }
        $cartoes = [['Atalhos', 'fas fa-bolt', $atalhos]];
      }
      foreach ($cartoes as [$titulo, $icone, $paginas]) {
        $itens = array_values(array_filter(array_map(fn($id) => $doMenu[$id] ?? null, $paginas)));
        if (!$itens) {
          continue;
        } ?>
        <div class="row">
          <div class="col-12">
            <div class="card shadow">
              <div class="card-header">
                <h3 class="card-title"><i class="<?= htmlspecialchars((string) ($icone), ENT_QUOTES, 'UTF-8') ?>"></i> <?= htmlspecialchars((string) ($titulo), ENT_QUOTES, 'UTF-8') ?></h3>
                <div class="card-actions">
                  <button type="button" class="btn btn-action" data-vc-recolher><i class="fas fa-minus"></i></button>
                </div>
              </div>
              <div class="card-body">
                <div class="row">
                  <?php foreach ($itens as $m) { ?>
                    <div class="<?= htmlspecialchars((string) ($btnMenuRapido), ENT_QUOTES, 'UTF-8') ?>">
                      <a href="<?= URLADM . $hAt("{$m['menu_controller']}/{$m['menu_metodo']}/") ?>" class="btn mb-2 btn-lg w-100 btn-flat rounded border-0 btn-outline-dark atalho-rapido" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="<?= $hAt($m['icone_pg']) ?> fa-2x"></i><br><?= $hAt($m['menu_pagina']) ?></a>
                    </div>
                  <?php } ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php } ?>
    </div>
  </section>
</div>
