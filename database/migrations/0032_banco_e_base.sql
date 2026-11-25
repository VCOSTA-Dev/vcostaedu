-- O banco e a base (Onda 6, Etapa 3; DECISIONS D-53). O modo do banco fixado pelo sistema passa a recusar agrupamento com
-- colunas soltas (ONLY_FULL_GROUP_BY): a lista da enturmação mostra, por aluno, a matrícula e a enturmação mais recentes,
-- e diz isso na tela; o cálculo da frequência no ano da escola foi reescrito para o modo. O runner aceita etapa só de
-- consulta; as etapas 0001 e 0002 perderam os nomes de origem dos comentários (o resumo antigo segue aceito, só para
-- elas). A constante de guarda e o nome de código herdado deram lugar a VCOSTAEDU — inclusive no nome do cookie da
-- sessão, o que pede entrar de novo uma vez. Nenhuma tabela muda: esta etapa só registra a versão e as novidades.

-- A versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.45.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 62, '1.45.0', 1, 45, 0, '2026-11-17', 'A lista da enturmação mostra a matrícula mais recente de cada aluno e diz isso; quem estava conectado entra de novo uma vez.', 1, '2026-11-17 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.45.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.45.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 157, 62, 'A enturmação mostra a matrícula mais recente', 'Em Educacional → Enturmação, cada aluno aparece uma vez. Quando ele tem mais de uma matrícula ou enturmação no ano, a lista mostra a mais recente, com a marca "mais recente de N", e a tela explica isso.', 1, 'administrador,secretaria', NULL, '2026-11-17 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 157);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 158, 62, 'Entrar de novo, uma vez', 'Depois desta atualização, quem estava conectado precisa entrar de novo uma vez, com o mesmo usuário e a mesma senha. Nada se perde.', 2, 'administrador,secretaria,professor,aluno', NULL, '2026-11-17 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 158);
