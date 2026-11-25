<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Criar de uma vez as disciplinas do ano a partir da grade, com a carga horária da matriz.</p>
<h2>Antes de começar</h2>
<p>A grade tem disciplinas e não está desativada.</p>
<h2>Passo a passo</h2>
<ol>
<li>Na grade (ou em <strong>Ensino → Todas as Disciplinas</strong>), clique em <strong>Gerar oferta do ano</strong> e escolha o curso e a grade.</li>
<li>As <strong>disciplinas da grade</strong> vêm todas marcadas: desmarque as que não serão oferecidas agora.</li>
<li>Escolha o ano, a turma, o polo, a modalidade (vazio = a do curso) e o <strong>total de encontros</strong>.</li>
<li>Confirme. Sai uma oferta por disciplina marcada. Repetir não duplica: a oferta que já existe é pulada.</li>
</ol>
<figure><img src="{{IMG}}secretaria_06_gerar_oferta.png" alt="Gerar oferta do ano"><figcaption>Gerar oferta do ano</figcaption></figure>
<ol>
<li>Confira em <strong>Todas as Disciplinas</strong> (situação <strong>Aberta</strong>). Depois, <a class="guia-interno" data-guia="completar-a-oferta" href="{{GUIA}}completar-a-oferta">complete cada oferta</a>.</li>
</ol>
<figure><img src="{{IMG}}secretaria_07_ofertas.png" alt="Ofertas do ano"><figcaption>Ofertas do ano</figcaption></figure>
<blockquote>O total de encontros é a base da frequência: sem ele, a disciplina não encerra.</blockquote>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Escolha ao menos uma disciplina.&quot;</strong> — todas ficaram desmarcadas.</li>
<li><strong>&quot;Informe a turma e o ano da oferta.&quot;</strong></li>
<li><strong>&quot;O total de encontros deve ser um número inteiro de 1 a 999 (ou vazio).&quot;</strong></li>
<li><strong>&quot;A grade N está Desativada: não gera oferta.&quot;</strong> — peça ao administrador para reabrir a grade.</li>
</ul>
