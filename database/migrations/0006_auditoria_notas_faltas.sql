-- 0006 — ONDA 2, ETAPA 3: a auditoria de notas e faltas (versão 1.21.0). docs/AUDITORIA.md.
--
-- 1. A tabela, só de acréscimo: cada linha é uma mudança de nota, de falta, de presença na chamada, de compensação,
--    ou o fechamento e a reabertura de uma disciplina — com o valor anterior e o novo, quem (usuário e perfil no
--    momento), quando, o IP, o navegador resumido e a origem (tela do painel, tela do professor, importação, API).
--    Nenhuma tela nem rota altera ou apaga linha daqui; os gatilhos da 0007 (que exigem o privilégio TRIGGER) o
--    garantem também no banco — só a limpeza da demonstração apaga, e só as linhas do ambiente 'demonstracao'.
-- 2. As páginas: o "Histórico desta nota" (painel, com o escopo do coordenador) e o relatório "Auditoria de notas e
--    faltas" (Administrador, Direção acadêmica, Supervisão de secretaria; Coordenação de curso só dos cursos dela),
--    com a permissão na mesma migration.
-- 3. A versão 1.21.0 e as novidades.
-- Idempotente.

CREATE TABLE IF NOT EXISTS `rm_auditoria_lancamentos` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ocorrido_em` datetime(3) NOT NULL DEFAULT current_timestamp(3),
  `tipo` varchar(20) NOT NULL COMMENT 'nota | falta | presenca | compensacao | fechamento',
  `acao` varchar(20) NOT NULL COMMENT 'lancou | alterou | apagou | encerrou | reabriu',
  `apos_fechamento` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = a disciplina já tinha sido encerrada antes (a mudança veio depois de uma reabertura)',
  `oferta_id` int(11) NOT NULL COMMENT 'rm_professores_materias.id',
  `curso_id` int(11) DEFAULT NULL COMMENT 'o curso da oferta (o escopo do coordenador)',
  `atividade_id` int(11) DEFAULT NULL COMMENT 'nota: a avaliação',
  `encontro_id` int(11) DEFAULT NULL COMMENT 'presença: o encontro da chamada',
  `data_aula` date DEFAULT NULL COMMENT 'presença: a data da aula',
  `rm` varchar(30) DEFAULT NULL COMMENT 'o aluno (RA); NULL no fechamento',
  `aluno_nome` varchar(255) DEFAULT NULL,
  `campo` varchar(40) NOT NULL COMMENT 'nota | faltas | faltas_compensadas | presenca | situacao',
  `valor_anterior` varchar(60) DEFAULT NULL,
  `valor_novo` varchar(60) DEFAULT NULL,
  `detalhe` varchar(500) DEFAULT NULL COMMENT 'a justificativa da reabertura, o motivo da compensação',
  `usuario_id` int(11) DEFAULT NULL COMMENT 'adms_usuarios.id; NULL quando foi uma chave de API',
  `usuario_nome` varchar(255) NOT NULL,
  `perfil` varchar(80) NOT NULL COMMENT 'o perfil (nível de acesso) no momento, ou "Chave de API"',
  `origem` varchar(20) NOT NULL COMMENT 'painel | professor | importacao | api',
  `ip` varchar(45) DEFAULT NULL,
  `navegador` varchar(120) DEFAULT NULL COMMENT 'resumido (navegador e sistema), sem a assinatura completa',
  `ambiente` varchar(20) NOT NULL DEFAULT 'producao' COMMENT 'o AMBIENTE da instalação no momento; a limpeza da demonstração apaga só os de demonstracao',
  PRIMARY KEY (`id`),
  KEY `ix_aud_oferta` (`oferta_id`, `ocorrido_em`),
  KEY `ix_aud_rm` (`rm`, `ocorrido_em`),
  KEY `ix_aud_atividade` (`atividade_id`, `rm`),
  KEY `ix_aud_curso` (`curso_id`, `ocorrido_em`),
  KEY `ix_aud_usuario` (`usuario_id`, `ocorrido_em`),
  KEY `ix_aud_quando` (`ocorrido_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Onda 2: auditoria de notas e faltas (só acréscimo; a collation das tabelas herdadas, com que ela se cruza — DUVIDAS 10)';

-- As páginas (Ensino → Relatórios recebe o relatório; o histórico abre ao lado da nota)
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`)
SELECT 465, 'Auditoria de notas e faltas', 'Auditoria', 'listar', 'auditoria', 'listar', 'Auditoria de notas e faltas', 'Onda 2: quem lançou, alterou ou apagou nota e falta, quando e de onde (só leitura).', 2, 'fas fa-clipboard-check', 1, 1, 1, NOW(), 0, 0, 'relatorio'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 465);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`)
SELECT 466, 'Histórico desta nota', 'Auditoria', 'nota', 'auditoria', 'nota', 'Histórico desta nota', 'Onda 2: as mudanças de uma nota (avaliação e aluno), em janela.', 2, 'fas fa-clock-rotate-left', 1, 1, 1, NOW(), 0, 0, 'atividade'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 466);

-- A permissão: uma linha por nível (os criados na tela também, sem permissão); o relatório no menu Relatórios (10)
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 2, 465, 2, 2, 10, n.`id`, p.`id`, NOW()
  FROM `adms_niveis_acessos` n JOIN `adms_paginas` p ON p.`id` IN (465, 466)
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.`id` AND x.`adms_pagina_id` = p.`id`);
-- o relatório: Administrador, Direção, Supervisão, Coordenação (no menu); o histórico: quem vê a nota (o aluno não)
UPDATE `adms_nivacs_pgs` d JOIN `adms_niveis_acessos` n ON n.`id` = d.`adms_niveis_acesso_id`
   SET d.`permissao` = 1, d.`lib_menu` = 1
 WHERE d.`adms_pagina_id` = 465 AND n.`papel` IN ('administrador', 'direcao', 'supervisao_secretaria', 'coordenacao');
UPDATE `adms_nivacs_pgs` d JOIN `adms_niveis_acessos` n ON n.`id` = d.`adms_niveis_acesso_id`
   SET d.`permissao` = 1, d.`lib_menu` = 2
 WHERE d.`adms_pagina_id` = 466 AND n.`id` IN (SELECT `adms_niveis_acesso_id` FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id`, `permissao` FROM `adms_nivacs_pgs`) y
                                               JOIN `adms_paginas` q ON q.`id` = y.`adms_pagina_id`
                                              WHERE q.`controller` = 'Atividades' AND q.`metodo` = 'visualizar' AND y.`permissao` = 1);

-- A versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.21.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 37, '1.21.0', 1, 21, 0, '2026-11-14', 'Auditoria de notas e faltas: cada lançamento, alteração e exclusão fica registrado com quem, quando, de onde e o valor anterior; o histórico de cada nota e o relatório de auditoria.', 1, '2026-11-14 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.21.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.21.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 119, 37, 'Histórico de cada nota', 'Ao lado de cada nota, o ícone do relógio mostra quem lançou ou alterou a nota, quando, o valor anterior e de onde veio (tela, importação ou integração).', 1, 'administrador,secretaria,professor', 'auditoria-de-notas-e-faltas', '2026-11-14 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 119);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 120, 37, 'Relatório de auditoria de notas e faltas', 'Em Relatórios, a Auditoria de notas e faltas lista cada mudança, com filtros por curso, disciplina, aluno, quem lançou e período, e a exportação. O registro não pode ser alterado nem apagado por ninguém.', 2, 'administrador', 'auditoria-de-notas-e-faltas', '2026-11-14 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 120);
