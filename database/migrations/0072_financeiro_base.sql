-- O FINANCEIRO, a base (Onda 8, financeiro, Parte 1; D-98; docs/FINANCEIRO.md). Simples e correto: o dinheiro em centavos
-- inteiros (nunca ponto flutuante), as taxas em centésimos de ponto percentual (2% = 200), um título com uma situação
-- gravada (aberto, recebido, baixado, cancelado, renegociado; "a vencer" e "vencido" saem da data), e uma tabela de
-- operações que guarda cada ação e cada desfazer, com quem, quando, o motivo e o antes e o depois. Nada é apagado.
-- Vale para os dois tipos de instituição.

CREATE TABLE IF NOT EXISTS `fin_regras` (
  `id` tinyint(4) NOT NULL,
  `multa_bp` int(11) NOT NULL DEFAULT 200 COMMENT 'a multa por atraso, em centésimos de ponto (2% = 200), uma vez',
  `juros_mes_bp` int(11) NOT NULL DEFAULT 100 COMMENT 'os juros ao mês, em centésimos de ponto (1% = 100), pró-rata por dia de atraso',
  `desconto_bp` int(11) NOT NULL DEFAULT 0 COMMENT 'o desconto até o vencimento (0 = sem desconto)',
  `atualizado_por` int(11) DEFAULT NULL,
  `atualizado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As regras financeiras da instalação (uma linha)';
INSERT IGNORE INTO `fin_regras` (`id`, `multa_bp`, `juros_mes_bp`, `desconto_bp`) VALUES (1, 200, 100, 0);

CREATE TABLE IF NOT EXISTS `fin_planos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) NOT NULL,
  `parcelas` int(11) NOT NULL,
  `valor_centavos` bigint(20) NOT NULL COMMENT 'o valor de cada parcela',
  `dia_vencimento` tinyint(4) NOT NULL COMMENT '1 a 28',
  `mes_primeira` tinyint(4) NOT NULL COMMENT 'o mês da primeira parcela (1 a 12), no ano da matrícula',
  `situacao` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1 = em uso; 2 = fora de uso (não aparece na matrícula)',
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Os planos de pagamento';

CREATE TABLE IF NOT EXISTS `fin_titulos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL COMMENT 'col_usuarios.id',
  `matricula_curso_id` int(11) DEFAULT NULL COMMENT 'a matrícula (vazio no avulso sem matrícula)',
  `plano_id` int(11) DEFAULT NULL,
  `parcela` int(11) DEFAULT NULL COMMENT 'a parcela do plano (1, 2, ...)',
  `ano` int(11) NOT NULL,
  `descricao` varchar(160) NOT NULL,
  `valor_centavos` bigint(20) NOT NULL,
  `vencimento` date NOT NULL,
  `situacao` varchar(12) NOT NULL DEFAULT 'aberto' COMMENT 'aberto | recebido | baixado | cancelado | renegociado',
  `renegociacao_id` int(11) DEFAULT NULL COMMENT 'a renegociação de que esta parcela nasceu',
  `renegociado_por` int(11) DEFAULT NULL COMMENT 'a renegociação que absorveu este título',
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parcela_do_plano` (`matricula_curso_id`, `plano_id`, `parcela`),
  KEY `aluno` (`aluno_id`, `vencimento`),
  KEY `situacao` (`situacao`, `vencimento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Os títulos: as parcelas dos planos e os avulsos';

CREATE TABLE IF NOT EXISTS `fin_renegociacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL,
  `total_centavos` bigint(20) NOT NULL COMMENT 'os originais com os encargos do dia',
  `desconto_centavos` bigint(20) NOT NULL DEFAULT 0,
  `parcelas` int(11) NOT NULL,
  `primeiro_vencimento` date NOT NULL,
  `situacao` varchar(10) NOT NULL DEFAULT 'vigente' COMMENT 'vigente | desfeita',
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL,
  `desfeita_em` datetime DEFAULT NULL,
  `motivo_desfazer` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `aluno` (`aluno_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As renegociações (os originais ficam renegociado; as parcelas novas nascem ligadas)';

CREATE TABLE IF NOT EXISTS `fin_operacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo_id` int(11) DEFAULT NULL,
  `aluno_id` int(11) NOT NULL,
  `tipo` varchar(24) NOT NULL COMMENT 'gerar | receber | baixar | cancelar | renegociar | desfazer_recebimento | desfazer_baixa | desfazer_cancelamento | desfazer_renegociacao | atendimento',
  `data_operacao` date DEFAULT NULL COMMENT 'no receber: a data do recebimento (pode ser antes de hoje)',
  `forma` varchar(20) DEFAULT NULL COMMENT 'dinheiro | pix | cartao | transferencia | outro',
  `original_centavos` bigint(20) DEFAULT NULL,
  `multa_centavos` bigint(20) DEFAULT NULL,
  `juros_centavos` bigint(20) DEFAULT NULL,
  `desconto_centavos` bigint(20) DEFAULT NULL,
  `recebido_centavos` bigint(20) DEFAULT NULL,
  `motivo` varchar(500) DEFAULT NULL,
  `observacao` varchar(500) DEFAULT NULL,
  `situacao_antes` varchar(12) DEFAULT NULL,
  `situacao_depois` varchar(12) DEFAULT NULL,
  `renegociacao_id` int(11) DEFAULT NULL,
  `desfeita_em` datetime DEFAULT NULL COMMENT 'a operação desfeita fica, marcada',
  `desfeita_por_operacao` int(11) DEFAULT NULL,
  `envio` varchar(40) DEFAULT NULL COMMENT 'o número do envio do formulário: o clique duplo não grava duas vezes',
  `usuario_id` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `envio` (`envio`),
  KEY `titulo` (`titulo_id`, `id`),
  KEY `aluno` (`aluno_id`, `id`),
  KEY `caixa` (`tipo`, `data_operacao`, `usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O histórico do financeiro: cada ação e cada desfazer (nada é apagado)';

-- as ações: o financeiro (ver e agir) e o desfazer, separadas
INSERT IGNORE INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`) VALUES
(105, 'operar_o_financeiro_105', 'Operar o financeiro (ficha, receber, baixar, cancelar, renegociar)', 'Financeiro', 'secretaria', 1),
(106, 'desfazer_no_financeiro_106', 'Desfazer no financeiro (recebimento, baixa, cancelamento, renegociação)', 'Financeiro', 'secretaria', 2);

INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, x.icone, 1, 1, 1, NOW(), x.grava, 0, NULL, 105 FROM (
  SELECT 560 id, 'Regras financeiras' menu, 'FinRegras' ctl, 'index' met, 'fin-regras' mctl, 'index' mmet, 'Regras financeiras' nome, 'A multa, os juros ao mês e o desconto até o vencimento.' obs, 'fas fa-percent' icone, 0 grava
  UNION ALL SELECT 561, 'Salvar as regras financeiras', 'FinRegras', 'salvar', 'fin-regras', 'salvar', 'Salvar as regras financeiras', 'POST.', NULL, 1
  UNION ALL SELECT 562, 'Planos de pagamento', 'FinPlanos', 'listar', 'fin-planos', 'listar', 'Planos de pagamento', 'O nome, as parcelas, o valor, o dia de vencimento e o mês da primeira.', 'fas fa-money-check-dollar', 0
  UNION ALL SELECT 563, 'Salvar o plano de pagamento', 'FinPlanos', 'salvar', 'fin-planos', 'salvar', 'Cadastrar ou editar o plano de pagamento', 'POST.', NULL, 1
  UNION ALL SELECT 564, 'Ativar ou desativar o plano', 'FinPlanos', 'situacao', 'fin-planos', 'situacao', 'Ativar ou desativar o plano de pagamento', 'POST.', NULL, 1
  UNION ALL SELECT 565, 'Ficha financeira', 'Financeiro', 'ficha', 'financeiro', 'ficha', 'A ficha financeira do aluno', 'Os títulos, os filtros, as ações e o histórico.', NULL, 0
  UNION ALL SELECT 566, 'Título avulso', 'Financeiro', 'avulso', 'financeiro', 'avulso', 'Lançar um título avulso', 'POST. A descrição, o valor e o vencimento.', NULL, 1
  UNION ALL SELECT 567, 'Aplicar um plano', 'Financeiro', 'aplicarPlano', 'financeiro', 'aplicar-plano', 'Gerar os títulos de um plano para a matrícula', 'POST.', NULL, 1
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id);

-- as permissões: as de cadastrar alunos (47), nível a nível (a secretaria, a supervisão e o administrador); no menu
-- Configurações, só as regras e os planos
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT d.`permissao`, p.`id`, d.`dropdown`, IF(p.`id` IN (560, 562), 1, 2), IF(p.`id` IN (560, 562), 4, d.`adms_menu_id`), d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` BETWEEN 560 AND 567
 WHERE d.`adms_pagina_id` = 47
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`; -- a ordem dos ids, fixa

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.85.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 102, '1.85.0', 1, 85, 0, '2026-11-22', 'O financeiro, a base: as regras, os planos de pagamento e os títulos.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.85.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.85.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 205, 102, 'O financeiro', 'Em Configurações: as Regras financeiras (a multa, os juros ao mês por dia de atraso e o desconto até o vencimento) e os Planos de pagamento. Ao vincular o aluno a um curso com um plano, os títulos são gerados; na ficha do aluno, o botão Ficha financeira mostra os títulos e permite lançar um título avulso.', 1, 'administrador,secretaria', 'o-financeiro', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 205);
