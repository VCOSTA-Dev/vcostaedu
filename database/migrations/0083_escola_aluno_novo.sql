-- A CORREÇÃO DA ESCOLA, Parte 3 (D-102; docs/ESCOLA.md): o aluno novo e o que entra no meio do ano.
--   - as NOTAS DE TRANSFERÊNCIA: por disciplina e etapa, a nota e as faltas trazidas da escola de origem, com o nome dela
--     e o documento apresentado (esc_notas_transferencia); valem como a nota da etapa (mesmo na etapa fechada), contam na
--     média anual, aparecem marcadas no boletim; só a secretaria, a supervisão de secretaria e o administrador lançam
--     (a ação 110); cada mudança no histórico (esc_notas_transferencia_hist) e na auditoria; nada é apagado;
--   - o ATESTADO DE MATRÍCULA DA ESCOLA: um modelo próprio, marcado para a escola (rm_historico_modelos.tipo_instituicao),
--     que usa a série e o ano letivo, sem a previsão de conclusão e sem o CPF; no ensino superior ele não aparece e os
--     modelos de sempre continuam os mesmos.
-- O número proposto do aluno, a parcela a partir da qual cobrar, a turma na matrícula, os candidatos da série e do ano
-- na enturmação e o endereço na planilha dos responsáveis não pedem nada no banco.

CREATE TABLE IF NOT EXISTS `esc_notas_transferencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aluno_id` int(11) NOT NULL COMMENT 'col_usuarios.id',
  `rm` varchar(50) NOT NULL,
  `oferta_id` int(11) NOT NULL COMMENT 'a disciplina da turma do aluno',
  `etapa_id` int(11) NOT NULL,
  `nota` decimal(5,2) DEFAULT NULL COMMENT 'a nota trazida (vazia = só as faltas)',
  `faltas` int(11) NOT NULL DEFAULT 0 COMMENT 'as faltas trazidas, somadas às da disciplina',
  `escola_origem` varchar(160) NOT NULL,
  `documento` varchar(160) NOT NULL COMMENT 'o documento apresentado (a declaração de transferência, o histórico parcial)',
  `situacao` varchar(10) NOT NULL DEFAULT 'valida' COMMENT 'valida | retirada',
  `por` int(11) DEFAULT NULL,
  `em` datetime NOT NULL,
  `alterado_por` int(11) DEFAULT NULL,
  `alterado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oferta` (`oferta_id`, `etapa_id`, `rm`, `situacao`),
  KEY `aluno` (`aluno_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='As notas e as faltas trazidas da escola de origem, por disciplina e etapa';

CREATE TABLE IF NOT EXISTS `esc_notas_transferencia_hist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transferencia_id` int(11) NOT NULL,
  `nota_antes` decimal(5,2) DEFAULT NULL,
  `faltas_antes` int(11) DEFAULT NULL,
  `nota` decimal(5,2) DEFAULT NULL,
  `faltas` int(11) NOT NULL DEFAULT 0,
  `escola_origem` varchar(160) DEFAULT NULL,
  `documento` varchar(160) DEFAULT NULL,
  `por` int(11) DEFAULT NULL,
  `em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transferencia` (`transferencia_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O histórico das notas de transferência: cada mudança, com o antes';

-- o modelo de documento marcado para um tipo de instituição (vazio = os dois); o atestado da escola nasce só na escola
-- (aqui, na que já é escola; na instalação nova, quando o tipo é escolhido: VcAtestadoEscola, como VcHorarioPadrao)
ALTER TABLE `rm_historico_modelos` ADD COLUMN IF NOT EXISTS `tipo_instituicao` varchar(10) DEFAULT NULL COMMENT 'escola | superior: o modelo só existe nesse tipo (vazio = nos dois)' AFTER `nome`;
INSERT INTO `rm_historico_modelos` (`tipo`, `nome`, `tipo_instituicao`, `situacao`, `versao_atual`, `usuario_cre`, `created`)
SELECT 'ATESTADO', 'Atestado de matrícula — escola', 'escola', 2, 1, NULL, '2026-11-22 00:00:00' FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `rm_historico_modelos` WHERE `nome` = 'Atestado de matrícula — escola')
   AND EXISTS (SELECT 1 FROM `adms_instalacao_estado` WHERE `chave` = 'instituicao.tipo' AND `valor` = 'escola'); -- só na escola (a instalação nova escolhe o tipo depois: core/AtestadoEscola.php)
INSERT INTO `rm_historico_modelo_versoes` (`modelo_id`, `versao`, `secoes`, `usuario_cre`, `created`)
SELECT m.`id`, 1, '{"assinaturas":{"blocos":[]},"documento":"<table class=\\"cab\\"><tbody><tr><td class=\\"cab-logo\\">{{LOGO_INSTITUICAO}}</td><td class=\\"cab-texto\\">{{INSTITUICAO_CABECALHO}}</td></tr></tbody></table><p><br></p><p><br></p><h1>ATESTADO DE MATRÍCULA</h1><p><br></p><p><br></p><p class=\\"recuo\\" style=\\"text-align: justify; font-size: 12pt; line-height: 1.5\\">Atestamos, para os devidos fins, que {{ALUNO_NOME}} está regularmente {{ALUNO_MATRICULADO}} nesta escola, no {{CURSO_NOME}} ({{ESCOLA_SERIE}}), no ano letivo de {{ESCOLA_ANO_LETIVO}}.</p><p><br></p><p><br></p><p style=\\"text-align: right\\">{{INSTITUICAO_CIDADE}}, {{EMISSAO_DATA_EXTENSO}}.</p><p><br></p><p>{{ASSINATURAS}}</p><p>{{CODIGO_VERIFICACAO}}</p>","formato":2,"opcoes":{"casasNota":2,"estilo":"oficial","fonte":"liberation","paginacao":true},"quadro":{"atividades":false,"colunas":[],"emCurso":"Em curso"}}', NULL, '2026-11-22 00:00:00'
  FROM `rm_historico_modelos` m
 WHERE m.`nome` = 'Atestado de matrícula — escola' AND NOT EXISTS (SELECT 1 FROM `rm_historico_modelo_versoes` v WHERE v.`modelo_id` = m.`id`);

-- a ação e as páginas das notas de transferência (só a escola)
INSERT IGNORE INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`) VALUES
(110, 'lancar_as_notas_de_transferencia_110', 'Lançar as notas de transferência (da escola de origem)', 'Notas e frequência', 'secretaria', 29);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `consulta_grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, NULL, 1, 1, 1, NOW(), x.grava, 0, 0, NULL, 110 FROM (
  SELECT 592 id, 'Notas de transferência' menu, 'EscolaTransferencia' ctl, 'index' met, 'escola-transferencia' mctl, 'index' mmet, 'Notas de transferência do aluno' nome, 'As disciplinas da turma do aluno × as etapas: a nota e as faltas trazidas da escola de origem.' obs, 0 grava
  UNION ALL SELECT 593, 'Salvar as notas de transferência', 'EscolaTransferencia', 'salvar', 'escola-transferencia', 'salvar', 'Salvar as notas de transferência', 'POST: a tela inteira, com o histórico e a auditoria.', 1
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id) ORDER BY x.id;
INSERT INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`)
SELECT x.id, 'escola' FROM (SELECT 592 id UNION ALL SELECT 593) x
 WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas_modulo` m WHERE m.`adms_pagina_id` = x.id) ORDER BY x.id;
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT IF(d.`adms_niveis_acesso_id` IN (1, 4, 10), 1, 2), p.`id`, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` IN (592, 593)
 WHERE d.`adms_pagina_id` = 509
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`; -- a ordem dos ids, fixa

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.96.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 113, '1.96.0', 1, 96, 0, '2026-11-22', 'A escola: o aluno novo (o número proposto, a turma na matrícula, a parcela inicial) e o que entra no meio do ano (as notas de transferência), e o atestado da escola.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.96.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.96.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 218, 113, 'O aluno que entra no meio do ano', 'A etapa que terminou antes da matrícula fica sem nota e fora da média anual (antes, aparecia 0,0). Na ficha do aluno, Matrículas → Notas de transferência: a nota e as faltas trazidas da escola de origem, por disciplina e etapa, com o nome da escola e o documento; elas contam na média, aparecem marcadas com "T" no boletim e valem mesmo na etapa fechada.', 1, 'administrador,secretaria', 'cadastrar-aluno-e-enviar-link', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 218);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 219, 113, 'Cadastrar e matricular mais rápido', 'O cadastro do aluno já abre com o próximo número da sequência (pode trocar; se outra pessoa salvar o mesmo número antes, a tela avisa e traz o seguinte). Na matrícula, a turma pode ser escolhida no mesmo passo, e o plano de pagamento pergunta a partir de qual parcela cobrar (marcada: a primeira que ainda não venceu). O atestado de matrícula da escola sai com a série e o ano letivo, sem a previsão de conclusão e sem o CPF. Enturmar alunos mostra a série e o ano de cada um e só oferece os da série e do ano da turma.', 2, 'administrador,secretaria', 'vincular-e-enturmar', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 219);
