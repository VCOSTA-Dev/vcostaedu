-- A preparação para abrir o código (Onda 6, Etapa 8; DECISIONS D-60). Nada é publicado: o repositório ganha o README de
-- apresentação, como contribuir, a política de segurança, o código de conduta, o resumo da marca e o rascunho do termo
-- de contribuição; o inventário do esqueleto herdado (docs/ORIGEM_DO_CODIGO.md) e a ferramenta da pasta limpa. Nenhuma
-- tabela muda: esta etapa só registra a versão e a novidade.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.50.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 67, '1.50.0', 1, 50, 0, '2026-11-18', 'O código pronto para ser aberto: a apresentação, como contribuir, a política de segurança, o código de conduta e a marca.', 1, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.50.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.50.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 170, 67, 'O código pronto para ser aberto', 'O pacote do sistema traz a apresentação (README.md), como contribuir, a política de segurança com o canal privado para avisar falhas, o código de conduta e o resumo da marca. Nada mudou no uso do sistema.', 1, 'administrador', NULL, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 170);
