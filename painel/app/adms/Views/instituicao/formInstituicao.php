<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E6a: dados da instituição (D-09) — o que os cabeçalhos e o histórico mostram. */
$i = $this->dados['inst'] ?? [];
$ufs = $this->dados['ufs'] ?? [];
$v = fn($c) => htmlspecialchars((string) ($i[$c] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Dados da instituição<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <div class="alert alert-info">Estes dados saem no cabeçalho do boletim em PDF e nos documentos (histórico escolar), pelas variáveis do editor de modelos. Enquanto nada for cadastrado, o sistema usa o nome da configuração do servidor.</div>
      <form method="POST" action="" enctype="multipart/form-data" novalidate id="formInstituicao">
        <div class="card shadow">
          <div class="card-body">
            <div class="row g-2">
              <div class="mb-3 col-md-6"><label class="form-label" for="nome">Nome da instituição</label><input type="text" name="nome" id="nome" class="form-control" value="<?= $v('nome') ?>" required></div>
              <div class="mb-3 col-md-6"><label class="form-label" for="razao_social">Mantenedora (razão social)</label><input type="text" name="razao_social" id="razao_social" class="form-control" value="<?= $v('razao_social') ?>" required></div>
            </div>
            <div class="row g-2">
              <div class="mb-3 col-md-4"><label class="form-label" for="cnpj">CNPJ</label><input type="text" name="cnpj" id="cnpj" class="form-control" value="<?= $v('cnpj') ?>" placeholder="00.000.000/0000-00" required></div>
              <div class="mb-3 col-md-3"><label class="form-label" for="codigo_emec">Código e-MEC <small class="text-muted">(opcional)</small></label><input type="text" inputmode="numeric" name="codigo_emec" id="codigo_emec" class="form-control" value="<?= $v('codigo_emec') ?>"><small class="form-text text-muted" id="ajudaEmec">Instituição de sistema estadual (regulada pelo conselho estadual) pode deixar vazio.</small></div>
              <div class="mb-3 col-md-5"><label class="form-label" for="site">Site <small class="text-muted">(opcional)</small></label><input type="text" name="site" id="site" class="form-control" value="<?= $v('site') ?>"></div>
            </div>
            <div class="row g-2">
              <div class="mb-3 col-md-4"><label class="form-label" for="telefone">Telefone <small class="text-muted">(opcional)</small></label><input type="tel" name="telefone" id="telefone" class="form-control" maxlength="30" value="<?= $v('telefone') ?>" placeholder="(00) 0000-0000"></div>
              <div class="mb-3 col-md-8"><label class="form-label" for="email">E-mail institucional <small class="text-muted">(opcional)</small></label><input type="email" inputmode="email" autocomplete="email" name="email" id="email" class="form-control" maxlength="150" value="<?= $v('email') ?>"></div>
            </div>
            <h5 class="mt-2">Endereço</h5>
            <div class="row g-2">
              <div class="mb-3 col-md-2"><label class="form-label" for="cep">CEP</label><input type="text" name="cep" id="cep" class="form-control" value="<?= $v('cep') ?>" placeholder="00000-000"></div>
              <div class="mb-3 col-md-6"><label class="form-label" for="logradouro">Logradouro</label><input type="text" name="logradouro" id="logradouro" class="form-control" value="<?= $v('logradouro') ?>"></div>
              <div class="mb-3 col-md-2"><label class="form-label" for="numero">Número</label><input type="text" name="numero" id="numero" class="form-control" value="<?= $v('numero') ?>"></div>
              <div class="mb-3 col-md-2"><label class="form-label" for="complemento">Complemento</label><input type="text" name="complemento" id="complemento" class="form-control" value="<?= $v('complemento') ?>"></div>
            </div>
            <div class="row g-2">
              <div class="mb-3 col-md-5"><label class="form-label" for="bairro">Bairro</label><input type="text" name="bairro" id="bairro" class="form-control" value="<?= $v('bairro') ?>"></div>
              <div class="mb-3 col-md-5"><label class="form-label" for="cidade">Cidade</label><input type="text" name="cidade" id="cidade" class="form-control" value="<?= $v('cidade') ?>"></div>
              <div class="mb-3 col-md-2"><label class="form-label" for="uf">UF</label>
                <select name="uf" id="uf" class="form-select"><option value=""></option>
                  <?php foreach ($ufs as $u) : ?><option value="<?= htmlspecialchars($u, ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) (($i['uf'] ?? '') === $u ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= htmlspecialchars($u, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select>
              </div>
            </div>
            <?php /* E1a (D-339): as linhas do cabeçalho institucional, na ordem em que saem nos documentos. As setas trocam a
               linha de lugar (só na tela; vale ao salvar). A prévia repete o desenho do documento: logo à esquerda, linhas à
               direita e, por último, a linha do curso, que vem do cadastro de cada curso. */
            $maxLin = \App\adms\Models\helper\AdmsInstituicao::CABECALHO_LINHAS; $maxCar = \App\adms\Models\helper\AdmsInstituicao::CABECALHO_MAX; ?>
            <h5 class="mt-2">Cabeçalho dos documentos</h5>
            <p class="small text-muted mb-2">Até <?= htmlspecialchars((string) ($maxLin), ENT_QUOTES, 'UTF-8') ?> linhas (Estado, órgão, diretoria, programa…), na ordem em que saem no topo do histórico e dos outros documentos. Linha vazia é ignorada. A linha do <b>curso</b> vem do cadastro de cada curso e sai por último.</p>
            <div class="row g-2">
              <div class="col-lg-7" id="linhasCabecalho">
                <?php for ($n = 1; $n <= $maxLin; $n++) : ?>
                  <div class="input-group input-group-sm mb-1 linha-cab">
                    <span class="input-group-text num-linha" style="min-width:2.2rem"><?= htmlspecialchars((string) ($n), ENT_QUOTES, 'UTF-8') ?></span>
                    <input type="text" name="cabecalho_<?= htmlspecialchars((string) ($n), ENT_QUOTES, 'UTF-8') ?>" id="cabecalho_<?= htmlspecialchars((string) ($n), ENT_QUOTES, 'UTF-8') ?>" class="form-control campo-cab" maxlength="<?= htmlspecialchars((string) ($maxCar), ENT_QUOTES, 'UTF-8') ?>" value="<?= $v('cabecalho_' . $n) ?>" aria-label="Linha <?= htmlspecialchars((string) ($n), ENT_QUOTES, 'UTF-8') ?> do cabeçalho">
                    
                      <button type="button" class="btn btn-outline-secondary sobe-cab" title="Subir esta linha" aria-label="Subir a linha <?= htmlspecialchars((string) ($n), ENT_QUOTES, 'UTF-8') ?>" <?= $n === 1 ? 'disabled' : '' ?>><i class="fas fa-arrow-up"></i></button>
                      <button type="button" class="btn btn-outline-secondary desce-cab" title="Descer esta linha" aria-label="Descer a linha <?= htmlspecialchars((string) ($n), ENT_QUOTES, 'UTF-8') ?>" <?= $n === $maxLin ? 'disabled' : '' ?>><i class="fas fa-arrow-down"></i></button>
                      <button type="button" class="btn btn-outline-danger limpa-cab" title="Apagar o texto desta linha" aria-label="Apagar a linha <?= htmlspecialchars((string) ($n), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-eraser"></i></button>
                    
                  </div>
                <?php endfor; ?>
              </div>
              <div class="col-lg-5">
                <div class="small fw-bold mb-1"><i class="far fa-eye"></i> Prévia do cabeçalho</div>
                <div class="border bg-white p-2" id="previaCabecalho" data-bs-theme="light" style="font-family: Arial, Helvetica, sans-serif; font-size: 9pt; line-height: 1.25">
                  <table style="width:100%"><tr>
                    <td style="width:26%; vertical-align: middle"><img src="<?= htmlspecialchars(\App\adms\Models\helper\AdmsInstituicao::logoUrl(), ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="max-width:100%; max-height:60px"></td>
                    <td style="vertical-align: middle"><div id="previaLinhas"></div><div class="text-muted fst-italic">[linha do curso, do cadastro do curso]</div></td>
                  </tr></table>
                </div>
                <small class="text-muted">No editor de modelos: bloco <b>Cabeçalho institucional</b> ou as variáveis de cada linha.</small>
              </div>
            </div>
            <h5 class="mt-3">Logo</h5>
            <div class="row g-2 align-items-center">
              <div class="mb-3 col-md-2 text-center"><img src="<?= htmlspecialchars(\App\adms\Models\helper\AdmsInstituicao::logoUrl(), ENT_QUOTES, 'UTF-8') ?>" alt="Logo atual" id="logoAtual" style="max-height:80px;max-width:100%"></div>
              <div class="mb-3 col-md-10">
                <label class="form-label" for="logo">Trocar o logo <small class="text-muted">(PNG ou JPEG, até 1 MB e 2000×2000 px)</small></label>
                <input type="file" name="logo" id="logo" class="form-control" accept="image/png,image/jpeg">
                <small class="form-text text-muted">A imagem é conferida pelo conteúdo e regravada pelo sistema. Deixe em branco para manter o atual. É o logo do topo do menu, da tela de entrada e dos documentos.</small>
              </div>
            </div>
            <?php $IL = \App\adms\Models\helper\AdmsInstituicao::class; $dadosI = $IL::dados() ?? []; ?>
            <div class="mb-3" style="max-width: 32rem">
              <label class="form-label" for="topo_menu">No topo do menu, ao lado do selo</label>
              <select name="topo_menu" id="topo_menu" class="form-select">
                <option value="nome" <?= htmlspecialchars((string) (($dadosI['topo_menu'] ?? 'nome') !== 'logo' ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>O nome da instituição, escrito</option>
                <option value="logo" <?= htmlspecialchars((string) (($dadosI['topo_menu'] ?? '') === 'logo' ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>O logo da instituição</option>
              </select>
              <small class="form-text text-muted">Sem logo enviado, vale o nome.</small>
            </div>
            <div class="mb-3" style="max-width: 32rem">
              <label class="form-label" for="icone_aba">Ícone da aba do navegador</label>
              <select name="icone_aba" id="icone_aba" class="form-select">
                <option value="vcostaedu" <?= htmlspecialchars((string) (($dadosI['icone_aba'] ?? 'vcostaedu') !== 'instituicao' ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>O do VCOSTAEDU</option>
                <option value="instituicao" <?= htmlspecialchars((string) (($dadosI['icone_aba'] ?? '') === 'instituicao' ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>O logo da instituição</option>
              </select>
            </div>
          </div>
          <div class="card-footer"><button type="submit" name="SalvarInstituicao" value="Salvar" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Salvar</button></div>
        </div>
      </form>
    </div>
  </section>
</div>
<script>
  /* E1a: setas trocam o TEXTO de duas linhas vizinhas (os campos ficam no lugar); a prévia acompanha ao digitar. */
  document.addEventListener('DOMContentLoaded', function () {
    var caixa = document.getElementById('linhasCabecalho'), previa = document.getElementById('previaLinhas');
    if (!caixa || !previa) { return; }
    var campos = Array.prototype.slice.call(caixa.querySelectorAll('.campo-cab'));
    var desenha = function () {
      previa.textContent = '';
      campos.forEach(function (c) {
        var t = c.value.replace(/\s+/g, ' ').trim();
        if (t !== '') { var d = document.createElement('div'); d.textContent = t; previa.appendChild(d); }
      });
      if (!previa.childNodes.length) { var v = document.createElement('div'); v.className = 'text-muted'; v.textContent = '(nenhuma linha preenchida)'; previa.appendChild(v); }
    };
    var troca = function (i, j) {
      if (j < 0 || j >= campos.length) { return; }
      var t = campos[i].value; campos[i].value = campos[j].value; campos[j].value = t;
      campos[j].focus(); desenha();
    };
    caixa.addEventListener('click', function (ev) {
      var b = ev.target.closest('button'); if (!b) { return; }
      var i = campos.indexOf(b.closest('.linha-cab').querySelector('.campo-cab'));
      if (b.classList.contains('sobe-cab')) { troca(i, i - 1); }
      else if (b.classList.contains('desce-cab')) { troca(i, i + 1); }
      else if (b.classList.contains('limpa-cab')) { campos[i].value = ''; campos[i].focus(); desenha(); }
    });
    caixa.addEventListener('input', desenha);
    desenha();
  });
</script>
