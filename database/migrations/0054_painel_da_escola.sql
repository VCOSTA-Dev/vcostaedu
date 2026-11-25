-- O painel acadêmico da escola (Onda 8, correção, Parte 3.2 e acréscimo A, itens 1 e 2; D-77): o resultado anual da
-- escola chega aos relatórios (o painel e o resultado por disciplina mostram o ano concluído), e os relatórios que
-- calculam muitas ofertas abrem de uma vez quando o cálculo cabe em poucos segundos (sem a janela de passos). Só código.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.67.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 84, '1.67.0', 1, 67, 0, '2026-11-21', 'O painel acadêmico da escola: o resultado do ano concluído, e os relatórios abrindo de uma vez.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.67.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.67.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 187, 84, 'O painel acadêmico da escola', 'Na escola, o painel acadêmico e o resultado por disciplina mostram o resultado do ano concluído (a aprovação, a reprovação por nota e por frequência, a frequência média). E os relatórios que calculam as notas de muitas ofertas abrem de uma vez numa escola pequena, sem a janela de passos.', 1, 'administrador,secretaria', 'relatorios-a-painel', '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 187);
