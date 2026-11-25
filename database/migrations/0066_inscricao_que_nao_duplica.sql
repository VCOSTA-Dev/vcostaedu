-- A inscrição que não duplica (Onda 8, correção, segunda leva, 2.6; D-92). POST /inscricoes na escola: o mesmo e-mail
-- na mesma turma é a mesma inscrição, ainda que venha com outro número de matrícula — a resposta é a matrícula que já
-- existe, com sucesso, e nada é criado (nem a pessoa, nem a matrícula, nem um envio novo ao Moodle). Duas chamadas ao
-- mesmo tempo passam uma depois da outra (a trava pelo e-mail e pela turma). Nenhuma tabela muda.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.79.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 96, '1.79.0', 1, 79, 0, '2026-11-21', 'A inscrição que não duplica: o mesmo e-mail na mesma turma devolve a matrícula que já existe.', 1, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.79.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.79.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 199, 96, 'A inscrição que não duplica', 'Pela interface (POST /inscricoes), a inscrição que não duplica: o mesmo e-mail na mesma turma devolve a matrícula que já existe, com sucesso, sem criar outra pessoa, outra matrícula ou outro envio ao Moodle — também com duas chamadas ao mesmo tempo, ou com outro número de matrícula.', 1, 'administrador', NULL, '2026-11-21 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 199);
