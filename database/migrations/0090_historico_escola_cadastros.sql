-- O HISTÓRICO ESCOLAR DA EDUCAÇÃO BÁSICA, Parte A (D-110; docs/HISTORICO_ESCOLAR.md): os cadastros e os textos.
--   Só tabelas novas da escola (esc_hist_*), vazias no ensino superior; nenhuma coluna nova em tabela que o superior usa
--   (a tabela do aluno, as séries e os segmentos são compartilhados: o que é só do histórico mora aqui, ligado pelo id).
--   - o nível do documento de cada segmento (Fundamental ou Médio: o histórico do Fundamental junta os segmentos marcados)
--     e o número de cada série (1 a 9 no Fundamental, 1 a 3 no Médio);
--   - os textos do documento por nível e vigência (vale o de maior "vigente desde" até a data do documento), com os
--     marcadores do fecho; nenhum texto vem pronto: os rótulos de cada estado ou rede são texto do cadastro;
--   - as observações (padrão ou selecionáveis por série), o logo por nível, o cadastro de escolas, o nome e a ordem de
--     cada disciplina no histórico, o RA estadual e a certidão do aluno;
--   - o registro de alterações "de → para", que grava só o que mudou;
--   - o registro do que é dado de prova (o selo PROVA nas listas).
-- COMO DESFAZER: apagar as páginas 620 a 628 (adms_paginas, adms_paginas_modulo, adms_nivacs_pgs), a ação 130 e as
-- tabelas esc_hist_segmento, esc_hist_serie, esc_hist_texto, esc_hist_observacao, esc_hist_logo, esc_hist_escola,
-- esc_hist_disciplina, esc_hist_aluno, esc_hist_alteracao e esc_hist_dado_prova.

CREATE TABLE IF NOT EXISTS `esc_hist_segmento` (
  `curso_id` int(11) NOT NULL COMMENT 'rm_cursos.id (o segmento)',
  `nivel` varchar(2) NOT NULL DEFAULT '' COMMENT 'EF = Ensino Fundamental | EM = Ensino Médio | vazio = fora do histórico',
  PRIMARY KEY (`curso_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O nível do histórico de cada segmento';

CREATE TABLE IF NOT EXISTS `esc_hist_serie` (
  `modulo_id` int(11) NOT NULL COMMENT 'rm_cursos_turma_series.id (a série)',
  `numero` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1 a 9 no Fundamental, 1 a 3 no Médio (0 = sem número)',
  PRIMARY KEY (`modulo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O número de cada série no histórico';

CREATE TABLE IF NOT EXISTS `esc_hist_texto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nivel` varchar(2) NOT NULL COMMENT 'EF | EM',
  `vigente_desde` date NOT NULL,
  `orgao_linha1` varchar(200) NOT NULL DEFAULT '',
  `orgao_linha2` varchar(200) NOT NULL DEFAULT '',
  `orgao_linha3` varchar(200) NOT NULL DEFAULT '',
  `escola_nome` varchar(200) NOT NULL DEFAULT '' COMMENT 'vazio = o nome da instituição',
  `escola_endereco` varchar(300) NOT NULL DEFAULT '' COMMENT 'vazio = o endereço da instituição',
  `ato_escola` varchar(500) NOT NULL DEFAULT '',
  `ato_curso` varchar(500) NOT NULL DEFAULT '',
  `turno` varchar(60) NOT NULL DEFAULT '',
  `escala` text DEFAULT NULL,
  `fecho_declaracao` text DEFAULT NULL COMMENT 'marcadores {escola} {aluno} {ra} {rg} {serie} {ano} {etapa}',
  `fecho_certificado` text DEFAULT NULL,
  `cidade` varchar(120) NOT NULL DEFAULT '' COMMENT 'vazio = a cidade da instituição',
  `secretaria_nome` varchar(120) NOT NULL DEFAULT '',
  `secretaria_doc` varchar(60) NOT NULL DEFAULT '',
  `diretora_nome` varchar(120) NOT NULL DEFAULT '',
  `diretora_doc` varchar(60) NOT NULL DEFAULT '',
  `titulo_secretaria` varchar(60) NOT NULL DEFAULT '',
  `titulo_diretora` varchar(60) NOT NULL DEFAULT '',
  `rotulo_registro` varchar(40) NOT NULL DEFAULT '' COMMENT 'o rótulo do registro do aluno na rede (ex.: R.A.); vazio = a linha não sai',
  `rotulo_publicacao` varchar(120) NOT NULL DEFAULT '' COMMENT 'o rótulo do número de publicação dos concluintes; vazio = a linha não sai',
  `unidade_carga` varchar(6) NOT NULL DEFAULT 'aulas' COMMENT 'aulas | horas: a carga que o quadro mostra',
  `por` int(11) NOT NULL DEFAULT 0,
  `em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nivel_vigencia` (`nivel`, `vigente_desde`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Os textos do histórico por nível e vigência';

CREATE TABLE IF NOT EXISTS `esc_hist_observacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nivel` varchar(2) NOT NULL,
  `chave` varchar(40) NOT NULL COMMENT 'estável (conselho = a do conselho de classe)',
  `titulo` varchar(120) NOT NULL,
  `texto` text NOT NULL COMMENT 'marcadores {serie} {ano}',
  `tipo` varchar(12) NOT NULL DEFAULT 'padrao' COMMENT 'padrao = sai sempre | selecionavel = marcada por série',
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nivel_chave` (`nivel`, `chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As observações do histórico';

CREATE TABLE IF NOT EXISTS `esc_hist_logo` (
  `nivel` varchar(2) NOT NULL,
  `imagem` mediumblob NOT NULL,
  `mime` varchar(20) NOT NULL,
  `largura` int(11) NOT NULL DEFAULT 0,
  `altura` int(11) NOT NULL DEFAULT 0,
  `sha256` varchar(64) NOT NULL,
  `por` int(11) NOT NULL DEFAULT 0,
  `em` datetime NOT NULL,
  PRIMARY KEY (`nivel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O logo do histórico por nível (sem linha: o logo da instituição)';

CREATE TABLE IF NOT EXISTS `esc_hist_escola` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(200) NOT NULL,
  `municipio` varchar(120) NOT NULL,
  `uf` char(2) NOT NULL,
  `rede` varchar(30) NOT NULL DEFAULT '',
  `esta_escola` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = a própria escola (uma só)',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As escolas onde o aluno estudou';

CREATE TABLE IF NOT EXISTS `esc_hist_disciplina` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `materia_id` int(11) NOT NULL DEFAULT 0 COMMENT 'rm_materias.id (0 = criada na hora, para um ano cursado fora)',
  `nome` varchar(160) NOT NULL COMMENT 'o nome que sai no histórico',
  `base` varchar(14) NOT NULL DEFAULT 'comum' COMMENT 'comum | diversificada (a criada na hora)',
  `religioso` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = é o Ensino Religioso (linha própria no histórico)',
  `ordem_ef` int(11) NOT NULL DEFAULT 0 COMMENT '0 = depois das ordenadas, na ordem da matriz',
  `ordem_em` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `materia` (`materia_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O nome e a ordem de cada disciplina no histórico';

CREATE TABLE IF NOT EXISTS `esc_hist_aluno` (
  `aluno_id` int(11) NOT NULL COMMENT 'col_usuarios.id',
  `ra` varchar(30) NOT NULL DEFAULT '' COMMENT 'o registro do aluno na rede estadual (o RM é o número da escola)',
  `certidao` varchar(300) NOT NULL DEFAULT '' COMMENT 'a certidão de nascimento: local e número',
  `ef_conclusao_ano` int(11) NOT NULL DEFAULT 0,
  `ef_conclusao_escola_id` int(11) NOT NULL DEFAULT 0,
  `ef_publicacao` varchar(60) NOT NULL DEFAULT '',
  `obs_livre` text DEFAULT NULL,
  PRIMARY KEY (`aluno_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Os dados do aluno que só o histórico usa';

CREATE TABLE IF NOT EXISTS `esc_hist_alteracao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL DEFAULT 0,
  `quando` datetime NOT NULL,
  `tabela` varchar(40) NOT NULL,
  `registro_id` int(11) NOT NULL DEFAULT 0,
  `aluno_id` int(11) NOT NULL DEFAULT 0,
  `acao` varchar(12) NOT NULL COMMENT 'incluir | alterar | excluir | travar | destravar',
  `antes` longtext DEFAULT NULL CHECK (`antes` IS NULL OR json_valid(`antes`)),
  `depois` longtext DEFAULT NULL CHECK (`depois` IS NULL OR json_valid(`depois`)),
  `motivo` varchar(500) NOT NULL DEFAULT '',
  `texto` text NOT NULL COMMENT 'o que mudou, em texto legível: "campo: de → para"',
  PRIMARY KEY (`id`),
  KEY `aluno` (`aluno_id`, `id`),
  KEY `registro` (`tabela`, `registro_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O registro de alterações do histórico: só o que mudou';

CREATE TABLE IF NOT EXISTS `esc_hist_dado_prova` (
  `tabela` varchar(40) NOT NULL,
  `registro_id` int(11) NOT NULL,
  `rotulo` varchar(80) NOT NULL DEFAULT '',
  PRIMARY KEY (`tabela`, `registro_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O que é dado de prova (o selo PROVA nas listas)';

-- a ação e as páginas dos cadastros (só a escola)
INSERT IGNORE INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`) VALUES
(130, 'cadastros_do_historico_escolar_130', 'Os cadastros, a matriz e a vida escolar do histórico escolar', 'Documentos', 'secretaria', 40);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `consulta_grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, x.icone, 1, 1, 1, NOW(), x.grava, 0, 0, NULL, 130 FROM (
  SELECT 620 id, 'Cadastros do histórico' menu, 'HistoricoEscola' ctl, 'cadastros' met, 'historico-escola' mctl, 'cadastros' mmet, 'Cadastros do histórico escolar' nome, 'Os textos, as observações, o logo, as escolas, as disciplinas, os segmentos e as séries, e o registro de alterações.' obs, 'fas fa-scroll' icone, 0 grava
  UNION ALL SELECT 621, 'Salvar os textos do histórico', 'HistoricoEscola', 'salvarTexto', 'historico-escola', 'salvar-texto', 'Salvar os textos do histórico', 'POST: uma vigência dos textos de um nível.', NULL, 1
  UNION ALL SELECT 622, 'Salvar a observação do histórico', 'HistoricoEscola', 'salvarObservacao', 'historico-escola', 'salvar-observacao', 'Salvar a observação do histórico', 'POST.', NULL, 1
  UNION ALL SELECT 623, 'Enviar o logo do histórico', 'HistoricoEscola', 'logo', 'historico-escola', 'logo', 'Enviar ou retirar o logo do histórico', 'POST: PNG ou JPEG até 1 MB, por nível.', NULL, 1
  UNION ALL SELECT 624, 'Salvar a escola', 'HistoricoEscola', 'salvarEscola', 'historico-escola', 'salvar-escola', 'Salvar a escola do cadastro de escolas', 'POST.', NULL, 1
  UNION ALL SELECT 625, 'Salvar as disciplinas do histórico', 'HistoricoEscola', 'salvarDisciplinas', 'historico-escola', 'salvar-disciplinas', 'Salvar o nome e a ordem das disciplinas no histórico', 'POST: a tela inteira.', NULL, 1
  UNION ALL SELECT 626, 'Salvar os segmentos e as séries', 'HistoricoEscola', 'salvarSeries', 'historico-escola', 'salvar-series', 'Salvar o nível dos segmentos e o número das séries', 'POST: a tela inteira.', NULL, 1
  UNION ALL SELECT 627, 'Dados do aluno para o histórico', 'HistoricoEscola', 'aluno', 'historico-escola', 'aluno', 'Dados do aluno para o histórico', 'O RA estadual, a certidão de nascimento e a observação livre.', NULL, 0
  UNION ALL SELECT 628, 'Salvar os dados do aluno', 'HistoricoEscola', 'salvarAluno', 'historico-escola', 'salvar-aluno', 'Salvar os dados do aluno para o histórico', 'POST.', NULL, 1
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id) ORDER BY x.id;
INSERT INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`)
SELECT p.`id`, 'escola' FROM `adms_paginas` p
 WHERE p.`id` BETWEEN 620 AND 628 AND NOT EXISTS (SELECT 1 FROM `adms_paginas_modulo` m WHERE m.`adms_pagina_id` = p.`id`) ORDER BY p.`id`;
-- as linhas de permissão, nível a nível (as do "Ano letivo e etapas", 509, com o menu Ensino): abrem o administrador, a
-- direção, a secretaria e a supervisão de secretaria; a direção só consulta (as páginas que gravam ficam fora dela)
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT IF(d.`adms_niveis_acesso_id` IN (1, 3, 4, 10), 1, 2), p.`id`, d.`dropdown`, IF(p.`id` = 620 AND d.`adms_niveis_acesso_id` IN (1, 3, 4, 10), 1, 2), d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` BETWEEN 620 AND 628
 WHERE d.`adms_pagina_id` = 509
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`; -- a ordem dos ids, fixa
