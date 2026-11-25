<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
use App\adms\Models\historico\AdmsHistoricoModelos as M;
use App\adms\Models\historico\AdmsHistoricoSecoes as S;
use App\adms\Models\historico\AdmsHistoricoVariaveis as V;
use App\adms\Models\historico\AdmsHistoricoEditor as E;

/* E8d (A24, R10): o EDITOR PADRÃO — um documento só no editor visual, painel de variáveis que insere no cursor,
   blocos do sistema como marcadores, opções dos blocos em abas ao lado e a prévia em PDF. Nada de HTML digitado. */
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$id = (int) $this->dados['id'];
$m = $this->dados['modeloAtual'];
$f = $this->dados['form'] ?? null;
$ed = $this->dados['editor'];
$nome = $f['nome'] ?? ($m['nome'] ?? '');
$situacao = (int) ($f['situacao'] ?? ($m['situacao'] ?? 1));
$cursosSel = array_map('intval', (array) ($f['cursos'] ?? ($m['cursos'] ?? [])));
$tipo = $this->dados['tipo'];
$parcial = $tipo === 'PARCIAL';
$semMod = \App\adms\Models\helper\AdmsModuloInterno::ativo(); /* E8c (D-268) / E8d (P-10): sem a coluna "Módulo" */
$cols = [];
foreach ($ed['quadro']['colunas'] ?? [] as $c) {
  $cols[$c['campo']] = $c;
}
/* colunas na ordem do modelo; as não incluídas depois, na ordem do catálogo */
$ordemCampos = array_merge(array_keys($cols), array_diff(array_keys(S::COLUNAS), array_keys($cols)));
$config = ['blocos' => E::BLOCOS + E::condicionais()]; // Onda 3, Etapa 3: sem o "Ver código" (o produto não se programa pela tela) // E5 (D-366): os marcadores de qualquer trecho condicional
?>
<?php /* VCOSTAEDU (Etapa 4): o CSS do Summernote no build Bootstrap 5 vem do head.php do layout Tabler (carregarEditor) */ ?>
<div class="vc-pagina">
  <?php ob_start(); ?><?= $id ? 'Editar modelo de histórico' : 'Novo modelo de histórico' ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <form method="POST" action="" enctype="multipart/form-data" id="formModelo" novalidate>
        <input type="hidden" name="formato" value="<?= E::FORMATO ?>">
        <div class="card shadow">
          <div class="card-body">
            <div class="row g-2">
              <div class="mb-3 col-md-5"><label class="form-label" for="nome">Nome do modelo</label><input type="text" name="nome" id="nome" class="form-control" maxlength="150" value="<?= $h($nome) ?>"></div>
              <div class="mb-3 col-md-2"><label class="form-label" for="tipo">Tipo</label>
                <select name="tipo" id="tipo" class="form-select" <?= $id ? 'disabled' : '' ?>>
                  <?php foreach (M::TIPOS as $k => $rot) : ?><option value="<?= htmlspecialchars((string) ($k), ENT_QUOTES, 'UTF-8') ?>" <?= $tipo === $k ? 'selected' : '' ?>><?= htmlspecialchars((string) ($rot), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select>
                <?php if (!$id) : ?><small class="text-muted">Trocou o tipo? <a href="#" onclick="location.href='<?= URLADM ?>historico-modelos/cadastrar?tipo='+document.getElementById('tipo').value;return false;">recarregue o padrão</a>.</small><?php endif; ?>
              </div>
              <div class="mb-3 col-md-2"><label class="form-label" for="situacao">Situação</label>
                <select name="situacao" id="situacao" class="form-select">
                  <?php foreach (M::SITUACOES as $k => $rot) : ?><option value="<?= htmlspecialchars((string) ($k), ENT_QUOTES, 'UTF-8') ?>" <?= $situacao === $k ? 'selected' : '' ?>><?= htmlspecialchars((string) ($rot), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select></div>
              <div class="mb-3 col-md-3"><label class="form-label">Versão</label>
                <p class="form-control-plaintext" id="versaoModelo"><?= htmlspecialchars((string) ($m ? 'v' . (int) $m['versao_atual'] . ((int) $m['situacao'] === 2 ? ' — salvar uma mudança cria a v' . ((int) $m['versao_atual'] + 1) : '') : 'v1 (novo)'), ENT_QUOTES, 'UTF-8') ?></p></div>
            </div>
            <div class="mb-3 mb-0"><label class="form-label">Aplica-se aos cursos <small class="text-muted">(nenhum marcado = padrão da instituição para o tipo)</small></label><br>
              <?php foreach ($this->dados['cursos'] as $c) : ?>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="cursos[]" id="curso<?= htmlspecialchars((string) ((int) $c['id']), ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) ((int) $c['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) (in_array((int) $c['id'], $cursosSel, true) ? 'checked' : ''), ENT_QUOTES, 'UTF-8') ?>><label class="form-check-label" for="curso<?= htmlspecialchars((string) ((int) $c['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $h($c['nome']) ?></label></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-xl-7 col-lg-7">
            <div class="card shadow">
              <div class="card-header py-2"><b>Documento</b> <small class="text-muted">— escreva como num editor de texto; as variáveis e os blocos entram pelo painel ao lado, no ponto do cursor</small></div>
              <div class="card-body p-2">
                <textarea name="documento" id="documento" class="d-none"><?= $h($ed['documento'] ?? '') ?></textarea>
                <div id="editorDocumento" class="editor-padrao" data-campo="documento"></div>
                <small class="text-muted d-block mt-1">Os marcadores em destaque (ex.: <span class="bloco-sistema">[Quadro de disciplinas]</span>) são montados pelo sistema na emissão: podem ser movidos (recortar e colar) ou apagados inteiros. As opções deles ficam nas abas <b>Quadro</b> e <b>Assinaturas</b>.</small>
              </div>
            </div>
            <div class="mb-4"><input type="submit" name="SalvarModelo" value="Salvar modelo" class="btn btn-primary"> <a href="<?= URLADM ?>historico-modelos/listar" class="btn btn-secondary">Voltar</a></div>
          </div>

          <div class="col-xl-5 col-lg-5">
            <div class="card shadow painel-editor">
              <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" id="abasEditor" role="tablist">
                  <li class="nav-item"><a class="nav-link active" id="aba-variaveis" data-bs-toggle="tab" href="#painelVariaveis" role="tab">Variáveis</a></li>
                  <li class="nav-item"><a class="nav-link" id="aba-quadro" data-bs-toggle="tab" href="#painelQuadro" role="tab">Quadro</a></li>
                  <li class="nav-item"><a class="nav-link" id="aba-assinaturas" data-bs-toggle="tab" href="#painelAssinaturas" role="tab">Assinaturas</a></li>
                  <li class="nav-item"><a class="nav-link" id="aba-opcoes" data-bs-toggle="tab" href="#painelOpcoes" role="tab">Opções</a></li>
                  <li class="nav-item"><a class="nav-link" id="aba-previa" data-bs-toggle="tab" href="#painelPrevia" role="tab"><i class="fas fa-eye"></i> Prévia</a></li>
                </ul>
              </div>
              <div class="card-body tab-content py-2">
                <div class="tab-pane fade show active" id="painelVariaveis" role="tabpanel">
                  <input type="search" id="buscaVariavel" class="form-control form-control-sm mb-2" placeholder="Buscar variável (ex.: nome, CPF, data)" aria-label="Buscar variável">
                  <div id="catalogoVariaveis" class="catalogo-variaveis">
                    <div class="grupo-var"><div class="small fw-bold mt-1">Blocos do sistema</div>
                      <?php foreach (E::BLOCOS as $var => $rot) : ?>
                        <button type="button" class="btn btn-sm btn-outline-primary mb-1 btn-var btn-bloco" data-var="<?= htmlspecialchars((string) ($var), ENT_QUOTES, 'UTF-8') ?>" data-busca="<?= $h(mb_strtolower($rot . ' ' . $var)) ?>" title="Bloco montado pelo sistema: <?= $h($rot) ?>"><i class="fas fa-cube"></i> <?= $h($rot) ?></button>
                      <?php endforeach; ?>
                    </div>
                    <?php foreach (V::catalogo() as $grupo => $vars) : ?>
                      <div class="grupo-var"><div class="small fw-bold mt-2"><?= $h($grupo) ?></div>
                        <?php foreach ($vars as $var => $desc) : ?>
                          <button type="button" class="btn btn-sm btn-outline-secondary mb-1 btn-var" data-var="<?= $h($var) ?>" data-busca="<?= $h(mb_strtolower($grupo . ' ' . $desc . ' ' . $var)) ?>" title="{{<?= $h($var) ?>}}"><?= $h($desc) ?></button>
                        <?php endforeach; ?>
                        <?php if ($grupo === 'TCC') : foreach (E::CONDICIONAIS as $var => $rot) : ?>
                          <button type="button" class="btn btn-sm btn-outline-primary mb-1 btn-var btn-bloco" data-var="<?= htmlspecialchars((string) ($var), ENT_QUOTES, 'UTF-8') ?>" data-busca="<?= $h(mb_strtolower('tcc ' . $rot)) ?>" title="O que ficar entre o início e o fim só sai quando o aluno tem TCC"><i class="fas fa-code-branch"></i> <?= $h($rot) ?></button>
                        <?php endforeach; endif; ?>
                      </div>
                    <?php endforeach; ?>
                    <p class="small text-muted mb-0 mt-2" id="semVariavel" hidden>Nenhuma variável com esse texto.</p>
                  </div>
                  <?php /* E5 (D-366): trecho que só sai quando a variável escolhida está preenchida (o mesmo mecanismo do TCC) */ ?>
                  <div class="border rounded p-1 mt-2" id="trechoCondicional">
                    <div class="small fw-bold">Trecho que só sai se houver o dado</div>
                    <select id="varCondicional" class="form-select form-select-sm mt-1" aria-label="Variável da condição">
                      <?php foreach (V::catalogo() as $grupo => $vars) : ?><optgroup label="<?= $h($grupo) ?>"><?php foreach ($vars as $var => $desc) : ?><option value="<?= $h($var) ?>"><?= $h($desc) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?>
                    </select>
                    <div class="btn-group btn-group-sm mt-1">
                      <button type="button" class="btn btn-outline-primary btn-cond" data-lado="SE_" title="Marca o início do trecho no ponto do cursor"><i class="fas fa-code-branch"></i> Início</button>
                      <button type="button" class="btn btn-outline-primary btn-cond" data-lado="FIM_SE_" title="Marca o fim do trecho no ponto do cursor"><i class="fas fa-flag-checkered"></i> Fim</button>
                    </div>
                    <small class="text-muted d-block">Sem o dado, o trecho inteiro sai do documento e não vira alerta. Ex.: "Telefone: …" só com o telefone cadastrado.</small>
                  </div>
                  <small class="text-muted d-block mt-2">Um clique insere no ponto do cursor. Variável sem valor para o aluno vira <b>alerta</b> na emissão.</small>
                </div>

                <div class="tab-pane fade" id="painelQuadro" role="tabpanel">
                  <small class="text-muted d-block mb-2"><?= $semMod ? 'Lista simples das disciplinas da grade.' : 'Agrupado por módulo, com a CH do módulo.' ?> Marque as colunas, dê o rótulo, a largura (em %) e o alinhamento; as setas mudam a ordem.</small>
                  <div id="colunasQuadro">
                    <?php foreach ($ordemCampos as $campo) :
                      $rot = S::COLUNAS[$campo];
                      $c = $cols[$campo] ?? null;
                      $cn = "quadro[colunas][{$campo}]";
                      if ($semMod && $campo === 'modulo') : /* P-10: com "Não", a linha nem aparece; a escolha fica guardada no servidor */
                        continue;
                      endif; ?>
                      <div class="coluna-quadro border rounded p-1 mb-1" data-campo="<?= htmlspecialchars((string) ($campo), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="d-flex align-items-center">
                          <div class="form-check me-2"><input type="checkbox" class="form-check-input" id="col_<?= htmlspecialchars((string) ($campo), ENT_QUOTES, 'UTF-8') ?>" name="<?= htmlspecialchars((string) ($cn), ENT_QUOTES, 'UTF-8') ?>[incluir]" value="1" <?= $c ? 'checked' : '' ?>><label class="form-check-label fw-bold small" for="col_<?= htmlspecialchars((string) ($campo), ENT_QUOTES, 'UTF-8') ?>"><?= $h($rot) ?></label></div>
                          <input type="hidden" name="<?= htmlspecialchars((string) ($cn), ENT_QUOTES, 'UTF-8') ?>[ordem]" value="<?= htmlspecialchars((string) ($c ? (int) $c['ordem'] : 99), ENT_QUOTES, 'UTF-8') ?>" class="ordem-coluna">
                          <div class="ms-auto btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary btn-subir" title="Subir: a coluna vai para a esquerda" aria-label="Subir <?= $h($rot) ?>"><i class="fas fa-arrow-up"></i></button>
                            <button type="button" class="btn btn-outline-secondary btn-descer" title="Descer: a coluna vai para a direita" aria-label="Descer <?= $h($rot) ?>"><i class="fas fa-arrow-down"></i></button>
                          </div>
                        </div>
                        <div class="row g-2 mt-1">
                          <div class="col-6"><input type="text" class="form-control form-control-sm" name="<?= htmlspecialchars((string) ($cn), ENT_QUOTES, 'UTF-8') ?>[rotulo]" maxlength="40" value="<?= $h($c['rotulo'] ?? $rot) ?>" aria-label="Rótulo de <?= $h($rot) ?>" title="Rótulo no cabeçalho do quadro"></div>
                          <div class="col-2"><input type="text" class="form-control form-control-sm" name="<?= htmlspecialchars((string) ($cn), ENT_QUOTES, 'UTF-8') ?>[largura]" maxlength="5" placeholder="12%" value="<?= $h($c['largura'] ?? '') ?>" aria-label="Largura de <?= $h($rot) ?>" title="Largura em % (vazio = automática)"></div>
                          <div class="col-4"><select class="form-select form-select-sm" name="<?= htmlspecialchars((string) ($cn), ENT_QUOTES, 'UTF-8') ?>[alinhamento]" aria-label="Alinhamento de <?= $h($rot) ?>" title="Alinhamento"><?php foreach (S::ALINHAMENTOS as $k => $r) : ?><option value="<?= htmlspecialchars((string) ($k), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) (($c['alinhamento'] ?? 'left') === $k ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= htmlspecialchars((string) ($r), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <?php if ($parcial) : ?>
                    <div class="form-check mt-2"><input type="checkbox" class="form-check-input" id="quadroAtividades" name="quadro[atividades]" value="1" <?= htmlspecialchars((string) (!empty($ed['quadro']['atividades']) ? 'checked' : ''), ENT_QUOTES, 'UTF-8') ?>><label class="form-check-label" for="quadroAtividades">Linha das atividades (tipo e data) em cada disciplina</label></div>
                    <div class="mb-3 mt-1 mb-0"><label class="small mb-0" for="quadroEmCurso">Situação de disciplina ainda aberta</label><input type="text" class="form-control form-control-sm" id="quadroEmCurso" name="quadro[emCurso]" maxlength="30" value="<?= $h($ed['quadro']['emCurso'] ?? E::EM_CURSO) ?>"></div>
                  <?php else : ?>
                    <input type="hidden" name="quadro[atividades]" value="<?= htmlspecialchars((string) (!empty($ed['quadro']['atividades']) ? 1 : ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="quadro[emCurso]" value="<?= $h($ed['quadro']['emCurso'] ?? E::EM_CURSO) ?>">
                    <small class="text-muted d-block mt-2">No histórico FINAL, cada disciplina sai só com a tentativa vigente (sem as atividades).</small>
                  <?php endif; ?>
                  <?php /* E5 (D-369, D-370): opções do modelo do cliente; desligadas, o quadro sai como sempre */ $qd = $ed['quadro'] ?? []; ?>
                  <div class="border rounded p-1 mt-2" id="opcoesQuadroCliente">
                    <div class="form-check"><input type="checkbox" class="form-check-input" id="quadroChNoNome" name="quadro[chNoNome]" value="1" <?= htmlspecialchars((string) (!empty($qd['chNoNome']) ? 'checked' : ''), ENT_QUOTES, 'UTF-8') ?>><label class="form-check-label small" for="quadroChNoNome">Carga horária dentro do nome: "Disciplina (<b>18</b> …)"</label></div>
                    <input type="text" class="form-control form-control-sm mt-1" name="quadro[textoCh]" id="quadroTextoCh" maxlength="20" value="<?= $h($qd['textoCh'] ?? E::TEXTO_CH) ?>" aria-label="Texto depois da carga horária" title="Texto depois do número (ex.: horas aula)">
                    <div class="form-check mt-1"><input type="checkbox" class="form-check-input" id="quadroSoVigente" name="quadro[soVigente]" value="1" <?= htmlspecialchars((string) (!empty($qd['soVigente']) ? 'checked' : ''), ENT_QUOTES, 'UTF-8') ?>><label class="form-check-label small" for="quadroSoVigente">Uma linha por disciplina (só a tentativa vigente)</label></div>
                    <div class="form-check"><input type="checkbox" class="form-check-input" id="quadroAndamentoBranco" name="quadro[andamentoEmBranco]" value="1" <?= htmlspecialchars((string) (!empty($qd['andamentoEmBranco']) ? 'checked' : ''), ENT_QUOTES, 'UTF-8') ?>><label class="form-check-label small" for="quadroAndamentoBranco">Disciplina em andamento: nota e frequência em branco</label></div>
                    <div class="form-check"><input type="checkbox" class="form-check-input" id="quadroLinhasFinais" name="quadro[linhasFinais]" value="1" <?= htmlspecialchars((string) (!empty($qd['linhasFinais']) ? 'checked' : ''), ENT_QUOTES, 'UTF-8') ?>><label class="form-check-label small" for="quadroLinhasFinais">Linhas finais: média final do aluno, TCC e situação (fundo cinza)</label></div>
                  </div>
                  <?php /* E2 (D-362): como a frequência sai na coluna Frequência; ausente no modelo = percentual */ $modoFreqAtual = (string) ($ed['quadro']['frequencia'] ?? E::FREQUENCIA_PADRAO); ?>
                  <div class="mb-3 mt-2 mb-0"><label class="small mb-0" for="quadroFrequencia">Frequência</label>
                    <select class="form-select form-select-sm" id="quadroFrequencia" name="quadro[frequencia]">
                      <?php foreach (E::FREQUENCIA_EXIBICAO as $k => $rot) : ?><option value="<?= $h($k) ?>" <?= $modoFreqAtual === $k ? 'selected' : '' ?>><?= $h($rot) ?></option><?php endforeach; ?>
                    </select>
                    <small class="text-muted">Sigla: FS/FI só com a disciplina encerrada (mínimo gravado no encerramento); aberta, em branco.</small>
                  </div>
                </div>

                <div class="tab-pane fade" id="painelAssinaturas" role="tabpanel">
                  <?php $blocos = array_values($ed['assinaturas']['blocos'] ?? []); while (count($blocos) < min(S::MAX_ASSINATURAS, max(3, count($blocos) + 1))) { $blocos[] = ['nome' => '', 'cargo' => '', 'imagem' => null]; } ?>
                  <small class="text-muted d-block mb-2">Onde está o marcador <span class="bloco-sistema">[Assinaturas]</span>, até três por linha. Nome e cargo de quem assina; imagem opcional (PNG ou JPEG até 1 MB, conferida pelo conteúdo). Bloco sem nome e sem cargo é ignorado.</small>
                  <div id="blocosAssinatura">
                    <?php foreach ($blocos as $i => $b) : $bn = "assinaturas[blocos][{$i}]"; ?>
                      <div class="border rounded p-1 mb-1 bloco-assinatura">
                        <div class="small fw-bold">Assinatura <?= $i + 1 ?></div>
                        <div class="row g-2">
                          <div class="col-6"><input type="text" class="form-control form-control-sm" name="<?= htmlspecialchars((string) ($bn), ENT_QUOTES, 'UTF-8') ?>[nome]" maxlength="120" placeholder="Nome" aria-label="Nome da assinatura <?= $i + 1 ?>" value="<?= $h($b['nome']) ?>"></div>
                          <div class="col-6"><input type="text" class="form-control form-control-sm" name="<?= htmlspecialchars((string) ($bn), ENT_QUOTES, 'UTF-8') ?>[cargo]" maxlength="120" placeholder="Cargo" aria-label="Cargo da assinatura <?= $i + 1 ?>" value="<?= $h($b['cargo']) ?>"></div>
                        </div>
                        <?php if (!empty($b['imagem'])) : ?><input type="hidden" name="<?= htmlspecialchars((string) ($bn), ENT_QUOTES, 'UTF-8') ?>[imagemAtual]" value="<?= $h($b['imagem']) ?>"><span class="badge bg-success-lt">imagem enviada</span> <label class="small mb-0"><input type="checkbox" name="<?= htmlspecialchars((string) ($bn), ENT_QUOTES, 'UTF-8') ?>[removerImagem]" value="1"> remover</label><?php endif; ?>
                        <input type="file" name="assinatura_img[<?= htmlspecialchars((string) ($i), ENT_QUOTES, 'UTF-8') ?>]" accept="image/png,image/jpeg" class="form-control form-control-sm mt-1" aria-label="Imagem da assinatura <?= $i + 1 ?>">
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>

                <div class="tab-pane fade" id="painelOpcoes" role="tabpanel">
                  <div class="form-check"><input type="checkbox" class="form-check-input" id="paginacao" name="opcoes[paginacao]" value="1" <?= htmlspecialchars((string) (!empty($ed['opcoes']['paginacao']) ? 'checked' : ''), ENT_QUOTES, 'UTF-8') ?>><label class="form-check-label" for="paginacao">Código de verificação e "Página X de Y" no pé de toda página</label></div>
                  <small class="text-muted d-block mt-2">A prévia e a emissão levam também, no topo de toda página, a tarja da prévia ou a marca da 2ª via.</small>
                  <?php /* E5 (D-367, D-368): estilo, fonte e casas decimais do documento; os modelos anteriores ficam no padrão */
                  $op = $ed['opcoes'] ?? []; $estAt = (string) ($op['estilo'] ?? 'padrao'); $fonAt = (string) ($op['fonte'] ?? 'padrao'); $casAt = isset($op['casasNota']) ? (string) (int) $op['casasNota'] : ''; ?>
                  <div class="mb-3 mt-3 mb-1"><label class="small mb-0" for="opEstilo">Estilo do documento</label>
                    <select class="form-select form-select-sm" id="opEstilo" name="opcoes[estilo]"><?php foreach (E::ESTILOS as $k => $r) : ?><option value="<?= $h($k) ?>" <?= $estAt === $k ? 'selected' : '' ?>><?= $h($r) ?></option><?php endforeach; ?></select>
                    <small class="text-muted">"Documento oficial": margens, cabeçalho sem filete e quadro com bordas pretas, como no modelo Word.</small></div>
                  <div class="mb-3 mb-1"><label class="small mb-0" for="opFonte">Fonte</label>
                    <select class="form-select form-select-sm" id="opFonte" name="opcoes[fonte]"><?php foreach (E::FONTES as $k => $r) : ?><option value="<?= $h($k) ?>" <?= $fonAt === $k ? 'selected' : '' ?>><?= $h($r) ?></option><?php endforeach; ?></select></div>
                  <div class="mb-3 mb-0"><label class="small mb-0" for="opCasas">Casas decimais das notas no documento</label>
                    <select class="form-select form-select-sm" id="opCasas" name="opcoes[casasNota]"><?php foreach (E::CASAS_NOTA as $k => $r) : ?><option value="<?= $h((string) $k) ?>" <?= $casAt === (string) $k ? 'selected' : '' ?>><?= $h($r) ?></option><?php endforeach; ?></select>
                    <small class="text-muted">Só a forma de escrever: a média é calculada com as casas de Configurações → Instituição. Vale para disciplina, TCC e média final do aluno.</small></div>
                </div>

                <div class="tab-pane fade" id="painelPrevia" role="tabpanel">
                  <select name="previa_matricula" id="previa_matricula" class="form-select form-select-sm mb-2" aria-label="Aluno da prévia">
                    <option value="">— aluno (matrícula) —</option>
                    <?php foreach ($this->dados['matriculas'] as $x) : ?><option value="<?= htmlspecialchars((string) ((int) $x['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $h($x['nome'] . ' (' . $x['rm'] . ') — ' . $x['curso'] . ' — ' . $x['situacao']) ?></option><?php endforeach; ?>
                  </select>
                  <button type="submit" name="PreviaModelo" value="1" id="btnPrevia" class="btn btn-warning btn-sm w-100" formaction="<?= URLADM . 'historico-modelos/previa/' . $id ?>" formtarget="quadroPrevia"><i class="fas fa-eye"></i> Gerar a prévia em PDF (não salva nada)</button>
                  <iframe name="quadroPrevia" id="quadroPrevia" class="quadro-previa mt-2" title="Prévia do histórico em PDF"></iframe>
                  <small class="text-muted">Usa o documento como está agora; imagens de assinatura novas só entram depois de salvar.</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </section>
</div>
<script type="application/json" id="configEditor"><?= json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
