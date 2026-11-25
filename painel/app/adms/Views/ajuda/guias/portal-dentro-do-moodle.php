<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>O aluno vê o boletim, as notas e o horário <strong>sem sair do Moodle</strong>: o portal aparece dentro de uma página do curso.</p>
<h2>Antes de começar</h2>
<ul>
<li>O portal e o Moodle precisam estar em <strong>HTTPS</strong>. Sem HTTPS, o navegador não guarda o acesso de um site aberto dentro de outro.</li>
<li><strong>Recomendado:</strong> o portal num <strong>subdomínio do mesmo domínio do Moodle</strong> (por exemplo, Moodle em <code>moodle.escola.exemplo.invalid</code> e portal em <code>portal.escola.exemplo.invalid</code>). Assim o navegador trata os dois como o mesmo site e não bloqueia o acesso.</li>
<li>Quem instala o sistema precisa editar um arquivo no servidor. Se não for você, repasse este guia a essa pessoa.</li>
</ul>
<h2>Passo a passo</h2>
<ol>
<li><strong>No servidor do portal</strong>, no arquivo <code>portal/core/Config.php</code>, informe o endereço do Moodle (só o começo, sem caminho) na linha:</li>
</ol>
<p><code>define(&#039;ORIGENS_PERMITIDAS_IFRAME&#039;, &#039;https://moodle.escola.exemplo.invalid&#039;);</code></p>
<p>Mais de um endereço: separe por espaço. Só são aceitos endereços <code>https://</code>.</p>
<p><code>COOKIE_PARTICIONADO</code>: deixe <code>true</code> se o portal está num domínio diferente do Moodle. Assim, o acesso dentro do Moodle continua mesmo nos navegadores que bloqueiam &quot;cookies de terceiros&quot;, mas o aluno entra de novo se abrir o portal numa aba própria. Com o portal num <strong>subdomínio do mesmo domínio do Moodle</strong> (o recomendado), pode ser <code>false</code>: o navegador trata os dois como o mesmo site e o acesso é um só.</p>
<ol>
<li><strong>No Moodle</strong>, dentro do curso, com a edição ativada, escolha uma das formas:</li>
</ol>
<p>- <strong>Recurso &quot;URL&quot;</strong>: cole o endereço do portal (<code>https://portal.escola.exemplo.invalid/portal/</code>) e, em <strong>Aparência → Exibir</strong>, escolha <strong>Embutir</strong>. - <strong>Bloco &quot;HTML&quot;</strong> (ou um rótulo/página): no editor, use o modo de código e cole:</p>
<p><code>&lt;iframe src=&quot;https://portal.escola.exemplo.invalid/portal/&quot; style=&quot;width:100%;height:800px;border:0&quot;&gt;&lt;/iframe&gt;</code></p>
<ol>
<li>Abra o curso como um aluno de teste, entre no portal dentro do Moodle e abra o <strong>Boletim</strong>. Se ele aparece sem pedir o login de novo, está pronto.</li>
</ol>
<figure><img src="{{IMG}}administrador_20_portal_no_moodle.png" alt="Página de curso com o portal embutido, mostrando o boletim"><figcaption>Página de curso com o portal embutido, mostrando o boletim</figcaption></figure>
<blockquote>Só o <strong>portal do aluno</strong> pode aparecer dentro de outro site. O <strong>painel da equipe</strong> e a <strong>página de verificação</strong> de documentos nunca aparecem num iframe, por segurança.</blockquote>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>O quadro fica em branco ou mostra &quot;recusou a conexão&quot;</strong> — o endereço do Moodle não está em <code>ORIGENS_PERMITIDAS_IFRAME</code> (confira <code>https://</code>, o nome exato e a porta, se houver).</li>
<li><strong>&quot;O seu navegador bloqueou o portal dentro do Moodle.&quot;</strong> com o botão <strong>Abrir o portal em nova aba</strong> — o navegador do aluno bloqueia o acesso de sites abertos dentro de outros (bloqueio de cookies de terceiros). O aluno clica no botão e usa o portal numa aba própria. Para evitar isso para todos, use o subdomínio recomendado acima.</li>
</ul>
<figure><img src="{{IMG}}administrador_21_plano_b.png" alt="Aviso &quot;Abrir o portal em nova aba&quot; dentro da página do curso"><figcaption>Aviso &quot;Abrir o portal em nova aba&quot; dentro da página do curso</figcaption></figure>
<ul>
<li><strong>O painel da equipe não abre dentro do Moodle</strong> — é assim de propósito; abra o painel no próprio endereço.</li>
</ul>
