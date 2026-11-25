-- A CORREÇÃO DA ESCOLA, Parte 4 (D-103; docs/ESCOLA.md): os textos e os acertos. Nada muda no banco além da versão e da
-- novidade: na escola, o número do aluno é "RM" (um rótulo só; o campo "ra" da API não muda), as palavras do ensino
-- superior saem das telas da escola (a "aula" no lugar do "encontro" na chamada), os plurais sem "(s)"; Parâmetros mostra
-- o valor gravado e salvar não muda o que não foi mexido; remover a demonstração limpa os planos de pagamento e volta à
-- pergunta "Como você quer começar?"; o aluno que abre um endereço do portal da família vê "não permitido".

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.97.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 114, '1.97.0', 1, 97, 0, '2026-11-23', 'A escola: os textos (RM, aula, disciplina, os plurais), os Parâmetros que mostram o valor gravado e a demonstração removida sem sobras.', 1, '2026-11-23 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.97.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.97.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 220, 114, 'As palavras da escola', 'Na escola, o número do aluno aparece como RM em todas as telas, relatórios e no boletim; a chamada fala em aula (não em encontro), as telas de notas falam em disciplina e segmento, e os campos do ensino superior (polo, modalidade, realização) não aparecem. As contagens saem no singular ou no plural certo, sem "(s)".', 1, 'administrador,secretaria,professor,coordenacao,direcao,supervisao', 'organizar-a-escola', '2026-11-23 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 220);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 221, 114, 'Parâmetros e a demonstração', 'Em Configurações → Parâmetros, cada campo mostra o valor gravado, e salvar não muda o que não foi mexido. Remover a demonstração apaga também os planos de pagamento e os títulos dela, e a Página Inicial e os Primeiros passos voltam à pergunta "Como você quer começar?".', 2, 'administrador', 'primeiros-passos', '2026-11-23 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 221);
