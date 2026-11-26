-- A VERSÃO 1.101.0: o histórico escolar da educação básica (D-110 a D-117; docs/HISTORICO_ESCOLAR.md).
--   As migrations do histórico são as 0090 a 0095 (os números 0088 e 0089 ficaram livres: o runner aplica as pendentes
--   pela ordem do nome, e o que falta no meio não muda nada). Esta só registra a versão e as novidades.
-- COMO DESFAZER: apagar as novidades 226 e 227 e a versão 118, e voltar a 1.100.0 para a situação 1.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.101.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 118, '1.101.0', 1, 101, 0, '2026-11-25', 'O histórico escolar da educação básica: os cadastros, a matriz curricular por ano, a vida escolar do aluno, os modelos do Ensino Fundamental e do Ensino Médio, a emissão com a 2ª via e o cancelamento, a saída no meio do ano e o ano letivo fechado.', 1, '2026-11-25 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.101.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.101.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 226, 118, 'O histórico escolar', 'Na escola, a secretaria emite o histórico escolar do Ensino Fundamental e do Ensino Médio: os cadastros e os textos, a matriz curricular de cada ano, a vida escolar do aluno (os anos desta escola, pelo sistema, e os de outras escolas, digitados), a prévia, a emissão com o código de verificação, a 2ª via e o cancelamento com o motivo. A demonstração do colégio já traz três alunos com a vida escolar pronta.', 1, 'administrador,secretaria', 'emitir-historico-escolar', '2026-11-25 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 226);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 227, 118, 'A saída no meio do ano e o ano letivo fechado', 'Na escola, a data da saída da turma é digitada, e o aluno transferido no meio do ano tem o ano parcial no histórico, até a data da saída. A secretaria fecha e reabre o ano letivo (reabrir pede o motivo).', 2, 'administrador,secretaria', NULL, '2026-11-25 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 227);
