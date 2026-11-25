-- Onda 8, Etapa 1 (D-67): o login do portal na escola.
--   - o responsável ganha o LOGIN próprio (esc_responsaveis.login), separado do cadastro: o CPF (só os dígitos) ou o
--     e-mail (em minúsculas), escolhido no convite; único;
--   - quem já criou a senha (antes desta versão entrava com o e-mail OU o CPF) fica com o e-mail como login, ou com o
--     CPF quando não tem e-mail; nada do cadastro muda;
--   - o e-mail do cadastro do aluno sem a unicidade no banco (a regra fica na tela, só no ensino superior);
--   - a página "Trocar o login" do responsável, na ficha (a mesma permissão de bloquear o acesso).
-- No ensino superior a tabela fica vazia e nada muda.

ALTER TABLE `esc_responsaveis`
  ADD COLUMN IF NOT EXISTS `login` varchar(190) DEFAULT NULL COMMENT 'o login do portal: o CPF (só os dígitos) ou o e-mail; escolhido no convite' AFTER `cpf`,
  ADD UNIQUE KEY IF NOT EXISTS `login` (`login`);

-- o e-mail do cadastro do aluno deixa de ser único no banco: na escola ele não é login, e irmãos podem ter o e-mail da
-- mesma pessoa; no ensino superior, onde o e-mail pode ser o login, a tela continua recusando o repetido
ALTER TABLE `col_usuarios` DROP INDEX IF EXISTS `email`, ADD KEY IF NOT EXISTS `email` (`email`);

UPDATE `esc_responsaveis` SET `login` = COALESCE(`email`, `cpf`) WHERE `login` IS NULL AND `senha` IS NOT NULL;

-- a página do painel
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT 552, 'Trocar o login', 'Responsaveis', 'login', 'responsaveis', 'login', 'Trocar o login do responsável no portal', 'POST. O CPF ou o e-mail do cadastro, se não for o login de outra conta.', 2, NULL, 1, 1, 1, NOW(), 1, 0, NULL, 104
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 552);
INSERT IGNORE INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`) VALUES (552, 'escola');
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 552, d.`dropdown`, d.`lib_menu`, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 552, NOW()
  FROM `adms_nivacs_pgs` d
 WHERE d.`adms_pagina_id` = 548
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 552);

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.57.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 74, '1.57.0', 1, 57, 0, '2026-11-20', 'A entrada no portal da escola pelo login de cada conta: a matrícula do aluno; o CPF ou o e-mail do responsável.', 1, '2026-11-20 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.57.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.57.0';

-- a novidade
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 177, 74, 'A entrada no portal da escola', 'Na escola, cada conta entra no portal pelo próprio login: o aluno, com o número de matrícula; o responsável, com o CPF ou o e-mail que escolheu no convite. O e-mail e o CPF do cadastro do aluno não servem para entrar, e dois logins iguais não existem. Na ficha do responsável, "Trocar o login".', 1, 'administrador,secretaria', 'entrar-no-portal-como-responsavel', '2026-11-20 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 177);
