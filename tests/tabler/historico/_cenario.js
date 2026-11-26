/*
 * O cenário das provas do histórico escolar (Partes E em diante): os cadastros pela tela (os segmentos e as séries, as
 * escolas, as matrizes do Fundamental de 2025 e 2026, os textos do Fundamental, as observações) e os alunos de prova
 * por script (clones fictícios de alunos da demonstração, marcados como prova; as séries de fora digitadas).
 */
const path = require('path');
const { execFileSync } = require('child_process');
const L = require('../lib');
const X = require('../onda8/_escola');

const prova = (...a) => execFileSync('php', [path.join(__dirname, 'prova.php'), ...a], { encoding: 'utf8' });
const NOTAS_FORA = ['7,5', '8,0', '6,5', '9,0', '5,5', '7,0', '8,5', '10,0'];

async function cadastros(p) {
  await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=series');
  await p.selectOption('#tabelaSeries tr[data-segmento="3"] select', '');
  await Promise.all([p.waitForNavigation(), p.click('#salvarSeries')]);
  for (const [nome, mun, uf, esta] of [['PROVA Colegio desta escola', 'Cidade Prova', 'SC', true], ['PROVA Escola de origem', 'Outra Cidade', 'PR', false]]) {
    await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=escolas');
    await p.fill('#eNome', nome); await p.fill('#eMunicipio', mun); await p.fill('#eUf', uf);
    if (esta) { await p.check('#eEsta'); }
    await Promise.all([p.waitForNavigation(), p.click('#salvarEscola')]);
  }
  for (const ano of [2025, 2026]) {
    await p.goto(L.BASE + 'painel/historico-escola-matriz/listar');
    await p.selectOption('#nGrade', '1'); await p.fill('#nAno', String(ano));
    await Promise.all([p.waitForNavigation(), p.click('#criarMatriz')]);
  }
  await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=textos&nivel=EF&nova=1');
  const textos = { vigente_desde: '2020-01-01', orgao_linha1: 'LINHA UM DO ÓRGÃO (PROVA)', ato_escola: 'Ato de prova nº 0', ato_curso: 'Ato do curso de prova', turno: 'Diurno',
    escala: 'Escala de prova: de zero a dez; média mínima seis.', secretaria_nome: 'Secretária de Prova', diretora_nome: 'Diretora de Prova', rotulo_registro: 'R.A. Nº',
    rotulo_publicacao: 'Nº do Registro da Publicação (prova)' };
  for (const [k, v] of Object.entries(textos)) { await p.fill(`#formTexto [name="${k}"]`, v); }
  await Promise.all([p.waitForNavigation(), p.click('#salvarTexto')]);
  return X.um("SELECT id FROM esc_hist_escola WHERE nome = 'PROVA Escola de origem'");
}

/** o aluno A: o 9º de 2025 aqui (clone), o 6º ao 8º fora; com o RA */
function alunoA(origem) {
  const A = JSON.parse(prova('clonar', '255001', '995001', 'PROVA HIST A CONCLUIU')).id;
  for (const [s, ano] of [[6, 2022], [7, 2023], [8, 2024]]) { prova('serie', String(A), 'EF', String(s), String(ano), String(origem), 'aprovado', ...NOTAS_FORA); }
  return A;
}

module.exports = { prova, cadastros, alunoA, NOTAS_FORA };
