<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 6, Etapa 7 (D-59): registrar a instalação — voluntário; o conteúdo exato antes de enviar. */
use App\adms\Models\manutencao\AdmsRegistroInstalacao as R;

$d = $this->dados;
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$e = $d['estado'];
$sit = (string) ($e['situacao'] ?? 'nao');
$esc = $d['escolhas'];
$op = $d['opcionais'];
$cor = ['nao' => 'secondary', 'pendente' => 'warning', 'enviado' => 'success', 'confirmar' => 'warning', 'cancelar_pendente' => 'secondary', 'cancelado' => 'secondary'][$sit] ?? 'secondary';
$marcado = fn($b) => $b ? ' checked' : '';
?>
<div class="vc-pagina">
  <?php $paginaTitulo = 'Registrar a instalação'; include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="alert alert-<?= $h($cor) ?>" id="situacaoRegistro" data-situacao="<?= $h($sit) ?>"><?= $h(R::SITUACOES[$sit] ?? '') ?>
        <?php if (!empty($e['enviado_em'])) : ?><span class="text-secondary small"> Último envio: <?= $h(date('d/m/Y H:i', strtotime((string) $e['enviado_em']))) ?>.</span><?php endif; ?></div>
      <div class="row row-cards">
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h3 class="card-title">O que é</h3></div>
            <div class="card-body">
              <p>O registro é <b>voluntário</b>. Ele ajuda o projeto a saber quantas instituições usam o sistema, com que versão e de que tipo, para planejar as versões e avisar sobre falhas de segurança. Nada muda no uso do sistema se você não registrar.</p>
              <p class="mb-1"><b>Vai sempre</b> (quando você registra): um identificador aleatório desta instalação (ele não diz quem é), a versão, o tipo de instituição, o país e a UF, os módulos ligados e três contagens (alunos, cursos e turmas).</p>
              <p class="mb-1"><b>Vai só se você marcar:</b> o nome da instituição, o endereço do site, um e-mail para avisos de segurança e aparecer na lista de instituições que usam o sistema.</p>
              <p class="mb-0"><b>Nunca vai</b> dado de aluno, professor ou responsável: nem nome, nem RA, nem e-mail.</p>
              <p class="text-secondary small mt-2 mb-0">O envio vai para <code><?= $h($d['endereco'] !== '' ? $d['endereco'] : '(endereço ainda não definido)') ?></code>. Se ele não responder, o registro fica pendente e o sistema tenta de novo sozinho, uma vez por dia. Depois de enviado, o sistema manda as contagens de novo uma vez por mês; se o nome ou o site mudarem, ele pede a sua confirmação antes.</p>
            </div>
          </div>
          <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">As suas escolhas</h3></div>
            <form method="GET" action="<?= URLADM ?>registro-instalacao/index" id="formPrevia" class="card-body">
              <input type="hidden" name="previa" value="1">
              <label class="form-check"><input class="form-check-input" type="checkbox" name="nome" value="1" id="registroNome"<?= $marcado($esc['nome']) ?><?= $op['nome'] === '' ? ' disabled' : '' ?>>
                <span class="form-check-label">Mandar o nome da instituição<?= $op['nome'] !== '' ? ' (' . $h($op['nome']) . ')' : ' (não cadastrado em Dados da instituição)' ?></span></label>
              <label class="form-check"><input class="form-check-input" type="checkbox" name="site" value="1" id="registroSite"<?= $marcado($esc['site']) ?><?= $op['site'] === '' ? ' disabled' : '' ?>>
                <span class="form-check-label">Mandar o endereço do site<?= $op['site'] !== '' ? ' (' . $h($op['site']) . ')' : ' (não cadastrado em Dados da instituição)' ?></span></label>
              <label class="form-check"><input class="form-check-input" type="checkbox" name="listar" value="1" id="registroListar"<?= $marcado($esc['listar']) ?>>
                <span class="form-check-label">Aparecer na lista de instituições que usam o sistema</span></label>
              <div class="mt-2" style="max-width: 26rem"><label class="form-label" for="registroEmail">E-mail para avisos de segurança (opcional)</label>
                <input type="email" class="form-control" name="email" id="registroEmail" value="<?= $h($esc['email']) ?>" maxlength="190" placeholder="ti@suainstituicao.edu.br"></div>
              <button type="submit" class="btn btn-outline-primary mt-3" id="atualizarPrevia">Atualizar o que vai</button>
            </form>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><h3 class="card-title">O que vai, exatamente</h3></div>
            <div class="card-body">
              <pre class="bg-dark text-white p-3 rounded small mb-3" id="conteudoRegistro"><?= $h(json_encode($d['conteudo'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
              <form method="POST" action="<?= URLADM ?>registro-instalacao/enviar" id="formRegistro">
                <input type="hidden" name="nome" value="<?= $esc['nome'] ? '1' : '' ?>">
                <input type="hidden" name="site" value="<?= $esc['site'] ? '1' : '' ?>">
                <input type="hidden" name="listar" value="<?= $esc['listar'] ? '1' : '' ?>">
                <input type="hidden" name="email" value="<?= $h($esc['email']) ?>">
                <button type="submit" class="btn btn-primary" id="enviarRegistro"><i class="fas fa-paper-plane"></i> <?= in_array($sit, ['enviado', 'pendente', 'confirmar'], true) ? 'Confirmar e enviar este conteúdo' : 'Registrar e enviar este conteúdo' ?></button>
              </form>
              <?php if (in_array($sit, ['enviado', 'pendente', 'confirmar'], true)) : ?>
                <form method="POST" action="<?= URLADM ?>registro-instalacao/cancelar" class="mt-3" id="formCancelarRegistro" data-vc-confirma="Cancelar o registro?" data-vc-confirma-extra="O projeto é avisado e nada mais é enviado.">
                  <button type="submit" class="btn btn-outline-danger" id="cancelarRegistro">Cancelar o registro</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
