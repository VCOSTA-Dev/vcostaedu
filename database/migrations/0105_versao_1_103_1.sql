-- A VERSÃO 1.103.1 (D-134 a D-139): os acertos depois da rodada ampla da 1.103.0.
--   O financeiro desligado diz o motivo no endereço direto; o instalador não deixa o navegador preencher o usuário e a
--   senha do banco; o CPF de demonstração igual nos três tipos; o filtro por ano de Minhas Disciplinas; o número da
--   oferta nas listas de disciplinas; a verificação pública com o vocabulário do tipo.
-- COMO DESFAZER: apagar as novidades 237 a 240 e a versão 122, e voltar a 1.103.0 à situação 1.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.103.1' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 122, '1.103.1', 1, 103, 1, '2026-11-27', 'O número de cada disciplina nas listas, o filtro por ano de Minhas Disciplinas, o aviso do financeiro desligado e o termo da escola na verificação pública de documentos.', 1, '2026-11-27 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.103.1');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.103.1';

INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 237, 122, 'O número de cada disciplina', 'Em Disciplinas e em Minhas Disciplinas, a primeira coluna (#) traz o número de cada disciplina na turma, que não se repete. Ele aparece também no alto da tela da disciplina e a busca da lista acha por ele: informe esse número num atendimento.', 1, 'administrador,secretaria,coordenacao,professor', 'minhas-disciplinas-e-atividades', '2026-11-27 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 237);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 238, 122, 'O filtro por ano de Minhas Disciplinas', 'O filtro por ano de Minhas Disciplinas mostra o ano da lista, como em Disciplinas. Antes, ele podia vir vazio com a lista cheia.', 2, 'administrador,coordenacao,professor', 'minhas-disciplinas-e-atividades', '2026-11-27 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 238);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 239, 122, 'Quando o financeiro está desligado', 'Com o financeiro desligado, quem abre uma tela do financeiro pelo endereço vê que ele está desligado nesta instalação; quem pode ligar vê onde: Configurações → Recursos da instituição.', 3, 'administrador,secretaria', 'o-financeiro', '2026-11-27 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 239);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 240, 122, 'A verificação pública fala como a escola', 'Na escola, a página pública que confere um documento pelo código diz "Segmento" (no ensino superior e no curso livre, "Curso"), também no comprovante impresso. O documento emitido não muda.', 4, 'administrador,secretaria', 'conferir-um-documento', '2026-11-27 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 240);
