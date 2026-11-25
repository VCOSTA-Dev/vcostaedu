<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
if (isset($this->dados['dados_info'][0])) {
  $info = $this->dados['dados_info'][0];
?>
  <div class="vc-pagina">
    <?php ob_start(); ?><a href="<?= htmlspecialchars((string) (URLADM . "atividades/visualizar/{$info['rm_prof_mat_id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary rounded-circle" title="Voltar"><i class="fas fa-arrow-left"></i></a> <?= htmlspecialchars((string) ("{$info['nomeAtividade']}"), ENT_QUOTES, 'UTF-8') ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
                <a href="<?= htmlspecialchars((string) (URLADM . "atividades/listar/{$info['rm_prof_mat_id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-info me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-arrow-left"></i> Atividades</a>
                <?php
                if ($this->dados['botao']['vis_ativi']) {
                ?>
                  <a href="<?= htmlspecialchars((string) (URLADM . "atividades/visualizar/{$info['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-eye"></i> Visualizar</a>
                <?php
                }
                ?>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="card shadow">
              <form method="POST" action="" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="atividade_id" id="atividade_id" value="<?= htmlspecialchars((string) ($info['id']), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="rm_prof_mat_id" id="rm_prof_mat_id" value="<?= htmlspecialchars((string) ($info['rm_prof_mat_id']), ENT_QUOTES, 'UTF-8') ?>">
                <div class="card-body">
                  <p><strong>Atividade: </strong><?= htmlspecialchars((string) ($info['nomeAtividade']), ENT_QUOTES, 'UTF-8') ?><br><strong>Disciplina: </strong><?= htmlspecialchars((string) ("{$info['rm_prof_mat_id']} - {$info['materia']}"), ENT_QUOTES, 'UTF-8') ?><br><strong>Curso: </strong><?= htmlspecialchars((string) ("{$info['nomeCurso']} - {$info['codtur']}"), ENT_QUOTES, 'UTF-8') ?></p>
                  <div class="row g-2">
                    <div class="mb-3 col-md-12">
                      <label class="form-label"> Arquivo CSV</label>
                      <input type="file" name="arquivo" class="form-control" required>
                    </div>
                  </div>
                </div>
                <div class="card-footer">
                  <a href="<?= htmlspecialchars((string) (URLADM . "atividades/visualizar/{$info['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="me-1 btn btn-secondary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Cancelar</a>
                  <input name="UpNotasCsv" type="submit" class="btn btn-primary botaoEnvia" value="Salvar">
                </div>
              </form>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="card shadow">
              <div class="card-body">
                <strong>Atualização de Notas - Preenchimento da Planilha</strong>
                <p>Para facilitar a atualização de notas de forma eficiente, disponibilizamos uma planilha CSV padrão que você pode preencher com os dados necessários. Siga as instruções abaixo para garantir um preenchimento correto:</p>
                <strong>Passo 1: Download da Planilha Padrão</strong>
                <p>Faça o download da planilha padrão <a href="<?= URLADM . 'faltas/modelo/0?tipo=notas' ?>" id="modeloNotas">clicando aqui</a>. <i> (Certifique-se de que você está usando a versão mais recente da planilha para evitar problemas de compatibilidade.)</i></p>
                <strong>Passo 2: Preenchimento dos Dados</strong>
                <ul>
                  <li>Abra a planilha em sua ferramenta de planilhas favorita (como Microsoft Excel, Google Sheets, etc.).</li>
                  <li>Observe que a planilha contém 2 colunas parametrizadas (rm e nota) para as informações que serão atualizados.</li>
                  <li>Preencha cada linha da planilha com os dados solicitados. Certifique-se de seguir as instruções específicas de cada coluna.</li>
                </ul>
                <strong>Passo 3: Formato do CSV</strong>
                <p>É importante salvar a planilha no formato CSV (Comma-Separated Values) quando terminar de preenchê-la. Muitos programas de planilha permitem que você faça isso selecionando "Salvar como" e escolhendo o formato CSV.</p>
                <strong>Passo 4: Upload da Planilha</strong>
                <p>Envie o arquivo CSV preenchido e clique em salvar.</p>
                <strong>Dicas Importantes</strong>
                <ul>
                  <li>Certifique-se de que os dados estão organizados corretamente, com as colunas na ordem correta e os dados correspondendo às colunas parametrizadas.</li>
                  <li>Verifique se não há erros de formatação, como números e texto, para evitar problemas de importação.</li>
                </ul>
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
          Valor digitado inválido. Os valores não podem ser maiores que 10 e menores que 0.<br>Use vírgula (,) ou ponto (.) para número decimal.<br>Retorne na nota informada e digite um valor válido.
        </div>
      </div>
    </div>
  </div>

<?php
} else {
  $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-hidden='true'></button>Erro: Atividade da disciplinas não encontrada!</div>";
  $urlDestino = URLADM . 'disciplinas/listar';
  header("Location: $urlDestino");
}
?>