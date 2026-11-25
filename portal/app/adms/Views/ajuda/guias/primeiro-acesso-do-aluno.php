<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Criar a sua senha do portal. Ninguém da instituição fica sabendo dela.</p>
<h2>Antes de começar</h2>
<p>A secretaria cadastrou você e enviou um <strong>link de acesso</strong> (por e-mail ou entregue pessoalmente).</p>
<h2>Passo a passo</h2>
<ol>
<li>Abra o link. Ele leva à página <strong>Definir a senha</strong>, que abre em seguida o formulário (o navegador precisa estar com o JavaScript ligado, como já é o normal).</li>
<li>Digite a nova senha duas vezes: pelo menos 12 caracteres; espaços são aceitos (<a class="guia-interno" data-guia="escolher-uma-senha" href="{{GUIA}}escolher-uma-senha">Escolher uma senha</a>).</li>
<li>Clique em <strong>Atualizar Senha</strong>. Pronto: entre no portal (<a class="guia-interno" data-guia="entrar-no-portal" href="{{GUIA}}entrar-no-portal">Entrar no portal</a>).</li>
</ol>
<figure><img src="{{IMG}}aluno_02_primeiro_acesso.png" alt="Definição da senha pelo link"><figcaption>Definição da senha pelo link</figcaption></figure>
<blockquote>O link vale por tempo limitado (48 horas, se a instituição não mudou) e uma vez só. O código do link não aparece na barra de endereço depois que a página abre, e não fica gravado no servidor.</blockquote>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Este link é inválido, já foi usado ou expirou. Peça um novo em Esqueci a senha ou procure a secretaria.&quot;</strong></li>
<li><strong>&quot;Para definir a senha, o navegador precisa do JavaScript ligado. …&quot;</strong> — ligue o JavaScript e abra o link de novo, ou abra-o em outro navegador.</li>
<li><strong>&quot;Este link está num formato antigo e não vale mais. Peça um novo em Esqueci a senha ou procure a secretaria.&quot;</strong> — links emitidos antes da versão 1.7.0 não valem mais: peça outro.</li>
<li><strong>&quot;As duas senhas não conferem.&quot;</strong></li>
<li><strong>&quot;A senha precisa ter pelo menos 12 caracteres.&quot;</strong>, <strong>&quot;Esta senha é muito comum e fácil de adivinhar. Escolha outra.&quot;</strong> ou <strong>&quot;A senha não pode conter o seu nome, o seu usuário ou o seu e-mail.&quot;</strong> — veja <a class="guia-interno" data-guia="escolher-uma-senha" href="{{GUIA}}escolher-uma-senha">Escolher uma senha</a>.</li>
</ul>
