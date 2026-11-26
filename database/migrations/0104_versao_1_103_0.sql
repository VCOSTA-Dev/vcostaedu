-- A VERSÃO 1.103.0 (D-119, D-129 a D-133): a versão do lançamento.
--   O financeiro de ligar e desligar nos três tipos; a compensação liberável ao professor; a porta do banco no
--   instalador; a defesa contra duas migrations com o mesmo número; o aviso no histórico do Médio; e, no histórico da
--   escola, emitir e cancelar como permissões separadas.
-- COMO DESFAZER: apagar as novidades 232 a 236 e a versão 121, e voltar a 1.102.1 à situação 1.

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.103.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 121, '1.103.0', 1, 103, 0, '2026-11-26', 'O financeiro de ligar e desligar em qualquer tipo de instituição, a compensação de faltas pelo professor quando a instituição permite, a porta do banco na instalação, o aviso do histórico do Ensino Médio e as permissões separadas de emitir e cancelar o histórico escolar.', 1, '2026-11-26 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.103.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.103.0';

INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 232, 121, 'Ligar e desligar o financeiro', 'Em Configurações → Recursos da instituição, o administrador liga ou desliga o financeiro, na escola, no ensino superior e no curso livre. Desligado, ele some do menu, das telas e do portal para todos; nada é apagado, e ligando de novo tudo volta como estava.', 1, 'administrador,secretaria', 'o-financeiro', '2026-11-26 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 232);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 233, 121, 'O professor pode compensar faltas', 'Se a instituição quiser, o administrador liga para o professor a ação "Lançar compensação de ausência", em Perfis de acesso. Com ela, o professor lança a compensação (atestado, licença) nas disciplinas dele. De partida, continua com a secretaria.', 2, 'administrador,secretaria,professor', 'compensar-faltas', '2026-11-26 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 233);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 234, 121, 'Emitir e cancelar o histórico, separados', 'Na escola, emitir o histórico escolar (e a 2ª via) e cancelar um histórico emitido são permissões separadas em Perfis de acesso: dá para deixar alguém emitir sem poder cancelar.', 3, 'administrador,secretaria', 'perfis-de-acesso', '2026-11-26 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 234);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 235, 121, 'Aviso no histórico do Ensino Médio', 'Na prévia do histórico do Ensino Médio, se a conclusão do Ensino Fundamental do aluno não estiver registrada, a tela avisa e diz onde registrar. É só um aviso: o documento não muda e a emissão continua possível.', 4, 'administrador,secretaria', 'emitir-historico-escolar', '2026-11-26 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 235);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 236, 121, 'A porta do banco na instalação', 'A instalação pergunta a porta do banco de dados (de partida, 3306). Serve para o servidor em que o banco não está na porta padrão. A atualização do sistema também confere, antes de começar, se duas etapas têm o mesmo número, e para com um aviso claro.', 5, 'administrador', 'instalar-no-linux-ao-lado-do-moodle', '2026-11-26 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 236);
