-- O FINANCEIRO DE LIGAR E DESLIGAR NOS TRÊS TIPOS (D-129; 1.103.0, C4).
--   O financeiro (as páginas marcadas em adms_paginas_recurso desde a 0087) passa a ser de ligar e desligar também na
--   escola e no ensino superior: o estado fica em adms_instalacao_estado (financeiro.ligado; no curso livre, a chave de
--   sempre, livre.financeiro). Sem a chave, o padrão do tipo: ligado na escola e no ensino superior (nada muda numa
--   instalação que já existe), desligado no curso livre. Nenhum dado é apagado.
--   A tela de ligar e desligar (as páginas 597 e 598, D-106) sai da marca do curso livre e vale nos três tipos, com o
--   nome "Recursos da instituição" (na escola e no ensino superior, ela mostra só o financeiro). As permissões não mudam:
--   as mesmas da tela Configurações → Instituição.
-- COMO DESFAZER: a marca 'livre' de volta às páginas 597 e 598, os nomes antigos, e apagar a chave financeiro.ligado.

DELETE FROM `adms_paginas_modulo` WHERE `adms_pagina_id` IN (597, 598) AND `modulo` = 'livre';
UPDATE `adms_paginas` SET `menu_pagina` = 'Recursos da instituição', `nome_pagina` = 'Recursos da instituição',
       `obs` = 'O financeiro (nos três tipos) e, no curso livre, a matrícula pela secretaria: ligar e desligar.'
 WHERE `id` = 597 AND `nome_pagina` = 'Recursos do curso livre';
UPDATE `adms_paginas` SET `menu_pagina` = 'Gravar os recursos da instituição', `nome_pagina` = 'Gravar os recursos da instituição', `obs` = 'POST.'
 WHERE `id` = 598 AND `nome_pagina` = 'Gravar os recursos do curso livre';
