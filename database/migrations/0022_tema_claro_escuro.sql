-- O tema claro e escuro (Onda 3, Etapa 10): cada pessoa escolhe, no menu do usuário, "Claro", "Escuro" ou "Automático"
-- (o do aparelho); começa claro. A escolha fica na conta (no painel e no portal) e num cookie do navegador, para as
-- telas de acesso seguirem a última escolha. Documentos e PDFs são sempre claros.

ALTER TABLE `adms_usuarios` ADD COLUMN IF NOT EXISTS `tema` varchar(10) NOT NULL DEFAULT 'claro' COMMENT 'claro | escuro | auto (Onda 3, Etapa 10)';
ALTER TABLE `col_usuarios` ADD COLUMN IF NOT EXISTS `tema` varchar(10) NOT NULL DEFAULT 'claro' COMMENT 'claro | escuro | auto (Onda 3, Etapa 10)';

-- a página que grava a escolha (POST, fora do menu): todo perfil do painel pode — inclusive os "só consulta" (a Direção
-- acadêmica, a Coordenação de curso): é a preferência da pessoa, não um dado acadêmico (grava = 0)
INSERT IGNORE INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`) VALUES
(98, 'escolher_o_tema_98', 'Escolher o tema (claro ou escuro)', 'Ajuda e perfil', 'professor', 98);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT 505, 'Escolher o tema', 'Tema', 'salvar', 'tema', 'salvar', 'Escolher o tema', 'POST. Grava o tema (claro, escuro ou automático) de quem entrou.', 2, NULL, 1, 1, 1, NOW(), 0, 0, NULL, 98
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 505);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 1, 505, 2, 2, 1, n.`id`, 505, NOW() FROM `adms_niveis_acessos` n
 WHERE NOT EXISTS (SELECT 1 FROM `adms_nivacs_pgs` x WHERE x.`adms_niveis_acesso_id` = n.`id` AND x.`adms_pagina_id` = 505);

-- o portal: o aluno também escolhe
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`)
SELECT 28, 'Escolher o tema', 'Tema', 'salvar', 'tema', 'salvar', 'Escolher o tema', 'POST. Grava o tema do aluno.', 2, NULL, 1, 1, 1, NOW()
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `col_paginas` WHERE `id` = 28);
INSERT INTO `col_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 1, 0, 1, 2, 2, 1, 28, NOW() FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `col_nivacs_pgs` x WHERE x.`adms_niveis_acesso_id` = 1 AND x.`adms_pagina_id` = 28);

-- A versão e a novidade
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.36.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 52, '1.36.0', 1, 36, 0, '2026-11-15', 'O tema claro, escuro ou automático, por pessoa, no painel e no portal; documentos e PDFs sempre claros; a versão só para quem entrou.', 1, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.36.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.36.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 147, 52, 'O tema claro ou escuro', 'No menu do usuário (o seu nome, no canto do menu), escolha o tema: Claro, Escuro ou Automático (o do seu aparelho).', 1, 'administrador,secretaria,professor,aluno', NULL, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 147);
