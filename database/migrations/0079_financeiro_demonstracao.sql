-- O financeiro, Parte 8 (Onda 8; D-98): a demonstração do colégio com o financeiro (as regras, um plano por segmento, as
-- parcelas dos alunos de 2026, a maioria recebida em dia, alguns com atraso, alguns vencidos, uma baixa, um cancelamento,
-- uma renegociação e os atendimentos), o manual e o roteiro de atualização. Só a montagem e a documentação.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.92.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 109, '1.92.0', 1, 92, 0, '2026-11-22', 'O financeiro na demonstração do colégio, o manual e o roteiro de atualização.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.92.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.92.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 212, 109, 'O financeiro na demonstração', 'A demonstração do colégio passa a ter o financeiro: os planos, as parcelas dos alunos de 2026, recebimentos em dia e com atraso, títulos vencidos, uma baixa, um cancelamento, uma renegociação e atendimentos. Tudo fictício.', 1, 'administrador,secretaria', 'conhecer-com-a-demonstracao', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 212);
