---
titulo: Completar a oferta: período e professor
resumo: Em cada disciplina ofertada: total de encontros, período de realização e o professor.
perfis: administrador, secretaria
ordem: 14
comece: secretaria=7
relacionados: gerar-a-oferta-do-ano, previa-do-historico
telas: disciplinas/editar
entrou: 1.1.0
mudou: 1.34.0
---
## Para que serve

Deixar cada oferta pronta: o professor passa a vê-la, a frequência tem base e o histórico tem o período e o docente.

## Antes de começar

A oferta do ano foi gerada. O professor está cadastrado (é do administrador).

## Passo a passo

1. **Ensino → Todas as Disciplinas**, botão **Editar** da disciplina: **total de encontros** e **período de realização** (início e fim — saem no histórico). A ligação com o Moodle não se digita: com o Moodle conectado, a disciplina vai sozinha (veja **Conectar ao Moodle**).

![Edição da oferta com o período de realização](img/secretaria_18_oferta_editar.png)

2. Na mesma lista, o botão verde com o ícone de pessoa: escolha o **professor** e salve.

![Lista de ofertas](img/administrador_13_ofertas_professor.png)

## O que pode dar errado

- **"O fim do período da oferta não pode ser antes do início."**
- **A disciplina da matriz vem travada**, com a nota *"A disciplina da matriz não muda: a oferta já tem alunos ou atividades."* — depois que há aluno ou atividade, a oferta fica presa à disciplina; os outros campos (período, encontros) continuam editáveis.
- Sem período, a [prévia do histórico](guia:previa-do-historico) avisa "oferta sem período de realização"; sem professor, "disciplina sem docente".
