<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Escolher o perfil certo para cada pessoa da equipe e indicar quem coordena cada curso.</p>
<h2>Antes de começar</h2>
<p>Os perfis do sistema:</p>
<table>
<thead><tr><th>Perfil</th><th>Vê</th><th>Altera</th></tr></thead>
<tbody>
<tr><td><strong>Administrador</strong></td><td>tudo</td><td>tudo, inclusive a equipe e a manutenção</td></tr>
<tr><td><strong>Direção acadêmica</strong></td><td>tudo, de todos os cursos</td><td>nada (consulta e relatórios); indica os coordenadores</td></tr>
<tr><td><strong>Coordenação de curso</strong></td><td>só os cursos em que é coordenador: as disciplinas, as notas, as faltas, os boletins e os relatórios</td><td>nada</td></tr>
<tr><td><strong>Supervisão de secretaria</strong></td><td>o mesmo que a Secretaria</td><td>o que a Secretaria altera e as ações sensíveis</td></tr>
<tr><td><strong>Secretaria</strong></td><td>cadastros, matrícula e documentos</td><td>o dia a dia da secretaria</td></tr>
<tr><td><strong>Professor</strong></td><td>as disciplinas dele</td><td>as notas e as faltas dele</td></tr>
</tbody>
</table>
<p><strong>Ações sensíveis</strong> (só a Supervisão de secretaria e o Administrador): reabrir uma disciplina encerrada, excluir um encontro da chamada, desfazer uma importação e apagar um aluno.</p>
<p>Os perfis vêm prontos e não se editam numa tela: mudam com a atualização do sistema. Cada ação tem um <strong>limite</strong>, o perfil mais baixo que pode recebê-la (lançar notas vai até o Professor; reabrir uma disciplina, até a Supervisão de secretaria). O sistema recusa a ação para um perfil abaixo do limite, mesmo que ela tenha sido dada a ele por engano.</p>
<h2>Passo a passo</h2>
<ol>
<li>Para ver o que cada perfil pode fazer, abra <strong>Equipe → Perfis de acesso</strong> (ou o botão <strong>Perfis de acesso</strong> na lista de usuários): as ações, agrupadas, com o limite de cada uma e um ✓ em cada perfil que pode. A tela é só para consultar.</li>
<li>Cadastre a pessoa em <strong>Equipe → Usuários → Cadastrar</strong> e escolha o perfil no campo <strong>Nível de acesso</strong> (<a class="guia-interno" data-guia="usuarios-e-niveis" href="{{GUIA}}usuarios-e-niveis">Usuários da equipe</a>).</li>
<li>Para a <strong>Coordenação de curso</strong>: abra o curso em <strong>Ensino → Cursos</strong>, clique em <strong>Adicionar coordenador</strong>, escolha a pessoa e a posição e clique em <strong>Salvar</strong>. Só o Administrador e a Direção acadêmica veem esse botão.</li>
<li>A pessoa coordenadora entra e encontra em <strong>Minhas disciplinas</strong> as disciplinas dos cursos que coordena, e em <strong>Relatórios</strong> só os dados desses cursos.</li>
<li>Para tirar a coordenação, use o botão de desligar na lista de coordenadores do curso.</li>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Você não tem acesso a esta tela&quot;</strong> — a pessoa abriu (por um link antigo, um favorito ou o endereço) uma tela que o perfil dela não alcança. Ela continua conectada: <strong>Voltar</strong> leva à página de antes e <strong>Página Inicial</strong> ao começo. Se ela precisa da tela no trabalho, o administrador libera a ação no perfil, aqui em Perfis de acesso.</li>
<li><strong>&quot;Este conteúdo é de um curso que você não coordena.&quot;</strong> — a Coordenação de curso abriu o endereço de uma disciplina, de um aluno ou de um relatório de outro curso. O sistema recusa e volta para Minhas disciplinas.</li>
<li>A Direção acadêmica ou a Coordenação de curso não encontra o botão de salvar: esses perfis só consultam. Quem altera é a Secretaria, a Supervisão de secretaria ou o Administrador.</li>
<li>A Coordenação de curso não vê nenhuma disciplina: falta indicar a pessoa como coordenadora na tela do curso.</li>
<li><strong>&quot;Só o administrador ou a supervisão da secretaria reabre uma oferta encerrada.&quot;</strong> — reabrir é uma ação sensível.</li>
<li>A conferência do instalador ou a verificação do ambiente reprova com <strong>&quot;permissão acima do perfil&quot;</strong>: um perfil recebeu uma ação abaixo do limite dela (a mensagem diz qual). O sistema já recusa essa ação; peça ao suporte técnico da instalação para corrigir a permissão.</li>
</ul>
