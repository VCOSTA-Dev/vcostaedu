<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Nova turma / editar a turma (escola). Na edição, o ano, a série e a unidade não mudam (as ofertas são deles). */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$f = $d['form'] ?? [];
$t = $d['turma'] ?? null;
$o = $d['opcoes'];
$v = fn($k) => $h($f[$k] ?? '');
?>
<div class="vc-pagina">
  <?php ob_start(); ?><?= $t ? 'Editar a turma ' . $h($t['codtur']) : 'Nova turma' ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <form method="post" class="card" id="formTurma" action="<?= htmlspecialchars((string) (URLADM . ($t ? 'turmas/editar/' . (int) $t['id'] : 'turmas/cadastrar')), ENT_QUOTES, 'UTF-8') ?>">
        <div class="card-body">
          <div class="row g-3">
            <?php if (!$t) : ?>
              <div class="col-md-3"><label class="form-label required" for="ano">Ano letivo</label>
                <select name="ano" id="ano" class="form-select" required>
                  <?php foreach ($o['anos'] as $a) : ?><option value="<?= (int) $a ?>"<?= htmlspecialchars((string) ((int) ($f['ano'] ?? 0) === (int) $a ? ' selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= (int) $a ?></option><?php endforeach; ?>
                </select></div>
              <?php $livreForm = \App\adms\Models\helper\AdmsTipoInstituicao::ehLivre(); /* D-106: no curso livre, o curso (a série é a interna, nunca exibida) e sem a unidade */ ?>
              <div class="col-md-5"><label class="form-label required" for="modulo_id"><?= $livreForm ? 'Curso' : 'Série' ?></label>
                <select name="modulo_id" id="modulo_id" class="form-select" required>
                  <option value="">Escolha…</option>
                  <?php foreach ($o['series'] as $s) : ?><option value="<?= htmlspecialchars((string) ((int) $s['id']), ENT_QUOTES, 'UTF-8') ?>"<?= htmlspecialchars((string) ((int) ($f['modulo_id'] ?? 0) === (int) $s['id'] ? ' selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($livreForm ? $s['segmento'] . ' (matriz ' . $s['versao'] . ')' : $s['segmento'] . ' — ' . $s['codnome'] . ' (matriz ' . $s['versao'] . ')') ?></option><?php endforeach; ?>
                </select>
                <div class="form-hint"><?= $livreForm ? 'Ao criar a turma, o sistema põe nela as disciplinas da matriz do curso; cada uma vira um curso no Moodle.' : 'Ao criar a turma, o sistema gera as ofertas da série: uma por disciplina da matriz.' ?></div></div>
              <div class="col-md-4"<?= $livreForm ? ' hidden' : '' ?>><label class="form-label" for="polo_id">Unidade</label>
                <select name="polo_id" id="polo_id" class="form-select">
                  <?php foreach ($o['unidades'] as $u) : ?><option value="<?= htmlspecialchars((string) ((int) $u['id']), ENT_QUOTES, 'UTF-8') ?>"<?= htmlspecialchars((string) ((int) ($f['polo_id'] ?? 0) === (int) $u['id'] ? ' selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($u['nome']) ?></option><?php endforeach; ?>
                </select></div>
            <?php else : ?>
              <div class="col-12 text-secondary">Ano letivo <?= htmlspecialchars((string) ((int) $t['ano']), ENT_QUOTES, 'UTF-8') ?> · <?= $h($t['segmento']) ?><?= \App\adms\Models\helper\AdmsTipoInstituicao::ehLivre() ? '' : ' · ' . $h($t['serie']) ?></div>
            <?php endif; ?>
            <div class="col-md-3"><label class="form-label required" for="nome">Nome da turma</label>
              <input type="text" name="nome" id="nome" class="form-control" maxlength="30" required value="<?= $v('nome') ?>" placeholder="A">
              <div class="form-hint">A turma aparece como a série e o nome: "6º ano A".</div></div>
            <div class="col-md-3"><label class="form-label" for="turno_id">Turno</label>
              <select name="turno_id" id="turno_id" class="form-select"><option value="">—</option>
                <?php foreach ($o['turnos'] as $tu) : ?><option value="<?= htmlspecialchars((string) ((int) $tu['id']), ENT_QUOTES, 'UTF-8') ?>"<?= htmlspecialchars((string) ((int) ($f['turno_id'] ?? 0) === (int) $tu['id'] ? ' selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($tu['nome']) ?></option><?php endforeach; ?>
              </select></div>
            <div class="col-md-3"><label class="form-label" for="sala">Sala</label><input type="text" name="sala" id="sala" class="form-control" maxlength="60" value="<?= $v('sala') ?>"></div>
            <div class="col-md-3"><label class="form-label" for="capacidade">Capacidade</label><input type="number" name="capacidade" id="capacidade" class="form-control" min="1" max="999" value="<?= $v('capacidade') ?>"></div>
          </div>
        </div>
        <div class="card-footer d-flex gap-2">
          <button type="submit" class="btn btn-primary" id="salvarTurma"><i class="fas fa-check"></i> <?= $t ? 'Salvar' : 'Criar a turma' ?></button>
          <a href="<?= htmlspecialchars((string) (URLADM . ($t ? 'turmas/ver/' . (int) $t['id'] : 'turmas/listar')), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-link">Cancelar</a>
        </div>
      </form>
    </div>
  </section>
</div>
