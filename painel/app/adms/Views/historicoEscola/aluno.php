<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* OS DADOS DO ALUNO PARA O HISTÓRICO (D-110): o que só o histórico usa, fora da tabela do aluno (que é compartilhada
   com o ensino superior): o RA estadual (o RM é o número da escola), a certidão de nascimento, a observação livre e a
   conclusão do Fundamental (para o histórico do Médio). */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$a = $d['aluno'];
$pode = $d['pode'];
$dis = $pode ? '' : ' disabled';
$voltaVida = !empty($d['voltaVida']);
?>
<div class="vc-pagina" id="historicoAluno">
  <?php $paginaTitulo = 'Dados do aluno para o histórico — ' . $h($a['nome']);
  $paginaPretitulo = 'RM ' . $h($a['rm']) . ($a['prova'] ? ' · PROVA' : '');
  $paginaAcoesHtml = '<a href="' . URLADM . 'historico-escola-vida/aluno/' . (int) $a['id'] . '" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> A vida escolar</a>';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <form method="post" action="<?= URLADM ?>historico-escola/salvar-aluno/<?= (int) $a['id'] ?>" class="card" id="formAlunoHistorico">
        <input type="hidden" name="voltar" value="<?= $voltaVida ? 'vida' : '' ?>">
        <div class="card-body row g-3">
          <div class="col-md-4"><label class="form-label" for="hRa">RA (registro na rede estadual)</label><input class="form-control" name="ra" id="hRa" maxlength="30" value="<?= $h($a['ra']) ?>"<?= $dis ?>><div class="form-hint">O RM (<?= $h($a['rm']) ?>) é o número do aluno nesta escola.</div></div>
          <div class="col-md-8"><label class="form-label" for="hCertidao">Certidão de nascimento (local e número)</label><input class="form-control" name="certidao" id="hCertidao" maxlength="300" value="<?= $h($a['certidao']) ?>"<?= $dis ?>></div>
          <div class="col-md-3"><label class="form-label" for="hEfAno">Conclusão do Fundamental: ano</label><input type="number" class="form-control" name="ef_conclusao_ano" id="hEfAno" value="<?= (int) $a['ef_conclusao_ano'] ?: '' ?>"<?= $dis ?>></div>
          <div class="col-md-5"><label class="form-label" for="hEfEscola">Conclusão do Fundamental: escola</label>
            <select class="form-select" name="ef_conclusao_escola_id" id="hEfEscola"<?= $dis ?>><option value="0">—</option><?php foreach ($d['escolas'] as $e) : ?><option value="<?= (int) $e['id'] ?>"<?= (int) $a['ef_conclusao_escola_id'] === (int) $e['id'] ? ' selected' : '' ?>><?= $h($e['nome'] . ' — ' . $e['municipio'] . '/' . $e['uf']) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-4"><label class="form-label" for="hEfPub">Conclusão do Fundamental: número de publicação</label><input class="form-control" name="ef_publicacao" id="hEfPub" maxlength="60" value="<?= $h($a['ef_publicacao']) ?>"<?= $dis ?>></div>
          <div class="col-12"><label class="form-label" for="hObs">Observação livre (sai nas observações do histórico)</label><textarea class="form-control" rows="2" name="obs_livre" id="hObs"<?= $dis ?>><?= $h($a['obs_livre']) ?></textarea></div>
        </div>
        <?php if ($pode) : ?><div class="card-footer"><button type="submit" class="btn btn-primary" id="salvarAlunoHistorico"><i class="fas fa-save me-1"></i> Salvar</button></div><?php endif; ?>
      </form>
      <div class="card mt-3"><div class="card-header"><h3 class="card-title">Registro de alterações</h3></div>
        <div class="table-responsive"><table class="table card-table" id="registroAluno"><tbody>
        <?php foreach ($d['alteracoes'] as $x) : ?><tr><td class="text-nowrap"><?= $h(date('d/m/Y H:i', strtotime($x['quando']))) ?></td><td><?= $h($x['usuario'] ?? '—') ?></td><td><?= $h($x['texto']) ?></td></tr><?php endforeach; ?>
        <?php if (!$d['alteracoes']) : ?><tr><td class="text-secondary">Nada registrado ainda.</td></tr><?php endif; ?>
        </tbody></table></div></div>
    </div>
  </section>
</div>
