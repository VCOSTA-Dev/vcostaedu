<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Comprovar que o aluno está matriculado e cursando: curso, carga horária, mês de início e previsão de término. O atestado tem <strong>código de verificação</strong>, como o histórico, e qualquer pessoa confere a autenticidade no endereço impresso.</p>
<h2>Antes de começar</h2>
<ul>
<li>A matrícula precisa estar em <strong>Cursando</strong>. Em outra situação (Trancado, Concluído…), o atestado não sai.</li>
<li>A matrícula precisa ter a <strong>previsão de conclusão</strong> (ficha do aluno, aba <strong>Matrículas</strong>, editar a matrícula). Sem ela, a frase do atestado ficaria quebrada, e a emissão é bloqueada.</li>
<li>Precisa haver um modelo <strong>ativo</strong> do tipo <strong>Atestado de matrícula</strong> que sirva ao curso (<strong>Configurações → Modelos de histórico</strong>, do administrador).</li>
</ul>
<h2>Passo a passo</h2>
<h3>Secretaria</h3>
<ol>
<li>Ficha do aluno, aba <strong>Históricos</strong>, <strong>Emitir atestado</strong> na matrícula. Ou, na <strong>lista de alunos</strong>, o botão com o ícone de documento assinado (abre a matrícula em Cursando mais recente).</li>
</ol>
<div class="print-pendente"><i class="fas fa-camera"></i> Print a fazer antes do treinamento (D-157): aba Históricos com o botão Emitir atestado</div>
<ol>
<li>Confira o modelo e, se quiser, a <strong>Prévia em PDF</strong> (sai com a tarja PRÉVIA; nada é gravado).</li>
<li><strong>Emitir atestado de matrícula</strong>. A emissão guarda a cópia fiel dos dados do dia, o PDF e o código.</li>
</ol>
<blockquote>Com a matrícula fora de Cursando, a ficha mostra o botão desligado e a razão: &quot;atestado só com a matrícula em Cursando&quot;.</blockquote>
<blockquote><strong>Na escola:</strong> a aba da ficha se chama <strong>Documentos</strong> e o painel emite o atestado de matrícula; o histórico escolar da educação básica sai pela vida escolar do aluno (veja <a class="guia-interno" data-guia="emitir-historico-escolar" href="{{GUIA}}emitir-historico-escolar">Emitir o histórico escolar</a>). É o mesmo modelo e o mesmo código de verificação do atestado que o aluno e o responsável emitem pelo portal.</blockquote>
<h3>Aluno (portal)</h3>
<ol>
<li>Menu <strong>Acadêmico → Atestado de matrícula</strong>.</li>
<li>Na matrícula em curso, <strong>Emitir atestado</strong>.</li>
<li>Abra o <strong>PDF</strong> na lista <strong>Atestados emitidos</strong>. Ali ficam também os atestados que a secretaria emitiu para você.</li>
</ol>
<div class="print-pendente"><i class="fas fa-camera"></i> Print a fazer antes do treinamento (D-157): portal, tela Atestado de matrícula com o botão e a lista</div>
<blockquote>O atestado emitido pelo portal não passa pela assinatura da diretoria: no lugar de &quot;(assinatura digital)&quot;, ele traz &quot;Documento emitido eletronicamente em … Confira a autenticidade em … com o código …&quot;.</blockquote>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;O atestado de matrícula só é emitido com a matrícula em &quot;Cursando&quot; (situação atual: …).&quot;</strong> — a situação da matrícula não permite.</li>
<li><strong>&quot;Falta a previsão de conclusão da matrícula …&quot;</strong> — preencha a previsão na aba Matrículas (secretaria). No portal, procure a secretaria.</li>
<li><strong>&quot;O documento tem dado sem valor: …&quot;</strong> — um dado que o modelo usa está vazio no cadastro. Complete e emita de novo.</li>
<li><strong>&quot;O atestado ainda não está disponível para o seu curso.&quot;</strong> (portal) — não há modelo ativo de atestado para o curso; o administrador ativa um.</li>
<li><strong>&quot;Matrícula não encontrada.&quot;</strong> (portal) — o aluno só emite o próprio atestado.</li>
</ul>
