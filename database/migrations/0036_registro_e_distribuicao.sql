-- O registro da instalação e a distribuição (Onda 6, Etapa 7; DECISIONS D-59). O registro voluntário, no modelo do
-- Moodle (Manutenção → Registrar a instalação; o convite, sem vir marcado, na última tela do instalador): a tela mostra
-- exatamente o que vai antes de enviar; o estado fica em adms_instalacao_estado ('registro.instalacao'). Os endereços do
-- projeto preenchidos no código (core/Legal.php); o pacote da versão com as notas, o código de conferência e o
-- versao.json (docs/VERSAO_JSON.md). Nenhuma tabela muda.

-- a ação (só o Administrador)
INSERT INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`)
SELECT 100, 'registrar_a_instalacao_100', 'Registrar a instalação', 'Manutenção', 'administrador', 84
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_acoes` WHERE `id` = 100);

-- as páginas
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, 'RegistroInstalacao', x.met, 'registro-instalacao', x.met, x.nome, x.obs, 2, x.icone, 1, 1, 1, NOW(), x.grava, 0, NULL, 100 FROM (
  SELECT 529 id, 'Registrar a instalação' menu, 'index' met, 'Registrar a instalação' nome, 'Voluntário: o que vai, exatamente, antes de enviar.' obs, 'fas fa-paper-plane' icone, 0 grava
  UNION ALL SELECT 530, 'Enviar o registro', 'enviar', 'Enviar o registro da instalação', 'POST.', NULL, 1
  UNION ALL SELECT 531, 'Cancelar o registro', 'cancelar', 'Cancelar o registro da instalação', 'POST.', NULL, 1
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id);

-- as permissões: todo nível tem a linha (sem permissão); o Administrador, com a tela no menu Manutenção
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 2, p.`id`, 2, 2, 1, n.`id`, p.`id`, NOW() FROM `adms_niveis_acessos` n JOIN `adms_paginas` p ON p.`id` IN (529, 530, 531)
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.`id` AND x.`adms_pagina_id` = p.`id`);
UPDATE `adms_nivacs_pgs` d JOIN `adms_niveis_acessos` n ON n.`id` = d.`adms_niveis_acesso_id`
   SET d.`permissao` = 1, d.`dropdown` = 1, d.`adms_menu_id` = 9, d.`lib_menu` = IF(d.`adms_pagina_id` = 529, 1, 2), d.`ordem` = IF(d.`adms_pagina_id` = 529, 494, d.`adms_pagina_id`)
 WHERE d.`adms_pagina_id` IN (529, 530, 531) AND n.`papel` = 'administrador';

-- A versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.49.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 66, '1.49.0', 1, 49, 0, '2026-11-18', 'O registro voluntário da instalação, os endereços do projeto e o pacote da versão com as notas e o código de conferência.', 1, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.49.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.49.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 168, 66, 'Registrar a instalação (voluntário)', 'Em Manutenção → Registrar a instalação, você pode contar ao projeto que usa o sistema: a tela mostra exatamente o que vai antes de enviar — a versão, o tipo de instituição, a UF, os módulos e três contagens; o nome, o site e um e-mail para avisos de segurança só se você marcar. Nunca vai dado de aluno, professor ou responsável, e dá para cancelar quando quiser.', 1, 'administrador', 'avisos-e-seguranca', '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 168);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 169, 66, 'A versão nova que corrige falha de segurança', 'Em Avisos do sistema, quando a versão mais nova corrige uma falha de segurança, o aviso aparece em destaque. Os links do projeto (Apoie e Serviços) aparecem quando o site do projeto estiver no ar.', 2, 'administrador', 'avisos-e-seguranca', '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 169);
