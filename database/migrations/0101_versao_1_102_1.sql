-- A VERSÃO 1.102.1 (D-123 a D-128): a higiene antes de o código ir a público, as Novidades do zero na instalação nova,
-- os textos do ensaio, o professor no Moodle nos três tipos, a instalação no Linux e o nome fictício do administrador.
--   1. C5 (D-126): a entrada do professor no Moodle (as páginas 599 e 600, a ação 111) vale também na escola e no ensino
--      superior (antes, só no curso livre): as duas páginas saem de adms_paginas_modulo e passam ao núcleo. A permissão
--      não muda: só o papel de professor.
--   2. A versão e as novidades.
-- COMO DESFAZER: 1. a marca das páginas 599 e 600 de volta ('livre', em adms_paginas_modulo); 2. apagar as novidades 230 e 231 e a versão 120, e
-- voltar a 1.102.0 à situação 1.

-- 1. o professor no Moodle nos três tipos
-- sem a linha da marca, a página é do núcleo: vale nos três tipos (a marca 'escola,superior,livre' não cabe na coluna)
DELETE FROM `adms_paginas_modulo` WHERE `adms_pagina_id` IN (599, 600);

-- 2. a versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.102.1' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 120, '1.102.1', 1, 102, 1, '2026-11-26', 'Os acertos antes de o código ir a público: as Novidades começam do zero numa instalação nova, os textos da escola, o professor no Moodle também na escola e no ensino superior, o guia de instalação no Linux e o nome fictício do administrador da demonstração.', 1, '2026-11-26 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.102.1');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.102.1';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 230, 120, 'O professor entra no Moodle pela Página Inicial', 'Com o sistema conectado ao Moodle, o professor tem o botão "Entrar no Moodle" na Página Inicial, e "Abrir no Moodle" em cada disciplina enviada, sem uma segunda senha. Vale na escola, no ensino superior e no curso livre.', 1, 'professor,administrador', NULL, '2026-11-26 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 230);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 231, 120, 'Instalar no Linux, ao lado do Moodle', 'O guia novo mostra a instalação num servidor Linux que já tem o Moodle, sem parar o Moodle: o mod_rewrite, o AllowOverride, o banco com a collation certa e as permissões das pastas. Na escola, as telas falam de segmento, aula e RM.', 2, 'administrador', 'instalar-no-linux-ao-lado-do-moodle', '2026-11-26 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 231);
