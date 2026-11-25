<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 2, Etapa 5: a conferência do lote — as colunas, os valores, as linhas (com a correção), aplicar e desfazer. */
use App\adms\Models\importacao\AdmsImportacaoAlunos as Imp;

$d = $this->dados;
$l = $d['lote'];
$b = $d['botao'];
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$conferindo = (int) $l['situacao'] === Imp::CONFERINDO;
$corRes = [1 => 'bg-green-lt', 2 => 'bg-yellow-lt', 3 => 'bg-red-lt', 4 => 'bg-secondary-lt', 5 => 'bg-blue-lt'];
$primeira = $d['linhas'][0]['bruto'] ?? [];
$nomesValor = ['sexo' => 'Sexo', 'situacao' => 'Situação', 'modalidade' => 'Modalidade'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Importação #<?= htmlspecialchars((string) ((int) $l['id']), ENT_QUOTES, 'UTF-8') ?> — <?= $h($l['arquivo_nome']) ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="mb-3"><a href="<?= URLADM ?>importacao-alunos/listar" class="btn btn-sm btn-ghost-secondary"><i class="fas fa-arrow-left"></i> Importações</a></div>

      <div class="row row-cards mb-3" id="contadores" data-situacao="<?= htmlspecialchars((string) ((int) $l['situacao']), ENT_QUOTES, 'UTF-8') ?>">
        <?php foreach ([['Linhas', $l['total'], 'total', ''], ['Prontas', $l['prontas'], 'prontas', 'text-green'], ['Com aviso', $l['avisos'], 'avisos', 'text-yellow'], ['Com erro', $l['erros'], 'erros', 'text-red'], ['Puladas (já cadastradas)', $l['puladas'], 'puladas', 'text-secondary']] as [$rot, $n, $k, $cor]) : ?>
          <div class="col-6 col-md">
            <div class="card card-sm"><div class="card-body"><div class="h1 mb-0 <?= htmlspecialchars((string) ($cor), ENT_QUOTES, 'UTF-8') ?>" data-contagem="<?= htmlspecialchars((string) ($k), ENT_QUOTES, 'UTF-8') ?>"><?= (int) $n ?></div><div class="text-secondary"><?= htmlspecialchars((string) ($rot), ENT_QUOTES, 'UTF-8') ?></div></div></div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ((int) $l['situacao'] === Imp::APLICANDO) : ?>
        <div class="alert alert-warning" id="aplicacaoParada">
          <b>A aplicação deste lote parou no meio</b> (a página foi fechada, ou a conexão caiu). Nada se perdeu: continue de onde parou.
          <div class="mt-2"><button type="button" class="btn btn-warning" id="continuarAplicacao" data-progresso="progressoImportacao" data-fase="passo"><i class="fas fa-play"></i> Continuar</button></div>
        </div>
      <?php endif; ?>
      <?php if ((int) $l['situacao'] === Imp::APLICADA) : ?>
        <div class="card mb-3" id="resumoAplicado">
          <div class="card-status-top bg-green"></div>
          <div class="card-body">
            <h3 class="card-title">Aplicada</h3>
            <p class="mb-2">Em <?= $h(date('d/m/Y H:i', strtotime((string) $l['aplicada_em']))) ?>, por <?= $h($l['aplicador']) ?>: <strong><?= htmlspecialchars((string) ((int) $l['aplicadas']), ENT_QUOTES, 'UTF-8') ?></strong> aluno(s) cadastrado(s)<?= htmlspecialchars((string) ((int) $l['vinculos'] ? ', ' . (int) $l['vinculos'] . ' vinculado(s) ao curso' : ''), ENT_QUOTES, 'UTF-8') ?>.</p>
            <?php if (!empty($d['impedimentos'])) : ?>
              <div class="alert alert-warning mb-2" id="impedimentosDesfazer">
                <b>Este lote não pode mais ser desfeito</b> — alunos dele já têm uso no sistema:
                <ul class="mb-0"><?php foreach ($d['impedimentos'] as $i) : ?><li>RA <?= $h($i['rm']) ?> — <?= $h($i['nome']) ?>: <?= $h(implode('; ', $i['motivos'])) ?></li><?php endforeach; ?></ul>
              </div>
            <?php elseif (!empty($b['desfazer'])) : ?>
              <form method="POST" action="<?= URLADM ?>importacao-alunos/desfazer/<?= htmlspecialchars((string) ((int) $l['id']), ENT_QUOTES, 'UTF-8') ?>" id="formDesfazer" class="mt-2" style="max-width: 40rem">
                <p class="text-secondary mb-2">Desfazer tira do sistema os <?= htmlspecialchars((string) ((int) $l['aplicadas']), ENT_QUOTES, 'UTF-8') ?> aluno(s) deste lote (e os vínculos deles). Só vale enquanto nenhum deles tiver nota, falta, chamada, documento emitido ou senha criada no portal. É uma <strong>ação sensível</strong>: o motivo fica registrado.</p>
                <label class="form-label required" for="motivoDesfazer">Motivo</label>
                <textarea name="motivo" id="motivoDesfazer" class="form-control mb-2" rows="2" minlength="10" maxlength="500" required></textarea>
                <button type="submit" class="btn btn-outline-danger" id="desfazerLote" data-vc-confirma="Desfazer a importação e retirar os alunos deste lote?"><i class="fas fa-rotate-left"></i> Desfazer a importação</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php elseif ((int) $l['situacao'] === Imp::DESFEITA) : ?>
        <div class="alert alert-secondary" id="resumoDesfeito">Desfeita em <?= $h(date('d/m/Y H:i', strtotime((string) $l['desfeita_em']))) ?>, por <?= $h($l['desfez']) ?>. Motivo: <?= $h($l['desfeita_motivo']) ?></div>
      <?php endif; ?>

      <?php if ($conferindo) : ?>
        <div class="card mb-3" id="cartaoColunas">
          <div class="card-header"><h3 class="card-title">1. As colunas</h3></div>
          <div class="card-body">
            <p class="text-secondary">Cada coluna do arquivo e o campo em que ela entra. O sistema reconheceu pelo nome; ajuste o que estiver errado. O ajuste vale para os próximos arquivos com os mesmos cabeçalhos. Coluna opcional que não está no arquivo não é erro.</p>
            <form method="POST" action="<?= URLADM ?>importacao-alunos/mapear/<?= htmlspecialchars((string) ((int) $l['id']), ENT_QUOTES, 'UTF-8') ?>" id="formMapa">
              <div class="table-responsive">
                <table class="table table-sm table-vcenter" id="tabelaColunas">
                  <thead><tr><th>Coluna no arquivo</th><th>Exemplo (linha 2)</th><th>Entra em</th></tr></thead>
                  <tbody>
                    <?php foreach ($l['cabecalho'] as $i => $titulo) : $atual = (string) ($l['mapa_colunas'][$i] ?? ''); ?>
                      <tr>
                        <td><strong><?= $h($titulo) ?></strong></td>
                        <td class="text-secondary text-break"><?= $h($primeira[$i] ?? '') ?></td>
                        <td>
                          <select name="mapa[<?= (int) $i ?>]" class="form-select form-select-sm vc-mapa" data-coluna="<?= (int) $i ?>" aria-label="Campo da coluna <?= $h($titulo) ?>" <?= htmlspecialchars((string) (empty($b['mapear']) ? 'disabled' : ''), ENT_QUOTES, 'UTF-8') ?>>
                            <option value="">(não importar)</option>
                            <?php foreach (Imp::campos() as $campo => [$rot, $obr]) : ?><option value="<?= htmlspecialchars((string) ($campo), ENT_QUOTES, 'UTF-8') ?>" <?= $atual === $campo ? 'selected' : '' ?>><?= $h($rot) ?><?= $obr ? ' *' : '' ?></option><?php endforeach; ?>
                          </select>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php if (!empty($b['mapear'])) : ?><button type="submit" class="btn btn-outline-primary btn-sm" id="salvarMapa">Salvar as colunas e conferir de novo</button><?php endif; ?>
            </form>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($d['valores'])) : ?>
        <div class="card mb-3" id="deParaValores">
          <div class="card-header"><h3 class="card-title"><?= $conferindo ? '2. ' : '' ?>Os valores</h3></div>
          <div class="card-body">
            <p class="text-secondary">Cada valor diferente do arquivo e o que ele virou. Valor não reconhecido não é adivinhado: a linha fica com erro, e você corrige.</p>
            <div class="row">
              <?php foreach ($d['valores'] as $campo => $vals) : ?>
                <div class="col-md-4">
                  <h4><?= $h($nomesValor[$campo] ?? $campo) ?></h4>
                  <table class="table table-sm" data-campo="<?= $h($campo) ?>">
                    <thead><tr><th>No arquivo</th><th>Virou</th><th class="text-end">Linhas</th></tr></thead>
                    <tbody>
                      <?php foreach ($vals as $bruto => $x) : ?>
                        <tr class="<?= htmlspecialchars((string) ($x['vira'] === null ? 'table-danger' : ''), ENT_QUOTES, 'UTF-8') ?>"><td><?= $h($bruto) ?></td><td><?= $x['vira'] === null ? '<span class="text-danger">não reconhecido</span>' : $h($x['vira']) ?></td><td class="text-end"><?= htmlspecialchars((string) ((int) $x['linhas']), ENT_QUOTES, 'UTF-8') ?></td></tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="card mb-3" id="cartaoLinhas">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
          <h3 class="card-title me-auto"><?= $conferindo ? '3. ' : '' ?>As linhas</h3>
          <select class="form-select form-select-sm w-auto" id="filtroResultado" aria-label="Mostrar">
            <option value="">Todas</option>
            <?php foreach (Imp::NOMES_RESULTADO as $k => $nome) : ?><option value="<?= htmlspecialchars((string) ($k), ENT_QUOTES, 'UTF-8') ?>"><?= $h($nome) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="table-responsive">
          <table class="table table-vcenter card-table" id="tabelaLinhas">
            <thead><tr><th>Linha</th><th>RA</th><th>Nome</th><th>Curso</th><th>Situação</th><th>Motivo</th><?php if ($conferindo && !empty($b['corrigir'])) : ?><th></th><?php endif; ?></tr></thead>
            <tbody>
              <?php foreach ($d['linhas'] as $x) : $res = (int) $x['resultado']; $v = Imp::valores($l, $x); ?>
                <tr id="linha-<?= htmlspecialchars((string) ((int) $x['numero']), ENT_QUOTES, 'UTF-8') ?>" data-linha="<?= htmlspecialchars((string) ((int) $x['numero']), ENT_QUOTES, 'UTF-8') ?>" data-resultado="<?= htmlspecialchars((string) ($res), ENT_QUOTES, 'UTF-8') ?>">
                  <td><?= htmlspecialchars((string) ((int) $x['numero']), ENT_QUOTES, 'UTF-8') ?><?= $x['correcao'] ? ' <span class="badge bg-azure-lt" title="Corrigida na tela">corrigida</span>' : '' ?></td>
                  <td><?= $h($v['ra']) ?></td>
                  <td><?= $h($v['nome']) ?></td>
                  <td class="small"><?= !empty($x['dados']['vinculo']) ? $h($x['dados']['vinculo']['curso'] . ' (' . $x['dados']['vinculo']['ano'] . ')') : $h($v['curso']) ?></td>
                  <td><span class="badge <?= htmlspecialchars((string) ($corRes[$res] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= $h(Imp::NOMES_RESULTADO[$res] ?? '') ?></span></td>
                  <td class="small">
                    <?php foreach ($x['mensagens'] as $m) : ?><div class="<?= htmlspecialchars((string) ($m['tipo'] === 'erro' ? 'text-danger' : 'text-warning'), ENT_QUOTES, 'UTF-8') ?>" data-tipo="<?= $h($m['tipo']) ?>"><?= $h($m['texto']) ?></div><?php endforeach; ?>
                  </td>
                  <?php if ($conferindo && !empty($b['corrigir'])) : ?>
                    <td><button type="button" class="btn btn-sm btn-outline-secondary corrigirLinha" data-linha="<?= htmlspecialchars((string) ((int) $x['numero']), ENT_QUOTES, 'UTF-8') ?>" data-valores="<?= $h(json_encode($v, JSON_UNESCAPED_UNICODE)) ?>" data-bs-toggle="modal" data-bs-target="#modalCorrigir">Corrigir</button></td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php if ($conferindo && !empty($b['aplicar'])) : ?>
        <div class="card mb-3" id="cartaoAplicar">
          <div class="card-header"><h3 class="card-title">4. Aplicar</h3></div>
          <div class="card-body">
            <form method="POST" action="<?= URLADM ?>importacao-alunos/aplicar/<?= htmlspecialchars((string) ((int) $l['id']), ENT_QUOTES, 'UTF-8') ?>" id="formAplicar" onsubmit="return false;">
              <p class="text-secondary">Grava as <?= htmlspecialchars((string) ((int) $l['prontas'] + (int) $l['avisos']), ENT_QUOTES, 'UTF-8') ?> linha(s) prontas e com aviso, uma a uma, com o andamento na tela: se uma falhar, as já gravadas são desfeitas e nada fica gravado. As puladas (alunos já cadastrados) não mudam.</p>
              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label" for="formaIngresso">Forma de ingresso dos vínculos</label>
                  <select name="forma_ingresso_id" id="formaIngresso" class="form-select">
                    <option value="">Escolha (só para as linhas com curso)</option>
                    <?php foreach ($d['formas'] as $f) : ?><option value="<?= htmlspecialchars((string) ((int) $f['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $h($f['nome']) ?></option><?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3 mb-3">
                  <label class="form-label" for="dataIngresso">Data de ingresso</label>
                  <input type="date" name="data_ingresso" id="dataIngresso" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
              </div>
              <?php if ((int) $l['erros'] > 0) : ?>
                <label class="form-check mb-3"><input type="checkbox" class="form-check-input" name="aceitar_erros" value="1" id="aceitarErros"><span class="form-check-label">Aplicar sem as <?= htmlspecialchars((string) ((int) $l['erros']), ENT_QUOTES, 'UTF-8') ?> linha(s) com erro (elas ficam de fora; dá para importá-las depois, num arquivo só com elas)</span></label>
              <?php endif; ?>
              <div><button type="button" class="btn btn-success" id="aplicarLote" data-progresso="progressoImportacao" data-fase="iniciar" <?= htmlspecialchars((string) ((int) $l['prontas'] + (int) $l['avisos'] === 0 ? 'disabled' : ''), ENT_QUOTES, 'UTF-8') ?>><i class="fas fa-check-double"></i> Aplicar a importação</button></div>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>

<?php
if (($conferindo && !empty($b['aplicar'])) || (int) $l['situacao'] === Imp::APLICANDO) {
    $progressoId = 'progressoImportacao';
    $progressoTitulo = 'Aplicando a importação #' . (int) $l['id'];
    $progressoGrupos = ['Os alunos, linha a linha' => max(1, (int) $l['prontas'] + (int) $l['avisos'])];
    $progressoUrl = URLADM . 'importacao-alunos/aplicar/' . (int) $l['id'];
    $progressoFim = ['Ver o lote', URLADM . 'importacao-alunos/conferir/' . (int) $l['id']];
    $progressoCampos = ['forma_ingresso_id' => '#formaIngresso', 'data_ingresso' => '#dataIngresso', 'aceitar_erros' => '#aceitarErros'];
    $progressoNavegar = true; // no fim (gravado ou recusado), a conferência abre com a mensagem
    include 'app/adms/Views/include_tabler/_progresso.php';
}
?>
<?php if ($conferindo && !empty($b['corrigir'])) : ?>
<div class="modal fade" id="modalCorrigir" tabindex="-1" aria-labelledby="tituloCorrigir" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="POST" action="<?= URLADM ?>importacao-alunos/corrigir/<?= htmlspecialchars((string) ((int) $l['id']), ENT_QUOTES, 'UTF-8') ?>" id="formCorrigir">
      <div class="modal-header"><h5 class="modal-title" id="tituloCorrigir">Corrigir a linha <span id="numeroCorrigir"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
      <div class="modal-body">
        <p class="text-secondary small">A correção vale só para esta importação; o arquivo não muda. Deixe vazio o campo opcional que não vier.</p>
        <input type="hidden" name="numero" id="corrigirNumero">
        <div class="row">
          <?php foreach (Imp::campos() as $campo => [$rot, $obr]) : ?>
            <div class="col-md-4 mb-2"><label class="form-label<?= $obr ? ' required' : '' ?>" for="corrigir_<?= htmlspecialchars((string) ($campo), ENT_QUOTES, 'UTF-8') ?>"><?= $h($rot) ?></label><input type="text" name="dados[<?= htmlspecialchars((string) ($campo), ENT_QUOTES, 'UTF-8') ?>]" id="corrigir_<?= htmlspecialchars((string) ($campo), ENT_QUOTES, 'UTF-8') ?>" class="form-control form-control-sm"></div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" id="salvarCorrecao">Salvar a correção</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<script>
  /* a linha a corrigir: os valores atuais no formulário do modal */
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('.corrigirLinha'); if (!b) { return; }
    var v = JSON.parse(b.getAttribute('data-valores') || '{}');
    document.getElementById('corrigirNumero').value = b.getAttribute('data-linha');
    document.getElementById('numeroCorrigir').textContent = b.getAttribute('data-linha');
    Object.keys(v).forEach(function (k) { var c = document.getElementById('corrigir_' + k); if (c) { c.value = v[k]; } });
  });
  /* o filtro das linhas */
  (function () {
    var f = document.getElementById('filtroResultado'); if (!f) { return; }
    f.addEventListener('change', function () {
      document.querySelectorAll('#tabelaLinhas tbody tr').forEach(function (tr) { tr.hidden = f.value !== '' && tr.getAttribute('data-resultado') !== f.value; });
    });
  })();
</script>
