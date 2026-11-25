-- A coordenação do segmento na demonstração do colégio (Onda 8, correção, segunda leva, 2.3; D-89). A coordenadora
-- fictícia coordena os anos finais (a montagem a indica pela tela do segmento, como no ensino superior): vê as turmas,
-- a estrutura e os relatórios desse segmento e é barrada no Ensino Médio. A turma de outro segmento passa a dar a
-- recusa do recorte ("Esta turma é de um segmento que você não coordena"), como as outras telas, em vez de "não
-- encontrada". Nenhuma tabela muda.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.76.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 93, '1.76.0', 1, 76, 0, '2026-11-21', 'Na escola, a coordenação vê o segmento que coordena e é barrada nos outros; na demonstração, a coordenadora dos anos finais.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.76.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.76.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 196, 93, 'A coordenação do segmento', 'Na escola, quem coordena um segmento vê as turmas, a estrutura e os relatórios dele; os outros segmentos ficam fora. Na demonstração do colégio, a coordenadora fictícia coordena os anos finais.', 1, 'administrador,professor', NULL, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 196);
