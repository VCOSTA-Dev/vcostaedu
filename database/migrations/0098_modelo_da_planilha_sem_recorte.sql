-- A CORREÇÃO DO ENSAIO, B1.2 e B1.3 (D-120): o modelo da planilha (de notas e de faltas) sem o recorte da oferta.
--   O modelo é um arquivo fixo (o cabeçalho e uma linha de exemplo de AdmsImportacaoCsv::MODELOS): não mostra dado de
--   nenhuma oferta, então não há o que recortar. A 0013 tinha posto o recorte "oferta" nesta página junto com as rotas
--   que gravam; com ele, o link do modelo de notas (que pedia a oferta 0) recusava o professor com "Esta disciplina não
--   é sua". A permissão da página continua a mesma (a ação de ver as faltas, que o professor tem).
-- COMO DESFAZER: UPDATE adms_paginas SET escopo = 'oferta' WHERE controller = 'Faltas' AND metodo = 'modelo';

UPDATE `adms_paginas` SET `escopo` = NULL WHERE `controller` = 'Faltas' AND `metodo` = 'modelo' AND `escopo` = 'oferta';
