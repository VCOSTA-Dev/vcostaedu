<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 2, Etapa 5: importar alunos de planilha — o envio e os lotes. */
use App\adms\Models\importacao\AdmsImportacaoAlunos as Imp;

$d = $this->dados;
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$corSit = [1 => 'bg-blue-lt', 2 => 'bg-green-lt', 3 => 'bg-secondary-lt'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Importar alunos de planilha<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if (!empty($d['botao']['enviar'])) : ?>
        <div class="card mb-3" id="cartaoEnvio">
          <div class="card-body">
            <h3 class="card-title">Enviar a planilha</h3>
            <p class="text-secondary mb-2">Um arquivo <strong>CSV</strong> (no Excel: <em>Arquivo → Salvar como → CSV</em>), com uma linha de cabeçalho e um aluno por linha. <strong>Nada é gravado no envio:</strong> o sistema mostra cada linha conferida, e você aplica quando estiver tudo certo.</p>
            <p class="small text-secondary mb-3">Obrigatórias: <strong>RA</strong> e <strong>Nome</strong>. Opcionais: CPF, e-mail, nascimento, sexo, telefone e situação; e curso, grade, ano, polo e modalidade para já vincular o aluno ao curso. Os nomes das colunas podem variar ("Nome completo", "Data de nascimento"): o sistema reconhece e você ajusta o que faltar.</p>
            <form method="POST" action="<?= URLADM ?>importacao-alunos/enviar" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center" id="formEnvio">
              <input type="file" name="arquivo" id="arquivoImportacao" class="form-control" style="max-width: 28rem" accept=".csv,.txt,text/csv" required>
              <button type="submit" class="btn btn-primary" id="enviarImportacao"><i class="fas fa-upload"></i> Enviar e conferir</button>
              <a href="<?= URLADM ?>importacao-alunos/modelo" class="btn btn-outline-secondary" id="baixarModelo"><i class="fas fa-file-csv"></i> Baixar o modelo</a>
            </form>
          </div>
        </div>
      <?php endif; ?>
      <div class="card">
        <div class="card-header"><h3 class="card-title">Importações</h3></div>
        <?php if (empty($d['lotes'])) : ?>
          <div class="card-body text-secondary" id="semLotes">Nenhuma importação ainda.</div>
        <?php else : ?>
          <div class="table-responsive">
            <table class="table table-vcenter card-table" id="tabelaLotes">
              <thead><tr><th>Lote</th><th>Arquivo</th><th>Enviado</th><th>Situação</th><th class="text-end">Linhas</th><th class="text-end">Gravados</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($d['lotes'] as $l) : ?>
                  <tr data-lote="<?= htmlspecialchars((string) ((int) $l['id']), ENT_QUOTES, 'UTF-8') ?>" data-situacao="<?= htmlspecialchars((string) ((int) $l['situacao']), ENT_QUOTES, 'UTF-8') ?>">
                    <td>#<?= htmlspecialchars((string) ((int) $l['id']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-break"><?= $h($l['arquivo_nome']) ?></td>
                    <td><?= $h(date('d/m/Y H:i', strtotime((string) $l['created']))) ?><div class="small text-secondary"><?= $h($l['autor']) ?></div></td>
                    <td><span class="badge <?= htmlspecialchars((string) ($corSit[(int) $l['situacao']] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= $h(Imp::NOMES_SITUACAO[(int) $l['situacao']] ?? '') ?></span></td>
                    <td class="text-end"><?= htmlspecialchars((string) ((int) $l['total']), ENT_QUOTES, 'UTF-8') ?><?php if ((int) $l['situacao'] === 1 && (int) $l['erros']) : ?><div class="small text-danger"><?= htmlspecialchars((string) ((int) $l['erros']), ENT_QUOTES, 'UTF-8') ?> com erro</div><?php endif; ?></td>
                    <td class="text-end"><?= htmlspecialchars((string) ((int) $l['situacao'] === 1 ? '—' : (int) $l['aplicadas']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end"><a href="<?= URLADM ?>importacao-alunos/conferir/<?= htmlspecialchars((string) ((int) $l['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars((string) ((int) $l['situacao'] === 1 ? 'Conferir' : 'Ver'), ENT_QUOTES, 'UTF-8') ?></a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>
