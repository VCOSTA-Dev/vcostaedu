/*
 * O que as provas do financeiro (Onda 8, D-98) usam juntas: o banco de prova, a entrada pela tela, a demonstração do
 * colégio e o arranjo de um aluno com um plano (feito pela tela da secretaria, como uma pessoa faria).
 */
const path = require('path');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }

/* o mysql do Windows termina as linhas com CR: cada linha sai aparada */
const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).split('\n').map((l) => l.trim()).filter(Boolean).join('\n');
const um = (q) => +(sql(q).split('\n')[0] || 0);
const montar = (rotulo) => spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', rotulo], { encoding: 'utf8', maxBuffer: 1 << 24 });
const cred = () => JSON.parse(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.credenciais'"));
const ymd = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
const hoje = () => ymd(new Date());
const diasAntes = (n) => ymd(new Date(Date.now() - n * 86400000));

async function entrar(navegador, usuario, senha, lado = 'painel', largura = 1366) {
  const ctx = await navegador.newContext({ viewport: { width: largura, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  p.on('dialog', (d) => d.accept()); // as confirmações ("Receber R$ …?") são aceitas
  await p.goto(L.BASE + lado + '/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}

/** O plano (pela tela) e as parcelas dele para o aluno (pela ficha). @return {plano, titulos: [{id, parcela, vencimento}]} */
async function arranjo(p, ra, { nome = 'Plano de prova', parcelas = 3, valor = '500,00', dia = 20, mes = 2 } = {}) {
  await p.goto(L.BASE + 'painel/fin-planos/listar');
  await p.fill('#pNome', nome);
  await p.fill('#pParcelas', String(parcelas));
  await p.fill('#pValor', valor);
  await p.fill('#pDia', String(dia));
  await p.selectOption('#pMes', String(mes));
  await Promise.all([p.waitForNavigation(), p.click('#salvarPlano')]);
  const plano = um(`SELECT MAX(id) FROM fin_planos WHERE nome = '${nome}'`);
  const aluno = um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`);
  await p.goto(L.BASE + `painel/financeiro/ficha/${aluno}`);
  await p.selectOption('#apPlano', String(plano));
  await Promise.all([p.waitForNavigation(), p.click('#aplicarPlano')]);
  const titulos = sql(`SELECT id, parcela, vencimento FROM fin_titulos WHERE aluno_id = ${aluno} AND plano_id = ${plano} ORDER BY parcela`).split('\n').filter(Boolean)
    .map((l) => { const [id, parcela, vencimento] = l.split('\t'); return { id: +id, parcela: +parcela, vencimento }; });
  return { plano, aluno, titulos };
}

/** Abre a ação da linha do título e devolve o formulário dela. */
async function abrirAcao(p, titulo, acao) {
  await p.click(`#tabelaTitulos tr[data-titulo="${titulo}"] .vc-abrir-acao[data-acao="${acao}"]`);
  return `#tabelaTitulos tr.vc-acao-titulo[data-titulo="${titulo}"] form[data-acao="${acao}"]`;
}

const situacao = (titulo) => sql(`SELECT situacao FROM fin_titulos WHERE id = ${titulo}`);

module.exports = { sql, um, montar, cred, entrar, arranjo, abrirAcao, situacao, hoje, diasAntes, ymd };
