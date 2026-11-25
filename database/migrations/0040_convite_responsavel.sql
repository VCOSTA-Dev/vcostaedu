-- O convite e o primeiro acesso do responsável (Onda 7, Etapa 3; docs/RESPONSAVEIS.md, D-63). Só na escola. O convite é
-- um link de uso único, com validade (Regras da escola; o padrão, 7 dias): no banco fica só o resumo do código (sha256),
-- nunca o código. Gerar de novo invalida o anterior; a secretaria cancela o convite e bloqueia o acesso. O mesmo
-- registro serve ao "Esqueci a senha" do responsável com e-mail (tipo "senha", 2 horas). Cada convite gerado, enviado,
-- usado e cancelado vai para a auditoria dos responsáveis. O portal ganha o nível "Responsável", que só abre as telas da
-- família. Numa instalação de ensino superior a tabela fica vazia e as páginas do painel são do módulo "escola".

CREATE TABLE IF NOT EXISTS `esc_responsavel_convites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `responsavel_id` int(11) NOT NULL,
  `tipo` varchar(8) NOT NULL DEFAULT 'convite' COMMENT 'convite | senha (o "Esqueci a senha")',
  `resumo` char(64) NOT NULL COMMENT 'sha256 do código; o código não é guardado',
  `criado_em` datetime NOT NULL,
  `expira_em` datetime NOT NULL,
  `enviado_em` datetime DEFAULT NULL COMMENT 'enviado por e-mail',
  `usado_em` datetime DEFAULT NULL,
  `cancelado_em` datetime DEFAULT NULL,
  `criado_por` int(11) DEFAULT NULL COMMENT 'o usuário do painel (vazio: o próprio responsável, pelo Esqueci a senha)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `resumo` (`resumo`),
  KEY `pessoa` (`responsavel_id`, `usado_em`, `cancelado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Os convites de primeiro acesso do responsável (e o link do Esqueci a senha)';

-- o nível do portal: o responsável (só as telas da família)
INSERT INTO `col_niveis_acessos` (`id`, `nome`, `ordem`, `view`, `situacao`, `usuario_cre`, `created`)
SELECT 2, 'Responsável', 2, 'familia', 1, 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `col_niveis_acessos` WHERE `id` = 2);

-- as páginas do portal: as do convite (públicas) e a dos filhos
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.menu, x.obs, x.pub, x.icone, x.grupo, 1, 1, NOW() FROM (
  SELECT 29 id, 'Convite do responsável' menu, 'Convite' ctl, 'abrir' met, 'convite' mctl, 'abrir' mmet, 'O link do convite (o código no fragmento #c=).' obs, 1 pub, NULL icone, 5 grupo
  UNION ALL SELECT 30, 'Conferir o convite', 'Convite', 'conferir', 'convite', 'conferir', 'POST: o nome, os alunos e o formulário da senha.', 1, NULL, 5
  UNION ALL SELECT 31, 'Criar a senha pelo convite', 'Convite', 'definir', 'convite', 'definir', 'POST: a senha; o código vale uma vez só.', 1, NULL, 5
  UNION ALL SELECT 32, 'Meus filhos', 'Familia', 'index', 'familia', 'index', 'O portal do responsável: os alunos que ele acompanha.', 2, 'fas fa-people-roof', 1
) x WHERE NOT EXISTS (SELECT 1 FROM `col_paginas` p WHERE p.`id` = x.id);

-- as permissões do nível "Responsável": os filhos (no menu, em Página Inicial) e sair (no menu Sair)
INSERT INTO `col_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 1, x.ordem, 2, 1, x.menu, 2, x.pg, NOW() FROM (
  SELECT 32 pg, 1 ordem, 1 menu
  UNION ALL SELECT 3, 90, 5
) x WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `col_nivacs_pgs`) y WHERE y.`adms_niveis_acesso_id` = 2 AND y.`adms_pagina_id` = x.pg);

-- a ação e as páginas do painel
INSERT IGNORE INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`) VALUES
(104, 'convidar_e_bloquear_os_responsaveis_104', 'Convidar e bloquear os responsáveis', 'Alunos', 'secretaria', 18);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, 'Responsaveis', x.met, 'responsaveis', x.mmet, x.nome, x.obs, 2, NULL, 1, 1, 1, NOW(), 1, 0, NULL, 104 FROM (
  SELECT 545 id, 'Gerar convite' menu, 'convidar' met, 'convidar' mmet, 'Gerar o convite do responsável' nome, 'POST. O link de uso único; o anterior deixa de valer.' obs
  UNION ALL SELECT 546, 'Enviar o convite por e-mail', 'enviarConvite', 'enviar-convite', 'Enviar o convite por e-mail', 'POST. O convite que acabou de ser gerado.'
  UNION ALL SELECT 547, 'Cancelar o convite', 'cancelarConvite', 'cancelar-convite', 'Cancelar o convite', 'POST.'
  UNION ALL SELECT 548, 'Bloquear o acesso', 'bloquear', 'bloquear', 'Bloquear ou desbloquear o acesso do responsável', 'POST. Com o motivo.'
  UNION ALL SELECT 549, 'Convidar uma turma', 'convidarTurma', 'convidar-turma', 'Convidar os responsáveis de uma turma', 'Em passos: o envio por e-mail e a lista do envio manual.'
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id);
INSERT IGNORE INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`) VALUES (545, 'escola'), (546, 'escola'), (547, 'escola'), (548, 'escola'), (549, 'escola');

-- as permissões: as de cadastrar alunos, nível a nível (fora do menu: os botões ficam na lista e na ficha)
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, p.`id`, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` IN (545, 546, 547, 548, 549)
 WHERE d.`adms_pagina_id` = 47
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`);

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.53.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 70, '1.53.0', 1, 53, 0, '2026-11-18', 'O convite do responsável: o link de uso único, o primeiro acesso ao portal, o bloqueio e os convites de uma turma.', 1, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.53.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.53.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 173, 70, 'O convite do responsável', 'Na escola, na ficha do responsável e na lista: "Gerar convite" cria um link de uso único, com prazo, para a pessoa criar a senha e entrar no portal. Depois de gerar, envie por e-mail, copie o link ou abra o WhatsApp com a mensagem pronta (o texto fica em Regras da escola). Também: cancelar o convite, bloquear o acesso e convidar todos os responsáveis de uma turma de uma vez.', 1, 'administrador,secretaria', 'convidar-um-responsavel', '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 173);
