-- vcostaedu:exige-privilegio TRIGGER
-- =============================================================================
-- 0003 — GATILHOS DE IMUTABILIDADE (6): o histórico emitido e suas vias (D-201 da
-- origem) e os resultados de fechamento substituídos (D-397 da origem).
-- A primeira linha deste arquivo diz ao runner que ele exige o privilégio TRIGGER:
-- sem o privilégio, a migration fica ADIADA (não é registrada), o runner segue com
-- as próximas e avisa; o sistema funciona, com o histórico protegido só pelo código.
-- Conferência: php database/ferramentas/conferir_gatilhos.php
-- =============================================================================
DROP TRIGGER IF EXISTS `rm_historico_emissoes_imutavel`;
DROP TRIGGER IF EXISTS `rm_historico_emissoes_sem_apagar`;
DROP TRIGGER IF EXISTS `rm_historico_emissao_vias_imutavel`;
DROP TRIGGER IF EXISTS `rm_historico_emissao_vias_sem_apagar`;
DROP TRIGGER IF EXISTS `rm_media_fechamento_resultados_imutavel`;
DROP TRIGGER IF EXISTS `rm_media_fechamento_resultados_sem_apagar`;
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
END;
CREATE TRIGGER `rm_historico_emissoes_sem_apagar` BEFORE DELETE ON `rm_historico_emissoes` FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Emissão de histórico não pode ser apagada: cancele-a.';
END;
CREATE TRIGGER `rm_historico_emissao_vias_imutavel` BEFORE UPDATE ON `rm_historico_emissao_vias` FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Via de histórico é imutável.';
END;
CREATE TRIGGER `rm_historico_emissao_vias_sem_apagar` BEFORE DELETE ON `rm_historico_emissao_vias` FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Via de histórico não pode ser apagada.';
END;
-- 1.13.0 (D-397): resultado de fechamento substituído (guardado na reabertura) não muda nem é apagado
CREATE TRIGGER `rm_media_fechamento_resultados_imutavel` BEFORE UPDATE ON `rm_media_fechamento_resultados` FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Resultado de fechamento substituído é imutável (1.13.0, D-397).';
END;
CREATE TRIGGER `rm_media_fechamento_resultados_sem_apagar` BEFORE DELETE ON `rm_media_fechamento_resultados` FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Resultado de fechamento substituído não pode ser apagado (1.13.0, D-397).';
END;
