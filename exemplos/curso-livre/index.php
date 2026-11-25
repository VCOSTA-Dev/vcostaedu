<?php
/* A PÁGINA DE VENDA do curso livre (o site de exemplo de uma instituição fictícia). */
require __DIR__ . '/lib.php';
$erro = $_SESSION['erro'] ?? '';
$antes = $_SESSION['antes'] ?? [];
unset($_SESSION['erro'], $_SESSION['antes']);
$envio = bin2hex(random_bytes(12)); // um por formulário mostrado: o clique duplo manda o mesmo
topo('IA na prática para professores — ' . (cfg()['instituicao'] ?? 'Instituto Ventoalto'));
?>
<main>
  <section class="capa" aria-labelledby="titulo">
    <div class="capa-conteudo">
      <p class="selo">Curso livre · 10 horas, em quatro módulos</p>
      <h1 id="titulo">IA na prática para professores</h1>
      <p class="promessa">Planeje, corrija e dê retorno em menos tempo — com critério, e sem abrir mão do que só você sabe fazer em sala.</p>
      <a class="botao" href="#inscricao" id="comecarTopo">Começar agora, grátis</a>
      <p class="nota">O primeiro módulo é gratuito.</p>
    </div>
    <svg class="ondas" viewBox="0 0 400 140" aria-hidden="true" focusable="false"><path d="M0 120c70-90 190-90 260 0" /><path d="M60 120c50-60 140-60 190 0" /><path d="M120 120c30-34 80-34 110 0" /><path d="M180 120c60-110 170-110 220 0" /></svg>
  </section>

  <section class="bloco" aria-labelledby="paraQuem">
    <h2 id="paraQuem">Para quem é</h2>
    <p>Para professoras e professores da educação básica que já ouviram falar de inteligência artificial, talvez já tenham testado uma ferramenta, e querem usá-la no trabalho de verdade: no planejamento da semana, na correção da pilha de redações, no recado para a turma.</p>
    <p>Não precisa saber programar nem ter computador novo. Precisa de vontade de experimentar e de olhar com senso crítico para o que a máquina devolve.</p>
  </section>

  <section class="bloco" aria-labelledby="oQue">
    <h2 id="oQue">O que você aprende</h2>
    <ul class="lista">
      <li>Pedir à IA o que você precisa, com contexto e exemplos, e reconhecer quando a resposta não presta.</li>
      <li>Montar sequências de aula, atividades e rubricas a partir do seu currículo, não de um modelo genérico.</li>
      <li>Usar a IA para dar retorno mais rápido aos alunos, mantendo a decisão da nota com você.</li>
      <li>Combinar regras claras com a turma sobre o uso de IA nos trabalhos — e cuidar dos dados dos alunos.</li>
    </ul>
  </section>

  <section class="bloco" aria-labelledby="modulos">
    <h2 id="modulos">Os quatro módulos</h2>
    <ol class="modulos">
      <li><b>Primeiros passos com IA na sala de aula</b> <span class="gratis">grátis</span><br>O que a IA faz bem, o que ela inventa, e como pedir com clareza.</li>
      <li><b>Planejar aulas e atividades</b><br>Sequências, adaptações para quem precisa e rubricas que a turma entende.</li>
      <li><b>Avaliar e dar retorno</b><br>Comentários mais rápidos em produções escritas, sem terceirizar o julgamento.</li>
      <li><b>Ética, privacidade e autoria</b><br>O que pode, o que não pode e como conversar sobre isso com os alunos.</li>
    </ol>
  </section>

  <section class="bloco" aria-labelledby="perguntas">
    <h2 id="perguntas">Perguntas frequentes</h2>
    <details><summary>Preciso saber algo de tecnologia?</summary><p>Não. Se você usa e-mail e navegador, consegue acompanhar.</p></details>
    <details><summary>Como funciona o módulo gratuito?</summary><p>Ao se inscrever, você entra na turma e já pode começar pelo módulo 1, sem pagar nada. As condições dos módulos 2 a 4 a nossa equipe envia por e-mail.</p></details>
    <details><summary>Quando posso estudar?</summary><p>Quando quiser. São 10 horas no total, em quatro módulos, no seu ritmo, pelo ambiente de estudo on-line.</p></details>
    <details><summary>Como eu entro no curso?</summary><p>Logo depois da inscrição, você cria a sua senha e entra no ambiente de estudo, com o número de matrícula que aparece na tela.</p></details>
  </section>

  <section class="bloco inscricao" id="inscricao" aria-labelledby="tituloInscricao">
    <h2 id="tituloInscricao">Comece agora, grátis</h2>
    <?php if (trim((string) (cfg()['aviso'] ?? '')) !== '') : ?><p class="aviso-local" id="avisoLocal"><?= h(trim((string) cfg()['aviso'])) ?></p><?php endif; ?>
    <?php if ($erro !== '') : ?><p class="erro" role="alert" id="erroInscricao"><?= h($erro) ?></p><?php endif; ?>
    <form method="post" action="inscrever.php" id="formInscricao" novalidate>
      <input type="hidden" name="token" value="<?= h(token()) ?>">
      <input type="hidden" name="envio" value="<?= h($envio) ?>">
      <label for="nome">Seu nome</label>
      <input id="nome" name="nome" autocomplete="name" required maxlength="120" value="<?= h($antes['nome'] ?? '') ?>">
      <label for="email">Seu e-mail</label>
      <input id="email" name="email" type="email" autocomplete="email" required maxlength="190" value="<?= h($antes['email'] ?? '') ?>">
      <label for="celular">Seu celular, com DDD</label>
      <input id="celular" name="celular" type="tel" autocomplete="tel" inputmode="tel" required maxlength="20" value="<?= h($antes['celular'] ?? '') ?>">
      <button type="submit" class="botao" id="comecarFim">Começar agora, grátis</button>
      <p class="nota">Usamos seus dados só para a sua inscrição e para falar com você sobre o curso.</p>
    </form>
  </section>
</main>
<?php rodape();
