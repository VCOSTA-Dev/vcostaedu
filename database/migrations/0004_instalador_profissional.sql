-- 0004 — ONDA 2, ETAPA 1: o instalador com acabamento profissional (versão 1.19.0).
--
-- 1. As quatro páginas mortas herdadas da base saem: nenhum perfil as alcança e o controller não tem o método
--    (conferido um a um: AcessoAcademico::permissao, AcessoAcademico::itemMenu, Disciplinas::matrizAno,
--    Home::notasAtividades não existem). Nenhuma tabela tem chave estrangeira para adms_paginas; os vínculos de
--    permissão delas (adms_nivacs_pgs, todos com permissao = 2) saem junto. Página sem perfil volta a reprovar a
--    conferência final do instalador.
-- 2. A versão 1.19.0 e as novidades: a senha com o medidor e o revelar, os avisos legais e a página "Sobre",
--    as mensagens de sucesso com o botão de fechar.
-- Idempotente: pode rodar de novo sem efeito.

DELETE FROM `adms_nivacs_pgs`
 WHERE `adms_pagina_id` IN (
   SELECT `id` FROM `adms_paginas`
    WHERE (`controller` = 'AcessoAcademico' AND `metodo` = 'permissao')
       OR (`controller` = 'AcessoAcademico' AND `metodo` = 'itemMenu')
       OR (`controller` = 'Disciplinas' AND `metodo` = 'matrizAno')
       OR (`controller` = 'Home' AND `metodo` = 'notasAtividades')
 );

DELETE FROM `adms_paginas`
 WHERE (`controller` = 'AcessoAcademico' AND `metodo` = 'permissao')
    OR (`controller` = 'AcessoAcademico' AND `metodo` = 'itemMenu')
    OR (`controller` = 'Disciplinas' AND `metodo` = 'matrizAno')
    OR (`controller` = 'Home' AND `metodo` = 'notasAtividades');

UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.19.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 35, '1.19.0', 1, 19, 0, '2026-11-14', 'Instalação com acabamento profissional: textos revisados, botão Copiar nos comandos, senha com medidor de força e botão de revelar em todas as telas de senha, avisos legais e página Sobre, mensagens de sucesso que fecham.', 1, '2026-11-14 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.19.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.19.0';

INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 113, 35, 'Senha: mais longa, sem regra de símbolo', 'Toda tela de senha mostra a força enquanto você digita e tem o botão Mostrar. Vale o comprimento: pelo menos 12 caracteres, e uma frase longa é a melhor escolha. Senhas muito comuns e as que contêm o seu nome, usuário ou e-mail são recusadas. Espaços agora são aceitos.', 1, 'administrador,secretaria,professor,aluno', 'escolher-uma-senha', '2026-11-14 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 113);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 114, 35, 'Avisos legais e a página Sobre', 'O rodapé de todas as telas mostra a versão, os direitos autorais e a licença do sistema (software livre, AGPL-3.0). O link Sobre abre a licença completa e os créditos das bibliotecas usadas.', 2, 'administrador,secretaria,professor,aluno', NULL, '2026-11-14 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 114);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 115, 35, 'Toda mensagem de sucesso fecha no ×', 'As mensagens que confirmam uma gravação (chamada, faltas importadas, emissões, modelos, regras de média e outras) passam a ter o botão de fechar, como as demais.', 3, 'administrador,secretaria,professor', NULL, '2026-11-14 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 115);
