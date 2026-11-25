-- O motor de médias: o acréscimo e as regras prontas (Onda 4, Etapa 5; docs/ESCOLA.md, D-50).
-- O ACRÉSCIMO: um ponto somado por cima da média fechada (fechou com 5,0, acréscimo 1,0 → 6,0), lançado pelo professor como
-- uma atividade própria, fora dos componentes e sem peso; uma opção da regra (padrão: não), com o limite por disciplina
-- (na escola, por etapa). A opção mora numa tabela própria, por versão da regra: a regra sem a linha — todas as de antes —
-- calcula exatamente como sempre.
-- AS REGRAS PRONTAS no catálogo, como dado: "Só atividades" e "Prova mensal + prova bimestral + atividades". Entram no
-- catálogo sem trocar a regra de nenhuma disciplina; a escola as recebe e os Primeiros passos perguntam se mantém ou ajusta.

CREATE TABLE IF NOT EXISTS `rm_media_regra_acrescimo` (
  `versao_id` int(11) NOT NULL,
  `permitir` tinyint(1) NOT NULL DEFAULT 0,
  `limite` decimal(4,2) DEFAULT NULL COMMENT 'o limite do acréscimo por disciplina (na escola, por etapa); vazio = sem limite',
  PRIMARY KEY (`versao_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='O acréscimo de cada versão da regra de média (sem linha = não permite)';

-- as regras prontas (no catálogo; nenhuma disciplina muda)
INSERT INTO `rm_media_regras` (`nome`, `descricao`, `padrao`, `escopo`, `escopo_id`, `versao_atual`, `situacao`, `usuario_cre`, `created`)
SELECT x.nome, x.descricao, 0, 'catalogo', 0, 1, 1, NULL, NOW() FROM (
  SELECT 'Só atividades' nome, 'Pronta: a média das atividades, sem prova. Indicada para os componentes práticos (Arte, Educação Física, os projetos). Ajuste à vontade.' descricao
  UNION ALL SELECT 'Prova mensal + prova bimestral + atividades', 'Pronta: prova mensal (30%), prova bimestral (40%, obrigatória) e atividades (30%). Indicada para os componentes com prova. Ajuste à vontade.'
) x WHERE NOT EXISTS (SELECT 1 FROM `rm_media_regras` r WHERE r.`nome` = x.nome);
INSERT INTO `rm_media_regra_versoes` (`regra_id`, `versao`, `casas`, `arredondamento`, `momento`, `sem_nota`, `componente_vazio`, `nota_minima`, `usuario_cre`, `created`)
SELECT r.`id`, 1, 1, 'meio_para_cima', 'so_final', 'zero', 'redistribuir', 6.00, NULL, NOW() FROM `rm_media_regras` r
 WHERE r.`nome` IN ('Só atividades', 'Prova mensal + prova bimestral + atividades')
   AND NOT EXISTS (SELECT 1 FROM `rm_media_regra_versoes` v WHERE v.`regra_id` = r.`id`);
INSERT INTO `rm_media_regra_componentes` (`versao_id`, `codigo`, `nome`, `peso`, `modo`, `parametro_n`, `parametro_tipo`, `ordem`, `min_atividades`, `max_atividades`)
SELECT v.`id`, x.codigo, x.nome, x.peso, 'aritmetica', NULL, NULL, x.ordem, x.minimo, NULL
  FROM `rm_media_regras` r INNER JOIN `rm_media_regra_versoes` v ON v.`regra_id` = r.`id` AND v.`versao` = 1
  INNER JOIN (
    SELECT 'Só atividades' regra, 'atividades' codigo, 'Atividades' nome, 100.0000 peso, 1 ordem, 0 minimo
    UNION ALL SELECT 'Prova mensal + prova bimestral + atividades', 'prova_mensal', 'Prova mensal', 30.0000, 1, 0
    UNION ALL SELECT 'Prova mensal + prova bimestral + atividades', 'prova_bimestral', 'Prova bimestral', 40.0000, 2, 1
    UNION ALL SELECT 'Prova mensal + prova bimestral + atividades', 'atividades', 'Atividades', 30.0000, 3, 0
  ) x ON x.regra = r.`nome`
 WHERE NOT EXISTS (SELECT 1 FROM `rm_media_regra_componentes` c WHERE c.`versao_id` = v.`id`);

-- os Primeiros passos da escola: as regras de média (manter as prontas ou ajustar)
INSERT INTO `adms_fases` (`codigo`, `ordem`, `titulo`, `texto`, `feito`, `convite`, `rota`, `rotulo_rota`, `roteiro`, `condicao`, `depende_de`, `pode_pular`, `modulo`, `situacao`)
SELECT 'esc_regras', 55, 'As regras de média', 'A escola já recebe duas regras prontas — "Só atividades" e "Prova mensal + prova bimestral + atividades". Escolha a que vale como padrão (ou ajuste uma delas) e a que vale para cada componente.', 'Você já escolheu a regra de média padrão.', 'Agora vamos escolher a regra de média: manter as prontas ou ajustar.', 'regra-media/listar', 'Abrir as regras de média', 'fase-escola-regras',
       "SELECT COUNT(*) FROM rm_media_regras WHERE padrao = 1 AND nome IN ('Só atividades', 'Prova mensal + prova bimestral + atividades')", NULL, 1, 'escola', 1
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_fases` WHERE `codigo` = 'esc_regras');

-- A versão e a novidade
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.42.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 59, '1.42.0', 1, 42, 0, '2026-11-16', 'O acréscimo na regra de média (um ponto por cima da média fechada, com limite e teto; padrão: não) e as regras prontas no catálogo ("Só atividades"; "Prova mensal + prova bimestral + atividades"). Nenhuma disciplina muda de regra.', 1, '2026-11-16 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.42.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.42.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 154, 59, 'O acréscimo e as regras prontas', 'Na regra de média, "Permitir acréscimo": o professor lança um acréscimo, que soma à média fechada (5,0 + 1,0 → 6,0), com o limite da regra e o teto da nota. E duas regras prontas no catálogo, para usar como estão ou ajustar.', 1, 'administrador,secretaria,professor', 'configurar-a-regra-de-media', '2026-11-16 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 154);
