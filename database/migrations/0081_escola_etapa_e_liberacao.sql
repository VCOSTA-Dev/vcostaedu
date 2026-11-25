-- A CORREÇÃO DA ESCOLA, Parte 1 (D-100; docs/ESCOLA.md): a etapa, a liberação do boletim e o conselho de classe.
--   - FECHAR A ETAPA ganha a ação própria (o professor fecha nas disciplinas dele; a secretaria, a supervisão e o
--     administrador continuam fechando); REABRIR A ETAPA também (a supervisão de secretaria, a direção acadêmica e o
--     administrador, com o motivo). A direção acadêmica é "só consulta": a página que grava e que ela também abre leva a
--     marca nova consulta_grava (a reabertura, a liberação do boletim e a correção do conselho);
--   - a RECUPERAÇÃO passa a ser lançada também pela secretaria e pela supervisão (as que lançam as faltas da etapa), e
--     a recuperação e as faltas da etapa gravam por um botão só ("Gravar");
--   - a LIBERAÇÃO DO BOLETIM, por ano letivo: uma linha por etapa, a média final e a situação final; cada uma com
--     "Exibir no portal" e "Liberar a partir de" (data e hora), quem alterou e quando; cada alteração fica no histórico.
--     As liberações de antes (esc_boletim_liberado, que fica no banco sem uso) passam para a tabela nova; o ano que tinha
--     todas as etapas liberadas mantém a média e a situação finais à vista (era a regra de antes);
--   - o relatório de reaberturas mostra também a reabertura da etapa, e quem reabre o vê;
--   - o CONSELHO DE CLASSE: a observação pode ser corrigida ou retirada pelo autor ou pela direção; nada é apagado (a
--     retirada fica marcada) e cada mudança fica no histórico.
-- Só a escola usa estas páginas (adms_paginas_modulo); o ensino superior não muda.

ALTER TABLE `adms_paginas` ADD COLUMN IF NOT EXISTS `consulta_grava` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1 = página que grava e que o perfil só consulta também abre (D-100)' AFTER `grava`;

CREATE TABLE IF NOT EXISTS `esc_boletim_liberacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ano` int(11) NOT NULL,
  `item` varchar(16) NOT NULL COMMENT 'etapa | media_final | situacao_final',
  `etapa_id` int(11) NOT NULL DEFAULT 0 COMMENT 'a etapa (item etapa); 0 nos itens finais',
  `exibir` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1 = exibir no portal',
  `a_partir` datetime DEFAULT NULL COMMENT 'com exibir = 1: aparece só a partir desta data e hora (vazio = já)',
  `alterado_por` int(11) DEFAULT NULL,
  `alterado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item` (`ano`, `item`, `etapa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='A liberação do boletim no portal, por ano letivo: cada etapa, a média final e a situação final';

CREATE TABLE IF NOT EXISTS `esc_boletim_liberacao_hist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ano` int(11) NOT NULL,
  `item` varchar(16) NOT NULL,
  `etapa_id` int(11) NOT NULL DEFAULT 0,
  `exibir_antes` tinyint(4) DEFAULT NULL,
  `a_partir_antes` datetime DEFAULT NULL,
  `exibir` tinyint(4) NOT NULL,
  `a_partir` datetime DEFAULT NULL,
  `por` int(11) DEFAULT NULL,
  `em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ano` (`ano`, `em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O histórico da liberação do boletim: cada alteração, com o antes e o depois';

-- as liberações de antes passam para a tabela nova (a etapa liberada, com quem e quando)
INSERT INTO `esc_boletim_liberacao` (`ano`, `item`, `etapa_id`, `exibir`, `a_partir`, `alterado_por`, `alterado_em`)
SELECT e.`ano`, 'etapa', e.`id`, 1, NULL, b.`por`, b.`em`
  FROM `esc_boletim_liberado` b INNER JOIN `esc_etapas` e ON e.`id` = b.`etapa_id`
 WHERE NOT EXISTS (SELECT 1 FROM `esc_boletim_liberacao` x WHERE x.`ano` = e.`ano` AND x.`item` = 'etapa' AND x.`etapa_id` = e.`id`)
 ORDER BY e.`id`;
-- o ano com todas as etapas liberadas mostrava a média anual e a situação: continua mostrando
INSERT INTO `esc_boletim_liberacao` (`ano`, `item`, `etapa_id`, `exibir`, `a_partir`, `alterado_por`, `alterado_em`)
SELECT y.`ano`, i.`item`, 0, 1, NULL, NULL, NOW()
  FROM (SELECT e.`ano` FROM `esc_etapas` e LEFT JOIN `esc_boletim_liberado` b ON b.`etapa_id` = e.`id` GROUP BY e.`ano` HAVING COUNT(*) = COUNT(b.`etapa_id`)) y
  JOIN (SELECT 'media_final' `item` UNION ALL SELECT 'situacao_final') i
 WHERE NOT EXISTS (SELECT 1 FROM `esc_boletim_liberacao` x WHERE x.`ano` = y.`ano` AND x.`item` = i.`item`)
 ORDER BY y.`ano`, i.`item`;

-- o conselho de classe: a correção e a retirada (nada é apagado)
ALTER TABLE `esc_conselho` ADD COLUMN IF NOT EXISTS `situacao` varchar(10) NOT NULL DEFAULT 'valida' COMMENT 'valida | retirada' AFTER `em`;
ALTER TABLE `esc_conselho` ADD COLUMN IF NOT EXISTS `alterado_por` int(11) DEFAULT NULL AFTER `situacao`;
ALTER TABLE `esc_conselho` ADD COLUMN IF NOT EXISTS `alterado_em` datetime DEFAULT NULL AFTER `alterado_por`;
CREATE TABLE IF NOT EXISTS `esc_conselho_hist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conselho_id` int(11) NOT NULL,
  `acao` varchar(10) NOT NULL COMMENT 'corrigiu | retirou',
  `observacao_antes` varchar(1000) DEFAULT NULL,
  `observacao_depois` varchar(1000) DEFAULT NULL,
  `por` int(11) DEFAULT NULL,
  `em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `conselho` (`conselho_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O histórico do conselho de classe: cada correção e cada retirada, com o texto de antes';

-- as ações: fechar a etapa (até o professor), reabrir a etapa e liberar o boletim (até a supervisão de secretaria)
INSERT IGNORE INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`) VALUES
(107, 'fechar_a_etapa_107', 'Fechar a etapa (o professor, nas disciplinas dele)', 'Notas e frequência', 'professor', 26),
(108, 'reabrir_a_etapa_108', 'Reabrir a etapa (com o motivo)', 'Notas e frequência', 'supervisao_secretaria', 27),
(109, 'liberar_o_boletim_no_portal_109', 'Liberar o boletim no portal (etapas, média final e situação final)', 'Notas e frequência', 'supervisao_secretaria', 28);

-- as páginas que mudam: fechar (a ação nova, o escopo da disciplina: o professor só nas dele), reabrir (a ação nova; a
-- direção também), a liberação antiga do "Ano letivo e etapas" (leva à tela nova); quem reabre vê o relatório de
-- reaberturas (a ação de reabrir, a 55, até a supervisão)
UPDATE `adms_paginas` SET `acao_id` = 107, `escopo` = 'oferta' WHERE `id` = 524;
UPDATE `adms_paginas` SET `acao_id` = 108, `consulta_grava` = 1 WHERE `id` = 525;
UPDATE `adms_paginas` SET `acao_id` = 109, `consulta_grava` = 1, `nome_pagina` = 'Liberar o boletim (endereço antigo: leva à Liberação do boletim)', `grava` = 0 WHERE `id` = 528;
UPDATE `adms_paginas` SET `acao_id` = 55, `menu_pagina` = 'Reaberturas', `nome_pagina` = 'Reaberturas' WHERE `id` = 459;

-- as páginas novas: gravar a etapa (a recuperação e as faltas num botão só), a liberação do boletim (a tela e o
-- salvar) e o conselho (corrigir e retirar a observação)
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `consulta_grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, x.icone, 1, 1, 1, NOW(), x.grava, x.cg, 0, x.esc, x.acao FROM (
  SELECT 587 id, 'Gravar a etapa' menu, 'EscolaNotas' ctl, 'gravar' met, 'escola-notas' mctl, 'gravar' mmet, 'Gravar a recuperação e as faltas da etapa' nome, 'POST: as duas colunas de uma vez, cada uma pela permissão dela.' obs, NULL icone, 1 grava, 0 cg, 'oferta' esc, 23 acao
  UNION ALL SELECT 588, 'Liberação do boletim', 'EscolaLiberacao', 'index', 'escola-liberacao', 'index', 'Liberação do boletim', 'Por ano letivo: cada etapa, a média final e a situação final no portal.', 'fas fa-eye', 0, 0, NULL, 109
  UNION ALL SELECT 589, 'Salvar a liberação do boletim', 'EscolaLiberacao', 'salvar', 'escola-liberacao', 'salvar', 'Salvar a liberação do boletim', 'POST: a tela inteira, com o histórico.', NULL, 1, 1, NULL, 109
  UNION ALL SELECT 590, 'Corrigir a observação do conselho', 'EscolaConselho', 'corrigir', 'escola-conselho', 'corrigir', 'Corrigir a observação do conselho de classe', 'POST: o autor ou a direção; o texto de antes fica no histórico.', NULL, 1, 1, NULL, 54
  UNION ALL SELECT 591, 'Retirar a observação do conselho', 'EscolaConselho', 'retirar', 'escola-conselho', 'retirar', 'Retirar a observação do conselho de classe', 'POST: o autor ou a direção; fica marcada como retirada, no histórico.', NULL, 1, 1, NULL, 54
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id) ORDER BY x.id;
INSERT INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`)
SELECT x.id, 'escola' FROM (SELECT 587 id UNION ALL SELECT 588 UNION ALL SELECT 589 UNION ALL SELECT 590 UNION ALL SELECT 591) x
 WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas_modulo` m WHERE m.`adms_pagina_id` = x.id) ORDER BY x.id;

-- as linhas de permissão das páginas novas, nível a nível (as do "Ano letivo e etapas", 509, com o menu Ensino):
-- a liberação no menu para quem libera; as outras fora do menu
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 2, p.`id`, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` BETWEEN 587 AND 591
 WHERE d.`adms_pagina_id` = 509
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`; -- a ordem dos ids, fixa

-- quem pode o quê (1 administrador, 3 direção acadêmica, 4 secretaria, 6 professor, 10 supervisão de secretaria):
--   522 recuperação e 587 gravar: + secretaria e supervisão (as que lançam as faltas da etapa);
--   524 fechar: + professor;  525 reabrir: + direção;
--   528 (endereço antigo), 588 e 589 liberação: administrador, direção e supervisão (a secretaria deixa de liberar);
--   590 e 591 conselho: quem registra (administrador, secretaria, supervisão) e a direção;
--   459 reaberturas: + supervisão (quem reabre)
UPDATE `adms_nivacs_pgs` SET `permissao` = 1 WHERE `adms_pagina_id` IN (522, 587) AND `adms_niveis_acesso_id` IN (1, 4, 6, 10);
UPDATE `adms_nivacs_pgs` SET `permissao` = 1 WHERE `adms_pagina_id` = 524 AND `adms_niveis_acesso_id` = 6;
UPDATE `adms_nivacs_pgs` SET `permissao` = 1 WHERE `adms_pagina_id` = 525 AND `adms_niveis_acesso_id` = 3;
UPDATE `adms_nivacs_pgs` SET `permissao` = IF(`adms_niveis_acesso_id` IN (1, 3, 10), 1, 2) WHERE `adms_pagina_id` IN (528, 588, 589);
UPDATE `adms_nivacs_pgs` SET `lib_menu` = 1 WHERE `adms_pagina_id` = 588 AND `adms_niveis_acesso_id` IN (1, 3, 10);
UPDATE `adms_nivacs_pgs` SET `permissao` = 1 WHERE `adms_pagina_id` IN (590, 591) AND `adms_niveis_acesso_id` IN (1, 3, 4, 10);
UPDATE `adms_nivacs_pgs` SET `permissao` = 1 WHERE `adms_pagina_id` = 459 AND `adms_niveis_acesso_id` = 10;

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.94.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 111, '1.94.0', 1, 94, 0, '2026-11-22', 'A escola: a etapa (fechar, reabrir, gravar), a liberação do boletim por ano letivo e o conselho de classe.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.94.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.94.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 214, 111, 'A liberação do boletim', 'Em Ensino → Liberação do boletim, por ano letivo: cada etapa, a média final e a situação final, com "Exibir no portal" e "Liberar a partir de". Sem a média final liberada, a família e o aluno não veem média anual; sem a situação final, não veem aprovado nem reprovado. A tela diz, antes de salvar, o que muda no portal.', 1, 'administrador,secretaria', 'liberar-o-boletim', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 214);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 215, 111, 'A etapa', 'O professor fecha a etapa nas disciplinas dele. Com a etapa fechada, nota, falta e recuperação aparecem travadas, com o aviso para pedir a reabertura; quem reabre (supervisão de secretaria, direção acadêmica, administrador) escreve o motivo, e a reabertura aparece no relatório de reaberturas. A recuperação e as faltas da etapa gravam por um botão só. No conselho de classe, a observação pode ser corrigida ou retirada pelo autor ou pela direção.', 2, 'administrador,secretaria,professor', 'avaliar-na-escola', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 215);
