-- O fechamento da correção (Onda 8, correção; D-86). O horário padrão de aulas (D-71) é só da escola: a 0048 passa a
-- gravá-lo só onde a instalação já é uma escola, e o instalador (ou a troca de tipo pelo painel) grava ao escolher
-- "escola" (core/HorarioPadrao.php). Aqui, numa instalação de ensino superior que já tinha recebido o padrão, ele sai:
-- só as aulas do padrão que ninguém editou e que nenhum horário de professor usa. A grafia "Vespertino" (a 0048) fica.

DELETE a FROM `rm_professores_horario_aula` a
  JOIN (
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
) p ON p.nome = a.`nome` AND p.periodo = a.`periodo` AND p.inicio = a.`inicio` AND p.termino = a.`termino`
 WHERE a.`usuario_mod` IS NULL
   AND NOT EXISTS (SELECT 1 FROM `rm_professores_horarios` h WHERE h.`horario_id` = a.`id`)
   AND NOT EXISTS (SELECT 1 FROM `adms_instalacao_estado` WHERE `chave` = 'instituicao.tipo' AND `valor` = 'escola');

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.73.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 90, '1.73.0', 1, 73, 0, '2026-11-21', 'O fechamento da correção: o horário padrão de aulas só na escola.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.73.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.73.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 193, 90, 'O horário padrão, só na escola', 'O horário padrão de aulas (as aulas do dia de cada turno) vem pronto só na escola. No ensino superior, Horários de aula começa vazio, como antes: cadastre as aulas do dia de cada turno, se precisar delas.', 1, 'administrador,secretaria', NULL, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 193);
