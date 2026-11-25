-- Onda 2, Etapa 6 — a API de integração (/api/v1).
--
-- A chave fica gravada só como hash (sha256); a tela mostra a chave UMA vez. Toda chamada fica registrada (a chave, a
-- operação, o resultado, o IP, quando). A Idempotency-Key das escritas guarda a resposta para repeti-la. O limite de
-- requisições por chave é um parâmetro (dado, não código). As páginas: "Integrações" no menu, só o Administrador.

CREATE TABLE IF NOT EXISTS `adms_api_chaves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `prefixo` varchar(16) NOT NULL COMMENT 'o começo da chave, para reconhecê-la na lista',
  `hash` char(64) NOT NULL COMMENT 'sha256 da chave inteira; a chave não é guardada',
  `escopo` varchar(10) NOT NULL DEFAULT 'leitura' COMMENT 'leitura ou escrita (escrita inclui leitura)',
  `validade` date DEFAULT NULL COMMENT 'vazia: sem prazo',
  `situacao` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 ativa, 2 revogada',
  `usuario_id` int(11) NOT NULL COMMENT 'quem criou: a API grava em nome dele',
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `revogada_por` int(11) DEFAULT NULL,
  `revogada_em` datetime DEFAULT NULL,
  `ultimo_uso_em` datetime DEFAULT NULL,
  `ultimo_uso_ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_api_chaves_hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `adms_api_chamadas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave_id` int(11) DEFAULT NULL,
  `prefixo` varchar(16) DEFAULT NULL,
  `metodo` varchar(8) NOT NULL,
  `rota` varchar(255) NOT NULL,
  `operacao` varchar(60) DEFAULT NULL,
  `status` smallint(6) NOT NULL,
  `codigo_erro` varchar(40) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `idempotency_key` varchar(100) DEFAULT NULL,
  `duracao_ms` int(11) DEFAULT NULL,
  `created` datetime(3) NOT NULL DEFAULT current_timestamp(3),
  PRIMARY KEY (`id`),
  KEY `idx_api_chamadas_chave` (`chave_id`, `created`),
  KEY `idx_api_chamadas_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `adms_api_idempotencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave_id` int(11) NOT NULL,
  `idem_key` varchar(100) NOT NULL,
  `metodo` varchar(8) NOT NULL,
  `rota` varchar(255) NOT NULL,
  `hash_corpo` char(64) NOT NULL,
  `status` smallint(6) NOT NULL,
  `resposta` mediumtext NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_api_idempotencia` (`chave_id`, `idem_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- O limite de requisições por chave (por minuto): dado
INSERT INTO `adms_parametros` (`chave`, `valor`, `descricao`, `grupo`, `editavel`)
SELECT 'api.limite_por_minuto', '120', 'Quantas chamadas cada chave da API pode fazer por minuto (acima disso, a API responde 429).', 'Integrações', 1
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_parametros` WHERE `chave` = 'api.limite_por_minuto');

-- O menu "Integrações"
INSERT INTO `adms_menus` (`id`, `nome`, `icone`, `ordem`, `adms_sit_id`)
SELECT 11, 'Integrações', 'fas fa-plug', 6, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_menus` WHERE `id` = 11);

-- As páginas
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`)
SELECT p.* FROM (
  SELECT 483 AS id, 'Chaves de API' AS mp, 'Integracoes' AS c, 'chaves' AS m, 'integracoes' AS mc, 'chaves' AS mm, 'Chaves de API' AS np, 'Onda 2: as chaves da API — criar, revogar, ver o último uso.' AS o, 2 AS lp, 'fas fa-key' AS ic, 1 AS g, 1 AS tp, 1 AS s, NOW() AS cr, 0 AS gr, 0 AS se, NULL AS es
  UNION ALL SELECT 484, 'Criar chave de API', 'Integracoes', 'criar', 'integracoes', 'criar', 'Criar chave de API', 'Onda 2: cria a chave e a mostra uma vez (POST).', 2, 'fas fa-plus', 1, 1, 1, NOW(), 1, 0, NULL
  UNION ALL SELECT 485, 'Revogar chave de API', 'Integracoes', 'revogar', 'integracoes', 'revogar', 'Revogar chave de API', 'Onda 2: revoga a chave (POST).', 2, 'fas fa-ban', 1, 1, 1, NOW(), 1, 0, NULL
  UNION ALL SELECT 486, 'Documentação da API', 'Integracoes', 'documentacao', 'integracoes', 'documentacao', 'Documentação da API', 'Onda 2: o Swagger UI servido localmente, com o teste pela chave.', 2, 'fas fa-book', 1, 1, 1, NOW(), 0, 0, NULL
  UNION ALL SELECT 487, 'Chamadas da API', 'Integracoes', 'chamadas', 'integracoes', 'chamadas', 'Chamadas da API', 'Onda 2: o registro de cada chamada (chave, operação, resultado, IP, quando).', 2, 'fas fa-list', 1, 1, 1, NOW(), 0, 0, NULL
  UNION ALL SELECT 488, 'Especificação da API', 'Integracoes', 'especificacao', 'integracoes', 'especificacao', 'Especificação da API (OpenAPI)', 'Onda 2: a especificação OpenAPI 3, em JSON, para o Swagger UI.', 2, 'fas fa-file-code', 1, 1, 1, NOW(), 0, 0, NULL
) p WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` x WHERE x.`id` = p.id);

INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 2, p.`id`, 2, 2, 1, n.`id`, p.`id`, NOW()
  FROM `adms_niveis_acessos` n JOIN `adms_paginas` p ON p.`id` BETWEEN 483 AND 488
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.`id` AND x.`adms_pagina_id` = p.`id`);
-- Só o Administrador; no menu Integrações: Chaves de API, Documentação da API e Chamadas da API
UPDATE `adms_nivacs_pgs` d JOIN `adms_niveis_acessos` n ON n.`id` = d.`adms_niveis_acesso_id`
   SET d.`permissao` = 1,
       d.`lib_menu` = IF(d.`adms_pagina_id` IN (483, 486, 487), 1, 2), d.`dropdown` = IF(d.`adms_pagina_id` IN (483, 486, 487), 1, 2),
       d.`adms_menu_id` = IF(d.`adms_pagina_id` IN (483, 486, 487), 11, 1)
 WHERE d.`adms_pagina_id` BETWEEN 483 AND 488 AND n.`papel` = 'administrador';

-- A versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.24.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 40, '1.24.0', 1, 24, 0, '2026-11-15', 'API de integração: /api/v1 com chaves de acesso, a documentação Swagger servida pelo próprio sistema e o registro de cada chamada.', 1, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.24.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.24.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 126, 40, 'Integrar outro sistema pela API', 'Em Integrações, o Administrador cria chaves de acesso (só leitura, ou leitura e escrita) para outro sistema consultar e cadastrar alunos, matrículas e enturmações pelas mesmas regras das telas. A documentação, com teste, fica em Integrações → Documentação da API.', 1, 'administrador', 'integrar-outro-sistema-pela-api', '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 126);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 127, 40, 'O registro das chamadas da API', 'Cada chamada da API fica registrada — a chave, a operação, o resultado, o IP e a hora — em Integrações → Chamadas da API.', 2, 'administrador', 'integrar-outro-sistema-pela-api', '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 127);
