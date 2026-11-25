---
titulo: Relatórios D: frequência
resumo: A frequência de cada aluno na oferta e quem vai reprovar por falta se faltar de novo.
perfis: administrador, secretaria, professor
tipo: escola,superior
ordem: 64
relacionados: relatorios-visao-geral, fazer-a-chamada, lancar-faltas, compensar-faltas
entrou: 1.17.0
mudou: 1.17.4
telas: relatorios/frequencia-oferta, relatorios/frequencia-minimo
---
## Para que serve

Ver **quem está faltando** e agir **antes** de o aluno reprovar por frequência.

## Antes de começar

- Frequência = 1 − (faltas − compensadas) ÷ encontros, a mesma conta do boletim. Sem o total de encontros, ela não é calculada.
- A frequência mínima é a do curso; sem ela, a da instituição.

## Passo a passo

1. **Frequência da oferta**: com chamada, uma coluna por encontro (P ou F); com lançamento, as faltas lançadas. Mais compensadas, frequência e FS/FI.
2. **Abaixo ou perto do mínimo**: em todas as ofertas abertas, os alunos com as faltas que cada um ainda pode ter e a classificação.
   - O relatório **abre em "Só os alertas"** (abaixo, no limite e perto). Para ver também os **"Sem alerta"**, escolha **Mostrar todos** no filtro **Mostrar** e clique em Filtrar. O filtro fica no link: quem abrir o link copiado vê a mesma lista.
   - Os **cartões** mostram sempre as quatro contagens (Abaixo, No limite, Perto e Sem alerta), qualquer que seja o filtro; clique em um para ver a lista daquele grupo.
   - A **exportação** (Excel, CSV, PDF, Imprimir) leva só o que a tabela mostra no filtro ativo, e o filtro fica no cabeçalho do arquivo e no registro da exportação.
   - **Faltas que ainda pode ter** = as faltas que o aluno pode ter sem cair abaixo da mínima, menos as que já tem (as compensadas descontam).
   - Na oferta por **chamada com encontros previstos** (Editar a disciplina → Encontros previstos), a conta é sobre os **previstos**: piso(previstos × (100 − mínimo) ÷ 100) − faltas não compensadas. Ex.: 10 previstos, mínimo 75%, 1 falta → piso(10 × 0,25) = 2, menos 1 = **pode ter mais 1 falta**. A coluna Encontros mostra "10 previstos (2 registrados)".
   - Sem previstos, a conta é sobre os encontros já registrados (como antes).
   - **Abaixo**: já passou do mínimo. **No limite**: tem falta e não pode faltar mais. **Perto**: tem ao menos 1 falta não compensada e pode faltar até a margem (padrão do alerta de risco; mude no filtro Margem). **Sem alerta**: nenhuma falta efetiva, ou ainda pode faltar mais que a margem. É a mesma classificação do alerta de risco.
3. Ordene pela coluna "Faltas que ainda pode ter" para ver primeiro quem está pior.

## O que pode dar errado

- A oferta não aparece em "Abaixo ou perto": falta o total de encontros (o aviso no topo conta quantas ficaram de fora).
- Na chamada **sem** encontros previstos, o total é o de encontros já registrados: no começo da oferta a margem é pequena para todos. Informe os previstos na edição da disciplina.
- A frequência do boletim e dos documentos é sempre sobre os encontros **registrados**: os previstos só servem a este relatório e ao alerta de risco.
