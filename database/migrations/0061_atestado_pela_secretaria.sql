-- Os documentos na escola: a secretaria emite o atestado de matrícula pelo painel (Onda 8, correção, segunda leva, 2.1;
-- D-87). As telas de emissão (emitir, prévia, ver, PDF, 2ª via, cancelar: 396 a 401) deixam de ser só do ensino
-- superior e passam ao núcleo; na escola, elas emitem só o atestado (o controlador e o modelo travam o tipo), com o
-- mesmo modelo e o mesmo código de verificação do portal. A lista dos históricos emitidos (395) e os modelos de
-- histórico continuam do ensino superior: o histórico escolar da educação básica é uma etapa à parte. Os botões passam a
-- conferir o módulo da página (AdmsBotao): o de uma página desligada some, em vez de levar à página sem acesso.

DELETE FROM `adms_paginas_modulo` WHERE `adms_pagina_id` IN (396, 397, 398, 399, 400, 401);

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.74.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 91, '1.74.0', 1, 74, 0, '2026-11-21', 'Na escola, a secretaria emite o atestado de matrícula pelo painel; nenhum botão leva à página sem acesso.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.74.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.74.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 194, 91, 'O atestado de matrícula pela secretaria', 'Na escola, a secretaria emite o atestado de matrícula pelo painel: na lista de alunos, o botão "Emitir atestado de matrícula"; na ficha do aluno, a aba Documentos. É o mesmo modelo e o mesmo código de verificação do atestado que o aluno e o responsável emitem pelo portal.', 1, 'administrador,secretaria', NULL, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 194);
