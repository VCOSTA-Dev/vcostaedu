---
titulo: Instalar no Linux, ao lado do Moodle
resumo: O VCOSTAEDU num servidor Linux com Apache, PHP e MariaDB, no mesmo servidor do Moodle, sem mexer nele: o mod_rewrite, o AllowOverride, o banco com a collation certa, as permissões das pastas e o porquê de uma porta ou um domínio próprio.
perfis: administrador
ordem: 2
relacionados: instalar-como-curso-livre, o-tipo-de-instituicao, conectar-ao-moodle
telas:
entrou: 1.102.1
mudou: 1.103.0
---
## Para que serve

Instalar o VCOSTAEDU num servidor Linux (Ubuntu ou Debian, com Apache e PHP do próprio sistema) que **já tem o Moodle**,
sem parar o Moodle. Os comandos são de quem administra o servidor (com `sudo`). Onde aparece `$R`, leia "a pasta do
VCOSTAEDU no servidor" (por exemplo, uma pasta própria dentro da pasta que o Apache publica).

## Antes de começar

- **O que o instalador confere:** PHP 8.1 ou superior, com `pdo_mysql`, `mbstring`, `gd`, `dom` e `iconv`. No Ubuntu, o
  `dom` vem do pacote `php-xml`. O `zip` só é pedido pelas ferramentas de linha de comando (o backup e o pacote).
- **Instalar uma extensão** (`apt install php8.3-mysql php8.3-mbstring php8.3-gd php8.3-xml php8.3-zip`, com a versão do
  seu PHP) só vale depois de **reiniciar** o Apache, e reiniciar derruba o Moodle por alguns segundos. Num servidor com
  o Moodle em uso, faça isso numa janela de manutenção.
- **Faça um backup** do servidor antes, como em qualquer instalação.

## Passo a passo

1. **Ligue o `mod_rewrite`** (no Ubuntu ele vem desligado; sem ele, as rotas do sistema dão erro):
   `sudo a2enmod rewrite` e `sudo systemctl reload apache2`. O `reload` basta e não derruba o Moodle (o `a2enmod` sugere
   `restart`, que não é preciso). O Moodle não usa os `.htaccess`, então ligar o módulo não muda nada nele.
2. **Dê ao VCOSTAEDU um site próprio, numa porta ou num domínio próprio**, com `AllowOverride All` na pasta dele. O
   padrão do Ubuntu é `AllowOverride None`, e esse é o tropeço mais traiçoeiro: o Apache ignora os `.htaccess` **sem
   avisar**, as rotas não abrem e as pastas que deviam ser protegidas (a configuração, as etapas do banco, o código)
   **respondem pela web**. Um exemplo de site (`/etc/apache2/sites-available/vcostaedu.conf`, com a sua porta ou o seu
   domínio):

   ```apache
   <VirtualHost *:PORTA>
       DocumentRoot $R
       <Directory $R>
           Options FollowSymLinks
           AllowOverride All
           Require all granted
           DirectoryIndex index.php
       </Directory>
   </VirtualHost>
   ```

   Numa porta nova, ponha também `Listen PORTA` no mesmo arquivo. Depois: `sudo a2ensite vcostaedu`,
   `sudo apache2ctl configtest` (tem de dizer "Syntax OK") e `sudo systemctl reload apache2`.
3. **Subpasta sob um `Alias` pede ajuste.** Os `.htaccess` do sistema reescrevem as rotas sem `RewriteBase`: sob um
   `Alias` (por exemplo, o sistema em `/vcostaedu` dentro do site do Moodle), o Apache monta o caminho errado e as rotas
   dão erro. Prefira a porta ou o domínio próprio. Se a subpasta for obrigatória, é preciso acrescentar o `RewriteBase`
   com o caminho nos `.htaccess` do sistema (e repetir a cada atualização).
4. **Crie o banco com a collation certa.** O MariaDB recente usa outra collation de partida; escreva a do sistema:
   `CREATE DATABASE vcostaedu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`, um usuário só desse banco
   (`GRANT ALL PRIVILEGES ON vcostaedu.* TO ...`, que inclui o `TRIGGER`, usado por duas etapas do banco) e uma senha
   forte, guardada fora da pasta publicada.
5. **Copie o sistema e acerte as permissões.** O pacote (ou o zip do repositório) extrai com permissões largas e com o
   dono de quem extraiu. O código fica do `root`, e o servidor web só lê:

   ```bash
   sudo chown -R root:root $R
   sudo find $R -type d -exec chmod 755 {} +
   sudo find $R -type f -exec chmod 644 {} +
   ```

   Dê ao usuário do servidor web (`www-data`) **só** as pastas em que o sistema grava: `$R/painel/core` e
   `$R/portal/core` (o instalador grava ali os dois `Config.php`), `$R/assets/instituicao` (o logo), `$R/assets/videos` e
   `$R/assets/videos/guias` (os vídeos da Ajuda), e `$R/painel/assets/img/usuario` e `$R/portal/assets/img/usuario` (as
   fotos de perfil). A pasta dos **arquivos privados** (os PDFs emitidos, as assinaturas, o segredo da chave do Moodle)
   fica **fora** da pasta publicada, também do `www-data`, com `chmod 750`.
6. **Abra o endereço** do site no navegador. O instalador aparece e guia o resto. No banco, use **`localhost`** como
   servidor quando o banco é da mesma máquina: o PHP usa o socket e a porta não importa. Se o banco estiver em outra
   porta ou em outra máquina, informe a **porta** no campo do instalador (de partida, 3306).
7. **Depois de instalar**, devolva as pastas de configuração ao `root` e deixe o `Config.php` só legível pelo servidor
   web: `sudo chown root:root $R/painel/core $R/portal/core`, `sudo chown root:www-data $R/*/core/Config.php` e
   `sudo chmod 640 $R/*/core/Config.php`. Confira em **Manutenção → Verificações de segurança**.

## O que pode dar errado

- **Erro 500 em todas as telas:** o `mod_rewrite` está desligado (passo 1).
- **As rotas não abrem e os arquivos de configuração respondem pela web:** falta o `AllowOverride All` (passo 2). Confira
  em Manutenção → Verificações de segurança: cada arquivo privado tem de ser recusado.
- **"não conectou ao banco":** com `127.0.0.1`, a conexão vai pela porta; com `localhost`, pelo socket. Use `localhost`
  ou informe a porta certa.
- **A foto de perfil não grava:** a pasta `assets/img/usuario` do painel ou do portal sem permissão para o `www-data`
  (passo 5).
- **Os vídeos saem sem os cabeçalhos de cache:** o `mod_headers` está desligado. Não quebra nada; para ligar,
  `sudo a2enmod headers` e `sudo systemctl reload apache2`.
