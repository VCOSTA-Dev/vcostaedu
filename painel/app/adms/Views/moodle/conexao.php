<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Conectar ao Moodle (Onda 3, Etapa 8). A chave nunca aparece: só os 4 últimos caracteres. */
$d = $this->dados;
$c = $d['conexao'];
$s = $d['saude'];
$t = $d['teste'];
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$sel = fn($campo, $valor) => (string) ($c[$campo] ?? '') === (string) $valor ? ' checked' : '';
$data = fn($x) => $x ? date('d/m/Y H:i', strtotime((string) $x)) : '—';
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Conectar ao Moodle<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if (!empty($d['demonstracao'])) : ?>
        <div class="alert alert-info" id="moodleDemonstracao">A demonstração está ligada: <?= $d['conectado'] ? 'o que for enviado ao Moodle são <b>dados fictícios</b>.' : 'nada está conectado ao Moodle.' ?></div>
      <?php endif; ?>

      <?php if ($t) : ?>
        <?php if ($t['ok']) : ?>
          <div class="alert alert-success" id="testeOk"><i class="fas fa-circle-check me-1"></i><b>Conectado ao Moodle "<?= $h($t['site']) ?>"</b> (versão <?= $h($t['versao']) ?>), em <?= htmlspecialchars((string) ((int) $t['ms']), ENT_QUOTES, 'UTF-8') ?> ms. As <?= count(\VcMoodle::FUNCOES) ?> funções estão no serviço do VCOSTAEDU. Os envios <?= htmlspecialchars((string) (($c['envio'] ?? 'automatico') === 'automatico' ? 'começam sozinhos, a cada mudança' : 'saem pelo botão, em Envios ao Moodle'), ENT_QUOTES, 'UTF-8') ?>.</div>
        <?php else : [$oque, $fazer] = \VcMoodle::explicar($t['codigo'] ?: 'nao_conectado'); ?>
          <div class="alert alert-danger" id="testeFalhou" data-codigo="<?= $h($t['codigo']) ?>"><b>A conexão não deu certo.</b> <?= $h($oque) ?><br><b>O que fazer:</b> <?= $h($fazer) ?>
            <?php if ($t['faltam']) : ?><br>Faltam no serviço: <?= $h(implode(', ', $t['faltam'])) ?>.<?php endif; ?></div>
        <?php endif; ?>
      <?php endif; ?>

      <div class="row row-cards">
        <div class="col-lg-8">
          <form method="POST" action="<?= URLADM ?>moodle/salvar" class="card" id="formMoodle" autocomplete="off">
            <div class="card-header"><h3 class="card-title">A conexão</h3>
              <div class="card-actions"><span class="badge <?= htmlspecialchars((string) ($d['conectado'] ? 'bg-success-lt' : 'bg-secondary-lt'), ENT_QUOTES, 'UTF-8') ?>" id="estadoConexao"><?= htmlspecialchars((string) ($d['conectado'] ? 'Conectado' : 'Não conectado'), ENT_QUOTES, 'UTF-8') ?></span></div></div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label required" for="url">Endereço do Moodle</label>
                <input type="url" class="form-control" id="url" name="url" value="<?= $h($c['url'] ?? '') ?>" placeholder="https://moodle.suainstituicao.edu.br" required maxlength="255">
                <div class="form-hint">A página inicial do Moodle, com https://. Endereço sem https:// só numa instalação local.</div>
              </div>
              <div class="mb-3">
                <label class="form-label<?= htmlspecialchars((string) ($d['temChave'] ? '' : ' required'), ENT_QUOTES, 'UTF-8') ?>" for="chave">Chave do serviço</label>
                <input type="password" class="form-control" id="chave" name="chave" value="" autocomplete="new-password" placeholder="<?= $d['temChave'] ? 'guardada (termina em ' . $h($c['chave_fim']) . ') — em branco, mantém' : 'cole a chave do Moodle' ?>">
                <div class="form-hint">A chave fica cifrada no banco e não aparece mais inteira em lugar nenhum.</div>
              </div>

              <h4 class="mt-4">As escolhas da instituição</h4>
              <div class="mb-3">
                <div class="form-label">Disciplina no Moodle</div>
                <label class="form-check"><input class="form-check-input" type="radio" name="disciplina_modo" value="criar"<?= $sel('disciplina_modo', 'criar') ?>><span class="form-check-label"><b>Criar pelo VCOSTAEDU</b> — cada disciplina ofertada vira uma disciplina nova no Moodle, na categoria abaixo.</span></label>
                <label class="form-check"><input class="form-check-input" type="radio" name="disciplina_modo" value="vincular"<?= $sel('disciplina_modo', 'vincular') ?>><span class="form-check-label"><b>Vincular a uma que já existe</b> — na tela de cada disciplina, você informa qual é a disciplina do Moodle.</span></label>
              </div>
              <div class="row g-2 mb-3">
                <div class="col-md-6">
                  <label class="form-label" for="categoria_id">Categoria onde as disciplinas são criadas</label>
                  <select class="form-select" id="categoria_id" name="categoria_id" onchange="document.getElementById('categoria_nome').value = this.options[this.selectedIndex].text">
                    <option value="0">— escolha —</option>
                    <?php foreach ($d['categorias'] as $id => $nome) : ?><option value="<?= (int) $id ?>"<?= htmlspecialchars((string) ((int) ($c['categoria_id'] ?? 0) === (int) $id ? ' selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($nome) ?></option><?php endforeach; ?>
                    <?php if (!empty($c['categoria_id']) && !isset($d['categorias'][(int) $c['categoria_id']])) : ?><option value="<?= htmlspecialchars((string) ((int) $c['categoria_id']), ENT_QUOTES, 'UTF-8') ?>" selected><?= $h($c['categoria_nome'] ?: 'a categoria escolhida') ?></option><?php endif; ?>
                  </select>
                  <input type="hidden" id="categoria_nome" name="categoria_nome" value="<?= $h($c['categoria_nome'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="categoria_nova">Ou crie uma categoria nova no Moodle</label>
                  <input type="text" class="form-control" id="categoria_nova" name="categoria_nova" maxlength="255" placeholder="o nome da categoria"<?= htmlspecialchars((string) ($d['conectado'] ? '' : ' disabled'), ENT_QUOTES, 'UTF-8') ?>>
                  <?php if (!$d['conectado']) : ?><div class="form-hint">Depois de conectar.</div><?php endif; ?>
                </div>
              </div>
              <div class="mb-3">
                <div class="form-label">Trancamento ou cancelamento da matrícula</div>
                <label class="form-check"><input class="form-check-input" type="radio" name="trancamento" value="suspender"<?= $sel('trancamento', 'suspender') ?>><span class="form-check-label"><b>Suspender a inscrição</b> nas disciplinas do Moodle — o aluno não entra, e tudo volta se a matrícula for reativada.</span></label>
                <label class="form-check"><input class="form-check-input" type="radio" name="trancamento" value="remover"<?= $sel('trancamento', 'remover') ?>><span class="form-check-label"><b>Remover a inscrição</b> — o aluno sai das disciplinas do Moodle.</span></label>
                <label class="form-check"><input class="form-check-input" type="radio" name="trancamento" value="nada"<?= $sel('trancamento', 'nada') ?>><span class="form-check-label"><b>Não fazer nada</b> no Moodle.</span></label>
              </div>
              <div class="mb-3">
                <div class="form-label">Professor da disciplina</div>
                <label class="form-check"><input class="form-check-input" type="radio" name="professor_enviar" value="1"<?= $sel('professor_enviar', '1') ?>><span class="form-check-label"><b>Inscrever no Moodle</b> o professor indicado na disciplina (o coordenador do curso não é enviado)…</span></label>
                <div class="ms-4 mb-1">
                  <label class="form-check form-check-inline"><input class="form-check-input" type="radio" name="professor_papel" value="edicao"<?= $sel('professor_papel', 'edicao') ?>><span class="form-check-label">como professor com edição</span></label>
                  <label class="form-check form-check-inline"><input class="form-check-input" type="radio" name="professor_papel" value="sem_edicao"<?= $sel('professor_papel', 'sem_edicao') ?>><span class="form-check-label">sem edição</span></label>
                </div>
                <label class="form-check"><input class="form-check-input" type="radio" name="professor_enviar" value="0"<?= $sel('professor_enviar', '0') ?>><span class="form-check-label"><b>Não inscrever</b> — os professores são cuidados direto no Moodle.</span></label>
              </div>
              <div class="mb-1">
                <div class="form-label">Envio</div>
                <label class="form-check"><input class="form-check-input" type="radio" name="envio" value="automatico"<?= $sel('envio', 'automatico') ?>><span class="form-check-label"><b>Automático</b>, a cada mudança (depois de um teste que deu certo).</span></label>
                <label class="form-check"><input class="form-check-input" type="radio" name="envio" value="botao"<?= $sel('envio', 'botao') ?>><span class="form-check-label"><b>Só pelo botão</b> — as mudanças ficam na fila até alguém clicar em "Enviar tudo o que falta".</span></label>
              </div>
              <?php $modoEmail = \App\adms\Models\moodle\AdmsMoodle::emailModo(); $dominio = \App\adms\Models\moodle\AdmsMoodle::parametro('moodle.email_dominio', '');
                $exemplo = '260101@' . ($dominio !== '' ? $dominio : 'alunos.suaescola.edu.br'); ?>
              <div class="mt-3" id="emailModo" data-modo="<?= $h($modoEmail) ?>">
                <div class="form-label">O e-mail do aluno no Moodle</div>
                <div class="form-hint mb-2">O Moodle não aceita conta sem e-mail, e cada conta é de uma pessoa só. O e-mail da escola é feito da matrícula no domínio abaixo (por exemplo, <code id="exemploEmail"><?= $h($exemplo) ?></code>).</div>
                <label class="form-check"><input class="form-check-input" type="radio" name="email_modo" value="escola" id="emailModoEscola"<?= $modoEmail === 'escola' ? ' checked' : '' ?>><span class="form-check-label"><b>Sempre o e-mail da escola</b> — todo aluno vai com o endereço da escola, tenha ou não e-mail no cadastro.</span></label>
                <label class="form-check"><input class="form-check-input" type="radio" name="email_modo" value="aluno_ou_escola" id="emailModoAlunoOuEscola"<?= $modoEmail === 'aluno_ou_escola' ? ' checked' : '' ?>><span class="form-check-label"><b>O e-mail do aluno, ou o da escola</b> — o do cadastro do aluno; sem ele, ou se outro aluno tem o mesmo (irmãos com o e-mail da família), o da escola.</span></label>
                <label class="form-check"><input class="form-check-input" type="radio" name="email_modo" value="aluno" id="emailModoAluno"<?= $modoEmail === 'aluno' ? ' checked' : '' ?>><span class="form-check-label"><b>Só o e-mail do aluno</b> — sem e-mail no cadastro, ou com o mesmo de outro aluno, o aluno fica fora do Moodle, com o motivo em Envios ao Moodle.</span></label>
                <div class="alert alert-info mt-2 mb-2" id="avisoEmailEscola">O endereço da escola <b>não recebe mensagens</b>: o Moodle não consegue mandar nada a ele, e o "Esqueci a senha" do Moodle não funciona para ele. O aluno entra no Moodle <b>pelo portal</b> (o botão "Entrar no Moodle"), sem senha do Moodle. O endereço não é gravado no cadastro do aluno. Se o domínio mudar, os alunos que o sistema criou no Moodle vão com o endereço novo; a conta que já existia lá fica com o e-mail dela.</div>
                <div style="max-width: 26rem"><label class="form-label" for="emailDominio">Domínio do e-mail da escola</label>
                  <input type="text" class="form-control" name="email_dominio" id="emailDominio" value="<?= $h($dominio) ?>" placeholder="alunos.suaescola.edu.br" maxlength="190"></div>
              </div>
            </div>
            <div class="card-footer d-flex flex-wrap gap-2">
              <button type="submit" class="btn btn-primary" id="salvarMoodle"><i class="fas fa-floppy-disk"></i> Salvar</button>
              <button type="submit" class="btn btn-outline-primary" id="testarMoodle" formaction="<?= URLADM ?>moodle/testar" formnovalidate><i class="fas fa-plug-circle-check"></i> Testar conexão</button>
              <?php if ($d['temChave']) : ?>
                <button type="submit" class="btn btn-outline-danger ms-auto" id="desconectarMoodle" formaction="<?= URLADM ?>moodle/desconectar" formnovalidate data-vc-confirma="Desconectar do Moodle?" data-vc-confirma-extra="A chave é apagada e os envios param; o que já está ligado continua ligado."><i class="fas fa-plug-circle-xmark"></i> Desconectar</button>
              <?php endif; ?>
            </div>
          </form>
        </div>

        <div class="col-lg-4">
          <div class="card mb-3" id="saudeMoodle">
            <div class="card-header"><h3 class="card-title">A saúde da conexão</h3></div>
            <div class="card-body">
              <dl class="row mb-0">
                <dt class="col-7">Última chamada que deu certo</dt><dd class="col-5" id="ultimaOk"><?= $h($data($s['ultima_ok'])) ?></dd>
                <dt class="col-7">Envios pendentes</dt><dd class="col-5" id="saudePendentes"><?= htmlspecialchars((string) ((int) $s['pendentes']), ENT_QUOTES, 'UTF-8') ?></dd>
                <dt class="col-7">Envios que falharam</dt><dd class="col-5" id="saudeFalhas"><?= htmlspecialchars((string) ((int) $s['falhas']), ENT_QUOTES, 'UTF-8') ?></dd>
                <?php if (!empty($c['site_nome'])) : ?><dt class="col-7">O Moodle</dt><dd class="col-5"><?= $h($c['site_nome']) ?> <span class="text-secondary"><?= $h($c['site_versao']) ?></span></dd><?php endif; ?>
              </dl>
              <a href="<?= URLADM ?>moodle-envios/listar" class="btn btn-outline-secondary btn-sm mt-3">Ver os envios ao Moodle</a>
            </div>
          </div>
          <div class="card" id="passoAPasso">
            <div class="card-header"><h3 class="card-title">Ainda não instalou o plugin?</h3></div>
            <div class="card-body">
              <ol class="mb-0 ps-3">
                <li class="mb-2">No Moodle, como administrador do site, instale o plugin <b>local_vcostaedu</b> (Administração do site → Plugins → Instalar plugins).</li>
                <li class="mb-2">No Moodle, abra <b>Conectar ao VCOSTAEDU</b> (a página que o plugin cria em Administração do site).</li>
                <li class="mb-2">Copie o <b>endereço</b> e a <b>chave</b> que aparecem lá.</li>
                <li>Cole aqui, clique em <b>Salvar</b> e depois em <b>Testar conexão</b>.</li>
              </ol>
            </div>
          </div>
        </div>
      </div>

      <div class="card mt-3" id="glossarioMoodle">
        <div class="card-header"><h3 class="card-title">Quando algo não vai: o que cada aviso quer dizer</h3></div>
        <div class="table-responsive">
          <table class="table table-vcenter card-table">
            <thead><tr><th>O que aconteceu</th><th>O que fazer</th></tr></thead>
            <tbody>
              <?php foreach (\VcMoodle::GLOSSARIO as $cod => [$oque, $fazer]) : ?>
                <tr data-codigo="<?= $h($cod) ?>"><td><?= $h($oque) ?></td><td><?= $h($fazer) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer text-secondary small">Com o Moodle, o problema costuma ser configuração do lado dele: os serviços web desligados, a chave trocada, uma função fora do serviço ou o e-mail do Moodle.</div>
      </div>
    </div>
  </section>
</div>
