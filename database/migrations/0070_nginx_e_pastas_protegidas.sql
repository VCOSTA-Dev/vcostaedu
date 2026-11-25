-- O Nginx e as pastas protegidas (Onda 8, correção, segunda leva, 2.10; D-96). O sistema faz as rotas e protege as
-- pastas pelos .htaccess, que só o Apache lê. Vêm o exemplo de configuração do Nginx (docs/nginx/vcostaedu.conf), o
-- guia curto (docs/IMPLANTACAO.md) e, nas Verificações de segurança, o item "Pastas protegidas": a tela pede pela web
-- um arquivo de cada pasta protegida e acusa FALHA quando alguma responde. A última conferência fica guardada
-- (adms_instalacao_estado, seguranca.pastas_protegidas) para a linha de comando, que não conhece o endereço.

-- a versão
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.83.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 100, '1.83.0', 1, 83, 0, '2026-11-22', 'O exemplo de configuração do Nginx; as Verificações de segurança acusam a pasta protegida que responde.', 1, '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.83.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.83.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 203, 100, 'O Nginx e as pastas protegidas', 'Em Manutenção → Verificações de segurança, o item "Pastas protegidas" pede pela web um arquivo de cada pasta que não pode responder (o código, a configuração, as etapas do banco, os arquivos privados) e diz FALHA, com a pasta, quando alguma responde — como num servidor Nginx, que não lê os arquivos .htaccess. Para o Nginx, o pacote traz um exemplo de configuração que faz as mesmas rotas e nega as mesmas pastas (docs/nginx/vcostaedu.conf, com o guia docs/IMPLANTACAO.md).', 1, 'administrador', 'avisos-e-seguranca', '2026-11-22 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 203);
