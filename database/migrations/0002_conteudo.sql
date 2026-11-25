-- =============================================================================
-- 0002 — CONTEÚDO DE BASE, versão 1.18.4: páginas, menu, níveis, permissões,
-- domínios, parâmetros, versões, novidades e os modelos padrão neutros.
-- Nenhuma linha de pessoa e nenhuma credencial. Gerada do database/conteudo.sql
-- da origem sem a trava pelo nome do banco.
-- =============================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- adms_paginas
DELETE FROM `adms_paginas`;
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (1,'Logar','Login','acesso','login','acesso','Login','Página de Login do sistema.',1,NULL,7,1,1,'2022-03-09 17:18:38','2022-05-19 09:58:01');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (2,'Página Inicial','Home','index','home','index','Página Inicial','Página inicial do sistema. A primeira tela que o usuário acessa ao fazer o login.',2,'fas fa-tachometer-alt',1,1,1,'2022-03-09 17:40:10','2022-05-19 09:58:53');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (3,'Sair','Login','logout','login','logout','Sair','Página de deslogar do sistema. Ele desfaz as session de acesso.',1,'fas fa-sign-out-alt',7,1,1,'2022-03-10 07:44:09','2024-09-10 09:12:54');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (4,'Páginas','Paginas','listar','paginas','listar','Listar Páginas','Listar as páginas da plataforma',2,'fas fa-file-alt',1,1,1,'2022-03-10 14:01:44','2022-03-17 14:25:25');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (5,'Cadastrar Páginas','Paginas','cadastrar','paginas','cadastrar','Cadastrar Páginas','Cadastro de páginas para a plataforma',2,'fas fa-file-alt',2,1,1,'2022-03-10 14:01:44',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (6,'Visualizar','Paginas','visualizar','paginas','visualizar','Ver Páginas','Visualização dos dados da página da plataforma.',2,NULL,5,1,1,'2022-03-10 16:24:51','2022-03-21 16:39:53');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (7,'Editar Página','Paginas','editar','paginas','editar','Editar a Página','Editar página da plataforma',2,NULL,3,1,1,'2022-03-10 16:57:05','2022-03-10 17:26:38');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (8,'Usuários','Usuarios','listar','usuarios','listar','Listar Usuários','Listar os usuários da plataforma',2,'fas fa-users',1,1,1,'2022-03-11 09:52:42','2022-03-22 08:25:58');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (9,'Cadastrar','Usuarios','cadastrar','usuarios','cadastrar','Cadastrar Usuários','Cadastrar usuários da plataforma',2,NULL,2,1,1,'2022-03-11 14:19:57',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (10,'Visualizar','Usuarios','visualizar','usuarios','visualizar','Ver Usuário','Visualizar os dados do usuário.',2,NULL,5,1,1,'2022-03-11 14:30:25',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (11,'Editar','Usuarios','editar','usuarios','editar','Editar Usuário','Editar usuário da plataforma',2,NULL,3,1,1,'2022-03-22 07:50:14',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (12,'Apagar','Usuarios','apagar','usuarios','apagar','Apagar Usuário','Apagar o usuário',2,NULL,4,1,1,'2022-03-22 07:52:03',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (13,'Níveis de acesso','NivelAcesso','listar','nivel-acesso','listar','Listar Niveis de acesso','Listar níveis de acesso da plataforma.',2,'fas fa-key',1,1,1,'2022-03-22 16:47:49','2022-03-23 09:37:44');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (14,'Cadastro','NivelAcesso','cadastrar','nivel-acesso','cadastrar','Cadastrar Níveis de acesso','Criar um perfil de acesso para a plataforma.',2,NULL,2,1,1,'2022-03-22 16:51:57',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (15,'Editar','NivelAcesso','editar','nivel-acesso','editar','Editar Nível de Acesso','Editar o nível de acesso da plataforma.',2,NULL,3,1,1,'2022-03-23 07:47:51',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (16,'Ver Nivel Acesso','NivelAcesso','visualizar','nivel-acesso','visualizar','Visualizar Nivel de Acesso','Ver as informações do Nível de Acesso.',2,NULL,5,1,1,'2022-03-23 07:56:16',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (17,'Apagar Nivel Acesso','NivelAcesso','apagar','nivel-acesso','apagar','Apagar Nível de Acesso','Apagar o Nível de acesso da plataforma.',2,NULL,4,1,1,'2022-03-23 08:00:50',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (18,'Itens de Menu','Menu','listar','menu','listar','Listar os Itens de Menu','Listar os itens do menu da plataforma.',2,'fab fa-elementor',1,1,1,'2022-03-23 08:21:23','2022-03-23 14:54:50');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (19,'Cadastrar','Menu','cadastrar','menu','cadastrar','Cadastrar item de menu','Cadastrar um item de menu.',2,NULL,2,1,1,'2022-03-23 08:25:32',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (20,'Editar','Menu','editar','menu','editar','Editar item de menu','Editar o item do menu',2,NULL,3,1,1,'2022-03-23 08:32:33',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (21,'Visualizar item de menu','Menu','visualizar','menu','visualizar','Visualizar item de menu','Visualizar as informações do item de menu.',2,NULL,5,1,1,'2022-03-23 08:51:02',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (22,'Apagar','Menu','apagar','menu','apagar','Apagar item de menu','Apagar um item de menu.',2,NULL,4,1,1,'2022-03-23 08:54:42',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (23,'Permissões do Nivel de Acesso','NivelAcesso','permissao','nivel-acesso','permissao','Listar Permissões do Nível de Acesso','Listar as permissões de páginas dos niveis de acesso.',2,NULL,1,1,1,'2022-03-24 09:39:54','2022-03-24 10:02:52');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (24,'Liberar Permissão','Liberar','permissao','liberar','permissao','Liberar Permissão Nivel de acesso','Liberar a permissão de acesso a página para o nível de acesso.',2,NULL,3,1,1,'2022-03-24 16:58:31',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (25,'liberar Menu','Liberar','menu','liberar','menu','Liberar Menu para nível de Acesso','Liberar para aparecer no menu do nível de acesso.',2,NULL,3,1,1,'2022-03-25 16:02:57','2022-03-25 16:04:26');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (26,'liberar dropdown no menu','Liberar','dropdown','liberar','dropdown','Liberar dropdown no menu','Liberar o dropdown no menu do nível de acesso.',2,NULL,3,1,1,'2022-03-25 16:04:10',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (27,'Editar opção de menu do nível de acesso','NivelAcesso','itemMenu','nivel-acesso','item-menu','Editar opção de Menu no nível de acesso','Alterar o menu ligado a página no nível de acesso.',2,NULL,3,1,1,'2022-03-25 16:24:16',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (28,'Esqueci a senha','Login','esqueceuSenha','login','esqueceu-senha','Esqueci a Senha','Recuperar senha esquecida.',1,NULL,7,1,1,'2022-03-25 17:13:24','2024-10-23 08:28:56');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (29,'Config.  E-mail','ConfiguracaoEmail','listar','configuracao-email','listar','Configurações de e-mail','Configuração de email do sistema',2,'far fa-envelope',1,1,1,'2022-05-04 08:12:44','2022-05-04 08:14:56');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (30,'Cadastrar Config. Email','ConfiguracaoEmail','cadastrar','configuracao-email','cadastrar','Cadastrar Config. Email','Cadastrar uma configuração de email',2,'',2,1,1,'2022-05-04 08:19:52',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (31,'Editar Config. email','ConfiguracaoEmail','editar','configuracao-email','editar','Editar Config. email','Editar Configuração de email.',2,'',3,1,1,'2022-05-04 08:24:24',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (32,'Visualizar Config. email','ConfiguracaoEmail','visualizar','configuracao-email','visualizar','Visualizar Config. email','Visualizar a configuração de E-mail',2,'',5,1,1,'2022-05-04 08:26:11',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (33,'Apagar Config. email','ConfiguracaoEmail','apagar','configuracao-email','apagar','Apagar Config. email','Apagar Config. email',2,'',4,1,1,'2022-05-04 08:27:32',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (34,'Ordenacao do Menu','Menu','ordenacao','menu','ordenacao','Alterar a Ordem do Menu','Altera a ordem do menu. Ele recalcula as ordens novamente.',2,'',3,1,1,'2022-05-19 14:55:05',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (35,'Atualizar a senha Perdida','Login','atualizarSenha','login','atualizar-senha','Atualizar a Senha perdida','Trocar a senha esquecida do usuário através de um token de confirmação.',1,NULL,3,1,1,'2022-05-20 16:15:22','2024-10-23 10:35:29');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (36,'Perfil','Perfil','visualizar','perfil','visualizar','Visualizar o perfil','Visualizar o próprio perfil. ',2,'',5,1,1,'2022-05-23 11:33:04',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (37,'Editar perfil','Perfil','editar','perfil','editar','Editar o Perfil','Editar os dados do perfil. Menos a senha.',2,'',3,1,1,'2022-05-23 11:37:41',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (38,'Editar senha do usuário','Usuarios','editarSenha','usuarios','editar-senha','Editar Senha de usuário','Editar senha do usuário.',2,'',3,1,1,'2022-05-24 08:20:32',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (39,'Editar Senha','Perfil','editarSenha','perfil','editar-senha','Atualizar minha senha (Perfil)','Atualizar a própria senha.',2,'fas fa-key',3,1,1,'2022-05-24 10:42:32',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (40,'Empresas','Empresas','listar','empresas','listar','Empresas','Listar empresas do sistema.',2,'fas fa-home',1,1,1,'2022-06-01 08:37:26','2024-10-23 16:08:27');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (41,'Cadastrar Empresas','Empresas','cadastrar','empresas','cadastrar','Cadastrar Empresas','Formulário de cadastro das empresas do sistema.',2,NULL,2,1,1,'2022-06-01 08:38:24','2024-10-23 16:09:27');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (42,'Editar Empresas','Empresas','editar','empresas','editar','Editar Empresas','Editar informação das empresas do sistema.',2,NULL,3,1,1,'2022-06-01 08:39:15','2024-10-23 16:10:10');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (43,'Ver Empresas','Empresas','visualizar','empresas','visualizar','Visualizar Empresas','Visualizar a informação de uma empresa do sistema.',2,NULL,5,1,1,'2022-06-01 08:40:21','2024-10-23 16:29:12');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (44,'Apagar Empresa','Empresas','apagar','empresas','apagar','Apagar Empresas','Apagar uma empresa do sistema.',2,NULL,4,1,1,'2022-06-01 08:41:05','2024-10-23 16:31:01');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (45,'ordenação nível de acesso','NivelAcesso','ordenacao','nivel-acesso','ordenacao','Ordenação Nível de Acesso','Editar a ordenação do Nível de Acesso, fazendo com que ele suba uma posição.',2,'',3,1,1,'2022-06-06 14:07:01',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (46,'Usuários Acadêmico','Academicos','listar','academicos','listar','Listar Acadêmicos','Listar usuários do sistema acadêmico.',2,'fas fa-users',1,1,1,'2022-06-07 07:39:36','2022-06-08 09:00:30');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (47,'cadastrar academico','Academicos','cadastrar','academicos','cadastrar','Cadastrar Academico','Cadastrar usuários do sistema acadêmico.',2,NULL,2,1,1,'2022-06-07 07:41:36','2022-06-08 08:55:09');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (48,'Editar usuário acadêmico','Academicos','editar','academicos','editar','Editar acadêmico','Editar usuário do sistema acadêmico.',2,NULL,3,1,1,'2022-06-07 07:42:30','2022-06-08 08:56:08');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (49,'Visualizar o usuário acadêmico','Academicos','visualizar','academicos','visualizar','Visualizar usuário acadêmico','Visualizar o perfil do usuário acadêmico.',2,NULL,5,1,1,'2022-06-07 07:43:25','2022-06-08 08:57:02');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (50,'Apagar usuário acadêmico','Academicos','apagar','academicos','apagar','Apagar academico','Apagar definitivamente o usuário do sistema acadêmico.',2,NULL,4,1,1,'2022-06-07 07:44:34','2022-06-08 08:57:53');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (51,'Acesso Acadêmico','AcessoAcademico','listar','acesso-academico','listar','Listar Níveis acesso acadêmico','Listar os perfis de acesso no sistema acadêmico em relação a pais / alunos ou responsáveis dos alunos.',2,'fas fa-user-lock',1,1,1,'2022-06-07 14:13:56','2024-12-13 07:54:13');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (52,'Cad Nivel acesso','AcessoAcademico','cadastrar','acesso-academico','cadastrar','Cadastrar Nível de acesso educacional','Cadastrar um Nível de acesso à parte relacionada aos alunos, pais ou responsáveis pelos alunos.',2,NULL,2,1,1,'2022-06-07 14:15:43','2024-12-17 08:31:14');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (53,'Edit nivel Acesso','AcessoAcademico','editar','acesso-academico','editar','Editar Niveis de acesso','Editar um nível de acesso  relacionado aos alunos, pais ou responsáveis pelos alunos.',2,NULL,3,1,1,'2022-06-07 14:19:21','2024-12-17 08:46:05');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (54,'Ver nivel de acesso','AcessoAcademico','visualizar','acesso-academico','visualizar','Visualizar nível de acesso','Visualizar um Perfil de acesso relacionada à parte aos alunos, pais ou responsáveis pelos alunos.',2,NULL,5,1,1,'2022-06-07 14:20:26','2024-12-17 08:46:33');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (55,'Apagar nivel acesso','AcessoAcademico','apagar','acesso-academico','apagar','Apagar Nível acesso','apagar um nível de acesso relacionada à parte aos alunos, pais ou responsáveis pelos alunos.',2,NULL,4,1,1,'2022-06-07 14:21:14','2024-12-17 08:47:06');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (56,'Editar senha acadêmico','Academicos','editarSenha','academicos','editar-senha','Editar senha acadêmico','Editar acesso do usuário acadêmico no sistema.',2,NULL,3,1,1,'2022-06-09 07:46:58','2026-03-13 17:12:32');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (57,'menus Permissão','NivelAcesso','buscaMenu','nivel-acesso','busca-menu','Busca menus para permissão','página de consulta dos itens de menu para liberar o nível de acesso.',2,'',6,1,1,'2024-09-26 14:51:05',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (58,'edit menu acesso página','Paginas','editarMenu','paginas','editar-menu','Alterar menu do nivel de acesso','Formulário para editar a linha do nível de acesso dentro de uma página.',2,'',3,1,1,'2024-09-26 16:11:36',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (59,'Atualizar páginas do nível de acesso','NivelAcesso','atualizarPermissao','nivel-acesso','atualizar-permissao','Atualizar páginas do nível de acesso','Página para atualizar as páginas do nível de acesso.',2,'',6,1,1,'2024-10-22 09:24:51',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (60,'Cad. filial Empresa','Empresas','adicionarFilial','empresas','adicionar-filial','Adicionar Filial Empresa','Adicionar filial da empresa.',2,'',2,1,1,'2024-10-29 15:58:10',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (61,'Editar filial Empresa','Empresas','editarFilial','empresas','editar-filial','Editar Filial Empresa','Editar filial de empresa.',2,'',3,1,1,'2024-10-29 15:59:00',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (62,'Desativar Filial Empresa','Empresas','apagarFilial','empresas','apagar-filial','Desativar Filial Empresa','Desativa filial de uma empresa.',2,'',4,1,1,'2024-11-01 15:35:24',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (63,'Páginas Educacional','PaginasAcademico','listar','paginas-academico','listar','Páginas Sistema Acadêmico','Páginas para o sistema acadêmico de acesso dos alunos, pais e responsáveis.',2,'fas fa-graduation-cap',1,1,1,'2024-12-18 08:52:48','2024-12-18 09:23:06');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (64,'Cadastrar Página Acadêmico','PaginasAcademico','cadastrar','paginas-academico','cadastrar','Cadastrar Páginas Acadêmico','Cadastrar páginas  do sistema acadêmico de acesso dos alunos, pais e responsáveis.',2,'',2,1,1,'2024-12-18 09:22:35',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (65,'Ver Páginas Acadêmico','PaginasAcademico','visualizar','paginas-academico','visualizar','Ver Página Acadêmico','Visualizar as páginas do sistema acadêmico de acesso dos alunos, pais e responsáveis.',2,'',5,1,1,'2024-12-18 09:23:54',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (66,'Editar Páginas Acadêmico','PaginasAcademico','editar','paginas-academico','editar','Editar Páginas Acadêmico','Editar página do sistema acadêmico de acesso dos alunos, pais e responsáveis.',2,'',3,1,1,'2024-12-18 09:25:11',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (67,'Apagar Página Acadêmico','PaginasAcademico','apagar','paginas-academico','apagar','Apagar Página Acadêmico','Apagar página do sistema acadêmico de acesso dos alunos, pais e responsáveis.',2,'',4,1,1,'2024-12-18 09:25:59',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (68,'Editar Menu Página Acadêmico','PaginasAcademico','editarMenu','paginas-academico','editar-menu','Editar Menu Página Acadêmico','Página para editar menu do nível de acesso acadêmico dos alunos, pais e responsáveis.',2,NULL,3,1,1,'2024-12-18 10:53:38','2024-12-18 15:56:37');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (69,'Liberar Página Acadêmico','LiberarAcademico','permissao','liberar-academico','permissao','Liberar Página Acadêmico','Página para liberação de acesso do sistema de alunos, pais e responsáveis.',2,'',3,1,1,'2024-12-18 15:31:44',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (70,'Liberar menu acadêmico','LiberarAcademico','menu','liberar-academico','menu','Liberar no menu Acadêmico','Página para liberação do menu no sistema dos alunos, pais e responsáveis.',2,'',3,1,1,'2024-12-18 15:33:25',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (71,'Liberar dropdown acadêmico','LiberarAcademico','dropdown','liberar-academico','dropdown','Liberação dropdown acadêmico','Página para liberar o dropdown no sistema acadêmico dos alunos, pais e responsáveis.',2,'',3,1,1,'2024-12-18 15:35:03',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (72,'Buscar Menu Acesso Acadêmico','AcessoAcademico','buscaMenu','acesso-academico','busca-menu','Buscar Menu acesso Academico','Página para buscar menu do acesso acadêmico dos alunos, pais e responsáveis.',2,'',1,1,1,'2024-12-18 16:40:02',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (73,'Permissão Acesso Acadêmico','AcessoAcademico','permissao','acesso-academico','permissao','Permissões Acesso Acadêmico','Listar as permissões de acesso do sistema dos alunos, pais e responsáveis. [FORA DO AR desde 07/09/2026 - a classe ou o metodo nao existe, e o ConfigController desvia para o login sem mensagem. Conferido por token_get_all, nao so por regex. Volta ligando permissao=1 quando o codigo existir.]',2,'',1,1,1,'2024-12-19 07:17:34','2026-09-07 23:32:48');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (74,'Editar opção de Menu no acesso acadêmico','AcessoAcademico','itemMenu','acesso-academico','item-menu','Editar item de Menu do acesso acadêmico','Alterar o menu ligado a página no acesso acadêmico dos alunos, pais e responsáveis. [FORA DO AR desde 07/09/2026 - a classe ou o metodo nao existe, e o ConfigController desvia para o login sem mensagem. Conferido por token_get_all, nao so por regex. Volta ligando permissao=1 quando o codigo existir.]',2,'',3,1,1,'2024-12-19 07:20:24','2026-09-07 23:32:48');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (75,'Ensino','Cursos','listar','cursos','listar',' lista de Cursos (Ensino)','Listar os cursos do gerenciados.',2,'fas fa-school',1,1,1,'2024-12-19 07:38:35','2025-01-07 16:06:02');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (76,'Cadastrar Curso','Cursos','cadastrar','cursos','cadastrar','Cadastrar curso','Formulário para cadastrar o curso do sistema.',2,'',2,1,1,'2024-12-19 07:40:28',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (77,'Visualizar Curso','Cursos','visualizar','cursos','visualizar','Visualizar Curso','Página para visualizar o curso.',2,'',5,1,1,'2024-12-19 07:41:13',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (78,'Editar Curso','Cursos','editar','cursos','editar','Editar Curso','Formulário para editar o curso.',2,'',3,1,1,'2024-12-19 07:41:48',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (79,'Apagar Curso','Cursos','apagar','cursos','apagar','Apagar Curso','Página para apagar o curso.',2,'',4,1,1,'2024-12-19 07:42:43',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (80,'Matérias de Ensino','Materias','listar','materias','listar','Listar Matérias de Ensino','Lista de matérias gerenciadas pelas unidades de ensino.',2,'fas fa-chalkboard',1,1,1,'2024-12-19 07:45:13',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (81,'Cadastrar Matérias','Materias','cadastrar','materias','cadastrar','Cadastrar Matérias','Formulário para cadastro de matérias do sistema.',2,'',2,1,1,'2024-12-19 07:46:03',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (82,'Visualizar Matéria','Materias','visualizar','materias','visualizar','Visualizar Matéria','Página para visualizar a matéria do sistema.',2,'',5,1,1,'2024-12-19 07:46:41',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (83,'Editar Matéria','Materias','editar','materias','editar','Editar Matérias','Formulário para editar as materias.',2,'',3,1,1,'2024-12-19 07:47:38',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (84,'apagar matéria','Materias','apagar','materias','apagar','Apagar Matérias','Página para apagar  a matéria do sistema.',2,'',4,1,1,'2024-12-19 07:48:22',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (91,'Todas as Disciplinas','Disciplinas','listar','disciplinas','listar','Listar Disciplinas','Página para listar as disciplinas do sistema.',2,'fas fa-landmark',1,1,1,'2024-12-19 16:10:02','2025-01-19 20:14:23');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (92,'Cadastrar Disciplinas','Disciplinas','cadastrar','disciplinas','cadastrar','Cadastrar Disciplinas','Formulário para cadastrar a disciplina.',2,'',2,1,1,'2024-12-19 16:10:39',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (93,'Editar Disciplina','Disciplinas','editar','disciplinas','editar','Editar Disciplinas','Formulário para editar as disciplinas do sistema.',2,'',3,1,1,'2024-12-19 16:11:15',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (94,'Visualizar Disciplina','Disciplinas','visualizar','disciplinas','visualizar','Visualizar Disciplina','Página para visualizar a disciplina do sistema.',2,'',5,1,1,'2024-12-19 16:12:02',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (95,'Apagar Disciplina','Disciplinas','apagar','disciplinas','apagar','Apagar Disciplina','Página que desabilita a disciplina do sistema.',2,'',4,1,1,'2024-12-19 16:12:40',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (96,'Faltas da Disciplina','Faltas','visualizar','faltas','visualizar','Lista Faltas da Disciplina','Página para ver as faltas da disciplina.',2,'',5,1,1,'2024-12-20 12:51:12',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (104,'Adicionar Professor Disciplina','Disciplinas','adicionaProfessor','disciplinas','adiciona-professor','Adicionar Professor disciplina','Formulário para ligar professor a disciplina.',2,'',3,1,1,'2025-01-13 09:26:28',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (105,'Editar Falta por Bimestre','Faltas','editar','faltas','editar','Editar Falta por Bimestre','Formulário para editar as faltas de uma disciplina em um determinado bimestre.',2,'',3,1,1,'2025-01-15 09:25:20',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (107,'Cadastro de coordenação do Ensino','Cursos','cadastrarUsuario','cursos','cadastrar-usuario','Cadastro de coordenação do Ensino','Formulário para liberar o usuário no Ensino.',2,'',2,1,1,'2025-01-16 07:09:39',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (109,'Atividades para nota','Atividades','listar','atividades','listar','Atividades para Nota','Página para listar as atividades da disciplina.',2,'',1,1,1,'2025-01-17 09:01:39',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (110,'Cadastrar atividade para nota','Atividades','cadastrar','atividades','cadastrar','Cadastrar atividade para nota','Formulário para cadastrar atividades na disciplina.',2,'',2,1,1,'2025-01-17 16:26:31',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (111,'Visualizar a atividade para nota','Atividades','visualizar','atividades','visualizar','Visualizar a atividade para nota','Página para visualizar a atividade para nota da disciplina.',2,'',5,1,1,'2025-01-17 16:27:14',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (112,'Editar atividade para nota','Atividades','editar','atividades','editar','Editar atividade para nota','Formulário para editar a atividade da disciplina.',2,'',3,1,1,'2025-01-17 16:28:06',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (113,'Apagar atividade para nota','Atividades','apagar','atividades','apagar','Apagar atividade para nota','Página para apagar a atividade para nota da disciplina.',2,'',4,1,1,'2025-01-17 16:29:05',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (114,'Editar Nota Atividade','Atividades','editNotas','atividades','edit-notas','Editar Nota Atividade','Formulário para editar as notas de uma atividade da disciplina.',2,'',3,1,1,'2025-01-17 18:29:01',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (115,'Minhas Disciplinas','MinhasDisciplinas','listar','minhas-disciplinas','listar','Minhas Disciplinas','Página para mostrar as disciplinas do usuário',2,'fas fa-chalkboard-teacher',1,1,1,'2025-01-19 13:07:25','2025-01-19 20:39:18');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (116,'Horários','HorarioAula','listar','horario-aula','listar','Horário Aula','Página para listar os horário das disciplinas.',2,'fa-solid fa-stopwatch',1,1,1,'2025-01-20 11:25:41',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (117,'Visualizar Horário Aula','HorarioAula','visualizar','horario-aula','visualizar','Visualizar Horário Aula','Formulário para cadastrar, editar e visualizar o horário das aulas.',2,NULL,5,1,1,'2025-01-20 11:27:08','2025-01-21 07:19:37');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (118,'Editar Horário Aula','HorarioAula','editar','horario-aula','editar','Editar Horário Aula','Formulário para editar o horário de aula.',2,'',3,1,1,'2025-01-21 07:33:54',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (119,'Salas de Aula','SalasDeAula','listar','salas-de-aula','listar','Salas de Aula','Página para listar salas de aula.',2,'fas fa-laptop-house',1,1,1,'2025-01-21 18:22:20',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (120,'Cadastrar Salas de aula','SalasDeAula','cadastrar','salas-de-aula','cadastrar','Cadastrar Salas de Aula','Formulário para cadastrar as salas de aula.',2,'',2,1,1,'2025-01-21 18:23:33',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (121,'Visualizar Sala de Aula','SalasDeAula','visualizar','salas-de-aula','visualizar','Visualizar Sala de Aula','Página para visualizar a sala de aula.',2,'',5,1,1,'2025-01-21 18:24:28',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (122,'Editar Sala de Aula','SalasDeAula','editar','salas-de-aula','editar','Editar Sala de Aula','Formulário para editar a sala de aula.',2,'',3,1,1,'2025-01-21 18:25:09',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (123,'Apagar Sala de Aula','SalasDeAula','apagar','salas-de-aula','apagar','Apagar sala de aula','Apagar Sala de Aula.',2,'',4,1,1,'2025-01-21 18:25:43',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (124,'Cadastrar Sala na disciplina','HorarioAula','cadastrarSala','horario-aula','cadastrar-sala','Cadastrar Sala na disciplina','Formulário para cadastrar salas na disciplina.',2,'',2,1,1,'2025-01-22 10:10:43',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (135,'Médias da disciplina','Medias','final','medias','final','Médias da discplina','Página mostra a média final da disciplina.',2,'',5,1,1,'2025-01-28 09:29:24',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (137,'Desligar Coordenador Curso','Cursos','apagarUsuario','cursos','apagar-usuario','Desligar Coordenador Curso','Desligar coordenador do curso.',2,'',4,1,1,'2025-01-30 17:31:15',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (138,'Editar usuário curso','Cursos','editarUsuario','cursos','editar-usuario','Editar usuário curso','Editar usuário do curso.',2,'',3,1,1,'2025-01-30 17:31:58',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (148,'Adicionar Disc. Sistema Polivalente','Disciplinas','adicionaPolivalencia','disciplinas','adiciona-polivalencia','Adiciona Disciplina sistema polivalente','Adicionar uma disciplina para o sistema de polivalência.',2,'',2,1,1,'2025-06-12 15:38:29',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (149,'Disciplinas Polivalentes','Disciplinas','verPolivalente','disciplinas','ver-polivalente','Disciplinas Polivalentes','Ver lista de disciplinas ligadas a disciplina polivalente.',2,'',5,1,1,'2025-06-12 16:47:48',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (150,'Apagar Disciplinas Polivalentes','Disciplinas','apagarPolivalencia','disciplinas','apagar-polivalencia','Apagar Disciplinas Polivalentes','Apagar a ligação das disciplinas polivalentes.',2,'',4,1,1,'2025-06-12 17:06:32',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (183,'Listar Turmas Series selecoes','Buscar','turmas','buscar','turmas','Listar Turmas Series selecoes','Listar seleção de turmas dos ensinos.',2,'',1,1,1,'2025-09-29 06:58:13',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (220,'Seleção de série do ensino','Buscar','turmaserie','buscar','turmaserie','Seleção de série do ensino','Fazer a busca das turmas séries de um ensino para o seletor de ensino. (option)',2,'',1,1,1,'2025-12-11 17:17:47',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (240,'Gerar Disciplinas do novo ano.','Disciplinas','gerarNovoano','disciplinas','gerar-novoano','Gerar Disciplinas do novo ano.','Página para copiar todas as disciplinas de um ano para outro.',2,'',2,1,1,'2026-01-15 07:55:22',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (241,'Portal Alunos','PortalAlunos','listar','portal-alunos','listar','Portal Alunos','Página para listar e controlar os alunos e suas disciplinas vinculadas.',2,'',1,1,1,'2026-01-15 10:14:37',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (244,'Matriz Disciplinas do Ano','Disciplinas','matrizAno','disciplinas','matriz-ano','Matriz Disciplinas do Ano','Página para listar e ver a lista de disciplinas que serão criadas no ano. [FORA DO AR desde 07/09/2026 - a classe ou o metodo nao existe, e o ConfigController desvia para o login sem mensagem. Conferido por token_get_all, nao so por regex. Volta ligando permissao=1 quando o codigo existir.]',2,'',1,1,1,'2026-01-16 16:16:48','2026-09-07 23:32:48');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (249,'Visualizar Portal Alunos','PortalAlunos','visualizar','portal-alunos','visualizar','Visualizar Portal Alunos','Página para visualizar o aluno do portal com as disciplinas.',2,'',5,1,1,'2026-01-20 11:25:16',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (250,'Ordenar Portal Alunos','PortalAlunos','ordenacao','portal-alunos','ordenacao','Ordenar Portal Alunos','Formulário para ordenar os alunos do ensino de um período',2,'',6,1,1,'2026-01-20 12:37:27',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (255,'Upload Notas','Atividades','uploadNotas','atividades','upload-notas','Upload Notas','Formulário para fazer o upload das notas na atividade.',2,'',3,1,1,'2026-02-03 13:06:13',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (263,'Notas das Atividades','Home','notasAtividades','home','notas-atividades','Notas das Atividades','Exibe as notas das atividades de um ou mais alunos durante um determinado ciclo letivo [FORA DO AR desde 07/09/2026 - a classe ou o metodo nao existe, e o ConfigController desvia para o login sem mensagem. Conferido por token_get_all, nao so por regex. Volta ligando permissao=1 quando o codigo existir.]',2,'',1,1,1,'2026-03-10 13:46:45','2026-09-07 23:32:48');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (264,'Boletim','Academicos','boletim','academicos','boletim','Boletim do Aluno','Visualizar o boletim do aluno por ano letivo',2,NULL,1,1,1,'2026-03-11 10:27:44','2026-08-28 15:19:53');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (273,'Boletim Aluno','BoletimAluno','listar','boletim-aluno','listar','Boletim Aluno','Exibe o boletim do aluno a partir de um determinado ano',2,'',1,1,1,'2026-04-18 13:31:22',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (295,'Impressão do Boletim','Academicos','imprimirBoletim','academicos','imprimir-boletim','Impressão do Boletim','Gerar arquivo de impressão do boletim do aluno.',2,'',6,1,1,'2026-08-28 16:33:38',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (305,'Anos Letivos','AnosLetivos','listar','anos-letivos','listar','Anos Letivos','Lista dos anos letivos. E a fonte do ano corrente e do filtro de ano de sete telas.',2,'far fa-calendar-alt',1,1,1,'2026-09-07 23:17:11','2026-09-07 23:21:15');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (306,'Anos Letivos','AnosLetivos','cadastrar','anos-letivos','cadastrar','Cadastrar Ano Letivo','Formulario para cadastrar um ano letivo novo.',2,NULL,2,1,1,'2026-09-07 23:17:11',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (307,'Anos Letivos','AnosLetivos','editar','anos-letivos','editar','Editar Ano Letivo','Formulario para editar o ano letivo e as tres bandeiras.',2,NULL,3,1,1,'2026-09-07 23:17:11',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (308,'Anos Letivos','AnosLetivos','apagar','anos-letivos','apagar','Apagar Ano Letivo','Apaga o ano letivo. Recusado quando ha matricula no ano.',2,NULL,4,1,1,'2026-09-07 23:17:11',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (332,'Séries','Series','listar','series','listar','Séries','',2,'fas fa-layer-group',1,1,1,'2026-09-07 23:18:06','2026-09-07 23:18:18');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (333,'Cadastrar Série','Series','cadastrar','series','cadastrar','Cadastrar Série','',2,'fas fa-plus',1,1,1,'2026-09-07 23:18:06','2026-09-07 23:18:18');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (334,'Editar Série','Series','editar','series','editar','Editar Série','',2,'fas fa-edit',1,1,1,'2026-09-07 23:18:06','2026-09-07 23:18:18');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (335,'Apagar Série','Series','apagar','series','apagar','Apagar Série','',2,'fas fa-trash',1,1,1,'2026-09-07 23:18:06','2026-09-07 23:18:18');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (364,'Parametros do sistema','Parametros','listar','parametros','listar','Configuracao que muda por instalacao','O que muda por instalacao e nao e segredo. Segredo continua no Config.php. [FORA DO AR desde 07/09/2026 - a classe ou o metodo nao existe, e o ConfigController desvia para o login sem mensagem. Conferido por token_get_all, nao so por regex. Volta ligando permissao=1 quando o codigo existir.]',2,'fas fa-sliders',1,1,1,'2026-09-07 23:29:17','2026-09-07 23:33:06');
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (365,'Salvar parametro','Parametros','salvar','parametros','salvar','Grava um parametro','POST. [FORA DO AR desde 07/09/2026 - a classe ou o metodo nao existe, e o ConfigController desvia para o login sem mensagem. Conferido por token_get_all, nao so por regex. Volta ligando permissao=1 quando o codigo existir.]',2,NULL,1,1,1,'2026-09-07 23:29:17','2026-09-07 23:33:06');

-- adms_menus
DELETE FROM `adms_menus`;
INSERT INTO `adms_menus` (`id`, `nome`, `icone`, `ordem`, `adms_sit_id`, `created`, `modified`) VALUES (1,'Dashboard','fas fa-tachometer-alt',1,1,'2018-05-23 00:00:00','2024-09-10 09:11:56');
INSERT INTO `adms_menus` (`id`, `nome`, `icone`, `ordem`, `adms_sit_id`, `created`, `modified`) VALUES (2,'Usuários','fas fa-user',2,1,'2018-05-23 00:00:00','2024-09-10 09:11:56');
INSERT INTO `adms_menus` (`id`, `nome`, `icone`, `ordem`, `adms_sit_id`, `created`, `modified`) VALUES (3,'Sair','fas fa-sign-out-alt',7,1,'2018-06-23 00:00:00','2025-10-21 16:55:52');
INSERT INTO `adms_menus` (`id`, `nome`, `icone`, `ordem`, `adms_sit_id`, `created`, `modified`) VALUES (4,'Configurações','fas fa-cogs',3,1,'2018-06-23 00:00:00','2024-09-27 15:07:44');
INSERT INTO `adms_menus` (`id`, `nome`, `icone`, `ordem`, `adms_sit_id`, `created`, `modified`) VALUES (5,'Educacional','fas fa-user-graduate',5,1,'2022-03-23 16:18:56','2024-12-19 08:26:04');
INSERT INTO `adms_menus` (`id`, `nome`, `icone`, `ordem`, `adms_sit_id`, `created`, `modified`) VALUES (7,'Ensino','fas fa-university',4,1,'2024-12-19 08:25:53','2024-12-20 14:50:17');

-- adms_nivacs_pgs
DELETE FROM `adms_nivacs_pgs`;
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1,1,1,2,2,1,1,1,'2022-03-10 09:39:52','2022-03-17 09:04:10');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2,1,2,2,1,1,1,2,'2022-03-10 09:40:30','2024-10-22 09:18:03');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (3,1,1,2,2,1,2,1,'2022-03-10 09:39:52','2022-03-17 09:04:27');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (4,1,2,2,1,1,2,2,'2022-03-10 09:40:30','2024-10-23 08:17:56');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (5,1,4,2,1,3,1,3,'2022-03-10 09:40:30','2024-12-19 07:21:43');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (6,1,3,1,1,4,1,4,'2022-03-10 09:40:30','2022-03-17 09:52:36');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (7,1,5,2,2,4,1,5,'2022-03-10 09:40:30','2024-12-18 15:28:36');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (8,1,6,1,2,1,1,6,'2022-03-10 09:40:30','2022-03-22 08:24:16');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (9,1,7,1,2,1,1,7,'2022-03-10 09:40:30','2022-03-22 08:24:19');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (10,1,8,1,1,2,1,8,'2022-03-10 09:40:30','2022-03-25 16:15:01');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (11,1,9,1,2,2,1,9,'2022-03-10 09:40:30','2022-03-22 08:24:21');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (12,1,10,1,2,2,1,10,'2022-03-22 09:40:30','2022-03-22 08:24:24');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (13,1,11,1,2,2,1,11,'2022-03-22 09:40:30','2022-03-22 08:24:26');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (14,1,12,1,2,2,1,12,'2022-03-22 09:40:30','2022-03-22 08:24:29');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (15,1,13,1,1,2,1,13,'2022-03-22 09:40:30','2022-03-23 09:34:17');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (16,1,14,1,2,2,1,14,'2022-03-22 09:40:30',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (17,1,15,1,2,2,1,15,'2022-03-22 09:40:30',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (18,1,16,1,2,2,1,16,'2022-03-22 09:40:30',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (19,1,17,1,2,2,1,17,'2022-03-22 09:40:30',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (20,1,18,1,1,4,1,18,'2022-03-22 09:40:30','2022-03-23 14:54:22');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (21,1,19,1,2,2,1,19,'2022-03-23 08:25:32','2022-03-23 08:31:12');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (22,1,3,2,2,4,2,19,'2022-03-23 08:25:33','2024-12-20 07:41:28');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (23,1,20,2,2,4,1,20,'2022-03-23 08:32:33','2022-03-23 08:47:28');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (24,1,4,2,2,4,2,20,'2022-03-23 08:32:33','2024-12-20 07:41:29');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (25,1,21,2,2,4,1,21,'2022-03-23 08:51:02',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (26,1,5,2,2,4,2,21,'2022-03-23 08:51:02','2024-12-20 13:49:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (27,1,22,2,2,4,1,22,'2022-03-23 08:54:42',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (28,1,6,2,2,4,2,22,'2022-03-23 08:54:42','2024-12-20 13:49:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (29,1,23,2,2,4,1,23,'2022-03-24 09:39:55',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (30,1,7,2,2,4,2,23,'2022-03-24 09:39:55','2024-12-20 13:49:48');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (32,1,24,2,2,4,1,24,'2022-03-24 16:58:31',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (33,1,8,2,2,4,2,24,'2022-03-24 16:58:31','2024-12-20 13:49:48');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (35,1,25,2,2,4,1,25,'2022-03-25 16:02:57',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (36,1,9,2,2,4,2,25,'2022-03-25 16:02:57','2024-12-20 13:49:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (38,1,26,2,2,4,1,26,'2022-03-25 16:04:10',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (39,1,10,2,2,4,2,26,'2022-03-25 16:04:10','2024-12-20 13:49:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (41,1,27,2,2,4,1,27,'2022-03-25 16:24:16',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (42,1,11,2,2,4,2,27,'2022-03-25 16:24:16','2024-12-20 13:49:50');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (44,1,28,2,2,4,1,28,'2022-03-25 17:13:24',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (45,1,12,2,2,4,2,28,'2022-03-25 17:13:24','2024-10-23 08:28:19');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (47,1,29,1,1,4,1,29,'2022-05-04 08:12:44','2022-05-04 08:13:10');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (48,1,13,2,2,4,2,29,'2022-05-04 08:12:44','2024-12-20 13:49:51');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (50,1,30,2,2,4,1,30,'2022-05-04 08:19:52',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (51,1,14,2,2,4,2,30,'2022-05-04 08:19:52','2024-12-20 13:49:52');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (53,1,31,2,2,4,1,31,'2022-05-04 08:24:24',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (54,1,15,2,2,4,2,31,'2022-05-04 08:24:24','2024-12-20 13:50:01');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (56,1,32,2,2,4,1,32,'2022-05-04 08:26:11',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (57,1,16,2,2,4,2,32,'2022-05-04 08:26:11','2024-12-20 13:50:01');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (59,1,33,2,2,4,1,33,'2022-05-04 08:27:32',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (60,1,17,2,2,4,2,33,'2022-05-04 08:27:32','2024-12-20 13:50:02');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (62,1,34,2,2,4,1,34,'2022-05-19 14:55:05',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (63,1,18,2,2,4,2,34,'2022-05-19 14:55:05','2024-12-20 13:50:02');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (65,1,35,2,2,4,1,35,'2022-05-20 16:15:22',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (66,1,19,2,2,4,2,35,'2022-05-20 16:15:22','2024-10-23 10:35:32');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (68,1,36,2,2,4,1,36,'2022-05-23 11:33:04',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (69,1,20,2,2,4,2,36,'2022-05-23 11:33:04','2024-12-20 13:50:03');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (71,1,37,2,2,4,1,37,'2022-05-23 11:37:41',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (72,1,21,2,2,4,2,37,'2022-05-23 11:37:41','2024-12-20 13:50:04');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (74,1,38,2,2,4,1,38,'2022-05-24 08:20:32',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (75,1,22,2,2,4,2,38,'2022-05-24 08:20:32','2024-12-20 13:50:04');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (77,1,39,2,2,4,1,39,'2022-05-24 10:42:32',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (78,1,23,2,2,4,2,39,'2022-05-24 10:42:32','2024-12-20 13:50:06');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (80,1,40,1,1,4,1,40,'2022-06-01 08:37:26','2022-06-01 08:47:36');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (81,1,24,1,1,4,2,40,'2022-06-01 08:37:26','2024-12-20 13:51:54');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (83,1,41,2,2,4,1,41,'2022-06-01 08:38:24',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (84,1,25,2,2,4,2,41,'2022-06-01 08:38:24','2024-10-23 16:09:30');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (86,1,42,2,2,4,1,42,'2022-06-01 08:39:15',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (87,1,26,2,2,4,2,42,'2022-06-01 08:39:15','2024-10-23 16:10:12');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (89,1,43,2,2,4,1,43,'2022-06-01 08:40:21',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (90,1,27,2,2,4,2,43,'2022-06-01 08:40:21','2024-10-23 16:29:15');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (92,1,44,2,2,4,1,44,'2022-06-01 08:41:06',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (93,1,28,2,2,4,2,44,'2022-06-01 08:41:06','2024-10-23 16:31:03');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (95,1,45,2,2,4,1,45,'2022-06-06 14:07:02',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (96,1,29,2,2,4,2,45,'2022-06-06 14:07:02','2024-12-20 13:50:06');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (98,1,46,1,1,5,1,46,'2022-06-07 07:39:36','2022-06-07 07:51:18');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (99,1,30,2,2,4,2,46,'2022-06-07 07:39:36','2024-12-20 13:50:07');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (101,1,47,2,2,4,1,47,'2022-06-07 07:41:36',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (102,1,31,2,2,4,2,47,'2022-06-07 07:41:36','2024-12-20 13:50:08');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (104,1,48,2,2,4,1,48,'2022-06-07 07:42:31',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (105,1,32,2,2,4,2,48,'2022-06-07 07:42:31','2024-12-20 13:50:08');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (107,1,49,2,2,4,1,49,'2022-06-07 07:43:25',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (108,1,33,2,2,4,2,49,'2022-06-07 07:43:26','2024-12-20 13:50:08');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (110,1,50,2,2,4,1,50,'2022-06-07 07:44:34',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (111,1,34,2,2,4,2,50,'2022-06-07 07:44:34','2024-12-20 13:50:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (113,1,51,1,1,5,1,51,'2022-06-07 14:13:56','2022-06-07 14:45:59');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (114,1,35,2,2,4,2,51,'2022-06-07 14:13:56','2024-12-20 13:50:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (116,1,52,2,2,4,1,52,'2022-06-07 14:15:43',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (117,1,36,2,2,4,2,52,'2022-06-07 14:15:43','2024-12-20 13:50:10');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (119,1,53,2,2,4,1,53,'2022-06-07 14:19:21',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (120,1,37,2,2,4,2,53,'2022-06-07 14:19:21','2024-12-20 13:50:10');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (122,1,54,2,2,4,1,54,'2022-06-07 14:20:26',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (123,1,38,2,2,4,2,54,'2022-06-07 14:20:27','2024-12-20 13:50:12');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (125,1,55,2,2,4,1,55,'2022-06-07 14:21:14',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (126,1,39,2,2,4,2,55,'2022-06-07 14:21:14','2024-12-20 13:50:13');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (128,1,56,2,2,4,1,56,'2022-06-09 07:46:59',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (129,1,40,2,2,4,2,56,'2022-06-09 07:46:59','2024-12-20 13:50:13');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (131,1,57,2,2,4,1,57,'2024-09-26 14:51:05',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (132,1,41,2,2,4,2,57,'2024-09-26 14:51:05','2024-12-20 13:50:14');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (134,1,58,2,2,4,1,58,'2024-09-26 16:11:36',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (135,1,42,2,2,4,2,58,'2024-09-26 16:11:36','2024-12-20 13:50:14');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (137,1,59,2,2,4,1,59,'2024-10-22 09:24:51',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (138,1,43,2,2,4,2,59,'2024-10-22 09:24:51','2024-12-20 13:50:14');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (141,1,44,2,1,3,2,3,'2024-10-22 10:38:59','2024-12-20 07:41:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (142,2,45,2,2,4,2,4,'2024-10-22 10:38:59','2024-12-20 13:50:56');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (143,2,46,2,2,4,2,5,'2024-10-22 10:38:59','2024-12-20 13:50:57');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (144,2,47,2,2,4,2,6,'2024-10-22 10:38:59','2024-12-20 13:50:57');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (145,2,48,2,2,4,2,7,'2024-10-22 10:38:59','2024-12-20 13:50:58');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (146,1,49,1,1,2,2,8,'2024-10-22 10:39:00','2024-12-20 13:51:16');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (147,1,50,2,2,4,2,9,'2024-10-22 10:39:00','2024-12-20 07:41:18');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (148,1,51,2,2,4,2,10,'2024-10-22 10:39:00','2024-12-20 07:41:19');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (149,1,52,2,2,4,2,11,'2024-10-22 10:39:00','2024-12-20 07:41:21');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (150,1,53,2,2,4,2,12,'2024-10-22 10:39:00','2024-12-20 07:41:22');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (151,1,54,1,1,2,2,13,'2024-10-22 10:39:00','2024-12-20 13:51:31');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (152,1,55,2,2,4,2,14,'2024-10-22 10:39:00','2024-12-20 07:41:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (153,1,56,2,2,4,2,15,'2024-10-22 10:39:00','2024-12-20 07:41:26');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (154,1,57,2,2,4,2,16,'2024-10-22 10:39:00','2024-12-20 07:41:26');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (155,1,58,2,2,4,2,17,'2024-10-22 10:39:00','2024-12-20 07:41:27');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (156,1,59,2,2,4,2,18,'2024-10-22 10:39:00','2024-12-20 07:41:28');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (238,1,60,2,2,4,1,60,'2024-10-29 15:58:10',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (239,1,60,2,2,4,2,60,'2024-10-29 15:58:10','2024-12-20 13:50:15');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (240,1,1,2,2,4,3,60,'2024-10-29 15:58:10','2025-02-26 17:49:26');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (241,1,61,2,2,4,1,61,'2024-10-29 15:59:00',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (242,1,61,2,2,4,2,61,'2024-10-29 15:59:00','2024-12-20 13:50:15');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (243,1,2,2,2,4,3,61,'2024-10-29 15:59:00','2025-02-26 17:49:31');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (244,1,62,2,2,4,1,62,'2024-11-01 15:35:24',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (245,1,62,2,2,4,2,62,'2024-11-01 15:35:24','2024-12-20 13:50:16');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (246,1,3,2,2,4,3,62,'2024-11-01 15:35:24','2025-02-26 17:49:32');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (247,1,63,1,1,5,1,63,'2024-12-18 08:52:48','2024-12-18 09:14:04');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (248,1,63,2,2,4,2,63,'2024-12-18 08:52:48','2024-12-18 09:14:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (249,2,4,2,2,4,3,63,'2024-12-18 08:52:48','2025-02-26 17:49:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (250,1,64,2,2,4,1,64,'2024-12-18 09:22:35',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (251,1,64,2,2,4,2,64,'2024-12-18 09:22:35','2024-12-20 13:50:17');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (252,2,5,2,2,4,3,64,'2024-12-18 09:22:36',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (253,1,65,2,2,4,1,65,'2024-12-18 09:23:54',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (254,1,65,2,2,4,2,65,'2024-12-18 09:23:54','2024-12-20 13:50:18');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (255,2,6,2,2,4,3,65,'2024-12-18 09:23:54',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (256,1,66,2,2,4,1,66,'2024-12-18 09:25:11',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (257,1,66,2,2,4,2,66,'2024-12-18 09:25:11','2024-12-20 13:50:19');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (258,2,7,2,2,4,3,66,'2024-12-18 09:25:11',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (259,1,67,2,2,4,1,67,'2024-12-18 09:25:59',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (260,1,67,2,2,4,2,67,'2024-12-18 09:25:59','2024-12-20 13:50:19');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (261,2,8,2,2,4,3,67,'2024-12-18 09:25:59',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (262,1,68,2,2,4,1,68,'2024-12-18 10:53:38',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (263,1,68,2,2,4,2,68,'2024-12-18 10:53:38','2024-12-20 13:50:19');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (264,2,9,2,2,4,3,68,'2024-12-18 10:53:38',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (265,1,69,2,2,4,1,69,'2024-12-18 15:31:45',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (266,1,69,2,2,4,2,69,'2024-12-18 15:31:45','2024-12-20 13:50:20');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (267,2,10,2,2,4,3,69,'2024-12-18 15:31:45',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (268,1,70,2,2,4,1,70,'2024-12-18 15:33:25',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (269,1,70,2,2,4,2,70,'2024-12-18 15:33:25','2024-12-20 13:50:20');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (270,2,11,2,2,4,3,70,'2024-12-18 15:33:25',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (271,1,71,2,2,4,1,71,'2024-12-18 15:35:03',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (272,1,71,2,2,4,2,71,'2024-12-18 15:35:03','2024-12-20 13:50:21');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (273,2,12,2,2,4,3,71,'2024-12-18 15:35:03',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (274,1,72,2,2,4,1,72,'2024-12-18 16:40:02',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (275,1,72,2,2,4,2,72,'2024-12-18 16:40:02','2024-12-20 13:50:21');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (276,2,13,2,2,4,3,72,'2024-12-18 16:40:02',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (277,2,73,2,2,4,1,73,'2024-12-19 07:17:34','2026-09-07 23:32:48');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (278,2,73,2,2,4,2,73,'2024-12-19 07:17:34','2026-09-07 23:32:48');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (279,2,14,2,2,4,3,73,'2024-12-19 07:17:34',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (280,2,74,2,2,4,1,74,'2024-12-19 07:20:25','2026-09-07 23:32:48');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (281,2,74,2,2,4,2,74,'2024-12-19 07:20:25','2026-09-07 23:32:48');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (282,2,15,2,2,4,3,74,'2024-12-19 07:20:25',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (283,1,75,1,1,7,1,75,'2024-12-19 07:38:35','2024-12-19 08:26:30');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (284,1,75,2,2,4,2,75,'2024-12-19 07:38:35','2024-12-20 13:50:24');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (285,1,16,1,1,7,3,75,'2024-12-19 07:38:35','2025-02-26 17:49:58');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (286,1,76,2,2,4,1,76,'2024-12-19 07:40:28',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (287,1,76,2,2,4,2,76,'2024-12-19 07:40:28','2024-12-20 13:50:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (288,1,17,2,2,4,3,76,'2024-12-19 07:40:28','2025-02-26 17:49:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (289,1,77,2,2,4,1,77,'2024-12-19 07:41:13',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (290,1,77,2,2,4,2,77,'2024-12-19 07:41:14','2024-12-20 13:50:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (291,1,18,2,2,4,3,77,'2024-12-19 07:41:14','2025-02-26 17:49:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (292,1,78,2,2,4,1,78,'2024-12-19 07:41:48',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (293,1,78,2,2,4,2,78,'2024-12-19 07:41:48','2024-12-20 13:50:26');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (294,1,19,2,2,4,3,78,'2024-12-19 07:41:48','2025-02-26 17:49:50');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (295,1,79,2,2,4,1,79,'2024-12-19 07:42:43',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (296,1,79,2,2,4,2,79,'2024-12-19 07:42:43','2024-12-20 13:50:26');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (297,1,20,2,2,4,3,79,'2024-12-19 07:42:43','2025-02-26 17:49:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (298,1,80,1,1,7,1,80,'2024-12-19 07:45:13','2024-12-20 13:49:32');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (299,1,80,2,2,4,2,80,'2024-12-19 07:45:13','2024-12-20 13:50:27');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (300,1,21,1,1,7,3,80,'2024-12-19 07:45:13','2025-02-26 17:50:12');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (301,1,81,2,2,4,1,81,'2024-12-19 07:46:03',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (302,1,81,2,2,4,2,81,'2024-12-19 07:46:03','2024-12-20 13:50:27');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (303,1,22,2,2,4,3,81,'2024-12-19 07:46:03','2025-02-26 17:51:32');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (304,1,82,2,2,4,1,82,'2024-12-19 07:46:41',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (305,1,82,2,2,4,2,82,'2024-12-19 07:46:41','2024-12-20 13:50:27');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (306,1,23,2,2,4,3,82,'2024-12-19 07:46:41','2025-02-26 17:52:07');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (307,1,83,2,2,4,1,83,'2024-12-19 07:47:38',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (308,1,83,2,2,4,2,83,'2024-12-19 07:47:38','2024-12-20 13:50:28');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (309,1,24,2,2,4,3,83,'2024-12-19 07:47:38','2025-02-26 17:52:08');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (310,1,84,2,2,4,1,84,'2024-12-19 07:48:22',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (311,1,84,2,2,4,2,84,'2024-12-19 07:48:22','2024-12-20 13:50:28');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (312,1,25,2,2,4,3,84,'2024-12-19 07:48:22','2025-02-26 17:52:10');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (319,1,91,1,1,7,1,91,'2024-12-20 13:48:27','2024-12-20 13:49:00');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (320,1,92,2,2,4,1,92,'2024-12-20 13:48:27','2024-12-20 13:48:36');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (321,1,93,2,2,4,1,93,'2024-12-20 13:48:27','2024-12-20 13:48:36');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (322,1,94,2,2,4,1,94,'2024-12-20 13:48:27','2024-12-20 13:48:43');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (323,1,95,2,2,4,1,95,'2024-12-20 13:48:27','2024-12-20 13:48:34');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (324,1,96,2,2,4,1,96,'2024-12-20 13:48:27','2024-12-20 13:48:38');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (331,1,91,2,2,4,2,91,'2024-12-20 13:49:44','2024-12-20 13:50:33');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (332,1,92,2,2,4,2,92,'2024-12-20 13:49:44','2024-12-20 13:50:34');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (333,1,93,2,2,4,2,93,'2024-12-20 13:49:44','2024-12-20 13:50:34');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (334,1,94,2,2,4,2,94,'2024-12-20 13:49:44','2024-12-20 13:50:35');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (335,1,95,2,2,4,2,95,'2024-12-20 13:49:44','2024-12-20 13:50:37');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (336,1,96,2,2,4,2,96,'2024-12-20 13:49:44','2024-12-20 13:50:37');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (337,1,26,2,2,1,3,1,'2024-12-20 13:56:39','2025-02-27 09:57:51');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (338,1,27,2,1,3,3,3,'2024-12-20 13:56:39','2025-02-26 17:47:27');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (339,1,28,2,2,4,3,28,'2024-12-20 13:56:39','2025-02-26 17:48:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (340,1,29,2,2,4,3,35,'2024-12-20 13:56:39','2025-02-26 17:48:37');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (341,1,30,2,1,1,3,2,'2024-12-20 13:56:39','2025-02-27 09:57:57');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (342,2,31,2,2,4,3,4,'2024-12-20 13:56:39','2025-02-26 17:51:50');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (343,2,32,2,2,4,3,5,'2024-12-20 13:56:39','2025-02-26 17:51:37');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (344,2,33,2,2,4,3,6,'2024-12-20 13:56:39','2025-02-26 17:51:36');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (345,2,34,2,2,4,3,7,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (346,1,35,1,1,2,3,8,'2024-12-20 13:56:39','2025-02-26 17:47:43');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (347,1,36,2,2,4,3,9,'2024-12-20 13:56:39','2025-02-26 17:47:32');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (348,1,37,2,2,4,3,10,'2024-12-20 13:56:39','2025-02-26 17:47:45');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (349,1,38,2,2,4,3,11,'2024-12-20 13:56:39','2025-02-26 17:47:48');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (350,1,39,2,2,4,3,12,'2024-12-20 13:56:39','2025-02-26 17:47:50');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (351,2,40,2,2,4,3,13,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (352,2,41,2,2,4,3,14,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (353,2,42,2,2,4,3,15,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (354,2,43,2,2,4,3,16,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (355,2,44,2,2,4,3,17,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (356,2,45,2,2,4,3,18,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (357,2,46,2,2,4,3,19,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (358,2,47,2,2,4,3,20,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (359,2,48,2,2,4,3,21,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (360,2,49,2,2,4,3,22,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (361,2,50,2,2,4,3,23,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (362,2,51,2,2,4,3,24,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (363,2,52,2,2,4,3,25,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (364,2,53,2,2,4,3,26,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (365,2,54,2,2,4,3,27,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (366,2,55,2,2,4,3,29,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (367,2,56,2,2,4,3,30,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (368,2,57,2,2,4,3,31,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (369,2,58,2,2,4,3,32,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (370,2,59,2,2,4,3,33,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (371,2,60,2,2,4,3,34,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (372,1,61,2,2,4,3,36,'2024-12-20 13:56:39','2025-02-26 17:48:38');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (373,1,62,2,2,4,3,37,'2024-12-20 13:56:39','2025-02-26 17:48:38');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (374,1,63,2,2,4,3,38,'2024-12-20 13:56:39','2025-02-26 17:48:40');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (375,1,64,2,2,4,3,39,'2024-12-20 13:56:39','2025-02-26 17:48:41');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (376,1,65,2,2,4,3,40,'2024-12-20 13:56:39','2025-02-26 17:48:43');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (377,1,66,2,2,4,3,41,'2024-12-20 13:56:39','2025-02-26 17:48:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (378,1,67,2,2,4,3,42,'2024-12-20 13:56:39','2025-02-26 17:48:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (379,1,68,2,2,4,3,43,'2024-12-20 13:56:39','2025-02-26 17:48:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (380,1,69,2,2,4,3,44,'2024-12-20 13:56:39','2025-02-26 17:48:48');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (381,2,70,2,2,4,3,45,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (382,1,71,1,1,5,3,46,'2024-12-20 13:56:39','2025-02-26 17:59:18');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (383,1,72,2,2,4,3,47,'2024-12-20 13:56:39','2025-02-26 17:48:59');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (384,1,73,2,2,4,3,48,'2024-12-20 13:56:39','2025-02-26 17:49:00');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (385,1,74,2,2,4,3,49,'2024-12-20 13:56:39','2025-02-26 17:49:00');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (386,1,75,2,2,4,3,50,'2024-12-20 13:56:39','2025-02-26 17:49:01');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (387,2,76,2,2,4,3,51,'2024-12-20 13:56:39','2025-02-26 17:58:58');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (388,1,77,2,2,4,3,52,'2024-12-20 13:56:39','2025-02-26 17:49:13');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (389,1,78,2,2,4,3,53,'2024-12-20 13:56:39','2025-02-26 17:49:14');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (390,1,79,2,2,4,3,54,'2024-12-20 13:56:39','2025-02-26 17:49:15');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (391,1,80,2,2,4,3,55,'2024-12-20 13:56:39','2025-02-26 17:49:19');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (392,1,81,2,2,4,3,56,'2024-12-20 13:56:39','2026-03-13 17:09:44');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (393,2,82,2,2,4,3,57,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (394,2,83,2,2,4,3,58,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (395,2,84,2,2,4,3,59,'2024-12-20 13:56:39','2024-12-20 13:56:39');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (402,1,91,1,1,7,3,91,'2024-12-20 13:56:39','2025-02-26 17:52:31');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (403,1,92,2,2,4,3,92,'2024-12-20 13:56:39','2025-02-26 17:52:22');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (404,1,93,2,2,4,3,93,'2024-12-20 13:56:39','2025-02-26 17:52:22');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (405,1,94,2,2,4,3,94,'2024-12-20 13:56:39','2025-02-26 17:52:23');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (406,1,95,2,2,4,3,95,'2024-12-20 13:56:39','2025-02-26 17:52:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (407,1,96,2,2,4,3,96,'2024-12-20 13:56:39','2025-02-26 17:52:37');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (429,1,104,2,2,4,1,104,'2025-01-13 09:26:28',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (430,2,104,2,2,4,2,104,'2025-01-13 09:26:28',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (431,1,104,2,2,4,3,104,'2025-01-13 09:26:28','2025-02-26 17:52:45');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (432,1,105,2,2,4,1,105,'2025-01-15 09:25:20',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (433,2,105,2,2,4,2,105,'2025-01-15 09:25:20',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (434,1,105,2,2,4,3,105,'2025-01-15 09:25:20','2025-02-26 17:52:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (438,1,107,2,2,4,1,107,'2025-01-16 07:09:39',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (439,2,107,2,2,4,2,107,'2025-01-16 07:09:39',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (440,1,107,2,2,4,3,107,'2025-01-16 07:09:39','2025-02-26 17:52:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (444,1,109,2,2,4,1,109,'2025-01-17 09:01:39',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (445,2,109,2,2,4,2,109,'2025-01-17 09:01:39',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (446,1,109,2,2,4,3,109,'2025-01-17 09:01:39','2025-02-26 17:52:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (447,1,110,2,2,4,1,110,'2025-01-17 16:26:31',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (448,2,110,2,2,4,2,110,'2025-01-17 16:26:31',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (449,1,110,2,2,4,3,110,'2025-01-17 16:26:31','2025-02-26 17:52:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (450,1,111,2,2,4,1,111,'2025-01-17 16:27:14',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (451,2,111,2,2,4,2,111,'2025-01-17 16:27:14',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (452,1,111,2,2,4,3,111,'2025-01-17 16:27:14','2025-02-26 17:53:03');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (453,1,112,2,2,4,1,112,'2025-01-17 16:28:06',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (454,2,112,2,2,4,2,112,'2025-01-17 16:28:06',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (455,1,112,2,2,4,3,112,'2025-01-17 16:28:06','2025-02-26 17:53:04');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (456,1,113,2,2,4,1,113,'2025-01-17 16:29:05',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (457,2,113,2,2,4,2,113,'2025-01-17 16:29:05',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (458,1,113,2,2,4,3,113,'2025-01-17 16:29:05','2025-02-26 17:53:04');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (459,1,0,2,2,4,4,1,'2025-01-17 16:46:25','2025-09-22 14:55:01');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (460,1,0,2,1,3,4,3,'2025-01-17 16:46:25','2025-10-23 14:54:06');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (461,1,0,2,2,4,4,28,'2025-01-17 16:46:25','2025-10-23 14:54:23');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (462,2,0,2,2,4,4,35,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (463,2,0,2,2,4,4,68,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (464,1,0,2,1,1,4,2,'2025-01-17 16:46:25','2025-10-23 14:54:00');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (465,2,0,2,2,4,4,4,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (466,2,0,2,2,4,4,5,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (467,2,0,2,2,4,4,6,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (468,2,0,2,2,4,4,7,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (469,2,0,2,2,4,4,8,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (470,2,0,2,2,4,4,9,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (471,2,0,2,2,4,4,10,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (472,2,0,2,2,4,4,11,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (473,2,0,2,2,4,4,12,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (474,2,0,2,2,4,4,13,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (475,2,0,2,2,4,4,14,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (476,2,0,2,2,4,4,15,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (477,2,0,2,2,4,4,16,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (478,2,0,2,2,4,4,17,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (479,2,0,2,2,4,4,18,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (480,2,0,2,2,4,4,19,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (481,2,0,2,2,4,4,20,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (482,2,0,2,2,4,4,21,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (483,2,0,2,2,4,4,22,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (484,2,0,2,2,4,4,23,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (485,2,0,2,2,4,4,24,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (486,2,0,2,2,4,4,25,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (487,2,0,2,2,4,4,26,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (488,2,0,2,2,4,4,27,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (489,2,0,2,2,4,4,29,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (490,2,0,2,2,4,4,30,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (491,2,0,2,2,4,4,31,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (492,2,0,2,2,4,4,32,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (493,2,0,2,2,4,4,33,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (494,2,0,2,2,4,4,34,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (495,1,0,2,2,4,4,36,'2025-01-17 16:46:25','2025-10-23 14:54:29');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (496,1,0,2,2,4,4,37,'2025-01-17 16:46:25','2025-10-23 14:54:30');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (497,1,0,2,2,4,4,38,'2025-01-17 16:46:25','2025-10-23 14:54:31');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (498,1,0,2,2,4,4,39,'2025-01-17 16:46:25','2025-10-23 14:54:31');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (499,2,0,2,2,4,4,40,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (500,2,0,2,2,4,4,41,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (501,2,0,2,2,4,4,42,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (502,2,0,2,2,4,4,43,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (503,2,0,2,2,4,4,44,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (504,2,0,2,2,4,4,45,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (505,1,0,1,1,5,4,46,'2025-01-17 16:46:25','2025-10-23 14:55:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (506,1,0,2,2,4,4,47,'2025-01-17 16:46:25','2026-01-09 09:47:28');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (507,1,0,2,2,4,4,48,'2025-01-17 16:46:25','2026-01-09 09:47:29');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (508,1,0,2,2,4,4,49,'2025-01-17 16:46:25','2025-10-23 14:55:35');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (509,2,0,2,2,4,4,50,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (510,2,0,2,2,4,4,51,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (511,2,0,2,2,4,4,52,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (512,2,0,2,2,4,4,53,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (513,2,0,2,2,4,4,54,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (514,2,0,2,2,4,4,55,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (515,1,0,2,2,4,4,56,'2025-01-17 16:46:25','2026-01-09 09:48:59');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (516,2,0,2,2,4,4,57,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (517,2,0,2,2,4,4,58,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (518,2,0,2,2,4,4,59,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (519,2,0,2,2,4,4,60,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (520,2,0,2,2,4,4,61,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (521,2,0,2,2,4,4,62,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (522,2,0,2,2,4,4,63,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (523,2,0,2,2,4,4,64,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (524,2,0,2,2,4,4,65,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (525,2,0,2,2,4,4,66,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (526,2,0,2,2,4,4,67,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (527,2,0,2,2,4,4,69,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (528,2,0,2,2,4,4,70,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (529,2,0,2,2,4,4,71,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (530,2,0,2,2,4,4,72,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (531,2,0,2,2,4,4,73,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (532,2,0,2,2,4,4,74,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (533,2,0,2,2,4,4,75,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (534,2,0,2,2,4,4,76,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (535,2,0,2,2,4,4,77,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (536,2,0,2,2,4,4,78,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (537,2,0,2,2,4,4,79,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (538,2,0,2,2,4,4,80,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (539,2,0,2,2,4,4,81,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (540,2,0,2,2,4,4,82,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (541,2,0,2,2,4,4,83,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (542,2,0,2,2,4,4,84,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (549,2,0,2,2,4,4,91,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (550,2,0,2,2,4,4,92,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (551,2,0,2,2,4,4,93,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (552,2,0,2,2,4,4,94,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (553,2,0,2,2,4,4,95,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (554,2,0,2,2,4,4,96,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (562,2,0,2,2,4,4,104,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (563,2,0,2,2,4,4,105,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (565,2,0,2,2,4,4,107,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (567,2,0,2,2,4,4,109,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (568,2,0,2,2,4,4,110,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (569,2,0,2,2,4,4,111,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (570,2,0,2,2,4,4,112,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (571,2,0,2,2,4,4,113,'2025-01-17 16:46:25','2025-01-17 16:46:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (572,1,0,2,2,1,5,1,'2025-01-17 16:46:42','2025-03-11 08:50:28');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (573,1,0,2,1,3,5,3,'2025-01-17 16:46:42','2025-02-13 14:38:29');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (574,1,0,2,2,4,5,28,'2025-01-17 16:46:42','2025-03-11 08:49:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (575,2,0,2,2,4,5,35,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (576,2,0,2,2,4,5,68,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (577,1,0,2,1,1,5,2,'2025-01-17 16:46:42','2025-03-11 08:50:36');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (578,2,0,2,2,4,5,4,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (579,2,0,2,2,4,5,5,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (580,2,0,2,2,4,5,6,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (581,2,0,2,2,4,5,7,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (582,2,0,2,2,4,5,8,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (583,2,0,2,2,4,5,9,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (584,2,0,2,2,4,5,10,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (585,2,0,2,2,4,5,11,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (586,2,0,2,2,4,5,12,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (587,2,0,2,2,4,5,13,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (588,2,0,2,2,4,5,14,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (589,2,0,2,2,4,5,15,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (590,2,0,2,2,4,5,16,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (591,2,0,2,2,4,5,17,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (592,2,0,2,2,4,5,18,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (593,2,0,2,2,4,5,19,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (594,2,0,2,2,4,5,20,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (595,2,0,2,2,4,5,21,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (596,2,0,2,2,4,5,22,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (597,2,0,2,2,4,5,23,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (598,2,0,2,2,4,5,24,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (599,2,0,2,2,4,5,25,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (600,2,0,2,2,4,5,26,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (601,2,0,2,2,4,5,27,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (602,2,0,2,2,4,5,29,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (603,2,0,2,2,4,5,30,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (604,2,0,2,2,4,5,31,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (605,2,0,2,2,4,5,32,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (606,2,0,2,2,4,5,33,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (607,2,0,2,2,4,5,34,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (608,1,0,2,2,4,5,36,'2025-01-17 16:46:42','2025-03-11 08:49:15');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (609,1,0,2,2,4,5,37,'2025-01-17 16:46:42','2025-03-11 08:49:16');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (610,2,0,2,2,4,5,38,'2025-01-17 16:46:42','2025-03-11 08:49:19');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (611,1,0,2,2,4,5,39,'2025-01-17 16:46:42','2025-03-11 08:49:20');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (612,2,0,2,2,4,5,40,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (613,2,0,2,2,4,5,41,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (614,2,0,2,2,4,5,42,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (615,2,0,2,2,4,5,43,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (616,2,0,2,2,4,5,44,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (617,2,0,2,2,4,5,45,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (618,1,0,2,2,4,5,46,'2025-01-17 16:46:42','2025-03-11 08:51:58');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (619,1,0,2,2,4,5,47,'2025-01-17 16:46:42','2025-03-11 08:51:59');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (620,1,0,2,2,4,5,48,'2025-01-17 16:46:42','2025-03-11 08:52:01');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (621,1,0,2,2,4,5,49,'2025-01-17 16:46:42','2025-03-11 08:52:01');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (622,2,0,2,2,4,5,50,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (623,2,0,2,2,4,5,51,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (624,2,0,2,2,4,5,52,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (625,2,0,2,2,4,5,53,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (626,2,0,2,2,4,5,54,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (627,2,0,2,2,4,5,55,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (628,2,0,2,2,4,5,56,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (629,2,0,2,2,4,5,57,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (630,2,0,2,2,4,5,58,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (631,2,0,2,2,4,5,59,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (632,2,0,2,2,4,5,60,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (633,2,0,2,2,4,5,61,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (634,2,0,2,2,4,5,62,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (635,2,0,2,2,4,5,63,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (636,2,0,2,2,4,5,64,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (637,2,0,2,2,4,5,65,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (638,2,0,2,2,4,5,66,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (639,2,0,2,2,4,5,67,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (640,2,0,2,2,4,5,69,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (641,2,0,2,2,4,5,70,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (642,2,0,2,2,4,5,71,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (643,2,0,2,2,4,5,72,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (644,2,0,2,2,4,5,73,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (645,2,0,2,2,4,5,74,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (646,1,0,1,1,7,5,75,'2025-01-17 16:46:42','2025-03-11 08:52:32');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (647,1,0,2,2,4,5,76,'2025-01-17 16:46:42','2025-03-11 08:52:34');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (648,1,0,2,2,4,5,77,'2025-01-17 16:46:42','2025-03-11 08:52:35');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (649,1,0,2,2,4,5,78,'2025-01-17 16:46:42','2025-03-11 08:52:36');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (650,2,0,2,2,4,5,79,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (651,1,0,2,2,4,5,80,'2025-01-17 16:46:42','2025-03-11 08:52:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (652,1,0,2,2,4,5,81,'2025-01-17 16:46:42','2025-03-11 08:52:45');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (653,1,0,2,2,4,5,82,'2025-01-17 16:46:42','2025-03-11 08:52:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (654,1,0,2,2,4,5,83,'2025-01-17 16:46:42','2025-03-11 08:52:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (655,2,0,2,2,4,5,84,'2025-01-17 16:46:42','2025-01-17 16:46:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (662,1,0,1,1,7,5,91,'2025-01-17 16:46:42','2025-03-11 09:14:22');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (663,1,0,2,2,4,5,92,'2025-01-17 16:46:42','2025-03-11 09:02:15');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (664,1,0,2,2,4,5,93,'2025-01-17 16:46:42','2025-03-11 09:02:16');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (665,1,0,2,2,4,5,94,'2025-01-17 16:46:42','2025-03-11 09:02:18');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (666,2,0,2,2,4,5,95,'2025-01-17 16:46:42','2025-03-11 08:50:55');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (667,1,0,2,2,4,5,96,'2025-01-17 16:46:42','2025-03-11 08:50:58');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (675,1,0,2,2,4,5,104,'2025-01-17 16:46:42','2025-03-11 08:51:00');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (676,1,0,2,2,4,5,105,'2025-01-17 16:46:42','2025-03-11 08:53:04');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (678,1,0,2,2,4,5,107,'2025-01-17 16:46:42','2025-03-11 08:53:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (680,1,0,2,2,4,5,109,'2025-01-17 16:46:42','2025-03-11 08:53:07');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (681,1,0,2,2,4,5,110,'2025-01-17 16:46:42','2025-03-11 08:53:07');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (682,1,0,2,2,4,5,111,'2025-01-17 16:46:42','2025-03-11 08:53:10');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (683,1,0,2,2,4,5,112,'2025-01-17 16:46:42','2025-03-11 08:53:10');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (684,1,0,2,2,4,5,113,'2025-01-17 16:46:42','2025-03-11 08:53:11');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (685,1,0,2,2,4,6,1,'2025-01-17 16:46:46','2025-01-17 16:46:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (686,1,0,2,1,3,6,3,'2025-01-17 16:46:46','2025-01-17 16:47:11');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (687,1,0,2,2,4,6,28,'2025-01-17 16:46:46','2025-01-19 13:26:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (688,2,0,2,2,4,6,35,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (689,2,0,2,2,4,6,68,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (690,1,0,2,1,1,6,2,'2025-01-17 16:46:46','2025-01-17 16:47:04');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (691,2,0,2,2,4,6,4,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (692,2,0,2,2,4,6,5,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (693,2,0,2,2,4,6,6,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (694,2,0,2,2,4,6,7,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (695,2,0,2,2,4,6,8,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (696,2,0,2,2,4,6,9,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (697,2,0,2,2,4,6,10,'2025-01-17 16:46:46','2025-01-20 08:45:57');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (698,2,0,2,2,4,6,11,'2025-01-17 16:46:46','2025-01-20 08:45:58');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (699,2,0,2,2,4,6,12,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (700,2,0,2,2,4,6,13,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (701,2,0,2,2,4,6,14,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (702,2,0,2,2,4,6,15,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (703,2,0,2,2,4,6,16,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (704,2,0,2,2,4,6,17,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (705,2,0,2,2,4,6,18,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (706,2,0,2,2,4,6,19,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (707,2,0,2,2,4,6,20,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (708,2,0,2,2,4,6,21,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (709,2,0,2,2,4,6,22,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (710,2,0,2,2,4,6,23,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (711,2,0,2,2,4,6,24,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (712,2,0,2,2,4,6,25,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (713,2,0,2,2,4,6,26,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (714,2,0,2,2,4,6,27,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (715,2,0,2,2,4,6,29,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (716,2,0,2,2,4,6,30,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (717,2,0,2,2,4,6,31,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (718,2,0,2,2,4,6,32,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (719,2,0,2,2,4,6,33,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (720,2,0,2,2,4,6,34,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (721,1,0,2,2,4,6,36,'2025-01-17 16:46:46','2025-01-19 13:26:59');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (722,1,0,2,2,4,6,37,'2025-01-17 16:46:46','2025-01-19 13:27:00');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (723,2,0,2,2,4,6,38,'2025-01-17 16:46:46','2025-01-19 13:27:07');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (724,1,0,2,2,4,6,39,'2025-01-17 16:46:46','2025-01-19 13:27:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (725,2,0,2,2,4,6,40,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (726,2,0,2,2,4,6,41,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (727,2,0,2,2,4,6,42,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (728,2,0,2,2,4,6,43,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (729,2,0,2,2,4,6,44,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (730,2,0,2,2,4,6,45,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (731,2,0,2,2,4,6,46,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (732,2,0,2,2,4,6,47,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (733,2,0,2,2,4,6,48,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (734,2,0,2,2,4,6,49,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (735,2,0,2,2,4,6,50,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (736,2,0,2,2,4,6,51,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (737,2,0,2,2,4,6,52,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (738,2,0,2,2,4,6,53,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (739,2,0,2,2,4,6,54,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (740,2,0,2,2,4,6,55,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (741,2,0,2,2,4,6,56,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (742,2,0,2,2,4,6,57,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (743,2,0,2,2,4,6,58,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (744,2,0,2,2,4,6,59,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (745,2,0,2,2,4,6,60,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (746,2,0,2,2,4,6,61,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (747,2,0,2,2,4,6,62,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (748,2,0,2,2,4,6,63,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (749,2,0,2,2,4,6,64,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (750,2,0,2,2,4,6,65,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (751,2,0,2,2,4,6,66,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (752,2,0,2,2,4,6,67,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (753,2,0,2,2,4,6,69,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (754,2,0,2,2,4,6,70,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (755,2,0,2,2,4,6,71,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (756,2,0,2,2,4,6,72,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (757,2,0,2,2,4,6,73,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (758,2,0,2,2,4,6,74,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (759,2,0,2,2,4,6,75,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (760,2,0,2,2,4,6,76,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (761,2,0,2,2,4,6,77,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (762,2,0,2,2,4,6,78,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (763,2,0,2,2,4,6,79,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (764,2,0,2,2,4,6,80,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (765,2,0,2,2,4,6,81,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (766,1,0,2,2,4,6,82,'2025-01-17 16:46:46','2025-01-19 13:27:31');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (767,2,0,2,2,4,6,83,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (768,2,0,2,2,4,6,84,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (775,2,0,2,2,4,6,91,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (776,2,0,2,2,4,6,92,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (777,2,0,2,2,4,6,93,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (778,1,0,2,2,4,6,94,'2025-01-17 16:46:46','2025-01-19 13:27:42');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (779,2,0,2,2,4,6,95,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (780,1,0,2,2,4,6,96,'2025-01-17 16:46:46','2025-01-19 13:27:48');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (788,2,0,2,2,4,6,104,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (789,1,0,2,2,4,6,105,'2025-01-17 16:46:46','2025-01-20 09:07:02');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (791,2,0,2,2,4,6,107,'2025-01-17 16:46:46','2025-01-17 16:46:46');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (793,1,0,2,2,4,6,109,'2025-01-17 16:46:46','2025-01-19 13:28:08');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (794,1,0,2,2,4,6,110,'2025-01-17 16:46:46','2025-01-19 13:28:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (795,1,0,2,2,4,6,111,'2025-01-17 16:46:46','2025-01-19 13:28:12');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (796,1,0,2,2,4,6,112,'2025-01-17 16:46:46','2025-01-19 13:28:17');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (797,1,0,2,2,4,6,113,'2025-01-17 16:46:46','2025-01-19 13:28:19');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (798,1,114,2,2,4,1,114,'2025-01-17 18:29:01',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (799,2,114,2,2,4,2,114,'2025-01-17 18:29:01',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (800,1,114,2,2,4,3,114,'2025-01-17 18:29:01','2025-02-26 17:53:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (801,2,1,2,2,4,4,114,'2025-01-17 18:29:01',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (802,1,1,2,2,4,5,114,'2025-01-17 18:29:01','2025-03-11 08:53:11');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (803,1,1,2,2,4,6,114,'2025-01-17 18:29:01','2025-01-19 13:28:20');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (804,1,115,1,1,7,1,115,'2025-01-19 13:07:25','2025-01-19 13:24:44');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (805,2,115,2,2,4,2,115,'2025-01-19 13:07:25',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (806,1,115,2,2,4,3,115,'2025-01-19 13:07:25','2026-03-13 11:22:33');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (807,2,2,2,2,4,4,115,'2025-01-19 13:07:25',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (808,1,2,2,2,4,5,115,'2025-01-19 13:07:25','2025-03-11 08:51:08');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (809,1,2,1,1,7,6,115,'2025-01-19 13:07:25','2026-01-21 09:32:34');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (810,1,116,2,2,4,1,116,'2025-01-20 11:25:41',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (811,2,116,2,2,4,2,116,'2025-01-20 11:25:41',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (812,1,116,2,2,4,3,116,'2025-01-20 11:25:41','2025-02-26 17:53:10');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (813,2,3,2,2,4,4,116,'2025-01-20 11:25:41',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (814,2,3,2,2,4,5,116,'2025-01-20 11:25:41','2025-03-11 08:53:22');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (815,2,3,2,2,4,6,116,'2025-01-20 11:25:41',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (816,1,117,2,2,4,1,117,'2025-01-20 11:27:08',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (817,2,117,2,2,4,2,117,'2025-01-20 11:27:08',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (818,1,117,2,2,4,3,117,'2025-01-20 11:27:08','2025-02-26 17:53:10');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (819,2,4,2,2,4,4,117,'2025-01-20 11:27:08',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (820,2,4,2,2,4,5,117,'2025-01-20 11:27:08','2025-03-11 08:53:20');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (821,2,4,2,2,4,6,117,'2025-01-20 11:27:08',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (822,1,118,2,2,4,1,118,'2025-01-21 07:33:54',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (823,2,118,2,2,4,2,118,'2025-01-21 07:33:54',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (824,1,118,2,2,4,3,118,'2025-01-21 07:33:54','2025-02-26 17:53:11');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (825,2,5,2,2,4,4,118,'2025-01-21 07:33:54',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (826,2,5,2,2,4,5,118,'2025-01-21 07:33:54','2025-03-11 08:53:19');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (827,2,5,2,2,4,6,118,'2025-01-21 07:33:54',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (828,1,119,2,2,4,1,119,'2025-01-21 18:22:20',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (829,2,119,2,2,4,2,119,'2025-01-21 18:22:20',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (830,1,119,2,2,4,3,119,'2025-01-21 18:22:20','2025-02-26 17:53:12');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (831,2,6,2,2,4,4,119,'2025-01-21 18:22:20',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (832,2,6,2,2,4,5,119,'2025-01-21 18:22:20','2025-03-11 08:53:17');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (833,2,6,2,2,4,6,119,'2025-01-21 18:22:20',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (834,1,120,2,2,4,1,120,'2025-01-21 18:23:33',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (835,2,120,2,2,4,2,120,'2025-01-21 18:23:33',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (836,1,120,2,2,4,3,120,'2025-01-21 18:23:33','2025-02-26 17:53:14');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (837,2,7,2,2,4,4,120,'2025-01-21 18:23:33',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (838,2,7,2,2,4,5,120,'2025-01-21 18:23:33','2025-03-11 08:53:17');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (839,2,7,2,2,4,6,120,'2025-01-21 18:23:33',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (840,1,121,2,2,4,1,121,'2025-01-21 18:24:28',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (841,2,121,2,2,4,2,121,'2025-01-21 18:24:28',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (842,1,121,2,2,4,3,121,'2025-01-21 18:24:28','2025-02-26 17:53:16');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (843,2,8,2,2,4,4,121,'2025-01-21 18:24:28',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (844,2,8,2,2,4,5,121,'2025-01-21 18:24:28',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (845,2,8,2,2,4,6,121,'2025-01-21 18:24:28',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (846,1,122,2,2,4,1,122,'2025-01-21 18:25:09',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (847,2,122,2,2,4,2,122,'2025-01-21 18:25:09',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (848,1,122,2,2,4,3,122,'2025-01-21 18:25:09','2025-02-26 17:53:16');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (849,2,9,2,2,4,4,122,'2025-01-21 18:25:09',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (850,2,9,2,2,4,5,122,'2025-01-21 18:25:09',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (851,2,9,2,2,4,6,122,'2025-01-21 18:25:09',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (852,1,123,2,2,4,1,123,'2025-01-21 18:25:43',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (853,2,123,2,2,4,2,123,'2025-01-21 18:25:43',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (854,1,123,2,2,4,3,123,'2025-01-21 18:25:43','2025-02-26 17:53:17');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (855,2,10,2,2,4,4,123,'2025-01-21 18:25:43',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (856,2,10,2,2,4,5,123,'2025-01-21 18:25:43',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (857,2,10,2,2,4,6,123,'2025-01-21 18:25:43',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (858,1,124,2,2,4,1,124,'2025-01-22 10:10:43',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (859,2,124,2,2,4,2,124,'2025-01-22 10:10:43',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (860,1,124,2,2,4,3,124,'2025-01-22 10:10:43','2025-02-26 17:53:17');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (861,2,11,2,2,4,4,124,'2025-01-22 10:10:43',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (862,2,11,2,2,4,5,124,'2025-01-22 10:10:43','2025-03-11 08:53:30');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (863,2,11,2,2,4,6,124,'2025-01-22 10:10:43',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (924,1,135,2,2,4,1,135,'2025-01-28 09:29:24',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (925,1,135,2,2,4,2,135,'2025-01-28 09:29:24','2025-02-28 15:16:14');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (926,1,135,2,2,4,3,135,'2025-01-28 09:29:24','2025-02-26 17:53:26');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (927,2,22,2,2,4,4,135,'2025-01-28 09:29:24',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (928,1,22,2,2,4,5,135,'2025-01-28 09:29:24','2025-02-28 15:16:15');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (929,1,22,2,2,4,6,135,'2025-01-28 09:29:24','2025-02-13 10:52:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (936,1,137,2,2,4,1,137,'2025-01-30 17:31:15',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (937,1,137,2,2,4,2,137,'2025-01-30 17:31:15','2025-04-08 15:18:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (938,1,137,2,2,4,3,137,'2025-01-30 17:31:15','2025-02-26 17:53:28');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (939,2,24,2,2,4,4,137,'2025-01-30 17:31:15',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (940,2,24,2,2,4,5,137,'2025-01-30 17:31:15',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (941,2,24,2,2,4,6,137,'2025-01-30 17:31:15',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (942,1,138,2,2,4,1,138,'2025-01-30 17:31:58',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (943,1,138,2,2,4,2,138,'2025-01-30 17:31:58','2025-04-08 15:18:51');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (944,1,138,2,2,4,3,138,'2025-01-30 17:31:58','2025-02-26 17:53:29');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (945,2,25,2,2,4,4,138,'2025-01-30 17:31:58',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (946,2,25,2,2,4,5,138,'2025-01-30 17:31:58',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (947,2,25,2,2,4,6,138,'2025-01-30 17:31:58',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1002,1,148,2,2,4,1,148,'2025-06-12 15:38:29',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1003,2,148,2,2,4,2,148,'2025-06-12 15:38:29',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1004,1,148,2,2,4,3,148,'2025-06-12 15:38:29','2025-06-25 09:17:59');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1005,2,35,2,2,4,4,148,'2025-06-12 15:38:29',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1006,1,35,2,2,4,5,148,'2025-06-12 15:38:29','2025-06-25 09:18:17');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1007,2,35,2,2,4,6,148,'2025-06-12 15:38:29',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1008,1,149,2,2,4,1,149,'2025-06-12 16:47:48',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1009,2,149,2,2,4,2,149,'2025-06-12 16:47:48',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1010,1,149,2,2,4,3,149,'2025-06-12 16:47:48','2025-06-25 09:18:01');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1011,2,36,2,2,4,4,149,'2025-06-12 16:47:48',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1012,1,36,2,2,4,5,149,'2025-06-12 16:47:48','2025-06-25 09:18:16');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1013,2,36,2,2,4,6,149,'2025-06-12 16:47:48',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1014,1,150,2,2,4,1,150,'2025-06-12 17:06:32',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1015,2,150,2,2,4,2,150,'2025-06-12 17:06:32',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1016,1,150,2,2,4,3,150,'2025-06-12 17:06:32','2025-06-25 09:18:02');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1017,2,37,2,2,4,4,150,'2025-06-12 17:06:32',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1018,1,37,2,2,4,5,150,'2025-06-12 17:06:32','2025-06-25 09:18:15');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1019,2,37,2,2,4,6,150,'2025-06-12 17:06:32',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1212,1,183,2,2,4,1,183,'2025-09-29 06:58:13',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1213,1,183,2,2,4,2,183,'2025-09-29 06:58:13','2025-12-11 17:18:11');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1214,1,183,2,2,4,3,183,'2025-09-29 06:58:13','2025-10-07 10:34:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1215,1,70,2,2,4,4,183,'2025-09-29 06:58:13','2025-10-07 10:33:33');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1216,2,70,2,2,4,5,183,'2025-09-29 06:58:13',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1217,2,70,2,2,4,6,183,'2025-09-29 06:58:13',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1290,1,0,2,2,4,7,1,'2025-10-22 11:31:09','2025-10-22 11:31:12');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1291,1,0,2,1,3,7,3,'2025-10-22 11:31:09','2025-10-22 11:58:17');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1292,1,0,2,2,4,7,28,'2025-10-22 11:31:09','2025-10-22 11:36:11');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1293,1,0,2,2,4,7,35,'2025-10-22 11:31:09','2025-10-22 11:36:29');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1294,2,0,2,2,4,7,68,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1295,1,0,2,1,1,7,2,'2025-10-22 11:31:09','2025-10-22 11:31:45');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1296,2,0,2,2,4,7,4,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1297,2,0,2,2,4,7,5,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1298,2,0,2,2,4,7,6,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1299,2,0,2,2,4,7,7,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1300,1,0,1,1,2,7,8,'2025-10-22 11:31:09','2025-10-23 14:58:32');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1301,1,0,2,2,4,7,9,'2025-10-22 11:31:09','2025-10-23 14:58:22');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1302,1,0,2,2,4,7,10,'2025-10-22 11:31:09','2025-10-23 14:58:22');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1303,1,0,2,2,4,7,11,'2025-10-22 11:31:09','2025-10-23 14:58:35');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1304,1,0,2,2,4,7,12,'2025-10-22 11:31:09','2025-10-23 14:58:36');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1305,2,0,2,2,4,7,13,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1306,2,0,2,2,4,7,14,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1307,2,0,2,2,4,7,15,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1308,2,0,2,2,4,7,16,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1309,2,0,2,2,4,7,17,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1310,2,0,2,2,4,7,18,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1311,2,0,2,2,4,7,19,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1312,2,0,2,2,4,7,20,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1313,2,0,2,2,4,7,21,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1314,2,0,2,2,4,7,22,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1315,2,0,2,2,4,7,23,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1316,2,0,2,2,4,7,24,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1317,2,0,2,2,4,7,25,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1318,2,0,2,2,4,7,26,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1319,2,0,2,2,4,7,27,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1320,2,0,2,2,4,7,29,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1321,2,0,2,2,4,7,30,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1322,2,0,2,2,4,7,31,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1323,2,0,2,2,4,7,32,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1324,2,0,2,2,4,7,33,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1325,2,0,2,2,4,7,34,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1326,1,0,2,2,4,7,36,'2025-10-22 11:31:09','2025-10-22 11:36:19');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1327,1,0,2,2,4,7,37,'2025-10-22 11:31:09','2025-10-22 11:36:20');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1328,1,0,2,2,4,7,38,'2025-10-22 11:31:09','2025-10-22 11:36:21');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1329,1,0,2,2,4,7,39,'2025-10-22 11:31:09','2025-10-22 11:36:22');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1330,1,0,2,2,4,7,40,'2025-10-22 11:31:09','2025-10-22 11:36:44');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1331,1,0,2,2,4,7,41,'2025-10-22 11:31:09','2025-10-23 14:59:30');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1332,1,0,2,2,4,7,42,'2025-10-22 11:31:09','2025-10-23 14:59:31');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1333,1,0,2,2,4,7,43,'2025-10-22 11:31:09','2025-10-23 14:59:32');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1334,1,0,2,2,4,7,44,'2025-10-22 11:31:09','2025-10-23 14:59:33');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1335,2,0,2,2,4,7,45,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1336,1,0,1,1,5,7,46,'2025-10-22 11:31:09','2025-10-23 14:47:36');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1337,1,0,2,2,4,7,47,'2025-10-22 11:31:09','2025-10-23 15:00:03');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1338,1,0,2,2,4,7,48,'2025-10-22 11:31:09','2025-10-23 15:00:07');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1339,1,0,2,2,4,7,49,'2025-10-22 11:31:09','2025-10-23 14:59:57');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1340,1,0,2,2,4,7,50,'2025-10-22 11:31:09','2025-10-23 15:00:13');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1341,2,0,2,2,4,7,51,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1342,2,0,2,2,4,7,52,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1343,2,0,2,2,4,7,53,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1344,2,0,2,2,4,7,54,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1345,2,0,2,2,4,7,55,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1346,1,0,2,2,4,7,56,'2025-10-22 11:31:09','2026-01-09 09:49:01');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1347,2,0,2,2,4,7,57,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1348,2,0,2,2,4,7,58,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1349,2,0,2,2,4,7,59,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1350,1,0,2,2,4,7,60,'2025-10-22 11:31:09','2025-10-23 15:00:22');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1351,1,0,2,2,4,7,61,'2025-10-22 11:31:09','2025-10-23 15:00:27');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1352,1,0,2,2,4,7,62,'2025-10-22 11:31:09','2025-10-23 15:00:28');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1353,2,0,2,2,4,7,63,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1354,2,0,2,2,4,7,64,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1355,2,0,2,2,4,7,65,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1356,2,0,2,2,4,7,66,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1357,2,0,2,2,4,7,67,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1358,2,0,2,2,4,7,69,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1359,2,0,2,2,4,7,70,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1360,2,0,2,2,4,7,71,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1361,2,0,2,2,4,7,72,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1362,2,0,2,2,4,7,73,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1363,2,0,2,2,4,7,74,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1364,2,0,2,2,4,7,75,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1365,2,0,2,2,4,7,76,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1366,2,0,2,2,4,7,77,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1367,2,0,2,2,4,7,78,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1368,2,0,2,2,4,7,79,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1369,2,0,2,2,4,7,80,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1370,2,0,2,2,4,7,81,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1371,2,0,2,2,4,7,82,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1372,2,0,2,2,4,7,83,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1373,2,0,2,2,4,7,84,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1380,2,0,2,2,4,7,91,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1381,2,0,2,2,4,7,92,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1382,2,0,2,2,4,7,93,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1383,2,0,2,2,4,7,94,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1384,2,0,2,2,4,7,95,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1385,2,0,2,2,4,7,96,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1393,2,0,2,2,4,7,104,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1394,2,0,2,2,4,7,105,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1396,2,0,2,2,4,7,107,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1398,2,0,2,2,4,7,109,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1399,2,0,2,2,4,7,110,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1400,2,0,2,2,4,7,111,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1401,2,0,2,2,4,7,112,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1402,2,0,2,2,4,7,113,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1403,2,0,2,2,4,7,114,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1404,2,0,2,2,4,7,115,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1405,2,0,2,2,4,7,116,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1406,2,0,2,2,4,7,117,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1407,2,0,2,2,4,7,118,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1408,2,0,2,2,4,7,119,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1409,2,0,2,2,4,7,120,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1410,2,0,2,2,4,7,121,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1411,2,0,2,2,4,7,122,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1412,2,0,2,2,4,7,123,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1413,2,0,2,2,4,7,124,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1424,2,0,2,2,4,7,135,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1426,2,0,2,2,4,7,137,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1427,2,0,2,2,4,7,138,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1437,2,0,2,2,4,7,148,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1438,2,0,2,2,4,7,149,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1439,2,0,2,2,4,7,150,'2025-10-22 11:31:09','2025-10-22 11:31:09');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1472,1,0,2,2,4,7,183,'2025-10-22 11:31:09','2025-12-11 17:18:17');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1485,1,0,2,2,1,8,1,'2025-10-23 14:41:49','2025-10-23 14:44:19');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1486,1,0,2,1,3,8,3,'2025-10-23 14:41:49','2025-10-23 14:42:10');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1487,1,0,2,2,4,8,28,'2025-10-23 14:41:49','2025-10-23 14:42:22');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1488,2,0,2,2,4,8,35,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1489,2,0,2,2,4,8,68,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1490,1,0,2,1,1,8,2,'2025-10-23 14:41:49','2025-10-23 14:44:26');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1491,2,0,2,2,4,8,4,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1492,2,0,2,2,4,8,5,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1493,2,0,2,2,4,8,6,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1494,2,0,2,2,4,8,7,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1495,2,0,2,2,4,8,8,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1496,2,0,2,2,4,8,9,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1497,2,0,2,2,4,8,10,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1498,2,0,2,2,4,8,11,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1499,2,0,2,2,4,8,12,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1500,2,0,2,2,4,8,13,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1501,2,0,2,2,4,8,14,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1502,2,0,2,2,4,8,15,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1503,2,0,2,2,4,8,16,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1504,2,0,2,2,4,8,17,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1505,2,0,2,2,4,8,18,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1506,2,0,2,2,4,8,19,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1507,2,0,2,2,4,8,20,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1508,2,0,2,2,4,8,21,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1509,2,0,2,2,4,8,22,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1510,2,0,2,2,4,8,23,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1511,2,0,2,2,4,8,24,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1512,2,0,2,2,4,8,25,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1513,2,0,2,2,4,8,26,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1514,2,0,2,2,4,8,27,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1515,2,0,2,2,4,8,29,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1516,2,0,2,2,4,8,30,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1517,2,0,2,2,4,8,31,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1518,2,0,2,2,4,8,32,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1519,2,0,2,2,4,8,33,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1520,2,0,2,2,4,8,34,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1521,1,0,2,2,4,8,36,'2025-10-23 14:41:49','2025-10-23 14:42:26');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1522,1,0,2,2,4,8,37,'2025-10-23 14:41:49','2025-10-23 14:42:27');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1523,1,0,2,2,4,8,38,'2025-10-23 14:41:49','2025-10-23 14:42:28');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1524,1,0,2,2,4,8,39,'2025-10-23 14:41:49','2025-10-23 14:42:29');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1525,2,0,2,2,4,8,40,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1526,2,0,2,2,4,8,41,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1527,2,0,2,2,4,8,42,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1528,2,0,2,2,4,8,43,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1529,2,0,2,2,4,8,44,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1530,2,0,2,2,4,8,45,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1531,1,0,1,1,5,8,46,'2025-10-23 14:41:49','2025-10-23 14:47:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1532,1,0,2,2,4,8,47,'2025-10-23 14:41:49','2025-10-23 14:45:18');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1533,1,0,2,2,4,8,48,'2025-10-23 14:41:49','2026-01-08 15:43:32');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1534,1,0,2,2,4,8,49,'2025-10-23 14:41:49','2025-10-23 14:46:28');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1535,2,0,2,2,4,8,50,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1536,2,0,2,2,4,8,51,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1537,2,0,2,2,4,8,52,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1538,2,0,2,2,4,8,53,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1539,2,0,2,2,4,8,54,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1540,2,0,2,2,4,8,55,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1541,1,0,2,2,4,8,56,'2025-10-23 14:41:49','2026-01-09 09:49:00');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1542,2,0,2,2,4,8,57,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1543,2,0,2,2,4,8,58,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1544,2,0,2,2,4,8,59,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1545,2,0,2,2,4,8,60,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1546,2,0,2,2,4,8,61,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1547,2,0,2,2,4,8,62,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1548,2,0,2,2,4,8,63,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1549,2,0,2,2,4,8,64,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1550,2,0,2,2,4,8,65,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1551,2,0,2,2,4,8,66,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1552,2,0,2,2,4,8,67,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1553,2,0,2,2,4,8,69,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1554,2,0,2,2,4,8,70,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1555,2,0,2,2,4,8,71,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1556,2,0,2,2,4,8,72,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1557,2,0,2,2,4,8,73,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1558,2,0,2,2,4,8,74,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1559,2,0,2,2,4,8,75,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1560,2,0,2,2,4,8,76,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1561,2,0,2,2,4,8,77,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1562,2,0,2,2,4,8,78,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1563,2,0,2,2,4,8,79,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1564,2,0,2,2,4,8,80,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1565,2,0,2,2,4,8,81,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1566,2,0,2,2,4,8,82,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1567,2,0,2,2,4,8,83,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1568,2,0,2,2,4,8,84,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1575,2,0,2,2,4,8,91,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1576,2,0,2,2,4,8,92,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1577,2,0,2,2,4,8,93,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1578,2,0,2,2,4,8,94,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1579,2,0,2,2,4,8,95,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1580,2,0,2,2,4,8,96,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1588,2,0,2,2,4,8,104,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1589,2,0,2,2,4,8,105,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1591,2,0,2,2,4,8,107,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1593,2,0,2,2,4,8,109,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1594,2,0,2,2,4,8,110,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1595,2,0,2,2,4,8,111,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1596,2,0,2,2,4,8,112,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1597,2,0,2,2,4,8,113,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1598,2,0,2,2,4,8,114,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1599,2,0,2,2,4,8,115,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1600,2,0,2,2,4,8,116,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1601,2,0,2,2,4,8,117,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1602,2,0,2,2,4,8,118,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1603,2,0,2,2,4,8,119,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1604,2,0,2,2,4,8,120,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1605,2,0,2,2,4,8,121,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1606,2,0,2,2,4,8,122,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1607,2,0,2,2,4,8,123,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1608,2,0,2,2,4,8,124,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1619,2,0,2,2,4,8,135,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1621,2,0,2,2,4,8,137,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1622,2,0,2,2,4,8,138,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1632,2,0,2,2,4,8,148,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1633,2,0,2,2,4,8,149,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1634,2,0,2,2,4,8,150,'2025-10-23 14:41:49','2025-10-23 14:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1667,1,0,2,2,4,8,183,'2025-10-23 14:41:49','2025-12-11 17:18:18');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1872,1,220,2,2,4,1,220,'2025-12-11 17:17:47',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1873,1,220,2,2,4,2,220,'2025-12-11 17:17:47','2025-12-11 17:17:52');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1874,1,220,2,2,4,3,220,'2025-12-11 17:17:47','2025-12-11 17:17:55');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1875,1,107,2,2,4,4,220,'2025-12-11 17:17:47','2025-12-11 17:17:56');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1876,1,107,2,2,4,5,220,'2025-12-11 17:17:47','2025-12-11 17:17:54');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1877,1,107,2,2,4,6,220,'2025-12-11 17:17:47','2025-12-11 17:17:56');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1878,1,25,2,2,4,7,220,'2025-12-11 17:17:47','2025-12-11 17:17:57');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (1879,1,25,2,2,4,8,220,'2025-12-11 17:17:47','2025-12-11 17:17:58');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2032,2,240,2,2,4,1,240,'2026-01-15 07:55:23',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2033,2,240,2,2,4,2,240,'2026-01-15 07:55:23',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2034,2,240,2,2,4,3,240,'2026-01-15 07:55:23','2026-03-13 11:19:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2035,2,127,2,2,4,4,240,'2026-01-15 07:55:24',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2036,2,127,2,2,4,5,240,'2026-01-15 07:55:24',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2037,2,127,2,2,4,6,240,'2026-01-15 07:55:24',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2038,2,45,2,2,4,7,240,'2026-01-15 07:55:25',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2039,2,45,2,2,4,8,240,'2026-01-15 07:55:25',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2040,1,241,2,2,4,1,241,'2026-01-15 10:14:37',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2041,2,241,2,2,4,2,241,'2026-01-15 10:14:38',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2042,1,241,2,2,4,3,241,'2026-01-15 10:14:38','2026-01-27 06:56:38');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2043,2,128,2,2,4,4,241,'2026-01-15 10:14:38',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2044,2,128,2,2,4,5,241,'2026-01-15 10:14:39',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2045,2,128,2,2,4,6,241,'2026-01-15 10:14:39',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2046,2,46,2,2,4,7,241,'2026-01-15 10:14:39',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2047,2,46,2,2,4,8,241,'2026-01-15 10:14:39',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2064,2,244,2,2,4,1,244,'2026-01-16 16:16:49','2026-09-07 23:32:48');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2065,2,244,2,2,4,2,244,'2026-01-16 16:16:49',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2066,2,244,2,2,4,3,244,'2026-01-16 16:16:49','2026-09-07 23:32:48');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2067,2,131,2,2,4,4,244,'2026-01-16 16:16:49',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2068,2,131,2,2,4,5,244,'2026-01-16 16:16:50',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2069,2,131,2,2,4,6,244,'2026-01-16 16:16:50',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2070,2,49,2,2,4,7,244,'2026-01-16 16:16:51',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2071,2,49,2,2,4,8,244,'2026-01-16 16:16:51',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2104,1,249,2,2,4,1,249,'2026-01-20 11:25:16',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2105,2,249,2,2,4,2,249,'2026-01-20 11:25:16',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2106,1,249,2,2,4,3,249,'2026-01-20 11:25:17','2026-01-26 16:54:14');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2107,2,136,2,2,4,4,249,'2026-01-20 11:25:17',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2108,2,136,2,2,4,5,249,'2026-01-20 11:25:17',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2109,2,136,2,2,4,6,249,'2026-01-20 11:25:18',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2110,2,54,2,2,4,7,249,'2026-01-20 11:25:18',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2111,2,54,2,2,4,8,249,'2026-01-20 11:25:18',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2112,1,250,2,2,4,1,250,'2026-01-20 12:37:28',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2113,2,250,2,2,4,2,250,'2026-01-20 12:37:28',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2114,1,250,2,2,4,3,250,'2026-01-20 12:37:29','2026-01-26 16:54:12');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2115,2,137,2,2,4,4,250,'2026-01-20 12:37:29',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2116,2,137,2,2,4,5,250,'2026-01-20 12:37:29',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2117,2,137,2,2,4,6,250,'2026-01-20 12:37:30',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2118,2,55,2,2,4,7,250,'2026-01-20 12:37:30',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2119,2,55,2,2,4,8,250,'2026-01-20 12:37:30',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2152,1,255,2,2,4,1,255,'2026-02-03 13:06:14',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2153,1,255,2,2,4,2,255,'2026-02-03 13:06:14','2026-02-03 16:38:23');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2154,1,255,2,2,4,3,255,'2026-02-03 13:06:15','2026-02-03 16:38:28');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2155,2,142,2,2,4,4,255,'2026-02-03 13:06:15',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2156,1,142,2,2,4,5,255,'2026-02-03 13:06:15','2026-02-03 16:38:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2157,1,142,2,2,4,6,255,'2026-02-03 13:06:16','2026-02-03 16:38:26');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2158,2,60,2,2,4,7,255,'2026-02-03 13:06:16',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2159,2,60,2,2,4,8,255,'2026-02-03 13:06:16',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2216,2,263,2,2,4,1,263,'2026-03-10 13:46:45','2026-09-07 23:32:48');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2217,2,263,2,2,4,2,263,'2026-03-10 13:46:46',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2218,2,263,2,2,4,3,263,'2026-03-10 13:46:46','2026-09-07 23:32:48');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2219,2,150,2,2,4,4,263,'2026-03-10 13:46:46',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2220,2,150,2,2,4,5,263,'2026-03-10 13:46:47','2026-09-07 23:32:48');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2221,2,150,2,2,4,6,263,'2026-03-10 13:46:47',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2222,2,68,2,2,4,7,263,'2026-03-10 13:46:47','2026-09-07 23:32:48');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2223,2,68,2,2,4,8,263,'2026-03-10 13:46:48',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2224,1,264,2,2,4,1,264,'2026-03-11 10:27:45',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2225,1,264,2,2,4,2,264,'2026-03-11 10:27:45','2026-08-28 17:23:23');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2226,1,264,2,2,4,3,264,'2026-03-11 10:27:45','2026-08-28 17:23:25');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2227,1,151,2,2,4,4,264,'2026-03-11 10:27:46','2026-08-28 17:23:26');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2228,1,151,2,2,4,5,264,'2026-03-11 10:27:46','2026-08-28 17:23:24');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2229,1,151,2,2,4,6,264,'2026-03-11 10:27:46','2026-08-28 17:23:26');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2230,1,69,2,2,4,7,264,'2026-03-11 10:27:47','2026-08-28 17:23:27');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2231,2,69,2,2,4,8,264,'2026-03-11 10:27:47',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2254,1,267,2,2,4,1,273,'2026-04-18 13:31:22',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2255,1,273,2,2,4,2,273,'2026-04-18 13:31:22','2026-06-07 16:58:44');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2256,1,267,2,2,4,3,273,'2026-04-18 13:31:23','2026-04-18 13:39:31');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2257,1,154,2,2,4,4,273,'2026-04-18 13:31:23','2026-04-18 13:40:11');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2258,1,154,2,2,4,5,273,'2026-04-18 13:31:23','2026-04-18 13:40:30');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2259,1,154,2,2,4,6,273,'2026-04-18 13:31:24','2026-04-18 13:40:54');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2260,1,72,2,2,4,7,273,'2026-04-18 13:31:24','2026-04-18 13:41:49');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2261,1,72,2,2,4,8,273,'2026-04-18 13:31:24','2026-04-18 13:42:10');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2485,1,279,2,2,4,1,295,'2026-08-28 16:33:38',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2486,1,285,2,2,4,2,295,'2026-08-28 16:33:38','2026-08-28 17:23:56');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2487,1,279,2,2,4,3,295,'2026-08-28 16:33:38','2026-08-28 17:23:58');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2488,1,166,2,2,4,4,295,'2026-08-28 16:33:38','2026-08-28 17:23:59');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2489,1,166,2,2,4,5,295,'2026-08-28 16:33:38','2026-08-28 17:23:57');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2490,1,166,2,2,4,6,295,'2026-08-28 16:33:38','2026-08-28 17:23:58');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2491,1,84,2,2,4,7,295,'2026-08-28 16:33:38','2026-08-28 17:24:00');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2492,2,84,2,2,4,8,295,'2026-08-28 16:33:38',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2586,1,0,2,2,4,1,305,'2026-09-07 23:17:11',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2587,2,0,2,2,4,2,305,'2026-09-07 23:17:11',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2588,1,312,1,1,4,3,305,'2026-09-07 23:17:11','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2589,1,312,1,1,4,4,305,'2026-09-07 23:17:11','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2590,1,0,2,2,4,5,305,'2026-09-07 23:17:11','2026-09-07 23:23:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2591,2,0,2,2,4,6,305,'2026-09-07 23:17:11',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2592,1,312,1,1,4,7,305,'2026-09-07 23:17:11','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2593,1,312,1,1,4,8,305,'2026-09-07 23:17:11','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2594,1,0,2,2,4,1,306,'2026-09-07 23:17:11',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2595,2,0,2,2,4,2,306,'2026-09-07 23:17:11',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2596,1,312,1,2,4,3,306,'2026-09-07 23:17:11','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2597,1,312,1,2,4,4,306,'2026-09-07 23:17:11','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2598,1,0,2,2,4,5,306,'2026-09-07 23:17:11','2026-09-07 23:23:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2599,2,0,2,2,4,6,306,'2026-09-07 23:17:11',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2600,1,312,1,2,4,7,306,'2026-09-07 23:17:11','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2601,1,312,1,2,4,8,306,'2026-09-07 23:17:11','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2602,1,0,2,2,4,1,307,'2026-09-07 23:17:11',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2603,2,0,2,2,4,2,307,'2026-09-07 23:17:11',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2604,1,312,1,2,4,3,307,'2026-09-07 23:17:11','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2605,1,312,1,2,4,4,307,'2026-09-07 23:17:11','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2606,1,0,2,2,4,5,307,'2026-09-07 23:17:11','2026-09-07 23:23:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2607,2,0,2,2,4,6,307,'2026-09-07 23:17:11',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2608,1,312,1,2,4,7,307,'2026-09-07 23:17:11','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2609,1,312,1,2,4,8,307,'2026-09-07 23:17:11','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2610,1,0,2,2,4,1,308,'2026-09-07 23:17:11',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2611,2,0,2,2,4,2,308,'2026-09-07 23:17:11',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2612,1,312,1,2,4,3,308,'2026-09-07 23:17:11','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2613,1,312,1,2,4,4,308,'2026-09-07 23:17:11','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2614,1,0,2,2,4,5,308,'2026-09-07 23:17:11','2026-09-07 23:23:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2615,2,0,2,2,4,6,308,'2026-09-07 23:17:11',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2616,1,312,1,2,4,7,308,'2026-09-07 23:17:11','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2617,1,312,1,2,4,8,308,'2026-09-07 23:17:11','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2837,1,125,1,1,7,1,332,'2026-09-07 23:18:06',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2838,2,125,2,2,4,2,332,'2026-09-07 23:18:06',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2839,1,310,1,1,7,3,332,'2026-09-07 23:18:06','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2840,1,310,1,1,7,4,332,'2026-09-07 23:18:06','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2841,1,12,2,2,4,5,332,'2026-09-07 23:18:06','2026-09-07 23:23:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2842,2,12,2,2,4,6,332,'2026-09-07 23:18:06',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2843,1,310,1,1,7,7,332,'2026-09-07 23:18:06','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2844,1,310,1,1,7,8,332,'2026-09-07 23:18:06','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2845,1,125,1,2,7,1,333,'2026-09-07 23:18:06',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2846,2,125,2,2,4,2,333,'2026-09-07 23:18:06',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2847,1,310,1,2,7,3,333,'2026-09-07 23:18:06','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2848,1,310,1,2,7,4,333,'2026-09-07 23:18:06','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2849,1,12,2,2,4,5,333,'2026-09-07 23:18:06','2026-09-07 23:23:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2850,2,12,2,2,4,6,333,'2026-09-07 23:18:06',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2851,1,310,1,2,7,7,333,'2026-09-07 23:18:06','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2852,1,310,1,2,7,8,333,'2026-09-07 23:18:06','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2853,1,125,1,2,7,1,334,'2026-09-07 23:18:06',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2854,2,125,2,2,4,2,334,'2026-09-07 23:18:06',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2855,1,310,1,2,7,3,334,'2026-09-07 23:18:06','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2856,1,310,1,2,7,4,334,'2026-09-07 23:18:06','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2857,1,12,2,2,4,5,334,'2026-09-07 23:18:06','2026-09-07 23:23:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2858,2,12,2,2,4,6,334,'2026-09-07 23:18:06',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2859,1,310,1,2,7,7,334,'2026-09-07 23:18:06','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2860,1,310,1,2,7,8,334,'2026-09-07 23:18:06','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2861,1,125,1,2,7,1,335,'2026-09-07 23:18:06',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2862,2,125,2,2,4,2,335,'2026-09-07 23:18:06',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2863,1,310,1,2,7,3,335,'2026-09-07 23:18:06','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2864,1,310,1,2,7,4,335,'2026-09-07 23:18:06','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2865,1,12,2,2,4,5,335,'2026-09-07 23:18:06','2026-09-07 23:23:47');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2866,2,12,2,2,4,6,335,'2026-09-07 23:18:06',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2867,1,310,1,2,7,7,335,'2026-09-07 23:18:06','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (2868,1,310,1,2,7,8,335,'2026-09-07 23:18:06','2026-09-07 23:21:05');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (3023,2,0,2,2,1,1,364,'2026-09-07 23:29:21','2026-09-07 23:33:06');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (3024,2,0,2,2,1,1,365,'2026-09-07 23:29:21','2026-09-07 23:33:06');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (3025,2,0,2,2,1,7,364,'2026-09-07 23:29:21','2026-09-07 23:33:06');
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (3026,2,0,2,2,1,7,365,'2026-09-07 23:29:21','2026-09-07 23:33:06');

-- adms_niveis_acessos
DELETE FROM `adms_niveis_acessos`;
INSERT INTO `adms_niveis_acessos` (`id`, `nome`, `ordem`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (1,'Super Administrador',1,1,1,NULL,'2022-03-10 08:26:01','2024-10-23 08:02:13');
INSERT INTO `adms_niveis_acessos` (`id`, `nome`, `ordem`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (2,'Administrador',2,1,1,NULL,'2022-03-10 08:26:01','2024-10-23 08:02:13');
INSERT INTO `adms_niveis_acessos` (`id`, `nome`, `ordem`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (3,'Diretoria',3,1,1,NULL,'2024-10-23 07:41:38','2024-10-23 08:02:13');
INSERT INTO `adms_niveis_acessos` (`id`, `nome`, `ordem`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (4,'Secretaria',4,1,1,NULL,'2025-01-17 16:45:09',NULL);
INSERT INTO `adms_niveis_acessos` (`id`, `nome`, `ordem`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (5,'Coordenação',5,1,1,NULL,'2025-01-17 16:45:19',NULL);
INSERT INTO `adms_niveis_acessos` (`id`, `nome`, `ordem`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (6,'Professor',6,1,1,NULL,'2025-01-17 16:45:24',NULL);
INSERT INTO `adms_niveis_acessos` (`id`, `nome`, `ordem`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (7,'Supervisor VCOSTA',7,1,1,1,'2025-10-22 11:30:44','2025-10-23 14:41:26');
INSERT INTO `adms_niveis_acessos` (`id`, `nome`, `ordem`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (8,'VCOSTA',8,1,1,NULL,'2025-10-23 14:41:34',NULL);

-- adms_grps_pgs
DELETE FROM `adms_grps_pgs`;
INSERT INTO `adms_grps_pgs` (`id`, `nome`, `ordem`, `created`, `modified`) VALUES (1,'Listar',1,'2022-03-10 08:30:52',NULL);
INSERT INTO `adms_grps_pgs` (`id`, `nome`, `ordem`, `created`, `modified`) VALUES (2,'Cadastrar',2,'2022-03-10 08:30:52',NULL);
INSERT INTO `adms_grps_pgs` (`id`, `nome`, `ordem`, `created`, `modified`) VALUES (3,'Editar',3,'2022-03-10 08:30:52',NULL);
INSERT INTO `adms_grps_pgs` (`id`, `nome`, `ordem`, `created`, `modified`) VALUES (4,'Apagar',4,'2022-03-10 08:30:52',NULL);
INSERT INTO `adms_grps_pgs` (`id`, `nome`, `ordem`, `created`, `modified`) VALUES (5,'Visualizar',5,'2022-03-10 08:30:52',NULL);
INSERT INTO `adms_grps_pgs` (`id`, `nome`, `ordem`, `created`, `modified`) VALUES (6,'Outros',6,'2022-03-10 08:30:52',NULL);
INSERT INTO `adms_grps_pgs` (`id`, `nome`, `ordem`, `created`, `modified`) VALUES (7,'Acesso',7,'2022-03-10 08:30:52',NULL);
INSERT INTO `adms_grps_pgs` (`id`, `nome`, `ordem`, `created`, `modified`) VALUES (8,'Alterar Ordem',8,'2022-03-10 08:30:52',NULL);

-- adms_tps_pgs
DELETE FROM `adms_tps_pgs`;
INSERT INTO `adms_tps_pgs` (`id`, `tipo`, `nome`, `obs`, `ordem`, `created`, `modified`) VALUES (1,'adms','Administrativo','Core do Administrativo',1,'2022-03-09 17:30:31',NULL);

-- adms_sits_pgs
DELETE FROM `adms_sits_pgs`;
INSERT INTO `adms_sits_pgs` (`id`, `nome`, `cor_id`, `created`, `modified`) VALUES (1,'Ativo',3,'2022-03-10 08:24:05',NULL);
INSERT INTO `adms_sits_pgs` (`id`, `nome`, `cor_id`, `created`, `modified`) VALUES (2,'Inativo',5,'2022-03-10 08:24:05',NULL);
INSERT INTO `adms_sits_pgs` (`id`, `nome`, `cor_id`, `created`, `modified`) VALUES (3,'Análise',1,'2022-03-10 08:24:17',NULL);

-- adms_sits_usuarios
DELETE FROM `adms_sits_usuarios`;
INSERT INTO `adms_sits_usuarios` (`id`, `nome`, `adms_cor_id`, `created`, `modified`) VALUES (1,'Ativo',3,'2022-03-10 08:22:20',NULL);
INSERT INTO `adms_sits_usuarios` (`id`, `nome`, `adms_cor_id`, `created`, `modified`) VALUES (2,'Atualizar Dados',5,'2022-03-10 08:22:20','2024-09-26 17:12:01');
INSERT INTO `adms_sits_usuarios` (`id`, `nome`, `adms_cor_id`, `created`, `modified`) VALUES (3,'Inativo',4,'2024-09-26 16:57:58','2024-09-26 17:12:48');
INSERT INTO `adms_sits_usuarios` (`id`, `nome`, `adms_cor_id`, `created`, `modified`) VALUES (4,'Bloqueado',4,'2024-09-26 17:12:26','2024-09-26 17:12:53');
INSERT INTO `adms_sits_usuarios` (`id`, `nome`, `adms_cor_id`, `created`, `modified`) VALUES (5,'Apagado',8,'2024-09-26 17:12:26','2024-09-26 17:13:08');

-- adms_cors
DELETE FROM `adms_cors`;
INSERT INTO `adms_cors` (`id`, `nome`, `cor`, `created`, `modified`) VALUES (1,'Azul','primary','2018-05-23 00:00:00',NULL);
INSERT INTO `adms_cors` (`id`, `nome`, `cor`, `created`, `modified`) VALUES (2,'Cinza','secondary','2018-05-23 00:00:00',NULL);
INSERT INTO `adms_cors` (`id`, `nome`, `cor`, `created`, `modified`) VALUES (3,'Verde','success','2018-05-23 00:00:00',NULL);
INSERT INTO `adms_cors` (`id`, `nome`, `cor`, `created`, `modified`) VALUES (4,'Vermelho','danger','2018-05-23 00:00:00',NULL);
INSERT INTO `adms_cors` (`id`, `nome`, `cor`, `created`, `modified`) VALUES (5,'Laranjado','warning','2018-05-23 00:00:00',NULL);
INSERT INTO `adms_cors` (`id`, `nome`, `cor`, `created`, `modified`) VALUES (6,'Azul claro','info','2018-05-23 00:00:00',NULL);
INSERT INTO `adms_cors` (`id`, `nome`, `cor`, `created`, `modified`) VALUES (7,'Claro','light','2018-05-23 00:00:00',NULL);
INSERT INTO `adms_cors` (`id`, `nome`, `cor`, `created`, `modified`) VALUES (8,'Cinza escuro','dark','2018-05-23 00:00:00',NULL);

-- adms_generos
DELETE FROM `adms_generos`;
INSERT INTO `adms_generos` (`id`, `nome`, `situacao`, `created`, `modified`) VALUES (1,'Feminino',1,'2022-03-22 16:18:49','2025-02-07 08:12:06');
INSERT INTO `adms_generos` (`id`, `nome`, `situacao`, `created`, `modified`) VALUES (2,'Masculino',1,'2022-03-22 16:18:49','2025-02-07 08:12:12');
INSERT INTO `adms_generos` (`id`, `nome`, `situacao`, `created`, `modified`) VALUES (3,'Outros',1,'2022-03-22 16:19:05',NULL);

-- adms_estado_civil
DELETE FROM `adms_estado_civil`;
INSERT INTO `adms_estado_civil` (`id`, `nome`, `situacao`, `created`, `modified`) VALUES (1,'Casado(a)',1,'2024-10-23 16:00:52',NULL);
INSERT INTO `adms_estado_civil` (`id`, `nome`, `situacao`, `created`, `modified`) VALUES (2,'Concubinato',1,'2024-10-23 16:00:52',NULL);
INSERT INTO `adms_estado_civil` (`id`, `nome`, `situacao`, `created`, `modified`) VALUES (3,'Divorciado(a)',1,'2024-10-23 16:00:52',NULL);
INSERT INTO `adms_estado_civil` (`id`, `nome`, `situacao`, `created`, `modified`) VALUES (4,'Separado Judicialmente',1,'2024-10-23 16:00:52',NULL);
INSERT INTO `adms_estado_civil` (`id`, `nome`, `situacao`, `created`, `modified`) VALUES (5,'Solteiro(a)',1,'2024-10-23 16:00:52',NULL);
INSERT INTO `adms_estado_civil` (`id`, `nome`, `situacao`, `created`, `modified`) VALUES (6,'União Estável',1,'2024-10-23 16:00:52',NULL);
INSERT INTO `adms_estado_civil` (`id`, `nome`, `situacao`, `created`, `modified`) VALUES (7,'Viúvo(a)',1,'2024-10-23 16:00:52',NULL);

-- adms_raca
DELETE FROM `adms_raca`;
INSERT INTO `adms_raca` (`id`, `nome`, `observacao`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (1,'Amarela',NULL,1,1,NULL,'2025-09-03 16:13:16',NULL);
INSERT INTO `adms_raca` (`id`, `nome`, `observacao`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (2,'Branca',NULL,1,1,NULL,'2025-09-03 16:13:16',NULL);
INSERT INTO `adms_raca` (`id`, `nome`, `observacao`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (3,'Indígena',NULL,1,1,NULL,'2025-09-03 16:13:49',NULL);
INSERT INTO `adms_raca` (`id`, `nome`, `observacao`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (4,'Negra',NULL,1,1,NULL,'2025-09-03 16:13:49',NULL);
INSERT INTO `adms_raca` (`id`, `nome`, `observacao`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (5,'Parda',NULL,1,1,NULL,'2025-09-03 16:14:14',NULL);
INSERT INTO `adms_raca` (`id`, `nome`, `observacao`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (6,'Não declarada',NULL,1,1,NULL,'2025-09-03 16:14:14',NULL);

-- adms_uf
DELETE FROM `adms_uf`;
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (1,'AC','Acre');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (2,'AL','Alagoas');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (3,'AM','Amazonas');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (4,'AP','Amapá');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (5,'BA','Bahia');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (6,'CE','Ceará');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (7,'DF','Distrito Federal');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (8,'ES','Espírito Santo');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (9,'GO','Góias');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (10,'MA','Maranhão');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (11,'MG','Minas Gerais');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (12,'MS','Mato Grosso do Sul');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (13,'MT','Mato Grosso');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (14,'PA','Pará');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (15,'PB','Paraíba');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (16,'PE','Pernambuco');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (17,'PI','Piauí');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (18,'PR','Paraná');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (19,'RJ','Rio de Janeiro');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (20,'RN','Rio Grande do Norte');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (21,'RS','Rio Grande do Sul');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (22,'RO','Rondônia');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (23,'RR','Roraima');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (24,'SC','Santa Catarina');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (25,'SE','Sergipe');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (26,'SP','São Paulo');
INSERT INTO `adms_uf` (`id`, `nome`, `nome_completo`) VALUES (27,'TO','Tocantins');

-- adms_dia_semana
DELETE FROM `adms_dia_semana`;
INSERT INTO `adms_dia_semana` (`id`, `dia`, `dia_completo`, `abreviacao`, `status`, `usuario_id`, `created`, `modified`) VALUES (1,'Domingo','Domingo','dom',1,1,'2021-08-04 15:57:53','2021-09-13 14:59:44');
INSERT INTO `adms_dia_semana` (`id`, `dia`, `dia_completo`, `abreviacao`, `status`, `usuario_id`, `created`, `modified`) VALUES (2,'Segunda','Segunda-feira','seg',1,1,'2021-08-04 15:57:53','2021-09-13 14:59:51');
INSERT INTO `adms_dia_semana` (`id`, `dia`, `dia_completo`, `abreviacao`, `status`, `usuario_id`, `created`, `modified`) VALUES (3,'Terça','Terça-feira','ter',1,1,'2021-08-04 15:58:13','2021-09-13 14:59:55');
INSERT INTO `adms_dia_semana` (`id`, `dia`, `dia_completo`, `abreviacao`, `status`, `usuario_id`, `created`, `modified`) VALUES (4,'Quarta','Quarta-feira','qua',1,1,'2021-08-04 15:58:13','2021-09-13 15:00:00');
INSERT INTO `adms_dia_semana` (`id`, `dia`, `dia_completo`, `abreviacao`, `status`, `usuario_id`, `created`, `modified`) VALUES (5,'Quinta','Quinta-feira','qui',1,1,'2021-08-04 15:58:36','2021-09-13 15:00:05');
INSERT INTO `adms_dia_semana` (`id`, `dia`, `dia_completo`, `abreviacao`, `status`, `usuario_id`, `created`, `modified`) VALUES (6,'Sexta','Sexta-feira','sex',1,1,'2021-08-04 15:58:36','2021-09-13 15:00:11');
INSERT INTO `adms_dia_semana` (`id`, `dia`, `dia_completo`, `abreviacao`, `status`, `usuario_id`, `created`, `modified`) VALUES (7,'Sábado','Sábado','sab',1,1,'2021-08-04 15:58:54','2021-09-13 15:00:14');

-- col_paginas
DELETE FROM `col_paginas`;
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (1,'Login','Login','acesso','login','acesso','Login Alunos','Página de login do sistema acadêmico dos alunos, pais e responsáveis.',1,NULL,7,1,1,'2024-12-18 10:55:53','2024-12-18 15:12:27');
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (2,'Página Inicial','Home','index','home','index','Página Inicial','Página inicial dos responsáveis, alunos do sistema.',2,'fas fa-tachometer-alt',5,1,1,'2026-01-26 08:50:41',NULL);
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (3,'Sair','Login','logout','login','logout','Sair','Página para deslogar o usuário acadêmico.',2,'fas fa-sign-out-alt',7,1,1,'2026-01-26 08:52:11',NULL);
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (4,'Esqueci a senha','Login','esqueceuSenha','login','esqueceu-senha','Esqueci a Senha','Página para recuperar senha esquecida.',1,'',7,1,1,'2026-02-02 17:36:14',NULL);
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (5,'Trocar a senha de acesso','Login','atualizarSenha','login','atualizar-senha','Trocar a Senha de acesso','Formulário para atualização da senha perdida.',1,'',7,1,1,'2026-02-02 18:17:46',NULL);
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (6,'ver Perfil','Perfil','visualizar','perfil','visualizar','Ver Perfil','Página para visualizar as informações do seu perfil de usuário.',2,'',5,1,1,'2026-02-03 17:00:08',NULL);
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (7,'Editar Perfil','Perfil','editar','perfil','editar','Editar Perfil','Formulário para o próprio usuário atualizar alguns dados.',2,'',3,1,1,'2026-02-04 10:11:37',NULL);
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (8,'Editar Senha','Perfil','editarSenha','perfil','editar-senha','Editar Senha','Formulário para editar senha de acesso.',2,'',3,1,1,'2026-02-04 10:16:13',NULL);
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (10,'Horário','Home','horarioAluno','home','horario-aluno','Horário','Exibe o horário do aluno',2,'far fa-clock',1,1,1,'2026-03-10 17:12:22','2026-03-12 09:45:52');
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (11,'Boletim','Home','boletimAluno','home','boletim-aluno','Boletim','Boletim do aluno por ano letivo.',2,'fas fa-book-reader',1,1,1,'2026-03-11 10:32:05','2026-03-12 09:46:41');
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES (13,'Notas das Atividades','Home','notasAtividades','home','notas-atividades','Notas das Atividades','Exibe as notas das atividades de um ou mais alunos durante um determinado ciclo letivo',2,'fas fa-clipboard-list',1,1,1,'2026-03-12 09:35:29','2026-03-12 10:47:21');

-- col_menus
DELETE FROM `col_menus`;
INSERT INTO `col_menus` (`id`, `nome`, `icone`, `ordem`, `col_sit_id`, `created`, `modified`) VALUES (1,'Página Inicial','fas fa-tachometer-alt',1,1,'2026-01-09 16:08:21','2026-01-09 16:14:19');
INSERT INTO `col_menus` (`id`, `nome`, `icone`, `ordem`, `col_sit_id`, `created`, `modified`) VALUES (2,'Acadêmico','fas fa-university',2,1,'2026-01-09 16:35:40','2026-01-26 18:14:26');
INSERT INTO `col_menus` (`id`, `nome`, `icone`, `ordem`, `col_sit_id`, `created`, `modified`) VALUES (3,'Financeiro','fas fa-file-invoice-dollar',3,1,'2026-01-09 16:35:40','2026-01-26 18:25:11');
INSERT INTO `col_menus` (`id`, `nome`, `icone`, `ordem`, `col_sit_id`, `created`, `modified`) VALUES (4,'Atendimento','fas fa-comments',4,1,'2026-01-09 16:35:40','2026-01-26 18:17:47');
INSERT INTO `col_menus` (`id`, `nome`, `icone`, `ordem`, `col_sit_id`, `created`, `modified`) VALUES (5,'Sair','fas fa-sign-out-alt',5,1,'2026-01-09 16:35:40','2026-01-26 18:17:50');

-- col_nivacs_pgs
DELETE FROM `col_nivacs_pgs`;
INSERT INTO `col_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (5,1,1,2,2,1,1,1,'2024-12-18 10:55:53','2026-01-26 08:52:45');
INSERT INTO `col_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (9,1,2,2,1,1,1,2,'2026-01-26 08:50:42','2026-01-27 15:03:42');
INSERT INTO `col_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (12,1,3,2,1,5,1,3,'2026-01-26 08:52:12','2026-01-27 15:02:56');
INSERT INTO `col_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (15,1,4,2,2,4,1,4,'2026-02-02 17:36:14',NULL);
INSERT INTO `col_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (18,1,5,2,2,4,1,5,'2026-02-02 18:17:46',NULL);
INSERT INTO `col_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (21,1,6,2,2,4,1,6,'2026-02-03 17:00:08',NULL);
INSERT INTO `col_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (24,1,7,2,2,4,1,7,'2026-02-04 10:11:37',NULL);
INSERT INTO `col_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (27,1,8,2,2,4,1,8,'2026-02-04 10:16:14',NULL);
INSERT INTO `col_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (33,1,10,1,1,2,1,10,'2026-03-10 17:12:23','2026-03-12 10:48:50');
INSERT INTO `col_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (36,1,11,1,1,2,1,11,'2026-03-11 10:32:06','2026-03-12 10:48:11');
INSERT INTO `col_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES (42,1,13,1,1,2,1,13,'2026-03-12 09:35:30','2026-03-12 10:45:54');

-- col_niveis_acessos
DELETE FROM `col_niveis_acessos`;
INSERT INTO `col_niveis_acessos` (`id`, `nome`, `ordem`, `view`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (1,'Estudante',1,'alunos',1,1,NULL,'2022-06-07 16:17:21','2026-01-27 10:11:05');

-- col_situacao
DELETE FROM `col_situacao`;
INSERT INTO `col_situacao` (`id`, `nome`, `observacao`, `cores`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (1,'Ativo','Ativo no sistema','success',1,1,NULL,'2018-12-02 20:00:00','2025-09-03 09:46:43');
INSERT INTO `col_situacao` (`id`, `nome`, `observacao`, `cores`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (2,'Inativo','Inativo no sistema','danger',1,1,NULL,'2018-12-02 20:00:00','2025-09-03 09:47:09');
INSERT INTO `col_situacao` (`id`, `nome`, `observacao`, `cores`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (3,'Aberto','Mensagens, ocorrencias, etc. abertas no sistema.','info',1,1,NULL,'2018-12-02 20:00:00','2025-09-03 09:48:38');
INSERT INTO `col_situacao` (`id`, `nome`, `observacao`, `cores`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (4,'Pendente','Mensagens, ocorrencias, etc. pendentes no sistema.','info',1,1,NULL,'2018-12-02 20:00:00','2025-09-03 09:47:27');
INSERT INTO `col_situacao` (`id`, `nome`, `observacao`, `cores`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (5,'Fechadas','Mensagens, ocorrencias, etc. fechadas no sistema.','info',1,1,NULL,'2018-12-02 20:00:00','2025-09-03 09:47:32');
INSERT INTO `col_situacao` (`id`, `nome`, `observacao`, `cores`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (6,'Transferido','','danger',1,1,NULL,'2018-12-02 20:00:00','2025-09-03 09:47:39');
INSERT INTO `col_situacao` (`id`, `nome`, `observacao`, `cores`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (7,'Cancelado','','danger',1,1,NULL,'2018-12-02 20:00:00','2025-09-03 09:47:44');
INSERT INTO `col_situacao` (`id`, `nome`, `observacao`, `cores`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (8,'Concluído','','primary',1,1,NULL,'2018-12-02 20:00:00','2025-09-03 09:47:51');
INSERT INTO `col_situacao` (`id`, `nome`, `observacao`, `cores`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (9,'Atualizar Dados','Necessário atualizar os dados cadastrais','warning',1,1,NULL,'2025-12-11 12:38:17',NULL);

-- rm_disciplina_atividade_tipo
DELETE FROM `rm_disciplina_atividade_tipo`;
INSERT INTO `rm_disciplina_atividade_tipo` (`id`, `nome`, `nomeAtividade`, `descricao`, `grupo`, `peso_padrao`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (1,'Prova Mensal','Prova Mensal',NULL,1,'10',1,1,NULL,'2025-01-19 11:11:05','2025-01-19 11:57:56');
INSERT INTO `rm_disciplina_atividade_tipo` (`id`, `nome`, `nomeAtividade`, `descricao`, `grupo`, `peso_padrao`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (2,'Prova Bimestral','Prova Bimestral',NULL,1,'10',1,1,NULL,'2024-12-20 11:11:05','2025-01-19 11:57:53');
INSERT INTO `rm_disciplina_atividade_tipo` (`id`, `nome`, `nomeAtividade`, `descricao`, `grupo`, `peso_padrao`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (3,'Prova Substitutiva','Prova Substitutiva',NULL,1,'10',2,1,NULL,'2024-12-20 11:11:05','2026-03-25 16:22:25');
INSERT INTO `rm_disciplina_atividade_tipo` (`id`, `nome`, `nomeAtividade`, `descricao`, `grupo`, `peso_padrao`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (4,'Recuperação','Recuperação',NULL,4,'10',2,1,NULL,'2024-12-20 11:11:45','2025-04-03 17:57:07');
INSERT INTO `rm_disciplina_atividade_tipo` (`id`, `nome`, `nomeAtividade`, `descricao`, `grupo`, `peso_padrao`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (5,'Atividade','Atividade',NULL,2,'10',1,1,NULL,'2024-12-20 11:11:45','2025-04-02 12:45:40');
INSERT INTO `rm_disciplina_atividade_tipo` (`id`, `nome`, `nomeAtividade`, `descricao`, `grupo`, `peso_padrao`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (6,'Acréscimo','Acréscimo',NULL,3,'10',2,1,NULL,'2024-12-20 11:12:41','2025-04-02 18:17:33');

-- rm_matriculas_situacao — E3b: domínio da SITUAÇÃO DA MATRÍCULA (no curso e no ano).
-- As 4 linhas do sistema de origem (Regular, Transferido, Cancelado, Substituído) saíram.
DELETE FROM `rm_matriculas_situacao`;
INSERT INTO `rm_matriculas_situacao` (`id`, `nome`, `observacao`, `cores`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES
 (1,'Cursando',NULL,'success',1,1,NULL,'2026-09-27 12:00:00',NULL),
 (2,'Concluído',NULL,'primary',1,1,NULL,'2026-09-27 12:00:00',NULL),
 (3,'Trancado',NULL,'warning',1,1,NULL,'2026-09-27 12:00:00',NULL),
 (4,'Cancelado',NULL,'danger',1,1,NULL,'2026-09-27 12:00:00',NULL),
 (5,'Transferido',NULL,'secondary',1,1,NULL,'2026-09-27 12:00:00',NULL);

-- rm_cursos_periodos
DELETE FROM `rm_cursos_periodos`;
INSERT INTO `rm_cursos_periodos` (`id`, `nome`, `observacao`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (1,'Diurno',NULL,1,1,NULL,'2025-01-30 07:47:12',NULL);
INSERT INTO `rm_cursos_periodos` (`id`, `nome`, `observacao`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (2,'Verspertino',NULL,1,1,NULL,'2025-01-30 07:47:12',NULL);
INSERT INTO `rm_cursos_periodos` (`id`, `nome`, `observacao`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES (3,'Noturno',NULL,2,1,NULL,'2025-01-30 07:48:01',NULL);

-- rm_matricula_tipos — E3b (D-51): domínio da FORMA DE INGRESSO no curso.
-- As 5 linhas do sistema de origem (Matrícula, Rematrícula, Transferência, Reserva de Vaga,
-- Troca de Responsável) eram do fluxo comercial e saíram.
DELETE FROM `rm_matricula_tipos`;
INSERT INTO `rm_matricula_tipos` (`id`, `nome`, `observacao`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES
 (1,'Processo seletivo',NULL,1,1,NULL,'2026-09-27 12:00:00',NULL),
 (2,'Transferência',NULL,1,1,NULL,'2026-09-27 12:00:00',NULL),
 (3,'Convênio ou indicação institucional',NULL,1,1,NULL,'2026-09-27 12:00:00',NULL),
 (4,'Outra',NULL,1,1,NULL,'2026-09-27 12:00:00',NULL);

-- adms_versoes
DELETE FROM `adms_versoes`;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`) VALUES (1,'1.0.0',1,0,0,'2026-09-27','VCOSTAEDU Escola 1: primeira versão, acadêmico de pós-graduação lato sensu.',2,'2026-09-27 12:00:00');
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`) VALUES (2,'1.1.0',1,1,0,'2026-09-27','Vínculo do aluno (curso, grade, módulo, ingresso), polos, modalidade, oferta ligada à matriz com idMoodle, gerar oferta do ano e enturmação sugerida e confirmada.',2,'2026-09-27 12:00:00');
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`) VALUES (3,'1.2.0',1,2,0,'2026-09-27','Notas, frequência e resultado pelas regras da instituição: etapa única, média das atividades, frequência pelos encontros, encerramento da oferta e boletim por curso.',2,'2026-09-27 12:00:00');
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`) VALUES (4,'1.3.0',1,3,0,'2026-09-27','Pronta para apresentar e treinar: demonstração consolidada montada pelas telas, guias de uso por perfil com as telas, Ajuda dentro do sistema e as decisões sobre a E4.',2,'2026-09-27 12:00:00');
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`) VALUES (5,'1.4.0',1,4,0,'2026-09-27','Dados do histórico escolar: instituição, atos legais por modalidade, polo e modalidade da matrícula, conclusão, TCC, titulação do docente, fonte única da vida acadêmica e prévia de conferência.',2,'2026-09-27 12:00:00');
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`) VALUES (6,'1.5.0',1,5,0,'2026-09-27','Histórico escolar parcial e final: modelos editáveis por seções, emissão em PDF com cópia fiel, hash e código, 2ª via, cancelamento e verificação pública.',2,'2026-09-27 12:00:00');
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`) VALUES (7,'1.6.0',1,6,0,'2026-09-27','Central de ajuda com guias curtos por tarefa e o Comece por aqui de cada perfil, tela e aviso de novidades, e reforços de segurança (histórico imutável no banco, arquivos internos fora da web).',2,'2026-09-27 12:00:00');
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`) VALUES (8,'1.7.0',1,7,0,'2026-09-28','Portal dentro do Moodle (iframe com HTTPS, cookies e plano B), limite de tentativas no login, no Esqueci a senha e na verificação pública, link de senha com o código fora do endereço gravado e trava de ambiente na limpeza da demonstração.',2,'2026-09-28 12:00:00');
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`) VALUES (9,'1.7.1',1,7,1,'2026-09-28','Preparação da homologação: ferramentas de implantação, conferência do ambiente, backup e restauração conferidos, limites de tentativas por IP ajustáveis, IP real atrás de proxy, links de senha antigos recusados e guias com as telas atualizadas.',2,'2026-09-28 12:00:00');
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`) VALUES (10,'1.7.2',1,7,2,'2026-09-28','Menu lateral reorganizado (grupos com nomes únicos, itens no grupo certo, marcação única do AdminLTE, Menu Rápido igual ao menu) e aviso da página inicial que fica até o Entendi ou o X.',1,'2026-09-28 12:00:00');
-- adms_novidades (E6c): as novidades de cada versão, escritas para o USUÁRIO, com os PERFIS a que
-- interessam e o GUIA ligado (docs/guia/tarefas/<slug>.md). Reescritas na E6c; as da E3a à E6b falavam com o desenvolvedor.
DELETE FROM `adms_novidades`;
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`) VALUES
 (1,1,'Painel da equipe','A secretaria, os professores e o administrador trabalham no painel: cada perfil vê só o que é dele.',1,'administrador,secretaria,professor','entrar-no-painel','2026-09-27 12:00:00'),
 (2,1,'Portal do aluno','No portal você vê o seu boletim, as notas das atividades e o horário.',2,'aluno','entrar-no-portal','2026-09-27 12:00:00'),
 (3,1,'Você cria a sua senha','A secretaria envia um link e você mesmo define a senha do portal. Esqueceu? Use "Esqueci a senha" na tela de entrada.',3,'aluno','primeiro-acesso-do-aluno','2026-09-27 12:00:00'),
 (4,1,'Cadastro do aluno com link de acesso','O aluno é cadastrado com o RA (único) e recebe um link para definir a própria senha; ninguém da equipe conhece a senha dele.',4,'secretaria,administrador','cadastrar-aluno-e-enviar-link','2026-09-27 12:00:00'),
 (5,1,'Matriz curricular por módulos','Curso, grade, módulos e disciplinas pela tela, com a carga horária somada pelo sistema.',5,'secretaria,administrador','montar-a-matriz','2026-09-27 12:00:00'),
 (6,2,'Oferta do ano a partir da matriz','Um clique cria as disciplinas do ano, com a carga horária da matriz, a turma, o polo e a modalidade.',1,'secretaria,administrador','gerar-a-oferta-do-ano','2026-09-27 12:00:00'),
 (7,2,'Vínculo e enturmação sugerida','O aluno é vinculado ao curso e ao módulo; as disciplinas do módulo dele já vêm sugeridas para confirmar.',2,'secretaria,administrador','vincular-e-enturmar','2026-09-27 12:00:00'),
 (8,2,'Polos e modalidade do curso','Cadastre os polos e defina, em cada curso, o polo e a modalidade padrão (presencial, EaD ou híbrido).',3,'administrador','polos-e-cursos','2026-09-27 12:00:00'),
 (9,3,'Notas com vírgula ou ponto','A nota vai de 0 a 10, com até 2 casas, e fica como você lançou; também por planilha CSV.',1,'professor,administrador','lancar-notas','2026-09-27 12:00:00'),
 (10,3,'Faltas e frequência','As faltas (e as compensadas, por atestado) dão a frequência pelo total de encontros da disciplina.',2,'professor,secretaria,administrador','lancar-faltas','2026-09-27 12:00:00'),
 (11,3,'Encerramento da disciplina','A secretaria encerra com uma prévia da conta de cada aluno; o resultado fica gravado e notas e faltas travam.',3,'secretaria,administrador','encerrar-uma-disciplina','2026-09-27 12:00:00'),
 (12,3,'Boletim por curso','O boletim mostra, por módulo, a média (parcial enquanto a disciplina está aberta), a frequência e o resultado.',4,'aluno','ler-o-boletim','2026-09-27 12:00:00'),
 (13,3,'Boletim da secretaria, com PDF','O mesmo boletim do aluno, por curso e por módulo, no painel e em PDF.',5,'secretaria,administrador','ver-e-imprimir-o-boletim','2026-09-27 12:00:00'),
 (14,3,'Regras da instituição','Média e frequência mínimas, casas decimais e o campo de login do portal são configurados pela instituição.',6,'administrador','regras-de-nota-e-frequencia','2026-09-27 12:00:00'),
 (15,4,'Ajuda dentro do sistema','O item Ajuda do menu abre o guia do seu perfil.',1,'administrador,secretaria,professor,aluno','usar-a-ajuda','2026-09-27 12:00:00'),
 (16,4,'Média só no encerramento, se a instituição quiser','A instituição pode mostrar a média só quando a disciplina é encerrada; até lá aparece "—".',2,'aluno','ler-o-boletim','2026-09-27 12:00:00'),
 (17,4,'Mostrar média parcial ao aluno','Nova opção nas configurações da instituição (padrão: sim).',3,'administrador','regras-de-nota-e-frequencia','2026-09-27 12:00:00'),
 (18,4,'Disciplina encerrada fica protegida','Não é possível enturmar aluno numa disciplina já encerrada: o administrador precisa reabrir antes.',4,'secretaria','vincular-e-enturmar','2026-09-27 12:00:00'),
 (19,5,'Dados da instituição e atos legais','Nome, CNPJ, logo e os atos de credenciamento por modalidade, que o histórico usa.',1,'administrador','dados-da-instituicao-e-atos-legais','2026-09-27 12:00:00'),
 (20,5,'Conclusão do curso e TCC','O TCC fica na ficha do aluno; a conclusão só é aceita com todas as disciplinas aprovadas e o TCC, se o curso exigir.',2,'secretaria,administrador','concluir-o-curso-e-tcc','2026-09-27 12:00:00'),
 (21,5,'Prévia do histórico','Confira os dados do histórico de um aluno, com todas as tentativas e os alertas do que falta.',3,'secretaria,administrador','previa-do-historico','2026-09-27 12:00:00'),
 (22,5,'Moodle, período e professor da oferta','A secretaria também completa cada disciplina ofertada: id do Moodle, período de realização e professor.',4,'secretaria','completar-a-oferta','2026-09-27 12:00:00'),
 (23,5,'Titulação do professor','Informe a titulação no cadastro do professor; ela sai no histórico.',5,'administrador','usuarios-e-niveis','2026-09-27 12:00:00'),
 (24,6,'Modelos de histórico','Personalize os modelos PARCIAL e FINAL por seções, com variáveis, assinaturas e prévia em PDF.',1,'administrador','editar-modelo-de-historico','2026-09-27 12:00:00'),
 (25,6,'Histórico parcial','Emita o histórico de quem ainda cursa, com as atividades por disciplina e o que está em curso.',2,'secretaria,administrador','emitir-historico-parcial','2026-09-27 12:00:00'),
 (26,6,'Histórico final','Emita o histórico de quem concluiu, com código de verificação; só sai sem pendências.',3,'secretaria,administrador','emitir-historico-final','2026-09-27 12:00:00'),
 (27,6,'2ª via e cancelamento','Reimprima o mesmo documento com a marca "2ª via"; o administrador cancela uma emissão, com motivo.',4,'secretaria,administrador','emitir-2a-via','2026-09-27 12:00:00'),
 (28,6,'Seu histórico tem código de verificação','Qualquer pessoa confere a autenticidade do seu histórico pelo código impresso no rodapé.',5,'aluno','conferir-um-documento','2026-09-27 12:00:00'),
 (29,6,'Verificação pública','Quem recebe um histórico confere a autenticidade pelo código, sem login, e pode enviar o PDF para comparar.',6,'secretaria,administrador','conferir-um-documento','2026-09-27 12:00:00'),
 (30,6,'Código da disciplina','A matéria ganhou o campo "Código / abreviação", que sai no histórico.',7,'secretaria,administrador','cadastrar-materias','2026-09-27 12:00:00'),
 (31,7,'Central de ajuda por tarefa','A Ajuda agora tem um guia curto para cada tarefa, com o "Comece por aqui" do seu perfil e, nas telas principais, o ícone ? que abre o guia daquela tela.',1,'administrador,secretaria,professor,aluno','usar-a-ajuda','2026-09-27 12:00:00'),
 (32,7,'Novidades','Esta tela: o que mudou em cada versão, com o link para o guia. O aviso aparece na entrada depois de cada versão, até você clicar em Entendi.',2,'administrador,secretaria,professor,aluno','usar-a-ajuda','2026-09-27 12:00:00'),
 (33,7,'Histórico emitido protegido também no banco','Uma emissão não pode ser alterada nem apagada nem direto no banco de dados; só cancelada, com motivo.',3,'administrador','cancelar-uma-emissao','2026-09-27 12:00:00'),
 (34,8,'Portal dentro do Moodle','O portal do aluno pode aparecer dentro de uma página do curso no Moodle, com HTTPS. Se o navegador do aluno bloquear, aparece o botão para abrir o portal numa aba própria.',1,'administrador','portal-dentro-do-moodle','2026-09-28 12:00:00'),
 (35,8,'Portal também dentro do Moodle','A instituição pode mostrar o portal dentro do Moodle. Se aparecer "Abrir o portal em nova aba", clique nele: o seu navegador preferiu abrir o portal numa aba própria.',2,'aluno','entrar-no-portal','2026-09-28 12:00:00'),
 (36,8,'Espera depois de muitas tentativas','Depois de 5 senhas erradas seguidas, a entrada pede uma espera curta, que cresce a cada novo erro. A mensagem de erro é a mesma para usuário ou senha.',3,'administrador,secretaria,professor','entrar-no-painel','2026-09-28 12:00:00'),
 (41,8,'Espera depois de muitas tentativas','Depois de 5 senhas erradas seguidas, a entrada do portal pede uma espera curta, que cresce a cada novo erro. Esqueceu a senha? Use Esqueci a senha.',8,'aluno','entrar-no-portal','2026-09-28 12:00:00'),
 (37,8,'Tentativas de acesso','Veja quem está em espera depois de muitas tentativas (login, Esqueci a senha e verificação) e libere antes, se for preciso.',4,'administrador','tentativas-de-acesso','2026-09-28 12:00:00'),
 (38,8,'Link de senha mais seguro','O link de senha abre a página Definir a senha e o código dele não fica no endereço gravado pelo servidor. Links antigos continuam valendo até expirar.',5,'secretaria,administrador','cadastrar-aluno-e-enviar-link','2026-09-28 12:00:00'),
 (39,8,'Link de senha mais seguro','O link da secretaria e o do Esqueci a senha abrem a página Definir a senha, que precisa do JavaScript ligado no navegador.',6,'aluno','primeiro-acesso-do-aluno','2026-09-28 12:00:00'),
 (40,8,'Documentos de demonstração marcados','Históricos emitidos fora da produção saem com a tarja DEMONSTRAÇÃO ou HOMOLOGAÇÃO, também na verificação pública.',7,'secretaria,administrador','conferir-um-documento','2026-09-28 12:00:00'),
 (42,9,'Limites de tentativas ajustáveis','Em Configurações → Instituição, ajuste quantas tentativas por hora um mesmo endereço de rede (IP) pode fazer no login, no Esqueci a senha e na verificação — útil para laboratórios e redes compartilhadas.',1,'administrador','tentativas-de-acesso','2026-09-28 12:00:00'),
 (43,9,'Guias com as telas novas','Os guias da Ajuda ganharam as imagens das telas que mudaram desde a versão 1.3.0: histórico, modelos, atos legais, TCC, verificação e outras.',2,'administrador,secretaria,professor','usar-a-ajuda','2026-09-28 12:00:00'),
 (44,9,'Links de senha antigos não valem mais','Links de definição de senha emitidos antes da versão 1.7.0 foram desativados. Se um aluno tiver um desses, envie um link novo em Enviar link de acesso.',3,'secretaria,administrador','cadastrar-aluno-e-enviar-link','2026-09-28 12:00:00'),
 (45,9,'Link de senha antigo','Se você recebeu um link para criar a senha antes desta versão, ele não vale mais: use Esqueci a senha ou peça um novo à secretaria.',4,'aluno','primeiro-acesso-do-aluno','2026-09-28 12:00:00'),
 (46,10,'Menu reorganizado','O menu da esquerda ficou em grupos com nomes únicos (Ensino, Educacional, Configurações, Equipe, Ajuda), com cada tela no grupo certo; os atalhos do Menu Rápido são os mesmos do menu.',1,'administrador,secretaria,professor','entrar-no-painel','2026-09-28 12:00:00'),
 (47,10,'Menu reorganizado','O menu do portal ficou em dois grupos: Acadêmico (Horário, Boletim, Notas das Atividades) e Ajuda (Central de ajuda e Novidades).',2,'aluno','entrar-no-portal','2026-09-28 12:00:00'),
 (48,10,'O aviso fica até você clicar em Entendi','O aviso de boas-vindas ou de novidades da página inicial aparece toda vez que ela abre, até você clicar em Entendi ou no ×.',3,'administrador,secretaria,professor,aluno','usar-a-ajuda','2026-09-28 12:00:00');


-- =============================================================================
-- E2b — PERFIL SECRETARIA (nível 4, já existente na origem)
-- Além do que o nível já tinha (acadêmicos, boletim, anos letivos, séries,
-- períodos, perfil), a secretaria passa a ter a ENTURMAÇÃO e as CONSULTAS:
--   91 disciplinas/listar (no menu), 94 disciplinas/visualizar,
--   75 cursos/listar, 77 cursos/visualizar,
--   241 portal-alunos/listar, 249 visualizar, 250 ordenacao, 253 enturmar,
--   254 desenturmar, 135 medias/final, 136 medias/bimestral,
--   96 faltas/visualizar (consulta).
-- =============================================================================
UPDATE `adms_nivacs_pgs` SET `permissao` = 1, `modified` = `modified`
 WHERE `adms_niveis_acesso_id` = 4 AND `adms_pagina_id` IN (91,94,75,77,241,249,250,253,254,135,136,96);
UPDATE `adms_nivacs_pgs` SET `lib_menu` = 1, `modified` = `modified`
 WHERE `adms_niveis_acesso_id` = 4 AND `adms_pagina_id` IN (91);

-- =============================================================================
-- E2b — CONFIGURAÇÕES DA INSTITUIÇÃO (adms_parametros, chave/valor/grupo)
-- =============================================================================
DELETE FROM `adms_parametros`;
INSERT INTO `adms_parametros` (`id`, `chave`, `valor`, `descricao`, `grupo`, `editavel`, `usuario_mod`, `created`, `modified`) VALUES
 (1,'portal.login.campo','ra','Campo que o aluno usa para entrar no portal: ra, cpf ou email.','Portal do aluno',1,NULL,'2026-09-27 12:00:00',NULL),
 (2,'portal.senha.link_horas','48','Validade, em horas, do link de definição de senha (primeiro acesso e esqueci a senha).','Portal do aluno',1,NULL,'2026-09-27 12:00:00',NULL),
 (3,'nota.casas','1','Casas decimais da média final (1 ou 2). Arredondamento meio para cima, só na média final.','Notas e frequência',1,NULL,'2026-09-27 12:00:00',NULL),
 (4,'nota.media_minima','7.0','Média mínima para aprovação (0 a 10).','Notas e frequência',1,NULL,'2026-09-27 12:00:00',NULL),
 (5,'frequencia.minima','75','Frequência mínima para aprovação, em % (0 a 100).','Notas e frequência',1,NULL,'2026-09-27 12:00:00',NULL),
 (6,'portal.media_parcial','1','Portal: mostrar ao aluno a média parcial da disciplina aberta (1 = sim, 0 = não). A média final e o resultado aparecem sempre após o encerramento.','Notas e frequência',1,NULL,'2026-09-27 12:00:00',NULL),
 (7,'limite.ip.login','50','Limite de tentativas: falhas de login por hora por IP (10 a 1000). E8.','Segurança',1,NULL,'2026-09-28 12:00:00',NULL),
 (8,'limite.ip.esqueci','20','Limite de tentativas: pedidos de Esqueci a senha por hora por IP (5 a 500). E8.','Segurança',1,NULL,'2026-09-28 12:00:00',NULL),
 (9,'limite.ip.verificacao','60','Limite de tentativas: consultas da verificação pública a cada 10 minutos por IP (10 a 1000). E8.','Segurança',1,NULL,'2026-09-28 12:00:00',NULL);

-- =============================================================================
-- E2b — ROTA academicos/enviar-link (366): emite o link de definição de senha.
-- Permissão por nível igual à de academicos/cadastrar (47).
-- =============================================================================
DELETE FROM `adms_paginas` WHERE `id` = 366;
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (366,'Enviar link de acesso','Academicos','enviarLink','academicos','enviar-link','Enviar link de acesso','Emite o link para o aluno definir a senha do portal.',2,NULL,2,1,1,'2026-09-27 12:00:00',NULL);
DELETE FROM `adms_nivacs_pgs` WHERE `adms_pagina_id` = 366;
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`)
SELECT 3026 + n.`adms_niveis_acesso_id`, n.`permissao`, 0, 2, 2, n.`adms_menu_id`, n.`adms_niveis_acesso_id`, 366, '2026-09-27 12:00:00', NULL
  FROM `adms_nivacs_pgs` n WHERE n.`adms_pagina_id` = 47;

-- E2b — TELA Configurações > Instituição (parametros/listar 364, parametros/salvar 365).
-- A classe passou a existir; liga só para o nível 1 (administrador). Nível 7 segue negado.
UPDATE `adms_paginas` SET `nome_pagina` = 'Configurações da instituição', `menu_pagina` = 'Instituição',
  `obs` = 'Login do portal (RA, CPF ou e-mail) e validade do link de senha. A troca do login é recusada com lista se algum aluno ativo não tiver o campo ou tiver valor repetido.', `modified` = `modified`
  WHERE `id` = 364;
UPDATE `adms_paginas` SET `obs` = 'POST. Grava as configurações da instituição.', `modified` = `modified` WHERE `id` = 365;
UPDATE `adms_nivacs_pgs` SET `permissao` = 1, `lib_menu` = 1, `adms_menu_id` = 4, `ordem` = 280, `modified` = `modified`
  WHERE `adms_niveis_acesso_id` = 1 AND `adms_pagina_id` = 364;
UPDATE `adms_nivacs_pgs` SET `permissao` = 1, `modified` = `modified`
  WHERE `adms_niveis_acesso_id` = 1 AND `adms_pagina_id` = 365;

-- E3a — DOMÍNIO da situação da grade (uni_rh_cursos_grades_situacao, da base de origem).
-- Só a grade ABERTA aceita mudança na matriz; a troca de situação fica no histórico.
DELETE FROM `uni_rh_cursos_grades_situacao`;
INSERT INTO `uni_rh_cursos_grades_situacao` (`id`, `nome`, `cores`, `created`, `modified`) VALUES
 (1,'Aberta','info','2026-09-27 12:00:00',NULL),
 (2,'Em análise','primary','2026-09-27 12:00:00',NULL),
 (3,'Finalizada','success','2026-09-27 12:00:00',NULL),
 (4,'Desativada','danger','2026-09-27 12:00:00',NULL);

-- E3a — SÉRIES → MÓDULOS. As rotas 332-335 (`series/*`) passam a ser `modulos/*`:
-- a série do sistema de origem é o módulo da grade. `series/*` deixa de existir e cai na recusa.
UPDATE `adms_paginas` SET `menu_pagina` = 'Módulos', `controller` = 'Modulos', `menu_controller` = 'modulos',
  `nome_pagina` = 'Módulos da matriz', `obs` = 'Módulos das grades, com a CH somada das disciplinas.', `modified` = `modified` WHERE `id` = 332;
UPDATE `adms_paginas` SET `menu_pagina` = 'Cadastrar módulo', `controller` = 'Modulos', `menu_controller` = 'modulos',
  `nome_pagina` = 'Cadastrar módulo', `obs` = 'Inclui um módulo numa grade aberta.', `modified` = `modified` WHERE `id` = 333;
UPDATE `adms_paginas` SET `menu_pagina` = 'Editar módulo', `controller` = 'Modulos', `menu_controller` = 'modulos',
  `nome_pagina` = 'Editar módulo', `obs` = 'Edita um módulo de grade aberta; a ordem de módulo em uso não muda.', `modified` = `modified` WHERE `id` = 334;
UPDATE `adms_paginas` SET `menu_pagina` = 'Apagar módulo', `controller` = 'Modulos', `menu_controller` = 'modulos',
  `nome_pagina` = 'Apagar módulo', `obs` = 'POST. Só módulo sem disciplina, matrícula ou oferta.', `modified` = `modified` WHERE `id` = 335;

-- E3a — MATRIZ: rotas 367-374 (controller Matriz). Liberadas ao administrador (1) e à
-- secretaria (4); os outros níveis ficam negados. "Matriz" entra no menu Ensino (7).
-- Saem as rotas 85-87 (cursos/cadastrar-modelo, visualizar-modelo, editar-modelo):
-- cadastrarModelo apagava o curso, e as outras duas não tinham método.
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (367,'Matriz','Matriz','listar','matriz','listar','Matriz curricular','Cursos com as grades, módulos e CH da matriz.',2,'fas fa-sitemap',1,1,1,'2026-09-27 12:00:00',NULL),
 (368,'Ver matriz','Matriz','visualizar','matriz','visualizar','Visualizar a matriz da grade','Módulos, disciplinas e CH de uma grade.',2,NULL,5,1,1,'2026-09-27 12:00:00',NULL),
 (369,'Cadastrar grade','Matriz','cadastrarGrade','matriz','cadastrar-grade','Cadastrar grade','Grade nova num curso; o número digitado é respeitado.',2,NULL,2,1,1,'2026-09-27 12:00:00',NULL),
 (370,'Editar grade','Matriz','editarGrade','matriz','editar-grade','Editar grade','Número e descrição de grade aberta.',2,NULL,3,1,1,'2026-09-27 12:00:00',NULL),
 (371,'Situação da grade','Matriz','situacaoGrade','matriz','situacao-grade','Alterar situação da grade','POST. Troca a situação com observação no histórico.',2,NULL,3,1,1,'2026-09-27 12:00:00',NULL),
 (372,'Incluir disciplina na matriz','Matriz','adicionarDisciplina','matriz','adicionar-disciplina','Incluir disciplina na matriz','POST. Disciplina do catálogo num módulo, com CH.',2,NULL,2,1,1,'2026-09-27 12:00:00',NULL),
 (373,'Mover disciplina na matriz','Matriz','moverDisciplina','matriz','mover-disciplina','Mover disciplina / alterar CH','POST. Troca o módulo ou a CH da disciplina.',2,NULL,3,1,1,'2026-09-27 12:00:00',NULL),
 (374,'Remover disciplina da matriz','Matriz','removerDisciplina','matriz','remover-disciplina','Remover disciplina da matriz','POST. Só se nenhuma oferta usar a disciplina.',2,NULL,4,1,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (3035,1,281,1,1,7,1,367,'2026-09-27 12:00:00',NULL),
 (3036,2,0,2,2,7,2,367,'2026-09-27 12:00:00',NULL),
 (3037,2,0,2,2,7,3,367,'2026-09-27 12:00:00',NULL),
 (3038,1,313,1,1,7,4,367,'2026-09-27 12:00:00',NULL),
 (3039,2,0,2,2,7,5,367,'2026-09-27 12:00:00',NULL),
 (3040,2,0,2,2,7,6,367,'2026-09-27 12:00:00',NULL),
 (3041,2,0,2,2,7,7,367,'2026-09-27 12:00:00',NULL),
 (3042,2,0,2,2,7,8,367,'2026-09-27 12:00:00',NULL),
 (3043,1,0,2,2,7,1,368,'2026-09-27 12:00:00',NULL),
 (3044,2,0,2,2,7,2,368,'2026-09-27 12:00:00',NULL),
 (3045,2,0,2,2,7,3,368,'2026-09-27 12:00:00',NULL),
 (3046,1,0,2,2,7,4,368,'2026-09-27 12:00:00',NULL),
 (3047,2,0,2,2,7,5,368,'2026-09-27 12:00:00',NULL),
 (3048,2,0,2,2,7,6,368,'2026-09-27 12:00:00',NULL),
 (3049,2,0,2,2,7,7,368,'2026-09-27 12:00:00',NULL),
 (3050,2,0,2,2,7,8,368,'2026-09-27 12:00:00',NULL),
 (3051,1,0,2,2,7,1,369,'2026-09-27 12:00:00',NULL),
 (3052,2,0,2,2,7,2,369,'2026-09-27 12:00:00',NULL),
 (3053,2,0,2,2,7,3,369,'2026-09-27 12:00:00',NULL),
 (3054,1,0,2,2,7,4,369,'2026-09-27 12:00:00',NULL),
 (3055,2,0,2,2,7,5,369,'2026-09-27 12:00:00',NULL),
 (3056,2,0,2,2,7,6,369,'2026-09-27 12:00:00',NULL),
 (3057,2,0,2,2,7,7,369,'2026-09-27 12:00:00',NULL),
 (3058,2,0,2,2,7,8,369,'2026-09-27 12:00:00',NULL),
 (3059,1,0,2,2,7,1,370,'2026-09-27 12:00:00',NULL),
 (3060,2,0,2,2,7,2,370,'2026-09-27 12:00:00',NULL),
 (3061,2,0,2,2,7,3,370,'2026-09-27 12:00:00',NULL),
 (3062,1,0,2,2,7,4,370,'2026-09-27 12:00:00',NULL),
 (3063,2,0,2,2,7,5,370,'2026-09-27 12:00:00',NULL),
 (3064,2,0,2,2,7,6,370,'2026-09-27 12:00:00',NULL),
 (3065,2,0,2,2,7,7,370,'2026-09-27 12:00:00',NULL),
 (3066,2,0,2,2,7,8,370,'2026-09-27 12:00:00',NULL),
 (3067,1,0,2,2,7,1,371,'2026-09-27 12:00:00',NULL),
 (3068,2,0,2,2,7,2,371,'2026-09-27 12:00:00',NULL),
 (3069,2,0,2,2,7,3,371,'2026-09-27 12:00:00',NULL),
 (3070,1,0,2,2,7,4,371,'2026-09-27 12:00:00',NULL),
 (3071,2,0,2,2,7,5,371,'2026-09-27 12:00:00',NULL),
 (3072,2,0,2,2,7,6,371,'2026-09-27 12:00:00',NULL),
 (3073,2,0,2,2,7,7,371,'2026-09-27 12:00:00',NULL),
 (3074,2,0,2,2,7,8,371,'2026-09-27 12:00:00',NULL),
 (3075,1,0,2,2,7,1,372,'2026-09-27 12:00:00',NULL),
 (3076,2,0,2,2,7,2,372,'2026-09-27 12:00:00',NULL),
 (3077,2,0,2,2,7,3,372,'2026-09-27 12:00:00',NULL),
 (3078,1,0,2,2,7,4,372,'2026-09-27 12:00:00',NULL),
 (3079,2,0,2,2,7,5,372,'2026-09-27 12:00:00',NULL),
 (3080,2,0,2,2,7,6,372,'2026-09-27 12:00:00',NULL),
 (3081,2,0,2,2,7,7,372,'2026-09-27 12:00:00',NULL),
 (3082,2,0,2,2,7,8,372,'2026-09-27 12:00:00',NULL),
 (3083,1,0,2,2,7,1,373,'2026-09-27 12:00:00',NULL),
 (3084,2,0,2,2,7,2,373,'2026-09-27 12:00:00',NULL),
 (3085,2,0,2,2,7,3,373,'2026-09-27 12:00:00',NULL),
 (3086,1,0,2,2,7,4,373,'2026-09-27 12:00:00',NULL),
 (3087,2,0,2,2,7,5,373,'2026-09-27 12:00:00',NULL),
 (3088,2,0,2,2,7,6,373,'2026-09-27 12:00:00',NULL),
 (3089,2,0,2,2,7,7,373,'2026-09-27 12:00:00',NULL),
 (3090,2,0,2,2,7,8,373,'2026-09-27 12:00:00',NULL),
 (3091,1,0,2,2,7,1,374,'2026-09-27 12:00:00',NULL),
 (3092,2,0,2,2,7,2,374,'2026-09-27 12:00:00',NULL),
 (3093,2,0,2,2,7,3,374,'2026-09-27 12:00:00',NULL),
 (3094,1,0,2,2,7,4,374,'2026-09-27 12:00:00',NULL),
 (3095,2,0,2,2,7,5,374,'2026-09-27 12:00:00',NULL),
 (3096,2,0,2,2,7,6,374,'2026-09-27 12:00:00',NULL),
 (3097,2,0,2,2,7,7,374,'2026-09-27 12:00:00',NULL),
 (3098,2,0,2,2,7,8,374,'2026-09-27 12:00:00',NULL);

-- E3b — DOMÍNIO da MODALIDADE (uni_rh_cursos_modalidade, estrutura da base de origem; linhas do VCOSTAEDU).
DELETE FROM `uni_rh_cursos_modalidade`;
INSERT INTO `uni_rh_cursos_modalidade` (`id`, `nome`, `descricao`, `status`, `usuario_id`, `usuario_mod`, `created`, `modified`) VALUES
 (1,'Presencial',NULL,1,1,NULL,'2026-09-27 12:00:00',NULL),
 (2,'EaD',NULL,1,1,NULL,'2026-09-27 12:00:00',NULL),
 (3,'Híbrido',NULL,1,1,NULL,'2026-09-27 12:00:00',NULL);

-- E3b — VÍNCULO DO ALUNO: rotas 375-377 (Academicos), níveis 1 e 4. Só POST, a partir da ficha do aluno.
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (375,'Vincular aluno ao curso','Academicos','vincular','academicos','vincular','Vincular aluno ao curso','POST. Matrícula no curso (grade, ingresso) e posição no ano (módulo).',2,NULL,2,1,1,'2026-09-27 12:00:00',NULL),
 (376,'Editar vínculo','Academicos','editarVinculo','academicos','editar-vinculo','Editar matrícula no curso','POST. Forma e data de ingresso, situação e observação.',2,NULL,3,1,1,'2026-09-27 12:00:00',NULL),
 (377,'Módulo do aluno no ano','Academicos','posicionarModulo','academicos','posicionar-modulo','Módulo do aluno no ano','POST. Grava ou muda o módulo do aluno num ano.',2,NULL,3,1,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (3099,1,0,2,2,7,1,375,'2026-09-27 12:00:00',NULL),
 (3100,2,0,2,2,7,2,375,'2026-09-27 12:00:00',NULL),
 (3101,2,0,2,2,7,3,375,'2026-09-27 12:00:00',NULL),
 (3102,1,0,2,2,7,4,375,'2026-09-27 12:00:00',NULL),
 (3103,2,0,2,2,7,5,375,'2026-09-27 12:00:00',NULL),
 (3104,2,0,2,2,7,6,375,'2026-09-27 12:00:00',NULL),
 (3105,2,0,2,2,7,7,375,'2026-09-27 12:00:00',NULL),
 (3106,2,0,2,2,7,8,375,'2026-09-27 12:00:00',NULL),
 (3107,1,0,2,2,7,1,376,'2026-09-27 12:00:00',NULL),
 (3108,2,0,2,2,7,2,376,'2026-09-27 12:00:00',NULL),
 (3109,2,0,2,2,7,3,376,'2026-09-27 12:00:00',NULL),
 (3110,1,0,2,2,7,4,376,'2026-09-27 12:00:00',NULL),
 (3111,2,0,2,2,7,5,376,'2026-09-27 12:00:00',NULL),
 (3112,2,0,2,2,7,6,376,'2026-09-27 12:00:00',NULL),
 (3113,2,0,2,2,7,7,376,'2026-09-27 12:00:00',NULL),
 (3114,2,0,2,2,7,8,376,'2026-09-27 12:00:00',NULL),
 (3115,1,0,2,2,7,1,377,'2026-09-27 12:00:00',NULL),
 (3116,2,0,2,2,7,2,377,'2026-09-27 12:00:00',NULL),
 (3117,2,0,2,2,7,3,377,'2026-09-27 12:00:00',NULL),
 (3118,1,0,2,2,7,4,377,'2026-09-27 12:00:00',NULL),
 (3119,2,0,2,2,7,5,377,'2026-09-27 12:00:00',NULL),
 (3120,2,0,2,2,7,6,377,'2026-09-27 12:00:00',NULL),
 (3121,2,0,2,2,7,7,377,'2026-09-27 12:00:00',NULL),
 (3122,2,0,2,2,7,8,377,'2026-09-27 12:00:00',NULL);

-- E3b — POLOS (D-34): rotas 378-380, só o administrador (nível 1). Menu Configurações.
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (378,'Polos','Polos','listar','polos','listar','Polos','Polos da instituição.',2,'fas fa-map-marker-alt',1,1,1,'2026-09-27 12:00:00',NULL),
 (379,'Cadastrar polo','Polos','cadastrar','polos','cadastrar','Cadastrar polo','Nome, cidade, UF, endereço, instituição parceira.',2,NULL,2,1,1,'2026-09-27 12:00:00',NULL),
 (380,'Editar polo','Polos','editar','polos','editar','Editar polo','Edita ou desativa um polo.',2,NULL,3,1,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (3123,1,282,1,1,4,1,378,'2026-09-27 12:00:00',NULL),
 (3124,2,0,2,2,4,2,378,'2026-09-27 12:00:00',NULL),
 (3125,2,0,2,2,4,3,378,'2026-09-27 12:00:00',NULL),
 (3126,2,0,2,2,4,4,378,'2026-09-27 12:00:00',NULL),
 (3127,2,0,2,2,4,5,378,'2026-09-27 12:00:00',NULL),
 (3128,2,0,2,2,4,6,378,'2026-09-27 12:00:00',NULL),
 (3129,2,0,2,2,4,7,378,'2026-09-27 12:00:00',NULL),
 (3130,2,0,2,2,4,8,378,'2026-09-27 12:00:00',NULL),
 (3131,1,0,2,2,7,1,379,'2026-09-27 12:00:00',NULL),
 (3132,2,0,2,2,7,2,379,'2026-09-27 12:00:00',NULL),
 (3133,2,0,2,2,7,3,379,'2026-09-27 12:00:00',NULL),
 (3134,2,0,2,2,7,4,379,'2026-09-27 12:00:00',NULL),
 (3135,2,0,2,2,7,5,379,'2026-09-27 12:00:00',NULL),
 (3136,2,0,2,2,7,6,379,'2026-09-27 12:00:00',NULL),
 (3137,2,0,2,2,7,7,379,'2026-09-27 12:00:00',NULL),
 (3138,2,0,2,2,7,8,379,'2026-09-27 12:00:00',NULL),
 (3139,1,0,2,2,7,1,380,'2026-09-27 12:00:00',NULL),
 (3140,2,0,2,2,7,2,380,'2026-09-27 12:00:00',NULL),
 (3141,2,0,2,2,7,3,380,'2026-09-27 12:00:00',NULL),
 (3142,2,0,2,2,7,4,380,'2026-09-27 12:00:00',NULL),
 (3143,2,0,2,2,7,5,380,'2026-09-27 12:00:00',NULL),
 (3144,2,0,2,2,7,6,380,'2026-09-27 12:00:00',NULL),
 (3145,2,0,2,2,7,7,380,'2026-09-27 12:00:00',NULL),
 (3146,2,0,2,2,7,8,380,'2026-09-27 12:00:00',NULL);

-- E3b — GERAR OFERTA DO ANO: a rota 240 (disciplinas/gerar-novoano, negada na E2b porque
-- AdmsGerarNovaGrade não existia) vira disciplinas/gerar-oferta, liberada aos níveis 1 e 4.
UPDATE `adms_paginas` SET `menu_pagina` = 'Gerar oferta do ano', `metodo` = 'gerarOferta', `menu_metodo` = 'gerar-oferta',
  `nome_pagina` = 'Gerar oferta do ano', `obs` = 'Cria as ofertas do ano a partir da matriz (grade + módulos + turma + polo + modalidade), sem duplicar.', `modified` = `modified` WHERE `id` = 240;
UPDATE `adms_nivacs_pgs` SET `permissao` = IF(`adms_niveis_acesso_id` IN (1,4), 1, 2), `lib_menu` = 2, `modified` = `modified` WHERE `adms_pagina_id` = 240;

-- E3b — ENTURMAÇÃO SUGERIDA E CONFIRMADA: rota 381 (PortalAlunos), níveis 1 e 4. Só POST.
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (381,'Confirmar enturmação','PortalAlunos','confirmarEnturmacao','portal-alunos','confirmar-enturmacao','Confirmar enturmação','POST. Grava as ofertas marcadas da posição do aluno no ano.',2,NULL,3,1,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (3147,1,0,2,2,7,1,381,'2026-09-27 12:00:00',NULL),
 (3148,2,0,2,2,7,2,381,'2026-09-27 12:00:00',NULL),
 (3149,2,0,2,2,7,3,381,'2026-09-27 12:00:00',NULL),
 (3150,1,0,2,2,7,4,381,'2026-09-27 12:00:00',NULL),
 (3151,2,0,2,2,7,5,381,'2026-09-27 12:00:00',NULL),
 (3152,2,0,2,2,7,6,381,'2026-09-27 12:00:00',NULL),
 (3153,2,0,2,2,7,7,381,'2026-09-27 12:00:00',NULL),
 (3154,2,0,2,2,7,8,381,'2026-09-27 12:00:00',NULL);

-- E4 — FALTAS em etapa única: faltas/visualizar (96) e faltas/editar (105, POST) — o
-- professor e a SECRETARIA lançam. Saíram atualizar-alunos, edit-faltas, apagar, compensar e
-- editar-limite (100, 106, 146, 213, 219), que eram da chamada e dos bimestres.
UPDATE `adms_paginas` SET `menu_pagina` = 'Lançar faltas', `nome_pagina` = 'Lançar faltas da oferta', `obs` = 'POST. Faltas e compensadas por aluno; recusado com a oferta encerrada.', `modified` = `modified` WHERE `id` = 105;
UPDATE `adms_nivacs_pgs` SET `permissao` = 1, `modified` = `modified` WHERE `adms_pagina_id` = 105 AND `adms_niveis_acesso_id` = 4;

-- E4 — ENCERRAMENTO DA OFERTA: 382 (encerrar, secretaria e administrador) e 383 (reabrir, só o administrador).
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (382,'Encerrar oferta','Disciplinas','encerrar','disciplinas','encerrar','Encerrar oferta','Prévia e confirmação: grava média final, frequência e resultado por aluno e trava notas e faltas.',2,NULL,3,1,1,'2026-09-27 12:00:00',NULL),
 (383,'Reabrir oferta','Disciplinas','reabrir','disciplinas','reabrir','Reabrir oferta','POST. Só o administrador, com observação no histórico.',2,NULL,3,1,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (3155,1,0,2,2,7,1,382,'2026-09-27 12:00:00',NULL),
 (3156,2,0,2,2,7,2,382,'2026-09-27 12:00:00',NULL),
 (3157,2,0,2,2,7,3,382,'2026-09-27 12:00:00',NULL),
 (3158,1,0,2,2,7,4,382,'2026-09-27 12:00:00',NULL),
 (3159,2,0,2,2,7,5,382,'2026-09-27 12:00:00',NULL),
 (3160,2,0,2,2,7,6,382,'2026-09-27 12:00:00',NULL),
 (3161,2,0,2,2,7,7,382,'2026-09-27 12:00:00',NULL),
 (3162,2,0,2,2,7,8,382,'2026-09-27 12:00:00',NULL),
 (3163,1,0,2,2,7,1,383,'2026-09-27 12:00:00',NULL),
 (3164,2,0,2,2,7,2,383,'2026-09-27 12:00:00',NULL),
 (3165,2,0,2,2,7,3,383,'2026-09-27 12:00:00',NULL),
 (3166,2,0,2,2,7,4,383,'2026-09-27 12:00:00',NULL),
 (3167,2,0,2,2,7,5,383,'2026-09-27 12:00:00',NULL),
 (3168,2,0,2,2,7,6,383,'2026-09-27 12:00:00',NULL),
 (3169,2,0,2,2,7,7,383,'2026-09-27 12:00:00',NULL),
 (3170,2,0,2,2,7,8,383,'2026-09-27 12:00:00',NULL);

-- E5 — MATÉRIAS para a SECRETARIA (nível 4): o catálogo de disciplinas é da secretaria, que monta a
-- matriz. listar (no menu Ensino), cadastrar, visualizar e editar; apagar continua fora.
UPDATE `adms_nivacs_pgs` SET `permissao` = 1, `modified` = `modified` WHERE `adms_niveis_acesso_id` = 4 AND `adms_pagina_id` IN (80,81,82,83);
UPDATE `adms_nivacs_pgs` SET `lib_menu` = 1, `adms_menu_id` = 7, `modified` = `modified` WHERE `adms_niveis_acesso_id` = 4 AND `adms_pagina_id` = 80;

-- E5 — AJUDA: guia de uso do perfil logado, em nova aba (D-143).
-- Painel: menu próprio "Ajuda" (antes de Sair), rota 384 ajuda/ver para administrador (1),
-- secretaria (4) e professor (6); os demais níveis não têm guia. Portal: home/ajuda (página 14).
INSERT INTO `adms_menus` (`id`, `nome`, `icone`, `ordem`, `adms_sit_id`, `created`, `modified`) VALUES (8,'Ajuda','fas fa-question-circle',6,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (384,'Ajuda','Ajuda','ver','ajuda','ver','Ajuda — guia do perfil','Abre, em nova aba, o guia de uso do perfil logado (administrador, secretaria ou professor) e entrega as imagens dele.',2,'fas fa-question-circle',5,1,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (3183,1,1,2,1,8,1,384,'2026-09-27 12:00:00',NULL),
 (3184,2,1,2,2,8,2,384,'2026-09-27 12:00:00',NULL),
 (3185,2,1,2,2,8,3,384,'2026-09-27 12:00:00',NULL),
 (3186,1,1,2,1,8,4,384,'2026-09-27 12:00:00',NULL),
 (3187,2,1,2,2,8,5,384,'2026-09-27 12:00:00',NULL),
 (3188,1,1,2,1,8,6,384,'2026-09-27 12:00:00',NULL),
 (3189,2,1,2,2,8,7,384,'2026-09-27 12:00:00',NULL),
 (3190,2,1,2,2,8,8,384,'2026-09-27 12:00:00',NULL);
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (14,'Ajuda','Home','ajuda','home','ajuda','Ajuda','Guia do aluno, em nova aba (E5).',2,'fas fa-question-circle',5,1,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `col_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (43,1,14,2,1,4,1,14,'2026-09-27 12:00:00',NULL);

-- E6a — OFERTA para a SECRETARIA (decisão do Vini sobre a E5): editar a oferta (93: id do Moodle,
-- encontros, período) e o professor da oferta (104) passam a valer também para o nível 4.
UPDATE `adms_nivacs_pgs` SET `permissao` = 1, `modified` = `modified` WHERE `adms_niveis_acesso_id` = 4 AND `adms_pagina_id` IN (93,104);

-- E6a — DADOS DA INSTITUIÇÃO (D-09): rota 385 dados-instituicao/editar, só o administrador, no menu Configurações.
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (385,'Dados da instituição','DadosInstituicao','editar','dados-instituicao','editar','Dados da instituição','Nome, mantenedora, CNPJ, código e-MEC, endereço e logo: cabeçalhos e histórico (E6a).',2,'fas fa-university',3,1,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (3301,1,283,1,1,4,1,385,'2026-09-27 12:00:00',NULL),
 (3302,2,283,1,2,4,2,385,'2026-09-27 12:00:00',NULL),
 (3303,2,283,1,2,4,3,385,'2026-09-27 12:00:00',NULL),
 (3304,2,283,1,2,4,4,385,'2026-09-27 12:00:00',NULL),
 (3305,2,283,1,2,4,5,385,'2026-09-27 12:00:00',NULL),
 (3306,2,283,1,2,4,6,385,'2026-09-27 12:00:00',NULL),
 (3307,2,283,1,2,4,7,385,'2026-09-27 12:00:00',NULL),
 (3308,2,283,1,2,4,8,385,'2026-09-27 12:00:00',NULL);

-- E6a — ATOS LEGAIS (D-36): 386 listar (menu Configurações), 387 cadastrar, 388 editar; só o administrador.
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (386,'Atos legais','AtosLegais','listar','atos-legais','listar','Atos legais','Atos da instituição por modalidade; o histórico usa o ativo e vigente da modalidade da matrícula (E6a).',2,'fas fa-gavel',1,1,1,'2026-09-27 12:00:00',NULL),
 (387,'Cadastrar ato legal','AtosLegais','cadastrar','atos-legais','cadastrar','Cadastrar ato legal','E6a',2,NULL,2,1,1,'2026-09-27 12:00:00',NULL),
 (388,'Editar ato legal','AtosLegais','editar','atos-legais','editar','Editar ato legal','E6a',2,NULL,3,1,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (3309,1,284,1,1,4,1,386,'2026-09-27 12:00:00',NULL),
 (3310,2,284,1,2,4,2,386,'2026-09-27 12:00:00',NULL),
 (3311,2,284,1,2,4,3,386,'2026-09-27 12:00:00',NULL),
 (3312,2,284,1,2,4,4,386,'2026-09-27 12:00:00',NULL),
 (3313,2,284,1,2,4,5,386,'2026-09-27 12:00:00',NULL),
 (3314,2,284,1,2,4,6,386,'2026-09-27 12:00:00',NULL),
 (3315,2,284,1,2,4,7,386,'2026-09-27 12:00:00',NULL),
 (3316,2,284,1,2,4,8,386,'2026-09-27 12:00:00',NULL),
 (3317,1,0,1,2,4,1,387,'2026-09-27 12:00:00',NULL),
 (3318,2,0,1,2,4,2,387,'2026-09-27 12:00:00',NULL),
 (3319,2,0,1,2,4,3,387,'2026-09-27 12:00:00',NULL),
 (3320,2,0,1,2,4,4,387,'2026-09-27 12:00:00',NULL),
 (3321,2,0,1,2,4,5,387,'2026-09-27 12:00:00',NULL),
 (3322,2,0,1,2,4,6,387,'2026-09-27 12:00:00',NULL),
 (3323,2,0,1,2,4,7,387,'2026-09-27 12:00:00',NULL),
 (3324,2,0,1,2,4,8,387,'2026-09-27 12:00:00',NULL),
 (3325,1,0,1,2,4,1,388,'2026-09-27 12:00:00',NULL),
 (3326,2,0,1,2,4,2,388,'2026-09-27 12:00:00',NULL),
 (3327,2,0,1,2,4,3,388,'2026-09-27 12:00:00',NULL),
 (3328,2,0,1,2,4,4,388,'2026-09-27 12:00:00',NULL),
 (3329,2,0,1,2,4,5,388,'2026-09-27 12:00:00',NULL),
 (3330,2,0,1,2,4,6,388,'2026-09-27 12:00:00',NULL),
 (3331,2,0,1,2,4,7,388,'2026-09-27 12:00:00',NULL),
 (3332,2,0,1,2,4,8,388,'2026-09-27 12:00:00',NULL);

-- E6a — TITULAÇÃO do docente: uni_rh_titulacao (estrutura da base de origem; linhas do VCOSTAEDU).
DELETE FROM `uni_rh_titulacao`;
INSERT INTO `uni_rh_titulacao` (`id`, `nome`, `status_id`, `created`, `modified`) VALUES
 (1,'Especialista',1,'2026-09-27 12:00:00',NULL),
 (2,'Mestre',1,'2026-09-27 12:00:00',NULL),
 (3,'Doutor',1,'2026-09-27 12:00:00',NULL);

-- E6a — TCC (389, POST da ficha do aluno) e PRÉVIA DO HISTÓRICO (390, menu Educacional): administrador e secretaria.
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (389,'Salvar TCC','Tcc','salvar','tcc','salvar','Salvar TCC','POST. TCC da matrícula no curso: título, orientador, nota, data, situação (E6a).',2,NULL,3,1,1,'2026-09-27 12:00:00',NULL),
 (390,'Prévia do histórico','Historico','previa','historico','previa','Prévia do histórico','Conferência dos dados do histórico (fonte única da vida acadêmica), com alertas (E6a).',2,'fas fa-scroll',5,1,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (3333,1,0,1,2,5,1,389,'2026-09-27 12:00:00',NULL),
 (3334,2,0,1,2,5,2,389,'2026-09-27 12:00:00',NULL),
 (3335,2,0,1,2,5,3,389,'2026-09-27 12:00:00',NULL),
 (3336,1,0,1,2,5,4,389,'2026-09-27 12:00:00',NULL),
 (3337,2,0,1,2,5,5,389,'2026-09-27 12:00:00',NULL),
 (3338,2,0,1,2,5,6,389,'2026-09-27 12:00:00',NULL),
 (3339,2,0,1,2,5,7,389,'2026-09-27 12:00:00',NULL),
 (3340,2,0,1,2,5,8,389,'2026-09-27 12:00:00',NULL),
 (3341,1,47,1,1,5,1,390,'2026-09-27 12:00:00',NULL),
 (3342,2,47,1,2,5,2,390,'2026-09-27 12:00:00',NULL),
 (3343,2,47,1,2,5,3,390,'2026-09-27 12:00:00',NULL),
 (3344,1,47,1,1,5,4,390,'2026-09-27 12:00:00',NULL),
 (3345,2,47,1,2,5,5,390,'2026-09-27 12:00:00',NULL),
 (3346,2,47,1,2,5,6,390,'2026-09-27 12:00:00',NULL),
 (3347,2,47,1,2,5,7,390,'2026-09-27 12:00:00',NULL),
 (3348,2,47,1,2,5,8,390,'2026-09-27 12:00:00',NULL);

-- E6b — MODELOS DE HISTÓRICO (391-394, só o administrador; 391 no menu Configurações), HISTÓRICOS EMITIDOS (395-400, administrador e secretaria; 395 no menu Educacional), CANCELAR (401, só o administrador) e VERIFICAÇÃO PÚBLICA (402, lib_pub = 1, sem login).
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (391,'Modelos de histórico','HistoricoModelos','listar','historico-modelos','listar','Modelos de histórico','Modelos PARCIAL/FINAL por seções, com versões (E6b).',2,'fas fa-file-signature',1,1,1,'2026-09-27 12:00:00',NULL),
 (392,'Novo modelo de histórico','HistoricoModelos','cadastrar','historico-modelos','cadastrar','Novo modelo de histórico','E6b',2,NULL,2,1,1,'2026-09-27 12:00:00',NULL),
 (393,'Editar modelo de histórico','HistoricoModelos','editar','historico-modelos','editar','Editar modelo de histórico','E6b',2,NULL,3,1,1,'2026-09-27 12:00:00',NULL),
 (394,'Prévia do modelo de histórico','HistoricoModelos','previa','historico-modelos','previa','Prévia do modelo de histórico','PDF com tarja, sem gravar (E6b).',2,NULL,5,1,1,'2026-09-27 12:00:00',NULL),
 (395,'Históricos emitidos','HistoricoEmissoes','listar','historico-emissoes','listar','Históricos emitidos','Emissões do histórico parcial e final (E6b).',2,'fas fa-stamp',1,1,1,'2026-09-27 12:00:00',NULL),
 (396,'Emitir histórico','HistoricoEmissoes','emitir','historico-emissoes','emitir','Emitir histórico','Tipo e modelo → prévia → emitir (E6b).',2,NULL,2,1,1,'2026-09-27 12:00:00',NULL),
 (397,'Prévia da emissão','HistoricoEmissoes','previa','historico-emissoes','previa','Prévia da emissão do histórico','PDF com tarja, sem gravar (E6b).',2,NULL,5,1,1,'2026-09-27 12:00:00',NULL),
 (398,'Ver emissão','HistoricoEmissoes','ver','historico-emissoes','ver','Ver emissão do histórico','E6b',2,NULL,5,1,1,'2026-09-27 12:00:00',NULL),
 (399,'PDF da emissão','HistoricoEmissoes','pdf','historico-emissoes','pdf','PDF do histórico emitido','Serve o PDF gravado fora da web (E6b).',2,NULL,5,1,1,'2026-09-27 12:00:00',NULL),
 (400,'2ª via do histórico','HistoricoEmissoes','segundaVia','historico-emissoes','segunda-via','2ª via do histórico','POST. Mesmo snapshot e código, com a marca da via (E6b).',2,NULL,3,1,1,'2026-09-27 12:00:00',NULL),
 (401,'Cancelar emissão','HistoricoEmissoes','cancelar','historico-emissoes','cancelar','Cancelar emissão do histórico','POST, com motivo; só o administrador (E6b).',2,NULL,3,1,1,'2026-09-27 12:00:00',NULL),
 (402,'Verificar documento','Verificar','codigo','verificar','codigo','Verificação pública do histórico','Pública (sem login): código, hash e conferência do PDF (E6b).',1,NULL,5,1,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (3361,1,285,1,1,4,1,391,'2026-09-27 12:00:00',NULL),
 (3362,2,285,1,2,4,2,391,'2026-09-27 12:00:00',NULL),
 (3363,2,285,1,2,4,3,391,'2026-09-27 12:00:00',NULL),
 (3364,2,285,1,2,4,4,391,'2026-09-27 12:00:00',NULL),
 (3365,2,285,1,2,4,5,391,'2026-09-27 12:00:00',NULL),
 (3366,2,285,1,2,4,6,391,'2026-09-27 12:00:00',NULL),
 (3367,2,285,1,2,4,7,391,'2026-09-27 12:00:00',NULL),
 (3368,2,285,1,2,4,8,391,'2026-09-27 12:00:00',NULL),
 (3369,1,0,1,2,4,1,392,'2026-09-27 12:00:00',NULL),
 (3370,2,0,1,2,4,2,392,'2026-09-27 12:00:00',NULL),
 (3371,2,0,1,2,4,3,392,'2026-09-27 12:00:00',NULL),
 (3372,2,0,1,2,4,4,392,'2026-09-27 12:00:00',NULL),
 (3373,2,0,1,2,4,5,392,'2026-09-27 12:00:00',NULL),
 (3374,2,0,1,2,4,6,392,'2026-09-27 12:00:00',NULL),
 (3375,2,0,1,2,4,7,392,'2026-09-27 12:00:00',NULL),
 (3376,2,0,1,2,4,8,392,'2026-09-27 12:00:00',NULL),
 (3377,1,0,1,2,4,1,393,'2026-09-27 12:00:00',NULL),
 (3378,2,0,1,2,4,2,393,'2026-09-27 12:00:00',NULL),
 (3379,2,0,1,2,4,3,393,'2026-09-27 12:00:00',NULL),
 (3380,2,0,1,2,4,4,393,'2026-09-27 12:00:00',NULL),
 (3381,2,0,1,2,4,5,393,'2026-09-27 12:00:00',NULL),
 (3382,2,0,1,2,4,6,393,'2026-09-27 12:00:00',NULL),
 (3383,2,0,1,2,4,7,393,'2026-09-27 12:00:00',NULL),
 (3384,2,0,1,2,4,8,393,'2026-09-27 12:00:00',NULL),
 (3385,1,0,1,2,4,1,394,'2026-09-27 12:00:00',NULL),
 (3386,2,0,1,2,4,2,394,'2026-09-27 12:00:00',NULL),
 (3387,2,0,1,2,4,3,394,'2026-09-27 12:00:00',NULL),
 (3388,2,0,1,2,4,4,394,'2026-09-27 12:00:00',NULL),
 (3389,2,0,1,2,4,5,394,'2026-09-27 12:00:00',NULL),
 (3390,2,0,1,2,4,6,394,'2026-09-27 12:00:00',NULL),
 (3391,2,0,1,2,4,7,394,'2026-09-27 12:00:00',NULL),
 (3392,2,0,1,2,4,8,394,'2026-09-27 12:00:00',NULL),
 (3393,1,48,1,1,5,1,395,'2026-09-27 12:00:00',NULL),
 (3394,2,48,1,2,5,2,395,'2026-09-27 12:00:00',NULL),
 (3395,2,48,1,2,5,3,395,'2026-09-27 12:00:00',NULL),
 (3396,1,48,1,1,5,4,395,'2026-09-27 12:00:00',NULL),
 (3397,2,48,1,2,5,5,395,'2026-09-27 12:00:00',NULL),
 (3398,2,48,1,2,5,6,395,'2026-09-27 12:00:00',NULL),
 (3399,2,48,1,2,5,7,395,'2026-09-27 12:00:00',NULL),
 (3400,2,48,1,2,5,8,395,'2026-09-27 12:00:00',NULL),
 (3401,1,0,1,2,5,1,396,'2026-09-27 12:00:00',NULL),
 (3402,2,0,1,2,5,2,396,'2026-09-27 12:00:00',NULL),
 (3403,2,0,1,2,5,3,396,'2026-09-27 12:00:00',NULL),
 (3404,1,0,1,2,5,4,396,'2026-09-27 12:00:00',NULL),
 (3405,2,0,1,2,5,5,396,'2026-09-27 12:00:00',NULL),
 (3406,2,0,1,2,5,6,396,'2026-09-27 12:00:00',NULL),
 (3407,2,0,1,2,5,7,396,'2026-09-27 12:00:00',NULL),
 (3408,2,0,1,2,5,8,396,'2026-09-27 12:00:00',NULL),
 (3409,1,0,1,2,5,1,397,'2026-09-27 12:00:00',NULL),
 (3410,2,0,1,2,5,2,397,'2026-09-27 12:00:00',NULL),
 (3411,2,0,1,2,5,3,397,'2026-09-27 12:00:00',NULL),
 (3412,1,0,1,2,5,4,397,'2026-09-27 12:00:00',NULL),
 (3413,2,0,1,2,5,5,397,'2026-09-27 12:00:00',NULL),
 (3414,2,0,1,2,5,6,397,'2026-09-27 12:00:00',NULL),
 (3415,2,0,1,2,5,7,397,'2026-09-27 12:00:00',NULL),
 (3416,2,0,1,2,5,8,397,'2026-09-27 12:00:00',NULL),
 (3417,1,0,1,2,5,1,398,'2026-09-27 12:00:00',NULL),
 (3418,2,0,1,2,5,2,398,'2026-09-27 12:00:00',NULL),
 (3419,2,0,1,2,5,3,398,'2026-09-27 12:00:00',NULL),
 (3420,1,0,1,2,5,4,398,'2026-09-27 12:00:00',NULL),
 (3421,2,0,1,2,5,5,398,'2026-09-27 12:00:00',NULL),
 (3422,2,0,1,2,5,6,398,'2026-09-27 12:00:00',NULL),
 (3423,2,0,1,2,5,7,398,'2026-09-27 12:00:00',NULL),
 (3424,2,0,1,2,5,8,398,'2026-09-27 12:00:00',NULL),
 (3425,1,0,1,2,5,1,399,'2026-09-27 12:00:00',NULL),
 (3426,2,0,1,2,5,2,399,'2026-09-27 12:00:00',NULL),
 (3427,2,0,1,2,5,3,399,'2026-09-27 12:00:00',NULL),
 (3428,1,0,1,2,5,4,399,'2026-09-27 12:00:00',NULL),
 (3429,2,0,1,2,5,5,399,'2026-09-27 12:00:00',NULL),
 (3430,2,0,1,2,5,6,399,'2026-09-27 12:00:00',NULL),
 (3431,2,0,1,2,5,7,399,'2026-09-27 12:00:00',NULL),
 (3432,2,0,1,2,5,8,399,'2026-09-27 12:00:00',NULL),
 (3433,1,0,1,2,5,1,400,'2026-09-27 12:00:00',NULL),
 (3434,2,0,1,2,5,2,400,'2026-09-27 12:00:00',NULL),
 (3435,2,0,1,2,5,3,400,'2026-09-27 12:00:00',NULL),
 (3436,1,0,1,2,5,4,400,'2026-09-27 12:00:00',NULL),
 (3437,2,0,1,2,5,5,400,'2026-09-27 12:00:00',NULL),
 (3438,2,0,1,2,5,6,400,'2026-09-27 12:00:00',NULL),
 (3439,2,0,1,2,5,7,400,'2026-09-27 12:00:00',NULL),
 (3440,2,0,1,2,5,8,400,'2026-09-27 12:00:00',NULL),
 (3441,1,0,1,2,5,1,401,'2026-09-27 12:00:00',NULL),
 (3442,2,0,1,2,5,2,401,'2026-09-27 12:00:00',NULL),
 (3443,2,0,1,2,5,3,401,'2026-09-27 12:00:00',NULL),
 (3444,2,0,1,2,5,4,401,'2026-09-27 12:00:00',NULL),
 (3445,2,0,1,2,5,5,401,'2026-09-27 12:00:00',NULL),
 (3446,2,0,1,2,5,6,401,'2026-09-27 12:00:00',NULL),
 (3447,2,0,1,2,5,7,401,'2026-09-27 12:00:00',NULL),
 (3448,2,0,1,2,5,8,401,'2026-09-27 12:00:00',NULL),
 (3449,2,0,1,2,5,1,402,'2026-09-27 12:00:00',NULL),
 (3450,2,0,1,2,5,2,402,'2026-09-27 12:00:00',NULL),
 (3451,2,0,1,2,5,3,402,'2026-09-27 12:00:00',NULL),
 (3452,2,0,1,2,5,4,402,'2026-09-27 12:00:00',NULL),
 (3453,2,0,1,2,5,5,402,'2026-09-27 12:00:00',NULL),
 (3454,2,0,1,2,5,6,402,'2026-09-27 12:00:00',NULL),
 (3455,2,0,1,2,5,7,402,'2026-09-27 12:00:00',NULL),
 (3456,2,0,1,2,5,8,402,'2026-09-27 12:00:00',NULL);

-- E6b — MODELOS DE HISTÓRICO padrão, NEUTROS (sem dado de cliente), gerados de AdmsHistoricoSecoes::padrao():
-- 1 = PARCIAL, 2 = FINAL; ativos, sem curso (padrão da instituição), versão 1. A instituição personaliza pela tela.
-- INSERT IGNORE: rodar de novo não desfaz versões nem modelos já personalizados.
INSERT IGNORE INTO `rm_historico_modelos` (`id`, `tipo`, `nome`, `situacao`, `versao_atual`, `usuario_cre`, `usuario_mod`, `created`, `modified`) VALUES
 (1,'PARCIAL','Histórico parcial — padrão',2,1,NULL,NULL,'2026-09-27 12:00:00',NULL),
 (2,'FINAL','Histórico final — padrão',2,1,NULL,NULL,'2026-09-27 12:00:00',NULL);
INSERT IGNORE INTO `rm_historico_modelo_versoes` (`id`, `modelo_id`, `versao`, `secoes`, `usuario_cre`, `created`) VALUES
 (1,1,1,'{"secoes":[{"ativa":true,"html":"<p style=\\"text-align: center\\"><b>{{INSTITUICAO_NOME}}</b></p><p style=\\"text-align: center\\">{{CURSO_NOME}}</p><p style=\\"text-align: center\\">Polo: {{POLO_NOME}}</p>","logo":true,"ordem":1,"tipo":"cabecalho"},{"ativa":true,"html":"<h2 style=\\"text-align: center\\">HISTÓRICO ESCOLAR PARCIAL</h2>","ordem":2,"tipo":"tipo_documento"},{"ativa":true,"html":"<table><tr><td style=\\"width: 60%\\"><b>Nome:</b> {{ALUNO_NOME}}</td><td><b>RA:</b> {{ALUNO_RA}}</td></tr><tr><td><b>Nascimento:</b> {{ALUNO_NASCIMENTO}} — {{ALUNO_NATURALIDADE}}</td><td><b>CPF:</b> {{ALUNO_CPF}}</td></tr></table>","ordem":3,"tipo":"aluno"},{"ativa":true,"html":"<p><b>Curso:</b> {{CURSO_NOME}} — <b>Modalidade:</b> {{MODALIDADE}} — <b>Polo:</b> {{POLO_NOME}}</p><p><b>Ingresso:</b> {{MATRICULA_INGRESSO_FORMA}}, em {{MATRICULA_INGRESSO_DATA}} — <b>Situação:</b> {{MATRICULA_SITUACAO}}</p>","ordem":4,"tipo":"matricula"},{"ativa":true,"html":"<p><b>Ato legal:</b> {{ATO_DESCRICAO}}</p>","ordem":5,"tipo":"ato"},{"ativa":true,"colunas":[{"alinhamento":"left","campo":"codigo","largura":"9%","ordem":1,"rotulo":"Código"},{"alinhamento":"left","campo":"disciplina","largura":"28%","ordem":2,"rotulo":"Disciplina"},{"alinhamento":"center","campo":"ch","largura":"6%","ordem":3,"rotulo":"CH"},{"alinhamento":"left","campo":"periodo","largura":"15%","ordem":4,"rotulo":"Período de realização"},{"alinhamento":"left","campo":"docente","largura":"18%","ordem":5,"rotulo":"Docente"},{"alinhamento":"center","campo":"nota","largura":"8%","ordem":6,"rotulo":"Nota final"},{"alinhamento":"center","campo":"frequencia","largura":"8%","ordem":7,"rotulo":"Frequência"},{"alinhamento":"center","campo":"situacao","largura":"8%","ordem":8,"rotulo":"Situação"}],"html":"","ordem":6,"tipo":"quadro"},{"ativa":true,"html":"<p><b>Trabalho de conclusão de curso:</b> {{TCC_TITULO}}</p><p>Orientação: {{TCC_ORIENTADOR}} ({{TCC_ORIENTADOR_TITULACAO}}) — Nota: {{TCC_NOTA}} — {{TCC_DATA}} — {{TCC_SITUACAO}}</p>","ordem":7,"somenteSeHouver":true,"tipo":"tcc"},{"ativa":true,"html":"<p><b>Carga horária:</b> exigida {{TOTAL_CH_EXIGIDA}} h; integralizada {{TOTAL_CH_INTEGRALIZADA}} h ({{TOTAL_APROVADAS}} de {{TOTAL_DISCIPLINAS}} disciplinas).</p><p><b>Período de realização:</b> {{PERIODO_INICIO}} a {{PERIODO_FIM}} — <b>duração total:</b> {{DURACAO_MESES}} meses.</p>","ordem":8,"tipo":"totais"},{"ativa":false,"html":"","ordem":9,"tipo":"observacoes"},{"ativa":true,"blocos":[{"cargo":"Secretaria acadêmica","imagem":null,"nome":""},{"cargo":"Direção","imagem":null,"nome":""}],"html":"<p style=\\"text-align: right\\">{{EMISSAO_DATA_EXTENSO}}</p>","ordem":10,"tipo":"assinaturas"},{"ativa":true,"html":"<p style=\\"font-size: 7pt\\">Emitido em {{EMISSAO_DATA}}. Código de verificação: <b>{{EMISSAO_CODIGO}}</b>. Confira a autenticidade em {{EMISSAO_URL_VERIFICACAO}}</p>","ordem":11,"paginacao":true,"qr":true,"tipo":"rodape"}]}',NULL,'2026-09-27 12:00:00'),
 (2,2,1,'{"secoes":[{"ativa":true,"html":"<p style=\\"text-align: center\\"><b>{{INSTITUICAO_NOME}}</b></p><p style=\\"text-align: center\\">{{CURSO_NOME}}</p><p style=\\"text-align: center\\">Polo: {{POLO_NOME}}</p>","logo":true,"ordem":1,"tipo":"cabecalho"},{"ativa":true,"html":"<h2 style=\\"text-align: center\\">HISTÓRICO ESCOLAR FINAL</h2>","ordem":2,"tipo":"tipo_documento"},{"ativa":true,"html":"<table><tr><td style=\\"width: 60%\\"><b>Nome:</b> {{ALUNO_NOME}}</td><td><b>RA:</b> {{ALUNO_RA}}</td></tr><tr><td><b>Nascimento:</b> {{ALUNO_NASCIMENTO}} — {{ALUNO_NATURALIDADE}}</td><td><b>CPF:</b> {{ALUNO_CPF}}</td></tr></table>","ordem":3,"tipo":"aluno"},{"ativa":true,"html":"<p><b>Curso:</b> {{CURSO_NOME}} — <b>Modalidade:</b> {{MODALIDADE}} — <b>Polo:</b> {{POLO_NOME}}</p><p><b>Ingresso:</b> {{MATRICULA_INGRESSO_FORMA}}, em {{MATRICULA_INGRESSO_DATA}} — <b>Situação:</b> {{MATRICULA_SITUACAO}}, em {{MATRICULA_CONCLUSAO}}</p>","ordem":4,"tipo":"matricula"},{"ativa":true,"html":"<p><b>Ato legal:</b> {{ATO_DESCRICAO}}</p>","ordem":5,"tipo":"ato"},{"ativa":true,"colunas":[{"alinhamento":"left","campo":"codigo","largura":"9%","ordem":1,"rotulo":"Código"},{"alinhamento":"left","campo":"disciplina","largura":"27%","ordem":2,"rotulo":"Disciplina"},{"alinhamento":"center","campo":"ch","largura":"6%","ordem":3,"rotulo":"CH"},{"alinhamento":"left","campo":"periodo","largura":"15%","ordem":4,"rotulo":"Período de realização"},{"alinhamento":"left","campo":"docente","largura":"17%","ordem":5,"rotulo":"Docente"},{"alinhamento":"left","campo":"titulacao","largura":"9%","ordem":6,"rotulo":"Titulação"},{"alinhamento":"center","campo":"nota","largura":"6%","ordem":7,"rotulo":"Nota final"},{"alinhamento":"center","campo":"frequencia","largura":"6%","ordem":8,"rotulo":"Frequência"},{"alinhamento":"center","campo":"situacao","largura":"5%","ordem":9,"rotulo":"Situação"}],"html":"","ordem":6,"tipo":"quadro"},{"ativa":true,"html":"<p><b>Trabalho de conclusão de curso:</b> {{TCC_TITULO}}</p><p>Orientação: {{TCC_ORIENTADOR}} ({{TCC_ORIENTADOR_TITULACAO}}) — Nota: {{TCC_NOTA}} — {{TCC_DATA}} — {{TCC_SITUACAO}}</p>","ordem":7,"somenteSeHouver":true,"tipo":"tcc"},{"ativa":true,"html":"<p><b>Carga horária:</b> exigida {{TOTAL_CH_EXIGIDA}} h; integralizada {{TOTAL_CH_INTEGRALIZADA}} h ({{TOTAL_APROVADAS}} de {{TOTAL_DISCIPLINAS}} disciplinas).</p><p><b>Período de realização:</b> {{PERIODO_INICIO}} a {{PERIODO_FIM}} — <b>duração total:</b> {{DURACAO_MESES}} meses.</p>","ordem":8,"tipo":"totais"},{"ativa":false,"html":"","ordem":9,"tipo":"observacoes"},{"ativa":true,"blocos":[{"cargo":"Secretaria acadêmica","imagem":null,"nome":""},{"cargo":"Direção","imagem":null,"nome":""}],"html":"<p style=\\"text-align: right\\">{{EMISSAO_DATA_EXTENSO}}</p>","ordem":10,"tipo":"assinaturas"},{"ativa":true,"html":"<p style=\\"font-size: 7pt\\">Emitido em {{EMISSAO_DATA}}. Código de verificação: <b>{{EMISSAO_CODIGO}}</b>. Confira a autenticidade em {{EMISSAO_URL_VERIFICACAO}}</p>","ordem":11,"paginacao":true,"qr":true,"tipo":"rodape"}]}',NULL,'2026-09-27 12:00:00');

-- E6c — CENTRAL DE AJUDA e NOVIDADES do painel: 403 ajuda/guia (um guia curto), 404 ajuda/imagem (imagem dos guias do perfil), 405 novidades/listar (menu Ajuda); administrador, secretaria e professor. A 384 passa a ser o índice (ajuda/index).
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (403,'Guia','Ajuda','guia','ajuda','guia','Guia de uso (tarefa)','Um guia curto por tarefa; o perfil precisa estar no guia (E6c).',2,NULL,5,1,1,'2026-09-27 12:00:00',NULL),
 (404,'Imagem de guia','Ajuda','imagem','ajuda','imagem','Imagem dos guias','Imagens dos guias do perfil (docs/ não responde pela web) (E6c).',2,NULL,5,1,1,'2026-09-27 12:00:00',NULL),
 (405,'Novidades','Novidades','listar','novidades','listar','Novidades','O que mudou em cada versão, para o perfil, com o guia (E6c).',2,'fas fa-bullhorn',1,1,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (3457,1,0,1,2,8,1,403,'2026-09-27 12:00:00',NULL),
 (3458,2,0,1,2,8,2,403,'2026-09-27 12:00:00',NULL),
 (3459,2,0,1,2,8,3,403,'2026-09-27 12:00:00',NULL),
 (3460,1,0,1,2,8,4,403,'2026-09-27 12:00:00',NULL),
 (3461,2,0,1,2,8,5,403,'2026-09-27 12:00:00',NULL),
 (3462,1,0,1,2,8,6,403,'2026-09-27 12:00:00',NULL),
 (3463,2,0,1,2,8,7,403,'2026-09-27 12:00:00',NULL),
 (3464,2,0,1,2,8,8,403,'2026-09-27 12:00:00',NULL),
 (3465,1,0,1,2,8,1,404,'2026-09-27 12:00:00',NULL),
 (3466,2,0,1,2,8,2,404,'2026-09-27 12:00:00',NULL),
 (3467,2,0,1,2,8,3,404,'2026-09-27 12:00:00',NULL),
 (3468,1,0,1,2,8,4,404,'2026-09-27 12:00:00',NULL),
 (3469,2,0,1,2,8,5,404,'2026-09-27 12:00:00',NULL),
 (3470,1,0,1,2,8,6,404,'2026-09-27 12:00:00',NULL),
 (3471,2,0,1,2,8,7,404,'2026-09-27 12:00:00',NULL),
 (3472,2,0,1,2,8,8,404,'2026-09-27 12:00:00',NULL),
 (3473,1,2,1,1,8,1,405,'2026-09-27 12:00:00',NULL),
 (3474,2,2,1,2,8,2,405,'2026-09-27 12:00:00',NULL),
 (3475,2,2,1,2,8,3,405,'2026-09-27 12:00:00',NULL),
 (3476,1,2,1,1,8,4,405,'2026-09-27 12:00:00',NULL),
 (3477,2,2,1,2,8,5,405,'2026-09-27 12:00:00',NULL),
 (3478,1,2,1,1,8,6,405,'2026-09-27 12:00:00',NULL),
 (3479,2,2,1,2,8,7,405,'2026-09-27 12:00:00',NULL),
 (3480,2,2,1,2,8,8,405,'2026-09-27 12:00:00',NULL);

-- E6c — CENTRAL DE AJUDA: a 384 (menu Ajuda) passa a abrir o ÍNDICE dos guias, dentro do sistema.
UPDATE `adms_paginas` SET `metodo` = 'index', `menu_metodo` = 'index', `menu_pagina` = 'Ajuda', `nome_pagina` = 'Central de ajuda',
  `obs` = 'Índice dos guias do perfil, com "Comece por aqui" (E6c; na E5 abria o guia longo em nova aba).', `modified` = `modified` WHERE `id` = 384;
-- E6c — portal: 14 home/ajuda vira o índice; 15 um guia; 16 imagem dos guias; 17 novidades (menu).
UPDATE `col_paginas` SET `nome_pagina` = 'Central de ajuda', `obs` = 'Índice dos guias do aluno, com "Comece por aqui" (E6c).', `modified` = `modified` WHERE `id` = 14;
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (15,'Guia','Home','guia','home','guia','Guia de uso (tarefa)','Um guia curto do aluno (E6c).',2,NULL,5,1,1,'2026-09-27 12:00:00',NULL),
 (16,'Imagem de guia','Home','guiaImagem','home','guia-imagem','Imagem dos guias','Imagens dos guias do aluno (E6c).',2,NULL,5,1,1,'2026-09-27 12:00:00',NULL),
 (17,'Novidades','Home','novidades','home','novidades','Novidades','O que mudou em cada versão, para o aluno, com o guia (E6c).',2,'fas fa-bullhorn',5,1,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `col_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (44,1,15,2,2,4,1,15,'2026-09-27 12:00:00',NULL),
 (45,1,16,2,2,4,1,16,'2026-09-27 12:00:00',NULL),
 (46,1,15,2,1,4,1,17,'2026-09-27 12:00:00',NULL);
-- E7 — NOVIDADES: 406 novidades/visto (POST do "Entendi"; D-218); administrador, secretaria e professor.
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (406,'Novidades vistas','Novidades','visto','novidades','visto','Marcar novidades como vistas','POST do Entendi / fechar o aviso (E7).',2,NULL,3,1,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (3493,1,0,1,2,8,1,406,'2026-09-27 12:00:00',NULL),
 (3494,2,0,1,2,8,2,406,'2026-09-27 12:00:00',NULL),
 (3495,2,0,1,2,8,3,406,'2026-09-27 12:00:00',NULL),
 (3496,1,0,1,2,8,4,406,'2026-09-27 12:00:00',NULL),
 (3497,2,0,1,2,8,5,406,'2026-09-27 12:00:00',NULL),
 (3498,1,0,1,2,8,6,406,'2026-09-27 12:00:00',NULL),
 (3499,2,0,1,2,8,7,406,'2026-09-27 12:00:00',NULL),
 (3500,2,0,1,2,8,8,406,'2026-09-27 12:00:00',NULL);

-- E7 — portal: página 18 home/novidades-visto
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (18,'Novidades vistas','Home','novidadesVisto','home','novidades-visto','Novidades vistas','POST do Entendi / fechar o aviso (E7).',2,NULL,5,1,1,'2026-09-28 12:00:00',NULL);
INSERT INTO `col_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (47,1,18,2,2,4,1,18,'2026-09-28 12:00:00',NULL);

-- E7 — portal: página 19 login/sonda
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (19,'Sonda da sessão','Login','sonda','login','sonda','Sonda da sessão','Plano B do iframe: a sessão se mantém? (E7, pública)',1,NULL,5,1,1,'2026-09-28 12:00:00',NULL);

-- E7 — TENTATIVAS DE ACESSO (limite de tentativas, D-73): 407 listar (menu Configurações) e 408 liberar (POST); só o administrador.
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (407,'Tentativas de acesso','TentativasAcesso','listar','tentativas-acesso','listar','Tentativas de acesso','Contagens e bloqueios do limite de tentativas; liberar (E7).',2,'fas fa-user-lock',1,1,1,'2026-09-27 12:00:00',NULL),
 (408,'Liberar tentativas','TentativasAcesso','liberar','tentativas-acesso','liberar','Liberar bloqueio de tentativas','POST (E7).',2,NULL,3,1,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (3501,1,286,1,1,4,1,407,'2026-09-27 12:00:00',NULL),
 (3502,2,286,1,2,4,2,407,'2026-09-27 12:00:00',NULL),
 (3503,2,286,1,2,4,3,407,'2026-09-27 12:00:00',NULL),
 (3504,2,286,1,2,4,4,407,'2026-09-27 12:00:00',NULL),
 (3505,2,286,1,2,4,5,407,'2026-09-27 12:00:00',NULL),
 (3506,2,286,1,2,4,6,407,'2026-09-27 12:00:00',NULL),
 (3507,2,286,1,2,4,7,407,'2026-09-27 12:00:00',NULL),
 (3508,2,286,1,2,4,8,407,'2026-09-27 12:00:00',NULL),
 (3509,1,0,1,2,4,1,408,'2026-09-27 12:00:00',NULL),
 (3510,2,0,1,2,4,2,408,'2026-09-27 12:00:00',NULL),
 (3511,2,0,1,2,4,3,408,'2026-09-27 12:00:00',NULL),
 (3512,2,0,1,2,4,4,408,'2026-09-27 12:00:00',NULL),
 (3513,2,0,1,2,4,5,408,'2026-09-27 12:00:00',NULL),
 (3514,2,0,1,2,4,6,408,'2026-09-27 12:00:00',NULL),
 (3515,2,0,1,2,4,7,408,'2026-09-27 12:00:00',NULL),
 (3516,2,0,1,2,4,8,408,'2026-09-27 12:00:00',NULL);

-- E7 — LINK DE SENHA COM O TOKEN NO FRAGMENTO (D-70): 409 login/definir-senha, pública (lê #t=… por JavaScript e envia por POST).
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (409,'Definir senha','Login','definirSenha','login','definir-senha','Definir senha pelo link','Pública. O token vem no fragmento (#t=), fora do access log (E7).',1,NULL,7,1,1,'2026-09-27 12:00:00',NULL);
INSERT INTO `adms_nivacs_pgs` (`id`, `permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`) VALUES
 (3517,2,0,1,2,8,1,409,'2026-09-27 12:00:00',NULL),
 (3518,2,0,1,2,8,2,409,'2026-09-27 12:00:00',NULL),
 (3519,2,0,1,2,8,3,409,'2026-09-27 12:00:00',NULL),
 (3520,2,0,1,2,8,4,409,'2026-09-27 12:00:00',NULL),
 (3521,2,0,1,2,8,5,409,'2026-09-27 12:00:00',NULL),
 (3522,2,0,1,2,8,6,409,'2026-09-27 12:00:00',NULL),
 (3523,2,0,1,2,8,7,409,'2026-09-27 12:00:00',NULL),
 (3524,2,0,1,2,8,8,409,'2026-09-27 12:00:00',NULL);

-- E7 — portal: página 20 login/definir-senha
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`) VALUES
 (20,'Definir senha','Login','definirSenha','login','definir-senha','Definir senha','Pública. O token do link vem no fragmento (#t=), fora do access log (E7).',1,NULL,5,1,1,'2026-09-28 12:00:00',NULL);

-- =============================================================================
-- E8b (1.7.2) — MENU: rótulos únicos por perfil e itens no grupo certo (D-254, D-255).
-- Numa base EXISTENTE (1.7.1): database/atualizacoes/1.7.2_menu.sql (o mesmo bloco).
-- Só UPDATEs idempotentes (rodar duas vezes não muda nada); `modified` é preservado.
-- =============================================================================

-- Painel: o grupo "Usuários" tinha um item "Usuários" (rótulo repetido) → o grupo passa a "Equipe".
UPDATE `adms_menus` SET `nome` = 'Equipe', `modified` = `modified` WHERE `id` = 2;
-- Painel: a lista de cursos aparecia como "Ensino" dentro do grupo Ensino → "Cursos".
UPDATE `adms_paginas` SET `menu_pagina` = 'Cursos', `modified` = `modified` WHERE `id` = 75;
-- Painel: "Ajuda" era item e grupo ao mesmo tempo → grupo Ajuda com "Central de ajuda" e "Novidades".
UPDATE `adms_paginas` SET `menu_pagina` = 'Central de ajuda', `modified` = `modified` WHERE `id` = 384;
UPDATE `adms_nivacs_pgs` SET `dropdown` = 1, `modified` = `modified` WHERE `adms_pagina_id` = 384;
-- Painel: "Instituição" estava com dropdown 2 no meio do grupo Configurações (quebrava o menu) → 1.
UPDATE `adms_nivacs_pgs` SET `dropdown` = 1, `modified` = `modified` WHERE `adms_pagina_id` = 364;
-- Painel, secretaria: "Todas as Disciplinas" estava no grupo Configurações → Ensino.
UPDATE `adms_nivacs_pgs` SET `adms_menu_id` = 7, `ordem` = 90, `dropdown` = 1, `modified` = `modified` WHERE `adms_pagina_id` = 91 AND `adms_niveis_acesso_id` = 4;
UPDATE `adms_nivacs_pgs` SET `dropdown` = 1, `modified` = `modified` WHERE `adms_pagina_id` = 80 AND `adms_niveis_acesso_id` = 4;
-- Painel: "Anos Letivos" no grupo Ensino, junto de Módulos e Matriz (secretaria: saía sozinha em Configurações;
-- administrador: tinha acesso e atalho na Página Inicial, mas não estava no menu).
UPDATE `adms_nivacs_pgs` SET `adms_menu_id` = 7, `ordem` = 311, `modified` = `modified` WHERE `adms_pagina_id` = 305 AND `adms_niveis_acesso_id` = 4;
UPDATE `adms_nivacs_pgs` SET `adms_menu_id` = 7, `ordem` = 126, `dropdown` = 1, `lib_menu` = 1, `modified` = `modified` WHERE `adms_pagina_id` = 305 AND `adms_niveis_acesso_id` = 1 AND `permissao` = 1;
-- Portal: o grupo "Atendimento" (Ajuda e Novidades soltas) passa a "Ajuda", com "Central de ajuda" e "Novidades".
UPDATE `col_menus` SET `nome` = 'Ajuda', `icone` = 'fas fa-question-circle', `modified` = `modified` WHERE `id` = 4;
UPDATE `col_paginas` SET `menu_pagina` = 'Central de ajuda', `modified` = `modified` WHERE `id` = 14;
UPDATE `col_nivacs_pgs` SET `dropdown` = 1, `modified` = `modified` WHERE `adms_pagina_id` IN (14, 17) AND `adms_menu_id` = 4;

-- >>> E8c 1.8.0 (gerado de database/atualizacoes/1.8.0_usabilidade.sql)
-- =============================================================================
-- E8c (1.8.0) — USABILIDADE E PARIDADE COM O SISTEMA DE ORIGEM. Idempotente: rodar duas vezes não muda nada.
-- Numa base EXISTENTE (1.7.2): mysql -u USUARIO -p <banco> < database/atualizacoes/1.8.0_usabilidade.sql
-- O mesmo bloco está no fim de database/conteudo.sql (instalação limpa). `modified` é preservado nos UPDATEs.
-- =============================================================================

-- ---------------------------------------------------------------- Bloco 2 — menu (D-260 a D-263)

-- D-260: nível novo "Suporte técnico" (uso da VCOSTA), com as telas de manutenção herdadas do sistema de origem.
INSERT INTO `adms_niveis_acessos` (`id`, `nome`, `ordem`, `situacao`, `usuario_cre`, `usuario_mod`, `created`, `modified`)
SELECT 9, 'Suporte técnico', 9, 1, 1, NULL, '2026-09-28 12:00:00', NULL FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_niveis_acessos` WHERE `id` = 9);
INSERT INTO `adms_menus` (`id`, `nome`, `icone`, `ordem`, `adms_sit_id`, `created`, `modified`)
SELECT 9, 'Suporte técnico', 'fas fa-screwdriver-wrench', 6, 1, '2026-09-28 12:00:00', NULL FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_menus` WHERE `id` = 9);
UPDATE `adms_menus` SET `ordem` = 7, `modified` = `modified` WHERE `id` = 8;   -- Ajuda
UPDATE `adms_menus` SET `ordem` = 8, `modified` = `modified` WHERE `id` = 3;   -- Sair
-- Permissões do nível 9: uma linha por página (cópia da estrutura do nível 1). Liberadas só as telas de manutenção
-- (Páginas, Itens de Menu, Empresas, Acesso Acadêmico, Páginas Educacional), a Página Inicial, o login e o perfil.
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`)
SELECT CASE WHEN p.`controller` IN ('Paginas', 'Menu', 'Empresas', 'AcessoAcademico', 'PaginasAcademico', 'Home', 'Login', 'Perfil') THEN 1 ELSE 2 END,
       n.`ordem`, n.`dropdown`,
       CASE WHEN n.`adms_pagina_id` IN (2, 3, 4, 18, 40, 51, 63) THEN 1 ELSE 2 END,
       CASE WHEN n.`adms_pagina_id` IN (4, 18, 40, 51, 63) THEN 9 ELSE n.`adms_menu_id` END,
       9, n.`adms_pagina_id`, '2026-09-28 12:00:00', NULL
  FROM `adms_nivacs_pgs` n INNER JOIN `adms_paginas` p ON p.`id` = n.`adms_pagina_id`
 WHERE n.`adms_niveis_acesso_id` = 1
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_pagina_id` FROM `adms_nivacs_pgs` WHERE `adms_niveis_acesso_id` = 9) x WHERE x.`adms_pagina_id` = n.`adms_pagina_id`)
 ORDER BY n.`adms_pagina_id`;
-- O administrador da instituição deixa de ver (menu e acesso) as telas de manutenção. "Empresas" edita a mesma
-- tabela (adms_empresas) em que "Dados da instituição" guarda a instituição: duplicada, fica só no suporte.
UPDATE `adms_nivacs_pgs` n INNER JOIN `adms_paginas` p ON p.`id` = n.`adms_pagina_id`
   SET n.`permissao` = 2, n.`lib_menu` = 2, n.`modified` = n.`modified`
 WHERE n.`adms_niveis_acesso_id` = 1 AND p.`controller` IN ('Paginas', 'Menu', 'Empresas', 'AcessoAcademico', 'PaginasAcademico');

-- D-261: "Usuários Acadêmico" passa a "Alunos".
UPDATE `adms_paginas` SET `menu_pagina` = 'Alunos', `nome_pagina` = 'Alunos', `modified` = `modified` WHERE `id` = 46;

-- D-263: um ícone único por item de menu (sem repetir o do grupo nem o de outro item do mesmo perfil);
-- Ajuda com o mesmo ícone (?) em todo lugar: o grupo Ajuda tem o "?", a Central de ajuda um livro (sem dois "?" seguidos, A20).
UPDATE `adms_paginas` SET `icone` = 'fas fa-book-open', `modified` = `modified` WHERE `id` = 91;          -- Todas as Disciplinas (era o prédio do grupo Ensino)
UPDATE `adms_paginas` SET `icone` = 'fas fa-building', `modified` = `modified` WHERE `id` = 385;          -- Dados da instituição (era o prédio do grupo Ensino)
UPDATE `adms_paginas` SET `icone` = 'fas fa-address-book', `modified` = `modified` WHERE `id` = 46;       -- Alunos (era o mesmo de Usuários)
UPDATE `adms_paginas` SET `icone` = 'fas fa-shield-halved', `modified` = `modified` WHERE `id` = 407;     -- Tentativas de acesso (era o mesmo de Acesso Acadêmico)
UPDATE `adms_paginas` SET `icone` = 'fas fa-book', `modified` = `modified` WHERE `id` = 384;              -- Central de ajuda
UPDATE `adms_paginas` SET `icone` = 'fas fa-bars', `modified` = `modified` WHERE `id` = 18;               -- Itens de Menu
UPDATE `adms_paginas` SET `icone` = 'fas fa-city', `modified` = `modified` WHERE `id` = 40;               -- Empresas
UPDATE `col_paginas` SET `icone` = 'fas fa-book', `modified` = `modified` WHERE `id` = 14;                -- portal: Central de ajuda
UPDATE `col_menus` SET `icone` = 'fas fa-graduation-cap', `modified` = `modified` WHERE `id` = 2;         -- portal: grupo Acadêmico
UPDATE `adms_paginas` SET `menu_pagina` = 'E-mail (SMTP)', `modified` = `modified` WHERE `id` = 29;          -- era "Config.  E-mail"

-- ---------------------------------------------------------------- Bloco 3 — disciplina (D-264, D-265, A12)
-- rm_faltas_compensacoes — E8c (D-264, D-265): cada COMPENSAÇÃO de faltas lançada pela secretaria ou pelo
-- administrador, com tipo, justificativa obrigatória, período, quem lançou e quando. O número em
-- rm_disciplina_faltas.faltas_compensadas é a soma destas linhas (aluno × oferta). Sem anexo (dado de saúde,
-- fica para depois, com LGPD). O aluno vê só a quantidade; o motivo nunca sai no portal.
CREATE TABLE IF NOT EXISTS `rm_faltas_compensacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rm_prof_mat_id` int(11) NOT NULL,
  `rm` varchar(255) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `tipo` varchar(20) NOT NULL COMMENT 'atestado | licenca | convocacao | outro',
  `justificativa` text NOT NULL,
  `inicio` date NOT NULL,
  `fim` date NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `oferta_rm` (`rm_prof_mat_id`, `rm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
;

-- Rotas: 410 faltas/compensar e 411 faltas/remover-compensacao (POST; só secretaria e administrador, D-264);
-- 412 medias/atualizar-lista (POST; quem vê notas e resultado: administrador, secretaria, professor).
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT * FROM (SELECT 410 a, 'Compensar faltas' b, 'Faltas' c, 'compensar' d, 'faltas' e, 'compensar' f, 'Compensar faltas (justificativa obrigatória)' g, 'POST. Só secretaria e administrador (E8c, D-264).' h, 2 i, NULL j, 3 k, 1 l, 1 m, '2026-09-28 12:00:00' n, NULL o) t WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 410);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT * FROM (SELECT 411 a, 'Remover compensação' b, 'Faltas' c, 'removerCompensacao' d, 'faltas' e, 'remover-compensacao' f, 'Remover um lançamento de compensação' g, 'POST. Só secretaria e administrador (E8c, D-264).' h, 2 i, NULL j, 3 k, 1 l, 1 m, '2026-09-28 12:00:00' n, NULL o) t WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 411);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT * FROM (SELECT 412 a, 'Atualizar lista' b, 'Medias' c, 'atualizarLista' d, 'medias' e, 'atualizar-lista' f, 'Atualizar a lista de alunos da disciplina' g, 'POST. Cria as linhas de nota que faltam para aluno enturmado depois (E8c, A12, resolve a D-133).' h, 2 i, NULL j, 3 k, 1 l, 1 m, '2026-09-28 12:00:00' n, NULL o) t WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 412);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`)
SELECT CASE WHEN (p.id IN (410, 411) AND n.id IN (1, 4)) OR (p.id = 412 AND n.id IN (1, 4, 6)) THEN 1 ELSE 2 END, 0, 2, 2, 4, n.id, p.id, '2026-09-28 12:00:00', NULL
  FROM `adms_niveis_acessos` n CROSS JOIN (SELECT 410 id UNION ALL SELECT 411 UNION ALL SELECT 412) p
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.id AND x.`adms_pagina_id` = p.id)
 ORDER BY p.id, n.id;

-- Complemento do cliente (D-268): parâmetro "Organizar a matriz em módulos". Padrão 0 (Não): sem módulo em telas e documentos.
INSERT INTO `adms_parametros` (`chave`, `valor`, `descricao`, `grupo`, `editavel`, `usuario_mod`, `created`, `modified`)
SELECT 'matriz.modulos', '0', 'Organizar a matriz em módulos (1 = sim, 0 = não). Com 0, cada grade tem um módulo interno, nunca exibido: curso, grade e a lista de disciplinas. E8c, D-268.', 'Matriz curricular', 1, NULL, '2026-09-28 12:00:00', NULL
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_parametros` WHERE `chave` = 'matriz.modulos');
-- Toda grade sem módulo ganha o módulo INTERNO "Disciplinas" (ordem 1, ativo), para a lista única de disciplinas funcionar.
INSERT INTO `rm_cursos_turma_series` (`curso_id`, `grade_id`, `periodo`, `codnome`, `descricao`, `ordem`, `observacao`, `situacao`, `usuario_cre`, `created`)
SELECT g.`curso_id`, g.`id`, 1, 'Disciplinas', 'Módulo interno da grade (a matriz não é organizada em módulos).', 1, NULL, 1, 0, NOW()
  FROM `rm_cursos_grades` g WHERE NOT EXISTS (SELECT 1 FROM `rm_cursos_turma_series` m WHERE m.`grade_id` = g.`id`);

-- Versão 1.8.0 (a 1.7.2 passa a anterior) e as novidades 49 a 60, escritas para o usuário, por perfil, com o guia.
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.8.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 11, '1.8.0', 1, 8, 0, '2026-09-28', 'Usabilidade: telas da disciplina com barra única, listas com exportar e imprimir, compensação com justificativa, boletim e verificação novos, e o curso sem módulo (grade e lista de disciplinas).', 1, '2026-09-28 12:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.8.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.8.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT n.* FROM (
  SELECT 49 id, 11 v, 'Curso sem módulo' t, 'A matriz agora é a grade com a lista de disciplinas: sem módulo para cadastrar nem escolher. A oferta do ano, o vínculo, o boletim e o histórico seguem a mesma lista.' x, 1 o, 'administrador,secretaria' p, 'montar-a-matriz' g, '2026-09-28 12:00:00' c
  UNION ALL SELECT 50, 11, 'Mesma barra em toda tela da disciplina', 'Atividades, Notas e resultado, Faltas e Encerramento têm a mesma barra no topo: um clique leva de uma para a outra.', 2, 'administrador,secretaria,professor', 'acompanhar-notas-e-resultado', '2026-09-28 12:00:00'
  UNION ALL SELECT 51, 11, 'Faltas compensadas com justificativa', 'Atestado, licença ou convocação: a secretaria lança a compensação com o tipo, a justificativa e o período, e fica registrado quem lançou e quando.', 3, 'secretaria,administrador', 'compensar-faltas', '2026-09-28 12:00:00'
  UNION ALL SELECT 52, 11, 'Compensação é com a secretaria', 'As faltas compensadas agora são lançadas pela secretaria, com justificativa. Você vê o total na coluna Compensadas, e a frequência já sai com o abono.', 4, 'professor', 'lancar-faltas', '2026-09-28 12:00:00'
  UNION ALL SELECT 53, 11, 'Atalhos em Minhas Disciplinas', 'Cada disciplina da lista tem atalhos para Atividades, Notas e resultado e Faltas.', 5, 'professor', 'minhas-disciplinas-e-atividades', '2026-09-28 12:00:00'
  UNION ALL SELECT 54, 11, 'Listas com Excel, PDF e Imprimir', 'Toda lista do painel tem pesquisa, ordenação e os botões Excel, CSV, PDF e Imprimir.', 6, 'administrador,secretaria,professor', 'exportar-e-imprimir-listas', '2026-09-28 12:00:00'
  UNION ALL SELECT 55, 11, 'Botão apagado diz o porquê', 'Quando uma ação não pode ser feita, o botão aparece apagado e a dica explica o motivo, em vez de dar erro depois do clique.', 7, 'administrador,secretaria,professor', 'exportar-e-imprimir-listas', '2026-09-28 12:00:00'
  UNION ALL SELECT 56, 11, 'Boletim novo', 'Faixa com os dados do aluno, tabela mais compacta, o que está abaixo do mínimo em vermelho, filtro de curso e ano, Imprimir e PDF.', 8, 'secretaria,administrador', 'ver-e-imprimir-o-boletim', '2026-09-28 12:00:00'
  UNION ALL SELECT 57, 11, 'Boletim para imprimir', 'O boletim ganhou o botão Imprimir e os filtros de curso e ano. As faltas compensadas aparecem como número.', 9, 'aluno', 'ler-o-boletim', '2026-09-28 12:00:00'
  UNION ALL SELECT 58, 11, 'Prévia do histórico com PDF', 'Na prévia do histórico: Prévia em PDF (parcial ou final), Imprimir, Ir para a ficha do aluno e Emitir histórico, sem sair da tela.', 10, 'secretaria,administrador', 'previa-do-historico', '2026-09-28 12:00:00'
  UNION ALL SELECT 59, 11, 'Verificação do documento mais clara', 'A página pública mostra o resultado em destaque, com a instituição no topo, o botão Copiar do código de integridade e o comprovante para imprimir.', 11, 'secretaria,administrador,aluno', 'conferir-um-documento', '2026-09-28 12:00:00'
  UNION ALL SELECT 60, 11, 'Justificativa na situação da grade', 'Reabrir uma grade Finalizada ou Desativada pede justificativa; as outras trocas, não. Tudo fica no Registro de alterações da grade.', 12, 'administrador', 'situacao-da-grade', '2026-09-28 12:00:00'
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` a WHERE a.`id` = n.id);
-- <<< E8c 1.8.0

-- >>> E8d 1.9.0 (gerado de database/atualizacoes/1.9.0_decisoes_e_editor.sql)
-- VCOSTAEDU Escola 1 — atualização 1.8.0 → 1.9.0 (E8d). IDEMPOTENTE: pode rodar mais de uma vez.
-- Uso: mysql -u USUARIO -p <banco> < database/atualizacoes/1.9.0_decisoes_e_editor.sql
-- Depois, na base existente: php database/ferramentas/converter_modelos_190.php (converte os modelos de histórico
-- para o editor novo como NOVA VERSÃO; as emissões feitas não mudam).
-- O conteudo.sql traz este mesmo bloco no fim (instalação limpa = base 1.8.0 + esta atualização).

SET NAMES utf8mb4;

-- P-1 (E8d): os níveis da VCOSTA (7 Supervisor VCOSTA, 8 VCOSTA, 9 Suporte técnico) só fazem MANUTENÇÃO.
-- Perdem toda rota que não seja de manutenção, entrada, perfil ou ajuda (o código também recusa: AdmsNivel).
UPDATE `adms_nivacs_pgs` n JOIN `adms_paginas` p ON p.`id` = n.`adms_pagina_id`
   SET n.`permissao` = 2, n.`lib_menu` = 2
 WHERE n.`adms_niveis_acesso_id` IN (7, 8, 9)
   AND p.`controller` NOT IN ('Login', 'Home', 'Perfil', 'Ajuda', 'Novidades', 'Paginas', 'Menu', 'AcessoAcademico', 'PaginasAcademico')
   AND p.`lib_pub` <> 1;

-- P-11 (E8d): a TELA "Empresas" sai (rotas, menu, permissões). A tabela adms_empresas FICA: "Dados da instituição" a usa.
DELETE FROM `adms_nivacs_pgs` WHERE `adms_pagina_id` IN (SELECT `id` FROM (SELECT `id` FROM `adms_paginas` WHERE `controller` = 'Empresas') x);
DELETE FROM `adms_paginas` WHERE `controller` = 'Empresas';

-- P-8 (E8d): INATIVAR/REATIVAR curso e matéria. Rotas 413 cursos/situacao (administrador) e 414 materias/situacao
-- (administrador e secretaria), só POST. Inativo sai das listas de escolha; o passado não muda.
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT * FROM (SELECT 413 a, 'Inativar/reativar curso' b, 'Cursos' c, 'situacao' d, 'cursos' e, 'situacao' f, 'Inativar ou reativar o curso' g, 'POST. Inativo sai das listas de escolha (E8d, P-8).' h, 2 i, NULL j, 3 k, 1 l, 1 m, '2026-09-29 12:00:00' n, NULL o) t WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 413);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT * FROM (SELECT 414 a, 'Inativar/reativar matéria' b, 'Materias' c, 'situacao' d, 'materias' e, 'situacao' f, 'Inativar ou reativar a matéria' g, 'POST. Inativa sai das listas de escolha (E8d, P-8).' h, 2 i, NULL j, 3 k, 1 l, 1 m, '2026-09-29 12:00:00' n, NULL o) t WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 414);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`)
SELECT CASE WHEN (p.id = 413 AND n.id = 1) OR (p.id = 414 AND n.id IN (1, 4)) THEN 1 ELSE 2 END, 0, 2, 2, 4, n.id, p.id, '2026-09-29 12:00:00', NULL
  FROM `adms_niveis_acessos` n CROSS JOIN (SELECT 413 id UNION ALL SELECT 414) p
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.id AND x.`adms_pagina_id` = p.id)
 ORDER BY p.id, n.id;

-- P-9 (E8d): o módulo interno das grades (D-268) passa a se chamar "Geral". Com "Organizar a matriz em módulos" em Sim,
-- ele aparece com esse nome, editável e reorganizável; com Não, continua sem aparecer. Não cria nem apaga nada.
UPDATE `rm_cursos_turma_series` SET `codnome` = 'Geral', `descricao` = 'Módulo interno da grade (a matriz não é organizada em módulos). Com módulos, é o módulo Geral.'
 WHERE `codnome` = 'Disciplinas' AND `descricao` = 'Módulo interno da grade (a matriz não é organizada em módulos).';

-- A24 (E8d): EDITOR DE MODELOS. Os modelos de histórico passam ao formato do editor novo (um documento só, com
-- blocos). Aqui, só os modelos PADRÃO nunca editados (v1 igual ao padrão antigo) ganham a v2 convertida, com o mesmo
-- resultado. Modelo já editado: rode depois  php database/ferramentas/converter_modelos_190.php  (nova versão; as
-- versões antigas e as emissões não mudam). Pode rodar de novo: nada é criado duas vezes.
-- modelo padrão PARCIAL: só se a versão atual for a v1 ainda IGUAL ao padrão antigo (nunca editado)
INSERT INTO rm_historico_modelo_versoes (modelo_id, versao, secoes, usuario_cre, created)
SELECT m.id, 2, '{\"assinaturas\":{\"blocos\":[{\"cargo\":\"Secretaria acadêmica\",\"imagem\":null,\"nome\":\"\"},{\"cargo\":\"Direção\",\"imagem\":null,\"nome\":\"\"}]},\"documento\":\"<table class=\\\"cab\\\"><tr><td class=\\\"cab-logo\\\">{{LOGO_INSTITUICAO}}</td><td class=\\\"cab-texto\\\"><p style=\\\"text-align: center\\\"><b>{{INSTITUICAO_NOME}}</b></p><p style=\\\"text-align: center\\\">{{CURSO_NOME}}</p><p style=\\\"text-align: center\\\">Polo: {{POLO_NOME}}</p></td></tr></table><div class=\\\"secao s-tipo_documento\\\"><h2 style=\\\"text-align: center\\\">HISTÓRICO ESCOLAR PARCIAL</h2></div><div class=\\\"secao s-aluno\\\"><table><tr><td style=\\\"width: 60%\\\"><b>Nome:</b> {{ALUNO_NOME}}</td><td><b>RA:</b> {{ALUNO_RA}}</td></tr><tr><td><b>Nascimento:</b> {{ALUNO_NASCIMENTO}} — {{ALUNO_NATURALIDADE}}</td><td><b>CPF:</b> {{ALUNO_CPF}}</td></tr></table></div><div class=\\\"secao s-matricula\\\"><p><b>Curso:</b> {{CURSO_NOME}} — <b>Modalidade:</b> {{MODALIDADE}} — <b>Polo:</b> {{POLO_NOME}}</p><p><b>Ingresso:</b> {{MATRICULA_INGRESSO_FORMA}}, em {{MATRICULA_INGRESSO_DATA}} — <b>Situação:</b> {{MATRICULA_SITUACAO}}</p></div><div class=\\\"secao s-ato\\\"><p><b>Ato legal:</b> {{ATO_DESCRICAO}}</p></div><div class=\\\"secao s-quadro\\\"><p>{{QUADRO_DISCIPLINAS}}</p></div><p>{{SE_TCC}}</p><div class=\\\"secao s-tcc\\\"><p><b>Trabalho de conclusão de curso:</b> {{TCC_TITULO}}</p><p>Orientação: {{TCC_ORIENTADOR}} ({{TCC_ORIENTADOR_TITULACAO}}) — Nota: {{TCC_NOTA}} — {{TCC_DATA}} — {{TCC_SITUACAO}}</p></div><p>{{FIM_SE_TCC}}</p><div class=\\\"secao s-totais\\\"><p><b>Carga horária:</b> exigida {{TOTAL_CH_EXIGIDA}} h; integralizada {{TOTAL_CH_INTEGRALIZADA}} h ({{TOTAL_APROVADAS}} de {{TOTAL_DISCIPLINAS}} disciplinas).</p><p><b>Período de realização:</b> {{PERIODO_INICIO}} a {{PERIODO_FIM}} — <b>duração total:</b> {{DURACAO_MESES}} meses.</p></div><div class=\\\"secao s-assinaturas\\\"><p style=\\\"text-align: right\\\">{{EMISSAO_DATA_EXTENSO}}</p><p>{{ASSINATURAS}}</p></div><table class=\\\"rodape\\\"><tr><td><p style=\\\"font-size: 7pt\\\">Emitido em {{EMISSAO_DATA}}. Código de verificação: <b>{{EMISSAO_CODIGO}}</b>. Confira a autenticidade em {{EMISSAO_URL_VERIFICACAO}}</p></td><td class=\\\"qr\\\">{{QR_CODE}}</td></tr></table>\",\"formato\":2,\"opcoes\":{\"paginacao\":true},\"quadro\":{\"atividades\":true,\"colunas\":[{\"alinhamento\":\"left\",\"campo\":\"codigo\",\"largura\":\"9%\",\"ordem\":1,\"rotulo\":\"Código\"},{\"alinhamento\":\"left\",\"campo\":\"disciplina\",\"largura\":\"28%\",\"ordem\":2,\"rotulo\":\"Disciplina\"},{\"alinhamento\":\"center\",\"campo\":\"ch\",\"largura\":\"6%\",\"ordem\":3,\"rotulo\":\"CH\"},{\"alinhamento\":\"left\",\"campo\":\"periodo\",\"largura\":\"15%\",\"ordem\":4,\"rotulo\":\"Período de realização\"},{\"alinhamento\":\"left\",\"campo\":\"docente\",\"largura\":\"18%\",\"ordem\":5,\"rotulo\":\"Docente\"},{\"alinhamento\":\"center\",\"campo\":\"nota\",\"largura\":\"8%\",\"ordem\":6,\"rotulo\":\"Nota final\"},{\"alinhamento\":\"center\",\"campo\":\"frequencia\",\"largura\":\"8%\",\"ordem\":7,\"rotulo\":\"Frequência\"},{\"alinhamento\":\"center\",\"campo\":\"situacao\",\"largura\":\"8%\",\"ordem\":8,\"rotulo\":\"Situação\"}],\"emCurso\":\"Em curso\"}}', NULL, NOW()
  FROM rm_historico_modelos m JOIN rm_historico_modelo_versoes v ON v.modelo_id = m.id AND v.versao = 1
 WHERE m.tipo = 'PARCIAL' AND m.versao_atual = 1 AND v.secoes = '{\"secoes\":[{\"ativa\":true,\"html\":\"<p style=\\\"text-align: center\\\"><b>{{INSTITUICAO_NOME}}</b></p><p style=\\\"text-align: center\\\">{{CURSO_NOME}}</p><p style=\\\"text-align: center\\\">Polo: {{POLO_NOME}}</p>\",\"logo\":true,\"ordem\":1,\"tipo\":\"cabecalho\"},{\"ativa\":true,\"html\":\"<h2 style=\\\"text-align: center\\\">HISTÓRICO ESCOLAR PARCIAL</h2>\",\"ordem\":2,\"tipo\":\"tipo_documento\"},{\"ativa\":true,\"html\":\"<table><tr><td style=\\\"width: 60%\\\"><b>Nome:</b> {{ALUNO_NOME}}</td><td><b>RA:</b> {{ALUNO_RA}}</td></tr><tr><td><b>Nascimento:</b> {{ALUNO_NASCIMENTO}} — {{ALUNO_NATURALIDADE}}</td><td><b>CPF:</b> {{ALUNO_CPF}}</td></tr></table>\",\"ordem\":3,\"tipo\":\"aluno\"},{\"ativa\":true,\"html\":\"<p><b>Curso:</b> {{CURSO_NOME}} — <b>Modalidade:</b> {{MODALIDADE}} — <b>Polo:</b> {{POLO_NOME}}</p><p><b>Ingresso:</b> {{MATRICULA_INGRESSO_FORMA}}, em {{MATRICULA_INGRESSO_DATA}} — <b>Situação:</b> {{MATRICULA_SITUACAO}}</p>\",\"ordem\":4,\"tipo\":\"matricula\"},{\"ativa\":true,\"html\":\"<p><b>Ato legal:</b> {{ATO_DESCRICAO}}</p>\",\"ordem\":5,\"tipo\":\"ato\"},{\"ativa\":true,\"colunas\":[{\"alinhamento\":\"left\",\"campo\":\"codigo\",\"largura\":\"9%\",\"ordem\":1,\"rotulo\":\"Código\"},{\"alinhamento\":\"left\",\"campo\":\"disciplina\",\"largura\":\"28%\",\"ordem\":2,\"rotulo\":\"Disciplina\"},{\"alinhamento\":\"center\",\"campo\":\"ch\",\"largura\":\"6%\",\"ordem\":3,\"rotulo\":\"CH\"},{\"alinhamento\":\"left\",\"campo\":\"periodo\",\"largura\":\"15%\",\"ordem\":4,\"rotulo\":\"Período de realização\"},{\"alinhamento\":\"left\",\"campo\":\"docente\",\"largura\":\"18%\",\"ordem\":5,\"rotulo\":\"Docente\"},{\"alinhamento\":\"center\",\"campo\":\"nota\",\"largura\":\"8%\",\"ordem\":6,\"rotulo\":\"Nota final\"},{\"alinhamento\":\"center\",\"campo\":\"frequencia\",\"largura\":\"8%\",\"ordem\":7,\"rotulo\":\"Frequência\"},{\"alinhamento\":\"center\",\"campo\":\"situacao\",\"largura\":\"8%\",\"ordem\":8,\"rotulo\":\"Situação\"}],\"html\":\"\",\"ordem\":6,\"tipo\":\"quadro\"},{\"ativa\":true,\"html\":\"<p><b>Trabalho de conclusão de curso:</b> {{TCC_TITULO}}</p><p>Orientação: {{TCC_ORIENTADOR}} ({{TCC_ORIENTADOR_TITULACAO}}) — Nota: {{TCC_NOTA}} — {{TCC_DATA}} — {{TCC_SITUACAO}}</p>\",\"ordem\":7,\"somenteSeHouver\":true,\"tipo\":\"tcc\"},{\"ativa\":true,\"html\":\"<p><b>Carga horária:</b> exigida {{TOTAL_CH_EXIGIDA}} h; integralizada {{TOTAL_CH_INTEGRALIZADA}} h ({{TOTAL_APROVADAS}} de {{TOTAL_DISCIPLINAS}} disciplinas).</p><p><b>Período de realização:</b> {{PERIODO_INICIO}} a {{PERIODO_FIM}} — <b>duração total:</b> {{DURACAO_MESES}} meses.</p>\",\"ordem\":8,\"tipo\":\"totais\"},{\"ativa\":false,\"html\":\"\",\"ordem\":9,\"tipo\":\"observacoes\"},{\"ativa\":true,\"blocos\":[{\"cargo\":\"Secretaria acadêmica\",\"imagem\":null,\"nome\":\"\"},{\"cargo\":\"Direção\",\"imagem\":null,\"nome\":\"\"}],\"html\":\"<p style=\\\"text-align: right\\\">{{EMISSAO_DATA_EXTENSO}}</p>\",\"ordem\":10,\"tipo\":\"assinaturas\"},{\"ativa\":true,\"html\":\"<p style=\\\"font-size: 7pt\\\">Emitido em {{EMISSAO_DATA}}. Código de verificação: <b>{{EMISSAO_CODIGO}}</b>. Confira a autenticidade em {{EMISSAO_URL_VERIFICACAO}}</p>\",\"ordem\":11,\"paginacao\":true,\"qr\":true,\"tipo\":\"rodape\"}]}'
   AND NOT EXISTS (SELECT 1 FROM rm_historico_modelo_versoes x WHERE x.modelo_id = m.id AND x.versao = 2);
UPDATE rm_historico_modelos m JOIN rm_historico_modelo_versoes v ON v.modelo_id = m.id AND v.versao = 2
   SET m.versao_atual = 2
 WHERE m.tipo = 'PARCIAL' AND m.versao_atual = 1 AND v.secoes = '{\"assinaturas\":{\"blocos\":[{\"cargo\":\"Secretaria acadêmica\",\"imagem\":null,\"nome\":\"\"},{\"cargo\":\"Direção\",\"imagem\":null,\"nome\":\"\"}]},\"documento\":\"<table class=\\\"cab\\\"><tr><td class=\\\"cab-logo\\\">{{LOGO_INSTITUICAO}}</td><td class=\\\"cab-texto\\\"><p style=\\\"text-align: center\\\"><b>{{INSTITUICAO_NOME}}</b></p><p style=\\\"text-align: center\\\">{{CURSO_NOME}}</p><p style=\\\"text-align: center\\\">Polo: {{POLO_NOME}}</p></td></tr></table><div class=\\\"secao s-tipo_documento\\\"><h2 style=\\\"text-align: center\\\">HISTÓRICO ESCOLAR PARCIAL</h2></div><div class=\\\"secao s-aluno\\\"><table><tr><td style=\\\"width: 60%\\\"><b>Nome:</b> {{ALUNO_NOME}}</td><td><b>RA:</b> {{ALUNO_RA}}</td></tr><tr><td><b>Nascimento:</b> {{ALUNO_NASCIMENTO}} — {{ALUNO_NATURALIDADE}}</td><td><b>CPF:</b> {{ALUNO_CPF}}</td></tr></table></div><div class=\\\"secao s-matricula\\\"><p><b>Curso:</b> {{CURSO_NOME}} — <b>Modalidade:</b> {{MODALIDADE}} — <b>Polo:</b> {{POLO_NOME}}</p><p><b>Ingresso:</b> {{MATRICULA_INGRESSO_FORMA}}, em {{MATRICULA_INGRESSO_DATA}} — <b>Situação:</b> {{MATRICULA_SITUACAO}}</p></div><div class=\\\"secao s-ato\\\"><p><b>Ato legal:</b> {{ATO_DESCRICAO}}</p></div><div class=\\\"secao s-quadro\\\"><p>{{QUADRO_DISCIPLINAS}}</p></div><p>{{SE_TCC}}</p><div class=\\\"secao s-tcc\\\"><p><b>Trabalho de conclusão de curso:</b> {{TCC_TITULO}}</p><p>Orientação: {{TCC_ORIENTADOR}} ({{TCC_ORIENTADOR_TITULACAO}}) — Nota: {{TCC_NOTA}} — {{TCC_DATA}} — {{TCC_SITUACAO}}</p></div><p>{{FIM_SE_TCC}}</p><div class=\\\"secao s-totais\\\"><p><b>Carga horária:</b> exigida {{TOTAL_CH_EXIGIDA}} h; integralizada {{TOTAL_CH_INTEGRALIZADA}} h ({{TOTAL_APROVADAS}} de {{TOTAL_DISCIPLINAS}} disciplinas).</p><p><b>Período de realização:</b> {{PERIODO_INICIO}} a {{PERIODO_FIM}} — <b>duração total:</b> {{DURACAO_MESES}} meses.</p></div><div class=\\\"secao s-assinaturas\\\"><p style=\\\"text-align: right\\\">{{EMISSAO_DATA_EXTENSO}}</p><p>{{ASSINATURAS}}</p></div><table class=\\\"rodape\\\"><tr><td><p style=\\\"font-size: 7pt\\\">Emitido em {{EMISSAO_DATA}}. Código de verificação: <b>{{EMISSAO_CODIGO}}</b>. Confira a autenticidade em {{EMISSAO_URL_VERIFICACAO}}</p></td><td class=\\\"qr\\\">{{QR_CODE}}</td></tr></table>\",\"formato\":2,\"opcoes\":{\"paginacao\":true},\"quadro\":{\"atividades\":true,\"colunas\":[{\"alinhamento\":\"left\",\"campo\":\"codigo\",\"largura\":\"9%\",\"ordem\":1,\"rotulo\":\"Código\"},{\"alinhamento\":\"left\",\"campo\":\"disciplina\",\"largura\":\"28%\",\"ordem\":2,\"rotulo\":\"Disciplina\"},{\"alinhamento\":\"center\",\"campo\":\"ch\",\"largura\":\"6%\",\"ordem\":3,\"rotulo\":\"CH\"},{\"alinhamento\":\"left\",\"campo\":\"periodo\",\"largura\":\"15%\",\"ordem\":4,\"rotulo\":\"Período de realização\"},{\"alinhamento\":\"left\",\"campo\":\"docente\",\"largura\":\"18%\",\"ordem\":5,\"rotulo\":\"Docente\"},{\"alinhamento\":\"center\",\"campo\":\"nota\",\"largura\":\"8%\",\"ordem\":6,\"rotulo\":\"Nota final\"},{\"alinhamento\":\"center\",\"campo\":\"frequencia\",\"largura\":\"8%\",\"ordem\":7,\"rotulo\":\"Frequência\"},{\"alinhamento\":\"center\",\"campo\":\"situacao\",\"largura\":\"8%\",\"ordem\":8,\"rotulo\":\"Situação\"}],\"emCurso\":\"Em curso\"}}';

-- modelo padrão FINAL: só se a versão atual for a v1 ainda IGUAL ao padrão antigo (nunca editado)
INSERT INTO rm_historico_modelo_versoes (modelo_id, versao, secoes, usuario_cre, created)
SELECT m.id, 2, '{\"assinaturas\":{\"blocos\":[{\"cargo\":\"Secretaria acadêmica\",\"imagem\":null,\"nome\":\"\"},{\"cargo\":\"Direção\",\"imagem\":null,\"nome\":\"\"}]},\"documento\":\"<table class=\\\"cab\\\"><tr><td class=\\\"cab-logo\\\">{{LOGO_INSTITUICAO}}</td><td class=\\\"cab-texto\\\"><p style=\\\"text-align: center\\\"><b>{{INSTITUICAO_NOME}}</b></p><p style=\\\"text-align: center\\\">{{CURSO_NOME}}</p><p style=\\\"text-align: center\\\">Polo: {{POLO_NOME}}</p></td></tr></table><div class=\\\"secao s-tipo_documento\\\"><h2 style=\\\"text-align: center\\\">HISTÓRICO ESCOLAR FINAL</h2></div><div class=\\\"secao s-aluno\\\"><table><tr><td style=\\\"width: 60%\\\"><b>Nome:</b> {{ALUNO_NOME}}</td><td><b>RA:</b> {{ALUNO_RA}}</td></tr><tr><td><b>Nascimento:</b> {{ALUNO_NASCIMENTO}} — {{ALUNO_NATURALIDADE}}</td><td><b>CPF:</b> {{ALUNO_CPF}}</td></tr></table></div><div class=\\\"secao s-matricula\\\"><p><b>Curso:</b> {{CURSO_NOME}} — <b>Modalidade:</b> {{MODALIDADE}} — <b>Polo:</b> {{POLO_NOME}}</p><p><b>Ingresso:</b> {{MATRICULA_INGRESSO_FORMA}}, em {{MATRICULA_INGRESSO_DATA}} — <b>Situação:</b> {{MATRICULA_SITUACAO}}, em {{MATRICULA_CONCLUSAO}}</p></div><div class=\\\"secao s-ato\\\"><p><b>Ato legal:</b> {{ATO_DESCRICAO}}</p></div><div class=\\\"secao s-quadro\\\"><p>{{QUADRO_DISCIPLINAS}}</p></div><p>{{SE_TCC}}</p><div class=\\\"secao s-tcc\\\"><p><b>Trabalho de conclusão de curso:</b> {{TCC_TITULO}}</p><p>Orientação: {{TCC_ORIENTADOR}} ({{TCC_ORIENTADOR_TITULACAO}}) — Nota: {{TCC_NOTA}} — {{TCC_DATA}} — {{TCC_SITUACAO}}</p></div><p>{{FIM_SE_TCC}}</p><div class=\\\"secao s-totais\\\"><p><b>Carga horária:</b> exigida {{TOTAL_CH_EXIGIDA}} h; integralizada {{TOTAL_CH_INTEGRALIZADA}} h ({{TOTAL_APROVADAS}} de {{TOTAL_DISCIPLINAS}} disciplinas).</p><p><b>Período de realização:</b> {{PERIODO_INICIO}} a {{PERIODO_FIM}} — <b>duração total:</b> {{DURACAO_MESES}} meses.</p></div><div class=\\\"secao s-assinaturas\\\"><p style=\\\"text-align: right\\\">{{EMISSAO_DATA_EXTENSO}}</p><p>{{ASSINATURAS}}</p></div><table class=\\\"rodape\\\"><tr><td><p style=\\\"font-size: 7pt\\\">Emitido em {{EMISSAO_DATA}}. Código de verificação: <b>{{EMISSAO_CODIGO}}</b>. Confira a autenticidade em {{EMISSAO_URL_VERIFICACAO}}</p></td><td class=\\\"qr\\\">{{QR_CODE}}</td></tr></table>\",\"formato\":2,\"opcoes\":{\"paginacao\":true},\"quadro\":{\"atividades\":true,\"colunas\":[{\"alinhamento\":\"left\",\"campo\":\"codigo\",\"largura\":\"9%\",\"ordem\":1,\"rotulo\":\"Código\"},{\"alinhamento\":\"left\",\"campo\":\"disciplina\",\"largura\":\"27%\",\"ordem\":2,\"rotulo\":\"Disciplina\"},{\"alinhamento\":\"center\",\"campo\":\"ch\",\"largura\":\"6%\",\"ordem\":3,\"rotulo\":\"CH\"},{\"alinhamento\":\"left\",\"campo\":\"periodo\",\"largura\":\"15%\",\"ordem\":4,\"rotulo\":\"Período de realização\"},{\"alinhamento\":\"left\",\"campo\":\"docente\",\"largura\":\"17%\",\"ordem\":5,\"rotulo\":\"Docente\"},{\"alinhamento\":\"left\",\"campo\":\"titulacao\",\"largura\":\"9%\",\"ordem\":6,\"rotulo\":\"Titulação\"},{\"alinhamento\":\"center\",\"campo\":\"nota\",\"largura\":\"6%\",\"ordem\":7,\"rotulo\":\"Nota final\"},{\"alinhamento\":\"center\",\"campo\":\"frequencia\",\"largura\":\"6%\",\"ordem\":8,\"rotulo\":\"Frequência\"},{\"alinhamento\":\"center\",\"campo\":\"situacao\",\"largura\":\"5%\",\"ordem\":9,\"rotulo\":\"Situação\"}],\"emCurso\":\"Em curso\"}}', NULL, NOW()
  FROM rm_historico_modelos m JOIN rm_historico_modelo_versoes v ON v.modelo_id = m.id AND v.versao = 1
 WHERE m.tipo = 'FINAL' AND m.versao_atual = 1 AND v.secoes = '{\"secoes\":[{\"ativa\":true,\"html\":\"<p style=\\\"text-align: center\\\"><b>{{INSTITUICAO_NOME}}</b></p><p style=\\\"text-align: center\\\">{{CURSO_NOME}}</p><p style=\\\"text-align: center\\\">Polo: {{POLO_NOME}}</p>\",\"logo\":true,\"ordem\":1,\"tipo\":\"cabecalho\"},{\"ativa\":true,\"html\":\"<h2 style=\\\"text-align: center\\\">HISTÓRICO ESCOLAR FINAL</h2>\",\"ordem\":2,\"tipo\":\"tipo_documento\"},{\"ativa\":true,\"html\":\"<table><tr><td style=\\\"width: 60%\\\"><b>Nome:</b> {{ALUNO_NOME}}</td><td><b>RA:</b> {{ALUNO_RA}}</td></tr><tr><td><b>Nascimento:</b> {{ALUNO_NASCIMENTO}} — {{ALUNO_NATURALIDADE}}</td><td><b>CPF:</b> {{ALUNO_CPF}}</td></tr></table>\",\"ordem\":3,\"tipo\":\"aluno\"},{\"ativa\":true,\"html\":\"<p><b>Curso:</b> {{CURSO_NOME}} — <b>Modalidade:</b> {{MODALIDADE}} — <b>Polo:</b> {{POLO_NOME}}</p><p><b>Ingresso:</b> {{MATRICULA_INGRESSO_FORMA}}, em {{MATRICULA_INGRESSO_DATA}} — <b>Situação:</b> {{MATRICULA_SITUACAO}}, em {{MATRICULA_CONCLUSAO}}</p>\",\"ordem\":4,\"tipo\":\"matricula\"},{\"ativa\":true,\"html\":\"<p><b>Ato legal:</b> {{ATO_DESCRICAO}}</p>\",\"ordem\":5,\"tipo\":\"ato\"},{\"ativa\":true,\"colunas\":[{\"alinhamento\":\"left\",\"campo\":\"codigo\",\"largura\":\"9%\",\"ordem\":1,\"rotulo\":\"Código\"},{\"alinhamento\":\"left\",\"campo\":\"disciplina\",\"largura\":\"27%\",\"ordem\":2,\"rotulo\":\"Disciplina\"},{\"alinhamento\":\"center\",\"campo\":\"ch\",\"largura\":\"6%\",\"ordem\":3,\"rotulo\":\"CH\"},{\"alinhamento\":\"left\",\"campo\":\"periodo\",\"largura\":\"15%\",\"ordem\":4,\"rotulo\":\"Período de realização\"},{\"alinhamento\":\"left\",\"campo\":\"docente\",\"largura\":\"17%\",\"ordem\":5,\"rotulo\":\"Docente\"},{\"alinhamento\":\"left\",\"campo\":\"titulacao\",\"largura\":\"9%\",\"ordem\":6,\"rotulo\":\"Titulação\"},{\"alinhamento\":\"center\",\"campo\":\"nota\",\"largura\":\"6%\",\"ordem\":7,\"rotulo\":\"Nota final\"},{\"alinhamento\":\"center\",\"campo\":\"frequencia\",\"largura\":\"6%\",\"ordem\":8,\"rotulo\":\"Frequência\"},{\"alinhamento\":\"center\",\"campo\":\"situacao\",\"largura\":\"5%\",\"ordem\":9,\"rotulo\":\"Situação\"}],\"html\":\"\",\"ordem\":6,\"tipo\":\"quadro\"},{\"ativa\":true,\"html\":\"<p><b>Trabalho de conclusão de curso:</b> {{TCC_TITULO}}</p><p>Orientação: {{TCC_ORIENTADOR}} ({{TCC_ORIENTADOR_TITULACAO}}) — Nota: {{TCC_NOTA}} — {{TCC_DATA}} — {{TCC_SITUACAO}}</p>\",\"ordem\":7,\"somenteSeHouver\":true,\"tipo\":\"tcc\"},{\"ativa\":true,\"html\":\"<p><b>Carga horária:</b> exigida {{TOTAL_CH_EXIGIDA}} h; integralizada {{TOTAL_CH_INTEGRALIZADA}} h ({{TOTAL_APROVADAS}} de {{TOTAL_DISCIPLINAS}} disciplinas).</p><p><b>Período de realização:</b> {{PERIODO_INICIO}} a {{PERIODO_FIM}} — <b>duração total:</b> {{DURACAO_MESES}} meses.</p>\",\"ordem\":8,\"tipo\":\"totais\"},{\"ativa\":false,\"html\":\"\",\"ordem\":9,\"tipo\":\"observacoes\"},{\"ativa\":true,\"blocos\":[{\"cargo\":\"Secretaria acadêmica\",\"imagem\":null,\"nome\":\"\"},{\"cargo\":\"Direção\",\"imagem\":null,\"nome\":\"\"}],\"html\":\"<p style=\\\"text-align: right\\\">{{EMISSAO_DATA_EXTENSO}}</p>\",\"ordem\":10,\"tipo\":\"assinaturas\"},{\"ativa\":true,\"html\":\"<p style=\\\"font-size: 7pt\\\">Emitido em {{EMISSAO_DATA}}. Código de verificação: <b>{{EMISSAO_CODIGO}}</b>. Confira a autenticidade em {{EMISSAO_URL_VERIFICACAO}}</p>\",\"ordem\":11,\"paginacao\":true,\"qr\":true,\"tipo\":\"rodape\"}]}'
   AND NOT EXISTS (SELECT 1 FROM rm_historico_modelo_versoes x WHERE x.modelo_id = m.id AND x.versao = 2);
UPDATE rm_historico_modelos m JOIN rm_historico_modelo_versoes v ON v.modelo_id = m.id AND v.versao = 2
   SET m.versao_atual = 2
 WHERE m.tipo = 'FINAL' AND m.versao_atual = 1 AND v.secoes = '{\"assinaturas\":{\"blocos\":[{\"cargo\":\"Secretaria acadêmica\",\"imagem\":null,\"nome\":\"\"},{\"cargo\":\"Direção\",\"imagem\":null,\"nome\":\"\"}]},\"documento\":\"<table class=\\\"cab\\\"><tr><td class=\\\"cab-logo\\\">{{LOGO_INSTITUICAO}}</td><td class=\\\"cab-texto\\\"><p style=\\\"text-align: center\\\"><b>{{INSTITUICAO_NOME}}</b></p><p style=\\\"text-align: center\\\">{{CURSO_NOME}}</p><p style=\\\"text-align: center\\\">Polo: {{POLO_NOME}}</p></td></tr></table><div class=\\\"secao s-tipo_documento\\\"><h2 style=\\\"text-align: center\\\">HISTÓRICO ESCOLAR FINAL</h2></div><div class=\\\"secao s-aluno\\\"><table><tr><td style=\\\"width: 60%\\\"><b>Nome:</b> {{ALUNO_NOME}}</td><td><b>RA:</b> {{ALUNO_RA}}</td></tr><tr><td><b>Nascimento:</b> {{ALUNO_NASCIMENTO}} — {{ALUNO_NATURALIDADE}}</td><td><b>CPF:</b> {{ALUNO_CPF}}</td></tr></table></div><div class=\\\"secao s-matricula\\\"><p><b>Curso:</b> {{CURSO_NOME}} — <b>Modalidade:</b> {{MODALIDADE}} — <b>Polo:</b> {{POLO_NOME}}</p><p><b>Ingresso:</b> {{MATRICULA_INGRESSO_FORMA}}, em {{MATRICULA_INGRESSO_DATA}} — <b>Situação:</b> {{MATRICULA_SITUACAO}}, em {{MATRICULA_CONCLUSAO}}</p></div><div class=\\\"secao s-ato\\\"><p><b>Ato legal:</b> {{ATO_DESCRICAO}}</p></div><div class=\\\"secao s-quadro\\\"><p>{{QUADRO_DISCIPLINAS}}</p></div><p>{{SE_TCC}}</p><div class=\\\"secao s-tcc\\\"><p><b>Trabalho de conclusão de curso:</b> {{TCC_TITULO}}</p><p>Orientação: {{TCC_ORIENTADOR}} ({{TCC_ORIENTADOR_TITULACAO}}) — Nota: {{TCC_NOTA}} — {{TCC_DATA}} — {{TCC_SITUACAO}}</p></div><p>{{FIM_SE_TCC}}</p><div class=\\\"secao s-totais\\\"><p><b>Carga horária:</b> exigida {{TOTAL_CH_EXIGIDA}} h; integralizada {{TOTAL_CH_INTEGRALIZADA}} h ({{TOTAL_APROVADAS}} de {{TOTAL_DISCIPLINAS}} disciplinas).</p><p><b>Período de realização:</b> {{PERIODO_INICIO}} a {{PERIODO_FIM}} — <b>duração total:</b> {{DURACAO_MESES}} meses.</p></div><div class=\\\"secao s-assinaturas\\\"><p style=\\\"text-align: right\\\">{{EMISSAO_DATA_EXTENSO}}</p><p>{{ASSINATURAS}}</p></div><table class=\\\"rodape\\\"><tr><td><p style=\\\"font-size: 7pt\\\">Emitido em {{EMISSAO_DATA}}. Código de verificação: <b>{{EMISSAO_CODIGO}}</b>. Confira a autenticidade em {{EMISSAO_URL_VERIFICACAO}}</p></td><td class=\\\"qr\\\">{{QR_CODE}}</td></tr></table>\",\"formato\":2,\"opcoes\":{\"paginacao\":true},\"quadro\":{\"atividades\":true,\"colunas\":[{\"alinhamento\":\"left\",\"campo\":\"codigo\",\"largura\":\"9%\",\"ordem\":1,\"rotulo\":\"Código\"},{\"alinhamento\":\"left\",\"campo\":\"disciplina\",\"largura\":\"27%\",\"ordem\":2,\"rotulo\":\"Disciplina\"},{\"alinhamento\":\"center\",\"campo\":\"ch\",\"largura\":\"6%\",\"ordem\":3,\"rotulo\":\"CH\"},{\"alinhamento\":\"left\",\"campo\":\"periodo\",\"largura\":\"15%\",\"ordem\":4,\"rotulo\":\"Período de realização\"},{\"alinhamento\":\"left\",\"campo\":\"docente\",\"largura\":\"17%\",\"ordem\":5,\"rotulo\":\"Docente\"},{\"alinhamento\":\"left\",\"campo\":\"titulacao\",\"largura\":\"9%\",\"ordem\":6,\"rotulo\":\"Titulação\"},{\"alinhamento\":\"center\",\"campo\":\"nota\",\"largura\":\"6%\",\"ordem\":7,\"rotulo\":\"Nota final\"},{\"alinhamento\":\"center\",\"campo\":\"frequencia\",\"largura\":\"6%\",\"ordem\":8,\"rotulo\":\"Frequência\"},{\"alinhamento\":\"center\",\"campo\":\"situacao\",\"largura\":\"5%\",\"ordem\":9,\"rotulo\":\"Situação\"}],\"emCurso\":\"Em curso\"}}';


-- Versão 1.9.0 (a 1.8.0 passa a anterior) e as novidades 61 a 68, escritas para o usuário, por perfil, com o guia.
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.9.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 12, '1.9.0', 1, 9, 0, '2026-09-29', 'Editor novo do modelo do histórico, inativar curso e matéria, boletim em cartões no celular, turno só onde se aplica e a barra da disciplina mais enxuta.', 1, '2026-09-29 12:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.9.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.9.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT n.* FROM (
  SELECT 61 id, 12 v, 'Editor novo do modelo do histórico' t, 'O modelo agora é um documento só, escrito como num editor de texto. As variáveis entram com um clique no ponto do cursor (com busca), o quadro de disciplinas, as assinaturas, o logo e o QR code aparecem como marcadores, e a prévia em PDF fica ao lado.' x, 1 o, 'administrador' p, 'editar-modelo-de-historico' g, '2026-09-29 12:00:00' c
  UNION ALL SELECT 62, 12, 'Inativar curso e matéria', 'Curso ou matéria que não vai mais ser oferecido pode ser inativado: sai das listas de escolha e continua nas de consulta com a etiqueta Inativo. Notas, ofertas e históricos não mudam, e dá para reativar.', 2, 'administrador,secretaria', 'inativar-curso-ou-materia', '2026-09-29 12:00:00'
  UNION ALL SELECT 63, 12, 'Boletim em cartões no celular', 'No celular, o boletim mostra um cartão por disciplina, com a carga horária, a média, a frequência e o resultado. No computador, continua a tabela.', 3, 'aluno', 'ler-o-boletim', '2026-09-29 12:00:00'
  UNION ALL SELECT 64, 12, 'Boletim em cartões no celular', 'Aberto no celular, o boletim do aluno vira um cartão por disciplina, com os mesmos números da tabela.', 4, 'administrador,secretaria', 'ver-e-imprimir-o-boletim', '2026-09-29 12:00:00'
  UNION ALL SELECT 65, 12, 'Turno só onde se aplica', 'O turno do curso é escolhido numa lista e não aparece nos cursos, ofertas e horários EaD.', 5, 'administrador', 'polos-e-cursos', '2026-09-29 12:00:00'
  UNION ALL SELECT 66, 12, 'Horário sem turno no EaD', 'No curso a distância, o horário não mostra mais o turno, que não se aplica.', 6, 'aluno', NULL, '2026-09-29 12:00:00'
  UNION ALL SELECT 67, 12, 'Barra da disciplina mais enxuta', 'Encerramento fica só na barra do topo; o nome das atividades quebra em duas linhas (o nome completo aparece ao parar o mouse); e o boletim do aluno tem o ícone de documento, com a dica Ver boletim do aluno.', 7, 'administrador,secretaria,professor', 'acompanhar-notas-e-resultado', '2026-09-29 12:00:00'
  UNION ALL SELECT 68, 12, 'Módulos: pode voltar para Sim', 'Com Organizar a matriz em módulos em Sim, as grades que já existem mostram o módulo Geral, que você renomeia e reorganiza. A coluna Módulo do histórico aparece e some sozinha com a opção.', 8, 'administrador', 'regras-de-nota-e-frequencia', '2026-09-29 12:00:00'
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` a WHERE a.`id` = n.id);
-- <<< E8d 1.9.0

-- >>> 1.9.1 (correção: endereço atrás de proxy/túnel)
-- Versão 1.9.1 (a 1.9.0 passa a anterior) e a novidade 69. Só conteúdo: nenhuma tabela muda.
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.9.1' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 13, '1.9.1', 1, 9, 1, '2026-09-30', 'Correção: endereço do sistema certo atrás de proxy, balanceador ou túnel que termina o HTTPS, sem caminho fixo e só com os domínios autorizados.', 1, '2026-09-30 12:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.9.1');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.9.1';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT n.* FROM (
  SELECT 69 id, 13 v, 'Endereço certo atrás de proxy' t, 'Com o sistema atrás de um balanceador, proxy ou túnel que cuida do HTTPS, as telas, os links de senha enviados por e-mail e o QR code do histórico saem com o endereço certo (https e o domínio da instituição).' x, 1 o, 'administrador' p, NULL g, '2026-09-30 12:00:00' c
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` a WHERE a.`id` = n.id);
-- <<< 1.9.1

-- >>> E1a 1.10.0 (identidade da instituição e do curso)
-- Versão 1.10.0 (a 1.9.1 passa a anterior) e as novidades 70 e 71.
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.10.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 14, '1.10.0', 1, 10, 0, '2026-09-30', 'Identidade da instituição e do curso nos documentos: linhas do cabeçalho, telefone e e-mail da instituição; nível, nome por extenso, linha do cabeçalho e sigla do curso; variáveis novas no editor de modelos.', 1, '2026-09-30 18:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.10.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.10.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT n.* FROM (
  SELECT 70 id, 14 v, 'Cabeçalho da instituição nos documentos' t, 'Em Dados da instituição, cadastre até 5 linhas do cabeçalho (Estado, órgão, diretoria, programa…), na ordem em que saem nos documentos, com a prévia ao lado; e também o telefone e o e-mail da instituição.' x, 1 o, 'administrador' p, 'dados-da-instituicao-e-atos-legais' g, '2026-09-30 18:00:00' c
  UNION ALL
  SELECT 71, 14, 'Curso: nível, nome por extenso e sigla', 'No cadastro do curso entram o nível (ex.: Especialização), o nome por extenso usado no texto dos documentos, a linha do curso no cabeçalho e a sigla. No editor de modelos, eles e os dados novos da instituição viram variáveis, e há o bloco "Cabeçalho institucional".', 2, 'administrador', 'polos-e-cursos', '2026-09-30 18:00:00'
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` a WHERE a.`id` = n.id);
-- <<< E1a 1.10.0

-- >>> E1b/E2 1.11.0 (matrícula, matriz e frequência)
-- D-353: a ordem das disciplinas nasce ALFABÉTICA (a de hoje), por grade; só nas grades que ainda não têm ordem.
UPDATE `uni_rh_cursos_disciplinas_grade` l
  JOIN (SELECT l2.`id`, ROW_NUMBER() OVER (PARTITION BY l2.`grade_id` ORDER BY mt.`nome`, l2.`id`) AS rn
          FROM `uni_rh_cursos_disciplinas_grade` l2 INNER JOIN `rm_materias` mt ON mt.`id` = l2.`materia_id`
         WHERE l2.`grade_id` IN (SELECT g.`grade_id` FROM `uni_rh_cursos_disciplinas_grade` g GROUP BY g.`grade_id` HAVING MAX(g.`ordem`) = 0)) x
    ON x.`id` = l.`id`
   SET l.`ordem` = x.rn, l.`modified` = l.`modified`;
-- D-355: rótulo no documento de cada situação (nenhuma situação nova). "Cursando" sai "EM CURSO".
UPDATE `rm_matriculas_situacao` SET `rotulo_documento` = CASE WHEN `id` = 1 THEN 'EM CURSO' ELSE UPPER(`nome`) END, `modified` = `modified`
 WHERE `rotulo_documento` IS NULL;
-- D-357 e D-361: formas neutras da concordância e siglas/rótulos da frequência (editáveis em Configurações → Instituição).
INSERT INTO `adms_parametros` (`chave`, `valor`, `descricao`, `grupo`, `editavel`, `created`)
SELECT n.* FROM (
  SELECT 'aluno.rotulo_neutro' c, 'ESTUDANTE' v, 'Documentos: rótulo do aluno quando o sexo é Outros (no lugar de ALUNA/ALUNO).' d, 'Documentos' g, 1 e, '2026-09-30 20:00:00' t
  UNION ALL SELECT 'aluno.matriculado_neutro', 'com matrícula regular', 'Documentos: forma neutra de matriculada/matriculado (sexo Outros).', 'Documentos', 1, '2026-09-30 20:00:00'
  UNION ALL SELECT 'aluno.artigo_neutro', 'o(a)', 'Documentos: forma neutra do artigo a/o (sexo Outros).', 'Documentos', 1, '2026-09-30 20:00:00'
  UNION ALL SELECT 'frequencia.sigla_suficiente', 'FS', 'Sigla da frequência suficiente (frequência ≥ a mínima).', 'Notas e frequência', 1, '2026-09-30 20:00:00'
  UNION ALL SELECT 'frequencia.rotulo_suficiente', 'Frequência Suficiente', 'Rótulo da frequência suficiente (legenda dos documentos).', 'Notas e frequência', 1, '2026-09-30 20:00:00'
  UNION ALL SELECT 'frequencia.sigla_insuficiente', 'FI', 'Sigla da frequência insuficiente (frequência abaixo da mínima).', 'Notas e frequência', 1, '2026-09-30 20:00:00'
  UNION ALL SELECT 'frequencia.rotulo_insuficiente', 'Frequência Insuficiente', 'Rótulo da frequência insuficiente (legenda dos documentos).', 'Notas e frequência', 1, '2026-09-30 20:00:00'
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_parametros` a WHERE a.`chave` = n.c);
-- Rotas: 415 ordenar as disciplinas da grade (POST, arrastar) e 416 lista de pendência do sexo; administrador e secretaria.
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT * FROM (SELECT 415 a, 'Ordenar disciplinas da matriz' b, 'Matriz' c, 'ordenarDisciplinas' d, 'matriz' e, 'ordenar-disciplinas' f, 'Ordenar as disciplinas da grade' g, 'POST. Ordem usada nos documentos (E1b, D-353).' h, 2 i, NULL j, 3 k, 1 l, 1 m, '2026-09-30 20:00:00' n, NULL o) t WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 415);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT * FROM (SELECT 416 a, 'Alunos sem sexo informado' b, 'Academicos' c, 'pendenciaSexo' d, 'academicos' e, 'pendencia-sexo' f, 'Pendência: sexo do aluno' g, 'Alunos com o padrão antigo (Outros não escolhido) — E1b, D-356.' h, 2 i, 'fas fa-user-tag' j, 1 k, 1 l, 1 m, '2026-09-30 20:00:00' n, NULL o) t WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 416);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`)
SELECT CASE WHEN n.id IN (1, 4) THEN 1 ELSE 2 END, 0, 2, 2, 7, n.id, p.id, '2026-09-30 20:00:00', NULL
  FROM `adms_niveis_acessos` n CROSS JOIN (SELECT 415 id UNION ALL SELECT 416) p
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.id AND x.`adms_pagina_id` = p.id)
 ORDER BY p.id, n.id;
-- Versão 1.11.0 (a 1.10.0 passa a anterior) e as novidades 72 a 76.
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.11.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 15, '1.11.0', 1, 11, 0, '2026-09-30', 'Matrícula, matriz e frequência: ordem das disciplinas na grade, previsão de conclusão, rótulo da situação nos documentos, sexo obrigatório com pendência, concordância, CPF mascarado, frequência mínima por curso e FS/FI; salvamento do modelo de histórico em transação.', 1, '2026-09-30 20:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.11.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.11.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT n.* FROM (
  SELECT 72 id, 15 v, 'Ordem das disciplinas na grade' t, 'Na matriz, arraste as disciplinas para a ordem do currículo e clique em Salvar a ordem. O histórico passa a listar as disciplinas nessa ordem.' x, 1 o, 'administrador,secretaria' p, 'montar-a-matriz' g, '2026-09-30 20:00:00' c
  UNION ALL SELECT 73, 15, 'Previsão de conclusão na matrícula', 'No vínculo do aluno ao curso entra a previsão de conclusão, com uma sugestão tirada das ofertas da grade. Ela e o ingresso saem nos documentos como "novembro de 2025".', 2, 'administrador,secretaria', 'vincular-e-enturmar', '2026-09-30 20:00:00'
  UNION ALL SELECT 74, 15, 'Sexo obrigatório no cadastro do aluno', 'O sexo passou a ser obrigatório (os documentos concordam: ALUNA/ALUNO, matriculada/matriculado). Alunos antigos que ficaram com "Outros" por padrão aparecem na lista "Alunos sem sexo informado".', 3, 'administrador,secretaria', 'cadastrar-aluno-e-enviar-link', '2026-09-30 20:00:00'
  UNION ALL SELECT 75, 15, 'Frequência: mínimo por curso e FS/FI', 'O curso pode ter frequência mínima própria (vazio = a da instituição). Os documentos podem mostrar a frequência como FS/FI, percentual ou os dois (aba Quadro do modelo); o boletim mostra o percentual com a sigla.', 4, 'administrador', 'regras-de-nota-e-frequencia', '2026-09-30 20:00:00'
  UNION ALL SELECT 76, 15, 'Rótulos para os documentos', 'Em Configurações → Instituição: o rótulo de cada situação da matrícula nos documentos (Cursando → EM CURSO), as formas neutras da concordância e as siglas FS/FI.', 5, 'administrador', 'regras-de-nota-e-frequencia', '2026-09-30 20:00:00'
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` a WHERE a.`id` = n.id);
-- <<< E1b/E2 1.11.0

-- >>> E5/E6 1.12.0 (gerado de database/atualizacoes/1.12.0_historico_atestado.sql: histórico no layout do Word e atestado de matrícula)
-- D-314: como a média final do aluno é calculada (Configurações → Instituição).
INSERT INTO `adms_parametros` (`chave`, `valor`, `descricao`, `grupo`, `editavel`, `created`)
SELECT 'documento.media_final', 'aritmetica', 'Documentos: média final do aluno no histórico FINAL — aritmetica (simples das disciplinas) ou ponderada (pela carga horária).', 'Documentos', 1, '2026-09-30 22:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_parametros` WHERE `chave` = 'documento.media_final');
-- D-376: páginas do portal para o ATESTADO (o próprio aluno): a tela, a emissão (POST) e o PDF; só o nível 1 (Estudante).
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `nome_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT n.* FROM (
  SELECT 21 id, 'Atestado de matrícula' mp, 'Atestado de matrícula' nm, 'Atestado' c, 'index' me, 'atestado' mc, 'index' mm, 'E6 (D-376): o aluno emite o próprio atestado.' o, 2 lp, 'fas fa-file-signature' ic, 1 g, 1 t, 1 s, '2026-09-30 22:00:00' cr, NULL md
  UNION ALL SELECT 22, 'Emitir atestado', 'Emitir atestado', 'Atestado', 'emitir', 'atestado', 'emitir', 'POST. Só a matrícula do próprio aluno, com vínculo ativo.', 2, NULL, 1, 1, 1, '2026-09-30 22:00:00', NULL
  UNION ALL SELECT 23, 'PDF do atestado', 'PDF do atestado', 'Atestado', 'pdf', 'atestado', 'pdf', 'Só as emissões do próprio aluno.', 2, NULL, 1, 1, 1, '2026-09-30 22:00:00', NULL
) n WHERE NOT EXISTS (SELECT 1 FROM `col_paginas` p WHERE p.`id` = n.id);
INSERT INTO `col_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`)
SELECT 1, p.o, 1, p.lm, 2, 1, p.id, '2026-09-30 22:00:00', NULL
  FROM (SELECT 21 id, 12 o, 1 lm UNION ALL SELECT 22, 0, 2 UNION ALL SELECT 23, 0, 2) p
 WHERE NOT EXISTS (SELECT 1 FROM `col_nivacs_pgs` x WHERE x.`adms_pagina_id` = p.id AND x.`adms_niveis_acesso_id` = 1);
-- Versão 1.12.0 (a 1.11.0 passa a anterior) e as novidades 77 a 81.
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.12.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 16, '1.12.0', 1, 12, 0, '2026-09-30', 'Histórico no layout do modelo do cliente (estilo oficial, Liberation Sans, casas do documento, CH no nome, linhas finais com a média final do aluno) e o atestado de matrícula (secretaria e portal), com trechos condicionais por variável.', 1, '2026-09-30 22:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.12.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.12.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT n.* FROM (
  SELECT 77 id, 16 v, 'Atestado de matrícula' t, 'A secretaria emite o atestado de matrícula pela ficha do aluno (aba Históricos) ou pelo botão na lista de alunos; só com a matrícula em "Cursando" e a previsão de conclusão preenchida. O documento tem código de verificação, como o histórico.' x, 1 o, 'administrador,secretaria' p, 'emitir-atestado-de-matricula' g, '2026-09-30 22:00:00' c
  UNION ALL SELECT 78, 16, 'Atestado no portal', 'Você pode emitir o seu atestado de matrícula no portal, no menu Acadêmico → Atestado de matrícula, enquanto a matrícula estiver ativa. O documento traz o código para qualquer pessoa conferir a autenticidade.', 1, 'aluno', 'emitir-atestado-de-matricula', '2026-09-30 22:00:00'
  UNION ALL SELECT 79, 16, 'Modelo do histórico no desenho do cliente', 'No editor de modelos: estilo "Documento oficial", fonte Liberation Sans, casas decimais do documento, carga horária dentro do nome da disciplina, uma linha por disciplina e as linhas finais (média final do aluno, TCC e situação).', 2, 'administrador', 'editar-modelo-de-historico', '2026-09-30 22:00:00'
  UNION ALL SELECT 80, 16, 'Trecho que só sai se houver o dado', 'No editor de modelos, marque o início e o fim de um trecho que só sai quando uma variável está preenchida (ex.: o telefone, o e-MEC). Dado vazio dentro do trecho não vira alerta.', 3, 'administrador', 'editar-modelo-de-historico', '2026-09-30 22:00:00'
  UNION ALL SELECT 81, 16, 'Média final do aluno', 'Em Configurações → Instituição, escolha como a média final do aluno é calculada no histórico FINAL: aritmética simples das disciplinas ou ponderada pela carga horária.', 4, 'administrador', 'regras-de-nota-e-frequencia', '2026-09-30 22:00:00'
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` a WHERE a.`id` = n.id);
-- <<< E5/E6 1.12.0

-- >>> E3/E4 1.13.0 (gerado de database/atualizacoes/1.13.0_motor_media.sql: motor de média; ajustes da E5 + E6)
-- D-392: a regra da INSTITUIÇÃO igual à de hoje (as casas e a nota mínima vêm dos parâmetros da base).
INSERT INTO `rm_media_regras` (`escopo`, `escopo_id`, `versao_atual`, `situacao`, `created`)
SELECT 'instituicao', 0, 1, 1, '2026-10-01 00:00:00' FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `rm_media_regras` WHERE `escopo` = 'instituicao' AND `escopo_id` = 0);
INSERT INTO `rm_media_regra_versoes` (`regra_id`, `versao`, `casas`, `arredondamento`, `momento`, `sem_nota`, `componente_vazio`, `nota_minima`, `created`)
SELECT r.`id`, 1,
       CASE WHEN (SELECT `valor` FROM `adms_parametros` WHERE `chave` = 'nota.casas') = '2' THEN 2 ELSE 1 END,
       'meio_para_cima', 'so_final', 'zero', 'redistribuir',
       COALESCE((SELECT CAST(REPLACE(`valor`, ',', '.') AS DECIMAL(4,2)) FROM `adms_parametros` WHERE `chave` = 'nota.media_minima'), 7.00),
       '2026-10-01 00:00:00'
  FROM `rm_media_regras` r
 WHERE r.`escopo` = 'instituicao' AND r.`escopo_id` = 0
   AND NOT EXISTS (SELECT 1 FROM `rm_media_regra_versoes` v WHERE v.`regra_id` = r.`id`);
INSERT INTO `rm_media_regra_componentes` (`versao_id`, `codigo`, `nome`, `peso`, `modo`, `parametro_n`, `parametro_tipo`, `ordem`)
SELECT v.`id`, 'atividades', 'Atividades', 100.0000, 'aritmetica', NULL, NULL, 1
  FROM `rm_media_regra_versoes` v INNER JOIN `rm_media_regras` r ON r.`id` = v.`regra_id`
 WHERE r.`escopo` = 'instituicao' AND r.`escopo_id` = 0 AND v.`versao` = 1
   AND NOT EXISTS (SELECT 1 FROM `rm_media_regra_componentes` c WHERE c.`versao_id` = v.`id`);
-- D-387: os 3 modelos padrão NEUTROS no desenho oficial (só o desenho; nenhum curso; assinaturas vazias).
INSERT INTO `rm_historico_modelos` (`tipo`, `nome`, `situacao`, `versao_atual`, `usuario_cre`, `created`)
SELECT 'PARCIAL', 'Histórico parcial — desenho oficial', 2, 1, NULL, '2026-10-01 00:00:00' FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `rm_historico_modelos` WHERE `nome` = 'Histórico parcial — desenho oficial');
INSERT INTO `rm_historico_modelo_versoes` (`modelo_id`, `versao`, `secoes`, `usuario_cre`, `created`)
SELECT m.`id`, 1, '{\"assinaturas\":{\"blocos\":[]},\"documento\":\"<table class=\\\"cab\\\"><tbody><tr><td class=\\\"cab-logo\\\">{{LOGO_INSTITUICAO}}</td><td class=\\\"cab-texto\\\">{{INSTITUICAO_CABECALHO}}</td></tr></tbody></table><p><br></p><h1>HISTÓRICO ESCOLAR</h1><p><br></p><p style=\\\"text-align: justify\\\">CURSO: {{CURSO_NOME}}</p><p style=\\\"text-align: justify\\\">{{ALUNO_ROTULO}}: {{ALUNO_NOME}}</p><p style=\\\"text-align: justify\\\">CPF: {{ALUNO_CPF}}</p><p><br></p><p>{{QUADRO_DISCIPLINAS}}</p><p style=\\\"text-align: justify; font-size: 10pt\\\">{{FS_SIGLA}}: {{FS_ROTULO}} – {{FI_SIGLA}}: {{FI_ROTULO}} – Nota mínima por disciplina: {{NOTA_MINIMA}}/10,0</p><p><br></p><p style=\\\"text-align: right\\\">{{INSTITUICAO_CIDADE}}, data da assinatura eletrônica.</p><p><br></p><p><br></p><p>{{ASSINATURAS}}</p><p style=\\\"text-align: center\\\">(assinatura digital)</p>\",\"formato\":2,\"opcoes\":{\"casasNota\":2,\"estilo\":\"oficial\",\"fonte\":\"liberation\",\"paginacao\":true},\"quadro\":{\"andamentoEmBranco\":true,\"atividades\":false,\"chNoNome\":true,\"colunas\":[{\"alinhamento\":\"left\",\"campo\":\"disciplina\",\"largura\":\"61.2%\",\"ordem\":1,\"rotulo\":\"DISCIPLINA\"},{\"alinhamento\":\"center\",\"campo\":\"nota\",\"largura\":\"18.8%\",\"ordem\":2,\"rotulo\":\"NOTA\"},{\"alinhamento\":\"center\",\"campo\":\"frequencia\",\"largura\":\"20%\",\"ordem\":3,\"rotulo\":\"FREQUÊNCIA\"}],\"emCurso\":\"Em curso\",\"frequencia\":\"sigla\",\"linhasFinais\":true,\"soVigente\":true,\"textoCh\":\"horas aula\"}}', NULL, '2026-10-01 00:00:00' FROM `rm_historico_modelos` m
 WHERE m.`nome` = 'Histórico parcial — desenho oficial' AND NOT EXISTS (SELECT 1 FROM `rm_historico_modelo_versoes` v WHERE v.`modelo_id` = m.`id`);
INSERT INTO `rm_historico_modelos` (`tipo`, `nome`, `situacao`, `versao_atual`, `usuario_cre`, `created`)
SELECT 'FINAL', 'Histórico final — desenho oficial', 2, 1, NULL, '2026-10-01 00:00:00' FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `rm_historico_modelos` WHERE `nome` = 'Histórico final — desenho oficial');
INSERT INTO `rm_historico_modelo_versoes` (`modelo_id`, `versao`, `secoes`, `usuario_cre`, `created`)
SELECT m.`id`, 1, '{\"assinaturas\":{\"blocos\":[]},\"documento\":\"<table class=\\\"cab\\\"><tbody><tr><td class=\\\"cab-logo\\\">{{LOGO_INSTITUICAO}}</td><td class=\\\"cab-texto\\\">{{INSTITUICAO_CABECALHO}}</td></tr></tbody></table><p><br></p><h1>HISTÓRICO ESCOLAR</h1><p><br></p><p style=\\\"text-align: justify\\\">CURSO: {{CURSO_NOME}}</p><p style=\\\"text-align: justify\\\">{{ALUNO_ROTULO}}: {{ALUNO_NOME}}</p><p style=\\\"text-align: justify\\\">CPF: {{ALUNO_CPF}}</p><p><br></p><p>{{QUADRO_DISCIPLINAS}}</p><p style=\\\"text-align: justify; font-size: 10pt\\\">{{FS_SIGLA}}: {{FS_ROTULO}} – {{FI_SIGLA}}: {{FI_ROTULO}} – Nota mínima por disciplina: {{NOTA_MINIMA}}/10,0</p><p><br></p><p style=\\\"text-align: right\\\">{{INSTITUICAO_CIDADE}}, data da assinatura eletrônica.</p><p><br></p><p><br></p><p>{{ASSINATURAS}}</p><p style=\\\"text-align: center\\\">(assinatura digital)</p>\",\"formato\":2,\"opcoes\":{\"casasNota\":2,\"estilo\":\"oficial\",\"fonte\":\"liberation\",\"paginacao\":true},\"quadro\":{\"andamentoEmBranco\":true,\"atividades\":false,\"chNoNome\":true,\"colunas\":[{\"alinhamento\":\"left\",\"campo\":\"disciplina\",\"largura\":\"61.2%\",\"ordem\":1,\"rotulo\":\"DISCIPLINA\"},{\"alinhamento\":\"center\",\"campo\":\"nota\",\"largura\":\"18.8%\",\"ordem\":2,\"rotulo\":\"NOTA\"},{\"alinhamento\":\"center\",\"campo\":\"frequencia\",\"largura\":\"20%\",\"ordem\":3,\"rotulo\":\"FREQUÊNCIA\"}],\"emCurso\":\"Em curso\",\"frequencia\":\"sigla\",\"linhasFinais\":true,\"soVigente\":true,\"textoCh\":\"horas aula\"}}', NULL, '2026-10-01 00:00:00' FROM `rm_historico_modelos` m
 WHERE m.`nome` = 'Histórico final — desenho oficial' AND NOT EXISTS (SELECT 1 FROM `rm_historico_modelo_versoes` v WHERE v.`modelo_id` = m.`id`);
INSERT INTO `rm_historico_modelos` (`tipo`, `nome`, `situacao`, `versao_atual`, `usuario_cre`, `created`)
SELECT 'ATESTADO', 'Atestado de matrícula — desenho oficial', 2, 1, NULL, '2026-10-01 00:00:00' FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `rm_historico_modelos` WHERE `nome` = 'Atestado de matrícula — desenho oficial');
INSERT INTO `rm_historico_modelo_versoes` (`modelo_id`, `versao`, `secoes`, `usuario_cre`, `created`)
SELECT m.`id`, 1, '{\"assinaturas\":{\"blocos\":[]},\"documento\":\"<table class=\\\"cab\\\"><tbody><tr><td class=\\\"cab-logo\\\">{{LOGO_INSTITUICAO}}</td><td class=\\\"cab-texto\\\">{{INSTITUICAO_CABECALHO}}</td></tr></tbody></table><p><br></p><p><br></p><p><br></p><p><br></p><p><br></p><p><br></p><p><br></p><h1>ATESTADO</h1><p><br></p><p><br></p><p><br></p><p><br></p><p><br></p><p class=\\\"recuo\\\" style=\\\"text-align: justify; font-size: 12pt; line-height: 1.5\\\">Atestamos para os devidos fins que, {{ALUNO_NOME}}, CPF {{ALUNO_CPF_MASCARADO}}, está regularmente {{ALUNO_MATRICULADO}} no curso de {{CURSO_NIVEL_TEXTO}} em {{CURSO_NOME_EXTENSO}} – {{CURSO_SIGLA}}, com carga horária total de {{TOTAL_CH_EXIGIDA}} horas, iniciado em {{MATRICULA_INGRESSO_MES_ANO}} e com previsão de término para {{MATRICULA_PREVISAO_MES_ANO}}.</p><p><br></p><p><br></p><p><br></p><p style=\\\"text-align: justify; font-size: 12pt; text-indent: 30mm\\\">Por ser verdade, firmo o presente.</p><p><br></p><p><br></p><p>{{SE_EMISSAO_PELA_SECRETARIA}}</p><p style=\\\"text-align: right; font-size: 12pt\\\">{{INSTITUICAO_CIDADE}}, data da assinatura eletrônica.</p><p>{{FIM_SE_EMISSAO_PELA_SECRETARIA}}</p><p>{{SE_EMISSAO_PELO_PORTAL}}</p><p style=\\\"text-align: right; font-size: 12pt\\\">{{INSTITUICAO_CIDADE}}, {{EMISSAO_DATA_EXTENSO}}.</p><p>{{FIM_SE_EMISSAO_PELO_PORTAL}}</p><p><br></p><p><br></p><p><br></p><p>{{SE_EMISSAO_PELA_SECRETARIA}}</p><div style=\\\"font-size: 12pt\\\">{{ASSINATURAS}}</div><p style=\\\"text-align: center; font-size: 12pt\\\">(assinatura digital)</p><p>{{FIM_SE_EMISSAO_PELA_SECRETARIA}}</p><p>{{SE_EMISSAO_PELO_PORTAL}}</p><p style=\\\"text-align: center; font-size: 10pt\\\">Documento emitido eletronicamente em {{EMISSAO_DATA_HORA}}. Confira a autenticidade em {{EMISSAO_URL_VERIFICACAO}} com o código {{EMISSAO_CODIGO}}.</p><p>{{FIM_SE_EMISSAO_PELO_PORTAL}}</p><div class=\\\"rodape-fixo\\\"><p>{{SE_INSTITUICAO_LOGRADOURO}}{{INSTITUICAO_LOGRADOURO}}{{FIM_SE_INSTITUICAO_LOGRADOURO}}{{SE_INSTITUICAO_NUMERO}}, nº{{INSTITUICAO_NUMERO}}{{FIM_SE_INSTITUICAO_NUMERO}}{{SE_INSTITUICAO_COMPLEMENTO}} | {{INSTITUICAO_COMPLEMENTO}}{{FIM_SE_INSTITUICAO_COMPLEMENTO}}{{SE_INSTITUICAO_BAIRRO}} | Bairro {{INSTITUICAO_BAIRRO}}{{FIM_SE_INSTITUICAO_BAIRRO}}</p><p>{{SE_INSTITUICAO_CIDADE}}{{INSTITUICAO_CIDADE}}{{FIM_SE_INSTITUICAO_CIDADE}}{{SE_INSTITUICAO_UF}}-{{INSTITUICAO_UF}}{{FIM_SE_INSTITUICAO_UF}}{{SE_INSTITUICAO_CEP}} | {{INSTITUICAO_CEP}}{{FIM_SE_INSTITUICAO_CEP}}{{SE_INSTITUICAO_SITE}} | {{INSTITUICAO_SITE}}{{FIM_SE_INSTITUICAO_SITE}}{{SE_INSTITUICAO_TELEFONE}} | {{INSTITUICAO_TELEFONE}}{{FIM_SE_INSTITUICAO_TELEFONE}}</p></div>\",\"formato\":2,\"opcoes\":{\"casasNota\":2,\"estilo\":\"oficial\",\"fonte\":\"liberation\",\"paginacao\":true},\"quadro\":{\"andamentoEmBranco\":true,\"atividades\":false,\"chNoNome\":true,\"colunas\":[{\"alinhamento\":\"left\",\"campo\":\"disciplina\",\"largura\":\"61.2%\",\"ordem\":1,\"rotulo\":\"DISCIPLINA\"},{\"alinhamento\":\"center\",\"campo\":\"nota\",\"largura\":\"18.8%\",\"ordem\":2,\"rotulo\":\"NOTA\"},{\"alinhamento\":\"center\",\"campo\":\"frequencia\",\"largura\":\"20%\",\"ordem\":3,\"rotulo\":\"FREQUÊNCIA\"}],\"emCurso\":\"Em curso\",\"frequencia\":\"sigla\",\"linhasFinais\":true,\"soVigente\":true,\"textoCh\":\"horas aula\"}}', NULL, '2026-10-01 00:00:00' FROM `rm_historico_modelos` m
 WHERE m.`nome` = 'Atestado de matrícula — desenho oficial' AND NOT EXISTS (SELECT 1 FROM `rm_historico_modelo_versoes` v WHERE v.`modelo_id` = m.`id`);
-- D-325/D-387: com o PARCIAL padrão no desenho oficial ativo, o PARCIAL detalhado de fábrica (id 1) é arquivado
-- (as emissões dele continuam válidas). Só o de fábrica: tipo PARCIAL, id 1, ainda ativo.
UPDATE `rm_historico_modelos` SET `situacao` = 3
 WHERE `id` = 1 AND `tipo` = 'PARCIAL' AND `situacao` = 2
   AND EXISTS (SELECT 1 FROM (SELECT `id` FROM `rm_historico_modelos` WHERE `nome` = 'Histórico parcial — desenho oficial' AND `situacao` = 2) x);
-- D-398: rotas da regra de média — 417 instituição (administrador; no menu Configurações), 418 curso e 419 oferta
-- (administrador e secretaria), 420 simulador (POST, devolve JSON; administrador e secretaria).
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT * FROM (SELECT 417 a, 'Regra de média' b, 'RegraMedia' c, 'instituicao' d, 'regra-media' e, 'instituicao' f, 'Regra de média da instituição' g, 'Componentes, pesos, arredondamento e simulador (E4, D-398).' h, 2 i, 'fas fa-calculator' j, 1 k, 1 l, 1 m, '2026-10-01 00:00:00' n, NULL o) t WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 417);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT * FROM (SELECT 418 a, 'Regra de média do curso' b, 'RegraMedia' c, 'curso' d, 'regra-media' e, 'curso' f, 'Regra de média do curso' g, 'Herda da instituição ou regra própria (E4).' h, 2 i, NULL j, 5 k, 1 l, 1 m, '2026-10-01 00:00:00' n, NULL o) t WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 418);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT * FROM (SELECT 419 a, 'Regra de média da oferta' b, 'RegraMedia' c, 'oferta' d, 'regra-media' e, 'oferta' f, 'Regra de média da oferta' g, 'Herda do curso ou regra própria (E4).' h, 2 i, NULL j, 5 k, 1 l, 1 m, '2026-10-01 00:00:00' n, NULL o) t WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 419);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT * FROM (SELECT 420 a, 'Simular a regra de média' b, 'RegraMedia' c, 'simular' d, 'regra-media' e, 'simular' f, 'Simulador da regra de média' g, 'POST; devolve JSON. Chama o mesmo motor (core/MotorMedia.php).' h, 2 i, NULL j, 3 k, 1 l, 1 m, '2026-10-01 00:00:00' n, NULL o) t WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 420);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`)
SELECT CASE WHEN n.id = 1 OR (n.id = 4 AND p.id <> 417) THEN 1 ELSE 2 END,
       CASE WHEN p.id = 417 THEN 281 ELSE 0 END, 1,
       CASE WHEN p.id = 417 AND n.id = 1 THEN 1 ELSE 2 END,
       CASE WHEN p.id = 417 THEN 4 ELSE 7 END, n.id, p.id, '2026-10-01 00:00:00', NULL
  FROM `adms_niveis_acessos` n CROSS JOIN (SELECT 417 id UNION ALL SELECT 418 UNION ALL SELECT 419 UNION ALL SELECT 420) p
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.id AND x.`adms_pagina_id` = p.id)
 ORDER BY p.id, n.id;
-- Versão 1.13.0 (a 1.12.0 passa a anterior) e as novidades 82 a 86.
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.13.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 17, '1.13.0', 1, 13, 0, '2026-10-01', 'Motor de média: regra por instituição, curso e oferta, com componentes, pesos, modos de agregação e arredondamento; simulador; memória de cálculo; fechamento com a regra guardada e reabertura que não apaga. Modelos padrão no desenho oficial.', 1, '2026-10-01 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.13.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.13.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT n.* FROM (
  SELECT 82 id, 17 v, 'Regra de média com componentes' t, 'Em Configurações → Regra de média, a instituição define como a média é calculada: componentes (ex.: Questionários, Trabalho, Prova) com pesos que somam 100, o modo de cada um (aritmética, ponderada, soma, maior, menor, descartar as menores, manter as maiores), o arredondamento e a nota mínima. O curso e a oferta podem ter regra própria ou herdar.' x, 1 o, 'administrador' p, 'configurar-a-regra-de-media' g, '2026-10-01 00:00:00' c
  UNION ALL SELECT 83, 17, 'Simulador da regra', 'Na tela da regra, digite notas de teste e veja a média e a memória de cálculo na hora, calculadas pelo mesmo motor do sistema.', 2, 'administrador,secretaria', 'configurar-a-regra-de-media', '2026-10-01 00:00:00'
  UNION ALL SELECT 84, 17, 'Encerrar guarda a regra; reabrir não apaga', 'O encerramento grava a regra usada e a conta de cada aluno: mudar a regra depois não altera o que foi fechado. Reabrir pede justificativa e guarda o fechamento anterior como substituído.', 3, 'administrador,secretaria', 'encerrar-uma-disciplina', '2026-10-01 00:00:00'
  UNION ALL SELECT 85, 17, 'Componente e peso na atividade', 'Ao cadastrar uma atividade, escolha o componente da regra (ex.: Prova). O peso aparece quando o componente usa média ponderada.', 4, 'administrador,secretaria', 'configurar-a-regra-de-media', '2026-10-01 00:00:00'
  UNION ALL SELECT 86, 17, 'Como foi calculado', 'No boletim, cada disciplina mostra, recolhida, a conta da sua média.', 1, 'aluno', 'ler-o-boletim', '2026-10-01 00:00:00'
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` a WHERE a.`id` = n.id);
-- <<< E3/E4 1.13.0

-- >>> E7/E8/E9 1.14.0 (gerado de database/atualizacoes/1.14.0_chamada_importacao.sql: chamada, importação e sobras)
-- D-409: rotas — 421 chamada da oferta (lista de encontros), 422 encontro (fazer/editar a chamada), 423 excluir
-- encontro (POST), 424 trocar a fonte da frequência (POST), 425 importar faltas (POST), 426 modelo de planilha.
-- Mesmas permissões de quem lança faltas (rota 105 faltas/editar).
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT n.* FROM (
  SELECT 421 a, 'Chamada' b, 'Chamada' c, 'listar' d, 'chamada' e, 'listar' f, 'Chamada da oferta' g, 'Encontros da oferta e a fonte da frequência (E7).' h, 2 i, 'fas fa-clipboard-check' j, 1 k, 1 l, 1 m, '2026-10-01 00:00:00' cr, NULL md
  UNION ALL SELECT 422, 'Fazer a chamada', 'Chamada', 'encontro', 'chamada', 'encontro', 'Encontro da chamada', 'Data, horário e presenças (E7).', 2, NULL, 1, 1, 1, '2026-10-01 00:00:00', NULL
  UNION ALL SELECT 423, 'Excluir encontro', 'Chamada', 'excluir', 'chamada', 'excluir', 'Excluir encontro', 'POST (E7).', 2, NULL, 3, 1, 1, '2026-10-01 00:00:00', NULL
  UNION ALL SELECT 424, 'Fonte da frequência', 'Chamada', 'fonte', 'chamada', 'fonte', 'Trocar a fonte da frequência', 'POST; recusada com registro da outra fonte (E7).', 2, NULL, 3, 1, 1, '2026-10-01 00:00:00', NULL
  UNION ALL SELECT 425, 'Importar faltas', 'Faltas', 'importar', 'faltas', 'importar', 'Importar faltas (CSV rm;faltas)', 'POST; só na fonte lancamento (E8).', 2, NULL, 3, 1, 1, '2026-10-01 00:00:00', NULL
  UNION ALL SELECT 426, 'Modelo de planilha', 'Faltas', 'modelo', 'faltas', 'modelo', 'Modelo de planilha (notas ou faltas)', 'CSV com o cabeçalho e uma linha fictícia (E8).', 2, NULL, 3, 1, 1, '2026-10-01 00:00:00', NULL
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = n.a);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`)
SELECT b.`permissao`, 0, 2, 2, b.`adms_menu_id`, b.`adms_niveis_acesso_id`, p.id, '2026-10-01 00:00:00', NULL
  FROM `adms_nivacs_pgs` b CROSS JOIN (SELECT 421 id UNION ALL SELECT 422 UNION ALL SELECT 423 UNION ALL SELECT 424 UNION ALL SELECT 425 UNION ALL SELECT 426) p
 WHERE b.`adms_pagina_id` = 105
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = b.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.id)
 ORDER BY p.id, b.`adms_niveis_acesso_id`;
-- G24 (D-417): sem período, os tipos de atividade não falam em mês nem bimestre. Só os que ninguém usa mudam.
UPDATE `rm_disciplina_atividade_tipo` t SET t.`nome` = 'Prova', t.`nomeAtividade` = 'Prova'
 WHERE t.`id` = 1 AND t.`nome` = 'Prova Mensal' AND NOT EXISTS (SELECT 1 FROM (SELECT `tipo` FROM `rm_disciplina_atividade`) a WHERE a.`tipo` = 1);
UPDATE `rm_disciplina_atividade_tipo` t SET t.`situacao` = 2
 WHERE t.`id` = 2 AND t.`nome` = 'Prova Bimestral' AND t.`situacao` = 1 AND NOT EXISTS (SELECT 1 FROM (SELECT `tipo` FROM `rm_disciplina_atividade`) a WHERE a.`tipo` = 2);
-- Versão 1.14.0 (a 1.13.0 passa a anterior) e as novidades 87 a 91.
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.14.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 18, '1.14.0', 1, 14, 0, '2026-10-01', 'Chamada por encontro (fonte da frequência por oferta), importação de notas e faltas por planilha em transação (tudo ou nada), aviso de emissão anterior a uma reabertura e as sobras da revisão.', 1, '2026-10-01 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.14.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.14.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT n.* FROM (
  SELECT 87 id, 18 v, 'Chamada por encontro' t, 'Na barra da disciplina, Chamada: escolha a fonte da frequência (chamada ou lançamento de faltas), registre cada encontro com a data e marque quem faltou. As faltas e o total de encontros saem da chamada.' x, 1 o, 'administrador,secretaria,professor' p, 'fazer-a-chamada' g, '2026-10-01 00:00:00' c
  UNION ALL SELECT 88, 18, 'Planilha de notas: tudo ou nada', 'A importação de notas (rm;nota) agora grava tudo ou nada e lista os erros por linha (RA inexistente, aluno fora da oferta, nota inválida, RA repetido). Aceita vírgula ou ponto e arquivos do Excel.', 2, 'administrador,secretaria,professor', 'lancar-notas', '2026-10-01 00:00:00'
  UNION ALL SELECT 89, 18, 'Planilha de faltas', 'Em Faltas, importe um CSV rm;faltas (só nas disciplinas com lançamento de faltas). Baixe o modelo na própria tela.', 3, 'administrador,secretaria,professor', 'lancar-faltas', '2026-10-01 00:00:00'
  UNION ALL SELECT 90, 18, 'Aviso de emissão anterior a uma reabertura', 'Se uma disciplina do aluno foi reaberta depois de um histórico emitido, a emissão e a lista de emitidos avisam: confira se precisa emitir de novo. O documento emitido continua válido.', 4, 'administrador,secretaria', 'emitir-historico-parcial', '2026-10-01 00:00:00'
  UNION ALL SELECT 91, 18, 'E-mail inválido', 'Um e-mail mal digitado agora aparece como "E-mail inválido" ao salvar, e não como campo por preencher.', 5, 'administrador,secretaria', NULL, '2026-10-01 00:00:00'
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` a WHERE a.`id` = n.id);
-- <<< E7/E8/E9 1.14.0

-- >>> E10 1.15.0 (gerado de database/atualizacoes/1.15.0_componente_obrigatorio.sql: componente obrigatório e limite de atividades)
-- Versão 1.15.0 (a 1.14.0 passa a anterior) e as novidades 92 a 94.
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.15.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 19, '1.15.0', 1, 15, 0, '2026-10-02', 'Componente obrigatório e limite de atividades por componente na regra de média: sem a avaliação obrigatória a média fica pendente e a disciplina não encerra; o máximo é conferido no cadastro de atividade.', 1, '2026-10-02 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.15.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.15.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT n.* FROM (
  SELECT 92 id, 19 v, 'Componente obrigatório na regra de média' t, 'Na Regra de média, marque "Obrigatório" no componente que toda disciplina precisa ter (a prova, por exemplo). Sem atividade nele, a média fica "Pendente" e a disciplina não encerra; o peso dele não passa para os outros componentes.' x, 1 o, 'administrador,secretaria' p, 'configurar-a-regra-de-media' g, '2026-10-02 00:00:00' c
  UNION ALL SELECT 93, 19, 'Limite de atividades por componente', 'Cada componente pode ter um máximo de atividades por disciplina (vazio = sem limite). O cadastro de atividade recusa a que passaria do máximo e mostra o componente cheio como "(2/2)".', 2, 'administrador,secretaria,professor', 'configurar-a-regra-de-media', '2026-10-02 00:00:00'
  UNION ALL SELECT 94, 19, 'Falta cadastrar', 'A tela da disciplina, a de atividades e a de notas avisam quando falta cadastrar atividade num componente obrigatório, por exemplo "Falta cadastrar: Prova (mínimo 1)".', 3, 'administrador,secretaria,professor', 'encerrar-uma-disciplina', '2026-10-02 00:00:00'
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` a WHERE a.`id` = n.id);
-- <<< E10 1.15.0

-- >>> 1.15.1 (gerado de database/atualizacoes/1.15.1_herdar_memoria.sql: ajustes da E10)
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.15.1' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 20, '1.15.1', 1, 15, 1, '2026-10-02', '"Voltar a herdar" avisa as disciplinas abertas fora dos limites da regra herdada; a memória de cálculo diz "sem nota do aluno" quando o componente tem atividade.', 1, '2026-10-02 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.15.1');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.15.1';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT n.* FROM (
  SELECT 95 id, 20 v, 'Voltar a herdar com aviso' t, 'Ao voltar a herdar a regra de média (curso ou oferta), o sistema avisa as disciplinas abertas que ficam fora dos limites da regra herdada, como já fazia ao salvar. A conta da média passa a dizer "sem nota do aluno" quando o componente tem atividade e o aluno não tem nota.' x, 1 o, 'administrador,secretaria' p, 'configurar-a-regra-de-media' g, '2026-10-02 00:00:00' c
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` a WHERE a.`id` = n.id);
-- <<< 1.15.1

-- >>> 1.15.2 (gerado de database/atualizacoes/1.15.2_tela_regra.sql: clareza da tela Regra de média)
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.15.2' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 21, '1.15.2', 1, 15, 2, '2026-10-02', 'Tela Regra de média mais clara: o simulador segue as travas do cadastro, pendência tem prioridade no resultado ("Pendente — motivo", com média provisória), colunas "Quantos" e "Peso" só quando usadas, rótulos revistos.', 1, '2026-10-02 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.15.2');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.15.2';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT n.* FROM (
  SELECT 96 id, 21 v, 'Simulador da regra mais fiel' t, 'O simulador da Regra de média segue as travas do cadastro (componente cheio aparece "(1/1)" e não pode ser escolhido). Faltando a atividade obrigatória ou passando do máximo, o resultado é "Pendente — motivo", com a média provisória, no simulador, na tela de notas e no encerramento.' x, 1 o, 'administrador,secretaria' p, 'configurar-a-regra-de-media' g, '2026-10-02 00:00:00' c
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` a WHERE a.`id` = n.id);
-- <<< 1.15.2

-- >>> 1.16.0 (gerado de database/atualizacoes/1.16.0_catalogo_regras.sql: catálogo de regras de média — os dados e as rotas;
-- numa instalação limpa, a regra da instituição vira o "Padrão da instituição")
-- ---------------------------------------------------------------- migração (D-445), uma vez só
SET @vcostaedu_migrar := (SELECT COUNT(*) = 0 FROM `adms_versoes` WHERE `numero` = '1.16.0');

-- a regra da instituição → "Padrão da instituição"
UPDATE `rm_media_regras` SET `padrao` = 1, `nome` = COALESCE(`nome`, 'Padrão da instituição')
 WHERE @vcostaedu_migrar = 1 AND `escopo` = 'instituicao' AND `escopo_id` = 0;
-- regras próprias de OFERTA → regra da oferta (nome a partir da oferta; o nome real fica só no banco)
UPDATE `rm_media_regras` r JOIN `rm_professores_materias` o ON o.`id` = r.`escopo_id`
   SET r.`nome` = LEFT(CONCAT(IF(r.`situacao` = 1, 'Regra da oferta ', 'Regra guardada da oferta '), o.`materia`, ' — ', o.`codtur`, ' ', o.`ano`), 120)
 WHERE @vcostaedu_migrar = 1 AND r.`escopo` = 'oferta' AND r.`nome` IS NULL;
UPDATE `rm_professores_materias` o JOIN `rm_media_regras` r ON r.`escopo` = 'oferta' AND r.`escopo_id` = o.`id` AND r.`situacao` = 1
   SET o.`regra_media_id` = r.`id`
 WHERE @vcostaedu_migrar = 1 AND o.`regra_media_id` IS NULL;
-- regras próprias de CURSO → regra do catálogo associada a TODAS as disciplinas da matriz do curso
UPDATE `rm_media_regras` r JOIN `rm_cursos` c ON c.`id` = r.`escopo_id`
   SET r.`nome` = LEFT(CONCAT(IF(r.`situacao` = 1, 'Regra do curso ', 'Regra guardada do curso '), c.`nome`), 120)
 WHERE @vcostaedu_migrar = 1 AND r.`escopo` = 'curso' AND r.`nome` IS NULL;
UPDATE `uni_rh_cursos_disciplinas_grade` l JOIN `rm_cursos_grades` g ON g.`id` = l.`grade_id`
  JOIN `rm_media_regras` r ON r.`escopo` = 'curso' AND r.`escopo_id` = g.`curso_id` AND r.`situacao` = 1
   SET l.`regra_media_id` = r.`id`
 WHERE @vcostaedu_migrar = 1 AND l.`regra_media_id` IS NULL;
-- oferta do curso com regra própria cuja disciplina não leva à regra do curso: a regra do curso fica na oferta
UPDATE `rm_professores_materias` o
  JOIN `rm_media_regras` r ON r.`escopo` = 'curso' AND r.`escopo_id` = o.`curso_id` AND r.`situacao` = 1
  LEFT JOIN `uni_rh_cursos_disciplinas_grade` l ON l.`id` = o.`matriz_id`
   SET o.`regra_media_id` = r.`id`
 WHERE @vcostaedu_migrar = 1 AND o.`regra_media_id` IS NULL AND (l.`id` IS NULL OR l.`regra_media_id` IS NULL OR l.`regra_media_id` <> r.`id`);
-- oferta de curso SEM regra própria cuja disciplina ganhou regra de outro curso: continua no padrão
UPDATE `rm_professores_materias` o
  JOIN `uni_rh_cursos_disciplinas_grade` l ON l.`id` = o.`matriz_id`
  LEFT JOIN `rm_media_regras` r ON r.`escopo` = 'curso' AND r.`escopo_id` = o.`curso_id` AND r.`situacao` = 1
   SET o.`regra_media_id` = (SELECT p.`id` FROM (SELECT `id` FROM `rm_media_regras` WHERE `padrao` = 1 LIMIT 1) p)
 WHERE @vcostaedu_migrar = 1 AND o.`regra_media_id` IS NULL AND l.`regra_media_id` IS NOT NULL AND r.`id` IS NULL;
-- regra guardada (situacao 2) = INATIVA no catálogo; nome genérico para o que sobrar
UPDATE `rm_media_regras` SET `nome` = CONCAT('Regra ', `id`) WHERE @vcostaedu_migrar = 1 AND `nome` IS NULL;

-- D-443: rotas. 417 vira o catálogo; 418 (curso) redireciona ao catálogo; 419 (oferta) mostra a regra da oferta e a
-- troca; 427 editar, 428 cadastrar, 429 duplicar (POST), 430 inativar/reativar (POST), 431 padrão (POST; só
-- administrador), 432 aplicar a disciplinas (lote), 433 regra da disciplina da matriz (POST), 434 o endereço antigo
-- regra-media/instituicao (só redireciona ao catálogo).
UPDATE `adms_paginas` SET `menu_pagina` = 'Regras de média', `metodo` = 'listar', `menu_metodo` = 'listar', `nome_pagina` = 'Regras de média',
       `obs` = 'Catálogo de regras de média com nome, associadas às disciplinas (1.16.0, D-436).' WHERE `id` = 417;
UPDATE `adms_paginas` SET `obs` = 'Redireciona ao catálogo de regras de média (1.16.0).' WHERE `id` = 418;
UPDATE `adms_paginas` SET `nome_pagina` = 'Regra de média da oferta', `obs` = 'A regra da oferta (da disciplina, padrão ou própria) e a troca com justificativa (1.16.0).' WHERE `id` = 419;
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT n.* FROM (
  SELECT 427 a, 'Editar regra de média' b, 'RegraMedia' c, 'editar' d, 'regra-media' e, 'editar' f, 'Editar regra de média' g, 'Componentes, conta, simulador e onde é usada (1.16.0).' h, 2 i, NULL j, 5 k, 1 l, 1 m, '2026-10-02 00:00:00' cr, NULL md
  UNION ALL SELECT 428, 'Nova regra de média', 'RegraMedia', 'cadastrar', 'regra-media', 'cadastrar', 'Nova regra de média', 'Cria uma regra no catálogo (1.16.0).', 2, NULL, 1, 1, 1, '2026-10-02 00:00:00', NULL
  UNION ALL SELECT 429, 'Duplicar regra de média', 'RegraMedia', 'duplicar', 'regra-media', 'duplicar', 'Duplicar regra de média', 'POST: cópia sem associações (1.16.0).', 2, NULL, 3, 1, 1, '2026-10-02 00:00:00', NULL
  UNION ALL SELECT 430, 'Inativar regra de média', 'RegraMedia', 'inativar', 'regra-media', 'inativar', 'Inativar ou reativar regra de média', 'POST: só sem uso (1.16.0).', 2, NULL, 3, 1, 1, '2026-10-02 00:00:00', NULL
  UNION ALL SELECT 431, 'Padrão da instituição', 'RegraMedia', 'padrao', 'regra-media', 'padrao', 'Trocar o padrão da instituição', 'POST com confirmação; só administrador (1.16.0).', 2, NULL, 3, 1, 1, '2026-10-02 00:00:00', NULL
  UNION ALL SELECT 432, 'Aplicar a disciplinas', 'RegraMedia', 'aplicar', 'regra-media', 'aplicar', 'Aplicar regra de média a disciplinas', 'Em lote, com filtro por curso e grade e confirmação (1.16.0).', 2, NULL, 5, 1, 1, '2026-10-02 00:00:00', NULL
  UNION ALL SELECT 433, 'Regra da disciplina', 'RegraMedia', 'disciplina', 'regra-media', 'disciplina', 'Regra de média da disciplina da matriz', 'POST na tela da matriz (1.16.0).', 2, NULL, 3, 1, 1, '2026-10-02 00:00:00', NULL
  UNION ALL SELECT 434, 'Regra de média (endereço antigo)', 'RegraMedia', 'instituicao', 'regra-media', 'instituicao', 'Regra de média da instituição (endereço antigo)', 'Só redireciona ao catálogo (1.16.0).', 2, NULL, 3, 1, 1, '2026-10-02 00:00:00', NULL
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = n.a);
-- permissões: 427 a 430, 432 e 433 como a 418 (administrador e secretaria); 431 como a 417 (só administrador)
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`)
SELECT b.`permissao`, 0, 1, 2, 7, b.`adms_niveis_acesso_id`, p.id, '2026-10-02 00:00:00', NULL
  FROM `adms_nivacs_pgs` b CROSS JOIN (SELECT 427 id, 418 base UNION ALL SELECT 428, 418 UNION ALL SELECT 429, 418 UNION ALL SELECT 430, 418
       UNION ALL SELECT 431, 417 UNION ALL SELECT 432, 418 UNION ALL SELECT 433, 418 UNION ALL SELECT 434, 418) p
 WHERE b.`adms_pagina_id` = p.base
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = b.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.id)
 ORDER BY p.id, b.`adms_niveis_acesso_id`;
-- o catálogo (417) passa a valer também para a secretaria (sem item de menu): ela já editava as regras de curso e oferta
UPDATE `adms_nivacs_pgs` SET `permissao` = 1 WHERE `adms_pagina_id` = 417 AND `adms_niveis_acesso_id` = 4 AND @vcostaedu_migrar = 1;

-- Versão 1.16.0 (a 1.15.2 passa a anterior) e as novidades 97 a 99.
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.16.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 22, '1.16.0', 1, 16, 0, '2026-10-02', 'Catálogo de regras de média com nome, associadas às disciplinas da matriz (uma a uma ou em lote), com a regra padrão da instituição e a troca na oferta aberta com justificativa. Sai a regra por nível (instituição, curso, oferta).', 1, '2026-10-02 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.16.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.16.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT n.* FROM (
  SELECT 97 id, 22 v, 'Catálogo de regras de média' t, 'Em Configurações → Regras de média, crie quantas regras quiser, cada uma com nome (ex.: "Atividades 30% · Prova 70%"), e veja onde cada uma é usada. Uma delas é o Padrão da instituição, que vale para toda disciplina sem regra.' x, 1 o, 'administrador,secretaria' p, 'configurar-a-regra-de-media' g, '2026-10-02 00:00:00' c
  UNION ALL SELECT 98, 22, 'Regra na disciplina da matriz', 'Associe a regra à disciplina na tela da matriz, ou a várias de uma vez em "Aplicar a disciplinas…", na tela da regra. Agrupar por curso, tipo ou módulo é só aplicar a mesma regra às disciplinas desse grupo.', 2, 'administrador,secretaria', 'configurar-a-regra-de-media', '2026-10-02 00:00:00'
  UNION ALL SELECT 99, 22, 'Regra da oferta', 'Na barra da disciplina, Regra de média mostra a regra que vale e de onde vem (da disciplina, padrão ou própria desta oferta). Com a oferta aberta, dá para trocar, com justificativa.', 3, 'administrador,secretaria', 'configurar-a-regra-de-media', '2026-10-02 00:00:00'
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` a WHERE a.`id` = n.id);
-- <<< 1.16.0

-- >>> 1.16.1 (gerado de database/atualizacoes/1.16.1_regra_do_curso.sql: a regra do TCC e dos documentos, no curso)
-- A rota 418 (regra-media/curso) passa a gravar o campo (POST, na tela do curso); o GET continua levando ao catálogo.
UPDATE `adms_paginas` SET `nome_pagina` = 'Regra de média do TCC e dos documentos', `obs` = 'POST na tela do curso: a regra do TCC e dos documentos (1.16.1); GET leva ao catálogo.' WHERE `id` = 418;
-- Versão 1.16.1 (a 1.16.0 passa a anterior) e a novidade 100.
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.16.1' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 23, '1.16.1', 1, 16, 1, '2026-10-02', 'No curso, a regra de média do TCC e dos documentos (nota mínima da aprovação do TCC e legenda dos documentos); vazio = o Padrão da instituição.', 1, '2026-10-02 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.16.1');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.16.1';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT n.* FROM (
  SELECT 100 id, 23 v, 'Regra do TCC e dos documentos, no curso' t, 'Na tela do curso, escolha a regra de média que vale para a aprovação do TCC (nota mínima) e para a legenda dos documentos do curso. Vazio = o Padrão da instituição.' x, 1 o, 'administrador,secretaria' p, 'configurar-a-regra-de-media' g, '2026-10-02 00:00:00' c
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` a WHERE a.`id` = n.id);
-- <<< 1.16.1

-- >>> 1.16.2 (gerado de database/atualizacoes/1.16.2_divergencia_nota_minima.sql: divergência da nota mínima)
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.16.2' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 24, '1.16.2', 1, 16, 2, '2026-10-03', 'A nota mínima da legenda dos documentos tem de ser a exigida pelas disciplinas: aviso na tela do curso e alerta no histórico (no FINAL, bloqueia) quando divergem.', 1, '2026-10-03 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.16.2');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.16.2';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT n.* FROM (
  SELECT 101 id, 24 v, 'Nota mínima coerente nos documentos' t, 'Se alguma disciplina exige uma nota mínima diferente da que a legenda dos documentos vai dizer, a tela do curso avisa (com a lista e o link para cada regra) e o histórico mostra um alerta; o FINAL só sai depois de alinhar.' x, 1 o, 'administrador,secretaria' p, 'configurar-a-regra-de-media' g, '2026-10-03 00:00:00' c
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` a WHERE a.`id` = n.id);
-- <<< 1.16.2

-- >>> 1.17.0 (gerado de database/atualizacoes/1.17.0_relatorios.sql: menu Relatórios, rotas 435 a 455, permissões, parâmetros do risco)
INSERT INTO `adms_menus` (`id`, `nome`, `icone`, `ordem`, `adms_sit_id`, `created`)
SELECT 10, 'Relatórios', 'fas fa-chart-bar', 5, 1, '2026-10-03 00:00:00' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_menus` WHERE `id` = 10);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT n.* FROM (
  SELECT 435 a, 'Todos os relatórios' b, 'Relatorios' c, 'listar' d, 'relatorios' e, 'listar' f, 'Relatórios' g, 'Os relatórios do perfil, por categoria, e os salvos (1.17.0).' h, 2 i, 'fas fa-chart-bar' j, 1 k, 1 l, 1 m, '2026-10-03 00:00:00' cr, NULL md
  UNION ALL SELECT 436, 'Salvar relatório', 'Relatorios', 'salvar', 'relatorios', 'salvar', 'Salvar relatório', 'POST: relatório + filtros + colunas (1.17.0).', 2, NULL, 3, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 437, 'Apagar relatório salvo', 'Relatorios', 'apagarSalvo', 'relatorios', 'apagar-salvo', 'Apagar relatório salvo', 'POST: só o dono (1.17.0).', 2, NULL, 3, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 438, 'Registrar exportação', 'Relatorios', 'exportar', 'relatorios', 'exportar', 'Registrar exportação de relatório', 'POST (JSON): auditoria de exportação (1.17.0).', 2, NULL, 3, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 439, 'Alerta de risco', 'Relatorios', 'parametros', 'relatorios', 'parametros', 'Parâmetros do alerta de risco', 'Os limites dos sinais do R-EVA-01 (1.17.0).', 2, NULL, 1, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 440, 'Ver CPF completo em relatórios', 'Relatorios', 'cpfCompleto', 'relatorios', 'cpf-completo', 'Ver CPF completo em relatórios', 'Só permissão (sem tela): quem tem vê o CPF completo nos relatórios e nas exportações (1.17.0).', 2, NULL, 3, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 441, 'Painel acadêmico', 'Relatorios', 'painelAcademico', 'relatorios', 'painel-academico', 'R-PAI-01 Painel acadêmico', 'Relatório (1.17.0).', 2, 'fas fa-tachometer-alt', 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 442, 'Alunos por curso e situação', 'Relatorios', 'alunosSituacao', 'relatorios', 'alunos-situacao', 'R-SEC-01 Alunos por curso e situação', 'Relatório (1.17.0).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 443, 'Lista de turma', 'Relatorios', 'listaTurma', 'relatorios', 'lista-turma', 'R-SEC-02 Lista de turma', 'Relatório (1.17.0).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 444, 'Previsão de conclusão', 'Relatorios', 'previsaoConclusao', 'relatorios', 'previsao-conclusao', 'R-SEC-03 Previsão de conclusão', 'Relatório (1.17.0).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 445, 'Qualidade do cadastro', 'Relatorios', 'qualidadeCadastro', 'relatorios', 'qualidade-cadastro', 'R-SEC-04 Qualidade do cadastro', 'Relatório (1.17.0).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 446, 'Mapa de notas da oferta', 'Relatorios', 'mapaNotas', 'relatorios', 'mapa-notas', 'R-DES-01 Mapa de notas da oferta', 'Relatório (1.17.0).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 447, 'Resultado por disciplina', 'Relatorios', 'resultadoDisciplina', 'relatorios', 'resultado-disciplina', 'R-DES-02 Resultado por disciplina', 'Relatório (1.17.0).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 448, 'Distribuição de notas', 'Relatorios', 'distribuicaoNotas', 'relatorios', 'distribuicao-notas', 'R-DES-03 Distribuição de notas', 'Relatório (1.17.0).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 449, 'Pendências de lançamento', 'Relatorios', 'pendenciasLancamento', 'relatorios', 'pendencias-lancamento', 'R-DES-04 Pendências de lançamento', 'Relatório (1.17.0).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 450, 'Desempenho do aluno', 'Relatorios', 'desempenhoAluno', 'relatorios', 'desempenho-aluno', 'R-DES-05 Desempenho do aluno', 'Relatório (1.17.0).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 451, 'Frequência da oferta', 'Relatorios', 'frequenciaOferta', 'relatorios', 'frequencia-oferta', 'R-FRE-01 Frequência da oferta', 'Relatório (1.17.0).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 452, 'Abaixo ou perto do mínimo', 'Relatorios', 'frequenciaMinimo', 'relatorios', 'frequencia-minimo', 'R-FRE-02 Alunos abaixo ou perto do mínimo', 'Relatório (1.17.0).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 453, 'Alunos em risco', 'Relatorios', 'alunosRisco', 'relatorios', 'alunos-risco', 'R-EVA-01 Alunos em risco', 'Relatório (1.17.0).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 454, 'Aptos a concluir', 'Relatorios', 'aptosConcluir', 'relatorios', 'aptos-concluir', 'R-EVA-03 Aptos a concluir', 'Relatório (1.17.0).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 455, 'Situação do TCC', 'Relatorios', 'situacaoTcc', 'relatorios', 'situacao-tcc', 'R-TCC-01 Situação do TCC', 'Relatório (1.17.0).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = n.a);
-- Permissões (D-455): quem acessa cada relatório, pelo catálogo. Níveis: 1 Super Administrador, 2 Administrador, 3 Diretoria,
-- 4 Secretaria, 5 Coordenação, 6 Professor (7 a 9: suporte, sem relatórios). Menu "Relatórios" (10): o hub e o painel.
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`)
SELECT IF(FIND_IN_SET(n.id, p.niveis) > 0, 1, 2), p.ordem, 1, IF(p.menu = 1 AND FIND_IN_SET(n.id, p.niveis) > 0, 1, 2), 10, n.id, p.id, '2026-10-03 00:00:00', NULL
  FROM `adms_niveis_acessos` n CROSS JOIN (
    SELECT 435 id, '1,2,3,4,5,6' niveis, 1 menu, 1 ordem
    UNION ALL SELECT 436, '1,2,3,4,5,6', 0, 0 UNION ALL SELECT 437, '1,2,3,4,5,6', 0, 0 UNION ALL SELECT 438, '1,2,3,4,5,6', 0, 0
    UNION ALL SELECT 439, '1,2', 0, 0 UNION ALL SELECT 440, '1,2,4', 0, 0
    UNION ALL SELECT 441, '1,2,3,4,5', 1, 2
    UNION ALL SELECT 442, '1,2,3,4,5', 0, 0 UNION ALL SELECT 443, '1,2,4,5,6', 0, 0 UNION ALL SELECT 444, '1,2,3,4,5', 0, 0
    UNION ALL SELECT 445, '1,2,4', 0, 0 UNION ALL SELECT 446, '1,2,4,5,6', 0, 0 UNION ALL SELECT 447, '1,2,3,4,5', 0, 0
    UNION ALL SELECT 448, '1,2,3,4,5', 0, 0 UNION ALL SELECT 449, '1,2,4,5', 0, 0 UNION ALL SELECT 450, '1,2,4,5', 0, 0
    UNION ALL SELECT 451, '1,2,4,5,6', 0, 0 UNION ALL SELECT 452, '1,2,4,5', 0, 0 UNION ALL SELECT 453, '1,2,3,4,5', 0, 0
    UNION ALL SELECT 454, '1,2,4', 0, 0 UNION ALL SELECT 455, '1,2,4,5', 0, 0
  ) p
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.id AND x.`adms_pagina_id` = p.id)
 ORDER BY p.id, n.id;
-- Parâmetros do alerta de risco (R-EVA-01), com os padrões propostos [DECIDIR] no RELATORIOS.md
INSERT INTO `adms_parametros` (`chave`, `valor`, `descricao`, `grupo`, `editavel`, `created`)
SELECT n.* FROM (
  SELECT 'risco.faltas_margem' c, '2' v, 'Alerta de risco: faltas que o aluno ainda pode ter, a partir das quais o sinal "frequência" dispara' d, 'Alerta de risco' g, 1 e, '2026-10-03 00:00:00' cr
  UNION ALL SELECT 'risco.min_notas', '1', 'Alerta de risco: notas lançadas mínimas para o sinal "média provisória abaixo da mínima"', 'Alerta de risco', 1, '2026-10-03 00:00:00'
  UNION ALL SELECT 'risco.sem_nota_pct', '50', 'Alerta de risco: % de atividades sem nota (nas ofertas abertas) a partir do qual o sinal dispara', 'Alerta de risco', 1, '2026-10-03 00:00:00'
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_parametros` a WHERE a.`chave` = n.c);
-- Versão 1.17.0 (a 1.16.2 passa a anterior) e as novidades 102 e 103.
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.17.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 25, '1.17.0', 1, 17, 0, '2026-10-03', 'Menu Relatórios: painel acadêmico, secretaria e cadastro, desempenho, frequência, alunos em risco, aptos a concluir e TCC — cada relatório responde a uma pergunta de decisão, com a ficha técnica, drill-down, exportação auditada e relatórios salvos.', 1, '2026-10-03 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.17.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.17.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT n.* FROM (
  SELECT 102 id, 25 v, 'Menu Relatórios' t, 'No novo menu Relatórios, cada relatório responde a uma pergunta (ex.: "onde estão os gargalos?", "quem vai reprovar por falta?"), mostra como é calculado e abre a lista que compõe cada número. Exporte para Excel, CSV ou PDF e salve os filtros com um nome.' x, 1 o, 'administrador,secretaria,professor' p, 'relatorios-visao-geral' g, '2026-10-03 00:00:00' c
  UNION ALL SELECT 103, 25, 'Alunos em risco', 'O relatório Alunos em risco lista quem precisa de atenção e por quê (frequência perto do mínimo, média abaixo da mínima, atividades sem nota, reprovação anterior, sem enturmação). Os limites ficam em Relatórios → Alerta de risco.', 2, 'administrador,secretaria', 'relatorios-visao-geral', '2026-10-03 00:00:00'
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` a WHERE a.`id` = n.id);
-- <<< 1.17.0

-- >>> 1.17.1 (gerado de database/atualizacoes/1.17.1_relatorios_onda2.sql: relatórios da onda 2 e o "Meu progresso" do portal)
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT n.* FROM (
  SELECT 456 a, 'Coorte de ingresso' b, 'Relatorios' c, 'coorteIngresso' d, 'relatorios' e, 'coorte-ingresso' f, 'R-EVA-02 Coorte de ingresso' g, 'Relatório (1.17.1).' h, 2 i, NULL j, 5 k, 1 l, 1 m, '2026-10-03 00:00:00' cr, NULL md
  UNION ALL SELECT 457, 'Concluintes', 'Relatorios', 'concluintes', 'relatorios', 'concluintes', 'R-EVA-04 Concluintes e tempo até a conclusão', 'Relatório (1.17.1).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 458, 'Documentos emitidos', 'Relatorios', 'documentosEmitidos', 'relatorios', 'documentos-emitidos', 'R-DOC-01 Documentos emitidos', 'Relatório (1.17.1).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 459, 'Reaberturas de oferta', 'Relatorios', 'reaberturas', 'relatorios', 'reaberturas', 'R-AUD-01 Reaberturas de oferta', 'Relatório (1.17.1).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 460, 'Trocas de regra de média', 'Relatorios', 'trocasRegra', 'relatorios', 'trocas-regra', 'R-AUD-02 Trocas e edições de regra de média', 'Relatório (1.17.1).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 461, 'Exportações de relatórios', 'Relatorios', 'exportacoes', 'relatorios', 'exportacoes', 'R-AUD-03 Exportações de relatórios', 'Relatório (1.17.1).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
  UNION ALL SELECT 462, 'Usuários e último acesso', 'Relatorios', 'usuariosAcesso', 'relatorios', 'usuarios-acesso', 'R-AUD-04 Usuários e último acesso', 'Relatório (1.17.1).', 2, NULL, 5, 1, 1, '2026-10-03 00:00:00', NULL
) n WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = n.a);
-- Permissões (catálogo): coorte e concluintes = administrador e diretoria; documentos = administrador e secretaria;
-- auditoria = administrador (níveis 1 e 2).
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`)
SELECT IF(FIND_IN_SET(n.id, p.niveis) > 0, 1, 2), 0, 1, 2, 10, n.id, p.id, '2026-10-03 00:00:00', NULL
  FROM `adms_niveis_acessos` n CROSS JOIN (
    SELECT 456 id, '1,2,3' niveis UNION ALL SELECT 457, '1,2,3' UNION ALL SELECT 458, '1,2,4'
    UNION ALL SELECT 459, '1,2' UNION ALL SELECT 460, '1,2' UNION ALL SELECT 461, '1,2' UNION ALL SELECT 462, '1,2'
  ) p
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.id AND x.`adms_pagina_id` = p.id)
 ORDER BY p.id, n.id;
-- Portal: Acadêmico → Meu progresso (nível Estudante)
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`)
SELECT 24, 'Meu progresso', 'Progresso', 'index', 'progresso', 'index', 'Meu progresso', '1.17.1 (R-POR-01): o progresso do aluno da sessão no curso.', 2, 'fas fa-chart-line', 1, 1, 1, '2026-10-03 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `col_paginas` WHERE `id` = 24);
INSERT INTO `col_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 1, 13, 1, 1, 2, 1, 24, '2026-10-03 00:00:00' FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `col_nivacs_pgs` WHERE `adms_niveis_acesso_id` = 1 AND `adms_pagina_id` = 24);
-- Versão 1.17.1 e a novidade 104
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.17.1' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 26, '1.17.1', 1, 17, 1, '2026-10-03', 'Relatórios, onda 2: coorte de ingresso, concluintes e tempo até a conclusão, documentos emitidos, auditoria (reaberturas, trocas de regra, exportações, usuários e último acesso) e o "Meu progresso" no portal do aluno.', 1, '2026-10-03 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.17.1');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.17.1';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 104, 26, 'Mais relatórios e o "Meu progresso"', 'O menu Relatórios ganhou coorte de ingresso, concluintes e tempo até a conclusão, documentos emitidos (com a marca dos emitidos antes de uma reabertura) e a auditoria (reaberturas, trocas de regra, exportações e último acesso). No portal, o aluno vê o próprio progresso no curso em Acadêmico → Meu progresso.', 1, 'administrador,secretaria,aluno', 'relatorios-visao-geral', '2026-10-03 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 104);
-- <<< 1.17.1

-- >>> 1.17.2 (gerado de database/atualizacoes/1.17.2_encontros_previstos.sql: encontros previstos e relatórios em lote)
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.17.2' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 27, '1.17.2', 1, 17, 2, '2026-10-04', 'Encontros previstos na oferta por chamada (base de "faltas que ainda pode ter" e do alerta de risco; a frequência oficial não muda), aviso ao encerrar com encontros a menos e os aptos a concluir calculados em lote.', 1, '2026-10-04 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.17.2');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.17.2';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 105, 27, 'Encontros previstos na chamada', 'Na oferta com a frequência pela chamada, informe os encontros previstos (Editar a disciplina). Os relatórios de frequência e o alerta de risco passam a contar as faltas que o aluno ainda pode ter sobre o total previsto, e não só sobre os encontros já registrados. A frequência do boletim e dos documentos não muda. Ao encerrar com menos encontros registrados que os previstos, aparece um aviso.', 1, 'administrador,secretaria,professor', 'relatorios-d-frequencia', '2026-10-04 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 105);
-- <<< 1.17.2

-- >>> 1.17.3 (gerado de database/atualizacoes/1.17.3_sinal_frequencia.sql: só versão e novidade)
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.17.3' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 28, '1.17.3', 1, 17, 3, '2026-10-04', 'Alerta de risco: o sinal de frequência "perto do mínimo" só dispara para quem tem ao menos uma falta não compensada (abaixo do mínimo continua disparando sempre). O relatório de frequência lista todos os alunos, com a mesma classificação.', 1, '2026-10-04 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.17.3');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.17.3';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 106, 28, 'Alerta de risco: frequência só com falta', 'O sinal de frequência do alerta de risco passou a exigir ao menos uma falta não compensada: o aluno sem nenhuma falta não entra mais como "perto do mínimo", mesmo numa disciplina curta. Quem já está abaixo do mínimo continua no alerta. O relatório Abaixo ou perto do mínimo lista todos os alunos, com quantas faltas cada um ainda pode ter.', 1, 'administrador,secretaria', 'relatorios-e-risco-conclusao', '2026-10-04 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 106);
-- <<< 1.17.3

-- >>> 1.17.4 (gerado de database/atualizacoes/1.17.4_frequencia_filtro.sql: só versão e novidade)
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.17.4' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 29, '1.17.4', 1, 17, 4, '2026-10-04', 'O relatório "Abaixo ou perto do mínimo" abre só com os alertas (abaixo, no limite e perto); "Mostrar todos" inclui os sem alerta. O filtro fica no link, vale na exportação e é registrado na auditoria.', 1, '2026-10-04 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.17.4');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.17.4';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 107, 29, 'Frequência: o relatório abre nos alertas', 'O relatório Abaixo ou perto do mínimo abre só com os alunos em alerta (abaixo, no limite e perto). Para ver todos, escolha "Mostrar todos" no filtro Mostrar. Os cartões continuam contando os quatro grupos, inclusive "Sem alerta", e cada um abre a sua lista.', 1, 'administrador,secretaria', 'relatorios-d-frequencia', '2026-10-04 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 107);
-- <<< 1.17.4

-- >>> 1.18.0 (gerado de database/atualizacoes/1.18.0_videos_guias.sql: página Vídeos na Ajuda, versão e novidade)
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT 463, 'Vídeos', 'Ajuda', 'videos', 'ajuda', 'videos', 'Vídeos dos guias', 'Os vídeos dos guias do perfil, com busca pelo título e pela transcrição (1.18.0).', 2, 'fas fa-film', 5, 1, 1, '2026-10-05 00:00:00', NULL
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 463);
-- As mesmas permissões da Central de ajuda (384), no menu Ajuda (8), depois de Novidades.
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`)
SELECT a.`permissao`, 3, 1, a.`lib_menu`, 8, a.`adms_niveis_acesso_id`, 463, '2026-10-05 00:00:00', NULL
  FROM `adms_nivacs_pgs` a
 WHERE a.`adms_pagina_id` = 384
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = a.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = 463)
 ORDER BY a.`adms_niveis_acesso_id`;
-- Portal: Ajuda → Vídeos (nível Estudante), depois de Novidades.
INSERT INTO `col_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`)
SELECT 25, 'Vídeos', 'Home', 'videos', 'home', 'videos', 'Vídeos dos guias', '1.18.0: os vídeos dos guias do aluno, com busca.', 2, 'fas fa-film', 1, 1, 1, '2026-10-05 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `col_paginas` WHERE `id` = 25);
INSERT INTO `col_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 1, 16, 1, 1, 4, 1, 25, '2026-10-05 00:00:00' FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `col_nivacs_pgs` WHERE `adms_niveis_acesso_id` = 1 AND `adms_pagina_id` = 25);
-- Versão 1.18.0 (a 1.17.4 passa a anterior) e a novidade 108.
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.18.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 30, '1.18.0', 1, 18, 0, '2026-10-05', 'Cada guia da Ajuda ganhou um vídeo curto, no topo do próprio guia, com legenda, capítulos e transcrição; a página Vídeos reúne os vídeos do perfil, com busca.', 1, '2026-10-05 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.18.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.18.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 108, 30, 'Vídeos nos guias da Ajuda', 'Cada guia da Ajuda tem agora um vídeo curto, no topo, que mostra a tarefa do começo ao fim, com legenda, capítulos (clique para pular) e a transcrição. Em Ajuda → Vídeos estão todos os vídeos do seu perfil, com busca.', 1, 'administrador,secretaria,professor,aluno', 'como-usar-os-videos-da-ajuda', '2026-10-05 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 108);
-- <<< 1.18.0

-- >>> 1.18.1 (gerado de database/atualizacoes/1.18.1_correcoes_videos.sql: só versão e novidade)
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.18.1' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 31, '1.18.1', 1, 18, 1, '2026-10-05', 'Correções encontradas na gravação dos vídeos: curso inativo fora das listas de escolha, matéria exige nome e carga horária, mensagens de erro em vermelho, listas em ordem alfabética com acentos e o filtro de ano das Disciplinas mantido.', 1, '2026-10-05 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.18.1');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.18.1';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 109, 31, 'Cadastros mais seguros', 'Curso inativo não aparece mais na escolha de uma grade nova. Matéria só é salva com nome e carga horária. As listas ficam em ordem alfabética respeitando os acentos, e o filtro de ano das Disciplinas continua no ano escolhido depois de salvar o professor.', 1, 'administrador,secretaria', 'cadastrar-materias', '2026-10-05 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 109);
-- <<< 1.18.1

-- >>> 1.18.2 (gerado de database/atualizacoes/1.18.2_seguranca_senhas.sql: só versão e novidade)
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.18.2' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 32, '1.18.2', 1, 18, 2, '2026-10-05', 'Segurança: nenhuma senha volta preenchida na tela (cadastro de usuário e configuração de e-mail). A nota recusada fica legível e volta ao valor gravado; a dica de reabrir disciplina diz que o fechamento anterior fica guardado.', 1, '2026-10-05 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.18.2');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.18.2';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 110, 32, 'Senhas protegidas e ajustes', 'Depois de um erro ao salvar, a senha digitada não volta mais preenchida: digite de novo. Na configuração de e-mail, a senha gravada não aparece; deixe o campo em branco para mantê-la. Uma nota recusada fica marcada em vermelho claro e a célula volta ao valor gravado.', 1, 'administrador,secretaria,professor', 'email-smtp', '2026-10-05 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 110);
-- <<< 1.18.2

-- >>> 1.18.3 (gerado de database/atualizacoes/1.18.3_permissoes.sql: a matriz de permissões, a ordem dos níveis, versão e novidade)

-- 1. A ordem dos níveis (menor = acima). Os da VCOSTA no alto; os criados pela tela (id > 9) seguem abaixo, na ordem
--    em que estavam.
UPDATE `adms_niveis_acessos`
   SET `ordem` = CASE `id` WHEN 1 THEN 1 WHEN 7 THEN 2 WHEN 8 THEN 3 WHEN 9 THEN 4 WHEN 2 THEN 5
                           WHEN 3 THEN 6 WHEN 4 THEN 7 WHEN 5 THEN 8 WHEN 6 THEN 9 END
 WHERE `id` BETWEEN 1 AND 9
   AND `ordem` <> CASE `id` WHEN 1 THEN 1 WHEN 7 THEN 2 WHEN 8 THEN 3 WHEN 9 THEN 4 WHEN 2 THEN 5
                            WHEN 3 THEN 6 WHEN 4 THEN 7 WHEN 5 THEN 8 WHEN 6 THEN 9 END;
UPDATE `adms_niveis_acessos` a
  JOIN (SELECT `id`, 9 + ROW_NUMBER() OVER (ORDER BY `ordem`, `id`) AS `nova`
          FROM `adms_niveis_acessos` WHERE `id` > 9) x ON x.`id` = a.`id`
   SET a.`ordem` = x.`nova`
 WHERE a.`ordem` <> x.`nova`;

-- 2. Toda página com linha em todo nível. A que falta entra NEGADA, com o menu e a ordem da linha do nível 1.
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 2, COALESCE(r.`ordem`, 0), 2, 2, COALESCE(r.`adms_menu_id`, 4), n.`id`, p.`id`, NOW()
  FROM `adms_niveis_acessos` n
 CROSS JOIN `adms_paginas` p
  LEFT JOIN (SELECT `adms_pagina_id`, MIN(`ordem`) AS `ordem`, MIN(`adms_menu_id`) AS `adms_menu_id`
               FROM `adms_nivacs_pgs` WHERE `adms_niveis_acesso_id` = 1 GROUP BY `adms_pagina_id`) r ON r.`adms_pagina_id` = p.`id`
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x
                    WHERE x.`adms_niveis_acesso_id` = n.`id` AND x.`adms_pagina_id` = p.`id`);

-- 3. Nível 1 (Administrador VCOSTA): tudo, menos as rotas fora do ar.
UPDATE `adms_nivacs_pgs` SET `permissao` = 1
 WHERE `adms_niveis_acesso_id` = 1 AND `permissao` <> 1 AND `adms_pagina_id` NOT IN (73, 74, 244, 263);
--    A manutenção no menu dele, no grupo "Suporte técnico" (menu 9), como no nível 9.
UPDATE `adms_nivacs_pgs` n
  JOIN `adms_paginas` p ON p.`id` = n.`adms_pagina_id`
   SET n.`lib_menu` = 1, n.`adms_menu_id` = 9
 WHERE n.`adms_niveis_acesso_id` = 1 AND p.`menu_metodo` = 'listar'
   AND p.`menu_controller` IN ('paginas', 'menu', 'acesso-academico', 'paginas-academico')
   AND (n.`lib_menu` <> 1 OR n.`adms_menu_id` <> 9);
UPDATE `adms_nivacs_pgs` a
  JOIN `adms_nivacs_pgs` b ON b.`adms_pagina_id` = a.`adms_pagina_id` AND b.`adms_niveis_acesso_id` = 9
  JOIN `adms_paginas` p ON p.`id` = a.`adms_pagina_id`
   SET a.`ordem` = b.`ordem`, a.`dropdown` = b.`dropdown`
 WHERE a.`adms_niveis_acesso_id` = 1 AND p.`menu_metodo` = 'listar'
   AND p.`menu_controller` IN ('paginas', 'menu', 'acesso-academico', 'paginas-academico')
   AND (a.`ordem` <> b.`ordem` OR a.`dropdown` <> b.`dropdown`);

-- 4. Nível 2 (Administrador da instituição): a mesma fileira do nível 1 (permissão, menu, ordem, dropdown)…
UPDATE `adms_nivacs_pgs` a
  JOIN `adms_nivacs_pgs` b ON b.`adms_pagina_id` = a.`adms_pagina_id` AND b.`adms_niveis_acesso_id` = 1
  JOIN `adms_paginas` p ON p.`id` = a.`adms_pagina_id`
   SET a.`permissao` = b.`permissao`, a.`lib_menu` = b.`lib_menu`, a.`dropdown` = b.`dropdown`,
       a.`adms_menu_id` = b.`adms_menu_id`, a.`ordem` = b.`ordem`
 WHERE a.`adms_niveis_acesso_id` = 2
   AND p.`menu_controller` NOT IN ('nivel-acesso', 'liberar', 'liberar-academico', 'paginas', 'paginas-academico', 'menu', 'acesso-academico')
   AND (a.`permissao` <> b.`permissao` OR a.`lib_menu` <> b.`lib_menu` OR a.`dropdown` <> b.`dropdown`
        OR a.`adms_menu_id` <> b.`adms_menu_id` OR a.`ordem` <> b.`ordem`);

-- 5. …e NINGUÉM além do nível 1 tem Níveis de acesso nem os botões de liberar; além do 1, só o Suporte técnico (9)
--    tem a manutenção (Páginas, Itens de menu, Acesso e Páginas do portal — E8d).
UPDATE `adms_nivacs_pgs` n
  JOIN `adms_paginas` p ON p.`id` = n.`adms_pagina_id`
   SET n.`permissao` = 2, n.`lib_menu` = 2
 WHERE n.`adms_niveis_acesso_id` <> 1
   AND p.`menu_controller` IN ('nivel-acesso', 'liberar', 'liberar-academico')
   AND (n.`permissao` <> 2 OR n.`lib_menu` <> 2);
UPDATE `adms_nivacs_pgs` n
  JOIN `adms_paginas` p ON p.`id` = n.`adms_pagina_id`
   SET n.`permissao` = 2, n.`lib_menu` = 2
 WHERE n.`adms_niveis_acesso_id` NOT IN (1, 9)
   AND p.`menu_controller` IN ('paginas', 'paginas-academico', 'menu', 'acesso-academico')
   AND (n.`permissao` <> 2 OR n.`lib_menu` <> 2);

-- 6. As rotas fora do ar (73, 74, 244, 263: o método não existe no controller) negadas a todos; a Secretaria sem usuarios/editar-senha.
UPDATE `adms_nivacs_pgs` SET `permissao` = 2, `lib_menu` = 2
 WHERE `adms_pagina_id` IN (73, 74, 244, 263) AND (`permissao` <> 2 OR `lib_menu` <> 2);
UPDATE `adms_nivacs_pgs` n
  JOIN `adms_paginas` p ON p.`id` = n.`adms_pagina_id`
   SET n.`permissao` = 2
 WHERE n.`adms_niveis_acesso_id` = 4 AND p.`menu_controller` = 'usuarios' AND p.`menu_metodo` = 'editar-senha'
   AND n.`permissao` <> 2;

-- 7. O menu mostra exatamente o que abre: item de menu sem permissão sai do menu.
UPDATE `adms_nivacs_pgs` SET `lib_menu` = 2 WHERE `permissao` <> 1 AND `lib_menu` = 1;

-- 8. A versão e a novidade.
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.18.3' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 33, '1.18.3', 1, 18, 3, '2026-10-05', 'Permissões por perfil: o administrador da instituição vê todo o menu da operação e da configuração acadêmica (menos Níveis de acesso e a manutenção da plataforma). Ninguém altera usuário nem nível igual ou acima do seu.', 1, '2026-10-05 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.18.3');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.18.3';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 111, 33, 'Menu completo e usuários protegidos', 'O administrador da instituição passa a ver todo o menu: cursos, matriz, regras de média, documentos, anos letivos, polos, encerramento e relatórios. Na lista de usuários, quem é de nível igual ou acima do seu aparece como "Protegido": só um nível acima dele altera. O seu cadastro e a sua senha ficam no Perfil.', 1, 'administrador', 'usuarios-e-niveis', '2026-10-05 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 111);
-- <<< 1.18.3

-- >>> 1.18.4 (gerado de database/atualizacoes/1.18.4_atualizar_dados.sql: a página 464, versão e novidade)
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `modified`)
SELECT 464, 'Atualize seus dados', 'Perfil', 'atualizarDados', 'perfil', 'atualizar-dados', 'Atualize seus dados', 'Com a situação "Atualizar Dados", a única tela liberada até o usuário salvar os dados pessoais; salvar desliga a marca (1.18.4).', 2, 'fas fa-user-edit', 3, 1, 1, '2026-10-05 00:00:00', NULL
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 464);
-- Liberada a todos os níveis (como o Perfil), fora do menu.
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`, `modified`)
SELECT 1, 0, 2, 2, 4, n.`id`, 464, '2026-10-05 00:00:00', NULL
  FROM `adms_niveis_acessos` n
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.`id` AND x.`adms_pagina_id` = 464)
 ORDER BY n.`id`;
UPDATE `adms_nivacs_pgs` SET `permissao` = 1, `lib_menu` = 2 WHERE `adms_pagina_id` = 464 AND (`permissao` <> 1 OR `lib_menu` <> 2);

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.18.4' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 34, '1.18.4', 1, 18, 4, '2026-10-05', 'Atualizar dados: o usuário marcado entra normalmente e é levado à tela "Atualize seus dados"; só depois de salvar segue para o sistema. Antes, a marca bloqueava o login.', 1, '2026-10-05 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.18.4');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.18.4';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 112, 34, '"Atualizar Dados" não bloqueia mais', 'Na tela de usuários, a situação "Atualizar Dados" faz a pessoa entrar e atualizar os dados pessoais antes de usar o sistema. Ao salvar, a marca sai sozinha. Inativo e Bloqueado continuam sem entrar.', 1, 'administrador', 'usuarios-e-niveis', '2026-10-05 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 112);
-- <<< 1.18.4

SET FOREIGN_KEY_CHECKS = 1;
