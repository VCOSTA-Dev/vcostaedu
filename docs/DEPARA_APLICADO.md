# O de-para como foi aplicado (AdminLTE 3 / BS4 → Tabler 1.4 / BS5)

Escrito no piloto (Etapa 4) e mantido pelas etapas seguintes. É o dicionário da §2 do `LEVANTAMENTO_DEPARA_TABLER.md` **como ele ficou no código**, e o que não deu para fazer por script.

## As ferramentas

| ferramenta | o que faz |
|---|---|
| `database/ferramentas/tabler/converter_views.php [--conferir] <views…>` | a casca da página e o dicionário abaixo, nas views (rodar de dentro de `painel/` ou `portal/`, que é de onde os includes `app/...` resolvem); relata, por arquivo, o que sobra para o manual. Idempotente |
| `database/ferramentas/tabler/converter_mensagens.php [--conferir\|--final]` | a marcação que controllers e models montam em texto (mensagens de retorno, badges, o CSS do PDF do boletim) |
| `tests/tabler/telas.js` | a prova de cada tela: aviso do PHP, erro no console, **sobra do dicionário no DOM renderizado**, layout Tabler, prints 1366/390 |
| `tests/tabler/comportamento.js` | o que a tela faz: lista (pesquisa, ordena, pagina, exporta e abre os arquivos), formulário (grava, mensagem aparece e fecha), modal (abre preenchido), ação desabilitada (dica), editor |

Depois de converter uma view, o controller dela passa a chamar `renderizar('tabler')` (ou `renderizarLogin('tabler')`, `renderizarEditInt('tabler')`).

## 1. A casca da página

| AdminLTE | Tabler (como ficou) |
|---|---|
| `<div class="content-wrapper">` | `<div class="vc-pagina">` (neutro; o `.page-wrapper` vem do layout) |
| `<div\|section class="content-header">…<h1>TÍTULO</h1>…breadcrumb…</div>` | `<?php ob_start(); ?>TÍTULO<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>` — o título é o `<h1>` da origem renderizado (o que lá era escapado continua escapado); o breadcrumb some (D-07); o `_page_header` traz o ícone de ajuda da tela |
| `<section class="content">` | `<section class="page-body">` |
| `container-fluid` | `container-xl` |
| `main-sidebar`, `main-header`, `main-footer`, pushmenu | o layout: `include_tabler/head.php` + `rail.php` + `libraries_js.php` |

## 2. Classes (por script)

| Bootstrap 4 / AdminLTE | Bootstrap 5 / Tabler |
|---|---|
| `ml-*` `mr-*` `pl-*` `pr-*` (com breakpoint e `auto`) | `ms-*` `me-*` `ps-*` `pe-*` |
| `float-left/right`, `text-left/right` (com breakpoint) | `float-start/end`, `text-start/end` |
| `border-left/right`, `rounded-left/right` | `border-start/end`, `rounded-start/end` |
| `font-weight-*`, `font-italic` | `fw-*`, `fst-italic` |
| `sr-only`, `no-gutters`, `btn-block`, `btn-xs` | `visually-hidden`, `g-0`, `w-100`, `btn-sm` |
| `thead-light/dark`, `img-circle`, `elevation-N` | `table-light/dark`, `rounded-circle`, `shadow-sm` |
| `badge-pill`, `badge-<cor>`, `badge-light` | `rounded-pill`, `bg-<cor>-lt` (D-08), `bg-light text-dark` |
| `badge badge-<?= $cor ?>` (inclusive com ternário dentro) | `badge bg-<?= $cor ?>-lt` |
| `'badge badge-' . $x . '"'` (concatenação) | `'badge bg-' . $x . '-lt"'` |
| `badge-{$cor}` (interpolação) | `bg-{$cor}-lt` |
| `form-row`, `form-group`, `custom-select` | `row g-2`, `mb-3`, `form-select` |
| `custom-control custom-checkbox/radio`, `custom-switch` | `form-check`, `form-check form-switch` |
| `custom-control-input/label` | `form-check-input/label` |
| `<select class="form-control …">` | `form-select` |
| `<label>` sem classe | `<label class="form-label">` |
| `card-outline`, `card-<cor>` | somem (D-08) |
| `card-tools`, `btn-tool`, `btn-default` | `card-actions`, `btn-action`, `btn-outline-secondary` |
| `callout callout-<cor>` | `alert alert-<cor>` |
| `info-box`, `info-box-icon/-content/-text/-number` | `card card-sm vc-indicador`, `vc-indicador-icone/-conteudo/-texto/-numero` (o indicador desta casa, desenhado no CSS; os ids ficam) |
| `custom-control-inline` | `form-check-inline` |
| `input-group-append/-prepend` (invólucro) | o invólucro sai; os filhos ficam no `input-group` |
| `badge rounded-pill badge-<?= $x ?>` (o `badge-pill` no meio) | `badge rounded-pill bg-<?= $x ?>-lt` (Etapa 7) |
| `<select … <?= … ?> … class="form-control">` (PHP antes do `class`) | `form-select` (Etapa 7) |
| `form-inline` | `d-flex flex-wrap align-items-center gap-2` (Etapa 7) |
| `form-control-file`, `text-monospace` | `form-control`, `font-monospace` (Etapa 7) |
| `<button class="close">×</button>` | `<button class="btn-close"></button>` |

## 3. Atributos (por script)

| BS4 | BS5 |
|---|---|
| `data-toggle/target/dismiss/placement/parent/spy/offset/ride/slide/interval` | `data-bs-*` |
| `data-card-widget="collapse"` (recolher o cartão do AdminLTE) | `data-vc-recolher`, tratado pelo `assets/escola1/escola1-tabler.js` |

## 4. JavaScript (à mão, num lugar só)

| BS4 | como ficou |
|---|---|
| `new bootstrap.Modal(el)` (8× no `funcoes.js`) | `abrirModal(id)`, que usa `window.tabler.Modal` no Tabler e o do Bootstrap 4 no AdminLTE enquanto convivem |
| `$(el).on('show.bs.modal', …)` + `event.relatedTarget` | **fica**. Provado no piloto: o modal "Compensar" das faltas e o "Apagar" de Usuários abrem preenchidos por essas escutas. O bundle do Tabler traz a ponte jQuery do Bootstrap 5 (registrada no `DOMContentLoaded`) e repassa o evento com o `relatedTarget` |
| `$(…).modal('show')`, `$(…).tooltip()` | ficam (a mesma ponte), protegidos por `if ($.fn.…)` |
| `data-toggle="tooltip"` do `AdmsUi` (nunca iniciado na origem) | `data-bs-toggle="tooltip"`, iniciado pelo `escola1-tabler.js` (D-11) |
| `collapsed.lte.pushmenu` (`escola1-ui.js`) | o rail guarda o recolhido sozinho (`localStorage` `vc-side-mini`) |
| Summernote: dicas da barra pelo `$.fn.tooltip` | `tooltip: false` (como o projeto de referência); o build bs5 0.8.20 ainda monta os menus com `data-toggle="dropdown"` e os diálogos com `form-group`: o `escola1-editor.js` traduz na hora, só dentro do editor |
| `validarForm.js`: o rótulo do campo pelo `.form-group` | `.form-group, .form-row, .mb-3, .modal-body` |
| duallistbox (plugin que saiu) | a chamada é protegida |

## 5. CSS de ajuste (`assets/css/vcostaedu-tabler.css`)

Por que cada regra existe:
- a marca do rail sobre placa branca; o logo da instituição;
- DataTables e botões de exportar (do projeto de referência); Select2 com a cara do `form-select` (D-02); as classes do BS3 do tabledit (D-03);
- `btn-sm` volta a ser pequeno (a identidade do vcostaedu aumenta todo `.btn`);
- o `.alert` e o `.page-title` do Tabler são `flex`: o conteúdo misto da origem (texto, `<b>`, ícones, `<small>`) se espalhava em pedaços — o alerta volta a bloco e o título ganha espaço entre os trechos;
- o `.btn` do Tabler é `flex` e perdia o espaço entre o ícone e o texto;
- os atalhos da Página Inicial (ícone em cima, nome embaixo);
- o cartão recolhido; a impressão.

## 6. Convivência das duas pilhas (Etapas 3 a 7) — encerrada na Etapa 8

- A marcação gerada fora da view (mensagens de retorno, `AdmsUi::acaoModal`) leva os atributos do BS5 **e** do BS4; nas telas Tabler, `ConfigView::soBootstrap5()` tira os do BS4. O `personalizado.css` (só AdminLTE) desenha o `btn-close` e os `bg-*-lt`. Provado: a mensagem fecha numa tela Tabler e numa tela AdminLTE.
- **Etapa 8 (feito):** `converter_mensagens.php --final` (318 botões de fechar só com o atributo do BS5), o `AdmsUi::acaoModal` só com `data-bs-*`, o filtro `soBootstrap5()` e o fallback do `ConfigView` saíram; saíram `include/` (painel e portal), os CSS e JS do AdminLTE (`adminlte*`, `alt/`, `demo.js`, `pages/`, `tables.js`, `personalizado.css`, e no portal `boot.css`, `signin.css`, `fonts/`), o Bootstrap 4, `datatables-bs4` e `select2-bootstrap4-theme`; o `abrirModal` ficou só com o Tabler e a ponte jQuery.

## 7. O portal (Etapa 6)

- O layout é o do painel com as trocas do rail do Portal 2.0: `URLADM` → `URLACAD`, sessão `academico_*`, o menu do banco do portal (`col_*`), a alça no grupo 5 ("Sair"), "Meu perfil" em `perfil/visualizar`, a versão em `home/novidades`, rótulo "Aluno" sob o nome; scripts `portal/assets/js/` (o `funcaologin.js` da origem fica no login: ele só usa jQuery e as classes de validação).
- O login guarda o plano B do iframe inteiro (`#planoB`, `#abrirNovaAba`, `#cartaoLogin`, o script da sonda).
- `small-box` (Meu progresso) → `card card-sm` com o número em `h1` (o primeiro com `card-status-top bg-info`); os ids ficam.
- `form-inline` (não é do dicionário do script, mas não existe no BS5) → `d-flex flex-wrap align-items-center gap-*`.

## 8. O resto do painel (Etapa 7)

- Os cartões de número dos relatórios: `card card-sm` + `card-status-top` com a cor do model; o rodapé "Ver a lista" virou `card-footer`.
- A verificação pública (página avulsa): o CSS do Tabler no lugar do AdminLTE; `text-monospace` → `font-monospace`; `form-control-file` → `form-control`.
- Marcação montada no servidor que ainda era do BS4: `AdmsBoletimCurso` do painel (igual ao do portal), `AdmsRegraMedia` (`me-1`), `AdmsVerHorarioAula` (`table-dark`), `AdmsEnviarLinkAcesso` (sem o invólucro do botão), o aviso de confirmação da regra do curso (`card border-warning`), as classes de célula dos relatórios (`fw-bold`).
- O script ficou mais seguro: se uma regra falha no PCRE ou o resultado sai vazio, ele pára sem gravar (DUVIDAS 43).

## 9. O que o script não resolve (manual, por tela)

`small-box`, `custom-file`, os widgets do AdminLTE que não são o recolher do cartão, `media`, `jumbotron`, `embed-responsive`, e JavaScript da própria tela que escreve classe do BS4 em texto (o simulador da regra de média, a chamada). O relatório do `converter_views.php` lista cada um, por arquivo.
