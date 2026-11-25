-- O tempo da demonstração da escola (Onda 6, Etapa 2; DUVIDAS 264). A montagem do colégio fictício grava pelas telas, como
-- sempre; cada gravação em série deixou de abrir a tela seguinte (a lista inteira, que ninguém lia). O resultado é o
-- mesmo (a régua da escola idêntica); nenhuma tabela muda: esta etapa só registra a versão e a novidade.

-- A versão e a novidade
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.44.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 61, '1.44.0', 1, 44, 0, '2026-11-17', 'A demonstração da escola monta em cerca de um terço do tempo, com nenhum passo acima de 30 segundos; o colégio fictício é o mesmo.', 1, '2026-11-17 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.44.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.44.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 156, 61, 'A demonstração da escola mais rápida', 'Em "Conhecer com dados de demonstração", o colégio fictício fica pronto em cerca de um terço do tempo de antes, com a mesma janela de passos; o resultado é o mesmo colégio.', 1, 'administrador', 'conhecer-com-a-demonstracao', '2026-11-17 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 156);
