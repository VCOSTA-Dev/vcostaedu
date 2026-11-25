-- A chamada a cada clique (Onda 8, correção, Parte 2.2; D-75): no encontro que já existe, cada clique na presença grava
-- na hora (a oferta encerrada e a etapa fechada recusam; a auditoria e a consolidação das faltas, no mesmo clique). O
-- encontro novo começa com todos presentes ou todos ausentes, e a escolha do professor fica lembrada. A página nova
-- tem o escopo da oferta, como a do encontro: o professor só grava nas ofertas dele.

INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT 558, 'Gravar a presença', 'Chamada', 'presenca', 'chamada', 'presenca', 'Gravar um clique da chamada', 'POST, em JSON. A presença de um aluno num encontro, na hora.', 2, NULL, 1, 1, 1, NOW(), 1, 0, 'oferta', 22
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = 558);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, 558, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, 558, NOW()
  FROM `adms_nivacs_pgs` d WHERE d.`adms_pagina_id` = 422
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 558)
 ORDER BY d.`adms_niveis_acesso_id`; -- a ordem dos ids, fixa

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.65.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 82, '1.65.0', 1, 65, 0, '2026-11-21', 'A chamada a cada clique, e o encontro que começa com todos presentes ou todos ausentes.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.65.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.65.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 185, 82, 'A chamada grava a cada clique', 'No encontro já criado, cada clique em PRESENTE ou AUSENTE grava na hora, com um sinal pequeno de confirmado ao lado; as faltas da etapa se atualizam no mesmo clique. Ao criar o encontro, escolha começar com todos presentes ou todos ausentes: a escolha fica lembrada para o próximo.', 1, 'administrador,secretaria,professor', 'fazer-a-chamada', '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 185);
