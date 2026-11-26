<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* OS CADASTROS DO HISTÓRICO ESCOLAR (D-110): em abas. Nenhum texto vem pronto de um estado: o que o documento imprime é
   o que a escola cadastra aqui (o que ficar vazio vem dos dados da instituição, ou fica em branco, com aviso na prévia). */
use App\adms\Models\historicoEscola\AdmsHistBase as B;
use App\adms\Models\historicoEscola\AdmsHistCadastros as C;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$aba = $d['aba'];
$nivel = $d['nivel'];
$pode = $d['pode'];
$url = fn($a, $n = null, $extra = '') => URLADM . 'historico-escola/cadastros?aba=' . $a . '&nivel=' . ($n ?? $nivel) . $extra;
$dis = fn($ok) => $ok ? '' : ' disabled';
?>
<div class="vc-pagina" id="historicoCadastros">
  <?php $paginaTitulo = 'Cadastros do histórico escolar';
  $paginaPretitulo = 'Histórico escolar';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <ul class="nav nav-tabs mb-3" id="abasHistorico">
        <?php foreach (\App\adms\Controllers\HistoricoEscola::ABAS as $k => $rot) : ?>
          <li class="nav-item"><a class="nav-link<?= $k === $aba ? ' active' : '' ?>" id="aba-<?= $k ?>" href="<?= $url($k) ?>"><?= $h($rot) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <?php if (in_array($aba, ['textos', 'observacoes'], true)) : ?>
        <div class="btn-group mb-3" role="group" aria-label="Nível">
          <?php foreach (B::NIVEIS as $k => $rot) : ?><a class="btn <?= $k === $nivel ? 'btn-primary' : 'btn-outline-primary' ?>" id="nivel-<?= $k ?>" href="<?= $url($aba, $k) ?>"><?= $h($rot) ?></a><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($aba === 'textos') : $f = $d['form']; $atual = $d['atual']; ?>
        <div class="row g-3">
          <div class="col-lg-3">
            <div class="card"><div class="card-header"><h3 class="card-title">Vigências</h3></div>
              <div class="list-group list-group-flush" id="listaVigencias">
                <?php foreach ($d['vigencias'] as $v) : ?>
                  <a class="list-group-item list-group-item-action<?= $atual && (int) $atual['id'] === (int) $v['id'] && !$d['nova'] ? ' active' : '' ?>" href="<?= $url('textos', null, '&vigencia=' . (int) $v['id']) ?>">vigente desde <?= $h(B::br($v['vigente_desde'])) ?></a>
                <?php endforeach; ?>
                <?php if (!$d['vigencias']) : ?><div class="list-group-item text-secondary" id="semVigencia">Nenhum texto cadastrado: o documento usa os dados da instituição e o texto de partida.</div><?php endif; ?>
              </div>
              <?php if ($pode['texto'] && $d['vigencias']) : ?><div class="card-footer"><a class="btn btn-outline-primary w-100" id="novaVigencia" href="<?= $url('textos', null, '&nova=1') ?>"><i class="fas fa-plus me-1"></i> Nova vigência</a></div><?php endif; ?>
            </div>
            <p class="small text-secondary mt-2">O documento usa o texto de maior "vigente desde" até a data dele. A vigência nova começa com a cópia da que vale hoje.</p>
            <form method="get" class="card card-body" id="formConferir"><input type="hidden" name="aba" value="textos"><input type="hidden" name="nivel" value="<?= $h($nivel) ?>">
              <label class="form-label" for="dataConferir">Qual texto vale na data</label>
              <div class="d-flex gap-2"><input type="date" class="form-control" name="conferir" id="dataConferir" value="<?= $h($d['conferir']['data'] ?? '') ?>"><button class="btn btn-outline-primary" id="conferirVigencia">Ver</button></div>
              <?php if ($d['conferir']) : ?><div class="mt-2" id="vigenciaConferida"><?= $d['conferir']['vigencia'] ? 'Em ' . $h(B::br($d['conferir']['data'])) . ' vale a vigência de ' . $h(B::br($d['conferir']['vigencia']['vigente_desde'])) . '.' : 'Em ' . $h(B::br($d['conferir']['data'])) . ' nenhuma vigência vale: o documento usa os dados da instituição e o texto de partida.' ?></div><?php endif; ?>
            </form>
          </div>
          <div class="col-lg-9">
            <form method="post" action="<?= URLADM ?>historico-escola/salvar-texto" class="card" id="formTexto">
              <input type="hidden" name="nivel" value="<?= $h($nivel) ?>">
              <input type="hidden" name="id" value="<?= $d['nova'] ? 0 : (int) ($atual['id'] ?? 0) ?>">
              <div class="card-header"><h3 class="card-title"><?= $d['nova'] ? 'Nova vigência' : 'Vigente desde ' . $h(B::br($atual['vigente_desde'] ?? '')) ?> — <?= $h(B::NIVEIS[$nivel]) ?></h3></div>
              <div class="card-body row g-3">
                <div class="col-md-4"><label class="form-label" for="tVigencia">Vigente desde</label>
                  <input type="date" class="form-control" name="vigente_desde" id="tVigencia" value="<?= $h($d['nova'] ? date('Y-m-d') : ($atual['vigente_desde'] ?? '')) ?>"<?= $dis($pode['texto']) ?>></div>
                <?php foreach (C::CAMPOS_TEXTO as $c => $rot) : $id = 't_' . $c; ?>
                  <div class="<?= in_array($c, C::LONGOS, true) ? 'col-12' : 'col-md-6' ?>">
                    <label class="form-label" for="<?= $id ?>"><?= $h($rot) ?></label>
                    <?php if ($c === 'unidade_carga') : ?>
                      <select class="form-select" name="<?= $c ?>" id="<?= $id ?>"<?= $dis($pode['texto']) ?>><option value="aulas"<?= ($f[$c] ?? '') !== 'horas' ? ' selected' : '' ?>>aulas</option><option value="horas"<?= ($f[$c] ?? '') === 'horas' ? ' selected' : '' ?>>horas</option></select>
                    <?php elseif (in_array($c, C::LONGOS, true)) : ?>
                      <textarea class="form-control" rows="3" name="<?= $c ?>" id="<?= $id ?>"<?= $dis($pode['texto']) ?>><?= $h($f[$c] ?? '') ?></textarea>
                    <?php else : ?>
                      <input type="text" class="form-control" name="<?= $c ?>" id="<?= $id ?>" value="<?= $h($f[$c] ?? '') ?>"<?= $dis($pode['texto']) ?>>
                    <?php endif; ?>
                    <?php if (in_array($c, ['escola_nome', 'escola_endereco', 'cidade'], true)) : ?><div class="form-hint">Vazio: vale o dos dados da instituição (<?= $h($d['resolvido'][$c] ?: 'não cadastrado') ?>).</div><?php endif; ?>
                    <?php if (in_array($c, ['fecho_declaracao', 'fecho_certificado'], true)) : ?><div class="form-hint">Marcadores: <?= $h(implode(' ', C::MARCADORES)) ?>.</div><?php endif; ?>
                    <?php if ($c === 'rotulo_registro') : ?><div class="form-hint">Como a rede do estado chama o registro do aluno (o RM é o número da escola). Vazio: a linha fica sem rótulo.</div><?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
              <?php if ($pode['texto']) : ?><div class="card-footer"><button type="submit" class="btn btn-primary" id="salvarTexto"><i class="fas fa-save me-1"></i> Salvar</button></div><?php endif; ?>
            </form>
          </div>
        </div>

      <?php elseif ($aba === 'observacoes') : ?>
        <div class="card mb-3">
          <div class="table-responsive"><table class="table table-vcenter card-table" id="tabelaObservacoes">
            <thead><tr><th>Ordem</th><th>Chave</th><th>Título</th><th>Tipo</th><th>Ativa</th><th>Texto</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($d['observacoes'] as $o) : ?>
              <tr data-chave="<?= $h($o['chave']) ?>"><td><?= (int) $o['ordem'] ?></td><td><code><?= $h($o['chave']) ?></code></td><td><?= $h($o['titulo']) ?></td>
                <td><?= $o['tipo'] === 'padrao' ? 'sai sempre' : 'marcada por série' ?></td><td><?= $o['ativo'] ? 'sim' : 'não' ?></td><td class="small"><?= $h(mb_strimwidth($o['texto'], 0, 120, '…')) ?></td>
                <td><?php if ($pode['observacao']) : ?><a class="btn btn-sm btn-outline-secondary" href="<?= $url('observacoes', null, '&editar=' . (int) $o['id']) ?>#formObservacao">Editar</a><?php endif; ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$d['observacoes']) : ?><tr><td colspan="7" class="text-secondary">Nenhuma observação cadastrada neste nível.</td></tr><?php endif; ?>
            </tbody></table></div>
        </div>
        <?php if ($pode['observacao']) : $ed = null; foreach ($d['observacoes'] as $o) { if ((int) $o['id'] === (int) ($_GET['editar'] ?? 0)) { $ed = $o; } } ?>
          <form method="post" action="<?= URLADM ?>historico-escola/salvar-observacao" class="card" id="formObservacao">
            <input type="hidden" name="nivel" value="<?= $h($nivel) ?>"><input type="hidden" name="id" value="<?= (int) ($ed['id'] ?? 0) ?>">
            <div class="card-header"><h3 class="card-title"><?= $ed ? 'Editar a observação' : 'Nova observação' ?></h3></div>
            <div class="card-body row g-3">
              <div class="col-md-3"><label class="form-label" for="oChave">Chave</label><input class="form-control" name="chave" id="oChave" value="<?= $h($ed['chave'] ?? '') ?>" placeholder="ex.: conselho"><div class="form-hint">"conselho" é a do conselho de classe (sai na série marcada).</div></div>
              <div class="col-md-5"><label class="form-label" for="oTitulo">Título</label><input class="form-control" name="titulo" id="oTitulo" value="<?= $h($ed['titulo'] ?? '') ?>"></div>
              <div class="col-md-2"><label class="form-label" for="oTipo">Tipo</label><select class="form-select" name="tipo" id="oTipo"><option value="padrao">sai sempre</option><option value="selecionavel"<?= ($ed['tipo'] ?? '') === 'selecionavel' ? ' selected' : '' ?>>marcada por série</option></select></div>
              <div class="col-md-1"><label class="form-label" for="oOrdem">Ordem</label><input type="number" class="form-control" name="ordem" id="oOrdem" value="<?= (int) ($ed['ordem'] ?? 0) ?>"></div>
              <div class="col-md-1"><label class="form-check mt-4"><input type="checkbox" class="form-check-input" name="ativo" value="1" id="oAtivo"<?= ($ed['ativo'] ?? 1) ? ' checked' : '' ?>><span class="form-check-label">Ativa</span></label></div>
              <div class="col-12"><label class="form-label" for="oTexto">Texto</label><textarea class="form-control" rows="3" name="texto" id="oTexto"><?= $h($ed['texto'] ?? '') ?></textarea><div class="form-hint">Marcadores: {serie} {ano}.</div></div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary" id="salvarObservacao"><i class="fas fa-save me-1"></i> Salvar</button></div>
          </form>
        <?php endif; ?>

      <?php elseif ($aba === 'logo') : ?>
        <div class="row g-3">
          <?php foreach (B::NIVEIS as $k => $rot) : $l = $d['logos'][$k]; ?>
            <div class="col-md-6"><div class="card" id="logo-<?= $k ?>"><div class="card-header"><h3 class="card-title"><?= $h($rot) ?></h3></div>
              <div class="card-body">
                <?php if ($d['logoUri'][$k]) : ?><img src="<?= $h($d['logoUri'][$k]) ?>" alt="Logo" style="max-height: 80px; max-width: 200px" class="mb-2"><?php endif; ?>
                <p class="text-secondary"><?= $l ? 'Logo próprio deste nível (' . (int) $l['largura'] . '×' . (int) $l['altura'] . ').' : 'Usa o logo da instituição.' ?></p>
                <?php if ($pode['logo']) : ?>
                  <form method="post" enctype="multipart/form-data" action="<?= URLADM ?>historico-escola/logo" class="d-flex gap-2">
                    <input type="hidden" name="nivel" value="<?= $k ?>"><input type="file" class="form-control" name="logo" accept="image/png,image/jpeg" aria-label="Logo do <?= $h($rot) ?>">
                    <button type="submit" class="btn btn-primary" id="enviarLogo-<?= $k ?>">Enviar</button>
                  </form>
                  <?php if ($l) : ?><form method="post" action="<?= URLADM ?>historico-escola/logo" class="mt-2"><input type="hidden" name="nivel" value="<?= $k ?>"><input type="hidden" name="retirar" value="1"><button class="btn btn-outline-secondary btn-sm" id="retirarLogo-<?= $k ?>">Voltar ao logo da instituição</button></form><?php endif; ?>
                <?php endif; ?>
                <div class="form-hint mt-2">PNG ou JPEG, até 1 MB.</div>
              </div></div></div>
          <?php endforeach; ?>
        </div>

      <?php elseif ($aba === 'escolas') : $ed = null; foreach ($d['escolas'] as $e) { if ((int) $e['id'] === (int) ($_GET['editar'] ?? 0)) { $ed = $e; } } ?>
        <div class="card mb-3"><div class="table-responsive"><table class="table table-vcenter card-table" id="tabelaEscolas">
          <thead><tr><th>Escola</th><th>Município</th><th>UF</th><th>Rede</th><th>Ativa</th><th></th></tr></thead><tbody>
          <?php foreach ($d['escolas'] as $e) : ?>
            <tr data-escola="<?= (int) $e['id'] ?>"><td><?= $h($e['nome']) ?><?= $e['esta_escola'] ? ' <span class="badge bg-green-lt">esta escola</span>' : '' ?></td><td><?= $h($e['municipio']) ?></td><td><?= $h($e['uf']) ?></td><td><?= $h($e['rede']) ?></td><td><?= $e['ativo'] ? 'sim' : 'não' ?></td>
              <td><?php if ($pode['escola']) : ?><a class="btn btn-sm btn-outline-secondary" href="<?= $url('escolas', 'EF', '&editar=' . (int) $e['id']) ?>#formEscola">Editar</a><?php endif; ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$d['escolas']) : ?><tr><td colspan="6" class="text-secondary">Nenhuma escola cadastrada. Cadastre "esta escola" primeiro: é a escola das séries cursadas aqui.</td></tr><?php endif; ?>
          </tbody></table></div></div>
        <?php if ($pode['escola']) : ?>
          <form method="post" action="<?= URLADM ?>historico-escola/salvar-escola" class="card" id="formEscola"><input type="hidden" name="id" value="<?= (int) ($ed['id'] ?? 0) ?>">
            <div class="card-header"><h3 class="card-title"><?= $ed ? 'Editar a escola' : 'Nova escola' ?></h3></div>
            <div class="card-body row g-3">
              <div class="col-md-5"><label class="form-label" for="eNome">Nome</label><input class="form-control" name="nome" id="eNome" value="<?= $h($ed['nome'] ?? '') ?>"></div>
              <div class="col-md-3"><label class="form-label" for="eMunicipio">Município</label><input class="form-control" name="municipio" id="eMunicipio" value="<?= $h($ed['municipio'] ?? '') ?>"></div>
              <div class="col-md-1"><label class="form-label" for="eUf">UF</label><input class="form-control" name="uf" id="eUf" maxlength="2" value="<?= $h($ed['uf'] ?? '') ?>"></div>
              <div class="col-md-3"><label class="form-label" for="eRede">Rede</label><input class="form-control" name="rede" id="eRede" value="<?= $h($ed['rede'] ?? '') ?>" placeholder="ex.: particular, municipal"></div>
              <div class="col-md-3"><label class="form-check"><input type="checkbox" class="form-check-input" name="esta_escola" value="1" id="eEsta"<?= !empty($ed['esta_escola']) ? ' checked' : '' ?>><span class="form-check-label">É esta escola</span></label></div>
              <div class="col-md-3"><input type="hidden" name="ativo" value="0"><label class="form-check"><input type="checkbox" class="form-check-input" name="ativo" value="1" id="eAtivo"<?= ($ed['ativo'] ?? 1) ? ' checked' : '' ?>><span class="form-check-label">Ativa</span></label></div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary" id="salvarEscola"><i class="fas fa-save me-1"></i> Salvar</button></div>
          </form>
        <?php endif; ?>

      <?php elseif ($aba === 'disciplinas') : ?>
        <p class="text-secondary">O nome de cada disciplina no histórico e a ordem dela no quadro de cada nível (0 = depois das ordenadas, na ordem da matriz). A folha da matriz continua na ordem da matriz.</p>
        <form method="post" action="<?= URLADM ?>historico-escola/salvar-disciplinas" class="card" id="formDisciplinas">
          <div class="table-responsive"><table class="table table-vcenter card-table" id="tabelaDisciplinas">
            <thead><tr><th>Disciplina (cadastro)</th><th>Nome no histórico</th><th class="text-center">Ensino Religioso</th><th>Ordem no Fundamental</th><th>Ordem no Médio</th></tr></thead><tbody>
            <?php foreach ($d['disciplinas'] as $x) : $k = $h($x['chave']); ?>
              <tr data-disciplina="<?= $k ?>"><td><?= $h($x['materia'] ?: '(criada na vida escolar)') ?></td>
                <td><input class="form-control" name="nome[<?= $k ?>]" value="<?= $h($x['nome']) ?>" aria-label="Nome no histórico"<?= $dis($pode['disciplinas']) ?>></td>
                <td class="text-center"><input type="checkbox" class="form-check-input" name="religioso[<?= $k ?>]" value="1"<?= $x['religioso'] ? ' checked' : '' ?> aria-label="Ensino Religioso"<?= $dis($pode['disciplinas']) ?>></td>
                <td><input type="number" min="0" class="form-control" style="width: 6rem" name="ordem_ef[<?= $k ?>]" value="<?= (int) $x['ordem_ef'] ?>" aria-label="Ordem no Fundamental"<?= $dis($pode['disciplinas']) ?>></td>
                <td><input type="number" min="0" class="form-control" style="width: 6rem" name="ordem_em[<?= $k ?>]" value="<?= (int) $x['ordem_em'] ?>" aria-label="Ordem no Médio"<?= $dis($pode['disciplinas']) ?>></td></tr>
            <?php endforeach; ?>
            <?php if (!$d['disciplinas']) : ?><tr><td colspan="5" class="text-secondary">Nenhuma disciplina: marque antes o nível dos segmentos (aba Segmentos e séries).</td></tr><?php endif; ?>
            </tbody></table></div>
          <?php if ($pode['disciplinas'] && $d['disciplinas']) : ?><div class="card-footer"><button type="submit" class="btn btn-primary" id="salvarDisciplinas"><i class="fas fa-save me-1"></i> Salvar</button></div><?php endif; ?>
        </form>

      <?php elseif ($aba === 'series') : ?>
        <p class="text-secondary">O histórico do Fundamental junta os segmentos marcados como Fundamental (anos iniciais e finais podem ser dois segmentos). O número de cada série é o da coluna dela no quadro (1 a 9 no Fundamental, 1 a 3 no Médio). O que aparece com "proposto" ainda não foi gravado.</p>
        <form method="post" action="<?= URLADM ?>historico-escola/salvar-series" class="card" id="formSeries">
          <div class="table-responsive"><table class="table table-vcenter card-table" id="tabelaSeries">
            <thead><tr><th>Segmento</th><th>Nível no histórico</th><th>Séries e número</th></tr></thead><tbody>
            <?php foreach ($d['segmentos'] as $c) : ?>
              <tr data-segmento="<?= (int) $c['id'] ?>"><td><?= $h($c['nome']) ?></td>
                <td><select class="form-select" name="nivel[<?= (int) $c['id'] ?>]" aria-label="Nível de <?= $h($c['nome']) ?>"<?= $dis($pode['series']) ?>>
                  <option value="">fora do histórico</option><?php foreach (B::NIVEIS as $k => $rot) : ?><option value="<?= $k ?>"<?= $c['nivel'] === $k ? ' selected' : '' ?>><?= $h($rot) ?></option><?php endforeach; ?></select>
                  <?php if (!$c['gravado'] && $c['nivel'] !== '') : ?><span class="badge bg-yellow-lt mt-1">proposto</span><?php endif; ?></td>
                <td><div class="d-flex flex-wrap gap-2"><?php foreach ($c['series'] as $m) : ?>
                  <label class="d-flex align-items-center gap-1" data-serie="<?= (int) $m['id'] ?>"><span class="small"><?= $h($m['codnome']) ?></span>
                    <input type="number" min="0" max="12" class="form-control form-control-sm" style="width: 4.5rem" name="numero[<?= (int) $m['id'] ?>]" value="<?= (int) $m['numero'] ?: '' ?>"<?= $dis($pode['series']) ?>>
                    <?php if (!$m['gravado'] && $m['numero']) : ?><span class="badge bg-yellow-lt">proposto</span><?php endif; ?></label>
                <?php endforeach; ?></div></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
          <?php if ($pode['series']) : ?><div class="card-footer"><button type="submit" class="btn btn-primary" id="salvarSeries"><i class="fas fa-save me-1"></i> Salvar</button></div><?php endif; ?>
        </form>

      <?php else : ?>
        <div class="card"><div class="table-responsive"><table class="table table-vcenter card-table" id="tabelaRegistro">
          <thead><tr><th>Quando</th><th>Quem</th><th>O que mudou</th></tr></thead><tbody>
          <?php foreach ($d['alteracoes'] as $a) : ?>
            <tr><td class="text-nowrap"><?= $h(date('d/m/Y H:i', strtotime($a['quando']))) ?></td><td><?= $h($a['usuario'] ?? '—') ?></td><td><?= $h($a['texto']) ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$d['alteracoes']) : ?><tr><td colspan="3" class="text-secondary">Nada registrado ainda.</td></tr><?php endif; ?>
          </tbody></table></div></div>
      <?php endif; ?>
    </div>
  </section>
</div>
