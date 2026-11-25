# Jornada de validação (navegador visível)

> **Só rodar em base de teste ou com a limpeza estendida depois.** Os blocos gravam dados de verdade no banco que a URL
> serve (curso, alunos, ofertas, notas, documentos). Tudo leva a marca "(jornada — apagar)" e RA 993001 a 993005, e a
> limpeza (`docs/docs_acad/limpeza_dados_prova.php`, fora do git) sabe apagar.

A jornada percorre o sistema de ponta a ponta em 12 blocos. O Chromium do Playwright fica **visível**. Um painel de
narração ("Bloco X/12 — Passo Y") tem o botão **Próximo ▶**, que controla o ritmo, e a opção "Sem pausas neste bloco".

| Bloco | O que mostra |
|---|---|
| 01 | Entrada e visão geral (início, menu, dados da instituição só abertos) |
| 02 | Regra de média: pesos que não somam 100 recusados (tela e servidor), simulador |
| 03 | Curso, grade (3 disciplinas, ordem por arrastar), regra em lote, regra do TCC e dos documentos com o aviso de divergência |
| 04 | 5 alunos fictícios (recusa sem sexo), vínculo com previsão e polo |
| 05 | Docente fictício, ofertas A (chamada, 6 previstos), B e C (lançamento), enturmação, troca da fonte recusada, sugestão da previsão |
| 06 | Atividades ("(3/3)" desabilitada, "Falta cadastrar: Prova"), notas pela tela, CSV com linha errada e certo, memória de cálculo |
| 07 | Chamada de 3 encontros, compensação, faltas por CSV, FS/FI, frequência perto do mínimo (alertas/todos) e risco com as ofertas abertas |
| 08 | Encerramento recusado com pendência, aviso "3 de 6 previstos", reabertura com e sem justificativa |
| 09 | TCC, histórico PARCIAL e FINAL, atestado pela secretaria, verificação pública |
| 10 | Portal: link de acesso, senha, login, "Como foi calculado", "Meu progresso", atestado pelo portal |
| 11 | Relatórios: painel com drill-down, mapa de notas, resultado, frequência, risco, aptos, Excel com auditoria, relatório salvo |
| 12 | Fim: o navegador fica aberto para explorar |

## Como rodar

1. Instale o `playwright-core` e o Chromium do Playwright fora do repositório (`npm i playwright-core` numa pasta sua e
   `npx playwright install chromium`), e aponte `NODE_PATH` para o `node_modules` dela.
2. Crie um usuário de teste com `criar_admin.php` e escreva `tests/jornada/config.local.json` (ignorado pelo git) ou as
   variáveis de ambiente `JORNADA_*`:

   ```json
   { "BASE": "http://localhost/vcostaedu/", "USUARIO": "…", "SENHA": "…",
     "CHROMIUM": "…/chrome.exe", "PERFIL": "…/perfil", "SAIDA": "…/saida", "VIDEOS": "…/videos",
     "FFMPEG": "…/ffmpeg.exe", "PDFTOTEXT": "…/pdftotext.exe", "PORTA": 9333, "SLOWMO": 400 }
   ```

   `SAIDA` guarda o estado da jornada, os CSV gerados e o export (fora do git). `VIDEOS` vazio = sem vídeo.
   `PDFTOTEXT` é opcional: confere o texto do atestado do portal.
3. `node abrir_navegador.js` abre o Chromium **uma vez** (perfil próprio, porta de depuração).
4. Rode um bloco por vez, em ordem: `node bloco01.js`, `node bloco02.js` e assim por diante. Cada bloco se conecta,
   mostra os passos, grava o vídeo (`blocoNN.webm`) e se desconecta sem fechar a janela. Os blocos 6 e 9 retomam do
   ponto em que pararam, sem recriar o que já existe.
5. O registro de cada passo (OK, ESTRANHEZA, INTERROMPIDO) fica em `SAIDA/registro.jsonl`.
6. No fim, rode a limpeza estendida (simulação, depois `--executar`) e confira os retratos da base.

Não vão ao git: vídeos, prints, exports, o perfil do navegador e a pasta de saída.
