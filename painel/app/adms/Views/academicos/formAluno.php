<?php
/*
 * Campos do cadastro do ALUNO (E2b), compartilhados por cadAcademico.php e
 * editAcademico.php. Espera $valorForm (array) e $this->dados['select'].
 * Referência de campos: portal_alunos do sistema de origem de ensino superior + o que o histórico de pós
 * exige (nome social, naturalidade, nacionalidade, RG com órgão/UF, CPF,
 * filiação).
 */
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$v = function ($campo) use ($valorForm) {
  return htmlspecialchars((string) ($valorForm[$campo] ?? ''), ENT_QUOTES);
};
$sel = $this->dados['select'] ?? [];
$opcoesUf = function ($campo) use ($valorForm, $sel) {
  $html = "<option value=''>UF</option>";
  foreach (($sel['ufs'] ?? []) as $uf) {
    $s = (($valorForm[$campo] ?? '') === $uf['sigla']) ? ' selected' : '';
    $html .= "<option value='" . htmlspecialchars($uf['sigla'], ENT_QUOTES) . "'{$s}>" . htmlspecialchars($uf['sigla']) . "</option>";
  }
  return $html;
};
?>
<h5 class="text-muted">Identificação</h5>
<div class="row g-2">
  <div class="mb-3 col-md-3">
    <label class="form-label" for="rm"><?= \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero() ?> <span class="text-danger">*</span></label>
    <?php /* D-102: na escola, o cadastro novo abre com o próximo número da sequência (a secretaria pode trocar) */
      $numeroProposto = \App\adms\Models\helper\AdmsParametro::naEscola() && empty($valorForm['id']) && (string) ($valorForm['rm'] ?? '') === '' ? \App\adms\Models\academicos\AdmsValidaAluno::proximoNumero() : null; ?>
    <input type="text" inputmode="numeric" pattern="\d{1,9}" maxlength="9" class="form-control" name="rm" id="rm" placeholder="Só dígitos" value="<?= $numeroProposto !== null ? htmlspecialchars($numeroProposto, ENT_QUOTES) : $v('rm') ?>" required>
    <small class="form-text text-muted" id="dicaNumero"><?= $numeroProposto !== null ? 'O próximo número da sequência; pode trocar. Único.' : (\App\adms\Models\helper\AdmsParametro::naEscola() ? 'O número de matrícula do aluno. Único.' : 'Registro acadêmico, digitado pela secretaria. Único.') ?></small>
  </div>
  <div class="mb-3 col-md-9">
    <label class="form-label" for="nome">Nome completo <span class="text-danger">*</span></label>
    <input type="text" class="form-control" name="nome" id="nome" value="<?= $v('nome') ?>" required>
  </div>
</div>
<div class="row g-2">
  <div class="mb-3 col-md-6">
    <label class="form-label" for="nome_social">Nome social <small class="text-muted">(opcional)</small></label>
    <input type="text" class="form-control" name="nome_social" id="nome_social" value="<?= $v('nome_social') ?>">
  </div>
  <div class="mb-3 col-md-3">
    <?php /* E1b (D-356): obrigatório, sem padrão. Aluno antigo que ficou com "Outros" POR PADRÃO (nunca escolhido na tela)
       abre com "Selecione", para a secretaria escolher de propósito. */
    $genSel = (string) ($valorForm['genero_id'] ?? '');
    if (array_key_exists('genero_confirmado_em', $valorForm ?? []) && $valorForm['genero_confirmado_em'] === null && !in_array($genSel, ['1', '2'], true)) {
        $genSel = '';
    } ?>
    <label class="form-label" for="genero_id">Sexo <span class="text-danger">*</span></label>
    <select name="genero_id" id="genero_id" class="form-select" required>
      <option value="" <?= $genSel === '' ? 'selected' : '' ?>>Selecione</option>
      <?php foreach (($sel['generos'] ?? []) as $gen) { ?>
        <option value="<?= htmlspecialchars((string) ((int) $gen['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ($genSel === (string) $gen['id'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= htmlspecialchars($gen['nome']) ?></option>
      <?php } ?>
    </select>
    <small class="form-text text-muted">Os documentos concordam com ele (ALUNA/ALUNO). "Outros" usa a forma neutra.</small>
  </div>
  <div class="mb-3 col-md-3">
    <label class="form-label" for="col_sits_usuario_id">Situação</label>
    <select name="col_sits_usuario_id" id="col_sits_usuario_id" class="form-select">
      <?php foreach (($sel['situacoes'] ?? []) as $sit) { ?>
        <option value="<?= htmlspecialchars((string) ((int) $sit['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) (((string) ($valorForm['col_sits_usuario_id'] ?? '1') === (string) $sit['id']) ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= htmlspecialchars($sit['nome']) ?></option>
      <?php } ?>
    </select>
  </div>
</div>

<h5 class="text-muted mt-3">Documentos e nascimento</h5>
<div class="row g-2">
  <div class="mb-3 col-md-3">
    <label class="form-label" for="cpf">CPF <small class="text-muted">(opcional)</small></label>
    <input type="text" class="form-control campocpf" name="cpf" id="cpf" value="<?= $v('cpf') ?>">
  </div>
  <div class="mb-3 col-md-3">
    <label class="form-label" for="rg">RG</label>
    <input type="text" class="form-control" name="rg" id="rg" value="<?= $v('rg') ?>">
  </div>
  <div class="mb-3 col-md-3">
    <label class="form-label" for="rg_org">Órgão emissor</label>
    <input type="text" class="form-control" name="rg_org" id="rg_org" value="<?= $v('rg_org') ?>">
  </div>
  <div class="mb-3 col-md-3">
    <label class="form-label" for="rg_uf">UF do RG</label>
    <select name="rg_uf" id="rg_uf" class="form-select"><?= $opcoesUf('rg_uf') ?></select>
  </div>
</div>
<div class="row g-2">
  <div class="mb-3 col-md-3">
    <label class="form-label" for="nasc_data">Data de nascimento</label>
    <input type="date" class="form-control" name="nasc_data" id="nasc_data" value="<?= $v('nasc_data') ?>">
  </div>
  <div class="mb-3 col-md-4">
    <label class="form-label" for="nasc_local">Cidade de nascimento</label>
    <input type="text" class="form-control" name="nasc_local" id="nasc_local" value="<?= $v('nasc_local') ?>">
  </div>
  <div class="mb-3 col-md-2">
    <label class="form-label" for="nasc_uf">UF</label>
    <select name="nasc_uf" id="nasc_uf" class="form-select"><?= $opcoesUf('nasc_uf') ?></select>
  </div>
  <div class="mb-3 col-md-3">
    <label class="form-label" for="nasc_nac">Nacionalidade</label>
    <input type="text" class="form-control" name="nasc_nac" id="nasc_nac" value="<?= $v('nasc_nac') ?>">
  </div>
</div>
<div class="row g-2">
  <div class="mb-3 col-md-6">
    <label class="form-label" for="nome_mae">Nome da mãe <small class="text-muted">(opcional)</small></label>
    <input type="text" class="form-control" name="nome_mae" id="nome_mae" value="<?= $v('nome_mae') ?>">
  </div>
  <div class="mb-3 col-md-6">
    <label class="form-label" for="nome_pai">Nome do pai <small class="text-muted">(opcional)</small></label>
    <input type="text" class="form-control" name="nome_pai" id="nome_pai" value="<?= $v('nome_pai') ?>">
  </div>
</div>

<h5 class="text-muted mt-3">Contato</h5>
<div class="row g-2">
  <div class="mb-3 col-md-6">
    <label class="form-label" for="email">E-mail <small class="text-muted">(necessário para receber o link de acesso)</small></label>
    <input type="email" class="form-control" name="email" id="email" value="<?= $v('email') ?>">
  </div>
  <div class="mb-3 col-md-3">
    <label class="form-label" for="telefone">Telefone</label>
    <input type="text" class="form-control campotelefone" name="telefone" id="telefone" value="<?= $v('telefone') ?>">
  </div>
  <div class="mb-3 col-md-3">
    <label class="form-label" for="celular">Celular</label>
    <input type="text" class="form-control campocelular" name="celular" id="celular" value="<?= $v('celular') ?>">
  </div>
</div>

<h5 class="text-muted mt-3">Endereço</h5>
<div class="row g-2">
  <div class="mb-3 col-md-3">
    <label class="form-label" for="cep0">CEP</label>
    <input type="text" class="form-control campocep" name="cep" id="cep0" onblur="pesquisacep(this.value, 0);" value="<?= $v('cep') ?>">
  </div>
  <div class="mb-3 col-md-7">
    <label class="form-label" for="rua0">Endereço</label>
    <input type="text" class="form-control" name="endereco" id="rua0" value="<?= $v('endereco') ?>">
  </div>
  <div class="mb-3 col-md-2">
    <label class="form-label" for="numero0">Número</label>
    <input type="text" class="form-control" name="numero" id="numero0" value="<?= $v('numero') ?>">
  </div>
</div>
<div class="row g-2">
  <div class="mb-3 col-md-4">
    <label class="form-label" for="complemento0">Complemento</label>
    <input type="text" class="form-control" name="complemento" id="complemento0" value="<?= $v('complemento') ?>">
  </div>
  <div class="mb-3 col-md-3">
    <label class="form-label" for="bairro0">Bairro</label>
    <input type="text" class="form-control" name="bairro" id="bairro0" value="<?= $v('bairro') ?>">
  </div>
  <div class="mb-3 col-md-3">
    <label class="form-label" for="cidade0">Cidade</label>
    <input type="text" class="form-control" name="cidade" id="cidade0" value="<?= $v('cidade') ?>">
  </div>
  <div class="mb-3 col-md-2">
    <label class="form-label" for="uf0">UF</label>
    <input type="text" class="form-control" name="uf" id="uf0" maxlength="2" value="<?= $v('uf') ?>">
  </div>
</div>
