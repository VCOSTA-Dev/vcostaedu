<?php

/**
 * O LAYOUT DO INSTALADOR — próprio, mínimo, e dentro de `instalar/`.
 *
 * ================== POR QUE NÃO USA O ConfigView =============================
 *
 * Decisão D15. Está medido (§ do LEVANTAMENTO, e o comentário do próprio
 * `ConfigView` de outro projeto da casa o registra) que `baseIncludes()` CAI para
 * `app/adms/Views/include/` — o layout AdminLTE herdado — quando a pasta de
 * layout pedida não tem os partials. Essa pasta não existe neste projeto: o
 * instalador cairia num include ausente e renderizaria quebrado, com HTTP 200.
 *
 * Além disso o `ConfigView` monta caminho com `app/{modulo}/...` relativo ao
 * diretório de trabalho, e depende da constante-guarda do projeto. Nenhuma das
 * duas coisas existe aqui.
 *
 * ==================== POR QUE OS CAMINHOS SÃO RELATIVOS ======================
 *
 * NÃO existe a constante `URL`: ela nasce dentro do `Config.php`, que é o
 * arquivo que este instalador ainda vai criar. O `head.php` do projeto de referência
 * monta todo caminho de asset com `URL . 'assets/...'`, e por isso ele não pôde
 * ser herdado como está.
 *
 * `../assets/` resolve a partir de `instalar/`, e resolve igual em qualquer
 * pasta em que o projeto tenha sido descompactado — que é o público desta tela.
 *
 * ======================== POR QUE NADA VEM DE CDN ============================
 *
 * D15. O instalador tem de funcionar em rede fechada; escola sem internet de
 * saída é caso real. O `head.php` de origem carrega a família Barlow do Google
 * Fonts, e ela NÃO está vendorizada em lugar nenhum (medido). A linha do CDN
 * não veio: sem ela a tipografia cai para a pilha de sistema que o próprio
 * `vcostable-tabler.css` já declara ao lado de 'Barlow'. Uma tela sem estilo
 * responde HTTP 200 igualzinho — é a regra 1 valendo para a primeira tela do
 * projeto.
 */

declare(strict_types=1);

/* Só o index.php do instalador carrega este arquivo; pedido direto não responde nada (o .htaccess da pasta já o
   manda para o index.php, que recusa quando o sistema está instalado). */
if (!defined('VCOSTAEDU_INSTALADOR')) {
    http_response_code(404);
    exit;
}

/**
 * Os passos da instalação, na ordem.
 *
 * SÃO SETE, e o sétimo é o "Pronto". Ele era renderizado dentro do passo do
 * administrador, e a tela dizia "Passo 6 de 6" numa página que a pessoa ainda ia
 * ver depois — a régua terminava antes da instalação. A §2 do
 * `LEVANTAMENTO_INSTALADOR.md` sempre contou o "Pronto" como tela; a numeração é
 * que estava atrasada.
 *
 * O rótulo é o que a pessoa lê. São nomes do mundo dela, não do nosso: "Tabelas"
 * e não "migrations", "Conferência" e não "validação de charset".
 */
const PASSOS = [
    'ambiente'      => 'Requisitos',
    'banco'         => 'Banco de dados',
    'conferencia'   => 'Conferência',
    'arquivo'       => 'Configuração',
    'tabelas'       => 'Tabelas',
    'administrador' => 'Sua conta',
    'pronto'        => 'Pronto',
];

/* D-125 (1.102.1, C3.1): $rotuloPasso é o nome do passo na régua quando a tela é uma parte do passo (o tipo de instituição,
   dentro de "Sua conta"); sem ele, o nome do passo. */
function abrirPagina(string $titulo, ?string $passoAtual = null, ?string $rotuloPasso = null): void
{
    $indice = $passoAtual === null ? -1 : array_search($passoAtual, array_keys(PASSOS), true);
    ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo) ?> — Instalação do VCOSTAEDU</title>
  <link rel="icon" type="image/png" href="<?= e(assetUrl('assets/vcostaedu/favicon.png', '../')) ?>">
  <?php /* A marca de alteracao na URL — a MESMA funcao que o sistema usa.
           Ver `core/asset.php`. Aqui o prefixo e relativo porque a constante
           `URL` so passa a existir depois que este instalador grava o arquivo
           de configuracao. */ ?>
  <?php require_once dirname(__DIR__) . '/core/Tema.php'; echo VcTema::script(null); /* Onda 3, Etapa 10: o tema (claro, escuro, automático) antes do primeiro desenho — core/Tema.php */ ?>
  <link rel="stylesheet" href="<?= e(assetUrl('assets/plugins/tabler/css/tabler.min.css', '../')) ?>">
  <link rel="stylesheet" href="<?= e(assetUrl('assets/css/vcostable-tabler.css', '../')) ?>">
  <link rel="stylesheet" href="<?= e(assetUrl('assets/css/vcostaedu-componentes.css', '../')) ?>">
</head>
<body class="border-top-wide border-primary d-flex flex-column">
<div class="page page-center">
  <div class="container container-tight py-4">

    <div class="text-center mb-4">
      <?php /* VCOSTAEDU: a marca (assets/marca/, fora da AGPL — TRADEMARK.md) */ ?>
      <h1 class="mb-1"><img src="<?= e(assetUrl('assets/marca/vcostaedu-wordmark.svg', '../')) ?>" alt="VCOSTAEDU" style="height:44px;width:auto"></h1>
      <p class="text-secondary mb-0">Instalação</p>
    </div>

    <?php if ($indice !== false && $indice >= 0): ?>
    <?php
    /*
     * A RÉGUA DE PASSOS — e por que ela deixou de listar todos os nomes.
     *
     * ========================= O QUE ESTAVA ERRADO =========================
     *
     * Ela era uma lista com os SETE rótulos lado a lado, e vinha DUAS VEZES no
     * HTML: uma versão vertical para telas estreitas e uma horizontal para telas
     * largas, cada uma escondida pela outra por classe de responsividade.
     *
     * Duas coisas, e as duas medidas:
     *
     *   1. o HTML trazia 14 itens para um percurso de 7. Quando a regra de
     *      responsividade não pega — container estreito, tema alterado, folha de
     *      estilo que não carregou —, aparecem os dois. Não é defeito de CSS a
     *      esconder: é markup duplicado, e a correção é não duplicar;
     *   2. sete rótulos lado a lado NÃO CABEM na largura desta caixa (a mesma
     *      `container-tight` do Tabler). O resultado é texto espremido que a
     *      pessoa não lê — e uma régua ilegível não faz o trabalho dela.
     *
     * ======================= PARA QUE A RÉGUA EXISTE ========================
     *
     * Duas perguntas, e só duas: **onde estou** e **quanto falta**. Listar os
     * sete nomes responde a uma terceira — "quais são todos os passos" — que a
     * pessoa não faz, e que custa a legibilidade das outras duas.
     *
     * Então: uma barra para o quanto falta, e o nome do passo ATUAL para o onde
     * estou. Cabe em qualquer largura, sem duplicar markup e sem depender de
     * regra de responsividade nenhuma.
     *
     * Alternativas consideradas e recusadas: rótulos mais curtos (sete itens
     * continuam não cabendo, e nome abreviado é nome pior); régua vertical fixa
     * (empurra o formulário para baixo, e a pessoa passa a rolar a tela para ver
     * o que veio fazer); agrupar em fases (inventa uma estrutura que a pessoa não
     * tem e que teríamos de explicar).
     */
    $numero = $indice + 1;
    $total = count(PASSOS);
    $percentual = (int) round($numero / $total * 100);
    ?>
    <div class="mb-4">
      <div class="d-flex align-items-baseline justify-content-between mb-1">
        <span class="fw-medium"><?= e($rotuloPasso ?? array_values(PASSOS)[$indice]) ?></span>
        <span class="text-secondary small">Passo <?= $numero ?> de <?= $total ?></span>
      </div>
      <div class="progress progress-sm" role="progressbar"
           aria-valuenow="<?= $numero ?>" aria-valuemin="1" aria-valuemax="<?= $total ?>"
           aria-label="Progresso da instalação">
        <div class="progress-bar" style="width: <?= $percentual ?>%"></div>
      </div>
    </div>
    <?php endif; ?>
<?php
}

function fecharPagina(): void
{
    ?>
    <?= VcLegal::rodape(VcLegal::versaoDoPacote(raizDoProjeto()), '../', 'text-center mt-4') ?>
  </div>
</div>
<script src="<?= e(assetUrl('assets/plugins/tabler/js/tabler.min.js', '../')) ?>"></script>
<script src="<?= e(assetUrl('assets/js/vcostaedu-senhas-comuns.js', '../')) ?>"></script>
<script src="<?= e(assetUrl('assets/js/vcostaedu-componentes.js', '../')) ?>"></script>
</body>
</html>
<?php
}

/**
 * Escapa para HTML. Nome curto porque aparece em toda interpolação — e o que
 * precisa ser óbvio na leitura é ONDE ele falta, não onde ele está.
 */
function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * A tela de RECUSA. Uma só, para todas as recusas da §4 do levantamento.
 *
 * A estrutura é sempre a mesma e é deliberada: o que aconteceu, o que fazer, e
 * o que o instalador deixou de fazer. A terceira parte é a que costuma faltar e
 * é a que evita que a pessoa refaça um passo já feito.
 */
function telaRecusa(
    string $titulo,
    array $paragrafos,
    string $rodape = '',
    string $comando = '',
    string $voltarPara = '',
    string $voltarRotulo = ''
): void {
    abrirPagina($titulo);
    ?>
    <div class="card card-md">
      <div class="card-status-top bg-danger"></div>
      <div class="card-body">
        <h2 class="card-title text-danger"><?= e($titulo) ?></h2>
        <?php foreach ($paragrafos as $p): ?>
        <p class="text-secondary"><?= $p ?></p>
        <?php endforeach; ?>
        <?php if ($comando !== ''): ?>
        <pre class="bg-dark text-white p-3 rounded vc-comando" data-vc-copiar><code><?= e($comando) ?></code></pre>
        <?php endif; ?>
        <?php if ($rodape !== ''): ?>
        <p class="<?= $voltarPara !== '' ? 'mb-3' : 'mb-0' ?>"><strong><?= e($rodape) ?></strong></p>
        <?php endif; ?>
        <?php /* CAMINHO DE VOLTA. Sem ele a recusa é um beco: a pessoa lê o que
                 fazer e não tem para onde ir, a não ser editar o endereço à mão.
                 Recusa que não oferece a próxima ação é metade de uma mensagem. */ ?>
        <?php if ($voltarPara !== ''): ?>
        <a href="<?= e($voltarPara) ?>" class="btn btn-primary w-100"><?= e($voltarRotulo) ?></a>
        <?php endif; ?>
      </div>
    </div>
    <?php
    fecharPagina();
}

/** Erro fatal: o handler de exceção não tem passo nem formulário para mostrar. */
function telaErroFatal(string $mensagem): void
{
    telaRecusa(
        'O instalador parou.',
        [e($mensagem)],
        'Nada foi alterado.'
    );
}
