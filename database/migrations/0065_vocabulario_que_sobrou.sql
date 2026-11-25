-- O vocabulário que sobrou na escola (Onda 8, correção, segunda leva, 2.5; D-91): os filtros dos relatórios sem a
-- grade, o polo e a coorte de ingresso, e o curso como segmento; o relatório de alunos sem as colunas da grade e do
-- polo; a lista de disciplinas com "Disciplina" e "Segmento"; a aba do navegador do responsável, "Portal do
-- responsável", e a entrada da escola, "Portal do aluno e do responsável". Só telas: nenhuma tabela muda.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.78.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 95, '1.78.0', 1, 78, 0, '2026-11-21', 'Na escola, o vocabulário que sobrou: os filtros dos relatórios, a lista de disciplinas e o nome do portal do responsável.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.78.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.78.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 198, 95, 'As palavras da escola', 'Na escola, os relatórios filtram pelo segmento, pela turma e pela situação (sem grade, polo e coorte de ingresso); a lista de disciplinas mostra a disciplina e o segmento; e o portal do responsável se chama assim, também na aba do navegador.', 1, 'administrador,secretaria', NULL, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 198);
