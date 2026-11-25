-- Atualizar o sistema em passos (Onda 3, Etapa 11): a tela aplica uma etapa do banco por chamada, com a janela de
-- progresso; a página fechada ou a etapa que falhou retomam de onde pararam; uma trava contra duas atualizações ao mesmo
-- tempo. O comando (php bin/migrar.php aplicar) segue aplicando tudo em sequência. Esta etapa só registra a versão: o
-- estado do lote da tela fica numa tabela que a própria tela cria e apaga (adms_atualizacao_lote), porque o banco de
-- quem atualiza pode ser de uma versão bem antiga.

-- A versão e a novidade
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.37.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 54, '1.37.0', 1, 37, 0, '2026-11-15', 'Atualizar o sistema em passos: uma etapa do banco por vez, com o andamento na tela; a página fechada ou a etapa que falhou continuam de onde pararam.', 1, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.37.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.37.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 149, 54, 'Atualizar o sistema em passos', 'A tela Atualizar o sistema aplica uma etapa do banco por vez e mostra o andamento. Se a página fechar no meio ou uma etapa falhar, "Continuar a atualização" segue de onde parou.', 1, 'administrador', 'atualizar-o-sistema', '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 149);
