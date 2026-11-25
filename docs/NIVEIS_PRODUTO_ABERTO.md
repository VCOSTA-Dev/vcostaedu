# Os níveis de acesso no produto aberto (Onda 2, Etapa 2)

Escrito **antes** da mudança, a partir do banco de uma instalação nova (migrations 0001 a 0004) e do código no commit `4e24065`. Na seção 4, o que a migration `0005` faz com cada nível.

## 1. Os níveis de hoje

`adms_niveis_acessos` (`database/migrations/0001_estrutura.sql:204`): id, nome, ordem, situacao. Criados em `0002_conteudo.sql:1247-1255`; o 9 em `:2150-2151` (1.8.0, D-260 da origem); a ordem foi refeita em `:2903-2913` (1.18.3) e a matriz em `:2915-2978`.

| id | nome | ordem | páginas com permissão | usuários (instalação nova + demonstração) | de quem é |
|---|---|---|---|---|---|
| 1 | Super Administrador | 1 | 224 de 224 | 1 (o do instalador) | da instalação (o comentário da origem o chama "Administrador VCOSTA") |
| 7 | Supervisor VCOSTA | 2 | 9 | 0 | **do fornecedor** (leva a marca) |
| 8 | VCOSTA | 3 | 8 | 0 | **do fornecedor** (leva a marca) |
| 9 | Suporte técnico | 4 | 33 (só manutenção) | 0 | **do fornecedor** (suporte técnico) |
| 2 | Administrador | 5 | 185 | 1 (`adm.escola.demo`) | da instituição |
| 3 | Diretoria | 6 | 98 | 0 | da instituição |
| 4 | Secretaria | 7 | 115 | 1 | da instituição |
| 5 | Coordenação | 8 | 76 | 0 | da instituição |
| 6 | Professor | 9 | 45 | 2 | da instituição |

O portal tem o seu próprio nível (`col_niveis_acessos`, id 1 "Estudante"); ele não muda nesta etapa.

## 2. Todo ponto do código que cita nível por id ou por nome

### O núcleo
- `painel/app/adms/Models/helper/AdmsNivel.php:23` `VCOSTA = [7, 8, 9]`; `:26` `MANUTENCAO` (os controllers que os níveis da VCOSTA abrem); `:33` `ADMINISTRADORES = [1, 2]`; `:35` `ehAdministrador()`; `:45` `ehVcosta()`; `:51` `podeController()`.
- `painel/app/adms/Models/AdmsPaginas.php:44` — a rota recusa o controller fora de `MANUTENCAO` para os níveis da VCOSTA.
- `painel/app/adms/Models/AdmsBotao.php:38` — o botão, idem.
- `painel/app/adms/Models/AdmsMenus.php:33-34` — o menu, idem.
- `painel/app/adms/Models/helper/AdmsHierarquia.php` — por **ordem**, não por id (`:25` ordem, `:37` podeAlterarNivel, `:57` podeAlterarUsuario, `:79` niveisAtribuiveis); quem chama: `Controllers/Liberar.php:75`, `NivelAcesso.php:209,309`, `Usuarios.php:80,102`, `usuarios/AdmsCadastrarUsuarios.php:32,162`, `AdmsEditarUsuario.php:42`, `AdmsEditarSenhaUsuario.php:26`, `AdmsApagarUsuario.php:26`, `AdmsListarUsuarios.php:21`, `nivelAcesso/AdmsEditarOrdenacao.php:31`, `AdmsListarNivelAcesso.php:20`.

### Por id, fora do núcleo
- `painel/app/adms/Models/helper/AdmsValAcesso.php:25` — `[1, 2, 4]` veem todas as disciplinas; `:53` a coordenação (qualquer coordenação ativa, sem olhar o curso).
- `painel/app/adms/Models/faltas/AdmsCompensacoes.php:24` — `NIVEIS = [1, 2, 4]` lançam compensação (`:35`).
- `painel/app/adms/Models/helper/AdmsCentralGuias.php:22,47` e `portal/app/adms/Models/helper/AdmsCentralGuias.php:22,47` — `PERFIS_PAINEL` 1 e 2 → administrador, 4 → secretaria, 6 → professor.
- `painel/app/adms/Models/relatorios/AdmsRelatorios.php:112` — `ehProfessor()` é `=== 6`; `:266` o filtro de nível tira `AdmsNivel::VCOSTA`; `:69` `podeController('Relatorios')`.
- `painel/app/adms/Models/tcc/AdmsTcc.php:47` — os orientadores são os usuários do nível 6.
- `painel/app/adms/Views/historicoModelos/formModelo.php:24` — "Ver código" só para `nivel() === 1` (o comentário diz "administrador da instituição", e o código deixa o 2 de fora).
- `painel/app/adms/Models/historico/AdmsHistoricoEmissao.php:177-180,196,247,285` — `recusaVcosta()`: os níveis da VCOSTA não emitem nem cancelam documento.
- `ehAdministrador()` (níveis 1 e 2): `Controllers/Chamada.php:102`, `Disciplinas.php:393`, `HistoricoEmissoes.php:27`, `RegraMedia.php:330`, `Models/matriz/AdmsMatrizRegras.php:59`, `notas/AdmsReabrirOferta.php:31`, `Views/faltas/visFaltas.php:35`, `Views/medias/visFinal.php:24`, `Views/atualizacao/pendente.php:17`, `Views/include_tabler/_aviso_atualizacao.php:7`.
- `painel/app/adms/Views/home/home.php:36,43` — comentários que citam Suporte técnico, Supervisor VCOSTA e VCOSTA.
- `painel/app/adms/Models/acesso/AdmsLogin.php:46,71-74` — o login grava `colUsuario_nivel`, `colUsuario_ordem` e `colUsuario_nivel_nome`.

### Ferramentas, instalador e provas
- `database/ferramentas/admin_comum.php:3,36-37` — cria o administrador no nível 1 ("Super Administrador").
- `database/ferramentas/criar_admin.php:3,34` — confere `!== 1`.
- `database/ferramentas/promover_admin_vcosta.php:3,23,36,46-47,65` — põe um usuário no nível 1 ("o ADMINISTRADOR VCOSTA").
- `database/ferramentas/implantacao/verificar_ambiente.php:185` — conta os administradores do nível 1.
- `database/ferramentas/seed_demo_processar.php:83-88` — a equipe da demonstração: níveis 2, 4, 6, 6.
- `instalar/administrador.php:8,82-90` (nível 1, "Super Administrador") e `:149-162` (a cópia da consulta do roteador); `instalar/index.php:15,806` ("Super Administrador").
- `tests/tabler/regua.php:47` (sessão de nível 1); `tests/tabler/sessao.js:51,54,75` (troca a secretaria para 6 e de volta para 4); `tests/jornada/bloco05.js:39` (o professor pelo texto da opção).

### O aluno (portal), por id do nível do portal (1 = Estudante) — não muda
`painel/app/adms/Models/academicos/AdmsEnviarLinkAcesso.php:32`, `AdmsListarAcademico.php:28`, `AdmsPendenciaSexo.php:19`, `AdmsCadastrarUsuarioAcademico.php:81`, `importacao/AdmsImportacaoCsv.php:123`, `notas/AdmsBoletimCurso.php:28`, `portalAlunos/AdmsEnturmacaoSugerida.php:24`, `vinculo/AdmsVincularAluno.php:29`, `parametros/AdmsParametrosInstituicao.php:215,220`; `portal/app/adms/Models/acesso/AdmsIdentificaAluno.php:35`, `portal/app/adms/Models/notas/AdmsBoletimCurso.php:28`.

### Onde o nível é só coluna (sem id fixo)
As telas de níveis, permissões, páginas e usuários (`nivelAcesso/*`, `acessoAcademico/*`, `paginas/AdmsCadastrarPaginas.php`, `pgAcademico/*`, `usuarios/*`, `relatorios/RelUsuariosAcesso.php`, `RelExportacoes.php`) leem e gravam o nível escolhido na tela; `rm_relatorios_salvos.nivel_acesso_id` (`0001:1624`) guarda o perfil com quem uma visão de relatório foi compartilhada.

## 3. O que a origem já tinha de coordenação
`rm_cursos_coordenacao` (`0001:740`: curso, usuário, posição, situação) e a tela do curso (`Views/cursos/verCurso.php`, o modal "Adicionar coordenador" → `cursos/cadastrar-usuario`, editar, apagar). Só a lista de disciplinas a usa (`disciplinas/AdmsListarDisciplinas.php`, junção com `rm_cursos_coordenacao`). O nível 5 "Coordenação" não é citado no código.

## 4. O que a migration 0005 faz

O papel deixa de ser deduzido do id: `adms_niveis_acessos.papel` diz o papel do produto (administrador, direcao, supervisao_secretaria, secretaria, coordenacao, professor); `so_consulta` e `acoes_sensiveis` dizem o que o nível pode; `adms_paginas.grava` e `adms_paginas.sensivel` dizem o que a página faz. Nível criado na tela nasce sem papel e sem as duas marcas, e segue a matriz que o administrador der a ele.

| antes | depois | papel | marcas |
|---|---|---|---|
| 1 Super Administrador | **Administrador** (id 1) — todas as páginas | administrador | ações sensíveis |
| 2 Administrador | sai: os usuários dele passam ao Administrador (o mesmo papel, duplicado) | — | — |
| 7 Supervisor VCOSTA, 8 VCOSTA, 9 Suporte técnico | saem: os usuários deles passam ao Administrador; as telas de manutenção ficam com o Administrador | — | — |
| 3 Diretoria | **Direção acadêmica** (id 3) — toda página de consulta (`grava = 0`), menos a administração do sistema | direcao | só consulta |
| — | **Supervisão de secretaria** (novo) — a matriz da Secretaria e as ações sensíveis | supervisao_secretaria | ações sensíveis |
| 4 Secretaria | **Secretaria** (id 4) — a mesma matriz, sem as ações sensíveis | secretaria | — |
| 5 Coordenação | **Coordenação de curso** (id 5) — a visão do professor e os relatórios, só dos cursos em que é coordenador | coordenacao | só consulta |
| 6 Professor | **Professor** (id 6) — a mesma matriz, sem as ações sensíveis | professor | — |

A ordem (a hierarquia de quem altera quem) fica: Administrador 1, Direção acadêmica 2, Supervisão de secretaria 3, Secretaria 4, Coordenação de curso 5, Professor 6; os níveis criados na tela vêm depois, na ordem em que estavam.

Cada ponto da seção 2 que citava nível do fornecedor, ou o administrador pelo id, passa a perguntar o papel (`AdmsNivel::papel()`); até a `0005` ser aplicada, a pergunta responde pelo id, como antes (a instalação em uso não cai entre o código novo e a migration).
