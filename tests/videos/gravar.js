// Grava os vídeos dos guias na DEMONSTRAÇÃO (1.18.0). Ver README.md.
//   node gravar.js                     todos os guias que têm roteiro
//   node gravar.js --guia=<slug>[,..]  só esses
//   node gravar.js --desatualizados    só os sem vídeo ou desatualizados (conferir.js lista quais)
// Antes de abrir o navegador, a guarda (banco.php) confere que o banco em uso é o da demonstração; senão, PARA (código 9).
// Cada guia: retrato do banco → senhas temporárias → preparação do roteiro → gravação → o banco VOLTA ao retrato.
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright-core');
const L = require('./lib');

const args = process.argv.slice(2);
const opt = k => (args.find(a => a.startsWith('--' + k + '=')) || '').split('=')[1];

(async () => {
  /* 1. a guarda, antes de tudo */
  let guarda;
  try { guarda = JSON.parse(L.banco('guarda')); } catch (e) {
    process.stderr.write(String(e.stderr || e.message));
    console.error('Gravação ABORTADA pela guarda: nenhuma tela foi aberta.');
    process.exit(9);
  }
  console.log(`Demonstração: ${guarda.instituicao} · banco ${guarda.banco} · versão ${guarda.versao}`);

  /* 2. quais guias */
  let lista;
  if (opt('guia')) lista = opt('guia').split(',').map(s => s.trim()).filter(Boolean);
  else if (args.includes('--desatualizados')) lista = L.situacoes().filter(s => s.situacao === 'sem vídeo' || s.situacao === 'desatualizado').map(s => s.slug);
  else lista = L.situacoes().filter(s => s.situacao !== 'sem roteiro').map(s => s.slug);
  for (const s of lista) if (!fs.existsSync(path.join(L.ROTEIROS, s + '.js'))) { console.error(`Sem roteiro: ${s}`); process.exit(1); }
  if (!lista.length) { console.log('Nada a gravar.'); return; }

  /* 3. uma gravação por vez (a demonstração é uma só) */
  const trava = path.join(L.C.trabalho, '.gravando');
  for (let espera = 0; ; espera++) { // outra gravação em andamento: espera a vez (até 40 min); trava velha (> 40 min) é tomada
    try { fs.writeFileSync(trava, String(process.pid), { flag: 'wx' }); break; } catch (e) {
      const idade = Date.now() - (fs.statSync(trava, { throwIfNoEntry: false }) || { mtimeMs: Date.now() }).mtimeMs;
      if (idade > 40 * 60000) { fs.rmSync(trava, { force: true }); continue; }
      if (espera === 0) console.log('Outra gravação em andamento: esperando a vez…');
      await new Promise(r => setTimeout(r, 3000));
    }
  }
  const resultado = [];
  // o Chromium completo (canal 'chromium'), sem janela: o headless shell só tem inglês; assim as mensagens do navegador
  // (validação de formulário) saem em português, como no navegador do usuário
  const browser = await chromium.launch({ headless: true, channel: 'chromium', args: ['--lang=pt-BR'] });
  try {
    const ids = JSON.parse(L.banco('ids'));
    for (const slug of lista) {
      const retrato = path.join(L.C.trabalho, 'retrato.sql');
      const roteiro = require(path.join(L.ROTEIROS, slug + '.js'));
      console.log(`\n== ${slug}`);
      L.banco('retrato', retrato);
      try {
        const contas = [...new Set([roteiro.conta, ...(roteiro.contas || [])].filter(Boolean))];
        const senhas = contas.length ? JSON.parse(L.banco('senhas', JSON.stringify(contas))) : {};
        if (roteiro.sql) {
          const f = path.join(L.C.trabalho, 'preparar.sql');
          fs.writeFileSync(f, typeof roteiro.sql === 'function' ? roteiro.sql(ids) : roteiro.sql);
          L.banco('sql', f);
        }
        /* Onda 5: o endereço e a chave do Moodle de teste (do ambiente) são mascarados em toda tela gravada */
        const murl = (process.env.VCOSTAEDU_MOODLE_URL || '').trim();
        const segredos = [murl, (process.env.VCOSTAEDU_MOODLE_CHAVE || '').trim()];
        try { segredos.push(new URL(murl).host); } catch (e) { /* sem Moodle */ }
        const g = new L.Gravacao({ browser, slug, roteiro, ids, senhas, versao: guarda.versao, segredos: segredos.filter(Boolean) });
        g.guiaJanela = L.cabecalhoGuia(slug).titulo || ''; // Onda 5: o nome do guia no alto da janela de narração
        await g.abrir();
        try { await roteiro.gravar(g); } catch (e) {
          /* o diagnóstico da falha (fora do versionado): o que se esperava, a tela e o HTML, para consertar o roteiro */
          try {
            const base = path.join(L.C.trabalho, 'falha-' + slug);
            const espera = String(e.message || e).split('\n').filter(l => /waiting for|locator|selector/i.test(l)).slice(0, 3).join(' | ');
            fs.writeFileSync(base + '.txt', `${g.page.url()}\n${espera}\n${String(e.stack || e)}\n`);
            await g.page.screenshot({ path: base + '.png', fullPage: true }).catch(() => {});
            fs.writeFileSync(base + '.html', await g.page.content().catch(() => ''));
            console.error('   ESPERAVA: ' + espera);
          } catch (x) { /* o diagnóstico não muda o resultado */ }
          await g.ctx.close().catch(() => {}); throw e;
        }
        const r = await g.fechar();
        const cab = L.cabecalhoGuia(slug);
        const m = L.lerManifesto();
        m.videos[slug] = {
          titulo: roteiro.titulo || cab.titulo, perfis: cab.perfis.split(',').map(s => s.trim()),
          arquivo: slug + '.mp4', poster: slug + '.jpg', legenda: slug + '.vtt', capitulos_vtt: slug + '.capitulos.vtt',
          duracao: r.duracao, tamanho: r.tamanho, versao: guarda.versao, gravado_em: new Date().toISOString().slice(0, 10),
          hash_roteiro: L.hashRoteiro(slug), hash_guia: L.hashGuia(slug), capitulos: r.capitulos, transcricao: r.transcricao,
        };
        L.gravarManifesto(m);
        const mbMin = r.tamanho / 1048576 / (r.duracao / 60);
        resultado.push({ slug, duracao: r.duracao, tamanho: r.tamanho, capitulos: r.capitulos.length, situacao: 'OK' + (r.duracao > 180 ? ' (acima de 3 min)' : '') + (mbMin > 3 ? ` (${mbMin.toFixed(1)} MB/min)` : '') });
        console.log(`   OK: ${r.duracao}s, ${(r.tamanho / 1048576).toFixed(2)} MB, ${r.capitulos.length} capítulo(s)`);
      } catch (e) {
        resultado.push({ slug, situacao: 'FALHOU: ' + String(e.message || e).split('\n')[0] });
        console.error(`   FALHOU: ${String(e.message || e).split('\n')[0]}`);
      } finally {
        console.log('   banco: ' + L.banco('restaurar', retrato));
      }
    }
  } finally {
    await browser.close();
    fs.rmSync(trava, { force: true });
    for (const f of fs.readdirSync(L.C.trabalho)) if (/^quadros_/.test(f)) fs.rmSync(path.join(L.C.trabalho, f), { recursive: true, force: true }); // quadros intermediários
  }
  fs.writeFileSync(path.join(L.C.trabalho, 'ultima_gravacao.json'), JSON.stringify(resultado, null, 2));
  console.log('\nGuia                                      duração  tamanho   cap.  situação');
  for (const r of resultado) console.log(`${r.slug.padEnd(42)}${String(r.duracao ?? '-').padStart(6)}s ${r.tamanho ? (r.tamanho / 1048576).toFixed(2).padStart(6) + ' MB' : '        -'} ${String(r.capitulos ?? '-').padStart(5)}  ${r.situacao}`);
  if (resultado.some(r => r.situacao.startsWith('FALHOU'))) process.exitCode = 1;
})();
