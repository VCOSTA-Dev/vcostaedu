<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$campo = $this->dados['campoLogin'] ?? 'ra';
$horas = (int) ($this->dados['horasLink'] ?? 48);
$pendencias = $this->dados['pendencias'] ?? [];
$rot = fn($c) => \App\adms\Models\helper\AdmsParametro::rotuloCampoLogin($c);
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Configurações da instituição<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <?php if ($pendencias) { ?>
        <div class="card shadow">
          <div class="card-header"><h3 class="card-title">Alunos ativos que impedem entrar com <?= htmlspecialchars($rot($this->dados['pendenciasCampo'] ?? '')) ?></h3></div>
          <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
              <thead><tr><th>RA</th><th>Nome</th><th>Problema</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($pendencias as $p) { ?>
                  <tr>
                    <td><?= htmlspecialchars((string) $p['rm']) ?></td>
                    <td><?= htmlspecialchars((string) $p['nome']) ?></td>
                    <td><?= htmlspecialchars((string) $p['problema']) ?></td>
                    <td><a href="<?= htmlspecialchars((string) (URLADM . 'academicos/editar/' . (int) $p['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-warning">Editar aluno</a></td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php } ?>
      <div class="card shadow">
        <form method="POST" action="<?= URLADM . 'parametros/salvar' ?>" novalidate>
          <div class="card-body">
            <h5>Portal do aluno</h5>
            <div class="row g-2">
              <div class="mb-3 col-md-6">
                <?php if (\App\adms\Models\helper\AdmsParametro::naEscola()) : /* Onda 8 (D-67) */ ?>
                <label class="form-label">O aluno entra no portal com</label>
                <p class="form-control-plaintext py-1 mb-0" id="loginEscola"><b>o número de matrícula</b></p>
                <small class="form-text text-muted">Na escola, o login do aluno é sempre o número de matrícula; o e-mail e o CPF do cadastro do aluno não servem para entrar. O responsável entra com o CPF ou o e-mail que escolheu no convite.</small>
                <?php else : ?>
                <label class="form-label" for="portal_login_campo">O aluno entra no portal com</label>
                <select name="portal_login_campo" id="portal_login_campo" class="form-select">
                  <?php foreach (['ra', 'cpf', 'email'] as $c) { ?>
                    <option value="<?= htmlspecialchars((string) ($c), ENT_QUOTES, 'UTF-8') ?>" <?= $c === $campo ? 'selected' : '' ?>><?= $rot($c) ?></option>
                  <?php } ?>
                </select>
                <small class="form-text text-muted">Hoje: <b><?= $rot($campo) ?></b>. Para trocar, todo aluno ativo precisa ter o campo escolhido preenchido e sem repetição; se não tiver, a troca é recusada com a lista do que resolver.</small>
                <?php endif; ?>
              </div>
              <div class="mb-3 col-md-6">
                <label class="form-label" for="portal_senha_link_horas">Validade do link de definição de senha (horas)</label>
                <input type="number" min="1" max="720" name="portal_senha_link_horas" id="portal_senha_link_horas" class="form-control" value="<?= htmlspecialchars((string) ($horas), ENT_QUOTES, 'UTF-8') ?>">
                <small class="form-text text-muted">Vale para o primeiro acesso e para o "esqueci a senha". O link é de uso único.</small>
              </div>
            </div>
            <h5 class="mt-3">Notas e frequência</h5>
            <?php $rg = $this->dados['regras'] ?? ['casas' => 1, 'mediaMinima' => '7.00', 'frequenciaMinima' => '75']; ?>
            <div class="row g-2">
              <div class="mb-3 col-md-8" id="regraMediaResumo"><?php /* 1.13.0 (D-398): casas, arredondamento e nota mínima são da REGRA de média */ ?>
                <label class="mb-0">Média: casas e nota mínima</label>
                <p class="form-control-plaintext py-1 mb-0"><?= htmlspecialchars((string) ((int) $rg['casas']), ENT_QUOTES, 'UTF-8') ?> casa<?= htmlspecialchars((string) ((int) $rg['casas'] > 1 ? 's' : ''), ENT_QUOTES, 'UTF-8') ?> · nota mínima <?= htmlspecialchars(str_replace('.', ',', (string) $rg['mediaMinima']), ENT_QUOTES, 'UTF-8') ?>
                  <a href="<?= URLADM ?>regra-media/listar" class="btn btn-sm btn-outline-info ms-2" id="linkRegraMedia"><i class="fas fa-calculator"></i> Regras de média</a></p>
                <small class="form-text text-muted">Componentes, pesos, arredondamento, casas e nota mínima ficam nas <b>Regras de média</b> (com o simulador): estes valores são os da regra <b>Padrão da instituição</b>. Cada disciplina pode ter a sua regra (1.16.0).</small>
              </div>
              <div class="mb-3 col-md-4">
                <label class="form-label" for="frequencia_minima">Frequência mínima (%)</label>
                <input type="text" inputmode="decimal" name="frequencia_minima" id="frequencia_minima" class="form-control" value="<?= htmlspecialchars(str_replace('.', ',', (string) $rg['frequenciaMinima']), ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>
            <?php $parcial = \App\adms\Models\helper\AdmsParametro::mostrarMediaParcial(); ?>
            <div class="row g-2">
              <div class="mb-3 col-md-4">
                <label class="form-label" for="portal_media_parcial">Mostrar média parcial ao aluno</label>
                <select name="portal_media_parcial" id="portal_media_parcial" class="form-select">
                  <option value="1" <?= $parcial ? 'selected' : '' ?>>Sim</option>
                  <option value="0" <?= !$parcial ? 'selected' : '' ?>>Não</option>
                </select>
                <small class="form-text text-muted">Enquanto a disciplina está aberta, o boletim do portal mostra (ou não) a média parcial. Depois do encerramento, a média final e o resultado aparecem sempre.</small>
              </div>
              <?php /* D-103 (B7): o campo mostra o valor GRAVADO; na escola ele não aparece (as séries são sempre os módulos) */
                $usaMod = (\App\adms\Models\helper\AdmsParametro::ler('matriz.modulos', '0') ?? '0') === '1'; ?>
              <?php if (!\App\adms\Models\helper\AdmsParametro::naEscola()) : ?>
              <div class="mb-3 col-md-4">
                <label class="form-label" for="matriz_modulos">Organizar a matriz em módulos</label>
                <select name="matriz_modulos" id="matriz_modulos" class="form-select">
                  <option value="0" <?= !$usaMod ? 'selected' : '' ?>>Não — curso, grade e a lista de disciplinas</option>
                  <option value="1" <?= $usaMod ? 'selected' : '' ?>>Sim — as disciplinas ficam agrupadas em módulos</option>
                </select>
                <small class="form-text text-muted" id="dicaModulos">Com <b>Não</b>, módulo não aparece em nenhuma tela nem documento (matriz, oferta, vínculo, enturmação, boletim e histórico). Os dados já lançados não mudam.</small>
              </div>
              <?php endif; ?>
            </div>
            <?php /* E1b (D-355, D-357) e E2 (D-361): os textos que os documentos usam */
            $P = \App\adms\Models\helper\AdmsParametro::class; $tf = $P::textosFrequencia(); $tn = $P::formasNeutras();
            $ht = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8'); ?>
            <h5 class="mt-3" id="textosDocumentos">Textos dos documentos</h5>
            <p class="text-muted small mb-2">Usados pelas variáveis do editor de modelos (histórico e atestado). O que já foi emitido não muda.</p>
            <div class="row g-2">
              <div class="mb-3 col-md-2"><label class="form-label" for="frequencia_sigla_suficiente">Sigla: suficiente</label><input type="text" maxlength="10" name="frequencia_sigla_suficiente" id="frequencia_sigla_suficiente" class="form-control" value="<?= $ht($tf['siglaSuficiente']) ?>"></div>
              <div class="mb-3 col-md-4"><label class="form-label" for="frequencia_rotulo_suficiente">Rótulo: suficiente</label><input type="text" maxlength="60" name="frequencia_rotulo_suficiente" id="frequencia_rotulo_suficiente" class="form-control" value="<?= $ht($tf['rotuloSuficiente']) ?>"></div>
              <div class="mb-3 col-md-2"><label class="form-label" for="frequencia_sigla_insuficiente">Sigla: insuficiente</label><input type="text" maxlength="10" name="frequencia_sigla_insuficiente" id="frequencia_sigla_insuficiente" class="form-control" value="<?= $ht($tf['siglaInsuficiente']) ?>"></div>
              <div class="mb-3 col-md-4"><label class="form-label" for="frequencia_rotulo_insuficiente">Rótulo: insuficiente</label><input type="text" maxlength="60" name="frequencia_rotulo_insuficiente" id="frequencia_rotulo_insuficiente" class="form-control" value="<?= $ht($tf['rotuloInsuficiente']) ?>"></div>
            </div>
            <small class="form-text text-muted mt-n2 mb-2">A sigla sai no quadro do histórico (modelo com a frequência em "Sigla" ou "Os dois") e no boletim, ao lado do percentual, só com a disciplina encerrada.</small>
            <div class="row g-2">
              <div class="mb-3 col-md-4"><label class="form-label" for="aluno_rotulo_neutro">Forma neutra de ALUNA/ALUNO</label><input type="text" maxlength="60" name="aluno_rotulo_neutro" id="aluno_rotulo_neutro" class="form-control" value="<?= $ht($tn['rotulo']) ?>"></div>
              <div class="mb-3 col-md-5"><label class="form-label" for="aluno_matriculado_neutro">Forma neutra de matriculada/matriculado</label><input type="text" maxlength="60" name="aluno_matriculado_neutro" id="aluno_matriculado_neutro" class="form-control" value="<?= $ht($tn['matriculado']) ?>"></div>
              <div class="mb-3 col-md-3"><label class="form-label" for="aluno_artigo_neutro">Forma neutra de a/o</label><input type="text" maxlength="20" name="aluno_artigo_neutro" id="aluno_artigo_neutro" class="form-control" value="<?= $ht($tn['artigo']) ?>"></div>
            </div>
            <small class="form-text text-muted mt-n2 mb-2">Para o aluno com sexo "Outros". Feminino e Masculino usam ALUNA/ALUNO, matriculada/matriculado, a/o.</small>
            <?php /* E5 (D-314) */ $modoMf = $P::mediaFinalModo(); ?>
            <div class="row g-2">
              <div class="mb-3 col-md-6"><label class="form-label" for="documento_media_final">Média final do aluno (histórico FINAL)</label>
                <select name="documento_media_final" id="documento_media_final" class="form-select">
                  <?php foreach ($P::MEDIA_FINAL_MODOS as $k => $rot) : ?><option value="<?= $ht($k) ?>" <?= $modoMf === $k ? 'selected' : '' ?>><?= $ht($rot) ?></option><?php endforeach; ?>
                </select>
                <small class="form-text text-muted">Entram as disciplinas encerradas (a tentativa vigente). O TCC fica fora. No PARCIAL, a linha sai em branco.</small>
              </div>
            </div>
            <label class="mb-1">Situação da matrícula no documento</label>
            <div class="row g-2">
              <?php foreach (\App\adms\Models\parametros\AdmsParametrosInstituicao::situacoesMatricula() as $sm) : ?>
                <div class="mb-3 col-md-2"><label class="small mb-0" for="situacao_rotulo_<?= htmlspecialchars((string) ((int) $sm['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $ht($sm['nome']) ?></label><input type="text" maxlength="60" name="situacao_rotulo_<?= htmlspecialchars((string) ((int) $sm['id']), ENT_QUOTES, 'UTF-8') ?>" id="situacao_rotulo_<?= htmlspecialchars((string) ((int) $sm['id']), ENT_QUOTES, 'UTF-8') ?>" class="form-control" value="<?= $ht($sm['rotulo_documento'] ?? mb_strtoupper((string) $sm['nome'])) ?>"></div>
              <?php endforeach; ?>
            </div>
            <small class="form-text text-muted mt-n2 mb-2">Variável MATRICULA_SITUACAO_DOCUMENTO (ex.: "Cursando" sai "EM CURSO"). As situações em si não mudam.</small>
            <?php $lim = \App\adms\Models\helper\AdmsParametro::limitesIp(); $fx = \App\adms\Models\helper\AdmsParametro::LIMITES_IP; ?>
            <h5 class="mt-3" id="limitesIp">Limite de tentativas por IP</h5>
            <p class="text-muted small mb-2">Um IP pode ser de uma rede compartilhada (laboratório, escola): todos os acessos dele contam juntos. Passado o limite, esse IP espera 30 segundos, que dobram a cada nova falha, até 15 minutos. O limite por usuário (5 senhas erradas; 3 pedidos de link) não muda aqui.</p>
            <div class="row g-2">
              <div class="mb-3 col-md-4">
                <label class="form-label" for="limite_ip_login">Login: falhas por hora</label>
                <input type="number" min="<?= htmlspecialchars((string) ($fx['login']['min']), ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars((string) ($fx['login']['max']), ENT_QUOTES, 'UTF-8') ?>" name="limite_ip_login" id="limite_ip_login" class="form-control" value="<?= htmlspecialchars((string) ((int) $lim['login']), ENT_QUOTES, 'UTF-8') ?>">
                <small class="form-text text-muted">Painel e portal. De <?= htmlspecialchars((string) ($fx['login']['min']), ENT_QUOTES, 'UTF-8') ?> a <?= htmlspecialchars((string) ($fx['login']['max']), ENT_QUOTES, 'UTF-8') ?>; padrão <?= htmlspecialchars((string) ($fx['login']['padrao']), ENT_QUOTES, 'UTF-8') ?>.</small>
              </div>
              <div class="mb-3 col-md-4">
                <label class="form-label" for="limite_ip_esqueci">Esqueci a senha: pedidos por hora</label>
                <input type="number" min="<?= htmlspecialchars((string) ($fx['esqueci']['min']), ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars((string) ($fx['esqueci']['max']), ENT_QUOTES, 'UTF-8') ?>" name="limite_ip_esqueci" id="limite_ip_esqueci" class="form-control" value="<?= htmlspecialchars((string) ((int) $lim['esqueci']), ENT_QUOTES, 'UTF-8') ?>">
                <small class="form-text text-muted">De <?= htmlspecialchars((string) ($fx['esqueci']['min']), ENT_QUOTES, 'UTF-8') ?> a <?= htmlspecialchars((string) ($fx['esqueci']['max']), ENT_QUOTES, 'UTF-8') ?>; padrão <?= htmlspecialchars((string) ($fx['esqueci']['padrao']), ENT_QUOTES, 'UTF-8') ?>.</small>
              </div>
              <div class="mb-3 col-md-4">
                <label class="form-label" for="limite_ip_verificacao">Verificação pública: consultas a cada 10 minutos</label>
                <input type="number" min="<?= htmlspecialchars((string) ($fx['verificacao']['min']), ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars((string) ($fx['verificacao']['max']), ENT_QUOTES, 'UTF-8') ?>" name="limite_ip_verificacao" id="limite_ip_verificacao" class="form-control" value="<?= htmlspecialchars((string) ((int) $lim['verificacao']), ENT_QUOTES, 'UTF-8') ?>">
                <small class="form-text text-muted">De <?= htmlspecialchars((string) ($fx['verificacao']['min']), ENT_QUOTES, 'UTF-8') ?> a <?= htmlspecialchars((string) ($fx['verificacao']['max']), ENT_QUOTES, 'UTF-8') ?>; padrão <?= htmlspecialchars((string) ($fx['verificacao']['padrao']), ENT_QUOTES, 'UTF-8') ?>.</small>
              </div>
            </div>
            <?php /* Onda 8, correção, segunda leva, 2.7 (D-93): o limite de chamadas por minuto de cada chave da API */
            $fxApi = \App\adms\Models\helper\AdmsParametro::API_LIMITE; $apiLim = \App\adms\Models\helper\AdmsParametro::apiLimitePorMinuto(); ?>
            <h5 class="mt-3" id="limiteApi">Integrações: chamadas por minuto de cada chave da API</h5>
            <div class="row g-2">
              <div class="mb-3 col-md-4">
                <label class="form-label" for="api_limite_por_minuto">Chamadas por minuto, por chave</label>
                <input type="number" min="<?= htmlspecialchars((string) $fxApi['min'], ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars((string) $fxApi['max'], ENT_QUOTES, 'UTF-8') ?>" name="api_limite_por_minuto" id="api_limite_por_minuto" class="form-control" value="<?= htmlspecialchars((string) $apiLim, ENT_QUOTES, 'UTF-8') ?>">
                <small class="form-text text-muted">De <?= htmlspecialchars((string) $fxApi['min'], ENT_QUOTES, 'UTF-8') ?> a <?= htmlspecialchars((string) $fxApi['max'], ENT_QUOTES, 'UTF-8') ?>; padrão <?= htmlspecialchars((string) $fxApi['padrao'], ENT_QUOTES, 'UTF-8') ?>.</small>
              </div>
              <div class="mb-3 col-md-8">
                <p class="text-muted small mb-0" id="limiteApiQuando">Cada chave da API (Integrações → Chaves de API) pode fazer este número de chamadas num minuto; passado o limite, a API responde 429 ("limite excedido") e o outro sistema espera um minuto. <b>Quando aumentar:</b> quando um sistema ligado à API faz muitas chamadas de uma vez — o site de inscrições num período de matrícula (cada inscrição faz duas ou três chamadas), uma importação grande ou uma sincronização da noite — e o registro de chamadas (Integrações → Chamadas da API) mostra respostas 429. Aumente só o necessário: o limite protege a instalação de um sistema com defeito que chama sem parar.</p>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <input type="submit" name="SalvarParametros" value="Salvar" class="btn btn-primary botaoEnvia">
          </div>
        </form>
      </div>
    </div>
  </section>
</div>
