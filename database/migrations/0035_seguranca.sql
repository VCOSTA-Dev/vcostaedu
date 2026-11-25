-- A segurança (Onda 6, Etapa 6; DECISIONS D-58; docs/SEGURANCA_REVISAO.md). O envio de formulário vindo de outro site é
-- recusado; apagar o ano letivo só por POST; a foto enviada pelo conteúdo e pela extensão do tipo, e nada roda como
-- programa dentro de assets/; as saídas das telas tratadas; a sessão sem uso pede para entrar de novo
-- (SESSAO_INATIVA_MINUTOS, padrão 120); o cabeçalho Permissions-Policy; em Verificações de segurança, o perfil criado
-- pela tela. Nenhuma tabela muda: esta etapa só registra a versão e as novidades.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.48.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 65, '1.48.0', 1, 48, 0, '2026-11-18', 'A revisão de segurança: o envio forjado recusado, a foto conferida pelo conteúdo, as telas com a saída tratada e a sessão sem uso encerrada.', 1, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.48.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.48.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 166, 65, 'A sessão sem uso pede para entrar de novo', 'Por segurança, quem fica mais de 2 horas sem usar o sistema precisa entrar de novo, com o mesmo usuário e a mesma senha; a tela de entrada diz o motivo.', 1, 'administrador,secretaria,professor,aluno', NULL, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 166);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 167, 65, 'A revisão de segurança', 'O sistema recusa formulário enviado de outro site, confere a foto enviada pelo conteúdo (não pelo nome) e mostra como texto qualquer nome cadastrado com código de página. Em Verificações de segurança, o perfil de acesso criado pela tela numa instalação anterior aparece para conferir.', 2, 'administrador', NULL, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 167);
