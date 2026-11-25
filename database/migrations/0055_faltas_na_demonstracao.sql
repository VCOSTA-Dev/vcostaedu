-- As faltas na demonstração do colégio (Onda 8, correção, Parte 3.3; D-78): os alunos de 2026 têm de 0 a 2 faltas por
-- bimestre em cada disciplina, sempre as mesmas, sem ninguém perto da reprovação por falta. Só a montagem.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.68.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 85, '1.68.0', 1, 68, 0, '2026-11-21', 'A demonstração do colégio com faltas no ano corrente.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.68.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.68.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 188, 85, 'Faltas na demonstração do colégio', 'Na demonstração do colégio, os alunos de 2026 têm algumas faltas em cada bimestre (de 0 a 2 por disciplina), e ninguém fica perto da reprovação por falta: a frequência aparece no boletim, nos relatórios e no portal.', 1, 'administrador', 'conhecer-com-a-demonstracao', '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 188);
