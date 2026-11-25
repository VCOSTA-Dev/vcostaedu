-- Onda 8, a etapa nova (D-70): do site da instituição ao Moodle numa chamada só.
--   - a interface ganha POST /inscricoes (o cadastro, a matrícula e a turma ou as ofertas, tudo ou nada) e
--     POST /alunos/{ra}/primeiro-acesso (o e-mail de boas-vindas com o link de criar a senha);
--   - o que sobrou na fila de envios ao Moodle anda sozinho, no fim das requisições seguintes (AdmsMoodle::retomar);
--   - as inscrições que falharam porque o usuário do aluno não estava no Moodle voltam à fila quando ele chega lá;
--   - o link que já criou a senha, aberto de novo, leva à entrada com o aviso de que a senha já foi criada;
--   - a demonstração da escola ganha o curso livre (a turma aberta, os quatro módulos).
--   Nenhuma tabela muda: só a versão e a novidade.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.60.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 77, '1.60.0', 1, 60, 0, '2026-11-20', 'Do site da instituição ao Moodle numa chamada só: a inscrição pela interface, o primeiro acesso e o envio que termina sozinho.', 1, '2026-11-20 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.60.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.60.0';

-- a novidade
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 180, 77, 'A inscrição numa chamada só', 'A interface de integração ganhou a inscrição numa chamada só (POST /inscricoes): o site da instituição cadastra a pessoa, faz a matrícula e põe na turma de uma vez, tudo ou nada, e ela vai ao Moodle; e o primeiro acesso (POST /alunos/{ra}/primeiro-acesso), que manda o e-mail de boas-vindas com o link de criar a senha. O que não coube no envio ao Moodle termina sozinho, sem clique, e as inscrições que esperavam o usuário do aluno voltam à fila quando ele chega lá.', 1, 'administrador', 'integrar-outro-sistema-pela-api', '2026-11-20 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 180);
