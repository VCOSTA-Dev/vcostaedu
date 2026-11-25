// Guia: Minhas disciplinas e atividades (professor). A lista do ano com os atalhos, a disciplina com a barra e os dados,
// e o cadastro de uma atividade, que cria a linha de nota de cada aluno. Dados fictícios; o banco volta ao retrato.
let OFERTA;   // GOC, aberta, da Profa. Laura
const NOME = 'Estudo de caso — controle social';
module.exports = {
  conta: 'prof.laura.demo',
  async gravar(v) {
    OFERTA = v.ids.oferta.GOC; // 1.18.2: da demonstração em uso, sem id fixo
    v.capitulo('Minhas Disciplinas');
    await v.ir('painel/minhas-disciplinas/listar');
    await v.passo('Ensino → Minhas Disciplinas: as disciplinas do ano em que você é o professor.');
    await v.destacar('#tabelaDisciplinas');
    const linha = v.page.locator('#tabelaDisciplinas tbody tr', { hasText: 'Liderança de Equipes' }).first();
    v.confere(/Aberta/.test(await linha.textContent()), 'a disciplina deveria aparecer como Aberta');
    await v.passo('Em cada linha: a quantidade de alunos, a situação (Aberta ou Encerrada) e os atalhos Atividades, Notas e Faltas.');
    await v.destacar(linha);
    await v.destacar(linha.locator('.acoes-linha'));

    v.capitulo('Dentro da disciplina');
    await v.passo('Clique em Atividades. A barra do topo leva a Atividades, Notas e resultado, Faltas e Encerramento.');
    await v.clicarENavegar(linha.locator(`a[href$="atividades/listar/${OFERTA}"]`));
    await v.destacar('.barra-disciplina');
    await v.passo('O cartão "Dados da disciplina" mostra curso, turma, polo, modalidade, datas e encontros.');
    await v.destacar('#dadosDisciplina');

    v.capitulo('Cadastrar atividade');
    await v.passo('"Cadastrar atividade": escolha o tipo. Os tipos desativados (Acréscimo, Recuperação…) não aparecem.');
    await v.clicar('#botaoCadastrarAtividade');
    await v.page.waitForSelector('#addAtividadeNota.show', { timeout: 10000 });
    await v.pausa(600);
    await v.clicarENavegar(`#addAtividadeNota a[href*="atividades/cadastrar/${OFERTA}?tipo=5"]`);
    await v.passo('Nome, conteúdo e as datas. Salve.');
    await v.digitar('#nomeAtividade', NOME);
    await v.digitar('#descricao', 'Análise de um caso fictício de controle social.');
    await v.page.fill('input[name="data_inicial"]', '2026-04-06'); await v.destacar('input[name="data_inicial"]');
    await v.page.fill('input[name="data_final"]', '2026-04-20'); await v.destacar('input[name="data_final"]');
    await v.clicarENavegar('input[name="CadAtividadeNota"]');
    if (!/atividades\/listar/.test(v.page.url())) await v.ir(`painel/atividades/listar/${OFERTA}`);
    const nova = v.page.locator('#tabelaAtividades tbody tr', { hasText: NOME }).first();
    v.confere(await nova.count(), 'a atividade nova deveria estar na lista');
    await v.passo('A atividade está na lista. Ao salvar, cada aluno ganhou a linha de nota.');
    await v.destacar(nova);
    v.poster();
    await v.clicarENavegar(nova.locator('a[href*="atividades/visualizar/"]').first());
    v.confere(await v.page.locator('#tabelaLancaNotas tbody tr').count() === 8, 'os 8 alunos deveriam ter a linha de nota');
    await v.passo('Os 8 alunos da turma, prontos para a nota. Aluno enturmado depois? "Atualizar lista", em Notas e resultado.');
    await v.destacar('#tabelaLancaNotas');
  },
};
