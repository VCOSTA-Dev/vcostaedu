<?php
if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
/* O rodapé das telas de acesso. Sai o "All rights reserved" da origem, que contradizia a licença (DUVIDAS 4).
   Onda 2, Etapa 1: os avisos legais completos (core/Legal.php), os mesmos do painel, do portal e do instalador. */
?>
<?= \VcLegal::rodape('', URL, 'text-center mt-3') /* Onda 3, Etapa 10: a versão só para quem entrou */ ?>
