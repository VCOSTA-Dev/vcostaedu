---
titulo: Cadastrar aluno e enviar o link
resumo: Cadastro com RA único e o link para o próprio aluno definir a senha.
perfis: administrador, secretaria
ordem: 20
comece: secretaria=8
relacionados: vincular-e-enturmar, primeiro-acesso-do-aluno, email-smtp
telas: academicos/listar, academicos/cadastrar, academicos/editar, academicos/enviar-link
entrou: 1.0.0
mudou: 1.11.0
---
## Para que serve

Criar o aluno no sistema e dar a ele o acesso ao portal sem que ninguém da equipe conheça a senha.

## Antes de começar

Tenha o **RA** (só números, até 9 dígitos) e, se possível, o e-mail do aluno.

## Passo a passo

1. **Educacional → Usuários Acadêmico**, **Cadastrar aluno**.

![Lista de alunos](img/secretaria_08_alunos.png)

2. Preencha o **RA** (obrigatório e único), o nome, o **sexo** (obrigatório: os documentos concordam com ele, ALUNA/ALUNO) e o que tiver (CPF e e-mail são opcionais, mas precisam ser válidos e únicos). O aluno nasce **sem senha**.

![Cadastro de aluno](img/secretaria_09_aluno_cadastrar.png)

3. Na ficha, clique em **Enviar link de acesso**.
4. Com o e-mail configurado, o link vai para o aluno. Sem e-mail, a tela avisa **"O e-mail NÃO foi enviado"** e mostra o link para você entregar por um canal seguro.

![Link de acesso gerado (no guia, o link está mascarado)](img/secretaria_11_link_de_acesso.png)

> O link vale por 48 horas (o administrador ajusta), uma vez só; um link novo invalida o anterior.

## O que pode dar errado

- **"Escolha o sexo do aluno…"** — o campo não tem mais valor padrão. Alunos antigos que ficaram com "Outros" sem ninguém escolher aparecem em **Alunos sem sexo informado** (botão na lista de alunos): abra, escolha e salve.
- **"Informe o RA (registro acadêmico)."** e **"O RA deve ter só dígitos, até 9."**
- **"O RA … já pertence a outro aluno."** — o aluno já existe: abra a ficha dele.
- **"CPF inválido (dígitos verificadores não conferem)."** e **"Este CPF já está cadastrado para outro aluno."**
- **"E-mail inválido."** e **"Este e-mail já está cadastrado para outro aluno."**
