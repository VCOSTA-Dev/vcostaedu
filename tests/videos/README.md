# Vídeos dos guias da Ajuda (1.18.0)

Cada guia de `docs/guia/tarefas/*.md` tem um roteiro de gravação em `roteiros/<slug>.js`. O gravador abre o Chromium
do Playwright **sem janela**, mostra a tarefa do guia na **demonstração fictícia** e produz, em `assets/videos/guias/`
(pasta fora do git):

| Arquivo | O que é |
|---|---|
| `<slug>.mp4` | H.264, 1280×720, sem áudio, `faststart` (toca em qualquer navegador, inclusive Safari) |
| `<slug>.jpg` | poster (um quadro representativo) |
| `<slug>.vtt` | legenda pt-BR: o texto de narração de cada passo, com os tempos medidos na gravação |
| `<slug>.capitulos.vtt` | capítulos |
| `<slug>.txt` | transcrição (a narração em texto) |

O git guarda só os roteiros, este gravador e o manifesto `docs/guia/videos.json` (um item por guia: título, arquivo,
duração, tamanho, versão do sistema na gravação, hash do roteiro e do guia, capítulos e transcrição). O gerador dos
guias (`database/ferramentas/guias/gerar_guias.php`) leva o manifesto para o índice da Ajuda, que mostra o player.

## Só na demonstração

O gravador **nunca** grava na base com dados reais. Antes de abrir qualquer tela, `banco.php` confere, na Config da
raiz servida:
- que a instituição do banco em uso é a fictícia da demonstração;
- `AMBIENTE = 'demonstracao'` e `BANCO_DEMONSTRACAO = DBNAME` (o banco separado, D-471);
- que não há emissão fora da demonstração;
- que a URL aponta para essa mesma raiz.

Falhando uma delas, sai com o código 9 e nada é aberto.

A demonstração fica num **worktree** do repositório (ex.: `C:\xampp\htdocs\vcostaedu-demo`), com Configs
próprias (ignoradas) apontando para o banco `vcostaedu_demo`. Para montá-la:

```
git worktree add --detach ../vcostaedu-demo HEAD
# copie painel/core/Config.php e portal/core/Config.php para o worktree, troque DBNAME para o banco da demonstração e
# acrescente define('BANCO_DEMONSTRACAO', '<o mesmo DBNAME>'); com AMBIENTE = 'demonstracao'
mysql -e "CREATE DATABASE vcostaedu_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
php database/ferramentas/implantacao/instalar_banco.php          (no worktree)
php database/ferramentas/criar_admin.php admin.demo admin.demo@exemplo.invalid
php database/ferramentas/limpar_demo.php
php database/ferramentas/seed_demo_processar.php http://localhost/vcostaedu-demo/
```

A cada gravação, o banco da demonstração é **retratado** (mysqldump) e, no fim, **volta ao retrato** (inclusive os
arquivos enviados). Assim, cada roteiro pode preparar e mexer nos dados à vontade e os vídeos regravam em qualquer ordem.
As senhas das contas usadas são trocadas por senhas aleatórias só durante a gravação (o retrato devolve as originais).

## Como rodar

Uma vez: `npm install` nesta pasta (instala `playwright-core`, `ffmpeg-static` com H.264 e `pdfjs-dist`, tudo local em
`node_modules/`, ignorado) e `npx playwright-core install chromium`. Opcional: `config.local.json` (ignorado) ou
variáveis `VCOSTAEDU_VIDEOS_*` com `BASE`, `RAIZ`, `SAIDA`, `PHP` e `FFMPEG`; os padrões servem para a máquina local.

| Comando | O que faz |
|---|---|
| `node gravar.js` | grava **todos** os guias que têm roteiro |
| `node gravar.js --guia=<slug>[,<slug>…]` | grava só esses |
| `node gravar.js --desatualizados` | grava só os **sem vídeo** e os **desatualizados** |
| `node conferir.js` | lista os guias sem roteiro, sem vídeo e desatualizados (sai com 1 se houver algum) |

Um vídeo fica **desatualizado** quando o roteiro muda, quando o guia é editado depois da gravação ou quando o arquivo
não está na pasta. Depois de gravar, rode o gerador dos guias para o índice da Ajuda levar o manifesto novo.
Duas gravações não rodam juntas: a segunda espera a vez.

## Escrever um roteiro

```js
module.exports = {
  conta: 'secretaria.demo',          // a conta já logada no começo (painel: usuário; portal: o RA, ex. '260104')
  contas: ['260108'],                // outras contas que o roteiro usa com v.entrar() (opcional)
  sql: ids => `UPDATE …;`,           // preparação extra no banco da demonstração (opcional; o retrato desfaz)
  async gravar(v) {
    v.capitulo('Abrir a lista');                  // um capítulo (sem nenhum, o 1º passo vira capítulo)
    await v.ir('painel/academicos/listar');       // rota a partir da URL base
    await v.passo('O que está acontecendo e o que observar.');   // vira legenda (curta: 1 a 2 linhas)
    await v.clicarENavegar('a[href$="academicos/cadastrar"]');   // destaca, leva o cursor e clica
    await v.digitar('#nome', 'Nome inventado');
    v.confere(/sucesso/i.test(await v.alerta()), 'a tela deveria confirmar');  // falhou = vídeo recusado
    v.poster();                                   // este momento vira o poster
  },
};
```

API de `v`:
- **Ações** (destaque e cursor visíveis): `ir`, `clicar`, `clicarENavegar`, `digitar`, `escolher`, `destacar`, `rolar(alvo|pixels)`, `pausa(ms)`.
- **Leitura da tela:** `texto(sel)`, `alerta()`, `page` (o Playwright).
- **Abas e PDF:** `abrirAqui(link)` abre um link `target=_blank` na própria aba; `pdf(link|rota, s)` mostra um PDF do sistema num visor e volta à tela.
- **Conta e dados:** `entrar(conta)` faz o login visível; `ids` traz os ids da demonstração (curso, ofertas por sigla, alunos e matrículas por RA, emissões, modelos, usuários).

Regras de roteiro:
- mostrar **exatamente a tarefa do guia**, do começo ao fim, com os passos de recusa e erro que o guia ensina;
- duração de 30 s a 2 min (visão geral até 3 min);
- só nomes e dados inventados;
- nada de mudar o sistema para o roteiro funcionar.

## A quinta onda: o padrão "Faça comigo", a máscara, o percurso da palestra

**A narração na tela.** Todo vídeo mostra a janela de narração no padrão do "Faça comigo" (`narrador.js`): o título, o
texto, o "Agora: …" e, depois de cada ação, o aviso do que foi feito (`v.feito`). `v.passo(texto, { t, aviso })` desenha
a janela (ela foge do alvo, como a do produto). No painel, `v.facaComigo(slug, { valores })` roda um roteiro de
`painel/app/adms/Roteiros/<slug>.json` no próprio motor do produto, no modo "junto": a janela é a do produto, e o
gravador faz à vista o que a pessoa faria (as ações do arquivo e, nos passos sem ação, o valor dado — `true` é o clique
no alvo). Assim, o mesmo arquivo é a prova, o vídeo e o botão "Faça comigo" do guia.

**A máscara.** Nenhum endereço local, porta, caminho de servidor, chave da API ou segredo da gravação (o endereço e a
chave do Moodle de teste, passados pelo ambiente) aparece: o texto vira "•••" e o campo é desenhado com bolinhas (o valor
do formulário não muda). Ela age desde o primeiro nó da página, ainda durante o carregamento.

**A prova.** Um passo que não acontece (`v.confere`), um erro no console ou um aviso do PHP recusam o vídeo. O texto de
cada tela gravada (depois da máscara) fica em `saida/<slug>.tela.txt` para a varredura.

| Comando | O que faz |
|---|---|
| `node palestra.js superior\|escola` | o percurso da palestra: instala do zero pela tela (na pasta e no banco da gravação), monta a demonstração, conecta ao Moodle de teste, cria a disciplina (na escola, a turma), cadastra e enturma, Envios ao Moodle, o aluno no Moodle sem senha e o portal de matrícula de exemplo — um vídeo contínuo e um por capítulo (`docs/guia/palestra.json`) |
| `node varrer.js [--sabotar]` | a varredura do registro das telas, das legendas e das transcrições: endereço local, porta, caminho, chave, e-mail fora de `@exemplo.invalid`, nome de pasta ou de banco, jargão |
| `node galeria.js` | a galeria para assistir a tudo em sequência: `assets/videos/galeria.html` (fora do git e do produto) |
| `node material.js` | o quadro do material: `docs/MATERIAL.md` |
| `node palestra.js superior --so-montar` | só os capítulos 1 e 2, sem vídeo: a demonstração limpa, para regravar os guias (a palestra inteira deixa um aluno a mais e o Moodle ligado) |
| `node provar_faca_comigo.js [slug,...]` | cada roteiro do "Faça comigo" que não é de fase, do começo ao fim no motor do produto; o botão no guia; a sabotagem (um alvo que não existe) |
| `node provar_player.js` | o player (legenda, capítulos, avanço, 206) e CADA vídeo do manifesto aberto no seu guia, com a conta de um perfil dele |

**A instalação da gravação.** Um worktree à parte (o `VCOSTAEDU_VIDEOS_RAIZ`, servido em `VCOSTAEDU_VIDEOS_BASE`) com um banco
próprio, cujo nome termina em `_demo` (`VCOSTAEDU_VIDEOS_BANCO`). `preparar.php zerar` apaga as Configs dele e recria o
banco vazio (só aceita esse sufixo, e nunca a pasta de trabalho). A guarda (`banco.php`) aceita também a instalação
montada pela tela: o banco "_demo" com a demonstração ligada e a instituição fictícia (a faculdade ou o colégio).

**O que a onda 5 acrescentou ao roteiro.** `v.menu('<parte do endereço>')` abre um item do menu lateral (abre o grupo pelo
`aria-expanded`); `errosEsperados: [/…/]` no roteiro aceita o erro de console que o vídeo mostra de propósito (a recusa
do painel dentro de um iframe). Quando a gravação falha, `saida/falha-<slug>.txt|png|html` guardam o que se esperava, a
tela e o HTML (fora do git; a varredura não os lê). A coluna Senha do "Conheça o sistema" sai mascarada, e a varredura
reprova senha da demonstração à mostra.

**Os prints dos guias.** `php database/ferramentas/guias/capturar.php <endereço> <pasta node_modules>`, rodado de DENTRO
da pasta da instalação da gravação (ele usa a Config dela): troca por um instante as senhas de quatro contas, tira os
prints em `docs/guia/img/` daquela pasta, restaura tudo e usa a mesma máscara dos vídeos. Com `VCOSTAEDU_TELAS=<pasta>`, o
texto de cada print vai para lá (use `saida/prints`, que a varredura lê). Copie os PNG para o worktree e registre os
guias com o print atual em `docs/guia/prints.json`.

