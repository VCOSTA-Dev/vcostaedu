---
titulo: O financeiro: a ficha financeira, receber, o recibo e os desfazer
resumo: A ficha financeira do aluno (os responsáveis do ano, os totais, as abas, os filtros e o Em lote); Receber um ou vários títulos, com o recibo; Baixar, Cancelar, Renegociar e os desfazer; o caixa do dia e os recebimentos por período.
perfis: administrador, secretaria
ordem: 40
relacionados: vincular-e-enturmar, o-que-o-responsavel-ve-no-portal
telas: fin-regras/index, fin-planos/listar, fin-motivos/listar, fin-bandeiras/listar, financeiro/ficha, financeiro/receber-pagamento, financeiro/desfazer-recebimento, financeiro/titulo, financeiro/renegociar-titulos
entrou: 1.85.0
mudou: 1.99.0
---
## Para que serve

Controlar o que cada aluno deve e o que pagou, com o recibo de cada pagamento e o histórico de tudo. O financeiro não emite boleto nem Pix e não fala com banco: o pagamento é recebido por fora (no caixa, no Pix da escola, na maquininha do cartão) e registrado aqui.

As palavras de cada ação são as mesmas no menu, na confirmação e no histórico:

- **Receber**: o dinheiro entrou. Um ou vários títulos num recibo só; os títulos ficam recebidos e entram no caixa de quem recebeu.
- **Baixar**: quita o título sem entrada de dinheiro (uma bolsa, uma isenção, um acerto). Não entra no caixa.
- **Cancelar**: o título deixa de ser devido.
- **Renegociar**: junta títulos em aberto num acordo — parcelado, com entrada, ou à vista —, com número próprio; as parcelas do acordo pagam juros e não pagam multa.
- **Desfazer recebimento** (o recibo inteiro), **Desfazer baixa**, **Desfazer cancelamento**, **Desfazer renegociação**: voltam atrás, com o motivo.

## Antes de começar

- **Configurações → Regras financeiras**: a multa por atraso (%, cobrada uma vez), os juros ao mês (%, calculados por dia de atraso) e, se quiser, o desconto até o vencimento (%).
- **Configurações → Planos de pagamento**: o nome, o número de parcelas, o valor da parcela, o dia de vencimento e o mês da primeira parcela.
- **Configurações → Bandeiras de cartão** e **Motivos das operações**: as listas que o Receber e os desfazer oferecem. Vêm com uma lista padrão; inclua o que faltar e tire de uso o que não servir.
- Quem desfaz é o administrador ou a supervisão da secretaria (a permissão "Desfazer no financeiro", separada da de operar).

## Passo a passo

1. **As parcelas**: ao vincular o aluno a um curso (**Alunos → o aluno → Matrícula**), escolha o **Plano de pagamento**: as parcelas nascem, uma por mês. Para quem já está matriculado, na ficha financeira, **Novo título → Aplicar um plano de pagamento**. A parcela que já existe não nasce de novo.
2. **A ficha**: na tela do aluno, **Ficha financeira** (ou a busca do alto da própria ficha: o nome, o número de matrícula ou o responsável). No alto, o aluno, o ano letivo (uma pílula por ano: a troca muda tudo para o ano escolhido) e os responsáveis do ano, com o celular, o WhatsApp e o endereço; à direita, o que está em atraso, o que está a vencer e o que foi pago no ano.
3. **A lista**: **Atual** (o ano escolhido, os seguintes e o que ficou em aberto de antes, em laranja), **Pendências** (tudo em aberto) e **Anos anteriores**. O período (por vencimento ou por recebimento) e a situação filtram a lista; cada situação mostra quantos títulos tem. O título vencido em aberto aparece como "Atrasado".
4. **Receber um título**: no menu **Ações** da linha, **Receber**. Na tela de Receber, escolha a data do recebimento (hoje, ou um dia antes, se a pessoa pagou antes): os juros e a multa de cada título são refeitos na hora. Se houver acréscimo ou desconto, escreva a justificativa. Escolha a forma: dinheiro; Pix (o CPF ou o CNPJ de quem pagou); cartão (crédito ou débito, as parcelas, a bandeira, o final do cartão, o número do documento e o da autorização, do comprovante da maquininha). Para pagar em mais de uma forma, **Múltiplos**: a tela diz quanto falta até fechar.
5. **Receber vários títulos**: no cartão **Em lote**, **Receber**; marque os títulos (o contador soma) e **Continuar**. O acréscimo e o desconto se dividem entre eles, fechando ao centavo. O recibo sai em nome do responsável financeiro do ano dos títulos; com títulos de anos letivos diferentes, no do ano letivo corrente (a tela avisa).
6. **O recibo**: **Receber e emitir recibo** abre o recibo para imprimir, em duas vias; **Receber e finalizar** volta à ficha. Para imprimir de novo, no menu **Ações** do título, **Recibo**.
7. **Desfazer recebimento**: no menu **Ações** do título recebido. Desfaz o recibo inteiro (todos os títulos dele): a data, o motivo e a justificativa. Os títulos voltam a em aberto; o recibo continua existindo, com a faixa "ESTORNADO", e o estorno sai do caixa do dia em que foi feito.
8. **Baixar** ou **Cancelar**: no menu **Ações** (ou no **Em lote**), com o motivo. Os desfazer, no mesmo menu.
9. **Renegociar**: no **Em lote**, **Renegociar**; marque os títulos e **Continuar**. Na tela **Renegociar títulos**:
   - a **Data da renegociação** (hoje, ou um dia antes): os juros e a multa de cada título são refeitos na hora; o **Acréscimo** e o **Desconto**, com a justificativa, dão o **Valor a renegociar**;
   - a **Proposta parcelada**: a **Entrada** (em R$ ou em %) e o vencimento dela, a quantidade de parcelas e o dia do vencimento (de 1 a 28); a **Proposta à vista**: o desconto (em R$ ou em %) e o vencimento. O **Interesse** de cada uma registra o que a família disse;
   - **Gerar proposta** monta as parcelas de cada condição: a entrada é a 01; as parcelas vencem a partir do mês seguinte, no dia escolhido (sem entrada, a partir do mês seguinte ao da data da renegociação); o vencimento de cada parcela pode mudar, o valor não; a última leva os centavos da divisão;
   - escolha o **Motivo do acordo** (e, se quiser, a observação) e: **Salvar proposta** (ela vale até a data da renegociação; para efetivar depois, a aba **Renegociações** do alto, **Efetivar acordo**, com a condição que a família escolheu — os valores são os da proposta), **Efetivar proposta à vista** ou **Efetivar proposta parcelada**;
   - efetivado, os títulos escolhidos ficam **Renegociado** e nascem as parcelas do acordo ("Acordo Parcelado de … - n. 12", parcelas 01.04, 02.04…), que pagam juros em atraso e não pagam multa; recebem-se como qualquer título.
   - Na aba **Renegociações**, cada acordo e cada proposta (Efetivado, Desfeito, Salvo, Salvo vencido, Substituído), com **Efetivar acordo**, **Desfazer renegociação** (o motivo da lista e a observação) e os impressos em PDF: a **Proposta de acordo**, o **Acordo efetivado** e o **Termo de confissão de dívida**. Os impressos são modelos de redação padrão: peça ao jurídico da instituição para revisá-los (o aviso está em **Configurações → Regras financeiras**).
10. **Um título avulso**: **Novo título**, no alto da ficha (a descrição, o valor, o vencimento e o ano letivo).
11. **O atendimento**: na aba **Histórico** do alto, registre o que foi conversado e combinado. Cada ação também entra sozinha no histórico, com quem fez e quando.
12. **O caixa e os recebimentos**: em **Relatórios → Financeiro**, a **Ficha do caixa** (o que entrou e saiu num dia, por pessoa e por forma) e os **Recebimentos por período** (um recibo por linha, com os estornados marcados).

## O que pode dar errado

- **"Este título já está recebido: nada foi gravado."** — outra pessoa (ou um clique repetido) já recebeu. A ficha mostra a situação de agora.
- **"As formas somam … falta …"** — no pagamento em mais de uma forma, a soma tem de fechar com o valor a receber, ao centavo.
- **"No Pix, informe o CPF ou o CNPJ de quem pagou."** — o documento de quem fez o Pix, com os números certos.
- **"A data do recebimento não pode ser depois de hoje."** — registre o recebimento no dia em que ele acontecer.
- **"Não dá para desfazer: … está recebido."** — num acordo com parcela paga, desfaça antes o recebimento dessa parcela.
- **"A conta mudou desde que a tela foi montada…"** — a tela ficou aberta enquanto o dia (ou um título) mudou: confira e gere a proposta de novo.
- **"A proposta nº … venceu…"** — a proposta vale até a data da renegociação: faça uma proposta nova, com a conta de hoje.
- **"A proposta vale até a data da renegociação: com uma data passada…"** — para salvar a proposta, use a data de hoje (com uma data anterior, só dá para efetivar na hora).
- **O item do desfazer não aparece no menu** — o seu perfil não tem a permissão "Desfazer no financeiro".
