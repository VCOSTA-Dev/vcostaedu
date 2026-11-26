-- A COMPENSAÇÃO DE AUSÊNCIA LIBERÁVEL AO PROFESSOR (D-130; 1.103.0, C7).
--   Até aqui, lançar e remover compensação (as páginas 410 e 411) eram da ação 24 ("Lançar faltas e compensações", até a
--   secretaria), e o código só aceitava o administrador, a supervisão e a secretaria. Agora têm a ação própria 112,
--   "Lançar compensação de ausência", com o teto no professor: a instituição pode ligá-la para o perfil do professor em
--   Perfis de acesso. **De fábrica, o professor continua sem ela** (as linhas da matriz não mudam: quem tinha, continua;
--   o professor, não). Ligada, o professor lança e remove só nas disciplinas dele (AdmsCompensacoes::podeNaOferta).
-- COMO DESFAZER: as páginas 410 e 411 de volta à ação 24 e apagar a ação 112.

INSERT IGNORE INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`) VALUES
(112, 'lancar_compensacao_de_ausencia_112', 'Lançar compensação de ausência', 'Notas e frequência', 'professor', 24);
UPDATE `adms_paginas` SET `acao_id` = 112 WHERE `id` IN (410, 411) AND `acao_id` = 24;
