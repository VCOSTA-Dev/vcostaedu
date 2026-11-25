<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Qualquer pessoa (um empregador, outra instituição, o próprio aluno) confere se um histórico é autêntico, sem login.</p>
<h2>Antes de começar</h2>
<p>Tenha o <strong>código de verificação</strong> impresso no rodapé do documento (ex.: <code>7KQ2-M8XD-4HPT</code>) ou o QR code.</p>
<h2>Passo a passo</h2>
<ol>
<li>Abra o endereço do sistema seguido de <strong>/verificar</strong> e digite o código. O QR code já leva à página certa, e o endereço de cada documento é curto: <strong>/verificar/CÓDIGO</strong>.</li>
<li>No topo, o logo e o nome da instituição. Logo abaixo, o <strong>resultado em destaque</strong>:</li>
</ol>
<p>- <strong>Documento válido</strong>; - <strong>Documento cancelado</strong>, com a data; - <strong>Não confere</strong>; - <strong>Código não encontrado</strong>.</p>
<p>Depois vêm os dados mínimos: tipo, data de emissão, instituição, curso, nome do aluno e <strong>CPF mascarado</strong> (<code><strong><em>.</strong></em>.789-**</code>).</p>
<ol>
<li>A <strong>impressão digital do documento</strong> (o hash) fica num bloco com o botão <strong>Copiar</strong>. <strong>Imprimir comprovante de verificação</strong> imprime a página como prova de que você conferiu.</li>
<li>Para conferir o arquivo recebido, envie o PDF em <strong>Conferir PDF</strong>: a página diz se ele é <strong>idêntico</strong> ao emitido (ou a uma 2ª via). O arquivo não é guardado.</li>
</ol>
<figure><img src="{{IMG}}secretaria_20_verificacao.png" alt="Página pública de verificação com um documento válido"><figcaption>Página pública de verificação com um documento válido</figcaption></figure>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Código não encontrado&quot;</strong> — confira o código impresso no documento; se tiver certeza, tente de novo em alguns minutos.</li>
<li><strong>&quot;O arquivo enviado NÃO é idêntico a nenhum documento emitido com este código: ele pode ter sido alterado.&quot;</strong></li>
<li><strong>&quot;Não confere&quot;</strong> — o registro guardado não corresponde ao documento emitido: não aceite o documento; procure a instituição.</li>
</ul>
