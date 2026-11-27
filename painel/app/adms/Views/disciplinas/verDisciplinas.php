<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
if ($this->dados['botao']['list_disc']) {
  $btnDisciplinaVoltar = "disciplinas/listar/";
  $textDisciplinaVoltar = "Todas as Disciplinas";
} else {
  if ($this->dados['botao']['my_disc']) {
    $btnDisciplinaVoltar = "minhas-disciplinas/listar/";
    $textDisciplinaVoltar = "Minhas Disciplinas";
  }
}
if (!empty($this->dados['info_dado']['info'])) {
  $info = $this->dados['info_dado'];

?>
  <div class="vc-pagina">
    <?php ob_start(); ?><a href="<?= htmlspecialchars((string) (URLADM . "{$btnDisciplinaVoltar}{$info['info'][0]['ano']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary rounded-circle" title="Voltar"><i class="fas fa-arrow-left"></i></a> <?= htmlspecialchars((string) ("{$info['info'][0]['materia']} - {$info['info'][0]['codtur']}"), ENT_QUOTES, 'UTF-8') ?> <span class="text-secondary fw-normal" id="numeroOferta" title="O número desta disciplina (informe num atendimento)">#<?= (int) $info['info'][0]['id'] /* 1.103.1 (D-138) */ ?></span><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
    <section class="page-body">
      <div class="container-xl">
        <?php
        if (isset($_SESSION['msg'])) {
          echo $_SESSION['msg'];
          unset($_SESSION['msg']);
        }
        ?>
        <?php /* E8c (R5): a barra única das telas da disciplina; a volta à lista fica à direita */
        $extrasVer = ''; // Onda 3, Etapa 5: a volta à lista é a seta do título (um caminho só)
        if ((int) $info['info'][0]['tipo'] === 4) {
          $extrasVer .= '<a href="' . URLADM . 'disciplinas/ver-polivalente/' . (int) $info['info'][0]['id'] . '" class="btn btn-sm btn-outline-primary"><i class="fas fa-layer-group"></i> Polivalente</a>';
        }
        echo \App\adms\Models\helper\AdmsUi::barraDisciplina((int) $info['info'][0]['id'], 'disciplina', '', $this->dados['barra'] ?? [], $extrasVer); ?>
        <?= \App\adms\Models\notas\AdmsRegraMedia::avisoLimitesHtml((int) $info['info'][0]['id']) /* E10 (D-423): falta cadastrar / acima do máximo */ ?>
        <div class="row">
          <div class="col-12">
            <div class="card shadow">
              <div class="card-body">
                <div class="row">
                  <div class="col-12 col-md-12 col-lg-8 order-2 order-md-1">
                    <?php
                    /* Onda 3, Etapa 5: os atalhos da disciplina viram CARTÕES normais (título e o que se faz lá), no lugar das
                       faixas azuis de largura total com texto claro sobre azul — o mesmo desenho dos cartões da Página Inicial */
                    $qtdAlunos = count($this->dados['info_dado']['qtdeAluno'] ?? []);
                    /* os alunos, no mesmo estilo e na mesma linha dos outros (abre a lista da turma; sem lupa solta) */
                    $cartoesDisc = [['#listaBuscar', 'fas fa-users', 'Alunos', $qtdAlunos . ($qtdAlunos === 1 ? ' aluno na turma' : ' alunos na turma') . '. Ver a lista.', 'cartaoAlunos'],
                      ['medias/final/', 'fas fa-calculator', 'Notas e resultado', 'As notas, a frequência e o resultado de cada aluno.', 'cartaoNotas']];
                    if ((int) $info['info'][0]['tipo'] !== 4 && !empty($this->dados['botao']['list_ativi'])) {
                      $cartoesDisc[] = ['atividades/listar/', 'fas fa-tasks', 'Atividades', 'As atividades e as provas, e as notas de cada uma.', 'cartaoAtividades'];
                    }
                    if (!empty($this->dados['botao']['vis_faltas'])) {
                      $cartoesDisc[] = ['faltas/visualizar/', 'fas fa-user-clock', 'Faltas', 'As faltas de cada aluno e as compensações.', 'cartaoFaltas'];
                    }
                    ?>
                    <div class="row row-cards" id="atalhosDisciplina">
                      <?php foreach ($cartoesDisc as [$rotaC, $iconeC, $tituloC, $textoC, $idC]) : ?>
                      <div class="col-12 col-sm-6 col-xxl-3">
                        <?php if ($rotaC[0] === '#') : ?>
                        <button type="button" class="card card-sm card-link vc-cartao-atalho h-100 w-100 text-start" id="<?= htmlspecialchars((string) ($idC), ENT_QUOTES, 'UTF-8') ?>" data-bs-toggle="modal" data-bs-target="<?= htmlspecialchars((string) ($rotaC), ENT_QUOTES, 'UTF-8') ?>">
                        <?php else : ?>
                        <a class="card card-sm card-link vc-cartao-atalho h-100" id="<?= htmlspecialchars((string) ($idC), ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars((string) (URLADM . $rotaC . (int) $info['info'][0]['id']), ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                          <div class="card-body d-flex align-items-start gap-3">
                            <span class="avatar bg-primary-lt"><i class="<?= htmlspecialchars((string) ($iconeC), ENT_QUOTES, 'UTF-8') ?>"></i></span>
                            <div>
                              <div class="fw-semibold"><?= htmlspecialchars((string) ($tituloC), ENT_QUOTES, 'UTF-8') ?></div>
                              <div class="text-secondary small"><?= htmlspecialchars((string) ($textoC), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                          </div>
                        <?= $rotaC[0] === '#' ? '</button>' : '</a>' ?>
                      </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                  <div class="col-12 col-md-12 col-lg-4 order-1 order-md-2">
                    <h3 class="h4"><?= htmlspecialchars((string) ($info['info'][0]['materia']), ENT_QUOTES, 'UTF-8') ?></h3>
                    <?php
                    /* Onda 3, Etapa 5: rótulo sem valor não aparece (sem sala, sem polo, sem coordenação… a linha some); o nome
                       da instituição, que ficava solto aqui, saiu — está no topo do menu */
                    $iv = $info['info'][0];
                    $tem = static fn($v): bool => $v !== null && trim(strip_tags((string) $v)) !== '';
                    ?>
                    <dl class="row text-sm" id="ofertaMatriz">
                      <?php if ($tem($iv['salas'] ?? null)) : ?><dt class="col-5">Sala</dt><dd class="col-7" id="salaOferta"><?= implode('<br>', array_map(fn($x) => htmlspecialchars($x, ENT_QUOTES, 'UTF-8'), explode('<br>', (string) ($iv['salas'])))) ?></dd><?php endif; ?>
                      <dt class="col-5">Matriz</dt><dd class="col-7"><?= htmlspecialchars((string) ($iv['linhaMatriz'] ?? '— sem ligação'), ENT_QUOTES, 'UTF-8') ?></dd>
                      <?php if ($tem($iv['polo'] ?? null) && !\App\adms\Models\helper\AdmsTipoInstituicao::porTurma()) : /* D-103 (C2) */ ?><dt class="col-5">Polo</dt><dd class="col-7"><?= htmlspecialchars((string) $iv['polo'], ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?>
                      <?php if ($tem($iv['modalidade'] ?? null) && !\App\adms\Models\helper\AdmsTipoInstituicao::porTurma()) : ?><dt class="col-5">Modalidade</dt><dd class="col-7"><?= htmlspecialchars((string) $iv['modalidade'], ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?>
                      <?php if ($iv['encontros'] !== null) : ?><dt class="col-5"><?= \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'Carga horária (aulas)' : 'Encontros' ?></dt><dd class="col-7"><?= htmlspecialchars((string) ((int) $iv['encontros']), ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?>
                      <dt class="col-5">Situação</dt><dd class="col-7" id="situacaoOferta"><?= htmlspecialchars((string) (empty($iv['encerrada_em']) ? 'Aberta' : 'Encerrada em ' . date('d/m/Y', strtotime($iv['encerrada_em']))), ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($this->dados['botao']['encerrar'])) : ?> <a href="<?= htmlspecialchars((string) (URLADM . 'disciplinas/encerrar/' . (int) $iv['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-success ms-1">Encerramento</a><?php endif; ?></dd>
                      <?php /* Onda 3, Etapa 8: o estado real no Moodle (não mais um número digitado); na escolha "vincular",
                         quem edita a oferta liga a disciplina a uma do Moodle */
                      if (\App\adms\Models\moodle\AdmsMoodle::disponivel()) :
                        $em = \App\adms\Models\moodle\AdmsMoodle::estadoOferta($iv['idMoodle'] !== null ? (int) $iv['idMoodle'] : null);
                        $modo = \App\adms\Models\moodle\AdmsMoodle::conexao()['disciplina_modo'] ?? 'criar'; ?>
                        <dt class="col-5">Moodle</dt><dd class="col-7" id="estadoMoodle" data-estado="<?= htmlspecialchars((string) ($em['codigo']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($em['texto'], ENT_QUOTES, 'UTF-8') ?><?= $em['codigo'] === 'ligada' ? ' <span class="text-secondary small">(nº ' . (int) $iv['idMoodle'] . ')</span>' : '' ?>
                          <?php if ($em['codigo'] === 'pendente' && $modo === 'vincular' && !empty($this->dados['botao']['edit_disc'])) : ?>
                            <form method="POST" action="<?= URLADM ?>moodle-envios/ligar-oferta/<?= htmlspecialchars((string) ((int) $iv['id']), ENT_QUOTES, 'UTF-8') ?>" class="input-group input-group-sm mt-1" id="ligarMoodle">
                              <input type="text" class="form-control" name="moodle_disciplina" placeholder="nº ou nome curto no Moodle" required maxlength="100" aria-label="A disciplina do Moodle">
                              <button type="submit" class="btn btn-outline-primary">Ligar</button>
                            </form>
                          <?php endif; ?></dd>
                      <?php endif; ?>
                      <?php if ($tem($iv['coordenacao'] ?? null)) : ?><dt class="col-5">Coordenação do curso</dt><dd class="col-7"><?= htmlspecialchars((string) ($iv['coordenacao']), ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?>
                      <?php if ($tem($iv['professores'] ?? null)) : ?><dt class="col-5">Professor</dt><dd class="col-7"><?= implode('<br>', array_map(fn($x) => htmlspecialchars($x, ENT_QUOTES, 'UTF-8'), explode('<br>', (string) ($iv['professores'])))) ?></dd><?php endif; ?>
                    </dl>

                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <div class="modal fade" id="listaBuscar" tabindex="-1" aria-labelledby="listaBuscarLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="listaBuscarTitulo">Lista de Alunos da Disciplina</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <span id="msgErro"></span>
          <table class="table table-bordered table-striped table-sm table-hover table-compacta lista-dados" data-paginar="nao" data-ordem='[]'>
            <thead>
              <th>Nº</th>
              <th>RM</th>
              <th>Nome</th>
              <th>Situação</th>
            </thead>
            <tbody>
              <?php
              foreach ($this->dados['info_dado']['qtdeAluno'] as $aluno) {
                extract($aluno);
              ?>
                <tr>
                  <td><?= htmlspecialchars((string) ($ordem), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($rm), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($nome_aluno), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars((string) ($observacao), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
              <?php
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
<?php
} else {
  $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-hidden='true'></button>Erro: Não foi possível encontrar a Disciplina!</div>";
  $urlDestino = URLADM . "{$btnDisciplinaVoltar}{$info['info'][0]['ano']}";
  header("Location: $urlDestino");
}
?>