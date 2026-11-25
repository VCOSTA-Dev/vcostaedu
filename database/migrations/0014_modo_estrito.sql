-- Onda 3, Etapa 2 — o modo estrito do banco.
--
-- O sistema passa a fixar o modo de SQL estrito em toda conexão (core/Banco.php). Medido nas migrations
-- (tests/tabler/onda3/medir_estrito.php): só duas gravavam um texto maior que a coluna — o resumo das versões 1.11.0 e
-- 1.20.0, maior que os 255 caracteres de adms_versoes.resumo, cortado em silêncio no banco sem o modo estrito. Elas
-- continuam sendo aplicadas como sempre (o runner as aplica sem STRICT_TRANS_TABLES, só elas); esta alarga a coluna e
-- devolve os dois textos inteiros.

ALTER TABLE `adms_versoes` MODIFY `resumo` text DEFAULT NULL COMMENT 'uma frase sobre a entrega';

UPDATE `adms_versoes` SET `resumo` = 'Matrícula, matriz e frequência: ordem das disciplinas na grade, previsão de conclusão, rótulo da situação nos documentos, sexo obrigatório com pendência, concordância, CPF mascarado, frequência mínima por curso e FS/FI; salvamento do modelo de histórico em transação.' WHERE `numero` = '1.11.0';
UPDATE `adms_versoes` SET `resumo` = 'Perfis de acesso do produto aberto: Administrador, Direção acadêmica, Coordenação de curso, Supervisão de secretaria, Secretaria e Professor; coordenação por curso; consulta sem gravação para a Direção e a Coordenação; ações sensíveis só para a Supervisão e o Administrador.' WHERE `numero` = '1.20.0';

-- O usuário legado do aluno (espelha o RA): o perfil do portal gravava NULL nele (o formulário não o manda), e o banco sem
-- o modo estrito o trocava por vazio. Volta a ser o RA, como a tela do aluno grava.
UPDATE `col_usuarios` SET `usuario` = `rm` WHERE `col_niveis_acesso_id` = 1 AND (`usuario` IS NULL OR `usuario` = '') AND `rm` IS NOT NULL AND `rm` <> '';

-- A versão e a novidade
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.28.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 44, '1.28.0', 1, 28, 0, '2026-11-15', 'O banco no modo estrito: valor que não cabe, data inválida ou campo obrigatório vazio são recusados com mensagem clara, em vez de gravados pela metade; o boletim e as disciplinas do professor seguem o escopo dele.', 1, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.28.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.28.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 132, 44, 'Dados conferidos pelo banco', 'O banco de dados passa a recusar o que antes gravava pela metade (um texto maior que o campo, uma data impossível, um campo obrigatório vazio), em qualquer servidor. A tela diz o que corrigir.', 1, 'administrador', NULL, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 132);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 133, 44, 'O professor que coordena um curso', 'Em Minhas disciplinas, o professor que coordena um curso vê também as disciplinas do curso, marcadas "só consulta"; o boletim abre para os alunos das disciplinas dele e dos cursos que coordena.', 2, 'administrador,professor', 'minhas-disciplinas-e-atividades', '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 133);
