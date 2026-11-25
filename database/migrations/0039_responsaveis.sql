-- Os responsáveis (Onda 7, Etapa 2; docs/RESPONSAVEIS.md, DECISIONS D-62). Só na escola: a pessoa responsável, com
-- cadastro próprio (pode ser responsável por vários alunos); o parentesco com cada aluno; a designação por aluno e por
-- ano letivo — o responsável financeiro (um só), o acadêmico titular (um só) e os acessos acadêmicos adicionais (com o
-- motivo e a referência do termo); os responsáveis para contato (na ficha do aluno, sem acesso ao portal); a
-- auditoria de cada designação, liberação e encerramento. As tabelas ficam vazias numa instalação de ensino superior;
-- as páginas são do módulo "escola".
--
-- A regra "um financeiro só" é do banco, sem gatilho: a coluna calculada `vigente_unica` vale o papel enquanto a
-- designação do financeiro ou do titular está aberta (sem fim) e NULL no resto; o índice único (aluno, ano,
-- vigente_unica) recusa a segunda aberta. A mesma pessoa não tem duas designações abertas do mesmo papel no mesmo ano.

CREATE TABLE IF NOT EXISTS `esc_responsaveis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `email` varchar(190) DEFAULT NULL COMMENT 'identificador de entrada no portal (ou o CPF)',
  `celular` varchar(20) DEFAULT NULL COMMENT 'só os dígitos, com o DDD',
  `cpf` char(11) DEFAULT NULL COMMENT 'opcional; só os dígitos',
  `senha` varchar(255) DEFAULT NULL COMMENT 'o resumo da senha; vazio até o primeiro acesso pelo convite',
  `bloqueado` tinyint(1) NOT NULL DEFAULT 0,
  `bloqueado_motivo` varchar(255) DEFAULT NULL,
  `bloqueado_em` datetime DEFAULT NULL,
  `bloqueado_por` int(11) DEFAULT NULL,
  `ativado_em` datetime DEFAULT NULL COMMENT 'quando definiu a senha pelo convite',
  `ultimo_acesso` datetime DEFAULT NULL,
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL,
  `alterado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `cpf` (`cpf`),
  KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='A pessoa responsável (escola)';

CREATE TABLE IF NOT EXISTS `esc_responsavel_alunos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `responsavel_id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL COMMENT 'col_usuarios.id',
  `parentesco` varchar(40) NOT NULL,
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pessoa_aluno` (`responsavel_id`, `aluno_id`),
  KEY `aluno` (`aluno_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O parentesco da pessoa responsável com cada aluno';

CREATE TABLE IF NOT EXISTS `esc_responsavel_designacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL COMMENT 'col_usuarios.id',
  `ano` int(11) NOT NULL COMMENT 'o ano letivo',
  `responsavel_id` int(11) NOT NULL,
  `papel` varchar(12) NOT NULL COMMENT 'financeiro | titular | adicional',
  `inicio` date NOT NULL,
  `fim` date DEFAULT NULL COMMENT 'vazio = em vigor; com data, o papel acabou nela (a troca, o encerramento)',
  `motivo` varchar(255) DEFAULT NULL COMMENT 'o adicional: obrigatório',
  `referencia` varchar(190) DEFAULT NULL COMMENT 'o adicional: o termo ou documento que apoia a liberação',
  `origem` varchar(12) NOT NULL DEFAULT 'tela' COMMENT 'tela | planilha | api',
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL,
  `motivo_fim` varchar(255) DEFAULT NULL,
  `encerrado_por` int(11) DEFAULT NULL,
  `encerrado_em` datetime DEFAULT NULL,
  `vigente_unica` varchar(12) GENERATED ALWAYS AS (IF(`fim` IS NULL AND `papel` IN ('financeiro', 'titular'), `papel`, NULL)) STORED,
  `aberta_pessoa` varchar(12) GENERATED ALWAYS AS (IF(`fim` IS NULL, `papel`, NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `um_financeiro_um_titular` (`aluno_id`, `ano`, `vigente_unica`),
  UNIQUE KEY `uma_aberta_por_pessoa` (`aluno_id`, `ano`, `responsavel_id`, `aberta_pessoa`),
  KEY `pessoa` (`responsavel_id`, `ano`),
  KEY `aluno_ano` (`aluno_id`, `ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O papel de cada responsável em cada aluno, por ano letivo';

CREATE TABLE IF NOT EXISTS `esc_responsavel_contatos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `parentesco` varchar(40) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `observacao` varchar(255) DEFAULT NULL,
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `aluno` (`aluno_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Os responsáveis para contato: na ficha do aluno, sem acesso ao portal';

CREATE TABLE IF NOT EXISTS `esc_responsavel_auditoria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `em` datetime NOT NULL,
  `por` int(11) DEFAULT NULL COMMENT 'o usuário do painel (vazio: o próprio responsável ou o sistema)',
  `origem` varchar(12) NOT NULL COMMENT 'tela | planilha | api | portal',
  `acao` varchar(40) NOT NULL,
  `responsavel_id` int(11) DEFAULT NULL,
  `aluno_id` int(11) DEFAULT NULL,
  `ano` int(11) DEFAULT NULL,
  `antes` text DEFAULT NULL COMMENT 'JSON: o que havia antes',
  `depois` text DEFAULT NULL COMMENT 'JSON: o que ficou',
  `motivo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pessoa` (`responsavel_id`),
  KEY `aluno` (`aluno_id`, `ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quem designou, liberou ou encerrou, quando, o que havia antes e o motivo';

-- as ações (os perfis de acesso)
INSERT IGNORE INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`) VALUES
(101, 'ver_os_responsaveis_101', 'Ver os responsáveis dos alunos', 'Alunos', 'secretaria', 15),
(102, 'cadastrar_e_designar_os_responsaveis_102', 'Cadastrar e designar os responsáveis', 'Alunos', 'secretaria', 16),
(103, 'liberar_acesso_academico_adicional_103', 'Liberar o acesso acadêmico adicional', 'Alunos', 'secretaria', 17);

-- as páginas (módulo "escola")
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, 'Responsaveis', x.met, 'responsaveis', x.mmet, x.nome, x.obs, 2, x.icone, 1, 1, 1, NOW(), x.grava, 0, NULL, x.acao FROM (
  SELECT 532 id, 'Responsáveis' menu, 'listar' met, 'listar' mmet, 'Responsáveis' nome, 'As pessoas responsáveis: os alunos, o papel por ano e a situação do acesso.' obs, 'fas fa-people-roof' icone, 0 grava, 101 acao
  UNION ALL SELECT 533, 'Ver o responsável', 'ver', 'ver', 'Ver o responsável', 'O cadastro, os alunos, os papéis e o acesso.', NULL, 0, 101
  UNION ALL SELECT 534, 'Novo responsável', 'cadastrar', 'cadastrar', 'Novo responsável', 'Procura por e-mail e por CPF antes de criar: a mesma pessoa não nasce duas vezes.', NULL, 1, 102
  UNION ALL SELECT 535, 'Editar o responsável', 'editar', 'editar', 'Editar o responsável', 'O nome, o e-mail, o celular e o CPF.', NULL, 1, 102
  UNION ALL SELECT 536, 'Responsáveis do aluno', 'aluno', 'aluno', 'Responsáveis do aluno', 'Por ano letivo: o financeiro, o titular, os acessos adicionais e os contatos.', NULL, 0, 101
  UNION ALL SELECT 537, 'Designar o responsável', 'designar', 'designar', 'Designar o responsável do ano', 'POST. O financeiro ou o titular; a troca encerra o anterior com data e motivo.', NULL, 1, 102
  UNION ALL SELECT 538, 'Encerrar o papel', 'encerrar', 'encerrar', 'Encerrar o papel do responsável', 'POST. Com a data e o motivo.', NULL, 1, 102
  UNION ALL SELECT 539, 'Liberar o acesso adicional', 'liberar', 'liberar', 'Liberar o acesso acadêmico adicional', 'POST. Com o motivo e a referência do termo.', NULL, 1, 103
  UNION ALL SELECT 540, 'Responsável para contato', 'contato', 'contato', 'Responsável para contato', 'POST. Cadastrar ou retirar.', NULL, 1, 102
  UNION ALL SELECT 541, 'Copiar do ano anterior', 'copiar', 'copiar', 'Copiar os responsáveis do ano anterior', 'POST, depois da conferência.', NULL, 1, 102
  UNION ALL SELECT 542, 'Procurar o responsável', 'procurar', 'procurar', 'Procurar o responsável', 'Por e-mail ou CPF.', NULL, 0, 101
  UNION ALL SELECT 543, 'Importar responsáveis', 'importar', 'importar', 'Importar responsáveis de planilha', 'A conferência linha a linha; tudo ou nada.', NULL, 1, 102
  UNION ALL SELECT 544, 'Modelo da planilha de responsáveis', 'modelo', 'modelo', 'Modelo da planilha de responsáveis', 'O arquivo de exemplo.', NULL, 0, 101
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id);
INSERT IGNORE INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`) VALUES (532, 'escola'), (533, 'escola'), (534, 'escola'), (535, 'escola'), (536, 'escola'), (537, 'escola'), (538, 'escola'), (539, 'escola'), (540, 'escola'), (541, 'escola'), (542, 'escola'), (543, 'escola'), (544, 'escola');

-- as permissões: as de consultar alunos (a lista, no menu ao lado dela) e as de cadastrar e editar alunos, nível a nível
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, IF(p.`id` = 532, d.`ordem` + 1, p.`id`), d.`dropdown`, IF(p.`id` = 532, d.`lib_menu`, 2), d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` IN (532, 533, 536, 542, 544)
 WHERE d.`adms_pagina_id` = 46
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, p.`id`, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` IN (534, 535, 537, 538, 539, 540, 541, 543)
 WHERE d.`adms_pagina_id` = 47
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`);

-- A versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.52.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 69, '1.52.0', 1, 52, 0, '2026-11-18', 'Os responsáveis na escola: o cadastro da pessoa e o papel de cada uma em cada aluno, por ano letivo.', 1, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.52.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.52.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 172, 69, 'Os responsáveis dos alunos', 'Na escola, em Responsáveis e na ficha do aluno: o cadastro da pessoa responsável (que pode ser de vários alunos) e, para cada aluno e cada ano letivo, o responsável financeiro (um só), o acadêmico titular e os acessos acadêmicos adicionais, com o motivo. Também os responsáveis para contato, a cópia do ano anterior e a importação por planilha.', 1, 'administrador,secretaria', 'cadastrar-um-responsavel', '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 172);
