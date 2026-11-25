/*
 * Prova do instalador pelo navegador (Etapa 2).
 *
 *   node tests/tabler/instalar.js [cenario]
 *
 * Cenários (o reset é feito ANTES, de fora, por tests/tabler/reset.php — e cada sabotagem também):
 *   normal      as 7 telas até a Página Inicial; depois, a recusa de reinstalar
 *   exposicao   a pasta dos arquivos privados está servida (sabotagem preparada fora): a tela 4 tem de PARAR
 *   charset     o banco não é utf8mb4: a tela 3 tem de RECUSAR
 *   naovazio    o banco tem tabela: a tela 3 tem de RECUSAR
 *   semtrigger  o usuário do banco não tem TRIGGER: a 0003 fica ADIADA, com aviso, e a instalação termina
 *
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE; usuário do banco no formulário: VCOSTAEDU_FORM_USUARIO e
 * VCOSTAEDU_FORM_SENHA (padrão root, sem senha — o MariaDB do XAMPP local). A conta criada usa
 * VCOSTAEDU_ADMIN_SENHA (padrão: uma senha só de prova, que vale só no banco descartável).
 */
const L = require('./lib');

const cenario = process.argv[2] || 'normal';
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
const FORM_USUARIO = process.env.VCOSTAEDU_FORM_USUARIO || 'root';
const FORM_SENHA = process.env.VCOSTAEDU_FORM_SENHA || '';
const ADMIN_SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const ETAPA = process.env.VCOSTAEDU_ETAPA_PROVA || 'onda2/etapa1';
const TIPO = ['escola', 'livre'].includes(process.env.VCOSTAEDU_TIPO) ? process.env.VCOSTAEDU_TIPO : 'superior'; // Onda 4, Etapa 1: o tipo de instituição escolhido (D-106: e o curso livre)
/* D-106: no curso livre, os recursos marcados na instalação (VCOSTAEDU_RECURSOS="financeiro,matricula"; sem a variável, o padrão da tela) */
const RECURSOS = process.env.VCOSTAEDU_RECURSOS === undefined ? null : process.env.VCOSTAEDU_RECURSOS.split(',').map((x) => x.trim()).filter(Boolean);
const NOME_TIPO = { escola: 'Escola (educação básica)', livre: 'Curso livre (curso on-line)', superior: 'Ensino superior' };

(async () => {
  const { navegador, contexto } = await L.abrir();
  await contexto.grantPermissions(['clipboard-read', 'clipboard-write'], { origin: new URL(L.BASE).origin });
  const p = await L.pagina(contexto);
  try {
    console.log(`\n== Instalador — cenário ${cenario}`);

    await p.goto(L.BASE);
    L.ok(/\/instalar\/(\?.*)?$/.test(p.url()), `a raiz sem configuração leva ao instalador (${p.url()})`);
    await L.semErros(p, 'tela 1 (requisitos)');
    L.ok(await p.isVisible('h2:has-text("Requisitos do servidor")'), 'tela 1: o título revisado (sem "Vamos ver se este servidor serve")');
    L.ok((await p.content()).indexOf('Vamos ver se este servidor serve') === -1, 'tela 1: a frase antiga saiu');
    /* Onda 3, Etapa 5: a palavra VCOSTA do copyright é a marca em imagem (o texto alternativo conta) */
    const legal = await p.$eval('.vc-legal', (e) => { const c = e.cloneNode(true); c.querySelectorAll('img[alt]').forEach((i) => { if (getComputedStyle(e.querySelector('img[src="' + i.getAttribute('src') + '"]')).display !== 'none') { i.replaceWith(i.alt); } else { i.remove(); } }); return c.textContent.replace(/\s+/g, ' '); }).catch(() => '');
    L.ok(/VCOSTAEDU \d+\.\d+\.\d+/.test(legal) && /Copyright © 20\d\d(-20\d\d)? VCOSTA/.test(legal) && /Software livre, sem garantia, sob a licença AGPL-3\.0/.test(legal) && /Licença/.test(legal) && /Sobre/.test(legal),
      `rodapé legal no instalador ("${legal.replace(/\s+/g, ' ').slice(0, 140)}")`);
    L.ok(!/all rights reserved/i.test(await p.content()), 'sem "All rights reserved"');
    if (cenario === 'normal') { await L.print(p, ETAPA, 'instalador-1-requisitos'); }
    await p.click('a.btn:has-text("Continuar para o banco de dados")');

    await p.fill('#host', 'localhost');
    await p.fill('#dbname', BANCO);
    await p.fill('#usuario', FORM_USUARIO);
    await p.fill('#senha', FORM_SENHA);
    if (cenario === 'normal') {
      /* o bloco do CREATE DATABASE: botão Copiar com confirmação, e a seleção visível no fundo escuro */
      await p.click('summary:has-text("Como criar o banco de dados")');
      const bloco = '#comandoCriarBanco';
      await p.click('.vc-copiar-caixa:has(' + bloco + ') .vc-copiar-botao');
      await p.waitForTimeout(200);
      const rotulo = await p.$eval('.vc-copiar-caixa:has(' + bloco + ') .vc-copiar-botao', (b) => b.textContent);
      const copiado = await p.evaluate(() => navigator.clipboard.readText()).catch(() => '');
      L.ok(rotulo === 'Copiado', `Copiar: o botão confirma ("${rotulo}")`);
      L.ok(/^CREATE DATABASE vcostaedu\s+CHARACTER SET utf8mb4\s+COLLATE utf8mb4_unicode_ci;$/.test(copiado.trim()), 'Copiar: a área de transferência tem o comando inteiro');
      const selecao = await p.$eval(bloco, (e) => getComputedStyle(e, '::selection').backgroundColor);
      L.ok(selecao === 'rgb(251, 227, 142)', `a seleção de texto aparece no bloco escuro (${selecao})`);
      await p.waitForTimeout(2700);
      L.ok((await p.$eval('.vc-copiar-caixa:has(' + bloco + ') .vc-copiar-botao', (b) => b.textContent)) === 'Copiar', 'Copiar: o rótulo volta depois de alguns segundos');
      await L.print(p, ETAPA, 'instalador-2-banco');
    }
    await p.click('button:has-text("Testar a conexão")');
    /* A recusa responde HTTP 409 de propósito; o navegador registra o código no console. */
    await L.semErros(p, 'tela 3 (conferência)', ['charset', 'naovazio'].includes(cenario) ? /status of 409/ : null);

    if (cenario === 'charset') {
      L.ok(await p.isVisible('.vc-copiar-caixa .vc-copiar-botao'), 'a recusa traz o Copiar no comando');
      L.ok(await p.isVisible('text=O banco não usa o conjunto de caracteres exigido'), 'charset diferente de utf8mb4: RECUSADO na conferência');
      L.ok(await p.isVisible('text=CREATE DATABASE'), 'a recusa traz o CREATE DATABASE pronto');
      await L.print(p, ETAPA, 'instalador-recusa-charset');
      return;
    }
    if (cenario === 'naovazio') {
      L.ok(await p.isVisible('text=O banco informado não está vazio'), 'banco com tabela: RECUSADO na conferência');
      await L.print(p, ETAPA, 'instalador-recusa-nao-vazio');
      return;
    }

    L.ok(await p.isVisible('text=Conferência do banco de dados'), 'conferência: o banco está pronto');
    /* Onda 3, Etapa 2: a conferência informa o modo de SQL que o sistema fixa na conexão */
    const modo = await p.$eval('#modoDoBanco', (e) => e.textContent.replace(/\s+/g, ' ').trim()).catch(() => '');
    L.ok(/estrito, fixado pelo sistema/.test(modo) && /STRICT_TRANS_TABLES/.test(modo), `conferência: o modo do banco (${modo.slice(0, 60)}…)`);
    if (cenario === 'normal') { await L.print(p, ETAPA, 'instalador-3-conferencia'); }
    await p.click('button:has-text("Gravar a configuração")');
    await L.semErros(p, 'tela 4 (configuração)');
    const vereditos = await p.$$eval('#exposicao [data-veredito]', (els) => els.map((e) => e.getAttribute('data-veredito')));
    console.log('     vereditos da exposição: ' + vereditos.join(', '));

    if (cenario === 'exposicao') {
      L.ok(vereditos.includes('exposto'), 'sabotagem: a pasta dos arquivos privados servida pela web foi acusada (exposto)');
      L.ok(await p.isVisible('text=Não continue: há um arquivo privado acessível pela web'), 'a tela manda não continuar');
      L.ok((await p.$$('a:has-text("Criar as tabelas")')).length === 0, 'não há botão para seguir para as tabelas');
      await L.print(p, ETAPA, 'instalador-exposicao-sabotada');
      return;
    }

    L.ok(vereditos.length === 3 && !vereditos.includes('exposto') && !vereditos.includes('nao_medido'), 'as duas configurações e a pasta dos arquivos privados não vazam pela web');
    if (cenario === 'normal') { await L.print(p, ETAPA, 'instalador-4-configuracao'); }
    await p.click('a.btn:has-text("Criar as tabelas")');
    await p.click('button:has-text("Criar as tabelas")');
    await L.semErros(p, 'tela 5 (tabelas)');
    const situacoes = await p.$$eval('#etapas tr[data-situacao]', (els) => els.map((e) => e.getAttribute('data-situacao')));
    console.log('     etapas: ' + situacoes.join(', '));
    if (cenario === 'semtrigger') {
      L.ok(situacoes[2] === 'ADIADA' && situacoes.filter((x) => x === 'APLICADA').length === situacoes.length - 1, 'sem TRIGGER: só a 0003 ADIADA, as demais aplicadas');
      L.ok(await p.isVisible('#avisoGatilhos'), 'o aviso dos gatilhos aparece e a instalação continua');
      await L.print(p, ETAPA, 'instalador-sem-trigger');
    } else {
      L.ok(situacoes.length >= 4 && situacoes.every((x) => x === 'APLICADA'), `as ${situacoes.length} migrations aplicadas`);
      if (cenario === 'normal') { await L.print(p, ETAPA, 'instalador-5-tabelas'); }
    }
    await p.click('a.btn:has-text("Criar a conta de administrador")');

    /* Uma recusa da tela 6 antes do certo: senha curta */
    await p.fill('#usuario', 'administrador');
    await p.fill('#nome', 'Administrador da Prova');
    await p.fill('#email', 'administrador@prova.invalid');
    const medidor = async (valor) => {
      await p.fill('#senha', valor);
      await p.waitForTimeout(80);
      return p.$eval('.vc-senha-medidor', (m) => ({ nivel: m.dataset.nivel, recusada: m.dataset.recusada, texto: m.innerText.trim() }));
    };
    L.ok(await p.isVisible('#senha + .input-group-text .vc-senha-revelar, .vc-senha-grupo:has(#senha) .vc-senha-revelar'), 'senha: o botão de revelar aparece');
    await p.fill('#senha', 'uma frase qualquer de teste');
    await p.click('.vc-senha-grupo:has(#senha) .vc-senha-revelar');
    L.ok((await p.$eval('#senha', (e) => e.type)) === 'text', 'senha: revelar mostra o texto');
    await p.click('.vc-senha-grupo:has(#senha) .vc-senha-revelar');
    L.ok((await p.$eval('#senha', (e) => e.type)) === 'password', 'senha: ocultar volta a esconder');
    L.ok(await p.isVisible('.vc-senha-grupo:has(#confirmacao) .vc-senha-revelar'), 'confirmação: também tem o revelar');
    const m1 = await medidor('curta');
    L.ok(m1.recusada === '1' && /Curta demais/.test(m1.texto), `medidor: curta reprovada ("${m1.texto}")`);
    const m2 = await medidor('123456789012');
    L.ok(m2.recusada === '1' && /comum/.test(m2.texto), `medidor: senha da lista local reprovada ("${m2.texto}")`);
    const m3 = await medidor('Prova2026administrador');
    L.ok(m3.recusada === '1' && /nome|usuário|e-mail/.test(m3.texto), `medidor: senha com o usuário reprovada ("${m3.texto}")`);
    const m4 = await medidor('cadeiras azuis no jardim de inverno');
    L.ok(m4.recusada === '0' && m4.nivel === '4', `medidor: frase longa sem símbolo é forte ("${m4.texto}")`);
    const m5 = await medidor('cadeiraazul1');
    L.ok(m5.recusada === '0' && m5.nivel === '2', `medidor: 12 caracteres é aceitável, não forte ("${m5.texto}")`);
    L.ok((await p.content()).indexOf('Senha@2026') === -1, 'a ajuda não traz exemplo de senha');
    /* colar funciona: a senha entra pela área de transferência */
    await p.evaluate(() => navigator.clipboard.writeText('cadeiras azuis no jardim de inverno'));
    await p.fill('#senha', '');
    await p.focus('#senha');
    await p.keyboard.press('Control+V');
    await p.waitForTimeout(100);
    L.ok((await p.$eval('#senha', (e) => e.value)) === 'cadeiras azuis no jardim de inverno', 'colar: a senha colada entra no campo');
    /* a regra do SERVIDOR (o navegador só avisa): senha comum com números no fim, e com o usuário */
    await p.fill('#senha', 'flamengo123456');
    await p.fill('#confirmacao', 'flamengo123456');
    await p.click('button:has-text("Criar a conta e concluir")');
    L.ok(await p.isVisible('text=Esta senha é muito comum'), 'servidor: recusa a senha comum (com números no fim)');
    await p.fill('#senha', 'curta');
    await p.fill('#confirmacao', 'curta');
    await p.click('button:has-text("Criar a conta e concluir")');
    L.ok(await p.isVisible('text=pelo menos 12 caracteres'), 'servidor: senha curta recusada, com a razão');
    await p.fill('#senha', 'administrador e mais nada');
    await p.fill('#confirmacao', 'administrador e mais nada');
    await p.click('button:has-text("Criar a conta e concluir")');
    L.ok(await p.isVisible('text=não pode conter o seu nome'), 'servidor: recusa a senha com o usuário');
    await p.fill('#senha', ADMIN_SENHA);
    await p.fill('#confirmacao', ADMIN_SENHA);
    if (cenario === 'normal') { await L.print(p, ETAPA, 'instalador-6-conta'); }
    await p.click('button:has-text("Criar a conta e concluir")');
    /* Onda 4, Etapa 1: o tipo da instituição, depois da conta e antes de "Como você quer começar?" (VCOSTAEDU_TIPO;
       o padrão é o ensino superior, já marcado) */
    await L.semErros(p, 'o tipo de instituição');
    L.ok(await p.isVisible('#tipoInstituicao') && await p.isChecked('#tipo_superior') && await p.isVisible('#tipo_escola'),
      'depois da conta, a escolha do tipo de instituição (ensino superior marcado; escola), uma frase de cada');
    if (cenario === 'normal') { await L.print(p, ETAPA, 'instalador-6b-tipo'); }
    L.ok(await p.isVisible('#tipo_livre') || await p.isVisible('label:has(#tipo_livre)'), 'D-106: o curso livre é a terceira opção');
    L.ok(!(await p.isVisible('#recursosLivre')), 'D-106: as caixas do curso livre ficam escondidas enquanto outro tipo está marcado');
    await p.check('#tipo_' + TIPO, { force: true }); // o rádio do Tabler fica sob o rótulo
    if (TIPO === 'livre') {
      L.ok(await p.isVisible('#recursosLivre'), 'D-106: marcado o curso livre, aparecem o financeiro e a matrícula');
      if (RECURSOS !== null) {
        for (const r of ['financeiro', 'matricula']) {
          if (RECURSOS.includes(r)) { await p.check('#recurso_' + r); } else { await p.uncheck('#recurso_' + r); }
        }
      }
      if (cenario === 'normal') { await L.print(p, ETAPA, 'instalador-6c-curso-livre'); }
    }
    await Promise.all([p.waitForNavigation(), p.click('#confirmarTipo')]);
    await L.semErros(p, 'tela 7 (pronto)');
    L.ok((await p.textContent('#tipoEscolhido').catch(() => '')).includes(NOME_TIPO[TIPO]), `a tela final diz o tipo escolhido (${TIPO})`);
    if (TIPO === 'livre') { /* D-106: e o que ficou ligado */
      const rec = (await p.textContent('#recursosEscolhidos').catch(() => '')).replace(/\s+/g, ' ');
      const fin = RECURSOS === null ? false : RECURSOS.includes('financeiro');
      const mat = RECURSOS === null ? true : RECURSOS.includes('matricula');
      L.ok(rec.includes(`Financeiro: ${fin ? 'ligado' : 'desligado'}`) && rec.includes(`Matrícula pela secretaria: ${mat ? 'ligado' : 'desligado'}`), `a tela final diz o que ficou ligado no curso livre (${rec})`);
    }
    const acesso = await p.$$eval('#conferenciaAcesso [data-ok]', (els) => els.map((e) => e.getAttribute('data-ok')));
    L.ok(acesso.length === 7 && acesso.every((x) => x === '1'), `a conferência do acesso: ${acesso.filter((x) => x === '1').length} ok de ${acesso.length}, sem aviso (página sem perfil, ação acima do perfil e tela sem ação reprovam)`);
    L.ok(!/apague a pasta/i.test(await p.content()), 'o aviso "apague a pasta instalar/" saiu');
    const amb = await p.$$eval('#conferenciaAmbiente [data-tipo]', (els) => els.map((e) => e.getAttribute('data-tipo') + ' ' + e.textContent.trim().replace(/\s+/g, ' ')));
    L.ok(amb.length > 20, `a conferência do servidor (verificar_ambiente.php) rodou: ${amb.length} itens`);
    amb.filter((x) => !x.startsWith('OK')).forEach((x) => console.log('     ' + x.slice(0, 170)));
    if (cenario === 'normal') { await L.print(p, ETAPA, 'instalador-7-pronto'); }

    if (TIPO === 'superior' || TIPO === 'escola' || TIPO === 'livre') { // D-106: e o curso livre /* Onda 4, Etapa 4: a escola também tem a demonstração (o colégio fictício) */
      L.ok(await p.isVisible('#conhecerDemonstracao') && await p.isVisible('#abrirPainel'), 'a última tela traz a escolha: conhecer com a demonstração ou configurar a instituição');
      /* Onda 3, Etapa 4: o destaque na demonstração; o resumo do que fazer no topo, o detalhe recolhido, sem nome técnico */
      L.ok(/\bbtn-primary\b/.test(await p.getAttribute('#conhecerDemonstracao', 'class')) && !/\bbtn-primary\b/.test(await p.getAttribute('#abrirPainel', 'class')), 'o botão em destaque é "Conhecer com dados de demonstração"');
    } else {
      L.ok(await p.isVisible('#abrirPainel') && !(await p.$('#conhecerDemonstracao')) && await p.isVisible('#semDemonstracao'), 'escola: a última tela leva ao painel e diz que a demonstração da escola ainda não está disponível');
    }
    const resumoTopo = (await p.textContent('#resumoAFazer').catch(() => '')).replace(/\s+/g, ' ').trim();
    const recolhido = await p.$eval('#detalheAmbiente', (d) => d.tagName === 'DETAILS' && !d.open).catch(() => false);
    const textoAmb = resumoTopo + ' ' + (await p.textContent('#conferenciaAmbiente').catch(() => ''));
    const tecnico = textoAmb.match(/VERSAODEV|PROXIES_CONFIAVEIS|CAMINHO_BASE|REMOTE_ADDR|EXIGIR_HTTPS|PASTA_STORAGE|HOSTS_PERMITIDOS|--raiz-publica|php bin|\.php\b/g) || [];
    L.ok(resumoTopo.length > 0 && recolhido && tecnico.length === 0 && /normal em instalação local; resolva antes de publicar/.test(resumoTopo) && !/sem permissão de escrita|vídeo\(s\) listados não estão|vídeo\(s\) dos guias faltam/.test(textoAmb), // Onda 5: a linha OK ("N vídeo(s) listados …, todos na pasta") não é alarme
      `a verificação do servidor: o que fazer no topo ("${resumoTopo.slice(0, 80)}…"), o detalhe recolhido, sem nome técnico, sem o falso alarme da escrita nem dos vídeos${tecnico.length ? ' — achou: ' + tecnico.join(', ') : ''}`);
    await p.click('#abrirPainel');
    L.ok(/\/painel\/login\/acesso|\/painel\/$/.test(p.url()), `o botão abre o painel (${p.url()})`);
    await p.fill('input[name="usuario"]', 'administrador');
    await p.fill('input[name="senha"]', ADMIN_SENHA);
    await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
    L.ok(/\/painel\/(home\/index|primeiros-passos\/index)/.test(p.url()), `o administrador entra e, pela escolha "Configurar a minha instituição", cai nos Primeiros passos (${p.url()})`);
    await L.semErros(p, 'Página Inicial');

    if (cenario === 'normal') {
      const r = await p.goto(L.BASE + 'instalar/');
      L.ok(r.status() === 409 && (await p.isVisible('text=Este sistema já está instalado')), 'instalar de novo: RECUSADO (HTTP 409)');
      L.ok(!/Config\.php|htdocs|:\\|\/var\/|DBNAME|_teste/.test(await p.content()), 'a recusa não revela caminho, configuração nem banco');
      await L.print(p, ETAPA, 'instalador-recusa-ja-instalado');
    }
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
    await L.print(p, ETAPA, `instalador-erro-${cenario}`).catch(() => {});
  } finally {
    await navegador.close();
    process.exit(L.resumo(`instalador (${cenario})`) ? 0 : 1);
  }
})();
