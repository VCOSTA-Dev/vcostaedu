-- A demonstração em passos curtos e a tela final do instalador.
--
-- Montar e remover a demonstração deixam de ser uma requisição longa: viram passos curtos, um por chamado do navegador,
-- dentro do PHP da requisição (sem executar programa nenhum), com o estado no banco (adms_instalacao_estado,
-- "demonstracao.montagem", que existe só enquanto a montagem ou a remoção está em andamento). A rota é a de sempre
-- (Demonstracao/montar, a ação "Montar e remover a demonstração"): esta migration só traz a versão e as novidades.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.30.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 46, '1.30.0', 1, 30, 0, '2026-11-15', 'A demonstração montada e removida em passos curtos, com uma janela de progresso que retoma de onde parou; a tela final do instalador com o que fazer no topo, sem falso alarme e sem termos técnicos.', 1, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.30.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.30.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 136, 46, 'A demonstração passo a passo', 'Montar e remover a demonstração mostram uma janela com cada parte (a equipe, os cursos, os alunos, as notas, os documentos) e a barra de progresso. Se a página fechar no meio, o botão Continuar retoma de onde parou; se um passo falhar, Tentar de novo recomeça só ele. Logo depois de instalar, a senha não é pedida de novo.', 1, 'administrador', 'conhecer-com-a-demonstracao', '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 136);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 137, 46, 'A verificação do servidor mais clara', 'A tela final do instalador e a verificação do servidor dizem no topo o que fazer, em ordem de importância, com o detalhe recolhido; numa instalação local, o HTTPS desligado e a pasta dos arquivos dentro da pasta publicada são aviso, não falha; a escrita nas pastas é testada de verdade.', 2, 'administrador', NULL, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 137);
