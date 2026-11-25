-- A demonstração da escola (Onda 4, Etapa 4; docs/ESCOLA.md): numa instalação de escola, "Conhecer com dados de
-- demonstração" monta o Colégio Horizonte (fictício) — a matriz de exemplo pela Base Nacional Comum Curricular, as turmas
-- do ano corrente com dois bimestres fechados e uma turma do ano anterior concluída —, pela tela e pela linha de comando,
-- em passos, removível. A turma passa a ter, em cada oferta, os encontros iguais às horas do componente. Só a versão.

-- A versão e a novidade
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.41.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 58, '1.41.0', 1, 41, 0, '2026-11-16', 'A demonstração da escola: o Colégio Horizonte (fictício), com a matriz de exemplo pela Base Nacional Comum Curricular, turmas, etapas, recuperação, conselho de classe e uma turma concluída.', 1, '2026-11-16 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.41.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.41.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 153, 58, 'A demonstração da escola', 'Numa instalação de escola, "Conhecer com dados de demonstração" monta um colégio fictício completo: os segmentos e a matriz de exemplo, as turmas, as etapas fechadas, a recuperação, o conselho de classe e uma turma do ano anterior concluída.', 1, 'administrador', 'conhecer-com-a-demonstracao', '2026-11-16 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 153);
