<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 2, Etapa 4: a escolha do começo (depois da instalação e no primeiro acesso). */
$d = $this->dados;
$temDados = ($d['dadosInstituicao']['cursos'] ?? 0) + ($d['dadosInstituicao']['alunos'] ?? 0) > 0;
$mont = $d['montagem'] ?? null; // a montagem (ou a remoção) em andamento, interrompida no meio
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Como você quer começar?<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if ($mont !== null && $mont['acao'] === 'montar') : ?>
        <div class="alert alert-warning" id="montagemInterrompida">
          <b>A montagem da demonstração foi interrompida no passo <?= htmlspecialchars((string) ((int) $mont['passo'] + 1), ENT_QUOTES, 'UTF-8') ?> de <?= htmlspecialchars((string) ((int) $mont['total']), ENT_QUOTES, 'UTF-8') ?></b> (<?= htmlspecialchars((string) $mont['grupoAtual'], ENT_QUOTES, 'UTF-8') ?>)<?= !empty($mont['falhou']) ? ': ' . htmlspecialchars((string) $mont['falhou']['motivo'], ENT_QUOTES, 'UTF-8') : '.' ?>
          Nada do que já foi feito se perde: continue de onde parou.
          <div class="mt-2"><button type="button" class="btn btn-warning" id="continuarMontagem" data-progresso="progressoMontagem" data-fase="passo"><i class="fas fa-play"></i> Continuar</button></div>
        </div>
      <?php elseif (!empty($d['demonstracao'])) : ?>
        <div class="alert alert-info" id="jaNaDemonstracao">A demonstração já está ligada. Quando quiser trocar pelos dados da sua instituição, use
          <a href="<?= URLADM ?>demonstracao/instituicao">Começar com os dados da minha instituição</a>.</div>
      <?php endif; ?>
      <div class="row row-cards">
        <div class="col-md-6">
          <div class="card h-100" id="opcaoDemonstracao">
            <div class="card-status-top bg-azure"></div>
            <div class="card-body">
              <h3 class="card-title"><i class="fas fa-flask me-1"></i> Conhecer com dados de demonstração</h3>
              <p class="text-secondary">O sistema monta uma instituição fictícia completa — cursos, disciplinas, professores, alunos, notas, faltas e documentos — e um usuário de cada perfil para você experimentar. Uma faixa avisa, em toda tela, que os dados são fictícios.</p>
              <p class="text-secondary">Quando quiser, o botão <strong>Começar com os dados da minha instituição</strong> remove tudo o que é fictício, sem reinstalar.</p>
              <?php if (!\App\adms\Controllers\Demonstracao::demonstracaoDisponivel()) : /* Onda 4, Etapa 1: a da escola chega numa versão seguinte */ ?>
                <div class="alert alert-info mb-0" id="semDemonstracaoEscola">A demonstração com dados de escola ainda não está disponível nesta versão. Comece pelos <a href="<?= URLADM ?>primeiros-passos/index">Primeiros passos</a>.</div>
              <?php elseif ($temDados) : ?>
                <div class="alert alert-warning mb-0" id="demonstracaoImpedida">Esta instalação já tem dados da instituição (<?= htmlspecialchars((string) ((int) $d['dadosInstituicao']['cursos']), ENT_QUOTES, 'UTF-8') ?> curso(s) e <?= htmlspecialchars((string) ((int) $d['dadosInstituicao']['alunos']), ENT_QUOTES, 'UTF-8') ?> aluno(s)). A demonstração só é montada numa instalação ainda vazia.</div>
              <?php elseif (empty($d['demonstracao']) && $mont === null) : ?>
                <form method="POST" action="<?= URLADM ?>demonstracao/montar" id="formDemonstracao" onsubmit="return false">
                  <?php if (!empty($d['pedeSenha'])) : /* a instalação já tem dado próprio: a senha é pedida de novo */ ?>
                  <div class="mb-3">
                    <label class="form-label required" for="senhaDemonstracao">Confirme a sua senha</label>
                    <input type="password" name="senha" id="senhaDemonstracao" class="form-control" required <?= \VcSenha::atributos('atual') ?>>
                  </div>
                  <?php endif; ?>
                  <button type="submit" class="btn btn-primary btn-lg" id="montarDemonstracao" data-progresso="progressoMontagem" data-fase="iniciar"><i class="fas fa-flask"></i> Conhecer com dados de demonstração</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card h-100" id="opcaoInstituicao">
            <div class="card-status-top bg-green"></div>
            <div class="card-body">
              <h3 class="card-title"><i class="fas fa-building me-1"></i> Configurar a minha instituição agora</h3>
              <p class="text-secondary">Os <strong>Primeiros passos</strong> levam você pela configuração, uma fase de cada vez: a instituição, as regras de nota e frequência, o e-mail de envio, o ano letivo, o primeiro curso, a oferta, os alunos e a primeira nota. Cada fase tem o <strong>Faça comigo</strong>, que conduz o procedimento na própria tela.</p>
              <a href="<?= URLADM ?>demonstracao/comecar?escolha=instituicao" class="btn btn-outline-secondary" id="configurarInstituicao"><i class="fas fa-shoe-prints"></i> Configurar a minha instituição</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<?php
$progressoId = 'progressoMontagem';
$progressoTitulo = 'Montando a demonstração';
$progressoGrupos = $d['grupos'];
$progressoUrl = URLADM . 'demonstracao/montar';
$progressoFim = ['Entrar na demonstração', URLADM . 'home/index'];
$progressoCampos = ['operacao' => '#operacaoMontar', 'senha' => '#senhaDemonstracao'];
include 'app/adms/Views/include_tabler/_progresso.php';
?>
<input type="hidden" id="operacaoMontar" value="montar">
