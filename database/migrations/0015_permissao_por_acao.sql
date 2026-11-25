-- Onda 3, Etapa 3 — o pente fino (docs/PENTE_FINO.md): a permissão por AÇÃO, com o teto, como dado; a tela "Perfis de
-- acesso" (só leitura); a saída das telas de programação; os nomes do menu.
--
-- O produto se configura, não se programa pela tela: saem Níveis de acesso, Páginas, Itens de menu, Acesso acadêmico,
-- Páginas acadêmicas e as permissões por tela (as páginas e os vínculos delas). O motor de permissão continua igual: a
-- matriz (adms_nivacs_pgs) é a mesma, e um nível criado pela tela numa instalação existente continua valendo.
--
-- Cada página aponta para a AÇÃO dela (um nome que a pessoa entende, o grupo) e cada ação tem um TETO: o perfil mais baixo
-- que pode recebê-la (pela ordem dos perfis). O motor recusa, e a conferência do instalador e a verificação do ambiente
-- reprovam, a ação dada a um perfil abaixo do teto. 91 ações, geradas a partir da matriz da instalação (o gerador fica
-- fora do repositório).

CREATE TABLE IF NOT EXISTS `adms_acoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(80) NOT NULL,
  `nome` varchar(120) NOT NULL COMMENT 'como a pessoa entende ("Lançar notas e atividades")',
  `grupo` varchar(60) NOT NULL,
  `teto` varchar(30) NOT NULL COMMENT 'o papel mais baixo que pode receber a ação (adms_niveis_acessos.papel, pela ordem)',
  `ordem` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_acoes_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `adms_paginas` ADD COLUMN IF NOT EXISTS `acao_id` int(11) DEFAULT NULL COMMENT 'a ação da página (adms_acoes)';

INSERT IGNORE INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`) VALUES
(1, 'montar_e_remover_a_demonstrac_ao_1', 'Montar e remover a demonstração', 'Começo', 'administrador', 1),
(2, 'primeiros_passos_2', 'Primeiros passos', 'Começo', 'administrador', 2),
(3, 'pular_e_retomar_fases_3', 'Pular e retomar fases', 'Começo', 'administrador', 3),
(4, 'ver_como_comecar_4', 'Ver como começar', 'Começo', 'administrador', 4),
(5, 'apagar_aluno_5', 'Apagar aluno', 'Alunos', 'supervisao_secretaria', 5),
(6, 'cadastrar_e_editar_alunos_e_v_inculos_6', 'Cadastrar e editar alunos e vínculos', 'Alunos', 'secretaria', 6),
(7, 'consultar_a_enturmac_ao_7', 'Consultar a enturmação', 'Alunos', 'secretaria', 7),
(8, 'consultar_alunos_e_boletins_at_e_professor_8', 'Consultar alunos e boletins (até Professor)', 'Alunos', 'professor', 8),
(9, 'consultar_alunos_e_boletins_at_e_secretaria_9', 'Consultar alunos e boletins (até Secretaria)', 'Alunos', 'secretaria', 9),
(10, 'consultar_importac_oes_de_alunos_10', 'Consultar importações de alunos', 'Alunos', 'secretaria', 10),
(11, 'consultar_o_boletim_do_aluno_11', 'Consultar o boletim do aluno', 'Alunos', 'professor', 11),
(12, 'desfazer_importac_ao_de_alunos_12', 'Desfazer importação de alunos', 'Alunos', 'supervisao_secretaria', 12),
(13, 'enturmar_alunos_13', 'Enturmar alunos', 'Alunos', 'secretaria', 13),
(14, 'importar_alunos_de_planilha_14', 'Importar alunos de planilha', 'Alunos', 'secretaria', 14),
(15, 'registrar_o_tcc_15', 'Registrar o TCC', 'Alunos', 'secretaria', 15),
(16, 'atualizar_a_lista_de_notas_16', 'Atualizar a lista de notas', 'Notas e frequência', 'professor', 16),
(17, 'consultar_a_chamada_17', 'Consultar a chamada', 'Notas e frequência', 'professor', 17),
(18, 'consultar_atividades_e_notas_18', 'Consultar atividades e notas', 'Notas e frequência', 'professor', 18),
(19, 'consultar_faltas_19', 'Consultar faltas', 'Notas e frequência', 'professor', 19),
(20, 'consultar_notas_e_resultado_20', 'Consultar notas e resultado', 'Notas e frequência', 'professor', 20),
(21, 'excluir_encontro_da_chamada_21', 'Excluir encontro da chamada', 'Notas e frequência', 'supervisao_secretaria', 21),
(22, 'fazer_a_chamada_22', 'Fazer a chamada', 'Notas e frequência', 'professor', 22),
(23, 'lancar_faltas_e_compensac_oes_at_e_professor_23', 'Lançar faltas e compensações (até Professor)', 'Notas e frequência', 'professor', 23),
(24, 'lancar_faltas_e_compensac_oes_at_e_secretaria_24', 'Lançar faltas e compensações (até Secretaria)', 'Notas e frequência', 'secretaria', 24),
(25, 'lancar_notas_e_atividades_25', 'Lançar notas e atividades', 'Notas e frequência', 'professor', 25),
(26, 'minhas_disciplinas_26', 'Minhas disciplinas', 'Notas e frequência', 'professor', 26),
(27, 'consultar_documentos_emitidos_27', 'Consultar documentos emitidos', 'Documentos', 'secretaria', 27),
(28, 'emitir_e_cancelar_documentos_at_e_administrador_28', 'Emitir e cancelar documentos (até Administrador)', 'Documentos', 'administrador', 28),
(29, 'emitir_e_cancelar_documentos_at_e_secretaria_29', 'Emitir e cancelar documentos (até Secretaria)', 'Documentos', 'secretaria', 29),
(30, 'ver_a_pr_evia_do_hist_orico_30', 'Ver a prévia do histórico', 'Documentos', 'secretaria', 30),
(31, 'cadastrar_e_editar_anos_letivos_31', 'Cadastrar e editar anos letivos', 'Ensino', 'secretaria', 31),
(32, 'cadastrar_e_editar_cursos_e_a_coordenac_ao_32', 'Cadastrar e editar cursos e a coordenação', 'Ensino', 'administrador', 32),
(33, 'cadastrar_e_editar_mat_erias_at_e_administrador_33', 'Cadastrar e editar matérias (até Administrador)', 'Ensino', 'administrador', 33),
(34, 'cadastrar_e_editar_mat_erias_at_e_secretaria_34', 'Cadastrar e editar matérias (até Secretaria)', 'Ensino', 'secretaria', 34),
(35, 'cadastrar_e_editar_m_odulos_35', 'Cadastrar e editar módulos', 'Ensino', 'secretaria', 35),
(36, 'cadastrar_e_editar_polos_36', 'Cadastrar e editar polos', 'Ensino', 'administrador', 36),
(37, 'cadastrar_e_editar_salas_de_aula_37', 'Cadastrar e editar salas de aula', 'Ensino', 'administrador', 37),
(38, 'cadastrar_hor_arios_de_aula_38', 'Cadastrar horários de aula', 'Ensino', 'administrador', 38),
(39, 'consultar_a_matriz_39', 'Consultar a matriz', 'Ensino', 'secretaria', 39),
(40, 'consultar_anos_letivos_40', 'Consultar anos letivos', 'Ensino', 'secretaria', 40),
(41, 'consultar_cursos_at_e_coordenac_ao_41', 'Consultar cursos (até Coordenação)', 'Ensino', 'coordenacao', 41),
(42, 'consultar_cursos_at_e_direc_ao_42', 'Consultar cursos (até Direção)', 'Ensino', 'direcao', 42),
(43, 'consultar_disciplinas_ofertadas_at_e_coordenac_ao_43', 'Consultar disciplinas ofertadas (até Coordenação)', 'Ensino', 'coordenacao', 43),
(44, 'consultar_disciplinas_ofertadas_at_e_direc_ao_44', 'Consultar disciplinas ofertadas (até Direção)', 'Ensino', 'direcao', 44),
(45, 'consultar_disciplinas_ofertadas_at_e_professor_45', 'Consultar disciplinas ofertadas (até Professor)', 'Ensino', 'professor', 45),
(46, 'consultar_hor_arios_de_aula_46', 'Consultar horários de aula', 'Ensino', 'direcao', 46),
(47, 'consultar_mat_erias_at_e_professor_47', 'Consultar matérias (até Professor)', 'Ensino', 'professor', 47),
(48, 'consultar_mat_erias_at_e_secretaria_48', 'Consultar matérias (até Secretaria)', 'Ensino', 'secretaria', 48),
(49, 'consultar_m_odulos_49', 'Consultar módulos', 'Ensino', 'secretaria', 49),
(50, 'consultar_polos_50', 'Consultar polos', 'Ensino', 'direcao', 50),
(51, 'consultar_salas_de_aula_51', 'Consultar salas de aula', 'Ensino', 'direcao', 51),
(52, 'montar_a_matriz_as_grades_52', 'Montar a matriz (as grades)', 'Ensino', 'secretaria', 52),
(53, 'ofertar_encerrar_e_indicar_professores_at_e_administrador_53', 'Ofertar, encerrar e indicar professores (até Administrador)', 'Ensino', 'administrador', 53),
(54, 'ofertar_encerrar_e_indicar_professores_at_e_secretaria_54', 'Ofertar, encerrar e indicar professores (até Secretaria)', 'Ensino', 'secretaria', 54),
(55, 'reabrir_disciplina_encerrada_55', 'Reabrir disciplina encerrada', 'Ensino', 'supervisao_secretaria', 55),
(56, 'consultar_a_auditoria_de_notas_e_faltas_at_e_coordenac_ao_56', 'Consultar a auditoria de notas e faltas (até Coordenação)', 'Relatórios', 'coordenacao', 56),
(57, 'consultar_a_auditoria_de_notas_e_faltas_at_e_professor_57', 'Consultar a auditoria de notas e faltas (até Professor)', 'Relatórios', 'professor', 57),
(58, 'consultar_relat_orios_at_e_coordenac_ao_58', 'Consultar relatórios (até Coordenação)', 'Relatórios', 'coordenacao', 58),
(59, 'consultar_relat_orios_at_e_direc_ao_59', 'Consultar relatórios (até Direção)', 'Relatórios', 'direcao', 59),
(60, 'consultar_relat_orios_at_e_professor_60', 'Consultar relatórios (até Professor)', 'Relatórios', 'professor', 60),
(61, 'consultar_relat_orios_at_e_secretaria_61', 'Consultar relatórios (até Secretaria)', 'Relatórios', 'secretaria', 61),
(62, 'salvar_e_exportar_relat_orios_62', 'Salvar e exportar relatórios', 'Relatórios', 'administrador', 62),
(63, 'cadastrar_e_editar_a_equipe_63', 'Cadastrar e editar a equipe', 'Equipe', 'administrador', 63),
(64, 'consultar_a_equipe_64', 'Consultar a equipe', 'Equipe', 'direcao', 64),
(65, 'consultar_os_perfis_de_acesso_65', 'Consultar os perfis de acesso', 'Equipe', 'administrador', 65),
(66, 'consultar_tentativas_de_acesso_66', 'Consultar tentativas de acesso', 'Equipe', 'administrador', 66),
(67, 'liberar_acesso_bloqueado_67', 'Liberar acesso bloqueado', 'Equipe', 'administrador', 67),
(68, 'cadastrar_e_aplicar_regras_de_m_edia_at_e_administrador_68', 'Cadastrar e aplicar regras de média (até Administrador)', 'Configuração', 'administrador', 68),
(69, 'cadastrar_e_aplicar_regras_de_m_edia_at_e_secretaria_69', 'Cadastrar e aplicar regras de média (até Secretaria)', 'Configuração', 'secretaria', 69),
(70, 'cadastrar_e_editar_atos_legais_70', 'Cadastrar e editar atos legais', 'Configuração', 'administrador', 70),
(71, 'configurar_o_e_mail_de_envio_71', 'Configurar o e-mail de envio', 'Configuração', 'administrador', 71),
(72, 'consultar_atos_legais_72', 'Consultar atos legais', 'Configuração', 'direcao', 72),
(73, 'consultar_modelos_de_hist_orico_73', 'Consultar modelos de histórico', 'Configuração', 'direcao', 73),
(74, 'consultar_o_e_mail_de_envio_74', 'Consultar o e-mail de envio', 'Configuração', 'administrador', 74),
(75, 'consultar_par_ametros_da_instituic_ao_75', 'Consultar parâmetros da instituição', 'Configuração', 'direcao', 75),
(76, 'consultar_regras_de_m_edia_76', 'Consultar regras de média', 'Configuração', 'secretaria', 76),
(77, 'editar_modelos_de_hist_orico_77', 'Editar modelos de histórico', 'Configuração', 'administrador', 77),
(78, 'editar_os_dados_da_instituic_ao_78', 'Editar os dados da instituição', 'Configuração', 'administrador', 78),
(79, 'editar_par_ametros_da_instituic_ao_79', 'Editar parâmetros da instituição', 'Configuração', 'administrador', 79),
(80, 'consultar_a_documentac_ao_e_as_chamadas_da_api_80', 'Consultar a documentação e as chamadas da API', 'Integrações', 'administrador', 80),
(81, 'gerenciar_chaves_de_api_81', 'Gerenciar chaves de API', 'Integrações', 'administrador', 81),
(82, 'atualizar_o_sistema_82', 'Atualizar o sistema', 'Manutenção', 'administrador', 82),
(83, 'ver_as_atualizac_oes_pendentes_83', 'Ver as atualizações pendentes', 'Manutenção', 'administrador', 83),
(84, 'buscar_turmas_nas_telas_at_e_professor_84', 'Buscar turmas nas telas (até Professor)', 'Ajuda e perfil', 'professor', 84),
(85, 'buscar_turmas_nas_telas_at_e_secretaria_85', 'Buscar turmas nas telas (até Secretaria)', 'Ajuda e perfil', 'secretaria', 85),
(86, 'consultar_a_ajuda_86', 'Consultar a ajuda', 'Ajuda e perfil', 'professor', 86),
(87, 'p_agina_inicial_87', 'Página inicial', 'Ajuda e perfil', 'professor', 87),
(88, 'usar_o_faca_comigo_88', 'Usar o Faça comigo', 'Ajuda e perfil', 'professor', 88),
(89, 'ver_a_vers_ao_89', 'Ver a versão', 'Ajuda e perfil', 'professor', 89),
(90, 'ver_as_novidades_90', 'Ver as novidades', 'Ajuda e perfil', 'professor', 90),
(91, 'ver_e_editar_o_pr_oprio_perfil_91', 'Ver e editar o próprio perfil', 'Ajuda e perfil', 'professor', 91);

-- A página "Perfis de acesso" (só leitura, só o Administrador, no menu Equipe, no lugar de Níveis de acesso)
INSERT INTO `adms_paginas` (`id`, `menu_pagina`, `controller`, `metodo`, `menu_controller`, `menu_metodo`, `nome_pagina`, `obs`, `lib_pub`, `icone`, `adms_grps_pg_id`, `adms_tps_pg_id`, `adms_sits_pg_id`, `created`, `grava`, `sensivel`, `escopo`)
SELECT 492, 'Perfis de acesso', 'PerfisAcesso', 'index', 'perfis-acesso', 'index', 'Perfis de acesso', 'O que cada perfil pode fazer, por ação (só leitura).', 2, 'fas fa-user-shield', 1, 1, 1, NOW(), 0, 0, NULL
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_paginas` WHERE `id` = 492);
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 2, 492, 2, 2, 1, n.`id`, 492, NOW() FROM `adms_niveis_acessos` n
 WHERE NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x WHERE x.`adms_niveis_acesso_id` = n.`id` AND x.`adms_pagina_id` = 492);
UPDATE `adms_nivacs_pgs` d JOIN `adms_niveis_acessos` n ON n.`id` = d.`adms_niveis_acesso_id`
   SET d.`permissao` = 1, d.`lib_menu` = 1, d.`dropdown` = 1, d.`adms_menu_id` = 2, d.`ordem` = 13
 WHERE d.`adms_pagina_id` = 492 AND n.`papel` = 'administrador';

-- A ação de cada página
UPDATE `adms_paginas` SET `acao_id` = 9 WHERE (`controller`, `metodo`) IN (('Academicos', 'listar'), ('Academicos', 'visualizar'), ('Academicos', 'pendenciaSexo'));
UPDATE `adms_paginas` SET `acao_id` = 6 WHERE (`controller`, `metodo`) IN (('Academicos', 'cadastrar'), ('Academicos', 'editar'), ('Academicos', 'editarSenha'), ('Academicos', 'enviarLink'), ('Academicos', 'vincular'), ('Academicos', 'editarVinculo'), ('Academicos', 'posicionarModulo'));
UPDATE `adms_paginas` SET `acao_id` = 5 WHERE (`controller`, `metodo`) IN (('Academicos', 'apagar'));
UPDATE `adms_paginas` SET `acao_id` = 8 WHERE (`controller`, `metodo`) IN (('Academicos', 'boletim'), ('Academicos', 'imprimirBoletim'));
UPDATE `adms_paginas` SET `acao_id` = 86 WHERE (`controller`, `metodo`) IN (('Ajuda', 'index'), ('Ajuda', 'guia'), ('Ajuda', 'imagem'), ('Ajuda', 'videos'));
UPDATE `adms_paginas` SET `acao_id` = 40 WHERE (`controller`, `metodo`) IN (('AnosLetivos', 'listar'));
UPDATE `adms_paginas` SET `acao_id` = 31 WHERE (`controller`, `metodo`) IN (('AnosLetivos', 'cadastrar'), ('AnosLetivos', 'editar'), ('AnosLetivos', 'apagar'));
UPDATE `adms_paginas` SET `acao_id` = 18 WHERE (`controller`, `metodo`) IN (('Atividades', 'listar'), ('Atividades', 'visualizar'));
UPDATE `adms_paginas` SET `acao_id` = 25 WHERE (`controller`, `metodo`) IN (('Atividades', 'cadastrar'), ('Atividades', 'editar'), ('Atividades', 'apagar'), ('Atividades', 'editNotas'), ('Atividades', 'uploadNotas'));
UPDATE `adms_paginas` SET `acao_id` = 72 WHERE (`controller`, `metodo`) IN (('AtosLegais', 'listar'));
UPDATE `adms_paginas` SET `acao_id` = 70 WHERE (`controller`, `metodo`) IN (('AtosLegais', 'cadastrar'), ('AtosLegais', 'editar'));
UPDATE `adms_paginas` SET `acao_id` = 83 WHERE (`controller`, `metodo`) IN (('Atualizar', 'index'));
UPDATE `adms_paginas` SET `acao_id` = 82 WHERE (`controller`, `metodo`) IN (('Atualizar', 'aplicar'));
UPDATE `adms_paginas` SET `acao_id` = 56 WHERE (`controller`, `metodo`) IN (('Auditoria', 'listar'));
UPDATE `adms_paginas` SET `acao_id` = 57 WHERE (`controller`, `metodo`) IN (('Auditoria', 'nota'));
UPDATE `adms_paginas` SET `acao_id` = 11 WHERE (`controller`, `metodo`) IN (('BoletimAluno', 'listar'));
UPDATE `adms_paginas` SET `acao_id` = 85 WHERE (`controller`, `metodo`) IN (('Buscar', 'turmas'));
UPDATE `adms_paginas` SET `acao_id` = 84 WHERE (`controller`, `metodo`) IN (('Buscar', 'turmaserie'));
UPDATE `adms_paginas` SET `acao_id` = 17 WHERE (`controller`, `metodo`) IN (('Chamada', 'listar'));
UPDATE `adms_paginas` SET `acao_id` = 22 WHERE (`controller`, `metodo`) IN (('Chamada', 'encontro'), ('Chamada', 'fonte'));
UPDATE `adms_paginas` SET `acao_id` = 21 WHERE (`controller`, `metodo`) IN (('Chamada', 'excluir'));
UPDATE `adms_paginas` SET `acao_id` = 74 WHERE (`controller`, `metodo`) IN (('ConfiguracaoEmail', 'listar'), ('ConfiguracaoEmail', 'visualizar'));
UPDATE `adms_paginas` SET `acao_id` = 71 WHERE (`controller`, `metodo`) IN (('ConfiguracaoEmail', 'cadastrar'), ('ConfiguracaoEmail', 'editar'), ('ConfiguracaoEmail', 'apagar'), ('ConfiguracaoEmail', 'testar'));
UPDATE `adms_paginas` SET `acao_id` = 41 WHERE (`controller`, `metodo`) IN (('Cursos', 'listar'), ('Cursos', 'visualizar'));
UPDATE `adms_paginas` SET `acao_id` = 32 WHERE (`controller`, `metodo`) IN (('Cursos', 'cadastrar'), ('Cursos', 'editar'), ('Cursos', 'apagar'), ('Cursos', 'situacao'));
UPDATE `adms_paginas` SET `acao_id` = 42 WHERE (`controller`, `metodo`) IN (('Cursos', 'cadastrarUsuario'), ('Cursos', 'apagarUsuario'), ('Cursos', 'editarUsuario'));
UPDATE `adms_paginas` SET `acao_id` = 78 WHERE (`controller`, `metodo`) IN (('DadosInstituicao', 'editar'));
UPDATE `adms_paginas` SET `acao_id` = 4 WHERE (`controller`, `metodo`) IN (('Demonstracao', 'comecar'));
UPDATE `adms_paginas` SET `acao_id` = 1 WHERE (`controller`, `metodo`) IN (('Demonstracao', 'montar'), ('Demonstracao', 'instituicao'));
UPDATE `adms_paginas` SET `acao_id` = 43 WHERE (`controller`, `metodo`) IN (('Disciplinas', 'listar'));
UPDATE `adms_paginas` SET `acao_id` = 53 WHERE (`controller`, `metodo`) IN (('Disciplinas', 'cadastrar'), ('Disciplinas', 'apagar'), ('Disciplinas', 'adicionaPolivalencia'), ('Disciplinas', 'apagarPolivalencia'));
UPDATE `adms_paginas` SET `acao_id` = 54 WHERE (`controller`, `metodo`) IN (('Disciplinas', 'editar'), ('Disciplinas', 'adicionaProfessor'), ('Disciplinas', 'gerarOferta'), ('Disciplinas', 'encerrar'));
UPDATE `adms_paginas` SET `acao_id` = 45 WHERE (`controller`, `metodo`) IN (('Disciplinas', 'visualizar'));
UPDATE `adms_paginas` SET `acao_id` = 44 WHERE (`controller`, `metodo`) IN (('Disciplinas', 'verPolivalente'));
UPDATE `adms_paginas` SET `acao_id` = 55 WHERE (`controller`, `metodo`) IN (('Disciplinas', 'reabrir'));
UPDATE `adms_paginas` SET `acao_id` = 88 WHERE (`controller`, `metodo`) IN (('FacaComigo', 'iniciar'), ('FacaComigo', 'passos'));
UPDATE `adms_paginas` SET `acao_id` = 19 WHERE (`controller`, `metodo`) IN (('Faltas', 'visualizar'), ('Faltas', 'modelo'));
UPDATE `adms_paginas` SET `acao_id` = 23 WHERE (`controller`, `metodo`) IN (('Faltas', 'editar'), ('Faltas', 'importar'));
UPDATE `adms_paginas` SET `acao_id` = 24 WHERE (`controller`, `metodo`) IN (('Faltas', 'compensar'), ('Faltas', 'removerCompensacao'));
UPDATE `adms_paginas` SET `acao_id` = 30 WHERE (`controller`, `metodo`) IN (('Historico', 'previa'));
UPDATE `adms_paginas` SET `acao_id` = 27 WHERE (`controller`, `metodo`) IN (('HistoricoEmissoes', 'listar'), ('HistoricoEmissoes', 'previa'), ('HistoricoEmissoes', 'ver'), ('HistoricoEmissoes', 'pdf'));
UPDATE `adms_paginas` SET `acao_id` = 29 WHERE (`controller`, `metodo`) IN (('HistoricoEmissoes', 'emitir'), ('HistoricoEmissoes', 'segundaVia'));
UPDATE `adms_paginas` SET `acao_id` = 28 WHERE (`controller`, `metodo`) IN (('HistoricoEmissoes', 'cancelar'));
UPDATE `adms_paginas` SET `acao_id` = 73 WHERE (`controller`, `metodo`) IN (('HistoricoModelos', 'listar'), ('HistoricoModelos', 'previa'));
UPDATE `adms_paginas` SET `acao_id` = 77 WHERE (`controller`, `metodo`) IN (('HistoricoModelos', 'cadastrar'), ('HistoricoModelos', 'editar'));
UPDATE `adms_paginas` SET `acao_id` = 87 WHERE (`controller`, `metodo`) IN (('Home', 'index'));
UPDATE `adms_paginas` SET `acao_id` = 46 WHERE (`controller`, `metodo`) IN (('HorarioAula', 'listar'), ('HorarioAula', 'visualizar'));
UPDATE `adms_paginas` SET `acao_id` = 38 WHERE (`controller`, `metodo`) IN (('HorarioAula', 'editar'), ('HorarioAula', 'cadastrarSala'));
UPDATE `adms_paginas` SET `acao_id` = 10 WHERE (`controller`, `metodo`) IN (('ImportacaoAlunos', 'listar'), ('ImportacaoAlunos', 'conferir'), ('ImportacaoAlunos', 'modelo'));
UPDATE `adms_paginas` SET `acao_id` = 14 WHERE (`controller`, `metodo`) IN (('ImportacaoAlunos', 'enviar'), ('ImportacaoAlunos', 'mapear'), ('ImportacaoAlunos', 'corrigir'), ('ImportacaoAlunos', 'aplicar'));
UPDATE `adms_paginas` SET `acao_id` = 12 WHERE (`controller`, `metodo`) IN (('ImportacaoAlunos', 'desfazer'));
UPDATE `adms_paginas` SET `acao_id` = 80 WHERE (`controller`, `metodo`) IN (('Integracoes', 'chaves'), ('Integracoes', 'documentacao'), ('Integracoes', 'chamadas'), ('Integracoes', 'especificacao'));
UPDATE `adms_paginas` SET `acao_id` = 81 WHERE (`controller`, `metodo`) IN (('Integracoes', 'criar'), ('Integracoes', 'revogar'));
UPDATE `adms_paginas` SET `acao_id` = 48 WHERE (`controller`, `metodo`) IN (('Materias', 'listar'));
UPDATE `adms_paginas` SET `acao_id` = 34 WHERE (`controller`, `metodo`) IN (('Materias', 'cadastrar'), ('Materias', 'editar'), ('Materias', 'situacao'));
UPDATE `adms_paginas` SET `acao_id` = 47 WHERE (`controller`, `metodo`) IN (('Materias', 'visualizar'));
UPDATE `adms_paginas` SET `acao_id` = 33 WHERE (`controller`, `metodo`) IN (('Materias', 'apagar'));
UPDATE `adms_paginas` SET `acao_id` = 39 WHERE (`controller`, `metodo`) IN (('Matriz', 'listar'), ('Matriz', 'visualizar'));
UPDATE `adms_paginas` SET `acao_id` = 52 WHERE (`controller`, `metodo`) IN (('Matriz', 'cadastrarGrade'), ('Matriz', 'editarGrade'), ('Matriz', 'situacaoGrade'), ('Matriz', 'adicionarDisciplina'), ('Matriz', 'moverDisciplina'), ('Matriz', 'removerDisciplina'), ('Matriz', 'ordenarDisciplinas'));
UPDATE `adms_paginas` SET `acao_id` = 20 WHERE (`controller`, `metodo`) IN (('Medias', 'final'));
UPDATE `adms_paginas` SET `acao_id` = 16 WHERE (`controller`, `metodo`) IN (('Medias', 'atualizarLista'));
UPDATE `adms_paginas` SET `acao_id` = 26 WHERE (`controller`, `metodo`) IN (('MinhasDisciplinas', 'listar'));
UPDATE `adms_paginas` SET `acao_id` = 49 WHERE (`controller`, `metodo`) IN (('Modulos', 'listar'));
UPDATE `adms_paginas` SET `acao_id` = 35 WHERE (`controller`, `metodo`) IN (('Modulos', 'cadastrar'), ('Modulos', 'editar'), ('Modulos', 'apagar'));
UPDATE `adms_paginas` SET `acao_id` = 90 WHERE (`controller`, `metodo`) IN (('Novidades', 'listar'), ('Novidades', 'visto'));
UPDATE `adms_paginas` SET `acao_id` = 75 WHERE (`controller`, `metodo`) IN (('Parametros', 'listar'));
UPDATE `adms_paginas` SET `acao_id` = 79 WHERE (`controller`, `metodo`) IN (('Parametros', 'salvar'));
UPDATE `adms_paginas` SET `acao_id` = 91 WHERE (`controller`, `metodo`) IN (('Perfil', 'visualizar'), ('Perfil', 'editar'), ('Perfil', 'editarSenha'), ('Perfil', 'atualizarDados'));
UPDATE `adms_paginas` SET `acao_id` = 50 WHERE (`controller`, `metodo`) IN (('Polos', 'listar'));
UPDATE `adms_paginas` SET `acao_id` = 36 WHERE (`controller`, `metodo`) IN (('Polos', 'cadastrar'), ('Polos', 'editar'));
UPDATE `adms_paginas` SET `acao_id` = 7 WHERE (`controller`, `metodo`) IN (('PortalAlunos', 'listar'), ('PortalAlunos', 'visualizar'));
UPDATE `adms_paginas` SET `acao_id` = 13 WHERE (`controller`, `metodo`) IN (('PortalAlunos', 'ordenacao'), ('PortalAlunos', 'confirmarEnturmacao'));
UPDATE `adms_paginas` SET `acao_id` = 2 WHERE (`controller`, `metodo`) IN (('PrimeirosPassos', 'index'));
UPDATE `adms_paginas` SET `acao_id` = 3 WHERE (`controller`, `metodo`) IN (('PrimeirosPassos', 'fase'));
UPDATE `adms_paginas` SET `acao_id` = 76 WHERE (`controller`, `metodo`) IN (('RegraMedia', 'listar'), ('RegraMedia', 'oferta'), ('RegraMedia', 'simular'));
UPDATE `adms_paginas` SET `acao_id` = 69 WHERE (`controller`, `metodo`) IN (('RegraMedia', 'curso'), ('RegraMedia', 'editar'), ('RegraMedia', 'cadastrar'), ('RegraMedia', 'duplicar'), ('RegraMedia', 'inativar'), ('RegraMedia', 'aplicar'), ('RegraMedia', 'disciplina'), ('RegraMedia', 'instituicao'));
UPDATE `adms_paginas` SET `acao_id` = 68 WHERE (`controller`, `metodo`) IN (('RegraMedia', 'padrao'));
UPDATE `adms_paginas` SET `acao_id` = 60 WHERE (`controller`, `metodo`) IN (('Relatorios', 'listar'), ('Relatorios', 'salvar'), ('Relatorios', 'apagarSalvo'), ('Relatorios', 'exportar'), ('Relatorios', 'listaTurma'), ('Relatorios', 'mapaNotas'), ('Relatorios', 'frequenciaOferta'));
UPDATE `adms_paginas` SET `acao_id` = 62 WHERE (`controller`, `metodo`) IN (('Relatorios', 'parametros'));
UPDATE `adms_paginas` SET `acao_id` = 61 WHERE (`controller`, `metodo`) IN (('Relatorios', 'cpfCompleto'), ('Relatorios', 'qualidadeCadastro'), ('Relatorios', 'aptosConcluir'), ('Relatorios', 'documentosEmitidos'));
UPDATE `adms_paginas` SET `acao_id` = 58 WHERE (`controller`, `metodo`) IN (('Relatorios', 'painelAcademico'), ('Relatorios', 'alunosSituacao'), ('Relatorios', 'previsaoConclusao'), ('Relatorios', 'resultadoDisciplina'), ('Relatorios', 'distribuicaoNotas'), ('Relatorios', 'pendenciasLancamento'), ('Relatorios', 'desempenhoAluno'), ('Relatorios', 'frequenciaMinimo'), ('Relatorios', 'alunosRisco'), ('Relatorios', 'situacaoTcc'));
UPDATE `adms_paginas` SET `acao_id` = 59 WHERE (`controller`, `metodo`) IN (('Relatorios', 'coorteIngresso'), ('Relatorios', 'concluintes'), ('Relatorios', 'reaberturas'), ('Relatorios', 'trocasRegra'), ('Relatorios', 'exportacoes'), ('Relatorios', 'usuariosAcesso'));
UPDATE `adms_paginas` SET `acao_id` = 51 WHERE (`controller`, `metodo`) IN (('SalasDeAula', 'listar'), ('SalasDeAula', 'visualizar'));
UPDATE `adms_paginas` SET `acao_id` = 37 WHERE (`controller`, `metodo`) IN (('SalasDeAula', 'cadastrar'), ('SalasDeAula', 'editar'), ('SalasDeAula', 'apagar'));
UPDATE `adms_paginas` SET `acao_id` = 15 WHERE (`controller`, `metodo`) IN (('Tcc', 'salvar'));
UPDATE `adms_paginas` SET `acao_id` = 66 WHERE (`controller`, `metodo`) IN (('TentativasAcesso', 'listar'));
UPDATE `adms_paginas` SET `acao_id` = 67 WHERE (`controller`, `metodo`) IN (('TentativasAcesso', 'liberar'));
UPDATE `adms_paginas` SET `acao_id` = 64 WHERE (`controller`, `metodo`) IN (('Usuarios', 'listar'), ('Usuarios', 'visualizar'));
UPDATE `adms_paginas` SET `acao_id` = 63 WHERE (`controller`, `metodo`) IN (('Usuarios', 'cadastrar'), ('Usuarios', 'editar'), ('Usuarios', 'apagar'), ('Usuarios', 'editarSenha'));
UPDATE `adms_paginas` SET `acao_id` = 89 WHERE (`controller`, `metodo`) IN (('Versao', 'index'));
UPDATE `adms_paginas` SET `acao_id` = 65 WHERE (`controller`, `metodo`) IN (('PerfisAcesso', 'index'));

-- Saem as telas de programação (as páginas e os vínculos; o motor de permissão fica)
DELETE n FROM `adms_nivacs_pgs` n JOIN `adms_paginas` p ON p.`id` = n.`adms_pagina_id` WHERE p.`controller` IN ('NivelAcesso', 'Paginas', 'PaginasAcademico', 'Menu', 'AcessoAcademico', 'Liberar', 'LiberarAcademico');
DELETE FROM `adms_paginas` WHERE `controller` IN ('NivelAcesso', 'Paginas', 'PaginasAcademico', 'Menu', 'AcessoAcademico', 'Liberar', 'LiberarAcademico');

-- Os nomes do menu: o grupo da Página Inicial em português; "Matérias", sem "de Ensino"
UPDATE `adms_menus` SET `nome` = 'Início' WHERE `id` = 1 AND `nome` = 'Dashboard';
UPDATE `adms_paginas` SET `menu_pagina` = 'Matérias' WHERE `controller` = 'Materias' AND `metodo` = 'listar' AND `menu_pagina` = 'Matérias de Ensino';
UPDATE `adms_paginas` SET `nome_pagina` = 'Listar cursos' WHERE `controller` = 'Cursos' AND `metodo` = 'listar' AND `nome_pagina` LIKE '%(Ensino)%';
UPDATE `adms_paginas` SET `nome_pagina` = 'Listar matérias' WHERE `controller` = 'Materias' AND `metodo` = 'listar' AND `nome_pagina` = 'Listar Matérias de Ensino';
UPDATE `adms_paginas` SET `menu_pagina` = 'Adicionar coordenação do curso', `nome_pagina` = 'Adicionar coordenação do curso' WHERE `controller` = 'Cursos' AND `metodo` = 'cadastrarUsuario' AND `nome_pagina` = 'Cadastro de coordenação do Ensino';
UPDATE `adms_paginas` SET `menu_pagina` = 'Seleção de turma', `nome_pagina` = 'Seleção de turma' WHERE `controller` = 'Buscar' AND `metodo` = 'turmaserie' AND `nome_pagina` = 'Seleção de série do ensino';

-- A fase "Os alunos" dos Primeiros passos: os três caminhos já aparecem embaixo, com os links (a frase saía duas vezes)
UPDATE `adms_fases` SET `texto` = 'Os alunos e o vínculo deles com o curso.' WHERE `codigo` = 'alunos' AND `texto` = 'Os alunos e o vínculo deles com o curso: cadastrar na tela, importar de uma planilha ou integrar com outro sistema pela API.';

-- A versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.29.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 45, '1.29.0', 1, 29, 0, '2026-11-15', 'O produto se configura, não se programa pela tela: saem as telas de páginas, menus e níveis; cada permissão é uma ação com nome e limite, e a tela Perfis de acesso mostra o que cada perfil pode fazer.', 1, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.29.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.29.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 134, 45, 'Perfis de acesso', 'Em Equipe → Perfis de acesso, o que cada perfil pode fazer, por ação ("Lançar notas e atividades", "Reabrir disciplina encerrada"), com o limite de cada uma. Os perfis vêm prontos: a tela é só para consultar, e o sistema recusa a ação dada a um perfil abaixo do limite.', 1, 'administrador', 'perfis-de-acesso', '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 134);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 135, 45, 'Menos telas técnicas', 'Saíram as telas de páginas, itens de menu, níveis e acessos (eram de quem programa o sistema). O grupo Manutenção fica com Atualizar o sistema. Os nomes ficaram mais claros: o grupo da Página Inicial é Início, e "Ensino" no sentido de curso virou curso.', 2, 'administrador', NULL, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 135);
