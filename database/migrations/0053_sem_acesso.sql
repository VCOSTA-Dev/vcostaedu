-- A página sem acesso (Onda 8, correção, Parte 3.1; D-76): quem entrou e abre uma página que o perfil não abre recebe
-- "Você não tem acesso a esta tela" (HTTP 403), com o caminho de volta, e continua conectado; antes, recebia a tela de
-- entrada. Quem não entrou continua indo ao login. Só o roteador e uma tela.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.66.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 83, '1.66.0', 1, 66, 0, '2026-11-21', 'A página sem acesso: o perfil que não abre a tela continua conectado e tem o caminho de volta.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.66.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.66.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 186, 83, 'Você não tem acesso a esta tela', 'Quem abre uma tela que o perfil não alcança (por um link antigo ou pelo endereço) vê a página "Você não tem acesso a esta tela", com o botão de voltar e o da Página Inicial, e continua conectado. Antes, aparecia a tela de entrada, como se a pessoa tivesse saído.', 1, 'administrador,secretaria,professor', 'perfis-de-acesso', '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 186);
