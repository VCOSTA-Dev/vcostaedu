<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>O horário da semana mostra, para cada aula do dia, a disciplina e o professor. Ele aparece no portal do aluno e no portal do responsável, com o dia de hoje em destaque.</p>
<p>São duas telas:</p>
<ul>
<li><strong>Horários de aula</strong>: as aulas do dia de cada turno (a 1ª aula, a 2ª aula…), com o início e o fim. A instalação já vem com um horário padrão por turno, que você ajusta ao da escola.</li>
<li><strong>O horário da turma</strong>: a grade da semana da turma, com uma linha para cada aula do turno dela e uma coluna para cada dia.</li>
</ul>
<h2>Antes de começar</h2>
<ul>
<li>A turma tem o turno certo (na tela da turma, <strong>Editar</strong>). Sem turno, vale o primeiro.</li>
<li>O professor de cada disciplina está indicado na tela da disciplina. O horário mostra o professor que está lá.</li>
</ul>
<h2>Passo a passo</h2>
<h3>Ajustar as aulas do dia</h3>
<ol>
<li>Na tela de uma turma, clique em <strong>Horário</strong> e depois em <strong>Horários de aula</strong>. Você também chega lá pelo botão <strong>Horários de aula</strong> na tela do horário de qualquer turma.</li>
<li>Cada turno tem um cartão com as aulas dele. Para mudar o início ou o fim de uma aula, clique em <strong>Editar</strong>, ajuste e clique em <strong>Salvar a alteração</strong>.</li>
<li>Para acrescentar uma aula, preencha <strong>Novo horário neste turno</strong> (o nome, o início e o fim) e clique em <strong>Cadastrar</strong>.</li>
<li><strong>Desativar</strong> tira a aula da grade e do portal sem apagar o que já foi montado nela. <strong>Ativar</strong> devolve.</li>
</ol>
<p>Duas aulas do mesmo turno não podem se sobrepor. Uma pode começar no minuto em que a outra termina.</p>
<h3>Montar a semana da turma</h3>
<ol>
<li>Abra a turma (<strong>Turmas</strong>, clique na turma) e clique em <strong>Horário</strong>.</li>
<li>Em cada aula de cada dia, escolha a disciplina. A escolha <strong>grava na hora</strong>: um sinal de confirmado aparece ao lado e some sozinho. Passe o mouse sobre ele e aparece &quot;Gravado&quot;.</li>
<li>Para deixar uma aula sem disciplina, escolha <strong>Vaga</strong>.</li>
<li>O quadro <strong>Aulas por semana</strong> conta quantas aulas cada disciplina já tem, para você conferir com a carga horária.</li>
<li>Aula no sábado: clique em <strong>Mostrar o sábado</strong>.</li>
</ol>
<p>Se a gravação falhar, aparece um sinal de alerta ao lado da aula, com o motivo escrito. A aula volta ao que estava gravado e o alerta fica até você escolher de novo.</p>
<h2>O que o aluno e o responsável veem</h2>
<p>No portal, <strong>Horário</strong> mostra um cartão para cada dia, com as aulas pela hora, a disciplina e o professor; o filtro <strong>Mostrar</strong> escolhe hoje, a semana ou um dia. Quando a turma ainda não tem horário, o portal diz: &quot;Ainda não há horário cadastrado&quot;.</p>
<h2>O que pode dar errado</h2>
<p><strong>O mesmo professor aparece em duas turmas na mesma aula.</strong> O horário não impede isso. Confira a grade das turmas do mesmo turno quando um professor dá aula em mais de uma.</p>
<p><strong>Mudei o professor da disciplina.</strong> O horário passa a mostrar o novo professor sozinho, sem precisar montar a grade de novo.</p>
<p><strong>&quot;Esse horário se sobrepõe a…&quot;</strong> Duas aulas ativas do mesmo turno não podem ocupar o mesmo tempo. Ajuste o início ou o fim, ou desative a outra aula primeiro.</p>
<p><strong>&quot;Esse horário de aula não está ativo no turno da turma.&quot;</strong> Alguém desativou a aula enquanto a grade estava aberta. Recarregue a página: a aula desativada sai da grade.</p>
