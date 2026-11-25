-- "Disciplina" nos guias e na documentação da interface de integração (Onda 8, correção, segunda leva, 2.8; D-94):
-- onde "componente" era a matéria da turma, o texto diz "disciplina" (o guia "Conhecer com a demonstração", o exemplo
-- do curso livre e a especificação da interface). Os nomes dos campos e das rotas da interface não mudam (o campo
-- "componentes" da turma continua). O "componente" da regra de média (a prova, o trabalho) é outra coisa e fica.

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.81.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 98, '1.81.0', 1, 81, 0, '2026-11-22', '"Disciplina" nos guias e na documentação da interface de integração.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.81.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.81.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 201, 98, '"Disciplina" nos guias e na documentação da API', 'Os guias e a documentação da interface de integração dizem "disciplina" onde diziam "componente" para a matéria da turma. Os nomes dos campos e das rotas da interface continuam os mesmos: o sistema ligado a ela não precisa mudar nada.', 1, 'administrador', 'integrar-outro-sistema-pela-api', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 201);
