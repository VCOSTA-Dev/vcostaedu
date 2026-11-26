<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* AS NOTAS DA SÉRIE (D-112): cursada aqui — a média do sistema (lida na hora, pela função única) e a sobreposta;
   digitada — a média e a carga de cada disciplina, e a disciplina criada na hora. */
use App\adms\Models\historicoEscola\AdmsHistBase as B;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$a = $d['aluno'];
$s = $d['serie'];
$q = $d['quadro'];
$aqui = (int) $s['turma_id'] > 0;
$gravada = (int) $s['id'] > 0;
$pode = $d['pode'] && $gravada;
?>
<div class="vc-pagina" id="historicoNotas">
  <?php $paginaTitulo = 'Notas — ' . $h(B::nomeSerie($s['nivel'], (int) $s['serie'])) . ' de ' . (int) $s['ano'];
  $paginaPretitulo = $h($a['nome']) . ' · RM ' . $h($a['rm']);
  $paginaAcoesHtml = '<a href="' . URLADM . 'historico-escola-vida/aluno/' . (int) $a['id'] . '?nivel=' . $h($s['nivel']) . '" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> A vida escolar</a>';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if (!$gravada) : ?><div class="alert alert-info" id="serieProposta">Esta série ainda é proposta: confirme-a na vida escolar para sobrepor uma média.</div><?php endif; ?>
      <?php if ($q['avisos']) : ?><div class="alert alert-warning" id="avisosSerie"><ul class="mb-0"><?php foreach ($q['avisos'] as $x) : ?><li><?= $h($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      <form method="post" action="<?= URLADM ?>historico-escola-vida/salvar-notas/<?= (int) $a['id'] ?>" class="card" id="formNotas">
        <input type="hidden" name="serie_id" value="<?= (int) $s['id'] ?>">
        <div class="table-responsive"><table class="table table-vcenter card-table" id="tabelaNotas">
          <thead><tr><th>Disciplina</th><th class="text-center">Aulas anuais</th><?php if ($aqui) : ?><th class="text-center">Média do sistema</th><th>Média sobreposta</th><?php else : ?><th>Média</th><th>Aulas anuais digitadas</th><?php endif; ?></tr></thead><tbody>
          <?php foreach ($q['linhas'] as $l) : $k = $l['disciplina_id'] ?: 'm' . $l['materia_id']; ?>
            <tr data-materia="<?= (int) $l['materia_id'] ?>" data-disciplina="<?= (int) $l['disciplina_id'] ?>"><td><?= $h($l['nome']) ?><?= $l['religioso'] ? ' <span class="badge bg-secondary-lt">Ensino Religioso</span>' : '' ?></td>
              <td class="text-center"><?= $l['aulas_anuais'] ? (int) $l['aulas_anuais'] : '—' ?></td>
              <?php if ($aqui) : ?>
                <td class="text-center vc-media-sistema"><?= $h($l['media_sistema'] ?? '—') ?></td>
                <td><input class="form-control form-control-sm" style="width: 6rem" name="media[<?= $h($k) ?>]" value="<?= $l['origem_media'] === 'sobreposto' ? $h($l['media']) : '' ?>" placeholder="a do sistema" aria-label="Média sobreposta de <?= $h($l['nome']) ?>"<?= $pode ? '' : ' disabled' ?>></td>
              <?php else : ?>
                <td><input class="form-control form-control-sm" style="width: 6rem" name="media[<?= $h($k) ?>]" value="<?= $h($l['media']) ?>" aria-label="Média de <?= $h($l['nome']) ?>"<?= $pode ? '' : ' disabled' ?>></td>
                <td><input type="number" class="form-control form-control-sm" style="width: 7rem" name="aulas[<?= $h($k) ?>]" value="<?= $l['aulas_anuais'] ?: '' ?>" aria-label="Aulas anuais de <?= $h($l['nome']) ?>"<?= $pode ? '' : ' disabled' ?>></td>
              <?php endif; ?></tr>
          <?php endforeach; ?>
          <?php if (!$q['linhas']) : ?><tr><td colspan="4" class="text-secondary">Nenhuma disciplina ainda.</td></tr><?php endif; ?>
          </tbody></table></div>
        <?php if ($pode && !$aqui) : ?>
          <div class="card-body border-top row g-2" id="novaDisciplina"><div class="col-12 form-label">Incluir uma disciplina</div>
            <div class="col-md-3"><select class="form-select" name="nova_disciplina" id="nDisc" aria-label="Disciplina do cadastro"><option value="0">— criar uma nova —</option><?php foreach ($d['disciplinas'] as $x) : if (!$x['id']) { continue; } ?><option value="<?= (int) $x['id'] ?>"><?= $h($x['nome']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-3"><input class="form-control" name="nova_nome" id="nNome" placeholder="nome da disciplina nova" aria-label="Nome da disciplina nova"></div>
            <div class="col-md-2"><select class="form-select" name="nova_base" id="nBase" aria-label="Base"><option value="comum">base comum</option><option value="diversificada">parte diversificada</option></select></div>
            <div class="col-md-2"><input class="form-control" name="nova_media" id="nMedia" placeholder="média" aria-label="Média"></div>
            <div class="col-md-2"><input type="number" class="form-control" name="nova_aulas" id="nAulas" placeholder="aulas anuais" aria-label="Aulas anuais"></div>
          </div>
        <?php endif; ?>
        <?php if ($pode) : ?><div class="card-footer"><button type="submit" class="btn btn-primary" id="salvarNotas"><i class="fas fa-save me-1"></i> Salvar</button>
          <span class="small text-secondary ms-2"><?= $aqui ? 'A média do sistema é lida na hora (a mesma do boletim). Sobrepor fica registrado: quem, quando, a do sistema e a nova. Vazio: volta a valer a do sistema.' : 'Média de 0 a 10, F (frequentou), NF (não frequentou) ou "-". Aulas anuais vazias: as da matriz desta escola naquele ano.' ?></span></div><?php endif; ?>
      </form>
      <?php if ($d['alteracoes']) : ?><div class="card mt-3"><div class="card-header"><h3 class="card-title">Registro</h3></div><div class="table-responsive"><table class="table card-table" id="registroNotas"><tbody>
        <?php foreach ($d['alteracoes'] as $x) : ?><tr><td class="text-nowrap"><?= $h(date('d/m/Y H:i', strtotime($x['quando']))) ?></td><td><?= $h($x['usuario'] ?? '—') ?></td><td><?= $h($x['texto']) ?></td></tr><?php endforeach; ?>
      </tbody></table></div></div><?php endif; ?>
    </div>
  </section>
</div>
