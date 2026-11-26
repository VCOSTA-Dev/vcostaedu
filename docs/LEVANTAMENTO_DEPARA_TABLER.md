# VCOSTAEDU — Levantamento do de-para AdminLTE → Tabler

Escrito em 14/11/2026. É só leitura: nenhuma linha de código nesta pasta, nenhum arquivo copiado, nenhum banco aberto para escrita.

"Este sistema" e "o sistema de origem" são o mesmo sistema (painel e portal, AdminLTE 3.2 / Bootstrap 4.6.1). As outras fontes:

| sigla | pasta | papel |
|---|---|---|
| **ORIG** | o sistema de origem | o sistema a converter (v1.18.4) |
| **referência** | outro projeto da casa | a conversão AdminLTE → Tabler que já foi feita, em produção (chamado aqui de "o projeto de referência") |
| **EDU** | `vcostaedu` | o destino visual: instalador, rail, marca, Tabler vendorizado |
| **P2** | `portal` do sistema escolar em Tabler | o Portal 2.0 em Tabler do sistema escolar. **Confirmado**: é este. O portal do sistema escolar de origem não tem Tabler (`grep -rli tabler` vazio fora de vendor) |

Os caminhos `arquivo:linha` são relativos à pasta da fonte indicada. As contagens dizem o comando. Onde está escrito "não medido", não foi medido.

**Resposta curta à hipótese do responsável pelo projeto.** Sim, é um de-para, e é mais mecânico do que parece: no projeto de referência a conversão inteira levou **5 commits em 2 dias** (31/07 a 01/08/2026, mais a Home em 02/08), sem nenhum CSS de compatibilidade BS4. No ORIG, a camada de layout é **uma** (os includes), as listas são **um** componente (`escola1-listas.js`) e as ações são **um** helper (`AdmsUi`). O que **não** é de-para está concentrado em poucos lugares: o JS próprio que fala com o Bootstrap 4 por jQuery (`funcoes.js`, `escola1-ui.js`), o editor de modelos (Summernote), as mensagens de retorno geradas no servidor (318 ocorrências em 120 models e controllers), os quadros `info-box`/`small-box` e o menu lateral. E há um custo que não é código: **54 vídeos e 65 prints** ficam visualmente obsoletos.

---

## 1. Como a conversão foi feita no projeto de referência

### 1.1 O mecanismo

A escolha do layout é **por chamada do método de render**, não por configuração, rota ou usuário (projeto de referência, `docs/DECISIONS.md:83-87`, D-08 "Tema por parâmetro").

| peça | onde (projeto de referência) | o que faz |
|---|---|---|
| `baseIncludes()` | `core/ConfigView.php:45-52` | `$pasta = $layout === 'tabler' ? 'include_tabler' : 'include';` (`:48`); se a pasta não existe, cai em `include/` (`:51`) |
| `renderizar(string $layout='padrao')` | `core/ConfigView.php:54-76` | `'tabler'`: `head.php` + view + `libraries_js.php` (`:63-67`; o rail é incluído **pela view**). Sem argumento: `cabecalho` + `head_menu` + `menu` + view + `footer` + `rodapejs` (`:70-75`) |
| `renderizarLogin($layout)` | `core/ConfigView.php:86-101` | idem, com fallback por arquivo (`:94-96`) |
| `renderizarPublico()` | `core/ConfigView.php:119-131` | sempre `include_tabler` |
| opt-in no controller | ex.: `app/adms/Controllers/Login.php:118` | `renderizarLogin('tabler')` |
| bibliotecas por flag | `include_tabler/libraries_js.php:72-87`, `head.php:29,36,41,49` | `$this->dados['carregarDataTablesJs' / 'carregarSelect2Js' / 'carregarTomSelectJs' / 'carregarEditorJs' / 'carregarFormJs']` |

Contagens no projeto de referência hoje:

| medida | valor | comando |
|---|---|---|
| controllers | 53 | `ls app/adms/Controllers/*.php \| wc -l` |
| arquivos com `renderizar('tabler')`/`renderizarLogin('tabler')` | 41 arquivos, 111 chamadas | `grep -rlE "renderizar(Login)?\('tabler'\)" app --include=*.php` |
| controllers que renderizam pela trait `traits/ListaCrud.php:72` | 6 | `grep -rlE "use .*traits"` |
| controllers ainda em AdminLTE | **0** | `grep -rnE "renderizar(Login)?\(\)" app` só acha comentários |
| views com marcador AdminLTE/BS4 (`content-wrapper`, `data-toggle=`, `form-group`, `ml-/mr-`, `badge-*`, `custom-*`, `card-tools`) | **0** | `grep -rlE "<padrão>" --include=*.php app/adms/Views \| grep -v include/` |

O D-08 do projeto de referência diz que "só `home/index` ainda usa `include/`": está desatualizado, `Home.php:504` já chama `renderizar('tabler')` desde o commit `4b0642a` (02/08/2026). A pasta `include/` só é alcançada pelo fallback.

### 1.2 O que há nas duas pastas

**`include/` (AdminLTE, 7 arquivos):** `cabecalho.php` (head + `body.hold-transition sidebar-mini` + `.wrapper` + preloader), `head_menu.php` (`main-header`), `menu.php` (`main-sidebar` treeview do banco), `footer.php` (`main-footer`), `rodapejs.php` (jQuery, bootstrap.bundle 4.6.1, plugins, `adminlte.min.js`), `cabecalho_login.php`, `rodapejs_login.php`. Detalhe: já carregava `datatables-bs5` e `buttons.bootstrap5` sobre o Bootstrap 4 (`cabecalho.php:18`, "decisão da Etapa 2"), isto é, as listas mudaram de integração **antes** do layout.

**`include_tabler/` (24 arquivos):**
- esqueleto: `head.php` (`tabler-theme.min.js` antes do primeiro desenho, `:20`; FA `:23`; `tabler.min.css` `:24`; CSS condicional `:29-55`; `vcostable-tabler.css` `:56`; o CSS do projeto de referência `:60`), `libraries_js.php`, `rail.php`;
- outros layouts: `cabecalho_login`/`rodapejs_login`, `cabecalho_publico`/`rodapejs_publico`;
- partials de tela: `_page_header`, `_listing_toolbar`, `_dropdown_acoes`, `_paginacao` (este veio e não foi ligado, `head.php:42-45`);
- partials de negócio (12): `_barra_envio`, `_modal_avisos`, `_modal_novidades`, `_faixa_treino` etc.

Versões lidas dos cabeçalhos: Tabler 1.4.0, jQuery 3.6.0, Font Awesome Free 6.5.2, DataTables 1.10.20 com integração bs5, Buttons 2.2.2, Select2 4.0.12, Summernote 0.8.20 (build bs5), Tom Select 2.6.2, Inputmask 5.0.7. **Tabler Icons não é usado** (`grep "ti ti-"` = 0): os ícones continuaram Font Awesome.

### 1.3 Oito pares reais (a mesma tela antes e depois)

Não há arquivos irmãos (`X_tabler.php`): a tela foi convertida **no mesmo arquivo**. O par sai de `git show <c>^:arquivo` contra `git show <c>:arquivo`, contado com `grep -oE … | sort | uniq -c`.

| # | view (projeto de referência, `app/adms/Views/`) | commit | antes → depois |
|---|---|---|---|
| 1 | `nivelAcesso/listNivelAcesso.php` | `4a7c249` 31/07 (4a) | `data-dismiss` 6→0 e `data-bs-dismiss` 0→6; `class="close"` 3 → `btn-close` 5; `form-group` 4→0, `form-label` 0→2; `breadcrumb` 3→0; `container-xl` 0→3 |
| 2 | `nivelAcesso/editNivelAcesso.php` | `4a7c249` | `form-group` 2→0; `form-select` 0→2; `me-2` 0→2 |
| 3 | `usuarios/listUsuarios.php` | `6962445` 01/08 (4b) | `mr-1`→`me-2`; `card-table` e `table-vcenter` novos; modais BS5 |
| 4 | `usuarios/cadUsuario.php` | `6962445` | `form-group` 8→0; `form-label` 0→8; `form-select` 0→3 |
| 5 | `paginas/verPages.php` | `6962445` | `badge badge-pill badge-<cor>` (6) → `badge bg-<cor>-lt` (6); `alert-dismissible` sai |
| 6 | `perfil/editPerfil.php` | `6962445` | `btn-block` 2→0; `mr-1` 3 → `me-2` 1; `form-group` 8 → `form-label` 7 |
| 7 | `nivelAcesso/listPermissao.php` | `243011b` 01/08 (4c) | badges → `bg-*-lt`; `btn-close`; `bootstrap.Modal` → `tabler.Modal` |
| 8 | `acesso/login.php` | `6a943eb` 01/08 (4d) | `login-box` / `card-outline card-success` → `page page-center` + `container-tight`; `input-group-append` → `input-group-flat`; `btn-block` → `w-100` |

Escopo: 5 commits da Etapa 4 (4a–4e); `git diff --stat 412e3a1 85f8f00` = 40 arquivos em `app`, `core`, `assets/js` e `assets/css` (+3554/−2207), dos quais 25 views; 74 arquivos distintos somando os 6 commits de interface e dívida. 160 views fora de `include*/`, 107 com `page-wrapper`.

### 1.4 O que deu trabalho no projeto de referência e não era de-para

1. **`window.tabler`, não `window.bootstrap`.** O bundle do Tabler não expõe `window.bootstrap` (os componentes ficam em `window.tabler`). Foi preciso um `abrirModal()` que usa `new window.tabler.Modal(el).show()` (projeto de referência, `assets/js/funcoes.js:25-55`; `libraries_js.php:82-86`). Os comentários do projeto de referência dizem também que `$.fn.tooltip` não existe; a leitura do bundle nesta sessão mostra que ele **traz** a ponte jQuery do BS5, registrada só no `DOMContentLoaded` (§2.4): o que quebra é o código que roda antes disso. **No ORIG isso pega:** `painel/assets/js/funcoes.js` tem 8 `new bootstrap.Modal(...)` (`:116,124,151,159,169,178,187,196`), `.tooltip()` por jQuery (`:21`, e o mesmo no portal) e 14 escutas `show.bs.modal` por jQuery nos JS próprios.
2. **Summernote sem tooltip.** O Summernote chama `$.fn.tooltip` ao montar a barra; sob o Tabler a barra ficou vazia. Correção: `tooltip:false`; um shim foi recusado (projeto de referência, `contratos/formModelo.php:330-345`, `include_tabler/_compor_paginas.php:32-38`, `docs/DECISIONS.md:1033-1036`). **No ORIG pega no editor de modelos do histórico.**
3. **Marcação BS4 gerada no servidor.** 83 mensagens de retorno com `.close`/`data-dismiss`: o helper `AdmsMensagem` fez 94 substituições em 40 arquivos (commit `21d4e95`). Os badges devolvidos por AJAX ficaram para trás: ainda há 9 `badge-pill badge-*` em `Controllers/Liberar.php:23,38,54` e `Models/liberar/AdmsLib{Dropdown,Menu,Permissao}.php:48,56` (`grep -rn badge-pill app`). **A conversão do projeto de referência está incompleta neste ponto, e o ORIG herdou os mesmos models de "Liberar".**
4. **Plugins presos ao jQuery.** O Inputmask dispara eventos jQuery que um `addEventListener` nativo não escuta (commit `696ed52`); o Inputmask tem de vir antes do `validarForm.js`, senão um TypeError mata o resto do arquivo.
5. **Ajustes de CSS dos plugins.** O DataTables bs5 gera `form-control-sm` e o botão de exportar estica sob `.btn-group` (o CSS do projeto de referência, linhas 15-49); o Tom Select usa `--bs-*` contra `--tblr-*` e a lista ficava transparente (`head.php:31-33`, `vc-tom-select.css`).
6. **O menu.** O treeview foi reescrito no idioma do rail (títulos de seção), D-09.
7. **Cache.** O `VERSAODEV` fixo servia JS velho; passou a `filemtime` (D-45).
8. **Correções depois da conversão:** `21d4e95` (mensagens), `5f021c3` (seletor de cor sob `.form-select`), `d0e8a96` (coluna de ações indo para o Excel e o CSV), `696ed52` (Inputmask).

---

## 2. O dicionário de-para

Contagem no ORIG por `grep -rhoP` (ocorrências) e `grep -rlP` (arquivos), com o script `contar.sh` do scratchpad da sessão (não versionado). Escopos:
- **painel** = `painel/app/adms/Views` (179 arquivos .php, dos quais 9 em `include/`);
- **portal** = `portal/app/adms/Views` (39 arquivos, 7 em `include/`);
- **M+C** = Models e Controllers dos dois apps (HTML gerado no servidor);
- **JS** = `assets/escola1/*.js|css`, `painel/assets/js/{funcoes,validarForm,tables,tables_editint}.js`.

Formato: `ocorrências (arquivos)`. **Mec.** = um script resolve (substituição de texto sem olhar o contexto). **Semi** = script resolve com conferência visual. **Man.** = trabalho à mão.

Os equivalentes foram conferidos contra o Tabler vendorizado em EDU (`assets/plugins/tabler/css/tabler.min.css`, **Tabler v1.4.0**, com Bootstrap **5.3.7** embutido em `tabler.min.js`), por `grep -o "<classe>"` no CSS.

### 2.1 Estrutura da página

| AdminLTE 3 / BS4 | Tabler 1.4 / BS5 | painel | portal | troca |
|---|---|---|---|---|
| `.wrapper` (no `cabecalho.php`) | `.page` | include | include | layout (A) |
| `.content-wrapper` | `.page-wrapper` | 103 (103) | 13 (13) | Mec. |
| `section.content-header` + `h1` + `ol.breadcrumb.float-sm-right` | `.page-header` > `.page-pretitle` + `h2.page-title` (+ ações à direita) | 103 (103); `breadcrumb` 332 (95) | 13 (13); 18 (6) | **Semi**: o bloco tem formato fixo nas 116 telas; um script troca o bloco inteiro; o breadcrumb some ou vira `page-pretitle` (decisão) |
| `section.content` > `.container-fluid` | `.page-body` > `.container-xl` | 206 (103) | 26 (13) | Mec. |
| `.card`, `.card-header`, `.card-title`, `.card-body`, `.card-footer` | iguais | card 730 (110); header 61 (33); title 43 (23); body 228 (110); footer 51 (49) | 118 (19); 11 (7); 6 (5); 28 (19); 4 (4) | ficam |
| `.card-outline card-<cor>` | `card-status-top bg-<cor>` (existe) ou nada | 43 (25) | 13 (9) | Mec. (decisão de estilo) |
| `.card-tools` | `.card-actions` (existe, `card-actions`=2 no CSS) | 13 (11) | 2 (2) | Mec. |
| `.btn-tool` + `data-card-widget="collapse"` | `btn btn-ghost-secondary btn-icon` + `data-bs-toggle="collapse"` (não há widget de cartão no Tabler) | btn-tool 6 (4); widget 6 (4) | 1 (1); 1 (1) | **Man.** (o recolher de cartão do AdminLTE não existe) |
| `.row`, `.col-*` | iguais | 436 (112); 870 (112) | 38 (17); 65 (17) | ficam |
| `.form-row` | `.row g-2` | 173 (54) | 6 (3) | Mec. |
| `.info-box` (ícone + número) | sem equivalente: `card card-sm` > `row` > `span.avatar` + texto | 28 (4) | 2 (1) | **Man.** |
| `.small-box` (número grande + rodapé link) | sem equivalente: `card` + `.h1` + `card-footer` ou `card-link` | 2 (1) | 3 (1) | **Man.** (14 seletores dos vídeos usam `.small-box`, ver §8) |
| `.callout callout-<cor>` | **existe** no Tabler 1.4 (`.callout` = 2 no CSS), com outro desenho; ou `alert alert-<cor>` | 34 (16) | 2 (1) | Mec. para `alert` ou fica (decisão) |
| `direct-chat`, `timeline` | — | 0 | 0 | não usados |
| `nav-sidebar`, `nav-treeview` | rail | 4 (1) | 4 (1) | layout (menu.php) |

### 2.2 Classes do Bootstrap 4 que mudam no 5

| BS4 | BS5 | painel | portal | M+C | JS | troca |
|---|---|---|---|---|---|---|
| `ml-*`/`mr-*`/`pl-*`/`pr-*` (inclusive `-auto`) | `ms-*`/`me-*`/`ps-*`/`pe-*` | 163 (69) | 28 (11) | 14 | 0 | Mec. (mais usados: `mr-1` 96, `mr-2` 43, `ml-2` 19) |
| `float-left`/`float-right` (+ `-sm-` etc.) | `float-start`/`float-end` | 100 (97) | 7 (7) | 0 | 0 | Mec. (quase todos do `breadcrumb float-sm-right`, que some com o `page-header`) |
| `text-left`/`text-right` (+ `-md-` etc.) | `text-start`/`text-end` | 38 (31) | 11 (4) | — | — | Mec. (inclui o `text-right` de `AdmsUi` e das colunas `num`) |
| `badge-<cor>` | `bg-<cor>` (ou `bg-<cor>-lt`, como o projeto de referência) | 73 (29) | 5 (4) | **69** (9 arquivos) | 0 | Mec. (a cor de texto: `text-bg-<cor>` ou `-lt`, decisão de estilo) |
| `badge-pill` | `rounded-pill` | incluído acima (21) | — | 33 | — | Mec. |
| `form-group` | `mb-3` | 373 (71) | 8 (3) | 0 | 1 | Mec. |
| `label` sem classe dentro de `form-group` | `form-label` | não medido | não medido | — | — | Semi |
| `<select class="form-control">` | `form-select` | não medido (46 `form-control select2`) | — | — | — | Semi (o select2 decide, §3) |
| `custom-select` | `form-select` | 0 | 8 (3) | — | — | Mec. |
| `custom-control custom-checkbox/radio/switch`, `custom-control-input/label` | `form-check` (`form-switch`), `form-check-input/label` | 64 (6) | 0 | — | — | **Semi** (a estrutura muda: o `label` deixa de envolver) |
| `custom-file` | `form-control` | 0 | 0 | — | — | — |
| `input-group-append`/`-prepend` | (o wrapper some) ou `input-group-flat` | 7 (5) | 4 (3) | 1 | 0 | Semi |
| `button.close` + `&times;` | `button.btn-close` vazio | 46 (28) | 1 (1) | **318** (120 arquivos) | 0 | Mec. (318 são `class='close' data-dismiss='alert'` das mensagens em `$_SESSION['msg']`, todas idênticas: `grep -rhoE "class=.close. data-dismiss=.alert."`) |
| `sr-only` | `visually-hidden` | 0 | 0 | — | — | — |
| `no-gutters` | `g-0` | 0 | 0 | — | — | — |
| `font-weight-*` | `fw-*` | 14 (8) | 2 (2) | 2 | — | Mec. |
| `font-italic` | `fst-italic` | 1 (1) | 0 | — | — | Mec. |
| `btn-block` | `w-100` (ou `d-grid`) | 31 (18) | 16 (7) | — | — | Mec. |
| `border-left`/`border-right` | `border-start`/`border-end` | 4 (4) | 4 (3) | 1 | 1 | Mec. |
| `thead-light`/`thead-dark` | `table-light`/`table-dark` | 1 (1) | 0 | 1 | — | Mec. |
| `rounded-left/right`, `embed-responsive`, `media`, `jumbotron` | — | 0 | 0 | — | — | — |

### 2.3 Atributos

| BS4 | BS5 | painel | portal | M+C | troca |
|---|---|---|---|---|---|
| `data-toggle` | `data-bs-toggle` | 44 (21): `modal` 33, `tab` 5, `pill` 4, `dropdown` 1, `tooltip` 1 | 1 (1): `tab` | 4 | Mec. |
| `data-target` | `data-bs-target` | 33 (18) | 0 | 2 | Mec. |
| `data-dismiss` | `data-bs-dismiss` | 96 (34): `modal` 80, `alert` 16 | 0 | 318 | Mec. |
| `data-placement` | `data-bs-placement` | 7 (4) | 0 | 0 | Mec. |
| `data-parent` | `data-bs-parent` | 0 | 0 | — | — |
| `data-widget` (AdminLTE: `pushmenu`, `treeview`, `fullscreen`, `navbar-search`) | — (rail) | 5 (2) | 2 (2) | — | layout |
| `data-ttip="tooltip"` (atributo próprio, ligado em `funcoes.js:21`) | fica, muda o JS | 4 views | — | — | Man. (1 linha de JS) |

### 2.4 JavaScript

O que o bundle do Tabler expõe, lido em EDU `assets/plugins/tabler/js/tabler.min.js` (última linha e `grep -o`): o UMD grava em `globalThis.tabler` os objetos `Alert, Button, Carousel, Collapse, Dropdown, Modal, Offcanvas, Popover, ScrollSpy, Tab, Toast, Tooltip`, mais `tabler.bootstrap` e `tabler.tabler`. **Não existe `window.bootstrap`.** E o bundle **traz a ponte jQuery do BS5**: `()=>window.jQuery&&!document.body.hasAttribute("data-bs-no-jquery")?window.jQuery:null`, registrada no `DOMContentLoaded`. Pela leitura, então, com o jQuery carregado antes do `tabler.min.js` (como no projeto de referência e no P2), `$.fn.modal`/`$.fn.tooltip` passam a existir depois do `DOMContentLoaded`, e os eventos `show.bs.modal` chegam a `$(el).on(...)` com o `relatedTarget`. O que o projeto de referência viu quebrar (Summernote sem tooltip, §1.4) é código que roda **antes** do registro ou que procura `window.bootstrap`. **Isso foi lido, não executado: o piloto (§9) prova.**

| chamada | onde no ORIG | sob o Tabler | troca |
|---|---|---|---|
| `new bootstrap.Modal(el)` | `painel/assets/js/funcoes.js` (8×: `:116,124,151,159,169,178,187,196`) | `window.bootstrap` não existe: `new tabler.Modal(el)`, ou um `abrirModal()` único como o projeto de referência (`funcoes.js:47-55`) | Mec. (1 arquivo, 8 linhas) |
| `$(…).modal("show")` | `painel/assets/js/funcoes.js:436,439`; views: 0 | ponte jQuery (lido) | fica, conferir |
| `$(…).tooltip()` | `painel/assets/js/funcoes.js:21`, o mesmo no portal | ponte jQuery (lido); mas o seletor é `[data-ttip="tooltip"]` e o `AdmsUi::acao` emite `data-toggle="tooltip"` (`AdmsUi.php:63`), que **nunca foi inicializado**: hoje a dica que aparece é o `title` do navegador | Man. (decidir se a dica vira tooltip de verdade) |
| `$(el).on('show.bs.modal', fn)` + `event.relatedTarget` | 13 em `painel/assets/js/funcoes.js` (`grep -c`), 1 em `escola1-relatorios.js:32`, 1 em view | ponte jQuery (lido) | fica, conferir no piloto (`faltas/visFaltas.php`) |
| `collapsed.lte.pushmenu` / `shown.lte.pushmenu` | `assets/escola1/escola1-ui.js:11-12` | evento do AdminLTE; o rail do EDU guarda `localStorage['vc-side-mini']` sozinho (`rodape.php:86`) | o arquivo sai (A) |
| `.tab()`, `.collapse()`, `.dropdown()`, `.popover()` | 0 | — | — |
| DataTables `dom` com `col-sm-12 col-md-7` e `button: { className: 'btn' }` | `assets/escola1/escola1-listas.js:51-54` | igual sob `dataTables.bootstrap5`; os botões outline continuam | Semi (1 arquivo) |

### 2.5 Ícones

| | painel | portal | M+C | JS | banco (`conteudo.sql`) |
|---|---|---|---|---|---|
| total `fa*` | 395 (105) | 68 (20) | 49 | 23 | 75 |
| `fas` | 296 (99) | 62 (19) | 41 | 9 | 71 |
| `far` | 95 (53) | 6 (5) | 8 | 14 | 3 |
| `fab` | 4 (4) | 0 | 0 | 0 | 1 (`fab fa-elementor`) |

O ORIG carrega o **Font Awesome Free 6.5.2 inteiro** (`assets/plugins/fontawesome-free/css/all.min.css`), que aceita a sintaxe `fas fa-*` do FA5. O projeto de referência **manteve** os ícones FA. Nenhum trabalho de ícone, **desde que** o pacote completo venha junto: o EDU vendorizou **só** `fontawesome.min.css` + `solid.min.css` + `fa-solid-900` (EDU `assets/plugins/fontawesome-free/`), e com ele os 101 `far` e os 5 `fab` do ORIG somem. Troca: Mec. (vendorizar `regular` e `brands`, ou o `all.min.css` que o ORIG já tem). O ORIG ainda carrega o **Ionicons 2.0.1 por CDN** (`painel/.../include/cabecalho.php:17`) e a fonte Source Sans Pro do Google (`:15`); o uso de `ion-` nas views não foi medido; os dois saem com o layout (a D15 do EDU proíbe CDN).

### 2.6 Resumo do dicionário

- Classes e atributos **mecânicos**: cerca de 1.500 ocorrências nas views (somando as linhas Mec. das tabelas acima) mais 318 + 69 + 33 no HTML gerado pelos models (mensagens e badges), mais o bloco do `content-header` (116 telas, formato fixo).
- **Manuais**: `info-box` (4 telas), `small-box` (2), `custom-control` (6), `card-widget` (5), `input-group-append` (8), a dica do botão desabilitado (nunca inicializada).
- **A conferir no piloto**: a ponte jQuery do bundle do Tabler (14 escutas `show.bs.modal`, 2 `.modal("show")`, 1 `.tooltip()`).
- **Ficam**: `card*`, `row`/`col-*`, `btn-*`, `alert`, `table*`, `modal*`, `nav-tabs`, ícones FA.

---

## 3. Os plugins

O ORIG carrega **todos** os plugins em **toda** tela (`painel/app/adms/Views/include/cabecalho.php:15-32` e `rodapejs.php:7-30`; o portal, igual). Só quatro vêm por flag: `carregarRelatorio` (`rodapejs.php:37,43`, ligada em `Controllers/Relatorios.php:92`), `carregarOrdenacao` (`:46`, em `Matriz.php:45`), `carregarEditor` (`:50`, em `HistoricoModelos.php:71`).

Uso real, por `grep -rl` nas views e nos JS próprios (fora dos `include/`):

| plugin (versão lida no arquivo) | quem usa de fato no ORIG | build BS5 | com o Tabler do EDU | veredito |
|---|---|---|---|---|
| **jQuery 3.6.0** | todo JS próprio (`escola1-listas.js`, `validarForm.js`, `funcoes.js`, `escola1-editor.js`) | — | o EDU **não carrega jQuery** (`grep -ril jquery` vazio); o projeto de referência e o P2 carregam, antes do `tabler.min.js` (projeto de referência, `libraries_js.php:89`) | **continua necessário**: DataTables 1.x/2.x, Summernote, Select2, jQuery UI e Inputmask (modo jQuery) dependem dele |
| **DataTables 1.10.20** + bs4 | **todas as listas** por `escola1-listas.js` (classe `lista-dados`); `relatorios/relatorio.php` | **sim**: `dataTables.bootstrap5` (integração oficial). O projeto de referência e o P2 rodam em produção a **1.10.20 com a integração bs5** (P2 `assets/plugins/datatables-bs5/`) | funciona (projeto de referência, P2) | troca `datatables-bs4` → `datatables-bs5` (arquivo já existe no P2 e no projeto de referência) |
| **Buttons 2.2.2** + html5 + print + colVis | `escola1-listas.js` (Excel, CSV, PDF, Imprimir) | **sim**: `buttons.bootstrap5` (já presente no P2: `datatables-buttons/js/buttons.bootstrap5.min.js`) | funciona (projeto de referência, P2) | troca a integração; o CSS dos botões outline precisa do ajuste do projeto de referência (no CSS de lá, linhas 15-49) |
| **JSZip 3.7.1**, **pdfmake 0.2.4** | Excel e PDF das listas | independentes de framework | funcionam | ficam. O projeto de referência **não** carrega pdfmake (não tem PDF nas listas); o ORIG tem e mantém |
| Responsive / FixedHeader / FixedColumns (bs4) | carregados; uso por opção não medido (`escola1-listas.js` não liga `responsive`, `fixedHeader` nem `fixedColumns`: `grep` = 0) | existem builds bs5 | — | **saem** (o projeto de referência também não carrega) |
| **Summernote 0.8.20** (`summernote-bs4`) | 1 tela: `historicoModelos/formModelo.php` (editor de modelos) | **sim**: `summernote-bs5.min.js/.css` **já estão** em `ORIG assets/plugins/summernote/` (e o README oficial da 0.8.20 documenta o bs5) | funciona com `tooltip:false` (projeto de referência, §1.4) | troca o arquivo; o `escola1-editor.js` (141 linhas, 16 chamadas `summernote(`) precisa ser conferido |
| **Select2 4.0.12** + `select2-bootstrap4-theme` | 21 views do painel, 2 do portal (46 `form-control select2`), ligado em `validarForm.js:118,147` e em `portal/.../home/notas_atividades.php:214` | **sem tema oficial**. O tema da comunidade `select2-bootstrap-5-theme` (1.3.0 suporta Select2 4.0) está **arquivado/abandonado**. O projeto de referência carrega o Select2 **sem tema** e prefere o **Tom Select 2.6.2** (que é do próprio Tabler, sem jQuery, `tom-select.bootstrap5.min.css`) | Select2 funciona sem tema (visual destoa); Tom Select funciona (projeto de referência, P2) | **decisão 2**: Select2 sem tema, Select2 com o tema arquivado, ou Tom Select |
| **Inputmask 5.0.7** | `validarForm.js:112-129`; uso por classe: `campotelefone` e `campocelular` 9 views cada, `campocep` 1, `campocpf` 1 | independente de framework | funciona (projeto de referência, P2), **com a ordem** inputmask → validarForm | fica |
| **jQuery UI 1.13.0** (`sortable`) | 1 tela: matriz (arrastar a ordem), `escola1-ordenar.js` (45 linhas) | independente de framework | funciona (não tem tema BS) | fica; alternativa sem jQuery: SortableJS (MIT), não necessária |
| **Chart.js 2.9.4** | relatórios (`escola1-relatorios.js:8-27`, por `carregarRelatorio`) | independente de framework | funciona | fica (o Tabler traz ApexCharts nos exemplos; trocar não é necessário) |
| tempusdominus-bootstrap-4 5.39.0, daterangepicker, moment | **nenhuma tela**: só os `include/` citam (`grep -rl`) | (tempus dominus 6 é BS5) | — | **saem** |
| bootstrap4-duallistbox 4.0.2 | **nenhuma tela** | sem build BS5 mantido | — | **sai** |
| icheck-bootstrap 3.0.1 | **nenhuma tela** (só os `cabecalho*`) | — | — | **sai** |
| overlayScrollbars 1.13.0 | só o AdminLTE (barra do menu) | — | — | **sai** |
| jquery-tabledit (`rodapejs_editint.php`, `tables_editint.js`) | o lançamento de notas, `atividades/visAtividades.php` (por `renderizarEditInt`; 12 seletores `tabledit-*` nos roteiros) | não tem build BS5; o plugin gera classes do **BS3** (`btn-default`, `glyphicon`, `input-sm`) | funciona sem Bootstrap, com os botões sem estilo | **sem saída pronta**: CSS próprio para as 4 classes, ou reescrever a edição em linha (decisão 3) |
| sweetalert2, toastr, bootstrap-switch, uPlot, codemirror, dropzone etc. | **nenhuma tela** (`grep` = 0) | — | — | não vêm |
| Font Awesome 6.5.2 | todas | — | o EDU tem só a família solid | vendorizar regular e brands (§2.5) |

**Só um plugin em uso fica sem saída pronta: o jquery-tabledit (uma tela).** O outro com escolha é o Select2. O resto é trocar o arquivo de integração (DataTables, Buttons, Summernote), manter (jQuery, JSZip, pdfmake, Inputmask, jQuery UI, Chart.js) ou tirar (7 plugins carregados e não usados).

Fontes conferidas: README do Summernote 0.8.20 (jsDelivr) e a página "getting started" (bs5 oficial); npm/Packagist do `select2-bootstrap-5-theme` (arquivado, Select2 4.0 a partir da 1.3.0); `datatables.net-buttons-bs5` (cdnjs, jsDelivr); para o SweetAlert2 não há tema BS5 confirmado, mas o ORIG não o usa.

---

## 4. Os componentes comuns

Contagem de uso por `grep -rl` nas views (fora de `include/`); `Ui::` pega as três formas de chamada (`AdmsUi::x`, `Ui::x` com `use … as Ui`, `$Ui::x`).

| componente | o que emite ou de que depende em BS4/AdminLTE (ORIG) | o que muda para Tabler | telas que usam (painel / portal) |
|---|---|---|---|
| `AdmsUi::acao` | `btn btn-sm btn-acao` (`painel/app/adms/Models/helper/AdmsUi.php:64,70`); o desabilitado é `span.acao-desabilitada` com `data-toggle="tooltip"` (`:63`) | `data-bs-toggle` (1 linha); as cores `btn-outline-*` (`:25-39`) existem no Tabler | 20 / 0 |
| `AdmsUi::acaoModal` | `data-toggle="modal" data-target=` (`:90`) | **obrigatório** `data-bs-toggle`/`data-bs-target`, senão o modal não abre (1 linha) | 4 / 0 |
| `AdmsUi::acaoPost` | `form.d-inline` + `confirm()` (`:106,111`) | nada | 11 / 0 |
| `AdmsUi::barraDisciplina` | `btn-sm btn-primary/outline-*`, `no-print` (`:135-149`) | nada obrigatório | 8 / 0 |
| `AdmsUi::faixaTrava` | `alert alert-secondary`, `btn-warning` (`:163-164`) | nada obrigatório (o alerta do Tabler tem outro desenho) | 7 / 0 |
| `AdmsUi::etiqueta` | `badge badge-{c}` (`:174`) | `badge bg-{c}` ou `bg-{c}-lt` (1 linha) | 5 / 0 |
| `AdmsUi` (qualquer método) | — | **3 linhas** no arquivo | **25 / 0** |
| `escola1-listas.js` (112 linhas) | título de `.content-header h1` (`:32`); `dom` com grade BS4 (`:51`); `button.className='btn'` para anular o `btn-secondary` do `buttons.bootstrap4` (`:52-54`); depende de `dataTables.bootstrap4`, `responsive/fixedHeader/fixedColumns.bootstrap4`, `buttons.bootstrap4` (`rodapejs.php:16-29`) | `.page-title` no lugar de `.content-header h1`; integração `bootstrap5`; revisar o `className` com o `buttons.bootstrap5` | **37 / 0** (+ 1 relatório) |
| `escola1-ui.css` (127 linhas) | `.sidebar-dark-primary .nav-sidebar … .active`, `.menu-open` (`:7-19`); `.brand-link .brand-image` (`:22-30`); `div.dt-buttons`, `.dataTables_filter` (`:63-72`); `td.media` contra o `.media` do BS4 (`:80-81`); impressão com `.main-sidebar/.main-header/.main-footer/.content-wrapper` (`:103-104`); cores fixas da paleta BS4 | o bloco do menu e da marca sai (o rail tem o seu CSS); a impressão passa a `.navbar-vertical`, `.page-wrapper`; cores para `--tblr-*`. As classes próprias ficam: `acoes-linha` (23 telas), `table-compacta` (36), `num` (21 / 1), `no-print` (4 / 1) | todas |
| `escola1-ui.js` (14 linhas) | `collapsed.lte.pushmenu`/`shown.lte.pushmenu` (`:11-12`) + o script de `cabecalho.php:35-38` que alterna `sidebar-collapse` | **sai**: o rail do EDU guarda o recolhido sozinho | todas |
| `escola1-editor.js` (141 linhas) | `$.fn.summernote` da build bs4 (`:15-16,58`); a view carrega `summernote-bs4.min.css` (`historicoModelos/formModelo.php:33`) e tem abas `data-toggle="tab"` (`:89-93`) | build bs5 (já no ORIG) + `tooltip:false` (projeto de referência) | 1 / 0 |
| `escola1-ordenar.js` (45 linhas) | só jQuery UI `sortable` (`:10-26`) | nada | 1 / 0 (matriz) |
| `escola1-relatorios.js` (42 linhas) | Chart.js 2.9.4; cores BS4 fixas (`:10`); `$('#modalSalvar').on('show.bs.modal')` (`:32`) | cores; o evento pela ponte jQuery (conferir) | 1 / 0 |
| `funcoes.js` (painel 444 linhas) | 8 `new bootstrap.Modal`, 13 `show.bs.modal`, 2 `.modal("show")`, `.tooltip()`, `select2:open` | `tabler.Modal` (8 linhas); o resto pela ponte | todas |
| `validarForm.js` (painel 134, portal) | `inputmask` e `select2` com `dropdownParent` (`:112-147`) | nada (a ordem inputmask → validarForm importa) | todas |
| includes do painel (9 arquivos, ~388 linhas) | `cabecalho.php` (head + `body.sidebar-mini.sidebar-collapse.layout-fixed` + `.wrapper` + preloader), `head_menu.php` (`main-header`, `pushmenu`, uma busca `navbar-search` herdada do modelo do AdminLTE que não faz nada, `fullscreen`, o **?** da ajuda), `menu.php`, `footer.php`, `rodapejs.php`, `rodapejs_editint.php` (tabledit), `cabecalho_ajax.php`, `cabecalho_login.php`, `rodapejs_login.php`; carrega o `demo.js` do AdminLTE | `include_tabler/`: `head.php`, `rail.php` (do EDU, §6.1), `libraries_js.php` (flags), `_page_header.php`, login | todas |
| includes do portal (7 arquivos, ~378 linhas) | os mesmos, com a marca "Portal" em CSS inline (`head_menu.php:7-116`) e o `adminlte.js` não minificado | `include_tabler/` a partir do P2 | todas do portal |
| mensagens de retorno (fora das views) | `<div class='alert … alert-dismissible'><button type='button' class='close' data-dismiss='alert'>` em `$_SESSION['msg']`: **298 em 109 arquivos do painel e 20 em 11 do portal** (`grep -rlE "data-dismiss=.alert"` em Controllers e Models) | `btn-close` + `data-bs-dismiss`; um script resolve (texto idêntico), ou um helper de mensagem como o `AdmsMensagem` do projeto de referência | aparecem em quase toda tela |
| badges gerados no servidor | 69 `badge-*` em 9 models (`AdmsLib*` com `badge-pill`, `AdmsBoletimCurso.php:270,292`, `AdmsGerarBoletimPdf.php:46-47`) | `bg-*`; **o boletim muda em dois arquivos juntos** (a tela e o PDF do Dompdf, que define `.badge-success/-danger/-secondary` no próprio CSS) | boletim, Liberar |

**Quantas telas ficam convertidas só porque os componentes comuns mudam: nenhuma por inteiro.** Medido: 103 views do painel e 13 do portal têm a casca da página **dentro da própria view** (`content-wrapper` 103 (103) / 13 (13), `content-header` idem), e quase toda view tem algum `ml-`, `form-group` ou `float-` (só 7 das 140 views que contam têm zero padrões). O que os componentes fazem é tirar trabalho: `AdmsUi` cobre as ações de 25 telas, `escola1-listas.js` as listas de 37, o layout o menu e o cabeçalho de todas, e o script de mensagens os avisos de todas. Mudando os componentes, as 47 telas com 1 a 5 padrões viram "A com dicionário".

**Achados de passagem (não são da conversão, ficam registrados):**
1. `assets/escola1/escola1-ui.css:99` tem uma `}` sobrando depois do `@media (max-width: 575.98px)` das linhas 96-98. Pela gramática do CSS, o `@media print` das linhas 102-108 passa a ser descartado: a regra R1 (imprimir só o conteúdo) pode não estar valendo. Não conferido no navegador.
2. O `AdmsUi.php` do portal **não** é idêntico ao do painel, ao contrário de `docs/USABILIDADE_COMPONENTES.md:10`: faltam 3 linhas (`atestado`, `chamada`, `regra`; `diff`). E nenhum arquivo do portal usa o `AdmsUi`, nem `lista-dados` (o `escola1-listas.js` é carregado no portal sem nada para iniciar).
3. A dica do botão desabilitado nunca é inicializada (§2.4).
4. `painel/.../include/rodapejs.php:54-57` tem um comentário sobre "a ficha financeira" que não pertence a este sistema.

## 5. O inventário das telas

Critério (contagem por arquivo com um script PHP no scratchpad da sessão, `preg_match_all` da regex do dicionário, mais `<script>` inline e linhas de JS, mais marcas de dependência: `.modal(`, `.tooltip(`, `*.bs.*`, `sortable`, `Chart`, `summernote`, `<video>`, `tabledit`, `select2(`, `fetch`, `data-widget`):
- **A**: 0 padrões do dicionário e no máximo 1 linha de JS;
- **B**: tem padrões; JS nenhum ou trivial, sem dependência do BS4;
- **C**: JS inline com mais de 15 linhas, **ou** plugin/evento do BS4/jQuery, **ou** JS que escreve classe BS4, **ou** página autônoma com CSS do AdminLTE;
- **N**: não muda (guias gerados, dados, PDF, documento, e-mail).

Distribuição dos padrões nas 140 views que contam: 0 padrões = 7; 1-5 = 47; 6-15 = 56; 16 ou mais = 30. As áreas seguem o menu (`docs/PERMISSOES.md` e o grupo 9 das atualizações 1.8.0 e 1.18.3); o que não está no menu, pela pasta.

| Área | Arquivos | A | B | C | N | Linhas |
|---|---|---|---|---|---|---|
| Ensino | 42 | 0 | 38 | 4 | 0 | 5.143 |
| Educacional | 14 | 0 | 14 | 0 | 0 | 1.593 |
| Configurações | 16 | 0 | 13 | 3 | 0 | 1.837 |
| Relatórios | 3 | 0 | 2 | 1 | 0 | 406 |
| Ajuda (painel) | 60 | 0 | 8 | 1 | 51 | 5.683 |
| Equipe | 12 | 0 | 12 | 0 | 0 | 1.721 |
| Suporte técnico | 15 | 0 | 15 | 0 | 0 | 2.115 |
| Início | 1 | 0 | 1 | 0 | 0 | 92 |
| Portal | 26 | 0 | 12 | 3 | 11 | 2.471 |
| Público / login / erro | 10 | 1 | 7 | 2 | 0 | 726 |
| Impressão (views) | 3 | 1 | 2 | 0 | 0 | 268 |
| Layout (includes) | 16 | — | — | — | — | 782 (§4) |
| **Total** | **218** | **2** | **124** | **14** | **62** | **22.837** |

Na prática: **2 A** + **47 B leves** (1 a 5 padrões: convertem com o script e uma olhada) + **77 B** (6 ou mais padrões: script + conferência visual) + **14 C** + **62 N**. Fora das views, mais 13 itens N (abaixo).

### 5.1 As 14 C e o motivo

| tela | área | o que exige trabalho manual |
|---|---|---|
| `atividades/visAtividades.php` | Ensino | lançamento de notas com **jquery-tabledit** (`renderizarEditInt`, `rodapejs_editint.php`, `tables_editint.js`); o plugin gera `glyphicon`, `btn-default`, `input-sm` (classes do BS3); modal `#ValorErrado` por `.modal("show")` |
| `matriz/verMatriz.php` | Ensino | **matriz com arrastar** (jQuery UI sortable) e 40 padrões |
| `chamada/encontro.php` | Ensino | **chamada**: o JS reescreve `className` com `btn-block` |
| `faltas/visFaltas.php` | Ensino | modal "Compensar" preenchido por `show.bs.modal` + `relatedTarget`; barra da disciplina; 25 padrões |
| `historicoModelos/formModelo.php` | Configurações | **editor de modelos**: Summernote bs4, abas `data-toggle="tab"`, variáveis no cursor, 56 padrões |
| `instituicao/formInstituicao.php` | Configurações | 28 linhas de JS puro (ordem e prévia das linhas do cabeçalho); C leve |
| `regraMedia/regra.php` | Configurações | **simulador da regra de média**: 144 linhas de JS com `fetch`, que monta HTML com `badge-success`, `badge-warning`, `ml-2` |
| `relatorios/relatorio.php` | Relatórios | **gráfico** Chart.js, colVis, `#modalSalvar` por `show.bs.modal`, dropdown |
| `ajuda/_video.php` | Ajuda | **player de vídeo** (`<video>`, capítulos, `.play()`) |
| `portal ajuda/_video.php` | Portal | o mesmo player |
| `portal home/horario_aluno.php` | Portal | 45 linhas de JS da grade de horário; C leve |
| `portal home/notas_atividades.php` | Portal | 39 linhas de JS com select2 (tema bs4) |
| `verificar/verificar.php` | Público | página pública autônoma, `DOCTYPE` próprio, `adminlte.min.css`, `<style>` e 21 linhas de JS (copiar o hash, imprimir) |
| `portal acesso/login.php` | Público | login com a **sonda do iframe** (`fetch` em `login/sonda`) e o plano B |

### 5.2 Todas as telas, por área — `faixa(padrões)`

Pastas do painel relativas a `painel/app/adms/Views/`, do portal a `portal/app/adms/Views/`.

- **Ensino (42):** anosLetivos: formAno B(9), listAnos B(7) · atividades: _componentePeso B(3), cadAtividades B(21), editAtividades B(23), listAtividades B(24), uploadNotas B(9), visAtividades **C**(9) · chamada: encontro **C**(13), listChamada B(5) · cursos: _identidadeCurso B(10), cadCursos B(11), editCursos B(12), listCursos B(14), verCurso B(31) · disciplinas: cadDisciplinas B(7), camposMatriz B(11), editDisciplinas B(6), encerrarOferta B(4), gerarOferta B(15), listDisciplinas B(21), listMinhasDisciplinas B(3), verDisciplinas B(13), verPolivalente B(28) · faltas: visFaltas **C**(25) · horarioAula: listHorarioAula B(14), verHorarioAula B(3) · materias: cad B(9), edit B(10), list B(8), ver B(4) · matriz: formGrade B(6), listMatriz B(7), verMatriz **C**(40) · medias: visFinal B(6) · modulos: formModulo B(10), listModulos B(10) · regraMedia: oferta B(3) · salaAula: cad B(12), edit B(12), list B(8), ver B(4).
- **Educacional (14):** academicos: cadAcademico B(1), editAcademico B(7), editSenhaAcademico B(10), formAluno B(33), historicosAluno B(5), listAcademicos B(10), listPendenciaSexo B(5), verAcademico B(14; card-widget e abas), vinculoAluno B(27) · historicoEmissoes: emitir B(15), listEmissoes B(9), verEmissao B(6) · portalAlunos: listAlunos B(20), visAluno B(10).
- **Configurações (16):** atosLegais: formAto B(15), listAtos B(5) · configEmail: cad B(14), edit B(14), list B(7), ver B(2) · historicoModelos: formModelo **C**(56), listModelos B(4) · instituicao: formInstituicao **C**(30) · parametros: listParametros B(28) · polos: formPolo B(9), listPolos B(5) · regraMedia: aplicar B(5), lista B(8), regra **C**(46) · tentativasAcesso: listar B(6).
- **Relatórios (3):** hub B(3), parametros B(5), relatorio **C**(25).
- **Ajuda, painel (60):** ajuda: guia B(2), guia_administrador B(1), guia_professor B(1), guia_secretaria B(1), indice B(4), videos B(3), _video **C** · novidades: aviso B(4), listar B(2) · guias: `_indice` N (dados, 3.721 linhas) e 49 guias N (HTML gerado por `database/ferramentas/guias/gerar_guias.php`, só classes próprias `guia-interno`, `print-pendente`; um guia do portal caiu nesta contagem).
- **Equipe (12):** nivelAcesso: edit B(5), listNivelAcesso B(22), listPermissao B(20), ver B(2) · perfil: atualizarDados B(14), editPerfil B(24), meuPerfil B(16; card-widget) · usuarios: cad B(17), editSenha B(4), edit B(22), list B(8), ver B(17; card-widget).
- **Suporte técnico (15):** acessoAcademico: edit B(4), list B(14), ver B(3) · menu: cad B(6), edit B(7), list B(15), ver B(4) · paginas: cad B(19), edit B(19), list B(1), ver B(21) · pgAcademicos: cad B(19), edit B(20), list B(7), ver B(22).
- **Início (1):** home/home B(3; card-widget, Menu Rápido).
- **Portal (26):** ajuda: guia B(2), guia_aluno B(1), indice B(4), videos B(3), _video **C**; 10 guias e `_indice` N · atestado: atestado B(6) · home: aluno B(7), horario_aluno **C**(19), notas_atividades **C**(9) · novidades: aviso B(4), listar B(2) · perfil: editPerfil B(14), editSenhaPerfil B(6), meuPerfil B(8; abas e card-widget) · progresso: progresso B(14).
- **Público / login / erro (10):** painel acesso: login B(3), esqueceuSenha B(2), atualizaSenha B(2), definirSenha B(0; 7 linhas de JS puro, na prática A) · painel verificar/verificar **C** · portal acesso: login **C**(4), esqueceuSenha B(2), atualizaSenha B(1), definirSenha B(0; na prática A) · portal erro/pgErro **A** (8 linhas). A **sonda** não tem view: `portal/app/adms/Controllers/Login.php:20` devolve JSON (N). A tela 503 da instalação é HTML inline em `painel/core/ConfigController.php:141` (N).
- **Impressão (3):** academicos/verBoletim **A**(0), historico/previa B(9), portal home/boletim_aluno B(2). As três usam `window.print`; as tabelas do boletim saem de `AdmsBoletimCurso` (badges, §4).

### 5.3 O que NÃO muda, e por quê

| item | arquivo | motivo |
|---|---|---|
| PDF do boletim | `painel/app/adms/Models/academicos/AdmsGerarBoletimPdf.php` (81 linhas) | Dompdf com CSS inline próprio; muda **só** se o badge da tela mudar de nome (§4) |
| histórico e atestado em PDF | `Models/historico/AdmsHistoricoDocumento.php` (492), `AdmsHistoricoEditor.php` (375), `AdmsHtmlControlado.php` | Dompdf; os modelos ficam no banco; a lista branca de classes é a do documento, não a do Bootstrap |
| atestado do portal | `portal/app/adms/Models/helper/AdmsPainelPonte.php`, `portal/.../Controllers/Atestado.php` | usa o motor do painel |
| modelos de documento | banco (`rm_historico_modelo_versoes`) | HTML do documento, sem Bootstrap |
| "prévia recusada" | `Controllers/HistoricoEmissoes.php:85`, `HistoricoModelos.php:97` | HTML mínimo |
| e-mails | `Models/helper/AdmsTokenAcesso.php:136-156`, `AdmsSendPhpMailer.php` (dois apps) | `<p>` e `<a>` simples |
| guias da Ajuda (60 arquivos) | `Views/guias/*.php` | gerados por `gerar_guias.php`, só classes próprias; **mas** os prints dentro deles ficam velhos (§8) |
| sonda, 503 | §5.2 | JSON e HTML inline |

---

## 6. O menu e a marca

### 6.1 O que o rail do EDU espera

O rail **monta o menu sozinho**: `$menus = (new AdmsMenus())->itemMenu()` (EDU `app/adms/Views/include/rail.php:60`), com a consulta de EDU `app/adms/Models/AdmsMenus.php:52-63`:

```
SELECT nivpg.dropdown, men.id id_men, men.nome nome_men, men.icone icone_men,
       pg.id id_pg, pg.menu_controller, pg.menu_metodo, pg.menu_pagina, pg.nome_pagina, pg.icone icone_pg
FROM adms_nivacs_pgs nivpg
JOIN adms_menus men ON men.id=nivpg.adms_menu_id
JOIN adms_paginas pg ON pg.id=nivpg.adms_pagina_id
WHERE nivpg.adms_niveis_acesso_id=:nivel AND permissao=1 AND lib_menu=1
ORDER BY men.ordem, nivpg.ordem
```

| o que o rail usa | EDU | ORIG painel | igual? |
|---|---|---|---|
| tabelas | `adms_paginas` (`0001_estrutura_nucleo.sql:143`), `adms_menus` (`:222`), `adms_nivacs_pgs` (`:291`), `adms_niveis_acessos` (`:201`) | as **mesmas**, mesmas colunas (`schema.sql:357, 242, 256, 279`) | **sim** (as duas bases descendem do mesmo `adms`) |
| consulta | `permissao=1 AND lib_menu=1`, `ORDER BY men.ordem, nivpg.ordem` | a **mesma** (`painel/app/adms/Models/AdmsMenus.php:17-26`) | sim |
| filtros depois da consulta | nenhum | tira "modulos" se `AdmsModuloInterno::ativo()` (`:29-31`) e aplica a trava VCOSTA `AdmsNivel` (`:33-35`) | **não**: o rail precisa receber o menu já filtrado do `AdmsMenus` do ORIG (manter o model do ORIG, não o do EDU) |
| grupo | `dropdown=1` põe o item no grupo; `dropdown≠1` vira item solto de raiz (EDU `rail.php:105-124`) | **ignora `dropdown`**: agrupa por `id_men`; grupo com 1 item vira link direto, com mais vira treeview (`painel/.../include/menu.php:34-39,55-76`) | **não**. No `conteudo.sql`, das linhas com `permissao=1` e `lib_menu=1`, 40 têm `dropdown=1` e 18 têm `dropdown=2` (contagem aproximada pelo padrão `VALUES (id,perm,ordem,dropdown,lib_menu,…`, que também pega `col_nivacs_pgs`). Sob o rail do EDU, esses 18 sairiam soltos fora do grupo. Saída: mudar a regra do rail para "agrupar por `id_men`" (como o ORIG) **ou** uma migration que acerte `dropdown` |
| ícone | string FA inteira na coluna (`fas fa-school`); padrão `fas fa-circle` (`rail.php:164`) | string FA (71 `fas`, 3 `far`, 1 `fab` no `conteudo.sql`) | sim, desde que `far`/`fab` venham (§2.5) |
| item ativo | `paginaAtiva === id_pg` (`rail.php:157`); o grupo do item ativo nasce aberto (`:279-300`) | **rota da URL** contra `menu_controller/menu_metodo`, e `paginaAtiva` só se não casar (`menu.php:46-54`; "menu ativo pela rota", 1.18.1) | **não**: portar a regra do ORIG para o rail |
| alça do usuário | itens do grupo `AdmsMenus::GRUPO_DA_ALCA = 3` vão para o rodapé (`AdmsMenus.php:42`, `rail.php:99`); avatar com a inicial, nome e perfil (`:350-361`); sem foto | o grupo 3 do ORIG **é** "Sair" (`conteudo.sql:154-159`) | coincide; o resto da alça (Meu perfil) depende de quais páginas o ORIG põe no grupo 3 (não medido) |
| "Sair" | único item em código, `login/logout` (`rail.php:342-347`) | vem do banco (grupo 3) | conferir duplicidade |
| colapso | `localStorage['vc-side-mini']` (`rodape.php:86`, `rail.php:389`); grupos não lembrados | `localStorage['escola1.menu']` (`escola1-ui.js:8`), aberto ≥ 1200 px | o do rail substitui (D-262 precisa ser revista: o rail recolhe só a partir de 992 px) |
| marca | `assets/marca/vcosta.{svg,png,webp}` ou as letras "VC" (`rail.php:139-147,221-238`); o nome é o literal `vcostaedu` (`:237`) | brasão `assets/marca/vcosta.png` sobre placa branca (`escola1-placa`), logo da instituição dos Dados da instituição | o arquivo `assets/marca/vcosta.png` existe nos dois; o logo da instituição (que o ORIG mostra no login e nos documentos) não tem lugar no rail do EDU |
| outros dados do rail | `AdmsVersoes::vigente()` (`:149`), `AdmsAvisos::paraMim()` (`:416`), `AdmsNovidades::naoLidaPor()` (`:440`), `AdmsMensagem::ler()` (`:455`); sessão `colUsuario_nome`, `colUsuario_nivel_nome`, `colUsuario_id`, `auditoria_falhou` (`:150-153`) | `AdmsVersao::numero()` (footer), novidades por `col_novidades_vistas`, `$_SESSION['msg']`; a sessão **não tem** `colUsuario_nivel_nome` (`AdmsLogin.php:65-71`) | **não**: 4 chamadas a trocar por equivalentes do ORIG e uma chave de sessão a gravar no login |
| ajuda da tela | — | `AdmsCentralGuias::guiaDaTela` no `head_menu.php:17-20` (o ícone **?**) | precisa de lugar no `page-header` |
| lista de itens em código | só "Sair" | nenhuma | — |

**O que falta para o menu do ORIG ser desenhado pelo rail, sem lista em código:** (1) o rail lê o `AdmsMenus` do ORIG (com os dois filtros); (2) a regra de grupo passa a ser por `id_men` (ou uma migration acerta `dropdown`); (3) o item ativo pela rota; (4) as 4 chamadas de versão/avisos/novidades/mensagem trocadas pelas do ORIG; (5) `colUsuario_nivel_nome` gravado no login; (6) FA regular e brands; (7) lugar para o ícone de ajuda da tela e para o logo da instituição. Tudo isso é um arquivo (`rail.php`, 462 linhas) mais o login. **Nenhuma tabela nova.**

### 6.2 Os 9 níveis

`conteudo.sql:1250-1257, 2152-2153`: 1 Super Administrador, 2 Administrador, 3 Diretoria, 4 Secretaria, 5 Coordenação, 6 Professor, 7 Supervisor VCOSTA, 8 VCOSTA, 9 Suporte técnico. Grupos do painel: 1 Dashboard, 2 Usuários, 3 Sair, 4 Configurações, 5 Educacional, 7 Ensino (`:154-159`), 8 Ajuda (`:1793`), 9 Suporte técnico (`:2154-2155`), 10 Relatórios (`:2715-2716`). O rail é indiferente ao número de níveis: lê o nível da sessão. O EDU tem 5 perfis fixos (D19); isso só importa no modo Y (§7).

### 6.3 O portal do aluno

- **Menu próprio, do banco:** `portal/app/adms/Models/AdmsMenus.php:17-26`, a mesma consulta sobre `col_nivacs_pgs`, `col_menus`, `col_paginas` com `academico_nivel`. Grupos: 1 Página Inicial, 2 Acadêmico, 3 Financeiro, 4 Atendimento, 5 Sair (`conteudo.sql:1380-1384`). `portal/.../include/menu.php` é idêntico ao do painel. **O grupo da alça no portal é o 5**, não o 3.
- **Já existe um rail de portal em Tabler:** P2 `portal/app/adms/Views/include_tabler/rail.php` (612 linhas) lê o mesmo `AdmsMenus` sobre `col_*` (`:59`), põe "Sair" (pela rota `login/logout`) na alça (`:113-119, :439-442`), marca VCOSTA + logo da instituição de origem (`:213-220`), recolher (`#vcSideRecolher`, `:237`), toggler móvel (`:249-256`). O `ConfigView` do P2 tem `renderizar('tabler')` com fallback para `include/` (`portal/core/ConfigView.php:18-19,65-70,115-138`). **É o molde direto do portal do ORIG.** Atenção: o `head.php` do P2 traz Barlow do Google (`:110-112`) e o nome da escola-cliente no `<title>` (`:45`): os dois não podem vir.
- **Dentro do Moodle (iframe):** o que existe no ORIG:
  - `ORIGENS_PERMITIDAS_IFRAME` (vazio por padrão) e `COOKIE_PARTICIONADO` (`portal/core/Config.php:38,43`); `origensIframe()` só `https://host[:porta]` (`portal/core/Seguranca.php:38-49`);
  - cookie `SameSite=None` com iframe, senão `Lax`; `Partitioned` injetado por `header_register_callback` (`:102-125`);
  - `frame-ancestors 'self' <origens>` no portal, `'none'` no painel (`:129-137`);
  - a sonda `login/sonda` no login (`portal/app/adms/Views/acesso/login.php:126-134`, `Controllers/Login.php:21-31`) e o plano B "Abrir o portal em nova aba" (`login.php:86-92`).
  - **No layout, nada muda no iframe**: nenhum código esconde menu ou cabeçalho (`body.iframe-mode` só existe no CSS do AdminLTE e não é usado).

  **O que o layout Tabler precisa respeitar ali:** (a) nada de recurso externo (fonte do Google, CDN), porque o portal pode rodar em rede fechada e dentro de uma página HTTPS de terceiro; (b) o `tabler-theme.min.js` e o recolher do rail usam `localStorage`, que no iframe pode ser **particionado ou bloqueado**: todo acesso em try/catch (o rail do EDU já faz? não medido); (c) o iframe do Moodle costuma ter menos de 992 px: o rail cai no modo móvel (toggler) e precisa funcionar ali sem cobrir a tela; (d) os ids `#cartaoLogin` e `#planoB` e o script da sonda têm de sobreviver à troca do login; (e) nenhum `target=_top`; links externos com `target=_blank rel=noopener`, como hoje.

---

## 7. Os dois esqueletos

### 7.1 A tabela (formato da seção 5 do `docs/LEVANTAMENTO_RECORTE.md` do ORIG)

| Aspecto | ORIG (o sistema de origem) | EDU (`vcostaedu`) |
|---|---|---|
| Guarda de arquivo | `Un1MvC05tA` no painel (`painel/index.php:5`), `ColMvC05tA` no portal (`portal/index.php:5`); **545 arquivos** a conferem (`grep -rlE "Un1MvC05tA\|ColMvC05tA"`) | `VCOSTAEDU_APP` (`index.php:60`), fonte única em `instalar/gerador.php:29`; o teste faz redirect + `die` (`core/ConfigController.php:5-8`) |
| `session_start` | `Seguranca::iniciar()`: nomes `ESCOLA1_PAINEL` / `ESCOLA1_PORTAL` (`portal/core/Seguranca.php:91,126`), cookies HttpOnly/Secure/SameSite, `Partitioned` | `session_start()` sem nome (`PHPSESSID`) + `ob_start()` (`index.php:54`); o instalador tem sessão própria `vcostaedu_instalador` (`instalar/comum.php:42`) |
| Chaves de sessão | painel `colUsuario_id/nome/email/imagem/nivel/situacao/ordem` (`painel/.../acesso/AdmsLogin.php:65-71`); portal `academico_*` (`portal/.../AdmsLogin.php:70-76`) + `sonda_iframe`; `msg` (686 usos); 964 usos de `$_SESSION[` em 284 arquivos | `colUsuario_id/nome/usuario/nivel/nivel_nome/situacao` (`Models/acesso/AdmsLogin.php:138-143`), `session_regenerate_id(true)` (`:136`); `mensagem_de_retorno`, `auditoria_falhou` |
| Controller | sem classe base (nenhum `extends` nos 49 controllers); `private $pagina = N; $dados; $dadosId; $dadosForm` (`painel/.../Controllers/Cursos.php:12-15`); o controller chama `AdmsMenus` e passa `menus` | sem classe base; `private array $dados`, `paginaAtiva` cravado, métodos com `string $parametro = ''` (`Controllers/Home.php:27-48`); o rail busca o menu |
| Menu | `AdmsMenus::itemMenu()` + 2 filtros; `menu.php` AdminLTE, grupo por `id_men` | `AdmsMenus::itemMenu()` sem filtros; `rail.php` Tabler, grupo por `dropdown` (§6.1) |
| Leitura | `AdmsRead::exeRead($tabela, $termos = null, $parseString = null)` (`AdmsRead.php:26`), `fullRead($query, $parseString = null)` (`:35`), `getResultado()` | `AdmsRead::exeRead($tabela, ?$termos, ?$parseString)` (`:45`), `fullRead(string, ?string)` (`:55`), mais `falhou()`/`ultimoErro()` |
| Criar/Atualizar | `AdmsCreate::exeCreate($table, array $data)` (`:27`), `AdmsUpdate::exeUpdate($table, array $data, $termos, $parseString)` (`:29`) | `exeCreate(string, array)` (`:66`), `exeUpdate($tabela, array, $termos, ?$parseString)` (`:74`) |
| Apagar | `AdmsDeletar::exeDelete($table, $termos, $parseString)` (`:28`) | `AdmsDeletar::exeDelete($tabela, $termos, ?$parseString)` (`:59`) |
| Uso dos helpers | 635 chamadas em 211 arquivos (`grep -rhoE 'exeRead\|fullRead\|exeCreate\|exeUpdate\|exeDelete'`) | — |
| Transação | `core/Transacao.php`: `executar(callable)` (`:49`), `falhar`, `exigir`, `pdo()`, `ativa()`; os helpers usam o PDO dela quando aberta (`AdmsConn.php:15-35`) | `AdmsConn::iniciarTransacao()` (`:127`), `confirmarTransacao()` (`:140`), `desfazerTransacao()` (`:172`), `transacao(callable)` (`:203`) |
| Log | `AdmsSalvarLog::salvarLogs($pagina, $codigo, $acao, $descricao)` → `adms_logs` com `Seguranca::ipCliente()` (`AdmsSalvarLog.php:24-39`); o portal tem `AdmsSalvarLogs` | `AdmsSalvarLog::salvarLogs(int $pagina, int $codigo, string $descricao, ?int $usuario)` (`:67`) → `adms_logs` (`0005_adms_logs.sql:37`) |
| E-mail | `AdmsSendPhpMailer::enviarEmail(array $dados, $optionConfEmail)` (`:28`), PHPMailer em `painel/vendor` | **não existe** |
| Conexão | `abstract AdmsConn::connect()` (PDO da transação ou novo, utf8mb4, erro vira `die`) | `AdmsConn::conexao(): PDO` estático e único por requisição (`:82`), 503 se faltar constante, `ERRMODE_EXCEPTION` |
| ConfigController | `extends Config`; `?url=` → controller/método/parâmetro (`painel/core/ConfigController.php:30-78`); `carregar()` → `AdmsPaginas::listarPaginas()` → `\App\{tipo}\Controllers\X` (`:93-109`); sem página: `voltarAoPadrao()`, 503 na segunda falha (`:130-145`) | `?url=` em lista de permissão (`:104`), kebab → Pascal (`:85-94`), `AdmsPaginas::listarPaginas()` (`:114`), namespace por `adms_tps_pgs.tipo` (`:125`), fallback Login/acesso, 500 na segunda falha (`:150-166`) |
| Portão de rota | `AdmsPaginas.php:16-45`: `AdmsAtualizarDados::portao()` (`:24`), `lib_pub=1 OR permissao=1` (`:26-33`), exceção da tela de atualização (`:35-39`), `AdmsNivel::podeController` (`:41-43`); portal sem portão | `AdmsPaginas.php:54-68`: `lib_pub=1 OR permissao=1`; exceção `Atualizar` para o perfil 1 (`:72,113-121`) |
| Sessão conferida a cada requisição | **não**: a situação e o nível não são relidos; usuário desativado continua até sair; sem `session_regenerate_id` no login, logout só faz `unset` (`painel/.../Controllers/Login.php:51`) | **sim**: `AdmsPaginas::sessaoAindaVale()` (`:149-177`) relê `adms_usuarios` (situação 1 e mesmo perfil) e apaga as chaves se falhar (`:187-198`) |
| ConfigView | `renderizarLogin`, `renderizar` (cabecalho, head_menu, menu, view, footer, rodapejs), `renderizarEditInt`, `renderizarAjax` (`painel/core/ConfigView.php:21-60`); 107 chamadas a `ConfigView(` | `renderizar()`: head, rail, view, rodape (`core/ConfigView.php:49-55`); `renderizarLogin()` (`:64-69`); partial ausente vira 500 |
| Layout | AdminLTE 3.2, BS 4.6.1, todos os plugins sempre | Tabler 1.4.0, BS 5.3.7, sem jQuery, sem plugins |
| RBAC | `adms_paginas`, `adms_menus`, `adms_nivacs_pgs`, `adms_niveis_acessos`, `adms_tps_pgs` (`schema.sql:242-463`) + o espelho `col_*` do portal (`:573-628`); 9 níveis + `AdmsNivel` (`VCOSTA=[7,8,9]`, `ADMINISTRADORES=[1,2]`) + `AdmsHierarquia` | as mesmas `adms_*` (`0001:65-291`), 5 perfis fixos (D19), Administrador = perfil 1 |
| Prefixos | 88 tabelas: `rm_` 44, `adms_` 25, `col_` 12, `uni_` 5, `seg_` 1, `portal_` 1 | 33 tabelas: `adms_` 17 (+ `adms_migrations`), `edu_` 16 (D31) |
| Rotas | duas aplicações (`painel/`, `portal/`), cada uma com `core/` e `vendor/` próprios; `core/` comum (Transacao, Requisicao, MotorMedia) | aplicação única `app/adms/`, autoload PSR-4 próprio (`core/autoload.php:28-47`) |
| Instalação | `database/schema.sql` + `conteudo.sql` + `ferramentas/implantacao/instalar_banco.php` + `criar_admin.php`; 25 SQL em `database/atualizacoes/` aplicados à mão | instalador web de 7 telas (`instalar/`, 2.611 linhas), runner `AdmsMigrations` (499 linhas, `GET_LOCK`, `adms_migrations` com hash), CLI `bin/migrar.php`, atualizador web (`Controllers/Atualizar.php` + `Models/AdmsAtualizacao.php`) |
| Tamanho | painel: 43 controllers (6.029 linhas), 224 models (23.118), 179 views (19.342); portal: 6 / 41 / 39 (730 / 3.611 / 3.277); `core/` comum 3 (904) | 20 controllers (2.487), 28 models (8.343), 38 views (4.048), `core` 5 (434), 21 migrations (3.152 linhas) |

Evidências: código das duas pastas, lido nesta sessão; contagens com `find … -name '*.php' | wc -l` e `grep` indicados.

**Veredito:**
- os dois descendem do mesmo esqueleto `adms`: **mesmos nomes de helpers, mesmas tabelas RBAC, mesmo roteamento por `adms_paginas`, mesmas chaves `colUsuario_*`**. O que difere é a guarda, a conexão/transação, a sessão (o EDU é mais rigoroso) e a camada de layout;
- o EDU tem o que o ORIG não tem: instalador, runner de migrations com trava, atualizador e sessão conferida a cada requisição;
- o ORIG tem o que o EDU não tem: o acadêmico inteiro (88 tabelas, 49 controllers, 265 models, 218 views), o portal, o e-mail, o motor de média, a segurança de iframe, 9 níveis com travas.

### 7.2 Os dois modos de montar esta pasta (medidos, sem escolher)

**(X) Começa com o código do ORIG; troca o layout para Tabler; traz o rail, a marca, o instalador e o runner do EDU.**

| trabalho | tipo | arquivos |
|---|---|---|
| copiar o ORIG sem o que é da instituição de origem (base, logo, retornos, `graphify-out`, dados de prova) | higiene | toda a árvore; a conferência é por lista |
| `ConfigView` com `renderizar('tabler')` (painel e portal), como o projeto de referência e o P2 | reescrita pequena | 2 |
| `include_tabler/` do painel: `head.php`, `rail.php` (do EDU, adaptado como em §6.1), `libraries_js.php`, login | reescrita | ~6 |
| `include_tabler/` do portal: a partir do rail do P2 | reescrita | ~5 |
| componentes comuns (§4) | reescrita pequena | 7 |
| views (§5) | de-para | 140 (2 A, 124 B, 14 C); 62 N não mudam |
| mensagens geradas no servidor (318 `close`/`data-dismiss`, 69 + 33 badges) | mecânica | ~120 |
| instalador do EDU: trocar o template do Config e a guarda (`instalar/gerador.php:29`), apontar o runner para uma migration de base feita do `schema.sql` + `conteudo.sql` | adaptação | 7 + 1 runner + 1 migration de base |
| atualizador do EDU: trocar `ConfigView`, `AdmsSalvarLog` (assinatura diferente), as chaves, a exceção do perfil 1 | adaptação | 3 |
| os 25 SQL de `database/atualizacoes/` viram histórico (a base é a v1.18.4) | decisão | 0 |
| sessão conferida a cada requisição (`sessaoAindaVale` do EDU) dentro do `AdmsPaginas` do ORIG | enxerto | 1-2 |

- **Mecânico em todo arquivo:** nada fora das views e das mensagens. A guarda, os helpers, as chaves e as tabelas ficam como estão.
- **Ganha:** o acadêmico intacto e validado (régua de ouro, 18 retratos, jornada), o portal, o e-mail, a segurança do iframe; instalador + runner + atualizador + sessão conferida vêm por enxerto.
- **Perde:** a guarda `VCOSTAEDU_APP` e a aplicação única (ficam duas apps); as tabelas não seguem o `edu_` do EDU (D31); a matrícula do EDU (pessoas, responsáveis, importação por CSV com desfazer) fica de fora ou vem depois como módulo.
- **Tamanho:** ~20 arquivos de infraestrutura reescritos + 140 views + ~120 arquivos com mensagens (mecânico) + 16 includes substituídos. É o modo que o `PLANO_PALESTRA_26NOV_VCOSTAEDU.md` desta pasta chama de "caminho B", com uma diferença: lá a D15 (Tabler) ficava suspensa; aqui ela entra.

**(Y) Começa com o esqueleto do EDU e recebe os módulos do ORIG.**

| trabalho | tipo | arquivos |
|---|---|---|
| guarda: `Un1MvC05tA`/`ColMvC05tA` → `VCOSTAEDU_APP` | mecânica | 545 |
| conexão e transação: `connect()` → `AdmsConn::conexao()`; `Core\Transacao::executar` → `AdmsConn::transacao` (ou trazer o `Transacao.php`) | semi-mecânica | os 211 arquivos que usam os helpers (635 chamadas: as assinaturas coincidem, a base muda) + os que usam `Transacao` (não medido) |
| `AdmsSalvarLog::salvarLogs` com 4 parâmetros → 3 + usuário | mecânica | não medido (todas as chamadas do ORIG) |
| sessão: `msg` → `mensagem_de_retorno` (686 usos), `colUsuario_email/imagem/ordem` que o EDU não grava | mecânica | ~284 arquivos com `$_SESSION[` |
| tabelas: `rm_`/`uni_`/`col_`/`seg_` → `edu_` (D31) **ou** manter os prefixos | reescrita de SQL **ou** decisão | 88 tabelas; todo SQL dos 265 models |
| sobreposição de domínio: `edu_cursos`, `edu_matriculas`, `edu_pessoas`, `edu_anos_letivos`, `edu_periodos`, `edu_series` do EDU contra `rm_cursos`, `rm_matriculas_curso`, `adms_usuarios`/`col_usuarios`, `rm_cursos_periodos` do ORIG | **reescrita** (dois modelos de matrícula: escola básica no EDU, curso/grade/ano no ORIG) | não medido; afeta matrícula, vínculo, enturmação, boletim, histórico |
| portal: o EDU é aplicação única com 5 perfis (D19, sem professor); o ORIG tem o portal como segunda app com RBAC `col_*` e 9 níveis | **reescrita** (juntar as duas apps, ou trazer o portal como segunda app ao lado) | 6 + 41 + 39 do portal + RBAC |
| e-mail (PHPMailer), `Seguranca` (iframe, cookies, tentativas), `Requisicao`, `MotorMedia` | trazer | ~6 |
| as views (§5) | de-para (o mesmo de X) | 140 |
| RBAC: 9 níveis e as travas (`AdmsNivel`, `AdmsHierarquia`) contra os 5 perfis do EDU | reescrita das sementes e das travas | migrations + 2 helpers |

- **Ganha:** guarda única, aplicação única, prefixos `edu_`, o instalador/runner/atualizador **sem adaptação**, sessão conferida já no lugar, o histórico de decisões D1–D35 do EDU continua valendo.
- **Perde:** a validação do ORIG (régua de ouro, 18 retratos, jornada, 54 vídeos) deixa de valer sobre código reescrito; a reconciliação dos dois modelos de matrícula é trabalho de produto, não de conversão.
- **Tamanho:** as mesmas 140 views, mais ~545 arquivos com a guarda (mecânico), mais a reescrita do SQL de 265 models se os prefixos mudarem, mais a fusão do portal e da matrícula. Em número de arquivos tocados, **Y é 2 a 3 vezes X**; em risco, a diferença é maior, porque a parte de Y que não é mecânica é a parte validada pela instituição de origem.

O que só um dos dois tem, e o outro precisa trazer:

| peça | X (base ORIG) | Y (base EDU) |
|---|---|---|
| instalador de 7 telas | enxerto: gerador do Config + guarda + migration de base | já está |
| runner de migrations | enxerto: 1 arquivo, só constantes (`AdmsMigrations.php:106-130`) | já está |
| atualizador (tela + CLI) | adaptação de 3 arquivos | já está |
| sessão conferida a cada requisição | enxerto em `AdmsPaginas` (1-2 arquivos) | já está |
| e-mail, iframe, tentativas, motor de média, 9 níveis | já está | trazer |
| matrícula do EDU (importação por CSV com de-para e desfazer) | módulo depois | já está, mas no modelo de escola básica |

---

## 8. O que depende do HTML atual

Lido sem rodar nada. Seletores extraídos por script (primeiro argumento-texto de `destacar|clicar|clicarENavegar|digitar|escolher|rolar|locator|$|$$|$eval|waitForSelector|fill|click|querySelector…`).

| grupo | arquivos | linhas | seletores | AdminLTE | BS4→5 | Bootstrap genérico | estáveis (id, name, href, data-* próprio) |
|---|---|---|---|---|---|---|---|
| vídeos, núcleo (`tests/videos/gravar.js`, `conferir.js`, `lib.js`, `banco.php`, `tests/comum/visivel.js`) | 5 | 694 | 11 | 0 | 0 | 1 | 10 |
| roteiros (`tests/videos/roteiros/*.js`) | 54 | 3.415 | 1.116 | 38 | ~10 | 69 | 695 |
| jornada (`tests/jornada/*.js`) | 15 | 1.181 | 164 | 4 | 0 | 2 | 84 |
| guias (`database/ferramentas/guias/capturar.js`, `capturar.php`, `gerar_guias.php`) | 3 | 608 | 13 | 0 (+1 em JS) | 0 | 1 | 10 |
| fumaça (`database/ferramentas/implantacao/fumaca.php`, `_base.php`) | 2 | 264 | 1 XPath `//aside//a[@href] \| //nav//a[@href]` (`fumaca.php:49`) | 0 | 0 | 0 | `name="SendLogin"`, `id='loginFalha'` |
| provas Playwright (`tests/videos/provar_menu.js`, `provar_player.js`) | 2 | 140 | 31 | 4 | 0 | 0 | 20 |
| **total** | **81** | **6.302** | **1.335** | **46** | **~10** | **73** | **819** |

(Ainda: 210 seletores de classe própria, que ficam; 159 estruturais do tipo `#tabelaX tbody tr[data-rm] td`, que ficam se os ids ficarem; 69 por texto. `docs/provas/` só tem 180 PNG e `.txt`, sem script.)

- **Quebra certa:** ~56 seletores (4%) em 26 dos 54 roteiros e 4 blocos da jornada. As classes: `nav-sidebar` 22, `small-box` + `small-box-footer` 20, `nav-item` 13 (em `aside .nav-sidebar > li.nav-item > a`), `content-wrapper` 9, `callout-info` 3, `badge-*` 3, `pushmenu` 2, `user-panel`, `card-tools`, `breadcrumb`.
- **Ponto único de falha:** `alerta()` em `tests/comum/visivel.js:72` lê `.alert` e é chamado 82 vezes nos roteiros e 47 na jornada. O Tabler mantém `.alert`: fica, se as mensagens continuarem `.alert`.
- **DataTables:** nenhum seletor usa `dataTables_filter`; `dt-buttons`/`buttons-*` continuam. Risco baixo.
- **Fumaça:** imune (o rail do EDU é `aside`).

**Custo de ajuste (horas de pessoa):** mecânico 4 a 6 h (um helper `grupoMenu(nome)` no lugar de `aside .nav-sidebar > li.nav-item > a` em 10 roteiros e `bloco01.js:18-26`; um `data-indicador` próprio no lugar de `.small-box` nos 8 roteiros `relatorios-*` e `bloco11.js:18-20`; `content-wrapper`, `callout-info`, `badge-*`, `data-target`; `provar_menu.js:24-38`; `select2-*`). Manual 6 a 10 h (abrir o grupo do menu antes do item em `meu-progresso.js:11`, `notas-das-atividades.js:12`, `ler-o-boletim.js:10-18`, `entrar-no-painel.js:30-39,54`; `bloco01.js:18` lê `a p` e `childNodes[0]`; `capturar.js:27` força `body.sidebar-collapse/open`; `relatorios-visao-geral.js:20`; `inativar-curso-ou-materia.js:31`; revisar legendas que falam de posição, "menu lateral", "lápis": 3 a 4 h). Depuração até cada roteiro passar o `confere()`: 4 a 6 h. **Total 17 a 26 h de pessoa**, fora as views.

**Tempo de máquina:**

| | valor | fonte |
|---|---|---|
| filme dos 54 vídeos | 3.357,7 s = **56,0 min** (61 MB; mediana 57,9 s; máx. 100,2 s) | `docs/guia/videos.json`; 55,2 min pelas legendas `.vtt` |
| lote completo, relógio | **≈ 63 min** (≈ 1,17 × o filme: retrato e restauro do banco, login, ffmpeg) | `tests/videos/saida/gravacao_final.log` e mtimes |
| declarado | "regravar todos, cerca de 80 min, um comando" | `docs/retornos/1.18.1.md:125` |
| regravações no dia da 1.18.x | 30 regravações, +56% de filme | `gravacao_1181.log`, `gravacao_1182.log`, `ultima_gravacao.json` |
| prints | 64 chamadas no capturador, 65 PNG (22 administrador, 25 secretaria, 12 professor, 6 aluno); a última captura levou **≈ 90 s** | `capturar.js`, mtimes de `docs/guia/img/` |
| `gerar_guias.php` (PDF por perfil) | não medido; o HTML que ele emite só usa classes próprias | — |

**Estimativa para a troca:** ensaio 30-40 min + gravação completa 65-80 min + regravações 35-80 min = **2,5 a 3,5 h de máquina** para os vídeos; **≈ 10 min** para os prints (3 a 4 rodadas). As 17-26 h de pessoa vêm antes. O ponto: os seletores quebrados são poucos; o custo dominante é **visual**, porque todos os 54 vídeos e 65 prints ficam velhos, mesmo os que não precisam de ajuste de seletor. E eles só podem ser refeitos depois de **todas** as telas convertidas.

---

## 9. O piloto

Quatro telas do painel que, convertidas primeiro (depois da camada de layout e dos componentes comuns), provam o de-para inteiro:

| # | pedido | tela | o que tem (medido) | o que prova |
|---|---|---|---|---|
| 1 | lista com exportação | `disciplinas/listDisciplinas.php` (190 linhas, B(21)) | `lista-dados` (2), `AdmsUi` (4), select2 (2), `form-group` (4), modal | `escola1-listas.js` com `dataTables.bootstrap5` + `buttons.bootstrap5` + JSZip + pdfmake (Excel, CSV, PDF, Imprimir com os botões outline); o título pelo `.page-title`; o `page-header`; `AdmsUi::acao` e o desabilitado com a razão; o filtro com select2 sob o Tabler. É a tela "Todas as Disciplinas", a mais aberta do dia a dia |
| 2 | formulário com modal | `cursos/verCurso.php` (270 linhas, B(31)) | `form-group` (5), select2 (3), modal por `data-toggle="modal"`, 31 padrões | o dicionário de formulário (`form-group` → `mb-3`, `form-row` → `row g-2`, `form-label`, `form-select`, `custom-*`), `data-bs-*`, `btn-close`, a mensagem de retorno gerada no servidor depois do salvar, o select2 dentro de modal (`dropdownParent`, `validarForm.js:147`) |
| 3 | tela da disciplina com a barra única | `faltas/visFaltas.php` (168 linhas, C(25)) | `barraDisciplina` (1), `AdmsUi` (8), `lista-dados` (2), modal "Compensar" preenchido por `show.bs.modal` + `relatedTarget` | `barraDisciplina` e `faixaTrava`; **a ponte jQuery do bundle do Tabler** (§2.4): se o modal "Compensar" abre preenchido, as 14 escutas `show.bs.modal` do `funcoes.js` ficam como estão; se não, elas viram `addEventListener` (1 arquivo) |
| 4 | a mais difícil da faixa C | `historicoModelos/formModelo.php` (C(56)) | Summernote (`carregarEditor`), `escola1-editor.js` (141 linhas), abas, painel de variáveis, prévia em PDF ao lado | a build `summernote-bs5` (já vendorizada no ORIG), o `tooltip:false` que o projeto de referência precisou, as abas `data-bs-toggle="tab"`, a inserção no cursor, e que o PDF de prévia (Dompdf) não muda. É a tela com mais padrões e o único plugin pesado; se ela passa, sobram C de JS próprio, não de plugin |

Por que estas: juntas, elas passam por **todos** os componentes comuns (`AdmsUi` inteiro, listas, barra, faixa, editor), pelos três plugins que trocam de arquivo (DataTables, Buttons, Summernote), pelo Select2 (a decisão 2 fica visível), pela única incógnita técnica (a ponte jQuery) e pelo HTML gerado no servidor. As quatro são da secretaria ou do administrador, que estão nos roteiros dos vídeos.

Alternativa para a #4, se o editor não for prioridade: `regraMedia/regra.php` (o simulador, 144 linhas de JS que montam `badge-*` e `ml-2` em texto: prova a parte "JS que escreve classe BS4", que não tem script que resolva).

Fora dos quatro, o portal tem o seu próprio piloto natural: `portal acesso/login.php` (sonda do iframe) + `home/aluno.php` + `home/boletim_aluno.php`, se o portal converter junto (decisão 4).

## 10. A ordem e o tamanho

"Sessão" = uma sessão de trabalho do agente como as que fizeram as versões do ORIG (um prompt, um retorno, um commit ou poucos). Referência medida: o projeto de referência converteu 25 views + a infraestrutura em 5 commits de 2 dias. Os números abaixo são **estimativas** a partir das contagens das seções 2 a 8, para o **modo X**; o modo Y está no fim.

| onda | o que vai junto | depende de | arquivos | sessões |
|---|---|---|---|---|
| **0. Montar a pasta** | cópia do ORIG sem o que é da instituição de origem (base, logo, retornos, dados de prova, `graphify-out`, prompts); licença AGPL; a base 1.18.4 vira a migration inicial (de `schema.sql` + `conteudo.sql`); o instalador, o runner e o CLI do EDU enxertados; a sessão conferida a cada requisição; o banco de teste instalado do zero e conferido | decisão 1 | ~15 escritos; a cópia por lista | 2 |
| **1. Camada Tabler do painel** | `ConfigView` com `renderizar('tabler')`; `include_tabler/` (head, rail adaptado, `libraries_js` com flags, `_page_header` com o **?** da ajuda, login); vendorizar `datatables-bs5`, `buttons.bootstrap5`, FA regular e brands; tirar 7 plugins sem uso; os componentes comuns (§4: `AdmsUi` 3 linhas, `escola1-listas.js`, `escola1-ui.css`, `escola1-ui.js` sai, `funcoes.js` 8 linhas); o script das mensagens e dos badges (120 + 9 arquivos) | 0 | ~20 escritos + ~130 mecânicos | 1-2 |
| **2. Piloto** | as 4 telas da §9; a prova da ponte jQuery; a decisão do Select2 aplicada numa tela | 1 | 4 | 1 |
| **3. Script nas B do painel** | o dicionário (§2) rodado por área, com conferência visual de cada tela: Ensino + Educacional (56 arquivos) numa sessão; Configurações, Relatórios, Equipe, Suporte técnico, Ajuda e Início (os outros ~57) noutra | 2 | ~113 | 2 |
| **4. As C do painel** | as 8 que sobram depois do piloto: `visAtividades` (tabledit, decisão 3), `verMatriz` (arrastar), `encontro` (chamada), `formInstituicao`, `regra` (simulador), `relatorio` (gráfico), `ajuda/_video`, `verificar` (pública) | 3 | 8 | 2 |
| **5. Portal** | `include_tabler/` do portal a partir do rail do P2 (`col_*`, alça no grupo 5, sem fonte externa, `localStorage` em try/catch); 15 B; as 4 C (login com sonda, `horario_aluno`, `notas_atividades`, `_video`); conferência dentro de um iframe HTTPS | 1 (e decisão 4) | ~25 | 1-2 |
| **6. Provas e material** | seletores dos roteiros, da jornada, do capturador e das provas (17 a 26 h de pessoa, §8); régua de ouro e retratos de novo; gravação dos 54 vídeos (2,5 a 3,5 h de máquina) e dos 65 prints (~10 min); `gerar_guias.php` | 3, 4, 5 | ~80 | 2-3 |
| **Total X** | | | | **11 a 15** |

**Usável de ponta a ponta em Tabler pela primeira vez:** ondas 0, 1, 2 e 3 completas, mais da onda 4 as três telas do fluxo diário (`visAtividades`, `verMatriz`, `encontro`), mais o login e a página inicial do portal (ou o portal inteiro em AdminLTE, decisão 4). Isto é, depois de **7 a 9 sessões**.

**Obrigatório para uma demonstração** (o roteiro do `PLANO_PALESTRA_26NOV_VCOSTAEDU.md` §3: instalar pelo navegador, criar curso, matriz, disciplina, aluno, professor, lançar nota, o aluno entrar no portal):
- onda 0 inteira (o instalador é a primeira tela da demonstração);
- onda 1 inteira (sem ela nada aparece em Tabler);
- da onda 3, **só Ensino e Educacional** (56 arquivos), mais `home/home` e `usuarios/*` (cadastrar o professor);
- da onda 4, `visAtividades` (notas) e `verMatriz`;
- do portal, `login`, `home/aluno` e `home/boletim_aluno`;
- **≈ 6 a 7 sessões.**

**Pode ficar para depois da demonstração:** Suporte técnico (15 telas, uso só da VCOSTA), Configurações exceto Dados da instituição, Relatórios (3 + 22 relatórios por uma tela só), o simulador da regra, a chamada, Tentativas de acesso, o resto do portal, e **toda a onda 6** (vídeos, prints e roteiros): na demonstração eles mostrariam a tela antiga.

**Modo Y, para comparar:** as ondas 1 a 6 são as mesmas (as views não mudam de dono), e a onda 0 vira: guarda em 545 arquivos, conexão/transação e log em ~211 + (não medido) arquivos, as chaves de sessão em ~284, o portal fundido na aplicação única ou trazido ao lado, e a reconciliação dos dois modelos de matrícula (`edu_*` contra `rm_*`). A onda 0 de Y é **estimada em 6 a 10 sessões** em vez de 2, e a régua de ouro, os retratos e a jornada precisam ser refeitos **antes** da troca de layout, porque o código por baixo mudou. **Total Y: 15 a 23 sessões.**

---

## O que NÃO foi medido

- A renderização no navegador de qualquer tela (nada foi aberto; a demonstração do ORIG não foi usada). Em particular: o efeito real da `}` sobrando em `escola1-ui.css:99` e a ponte jQuery do bundle do Tabler (lida no código minificado, não executada).
- O uso de `ion-` (Ionicons) nas views; o uso real das opções Responsive/FixedHeader/FixedColumns fora do `escola1-listas.js`.
- Os `label` sem `form-label` e os `<select class="form-control">` sem select2.
- As chamadas de `AdmsSalvarLog::salvarLogs` e de `Core\Transacao` (para o custo do modo Y), e a diferença entre o `AdmsRead` do painel e o do portal (`diff -q` diz que diferem).
- Quais páginas o ORIG põe no grupo 3 além de "Sair" (para a alça do rail), e a contagem exata de `dropdown` por grupo (a de §6.1 é aproximada e mistura `adms_` e `col_`).
- Se o rail do EDU já envolve o `localStorage` em try/catch.
- O tempo de `gerar_guias.php`, de `provar_menu.js`/`provar_player.js` e da jornada (interativa, sem registro).
- O conteúdo de `cabecalho_login.php` e `rodapejs_login.php` em detalhe.
- A licença do código herdado (cabeçalhos de copyright de terceiros no esqueleto `adms`), que o plano da palestra pede para conferir antes de abrir o repositório.
- O tamanho da cópia do ORIG em arquivos (fora o PHP) e o que nela é dado da instituição de origem.

## Decisões que dependem do responsável pelo projeto

1. **Modo X ou Y.**
   - **X** (código do ORIG + layout Tabler + instalador/runner do EDU): 11 a 15 sessões; o acadêmico validado continua valendo; ficam duas aplicações (painel e portal), a guarda e os prefixos do ORIG (`rm_`, `col_`, `uni_`); as decisões D1, D2, D19 e D31 do EDU deixam de valer; a matrícula do EDU vira módulo depois.
   - **Y** (esqueleto do EDU + módulos do ORIG): 15 a 23 sessões; aplicação única, guarda única, `edu_`, instalador sem adaptação; a validação do ORIG (régua, retratos, jornada, vídeos) tem de ser refeita sobre código reescrito; os dois modelos de matrícula precisam ser reconciliados.
2. **Select2** (23 telas + `validarForm.js` + `funcoes.js:16`):
   - (a) sem tema, como o projeto de referência: custo zero, o campo destoa do Tabler;
   - (b) o tema `select2-bootstrap-5-theme` 1.3.0: 1 CSS vendorizado, projeto arquivado (sem manutenção);
   - (c) Tom Select 2.6.2 (o do Tabler, sem jQuery, já em produção no projeto de referência e no P2): ~1 sessão a mais, API diferente (`dropdownParent`, o evento `select2:open`), e os 7 seletores `select2-*` dos roteiros mudam.
3. **jquery-tabledit** (lançamento de notas, sem build BS5, classes BS3):
   - (a) CSS próprio para `btn-default`, `glyphicon`, `input-sm`, `btn-toolbar`: ~1 h, o plugin continua velho;
   - (b) reescrever a edição em linha com JS próprio: ~1 sessão, e os 12 seletores `tabledit-*` dos roteiros mudam.
4. **O portal do aluno converte junto ou depois.**
   - **Junto** (+1 a 2 sessões): o rail do P2 já existe e serve de molde; a demonstração mostra uma identidade só; os vídeos do aluno são gravados uma vez.
   - **Depois**: o portal fica em AdminLTE e a pasta carrega **as duas pilhas** (BS4 e BS5, as duas integrações do DataTables, os dois `include/`), e o `escola1-ui.css` comum precisa servir aos dois; os vídeos e prints do aluno são refeitos duas vezes.
5. **Plugins carregados e sem uso** (tempusdominus, daterangepicker, moment, duallistbox, icheck, overlayScrollbars, Responsive/FixedHeader/FixedColumns): tirar (custo zero, recomendação do levantamento) ou manter para módulos futuros (cada um precisa de troca de build ou CSS).
6. **Ícones:** vendorizar o FA 6.5.2 completo, que o ORIG já tem (custo zero), ou ficar só com o solid do EDU e trocar 101 `far` e 5 `fab` (+ 4 no banco) por ícones solid (~1 h, mecânico).
7. **Cabeçalho da página:** o breadcrumb (332 ocorrências em 95 telas) some, ou vira o `page-pretitle` do Tabler (o mesmo script, outra saída).
8. **Estilo dos badges e dos cartões:** `bg-<cor>` sólido ou `bg-<cor>-lt` (como o projeto de referência); `card-outline` vira `card-status-top` ou some. Os dois são o mesmo custo; decide-se uma vez para o script.
9. **Regra de grupo do menu:** o rail passa a agrupar por `id_men` como o ORIG (1 arquivo), ou uma migration acerta `dropdown` no banco (18 linhas, e todo nível novo precisa nascer certo).
10. **Menu aberto ou recolhido (D-262 do ORIG):** manter "aberto a partir de 1200 px" (ajuste no rail) ou adotar o comportamento do rail do EDU (recolhe só por escolha, a partir de 992 px).
11. **A dica do botão desabilitado:** vira tooltip do Tabler de verdade (2 linhas de JS + 1 no `AdmsUi`), ou continua sendo o `title` do navegador, como é hoje sem que ninguém tenha notado.
12. **Mensagens de retorno:** substituição por script nos 120 arquivos (rápido, mantém o HTML espalhado) ou um helper de mensagem como o `AdmsMensagem` do projeto de referência (mais lento, e o HTML passa a existir num lugar só).
13. **Vídeos e prints:** refazer só no fim, com todas as telas convertidas (2,5 a 3,5 h de máquina uma vez), ou a cada onda (o mesmo tempo várias vezes); e se a demonstração usa os vídeos antigos.
14. **A demonstração de 26/11:** com 12 dias e a trilha Moodle no mesmo prazo (`PLANO_PALESTRA_26NOV_VCOSTAEDU.md` §4), o recorte de demonstração em Tabler custa 6 a 7 sessões. Caminhos: (a) Tabler no recorte da demonstração e Moodle em paralelo; (b) demonstrar em AdminLTE (o plano da palestra já previa suspender a D15) e converter depois; (c) Tabler só no instalador, no login e no rail, com as telas internas em AdminLTE por baixo (não é possível sem as duas pilhas carregadas na mesma tela; custo e risco altos).
15. **Achados no sistema de origem** (a `}` sobrando em `escola1-ui.css:99`, o `AdmsUi` diferente no portal, o comentário da ficha financeira): corrigir também na instância da instituição de origem ou só aqui. Não foi tocado nada lá.
