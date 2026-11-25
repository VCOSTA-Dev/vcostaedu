<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Acompanhar o que o VCOSTAEDU manda para o Moodle. Salvar um aluno, uma matrícula ou uma enturmação <strong>nunca falha por causa do Moodle</strong>: cada coisa a enviar vira um item numa fila; o sistema tenta enviar na hora e, se não der, o item fica pendente, com o motivo.</p>
<h2>Antes de começar</h2>
<p>O Moodle precisa estar conectado (Integrações → Conectar ao Moodle, pelo Administrador). Sem conexão, nada vai para a fila.</p>
<h2>Passo a passo</h2>
<ol>
<li>Abra <strong>Integrações → Envios ao Moodle</strong>. No alto, quantos envios estão pendentes, quantos falharam e quando foi a última chamada que deu certo.</li>
<li>Filtre por situação: <strong>Pendente</strong>, <strong>Enviado</strong>, <strong>Enviado com aviso</strong> ou <strong>Falhou</strong>. Cada falha diz o que aconteceu e <strong>o que fazer</strong>.</li>
<li>Resolvido o motivo, clique em <strong>Tentar de novo</strong> na linha. Quando o motivo era o e-mail do aluno, basta corrigir o cadastro: salvo o aluno, o usuário dele vai ao Moodle e as inscrições dele que tinham falhado pelo mesmo motivo voltam à fila sozinhas.</li>
</ol>
<p>O que fica pendente (por exemplo, o que não coube no tempo de uma tela) e o que falhou por um motivo passageiro (o Moodle fora do ar, sem resposta) <strong>andam sozinhos</strong>: a cada poucos segundos, uma das telas ou chamadas da interface manda um pouco do que sobrou, sem clique de ninguém (a falha passageira volta com espera crescente, até uma hora). O que só uma pessoa resolve (sem e-mail, o e-mail em mais de uma conta) não é tentado de novo sozinho.</p>
<ol>
<li><strong>O e-mail em mais de uma conta do Moodle:</strong> o VCOSTAEDU não escolhe sozinho. Na linha do envio aparece a lista das contas (o número de cada uma no Moodle): escolha a da pessoa e clique em <strong>Ligar a esta conta</strong>; o envio segue com ela.</li>
<li><strong>Enviar tudo o que falta</strong> envia a fila inteira em passos curtos, com a janela de progresso. Na primeira vez, ele também manda o que já existia antes da conexão (as disciplinas abertas, os alunos enturmados nelas e os professores). Se fechar a página, nada se perde: o botão <strong>Continuar o envio</strong> retoma.</li>
<li>Para o reenvio não depender de alguém usar o sistema (a instalação parada à noite, por exemplo), agende no servidor o comando <code>php database/ferramentas/moodle_enviar.php</code>, rodado da pasta da instalação; ele faz o mesmo que <strong>Enviar tudo o que falta</strong> e sai com o código 1 se o Moodle não responder (o que falta fica para a próxima vez). Não é obrigatório.</li>
</ol>
<p>- <strong>Linux (cron):</strong> a cada 15 minutos, <code><em>/15 </em> <em> </em> * cd /caminho/da/instalacao &amp;&amp; php database/ferramentas/moodle_enviar.php &gt; /dev/null 2&gt;&amp;1</code>. - <strong>Windows (Agendador de Tarefas):</strong> uma tarefa que repete a cada 15 minutos, com o programa <code>php.exe</code>, o argumento <code>database/ferramentas/moodle_enviar.php</code> e a pasta de início na pasta da instalação.</p>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;O aluno não tem e-mail cadastrado.&quot;</strong> — o Moodle exige e-mail: cadastre na ficha do aluno; o envio vai sozinho. Ou escolha, em Conectar ao Moodle, um modo com o e-mail da escola.</li>
<li><strong>&quot;O e-mail do cadastro do aluno é o mesmo de outro aluno.&quot;</strong> — irmãos com o e-mail da família: cada conta do Moodle é de uma pessoa só. Cadastre um e-mail só do aluno, ou escolha um modo com o e-mail da escola.</li>
<li><strong>&quot;O Moodle não respondeu&quot;</strong> ou <strong>&quot;demorou demais&quot;</strong> — o que falta fica na fila; use <strong>Tentar de novo</strong> mais tarde, ou <strong>Enviar tudo o que falta</strong>.</li>
<li><strong>&quot;Enviado com aviso&quot;</strong> — o Moodle gravou a inscrição, mas não mandou o e-mail de aviso dele (&quot;Message was not sent.&quot;): a inscrição está certa; quem cuida do Moodle confere o e-mail dele.</li>
<li><strong>&quot;Enviado com aviso: a conta está SUSPENSA no Moodle&quot;</strong> — o aluno já tinha conta no Moodle (achada pelo e-mail) e ela foi ligada, mas está suspensa lá: a pessoa não entra no Moodle até quem cuida do Moodle reativar a conta.</li>
<li>A pessoa que <strong>já tem conta no Moodle</strong> (com o mesmo e-mail) é ligada a ela, sem criar outra.</li>
<li>Mudou o <strong>nome ou a turma da disciplina</strong> no VCOSTAEDU: a disciplina que o VCOSTAEDU criou no Moodle muda de nome junto. A que foi só <strong>ligada</strong> a uma disciplina que já existia no Moodle não é renomeada.</li>
<li>O nome ou o e-mail que o aluno muda no próprio perfil do portal entra na fila e vai no próximo <strong>Enviar tudo o que falta</strong>.</li>
</ul>
