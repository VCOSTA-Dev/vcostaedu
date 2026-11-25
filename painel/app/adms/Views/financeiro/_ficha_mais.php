<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* As abas do alto Renegociações e Histórico (D-99). Renegociações (D-105): os acordos (efetivado, desfeito) e as propostas
   (salvo, salvo vencido, substituído), com o ID, a situação, a descrição, o total, a validade, a data e o usuário, e as
   ações Efetivar acordo, Desfazer renegociação e os impressos; Renegociar se faz pelo cartão Em lote da ficha. O
   Histórico (até a Parte 4) é o das operações com o atendimento em texto livre. Incluído pela ficha ($d, $a, $h, $envio, $brl, $dt). */
use App\adms\Models\financeiro\AdmsFinanceiro as F;

$podeM = (new \App\adms\Models\AdmsBotao())->valBotao([
  'desfazerRen' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'desfazer-renegociacao'],
  'atendimento' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'atendimento'],
  'efetivar' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'efetivar-proposta'],
  'impresso' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'impresso-renegociacao'],
]);
$N = \App\adms\Models\financeiro\AdmsFinOperacoes::NOMES;
$formasHist = F::FORMAS + ['multiplo' => 'Mais de uma forma'];
?>
<?php if ($d['ver'] === 'renegociacoes') :
  $RN = \App\adms\Models\financeiro\AdmsFinRenegociacao::class;
  $corRen = ['efetivado' => 'bg-purple-lt', 'desfeito' => 'bg-secondary-lt', 'salva' => 'bg-blue-lt', 'salva_vencida' => 'bg-orange-lt', 'substituida' => 'bg-secondary-lt']; ?>
<div class="card" id="renegociacoes">
  <div class="card-header"><h3 class="card-title">Renegociações</h3>
    <div class="card-actions"><span class="text-secondary small">Para renegociar: na Ficha Financeira, Em lote › Renegociar.</span></div></div>
  <?php if (empty($d['renegociacoes'])) : ?>
    <div class="card-body text-secondary" id="semRenegociacoes">Nenhum acordo nem proposta deste aluno.</div>
  <?php else : ?>
  <div class="table-responsive">
    <table class="table table-vcenter card-table table-sm" id="tabelaRenegociacoes">
      <thead><tr><th>ID</th><th>Situação</th><th>Descrição</th><th class="text-end">Total</th><th>Validade</th><th>Data</th><th>Usuário</th><th class="text-end">Ações</th></tr></thead>
      <tbody>
      <?php foreach ($d['renegociacoes'] as $r) : $ehAcordo = $r['tipo'] === 'acordo'; ?>
        <tr class="vc-linha-ren" data-tipo="<?= $h($r['tipo']) ?>" data-situacao="<?= $h($r['situacao']) ?>" <?= $ehAcordo ? 'data-renegociacao="' . $h($r['id']) . '"' : 'data-proposta="' . $h($r['id']) . '"' ?>>
          <td class="text-nowrap"><?= $ehAcordo ? 'Acordo nº ' : 'Proposta nº ' ?><?= $h($r['numero']) ?></td>
          <td><span class="badge <?= $h($corRen[$r['situacao']] ?? 'bg-secondary-lt') ?> vc-situacao-ren"><?= $h($RN::ROTULOS[$r['situacao']] ?? $r['situacao']) ?></span>
            <?php if ($r['situacao'] === 'desfeito' && $r['motivo_desfazer']) : ?><div class="small text-secondary"><?= $h($r['motivo_desfazer']) ?></div><?php endif; ?></td>
          <td class="small"><?= $h($r['descricao']) ?></td>
          <td class="text-end text-nowrap"><?= $h($brl($r['total'])) ?></td>
          <td class="text-nowrap"><?= $r['validade'] ? $h($dt($r['validade'])) : '—' ?></td>
          <td class="text-nowrap"><?= $h($dt($r['data'])) ?></td>
          <td class="small"><?= $h($r['usuario'] ?? '') ?></td>
          <td class="text-end">
            <div class="dropdown">
              <button type="button" class="btn btn-sm btn-primary dropdown-toggle vc-acoes-ren" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">Ações</button>
              <div class="dropdown-menu dropdown-menu-end">
                <?php $algum = false; ?>
                <?php if (!$ehAcordo && $r['situacao'] === 'salva' && !empty($podeM['efetivar'])) : $algum = true; ?>
                  <button type="button" class="dropdown-item vc-item-efetivar" data-vc-proposta="<?= $h($r['id']) ?>"><i class="fas fa-check me-2"></i> Efetivar acordo</button>
                <?php endif; ?>
                <?php if ($ehAcordo && $r['situacao'] === 'efetivado' && !empty($podeM['desfazerRen'])) : $algum = true; ?>
                  <button type="button" class="dropdown-item vc-item-desfazer-ren" data-vc-renegociacao="<?= $h($r['id']) ?>" data-vc-numero="<?= $h($r['numero']) ?>"><i class="fas fa-undo me-2"></i> Desfazer renegociação</button>
                <?php endif; ?>
                <?php if (!empty($podeM['impresso'])) : ?>
                  <?php if (!$ehAcordo || $r['proposta_id']) : $algum = true; ?>
                    <a class="dropdown-item vc-item-imprimir-proposta" target="_blank" href="<?= URLADM ?>financeiro/impresso-renegociacao/<?= $h($ehAcordo ? $r['proposta_id'] : $r['id']) ?>?tipo=proposta"><i class="far fa-file-pdf me-2"></i> Proposta de acordo</a>
                  <?php endif; ?>
                  <?php if ($ehAcordo) : $algum = true; ?>
                    <a class="dropdown-item vc-item-imprimir-acordo" target="_blank" href="<?= URLADM ?>financeiro/impresso-renegociacao/<?= $h($r['id']) ?>?tipo=acordo"><i class="far fa-file-pdf me-2"></i> Acordo efetivado</a>
                    <a class="dropdown-item vc-item-imprimir-termo" target="_blank" href="<?= URLADM ?>financeiro/impresso-renegociacao/<?= $h($r['id']) ?>?tipo=termo"><i class="far fa-file-pdf me-2"></i> Termo de confissão de dívida</a>
                  <?php endif; ?>
                <?php endif; ?>
                <?php if (!$algum) : ?><span class="dropdown-item-text text-secondary">Nenhuma ação disponível</span><?php endif; ?>
              </div>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- EFETIVAR ACORDO: a condição da proposta salva (sem refazer a conta) -->
<div class="modal modal-blur fade" id="modalEfetivar" tabindex="-1" aria-labelledby="modalEfetivarNome" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form method="post" class="modal-content" id="formEfetivar" data-vc-confirma="Efetivar o acordo">
      <div class="modal-header"><h5 class="modal-title" id="modalEfetivarNome">Efetivar acordo</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
      <div class="modal-body">
        <input type="hidden" name="envio" value="<?= $h($envio()) ?>"><input type="hidden" name="voltar" value="ver=renegociacoes">
        <div class="mb-2">Qual condição a família escolheu? Os valores são os da proposta (a conta do dia dela).</div>
        <label class="form-check"><input class="form-check-input" type="radio" name="condicao" value="parcelada" checked> <span class="form-check-label">Parcelada</span></label>
        <label class="form-check"><input class="form-check-input" type="radio" name="condicao" value="avista"> <span class="form-check-label">À vista</span></label>
      </div>
      <div class="modal-footer"><button type="button" class="btn" data-bs-dismiss="modal">Voltar</button><button type="submit" class="btn btn-success" id="gravarEfetivar">Efetivar</button></div>
    </form>
  </div>
</div>

<!-- DESFAZER RENEGOCIAÇÃO: o motivo da lista e a observação -->
<div class="modal modal-blur fade" id="modalDesfazerRen" tabindex="-1" aria-labelledby="modalDesfazerRenNome" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form method="post" class="modal-content" id="formDesfazerRen" data-vc-confirma="Desfazer a renegociação">
      <div class="modal-header"><h5 class="modal-title" id="modalDesfazerRenNome">Desfazer renegociação</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
      <div class="modal-body">
        <input type="hidden" name="envio" value="<?= $h($envio()) ?>"><input type="hidden" name="voltar" value="ver=renegociacoes">
        <div class="mb-2 small text-secondary">Os títulos originais voltam a aberto e as parcelas do acordo em aberto são canceladas. Se alguma parcela do acordo já foi recebida ou baixada, desfaça isso antes.</div>
        <label class="form-label required" for="motivoDesfazerRen">Motivo</label>
        <select class="form-select mb-2" name="motivo_id" id="motivoDesfazerRen" required>
          <?php foreach ($d['motivosDesfazerRen'] ?? [] as $mid => $mnome) : ?><option value="<?= $h($mid) ?>"><?= $h($mnome) ?></option><?php endforeach; ?>
        </select>
        <label class="form-label required" for="observacaoDesfazerRen">Observação</label>
        <input type="text" class="form-control" name="observacao" id="observacaoDesfazerRen" minlength="5" maxlength="360" required>
      </div>
      <div class="modal-footer"><button type="button" class="btn" data-bs-dismiss="modal">Voltar</button><button type="submit" class="btn btn-warning" id="gravarDesfazerRen">Desfazer</button></div>
    </form>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.addEventListener('click', function (ev) {
    var e = ev.target.closest('.vc-item-efetivar');
    if (e) {
      var f = document.getElementById('formEfetivar');
      f.action = '<?= URLADM ?>financeiro/efetivar-proposta/' + e.dataset.vcProposta;
      f.dataset.vcConfirma = 'Efetivar a proposta nº ' + e.dataset.vcProposta;
      document.getElementById('modalEfetivarNome').textContent = 'Efetivar a proposta nº ' + e.dataset.vcProposta;
      window.vcAbrirModal('modalEfetivar');
      return;
    }
    var x = ev.target.closest('.vc-item-desfazer-ren');
    if (x) {
      var g = document.getElementById('formDesfazerRen');
      g.action = '<?= URLADM ?>financeiro/desfazer-renegociacao/' + x.dataset.vcRenegociacao;
      g.dataset.vcConfirma = 'Desfazer o acordo nº ' + x.dataset.vcNumero;
      g.querySelector('[name="observacao"]').value = '';
      document.getElementById('modalDesfazerRenNome').textContent = 'Desfazer o acordo nº ' + x.dataset.vcNumero;
      window.vcAbrirModal('modalDesfazerRen');
    }
  });
  document.getElementById('formEfetivar').addEventListener('submit', function () {
    var c = this.querySelector('[name="condicao"]:checked');
    this.dataset.vcConfirmaExtra = 'A condição: ' + (c && c.value === 'avista' ? 'à vista' : 'parcelada');
  }, true);
  document.getElementById('formDesfazerRen').addEventListener('submit', function () {
    var s = this.querySelector('[name="motivo_id"]');
    this.dataset.vcConfirmaExtra = 'O motivo: ' + s.options[s.selectedIndex].text.replace(/</g, '&lt;') + ' · ' + this.querySelector('[name="observacao"]').value.replace(/</g, '&lt;');
  }, true);
});
</script>
<?php endif; ?>

<?php if ($d['ver'] === 'historico') : ?>
<div class="card" id="historicoFinanceiro">
  <div class="card-header"><h3 class="card-title">Histórico</h3></div>
  <?php if (!empty($podeM['atendimento'])) : ?>
  <form method="post" action="<?= URLADM ?>financeiro/atendimento/<?= $h((int) $a['id']) ?>" class="card-body row g-2 border-bottom" id="formAtendimento">
    <input type="hidden" name="envio" value="<?= $h($envio()) ?>">
    <div class="col-sm-2"><label class="form-label">Data</label><input type="date" class="form-control form-control-sm" name="data" value="<?= $h(date('Y-m-d')) ?>" max="<?= $h(date('Y-m-d')) ?>" required></div>
    <div class="col-sm-3"><label class="form-label">Título <small class="text-muted">(opcional)</small></label><select class="form-select form-select-sm" name="titulo_id"><option value="">—</option>
      <?php foreach ($d['todosTitulos'] ?? [] as $t) : ?><option value="<?= $h($t['id']) ?>"><?= $h($t['id'] . ' · ' . $t['descricao']) ?></option><?php endforeach; ?></select></div>
    <div class="col-sm-5"><label class="form-label">O que foi conversado e combinado</label><input type="text" class="form-control form-control-sm" name="texto" minlength="5" maxlength="500" required></div>
    <div class="col-sm-2 d-flex align-items-end"><button type="submit" class="btn btn-sm btn-outline-primary w-100" id="registrarAtendimento">Registrar</button></div>
  </form>
  <?php endif; ?>
  <div class="table-responsive">
    <table class="table table-vcenter card-table table-sm" id="tabelaHistorico">
      <thead><tr><th>Quando</th><th>O quê</th><th>Título</th><th>Detalhe</th><th>Quem</th></tr></thead>
      <tbody>
      <?php if (empty($d['historico'])) : ?><tr><td colspan="5" class="text-secondary">Nada ainda.</td></tr><?php endif; ?>
      <?php foreach ($d['historico'] ?? [] as $o) :
        $det = $o['tipo'] === 'receber' ? $brl((int) $o['recebido_centavos']) . ' · ' . ($formasHist[$o['forma']] ?? $o['forma']) . ' · ' . $dt($o['data_operacao'])
          . ((int) $o['multa_centavos'] + (int) $o['juros_centavos'] ? ' (encargos R$ ' . F::brl((int) $o['multa_centavos'] + (int) $o['juros_centavos']) . ')' : '')
          . ($o['observacao'] ? ' · ' . $o['observacao'] : '')
          : (string) ($o['motivo'] ?: $o['observacao']); ?>
        <tr data-tipo="<?= $h($o['tipo']) ?>" class="<?= $o['desfeita_em'] ? 'text-secondary' : '' ?>">
          <td class="text-nowrap"><?= $h(date('d/m/Y H:i', strtotime((string) $o['criado_em']))) ?></td>
          <td><?= $h($N[$o['tipo']] ?? $o['tipo']) ?><?= $o['desfeita_em'] ? ' <span class="badge bg-secondary-lt">desfeito</span>' : '' ?></td>
          <td><?= $h(($o['titulo_id'] ? $o['titulo_id'] . ' · ' : '') . ($o['descricao'] ?? '')) ?></td><td><?= $h($det) ?></td><td><?= $h($o['usuario'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
