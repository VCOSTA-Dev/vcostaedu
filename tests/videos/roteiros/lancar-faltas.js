// Guia: Lançar faltas (professor). Faltas digitadas e salvas, a frequência recalculada, e a planilha rm;faltas: o arquivo
// com uma linha errada é recusado inteiro, o certo grava tudo. Dados fictícios; o banco volta ao retrato no fim.
const fs = require('fs');
const path = require('path');
let OFERTA;   // STF, aberta (lançamento de faltas, 15 encontros), do Prof. Marcos
const csv = (nome, linhas) => { const f = path.join(__dirname, '..', 'saida', nome); fs.writeFileSync(f, linhas.join('\r\n') + '\r\n'); return f; };
const freq = (v, rm) => v.texto(`#tabelaFaltas tr[data-rm="${rm}"] td.frequencia`);
module.exports = {
  conta: 'prof.marcos.demo',
  async gravar(v) {
    OFERTA = v.ids.oferta.STF; // 1.18.2: da demonstração em uso, sem id fixo
    v.capitulo('Abrir as faltas');
    await v.ir('painel/minhas-disciplinas/listar');
    await v.passo('Ensino → Minhas Disciplinas: o atalho Faltas de cada disciplina (ou Faltas, na barra da disciplina).');
    await v.clicarENavegar(`a[href$="faltas/visualizar/${OFERTA}"]`);
    await v.passo('Frequência = 1 − (faltas − compensadas) ÷ total de encontros. Aqui são 15 encontros; o mínimo é 75%.');
    await v.destacar('#situacaoFaltas');

    v.capitulo('Digitar e salvar');
    await v.passo('Digite as faltas de cada aluno e clique em "Salvar faltas".');
    await v.digitar('input[name="faltas[260101]"]', '2');
    await v.digitar('input[name="faltas[260104]"]', '5');
    await v.clicarENavegar('button[type="submit"]:has-text("Salvar faltas")');
    v.confere((await freq(v, '260104')).startsWith('66,67'), 'a frequência de 260104 deveria ser 66,67%: ' + await freq(v, '260104'));
    await v.passo('Salvo. 5 faltas em 15 encontros: 66,67%, em vermelho, "Abaixo do mínimo". A coluna Compensadas é da secretaria.');
    await v.destacar('#tabelaFaltas tr[data-rm="260104"]');
    v.poster();

    v.capitulo('Por planilha: tudo ou nada');
    await v.passo('Por planilha: "Importar faltas por planilha", um CSV rm;faltas. "Baixar o modelo" traz o cabeçalho.');
    await v.rolar('#importarFaltas');
    await v.destacar('#modeloFaltas');
    await v.passo('Um arquivo com uma linha errada: 20 faltas, mais que os 15 encontros.');
    const errado = csv('faltas_video_errado.csv', ['rm;faltas', '260101;2', '260102;1', '260103;20', '260105;0']);
    await v.destacar('#importarFaltas input[name="arquivo"]');
    await v.page.setInputFiles('#importarFaltas input[name="arquivo"]', errado);
    await v.clicarENavegar('#botaoImportarFaltas');
    const r1 = await v.texto('#importacaoRecusada');
    v.confere(/Nenhuma falta foi gravada/.test(r1) && /encontros/.test(r1), 'o arquivo errado deveria ser recusado inteiro: ' + (r1 || await v.alerta()));
    await v.pausa(800);
    await v.rolar('#importacaoRecusada');
    await v.passo('Recusado: nada é gravado, e a tela mostra a linha com o erro ("… é mais que o total de 15 encontros").');
    await v.destacar('#importacaoRecusada');

    await v.passo('Corrigido o arquivo, envie de novo.');
    await v.rolar('#importarFaltas');
    const certo = csv('faltas_video_certo.csv', ['rm;faltas', '260101;2', '260102;1', '260103;3', '260105;0']);
    await v.destacar('#importarFaltas input[name="arquivo"]');
    await v.page.setInputFiles('#importarFaltas input[name="arquivo"]', certo);
    await v.clicarENavegar('#botaoImportarFaltas');
    const r2 = await v.texto('#importacaoGravada');
    v.confere(/Faltas importadas: 4/.test(r2), 'o arquivo certo deveria gravar: ' + (r2 || await v.alerta()));
    await v.pausa(800);
    await v.rolar('#importacaoGravada');
    await v.passo('"Faltas importadas: 4 aluno(s). Tudo foi gravado." A frequência de cada um é recalculada.');
    await v.destacar('#importacaoGravada');
    await v.destacar('#tabelaFaltas tr[data-rm="260103"]');
  },
};
