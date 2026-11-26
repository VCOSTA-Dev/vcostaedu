<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* A VIDA ESCOLAR DO ALUNO (D-112): as séries do nível, em ordem cronológica — as propostas pelo sistema (das turmas do
   aluno), as confirmadas ("do sistema") e as digitadas (anos cursados em outra escola). Confirmar, editar, digitar e
   tirar; as notas de cada série; o registro de alterações. */
use App\adms\Models\historicoEscola\AdmsHistBase as B;
use App\adms\Models\historicoEscola\AdmsHistVida as V;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$a = $d['aluno'];
$nivel = $d['nivel'];
$pode = $d['pode'];
$base = URLADM . 'historico-escola-vida/';
$ed = null;
if (isset($_GET['editar']) || isset($_GET['turma'])) {
  foreach ($d['vida'] as $s) {
    if ((isset($_GET['editar']) && (int) $s['id'] === (int) $_GET['editar'] && $s['id']) || (isset($_GET['turma']) && !$s['id'] && (int) $s['turma_id'] === (int) $_GET['turma'])) { $ed = $s; }
  }
}
$nova = isset($_GET['nova']);
$form = $ed ?? ($nova ? ['id' => 0, 'turma_id' => 0, 'serie' => '', 'ano' => '', 'escola_id' => 0, 'resultado' => 'aprovado', 'religioso' => '', 'conselho' => 0, 'obs_chaves' => '', 'carga_total' => 0, 'itinerario' => 0] : null);
?>
<div class="vc-pagina" id="historicoVida">
  <?php $paginaTitulo = 'Vida escolar — ' . $h($a['nome']);
  $paginaPretitulo = 'RM ' . $h($a['rm']) . ' · ' . $h($d['situacao']) . ($a['prova'] ? ' · PROVA' : '');
  $paginaAcoesHtml = '<a href="' . URLADM . 'historico-escola-emissao/previa/' . (int) $a['id'] . '?nivel=' . $nivel . '" class="btn btn-primary" id="irEmissao"><i class="fas fa-file-signature me-1"></i> Prévia e emissão</a>'
    . '<a href="' . URLADM . 'historico-escola/aluno/' . (int) $a['id'] . '" class="btn btn-outline-secondary" id="dadosAluno">Dados do aluno (RA, certidão)</a>'
    . '<a href="' . $base . 'buscar?q=' . urlencode((string) $a['rm']) . '" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> A busca</a>';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="btn-group mb-3" role="group" aria-label="Nível">
        <?php foreach (B::NIVEIS as $k => $rot) : ?><a class="btn <?= $k === $nivel ? 'btn-primary' : 'btn-outline-primary' ?>" id="nivel-<?= $k ?>" href="<?= $base ?>aluno/<?= (int) $a['id'] ?>?nivel=<?= $k ?>"><?= $h($rot) ?></a><?php endforeach; ?>
      </div>
      <div class="card mb-3"><div class="table-responsive"><table class="table table-vcenter card-table" id="tabelaVida">
        <thead><tr><th>Ano</th><th>Série</th><th>Escola</th><th>Resultado</th><th>Origem</th><th></th></tr></thead><tbody>
        <?php foreach ($d['vida'] as $s) : $prop = !$s['id']; ?>
          <tr data-ano="<?= (int) $s['ano'] ?>" data-serie="<?= (int) $s['serie'] ?>" data-id="<?= (int) $s['id'] ?>" data-turma="<?= (int) $s['turma_id'] ?>">
            <td><?= (int) $s['ano'] ?></td>
            <td><?= $h(B::nomeSerie($nivel, (int) $s['serie'])) ?><?= $s['retencao'] ? ' <span class="badge bg-orange-lt">repetida</span>' : '' ?><?= !empty($s['turma']) ? '<div class="small text-secondary">' . $h($s['turma']) . '</div>' : '' ?></td>
            <td><?= $s['escola'] ? $h($s['escola'] . ' — ' . $s['municipio'] . '/' . $s['uf']) : '<span class="text-warning">sem escola</span>' ?></td>
            <td class="vc-resultado"><?= $h(V::RESULTADOS[(string) $s['resultado']] ?? '') ?><?= $s['conselho'] ? '<div class="small">pelo conselho de classe</div>' : '' ?><?= !empty($s['motivo']) ? '<div class="small text-warning">' . $h($s['motivo']) . '</div>' : '' ?></td>
            <td class="vc-origem"><?= $prop ? '<span class="badge bg-yellow-lt">proposta</span>' : ($s['origem'] === 'sistema' ? '<span class="badge bg-green-lt">do sistema</span>' : '<span class="badge bg-blue-lt">digitado</span>') ?></td>
            <td class="text-nowrap">
              <?php if ($prop && $pode['confirmar']) : ?><form method="post" action="<?= $base ?>confirmar/<?= (int) $a['id'] ?>" class="d-inline"><input type="hidden" name="turma_id" value="<?= (int) $s['turma_id'] ?>"><input type="hidden" name="nivel" value="<?= $nivel ?>"><button class="btn btn-sm btn-success vc-confirmar">Confirmar</button></form><?php endif; ?>
              <?php if ($pode['salvar']) : ?><a class="btn btn-sm btn-outline-secondary vc-editar" href="<?= $base ?>aluno/<?= (int) $a['id'] ?>?nivel=<?= $nivel ?>&<?= $prop ? 'turma=' . (int) $s['turma_id'] : 'editar=' . (int) $s['id'] ?>#formSerie">Editar</a><?php endif; ?>
              <a class="btn btn-sm btn-outline-primary vc-notas" href="<?= $base ?>notas/<?= (int) $a['id'] ?>?<?= $prop ? 'turma=' . (int) $s['turma_id'] : 'serie=' . (int) $s['id'] ?>">Notas</a>
              <?php if (!$prop && $pode['tirar']) : ?><form method="post" action="<?= $base ?>tirar/<?= (int) $a['id'] ?>" class="d-inline"><input type="hidden" name="serie_id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="nivel" value="<?= $nivel ?>"><button class="btn btn-sm btn-outline-danger vc-tirar">Tirar</button></form><?php endif; ?>
            </td></tr>
        <?php endforeach; ?>
        <?php if (!$d['vida']) : ?><tr><td colspan="6" class="text-secondary">Nenhuma série neste nível. O sistema propõe as séries pelas turmas do aluno; os anos cursados em outra escola são digitados.</td></tr><?php endif; ?>
        </tbody></table></div>
        <?php if ($pode['salvar']) : ?><div class="card-footer"><a class="btn btn-outline-primary" id="novaSerie" href="<?= $base ?>aluno/<?= (int) $a['id'] ?>?nivel=<?= $nivel ?>&nova=1#formSerie"><i class="fas fa-plus me-1"></i> Digitar uma série (ano cursado em outra escola)</a></div><?php endif; ?>
      </div>
      <?php if ($form && $pode['salvar']) : $digitada = !(int) $form['turma_id']; $marcadas = array_filter(explode(',', (string) $form['obs_chaves'])); ?>
        <form method="post" action="<?= $base ?>salvar-serie/<?= (int) $a['id'] ?>" class="card mb-3" id="formSerie">
          <input type="hidden" name="id" value="<?= (int) $form['id'] ?>"><input type="hidden" name="nivel" value="<?= $nivel ?>"><input type="hidden" name="turma_id" value="<?= (int) $form['turma_id'] ?>">
          <div class="card-header"><h3 class="card-title"><?= $form['id'] ? 'Editar a série' : ($digitada ? 'Digitar uma série' : 'Confirmar a série, com as correções') ?></h3></div>
          <div class="card-body row g-3">
            <div class="col-md-2"><label class="form-label" for="sSerie">Série</label><select class="form-select" name="serie" id="sSerie"<?= $digitada ? '' : ' disabled' ?>><?php for ($n = 1; $n <= B::SERIES[$nivel]; $n++) : ?><option value="<?= $n ?>"<?= (int) $form['serie'] === $n ? ' selected' : '' ?>><?= $h(B::nomeSerie($nivel, $n)) ?></option><?php endfor; ?></select></div>
            <div class="col-md-2"><label class="form-label" for="sAno">Ano</label><input type="number" class="form-control" name="ano" id="sAno" value="<?= $h($form['ano']) ?>"<?= $digitada ? '' : ' disabled' ?>></div>
            <div class="col-md-5"><label class="form-label" for="sEscola">Escola</label><select class="form-select" name="escola_id" id="sEscola"><option value="0">—</option><?php foreach ($d['escolas'] as $e) : ?><option value="<?= (int) $e['id'] ?>"<?= (int) $form['escola_id'] === (int) $e['id'] ? ' selected' : '' ?>><?= $h($e['nome'] . ' — ' . $e['municipio'] . '/' . $e['uf']) ?></option><?php endforeach; ?></select>
              <div class="form-hint">A escola vem do cadastro de escolas (Cadastros do histórico → Escolas).</div></div>
            <div class="col-md-3"><label class="form-label" for="sResultado">Resultado</label><select class="form-select" name="resultado" id="sResultado"><?php foreach (V::RESULTADOS as $k => $rot) : ?><option value="<?= $h($k) ?>"<?= (string) $form['resultado'] === $k ? ' selected' : '' ?>><?= $h($rot) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><label class="form-label" for="sReligioso">Ensino Religioso</label><input class="form-control" name="religioso" id="sReligioso" maxlength="5" value="<?= $h($form['religioso']) ?>" placeholder="nota, F, NF, -"></div>
            <div class="col-md-3"><label class="form-check mt-4"><input type="checkbox" class="form-check-input" name="conselho" value="1" id="sConselho"<?= $form['conselho'] ? ' checked' : '' ?>><span class="form-check-label">Promovido pelo conselho de classe</span></label></div>
            <?php if ($digitada) : ?><div class="col-md-3"><label class="form-label" for="sCarga">Carga total da série (aulas)</label><input type="number" class="form-control" name="carga_total" id="sCarga" value="<?= (int) $form['carga_total'] ?: '' ?>"><div class="form-hint">Vazia: a da matriz desta escola naquele ano.</div></div><?php endif; ?>
            <div class="col-md-3"><label class="form-label" for="sDataSaida">Data da saída (transferido)</label><input type="date" class="form-control" name="data_saida" id="sDataSaida" value="<?= $h((string) ($form['data_saida'] ?? '')) ?>"><div class="form-hint">Só no transferido; vazia, a da saída da turma.</div></div>
            <?php if ($nivel === 'EM') : ?><div class="col-md-2"><label class="form-label" for="sIt">Itinerário</label><input type="number" class="form-control" name="itinerario" id="sIt" value="<?= (int) $form['itinerario'] ?: '' ?>"></div><?php endif; ?>
            <?php if ($d['observacoes']) : ?><div class="col-12"><div class="form-label">Observações desta série</div><?php foreach ($d['observacoes'] as $o) : ?><label class="form-check form-check-inline"><input type="checkbox" class="form-check-input" name="obs[]" value="<?= $h($o['chave']) ?>"<?= in_array($o['chave'], $marcadas, true) ? ' checked' : '' ?>><span class="form-check-label"><?= $h($o['titulo']) ?></span></label><?php endforeach; ?></div><?php endif; ?>
          </div>
          <div class="card-footer"><button type="submit" class="btn btn-primary" id="salvarSerie"><i class="fas fa-save me-1"></i> Salvar</button></div>
        </form>
      <?php endif; ?>
      <div class="card"><div class="card-header"><h3 class="card-title">Registro de alterações</h3></div>
        <div class="table-responsive"><table class="table card-table" id="registroVida"><tbody>
        <?php foreach ($d['alteracoes'] as $x) : ?><tr><td class="text-nowrap"><?= $h(date('d/m/Y H:i', strtotime($x['quando']))) ?></td><td><?= $h($x['usuario'] ?? '—') ?></td><td><?= $h($x['texto']) ?></td></tr><?php endforeach; ?>
        <?php if (!$d['alteracoes']) : ?><tr><td class="text-secondary">Nada registrado ainda.</td></tr><?php endif; ?>
        </tbody></table></div></div>
    </div>
  </section>
</div>
