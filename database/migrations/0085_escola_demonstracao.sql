-- A CORREÇÃO DA ESCOLA, Parte 5 (D-104; docs/ESCOLA.md): a demonstração da escola. Nada muda no banco além da versão e
-- da novidade: a demonstração ganha o 8º ano A, o responsável com o sobrenome do filho, a Matemática do professor em
-- chamada por aula, o aluno que entrou no meio do ano com as notas de transferência e a liberação do boletim; a remoção
-- apaga também as aulas, as presenças e as notas de transferência da demonstração.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.98.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 115, '1.98.0', 1, 98, 0, '2026-11-23', 'A demonstração da escola: o 8º ano, a chamada do professor, o aluno que entrou no meio do ano e a liberação do boletim.', 1, '2026-11-23 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.98.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.98.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 222, 115, 'A demonstração da escola mais completa', 'A demonstração do colégio fictício ganhou o 8º ano A, com as nove disciplinas; a Matemática das turmas do professor de demonstração está em chamada por aula, com aulas lançadas; um aluno que entrou no meio do ano tem as notas da escola de origem lançadas; e o boletim mostra as etapas fechadas, com a média final e a situação final ainda ocultas.', 1, 'administrador', 'conhecer-com-a-demonstracao', '2026-11-23 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 222);
