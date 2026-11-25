<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Cadastrar onde as turmas acontecem e os cursos da instituição.</p>
<h2>Antes de começar</h2>
<p>Tenha o endereço de cada polo e, de cada curso, a modalidade (Presencial, EaD ou Híbrido) e se ele exige TCC.</p>
<h2>Passo a passo</h2>
<ol>
<li>A instalação já traz o polo <strong>Sede</strong>: se a instituição tem um lugar só, ele basta (complete o endereço em <strong>Configurações → Polos</strong>). Para outros polos, <strong>Configurações → Polos → Novo polo</strong>: nome, endereço, cidade e UF.</li>
</ol>
<figure><img src="{{IMG}}administrador_04_polo_cadastrar.png" alt="Cadastro de polo"><figcaption>Cadastro de polo</figcaption></figure>
<ol>
<li><strong>Ensino → Cursos</strong>: cadastre o curso com o <strong>polo padrão</strong>, a <strong>modalidade padrão</strong>, o <strong>turno</strong> (escolhido na lista; some quando a modalidade é <strong>EaD</strong>, porque não se aplica) e <strong>Exige TCC</strong> (com &quot;Sim&quot;, a conclusão exige o TCC aprovado).</li>
</ol>
<p>A <strong>frequência mínima do curso</strong> é opcional: vazio = a da instituição (Configurações → Instituição). Em <strong>Nos documentos</strong> (opcionais): o <strong>nível</strong> (ex.: Especialização), a <strong>sigla</strong>, o <strong>nome por extenso</strong> usado no texto dos documentos e a <strong>linha do curso no cabeçalho</strong>, escrita como deve sair (ex.: em maiúsculas). O Visualizar do curso mostra os quatro.</p>
<figure><img src="{{IMG}}administrador_06_curso_cadastrar.png" alt="Cadastro de curso com Exige TCC"><figcaption>Cadastro de curso com Exige TCC</figcaption></figure>
<ol>
<li>A oferta de cada disciplina e a matrícula do aluno herdam polo e modalidade; a secretaria pode trocá-los em cada uma.</li>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Erro: Não foi possível criar o Curso!&quot;</strong> — confira os campos obrigatórios.</li>
<li>A secretaria não cadastra polos nem cursos: só os escolhe.</li>
<li><strong>&quot;Em uso: … grade(s), … matrícula(s), … oferta(s). Não pode ser apagado.&quot;</strong> — curso em uso não é apagado. O botão Apagar já aparece desabilitado, com essa razão. Se ele não vai mais ser oferecido, <a class="guia-interno" data-guia="inativar-curso-ou-materia" href="{{GUIA}}inativar-curso-ou-materia">inative o curso</a>.</li>
</ul>
