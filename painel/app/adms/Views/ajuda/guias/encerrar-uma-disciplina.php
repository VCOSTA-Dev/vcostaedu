<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Fechar a disciplina: o resultado de cada aluno fica <strong>gravado</strong> (é o que sai no boletim e no histórico) e notas, faltas e atividades <strong>travam</strong>.</p>
<h2>Antes de começar</h2>
<p>Todas as notas e faltas foram lançadas. A oferta tem o total de encontros.</p>
<h2>Passo a passo</h2>
<ol>
<li><strong>Todas as Disciplinas → ver</strong> e <strong>Encerramento</strong>, nas abas do topo da disciplina (ou <strong>Encerrar disciplina</strong>, em Notas e resultado).</li>
</ol>
<figure><img src="{{IMG}}secretaria_14_oferta_aberta.png" alt="Disciplina aberta, com o botão Encerramento"><figcaption>Disciplina aberta, com o botão Encerramento</figcaption></figure>
<ol>
<li>A <strong>prévia</strong> mostra, para cada aluno, média final, frequência e resultado, com a conta, e diz qual <strong>regra de média</strong> vai valer, pelo nome e de onde vem (da disciplina, padrão da instituição ou própria desta oferta).</li>
</ol>
<figure><img src="{{IMG}}secretaria_15_previa_encerramento.png" alt="Prévia do encerramento"><figcaption>Prévia do encerramento</figcaption></figure>
<ol>
<li>Confira (o botão <strong>Imprimir a prévia</strong> imprime a conferência) e clique em <strong>Confirmar encerramento</strong>.</li>
</ol>
<blockquote>O encerramento <strong>guarda a regra</strong> usada e a <strong>memória de cálculo</strong> de cada aluno (&quot;Como foi calculado&quot;, em Notas e resultado e no boletim do aluno). Mudar a regra depois não altera o que foi fechado. Tudo é gravado de uma vez: se algo falhar no meio, nada é gravado. O quadro <strong>Fechamentos</strong> lista o vigente e os anteriores.</blockquote>
<figure><img src="{{IMG}}secretaria_16_oferta_encerrada.png" alt="Disciplina encerrada: notas e resultado gravados"><figcaption>Disciplina encerrada: notas e resultado gravados</figcaption></figure>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Encerramento RECUSADO: Informe o total de encontros da oferta (sem ele não há frequência).&quot;</strong></li>
<li><strong>&quot;Encerramento RECUSADO: A oferta não tem atividade (sem elas não há média).&quot;</strong></li>
<li><strong>&quot;Encerramento RECUSADO: A oferta não tem aluno enturmado.&quot;</strong></li>
<li><strong>&quot;Encerramento RECUSADO: A oferta já está encerrada.&quot;</strong></li>
<li><strong>&quot;Encerramento RECUSADO: Falta cadastrar: Prova (mínimo 1).&quot;</strong> — a regra de média tem um componente <strong>obrigatório</strong> sem atividade suficiente. Cadastre a atividade que falta; até lá, a média do aluno aparece &quot;Pendente — falta Prova&quot;. Ver <a class="guia-interno" data-guia="configurar-a-regra-de-media" href="{{GUIA}}configurar-a-regra-de-media">Configurar a regra de média</a>.</li>
<li><strong>&quot;Encerramento RECUSADO: Atividades acima do máximo: Prova: 2 atividades, o máximo é 1.&quot;</strong> — a regra mudou depois do cadastro. Ajuste as atividades (ou o máximo na regra).</li>
<li><strong>&quot;Encerramento NÃO gravado — nada mudou (…)&quot;</strong> — uma falha no meio do encerramento desfez tudo. Tente de novo; se continuar, procure o suporte.</li>
<li>Encerrou com erro? Só o administrador reabre: <a class="guia-interno" data-guia="reabrir-uma-disciplina" href="{{GUIA}}reabrir-uma-disciplina">Reabrir uma disciplina</a>.</li>
</ul>
