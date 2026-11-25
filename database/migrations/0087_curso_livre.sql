-- O CURSO LIVRE (Fechamento; D-106): o terceiro tipo de instituição, escolhido na instalação. O curso livre usa a
-- estrutura por turma da escola (o curso, a turma com vagas e as disciplinas da turma), sem etapas, boletim, conselho,
-- horário nem os documentos da escola e do ensino superior; o financeiro e a matrícula pela secretaria são recursos de
-- ligar e desligar. Nada muda na escola e no ensino superior: as páginas que já existiam continuam valendo neles.

-- 1. A marca do tipo nas páginas do painel passa a ser uma LISTA com vírgula (o código lê as duas formas): "escola,livre"
--    vale na escola e no curso livre; "escola,superior", em todos menos no curso livre. Sem linha, o núcleo (todos).

-- as turmas valem no curso livre (a lista, a nova, editar, ver, enturmar, tirar e trocar); o horário e os boletins da
-- turma ficam só na escola
UPDATE `adms_paginas_modulo` SET `modulo` = 'escola,livre' WHERE `adms_pagina_id` IN (512, 513, 514, 515, 516, 517, 518);

-- o que é do núcleo e não vale no curso livre: o boletim, as atividades e as notas, as médias, as regras de média, as
-- faltas e a chamada, a auditoria de notas e faltas, o horário e as salas, os polos, as disciplinas do professor, a
-- polivalência, gerar, encerrar e reabrir a oferta, os documentos (o atestado pela secretaria), o módulo do aluno no ano
-- e os relatórios de notas, frequência, risco, documentos e regras
INSERT INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`)
SELECT p.`id`, 'escola,superior' FROM `adms_paginas` p
 WHERE (p.`id` IN (264, 295, 377, 92, 148, 149, 150, 240, 382, 383, 115, 273, 396, 397, 398, 399, 400, 401,
                   439, 441, 446, 447, 448, 449, 450, 451, 452, 453, 458, 459, 460)
    OR p.`controller` IN ('Atividades', 'Auditoria', 'Chamada', 'Faltas', 'HorarioAula', 'HorariosAula', 'Medias', 'Polos', 'RegraMedia', 'SalasDeAula'))
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_pagina_id` FROM `adms_paginas_modulo`) x WHERE x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`;

-- 2. Os RECURSOS do curso livre: a página de um recurso desligado não abre e sai do menu (só no curso livre; na escola e
--    no ensino superior, sempre ligados). O estado fica em adms_instalacao_estado (livre.financeiro, livre.matricula).
CREATE TABLE IF NOT EXISTS `adms_paginas_recurso` (
  `adms_pagina_id` int(11) NOT NULL,
  `recurso` varchar(20) NOT NULL COMMENT 'financeiro | matricula: no curso livre, a página só existe com o recurso ligado',
  PRIMARY KEY (`adms_pagina_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O recurso do curso livre de cada página (sem linha = sempre)';

-- o financeiro: as regras, os planos, os motivos, as bandeiras, a ficha e as operações, e os dois relatórios
INSERT IGNORE INTO `adms_paginas_recurso` (`adms_pagina_id`, `recurso`)
SELECT `id`, 'financeiro' FROM `adms_paginas`
 WHERE `controller` IN ('Financeiro', 'FinRegras', 'FinPlanos', 'FinMotivos', 'FinBandeiras') OR `id` IN (573, 574)
 ORDER BY `id`;
-- a matrícula pela secretaria: cadastrar e apagar o aluno, matricular no curso e editar a matrícula, pôr na turma, tirar,
-- trocar, e a importação de planilha
INSERT IGNORE INTO `adms_paginas_recurso` (`adms_pagina_id`, `recurso`)
SELECT `id`, 'matricula' FROM `adms_paginas`
 WHERE `id` IN (47, 50, 375, 376, 516, 517, 518) OR `controller` = 'ImportacaoAlunos'
 ORDER BY `id`;

-- 3. A marca do tipo nas páginas do PORTAL (a mesma regra): no curso livre, o aluno tem a Página Inicial, o Moodle e a
--    Ajuda. O horário, o boletim, as notas das atividades, o atestado, o progresso, os vídeos e a ficha financeira ficam
--    na escola e no ensino superior, como sempre.
CREATE TABLE IF NOT EXISTS `col_paginas_modulo` (
  `col_pagina_id` int(11) NOT NULL,
  `modulo` varchar(20) NOT NULL COMMENT 'lista com vírgula (escola, superior, livre): a página só existe nesses tipos',
  PRIMARY KEY (`col_pagina_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O tipo de instituição de cada página do portal (sem linha = todos)';
INSERT IGNORE INTO `col_paginas_modulo` (`col_pagina_id`, `modulo`)
SELECT `id`, 'escola,superior' FROM `col_paginas` WHERE `id` IN (10, 11, 13, 21, 22, 23, 24, 25, 41, 43) ORDER BY `id`;

-- 4. A tela dos recursos do curso livre (Configurações): só no curso livre, com a ação e as permissões da tela
--    Configurações → Instituição (Parâmetros)
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, 'RecursosLivre', x.met, 'recursos-livre', x.mmet, x.nome, x.obs, 2, x.icone, 1, 1, 1, NOW(), x.grava, 0, NULL,
       (SELECT `acao_id` FROM `adms_paginas` WHERE `id` = x.base)
  FROM (SELECT 597 id, 'Recursos do curso livre' menu, 'index' met, 'index' mmet, 'Recursos do curso livre' nome, 'O financeiro e a matrícula pela secretaria: ligar e desligar.' obs, 'fas fa-toggle-on' icone, 0 grava, 364 base
        UNION ALL SELECT 598, 'Gravar os recursos do curso livre', 'salvar', 'salvar', 'Gravar os recursos do curso livre', 'POST. Só no curso livre.', NULL, 1, 365) x
 WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id);
INSERT IGNORE INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`) VALUES (597, 'livre'), (598, 'livre');
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, IF(x.id = 597, 281, 282), d.`dropdown`, IF(x.id = 597, d.`lib_menu`, 2), d.`adms_menu_id`, d.`adms_niveis_acesso_id`, x.id, NOW()
  FROM (SELECT 597 id, 364 base UNION ALL SELECT 598, 365) x
  JOIN `adms_nivacs_pgs` d ON d.`adms_pagina_id` = x.base
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) y WHERE y.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND y.`adms_pagina_id` = x.id);

-- 5. Os Primeiros passos do curso livre: o ano letivo, o curso e os alunos do ensino superior valem também no curso
--    livre; as regras de nota e frequência não; e duas fases próprias, a turma e pôr os alunos na turma (as da escola
--    pedem as etapas)
UPDATE `adms_fases` SET `modulo` = 'superior,livre' WHERE `codigo` IN ('ano_letivo', 'curso', 'alunos');
UPDATE `adms_fases` SET `modulo` = 'escola,superior' WHERE `codigo` = 'regras' AND `modulo` IS NULL;
INSERT INTO `adms_fases` (`id`, `codigo`, `ordem`, `titulo`, `texto`, `feito`, `convite`, `rota`, `rotulo_rota`, `roteiro`, `condicao`, `depende_de`, `pode_pular`, `modulo`, `situacao`)
SELECT x.* FROM (
  SELECT 30 id, 'livre_turma' codigo, 60 ordem, 'A turma do curso' titulo,
         'Crie a turma do curso no ano letivo: o nome, as vagas e a sala (on-line, se for o caso). As disciplinas da matriz entram na turma sozinhas, e cada uma vira um curso no Moodle.' texto,
         'A turma existe.' feito, 'Crie a turma do curso.' convite, 'turmas/listar' rota, 'Abrir as turmas' rotulo_rota, NULL roteiro,
         'SELECT COUNT(*) FROM esc_turmas' condicao, 'ano_letivo,curso' depende_de, 1 pode_pular, 'livre' modulo, 1 situacao
  UNION ALL SELECT 31, 'livre_enturmar', 80, 'Os alunos na turma',
         'Ponha os alunos na turma (pela tela da turma) ou deixe a inscrição do site fazer isso sozinha, pela interface de integração.',
         'Há aluno na turma.', 'Ponha os alunos na turma.', 'turmas/listar', 'Abrir as turmas', NULL,
         'SELECT COUNT(*) FROM esc_turma_alunos WHERE saida IS NULL', 'livre_turma,alunos', 1, 'livre', 1
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_fases` f WHERE f.`codigo` = x.codigo);

-- A versão e a novidade
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.100.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 117, '1.100.0', 1, 100, 0, '2026-11-25', 'O curso livre: o terceiro tipo de instituição, escolhido na instalação, com o financeiro e a matrícula de ligar e desligar; a entrada do aluno no portal pelo e-mail ou pela matrícula no ensino superior e no curso livre.', 1, '2026-11-25 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.100.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.100.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 224, 117, 'O curso livre', 'O sistema agora atende três tipos de instituição: ensino superior, escola e curso livre (curso on-line). O curso livre nasce enxuto: o curso, a turma com vagas, as disciplinas no Moodle e a inscrição pelo site, sem boletim, etapas, horário, conselho nem documentos; o financeiro e a matrícula pela secretaria são de ligar e desligar. A instalação que já existia continua do tipo que era.', 1, 'administrador', 'instalar-como-curso-livre', '2026-11-25 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 224);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 225, 117, 'A entrada do aluno no portal', 'No ensino superior e no curso livre, o aluno entra no portal com o e-mail do cadastro ou com o número de matrícula. Se o mesmo e-mail estiver em mais de um cadastro, a tela avisa e pede o número de matrícula. Na escola, nada muda: o aluno entra pelo RM e o responsável pelo e-mail.', 2, 'administrador,secretaria', NULL, '2026-11-25 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 225);
