-- O cartão das senhas da demonstração (Onda 8, correção, Parte 1.3; D-73): todas as senhas do mesmo jeito, escondidas,
-- com "Mostrar" e "Copiar"; "Copiar" copia a senha verdadeira mesmo escondida. Só tela.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.63.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 80, '1.63.0', 1, 63, 0, '2026-11-21', 'O cartão das senhas da demonstração: todas do mesmo jeito, com Mostrar e Copiar.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.63.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.63.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 183, 80, 'Copiar a senha da demonstração', 'No cartão "Conheça o sistema", todas as senhas aparecem do mesmo jeito, escondidas, com "Mostrar" e "Copiar". "Copiar" copia a senha verdadeira, mesmo escondida: cole no campo da senha.', 1, 'administrador', 'conhecer-com-a-demonstracao', '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 183);
