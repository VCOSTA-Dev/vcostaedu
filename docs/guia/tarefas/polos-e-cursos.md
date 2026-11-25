---
titulo: Polos e cursos
resumo: O local da turma e o curso, com polo e modalidade padrão e a exigência de TCC.
perfis: administrador
tipo: escola,superior
ordem: 62
comece: administrador=4
relacionados: inativar-curso-ou-materia, montar-a-matriz, usuarios-e-niveis
telas: polos/listar, polos/cadastrar, polos/editar, cursos/listar
entrou: 1.1.0
mudou: 1.31.0
---
## Para que serve

Cadastrar onde as turmas acontecem e os cursos da instituição.

## Antes de começar

Tenha o endereço de cada polo e, de cada curso, a modalidade (Presencial, EaD ou Híbrido) e se ele exige TCC.

## Passo a passo

1. A instalação já traz o polo **Sede**: se a instituição tem um lugar só, ele basta (complete o endereço em **Configurações → Polos**). Para outros polos, **Configurações → Polos → Novo polo**: nome, endereço, cidade e UF.

![Cadastro de polo](img/administrador_04_polo_cadastrar.png)

2. **Ensino → Cursos**: cadastre o curso com o **polo padrão**, a **modalidade padrão**, o **turno** (escolhido na lista; some quando a modalidade é **EaD**, porque não se aplica) e **Exige TCC** (com "Sim", a conclusão exige o TCC aprovado).
   A **frequência mínima do curso** é opcional: vazio = a da instituição (Configurações → Instituição).
   Em **Nos documentos** (opcionais): o **nível** (ex.: Especialização), a **sigla**, o **nome por extenso** usado no texto dos documentos e a **linha do curso no cabeçalho**, escrita como deve sair (ex.: em maiúsculas). O Visualizar do curso mostra os quatro.

![Cadastro de curso com Exige TCC](img/administrador_06_curso_cadastrar.png)

3. A oferta de cada disciplina e a matrícula do aluno herdam polo e modalidade; a secretaria pode trocá-los em cada uma.

## O que pode dar errado

- **"Erro: Não foi possível criar o Curso!"** — confira os campos obrigatórios.
- A secretaria não cadastra polos nem cursos: só os escolhe.
- **"Em uso: … grade(s), … matrícula(s), … oferta(s). Não pode ser apagado."** — curso em uso não é apagado. O botão Apagar já aparece desabilitado, com essa razão. Se ele não vai mais ser oferecido, [inative o curso](guia:inativar-curso-ou-materia).
