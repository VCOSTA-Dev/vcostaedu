<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Responder, quando uma nota ou falta é questionada, quem a lançou ou mudou, quando, qual era o valor antes e de onde veio a mudança (a tela, a importação por planilha ou a integração por API).</p>
<h2>Antes de começar</h2>
<p>Tudo é registrado sozinho, a cada gravação: não há nada a ligar. O registro não pode ser alterado nem apagado por ninguém. O <strong>histórico de uma nota</strong> abre para quem vê a nota (o aluno não vê). O <strong>relatório</strong> abre para o Administrador, a Direção acadêmica e a Supervisão de secretaria; a Coordenação de curso vê só os cursos que coordena.</p>
<h2>Passo a passo</h2>
<ol>
<li>Para uma nota: abra a avaliação na disciplina e clique no ícone do <strong>relógio</strong>, na coluna <strong>Histórico</strong>, na linha do aluno. A janela mostra cada mudança: quando, a ação, o valor antes e depois, quem e a origem.</li>
<li>Para tudo: abra <strong>Relatórios → Auditoria de notas e faltas</strong>. Use os filtros (curso, disciplina, aluno, quem lançou, o quê, período) e marque <strong>Só alterações e exclusões</strong> para ver só o que mudou depois de lançado. Clique em <strong>Filtrar</strong>.</li>
<li>Para guardar o resultado, use os botões de exportação (Excel, CSV ou PDF) acima da tabela.</li>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Nenhuma mudança registrada desde o início da auditoria.&quot;</strong> — a nota não mudou desde que a auditoria começou (versão 1.21.0); o que foi lançado antes não tem histórico.</li>
<li><strong>&quot;Este conteúdo é de um curso que você não coordena.&quot;</strong> — a Coordenação de curso abriu a auditoria de outro curso.</li>
<li>A linha marcada <strong>depois do fechamento</strong> veio de uma mudança feita depois que a disciplina foi reaberta.</li>
<li>O IP mostrado é o do acesso ao servidor. Se todos aparecem com o mesmo IP, o servidor está atrás de um proxy que a instalação não declarou (<code>PROXIES_CONFIAVEIS</code>).</li>
</ul>
