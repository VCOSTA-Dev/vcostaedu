-- A ficha do aluno na escola (Onda 8, correção, segunda leva, 2.2; D-88). A aba Matrículas da escola mostra o segmento,
-- a série, a turma, o ano letivo e a situação; nada do ensino superior (o módulo, a grade, o polo, a modalidade, a forma
-- de ingresso, o TCC, a conclusão pela grade). Ao salvar a matrícula pela ficha da escola, a forma de ingresso, o polo e
-- a modalidade guardados ficam como estavam. Só telas e regra de gravação: nenhuma tabela muda.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.75.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 92, '1.75.0', 1, 75, 0, '2026-11-21', 'A ficha do aluno na escola: o segmento, a série, a turma, o ano letivo e a situação.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.75.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.75.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 195, 92, 'A ficha do aluno da escola', 'Na escola, a aba Matrículas da ficha do aluno mostra o segmento, a situação e, ano a ano, a série e a turma (com o turno e a sala). Para matricular, escolha o ano letivo, o segmento e a série; depois, ponha o aluno numa turma em Turmas.', 1, 'administrador,secretaria', NULL, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 195);
