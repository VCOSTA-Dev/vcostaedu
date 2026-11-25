-- 0005 — ONDA 2, ETAPA 2: os perfis de acesso do produto aberto (versão 1.20.0). docs/NIVEIS_PRODUTO_ABERTO.md.
--
-- No produto aberto quem instala é o dono: não há nível do fornecedor. Esta migration:
--  1. dá ao NÍVEL o papel (papel) e as marcas (so_consulta, acoes_sensiveis), e à PÁGINA o que ela faz (grava,
--     sensivel) e o parâmetro que recebe (escopo: oferta, atividade, curso, aluno, relatorio) — o código pergunta
--     isso, não o id;
--  2. aproveita e renomeia os níveis que já cumprem um papel (1 Administrador, 3 Direção acadêmica, 4 Secretaria,
--     5 Coordenação de curso, 6 Professor) e cria a Supervisão de secretaria;
--  3. tira os níveis do fornecedor (7 Supervisor VCOSTA, 8 VCOSTA, 9 Suporte técnico) e o 2 (repetia o papel do
--     administrador): os usuários deles passam ao Administrador; as visões de relatório compartilhadas com eles,
--     também; as telas de manutenção ficam no menu do Administrador (grupo "Manutenção");
--  4. monta a matriz de cada papel;
--  5. registra a versão 1.20.0 e as novidades.
-- Idempotente: rodar de novo não muda nada.

-- 1. as colunas
ALTER TABLE `adms_niveis_acessos`
  ADD COLUMN IF NOT EXISTS `papel` varchar(40) DEFAULT NULL COMMENT 'Onda 2: o papel do produto (administrador, direcao, supervisao_secretaria, secretaria, coordenacao, professor); NULL = nível criado na tela' AFTER `ordem`,
  ADD COLUMN IF NOT EXISTS `so_consulta` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Onda 2: 1 = não abre página que grava (adms_paginas.grava)' AFTER `papel`,
  ADD COLUMN IF NOT EXISTS `acoes_sensiveis` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Onda 2: 1 = abre as páginas sensíveis (adms_paginas.sensivel)' AFTER `so_consulta`;
ALTER TABLE `adms_niveis_acessos` ADD UNIQUE KEY IF NOT EXISTS `uk_niveis_papel` (`papel`);
ALTER TABLE `adms_paginas`
  ADD COLUMN IF NOT EXISTS `grava` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Onda 2: 1 = a página grava (o nível só consulta não abre)',
  ADD COLUMN IF NOT EXISTS `sensivel` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Onda 2: 1 = ação sensível (só quem tem acoes_sensiveis)',
  ADD COLUMN IF NOT EXISTS `escopo` varchar(20) DEFAULT NULL COMMENT 'Onda 2: o parâmetro da rota (oferta, atividade, curso, aluno, relatorio), conferido por AdmsEscopo';

-- o que cada página faz
UPDATE `adms_paginas` SET `grava` = 1 WHERE (`controller`, `metodo`) IN (
   ('Academicos','apagar'),
   ('Academicos','cadastrar'),
   ('Academicos','editar'),
   ('Academicos','editarSenha'),
   ('Academicos','editarVinculo'),
   ('Academicos','enviarLink'),
   ('Academicos','posicionarModulo'),
   ('Academicos','vincular'),
   ('AcessoAcademico','apagar'),
   ('AcessoAcademico','cadastrar'),
   ('AcessoAcademico','editar'),
   ('AnosLetivos','apagar'),
   ('AnosLetivos','cadastrar'),
   ('AnosLetivos','editar'),
   ('Atividades','apagar'),
   ('Atividades','cadastrar'),
   ('Atividades','editar'),
   ('Atividades','editNotas'),
   ('Atividades','uploadNotas'),
   ('AtosLegais','cadastrar'),
   ('AtosLegais','editar'),
   ('Chamada','encontro'),
   ('Chamada','excluir'),
   ('Chamada','fonte'),
   ('ConfiguracaoEmail','apagar'),
   ('ConfiguracaoEmail','cadastrar'),
   ('ConfiguracaoEmail','editar'),
   ('Cursos','apagar'),
   ('Cursos','cadastrar'),
   ('Cursos','editar'),
   ('Cursos','situacao'),
   ('DadosInstituicao','editar'),
   ('Disciplinas','adicionaPolivalencia'),
   ('Disciplinas','adicionaProfessor'),
   ('Disciplinas','apagar'),
   ('Disciplinas','apagarPolivalencia'),
   ('Disciplinas','cadastrar'),
   ('Disciplinas','editar'),
   ('Disciplinas','encerrar'),
   ('Disciplinas','gerarOferta'),
   ('Disciplinas','reabrir'),
   ('Faltas','compensar'),
   ('Faltas','editar'),
   ('Faltas','importar'),
   ('Faltas','removerCompensacao'),
   ('HistoricoEmissoes','cancelar'),
   ('HistoricoEmissoes','emitir'),
   ('HistoricoEmissoes','segundaVia'),
   ('HistoricoModelos','cadastrar'),
   ('HistoricoModelos','editar'),
   ('HorarioAula','cadastrarSala'),
   ('HorarioAula','editar'),
   ('Liberar','dropdown'),
   ('Liberar','menu'),
   ('Liberar','permissao'),
   ('LiberarAcademico','dropdown'),
   ('LiberarAcademico','menu'),
   ('LiberarAcademico','permissao'),
   ('Materias','apagar'),
   ('Materias','cadastrar'),
   ('Materias','editar'),
   ('Materias','situacao'),
   ('Matriz','adicionarDisciplina'),
   ('Matriz','cadastrarGrade'),
   ('Matriz','editarGrade'),
   ('Matriz','moverDisciplina'),
   ('Matriz','ordenarDisciplinas'),
   ('Matriz','removerDisciplina'),
   ('Matriz','situacaoGrade'),
   ('Medias','atualizarLista'),
   ('Menu','apagar'),
   ('Menu','cadastrar'),
   ('Menu','editar'),
   ('Menu','ordenacao'),
   ('Modulos','apagar'),
   ('Modulos','cadastrar'),
   ('Modulos','editar'),
   ('NivelAcesso','apagar'),
   ('NivelAcesso','atualizarPermissao'),
   ('NivelAcesso','cadastrar'),
   ('NivelAcesso','editar'),
   ('NivelAcesso','itemMenu'),
   ('NivelAcesso','ordenacao'),
   ('Paginas','cadastrar'),
   ('Paginas','editar'),
   ('Paginas','editarMenu'),
   ('PaginasAcademico','apagar'),
   ('PaginasAcademico','cadastrar'),
   ('PaginasAcademico','editar'),
   ('PaginasAcademico','editarMenu'),
   ('Parametros','salvar'),
   ('Polos','cadastrar'),
   ('Polos','editar'),
   ('PortalAlunos','confirmarEnturmacao'),
   ('PortalAlunos','ordenacao'),
   ('RegraMedia','aplicar'),
   ('RegraMedia','cadastrar'),
   ('RegraMedia','curso'),
   ('RegraMedia','disciplina'),
   ('RegraMedia','duplicar'),
   ('RegraMedia','editar'),
   ('RegraMedia','inativar'),
   ('RegraMedia','instituicao'),
   ('RegraMedia','padrao'),
   ('Relatorios','parametros'),
   ('SalasDeAula','apagar'),
   ('SalasDeAula','cadastrar'),
   ('SalasDeAula','editar'),
   ('Tcc','salvar'),
   ('TentativasAcesso','liberar'),
   ('Usuarios','apagar'),
   ('Usuarios','cadastrar'),
   ('Usuarios','editar'),
   ('Usuarios','editarSenha'));
UPDATE `adms_paginas` SET `sensivel` = 1 WHERE (`controller`, `metodo`) IN (
   ('Disciplinas','reabrir'),
   ('Chamada','excluir'),
   ('Academicos','apagar'));
UPDATE `adms_paginas` SET `escopo` = 'oferta' WHERE `controller` = 'Disciplinas' AND `metodo` = 'visualizar';
UPDATE `adms_paginas` SET `escopo` = 'oferta' WHERE `controller` = 'Atividades' AND `metodo` = 'listar';
UPDATE `adms_paginas` SET `escopo` = 'oferta' WHERE `controller` = 'Faltas' AND `metodo` = 'visualizar';
UPDATE `adms_paginas` SET `escopo` = 'oferta' WHERE `controller` = 'Chamada' AND `metodo` = 'listar';
UPDATE `adms_paginas` SET `escopo` = 'oferta' WHERE `controller` = 'Medias' AND `metodo` = 'final';
UPDATE `adms_paginas` SET `escopo` = 'atividade' WHERE `controller` = 'Atividades' AND `metodo` = 'visualizar';
UPDATE `adms_paginas` SET `escopo` = 'curso' WHERE `controller` = 'Cursos' AND `metodo` = 'visualizar';
UPDATE `adms_paginas` SET `escopo` = 'aluno' WHERE `controller` = 'Academicos' AND `metodo` = 'boletim';
UPDATE `adms_paginas` SET `escopo` = 'aluno' WHERE `controller` = 'Academicos' AND `metodo` = 'imprimirBoletim';
UPDATE `adms_paginas` SET `escopo` = 'relatorio' WHERE `controller` = 'Relatorios' AND `metodo` NOT IN ('listar', 'salvar', 'apagarSalvo', 'exportar', 'parametros', 'cpfCompleto');

-- 2. os papéis dos níveis que ficam
UPDATE `adms_niveis_acessos` SET `nome` = 'Administrador', `papel` = 'administrador', `so_consulta` = 0, `acoes_sensiveis` = 1 WHERE `id` = 1;
UPDATE `adms_niveis_acessos` SET `nome` = 'Direção acadêmica', `papel` = 'direcao', `so_consulta` = 1, `acoes_sensiveis` = 0 WHERE `id` = 3;
UPDATE `adms_niveis_acessos` SET `nome` = 'Secretaria', `papel` = 'secretaria', `so_consulta` = 0, `acoes_sensiveis` = 0 WHERE `id` = 4;
UPDATE `adms_niveis_acessos` SET `nome` = 'Coordenação de curso', `papel` = 'coordenacao', `so_consulta` = 1, `acoes_sensiveis` = 0 WHERE `id` = 5;
UPDATE `adms_niveis_acessos` SET `nome` = 'Professor', `papel` = 'professor', `so_consulta` = 0, `acoes_sensiveis` = 0 WHERE `id` = 6;
INSERT INTO `adms_niveis_acessos` (`nome`, `ordem`, `papel`, `so_consulta`, `acoes_sensiveis`, `situacao`, `usuario_cre`, `created`)
SELECT 'Supervisão de secretaria', 3, 'supervisao_secretaria', 0, 1, 1, 0, NOW()
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_niveis_acessos` WHERE `papel` = 'supervisao_secretaria');

-- 3. os níveis que saem: os usuários e as visões compartilhadas passam ao Administrador
UPDATE `adms_usuarios` SET `adms_niveis_acesso_id` = 1, `modified` = NOW() WHERE `adms_niveis_acesso_id` IN (2, 7, 8, 9);
UPDATE `rm_relatorios_salvos` SET `nivel_acesso_id` = 1 WHERE `nivel_acesso_id` IN (2, 7, 8, 9);
-- as telas de manutenção que o Suporte técnico via no menu passam ao menu do Administrador
UPDATE `adms_nivacs_pgs` a INNER JOIN `adms_nivacs_pgs` s ON s.`adms_pagina_id` = a.`adms_pagina_id` AND s.`adms_niveis_acesso_id` = 9
   SET a.`lib_menu` = s.`lib_menu`, a.`adms_menu_id` = s.`adms_menu_id`, a.`ordem` = s.`ordem`, a.`dropdown` = s.`dropdown`
 WHERE a.`adms_niveis_acesso_id` = 1 AND s.`permissao` = 1 AND s.`lib_menu` = 1;
UPDATE `adms_menus` SET `nome` = 'Manutenção' WHERE `id` = 9 AND `nome` = 'Suporte técnico';
DELETE FROM `adms_nivacs_pgs` WHERE `adms_niveis_acesso_id` IN (2, 7, 8, 9);
DELETE FROM `adms_niveis_acessos` WHERE `id` IN (2, 7, 8, 9);

-- a hierarquia: os papéis primeiro, os níveis criados na tela depois, na ordem em que estavam
UPDATE `adms_niveis_acessos` SET `ordem` = CASE `papel` WHEN 'administrador' THEN 1 WHEN 'direcao' THEN 2 WHEN 'supervisao_secretaria' THEN 3
                                              WHEN 'secretaria' THEN 4 WHEN 'coordenacao' THEN 5 WHEN 'professor' THEN 6 END
 WHERE `papel` IS NOT NULL;
SET @ordem := 6;
UPDATE `adms_niveis_acessos` SET `ordem` = (@ordem := @ordem + 1) WHERE `papel` IS NULL ORDER BY `ordem`, `id`;

-- 4. a matriz. Todo nível com papel tem uma linha por página, com o lugar no menu do Administrador.
INSERT INTO `adms_nivacs_pgs` (`permissao`, `ordem`, `dropdown`, `lib_menu`, `adms_menu_id`, `adms_niveis_acesso_id`, `adms_pagina_id`, `created`)
SELECT 2, a.`ordem`, a.`dropdown`, 2, a.`adms_menu_id`, n.`id`, a.`adms_pagina_id`, NOW()
  FROM `adms_nivacs_pgs` a
  JOIN `adms_niveis_acessos` n ON n.`papel` IN ('direcao', 'coordenacao', 'supervisao_secretaria')
 WHERE a.`adms_niveis_acesso_id` = 1
   AND NOT EXISTS (SELECT 1 FROM (SELECT `adms_niveis_acesso_id`, `adms_pagina_id` FROM `adms_nivacs_pgs`) x
                    WHERE x.`adms_niveis_acesso_id` = n.`id` AND x.`adms_pagina_id` = a.`adms_pagina_id`);

-- Administrador: todas as páginas
UPDATE `adms_nivacs_pgs` SET `permissao` = 1 WHERE `adms_niveis_acesso_id` = 1;

-- Direção acadêmica: toda página de consulta, menos a administração do sistema; e a atribuição de coordenador
UPDATE `adms_nivacs_pgs` d
  JOIN `adms_paginas` p ON p.`id` = d.`adms_pagina_id`
  JOIN `adms_nivacs_pgs` a ON a.`adms_pagina_id` = d.`adms_pagina_id` AND a.`adms_niveis_acesso_id` = 1
   SET d.`permissao` = IF(p.`grava` = 0 AND p.`controller` NOT IN ('NivelAcesso', 'Liberar', 'LiberarAcademico', 'Paginas', 'PaginasAcademico', 'Menu', 'AcessoAcademico', 'ConfiguracaoEmail', 'TentativasAcesso'), 1, 2),
       d.`lib_menu` = IF(p.`grava` = 0 AND p.`controller` NOT IN ('NivelAcesso', 'Liberar', 'LiberarAcademico', 'Paginas', 'PaginasAcademico', 'Menu', 'AcessoAcademico', 'ConfiguracaoEmail', 'TentativasAcesso') AND a.`lib_menu` = 1, 1, 2),
       d.`adms_menu_id` = a.`adms_menu_id`, d.`ordem` = a.`ordem`, d.`dropdown` = a.`dropdown`
 WHERE d.`adms_niveis_acesso_id` = (SELECT id FROM (SELECT `id` FROM `adms_niveis_acessos` WHERE `papel` = 'direcao') q);

-- Coordenação de curso: a visão do professor nos cursos que coordena, os relatórios com o recorte do curso
UPDATE `adms_nivacs_pgs` d
  JOIN `adms_paginas` p ON p.`id` = d.`adms_pagina_id`
  JOIN `adms_nivacs_pgs` a ON a.`adms_pagina_id` = d.`adms_pagina_id` AND a.`adms_niveis_acesso_id` = 1
   SET d.`permissao` = IF((p.`controller`, p.`metodo`) IN (
   ('Home','index'),
   ('Perfil','visualizar'),
   ('Perfil','editar'),
   ('Perfil','editarSenha'),
   ('Perfil','atualizarDados'),
   ('Ajuda','index'),
   ('Ajuda','guia'),
   ('Ajuda','imagem'),
   ('Ajuda','videos'),
   ('Novidades','listar'),
   ('Novidades','visto'),
   ('MinhasDisciplinas','listar'),
   ('Disciplinas','listar'),
   ('Disciplinas','visualizar'),
   ('Atividades','listar'),
   ('Atividades','visualizar'),
   ('Faltas','visualizar'),
   ('Chamada','listar'),
   ('Medias','final'),
   ('Cursos','listar'),
   ('Cursos','visualizar'),
   ('Academicos','boletim'),
   ('Academicos','imprimirBoletim'),
   ('Relatorios','listar'),
   ('Relatorios','exportar'),
   ('Relatorios','salvar'),
   ('Relatorios','apagarSalvo'),
   ('Relatorios','alunosRisco'),
   ('Relatorios','alunosSituacao'),
   ('Relatorios','desempenhoAluno'),
   ('Relatorios','distribuicaoNotas'),
   ('Relatorios','frequenciaMinimo'),
   ('Relatorios','frequenciaOferta'),
   ('Relatorios','listaTurma'),
   ('Relatorios','mapaNotas'),
   ('Relatorios','painelAcademico'),
   ('Relatorios','pendenciasLancamento'),
   ('Relatorios','previsaoConclusao'),
   ('Relatorios','resultadoDisciplina'),
   ('Relatorios','situacaoTcc')), 1, 2),
       d.`adms_menu_id` = a.`adms_menu_id`, d.`ordem` = a.`ordem`, d.`dropdown` = a.`dropdown`
 WHERE d.`adms_niveis_acesso_id` = (SELECT id FROM (SELECT `id` FROM `adms_niveis_acessos` WHERE `papel` = 'coordenacao') q);
UPDATE `adms_nivacs_pgs` d JOIN `adms_nivacs_pgs` a ON a.`adms_pagina_id` = d.`adms_pagina_id` AND a.`adms_niveis_acesso_id` = 1
   SET d.`lib_menu` = IF(d.`permissao` = 1 AND a.`lib_menu` = 1, 1, 2)
 WHERE d.`adms_niveis_acesso_id` = (SELECT id FROM (SELECT `id` FROM `adms_niveis_acessos` WHERE `papel` = 'coordenacao') q);

-- Supervisão de secretaria: a matriz da Secretaria (com o lugar no menu dela) e as ações sensíveis
UPDATE `adms_nivacs_pgs` v
  JOIN `adms_nivacs_pgs` s ON s.`adms_pagina_id` = v.`adms_pagina_id` AND s.`adms_niveis_acesso_id` = 4
   SET v.`permissao` = s.`permissao`, v.`lib_menu` = s.`lib_menu`, v.`adms_menu_id` = s.`adms_menu_id`, v.`ordem` = s.`ordem`, v.`dropdown` = s.`dropdown`
 WHERE v.`adms_niveis_acesso_id` = (SELECT id FROM (SELECT `id` FROM `adms_niveis_acessos` WHERE `papel` = 'supervisao_secretaria') q);
UPDATE `adms_nivacs_pgs` v JOIN `adms_paginas` p ON p.`id` = v.`adms_pagina_id`
   SET v.`permissao` = 1
 WHERE p.`sensivel` = 1 AND v.`adms_niveis_acesso_id` = (SELECT id FROM (SELECT `id` FROM `adms_niveis_acessos` WHERE `papel` = 'supervisao_secretaria') q);

-- a atribuição de coordenador: só o Administrador e a Direção acadêmica
UPDATE `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` = d.`adms_pagina_id`
   SET d.`permissao` = 2, d.`lib_menu` = 2
 WHERE (p.`controller`, p.`metodo`) IN (('Cursos','cadastrarUsuario'),
   ('Cursos','editarUsuario'),
   ('Cursos','apagarUsuario'))
   AND d.`adms_niveis_acesso_id` NOT IN (SELECT id FROM (SELECT `id` FROM `adms_niveis_acessos` WHERE `papel` IN ('administrador', 'direcao')) q);

-- as páginas sensíveis: só quem tem as ações sensíveis
UPDATE `adms_nivacs_pgs` d JOIN `adms_paginas` p ON p.`id` = d.`adms_pagina_id`
   SET d.`permissao` = 2, d.`lib_menu` = 2
 WHERE p.`sensivel` = 1
   AND d.`adms_niveis_acesso_id` NOT IN (SELECT id FROM (SELECT `id` FROM `adms_niveis_acessos` WHERE `acoes_sensiveis` = 1) q);

-- as posições da coordenação (a tabela nascia vazia na base, e o modal "Adicionar coordenador" do curso pede uma);
-- a 1 é a que a lista de cursos mostra como coordenador
INSERT INTO `rm_cursos_posicao` (`id`, `nome`, `ordem`, `situacao`, `usuario_cre`, `created`)
SELECT 1, 'Coordenador(a) do curso', 1, 1, 0, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `rm_cursos_posicao` WHERE `id` = 1);
INSERT INTO `rm_cursos_posicao` (`id`, `nome`, `ordem`, `situacao`, `usuario_cre`, `created`)
SELECT 2, 'Coordenador(a) adjunto(a)', 2, 1, 0, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `rm_cursos_posicao` WHERE `id` = 2);

-- 5. a versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.20.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 36, '1.20.0', 1, 20, 0, '2026-11-14', 'Perfis de acesso do produto aberto: Administrador, Direção acadêmica, Coordenação de curso, Supervisão de secretaria, Secretaria e Professor; coordenação por curso; consulta sem gravação para a Direção e a Coordenação; ações sensíveis só para a Supervisão e o Administrador.', 1, '2026-11-14 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.20.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.20.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 116, 36, 'Os perfis de acesso', 'Os perfis passam a ser: Administrador (tudo), Direção acadêmica (consulta e relatórios de todos os cursos), Coordenação de curso (consulta dos cursos que coordena), Supervisão de secretaria (a secretaria e as ações sensíveis), Secretaria e Professor. Os perfis antigos do fornecedor saíram; quem estava neles passou a Administrador.', 1, 'administrador', 'perfis-de-acesso', '2026-11-14 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 116);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 117, 36, 'Coordenação por curso', 'Na tela do curso, o Administrador ou a Direção acadêmica indica quem coordena. A pessoa com o perfil Coordenação de curso vê as disciplinas, as notas, as faltas, os boletins e os relatórios só desses cursos, sem alterar nada.', 2, 'administrador', 'perfis-de-acesso', '2026-11-14 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 117);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 118, 36, 'Ações sensíveis', 'Reabrir uma disciplina encerrada, excluir um encontro da chamada e apagar um aluno passam a ser exclusivos da Supervisão de secretaria e do Administrador.', 3, 'administrador,secretaria,professor', 'perfis-de-acesso', '2026-11-14 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 118);
