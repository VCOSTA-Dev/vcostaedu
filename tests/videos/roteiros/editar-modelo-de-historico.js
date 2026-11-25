// Guia: Editar um modelo de histórico (administrador). O editor do documento, a variável digitada à mão (recusada) e a
// inserida pelo painel, os blocos, as abas Quadro, Assinaturas, Opções e Prévia, e a nova versão. Modelo fictício.
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    const ID = 3; // "Histórico parcial — desenho oficial", o PARCIAL que a tela de emissão escolhe
    const MC = v.ids.matricula['260104'];
    v.capitulo('A lista de modelos');
    await v.ir('painel/historico-modelos/listar');
    await v.passo('Configurações → Modelos de histórico: o tipo, os cursos a que se aplica, a versão e a situação de cada modelo.');
    await v.destacar('#listaModelos');
    await v.passo('Clique em "Editar" no modelo (ou em "Novo modelo").');
    await v.clicarENavegar(`#listaModelos tr[data-modelo="${ID}"] a[href$="historico-modelos/editar/${ID}"]`);

    v.capitulo('Nome, cursos e situação');
    await v.passo('Nome, cursos a que se aplica (nenhum = padrão da instituição) e situação: só "Ativo" aparece para emitir.');
    await v.destacar('#nome');
    await v.destacar('#situacao');
    await v.destacar('#versaoModelo');

    v.capitulo('O documento e as variáveis');
    const ed = '.note-editable';
    v.confere(await v.page.$(ed), 'o editor visual deveria carregar');
    /* dentro do documento, nada de destaque (o contorno entraria no HTML do modelo): destaca-se a moldura do editor */
    const ultimo = v.page.locator(ed + ' > *').last();
    await v.rolar(ultimo);
    await v.passo('O documento: escreva e formate como num editor de texto. Vamos digitar uma variável à mão, errada.');
    await v.destacar('.note-editor');
    await ultimo.click();
    await v.page.keyboard.press('Control+End');
    await v.page.keyboard.press('Enter');
    await v.page.keyboard.type('Conferido por: {{CONFERIDO_POR}}', { delay: 55 });
    console.log('    (fim do documento: ' + (await v.page.locator(ed).innerText()).slice(-120).replace(/\s+/g, ' ') + ')');
    await v.rolar('input[name="SalvarModelo"]');
    await v.clicarENavegar('input[name="SalvarModelo"]');
    const rec = await v.texto('#modeloRecusado');
    v.confere(/variável desconhecida/.test(rec), 'a variável à mão deveria ser recusada: ' + (rec || await v.alerta()));
    await v.passo('"Nada foi salvo: … variável desconhecida — {{CONFERIDO_POR}}." Variável se põe pelo painel, não à mão.');
    await v.destacar('#modeloRecusado');

    const errado = v.page.locator(ed + ' *', { hasText: 'CONFERIDO_POR' }).last();
    v.confere(await errado.count(), 'o texto recusado deveria voltar ao editor para corrigir');
    await v.rolar(errado);
    await v.passo('O texto volta para corrigir. Reescreva a linha e, na aba Variáveis, busque e clique na variável certa.');
    await errado.click({ clickCount: 3 });
    await v.page.keyboard.type('Documento conferido para ', { delay: 55 });
    await v.digitar('#buscaVariavel', 'nome civil');
    const btn = v.page.locator('#catalogoVariaveis .btn-var:visible', { hasText: /Nome civil/i }).first();
    v.confere(await btn.count(), 'a busca deveria achar a variável Nome civil');
    await v.clicar(btn);
    const doc = await v.page.locator(ed).innerText();
    console.log('    (fim do documento: ' + doc.slice(-120).replace(/\s+/g, ' ') + ')');
    v.confere(/Documento\s+conferido\s+para\s*\{\{ALUNO_NOME\}\}/.test(doc) && !/CONFERIDO_POR/.test(doc), 'a variável deveria entrar no lugar da errada');
    await v.passo('Ela entra no ponto do cursor, como {{ALUNO_NOME}}; na emissão, vira o nome do aluno.');
    await v.destacar('.note-editor');
    v.poster();
    await v.digitar('#buscaVariavel', '');
    await v.passo('No alto do painel, os blocos do sistema (quadro de disciplinas, assinaturas, logo, QR code…) e o trecho condicional.');
    await v.destacar('#catalogoVariaveis .grupo-var:first-child');
    await v.destacar('#trechoCondicional');

    v.capitulo('Quadro, assinaturas e opções');
    await v.rolar('#abasEditor');
    await v.passo('Aba Quadro: as colunas do quadro de disciplinas, o rótulo, a largura, o alinhamento e a frequência.');
    await v.clicar('#aba-quadro');
    await v.destacar('#colunasQuadro');
    await v.passo('Aba Assinaturas: nome, cargo e, se quiser, a imagem (PNG ou JPEG até 1 MB).');
    await v.clicar('#aba-assinaturas');
    await v.destacar('#blocosAssinatura');
    await v.passo('Aba Opções: o estilo do documento, a fonte e as casas decimais das notas.');
    await v.clicar('#aba-opcoes');
    await v.destacar('#painelOpcoes');

    v.capitulo('Prévia e salvar');
    await v.passo('Aba Prévia: escolha um aluno e "Gerar a prévia em PDF" — o documento como está, sem salvar nada.');
    await v.clicar('#aba-previa');
    await v.escolher('#previa_matricula', String(MC));
    await v.destacar('#btnPrevia');
    await v.passo('Depois, "Salvar modelo". Modelo ativo: salvar cria uma NOVA versão; as emissões antigas ficam com a delas.');
    await v.rolar('input[name="SalvarModelo"]');
    await v.clicarENavegar('input[name="SalvarModelo"]');
    v.confere(/nova versão criada/.test(await v.texto('#modeloSalvo')), 'deveria salvar com nova versão: ' + await v.alerta());
    await v.destacar('#modeloSalvo');
    await v.destacar('#versaoModelo');
    await v.passo('A prévia em PDF do modelo salvo, com a tarja PRÉVIA: a linha nova traz o nome do aluno.');
    await v.pdf(`painel/historico-emissoes/previa/${MC}?tipo=PARCIAL&modelo=${ID}`, 7);
  },
};
