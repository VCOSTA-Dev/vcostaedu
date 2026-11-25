-- Onda 2, Etapa 7 — a tela de atualização e a tela "Versão".
--
-- A página da tela de atualização nasce AQUI, na migration que a própria tela aplica: antes dela, o roteador abre a tela
-- para o Administrador assim mesmo (AdmsPaginas::telaDeAtualizacao — a dependência recursiva). A tela "Versão" é de
-- todos os perfis do painel (cada um vê a versão; só o Administrador vê o caminho para atualizar).

INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`)
SELECT p.* FROM (
  SELECT 489 AS id, 'Atualizar o sistema' AS mp, 'Atualizar' AS c, 'index' AS m, 'atualizar' AS mc, 'index' AS mm, 'Atualizar o sistema' AS np, 'Onda 2: as etapas do banco de dados por aplicar, com a confirmação do backup.' AS o, 2 AS lp, 'fas fa-arrows-rotate' AS ic, 1 AS g, 1 AS tp, 1 AS s, NOW() AS cr, 0 AS gr, 0 AS se, NULL AS es
  UNION ALL SELECT 490, 'Aplicar a atualização', 'Atualizar', 'aplicar', 'atualizar', 'aplicar', 'Aplicar a atualização', 'Onda 2: aplica as etapas pendentes pelo mesmo runner do comando (POST).', 2, 'fas fa-play', 1, 1, 1, NOW(), 1, 0, NULL
  UNION ALL SELECT 491, 'Versão', 'Versao', 'index', 'versao', 'index', 'Versão do sistema', 'Onda 2: a versão instalada e se há etapa pendente.', 2, 'fas fa-code-branch', 1, 1, 1, NOW(), 0, 0, NULL
) p WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` x WHERE x.`id` = p.id);

INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 2, p.`id`, 2, 2, 1, n.`id`, p.`id`, NOW()
  FROM `adms_niveis_acessos` n JOIN `adms_paginas` p ON p.`id` BETWEEN 489 AND 491
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.`id` AND x.`adms_pagina_id` = p.`id`);
-- Atualizar: só o Administrador, no menu Manutenção
UPDATE `adms_nivacs_pgs` d JOIN `adms_niveis_acessos` n ON n.`id` = d.`adms_niveis_acesso_id`
   SET d.`permissao` = 1, d.`lib_menu` = IF(d.`adms_pagina_id` = 489, 1, 2), d.`dropdown` = IF(d.`adms_pagina_id` = 489, 1, 2), d.`adms_menu_id` = IF(d.`adms_pagina_id` = 489, 9, 1)
 WHERE d.`adms_pagina_id` IN (489, 490) AND n.`papel` = 'administrador';
-- Versão: todos os perfis do painel, no menu Ajuda
UPDATE `adms_nivacs_pgs` d JOIN `adms_niveis_acessos` n ON n.`id` = d.`adms_niveis_acesso_id`
   SET d.`permissao` = 1, d.`lib_menu` = 1, d.`dropdown` = 1, d.`adms_menu_id` = 8
 WHERE d.`adms_pagina_id` = 491 AND n.`papel` IS NOT NULL;

-- A versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.25.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 41, '1.25.0', 1, 25, 0, '2026-11-15', 'Atualizar pela tela: as etapas pendentes do banco aplicadas pelo Administrador, com a confirmação do backup, pelo mesmo caminho do comando; e a tela Versão.', 1, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.25.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.25.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 128, 41, 'Atualizar o sistema pela tela', 'Quando os arquivos de uma versão nova chegam, o aviso de atualização pendente leva o Administrador à tela Manutenção → Atualizar o sistema: ela mostra as etapas do banco por aplicar, pede a confirmação do backup e aplica pelo mesmo caminho do comando php bin/migrar.php aplicar.', 1, 'administrador', 'atualizar-o-sistema', '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 128);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 129, 41, 'A tela Versão', 'Em Ajuda → Versão, cada perfil vê a versão instalada e se há atualização pendente.', 2, 'administrador,secretaria,professor', NULL, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 129);
