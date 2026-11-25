<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Ver o ano letivo inteiro de uma vez: o que acontece, em que ordem, quem faz e onde está o guia de cada passo.</p>
<h2>Antes de começar</h2>
<p>O administrador já cadastrou o polo, o curso, a equipe, os dados da instituição e as regras de nota e frequência (veja <a class="guia-interno" data-guia="polos-e-cursos" href="{{GUIA}}polos-e-cursos">Polos e cursos</a> e <a class="guia-interno" data-guia="regras-de-nota-e-frequencia" href="{{GUIA}}regras-de-nota-e-frequencia">Regras de nota e frequência</a>).</p>
<h2>Passo a passo</h2>
<table>
<thead><tr><th>#</th><th>Passo</th><th>Quem</th><th>Guia</th></tr></thead>
<tbody>
<tr><td>1</td><td>Cadastrar as matérias (catálogo, com o código)</td><td>secretaria</td><td>Cadastrar matérias</td></tr>
<tr><td>2</td><td>Montar a matriz: a grade e as disciplinas</td><td>secretaria</td><td>Montar a matriz</td></tr>
<tr><td>3</td><td>Finalizar a grade</td><td>administrador</td><td>Situação da grade</td></tr>
<tr><td>4</td><td>Gerar a oferta do ano e completar cada oferta</td><td>secretaria</td><td>Gerar a oferta do ano · Completar a oferta</td></tr>
<tr><td>5</td><td>Cadastrar os alunos e enviar o link de acesso</td><td>secretaria</td><td>Cadastrar aluno e enviar o link</td></tr>
<tr><td>6</td><td>Vincular ao curso e enturmar</td><td>secretaria</td><td>Vincular e enturmar</td></tr>
<tr><td>7</td><td>Atividades, notas e faltas; faltas compensadas (atestado)</td><td>professor (faltas também a secretaria); compensação: secretaria</td><td>Lançar notas · Lançar faltas · Compensar faltas</td></tr>
<tr><td>8</td><td>Encerrar cada disciplina</td><td>secretaria</td><td>Encerrar uma disciplina</td></tr>
<tr><td>9</td><td>Boletim</td><td>secretaria</td><td>Ver e imprimir o boletim</td></tr>
<tr><td>10</td><td>Conclusão, TCC e histórico</td><td>secretaria</td><td>Concluir o curso · Emitir o histórico parcial / final</td></tr>
</tbody>
</table>
<ol>
<li>Siga a ordem: cada passo depende do anterior (sem matriz não há oferta; sem oferta não há enturmação; sem enturmação não há nota).</li>
<li>Abra o guia de cada passo pelo <strong>Comece por aqui</strong> da Central de ajuda: <a class="guia-interno" data-guia="cadastrar-materias" href="{{GUIA}}cadastrar-materias">Cadastrar matérias</a>, <a class="guia-interno" data-guia="montar-a-matriz" href="{{GUIA}}montar-a-matriz">Montar a matriz</a>, <a class="guia-interno" data-guia="gerar-a-oferta-do-ano" href="{{GUIA}}gerar-a-oferta-do-ano">Gerar a oferta do ano</a>, <a class="guia-interno" data-guia="cadastrar-aluno-e-enviar-link" href="{{GUIA}}cadastrar-aluno-e-enviar-link">Cadastrar aluno e enviar o link</a>, <a class="guia-interno" data-guia="vincular-e-enturmar" href="{{GUIA}}vincular-e-enturmar">Vincular e enturmar</a>, <a class="guia-interno" data-guia="encerrar-uma-disciplina" href="{{GUIA}}encerrar-uma-disciplina">Encerrar uma disciplina</a>, <a class="guia-interno" data-guia="ver-e-imprimir-o-boletim" href="{{GUIA}}ver-e-imprimir-o-boletim">Ver e imprimir o boletim</a>, <a class="guia-interno" data-guia="emitir-historico-final" href="{{GUIA}}emitir-historico-final">Emitir o histórico final</a>.</li>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li>Pular um passo: a tela seguinte recusa com o motivo (por exemplo, &quot;A oferta não tem aluno enturmado.&quot; no encerramento). Volte ao passo que faltou.</li>
<li>Uma regra que parece errada (média mínima, frequência, casas decimais) é do administrador: <a class="guia-interno" data-guia="regras-de-nota-e-frequencia" href="{{GUIA}}regras-de-nota-e-frequencia">Regras de nota e frequência</a>.</li>
</ul>
