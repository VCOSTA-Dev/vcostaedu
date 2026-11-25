<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Registrar a presença: <strong>frequência = 1 − (faltas − compensadas) ÷ total de encontros</strong>. A mínima (padrão 75%) é da instituição.</p>
<h2>Antes de começar</h2>
<p>A oferta tem o <strong>total de encontros</strong> (<a class="guia-interno" data-guia="completar-a-oferta" href="{{GUIA}}completar-a-oferta">Completar a oferta</a>). Se a disciplina usa <strong>chamada</strong> (<a class="guia-interno" data-guia="fazer-a-chamada" href="{{GUIA}}fazer-a-chamada">Fazer a chamada</a>), as faltas vêm dela e esta tela fica só para leitura.</p>
<h2>Passo a passo</h2>
<ol>
<li>Na disciplina, clique em <strong>Faltas</strong> (nas abas do topo, no cartão Faltas da disciplina, ou no atalho de Minhas Disciplinas).</li>
<li>Digite as <strong>faltas</strong> de cada aluno e clique em <strong>Salvar faltas</strong>.</li>
<li>As <strong>compensadas</strong> (atestado, licença…) aparecem na coluna ao lado. Quem lança é a secretaria, com justificativa: <a class="guia-interno" data-guia="compensar-faltas" href="{{GUIA}}compensar-faltas">Compensar faltas</a>.</li>
<li><strong>Por planilha:</strong> em <strong>Importar faltas por planilha</strong> (embaixo da lista), envie um CSV <code>rm;faltas</code> separado por ponto e vírgula; <strong>Baixar o modelo</strong> traz o cabeçalho e uma linha de exemplo. Tudo ou nada: com uma linha errada, nada é gravado e a tela mostra a linha. Do Moodle (Presença), use o total de ausências de cada aluno, com o RA no número de identificação.</li>
</ol>
<figure><img src="{{IMG}}professor_09_faltas.png" alt="Lançamento de faltas"><figcaption>Lançamento de faltas</figcaption></figure>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;RA …: N faltas é mais que o total de M encontros.&quot;</strong></li>
<li><strong>&quot;RA …: N falta(s) é menos que as C já compensadas (remova a compensação antes).&quot;</strong></li>
<li><strong>&quot;RA …: as faltas devem ser um número inteiro (0 ou mais).&quot;</strong></li>
<li>Frequência <strong>&quot;—&quot;</strong>: falta o total de encontros da oferta.</li>
<li>Disciplina encerrada: as faltas aparecem só para leitura, e a tela diz por quê; a importação é recusada.</li>
<li><strong>&quot;As faltas desta oferta vêm da CHAMADA…&quot;</strong> — a disciplina usa chamada: o lançamento e a importação de faltas são recusados.</li>
</ul>
