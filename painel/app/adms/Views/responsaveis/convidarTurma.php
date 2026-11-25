<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Os convites de uma turma (Onda 7, Etapa 3; D-63): os responsáveis com papel no ano que ainda não têm acesso; o convite
   de cada um, com o envio por e-mail em passos (a janela de progresso); quem não recebeu por e-mail fica na lista do
   envio manual, com o link e o WhatsApp — o link só nesta tela, na hora (não fica guardado). */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$M = \App\adms\Models\escola\AdmsResponsaveis::class;
$lote = \App\adms\Models\escola\AdmsResponsaveisConviteLote::publico((new \App\adms\Models\escola\AdmsResponsaveisConviteLote())->estado());
$parado = $lote && !$lote['concluida'] && (int) $lote['turma'] === (int) $d['turma'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Convidar os responsáveis de uma turma<?php $paginaTitulo = ob_get_clean();
  $paginaPretitulo = 'Responsáveis';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <form method="get" class="card mb-3" id="escolherTurma"><div class="card-body d-flex flex-wrap gap-2 align-items-end">
        <div><label class="form-label" for="ano">Ano letivo</label>
          <select name="ano" id="ano" class="form-select" onchange="this.form.turma.value = ''; this.form.submit()"><?php foreach ($d['anos'] as $a) : ?><option value="<?= $h($a) ?>"<?= (int) $a === (int) $d['ano'] ? ' selected' : '' ?>><?= $h($a) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label" for="turma">Turma</label>
          <select name="turma" id="turma" class="form-select"><option value="">— escolha —</option><?php foreach ($d['turmas'] as $t) : ?><option value="<?= $h((int) $t['id']) ?>"<?= (int) $t['id'] === (int) $d['turma'] ? ' selected' : '' ?>><?= $h($t['nome']) ?></option><?php endforeach; ?></select></div>
        <button type="submit" class="btn btn-outline-primary">Ver os responsáveis</button>
      </div></form>

      <?php if ($d['turma']) : ?>
        <div class="card mb-3" id="pendentesTurma">
          <div class="card-header"><h3 class="card-title">Sem acesso ao portal</h3><div class="card-actions text-secondary small"><?= $h(count($d['pendentes'])) ?> pessoa(s)</div></div>
          <?php if (!$d['pendentes']) : ?>
            <div class="card-body text-secondary" id="ninguemPendente">Nesta turma, todos os responsáveis com papel em <?= $h($d['ano']) ?> já têm acesso (ou estão bloqueados).</div>
          <?php else : ?>
            <div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Responsável</th><th>Alunos</th><th>E-mail</th></tr></thead><tbody>
              <?php foreach ($d['pendentes'] as $p) : ?>
                <tr data-responsavel="<?= $h((int) $p['id']) ?>"><td><a href="<?= URLADM ?>responsaveis/ver/<?= $h((int) $p['id']) ?>"><?= $h($p['nome']) ?></a></td><td><?= $h($p['alunos']) ?></td>
                  <td><?= filter_var((string) $p['email'], FILTER_VALIDATE_EMAIL) ? $h($p['email']) : '<span class="text-secondary">sem e-mail: envio manual</span>' ?></td></tr>
              <?php endforeach; ?>
            </tbody></table></div>
            <div class="card-footer">
              <?php if (!$d['envio']) : ?><div class="alert alert-info mb-2" id="envioNaoConfigurado">O envio de e-mail não está configurado (Configurações de e-mail): os convites são gerados e todos ficam na lista do envio manual, com o link e o WhatsApp.</div><?php endif; ?>
              <input type="hidden" id="loteTurma" value="<?= $h((int) $d['turma']) ?>">
              <button type="button" class="btn btn-primary" id="gerarConvitesTurma" data-progresso="progressoConvites"<?= $parado ? ' data-fase="passo"' : '' ?>><i class="fas fa-paper-plane"></i> <?= $parado ? 'Continuar os convites' : 'Gerar os convites e enviar por e-mail' ?></button>
              <div class="form-hint mt-1">Cada pessoa recebe um convite novo; o convite anterior dela, se havia, deixa de valer.</div>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="card" id="resultadoLote" hidden>
        <div class="card-header"><h3 class="card-title">Para o envio manual</h3></div>
        <div class="card-body">
          <p class="text-secondary" id="resumoLote"></p>
          <p class="small text-secondary">Estes links aparecem só agora, nesta tela. Se sair dela, gere o convite de novo pela ficha da pessoa.</p>
          <div id="listaManual" class="list-group"></div>
        </div>
      </div>
    </div>
  </section>
</div>
<?php
$progressoId = 'progressoConvites';
$progressoTitulo = 'Gerando os convites da turma';
$progressoGrupos = ['Os convites da turma' => max(1, count($d['pendentes']))];
$progressoUrl = URLADM . 'responsaveis/convidar-turma';
$progressoCampos = ['turma' => '#loteTurma'];
$progressoFim = ['Ver o resultado', '#resultadoLote'];
include 'app/adms/Views/include_tabler/_progresso.php';
?>
<script>
  (function () {
    var janela = document.getElementById('progressoConvites');
    if (!janela) { return; }
    var lista = document.getElementById('listaManual');
    var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
    janela.addEventListener('vc-progresso-passo', function (ev) {
      var r = ev.detail || {};
      if (r.manual) {
        var m = r.manual;
        var item = document.createElement('div');
        item.className = 'list-group-item vc-manual';
        item.setAttribute('data-responsavel', m.id);
        item.innerHTML = '<div class="fw-bold">' + esc(m.nome) + ' <span class="text-secondary small fw-normal">(' + esc(m.motivo) + ')</span></div>'
          + '<div class="input-group input-group-sm my-1"><input type="text" class="form-control vc-link" readonly value="' + esc(m.link) + '"><button type="button" class="btn btn-outline-primary vc-copiar">Copiar o link</button></div>'
          + (m.whatsapp ? '<a class="btn btn-sm btn-success" target="_blank" rel="noopener" href="' + esc(m.whatsapp) + '"><i class="fab fa-whatsapp"></i> Abrir no WhatsApp</a>' : '<span class="small text-secondary">sem celular: copie o link</span>');
        lista.appendChild(item);
      }
      if (r.concluida) {
        var res = document.getElementById('resultadoLote');
        res.hidden = false;
        var manuais = (r.manuais || []).length, recusados = (r.recusados || []);
        document.getElementById('resumoLote').textContent = 'Pronto: ' + (r.enviados || 0) + ' convite(s) enviado(s) por e-mail; ' + manuais + ' para o envio manual'
          + (recusados.length ? '; ' + recusados.length + ' não gerado(s): ' + recusados.map(function (x) { return x.nome + ' (' + x.motivo + ')'; }).join('; ') : '') + '.';
        if (!lista.children.length && manuais) {
          (r.manuais || []).forEach(function (m) {
            var it = document.createElement('div');
            it.className = 'list-group-item';
            it.innerHTML = esc(m.nome) + ' <span class="text-secondary small">(' + esc(m.motivo) + ') — gere o convite de novo pela ficha</span>';
            lista.appendChild(it);
          });
        }
      }
    });
    janela.addEventListener('click', function (ev) {
      var fim = ev.target.closest('.vc-progresso-fim');
      if (fim) { ev.preventDefault(); janela.hidden = true; document.getElementById('resultadoLote').scrollIntoView(); }
    });
    lista.addEventListener('click', function (ev) {
      var b = ev.target.closest('.vc-copiar');
      if (!b) { return; }
      var c = b.parentNode.querySelector('.vc-link');
      c.select();
      if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(c.value); } else { document.execCommand('copy'); }
      b.textContent = 'Copiado';
    });
  })();
</script>
