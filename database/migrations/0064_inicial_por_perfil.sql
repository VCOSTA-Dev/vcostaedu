-- A Página Inicial de cada perfil na escola (Onda 8, correção, segunda leva, 2.4; D-90): a direção, o painel
-- acadêmico, os relatórios e as turmas; a secretaria e a supervisão, os alunos, as turmas, os responsáveis e a
-- importação de alunos; a coordenação, as turmas e os relatórios do segmento; o professor segue com "O meu dia". Cada
-- atalho só aparece com a permissão do perfil. Só a tela: nenhuma tabela muda.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.77.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 94, '1.77.0', 1, 77, 0, '2026-11-21', 'Na escola, a Página Inicial de cada perfil com os atalhos do dia a dia.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.77.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.77.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 197, 94, 'A Página Inicial de cada perfil', 'Na escola, a Página Inicial mostra os atalhos de cada perfil: a direção, o painel acadêmico, os relatórios e as turmas; a secretaria e a supervisão, os alunos, as turmas, os responsáveis e a importação de alunos; a coordenação, as turmas e os relatórios do segmento. O professor segue com "O meu dia".', 1, 'administrador,secretaria,professor', NULL, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 197);
