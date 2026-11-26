<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* A MATRIZ DO ANO (D-111): os parâmetros (semanas, minutos, os textos da folha, a data da folha), os itinerários (o
   Médio), as linhas da grade por série com as aulas semanais, o grupo, a área e o itinerário, e os totais ao vivo.
   Travada, cada correção pede o motivo. */
use App\adms\Models\historicoEscola\AdmsHistBase as B;
use App\adms\Models\historicoEscola\AdmsHistMatriz as M;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$m = $d['m'];
$pode = $d['pode'];
$dis = $pode['salvar'] ? '' : ' disabled';
$travada = $m['situacao'] === 'travada';
$em = $m['nivel'] === 'EM';
$grupos = M::GRUPOS[$m['nivel']];
$porSerie = [];
foreach ($m['itens'] as $i) {
  $porSerie[(int) $i['modulo_id']][] = $i;
}
?>
<div class="vc-pagina" id="historicoMatriz">
  <?php $paginaTitulo = 'Matriz ' . $h($m['curso']) . ' — ' . (int) $m['ano'];
  $paginaPretitulo = 'Matriz curricular do histórico' . ($m['prova'] ? ' · PROVA' : '');
  $paginaAcoesHtml = '<a href="' . URLADM . 'historico-escola-matriz/folha/' . (int) $m['id'] . '" target="_blank" class="btn btn-outline-primary" id="abrirFolha"><i class="fas fa-file-pdf me-1"></i> Folha da matriz</a>'
    . '<a href="' . URLADM . 'historico-escola-matriz/listar" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> As matrizes</a>';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if ($travada) : ?><div class="alert alert-info" id="avisoTravada">A matriz está travada (desde <?= $h(date('d/m/Y H:i', strtotime((string) $m['travada_em']))) ?>): cada correção pede o motivo, que fica no registro de alterações com o antes e o depois.</div><?php endif; ?>
      <form method="post" action="<?= URLADM ?>historico-escola-matriz/salvar/<?= (int) $m['id'] ?>" id="formMatriz">
        <div class="card mb-3"><div class="card-header"><h3 class="card-title">Parâmetros e textos da folha</h3></div>
          <div class="card-body row g-3">
            <div class="col-md-2"><label class="form-label" for="mSemanas">Semanas letivas</label><input type="number" class="form-control" name="semanas" id="mSemanas" value="<?= (int) $m['semanas'] ?>"<?= $dis ?>></div>
            <div class="col-md-2"><label class="form-label" for="mMinutos">Duração da aula (min)</label><input type="number" class="form-control" name="minutos" id="mMinutos" value="<?= (int) $m['minutos'] ?>"<?= $dis ?>></div>
            <div class="col-md-5"><label class="form-label" for="mPeriodo">Período e horário</label><input class="form-control" name="periodo" id="mPeriodo" value="<?= $h($m['periodo']) ?>"<?= $dis ?>></div>
            <div class="col-md-3"><label class="form-label" for="mData">Data da folha</label><input type="date" class="form-control" name="data_folha" id="mData" value="<?= $h($m['data_folha']) ?>"<?= $dis ?>><div class="form-hint">Vazia: a data de hoje.</div></div>
            <div class="col-md-6"><label class="form-label" for="mFundamento">Fundamento legal</label><textarea class="form-control" rows="2" name="fundamento" id="mFundamento"<?= $dis ?>><?= $h($m['fundamento']) ?></textarea></div>
            <div class="col-md-6"><label class="form-label" for="mAutorizacao">Autorização</label><textarea class="form-control" rows="2" name="autorizacao" id="mAutorizacao"<?= $dis ?>><?= $h($m['autorizacao']) ?></textarea></div>
          </div></div>
        <?php if ($em) : $its = $m['itinerarios']; $its[] = ['numero' => '', 'nome' => '']; $its[] = ['numero' => '', 'nome' => '']; ?>
          <div class="card mb-3"><div class="card-header"><h3 class="card-title">Itinerários do ano</h3></div><div class="card-body" id="itinerarios">
            <?php foreach ($its as $k => $it) : ?><div class="d-flex gap-2 mb-2"><input type="number" class="form-control" style="width: 6rem" name="it_numero[<?= $k ?>]" value="<?= $h($it['numero']) ?>" placeholder="nº" aria-label="Número do itinerário"<?= $dis ?>><input class="form-control" name="it_nome[<?= $k ?>]" value="<?= $h($it['nome']) ?>" placeholder="nome do itinerário" aria-label="Nome do itinerário"<?= $dis ?>></div><?php endforeach; ?>
            <div class="form-hint">0 = o itinerário comum (todos cursam); 1, 2… = os formativos.</div></div></div>
        <?php endif; ?>
        <?php foreach ($m['series'] as $s) : $t = $d['totais'][(int) $s['id']] ?? ['semanais' => 0, 'anuais' => 0, 'horas' => '0']; ?>
          <div class="card mb-3 vc-serie" data-serie="<?= (int) $s['id'] ?>"><div class="card-header"><h3 class="card-title"><?= $h($s['codnome']) ?></h3>
            <div class="card-actions small" id="total-<?= (int) $s['id'] ?>"><b class="vc-sem"><?= (int) $t['semanais'] ?></b> aulas semanais · <b class="vc-anu"><?= number_format($t['anuais'], 0, ',', '.') ?></b> aulas anuais · <b class="vc-hor"><?= $h($t['horas']) ?></b> horas</div></div>
            <div class="table-responsive"><table class="table table-vcenter card-table table-sm">
              <thead><tr><th>Disciplina</th><th class="text-center">Carga da grade (h)</th><th>Aulas semanais</th><th>Grupo</th><?php if ($em) : ?><th>Área</th><th>Itinerário</th><?php endif; ?><th>Ordem</th></tr></thead><tbody>
              <?php foreach ($porSerie[(int) $s['id']] ?? [] as $i) : $l = (int) $i['linha_id']; ?>
                <tr data-linha="<?= $l ?>"><td><?= $h($i['materia']) ?></td><td class="text-center text-secondary"><?= (int) $i['cargahoraria'] ?></td>
                  <td><input type="number" min="0" max="60" class="form-control form-control-sm vc-aulas" style="width: 5rem" name="aulas[<?= $l ?>]" value="<?= (int) $i['aulas_semanais'] ?>" aria-label="Aulas semanais de <?= $h($i['materia']) ?>"<?= $dis ?>></td>
                  <td><select class="form-select form-select-sm vc-grupo" name="grupo[<?= $l ?>]" aria-label="Grupo"<?= $dis ?>><?php foreach ($grupos as $k => $rot) : ?><option value="<?= $k ?>"<?= $i['grupo'] === $k ? ' selected' : '' ?>><?= $h($rot) ?></option><?php endforeach; ?></select></td>
                  <?php if ($em) : ?>
                    <td><select class="form-select form-select-sm" name="area[<?= $l ?>]" aria-label="Área"<?= $dis ?>><option value="0">—</option><?php foreach ($d['areas'] as $a) : ?><option value="<?= (int) $a['id'] ?>"<?= (int) $i['area_id'] === (int) $a['id'] ? ' selected' : '' ?>><?= $h($a['nome']) ?></option><?php endforeach; ?></select></td>
                    <td><input type="number" min="0" max="9" class="form-control form-control-sm vc-it" style="width: 4.5rem" name="itinerario[<?= $l ?>]" value="<?= (int) $i['itinerario'] ?: '' ?>" aria-label="Itinerário"<?= $dis ?>></td>
                  <?php endif; ?>
                  <td><input type="number" min="0" class="form-control form-control-sm" style="width: 4.5rem" name="ordem[<?= $l ?>]" value="<?= (int) $i['ordem'] ?: '' ?>" aria-label="Ordem"<?= $dis ?>></td></tr>
              <?php endforeach; ?>
              </tbody></table></div></div>
        <?php endforeach; ?>
        <?php if ($pode['salvar']) : ?>
          <div class="card mb-3"><div class="card-body d-flex flex-wrap gap-2 align-items-end">
            <?php if ($travada) : ?><div class="flex-grow-1"><label class="form-label" for="mMotivo">Motivo da correção</label><input class="form-control" name="motivo" id="mMotivo" maxlength="500"></div><?php endif; ?>
            <button type="submit" class="btn btn-primary" id="salvarMatriz"><i class="fas fa-save me-1"></i> Salvar</button>
          </div></div>
        <?php endif; ?>
      </form>
      <?php if ($pode['travar'] && !$travada) : ?>
        <form method="post" action="<?= URLADM ?>historico-escola-matriz/travar/<?= (int) $m['id'] ?>" class="mb-3"><button class="btn btn-outline-warning" id="travarMatriz"><i class="fas fa-lock me-1"></i> Travar a matriz (o ano está em uso)</button></form>
      <?php endif; ?>
      <?php if ($em && $pode['area']) : ?>
        <form method="post" action="<?= URLADM ?>historico-escola-matriz/salvar-area" class="card card-body mb-3 d-flex flex-row gap-2 align-items-end" id="formArea">
          <input type="hidden" name="matriz" value="<?= (int) $m['id'] ?>">
          <div class="flex-grow-1"><label class="form-label" for="aNome">Nova área de conhecimento</label><input class="form-control" name="nome" id="aNome"></div>
          <div><label class="form-label" for="aOrdem">Ordem</label><input type="number" class="form-control" style="width: 6rem" name="ordem" id="aOrdem" value="<?= count($d['areas']) + 1 ?>"></div>
          <button class="btn btn-outline-primary" id="salvarArea">Incluir</button>
        </form>
      <?php endif; ?>
      <div class="card"><div class="card-header"><h3 class="card-title">Registro de alterações</h3></div>
        <div class="table-responsive"><table class="table card-table" id="registroMatriz"><tbody>
        <?php foreach ($d['alteracoes'] as $x) : ?><tr><td class="text-nowrap"><?= $h(date('d/m/Y H:i', strtotime($x['quando']))) ?></td><td><?= $h($x['usuario'] ?? '—') ?></td><td><?= $h($x['texto']) ?></td></tr><?php endforeach; ?>
        <?php if (!$d['alteracoes']) : ?><tr><td class="text-secondary">Nada registrado ainda.</td></tr><?php endif; ?>
        </tbody></table></div></div>
    </div>
  </section>
</div>
<script>
/* os totais ao vivo (os mesmos do servidor: os itinerários formativos contam um só, o de mais aulas) */
(function () {
  var sem = document.getElementById('mSemanas'), min = document.getElementById('mMinutos');
  var fmt = function (n) { return n.toLocaleString('pt-BR'); };
  function recalcular() {
    document.querySelectorAll('.vc-serie').forEach(function (c) {
      var base = 0, its = {};
      c.querySelectorAll('tr[data-linha]').forEach(function (tr) {
        var a = parseInt(tr.querySelector('.vc-aulas').value, 10) || 0, g = tr.querySelector('.vc-grupo').value, it = tr.querySelector('.vc-it');
        if (g === 'IT_FORMATIVO') { var k = it ? (parseInt(it.value, 10) || 0) : 0; its[k] = (its[k] || 0) + a; } else { base += a; }
      });
      var mx = 0; Object.keys(its).forEach(function (k) { mx = Math.max(mx, its[k]); });
      var s = base + mx, an = s * (parseInt(sem.value, 10) || 0), dec = Math.floor((an * (parseInt(min.value, 10) || 0) * 10 + 30) / 60);
      c.querySelector('.vc-sem').textContent = s;
      c.querySelector('.vc-anu').textContent = fmt(an);
      c.querySelector('.vc-hor').textContent = dec % 10 === 0 ? fmt(dec / 10) : (dec / 10).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
    });
  }
  document.getElementById('formMatriz').addEventListener('input', recalcular);
})();
</script>
