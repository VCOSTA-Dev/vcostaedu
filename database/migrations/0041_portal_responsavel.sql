-- O portal do responsável (Onda 7, Etapa 4; docs/RESPONSAVEIS.md, D-64). Só na escola. Depois de entrar, o responsável
-- vê os filhos de que tem papel ou acesso em algum ano; escolhe um filho e, dele, no ano escolhido (só os anos em que
-- tem papel ou acesso), só para leitura: o boletim (a mesma regra de liberação do aluno), as notas das atividades, a
-- frequência, o horário e os dados da turma; e emite o atestado de matrícula, se o aluno pode. Toda página passa pela
-- barreira (core/AcessoFamilia.php) a cada requisição. Nada do financeiro.

INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`)
SELECT x.id, x.menu, 'Familia', x.met, 'familia', x.mmet, x.menu, x.obs, 2, x.icone, 1, 1, 1, NOW() FROM (
  SELECT 33 id, 'O filho' menu, 'filho' met, 'filho' mmet, 'O resumo do filho no ano.' obs, 'fas fa-child' icone
  UNION ALL SELECT 34, 'Boletim do filho', 'boletim', 'boletim', 'O boletim do filho no ano (a mesma regra de liberação do aluno).', NULL
  UNION ALL SELECT 35, 'Notas das atividades do filho', 'notas', 'notas', 'As notas das atividades do filho no ano.', NULL
  UNION ALL SELECT 36, 'Frequência do filho', 'frequencia', 'frequencia', 'As faltas e a frequência do filho no ano.', NULL
  UNION ALL SELECT 37, 'Horário do filho', 'horario', 'horario', 'O horário do filho no ano.', NULL
  UNION ALL SELECT 38, 'A turma do filho', 'turma', 'turma', 'A série, a turma, o turno, a sala, os componentes e os professores.', NULL
  UNION ALL SELECT 39, 'Atestado de matrícula do filho', 'atestado', 'atestado', 'Ver e emitir (POST) o atestado de matrícula do filho.', NULL
  UNION ALL SELECT 40, 'O PDF do atestado do filho', 'atestadoPdf', 'atestado-pdf', 'O PDF de um atestado emitido.', NULL
) x WHERE NOT EXISTS (SELECT 1 FROM `col_paginas` p WHERE p.`id` = x.id);

INSERT INTO `col_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 1, x.id, 2, 2, 1, 2, x.id, NOW() FROM (
  SELECT 33 id UNION ALL SELECT 34 UNION ALL SELECT 35 UNION ALL SELECT 36 UNION ALL SELECT 37 UNION ALL SELECT 38 UNION ALL SELECT 39 UNION ALL SELECT 40
) x WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `col_nivacs_pgs`) y WHERE y.`adms_niveis_acesso_id` = 2 AND y.`adms_pagina_id` = x.id);

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.54.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 71, '1.54.0', 1, 54, 0, '2026-11-18', 'O portal do responsável: os filhos, o ano, o boletim, as notas, a frequência, o horário, a turma e o atestado.', 1, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.54.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.54.0';

-- a novidade
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 174, 71, 'O portal do responsável', 'Na escola, o responsável que entrou no portal vê os filhos de que tem papel em algum ano e, de cada um, só nos anos em que tem papel: o boletim (com a mesma regra de liberação do aluno), as notas das atividades, a frequência, o horário e os dados da turma; e emite o atestado de matrícula. Tudo só para leitura; com um filho só, ele entra direto no filho.', 1, 'administrador,secretaria', 'o-que-o-responsavel-ve-no-portal', '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 174);
