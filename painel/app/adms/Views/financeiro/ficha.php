<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* A FICHA FINANCEIRA (Onda 8, financeiro refeito, Parte 1; D-99), na disposição da de origem:
   1. o título e as abas do alto (Ficha Financeira, Renegociações, Histórico), com a busca de outro aluno à direita;
   2-3. à esquerda, o aluno (o número, o nome, o ano letivo com uma pílula por ano, o segmento, a série e o contrato) e os
        responsáveis do ano; 4-5. à direita, os três totais e o resumo (a restrição não entra agora: os totais e o resumo
        ocupam a coluna);
   7-10. embaixo, na largura toda, as abas da lista (Atual, Pendências, Anos anteriores), os filtros (o período e a
        situação), o cartão Em lote e a tabela; 11. o menu Ações de cada título; 12. "Novo título", fora do corpo.
   O recorte é do servidor (AdmsFinFicha): cada aba, chip e período é um link com o pedido inteiro. */
use App\adms\Models\financeiro\AdmsFinanceiro as F;
use App\adms\Models\financeiro\AdmsFinFicha as Ficha;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$a = $d['aluno'];
$fi = $d['ficha'];
$R = $fi['recorte'];
$cab = $fi['cabecalho'];
$tot = $fi['totais'];
$alunoId = (int) $a['id'];
$base = URLADM . 'financeiro/ficha/' . $alunoId;
$dt = fn($x) => $x ? date('d/m/Y', strtotime((string) $x)) : '';
$brl = fn($c) => 'R$ ' . F::brl((int) $c);
$envio = fn() => bin2hex(random_bytes(12)); // o número do envio: o clique duplo não grava duas vezes
$escola = \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola();
$termoSeg = \App\adms\Models\helper\AdmsTipoInstituicao::termo('curso', false, true);
$termoSerie = \App\adms\Models\helper\AdmsTipoInstituicao::termo('modulo', false, true);
$pode = (new \App\adms\Models\AdmsBotao())->valBotao([
  'receber' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'receber-pagamento'],
  'recibo' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'recibo'],
  'titulo' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'titulo'],
  'desfazerRec' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'desfazer-recebimento'],
  'renegociar' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'renegociar'],
  'baixar' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'baixar'],
  'cancelar' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'cancelar'],
  'desfazer' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'desfazer'],
  'avulso' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'avulso'],
  'aplicar' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'aplicar-plano'],
  'buscar' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'buscar'],
]);
/* o link com o pedido de agora, mudando só o que se pede (null tira; o padrão não vai na URL) */
$padroes = ['aba' => 'atual', 'campo' => 'venc', 'periodo' => 'todo', 'situacao' => 'todos'];
$url = function (array $mudar = []) use ($R, $base, $padroes) {
  $q = ['ano' => $R['ano'], 'aba' => $R['aba'], 'ano_ant' => $R['aba'] === 'anteriores' ? $R['ano_ant'] : null, 'campo' => $R['campo'], 'periodo' => $R['periodo'],
    'de' => $R['periodo'] === 'personalizado' ? $R['de'] : null, 'ate' => $R['periodo'] === 'personalizado' ? $R['ate'] : null, 'situacao' => $R['modo'] ? null : $R['situacao'], 'modo' => $R['modo']];
  foreach ($mudar as $k => $v) { $q[$k] = $v; }
  foreach ($q as $k => $v) { if ($v === null || $v === '' || (($padroes[$k] ?? null) === $v)) { unset($q[$k]); } }
  return $base . ($q ? '?' . http_build_query($q) : '') . '#fichaLista';
};
$voltarQ = parse_url($url(), PHP_URL_QUERY) ?: ''; // o recorte de agora, para voltar a ele depois de uma ação
$voltarQ = explode('#', $voltarQ)[0];
$corSit = ['avencer' => 'bg-blue-lt', 'atrasados' => 'bg-red text-red-fg', 'recebidos' => 'bg-green-lt', 'baixados' => 'bg-teal-lt', 'cancelados' => 'bg-secondary-lt',
  'renegociados' => 'bg-purple-lt', 'prescritos' => 'bg-orange-lt'];
$corChip = ['todos' => 'secondary', 'avencer' => 'blue', 'atrasados' => 'red', 'abertos' => 'azure', 'recebidos' => 'green', 'baixados' => 'teal', 'cancelados' => 'secondary',
  'prescritos' => 'orange', 'renegociados' => 'purple'];
$rotSit = ['avencer' => 'A vencer', 'atrasados' => 'Atrasado', 'recebidos' => 'Recebido', 'baixados' => 'Baixado', 'cancelados' => 'Cancelado', 'renegociados' => 'Renegociado', 'prescritos' => 'Prescrito'];
$nomeAluno = $a['nome_social'] ?: $a['nome'];
$modo = $R['modo'];
$rotNum = Ficha::rotuloNumero(); // D-99: "RM" na escola, "RA" no ensino superior
$tituloExp = 'Ficha financeira ' . $rotNum . ' ' . $a['rm'] . ' - ' . Ficha::ABAS[$R['aba']] . ($R['aba'] === 'anteriores' && $R['ano_ant'] ? ' ' . $R['ano_ant'] : '')
  . ($R['situacao'] !== 'todos' ? ' - ' . Ficha::SITUACOES[$R['situacao']] : '');
?>
<div class="vc-pagina" id="fichaFinanceira" data-aluno="<?= $h($alunoId) ?>">
  <?php ob_start(); ?>Ficha Financeira<?php $paginaTitulo = ob_get_clean();
  ob_start(); ?>
    <?php if (!empty($pode['buscar'])) : ?>
    <div class="position-relative" style="min-width:320px" id="buscaFicha">
      <div class="input-icon">
        <input type="search" class="form-control" id="buscaFichaCampo" autocomplete="off" spellcheck="false" placeholder="Outro aluno: nome, <?= $h($rotNum) ?><?= $escola ? ' ou responsável' : '' ?>" aria-label="Buscar outro aluno">
        <span class="input-icon-addon"><i class="fas fa-search"></i></span>
      </div>
      <div class="list-group position-absolute w-100 shadow d-none" id="buscaFichaResultado" style="z-index:1050;max-height:320px;overflow-y:auto"></div>
    </div>
    <?php endif; ?>
    <?php if (!empty($pode['avulso']) || !empty($pode['aplicar'])) : ?>
      <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNovoTitulo" id="botaoNovoTitulo"><i class="fas fa-plus me-1"></i> Novo título</button>
    <?php endif; ?>
  <?php $paginaAcoesHtml = ob_get_clean();
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-fluid">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>

      <!-- 1. AS ABAS DO ALTO -->
      <ul class="nav nav-pills mb-3" id="abasDoAlto">
        <li class="nav-item"><a class="nav-link<?= $d['ver'] === 'ficha' ? ' active' : '' ?>" href="<?= $h($base . '?ano=' . $R['ano']) ?>" data-ver="ficha"><i class="fas fa-cash-register me-1"></i> Ficha Financeira</a></li>
        <li class="nav-item"><a class="nav-link<?= $d['ver'] === 'renegociacoes' ? ' active' : '' ?>" href="<?= $h($base . '?ver=renegociacoes&ano=' . $R['ano']) ?>" data-ver="renegociacoes"><i class="fas fa-sync-alt me-1"></i> Renegociações</a></li>
        <li class="nav-item"><a class="nav-link<?= $d['ver'] === 'historico' ? ' active' : '' ?>" href="<?= $h($base . '?ver=historico&ano=' . $R['ano']) ?>" data-ver="historico"><i class="fas fa-phone-volume me-1"></i> Histórico</a></li>
      </ul>

      <div class="card mb-3" id="fichaTopo">
        <div class="card-body">
          <div class="row g-3">
            <!-- 2-3. À ESQUERDA: O ALUNO E OS RESPONSÁVEIS DO ANO -->
            <div class="col-md-8" id="fichaAluno">
              <div class="d-flex align-items-start">
                <div class="flex-fill">
                  <h3 class="mb-1"><strong id="fichaNumeroNome"><?= $h($rotNum) ?>: <?= $h($a['rm']) ?> - <?= $h($nomeAluno) ?></strong> <small class="text-secondary ms-2">• ID: <?= $h($alunoId) ?></small></h3>
                  <div class="text-secondary mb-2" id="fichaAnos">
                    Ano letivo: <strong id="anoEscolhido"><?= $h($R['ano']) ?></strong>
                    <?php if (count($fi['anos']) > 1) : ?>
                      <span class="ms-2">·</span>
                      <?php foreach ($fi['anos'] as $an) :
                        $titA = trim(($an['segmento'] ?? '') . ' ' . ($an['serie'] ?? '')) . ($an['em_aberto'] ? ' · ' . $an['em_aberto'] . ' em aberto' : ''); ?>
                        <?php if ((int) $an['ano'] === (int) $R['ano']) : ?>
                          <span class="badge bg-primary-lt fw-bold vc-pilula-ano ms-1" data-ano="<?= $h($an['ano']) ?>"><?= $h($an['ano']) ?></span>
                        <?php else : ?>
                          <a class="badge bg-secondary-lt vc-pilula-ano ms-1" data-ano="<?= $h($an['ano']) ?>" href="<?= $h($base . '?ano=' . $an['ano'] . ($d['ver'] !== 'ficha' ? '&ver=' . $d['ver'] : '')) ?>" title="<?= $h($titA) ?>"><?= $h($an['ano']) ?></a>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                  <div id="fichaPilulas">
                    <?php if ($cab['segmento']) : ?><span class="badge bg-azure-lt me-1" title="<?= $h($termoSeg) ?>"><?= $h($cab['segmento']) ?></span><?php endif; ?>
                    <?php if ($cab['serie']) : ?><span class="badge bg-azure-lt me-1" title="<?= $h($termoSerie) ?>"><?= $h($cab['serie']) ?></span><?php endif; ?>
                    <?php if ($cab['contrato']) : ?><span class="badge bg-azure-lt me-1" id="fichaContrato">Contrato: <?= $h($cab['contrato']) ?></span><?php endif; ?>
                    <?php if (!$cab['contrato']) : ?><span class="text-secondary small">sem matrícula registrada em <?= $h($R['ano']) ?></span><?php endif; ?>
                  </div>
                </div>
                <a class="btn btn-icon btn-outline-secondary" href="<?= URLADM ?>academicos/visualizar/<?= $h($alunoId) ?>" title="A ficha do aluno" id="voltarAluno"><i class="fas fa-user-gear"></i></a>
              </div>
              <hr class="my-3">
              <div class="mb-1"><strong>Responsáveis</strong> <span class="text-secondary ms-2" id="contagemResponsaveis">(<?= count($fi['responsaveis']) ?> no ano letivo)</span></div>
              <?php if (!$fi['responsaveis']) : ?><div class="text-secondary small" id="semResponsaveis">Nenhum responsável designado em <?= $h($R['ano']) ?>.</div><?php endif; ?>
              <?php foreach ($fi['responsaveis'] as $i => $r) : $RS = \App\adms\Models\escola\AdmsResponsaveis::class; $wa = $RS::whatsapp($r['celular']); $endId = 'enderecoResp' . $i; ?>
                <div class="row g-2 py-2 border-top vc-responsavel" data-financeiro="<?= $r['financeiro'] ? '1' : '0' ?>">
                  <div class="col-sm-4"><div class="small text-secondary">Nome / CPF</div><div><?= $h($r['nome']) ?><?php if ($r['cpf']) : ?><span class="text-secondary small ms-2 vc-cpf"><?= $h($RS::cpfFormatado($r['cpf'])) ?></span><?php endif; ?></div></div>
                  <div class="col-sm-2"><div class="small text-secondary">Responsável</div><div class="vc-papel"><?= $h($r['papel']) ?></div></div>
                  <div class="col-sm-2"><div class="small text-secondary">Parentesco</div><div><?= $h($r['parentesco']) ?></div></div>
                  <div class="col-sm-4"><div class="small text-secondary">Celular</div>
                    <div class="d-flex align-items-center gap-1"><span class="me-auto text-nowrap"><?= $h($RS::celularFormatado($r['celular']) ?: '—') ?></span>
                      <?php if ($wa) : ?><a class="btn btn-sm btn-icon btn-success vc-whatsapp" target="_blank" rel="noopener" href="<?= $h($wa) ?>" title="Abrir no WhatsApp"><i class="fab fa-whatsapp"></i></a><?php endif; ?>
                      <a class="btn btn-sm btn-icon btn-outline-secondary" href="<?= URLADM . $h($r['link']) ?>" title="Abrir o cadastro"><i class="fas fa-pen"></i></a>
                      <?php if ($r['endereco']) : ?><a class="btn btn-sm btn-link px-1 vc-ver-endereco" data-bs-toggle="collapse" href="#<?= $h($endId) ?>" role="button" aria-expanded="false" aria-controls="<?= $h($endId) ?>">Ver endereço <i class="fas fa-chevron-down"></i></a><?php endif; ?>
                    </div></div>
                  <?php if ($r['endereco']) : ?><div class="col-12 collapse" id="<?= $h($endId) ?>"><div class="bg-light rounded p-2 small"><span class="text-secondary">Endereço:</span> <span class="vc-endereco"><?= $h($r['endereco']) ?></span></div></div><?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- 4-5. À DIREITA: OS TRÊS TOTAIS E O RESUMO -->
            <div class="col-md-4" id="fichaResumo">
              <div class="row g-2 mb-2">
                <div class="col-4"><div class="card card-sm" id="totalAtraso"><div class="card-body p-2">
                  <div class="small text-secondary">Em atraso</div>
                  <div class="fw-bold text-nowrap <?= $tot['atraso'] > 0 ? 'text-danger' : 'text-secondary' ?> vc-total"><?= $h($brl($tot['atraso'])) ?></div>
                  <div class="small text-secondary text-truncate"><?= $h($tot['atraso_n']) ?> títulos</div></div></div></div>
                <div class="col-4"><div class="card card-sm" id="totalAVencer"><div class="card-body p-2">
                  <div class="small text-secondary">A vencer</div>
                  <div class="fw-bold text-nowrap <?= $tot['avencer'] > 0 ? '' : 'text-secondary' ?> vc-total"><?= $h($brl($tot['avencer'])) ?></div>
                  <div class="small text-secondary text-truncate" title="<?= $h($tot['avencer_n'] . ($tot['proximo'] ? ' · próximo ' . $dt($tot['proximo']) : ' títulos')) ?>"><?= $h($tot['avencer_n'] . ($tot['proximo'] ? ' · próximo ' . $dt($tot['proximo']) : ' títulos')) ?></div></div></div></div>
                <div class="col-4"><div class="card card-sm" id="totalPago"><div class="card-body p-2">
                  <div class="small text-secondary">Pago em <?= $h($R['ano']) ?></div>
                  <div class="fw-bold text-nowrap <?= $tot['pago'] > 0 ? 'text-success' : 'text-secondary' ?> vc-total"><?= $h($brl($tot['pago'])) ?></div>
                  <div class="small text-secondary text-truncate"><?= $h($tot['pago_n']) ?> títulos</div></div></div></div>
              </div>
              <div class="card card-sm" id="resumoFinanceiro"><div class="card-body p-2">
                <div><strong>Resumo</strong> <span class="text-secondary small">Financeiro</span></div>
                <div class="d-flex flex-wrap gap-1 mt-2">
                  <?php /* a bolsa: o selo entra com as bolsas (Parte 5); até lá, oculto e sem buraco */ ?>
                  <?php if ($fi['resumo']['inadimplente']) : ?>
                    <span class="badge bg-red text-red-fg" id="seloSituacao">Inadimplente</span>
                  <?php else : ?>
                    <span class="badge bg-green text-green-fg" id="seloSituacao">Adimplente</span>
                  <?php endif; ?>
                  <?php $ult = $fi['resumo']['ultimo']; ?>
                  <span class="badge bg-secondary-lt text-nowrap" id="ultimoPagamento">Último pagamento: <?= $ult ? $h($brl($ult['total']) . ' em ' . $dt($ult['data_recebimento'])) : '—' ?></span>
                </div>
              </div></div>
            </div>
          </div>
        </div>
      </div>

      <?php if ($d['ver'] !== 'ficha') : ?>
        <?php include __DIR__ . '/_ficha_mais.php'; // as abas Renegociações e Histórico: o que a 1.92.0 já tem ?>
      <?php else : ?>

      <!-- 7-10. A LISTA: AS ABAS, OS FILTROS, O EM LOTE E A TABELA -->
      <div class="card" id="fichaLista">
        <div class="card-header">
          <ul class="nav nav-tabs card-header-tabs" id="abasLista">
            <li class="nav-item"><a class="nav-link<?= $R['aba'] === 'atual' && !$modo ? ' active' : '' ?>" href="<?= $h($url(['aba' => 'atual', 'modo' => null, 'ano_ant' => null])) ?>" data-aba="atual">Atual <span class="badge bg-secondary-lt ms-1 vc-contagem-aba"><?= $h($fi['abas']['atual']) ?></span></a></li>
            <li class="nav-item"><a class="nav-link<?= $R['aba'] === 'pendencias' && !$modo ? ' active' : '' ?>" href="<?= $h($url(['aba' => 'pendencias', 'modo' => null, 'ano_ant' => null])) ?>" data-aba="pendencias">Pendências <span class="badge bg-red text-red-fg ms-1 vc-contagem-aba"><?= $h($fi['abas']['pendencias']) ?></span></a></li>
            <li class="nav-item"><a class="nav-link<?= $R['aba'] === 'anteriores' && !$modo ? ' active' : '' ?>" href="<?= $h($url(['aba' => 'anteriores', 'modo' => null])) ?>" data-aba="anteriores">Anos anteriores</a></li>
          </ul>
        </div>
        <div class="card-body">
          <?php if ($R['aba'] === 'anteriores' && !$modo) : ?>
            <div class="d-flex align-items-center flex-wrap gap-2 mb-3" id="seletorAnoAnterior">
              <?php if ($R['anteriores']) : ?>
                <form method="get" action="<?= $h($base) ?>" class="d-flex align-items-center gap-2">
                  <input type="hidden" name="ano" value="<?= $h($R['ano']) ?>"><input type="hidden" name="aba" value="anteriores">
                  <label class="fw-bold" for="anoAnterior">Ano</label>
                  <select class="form-select form-select-sm w-auto" id="anoAnterior" name="ano_ant" onchange="this.form.submit()">
                    <?php foreach ($R['anteriores'] as $an) : ?><option value="<?= $h($an) ?>"<?= $an === $R['ano_ant'] ? ' selected' : '' ?>><?= $h($an) ?></option><?php endforeach; ?>
                  </select>
                </form>
                <small class="text-secondary">Um ano de cada vez.</small>
              <?php else : ?>
                <span class="text-secondary">Este aluno não tem títulos de anos anteriores a <?= $h($R['ano']) ?>.</span>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <div class="row g-2">
            <?php if ($modo) : ?>
              <!-- A FAIXA DO MODO LOTE (no lugar do cartão Em lote) -->
              <div class="col-12">
                <div class="alert alert-info d-flex flex-wrap align-items-center gap-2 mb-0 py-2" id="faixaLote" data-modo="<?= $h($modo) ?>">
                  <div class="me-2"><strong><?= $h(Ficha::MODOS[$modo][0]) ?></strong> — selecione os títulos</div>
                  <button type="button" class="btn btn-sm" id="selecionarTodos">Selecionar todos</button>
                  <button type="button" class="btn btn-sm d-none" id="limparSelecao">Limpar seleção</button>
                  <span class="small fw-bold" id="resumoSelecao">0 selecionado(s) · R$ 0,00</span>
                  <button type="button" class="btn btn-sm btn-primary" id="continuarLote" disabled>Continuar <i class="fas fa-arrow-right ms-1"></i></button>
                  <a href="<?= $h($url(['modo' => null])) ?>" class="btn btn-sm btn-outline-secondary ms-auto" id="sairLote"><i class="fas fa-times me-1"></i> Sair do modo lote</a>
                  <div class="w-100 small text-danger fw-bold d-none" id="avisoAnos">Os títulos selecionados são de anos diferentes. Receba (ou renegocie) os de cada ano em uma operação separada.</div>
                </div>
              </div>
            <?php endif; ?>
            <div class="<?= $modo ? 'col-12' : 'col-lg-7' ?>" id="filtrosFicha">
              <!-- 8. O PERÍODO -->
              <div class="d-flex align-items-center flex-wrap gap-2 mb-2" id="filtroPeriodo">
                <span class="fw-bold" style="width:70px">Período</span>
                <div class="btn-group btn-group-sm" role="group" aria-label="A data do filtro de período">
                  <a href="<?= $h($url(['campo' => 'venc'])) ?>" class="btn <?= $R['campo'] === 'venc' ? 'btn-primary' : 'btn-outline-primary' ?>" data-campo="venc">Vencimento</a>
                  <a href="<?= $h($url(['campo' => 'receb'])) ?>" class="btn <?= $R['campo'] === 'receb' ? 'btn-primary' : 'btn-outline-primary' ?>" data-campo="receb">Recebimento</a>
                </div>
                <form method="get" action="<?= $h($base) ?>#fichaLista" class="d-flex" id="formPeriodo">
                  <?php foreach (['ano' => $R['ano'], 'aba' => $R['aba'] !== 'atual' ? $R['aba'] : null, 'ano_ant' => $R['aba'] === 'anteriores' ? $R['ano_ant'] : null, 'campo' => $R['campo'] !== 'venc' ? $R['campo'] : null,
                    'situacao' => !$modo && $R['situacao'] !== 'todos' ? $R['situacao'] : null, 'modo' => $modo] as $k => $v) : if ($v === null) { continue; } ?>
                    <input type="hidden" name="<?= $h($k) ?>" value="<?= $h($v) ?>">
                  <?php endforeach; ?>
                  <select class="form-select form-select-sm" name="periodo" id="selectPeriodo" onchange="this.form.submit()" aria-label="Período">
                    <?php foreach (Ficha::PERIODOS as $k => [$rot, $vale]) : if ($k === 'personalizado' || ($vale === 'venc' && $R['campo'] === 'receb')) { continue; } ?>
                      <option value="<?= $h($k) ?>"<?= $R['periodo'] === $k ? ' selected' : '' ?>><?= $h($rot) ?></option>
                    <?php endforeach; ?>
                    <?php if ($R['periodo'] === 'personalizado') : ?><option value="personalizado" selected hidden>Personalizado</option><?php endif; ?>
                  </select>
                </form>
                <a href="<?= $h($url(['periodo' => 'personalizado'])) ?>" class="btn btn-sm <?= $R['periodo'] === 'personalizado' ? 'btn-primary' : 'btn-outline-primary' ?>" id="botaoPersonalizado">Personalizado…</a>
              </div>
              <?php if ($R['periodo'] === 'personalizado') : ?>
                <form method="get" action="<?= $h($base) ?>#fichaLista" class="d-flex align-items-center flex-wrap gap-2 mb-2" style="padding-left:78px" id="formPersonalizado">
                  <?php foreach (['ano' => $R['ano'], 'aba' => $R['aba'] !== 'atual' ? $R['aba'] : null, 'ano_ant' => $R['aba'] === 'anteriores' ? $R['ano_ant'] : null, 'campo' => $R['campo'] !== 'venc' ? $R['campo'] : null,
                    'situacao' => !$modo && $R['situacao'] !== 'todos' ? $R['situacao'] : null, 'modo' => $modo] as $k => $v) : if ($v === null) { continue; } ?>
                    <input type="hidden" name="<?= $h($k) ?>" value="<?= $h($v) ?>">
                  <?php endforeach; ?>
                  <input type="hidden" name="periodo" value="personalizado">
                  <label for="periodoDe">De</label><input type="date" class="form-control form-control-sm w-auto" id="periodoDe" name="de" value="<?= $h($R['de']) ?>" required>
                  <label for="periodoAte">até</label><input type="date" class="form-control form-control-sm w-auto" id="periodoAte" name="ate" value="<?= $h($R['ate']) ?>" required>
                  <button type="submit" class="btn btn-sm btn-primary">Aplicar</button>
                </form>
              <?php endif; ?>
              <!-- 8. A SITUAÇÃO, COM A CONTAGEM -->
              <div class="d-flex align-items-center flex-wrap gap-1" id="filtroSituacao">
                <span class="fw-bold" style="width:70px">Situação</span>
                <?php foreach (Ficha::SITUACOES as $k => $rot) : $ativo = $R['situacao'] === $k; $n = (int) $fi['contagem'][$k];
                  $cls = 'badge ' . ($ativo ? 'bg-' . $corChip[$k] . ' text-' . $corChip[$k] . '-fg' : 'bg-' . $corChip[$k] . '-lt'); ?>
                  <?php if ($modo) : ?>
                    <span class="<?= $h($cls) ?> vc-chip" data-situacao="<?= $h($k) ?>" style="<?= $ativo ? '' : 'opacity:.5' ?>"><?= $h($rot) ?> <span class="vc-contagem"><?= $h($n) ?></span></span>
                  <?php else : ?>
                    <a class="<?= $h($cls) ?> vc-chip text-decoration-none" data-situacao="<?= $h($k) ?>" href="<?= $h($url(['situacao' => $k])) ?>"><?= $h($rot) ?> <span class="vc-contagem"><?= $h($n) ?></span></a>
                  <?php endif; ?>
                <?php endforeach; ?>
                <?php if ($modo) : ?><small class="text-secondary ms-1">fixo no modo lote</small><?php endif; ?>
              </div>
            </div>

            <?php if (!$modo) : ?>
            <!-- 9. O CARTÃO EM LOTE -->
            <div class="col-lg-5">
              <div class="card card-sm" id="cartaoLote"><div class="card-body py-2">
                <div class="fw-bold mb-1">Em lote</div>
                <div class="d-flex flex-wrap gap-3">
                  <div class="vc-lote-grupo">
                    <small class="text-secondary d-block">Financeiro</small>
                    <div class="btn-group">
                      <?php if (!empty($pode['receber'])) : ?><a href="<?= $h($url(['modo' => 'receber'])) ?>" class="btn btn-sm btn-primary" data-modo="receber"><i class="fas fa-layer-group me-1"></i> Receber</a><?php endif; ?>
                      <?php if (!empty($pode['renegociar'])) : ?><a href="<?= $h($url(['modo' => 'renegociar'])) ?>" class="btn btn-sm btn-outline-primary" data-modo="renegociar"><i class="fas fa-sync-alt me-1"></i> Renegociar</a><?php endif; ?>
                    </div>
                  </div>
                  <div class="vc-lote-grupo border-start ps-3">
                    <small class="text-secondary d-block">Administrativo</small>
                    <div class="btn-group">
                      <?php if (!empty($pode['baixar'])) : ?><a href="<?= $h($url(['modo' => 'baixar'])) ?>" class="btn btn-sm btn-outline-secondary" data-modo="baixar"><i class="fas fa-download me-1"></i> Baixar</a><?php endif; ?>
                      <?php if (!empty($pode['cancelar'])) : ?><a href="<?= $h($url(['modo' => 'cancelar'])) ?>" class="btn btn-sm btn-outline-secondary" data-modo="cancelar"><i class="fas fa-times me-1"></i> Cancelar</a><?php endif; ?>
                      <?php /* Prescrever entra na Parte 3: até lá, o botão não aparece */ ?>
                    </div>
                  </div>
                </div>
              </div></div>
            </div>
            <?php endif; ?>
          </div>

          <!-- 10. A TABELA -->
          <div class="mt-3">
            <?php if (!$fi['titulos']) : ?>
              <div class="text-center py-4" id="semTitulos">
                <p class="h4 mb-2"><?= $modo ? 'Nenhum título elegível para ' . $h(mb_strtolower(Ficha::MODOS[$modo][0])) : ($R['situacao'] !== 'todos' || $R['periodo'] !== 'todo' ? 'Nenhum título com estes filtros' : 'Nenhum título nesta aba') ?></p>
                <?php if (!$modo && ($R['situacao'] !== 'todos' || $R['periodo'] !== 'todo')) : ?><a href="<?= $h($url(['situacao' => 'todos', 'periodo' => 'todo', 'campo' => 'venc'])) ?>" class="btn btn-sm btn-outline-primary">Limpar filtros</a><?php endif; ?>
              </div>
            <?php else : ?>
            <div class="table-responsive">
              <table class="table table-sm table-striped table-bordered table-hover lista-dados vc-tabela-ficha" id="tabelaTitulos" data-titulo="<?= $h($tituloExp) ?>" data-linhas="25">
                <thead>
                  <tr>
                    <?php if ($modo) : ?><th class="text-center nao-exportar nao-ordenar" style="width:38px"><input type="checkbox" class="form-check-input" id="marcarTodos" aria-label="Selecionar todos"></th><?php endif; ?>
                    <th class="text-center">Ano</th><th>Título</th><th>Descrição</th><th class="text-center">Parc.</th><th class="num">Valor</th><th class="num">Encargos hoje</th>
                    <th class="num">Valor Bolsa</th><th class="num">Valor até o desconto</th><th class="num">Valor Pago</th><th class="text-center">Data Venc.</th>
                    <th class="text-center">Data do desconto</th><th class="text-center">Data Receb</th><th class="text-center">Usuário</th><th class="text-center">Situação</th>
                    <?php if (!$modo) : ?><th class="text-center nao-exportar nao-ordenar">Ações</th><?php endif; ?>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($fi['titulos'] as $t) :
                  $anoT = (int) $t['ano'];
                  $seloAno = $anoT < $R['ano'] ? 'bg-yellow-lt' : ($anoT > $R['ano'] ? 'bg-blue-lt' : 'bg-secondary-lt');
                  $seloTit = $anoT < $R['ano'] ? 'Ano anterior' : ($anoT > $R['ano'] ? 'Ano seguinte' : 'Ano escolhido');
                  $atrasoAnterior = $t['chave'] === 'atrasados' && $anoT < $R['ano'];
                  $e = $t['encargos'];
                  $devidoHoje = $e ? $e['total'] : (int) $t['valor_centavos'];
                  $tipoD = ['baixado' => 'desfazer_baixa', 'cancelado' => 'desfazer_cancelamento'][$t['situacao']] ?? null; ?>
                  <tr class="<?= $atrasoAnterior ? 'vc-atraso-anterior' : '' ?>" data-titulo="<?= $h($t['id']) ?>" data-situacao="<?= $h($t['mostrada']) ?>" data-chave="<?= $h($t['chave']) ?>" data-ano="<?= $h($anoT) ?>" data-valor="<?= $h($devidoHoje) ?>">
                    <?php if ($modo) : ?><td class="text-center"><input type="checkbox" class="form-check-input vc-marca" value="<?= $h($t['id']) ?>" data-ano="<?= $h($anoT) ?>" data-valor="<?= $h($devidoHoje) ?>" aria-label="Selecionar o título <?= $h($t['id']) ?>"></td><?php endif; ?>
                    <td class="text-center" data-order="<?= $h($anoT) ?>"><span class="badge <?= $h($seloAno) ?>" title="<?= $h($seloTit) ?>"><?= $h($anoT) ?></span></td>
                    <td class="vc-numero-titulo"><?= $h($t['id']) ?></td>
                    <td class="vc-descricao"><?= $h($t['descricao']) ?></td>
                    <td class="text-center"><?= $h($t['parc']) ?></td>
                    <td class="num text-nowrap" data-order="<?= $h($t['valor_centavos']) ?>"><?= $h($brl($t['valor_centavos'])) ?></td>
                    <td class="num text-nowrap"><?= $e && ($e['multa'] || $e['juros']) ? '+' . $h(F::brl($e['multa'] + $e['juros'])) . '<br><span class="text-secondary small">' . $h($brl($e['total'])) . '</span>' : '<span class="text-secondary">—</span>' ?></td>
                    <td class="num text-nowrap vc-col-bolsa"><span class="text-secondary">—</span></td>
                    <td class="num text-nowrap vc-col-desconto"><?= $t['valor_desconto'] !== null ? $h($brl($t['valor_desconto'])) : '<span class="text-secondary">—</span>' ?></td>
                    <td class="num text-nowrap vc-col-pago" data-order="<?= $h((int) $t['recebido_centavos']) ?>"><?= $t['recebido_centavos'] !== null ? $h($brl($t['recebido_centavos'])) : '<span class="text-secondary">—</span>' ?></td>
                    <td class="text-center text-nowrap" data-order="<?= $h($t['vencimento']) ?>"><?= $h($dt($t['vencimento'])) ?></td>
                    <td class="text-center text-nowrap"><?= $t['data_desconto'] ? $h($dt($t['data_desconto'])) : '<span class="text-secondary">—</span>' ?></td>
                    <td class="text-center text-nowrap vc-col-data-receb" data-order="<?= $h((string) $t['data_recebimento']) ?>"><?= $t['data_recebimento'] ? $h($dt($t['data_recebimento'])) : '' ?></td>
                    <td class="vc-col-usuario"><?= $h($t['recebedor'] ?? '') ?></td>
                    <td class="text-center"><span class="badge <?= $h($corSit[$t['chave']] ?? 'bg-secondary-lt') ?> vc-situacao-titulo"><?= $h($rotSit[$t['chave']] ?? $t['situacao']) ?></span></td>
                    <?php if (!$modo) : ?>
                    <td class="text-center">
                      <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle vc-acoes" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">Ações</button>
                        <div class="dropdown-menu dropdown-menu-end">
                          <?php $algum = false; ?>
                          <?php if ($t['situacao'] === 'aberto' && !empty($pode['receber'])) : $algum = true; ?>
                            <a class="dropdown-item vc-item-receber" href="<?= URLADM ?>financeiro/receber-pagamento/<?= $h($alunoId) ?>?titulos=<?= $h($t['id']) ?>"><i class="fas fa-cash-register me-2"></i> Receber</a>
                          <?php endif; ?>
                          <?php if (!empty($pode['titulo'])) : $algum = true; ?>
                            <a class="dropdown-item vc-item-visualizar" href="<?= URLADM ?>financeiro/titulo/<?= $h($t['id']) ?>"><i class="far fa-eye me-2"></i> Visualizar</a>
                          <?php endif; ?>
                          <?php if ($t['situacao'] === 'recebido' && $t['recibo_id'] && !empty($pode['recibo'])) : $algum = true; ?>
                            <a class="dropdown-item vc-item-recibo" target="_blank" href="<?= URLADM ?>financeiro/recibo/<?= $h($t['recibo_id']) ?>"><i class="fas fa-receipt me-2"></i> Recibo</a>
                          <?php endif; ?>
                          <?php if ($t['situacao'] === 'recebido' && $t['recibo_id'] && !empty($pode['desfazerRec'])) : $algum = true; ?>
                            <a class="dropdown-item vc-item-desfazer-recebimento" href="<?= URLADM ?>financeiro/desfazer-recebimento/<?= $h($t['recibo_id']) ?>"><i class="fas fa-undo-alt me-2"></i> Desfazer recebimento</a>
                          <?php endif; ?>
                          <?php foreach (['baixar' => ['Baixar', 'fa-download', 'Baixar (sem entrada de dinheiro)'], 'cancelar' => ['Cancelar', 'fa-times', 'Cancelar (o título deixa de ser devido)']] as $ac => [$rotA, $ico, $leg]) :
                            if ($t['situacao'] !== 'aberto' || empty($pode[$ac])) { continue; } $algum = true; ?>
                            <button type="button" class="dropdown-item vc-item-<?= $h($ac) ?>" data-vc-acao="<?= $h($ac) ?>" data-vc-url="<?= URLADM ?>financeiro/<?= $h($ac) ?>/<?= $h($t['id']) ?>"
                              data-vc-titulo="<?= $h($rotA . ' o título ' . $t['id']) ?>" data-vc-legenda="<?= $h($leg) ?>"><i class="fas <?= $h($ico) ?> me-2"></i> <?= $h($rotA) ?></button>
                          <?php endforeach; ?>
                          <?php if ($tipoD && !empty($pode['desfazer'])) : $algum = true; $nomeD = \App\adms\Models\financeiro\AdmsFinOperacoes::NOMES[$tipoD]; ?>
                            <button type="button" class="dropdown-item vc-item-<?= $h(str_replace('_', '-', $tipoD)) ?>" data-vc-acao="desfazer" data-vc-tipo="<?= $h($tipoD) ?>" data-vc-url="<?= URLADM ?>financeiro/desfazer/<?= $h($t['id']) ?>"
                              data-vc-titulo="<?= $h($nomeD . ' do título ' . $t['id']) ?>" data-vc-legenda="<?= $h($nomeD . ': o título volta a aberto') ?>"><i class="fas fa-undo me-2"></i> <?= $h($nomeD) ?></button>
                          <?php endif; ?>
                          <?php if (!$algum) : ?><span class="dropdown-item-text text-secondary">Nenhuma ação disponível</span><?php endif; ?>
                        </div>
                      </div>
                    </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </section>
</div>

<!-- A AÇÃO DE UM TÍTULO (Baixar, Cancelar e os desfazer, como na 1.92.0): o motivo -->
<div class="modal modal-blur fade" id="modalAcaoTitulo" tabindex="-1" aria-labelledby="modalAcaoTituloNome" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form method="post" class="modal-content" id="formAcaoTitulo" data-vc-confirma="Confirmar">
      <div class="modal-header"><h5 class="modal-title" id="modalAcaoTituloNome">Ação</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
      <div class="modal-body">
        <input type="hidden" name="envio" value="<?= $h($envio()) ?>"><input type="hidden" name="tipo" value=""><input type="hidden" name="voltar" value="<?= $h($voltarQ) ?>">
        <label class="form-label" for="motivoAcao"><span id="modalAcaoLegenda"></span>: o motivo</label>
        <input type="text" class="form-control" id="motivoAcao" name="motivo" minlength="5" maxlength="500" required>
      </div>
      <div class="modal-footer"><button type="button" class="btn" data-bs-dismiss="modal">Voltar</button><button type="submit" class="btn btn-primary" id="gravarAcaoTitulo">Gravar</button></div>
    </form>
  </div>
</div>

<?php if ($modo && !in_array($modo, ['receber', 'renegociar'], true)) : ?>
<!-- CONTINUAR NO MODO LOTE: Baixar e Cancelar (até a Parte 3; o Receber e o Renegociar têm a tela própria) -->
<div class="modal modal-blur fade" id="modalLote" tabindex="-1" aria-labelledby="modalLoteNome" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form method="post" class="modal-content" id="formLote" data-vc-confirma="<?= $h(Ficha::MODOS[$modo][0]) ?>"
      action="<?= $h(URLADM . 'financeiro/' . $modo . '/0') ?>">
      <div class="modal-header"><h5 class="modal-title" id="modalLoteNome"><?= $h(Ficha::MODOS[$modo][0]) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
      <div class="modal-body">
        <input type="hidden" name="envio" value="<?= $h($envio()) ?>"><input type="hidden" name="aluno" value="<?= $h($alunoId) ?>"><input type="hidden" name="voltar" value="<?= $h(explode('#', parse_url($url(['modo' => null]), PHP_URL_QUERY) ?: '')[0]) ?>">
        <div id="loteTitulos"></div>
        <p class="mb-2" id="loteResumo"></p>
        <label class="form-label" for="loteMotivo">O motivo</label>
        <input type="text" class="form-control" id="loteMotivo" name="motivo" minlength="5" maxlength="500" required>
      </div>
      <div class="modal-footer"><button type="button" class="btn" data-bs-dismiss="modal">Voltar</button><button type="submit" class="btn btn-primary" id="gravarLote"><?= $h(Ficha::MODOS[$modo][0]) ?></button></div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- 12. NOVO TÍTULO (fora do corpo da ficha): o título avulso e o plano para uma matrícula -->
<div class="modal modal-blur fade" id="modalNovoTitulo" tabindex="-1" aria-labelledby="modalNovoTituloNome" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="modalNovoTituloNome">Novo título</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <?php if (!empty($pode['avulso'])) : ?>
          <div class="col-lg-6">
            <form method="post" action="<?= URLADM ?>financeiro/avulso/<?= $h($alunoId) ?>" class="card" id="formAvulso">
              <input type="hidden" name="voltar" value="<?= $h($voltarQ) ?>">
              <div class="card-header"><h3 class="card-title">Título avulso</h3></div>
              <div class="card-body row g-2">
                <div class="col-12"><label class="form-label" for="avDesc">Descrição</label><input type="text" class="form-control" id="avDesc" name="descricao" maxlength="160" placeholder="Ex.: Material didático" required></div>
                <div class="col-6"><label class="form-label" for="avValor">Valor (R$)</label><input type="text" inputmode="decimal" class="form-control" id="avValor" name="valor" placeholder="120,00" required></div>
                <div class="col-6"><label class="form-label" for="avVenc">Vencimento</label><input type="date" class="form-control" id="avVenc" name="vencimento" required></div>
                <div class="col-6"><label class="form-label" for="avAno">Ano letivo</label><select class="form-select" id="avAno" name="ano">
                  <?php $anosAv = array_unique(array_merge(array_keys($fi['anos']), [(int) date('Y'), (int) date('Y') + 1])); sort($anosAv);
                  foreach ($anosAv as $an) : ?><option value="<?= $h($an) ?>"<?= (int) $an === (int) $R['ano'] ? ' selected' : '' ?>><?= $h($an) ?></option><?php endforeach; ?></select></div>
                <?php if ($d['matriculas']) : ?><div class="col-6"><label class="form-label" for="avMat">Matrícula <small class="text-muted">(opcional)</small></label><select class="form-select" id="avMat" name="matricula_curso_id"><option value="">—</option>
                  <?php foreach ($d['matriculas'] as $m) : ?><option value="<?= $h($m['id']) ?>"><?= $h($m['curso'] . ' · ' . $m['situacao']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
              </div>
              <div class="card-footer"><button type="submit" class="btn btn-primary" id="lancarAvulso">Lançar o título</button></div>
            </form>
          </div>
          <?php endif; ?>
          <?php if (!empty($pode['aplicar']) && $d['matriculas'] && $d['planos']) : ?>
          <div class="col-lg-6">
            <form method="post" action="<?= URLADM ?>financeiro/aplicar-plano/<?= $h($alunoId) ?>" class="card" id="formAplicarPlano">
              <input type="hidden" name="voltar" value="<?= $h($voltarQ) ?>">
              <div class="card-header"><h3 class="card-title">Aplicar um plano de pagamento</h3></div>
              <div class="card-body row g-2">
                <div class="col-12"><label class="form-label" for="apMat">Matrícula</label><select class="form-select" id="apMat" name="matricula_curso_id">
                  <?php foreach ($d['matriculas'] as $m) : ?><option value="<?= $h($m['id']) ?>"><?= $h($m['curso'] . ' · ' . $m['situacao']) ?></option><?php endforeach; ?></select></div>
                <div class="col-12"><label class="form-label" for="apPlano">Plano</label><select class="form-select" id="apPlano" name="plano_id">
                  <?php foreach ($d['planos'] as $p) : ?><option value="<?= $h($p['id']) ?>"><?= $h($p['nome'] . ' · ' . $p['parcelas'] . ' × ' . $brl($p['valor_centavos'])) ?></option><?php endforeach; ?></select></div>
                <small class="form-hint">As parcelas do plano, no ano da matrícula; a parcela que já está na ficha não é gerada de novo.</small>
              </div>
              <div class="card-footer"><button type="submit" class="btn btn-outline-primary" id="aplicarPlano">Gerar as parcelas</button></div>
            </form>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .vc-tabela-ficha td.text-center, .vc-tabela-ficha td.num { white-space: nowrap; }
  .vc-tabela-ficha thead th { white-space: normal; vertical-align: bottom; line-height: 1.15; }
  .vc-tabela-ficha td, .vc-tabela-ficha th { padding: .25rem .28rem; font-size: .76rem; }
  .vc-tabela-ficha .vc-acoes { padding: .1rem .45rem; font-size: .75rem; }
  .vc-tabela-ficha td.vc-descricao { min-width: 140px; }
  .vc-tabela-ficha td.vc-col-usuario { min-width: 70px; max-width: 92px; line-height: 1.15; }
  #fichaResumo .text-truncate { white-space: normal; }
  .vc-tabela-ficha tr.vc-atraso-anterior > td { background-color: #fff4e0 !important; }
  #filtroSituacao .badge { font-size: 85%; padding: .45em .8em; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var brl = function (c) { return 'R$ ' + (c / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };

  /* a busca de outro aluno */
  var campo = document.getElementById('buscaFichaCampo');
  if (campo) {
    var res = document.getElementById('buscaFichaResultado');
    var espera = null;
    campo.addEventListener('input', function () {
      clearTimeout(espera);
      var q = campo.value.trim();
      if (q.length < 2) { res.classList.add('d-none'); return; }
      espera = setTimeout(function () {
        fetch('<?= URLADM ?>financeiro/buscar?q=' + encodeURIComponent(q), { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
          if (campo.value.trim() !== q) { return; }
          res.innerHTML = '';
          (j.alunos || []).forEach(function (a) {
            var it = document.createElement('a');
            it.className = 'list-group-item list-group-item-action vc-busca-item';
            it.href = '<?= URLADM ?>financeiro/ficha/' + a.id;
            it.textContent = '<?= $h($rotNum) ?> ' + a.rm + ' — ' + a.nome + (a.por ? ' (' + a.por + ')' : '');
            res.appendChild(it);
          });
          if (!res.children.length) { var v = document.createElement('div'); v.className = 'list-group-item text-secondary'; v.textContent = 'Ninguém encontrado.'; res.appendChild(v); }
          res.classList.remove('d-none');
        });
      }, 250);
    });
    document.addEventListener('click', function (ev) { if (!ev.target.closest('#buscaFicha')) { res.classList.add('d-none'); } });
  }

  /* o menu Ações: Baixar, Cancelar e os desfazer abrem a janela do motivo */
  var modalAcao = document.getElementById('modalAcaoTitulo');
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-vc-acao]');
    if (!b) { return; }
    var f = document.getElementById('formAcaoTitulo');
    f.action = b.dataset.vcUrl;
    f.dataset.vcConfirma = b.dataset.vcTitulo;
    f.querySelector('[name="tipo"]').value = b.dataset.vcTipo || '';
    f.querySelector('[name="motivo"]').value = '';
    document.getElementById('modalAcaoTituloNome').textContent = b.dataset.vcTitulo;
    document.getElementById('modalAcaoLegenda').textContent = b.dataset.vcLegenda;
    window.vcAbrirModal('modalAcaoTitulo');
  });
  document.getElementById('formAcaoTitulo').addEventListener('submit', function () {
    this.dataset.vcConfirmaExtra = 'O motivo: ' + this.querySelector('[name="motivo"]').value.replace(/</g, '&lt;');
  }, true);

  /* o modo lote: a seleção vale para a tabela inteira (todas as páginas da lista) */
  var faixa = document.getElementById('faixaLote');
  if (!faixa) { return; }
  var modo = faixa.dataset.modo;
  var tabela = window.jQuery && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable('#tabelaTitulos') ? jQuery('#tabelaTitulos').DataTable() : null;
  var caixas = function () {
    var nos = tabela ? tabela.rows().nodes().toArray() : Array.prototype.slice.call(document.querySelectorAll('#tabelaTitulos tbody tr'));
    var out = [];
    nos.forEach(function (tr) { var c = tr.querySelector('input.vc-marca'); if (c) { out.push(c); } });
    return out;
  };
  var marcadas = function () { return caixas().filter(function (c) { return c.checked; }); };
  var atualizar = function () {
    var m = marcadas();
    var total = 0, anos = {};
    m.forEach(function (c) { total += +c.dataset.valor; anos[c.dataset.ano] = 1; });
    /* anos diferentes (D-99, D-105): no Receber e no Renegociar, vale o responsável financeiro do ano letivo corrente, e a
       tela avisa */
    var mistura = Object.keys(anos).length > 1 && (modo === 'receber' || modo === 'renegociar');
    var aviso = document.getElementById('avisoAnos');
    aviso.textContent = modo === 'receber'
      ? 'Os títulos selecionados são de anos letivos diferentes: o recibo sai em nome do responsável financeiro do ano letivo corrente.'
      : 'Os títulos selecionados são de anos letivos diferentes: o acordo fica no ano letivo corrente, em nome do responsável financeiro desse ano.';
    aviso.classList.toggle('text-danger', false);
    aviso.classList.toggle('text-warning', true);
    document.getElementById('resumoSelecao').textContent = m.length + ' selecionado(s) · ' + brl(total);
    aviso.classList.toggle('d-none', !mistura);
    document.getElementById('continuarLote').disabled = !m.length;
    document.getElementById('limparSelecao').classList.toggle('d-none', !m.length);
    var todos = document.getElementById('marcarTodos');
    if (todos) { todos.checked = m.length > 0 && m.length === caixas().length; }
  };
  document.getElementById('tabelaTitulos') && document.getElementById('tabelaTitulos').addEventListener('change', function (ev) {
    if (ev.target.id === 'marcarTodos') { caixas().forEach(function (c) { c.checked = ev.target.checked; }); }
    atualizar();
  });
  document.getElementById('selecionarTodos').addEventListener('click', function () { caixas().forEach(function (c) { c.checked = true; }); atualizar(); });
  document.getElementById('limparSelecao').addEventListener('click', function () { caixas().forEach(function (c) { c.checked = false; }); atualizar(); });
  document.getElementById('continuarLote').addEventListener('click', function () {
    var m = marcadas();
    if (!m.length) { return; }
    var ids = m.map(function (c) { return c.value; });
    if (modo === 'receber' || modo === 'renegociar') { // a tela de Receber (ou a de Renegociar, D-105), com os títulos marcados
      window.location.href = '<?= URLADM ?>financeiro/' + (modo === 'receber' ? 'receber-pagamento' : 'renegociar-titulos') + '/<?= $h($alunoId) ?>?titulos=' + ids.join(',');
      return;
    }
    var alvo = document.getElementById('loteTitulos');
    alvo.innerHTML = '';
    var total = 0;
    m.forEach(function (c) { var i = document.createElement('input'); i.type = 'hidden'; i.name = 'titulos[]'; i.value = c.value; alvo.appendChild(i); total += +c.dataset.valor; });
    document.getElementById('loteResumo').textContent = m.length + ' título(s): ' + ids.join(', ') + ' · ' + brl(total);
    document.getElementById('formLote').dataset.vcConfirmaExtra = m.length + ' título(s) · ' + brl(total);
    window.vcAbrirModal('modalLote');
  });
  atualizar();
});
</script>
