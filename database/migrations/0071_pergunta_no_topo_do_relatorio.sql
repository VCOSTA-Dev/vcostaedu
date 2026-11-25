-- A pergunta no topo do relatório (Onda 8, correção, segunda leva, 2.11; D-97). O guia dos relatórios diz que a
-- pergunta de cada relatório fica no topo da tela; a tela a mostrava só na lista dos relatórios e em "Como é
-- calculado". Ela passa a ficar no topo do relatório aberto, logo abaixo do nome.

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.84.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 101, '1.84.0', 1, 84, 0, '2026-11-22', 'A pergunta de cada relatório no topo do relatório aberto.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.84.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.84.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 204, 101, 'A pergunta no topo do relatório', 'Cada relatório aberto mostra, logo abaixo do nome, a pergunta a que ele responde (antes ela aparecia só na lista dos relatórios e em "Como é calculado").', 1, 'administrador,secretaria,professor', 'relatorios-visao-geral', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 204);
