-- O HISTÓRICO ESCOLAR DA EDUCAÇÃO BÁSICA, Parte B (D-111; docs/HISTORICO_ESCOLAR.md): a matriz curricular por ano.
--   Uma matriz só (a decisão 12 do responsável): a grade de cada ano ganha, numa tabela própria da escola, os campos do
--   histórico — as semanas letivas e a duração da aula (parâmetros da matriz do ano), e, em cada linha da grade, as aulas
--   semanais, o grupo (base nacional comum, parte diversificada; no Médio, formação geral básica com a área, itinerário
--   comum, itinerário formativo com o número) e a ordem. A carga em horas da grade (uni_rh_cursos_disciplinas_grade)
--   continua como está, porque alimenta a frequência. As aulas anuais (semanais × semanas) e as horas (aulas × minutos
--   ÷ 60) são sempre calculadas, nunca gravadas. Copiar de um ano para outro; travar (travada, cada correção pede o
--   motivo, no registro de alterações); a folha da matriz em PDF.
-- COMO DESFAZER: apagar as páginas 630 a 636 (adms_paginas, adms_paginas_modulo, adms_nivacs_pgs) e as tabelas
-- esc_hist_matriz, esc_hist_matriz_item, esc_hist_itinerario e esc_hist_area.

CREATE TABLE IF NOT EXISTS `esc_hist_matriz` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade_id` int(11) NOT NULL COMMENT 'rm_cursos_grades.id (a grade do segmento)',
  `ano` int(11) NOT NULL COMMENT 'o ano letivo',
  `semanas` tinyint(4) NOT NULL DEFAULT 40 COMMENT 'semanas letivas do ano',
  `minutos` tinyint(4) NOT NULL DEFAULT 50 COMMENT 'a duração da aula',
  `fundamento` text DEFAULT NULL COMMENT 'o fundamento legal (texto da folha)',
  `autorizacao` text DEFAULT NULL,
  `periodo` varchar(200) NOT NULL DEFAULT '' COMMENT 'o período e o horário (texto da folha)',
  `data_folha` date DEFAULT NULL COMMENT 'a data impressa na folha (vazia = a de hoje)',
  `situacao` varchar(10) NOT NULL DEFAULT 'rascunho' COMMENT 'rascunho | travada',
  `travada_em` datetime DEFAULT NULL,
  `travada_por` int(11) NOT NULL DEFAULT 0,
  `origem` varchar(20) NOT NULL DEFAULT 'nova' COMMENT 'nova | copia (de outra matriz)',
  `origem_id` int(11) NOT NULL DEFAULT 0,
  `por` int(11) NOT NULL DEFAULT 0,
  `em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grade_ano` (`grade_id`, `ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='A matriz curricular do histórico: a grade de um segmento num ano letivo';

CREATE TABLE IF NOT EXISTS `esc_hist_area` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As áreas de conhecimento (a formação geral básica do Médio)';

CREATE TABLE IF NOT EXISTS `esc_hist_itinerario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matriz_id` int(11) NOT NULL,
  `numero` tinyint(4) NOT NULL COMMENT '0 = o comum; 1, 2… = os formativos',
  `nome` varchar(160) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matriz_numero` (`matriz_id`, `numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Os itinerários da matriz do ano (o Médio)';

CREATE TABLE IF NOT EXISTS `esc_hist_matriz_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matriz_id` int(11) NOT NULL,
  `linha_id` int(11) NOT NULL COMMENT 'uni_rh_cursos_disciplinas_grade.id (a série e a disciplina)',
  `aulas_semanais` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 = fora do histórico',
  `grupo` varchar(14) NOT NULL DEFAULT 'BNC' COMMENT 'BNC | PD | FGB | IT_COMUM | IT_FORMATIVO',
  `area_id` int(11) NOT NULL DEFAULT 0,
  `itinerario` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'o número do itinerário formativo',
  `ordem` int(11) NOT NULL DEFAULT 0 COMMENT 'a ordem na matriz (0 = a da grade)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `matriz_linha` (`matriz_id`, `linha_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As aulas semanais, o grupo e a área de cada linha da grade no ano';

-- as páginas da matriz (só a escola; a ação 130 da Parte A)
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `consulta_grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, x.icone, 1, 1, 1, NOW(), x.grava, 0, 0, NULL, 130 FROM (
  SELECT 630 id, 'Matriz do histórico' menu, 'HistoricoEscolaMatriz' ctl, 'listar' met, 'historico-escola-matriz' mctl, 'listar' mmet, 'Matriz curricular do histórico' nome, 'As matrizes por segmento e ano letivo, com os totais de cada série.' obs, 'fas fa-table' icone, 0 grava
  UNION ALL SELECT 631, 'Criar a matriz do ano', 'HistoricoEscolaMatriz', 'criar', 'historico-escola-matriz', 'criar', 'Criar a matriz do ano', 'POST: vazia ou copiando outra do mesmo nível.', NULL, 1
  UNION ALL SELECT 632, 'Editar a matriz', 'HistoricoEscolaMatriz', 'editar', 'historico-escola-matriz', 'editar', 'A matriz do ano', 'Os parâmetros, os itinerários, a grade com os totais e o registro de alterações.', NULL, 0
  UNION ALL SELECT 633, 'Salvar a matriz', 'HistoricoEscolaMatriz', 'salvar', 'historico-escola-matriz', 'salvar', 'Salvar a matriz', 'POST: a tela inteira; travada, com o motivo.', NULL, 1
  UNION ALL SELECT 634, 'Travar a matriz', 'HistoricoEscolaMatriz', 'travar', 'historico-escola-matriz', 'travar', 'Travar a matriz do ano', 'POST.', NULL, 1
  UNION ALL SELECT 635, 'Folha da matriz', 'HistoricoEscolaMatriz', 'folha', 'historico-escola-matriz', 'folha', 'A folha da matriz em PDF', 'PDF.', NULL, 0
  UNION ALL SELECT 636, 'Salvar a área', 'HistoricoEscolaMatriz', 'salvarArea', 'historico-escola-matriz', 'salvar-area', 'Salvar a área de conhecimento', 'POST.', NULL, 1
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id) ORDER BY x.id;
INSERT INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`)
SELECT p.`id`, 'escola' FROM `adms_paginas` p
 WHERE p.`id` BETWEEN 630 AND 636 AND NOT EXISTS (SELECT 1 FROM `adms_paginas_modulo` m WHERE m.`adms_pagina_id` = p.`id`) ORDER BY p.`id`;
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT IF(d.`adms_niveis_acesso_id` IN (1, 3, 4, 10), 1, 2), p.`id`, d.`dropdown`, IF(p.`id` = 630 AND d.`adms_niveis_acesso_id` IN (1, 3, 4, 10), 1, 2), d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` BETWEEN 630 AND 636
 WHERE d.`adms_pagina_id` = 509
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`;
