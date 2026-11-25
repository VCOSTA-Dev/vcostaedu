-- =============================================================================
-- 0001 — ESTRUTURA DE BASE (88 tabelas), versão 1.18.4 do sistema de origem.
-- Gerada do database/schema.sql da origem sem a trava pelo nome do banco e sem os
-- DROP (o instalador só roda sobre banco vazio). Os gatilhos estão na 0003.
-- Migration aplicada é imutável: mudança de estrutura é migration NOVA.
-- =============================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Tabelas que saíram na E2a, na E2b, na E4 e na E5 (sem código que as leia). Apagadas se
-- existirem, para que reaplicar este script num banco antigo não as deixe.

-- adms_confs_emails
CREATE TABLE `adms_confs_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `host` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `senhas` varchar(255) NOT NULL,
  `smtpsecure` varchar(255) NOT NULL,
  `porta` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_cors
CREATE TABLE `adms_cors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `cor` varchar(255) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_dia_semana
CREATE TABLE `adms_dia_semana` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dia` varchar(255) NOT NULL,
  `dia_completo` varchar(255) NOT NULL,
  `abreviacao` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_empresas
CREATE TABLE `adms_empresas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `razao_social` text NOT NULL,
  `cnpj` varchar(255) NOT NULL,
  `inscricao_estadual` varchar(255) NOT NULL,
  `site` text DEFAULT NULL,
  `situacao` int(11) NOT NULL,
  `observacao` longtext DEFAULT NULL,
  `instituicao` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'E6a: 1 = a instituição do sistema (dados do cabeçalho e do histórico)',
  `codigo_emec` varchar(20) DEFAULT NULL COMMENT 'E6a',
  `cep` varchar(9) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(255) DEFAULT NULL,
  `bairro` varchar(255) DEFAULT NULL,
  `cidade` varchar(255) DEFAULT NULL,
  `uf` varchar(2) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL COMMENT 'E6a: arquivo em assets/instituicao/ (PNG recodificado)',
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `telefone` varchar(30) DEFAULT NULL COMMENT 'E1a: telefone institucional (rodapé dos documentos)',
  `email` varchar(150) DEFAULT NULL COMMENT 'E1a: e-mail institucional',
  `cabecalho_1` varchar(200) DEFAULT NULL COMMENT 'E1a: linha 1 do cabeçalho institucional dos documentos',
  `cabecalho_2` varchar(200) DEFAULT NULL COMMENT 'E1a: linha 2 do cabeçalho institucional dos documentos',
  `cabecalho_3` varchar(200) DEFAULT NULL COMMENT 'E1a: linha 3 do cabeçalho institucional dos documentos',
  `cabecalho_4` varchar(200) DEFAULT NULL COMMENT 'E1a: linha 4 do cabeçalho institucional dos documentos',
  `cabecalho_5` varchar(200) DEFAULT NULL COMMENT 'E1a: linha 5 do cabeçalho institucional dos documentos',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_empresas_contato
CREATE TABLE `adms_empresas_contato` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` int(11) NOT NULL,
  `codfilial` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `telefone1` varchar(255) DEFAULT NULL,
  `telefone2` varchar(255) DEFAULT NULL,
  `telefone3` varchar(255) DEFAULT NULL,
  `email1` varchar(255) DEFAULT NULL,
  `email2` varchar(255) DEFAULT NULL,
  `cep` varchar(255) NOT NULL,
  `rua` text NOT NULL,
  `numero` varchar(255) DEFAULT NULL,
  `complemento` longtext DEFAULT NULL,
  `bairro` varchar(255) NOT NULL,
  `cidade` varchar(255) NOT NULL,
  `estado` varchar(255) NOT NULL,
  `ordem` int(11) NOT NULL,
  `observacao` longtext DEFAULT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `empresa_id` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_estado_civil
CREATE TABLE `adms_estado_civil` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `situacao` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_generos
CREATE TABLE `adms_generos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `situacao` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_grps_pgs
CREATE TABLE `adms_grps_pgs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `ordem` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_logs
CREATE TABLE `adms_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `usuario_ip` varchar(255) NOT NULL,
  `codigo` int(11) NOT NULL,
  `pagina_id` int(11) NOT NULL,
  `descricao` longtext NOT NULL,
  `acao` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `rel_pagina_usuario` (`pagina_id`,`usuario_id`,`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_menus
CREATE TABLE `adms_menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `icone` varchar(255) NOT NULL,
  `ordem` int(11) NOT NULL,
  `adms_sit_id` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_nivacs_pgs
CREATE TABLE `adms_nivacs_pgs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permissao` int(11) NOT NULL,
  `ordem` int(11) NOT NULL,
  `dropdown` int(11) NOT NULL DEFAULT 2,
  `lib_menu` int(11) NOT NULL DEFAULT 2,
  `adms_menu_id` int(11) NOT NULL DEFAULT 4,
  `adms_niveis_acesso_id` int(11) NOT NULL,
  `adms_pagina_id` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `permissao` (`permissao`),
  KEY `dropdown` (`dropdown`),
  KEY `lib_menu` (`lib_menu`),
  KEY `adms_menu_id` (`adms_menu_id`),
  KEY `adms_niveis_acesso_id` (`adms_niveis_acesso_id`),
  KEY `adms_pagina_id` (`adms_pagina_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_niveis_acessos
CREATE TABLE `adms_niveis_acessos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `ordem` int(11) NOT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_novidades
CREATE TABLE `adms_novidades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adms_versao_id` int(11) NOT NULL,
  `titulo` varchar(160) NOT NULL,
  `texto` text NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 1,
  `perfis` varchar(100) NOT NULL DEFAULT 'administrador,secretaria,professor' COMMENT 'E6c: a quem interessa (administrador, secretaria, professor, aluno)',
  `guia` varchar(80) DEFAULT NULL COMMENT 'E6c: slug do guia ligado (docs/guia/tarefas/<slug>.md)',
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_novidade_versao` (`adms_versao_id`),
  CONSTRAINT `fk_novidade_versao` FOREIGN KEY (`adms_versao_id`) REFERENCES `adms_versoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

-- adms_novidades_vistas
CREATE TABLE `adms_novidades_vistas` (
  `adms_versao_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`adms_versao_id`,`usuario_id`),
  KEY `fk_vista_usuario` (`usuario_id`),
  CONSTRAINT `fk_vista_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `adms_usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_vista_versao` FOREIGN KEY (`adms_versao_id`) REFERENCES `adms_versoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

-- col_novidades_vistas (E6c, VCOSTAEDU): o aviso de novidades já visto pelo ALUNO (o portal tem
-- outro cadastro de usuários; a equipe usa adms_novidades_vistas). Um registro por aluno e versão.
CREATE TABLE `col_novidades_vistas` (
  `adms_versao_id` int(11) NOT NULL,
  `col_usuario_id` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`adms_versao_id`,`col_usuario_id`),
  KEY `fk_col_vista_usuario` (`col_usuario_id`),
  CONSTRAINT `fk_col_vista_usuario` FOREIGN KEY (`col_usuario_id`) REFERENCES `col_usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_col_vista_versao` FOREIGN KEY (`adms_versao_id`) REFERENCES `adms_versoes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;
-- seg_tentativas (E7, VCOSTAEDU): contagem de tentativas por escopo (login, esqueci a senha, verificação)
-- e por chave (identificador digitado ou IP), com espera crescente (D-73). Guarda só o sha256 da chave e
-- uma dica mascarada; nunca senha, token ou identificador inteiro. Os bloqueios tentados vão para
-- col_tentativas_acesso (que vinha do sistema de origem sem leitor).
CREATE TABLE `seg_tentativas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `escopo` varchar(20) NOT NULL COMMENT 'login_painel | login_portal | esqueci_painel | esqueci_portal | verificacao',
  `tipo` varchar(2) NOT NULL COMMENT 'id (identificador digitado) | ip',
  `chave` char(64) NOT NULL COMMENT 'sha256 do identificador (minúsculo) ou do IP',
  `dica` varchar(40) DEFAULT NULL COMMENT 'mascarada, para a tela do administrador',
  `falhas` int(11) NOT NULL DEFAULT 0,
  `primeira` datetime NOT NULL,
  `ultima` datetime NOT NULL,
  `bloqueado_ate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `escopo_tipo_chave` (`escopo`, `tipo`, `chave`),
  KEY `bloqueado_ate` (`bloqueado_ate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;
-- adms_paginas
CREATE TABLE `adms_paginas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_pagina` varchar(255) NOT NULL,
  `controller` varchar(255) NOT NULL,
  `metodo` varchar(255) NOT NULL,
  `menu_controller` varchar(255) NOT NULL,
  `menu_metodo` varchar(255) NOT NULL,
  `nome_pagina` varchar(255) NOT NULL,
  `obs` text DEFAULT NULL,
  `lib_pub` int(11) NOT NULL DEFAULT 2,
  `icone` varchar(255) DEFAULT NULL,
  `adms_grps_pg_id` int(11) NOT NULL,
  `adms_tps_pg_id` int(11) NOT NULL,
  `adms_sits_pg_id` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `menu_controller` (`menu_controller`),
  KEY `menu_metodo` (`menu_metodo`),
  KEY `controller` (`controller`),
  KEY `metodo` (`metodo`),
  KEY `lib_pub` (`lib_pub`),
  KEY `adms_grps_pg_id` (`adms_grps_pg_id`),
  KEY `adms_tps_pg_id` (`adms_tps_pg_id`),
  KEY `adms_sits_pg_id` (`adms_sits_pg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_parametros
CREATE TABLE `adms_parametros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave` varchar(60) NOT NULL,
  `valor` varchar(255) NOT NULL DEFAULT '',
  `descricao` varchar(255) NOT NULL,
  `grupo` varchar(40) NOT NULL DEFAULT 'Geral',
  `editavel` tinyint(1) NOT NULL DEFAULT 1,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_parametro_chave` (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_periodos_letivos
CREATE TABLE `adms_periodos_letivos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ano` int(11) NOT NULL,
  `situacaodisciplina` int(11) NOT NULL,
  `situacaomodelo` int(11) NOT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ano` (`ano`),
  KEY `situacaodisciplina` (`situacaodisciplina`),
  KEY `situacaomodelo` (`situacaomodelo`),
  KEY `situacao` (`situacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_raca
CREATE TABLE `adms_raca` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `observacao` longtext DEFAULT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_sits_pgs
CREATE TABLE `adms_sits_pgs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `cor_id` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_sits_usuarios
CREATE TABLE `adms_sits_usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `adms_cor_id` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_tps_pgs
CREATE TABLE `adms_tps_pgs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(40) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `obs` text NOT NULL,
  `ordem` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_uf
CREATE TABLE `adms_uf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `nome_completo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_usuarios
CREATE TABLE `adms_usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `genero_id` int(11) NOT NULL,
  `apelido` varchar(255) DEFAULT NULL,
  `usuario` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telefone` varchar(255) DEFAULT NULL,
  `celular` varchar(255) DEFAULT NULL,
  `recuperar_senha` varchar(255) DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `titulacao_id` int(11) DEFAULT NULL COMMENT 'E6a: uni_rh_titulacao.id (professor)',
  `adms_niveis_acesso_id` int(11) NOT NULL,
  `adms_sits_usuario_id` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `genero_id` (`genero_id`),
  KEY `adms_niveis_acesso_id` (`adms_niveis_acesso_id`),
  KEY `usuario_cre` (`usuario_cre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_usuarios_informacoes
CREATE TABLE `adms_usuarios_informacoes` (
  `user_id` int(11) NOT NULL,
  `data_nascimento` varchar(255) DEFAULT NULL,
  `rg` varchar(255) DEFAULT NULL,
  `cpf` varchar(255) DEFAULT NULL,
  `estado_civil` int(11) DEFAULT NULL,
  `cep` varchar(255) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `numero` varchar(255) DEFAULT NULL,
  `complemento` varchar(255) DEFAULT NULL,
  `bairro` varchar(255) DEFAULT NULL,
  `cidade` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- adms_versoes
CREATE TABLE `adms_versoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(20) NOT NULL COMMENT 'o semver por extenso ? e o selo de cache dos assets',
  `major` int(11) NOT NULL DEFAULT 0,
  `minor` int(11) NOT NULL DEFAULT 0,
  `patch` int(11) NOT NULL DEFAULT 0,
  `data_versao` date NOT NULL,
  `resumo` varchar(255) DEFAULT NULL COMMENT 'uma frase sobre a entrega',
  `situacao` int(11) NOT NULL DEFAULT 1 COMMENT '1 = vigente, 2 = anterior',
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_versao_numero` (`numero`),
  KEY `idx_versao_ordem` (`major`,`minor`,`patch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
;

-- col_logs
CREATE TABLE `col_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_col` int(11) DEFAULT NULL,
  `usuario_ip` varchar(255) DEFAULT NULL,
  `observacao` longtext DEFAULT NULL,
  `descricao` longtext DEFAULT NULL,
  `codigo` int(11) DEFAULT NULL,
  `pagina_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_col` (`usuario_col`),
  KEY `codigo` (`codigo`),
  KEY `pagina_id` (`pagina_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- col_menus
CREATE TABLE `col_menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `icone` varchar(255) NOT NULL,
  `ordem` int(11) NOT NULL,
  `col_sit_id` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- col_nivacs_pgs
CREATE TABLE `col_nivacs_pgs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permissao` int(11) NOT NULL,
  `ordem` int(11) NOT NULL,
  `dropdown` int(11) NOT NULL DEFAULT 2,
  `lib_menu` int(11) NOT NULL DEFAULT 2,
  `adms_menu_id` int(11) NOT NULL DEFAULT 4,
  `adms_niveis_acesso_id` int(11) NOT NULL,
  `adms_pagina_id` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `permissao` (`permissao`),
  KEY `dropdown` (`dropdown`),
  KEY `lib_menu` (`lib_menu`),
  KEY `adms_menu_id` (`adms_menu_id`),
  KEY `adms_niveis_acesso_id` (`adms_niveis_acesso_id`),
  KEY `adms_pagina_id` (`adms_pagina_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- col_niveis_acessos
CREATE TABLE `col_niveis_acessos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `ordem` int(11) NOT NULL,
  `view` varchar(255) DEFAULT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) DEFAULT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ordem` (`ordem`),
  KEY `view` (`view`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- col_paginas
CREATE TABLE `col_paginas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_pagina` varchar(255) NOT NULL,
  `controller` varchar(255) NOT NULL,
  `metodo` varchar(255) NOT NULL,
  `menu_controller` varchar(255) NOT NULL,
  `menu_metodo` varchar(255) NOT NULL,
  `nome_pagina` varchar(255) NOT NULL,
  `obs` text DEFAULT NULL,
  `lib_pub` int(11) NOT NULL DEFAULT 2,
  `icone` varchar(255) DEFAULT NULL,
  `adms_grps_pg_id` int(11) NOT NULL,
  `adms_tps_pg_id` int(11) NOT NULL,
  `adms_sits_pg_id` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `menu_pagina` (`menu_pagina`),
  KEY `controller` (`controller`),
  KEY `metodo` (`metodo`),
  KEY `menu_controller` (`menu_controller`),
  KEY `menu_metodo` (`menu_metodo`),
  KEY `lib_pub` (`lib_pub`),
  KEY `adms_grps_pg_id` (`adms_grps_pg_id`),
  KEY `adms_tps_pg_id` (`adms_tps_pg_id`),
  KEY `adms_sits_pg_id` (`adms_sits_pg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- col_situacao
CREATE TABLE `col_situacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `observacao` longtext DEFAULT NULL,
  `cores` varchar(255) NOT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- col_tentativas_acesso
CREATE TABLE `col_tentativas_acesso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_ip` varchar(255) DEFAULT NULL,
  `observacao` longtext DEFAULT NULL,
  `descricao` longtext DEFAULT NULL,
  `pagina_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pagina_id` (`pagina_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- col_usuarios
CREATE TABLE `col_usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `rm` int(11) DEFAULT NULL,
  `nome` varchar(255) NOT NULL,
  `nome_social` varchar(255) DEFAULT NULL,
  `genero_id` int(11) NOT NULL,
  `genero_confirmado_em` datetime DEFAULT NULL COMMENT 'E1b (D-356): quando o sexo foi escolhido na tela (NULL = veio do padrão antigo)',
  `apelido` varchar(255) DEFAULT NULL,
  `usuario` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(255) DEFAULT NULL,
  `celular` varchar(255) DEFAULT NULL,
  `recuperar_senha` varchar(255) DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `col_niveis_acesso_id` int(11) NOT NULL,
  `col_sits_usuario_id` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `genero_id` (`genero_id`),
  KEY `usuario_cre` (`usuario_cre`),
  KEY `col_sits_usuario_id` (`col_sits_usuario_id`),
  KEY `col_niveis_acesso_id` (`col_niveis_acesso_id`),
  UNIQUE KEY `rm` (`rm`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_busca_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- col_usuarios_enderecos
CREATE TABLE `col_usuarios_enderecos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `endereco` text DEFAULT NULL,
  `numero` varchar(255) DEFAULT NULL,
  `complemento` text DEFAULT NULL,
  `bairro` varchar(255) DEFAULT NULL,
  `cidade` varchar(255) DEFAULT NULL,
  `uf` varchar(255) DEFAULT NULL,
  `cep` varchar(255) DEFAULT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- col_usuarios_informacoes
CREATE TABLE `col_usuarios_informacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `col_user_id` int(11) NOT NULL,
  `nasc_data` date DEFAULT NULL,
  `nasc_local` text DEFAULT NULL,
  `nasc_uf` varchar(255) DEFAULT NULL,
  `nasc_nac` text DEFAULT NULL,
  `rg_escolar` varchar(255) DEFAULT NULL,
  `rg` varchar(255) DEFAULT NULL,
  `rg_org` varchar(255) DEFAULT NULL,
  `rg_uf` varchar(255) DEFAULT NULL,
  `rg_data_exp` date DEFAULT NULL,
  `cpf` varchar(255) DEFAULT NULL,
  `estado_civil` int(11) DEFAULT NULL,
  `raca` int(11) DEFAULT NULL,
  `ocupacao` longtext DEFAULT NULL,
  `conjuge` longtext DEFAULT NULL,
  `nome_mae` varchar(255) DEFAULT NULL,
  `nome_pai` varchar(255) DEFAULT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`col_user_id`),
  KEY `raca` (`raca`),
  KEY `estado_civil` (`estado_civil`),
  KEY `rg` (`rg`),
  UNIQUE KEY `cpf` (`cpf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- portal_disciplinas_alunos — a ENTURMAÇÃO. E3b: o mesmo aluno não repete na mesma oferta.
-- portal_disciplinas_alunos
CREATE TABLE `portal_disciplinas_alunos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rm` varchar(255) NOT NULL,
  `nome_aluno` text NOT NULL,
  `ordem` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `periodo` int(11) DEFAULT NULL,
  `grupo` int(11) DEFAULT NULL,
  `ano` int(11) NOT NULL,
  `rm_prof_mat_id` int(11) NOT NULL,
  `observacao` text DEFAULT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `rm_oferta` (`rm`,`rm_prof_mat_id`),
  KEY `curso_id` (`curso_id`),
  KEY `ano` (`ano`),
  KEY `rm_prof_mat_id` (`rm_prof_mat_id`),
  KEY `ordem` (`ordem`),
  KEY `rm` (`rm`),
  KEY `grupo` (`grupo`),
  KEY `periodo` (`periodo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_cursos — E3b: `localidade` é o POLO padrão (rm_polos.id); + modalidade padrão.
-- rm_cursos
CREATE TABLE `rm_cursos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `abreviatura` varchar(255) DEFAULT NULL,
  `descricao` longtext DEFAULT NULL,
  `periodo` int(11) NOT NULL COMMENT '1-diurno/2-noturno',
  `localidade` int(11) NOT NULL,
  `filial` int(11) NOT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `modalidade_id` int(11) NOT NULL DEFAULT 1 COMMENT 'modalidade padrão (uni_rh_cursos_modalidade.id, E3b)',
  `exige_tcc` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'E6a: a conclusão exige TCC aprovado',
  `nivel` varchar(60) DEFAULT NULL COMMENT 'E1a: nível do curso nos documentos (ex.: Especialização)',
  `nome_extenso` varchar(255) DEFAULT NULL COMMENT 'E1a: área/nome por extenso para o corpo dos documentos',
  `cabecalho_documento` varchar(255) DEFAULT NULL COMMENT 'E1a: linha do curso no cabeçalho dos documentos',
  `frequencia_minima` decimal(5,2) DEFAULT NULL COMMENT 'E2 (D-360): frequência mínima do curso, em %; NULL = a da instituição',
  `regra_media_id` int(11) DEFAULT NULL COMMENT '1.16.1 (D-447): regra de média do TCC e dos documentos do curso; NULL = Padrão da instituição',
  PRIMARY KEY (`id`),
  KEY `localidade` (`localidade`),
  KEY `filial` (`filial`),
  KEY `periodo` (`periodo`),
  KEY `usuario_cre` (`usuario_cre`),
  KEY `situacao` (`situacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_cursos_coordenacao
CREATE TABLE `rm_cursos_coordenacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `posicao_id` int(11) NOT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_cursos_grades — a GRADE da matriz (E3a). `versao` é o NÚMERO da grade,
-- digitado e único no curso. A CH não é coluna: é a soma dos módulos.
-- (E3a: saíram `periodos` e `carga_horaria`, que nenhum código lia.)
CREATE TABLE `rm_cursos_grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `versao` int(11) NOT NULL COMMENT 'número da grade no curso, digitado',
  `descricao` longtext DEFAULT NULL,
  `situacao` int(11) NOT NULL DEFAULT 1 COMMENT 'uni_rh_cursos_grades_situacao',
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `curso_versao` (`curso_id`,`versao`),
  KEY `versao` (`versao`),
  KEY `situacao` (`situacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_cursos_periodos
CREATE TABLE `rm_cursos_periodos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` text NOT NULL,
  `observacao` longtext DEFAULT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_cursos_posicao
CREATE TABLE `rm_cursos_posicao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `ordem` int(11) NOT NULL,
  `observacao` longtext DEFAULT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_cursos_turma_series — o MÓDULO da matriz (E3a; era a "série" do sistema de origem).
-- Pertence a uma grade. `ordem` é a posição do módulo na grade, e é por
-- (curso_id, ordem) que matrícula e oferta o acham (`periodo` = `ordem`).
-- A CH não é coluna: é a soma das disciplinas do módulo.
CREATE TABLE `rm_cursos_turma_series` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `grade_id` int(11) NOT NULL COMMENT 'rm_cursos_grades.id',
  `periodo` int(11) NOT NULL,
  `codnome` text DEFAULT NULL,
  `descricao` longtext DEFAULT NULL,
  `ordem` int(11) NOT NULL,
  `observacao` longtext DEFAULT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `grade_ordem` (`grade_id`,`ordem`),
  KEY `curso_ordem` (`curso_id`,`ordem`),
  KEY `periodo` (`periodo`),
  KEY `situacao` (`situacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_disciplina_atividade
CREATE TABLE `rm_disciplina_atividade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nomeAtividade` text NOT NULL,
  `descricao` longtext DEFAULT NULL,
  `data_inicial` datetime DEFAULT NULL,
  `data_final` datetime DEFAULT NULL,
  `tipo` int(11) NOT NULL,
  `peso` decimal(7,4) NOT NULL DEFAULT 10.0000 COMMENT '1.13.0 (D-318): peso da atividade (modo ponderada)',
  `componente_codigo` varchar(40) DEFAULT NULL COMMENT '1.13.0 (D-394): código do componente da regra; NULL = o componente padrão (o primeiro)',
  `entrega_arquivo` int(11) NOT NULL,
  `rm_prof_mat_id` int(11) NOT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `data_inicial` (`data_inicial`),
  KEY `data_final` (`data_final`),
  KEY `tipo` (`tipo`),
  KEY `entrega_arquivo` (`entrega_arquivo`),
  KEY `rm_prof_mat_id` (`rm_prof_mat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_disciplina_atividade_notas
CREATE TABLE `rm_disciplina_atividade_notas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ordenacao` int(11) NOT NULL,
  `rm` varchar(255) NOT NULL,
  `nome_aluno` text NOT NULL,
  `atividade_id` int(11) NOT NULL,
  `rm_prof_mat_id` int(11) NOT NULL,
  `tipo` int(11) NOT NULL,
  `nota` varchar(50) DEFAULT NULL,
  `data_alt` datetime DEFAULT NULL,
  `usuario_alt` int(11) DEFAULT NULL,
  `peso` varchar(50) NOT NULL,
  `alteracao` int(11) NOT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ra_2` (`rm`,`atividade_id`),
  KEY `ra` (`rm`),
  KEY `atividade_id` (`atividade_id`),
  KEY `rm_prof_mat_id` (`rm_prof_mat_id`),
  KEY `tipo` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_disciplina_atividade_tipo
CREATE TABLE `rm_disciplina_atividade_tipo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` text NOT NULL,
  `nomeAtividade` text NOT NULL,
  `descricao` longtext DEFAULT NULL,
  `grupo` int(11) NOT NULL,
  `peso_padrao` varchar(50) NOT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `grupo` (`grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_disciplina_faltas — FALTAS do aluno na oferta (E4: etapa única). Uma linha por
-- aluno × oferta: faltas lançadas (pelo professor, pela secretaria ou, depois, pela
-- importação do Attendance do Moodle) e faltas compensadas (atestado, item 13).
-- Saíram b1..b4, c1..c4, obs1..obs4, total_faltas, limite_faltas, presenca e ordenacao.
CREATE TABLE `rm_disciplina_faltas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rm` varchar(255) NOT NULL,
  `nome_aluno` text DEFAULT NULL,
  `rm_prof_mat_id` int(11) NOT NULL,
  `faltas` int(11) NOT NULL DEFAULT 0,
  `faltas_compensadas` int(11) NOT NULL DEFAULT 0,
  `observacao` longtext DEFAULT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `rm_oferta` (`rm`,`rm_prof_mat_id`),
  KEY `rm_prof_mat_id` (`rm_prof_mat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_faltas_compensacoes — E8c (D-264, D-265): cada COMPENSAÇÃO de faltas lançada pela secretaria ou pelo
-- administrador, com tipo, justificativa obrigatória, período, quem lançou e quando. O número em
-- rm_disciplina_faltas.faltas_compensadas é a soma destas linhas (aluno × oferta). Sem anexo (dado de saúde,
-- fica para depois, com LGPD). O aluno vê só a quantidade; o motivo nunca sai no portal.
CREATE TABLE IF NOT EXISTS `rm_faltas_compensacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rm_prof_mat_id` int(11) NOT NULL,
  `rm` varchar(255) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `tipo` varchar(20) NOT NULL COMMENT 'atestado | licenca | convocacao | outro',
  `justificativa` text NOT NULL,
  `inicio` date NOT NULL,
  `fim` date NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `oferta_rm` (`rm_prof_mat_id`, `rm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_disciplina_media_final — o RESULTADO GRAVADO no encerramento da oferta (E4):
-- média final, frequência, resultado e motivo, por aluno × oferta, com as regras que
-- valeram (casas, mínimos) e as contagens usadas. É disso que o histórico (E6) lê.
-- Saíram n1..n4, r1..r4, f1..f4, f0, limite_faltas, boletim_lib e afins (4 bimestres).
CREATE TABLE `rm_disciplina_media_final` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rm` varchar(255) NOT NULL,
  `nome_aluno` text DEFAULT NULL,
  `rm_prof_mat_id` int(11) NOT NULL,
  `media_final` decimal(5,2) NOT NULL,
  `atividades` int(11) NOT NULL COMMENT 'atividades da oferta (denominador)',
  `notas_lancadas` int(11) NOT NULL,
  `faltas` int(11) NOT NULL,
  `faltas_compensadas` int(11) NOT NULL,
  `encontros` int(11) NOT NULL,
  `frequencia` decimal(5,2) NOT NULL COMMENT '%',
  `resultado` tinyint(4) NOT NULL COMMENT '1 Aprovado, 2 Reprovado',
  `motivo` varchar(20) DEFAULT NULL COMMENT 'nota | frequencia | nota_frequencia',
  `casas` tinyint(4) NOT NULL,
  `media_minima` decimal(4,2) NOT NULL,
  `frequencia_minima` decimal(5,2) NOT NULL,
  `fechamento_id` int(11) DEFAULT NULL COMMENT '1.13.0 (D-396): rm_media_fechamentos.id; NULL num encerramento anterior à 1.13.0',
  `memoria` longtext DEFAULT NULL COMMENT '1.13.0 (D-396): memória de cálculo (JSON do motor)',
  `usuario_cre` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `rm_oferta` (`rm`,`rm_prof_mat_id`),
  KEY `rm_prof_mat_id` (`rm_prof_mat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_materias
CREATE TABLE `rm_materias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `abreviacao` varchar(255) DEFAULT NULL,
  `carga_horaria` varchar(50) DEFAULT NULL,
  `descricao` longtext DEFAULT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_matricula_tipos
CREATE TABLE `rm_matricula_tipos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `observacao` longtext DEFAULT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_matriculas_ensino — a POSIÇÃO do aluno no ANO: uma linha por ano cursado (D-04),
-- com grade e módulo por id (E3b). A matrícula no CURSO é rm_matriculas_curso.
-- rm_matriculas_ensino
CREATE TABLE `rm_matriculas_ensino` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `ano` int(11) NOT NULL,
  `ensino_id` int(11) NOT NULL,
  `periodo` int(11) NOT NULL,
  `ordem_chamada` int(11) DEFAULT NULL,
  `tipo_id` int(11) NOT NULL,
  `dt_exp` date DEFAULT NULL,
  `plano_antecipacao` int(11) NOT NULL,
  `plano_id` int(11) NOT NULL,
  `plano_material` int(11) DEFAULT NULL,
  `observacao` longtext DEFAULT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `origem_matricula_id` int(11) DEFAULT NULL COMMENT 'contrato que esta matricula sucede, na troca de responsavel',
  `vigencia_mes` int(11) DEFAULT NULL COMMENT 'mes 1-12 a partir do qual este contrato vale; NULL = ano inteiro',
  `motivo_troca` varchar(255) DEFAULT NULL,
  `substituido_em` datetime DEFAULT NULL COMMENT 'quando ESTE contrato foi sucedido por outro',
  `transferido_em` date DEFAULT NULL COMMENT 'Quando o aluno saiu. NULL = saiu sem data registrada, ou nao saiu.',
  `transferido_em_origem` varchar(20) DEFAULT NULL COMMENT 'portal = data extraida do texto livre | sem_aula = nenhuma disciplina no ano | manual = digitada na tela',
  `saida_especie` varchar(16) DEFAULT NULL COMMENT 'desistencia = saiu antes do 1o dia letivo | evasao = saiu com o ano em curso | nao_renovacao = saiu depois do ultimo dia letivo | NULL = ano sem calendario cadastrado',
  `entrada_tardia_em` date DEFAULT NULL COMMENT 'Matricula suplementar: quando o aluno entrou, depois da lista enviada a SED.',
  `entrada_tardia_origem` varchar(20) DEFAULT NULL COMMENT 'portal_ms = M.S. no texto livre | manual = digitada na tela',
  `matricula_curso_id` int(11) DEFAULT NULL COMMENT 'rm_matriculas_curso.id (E3b)',
  `grade_id` int(11) DEFAULT NULL COMMENT 'rm_cursos_grades.id (E3b)',
  `modulo_id` int(11) DEFAULT NULL COMMENT 'rm_cursos_turma_series.id (E3b)',
  `enturmacao_confirmada_por` int(11) DEFAULT NULL COMMENT 'adms_usuarios.id (E3b)',
  `enturmacao_confirmada_em` datetime DEFAULT NULL,
  `enturmacao_modulo_id` int(11) DEFAULT NULL COMMENT 'módulo em que a enturmação foi confirmada (E6a: mudou de módulo, a sugestão volta marcada)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `curso_ano` (`matricula_curso_id`,`ano`),
  KEY `modulo_id` (`modulo_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `ano` (`ano`),
  KEY `ensino_id` (`ensino_id`),
  KEY `periodo` (`periodo`),
  KEY `tipo_id` (`tipo_id`),
  KEY `plano_id` (`plano_id`),
  KEY `plano_material` (`plano_material`),
  KEY `ix_mat_origem` (`origem_matricula_id`),
  KEY `ix_mat_transferido_em` (`ano`,`transferido_em`),
  KEY `ix_mat_entrada_tardia` (`ano`,`entrada_tardia_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_matriculas_situacao
CREATE TABLE `rm_matriculas_situacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `rotulo_documento` varchar(60) DEFAULT NULL COMMENT 'E1b (D-355): como a situação sai nos documentos (ex.: EM CURSO)',
  `observacao` longtext DEFAULT NULL,
  `cores` varchar(255) DEFAULT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;


-- rm_professores_horario_aula
CREATE TABLE `rm_professores_horario_aula` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` text NOT NULL,
  `periodo` int(11) NOT NULL,
  `inicio` time NOT NULL,
  `termino` time NOT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `periodo` (`periodo`),
  KEY `inicio` (`inicio`),
  KEY `termino` (`termino`),
  KEY `situacao` (`situacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_professores_horarios
CREATE TABLE `rm_professores_horarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rm_prof_mat_id` int(11) NOT NULL,
  `periodo` int(11) NOT NULL,
  `dia_semana` int(11) NOT NULL,
  `horario_id` int(11) NOT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `rm_prof_mat_id` (`rm_prof_mat_id`),
  KEY `periodo` (`periodo`),
  KEY `dia_semana` (`dia_semana`),
  KEY `horario_id` (`horario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_professores_mat_liga
CREATE TABLE `rm_professores_mat_liga` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `rm_prof_mat_id` int(11) NOT NULL,
  `acesso` int(11) NOT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;


-- rm_professores_materias — a OFERTA. E3b: aponta para a linha da matriz (`matriz_id`);
-- `codfilial` é o POLO (rm_polos.id); modalidade própria, herdada do curso.
-- rm_professores_materias
CREATE TABLE `rm_professores_materias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `grade` int(11) NOT NULL,
  `periodo` int(11) NOT NULL,
  `codmat` int(11) NOT NULL,
  `codtur` varchar(255) NOT NULL,
  `materia` text NOT NULL,
  `ch_considerada` varchar(50) NOT NULL,
  `ano` int(11) NOT NULL,
  `bimestre` int(11) NOT NULL,
  `tipo` int(11) NOT NULL,
  `codfilial` int(11) NOT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `matriz_id` int(11) DEFAULT NULL COMMENT 'uni_rh_cursos_disciplinas_grade.id (E3b, D-03)',
  `idMoodle` int(11) DEFAULT NULL COMMENT 'id do curso/atividade no Moodle (D-05)',
  `modalidade_id` int(11) DEFAULT NULL COMMENT 'uni_rh_cursos_modalidade.id; herda do curso (E3b)',
  `encontros` int(11) DEFAULT NULL COMMENT 'total de encontros da oferta (frequência, E4)',
  `encontros_previstos` int(11) DEFAULT NULL COMMENT '1.17.2 (D-462): encontros previstos (só com a chamada); base dos relatórios e do alerta de risco, nunca da frequência oficial',
  `frequencia_fonte` varchar(12) NOT NULL DEFAULT 'lancamento' COMMENT '1.14.0 (D-405): lancamento (faltas digitadas ou importadas) | chamada (encontros e presenças)',
  `regra_media_id` int(11) DEFAULT NULL COMMENT '1.16.0 (D-437): regra trocada nesta oferta; NULL = a da disciplina da matriz',
  `encerrada_em` datetime DEFAULT NULL COMMENT 'encerramento (E4)',
  `encerrada_por` int(11) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL COMMENT 'E6a: período de realização da oferta (histórico)',
  `data_fim` date DEFAULT NULL COMMENT 'E6a',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ano_linha_turma` (`ano`,`matriz_id`,`codtur`),
  KEY `matriz_id` (`matriz_id`),
  KEY `curso_id` (`curso_id`),
  KEY `grade` (`grade`),
  KEY `periodo` (`periodo`),
  KEY `codmat` (`codmat`),
  KEY `ano` (`ano`),
  KEY `bimestre` (`bimestre`),
  KEY `tipo` (`tipo`),
  KEY `codfilial` (`codfilial`),
  KEY `rel_encerrada` (`encerrada_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_professores_materias_administradores
CREATE TABLE `rm_professores_materias_administradores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `observacao` longtext NOT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_professores_materias_polivalencias
CREATE TABLE `rm_professores_materias_polivalencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rm_polivalente` int(11) NOT NULL,
  `rm_prof_valido` int(11) NOT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `rm_polivalente` (`rm_polivalente`),
  KEY `rm_prof_valido` (`rm_prof_valido`),
  KEY `situacao` (`situacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_professores_materias_salas
CREATE TABLE `rm_professores_materias_salas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rm_prof_mat_id` int(11) NOT NULL,
  `sala_id` int(11) NOT NULL,
  `alunos` int(11) DEFAULT NULL,
  `capacidade_max` int(11) DEFAULT NULL,
  `observacao` longtext DEFAULT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `rm_prof_mat_id` (`rm_prof_mat_id`),
  KEY `sala_id` (`sala_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_salas
CREATE TABLE `rm_salas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` text NOT NULL,
  `descricao` longtext DEFAULT NULL,
  `observacao` longtext DEFAULT NULL,
  `capacidade_max` int(11) DEFAULT NULL,
  `codfilial` int(11) NOT NULL,
  `situacao` int(11) NOT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `capacidade_max` (`capacidade_max`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- col_usuarios_tokens (própria do VCOSTAEDU, E2b): links de definição de senha.
-- Só o SHA-256 do token é gravado; o token em claro existe apenas na URL.
CREATE TABLE `col_usuarios_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `col_usuario_id` int(11) NOT NULL,
  `finalidade` enum('primeiro_acesso','recuperacao') NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado_em` datetime DEFAULT NULL,
  `invalidado_em` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `criado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `col_usuario_id` (`col_usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- =============================================================================
-- Matriz curricular (E3a) — da base de origem, adaptadas ao sistema de origem.
-- =============================================================================

-- uni_rh_cursos_grades_situacao — domínio da situação da grade (conteúdo).
CREATE TABLE `uni_rh_cursos_grades_situacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `cores` varchar(255) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- uni_rh_cursos_gradeamento_obs — histórico das trocas de situação da grade.
-- Adaptada: `modelo_id` (gradeamento da base de origem) virou `grade_id`.
CREATE TABLE `uni_rh_cursos_gradeamento_obs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade_id` int(11) NOT NULL COMMENT 'rm_cursos_grades.id',
  `alteracao` int(11) NOT NULL COMMENT 'situação nova (uni_rh_cursos_grades_situacao)',
  `observacao` longtext DEFAULT NULL,
  `usuario_cre` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `grade_id` (`grade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- uni_rh_cursos_disciplinas_grade — a LINHA da matriz: a disciplina dentro do
-- módulo de uma grade. Adaptada: curso/grade por número → `grade_id`;
-- `periodo` → `modulo_id`; `materia_id` → rm_materias (catálogo, D-03);
-- `cargahoraria` da disciplina na linha. Sem colunas de RM, de turma ou de
-- oferta. A mesma matéria não se repete no mesmo módulo da mesma grade.
CREATE TABLE `uni_rh_cursos_disciplinas_grade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade_id` int(11) NOT NULL COMMENT 'rm_cursos_grades.id',
  `modulo_id` int(11) NOT NULL COMMENT 'rm_cursos_turma_series.id',
  `materia_id` int(11) NOT NULL COMMENT 'rm_materias.id',
  `cargahoraria` int(11) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0 COMMENT 'E1b (D-353): ordem da disciplina na grade (documentos); empate = nome',
  `regra_media_id` int(11) DEFAULT NULL COMMENT '1.16.0 (D-437): regra de média da disciplina da matriz; NULL = padrão da instituição',
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `grade_modulo_materia` (`grade_id`,`modulo_id`,`materia_id`),
  KEY `modulo_id` (`modulo_id`),
  KEY `materia_id` (`materia_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- =============================================================================
-- Vínculo, polo e modalidade (E3b).
-- =============================================================================

-- rm_matriculas_curso — a MATRÍCULA NO CURSO (própria do VCOSTAEDU, E3b): curso,
-- grade, ingresso e situação. Uma por aluno × curso × entrada; as posições por
-- ano/módulo ficam em rm_matriculas_ensino. Nova porque rm_matriculas_ensino é
-- anual (lida por ano no boletim, no portal e em Anos Letivos) e o curso lato
-- sensu atravessa anos: ingresso e situação não se repetem a cada ano.
CREATE TABLE `rm_matriculas_curso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL COMMENT 'col_usuarios.id',
  `curso_id` int(11) NOT NULL,
  `grade_id` int(11) NOT NULL COMMENT 'rm_cursos_grades.id',
  `forma_ingresso_id` int(11) NOT NULL COMMENT 'rm_matricula_tipos.id',
  `data_ingresso` date NOT NULL,
  `situacao_id` int(11) NOT NULL DEFAULT 1 COMMENT 'rm_matriculas_situacao.id',
  `polo_id` int(11) DEFAULT NULL COMMENT 'E6a (D-115): polo da matrícula; NULL = o do curso',
  `modalidade_id` int(11) DEFAULT NULL COMMENT 'E6a: modalidade da matrícula (escolhe o ato legal); NULL = a do curso',
  `data_conclusao` date DEFAULT NULL COMMENT 'E6a: só com a situação Concluído',
  `previsao_conclusao` date DEFAULT NULL COMMENT 'E1b (D-354): previsão de conclusão, digitada no vínculo (atestado)',
  `observacao` longtext DEFAULT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `curso_id` (`curso_id`),
  KEY `grade_id` (`grade_id`),
  KEY `rel_situacao` (`situacao_id`,`curso_id`),
  KEY `rel_previsao` (`previsao_conclusao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_polos — POLO (D-34, própria do VCOSTAEDU). Nova: o sistema de origem não tem tabela de
-- polo/filial (as colunas codfilial/localidade apontavam para 1 fixo, e "Sede"
-- era texto cravado); a uni_rh_filial da base de origem tem dados reais e não vem.
CREATE TABLE `rm_polos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `cidade` varchar(255) DEFAULT NULL,
  `uf` char(2) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `instituicao_parceira` varchar(255) DEFAULT NULL,
  `situacao` int(11) NOT NULL DEFAULT 1,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- uni_rh_cursos_modalidade — domínio da MODALIDADE (D-35), da base de origem (só domínio;
-- as linhas são do VCOSTAEDU: Presencial, EaD, Híbrido).
CREATE TABLE `uni_rh_cursos_modalidade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `descricao` longtext DEFAULT NULL,
  `status` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_atos_legais (E6a, VCOSTAEDU): atos da instituição por modalidade (D-36)
CREATE TABLE `rm_atos_legais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orgao` varchar(255) NOT NULL COMMENT 'ex.: Conselho Estadual de Educação, Ministério da Educação',
  `tipo` varchar(60) NOT NULL COMMENT 'Parecer, Resolução, Portaria, Decreto, Deliberação, Outro',
  `numero` varchar(60) NOT NULL,
  `data_ato` date NOT NULL,
  `veiculo_publicacao` varchar(255) DEFAULT NULL,
  `data_publicacao` date DEFAULT NULL,
  `vigencia_inicio` date NOT NULL,
  `vigencia_fim` date DEFAULT NULL COMMENT 'NULL = sem fim',
  `modalidade_id` int(11) NOT NULL COMMENT 'uni_rh_cursos_modalidade.id',
  `situacao` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 ativo, 2 inativo',
  `observacao` text DEFAULT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `modalidade_vigencia` (`modalidade_id`,`situacao`,`vigencia_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_tcc (E6a, VCOSTAEDU): TCC por matrícula no curso (P-5)
CREATE TABLE `rm_tcc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matricula_curso_id` int(11) NOT NULL COMMENT 'rm_matriculas_curso.id',
  `titulo` varchar(500) NOT NULL,
  `orientador_id` int(11) DEFAULT NULL COMMENT 'adms_usuarios.id (professor)',
  `nota` varchar(10) DEFAULT NULL COMMENT 'mesma regra das notas (E4): 0 a 10, até 2 casas, gravada com ponto',
  `data_defesa` date DEFAULT NULL,
  `situacao` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 Em andamento, 2 Aprovado, 3 Reprovado',
  `observacao` text DEFAULT NULL,
  `usuario_cre` int(11) NOT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricula_curso` (`matricula_curso_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- uni_rh_titulacao (E6a): estrutura da base de origem, linhas do VCOSTAEDU (Especialista, Mestre, Doutor)
CREATE TABLE `uni_rh_titulacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(250) NOT NULL,
  `status_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_professores_materias_encerramentos — histórico de encerrar/reabrir a oferta (E4,
-- própria do VCOSTAEDU). Reabrir é do administrador e exige observação.
CREATE TABLE `rm_professores_materias_encerramentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rm_prof_mat_id` int(11) NOT NULL,
  `acao` varchar(10) NOT NULL COMMENT 'encerrar | reabrir',
  `observacao` longtext DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `rm_prof_mat_id` (`rm_prof_mat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_historico_modelos (E6b, VCOSTAEDU): modelos de histórico PARCIAL/FINAL, editáveis por seções
CREATE TABLE `rm_historico_modelos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(10) NOT NULL COMMENT 'PARCIAL | FINAL | ATESTADO',
  `nome` varchar(150) NOT NULL,
  `situacao` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 Rascunho, 2 Ativo, 3 Arquivado',
  `versao_atual` int(11) NOT NULL DEFAULT 1 COMMENT 'rm_historico_modelo_versoes.versao em edição/uso',
  `usuario_cre` int(11) DEFAULT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tipo_situacao` (`tipo`, `situacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_historico_modelo_cursos (E6b): cursos a que o modelo se aplica; sem linha = padrão da instituição
CREATE TABLE `rm_historico_modelo_cursos` (
  `modelo_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  PRIMARY KEY (`modelo_id`, `curso_id`),
  KEY `curso_id` (`curso_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_historico_modelo_versoes (E6b): cada versão das seções (JSON). A versão usada numa emissão nunca muda.
CREATE TABLE `rm_historico_modelo_versoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modelo_id` int(11) NOT NULL,
  `versao` int(11) NOT NULL,
  `secoes` longtext NOT NULL COMMENT 'JSON: seções, ordem, HTML controlado, quadro de disciplinas, assinaturas',
  `usuario_cre` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `modelo_versao` (`modelo_id`, `versao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_historico_emissoes (E6b): cada emissão, IMUTÁVEL (só a situação muda: cancelar)
CREATE TABLE `rm_historico_emissoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL COMMENT 'código público, aleatório (XXXX-XXXX-XXXX)',
  `tipo` varchar(10) NOT NULL COMMENT 'PARCIAL | FINAL | ATESTADO',
  `matricula_curso_id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL COMMENT 'col_usuarios.id',
  `modelo_id` int(11) NOT NULL,
  `modelo_versao_id` int(11) NOT NULL,
  `snapshot` longtext NOT NULL COMMENT 'objeto da vida acadêmica + emissão, JSON canônico',
  `html` longtext NOT NULL COMMENT 'HTML renderizado (imagens embutidas)',
  `hash_conteudo` char(64) NOT NULL COMMENT 'sha256(snapshot + 0x1E + html)',
  `pdf_arquivo` varchar(100) NOT NULL COMMENT 'em storage/historico/emissoes/ (fora do acesso web)',
  `hash_pdf` char(64) NOT NULL COMMENT 'sha256 do arquivo PDF',
  `alertas_aceitos` text DEFAULT NULL COMMENT 'JSON: alertas confirmados na emissão (só PARCIAL)',
  `ambiente` varchar(20) NOT NULL DEFAULT 'producao' COMMENT 'E7 (D-220): AMBIENTE da Config na emissão (producao | homologacao | demonstracao); também no snapshot',
  `emitido_por` int(11) NOT NULL COMMENT 'adms_usuarios.id (0 = emitido pelo próprio aluno no portal)',
  `origem` varchar(12) NOT NULL DEFAULT 'secretaria' COMMENT 'E6 (D-372): secretaria (painel) | portal (o próprio aluno)',
  `emitido_por_aluno` int(11) DEFAULT NULL COMMENT 'E6 (D-372): col_usuarios.id do aluno que emitiu pelo portal',
  `emitido_em` datetime NOT NULL,
  `situacao` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 Válida, 2 Cancelada',
  `cancelado_por` int(11) DEFAULT NULL,
  `cancelado_em` datetime DEFAULT NULL,
  `cancelamento_motivo` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `matricula_curso_id` (`matricula_curso_id`),
  KEY `aluno_id` (`aluno_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- rm_historico_emissao_vias (E6b): 2ª via e seguintes — o MESMO HTML gravado, com a marca da via
CREATE TABLE `rm_historico_emissao_vias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emissao_id` int(11) NOT NULL,
  `via` int(11) NOT NULL COMMENT '2, 3, ...',
  `pdf_arquivo` varchar(100) NOT NULL,
  `hash_pdf` char(64) NOT NULL,
  `emitido_por` int(11) NOT NULL,
  `emitido_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emissao_via` (`emissao_id`, `via`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- E3/E4 (1.13.0, D-391 a D-397): regra de média (cabeçalho por escopo, versões, componentes) e fechamentos da oferta
CREATE TABLE `rm_media_regras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) DEFAULT NULL COMMENT '1.16.0 (D-436): nome da regra no catálogo',
  `descricao` varchar(500) DEFAULT NULL COMMENT '1.16.0 (D-436)',
  `padrao` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1.16.0 (D-436): 1 = Padrão da instituição (exatamente uma)',
  `escopo` varchar(12) NOT NULL COMMENT 'instituicao | curso | oferta',
  `escopo_id` int(11) NOT NULL DEFAULT 0 COMMENT 'rm_cursos.id ou rm_professores_materias.id; 0 na instituição',
  `versao_atual` int(11) NOT NULL DEFAULT 1 COMMENT 'rm_media_regra_versoes.versao em uso',
  `situacao` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 regra própria (vale); 2 voltou a herdar (guardada, não vale)',
  `usuario_cre` int(11) DEFAULT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

CREATE TABLE `rm_media_regra_versoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `regra_id` int(11) NOT NULL,
  `versao` int(11) NOT NULL,
  `casas` tinyint(1) NOT NULL COMMENT '1 | 2',
  `arredondamento` varchar(16) NOT NULL COMMENT 'meio_para_cima | para_cima | para_baixo | meio_ponto',
  `momento` varchar(20) NOT NULL COMMENT 'so_final | em_cada_componente',
  `sem_nota` varchar(10) NOT NULL COMMENT 'atividade sem nota no fechamento: zero | ignorar',
  `componente_vazio` varchar(14) NOT NULL COMMENT 'redistribuir | zero',
  `nota_minima` decimal(4,2) NOT NULL,
  `usuario_cre` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `regra_versao` (`regra_id`, `versao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

CREATE TABLE `rm_media_regra_componentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `versao_id` int(11) NOT NULL COMMENT 'rm_media_regra_versoes.id',
  `codigo` varchar(40) NOT NULL COMMENT 'estável: as atividades apontam para ele (rm_disciplina_atividade.componente_codigo)',
  `nome` varchar(80) NOT NULL,
  `peso` decimal(7,4) NOT NULL COMMENT 'os pesos da versão somam 100',
  `modo` varchar(20) NOT NULL COMMENT 'aritmetica | ponderada | soma | maior | menor | descartar_menores | manter_maiores',
  `parametro_n` int(11) DEFAULT NULL COMMENT 'N (quantidade) ou X (percentual) dos modos descartar/manter',
  `parametro_tipo` varchar(12) DEFAULT NULL COMMENT 'quantidade | percentual',
  `ordem` int(11) NOT NULL DEFAULT 1,
  `min_atividades` int(11) NOT NULL DEFAULT 0 COMMENT '1.15.0 (D-420): 0 = opcional; N >= 1 = obrigatório, com pelo menos N atividades na oferta',
  `max_atividades` int(11) DEFAULT NULL COMMENT '1.15.0 (D-420): máximo de atividades do componente na oferta; NULL = sem limite',
  PRIMARY KEY (`id`),
  UNIQUE KEY `versao_codigo` (`versao_id`, `codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- 1.16.0 (D-438 c): cada troca de regra numa oferta, com a justificativa
CREATE TABLE `rm_media_regra_trocas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rm_prof_mat_id` int(11) NOT NULL COMMENT 'a oferta',
  `regra_anterior_id` int(11) DEFAULT NULL COMMENT 'a regra que valia antes da troca',
  `regra_nova_id` int(11) DEFAULT NULL COMMENT 'NULL = voltou para a regra da disciplina',
  `justificativa` varchar(500) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `oferta` (`rm_prof_mat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- 1.17.0 (D-457): cada exportação de relatório (Excel, CSV, PDF, impressão), gravada antes do arquivo — a auditoria da LGPD
CREATE TABLE `rm_relatorios_exportacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `relatorio` varchar(20) NOT NULL COMMENT 'código do catálogo (R-PAI-01…)',
  `formato` varchar(10) NOT NULL COMMENT 'excel | csv | pdf | imprimir',
  `filtros` text DEFAULT NULL COMMENT 'a query string dos filtros',
  `linhas` int(11) NOT NULL DEFAULT 0,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario` (`usuario_id`),
  KEY `quando` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- 1.17.0 (D-454): relatórios salvos (relatório + filtros + colunas visíveis), pessoais ou compartilhados com um perfil
CREATE TABLE `rm_relatorios_salvos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `relatorio` varchar(20) NOT NULL,
  `filtros` text DEFAULT NULL COMMENT 'a query string dos filtros',
  `colunas` text DEFAULT NULL COMMENT 'JSON: as colunas visíveis (chaves)',
  `usuario_id` int(11) NOT NULL COMMENT 'o dono (o único que edita e apaga)',
  `nivel_acesso_id` int(11) DEFAULT NULL COMMENT 'compartilhado com este perfil; NULL = pessoal',
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `dono` (`usuario_id`),
  KEY `perfil` (`nivel_acesso_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

CREATE TABLE `rm_media_fechamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rm_prof_mat_id` int(11) NOT NULL,
  `numero` int(11) NOT NULL COMMENT '1, 2, … a cada reabertura',
  `regra_versao_id` int(11) DEFAULT NULL COMMENT 'rm_media_regra_versoes.id usada; NULL num fechamento anterior à 1.13.0',
  `regra_origem` varchar(12) NOT NULL COMMENT 'instituicao | curso | oferta | legado',
  `regra_json` longtext NOT NULL COMMENT 'a regra EFETIVA copiada (JSON canônico): mudar a regra depois não altera o fechado',
  `regra_hash` char(64) NOT NULL COMMENT 'sha256 de regra_json',
  `situacao` varchar(12) NOT NULL DEFAULT 'vigente' COMMENT 'vigente | substituido',
  `fechado_por` int(11) DEFAULT NULL,
  `fechado_em` datetime NOT NULL,
  `reaberto_por` int(11) DEFAULT NULL,
  `reaberto_em` datetime DEFAULT NULL,
  `justificativa_reabertura` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `oferta_numero` (`rm_prof_mat_id`, `numero`),
  KEY `regra_versao_id` (`regra_versao_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

CREATE TABLE `rm_media_fechamento_resultados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fechamento_id` int(11) NOT NULL COMMENT 'rm_media_fechamentos.id (substituído)',
  `media_final_id` int(11) NOT NULL COMMENT 'o id que a linha tinha em rm_disciplina_media_final',
  `rm` varchar(255) NOT NULL,
  `nome_aluno` text DEFAULT NULL,
  `rm_prof_mat_id` int(11) NOT NULL,
  `media_final` decimal(5,2) NOT NULL,
  `atividades` int(11) NOT NULL,
  `notas_lancadas` int(11) NOT NULL,
  `faltas` int(11) NOT NULL,
  `faltas_compensadas` int(11) NOT NULL,
  `encontros` int(11) NOT NULL,
  `frequencia` decimal(5,2) NOT NULL,
  `resultado` tinyint(4) NOT NULL,
  `motivo` varchar(20) DEFAULT NULL,
  `casas` tinyint(4) NOT NULL,
  `media_minima` decimal(4,2) NOT NULL,
  `frequencia_minima` decimal(5,2) NOT NULL,
  `memoria` longtext DEFAULT NULL,
  `usuario_cre` int(11) NOT NULL,
  `created` datetime NOT NULL COMMENT 'quando a linha foi gravada no encerramento',
  `arquivado_em` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'quando a reabertura a guardou aqui',
  PRIMARY KEY (`id`),
  KEY `fechamento_id` (`fechamento_id`),
  KEY `oferta_rm` (`rm_prof_mat_id`, `rm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- E7 (1.14.0, D-405 a D-408): chamada por encontro da oferta (encontros e presenças)
CREATE TABLE `rm_oferta_encontros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rm_prof_mat_id` int(11) NOT NULL COMMENT 'a oferta',
  `data` date NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fim` time DEFAULT NULL,
  `titulo` varchar(150) DEFAULT NULL,
  `conteudo` text DEFAULT NULL,
  `usuario_cre` int(11) DEFAULT NULL,
  `usuario_mod` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `oferta_data` (`rm_prof_mat_id`, `data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

CREATE TABLE `rm_oferta_presencas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `encontro_id` int(11) NOT NULL COMMENT 'rm_oferta_encontros.id',
  `rm` varchar(255) NOT NULL,
  `presente` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 presente, 0 ausente',
  `usuario_cre` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `encontro_rm` (`encontro_id`, `rm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

SET FOREIGN_KEY_CHECKS = 1;
