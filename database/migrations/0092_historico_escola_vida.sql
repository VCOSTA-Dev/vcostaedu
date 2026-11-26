-- O HISTÓRICO ESCOLAR DA EDUCAÇÃO BÁSICA, Parte C (D-112; docs/HISTORICO_ESCOLAR.md): a vida escolar.
--   Uma linha por série cursada, só quando a secretaria CONFIRMA a proposta (a série que o sistema tira das turmas do
--   aluno) ou DIGITA uma série (um ano cursado em outra escola). Enquanto não é confirmada, a série é proposta na hora
--   da leitura. A média do sistema NUNCA é copiada para cá: é lida na hora, pela função única da plataforma; aqui fica só
--   a média digitada (o ano de fora) ou a sobreposta (com o registro: quem, quando, a do sistema e a nova).
-- COMO DESFAZER: apagar as páginas 640 a 646 (adms_paginas, adms_paginas_modulo, adms_nivacs_pgs) e as tabelas
-- esc_hist_vida_serie e esc_hist_vida_nota.

CREATE TABLE IF NOT EXISTS `esc_hist_vida_serie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL COMMENT 'col_usuarios.id',
  `nivel` varchar(2) NOT NULL COMMENT 'EF | EM',
  `serie` tinyint(4) NOT NULL COMMENT '1 a 9 (EF), 1 a 3 (EM)',
  `ano` int(11) NOT NULL,
  `escola_id` int(11) NOT NULL DEFAULT 0 COMMENT 'esc_hist_escola.id (0 = sem escola: aviso)',
  `resultado` varchar(12) NOT NULL DEFAULT '' COMMENT 'aprovado | retido | transferido | em_curso | vazio = a confirmar',
  `turma_id` int(11) NOT NULL DEFAULT 0 COMMENT 'esc_turmas.id (0 = série digitada)',
  `origem` varchar(10) NOT NULL DEFAULT 'sistema' COMMENT 'sistema = proposta confirmada | digitado',
  `religioso` varchar(5) NOT NULL DEFAULT '' COMMENT 'o Ensino Religioso digitado: nota, F, NF ou -',
  `conselho` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = promovido pelo conselho de classe (a observação)',
  `obs_chaves` varchar(500) NOT NULL DEFAULT '' COMMENT 'as observações selecionáveis marcadas, separadas por vírgula',
  `carga_total` int(11) NOT NULL DEFAULT 0 COMMENT 'a carga total da série digitada (0 = a da matriz)',
  `itinerario` tinyint(4) NOT NULL DEFAULT 0,
  `itinerario_confirmado` tinyint(1) NOT NULL DEFAULT 0,
  `data_saida` date DEFAULT NULL,
  `por` int(11) NOT NULL DEFAULT 0,
  `em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `aluno_serie` (`aluno_id`, `nivel`, `ano`, `serie`),
  KEY `turma` (`turma_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='A vida escolar: as séries confirmadas ou digitadas';

CREATE TABLE IF NOT EXISTS `esc_hist_vida_nota` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vida_serie_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL COMMENT 'esc_hist_disciplina.id',
  `media` varchar(5) NOT NULL COMMENT '"7,5", F, NF ou -',
  `aulas_anuais` int(11) NOT NULL DEFAULT 0 COMMENT 'a carga digitada (ano de fora; 0 = a da matriz)',
  `origem` varchar(10) NOT NULL COMMENT 'digitado | sobreposto',
  `media_sistema` varchar(8) NOT NULL DEFAULT '' COMMENT 'a média do sistema quando foi sobreposta (só registro)',
  `por` int(11) NOT NULL DEFAULT 0,
  `em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `serie_disciplina` (`vida_serie_id`, `disciplina_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='A média digitada ou sobreposta de uma disciplina numa série';

-- as páginas da vida escolar (só a escola; a ação 130); a busca no menu Educacional (o de Alunos)
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `consulta_grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, x.icone, 1, 1, 1, NOW(), x.grava, 0, 0, NULL, 130 FROM (
  SELECT 640 id, 'Histórico escolar' menu, 'HistoricoEscolaVida' ctl, 'buscar' met, 'historico-escola-vida' mctl, 'buscar' mmet, 'Histórico escolar: a busca' nome, 'Os alunos ativos e os ex-alunos, por nome ou RM, com a situação real.' obs, 'fas fa-scroll' icone, 0 grava
  UNION ALL SELECT 641, 'Vida escolar do aluno', 'HistoricoEscolaVida', 'aluno', 'historico-escola-vida', 'aluno', 'A vida escolar do aluno', 'As séries (propostas, confirmadas, digitadas), o registro.', NULL, 0
  UNION ALL SELECT 642, 'Confirmar a série', 'HistoricoEscolaVida', 'confirmar', 'historico-escola-vida', 'confirmar', 'Confirmar a série proposta', 'POST.', NULL, 1
  UNION ALL SELECT 643, 'Salvar a série', 'HistoricoEscolaVida', 'salvarSerie', 'historico-escola-vida', 'salvar-serie', 'Salvar a série (editar ou digitar)', 'POST.', NULL, 1
  UNION ALL SELECT 644, 'Tirar a série', 'HistoricoEscolaVida', 'tirar', 'historico-escola-vida', 'tirar', 'Tirar a série da vida escolar', 'POST: a confirmada volta a ser proposta; a digitada sai com as notas.', NULL, 1
  UNION ALL SELECT 645, 'Notas da série', 'HistoricoEscolaVida', 'notas', 'historico-escola-vida', 'notas', 'As notas da série', 'A média do sistema, a digitada e a sobreposta.', NULL, 0
  UNION ALL SELECT 646, 'Salvar as notas da série', 'HistoricoEscolaVida', 'salvarNotas', 'historico-escola-vida', 'salvar-notas', 'Salvar as notas da série', 'POST.', NULL, 1
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id) ORDER BY x.id;
INSERT INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`)
SELECT p.`id`, 'escola' FROM `adms_paginas` p
 WHERE p.`id` BETWEEN 640 AND 646 AND NOT EXISTS (SELECT 1 FROM `adms_paginas_modulo` m WHERE m.`adms_pagina_id` = p.`id`) ORDER BY p.`id`;
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT IF(d.`adms_niveis_acesso_id` IN (1, 3, 4, 10), 1, 2), p.`id`, d.`dropdown`, IF(p.`id` = 640 AND d.`adms_niveis_acesso_id` IN (1, 3, 4, 10), 1, 2), d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` BETWEEN 640 AND 646
 WHERE d.`adms_pagina_id` = 46
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`;
