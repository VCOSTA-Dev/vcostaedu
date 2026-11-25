/*
 * ONDA 2, ETAPA 1 — o acabamento: a recusa de TODO endereço de instalar/, os avisos legais e a página Sobre, o
 * componente de senha e a regra única no servidor (painel e portal), o perfil do portal só do próprio aluno, as
 * páginas mortas fora, e o aviso de "atualização pendente" (os arquivos novos antes da migration).
 *
 * Cada parte tem a sua SABOTAGEM: o defeito posto de propósito, e a prova tem de reprová-lo (sabotar()).
 *
 *   node tests/tabler/onda2/etapa1.js           (depois do ciclo: instalado e com a demonstração)
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE, NODE_PATH.
 */
const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste2?$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().trim();
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const ETAPA = 'onda2/etapa1';

async function entrar(p, app, usuario, senha) {
  await p.goto(L.BASE + app + '/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
}

/* Roda a conferência com o defeito posto: ela TEM de reprovar. Devolve true se reprovou. */
async function sabotar(nome, estragar, consertar, conferir) {
  const antes = L.falhas.length;
  let reprovou = false;
  try {
    await estragar();
    const ok = await conferir();
    reprovou = !ok;
  } finally {
    await consertar();
  }
  L.ok(reprovou, `sabotagem (${nome}): a prova reprova o defeito`);
  return reprovou && L.falhas.length === antes + 0;
}

/* ---- A. a recusa de instalar/ ---- */
const ENDERECOS = ['instalar/', 'instalar/index.php', 'instalar/?passo=banco', 'instalar/?passo=administrador', 'instalar/?passo=pronto',
  'instalar/?passo=tabelas', 'instalar/comum.php', 'instalar/layout.php', 'instalar/verificacoes.php', 'instalar/gerador.php',
  'instalar/pastas.php', 'instalar/migracoes.php', 'instalar/administrador.php', 'instalar/inexistente', 'instalar/x/y.php'];
async function conferirRecusa(req, registrar = true) {
  let tudo = true;
  for (const e of ENDERECOS) {
    for (const metodo of ['GET', 'POST']) {
      const r = metodo === 'GET' ? await req.get(L.BASE + e, { maxRedirects: 0 }) : await req.post(L.BASE + e, { form: { token: 'x', host: 'localhost' }, maxRedirects: 0 });
      const corpo = await r.text();
      const recusa = r.status() === 409 && corpo.includes('Este sistema já está instalado');
      const vaza = /Config\.php|htdocs|[A-Z]:[\\/]|\/var\/|DBNAME|_teste|SQLSTATE|Warning|Fatal/.test(corpo) || corpo.includes(BANCO);
      if (!recusa || vaza) {
        tudo = false;
        if (registrar) { L.ok(false, `${metodo} ${e}: HTTP ${r.status()}${vaza ? ', revela dado do servidor' : ''}`); }
      }
    }
  }
  return tudo;
}

(async () => {
  const { navegador, contexto } = await L.abrir();
  const p = await L.pagina(contexto);
  const req = contexto.request;
  try {
    console.log('\n== Onda 2, Etapa 1');

    /* A. Todo endereço de instalar/ responde a recusa, sem revelar nada */
    L.ok(await conferirRecusa(req), `instalar/: ${ENDERECOS.length} endereços × GET e POST respondem a recusa (409), sem caminho, banco ou erro`);
    const ht = path.join(L.RAIZ, 'instalar', '.htaccess');
    await sabotar('sem o .htaccess de instalar/', () => fs.renameSync(ht, ht + '.sabotado'), () => fs.renameSync(ht + '.sabotado', ht),
      () => conferirRecusa(req, false));

    /* B. Os avisos legais e a página Sobre */
    /* A marca VCOSTAEDU é desenho (alt="VCOSTAEDU", Onda 3, Etapa 7); a versão só no rodapé de quem entrou (Onda 3, Etapa 10, DUVIDAS 227) */
    const temLegal = (html, comVersao) => /alt="VCOSTAEDU"/.test(html) && /Copyright © 20\d\d(-20\d\d)? (VCOSTA|<img[^>]*alt="VCOSTA")/.test(html)
      && /Software livre, sem garantia, sob a licença AGPL-3\.0/.test(html) && /sobre\.php#licenca">Licença</.test(html) && !/all rights reserved/i.test(html)
      && /class="vc-legal-versao">\d+\.\d+\.\d+</.test(html) === comVersao;
    for (const u of ['painel/login/acesso', 'portal/login/acesso', 'painel/login/esqueceu-senha', 'portal/login/esqueceu-senha']) {
      const html = await (await req.get(L.BASE + u)).text();
      L.ok(temLegal(html, false), `rodapé legal: ${u} (sem a versão)`);
    }
    await p.goto(L.BASE + 'sobre.php');
    await L.semErros(p, 'Sobre');
    const sobre = await p.content();
    L.ok(/GNU AFFERO GENERAL PUBLIC LICENSE/.test(sobre) && /Version 3, 19 November 2007/.test(sobre), 'Sobre: o texto integral do LICENSE');
    /* Onda 3, Etapa 6: a lista das bibliotecas, com as versões, só para quem entrou; a quem não entrou, o aviso */
    L.ok(/id="creditos"/.test(sobre) && /aparece para quem entrou no sistema/.test(sobre) && !/phpmailer\/phpmailer/.test(sobre), 'Sobre (sem entrar): os créditos sem a lista das bibliotecas');
    L.ok(!/Código-fonte/.test(sobre), 'Sobre: sem URL_CODIGO_FONTE, o link do código-fonte não aparece');
    await L.print(p, ETAPA, 'sobre');
    const cfg = path.join(L.RAIZ, 'painel', 'core', 'Config.php');
    const cfgOriginal = fs.readFileSync(cfg, 'utf8');
    try {
      fs.writeFileSync(cfg, cfgOriginal.replace("define('URL_CODIGO_FONTE', '');", "define('URL_CODIGO_FONTE', 'https://exemplo.invalid/codigo');"));
      const html = await (await req.get(L.BASE + 'painel/login/acesso')).text();
      L.ok(/href="https:\/\/exemplo\.invalid\/codigo"[^>]*>Código-fonte</.test(html), 'com URL_CODIGO_FONTE: o link "Código-fonte" aparece no rodapé');
    } finally {
      fs.writeFileSync(cfg, cfgOriginal);
    }

    /* C. Painel: o rodapé dentro, o componente de senha e a regra do servidor (trocar a minha senha) */
    await entrar(p, 'painel', 'administrador', cred.painel['administrador']);
    L.ok(/\/painel\/home\/index/.test(p.url()), 'o administrador da demonstração entrou');
    L.ok(temLegal(await p.content(), true), 'rodapé legal dentro do painel (com a versão)');
    await p.goto(L.BASE + 'sobre.php');
    const sobreDentro = await p.content();
    L.ok(/<td>Tabler<\/td>/.test(sobreDentro) && /phpmailer\/phpmailer/.test(sobreDentro) && /dompdf\/dompdf/.test(sobreDentro), 'Sobre (quem entrou): os créditos das bibliotecas (interface e PHP)');
    const hashAdm = sql("SELECT senha FROM adms_usuarios WHERE usuario='administrador'");
    try {
      await p.goto(L.BASE + 'painel/perfil/visualizar');
      await L.semErros(p, 'painel: Perfil');
      const botao = await p.$('[data-bs-target="#EditarSenhaPerfil"]');
      if (botao) { await botao.click(); await p.waitForTimeout(500); }
      const campo = await p.$('#novaSenhaPerfil');
      L.ok(!!campo, 'painel: o campo da nova senha tem o componente');
      L.ok(!!(await p.$('.vc-senha-grupo:has(#novaSenhaPerfil) .vc-senha-revelar')), 'painel: botão de revelar na troca da minha senha');
      await p.fill('#novaSenhaPerfil', 'administrador da prova sempre');
      await p.waitForTimeout(80);
      const mx = await p.$eval('.vc-senha-medidor', (m) => m.dataset.recusada + '|' + m.innerText);
      L.ok(/^1\|/.test(mx), `painel: o medidor recusa a senha com o nome da pessoa ("${mx.split('|')[1].trim()}")`);
      await L.print(p, ETAPA, 'painel-trocar-a-minha-senha');
      /* o servidor: comum, com o e-mail, curta -> recusadas; boa -> aceita */
      for (const [senha, motivo] of [['flamengo123456', 'muito comum'], ['curtinha', 'pelo menos 12']]) {
        await p.fill('#novaSenhaPerfil', senha);
        await Promise.all([p.waitForNavigation().catch(() => {}), p.click('#EditarSenhaPerfil [name="EditSenhaPerfil"]')]);
        L.ok((await p.content()).includes(motivo) && sql("SELECT senha FROM adms_usuarios WHERE usuario='administrador'") === hashAdm, `painel, servidor: "${senha}" recusada (${motivo}) e a senha não mudou`);
        await p.goto(L.BASE + 'painel/perfil/visualizar');
        const b2 = await p.$('[data-bs-target="#EditarSenhaPerfil"]');
        if (b2) { await b2.click(); await p.waitForTimeout(500); }
      }
      await p.fill('#novaSenhaPerfil', 'uma frase longa com espacos aceita');
      await Promise.all([p.waitForNavigation().catch(() => {}), p.click('#EditarSenhaPerfil [name="EditSenhaPerfil"]')]);
      L.ok(sql("SELECT senha FROM adms_usuarios WHERE usuario='administrador'") !== hashAdm, 'painel, servidor: frase longa com espaços aceita (antes: espaço era proibido)');
      const fechar = await p.$('.alert-success .btn-close');
      L.ok(!!fechar, 'a mensagem de sucesso tem o ×');
    } finally {
      sql(`UPDATE adms_usuarios SET senha='${hashAdm.replace(/'/g, "''")}' WHERE usuario='administrador'`);
    }

    /* cadastro de usuário: o componente lê nome, usuário e e-mail do próprio formulário */
    await p.goto(L.BASE + 'painel/usuarios/cadastrar');
    await L.semErros(p, 'painel: cadastrar usuário');
    await p.fill('#nome', 'Pessoa Ficticia');
    await p.fill('#usuario', 'pessoa.ficticia');
    await p.fill('#senha', 'pessoa.ficticia2026');
    await p.waitForTimeout(80);
    L.ok((await p.$eval('.vc-senha-medidor', (m) => m.dataset.recusada)) === '1', 'cadastro de usuário: o medidor recusa a senha com o usuário digitado');
    await p.fill('#senha', 'janela aberta para o mar');
    await p.waitForTimeout(80);
    L.ok((await p.$eval('.vc-senha-medidor', (m) => m.dataset.recusada)) === '0', 'cadastro de usuário: frase longa aceita no medidor');
    await L.print(p, ETAPA, 'painel-cadastrar-usuario-senha');

    /* E. A atualização pendente: os arquivos antes do banco */
    const ctrl = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Controllers', 'ProvaPendente.php');
    const linha0004 = sql("SELECT CONCAT_WS('|', versao, arquivo, hash, aplicada_em, duracao_ms) FROM adms_migrations WHERE versao='0004'");
    L.ok(linha0004 !== '', 'a 0004 está aplicada no banco de prova');
    fs.writeFileSync(ctrl, "<?php\nnamespace App\\adms\\Controllers;\nif (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); }\n/* PROVA TEMPORÁRIA (tests/tabler/onda2/etapa1.js): um controller novo cuja página ainda não está no banco. */\nclass ProvaPendente { public function index() { echo 'NAO DEVIA ABRIR'; } }\n");
    try {
      /* sem pendência: rota desconhecida segue o caminho de sempre (não mostra o aviso) */
      let r = await p.goto(L.BASE + 'painel/prova-pendente/index');
      L.ok(!(await p.content()).includes('Atualização pendente'), 'sem migration pendente: a rota sem página não mostra o aviso');
      const [v, a, h, ap, d] = linha0004.split('|');
      sql("DELETE FROM adms_migrations WHERE versao='0004'");
      try {
        await p.goto(L.BASE + 'painel/home/index');
        L.ok(await p.isVisible('#avisoAtualizacao'), 'com a 0004 pendente: a faixa do administrador aparece');
        await L.semErros(p, 'Página Inicial com migration pendente');
        await L.print(p, ETAPA, 'painel-faixa-atualizacao-pendente');
        r = await p.goto(L.BASE + 'painel/prova-pendente/index');
        const html = await p.content();
        L.ok(r.status() === 503 && html.includes('Atualização pendente') && !html.includes('NAO DEVIA ABRIR'), `página nova sem a migration: aviso de atualização pendente (HTTP ${r.status()})`);
        await L.semErros(p, 'aviso de atualização pendente', /status of 503/);
        await L.print(p, ETAPA, 'painel-pagina-nova-pendente');
        /* todo o menu do administrador abre, sem erro do PHP, com a migration pendente */
        const destinos = await p.$$eval('.vc-side a[href*="/painel/"]', (as) => [...new Set(as.map((x) => x.getAttribute('href')))].filter((h) => !/logout|sair/i.test(h)));
        let semErro = 0;
        for (const href of destinos) {
          const resp = await p.goto(href);
          const c = await p.content();
          if (resp.status() < 400 && !/(Warning|Notice|Deprecated|Fatal error|Parse error|Uncaught)\b/.test(c)) { semErro++; } else { L.ok(false, `com a migration pendente, ${href} deu erro`); }
        }
        L.ok(destinos.length > 20 && semErro === destinos.length, `com a migration pendente: ${semErro} de ${destinos.length} telas do menu abrem sem erro`);
        p._erros.splice(0);
        /* a secretaria não vê a faixa e recebe o aviso sem o comando */
        const ctx2 = await navegador.newContext({ viewport: { width: 1366, height: 900 } });
        const p2 = await L.pagina(ctx2);
        await entrar(p2, 'painel', 'secretaria.demo', cred.painel['secretaria.demo']);
        L.ok(!(await p2.isVisible('#avisoAtualizacao')), 'secretaria: sem a faixa (não é quem atualiza)');
        await p2.goto(L.BASE + 'painel/prova-pendente/index');
        L.ok((await p2.content()).includes('Avise o administrador'), 'secretaria: o aviso manda avisar o administrador');
        await ctx2.close();
        /* sabotagem: sem a guarda do roteador, a página nova não mostra o aviso */
        const cc = path.join(L.RAIZ, 'painel', 'core', 'ConfigController.php');
        const ccOriginal = fs.readFileSync(cc, 'utf8');
        await sabotar('roteador sem o aviso de pendência', () => fs.writeFileSync(cc, ccOriginal.replace('} elseif ($this->paginaNovaSemMigration()) {', '} elseif (false) {')),
          () => fs.writeFileSync(cc, ccOriginal), async () => {
            await p.goto(L.BASE + 'painel/prova-pendente/index');
            return (await p.content()).includes('Atualização pendente');
          });
      } finally {
        sql(`INSERT INTO adms_migrations (versao, arquivo, hash, aplicada_em, duracao_ms) VALUES ('${v}', '${a}', '${h}', '${ap}', ${d || 0})`);
      }
      await p.goto(L.BASE + 'painel/home/index');
      L.ok(!(await p.isVisible('#avisoAtualizacao')), 'migration aplicada de novo: a faixa some');
    } finally {
      fs.unlinkSync(ctrl);
    }

    /* F. As páginas mortas saíram e nenhuma página ficou sem perfil */
    L.ok(sql("SELECT COUNT(*) FROM adms_paginas WHERE (controller,metodo) IN (('AcessoAcademico','permissao'),('AcessoAcademico','itemMenu'),('Disciplinas','matrizAno'),('Home','notasAtividades'))") === '0', 'as 4 páginas mortas saíram');
    const orfas = () => sql("SELECT COUNT(*) FROM adms_paginas p WHERE p.lib_pub <> 1 AND NOT EXISTS (SELECT 1 FROM adms_nivacs_pgs n WHERE n.adms_pagina_id = p.id AND n.permissao = 1)");
    L.ok(orfas() === '0', 'nenhuma página do painel sem perfil com acesso');
    const conferencia = () => {
      try {
        return execFileSync('php', [path.join(L.RAIZ, 'tests', 'tabler', 'onda2', 'conferir_acesso.php')]).toString().trim();
      } catch (e) {
        return ((e.stdout || '').toString().trim()) || 'FALHOU';
      }
    };
    L.ok(conferencia() === 'OK', 'a conferência do instalador (a mesma função) passa');
    await sabotar('página sem perfil', () => sql("INSERT INTO adms_paginas (id, menu_pagina, controller, metodo, menu_controller, menu_metodo, nome_pagina, lib_pub, icone, adms_grps_pg_id, adms_tps_pg_id, adms_sits_pg_id, created) VALUES (99001, 'Prova', 'ProvaOrfa', 'index', 'prova-orfa', 'index', 'Prova', 2, '', 1, 1, 1, NOW())"),
      () => sql('DELETE FROM adms_paginas WHERE id = 99001'), async () => conferencia() === 'OK');

    /* D. Portal: o perfil é só do próprio aluno; a senha com o componente e a regra */
    const ra = Object.keys(cred.portal)[0];
    const outro = Object.keys(cred.portal)[1];
    const hashOutro = sql(`SELECT senha FROM col_usuarios WHERE rm='${outro}'`);
    const hashMeu = sql(`SELECT senha FROM col_usuarios WHERE rm='${ra}'`);
    const idOutro = sql(`SELECT id FROM col_usuarios WHERE rm='${outro}'`);
    const nomeOutro = sql(`SELECT nome FROM col_usuarios WHERE rm='${outro}'`);
    const pa = await L.pagina(contexto);
    try {
      await entrar(pa, 'portal', ra, cred.portal[ra]);
      L.ok(/\/portal\/home\/index/.test(pa.url()), 'o aluno entrou no portal');
      L.ok(temLegal(await pa.content(), true), 'rodapé legal dentro do portal (com a versão)');
      await pa.goto(L.BASE + 'portal/perfil/editar-senha');
      await L.semErros(pa, 'portal: editar senha');
      L.ok(!!(await pa.$('.vc-senha-grupo:has(#senha) .vc-senha-revelar')) && !!(await pa.$('.vc-senha-medidor')), 'portal: revelar e medidor na troca de senha');
      await L.print(pa, ETAPA, 'portal-editar-senha');
      /* sabotagem de quem é: troca o id do formulário pelo de outro aluno */
      await pa.$eval('input[name="id"]', (e, v) => { e.value = v; }, idOutro);
      await pa.fill('#senha', 'outra frase longa para a prova');
      await Promise.all([pa.waitForNavigation().catch(() => {}), pa.click('[name="EditSenhaPerfil"]')]);
      L.ok(sql(`SELECT senha FROM col_usuarios WHERE rm='${outro}'`) === hashOutro, 'portal: com o id de OUTRO aluno no formulário, a senha dele não muda');
      L.ok(sql(`SELECT senha FROM col_usuarios WHERE rm='${ra}'`) !== hashMeu, 'portal: a senha trocada é a do aluno da sessão');
      /* sabotagem: o modelo volta a confiar no id do formulário (como na origem) — a prova tem de acusar */
      const modelo = path.join(L.RAIZ, 'portal', 'app', 'adms', 'Models', 'perfil', 'AdmsEditarSenhaPerfil.php');
      const modeloOriginal = fs.readFileSync(modelo, 'utf8');
      await sabotar('o id do formulário de volta no portal', () => fs.writeFileSync(modelo, modeloOriginal.replace("['id' => (int) ($_SESSION['academico_id'] ?? 0),", "['id' => (int) ($dados['id'] ?? 0),")),
        () => { fs.writeFileSync(modelo, modeloOriginal); sql(`UPDATE col_usuarios SET senha='${hashOutro.replace(/'/g, "''")}' WHERE rm='${outro}'`); }, async () => {
          await pa.goto(L.BASE + 'portal/perfil/editar-senha');
          await pa.$eval('input[name="id"]', (e, v) => { e.value = v; }, idOutro);
          await pa.fill('#senha', 'mais uma frase longa da prova');
          await Promise.all([pa.waitForNavigation().catch(() => {}), pa.click('[name="EditSenhaPerfil"]')]);
          return sql(`SELECT senha FROM col_usuarios WHERE rm='${outro}'`) === hashOutro;
        });
      /* o mesmo no perfil: o nome do outro aluno não muda */
      await pa.goto(L.BASE + 'portal/perfil/editar');
      if (await pa.$('input[name="id"]')) {
        await pa.$eval('input[name="id"]', (e, v) => { e.value = v; }, idOutro);
        const sub = await pa.$('[name="EditPerfil"]');
        if (sub) { await Promise.all([pa.waitForNavigation().catch(() => {}), sub.click()]); }
      }
      L.ok(sql(`SELECT nome FROM col_usuarios WHERE rm='${outro}'`) === nomeOutro && sql(`SELECT senha FROM col_usuarios WHERE rm='${outro}'`) === hashOutro, 'portal: editar o perfil com o id de outro aluno não altera o outro');
      /* a regra do servidor no portal */
      await pa.goto(L.BASE + 'portal/perfil/editar-senha');
      await pa.fill('#senha', 'palmeiras2026!!');
      await Promise.all([pa.waitForNavigation().catch(() => {}), pa.click('[name="EditSenhaPerfil"]')]);
      L.ok((await pa.content()).includes('muito comum'), 'portal, servidor: senha comum recusada');
    } finally {
      sql(`UPDATE col_usuarios SET senha='${hashMeu.replace(/'/g, "''")}' WHERE rm='${ra}'`);
      sql(`UPDATE col_usuarios SET senha='${hashOutro.replace(/'/g, "''")}' WHERE rm='${outro}'`);
    }
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
    await L.print(p, ETAPA, 'erro').catch(() => {});
  } finally {
    await navegador.close();
    process.exit(L.resumo('onda 2, etapa 1') ? 0 : 1);
  }
})();
