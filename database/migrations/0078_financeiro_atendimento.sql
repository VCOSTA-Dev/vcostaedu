-- O financeiro, Parte 7 (Onda 8; D-98): o histórico de atendimento na ficha financeira — cada ação entra sozinha (quem,
-- quando, o motivo); o atendimento se registra em texto livre (a data, quem atendeu, o que foi combinado), ligado ou não
-- a um título.

INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, NULL, x.grupo, 1, 1, NOW(), x.grava, x.sens, x.escopo, x.acao FROM (
  SELECT 577 id, 'Registrar atendimento' menu, 'Financeiro' ctl, 'atendimento' met, 'financeiro' mctl, 'atendimento' mmet, 'Registrar um atendimento financeiro' nome, 'POST. A data, o texto e, se quiser, o título.' obs, 1 grupo, 1 grava, 0 sens, NULL escopo, 105 acao
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, p.`id`, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON (d.`adms_pagina_id` = 47 AND p.`id` = 577)
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`; -- a ordem dos ids, fixa

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.91.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 108, '1.91.0', 1, 91, 0, '2026-11-22', 'O financeiro: o histórico de atendimento.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.91.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.91.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 211, 108, 'O histórico de atendimento', 'Na ficha financeira, o Histórico de atendimento mostra cada ação (quem, quando, o motivo) e guarda o que foi conversado e combinado com a família, em texto livre, ligado ou não a um título.', 1, 'administrador,secretaria', 'o-financeiro', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 211);
