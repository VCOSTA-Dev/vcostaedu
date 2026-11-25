-- O FINANCEIRO REFEITO, Parte 2 (D-105; docs/FINANCEIRO.md): Renegociar, no desenho do sistema de origem.
--   - a tela "Renegociar títulos", aberta pela seleção da ficha: a data da renegociação (o servidor refaz os encargos), os
--     quadros (o total original, os encargos, o acréscimo, o desconto, o valor a renegociar) e as duas condições — a
--     parcelada (a entrada em R$ ou em %, o saldo, as parcelas, o dia) e a à vista (o desconto em R$ ou em %);
--   - a PROPOSTA (fin_propostas): as duas condições guardadas com os valores do dia; vale até a data da renegociação;
--     salva, pode ser efetivada depois (sem refazer a conta) enquanto os títulos estão em aberto;
--   - o ACORDO (fin_renegociacoes, com o número próprio, sequencial): os originais ficam "renegociado", ligados a ele; as
--     parcelas do acordo pagam juros e NÃO pagam multa (a marca sem_multa do título);
--   - Desfazer renegociação com o motivo da lista e a observação.
-- Centavos inteiros; tudo numa transação; nada apagado; o número do envio contra o clique duplo.

CREATE TABLE IF NOT EXISTS `fin_propostas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL,
  `ano` int(11) DEFAULT NULL COMMENT 'o ano letivo das parcelas do acordo (o dos títulos; anos misturados: o corrente)',
  `data_renegociacao` date NOT NULL COMMENT 'a data da conta; a proposta vale até ela',
  `original_centavos` bigint(20) NOT NULL,
  `juros_centavos` bigint(20) NOT NULL DEFAULT 0,
  `multa_centavos` bigint(20) NOT NULL DEFAULT 0,
  `acrescimo_centavos` bigint(20) NOT NULL DEFAULT 0,
  `desconto_centavos` bigint(20) NOT NULL DEFAULT 0,
  `justificativa` varchar(500) DEFAULT NULL COMMENT 'obrigatória com acréscimo ou desconto',
  `total_centavos` bigint(20) NOT NULL COMMENT 'o valor a renegociar: os títulos com os encargos, mais o acréscimo, menos o desconto',
  `p_interesse` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'a família mostrou interesse na parcelada (só registra)',
  `p_entrada_centavos` bigint(20) NOT NULL DEFAULT 0,
  `p_entrada_bp` int(11) DEFAULT NULL COMMENT 'a entrada digitada em % (centésimos de ponto); NULL = em R$',
  `p_entrada_vencimento` date DEFAULT NULL,
  `p_parcelas` int(11) NOT NULL DEFAULT 1 COMMENT 'as parcelas depois da entrada',
  `p_dia` int(11) NOT NULL DEFAULT 10 COMMENT 'o dia do vencimento das parcelas (1 a 28)',
  `p_total_centavos` bigint(20) NOT NULL DEFAULT 0,
  `v_interesse` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'a família mostrou interesse na à vista (só registra)',
  `v_desconto_centavos` bigint(20) NOT NULL DEFAULT 0,
  `v_desconto_bp` int(11) DEFAULT NULL COMMENT 'o desconto à vista digitado em %; NULL = em R$',
  `v_vencimento` date DEFAULT NULL,
  `v_total_centavos` bigint(20) NOT NULL DEFAULT 0,
  `motivo_id` int(11) DEFAULT NULL COMMENT 'fin_motivos (renegociar)',
  `motivo` varchar(120) DEFAULT NULL,
  `observacao` varchar(500) DEFAULT NULL,
  `situacao` varchar(12) NOT NULL DEFAULT 'salva' COMMENT 'salva | formalizada | substituida (a vencida é a salva com a data passada)',
  `renegociacao_id` int(11) DEFAULT NULL COMMENT 'o acordo que nasceu dela',
  `substituida_por` int(11) DEFAULT NULL,
  `envio` varchar(40) DEFAULT NULL,
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `envio` (`envio`),
  KEY `aluno` (`aluno_id`, `situacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As propostas de acordo (as duas condições, com os valores do dia)';

CREATE TABLE IF NOT EXISTS `fin_proposta_titulos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposta_id` int(11) NOT NULL,
  `titulo_id` int(11) NOT NULL,
  `original_centavos` bigint(20) NOT NULL,
  `juros_centavos` bigint(20) NOT NULL DEFAULT 0,
  `multa_centavos` bigint(20) NOT NULL DEFAULT 0,
  `total_centavos` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `proposta` (`proposta_id`),
  KEY `titulo` (`titulo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Os títulos da proposta, com a conta do dia';

CREATE TABLE IF NOT EXISTS `fin_proposta_parcelas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposta_id` int(11) NOT NULL,
  `condicao` varchar(10) NOT NULL COMMENT 'parcelada | avista',
  `numero` int(11) NOT NULL COMMENT '1 = a entrada (quando há)',
  `total` int(11) NOT NULL,
  `vencimento` date NOT NULL,
  `valor_centavos` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `proposta` (`proposta_id`, `condicao`, `numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As parcelas de cada condição da proposta (o vencimento editável; o valor, do servidor)';

ALTER TABLE `fin_renegociacoes` ADD COLUMN IF NOT EXISTS `numero` int(11) DEFAULT NULL COMMENT 'o número do acordo, sequencial (fin_sequencias)' AFTER `id`;
ALTER TABLE `fin_renegociacoes` ADD COLUMN IF NOT EXISTS `proposta_id` int(11) DEFAULT NULL AFTER `numero`;
ALTER TABLE `fin_renegociacoes` ADD COLUMN IF NOT EXISTS `condicao` varchar(10) DEFAULT NULL COMMENT 'parcelada | avista (NULL: a renegociação da 1.92.0)' AFTER `proposta_id`;
ALTER TABLE `fin_renegociacoes` ADD COLUMN IF NOT EXISTS `entrada_centavos` bigint(20) NOT NULL DEFAULT 0 AFTER `desconto_centavos`;
ALTER TABLE `fin_renegociacoes` ADD COLUMN IF NOT EXISTS `motivo_desfazer_id` int(11) DEFAULT NULL AFTER `motivo_desfazer`;
ALTER TABLE `fin_renegociacoes` ADD COLUMN IF NOT EXISTS `desfeita_por` int(11) DEFAULT NULL AFTER `motivo_desfazer_id`;
ALTER TABLE `fin_titulos` ADD COLUMN IF NOT EXISTS `sem_multa` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'a parcela do acordo: paga juros, não paga multa' AFTER `renegociado_por`;

-- as renegociações de antes ganham o número (o id, na ordem), e a sequência continua dali
UPDATE `fin_renegociacoes` SET `numero` = `id` WHERE `numero` IS NULL;
INSERT INTO `fin_sequencias` (`nome`, `ultimo`) SELECT 'acordo', 0 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fin_sequencias` WHERE `nome` = 'acordo');
UPDATE `fin_sequencias` SET `ultimo` = GREATEST(`ultimo`, (SELECT COALESCE(MAX(`numero`), 0) FROM `fin_renegociacoes`)) WHERE `nome` = 'acordo';

-- as páginas: a tela Renegociar títulos (o POST continua em financeiro/renegociar: salvar a proposta e efetivar uma das
-- condições), Efetivar acordo (a proposta salva) e os impressos (a proposta, o acordo e o termo de confissão de dívida)
UPDATE `adms_paginas` SET `nome_pagina` = 'Salvar a proposta ou efetivar o acordo', `obs` = 'POST, da tela Renegociar títulos: a data, os quadros, as duas condições, o motivo e a observação.' WHERE `id` = 575;
UPDATE `adms_paginas` SET `obs` = 'POST. Com o motivo da lista e a observação; só sem recebimento nem baixa nas parcelas do acordo.' WHERE `id` = 576;
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, NULL, 1, 1, 1, NOW(), x.grava, 0, NULL, 105 FROM (
  SELECT 594 id, 'Renegociar títulos' menu, 'Financeiro' ctl, 'renegociarTitulos' met, 'financeiro' mctl, 'renegociar-titulos' mmet, 'Renegociar títulos' nome, 'A tela: os títulos marcados na ficha, a data, os quadros e as duas condições.' obs, 0 grava
  UNION ALL SELECT 595, 'Efetivar acordo', 'Financeiro', 'efetivarProposta', 'financeiro', 'efetivar-proposta', 'Efetivar a proposta salva', 'POST. A condição; recusa a proposta vencida e o título que não está em aberto.', 1
  UNION ALL SELECT 596, 'Impressos da renegociação', 'Financeiro', 'impressoRenegociacao', 'financeiro', 'impresso-renegociacao', 'A proposta, o acordo e o termo de confissão de dívida', 'PDF.', 0
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id) ORDER BY x.id;
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, p.`id`, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON d.`adms_pagina_id` = 575 AND p.`id` IN (594, 595, 596)
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`; -- a ordem dos ids, fixa

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.99.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 116, '1.99.0', 1, 99, 0, '2026-11-23', 'O financeiro refeito: Renegociar, com a proposta parcelada e à vista, o acordo e os impressos.', 1, '2026-11-23 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.99.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.99.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 223, 116, 'Renegociar títulos', 'Na ficha financeira, Em lote › Renegociar abre a tela própria: a data da renegociação (os juros e a multa saem dela), o acréscimo e o desconto com a justificativa, e as duas condições — parcelada, com entrada, e à vista. A proposta pode ser salva e efetivada depois, no mesmo dia; o acordo efetivado tem número próprio, as parcelas dele pagam juros e não pagam multa, e a proposta, o acordo e o termo de confissão de dívida saem em PDF. A aba Renegociações mostra tudo, com o efetivar, o desfazer e os impressos.', 1, 'administrador,secretaria', 'o-financeiro', '2026-11-23 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 223);
