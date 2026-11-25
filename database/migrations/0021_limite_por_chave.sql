-- O limite por chave da API (Onda 3, Etapa 9): cada chave diz, assunto por assunto (alunos, matrículas, enturmação,
-- cadastros de apoio), se pode consultar ou gravar; o padrão de uma chave nova é o mais restrito (nada marcado). As
-- chaves que já existem continuam fazendo o que faziam: a de leitura consulta tudo; a de escrita consulta e grava tudo.

ALTER TABLE `adms_api_chaves` ADD COLUMN IF NOT EXISTS `permissoes` varchar(255) DEFAULT NULL COMMENT 'o limite por assunto: "alunos:gravar cadastros:ler …" (gravar inclui consultar)' AFTER `escopo`;

UPDATE `adms_api_chaves` SET `permissoes` = 'alunos:ler matriculas:ler enturmacao:ler cadastros:ler'
 WHERE `permissoes` IS NULL AND `escopo` = 'leitura';
UPDATE `adms_api_chaves` SET `permissoes` = 'alunos:gravar matriculas:gravar enturmacao:gravar cadastros:ler'
 WHERE `permissoes` IS NULL AND `escopo` = 'escrita';

-- A versão e a novidade
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.35.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 51, '1.35.0', 1, 35, 0, '2026-11-15', 'O limite de cada chave da API por assunto (alunos, matrículas, enturmação, cadastros de apoio; consultar ou gravar), com o padrão mais restrito; e um portal de matrícula de exemplo, fora do produto, que só fala com a API.', 1, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.35.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.35.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 146, 51, 'O limite de cada chave da API', 'Em Integrações → Chaves de API, cada chave diz o que pode, assunto por assunto (alunos, matrículas, enturmação, cadastros de apoio), para consultar ou gravar. As chaves que já existiam continuam como estavam.', 1, 'administrador', 'integrar-outro-sistema-pela-api', '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 146);
