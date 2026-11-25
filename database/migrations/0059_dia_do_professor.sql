-- Peso baixo (Onda 8, correção, Parte 4; D-82, D-85): na escola, "Minhas disciplinas" com o segmento no lugar do curso e,
-- na Página Inicial do professor, "O meu dia": as disciplinas
-- dele no ano, com a chamada, as notas e as etapas. Só telas.

-- Onda 8, correção (D-85): na escola, o termo é "disciplina", como no ensino superior. As novidades de antes e as
-- descrições das páginas da escola deixam de dizer "componente curricular" (os artigos acompanham: "a disciplina").
UPDATE `adms_novidades` SET `texto` = 'Numa instalação de escola: Ensino → Ano letivo e etapas (bimestres ou trimestres) e Ensino → Turmas. Cada turma tem as disciplinas da série; o aluno entra na turma e fica em todas elas, de uma vez.' WHERE `id` = 151;
UPDATE `adms_novidades` SET `texto` = REPLACE(`texto`, 'as etapas de cada componente', 'as etapas de cada disciplina') WHERE `id` = 152;
UPDATE `adms_novidades` SET `texto` = REPLACE(`texto`, 'a chamada do dia no componente', 'a chamada do dia na disciplina') WHERE `id` = 162;
UPDATE `adms_paginas` SET
  `obs` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`obs`,
    'os componentes curriculares', 'as disciplinas'), 'componentes curriculares', 'disciplinas'),
    'um componente curricular', 'uma disciplina'), 'componente curricular', 'disciplina'),
    'Os componentes', 'As disciplinas'), 'os componentes', 'as disciplinas'), 'do componente', 'da disciplina'), 'o componente', 'a disciplina'),
  `nome_pagina` = REPLACE(REPLACE(`nome_pagina`, 'do componente', 'da disciplina'), 'componente', 'disciplina')
 WHERE `id` BETWEEN 509 AND 559 AND (`obs` LIKE '%omponente%' OR `nome_pagina` LIKE '%omponente%');

UPDATE `adms_fases` SET
  `titulo` = REPLACE(`titulo`, 'os componentes', 'as disciplinas'),
  `texto` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`texto`,
    'os componentes curriculares', 'as disciplinas'), 'cada componente curricular', 'cada disciplina'), 'componente curricular', 'disciplina'),
    'todos os componentes', 'todas as disciplinas'), 'os componentes', 'as disciplinas'), 'cada componente', 'cada disciplina'),
  `convite` = REPLACE(REPLACE(REPLACE(`convite`, 'os componentes curriculares', 'as disciplinas'), 'os componentes', 'as disciplinas'), 'cada componente', 'cada disciplina'),
  `feito` = REPLACE(REPLACE(`feito`, 'os componentes curriculares', 'as disciplinas'), 'os componentes', 'as disciplinas')
 WHERE `codigo` LIKE 'esc\_%';

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.72.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 89, '1.72.0', 1, 72, 0, '2026-11-21', 'O dia a dia do professor na escola: o vocabulário e os atalhos da chamada, das notas e das etapas.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.72.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.72.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 192, 89, 'O meu dia', 'Na escola, a Página Inicial do professor mostra "O meu dia": as disciplinas dele no ano, cada uma com os atalhos da chamada, das notas e das etapas.', 1, 'professor', 'minhas-disciplinas-e-atividades', '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 192);
