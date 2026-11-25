<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 2, Etapa 6: a documentação da API — o Swagger UI servido do próprio sistema (sem CDN), lendo a especificação
   OpenAPI desta instalação. O teste usa a chave colada em "Authorize"; nada sai para outro endereço (validatorUrl nulo). */
$ver = '5.17.14';
?>
<link rel="stylesheet" href="<?= URL ?>assets/plugins/swagger-ui/swagger-ui.css?v=<?= htmlspecialchars((string) ($ver), ENT_QUOTES, 'UTF-8') ?>">
<style>
  /* Onda 3, Etapa 10: a documentação é documento — fica clara nos dois temas; o contraste mínimo de 4,5:1 nos rótulos
     dos métodos (texto escuro sobre a cor deles) e nos links */
  #swaggerUi { background: #FFFFFF; color: #1D273B; border-radius: 6px; }
  #swaggerUi .swagger-ui .opblock .opblock-summary-method { color: #0A1929; }
  #swaggerUi .swagger-ui a, #swaggerUi .swagger-ui .info a, #swaggerUi .swagger-ui .info .url, #swaggerUi .swagger-ui span.url { color: #1C6FB5; }
  #swaggerUi .swagger-ui .btn.authorize { color: #1A6B30; border-color: #1A6B30; }
  #swaggerUi .swagger-ui .btn.authorize svg { fill: #1A6B30; }
</style>
<div class="vc-pagina">
  <?php ob_start(); ?>Documentação da API<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <div class="alert alert-info" id="ajudaDocumentacao">
        A API fica em <code><?= htmlspecialchars(URL . 'api/v1', ENT_QUOTES, 'UTF-8') ?></code>. Para testar aqui: clique em <strong>Authorize</strong>, cole uma chave criada em <a href="<?= URLADM ?>integracoes/chaves">Chaves de API</a> e use <strong>Try it out</strong> numa operação. Com uma chave de escrita, o teste <strong>grava de verdade</strong>. A especificação também está no código, em <code>docs/api/openapi.json</code>.
      </div>
      <p class="text-secondary" id="ajudaInscricao">
        Para outro sistema (o site da instituição, por exemplo) inscrever uma pessoa, a operação <strong>POST /inscricoes</strong> faz o cadastro, a matrícula e a turma (ou as ofertas) de uma vez, tudo ou nada, e manda a pessoa ao Moodle; a descrição dela, em <strong>Alunos</strong>, explica o que acontece lá. O passo a passo está na Ajuda, em <a href="<?= URLADM ?>ajuda/guia/integrar-outro-sistema-pela-api">Integrar outro sistema pela API</a>.
      </p>
      <div class="card"><div class="card-body p-0"><div id="swaggerUi"></div></div></div>
    </div>
  </section>
</div>
<script src="<?= URL ?>assets/plugins/swagger-ui/swagger-ui-bundle.js?v=<?= htmlspecialchars((string) ($ver), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
  window.addEventListener('load', function () {
    window.vcSwagger = SwaggerUIBundle({
      url: <?= json_encode(URLADM . 'integracoes/especificacao') ?>,
      dom_id: '#swaggerUi',
      deepLinking: false,
      validatorUrl: null,
      persistAuthorization: false,
      tryItOutEnabled: false,
      supportedSubmitMethods: ['get', 'post', 'patch'],
      onComplete: function () { document.getElementById('swaggerUi').setAttribute('data-pronto', '1'); }
    });
  });
</script>
