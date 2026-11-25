<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Com o SMTP configurado, o link de acesso vai direto para o e-mail do aluno.</p>
<h2>Antes de começar</h2>
<p>Tenha servidor, porta, usuário e senha da conta de e-mail da instituição.</p>
<h2>Passo a passo</h2>
<ol>
<li><strong>Configurações → Config. E-mail</strong> e preencha os dados do servidor.</li>
</ol>
<figure><img src="{{IMG}}administrador_15_email.png" alt="Configuração de e-mail"><figcaption>Configuração de e-mail</figcaption></figure>
<ol>
<li>Salve e peça à secretaria para enviar um link de teste a um aluno.</li>
<li>Para mudar a configuração depois, abra-a e edite: o campo <strong>Senha</strong> vem <strong>sempre em branco</strong> (a senha gravada não é mostrada). Deixe em branco para manter a atual; preencha só para trocar.</li>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li>Sem SMTP (ou sem e-mail no cadastro do aluno), a tela avisa <strong>&quot;O e-mail NÃO foi enviado&quot;</strong> e mostra o link para a secretaria entregar por um canal seguro.</li>
<li>A senha do e-mail fica só no banco da instituição; ela não aparece em tela, relatório nem guia. Depois de um erro ao salvar, digite a senha de novo: ela não volta preenchida.</li>
</ul>
