# O "Faça comigo"

O motor do tour guiado de um projeto de referência, trazido para cá na Onda 2, Etapa 4 (`assets/js/vcostaedu-faca-comigo.js`, `assets/css/vcostaedu-faca-comigo.css`). Ele conduz um procedimento **na própria tela**: uma janela de narração diz o que vai acontecer, destaca o botão ou o campo e só avança quando a pessoa age (o passo confere) ou clica em **Avançar**; a cada passo, confirma o que foi feito.

## O roteiro é um arquivo de dados

Um arquivo por procedimento: `painel/app/adms/Roteiros/<slug>.json`.

| chave | o quê |
|---|---|
| `titulo` | o nome do procedimento, na janela |
| `guia` | o slug do guia em texto (`docs/guia/tarefas/<slug>.md`); o guia ganha o botão **Faça comigo** |
| `fim` | a tela a que o "Ler o guia" do fim leva (padrão: o guia) |
| `inicio` | a rota onde o roteiro começa |
| `rotas` | **todas** as rotas que ele percorre: o roteiro só abre para quem pode abrir cada uma (a matriz e as travas da rota) |
| `passos` | os passos: `p` (regex do caminho da página), `a` (o seletor que se destaca), `t` e `x` (título e texto, com `**negrito**`), `aviso` ("Agora: …"), `atencao` (o passo que grava de verdade), `faz` (as ações: `preencher`, `escolher` — `{primeira}` = a primeira opção com valor —, `marcar`, `clicar`, `anexar`, `ir`, `esperar`; e `nota`, a célula da nota, que só a prova executa), `confere` (`[tipo, seletor, esperado, mensagem]`: `valor`, `existe`, `ausente`, `pagina`, `marcado`), `modal` |

O **mesmo arquivo** serve: ao motor no navegador (no modo "junto", sem as ações — `faca-comigo/passos/<slug>`), à prova do Playwright (`tests/tabler/onda2/jornada4.js` executa as ações do arquivo e segue o motor passo a passo) e, mais tarde, à gravação do vídeo (o modo "mostre", em que o motor executa as ações sozinho, só vale com a demonstração ligada). `{ano}` vira o ano corrente.

## Roteiros que existem (as 8 fases dos Primeiros passos)

`fase-instituicao`, `fase-regras`, `fase-email`, `fase-ano-letivo`, `fase-curso`, `fase-oferta`, `fase-alunos`, `fase-enturmar`.

## O que falta roteirizar

Os demais guias de `docs/guia/tarefas/` têm o motor pronto e ainda não têm roteiro — ganham o botão **Faça comigo** quando o arquivo `<slug>.json` com `"guia": "<slug do guia>"` existir. Na ordem sugerida (o dia a dia primeiro):

1. Professor: `lancar-notas`, `lancar-faltas`, `fazer-a-chamada`, `minhas-disciplinas-e-atividades`, `acompanhar-notas-e-resultado`.
2. Secretaria: `vincular-e-enturmar`, `emitir-historico-parcial`, `emitir-historico-final`, `emitir-2a-via`, `cancelar-uma-emissao`, `emitir-atestado-de-matricula`, `compensar-faltas`, `encerrar-uma-disciplina`, `reabrir-uma-disciplina`, `completar-a-oferta`, `concluir-o-curso-e-tcc`, `exportar-e-imprimir-listas`, `previa-do-historico`, `situacao-da-grade`, `inativar-curso-ou-materia`.
3. Administrador: `usuarios-e-niveis`, `perfis-de-acesso`, `configurar-a-regra-de-media`, `editar-modelo-de-historico`, `tentativas-de-acesso`, `portal-dentro-do-moodle`, `polos-e-cursos`, `cadastrar-materias`, `auditoria-de-notas-e-faltas`, os relatórios (`relatorios-*`).
4. Aluno (portal): o motor ainda não está no portal (`entrar-no-portal`, `primeiro-acesso-do-aluno`, `esqueci-a-senha`, `ler-o-boletim`, `ver-e-imprimir-o-boletim`, `notas-das-atividades`, `meu-progresso`) — trazer o script e a rota para o portal é o primeiro passo.

Os guias das fases (`dados-da-instituicao-e-atos-legais`, `regras-de-nota-e-frequencia`, `email-smtp`, `ano-letivo-passo-a-passo`, `montar-a-matriz`, `gerar-a-oferta-do-ano`, `cadastrar-aluno-e-enviar-link`, `vincular-e-enturmar`) já têm o botão, pelo roteiro da fase.
