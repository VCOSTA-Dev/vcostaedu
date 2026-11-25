// Guia: Portal dentro do Moodle (administrador). A demonstração não tem Moodle: o vídeo mostra o guia (a configuração no
// servidor e no Moodle) e uma página de curso SIMULADA, no mesmo endereço, com o portal embutido e o painel recusado.
const SIMULADA = 'http://localhost/pagina-de-curso-simulada/';
const pagina = (titulo, src, nota) => `<!doctype html><html><head><meta charset="utf-8"><title>${titulo}</title><style>
  body{margin:0;font-family:"Segoe UI",Arial,sans-serif;background:#f4f5f7;color:#222}
  header{background:#3a4a5c;color:#fff;padding:10px 20px;font-size:15px}header b{font-size:18px}
  .aviso{background:#fff3cd;color:#664d03;padding:6px 20px;font-size:13px;border-bottom:1px solid #e0c97a}
  main{padding:10px 20px}h2{margin:4px 0 8px;font-size:18px}iframe{width:100%;height:520px;border:1px solid #ccd;background:#fff}
</style></head><body><header><b>Página de curso SIMULADA</b> · Pós-graduação em Gestão de Projetos (fictícia)</header>
<div class="aviso">Simulação para este vídeo: não é o Moodle. ${nota}</div>
<main><h2>${titulo}</h2><iframe src="${src}"></iframe></main></body></html>`;

module.exports = {
  conta: 'administrador',
  contas: ['260104'],
  errosEsperados: [/Content Security Policy|frame-ancestors|refused to (frame|connect)/i, /'sessionStorage'.*Access is denied/], // a recusa do painel dentro do iframe (e o documento de erro, sem origem, que o navegador põe no lugar): é o que o vídeo mostra
  async gravar(v) {
    v.capitulo('O que o guia pede');
    await v.ir('painel/ajuda/guia/portal-dentro-do-moodle');
    v.confere(/Portal dentro do Moodle/.test(await v.texto('.page-title')), 'o guia deveria abrir na Central de ajuda');
    await v.passo('Ajuda → "Portal dentro do Moodle". Antes: portal e Moodle em HTTPS; o recomendado é o portal num subdomínio do Moodle.');
    await v.destacar('#conteudoGuia ul');
    const cfg = v.page.locator('#conteudoGuia code', { hasText: 'ORIGENS_PERMITIDAS_IFRAME' }).first();
    await v.rolar(cfg);
    await v.passo('1) No servidor do portal, em portal/core/Config.php: o endereço do Moodle em ORIGENS_PERMITIDAS_IFRAME.');
    await v.destacar(cfg);
    const ifr = v.page.locator('#conteudoGuia code', { hasText: '<iframe' }).first();
    await v.rolar(ifr);
    await v.passo('2) No Moodle, no curso: um recurso URL com "Embutir", ou um bloco HTML com este iframe.');
    await v.destacar(ifr);
    await v.passo('Quem instala o sistema edita o arquivo no servidor. A demonstração não tem Moodle: a seguir, uma simulação.');

    v.capitulo('O portal dentro da página do curso');
    const portal = v.page.url().replace(/painel\/.*$/, 'portal/');
    await v.page.route(SIMULADA + '**', r => r.fulfill({ contentType: 'text/html; charset=utf-8', body:
      /painel/.test(r.request().url())
        ? pagina('Painel da equipe (embutido)', portal.replace(/portal\/$/, 'painel/login/acesso'), 'O painel da equipe nunca abre num iframe.')
        : pagina('Portal do aluno', portal, 'Aqui a página está no mesmo endereço do portal; no Moodle real, vale a configuração do passo 1.') }));
    await v.page.goto(SIMULADA + 'curso.html', { waitUntil: 'load' });
    const f = v.page.frameLocator('iframe');
    await f.locator('input[name="usuario"]').waitFor({ timeout: 30000 });
    await v.passo('3) Abra o curso como um aluno de teste: o portal aparece dentro da página. O aluno entra com o RA.');
    await v.digitar(f.locator('input[name="usuario"]'), '260104');
    await v.digitar(f.locator('input[name="senha"]'), v.senhas['260104']);
    await v.clicar(f.locator('input[name="SendLogin"]'));
    await f.locator('.page-wrapper').first().waitFor({ timeout: 30000 });
    await v.pausa(800);
    await v.passo('Entrou sem sair do curso. Agora o Boletim: se ele aparece sem pedir o login de novo, está pronto.');
    await v.clicar(f.locator('.page-wrapper a[href*="boletim-aluno"]').first());
    await f.locator('.boletimCurso, #semBoletim').first().waitFor({ timeout: 30000 });
    await v.pausa(800);
    v.poster();
    v.confere(await f.locator('.boletimCurso').count(), 'o boletim deveria abrir dentro da página do curso');
    await v.passo('O boletim abriu dentro da página, sem novo login. O aluno vê boletim, notas e horário sem sair do curso.');
    await v.destacar(f.locator('.boletimCurso').first());

    v.capitulo('O painel nunca aparece num iframe');
    await v.passo('Só o portal do aluno pode aparecer dentro de outro site. O painel da equipe, por segurança, nunca.');
    await v.page.goto(SIMULADA + 'painel.html', { waitUntil: 'load' });
    await v.pausa(1500);
    const quadro = v.page.frames().find(x => x !== v.page.mainFrame());
    const abriu = quadro ? !!(await quadro.$('input[name="SendLogin"]').catch(() => null)) : false;
    v.confere(!abriu, 'o painel não deveria abrir dentro do iframe');
    await v.passo('O quadro do painel fica recusado ("recusou a conexão"). O mesmo acontece com o portal se o endereço do Moodle faltar no passo 1.');
    await v.destacar('iframe');
    await v.passo('Se o navegador do aluno bloquear o acesso dentro do Moodle, o portal mostra o botão "Abrir o portal em nova aba".');
    await v.page.unroute(SIMULADA + '**');
  },
};
