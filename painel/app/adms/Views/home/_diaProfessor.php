<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/*
 * O DIA A DIA DO PROFESSOR NA ESCOLA (Onda 8, correção, Parte 4; D-82): na Página Inicial do professor, as disciplinas
 * dele no ano corrente, cada um com os atalhos do dia a dia — a chamada, as notas (as atividades) e as etapas. Só na
 * escola e só para o papel de professor; cada atalho segue a permissão do perfil.
 */
if (!\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() || !\App\adms\Models\helper\AdmsNivel::ehPapel('professor')) {
  return;
}
$hDia = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$leDia = new \App\adms\Models\helper\AdmsRead();
$leDia->fullRead("SELECT o.id, o.materia, o.codtur, o.ano FROM rm_professores_mat_liga l INNER JOIN rm_professores_materias o ON o.id = l.rm_prof_mat_id
    INNER JOIN esc_turma_ofertas x ON x.oferta_id = o.id
   WHERE l.usuario_id = :u AND l.situacao = 1 AND o.encerrada_em IS NULL
     AND o.ano = (SELECT MAX(o2.ano) FROM rm_professores_mat_liga l2 INNER JOIN rm_professores_materias o2 ON o2.id = l2.rm_prof_mat_id WHERE l2.usuario_id = :u2 AND l2.situacao = 1)
   ORDER BY o.codtur, o.materia", 'u=' . (int) ($_SESSION['colUsuario_id'] ?? 0) . '&u2=' . (int) ($_SESSION['colUsuario_id'] ?? 0));
$compsDia = $leDia->getResultado() ?: [];
if (!$compsDia) {
  return;
}
$podeDia = (new \App\adms\Models\AdmsBotao())->valBotao([
  'chamada' => ['menu_controller' => 'chamada', 'menu_metodo' => 'listar'],
  'notas' => ['menu_controller' => 'atividades', 'menu_metodo' => 'listar'],
  'etapas' => ['menu_controller' => 'escola-notas', 'menu_metodo' => 'etapas'],
]);
?>
<div class="row">
  <div class="col-12">
    <div class="card shadow" id="diaProfessor">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chalkboard-user"></i> O meu dia</h3>
        <div class="card-actions"><button type="button" class="btn btn-action" data-vc-recolher><i class="fas fa-minus"></i></button></div>
      </div>
      <div class="table-responsive">
        <table class="table table-vcenter card-table">
          <thead><tr><th>Disciplina</th><th>Turma</th><th class="text-end">Atalhos</th></tr></thead>
          <tbody>
          <?php foreach ($compsDia as $c) : $id = (int) $c['id']; ?>
            <tr data-oferta="<?= $hDia($id) ?>">
              <td><?= $hDia($c['materia']) ?></td>
              <td><?= $hDia($c['codtur']) ?></td>
              <td class="text-end text-nowrap">
                <?php /* D-101 (B8): o atalho nunca leva a uma tela sem chamada — com o lançamento por etapa e sem como trocar
                   (há falta lançada), ele é "Faltas" e leva às faltas da etapa; sem falta lançada, a tela da chamada
                   oferece a troca num passo */
                  $faltasPorEtapa = \App\adms\Models\faltas\AdmsChamada::fonte($id) !== 'chamada' && \App\adms\Models\faltas\AdmsChamada::motivoSemChamada($id) !== null; ?>
                <?php if ($faltasPorEtapa && !empty($podeDia['etapas'])) : ?><a href="<?= URLADM ?>escola-notas/etapas/<?= $hDia($id) ?>" class="btn btn-sm btn-outline-primary vc-dia-faltas" title="As faltas desta disciplina são lançadas por etapa"><i class="fas fa-user-xmark"></i> Faltas</a>
                <?php elseif (!$faltasPorEtapa && !empty($podeDia['chamada'])) : ?><a href="<?= URLADM ?>chamada/listar/<?= $hDia($id) ?>" class="btn btn-sm btn-outline-primary vc-dia-chamada"><i class="fas fa-clipboard-check"></i> Chamada</a><?php endif; ?>
                <?php if (!empty($podeDia['notas'])) : ?><a href="<?= URLADM ?>atividades/listar/<?= $hDia($id) ?>" class="btn btn-sm btn-outline-primary vc-dia-notas"><i class="fas fa-tasks"></i> Notas</a><?php endif; ?>
                <?php if (!empty($podeDia['etapas'])) : ?><a href="<?= URLADM ?>escola-notas/etapas/<?= $hDia($id) ?>" class="btn btn-sm btn-outline-primary vc-dia-etapas"><i class="fas fa-layer-group"></i> Etapas</a><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
