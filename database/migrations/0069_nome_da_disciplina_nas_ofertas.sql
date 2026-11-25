-- O nome da disciplina nas ofertas (Onda 8, correção, segunda leva, 2.9; D-95). A oferta guarda uma cópia do nome da
-- disciplina (rm_professores_materias.materia), que as telas, o portal e o boletim mostram: renomear a disciplina
-- passa a valer também nas ofertas que já existem (as da disciplina pela matriz com o nome de antes). A migration não
-- muda dado: uma oferta com nome diferente do da disciplina pode ser de um renome antigo ou um nome dado à mão, e não dá
-- para saber qual; só a versão e a novidade.

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.82.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 99, '1.82.0', 1, 82, 0, '2026-11-22', 'Renomear a disciplina vale também nas ofertas que já existem.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.82.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.82.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 202, 99, 'O nome da disciplina nas ofertas', 'Ao renomear uma disciplina, o nome novo vale também nas ofertas que já existem (as telas, o portal e o boletim passam a mostrá-lo); o aviso diz em quantas. A oferta com outro nome, dado à mão, fica como está, e os documentos já emitidos não mudam.', 1, 'administrador,secretaria', NULL, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 202);
