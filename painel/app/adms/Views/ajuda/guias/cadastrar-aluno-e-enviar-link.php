<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Criar o aluno no sistema e dar a ele o acesso ao portal sem que ninguém da equipe conheça a senha.</p>
<h2>Antes de começar</h2>
<p>Tenha o <strong>RA</strong> (só números, até 9 dígitos) e, se possível, o e-mail do aluno.</p>
<h2>Passo a passo</h2>
<ol>
<li><strong>Educacional → Usuários Acadêmico</strong>, <strong>Cadastrar aluno</strong>.</li>
</ol>
<figure><img src="{{IMG}}secretaria_08_alunos.png" alt="Lista de alunos"><figcaption>Lista de alunos</figcaption></figure>
<ol>
<li>Preencha o <strong>RA</strong> (obrigatório e único), o nome, o <strong>sexo</strong> (obrigatório: os documentos concordam com ele, ALUNA/ALUNO) e o que tiver (CPF e e-mail são opcionais, mas precisam ser válidos e únicos). O aluno nasce <strong>sem senha</strong>.</li>
</ol>
<figure><img src="{{IMG}}secretaria_09_aluno_cadastrar.png" alt="Cadastro de aluno"><figcaption>Cadastro de aluno</figcaption></figure>
<ol>
<li>Na ficha, clique em <strong>Enviar link de acesso</strong>.</li>
<li>Com o e-mail configurado, o link vai para o aluno. Sem e-mail, a tela avisa <strong>&quot;O e-mail NÃO foi enviado&quot;</strong> e mostra o link para você entregar por um canal seguro.</li>
</ol>
<figure><img src="{{IMG}}secretaria_11_link_de_acesso.png" alt="Link de acesso gerado (no guia, o link está mascarado)"><figcaption>Link de acesso gerado (no guia, o link está mascarado)</figcaption></figure>
<blockquote>O link vale por 48 horas (o administrador ajusta), uma vez só; um link novo invalida o anterior.</blockquote>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Escolha o sexo do aluno…&quot;</strong> — o campo não tem mais valor padrão. Alunos antigos que ficaram com &quot;Outros&quot; sem ninguém escolher aparecem em <strong>Alunos sem sexo informado</strong> (botão na lista de alunos): abra, escolha e salve.</li>
<li><strong>&quot;Informe o RA (registro acadêmico).&quot;</strong> e <strong>&quot;O RA deve ter só dígitos, até 9.&quot;</strong></li>
<li><strong>&quot;O RA … já pertence a outro aluno.&quot;</strong> — o aluno já existe: abra a ficha dele.</li>
<li><strong>&quot;CPF inválido (dígitos verificadores não conferem).&quot;</strong> e <strong>&quot;Este CPF já está cadastrado para outro aluno.&quot;</strong></li>
<li><strong>&quot;E-mail inválido.&quot;</strong> e <strong>&quot;Este e-mail já está cadastrado para outro aluno.&quot;</strong></li>
</ul>
