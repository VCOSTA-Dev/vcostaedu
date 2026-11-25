/*
 * ONDA 2, ETAPA 5 — as planilhas FICTÍCIAS da prova da importação de alunos (nenhum dado real).
 *
 * principal(ref): ~50 linhas em Windows-1252 (o CSV do Excel em português, com acento), separador ";", com cada
 *   armadilha de propósito — RA repetido, CPF inválido, CPF repetido, data impossível, curso inexistente, sexo e
 *   modalidade não reconhecidos, e-mail inválido, ano não cadastrado, aluno que já existe (pulado), sem o sexo
 *   (aviso). Devolve o conteúdo (Buffer) e o que cada linha deve dar.
 * grafias(): 4 arquivos pequenos com o cabeçalho escrito de 4 jeitos (e codificações e separadores diferentes).
 * semRa(): um cabeçalho que o sistema não reconhece ("Código"), para o ajuste manual e a memória.
 *
 *   node tests/tabler/onda2/planilha_alunos.js <pasta>   grava os arquivos na pasta (para olhar ou enviar à mão)
 */
const fs = require('fs');
const path = require('path');

/** CPF fictício válido (os dígitos verificadores certos) a partir de um número de 9 dígitos. */
function cpf(base) {
  const d = String(base).padStart(9, '0').split('').map(Number);
  for (const n of [10, 11]) {
    const s = d.reduce((t, x, i) => t + x * (n - i), 0);
    const r = (s * 10) % 11;
    d.push(r === 10 ? 0 : r);
  }
  const t = d.join('');
  return `${t.slice(0, 3)}.${t.slice(3, 6)}.${t.slice(6, 9)}-${t.slice(9)}`;
}

const NOMES = ['Ana Júlia Conceição', 'Bárbara Lúcia Simões', 'Caio Ângelo Araújo', 'Débora Inês Peçanha', 'Érico João Gonçalves',
  'Fábio César Magalhães', 'Gisele Mônica Brandão', 'Hélio Tomás Assunção', 'Íris Lívia Falcão', 'Jônatas Luís Antônio'];
const SOBRENOMES = ['Exemplo', 'da Prova', 'Fictício', 'de Teste', 'Modelo'];
const SEXOS = ['F', 'Feminino', 'M', 'masculino', 'fem', 'MASC', 'Outros'];

/** ref: { curso, ano, polo, raExistente } — dados da demonstração (o curso, o ano e o polo existem; o RA já está cadastrado) */
function principal(ref) {
  const cab = ['RM', 'Nome Completo', 'CPF', 'e-mail', 'Data de nascimento', 'Sexo', 'Celular', 'Curso', 'Grade', 'Ano letivo', 'Polo', 'Modalidade'];
  const linhas = [];
  const esperado = {}; // número da linha no arquivo => { res: 'pronta'|'aviso'|'erro'|'pulada', motivo: /regex/, vinculo: bool }
  const add = (campos, res, motivo = null, vinculo = false) => { linhas.push(campos); esperado[linhas.length + 1] = { res, motivo, vinculo }; };
  for (let i = 1; i <= 40; i++) {
    const ra = String(900000 + i);
    const nome = `${NOMES[(i - 1) % 10]} ${SOBRENOMES[Math.floor((i - 1) / 10)]}`;
    const semSexo = i === 12 || i === 13;
    const comCurso = i <= 10;
    add([
      ra, nome, cpf(700000000 + i * 7), `aluno${ra}@exemplo.invalid`, `${String((i % 27) + 1).padStart(2, '0')}/${String((i % 12) + 1).padStart(2, '0')}/${1980 + (i % 20)}`,
      semSexo ? '' : SEXOS[i % SEXOS.length], `(11) 9${String(80000000 + i)}`,
      comCurso ? (i % 2 ? ref.curso.toUpperCase() : ref.curso) : '', comCurso ? '1' : '', comCurso ? String(ref.ano) : '',
      comCurso && i % 3 === 0 ? ref.polo : '', comCurso ? ['EaD', 'Presencial', '', 'a distância'][i % 4] : '',
    ], semSexo ? 'aviso' : 'pronta', semSexo ? /Alunos sem sexo informado/ : null, comCurso);
  }
  const base = () => ['', 'Aluno Armadilha Fictício', '', '', '', 'F', '', '', '', '', '', ''];
  const l = (mudar) => { const c = base(); Object.entries(mudar).forEach(([k, v]) => { c[k] = v; }); return c; };
  add(l({ 0: '900005', 1: 'Repetida Exemplo' }), 'erro', /RA 900005 repetido no arquivo/);                     // 42
  esperado[6] = { res: 'erro', motivo: /RA 900005 repetido no arquivo/, vinculo: true };                           // a primeira ocorrência (linha 6) também
  add(l({ 0: '900041', 2: '123.456.789-00' }), 'erro', /CPF inválido/);                                             // 43
  add(l({ 0: '900042', 4: '31/02/2001' }), 'erro', /Data de nascimento impossível/);                               // 44 (a prova corrige na tela)
  add(l({ 0: '900043', 7: 'Curso Que Não Existe', 8: '1', 9: String(ref.ano) }), 'erro', /Curso não encontrado/);  // 45
  add(l({ 0: ref.raExistente, 1: 'Já Cadastrada Exemplo' }), 'pulada', /Já cadastrado/);                           // 46
  add(l({ 0: '900044', 5: 'X' }), 'erro', /Sexo não reconhecido/);                                                  // 47
  add(l({ 0: '900045', 7: ref.curso, 8: '1', 9: String(ref.ano), 11: 'Por correspondência' }), 'erro', /Modalidade não reconhecida/); // 48
  add(l({ 0: '900046', 2: linhas[1][2] }), 'erro', /CPF repetido no arquivo/);                                      // 49 (o CPF da linha 3)
  esperado[3] = { res: 'erro', motivo: /CPF repetido no arquivo/, vinculo: true };
  add(l({ 0: '900047', 3: 'sem-arroba.exemplo' }), 'erro', /E-mail inválido/);                                      // 50
  add(l({ 0: '900048', 7: ref.curso, 8: '1', 9: '1999' }), 'erro', /Ano letivo não cadastrado/);                   // 51
  const texto = [cab, ...linhas].map((c) => c.join(';')).join('\r\n') + '\r\n';
  return { buffer: Buffer.from(texto, 'latin1'), esperado, total: linhas.length, nome: 'alunos-ficticios.csv' };
}

/** As 4 grafias do cabeçalho: [nome, Buffer, índice da coluna do RA, índice da coluna do nome] */
function grafias() {
  const bom = Buffer.from([0xEF, 0xBB, 0xBF]);
  return [
    ['grafia-1.csv', Buffer.concat([bom, Buffer.from('RA;Nome;CPF;E-mail;Nascimento;Sexo\r\n910001;Aluna Grafia Um;;;01/02/2000;F\r\n', 'utf8')]), 0, 1],
    ['grafia-2.csv', Buffer.from('Registro Acadêmico;Nome do Aluno;E-MAIL;Data de Nascimento;Gênero\r\n910002;Aluno Grafia Dois;;02/03/2001;Masculino\r\n', 'latin1'), 0, 1],
    ['grafia-3.csv', Buffer.from('matrícula,ALUNO,e mail,Dt Nasc,sexo\n910003,Aluna Grafia Três,,03/04/2002,feminino\n', 'utf8'), 0, 1],
    ['grafia-4.csv', Buffer.from('Situação;R.A.;Telefone celular;Nome completo\r\nAtivo;910004;(11) 3000-0004;Aluno Grafia Quatro\r\n', 'latin1'), 1, 3],
  ];
}

/** sufixo: uma coluna a mais com nome único, para que o ajuste guardado numa rodada anterior não valha nesta */
function semRa(sufixo = '') {
  const extra = sufixo ? `;Turma ${sufixo}` : '';
  const vazio = sufixo ? ';' : '';
  return ['codigo-aluno.csv', Buffer.from(`Código;Nome;Sexo${extra}\r\n920001;Aluna Sem Cabeçalho;F${vazio}\r\n920002;Aluno Sem Cabeçalho;M${vazio}\r\n`, 'latin1')];
}

module.exports = { cpf, principal, grafias, semRa };

if (require.main === module) {
  const pasta = process.argv[2];
  if (!pasta) { console.error('uso: node planilha_alunos.js <pasta>'); process.exit(2); }
  fs.mkdirSync(pasta, { recursive: true });
  const p = principal({ curso: 'Curso Exemplo', ano: new Date().getFullYear(), polo: 'Polo Exemplo', raExistente: '260101' });
  fs.writeFileSync(path.join(pasta, p.nome), p.buffer);
  for (const [nome, buf] of grafias()) { fs.writeFileSync(path.join(pasta, nome), buf); }
  const [n, b] = semRa();
  fs.writeFileSync(path.join(pasta, n), b);
  console.log(`gravados em ${pasta}: ${p.nome} (${p.total} linhas) e ${grafias().length + 1} arquivos de cabeçalho`);
}
