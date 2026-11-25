---
titulo: Reabrir uma disciplina encerrada
resumo: Desfazer o encerramento para corrigir nota ou falta, com justificativa registrada.
perfis: administrador
tipo: escola,superior
ordem: 35
comece: administrador=10
relacionados: encerrar-uma-disciplina, configurar-a-regra-de-media, emitir-historico-final
entrou: 1.2.0
mudou: 1.20.0
---
## Para que serve

Corrigir uma disciplina já encerrada. Reabrir **não apaga** o resultado: o fechamento fica **guardado como substituído** (com quem reabriu, quando e a justificativa), no quadro **Fechamentos**. Depois de corrigir, a secretaria encerra de novo e o sistema calcula com a regra que valer então (o fechamento seguinte: nº 2, 3…).

## Antes de começar

Reabrir é uma ação sensível: só o Administrador e a Supervisão de secretaria reabrem. Saiba o motivo: ele fica registrado. Históricos já **emitidos** não mudam (são cópia fiel do dia); emita de novo depois.

## Passo a passo

1. Abra a disciplina e clique na aba **Encerramento**, no topo. De **Notas e resultado**, o botão **Reabrir** leva para lá.
2. Escreva o motivo em **Justificativa** e clique em **Reabrir**.

![Reabrir disciplina encerrada](img/administrador_14_reabrir_oferta.png)

3. O professor corrige; a secretaria [encerra de novo](guia:encerrar-uma-disciplina).

## O que pode dar errado

- **"Escreva a justificativa da reabertura (ela fica no registro da disciplina)."** — a justificativa é obrigatória.
- A secretaria e o professor não reabrem: reabrir é uma ação sensível, do Administrador e da Supervisão de secretaria ([Perfis de acesso](guia:perfis-de-acesso)). Para os demais, o botão aparece desabilitado, com a razão.
