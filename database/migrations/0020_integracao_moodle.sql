-- A integração com o Moodle (docs/INTEGRACAO_MOODLE.md): a conexão (o endereço e a chave cifrada, as escolhas da
-- instituição), os vínculos (o aluno e o professor no Moodle; a oferta continua no idMoodle dela), a fila de envios
-- (nunca trava o acadêmico), o registro das chamadas (sem chave, sem senha, sem link, sem dado pessoal), as telas
-- "Conectar ao Moodle" e "Envios ao Moodle" em Integrações, a entrada no Moodle pelo portal, as ações e os parâmetros.

CREATE TABLE IF NOT EXISTS `adms_moodle_conexao` (
  `id` tinyint(4) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT 'o endereço do Moodle (http só em endereço local)',
  `chave_cifrada` text DEFAULT NULL COMMENT 'a chave do serviço, cifrada (AES-256-GCM, o segredo da Config); nunca volta à tela inteira',
  `chave_fim` varchar(8) DEFAULT NULL COMMENT 'os 4 últimos caracteres, para a tela reconhecer a chave',
  `testada_em` datetime DEFAULT NULL COMMENT 'o último "Testar conexão" que deu certo',
  `site_nome` varchar(255) DEFAULT NULL,
  `site_versao` varchar(80) DEFAULT NULL,
  `funcoes_ok` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'quantas das 16 funções o serviço oferece',
  `disciplina_modo` varchar(10) NOT NULL DEFAULT 'criar' COMMENT 'criar | vincular',
  `categoria_id` int(11) DEFAULT NULL COMMENT 'a categoria do Moodle onde as disciplinas são criadas',
  `categoria_nome` varchar(255) DEFAULT NULL,
  `trancamento` varchar(10) NOT NULL DEFAULT 'suspender' COMMENT 'suspender | remover | nada',
  `professor_enviar` tinyint(4) NOT NULL DEFAULT 1,
  `professor_papel` varchar(10) NOT NULL DEFAULT 'edicao' COMMENT 'edicao | sem_edicao',
  `envio` varchar(12) NOT NULL DEFAULT 'automatico' COMMENT 'automatico | botao',
  `ultima_ok_em` datetime DEFAULT NULL COMMENT 'a última chamada que deu certo',
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `adms_moodle_vinculos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(12) NOT NULL COMMENT 'aluno (col_usuarios.id) | professor (adms_usuarios.id) | prof_oferta (a oferta: o professor inscrito por nós)',
  `local_id` int(11) NOT NULL,
  `moodle_id` int(11) NOT NULL,
  `usuario` varchar(100) DEFAULT NULL COMMENT 'o nome de usuário no Moodle (o link de entrada pede o nome de usuário)',
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_moodle_vinculo` (`tipo`, `local_id`, `moodle_id`),
  KEY `idx_moodle_vinculo_local` (`tipo`, `local_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `adms_moodle_envios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(16) NOT NULL COMMENT 'aluno | oferta | enturmar | desenturmar | situacao | professor',
  `chave` varchar(60) NOT NULL COMMENT 'o que o item envia (ex.: aluno:12, enturmar:12:34): um pendente por chave',
  `aluno_id` int(11) DEFAULT NULL,
  `oferta_id` int(11) DEFAULT NULL,
  `situacao` varchar(10) NOT NULL DEFAULT 'pendente' COMMENT 'pendente | enviado | aviso | falhou',
  `tentativas` int(11) NOT NULL DEFAULT 0,
  `motivo` varchar(255) DEFAULT NULL COMMENT 'em linguagem de uso',
  `codigo` varchar(60) DEFAULT NULL COMMENT 'o código do erro do Moodle (o glossário)',
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `enviado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_moodle_envios_situacao` (`situacao`),
  KEY `idx_moodle_envios_chave` (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `adms_moodle_registro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `funcao` varchar(60) NOT NULL,
  `referencia` varchar(60) DEFAULT NULL COMMENT 'a quem se refere (ex.: aluno 12, oferta 34): nunca o dado pessoal',
  `resultado` varchar(10) NOT NULL COMMENT 'ok | aviso | erro',
  `codigo` varchar(60) DEFAULT NULL,
  `ms` int(11) NOT NULL DEFAULT 0,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_moodle_registro_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `adms_moodle_conexao` (`id`) VALUES (1);

-- a demonstração montada antes desta versão pôs um "Id no Moodle" FICTÍCIO nas ofertas dela (9101 a 9106, 9201 a 9204):
-- com o Moodle de verdade conectado, aquele número levaria aluno fictício a uma disciplina real. Sai — só nos cursos da
-- demonstração (os nomes de database/ferramentas/demonstracao_passos.php, CURSOS_DEMO) e só esses números.
UPDATE `rm_professores_materias` o JOIN `rm_cursos` c ON c.`id` = o.`curso_id`
   SET o.`idMoodle` = NULL
 WHERE o.`idMoodle` IN (9101, 9102, 9103, 9104, 9105, 9106, 9201, 9202, 9203, 9204)
   AND c.`nome` IN ('Pós-graduação em Gestão de Projetos', 'Pós-graduação em Docência no Ensino Superior', 'Graduação Fictícia de Demonstração',
                    'Especialização em Gestão Pública Exemplar', 'Especialização em Dados Públicos Exemplar');

-- os parâmetros (avançados): os papéis do Moodle (o serviço não tem função que os liste: os de uma instalação nova),
-- o prefixo dos idnumber e o limite de entradas por aluno
INSERT INTO `adms_parametros` (`chave`, `valor`, `descricao`, `grupo`)
SELECT x.chave, x.valor, x.descricao, 'Integrações' FROM (
  SELECT 'moodle.papel_estudante' chave, '5' valor, 'Moodle (avançado): o número do papel "estudante" (5 numa instalação nova)' descricao
  UNION ALL SELECT 'moodle.papel_professor_edicao', '3', 'Moodle (avançado): o número do papel "professor com edição" (3 numa instalação nova)'
  UNION ALL SELECT 'moodle.papel_professor', '4', 'Moodle (avançado): o número do papel "professor sem edição" (4 numa instalação nova)'
  UNION ALL SELECT 'moodle.prefixo', '', 'Moodle (avançado): um prefixo para o idnumber e o nome de usuário criados no Moodle (vazio = nenhum)'
  UNION ALL SELECT 'moodle.entradas_por_hora', '30', 'Moodle: quantas entradas pelo portal um aluno pode pedir por hora'
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_parametros` p WHERE p.`chave` = x.chave);

-- as ações
INSERT IGNORE INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`) VALUES
(96, 'conectar_ao_moodle_96', 'Conectar ao Moodle', 'Integrações', 'administrador', 82),
(97, 'ver_e_reenviar_os_envios_ao_moodle_97', 'Ver e reenviar os envios ao Moodle', 'Integrações', 'secretaria', 82);

-- as páginas do painel (Integrações): a conexão (só o Administrador) e os envios (até a Secretaria)
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, x.icone, 1, 1, 1, NOW(), x.grava, x.sensivel, NULL, x.acao FROM (
  SELECT 497 id, 'Conectar ao Moodle' menu, 'Moodle' ctl, 'conexao' met, 'moodle' mctl, 'conexao' mmet, 'Conectar ao Moodle' nome, 'O endereço e a chave do Moodle, o teste e as escolhas da instituição.' obs, 'fas fa-graduation-cap' icone, 0 grava, 0 sensivel, 96 acao
  UNION ALL SELECT 498, 'Salvar a conexão com o Moodle', 'Moodle', 'salvar', 'moodle', 'salvar', 'Salvar a conexão com o Moodle', '', NULL, 1, 1, 96
  UNION ALL SELECT 499, 'Testar a conexão com o Moodle', 'Moodle', 'testar', 'moodle', 'testar', 'Testar a conexão com o Moodle', '', NULL, 1, 0, 96
  UNION ALL SELECT 500, 'Desconectar do Moodle', 'Moodle', 'desconectar', 'moodle', 'desconectar', 'Desconectar do Moodle', '', NULL, 1, 1, 96
  UNION ALL SELECT 501, 'Envios ao Moodle', 'MoodleEnvios', 'listar', 'moodle-envios', 'listar', 'Envios ao Moodle', 'A fila: o que foi, o que falta e o motivo de cada falha.', 'fas fa-paper-plane', 0, 0, 97
  UNION ALL SELECT 502, 'Tentar de novo um envio', 'MoodleEnvios', 'tentar', 'moodle-envios', 'tentar', 'Tentar de novo um envio ao Moodle', '', NULL, 1, 0, 97
  UNION ALL SELECT 503, 'Enviar tudo o que falta', 'MoodleEnvios', 'enviarTudo', 'moodle-envios', 'enviar-tudo', 'Enviar ao Moodle tudo o que falta', '', NULL, 1, 0, 97
  UNION ALL SELECT 504, 'Ligar a disciplina ao Moodle', 'MoodleEnvios', 'ligarOferta', 'moodle-envios', 'ligar-oferta', 'Ligar a disciplina a uma do Moodle', '', NULL, 1, 0, 54
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id);

INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 2, p.`id`, 2, 2, 1, n.`id`, p.`id`, NOW() FROM `adms_niveis_acessos` n JOIN `adms_paginas` p ON p.`id` BETWEEN 497 AND 504
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.`id` AND x.`adms_pagina_id` = p.`id`);
-- a conexão: o Administrador, com o item no menu Integrações
UPDATE `adms_nivacs_pgs` d JOIN `adms_niveis_acessos` n ON n.`id` = d.`adms_niveis_acesso_id`
   SET d.`permissao` = 1, d.`lib_menu` = IF(d.`adms_pagina_id` = 497, 1, 2), d.`dropdown` = 1, d.`adms_menu_id` = 11, d.`ordem` = 497
 WHERE d.`adms_pagina_id` BETWEEN 497 AND 500 AND n.`papel` = 'administrador';
-- os envios: o Administrador, a Supervisão de secretaria e a Secretaria
UPDATE `adms_nivacs_pgs` d JOIN `adms_niveis_acessos` n ON n.`id` = d.`adms_niveis_acesso_id`
   SET d.`permissao` = 1, d.`lib_menu` = IF(d.`adms_pagina_id` = 501, 1, 2), d.`dropdown` = 1, d.`adms_menu_id` = 11, d.`ordem` = 498
 WHERE d.`adms_pagina_id` BETWEEN 501 AND 503 AND n.`papel` IN ('administrador', 'supervisao_secretaria', 'secretaria');
-- ligar a disciplina: quem já oferta e indica professores (a ação 54)
UPDATE `adms_nivacs_pgs` d JOIN `adms_nivacs_pgs` e ON e.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND e.`adms_pagina_id` = 93 AND e.`permissao` = 1
   SET d.`permissao` = 1
 WHERE d.`adms_pagina_id` = 504;

-- o portal: entrar no Moodle e abrir uma disciplina no Moodle (o aluno; fora do menu: os botões ficam na tela)
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`)
SELECT x.id, x.menu, 'Moodle', x.met, 'moodle', x.met, x.menu, x.obs, 2, NULL, 1, 1, 1, NOW() FROM (
  SELECT 26 id, 'Entrar no Moodle' menu, 'entrar' met, 'O link de entrada pedido pelo servidor, de uso único: o aluno entra sem senha.' obs
  UNION ALL SELECT 27, 'Abrir a disciplina no Moodle', 'abrir', 'O mesmo link, direto para a disciplina.'
) x WHERE NOT EXISTS (SELECT 1 FROM `col_paginas` p WHERE p.`id` = x.id);
INSERT INTO `col_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 1, 0, 1, 2, 2, 1, p.`id`, NOW() FROM `col_paginas` p
 WHERE p.`id` IN (26, 27) AND NOT EXISTS (SELECT 1 FROM `col_nivacs_pgs` x WHERE x.`adms_niveis_acesso_id` = 1 AND x.`adms_pagina_id` = p.`id`);

-- A versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.34.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 50, '1.34.0', 1, 34, 0, '2026-11-15', 'A integração com o Moodle: conectar pela tela, o envio dos alunos, das disciplinas, da enturmação, do trancamento e do professor por uma fila que nunca trava o acadêmico, e a entrada no Moodle pelo portal, sem senha.', 1, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.34.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.34.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT x.id, 50, x.titulo, x.texto, x.ordem, x.perfis, x.guia, '2026-11-15 00:00:00' FROM (
  SELECT 143 id, 'Conectar ao Moodle' titulo, 'Em Integrações: o endereço e a chave do Moodle, o teste da conexão e o que acontece com as disciplinas, o trancamento e o professor.' texto, 1 ordem, 'administrador' perfis, 'conectar-ao-moodle' guia
  UNION ALL SELECT 144, 'Envios ao Moodle', 'O aluno, a disciplina e a enturmação seguem sozinhos para o Moodle; o que não foi fica na fila, com o motivo e o "Tentar de novo".', 2, 'administrador,secretaria', 'envios-ao-moodle'
  UNION ALL SELECT 145, 'Entrar no Moodle pelo portal', 'O aluno entra no Moodle pelo portal, sem senha, e abre cada disciplina direto.', 3, 'aluno', 'entrar-no-moodle-pelo-portal'
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` n WHERE n.`id` = x.id);
