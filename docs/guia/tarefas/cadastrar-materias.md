---
titulo: Cadastrar matérias
resumo: O catálogo de disciplinas da instituição, com o código que sai no histórico.
perfis: administrador, secretaria
ordem: 10
comece: secretaria=4
relacionados: inativar-curso-ou-materia, montar-a-matriz, ano-letivo-passo-a-passo
telas: materias/listar, materias/cadastrar, materias/editar, materias/visualizar
entrou: 1.0.0
mudou: 1.18.1
---
## Para que serve

Manter a lista de todas as disciplinas que a instituição pode oferecer. A matriz de cada curso é montada com elas.

## Antes de começar

Tenha o nome, a carga horária padrão e o **código** de cada disciplina (ex.: GP101).

## Passo a passo

1. Menu **Ensino → Matérias**.

![Lista de matérias](img/secretaria_02_materias.png)

2. **Cadastrar**: **Código / abreviação** (até 20 caracteres; é o código que sai no histórico — em branco, sai o número interno da matéria), nome, carga horária e uma descrição curta.
3. Salve. A matéria aparece na lista, com o código.

![Cadastro de matéria com o campo Código / abreviação](img/secretaria_03_materia_cadastrar.png)

## O que pode dar errado

- **Nome e carga horária são obrigatórios.** A tela não deixa salvar sem eles; se o pedido chegar assim mesmo, o sistema recusa e nada é gravado: **"Erro: não foi possível criar a matéria: Informe o nome da matéria. / Informe a carga horária da matéria."** A carga horária é um número inteiro de horas, de 1 a 9999. O mesmo vale ao editar ("… alterar a matéria").
- Apagar matéria é do administrador; a secretaria cadastra e edita.
- **"Em uso: … grade(s) na matriz, … oferta(s). Não pode ser apagado."** — matéria que está numa grade ou tem oferta não é apagada. O botão Apagar já aparece desabilitado, com essa razão. Se ela não vai mais ser oferecida, [inative a matéria](guia:inativar-curso-ou-materia).
