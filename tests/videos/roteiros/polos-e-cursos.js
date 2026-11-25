// Guia: Polos e cursos (administrador). Um polo e um curso inventados: o turno some no EaD, Exige TCC, frequência
// mínima vazia (= a da instituição) e "Nos documentos"; o Visualizar mostra os quatro. O retrato desfaz tudo.
const POLO = 'Polo Vale Imaginário', CURSO = 'Especialização em Cidades Inteligentes (vídeo)';
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    v.capitulo('Novo polo');
    await v.ir('painel/polos/listar');
    await v.passo('Configurações → Polos: onde as turmas acontecem. Clique em "Novo polo".');
    await v.clicarENavegar('a[href$="polos/cadastrar"]');
    await v.passo('Nome, endereço, cidade e UF.');
    await v.digitar('#nome', POLO);
    await v.digitar('#endereco', 'Avenida das Palmeiras Fictícias, 100');
    await v.digitar('#cidade', 'Vale Imaginário');
    await v.escolher('#uf', 'SP');
    await v.clicarENavegar('input[name="SalvarPolo"]');
    v.confere(/cadastrado/i.test(await v.alerta()), 'o polo deveria ser cadastrado: ' + await v.alerta());
    await v.destacar(v.page.locator('#tabelaPolos tbody tr', { hasText: POLO }).first());
    await v.passo(`Polo cadastrado. Ele passa a aparecer na escolha do polo do curso, da oferta e da matrícula.`);

    v.capitulo('Novo curso');
    await v.ir('painel/cursos/listar');
    await v.passo('Ensino → Cursos → Cadastrar. A secretaria não cadastra polos nem cursos: só os escolhe.');
    await v.clicarENavegar('a[href$="cursos/cadastrar"]');
    await v.digitar('#nome', CURSO);
    const pid = await v.page.$$eval('#localidade option', (os, n) => (os.find(o => o.textContent.includes(n)) || {}).value, POLO);
    await v.passo('O polo padrão e a modalidade padrão: a oferta e a matrícula herdam os dois (e podem trocar).');
    await v.escolher('#localidade', pid);
    await v.destacar('#grupoTurno');
    const ead = await v.page.$$eval('#modalidade_id option', os => (os.find(o => /EaD|dist/i.test(o.textContent)) || {}).value);
    await v.escolher('#modalidade_id', ead);
    await v.pausa(600);
    v.confere(!(await v.page.isVisible('#grupoTurno')), 'o turno deveria sumir na modalidade EaD');
    await v.passo('Com a modalidade EaD, o Turno some: não se aplica. Em Presencial ou Híbrido, ele é escolhido na lista.');
    await v.passo('Exige TCC = Sim: a conclusão do curso passa a exigir o TCC aprovado.');
    await v.escolher('#exige_tcc', '1');
    await v.rolar('#frequencia_minima');
    await v.passo('Frequência mínima do curso: opcional. Vazia, vale a da instituição (Configurações → Instituição).');
    await v.destacar('#frequencia_minima');

    v.capitulo('Nos documentos');
    await v.passo('Nos documentos (opcionais): nível, sigla, nome por extenso e a linha do cabeçalho, escrita como deve sair.');
    await v.digitar('#nivel', 'Especialização');
    await v.digitar('#abreviatura', 'ECI');
    await v.digitar('#nome_extenso', 'Cidades Inteligentes');
    await v.digitar('#cabecalho_documento', 'CURSO DE ESPECIALIZAÇÃO EM CIDADES INTELIGENTES');
    await v.clicarENavegar('input[name="CadCurso"]');
    v.confere(/Curso criado/i.test(await v.alerta()), 'o curso deveria ser criado: ' + await v.alerta());
    const linha = v.page.locator('#tabelaNivel tbody tr', { hasText: CURSO }).first();
    await v.destacar(linha);
    await v.passo('Curso criado. Abra o Visualizar para conferir.');
    await v.clicarENavegar(linha.locator('a[href*="cursos/visualizar"]'));
    v.confere(/ECI/.test(await v.texto('#curso_abreviatura')) && /CIDADES INTELIGENTES/.test(await v.texto('#curso_cabecalho_documento')), 'o Visualizar deveria mostrar a identidade do curso');
    await v.rolar('#curso_nivel');
    v.poster();
    await v.passo('O Visualizar mostra o polo, a modalidade, a frequência (a da instituição) e os quatro campos dos documentos.');
    await v.destacar('#curso_cabecalho_documento');

    v.capitulo('Curso em uso não se apaga');
    await v.ir('painel/cursos/listar');
    const apagar = v.page.locator('#tabelaNivel tbody tr', { hasText: 'Gestão de Projetos' }).locator('.acao-desabilitada').last();
    const razao = await apagar.evaluate(e => e.getAttribute('title') || e.getAttribute('data-bs-original-title'));
    v.confere(/Em uso/.test(razao || ''), 'o Apagar do curso em uso deveria estar desabilitado: ' + razao);
    await v.destacar(apagar);
    await v.passo(`Curso em uso: Apagar desabilitado — "${razao.slice(0, 80)}". Se não vai mais ser oferecido, inative.`);
  },
};
