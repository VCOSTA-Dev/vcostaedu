-- O financeiro, Parte 2 (Onda 8; D-98): Receber e Desfazer recebimento. Receber grava a data (padrão hoje; pode ser
-- antes, não depois), a forma e a observação; os encargos são recalculados pela data no servidor (a tela mostra a
-- conta na hora). Desfazer recebimento devolve o título a aberto, tira do caixa e registra o motivo. A permissão de
-- desfazer é outra ação (106), a do administrador e da supervisão (como reabrir uma oferta).

INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, 'Financeiro', x.met, 'financeiro', x.mmet, x.nome, x.obs, 2, NULL, 1, 1, 1, NOW(), x.grava, x.sens, NULL, x.acao FROM (
  SELECT 568 id, 'Encargos na data' menu, 'encargos' met, 'encargos' mmet, 'Os encargos do título numa data' nome, 'GET, em JSON: a conta da tela de Receber.' obs, 0 grava, 0 sens, 105 acao
  UNION ALL SELECT 569, 'Receber', 'receber', 'receber', 'Receber o título', 'POST. A data, a forma e a observação; os encargos pela data.', 1, 0, 105
  UNION ALL SELECT 572, 'Desfazer no financeiro', 'desfazer', 'desfazer', 'Desfazer recebimento, baixa ou cancelamento', 'POST. Com o motivo; o título volta a aberto.', 1, 1, 106
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, p.`id`, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON (d.`adms_pagina_id` = 47 AND p.`id` IN (568, 569)) OR (d.`adms_pagina_id` = 383 AND p.`id` = 572)
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`; -- a ordem dos ids, fixa

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.86.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 103, '1.86.0', 1, 86, 0, '2026-11-22', 'O financeiro: Receber e Desfazer recebimento, com os encargos pela data.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.86.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.86.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 206, 103, 'Receber', 'Na ficha financeira, Receber pede a data (hoje, ou um dia antes), a forma e uma observação, e mostra a conta na hora: o título que vence no dia 20 e é pago no dia 18 não tem multa nem juros; pago no dia 21, tem. Desfazer recebimento (o administrador e a supervisão) devolve o título a aberto, com o motivo.', 1, 'administrador,secretaria', 'o-financeiro', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 206);
