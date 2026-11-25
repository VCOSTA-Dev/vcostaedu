# A revisão de segurança (Onda 6, Etapa 6)

A revisão do painel e do portal, em sete pontos, com o que foi corrigido e o que ficou, com a gravidade e o motivo.
A prova é `tests/tabler/onda6/etapa6.js`. Cada correção tem uma sabotagem: o defeito posto de propósito, que a prova
reprova. A decisão está em DECISIONS D-58.

Gravidade: **alta** (dá acesso ao servidor ou aos dados de outra pessoa), **média** (precisa de um usuário com acesso e
de uma ação da vítima), **baixa** (endurecimento).

## 1. O envio forjado de formulário (CSRF)

| Achado | Gravidade | Situação |
|---|---|---|
| A única defesa era o cookie de sessão `SameSite=Lax`. No portal em iframe (HTTPS e `ORIGENS_PERMITIDAS_IFRAME`), o cookie é `SameSite=None` e acompanha um POST vindo de outro site | média | **corrigido** |
| Duas ações gravavam por GET, que o `Lax` deixa passar numa navegação vinda de outro site: apagar o ano letivo (um link na lista) e apagar o módulo (a tela já usava POST; a rota conferia) | média | **corrigido** (o ano letivo; o módulo já recusava o GET) |

A correção fica num ponto só (`Core\Seguranca::iniciar`, igual no painel e no portal). Todo POST com o `Origin` de
outro endereço, ou sem `Origin` e com o `Referer` de outro endereço, é recusado com 403 e uma página que explica.
Um POST sem nenhum dos dois, que vem de um programa e não de um navegador, continua passando, e o cookie e a permissão
da rota continuam valendo para ele. A API não passa por aqui, porque usa a sua chave. Apagar o ano letivo passou a ser
só por POST, com um botão de formulário no lugar do link.

**Fica:** um token por formulário. A conferência da origem cobre os navegadores atuais, que mandam o `Origin` em todo
POST. O token pede mudar todas as telas que gravam, e fica para quando houver motivo novo. Gravidade **baixa**.

## 2. A sessão e os cookies

| Achado | Gravidade | Situação |
|---|---|---|
| A sessão não tinha limite de inatividade: durava até fechar o navegador | média | **corrigido** |

O cookie já era HttpOnly, Secure com HTTPS e SameSite. O identificador já trocava no login (a prova `sessao.js`). A
situação e o nível do usuário já eram relidos a cada requisição. Agora, a sessão sem uso por mais de
`SESSAO_INATIVA_MINUTOS` (padrão 120, na Config; 0 desliga) é encerrada. A próxima tela pede para entrar de novo, com o
motivo. As instalações novas recebem a constante pelo molde da Config. As que já existem ficam com o padrão sem mexer
em nada.

## 3. O upload de arquivo

| Achado | Gravidade | Situação |
|---|---|---|
| A foto do usuário (painel) e do aluno (painel e portal) tomava o tipo do que o navegador dizia e gravava o arquivo com a extensão enviada, numa pasta servida pelo Apache. Um `foto.php` com uma imagem válida dentro, e o código depois dela, era gravado e podia rodar como programa | **alta** | **corrigido** |

Três camadas:
1. **O tipo:** vem do conteúdo (`getimagesize`).
2. **O nome:** precisa ter a extensão desse tipo (`.jpg`/`.jpeg` ou `.png`). Um `foto.php` é recusado, mesmo com uma
   imagem de verdade dentro.
3. **A pasta:** nada roda como programa dentro de `assets/` (uma regra do `.htaccess` da raiz, que dá 404).

A imagem continua redesenhada pelo GD. O logo da instituição e a assinatura dos documentos já conferiam o conteúdo
(`finfo` e `getimagesize`) e gravam uma imagem nova, então não houve achado neles. As planilhas e os CSVs são lidos e
não ficam gravados como arquivo.

## 4. A saída sem tratamento nas telas

| Achado | Gravidade | Situação |
|---|---|---|
| Valores do banco escritos nas telas sem `htmlspecialchars`: nomes, e-mails, observações, títulos de atividade, os campos que voltam ao formulário depois de um erro (`isset($x) ? $x : ''`) e os títulos montados com o nome da disciplina. Um nome cadastrado com código de página rodaria no navegador de quem abre a lista (XSS guardado) | média | **corrigido** |
| As listas de professores, salas e coordenadores chegam juntadas com `<br>` pelo banco e eram escritas cruas | média | **corrigido** |

1.152 saídas em 121 telas passaram a sair tratadas. O que é HTML de propósito ficou como está, porque já é montado
tratado:
- o título da página e os botões dela;
- as células dos relatórios;
- os botões feitos pelos ajudantes da tela;
- os guias da Ajuda, gerados e tratados pelo gerador.

As listas juntadas com `<br>` são tratadas nome a nome. A prova põe um nome com código de página num aluno e abre a
lista, a ficha e o boletim. O nome aparece como texto e nenhum alerta dispara.

**Fica:** atributos `data-*` lidos por scripts das telas que escrevem com `innerHTML`. O valor chega tratado no
atributo, mas o script o devolve à página. Não há achado provado; fica anotado para uma revisão dos scripts.
Gravidade **baixa**.

## 5. O limite de tentativas de entrada

Já existia (`AdmsLimiteTentativas`; Configurações → Tentativas de acesso; `seg_tentativas`, por escopo: a entrada, o
"esqueci a senha" e a verificação pública). **Sem achado.**

## 6. Os cabeçalhos de segurança

| Achado | Gravidade | Situação |
|---|---|---|
| Faltava `Permissions-Policy` | baixa | **corrigido** (`camera=(), microphone=(), geolocation=(), payment=(), usb=()`) |
| Não há `Content-Security-Policy` de script | baixa | **fica** |

Já havia `nosniff`, `Referrer-Policy`, `frame-ancestors` (o painel nunca embutível; o portal só nas origens
configuradas) com `X-Frame-Options`, e o HSTS com `EXIGIR_HTTPS`.

**Fica:** a política de script. As telas têm script embutido, e trocar isso é obra grande. A saída tratada (o ponto 4)
é a defesa que vale agora.

## 7. A varredura de segredos no repositório

| Onde | Resultado |
|---|---|
| A árvore de agora | 0 achados. A varredura de higiene roda antes de cada commit (`provas/_retomada/higiene.sh`, fora do repositório), e a pasta limpa da publicação vem da Etapa 8 |
| O histórico do git, todas as versões | **0** chaves do Moodle de teste e **0** chaves de API reais. Há 2 versões de um arquivo de TESTE de uma biblioteca de terceiros com uma chave privada de exemplo, já fora da árvore. Há nomes de origem em versões antigas de arquivos já limpos |

A publicação parte da pasta limpa, **sem o histórico** (Etapa 8, `pasta_limpa.php`): o histórico privado não vai a
público.

## As bibliotecas

O Composer não está disponível nesta máquina (nem `composer` nem `composer.phar` no caminho). Por isso as bibliotecas
**não foram atualizadas** nesta onda. As versões instaladas são:
- **PDF:** `dompdf/dompdf` v2.0.4 (com `masterminds/html5` 2.8.1 e `sabberworm/php-css-parser` 8.5.1).
- **E-mail:** `phpmailer/phpmailer` v6.6.0.

Para rodar a atualização:
1. **Instalar o Composer 2:** baixar o `composer.phar` de getcomposer.org e rodar `php composer.phar --version`.
2. **O PDF, primeiro e sozinho:** em `painel/`, rodar `php composer.phar update dompdf/dompdf --with-dependencies`. A
   versão maior fica a 2: o `composer.json` pede `^2.0`.
3. **A comparação do PDF:** emitir o boletim, o histórico e o atestado da demonstração antes e depois, e comparar o
   texto (`pdftotext`, a mesma comparação da DUVIDAS 129). A régua do ensino superior também precisa ficar idêntica.
4. **O e-mail, depois:** `php composer.phar update phpmailer/phpmailer` em `painel/` e em `portal/`. O `composer.json`
   pede `^6.6`.
5. **A prova do envio:** a do e-mail de envio e a do link de acesso.
6. **Antes do commit:** rodar a bateria inteira.

## O nível criado pela tela

Em **Verificações de segurança**, o item novo "Perfis criados pela tela". O perfil criado numa instalação anterior não
tem papel (DUVIDAS 66 e 146). Ele segue só as permissões marcadas para ele, sem o limite das ações de um perfil do
sistema. Por isso aparece como **atenção**, com o nome e o que fazer: conferir as permissões em Equipe → Perfis de
acesso, ou passar as pessoas para um perfil do sistema.
