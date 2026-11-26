-- O HISTÓRICO ESCOLAR DA EDUCAÇÃO BÁSICA, Parte J (D-119; docs/HISTORICO_ESCOLAR.md): as permissões finas.
--   Emitir (com a 2ª via, que é a reimpressão) e cancelar passam a ser ações próprias: a 131 fica só com emitir e a 2ª
--   via; a 133, nova, é a de cancelar. As duas, da secretaria e da direção de fábrica (como antes: a marca consulta_grava
--   deixa a direção, que só consulta, gravar nestas páginas); os demais perfis não abrem. A matriz das permissões
--   (adms_nivacs_pgs) não muda: cada página já tem a linha de cada perfil.
-- COMO DESFAZER: voltar a página 654 para a ação 131, o nome da 131 para "Emitir e cancelar o histórico escolar" e apagar
-- a ação 133.

INSERT IGNORE INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`) VALUES
(133, 'cancelar_o_historico_escolar_133', 'Cancelar o histórico escolar emitido', 'Documentos', 'secretaria', 43);
UPDATE `adms_acoes` SET `nome` = 'Emitir o histórico escolar (e a 2ª via)' WHERE `id` = 131 AND `nome` = 'Emitir e cancelar o histórico escolar';
UPDATE `adms_paginas` SET `acao_id` = 133 WHERE `id` = 654 AND `acao_id` = 131;
