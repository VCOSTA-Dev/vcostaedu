<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Na escola, o <strong>segmento</strong> (o Ensino Fundamental, o Ensino Médio) tem uma matriz; cada <strong>série</strong> da matriz tem as suas <strong>disciplinas</strong>. No ano letivo, cada série tem uma ou mais <strong>turmas</strong>, e cada turma tem uma disciplina por linha, com o seu professor. O aluno entra na turma e fica em todas as disciplinas dela, de uma vez. Vale qualquer segmento e qualquer série, do 1º ano do Ensino Fundamental em diante (os anos iniciais, os finais, o Médio): os da demonstração são só um exemplo. A avaliação é por nota, de 0 a 10, em todas as séries.</p>
<h2>Antes de começar</h2>
<ul>
<li>O segmento, a matriz e as séries, com as disciplinas e a carga horária de cada uma (Ensino → Segmentos e a matriz de cada segmento). <strong>Ensino → Estrutura da escola</strong> mostra tudo junto.</li>
<li>O ano letivo cadastrado (Ensino → Anos letivos).</li>
<li>Os alunos cadastrados e vinculados ao segmento (Alunos), ou trazidos por planilha com a coluna da turma (Educacional → Importar alunos).</li>
</ul>
<h2>Passo a passo</h2>
<ol>
<li><strong>O ano letivo e as etapas:</strong> em <strong>Ensino → Ano letivo e etapas</strong>, escolha o modelo — quatro bimestres (o padrão) ou três trimestres — e clique em <strong>Abrir o ano</strong>. As etapas nascem com datas sugeridas; ajuste o início, o fim e a data do conselho de classe de cada uma e clique em <strong>Salvar as etapas</strong>. Depois que houver nota ou falta lançada no ano, o modelo não muda (as datas, sim).</li>
<li><strong>A turma:</strong> em <strong>Ensino → Turmas</strong>, clique em <strong>Nova turma</strong>, escolha a série, escreva o nome (&quot;A&quot;, &quot;B&quot;) e, se quiser, o turno, a sala e a capacidade. Ao criar, o sistema gera as ofertas da série: uma por disciplina. A turma aparece como a série e o nome — &quot;6º ano A&quot;.</li>
<li><strong>Os professores:</strong> na tela da turma, clique em cada disciplina e indique o professor. O professor vê só as turmas e as disciplinas dele.</li>
<li><strong>Os alunos na turma:</strong> na tela da turma, o cartão <strong>Enturmar alunos</strong> lista os alunos matriculados na série e no ano da turma e ainda sem turma, cada um com a série e o ano. Marque os alunos (ou <strong>Marcar todos</strong>) e clique em <strong>Enturmar os marcados</strong>. A janela mostra o andamento, aluno a aluno; se a página fechar no meio, a turma oferece <strong>Continuar</strong>. A turma também pode ser escolhida no mesmo passo da matrícula (abaixo).</li>
<li><strong>O aluno novo:</strong> em Educacional → Alunos → Cadastrar, o número do aluno já vem preenchido com o próximo da sequência (pode trocar; se outra pessoa salvar o mesmo número antes, a tela avisa e traz o seguinte). Na ficha, aba <strong>Matrículas → Matricular num segmento</strong>: o ano, o segmento e a série, a data da matrícula, a <strong>turma</strong> (opcional: só as da série e do ano) e o <strong>plano de pagamento</strong>, que pergunta <strong>a partir de qual parcela cobrar</strong> — marcada vem a primeira que ainda não venceu; as anteriores não são cobradas, a menos que você escolha. A lista dos alunos mostra a série e a turma de cada um.</li>
<li><strong>O aluno que entra no meio do ano:</strong> as etapas que terminaram antes da data da matrícula ficam sem nota e fora da média anual. Se o aluno trouxe as notas da escola de origem, abra <strong>Notas de transferência</strong> (na aba Matrículas da ficha): para cada disciplina e etapa, a nota e as faltas, com o nome da escola e o documento apresentado. Elas contam na média, aparecem com &quot;T&quot; no boletim e valem mesmo na etapa já fechada; só a secretaria lança, e cada mudança fica na auditoria.</li>
<li><strong>O atestado de matrícula:</strong> na escola, sai com o segmento, a série e o ano letivo, sem a previsão de conclusão e sem o CPF.</li>
<li><strong>Trocar de turma:</strong> na lista de alunos da turma, escolha a turma de destino (da mesma série) e clique em <strong>Trocar de turma</strong>. As notas e as faltas já lançadas vão junto.</li>
<li><strong>Sair da turma:</strong> <strong>Sair da turma</strong> tira o aluno de todas as disciplinas; o que já tinha sido lançado fica guardado.</li>
<li><strong>As regras da escola:</strong> em <strong>Configurações → Regras da escola</strong>, a frequência mínima (75%, o padrão) e onde ela é medida — no total de horas do ano letivo (o padrão) ou em cada disciplina.</li>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Abra antes o ano letivo com as etapas&quot;:</strong> a turma é criada num ano letivo já aberto na tela Ano letivo e etapas.</li>
<li><strong>O aluno não aparece para enturmar:</strong> ele precisa de matrícula (vínculo) Cursando no segmento e na matriz da turma, e de não estar em outra turma no mesmo ano.</li>
<li><strong>&quot;A turma está completa&quot;:</strong> a capacidade da turma foi atingida; aumente-a em Editar ou use outra turma.</li>
<li><strong>A troca é recusada:</strong> a troca é entre turmas da mesma série, no mesmo ano.</li>
</ul>
