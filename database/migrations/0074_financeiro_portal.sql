-- O financeiro, Parte 3 (Onda 8; D-98): a ficha financeira só de leitura no portal do aluno e no do responsável (só o
-- responsável financeiro daquele aluno, naquele ano: VcAcessoFamilia::podeVerFinanceiro; o titular e o acesso adicional,
-- nunca), e o cartão "Ficha financeira" ("Em dia" ou "N títulos vencidos", e "Consultar") na página inicial e no Resumo.

INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.menu, x.obs, 2, NULL, 1, 1, 1, NOW() FROM (
  SELECT 41 id, 'Ficha financeira' menu, 'Home' ctl, 'financeiroAluno' met, 'home' mctl, 'financeiro-aluno' mmet, 'A ficha financeira do aluno, só de leitura.' obs
  UNION ALL SELECT 42, 'Ficha financeira do filho', 'Familia', 'financeiro', 'familia', 'financeiro', 'A ficha financeira do filho, só de leitura (só o responsável financeiro).'
) x WHERE NOT EXISTS (SELECT 1 FROM `col_paginas` p WHERE p.`id` = x.id);
INSERT INTO `col_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 1, x.pg, 2, 2, 1, x.nivel, x.pg, NOW() FROM (SELECT 41 pg, 1 nivel UNION ALL SELECT 42, 2) x
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `col_nivacs_pgs`) y WHERE y.`adms_niveis_acesso_id` = x.nivel AND y.`adms_pagina_id` = x.pg)
 ORDER BY x.pg, x.nivel; -- a ordem dos ids, fixa

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.87.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 104, '1.87.0', 1, 87, 0, '2026-11-22', 'O financeiro no portal: a ficha só de leitura e o cartão.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.87.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.87.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 207, 104, 'A ficha financeira no portal', 'O aluno e o responsável financeiro veem a ficha financeira no portal, só para consulta, com os mesmos filtros (deste mês, vencidos, pagos, a vencer). Na página inicial e no Resumo do filho, o cartão Ficha financeira diz "Em dia" ou quantos títulos estão vencidos.', 1, 'administrador,secretaria', 'o-que-o-responsavel-ve-no-portal', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 207);
