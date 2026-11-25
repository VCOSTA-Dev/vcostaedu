<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Definir as regras da instituição para todo o sistema.</p>
<h2>Antes de começar</h2>
<p>As regras valem para as disciplinas abertas e para os próximos encerramentos. Uma disciplina já encerrada guarda o resultado com as regras do dia.</p>
<h2>Passo a passo</h2>
<ol>
<li><strong>Configurações → Instituição</strong>.</li>
</ol>
<figure><img src="{{IMG}}administrador_07_instituicao.png" alt="Configurações da instituição"><figcaption>Configurações da instituição</figcaption></figure>
<ol>
<li>Ajuste e salve:</li>
<ul>
<li><strong>O aluno entra no portal com</strong>: RA (padrão), CPF ou e-mail;</li>
<li><strong>Validade do link</strong> de senha: 1 a 720 horas (padrão 48);</li>
<li><strong>Casas decimais</strong> e <strong>média mínima</strong>: desde a 1.13.0 ficam na <a class="guia-interno" data-guia="configurar-a-regra-de-media" href="{{GUIA}}configurar-a-regra-de-media">regra de média</a> (a tela mostra os valores e o link);</li>
<li><strong>Frequência mínima</strong> (padrão 75%). Um curso pode ter a sua própria frequência mínima (no cadastro do curso); vazio = esta. A disciplina encerrada guarda o mínimo que valia no encerramento;</li>
<li><strong>Mostrar média parcial ao aluno</strong> (padrão: Sim);</li>
<li><strong>Média final do aluno (histórico FINAL)</strong>: <strong>aritmética simples</strong> das disciplinas (padrão) ou <strong>ponderada pela carga horária</strong>. Entram as disciplinas encerradas (a tentativa vigente); o TCC fica fora; no PARCIAL, a linha sai em branco. A frase &quot;Como ler&quot; do boletim mostra a frequência mínima do curso do aluno;</li>
<li><strong>Organizar a matriz em módulos</strong> (padrão: <strong>Não</strong>). Com Não, o curso é grade e lista de disciplinas, e módulo não aparece em nenhuma tela nem documento. Com Sim, as disciplinas ficam agrupadas em módulos, e aparecem a tela Módulos, o agrupamento no boletim e no histórico e a coluna &quot;Módulo&quot; no modelo do histórico. Pode trocar nos dois sentidos: ao passar para Sim, as grades que já existem mostram um módulo <strong>Geral</strong> com todas as disciplinas, que você renomeia ou reorganiza; nada se perde nem se duplica;</li>
<li><strong>Textos dos documentos</strong>: as siglas e os rótulos da frequência suficiente e insuficiente (FS/FI), as formas neutras da concordância (para o sexo &quot;Outros&quot;: ESTUDANTE, com matrícula regular, o(a)) e o <strong>rótulo de cada situação da matrícula</strong> no documento (Cursando → EM CURSO);</li>
<li><strong>Limite de tentativas por IP</strong>: falhas de login por hora (padrão 50), pedidos de Esqueci a senha por hora (padrão 20) e consultas da verificação a cada 10 minutos (padrão 60). Veja <a class="guia-interno" data-guia="tentativas-de-acesso" href="{{GUIA}}tentativas-de-acesso">Tentativas de acesso e limites</a>.</li>
</ul>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;A validade do link deve ser um número de horas entre 1 e 720.&quot;</strong></li>
<li><strong>&quot;O rótulo da situação … no documento deve ter de 1 a 60 caracteres.&quot;</strong> — nenhum texto dos documentos fica vazio.</li>
<li><strong>&quot;Escolha Sim ou Não em «Organizar a matriz em módulos».&quot;</strong> — escolha uma das duas opções.</li>
<li><strong>&quot;O limite por IP … deve ser um número inteiro de … a ….&quot;</strong> — cada limite tem a sua faixa, escrita embaixo do campo.</li>
<li><strong>&quot;A frequência mínima deve ser uma porcentagem de 0 a 100, com até 2 casas.&quot;</strong></li>
<li><strong>&quot;A troca do login do portal para … foi RECUSADA.&quot;</strong> — a tela lista os alunos ativos sem o campo escolhido (ou com valor repetido); corrija cada um e salve de novo.</li>
</ul>
