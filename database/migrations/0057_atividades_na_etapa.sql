-- As atividades na tela da etapa (Onda 8, correção, acréscimo B; D-81): na escola, a tela das etapas da disciplina mostra
-- uma coluna por atividade da etapa (as notas, só para leitura), com o link para lançar em cada uma. Só tela.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.70.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 87, '1.70.0', 1, 70, 0, '2026-11-21', 'A tela da etapa com as atividades, lado a lado.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.70.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.70.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 190, 87, 'As atividades na tela da etapa', 'Na escola, a tela das etapas da disciplina mostra as notas de cada atividade da etapa, uma coluna por atividade, antes da média, da recuperação e da nota da etapa. O nome da atividade leva à tela de lançar as notas dela.', 1, 'administrador,secretaria,professor', 'avaliar-na-escola', '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 190);
