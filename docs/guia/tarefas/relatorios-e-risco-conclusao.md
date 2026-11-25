---
titulo: Relatórios E: risco, evasão e conclusão
resumo: Alunos em risco (com o porquê), coorte de ingresso, aptos a concluir e o tempo até a conclusão.
perfis: administrador, secretaria
tipo: escola,superior
ordem: 65
relacionados: relatorios-visao-geral, concluir-o-curso-e-tcc, emitir-historico-final
entrou: 1.17.0
mudou: 1.17.3
telas: relatorios/alunos-risco, relatorios/aptos-concluir, relatorios/coorte-ingresso, relatorios/concluintes
---
## Para que serve

Saber **quem precisa de atenção agora**, **quem já pode concluir** e como cada turma de ingresso evoluiu.

## Antes de começar

- O alerta de risco não dá nota: ele mostra os **sinais** que dispararam e o porquê. Os limites ficam em **Relatórios → Alerta de risco**.
- "Aptos a concluir" usa a **mesma regra** que libera a situação Concluído.

## Passo a passo

1. **Alunos em risco**: matrículas em Cursando com ao menos um sinal:
   - **frequência**, numa oferta aberta: o aluno já está **abaixo do mínimo**, ou tem **ao menos 1 falta não compensada** e pode ter só até a margem de faltas (padrão 2). Quem não tem nenhuma falta efetiva não entra, mesmo numa disciplina curta. Na chamada com encontros previstos, a conta é sobre os previstos;
   - **média parcial** abaixo da mínima (com notas lançadas);
   - **atividades sem nota** acima do percentual;
   - **reprovação anterior** no curso;
   - **sem enturmação** em oferta aberta.

   Filtre por sinal para agir em grupo.
2. **Aptos a concluir**: todas as disciplinas da grade aprovadas e o TCC aprovado (quando o curso exige). O atalho abre a emissão do histórico final. Antes de emitir, mude a matrícula para Concluído, com a data. A lista "Cursando com pendência" mostra o que falta aos outros.
3. **Coorte de ingresso** (administrador e diretoria): por semestre de ingresso, quantos concluíram, seguem, trancaram ou saíram (evasão = cancelados + transferidos) e a **retenção (atraso no fluxo)**: trancados + cursando com a previsão de conclusão vencida. Atenção: não é a retenção "reprovação + trancamento" usada por outras instituições.
4. **Concluintes e tempo até a conclusão** (administrador e diretoria): por semestre de conclusão, tempo médio, mediano e máximo, em meses.

## O que pode dar errado

- Um aluno que "já terminou" não aparece como apto: abra "Cursando com pendência" e veja o que falta (disciplina não aprovada na tentativa vigente ou TCC).
- Um aluno sem falta não aparece no alerta de frequência: é o esperado. O sinal só dispara com ao menos 1 falta não compensada (ou abaixo do mínimo).
- Coorte com muitos "em atraso no fluxo": são trancados ou matrículas em Cursando com a previsão de conclusão já vencida. Atualize a previsão ou a situação.
