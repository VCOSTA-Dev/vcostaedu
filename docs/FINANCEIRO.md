# O financeiro

O financeiro da plataforma (Onda 8; D-98, refeito desde a D-99): a ficha financeira, o Receber com o recibo e as ações
com o desfazer de cada uma, no desenho do sistema de origem — as mesmas telas, os mesmos campos, a mesma lógica de
tabelas —, sem o que depende de integração (boleto, cobrança bancária, Pix pelo banco, retorno bancário) e sem os defeitos
que o levantamento marcou lá (aqui tudo grava numa transação, o servidor recalcula ao gravar e a situação atual é
conferida). Vale para os dois tipos de instituição. O pagamento é recebido por fora (no caixa, no Pix da escola, na
maquininha) e registrado aqui.

O refeito vem em partes (D-99): **1** a ficha, o Receber, o recibo e o Desfazer recebimento (1.93.0); **2** a renegociação
com tela própria (1.99.0; D-105); **3** os inadimplentes e a lista de débitos, com a trava da rematrícula; depois, baixar, cancelar e
prescrever em lote, o histórico, os planos por tipo e as bolsas, o portal, e a demonstração e os guias. Até a parte de
cada uma, Baixar, Cancelar e o histórico funcionam como na 1.92.0.

## O desenho

- **O dinheiro em centavos inteiros** (`BIGINT`), nunca em ponto flutuante; as taxas em centésimos de ponto percentual
  (2% = 200). A conversão de "1.234,56" e a escrita de volta ficam em `AdmsFinanceiro::centavos` e `::brl`.
- **As regras** (`fin_regras`, uma linha): a multa por atraso, os juros ao mês e o desconto até o vencimento.
- **Os encargos** (`AdmsFinanceiro::encargos`, a função única): até o vencimento, sem multa e sem juros, e com o
  desconto (se houver); depois, a multa (uma vez) e os juros pró-rata por dia de atraso (valor × juros ÷ 30 × dias), sem
  desconto. O arredondamento é o meio para cima, em centavos. A tela pede a conta ao servidor ao mudar a data
  (`financeiro/encargos`), e o servidor recalcula ao gravar: o que veio da tela não vale.
- **Os planos** (`fin_planos`): o nome, as parcelas, o valor, o dia de vencimento (1 a 28) e o mês da primeira. Mudar um
  plano vale para as matrículas de depois.
- **Os títulos** (`fin_titulos`): o aluno, a matrícula (opcional no avulso), o plano, a parcela e de quantas (a coluna
  "Parc.": 3.12), o ano letivo, a descrição, o valor, o vencimento e UMA situação gravada: `aberto`, `recebido`,
  `baixado`, `cancelado`, `renegociado`, `prescrito`. "A vencer" e "Atrasado" saem da data de hoje. A parcela de um plano
  é única por matrícula, plano e número: gerar de novo não duplica. As parcelas de uma renegociação são do ano letivo dos
  títulos de origem.
- **O recibo** (`fin_recibos`): um por recebimento, com o número sequencial próprio (`fin_sequencias`, travado na
  transação: a transação desfeita não gasta número), de quem se recebeu (na escola, o responsável financeiro: com
  todos os títulos do mesmo ano letivo, o daquele ano; com títulos de anos diferentes, o do ano letivo corrente, e a tela
  avisa; no ensino superior, o aluno), a data, o original, a multa, os juros, o desconto até o vencimento, o acréscimo, o
  desconto, o total, a justificativa e a observação. Uma linha por título (`fin_recibo_titulos`: o devido de cada um e a
  parte do acréscimo e do desconto) e uma linha por forma (`fin_recibo_formas`: o dinheiro; o Pix manual com o CPF ou o
  CNPJ de quem pagou; o cartão com o tipo, as parcelas, a bandeira, o final, o documento e a autorização). A
  transferência não é uma forma do Receber (não é mais usada); ela e o "outro" aparecem só nos recebimentos da 1.92.0. O recibo desfeito fica, "estornado", com a data, o motivo e a
  justificativa; as linhas e as formas também ficam, marcadas.
- **As listas** (em Configurações): as bandeiras de cartão (`fin_bandeiras`) e os motivos de cada operação
  (`fin_motivos`), com a lista padrão; incluir, renomear, mudar a ordem, tirar de uso — nada se apaga.
- **O histórico** (`fin_operacoes`): cada ação e cada desfazer, com quem, quando, o motivo, o antes e o depois; o
  recebimento de cada título aponta o recibo (`recibo_id`). O desfazer marca a operação desfeita (`desfeita_em`) e
  registra a sua. Nada é apagado. O atendimento é uma operação do tipo `atendimento`.
- **Os acordos** (`fin_renegociacoes`, D-105): o número próprio (sequencial, `fin_sequencias` "acordo"), a condição
  (parcelada ou à vista), a proposta de que nasceu, o total com os encargos do dia, o desconto, a entrada, as parcelas e o
  primeiro vencimento; as parcelas do acordo apontam para ele (`renegociacao_id`) e têm a marca `sem_multa` (pagam juros,
  não pagam multa); os originais apontam para o que os absorveu (`renegociado_por`).
- **As propostas** (`fin_propostas`, com `fin_proposta_titulos` e `fin_proposta_parcelas`): as duas condições, guardadas
  com os valores do dia (os títulos com a conta e as parcelas de cada condição); a situação salva, formalizada (virou
  acordo) ou substituída; a vencida é a salva com a data passada.

## A ficha financeira

A tela (`financeiro/ficha/{aluno}`) abre da ficha do aluno, da matrícula e da busca do alto. Na disposição da de origem:

- **No alto:** "Ficha Financeira", as abas Ficha Financeira, Renegociações e Histórico, a busca de outro aluno (o nome, o
  número de matrícula ou, na escola, o responsável) e "Novo título" (o título avulso, com o ano letivo, e "Aplicar um
  plano").
- **À esquerda:** o número de matrícula ("RM" na escola, "RA" no ensino superior) e o nome; "Ano letivo" com uma pílula
  por ano em que o aluno tem matrícula ou título; o segmento, a série e "Contrato: nº" (a matrícula do ano); os
  responsáveis do ano (o nome e o CPF, o papel — Financeiro, Acadêmico ou os dois —, o parentesco, o celular com o botão
  do WhatsApp, só o link, o cadastro e "Ver endereço"), o financeiro primeiro. No ensino superior, o próprio aluno
  (o pagador).
- **À direita:** os três totais — Em atraso, A vencer (com o próximo vencimento) e Pago no ano — e o resumo: Inadimplente
  (algum título em aberto vencido, de qualquer ano) ou Adimplente, e o último pagamento (o dia do recibo mais recente e a
  soma dos recibos desse dia). O selo da bolsa entra com as bolsas; a restrição, depois.
- **Embaixo:** as abas da lista — **Atual** (o ano escolhido, os seguintes e o que está em aberto de anos anteriores; a
  linha do atraso de ano anterior em laranja), **Pendências** (tudo em aberto) e **Anos anteriores** (um ano de cada
  vez) —; os filtros — o período (por vencimento ou por recebimento; todo o período, hoje, últimos 7 e 30 dias, próximos 7
  e 30 dias, este mês, mês passado, próximo mês, personalizado) e as nove situações com a contagem —; o cartão **Em
  lote** (Financeiro: Receber, Renegociar; Administrativo: Baixar, Cancelar; cada botão entra no modo de seleção, com
  "Selecionar todos", "Limpar seleção", o contador e "Continuar"); e a tabela (Ano, Título, Descrição, Parc., Valor,
  Encargos hoje, Valor Bolsa, Valor até o desconto, Valor Pago, Data Venc., Data do desconto, Data Receb, Usuário,
  Situação, Ações), com a exportação (Excel, CSV, PDF, imprimir), "mostrar N" e a pesquisa.
- **A troca de ano** muda o cabeçalho, os responsáveis, os totais e a tabela. Os totais valem para o recorte da aba Atual
  do ano escolhido.
- **O menu Ações** de cada título: Receber (em aberto), Visualizar (os dados, os recibos e o histórico do título),
  Recibo e Desfazer recebimento (recebido), Baixar e Cancelar (em aberto) e os desfazer (baixado, cancelado).

## Receber e o recibo

- **A tela "Receber pagamento"** (`financeiro/receber-pagamento/{aluno}?titulos=…`), para um título (pelo menu) ou vários
  (pelo modo lote): a data do recebimento (hoje, ou
  antes; a conta de cada título é refeita na tela ao mudar a data — a data incompleta não pede conta, e a resposta sem os
  números não muda a de antes —, e de novo pelo servidor ao gravar), a tabela dos títulos (o original, os juros, a multa, o atualizado), os quadros (o total original, os encargos,
  o acréscimo, o desconto e o valor a receber; com acréscimo ou desconto, a justificativa é obrigatória), as formas, a
  observação e os botões "Receber e emitir recibo" e "Receber e finalizar".
- **O rateio:** o acréscimo e o desconto se dividem entre os títulos na proporção do devido de cada um, em centavos; o
  centavo que sobra vai para o maior resto (no empate, o primeiro título). A soma fecha sempre.
- **Múltiplos:** de 2 a 6 formas, cada uma com o valor e os dados dela; a tela mostra "Falta", "Fechou" ou "Passou" e só
  envia quando fecha; o servidor confere de novo, ao centavo.
- **A confirmação** é uma janela do próprio sistema, com o total e as formas (sem a janela do navegador).
- **O recibo** (`financeiro/recibo/{id}`; no portal, o mesmo, com a barreira do responsável financeiro do ano): duas vias
  numa folha, com os dados da instituição, o número, "Recebemos de … a importância de R$ X (por extenso)", o aluno com a
  série, os títulos, o acréscimo ou o desconto, a observação, as formas por extenso, a data por extenso e quem recebeu. O
  estornado sai com a faixa "ESTORNADO em dd/mm/aaaa — não vale como comprovante", com o mesmo número. A página está em
  `core/ReciboFinanceiro.php`, a mesma no painel e no portal.

## Renegociar (a Parte 2 do refeito; 1.99.0; D-105)

No desenho do sistema de origem (a tela "Renegociar Títulos"), sem os defeitos de lá:

- **A tela** abre pela seleção da ficha (Em lote › Renegociar, como o Receber de vários; com anos letivos misturados, a
  tela avisa e o acordo fica no ano letivo corrente). A **data da renegociação** é hoje ou antes; ao mudar, o servidor
  refaz os encargos de cada título (a rota dos encargos). Os quadros: o total original, os encargos, o acréscimo, o
  desconto (com algum deles, a justificativa) e o valor a renegociar.
- **A parcelada:** a entrada em R$ ou em % (o % é calculado em centavos, o meio para cima), o vencimento dela, o saldo,
  as parcelas (1 a 24) e o dia (1 a 28). A entrada é a 01; as parcelas vencem a partir do mês seguinte ao da entrada
  (sem entrada, ao da data da renegociação), no dia; o vencimento de cada uma pode mudar na tela (nunca antes do da
  anterior), o valor não; a última leva os centavos da divisão. Sem entrada, não há parcela de R$ 0,00.
- **A à vista:** o desconto em R$ ou em %, o vencimento e o valor a pagar, num título só (01.01).
- **O interesse** de cada condição só registra o que a família disse. O **motivo do acordo** é da lista (`fin_motivos`
  "renegociar"; o padrão é o primeiro) e a observação é opcional.
- **Salvar a proposta:** as duas condições, com os valores do dia; a proposta vale até a data da renegociação (salvar
  com uma data passada é recusado: nasceria vencida); a proposta nova dos mesmos títulos substitui a salva.
- **Efetivar:** na hora (uma das condições) ou depois (a proposta salva, pela aba Renegociações), sem refazer a conta. É
  recusado: a proposta vencida, o título que não está mais em aberto e o primeiro vencimento que já passou. Os originais
  ficam "renegociado"; nascem os títulos do acordo ("Acordo Parcelado de {o 1º título} - n. {número}" ou "Acordo à Vista
  de …"; as parcelas "01.04", "02.04"…), com juros e sem multa (a marca explícita `sem_multa`, não o nome do título).
  Seguem o Receber, o recibo, o caixa e os relatórios como qualquer título.
- **O servidor refaz a conta** ao gravar e recusa se ela não bate com a que a tela mostrou ("A conta mudou…").
- **Os impressos** (PDF, Dompdf): a proposta de acordo (as duas condições, o resumo, a validade, as assinaturas e duas
  testemunhas), o acordo efetivado (os títulos renegociados, a condição e os lançamentos) e o termo de confissão de dívida
  (as partes, o total por extenso, as cláusulas, as parcelas). A redação é um modelo padrão, neutro, escrito aqui;
  Configurações › Regras financeiras avisa que o jurídico da instituição deve revisá-la. Na demonstração, a tarja.
- **A aba Renegociações:** os acordos e as propostas, com o ID, a situação (Efetivado, Desfeito, Salvo, Salvo vencido,
  Substituído), a descrição, o total, a validade, a data e o usuário; as ações Efetivar acordo, Desfazer renegociação e
  os impressos. O cartão "Renegociar" antigo (a janela das parcelas) saiu.
- **O histórico** registra salvar a proposta (`proposta`), efetivar (`renegociar`, por título) e desfazer.
- **Os portais** (o do aluno e o do responsável financeiro) mostram as parcelas do acordo e os originais "Renegociado",
  com "renegociado no acordo nº …".
- **Os defeitos de lá que não vieram:** sem transação; o desfeito com o rótulo "Efetivado"; o número do acordo pelo maior
  mais um; o dinheiro como texto; a entrada 0 virando a parcela 01 de R$ 0,00; o dia sem limite (31); o % arredondado em
  reais; a justificativa não obrigatória; a parcela do acordo reconhecida pelo nome.

## As regras de cada ação

| Ação | Da situação | Para | O caixa | O motivo |
|---|---|---|---|---|
| Receber | aberto (um ou vários) | recebido | entra (o dia do recebimento, quem recebeu) | a justificativa, só com acréscimo ou desconto |
| Desfazer recebimento | recebido (o recibo inteiro) | aberto | sai (o dia do desfazer, quem desfez) | o motivo da lista e a justificativa |
| Baixar | aberto | baixado | não entra | obrigatório |
| Cancelar | aberto | cancelado | não entra | obrigatório |
| Renegociar (efetivar) | aberto (vários) | renegociado; as parcelas do acordo, aberto | — | o motivo do acordo (da lista); a justificativa, só com acréscimo ou desconto |
| Desfazer baixa | baixado | aberto | — | obrigatório |
| Desfazer cancelamento | cancelado | aberto | — | obrigatório |
| Desfazer renegociação | (o acordo efetivado) | os originais, aberto; as parcelas do acordo em aberto, cancelado | — | o motivo da lista e a observação; recusado se uma parcela do acordo foi recebida ou baixada |

- **A concorrência:** toda ação roda numa transação, com os títulos travados (`SELECT … FOR UPDATE`, sempre na mesma
  ordem) e a mudança de situação condicionada à situação esperada. Duas pessoas no mesmo título, ou o clique duplo, dão
  uma operação só; a segunda recebe "este título já está recebido".
- **O envio:** cada formulário leva um número de envio, único: o mesmo envio não grava duas vezes (o recibo é o mesmo).
- **A data do recebimento:** de hoje para trás, nunca depois de hoje. A do desfazer: entre a do recebimento e hoje.
- **As permissões:** "Operar o financeiro" (ação 105: a ficha, Receber, o recibo, Visualizar, a busca, Baixar, Cancelar,
  Renegociar, o atendimento, as listas de Configurações, os relatórios do financeiro) e "Desfazer no financeiro" (ação
  106: os desfazer), separadas. Por padrão, a 105 é dos perfis que cadastram alunos (o administrador, a secretaria, a
  supervisão); a 106, dos que reabrem uma disciplina (o administrador e a supervisão).

## O portal

A ficha financeira só de leitura no portal do aluno e no do responsável, com o recibo para baixar em cada título
recebido. O responsável vê só o financeiro do filho de que é o responsável **financeiro** naquele ano
(`VcAcessoFamilia::podeVerFinanceiro`); o recibo de um ano em que ele não é o financeiro não aparece e, pedido direto,
responde 403. O desenho novo do portal é uma parte seguinte.

## Os relatórios

Na categoria "Financeiro" do menu de relatórios: a **Ficha do caixa** (R-FIN-01: o recibo entra no dia dele, no caixa de
quem recebeu, uma linha por forma; o estorno sai no dia em que foi feito, no caixa de quem desfez, com o valor negativo —
o caixa de um dia passado não muda depois; o total por forma, por pessoa e o do dia) e os **Recebimentos por período**
(R-FIN-02: um recibo por linha, com os títulos, os encargos, o acréscimo, o desconto, as formas e quem recebeu; os
estornados marcados, com a data e o motivo, fora do total; o total por forma; exporta como os outros).

## Para quem já tem a plataforma instalada

A atualização para a 1.93.0 cria as tabelas do recibo e as listas (com a padrão) e transforma cada recebimento da 1.92.0
num recibo de um título, na ordem em que foi feito (os desfeitos, estornados); nada do que já existe se perde. Depois de
atualizar, confira em **Configurações** as **Bandeiras de cartão** e os **Motivos das operações**. A permissão de
desfazer pode ser dada a outro perfil em **Equipe → Perfis de acesso** ("Desfazer no financeiro").
