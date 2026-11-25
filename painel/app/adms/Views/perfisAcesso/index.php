<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Perfis de acesso: a matriz por ação, só para ler (docs/PENTE_FINO.md). */
$m = $this->dados['matriz'];
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Perfis de acesso<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <div class="card mb-3"><div class="card-body text-secondary">
        O que cada perfil pode fazer no sistema, por ação. Esta tela é só para consultar: os perfis vêm prontos e mudam com a
        atualização do sistema. Para dar um perfil a uma pessoa, edite o cadastro dela em <a href="<?= URLADM ?>usuarios/listar">Usuários</a>.
        O <strong>limite</strong> é o perfil mais baixo que pode receber a ação.
      </div></div>
      <div class="card">
        <div class="table-responsive">
          <table class="table table-vcenter card-table table-sm" id="matrizPerfis">
            <thead>
              <tr>
                <th>Ação</th>
                <th class="text-nowrap">Limite</th>
                <?php foreach ($m['perfis'] as $p) : ?>
                  <th class="text-center" data-perfil="<?= htmlspecialchars((string) ((int) $p['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $h($p['nome']) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($m['grupos'] as $grupo => $acoes) : ?>
                <tr class="vc-grupo-acoes"><th colspan="<?= htmlspecialchars((string) (2 + count($m['perfis'])), ENT_QUOTES, 'UTF-8') ?>" class="bg-body-tertiary"><?= $h($grupo) ?></th></tr>
                <?php foreach ($acoes as $a) : ?>
                  <tr data-acao="<?= htmlspecialchars((string) ((int) $a['id']), ENT_QUOTES, 'UTF-8') ?>">
                    <td><?= $h($a['nome']) ?></td>
                    <td class="text-secondary text-nowrap"><?= $h($a['teto']) ?></td>
                    <?php foreach ($m['perfis'] as $p) : $sim = $a['perfis'][(int) $p['id']]; ?>
                      <td class="text-center" data-perfil="<?= htmlspecialchars((string) ((int) $p['id']), ENT_QUOTES, 'UTF-8') ?>" data-tem="<?= $sim ? '1' : '0' ?>">
                        <?php if ($sim) : ?><i class="fas fa-check text-success" title="Pode"></i><span class="visually-hidden">Pode</span><?php else : ?><span class="text-secondary" aria-label="Não pode">—</span><?php endif; ?>
                      </td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>
