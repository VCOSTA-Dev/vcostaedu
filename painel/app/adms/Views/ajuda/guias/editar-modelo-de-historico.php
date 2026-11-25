<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Deixar o histórico com a cara da instituição. O modelo é <strong>um documento só</strong>, escrito como num editor de texto. A secretaria só escolhe o modelo na hora de emitir.</p>
<h2>Antes de começar</h2>
<p>O sistema vem com dois modelos neutros: <strong>Histórico parcial — padrão</strong> e <strong>Histórico final — padrão</strong>. O tipo <strong>Atestado de matrícula</strong> nasce de um modelo neutro ao escolher <strong>Novo modelo</strong> com esse tipo. Cadastre antes os <a class="guia-interno" data-guia="dados-da-instituicao-e-atos-legais" href="{{GUIA}}dados-da-instituicao-e-atos-legais">dados da instituição e os atos legais</a>.</p>
<h2>Passo a passo</h2>
<ol>
<li><strong>Configurações → Modelos de histórico</strong>, <strong>Editar</strong> (ou <strong>Novo modelo</strong>).</li>
</ol>
<figure><img src="{{IMG}}administrador_18_modelos.png" alt="Lista de modelos, com tipo, cursos, versão e situação"><figcaption>Lista de modelos, com tipo, cursos, versão e situação</figcaption></figure>
<ol>
<li>Nome, <strong>cursos a que se aplica</strong> (nenhum = padrão da instituição) e <strong>situação</strong> (só <strong>Ativo</strong> aparece para emitir).</li>
<li><strong>Documento</strong>: escreva e formate como num editor de texto (negrito, listas, alinhamento, tabela, títulos). Colar sempre entra como texto simples.</li>
<li><strong>Variáveis</strong> (aba à direita): clique no ponto do texto e depois na variável (ex.: <em>Nome civil</em>). Ela entra ali, como <code>{{ALUNO_NOME}}</code>, e na emissão vira o dado do aluno. Use a <strong>busca</strong> para achar pelo nome. Da instituição há também cidade, site, telefone, e-mail e cada linha do cabeçalho; do curso, nível, nome por extenso, linha do cabeçalho e sigla. Do aluno, a concordância (ALUNA/ALUNO, matriculada/matriculado, a/o) e o CPF mascarado (123.xxx.xxx-45); da matrícula, a situação no documento (EM CURSO) e o ingresso e a previsão de conclusão como &quot;novembro de 2025&quot;; para a legenda, FS/FI, os rótulos, a frequência mínima (75%) e a nota mínima (7,0).</li>
<li><strong>Blocos do sistema</strong> (no alto da aba Variáveis): <strong>Quadro de disciplinas</strong>, <strong>Assinaturas</strong>, <strong>Logo da instituição</strong>, <strong>Cabeçalho institucional</strong> (as linhas de <em>Dados da instituição</em> e, por último, a linha do curso), <strong>QR code da verificação</strong> e <strong>Código de verificação</strong>. No documento aparecem como um marcador em destaque (ex.: <strong>[Quadro de disciplinas]</strong>), que o sistema monta na emissão. Para tirar, apague o marcador inteiro; para mudar de lugar, recorte e cole.</li>
<li><strong>Só se houver TCC</strong>: o que ficar entre <strong>[Início: só se houver TCC]</strong> e <strong>[Fim: só se houver TCC]</strong> (grupo TCC) só sai para quem tem TCC.</li>
</ol>
<p><strong>Trecho que só sai se houver o dado</strong> (no alto da aba Variáveis): escolha a variável e clique em <strong>Início</strong> e em <strong>Fim</strong> nos pontos do texto. O que ficar entre os dois só sai quando o dado está preenchido, e o dado vazio <strong>não vira alerta</strong>. Ex.: &quot;Telefone: …&quot; só com o telefone cadastrado; o e-MEC, o e-mail, o site e a linha 5 do cabeçalho. Para o atestado, as variáveis <strong>Emitido pelo aluno no portal</strong> e <strong>Emitido pela secretaria</strong> separam o texto de cada origem.</p>
<ol>
<li>Aba <strong>Quadro</strong>: marque as colunas, o rótulo, a largura (%) e o alinhamento; as setas mudam a ordem. No PARCIAL, escolha também a linha das atividades e o texto de disciplina aberta (&quot;Em curso&quot;). Para o desenho do modelo Word: <strong>Carga horária dentro do nome</strong> (&quot;Nome (<strong>18</strong> horas aula)&quot;, com o texto &quot;horas aula&quot; editável), <strong>Uma linha por disciplina</strong> (só a tentativa vigente, sem atividades), <strong>Disciplina em andamento em branco</strong> e <strong>Linhas finais</strong> (média final do aluno, TCC e situação, com fundo cinza; a média só sai no FINAL). Em <strong>Frequência</strong>, escolha como ela sai: <strong>Percentual</strong> (o padrão), <strong>Sigla</strong> (FS/FI, só com a disciplina encerrada, pelo mínimo gravado no encerramento) ou <strong>Os dois</strong>.</li>
<li>Aba <strong>Assinaturas</strong>: nome, cargo e, se quiser, a imagem (PNG ou JPEG até 1 MB).</li>
</ol>
<p>Aba <strong>Opções</strong>: <strong>Estilo do documento</strong> (&quot;Documento oficial&quot;: margens, cabeçalho sem filete e quadro de bordas pretas, como no modelo Word), <strong>Fonte</strong> (Liberation Sans, com a métrica da Arial) e <strong>Casas decimais das notas no documento</strong> (1 ou 2; só a forma de escrever — o cálculo continua com as casas de Configurações → Instituição). Um modelo que não usa essas opções sai como antes.</p>
<ol>
<li>Aba <strong>Prévia</strong>: escolha um aluno e clique em <strong>Gerar a prévia em PDF</strong> (nada é salvo). O PDF aparece ali mesmo, ao lado do documento. Depois, <strong>Salvar modelo</strong>.</li>
</ol>
<figure><img src="{{IMG}}administrador_19_modelo_editor.png" alt="Editor com o documento, os marcadores dos blocos e a aba de variáveis"><figcaption>Editor com o documento, os marcadores dos blocos e a aba de variáveis</figcaption></figure>
<blockquote>Salvar um modelo <strong>ativo</strong> cria uma <strong>nova versão</strong>; as emissões antigas continuam com a versão delas. A coluna &quot;Módulo&quot; do quadro só aparece com <em>Organizar a matriz em módulos</em> em <strong>Sim</strong>.</blockquote>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Nada foi salvo: Documento: conteúdo não permitido — …&quot;</strong> — o documento não aceita script, imagem, quadro de outro site (<code>iframe</code>), endereço externo nem código. Tire esse conteúdo e salve de novo.</li>
<li><strong>&quot;Nada foi salvo: Documento: variável desconhecida — {{…}}.&quot;</strong> — a variável foi digitada à mão ou está errada; use o painel.</li>
<li><strong>&quot;Formatação que o documento não usa foi retirada: …&quot;</strong> — o modelo foi salvo; só a formatação que o PDF não usa (fonte, cor) saiu.</li>
<li><strong>&quot;… só se houver TCC precisa de um início e de um fim&quot;</strong> — ponha os dois marcadores, o início antes do fim.</li>
<li><strong>Variável sem valor no documento</strong> na hora de emitir: o dado está vazio no cadastro do aluno. É um <strong>alerta</strong>: complete o cadastro (no FINAL, a emissão só sai sem alertas).</li>
<li>Assinatura: <strong>&quot;a imagem deve ser PNG ou JPEG.&quot;</strong> e <strong>&quot;a imagem deve ter até 1 MB.&quot;</strong></li>
</ul>
