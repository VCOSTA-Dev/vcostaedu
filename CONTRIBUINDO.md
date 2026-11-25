# Como contribuir

Toda ajuda conta: um relato de problema bem escrito, um guia mais claro, uma tradução de termo, uma correção de código.

## Antes de começar

- **Falha de segurança não vai em relato público.** Siga `SEGURANCA.md`.
- Leia `CODIGO_DE_CONDUTA.md`: ele vale para relatos, conversas e revisões.
- Para mudanças grandes, abra primeiro um relato dizendo o que pretende. Assim ninguém faz o mesmo trabalho duas vezes,
  e a ideia é conversada antes de virar código.

## Relatar um problema

Diga, de preferência nesta ordem:

1. **O que você fez:** a tela e os passos, como numa receita.
2. **O que esperava** que acontecesse.
3. **O que aconteceu:** a mensagem da tela, se houver, copiada como apareceu.
4. **A versão,** que fica no rodapé de qualquer tela e em Manutenção → Versão, e o tipo de instituição (ensino superior
   ou escola).

Não mande dado real de aluno, professor ou responsável, nem senha, chave ou endereço de servidor. Se for preciso mostrar
um caso, use a demonstração fictícia ("Conhecer com dados de demonstração").

## Contribuir com código

1. **O jeito do projeto.** O texto da tela é em português, sem jargão de programação: quem lê é a secretaria, o
   professor, o aluno. As regras vêm prontas, com padrões, e se ajustam pela tela. Nenhuma operação longa numa
   requisição só: o que pode demorar vai em passos curtos, com a janela de progresso.
2. **O banco muda por etapa.** Uma mudança no banco é uma migration nova em `database/migrations/`; uma etapa já
   publicada não se edita.
3. **Toda mudança vem com a prova.** As provas ficam em `tests/` e rodam pelo navegador, contra uma instalação de prova
   com a demonstração fictícia. Toda prova nova tem uma sabotagem: o defeito posto de propósito, que a prova precisa
   pegar.
4. **A régua.** Uma mudança que não deve mexer nos dados acadêmicos (boletins, vidas acadêmicas, documentos) precisa
   deixar a régua idêntica (`tests/tabler/regua.php`), nos dois tipos de instituição.
5. **Os guias.** Mudou uma tela? Mude o guia dela em `docs/guia/tarefas/` e regere a Ajuda
   (`php database/ferramentas/guias/gerar_guias.php`).
6. **Nada de dado sensível** em arquivo do repositório: credencial, chave, endereço de servidor, nome real de pessoa ou
   de instituição, dado de aluno.

## O termo de contribuição

Para que o projeto possa manter a licença e defender o código, quem contribui com código aceita o termo de contribuição
(`docs/TERMO_DE_CONTRIBUICAO.md`). **Ele está em rascunho, aguardando revisão jurídica:** até ficar pronto, as
contribuições de código de fora são recebidas como relato e sugestão.

## Contato

- O endereço do repositório público: **[A DEFINIR: endereço do repositório público]**
- Para conversar sobre uma contribuição grande: **[A DEFINIR: e-mail ou canal do projeto]**
