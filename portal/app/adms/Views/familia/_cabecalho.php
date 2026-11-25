<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* O cabeçalho de toda tela do filho: de quem são os dados (o nome, a turma, o ano), a troca de filho e de ano, e as
   abas. Incluído pelas telas da família (o escopo é o da view: $this->dados). */
$hF = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$fF = $this->dados['filho'];
$anoF = (int) $this->dados['ano'];
$tF = $this->dados['turma'] ?? null;
$rotaF = $this->dados['rotaFamilia'] ?? 'filho';
$abasF = ['filho' => 'Resumo', 'boletim' => 'Boletim', 'notas' => 'Notas', 'frequencia' => 'Frequência', 'horario' => 'Horário', 'atestado' => 'Atestado']; // Onda 8, correção (D-83): a turma subiu para o Resumo
$urlF = fn($rota, $aluno = null, $ano = null) => URLACAD . 'familia/' . $rota . '/' . (int) ($aluno ?? $fF['id']) . '?ano=' . (int) ($ano ?? $anoF);
?>
<div class="card mb-3 vc-familia-cabecalho" id="cabecalhoFilho" data-aluno="<?= $hF((int) $fF['id']) ?>" data-ano="<?= $hF($anoF) ?>">
  <div class="card-body py-2">
    <div class="d-flex flex-wrap align-items-center gap-2 justify-content-between">
      <div>
        <div class="h3 mb-0" id="nomeFilho"><?= $hF($fF['nome_social'] ?: $fF['nome']) ?></div>
        <div class="text-secondary" id="turmaAnoFilho"><?= $tF ? $hF(trim($tF['serie'] . ' ' . $tF['nome'])) . ($tF['turno'] ? ' · ' . $hF($tF['turno']) : '') : 'sem turma neste ano' ?> · <?= $hF($anoF) ?></div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <?php if (count($this->dados['filhos'] ?? []) > 1) : ?>
          <select class="form-select form-select-sm w-auto" id="trocarFilho" aria-label="Trocar de filho" onchange="location.href=this.value">
            <?php foreach ($this->dados['filhos'] as $x) : ?><option value="<?= $hF(URLACAD . 'familia/filho/' . (int) $x['id']) ?>"<?= (int) $x['id'] === (int) $fF['id'] ? ' selected' : '' ?>><?= $hF($x['nome_social'] ?: $x['nome']) ?></option><?php endforeach; ?>
          </select>
        <?php endif; ?>
        <?php if (count($this->dados['anos'] ?? []) > 1) : ?>
          <select class="form-select form-select-sm w-auto" id="trocarAno" aria-label="Ano letivo" onchange="location.href=this.value">
            <?php foreach ($this->dados['anos'] as $x) : ?><option value="<?= $hF($urlF($rotaF, null, $x)) ?>"<?= (int) $x === $anoF ? ' selected' : '' ?>><?= $hF($x) ?></option><?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>
    </div>
    <ul class="nav nav-pills mt-2 flex-nowrap overflow-auto" id="abasFamilia">
      <?php foreach ($abasF as $r => $rot) : ?><li class="nav-item"><a class="nav-link py-1<?= $r === $rotaF ? ' active' : '' ?>" href="<?= $hF($urlF($r)) ?>"><?= $hF($rot) ?></a></li><?php endforeach; ?>
    </ul>
  </div>
</div>
