<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/*
 * Onda 2, Etapa 4: os cartões do começo, na Página Inicial do Administrador.
 *   - "Como você quer começar?" — enquanto a escolha não foi feita, a demonstração não está ligada e a instalação
 *     não tem dados da instituição;
 *   - "Conheça o sistema" — com a demonstração ligada: o que experimentar em cada perfil e os usuários fictícios;
 *   - "Primeiros passos" — até concluir ou dispensar.
 */
use App\adms\Models\helper\AdmsAmbiente;

if (!\App\adms\Models\helper\AdmsNivel::ehAdministrador() || !\App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0008')) {
  return;
}
$hE = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$demo = AdmsAmbiente::demonstracaoAtiva();
$dadosInst = \App\adms\Controllers\Demonstracao::dadosDaInstituicao();
if (!$demo && AdmsAmbiente::estado('comeco.escolhido') === null && $dadosInst['cursos'] + $dadosInst['alunos'] === 0) : ?>
  <div class="card mb-3 border-primary" id="cartaoComecar">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
      <div class="flex-fill">
        <h3 class="card-title mb-1"><i class="fas fa-flag-checkered me-1"></i> Como você quer começar?</h3>
        <div class="text-secondary">Conheça o sistema com uma instituição fictícia completa, ou comece agora pelos dados da sua instituição.</div>
      </div>
      <a href="<?= URLADM ?>demonstracao/comecar" class="btn btn-outline-primary" id="cartaoConhecer"><i class="fas fa-flask"></i> Conhecer com dados de demonstração</a>
      <a href="<?= URLADM ?>demonstracao/comecar?escolha=instituicao" class="btn btn-primary" id="cartaoConfigurar"><i class="fas fa-building"></i> Configurar a minha instituição agora</a>
    </div>
  </div>
<?php endif;
if ($demo) :
  $cred = json_decode((string) AdmsAmbiente::estado('demonstracao.credenciais', '{}'), true) ?: [];
  $escolaDemo = \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola();
  $perfis = [
    'direcao.demo' => ['Direção acadêmica', 'Abra os relatórios de todos os cursos e a auditoria de notas e faltas; indique um coordenador na tela do curso. Nada se altera em nota e falta.'],
    'supervisao.demo' => ['Supervisão de secretaria', 'Faça o que a secretaria faz e as ações sensíveis: reabrir uma disciplina encerrada, excluir ' . ($escolaDemo ? 'uma aula' : 'um encontro') . ' da chamada.'],
    'secretaria.demo' => ['Secretaria', $escolaDemo /* D-115: o histórico escolar da demonstração do colégio, pronto para emitir */
      ? 'Cadastre um aluno e enturme. Em Histórico escolar, busque o Rui Fictício Moura e emita o histórico escolar do Fundamental pela prévia; o do Paulo Hipotético Lima já está emitido, com o código para a conferência pública.'
      : 'Cadastre um aluno, vincule ao curso, enturme nas disciplinas e emita um histórico.'],
    'coord.demo' => ['Coordenação de curso', 'Veja as disciplinas, as notas e os relatórios só do curso que coordena; tente abrir o outro curso pelo endereço.'],
    'prof.laura.demo' => ['Professor', $escolaDemo /* D-104 (D3): a Matemática das turmas dela já está na chamada */
      ? 'Lance uma nota na tela das notas, faça a chamada de uma aula na Matemática de uma das suas turmas (em "O meu dia") e veja o histórico da nota.'
      : 'Lance uma nota na tela das notas, faça a chamada de um encontro e veja o histórico da nota.'],
  ];
  /* D-125 (1.102.1, C3.2): na escola, as palavras da escola — segmento (não curso), a coordenação de segmento e "as
     alterações da nota" (o "histórico" fica para o histórico escolar) */
  if ($escolaDemo) {
    $perfis['direcao.demo'] = ['Direção acadêmica', 'Abra os relatórios de todos os segmentos e a auditoria de notas e faltas; indique um coordenador na tela do segmento. Nada se altera em nota e falta.'];
    $perfis['coord.demo'] = ['Coordenação de segmento', 'Veja as disciplinas, as notas e os relatórios só do segmento que coordena; tente abrir o outro segmento pelo endereço.'];
    $perfis['prof.laura.demo'][1] = 'Lance uma nota na tela das notas, faça a chamada de uma aula na Matemática de uma das suas turmas (em "O meu dia") e veja as alterações da nota.';
  }
  /* Fechamento (D-106): no curso livre, o que cada perfil experimenta é o que existe nele */
  if (\App\adms\Models\helper\AdmsTipoInstituicao::ehLivre()) {
    $perfis = [
      'direcao.demo' => ['Direção acadêmica', 'Abra os relatórios do curso e a lista de usuários. Nada se altera.'],
      'supervisao.demo' => ['Supervisão de secretaria', 'Faça o que a secretaria faz e veja quem está na turma aberta.'],
      'secretaria.demo' => ['Secretaria', 'Abra a turma aberta, veja as vagas e os quatro módulos, cadastre um aluno e ponha na turma (com a matrícula ligada).'],
      'coord.demo' => ['Coordenação de curso', 'Veja o curso, a turma e os módulos.'],
      'prof.marcos.demo' => ['Professor', 'O professor dos quatro módulos: as disciplinas dele vão para o Moodle.'],
    ];
  }
  $aluno = array_key_first($cred['portal'] ?? []) ?? null;
  /* Onda 8, correção, Parte 1.3 (D-73): todas as senhas do cartão do mesmo jeito — escondidas, com "Mostrar" e "Copiar";
     "Copiar" copia a senha verdadeira mesmo escondida (antes, só as dos responsáveis vinham escondidas, e a máscara
     "••••••••" chegou a ser usada como senha) */
  $senhaDemo = static fn(string $s): string => '<code class="vc-senha-oculta" data-senha="' . $hE($s) . '">••••••••</code>'
    . ' <button type="button" class="btn btn-link btn-sm p-0 ms-1 vc-mostrar-senha">Mostrar</button>'
    . ' <button type="button" class="btn btn-link btn-sm p-0 ms-1 vc-copiar-senha" title="Copiar a senha">Copiar</button>';
  ?>
  <div class="card mb-3" id="cartaoConheca">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-flask me-1"></i> Conheça o sistema</h3></div>
    <div class="card-body">
      <p class="text-secondary">A demonstração tem um usuário de cada perfil. Entre com cada um (numa janela anônima, para não sair da sua conta) e experimente. Você é o <strong>Administrador</strong>: tudo, inclusive a equipe, a configuração e a manutenção (o que cada perfil pode fazer está em Equipe → Perfis de acesso).</p>
      <div class="table-responsive">
        <table class="table table-sm table-vcenter mb-0" id="tabelaPerfisDemo">
          <thead><tr><th>Perfil</th><th>Usuário</th><th>Senha</th><th>O que experimentar</th></tr></thead>
          <tbody>
          <?php foreach ($perfis as $login => [$nome, $tente]) : if (!isset($cred['painel'][$login])) { continue; } ?>
            <tr><td><?= $hE($nome) ?></td><td><code><?= $hE($login) ?></code></td><td class="text-nowrap"><?= $senhaDemo((string) $cred['painel'][$login]) ?></td><td class="text-secondary"><?= $hE($tente) ?></td></tr>
          <?php endforeach; ?>
          <?php if ($aluno) : ?>
            <tr><td>Aluno (portal)</td><td><code><?= $hE($aluno) ?></code> <span class="text-secondary small">no <a href="<?= URL ?>portal/">portal</a></span></td><td class="text-nowrap"><?= $senhaDemo((string) $cred['portal'][$aluno]) ?></td><td class="text-secondary"><?= \App\adms\Models\helper\AdmsTipoInstituicao::ehLivre() ? 'Entre pelo e-mail (' . $hE('aluno.' . (int) $aluno . '@exemplo.invalid') . ') ou pela matrícula e clique em Entrar no Moodle: a Página Inicial, o Moodle e a Ajuda.' : (\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola()
              ? 'Veja o boletim, as notas das atividades, a frequência e o horário, e emita o atestado de matrícula.' /* Onda 8 (D-69): os alunos do colégio fictício ganharam o CPF e a previsão de conclusão, que o atestado pede */
              : 'Veja o boletim, as notas das atividades e emita o atestado de matrícula; confira o atestado na verificação pública.') ?></td></tr>
          <?php endif; ?>
          <?php /* Onda 7: os responsáveis fictícios (só na escola), com a senha só ao clicar em "Mostrar" */
          $familiaDemo = [
            'helena.ribeiro@exemplo.invalid' => 'Dois filhos: escolha um, troque de filho e de ano, veja o boletim, a frequência e a turma, e emita o atestado de matrícula.',
            'rogerio.sa@exemplo.invalid' => 'O financeiro de um aluno: o portal mostra o acompanhamento escolar dele.',
            'dora.ribeiro@exemplo.invalid' => 'O acesso acadêmico adicional: vê o acompanhamento de 2026, e só desse aluno.',
          ];
          foreach ($cred['familia'] ?? [] as $email => $senhaF) : ?>
            <tr class="vc-demo-familia" data-usuario="<?= $hE($email) ?>"><td>Responsável (portal)</td><td><code><?= $hE($email) ?></code> <span class="text-secondary small">no <a href="<?= URL ?>portal/">portal</a></span></td>
              <td class="text-nowrap"><?= $senhaDemo((string) $senhaF) ?></td>
              <td class="text-secondary"><?= $hE($familiaDemo[$email] ?? 'Entre no portal como responsável.') ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
        <script>
          document.querySelectorAll('#tabelaPerfisDemo .vc-mostrar-senha').forEach(function (b) {
            b.addEventListener('click', function () {
              var c = b.parentNode.querySelector('.vc-senha-oculta');
              var aberta = c.textContent === c.getAttribute('data-senha');
              c.textContent = aberta ? '••••••••' : c.getAttribute('data-senha');
              b.textContent = aberta ? 'Mostrar' : 'Esconder';
            });
          });
          /* "Copiar": a senha verdadeira (o data-senha), esteja ela à mostra ou escondida */
          document.querySelectorAll('#tabelaPerfisDemo .vc-copiar-senha').forEach(function (b) {
            b.addEventListener('click', function () {
              var senha = b.parentNode.querySelector('.vc-senha-oculta').getAttribute('data-senha');
              var avisar = function (txt) { b.textContent = txt; setTimeout(function () { b.textContent = 'Copiar'; }, 2000); };
              var antigo = function () {
                var t = document.createElement('textarea');
                t.value = senha; t.setAttribute('readonly', ''); t.style.position = 'fixed'; t.style.opacity = '0';
                document.body.appendChild(t); t.select();
                var ok = false;
                try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
                t.remove();
                avisar(ok ? 'Copiada' : 'Não copiou');
              };
              if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(senha).then(function () { avisar('Copiada'); }, antigo);
              } else {
                antigo();
              }
            });
          });
        </script>
    </div>
    <div class="card-footer text-end"><a href="<?= URLADM ?>demonstracao/instituicao" class="btn btn-warning btn-sm" id="cartaoComecarInstituicao">Começar com os dados da minha instituição</a></div>
  </div>
<?php endif;
if (\App\adms\Models\helper\AdmsPrimeirosPassos::ativo()) :
  $pp = \App\adms\Models\helper\AdmsPrimeirosPassos::progresso(); ?>
  <div class="card mb-3" id="cartaoPrimeirosPassos">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
      <div class="flex-fill">
        <h3 class="card-title mb-1"><i class="fas fa-shoe-prints me-1"></i> Primeiros passos — <?= htmlspecialchars((string) ((int) $pp['feitas']), ENT_QUOTES, 'UTF-8') ?> de <?= htmlspecialchars((string) ((int) $pp['total']), ENT_QUOTES, 'UTF-8') ?></h3>
        <div class="text-secondary"><?= $pp['proxima'] ? $hE($pp['proxima']['convite']) : 'Retome uma fase pulada quando quiser.' ?></div>
      </div>
      <a href="<?= URLADM ?>primeiros-passos/index" class="btn btn-primary" id="cartaoContinuar">Continuar</a>
    </div>
  </div>
<?php endif; ?>
