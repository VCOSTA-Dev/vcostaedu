-- O HISTÓRICO ESCOLAR DA EDUCAÇÃO BÁSICA, Parte G (D-116; docs/HISTORICO_ESCOLAR.md): o modelo do Médio.
--   O itinerário formativo do aluno no ano fica na MATRÍCULA DO ANO (a linha do aluno na turma, esc_turma_alunos),
--   numa tabela própria ligada a ela pelo id: a secretaria escolhe o itinerário na tela da turma (só nas turmas do
--   Médio cujo ano tem itinerário formativo na matriz do histórico); o histórico lê dali, sem proposta a confirmar.
-- COMO DESFAZER: apagar a página 647 (adms_paginas, adms_paginas_modulo, adms_nivacs_pgs) e a tabela
-- esc_hist_itinerario_aluno.

CREATE TABLE IF NOT EXISTS `esc_hist_itinerario_aluno` (
  `turma_aluno_id` int(11) NOT NULL COMMENT 'esc_turma_alunos.id (o aluno na turma do ano)',
  `itinerario` tinyint(4) NOT NULL COMMENT 'o número do itinerário formativo da matriz do ano',
  `por` int(11) NOT NULL DEFAULT 0,
  `em` datetime NOT NULL,
  PRIMARY KEY (`turma_aluno_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O itinerário formativo do aluno na matrícula do ano (o Médio)';

-- a página que grava o itinerário dos alunos da turma (só a escola; a ação 130, a de gravar o histórico)
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `consulta_grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, x.icone, 1, 1, 1, NOW(), x.grava, 0, 0, NULL, 130 FROM (
  SELECT 647 id, 'Itinerário na turma' menu, 'HistoricoEscolaVida' ctl, 'itinerarioTurma' met, 'historico-escola-vida' mctl, 'itinerario-turma' mmet, 'O itinerário formativo dos alunos da turma' nome, 'POST: o itinerário de cada aluno na matrícula do ano (o Médio).' obs, NULL icone, 1 grava
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id) ORDER BY x.id;
INSERT INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`)
SELECT p.`id`, 'escola' FROM `adms_paginas` p
 WHERE p.`id` = 647 AND NOT EXISTS (SELECT 1 FROM `adms_paginas_modulo` m WHERE m.`adms_pagina_id` = p.`id`) ORDER BY p.`id`;
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT IF(d.`adms_niveis_acesso_id` IN (1, 3, 4, 10), 1, 2), p.`id`, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` = 647
 WHERE d.`adms_pagina_id` = 46
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`;
