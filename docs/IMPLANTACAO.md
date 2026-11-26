# Implantação: o servidor web

O VCOSTAEDU roda num servidor web com PHP 8.1 ou superior (com `pdo_mysql`, `mbstring`, `gd`, `dom` e `iconv`: o que o instalador confere). As **rotas** (o endereço curto de cada tela) e as **pastas
que não respondem pela web** (o código, a configuração, as etapas do banco, os testes, os arquivos privados) são feitas
pelos arquivos `.htaccess`, que **só o Apache lê**.

## No Apache

Ligue o `mod_rewrite` e deixe a pasta do sistema ler os `.htaccess` (`AllowOverride All`). Nada mais.

## No Nginx

O Nginx **não lê** `.htaccess`: sem a configuração certa, as telas não abrem pelo endereço curto e as pastas
protegidas **respondem** — a configuração da instalação, as etapas do banco e o código ficam abertos pela web.

1. Use o exemplo `docs/nginx/vcostaedu.conf` (ele faz as mesmas rotas e nega as mesmas pastas; cada bloco diz de qual
   `.htaccess` veio). Troque os três valores marcados com `TROQUE`: o nome do site, a pasta do sistema e o endereço do
   PHP-FPM.
2. Ponha o arquivo na configuração do Nginx do servidor, confira com `nginx -t` e recarregue o Nginx.
3. Entre no painel como Administrador e abra **Manutenção → Verificações de segurança**. O item **Pastas protegidas**
   pede pela web um arquivo de cada pasta protegida: todos têm de recusar. Se algum responde, o item diz **FALHA** e
   qual pasta — não abra o sistema para a instituição até corrigir.
4. Para a lista completa (cada arquivo privado da instalação), rode no servidor
   `php database/ferramentas/implantacao/varredura_exposicao.php <endereço do sistema>`.

Numa subpasta (ex.: `/vcostaedu/`), ponha o nome dela na frente de cada endereço do exemplo e indique a subpasta em
`CAMINHO_BASE`, na configuração.

## Ao atualizar o sistema

Uma versão nova pode trazer uma pasta protegida nova (um `.htaccess` novo). O registro das mudanças diz quando isso
acontece; no Nginx, acrescente a recusa da pasta e confira de novo as Verificações de segurança.

## A porta do banco (1.103.0; D-131)

O instalador pede a **porta** do banco, com 3306 (a padrão do MySQL e do MariaDB) já preenchida. Ela vai para a constante
`PORTA` das duas `Config.php`, e todas as conexões a usam: as telas, o executor das migrations, as ferramentas de linha
de comando, o backup (`mysqldump -P`) e a restauração. Na instalação pela linha de comando, ponha a porta na constante
`PORTA` ao copiar os modelos `Config.example.php`. Uma instalação antiga, sem a constante, continua na 3306.
