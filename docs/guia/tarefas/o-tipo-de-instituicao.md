---
titulo: O tipo de instituição
resumo: Ensino superior, escola (educação básica) ou curso livre: o que cada tipo traz, onde se escolhe e quando ele não muda mais.
perfis: administrador
ordem: 2
relacionados: conhecer-com-a-demonstracao, instalar-como-curso-livre, atualizar-o-sistema
entrou: 1.38.0
mudou: 1.100.0
---
## Para que serve

O sistema atende três tipos de instituição, e cada instalação é de um deles:

- **Ensino superior** — cursos de graduação e pós-graduação: a matriz com módulos, as disciplinas ofertadas, uma média por disciplina, o histórico e o TCC.
- **Escola (educação básica)** — Ensino Fundamental (anos finais) e Ensino Médio: séries, turmas, o ano letivo em bimestres ou trimestres, a recuperação, a média anual e o conselho de classe.
- **Curso livre (curso on-line)** — o curso, a turma com vagas, as disciplinas no Moodle e a inscrição pelo site; sem boletim, etapas, horário, conselho nem documentos. O financeiro e a matrícula pela secretaria são de ligar e desligar (veja [Instalar como curso livre](guia:instalar-como-curso-livre)).

O tipo decide as telas que aparecem, as palavras da tela (na escola, "Segmentos" no lugar de "Cursos", "Séries" no lugar de "Módulos"; a disciplina é "disciplina" nos dois) e as regras que vêm prontas. As telas de um tipo não aparecem no outro.

## Antes de começar

- Uma instalação que já existia antes da versão 1.38.0 é de **ensino superior** e continua igual: ninguém precisa escolher nada.
- O tipo só pode ser trocado enquanto a instalação **não tem dados próprios** (cursos ou segmentos, matérias, alunos, pessoas da equipe) e com a demonstração desligada.

## Passo a passo

1. **Na instalação:** depois de criar a conta de administrador, o instalador pergunta o tipo. O ensino superior vem marcado; escolha e clique em **Continuar**. No curso livre, logo abaixo aparecem o **Financeiro** e a **Matrícula pela secretaria**, de ligar e desligar. A última tela, **Como você quer começar?**, mostra o tipo escolhido.
2. **Depois da instalação**, enquanto não houver dados próprios: nos **Primeiros passos**, o aviso no alto mostra o tipo e o link **trocar o tipo** (com a demonstração montada, o mesmo cartão fica em **Começar com os dados da minha instituição**). O cartão **Tipo de instituição** mostra o tipo atual e deixa trocá-lo; escolha e clique em **Gravar o tipo**.
3. Para conferir o tipo a qualquer momento: **Manutenção → Avisos do sistema** (o cartão Versões) ou a página **Sobre**.
4. Na escola, **Ensino → Estrutura da escola** mostra os segmentos, as séries de cada um e as disciplinas de cada série, com a carga horária do ano.

## O que pode dar errado

- **O cartão diz que o tipo não muda mais:** a instalação já tem dados próprios, que dependem do tipo. A troca não é possível sem uma instalação nova.
- **O cartão diz para remover a demonstração:** os dados fictícios são de um tipo; remova a demonstração e a escolha aparece no mesmo cartão.
- **A demonstração na escola** é o Colégio Horizonte (fictício); no curso livre, o Instituto Ventoalto (fictício): veja o guia "Conhecer com a demonstração".
