<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Deixar cada oferta pronta: o professor passa a vê-la, a frequência tem base e o histórico tem o período e o docente.</p>
<h2>Antes de começar</h2>
<p>A oferta do ano foi gerada. O professor está cadastrado (é do administrador).</p>
<h2>Passo a passo</h2>
<ol>
<li><strong>Ensino → Todas as Disciplinas</strong>, botão <strong>Editar</strong> da disciplina: <strong>total de encontros</strong> e <strong>período de realização</strong> (início e fim — saem no histórico). A ligação com o Moodle não se digita: com o Moodle conectado, a disciplina vai sozinha (veja <strong>Conectar ao Moodle</strong>).</li>
</ol>
<figure><img src="{{IMG}}secretaria_18_oferta_editar.png" alt="Edição da oferta com o período de realização"><figcaption>Edição da oferta com o período de realização</figcaption></figure>
<ol>
<li>Na mesma lista, o botão verde com o ícone de pessoa: escolha o <strong>professor</strong> e salve.</li>
</ol>
<figure><img src="{{IMG}}administrador_13_ofertas_professor.png" alt="Lista de ofertas"><figcaption>Lista de ofertas</figcaption></figure>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;O fim do período da oferta não pode ser antes do início.&quot;</strong></li>
<li><strong>A disciplina da matriz vem travada</strong>, com a nota <em>&quot;A disciplina da matriz não muda: a oferta já tem alunos ou atividades.&quot;</em> — depois que há aluno ou atividade, a oferta fica presa à disciplina; os outros campos (período, encontros) continuam editáveis.</li>
<li>Sem período, a <a class="guia-interno" data-guia="previa-do-historico" href="{{GUIA}}previa-do-historico">prévia do histórico</a> avisa &quot;oferta sem período de realização&quot;; sem professor, &quot;disciplina sem docente&quot;.</li>
</ul>
