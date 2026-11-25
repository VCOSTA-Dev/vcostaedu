---
titulo: Importar alunos de uma planilha
resumo: Trazer muitos alunos de uma vez por um CSV, conferido linha a linha antes de gravar, e desfazer se preciso.
perfis: administrador, secretaria
ordem: 3
relacionados: cadastrar-aluno-e-enviar-link, vincular-e-enturmar, primeiros-passos
telas: importacao-alunos/listar, importacao-alunos/conferir
mudou: 1.39.0
entrou: 1.23.0
---
## Para que serve

Cadastrar muitos alunos de uma vez a partir de uma planilha — e, se a planilha trouxer o curso, já vinculá-los a ele. Nada é gravado no envio: o sistema confere cada linha, diz o que está pronto e o que está errado (com o motivo), deixa corrigir na tela e só grava quando você aplica.

## Antes de começar

- Salve a planilha como **CSV** (no Excel: *Arquivo → Salvar como → CSV*). Planilhas `.xlsx` não são lidas. O CSV do Excel em português (com acentos) é reconhecido.
- Uma linha de cabeçalho e um aluno por linha. **Obrigatórias:** RA e Nome. **Opcionais:** CPF, e-mail, nascimento (dd/mm/aaaa), sexo, telefone e situação (Ativo/Inativo); e, para já vincular, curso, grade, ano, polo e modalidade. Use **Baixar o modelo** para ver as colunas.
- Para vincular, o curso, a grade, o ano letivo e o polo precisam existir. Sem polo e sem modalidade, valem os do curso, como na tela.
- **Na escola**, há também a coluna **Turma**: a série e o nome da turma ("6º ano A"), ou só o nome, se ele for único no segmento e no ano. Com a turma, o aluno é vinculado ao segmento na série da turma e entra em todas as disciplinas dela. A turma precisa existir (Ensino → Turmas).
- Quem importa: o Administrador, a Supervisão de secretaria e a Secretaria. Quem desfaz: o Administrador e a Supervisão de secretaria.

## Passo a passo

1. Abra **Educacional → Importar alunos**, escolha o arquivo e clique em **Enviar e conferir**.
2. **As colunas:** confira em que campo cada coluna do arquivo entra. Os nomes podem variar ("Nome completo", "Data de nascimento", "R.A."); o que o sistema não reconhecer fica como "(não importar)" — escolha o campo e clique em **Salvar as colunas e conferir de novo**. O próximo arquivo com os mesmos cabeçalhos já vem com esse ajuste.
3. **Os valores:** veja cada valor diferente de sexo, situação e modalidade e o que ele virou. Valor não reconhecido não é adivinhado: a linha fica com erro.
4. **As linhas:** cada uma aparece **Pronta**, **Com aviso**, **Com erro** (com o motivo) ou **Pulada** (o aluno já está cadastrado com aquele RA). Clique em **Corrigir** para acertar uma linha na própria tela, sem reenviar o arquivo.
5. **Aplicar:** se houver linhas com curso, escolha a forma e a data de ingresso. Se ainda houver linhas com erro, marque **Aplicar sem as linhas com erro** (elas ficam de fora). Clique em **Aplicar a importação**: a janela mostra o andamento, linha a linha (um arquivo de 5.000 linhas leva alguns minutos). Vale o tudo ou nada: se uma linha falhar, as já gravadas são desfeitas, nada fica gravado e a tela diz qual linha falhou e por quê. Se a página fechar no meio, nada se perde: ao voltar ao lote, clique em **Continuar**.
6. Os alunos importados recebem o primeiro acesso como os cadastrados na tela: pelo **Enviar link de acesso**.

## O que pode dar errado

- **"RA repetido no arquivo"** ou **"CPF repetido no arquivo"** — duas linhas com o mesmo RA (ou CPF): todas elas ficam com erro, porque não dá para saber qual é a certa. Corrija uma delas.
- **"CPF já cadastrado para outro aluno"**, **"E-mail já cadastrado"** — o dado pertence a outro aluno do sistema.
- **"Já cadastrado: RA …"** — o aluno já existe; a linha é pulada e o cadastro dele não muda. Por isso, enviar o mesmo arquivo de novo não duplica ninguém.
- **"Data de nascimento impossível"**, **"Curso não encontrado"**, **"Ano letivo não cadastrado"**, **"Sexo não reconhecido"** — corrija a linha na tela ou o arquivo.
- **"Sem o sexo"** (aviso) — o aluno entra mesmo assim, na lista **Alunos sem sexo informado** (em Alunos): os documentos concordam com o sexo (ALUNA/ALUNO), então complete-o depois.
- **Desfazer recusado** — desfazer tira os alunos do lote só enquanto nenhum deles tiver nota, falta, chamada, documento emitido, senha criada no portal ou outro vínculo. A tela diz qual aluno impede e por quê.
