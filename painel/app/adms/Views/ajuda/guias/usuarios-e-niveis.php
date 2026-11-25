<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Dar acesso ao painel a quem trabalha na secretaria e aos professores.</p>
<h2>Antes de começar</h2>
<p>Tenha nome, usuário, e-mail e o perfil de cada pessoa; de cada professor, a <strong>titulação</strong> (Especialista, Mestre, Doutor).</p>
<h2>Passo a passo</h2>
<ol>
<li><strong>Equipe → Usuários → Cadastrar</strong>: nome, usuário, e-mail, senha inicial e o <strong>nível de acesso</strong> (o perfil: veja <a class="guia-interno" data-guia="perfis-de-acesso" href="{{GUIA}}perfis-de-acesso">Perfis de acesso</a>). Para professor, a <strong>titulação</strong>. A lista de níveis oferece só os níveis <strong>abaixo do seu</strong>.</li>
</ol>
<figure><img src="{{IMG}}administrador_09_usuario_cadastrar.png" alt="Cadastro de usuário com a titulação"><figcaption>Cadastro de usuário com a titulação</figcaption></figure>
<ol>
<li>Entregue a senha por um canal seguro e peça a troca no primeiro acesso (<a class="guia-interno" data-guia="entrar-no-painel" href="{{GUIA}}entrar-no-painel">Entrar no painel e trocar a senha</a>).</li>
</ol>
<p>- <strong>Situação.</strong> <strong>Ativo</strong> entra normalmente. <strong>Atualizar Dados</strong> entra e, antes de usar o sistema, vai para a tela <strong>&quot;Atualize seus dados&quot;</strong>, onde confere e salva os dados pessoais; ao salvar, a situação volta sozinha para Ativo. <strong>Inativo</strong> e <strong>Bloqueado</strong> não entram.</p>
<ol>
<li><strong>Quem altera quem.</strong> Você só cadastra, edita, troca a senha, desativa ou apaga quem é de nível <strong>abaixo do seu</strong>.</li>
</ol>
<p>- Na lista, quem é do seu nível ou de um nível acima aparece como <strong>Protegido</strong>, sem os botões de editar e apagar. - Você não aparece na lista: o seu próprio cadastro e a sua senha ficam no <strong>Perfil</strong> (clique no seu nome, embaixo do menu). - A ordem dos níveis, de cima para baixo: <strong>Administrador</strong>, Direção acadêmica, Supervisão de secretaria, Secretaria, Coordenação de curso, Professor; os níveis criados numa versão anterior do sistema vêm depois e continuam valendo.</p>
<ol>
<li><strong>Perfis de acesso</strong> (o que cada perfil pode fazer, só para consultar: <a class="guia-interno" data-guia="perfis-de-acesso" href="{{GUIA}}perfis-de-acesso">Perfis de acesso</a>) e o grupo <strong>Manutenção</strong> são só do Administrador.</li>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Erro: titulação inválida.&quot;</strong></li>
<li><strong>&quot;Erro:Não foi possível cadastrar o usuário!&quot;</strong>: o usuário ou o e-mail já foram usados, ou falta um campo obrigatório. A tela volta com os dados preenchidos, <strong>menos a senha</strong>: por segurança, ela nunca volta. Digite de novo.</li>
<li><strong>&quot;Erro: Você só pode alterar usuários e níveis de acesso abaixo do seu.&quot;</strong>: você tentou alterar alguém do seu nível ou de um nível acima. Quem está acima precisa fazer a alteração.</li>
<li>Professor sem titulação: a prévia do histórico avisa &quot;docente … sem titulação&quot;.</li>
</ul>
