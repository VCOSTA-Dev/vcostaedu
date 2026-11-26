<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>O sistema atende três tipos de instituição, e cada instalação é de um deles:</p>
<ul>
<li><strong>Ensino superior</strong> — cursos de graduação e pós-graduação: a matriz com módulos, as disciplinas ofertadas, uma média por disciplina, o histórico e o TCC.</li>
<li><strong>Escola (educação básica)</strong> — Ensino Fundamental 1 e 2 e Ensino Médio: séries, turmas, o ano letivo em bimestres ou trimestres, a recuperação, a média anual e o conselho de classe.</li>
<li><strong>Curso livre (curso on-line)</strong> — o curso, a turma com vagas, as disciplinas no Moodle e a inscrição pelo site; sem boletim, etapas, horário, conselho nem documentos. O financeiro e a matrícula pela secretaria são de ligar e desligar (veja <a class="guia-interno" data-guia="instalar-como-curso-livre" href="{{GUIA}}instalar-como-curso-livre">Instalar como curso livre</a>).</li>
</ul>
<p>O tipo decide as telas que aparecem, as palavras da tela (na escola, &quot;Segmentos&quot; no lugar de &quot;Cursos&quot;, &quot;Séries&quot; no lugar de &quot;Módulos&quot;; a disciplina é &quot;disciplina&quot; nos dois) e as regras que vêm prontas. As telas de um tipo não aparecem no outro.</p>
<h2>Antes de começar</h2>
<ul>
<li>Uma instalação que já existia antes da versão 1.38.0 é de <strong>ensino superior</strong> e continua igual: ninguém precisa escolher nada.</li>
<li>O tipo só pode ser trocado enquanto a instalação <strong>não tem dados próprios</strong> (cursos ou segmentos, matérias, alunos, pessoas da equipe) e com a demonstração desligada.</li>
</ul>
<h2>Passo a passo</h2>
<ol>
<li><strong>Na instalação:</strong> depois de criar a conta de administrador, o instalador pergunta o tipo. O ensino superior vem marcado; escolha e clique em <strong>Continuar</strong>. No curso livre, logo abaixo aparecem o <strong>Financeiro</strong> e a <strong>Matrícula pela secretaria</strong>, de ligar e desligar. A última tela, <strong>Como você quer começar?</strong>, mostra o tipo escolhido.</li>
<li><strong>Depois da instalação</strong>, enquanto não houver dados próprios: nos <strong>Primeiros passos</strong>, o aviso no alto mostra o tipo e o link <strong>trocar o tipo</strong> (com a demonstração montada, o mesmo cartão fica em <strong>Começar com os dados da minha instituição</strong>). O cartão <strong>Tipo de instituição</strong> mostra o tipo atual e deixa trocá-lo; escolha e clique em <strong>Gravar o tipo</strong>.</li>
<li>Para conferir o tipo a qualquer momento: <strong>Manutenção → Avisos do sistema</strong> (o cartão Versões) ou a página <strong>Sobre</strong>.</li>
<li>Na escola, <strong>Ensino → Estrutura da escola</strong> mostra os segmentos, as séries de cada um e as disciplinas de cada série, com a carga horária do ano.</li>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>O cartão diz que o tipo não muda mais:</strong> a instalação já tem dados próprios, que dependem do tipo. A troca não é possível sem uma instalação nova.</li>
<li><strong>O cartão diz para remover a demonstração:</strong> os dados fictícios são de um tipo; remova a demonstração e a escolha aparece no mesmo cartão.</li>
<li><strong>A demonstração na escola</strong> é o Colégio Horizonte (fictício); no curso livre, o Instituto Ventoalto (fictício): veja o guia &quot;Conhecer com a demonstração&quot;.</li>
</ul>
