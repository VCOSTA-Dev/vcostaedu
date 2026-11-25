<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
if (!empty($this->dados['info_dado']['info'])) {
  $info = $this->dados['info_dado'];
?>
  <div class="vc-pagina">
    <?php ob_start(); ?><a href="<?= URLADM . "cursos/listar" ?>" class="btn btn-outline-primary rounded-circle" title="Voltar para Cursos"><i class="fas fa-arrow-left"></i></a> <?= htmlspecialchars((string) ($info['info'][0]['nome']), ENT_QUOTES, 'UTF-8') ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
    <section class="page-body">
      <div class="container-xl">
        <?php
        if (isset($_SESSION['msg'])) {
          echo $_SESSION['msg'];
          unset($_SESSION['msg']);
        }
        ?>
        <div class="row">
          <div class="col-12">
            <div class="card shadow">
              <div class="card-body">
                <?php
                if ($this->dados['botao']['edit_cur']) {
                ?>
                  <a href="<?= htmlspecialchars((string) (URLADM . "cursos/editar/{$info['info'][0]['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-warning me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-edit"></i> Editar</a>
                <?php
                }
                ?>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-6 col-md-12">
            <div class="card shadow">
              <div class="card-header">
                <h3 class="card-title">Dados</h3>
              </div>
              <div class="card-body">
                <dl class="row">
                  <dt class="col-sm-4">Nome de Habilitação</dt>
                  <dd class="col-sm-8"><?= htmlspecialchars((string) ($info['info'][0]['nome']), ENT_QUOTES, 'UTF-8') ?></dd>
                  <?php if (!\App\adms\Models\modalidades\AdmsModalidades::ehEad((int) $info['info'][0]['modalidade_id'])) : /* E8d (P-2): no EaD o turno some */ ?>
                  <dt class="col-sm-4">Turno</dt>
                  <dd class="col-sm-8" id="turnoCurso"><?= htmlspecialchars(\App\adms\Models\modalidades\AdmsModalidades::turnos()[(int) $info['info'][0]['periodo']] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                  <?php endif; ?>
                  <dt class="col-sm-4">Modalidade padrão</dt>
                  <dd class="col-sm-8"><?= htmlspecialchars((string) $info['info'][0]['modalidade'], ENT_QUOTES, 'UTF-8') ?></dd>
                  <dt class="col-sm-4">Polo padrão</dt>
                  <dd class="col-sm-8"><?= htmlspecialchars((string) ($info['info'][0]['localidade']), ENT_QUOTES, 'UTF-8') ?></dd>
                  <?php /* E2 (D-360): a frequência mínima que vale para o curso */ $fmCurso = $info['info'][0]['frequencia_minima'] ?? null; ?>
                  <dt class="col-sm-4">Frequência mínima</dt>
                  <?php $CalcV = \App\adms\Models\notas\AdmsCalculoNotas::class; ?>
                  <dd class="col-sm-8" id="curso_frequencia_minima"><?= $fmCurso !== null ? htmlspecialchars($CalcV::numero((string) $fmCurso), ENT_QUOTES, 'UTF-8') . '% (do curso)' : htmlspecialchars($CalcV::numero(\App\adms\Models\helper\AdmsParametro::regrasNotas()['frequenciaMinima']), ENT_QUOTES, 'UTF-8') . '% (a da instituição)' ?></dd>
                  <?php /* E1a (D-340): como o curso sai nos documentos; vazio aparece como — */
                  foreach (['nivel' => 'Nível', 'abreviatura' => 'Sigla', 'nome_extenso' => 'Nome por extenso', 'cabecalho_documento' => 'Linha do cabeçalho'] as $cId => $cRot) : ?>
                  <dt class="col-sm-4"><?= htmlspecialchars((string) ($cRot), ENT_QUOTES, 'UTF-8') ?></dt>
                  <dd class="col-sm-8" id="curso_<?= htmlspecialchars((string) ($cId), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($info['info'][0][$cId] ?? '') !== '' ? (string) $info['info'][0][$cId] : '—', ENT_QUOTES, 'UTF-8') ?></dd>
                  <?php endforeach; ?>
                  <?php /* 1.16.1 (D-447): a regra de média do TCC (nota mínima) e da legenda dos documentos do curso; vazio = o Padrão da instituição */
                  $rc = $this->dados['regraCurso'] ?? null; $rcId = ($rc && $rc['origem'] === 'curso') ? (int) $rc['regraId'] : 0; ?>
                  <dt class="col-sm-4">Regra de média do TCC e dos documentos</dt>
                  <dd class="col-sm-8" id="curso_regra_media">
                    <?php if (!empty($this->dados['botao']['regra_curso'])) : ?>
                      <form method="POST" action="<?= htmlspecialchars((string) (URLADM . 'regra-media/curso/' . (int) $info['info'][0]['id']), ENT_QUOTES, 'UTF-8') ?>" class="d-inline-flex align-items-center" id="formRegraCurso">
                        <select name="regra_media_id" class="form-select form-select-sm me-1" id="regraCurso" aria-label="Regra de média do TCC e dos documentos">
                          <option value="">Padrão da instituição</option>
                          <?php foreach ($this->dados['regrasMedia'] ?? [] as $rm) : if ($rm['padrao']) { continue; } ?><option value="<?= htmlspecialchars((string) ((int) $rm['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ($rm['id'] === $rcId ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= htmlspecialchars($rm['nome'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-primary" id="salvarRegraCurso" title="Salvar a regra do TCC e dos documentos"><i class="fas fa-save"></i></button>
                      </form>
                    <?php else : ?><?= htmlspecialchars($rcId ? (string) $rc['nome'] : 'Padrão da instituição', ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                    <?= \App\adms\Models\notas\AdmsRegraMedia::divergenciaHtml($this->dados['divergenciaCurso'] ?? ['divergencias' => []]) /* 1.16.2 (D-450) */ ?>
                    <small class="text-muted">Vale para a nota mínima da aprovação do TCC e para a legenda dos documentos (nota mínima <?= htmlspecialchars($CalcV::numero((string) ($rc['regra']['notaMinima'] ?? '7.00'), 1), ENT_QUOTES, 'UTF-8') ?>). A média de cada disciplina segue a regra dela.</small>
                  </dd>
                </dl>
                <?php
                /* E3a: as grades do curso vêm da matriz (rm_cursos_grades), com a CH
                   somada dos módulos (sem módulos, das disciplinas). Saíram o "Modelo Atual: Padrão" e o "Anual / 3200h"
                   cravados e os botões mortos de modelo (rotas 85-87 removidas). */
                ?>
                <h6 class="mt-2">Grades (matriz)</h6>
                <?php if (empty($info['grades'])) : ?>
                  <p class="text-muted mb-2">Nenhuma grade.</p>
                <?php else : ?>
                  <ul class="list-unstyled mb-2">
                    <?php foreach ($info['grades'] as $gr) : ?>
                      <li>
                        <?php if ($this->dados['botao']['ver_matriz']) : ?>
                          <a href="<?= htmlspecialchars((string) (URLADM . 'matriz/visualizar/' . (int) $gr['id']), ENT_QUOTES, 'UTF-8') ?>">Grade <?= htmlspecialchars((string) ((int) $gr['versao']), ENT_QUOTES, 'UTF-8') ?></a>
                        <?php else : ?>
                          Grade <?= htmlspecialchars((string) ((int) $gr['versao']), ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                        — <?= htmlspecialchars((string) $gr['nomeSituacao'], ENT_QUOTES, 'UTF-8') ?>
                        — <?= htmlspecialchars((string) (\App\adms\Models\helper\AdmsModuloInterno::ativo() ? (int) $gr['disciplinas'] . ' disciplina(s)' : (int) $gr['modulos'] . ' módulo(s)'), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars((string) ((int) $gr['ch']), ENT_QUOTES, 'UTF-8') ?> h
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
                <?php if ($this->dados['botao']['cad_grade']) : ?>
                  <a href="<?= htmlspecialchars((string) (URLADM . 'matriz/cadastrar-grade?curso=' . (int) $info['info'][0]['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-success btn-sm"><i class="fas fa-folder-plus"></i> Nova grade</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="col-lg-6 col-md-12">
            <div class="card shadow">
              <div class="card-header">
                <h3 class="card-title">Coordenação</h3>
              </div>
              <div class="card-body p-0">
                <ul class="products-list product-list-in-card ps-2 pe-2">
                  <?php
                  if (!empty($info['listCoordenacao'])) {
                    foreach ($info['listCoordenacao'] as $tc) {
                  ?>
                      <li class="item">
                        <div class="product-img">
                          <img src="<?= URLADM ?>assets/img/usuario/user-tie-solid.svg" alt="Coordenador" class="img-size-50">
                        </div>
                        <div class="product-info">
                          <span class="product-title"><?= htmlspecialchars((string) ($tc['posicao']), ENT_QUOTES, 'UTF-8') ?></span>
                          <div class="float-end">
                            <button type="button" class="btn btn-sm btn-outline-dark border-0 me-2" data-bs-toggle="modal" data-bs-target="#editCoordCurso" data-titulo="EDITAR - <?= htmlspecialchars((string) ($tc['professor']), ENT_QUOTES, 'UTF-8') ?>" data-identidade="<?= htmlspecialchars((string) ($tc['id']), ENT_QUOTES, 'UTF-8') ?>" data-curso="<?= htmlspecialchars((string) ($tc['curso_id']), ENT_QUOTES, 'UTF-8') ?>" data-prof="<?= htmlspecialchars((string) ($tc['usuario_id']), ENT_QUOTES, 'UTF-8') ?>" data-posicao="<?= htmlspecialchars((string) ($tc['posicao_id']), ENT_QUOTES, 'UTF-8') ?>"><i class="far fa-trash-alt"></i> Editar</button>
                            <button type="button" class="btn btn-sm btn-outline-danger border-0 me-2" data-bs-toggle="modal" data-bs-target="#apagarCoordCurso" data-titulo="DESATIVAR - <?= htmlspecialchars((string) ($tc['professor']), ENT_QUOTES, 'UTF-8') ?>" data-corpo="Tem certeza de que deseja desligar o <?= htmlspecialchars((string) ("{$tc['posicao']} {$tc['professor']}"), ENT_QUOTES, 'UTF-8') ?> ?" data-identidade="<?= htmlspecialchars((string) ($tc['id']), ENT_QUOTES, 'UTF-8') ?>"><i class="far fa-trash-alt"></i> Apagar</button>
                          </div>
                          <span class="product-description"><?= htmlspecialchars((string) ($tc['professor']), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                      </li>
                  <?php
                    }
                  }
                  ?>
                </ul>
              </div>
              <?php
              if ($this->dados['botao']['add_coord']) {
              ?>
                <div class="card-footer text-center">
                  <button type="button" class="btn w-100 btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCoordenadorCurso" data-curso="<?= htmlspecialchars((string) ($info['info'][0]['id']), ENT_QUOTES, 'UTF-8') ?>" data-corpo="<?= htmlspecialchars((string) ("{$info['info'][0]['nome']}"), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-user-plus"></i> Coordenadores</button>
                </div>
              <?php
              }
              ?>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <div class="modal fade" id="addCoordenadorCurso" tabindex="-1" aria-labelledby="addCoordenadorCursoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addCoordenadorCursotitle">ADICIONAR COORDENADOR</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="<?= htmlspecialchars((string) (URLADM . "cursos/cadastrar-usuario/{$info['info'][0]['id']}"), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" novalidate>
          <div class="modal-body">
            <input type="hidden" name="curso_id" id="curso_id" required>
            <div class="mb-3 col-12">
              <p class="corpoItem"></p>
            </div>
            <div class="mb-3 col-12">
              <select name="usuario_id" id="usuario_id" class="form-select select2" required>
                <option value="">Selecione o usuário</option>
                <?php
                foreach ($this->dados['select']['coordenadores'] as $educa) {
                  echo "<option value='{$educa['id']}'>{$educa['nome']}</option>";
                }
                ?>
              </select>
            </div>
            <div class="mb-3 col-12">
              <select name="posicao_id" id="posicao_id" class="form-select select2" required>
                <option value="0">Selecione a posição</option>
                <?php
                foreach ($this->dados['select']['posicoes'] as $pos) {
                  echo "<option value='{$pos['id']}'>{$pos['nome']}</option>";
                }
                ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <input name="CadCoordenadorCurso" type="submit" class="btn btn-primary botaoEnvia" value="Salvar">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editCoordCurso" tabindex="-1" aria-labelledby="editCoordCursoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editCoordCursotitle">EDITAR POSIÇÃO</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="<?= htmlspecialchars((string) (URLADM . "cursos/editar-usuario/{$info['info'][0]['id']}"), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" novalidate>
          <div class="modal-body">
            <input type="text" name="id" required>
            <input type="text" name="curso_id" id="curso_id" required>
            <div class="mb-3 col-12">
              <select name="posicao_id" id="posicao_id" class="form-select select2" required>
                <option value="">Selecione a posição</option>
                <?php
                foreach ($this->dados['select']['posicoes'] as $pos) {
                  echo "<option value='{$pos['id']}'>{$pos['nome']}</option>";
                }
                ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <input name="EditCoordCurso" type="submit" class="btn btn-primary botaoEnvia" value="Salvar">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="apagarCoordCurso" tabindex="-1" aria-labelledby="apagarCoordCursoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="cabecalhoItem">DESATIVAR COORDENADOR</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= htmlspecialchars((string) (URLADM . "cursos/apagar-usuario/{$info['info'][0]['id']}"), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" novalidate>
        <div class="modal-body">
          <div class="mb-3">
            <input type="text" name="id" class="form-control" value required>
          </div>
          <p class="corpoItem"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <input type="submit" name="DesliCoordCurso" class="btn btn-danger botaoEnvia" value="Apagar">
        </div>
      </form>
    </div>
  </div>
</div>
<?php
} else {
  $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-hidden='true'></button>Erro: Não foi possível encontrar o ensino!</div>";
  $urlDestino = URLADM . 'cursos/listar';
  header("Location: $urlDestino");
}
?>