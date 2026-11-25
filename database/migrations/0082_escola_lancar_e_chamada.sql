-- A CORREÇÃO DA ESCOLA, Parte 2 (D-101; docs/ESCOLA.md): lançar a nota e a chamada. Nada muda no banco além da versão e
-- da novidade: o Enter desce na hora e grava em segundo plano; a nota fora da escala segura o cursor; o atalho "Chamada"
-- nunca leva a uma tela sem chamada (a troca num passo, ou as faltas da etapa); a troca para a chamada conta também a
-- falta lançada por etapa; a chamada num sábado ou domingo pede a confirmação; a janela de andamento termina com o sinal
-- de concluído.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.95.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 112, '1.95.0', 1, 95, 0, '2026-11-22', 'A escola: lançar a nota com o Enter na hora, a chamada sem beco sem saída e o aviso do fim de semana.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.95.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.95.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 216, 112, 'Lançar a nota mais rápido', 'Na tela de notas da atividade e na recuperação da etapa, o Enter leva na hora ao aluno seguinte e a nota grava em segundo plano: o sinal de gravado (ou o motivo da recusa) aparece no campo de onde você saiu. A nota fora da escala (0 a 10) não deixa o campo: o cursor fica nele, com o motivo.', 1, 'administrador,secretaria,professor', 'lancar-notas', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 216);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 217, 112, 'A chamada', 'O atalho Chamada de "O meu dia" leva sempre a um lugar onde se registra a frequência: na disciplina que lança as faltas por etapa, a tela explica e oferece passar a fazer a chamada num passo; com falta já lançada, o atalho vira Faltas e leva à tela das etapas. A chamada com a data num sábado ou num domingo avisa e pede a confirmação.', 2, 'administrador,secretaria,professor', 'fazer-a-chamada', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 217);
