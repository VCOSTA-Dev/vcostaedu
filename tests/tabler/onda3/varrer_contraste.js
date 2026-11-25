/*
 * A VARREDURA DO CONTRASTE (Onda 3, Etapa 10): as telas das listas (tests/tabler/telas/*.json e as avulsas), nos dois
 * temas, com cada perfil. Grava provas/tabler/onda3/etapa10/contraste-<tema>.json e imprime o resumo.
 *   node tests/tabler/onda3/varrer_contraste.js [claro|escuro|ambos]
 */
const fs = require('fs');
const path = require('path');
const L = require('../lib');
const { contraste } = require('./contraste');

const temas = (process.argv[2] || 'ambos') === 'ambos' ? ['claro', 'escuro'] : [process.argv[2]];
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const pastaTelas = path.join(__dirname, '..', 'telas');
const telas = [];
for (const f of fs.readdirSync(pastaTelas).filter((x) => x.endsWith('.json'))) {
  for (const t of JSON.parse(fs.readFileSync(path.join(pastaTelas, f), 'utf8'))) { if (t.rota && !telas.some((x) => x.rota === t.rota && x.perfil === t.perfil)) { telas.push(t); } }
}
/* as telas desta onda que não estão nas listas, e as avulsas (sem conta) */
const extras = [
  ['painel/moodle/conexao', 'administrador'], ['painel/moodle-envios/listar', 'administrador'], ['painel/avisos-sistema/index', 'administrador'],
  ['painel/verificacoes-seguranca/index', 'administrador'], ['painel/bibliotecas/index', 'administrador'], ['painel/integracoes/chaves', 'administrador'],
  ['painel/integracoes/documentacao', 'administrador'], ['painel/primeiros-passos/index', 'administrador'], ['portal/home/index', '260101'],
  ['painel/login/acesso', null], ['portal/login/acesso', null], ['sobre.php', null], ['instalar/', null],
];
for (const [rota, perfil] of extras) { if (!telas.some((x) => x.rota === rota)) { telas.push({ nome: rota.replace(/\W+/g, '-'), rota, perfil }); } }

(async () => {
  const { navegador } = await L.abrir();
  const saida = path.join(L.PROVAS, 'onda3', 'etapa10');
  fs.mkdirSync(saida, { recursive: true });
  const contextos = {};
  async function pagina(perfil, rota) {
    const app = rota.startsWith('portal/') ? 'portal' : 'painel';
    const chave = perfil ? app + ':' + perfil : 'anonimo';
    if (!contextos[chave]) {
      const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
      const p = await ctx.newPage();
      if (perfil) {
        await p.goto(L.BASE + app + '/login/acesso');
        await p.fill('input[name="usuario"]', perfil);
        await p.fill('input[name="senha"]', perfil === 'administrador' ? SENHA : (app === 'portal' ? cred.portal[perfil] : cred.painel[perfil]));
        await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
      }
      contextos[chave] = p;
    }
    return contextos[chave];
  }
  const geral = {};
  for (const tema of temas) {
    const relatorio = [];
    const assinaturas = new Map();
    for (const t of telas) {
      try {
        const p = await pagina(t.perfil, t.rota);
        await p.goto(L.BASE + t.rota, { waitUntil: 'load', timeout: 30000 });
        /* sem as transições do CSS: a cor medida é a final (na tela de verdade, a página já abre no tema) */
        await p.addStyleTag({ content: '*, *::before, *::after { transition: none !important; animation: none !important; }' });
        await p.evaluate((tm) => { if (window.vcTema) { window.vcTema(tm); } else { document.documentElement.setAttribute('data-bs-theme', tm === 'escuro' ? 'dark' : 'light'); } }, tema);
        await p.waitForTimeout(120);
        const v = await contraste(p);
        relatorio.push({ tela: t.rota, perfil: t.perfil, problemas: v });
        for (const x of v) { const k = x.onde + ' | ' + x.cor + ' sobre ' + x.fundo; const a = assinaturas.get(k) || { ...x, telas: 0 }; a.telas++; assinaturas.set(k, a); }
      } catch (e) { relatorio.push({ tela: t.rota, perfil: t.perfil, erro: e.message.slice(0, 120) }); }
    }
    fs.writeFileSync(path.join(saida, `contraste-${tema}.json`), JSON.stringify(relatorio, null, 1));
    const lista = [...assinaturas.values()].sort((a, b) => b.telas - a.telas);
    const comProblema = relatorio.filter((r) => r.problemas && r.problemas.length).length;
    geral[tema] = { telas: relatorio.length, comProblema, assinaturas: lista.length, erros: relatorio.filter((r) => r.erro).length };
    console.log(`\n== ${tema}: ${relatorio.length} telas, ${comProblema} com texto abaixo do mínimo, ${lista.length} tipo(s) de problema, ${geral[tema].erros} tela(s) que não abriram`);
    for (const x of lista.slice(0, 40)) { console.log(`  ${x.telas}x ${x.razao}:1 (mín ${x.minimo}) ${x.onde} — "${x.texto}" ${x.cor} sobre ${x.fundo}`); }
  }
  fs.writeFileSync(path.join(saida, 'contraste-resumo.json'), JSON.stringify(geral, null, 1));
  await navegador.close();
})();
