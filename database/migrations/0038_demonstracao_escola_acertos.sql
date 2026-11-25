-- Os acertos da demonstração da escola (Onda 7, Etapa 1; DECISIONS D-61). O nome exibido do curso deixa de levar a
-- versão da grade (ela aparece num campo próprio, com o rótulo "Grade", só quando o curso tem mais de uma); a tela das
-- etapas da escola mostra a conta de cada aluno; o "Como ler" do boletim da escola usa a mínima da escola; no portal da
-- escola, a conta só mostra as etapas liberadas. Nenhuma tabela muda: esta etapa só registra a versão e a novidade.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.51.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 68, '1.51.0', 1, 51, 0, '2026-11-18', 'O nome do curso sem a versão da grade; a conta de cada aluno nas etapas da escola.', 1, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.51.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.51.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 171, 68, 'O nome do curso, sem a grade, e a conta de cada aluno', 'O boletim, a ficha do aluno e as listas mostram o curso pelo nome; a versão da grade aparece num campo próprio só quando o curso tem mais de uma. Na escola, a tela das etapas mostra a conta de cada aluno (a média, a recuperação e a nota de cada etapa), e o boletim explica a leitura com a média mínima da escola.', 1, 'administrador,secretaria,professor', 'avaliar-na-escola', '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 171);
