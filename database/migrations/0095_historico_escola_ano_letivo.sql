-- O HISTÓRICO ESCOLAR DA EDUCAÇÃO BÁSICA, Parte H (D-117; docs/HISTORICO_ESCOLAR.md): a saída no meio do ano e o ano
-- letivo fechado.
--   O marcador explícito do ano letivo fechado (o documento fechado, 2.19: "prefira um marcador explícito"): a secretaria
--   ou a direção FECHA o ano letivo; reabrir pede o motivo; cada ação fica no histórico do marcador. Sem o marcador, vale
--   o critério de antes (todas as disciplinas da turma encerradas). A ação 132, da secretaria e da direção (a marca
--   consulta_grava deixa a direção, que só consulta, gravar nestas páginas).
--   A data da saída do aluno no meio do ano é a da saída da turma (esc_turma_alunos.saida), agora DIGITADA na tela da
--   turma (antes, a hora do clique): nenhuma coluna nova.
-- COMO DESFAZER: apagar as páginas 656 a 658 (adms_paginas, adms_paginas_modulo, adms_nivacs_pgs), a ação 132 e as
-- tabelas esc_ano_letivo_fechamento e esc_ano_letivo_fechamento_hist.

INSERT IGNORE INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`) VALUES
(132, 'fechar_e_reabrir_o_ano_letivo_132', 'Fechar e reabrir o ano letivo (histórico escolar)', 'Documentos', 'secretaria', 42);

CREATE TABLE IF NOT EXISTS `esc_ano_letivo_fechamento` (
  `ano` int(11) NOT NULL,
  `situacao` varchar(10) NOT NULL COMMENT 'fechado | reaberto',
  `por` int(11) NOT NULL DEFAULT 0,
  `em` datetime NOT NULL,
  PRIMARY KEY (`ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O marcador do ano letivo fechado (o histórico escolar)';

CREATE TABLE IF NOT EXISTS `esc_ano_letivo_fechamento_hist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ano` int(11) NOT NULL,
  `acao` varchar(10) NOT NULL COMMENT 'fechar | reabrir',
  `motivo` varchar(500) NOT NULL DEFAULT '',
  `por` int(11) NOT NULL DEFAULT 0,
  `em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ano` (`ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quem fechou e reabriu o ano letivo, quando e por quê';

INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `consulta_grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, x.icone, 1, 1, 1, NOW(), x.grava, x.cg, 0, NULL, 132 FROM (
  SELECT 656 id, 'Ano letivo do histórico' menu, 'HistoricoEscola' ctl, 'anoLetivo' met, 'historico-escola' mctl, 'ano-letivo' mmet, 'O ano letivo: fechar e reabrir' nome, 'Os anos letivos da escola, fechados ou abertos, com o histórico do marcador.' obs, 'fas fa-lock' icone, 0 grava, 0 cg
  UNION ALL SELECT 657, 'Fechar o ano letivo', 'HistoricoEscola', 'fecharAno', 'historico-escola', 'fechar-ano', 'Fechar o ano letivo', 'POST: o marcador do ano fechado.', NULL, 1, 1
  UNION ALL SELECT 658, 'Reabrir o ano letivo', 'HistoricoEscola', 'reabrirAno', 'historico-escola', 'reabrir-ano', 'Reabrir o ano letivo', 'POST: com o motivo.', NULL, 1, 1
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id) ORDER BY x.id;
INSERT INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`)
SELECT p.`id`, 'escola' FROM `adms_paginas` p
 WHERE p.`id` BETWEEN 656 AND 658 AND NOT EXISTS (SELECT 1 FROM `adms_paginas_modulo` m WHERE m.`adms_pagina_id` = p.`id`) ORDER BY p.`id`;
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT IF(d.`adms_niveis_acesso_id` IN (1, 3, 4, 10), 1, 2), p.`id`, d.`dropdown`, IF(p.`id` = 656 AND d.`adms_niveis_acesso_id` IN (1, 3, 4, 10), 1, 2), d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` BETWEEN 656 AND 658
 WHERE d.`adms_pagina_id` = 46
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`;
