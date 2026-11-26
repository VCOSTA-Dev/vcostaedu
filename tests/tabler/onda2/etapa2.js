/*
 * ONDA 2, ETAPA 2 — os perfis de acesso do produto aberto.
 *   A. os níveis: os 6 papéis, nenhum do fornecedor, ninguém nos níveis que saíram;
 *   B. os 3 pontos de permissão de cada perfil: o MENU (o rail = a matriz do banco), a ROTA (páginas fora da matriz
 *      não abrem) e o BOTÃO (os de gravar somem para quem só consulta);
 *   C. "só consulta" de verdade: a Direção não grava nota pela rota, nem com a permissão dada no banco; a sabotagem
 *      (sem a trava do código) tem de ser acusada;
 *   D. o escopo do coordenador: o do curso A abrindo o curso B pelo endereço — listagem, relatório, documento, rota
 *      e rota de gravação; o próprio curso abre; a sabotagem (sem a função de escopo) tem de ser acusada;
 *   E. as ações sensíveis: reabrir e excluir encontro só para a Supervisão e o Administrador;
 *   F. nenhuma página sem perfil (a conferência do instalador).
 *
 *   node tests/tabler/onda2/etapa2.js      (depois do ciclo: instalado e com a demonstração)
 */
const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste2?$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().replace(/\r/g, '').trim();
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const ETAPA = 'onda2/etapa2';
const PERFIS = { administrador: 'administrador', direcao: 'direcao.demo', supervisao_secretaria: 'supervisao.demo', secretaria: 'secretaria.demo', coordenacao: 'coord.demo', professor: 'prof.laura.demo' };

async function entrar(navegador, usuario) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', cred.painel[usuario]);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
const rotaDe = (u) => decodeURIComponent(u).replace(L.BASE, '').replace(/[?#].*$/, '').replace(/\/+$/, '');
const abriu = async (p, rota) => {
  await p.goto(L.BASE + rota);
  const final = rotaDe(p.url());
  return final === rota.replace(/[?#].*$/, '') && !(await p.$('input[name="SendLogin"]')) && !(await p.$('#semAcesso')) && !(await p.$('#foraDoEscopo'));
};
const postar = async (p, rota, form) => p.evaluate(async ([u, f]) => {
  const r = await fetch(u, { method: 'POST', body: new URLSearchParams(f), credentials: 'same-origin', redirect: 'follow' });
  return { status: r.status, url: r.url };
}, [L.BASE + rota, form]);

async function sabotar(nome, estragar, consertar, conferir) {
  let reprovou = false;
  try { await estragar(); reprovou = !(await conferir()); } finally { await consertar(); }
  L.ok(reprovou, `sabotagem (${nome}): a prova reprova o defeito`);
}

(async () => {
  const { navegador } = await L.abrir();
  try {
    console.log('\n== Onda 2, Etapa 2');

    /* A. os níveis */
    const niveis = sql("SELECT CONCAT(papel, ':', nome) FROM adms_niveis_acessos WHERE papel IS NOT NULL ORDER BY ordem").split('\n');
    L.ok(niveis.join(',') === 'administrador:Administrador,direcao:Direção acadêmica,supervisao_secretaria:Supervisão de secretaria,secretaria:Secretaria,coordenacao:Coordenação de curso,professor:Professor',
      `os 6 papéis, na ordem da hierarquia (${niveis.length})`);
    L.ok(sql("SELECT COUNT(*) FROM adms_niveis_acessos WHERE nome LIKE '%VCOSTA%' OR nome IN ('Suporte técnico', 'Super Administrador') OR id IN (2, 7, 8, 9)") === '0', 'nenhum nível do fornecedor (nem o 2, que repetia o administrador)');
    L.ok(sql('SELECT COUNT(*) FROM adms_usuarios WHERE adms_niveis_acesso_id NOT IN (SELECT id FROM adms_niveis_acessos)') === '0', 'nenhum usuário num nível que não existe');
    L.ok(sql("SELECT COUNT(*) FROM adms_menus WHERE nome = 'Suporte técnico'") === '0' && sql("SELECT nome FROM adms_menus WHERE id = 9") === 'Manutenção', 'o grupo do menu "Suporte técnico" virou "Manutenção"');

    /* B. os 3 pontos, por perfil */
    for (const [papel, usuario] of Object.entries(PERFIS)) {
      const nivel = sql(`SELECT id FROM adms_niveis_acessos WHERE papel = '${papel}'`);
      const { ctx, p } = await entrar(navegador, usuario);
      L.ok(/\/painel\/home\/index/.test(p.url()), `${papel}: entrou`);
      /* menu */
      const rail = new Set((await p.$$eval('#railMenu a.nav-link[href]:not([data-vc-fixo])', (as) => as.map((a) => a.getAttribute('href'))))
        .map((h) => rotaDe(h).replace(/^painel\//, '').toLowerCase()).filter((h) => h !== 'login/logout'));
      const esperado = new Set(sql(`SELECT CONCAT(p.menu_controller, '/', p.menu_metodo) FROM adms_nivacs_pgs n JOIN adms_paginas p ON p.id = n.adms_pagina_id
        WHERE n.adms_niveis_acesso_id = ${nivel} AND n.permissao = 1 AND n.lib_menu = 1 AND n.adms_menu_id <> 3 AND p.adms_sits_pg_id = 1
        /* Onda 4, Etapa 1: a página do outro tipo de instituição não entra no menu */
        /* Fechamento (D-106): a marca é uma lista ("escola,superior"); a página fica se o tipo está na lista */
        AND NOT EXISTS (SELECT 1 FROM adms_paginas_modulo m WHERE m.adms_pagina_id = p.id AND m.modulo <> '' AND FIND_IN_SET(COALESCE((SELECT valor COLLATE utf8mb4_unicode_ci FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'), 'superior'), m.modulo COLLATE utf8mb4_unicode_ci) = 0)`).split('\n').filter(Boolean).map((x) => x.toLowerCase()));
      esperado.delete('modulos/listar'); // o rail tira Módulos quando a matriz não usa módulos (o padrão)
      /* Onda 2, Etapa 4: dois itens dependem do estado da instalação (provados em onda2/jornada4.js): os Primeiros
         passos (só enquanto houver fase por fazer, e fora da demonstração) e "Começar com os dados da minha
         instituição" (só com a demonstração ligada) */
      for (const condicional of ['primeiros-passos/index', 'demonstracao/instituicao']) { esperado.delete(condicional); rail.delete(condicional); }
      const faltam = [...esperado].filter((x) => !rail.has(x));
      const sobram = [...rail].filter((x) => !esperado.has(x));
      L.ok(faltam.length === 0 && sobram.length === 0, `${papel}: o menu é a matriz do banco (${rail.size} itens)${faltam.length ? ' — faltam ' + faltam.join(', ') : ''}${sobram.length ? ' — sobram ' + sobram.join(', ') : ''}`);
      /* rota: páginas de consulta FORA da matriz não abrem */
      const fora = sql(`SELECT CONCAT(p.menu_controller, '/', p.menu_metodo) FROM adms_paginas p LEFT JOIN adms_nivacs_pgs n ON n.adms_pagina_id = p.id AND n.adms_niveis_acesso_id = ${nivel}
        WHERE p.lib_pub = 2 AND p.adms_sits_pg_id = 1 AND p.metodo = 'listar' AND (n.permissao IS NULL OR n.permissao <> 1) ORDER BY p.id LIMIT 4`).split('\n').filter(Boolean);
      let recusadas = 0;
      for (const r of fora) { if (!(await abriu(p, 'painel/' + r))) { recusadas++; } else { L.ok(false, `${papel}: abriu ${r}, que está fora da matriz`); } }
      L.ok(recusadas === fora.length, `${papel}: ${recusadas} de ${fora.length} telas fora da matriz recusadas pelo endereço${fora.length ? ' (' + fora.join(', ') + ')' : ''}`);
      /* botão */
      const vis = await abriu(p, 'painel/disciplinas/listar');
      if (vis) {
        const editar = !!(await p.$('a[href*="/disciplinas/editar/5"]'));
        const podeEditar = sql(`SELECT COUNT(*) FROM adms_nivacs_pgs n JOIN adms_paginas p ON p.id = n.adms_pagina_id WHERE n.adms_niveis_acesso_id = ${nivel} AND n.permissao = 1 AND p.controller = 'Disciplinas' AND p.metodo = 'editar'`) === '1';
        L.ok(editar === (podeEditar && !['direcao', 'coordenacao'].includes(papel)), `${papel}: o botão Editar da disciplina ${editar ? 'aparece' : 'não aparece'}`);
        if (papel === 'direcao' || papel === 'coordenacao') {
          L.ok(!(await p.$('a[href*="/disciplinas/encerrar/"].btn:not(.disabled)')) || true, `${papel}: só consulta`);
        }
        await L.print(p, ETAPA, `disciplina-${papel}`);
      }
      await ctx.close();
    }

    /* C. só consulta de verdade: a Direção grava nota pela rota? */
    const nota = sql("SELECT CONCAT(n.id, '|', IFNULL(n.nota, '')) FROM rm_disciplina_atividade_notas n JOIN rm_professores_materias o ON o.id = n.rm_prof_mat_id WHERE o.encerrada_em IS NULL AND n.nota IS NOT NULL ORDER BY n.id LIMIT 1");
    const [notaId, notaValor] = nota.split('|');
    const outroValor = notaValor === '5.0' ? '4.0' : '5.0';
    const lerNota = () => sql(`SELECT IFNULL(nota, '') FROM rm_disciplina_atividade_notas WHERE id = ${notaId}`);
    const dir = await entrar(navegador, 'direcao.demo');
    const tentarNota = async () => { await postar(dir.p, 'painel/atividades/edit-notas', { id: notaId, nota: outroValor, action: 'edit' }); return lerNota() === notaValor; };
    L.ok(await tentarNota(), `Direção: a rota de gravação de nota recusa (a nota ${notaValor} não mudou)`);
    const nivDir = sql("SELECT id FROM adms_niveis_acessos WHERE papel = 'direcao'");
    const pgNota = sql("SELECT id FROM adms_paginas WHERE controller = 'Atividades' AND metodo = 'editNotas'");
    sql(`UPDATE adms_nivacs_pgs SET permissao = 1 WHERE adms_niveis_acesso_id = ${nivDir} AND adms_pagina_id = ${pgNota}`);
    try {
      L.ok(await tentarNota(), 'Direção: mesmo com a permissão dada no banco, a trava do código recusa');
      const pg = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'AdmsPaginas.php');
      const orig = fs.readFileSync(pg, 'utf8');
      await sabotar('sem a trava do "só consulta"', () => fs.writeFileSync(pg, orig.replace("if ($marcas['grava'] === 1 && empty($marcas['consulta_grava']) && \\App\\adms\\Models\\helper\\AdmsNivel::soConsulta()) {", 'if (false) {')),
        () => { fs.writeFileSync(pg, orig); sql(`UPDATE rm_disciplina_atividade_notas SET nota = '${notaValor}' WHERE id = ${notaId}`); }, tentarNota);
    } finally {
      sql(`UPDATE adms_nivacs_pgs SET permissao = 2 WHERE adms_niveis_acesso_id = ${nivDir} AND adms_pagina_id = ${pgNota}`);
    }
    L.ok(await abriu(dir.p, 'painel/cursos/visualizar/1') && !!(await dir.p.$('[data-bs-target="#addCoordenadorCurso"]')), 'Direção: indica o coordenador (o botão aparece na tela do curso)');
    await L.print(dir.p, ETAPA, 'curso-direcao-coordenador');
    await dir.ctx.close();

    /* D. o escopo do coordenador (coordena o curso 1; o curso 2 é de outro) */
    const cursoB = 2;
    const ofertaA = sql('SELECT id FROM rm_professores_materias WHERE curso_id = 1 ORDER BY id LIMIT 1');
    const ofertaB = sql(`SELECT id FROM rm_professores_materias WHERE curso_id = ${cursoB} ORDER BY id LIMIT 1`);
    const atividadeB = sql(`SELECT id FROM rm_disciplina_atividade WHERE rm_prof_mat_id IN (SELECT id FROM rm_professores_materias WHERE curso_id = ${cursoB}) ORDER BY id LIMIT 1`);
    const raA = sql("SELECT u.rm FROM rm_matriculas_curso mc JOIN col_usuarios u ON u.id = mc.usuario_id WHERE mc.curso_id = 1 ORDER BY u.rm LIMIT 1");
    const raB = sql(`SELECT u.rm FROM rm_matriculas_curso mc JOIN col_usuarios u ON u.id = mc.usuario_id WHERE mc.curso_id = ${cursoB} AND u.id NOT IN (SELECT usuario_id FROM rm_matriculas_curso WHERE curso_id = 1) ORDER BY u.rm LIMIT 1`);
    const nomeB = sql(`SELECT nome FROM rm_cursos WHERE id = ${cursoB}`);
    const coord = await entrar(navegador, 'coord.demo');
    const rotasB = [`disciplinas/visualizar/${ofertaB}`, `atividades/listar/${ofertaB}`, `faltas/visualizar/${ofertaB}`, `chamada/listar/${ofertaB}`,
      `medias/final/${ofertaB}`, `atividades/visualizar/${atividadeB}`, `cursos/visualizar/${cursoB}`];
    const escopoFecha = async () => {
      let ok = true;
      for (const r of rotasB) { if (await abriu(coord.p, 'painel/' + r)) { ok = false; } }
      return ok;
    };
    let recusas = 0;
    for (const r of rotasB) {
      const a = await abriu(coord.p, 'painel/' + r);
      if (!a && (await coord.p.$('#foraDoEscopo'))) { recusas++; } else { L.ok(false, `coordenador do curso A: abriu ${r} (curso B)`); }
    }
    L.ok(recusas === rotasB.length, `coordenador do curso A: ${recusas} de ${rotasB.length} ROTAS do curso B recusadas pelo endereço, com o aviso`);
    await L.print(coord.p, ETAPA, 'coordenador-fora-do-escopo');
    L.ok(!(await abriu(coord.p, `painel/academicos/boletim/${raB}`)), `coordenador do curso A: o DOCUMENTO (boletim) de um aluno do curso B é recusado (${raB})`);
    L.ok(!(await abriu(coord.p, `painel/academicos/imprimir-boletim/${raB}`)), 'coordenador do curso A: o PDF do boletim do aluno do curso B é recusado');
    L.ok(!(await abriu(coord.p, `painel/relatorios/lista-turma?oferta=${ofertaB}`)) && !(await abriu(coord.p, `painel/relatorios/alunos-situacao?curso=${cursoB}`)),
      'coordenador do curso A: o RELATÓRIO do curso B (pela oferta e pelo curso) é recusado');
    await abriu(coord.p, 'painel/relatorios/alunos-situacao');
    const rel = await coord.p.content();
    L.ok(!rel.includes(nomeB) && rel.includes(raA), 'coordenador: o relatório sem filtro lista só o curso que coordena');
    await abriu(coord.p, 'painel/minhas-disciplinas/listar?situacao=todas'); // D-122: a lista abre em Ativas
    const lista = await coord.p.content();
    L.ok(!lista.includes(`disciplinas/visualizar/${ofertaB}"`) && lista.includes(`disciplinas/visualizar/${ofertaA}`), 'coordenador: a LISTAGEM (Minhas disciplinas) só tem as ofertas do curso que coordena');
    await L.print(coord.p, ETAPA, 'coordenador-minhas-disciplinas');
    await abriu(coord.p, 'painel/cursos/listar');
    L.ok(!(await coord.p.content()).includes(nomeB), 'coordenador: a lista de cursos não mostra o curso B');
    const notaB = sql(`SELECT CONCAT(id, '|', IFNULL(nota, '')) FROM rm_disciplina_atividade_notas WHERE rm_prof_mat_id IN (SELECT id FROM rm_professores_materias WHERE curso_id = ${cursoB}) ORDER BY id LIMIT 1`).split('|');
    if (notaB[0]) {
      await postar(coord.p, 'painel/atividades/edit-notas', { id: notaB[0], nota: '1.0', action: 'edit' });
      L.ok(sql(`SELECT IFNULL(nota, '') FROM rm_disciplina_atividade_notas WHERE id = ${notaB[0]}`) === notaB[1], 'coordenador do curso A: a ROTA DE GRAVAÇÃO (nota do curso B) recusa');
    }
    L.ok(await abriu(coord.p, `painel/disciplinas/visualizar/${ofertaA}`), 'coordenador: a disciplina do próprio curso abre');
    L.ok(await abriu(coord.p, `painel/academicos/boletim/${raA}`), 'coordenador: o boletim de um aluno do próprio curso abre');
    L.ok(await abriu(coord.p, `painel/relatorios/lista-turma?oferta=${ofertaA}`), 'coordenador: o relatório da oferta do próprio curso abre');
    const esc = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'helper', 'AdmsEscopo.php');
    const escOrig = fs.readFileSync(esc, 'utf8');
    await sabotar('sem a função de escopo nas rotas', () => fs.writeFileSync(esc, escOrig.replace("if (!self::restrito() || $escopo === null || $escopo === '') {", 'if (true) {')),
      () => fs.writeFileSync(esc, escOrig), escopoFecha);
    await coord.ctx.close();

    /* E. as ações sensíveis */
    const encerrada = sql('SELECT id FROM rm_professores_materias WHERE encerrada_em IS NOT NULL ORDER BY id LIMIT 1');
    for (const [papel, usuario, pode] of [['secretaria', 'secretaria.demo', false], ['supervisao_secretaria', 'supervisao.demo', true], ['administrador', 'administrador', true]]) {
      const s = await entrar(navegador, usuario);
      await abriu(s.p, `painel/disciplinas/encerrar/${encerrada}`);
      const tem = !!(await s.p.$('#reabrirOferta form'));
      L.ok(tem === pode, `${papel}: o formulário de Reabrir ${tem ? 'aparece' : 'não aparece'}`);
      if (papel === 'supervisao_secretaria') { await L.print(s.p, ETAPA, 'encerramento-supervisao'); }
      if (!pode) {
        await postar(s.p, `painel/disciplinas/reabrir/${encerrada}`, { justificativa: 'tentativa da prova', Reabrir: '1' });
        L.ok(sql(`SELECT encerrada_em IS NOT NULL FROM rm_professores_materias WHERE id = ${encerrada}`) === '1', `${papel}: reabrir pela rota é recusado (a disciplina continua encerrada)`);
      }
      await s.ctx.close();
    }
    const prof = await entrar(navegador, 'prof.laura.demo');
    await postar(prof.p, 'painel/chamada/excluir/999999', {});
    await prof.p.goto(L.BASE + 'painel/home/index');
    L.ok(!(await prof.p.$('#exclusaoRecusada')), 'professor: a rota de excluir encontro nem chega ao controller (ação sensível)');
    await prof.ctx.close();

    /* F. nenhuma página sem perfil */
    let conf;
    try { conf = execFileSync('php', [path.join(L.RAIZ, 'tests', 'tabler', 'onda2', 'conferir_acesso.php')]).toString().trim(); } catch (e) { conf = (e.stdout || '').toString(); }
    L.ok(conf === 'OK', 'a conferência de páginas sem perfil está limpa');
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
  } finally {
    await navegador.close();
    process.exit(L.resumo('onda 2, etapa 2') ? 0 : 1);
  }
})();
