-- A escola, a estrutura (Onda 4, Etapa 2; docs/ESCOLA.md, D-47): o ano letivo em etapas (bimestres ou trimestres), as
-- turmas por cima da cadeia matriz → oferta → enturmação (a turma gera as ofertas da série), o aluno na turma (entrar,
-- sair, trocar de turma levando o lançado) e as regras da escola (a frequência). Tudo em tabelas próprias, vazias numa
-- instalação de ensino superior; as páginas são do módulo "escola" (a rota e o menu só existem na escola).

CREATE TABLE IF NOT EXISTS `esc_anos` (
  `ano` int(11) NOT NULL,
  `modelo` varchar(12) NOT NULL COMMENT 'bimestre | trimestre',
  `aberto_em` datetime NOT NULL,
  `aberto_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O ano letivo da escola: o modelo das etapas';

CREATE TABLE IF NOT EXISTS `esc_etapas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ano` int(11) NOT NULL,
  `ordem` tinyint(4) NOT NULL,
  `nome` varchar(40) NOT NULL,
  `inicio` date NOT NULL,
  `fim` date NOT NULL,
  `conselho` date DEFAULT NULL COMMENT 'a data do conselho de classe',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ano_ordem` (`ano`, `ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As etapas do ano letivo (bimestres ou trimestres)';

CREATE TABLE IF NOT EXISTS `esc_turmas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ano` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL COMMENT 'o segmento',
  `grade_id` int(11) NOT NULL COMMENT 'a matriz',
  `modulo_id` int(11) NOT NULL COMMENT 'a série',
  `nome` varchar(30) NOT NULL,
  `turno_id` int(11) DEFAULT NULL,
  `sala` varchar(60) DEFAULT NULL,
  `capacidade` int(11) DEFAULT NULL,
  `polo_id` int(11) DEFAULT NULL COMMENT 'a unidade',
  `situacao` tinyint(4) NOT NULL DEFAULT 1,
  `criado_por` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ano_serie_nome` (`ano`, `modulo_id`, `nome`),
  KEY `curso` (`curso_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As turmas da escola';

CREATE TABLE IF NOT EXISTS `esc_turma_ofertas` (
  `oferta_id` int(11) NOT NULL,
  `turma_id` int(11) NOT NULL,
  PRIMARY KEY (`oferta_id`),
  KEY `turma` (`turma_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As ofertas de cada turma (um componente curricular cada)';

CREATE TABLE IF NOT EXISTS `esc_turma_alunos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `turma_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `rm` varchar(50) NOT NULL,
  `entrada` datetime NOT NULL,
  `saida` datetime DEFAULT NULL,
  `destino_turma_id` int(11) DEFAULT NULL COMMENT 'trocou para esta turma',
  `por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `turma` (`turma_id`),
  KEY `aluno` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O aluno na turma: a entrada, a saída, a troca';

CREATE TABLE IF NOT EXISTS `esc_lancamentos_trazidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `oferta_destino_id` int(11) DEFAULT NULL COMMENT 'a oferta do mesmo componente na turma nova (vazio = o aluno saiu)',
  `oferta_origem_id` int(11) NOT NULL,
  `rm` varchar(50) NOT NULL,
  `notas` longtext NOT NULL COMMENT 'JSON: as atividades com nota (nome, data, peso, componente, nota)',
  `faltas` int(11) NOT NULL DEFAULT 0,
  `compensadas` int(11) NOT NULL DEFAULT 0,
  `em` datetime NOT NULL,
  `por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `destino` (`oferta_destino_id`, `rm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O que foi lançado ao aluno numa turma que ele deixou';

CREATE TABLE IF NOT EXISTS `esc_configuracao` (
  `chave` varchar(60) NOT NULL,
  `valor` varchar(255) NOT NULL,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As regras da escola que diferem do padrão (o padrão mora no código)';

-- as páginas (módulo "escola")
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, x.icone, 1, 1, 1, NOW(), x.grava, 0, NULL, x.acao FROM (
  SELECT 509 id, 'Ano letivo e etapas' menu, 'EscolaAnos' ctl, 'index' met, 'escola-anos' mctl, 'index' mmet, 'Ano letivo e etapas' nome, 'O ano letivo da escola: bimestres ou trimestres, as datas e o conselho de classe.' obs, 'fas fa-calendar-days' icone, 0 grava, 40 acao
  UNION ALL SELECT 510, 'Abrir o ano letivo da escola', 'EscolaAnos', 'abrir', 'escola-anos', 'abrir', 'Abrir o ano letivo da escola', 'POST. O modelo das etapas.', NULL, 1, 31
  UNION ALL SELECT 511, 'Salvar as etapas', 'EscolaAnos', 'salvar', 'escola-anos', 'salvar', 'Salvar as etapas do ano letivo', 'POST. Os nomes e as datas.', NULL, 1, 31
  UNION ALL SELECT 512, 'Turmas', 'Turmas', 'listar', 'turmas', 'listar', 'Turmas', 'As turmas do ano: a série, o turno, a sala, os alunos.', 'fas fa-people-group', 0, 43
  UNION ALL SELECT 513, 'Nova turma', 'Turmas', 'cadastrar', 'turmas', 'cadastrar', 'Nova turma', 'A turma gera as ofertas da série, uma por componente curricular.', NULL, 1, 54
  UNION ALL SELECT 514, 'Editar a turma', 'Turmas', 'editar', 'turmas', 'editar', 'Editar a turma', 'O nome, o turno, a sala e a capacidade.', NULL, 1, 54
  UNION ALL SELECT 515, 'Ver a turma', 'Turmas', 'ver', 'turmas', 'ver', 'Ver a turma', 'Os componentes, os professores e os alunos da turma.', NULL, 0, 43
  UNION ALL SELECT 516, 'Enturmar na turma', 'Turmas', 'enturmar', 'turmas', 'enturmar', 'Enturmar alunos na turma', 'POST, em passos. Os alunos marcados entram em todos os componentes da turma.', NULL, 1, 13
  UNION ALL SELECT 517, 'Tirar da turma', 'Turmas', 'sair', 'turmas', 'sair', 'Tirar o aluno da turma', 'POST.', NULL, 1, 13
  UNION ALL SELECT 518, 'Trocar de turma', 'Turmas', 'trocar', 'turmas', 'trocar', 'Trocar o aluno de turma', 'POST. Na mesma série; o lançado vai junto.', NULL, 1, 13
  UNION ALL SELECT 519, 'Regras da escola', 'EscolaRegras', 'index', 'escola-regras', 'index', 'Regras da escola', 'A frequência mínima e onde ela é medida.', 'fas fa-scale-balanced', 0, 75
  UNION ALL SELECT 520, 'Salvar as regras da escola', 'EscolaRegras', 'salvar', 'escola-regras', 'salvar', 'Salvar as regras da escola', 'POST.', NULL, 1, 79
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id);
INSERT IGNORE INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`) VALUES (509, 'escola'), (510, 'escola'), (511, 'escola'), (512, 'escola'), (513, 'escola'), (514, 'escola'), (515, 'escola'), (516, 'escola'), (517, 'escola'), (518, 'escola'), (519, 'escola'), (520, 'escola');

-- as permissões: as mesmas da página de hoje com a mesma ação, nível a nível; no menu, só as telas de lista (no lugar
-- do menu da página de hoje, para quem a tem no menu)
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 509, d.`dropdown`, d.`lib_menu`, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 509, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 305
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 509);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 510, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 510, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 306
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 510);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 511, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 511, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 306
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 511);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 512, d.`dropdown`, d.`lib_menu`, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 512, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 91
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 512);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 513, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 513, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 240
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 513);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 514, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 514, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 240
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 514);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 515, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 515, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 91
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 515);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 516, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 516, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 381
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 516);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 517, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 517, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 381
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 517);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 518, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 518, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 381
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 518);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 519, d.`dropdown`, d.`lib_menu`, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 519, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 364
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 519);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 520, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 520, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 365
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 520);

-- os Primeiros passos: as fases da escola (módulo "escola") e as do ensino superior que só valem lá (módulo "superior");
-- a instituição, as regras de nota e o e-mail continuam nos dois
UPDATE `adms_fases` SET `modulo` = 'superior' WHERE `codigo` IN ('ano_letivo', 'curso', 'oferta', 'alunos', 'enturmar') AND `modulo` IS NULL;
INSERT INTO `adms_fases` (`codigo`, `ordem`, `titulo`, `texto`, `feito`, `convite`, `rota`, `rotulo_rota`, `roteiro`, `condicao`, `depende_de`, `pode_pular`, `modulo`, `situacao`)
SELECT x.* FROM (
  SELECT 'esc_ano' codigo, 40 ordem, 'O ano letivo e as etapas' titulo, 'O ano letivo, dividido em quatro bimestres (o padrão) ou três trimestres, com as datas de cada etapa e do conselho de classe.' texto, 'Você já abriu o ano letivo com as etapas.' feito, 'Agora vamos abrir o ano letivo, com as etapas.' convite, 'escola-anos/index' rota, 'Abrir o ano letivo e as etapas' rotulo_rota, 'fase-escola-ano' roteiro, 'SELECT COUNT(*) FROM esc_etapas' condicao, NULL depende_de, 1 pode_pular, 'escola' modulo, 1 situacao
  UNION ALL SELECT 'esc_series', 50, 'Os segmentos, as séries e os componentes', 'Os segmentos (o Ensino Fundamental, o Ensino Médio), a matriz de cada um com as séries, e os componentes curriculares de cada série, com a carga horária do ano.', 'Você já montou os segmentos, as séries e os componentes.', 'Agora vamos montar os segmentos, as séries e os componentes curriculares.', 'cursos/listar', 'Abrir os segmentos', 'fase-escola-series', 'SELECT COUNT(*) FROM uni_rh_cursos_disciplinas_grade', NULL, 1, 'escola', 1
  UNION ALL SELECT 'esc_turmas', 60, 'As turmas', 'As turmas de cada série no ano letivo: o nome, o turno, a sala e a capacidade. Cada turma recebe uma oferta por componente curricular.', 'Você já criou as turmas.', 'Agora vamos criar as turmas.', 'turmas/listar', 'Abrir as turmas', 'fase-escola-turmas', 'SELECT COUNT(*) FROM esc_turmas', 'esc_ano,esc_series', 1, 'escola', 1
  UNION ALL SELECT 'esc_professores', 65, 'Os professores', 'O professor de cada componente curricular de cada turma.', 'Você já indicou os professores.', 'Agora vamos indicar o professor de cada componente.', 'turmas/listar', 'Abrir as turmas', 'fase-escola-professores', 'SELECT COUNT(*) FROM rm_professores_mat_liga l INNER JOIN esc_turma_ofertas o ON o.oferta_id = l.rm_prof_mat_id WHERE l.situacao = 1', 'esc_turmas', 1, 'escola', 1
  UNION ALL SELECT 'esc_alunos', 70, 'Os alunos', 'Os alunos e o vínculo de cada um com o segmento (pela tela ou por planilha).', 'Você já cadastrou os alunos.', 'Agora vamos trazer os alunos.', 'academicos/listar', 'Abrir os alunos', 'fase-escola-alunos', 'SELECT COUNT(*) FROM rm_matriculas_curso', 'esc_series', 1, 'escola', 1
  UNION ALL SELECT 'esc_enturmar', 80, 'Enturmar', 'Pôr os alunos nas turmas: cada aluno entra em todos os componentes curriculares da turma, de uma vez.', 'Você já enturmou os alunos. Os primeiros passos estão completos.', 'Agora vamos pôr os alunos nas turmas.', 'turmas/listar', 'Abrir as turmas', 'fase-escola-enturmar', 'SELECT COUNT(*) FROM esc_turma_alunos WHERE saida IS NULL', 'esc_turmas,esc_alunos', 1, 'escola', 1
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_fases` f WHERE f.`codigo` = x.codigo);

-- A versão e a novidade
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.39.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 56, '1.39.0', 1, 39, 0, '2026-11-16', 'A escola: o ano letivo em bimestres ou trimestres, as turmas (cada uma com os componentes curriculares da série), o aluno na turma — entrar, sair, trocar de turma levando as notas e as faltas — e as regras da escola. Nada muda no ensino superior.', 1, '2026-11-16 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.39.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.39.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 151, 56, 'Na escola: turmas e etapas', 'Numa instalação de escola: Ensino → Ano letivo e etapas (bimestres ou trimestres) e Ensino → Turmas. Cada turma tem os componentes curriculares da série; o aluno entra na turma e fica em todos eles, de uma vez.', 1, 'administrador,secretaria', 'organizar-a-escola', '2026-11-16 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 151);
