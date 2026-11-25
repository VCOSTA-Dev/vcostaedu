---
titulo: Montar a matriz
resumo: A grade do curso e a lista das disciplinas, com a carga horária somada.
perfis: administrador, secretaria
ordem: 11
comece: secretaria=5
relacionados: cadastrar-materias, situacao-da-grade, gerar-a-oferta-do-ano
telas: matriz/listar, matriz/visualizar, matriz/cadastrar-grade
entrou: 1.0.0
mudou: 1.18.1
---
## Para que serve

Definir o que o curso exige: a **grade** (a versão da matriz) e a lista das **disciplinas**, cada uma com a sua carga horária (CH). O boletim e o histórico listam essas disciplinas, e a conclusão do curso confere a grade inteira.

## Antes de começar

As matérias já estão no catálogo ([Cadastrar matérias](guia:cadastrar-materias)) e o curso existe (é do administrador).

## Passo a passo

1. Menu **Ensino → Matriz** e escolha o curso.

![Matriz: cursos e grades](img/secretaria_04_matriz.png)

2. **Nova grade**: número (inteiro, único no curso) e descrição.
3. Na grade, em **Incluir disciplina**, escolha a matéria e clique em **Incluir**. CH vazia = a do catálogo.
4. A CH da grade é a **soma** das disciplinas, feita pelo sistema. Para mudar a CH de uma disciplina, digite o número na linha e clique no disquete.
5. **Ordem das disciplinas:** a disciplina nova entra no fim. Para a ordem do currículo, **arraste** cada linha pela alça (a primeira coluna, com o número) e clique em **Salvar a ordem**. É a ordem em que as disciplinas saem no histórico.
6. Pronta, mande a grade para **Em análise**. Quem finaliza é o administrador ([Situação da grade](guia:situacao-da-grade)).

![Grade com as disciplinas](img/secretaria_05_grade.png)

> A grade só muda quando está **Aberta**. Travada, a tela mostra uma faixa com o motivo e o caminho para destravar, e os botões ficam desabilitados, com a razão na dica.

## O que pode dar errado

- **"A grade N já existe no curso … O número da grade não se repete no mesmo curso."** — use outro número.
- **"«Matéria» já está nesta grade."** — a mesma matéria não entra duas vezes.
- **"A ordem enviada não confere com as disciplinas desta grade…"** — a página ficou desatualizada (alguém incluiu ou removeu uma disciplina): recarregue e arraste de novo. A alça de arrastar só aparece com a grade **Aberta**.
- **O botão de remover vem desabilitado**, com a dica *"Usada por N oferta(s): não pode sair da matriz"* — depois que há oferta, a disciplina fica na matriz; só a CH ainda muda.
- **A faixa de trava** no alto da grade: *"A grade está «Finalizada» (ou «Em análise», «Desativada»): a matriz só muda com a grade Aberta."* — e os botões de incluir, remover e arrastar não aparecem. O administrador volta a grade para «Aberta» no quadro **Situação da grade** (com justificativa, se estava Finalizada ou Desativada); a secretaria pede a ele ([Situação da grade](guia:situacao-da-grade)).
