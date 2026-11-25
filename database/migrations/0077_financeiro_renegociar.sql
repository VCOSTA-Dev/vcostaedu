-- O financeiro, Parte 6 (Onda 8; D-98): Renegociar (os títulos em aberto, com os encargos de hoje, menos um desconto
-- opcional, em N parcelas novas ligadas à renegociação) e Desfazer renegociação (só sem recebimento nem baixa nas
-- parcelas novas; a permissão é a de desfazer: 106).

INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, NULL, x.grupo, 1, 1, NOW(), x.grava, x.sens, x.escopo, x.acao FROM (
  SELECT 575 id, 'Renegociar' menu, 'Financeiro' ctl, 'renegociar' met, 'financeiro' mctl, 'renegociar' mmet, 'Renegociar títulos do aluno' nome, 'POST. Os títulos, as parcelas, o primeiro vencimento e o desconto.' obs, 1 grupo, 1 grava, 0 sens, NULL escopo, 105 acao
  UNION ALL SELECT 576, 'Desfazer renegociação', 'Financeiro', 'desfazerRenegociacao', 'financeiro', 'desfazer-renegociacao', 'Desfazer a renegociação', 'POST. Com o motivo; só sem recebimento nem baixa nas parcelas novas.', 1, 1, 1, NULL, 106
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, p.`id`, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON (d.`adms_pagina_id` = 47 AND p.`id` = 575) OR (d.`adms_pagina_id` = 383 AND p.`id` = 576)
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`; -- a ordem dos ids, fixa

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.90.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 107, '1.90.0', 1, 90, 0, '2026-11-22', 'O financeiro: Renegociar e Desfazer renegociação.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.90.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.90.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 210, 107, 'Renegociar', 'Na ficha financeira, Renegociar junta títulos em aberto (com os encargos de hoje e, se quiser, um desconto) em parcelas novas. Desfazer renegociação devolve os títulos originais, só quando nenhuma parcela nova foi recebida ou baixada: se foi, a tela diz o que desfazer antes.', 1, 'administrador,secretaria', 'o-financeiro', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 210);
