<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Saber <strong>que documentos saíram</strong> e <strong>qual pode estar desatualizado</strong>.</p>
<h2>Antes de começar</h2>
<p>Cada emissão gravada entra: histórico parcial, final e atestado, pela secretaria ou pelo portal do aluno.</p>
<h2>Passo a passo</h2>
<ol>
<li>Abra <strong>Relatórios → Documentos emitidos</strong>. Filtre por curso, período de emissão, tipo, origem ou situação (válida ou cancelada).</li>
<li>Veja os cartões por tipo, as <strong>Canceladas</strong> e os <strong>Emitidos antes de reabertura</strong>: uma disciplina do aluno foi reaberta <strong>depois</strong> da emissão, e o documento pode estar desatualizado.</li>
<li>O código abre a emissão, com o PDF. O quadro <strong>Cancelar esta emissão</strong> aparece só para o <strong>administrador</strong>: a secretaria vê a emissão e o PDF, e pede ao administrador o cancelamento (<a class="guia-interno" data-guia="cancelar-uma-emissao" href="{{GUIA}}cancelar-uma-emissao">Cancelar uma emissão</a>).</li>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li>Um documento aparece &quot;emitido antes de reabertura&quot;: confira se a reabertura mudou a nota. Se mudou, emita de novo e peça ao <strong>administrador</strong> que cancele o antigo (só ele cancela).</li>
<li>As consultas à verificação pública não aparecem: o sistema não as registra (fica para uma fase futura).</li>
</ul>
