---
titulo: Cancelar uma emissão
resumo: Tirar a validade de um histórico emitido, com motivo; a verificação passa a mostrar "cancelado".
perfis: administrador
tipo: escola,superior
ordem: 53
comece: administrador=11
relacionados: emitir-historico-final, conferir-um-documento
entrou: 1.5.0
mudou: 1.18.2
---
## Para que serve

Invalidar um histórico emitido por engano ou substituído. O documento **não é apagado**: a verificação pública passa a dizer **CANCELADO**, com a data.

## Antes de começar

Só o administrador cancela. Tenha o motivo (fica gravado).

## Passo a passo

1. **Educacional → Históricos emitidos**, **Ver** na emissão.
2. No quadro **Cancelar esta emissão**, escreva o **motivo** e clique em **Cancelar emissão**.
3. Para corrigir, peça à secretaria para **emitir de novo** (novo código).

![Tela da emissão com o quadro de cancelamento](img/administrador_16_emissao_cancelar.png)

## O que pode dar errado

- **"Informe o motivo do cancelamento (de 5 a 500 caracteres)."**
- **"A emissão já está cancelada."** — cancelada não volta a valer; emita outra.
- **A secretaria não vê o quadro Cancelar esta emissão** — o cancelamento é só do administrador; a secretaria pede a ele, com o motivo.
- Apagar ou alterar uma emissão é recusado pelo próprio banco de dados.
