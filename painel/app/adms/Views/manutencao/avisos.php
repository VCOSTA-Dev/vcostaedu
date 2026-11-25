<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Avisos do sistema: as versões, as pendências, a versão nova, o resumo da segurança, o copyright e os links do projeto. */
$d = $this->dados;
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$v = $d['versoes'];
$n = $d['versaoNova'];
$s = $d['seguranca'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Avisos do sistema<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <div class="row row-cards">
        <div class="col-md-6">
          <div class="card h-100" id="cartaoVersoes"><div class="card-header"><h3 class="card-title">Versões</h3></div><div class="card-body">
            <dl class="row mb-0">
              <dt class="col-6">Os arquivos do sistema</dt><dd class="col-6" id="versaoArquivos"><?= $h($v['arquivos'] ?: '—') ?></dd>
              <dt class="col-6">O banco de dados</dt><dd class="col-6" id="versaoBanco"><?= $h($v['banco'] ?: '—') ?></dd>
              <dt class="col-6">O tipo de instituição</dt><dd class="col-6" id="tipoInstituicao"><?= $h(\App\adms\Models\helper\AdmsTipoInstituicao::nome()) ?></dd>
            </dl>
            <?php if ($d['pendentes']) : ?>
              <div class="alert alert-warning mt-3 mb-0" id="avisoPendentes"><?= htmlspecialchars((string) (count($d['pendentes'])), ENT_QUOTES, 'UTF-8') ?> atualização(ões) do banco de dados por aplicar. <a href="<?= URLADM ?>atualizar/index">Atualizar o sistema</a></div>
            <?php else : ?>
              <div class="text-success mt-3" id="semPendentes"><i class="fas fa-circle-check"></i> Nenhuma atualização do banco por aplicar.</div>
            <?php endif; ?>
          </div></div>
        </div>
        <div class="col-md-6">
          <div class="card h-100" id="cartaoVersaoNova"><div class="card-header"><h3 class="card-title">Versão nova</h3></div><div class="card-body">
            <?php if (!$n['ligado']) : ?>
              <div class="text-secondary" id="versaoNovaDesligada">A consulta de versão nova está desligada.</div>
            <?php elseif ($n['novo']) : ?>
              <div class="h3 mb-1" id="versaoNovaDisponivel"><i class="fas fa-gift text-primary"></i> A versão <?= $h($n['novo']) ?> está disponível.</div>
              <?php if (!empty($n['seguranca'])) : ?><div class="alert alert-danger py-2 mb-2" id="versaoNovaSeguranca"><i class="fas fa-shield-halved"></i> <b>Ela corrige uma falha de segurança:</b> atualize assim que puder.</div><?php endif; ?>
              <div class="text-secondary">O sistema não baixa nada sozinho: quem cuida da instalação traz os arquivos novos e usa Atualizar o sistema.<?php if ($n['notas']) : ?> <a href="<?= $h($n['notas']) ?>" rel="noopener" target="_blank">O que mudou</a><?php endif; ?></div>
            <?php elseif ($n['erro']) : ?>
              <div class="text-secondary" id="versaoNovaSemResposta">Não foi possível consultar a versão mais nova agora (a próxima tentativa é amanhã).</div>
            <?php else : ?>
              <div class="text-success" id="versaoNovaEmDia"><i class="fas fa-circle-check"></i> Esta é a versão mais nova.</div>
            <?php endif; ?>
            <?php if ($n['consultada_em']) : ?><div class="text-secondary small mt-2">Consultado em <?= $h(date('d/m/Y H:i', strtotime($n['consultada_em']))) ?>, uma vez por dia, sem enviar nenhum dado desta instalação.</div><?php endif; ?>
          </div></div>
        </div>
        <div class="col-md-6">
          <div class="card h-100" id="cartaoSeguranca"><div class="card-header"><h3 class="card-title">Segurança</h3></div><div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-2">
              <span class="badge bg-success-lt"><?= htmlspecialchars((string) ((int) $s['OK']), ENT_QUOTES, 'UTF-8') ?> OK</span>
              <span class="badge bg-warning-lt"><?= htmlspecialchars((string) ((int) $s['ALERTA']), ENT_QUOTES, 'UTF-8') ?> atenção</span>
              <span class="badge bg-danger-lt" id="segurancaFalhas"><?= htmlspecialchars((string) ((int) $s['FALHA']), ENT_QUOTES, 'UTF-8') ?> a resolver</span>
            </div>
            <a href="<?= URLADM ?>verificacoes-seguranca/index" id="irVerificacoes">Ver as verificações de segurança</a>
          </div></div>
        </div>
        <?php if (!empty($d['moodle'])) : $m = $d['moodle']; ?>
        <div class="col-md-6">
          <div class="card h-100" id="cartaoMoodle"><div class="card-header"><h3 class="card-title">Moodle</h3></div><div class="card-body">
            <?php if (!$m['conectado']) : ?>
              <div class="text-secondary" id="moodleNaoConectado">O Moodle não está conectado.</div>
            <?php else : ?>
              <dl class="row mb-2">
                <dt class="col-7">Última chamada que deu certo</dt><dd class="col-5"><?= $m['ultima_ok'] ? $h(date('d/m/Y H:i', strtotime($m['ultima_ok']))) : '—' ?></dd>
                <dt class="col-7">Envios pendentes</dt><dd class="col-5" id="avisoMoodlePendentes"><?= htmlspecialchars((string) ((int) $m['pendentes']), ENT_QUOTES, 'UTF-8') ?></dd>
                <dt class="col-7">Envios que falharam</dt><dd class="col-5<?= htmlspecialchars((string) ($m['falhas'] ? ' text-danger fw-bold' : ''), ENT_QUOTES, 'UTF-8') ?>" id="avisoMoodleFalhas"><?= htmlspecialchars((string) ((int) $m['falhas']), ENT_QUOTES, 'UTF-8') ?></dd>
              </dl>
            <?php endif; ?>
            <a href="<?= URLADM ?><?= htmlspecialchars((string) ($m['conectado'] ? 'moodle-envios/listar' : 'moodle/conexao'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($m['conectado'] ? 'Ver os envios ao Moodle' : 'Conectar ao Moodle'), ENT_QUOTES, 'UTF-8') ?></a>
          </div></div>
        </div>
        <?php endif; ?>
        <div class="col-md-6">
          <div class="card h-100" id="cartaoProjeto"><div class="card-header"><h3 class="card-title">O projeto</h3></div><div class="card-body">
            <p class="mb-2"><?= $h(\VcLegal::copyright()) ?>. Software livre, sem garantia, sob a licença <?= \VcLegal::LICENCA ?>.</p>
            <?php if (!empty($this->dados['registro'])) : ?><p class="small text-secondary mb-2" id="situacaoRegistroAvisos">Registro da instalação: <?= $h(\App\adms\Models\manutencao\AdmsRegistroInstalacao::SITUACOES[$this->dados['registro']['situacao'] ?? 'nao'] ?? '') ?></p><?php endif; ?>
            <div class="d-flex flex-wrap gap-2">
              <?php $apoie = \VcLegal::linkProjeto(\VcLegal::URL_APOIE, $n); $servicos = \VcLegal::linkProjeto(\VcLegal::URL_SERVICOS, $n); ?>
              <?php if ($apoie !== '') : ?><a class="btn btn-outline-primary" id="linkApoie" href="<?= $h($apoie) ?>" rel="noopener" target="_blank"><i class="fas fa-heart"></i> Apoie o projeto</a><?php endif; ?>
              <?php if ($servicos !== '') : ?><a class="btn btn-outline-primary" id="linkServicos" href="<?= $h($servicos) ?>" rel="noopener" target="_blank"><i class="fas fa-handshake"></i> Serviços</a><?php endif; ?>
              <a class="btn btn-outline-secondary" href="<?= URL ?>sobre.php">Sobre</a>
              <a class="btn btn-outline-secondary" href="<?= URLADM ?>bibliotecas/index">Bibliotecas</a>
              <a class="btn btn-outline-secondary" id="linkRegistro" href="<?= URLADM ?>registro-instalacao/index">Registrar a instalação</a>
            </div>
          </div></div>
        </div>
      </div>
    </div>
  </section>
</div>
