---
titulo: Emitir o histórico parcial
resumo: O histórico de quem ainda cursa: atividades por disciplina e o que está em curso; com alertas, só confirmando.
perfis: secretaria, administrador
tipo: escola,superior
ordem: 50
comece: secretaria=15
relacionados: previa-do-historico, emitir-historico-final, emitir-2a-via, conferir-um-documento
telas: historico-emissoes/listar, historico-emissoes/emitir
entrou: 1.5.0
---
## Para que serve

Dar ao aluno um documento oficial do que ele cursou até agora, com código de verificação.

## Antes de começar

Confira a [prévia do histórico](guia:previa-do-historico). O modelo PARCIAL da instituição está ativo (é do administrador).

## Passo a passo

1. Ficha do aluno, aba **Históricos**, **Emitir parcial** na matrícula (ou **Educacional → Históricos emitidos**, buscando o aluno).

![Aba Históricos da ficha do aluno](img/secretaria_22_aba_historicos.png)

2. Confira o **modelo** e clique em **Prévia em PDF**: o documento sai com a tarja **PRÉVIA** e nada é gravado.
3. Leia os **alertas**. Se houver e mesmo assim for emitir, marque **"Li os alertas acima e emito o PARCIAL assim mesmo"** — eles ficam gravados na emissão.
4. **Emitir histórico PARCIAL**. A emissão guarda uma cópia fiel dos dados do dia, o PDF e o **código** (impresso no rodapé).

![Tela de emissão com tipo, modelo, alertas e o botão de prévia](img/secretaria_23_emitir_parcial.png)

> No PARCIAL, cada disciplina traz as atividades (tipo e data) e a disciplina aberta sai **"Em curso"**, com a média **(parcial)**. O ato legal é o da modalidade da matrícula, na data de hoje.

## O que pode dar errado

- **"Há N alerta(s): confirme que os leu para emitir o PARCIAL assim mesmo."** — marque a confirmação ou resolva os alertas.
- **"Escolha um modelo ATIVO do tipo Parcial que sirva a este curso (Configurações → Modelos de histórico)."** — peça ao administrador para ativar um modelo.
- Corrigiu uma nota depois? O documento emitido não muda: emita de novo.
