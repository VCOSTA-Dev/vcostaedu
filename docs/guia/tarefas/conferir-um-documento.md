---
titulo: Conferir um documento
resumo: A verificação pública pelo código: resultado, dados mínimos, o hash, o comprovante e o PDF recebido.
perfis: secretaria, administrador, aluno
tipo: escola,superior
ordem: 54
relacionados: emitir-historico-final, emitir-2a-via, cancelar-uma-emissao, ler-o-boletim
entrou: 1.5.0
mudou: 1.8.0
---
## Para que serve

Qualquer pessoa (um empregador, outra instituição, o próprio aluno) confere se um histórico é autêntico, sem login.

## Antes de começar

Tenha o **código de verificação** impresso no rodapé do documento (ex.: `7KQ2-M8XD-4HPT`) ou o QR code.

## Passo a passo

1. Abra o endereço do sistema seguido de **/verificar** e digite o código. O QR code já leva à página certa, e o endereço de cada documento é curto: **/verificar/CÓDIGO**.
2. No topo, o logo e o nome da instituição. Logo abaixo, o **resultado em destaque**:
   - **Documento válido**;
   - **Documento cancelado**, com a data;
   - **Não confere**;
   - **Código não encontrado**.

   Depois vêm os dados mínimos: tipo, data de emissão, instituição, curso, nome do aluno e **CPF mascarado** (`***.***.789-**`).
3. A **impressão digital do documento** (o hash) fica num bloco com o botão **Copiar**. **Imprimir comprovante de verificação** imprime a página como prova de que você conferiu.
4. Para conferir o arquivo recebido, envie o PDF em **Conferir PDF**: a página diz se ele é **idêntico** ao emitido (ou a uma 2ª via). O arquivo não é guardado.

![Página pública de verificação com um documento válido](img/secretaria_20_verificacao.png)

## O que pode dar errado

- **"Código não encontrado"** — confira o código impresso no documento; se tiver certeza, tente de novo em alguns minutos.
- **"O arquivo enviado NÃO é idêntico a nenhum documento emitido com este código: ele pode ter sido alterado."**
- **"Não confere"** — o registro guardado não corresponde ao documento emitido: não aceite o documento; procure a instituição.
