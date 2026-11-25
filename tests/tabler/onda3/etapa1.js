/*
 * ONDA 3, ETAPA 1 — segurança.
 *   A. o professor só alcança as ofertas a que está ligado: pelo endereço, a LEITURA (atividades, faltas, chamada, médias,
 *      a disciplina, a atividade, o histórico da nota) e a GRAVAÇÃO (a nota na célula, as faltas, o encontro, a fonte da
 *      chamada, cadastrar e apagar atividade, as importações de notas e de faltas, a média) de uma oferta de OUTRO
 *      professor — tudo recusado, e nada muda no banco; a oferta dele continua abrindo e gravando;
 *   B. o professor que também coordena: LÊ a oferta do curso que coordena e NÃO GRAVA nela;
 *   C. o Administrador continua como estava;
 *   D. a API recusa HTTP quando a instalação exige HTTPS (e responde com a exigência desligada);
 *   E. as bibliotecas: as sem uso saíram (pasta e carga automática), o PDF sai, toda biblioteca dos créditos tem a versão
 *      lida do próprio arquivo;
 *   F. sabotagem: sem a conferência do professor no escopo, a prova reprova.
 *
 *   node tests/tabler/onda3/etapa1.js     (depois do ciclo, com a demonstração)
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { execFileSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const ETAPA = 'onda3/etapa1';
const ESCOPO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'helper', 'AdmsEscopo.php');
const CONFIG = path.join(L.RAIZ, 'painel', 'core', 'Config.php');

async function entrar(navegador, usuario) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', cred.painel[usuario]);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/** Abre pelo endereço: true se a tela abriu (não voltou para Minhas disciplinas com a recusa). */
const abre = async (p, rota) => {
  await p.goto(L.BASE + 'painel/' + rota);
  return !(await p.$('#foraDoEscopo')) && !/minhas-disciplinas\/listar/.test(p.url()) && !(await p.$('input[name="SendLogin"]')) && !(await p.$('#semAcesso'));
};
const postar = (p, rota, form, ajax = false) => p.evaluate(async ([u, f, a]) => {
  const h = a ? { 'X-Requested-With': 'XMLHttpRequest' } : {};
  const r = await fetch(u, { method: 'POST', body: new URLSearchParams(f), credentials: 'same-origin', headers: h, redirect: 'manual' });
  return r.status;
}, [L.BASE + 'painel/' + rota, form, ajax]);
const enviarArquivo = (p, rota, campo, nome, conteudo) => p.evaluate(async ([u, c, n, t]) => {
  const fd = new FormData();
  fd.append(c, new Blob([t], { type: 'text/csv' }), n);
  fd.append('EnviarArquivo', '1');
  const r = await fetch(u, { method: 'POST', body: fd, credentials: 'same-origin', redirect: 'manual' });
  return r.status;
}, [L.BASE + 'painel/' + rota, campo, nome, conteudo]);
const foto = () => sql(`SELECT CONCAT_WS('|', (SELECT COUNT(*) FROM rm_disciplina_atividade), (SELECT IFNULL(SUM(nota*10),0) FROM rm_disciplina_atividade_notas),
  (SELECT IFNULL(SUM(faltas),0) FROM rm_disciplina_faltas), (SELECT COUNT(*) FROM rm_oferta_encontros), (SELECT COUNT(*) FROM rm_oferta_presencas),
  (SELECT GROUP_CONCAT(frequencia_fonte ORDER BY id) FROM rm_professores_materias), (SELECT COUNT(*) FROM rm_disciplina_media_final))`);

(async () => {
  const { navegador } = await L.abrir();
  const configOriginal = fs.readFileSync(CONFIG, 'utf8');
  try {
    console.log('\n== Onda 3, Etapa 1');
    const laura = um("SELECT id FROM adms_usuarios WHERE usuario = 'prof.laura.demo'");
    /* a oferta de OUTRO professor (com atividade, nota e aluno) e uma da professora */
    const outra = um(`SELECT o.id FROM rm_professores_materias o JOIN rm_disciplina_atividade a ON a.rm_prof_mat_id = o.id
      WHERE o.encerrada_em IS NULL AND NOT EXISTS (SELECT 1 FROM rm_professores_mat_liga l WHERE l.rm_prof_mat_id = o.id AND l.usuario_id = ${laura} AND l.situacao = 1) ORDER BY o.id LIMIT 1`);
    const minha = um(`SELECT o.id FROM rm_professores_materias o JOIN rm_professores_mat_liga l ON l.rm_prof_mat_id = o.id AND l.usuario_id = ${laura} AND l.situacao = 1
      JOIN rm_disciplina_atividade a ON a.rm_prof_mat_id = o.id WHERE o.encerrada_em IS NULL ORDER BY o.id LIMIT 1`);
    const ativ = um(`SELECT id FROM rm_disciplina_atividade WHERE rm_prof_mat_id = ${outra} ORDER BY id LIMIT 1`);
    const [notaId, notaRm, notaValor] = sql(`SELECT CONCAT_WS('|', id, rm, IFNULL(nota, '')) FROM rm_disciplina_atividade_notas WHERE rm_prof_mat_id = ${outra} ORDER BY id LIMIT 1`).split('|');
    const [minhaNota, , minhaValor] = sql(`SELECT CONCAT_WS('|', id, rm, IFNULL(nota, '')) FROM rm_disciplina_atividade_notas WHERE rm_prof_mat_id = ${minha} AND nota IS NOT NULL ORDER BY id LIMIT 1`).split('|');
    const rmOutra = sql(`SELECT rm FROM portal_disciplinas_alunos WHERE rm_prof_mat_id = ${outra} ORDER BY id LIMIT 1`);
    L.ok(outra > 0 && minha > 0 && outra !== minha && ativ > 0 && +notaId > 0, `a oferta de outro professor (${outra}) e a da professora (${minha}), da demonstração`);

    /* A. leitura e gravação pelo endereço */
    const prof = await entrar(navegador, 'prof.laura.demo');
    const leituras = [`atividades/listar/${outra}`, `faltas/visualizar/${outra}`, `chamada/listar/${outra}`, `medias/final/${outra}`, `disciplinas/visualizar/${outra}`,
      `atividades/visualizar/${ativ}`, `auditoria/nota/${ativ}?rm=${notaRm}`];
    const abriram = [];
    for (const r of leituras) { if (await abre(prof.p, r)) { abriram.push(r); } }
    L.ok(abriram.length === 0, `ler a oferta de outro professor pelo endereço: as ${leituras.length} telas recusadas${abriram.length ? ' — abriram: ' + abriram.join(', ') : ''}`);
    await prof.p.goto(L.BASE + `painel/atividades/listar/${outra}`);
    await L.print(prof.p, ETAPA, 'professor-recusado');
    const antes = foto();
    const st = await postar(prof.p, 'atividades/edit-notas', { id: notaId, nota: notaValor === '9.0' ? '3.0' : '9.0', action: 'edit' }, true);
    await postar(prof.p, `faltas/editar/${outra}`, { [`faltas[${rmOutra}]`]: '7', SalvarFaltas: '1' });
    await postar(prof.p, `chamada/encontro/${outra}`, { data: '2026-05-05', titulo: 'Prova de escopo', SalvarEncontro: '1', [`presente[${rmOutra}]`]: '1' });
    await postar(prof.p, `chamada/fonte/${outra}`, { fonte: 'chamada' });
    await postar(prof.p, `atividades/cadastrar/${outra}?tipo=1`, { nome_atividade: 'Atividade indevida', CadAtividadeNota: '1' });
    await postar(prof.p, `atividades/apagar/${outra}`, { id: String(ativ), ApagarAtividades: '1' });
    await postar(prof.p, `medias/atualizar-lista/${outra}`, { Atualizar: '1' });
    await enviarArquivo(prof.p, `atividades/upload-notas/${ativ}`, 'arquivo', 'notas.csv', `RA;Nota\r\n${notaRm};1,0\r\n`);
    await enviarArquivo(prof.p, `faltas/importar/${outra}`, 'arquivo', 'faltas.csv', `RA;Faltas\r\n${rmOutra};9\r\n`);
    L.ok(st === 403 && foto() === antes && sql(`SELECT IFNULL(nota, '') FROM rm_disciplina_atividade_notas WHERE id = ${notaId}`) === notaValor,
      'gravar na oferta de outro professor pelo endereço (a nota na célula, as faltas, o encontro, a fonte, cadastrar e apagar atividade, a média e as duas importações): recusado, nada muda no banco');
    await prof.p.goto(L.BASE + 'painel/minhas-disciplinas/listar'); // as recusas acima deixaram a mensagem na sessão: ela sai aqui
    L.ok(await abre(prof.p, `atividades/listar/${minha}`) && await abre(prof.p, `faltas/visualizar/${minha}`), 'a oferta da professora continua abrindo');
    const outraNota = minhaValor === '8.0' ? '7.5' : '8.0';
    const st2 = await postar(prof.p, 'atividades/edit-notas', { id: minhaNota, nota: outraNota.replace('.', ','), action: 'edit' }, true);
    const gravou = sql(`SELECT nota FROM rm_disciplina_atividade_notas WHERE id = ${minhaNota}`);
    await postar(prof.p, 'atividades/edit-notas', { id: minhaNota, nota: minhaValor.replace('.', ','), action: 'edit' }, true);
    L.ok(st2 === 200 && Number(gravou) === Number(outraNota) && Number(sql(`SELECT nota FROM rm_disciplina_atividade_notas WHERE id = ${minhaNota}`)) === Number(minhaValor),
      'e a nota da oferta da professora continua gravando (gravada e devolvida)');

    /* B. a professora que também coordena o curso da outra oferta */
    const cursoOutra = um(`SELECT curso_id FROM rm_professores_materias WHERE id = ${outra}`);
    sql(`INSERT INTO rm_cursos_coordenacao (curso_id, usuario_id, posicao_id, situacao, usuario_cre) VALUES (${cursoOutra}, ${laura}, 1, 1, 1)`);
    try {
      const le = await abre(prof.p, `atividades/listar/${outra}`) && await abre(prof.p, `faltas/visualizar/${outra}`);
      await L.print(prof.p, ETAPA, 'professor-coordenador-le');
      const antes2 = foto();
      const st3 = await postar(prof.p, 'atividades/edit-notas', { id: notaId, nota: '1,0', action: 'edit' }, true);
      await postar(prof.p, `faltas/editar/${outra}`, { [`faltas[${rmOutra}]`]: '7', SalvarFaltas: '1' });
      L.ok(le && st3 === 403 && foto() === antes2, 'a professora que coordena o curso: LÊ a oferta do curso e NÃO GRAVA nela (só nas dela)');
    } finally {
      sql(`DELETE FROM rm_cursos_coordenacao WHERE curso_id = ${cursoOutra} AND usuario_id = ${laura}`);
    }
    await prof.ctx.close();

    /* C. o Administrador continua como estava */
    const adm = await entrar(navegador, 'administrador');
    L.ok(await abre(adm.p, `atividades/listar/${outra}`) && await abre(adm.p, `faltas/visualizar/${outra}`) && await abre(adm.p, `chamada/listar/${outra}`), 'o Administrador abre a oferta de qualquer professor, como antes');

    /* E. as bibliotecas */
    const fora = ['microsoft', 'guzzlehttp', 'pear', 'firebase', 'ralouphie', 'psr', 'symfony'].filter((d) => fs.existsSync(path.join(L.RAIZ, 'painel', 'vendor', d)));
    const auto = fs.readdirSync(path.join(L.RAIZ, 'painel', 'vendor', 'composer')).filter((f) => f.endsWith('.php') || f === 'installed.json')
      .filter((f) => /WindowsAzure|GuzzleHttp|\/pear\/|firebase\/php-jwt|ralouphie/.test(fs.readFileSync(path.join(L.RAIZ, 'painel', 'vendor', 'composer', f), 'utf8')));
    L.ok(fora.length === 0 && auto.length === 0 && !/windowsazure/.test(fs.readFileSync(path.join(L.RAIZ, 'painel', 'composer.json'), 'utf8')),
      'as bibliotecas sem uso saíram: as pastas, a carga automática e o composer.json');
    const rmBoletim = sql('SELECT rm FROM col_usuarios WHERE col_niveis_acesso_id = 1 ORDER BY id LIMIT 1');
    const pdf = await adm.p.evaluate(async (u) => { const r = await fetch(u, { credentials: 'same-origin' }); const b = new Uint8Array(await r.arrayBuffer()); return [r.headers.get('content-type'), String.fromCharCode(...b.slice(0, 5))]; },
      L.BASE + 'painel/academicos/imprimir-boletim/' + rmBoletim);
    L.ok(/pdf/.test(pdf[0] || '') && pdf[1] === '%PDF-', 'o gerador de PDF continua: o boletim sai em PDF');
    await adm.p.goto(L.BASE + 'sobre.php');
    const versoes = await adm.p.$$eval('table tbody tr', (t) => t.map((x) => [...x.cells].map((c) => c.textContent.trim())));
    const semVersao = versoes.filter((r) => r.length === 3 && (r[1] === '' || /não identificada/.test(r[1])));
    L.ok(versoes.length >= 13 && semVersao.length === 0, `nos créditos, toda biblioteca com a versão lida do próprio arquivo (${versoes.length} linhas, nenhuma sem versão)`);
    await adm.ctx.close();

    /* D. a API por HTTP com a exigência de HTTPS */
    const chave = 'vce_' + crypto.randomBytes(20).toString('hex');
    sql(`INSERT INTO adms_api_chaves (nome, prefixo, hash, escopo, permissoes, situacao, usuario_id) VALUES ('Prova HTTPS', '${chave.slice(0, 12)}', '${crypto.createHash('sha256').update(chave).digest('hex')}', 'leitura', 'alunos:ler matriculas:ler enturmacao:ler cadastros:ler', 1, 1)`);
    const chamar = async () => { const r = await fetch(L.BASE + 'api/v1/anos-letivos', { headers: { Authorization: 'Bearer ' + chave } }); return [r.status, await r.json().catch(() => null)]; };
    const [s1] = await chamar();
    fs.writeFileSync(CONFIG, configOriginal.replace(/define\('EXIGIR_HTTPS',\s*(true|false)\)/, "define('EXIGIR_HTTPS', true)"));
    const [s2, j2] = await chamar();
    fs.writeFileSync(CONFIG, configOriginal);
    L.ok(s1 === 200 && s2 === 403 && j2 && j2.erro && j2.erro.codigo === 'https_obrigatorio' && 'campo' in j2.erro,
      `a API: com a exigência de HTTPS desligada responde (${s1}); ligada, por HTTP, recusa no formato único (${s2}, https_obrigatorio)`);

    /* F. sabotagem */
    const orig = fs.readFileSync(ESCOPO, 'utf8');
    let reprovou = false;
    try {
      fs.writeFileSync(ESCOPO, orig.replace('if (self::professor()) {', 'if (false) {').replace('return self::recortado() || self::professor();', 'return self::recortado();'));
      const s = await entrar(navegador, 'prof.laura.demo');
      reprovou = await abre(s.p, `atividades/listar/${outra}`);
      await s.ctx.close();
    } finally {
      fs.writeFileSync(ESCOPO, orig);
    }
    L.ok(reprovou && fs.readFileSync(ESCOPO, 'utf8') === orig, 'sabotagem (sem a conferência do professor no escopo, a oferta do outro abre): a prova reprova o defeito; o escopo voltou');
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
  } finally {
    fs.writeFileSync(CONFIG, configOriginal);
    await navegador.close();
    process.exit(L.resumo('onda 3, etapa 1') ? 0 : 1);
  }
})();
