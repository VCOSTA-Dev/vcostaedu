<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Contar as faltas pela <strong>chamada de cada encontro</strong>, em vez de digitar o total. Cada ausência é uma falta, e o total de encontros da disciplina é o número de encontros registrados. A frequência é a de sempre: <strong>1 − (faltas − compensadas) ÷ encontros</strong>.</p>
<h2>Antes de começar</h2>
<p>Cada disciplina tem <strong>uma fonte</strong> de frequência:</p>
<ul>
<li><strong>Lançamento de faltas</strong> (o padrão): o total digitado ou importado na tela de Faltas;</li>
<li><strong>Chamada por encontro</strong>: as faltas saem da chamada. Na escola, as telas dizem <strong>aula</strong> no lugar de encontro: a fonte é <strong>Chamada por aula</strong>, o botão é <strong>Nova aula</strong> e a data é a <strong>Data da aula</strong>.</li>
</ul>
<p>As duas não se misturam, para a falta não contar duas vezes. A troca para chamada só é possível com as faltas lançadas zeradas. A volta ao lançamento só é possível sem encontro registrado.</p>
<h2>Passo a passo</h2>
<ol>
<li>Nas abas do topo da disciplina, clique em <strong>Chamada</strong>.</li>
</ol>
<div class="print-pendente"><i class="fas fa-camera"></i> Print a fazer antes do treinamento (D-157): tela da chamada com a fonte da frequência e a lista de encontros</div>
<ol>
<li>Se a fonte for &quot;Lançamento de faltas&quot;, clique em <strong>Trocar para Chamada por encontro</strong> e confirme. Na escola, a tela da chamada da disciplina que lança as faltas por etapa explica e oferece <strong>Passar a fazer a chamada</strong> num passo; com falta já lançada na etapa, ela diz por que não troca (a chamada recalcularia a falta de cada etapa pelas aulas) e leva às faltas da etapa — e, em &quot;O meu dia&quot;, o atalho dessa disciplina é <strong>Faltas</strong>.</li>
<li><strong>Novo encontro</strong>: informe a <strong>data</strong> (o horário, o título e o conteúdo são opcionais). Na escola, a data num sábado ou num domingo avisa ao lado do campo e pede a confirmação ao salvar.</li>
<li>Em <strong>Começar com</strong>, escolha <strong>todos presentes</strong> ou <strong>todos ausentes</strong> (a sua escolha fica lembrada para o próximo encontro). Clique em quem é diferente: o botão troca entre <strong>PRESENTE</strong> e <strong>AUSENTE</strong>.</li>
</ol>
<div class="print-pendente"><i class="fas fa-camera"></i> Print a fazer antes do treinamento (D-157): chamada de um encontro com um aluno marcado ausente</div>
<ol>
<li><strong>Salvar a chamada.</strong> O encontro, as presenças e as faltas de cada aluno são gravados de uma vez. Se algo falhar, nada é gravado. O encontro abre de novo, já gravado.</li>
<li><strong>Daqui em diante, cada clique grava na hora</strong>: no encontro já criado (também ao abri-lo pela lista, no lápis), clique na presença do aluno e ela grava, com as faltas recalculadas no mesmo clique. Um sinal pequeno de confirmado aparece ao lado do botão e some sozinho. Se a gravação for recusada (por exemplo, a etapa da data do encontro já está fechada), um sinal de alerta fica ao lado, com o motivo, e o botão não muda. Para mudar a data, o título ou o conteúdo, edite e use <strong>Salvar a chamada</strong>. Para apagar, use a lixeira: as faltas são recalculadas.</li>
<li><strong>Encontros previstos</strong> (opcional): em Editar a disciplina, informe quantos encontros a disciplina terá. Os relatórios de frequência e o alerta de risco passam a contar as faltas que o aluno ainda pode ter sobre esse total. A frequência do boletim continua sobre os encontros registrados. Ao encerrar com menos (ou mais) encontros registrados que os previstos, aparece o aviso &quot;Registrados X de Y previstos&quot;, que não impede o encerramento.</li>
</ol>
<blockquote>A compensação de faltas (atestado, licença…) continua valendo, na tela de Faltas. Com a disciplina <strong>encerrada</strong>, a chamada fica só para leitura; o administrador reabre, com justificativa, e ela volta a ser editável.</blockquote>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Não dá para trocar para chamada: há falta lançada para N aluno(s)…&quot;</strong> — zere as faltas lançadas na tela de Faltas antes.</li>
<li><strong>&quot;Não dá para voltar ao lançamento de faltas: há N encontro(s) registrado(s)…&quot;</strong> — exclua os encontros antes.</li>
<li><strong>&quot;… ausência(s) é menos que as … falta(s) já compensada(s) — remova a compensação antes&quot;</strong> — a chamada não pode deixar o aluno com menos faltas do que as compensadas.</li>
<li><strong>&quot;A oferta está ENCERRADA: a chamada fica só para leitura.&quot;</strong></li>
<li><strong>&quot;Registre ao menos um encontro na chamada&quot;</strong> — no encerramento de uma disciplina com chamada e sem encontro.</li>
</ul>
