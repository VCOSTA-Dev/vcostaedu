-- Onda 2, Etapa 5 — importação de alunos por planilha.
--
-- O lote (adms_importacoes) e as linhas dele (adms_importacoes_linhas): a linha BRUTA inteira do arquivo fica guardada
-- (o de-para de coluna pode mudar depois do envio), a correção feita na tela fica à parte, e o julgamento (pronta, com
-- aviso, com erro, pulada, aplicada) é refeito a cada mudança. Aplicar grava os alunos pelas MESMAS funções da tela
-- (AdmsCadastrarUsuarioAcademico e AdmsVincularAluno), numa transação só; desfazer é ação sensível (Etapa 2).
-- As 8 páginas e as permissões: Administrador, Supervisão de secretaria e Secretaria; o menu em Educacional.

CREATE TABLE IF NOT EXISTS `adms_importacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(20) NOT NULL DEFAULT 'alunos',
  `arquivo_nome` varchar(255) NOT NULL,
  `separador` varchar(4) NOT NULL,
  `codificacao` varchar(20) NOT NULL,
  `cabecalho` text NOT NULL COMMENT 'JSON: os cabeçalhos do arquivo, como vieram',
  `assinatura` char(40) NOT NULL COMMENT 'sha1 dos cabeçalhos achatados: o mesmo arquivo reaproveita o de-para',
  `mapa_colunas` text NOT NULL COMMENT 'JSON: índice da coluna -> campo (ou vazio)',
  `forma_ingresso_id` int(11) DEFAULT NULL,
  `data_ingresso` date DEFAULT NULL,
  `situacao` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 conferindo, 2 aplicada, 3 desfeita',
  `total` int(11) NOT NULL DEFAULT 0,
  `prontas` int(11) NOT NULL DEFAULT 0,
  `avisos` int(11) NOT NULL DEFAULT 0,
  `erros` int(11) NOT NULL DEFAULT 0,
  `puladas` int(11) NOT NULL DEFAULT 0,
  `aplicadas` int(11) NOT NULL DEFAULT 0,
  `vinculos` int(11) NOT NULL DEFAULT 0,
  `usuario_cre` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `aplicada_por` int(11) DEFAULT NULL,
  `aplicada_em` datetime DEFAULT NULL,
  `desfeita_por` int(11) DEFAULT NULL,
  `desfeita_em` datetime DEFAULT NULL,
  `desfeita_motivo` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_importacoes_assinatura` (`assinatura`),
  KEY `idx_importacoes_situacao` (`situacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `adms_importacoes_linhas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `importacao_id` int(11) NOT NULL,
  `numero` int(11) NOT NULL COMMENT 'a linha no arquivo (o cabeçalho é a 1)',
  `bruto` text NOT NULL COMMENT 'JSON: a linha inteira, como veio',
  `correcao` text DEFAULT NULL COMMENT 'JSON: campo -> valor corrigido na tela',
  `dados` text DEFAULT NULL COMMENT 'JSON: os valores já convertidos que vão para o cadastro',
  `resultado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 pronta, 2 com aviso, 3 com erro, 4 pulada, 5 aplicada',
  `mensagens` text DEFAULT NULL COMMENT 'JSON: [{tipo: erro|aviso, texto}]',
  `usuario_id` int(11) DEFAULT NULL COMMENT 'o aluno criado ao aplicar',
  `matricula_curso_id` int(11) DEFAULT NULL COMMENT 'o vínculo criado ao aplicar',
  PRIMARY KEY (`id`),
  KEY `idx_importacoes_linhas_lote` (`importacao_id`, `numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- As páginas
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`)
SELECT p.* FROM (
  SELECT 475 AS id, 'Importar alunos' AS mp, 'ImportacaoAlunos' AS c, 'listar' AS m, 'importacao-alunos' AS mc, 'listar' AS mm, 'Importar alunos de planilha' AS np, 'Onda 2: os lotes de importação e o envio da planilha.' AS o, 2 AS lp, 'fas fa-file-import' AS ic, 1 AS g, 1 AS tp, 1 AS s, NOW() AS cr, 0 AS gr, 0 AS se, NULL AS es
  UNION ALL SELECT 476, 'Enviar planilha de alunos', 'ImportacaoAlunos', 'enviar', 'importacao-alunos', 'enviar', 'Enviar planilha de alunos', 'Onda 2: lê o CSV e cria o lote para conferir (POST).', 2, 'fas fa-upload', 1, 1, 1, NOW(), 1, 0, NULL
  UNION ALL SELECT 477, 'Conferir importação', 'ImportacaoAlunos', 'conferir', 'importacao-alunos', 'conferir', 'Conferir a importação', 'Onda 2: as colunas, os valores e as linhas do lote, antes e depois de aplicar.', 2, 'fas fa-list-check', 1, 1, 1, NOW(), 0, 0, NULL
  UNION ALL SELECT 478, 'Ajustar as colunas da importação', 'ImportacaoAlunos', 'mapear', 'importacao-alunos', 'mapear', 'Ajustar as colunas da importação', 'Onda 2: o de-para das colunas do arquivo (POST).', 2, 'fas fa-table-columns', 1, 1, 1, NOW(), 1, 0, NULL
  UNION ALL SELECT 479, 'Corrigir linha da importação', 'ImportacaoAlunos', 'corrigir', 'importacao-alunos', 'corrigir', 'Corrigir uma linha da importação', 'Onda 2: corrige uma linha na tela, sem reenviar o arquivo (POST).', 2, 'fas fa-pen', 1, 1, 1, NOW(), 1, 0, NULL
  UNION ALL SELECT 480, 'Aplicar importação', 'ImportacaoAlunos', 'aplicar', 'importacao-alunos', 'aplicar', 'Aplicar a importação', 'Onda 2: grava os alunos do lote numa transação (POST).', 2, 'fas fa-check-double', 1, 1, 1, NOW(), 1, 0, NULL
  UNION ALL SELECT 481, 'Desfazer importação', 'ImportacaoAlunos', 'desfazer', 'importacao-alunos', 'desfazer', 'Desfazer a importação', 'Onda 2: ação sensível — tira os alunos do lote enquanto nenhum tiver lançamento ou documento (POST).', 2, 'fas fa-rotate-left', 1, 1, 1, NOW(), 1, 1, NULL
  UNION ALL SELECT 482, 'Modelo da planilha de alunos', 'ImportacaoAlunos', 'modelo', 'importacao-alunos', 'modelo', 'Modelo da planilha de alunos', 'Onda 2: o CSV de exemplo, com as colunas.', 2, 'fas fa-file-csv', 1, 1, 1, NOW(), 0, 0, NULL
) p WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` x WHERE x.`id` = p.id);

-- Uma linha por nível e página (sem permissão), como o sistema espera
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 2, p.`id`, 2, 2, IF(p.`id` = 475, 5, 1), n.`id`, p.`id`, NOW()
  FROM `adms_niveis_acessos` n JOIN `adms_paginas` p ON p.`id` BETWEEN 475 AND 482
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.`id` AND x.`adms_pagina_id` = p.`id`);
-- Administrador, Supervisão de secretaria e Secretaria: todas; "Importar alunos" no menu Educacional. O desfazer é
-- sensível: a rota o recusa a quem não tem ações sensíveis (a Secretaria), mesmo com a permissão
UPDATE `adms_nivacs_pgs` d JOIN `adms_niveis_acessos` n ON n.`id` = d.`adms_niveis_acesso_id`
   SET d.`permissao` = 1, d.`lib_menu` = IF(d.`adms_pagina_id` = 475, 1, 2), d.`dropdown` = IF(d.`adms_pagina_id` = 475, 1, 2),
       d.`adms_menu_id` = IF(d.`adms_pagina_id` = 475, 5, 1), d.`ordem` = IF(d.`adms_pagina_id` = 475, 49, d.`ordem`)
 WHERE d.`adms_pagina_id` BETWEEN 475 AND 482 AND n.`papel` IN ('administrador', 'supervisao_secretaria', 'secretaria')
   AND (d.`adms_pagina_id` <> 481 OR n.`acoes_sensiveis` = 1);

-- A versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.23.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 39, '1.23.0', 1, 23, 0, '2026-11-15', 'Importação de alunos por planilha: o CSV conferido linha a linha antes de gravar, as colunas e os valores reconhecidos, a correção na tela, a gravação em lote e o desfazer.', 1, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.23.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.23.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 124, 39, 'Importar alunos de uma planilha', 'Em Educacional → Importar alunos, envie um CSV (o do Excel serve). O sistema reconhece as colunas, mostra cada linha pronta, com aviso ou com erro e o motivo, deixa corrigir na tela e só grava quando você aplica — e pode já vincular ao curso.', 1, 'administrador,secretaria', 'importar-alunos-de-planilha', '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 124);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 125, 39, 'Desfazer uma importação', 'O lote aplicado pode ser desfeito pelo Administrador ou pela Supervisão de secretaria, enquanto nenhum aluno dele tiver nota, falta ou documento emitido. Se algum tiver, a tela diz qual.', 2, 'administrador,secretaria', 'importar-alunos-de-planilha', '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 125);
