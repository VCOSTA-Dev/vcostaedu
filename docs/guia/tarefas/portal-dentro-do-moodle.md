---
titulo: Portal dentro do Moodle
resumo: Mostrar o portal do aluno numa página do Moodle (iframe) e o que fazer se o navegador bloquear.
perfis: administrador
ordem: 65
relacionados: email-smtp, usuarios-e-niveis
entrou: 1.7.0
mudou: 1.7.1
---
## Para que serve

O aluno vê o boletim, as notas e o horário **sem sair do Moodle**: o portal aparece dentro de uma página do curso.

## Antes de começar

- O portal e o Moodle precisam estar em **HTTPS**. Sem HTTPS, o navegador não guarda o acesso de um site aberto dentro de outro.
- **Recomendado:** o portal num **subdomínio do mesmo domínio do Moodle** (por exemplo, Moodle em `moodle.escola.exemplo.invalid` e portal em `portal.escola.exemplo.invalid`). Assim o navegador trata os dois como o mesmo site e não bloqueia o acesso.
- Quem instala o sistema precisa editar um arquivo no servidor. Se não for você, repasse este guia a essa pessoa.

## Passo a passo

1. **No servidor do portal**, no arquivo `portal/core/Config.php`, informe o endereço do Moodle (só o começo, sem caminho) na linha:

   `define('ORIGENS_PERMITIDAS_IFRAME', 'https://moodle.escola.exemplo.invalid');`

   Mais de um endereço: separe por espaço. Só são aceitos endereços `https://`.

   `COOKIE_PARTICIONADO`: deixe `true` se o portal está num domínio diferente do Moodle. Assim, o acesso dentro do Moodle continua mesmo nos navegadores que bloqueiam "cookies de terceiros", mas o aluno entra de novo se abrir o portal numa aba própria. Com o portal num **subdomínio do mesmo domínio do Moodle** (o recomendado), pode ser `false`: o navegador trata os dois como o mesmo site e o acesso é um só.
2. **No Moodle**, dentro do curso, com a edição ativada, escolha uma das formas:
   - **Recurso "URL"**: cole o endereço do portal (`https://portal.escola.exemplo.invalid/portal/`) e, em **Aparência → Exibir**, escolha **Embutir**.
   - **Bloco "HTML"** (ou um rótulo/página): no editor, use o modo de código e cole:

   `<iframe src="https://portal.escola.exemplo.invalid/portal/" style="width:100%;height:800px;border:0"></iframe>`

3. Abra o curso como um aluno de teste, entre no portal dentro do Moodle e abra o **Boletim**. Se ele aparece sem pedir o login de novo, está pronto.

![Página de curso com o portal embutido, mostrando o boletim](img/administrador_20_portal_no_moodle.png)

> Só o **portal do aluno** pode aparecer dentro de outro site. O **painel da equipe** e a **página de verificação** de documentos nunca aparecem num iframe, por segurança.

## O que pode dar errado

- **O quadro fica em branco ou mostra "recusou a conexão"** — o endereço do Moodle não está em `ORIGENS_PERMITIDAS_IFRAME` (confira `https://`, o nome exato e a porta, se houver).
- **"O seu navegador bloqueou o portal dentro do Moodle."** com o botão **Abrir o portal em nova aba** — o navegador do aluno bloqueia o acesso de sites abertos dentro de outros (bloqueio de cookies de terceiros). O aluno clica no botão e usa o portal numa aba própria. Para evitar isso para todos, use o subdomínio recomendado acima.

![Aviso "Abrir o portal em nova aba" dentro da página do curso](img/administrador_21_plano_b.png)

- **O painel da equipe não abre dentro do Moodle** — é assim de propósito; abra o painel no próprio endereço.
