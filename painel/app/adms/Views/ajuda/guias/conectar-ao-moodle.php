<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Ligar o VCOSTAEDU ao Moodle da instituição. Conectado, o aluno, a disciplina, a enturmação, o trancamento e o professor seguem sozinhos para o Moodle, e o aluno entra no Moodle pelo portal, sem senha.</p>
<h2>Antes de começar</h2>
<ul>
<li>O Moodle precisa do plugin <strong>local_vcostaedu</strong> instalado (por quem cuida do Moodle, como administrador do site). O plugin cria no Moodle a página <strong>Conectar ao VCOSTAEDU</strong>, com o endereço e a chave.</li>
<li>A tela fica em <strong>Integrações → Conectar ao Moodle</strong> e é só do Administrador.</li>
</ul>
<h2>Passo a passo</h2>
<ol>
<li>Em <strong>Integrações → Conectar ao Moodle</strong>, cole o <strong>endereço</strong> do Moodle (com https://) e a <strong>chave</strong> que o Moodle mostrou.</li>
<li>Confira <strong>as escolhas da instituição</strong> — cada uma diz o que acontece:</li>
</ol>
<p>- <strong>Disciplina no Moodle:</strong> <em>criar pelo VCOSTAEDU</em> (cada disciplina ofertada vira uma disciplina nova no Moodle, na categoria escolhida — você escolhe uma das categorias do Moodle ou cria uma nova) ou <em>vincular a uma que já existe</em> (na tela de cada disciplina, você informa qual é a do Moodle). - <strong>Trancamento ou cancelamento da matrícula:</strong> <em>suspender a inscrição</em> (o padrão: o aluno não entra, e tudo volta na reativação), <em>remover a inscrição</em> ou <em>não fazer nada</em>. - <strong>Professor da disciplina:</strong> inscrever no Moodle (com edição, o padrão, ou sem edição) ou não. O coordenador do curso não é enviado. - <strong>Envio:</strong> <em>automático</em>, a cada mudança (o padrão), ou <em>só pelo botão</em>. - <strong>O e-mail do aluno no Moodle:</strong> o Moodle não aceita conta sem e-mail, e cada conta é de uma pessoa só. O <strong>e-mail da escola</strong> é feito da matrícula no domínio que você informar (por exemplo, 260101@alunos.suaescola.edu.br). Três modos: - <em>sempre o e-mail da escola</em> (o padrão na escola): todo aluno vai com o endereço da escola, tenha ou não e-mail no cadastro; - <em>o e-mail do aluno, ou o da escola</em>: o do cadastro do aluno; sem ele, ou se outro aluno tem o mesmo (irmãos com o e-mail da família), o da escola; - <em>só o e-mail do aluno</em> (o padrão no ensino superior): sem e-mail no cadastro, ou com o mesmo de outro aluno, o aluno fica fora do Moodle, com o motivo em Envios ao Moodle.</p>
<p>O endereço da escola <strong>não recebe mensagens</strong>: o Moodle não consegue mandar nada a ele, e o &quot;Esqueci a senha&quot; do Moodle não funciona para ele. O aluno entra no Moodle <strong>pelo portal</strong> (o botão &quot;Entrar no Moodle&quot;), sem senha do Moodle. O endereço não é gravado no cadastro do aluno. Se o modo ou o domínio mudar, os alunos que o sistema criou no Moodle vão com o endereço novo; a conta que já existia no Moodle, e que o sistema só ligou ao aluno, fica com o e-mail dela.</p>
<ol>
<li>Clique em <strong>Salvar</strong> e depois em <strong>Testar conexão</strong>. Dando certo, a tela mostra o nome e a versão do Moodle e que as 17 funções do serviço estão lá; os envios começam.</li>
<li>A <strong>saúde da conexão</strong>, na mesma tela (e num cartão em Manutenção → Avisos do sistema): a última chamada que deu certo, quantos envios estão pendentes e quantos falharam.</li>
<li><strong>Desconectar</strong> apaga a chave e para os envios; o que já está ligado ao Moodle continua ligado.</li>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;O Moodle não reconheceu a chave.&quot;</strong> — a chave foi trocada ou apagada no Moodle: gere de novo em &quot;Conectar ao VCOSTAEDU&quot;, no Moodle, e cole aqui.</li>
<li><strong>&quot;O Moodle recusou o acesso ao serviço.&quot;</strong> — os serviços web estão desligados no Moodle, ou falta uma função no serviço do VCOSTAEDU.</li>
<li><strong>&quot;Endereço sem https:// só é aceito numa instalação local.&quot;</strong> — use o endereço https:// do Moodle.</li>
<li>Os outros avisos estão em <strong>Quando algo não vai com o Moodle</strong> (o glossário), na mesma tela e na Ajuda.</li>
</ul>
