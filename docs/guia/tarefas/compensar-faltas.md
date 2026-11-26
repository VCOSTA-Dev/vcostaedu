---
titulo: Compensar faltas (atestado, licença)
resumo: Lançar falta compensada com tipo, justificativa e período; o aluno vê só o número.
perfis: secretaria, administrador, professor
tipo: escola,superior
ordem: 33
relacionados: lancar-faltas, acompanhar-notas-e-resultado
telas: faltas/compensar
entrou: 1.8.0
mudou: 1.103.0
---
## Para que serve

Abonar faltas justificadas (atestado médico, licença, convocação de serviço). A falta compensada não conta contra a frequência, e fica registrado quem lançou, quando e por quê.

## Antes de começar

O professor já lançou as faltas do aluno, e a disciplina está **aberta**. Tenha em mãos o documento: o tipo e o período vão para o registro.

## Passo a passo

1. Na disciplina, clique em **Faltas** e, na linha do aluno, no botão **Compensar**.
2. Preencha:
   - a **quantidade** de faltas compensadas;
   - o **tipo**: Atestado médico, Licença, Convocação de serviço ou Outro;
   - o **início** e o **fim** do período;
   - a **justificativa**, com ao menos 5 caracteres.
3. Clique em **Lançar compensação**. A frequência é recalculada na hora.
4. O quadro **Histórico de compensações** mostra cada lançamento. Um lançamento errado sai pelo botão de remover, e o total é recalculado.

> O aluno vê no boletim só a **quantidade** compensada, nunca o motivo. O professor também vê só o número.

## O que pode dar errado

- **"A justificativa é obrigatória (ao menos 5 caracteres)."** e **"Escolha o tipo da justificativa …"**
- **"O aluno tem N falta(s) e C já compensada(s): dá para compensar no máximo …"** — não se compensa mais do que as faltas.
- **"O fim do período não pode ser antes do início."**
- **"A disciplina está ENCERRADA: não aceita compensação nova."** — só o administrador reabre ([Reabrir uma disciplina](guia:reabrir-uma-disciplina)).
- O professor não compensa, a não ser que o administrador ligue para o perfil dele a ação **"Lançar compensação de ausência"** em [Perfis de acesso](guia:perfis-de-acesso). Ligada, o professor compensa só nas disciplinas em que dá aula; na de outro professor, a tela recusa e diz o porquê.
