<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E6b: verificação pública (mínimo: situação, tipo, data, instituição, curso, aluno, CPF mascarado e hash).
   E8c (A18): refeita — logo e nome da instituição no topo, resultado grande com ícone, pensada primeiro para o
   celular, hash num bloco com botão de copiar e uma frase simples, "Imprimir comprovante de verificação", busca
   pelo endereço curto <site>/verificar/<código> e o selo "Verificação pela plataforma VCOSTAEDU" no rodapé.
   O limite da E7 e o mínimo de dados não mudaram.
   VCOSTAEDU (Etapa 7): o CSS da página é o do Tabler (era o do AdminLTE); o desenho próprio fica. */
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$e = $d['e'];
$dt = fn($x) => $x ? date('d/m/Y H:i', strtotime($x)) : '—';
if ($e) {
  $estado = !$e['integra'] ? 'divergente' : ($e['valida'] ? 'valido' : 'cancelado');
} else {
  $estado = $d['informado'] ? 'nao-encontrado' : 'inicio';
}
$res = [
  'valido' => ['fa-circle-check', 'Documento válido', 'O documento com este código foi emitido pela instituição e não foi alterado.'],
  'cancelado' => ['fa-circle-xmark', 'Documento cancelado', 'Este documento foi cancelado pela instituição em ' . ($e ? $dt($e['canceladaEm']) : '') . '. Não tem mais validade.'],
  'divergente' => ['fa-triangle-exclamation', 'Não confere', 'O registro guardado não corresponde ao documento emitido. Não aceite o documento; procure a instituição.'],
  'nao-encontrado' => ['fa-circle-question', 'Código não encontrado', 'Confira o código impresso no documento. Se tiver certeza do código, tente de novo em alguns minutos.'],
][$estado] ?? null;
?><!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Verificação de documento — <?= $h($d['instituicaoNome'] ?? '') ?></title>
  <link rel="icon" type="image/png" href="<?= URL ?>assets/vcostaedu/favicon.png">
  <link rel="stylesheet" href="<?= \App\adms\Models\helper\AdmsAsset::url('assets/plugins/tabler/css/tabler.min.css', URL) ?>">
  <link rel="stylesheet" href="<?= URL ?>assets/plugins/fontawesome-free/css/all.min.css">
  <style>
    body { background: #f4f6f9; color: #212529; }
    .caixa { max-width: 640px; margin: 0 auto; padding: 16px; }
    .topo { display: flex; align-items: center; gap: 12px; padding: 12px 0 16px; }
    .topo img { max-height: 56px; max-width: 120px; background: #fff; border-radius: 8px; padding: 3px; }
    .topo .nome { font-weight: 700; font-size: 1.05rem; line-height: 1.2; }
    .topo .sub { font-size: .8rem; color: #5C6370; }
    .resultado { text-align: center; border-radius: 10px; padding: 22px 16px; color: #fff; margin-bottom: 14px; }
    .resultado i { font-size: 3.2rem; display: block; margin-bottom: 8px; }
    .resultado h1 { font-size: 1.6rem; margin: 0 0 6px; font-weight: 700; }
    .resultado p { margin: 0; font-size: .95rem; }
    .r-valido { background: #1e7e34; } .r-cancelado, .r-divergente { background: #c82333; } .r-nao-encontrado { background: #5C6370; }
    .dados { background: #fff; border-radius: 10px; padding: 4px 16px; margin-bottom: 14px; }
    .dados dl { margin: 0; } .dados .linha { display: flex; flex-wrap: wrap; padding: 9px 0; border-bottom: 1px solid #eee; }
    .dados .linha:last-child { border-bottom: 0; } .dados dt { width: 40%; min-width: 7.5rem; font-weight: 600; color: #495057; } .dados dd { flex: 1 1 12rem; margin: 0; }
    .hash-bloco { background: #fff; border-radius: 10px; padding: 12px 16px; margin-bottom: 14px; }
    .hash-bloco code { display: block; word-break: break-all; font-size: .8rem; background: #f1f3f5; color: #212529; padding: 8px; border-radius: 6px; margin: 6px 0; }
    .busca { background: #fff; border-radius: 10px; padding: 14px 16px; margin-bottom: 14px; }
    .busca .form-control { font-family: monospace; text-transform: uppercase; }
    .selo { text-align: center; font-size: .78rem; color: #5C6370; padding: 14px 0 24px; }
    .selo .marca { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #ced4da; border-radius: 20px; padding: 4px 12px; background: #fff; color: #495057; }
    .demo { background: #fff3cd; color: #856404; border-radius: 8px; padding: 10px 12px; margin-bottom: 14px; font-size: .9rem; }
    @media print {
      body { background: #fff; } .nao-imprimir { display: none !important; }
      .resultado { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .caixa { max-width: none; }
    }
  </style>
</head>
<body>
  <div class="caixa">
    <header class="topo">
      <?php if (!empty($d['instituicaoLogo'])) : ?><img src="<?= $h($d['instituicaoLogo']) ?>" alt="Logo de <?= $h($d['instituicaoNome']) ?>"><?php endif; ?>
      <div><div class="nome" id="instituicaoTopo"><?= $h($d['instituicaoNome'] ?? '') ?></div><div class="sub">Verificação de documento (histórico escolar ou atestado de matrícula)</div></div>
    </header>

    <?php if ($res) : ?>
      <section class="resultado r-<?= htmlspecialchars((string) ($estado), ENT_QUOTES, 'UTF-8') ?>" id="resultadoVerificacao" data-estado="<?= htmlspecialchars((string) ($estado), ENT_QUOTES, 'UTF-8') ?>">
        <i class="fas <?= htmlspecialchars((string) ($res[0]), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
        <h1 id="situacaoVerificacao"><?= $h($res[1]) ?></h1>
        <p><?= $h($res[2]) ?></p>
      </section>
    <?php endif; ?>

    <?php if ($e) : ?>
      <?php if (($e['ambiente'] ?? 'producao') !== 'producao') : ?>
        <div class="demo" id="ambienteVerificacao"><b><?= htmlspecialchars((string) ($e['ambiente'] === 'demonstracao' ? 'DOCUMENTO DE DEMONSTRAÇÃO' : 'DOCUMENTO DE HOMOLOGAÇÃO'), ENT_QUOTES, 'UTF-8') ?></b> — emitido num ambiente de teste, com dados fictícios. Não tem validade.</div>
      <?php endif; ?>
      <section class="dados">
        <dl>
          <div class="linha"><dt>Código</dt><dd class="font-monospace"><?= $h($e['codigo']) ?></dd></div>
          <div class="linha"><dt>Documento</dt><dd id="tipoDocumento"><?= $h(\App\adms\Models\historico\AdmsHistoricoModelos::nomeDocumento((string) $e['tipo'])) ?></dd></div>
          <div class="linha"><dt>Emitido em</dt><dd><?= $dt($e['emitidoEm']) ?></dd></div>
          <div class="linha"><dt>Instituição</dt><dd><?= $h($e['instituicao']) ?></dd></div>
          <div class="linha"><dt>Curso</dt><dd><?= $h($e['curso']) ?></dd></div>
          <div class="linha"><dt>Aluno</dt><dd id="alunoVerificado"><?= $h($e['aluno']) ?></dd></div>
          <div class="linha"><dt>CPF</dt><dd id="cpfVerificado"><?= $h($e['cpf']) ?></dd></div>
        </dl>
      </section>
      <section class="hash-bloco">
        <b>Impressão digital do documento</b>
        <code id="hashVerificacao"><?= $h($e['hash']) ?></code>
        <div class="d-flex flex-wrap align-items-center" style="gap: 8px">
          <button type="button" class="btn btn-sm btn-outline-secondary nao-imprimir" id="copiarHash" title="Copiar o código de integridade"><i class="far fa-copy"></i> Copiar</button>
          <small class="text-muted" id="integridadeVerificacao" style="flex: 1 1 14rem"><?= htmlspecialchars((string) ($e['integra'] ? 'Esta sequência só é igual se o documento não mudou desde a emissão.' : 'DIVERGÊNCIA: o registro guardado não corresponde a esta sequência.'), ENT_QUOTES, 'UTF-8') ?></small>
        </div>
      </section>
      <div class="d-flex flex-wrap mb-3 nao-imprimir" style="gap: 8px">
        <button type="button" class="btn btn-outline-dark" onclick="window.print()" id="imprimirComprovante"><i class="fas fa-print"></i> Imprimir comprovante de verificação</button>
      </div>
      <section class="busca nao-imprimir">
        <p class="mb-2"><b>Conferir o arquivo recebido:</b> envie o PDF para comparar com o documento emitido. O arquivo é só lido, não é guardado.</p>
        <p class="small text-muted mb-2" id="notaAssinado">O documento pode ter sido <b>assinado eletronicamente</b> depois de emitido; a assinatura muda o arquivo, e o PDF assinado não confere por este envio. Nesse caso, a autenticidade vale pelo <b>código</b> e pelos dados mostrados acima.</p>
        <form method="POST" action="<?= URLADM . 'verificar/codigo/' . $h($e['codigo']) ?>" enctype="multipart/form-data">
          <input type="file" name="pdf" accept="application/pdf" class="form-control mb-2" required>
          <button class="btn btn-outline-primary btn-sm">Conferir PDF</button>
        </form>
        <?php if ($d['conferencia']) : ?>
          <div class="alert alert-<?= htmlspecialchars((string) ($d['conferencia']['ok'] ? 'success' : 'danger'), ENT_QUOTES, 'UTF-8') ?> mb-0 mt-2" id="conferenciaPdf"><?= $h($d['conferencia']['msg']) ?></div>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <section class="busca nao-imprimir">
      <form method="GET" action="<?= URLADM ?>verificar/codigo" id="formVerificar">
        <label for="c" class="mb-1"><b><?= $e ? 'Verificar outro código' : 'Código do documento' ?></b></label>
        <div class="d-flex flex-wrap" style="gap: 8px">
          <input type="text" name="c" id="c" class="form-control" style="flex: 1 1 14rem" placeholder="ex.: ABCD-EFGH-JKLM" value="<?= $e ? '' : $h($d['codigo']) ?>" maxlength="20" autocomplete="off" inputmode="text" required>
          <button class="btn btn-primary"><i class="fas fa-magnifying-glass"></i> Verificar</button>
        </div>
        <small class="text-muted">O código está impresso no documento, perto do QR code.</small>
      </form>
    </section>

    <footer class="selo">
      <span class="marca" id="seloVcostaedu"><i class="fas fa-shield-halved"></i> Verificação pela plataforma VCOSTAEDU</span>
      <div class="mt-2">Esta página mostra só o necessário para conferir a autenticidade. Dados completos, só com a instituição.</div>
    </footer>
  </div>
  <script>
    (function () {
      /* A18: a busca vai direto ao endereço curto <site>/verificar/<código>; sem JavaScript, o GET faz o mesmo por redirecionamento */
      var f = document.getElementById('formVerificar');
      f.addEventListener('submit', function (ev) {
        var c = (document.getElementById('c').value || '').toUpperCase().replace(/[^A-Z0-9-]/g, '');
        if (c) { ev.preventDefault(); window.location.href = <?= json_encode(URL . 'verificar/') ?> + encodeURIComponent(c); }
      });
      var b = document.getElementById('copiarHash');
      if (b) {
        b.addEventListener('click', function () {
          var t = document.getElementById('hashVerificacao').textContent.trim();
          var ok = function () { b.innerHTML = '<i class="fas fa-check"></i> Copiado'; };
          try {
            if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(t).then(ok); return; }
          } catch (e) {}
          var r = document.createRange(); r.selectNodeContents(document.getElementById('hashVerificacao'));
          var s = window.getSelection(); s.removeAllRanges(); s.addRange(r);
          try { document.execCommand('copy'); ok(); } catch (e) {}
        });
      }
    })();
  </script>
</body>
</html>
