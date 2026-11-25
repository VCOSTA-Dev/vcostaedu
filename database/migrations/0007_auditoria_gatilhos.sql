-- vcostaedu:exige-privilegio TRIGGER
-- =============================================================================
-- 0007 — A AUDITORIA DE NOTAS E FALTAS NÃO MUDA E NÃO É APAGADA (Onda 2, Etapa 3).
-- O sistema não tem tela nem rota que altere ou apague a auditoria; estes dois gatilhos o garantem também no
-- banco. Como a 0003, a primeira linha diz ao runner que exige o privilégio TRIGGER: sem ele, esta migration fica
-- ADIADA e a auditoria segue protegida só pelo código.
-- =============================================================================
DROP TRIGGER IF EXISTS `rm_auditoria_lancamentos_imutavel`;
DROP TRIGGER IF EXISTS `rm_auditoria_lancamentos_sem_apagar`;
CREATE TRIGGER `rm_auditoria_lancamentos_imutavel` BEFORE UPDATE ON `rm_auditoria_lancamentos` FOR EACH ROW
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A auditoria de notas e faltas não pode ser alterada.';
-- a única exceção: a limpeza da demonstração apaga a auditoria dos dados fictícios (ambiente 'demonstracao')
CREATE TRIGGER `rm_auditoria_lancamentos_sem_apagar` BEFORE DELETE ON `rm_auditoria_lancamentos` FOR EACH ROW
BEGIN
  IF NOT (OLD.ambiente <=> 'demonstracao') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A auditoria de notas e faltas não pode ser apagada.';
  END IF;
END;
