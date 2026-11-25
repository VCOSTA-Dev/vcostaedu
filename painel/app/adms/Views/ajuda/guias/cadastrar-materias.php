<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Manter a lista de todas as disciplinas que a instituição pode oferecer. A matriz de cada curso é montada com elas.</p>
<h2>Antes de começar</h2>
<p>Tenha o nome, a carga horária padrão e o <strong>código</strong> de cada disciplina (ex.: GP101).</p>
<h2>Passo a passo</h2>
<ol>
<li>Menu <strong>Ensino → Matérias</strong>.</li>
</ol>
<figure><img src="{{IMG}}secretaria_02_materias.png" alt="Lista de matérias"><figcaption>Lista de matérias</figcaption></figure>
<ol>
<li><strong>Cadastrar</strong>: <strong>Código / abreviação</strong> (até 20 caracteres; é o código que sai no histórico — em branco, sai o número interno da matéria), nome, carga horária e uma descrição curta.</li>
<li>Salve. A matéria aparece na lista, com o código.</li>
</ol>
<figure><img src="{{IMG}}secretaria_03_materia_cadastrar.png" alt="Cadastro de matéria com o campo Código / abreviação"><figcaption>Cadastro de matéria com o campo Código / abreviação</figcaption></figure>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>Nome e carga horária são obrigatórios.</strong> A tela não deixa salvar sem eles; se o pedido chegar assim mesmo, o sistema recusa e nada é gravado: <strong>&quot;Erro: não foi possível criar a matéria: Informe o nome da matéria. / Informe a carga horária da matéria.&quot;</strong> A carga horária é um número inteiro de horas, de 1 a 9999. O mesmo vale ao editar (&quot;… alterar a matéria&quot;).</li>
<li>Apagar matéria é do administrador; a secretaria cadastra e edita.</li>
<li><strong>&quot;Em uso: … grade(s) na matriz, … oferta(s). Não pode ser apagado.&quot;</strong> — matéria que está numa grade ou tem oferta não é apagada. O botão Apagar já aparece desabilitado, com essa razão. Se ela não vai mais ser oferecida, <a class="guia-interno" data-guia="inativar-curso-ou-materia" href="{{GUIA}}inativar-curso-ou-materia">inative a matéria</a>.</li>
</ul>
