<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Ver <strong>quem está faltando</strong> e agir <strong>antes</strong> de o aluno reprovar por frequência.</p>
<h2>Antes de começar</h2>
<ul>
<li>Frequência = 1 − (faltas − compensadas) ÷ encontros, a mesma conta do boletim. Sem o total de encontros, ela não é calculada.</li>
<li>A frequência mínima é a do curso; sem ela, a da instituição.</li>
</ul>
<h2>Passo a passo</h2>
<ol>
<li><strong>Frequência da oferta</strong>: com chamada, uma coluna por encontro (P ou F); com lançamento, as faltas lançadas. Mais compensadas, frequência e FS/FI.</li>
<li><strong>Abaixo ou perto do mínimo</strong>: em todas as ofertas abertas, os alunos com as faltas que cada um ainda pode ter e a classificação.</li>
</ol>
<p>- O relatório <strong>abre em &quot;Só os alertas&quot;</strong> (abaixo, no limite e perto). Para ver também os <strong>&quot;Sem alerta&quot;</strong>, escolha <strong>Mostrar todos</strong> no filtro <strong>Mostrar</strong> e clique em Filtrar. O filtro fica no link: quem abrir o link copiado vê a mesma lista. - Os <strong>cartões</strong> mostram sempre as quatro contagens (Abaixo, No limite, Perto e Sem alerta), qualquer que seja o filtro; clique em um para ver a lista daquele grupo. - A <strong>exportação</strong> (Excel, CSV, PDF, Imprimir) leva só o que a tabela mostra no filtro ativo, e o filtro fica no cabeçalho do arquivo e no registro da exportação. - <strong>Faltas que ainda pode ter</strong> = as faltas que o aluno pode ter sem cair abaixo da mínima, menos as que já tem (as compensadas descontam). - Na oferta por <strong>chamada com encontros previstos</strong> (Editar a disciplina → Encontros previstos), a conta é sobre os <strong>previstos</strong>: piso(previstos × (100 − mínimo) ÷ 100) − faltas não compensadas. Ex.: 10 previstos, mínimo 75%, 1 falta → piso(10 × 0,25) = 2, menos 1 = <strong>pode ter mais 1 falta</strong>. A coluna Encontros mostra &quot;10 previstos (2 registrados)&quot;. - Sem previstos, a conta é sobre os encontros já registrados (como antes). - <strong>Abaixo</strong>: já passou do mínimo. <strong>No limite</strong>: tem falta e não pode faltar mais. <strong>Perto</strong>: tem ao menos 1 falta não compensada e pode faltar até a margem (padrão do alerta de risco; mude no filtro Margem). <strong>Sem alerta</strong>: nenhuma falta efetiva, ou ainda pode faltar mais que a margem. É a mesma classificação do alerta de risco.</p>
<ol>
<li>Ordene pela coluna &quot;Faltas que ainda pode ter&quot; para ver primeiro quem está pior.</li>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li>A oferta não aparece em &quot;Abaixo ou perto&quot;: falta o total de encontros (o aviso no topo conta quantas ficaram de fora).</li>
<li>Na chamada <strong>sem</strong> encontros previstos, o total é o de encontros já registrados: no começo da oferta a margem é pequena para todos. Informe os previstos na edição da disciplina.</li>
<li>A frequência do boletim e dos documentos é sempre sobre os encontros <strong>registrados</strong>: os previstos só servem a este relatório e ao alerta de risco.</li>
</ul>
