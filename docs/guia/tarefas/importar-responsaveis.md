---
titulo: Importar responsáveis de planilha
resumo: Uma planilha com uma linha por papel (o RA do aluno, o ano, financeiro ou titular, e a pessoa). O sistema confere todas as linhas antes de gravar; uma com problema, nada é gravado.
perfis: administrador, secretaria
ordem: 15
tipo: escola
relacionados: cadastrar-um-responsavel, designar-o-financeiro-e-o-titular
telas: responsaveis/importar
entrou: 1.52.0
---
## Para que serve

Para designar os responsáveis de muitos alunos de uma vez, como no começo do ano.

## Antes de começar

- Os alunos cadastrados (o RA) e o ano letivo aberto.
- A planilha em CSV, com as colunas: **ra; ano; papel; nome; email; celular; cpf; parentesco; motivo**. Baixe o modelo na tela.

## Passo a passo

1. No menu, abra **Responsáveis** (junto de Alunos) e clique em **Importar planilha**.
2. Clique em **Baixar o modelo**, preencha uma linha por papel: o RA do aluno, o ano, o papel (**financeiro** ou **titular**) e os dados da pessoa. A coluna **motivo** só se preenche para trocar quem já está no papel.
3. Escolha o arquivo e clique em **Conferir**. A tela mostra cada linha e o que vai acontecer: designa, troca, ou "já está assim".
4. Com tudo certo, clique em **Aplicar a planilha**.

## O que pode dar errado

- **Linhas em vermelho:** a linha tem problema (o aluno não existe, o ano não está aberto, o papel está errado, o e-mail ou o CPF não é válido, duas linhas dão o mesmo papel ao mesmo aluno). Nada é gravado: corrija a planilha e envie de novo.
- **"O acesso acadêmico adicional não entra pela planilha":** ele pede o motivo; libere pela tela do aluno.
- **"O aluno já tem … neste papel; para trocar, preencha o motivo":** preencha a coluna motivo, se a troca for mesmo para acontecer.
