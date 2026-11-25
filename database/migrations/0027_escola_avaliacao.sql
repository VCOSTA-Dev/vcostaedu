-- A escola, a avaliação (Onda 4, Etapa 3; docs/ESCOLA.md, D-48): a média em dois níveis — o motor de sempre uma vez
-- por etapa, a recuperação da etapa, a média anual, a frequência, a situação final e o conselho de classe; o fechamento
-- por etapa (a regra copiada; reabrir guarda o substituído); o boletim por etapa, liberado etapa a etapa. Tudo em
-- tabelas próprias, vazias numa instalação de ensino superior; o cálculo do ensino superior não muda.

CREATE TABLE IF NOT EXISTS `esc_recuperacoes` (
  `oferta_id` int(11) NOT NULL,
  `etapa_id` int(11) NOT NULL,
  `rm` varchar(50) NOT NULL,
  `nota` varchar(10) NOT NULL,
  `por` int(11) DEFAULT NULL,
  `em` datetime NOT NULL,
  PRIMARY KEY (`oferta_id`, `etapa_id`, `rm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='A nota da recuperação da etapa';

CREATE TABLE IF NOT EXISTS `esc_faltas_etapa` (
  `oferta_id` int(11) NOT NULL,
  `etapa_id` int(11) NOT NULL,
  `rm` varchar(50) NOT NULL,
  `faltas` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`oferta_id`, `etapa_id`, `rm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As faltas de cada etapa (o total da oferta é a soma)';

CREATE TABLE IF NOT EXISTS `esc_fechamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `oferta_id` int(11) NOT NULL,
  `etapa_id` int(11) NOT NULL,
  `numero` int(11) NOT NULL,
  `regra_json` longtext NOT NULL COMMENT 'a regra da oferta e as da escola, copiadas no fechamento',
  `regra_hash` char(64) NOT NULL,
  `situacao` varchar(12) NOT NULL COMMENT 'vigente | substituido',
  `fechado_por` int(11) DEFAULT NULL,
  `fechado_em` datetime NOT NULL,
  `reaberto_por` int(11) DEFAULT NULL,
  `reaberto_em` datetime DEFAULT NULL,
  `motivo` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oferta_etapa` (`oferta_id`, `etapa_id`, `situacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O fechamento de cada etapa de cada oferta (o reaberto fica, substituído)';

CREATE TABLE IF NOT EXISTS `esc_fechamento_notas` (
  `fechamento_id` int(11) NOT NULL,
  `rm` varchar(50) NOT NULL,
  `media` varchar(10) DEFAULT NULL,
  `recuperacao` varchar(10) DEFAULT NULL,
  `nota` varchar(10) DEFAULT NULL,
  `lancadas` int(11) NOT NULL DEFAULT 0,
  `memoria` text DEFAULT NULL,
  PRIMARY KEY (`fechamento_id`, `rm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='A nota da etapa de cada aluno, guardada no fechamento';

CREATE TABLE IF NOT EXISTS `esc_conselho` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `turma_id` int(11) NOT NULL,
  `etapa_id` int(11) DEFAULT NULL COMMENT 'vazio = o fim do ano',
  `rm` varchar(50) NOT NULL,
  `oferta_id` int(11) DEFAULT NULL COMMENT 'vazio = todos os componentes',
  `decisao` varchar(12) NOT NULL COMMENT 'aprovar | observacao',
  `observacao` varchar(1000) NOT NULL,
  `por` int(11) DEFAULT NULL,
  `em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `turma` (`turma_id`, `rm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O conselho de classe: as observações por etapa e a decisão do fim do ano';

CREATE TABLE IF NOT EXISTS `esc_boletim_liberado` (
  `etapa_id` int(11) NOT NULL,
  `por` int(11) DEFAULT NULL,
  `em` datetime NOT NULL,
  PRIMARY KEY (`etapa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As etapas cujo boletim o portal do aluno mostra';

-- as páginas (módulo "escola"; nenhuma no menu: abrem da turma e do componente)
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, NULL, 1, 1, 1, NOW(), x.grava, 0, x.escopo, x.acao FROM (
  SELECT 521 id, 'Etapas do componente' menu, 'EscolaNotas' ctl, 'etapas' met, 'escola-notas' mctl, 'etapas' mmet, 'As etapas de um componente' nome, 'Os alunos × as etapas: a média, a recuperação, a nota da etapa, a média anual e a situação.' obs, 0 grava, 20 acao, 'oferta' escopo
  UNION ALL SELECT 522, 'Gravar a recuperação', 'EscolaNotas', 'recuperacao', 'escola-notas', 'recuperacao', 'Gravar as notas da recuperação', 'POST. Só de quem está em recuperação na etapa.', 1, 25, 'oferta'
  UNION ALL SELECT 523, 'Gravar as faltas da etapa', 'EscolaNotas', 'faltas', 'escola-notas', 'faltas', 'Gravar as faltas da etapa', 'POST. O total da oferta é a soma das etapas.', 1, 23, 'oferta'
  UNION ALL SELECT 524, 'Fechar a etapa', 'EscolaNotas', 'fechar', 'escola-notas', 'fechar', 'Fechar a etapa', 'POST. As notas da etapa ficam guardadas, com a regra copiada.', 1, 54, NULL
  UNION ALL SELECT 525, 'Reabrir a etapa', 'EscolaNotas', 'reabrir', 'escola-notas', 'reabrir', 'Reabrir a etapa', 'POST. Com o motivo; o fechamento substituído fica guardado.', 1, 55, NULL
  UNION ALL SELECT 526, 'Conselho de classe', 'EscolaConselho', 'turma', 'escola-conselho', 'turma', 'Conselho de classe', 'Os alunos da turma, os componentes e as decisões do conselho.', 0, 43, NULL
  UNION ALL SELECT 527, 'Registrar no conselho de classe', 'EscolaConselho', 'registrar', 'escola-conselho', 'registrar', 'Registrar a decisão do conselho de classe', 'POST. A observação é obrigatória.', 1, 54, NULL
  UNION ALL SELECT 528, 'Liberar o boletim da etapa', 'EscolaAnos', 'liberar', 'escola-anos', 'liberar', 'Liberar o boletim da etapa', 'POST. O portal do aluno mostra só as etapas liberadas.', 1, 31, NULL
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id);
INSERT IGNORE INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`) VALUES (521, 'escola'), (522, 'escola'), (523, 'escola'), (524, 'escola'), (525, 'escola'), (526, 'escola'), (527, 'escola'), (528, 'escola');

-- as permissões: as mesmas da página de hoje com a mesma ação, nível a nível (fora do menu)
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 521, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 521, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 135
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 521);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 522, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 522, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 114
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 522);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 523, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 523, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 105
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 523);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 524, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 524, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 382
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 524);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 525, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 525, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 383
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 525);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 526, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 526, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 91
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 526);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 527, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 527, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 382
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 527);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 528, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 528, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 306
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 528);

-- A versão e a novidade
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.40.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 57, '1.40.0', 1, 40, 0, '2026-11-16', 'A avaliação da escola: a média de cada etapa, a recuperação, a média anual, a frequência e a situação final; o conselho de classe; o fechamento por etapa; o boletim por etapa, na tela, no PDF e no portal. Nada muda no ensino superior.', 1, '2026-11-16 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.40.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.40.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 152, 57, 'Na escola: a avaliação por etapa', 'Numa instalação de escola: as etapas de cada componente (a média, a recuperação, as faltas, o fechamento), a média anual e a situação, o conselho de classe e o boletim por etapa — que a escola libera etapa a etapa para o portal do aluno.', 1, 'administrador,secretaria,professor', 'avaliar-na-escola', '2026-11-16 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 152);
