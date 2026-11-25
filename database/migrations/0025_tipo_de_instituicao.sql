-- O tipo de instituição (Onda 4, Etapa 1; docs/ESCOLA.md, D-45): uma instalação é de ensino superior OU escola
-- (educação básica). Toda instalação que já existe é de ensino superior. O tipo liga o módulo do mesmo nome: a página
-- de um módulo desligado não abre e sai do menu. A escola vive em tabelas e linhas próprias; nada do ensino superior muda.

-- o tipo: as instalações que já existem são de ensino superior (o instalador grava o escolhido por cima)
INSERT INTO `adms_instalacao_estado` (`chave`, `valor`)
SELECT 'instituicao.tipo', 'superior' FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `adms_instalacao_estado` WHERE `chave` = 'instituicao.tipo');

-- o módulo de cada página que não é do núcleo
CREATE TABLE IF NOT EXISTS `adms_paginas_modulo` (
  `adms_pagina_id` int(11) NOT NULL,
  `modulo` varchar(20) NOT NULL COMMENT 'escola | superior: a página só existe na instalação desse tipo',
  PRIMARY KEY (`adms_pagina_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O módulo de cada página fora do núcleo (sem linha = o núcleo)';

-- só do ensino superior: o TCC, os atos legais, o histórico (prévia, emissões, modelos) e os relatórios de conclusão,
-- coorte e TCC
INSERT IGNORE INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`)
SELECT `id`, 'superior' FROM `adms_paginas`
 WHERE `controller` IN ('Tcc', 'AtosLegais', 'Historico', 'HistoricoEmissoes', 'HistoricoModelos')
    OR (`controller` = 'Relatorios' AND `metodo` IN ('previsaoConclusao', 'aptosConcluir', 'situacaoTcc', 'coorteIngresso', 'concluintes'));

-- a ação e a página da escola: a estrutura (os segmentos e as séries), no menu Ensino
INSERT IGNORE INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`) VALUES
(99, 'ver_a_estrutura_da_escola_99', 'Ver a estrutura da escola', 'Ensino', 'coordenacao', 41);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT 507, 'Estrutura da escola', 'Escola', 'index', 'escola', 'index', 'Estrutura da escola', 'Os segmentos, as séries e os componentes curriculares da escola.', 2, 'fas fa-school', 1, 1, 1, NOW(), 0, 0, NULL, 99
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 507);
INSERT IGNORE INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`) VALUES (507, 'escola');
-- as mesmas permissões e o mesmo lugar no menu da lista de cursos, nível a nível
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 74, d.`dropdown`, d.`lib_menu`, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 507, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` = d.`adms_pagina_id`
 WHERE p.`controller` = 'Cursos' AND p.`metodo` = 'listar'
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 507);

-- gravar o tipo (em "Começar com os dados da minha instituição"): a mesma ação da demonstração, só o Administrador
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT 508, 'Gravar o tipo da instituição', 'Demonstracao', 'tipo', 'demonstracao', 'tipo', 'Gravar o tipo da instituição', 'POST. Só sem dado próprio e com a demonstração desligada.', 2, NULL, 1, 1, 1, NOW(), 1, 0, NULL,
       (SELECT `acao_id` FROM `adms_paginas` WHERE `id` = 469)
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 508);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT IF(n.`papel` = 'administrador', 1, 2), 508, 2, 2, 1, n.`id`, 508, NOW() FROM `adms_niveis_acessos` n
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.`id` AND x.`adms_pagina_id` = 508);

-- A versão e a novidade
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.38.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 55, '1.38.0', 1, 38, 0, '2026-11-16', 'O tipo de instituição: ensino superior ou escola (educação básica), escolhido na instalação; cada tipo com as suas telas e as suas palavras. As instalações que já existiam são de ensino superior e continuam iguais.', 1, '2026-11-16 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.38.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.38.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 150, 55, 'O tipo de instituição', 'O sistema agora atende dois tipos de instituição: ensino superior e escola (educação básica). A instalação que já existia continua de ensino superior, como sempre foi; uma instalação nova escolhe o tipo ao instalar. O tipo aparece em Avisos do sistema.', 1, 'administrador', 'o-tipo-de-instituicao', '2026-11-16 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 150);
