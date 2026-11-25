---
titulo: Lançar faltas
resumo: Faltas por aluno, digitadas ou por planilha rm;faltas; a frequência sai do total de encontros e das compensadas.
perfis: professor, secretaria, administrador
tipo: escola,superior
ordem: 32
comece: professor=5, secretaria=10
relacionados: compensar-faltas, fazer-a-chamada, lancar-notas, acompanhar-notas-e-resultado, encerrar-uma-disciplina
telas: faltas/visualizar, faltas/editar
entrou: 1.2.0
mudou: 1.14.0
---
## Para que serve

Registrar a presença: **frequência = 1 − (faltas − compensadas) ÷ total de encontros**. A mínima (padrão 75%) é da instituição.

## Antes de começar

A oferta tem o **total de encontros** ([Completar a oferta](guia:completar-a-oferta)). Se a disciplina usa **chamada** ([Fazer a chamada](guia:fazer-a-chamada)), as faltas vêm dela e esta tela fica só para leitura.

## Passo a passo

1. Na disciplina, clique em **Faltas** (nas abas do topo, no cartão Faltas da disciplina, ou no atalho de Minhas Disciplinas).
2. Digite as **faltas** de cada aluno e clique em **Salvar faltas**.
3. As **compensadas** (atestado, licença…) aparecem na coluna ao lado. Quem lança é a secretaria, com justificativa: [Compensar faltas](guia:compensar-faltas).
4. **Por planilha:** em **Importar faltas por planilha** (embaixo da lista), envie um CSV `rm;faltas` separado por ponto e vírgula; **Baixar o modelo** traz o cabeçalho e uma linha de exemplo. Tudo ou nada: com uma linha errada, nada é gravado e a tela mostra a linha. Do Moodle (Presença), use o total de ausências de cada aluno, com o RA no número de identificação.

![Lançamento de faltas](img/professor_09_faltas.png)

## O que pode dar errado

- **"RA …: N faltas é mais que o total de M encontros."**
- **"RA …: N falta(s) é menos que as C já compensadas (remova a compensação antes)."**
- **"RA …: as faltas devem ser um número inteiro (0 ou mais)."**
- Frequência **"—"**: falta o total de encontros da oferta.
- Disciplina encerrada: as faltas aparecem só para leitura, e a tela diz por quê; a importação é recusada.
- **"As faltas desta oferta vêm da CHAMADA…"** — a disciplina usa chamada: o lançamento e a importação de faltas são recusados.
