---
titulo: Perfis de acesso
resumo: O que cada perfil vê e altera, a tela Perfis de acesso, a coordenação por curso e as ações sensíveis.
perfis: administrador
ordem: 62
relacionados: usuarios-e-niveis, reabrir-uma-disciplina, polos-e-cursos
telas: perfis-acesso/index, cursos/visualizar
entrou: 1.20.0
mudou: 1.66.0
---
## Para que serve

Escolher o perfil certo para cada pessoa da equipe e indicar quem coordena cada curso.

## Antes de começar

Os perfis do sistema:

| Perfil | Vê | Altera |
|---|---|---|
| **Administrador** | tudo | tudo, inclusive a equipe e a manutenção |
| **Direção acadêmica** | tudo, de todos os cursos | nada (consulta e relatórios); indica os coordenadores |
| **Coordenação de curso** | só os cursos em que é coordenador: as disciplinas, as notas, as faltas, os boletins e os relatórios | nada |
| **Supervisão de secretaria** | o mesmo que a Secretaria | o que a Secretaria altera e as ações sensíveis |
| **Secretaria** | cadastros, matrícula e documentos | o dia a dia da secretaria |
| **Professor** | as disciplinas dele | as notas e as faltas dele |

**Ações sensíveis** (só a Supervisão de secretaria e o Administrador): reabrir uma disciplina encerrada, excluir um encontro da chamada, desfazer uma importação e apagar um aluno.

Os perfis vêm prontos e não se editam numa tela: mudam com a atualização do sistema. Cada ação tem um **limite**, o perfil mais baixo que pode recebê-la (lançar notas vai até o Professor; reabrir uma disciplina, até a Supervisão de secretaria). O sistema recusa a ação para um perfil abaixo do limite, mesmo que ela tenha sido dada a ele por engano.

## Passo a passo

1. Para ver o que cada perfil pode fazer, abra **Equipe → Perfis de acesso** (ou o botão **Perfis de acesso** na lista de usuários): as ações, agrupadas, com o limite de cada uma e um ✓ em cada perfil que pode. A tela é só para consultar.
2. Cadastre a pessoa em **Equipe → Usuários → Cadastrar** e escolha o perfil no campo **Nível de acesso** ([Usuários da equipe](guia:usuarios-e-niveis)).
3. Para a **Coordenação de curso**: abra o curso em **Ensino → Cursos**, clique em **Adicionar coordenador**, escolha a pessoa e a posição e clique em **Salvar**. Só o Administrador e a Direção acadêmica veem esse botão.
4. A pessoa coordenadora entra e encontra em **Minhas disciplinas** as disciplinas dos cursos que coordena, e em **Relatórios** só os dados desses cursos.
5. Para tirar a coordenação, use o botão de desligar na lista de coordenadores do curso.

## O que pode dar errado

- **"Você não tem acesso a esta tela"** — a pessoa abriu (por um link antigo, um favorito ou o endereço) uma tela que o perfil dela não alcança. Ela continua conectada: **Voltar** leva à página de antes e **Página Inicial** ao começo. Se ela precisa da tela no trabalho, o administrador libera a ação no perfil, aqui em Perfis de acesso.
- **"Este conteúdo é de um curso que você não coordena."** — a Coordenação de curso abriu o endereço de uma disciplina, de um aluno ou de um relatório de outro curso. O sistema recusa e volta para Minhas disciplinas.
- A Direção acadêmica ou a Coordenação de curso não encontra o botão de salvar: esses perfis só consultam. Quem altera é a Secretaria, a Supervisão de secretaria ou o Administrador.
- A Coordenação de curso não vê nenhuma disciplina: falta indicar a pessoa como coordenadora na tela do curso.
- **"Só o administrador ou a supervisão da secretaria reabre uma oferta encerrada."** — reabrir é uma ação sensível.
- A conferência do instalador ou a verificação do ambiente reprova com **"permissão acima do perfil"**: um perfil recebeu uma ação abaixo do limite dela (a mensagem diz qual). O sistema já recusa essa ação; peça ao suporte técnico da instalação para corrigir a permissão.
