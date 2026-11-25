<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Responder perguntas de decisão sem montar planilha: &quot;como está a instituição agora?&quot;, &quot;quem vai reprovar por falta?&quot;, &quot;quem já pode receber o histórico final?&quot;. Cada relatório existe para uma pergunta, escrita no topo da tela, logo abaixo do nome do relatório (e também na lista dos relatórios e em <strong>Como é calculado</strong>).</p>
<h2>Antes de começar</h2>
<ul>
<li>Cada perfil vê só os relatórios a que tem acesso (Configurações → níveis de acesso). O professor vê os das ofertas dele.</li>
<li>Os números saem das mesmas contas das telas de notas, do boletim e do histórico: nada é recalculado à parte. Oferta <strong>aberta</strong> = valores <strong>provisórios</strong> (o encerramento é que grava).</li>
</ul>
<h2>Passo a passo</h2>
<ol>
<li>Abra <strong>Relatórios → Todos os relatórios</strong>. Eles estão por categoria: A. Painel gerencial, B. Secretaria e cadastro, C. Desempenho acadêmico, D. Frequência, E. Evasão, risco e conclusão, F. TCC, G. Documentos, H. Auditoria.</li>
<li>Escolha os <strong>filtros</strong> e clique em <strong>Filtrar</strong>. Os filtros ficam no endereço da página: copie o link e quem abrir verá o mesmo relatório (com os dados que o perfil dele permite).</li>
<li><strong>Como é calculado</strong>: o botão mostra a fórmula de cada número, o que entra e o que fica de fora.</li>
<li><strong>Números clicáveis</strong>: clique num número (ou em &quot;Ver a lista&quot;) para ver quem compõe aquele número. A lista sempre tem o mesmo tamanho do número.</li>
<li><strong>Colunas</strong>: o botão Colunas esconde ou mostra colunas. A exportação leva só as visíveis.</li>
<li><strong>Exportar</strong>: Excel, CSV, PDF ou Imprimir. O PDF, a impressão e o Excel trazem no topo a data e hora de geração e os filtros. Toda exportação fica registrada (quem, quando, relatório, filtros, linhas).</li>
<li><strong>Salvar</strong>: guarda os filtros e as colunas com um nome. Escolha um perfil para compartilhar: quem é do perfil abre, mas não altera nem apaga. Os salvos aparecem em Todos os relatórios e no botão Salvos de cada relatório.</li>
<li><strong>Alerta de risco</strong> (administrador): os limites dos sinais do relatório Alunos em risco.</li>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li>O CPF aparece como 123.xxx.xxx-45: o seu perfil não tem a permissão &quot;ver CPF completo em relatórios&quot; (por padrão, só administrador e secretaria). Vale na tela e nos arquivos.</li>
<li>&quot;O filtro recebeu um valor que não está disponível para o seu perfil&quot;: o link veio de outro perfil (ex.: uma oferta que não é sua). O filtro foi ignorado.</li>
<li>&quot;A exportação não pôde ser registrada&quot;: a sessão expirou. Entre de novo; sem o registro o arquivo não é gerado.</li>
<li>Um relatório não aparece: o seu perfil não tem acesso a ele.</li>
</ul>
