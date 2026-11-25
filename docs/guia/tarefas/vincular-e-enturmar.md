---
titulo: Vincular e enturmar
resumo: Matrícula no curso (grade, polo, modalidade) e a enturmação sugerida pela grade.
perfis: administrador, secretaria
tipo: escola,superior
ordem: 21
comece: secretaria=9
relacionados: cadastrar-aluno-e-enviar-link, gerar-a-oferta-do-ano, concluir-o-curso-e-tcc
telas: academicos/visualizar, portal-alunos/visualizar
entrou: 1.1.0
mudou: 1.11.0
---
## Para que serve

Ligar o aluno ao curso (a matrícula) e às disciplinas do ano (a enturmação). Sem enturmação, o aluno não aparece para o professor.

## Antes de começar

A oferta do ano foi gerada e o aluno está cadastrado.

## Passo a passo

1. Na ficha do aluno, aba **Matrículas → Vincular a um curso**. Preencha:
   - o ano;
   - o *curso — grade*;
   - a forma e a data de ingresso;
   - o **polo** e a **modalidade da matrícula** (vazio = os do curso);
   - a **previsão de conclusão** (opcional). Depois do vínculo, a edição da matrícula mostra **Usar a sugestão**: a maior data de fim das ofertas da grade. Ela e o ingresso saem nos documentos como "março de 2027".

   A modalidade da matrícula escolhe o ato legal do histórico.

![Aba Matrículas com polo e modalidade da matrícula](img/secretaria_12_vinculo.png)

2. Clique no botão **Enturmação** do ano. Vêm **sugeridas e marcadas** as ofertas abertas das disciplinas da grade que o aluno ainda não cursou com aprovação. Desmarque o que ele cursará depois, acrescente outra se precisar, e clique em **Confirmar enturmação**.

![Enturmação de uma aluna com ajuste](img/secretaria_13_enturmacao.png)

3. Num ano seguinte, clique em **Registrar no ano**, na aba Matrículas, e abra a Enturmação desse ano. Nada grava antes de confirmar.

## O que pode dar errado

- **"A grade N de … está Desativada e não aceita vínculo novo."**
- **"Informe a data de ingresso."**
- **"«Disciplina»: oferta encerrada — o administrador precisa reabrir. Nada foi gravado."** — a disciplina já foi encerrada; na tela, ela aparece bloqueada.
- A disciplina com nota, falta ou observação não sai da enturmação.
- Uma disciplina "já aprovada" não vem sugerida: o aluno já passou nela em outra oferta.
