<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
if(isset($this->dados['dados_info']['info'])){
  $info = $this->dados['dados_info'];
  /* Onda 8, correção, Parte 2.1 (D-74): quem grava nota digita no campo da linha; o Enter grava na hora e abre o campo do
     próximo aluno, pronto para digitar; sair do campo com o valor mudado também grava. O sinal é o sutil
     (include_tabler/_gravado.php); a recusa fica no campo, com o motivo, sem perder o que foi digitado. A gravação é a
     de sempre (atividades/edit-notas: a oferta encerrada, a etapa fechada, a escala e a auditoria). */
  /* correção da escola, Parte 1 (B6, D-100): na escola, a atividade de uma etapa fechada mostra os campos travados e o
     aviso no alto (com quem pede a reabertura); antes, os campos pareciam editáveis e só o servidor recusava */
  $etapaAtiv = \App\adms\Models\escola\AdmsEtapaNotas::etapaDaAtividade((int) $info['info'][0]['id']);
  $travaEtapa = $etapaAtiv ? \App\adms\Models\escola\AdmsEtapaNotas::trava((int) $etapaAtiv['oferta_id'], (int) $etapaAtiv['id']) : null;
  $campoTravado = $travaEtapa !== null && empty($info['info'][0]['encerrada_em']) && !empty($this->dados['botao']['edit_notas']);
  $lanca = empty($info['info'][0]['encerrada_em']) && !empty($this->dados['botao']['edit_notas']) && $travaEtapa === null;
?>
<div class="vc-pagina">
  <?php ob_start(); ?><a href="<?= htmlspecialchars((string) (URLADM . "atividades/listar/{$info['info'][0]['rm_prof_mat_id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary rounded-circle" title="Voltar para Disciplina"><i class="fas fa-arrow-left"></i></a> <?= htmlspecialchars((string) ("{$info['info'][0]['nomeAtividade']}"), ENT_QUOTES, 'UTF-8') ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <span id="msgErroAlertaCaixa"></span>
      <div class="row">
        <div class="col-12">
          <div class="card shadow">
            <div class="card-body">
            <?php
            if ($this->dados['botao']['edit_ativi'] && empty($info['info'][0]['encerrada_em']) && $travaEtapa === null) { // E5: encerrada não mostra Editar
              ?>
              <a href="<?= htmlspecialchars((string) (URLADM . "atividades/editar/{$info['info'][0]['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-warning me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-edit"></i> Editar</a>
              <?php
              }
            if ($this->dados['botao']['up_notas'] && empty($info['info'][0]['encerrada_em']) && $travaEtapa === null) {
              ?>
              <a href="<?= htmlspecialchars((string) (URLADM . "atividades/upload-notas/{$info['info'][0]['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-success me-1" onclick='this.style.pointerEvents = "none"; this.textContent = "Processando..."'><i class="far fa-file-excel"></i> Atualizar via Planilha</a>
              <?php
              }
              ?>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-12">
          <?php if (!empty($info['info'][0]['encerrada_em'])) : ?>
            <div class="alert alert-secondary" id="ofertaEncerrada">Oferta encerrada: notas em modo leitura. Só o administrador ou a supervisão da secretaria reabre.</div>
          <?php endif; ?>
          <?php if ($travaEtapa !== null) : ?>
            <div class="alert alert-warning" id="etapaFechadaNotas"><i class="fas fa-lock me-1"></i> <?= htmlspecialchars($travaEtapa, ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>
          <div class="card shadow">
            <div class="card-body">
              <table id="tabelaLancaNotas" class="table table-bordered table-hover table-sm table-striped">
                <thead>
                  <tr>
                    <th>id</th>
                    <th>Nº</th>
                    <th>RM</th>
                    <th>Nome Aluno</th>
                    <th>Nota</th>
                    <?php if (!empty($this->dados['botao']['hist_nota'])) : /* Onda 2, Etapa 3 */ ?><th class="nao-exportar">Histórico</th><?php endif; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  if(!empty($info['listAlunos'])){
                    foreach($info['listAlunos'] as $al){
                      ?>
                      <tr>
                        <td><?= htmlspecialchars((string) ($al['id']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($al['ordenacao']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($al['rm']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $al['nome_aluno'], ENT_QUOTES, 'UTF-8') . " <span class='badge rounded-pill bg-primary-lt'>" . htmlspecialchars((string) $al['observacao'], ENT_QUOTES, 'UTF-8') . '</span>' ?></td>
                        <?php $notaTela = str_replace('.', ',', (string) ($al['nota'] ?? '')); ?>
                        <?php if ($lanca) : ?>
                        <td class="text-nowrap"><span class="d-none vc-nota-texto"><?= htmlspecialchars($notaTela, ENT_QUOTES, 'UTF-8') ?></span><input type="text" inputmode="decimal" autocomplete="off" class="form-control form-control-sm d-inline-block vc-nota" style="width: 5.5rem" name="nota"
                          data-id="<?= htmlspecialchars((string) ((int) $al['id']), ENT_QUOTES, 'UTF-8') ?>" data-gravado="<?= htmlspecialchars($notaTela, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($notaTela, ENT_QUOTES, 'UTF-8') ?>"
                          aria-label="Nota de <?= htmlspecialchars((string) $al['nome_aluno'], ENT_QUOTES, 'UTF-8') ?>"></td>
                        <?php elseif ($campoTravado) : ?>
                        <td class="text-nowrap"><span class="d-none vc-nota-texto"><?= htmlspecialchars($notaTela, ENT_QUOTES, 'UTF-8') ?></span><input type="text" class="form-control form-control-sm d-inline-block vc-nota-travada" style="width: 5.5rem" value="<?= htmlspecialchars($notaTela, ENT_QUOTES, 'UTF-8') ?>" disabled title="A etapa está fechada" aria-label="Nota de <?= htmlspecialchars((string) $al['nome_aluno'], ENT_QUOTES, 'UTF-8') ?> (etapa fechada)"> <i class="fas fa-lock text-secondary" aria-hidden="true"></i></td>
                        <?php else : ?>
                        <td><?= htmlspecialchars($notaTela, ENT_QUOTES, 'UTF-8') ?></td>
                        <?php endif; ?>
                        <?php if (!empty($this->dados['botao']['hist_nota'])) : ?>
                        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-secondary vc-historico-nota"
                          data-url="<?= htmlspecialchars(URLADM . 'auditoria/nota/' . (int) $info['info'][0]['id'] . '?rm=' . rawurlencode((string) $al['rm']), ENT_QUOTES, 'UTF-8') ?>"
                          title="Histórico desta nota" aria-label="Histórico da nota de <?= htmlspecialchars((string) $al['nome_aluno'], ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-clock-rotate-left"></i></button></td>
                        <?php endif; ?>
                      </tr>
                      <?php
                    }
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" id="ValorErrado" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger">
        <h5 class="modal-title" id="exampleModalLabel">Valor Inválido</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <span id="ValorErradoMsg">Nota inválida: use um número de 0 a 10 com até 2 casas decimais (vírgula ou ponto).</span><br>Retorne na nota informada e digite um valor válido.
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="historicoNotaModal" tabindex="-1" aria-labelledby="historicoNotaTitulo" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="historicoNotaTitulo">Histórico desta nota</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body" id="historicoNotaCorpo">Carregando…</div>
    </div>
  </div>
</div>
<script>
  /* Onda 2, Etapa 3: o histórico da nota, numa janela (a página auditoria/nota, só o bloco #historicoNota) */
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('.vc-historico-nota');
    if (!b) { return; }
    var corpo = document.getElementById('historicoNotaCorpo');
    corpo.textContent = 'Carregando…';
    abrirModal('historicoNotaModal');
    fetch(b.getAttribute('data-url'), { credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (html) {
      var d = new DOMParser().parseFromString(html, 'text/html');
      var h = d.getElementById('historicoNota');
      corpo.innerHTML = '';
      if (h) { corpo.appendChild(h); } else { corpo.textContent = 'Não foi possível abrir o histórico.'; }
    }).catch(function () { corpo.textContent = 'Não foi possível abrir o histórico.'; });
  });
</script>
<script>
  /* E4: com a oferta encerrada, a tabela não fica editável (e o POST é recusado no model).
     Onda 2, Etapa 2: nem para quem não grava nota (a Direção e a Coordenação só consultam). */
  var tabelaNome = "<?= htmlspecialchars((string) (empty($info['info'][0]['encerrada_em']) && !empty($this->dados['botao']['edit_notas']) ? 'tabelaLancaNotas' : 'tabelaSomenteLeitura'), ENT_QUOTES, 'UTF-8') ?>";
  var envio = "atividades/edit-notas/";
  /* Onda 8 (D-74): o campo da nota é da própria tela (abaixo); a tabela continua com a exportação, sem a edição na célula */
  var campos = [];
</script>
<?php if ($lanca) : include 'app/adms/Views/include_tabler/_gravado.php'; ?>
<script>
/* Correção da escola, Parte 2 (B1, B2; D-101): o Enter desce NA HORA para o aluno seguinte e a gravação vai em segundo
   plano — o sinal (gravado, ou o motivo da recusa) aparece no campo de origem; digitar rápido ("4", Enter, "11", Enter)
   nunca cai no campo errado. A nota fora da escala (0 a 10, até 2 casas) não sai do campo: o cursor fica nele, com o
   motivo, para corrigir. O que for digitado de novo enquanto a gravação vai é gravado depois dela. */
(function () {
  var url = <?= json_encode(URLADM . 'atividades/edit-notas') ?>;
  var tabela = document.getElementById('tabelaLancaNotas');
  if (!tabela) { return; }
  /* a mesma regra do servidor (AdmsCalculoNotas::centesimos): 1 ou 2 algarismos, até 2 casas, no máximo 10 */
  var ESCALA = /^\d{1,2}([.,]\d{1,2})?$/;
  var MOTIVO = 'Nota inválida: use um número de 0 a 10 com até 2 casas decimais (vírgula ou ponto).';
  function campos() { return Array.prototype.slice.call(tabela.querySelectorAll('input.vc-nota')); }
  function proximo(inp) {
    var l = campos();
    var i = l.indexOf(inp);
    return i >= 0 && i + 1 < l.length ? l[i + 1] : null;
  }
  function abrir(inp) { if (inp) { inp.focus(); inp.select(); } }
  function valida(valor) { return valor === '' || (ESCALA.test(valor) && parseFloat(valor.replace(',', '.')) <= 10); }
  function gravar(inp) {
    var valor = inp.value.trim();
    if (inp.dataset.enviando === '1') { inp.dataset.pendente = '1'; return; } /* grava depois da que está indo */
    if (valor === inp.dataset.gravado && !inp.classList.contains('is-invalid')) { return; }
    if (!valida(valor)) { vcGravado.erro(inp, MOTIVO); return; }
    inp.dataset.enviando = '1';
    inp.dataset.pendente = '';
    vcGravado.gravando(inp);
    var corpo = new URLSearchParams({ id: inp.dataset.id, nota: valor, action: 'edit' });
    var fim = function () {
      inp.dataset.enviando = '';
      if (inp.dataset.pendente === '1') { inp.dataset.pendente = ''; gravar(inp); }
    };
    fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: corpo.toString() })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (j && j.erro === true) { /* o edit-notas responde erro = true quando GRAVOU (o formato herdado) */
          inp.dataset.gravado = valor;
          var t = inp.parentNode.querySelector('.vc-nota-texto');
          if (t) { t.textContent = valor; }
          if (inp.value.trim() === valor) { vcGravado.ok(inp); }
        } else {
          vcGravado.erro(inp, (j && j.msg) || 'A nota não foi gravada.'); /* o que foi digitado fica, para corrigir */
        }
        fim();
      })
      .catch(function () {
        vcGravado.erro(inp, 'Sem conexão com o servidor: a nota não foi gravada.');
        fim();
      });
  }
  tabela.addEventListener('keydown', function (ev) {
    var inp = ev.target;
    if (!inp.classList || !inp.classList.contains('vc-nota')) { return; }
    if (ev.key === 'Enter') {
      ev.preventDefault();
      var valor = inp.value.trim();
      if (!valida(valor)) { vcGravado.erro(inp, MOTIVO); abrir(inp); return; } /* B2: o cursor fica, para corrigir */
      gravar(inp);
      inp.dataset.saiuPorEnter = '1';
      abrir(proximo(inp)); /* B1: desce na hora, sem esperar o servidor */
    }
    if (ev.key === 'Escape') { inp.value = inp.dataset.gravado; vcGravado.limpar(inp); }
  });
  /* sair do campo com o valor mudado também grava: nada se perde (o Enter já mandou) */
  tabela.addEventListener('change', function (ev) {
    var inp = ev.target;
    if (!inp.classList || !inp.classList.contains('vc-nota')) { return; }
    if (inp.dataset.saiuPorEnter === '1') { inp.dataset.saiuPorEnter = ''; return; }
    gravar(inp);
  });
  tabela.addEventListener('input', function (ev) { if (ev.target.dataset) { ev.target.dataset.saiuPorEnter = ''; } });
})();
</script>
<?php endif; ?>
<?php
} else {
  $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-hidden='true'></button>Erro: Atividade da disciplinas não encontrada!</div>";
  $urlDestino = URLADM . 'disciplinas/listar';
  header("Location: $urlDestino");
}
?>