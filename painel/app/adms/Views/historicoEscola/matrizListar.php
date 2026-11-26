<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* A MATRIZ CURRICULAR DO HISTÓRICO (D-111): as matrizes por segmento e ano letivo, com os totais de cada série
   (aulas semanais · aulas anuais · horas), e a matriz nova (vazia ou copiando outra do mesmo nível). */
use App\adms\Models\historicoEscola\AdmsHistBase as B;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
?>
<div class="vc-pagina" id="historicoMatrizes">
  <?php $paginaTitulo = 'Matriz curricular do histórico';
  $paginaPretitulo = 'Histórico escolar';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <p class="text-secondary">A matriz de cada ano fica guardada como era: o histórico usa a carga da matriz do ano em que a série foi cursada. As aulas anuais e as horas são calculadas das aulas semanais (nunca digitadas). A carga em horas da grade (a da frequência) não muda.</p>
      <div class="card mb-3">
        <div class="table-responsive"><table class="table table-vcenter card-table" id="tabelaMatrizes">
          <thead><tr><th>Ano</th><th>Segmento</th><th>Séries (aulas semanais · anuais · horas)</th><th>Situação</th><th></th></tr></thead><tbody>
          <?php foreach ($d['matrizes'] as $m) : ?>
            <tr data-matriz="<?= (int) $m['id'] ?>"><td><?= (int) $m['ano'] ?></td><td><?= $h($m['curso']) ?><?= $m['prova'] ? ' <span class="badge bg-red-lt">PROVA</span>' : '' ?></td>
              <td class="small"><?php foreach ($m['totais'] as $t) : ?><div><?= $h($t['codnome']) ?>: <?= (int) $t['semanais'] ?> · <?= number_format($t['anuais'], 0, ',', '.') ?> · <?= $h($t['horas']) ?> h</div><?php endforeach; ?></td>
              <td><?= $m['situacao'] === 'travada' ? '<span class="badge bg-blue-lt">travada</span>' : 'rascunho' ?><?= $m['origem'] === 'copia' ? '<div class="small text-secondary">cópia</div>' : '' ?></td>
              <td><a class="btn btn-sm btn-outline-primary" href="<?= URLADM ?>historico-escola-matriz/editar/<?= (int) $m['id'] ?>">Abrir</a></td></tr>
          <?php endforeach; ?>
          <?php if (!$d['matrizes']) : ?><tr><td colspan="5" class="text-secondary">Nenhuma matriz cadastrada.</td></tr><?php endif; ?>
          </tbody></table></div>
      </div>
      <?php if ($d['pode']) : ?>
        <form method="post" action="<?= URLADM ?>historico-escola-matriz/criar" class="card" id="formNovaMatriz">
          <div class="card-header"><h3 class="card-title">Nova matriz</h3></div>
          <div class="card-body row g-3">
            <div class="col-md-5"><label class="form-label" for="nGrade">Segmento (grade)</label><select class="form-select" name="grade_id" id="nGrade">
              <?php foreach ($d['grades'] as $g) : ?><option value="<?= (int) $g['id'] ?>"><?= $h($g['curso'] . ' — grade ' . $g['versao'] . ' (' . B::NIVEIS[$g['nivel']] . ')') ?></option><?php endforeach; ?></select>
              <?php if (!$d['grades']) : ?><div class="form-hint">Marque antes o nível dos segmentos (Cadastros do histórico → Segmentos e séries).</div><?php endif; ?></div>
            <div class="col-md-2"><label class="form-label" for="nAno">Ano letivo</label><input type="number" class="form-control" name="ano" id="nAno" value="<?= (int) date('Y') ?>"></div>
            <div class="col-md-5"><label class="form-label" for="nCopiar">Copiar de</label><select class="form-select" name="copiar_de" id="nCopiar"><option value="0">— nova, vazia (as aulas propostas pela carga da grade) —</option>
              <?php foreach ($d['matrizes'] as $m) : ?><option value="<?= (int) $m['id'] ?>"><?= $h($m['curso'] . ' ' . $m['ano']) ?></option><?php endforeach; ?></select></div>
          </div>
          <div class="card-footer"><button type="submit" class="btn btn-primary" id="criarMatriz"><i class="fas fa-plus me-1"></i> Criar</button></div>
        </form>
      <?php endif; ?>
    </div>
  </section>
</div>
