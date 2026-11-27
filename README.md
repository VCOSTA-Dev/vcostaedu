# VCOSTAEDU

Plataforma acadêmica de código aberto para quem cuida da vida escolar: a secretaria, os professores, a coordenação e os
alunos. Serve a uma faculdade, a uma escola de educação básica ou a um curso livre (curso on-line) — cada instalação
escolhe o seu tipo.

Quem instala usa, não programa. As regras vêm prontas, com padrões, e tudo se ajusta pela tela.

## O que ela faz

- **O ensino superior:** cursos, matriz curricular, oferta de disciplinas e enturmação.
- **A escola:** o ano letivo em etapas, as séries, as turmas e a avaliação por etapa, com recuperação, média anual e
  conselho de classe.
- **O curso livre:** o curso, a turma com vagas, as disciplinas no Moodle e a inscrição pelo site, sem o que é da
  escola; a matrícula pela secretaria de ligar e desligar. O financeiro é de ligar e desligar nos três tipos: ligado
  de partida na escola e no ensino superior, desligado no curso livre.
- **Atividades e notas,** com a regra de média de cada instituição.
- **Frequência e chamada.**
- **Documentos:** boletim, histórico escolar e atestado de matrícula, com código de verificação público.
- **Relatórios,** com a fórmula de cada número à vista.
- **O portal do aluno,** que pode rodar dentro do Moodle, e a integração com o Moodle por um plugin próprio.
- **Uma API** para outros sistemas, com chave e limite por assunto.
- **A Ajuda dentro do sistema:** guias curtos, vídeos e o "Faça comigo", que conduz uma tarefa na própria tela.

Para conhecer sem cadastrar nada, o instalador oferece "Conhecer com dados de demonstração": uma instituição fictícia
completa, com uma conta de cada perfil.

## Instalar

1. Crie um banco vazio em `utf8mb4` / `utf8mb4_unicode_ci` (MySQL ou MariaDB).
2. Copie esta pasta para o servidor web: Apache com `mod_rewrite`, PHP 8.1 ou superior com `pdo_mysql`, `mbstring`, `gd`,
   `dom` e `iconv` (o que o instalador confere). O `zip` só é pedido pelas ferramentas de linha de comando (o backup e
   o pacote). No Linux, o guia "Instalar no Linux, ao lado do Moodle" mostra os passos. No Nginx, que não lê os
   `.htaccess`, use o exemplo `docs/nginx/vcostaedu.conf` (o guia: `docs/IMPLANTACAO.md`).
3. Abra o endereço da pasta no navegador. O instalador aparece sozinho e guia o resto. Ele pede o servidor, a **porta**
   (de partida, 3306, a padrão), o nome do banco, o usuário e a senha. Na instalação pela linha de comando, a porta é a
   constante `PORTA` das duas `Config.php` (os modelos `Config.example.php` já trazem 3306).

Para atualizar uma instalação, use **Manutenção → Atualizar o sistema**, que aplica as etapas do banco em passos curtos,
com o backup antes. Para conferir a segurança do servidor: **Manutenção → Verificações de segurança**, ou
`php database/ferramentas/implantacao/verificar_seguranca.php` na pasta do sistema.

## Como o código está organizado

| pasta | o que é |
|---|---|
| `painel/` | a aplicação da secretaria, dos professores e da administração |
| `portal/` | o portal do aluno |
| `api/` | a API de integração |
| `core/` | o que as aplicações compartilham: o banco, a transação, o motor de média, a segurança |
| `instalar/` | o instalador |
| `database/` | as etapas do banco (migrations), a demonstração e as ferramentas de linha de comando |
| `bin/` | os comandos do servidor (por exemplo, `migrar.php`: ver e aplicar as etapas do banco) |
| `assets/` | estilos, scripts e bibliotecas (nenhuma vem de fora na hora de abrir a página) |
| `docs/` | o estado do projeto, as decisões, os guias de uso e a origem do código |
| `tests/` | as provas automáticas, pelo navegador |

## Participar

- **Para contribuir** com código, texto ou relato de problema: `CONTRIBUINDO.md`.
- **Para avisar uma falha de segurança:** `SEGURANCA.md` — por canal privado, nunca num relato público.
- **A convivência no projeto:** `CODIGO_DE_CONDUTA.md`.

## Licença e marca

O código é livre, sob a **AGPL-3.0** (`LICENSE`): você pode usar, estudar, modificar e redistribuir, inclusive
comercialmente.

O nome VCOSTAEDU e os arquivos de `assets/marca/` não estão sob a AGPL. Em resumo: diga à vontade que usa o VCOSTAEDU;
numa versão modificada, troque o nome e o logo. O resumo está em `MARCA.md`, e o texto completo em `TRADEMARK.md`.

A origem do código está em `docs/ORIGEM.md` e, arquivo por arquivo, em `docs/ORIGEM_DO_CODIGO.md`.
