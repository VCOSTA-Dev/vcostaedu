-- Uma conexão com o banco por requisição (Onda 6, Etapa 1; DUVIDAS 270). O painel, o portal e a API abriam uma conexão
-- a cada consulta, dezenas por tela; agora abrem uma, reaproveitada por todas as consultas da tela (core/Banco.php).
-- Nenhuma tabela muda: esta etapa só registra a versão e a novidade.

-- A versão e a novidade
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.43.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 60, '1.43.0', 1, 43, 0, '2026-11-17', 'Uma conexão com o banco por tela, no lugar de uma por consulta: as telas abrem mais depressa e o servidor de banco recebe muito menos conexões. Nada muda no que a tela mostra.', 1, '2026-11-17 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.43.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.43.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 155, 60, 'As telas mais rápidas', 'Cada tela passou a usar uma conexão só com o banco de dados, no lugar de uma por consulta. As telas mais carregadas (a ficha do aluno, as notas e o boletim) abrem várias vezes mais depressa; nada muda no que elas mostram.', 1, 'administrador,secretaria,professor,aluno', NULL, '2026-11-17 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 155);
