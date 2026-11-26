-- A CORREÇÃO DO ENSAIO (D-121, D-122; 1.102.0): os acertos de configuração e a versão.
--   1. B3.10 (D-122): o professor ganha dois relatórios, "Pendências de lançamento" e "Abaixo ou perto do mínimo", sempre
--      só com as disciplinas dele (o recorte já é o da fonte dos relatórios: AdmsRelatorioFonte::ofertas). As duas páginas
--      passam para a ação "Consultar os relatórios da turma" (a 60, a dos outros três relatórios do professor); quem já
--      as via (todos os perfis acima do professor) tem a 60 também, então ninguém perde o acesso.
--   2. B3.11 (D-122): "Vídeos" sai do menu Ajuda (e, por isso, dos atalhos) no painel e no portal, em todos os perfis. A
--      página continua (a permissão não muda): só deixa de aparecer.
--   3. B2.6 (D-121): as páginas do professor que entra no Moodle.
--   4. A versão 1.102.0 e as novidades.
-- COMO DESFAZER: 1. as páginas 449 e 452 de volta à ação 58 e sem a permissão do papel professor; 2. lib_menu = 1 na
-- página 463 (painel) e 25 (portal); 3. apagar as páginas 599 e 600 (e as linhas delas) e a ação 111; 4. apagar as novidades 228 e 229 e a versão 119, e voltar a 1.101.0 à situação 1.

-- 1. os dois relatórios do professor
UPDATE `adms_paginas` SET `acao_id` = 60 WHERE `id` IN (449, 452) AND `acao_id` = 58;
UPDATE `adms_nivacs_pgs` x JOIN `adms_niveis_acessos` n ON n.`id` = x.`adms_niveis_acesso_id` AND n.`papel` = 'professor'
   SET x.`permissao` = 1
 WHERE x.`adms_pagina_id` IN (449, 452);

-- 2. Vídeos fora do menu (painel e portal)
UPDATE `adms_nivacs_pgs` SET `lib_menu` = 2 WHERE `adms_pagina_id` IN (SELECT `id` FROM `adms_paginas` WHERE `controller` = 'Ajuda' AND `metodo` = 'videos');
UPDATE `col_nivacs_pgs` SET `lib_menu` = 2 WHERE `adms_pagina_id` IN (SELECT `id` FROM `col_paginas` WHERE `controller` = 'Home' AND `metodo` = 'videos');

-- 3. B2.6 (D-121): o professor entra no Moodle pelo painel, como o aluno pelo portal (a ação 111; as páginas 599 e 600,
--    só no curso livre, só para o papel de professor, fora do menu: o botão fica na Página Inicial dele)
INSERT IGNORE INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`) VALUES
(111, 'entrar_no_moodle_como_professor_111', 'Entrar no Moodle (o professor)', 'Integrações', 'professor', 111);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `consulta_grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, 'MoodleProfessor', x.met, 'moodle-professor', x.mmet, x.nome, x.obs, 2, 'fas fa-right-to-bracket', 1, 1, 1, NOW(), 0, 0, 0, NULL, 111 FROM (
  SELECT 599 id, 'Entrar no Moodle' menu, 'entrar' met, 'entrar' mmet, 'Entrar no Moodle (o professor)' nome, 'O link de entrada de uso único, pedido pelo servidor (D-121).' obs
  UNION ALL SELECT 600, 'Abrir a disciplina no Moodle', 'abrir', 'abrir', 'Abrir a disciplina no Moodle (o professor)', 'O curso da disciplina no Moodle, pelo link de entrada (D-121).'
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id) ORDER BY x.id;
INSERT INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`)
SELECT p.`id`, 'livre' FROM `adms_paginas` p
 WHERE p.`id` IN (599, 600) AND NOT EXISTS (SELECT 1 FROM `adms_paginas_modulo` m WHERE m.`adms_pagina_id` = p.`id`) ORDER BY p.`id`;
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT IF(n.`papel` = 'professor', 1, 2), p.`id`, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` IN (599, 600)
  JOIN `adms_niveis_acessos` n ON n.`id` = d.`adms_niveis_acesso_id`
 WHERE d.`adms_pagina_id` = 46
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`;

-- 4. a versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.102.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 119, '1.102.0', 1, 102, 0, '2026-11-26', 'A correção do ensaio: a ficha financeira fora da escola, o modelo da planilha para o professor, o registro da instalação, o curso livre com o professor e o aluno no Moodle pela Página Inicial, o filtro das disciplinas e dois relatórios para o professor.', 1, '2026-11-26 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.102.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.102.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 228, 119, 'As disciplinas e os relatórios do professor', 'Em Minhas Disciplinas, o filtro Ativas, Encerradas e Todas (abre em Ativas). Nos relatórios, o professor ganha Pendências de lançamento e Abaixo ou perto do mínimo, sempre só com as disciplinas dele. O modelo da planilha de notas baixa de novo pela tela de importar as notas.', 1, 'professor', NULL, '2026-11-26 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 228);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 229, 119, 'Correções', 'A ficha financeira do aluno abre no ensino superior e no curso livre; o registro da instalação lista os módulos sem repetição; os vídeos saem do menu Ajuda (a Central de ajuda continua).', 2, 'administrador,secretaria', NULL, '2026-11-26 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 229);
