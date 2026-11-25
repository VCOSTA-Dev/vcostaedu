-- Os boletins da turma de uma vez (Onda 8, correção, acréscimo C; D-80): na escola, a tela da turma e a do conselho de
-- classe ganham "Imprimir os boletins da turma": um PDF só, um aluno por página, o mesmo boletim de cada um.

INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT 559, 'Boletins da turma', 'Turmas', 'boletins', 'turmas', 'boletins', 'Os boletins da turma em PDF', 'Um PDF só, um aluno por página.', 2, NULL, 1, 1, 1, NOW(), 0, 0, NULL, 8
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = 559);
INSERT IGNORE INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`) VALUES (559, 'escola');
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 559, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 559, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 295
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 559)
 ORDER BY d.`adms_niveis_acesso_id`; -- a ordem dos ids, fixa

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.71.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 88, '1.71.0', 1, 71, 0, '2026-11-21', 'Os boletins da turma num PDF só.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.71.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.71.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 191, 88, 'Os boletins da turma de uma vez', 'Na tela da turma e na do conselho de classe, "Imprimir os boletins da turma" gera um PDF só, com o boletim de cada aluno numa página.', 1, 'administrador,secretaria', 'ver-e-imprimir-o-boletim', '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 191);
