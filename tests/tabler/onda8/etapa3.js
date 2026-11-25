/*
 * PROVA DA ONDA 8, ETAPA 3 — o atestado de matrícula na demonstração da escola (D-69). APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela: cada aluno fictício tem um CPF de demonstração (000 + a matrícula,
 *      com os dois verificadores válidos) e a previsão de conclusão coerente com a série (o fim do ano da última série
 *      do segmento);
 *   2. a dica da Página Inicial cita o atestado na escola (para o aluno e para o responsável de dois filhos);
 *   3. o aluno emite o atestado pelo portal, e o PDF abre;
 *   4. o responsável emite o atestado do filho pelo portal da família, e o PDF abre;
 *   5. a régua da escola, contra a de antes da etapa: muda SÓ a previsão de conclusão (o CPF é coluna volátil na régua);
 *   sabotagem: a demonstração montada sem a previsão de conclusão — a prova acusa (o atestado do aluno é recusado, com
 *   a razão); o arquivo volta igual (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa3';
const SAIDA = path.join(__dirname, '..', 'saida');
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const PHP = 'C:/xampp/php/php.exe';
const CENARIO = path.join(L.RAIZ, 'database', 'ferramentas', 'demonstracao_escola.php');
const ANTES = path.join(SAIDA, 'regua-escola-antes-onda8-etapa3.json');
const CONFIG = 'adms_versoes,adms_novidades,adms_paginas,adms_nivacs_pgs,adms_acoes,adms_instalacao_estado,adms_paginas_modulo,adms_fases,adms_paginas_recurso,col_paginas_modulo,rm_media_regras,rm_media_regra_versoes,rm_media_regra_componentes,rm_media_regra_acrescimo,adms_parametros,col_niveis_acessos,col_paginas,col_nivacs_pgs'
  + ',rm_cursos_periodos,rm_professores_horario_aula'; // Onda 8, correção (D-71): os turnos e o horário padrão são configuração
const ANO = 2026;

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).trim();
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
function cpfValido(c) {
  if (!/^\d{11}$/.test(c) || /^(\d)\1{10}$/.test(c)) return false;
  for (let t = 9; t < 11; t++) {
    let s = 0;
    for (let i = 0; i < t; i++) s += +c[i] * (t + 1 - i);
    if (((10 * s) % 11) % 10 !== +c[t]) return false;
  }
  return true;
}
/** O dia local de uma data, AAAA-MM-DD. */
const dia = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
/**
 * A régua de antes foi gravada num dia; a nova pode ser de outro. As datas que a montagem grava como "hoje" (o início
 * de uma designação, a data do conselho, o ato legal "em DD/MM/AAAA") contam como o mesmo dia: o dia da régua de antes
 * (o do arquivo) vira o dia de hoje, nos dois formatos, antes de comparar.
 */
function mesmoDia(texto, quando) {
  const iso = dia(quando);
  const hoje = dia(new Date());
  if (iso === hoje) return texto;
  const br = (d) => d.split('-').reverse().join('/');
  /* o PHP grava a barra escapada no JSON ("20\/11\/2026") */
  const esc = (d) => br(d).split('/').join('\\/');
  return texto.split(iso).join(hoje).split(br(iso)).join(br(hoje)).split(esc(iso)).join(esc(hoje));
}
/**
 * As diferenças entre dois retratos da régua, coluna a coluna: "tabela/linha.coluna" (as linhas das tabelas) e
 * "vidas/id.caminho" ou "boletins/id.caminho". As tabelas de configuração (CONFIG) ficam fora: mudam de propósito.
 */
function diferencas(a, b) {
  const out = [];
  const fora = new Set(CONFIG.split(','));
  const fundo = (x, y, cam) => {
    if (JSON.stringify(x) === JSON.stringify(y)) return;
    if (x && y && typeof x === 'object' && typeof y === 'object') {
      for (const k of new Set([...Object.keys(x), ...Object.keys(y)])) fundo(x[k], y[k], cam + '.' + k);
      return;
    }
    /* o alerta dos dados pessoais incompletos perde o "CPF" da lista (e só ele): a consequência do CPF preenchido */
    if (typeof x === 'string' && typeof y === 'string' && /\.alertas\.\d+$/.test(cam) && x.replace(/CPF, |, CPF/, '') === y) { out.push(cam + ':semCpf'); return; }
    /* Onda 8, correção, Parte 4 (D-84): um professor fictício por disciplina — o professor que lança (o usuário das
       atividades, das notas, das faltas e da ligação) e o nome do docente (nas vidas e no alerta "sem titulação") mudam
       de um professor fictício para outro; o resto da linha, igual */
    const fict = /^Prof(a)?\. .+ \(fictíci[ao]\)$/;
    const doc = /^(.+): docente (Prof(?:a)?\. .+ \(fictíci[ao]\)) sem titulação\.$/;
    if (Number.isInteger(x) && Number.isInteger(y) && /^(rm_disciplina_atividade\/\d+\.usuario_cre|rm_disciplina_atividade_notas\/\d+\.usuario_(cre|alt|mod)|rm_disciplina_faltas\/\d+\.usuario_cre|rm_professores_mat_liga\/\d+\.usuario_id)$/.test(cam)) { out.push(cam + ':professor'); return; }
    if (typeof x === 'string' && typeof y === 'string' && /\.docentes\.\d+\.nome$/.test(cam) && fict.test(x) && fict.test(y)) { out.push(cam + ':professor'); return; }
    if (typeof x === 'string' && typeof y === 'string' && /\.alertas\.\d+$/.test(cam) && doc.test(x) && doc.test(y) && x.match(doc)[1] === y.match(doc)[1]) { out.push(cam + ':professor'); return; }
    /* Onda 8, correção, Parte 4 (D-85): na escola, "disciplina" — os dois textos da demonstração, palavra por palavra */
    if ((/^esc_conselho\/\d+\.observacao$/.test(cam) && x === 'O conselho considerou a evolução no segundo semestre e o desempenho nos outros componentes (demonstração).' && y === 'O conselho considerou a evolução no segundo semestre e o desempenho nas outras disciplinas (demonstração).')
      || (/^rm_materias\/\d+\.descricao$/.test(cam) && x === 'Componente curricular da demonstração' && y === 'Disciplina da demonstração')) { out.push(cam + ':disciplina'); return; }
    out.push(cam);
  };
  const chave = (l) => { const o = JSON.parse(l); const k = Object.keys(o)[0]; return k + '=' + o[k]; };
  for (const t of new Set([...Object.keys(a.conteudo || {}), ...Object.keys(b.conteudo || {})])) {
    if (fora.has(t)) continue;
    const la = (a.conteudo[t] || []);
    const lb = (b.conteudo[t] || []);
    /* a etapa nova (D-70) acrescenta o curso livre: a régua ordena as linhas pelo conteúdo, então as linhas novas
       podem cair no meio. As linhas iguais saem da conta; cada linha de antes que mudou é comparada, coluna a coluna,
       com a linha nova da mesma chave (a primeira coluna); as que sobram no retrato novo são o curso livre */
    const sa = new Set(la);
    const sb = new Set(lb);
    const novas = new Map(lb.filter((l) => !sa.has(l)).map((l) => [chave(l), l]));
    la.forEach((l, i) => {
      if (sb.has(l)) return;
      const par = novas.get(chave(l));
      if (par === undefined) { out.push(`${t}/${i}(saiu)`); return; }
      novas.delete(chave(l));
      fundo(JSON.parse(l), JSON.parse(par), `${t}/${i}`);
    });
    /* a correção (D-71) acrescenta o horário da semana das turmas de 2026: as linhas novas da grade */
    if (novas.size) { out.push(t === 'rm_professores_horarios' ? `${t}:horario` : `${t}:cursoLivre`); }
  }
  for (const s of ['vidas', 'boletins']) fundo(a[s] || {}, b[s] || {}, s);
  return out;
}
const montar = (rotulo) => spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', rotulo], { encoding: 'utf8', maxBuffer: 1 << 24 });
async function entrar(navegador, lado, usuario, senha, largura = 1366) {
  const ctx = await navegador.newContext({ viewport: { width: largura, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + lado + '/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/** O PDF do link: o tamanho em bytes (0 = não abriu como PDF). */
const pdfDe = (p, href) => p.evaluate(async (u) => { const x = await fetch(u, { credentials: 'same-origin' }); return x.ok && /pdf/.test(x.headers.get('content-type') || '') ? (await x.arrayBuffer()).byteLength : 0; }, href);
/** O aluno emite o atestado pelo portal: o tamanho do PDF e o texto da página (a recusa, se houver). */
async function atestadoDoAluno(navegador, ra, senha) {
  const a = await entrar(navegador, 'portal', ra, senha, 390);
  await a.p.goto(L.BASE + 'portal/atestado/index');
  const emitir = await a.p.$('button[name="EmitirAtestado"]');
  if (emitir) { await Promise.all([a.p.waitForNavigation(), emitir.click()]); }
  const texto = await a.p.textContent('body');
  const href = await a.p.getAttribute('#atestadosEmitidos a', 'href').catch(() => '');
  const bytes = href ? await pdfDe(a.p, href) : 0;
  return { a, texto, bytes };
}

(async () => {
  const { navegador } = await L.abrir();
  const shaCenario = sha(CENARIO);
  try {
    if (!fs.existsSync(ANTES)) { throw new Error('falta a régua da escola de antes da etapa (' + path.basename(ANTES) + ')'); }
    /* 1. a demonstração do colégio, com os dois campos */
    const r = montar('onda8-etapa3');
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela');
    const linhas = sql(`SELECT u.rm, IFNULL(i.cpf,''), IFNULL(mc.previsao_conclusao,'')
      FROM col_usuarios u LEFT JOIN col_usuarios_informacoes i ON i.col_user_id = u.id JOIN rm_matriculas_curso mc ON mc.usuario_id = u.id
      WHERE u.rm REGEXP '^2[56]50[0-9]{2}$' ORDER BY u.rm`).split('\n').filter(Boolean).map((l) => l.split('\t').map((x) => x.trim()));
    /* a previsão esperada, pela turma de cada aluno (database/ferramentas/demonstracao_escola.php, TURMAS): o 6º ano de
       2026 termina o fundamental em 2029; o 9º ano de 2026, em 2026; a 1ª série de 2026, o médio em 2028; o 9º ano de
       2025, em 2025 */
    const esperada = (ra) => ({ 26500: '2029', 26501: '2029', 26502: '2026', 26503: '2028', 26504: '2027', 25500: '2025' })[ra.slice(0, 5)] + '-12-15';
    const ruins = linhas.filter(([ra, cpf, prev]) => !(cpfValido(cpf) && cpf.slice(0, 9) === '000' + ra.padStart(6, '0') && prev === esperada(ra)));
    L.ok(linhas.length >= 19 && ruins.length === 0,
      `os ${linhas.length} alunos fictícios com o CPF de demonstração (000 + a matrícula, verificadores válidos) e a previsão coerente com a série (ex.: ${linhas[0] ? linhas[0][0] + ' → ' + linhas[0][1].slice(0, 3) + '…, ' + linhas[0][2] : '—'})${ruins.length ? '; fora: ' + ruins.map((x) => x.join('/')).join('; ') : ''}`);

    /* 5 (logo depois da montagem, antes de a prova emitir qualquer coisa). A régua da escola, contra a de antes da
       etapa: o retrato novo é gravado e comparado coluna a coluna (as tabelas de configuração mudam de propósito: a
       versão e a novidade). Mudam só os dois campos: a previsão de conclusão e o CPF. */
    const NOVA = path.join(SAIDA, 'regua-escola-onda8-etapa3.json');
    const grav = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'regua.php'), 'gravar', NOVA], { encoding: 'utf8', cwd: L.RAIZ, maxBuffer: 1 << 26 });
    const mudou = grav.status === 0 ? diferencas(JSON.parse(mesmoDia(fs.readFileSync(ANTES, 'utf8'), fs.statSync(ANTES).mtime)), JSON.parse(fs.readFileSync(NOVA, 'utf8'))) : ['(a régua não foi gravada)'];
    fs.writeFileSync(path.join(SAIDA, 'onda8-etapa3-regua-escola.txt'), mudou.join('\n') + '\n');
    const resumo = [...new Set(mudou.map((x) => x.replace(/\/\d+|\.\d+(?=\.)/g, '')))];
    /* as mudanças de propósito das etapas seguintes, cada uma com a decisão: o curso livre (D-70), a grade do horário
       (D-71), as faltas do ano corrente (D-78: as faltas por etapa e o total do componente, e por isso as faltas e a
       frequência dos boletins e das vidas acadêmicas; a memória do cálculo do boletim, que cita a frequência) e o atestado
       emitido pela montagem (D-79) */
    const depois = /^(esc_faltas_etapa:cursoLivre|rm_disciplina_faltas(\.(rm|rm_prof_mat_id|faltas)|:cursoLivre)|esc_lancamentos_trazidos\.faltas|rm_historico_emissoes:cursoLivre|(vidas\.modulos\.disciplinas\.(vigente|tentativas)|boletins\.blocos\.modulos\.disciplinas)\.(frequencia|faltas)|boletins\.blocos\.modulos\.disciplinas\.memoria\.\d+)$/;
    /* D-99 (o financeiro refeito): os responsáveis da demonstração com o celular e o endereço fictícios (o CPF já é volátil
       na régua), e por isso o "depois" da auditoria do cadastro deles */
    const d99 = /^(esc_responsaveis\.(celular|endereco|numero|complemento|bairro|cidade|uf|cep)|esc_responsavel_auditoria\.depois)$/;
    /* D-100 (a correção da escola, Parte 1): a demonstração libera o boletim pela tela nova (a tabela antiga fica vazia) e o
       conselho ganha as colunas da correção e da retirada */
    const d100 = /^(esc_boletim_liberado\(saiu\)|esc_conselho\.(situacao|alterado_por))$/;
    /* D-102 (a correção da escola, Parte 3): o modelo de documento ganha a marca do tipo de instituição (a coluna nova) */
    const d102 = /^rm_historico_modelos\.tipo_instituicao$/;
    /* D-104 (a correção da escola, Parte 5): três alunos da demonstração com o nome novo (D2), a Matemática do fundamental na
       chamada (a fonte da frequência) e o 8º ano A (os quatro alunos, os boletins e as vidas acadêmicas novas) */
    const d104 = /^(col_usuarios\.nome|portal_disciplinas_alunos\.nome_aluno|rm_disciplina_atividade_notas\.nome_aluno|(vidas|boletins)\.aluno\.nome|rm_professores_materias\.frequencia_fonte|vidas\.\d+|boletins\.26504\d)$/;
    const fora = resumo.filter((x) => !/(previsao_conclusao|previsaoConclusao|cpf|Cpf|alertas:semCpf|:cursoLivre|rm_professores_horarios:horario|:professor|:disciplina)$/.test(x) && !depois.test(x) && !d99.test(x) && !d100.test(x) && !d102.test(x) && !d104.test(x));
    L.ok(mudou.length > 0 && fora.length === 0,
      `a régua da escola, contra a de antes: mudam só a previsão de conclusão e o CPF, e o alerta dos dados pessoais deixa de pedir o CPF (${mudou.length} valor(es) em ${resumo.join(', ')})${fora.length ? '; fora: ' + fora.slice(0, 5).join(', ') : ''}`);

    /* 2. a dica da Página Inicial */
    const adm = await entrar(navegador, 'painel', 'administrador', SENHA);
    await adm.p.goto(L.BASE + 'painel/home/index');
    const dicaAluno = await adm.p.$$eval('tr', (l) => (l.find((t) => /Aluno \(portal\)/.test(t.textContent)) || { textContent: '' }).textContent);
    const dicaFamilia = await adm.p.$$eval('tr.vc-demo-familia', (l) => l.map((t) => t.textContent).join(' | '));
    await L.print(adm.p, ETAPA, 'pagina-inicial-dica-atestado');
    await adm.ctx.close();
    L.ok(/emita o atestado de matrícula/.test(dicaAluno) && /Dois filhos[^|]*atestado de matrícula/.test(dicaFamilia),
      'a dica da Página Inicial cita o atestado na escola: para o aluno e para o responsável de dois filhos');

    /* 3. o aluno */
    const cred = JSON.parse(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.credenciais'"));
    const ra = Object.keys(cred.portal).find((x) => um(`SELECT COUNT(*) FROM rm_matriculas_curso mc JOIN col_usuarios u ON u.id = mc.usuario_id WHERE u.rm = '${x}' AND mc.situacao_id = 1`) > 0 && /^265/.test(x));
    const al = await atestadoDoAluno(navegador, ra, cred.portal[ra]);
    await L.print(al.a.p, ETAPA, 'portal-aluno-atestado');
    await al.a.ctx.close();
    const emAluno = sql(`SELECT CONCAT(e.ambiente, '|', e.origem) FROM rm_historico_emissoes e JOIN col_usuarios u ON u.id = e.aluno_id WHERE u.rm = '${ra}' AND e.tipo = 'ATESTADO' ORDER BY e.id DESC LIMIT 1`);
    L.ok(al.bytes > 1000 && /^demonstracao\|portal/.test(emAluno), `o aluno ${ra} emite o atestado pelo portal; o PDF abre (${al.bytes} bytes), com a tarja da demonstração`);

    /* 4. o responsável de dois filhos */
    const email = 'helena.ribeiro@exemplo.invalid';
    const r1 = um(`SELECT id FROM esc_responsaveis WHERE email = '${email}'`);
    const filho = um(`SELECT ra.aluno_id FROM esc_responsavel_alunos ra JOIN rm_matriculas_curso mc ON mc.usuario_id = ra.aluno_id AND mc.situacao_id = 1 JOIN col_usuarios u ON u.id = ra.aluno_id AND u.rm LIKE '265%' WHERE ra.responsavel_id = ${r1} ORDER BY ra.aluno_id LIMIT 1`);
    const fam = await entrar(navegador, 'portal', email, (cred.familia || {})[email] || '', 390);
    await fam.p.goto(L.BASE + `portal/familia/atestado/${filho}?ano=${ANO}`);
    if (await fam.p.$('#emitirAtestado')) { await Promise.all([fam.p.waitForNavigation(), fam.p.click('#emitirAtestado')]); }
    const hrefF = await fam.p.getAttribute('#atestadosEmitidos a', 'href').catch(() => '');
    const bytesF = hrefF ? await pdfDe(fam.p, hrefF) : 0;
    await L.print(fam.p, ETAPA, 'portal-familia-atestado');
    await fam.ctx.close();
    const audF = um(`SELECT COUNT(*) FROM esc_responsavel_auditoria WHERE acao = 'atestado_emitido' AND responsavel_id = ${r1}`);
    L.ok(r1 > 0 && filho > 0 && bytesF > 1000 && audF >= 1, `o responsável emite o atestado do filho pelo portal da família; o PDF abre (${bytesF} bytes) e fica no registro`);

    /* sabotagem: desde a D-102, o atestado da escola usa a série e o ano letivo no lugar da previsão de conclusão;
       sem a série na fotografia da emissão, o atestado do aluno é recusado, com a razão (o dado que falta) */
    const EMISSAO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'historico', 'AdmsHistoricoEmissao.php');
    const shaEmissao = sha(EMISSAO);
    const orig = fs.readFileSync(EMISSAO, 'utf8');
    const trecho = "$vida['matricula']['escolaSerie'] = $pos['codnome'] ?? null;";
    if (orig.split(trecho).length !== 2) { throw new Error('a sabotagem não achou o trecho'); }
    /* o aluno já emitiu um atestado acima (o link da lista abre): a recusa se vê pelo número de emissões, que não muda */
    const emissoes = () => um(`SELECT COUNT(*) FROM rm_historico_emissoes e JOIN col_usuarios u ON u.id = e.aluno_id WHERE u.rm = '${ra}' AND e.tipo = 'ATESTADO'`);
    const nAntes = emissoes();
    let sab = { bytes: -1, texto: '' };
    let nSab = -1;
    try {
      fs.writeFileSync(EMISSAO, orig.replace(trecho, "$vida['matricula']['escolaSerie'] = null;"));
      sab = await atestadoDoAluno(navegador, ra, cred.portal[ra]);
      await sab.a.ctx.close();
      nSab = emissoes();
    } finally {
      fs.writeFileSync(EMISSAO, orig);
    }
    L.ok(nSab === nAntes && /S.rie no ano letivo/i.test(sab.texto), `sabotagem (a série fora da fotografia do atestado da escola): a prova reprova — o atestado do aluno é recusado, com a razão ("${sab.texto.slice(0, 90)}")`);
    L.ok(sha(EMISSAO) === shaEmissao && sha(CENARIO) === shaCenario, 'o arquivo sabotado voltou igual (sha256)');
    /* de volta: o atestado do aluno sai de novo */
    const volta = await atestadoDoAluno(navegador, ra, cred.portal[ra]);
    await volta.a.ctx.close();
    L.ok(emissoes() === nAntes + 1 && volta.bytes > 1000, `sem a sabotagem, o atestado do aluno sai de novo (${volta.bytes} bytes)`);
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(CENARIO) !== shaCenario) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
