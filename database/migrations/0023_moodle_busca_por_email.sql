-- O ajuste do Moodle (1.36.1): o plugin do Moodle ganhou a função que diz, para cada e-mail, quais contas o têm
-- (local_vcostaedu_find_users_by_email; o serviço passou a 17 funções). Com ela: um e-mail com uma conta liga o aluno a
-- ela; com mais de uma, o envio fica pendente da escolha do operador (as contas candidatas ficam na coluna nova); a conta
-- suspensa no Moodle liga com aviso. E a disciplina criada pelo VCOSTAEDU acompanha o nome da oferta.

ALTER TABLE `adms_moodle_envios` ADD COLUMN IF NOT EXISTS `contas` varchar(255) NULL DEFAULT NULL COMMENT 'na pendência de várias contas com o mesmo e-mail: os ids das contas do Moodle (JSON), para o operador escolher (1.36.1)' AFTER `motivo`;

-- escolher a conta (POST, fora do menu): quem vê e reenvia os envios (a ação 97)
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT 506, 'Escolher a conta do Moodle', 'MoodleEnvios', 'escolherConta', 'moodle-envios', 'escolher-conta', 'Escolher a conta do Moodle de um envio', 'POST. O e-mail está em mais de uma conta do Moodle: o operador escolhe qual é a da pessoa.', 2, NULL, 1, 1, 1, NOW(), 1, 0, NULL, 97
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 506);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 2, 506, 2, 2, 1, n.`id`, 506, NOW() FROM `adms_niveis_acessos` n
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.`id` AND x.`adms_pagina_id` = 506);
-- a mesma permissão do "Tentar de novo" (502), nível a nível
UPDATE `adms_nivacs_pgs` d JOIN `adms_nivacs_pgs` e ON e.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND e.`adms_pagina_id` = 502
   SET d.`permissao` = e.`permissao`, d.`dropdown` = e.`dropdown`, d.`adms_menu_id` = e.`adms_menu_id`
 WHERE d.`adms_pagina_id` = 506;

-- A versão e a novidade
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.36.1' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 53, '1.36.1', 1, 36, 1, '2026-11-15', 'Moodle: a conta já existente é achada pelo e-mail (em grupos, no envio em lote); e-mail em várias contas vira escolha do operador; conta suspensa liga com aviso; a disciplina criada acompanha o nome.', 1, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.36.1');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.36.1';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 148, 53, 'Moodle: a conta que já existe', 'O aluno que já tem conta no Moodle é ligado a ela pelo e-mail. Se o e-mail estiver em mais de uma conta, você escolhe qual em Envios ao Moodle; se a conta estiver suspensa lá, o envio avisa. E a disciplina criada pelo VCOSTAEDU acompanha o nome da oferta.', 1, 'administrador,secretaria', 'envios-ao-moodle', '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 148);
