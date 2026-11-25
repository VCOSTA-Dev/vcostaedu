<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
if (!empty($this->dados['info_user']['info'])) {
  $info = $this->dados['info_user']['info'][0];
  $vinculosResumo = $this->dados['vinculos'] ?? [];
?>
  <div class="vc-pagina">
    <?php ob_start(); ?><a href="<?= htmlspecialchars((string) (URLADM . "academicos/listar/{$info['acessoId']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary rounded-circle" data-ttip="tooltip" data-bs-placement="top" title="Voltar"><i class="fas fa-arrow-left"></i></a> <?= htmlspecialchars((string) ($info['nome']), ENT_QUOTES, 'UTF-8') ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
    <section class="page-body">
      <div class="container-xl">
        <?php
        if (isset($_SESSION['msg'])) {
          echo $_SESSION['msg'];
          unset($_SESSION['msg']);
        }
        ?>
        <div class="row">
          <div class="col-md-3">
            <div class="card shadow">
              <div class="card-body box-profile">
                <div class="text-center">
                  <img class="profile-user-img img-fluid rounded-circle" src="<?= htmlspecialchars((string) (URLACAD . "assets/img/academicos/" . (!empty($info['imagem']) ? "{$info['id']}/{$info['imagem']}" : "icone_usuario.png")), ENT_QUOTES, 'UTF-8') ?>" alt="User profile picture">
                </div>
                <h3 class="profile-username text-center"><?= htmlspecialchars((string) ($info['nome']), ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="text-muted text-center"><?= htmlspecialchars((string) ($info['nivelAcesso']), ENT_QUOTES, 'UTF-8') ?></p>
                <hr>
                <span class="btn w-100 btn-primary" style="pointer-events: none;"><?= (isset($info['acessoId']) ? ($info['acessoId'] == 1 ? "<i class='fas fa-user-graduate'></i>" : "<i class='fas fa-user-tie'></i>") : "<i class='fas fa-user-graduate'></i>") ?> Visualizar</span>
                <?php
                if ($this->dados['botao']['edit_user']) {
                ?>
                  <a href="<?= htmlspecialchars((string) (URLADM . "academicos/editar/{$info['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn w-100 btn-outline-warning" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-user-edit"></i> Editar</a>
                <?php
                }
                if (!empty($this->dados['botao']['enviar_link']) && ($info['acessoId'] ?? 0) == 1) {
                ?>
                  <form method="POST" action="<?= htmlspecialchars((string) (URLADM . "academicos/enviar-link/{$info['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="mt-2" onsubmit="this.querySelector('button').disabled = true;">
                    <input type="hidden" name="EnviarLink" value="1"><!-- E5: o botão é desabilitado no envio e deixa de ir no POST; o campo oculto garante o pedido -->
                    <button type="submit" name="EnviarLink" value="1" class="btn w-100 btn-outline-success"><i class="fas fa-key"></i> Enviar link de acesso</button>
                  </form>
                  <small class="text-muted d-block mb-2">O aluno define a própria senha pelo link. Emitir outro invalida o anterior.</small>
                <?php
                }
                if ($this->dados['botao']['edit_senha']) {
                ?>
                  <a href="<?= htmlspecialchars((string) (URLADM . "academicos/editar-senha/{$info['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn w-100 btn-outline-danger" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-lock-open"></i> <?= \App\adms\Models\helper\AdmsTipoInstituicao::porTurma() ? 'Definir uma senha' : 'Editar Senha' /* D-103 (C7): na escola, quem define a senha é o aluno, pelo link; a secretaria só a define de propósito */ ?></a>
                  <?php
                }
                /* Onda 8, financeiro, Parte 1 (D-98): a ficha financeira do aluno, aberta da ficha dele (para quem a vê) */
                if (($info['acessoId'] ?? 0) == 1 && !empty((new \App\adms\Models\AdmsBotao())->valBotao(['f' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'ficha']])['f'])) {
                ?>
                  <a href="<?= htmlspecialchars((string) (URLADM . "financeiro/ficha/{$info['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn w-100 btn-outline-success mt-2" id="abrirFichaFinanceira"><i class="fas fa-wallet"></i> Ficha financeira</a>
                  <?php
                }
                ?>
              </div>
            </div>
            <div class="card shadow">
              <div class="card-body">
                <strong><i class="fas fa-book me-1"></i> Inserido no sistema</strong>
                <p class="text-muted"><?= htmlspecialchars((string) ($info['criacao']), ENT_QUOTES, 'UTF-8') ?></p>
                <hr>
                <strong><i class="fas fa-map-marker-alt me-1"></i> Alterado</strong>
                <p class="text-muted"><?= htmlspecialchars((string) ($info['modificado']), ENT_QUOTES, 'UTF-8') ?></p>
              </div>
            </div>
          </div>

          <div class="col-md-9">
            <div class="card shadow">
              <div class="card-header">
                <h3 class="card-title">VÍNCULO</h3>
                <div class="card-actions"><button type="button" class="btn btn-action" data-vc-recolher><i class="fas fa-minus"></i></button></div>
              </div>
              <div class="card-body">
                <?php if (!$vinculosResumo) : ?>
                  <p class="mb-0 text-warning"><b><?= \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'Sem matrícula num segmento.' : (\App\adms\Models\helper\AdmsTipoInstituicao::ehLivre() ? 'Sem matrícula num curso.' : 'Sem vínculo com curso.') ?></b> <?= \App\adms\Models\helper\AdmsTipoInstituicao::porTurma() ? 'Matricule na aba Matrículas.' : 'Vincule na aba Matrículas.' ?></p>
                <?php endif; ?>
                <?php $escolaFicha = \App\adms\Models\helper\AdmsTipoInstituicao::porTurma(); /* D-106: e o curso livre */ /* 2.2 (D-88): na escola, sem a grade; a turma do ano */
                $turmasResumo = $escolaFicha && $vinculosResumo ? \App\adms\Models\escola\AdmsTurmaAlunos::turmasDoAluno((int) $info['id']) : []; ?>
                <?php foreach ($vinculosResumo as $vr) : $pos = $vr['posicoes'][0] ?? null; ?>
                  <?php if ($escolaFicha) : $tr = $pos ? ($turmasResumo[(int) $pos['ano']] ?? null) : null; ?>
                  <p class="mb-1 vc-resumo-escola"><strong><?= htmlspecialchars($vr['curso'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="badge bg-<?= htmlspecialchars($vr['cores'] ?: 'secondary', ENT_QUOTES, 'UTF-8') ?>-lt"><?= htmlspecialchars($vr['situacao'], ENT_QUOTES, 'UTF-8') ?></span><br>
                    <?= $pos ? (int) $pos['ano'] . ': ' . htmlspecialchars((string) $pos['modulo'], ENT_QUOTES, 'UTF-8') . ($tr ? ' — turma ' . htmlspecialchars($tr['nome'], ENT_QUOTES, 'UTF-8') : ' — sem turma') : 'sem série no ano' ?></p>
                  <?php continue; endif; ?>
                  <p class="mb-1"><strong><?= htmlspecialchars($vr['curso'], ENT_QUOTES, 'UTF-8') ?></strong> <span class="badge bg-secondary-lt ms-1 vc-grade" title="A versão da grade (a matriz) desta matrícula">Grade <?= htmlspecialchars((string) ((int) $vr['versao']), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="badge bg-<?= htmlspecialchars($vr['cores'] ?: 'secondary', ENT_QUOTES, 'UTF-8') ?>-lt"><?= htmlspecialchars($vr['situacao'], ENT_QUOTES, 'UTF-8') ?></span><br>
                    <?= $pos ? (!empty($this->dados['semModulos']) ? 'Registrado em ' . (int) $pos['ano'] : (int) $pos['ano'] . ': ' . htmlspecialchars((string) $pos['modulo'], ENT_QUOTES, 'UTF-8')) : 'sem registro no ano' ?></p>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="card card-tabs shadow">
              <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs">
                  <li class="nav-item">
                    <a class="nav-link <?= ($this->dados['tabActive'] == 'tabeInfo' ? 'active' : '') ?>" id="tabeInfo-tab" data-bs-toggle="pill" href="#tabeInfo" role="tab" aria-controls="tabeInfo" aria-selected="<?= ($this->dados['tabActive'] == 'tabeInfo' ? 'true' : 'false') ?>"><?= \App\adms\Models\helper\AdmsTipoInstituicao::porTurma() ? 'Informações' : 'INFORMAÇÕES DO USUÁRIO' /* D-103 (C7): na escola, as abas na mesma caixa */ ?></a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link <?= ($this->dados['tabActive'] == 'tabeMatricula' ? 'active' : '') ?>" id="tabeMatricula-tab" data-bs-toggle="pill" href="#tabeMatricula" role="tab" aria-controls="tabeMatricula" aria-selected="<?= ($this->dados['tabActive'] == 'tabeMatricula' ? 'true' : 'false') ?>"><?= \App\adms\Models\helper\AdmsTipoInstituicao::porTurma() ? 'Matrículas' : 'MATRÍCULAS' ?></a>
                  </li>
                  <?php /* Onda 7, Etapa 2: os responsáveis do aluno (só na escola, para quem pode vê-los) */
                  $verResp = (int) ($info['acessoId'] ?? 0) === 1 && \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola()
                    && !empty((new \App\adms\Models\AdmsBotao())->valBotao(['r' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'aluno']])['r']); ?>
                  <?php if ($verResp) : ?>
                  <li class="nav-item">
                    <a class="nav-link <?= ($this->dados['tabActive'] == 'tabeResponsaveis' ? 'active' : '') ?>" id="tabeResponsaveis-tab" data-bs-toggle="pill" href="#tabeResponsaveis" role="tab" aria-controls="tabeResponsaveis">Responsáveis</a>
                  </li>
                  <?php endif; ?>
                  <?php if (!empty($this->dados['botao']['emitir'])) : ?>
                  <li class="nav-item">
                    <a class="nav-link <?= ($this->dados['tabActive'] == 'tabeHistoricos' ? 'active' : '') ?>" id="tabeHistoricos-tab" data-bs-toggle="pill" href="#tabeHistoricos" role="tab" aria-controls="tabeHistoricos" aria-selected="<?= ($this->dados['tabActive'] == 'tabeHistoricos' ? 'true' : 'false') ?>"><?= \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'Documentos' : 'HISTÓRICOS' /* 2.1 (D-87) */ ?></a>
                  </li>
                  <?php endif; ?>
                  <!-- <li class="nav-item">
                  <a class="nav-link" id="tabeMessagem-tab" data-bs-toggle="pill" href="#tabeMessagem" role="tab" aria-controls="tabeMessagem" aria-selected="false">Messagens</a>
                </li> -->
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content" id="custom-tabs-three-tabContent">
                  <div class="tab-pane fade <?= ($this->dados['tabActive'] == 'tabeInfo' ? 'show active' : '') ?>" id="tabeInfo" role="tabpanel" aria-labelledby="tabeInfo-tab">
                    <?php
                    $e = fn($c) => htmlspecialchars((string) ($info[$c] ?? ''), ENT_QUOTES);
                    $cpfFmt = !empty($info['cpf']) ? preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $info['cpf']) : '';
                    ?>
                    <dl class="row mb-0">
                      <dt class="col-sm-4"><?= \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero() ?></dt><dd class="col-sm-8"><?= $e('rm') ?></dd>
                      <dt class="col-sm-4">Nome social</dt><dd class="col-sm-8"><?= $e('nome_social') ?: '—' ?></dd>
                      <dt class="col-sm-4">CPF</dt><dd class="col-sm-8"><?= htmlspecialchars($cpfFmt) ?: '—' ?></dd>
                      <dt class="col-sm-4">RG</dt><dd class="col-sm-8"><?= trim($e('rg') . ' ' . $e('rg_org') . ' ' . $e('rg_uf')) ?: '—' ?></dd>
                      <dt class="col-sm-4">Nascimento</dt><dd class="col-sm-8"><?= htmlspecialchars((string) (!empty($info['nasc_data']) ? date('d/m/Y', strtotime($info['nasc_data'])) : '—'), ENT_QUOTES, 'UTF-8') ?><?= !empty($info['nasc_local']) ? ' — ' . $e('nasc_local') . '/' . $e('nasc_uf') : '' ?></dd>
                      <dt class="col-sm-4">Nacionalidade</dt><dd class="col-sm-8"><?= $e('nasc_nac') ?: '—' ?></dd>
                      <dt class="col-sm-4">Filiação</dt><dd class="col-sm-8"><?= trim($e('nome_mae') . ($e('nome_mae') && $e('nome_pai') ? ' e ' : '') . $e('nome_pai')) ?: '—' ?></dd>
                      <dt class="col-sm-4">E-mail</dt><dd class="col-sm-8"><?= $e('email') ?: '— (sem e-mail o link de acesso não pode ser enviado)' ?></dd>
                      <?php /* Onda 3, Etapa 5: rótulo sem valor não aparece */ ?>
                      <?php if (trim((string) ($info['telefone'] ?? '') . (string) ($info['celular'] ?? '')) !== '') : ?>
                      <dt class="col-sm-4">Telefone / celular</dt><dd class="col-sm-8"><span class="campotelefone"><?= $e('telefone') ?></span> <span class="campocelular"><?= $e('celular') ?></span></dd>
                      <?php endif; ?>
                      <?php if (trim((string) ($info['endereco'] ?? '') . (string) ($info['cidade'] ?? '') . (string) ($info['cep'] ?? '')) !== '') : ?>
                      <dt class="col-sm-4">Endereço</dt><dd class="col-sm-8"><?= $e('endereco') ?><?= !empty($info['numero']) ? ', ' . $e('numero') : '' ?><?= !empty($info['cidade']) ? ' — ' . $e('cidade') . '/' . $e('uf') : '' ?><?= !empty($info['cep']) ? ' — CEP ' . $e('cep') : '' ?></dd>
                      <?php endif; ?>
                    </dl>
                  </div>
                  <div class="tab-pane fade <?= ($this->dados['tabActive'] == 'tabeMatricula' ? 'show active' : '') ?>" id="tabeMatricula" role="tabpanel" aria-labelledby="tabeMatricula-tab">
                    <?php
                    /* Onda 8, correção, segunda leva, 2.2 (D-88): na escola, a aba própria (o segmento, a série, a turma, o ano e a situação) */
                    include __DIR__ . (\App\adms\Models\helper\AdmsTipoInstituicao::porTurma() ? '/vinculoEscola.php' : '/vinculoAluno.php');
                    ?>
                  </div>
                  <?php if ($verResp) : ?>
                  <div class="tab-pane fade <?= ($this->dados['tabActive'] == 'tabeResponsaveis' ? 'show active' : '') ?>" id="tabeResponsaveis" role="tabpanel" aria-labelledby="tabeResponsaveis-tab">
                    <?php include __DIR__ . '/responsaveisAluno.php'; ?>
                  </div>
                  <?php endif; ?>
                  <?php if (!empty($this->dados['botao']['emitir'])) : ?>
                  <div class="tab-pane fade <?= ($this->dados['tabActive'] == 'tabeHistoricos' ? 'show active' : '') ?>" id="tabeHistoricos" role="tabpanel" aria-labelledby="tabeHistoricos-tab">
                    <?php include __DIR__ . '/historicosAluno.php'; ?>
                  </div>
                  <?php endif; ?>
                  <!-- <div class="tab-pane fade" id="tabeMessagem" role="tabpanel" aria-labelledby="tabeMessagem-tab">
                  Em processo....
                </div> -->
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>


<?php
} else {
  $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-hidden='true'></button>Erro: Usuário acadêmico não encontrado!</div>";
  $urlDestino = URLADM . 'academicos/listar';
  header("Location: $urlDestino");
}
?>