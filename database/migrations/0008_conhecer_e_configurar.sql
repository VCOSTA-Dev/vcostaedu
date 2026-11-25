-- 0008 — ONDA 2, ETAPA 4: conhecer primeiro, configurar por fases, com "Faça comigo" (versão 1.22.0).
--
-- 1. adms_instalacao_estado: o estado da instalação que não é configuração da instituição — a demonstração ligada,
--    as senhas fictícias dela (para o cartão "Conheça o sistema"), a escolha do começo, os Primeiros passos dispensados.
-- 2. O MOTOR DE FASES dos Primeiros passos: as fases são DADO (adms_fases) — a ordem, o texto, a tela, o roteiro do
--    "Faça comigo", de quais fases cada uma depende e a CONDIÇÃO DE CONCLUÍDA (uma consulta que conta no banco o que a
--    fase pede; maior que zero = concluída). Fase nova (de um módulo futuro) entra por migration, sem mexer no motor.
--    adms_fases_estado guarda só o que é da pessoa: pulada, e quando a conclusão foi vista.
-- 3. As páginas, com a permissão: Demonstração (começar, montar, limpar) e Primeiros passos — só o Administrador;
--    Faça comigo (iniciar, passos) — todos os perfis do painel (cada roteiro confere as rotas dele); o teste do envio
--    de e-mail — quem cadastra o e-mail de envio.
-- 4. A versão 1.22.0 e as novidades.
-- Idempotente.

CREATE TABLE IF NOT EXISTS `adms_instalacao_estado` (
  `chave` varchar(60) NOT NULL,
  `valor` text NOT NULL,
  `modified` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Onda 2: o estado da instalação (demonstração, começo, primeiros passos)';

CREATE TABLE IF NOT EXISTS `adms_fases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(40) NOT NULL,
  `ordem` int(11) NOT NULL,
  `titulo` varchar(120) NOT NULL,
  `texto` varchar(600) NOT NULL COMMENT 'o que a fase pede',
  `feito` varchar(200) NOT NULL COMMENT 'a frase de quando ela é concluída ("Você já configurou a instituição.")',
  `convite` varchar(200) NOT NULL COMMENT 'a frase que a anuncia como a próxima ("Agora vamos criar o seu primeiro curso.")',
  `rota` varchar(120) NOT NULL COMMENT 'a tela onde se faz (a mesma do sistema)',
  `rotulo_rota` varchar(80) NOT NULL DEFAULT 'Abrir a tela',
  `roteiro` varchar(60) DEFAULT NULL COMMENT 'o slug do Faça comigo (painel/app/adms/Roteiros/<slug>.json)',
  `condicao` text NOT NULL COMMENT 'SELECT que devolve um número; > 0 = concluída (medida no banco, não marcada à mão)',
  `depende_de` varchar(200) DEFAULT NULL COMMENT 'os códigos das fases anteriores necessárias, separados por vírgula',
  `pode_pular` tinyint(1) NOT NULL DEFAULT 1,
  `modulo` varchar(40) DEFAULT NULL COMMENT 'NULL = o núcleo; um módulo opcional futuro põe as fases dele aqui',
  `situacao` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fase_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Onda 2: as fases dos Primeiros passos (dado, não código)';

CREATE TABLE IF NOT EXISTS `adms_fases_estado` (
  `fase_id` int(11) NOT NULL,
  `pulada_em` datetime DEFAULT NULL,
  `concluida_em` datetime DEFAULT NULL COMMENT 'quando o motor viu a conclusão pela primeira vez',
  `anunciada_em` datetime DEFAULT NULL COMMENT 'quando a frase "Você já…" foi mostrada',
  `usuario_mod` int(11) DEFAULT NULL,
  PRIMARY KEY (`fase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- As fases (a) a (h)
INSERT INTO `adms_fases` (`codigo`, `ordem`, `titulo`, `texto`, `feito`, `convite`, `rota`, `rotulo_rota`, `roteiro`, `condicao`, `depende_de`, `pode_pular`)
SELECT * FROM (
  SELECT 'instituicao' AS c, 10 AS o, 'A instituição' AS t,
         'O nome, a mantenedora, o CNPJ, o endereço, o telefone, o e-mail, o site e o logo. O logo aparece no menu, na tela de entrada e no cabeçalho dos documentos.' AS x,
         'Você já configurou a instituição.' AS f, 'Agora vamos configurar a instituição: o nome e o logo.' AS cv,
         'dados-instituicao/editar' AS r, 'Abrir os dados da instituição' AS rr, 'fase-instituicao' AS ro,
         "SELECT COUNT(*) FROM adms_empresas WHERE instituicao = 1 AND TRIM(nome) <> '' AND logo IS NOT NULL AND logo <> ''" AS cd, NULL AS dp, 1 AS pp
  UNION ALL SELECT 'regras', 20, 'As regras de nota e frequência',
         'A média mínima para aprovação, a frequência mínima, as casas decimais das notas e o campo com que o aluno entra no portal (RA, CPF ou e-mail).',
         'Você já definiu as regras de nota e frequência.', 'Agora vamos definir as regras de nota e frequência.',
         'parametros/listar', 'Abrir os parâmetros', 'fase-regras',
         "SELECT COUNT(*) FROM adms_parametros WHERE chave IN ('frequencia.minima', 'portal.login.campo') AND usuario_mod IS NOT NULL", NULL, 1
  UNION ALL SELECT 'email', 30, 'O e-mail de envio',
         'O servidor de e-mail (SMTP) que manda os links de acesso e de nova senha, com um envio de teste. Pode ficar para depois.',
         'Você já configurou o e-mail de envio.', 'Agora vamos configurar o e-mail de envio (ou pular, para fazer depois).',
         'configuracao-email/listar', 'Abrir a configuração de e-mail', 'fase-email',
         "SELECT COUNT(*) FROM adms_confs_emails WHERE TRIM(IFNULL(host, '')) <> ''", NULL, 1
  UNION ALL SELECT 'ano_letivo', 40, 'O primeiro ano letivo',
         'O ano letivo em que as disciplinas vão ser ofertadas.',
         'Você já criou o ano letivo.', 'Agora vamos criar o primeiro ano letivo.',
         'anos-letivos/listar', 'Abrir os anos letivos', 'fase-ano-letivo',
         "SELECT COUNT(*) FROM adms_periodos_letivos", NULL, 1
  UNION ALL SELECT 'curso', 50, 'O primeiro curso, com a grade e as disciplinas',
         'O polo, as matérias, o curso, a grade (a matriz) e as disciplinas da grade.',
         'Você já criou o curso, a grade e as disciplinas.', 'Agora vamos criar o seu primeiro curso, com a grade e as disciplinas.',
         'cursos/listar', 'Abrir os cursos', 'fase-curso',
         "SELECT COUNT(*) FROM uni_rh_cursos_disciplinas_grade", NULL, 1
  UNION ALL SELECT 'oferta', 60, 'A primeira oferta e o professor',
         'A oferta das disciplinas do ano a partir da grade, e o professor de cada uma.',
         'Você já ofertou as disciplinas e indicou o professor.', 'Agora vamos ofertar as disciplinas e indicar o professor.',
         'disciplinas/listar', 'Abrir as disciplinas', 'fase-oferta',
         "SELECT COUNT(*) FROM rm_professores_mat_liga WHERE situacao = 1", 'ano_letivo,curso', 1
  UNION ALL SELECT 'alunos', 70, 'Os alunos',
         'Os alunos e o vínculo deles com o curso: cadastrar na tela, importar de uma planilha ou integrar com outro sistema pela API.',
         'Você já cadastrou os alunos.', 'Agora vamos trazer os alunos.',
         'academicos/listar', 'Abrir os alunos', 'fase-alunos',
         "SELECT COUNT(*) FROM rm_matriculas_curso", 'curso', 1
  UNION ALL SELECT 'enturmar', 80, 'Enturmar e lançar a primeira nota',
         'Pôr os alunos nas disciplinas ofertadas, criar a primeira atividade e lançar a primeira nota.',
         'Você já enturmou os alunos e lançou a primeira nota. Os primeiros passos estão completos.', 'Agora vamos enturmar os alunos e lançar a primeira nota.',
         'portal-alunos/listar', 'Abrir a enturmação', 'fase-enturmar',
         "SELECT COUNT(*) FROM rm_disciplina_atividade_notas WHERE nota IS NOT NULL", 'oferta,alunos', 1
) f
WHERE NOT EXISTS (SELECT 1 FROM `adms_fases` x WHERE x.`codigo` = f.c);

-- As páginas
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`)
SELECT p.* FROM (
  SELECT 467 AS id, 'Como começar' AS mp, 'Demonstracao' AS c, 'comecar' AS m, 'demonstracao' AS mc, 'comecar' AS mm, 'Como você quer começar' AS np, 'Onda 2: conhecer com a demonstração ou configurar a instituição.' AS o, 2 AS lp, 'fas fa-flag-checkered' AS ic, 1 AS g, 1 AS tp, 1 AS s, NOW() AS cr, 0 AS gr, 0 AS se, NULL AS es
  UNION ALL SELECT 468, 'Montar a demonstração', 'Demonstracao', 'montar', 'demonstracao', 'montar', 'Montar a demonstração', 'Onda 2: monta os dados fictícios pela ferramenta da demonstração (POST).', 2, 'fas fa-flask', 1, 1, 1, NOW(), 1, 0, NULL
  UNION ALL SELECT 469, 'Começar com os dados da minha instituição', 'Demonstracao', 'instituicao', 'demonstracao', 'instituicao', 'Começar com os dados da minha instituição', 'Onda 2: tira a demonstração pela limpeza que existe, com as travas dela.', 2, 'fas fa-building-circle-check', 1, 1, 1, NOW(), 1, 0, NULL
  UNION ALL SELECT 470, 'Primeiros passos', 'PrimeirosPassos', 'index', 'primeiros-passos', 'index', 'Primeiros passos', 'Onda 2: as fases da configuração da instituição.', 2, 'fas fa-shoe-prints', 1, 1, 1, NOW(), 0, 0, NULL
  UNION ALL SELECT 471, 'Pular ou retomar uma fase', 'PrimeirosPassos', 'fase', 'primeiros-passos', 'fase', 'Pular ou retomar uma fase', 'Onda 2: pular, retomar, dispensar (POST).', 2, 'fas fa-forward', 1, 1, 1, NOW(), 1, 0, NULL
  UNION ALL SELECT 472, 'Faça comigo', 'FacaComigo', 'iniciar', 'faca-comigo', 'iniciar', 'Faça comigo', 'Onda 2: começa o roteiro guiado na própria tela.', 2, 'fas fa-handshake-angle', 1, 1, 1, NOW(), 0, 0, NULL
  UNION ALL SELECT 473, 'Faça comigo (passos)', 'FacaComigo', 'passos', 'faca-comigo', 'passos', 'Faça comigo (passos)', 'Onda 2: os passos do roteiro, em JSON.', 2, 'fas fa-list-ol', 1, 1, 1, NOW(), 0, 0, NULL
  UNION ALL SELECT 474, 'Testar o envio de e-mail', 'ConfiguracaoEmail', 'testar', 'configuracao-email', 'testar', 'Testar o envio de e-mail', 'Onda 2: manda um e-mail de teste pela configuração gravada (POST).', 2, 'fas fa-paper-plane', 1, 1, 1, NOW(), 1, 0, NULL
) p WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` x WHERE x.`id` = p.id);

INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 2, p.`id`, 2, 2, 1, n.`id`, p.`id`, NOW()
  FROM `adms_niveis_acessos` n JOIN `adms_paginas` p ON p.`id` BETWEEN 467 AND 474
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.`id` AND x.`adms_pagina_id` = p.`id`);
-- Administrador: a demonstração e os primeiros passos (no menu: Primeiros passos e "Começar com os dados da minha
-- instituição"; o menu mostra cada um só quando vale — AdmsMenus)
UPDATE `adms_nivacs_pgs` d JOIN `adms_niveis_acessos` n ON n.`id` = d.`adms_niveis_acesso_id`
   SET d.`permissao` = 1, d.`lib_menu` = IF(d.`adms_pagina_id` IN (469, 470), 1, 2)
 WHERE d.`adms_pagina_id` BETWEEN 467 AND 471 AND n.`papel` = 'administrador';
-- Faça comigo: todos os perfis com papel (cada roteiro confere as rotas que percorre)
UPDATE `adms_nivacs_pgs` d JOIN `adms_niveis_acessos` n ON n.`id` = d.`adms_niveis_acesso_id`
   SET d.`permissao` = 1, d.`lib_menu` = 2
 WHERE d.`adms_pagina_id` IN (472, 473) AND n.`papel` IS NOT NULL;
-- o teste do envio: quem cadastra a configuração de e-mail
UPDATE `adms_nivacs_pgs` d
  JOIN `adms_paginas` q ON q.`controller` = 'ConfiguracaoEmail' AND q.`metodo` = 'cadastrar'
  JOIN `adms_nivacs_pgs` y ON y.`adms_pagina_id` = q.`id` AND y.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND y.`permissao` = 1
   SET d.`permissao` = 1, d.`lib_menu` = 2
 WHERE d.`adms_pagina_id` = 474;

-- A versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.22.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 38, '1.22.0', 1, 22, 0, '2026-11-14', 'Conhecer primeiro, configurar por fases: a demonstração montada e removida pela tela, os Primeiros passos com as fases da configuração e o Faça comigo, que conduz cada fase na própria tela.', 1, '2026-11-14 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.22.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.22.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 121, 38, 'Conhecer com dados de demonstração', 'Depois de instalar, o sistema pode ser explorado com uma escola fictícia completa. Uma faixa avisa que os dados são fictícios; o botão Começar com os dados da minha instituição remove a demonstração sem reinstalar.', 1, 'administrador', 'conhecer-com-a-demonstracao', '2026-11-14 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 121);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 122, 38, 'Primeiros passos', 'Os Primeiros passos levam a instituição pela configuração, uma fase de cada vez: a instituição, as regras, o e-mail, o ano letivo, o curso, a oferta, os alunos e a primeira nota. Cada fase se marca sozinha quando fica pronta.', 2, 'administrador', 'primeiros-passos', '2026-11-14 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 122);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 123, 38, 'Faça comigo', 'O botão Faça comigo conduz o procedimento na própria tela: uma janela diz o que fazer, destaca o campo ou o botão e confere cada passo.', 3, 'administrador', 'primeiros-passos', '2026-11-14 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 123);
