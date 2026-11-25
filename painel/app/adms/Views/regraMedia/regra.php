<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E4 (1.13.0, D-398, D-399): REGRA DE MÉDIA — instituição, curso ou oferta — com o editor de componentes e o
   SIMULADOR. A conta do simulador é do servidor (regra-media/simular → core/MotorMedia.php); o JS só soma os pesos
   para avisar e mostra o que o servidor devolve.
   E10 (1.15.0, D-420, D-421): coluna "Atividades na disciplina" — Obrigatório (liga o mínimo), Mín. e Máx. (vazio =
   sem limite); a tela confere mínimo e máximo antes de salvar (o servidor confere de novo) e avisa quando nenhum
   componente é obrigatório.
   1.15.2 (D-430 a D-433): o simulador segue as travas do cadastro (a resposta do servidor traz, por atividade de teste,
   os componentes cheios e a excedente — AdmsRegraMedia::travasSimulador, a mesma regra do cadastro); a pendência manda
   no selo e a média vira "provisória"; as colunas "Quantos (N ou %)" e "Peso" só aparecem quando algum componente
   usa o modo; rótulos conferidos com o motor; Encontros e Faltas na mesma linha. */
use Core\MotorMedia as M;

/* 1.16.0 (D-436 a D-446): a regra é um item do CATÁLOGO, com nome e descrição; a tela NÃO fala em versão (por baixo, a
   versão usada num encerramento continua guardada). Antes de salvar uma regra em uso, a tela mostra as disciplinas e
   ofertas abertas afetadas e as que ficariam fora dos limites, e pede confirmação (D-439). "Onde é usada" e "Aplicar a
   disciplinas…" no fim. */
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$id = (int) $d['id'];
$cab = $d['cab'];
$pode = $d['podeEditar'];
$r = $d['form'] ?? $d['regraAtual'];
$nome = $d['nome'] ?? (string) ($cab['nome'] ?? '');
$descricao = $d['descricao'] ?? (string) ($cab['descricao'] ?? '');
$conf = $d['confirmar'] ?? null;
$onde = $d['onde'];
$sel = fn($a, $b) => (string) $a === (string) $b ? 'selected' : '';
$origens = \App\adms\Models\notas\AdmsRegraMedia::ORIGENS;
?>
<div class="vc-pagina">
  <?php ob_start(); ?><i class="fas fa-calculator me-2 text-info"></i><?= $id ? 'Regra de média' : 'Nova regra de média' ?> <small class="text-muted"><?= $h($nome) ?></small><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>

      <?php if ($id) : $u = $d['ultima']; ?>
      <div class="card shadow-sm" id="situacaoRegra">
        <div class="card-body py-2">
          <?= $d['padrao'] ? '<span class="badge bg-primary-lt me-2">Padrão da instituição</span> vale para toda disciplina sem regra. ' : '' ?>
          <?= (int) ($cab['situacao'] ?? 1) !== 1 ? '<span class="badge bg-secondary-lt me-2">Inativa</span> não aparece para escolha. ' : '' ?>
          <span id="ultimaAlteracao">Última alteração em <?= $u && $u['quando'] ? $h(date('d/m/Y H:i', strtotime($u['quando']))) : '—' ?><?= $u && $u['usuario'] !== '' ? ' por ' . $h($u['usuario']) : '' ?>.
            Usada em <?= htmlspecialchars((string) ((int) $d['encerramentos']), ENT_QUOTES, 'UTF-8') ?> encerramento(s), que guardam a regra da época e não mudam.</span>
          <?= !$pode ? '<br><span class="text-muted small">Só o administrador muda o Padrão da instituição.</span>' : '' ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($conf) : ?>
        <div class="card shadow" id="confirmarSalvar">
          <div class="card-header"><b>Esta regra está em uso. Confira antes de salvar.</b></div>
          <div class="card-body small">
            <?php if (!empty($conf['padrao'])) : ?><p class="mb-1">É o <b>Padrão da instituição</b>: vale para toda disciplina sem regra própria.</p><?php endif; ?>
            <p class="mb-1"><b><?= htmlspecialchars((string) (count($conf['disciplinas'])), ENT_QUOTES, 'UTF-8') ?></b> disciplina(s) da matriz associada(s) e <b><?= htmlspecialchars((string) (count($conf['ofertas'])), ENT_QUOTES, 'UTF-8') ?></b> oferta(s) aberta(s) seguem esta regra e passam a calcular pela regra nova:</p>
            <?php if ($conf['ofertas']) : ?><ul class="mb-1" id="ofertasAfetadas"><?php foreach ($conf['ofertas'] as $o) : ?><li><?= $h($o['rotulo']) ?> <span class="text-muted">(<?= $h($origens[$o['origem']] ?? $o['origem']) ?>)</span></li><?php endforeach; ?></ul><?php endif; ?>
            <?php if ($conf['fora']) : ?><p class="mb-1 text-danger" id="foraDosLimites"><b>Ficariam fora dos limites</b> (o encerramento fica bloqueado até ajustar as atividades): <?= $h(implode(' | ', $conf['fora'])) ?></p><?php endif; ?>
            <p class="mb-2">Os <?= htmlspecialchars((string) ((int) $conf['encerramentos']), ENT_QUOTES, 'UTF-8') ?> encerramento(s) feitos com ela guardam a regra da época e não mudam.</p>
            <p class="mb-0">Para gravar, use <b>Confirmar e salvar</b>, embaixo do formulário.</p>
          </div>
        </div>
      <?php endif; ?>
      <div class="row">
        <div class="col-lg-7">
          <form method="POST" action="<?= $h($d['url']) ?>" id="formRegra" novalidate>
            <input type="hidden" name="acao" value="salvar">
            <?php if ($conf) : ?><input type="hidden" name="confirmado" value="1"><?php endif; ?>
            <fieldset <?= $pode ? '' : 'disabled' ?>>
            <div class="card shadow-sm"><div class="card-body pb-1"><div class="row g-2">
              <div class="mb-3 col-md-5"><label class="form-label" for="rNome">Nome da regra</label><input type="text" class="form-control" id="rNome" name="nome" maxlength="120" value="<?= $h($nome) ?>" placeholder="ex.: Atividades 30% · Prova 70%"></div>
              <div class="mb-3 col-md-7"><label class="form-label" for="rDescricao">Descrição <small class="text-muted">(opcional)</small></label><input type="text" class="form-control" id="rDescricao" name="descricao" maxlength="500" value="<?= $h($descricao) ?>" placeholder="para que disciplinas ela serve"></div>
            </div></div></div>
            <div class="card shadow-sm">
              <div class="card-header"><b>Componentes</b> <small class="text-muted">— grupos de atividades com peso; os pesos somam 100</small></div>
              <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0" id="tabelaComponentes">
                  <thead><tr><th>Nome</th><th style="width: 90px">Peso</th><th>Modo</th><th style="width: 160px" class="col-n">Quantos (N ou %)</th><th style="width: 190px">Atividades na disciplina</th><th></th></tr></thead>
                  <tbody>
                  <?php foreach ($r['componentes'] as $i => $c) : ?>
                    <tr class="linha-comp">
                      <td><input type="text" class="form-control form-control-sm c-nome" name="comp[<?= htmlspecialchars((string) ($i), ENT_QUOTES, 'UTF-8') ?>][nome]" value="<?= $h($c['nome']) ?>" maxlength="80">
                        <input type="hidden" class="c-codigo" name="comp[<?= htmlspecialchars((string) ($i), ENT_QUOTES, 'UTF-8') ?>][codigo]" value="<?= $h($c['codigo']) ?>"><small class="text-muted">código: <?= $h($c['codigo']) ?></small></td>
                      <td><input type="text" inputmode="decimal" class="form-control form-control-sm c-peso" name="comp[<?= htmlspecialchars((string) ($i), ENT_QUOTES, 'UTF-8') ?>][peso]" value="<?= $h(str_replace('.', ',', (string) $c['peso'])) ?>"></td>
                      <td><select class="form-select form-control-sm c-modo" name="comp[<?= htmlspecialchars((string) ($i), ENT_QUOTES, 'UTF-8') ?>][modo]"><?php foreach (M::MODOS as $k => $rot) : ?><option value="<?= htmlspecialchars((string) ($k), ENT_QUOTES, 'UTF-8') ?>" <?= $sel($c['modo'], $k) ?>><?= $h($rot) ?></option><?php endforeach; ?></select></td>
                      <td class="c-n-celula"><div class="input-group input-group-sm"><input type="text" inputmode="numeric" class="form-control c-n" name="comp[<?= htmlspecialchars((string) ($i), ENT_QUOTES, 'UTF-8') ?>][n]" value="<?= $h($c['n'] ?? '') ?>" placeholder="N">
                        <select class="form-select c-tipo" name="comp[<?= htmlspecialchars((string) ($i), ENT_QUOTES, 'UTF-8') ?>][tipoN]"><option value="quantidade" <?= $sel($c['tipoN'] ?? '', 'quantidade') ?>>atividades</option><option value="percentual" <?= $sel($c['tipoN'] ?? '', 'percentual') ?>>%</option></select></div></td>
                      <?php $cMin = (int) ($c['min'] ?? 0); $cMax = $c['max'] ?? null; ?>
                      <td class="c-lim-celula"><input type="hidden" class="c-tela" name="comp[<?= htmlspecialchars((string) ($i), ENT_QUOTES, 'UTF-8') ?>][tela]" value="1">
                        <div class="form-check"><input type="checkbox" class="form-check-input c-obrig" id="obrig_<?= htmlspecialchars((string) ($i), ENT_QUOTES, 'UTF-8') ?>" name="comp[<?= htmlspecialchars((string) ($i), ENT_QUOTES, 'UTF-8') ?>][obrig]" value="1" <?= $cMin > 0 ? 'checked' : '' ?>><label class="form-check-label c-obrig-rotulo" for="obrig_<?= htmlspecialchars((string) ($i), ENT_QUOTES, 'UTF-8') ?>" title="Toda disciplina precisa de atividade neste componente">Obrigatório</label></div>
                        <div class="d-flex align-items-center mt-1"><span class="c-min-grupo d-flex align-items-center me-2"><label class="small mb-0 me-1">Mín.</label><input type="text" inputmode="numeric" class="form-control form-control-sm c-min" name="comp[<?= htmlspecialchars((string) ($i), ENT_QUOTES, 'UTF-8') ?>][min]" value="<?= $cMin > 0 ? $cMin : '' ?>" style="width: 3rem" aria-label="Mínimo de atividades"></span>
                          <label class="small mb-0 me-1">Máx.</label><input type="text" inputmode="numeric" class="form-control form-control-sm c-max" name="comp[<?= htmlspecialchars((string) ($i), ENT_QUOTES, 'UTF-8') ?>][max]" value="<?= $h($cMax === null ? '' : $cMax) ?>" placeholder="sem limite" style="width: 5.5rem" aria-label="Máximo de atividades"></div>
                        <small class="text-muted">Máx.: vazio = sem limite</small></td>
                      <td><button type="button" class="btn btn-sm btn-outline-danger c-tirar" title="Tirar o componente"><i class="far fa-trash-alt"></i></button></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div class="card-footer py-2 d-flex align-items-center">
                <button type="button" class="btn btn-sm btn-outline-success me-3" id="addComponente"><i class="fas fa-plus"></i> Componente</button>
                <span id="somaPesos" class="fw-bold"></span>
              </div>
              <div class="px-3 pb-2"><div class="text-danger small" id="erroLimites"></div>
                <div class="alert alert-warning py-1 px-2 mb-0 small" id="avisoObrigatorio" hidden><i class="fas fa-exclamation-triangle me-1"></i>Nenhum componente obrigatório: uma disciplina pode ser encerrada sem nenhuma avaliação deste tipo. Salvar é permitido.</div></div>
            </div>
            <div class="card shadow-sm">
              <div class="card-header"><b>Conta e aprovação</b></div>
              <div class="card-body">
                <div class="row g-2">
                  <div class="mb-3 col-md-4"><label class="form-label" for="rCasas">Casas da média</label>
                    <select class="form-select" id="rCasas" name="regra[casas]"><option value="1" <?= $sel($r['casas'], 1) ?>>1 casa (8,2)</option><option value="2" <?= $sel($r['casas'], 2) ?>>2 casas (8,17)</option></select></div>
                  <div class="mb-3 col-md-4"><label class="form-label" for="rArred">Arredondamento</label>
                    <select class="form-select" id="rArred" name="regra[arredondamento]"><?php foreach (M::ARREDONDAMENTOS as $k => $rot) : ?><option value="<?= htmlspecialchars((string) ($k), ENT_QUOTES, 'UTF-8') ?>" <?= $sel($r['arredondamento'], $k) ?>><?= $h($rot) ?></option><?php endforeach; ?></select></div>
                  <div class="mb-3 col-md-4"><label class="form-label" for="rMomento">Quando arredondar</label>
                    <select class="form-select" id="rMomento" name="regra[momento]"><?php foreach (M::MOMENTOS as $k => $rot) : ?><option value="<?= htmlspecialchars((string) ($k), ENT_QUOTES, 'UTF-8') ?>" <?= $sel($r['momento'], $k) ?>><?= $h($rot) ?></option><?php endforeach; ?></select></div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-4"><label class="form-label" for="rSemNota">Atividade sem nota, no encerramento</label>
                    <select class="form-select" id="rSemNota" name="regra[semNota]"><?php foreach (M::SEM_NOTA as $k => $rot) : ?><option value="<?= htmlspecialchars((string) ($k), ENT_QUOTES, 'UTF-8') ?>" <?= $sel($r['semNota'], $k) ?>><?= $h($rot) ?></option><?php endforeach; ?></select>
                    <small class="text-muted" id="ajudaSemNota">Com a disciplina aberta, a média parcial sempre deixa de fora o que não tem nota. Num componente obrigatório, o aluno sem nenhuma nota nele fica com zero no encerramento, mesmo em "Fica de fora".</small></div>
                  <div class="mb-3 col-md-4"><label class="form-label" for="rVazio">Componente OPCIONAL sem atividade</label>
                    <select class="form-select" id="rVazio" name="regra[componenteVazio]"><?php foreach (M::COMPONENTE_VAZIO as $k => $rot) : ?><option value="<?= htmlspecialchars((string) ($k), ENT_QUOTES, 'UTF-8') ?>" <?= $sel($r['componenteVazio'], $k) ?>><?= $h($rot) ?></option><?php endforeach; ?></select>
                    <small class="text-muted" id="ajudaVazio">Componente obrigatório sem atividade deixa a disciplina pendente. Vale também para o opcional com atividade em que o aluno não tem nota, com "Fica de fora".</small></div>
                  <div class="mb-3 col-md-4"><label class="form-label" for="rMinima">Nota mínima para aprovar</label>
                    <input type="text" inputmode="decimal" class="form-control" id="rMinima" name="regra[notaMinima]" value="<?= $h(str_replace('.', ',', (string) $r['notaMinima'])) ?>">
                    <small class="text-muted">E a frequência suficiente (FS), pelo mínimo do curso.</small></div>
                </div>
                <?php /* Onda 4, Etapa 5: o acréscimo — um ponto por cima da média fechada (padrão: não) */ $acrR = $r['acrescimo'] ?? null; ?>
                <div class="row g-2" id="blocoAcrescimo">
                  <div class="mb-3 col-md-4"><div class="form-check mt-md-4"><input type="checkbox" class="form-check-input" id="rAcrescimo" name="regra[acrescimo]" value="1"<?= htmlspecialchars((string) (!empty($acrR['permitir']) ? ' checked' : ''), ENT_QUOTES, 'UTF-8') ?>>
                    <label class="form-check-label" for="rAcrescimo">Permitir acréscimo</label></div>
                    <small class="text-muted">O professor lança o acréscimo como uma atividade própria, fora dos componentes e sem peso: ele soma à média fechada (5,0 + 1,0 → 6,0), antes do arredondamento, até a nota máxima.</small></div>
                  <div class="mb-3 col-md-4"><label class="form-label" for="rAcrescimoLimite">Limite do acréscimo</label>
                    <input type="text" inputmode="decimal" class="form-control" id="rAcrescimoLimite" name="regra[acrescimoLimite]" value="<?= $h(isset($acrR['limite']) && $acrR['limite'] !== null ? str_replace('.', ',', (string) $acrR['limite']) : '') ?>" placeholder="sem limite">
                    <small class="text-muted">Por disciplina (na escola, por etapa). Vazio = sem limite.</small></div>
                </div>
                <?php if ($pode) : ?>
                  <button type="submit" class="btn <?= $conf ? 'btn-warning' : 'btn-primary' ?>" id="salvarRegra"><i class="fas fa-save"></i> <?= $conf ? 'Confirmar e salvar' : 'Salvar a regra' ?></button>
                  <small class="text-muted ms-2">O que já foi encerrado não muda: o encerramento guardou a regra da época.</small>
                <?php else : ?>
                  <p class="text-muted mb-0">Só o administrador muda o Padrão da instituição.</p>
                <?php endif; ?>
              </div>
            </div>
            </fieldset>
          </form>
          <?php if ($id) : ?>
            <div class="card shadow-sm" id="onde"><div class="card-header d-flex align-items-center"><b>Onde é usada</b>
              <?php if ((int) ($cab['situacao'] ?? 1) === 1) : ?><a href="<?= URLADM . 'regra-media/aplicar/' . $id ?>" class="btn btn-sm btn-outline-primary ms-auto" id="aplicarDisciplinas"><i class="fas fa-list-check"></i> Aplicar a disciplinas…</a><?php endif; ?></div>
              <div class="card-body small">
                <?php if ($d['padrao']) : ?><p class="mb-1">Padrão da instituição: vale para toda disciplina da matriz <b>sem regra</b> (e as ofertas delas).</p><?php endif; ?>
                <p class="mb-1"><b>Disciplinas da matriz associadas (<?= htmlspecialchars((string) (count($onde['disciplinas'])), ENT_QUOTES, 'UTF-8') ?>):</b></p>
                <?php if ($onde['disciplinas']) : ?><ul class="mb-2" id="ondeDisciplinas"><?php foreach ($onde['disciplinas'] as $x) : ?><li><?= $h($x['materia']) ?> — <?= $h($x['curso']) ?>, grade <?= htmlspecialchars((string) ((int) $x['grade']), ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul><?php else : ?><p class="text-muted mb-2">nenhuma</p><?php endif; ?>
                <?php if (!empty($onde['cursos'])) : /* 1.16.1 (D-447) */ ?><p class="mb-1"><b>Cursos que a usam no TCC e nos documentos (<?= htmlspecialchars((string) (count($onde['cursos'])), ENT_QUOTES, 'UTF-8') ?>):</b></p>
                  <ul class="mb-2" id="ondeCursos"><?php foreach ($onde['cursos'] as $x) : ?><li><?= $h($x['nome']) ?></li><?php endforeach; ?></ul><?php endif; ?>
                <p class="mb-1"><b>Ofertas abertas que seguem esta regra (<?= htmlspecialchars((string) (count($onde['ofertas'])), ENT_QUOTES, 'UTF-8') ?>):</b></p>
                <?php if ($onde['ofertas']) : ?><ul class="mb-0" id="ondeOfertas"><?php foreach ($onde['ofertas'] as $x) : ?><li><?= $h($x['rotulo']) ?> <span class="text-muted">(<?= $h($origens[$x['origem']] ?? $x['origem']) ?>)</span></li><?php endforeach; ?></ul><?php else : ?><p class="text-muted mb-0">nenhuma</p><?php endif; ?>
              </div></div>
          <?php endif; ?>
        </div>

        <div class="col-lg-5">
          <div class="card shadow-sm" id="simulador">
            <div class="card-header"><b><i class="fas fa-flask"></i> Simulador</b> <small class="text-muted">— notas de teste; nada é gravado</small></div>
            <div class="card-body">
              <form id="formSim" onsubmit="return false">
                <table class="table table-sm mb-2" id="simTabela"><thead><tr><th style="width: 32%">Atividade</th><th style="min-width: 10.5rem">Componente</th><th style="width: 70px" class="col-peso">Peso</th><th style="width: 80px">Nota</th><th></th></tr></thead><tbody></tbody></table>
                <button type="button" class="btn btn-sm btn-outline-success mb-2" id="simAdd"><i class="fas fa-plus"></i> Atividade</button>
                <div class="d-flex flex-wrap align-items-center sim-contexto" id="simContexto">
                  <div class="form-check me-4 mb-1"><input type="checkbox" class="form-check-input" id="simFinal" name="final" value="1" checked><label class="form-check-label" for="simFinal">Como no encerramento</label></div>
                  <div class="d-flex align-items-center mb-1"><div class="d-flex align-items-center me-3"><label class="small mb-0 me-2" for="simEncontros">Encontros</label><input type="text" inputmode="numeric" autocomplete="off" class="form-control form-control-sm" id="simEncontros" name="encontros" value="20" style="width: 4.5rem"></div>
                    <div class="d-flex align-items-center"><label class="small mb-0 me-2" for="simFaltas">Faltas</label><input type="text" inputmode="numeric" autocomplete="off" class="form-control form-control-sm" id="simFaltas" name="faltas" value="0" style="width: 4.5rem"></div>
                    <div class="d-flex align-items-center ms-3"><label class="small mb-0 me-2" for="simAcrescimo">Acréscimo</label><input type="text" inputmode="decimal" autocomplete="off" class="form-control form-control-sm" id="simAcrescimo" name="simAcrescimo" value="" style="width: 4.5rem" title="Com Permitir acréscimo marcado: soma à média fechada"></div></div>
                </div>
              </form>
              <div class="border rounded p-2 mt-2 bg-light">
                <div><span id="simMediaRotulo">Média:</span> <b id="simMedia" class="h5">—</b> <span id="simResultado" class="ms-2"></span> <span id="simEspera" class="text-muted small ms-2"></span></div>
                <ul class="small mb-0 mt-2 ps-3" id="simMemoria"></ul>
                <div class="text-danger small" id="simErros"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<script>
/* E4 (D-399): o simulador manda a regra do formulário (ou nenhuma, quando herda: o servidor usa a que vale) e as
   notas de teste ao servidor, que calcula com o motor. Aqui não há conta de média — só a soma dos pesos, para avisar. */
(function () {
  var URL_SIM = <?= json_encode(URLADM . 'regra-media/simular') ?>, REGRA_ID = <?= htmlspecialchars((string) ($id), ENT_QUOTES, 'UTF-8') ?>;
  var PODE = <?= $pode ? 'true' : 'false' ?>, MODOS_N = <?= json_encode(M::MODOS_COM_N) ?>, MODOS_PESO = <?= json_encode(M::MODOS_COM_PESO) ?>;
  var tabela = document.querySelector('#tabelaComponentes tbody'), sim = document.querySelector('#simTabela tbody');
  var espera = null, pedido = 0, travas = null; /* 1.15.2: as travas da última resposta do servidor */
  function num(v) { v = String(v || '').replace(',', '.').trim(); return /^\d+(\.\d+)?$/.test(v) ? Math.round(parseFloat(v) * 10000) : null; }
  function comps() {
    return Array.prototype.map.call(tabela.querySelectorAll('tr.linha-comp'), function (tr, i) {
      return { codigo: tr.querySelector('.c-codigo').value || ('novo_' + i), nome: tr.querySelector('.c-nome').value || ('Componente ' + (i + 1)), modo: tr.querySelector('.c-modo').value };
    });
  }
  function renumerar() {
    Array.prototype.forEach.call(tabela.querySelectorAll('tr.linha-comp'), function (tr, i) {
      tr.querySelectorAll('[name^="comp["]').forEach(function (el) { el.name = el.name.replace(/^comp\[\d+\]/, 'comp[' + i + ']'); });
      var n = MODOS_N.indexOf(tr.querySelector('.c-modo').value) >= 0;
      tr.querySelector('.c-n-celula').style.visibility = n ? 'visible' : 'hidden';
      tr.querySelectorAll('.c-n, .c-tipo').forEach(function (el) { el.disabled = !n; }); /* 1.15.2: o campo só existe na linha cujo modo usa */
      var ob = tr.querySelector('.c-obrig'); ob.id = 'obrig_' + i; tr.querySelector('.c-obrig-rotulo').setAttribute('for', 'obrig_' + i);
      tr.querySelector('.c-min-grupo').style.visibility = ob.checked ? 'visible' : 'hidden';
    });
    /* 1.15.2 (D-432): a coluna "Quantos (N ou %)" só aparece quando algum componente usa descartar/manter */
    var algumN = comps().some(function (c) { return MODOS_N.indexOf(c.modo) >= 0; });
    document.querySelectorAll('#tabelaComponentes .col-n, #tabelaComponentes .c-n-celula').forEach(function (el) { el.style.display = algumN ? '' : 'none'; });
  }
  function soma() {
    var s = 0, ok = true;
    tabela.querySelectorAll('.c-peso').forEach(function (el) { var p = num(el.value); if (p === null) ok = false; else s += p; });
    var el = document.getElementById('somaPesos'), btn = document.getElementById('salvarRegra');
    var txt = (s / 10000).toLocaleString('pt-BR', { maximumFractionDigits: 4 });
    var certo = ok && s === 1000000;
    el.textContent = 'Soma dos pesos: ' + txt + (certo ? ' ✓' : ' — precisa ser 100');
    el.className = 'fw-bold ' + (certo ? 'text-success' : 'text-danger');
    var lim = limites();
    document.getElementById('erroLimites').innerHTML = lim.erros.map(function (e) { return '<div>' + e.replace(/</g, '&lt;') + '</div>'; }).join('');
    document.getElementById('avisoObrigatorio').hidden = lim.obrigatorios > 0;
    if (btn) btn.disabled = !certo || lim.erros.length > 0;
  }
  /* E10 (D-420): a mesma conferência do servidor (MotorMedia::validar) — mínimo 0 a 99; máximo vazio ou 1 a 99; máximo ≥ mínimo */
  function limites() {
    var erros = [], obrig = 0;
    Array.prototype.forEach.call(tabela.querySelectorAll('tr.linha-comp'), function (tr, i) {
      var nome = tr.querySelector('.c-nome').value || ('Componente ' + (i + 1)), ob = tr.querySelector('.c-obrig').checked;
      var mn = ob ? (tr.querySelector('.c-min').value.trim() || '1') : '0', mx = tr.querySelector('.c-max').value.trim();
      if (ob) obrig++;
      if (!/^\d{1,2}$/.test(mn)) erros.push(nome + ': o mínimo de atividades deve ser um inteiro de 0 a 99.');
      else if (mx !== '' && (!/^\d{1,2}$/.test(mx) || parseInt(mx, 10) < 1)) erros.push(nome + ': o máximo deve ficar vazio (sem limite) ou ser um inteiro de 1 a 99.');
      else if (mx !== '' && parseInt(mx, 10) < parseInt(mn, 10)) erros.push(nome + ': o máximo (' + mx + ') não pode ser menor que o mínimo (' + mn + ').');
    });
    return { erros: erros, obrigatorios: obrig };
  }
  /* 1.15.2 (D-430): as travas vêm do servidor (a mesma regra do cadastro): "(n/máx)" e o componente cheio desabilitado */
  function cheiosDa(i) { if (!travas) return []; return (i !== null && travas.linhas[i]) ? travas.linhas[i].cheios : travas.cheiosNova; }
  function opcoesComp(atual, i) {
    var cheios = cheiosDa(i === undefined ? null : i);
    return comps().map(function (c) {
      var mx = travas && travas.max[c.codigo] !== undefined ? travas.max[c.codigo] : null, n = travas ? (travas.contagem[c.codigo] || 0) : 0;
      var cheio = cheios.indexOf(c.codigo) >= 0 && c.codigo !== atual;
      return '<option value="' + c.codigo + '"' + (c.codigo === atual ? ' selected' : '') + (cheio ? ' disabled' : '') + '>' + c.nome.replace(/</g, '&lt;') + (mx !== null ? ' (' + n + '/' + mx + ')' : '') + '</option>';
    }).join('');
  }
  function atualizarSelects() {
    var algumPeso = comps().some(function (c) { return MODOS_PESO.indexOf(c.modo) >= 0; });
    sim.querySelectorAll('tr').forEach(function (tr, i) {
      var s = tr.querySelector('.s-comp'), v = s.value; s.innerHTML = opcoesComp(v, i);
      var modo = (comps().filter(function (c) { return c.codigo === s.value; })[0] || {}).modo, usa = MODOS_PESO.indexOf(modo) >= 0;
      var p = tr.querySelector('.s-peso'); p.style.visibility = usa ? 'visible' : 'hidden'; p.disabled = !usa; /* 1.15.2 (D-433): só ativo nas atividades de ponderada */
      var exc = !!(travas && travas.linhas[i] && travas.linhas[i].excedente); /* 1.15.2 (D-430): a que passa do máximo, em vermelho */
      tr.classList.toggle('table-danger', exc); s.classList.toggle('is-invalid', exc); s.title = exc ? 'Passa do máximo deste componente: a disciplina fica pendente' : '';
    });
    /* 1.15.2 (D-433): a coluna "Peso" só aparece quando algum componente usa média ponderada */
    document.querySelectorAll('#simTabela .col-peso, #simTabela .s-peso-celula').forEach(function (el) { el.style.display = algumPeso ? '' : 'none'; });
  }
  function linhaSim(nome, comp, peso, nota) {
    var i = sim.querySelectorAll('tr').length, tr = document.createElement('tr');
    tr.innerHTML = '<td><input class="form-control form-control-sm s-nome" name="sim[' + i + '][nome]" value="' + nome + '"></td>'
      + '<td><select class="form-select form-control-sm s-comp" name="sim[' + i + '][componente]">' + opcoesComp(comp) + '</select></td>'
      + '<td class="s-peso-celula"><input class="form-control form-control-sm s-peso" inputmode="decimal" name="sim[' + i + '][peso]" value="' + peso + '"></td>'
      + '<td><input class="form-control form-control-sm s-nota" inputmode="decimal" name="sim[' + i + '][nota]" value="' + nota + '"></td>'
      + '<td><button type="button" class="btn btn-sm btn-outline-danger s-tirar" title="Tirar"><i class="far fa-trash-alt"></i></button></td>';
    sim.appendChild(tr);
  }
  function renumerarSim() {
    Array.prototype.forEach.call(sim.querySelectorAll('tr'), function (tr, i) {
      tr.querySelectorAll('[name^="sim["]').forEach(function (el) { el.name = el.name.replace(/^sim\[\d+\]/, 'sim[' + i + ']'); });
    });
  }
  function simular() {
    clearTimeout(espera);
    document.getElementById('simEspera').textContent = 'calculando…';
    espera = setTimeout(function () {
      var fd = PODE ? new FormData(document.getElementById('formRegra')) : new FormData();
      var fs = new FormData(document.getElementById('formSim'));
      fs.forEach(function (v, k) { fd.append(k, v); });
      fd.append('regra_id', REGRA_ID);
      var meu = ++pedido;
      fetch(URL_SIM, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
        if (meu !== pedido) return;
        document.getElementById('simEspera').textContent = '';
        document.getElementById('simErros').innerHTML = j.ok ? '' : j.erros.map(function (e) { return '<div>' + e.replace(/</g, '&lt;') + '</div>'; }).join('');
        document.getElementById('simMedia').textContent = j.ok ? j.mediaTexto : '—';
        document.getElementById('simMediaRotulo').textContent = j.ok && j.mediaProvisoria ? 'Média provisória:' : 'Média:'; /* 1.15.2 (D-431) */
        if (j.ok) { travas = j.travas; atualizarSelects(); }
        var res = document.getElementById('simResultado');
        res.textContent = j.ok && j.resultado ? j.resultado : (j.ok && !j.final ? '(parcial)' : '');
        res.className = 'ms-2 badge ' + (j.pendente ? 'bg-warning-lt' : (j.resultado === 'Aprovado' ? 'bg-success-lt' : (j.resultado ? 'bg-danger-lt' : 'bg-light text-dark')));
        document.getElementById('simMemoria').innerHTML = j.ok ? j.linhas.map(function (l) { return '<li>' + l.replace(/</g, '&lt;') + '</li>'; }).join('') : '';
      }).catch(function () { document.getElementById('simEspera').textContent = 'sem resposta do servidor'; });
    }, 300);
  }
  document.getElementById('addComponente').addEventListener('click', function () {
    var i = tabela.querySelectorAll('tr.linha-comp').length, tr = tabela.querySelector('tr.linha-comp').cloneNode(true);
    tr.querySelectorAll('input').forEach(function (el) { el.value = ''; });
    tr.querySelector('.c-tela').value = '1'; tr.querySelector('.c-obrig').value = '1'; tr.querySelector('.c-obrig').checked = false;
    var usados = comps().map(function (c) { return c.codigo; }), n = i + 1;
    while (usados.indexOf('componente_' + n) >= 0) n++;
    tr.querySelector('.c-codigo').value = 'componente_' + n; tr.querySelector('small').textContent = 'código: componente_' + n;
    tr.querySelector('.c-modo').value = 'aritmetica';
    tabela.appendChild(tr); renumerar(); soma(); atualizarSelects(); simular();
  });
  tabela.addEventListener('change', function (e) { /* E10: marcar "Obrigatório" liga o mínimo com 1 */
    var ob = e.target.closest('.c-obrig');
    if (ob) { var mn = ob.closest('tr').querySelector('.c-min'); if (ob.checked && mn.value.trim() === '') mn.value = '1'; if (!ob.checked) mn.value = ''; }
  });
  tabela.addEventListener('click', function (e) {
    var b = e.target.closest('.c-tirar');
    if (b && tabela.querySelectorAll('tr.linha-comp').length > 1) { b.closest('tr').remove(); renumerar(); soma(); atualizarSelects(); simular(); }
  });
  document.getElementById('simAdd').addEventListener('click', function () { /* 1.15.2: a atividade nova vai para o primeiro componente que ainda aceita */
    var cheios = cheiosDa(null), livre = comps().filter(function (c) { return cheios.indexOf(c.codigo) < 0; })[0] || comps()[0];
    linhaSim('Atividade ' + (sim.querySelectorAll('tr').length + 1), livre.codigo, '10', ''); atualizarSelects(); simular();
  });
  sim.addEventListener('click', function (e) { var b = e.target.closest('.s-tirar'); if (b) { b.closest('tr').remove(); renumerarSim(); travas = null; atualizarSelects(); simular(); } });
  ['input', 'change'].forEach(function (ev) {
    document.getElementById('formRegra').addEventListener(ev, function () { renumerar(); soma(); atualizarSelects(); simular(); });
    document.getElementById('formSim').addEventListener(ev, function () { atualizarSelects(); simular(); });
  });
  renumerar(); soma();
  /* as notas de teste iniciais: duas por componente, sem passar do máximo dele (1.15.2) */
  var MAX_INI = <?= json_encode(array_combine(array_column($r['componentes'], 'codigo'), array_map(fn($c) => M::maximo($c), $r['componentes']))) ?>, k = 0;
  comps().forEach(function (c) { ['8', '6,5'].slice(0, MAX_INI[c.codigo] ? Math.min(2, MAX_INI[c.codigo]) : 2).forEach(function (nota) { linhaSim('Atividade ' + (++k), c.codigo, '10', nota); }); });
  atualizarSelects(); simular();
})();
</script>
