<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/*
 * E6c/E7 — AVISO na página inicial (painel e portal). Espera em $avisoNov (AdmsNovidades::avisoPendente)
 * e $rotasNov = ['indice', 'guia', 'novidades', 'visto']. Só vira "visto" no "Entendi" ou no X; até lá, aparece
 * a cada abertura da Página Inicial (E8b, D-253). Usuário novo recebe as boas-vindas (D-219).
 */
if (!empty($avisoNov)) :
  $hN = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
  <div class="alert alert-info shadow-sm position-relative" id="avisoNovidades" data-tipo="<?= $hN($avisoNov['tipo']) ?>">
    <form method="POST" action="<?= $hN($rotasNov['visto']) ?>" class="aviso-fechar" style="position: absolute; top: 6px; right: 10px">
      <button type="submit" name="Entendi" value="1" class="btn-close" aria-label="Fechar o aviso" title="Fechar: conta como visto, como o Entendi"></button>
    </form>
    <?php if ($avisoNov['tipo'] === 'boasvindas') : ?>
      <h5><i class="fas fa-hand-sparkles"></i> Boas-vindas!</h5>
      <p class="mb-2">Para começar, siga o <b>"Comece por aqui"</b> do seu perfil: guias curtos, na ordem em que as coisas acontecem.
        <a href="<?= $hN($rotasNov['indice']) ?>#comecePorAqui" id="linkComece">Abrir o "Comece por aqui" ›</a></p>
    <?php else : ?>
      <h5><i class="fas fa-bullhorn"></i> Novidades da versão <?= $hN($avisoNov['numero']) ?></h5>
      <ul class="mb-2">
        <?php foreach ($avisoNov['itens'] as $nv) : ?><li><b><?= $hN($nv['titulo']) ?></b> — <?= $hN($nv['texto']) ?><?php if ($nv['guia']) : ?> <a href="<?= $hN($rotasNov['guia'] . $nv['guia']) ?>">Ver o guia ›</a><?php endif; ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <form method="POST" action="<?= $hN($rotasNov['visto']) ?>" class="d-inline"><button type="submit" name="Entendi" value="1" class="btn btn-sm btn-info" id="botaoEntendi">Entendi</button></form>
    <small class="text-muted ms-2">Este aviso fica aqui até você clicar em <b>Entendi</b> (ou no ×). Todas as novidades ficam em <a href="<?= $hN($rotasNov['novidades']) ?>">Novidades</a>.</small>
  </div>
<?php endif; ?>
