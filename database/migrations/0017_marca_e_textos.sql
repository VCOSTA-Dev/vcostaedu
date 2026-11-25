-- A marca, os textos e o pente fino das ações.
--
-- 1. O topo do menu: o selo do VCOSTAEDU fica, e ao lado dele vai a instituição — o NOME escrito ou o LOGO, à escolha
--    em Dados da instituição (sem instituição configurada, "VCOSTAEDU"). E a escolha do ícone da aba do navegador: o do
--    VCOSTAEDU (o padrão) ou o logo da instituição.
-- 2. O polo "Sede": toda instalação nasce com ele (o curso e a matrícula pedem um polo); numa instalação que não tem
--    nenhum polo, ele entra agora. A fase "o primeiro curso" dos Primeiros passos deixa de começar pelo polo.
-- 3. As ações: a coordenação do curso e os relatórios salvos ganham ação própria (estavam em ações chamadas
--    "Consultar…"); o limite ("até Professor") sai do NOME de toda ação — a tela Perfis de acesso o mostra na coluna dele.

ALTER TABLE `adms_empresas` ADD COLUMN IF NOT EXISTS `topo_menu` varchar(10) NOT NULL DEFAULT 'nome' COMMENT 'ao lado do selo, no topo do menu: nome ou logo';
ALTER TABLE `adms_empresas` ADD COLUMN IF NOT EXISTS `icone_aba` varchar(20) NOT NULL DEFAULT 'vcostaedu' COMMENT 'o ícone da aba do navegador: vcostaedu ou instituicao';

INSERT INTO `rm_polos` (`nome`, `cidade`, `uf`, `endereco`, `instituicao_parceira`, `situacao`, `usuario_cre`, `created`)
SELECT 'Sede', NULL, NULL, NULL, NULL, 1, 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `rm_polos`);
UPDATE `adms_fases` SET `texto` = 'As matérias, o curso (no polo Sede, que a instalação já traz), a grade (a matriz) e as disciplinas da grade.'
 WHERE `codigo` = 'curso' AND `texto` = 'O polo, as matérias, o curso, a grade (a matriz) e as disciplinas da grade.';

-- a coordenação do curso: a ação que era "Consultar cursos (até Direção)" já só tinha as três telas dela
UPDATE `adms_acoes` SET `nome` = 'Indicar a coordenação do curso' WHERE `id` = 42 AND `nome` = 'Consultar cursos (até Direção)';
-- os relatórios salvos: ação nova, com o mesmo limite (o Professor salva os relatórios da turma dele)
INSERT INTO `adms_acoes` (`id`, `codigo`, `nome`, `grupo`, `teto`, `ordem`)
SELECT 92, 'salvar_relatorios_92', 'Salvar relatórios', 'Relatórios', 'professor', 60 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_acoes` WHERE `id` = 92);
UPDATE `adms_paginas` SET `acao_id` = 92 WHERE `controller` = 'Relatorios' AND `metodo` IN ('salvar', 'apagarSalvo') AND `acao_id` = 60;

-- o limite sai do nome: cada ação diz o que faz
UPDATE `adms_acoes` SET `nome` = 'Ver o boletim de um aluno' WHERE `id` = 8 AND `nome` LIKE 'Consultar alunos e boletins (%';
UPDATE `adms_acoes` SET `nome` = 'Consultar alunos' WHERE `id` = 9 AND `nome` LIKE 'Consultar alunos e boletins (%';
UPDATE `adms_acoes` SET `nome` = 'Lançar faltas' WHERE `id` = 23 AND `nome` LIKE 'Lançar faltas e compensações (%';
UPDATE `adms_acoes` SET `nome` = 'Compensar faltas' WHERE `id` = 24 AND `nome` LIKE 'Lançar faltas e compensações (%';
UPDATE `adms_acoes` SET `nome` = 'Cancelar documentos emitidos' WHERE `id` = 28 AND `nome` LIKE 'Emitir e cancelar documentos (%';
UPDATE `adms_acoes` SET `nome` = 'Emitir documentos' WHERE `id` = 29 AND `nome` LIKE 'Emitir e cancelar documentos (%';
UPDATE `adms_acoes` SET `nome` = 'Apagar matérias' WHERE `id` = 33 AND `nome` LIKE 'Cadastrar e editar matérias (%';
UPDATE `adms_acoes` SET `nome` = 'Cadastrar e editar matérias' WHERE `id` = 34 AND `nome` LIKE 'Cadastrar e editar matérias (%';
UPDATE `adms_acoes` SET `nome` = 'Consultar cursos' WHERE `id` = 41 AND `nome` LIKE 'Consultar cursos (%';
UPDATE `adms_acoes` SET `nome` = 'Consultar as disciplinas ofertadas' WHERE `id` = 43 AND `nome` LIKE 'Consultar disciplinas ofertadas (%';
UPDATE `adms_acoes` SET `nome` = 'Consultar a polivalência das disciplinas' WHERE `id` = 44 AND `nome` LIKE 'Consultar disciplinas ofertadas (%';
UPDATE `adms_acoes` SET `nome` = 'Abrir uma disciplina' WHERE `id` = 45 AND `nome` LIKE 'Consultar disciplinas ofertadas (%';
UPDATE `adms_acoes` SET `nome` = 'Ver uma matéria' WHERE `id` = 47 AND `nome` LIKE 'Consultar matérias (%';
UPDATE `adms_acoes` SET `nome` = 'Consultar matérias' WHERE `id` = 48 AND `nome` LIKE 'Consultar matérias (%';
UPDATE `adms_acoes` SET `nome` = 'Cadastrar e apagar ofertas e polivalências' WHERE `id` = 53 AND `nome` LIKE 'Ofertar, encerrar e indicar professores (%';
UPDATE `adms_acoes` SET `nome` = 'Ofertar, encerrar e indicar professores' WHERE `id` = 54 AND `nome` LIKE 'Ofertar, encerrar e indicar professores (%';
UPDATE `adms_acoes` SET `nome` = 'Consultar a auditoria de notas e faltas' WHERE `id` = 56 AND `nome` LIKE 'Consultar a auditoria de notas e faltas (%';
UPDATE `adms_acoes` SET `nome` = 'Ver o histórico de uma nota' WHERE `id` = 57 AND `nome` LIKE 'Consultar a auditoria de notas e faltas (%';
UPDATE `adms_acoes` SET `nome` = 'Consultar os relatórios acadêmicos' WHERE `id` = 58 AND `nome` LIKE 'Consultar relatórios (%';
UPDATE `adms_acoes` SET `nome` = 'Consultar os relatórios de gestão' WHERE `id` = 59 AND `nome` LIKE 'Consultar relatórios (%';
UPDATE `adms_acoes` SET `nome` = 'Consultar os relatórios da turma' WHERE `id` = 60 AND `nome` LIKE 'Consultar relatórios (%';
UPDATE `adms_acoes` SET `nome` = 'Consultar os relatórios da secretaria' WHERE `id` = 61 AND `nome` LIKE 'Consultar relatórios (%';
UPDATE `adms_acoes` SET `nome` = 'Definir a regra de média padrão' WHERE `id` = 68 AND `nome` LIKE 'Cadastrar e aplicar regras de média (%';
UPDATE `adms_acoes` SET `nome` = 'Cadastrar e aplicar regras de média' WHERE `id` = 69 AND `nome` LIKE 'Cadastrar e aplicar regras de média (%';
UPDATE `adms_acoes` SET `nome` = 'Escolher a turma nas telas' WHERE `id` = 84 AND `nome` LIKE 'Buscar turmas nas telas (%';
UPDATE `adms_acoes` SET `nome` = 'Buscar turmas nas telas' WHERE `id` = 85 AND `nome` LIKE 'Buscar turmas nas telas (%';

-- A versão e as novidades
UPDATE `adms_versoes` SET `situacao` = 2 WHERE `numero` <> '1.31.0' AND `situacao` = 1;
INSERT INTO `adms_versoes` (`id`, `numero`, `major`, `minor`, `patch`, `data_versao`, `resumo`, `situacao`, `created`)
SELECT 47, '1.31.0', 1, 31, 0, '2026-11-15', 'A instituição no topo do menu, ao lado do selo (o nome ou o logo), e o ícone da aba; o rodapé com a marca; o × em toda mensagem; o polo Sede; as telas de detalhe com as ações em abas; a demonstração com uma faculdade fictícia genérica; ações com nomes que dizem o que fazem.', 1, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_versoes` WHERE `numero` = '1.31.0');
UPDATE `adms_versoes` SET `situacao` = 1 WHERE `numero` = '1.31.0';
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 138, 47, 'A marca da instituição', 'Ao lado do selo do VCOSTAEDU, no topo do menu, vai a instituição: o nome escrito ou o logo, à escolha em Configurações → Dados da instituição — onde também se escolhe o ícone da aba do navegador (o do VCOSTAEDU, ou o logo da instituição).', 1, 'administrador', 'dados-da-instituicao-e-atos-legais', '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 138);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 139, 47, 'Telas mais limpas', 'Na tela de uma disciplina (e nas outras telas de detalhe), as ações viram abas com a atual destacada, há um caminho de volta só, e as faixas azuis viraram cartões. Toda mensagem tem o × para fechar.', 2, 'administrador,secretaria,professor', NULL, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 139);
INSERT INTO `adms_novidades` (`id`, `adms_versao_id`, `titulo`, `texto`, `ordem`, `perfis`, `guia`, `created`)
SELECT 140, 47, 'A instalação já traz o polo Sede', 'Todo curso e toda matrícula pedem um polo: a instalação nasce com o polo Sede, e os Primeiros passos começam pelas matérias e pelo curso. A demonstração passou a ser uma faculdade fictícia genérica, com os mesmos dados de antes.', 3, 'administrador', NULL, '2026-11-15 00:00:00'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `adms_novidades` WHERE `id` = 140);
