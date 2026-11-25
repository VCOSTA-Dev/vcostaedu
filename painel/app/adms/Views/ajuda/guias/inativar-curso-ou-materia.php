<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Um curso ou uma matéria que <strong>já foi usado</strong> (tem grade, oferta, aluno ou nota) não pode ser apagado. Quando ele não vai mais ser oferecido, <strong>inative</strong>: ele sai das listas de escolha e todo o passado fica igual.</p>
<h2>Antes de começar</h2>
<ul>
<li><strong>Curso</strong>: só o administrador inativa. <strong>Matéria</strong>: o administrador e a secretaria.</li>
<li>O que muda com <strong>Inativo</strong>:</li>
<ul>
<li>some das listas de <strong>escolha</strong>: nova grade, nova oferta e vínculo do aluno (curso); matriz e nova oferta (matéria);</li>
<li>continua nas listas de <strong>consulta</strong>, com a etiqueta <strong>Inativo</strong>;</li>
<li>ofertas, notas, faltas e históricos já feitos <strong>não mudam</strong>, e a oferta que já existe continua editável.</li>
</ul>
</ul>
<h2>Passo a passo</h2>
<ol>
<li>Curso: <strong>Ensino → Cursos</strong>. Matéria: <strong>Ensino → Matérias</strong>.</li>
</ol>
<figure><img src="{{IMG}}administrador_05_cursos.png" alt="Lista de cursos, com as ações de cada linha"><figcaption>Lista de cursos, com as ações de cada linha</figcaption></figure>
<ol>
<li>Na linha, clique em <strong>Inativar</strong> (ícone de proibido) e confirme.</li>
<li>A situação passa a <strong>Inativo</strong> e, no lugar do botão, aparece <strong>Reativar</strong>.</li>
<li>Para voltar a oferecer, clique em <strong>Reativar</strong>: ele volta às listas de escolha.</li>
</ol>
<figure><img src="{{IMG}}secretaria_02_materias.png" alt="Lista de matérias, com Inativar e Reativar"><figcaption>Lista de matérias, com Inativar e Reativar</figcaption></figure>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>Não aparece o botão Inativar</strong> — o seu perfil não pode (curso é só do administrador).</li>
<li><strong>O curso não aparece para nova grade, nova oferta ou vínculo</strong> — ele está <strong>Inativo</strong>; reative-o antes.</li>
<li><strong>Apagar desabilitado com &quot;Em uso: …&quot;</strong> — é o caso de inativar: o que já foi usado não é apagado.</li>
</ul>
