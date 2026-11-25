-- Os relatórios na escola (Onda 8, correção, acréscimo A, itens 3 a 5; D-79): o vocabulário da escola no desempenho do
-- aluno (a série) e no mapa de notas (a nota e a recuperação de cada etapa, a média anual, a situação); a
-- montagem da demonstração emite um atestado (o relatório de documentos o mostra). Os que não se aplicam à escola já
-- ficavam fora do menu dela. Só código.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.69.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 86, '1.69.0', 1, 69, 0, '2026-11-21', 'Os relatórios na língua da escola: série, etapa e recuperação.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.69.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.69.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 189, 86, 'Os relatórios na língua da escola', 'Na escola, o desempenho do aluno mostra a série, e o mapa de notas mostra a nota e a recuperação de cada etapa, a média anual e a situação.', 1, 'administrador,secretaria,professor', 'relatorios-c-desempenho', '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 189);
