---
titulo: Emitir o histórico final
resumo: O histórico de quem concluiu: só com a matrícula Concluída e nenhum alerta.
perfis: secretaria, administrador
tipo: escola,superior
ordem: 51
comece: secretaria=16
relacionados: concluir-o-curso-e-tcc, previa-do-historico, emitir-2a-via, conferir-um-documento
telas: historico-emissoes/emitir?tipo=FINAL
entrou: 1.5.0
mudou: 1.7.0
---
## Para que serve

Emitir o documento final do curso, com nota final, frequência, CH, docente e titulação de cada disciplina, TCC, totais, ato legal e código de verificação.

## Antes de começar

A matrícula está **Concluída** ([Concluir o curso e registrar o TCC](guia:concluir-o-curso-e-tcc)) e a [prévia](guia:previa-do-historico) não tem alertas. O aluno tem CPF e nascimento no cadastro (o modelo padrão pede).

## Passo a passo

1. Ficha do aluno, aba **Históricos**, **Emitir final**.
2. **Prévia em PDF** para conferir (nada é gravado).
3. Sem bloqueios, **Emitir histórico FINAL**.
4. Abra a emissão (**Ver**): código, hashes, PDF e a verificação pública.

![Tela da emissão com os hashes, o PDF e a 2ª via](img/secretaria_21_emissao_final.png)

> O FINAL traz só a tentativa **vigente** de cada disciplina (a última encerrada) e o ato legal da modalidade na data de conclusão.

## O que pode dar errado

- **"O histórico FINAL só é emitido com a matrícula CONCLUÍDA (situação atual: …)."**
- **"O histórico FINAL só é emitido sem nenhum alerta (N alerta(s))."** — mesmo marcando a confirmação; resolva os alertas listados.
- **"Variável sem valor no documento: {{ALUNO_CPF}}."** (ou outra) — complete o cadastro do aluno.
- Para acompanhar o aluno antes da conclusão, use o [histórico parcial](guia:emitir-historico-parcial).
