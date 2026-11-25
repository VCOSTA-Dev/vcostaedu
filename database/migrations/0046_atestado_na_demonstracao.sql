-- Onda 8, Etapa 3 (D-69): o atestado de matrícula na demonstração da escola.
--   A demonstração do colégio passa a dar a cada aluno fictício um CPF de demonstração (000 + a matrícula + os dois
--   verificadores válidos) e a previsão de conclusão coerente com a série (o fim do ano da última série do segmento):
--   o atestado, que pede os dois, passa a sair para o aluno e para o responsável. Nenhuma tabela muda; a demonstração
--   já montada fica como está (a remoção e uma nova montagem trazem os dois campos).

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.59.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 76, '1.59.0', 1, 59, 0, '2026-11-20', 'O atestado de matrícula na demonstração da escola: os alunos fictícios ganharam o CPF de demonstração e a previsão de conclusão.', 1, '2026-11-20 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.59.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.59.0';

-- a novidade
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 179, 76, 'O atestado na demonstração da escola', 'Na demonstração do colégio, os alunos fictícios têm agora um CPF de demonstração (que começa com 000) e a previsão de conclusão: o atestado de matrícula sai pelo portal, para o aluno e para o responsável. A demonstração que já estava montada fica como está; para ver o atestado, remova e monte de novo.', 1, 'administrador', 'conhecer-com-a-demonstracao', '2026-11-20 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 179);
