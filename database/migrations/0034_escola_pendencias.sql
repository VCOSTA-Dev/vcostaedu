-- A escola: o que ficou para trás (Onda 6, Etapa 5; DECISIONS D-55 a D-57). A chamada por aula na escola (a falta da
-- etapa é a soma das chamadas dela; a escola escolhe a fonte em Regras da escola, guardada em esc_configuracao, onde
-- ficam só as regras diferentes do padrão); a turma e a etapa como filtro nos relatórios da escola; o e-mail montado para
-- o aluno sem e-mail no Moodle (desligado por padrão). Nenhuma tabela muda: os dois parâmetros do Moodle, a versão e as
-- novidades.

INSERT INTO `adms_parametros` (`chave`, `valor`, `descricao`, `grupo`)
SELECT 'moodle.email_montado', '0', 'Moodle: montar um e-mail (a matrícula no domínio da instituição) para o aluno que não tem. 0 = não (o padrão), 1 = sim.', 'Moodle'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_parametros` WHERE `chave` = 'moodle.email_montado');
INSERT INTO `adms_parametros` (`chave`, `valor`, `descricao`, `grupo`)
SELECT 'moodle.email_dominio', '', 'Moodle: o domínio do e-mail montado para o aluno sem e-mail (por exemplo, alunos.suaescola.edu.br).', 'Moodle'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_parametros` WHERE `chave` = 'moodle.email_dominio');

-- A versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.47.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 64, '1.47.0', 1, 47, 0, '2026-11-18', 'A escola: a chamada por aula, a turma e a etapa nos relatórios, o e-mail para o aluno sem e-mail no Moodle, qualquer série.', 1, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.47.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.47.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 162, 64, 'A chamada por aula na escola', 'Em Ensino → Regras da escola, "De onde vêm as faltas": as lançadas por etapa (o padrão) ou a chamada por aula, como no ensino superior. Com a chamada, o professor faz a chamada do dia no componente e a falta da etapa é a soma das chamadas daquela etapa; o total do ano segue a mesma regra.', 1, 'administrador,secretaria,professor', 'avaliar-na-escola', '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 162);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 163, 64, 'A turma e a etapa nos relatórios da escola', 'Na escola, os relatórios de notas, de frequência e de alunos têm o filtro da turma e, os de notas e faltas, o da etapa: com a etapa, a nota de cada aluno é a da etapa, com a recuperação.', 2, 'administrador,secretaria,professor', NULL, '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 163);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 164, 64, 'E-mail para aluno sem e-mail, no Moodle', 'Em Conectar ao Moodle, a opção "E-mail para aluno sem e-mail" (desligada por padrão): ligada, o aluno sem e-mail vai ao Moodle com um endereço feito da matrícula no domínio da instituição; o e-mail verdadeiro, quando cadastrado, substitui o montado no envio seguinte.', 3, 'administrador', 'conectar-ao-moodle', '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 164);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 165, 64, 'Qualquer segmento e qualquer série', 'A escola cadastra qualquer segmento e qualquer série, do 1º ano do Ensino Fundamental em diante; a avaliação é por nota, de 0 a 10, em todas as séries.', 4, 'administrador', 'organizar-a-escola', '2026-11-18 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 165);
