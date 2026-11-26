# A escola (educação básica) — levantamento e desenho

O VCOSTAEDU nasceu no modelo de ENSINO SUPERIOR: curso → matriz (com módulos) → oferta (uma disciplina num ano) →
enturmação (o aluno na oferta), com uma média por disciplina, calculada pelo motor de médias (`core/MotorMedia.php`)
e encerrada por oferta. A escola é o segundo tipo de instituição e se constrói **em cima** dessa cadeia: nada vem de
outro sistema.

As três decisões de produto que valem para tudo abaixo:

1. **Um tipo por instalação** — escola OU ensino superior; não há instalação híbrida.
2. **O ensino superior não muda.** A régua dele fica idêntica do começo ao fim (veja "A régua do superior", no fim).
   Toda instalação que já existe é ensino superior, sem ninguém escolher nada.
3. **Quem instala usa; não programa.** As regras vêm prontas; a instituição mantém ou ajusta por tela, com o padrão
   já preenchido.

## 1. Onde o modelo de hoje supõe ensino superior

| Área | O que existe | O que supõe superior |
|---|---|---|
| **Dados** | `rm_cursos` (curso, com `exige_tcc`, `nivel`, polo, modalidade), `rm_cursos_grades` (matriz), `rm_cursos_turma_series` (módulo), `uni_rh_cursos_disciplinas_grade` (a linha da matriz), `rm_materias`, `rm_professores_materias` (a oferta: `ano`, `codtur`, `bimestre` = 3 "Anual" sempre), `portal_disciplinas_alunos` (enturmação, pela RA), `rm_matriculas_curso` (com forma de ingresso e polo), `adms_periodos_letivos` (só o ano) | não há tabela de etapa (bimestre/trimestre) nem de turma: o ano letivo é só o ano, a "turma" é o texto `codtur` da oferta |
| **Médias** | `MotorMedia::calcular` (pura), a fachada `AdmsCalculoNotas` (painel e portal), a regra efetiva da oferta (`AdmsRegraMedia::efetivaOferta`), o simulador, o encerramento `AdmsEncerrarOferta` (a regra copiada em JSON com sha256), a reabertura | **uma** média por oferta; atividades sem etapa; a recuperação não existe; a situação é aprovado/reprovado por oferta |
| **Onde se calcula** | tela de médias, de faltas, de chamada; o boletim (`AdmsBoletimCurso::doAluno`), o PDF, o portal, a vida acadêmica e o histórico, os relatórios (`AdmsRelatorioFonte`), o TCC — todos pela fachada | — (a fachada única é o que permite a escola sem mexer no superior) |
| **Telas e textos** | os rótulos estão escritos nas views (não há camada de vocabulário); o menu e os nomes das páginas vêm do banco | "curso", "disciplina", "módulo", "polo", "TCC", "atos legais", "histórico", "coordenação de curso", "semestre" (relatórios de coorte) |
| **Relatórios** | 22 (o hub `AdmsRelatorios`) | só do superior: Previsão de conclusão, Coorte de ingresso, Aptos a concluir, Concluintes e tempo até a conclusão, Situação do TCC |
| **Documentos** | boletim (tela e PDF), histórico parcial e final, atestado de matrícula, a emissão com verificação pública | o histórico (ato legal, TCC, titulação, polo, forma de ingresso) é do superior |
| **Portal** | boletim, notas das atividades, horário, atestado, progresso, perfil, entrada no Moodle | o boletim de uma etapa só ("Anual") |
| **API** | alunos, matrículas, enturmação (por oferta), cadastros de apoio (cursos, grades, anos, polos, modalidades, formas de ingresso, disciplinas, ofertas), com o limite por assunto | não há turma |
| **Importação** | alunos por planilha (RA, nome, curso, grade, ano, polo, modalidade…) | não há a coluna da turma |
| **Demonstração** | "Faculdade Horizonte (fictícia)": uma pós-graduação, seis disciplinas, oito alunos, uma turma de 2025 concluída | uma faculdade |
| **Primeiros passos** | 8 fases em `adms_fases` (instituição, regras, e-mail, ano letivo, curso, oferta, alunos, enturmar); a coluna `modulo` existe e não é usada | as fases do superior |
| **Moodle** | uma oferta = uma disciplina no Moodle, "matéria — turma (ano)", idnumber `<prefixo>oferta-<id>`; a renomeação acompanha a oferta | — (a turma entra no nome) |
| **Módulo ligado** | não há mecanismo genérico: só o parâmetro `matriz.modulos`, filtros fixos no menu (demonstração, primeiros passos) e o Moodle conectado | — |
| **Instalação** | o instalador (7 telas); "Como você quer começar?" na tela final; "Começar com os dados da minha instituição" (`Demonstracao::instituicao`); o critério "sem dado próprio" (`AdmsDemonstracaoMontagem::semDadoProprio`) | — |

## 2. O desenho

### 2.1 O princípio que protege o superior

**A escola vive em tabelas novas (`esc_*`) e em linhas novas das tabelas de configuração; nenhuma coluna nova em
tabela que o superior usa.** A régua tira um sha256 de cada linha de cada tabela: uma coluna nova (mesmo vazia) mudaria
o retrato do superior. Numa instalação de superior as tabelas `esc_*` ficam vazias, e o código da escola não roda.

### 2.2 O tipo de instituição (Etapa 1)

- **O dado:** a chave `instituicao.tipo` em `adms_instalacao_estado`, `superior` ou `escola`. A migration grava
  `superior` em toda instalação que já existe.
- **Onde se escolhe:** no instalador, numa tela própria depois de criar o Administrador (antes de "Como você quer
  começar?"), e em "Começar com os dados da minha instituição". Uma frase por tipo. A troca só vale **sem dado
  próprio** (o mesmo critério que dispensa a senha); depois, a tela mostra o tipo e diz por que não muda.
- **O módulo ligado:** a tabela `adms_paginas_modulo` (página → `escola` | `superior`); página que não está nela é do
  núcleo. O tipo liga o módulo do mesmo nome: a rota de um módulo desligado é recusada (`AdmsPaginas`) e o item sai do
  menu (`AdmsMenus`). Na escola saem: TCC, Atos legais, o histórico (prévia, emissões, modelos) e os 5 relatórios só do
  superior.
- **O vocabulário, num ponto só** (`core/Vocabulario.php`): na escola, "segmento" (o curso), "série/ano" (o módulo),
  "turma", "etapa" (bimestre ou trimestre), "disciplina" (como no superior: D-85 — "componente curricular" saiu), "unidade" (o polo); no superior, os
  termos de hoje. Tabelas, colunas e rotas não mudam.
- **O que a instalação mostra de si:** a verificação do ambiente, o Sobre (para quem entrou) e Avisos do sistema dizem
  o tipo.

### 2.3 A estrutura da escola (Etapa 2) — turma e etapa por cima de matriz → oferta → enturmação

| Conceito | Onde fica |
|---|---|
| **Segmento** | um curso (`rm_cursos`): Ensino Fundamental — anos finais; Ensino Médio |
| **Série** | um módulo da matriz (`rm_cursos_turma_series`), com o rótulo dele ("6º ano", "1ª série") |
| **Disciplina** (antes, "componente curricular"; D-85) | a matéria na linha da matriz, com a carga horária do ano |
| **Etapas do ano letivo** | `esc_etapas` (ano, ordem, nome, início, fim, data do conselho). Ao abrir o ano: 4 bimestres (o padrão) ou 3 trimestres; com lançamento no ano, não muda |
| **Turma** | `esc_turmas` (ano, segmento, matriz, série, nome, turno, sala, capacidade). Criar a turma gera as ofertas da série — uma por componente, pelas funções de hoje — ligadas em `esc_turma_ofertas`, cada uma com o seu professor |
| **Aluno na turma** | `esc_turma_alunos` (turma, RA, entrada, saída, para qual turma foi). Entrar enturma em todas as ofertas da turma; sair tira de todas; em lote, em passos |
| **Trocar de turma** (mesma série) | o que já foi lançado vai junto: a nota e as faltas de cada componente, até a data da troca, ficam registradas para a oferta do mesmo componente na turma nova (`esc_lancamentos_trazidos`), e o cálculo as usa nas etapas em que o aluno não tem lançamento na turma nova |
| **Configuração da escola** | `esc_configuracao` (chave e valor): a média mínima (6,0), a regra da recuperação, a composição da média anual e os pesos, a base e o mínimo da frequência (o ano letivo, 75%) |

As três portas iguais: a tela, a planilha (a coluna "turma") e a API (turmas nos cadastros de apoio; enturmar pela
turma), com o limite por assunto. Os perfis são os mesmos (a coordenação de curso coordena o segmento; o professor,
só nas ofertas dele). Os Primeiros passos da escola são fases do módulo `escola` em `adms_fases` (a coluna `modulo`).

### 2.4 A avaliação (Etapa 3) — uma camada nova por cima do motor

O núcleo do motor não muda. A camada nova (`Models/escola/AdmsMediaEscola.php`) chama `MotorMedia::calcular` **uma vez
por etapa**, com a regra da oferta, sobre as atividades daquela etapa (`esc_atividade_etapa`: toda atividade de uma
oferta da escola pertence a uma etapa).

1. **Média da etapa** = o motor sobre as atividades da etapa.
2. **Recuperação:** quem fecha a etapa abaixo da média mínima fica em recuperação (`esc_recuperacoes`); a nota da
   etapa é a **maior** entre a média e a recuperação (o padrão), ou a média das duas, ou a maior com teto na média
   mínima.
3. **Média anual:** a média simples das notas das etapas (o padrão) ou com pesos por etapa.
4. **Situação final:** aprovado; reprovado por nota; reprovado por falta (a frequência no total de horas do ano, 75%,
   ou por componente); aprovado pelo conselho.
5. **Conselho** (`esc_conselho`): por turma e etapa, e no fim do ano; aprovar exige observação e guarda quem e quando.
   A nota não muda: muda a situação, e a memória de cálculo mostra.
6. **Fechamento por etapa** (`esc_fechamentos`), com a regra copiada como no encerramento de hoje; reabrir guarda o
   substituído. O ano (o encerramento da oferta) só fecha com as etapas fechadas.
7. **Um cálculo só:** na escola, a fachada `AdmsCalculoNotas` entrega o resultado da camada no mesmo formato de hoje
   (a média final é a média anual), com as etapas a mais; tela, boletim, PDF, portal e relatórios leem dela. No
   superior, a fachada continua exatamente como está.

### 2.4.1 Como ficou (Etapa 3)

- **As telas:** na turma, cada componente leva a **Etapas e notas** (`EscolaNotas`): uma aba por etapa, a média, a recuperação (só para quem está abaixo da média mínima), as faltas da etapa, a nota da etapa; fechar e reabrir (com motivo); o resumo do ano (as notas das etapas, a média anual, a frequência, a situação). Na turma, o **Conselho de classe** (`EscolaConselho`).
- **As tabelas:** `esc_recuperacoes`, `esc_faltas_etapa`, `esc_fechamentos` e `esc_fechamento_notas`, `esc_conselho`, `esc_boletim_liberado`.
- **As travas:** a nota de uma atividade de etapa fechada, a atividade nova com a data numa etapa fechada, a falta e a recuperação da etapa fechada — recusadas, com a razão. O encerramento da oferta (o fim do ano) pede todas as etapas fechadas.

### 2.4.2 A etapa, a liberação do boletim e o conselho (a correção da escola, Parte 1; 1.94.0; D-100)

- **Quem fecha e quem reabre:** o professor fecha a etapa das disciplinas dele (a ação 107); a secretaria, a supervisão de secretaria e o administrador também. Reabrem só a supervisão de secretaria, a direção acadêmica e o administrador (a ação 108), com o motivo. A marca `adms_paginas.consulta_grava` deixa a direção ("só consulta") abrir as páginas que gravam a reabertura, a liberação e a correção do conselho.
- **A etapa fechada parece fechada:** na tela da etapa e na tela de notas da atividade, os campos aparecem travados, com o aviso no alto e a quem pedir a reabertura. A recuperação e as faltas gravam por um botão só (`escola-notas/gravar`), cada lado pela permissão dele; só a falta que mudou grava.
- **A liberação do boletim** (`EscolaLiberacao`; `esc_boletim_liberacao` e o histórico `esc_boletim_liberacao_hist`): por ano letivo, uma linha por etapa, a média final e a situação final, com "Exibir no portal" e "Liberar a partir de". O portal lê por `AdmsCalculoNotas::etapasLiberadas()` e `::finalLiberado()`: sem a média final, nenhuma média anual; sem a situação final, nem aprovado nem reprovado. A `esc_boletim_liberado` fica no banco, sem uso.
- **As Notas do portal** mostram a nota da recuperação (em tempo real), depois das atividades da etapa.
- **O conselho** abre na etapa em andamento; a observação é corrigida ou retirada pelo autor ou pela direção (`esc_conselho.situacao`, o histórico `esc_conselho_hist`); a aprovação retirada deixa de valer.
- **O relatório de reaberturas** mostra também a reabertura da etapa, com o motivo; quem reabre o vê.

### 2.4.3 Lançar a nota e a chamada (a correção da escola, Parte 2; 1.95.0; D-101)

- O Enter desce na hora e grava em segundo plano (as notas da atividade e a recuperação); a nota fora da escala segura o cursor.
- A chamada da disciplina que lança as faltas por etapa explica e oferece a troca num passo; com falta lançada (no total ou por etapa: `AdmsChamada::motivoSemChamada`), a troca é recusada — a primeira aula recalcularia a falta de cada etapa pelas aulas — e o atalho de "O meu dia" vira "Faltas".
- A chamada num sábado ou domingo pede a confirmação (só na escola).

### 2.4.4 O aluno novo e o que entra no meio do ano (a correção da escola, Parte 3; 1.96.0; D-102)

- A etapa que terminou antes da data da matrícula (`rm_matriculas_curso.data_ingresso`) fica sem nota e fora da média (`MediaEscola::calcular`, a marca `antesDaMatricula`).
- As notas de transferência (`esc_notas_transferencia`, a tela `EscolaTransferencia`): a nota e as faltas da escola de origem por disciplina e etapa; valem como a nota da etapa (a marca `transferencia`), contam na média, "T" no boletim; só a secretaria lança; histórico e auditoria.
- O número proposto no cadastro, a turma e a parcela inicial na matrícula, os candidatos da série e do ano, o atestado da escola (o modelo marcado para a escola) e o endereço na planilha dos responsáveis.
- **O boletim** (a mesma `AdmsBoletimCurso`, no painel, no PDF e no portal): as etapas em colunas ("R" quando houve recuperação), a média anual, as faltas, a frequência e a situação.

### 2.4.5 As palavras da escola (a correção da escola, Parte 4; 1.97.0; D-103)

- O número do aluno é "RM" na escola (`AdmsTipoInstituicao::rotuloNumero()`; o campo `ra` da API não muda); a chamada fala em aula; o cartão da disciplina, sem polo, modalidade e realização; a matrícula, sem "Trancado".
- As quantidades sem "(s)" (`AdmsUi::plural`); a prévia do encerramento e a ficha sem texto de sistema; com todas as etapas fechadas, Notas e resultado diz que falta encerrar.
- Os Parâmetros mostram o gravado e salvam só o que mudou; remover a demonstração apaga o financeiro dela e volta ao "Como você quer começar?"; o aluno no portal da família recebe "não permitido".

### 2.4.6 O histórico escolar da educação básica (D-110 em diante)

- O histórico do Fundamental e do Médio, com a sua fonte (a vida escolar), os seus cadastros (os textos por nível e vigência, as observações, o logo, as escolas, as disciplinas, o nível dos segmentos e o número das séries) e a emissão guardada de sempre: o desenho e as partes em `docs/HISTORICO_ESCOLAR.md`.

### 2.5 O que fica fora desta onda

Educação Infantil e anos iniciais do Fundamental (avaliam por conceito e parecer; o segmento é um curso e a série um
módulo, então nada impede que entrem depois); matrícula; financeiro; virada de ano e promoção; histórico da educação
básica; motor de itinerário formativo. Os responsáveis e o portal da família ficaram fora desta onda e entraram na
Onda 7 (a seção 6, abaixo).

## 3. A demonstração da escola: o Colégio Horizonte (fictício)

Numa instalação de escola, "Conhecer com dados de demonstração" monta o Colégio Horizonte (fictício)
(`database/ferramentas/demonstracao_escola.php`), pelas telas, em passos, removível, como a faculdade fictícia.

### A matriz da demonstração — um EXEMPLO que segue a Base

A matriz da demonstração é um exemplo que segue a Base Nacional Comum Curricular; cada escola ajusta a sua. Nada aqui
afirma conformidade legal de uma escola real.

| Segmento | Componentes (por área) | Carga horária do exemplo | De onde vem o número |
|---|---|---|---|
| Ensino Fundamental — anos finais (6º ao 9º ano) | Linguagens: Língua Portuguesa, Arte, Educação Física, Língua Inglesa; Matemática; Ciências da Natureza: Ciências; Ciências Humanas: Geografia, História; Ensino Religioso | 840 h por ano (o mínimo é 800 h, em ao menos 200 dias letivos) | as áreas e os componentes: a BNCC do Ensino Fundamental (Resolução CNE/CP nº 2/2017); as 800 h e os 200 dias: a LDB (Lei nº 9.394/1996), art. 24, I |
| Ensino Médio (1ª à 3ª série) — a formação geral básica | Linguagens e suas Tecnologias: Língua Portuguesa, Língua Inglesa, Arte, Educação Física; Matemática e suas Tecnologias; Ciências da Natureza e suas Tecnologias: Biologia, Física, Química; Ciências Humanas e Sociais Aplicadas: História, Geografia, Filosofia, Sociologia | 800 h por ano; 2.400 h nos três (o mínimo) | as áreas: a BNCC do Ensino Médio (Resolução CNE/CP nº 4/2018); as 2.400 h: a LDB, art. 35-C, na redação da Lei nº 14.945/2024 |
| Ensino Médio — o itinerário formativo (UM, de exemplo, cadastrado como componentes comuns) | Projeto de Ciência e Tecnologia; Laboratório de Investigação | 200 h por ano; 600 h nos três (o mínimo) | a LDB, art. 36, na redação da Lei nº 14.945/2024 |
| Ensino Médio — o total | | 1.000 h por ano; 3.000 h nos três | a LDB, art. 24, I (1.000 h anuais no ensino médio, Lei nº 13.415/2017) e a Lei nº 14.945/2024 |

Não há motor de itinerário nesta onda: o itinerário da demonstração são dois componentes comuns da matriz.

### O tamanho e os casos

- **Turmas:** no ano corrente, 6º ano A e B, 8º ano A, 9º ano A e 1ª série A — o 1º e o 2º bimestre fechados, o 3º
  aberto, com as notas (a etapa "atual" segue o calendário); uma turma de 9º ano do ano anterior, com os quatro
  bimestres fechados e o ano encerrado. O 8º ano A (as nove disciplinas, quatro alunos) entrou na 1.98.0 (D-104).
- **Os casos:** aprovado direto; a recuperação que salvou a etapa (4,0 → recuperação 6,5); a que não salvou (4,0 →
  recuperação 4,5); reprovado por falta com nota (a frequência no total de horas do ano abaixo de 75%); aprovado pelo
  conselho (reprovado em Matemática, a nota não muda); um aluno que trocou do 6º ano A para o 6º ano B depois do 1º
  bimestre (as notas foram junto); um aluno do 8º ano A que entrou no 3º bimestre, com as notas e as faltas da escola
  de origem lançadas (as notas de transferência); a Matemática das turmas do professor de demonstração em chamada por
  aula, com três aulas lançadas; a liberação do boletim com as etapas fechadas liberadas e a média final e a situação
  final ocultas.
- **Os nomes:** o colégio e os segmentos com a marca "(fictício)" e "(demonstração)"; os alunos com sobrenomes
  inventados (Exemplo, Fictício, Modelo…), e o responsável com um sobrenome do filho; a equipe é a da demonstração. A remoção apaga o que é da demonstração (os
  segmentos, os alunos da faixa da demonstração, as turmas e tudo da escola ligado a elas) e os componentes que mais
  nenhuma matriz usa, e também as aulas da chamada, as presenças e as notas de transferência da demonstração.

## 4. A régua do superior

A régua (`tests/tabler/regua.php`) compara com o retrato da 1.37.0 (`regua-onda3-etapa11.json`) durante a onda
inteira. **Idênticos, sempre:** os boletins, as vidas acadêmicas e todas as tabelas acadêmicas. **Podem diferir, só
quando a etapa os muda de propósito e com o nome de cada uma na prova:** as tabelas de configuração do sistema
(versões, novidades, páginas, ações, permissões, menus, fases, o estado da instalação). Uma tabela nova só pode
aparecer **vazia**.

## 5. O acréscimo e as regras prontas (Etapa 5, 1.42.0; D-50)

- **O acréscimo** é uma opção da regra de média (padrão: não). É um ponto somado **por cima da média fechada** (5,0 + acréscimo 1,0 → 6,0), não uma nota que entra na conta. O professor o lança como uma atividade do tipo "Acréscimo", que só aparece quando a regra da oferta o permite; ela fica fora dos componentes e sem peso. No motor (`core/MotorMedia.php`), as notas de acréscimo do aluno se somam, com o **limite** da regra (vazio = sem limite), e entram depois da composição dos componentes e **antes do arredondamento**, com **teto** em 10,00. A memória mostra a linha.
- **Na escola**, o motor roda uma vez por etapa (D-48): o limite vale **por etapa**, e cada etapa soma o seu acréscimo.
- A opção mora numa tabela própria por versão da regra (`rm_media_regra_acrescimo`). A regra sem a linha — todas as de antes — calcula exatamente como sempre, e a régua do ensino superior continua idêntica.
- **As regras prontas** entram no catálogo como dado (a migration 0029): "Só atividades" (um componente, 100%) e "Prova mensal + prova bimestral + atividades" (30% / 40% obrigatória / 30%), nota mínima 6,0. Nenhuma disciplina muda de regra. Na escola, os Primeiros passos têm a fase "As regras de média", que pergunta se a escola mantém as prontas ou ajusta.

## Onda 6, Etapa 5: o que ficou para trás

- **A chamada por aula** (D-55): em Regras da escola, "De onde vêm as faltas" — as lançadas por etapa (o padrão) ou a chamada por aula, pela tela da chamada do componente. A falta da etapa é a soma das ausências nas aulas com data na etapa; o total do componente é a soma das etapas; os encontros continuam as horas da matriz. A etapa fechada recusa a chamada com data nela.
- **Os relatórios** (D-56): a turma e a etapa como filtros, só na escola. Com a etapa, a nota de cada aluno é a da etapa (com a recuperação), as atividades e as faltas são as da etapa; a frequência e a situação são as do ano.
- **O aluno sem e-mail no Moodle** (D-57): a opção "E-mail para aluno sem e-mail", desligada por padrão; ligada, o endereço é a matrícula no domínio que a instituição informa, e o verdadeiro substitui o montado no envio seguinte. Desde a 1.58.0, substituída pelos três modos da D-68 (abaixo).
- **O e-mail do aluno no Moodle, em três modos** (Onda 8, Etapa 2; D-68): em Conectar ao Moodle, *sempre o e-mail da escola* (o padrão na escola: a matrícula no domínio da instituição), *o e-mail do aluno, ou o da escola* (sem e-mail, ou com o mesmo de outro aluno, o da escola), ou *só o e-mail do aluno* (o padrão no ensino superior; sem e-mail, ou repetido, o aluno fica fora, com o motivo). O e-mail da escola é montado na hora do envio: nunca é gravado no cadastro e não recebe mensagens (o aluno entra no Moodle pelo portal). Só a conta que o sistema criou no Moodle acompanha o modo e o domínio; a que já existia lá, e que o sistema só ligou, fica com o e-mail dela. Irmãos com o mesmo e-mail no cadastro têm cada um a própria conta.
- **O atestado na demonstração** (Onda 8, Etapa 3; D-69): cada aluno fictício tem um CPF de demonstração (000 + a
  matrícula, com os verificadores válidos) e a previsão de conclusão coerente com a série; o atestado sai para o aluno e
  para o responsável.
- **Do site da instituição ao Moodle numa chamada só** (Onda 8; D-70): `POST /inscricoes` com `{"turma_id"}` no destino
  faz o cadastro, a matrícula e a turma (tudo ou nada) e manda a pessoa ao Moodle, no modo de e-mail escolhido; o que não
  coube termina sozinho. A demonstração tem o curso livre do site de exemplo (`exemplos/curso-livre`): a turma aberta, os
  quatro módulos como componentes (3, 2, 3 e 2 horas na matriz, que só aceita horas inteiras), 500 vagas.
- **O horário de aulas** (Onda 8, correção; D-71): as aulas do dia de cada turno em **Horários de aula** (um horário
  padrão por turno na instalação nova, editável); a tela da turma ganha **Horário**, a grade da semana em que cada
  escolha grava na hora; o portal do aluno e o do responsável mostram a semana. A demonstração monta a semana das quatro
  turmas de 2026.
- **As séries livres:** qualquer segmento e qualquer série, do 1º ano do Fundamental em diante (provado com um 3º ano dos anos iniciais); a avaliação é por nota, de 0 a 10.

## 6. Onda 7: os responsáveis e o portal da família

Só na escola; no superior nada aparece e nada muda. O detalhe está em `docs/RESPONSAVEIS.md` (D-62 a D-66).

- **O cadastro e os papéis:** a pessoa responsável (nome, e-mail, celular, CPF opcional) e, em cada aluno e em cada ano
  letivo, o papel: o responsável financeiro (exatamente um, pela restrição do banco), o acadêmico titular (um) e o acesso
  acadêmico adicional (com o motivo, quando a escola permite). A troca encerra o anterior na data, com o motivo. Pela
  tela, pela planilha própria e pela interface de integração.
- **O convite:** um link de uso único com prazo, por e-mail, copiando o link ou pelo WhatsApp de quem está na
  secretaria; a pessoa cria a senha e entra pela mesma tela do portal (o e-mail ou o CPF). Cancelar, bloquear e "esqueci
  a senha".
- **O portal do responsável, só de leitura:** os filhos, a troca de filho e de ano, o boletim (a mesma liberação do
  aluno), as notas das atividades, a frequência, o horário, a turma e o atestado de matrícula.
- **A barreira:** um ponto único (`VcAcessoFamilia`) responde, a cada requisição, se aquela pessoa vê o acadêmico
  daquele aluno naquele ano; "pode ver o financeiro?" responde sim só ao financeiro, para o módulo que ainda não existe.
  Nenhuma tela mostra financeiro. A conferência fica em Verificações de segurança ("Portal do responsável").
- **A demonstração, os Primeiros passos, a ajuda e os relatórios:** o Colégio Horizonte ganha cinco responsáveis
  fictícios (os casos de família); a fase "Os responsáveis e o convite"; os guias; os relatórios R-SEC-05 (os alunos sem
  o financeiro ou sem o titular) e R-AUD-05 (os acessos adicionais em vigor).

## 7. O curso livre (o fechamento, Parte 1; 1.100.0; D-106)

O terceiro tipo de instituição, escolhido na instalação. Ele usa a **estrutura por turma** desta escola — o curso, a
matriz com as disciplinas (os módulos do curso), o ano letivo e a turma com vagas — e deixa de fora o que é pedagógico da
escola: as etapas (a turma não as pede), a série (a turma fica no módulo interno da matriz, nunca exibido, e o nome dela é
só o nome), o boletim, as notas e as faltas, o conselho, o horário, o atestado, os responsáveis e o RM (no curso livre, a
"Matrícula"). No código: `AdmsTipoInstituicao::porTurma()` (a escola e o curso livre) para os pontos estruturais e
`ehEscola()` para o resto; a marca do tipo nas páginas é a lista com vírgula de `adms_paginas_modulo` ("escola,livre",
"escola,superior") e, no portal, `col_paginas_modulo`. O financeiro e a matrícula pela secretaria são recursos de ligar e
desligar (`adms_paginas_recurso`; Configurações → Recursos do curso livre). A demonstração é o Instituto Ventoalto
(fictício), com o curso "IA na prática para professores" (`database/ferramentas/demonstracao_livre.php`). A régua do
superior não muda: as duas tabelas novas são de configuração.
