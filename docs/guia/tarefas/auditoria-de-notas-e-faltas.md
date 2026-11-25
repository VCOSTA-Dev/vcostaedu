---
titulo: Auditoria de notas e faltas
resumo: Ver quem lançou, alterou ou apagou uma nota ou falta, quando e de onde.
perfis: administrador, professor
tipo: escola,superior
ordem: 64
relacionados: lancar-notas, lancar-faltas, reabrir-uma-disciplina, perfis-de-acesso
telas: auditoria/listar, auditoria/nota
entrou: 1.21.0
---
## Para que serve

Responder, quando uma nota ou falta é questionada, quem a lançou ou mudou, quando, qual era o valor antes e de onde veio a mudança (a tela, a importação por planilha ou a integração por API).

## Antes de começar

Tudo é registrado sozinho, a cada gravação: não há nada a ligar. O registro não pode ser alterado nem apagado por ninguém. O **histórico de uma nota** abre para quem vê a nota (o aluno não vê). O **relatório** abre para o Administrador, a Direção acadêmica e a Supervisão de secretaria; a Coordenação de curso vê só os cursos que coordena.

## Passo a passo

1. Para uma nota: abra a avaliação na disciplina e clique no ícone do **relógio**, na coluna **Histórico**, na linha do aluno. A janela mostra cada mudança: quando, a ação, o valor antes e depois, quem e a origem.
2. Para tudo: abra **Relatórios → Auditoria de notas e faltas**. Use os filtros (curso, disciplina, aluno, quem lançou, o quê, período) e marque **Só alterações e exclusões** para ver só o que mudou depois de lançado. Clique em **Filtrar**.
3. Para guardar o resultado, use os botões de exportação (Excel, CSV ou PDF) acima da tabela.

## O que pode dar errado

- **"Nenhuma mudança registrada desde o início da auditoria."** — a nota não mudou desde que a auditoria começou (versão 1.21.0); o que foi lançado antes não tem histórico.
- **"Este conteúdo é de um curso que você não coordena."** — a Coordenação de curso abriu a auditoria de outro curso.
- A linha marcada **depois do fechamento** veio de uma mudança feita depois que a disciplina foi reaberta.
- O IP mostrado é o do acesso ao servidor. Se todos aparecem com o mesmo IP, o servidor está atrás de um proxy que a instalação não declarou (`PROXIES_CONFIAVEIS`).
