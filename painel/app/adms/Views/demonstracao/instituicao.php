<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 2, Etapa 4: "Começar com os dados da minha instituição" — a confirmação escrita. */
$d = $this->dados;
$mont = $d['montagem'] ?? null;
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Começar com os dados da minha instituição<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php /* Onda 4, Etapa 1: o tipo da instituição — sempre à vista; trocado só sem dado próprio (AdmsTipoInstituicao::podeTrocar) */
      $tipoAtual = (string) ($d['tipo'] ?? \VcVocabulario::SUPERIOR); $troca = $d['troca'] ?? ['pode' => false, 'motivo' => '']; ?>
      <div class="card mb-3" id="cartaoTipo" data-tipo="<?= htmlspecialchars($tipoAtual, ENT_QUOTES, 'UTF-8') ?>" data-pode="<?= htmlspecialchars((string) ($troca['pode'] ? '1' : '0'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="card-body">
          <h3 class="card-title">Tipo de instituição: <span id="tipoAtual"><?= htmlspecialchars(\VcVocabulario::nome($tipoAtual), ENT_QUOTES, 'UTF-8') ?></span></h3>
          <?php if ($troca['pode']) : ?>
            <p class="text-secondary">A escolha decide as telas, as palavras e as regras que vêm prontas. Ela pode ser trocada agora, enquanto a instalação não tem dados próprios.</p>
            <form method="POST" action="<?= URLADM ?>demonstracao/tipo" id="formTipo">
              <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column flex-md-row gap-2 mb-3">
                <?php foreach (\VcVocabulario::TIPOS as $valor => [$nomeTipo, $frase]) : ?>
                  <label class="form-selectgroup-item flex-fill">
                    <input type="radio" name="tipo" value="<?= htmlspecialchars((string) ($valor), ENT_QUOTES, 'UTF-8') ?>" class="form-selectgroup-input" id="tipo_<?= htmlspecialchars((string) ($valor), ENT_QUOTES, 'UTF-8') ?>"<?= $valor === $tipoAtual ? ' checked' : '' ?>>
                    <div class="form-selectgroup-label d-flex align-items-start p-3">
                      <div class="me-3"><span class="form-selectgroup-check"></span></div>
                      <div class="text-start"><div class="fw-medium"><?= htmlspecialchars($nomeTipo, ENT_QUOTES, 'UTF-8') ?></div><div class="text-secondary small"><?= htmlspecialchars($frase, ENT_QUOTES, 'UTF-8') ?></div></div>
                    </div>
                  </label>
                <?php endforeach; ?>
              </div>
              <button type="submit" class="btn btn-primary" id="gravarTipo"><i class="fas fa-check"></i> Gravar o tipo</button>
            </form>
          <?php else : ?>
            <p class="text-secondary mb-0" id="tipoNaoMuda"><?= htmlspecialchars($troca['motivo'], ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($mont !== null && $mont['acao'] === 'remover') : ?>
        <div class="alert alert-warning" id="remocaoInterrompida">
          <b>A remoção da demonstração foi interrompida no passo <?= htmlspecialchars((string) ((int) $mont['passo'] + 1), ENT_QUOTES, 'UTF-8') ?> de <?= htmlspecialchars((string) ((int) $mont['total']), ENT_QUOTES, 'UTF-8') ?></b><?= !empty($mont['falhou']) ? ': ' . htmlspecialchars((string) $mont['falhou']['motivo'], ENT_QUOTES, 'UTF-8') : '.' ?>
          <div class="mt-2"><button type="button" class="btn btn-warning" id="continuarRemocao" data-progresso="progressoRemocao" data-fase="passo"><i class="fas fa-play"></i> Continuar</button></div>
        </div>
      <?php elseif ($mont !== null) : ?>
        <div class="alert alert-warning" id="montagemEmAndamento">A montagem da demonstração ainda não terminou. Continue por <a href="<?= URLADM ?>demonstracao/comecar">Como você quer começar</a>.</div>
      <?php elseif (empty($d['demonstracao'])) : ?>
        <div class="alert alert-info" id="semDemonstracao">A demonstração não está ligada: não há dados fictícios para remover. Continue pelos <a href="<?= URLADM ?>primeiros-passos/index">Primeiros passos</a>.</div>
      <?php else : ?>
        <div class="card" id="cartaoLimpeza">
          <div class="card-status-top bg-warning"></div>
          <div class="card-body">
            <h3 class="card-title">O que vai acontecer</h3>
            <ul>
              <li>Saem os dados fictícios: a instituição de exemplo, os cursos, as disciplinas, os professores e a equipe de demonstração, os alunos, as notas, as faltas e os documentos emitidos na demonstração.</li>
              <li>A sua conta de administrador e a configuração do sistema continuam.</li>
              <li>Não é preciso reinstalar. Em seguida, os <strong>Primeiros passos</strong> levam você pela configuração da sua instituição.</li>
            </ul>
            <p class="text-secondary">Se houver algum dado que não é da demonstração e que seria perdido (um documento emitido de verdade, ou a instituição já configurada por você), a remoção é recusada e a tela diz o quê.</p>
            <form method="POST" action="<?= URLADM ?>demonstracao/montar" id="formLimpeza" onsubmit="return false">
              <div class="mb-3" style="max-width: 24rem">
                <label class="form-label required" for="confirmacao">Para confirmar, escreva <strong><?= htmlspecialchars($d['frase'], ENT_QUOTES, 'UTF-8') ?></strong></label>
                <input type="text" name="confirmacao" id="confirmacao" class="form-control" autocomplete="off" required>
              </div>
              <button type="submit" class="btn btn-warning" id="removerDemonstracao" data-progresso="progressoRemocao" data-fase="iniciar"><i class="fas fa-building-circle-check"></i> Remover a demonstração e começar</button>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>
<?php
$progressoId = 'progressoRemocao';
$progressoTitulo = 'Removendo a demonstração';
$progressoGrupos = $d['grupos'];
$progressoUrl = URLADM . 'demonstracao/montar';
$progressoFim = ['Ir para os Primeiros passos', URLADM . 'primeiros-passos/index'];
$progressoCampos = ['operacao' => '#operacaoRemover', 'confirmacao' => '#confirmacao'];
include 'app/adms/Views/include_tabler/_progresso.php';
?>
<input type="hidden" id="operacaoRemover" value="remover">
