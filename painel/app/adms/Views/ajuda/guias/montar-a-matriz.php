<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Definir o que o curso exige: a <strong>grade</strong> (a versão da matriz) e a lista das <strong>disciplinas</strong>, cada uma com a sua carga horária (CH). O boletim e o histórico listam essas disciplinas, e a conclusão do curso confere a grade inteira.</p>
<h2>Antes de começar</h2>
<p>As matérias já estão no catálogo (<a class="guia-interno" data-guia="cadastrar-materias" href="{{GUIA}}cadastrar-materias">Cadastrar matérias</a>) e o curso existe (é do administrador).</p>
<h2>Passo a passo</h2>
<ol>
<li>Menu <strong>Ensino → Matriz</strong> e escolha o curso.</li>
</ol>
<figure><img src="{{IMG}}secretaria_04_matriz.png" alt="Matriz: cursos e grades"><figcaption>Matriz: cursos e grades</figcaption></figure>
<ol>
<li><strong>Nova grade</strong>: número (inteiro, único no curso) e descrição.</li>
<li>Na grade, em <strong>Incluir disciplina</strong>, escolha a matéria e clique em <strong>Incluir</strong>. CH vazia = a do catálogo.</li>
<li>A CH da grade é a <strong>soma</strong> das disciplinas, feita pelo sistema. Para mudar a CH de uma disciplina, digite o número na linha e clique no disquete.</li>
<li><strong>Ordem das disciplinas:</strong> a disciplina nova entra no fim. Para a ordem do currículo, <strong>arraste</strong> cada linha pela alça (a primeira coluna, com o número) e clique em <strong>Salvar a ordem</strong>. É a ordem em que as disciplinas saem no histórico.</li>
<li>Pronta, mande a grade para <strong>Em análise</strong>. Quem finaliza é o administrador (<a class="guia-interno" data-guia="situacao-da-grade" href="{{GUIA}}situacao-da-grade">Situação da grade</a>).</li>
</ol>
<figure><img src="{{IMG}}secretaria_05_grade.png" alt="Grade com as disciplinas"><figcaption>Grade com as disciplinas</figcaption></figure>
<blockquote>A grade só muda quando está <strong>Aberta</strong>. Travada, a tela mostra uma faixa com o motivo e o caminho para destravar, e os botões ficam desabilitados, com a razão na dica.</blockquote>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;A grade N já existe no curso … O número da grade não se repete no mesmo curso.&quot;</strong> — use outro número.</li>
<li><strong>&quot;«Matéria» já está nesta grade.&quot;</strong> — a mesma matéria não entra duas vezes.</li>
<li><strong>&quot;A ordem enviada não confere com as disciplinas desta grade…&quot;</strong> — a página ficou desatualizada (alguém incluiu ou removeu uma disciplina): recarregue e arraste de novo. A alça de arrastar só aparece com a grade <strong>Aberta</strong>.</li>
<li><strong>O botão de remover vem desabilitado</strong>, com a dica <em>&quot;Usada por N oferta(s): não pode sair da matriz&quot;</em> — depois que há oferta, a disciplina fica na matriz; só a CH ainda muda.</li>
<li><strong>A faixa de trava</strong> no alto da grade: <em>&quot;A grade está «Finalizada» (ou «Em análise», «Desativada»): a matriz só muda com a grade Aberta.&quot;</em> — e os botões de incluir, remover e arrastar não aparecem. O administrador volta a grade para «Aberta» no quadro <strong>Situação da grade</strong> (com justificativa, se estava Finalizada ou Desativada); a secretaria pede a ele (<a class="guia-interno" data-guia="situacao-da-grade" href="{{GUIA}}situacao-da-grade">Situação da grade</a>).</li>
</ul>
