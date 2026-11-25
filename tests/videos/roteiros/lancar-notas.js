// Guia: Lançar notas (professor). Nota pela tela (clique na célula, Enter grava), a nota inválida recusada, e a planilha
// rm;nota: o arquivo com linhas erradas é recusado inteiro, o certo grava tudo. Dados fictícios; o banco volta ao retrato.
const fs = require('fs');
const path = require('path');
let OFERTA;   // GOC, aberta, da Profa. Laura
let ATIV;     // "Trabalho final da disciplina", ainda sem notas
const csv = (nome, linhas) => { const f = path.join(__dirname, '..', 'saida', nome); fs.writeFileSync(f, linhas.join('\r\n') + '\r\n'); return f; };
const celula = (v, rm) => v.page.locator(`#tabelaLancaNotas tbody tr:has(td:text-is("${rm}")) td:has(.tabledit-span):not(:has(.tabledit-identifier))`).first();
async function notaNaTela(v, rm, nota) {
  const cel = celula(v, rm);
  await v.clicar(cel);
  const inp = cel.locator('input.tabledit-input').first();
  await inp.waitFor({ state: 'visible', timeout: 10000 });
  await inp.fill(''); await inp.pressSequentially(nota, { delay: 90 });
  await inp.press('Enter'); await v.pausa(1000);
}
module.exports = {
  conta: 'prof.laura.demo',
  async gravar(v) {
    OFERTA = v.ids.oferta.GOC; ATIV = v.ids.atividade.GOC[1]; // 1.18.2: da demonstração em uso, sem id fixo
    v.capitulo('Abrir a atividade');
    await v.ir(`painel/atividades/listar/${OFERTA}`);
    await v.passo('Na disciplina, Atividades: abra a atividade em que vai lançar as notas.');
    await v.clicarENavegar(`#tabelaAtividades a[href$="atividades/visualizar/${ATIV}"]`);

    v.capitulo('Nota pela tela');
    await v.passo('Clique na célula Nota do aluno, digite e aperte Enter: grava na hora. De 0 a 10, até 2 casas.');
    await notaNaTela(v, '260101', '8.5');
    await notaNaTela(v, '260102', '7.25');
    v.poster();
    await v.passo('Uma nota fora da escala (12) é recusada: nada é gravado.');
    await notaNaTela(v, '260103', '12');
    await v.page.waitForSelector('#ValorErrado.show', { timeout: 8000 }).catch(() => {});
    const erro = await v.texto('#ValorErradoMsg');
    v.confere(/Nota inválida/.test(erro) && await v.page.isVisible('#ValorErrado'), 'a nota 12 deveria ser recusada: ' + erro);
    await v.destacar('#ValorErrado .modal-content');
    await v.pausa(2500);
    await v.clicar('#ValorErrado .btn-close');
    await v.pausa(600);
    /* 1.18.2 (D-490): a linha recusada fica legível (vermelho claro, texto escuro) e a célula volta ao valor gravado */
    const linha = v.page.locator('#tabelaLancaNotas tbody tr:has(td:text-is("260103"))').first();
    const estado = await linha.evaluate(r => { const td = r.querySelector('td:nth-child(3)'); const c = getComputedStyle(td).color.match(/\d+/g).map(Number); return { classe: r.className, escuro: c[0] + c[1] + c[2] < 300 }; });
    v.confere(/table-danger/.test(estado.classe) && estado.escuro, 'a linha recusada deveria ficar legível: ' + JSON.stringify(estado));
    v.confere((await celula(v, '260103').innerText()).trim() === '', 'a célula deveria voltar ao valor gravado (vazio), não ao 12 recusado');
    await v.destacar(linha);
    await v.passo('A linha fica marcada em vermelho claro, legível, e a célula volta ao valor gravado (aqui, vazio): o 12 não foi salvo.');

    v.capitulo('Por planilha: tudo ou nada');
    await v.passo('Por planilha: "Atualizar via Planilha", com um CSV rm;nota separado por ponto e vírgula.');
    await v.clicarENavegar(`a[href$="atividades/upload-notas/${ATIV}"]`);
    await v.destacar('#modeloNotas');
    await v.passo('Primeiro, um arquivo com duas linhas erradas: uma nota 11 e um RA que não existe.');
    const errado = csv('notas_video_errado.csv', ['rm;nota', '260103;9,0', '260104;11', '260105;7,5', '999999;8', '260106;6,5', '260107;8']);
    await v.destacar('input[name="arquivo"]');
    await v.page.setInputFiles('input[name="arquivo"]', errado);
    await v.clicarENavegar('input[name="UpNotasCsv"]');
    const r1 = await v.texto('#importacaoRecusada');
    v.confere(/Nenhuma nota foi gravada/.test(r1) && /linha/i.test(r1), 'o arquivo errado deveria ser recusado inteiro: ' + r1);
    await v.passo('Recusado inteiro: "Nenhuma nota foi gravada", com o número da linha de cada erro. Corrija e envie de novo.');
    await v.destacar('#importacaoRecusada');

    await v.passo('Agora o arquivo corrigido (vírgula ou ponto na nota; colunas a mais são ignoradas).');
    const certo = csv('notas_video_certo.csv', ['rm;nota', '260103;9,0', '260104;6,0', '260105;7,5', '260106;6.5', '260107;8', '260108;7']);
    await v.destacar('input[name="arquivo"]');
    await v.page.setInputFiles('input[name="arquivo"]', certo);
    await v.clicarENavegar('input[name="UpNotasCsv"]');
    const r2 = await v.texto('#importacaoGravada');
    v.confere(/Notas importadas: 6/.test(r2), 'o arquivo certo deveria gravar: ' + (r2 || await v.alerta()));
    await v.passo('"Notas importadas: 6 aluno(s). Tudo foi gravado." As notas aparecem na atividade.');
    await v.destacar('#importacaoGravada');
    await v.pausa(1500);
    await v.ir(`painel/atividades/visualizar/${ATIV}`);
    v.confere(/9/.test(await celula(v, '260103').textContent()), 'a nota importada deveria estar na atividade');
    await v.destacar('#tabelaLancaNotas');
  },
};
