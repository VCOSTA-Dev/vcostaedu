-- Onda 8, Etapa 2 (D-68): o e-mail do aluno no Moodle, em três modos (Conectar ao Moodle):
--   escola          — sempre o e-mail da escola: a matrícula no domínio que a instituição informou (o padrão na escola);
--   aluno_ou_escola — o e-mail do aluno; sem ele (ou repetido em outro aluno), o da escola;
--   aluno           — só o e-mail do aluno; sem ele (ou repetido), o aluno fica fora, com o motivo (o padrão no superior).
-- O endereço da escola é montado na hora do envio: nunca é gravado no cadastro e não recebe mensagens.
--   - o vínculo do aluno guarda se a conta do Moodle foi CRIADA pelo sistema: só nessa o e-mail é atualizado (a conta
--     que já existia lá e foi só ligada fica com o e-mail que tinha);
--   - a instalação que já usava a opção "E-mail para aluno sem e-mail" (Onda 6) fica no modo equivalente
--     (aluno_ou_escola); a que já tinha o Moodle conectado sem ela, no modo "aluno" (como sempre foi); a instalação nova
--     fica sem escolha gravada ('') e segue o padrão do tipo. Nada é apagado: a opção antiga fica, sem uso.

ALTER TABLE `adms_moodle_vinculos`
  ADD COLUMN IF NOT EXISTS `criado` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = a conta do Moodle foi criada pelo VCOSTAEDU (o e-mail dela acompanha o modo); 0 = já existia e foi ligada (o e-mail não muda) (1.58.0)' AFTER `usuario`,
  ADD COLUMN IF NOT EXISTS `email_proprio` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = a conta criada foi com o e-mail do cadastro do aluno; 0 = com o da escola (ou não se sabe). Só a marca: o endereço não é guardado (1.58.0)' AFTER `criado`;

-- as contas de aluno ligadas antes desta versão: criada pelo sistema quando o nome de usuário é o que o sistema dá
-- (o prefixo e a matrícula); as outras, ligadas, ficam como "já existia"
UPDATE `adms_moodle_vinculos` v
  JOIN `col_usuarios` u ON u.`id` = v.`local_id`
   SET v.`criado` = 1
 WHERE v.`tipo` = 'aluno' AND v.`criado` = 0
   AND v.`usuario` = LOWER(CONCAT(IFNULL((SELECT p.`valor` FROM `adms_parametros` p WHERE p.`chave` = 'moodle.prefixo'), ''), u.`rm`));
-- até a 1.57.0, quem tinha e-mail no cadastro ia com ele
UPDATE `adms_moodle_vinculos` v
  JOIN `col_usuarios` u ON u.`id` = v.`local_id`
   SET v.`email_proprio` = 1
 WHERE v.`tipo` = 'aluno' AND v.`criado` = 1 AND u.`email` IS NOT NULL AND u.`email` <> '';

INSERT INTO `adms_parametros` (`chave`, `valor`, `descricao`, `grupo`)
SELECT 'moodle.email_modo',
       CASE WHEN (SELECT `valor` FROM `adms_parametros` WHERE `chave` = 'moodle.email_montado') = '1' THEN 'aluno_ou_escola'
            WHEN EXISTS (SELECT 1 FROM `adms_moodle_conexao` WHERE `id` = 1 AND `url` IS NOT NULL AND `url` <> '') THEN 'aluno'
            ELSE '' END,
       'Moodle: o e-mail do aluno no Moodle. escola = sempre o da escola (a matrícula no domínio); aluno_ou_escola = o do aluno, ou o da escola; aluno = só o do aluno. Vazio = o padrão do tipo (escola na escola, aluno no ensino superior).', 'Moodle'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_parametros` WHERE `chave` = 'moodle.email_modo');
UPDATE `adms_parametros` SET `descricao` = 'Moodle: o domínio do e-mail da escola, montado com a matrícula (por exemplo, alunos.suaescola.edu.br). Não recebe mensagens.'
 WHERE `chave` = 'moodle.email_dominio';
UPDATE `adms_parametros` SET `descricao` = 'Moodle (até a 1.57.0): montar um e-mail para o aluno sem e-mail. Desde a 1.58.0, substituído por moodle.email_modo; fica sem uso.'
 WHERE `chave` = 'moodle.email_montado';

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.58.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 75, '1.58.0', 1, 58, 0, '2026-11-20', 'O e-mail do aluno no Moodle em três modos: o da escola, o do aluno ou o da escola, só o do aluno.', 1, '2026-11-20 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.58.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.58.0';

-- a novidade
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 178, 75, 'O e-mail do aluno no Moodle', 'Em Conectar ao Moodle, o e-mail com que o aluno vai ao Moodle: sempre o da escola (a matrícula no domínio da instituição, o padrão na escola), o do aluno ou o da escola, ou só o do aluno. O endereço da escola não é gravado no cadastro e não recebe mensagens: o aluno entra no Moodle pelo portal. A conta que já existia no Moodle não tem o e-mail trocado.', 1, 'administrador', 'conectar-ao-moodle', '2026-11-20 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 178);
