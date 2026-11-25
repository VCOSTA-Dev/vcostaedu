// Guia: Conferir um documento (qualquer pessoa, sem login). O código do histórico FINAL fictício da Irene: resultado,
// dados mínimos, o hash, o PDF recebido (idêntico e alterado) e um código que não existe.
const fs = require('fs');
const os = require('os');
const path = require('path');
const { C } = require('../lib');
module.exports = {
  conta: 'secretaria.demo', // só para baixar o PDF emitido que o "recebedor" vai enviar; a página é pública
  async gravar(v) {
    const em = v.ids.emissao.find(e => e.ra === '250101' && e.tipo === 'FINAL');
    /* o PDF "recebido": o arquivo emitido e uma cópia alterada (um byte a mais no fim) */
    const pdf = await (await v.ctx.request.get(C.base + 'painel/historico-emissoes/pdf/' + em.id)).body().catch(() => null);
    const original = path.join(os.tmpdir(), 'historico_final_recebido.pdf');
    const alterado = path.join(os.tmpdir(), 'historico_final_alterado.pdf');

    v.capitulo('Digitar o código');
    await v.ir('verificar');
    await v.passo('Abra o endereço do sistema seguido de /verificar. Não precisa de login. Digite o código impresso no documento.');
    v.confere(await v.page.$('#c'), 'a página de verificação deveria ter o campo do código');
    await v.digitar('#c', em.codigo);
    await v.clicarENavegar('#formVerificar button');
    v.confere(v.page.url().endsWith(em.codigo), 'a página do código: ' + v.page.url());
    v.confere(/Documento válido/.test(await v.texto('#situacaoVerificacao')), 'deveria ser válido: ' + await v.texto('#situacaoVerificacao'));

    v.capitulo('O resultado');
    await v.passo(`A página do código (o QR code e o endereço curto /verificar/CÓDIGO levam a ela). No topo, a instituição e o resultado.`);
    await v.destacar('#resultadoVerificacao');
    v.poster();
    await v.passo('Os dados mínimos: tipo, data de emissão, instituição, curso, nome do aluno e o CPF mascarado.');
    await v.destacar('#tipoDocumento');
    await v.destacar('#alunoVerificado');
    await v.destacar('#cpfVerificado');
    await v.passo('A impressão digital do documento (o hash), com o botão Copiar. "Imprimir comprovante" guarda a prova da conferência.');
    await v.destacar('#hashVerificacao');
    await v.destacar('#copiarHash');
    await v.destacar('#imprimirComprovante');

    v.capitulo('Conferir o PDF recebido');
    v.confere(pdf && pdf.slice(0, 5).toString() === '%PDF-', 'o PDF emitido deveria ser baixado');
    fs.writeFileSync(original, pdf);
    fs.writeFileSync(alterado, Buffer.concat([pdf, Buffer.from('\n%alterado\n')]));
    await v.passo('Recebeu o arquivo? Em "Conferir o arquivo recebido", escolha o PDF e clique em "Conferir PDF". Ele não é guardado.');
    await v.destacar('input[name="pdf"]');
    await v.page.setInputFiles('input[name="pdf"]', original);
    await v.clicarENavegar('form[enctype] button');
    v.confere(/idêntico/.test(await v.texto('#conferenciaPdf')) && !/NÃO/.test(await v.texto('#conferenciaPdf')), 'o original deveria conferir: ' + await v.texto('#conferenciaPdf'));
    await v.passo('O arquivo é idêntico ao documento emitido com este código.');
    await v.destacar('#conferenciaPdf');
    await v.passo('Agora um arquivo alterado depois da emissão (mesmo que só um pouco).');
    await v.destacar('input[name="pdf"]');
    await v.page.setInputFiles('input[name="pdf"]', alterado);
    await v.clicarENavegar('form[enctype] button');
    v.confere(/NÃO é idêntico/.test(await v.texto('#conferenciaPdf')), 'o alterado não deveria conferir: ' + await v.texto('#conferenciaPdf'));
    await v.passo('"O arquivo enviado NÃO é idêntico a nenhum documento emitido com este código: ele pode ter sido alterado."');
    await v.destacar('#conferenciaPdf');

    v.capitulo('Código não encontrado');
    await v.rolar('#formVerificar');
    await v.passo('Um código que não existe (digitado errado, por exemplo):');
    await v.digitar('#c', 'ZZZZ-ZZZZ-ZZZZ');
    await v.clicarENavegar('#formVerificar button');
    v.confere(/não encontrado/i.test(await v.texto('#situacaoVerificacao')), 'deveria dizer código não encontrado: ' + await v.texto('#situacaoVerificacao'));
    await v.passo('"Código não encontrado": confira o código impresso no documento. Cancelado ou "Não confere": não aceite o documento.');
    await v.destacar('#resultadoVerificacao');
    for (const f of [original, alterado]) fs.rmSync(f, { force: true });
  },
};
