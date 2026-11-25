<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Quando o aluno nasce em outro sistema (o de matrículas, o financeiro, o site de inscrições), a API deixa esse sistema trazer o aluno para cá sem digitar de novo: consultar cursos, grades, anos e ofertas; cadastrar e atualizar alunos; vincular ao curso; trancar, reativar e cancelar a matrícula; e enturmar. Tudo pelas mesmas regras das telas: o que a tela recusa, a API recusa, com a mesma mensagem.</p>
<h2>Antes de começar</h2>
<ul>
<li>Só o Administrador cria chaves e vê o registro das chamadas.</li>
<li>Crie <strong>uma chave por sistema</strong>: assim dá para revogar uma sem parar as outras. Dê a cada chave <strong>só o que o sistema precisa</strong>, assunto por assunto (alunos, matrículas, enturmação, cadastros de apoio), para consultar ou para gravar — gravar inclui consultar o mesmo assunto. Uma chave nova começa sem nada marcado.</li>
<li>A chave aparece <strong>uma vez</strong>, ao ser criada. O sistema guarda só um resumo dela; se ela se perder, revogue-a e crie outra.</li>
<li>O endereço da API é o do sistema seguido de <code>/api/v1</code> (por exemplo, <code>https://escola.exemplo.invalid/api/v1</code>). Use sempre <strong>HTTPS</strong> fora do computador de testes.</li>
</ul>
<h2>Passo a passo</h2>
<ol>
<li>Abra <strong>Integrações → Chaves de API</strong>, escreva o nome de quem vai usar, marque <strong>o que a chave pode fazer</strong> em cada assunto e, se quiser, a validade. Clique em <strong>Criar</strong> e copie a chave.</li>
<li>Entregue a chave ao responsável pelo outro sistema, por um canal seguro. Ele a envia em toda chamada, no cabeçalho <code>Authorization: Bearer &lt;chave&gt;</code>.</li>
<li>Em <strong>Integrações → Documentação da API</strong> estão todas as operações, com os campos e as respostas. Para testar ali mesmo, clique em <strong>Authorize</strong>, cole a chave e use <strong>Try it out</strong> (com uma chave de escrita, o teste grava de verdade).</li>
<li>Do outro sistema, faça as chamadas como nos exemplos abaixo (dados fictícios: troque o endereço e a chave).</li>
<li>Acompanhe em <strong>Integrações → Chamadas da API</strong>: cada chamada, com a chave, a operação, o resultado, o IP e a hora. Na lista das chaves, veja o último uso de cada uma.</li>
<li>Quando um sistema deixar de ser usado (ou a chave vazar), clique em <strong>Revogar</strong>: a partir daí ela é recusada.</li>
</ol>
<p>Cadastrar um aluno:</p>
<pre><code>curl -X POST https://escola.exemplo.invalid/api/v1/alunos \
  -H &quot;Authorization: Bearer vce_0000000000000000000000000000000000000000&quot; \
  -H &quot;Content-Type: application/json&quot; \
  -H &quot;Idempotency-Key: inscricao-2026-0001&quot; \
  -d &#039;{&quot;ra&quot;: &quot;900101&quot;, &quot;nome&quot;: &quot;Aluna Exemplo da Silva&quot;, &quot;sexo&quot;: &quot;feminino&quot;, &quot;email&quot;: &quot;aluna@exemplo.invalid&quot;, &quot;nascimento&quot;: &quot;2000-03-15&quot;}&#039;</code></pre>
<p>E vinculá-la ao curso 1, no ano de 2026:</p>
<pre><code>curl -X POST https://escola.exemplo.invalid/api/v1/matriculas \
  -H &quot;Authorization: Bearer vce_0000000000000000000000000000000000000000&quot; \
  -H &quot;Content-Type: application/json&quot; \
  -d &#039;{&quot;ra&quot;: &quot;900101&quot;, &quot;curso_id&quot;: 1, &quot;ano&quot;: 2026, &quot;forma_ingresso_id&quot;: 1, &quot;data_ingresso&quot;: &quot;2026-02-02&quot;}&#039;</code></pre>
<p><strong>Na escola:</strong> <code>GET /turmas</code> (nos cadastros de apoio) lista as turmas do ano, com a série, o nome e as ofertas de cada uma; <code>POST /turmas/{id}/alunos</code> com a RA põe o aluno na turma (ele entra em todas as disciplinas dela, pela mesma regra da tela), e <code>GET /turmas/{id}/alunos</code> lista quem está nela. Pôr na turma pede o assunto <strong>enturmação</strong> na chave. Numa instalação de ensino superior, essas rotas não existem.</p>
<h2>Inscrever numa chamada só (o site da instituição)</h2>
<p>Para o site que vende um curso, ou o formulário de inscrição, a operação <strong>POST /inscricoes</strong> faz tudo de uma vez: o cadastro da pessoa, a matrícula no curso e a turma (na escola) ou as ofertas (no ensino superior). É <strong>tudo ou nada</strong>: se uma parte é recusada (a turma completa, a turma que não existe, a pessoa noutra turma), nada fica gravado, e a resposta diz a razão, começando por &quot;Nada foi gravado:&quot;. A chave precisa gravar <strong>Alunos, Matrículas e Enturmação</strong>. As operações separadas continuam como sempre.</p>
<pre><code>curl -X POST https://escola.exemplo.invalid/api/v1/inscricoes \
  -H &quot;Authorization: Bearer vce_0000000000000000000000000000000000000000&quot; \
  -H &quot;Content-Type: application/json&quot; \
  -H &quot;Idempotency-Key: site-inscricao-8f2c51d0&quot; \
  -d &#039;{&quot;aluno&quot;: {&quot;ra&quot;: &quot;970001&quot;, &quot;nome&quot;: &quot;Pessoa Exemplo da Silva&quot;, &quot;email&quot;: &quot;pessoa@exemplo.invalid&quot;, &quot;celular&quot;: &quot;61999990000&quot;}, &quot;destino&quot;: {&quot;turma_id&quot;: 12}}&#039;</code></pre>
<p>No ensino superior, o destino é o curso, o ano e as ofertas: <code>&quot;destino&quot;: {&quot;curso_id&quot;: 1, &quot;ano&quot;: 2026, &quot;ofertas&quot;: [10, 11, 12, 13]}</code>.</p>
<p>A resposta diz o que foi feito (<code>&quot;feito&quot;: {&quot;aluno&quot;: &quot;criado&quot;, &quot;matricula&quot;: &quot;criada&quot;, &quot;turma&quot;: &quot;entrou&quot;}</code>) e o estado no Moodle. A mesma matrícula de novo não cria uma segunda pessoa: a resposta diz o que já existia (<code>ja_existia</code>, <code>ja_estava</code>). Gere a <code>Idempotency-Key</code> a partir do <strong>envio do formulário</strong> (o mesmo envio, a mesma chave): o clique duplo manda a mesma chave, e a segunda chamada devolve a mesma resposta, mesmo chegando ao mesmo tempo que a primeira.</p>
<p><strong>O que acontece no Moodle.</strong> Com o Moodle conectado e o envio automático (<strong>Integrações → Conectar ao Moodle</strong>), logo depois de gravar a plataforma manda, na mesma chamada e dentro de um tempo curto: o usuário da pessoa (com o e-mail do modo escolhido em Conectar ao Moodle), a disciplina no Moodle de cada disciplina da turma, se ainda não existe, e a inscrição da pessoa em cada uma. A resposta traz <code>moodle</code>: <code>enviado</code> (o usuário existe lá), <code>disciplinas</code> (em quantas ela já está) e <code>disciplinas_total</code> (quantas são). O que não coube termina <strong>sozinho</strong> nas chamadas e telas seguintes, sem clique de ninguém; <code>GET /alunos/{ra}</code> mostra o andamento. O Moodle fora do ar não impede a inscrição: os dados ficam gravados, e o envio se completa quando ele volta. O que só uma pessoa resolve (a pessoa sem e-mail, no modo &quot;só o e-mail do aluno&quot;) fica em <strong>Integrações → Envios ao Moodle</strong>, com o motivo; corrigido o cadastro, o usuário e as inscrições vão sozinhos.</p>
<p><strong>O primeiro acesso.</strong> Logo depois da inscrição, <code>POST /alunos/{ra}/primeiro-acesso</code> manda à pessoa o e-mail de boas-vindas, com o link para criar a senha (o mesmo que a secretaria manda pela ficha do aluno: de uso único e com prazo). Com <code>&quot;devolver_link&quot;: true</code>, o link também volta na resposta, para o site mostrar um botão &quot;Criar minha senha agora&quot;; com isso, a inscrição deixa de confirmar que o e-mail é da pessoa: use só numa demonstração ao vivo, em que o e-mail pode atrasar. O link aberto de novo, depois de criar a senha, leva à entrada do portal, com o aviso de que a senha já foi criada.</p>
<h2>O que pode dar errado</h2>
<p>Toda resposta de erro vem no mesmo formato: <code>{&quot;erro&quot;: {&quot;codigo&quot;: &quot;...&quot;, &quot;mensagem&quot;: &quot;...&quot;, &quot;campo&quot;: &quot;...&quot;}}</code>.</p>
<ul>
<li><strong>401</strong> (<code>nao_autenticado</code>, <code>chave_revogada</code>, <code>chave_vencida</code>) — a chave não veio, está errada, foi revogada ou venceu.</li>
<li><strong>403</strong> (<code>sem_permissao</code>) — a chave não tem o assunto, ou tem só para consultar e tentou gravar. A mensagem diz o que a chave pode; peça ao administrador uma chave com o limite certo.</li>
<li><strong>422</strong> (<code>validacao</code>) — o dado foi recusado pela regra da tela (CPF inválido, sexo faltando, curso inexistente…); o <code>campo</code> diz qual.</li>
<li><strong>409</strong> (<code>conflito</code>) — a operação não cabe no estado atual (o aluno já tem matrícula no curso; reativar uma matrícula cancelada), ou a mesma <code>Idempotency-Key</code> veio com outra requisição.</li>
<li><strong>403</strong> (<code>https_obrigatorio</code>) — a instalação exige HTTPS e a chamada veio por <code>http://</code>. Chame por <code>https://</code>; se a chave foi enviada por HTTP, revogue-a e crie outra.</li>
<li><strong>429</strong> (<code>limite_excedido</code>) — a chave passou do limite de chamadas por minuto (120 de início). Espere o tempo do cabeçalho <code>Retry-After</code>. O limite fica em <strong>Parâmetros</strong>, em &quot;Integrações: chamadas por minuto de cada chave da API&quot; (de 10 a 10000): aumente quando um sistema ligado faz muitas chamadas de uma vez (o site de inscrições num período de matrícula, uma importação grande) e o registro de chamadas mostra respostas 429.</li>
<li><strong>Cadastrar um RA que já existe</strong> não é erro: a API devolve o aluno existente (<code>&quot;criado&quot;: false</code>), sem duplicar e sem mudar nada. Para mudar os dados dele, use o <code>PATCH</code>.</li>
<li><strong>422 com &quot;Nada foi gravado:&quot;</strong> (na inscrição numa chamada só) — uma parte foi recusada (a turma completa, por exemplo) e nada ficou gravado; a mensagem diz a razão.</li>
<li><strong>Repetir uma escrita por falha de rede:</strong> mande a mesma <code>Idempotency-Key</code>; a API devolve a mesma resposta, sem gravar duas vezes.</li>
</ul>
