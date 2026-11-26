-- O HISTÓRICO ESCOLAR DA EDUCAÇÃO BÁSICA, Partes D e E (D-113, D-114; docs/HISTORICO_ESCOLAR.md): o documento do
-- Fundamental, a prévia, a emissão, as vias e o cancelamento.
--   Nenhuma tabela nova: a emissão usa a de sempre (rm_historico_emissoes, com o código de verificação, o hash, o PDF
--   guardado e a conferência pública; a decisão 5) com o tipo próprio ESC_EF; a 2ª via é a reimpressão
--   (rm_historico_emissao_vias; a decisão 16). O tipo do ensino superior (PARCIAL, FINAL, ATESTADO) não muda.
--   Emitir e cancelar: a ação 131, da secretaria e também da direção (a decisão 4: a marca consulta_grava deixa a
--   direção, que só consulta, gravar nestas páginas), sem a marca de sensível (a decisão 17).
-- COMO DESFAZER: apagar as páginas 650 a 655 (adms_paginas, adms_paginas_modulo, adms_nivacs_pgs) e a ação 131.

INSERT IGNORE INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`) VALUES
(131, 'emitir_e_cancelar_o_historico_escolar_131', 'Emitir e cancelar o histórico escolar', 'Documentos', 'secretaria', 41);
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `consulta_grava`, `sensivel`, `escopo`, `acao_id`)
SELECT x.id, x.menu, x.ctl, x.met, x.mctl, x.mmet, x.nome, x.obs, 2, NULL, 1, 1, 1, NOW(), x.grava, x.cg, 0, NULL, x.acao FROM (
  SELECT 650 id, 'Prévia e emissão do histórico' menu, 'HistoricoEscolaEmissao' ctl, 'previa' met, 'historico-escola-emissao' mctl, 'previa' mmet, 'Prévia e emissão do histórico escolar' nome, 'Os avisos, a data do documento, a publicação, as aulas dadas; as vias emitidas.' obs, 0 grava, 0 cg, 130 acao
  UNION ALL SELECT 651, 'PDF da prévia do histórico', 'HistoricoEscolaEmissao', 'previaPdf', 'historico-escola-emissao', 'previa-pdf', 'A prévia em PDF (marcada PRÉVIA)', 'PDF; nada é gravado.', 0, 0, 130
  UNION ALL SELECT 652, 'Emitir o histórico', 'HistoricoEscolaEmissao', 'emitir', 'historico-escola-emissao', 'emitir', 'Emitir o histórico escolar', 'POST: a 1ª via, com o código de verificação e a cópia guardada.', 1, 1, 131
  UNION ALL SELECT 653, '2ª via do histórico', 'HistoricoEscolaEmissao', 'segundaVia', 'historico-escola-emissao', 'segunda-via', 'Reimprimir o histórico (2ª via)', 'POST: o mesmo documento, com o número da via.', 1, 1, 131
  UNION ALL SELECT 654, 'Cancelar o histórico', 'HistoricoEscolaEmissao', 'cancelar', 'historico-escola-emissao', 'cancelar', 'Cancelar o histórico escolar emitido', 'POST: com o motivo; a via cancelada fica guardada.', 1, 1, 131
  UNION ALL SELECT 655, 'PDF do histórico emitido', 'HistoricoEscolaEmissao', 'pdf', 'historico-escola-emissao', 'pdf', 'O PDF guardado do histórico emitido', 'PDF; só se o sha256 confere.', 0, 0, 130
) x WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` p WHERE p.`id` = x.id) ORDER BY x.id;
INSERT INTO `adms_paginas_modulo` (`adms_pagina_id`, `modulo`)
SELECT p.`id`, 'escola' FROM `adms_paginas` p
 WHERE p.`id` BETWEEN 650 AND 655 AND NOT EXISTS (SELECT 1 FROM `adms_paginas_modulo` m WHERE m.`adms_pagina_id` = p.`id`) ORDER BY p.`id`;
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT IF(d.`adms_niveis_acesso_id` IN (1, 3, 4, 10), 1, 2), p.`id`, d.`dropdown`, 2, d.`adms_menu_id`, d.`adms_niveis_acesso_id`, p.`id`, NOW()
  FROM `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` BETWEEN 650 AND 655
 WHERE d.`adms_pagina_id` = 46
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = d.`adms_niveis_acesso_id` AND x.`adms_pagina_id` = p.`id`)
 ORDER BY p.`id`, d.`adms_niveis_acesso_id`;
