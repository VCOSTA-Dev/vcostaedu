-- O financeiro, Parte 4 (Onda 8; D-98): Baixar (quita sem entrada de dinheiro: não entra no caixa) e Cancelar (o título
-- deixa de ser devido), os dois com o motivo obrigatório; Desfazer baixa e Desfazer cancelamento pela página de desfazer
-- (a mesma permissão: 106). Ao desfazer, a situação mostrada sai da data de hoje (pode voltar como vencido).

INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, 'Financeiro', x.met, 'financeiro', x.mmet, x.nome, x.obs, 2, NULL, 1, 1, 1, NOW(), 1, 0, NULL, 105 FROM (
  SELECT 570 id, 'Baixar' menu, 'baixar' met, 'baixar' mmet, 'Baixar o título' nome, 'POST. Sem entrada de dinheiro; com o motivo.' obs
  UNION ALL SELECT 571, 'Cancelar', 'cancelar', 'cancelar', 'Cancelar o título', 'POST. Com o motivo.'
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, p.`id`, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` IN (570, 571)
 WHERE d.`adms_pagina_id` = 47
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`; -- a ordem dos ids, fixa

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.88.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 105, '1.88.0', 1, 88, 0, '2026-11-22', 'O financeiro: Baixar e Cancelar, com o motivo, e os dois desfazer.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.88.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.88.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 208, 105, 'Baixar e Cancelar', 'Baixar quita o título sem entrada de dinheiro (uma bolsa, uma isenção, um acerto) e não entra no caixa; Cancelar faz o título deixar de ser devido. Os dois pedem o motivo. Desfazer baixa e Desfazer cancelamento devolvem o título a aberto; se o vencimento já passou, ele volta como vencido.', 1, 'administrador,secretaria', 'o-financeiro', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 208);
