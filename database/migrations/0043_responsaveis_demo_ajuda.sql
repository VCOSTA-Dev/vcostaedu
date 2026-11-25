-- A demonstração, os primeiros passos e a ajuda dos responsáveis (Onda 7, Etapa 6; docs/RESPONSAVEIS.md). Só na escola:
-- a fase "Os responsáveis e o convite" dos Primeiros passos (marcada sozinha quando há um papel designado e um convite
-- gerado) e dois relatórios: os alunos sem o responsável financeiro ou sem o titular no ano, por turma; e os acessos
-- acadêmicos adicionais em vigor, com o motivo e quem liberou.

-- os Primeiros passos: a fase nova vem depois de enturmar (a última até aqui)
INSERT INTO `adms_fases` (`codigo`, `ordem`, `titulo`, `texto`, `feito`, `convite`, `rota`, `rotulo_rota`, `roteiro`, `condicao`, `depende_de`, `pode_pular`, `modulo`, `situacao`)
SELECT 'esc_responsaveis', 90, 'Os responsáveis e o convite', 'O responsável financeiro e o acadêmico titular de cada aluno no ano, e o convite para a família entrar no portal.', 'Você já designou os responsáveis e gerou um convite. Os primeiros passos estão completos.', 'Agora vamos designar os responsáveis dos alunos e convidar a família para o portal.', 'responsaveis/listar', 'Abrir os responsáveis', 'fase-escola-responsaveis',
       'SELECT (SELECT COUNT(*) FROM esc_responsavel_designacoes) > 0 AND (SELECT COUNT(*) FROM esc_responsavel_convites) > 0', 'esc_alunos', 1, 'escola', 1
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_fases` WHERE `codigo` = 'esc_responsaveis');
UPDATE `adms_fases` SET `feito` = 'Você já enturmou os alunos.' WHERE `codigo` = 'esc_enturmar' AND `feito` = 'Você já enturmou os alunos. Os primeiros passos estão completos.';

-- os relatórios (a mesma ação e as mesmas permissões de "Qualidade do cadastro")
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, 'Relatorios', x.met, 'relatorios', x.mmet, x.nome, 'Relatório (Onda 7).', 2, NULL, 5, 1, 1, NOW(), 0, 0, 'relatorio', 61 FROM (
  SELECT 550 id, 'Alunos sem responsável financeiro ou sem titular' menu, 'responsaveisFaltando' met, 'responsaveis-faltando' mmet, 'R-SEC-05 Alunos sem responsável financeiro ou sem titular' nome
  UNION ALL SELECT 551, 'Acessos acadêmicos adicionais em vigor', 'acessosAdicionais', 'acessos-adicionais', 'R-AUD-05 Acessos acadêmicos adicionais em vigor'
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id);
INSERT IGNORE INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`) VALUES (550, 'escola'), (551, 'escola');
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, p.`id`, d.`dropdown`, d.`lib_menu`, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` IN (550, 551)
 WHERE d.`adms_pagina_id` = 445
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`);

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.56.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 73, '1.56.0', 1, 56, 0, '2026-11-18', 'Os responsáveis na demonstração da escola, nos Primeiros passos, na ajuda e em dois relatórios.', 1, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.56.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.56.0';

-- a novidade
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 176, 73, 'Os responsáveis nos relatórios e nos Primeiros passos', 'Na escola, dois relatórios novos: os alunos sem o responsável financeiro ou sem o titular no ano, por turma, e os acessos acadêmicos adicionais em vigor, com o motivo e quem liberou. Os Primeiros passos ganham a fase dos responsáveis e do convite, e a demonstração do colégio tem responsáveis fictícios para entrar no portal.', 1, 'administrador,secretaria', 'o-que-cada-papel-ve', '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 176);
