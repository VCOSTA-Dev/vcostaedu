<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E7 (1.14.0, D-405 a D-409): CHAMADA da oferta — a fonte da frequência e a lista de encontros (lista padrão). */
use App\adms\Models\helper\AdmsUi as Ui;
use App\adms\Models\faltas\AdmsChamada as M;

$d = $this->dados;
$o = $d['oferta'];
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$porChamada = $d['fonte'] === 'chamada';
$enc = $d['encerrada'];
$razao = $enc ? 'Disciplina encerrada: a chamada está em modo leitura' : (!$porChamada ? 'A frequência desta oferta é por lançamento de faltas' : null);
$base = URLADM . 'chamada/';
/* Correção da escola, Parte 2 (B8, D3; D-101): na escola, a disciplina com lançamento de faltas não deixa a pessoa numa
   tela sem chamada — o cartão explica e oferece a troca num passo (ou, se não dá para trocar, diz por quê e leva às
   faltas da etapa); o "Novo encontro" desligado não aparece */
$escolaLanc = !$porChamada && !$enc && \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola();
$semTroca = $escolaLanc ? M::motivoSemChamada((int) $o['id']) : null;
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Chamada — <?= $h($o['materia']) ?> <small class="text-muted"><?= $h($o['codtur']) ?> · <?= htmlspecialchars((string) ((int) $o['ano']), ENT_QUOTES, 'UTF-8') ?></small><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?= Ui::barraDisciplina((int) $o['id'], 'chamada', '', $d['barra'] ?? []) ?>
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if ($enc) : ?>
        <?= Ui::faixaTrava('Disciplina encerrada em ' . date('d/m/Y', strtotime($o['encerrada_em'])) . ': a chamada está em modo leitura.',
          $d['ehAdmin'] ? 'Para mudar, reabra a disciplina (com justificativa) e encerre de novo.' : 'Para mudar, peça ao administrador ou à supervisão da secretaria que reabra a disciplina.', $d['ehAdmin'] ? ['Reabrir no Encerramento', URLADM . 'disciplinas/encerrar/' . (int) $o['id']] : null, 'chamadaEncerrada') ?>
      <?php endif; ?>

      <?php if ($escolaLanc) : ?>
        <div class="card card-md mb-3 border-primary" id="chamadaLancamento">
          <div class="card-body">
            <h3 class="card-title mb-2"><i class="fas fa-clipboard-check me-1"></i> Esta disciplina lança as faltas por etapa</h3>
            <?php if ($semTroca === null) : ?>
              <p class="mb-3">Hoje as faltas desta disciplina são digitadas na tela das etapas. Para fazer a chamada aula a aula, troque para a chamada por aula: depois disso, a falta de cada etapa é a soma das aulas.</p>
              <?= Ui::acaoPost('acao', $base . 'fonte/' . (int) $o['id'], ['fonte' => 'chamada'], 'Passar a fazer a chamada', null,
                ['rotulo' => 'Passar a fazer a chamada', 'icone' => 'fas fa-clipboard-check', 'classe' => 'btn-primary', 'id' => 'passarChamada', 'confirmar' => 'Passar a fazer a chamada por aula nesta disciplina?']) ?>
            <?php else : ?>
              <p class="mb-3" id="semTrocaChamada"><?= $h($semTroca) ?></p>
              <a href="<?= URLADM ?>escola-notas/etapas/<?= (int) $o['id'] ?>" class="btn btn-primary" id="irFaltasEtapa"><i class="fas fa-layer-group me-1"></i> Lançar as faltas na etapa</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
      <div class="card shadow-sm" id="fonteFrequencia" data-fonte="<?= $h($d['fonte']) ?>">
        <div class="card-body py-2 d-flex flex-wrap align-items-center">
          <span class="me-3">Fonte da frequência: <b><?= $h(M::rotuloFonte($d['fonte'])) ?></b></span>
          <?php $outra = $porChamada ? 'lancamento' : 'chamada'; ?>
          <?= Ui::acaoPost('acao', $base . 'fonte/' . (int) $o['id'], ['fonte' => $outra], 'Trocar para ' . M::rotuloFonte($outra), $enc ? 'Disciplina encerrada: a fonte não muda' : null,
            ['rotulo' => 'Trocar para ' . M::rotuloFonte($outra), 'icone' => 'fas fa-exchange-alt', 'classe' => 'btn-outline-secondary', 'confirmar' => 'Trocar a fonte da frequência para "' . M::FONTES[$outra] . '"?']) ?>
          <small class="text-muted ms-3"><?= $porChamada
            ? (\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'As faltas e o total de aulas saem da chamada: a tela de Faltas fica só para leitura (a compensação continua valendo).' : 'As faltas e o total de encontros saem da chamada: a tela de Faltas fica só para leitura (a compensação continua valendo).')
            : 'As faltas são digitadas ou importadas na tela de Faltas. Para fazer chamada, troque a fonte (só sem faltas lançadas).' ?></small>
        </div>
      </div>

      <div class="card shadow">
        <div class="card-header d-flex align-items-center">
          <b class="me-auto"><?= \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'Aulas' : 'Encontros' ?> <?= htmlspecialchars((string) ($porChamada ? '(' . count($d['encontros']) . ')' : ''), ENT_QUOTES, 'UTF-8') ?></b>
          <?= $escolaLanc ? '' : Ui::acao('novo', $base . 'encontro/' . (int) $o['id'], \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'Nova aula' : 'Novo encontro', $razao, ['rotulo' => \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'Nova aula' : 'Novo encontro', 'id' => 'novoEncontro']) ?>
        </div>
        <div class="card-body">
          <table class="table table-sm table-striped table-hover table-compacta lista-dados" id="tabelaEncontros" data-titulo="Chamada — <?= $h($o['materia']) ?> (<?= $h($o['codtur']) ?>)" data-ordem='[[0,"desc"]]'>
            <thead><tr><th>Data</th><th>Horário</th><th>Título</th><th class="num">Presentes</th><th class="num">Ausentes</th><th class="nao-exportar nao-ordenar text-end">Ações</th></tr></thead>
            <tbody>
              <?php foreach ($d['encontros'] as $e) : ?>
                <tr data-encontro="<?= htmlspecialchars((string) ((int) $e['id']), ENT_QUOTES, 'UTF-8') ?>">
                  <td data-order="<?= $h($e['data']) ?>"><?= htmlspecialchars((string) (date('d/m/Y', strtotime($e['data']))), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($e['hora_inicio'] ? substr($e['hora_inicio'], 0, 5) . ($e['hora_fim'] ? '–' . substr($e['hora_fim'], 0, 5) : '') : '—'), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= $h($e['titulo'] ?? '') ?></td>
                  <td class="num"><?= htmlspecialchars((string) ((int) $e['presentes']), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="num"><?= htmlspecialchars((string) ((int) $e['ausentes']), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="acoes-linha">
                    <?= Ui::acao($enc ? 'ver' : 'editar', $base . 'encontro/' . (int) $o['id'] . '?id=' . (int) $e['id'], $enc ? 'Ver a chamada' : 'Fazer/editar a chamada') ?>
                    <?= Ui::acaoPost('excluir', $base . 'excluir/' . (int) $e['id'], [], 'Excluir o encontro', $enc ? 'Disciplina encerrada: a chamada está em modo leitura' : null, ['confirmar' => 'Excluir o encontro de ' . date('d/m/Y', strtotime($e['data'])) . ' e as presenças dele?']) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php if (!$d['encontros']) : ?><p class="text-muted mb-0" id="semEncontros"><?= $porChamada ? (\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'Nenhuma aula registrada. Use "Nova aula".' : 'Nenhum encontro registrado. Use "Novo encontro".') : (\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'Esta disciplina lança as faltas por etapa.' : 'Esta oferta usa lançamento de faltas.') ?></p><?php endif; ?>
          <p class="small text-muted mb-0 mt-2"><?= htmlspecialchars(\App\adms\Models\helper\AdmsUi::plural(count($d['alunos']), 'aluno enturmado', 'alunos enturmados'), ENT_QUOTES, 'UTF-8') ?>. Frequência = 1 − (faltas − compensadas) ÷ <?= \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'aulas previstas' : 'encontros' ?>, a mesma conta do boletim<?= \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? '' : ' e do histórico' ?>.</p>
        </div>
      </div>
    </div>
  </section>
</div>
