-- A HIGIENE ANTES DE O CÓDIGO IR A PÚBLICO (D-123; 1.102.1, C1): os três textos que aparecem na tela e que vinham de
-- migrations já aplicadas (que não mudam: o hash delas é conferido em toda instalação).
--   1. a versão 1.12.0 (id 16) e a novidade 79 falavam do "modelo do cliente": passam a falar do modelo oficial;
--   2. a descrição do parâmetro do domínio do e-mail da escola (Conectar ao Moodle) dava como exemplo um domínio que
--      alguém pode registrar: passa ao domínio reservado de exemplo (.invalid), o que as provas já usam.
-- Só o texto muda (nenhuma tabela, nenhuma regra). COMO DESFAZER: não há o que desfazer além do texto.

UPDATE `adms_versoes` SET `resumo` = 'Histórico no layout do documento oficial (estilo oficial, Liberation Sans, casas do documento, CH no nome, linhas finais com a média final do aluno) e o atestado de matrícula (secretaria e portal), com trechos condicionais por variável.'
 WHERE `id` = 16 AND `resumo` LIKE 'Hist%rico no layout do modelo do cliente%';
UPDATE `adms_novidades` SET `titulo` = 'Modelo do histórico no desenho do documento oficial'
 WHERE `id` = 79 AND `titulo` LIKE 'Modelo do hist%rico no desenho do cliente';
UPDATE `adms_parametros` SET `descricao` = 'Moodle: o domínio do e-mail da escola, montado com a matrícula (por exemplo, alunos.escola.exemplo.invalid). Não recebe mensagens.'
 WHERE `chave` = 'moodle.email_dominio' AND `descricao` LIKE '%suaescola.edu.br%';
