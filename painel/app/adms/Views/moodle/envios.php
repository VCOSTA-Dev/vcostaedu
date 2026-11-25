<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Envios ao Moodle (Onda 3, Etapa 8): a fila. */
$d = $this->dados;
$s = $d['saude'];
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$sit = \App\adms\Controllers\MoodleEnvios::SITUACOES;
$tipos = \App\adms\Controllers\MoodleEnvios::TIPOS;
$cor = ['pendente' => 'bg-warning-lt', 'enviado' => 'bg-success-lt', 'aviso' => 'bg-info-lt', 'falhou' => 'bg-danger-lt'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Envios ao Moodle<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if (!$s['conectado']) : ?>
        <div class="alert alert-secondary" id="enviosSemConexao">O Moodle não está conectado: nada é enviado. <?php if (\App\adms\Models\helper\AdmsNivel::ehAdministrador()) : ?><a href="<?= URLADM ?>moodle/conexao">Conectar ao Moodle</a><?php else : ?>O Administrador conecta em Integrações → Conectar ao Moodle.<?php endif; ?></div>
      <?php elseif (!empty($d['demonstracao'])) : ?>
        <div class="alert alert-info" id="enviosDemonstracao">A demonstração está ligada: o que vai ao Moodle são <b>dados fictícios</b>.</div>
      <?php endif; ?>

      <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
          <div><span class="h3 mb-0" id="contaPendentes"><?= htmlspecialchars((string) ((int) $s['pendentes']), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-secondary">pendente(s)</span></div>
          <div><span class="h3 mb-0 text-danger" id="contaFalhas"><?= htmlspecialchars((string) ((int) $s['falhas']), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-secondary">falharam</span></div>
          <div class="text-secondary">Última chamada que deu certo: <span id="enviosUltimaOk"><?= $s['ultima_ok'] ? $h(date('d/m/Y H:i', strtotime($s['ultima_ok']))) : '—' ?></span></div>
          <div class="ms-auto">
            <?php if ($s['conectado']) : ?>
              <button type="button" class="btn btn-primary" id="enviarTudo" data-progresso="progressoEnvios" data-fase="<?= htmlspecialchars((string) ($d['lote'] && !$d['lote']['concluida'] ? 'passo' : 'iniciar'), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-paper-plane"></i> <?= htmlspecialchars((string) ($d['lote'] && !$d['lote']['concluida'] ? 'Continuar o envio' : 'Enviar tudo o que falta'), ENT_QUOTES, 'UTF-8') ?></button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <ul class="nav nav-pills card-header-pills" id="filtroEnvios">
            <li class="nav-item"><a class="nav-link<?= htmlspecialchars((string) ($d['filtro'] === '' ? ' active' : ''), ENT_QUOTES, 'UTF-8') ?>" href="<?= URLADM ?>moodle-envios/listar">Todos</a></li>
            <?php foreach ($sit as $k => $nome) : ?>
              <li class="nav-item"><a class="nav-link<?= htmlspecialchars((string) ($d['filtro'] === $k ? ' active' : ''), ENT_QUOTES, 'UTF-8') ?>" data-situacao="<?= htmlspecialchars((string) ($k), ENT_QUOTES, 'UTF-8') ?>" href="<?= URLADM ?>moodle-envios/listar?situacao=<?= htmlspecialchars((string) ($k), ENT_QUOTES, 'UTF-8') ?>"><?= $h($nome) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php if (!$d['itens']) : ?>
          <div class="card-body text-secondary" id="filaVazia">Nada na fila<?= htmlspecialchars((string) ($d['filtro'] !== '' ? ' com essa situação' : ''), ENT_QUOTES, 'UTF-8') ?>.</div>
        <?php else : ?>
        <div class="table-responsive">
          <table class="table table-vcenter card-table" id="tabelaEnvios">
            <thead><tr><th>O quê</th><th>Quem / qual</th><th>Situação</th><th>Motivo</th><th>Tentativas</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($d['itens'] as $i) :
                $alvo = [];
                if ($i['aluno_id']) { $alvo[] = $i['aluno_nome'] !== null ? $i['aluno_nome'] . ' (RA ' . $i['aluno_ra'] . ')' : 'aluno ' . (int) $i['aluno_id']; }
                if ($i['oferta_id']) { $alvo[] = $i['materia'] !== null ? $i['materia'] . ' — ' . $i['codtur'] . ' (' . (int) $i['ano'] . ')' : 'disciplina ' . (int) $i['oferta_id']; }
                $fazer = $i['codigo'] ? \VcMoodle::explicar($i['codigo'])[1] : ''; ?>
                <tr data-envio="<?= htmlspecialchars((string) ((int) $i['id']), ENT_QUOTES, 'UTF-8') ?>" data-tipo="<?= $h($i['tipo']) ?>" data-situacao="<?= $h($i['situacao']) ?>" data-codigo="<?= $h($i['codigo']) ?>">
                  <td><?= $h($tipos[$i['tipo']] ?? $i['tipo']) ?></td>
                  <td><?= $h(implode(' · ', $alvo)) ?></td>
                  <td><span class="badge <?= htmlspecialchars((string) ($cor[$i['situacao']] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= $h($sit[$i['situacao']] ?? $i['situacao']) ?></span></td>
                  <td class="small"><?= $h($i['motivo'] ?? '') ?><?php if ($fazer !== '') : ?><div class="text-secondary"><b>O que fazer:</b> <?= $h($fazer) ?></div><?php endif; ?>
                    <?php $contas = $i['situacao'] === 'falhou' && $i['codigo'] === 'email_varias_contas' && $i['aluno_id'] ? array_filter(array_map('intval', (array) json_decode((string) ($i['contas'] ?? ''), true))) : [];
                    if ($contas && $s['conectado']) : ?>
                      <form method="POST" action="<?= URLADM ?>moodle-envios/escolher-conta/<?= htmlspecialchars((string) ((int) $i['id']), ENT_QUOTES, 'UTF-8') ?>" class="d-flex flex-wrap align-items-center gap-2 mt-2 vc-escolher-conta">
                        <label class="form-label mb-0" for="conta<?= htmlspecialchars((string) ((int) $i['id']), ENT_QUOTES, 'UTF-8') ?>">A conta da pessoa no Moodle:</label>
                        <select name="moodle_conta" id="conta<?= htmlspecialchars((string) ((int) $i['id']), ENT_QUOTES, 'UTF-8') ?>" class="form-select form-select-sm w-auto">
                          <?php foreach ($contas as $c) : ?><option value="<?= htmlspecialchars((string) ($c), ENT_QUOTES, 'UTF-8') ?>">Conta nº <?= htmlspecialchars((string) ($c), ENT_QUOTES, 'UTF-8') ?> no Moodle</option><?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-link"></i> Ligar a esta conta</button>
                      </form>
                    <?php endif; ?></td>
                  <td><?= htmlspecialchars((string) ((int) $i['tentativas']), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="text-end">
                    <?php if (in_array($i['situacao'], ['falhou', 'pendente'], true) && $s['conectado']) : ?>
                      <form method="POST" action="<?= URLADM ?>moodle-envios/tentar/<?= htmlspecialchars((string) ((int) $i['id']), ENT_QUOTES, 'UTF-8') ?>" class="d-inline"><button type="submit" class="btn btn-sm btn-outline-primary vc-tentar-envio"><i class="fas fa-rotate-right"></i> Tentar de novo</button></form>
                    <?php endif; ?>
                  </td>
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
<?php
$progressoId = 'progressoEnvios';
$progressoTitulo = 'Enviando ao Moodle';
$progressoGrupos = ['Os envios ao Moodle' => max(1, (int) ($d['lote']['total'] ?? $s['pendentes'] + $s['falhas']))];
$progressoUrl = URLADM . 'moodle-envios/enviar-tudo';
$progressoFim = ['Ver os envios', URLADM . 'moodle-envios/listar'];
include 'app/adms/Views/include_tabler/_progresso.php';
