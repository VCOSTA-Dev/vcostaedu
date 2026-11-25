<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Corrigir uma disciplina já encerrada. Reabrir <strong>não apaga</strong> o resultado: o fechamento fica <strong>guardado como substituído</strong> (com quem reabriu, quando e a justificativa), no quadro <strong>Fechamentos</strong>. Depois de corrigir, a secretaria encerra de novo e o sistema calcula com a regra que valer então (o fechamento seguinte: nº 2, 3…).</p>
<h2>Antes de começar</h2>
<p>Reabrir é uma ação sensível: só o Administrador e a Supervisão de secretaria reabrem. Saiba o motivo: ele fica registrado. Históricos já <strong>emitidos</strong> não mudam (são cópia fiel do dia); emita de novo depois.</p>
<h2>Passo a passo</h2>
<ol>
<li>Abra a disciplina e clique na aba <strong>Encerramento</strong>, no topo. De <strong>Notas e resultado</strong>, o botão <strong>Reabrir</strong> leva para lá.</li>
<li>Escreva o motivo em <strong>Justificativa</strong> e clique em <strong>Reabrir</strong>.</li>
</ol>
<figure><img src="{{IMG}}administrador_14_reabrir_oferta.png" alt="Reabrir disciplina encerrada"><figcaption>Reabrir disciplina encerrada</figcaption></figure>
<ol>
<li>O professor corrige; a secretaria <a class="guia-interno" data-guia="encerrar-uma-disciplina" href="{{GUIA}}encerrar-uma-disciplina">encerra de novo</a>.</li>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Escreva a justificativa da reabertura (ela fica no registro da disciplina).&quot;</strong> — a justificativa é obrigatória.</li>
<li>A secretaria e o professor não reabrem: reabrir é uma ação sensível, do Administrador e da Supervisão de secretaria (<a class="guia-interno" data-guia="perfis-de-acesso" href="{{GUIA}}perfis-de-acesso">Perfis de acesso</a>). Para os demais, o botão aparece desabilitado, com a razão.</li>
</ul>
