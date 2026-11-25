-- As outras operações longas, em passos (Onda 6, Etapa 4; DECISIONS D-54; docs/OPERACOES_LONGAS.md). A importação de
-- alunos por planilha aplica em passos, com o "tudo ou nada" mantido (uma linha que falha faz desfazer as já gravadas) e o
-- erro da linha; a conferência e o desfazer passaram a trabalhar por conjunto. Os relatórios com muitas ofertas calculam
-- as notas em passos e abrem no fim, com os mesmos números; os cálculos ficam guardados uma hora, só para quem pediu.
-- Confirmar a enturmação não espera o Moodle: o envio automático vem em passos, na tela do aluno.

-- Os cálculos dos relatórios em passos (AdmsRelatorioCalculo): uma linha por oferta calculada, por execução
CREATE TABLE IF NOT EXISTS `adms_relatorio_calculos` (
  `execucao` char(32) NOT NULL COMMENT 'a execução (um relatório pedido, por quem pediu)',
  `chave` varchar(24) NOT NULL COMMENT 'p:<oferta> a média parcial; v:<oferta> o provisório',
  `dados` longblob NOT NULL COMMENT 'o cálculo da oferta, exatamente como veio (serialize)',
  `criado` datetime NOT NULL,
  PRIMARY KEY (`execucao`, `chave`),
  KEY `idx_relatorio_calculos_criado` (`criado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Onda 6: os cálculos de um relatório feitos em passos (valem uma hora)';

-- Quantas ofertas um relatório calcula de uma vez (acima disso, em passos)
INSERT INTO `adms_parametros` (`chave`, `valor`, `descricao`, `grupo`)
SELECT 'relatorios.calculo_de_uma_vez', '20', 'Relatórios: até quantas ofertas as notas são calculadas de uma vez; acima disso, em passos, com o andamento na tela.', 'Relatórios'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_parametros` WHERE `chave` = 'relatorios.calculo_de_uma_vez');

-- A versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.46.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 63, '1.46.0', 1, 46, 0, '2026-11-18', 'A importação de alunos, os relatórios grandes e o envio da enturmação ao Moodle andam em passos, com o andamento na tela.', 1, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.46.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.46.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 159, 63, 'A importação de alunos em passos', 'Em Importar alunos de planilha, "Aplicar a importação" mostra o andamento, linha a linha, e aceita arquivos de até 5.000 linhas sem passar do tempo. Continua valendo o tudo ou nada: se uma linha falha, as já gravadas são desfeitas e a tela diz qual linha falhou. Se a página fechar no meio, o botão Continuar retoma de onde parou.', 1, 'administrador,secretaria', 'importar-alunos-de-planilha', '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 159);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 160, 63, 'Relatórios grandes calculados em partes', 'Um relatório que usa as notas de muitas disciplinas calcula essas notas em partes, com o andamento na tela, e abre sozinho no fim, com os mesmos números. O rodapé do relatório diz a hora do cálculo.', 2, 'administrador,secretaria', NULL, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 160);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 161, 63, 'A enturmação vai ao Moodle com o andamento na tela', 'Com o envio automático ao Moodle ligado, confirmar a enturmação de um aluno grava na hora e, em seguida, mostra o envio das disciplinas ao Moodle, uma a uma. Se o Moodle não responder, o que faltou fica em Envios ao Moodle.', 3, 'administrador,secretaria', NULL, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 161);
