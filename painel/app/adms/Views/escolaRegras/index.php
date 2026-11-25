<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* As regras da escola: o padrão vem preenchido; a escola mantém ou ajusta. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$r = $this->dados['regras'];
$C = \App\adms\Models\escola\AdmsEscolaConfig::class;
$pode = (new \App\adms\Models\AdmsBotao())->valBotao(['salvar' => ['menu_controller' => 'escola-regras', 'menu_metodo' => 'salvar']]);
$so = empty($pode['salvar']) ? ' disabled' : '';
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Regras da escola<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <p class="text-secondary">As regras vêm prontas, com o padrão já preenchido. A escola mantém ou ajusta.</p>
      <form method="post" action="<?= URLADM ?>escola-regras/salvar" class="card" id="formRegrasEscola">
        <div class="card-header"><h3 class="card-title">Avaliação</h3></div>
        <div class="card-body">
          <div class="mb-3" style="max-width: 14rem"><label class="form-label" for="media_minima">Média mínima</label>
            <input type="text" inputmode="decimal" class="form-control" name="media_minima" id="media_minima" value="<?= $h(str_replace('.', ',', rtrim(rtrim($r['media.minima'], '0'), '.'))) ?>"<?= htmlspecialchars((string) ($so), ENT_QUOTES, 'UTF-8') ?>>
            <div class="form-hint">O padrão é 6. Abaixo dela, o aluno fica em recuperação na etapa; é também a média para a aprovação no ano.</div></div>
          <div class="mb-3"><label class="form-label">A nota da etapa depois da recuperação</label>
            <?php foreach ($C::RECUPERACAO as $k => $txt) : ?>
              <label class="form-check"><input type="radio" class="form-check-input" name="recuperacao_regra" value="<?= $h($k) ?>" id="recuperacao_<?= $h($k) ?>"<?= htmlspecialchars((string) ($r['recuperacao.regra'] === $k ? ' checked' : ''), ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars((string) ($so), ENT_QUOTES, 'UTF-8') ?>>
                <span class="form-check-label"><?= $h($txt) ?><?= $k === $C::PADRAO['recuperacao.regra'] ? ' <span class="text-secondary">(o padrão)</span>' : '' ?></span></label>
            <?php endforeach; ?>
          </div>
          <div class="mb-3"><label class="form-label">A média anual</label>
            <?php foreach ($C::COMPOSICAO as $k => $txt) : ?>
              <label class="form-check"><input type="radio" class="form-check-input" name="anual_composicao" value="<?= $h($k) ?>" id="composicao_<?= $h($k) ?>"<?= htmlspecialchars((string) ($r['anual.composicao'] === $k ? ' checked' : ''), ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars((string) ($so), ENT_QUOTES, 'UTF-8') ?>>
                <span class="form-check-label"><?= $h($txt) ?><?= $k === $C::PADRAO['anual.composicao'] ? ' <span class="text-secondary">(o padrão)</span>' : '' ?></span></label>
            <?php endforeach; ?>
            <div class="mt-2" style="max-width: 20rem"><label class="form-label" for="anual_pesos">Os pesos das etapas, na ordem</label>
              <input type="text" class="form-control" name="anual_pesos" id="anual_pesos" value="<?= $h($r['anual.pesos']) ?>" placeholder="1, 1, 2, 2"<?= htmlspecialchars((string) ($so), ENT_QUOTES, 'UTF-8') ?>>
              <div class="form-hint">Só com a média ponderada.</div></div>
          </div>
        </div>
        <div class="card-header"><h3 class="card-title">Frequência</h3></div>
        <div class="card-body">
          <div class="mb-3"><label class="form-label">Onde a frequência mínima é medida</label>
            <?php foreach ($C::FREQUENCIA as $k => $txt) : ?>
              <label class="form-check"><input type="radio" class="form-check-input" name="frequencia_base" value="<?= $h($k) ?>" id="frequencia_<?= $h($k) ?>"<?= htmlspecialchars((string) ($r['frequencia.base'] === $k ? ' checked' : ''), ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars((string) ($so), ENT_QUOTES, 'UTF-8') ?>>
                <span class="form-check-label"><?= $h($txt) ?><?= $k === $C::PADRAO['frequencia.base'] ? ' <span class="text-secondary">(o padrão)</span>' : '' ?></span></label>
            <?php endforeach; ?>
          </div>
          <div class="mb-3" id="fonteFaltas"><label class="form-label">De onde vêm as faltas</label>
            <?php foreach ($C::FONTES as $k => $txt) : ?>
              <label class="form-check"><input type="radio" class="form-check-input" name="frequencia_fonte" value="<?= $h($k) ?>" id="fonte_<?= $h($k) ?>"<?= htmlspecialchars((string) ($r['frequencia.fonte'] === $k ? ' checked' : ''), ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars((string) ($so), ENT_QUOTES, 'UTF-8') ?>>
                <span class="form-check-label"><?= $h($txt) ?><?= $k === $C::PADRAO['frequencia.fonte'] ? ' <span class="text-secondary">(o padrão)</span>' : '' ?></span></label>
            <?php endforeach; ?>
            <div class="form-hint">Vale para as disciplinas abertas que ainda não têm falta lançada nem chamada, e para as turmas criadas depois. O total do ano segue a mesma regra nas duas.</div>
          </div>
          <div style="max-width: 14rem"><label class="form-label" for="frequencia_minima">Frequência mínima (%)</label>
            <input type="text" inputmode="decimal" class="form-control" name="frequencia_minima" id="frequencia_minima" value="<?= $h(rtrim(rtrim(str_replace('.', ',', $r['frequencia.minima']), '0'), ',')) ?>"<?= htmlspecialchars((string) ($so), ENT_QUOTES, 'UTF-8') ?>>
            <div class="form-hint">O padrão é 75%.</div></div>
        </div>
        <div class="card-header"><h3 class="card-title">Responsáveis</h3></div>
        <div class="card-body" id="regrasResponsaveis">
          <div class="mb-3"><label class="form-label">O acesso acadêmico adicional</label>
            <label class="form-check"><input type="radio" class="form-check-input" name="responsaveis_adicional" value="1" id="adicional_sim"<?= $r['responsaveis.adicional'] === '1' ? ' checked' : '' ?><?= $h($so) ?>>
              <span class="form-check-label">Permitido: a escola pode liberar o acompanhamento acadêmico a outras pessoas, com o motivo <span class="text-secondary">(o padrão)</span></span></label>
            <label class="form-check"><input type="radio" class="form-check-input" name="responsaveis_adicional" value="0" id="adicional_nao"<?= $r['responsaveis.adicional'] === '0' ? ' checked' : '' ?><?= $h($so) ?>>
              <span class="form-check-label">Não permitido: só o titular e o financeiro acompanham (as liberações que existirem deixam de valer)</span></label></div>
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="responsaveis_adicional_limite">Acessos adicionais por aluno</label>
              <input type="number" min="0" max="99" class="form-control" name="responsaveis_adicional_limite" id="responsaveis_adicional_limite" value="<?= $h($r['responsaveis.adicional_limite']) ?>"<?= $h($so) ?>>
              <div class="form-hint">0 = sem limite (o padrão).</div></div>
            <div class="col-md-4"><label class="form-label" for="responsaveis_convite_dias">Validade do convite (dias)</label>
              <input type="number" min="1" max="30" class="form-control" name="responsaveis_convite_dias" id="responsaveis_convite_dias" value="<?= $h($r['responsaveis.convite_dias']) ?>"<?= $h($so) ?>>
              <div class="form-hint">O padrão é 7.</div></div>
          </div>
          <div class="mt-3"><label class="form-label" for="responsaveis_mensagem">A mensagem do convite (e-mail e WhatsApp)</label>
            <textarea class="form-control" name="responsaveis_mensagem" id="responsaveis_mensagem" rows="3" maxlength="255"<?= $h($so) ?>><?= $h($r['responsaveis.mensagem']) ?></textarea>
            <div class="form-hint">O sistema troca {nome} pelo nome da pessoa, {alunos} pelo primeiro nome dos alunos, {validade} pela data em que o link vence e {link} pelo link.</div></div>
        </div>
        <?php if (!$so) : ?><div class="card-footer"><button type="submit" class="btn btn-primary" id="salvarRegrasEscola">Salvar</button></div><?php endif; ?>
      </form>
    </div>
  </section>
</div>
