---
titulo: Fazer a chamada
resumo: A frequência pela chamada de cada encontro: escolher a fonte, registrar o encontro com a data e marcar quem faltou.
perfis: professor, secretaria, administrador
tipo: escola,superior
ordem: 33
relacionados: lancar-faltas, compensar-faltas, encerrar-uma-disciplina, acompanhar-notas-e-resultado
telas: chamada/listar, chamada/encontro
entrou: 1.14.0
mudou: 1.97.0
---
## Para que serve

Contar as faltas pela **chamada de cada encontro**, em vez de digitar o total. Cada ausência é uma falta, e o total de encontros da disciplina é o número de encontros registrados. A frequência é a de sempre: **1 − (faltas − compensadas) ÷ encontros**.

## Antes de começar

Cada disciplina tem **uma fonte** de frequência:

- **Lançamento de faltas** (o padrão): o total digitado ou importado na tela de Faltas;
- **Chamada por encontro**: as faltas saem da chamada. Na escola, as telas dizem **aula** no lugar de encontro: a fonte é **Chamada por aula**, o botão é **Nova aula** e a data é a **Data da aula**.

As duas não se misturam, para a falta não contar duas vezes. A troca para chamada só é possível com as faltas lançadas zeradas. A volta ao lançamento só é possível sem encontro registrado.

## Passo a passo

1. Nas abas do topo da disciplina, clique em **Chamada**.

[PRINT: tela da chamada com a fonte da frequência e a lista de encontros]

2. Se a fonte for "Lançamento de faltas", clique em **Trocar para Chamada por encontro** e confirme. Na escola, a tela da chamada da disciplina que lança as faltas por etapa explica e oferece **Passar a fazer a chamada** num passo; com falta já lançada na etapa, ela diz por que não troca (a chamada recalcularia a falta de cada etapa pelas aulas) e leva às faltas da etapa — e, em "O meu dia", o atalho dessa disciplina é **Faltas**.
3. **Novo encontro**: informe a **data** (o horário, o título e o conteúdo são opcionais). Na escola, a data num sábado ou num domingo avisa ao lado do campo e pede a confirmação ao salvar.
4. Em **Começar com**, escolha **todos presentes** ou **todos ausentes** (a sua escolha fica lembrada para o próximo encontro). Clique em quem é diferente: o botão troca entre **PRESENTE** e **AUSENTE**.

[PRINT: chamada de um encontro com um aluno marcado ausente]

5. **Salvar a chamada.** O encontro, as presenças e as faltas de cada aluno são gravados de uma vez. Se algo falhar, nada é gravado. O encontro abre de novo, já gravado.
6. **Daqui em diante, cada clique grava na hora**: no encontro já criado (também ao abri-lo pela lista, no lápis), clique na presença do aluno e ela grava, com as faltas recalculadas no mesmo clique. Um sinal pequeno de confirmado aparece ao lado do botão e some sozinho. Se a gravação for recusada (por exemplo, a etapa da data do encontro já está fechada), um sinal de alerta fica ao lado, com o motivo, e o botão não muda. Para mudar a data, o título ou o conteúdo, edite e use **Salvar a chamada**. Para apagar, use a lixeira: as faltas são recalculadas.
7. **Encontros previstos** (opcional): em Editar a disciplina, informe quantos encontros a disciplina terá. Os relatórios de frequência e o alerta de risco passam a contar as faltas que o aluno ainda pode ter sobre esse total. A frequência do boletim continua sobre os encontros registrados. Ao encerrar com menos (ou mais) encontros registrados que os previstos, aparece o aviso "Registrados X de Y previstos", que não impede o encerramento.

> A compensação de faltas (atestado, licença…) continua valendo, na tela de Faltas. Com a disciplina **encerrada**, a chamada fica só para leitura; o administrador reabre, com justificativa, e ela volta a ser editável.

## O que pode dar errado

- **"Não dá para trocar para chamada: há falta lançada para N aluno(s)…"** — zere as faltas lançadas na tela de Faltas antes.
- **"Não dá para voltar ao lançamento de faltas: há N encontro(s) registrado(s)…"** — exclua os encontros antes.
- **"… ausência(s) é menos que as … falta(s) já compensada(s) — remova a compensação antes"** — a chamada não pode deixar o aluno com menos faltas do que as compensadas.
- **"A oferta está ENCERRADA: a chamada fica só para leitura."**
- **"Registre ao menos um encontro na chamada"** — no encerramento de uma disciplina com chamada e sem encontro.
