// Dados FICTÍCIOS da jornada (nomes obviamente inventados, RA 9930xx, e-mail .invalid). Nada aqui é de pessoa real.
const cpf = semente => { // CPF com dígitos válidos, gerado a partir de uma semente (não é de ninguém)
  const n = String(912345000 + semente * 37).padStart(9, '0').slice(0, 9).split('').map(Number);
  for (const t of [9, 10]) { let s = 0; for (let i = 0; i < t; i++) s += n[i] * (t + 1 - i); n.push((s * 10) % 11 % 10); }
  return n.join('');
};
const ALUNOS = [
  { rm: '993001', nome: 'Zéfiro Quaresma Pitanga (jornada — apagar)', sexo: '2' },
  { rm: '993002', nome: 'Ondina Trovão Cajá (jornada — apagar)', sexo: '1' },
  { rm: '993003', nome: 'Belarmino Tamarindo Lua (jornada — apagar)', sexo: '2' },
  { rm: '993004', nome: 'Iracema Pirilampo Sol (jornada — apagar)', sexo: '1' },
  { rm: '993005', nome: 'Jurandir Caramelo Vento (jornada — apagar)', sexo: '2' },
].map((a, i) => ({ ...a, cpf: cpf(i + 1), email: `aluno.jornada.${i + 1}@prova.invalid`, nasc: `199${i}-0${i + 2}-1${i}`, local: 'Cidade Inventada',
  uf: 'SP', nac: 'Brasileira', rg: `00.993.00${i + 1}-${i}`, mae: `Mãe Inventada ${i + 1} (jornada — apagar)` }));
module.exports = { ALUNOS };
