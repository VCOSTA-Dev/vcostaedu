-- Sobre, Avisos do sistema, a versão nova, as Bibliotecas e as Verificações de segurança.
--
-- Três telas novas no grupo Manutenção, só para o Administrador: "Avisos do sistema" (as versões, as pendências, a
-- versão nova, o resumo da segurança, o copyright e os links do projeto), "Verificações de segurança" (cada item OK,
-- ALERTA ou FALHA, com o que fazer — a mesma implementação da linha de comando) e "Bibliotecas" (as de terceiros, com
-- a versão e a licença). Com elas, o grupo Manutenção deixa de ter um item só. E o parâmetro que desliga a consulta da
-- versão nova (feita no máximo uma vez por dia, sem enviar nenhum dado da instalação).

INSERT IGNORE INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`) VALUES
(93, 'ver_os_avisos_do_sistema_93', 'Ver os avisos do sistema', 'Manutenção', 'administrador', 83),
(94, 'conferir_a_seguranca_da_instalacao_94', 'Conferir a segurança da instalação', 'Manutenção', 'administrador', 83),
(95, 'ver_as_bibliotecas_95', 'Ver as bibliotecas', 'Manutenção', 'administrador', 83);

INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT 494, 'Avisos do sistema', 'AvisosSistema', 'index', 'avisos-sistema', 'index', 'Avisos do sistema', 'As versões, as pendências, a versão nova, a segurança e o projeto.', 2, 'fas fa-bell', 1, 1, 1, NOW(), 0, 0, NULL, 93
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 494);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT 495, 'Verificações de segurança', 'VerificacoesSeguranca', 'index', 'verificacoes-seguranca', 'index', 'Verificações de segurança', 'Cada item OK, ALERTA ou FALHA, com o que fazer.', 2, 'fas fa-shield-halved', 1, 1, 1, NOW(), 0, 0, NULL, 94
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 495);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT 496, 'Bibliotecas', 'Bibliotecas', 'index', 'bibliotecas', 'index', 'Bibliotecas', 'As bibliotecas de terceiros, com a versão e a licença.', 2, 'fas fa-cubes', 1, 1, 1, NOW(), 0, 0, NULL, 95
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 496);

-- as permissões: todo nível tem a linha (sem permissão); o Administrador, com o item no menu Manutenção
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 2, p.`id`, 2, 2, 1, n.`id`, p.`id`, NOW() FROM `adms_niveis_acessos` n JOIN `adms_paginas` p ON p.`id` IN (494, 495, 496)
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.`id` AND x.`adms_pagina_id` = p.`id`);
UPDATE `adms_nivacs_pgs` d JOIN `adms_niveis_acessos` n ON n.`id` = d.`adms_niveis_acesso_id`
   SET d.`permissao` = 1, d.`lib_menu` = 1, d.`dropdown` = 1, d.`adms_menu_id` = 9, d.`ordem` = CASE d.`adms_pagina_id` WHEN 494 THEN 490 WHEN 495 THEN 492 ELSE 493 END
 WHERE d.`adms_pagina_id` IN (494, 495, 496) AND n.`papel` = 'administrador';

INSERT INTO `adms_parametros` (`chave`, `valor`, `descricao`, `grupo`)
SELECT 'sistema.verificar_versao_nova', '1', 'Consultar, uma vez por dia e sem enviar nenhum dado, se há uma versão nova (1 = sim, 0 = não)', 'Sistema'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_parametros` WHERE `chave` = 'sistema.verificar_versao_nova');

-- A versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.32.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 48, '1.32.0', 1, 32, 0, '2026-11-15', 'Avisos do sistema, Verificações de segurança e Bibliotecas em Manutenção; o aviso de versão nova (uma vez por dia, sem enviar dado nenhum); a página Sobre sem a versão e sem as bibliotecas para quem não entrou.', 1, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.32.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.32.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 141, 48, 'Avisos do sistema e segurança', 'Em Manutenção: Avisos do sistema (as versões, as atualizações por aplicar, a versão nova e o resumo da segurança), Verificações de segurança (cada item com o que fazer) e Bibliotecas.', 1, 'administrador', 'avisos-e-seguranca', '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 141);
