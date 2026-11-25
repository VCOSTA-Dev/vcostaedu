-- Onda 3, Etapa 1 — o professor só alcança as ofertas a que está ligado.
--
-- As rotas que gravam nota, falta, chamada e as importações não diziam o parâmetro que recebem (adms_paginas.escopo):
-- o roteador não tinha o que conferir. Agora cada uma diz, e AdmsEscopo::rota() confere — para a leitura e para a
-- gravação, com a marca "grava" da página. "nota" é a linha da nota que a célula grava (o id vem por POST); "encontro" é
-- o encontro da chamada. Só dado: nenhuma tabela muda.

UPDATE `adms_paginas` SET `escopo` = 'oferta'
 WHERE (`controller`, `metodo`) IN (('Atividades', 'cadastrar'), ('Atividades', 'apagar'), ('Chamada', 'encontro'), ('Chamada', 'fonte'),
                                   ('Faltas', 'editar'), ('Faltas', 'importar'), ('Faltas', 'modelo'), ('Medias', 'atualizarLista'))
   AND `escopo` IS NULL;
UPDATE `adms_paginas` SET `escopo` = 'atividade'
 WHERE (`controller`, `metodo`) IN (('Atividades', 'editar'), ('Atividades', 'uploadNotas'))
   AND `escopo` IS NULL;
UPDATE `adms_paginas` SET `escopo` = 'nota' WHERE `controller` = 'Atividades' AND `metodo` = 'editNotas' AND `escopo` IS NULL;
UPDATE `adms_paginas` SET `escopo` = 'encontro' WHERE `controller` = 'Chamada' AND `metodo` = 'excluir' AND `escopo` IS NULL;

-- A versão e a novidade
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.27.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 43, '1.27.0', 1, 27, 0, '2026-11-15', 'Segurança: o professor só alcança as disciplinas em que dá aula, na consulta e no lançamento; a API recusa HTTP quando a instalação exige HTTPS; as bibliotecas sem uso saíram.', 1, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.27.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.27.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 131, 43, 'Cada professor nas suas disciplinas', 'O professor consulta e lança notas, faltas, chamada e importações só nas disciplinas em que dá aula — também pelo endereço. O professor que coordena um curso consulta todas as disciplinas do curso e lança só nas suas.', 1, 'administrador,professor', 'minhas-disciplinas-e-atividades', '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 131);
