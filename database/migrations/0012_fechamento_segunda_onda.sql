-- Onda 2, Etapa 8 — o fechamento da segunda onda: a versão 1.26.0 e a novidade que resume o que mudou.
-- Só dado: nenhuma tabela nem página nova.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.26.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 42, '1.26.0', 1, 26, 0, '2026-11-15', 'O produto aberto: o instalador profissional, os perfis de acesso, a auditoria de notas e faltas, conhecer e configurar por fases, a importação de alunos, a API de integração e a atualização pela tela.', 1, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.26.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.26.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 130, 42, 'O que mudou nesta versão', 'Desde a 1.19.0: o instalador profissional; os perfis Administrador, Direção, Supervisão, Secretaria, Coordenação de curso e Professor; a auditoria de cada nota e falta; a demonstração e os Primeiros passos com o Faça comigo; a importação de alunos por planilha; a API de integração; e a atualização do sistema pela tela. Cada uma tem o seu guia na Ajuda.', 1, 'administrador', 'primeiros-passos', '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 130);
