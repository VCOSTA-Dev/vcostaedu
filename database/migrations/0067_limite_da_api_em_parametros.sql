-- O limite de chamadas por minuto de cada chave da API, na tela de Parâmetros (Onda 8, correção, segunda leva, 2.7;
-- D-93). O parâmetro já existia (0010, api.limite_por_minuto, 120): a tela passa a editá-lo, de 10 a 10000, e a
-- explicar quando aumentar. A descrição do parâmetro diz onde ele fica.

UPDATE `adms_parametros` SET `descricao` = 'Quantas chamadas cada chave da API pode fazer por minuto (acima disso, a API responde 429). Em Parâmetros, de 10 a 10000.'
 WHERE `chave` = 'api.limite_por_minuto';

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.80.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 97, '1.80.0', 1, 80, 0, '2026-11-22', 'O limite de chamadas por minuto de cada chave da API, na tela de Parâmetros.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.80.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.80.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 200, 97, 'O limite da API em Parâmetros', 'Em Parâmetros, "Integrações: chamadas por minuto de cada chave da API": o limite de chamadas que cada chave pode fazer num minuto (de 10 a 10000; padrão 120), com a explicação de quando aumentar — o site de inscrições num período de matrícula, uma importação grande, e o registro de chamadas mostrando respostas 429.', 1, 'administrador', 'integrar-outro-sistema-pela-api', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 200);
