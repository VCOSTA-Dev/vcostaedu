// Guia: Fazer a chamada (professor). A troca da fonte recusada com faltas lançadas; zeradas as faltas, a troca para
// chamada, um encontro com dois ausentes e a lista de encontros. Dados fictícios; o banco volta ao retrato no fim.
let OFERTA;   // GOC, aberta, da Profa. Laura; tem faltas lançadas para 3 alunos
const AUSENTES = ['260103', '260105'];
module.exports = {
  conta: 'prof.laura.demo',
  async gravar(v) {
    OFERTA = v.ids.oferta.GOC; // 1.18.2: da demonstração em uso, sem id fixo
    v.capitulo('A fonte da frequência');
    await v.ir(`painel/disciplinas/visualizar/${OFERTA}`);
    await v.passo('Na barra da disciplina, clique em Chamada.');
    await v.clicarENavegar(`a[href$="chamada/listar/${OFERTA}"]`);
    await v.passo('A fonte é "Lançamento de faltas". Para fazer chamada, clique em "Trocar para Chamada por encontro".');
    await v.destacar('#fonteFrequencia');
    await v.clicarENavegar('#fonteFrequencia form button');
    const r1 = await v.alerta();
    v.confere(/Não dá para trocar para chamada/.test(r1), 'a troca deveria ser recusada com faltas lançadas: ' + r1);
    await v.passo('Recusado: há falta lançada para 3 alunos. As duas fontes não se misturam, para a falta não contar duas vezes.');
    await v.destacar('.alert');

    v.capitulo('Zerar as faltas lançadas');
    await v.passo('Na tela de Faltas, zere as faltas lançadas e salve.');
    await v.clicarENavegar(`a[href$="faltas/visualizar/${OFERTA}"]`);
    for (const rm of ['260102', '260104', '260106']) await v.digitar(`input[name="faltas[${rm}]"]`, '0');
    await v.clicarENavegar('button[type="submit"]:has-text("Salvar faltas")');
    v.confere(/Faltas salvas/.test(await v.alerta()), 'as faltas deveriam ser salvas: ' + await v.alerta());
    await v.passo('De volta à Chamada, a troca agora passa (o sistema pede confirmação).');
    await v.clicarENavegar(`a[href$="chamada/listar/${OFERTA}"]`);
    await v.clicarENavegar('#fonteFrequencia form button');
    v.confere(await v.page.getAttribute('#fonteFrequencia', 'data-fonte') === 'chamada', 'a fonte deveria virar chamada: ' + await v.alerta());
    await v.destacar('#fonteFrequencia');

    v.capitulo('Registrar um encontro');
    await v.passo('"Novo encontro": a data é obrigatória; horário, título e conteúdo são opcionais.');
    await v.clicarENavegar('#novoEncontro');
    await v.page.fill('#data', '2026-03-09'); await v.destacar('#data');
    await v.digitar('#titulo', 'Encontro 1 — controle interno');
    await v.passo('Todos começam PRESENTES. Clique em quem faltou: o botão vira AUSENTE (clique de novo para desfazer).');
    for (const rm of AUSENTES) await v.clicar(`tr[data-rm="${rm}"] .botao-presenca`);
    v.confere((await v.texto(`tr[data-rm="${AUSENTES[0]}"] .botao-presenca`)) === 'AUSENTE', 'o botão deveria virar AUSENTE');
    await v.destacar('#contagemAusentes');
    v.poster();
    await v.passo('"Salvar a chamada": o encontro, as presenças e as faltas são gravados de uma vez.');
    await v.clicarENavegar('#salvarChamada');
    v.confere(/Chamada salva/i.test(await v.alerta()), 'a chamada deveria ser salva: ' + await v.alerta());

    v.capitulo('A lista de encontros');
    if (!/chamada\/listar/.test(v.page.url())) await v.ir(`painel/chamada/listar/${OFERTA}`);
    const linha = v.page.locator('#tabelaEncontros tbody tr[data-encontro]').first();
    v.confere(await linha.count() && /\b2\b/.test(await linha.locator('td').nth(4).textContent()), 'o encontro deveria aparecer com 2 ausentes');
    await v.passo('O encontro na lista, com presentes e ausentes. O lápis abre para corrigir; a lixeira apaga e recalcula as faltas.');
    await v.destacar(linha);
    await v.destacar(linha.locator('.acoes-linha'));
    await v.passo('As faltas agora vêm da chamada; a compensação continua na tela de Faltas.');
  },
};
