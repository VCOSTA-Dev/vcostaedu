---
titulo: Encerrar uma disciplina
resumo: Prévia com a conta de cada aluno e o encerramento que grava o resultado, a regra de média usada e a memória de cálculo, e trava notas e faltas.
perfis: secretaria, administrador
tipo: escola,superior
ordem: 34
comece: secretaria=12
relacionados: acompanhar-notas-e-resultado, reabrir-uma-disciplina, configurar-a-regra-de-media, ver-e-imprimir-o-boletim
telas: disciplinas/encerrar
entrou: 1.2.0
mudou: 1.16.0
---
## Para que serve

Fechar a disciplina: o resultado de cada aluno fica **gravado** (é o que sai no boletim e no histórico) e notas, faltas e atividades **travam**.

## Antes de começar

Todas as notas e faltas foram lançadas. A oferta tem o total de encontros.

## Passo a passo

1. **Todas as Disciplinas → ver** e **Encerramento**, nas abas do topo da disciplina (ou **Encerrar disciplina**, em Notas e resultado).

![Disciplina aberta, com o botão Encerramento](img/secretaria_14_oferta_aberta.png)

2. A **prévia** mostra, para cada aluno, média final, frequência e resultado, com a conta, e diz qual **regra de média** vai valer, pelo nome e de onde vem (da disciplina, padrão da instituição ou própria desta oferta).

![Prévia do encerramento](img/secretaria_15_previa_encerramento.png)

3. Confira (o botão **Imprimir a prévia** imprime a conferência) e clique em **Confirmar encerramento**.

> O encerramento **guarda a regra** usada e a **memória de cálculo** de cada aluno ("Como foi calculado", em Notas e resultado e no boletim do aluno). Mudar a regra depois não altera o que foi fechado. Tudo é gravado de uma vez: se algo falhar no meio, nada é gravado. O quadro **Fechamentos** lista o vigente e os anteriores.

![Disciplina encerrada: notas e resultado gravados](img/secretaria_16_oferta_encerrada.png)

## O que pode dar errado

- **"Encerramento RECUSADO: Informe o total de encontros da oferta (sem ele não há frequência)."**
- **"Encerramento RECUSADO: A oferta não tem atividade (sem elas não há média)."**
- **"Encerramento RECUSADO: A oferta não tem aluno enturmado."**
- **"Encerramento RECUSADO: A oferta já está encerrada."**
- **"Encerramento RECUSADO: Falta cadastrar: Prova (mínimo 1)."** — a regra de média tem um componente **obrigatório** sem atividade suficiente. Cadastre a atividade que falta; até lá, a média do aluno aparece "Pendente — falta Prova". Ver [Configurar a regra de média](guia:configurar-a-regra-de-media).
- **"Encerramento RECUSADO: Atividades acima do máximo: Prova: 2 atividades, o máximo é 1."** — a regra mudou depois do cadastro. Ajuste as atividades (ou o máximo na regra).
- **"Encerramento NÃO gravado — nada mudou (…)"** — uma falha no meio do encerramento desfez tudo. Tente de novo; se continuar, procure o suporte.
- Encerrou com erro? Só o administrador reabre: [Reabrir uma disciplina](guia:reabrir-uma-disciplina).
