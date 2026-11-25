# As operações longas

O levantamento da Onda 3, Etapa 11, levado até o fim na Onda 6, Etapa 4 (DECISIONS D-54). O que pode demorar numa
instalação real com muitos dados roda **em passos** (uma chamada curta do navegador por passo, a janela de progresso —
`assets/js/vcostaedu-progresso.js` e `include_tabler/_progresso.php` —, o estado no banco, a retomada depois de falha ou
de página fechada) ou foi medido e **cabe numa requisição** (por conjunto, numa transação só).

**Nenhuma operação da tela passa do tempo de uma requisição.** O que fica só pela linha de comando está no fim.

## Em passos

| Operação (onde aparece) | Arquivos | Estado e trava |
|---|---|---|
| **Atualizar o sistema** (Manutenção) — desde a 1.37.0 | `Controllers/Atualizar.php`, `Models/helper/AdmsAtualizacao.php`, `Models/banco/AdmsMigrations.php::aplicarProxima` | uma etapa do banco por passo; o estado na tabela `adms_atualizacao_lote` (criada e apagada pela própria tela: o banco de quem atualiza pode ser bem antigo); o dono do lote vivo há menos de 90 s e o GET_LOCK do runner. O comando `php bin/migrar.php aplicar` segue aplicando tudo em sequência |
| **A demonstração** — montar e remover | `Controllers/Demonstracao.php`, `Models/demonstracao/AdmsDemonstracaoMontagem.php` | `adms_instalacao_estado` ('demonstracao.montagem'), GET_LOCK, até 120 s por passo |
| **Enturmar na turma** (escola: os alunos marcados) — desde a 1.39.0 | `Controllers/Turmas.php::enturmar`, `Models/escola/AdmsTurmaLote.php` | `adms_instalacao_estado` ('escola.lote.turma.<id>'), GET_LOCK, até 4 s por passo, aluno a aluno; a página fechada retoma ("Continuar") |
| **Envios ao Moodle → Enviar tudo o que falta** | `Controllers/MoodleEnvios.php::enviarTudo`, `Models/moodle/AdmsMoodleLote.php` | `adms_instalacao_estado` ('moodle.lote'), GET_LOCK, 5 s por passo; para quando o Moodle para de responder. O comando `database/ferramentas/moodle_enviar.php` faz os mesmos passos |
| **Importação de alunos por planilha → Aplicar** — desde a 1.46.0 | `Controllers/ImportacaoAlunos.php::aplicar`, `Models/importacao/AdmsImportacaoAlunos.php` (`iniciarAplicacao`, `passoAplicacao`) | o lote em "aplicando" (situação 4) e `adms_instalacao_estado` ('importacao.alunos.<id>'), GET_LOCK, 4 s por passo; cada linha numa transação, marcada "aplicada" junto. Tudo ou nada: a linha que falha faz desfazer as já gravadas (em blocos de 200) e o lote volta a "conferindo", com o erro da linha. O Moodle só no fim, com tudo gravado. A página fechada retoma ("Continuar"). Medido: 5.000 linhas em 2 a 3 minutos, nenhum passo acima de 5 s |
| **Relatórios que calculam as notas por oferta** — desde a 1.46.0 | `Controllers/Relatorios.php::exibir`, `Models/relatorios/AdmsRelatorioCalculo.php`, `AdmsRelatorioFonte` | acima de `relatorios.calculo_de_uma_vez` ofertas (padrão 20): o relatório a seco anota as ofertas; a janela calcula em passos de 4 s e guarda em `adms_relatorio_calculos` (uma hora, só para quem pediu); a página abre com `?calc=` e o mesmo texto da execução de uma vez. GET_LOCK por execução |
| **Confirmar enturmação** (Portal de alunos), com o envio automático ao Moodle — desde a 1.46.0 | `Controllers/PortalAlunos.php::confirmarEnturmacao`, `Models/moodle/AdmsMoodle.php` (`soFila`, `enviarItens`) | a confirmação grava e só põe na fila; a tela do aluno envia os itens dela em passos de 5 s (a lista na sessão); o Moodle fora do ar para o envio e o resto fica em Envios ao Moodle |

## Numa requisição, medido

| Operação (onde aparece) | Arquivos | O que cresce com os dados | Medido (Onda 6, Etapa 4) |
|---|---|---|---|
| Importação de alunos: **enviar o arquivo** (ler, guardar as linhas, conferir) e **corrigir / ajustar as colunas** (conferir de novo) | `Controllers/ImportacaoAlunos.php`, `Models/importacao/AdmsImportacaoAlunos.php::criar` e `::julgar` | as linhas (até 5000) | 5.000 linhas: o envio em 2,4 s; conferir de novo em 2 s. As linhas gravadas 250 por comando, numa transação; o curso e o polo de cada linha consultados uma vez por curso |
| Importação de alunos: **desfazer** | `::desfazer`, `::impedimentosDoLote` | os alunos do lote | 5.000 alunos em 5 a 6 s: o que impede, uma consulta por motivo; a retirada, um comando por tabela para até 500 alunos; numa transação só (inteiro) |
| **Encerrar a disciplina** (uma oferta) | `Controllers/Disciplinas.php::encerrar`, `Models/notas/AdmsEncerrarOferta.php` | os alunos da oferta | 5.000 alunos em 1,8 s (o cálculo, 0,6 s): o registro de cada aluno, 200 por comando, numa transação só (inteiro) |
| Importar notas (CSV) de uma atividade | `Controllers/Atividades.php::uploadNotas`, `Models/importacao/AdmsImportacaoCsv.php` | uma nota por aluno da turma | 2 MB; uma turma |
| Importar faltas (CSV) de uma oferta | `Controllers/Faltas.php::importar`, `Models/faltas/AdmsImportarFaltas.php` | uma linha por aluno, tudo ou nada | 2 MB; uma oferta |
| Regra de média: aplicar a disciplinas, definir a padrão, simular | `Controllers/RegraMedia.php`, `Models/notas/AdmsRegraMedia.php` | as linhas da matriz escolhidas; a simulação percorre as ofertas abertas | a tela de confirmação |
| Atualizar a lista (médias), gerar a oferta do ano | `Models/notas/AdmsAtualizarLista.php`, `Models/disciplinas/AdmsGerarOferta.php` | uma oferta / as disciplinas escolhidas | — |
| **Documentos** — boletim, histórico, atestado, declaração (a emissão, a prévia, a 2ª via) | `Models/academicos/AdmsGerarBoletimPdf.php`, `Models/historico/*` | as páginas de **um** aluno (Dompdf) | **não existe geração de documentos em lote** (nem PDF em lote, nem ZIP) |
| O instalador, a tela 5 (as tabelas) | `instalar/index.php` | fixo (o banco vazio) | a execução interrompida é detectada e explicada |

## Só pela linha de comando

| Operação | Arquivo | Observação |
|---|---|---|
| **Os guias da Ajuda** (o HTML e os PDFs por perfil) | `database/ferramentas/guias/gerar_guias.php` | do desenvolvimento, no pacote; não depende dos dados da instituição. Não há "guia" de pagamento no sistema |
| Backup, restauração, o pacote | `database/ferramentas/implantacao/backup.php`, `restaurar.php`, `gerar_pacote.php` | crescem com o banco e com os PDFs guardados; não há backup pela tela |
| Converter os modelos da 1.9.0 | `database/ferramentas/converter_modelos_190.php` | com `--simular` |

## O que não existe (perguntado no levantamento)

Documentos em lote (boletins, históricos, diplomas, certificados, ZIP de PDFs), guias de pagamento, e-mail em massa,
"encerrar o ano" e backup ou exportação pela tela.

## O padrão para uma operação nova

O estado numa chave de `adms_instalacao_estado` (ou numa tabela própria, quando o banco pode ser antigo), o GET_LOCK, o
POST com `fase=iniciar|passo` respondendo JSON, um orçamento de tempo por passo, e a janela de progresso (com
`$progressoNavegar`, ela abre sozinha o endereço do fim, onde a mensagem do resultado espera na sessão). O que precisa
ser inteiro fica numa transação só e trabalha por conjunto (um comando para muitas linhas), medido com o maior volume
que a tela aceita.
