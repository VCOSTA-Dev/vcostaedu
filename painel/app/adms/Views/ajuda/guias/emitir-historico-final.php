<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Emitir o documento final do curso, com nota final, frequência, CH, docente e titulação de cada disciplina, TCC, totais, ato legal e código de verificação.</p>
<h2>Antes de começar</h2>
<p>A matrícula está <strong>Concluída</strong> (<a class="guia-interno" data-guia="concluir-o-curso-e-tcc" href="{{GUIA}}concluir-o-curso-e-tcc">Concluir o curso e registrar o TCC</a>) e a <a class="guia-interno" data-guia="previa-do-historico" href="{{GUIA}}previa-do-historico">prévia</a> não tem alertas. O aluno tem CPF e nascimento no cadastro (o modelo padrão pede).</p>
<h2>Passo a passo</h2>
<ol>
<li>Ficha do aluno, aba <strong>Históricos</strong>, <strong>Emitir final</strong>.</li>
<li><strong>Prévia em PDF</strong> para conferir (nada é gravado).</li>
<li>Sem bloqueios, <strong>Emitir histórico FINAL</strong>.</li>
<li>Abra a emissão (<strong>Ver</strong>): código, hashes, PDF e a verificação pública.</li>
</ol>
<figure><img src="{{IMG}}secretaria_21_emissao_final.png" alt="Tela da emissão com os hashes, o PDF e a 2ª via"><figcaption>Tela da emissão com os hashes, o PDF e a 2ª via</figcaption></figure>
<blockquote>O FINAL traz só a tentativa <strong>vigente</strong> de cada disciplina (a última encerrada) e o ato legal da modalidade na data de conclusão.</blockquote>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;O histórico FINAL só é emitido com a matrícula CONCLUÍDA (situação atual: …).&quot;</strong></li>
<li><strong>&quot;O histórico FINAL só é emitido sem nenhum alerta (N alerta(s)).&quot;</strong> — mesmo marcando a confirmação; resolva os alertas listados.</li>
<li><strong>&quot;Variável sem valor no documento: {{ALUNO_CPF}}.&quot;</strong> (ou outra) — complete o cadastro do aluno.</li>
<li>Para acompanhar o aluno antes da conclusão, use o <a class="guia-interno" data-guia="emitir-historico-parcial" href="{{GUIA}}emitir-historico-parcial">histórico parcial</a>.</li>
</ul>
