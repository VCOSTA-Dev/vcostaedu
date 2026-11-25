<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Registrar as notas de cada atividade. A nota fica como você lançou; o arredondamento é só na média final.</p>
<h2>Antes de começar</h2>
<p>A atividade existe (<a class="guia-interno" data-guia="minhas-disciplinas-e-atividades" href="{{GUIA}}minhas-disciplinas-e-atividades">Minhas disciplinas e atividades</a>) e a disciplina está aberta.</p>
<h2>Passo a passo</h2>
<ol>
<li>Abra a atividade, clique no campo <strong>Nota</strong> do primeiro aluno, digite e aperte <strong>Enter</strong>: o cursor vai na hora para o campo do próximo aluno, pronto para digitar, e a nota grava em segundo plano — pode digitar a próxima sem esperar. Sair do campo com outro valor (Tab ou um clique fora) também grava; <strong>Esc</strong> volta ao que estava gravado.</li>
</ol>
<p>- Gravou: um sinal pequeno de confirmado aparece ao lado do campo e some sozinho (passe o mouse: &quot;Gravado&quot;). - Não gravou: um sinal de alerta fica ao lado do campo de onde você saiu, com o motivo (por exemplo, a etapa fechada). O que você digitou continua lá: volte, corrija e aperte Enter de novo. - A nota fora da escala (acima de 10, ou com mais de 2 casas) nem sai do campo: o cursor fica nele, com o motivo, para corrigir.</p>
<figure><img src="{{IMG}}professor_06_notas.png" alt="Lançamento de notas"><figcaption>Lançamento de notas</figcaption></figure>
<ol>
<li>De <strong>0 a 10</strong>, até <strong>2 casas</strong>; <strong>vírgula ou ponto</strong> (7,5 = 7.5).</li>
<li>Por planilha: na atividade, <strong>Atualizar via Planilha</strong>, com um CSV de colunas <code>rm;nota</code> separadas por <strong>ponto e vírgula</strong> (o que o Excel brasileiro salva). Baixe o <strong>modelo</strong> na própria tela.</li>
</ol>
<pre><code>rm;nota
999999;8,5</code></pre>
<p>- Vírgula ou ponto na nota (8,5 = 8.5). Colunas a mais (ex.: o nome) são ignoradas. - O arquivo pode vir do Excel em UTF-8 ou no formato antigo (Windows): o acento não quebra. - <strong>Tudo ou nada:</strong> se alguma linha estiver errada, nenhuma nota é gravada, e a tela lista cada erro com o <strong>número da linha</strong> do arquivo. - Linha com a nota vazia é pulada.</p>
<h3>Do Moodle para o sistema</h3>
<ol>
<li>No Moodle, o <strong>número de identificação</strong> de cada aluno (perfil do usuário) deve ser o <strong>RA</strong>.</li>
<li>No curso do Moodle, <strong>Notas → Exportar</strong> em planilha (Excel ou CSV), com o &quot;Número de identificação&quot; e a coluna da atividade.</li>
<li>Na planilha, deixe só duas colunas: <strong>rm</strong> (o número de identificação) e <strong>nota</strong> (a da atividade), com o cabeçalho <code>rm;nota</code>. Salve como <strong>CSV (separado por ponto e vírgula)</strong>.</li>
<li>No sistema, abra a atividade correspondente e use <strong>Atualizar via Planilha</strong> com esse arquivo. Repita para cada atividade.</li>
</ol>
<figure><img src="{{IMG}}professor_08_importar_csv.png" alt="Importação de notas por CSV"><figcaption>Importação de notas por CSV</figcaption></figure>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Nota inválida: use um número de 0 a 10 com até 2 casas decimais (vírgula ou ponto).&quot;</strong> — nada é gravado.</li>
</ul>
<figure><img src="{{IMG}}professor_07_nota_invalida.png" alt="Nota inválida recusada"><figcaption>Nota inválida recusada</figcaption></figure>
<ul>
<li><strong>&quot;Nenhuma nota foi gravada. Corrija as linhas abaixo e envie de novo:&quot;</strong>, com uma linha por erro: <strong>RA … inexistente</strong>, <strong>aluno … não está enturmado nesta oferta</strong>, <strong>nota … fora da escala (0 a 10)</strong>, <strong>nota … em formato inválido</strong> ou <strong>RA … repetido no arquivo</strong>. Corrija as linhas indicadas: nada foi gravado.</li>
<li><strong>&quot;o cabeçalho deve ter as colunas rm e nota…&quot;</strong> — use o modelo; o separador é o ponto e vírgula.</li>
<li><strong>&quot;Oferta encerrada: a nota não pode mais ser alterada.&quot;</strong> — peça a reabertura à secretaria (quem reabre é o administrador).</li>
</ul>
