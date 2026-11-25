-- A digitação das notas (Onda 8, correção, Parte 2.1; D-74): na tela da atividade, a nota se digita no campo da linha;
-- o Enter grava na hora e abre o campo do próximo aluno. Na escola, a recuperação da etapa grava do mesmo jeito. O sinal
-- de gravado é sutil; a recusa fica no campo, com o motivo. A gravação, as travas e a auditoria são as de sempre; a
-- planilha continua. Só telas.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.64.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 81, '1.64.0', 1, 64, 0, '2026-11-21', 'A nota com Enter: grava na hora e vai para o próximo aluno; a recuperação também.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.64.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.64.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 184, 81, 'A nota com Enter', 'Na tela da atividade, digite a nota e aperte Enter: ela grava na hora e o cursor vai para o próximo aluno, pronto para digitar. Um sinal pequeno de confirmado aparece ao lado e some sozinho. Se a nota for recusada, o motivo aparece junto do campo e o que você digitou fica lá para corrigir. Na escola, a recuperação da etapa funciona do mesmo jeito.', 1, 'administrador,secretaria,professor', 'notas-das-atividades', '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 184);
