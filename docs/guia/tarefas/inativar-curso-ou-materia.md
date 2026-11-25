---
titulo: Inativar curso ou matéria
resumo: Tirar das listas de escolha um curso ou uma matéria que não vai mais ser oferecido, sem mexer no que já foi feito.
perfis: administrador, secretaria
ordem: 12
relacionados: cadastrar-materias, polos-e-cursos, montar-a-matriz
telas: cursos/situacao, materias/situacao
entrou: 1.9.0
---
## Para que serve

Um curso ou uma matéria que **já foi usado** (tem grade, oferta, aluno ou nota) não pode ser apagado. Quando ele não vai mais ser oferecido, **inative**: ele sai das listas de escolha e todo o passado fica igual.

## Antes de começar

- **Curso**: só o administrador inativa. **Matéria**: o administrador e a secretaria.
- O que muda com **Inativo**:
  - some das listas de **escolha**: nova grade, nova oferta e vínculo do aluno (curso); matriz e nova oferta (matéria);
  - continua nas listas de **consulta**, com a etiqueta **Inativo**;
  - ofertas, notas, faltas e históricos já feitos **não mudam**, e a oferta que já existe continua editável.

## Passo a passo

1. Curso: **Ensino → Cursos**. Matéria: **Ensino → Matérias**.

![Lista de cursos, com as ações de cada linha](img/administrador_05_cursos.png)

2. Na linha, clique em **Inativar** (ícone de proibido) e confirme.
3. A situação passa a **Inativo** e, no lugar do botão, aparece **Reativar**.
4. Para voltar a oferecer, clique em **Reativar**: ele volta às listas de escolha.

![Lista de matérias, com Inativar e Reativar](img/secretaria_02_materias.png)

## O que pode dar errado

- **Não aparece o botão Inativar** — o seu perfil não pode (curso é só do administrador).
- **O curso não aparece para nova grade, nova oferta ou vínculo** — ele está **Inativo**; reative-o antes.
- **Apagar desabilitado com "Em uso: …"** — é o caso de inativar: o que já foi usado não é apagado.
