-- A barreira de acesso da família (Onda 7, Etapa 5; docs/RESPONSAVEIS.md, D-65). Só na escola. Nada muda nas tabelas:
-- o ponto único (core/AcessoFamilia.php) já decide cada tela do portal do responsável; esta versão traz a conferência
-- dele em "Verificações de segurança" (core/BarreiraFamilia.php) e as provas da barreira.

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.55.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 72, '1.55.0', 1, 55, 0, '2026-11-18', 'A barreira de acesso da família: o ponto único conferido em Verificações de segurança.', 1, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.55.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.55.0';

-- a novidade
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 175, 72, 'A barreira do portal do responsável em Verificações de segurança', 'Na escola, Manutenção → Verificações de segurança ganha o item "Portal do responsável": ele confere que toda tela do portal da família pergunta, a cada requisição, se aquela pessoa pode ver aquele aluno naquele ano. Tirar o papel, trocar o financeiro ou encerrar o acesso adicional vale já na tela seguinte da pessoa.', 1, 'administrador', 'avisos-e-seguranca', '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 175);
