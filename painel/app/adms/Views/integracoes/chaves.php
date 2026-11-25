<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 2, Etapa 6: as chaves da API. */
$d = $this->dados;
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$dt = fn($v) => $v ? date('d/m/Y H:i', strtotime((string) $v)) : '—';
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Chaves de API<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if (!empty($d['nova'])) : ?>
        <div class="alert alert-warning" id="chaveNova" role="alert">
          <h4 class="alert-title">A chave "<?= $h($d['nova']['nome']) ?>" foi criada. Copie-a agora: ela não aparece de novo.</h4>
          <p class="mb-2">O sistema guarda só um resumo dela (hash); se for perdida, revogue-a e crie outra.</p>
          <pre data-vc-copiar id="textoChaveNova" class="mb-0"><?= $h($d['nova']['chave']) ?></pre>
        </div>
      <?php endif; ?>

      <?php if (!empty($d['botao']['criar'])) : ?>
        <div class="card mb-3" id="cartaoCriarChave">
          <div class="card-body">
            <h3 class="card-title">Criar uma chave</h3>
            <p class="text-secondary">Uma chave por sistema que se integra (assim, dá para revogar uma sem parar as outras). O outro sistema a envia no cabeçalho <code>Authorization: Bearer &lt;chave&gt;</code>. Veja as operações em <a href="<?= URLADM ?>integracoes/documentacao">Documentação da API</a>.</p>
            <form method="POST" action="<?= URLADM ?>integracoes/criar" class="row g-2 align-items-end" id="formCriarChave">
              <div class="col-md-4"><label class="form-label required" for="nomeChave">Nome (quem vai usar)</label><input type="text" name="nome" id="nomeChave" class="form-control" maxlength="100" required placeholder="Ex.: Sistema de matrículas"></div>
              <div class="col-md-3"><label class="form-label" for="validadeChave">Validade (opcional)</label><input type="date" name="validade" id="validadeChave" class="form-control" min="<?= date('Y-m-d') ?>"></div>
              <div class="col-12" id="limiteChave">
                <div class="form-label required">O que a chave pode fazer <span class="text-secondary fw-normal">— marque só o que o outro sistema precisa; gravar inclui consultar o mesmo assunto</span></div>
                <div class="table-responsive"><table class="table table-sm table-vcenter mb-0">
                  <thead><tr><th>Assunto</th><th class="text-center">Nada</th><th class="text-center">Consultar</th><th class="text-center">Consultar e gravar</th></tr></thead>
                  <tbody>
                  <?php foreach (\App\adms\Models\api\AdmsApiChaves::ASSUNTOS as $a => [$nomeA, $cobre]) : if (in_array($a, \App\adms\Models\api\AdmsApiChaves::SO_ESCOLA, true) && !\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola()) { continue; } $soLer = in_array($a, \App\adms\Models\api\AdmsApiChaves::SO_LEITURA, true); ?>
                    <tr data-assunto="<?= htmlspecialchars((string) ($a), ENT_QUOTES, 'UTF-8') ?>"><td><b><?= $h($nomeA) ?></b><div class="small text-secondary"><?= $h($cobre) ?></div></td>
                      <td class="text-center"><input class="form-check-input" type="radio" name="permissao[<?= htmlspecialchars((string) ($a), ENT_QUOTES, 'UTF-8') ?>]" value="" checked aria-label="<?= $h($nomeA) ?>: nada"></td>
                      <td class="text-center"><input class="form-check-input" type="radio" name="permissao[<?= htmlspecialchars((string) ($a), ENT_QUOTES, 'UTF-8') ?>]" value="ler" aria-label="<?= $h($nomeA) ?>: consultar"></td>
                      <td class="text-center"><?php if (!$soLer) : ?><input class="form-check-input" type="radio" name="permissao[<?= htmlspecialchars((string) ($a), ENT_QUOTES, 'UTF-8') ?>]" value="gravar" aria-label="<?= $h($nomeA) ?>: consultar e gravar"><?php else : ?><span class="text-secondary small">—</span><?php endif; ?></td></tr>
                  <?php endforeach; ?>
                  </tbody>
                </table></div>
              </div>
              <div class="col-md-3"><button type="submit" class="btn btn-primary w-100" id="criarChave"><i class="fas fa-key"></i> Criar</button></div>
            </form>
          </div>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header"><h3 class="card-title">Chaves</h3></div>
        <?php if (empty($d['chaves'])) : ?>
          <div class="card-body text-secondary" id="semChaves">Nenhuma chave criada.</div>
        <?php else : ?>
          <div class="table-responsive">
            <table class="table table-vcenter card-table" id="tabelaChaves">
              <thead><tr><th>Nome</th><th>Começa com</th><th>O que pode</th><th>Validade</th><th>Último uso</th><th>Situação</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($d['chaves'] as $k) : $ativa = (int) $k['situacao'] === 1; $vencida = $k['validade'] !== null && $k['validade'] < date('Y-m-d'); ?>
                  <tr data-chave="<?= htmlspecialchars((string) ((int) $k['id']), ENT_QUOTES, 'UTF-8') ?>" data-prefixo="<?= $h($k['prefixo']) ?>">
                    <td><?= $h($k['nome']) ?><div class="small text-secondary">criada em <?= $dt($k['created']) ?> por <?= $h($k['criador']) ?></div></td>
                    <td><code><?= $h($k['prefixo']) ?>…</code></td>
                    <td class="small vc-limite-chave"><?= $h(\App\adms\Models\api\AdmsApiChaves::descrever($k['permissoes'] ?? null)) ?></td>
                    <td><?= $k['validade'] ? $h(date('d/m/Y', strtotime((string) $k['validade']))) : 'sem prazo' ?></td>
                    <td><?= $dt($k['ultimo_uso_em']) ?><?php if ($k['ultimo_uso_ip']) : ?><div class="small text-secondary"><?= $h($k['ultimo_uso_ip']) ?> · <a href="<?= URLADM ?>integracoes/chamadas?chave=<?= htmlspecialchars((string) ((int) $k['id']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ((int) $k['chamadas']), ENT_QUOTES, 'UTF-8') ?> chamada(s)</a></div><?php endif; ?></td>
                    <td><?php if (!$ativa) : ?><span class="badge bg-secondary-lt">Revogada em <?= $dt($k['revogada_em']) ?></span><?php elseif ($vencida) : ?><span class="badge bg-yellow-lt">Vencida</span><?php else : ?><span class="badge bg-green-lt">Ativa</span><?php endif; ?></td>
                    <td class="text-end">
                      <?php if ($ativa && !empty($d['botao']['revogar'])) : ?>
                        <form method="POST" action="<?= URLADM ?>integracoes/revogar/<?= htmlspecialchars((string) ((int) $k['id']), ENT_QUOTES, 'UTF-8') ?>" data-vc-confirma="Revogar esta chave?" data-vc-confirma-extra="O sistema que a usa deixa de acessar a API.">
                          <button type="submit" class="btn btn-sm btn-outline-danger vc-revogar">Revogar</button>
                        </form>
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
