-- O financeiro, Parte 5 (Onda 8; D-98): os relatórios do financeiro — a Ficha do caixa (os recebimentos de um dia, por
-- quem recebeu, o total por forma e o total geral; imprimível) e os Recebimentos por período (para a auditoria, com os
-- desfeitos marcados; exporta como os outros). No menu de relatórios, na categoria nova "Financeiro".

INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, NULL, x.grupo, 1, 1, NOW(), x.grava, x.sens, x.escopo, x.acao FROM (
  SELECT 573 id, 'Ficha do caixa' menu, 'Relatorios' ctl, 'caixa' met, 'relatorios' mctl, 'caixa' mmet, 'R-FIN-01 Ficha do caixa' nome, 'Relatório (Onda 8, financeiro).' obs, 5 grupo, 0 grava, 0 sens, 'relatorio' escopo, 105 acao
  UNION ALL SELECT 574, 'Recebimentos por período', 'Relatorios', 'recebimentos', 'relatorios', 'recebimentos', 'R-FIN-02 Recebimentos por período', 'Relatório (Onda 8, financeiro).', 5, 0, 0, 'relatorio', 105
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, p.`id`, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON (d.`adms_pagina_id` = 47 AND p.`id` = 573) OR (d.`adms_pagina_id` = 47 AND p.`id` = 574)
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`; -- a ordem dos ids, fixa

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.89.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 106, '1.89.0', 1, 89, 0, '2026-11-22', 'O financeiro: a ficha do caixa e os recebimentos por período.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.89.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.89.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 209, 106, 'O caixa e os recebimentos', 'Em Relatórios → Financeiro: a Ficha do caixa (os recebimentos de um dia, por quem recebeu, com o total por forma e o total geral; dá para imprimir) e os Recebimentos por período (para a auditoria, com os desfeitos marcados; exporta como os outros relatórios).', 1, 'administrador,secretaria', 'o-financeiro', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 209);
