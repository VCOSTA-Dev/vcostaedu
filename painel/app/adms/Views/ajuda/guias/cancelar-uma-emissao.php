<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Invalidar um histórico emitido por engano ou substituído. O documento <strong>não é apagado</strong>: a verificação pública passa a dizer <strong>CANCELADO</strong>, com a data.</p>
<h2>Antes de começar</h2>
<p>Só o administrador cancela. Tenha o motivo (fica gravado).</p>
<h2>Passo a passo</h2>
<ol>
<li><strong>Educacional → Históricos emitidos</strong>, <strong>Ver</strong> na emissão.</li>
<li>No quadro <strong>Cancelar esta emissão</strong>, escreva o <strong>motivo</strong> e clique em <strong>Cancelar emissão</strong>.</li>
<li>Para corrigir, peça à secretaria para <strong>emitir de novo</strong> (novo código).</li>
</ol>
<figure><img src="{{IMG}}administrador_16_emissao_cancelar.png" alt="Tela da emissão com o quadro de cancelamento"><figcaption>Tela da emissão com o quadro de cancelamento</figcaption></figure>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Informe o motivo do cancelamento (de 5 a 500 caracteres).&quot;</strong></li>
<li><strong>&quot;A emissão já está cancelada.&quot;</strong> — cancelada não volta a valer; emita outra.</li>
<li><strong>A secretaria não vê o quadro Cancelar esta emissão</strong> — o cancelamento é só do administrador; a secretaria pede a ele, com o motivo.</li>
<li>Apagar ou alterar uma emissão é recusado pelo próprio banco de dados.</li>
</ul>
