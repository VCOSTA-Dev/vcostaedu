-- =============================================================================
-- VCOSTAEDU — demonstração FICTÍCIA consolidada (E5; E6a e E6b acrescentaram)
--
-- Desde a E5 a demonstração inteira é montada PELAS TELAS (D-18), via HTTP, por
-- database/ferramentas/seed_demo_processar.php: polo, curso, usuários da equipe,
-- ano letivo, matérias, matriz (a grade com 6 disciplinas, sem módulo), oferta 2026, alunos,
-- vínculo, enturmação, atividades, notas, faltas e encerramento.
--
-- Este script só APAGA a demonstração anterior (a da E5 e a antiga, da E1 à E4),
-- para que o processador possa rodar de novo. Idempotente e restrito às próprias
-- linhas (nomes e RAs fixos, abaixo). Tudo inventado; nenhum dado de pessoa real.
-- NÃO rodar em produção. Rode SEMPRE por:
--   php database/ferramentas/limpar_demo.php
-- (E7, D-220: a trava abaixo recusa rodar direto, fora de AMBIENTE = 'demonstracao' ou com emissão real.)
-- =============================================================================
-- 1.18.0 (D-471): a trava do nome do banco aceita também o banco de DEMONSTRAÇÃO declarado na Config
-- (BANCO_DEMONSTRACAO = DBNAME, com AMBIENTE = 'demonstracao'), que o limpar_demo.php passa em @vcostaedu_banco_demonstracao.
-- Sem a declaração, só o nome oficial passa, como antes. As travas de ambiente, de emissão real e da instituição
-- configurada continuam valendo.
SET @vcostaedu_banco_ok := (IFNULL(DATABASE(),'') = 'vcostaedu' OR (IFNULL(@vcostaedu_banco_demonstracao,'') <> '' AND IFNULL(DATABASE(),'') = @vcostaedu_banco_demonstracao));
SET @vcostaedu_guarda := (SELECT 1 FROM DUAL WHERE NOT @vcostaedu_banco_ok UNION ALL SELECT 2 FROM DUAL WHERE NOT @vcostaedu_banco_ok);

SET NAMES utf8mb4;

-- E7 (D-220): TRAVA DE AMBIENTE. Este script tira os gatilhos do histórico e apaga emissões: só roda
-- chamado por database/ferramentas/limpar_demo.php, que confere AMBIENTE = 'demonstracao' na Config e
-- passa @vcostaedu_limpeza_demo. Rodado direto (mysql < seed_demo.sql), ou havendo emissão que não é da
-- demonstração, PARA aqui com "Subquery returns more than 1 row", sem mudar nada.
SELECT 'Trava da demonstração: se aparecer "Subquery returns more than 1 row" logo abaixo, a limpeza foi RECUSADA (use database/ferramentas/limpar_demo.php, com AMBIENTE = demonstracao; e nenhuma emissão fora da demonstração).' AS aviso;
SET @vcostaedu_trava_ambiente := (SELECT 1 FROM DUAL WHERE IFNULL(@vcostaedu_limpeza_demo, '') <> 'demonstracao' UNION ALL SELECT 2 FROM DUAL WHERE IFNULL(@vcostaedu_limpeza_demo, '') <> 'demonstracao');
SET @vcostaedu_trava_emissoes := (SELECT 1 FROM DUAL WHERE EXISTS (SELECT 1 FROM rm_historico_emissoes WHERE ambiente <> 'demonstracao') UNION ALL SELECT 2 FROM DUAL WHERE EXISTS (SELECT 1 FROM rm_historico_emissoes WHERE ambiente <> 'demonstracao'));

-- Identidade da demonstração
CREATE TEMPORARY TABLE demo_cursos (id INT PRIMARY KEY);
INSERT INTO demo_cursos SELECT id FROM rm_cursos
 WHERE nome IN ('Pós-graduação em Gestão de Projetos', 'Pós-graduação em Docência no Ensino Superior', 'Graduação Fictícia de Demonstração',
   'Especialização em Gestão Pública Exemplar', 'Especialização em Dados Públicos Exemplar', -- os dois: os nomes de antes da 1.31.0
   'Ensino Fundamental — Anos Finais (demonstração)', 'Ensino Médio (demonstração)', -- Onda 4, Etapa 4: o colégio fictício
   'Curso livre: IA na prática para professores (demonstração)', -- Onda 8 (D-70): o curso livre do colégio
   'IA na prática para professores (demonstração)'); -- Fechamento (D-106): o curso da demonstração do curso livre
-- a demonstração antiga (E1 a E4) usava o curso id 1 com o aluno RA 900001
INSERT IGNORE INTO demo_cursos SELECT c.id FROM rm_cursos c
 WHERE c.id = 1 AND EXISTS (SELECT 1 FROM col_usuarios u WHERE u.rm = 900001);

CREATE TEMPORARY TABLE demo_ofertas (id INT PRIMARY KEY);
INSERT INTO demo_ofertas SELECT o.id FROM rm_professores_materias o JOIN demo_cursos c ON c.id = o.curso_id;
-- Onda 8, correção (D-71): o horário da semana das ofertas da demonstração
DELETE FROM rm_professores_horarios WHERE rm_prof_mat_id IN (SELECT id FROM demo_ofertas);

-- VCOSTAEDU: a collation das tabelas herdadas (general_ci) declarada aqui; sem ela a tabela temporária herdaria a
-- do banco (o instalador pede utf8mb4_unicode_ci) e a comparação com rm daria "Illegal mix of collations".
CREATE TEMPORARY TABLE demo_alunos (id INT PRIMARY KEY, rm VARCHAR(20)) DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
INSERT INTO demo_alunos SELECT id, CAST(rm AS CHAR) FROM col_usuarios
 WHERE rm IN (260101, 260102, 260103, 260104, 260105, 260106, 260107, 260108, 250101, 250102, 900001)
    OR rm BETWEEN 255001 AND 255099 OR rm BETWEEN 265001 AND 265099 -- Onda 4, Etapa 4: os alunos do colégio fictício
    OR rm BETWEEN 266001 AND 266099; -- Fechamento (D-106): os alunos do curso livre fictício

CREATE TEMPORARY TABLE demo_grades (id INT PRIMARY KEY);
-- Correção da escola, Parte 4 (B11; D-103): o financeiro da demonstração sai com ela — os recibos (as linhas e as formas),
-- o histórico das operações, os títulos e as renegociações dos alunos fictícios; depois, os planos de pagamento que
-- nenhum título usa (também o criado à mão para testar) e o número do recibo volta ao último que sobrou
SET @fin := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fin_titulos');
SET @finr := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fin_recibos');
SET @q := IF(@finr = 1, 'DELETE f FROM fin_recibo_formas f INNER JOIN fin_recibos r ON r.id = f.recibo_id WHERE r.aluno_id IN (SELECT id FROM demo_alunos)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@finr = 1, 'DELETE t FROM fin_recibo_titulos t INNER JOIN fin_recibos r ON r.id = t.recibo_id WHERE r.aluno_id IN (SELECT id FROM demo_alunos)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@finr = 1, 'DELETE FROM fin_recibos WHERE aluno_id IN (SELECT id FROM demo_alunos)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@fin = 1, 'DELETE FROM fin_operacoes WHERE aluno_id IN (SELECT id FROM demo_alunos)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@fin = 1, 'DELETE FROM fin_titulos WHERE aluno_id IN (SELECT id FROM demo_alunos)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@fin = 1, 'DELETE FROM fin_renegociacoes WHERE aluno_id IN (SELECT id FROM demo_alunos)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
-- D-105: as propostas de acordo da demonstração (e as linhas delas) e o número do acordo
SET @finp := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fin_propostas');
SET @q := IF(@finp = 1, 'DELETE l FROM fin_proposta_titulos l INNER JOIN fin_propostas p ON p.id = l.proposta_id WHERE p.aluno_id IN (SELECT id FROM demo_alunos)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@finp = 1, 'DELETE l FROM fin_proposta_parcelas l INNER JOIN fin_propostas p ON p.id = l.proposta_id WHERE p.aluno_id IN (SELECT id FROM demo_alunos)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@finp = 1, 'DELETE FROM fin_propostas WHERE aluno_id IN (SELECT id FROM demo_alunos)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@finp = 1, 'UPDATE fin_sequencias SET ultimo = (SELECT IFNULL(MAX(numero), 0) FROM fin_renegociacoes) WHERE nome = ''acordo''', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@fin = 1, 'DELETE p FROM fin_planos p WHERE NOT EXISTS (SELECT 1 FROM fin_titulos t WHERE t.plano_id = p.id)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@finr = 1, 'UPDATE fin_sequencias SET ultimo = (SELECT IFNULL(MAX(numero), 0) FROM fin_recibos) WHERE nome = ''recibo''', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
INSERT INTO demo_grades SELECT g.id FROM rm_cursos_grades g JOIN demo_cursos c ON c.id = g.curso_id;

-- Onda 4, Etapa 4: o que a escola guarda das turmas do colégio fictício (as tabelas existem da 1.39.0 e da 1.40.0 em diante)
SET @esc := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'esc_turmas');
SET @esc3 := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'esc_fechamentos');
SET @q := IF(@esc3 = 1, 'DELETE n FROM esc_fechamento_notas n INNER JOIN esc_fechamentos f ON f.id = n.fechamento_id WHERE f.oferta_id IN (SELECT id FROM demo_ofertas)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@esc3 = 1, 'DELETE FROM esc_fechamentos WHERE oferta_id IN (SELECT id FROM demo_ofertas)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@esc3 = 1, 'DELETE FROM esc_recuperacoes WHERE oferta_id IN (SELECT id FROM demo_ofertas)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@esc3 = 1, 'DELETE FROM esc_faltas_etapa WHERE oferta_id IN (SELECT id FROM demo_ofertas)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
-- D-104: as aulas da chamada (com as presenças) e as notas de transferência da demonstração saem com ela
DELETE p FROM rm_oferta_presencas p INNER JOIN rm_oferta_encontros e ON e.id = p.encontro_id WHERE e.rm_prof_mat_id IN (SELECT id FROM demo_ofertas);
DELETE FROM rm_oferta_encontros WHERE rm_prof_mat_id IN (SELECT id FROM demo_ofertas);
SET @ent := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'esc_notas_transferencia');
SET @q := IF(@ent = 1, 'DELETE h FROM esc_notas_transferencia_hist h INNER JOIN esc_notas_transferencia n ON n.id = h.transferencia_id WHERE n.oferta_id IN (SELECT id FROM demo_ofertas)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@ent = 1, 'DELETE FROM esc_notas_transferencia WHERE oferta_id IN (SELECT id FROM demo_ofertas)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
-- D-100 (0081): o histórico do conselho da demonstração sai com ele
SET @esc81 := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'esc_boletim_liberacao');
SET @q := IF(@esc81 = 1, 'DELETE h FROM esc_conselho_hist h INNER JOIN esc_conselho c ON c.id = h.conselho_id WHERE c.turma_id IN (SELECT id FROM esc_turmas WHERE curso_id IN (SELECT id FROM demo_cursos))', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@esc3 = 1, 'DELETE FROM esc_conselho WHERE turma_id IN (SELECT id FROM esc_turmas WHERE curso_id IN (SELECT id FROM demo_cursos))', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@esc = 1, 'DELETE FROM esc_lancamentos_trazidos WHERE oferta_origem_id IN (SELECT id FROM demo_ofertas) OR oferta_destino_id IN (SELECT id FROM demo_ofertas)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@esc = 1, 'DELETE FROM esc_turma_alunos WHERE turma_id IN (SELECT id FROM esc_turmas WHERE curso_id IN (SELECT id FROM demo_cursos))', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@esc = 1, 'DELETE FROM esc_turma_ofertas WHERE oferta_id IN (SELECT id FROM demo_ofertas)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@esc = 1, 'DELETE FROM esc_turmas WHERE curso_id IN (SELECT id FROM demo_cursos)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;

-- Resultados, notas, faltas, atividades e histórico de encerramento
DELETE FROM rm_disciplina_media_final     WHERE rm IN (SELECT rm FROM demo_alunos) OR rm_prof_mat_id IN (SELECT id FROM demo_ofertas);
DELETE FROM rm_disciplina_atividade_notas WHERE rm IN (SELECT rm FROM demo_alunos) OR rm_prof_mat_id IN (SELECT id FROM demo_ofertas);
DELETE FROM rm_disciplina_faltas          WHERE rm IN (SELECT rm FROM demo_alunos) OR rm_prof_mat_id IN (SELECT id FROM demo_ofertas);
-- Onda 2, Etapa 3: a auditoria dos dados fictícios (só a do ambiente 'demonstracao'; o gatilho da 0007 recusa apagar
-- qualquer outra). Por comando preparado: num banco anterior à 0006 a tabela ainda não existe.
SET @aud := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rm_auditoria_lancamentos');
SET @aud_sql := IF(@aud = 1, 'DELETE FROM rm_auditoria_lancamentos WHERE ambiente = ''demonstracao'' AND (oferta_id IN (SELECT id FROM demo_ofertas) OR rm IN (SELECT rm FROM demo_alunos))', 'DO 0');
PREPARE aud_limpa FROM @aud_sql;
EXECUTE aud_limpa;
DEALLOCATE PREPARE aud_limpa;
DELETE FROM rm_disciplina_atividade       WHERE rm_prof_mat_id IN (SELECT id FROM demo_ofertas);
DELETE FROM rm_professores_materias_encerramentos WHERE rm_prof_mat_id IN (SELECT id FROM demo_ofertas);
DELETE FROM portal_disciplinas_alunos     WHERE rm IN (SELECT rm FROM demo_alunos) OR rm_prof_mat_id IN (SELECT id FROM demo_ofertas);
DELETE FROM rm_professores_mat_liga       WHERE rm_prof_mat_id IN (SELECT id FROM demo_ofertas);

-- E6b: emissões do histórico dos alunos da demonstração (os PDFs órfãos o processador apaga)
-- e os 2 modelos padrão de volta à versão 1, se a demonstração os personalizou.
-- E6c (D-201): as emissões são imutáveis por gatilho; SÓ esta limpeza da DEMONSTRAÇÃO tira os
-- gatilhos, apaga as emissões dos alunos fictícios e os recria IGUAIS aos do schema.sql (o roteiro
-- de provas confere). Exige o privilégio TRIGGER, como a instalação.
DROP TRIGGER IF EXISTS `rm_historico_emissoes_imutavel`;
DROP TRIGGER IF EXISTS `rm_historico_emissoes_sem_apagar`;
DROP TRIGGER IF EXISTS `rm_historico_emissao_vias_imutavel`;
DROP TRIGGER IF EXISTS `rm_historico_emissao_vias_sem_apagar`;
DELETE FROM rm_historico_emissao_vias WHERE emissao_id IN (SELECT id FROM rm_historico_emissoes WHERE aluno_id IN (SELECT id FROM demo_alunos));
DELETE FROM rm_historico_emissoes WHERE aluno_id IN (SELECT id FROM demo_alunos);
-- gatilhos de volta, idênticos aos do schema.sql
DELIMITER ;;
CREATE TRIGGER `rm_historico_emissoes_imutavel` BEFORE UPDATE ON `rm_historico_emissoes` FOR EACH ROW
BEGIN
  -- comparação BINÁRIA nos textos: com a colação _ci (PAD SPACE), "abc" = "ABC " e a troca passaria
  IF NOT (NEW.id <=> OLD.id AND BINARY NEW.codigo <=> BINARY OLD.codigo AND BINARY NEW.tipo <=> BINARY OLD.tipo
      AND NEW.matricula_curso_id <=> OLD.matricula_curso_id AND NEW.aluno_id <=> OLD.aluno_id
      AND NEW.modelo_id <=> OLD.modelo_id AND NEW.modelo_versao_id <=> OLD.modelo_versao_id
      AND BINARY NEW.snapshot <=> BINARY OLD.snapshot AND BINARY NEW.html <=> BINARY OLD.html AND BINARY NEW.hash_conteudo <=> BINARY OLD.hash_conteudo
      AND BINARY NEW.pdf_arquivo <=> BINARY OLD.pdf_arquivo AND BINARY NEW.hash_pdf <=> BINARY OLD.hash_pdf AND BINARY NEW.alertas_aceitos <=> BINARY OLD.alertas_aceitos AND BINARY NEW.ambiente <=> BINARY OLD.ambiente
      AND NEW.emitido_por <=> OLD.emitido_por AND NEW.emitido_em <=> OLD.emitido_em) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Emissão de histórico é imutável: só a situação e os dados do cancelamento podem mudar.';
  END IF;
  IF OLD.situacao = 2 AND NOT (NEW.situacao <=> OLD.situacao AND NEW.cancelado_por <=> OLD.cancelado_por
      AND NEW.cancelado_em <=> OLD.cancelado_em AND BINARY NEW.cancelamento_motivo <=> BINARY OLD.cancelamento_motivo) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Emissão de histórico cancelada não volta atrás nem muda o cancelamento.';
  END IF;
END;;
CREATE TRIGGER `rm_historico_emissoes_sem_apagar` BEFORE DELETE ON `rm_historico_emissoes` FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Emissão de histórico não pode ser apagada: cancele-a.';
END;;
CREATE TRIGGER `rm_historico_emissao_vias_imutavel` BEFORE UPDATE ON `rm_historico_emissao_vias` FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Via de histórico é imutável.';
END;;
CREATE TRIGGER `rm_historico_emissao_vias_sem_apagar` BEFORE DELETE ON `rm_historico_emissao_vias` FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Via de histórico não pode ser apagada.';
END;;
DELIMITER ;
DELETE FROM rm_historico_modelo_cursos WHERE curso_id IN (SELECT id FROM demo_cursos);
DELETE v FROM rm_historico_modelo_versoes v
 INNER JOIN rm_historico_modelos m ON m.id = v.modelo_id
 WHERE m.id IN (1, 2) AND (m.nome LIKE '%Escola de Governo Exemplar%' OR m.nome LIKE '%Faculdade Horizonte%') AND v.versao > 1
   AND NOT EXISTS (SELECT 1 FROM rm_historico_emissoes e WHERE e.modelo_versao_id = v.id);
UPDATE rm_historico_modelos m
   SET m.versao_atual = (SELECT MAX(v.versao) FROM rm_historico_modelo_versoes v WHERE v.modelo_id = m.id),
       m.nome = IF(m.id = 1, 'Histórico parcial — padrão', 'Histórico final — padrão'), m.situacao = 2, m.usuario_mod = NULL, m.modified = NULL
 WHERE m.id IN (1, 2) AND (m.nome LIKE '%Escola de Governo Exemplar%' OR m.nome LIKE '%Faculdade Horizonte%');

-- Onda 7, Etapa 6: os responsáveis dos alunos fictícios (as tabelas existem da 1.52.0 em diante). Saem os papéis, os
-- vínculos, os contatos e o registro desses alunos; e as pessoas que só eram responsáveis de alunos fictícios, com os
-- convites e o registro delas. Quem também é responsável de um aluno real fica (só perde o vínculo com o fictício).
SET @er := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'esc_responsavel_designacoes');
SET @ec := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'esc_responsavel_convites');
DROP TEMPORARY TABLE IF EXISTS demo_responsaveis;
CREATE TEMPORARY TABLE demo_responsaveis (id INT PRIMARY KEY);
SET @q := IF(@er = 1, 'INSERT INTO demo_responsaveis SELECT DISTINCT x.responsavel_id FROM (SELECT responsavel_id, aluno_id FROM esc_responsavel_alunos UNION SELECT responsavel_id, aluno_id FROM esc_responsavel_designacoes) x GROUP BY x.responsavel_id HAVING SUM(x.aluno_id NOT IN (SELECT id FROM demo_alunos)) = 0', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@er = 1, 'DELETE FROM esc_responsavel_designacoes WHERE aluno_id IN (SELECT id FROM demo_alunos)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@er = 1, 'DELETE FROM esc_responsavel_alunos WHERE aluno_id IN (SELECT id FROM demo_alunos)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@er = 1, 'DELETE FROM esc_responsavel_contatos WHERE aluno_id IN (SELECT id FROM demo_alunos)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@er = 1, 'DELETE FROM esc_responsavel_auditoria WHERE aluno_id IN (SELECT id FROM demo_alunos)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@er = 1, 'DELETE FROM esc_responsavel_auditoria WHERE responsavel_id IN (SELECT id FROM demo_responsaveis)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@ec = 1, 'DELETE FROM esc_responsavel_convites WHERE responsavel_id IN (SELECT id FROM demo_responsaveis)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@er = 1, 'DELETE FROM esc_responsaveis WHERE id IN (SELECT id FROM demo_responsaveis)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
DROP TEMPORARY TABLE IF EXISTS demo_responsaveis;

-- Alunos (E6a: e o TCC das matrículas)
DELETE FROM rm_tcc WHERE matricula_curso_id IN (SELECT id FROM rm_matriculas_curso WHERE usuario_id IN (SELECT id FROM demo_alunos));
DELETE FROM rm_matriculas_ensino          WHERE usuario_id IN (SELECT id FROM demo_alunos);
DELETE FROM rm_matriculas_curso           WHERE usuario_id IN (SELECT id FROM demo_alunos);
DELETE FROM col_usuarios_tokens           WHERE col_usuario_id IN (SELECT id FROM demo_alunos);
DELETE FROM col_usuarios_informacoes      WHERE col_user_id IN (SELECT id FROM demo_alunos);
DELETE FROM col_usuarios_enderecos        WHERE usuario_id IN (SELECT id FROM demo_alunos);
DELETE FROM col_usuarios                  WHERE id IN (SELECT id FROM demo_alunos);

-- Oferta, matriz, curso, polo e matérias
DELETE FROM rm_professores_materias        WHERE id IN (SELECT id FROM demo_ofertas);
DELETE FROM uni_rh_cursos_disciplinas_grade WHERE grade_id IN (SELECT id FROM demo_grades);
DELETE FROM uni_rh_cursos_gradeamento_obs WHERE grade_id IN (SELECT id FROM demo_grades);
DELETE FROM rm_cursos_turma_series        WHERE grade_id IN (SELECT id FROM demo_grades) OR curso_id IN (SELECT id FROM demo_cursos);
DELETE FROM rm_cursos_grades              WHERE id IN (SELECT id FROM demo_grades);
DELETE FROM rm_cursos_coordenacao         WHERE curso_id IN (SELECT id FROM demo_cursos);
DELETE FROM rm_cursos                     WHERE id IN (SELECT id FROM demo_cursos);
DELETE FROM rm_polos                      WHERE nome IN ('Polo Central Fictício', 'Polo Sede Fictício');
DELETE FROM rm_materias WHERE nome IN (
  'Fundamentos da Administração Pública', 'Metodologia da Pesquisa Aplicada',
  'Gestão de Projetos no Setor Público', 'Orçamento e Finanças Públicas',
  'Governança e Controle', 'Seminário de Trabalho Final',
  'Estatística Aplicada ao Setor Público', 'Governança de Dados', 'Transparência e Dados Abertos',
  'Fundamentos de Exemplologia', 'Introdução à Simulação de Dados', 'Laboratório de Casos Inventados',
  -- os nomes da 1.31.0 em diante
  'Fundamentos de Gestão de Projetos', 'Métodos Ágeis', 'Gestão de Riscos', 'Planejamento e Cronograma', 'Liderança de Equipes',
  'Trabalho de Conclusão de Curso', 'Avaliação da Aprendizagem', 'Didática no Ensino Superior', 'Tecnologias Educacionais');

-- E6a: instituição fictícia e atos legais fictícios da demonstração (o logo órfão é apagado pelo processador)
DELETE FROM adms_empresas WHERE instituicao = 1 AND nome IN ('Faculdade Horizonte (fictícia)', 'Escola de Governo Exemplar (fictícia)', 'Colégio Horizonte (fictício)', 'Instituto Ventoalto (fictício)'); -- o novo, o de antes da 1.31.0, o colégio (Onda 4) e o curso livre (D-106)
DELETE FROM rm_atos_legais WHERE numero IN ('999/2024', '9999/2025') AND observacao LIKE '%demonstração%';

-- Onda 3, Etapa 5: o administrador volta ao nome dele (a demonstração lhe deu um nome fictício e guardou o original)
UPDATE adms_usuarios u JOIN adms_instalacao_estado e ON e.chave = 'demonstracao.admin_nome'
   SET u.nome = JSON_VALUE(e.valor, '$.nome') WHERE u.id = JSON_VALUE(e.valor, '$.id');
DELETE FROM adms_instalacao_estado WHERE chave = 'demonstracao.admin_nome';

-- Equipe fictícia
-- Onda 2, Etapa 2: um usuário de cada perfil (e o adm.escola.demo, que ficava para trás na limpeza)
DELETE FROM adms_usuarios WHERE usuario IN ('adm.escola.demo', 'direcao.demo', 'supervisao.demo', 'secretaria.demo', 'coord.demo', 'prof.laura.demo', 'prof.marcos.demo', 'professor.demo',
  -- Onda 8, correção (D-84): os professores de cada componente do colégio fictício
  'prof.renata.demo', 'prof.otavio.demo', 'prof.bianca.demo', 'prof.wagner.demo', 'prof.celia.demo', 'prof.gustavo.demo', 'prof.heloisa.demo', 'prof.ronaldo.demo',
  'prof.bruna.demo', 'prof.flavio.demo', 'prof.quiteria.demo', 'prof.teodoro.demo', 'prof.sofia.demo', 'prof.ivo.demo', 'prof.lara.demo');

DROP TEMPORARY TABLE demo_cursos, demo_ofertas, demo_alunos, demo_grades;

-- Onda 4, Etapa 4: os componentes do colégio fictício que nenhuma matriz usa mais, e os anos letivos da escola sem turma
DELETE m FROM rm_materias m WHERE m.nome IN ('Língua Portuguesa', 'Arte', 'Educação Física', 'Língua Inglesa', 'Matemática', 'Ciências', 'Geografia', 'História',
  'Ensino Religioso', 'Biologia', 'Física', 'Química', 'Filosofia', 'Sociologia', 'Itinerário: Projeto de Ciência e Tecnologia (exemplo)', 'Itinerário: Laboratório de Investigação (exemplo)')
  AND m.descricao IN ('Disciplina da demonstração', 'Componente curricular da demonstração') -- Onda 8 (D-85): o nome novo e o de antes
  AND NOT EXISTS (SELECT 1 FROM uni_rh_cursos_disciplinas_grade l WHERE l.materia_id = m.id);
-- Onda 8 (D-70): os quatro módulos do curso livre fictício que nenhuma matriz usa mais
DELETE m FROM rm_materias m WHERE m.nome IN ('Módulo 1 — Primeiros passos com IA na sala de aula', 'Módulo 2 — Planejar aulas e atividades',
  'Módulo 3 — Avaliar e dar retorno', 'Módulo 4 — Ética, privacidade e autoria')
  AND m.descricao = 'Módulo do curso livre da demonstração'
  AND NOT EXISTS (SELECT 1 FROM uni_rh_cursos_disciplinas_grade l WHERE l.materia_id = m.id);
SET @q := IF(@esc3 = 1, 'DELETE b FROM esc_boletim_liberado b INNER JOIN esc_etapas e ON e.id = b.etapa_id WHERE NOT EXISTS (SELECT 1 FROM esc_turmas t WHERE t.ano = e.ano)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
-- D-100 (0081): a liberação do boletim (e o histórico dela) do ano que ficou sem turma
SET @q := IF(@esc81 = 1, 'DELETE b FROM esc_boletim_liberacao b WHERE NOT EXISTS (SELECT 1 FROM esc_turmas t WHERE t.ano = b.ano)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@esc81 = 1, 'DELETE h FROM esc_boletim_liberacao_hist h WHERE NOT EXISTS (SELECT 1 FROM esc_turmas t WHERE t.ano = h.ano)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@esc = 1, 'DELETE e FROM esc_etapas e WHERE NOT EXISTS (SELECT 1 FROM esc_turmas t WHERE t.ano = e.ano)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
SET @q := IF(@esc = 1, 'DELETE a FROM esc_anos a WHERE NOT EXISTS (SELECT 1 FROM esc_turmas t WHERE t.ano = a.ano)', 'DO 0');
PREPARE q FROM @q; EXECUTE q; DEALLOCATE PREPARE q;
