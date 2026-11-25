/*
 * ONDA 6, ETAPA 4 — a planilha FICTÍCIA grande da prova da importação em passos (nenhum dado real): N linhas (até
 * 5000), RA a partir de 950001, CPF válido e único, e-mail, nascimento, sexo; metade com o curso (o vínculo).
 * ref: { curso, ano } — da demonstração.
 */
const { cpf } = require('../onda2/planilha_alunos');

function grande(ref, n = 5000) {
  const cab = ['RM', 'Nome Completo', 'CPF', 'e-mail', 'Data de nascimento', 'Sexo', 'Curso', 'Grade', 'Ano letivo', 'Modalidade'];
  const linhas = [];
  for (let i = 1; i <= n; i++) {
    const ra = String(950000 + i);
    const comCurso = i % 2 === 0;
    linhas.push([ra, `Aluno Grande ${String(i).padStart(4, '0')} Exemplo`, cpf(600000000 + i * 13), `grande${ra}@exemplo.invalid`,
      `${String((i % 27) + 1).padStart(2, '0')}/${String((i % 12) + 1).padStart(2, '0')}/${1985 + (i % 15)}`, i % 3 ? 'F' : 'M',
      comCurso ? ref.curso : '', comCurso ? '1' : '', comCurso ? String(ref.ano) : '', comCurso ? 'Presencial' : '']);
  }
  const texto = [cab, ...linhas].map((c) => c.join(';')).join('\r\n') + '\r\n';
  return { buffer: Buffer.from(texto, 'utf8'), total: n, comCurso: Math.floor(n / 2), nome: `alunos-grande-${n}.csv` };
}

module.exports = { grande };
