/*
 * O ARRANJO DAS PROVAS DA ONDA 7 depois da D-99 (o financeiro refeito). A demonstração do colégio passou a dar a todo aluno
 * com título o responsável financeiro do ano (uma mãe fictícia, quando a demonstração dos responsáveis não cuidava dele) e
 * aos responsáveis o celular, o CPF e o endereço fictícios. As provas da Onda 7 montam os cenários delas sobre os alunos
 * SEM responsável e sobre as pessoas SEM celular e sem CPF (o convite pelo WhatsApp, a planilha, a troca do financeiro).
 * Logo depois da montagem, este arranjo devolve o banco de prova a esse ponto: tira as mães fictícias (as pessoas sem
 * e-mail com o celular de demonstração, DDD 00) com os vínculos, os papéis, os convites e a auditoria delas, e apaga o
 * celular, o CPF e o endereço das pessoas da demonstração dos responsáveis. Só no banco de prova (o nome termina em _teste).
 */
const { execFileSync } = require('child_process');

module.exports = function antesDaD99() {
  const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
  if (!/_teste$/.test(BANCO)) { throw new Error('o arranjo só roda no banco de prova'); }
  const maes = "(SELECT id FROM (SELECT id FROM esc_responsaveis WHERE email IS NULL AND celular LIKE '0090000%') x)";
  const sql = [
    `DELETE FROM esc_responsavel_designacoes WHERE responsavel_id IN ${maes}`,
    `DELETE FROM esc_responsavel_alunos WHERE responsavel_id IN ${maes}`,
    `DELETE FROM esc_responsavel_convites WHERE responsavel_id IN ${maes}`,
    `DELETE FROM esc_responsavel_auditoria WHERE responsavel_id IN ${maes}`,
    "DELETE FROM esc_responsaveis WHERE email IS NULL AND celular LIKE '0090000%'",
    "UPDATE esc_responsaveis SET celular = NULL, cpf = NULL, endereco = NULL, numero = NULL, complemento = NULL, bairro = NULL, cidade = NULL, uf = NULL, cep = NULL WHERE celular LIKE '0090000%'",
  ].join(';\n') + ';';
  execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', BANCO, '-e', sql], { encoding: 'utf8' });
};
