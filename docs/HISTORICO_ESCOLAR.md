# O histórico escolar da educação básica

Só na escola. O histórico do ensino superior (os modelos editáveis, a vida acadêmica, a emissão PARCIAL e FINAL) não
muda em nada; o da escola tem a sua fonte (a vida escolar), o seu desenho fixo (o dos modelos tradicionais da escola) e
passa pela mesma emissão guardada, com o código de verificação, o hash, o QR e a conferência pública.

O princípio da escola vale aqui também: **só tabelas novas** (`esc_hist_*`), vazias no ensino superior; nenhuma coluna
nova em tabela que o superior usa (o aluno, a série, o segmento e a disciplina são compartilhados: o que é só do
histórico mora numa tabela própria, ligada pelo id).

## As partes

| Parte | O quê | Decisão |
|---|---|---|
| A | Os cadastros e os textos | D-110 |
| B | A matriz curricular por ano | D-111 |
| C | A vida escolar | D-112 |
| D | O modelo do Fundamental, frente e verso | D-113 |
| E | A prévia, a emissão, as vias e o cancelamento | D-114 |
| F | A demonstração do colégio com o histórico | D-115 |
| G | O modelo do Ensino Médio, com o itinerário da matrícula do ano | D-116 |
| H | A saída no meio do ano e o ano letivo fechado | D-117 |
| J | Emitir e cancelar como ações próprias | D-119 |

## Parte A — os cadastros e os textos (D-110)

**Cadastros do histórico** (menu Ensino; páginas 620 a 628; a ação 130), em abas:

- **Textos** por nível (Fundamental, Médio) e **vigência**: o documento usa o texto de maior "vigente desde" até a data
  dele ("Qual texto vale na data" mostra qual). A vigência nova começa com a cópia da que vale hoje. Os campos: as três
  linhas do órgão, o nome e o endereço da escola, os atos legais da escola e do curso, o turno, a escala avaliatória, os
  textos da declaração e do certificado (os marcadores `{escola} {aluno} {ra} {rg} {serie} {ano} {etapa}`; o marcador que
  não existe é recusado), a cidade, a secretária e a diretora (nome, documento e título), o **rótulo do registro do aluno
  na rede** (o que um estado chama de RA) e o **rótulo do número de publicação**, e a unidade da carga no quadro (aulas ou
  horas).
- **Nada de um estado fica no código.** Cada texto do documento segue uma ordem de prioridade: o cadastro do histórico
  (a vigência da data) → os dados da instituição (o nome, o endereço, a cidade, o logo) → vazio (a prévia avisa). Sem
  nenhuma vigência, o texto de partida é neutro e nacional (a LDB).
- **Observações** por nível: a chave (estável; `conselho` é a do conselho de classe), o título, o texto (`{serie}`,
  `{ano}`), o tipo (sai sempre, ou marcada por série), a ordem e se está ativa.
- **Logo** por nível (PNG ou JPEG até 1 MB); sem ele, o da instituição.
- **Escolas**: nome, município e UF (obrigatórios), rede, ativa, e "esta escola" (uma só: marcar outra desmarca).
- **Disciplinas**: o nome no histórico, a marca do Ensino Religioso e a ordem no quadro de cada nível (0 = depois das
  ordenadas, na ordem da matriz). Só a disciplina que muda ganha a linha própria; as outras saem com o nome do cadastro.
- **Segmentos e séries**: o nível de cada segmento (o histórico do Fundamental junta os segmentos marcados como
  Fundamental: anos iniciais e finais podem ser dois) e o número de cada série (1 a 9 no Fundamental, 1 a 3 no Médio),
  propostos pelo nome e gravados pela tela.
- **Registro de alterações**: quem, quando e o que mudou, em texto ("Turno: "" → "Integral""). Grava só os campos que
  mudaram; salvar igual não grava nada.

**Dados do aluno para o histórico** (`historico-escola/aluno/<id>`): o RA (o registro na rede estadual; o RM continua o
número do aluno na escola), a certidão de nascimento, a observação livre e a conclusão do Fundamental (para o Médio).

**Quem:** abrem o administrador, a direção, a secretaria e a supervisão de secretaria; a direção só consulta (a tela
esconde os botões e a rota que grava a recusa). O professor e a coordenação não abrem.

**O dado de prova:** `esc_hist_dado_prova` registra o que as provas criam (os alunos "PROVA HIST …"); as telas mostram o
selo PROVA.

**As tabelas:** `esc_hist_segmento`, `esc_hist_serie`, `esc_hist_texto`, `esc_hist_observacao`, `esc_hist_logo`,
`esc_hist_escola`, `esc_hist_disciplina`, `esc_hist_aluno`, `esc_hist_alteracao`, `esc_hist_dado_prova` (a migration
0090).

## Parte B — a matriz curricular por ano (D-111)

**Uma matriz só** (a decisão 12): a grade do segmento continua sendo a matriz da plataforma (as linhas série ×
disciplina, com a carga em horas que alimenta a frequência, que não muda). O histórico acrescenta, numa tabela própria
da escola, **a matriz do ano** (`esc_hist_matriz`: a grade × o ano letivo) com as semanas letivas e a duração da aula
(parâmetros, 40 e 50 de partida), os textos da folha (fundamento legal, autorização, período e horário, a data da
folha) e, em cada linha da grade (`esc_hist_matriz_item`), as **aulas semanais**, o **grupo** (base nacional comum,
parte diversificada; no Médio, formação geral básica com a **área**, itinerário comum, itinerário formativo com o
**número**) e a ordem. Os itinerários do ano e as áreas ficam em `esc_hist_itinerario` e `esc_hist_area`.

- **Os totais são sempre calculados:** aulas anuais = semanais × semanas; horas = aulas anuais × minutos ÷ 60 (com uma
  casa: 1.000 aulas de 50 minutos = 833,3 horas). Nenhum total é gravado. Os itinerários formativos de uma série contam
  um só (o de mais aulas).
- **Matriz do histórico** (menu Ensino; páginas 630 a 636): a lista por ano e segmento, com os totais de cada série;
  **nova** (as aulas propostas pela carga da grade, em aulas de 50 minutos e 40 semanas) ou **copiando** outra do mesmo
  nível (as linhas casadas pela série e pela disciplina); a edição com os totais ao vivo; **travar** (o ano está em uso):
  travada, cada correção pede o motivo, que vai para o registro com o antes e o depois; a **folha da matriz** em PDF
  (o Fundamental em paisagem, o Médio em retrato), com a identificação da escola (os textos do histórico), o fundamento,
  a autorização, o período, os totais, a data editável e a assinatura da direção.
- **Cada ano fica como era:** o histórico usa a matriz do ano em que a série foi cursada; sem ela, a do ano mais
  próximo da mesma grade, com aviso na prévia (e aviso quando a matriz é de prova).

## Parte C — a vida escolar (D-112)

**Histórico escolar** (menu Educacional; páginas 640 a 646): a **busca** acha os alunos ativos e os ex-alunos, por nome
ou RM, com a situação real ("ativo em AAAA", "saiu em dd/mm/aaaa", "ex-aluno (último ano AAAA)") e o selo PROVA.

**A vida escolar do aluno**, por nível: uma linha por série, em ordem cronológica.
- **Proposta:** o sistema tira as séries das turmas do aluno, uma por ano (a turma em que ele está; senão, a última),
  com a escola "esta escola" e o resultado proposto: no **ano aberto**, "em curso"; no **ano fechado**, pela situação
  final de cada disciplina (todas aprovadas → aprovado; o conselho marcado quando uma foi aprovada pelo conselho;
  alguma reprovada → retido; sem situação final → "a confirmar", com o motivo). O aluno que saiu da turma sem ir para
  outra fica "a confirmar" ("saiu da turma em …").
- **O marcador do ano letivo fechado** (Parte H; D-117) decide quando existe. **Sem ele**, um ano é fechado quando todas as disciplinas
  da turma estão encerradas.
- **Confirmar** grava a série ("do sistema"); **editar** (a escola, o resultado, o Ensino Religioso digitado — nota, F,
  NF ou "-" —, o conselho, as observações selecionáveis da série, o itinerário); **digitar** um ano cursado em outra
  escola (a escola do cadastro é obrigatória); **tirar** (a confirmada volta a ser proposta; a digitada sai com as
  notas). A **retenção** é a mesma série num ano posterior (marcada "repetida").
- **As notas da série:** a cursada aqui mostra, para cada disciplina da matriz do ano, a **média do sistema, lida na
  hora** pela função única da plataforma (a mesma do boletim; nunca copiada) e a **média sobreposta** (registrada com a
  do sistema e a nova; vazia volta a valer a do sistema). A disciplina com nota no sistema e fora da matriz do ano não
  sai e vira aviso. A digitada leva a média e a carga de cada disciplina, do cadastro ou **criada na hora** (base comum ou
  parte diversificada).
- Tudo vai para o registro de alterações, com o aluno.

**As tabelas:** `esc_hist_vida_serie` (só a série confirmada ou digitada; a proposta é lida na hora) e
`esc_hist_vida_nota` (a média digitada ou sobreposta) — a migration 0092.

## Parte D — o modelo do Fundamental (D-113)

O documento sai no **desenho do modelo** da escola, frente e verso (`Views/historicoEscola/pdf/fundamental.php`), e
**todo número vem do sistema** (`AdmsHistDocumento::montar`):

- **Frente:** o cabeçalho (o logo do nível, as linhas do órgão, a escola, o endereço, o ato legal da escola); "HISTÓRICO
  ESCOLAR - ENSINO FUNDAMENTAL" com o ato legal do curso; **10** o registro de matrícula (o RM); a identificação (o nome,
  o registro na rede com o rótulo do cadastro, o RG, o nascimento); **30** o quadro dos resultados — uma coluna por série
  (1º ao 9º), com o ano; a **retenção** é uma coluna a mais, em ordem cronológica; as disciplinas da base nacional comum
  (o rótulo vertical "BASE NACIONAL COMUM - Artº 26 da Lei 9394/96"), na ordem do histórico; a parte diversificada (o
  rótulo vertical e o "§ 5º do Artigo 26 da Lei 9394/96"); os totais de carga (na unidade do cadastro: aulas ou horas);
  o Ensino Religioso; **40** os estudos realizados (série, ano, estabelecimento, município, UF; "(transfere-se)" na
  série da saída).
- **Série não cursada:** em branco. **Série em curso ou da transferência:** sem nota e sem carga na frente (na da
  saída, "TRANSFERE-SE" vertical na coluna).
- **Verso:** **50** a transferência durante o período letivo — a última série cursada aqui, em curso ou da saída: as
  notas das etapas do ano (as colunas seguem as etapas parametrizadas: bimestres ou trimestres), só as iniciadas até a
  data, a nota trazida da escola de origem marcada com "*"; as faltas; as aulas dadas propostas pela matriz (aulas
  semanais × semanas decorridas) e os dias letivos (os dias úteis do início do ano até a data), os dois editáveis na
  prévia; sem série em curso, o bloco sai em branco com as disciplinas da última série; **60** a escala avaliatória;
  **70** as observações (as padrão, as marcadas em cada série com `{serie}` e `{ano}`, a do conselho, a certidão, a
  livre); o **fecho**: o CERTIFICADO na conclusão do 9º ano com aprovação, a DECLARAÇÃO nas demais (aprovado → a série
  seguinte no ano seguinte; retido → a mesma série no ano seguinte; em curso → a mesma série no mesmo ano; a etapa "anos
  iniciais" até o 5º, "anos finais" depois), com os marcadores trocados e o texto limpo; a data, as assinaturas, o
  número de publicação; a verificação (o endereço, o código e o QR).
- **O encaixe:** os degraus (a altura da linha e o corpo da letra, do maior para o menor); fica o primeiro que dá
  **exatamente duas páginas** e **nada além da margem direita** — contar as páginas não pega a tabela que passa da
  margem, então a largura é medida nos desenhos do próprio PDF. As páginas são contadas no PDF já com o rodapé (o
  código, a via, a página).
- **A técnica:** o mesmo Dompdf, a Helvetica, as larguras na primeira linha de cada tabela (uma linha sem altura), sem
  `table-layout: fixed`, o rótulo vertical num `div` girado com `transform-origin: 0 0`, cada bloco sem quebra de
  página, as imagens em data URI.

## Parte E — a prévia, a emissão, as vias e o cancelamento (D-114)

**Prévia e emissão** (da vida escolar do aluno; páginas 650 a 655): os **avisos** (só "o quadro não cabe" e "sem série
no nível" impedem; os outros ficam com quem emite, que confirma que os leu); a data do documento, o número de
publicação, os dias letivos e as aulas dadas do bloco de transferência; a **prévia em PDF** marcada PRÉVIA (nada é
gravado; também em HTML, `?formato=html`, com o degrau, as páginas e a largura nos cabeçalhos).

**Emitir** grava na **emissão guardada de sempre** (`rm_historico_emissoes`) com o tipo próprio da escola, `ESC_EF`: o
código de verificação aleatório, o conteúdo inteiro em JSON canônico (o histórico, os avisos aceitos, o degrau), o HTML,
o PDF fora do acesso web, o sha256 do conteúdo e o do PDF; o rodapé de cada página leva o código, a via e a página, e
o verso, o endereço da conferência e o QR. **A conferência pública** de sempre acha o histórico ("Histórico escolar —
Ensino Fundamental") e confere o PDF enviado. **Reimprimir é a 2ª via** (`rm_historico_emissao_vias`: o mesmo HTML, o
mesmo código, o número da via no rodapé), também pela lista de documentos emitidos. **Cancelar** pede o motivo; a via
cancelada fica guardada. O PDF só é entregue se o sha256 do arquivo confere com o gravado.

**Quem:** emitir e reimprimir são a ação 131; cancelar, a ação 133 (Parte J; D-119) — as duas da secretaria, da
supervisão, do administrador e também da **direção** (a marca `consulta_grava`), sem a marca de sensível; cada perfil
pode ter uma sem a outra pela tela "Perfis de acesso". O histórico do ensino superior (PARCIAL, FINAL) continua
fechado na escola, pelas travas de sempre.

## Parte F — a demonstração do colégio com o histórico (D-115)

A demonstração do colégio monta o histórico do Fundamental **pela tela da secretaria fictícia**, no fim da montagem: os
níveis e as séries (o curso livre fora), as disciplinas do Fundamental na ordem da grade, "esta escola" e uma escola de
origem fictícia, os textos e o logo de exemplo, o RA e a certidão fictícios, as matrizes de 2025 (travada) e de 2026 e
a vida escolar de três alunos:

| Aluno (fictício) | A vida escolar | O histórico |
|---|---|---|
| Rui Fictício Moura (255003) | 6º ao 8º digitados ("esta escola"); 9º de 2025 do sistema, retido por falta | declara a série que ele deve cursar |
| Paulo Hipotético Lima (255001) | 6º ao 8º digitados; 9º de 2025 aprovado | o certificado; **já emitido** na montagem (a lista e a conferência pública) |
| Vítor Inventado Gomes (265044) | 6º e 7º digitados com a escola de origem; 8º de 2026 em curso | os anos de antes com a escola de origem; as notas trazidas com "*" no bloco da transferência |

A remoção leva tudo o que a demonstração criou no histórico (e o registro de alterações); o que a escola cadastrou com o
próprio usuário fica.

## Parte G — o modelo do Ensino Médio (D-116)

O histórico do Médio, frente e verso, em retrato:

- **A frente**: o quadro por **área** da formação geral básica (o rótulo vertical; cada área com as disciplinas dela), o
  total da base; o **itinerário formativo** que o aluno cursou ("ITINERÁRIO FORMATIVO n" e o nome), com as disciplinas e
  o total; os totais da série sob as notas.
- **O verso**: os estudos realizados (o curso anterior — a conclusão do Fundamental, dos dados do aluno para o histórico
  — e as séries do Médio), a transferência por área e itinerário, as observações, o fecho, as assinaturas, a publicação e
  a verificação.
- **O itinerário do aluno é o da matrícula do ano**: o cartão "Itinerário formativo dos alunos" na tela da turma do
  Médio (só quando a matriz do histórico do ano tem itinerário formativo). Do grupo do itinerário formativo, só as
  disciplinas do itinerário do aluno entram; sem o itinerário, a prévia avisa e saem as de todos.
- **O encaixe**: o mesmo dos degraus do Fundamental. O pior caso (cinco colunas, dezesseis linhas de itinerário) dá duas
  páginas e fica dentro da margem, medido no PDF.

## Parte H — a saída no meio do ano e o ano letivo fechado (D-117)

- **A data da saída** é digitada na tela da turma (de partida, hoje; do começo do ano letivo da turma até hoje).
- **O ano parcial** do aluno que saiu sem ir para outra turma: as etapas até a data da saída com nota; a que começou
  depois fica **fora da conta** (sem nota, fora da média anual), como a de antes da matrícula.
- **"Transferido"**: a vida escolar o propõe com a data, quando a saída é antes do fim do ano letivo (depois, "a
  confirmar"); a data fica na série (editável) e o bloco de transferência sai preenchido até ela.
- **O ano letivo fechado**: Histórico escolar → "O ano letivo: fechar e reabrir" (a secretaria e a direção). Reabrir
  pede o motivo; o histórico do marcador mostra quem fechou e reabriu, quando e por quê. Sem o marcador, vale o critério
  das disciplinas encerradas.

## Parte J — emitir e cancelar como ações próprias (D-119)

- **Emitir** (e a 2ª via, a reimpressão): a ação 131. **Cancelar**: a ação 133.
- De fábrica, as duas com a secretaria e a direção (e a supervisão e o administrador); a coordenação e o professor não
  abrem. Em "Perfis de acesso", cada perfil pode ficar com uma sem a outra; o botão de cancelar segue a permissão de
  cancelar.
