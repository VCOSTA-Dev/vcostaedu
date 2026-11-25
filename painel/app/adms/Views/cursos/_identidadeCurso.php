<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E1a (D-340): como o curso sai nos documentos. Incluído por cadCursos.php e editCursos.php, com $valorForm. */
$idc = fn($c) => htmlspecialchars((string) ($valorForm[$c] ?? ''), ENT_QUOTES, 'UTF-8');
$IC = \App\adms\Models\cursos\AdmsIdentidadeCurso::class;
?>
<?php /* E2 (D-360): frequência mínima do curso; vazio = a da instituição */
$Calc = \App\adms\Models\notas\AdmsCalculoNotas::class;
$freqInst = $Calc::numero(\App\adms\Models\helper\AdmsParametro::regrasNotas()['frequenciaMinima']);
$freqCurso = (string) ($valorForm['frequencia_minima'] ?? '') !== '' ? $Calc::numero(str_replace(',', '.', (string) $valorForm['frequencia_minima'])) : ''; ?>
<div class="row g-2">
  <div class="mb-3 col-md-4">
    <label class="form-label" for="frequencia_minima">Frequência mínima do curso (%) <small class="text-muted">(opcional)</small></label>
    <input type="text" inputmode="decimal" class="form-control" name="frequencia_minima" id="frequencia_minima" maxlength="6" value="<?= htmlspecialchars($freqCurso, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($freqInst, ENT_QUOTES, 'UTF-8') ?> (a da instituição)">
    <small class="text-muted">Vazio = a da instituição (<?= htmlspecialchars($freqInst, ENT_QUOTES, 'UTF-8') ?>%). A disciplina encerrada guarda o mínimo que valia no encerramento.</small>
  </div>
</div>
<h6 class="mt-2 mb-1 fw-bold">Nos documentos <small class="text-muted fw-normal">(histórico, atestado; todos opcionais)</small></h6>
<div class="row g-2">
  <div class="mb-3 col-md-4">
    <label class="form-label" for="nivel">Nível</label>
    <input type="text" class="form-control" name="nivel" id="nivel" list="listaNiveis" maxlength="<?= $IC::CAMPOS['nivel'][1] ?>" value="<?= $idc('nivel') ?>" placeholder="Ex.: Especialização">
    <datalist id="listaNiveis"><?php foreach ($IC::NIVEIS as $nv) : ?><option value="<?= htmlspecialchars($nv, ENT_QUOTES, 'UTF-8') ?>"><?php endforeach; ?></datalist>
  </div>
  <div class="mb-3 col-md-2">
    <label class="form-label" for="abreviatura">Sigla</label>
    <input type="text" class="form-control" name="abreviatura" id="abreviatura" maxlength="<?= $IC::CAMPOS['abreviatura'][1] ?>" value="<?= $idc('abreviatura') ?>" placeholder="Ex.: PGXYZ">
  </div>
  <div class="mb-3 col-md-6">
    <label class="form-label" for="nome_extenso">Nome por extenso <small class="text-muted">(no texto do documento)</small></label>
    <input type="text" class="form-control" name="nome_extenso" id="nome_extenso" maxlength="<?= $IC::CAMPOS['nome_extenso'][1] ?>" value="<?= $idc('nome_extenso') ?>" placeholder="Ex.: Gestão de Projetos">
  </div>
</div>
<div class="row g-2">
  <div class="mb-3 col-md-12">
    <label class="form-label" for="cabecalho_documento">Linha do curso no cabeçalho</label>
    <input type="text" class="form-control" name="cabecalho_documento" id="cabecalho_documento" maxlength="<?= $IC::CAMPOS['cabecalho_documento'][1] ?>" value="<?= $idc('cabecalho_documento') ?>" placeholder="Ex.: CURSO DE PÓS-GRADUAÇÃO LATO SENSU EM GESTÃO PÚBLICA">
    <small class="text-muted">Sai por último no bloco "Cabeçalho institucional", depois das linhas da instituição, escrita como está aqui.</small>
  </div>
</div>
