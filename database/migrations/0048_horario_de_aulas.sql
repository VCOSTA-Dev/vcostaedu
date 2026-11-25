-- O horário de aulas, completo (Onda 8, correção, Parte 1.1; D-71). As aulas do dia (rm_professores_horario_aula)
-- ganham a tela de cadastro e edição por turno; a instalação nova nasce com um horário padrão por turno, que a
-- secretaria edita. Na escola, o horário da turma na semana (dia, aula, disciplina e professor) se monta pela tela da
-- turma: cada escolha grava na hora. O portal do aluno e o do responsável mostram a semana; sem horário, a mensagem é
-- "ainda não há horário cadastrado".

-- o nome do turno 2 vinha com a grafia errada
UPDATE `rm_cursos_periodos` SET `nome` = 'Vespertino' WHERE `id` = 2 AND `nome` = 'Verspertino';

-- o horário padrão de cada turno, só onde o turno ainda não tem nenhuma aula e SÓ NA ESCOLA (D-86): numa instalação
-- nova, a migration roda antes da escolha do tipo, e quem grava o padrão é o instalador ao gravar "escola"
-- (core/HorarioPadrao.php, a mesma lista); aqui, as escolas que já existiam
INSERT INTO `rm_professores_horario_aula` (`nome`, `periodo`, `inicio`, `termino`, `situacao`, `usuario_cre`, `created`)
SELECT x.nome, x.periodo, x.inicio, x.termino, 1, 1, NOW() FROM (
            SELECT '1ª aula' nome, 1 periodo, '07:00:00' inicio, '07:50:00' termino
  UNION ALL SELECT '2ª aula', 1, '07:50:00', '08:40:00'
  UNION ALL SELECT '3ª aula', 1, '08:40:00', '09:30:00'
  UNION ALL SELECT '4ª aula', 1, '09:50:00', '10:40:00'
  UNION ALL SELECT '5ª aula', 1, '10:40:00', '11:30:00'
  UNION ALL SELECT '6ª aula', 1, '11:30:00', '12:20:00'
  UNION ALL SELECT '1ª aula', 2, '13:00:00', '13:50:00'
  UNION ALL SELECT '2ª aula', 2, '13:50:00', '14:40:00'
  UNION ALL SELECT '3ª aula', 2, '14:40:00', '15:30:00'
  UNION ALL SELECT '4ª aula', 2, '15:50:00', '16:40:00'
  UNION ALL SELECT '5ª aula', 2, '16:40:00', '17:30:00'
  UNION ALL SELECT '6ª aula', 2, '17:30:00', '18:20:00'
  UNION ALL SELECT '1ª aula', 3, '19:00:00', '19:50:00'
  UNION ALL SELECT '2ª aula', 3, '19:50:00', '20:40:00'
  UNION ALL SELECT '3ª aula', 3, '20:50:00', '21:40:00'
  UNION ALL SELECT '4ª aula', 3, '21:40:00', '22:30:00'
) x WHERE NOT EXISTS (SELECT 1 FROM (SELECT `periodo` FROM `rm_professores_horario_aula`) h WHERE h.`periodo` = x.periodo)
  AND EXISTS (SELECT 1 FROM `adms_instalacao_estado` WHERE `chave` = 'instituicao.tipo' AND `valor` = 'escola');

-- as páginas: as aulas do dia (os dois tipos de instituição) e o horário da turma (só na escola)
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, NULL, 1, 1, 1, NOW(), x.grava, 0, NULL, x.acao FROM (
  SELECT 553 id, 'Horários de aula' menu, 'HorariosAula' ctl, 'listar' met, 'horarios-aula' mctl, 'listar' mmet, 'Horários de aula' nome, 'As aulas do dia de cada turno: o nome, o início e o fim.' obs, 0 grava, 43 acao
  UNION ALL SELECT 554, 'Salvar o horário de aula', 'HorariosAula', 'salvar', 'horarios-aula', 'salvar', 'Cadastrar ou editar um horário de aula', 'POST. O turno, o nome, o início e o fim.', 1, 54
  UNION ALL SELECT 555, 'Ativar ou desativar o horário de aula', 'HorariosAula', 'situacao', 'horarios-aula', 'situacao', 'Ativar ou desativar um horário de aula', 'POST. O desativado sai da grade; o que já foi montado fica guardado.', 1, 54
  UNION ALL SELECT 556, 'Horário da turma', 'Turmas', 'horario', 'turmas', 'horario', 'O horário da turma na semana', 'A grade: as aulas do turno da turma nos dias da semana, com a disciplina e o professor.', 0, 43
  UNION ALL SELECT 557, 'Gravar o horário da turma', 'Turmas', 'gravarHorario', 'turmas', 'gravar-horario', 'Gravar uma aula no horário da turma', 'POST, em JSON. Cada escolha grava na hora.', 1, 54
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id);
INSERT IGNORE INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`) VALUES (556, 'escola'), (557, 'escola');

-- as permissões: as de ver a turma (515) e as de editar a turma (514), nível a nível (quem cuida das turmas cuida do
-- horário: a secretaria também); fora do menu (os botões ficam na
-- tela dos horários e na da turma)
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, p.`id`, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON (d.`adms_pagina_id` = 515 AND p.`id` IN (553, 556)) OR (d.`adms_pagina_id` = 514 AND p.`id` IN (554, 555, 557))
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`; -- a ordem dos ids, fixa (o retrato da régua compara linha a linha)

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.61.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 78, '1.61.0', 1, 61, 0, '2026-11-21', 'O horário de aulas completo: as aulas do dia editáveis, o horário da turma na semana e a semana no portal.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.61.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.61.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 181, 78, 'O horário de aulas', 'Em Horários de aula, as aulas do dia de cada turno, com o início e o fim (na escola, a instalação já vem com um horário padrão, que você edita). Na escola, na tela da turma, o botão "Horário" abre a grade da semana: escolha a disciplina de cada aula e ela grava na hora. O aluno e o responsável veem a semana no portal.', 1, 'administrador,secretaria', 'montar-o-horario-da-turma', '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 181);
