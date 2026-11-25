<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 2, Etapa 3: o relatório da auditoria de notas e faltas (só leitura). */
use App\adms\Models\auditoria\AdmsAuditoriaConsulta as C;

$f = $this->dados['filtros'] ?? [];
$op = $this->dados['opcoes'] ?? [];
$linhas = $this->dados['linhas'] ?? [];
$e = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$sel = fn($k, $v) => isset($f[$k]) && (string) $f[$k] === (string) $v ? ' selected' : '';
$valor = static function (?string $v, string $campo): string {
  if ($v === null) {
    return '—';
  }
  return $campo === 'nota' ? str_replace('.', ',', $v) : $v;
};
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Auditoria de notas e faltas<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="card shadow mb-3">
        <div class="card-body">
          <form method="get" action="<?= URLADM ?>auditoria/listar" class="row g-2 align-items-end" id="filtrosAuditoria">
            <div class="col-md-3">
              <label class="form-label" for="curso">Curso</label>
              <select name="curso" id="curso" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($op['cursos'] ?? [] as $c) : ?><option value="<?= htmlspecialchars((string) ((int) $c['id']), ENT_QUOTES, 'UTF-8') ?>"<?= $sel('curso', $c['id']) ?>><?= $e($c['nome']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label" for="oferta">Disciplina</label>
              <select name="oferta" id="oferta" class="form-select">
                <option value="">Todas</option>
                <?php foreach ($op['ofertas'] ?? [] as $o) : ?><option value="<?= htmlspecialchars((string) ((int) $o['id']), ENT_QUOTES, 'UTF-8') ?>"<?= $sel('oferta', $o['id']) ?>><?= $e($o['materia'] . ' — ' . $o['codtur'] . ' ' . $o['ano']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label" for="aluno">Aluno (<?= \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero() ?> ou nome)</label>
              <input type="text" name="aluno" id="aluno" class="form-control" value="<?= $e($f['aluno'] ?? '') ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label" for="usuario">Quem lançou</label>
              <select name="usuario" id="usuario" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($op['usuarios'] ?? [] as $u) : ?><option value="<?= htmlspecialchars((string) ((int) $u['id']), ENT_QUOTES, 'UTF-8') ?>"<?= $sel('usuario', $u['id']) ?>><?= $e($u['nome']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label" for="tipo">O quê</label>
              <select name="tipo" id="tipo" class="form-select">
                <option value="">Tudo</option>
                <?php foreach (C::TIPOS as $k => $n) : ?><option value="<?= htmlspecialchars((string) ($k), ENT_QUOTES, 'UTF-8') ?>"<?= $sel('tipo', $k) ?>><?= $e($n) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label" for="de">De</label>
              <input type="date" name="de" id="de" class="form-control" value="<?= $e($f['de'] ?? '') ?>">
            </div>
            <div class="col-md-2">
              <label class="form-label" for="ate">Até</label>
              <input type="date" name="ate" id="ate" class="form-control" value="<?= $e($f['ate'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-check mb-2">
                <input type="checkbox" name="so_alteracoes" value="1" class="form-check-input" id="soAlteracoes"<?= htmlspecialchars((string) (!empty($f['so_alteracoes']) ? ' checked' : ''), ENT_QUOTES, 'UTF-8') ?>>
                <span class="form-check-label">Só alterações e exclusões</span>
              </label>
            </div>
            <div class="col-md-auto">
              <button type="submit" class="btn btn-primary" id="filtrar"><i class="fas fa-filter"></i> Filtrar</button>
              <a href="<?= URLADM ?>auditoria/listar" class="btn btn-outline-secondary">Limpar</a>
            </div>
          </form>
        </div>
      </div>
      <div class="card shadow">
        <div class="card-header">
          <h3 class="card-title" id="totalAuditoria"><?= count($linhas) ?> registro(s)<?= count($linhas) >= (int) $this->dados['limite'] ? ' (os mais recentes; refine os filtros para ver os demais)' : '' ?></h3>
        </div>
        <div class="card-body table-responsive">
          <table id="tabelaAuditoria" class="table table-sm table-striped table-hover table-compacta lista-dados" data-titulo="Auditoria de notas e faltas" data-ordem='[[0,"desc"]]'>
            <thead><tr><th>Quando</th><th>O quê</th><th>Ação</th><th>Disciplina</th><th>Avaliação ou aula</th><th>Aluno</th><th>Antes</th><th>Depois</th><th>Quem</th><th>Perfil</th><th>Origem</th><th>IP</th><th>Navegador</th><th>Detalhe</th></tr></thead>
            <tbody>
              <?php foreach ($linhas as $l) : ?>
                <tr data-id="<?= htmlspecialchars((string) ((int) $l['id']), ENT_QUOTES, 'UTF-8') ?>">
                  <td data-order="<?= $e($l['ocorrido_em']) ?>"><?= $e(date('d/m/Y H:i:s', strtotime(substr((string) $l['ocorrido_em'], 0, 19)))) ?></td>
                  <td><?= $e(C::TIPOS[$l['tipo']] ?? $l['tipo']) ?></td>
                  <td><?= $e(C::ACOES[$l['acao']] ?? $l['acao']) ?><?= (int) $l['apos_fechamento'] === 1 ? ' <span class="badge bg-warning-lt" title="A disciplina já tinha sido encerrada antes">depois do fechamento</span>' : '' ?></td>
                  <td><?= $e(trim(($l['materia'] ?? '') . ' — ' . ($l['codtur'] ?? '') . ' ' . ($l['ano'] ?? ''), ' —')) ?></td>
                  <td><?= $e($l['atividade'] ?? ($l['data_aula'] ? date('d/m/Y', strtotime($l['data_aula'])) : '')) ?></td>
                  <td><?= $e(trim(($l['rm'] ?? '') . ' ' . ($l['aluno_nome'] ?? ''))) ?></td>
                  <td><?= $e($valor($l['valor_anterior'], $l['campo'])) ?></td>
                  <td><?= $e($valor($l['valor_novo'], $l['campo'])) ?></td>
                  <td><?= $e($l['usuario_nome']) ?></td>
                  <td><?= $e($l['perfil']) ?></td>
                  <td><?= $e(C::ORIGENS[$l['origem']] ?? $l['origem']) ?></td>
                  <td><?= $e($l['ip']) ?></td>
                  <td><?= $e($l['navegador']) ?></td>
                  <td><?= $e($l['detalhe']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer text-secondary small">
          O registro é só de acréscimo: ninguém altera nem apaga uma linha da auditoria, nem o administrador. O IP é dado pessoal — veja o guia "Auditoria de notas e faltas".
        </div>
      </div>
    </div>
  </section>
</div>
