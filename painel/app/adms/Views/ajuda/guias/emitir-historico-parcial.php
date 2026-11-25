<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Dar ao aluno um documento oficial do que ele cursou até agora, com código de verificação.</p>
<h2>Antes de começar</h2>
<p>Confira a <a class="guia-interno" data-guia="previa-do-historico" href="{{GUIA}}previa-do-historico">prévia do histórico</a>. O modelo PARCIAL da instituição está ativo (é do administrador).</p>
<h2>Passo a passo</h2>
<ol>
<li>Ficha do aluno, aba <strong>Históricos</strong>, <strong>Emitir parcial</strong> na matrícula (ou <strong>Educacional → Históricos emitidos</strong>, buscando o aluno).</li>
</ol>
<figure><img src="{{IMG}}secretaria_22_aba_historicos.png" alt="Aba Históricos da ficha do aluno"><figcaption>Aba Históricos da ficha do aluno</figcaption></figure>
<ol>
<li>Confira o <strong>modelo</strong> e clique em <strong>Prévia em PDF</strong>: o documento sai com a tarja <strong>PRÉVIA</strong> e nada é gravado.</li>
<li>Leia os <strong>alertas</strong>. Se houver e mesmo assim for emitir, marque <strong>&quot;Li os alertas acima e emito o PARCIAL assim mesmo&quot;</strong> — eles ficam gravados na emissão.</li>
<li><strong>Emitir histórico PARCIAL</strong>. A emissão guarda uma cópia fiel dos dados do dia, o PDF e o <strong>código</strong> (impresso no rodapé).</li>
</ol>
<figure><img src="{{IMG}}secretaria_23_emitir_parcial.png" alt="Tela de emissão com tipo, modelo, alertas e o botão de prévia"><figcaption>Tela de emissão com tipo, modelo, alertas e o botão de prévia</figcaption></figure>
<blockquote>No PARCIAL, cada disciplina traz as atividades (tipo e data) e a disciplina aberta sai <strong>&quot;Em curso&quot;</strong>, com a média <strong>(parcial)</strong>. O ato legal é o da modalidade da matrícula, na data de hoje.</blockquote>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Há N alerta(s): confirme que os leu para emitir o PARCIAL assim mesmo.&quot;</strong> — marque a confirmação ou resolva os alertas.</li>
<li><strong>&quot;Escolha um modelo ATIVO do tipo Parcial que sirva a este curso (Configurações → Modelos de histórico).&quot;</strong> — peça ao administrador para ativar um modelo.</li>
<li>Corrigiu uma nota depois? O documento emitido não muda: emita de novo.</li>
</ul>
