<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Proteger as senhas contra quem tenta adivinhar. Depois de algumas falhas seguidas, a tela pede uma <strong>espera</strong> antes da próxima tentativa.</p>
<h2>Antes de começar</h2>
<p>Há dois limites. O que chegar primeiro vale:</p>
<table>
<thead><tr><th>Onde</th><th>Por usuário (fixo)</th><th>Por endereço (IP), ajustável</th></tr></thead>
<tbody>
<tr><td>Login do painel e do portal</td><td>5 falhas em 24 horas</td><td>padrão 50 falhas por hora (de 10 a 1000)</td></tr>
<tr><td>Esqueci a senha (painel e portal)</td><td>3 pedidos em 1 hora</td><td>padrão 20 pedidos por hora (de 5 a 500)</td></tr>
<tr><td>Verificação pública de documento</td><td>—</td><td>padrão 60 consultas a cada 10 minutos (de 10 a 1000)</td></tr>
</tbody>
</table>
<p>A espera começa em <strong>30 segundos</strong> e <strong>dobra</strong> a cada nova falha, até <strong>15 minutos</strong>. Uma entrada certa zera a contagem daquele usuário.</p>
<p>Um mesmo IP pode ser de muita gente: um laboratório ou a rede da escola saem todos pelo mesmo endereço. Por isso o limite por IP é mais folgado e pode ser ajustado.</p>
<h2>Passo a passo</h2>
<ol>
<li><strong>Ajustar os limites por IP:</strong> em <strong>Configurações → Instituição</strong>, no quadro <strong>Limite de tentativas por IP</strong>, mude os três números e clique em <strong>Salvar</strong>. Fora da faixa, nada é salvo, e a tela diz a faixa certa (<a class="guia-interno" data-guia="regras-de-nota-e-frequencia" href="{{GUIA}}regras-de-nota-e-frequencia">Regras de nota e frequência</a>).</li>
<li><strong>Ver quem está em espera:</strong> em <strong>Configurações → Tentativas de acesso</strong>. As linhas em amarelo estão em espera agora.</li>
</ol>
<figure><img src="{{IMG}}administrador_22_tentativas.png" alt="Tela Tentativas de acesso com uma linha em espera"><figcaption>Tela Tentativas de acesso com uma linha em espera</figcaption></figure>
<ol>
<li><strong>Liberar alguém antes do fim da espera:</strong> clique em <strong>Liberar</strong> na linha, ou digite o usuário (ou o IP) no campo do alto e clique em <strong>Liberar</strong>.</li>
</ol>
<blockquote>A tabela guarda só uma <strong>dica</strong> do usuário e do IP (parte do texto), nunca a senha. As tentativas bloqueadas ficam no registro de acessos, também sem senha e sem código de link.</blockquote>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;O limite por IP &quot;Login: falhas por hora&quot; deve ser um número inteiro de 10 a 1000.&quot;</strong> — e as mensagens parecidas dos outros dois limites: use um número dentro da faixa.</li>
<li>Digitou o usuário e nada mudou — use o mesmo texto que a pessoa digitou na entrada (usuário, e-mail, RA ou CPF), ou libere pela linha.</li>
<li>Muitas linhas do mesmo IP — pode ser uma rede compartilhada (o laboratório) ou alguém tentando adivinhar senhas. Se for a rede da escola, aumente o limite por IP. Na dúvida, não libere o IP.</li>
<li>Todos os alunos aparecem com o <strong>mesmo IP</strong> — o servidor está atrás de um proxy. Quem instala precisa informar esse proxy na configuração do servidor (<code>PROXIES_CONFIAVEIS</code>).</li>
</ul>
