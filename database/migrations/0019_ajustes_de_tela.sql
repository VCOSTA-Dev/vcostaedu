-- Ajustes de tela: as marcas do rodapé com a altura das letras do texto, o selo dos Primeiros passos com o número do
-- passo de que a fase depende, e a marca formatada no Sobre. Nada de estrutura: só a versão e as novidades.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.33.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 49, '1.33.0', 1, 33, 0, '2026-11-15', 'As marcas do rodapé com as letras da altura do texto; nos Primeiros passos, "Depois do passo N" e os botões apagados até o passo anterior ficar pronto; a marca formatada no Sobre.', 1, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.33.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.33.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 142, 49, 'Primeiros passos: de qual passo cada fase depende', 'O selo diz "Depois do passo 5" (ou "Depois dos passos 4 e 5"), e os botões da fase ficam apagados, com o motivo, até aquele passo ficar pronto.', 1, 'administrador', 'primeiros-passos', '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 142);
