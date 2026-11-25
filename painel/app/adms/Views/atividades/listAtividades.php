<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Atividades da disciplina (oferta). E8c (A14): barra da disciplina (R5), "Cadastrar atividade" em destaque, lista padrão
   (R2) com tipo em etiqueta e ações com ícone (R3/R4), contador de alunos e cartão com os dados da disciplina.
   Sem abas de bimestre (etapa única, D-76). Do sistema escolar de origem: "Cadastrar Avaliação" continua (grupo Avaliações). */
use App\adms\Models\helper\AdmsUi as Ui;

if (!empty($this->dados['info_dado']['info'])) {
  $info = $this->dados['info_dado'];
  $d = $info['info'][0];
  $f = $this->dados['ficha'] ?? [];
  $bt = $this->dados['botao'];
  $h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
  $encerrada = !empty($f['encerrada_em']);
  $travada = $encerrada ? 'Disciplina encerrada: as atividades não mudam' : null;
  $cores = ['Atividades' => 'info', 'Avaliações' => 'primary', 'Bônus' => 'warning', 'Recuperação' => 'danger'];
  $linhas = [];
  foreach (['exercicios' => 'Atividades', 'avaliacoes' => 'Avaliações', 'bonus' => 'Bônus', 'recuperacaoes' => 'Recuperação'] as $k => $grupo) {
    foreach ($info[$k] ?? [] as $a) { $linhas[] = $a + ['grupoNome' => $grupo]; }
  }
  $extras = '';
  if (!empty($bt['cad_trab'])) {
    $extras .= $travada ? Ui::acao('novo', '#', null, $travada, ['rotulo' => 'Cadastrar atividade'])
      : (count($info['tipos'] ?? []) === 1 /* D-103 (C7): um tipo só, direto ao formulário */
        ? '<a href="' . htmlspecialchars(URLADM . 'atividades/cadastrar/' . (int) $d['id'] . '?tipo=' . (int) $info['tipos'][0]['id'], ENT_QUOTES, 'UTF-8') . '" class="btn btn-sm btn-success" id="botaoCadastrarAtividade"><i class="fas fa-plus"></i> Cadastrar atividade</a>'
        : '<button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addAtividadeNota" id="botaoCadastrarAtividade"><i class="fas fa-plus"></i> Cadastrar atividade</button>');
    if ((int) $d['tipo'] !== 3 && !empty($info['provas'])) {
      $extras .= $travada ? '' : ' <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addAvaliacoes"><i class="fas fa-plus"></i> Cadastrar avaliação</button>';
    }
  }
?>
  <div class="vc-pagina">
    <?php ob_start(); ?>Atividades — <?= $h($d['materia']) ?> <small class="text-muted"><?= $h($d['codtur']) ?> · <?= htmlspecialchars((string) ((int) $d['ano']), ENT_QUOTES, 'UTF-8') ?></small><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
    <section class="page-body">
      <div class="container-xl">
        <?= Ui::barraDisciplina((int) $d['id'], 'atividades', '', $this->dados['barra'] ?? [], $extras) ?>
        <?= \App\adms\Models\notas\AdmsRegraMedia::avisoLimitesHtml((int) $d['id']) /* E10 (D-423): falta cadastrar / acima do máximo */ ?>
        <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
        <?php if ($encerrada) : ?>
          <?= Ui::faixaTrava('Disciplina encerrada em ' . date('d/m/Y', strtotime($f['encerrada_em'])) . ': atividades e notas estão em modo leitura.', 'Para mudar, o administrador reabre a disciplina (com justificativa) na tela Encerramento.', \App\adms\Models\helper\AdmsNivel::ehAdministrador() ? ['Ir ao Encerramento', URLADM . 'disciplinas/encerrar/' . (int) $d['id']] : null) ?>
        <?php endif; ?>
        <div class="row">
          <div class="col-12 col-lg-8">
            <div class="card shadow">
              <div class="card-body">
                <table class="table table-sm table-striped table-hover table-compacta lista-dados" id="tabelaAtividades" data-titulo="Atividades — <?= $h($d['materia']) ?> (<?= $h($d['codtur']) ?>)" data-ordem='[[2,"asc"]]'>
                  <thead><tr><th>Tipo</th><th>Atividade</th><th>Data</th><th class="nao-exportar nao-ordenar text-end">Ações</th></tr></thead>
                  <tbody>
                    <?php foreach ($linhas as $a) : ?>
                      <tr>
                        <td><span class="badge bg-<?= htmlspecialchars((string) ($cores[$a['grupoNome']] ?? 'secondary'), ENT_QUOTES, 'UTF-8') ?>-lt"><?= $h($a['nomeTipo']) ?></span></td>
                        <td><?= !empty($bt['vis_trab']) ? '<a href="' . URLADM . 'atividades/visualizar/' . (int) $a['id'] . '">' . $h($a['nomeAtividade']) . '</a>' : $h($a['nomeAtividade']) ?></td>
                        <td data-order="<?= $h($a['data_inicial'] ?? '') ?>"><?= $h($a['datasatividade']) ?></td>
                        <td class="acoes-linha">
                          <?= !empty($bt['vis_trab']) ? Ui::acao('ver', URLADM . 'atividades/visualizar/' . (int) $a['id'], 'Ver e lançar as notas') : '' ?>
                          <?= !empty($bt['edit_trab']) ? Ui::acao('editar', URLADM . 'atividades/editar/' . (int) $a['id'], 'Editar a atividade', $travada) : '' ?>
                          <?= !empty($bt['del_trab']) ? Ui::acaoPost('excluir', URLADM . 'atividades/apagar/' . (int) $d['id'], ['id' => (int) $a['id'], 'rm_prof_mat_id' => (int) $d['id'], 'ApagarAtividades' => 'Apagar'], 'Apagar a atividade', $travada, ['confirmar' => 'Apagar a atividade "' . $a['nomeAtividade'] . '" e as notas lançadas nela?']) : '' ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
                <?php if (!$linhas) : ?><p class="text-muted mb-0">Nenhuma atividade cadastrada. <?= !$travada && !empty($bt['cad_trab']) ? 'Use <b>Cadastrar atividade</b>, acima.' : '' ?></p><?php endif; ?>
              </div>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="card card-sm vc-indicador shadow-sm">
              <span class="vc-indicador-icone bg-info"><i class="fas fa-users"></i></span>
              <div class="vc-indicador-conteudo">
                <span class="vc-indicador-texto">Alunos na turma</span>
                <span class="vc-indicador-numero" id="contadorAlunos"><?= htmlspecialchars((string) (count($info['qtdeAluno'] ?: [])), ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($info['qtdeAluno'])) : ?><a href="#" data-bs-toggle="modal" data-bs-target="#listaBuscar" class="small"><i class="fas fa-list"></i> Ver a lista</a><?php endif; ?>
              </div>
            </div>
            <div class="card shadow-sm" id="dadosDisciplina">
              <div class="card-header"><h3 class="card-title"><i class="fas fa-book-open"></i> <?= $h($d['materia']) ?></h3></div>
              <div class="card-body p-0">
                <table class="table table-sm mb-0">
                  <tr><th>Situação</th><td><?= Ui::etiqueta($encerrada ? 'Encerrada' : 'Aberta') ?></td></tr>
                  <tr><th><?= preg_match('~,|<br>~', (string) ($f['professores'] ?? '')) ? 'Professores' : 'Professor' ?></th><td><?= $h($f['professores'] ?? '') ?: '<span class="text-muted">sem professor</span>' ?></td></tr>
                  <?php $escolaCartao = \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola(); /* D-103 (C2): na escola, o cartão da escola */ ?>
                  <tr><th><?= $escolaCartao ? 'Segmento' : 'Curso' ?></th><td><?= $h($f['curso'] ?? '—') ?><?= !empty($f['modulo']) && !\App\adms\Models\helper\AdmsModuloInterno::ativo() ? '<br><small class="text-muted">' . $h($f['modulo']) . '</small>' : '' ?></td></tr>
                  <tr><th>Turma</th><td><?= $h($d['codtur']) ?> · <?= htmlspecialchars((string) ((int) $d['ano']), ENT_QUOTES, 'UTF-8') ?></td></tr>
                  <?php if (!$escolaCartao) : ?>
                  <tr><th>Polo</th><td><?= $h($f['polo'] ?? '') ?: '—' ?></td></tr>
                  <tr><th>Modalidade</th><td><?= $h($f['modalidade'] ?? '') ?: '—' ?></td></tr>
                  <tr><th>Realização</th><td><?= !empty($f['data_inicio']) ? $h(date('d/m/Y', strtotime($f['data_inicio']))) . ' a ' . $h(date('d/m/Y', strtotime($f['data_fim']))) : '<span class="text-muted">não informado</span>' ?></td></tr>
                  <?php endif; ?>
                  <tr><th><?= $escolaCartao ? 'Carga horária (aulas)' : 'Encontros' ?></th><td><?= isset($f['encontros']) && $f['encontros'] !== null ? (int) $f['encontros'] : '<span class="text-muted">não informado</span>' ?></td></tr>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
  <div class="modal fade" id="addAtividadeNota" tabindex="-1" aria-labelledby="tituloAddAtividade" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="tituloAddAtividade"><i class="fas fa-plus"></i> Tipo da atividade</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
        <div class="modal-body">
          <?php foreach ($info['tipos'] ?? [] as $tip) : ?>
            <a href="<?= htmlspecialchars((string) (URLADM . 'atividades/cadastrar/' . (int) $d['id'] . '?tipo=' . (int) $tip['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn w-100 btn-outline-dark"><?= $h($tip['nome'] ?? $tip['nomeTipo'] ?? '') ?></a>
          <?php endforeach; ?>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button></div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="addAvaliacoes" tabindex="-1" aria-labelledby="tituloAddAvaliacao" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="tituloAddAvaliacao"><i class="fas fa-plus"></i> Tipo da avaliação</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
        <div class="modal-body">
          <?php foreach ($info['provas'] ?? [] as $tip) : ?>
            <a href="<?= htmlspecialchars((string) (URLADM . 'atividades/cadastrar/' . (int) $d['id'] . '?tipo=' . (int) $tip['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn w-100 btn-outline-dark"><?= $h($tip['nome'] ?? $tip['nomeTipo'] ?? '') ?></a>
          <?php endforeach; ?>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button></div>
      </div>
    </div>
  </div>
  <div class="modal fade" id="listaBuscar" tabindex="-1" aria-labelledby="listaBuscarTitulo" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="listaBuscarTitulo"><i class="fas fa-users"></i> Alunos da turma</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
        <div class="modal-body">
          <table class="table table-sm table-striped table-compacta lista-dados" data-titulo="Alunos — <?= $h($d['materia']) ?> (<?= $h($d['codtur']) ?>)" data-paginar="nao">
            <thead><tr><th class="num">Nº</th><th><?= \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero() ?></th><th>Nome</th><th>Observação</th></tr></thead>
            <tbody>
              <?php foreach ($info['qtdeAluno'] ?: [] as $al) : ?>
                <tr><td class="num"><?= htmlspecialchars((string) ((int) $al['ordem']), ENT_QUOTES, 'UTF-8') ?></td><td><?= $h($al['rm']) ?></td><td><?= $h($al['nome_aluno']) ?></td><td><?= $h($al['observacao'] ?? '') ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
<?php
} else {
  $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-hidden='true'></button>Erro: Não foi possível encontrar a página!</div>";
  header('Location: ' . URLADM . 'disciplinas/listar');
}
