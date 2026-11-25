-- O FINANCEIRO REFEITO, Parte 1 (Onda 8; D-99; docs/FINANCEIRO.md): a ficha financeira no desenho da de origem, o
-- Receber de um ou de vários títulos, o recibo e o Desfazer recebimento. Nada das migrations 0072 a 0079 muda: as tabelas
-- delas ganham colunas e as novas se somam.
--   - o RECIBO: um por recebimento, com o número sequencial próprio (fin_sequencias, sem buraco), uma linha por título
--     (fin_recibo_titulos: o original, os encargos, o acerto rateado e o recebido) e uma linha por forma de pagamento
--     (fin_recibo_formas: dinheiro, Pix manual com o documento de quem pagou, cartão com tipo, parcelas, bandeira, final,
--     documento e autorização; a transferência não entra: não é mais usada). O recibo desfeito fica, "estornado", com a
--     data, o motivo e a justificativa; as linhas e as formas também ficam, marcadas;
--   - as BANDEIRAS de cartão e os MOTIVOS de cada operação: listas padrão, editáveis em Configurações;
--   - os recebimentos da 1.92.0 (um título, uma forma) viram recibos de um título, na ordem em que foram feitos; os
--     desfeitos viram recibos estornados.
-- Vale para os dois tipos de instituição.

CREATE TABLE IF NOT EXISTS `fin_sequencias` (
  `nome` varchar(20) NOT NULL,
  `ultimo` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Os números sequenciais (o do recibo): travados na transação, sem buraco';

CREATE TABLE IF NOT EXISTS `fin_bandeiras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(60) NOT NULL,
  `situacao` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1 = em uso; 2 = fora de uso (não aparece no Receber)',
  `ordem` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As bandeiras de cartão (o Receber registra o cartão à mão)';

CREATE TABLE IF NOT EXISTS `fin_motivos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operacao` varchar(24) NOT NULL COMMENT 'desfazer_recebimento | baixar | cancelar | prescrever | renegociar | desfazer_baixa | desfazer_cancelamento | desfazer_prescricao | desfazer_renegociacao',
  `nome` varchar(120) NOT NULL,
  `situacao` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1 = em uso; 2 = fora de uso',
  `ordem` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `operacao` (`operacao`, `situacao`, `ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Os motivos de cada operação do financeiro (a lista da tela)';

CREATE TABLE IF NOT EXISTS `fin_recibos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` int(11) NOT NULL COMMENT 'o número do recibo, sequencial (fin_sequencias)',
  `aluno_id` int(11) NOT NULL,
  `ano` int(11) DEFAULT NULL COMMENT 'o ano letivo dos títulos',
  `pagador_nome` varchar(160) DEFAULT NULL COMMENT 'de quem se recebeu: o responsável financeiro do ano (na escola) ou o aluno',
  `pagador_responsavel_id` int(11) DEFAULT NULL,
  `data_recebimento` date NOT NULL,
  `original_centavos` bigint(20) NOT NULL,
  `multa_centavos` bigint(20) NOT NULL DEFAULT 0,
  `juros_centavos` bigint(20) NOT NULL DEFAULT 0,
  `pontualidade_centavos` bigint(20) NOT NULL DEFAULT 0 COMMENT 'o desconto até o vencimento das regras',
  `acrescimo_centavos` bigint(20) NOT NULL DEFAULT 0,
  `desconto_centavos` bigint(20) NOT NULL DEFAULT 0,
  `total_centavos` bigint(20) NOT NULL COMMENT 'o valor recebido',
  `justificativa` varchar(500) DEFAULT NULL COMMENT 'obrigatória com acréscimo ou desconto',
  `observacao` varchar(500) DEFAULT NULL,
  `situacao` varchar(10) NOT NULL DEFAULT 'valido' COMMENT 'valido | estornado',
  `estorno_data` date DEFAULT NULL,
  `estorno_motivo_id` int(11) DEFAULT NULL,
  `estorno_motivo` varchar(120) DEFAULT NULL,
  `estorno_justificativa` varchar(500) DEFAULT NULL,
  `estornado_por` int(11) DEFAULT NULL,
  `estornado_em` datetime DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL COMMENT 'quem recebeu',
  `envio` varchar(40) DEFAULT NULL COMMENT 'o número do envio: o clique duplo não grava dois recibos',
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`),
  UNIQUE KEY `envio` (`envio`),
  KEY `aluno` (`aluno_id`, `id`),
  KEY `caixa` (`data_recebimento`, `usuario_id`),
  KEY `estorno` (`estorno_data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Os recibos: um por recebimento (nada é apagado; o desfeito fica estornado)';

CREATE TABLE IF NOT EXISTS `fin_recibo_titulos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recibo_id` int(11) NOT NULL,
  `titulo_id` int(11) NOT NULL,
  `original_centavos` bigint(20) NOT NULL,
  `multa_centavos` bigint(20) NOT NULL DEFAULT 0,
  `juros_centavos` bigint(20) NOT NULL DEFAULT 0,
  `pontualidade_centavos` bigint(20) NOT NULL DEFAULT 0,
  `acrescimo_centavos` bigint(20) NOT NULL DEFAULT 0 COMMENT 'a parte do acréscimo do recibo (rateada pelo devido)',
  `desconto_centavos` bigint(20) NOT NULL DEFAULT 0 COMMENT 'a parte do desconto do recibo (rateada pelo devido)',
  `recebido_centavos` bigint(20) NOT NULL,
  `situacao` varchar(10) NOT NULL DEFAULT 'valido' COMMENT 'valido | estornado',
  PRIMARY KEY (`id`),
  UNIQUE KEY `recibo_titulo` (`recibo_id`, `titulo_id`),
  KEY `titulo` (`titulo_id`, `situacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As linhas do recibo: uma por título';

CREATE TABLE IF NOT EXISTS `fin_recibo_formas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recibo_id` int(11) NOT NULL,
  `ordem` tinyint(4) NOT NULL DEFAULT 1,
  `forma` varchar(14) NOT NULL COMMENT 'dinheiro | pix | cartao; transferencia e outro: só os recebimentos da 1.92.0',
  `valor_centavos` bigint(20) NOT NULL,
  `pagador_documento` varchar(18) DEFAULT NULL COMMENT 'Pix manual: o CPF ou o CNPJ de quem pagou',
  `cartao_tipo` varchar(8) DEFAULT NULL COMMENT 'credito | debito',
  `cartao_parcelas` tinyint(4) DEFAULT NULL,
  `bandeira_id` int(11) DEFAULT NULL,
  `cartao_final` char(4) DEFAULT NULL,
  `cartao_documento` varchar(40) DEFAULT NULL,
  `cartao_autorizacao` varchar(40) DEFAULT NULL,
  `situacao` varchar(10) NOT NULL DEFAULT 'valido' COMMENT 'valido | estornado',
  PRIMARY KEY (`id`),
  KEY `recibo` (`recibo_id`, `ordem`),
  KEY `forma` (`forma`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As formas de pagamento de cada recibo, com os dados de cada uma';

-- o recebimento no histórico aponta o recibo; o título guarda de quantas parcelas é (a coluna "Parc.": 3.12)
ALTER TABLE `fin_operacoes` ADD COLUMN IF NOT EXISTS `recibo_id` int(11) DEFAULT NULL COMMENT 'o recibo do recebimento' AFTER `renegociacao_id`;
ALTER TABLE `fin_operacoes` ADD INDEX IF NOT EXISTS `recibo` (`recibo_id`);
ALTER TABLE `fin_titulos` ADD COLUMN IF NOT EXISTS `parcelas_total` int(11) DEFAULT NULL COMMENT 'de quantas parcelas (a coluna Parc.: parcela.total)' AFTER `parcela`;
UPDATE `fin_titulos` t JOIN `fin_planos` p ON p.`id` = t.`plano_id` SET t.`parcelas_total` = p.`parcelas` WHERE t.`parcelas_total` IS NULL AND t.`parcela` IS NOT NULL;
UPDATE `fin_titulos` t JOIN `fin_renegociacoes` r ON r.`id` = t.`renegociacao_id` SET t.`parcelas_total` = r.`parcelas` WHERE t.`parcelas_total` IS NULL AND t.`parcela` IS NOT NULL;

-- o endereço da pessoa responsável (o "Ver endereço" da ficha financeira; opcional)
ALTER TABLE `esc_responsaveis` ADD COLUMN IF NOT EXISTS `endereco` varchar(160) DEFAULT NULL AFTER `cpf`;
ALTER TABLE `esc_responsaveis` ADD COLUMN IF NOT EXISTS `numero` varchar(20) DEFAULT NULL AFTER `endereco`;
ALTER TABLE `esc_responsaveis` ADD COLUMN IF NOT EXISTS `complemento` varchar(80) DEFAULT NULL AFTER `numero`;
ALTER TABLE `esc_responsaveis` ADD COLUMN IF NOT EXISTS `bairro` varchar(80) DEFAULT NULL AFTER `complemento`;
ALTER TABLE `esc_responsaveis` ADD COLUMN IF NOT EXISTS `cidade` varchar(80) DEFAULT NULL AFTER `bairro`;
ALTER TABLE `esc_responsaveis` ADD COLUMN IF NOT EXISTS `uf` char(2) DEFAULT NULL AFTER `cidade`;
ALTER TABLE `esc_responsaveis` ADD COLUMN IF NOT EXISTS `cep` varchar(9) DEFAULT NULL AFTER `uf`;

-- as listas padrão
INSERT INTO `fin_sequencias` (`nome`, `ultimo`) SELECT 'recibo', 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fin_sequencias` WHERE `nome` = 'recibo');
INSERT INTO `fin_bandeiras` (`id`, `nome`, `situacao`, `ordem`)
SELECT x.id, x.nome, 1, x.id FROM (
  SELECT 1 id, 'Visa' nome UNION ALL SELECT 2, 'Mastercard' UNION ALL SELECT 3, 'Elo' UNION ALL SELECT 4, 'American Express'
  UNION ALL SELECT 5, 'Hipercard' UNION ALL SELECT 6, 'Outra'
) x WHERE NOT EXISTS (SELECT 1 FROM `fin_bandeiras` b WHERE b.`id` = x.id) ORDER BY x.id;
INSERT INTO `fin_motivos` (`id`, `operacao`, `nome`, `situacao`, `ordem`)
SELECT x.id, x.op, x.nome, 1, x.ordem FROM (
  SELECT 1 id, 'desfazer_recebimento' op, 'Recebimento lançado em duplicidade' nome, 1 ordem
  UNION ALL SELECT 2, 'desfazer_recebimento', 'Valor, data ou forma registrados errado', 2
  UNION ALL SELECT 3, 'desfazer_recebimento', 'Título errado', 3
  UNION ALL SELECT 4, 'desfazer_recebimento', 'Pagamento não compensado (cartão ou Pix)', 4
  UNION ALL SELECT 5, 'desfazer_recebimento', 'Devolução do valor ao responsável', 5
  UNION ALL SELECT 6, 'desfazer_recebimento', 'Outro', 99
  UNION ALL SELECT 11, 'baixar', 'Bolsa integral concedida', 1
  UNION ALL SELECT 12, 'baixar', 'Acordo com a direção', 2
  UNION ALL SELECT 13, 'baixar', 'Débito de valor pequeno', 3
  UNION ALL SELECT 14, 'baixar', 'Outro', 99
  UNION ALL SELECT 21, 'cancelar', 'Lançado por engano', 1
  UNION ALL SELECT 22, 'cancelar', 'Desistência antes do início das aulas', 2
  UNION ALL SELECT 23, 'cancelar', 'Desconto concedido', 3
  UNION ALL SELECT 24, 'cancelar', 'Outro', 99
  UNION ALL SELECT 31, 'prescrever', 'Prazo de cobrança encerrado', 1
  UNION ALL SELECT 32, 'prescrever', 'Outro', 99
  UNION ALL SELECT 41, 'renegociar', 'Dificuldade financeira da família', 1
  UNION ALL SELECT 42, 'renegociar', 'Acordo para a rematrícula', 2
  UNION ALL SELECT 43, 'renegociar', 'Outro', 99
  UNION ALL SELECT 51, 'desfazer_baixa', 'Feito por engano', 1
  UNION ALL SELECT 52, 'desfazer_baixa', 'Outro', 99
  UNION ALL SELECT 61, 'desfazer_cancelamento', 'Feito por engano', 1
  UNION ALL SELECT 62, 'desfazer_cancelamento', 'Outro', 99
  UNION ALL SELECT 71, 'desfazer_prescricao', 'Feito por engano', 1
  UNION ALL SELECT 72, 'desfazer_prescricao', 'Outro', 99
  UNION ALL SELECT 81, 'desfazer_renegociacao', 'Acordo não cumprido', 1
  UNION ALL SELECT 82, 'desfazer_renegociacao', 'Feito por engano', 2
  UNION ALL SELECT 83, 'desfazer_renegociacao', 'Outro', 99
) x WHERE NOT EXISTS (SELECT 1 FROM `fin_motivos` m WHERE m.`id` = x.id) ORDER BY x.id;

-- os recebimentos da 1.92.0 viram recibos de um título (o número é a ordem do recebimento); os desfeitos, estornados
INSERT INTO `fin_recibos` (`numero`, `aluno_id`, `ano`, `data_recebimento`, `original_centavos`, `multa_centavos`, `juros_centavos`, `pontualidade_centavos`,
  `total_centavos`, `observacao`, `situacao`, `estorno_data`, `estorno_motivo`, `estorno_justificativa`, `estornado_por`, `estornado_em`, `usuario_id`, `envio`, `criado_em`)
SELECT -o.`id`, o.`aluno_id`, t.`ano`, o.`data_operacao`, COALESCE(o.`original_centavos`, 0), COALESCE(o.`multa_centavos`, 0), COALESCE(o.`juros_centavos`, 0), COALESCE(o.`desconto_centavos`, 0),
  COALESCE(o.`recebido_centavos`, 0), o.`observacao`, IF(o.`desfeita_em` IS NULL, 'valido', 'estornado'), DATE(o.`desfeita_em`), IF(o.`desfeita_em` IS NULL, NULL, 'Outro'),
  d.`motivo`, d.`usuario_id`, o.`desfeita_em`, o.`usuario_id`, CONCAT('op-', o.`id`), o.`criado_em`
  FROM `fin_operacoes` o JOIN `fin_titulos` t ON t.`id` = o.`titulo_id` LEFT JOIN `fin_operacoes` d ON d.`id` = o.`desfeita_por_operacao`
 WHERE o.`tipo` = 'receber' AND o.`recibo_id` IS NULL AND NOT EXISTS (SELECT 1 FROM `fin_recibos` r WHERE r.`envio` = CONCAT('op-', o.`id`))
 ORDER BY o.`id`;
SET @vc_recibo := (SELECT COALESCE(MAX(`numero`), 0) FROM `fin_recibos` WHERE `numero` > 0);
UPDATE `fin_recibos` SET `numero` = (@vc_recibo := @vc_recibo + 1) WHERE `numero` < 0 ORDER BY `id`; -- o número provisório (negativo) vira 1, 2, 3…, na ordem
UPDATE `fin_operacoes` o JOIN `fin_recibos` r ON r.`envio` = CONCAT('op-', o.`id`) SET o.`recibo_id` = r.`id` WHERE o.`tipo` = 'receber' AND o.`recibo_id` IS NULL;
INSERT INTO `fin_recibo_titulos` (`recibo_id`, `titulo_id`, `original_centavos`, `multa_centavos`, `juros_centavos`, `pontualidade_centavos`, `recebido_centavos`, `situacao`)
SELECT r.`id`, o.`titulo_id`, r.`original_centavos`, r.`multa_centavos`, r.`juros_centavos`, r.`pontualidade_centavos`, r.`total_centavos`, r.`situacao`
  FROM `fin_operacoes` o JOIN `fin_recibos` r ON r.`id` = o.`recibo_id`
 WHERE o.`tipo` = 'receber' AND NOT EXISTS (SELECT 1 FROM `fin_recibo_titulos` x WHERE x.`recibo_id` = r.`id`)
 ORDER BY r.`id`;
INSERT INTO `fin_recibo_formas` (`recibo_id`, `ordem`, `forma`, `valor_centavos`, `situacao`)
SELECT r.`id`, 1, COALESCE(o.`forma`, 'outro'), r.`total_centavos`, r.`situacao`
  FROM `fin_operacoes` o JOIN `fin_recibos` r ON r.`id` = o.`recibo_id`
 WHERE o.`tipo` = 'receber' AND NOT EXISTS (SELECT 1 FROM `fin_recibo_formas` x WHERE x.`recibo_id` = r.`id`)
 ORDER BY r.`id`;
UPDATE `fin_sequencias` SET `ultimo` = GREATEST(`ultimo`, (SELECT COALESCE(MAX(`numero`), 0) FROM `fin_recibos`)) WHERE `nome` = 'recibo';

-- as páginas: Receber pagamento (a tela; o POST continua em financeiro/receber, agora de um ou vários títulos), o recibo,
-- Desfazer recebimento (o recibo inteiro; a ação de desfazer, 106), Visualizar o título, a busca de outro aluno, e em
-- Configurações os motivos e as bandeiras
UPDATE `adms_paginas` SET `nome_pagina` = 'Receber um ou vários títulos', `obs` = 'POST, da tela Receber pagamento: os títulos, a data, o acréscimo, o desconto, a justificativa e as formas; grava o recibo.' WHERE `id` = 569;
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, x.icone, 1, 1, 1, NOW(), x.grava, x.sens, NULL, x.acao FROM (
  SELECT 578 id, 'Receber pagamento' menu, 'Financeiro' ctl, 'receberPagamento' met, 'financeiro' mctl, 'receber-pagamento' mmet, 'Receber pagamento' nome, 'A tela de Receber: um título (pelo menu) ou vários (pelo modo lote).' obs, NULL icone, 0 grava, 0 sens, 105 acao
  UNION ALL SELECT 579, 'Recibo', 'Financeiro', 'recibo', 'financeiro', 'recibo', 'O recibo, para imprimir', 'Duas vias; o estornado sai com a faixa.', NULL, 0, 0, 105
  UNION ALL SELECT 580, 'Desfazer recebimento', 'Financeiro', 'desfazerRecebimento', 'financeiro', 'desfazer-recebimento', 'Desfazer recebimento (o recibo inteiro)', 'GET a tela; POST com a data, o motivo e a justificativa.', NULL, 1, 1, 106
  UNION ALL SELECT 581, 'Visualizar o título', 'Financeiro', 'titulo', 'financeiro', 'titulo', 'Visualizar o título', 'Os dados do título, os recibos e o histórico dele.', NULL, 0, 0, 105
  UNION ALL SELECT 582, 'Buscar aluno no financeiro', 'Financeiro', 'buscar', 'financeiro', 'buscar', 'Buscar outro aluno na ficha financeira', 'GET, em JSON: nome, RA ou responsável.', NULL, 0, 0, 105
  UNION ALL SELECT 583, 'Motivos das operações', 'FinMotivos', 'listar', 'fin-motivos', 'listar', 'Motivos das operações do financeiro', 'A lista de cada operação: incluir, renomear, tirar de uso.', 'fas fa-list-check', 0, 0, 105
  UNION ALL SELECT 584, 'Salvar o motivo', 'FinMotivos', 'salvar', 'fin-motivos', 'salvar', 'Incluir ou editar um motivo', 'POST.', NULL, 1, 0, 105
  UNION ALL SELECT 585, 'Bandeiras de cartão', 'FinBandeiras', 'listar', 'fin-bandeiras', 'listar', 'Bandeiras de cartão', 'As bandeiras que o Receber oferece no cartão.', 'far fa-credit-card', 0, 0, 105
  UNION ALL SELECT 586, 'Salvar a bandeira', 'FinBandeiras', 'salvar', 'fin-bandeiras', 'salvar', 'Incluir ou editar uma bandeira', 'POST.', NULL, 1, 0, 105
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id) ORDER BY x.id;

-- o recibo no portal, para baixar: o do aluno (nível 1) e o do filho (o responsável, nível 2; a barreira é a da ficha)
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.menu, x.obs, 2, NULL, 1, 1, 1, NOW() FROM (
  SELECT 43 id, 'Recibo' menu, 'Home' ctl, 'reciboAluno' met, 'home' mctl, 'recibo-aluno' mmet, 'O recibo de um título do aluno, para baixar.' obs
  UNION ALL SELECT 44, 'Recibo do filho', 'Familia', 'recibo', 'familia', 'recibo', 'O recibo de um título do filho, para baixar (só o responsável financeiro).'
) x WHERE NOT EXISTS (SELECT 1 FROM `col_paginas` p WHERE p.`id` = x.id) ORDER BY x.id;
INSERT INTO `col_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 1, x.pg, 2, 2, 1, x.nivel, x.pg, NOW() FROM (SELECT 43 pg, 1 nivel UNION ALL SELECT 44, 2) x
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `col_nivacs_pgs`) y WHERE y.`adms_niveis_acesso_id` = x.nivel AND y.`adms_pagina_id` = x.pg)
 ORDER BY x.pg, x.nivel; -- a ordem dos ids, fixa

-- as permissões: as da ficha (565) para as de operar, as de desfazer (572) para o Desfazer recebimento; no menu
-- Configurações (o das regras, 560), os motivos e as bandeiras
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, p.`id`, d.`dropdown`, IF(p.`id` IN (583, 585), d.`lib_menu`, 2), d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p
    ON (d.`adms_pagina_id` = 565 AND p.`id` IN (578, 579, 581, 582))
    OR (d.`adms_pagina_id` = 572 AND p.`id` = 580)
    OR (d.`adms_pagina_id` = 560 AND p.`id` IN (583, 584, 585, 586))
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`; -- a ordem dos ids, fixa

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.93.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 110, '1.93.0', 1, 93, 0, '2026-11-22', 'O financeiro refeito: a ficha financeira no desenho do sistema de origem, o Receber de vários títulos, o recibo e o Desfazer recebimento.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.93.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.93.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 213, 110, 'A ficha financeira nova', 'A ficha financeira tem o desenho de sempre: o aluno e os responsáveis do ano, os totais (em atraso, a vencer, pago no ano), o resumo, as abas Atual, Pendências e Anos anteriores, os filtros por período e por situação, o cartão Em lote e a tabela com o valor pago, a data e quem recebeu. Receber abre uma tela própria, para um ou vários títulos, com acréscimo, desconto e mais de uma forma de pagamento, e emite o recibo; Desfazer recebimento desfaz o recibo inteiro, com o motivo.', 1, 'administrador,secretaria', 'o-financeiro', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 213);
