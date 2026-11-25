-- O caminho do professor até as etapas da disciplina (Onda 8, correção, Parte 1.2; D-72). Na escola, a barra das telas
-- da disciplina ganha a aba "Etapas" (a recuperação, as faltas da etapa, fechar a etapa), e "Minhas disciplinas" ganha a
-- ação "Etapas" em cada linha. Só telas: nenhuma tabela muda.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.62.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 79, '1.62.0', 1, 62, 0, '2026-11-21', 'O caminho do professor até as etapas da disciplina: a aba Etapas e a ação em Minhas disciplinas.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.62.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.62.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 182, 79, 'As etapas ao alcance do professor', 'Na escola, a tela da disciplina tem a aba "Etapas" (a recuperação, as faltas da etapa e fechar a etapa), e a lista "Minhas disciplinas" tem a ação "Etapas" em cada disciplina. Antes, o caminho só existia na tela da turma.', 1, 'administrador,secretaria,professor', 'avaliar-na-escola', '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 182);
