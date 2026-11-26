<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Instalar o VCOSTAEDU num servidor Linux (Ubuntu ou Debian, com Apache e PHP do próprio sistema) que <strong>já tem o Moodle</strong>, sem parar o Moodle. Os comandos são de quem administra o servidor (com <code>sudo</code>). Onde aparece <code>$R</code>, leia &quot;a pasta do VCOSTAEDU no servidor&quot; (por exemplo, uma pasta própria dentro da pasta que o Apache publica).</p>
<h2>Antes de começar</h2>
<ul>
<li><strong>O que o instalador confere:</strong> PHP 8.1 ou superior, com <code>pdo_mysql</code>, <code>mbstring</code>, <code>gd</code>, <code>dom</code> e <code>iconv</code>. No Ubuntu, o</li>
</ul>
<p><code>dom</code> vem do pacote <code>php-xml</code>. O <code>zip</code> só é pedido pelas ferramentas de linha de comando (o backup e o pacote).</p>
<ul>
<li><strong>Instalar uma extensão</strong> (<code>apt install php8.3-mysql php8.3-mbstring php8.3-gd php8.3-xml php8.3-zip</code>, com a versão do</li>
</ul>
<p>seu PHP) só vale depois de <strong>reiniciar</strong> o Apache, e reiniciar derruba o Moodle por alguns segundos. Num servidor com o Moodle em uso, faça isso numa janela de manutenção.</p>
<ul>
<li><strong>Faça um backup</strong> do servidor antes, como em qualquer instalação.</li>
</ul>
<h2>Passo a passo</h2>
<ol>
<li><strong>Ligue o <code>mod_rewrite</code></strong> (no Ubuntu ele vem desligado; sem ele, as rotas do sistema dão erro):</li>
</ol>
<p><code>sudo a2enmod rewrite</code> e <code>sudo systemctl reload apache2</code>. O <code>reload</code> basta e não derruba o Moodle (o <code>a2enmod</code> sugere <code>restart</code>, que não é preciso). O Moodle não usa os <code>.htaccess</code>, então ligar o módulo não muda nada nele.</p>
<ol>
<li><strong>Dê ao VCOSTAEDU um site próprio, numa porta ou num domínio próprio</strong>, com <code>AllowOverride All</code> na pasta dele. O</li>
</ol>
<p>padrão do Ubuntu é <code>AllowOverride None</code>, e esse é o tropeço mais traiçoeiro: o Apache ignora os <code>.htaccess</code> <strong>sem avisar</strong>, as rotas não abrem e as pastas que deviam ser protegidas (a configuração, as etapas do banco, o código) <strong>respondem pela web</strong>. Um exemplo de site (<code>/etc/apache2/sites-available/vcostaedu.conf</code>, com a sua porta ou o seu domínio):</p>
<p>``<code>apache &lt;VirtualHost *:PORTA&gt; DocumentRoot $R &lt;Directory $R&gt; Options FollowSymLinks AllowOverride All Require all granted DirectoryIndex index.php &lt;/Directory&gt; &lt;/VirtualHost&gt; </code>``</p>
<p>Numa porta nova, ponha também <code>Listen PORTA</code> no mesmo arquivo. Depois: <code>sudo a2ensite vcostaedu</code>, <code>sudo apache2ctl configtest</code> (tem de dizer &quot;Syntax OK&quot;) e <code>sudo systemctl reload apache2</code>.</p>
<ol>
<li><strong>Subpasta sob um <code>Alias</code> pede ajuste.</strong> Os <code>.htaccess</code> do sistema reescrevem as rotas sem <code>RewriteBase</code>: sob um</li>
</ol>
<p><code>Alias</code> (por exemplo, o sistema em <code>/vcostaedu</code> dentro do site do Moodle), o Apache monta o caminho errado e as rotas dão erro. Prefira a porta ou o domínio próprio. Se a subpasta for obrigatória, é preciso acrescentar o <code>RewriteBase</code> com o caminho nos <code>.htaccess</code> do sistema (e repetir a cada atualização).</p>
<ol>
<li><strong>Crie o banco com a collation certa.</strong> O MariaDB recente usa outra collation de partida; escreva a do sistema:</li>
</ol>
<p><code>CREATE DATABASE vcostaedu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</code>, um usuário só desse banco (<code>GRANT ALL PRIVILEGES ON vcostaedu.* TO ...</code>, que inclui o <code>TRIGGER</code>, usado por duas etapas do banco) e uma senha forte, guardada fora da pasta publicada.</p>
<ol>
<li><strong>Copie o sistema e acerte as permissões.</strong> O pacote (ou o zip do repositório) extrai com permissões largas e com o</li>
</ol>
<p>dono de quem extraiu. O código fica do <code>root</code>, e o servidor web só lê:</p>
<p>``<code>bash sudo chown -R root:root $R sudo find $R -type d -exec chmod 755 {} + sudo find $R -type f -exec chmod 644 {} + </code>``</p>
<p>Dê ao usuário do servidor web (<code>www-data</code>) <strong>só</strong> as pastas em que o sistema grava: <code>$R/painel/core</code> e <code>$R/portal/core</code> (o instalador grava ali os dois <code>Config.php</code>), <code>$R/assets/instituicao</code> (o logo), <code>$R/assets/videos</code> e <code>$R/assets/videos/guias</code> (os vídeos da Ajuda), e <code>$R/painel/assets/img/usuario</code> e <code>$R/portal/assets/img/usuario</code> (as fotos de perfil). A pasta dos <strong>arquivos privados</strong> (os PDFs emitidos, as assinaturas, o segredo da chave do Moodle) fica <strong>fora</strong> da pasta publicada, também do <code>www-data</code>, com <code>chmod 750</code>.</p>
<ol>
<li><strong>Abra o endereço</strong> do site no navegador. O instalador aparece e guia o resto. No banco, use <strong><code>localhost</code></strong> como</li>
</ol>
<p>servidor quando o banco é da mesma máquina: o PHP usa o socket e a porta não importa. Se o banco estiver em outra porta ou em outra máquina, informe a <strong>porta</strong> no campo do instalador (de partida, 3306).</p>
<ol>
<li><strong>Depois de instalar</strong>, devolva as pastas de configuração ao <code>root</code> e deixe o <code>Config.php</code> só legível pelo servidor</li>
</ol>
<p>web: <code>sudo chown root:root $R/painel/core $R/portal/core</code>, <code>sudo chown root:www-data $R/<em>/core/Config.php</code> e <code>sudo chmod 640 $R/</em>/core/Config.php</code>. Confira em <strong>Manutenção → Verificações de segurança</strong>.</p>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>Erro 500 em todas as telas:</strong> o <code>mod_rewrite</code> está desligado (passo 1).</li>
<li><strong>As rotas não abrem e os arquivos de configuração respondem pela web:</strong> falta o <code>AllowOverride All</code> (passo 2). Confira</li>
</ul>
<p>em Manutenção → Verificações de segurança: cada arquivo privado tem de ser recusado.</p>
<ul>
<li><strong>&quot;não conectou ao banco&quot;:</strong> com <code>127.0.0.1</code>, a conexão vai pela porta; com <code>localhost</code>, pelo socket. Use <code>localhost</code></li>
</ul>
<p>ou informe a porta certa.</p>
<ul>
<li><strong>A foto de perfil não grava:</strong> a pasta <code>assets/img/usuario</code> do painel ou do portal sem permissão para o <code>www-data</code></li>
</ul>
<p>(passo 5).</p>
<ul>
<li><strong>Os vídeos saem sem os cabeçalhos de cache:</strong> o <code>mod_headers</code> está desligado. Não quebra nada; para ligar,</li>
</ul>
<p><code>sudo a2enmod headers</code> e <code>sudo systemctl reload apache2</code>.</p>
