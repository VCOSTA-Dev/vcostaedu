<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Cadastrar muitos alunos de uma vez a partir de uma planilha — e, se a planilha trouxer o curso, já vinculá-los a ele. Nada é gravado no envio: o sistema confere cada linha, diz o que está pronto e o que está errado (com o motivo), deixa corrigir na tela e só grava quando você aplica.</p>
<h2>Antes de começar</h2>
<ul>
<li>Salve a planilha como <strong>CSV</strong> (no Excel: <em>Arquivo → Salvar como → CSV</em>). Planilhas <code>.xlsx</code> não são lidas. O CSV do Excel em português (com acentos) é reconhecido.</li>
<li>Uma linha de cabeçalho e um aluno por linha. <strong>Obrigatórias:</strong> RA e Nome. <strong>Opcionais:</strong> CPF, e-mail, nascimento (dd/mm/aaaa), sexo, telefone e situação (Ativo/Inativo); e, para já vincular, curso, grade, ano, polo e modalidade. Use <strong>Baixar o modelo</strong> para ver as colunas.</li>
<li>Para vincular, o curso, a grade, o ano letivo e o polo precisam existir. Sem polo e sem modalidade, valem os do curso, como na tela.</li>
<li><strong>Na escola</strong>, há também a coluna <strong>Turma</strong>: a série e o nome da turma (&quot;6º ano A&quot;), ou só o nome, se ele for único no segmento e no ano. Com a turma, o aluno é vinculado ao segmento na série da turma e entra em todas as disciplinas dela. A turma precisa existir (Ensino → Turmas).</li>
<li>Quem importa: o Administrador, a Supervisão de secretaria e a Secretaria. Quem desfaz: o Administrador e a Supervisão de secretaria.</li>
</ul>
<h2>Passo a passo</h2>
<ol>
<li>Abra <strong>Educacional → Importar alunos</strong>, escolha o arquivo e clique em <strong>Enviar e conferir</strong>.</li>
<li><strong>As colunas:</strong> confira em que campo cada coluna do arquivo entra. Os nomes podem variar (&quot;Nome completo&quot;, &quot;Data de nascimento&quot;, &quot;R.A.&quot;); o que o sistema não reconhecer fica como &quot;(não importar)&quot; — escolha o campo e clique em <strong>Salvar as colunas e conferir de novo</strong>. O próximo arquivo com os mesmos cabeçalhos já vem com esse ajuste.</li>
<li><strong>Os valores:</strong> veja cada valor diferente de sexo, situação e modalidade e o que ele virou. Valor não reconhecido não é adivinhado: a linha fica com erro.</li>
<li><strong>As linhas:</strong> cada uma aparece <strong>Pronta</strong>, <strong>Com aviso</strong>, <strong>Com erro</strong> (com o motivo) ou <strong>Pulada</strong> (o aluno já está cadastrado com aquele RA). Clique em <strong>Corrigir</strong> para acertar uma linha na própria tela, sem reenviar o arquivo.</li>
<li><strong>Aplicar:</strong> se houver linhas com curso, escolha a forma e a data de ingresso. Se ainda houver linhas com erro, marque <strong>Aplicar sem as linhas com erro</strong> (elas ficam de fora). Clique em <strong>Aplicar a importação</strong>: a janela mostra o andamento, linha a linha (um arquivo de 5.000 linhas leva alguns minutos). Vale o tudo ou nada: se uma linha falhar, as já gravadas são desfeitas, nada fica gravado e a tela diz qual linha falhou e por quê. Se a página fechar no meio, nada se perde: ao voltar ao lote, clique em <strong>Continuar</strong>.</li>
<li>Os alunos importados recebem o primeiro acesso como os cadastrados na tela: pelo <strong>Enviar link de acesso</strong>.</li>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;RA repetido no arquivo&quot;</strong> ou <strong>&quot;CPF repetido no arquivo&quot;</strong> — duas linhas com o mesmo RA (ou CPF): todas elas ficam com erro, porque não dá para saber qual é a certa. Corrija uma delas.</li>
<li><strong>&quot;CPF já cadastrado para outro aluno&quot;</strong>, <strong>&quot;E-mail já cadastrado&quot;</strong> — o dado pertence a outro aluno do sistema.</li>
<li><strong>&quot;Já cadastrado: RA …&quot;</strong> — o aluno já existe; a linha é pulada e o cadastro dele não muda. Por isso, enviar o mesmo arquivo de novo não duplica ninguém.</li>
<li><strong>&quot;Data de nascimento impossível&quot;</strong>, <strong>&quot;Curso não encontrado&quot;</strong>, <strong>&quot;Ano letivo não cadastrado&quot;</strong>, <strong>&quot;Sexo não reconhecido&quot;</strong> — corrija a linha na tela ou o arquivo.</li>
<li><strong>&quot;Sem o sexo&quot;</strong> (aviso) — o aluno entra mesmo assim, na lista <strong>Alunos sem sexo informado</strong> (em Alunos): os documentos concordam com o sexo (ALUNA/ALUNO), então complete-o depois.</li>
<li><strong>Desfazer recusado</strong> — desfazer tira os alunos do lote só enquanto nenhum deles tiver nota, falta, chamada, documento emitido, senha criada no portal ou outro vínculo. A tela diz qual aluno impede e por quê.</li>
</ul>
