# O pente fino das telas

Toda página do painel, classificada (versão 1.29.0):

- **USO** — o trabalho do dia a dia: secretaria, professores, coordenação e direção;
- **CONFIGURAÇÃO** — o que a instituição ajusta (de vez em quando) para o sistema ser o dela;
- **PROGRAMAÇÃO** — o que só quem programa o sistema mexe (páginas, menus, níveis, permissões tela a tela). **Saiu do produto** na 1.29.0 (migration `0015`): o produto se configura, não se programa pela tela.

O motor de permissão continua: a matriz (`adms_nivacs_pgs`) é a mesma, e um nível criado pela tela numa instalação anterior continua valendo. O que mudou é que a permissão passou a ser por **ação** (`adms_acoes`): cada página aponta para a ação dela, e cada ação tem um nome que a pessoa entende, o grupo e o **teto** — o perfil mais baixo que pode recebê-la, pela ordem dos perfis. O motor recusa (`AdmsPaginas::nivelPode`, e o menu esconde) e a conferência do instalador e a verificação do ambiente reprovam (`core/Teto.php`) quando a matriz dá uma ação a um perfil abaixo do teto. O nível sem papel (criado pela tela) não tem lugar na ordem e segue só a matriz. A tela **Perfis de acesso** (Equipe, só o Administrador) mostra a matriz por ação, só para ler.

O grupo **Manutenção** do menu fica com **Atualizar o sistema** (e, adiante, as telas de avisos e de verificações de segurança).

## PROGRAMAÇÃO (saíram)

| Tela | Controller | Métodos |
|---|---|---|
| Níveis de acesso | `NivelAcesso` | `listar`, `cadastrar`, `visualizar`, `editar`, `permissao`, `buscaMenu`, `itemMenu`, `ordenacao`, `apagar`, `atualizarPermissao` |
| Páginas | `Paginas` | `listar`, `cadastrar`, `visualizar`, `editar`, `editarMenu`, `apagar` |
| Itens de menu | `Menu` | `listar`, `cadastrar`, `visualizar`, `editar`, `apagar`, `ordenacao` |
| Acesso acadêmico | `AcessoAcademico` | `listar`, `cadastrar`, `visualizar`, `editar`, `apagar`, `buscaMenu` |
| Páginas acadêmicas | `PaginasAcademico` | `listar`, `cadastrar`, `visualizar`, `editar`, `apagar`, `editarMenu` |
| as permissões por tela (Liberar) | `Liberar` | `permissao`, `menu`, `dropdown` |
| as permissões acadêmicas por tela | `LiberarAcademico` | `permissao`, `menu`, `dropdown` |

Também saíram: o botão **Ver código** do editor de modelos de histórico (o modelo se edita pelos blocos, não pelo HTML) e as sobras do fornecedor no código (`AdmsNivel::ehVcosta` e `podeController`, a trava dos níveis 7, 8 e 9 que a migration `0005` já tinha removido do banco).

## USO (119 páginas)

| Controller | Página (método) | Ação |
|---|---|---|
| `Academicos` | apagar | Apagar aluno |
|  | boletim | Consultar alunos e boletins (até Professor) |
|  | cadastrar | Cadastrar e editar alunos e vínculos |
|  | editar | Cadastrar e editar alunos e vínculos |
|  | editarSenha | Cadastrar e editar alunos e vínculos |
|  | editarVinculo | Cadastrar e editar alunos e vínculos |
|  | enviarLink | Cadastrar e editar alunos e vínculos |
|  | imprimirBoletim | Consultar alunos e boletins (até Professor) |
|  | listar | Consultar alunos e boletins (até Secretaria) |
|  | pendenciaSexo | Consultar alunos e boletins (até Secretaria) |
|  | posicionarModulo | Cadastrar e editar alunos e vínculos |
|  | vincular | Cadastrar e editar alunos e vínculos |
|  | visualizar | Consultar alunos e boletins (até Secretaria) |
| `Ajuda` | guia | Consultar a ajuda |
|  | imagem | Consultar a ajuda |
|  | index | Consultar a ajuda |
|  | videos | Consultar a ajuda |
| `Atividades` | apagar | Lançar notas e atividades |
|  | cadastrar | Lançar notas e atividades |
|  | editar | Lançar notas e atividades |
|  | editNotas | Lançar notas e atividades |
|  | listar | Consultar atividades e notas |
|  | uploadNotas | Lançar notas e atividades |
|  | visualizar | Consultar atividades e notas |
| `Auditoria` | listar | Consultar a auditoria de notas e faltas (até Coordenação) |
|  | nota | Consultar a auditoria de notas e faltas (até Professor) |
| `BoletimAluno` | listar | Consultar o boletim do aluno |
| `Buscar` | turmas | Buscar turmas nas telas (até Secretaria) |
|  | turmaserie | Buscar turmas nas telas (até Professor) |
| `Chamada` | encontro | Fazer a chamada |
|  | excluir | Excluir encontro da chamada |
|  | fonte | Fazer a chamada |
|  | listar | Consultar a chamada |
| `Disciplinas` | adicionaPolivalencia | Ofertar, encerrar e indicar professores (até Administrador) |
|  | adicionaProfessor | Ofertar, encerrar e indicar professores (até Secretaria) |
|  | apagar | Ofertar, encerrar e indicar professores (até Administrador) |
|  | apagarPolivalencia | Ofertar, encerrar e indicar professores (até Administrador) |
|  | cadastrar | Ofertar, encerrar e indicar professores (até Administrador) |
|  | editar | Ofertar, encerrar e indicar professores (até Secretaria) |
|  | encerrar | Ofertar, encerrar e indicar professores (até Secretaria) |
|  | gerarOferta | Ofertar, encerrar e indicar professores (até Secretaria) |
|  | listar | Consultar disciplinas ofertadas (até Coordenação) |
|  | reabrir | Reabrir disciplina encerrada |
|  | verPolivalente | Consultar disciplinas ofertadas (até Direção) |
|  | visualizar | Consultar disciplinas ofertadas (até Professor) |
| `FacaComigo` | iniciar | Usar o Faça comigo |
|  | passos | Usar o Faça comigo |
| `Faltas` | compensar | Lançar faltas e compensações (até Secretaria) |
|  | editar | Lançar faltas e compensações (até Professor) |
|  | importar | Lançar faltas e compensações (até Professor) |
|  | modelo | Consultar faltas |
|  | removerCompensacao | Lançar faltas e compensações (até Secretaria) |
|  | visualizar | Consultar faltas |
| `Historico` | previa | Ver a prévia do histórico |
| `HistoricoEmissoes` | cancelar | Emitir e cancelar documentos (até Administrador) |
|  | emitir | Emitir e cancelar documentos (até Secretaria) |
|  | listar | Consultar documentos emitidos |
|  | pdf | Consultar documentos emitidos |
|  | previa | Consultar documentos emitidos |
|  | segundaVia | Emitir e cancelar documentos (até Secretaria) |
|  | ver | Consultar documentos emitidos |
| `Home` | index | Página inicial |
| `ImportacaoAlunos` | aplicar | Importar alunos de planilha |
|  | conferir | Consultar importações de alunos |
|  | corrigir | Importar alunos de planilha |
|  | desfazer | Desfazer importação de alunos |
|  | enviar | Importar alunos de planilha |
|  | listar | Consultar importações de alunos |
|  | mapear | Importar alunos de planilha |
|  | modelo | Consultar importações de alunos |
| `Login` | acesso | (pública) |
|  | atualizarSenha | (pública) |
|  | definirSenha | (pública) |
|  | esqueceuSenha | (pública) |
|  | logout | (pública) |
| `Medias` | atualizarLista | Atualizar a lista de notas |
|  | final | Consultar notas e resultado |
| `MinhasDisciplinas` | listar | Minhas disciplinas |
| `Novidades` | listar | Ver as novidades |
|  | visto | Ver as novidades |
| `Perfil` | atualizarDados | Ver e editar o próprio perfil |
|  | editar | Ver e editar o próprio perfil |
|  | editarSenha | Ver e editar o próprio perfil |
|  | visualizar | Ver e editar o próprio perfil |
| `PortalAlunos` | confirmarEnturmacao | Enturmar alunos |
|  | listar | Consultar a enturmação |
|  | ordenacao | Enturmar alunos |
|  | visualizar | Consultar a enturmação |
| `Relatorios` | alunosRisco | Consultar relatórios (até Coordenação) |
|  | alunosSituacao | Consultar relatórios (até Coordenação) |
|  | apagarSalvo | Consultar relatórios (até Professor) |
|  | aptosConcluir | Consultar relatórios (até Secretaria) |
|  | concluintes | Consultar relatórios (até Direção) |
|  | coorteIngresso | Consultar relatórios (até Direção) |
|  | cpfCompleto | Consultar relatórios (até Secretaria) |
|  | desempenhoAluno | Consultar relatórios (até Coordenação) |
|  | distribuicaoNotas | Consultar relatórios (até Coordenação) |
|  | documentosEmitidos | Consultar relatórios (até Secretaria) |
|  | exportacoes | Consultar relatórios (até Direção) |
|  | exportar | Consultar relatórios (até Professor) |
|  | frequenciaMinimo | Consultar relatórios (até Coordenação) |
|  | frequenciaOferta | Consultar relatórios (até Professor) |
|  | listar | Consultar relatórios (até Professor) |
|  | listaTurma | Consultar relatórios (até Professor) |
|  | mapaNotas | Consultar relatórios (até Professor) |
|  | painelAcademico | Consultar relatórios (até Coordenação) |
|  | parametros | Salvar e exportar relatórios |
|  | pendenciasLancamento | Consultar relatórios (até Coordenação) |
|  | previsaoConclusao | Consultar relatórios (até Coordenação) |
|  | qualidadeCadastro | Consultar relatórios (até Secretaria) |
|  | reaberturas | Consultar relatórios (até Direção) |
|  | resultadoDisciplina | Consultar relatórios (até Coordenação) |
|  | salvar | Consultar relatórios (até Professor) |
|  | situacaoTcc | Consultar relatórios (até Coordenação) |
|  | trocasRegra | Consultar relatórios (até Direção) |
|  | usuariosAcesso | Consultar relatórios (até Direção) |
| `Tcc` | salvar | Registrar o TCC |
| `Verificar` | codigo | (pública) |
| `Versao` | index | Ver a versão |

## CONFIGURAÇÃO (94 páginas)

| Controller | Página (método) | Ação |
|---|---|---|
| `AnosLetivos` | apagar | Cadastrar e editar anos letivos |
|  | cadastrar | Cadastrar e editar anos letivos |
|  | editar | Cadastrar e editar anos letivos |
|  | listar | Consultar anos letivos |
| `AtosLegais` | cadastrar | Cadastrar e editar atos legais |
|  | editar | Cadastrar e editar atos legais |
|  | listar | Consultar atos legais |
| `Atualizar` | aplicar | Atualizar o sistema |
|  | index | Ver as atualizações pendentes |
| `ConfiguracaoEmail` | apagar | Configurar o e-mail de envio |
|  | cadastrar | Configurar o e-mail de envio |
|  | editar | Configurar o e-mail de envio |
|  | listar | Consultar o e-mail de envio |
|  | testar | Configurar o e-mail de envio |
|  | visualizar | Consultar o e-mail de envio |
| `Cursos` | apagar | Cadastrar e editar cursos e a coordenação |
|  | apagarUsuario | Consultar cursos (até Direção) |
|  | cadastrar | Cadastrar e editar cursos e a coordenação |
|  | cadastrarUsuario | Consultar cursos (até Direção) |
|  | editar | Cadastrar e editar cursos e a coordenação |
|  | editarUsuario | Consultar cursos (até Direção) |
|  | listar | Consultar cursos (até Coordenação) |
|  | situacao | Cadastrar e editar cursos e a coordenação |
|  | visualizar | Consultar cursos (até Coordenação) |
| `DadosInstituicao` | editar | Editar os dados da instituição |
| `Demonstracao` | comecar | Ver como começar |
|  | instituicao | Montar e remover a demonstração |
|  | montar | Montar e remover a demonstração |
| `HistoricoModelos` | cadastrar | Editar modelos de histórico |
|  | editar | Editar modelos de histórico |
|  | listar | Consultar modelos de histórico |
|  | previa | Consultar modelos de histórico |
| `HorarioAula` | cadastrarSala | Cadastrar horários de aula |
|  | editar | Cadastrar horários de aula |
|  | listar | Consultar horários de aula |
|  | visualizar | Consultar horários de aula |
| `Integracoes` | chamadas | Consultar a documentação e as chamadas da API |
|  | chaves | Consultar a documentação e as chamadas da API |
|  | criar | Gerenciar chaves de API |
|  | documentacao | Consultar a documentação e as chamadas da API |
|  | especificacao | Consultar a documentação e as chamadas da API |
|  | revogar | Gerenciar chaves de API |
| `Materias` | apagar | Cadastrar e editar matérias (até Administrador) |
|  | cadastrar | Cadastrar e editar matérias (até Secretaria) |
|  | editar | Cadastrar e editar matérias (até Secretaria) |
|  | listar | Consultar matérias (até Secretaria) |
|  | situacao | Cadastrar e editar matérias (até Secretaria) |
|  | visualizar | Consultar matérias (até Professor) |
| `Matriz` | adicionarDisciplina | Montar a matriz (as grades) |
|  | cadastrarGrade | Montar a matriz (as grades) |
|  | editarGrade | Montar a matriz (as grades) |
|  | listar | Consultar a matriz |
|  | moverDisciplina | Montar a matriz (as grades) |
|  | ordenarDisciplinas | Montar a matriz (as grades) |
|  | removerDisciplina | Montar a matriz (as grades) |
|  | situacaoGrade | Montar a matriz (as grades) |
|  | visualizar | Consultar a matriz |
| `Modulos` | apagar | Cadastrar e editar módulos |
|  | cadastrar | Cadastrar e editar módulos |
|  | editar | Cadastrar e editar módulos |
|  | listar | Consultar módulos |
| `Parametros` | listar | Consultar parâmetros da instituição |
|  | salvar | Editar parâmetros da instituição |
| `PerfisAcesso` | index | Consultar os perfis de acesso |
| `Polos` | cadastrar | Cadastrar e editar polos |
|  | editar | Cadastrar e editar polos |
|  | listar | Consultar polos |
| `PrimeirosPassos` | fase | Pular e retomar fases |
|  | index | Primeiros passos |
| `RegraMedia` | aplicar | Cadastrar e aplicar regras de média (até Secretaria) |
|  | cadastrar | Cadastrar e aplicar regras de média (até Secretaria) |
|  | curso | Cadastrar e aplicar regras de média (até Secretaria) |
|  | disciplina | Cadastrar e aplicar regras de média (até Secretaria) |
|  | duplicar | Cadastrar e aplicar regras de média (até Secretaria) |
|  | editar | Cadastrar e aplicar regras de média (até Secretaria) |
|  | inativar | Cadastrar e aplicar regras de média (até Secretaria) |
|  | instituicao | Cadastrar e aplicar regras de média (até Secretaria) |
|  | listar | Consultar regras de média |
|  | oferta | Consultar regras de média |
|  | padrao | Cadastrar e aplicar regras de média (até Administrador) |
|  | simular | Consultar regras de média |
| `SalasDeAula` | apagar | Cadastrar e editar salas de aula |
|  | cadastrar | Cadastrar e editar salas de aula |
|  | editar | Cadastrar e editar salas de aula |
|  | listar | Consultar salas de aula |
|  | visualizar | Consultar salas de aula |
| `TentativasAcesso` | liberar | Liberar acesso bloqueado |
|  | listar | Consultar tentativas de acesso |
| `Usuarios` | apagar | Cadastrar e editar a equipe |
|  | cadastrar | Cadastrar e editar a equipe |
|  | editar | Cadastrar e editar a equipe |
|  | editarSenha | Cadastrar e editar a equipe |
|  | listar | Consultar a equipe |
|  | visualizar | Consultar a equipe |

## O portal do aluno (23 páginas): todas USO

O portal é o aluno usando o sistema: não tem tela de configuração nem de programação. A permissão dele é a do aluno (o portal tem um perfil só).

| Controller | Páginas (métodos) |
|---|---|
| `Atestado` | emitir, index, pdf |
| `Home` | ajuda, boletimAluno, guia, guiaImagem, horarioAluno, index, notasAtividades, novidades, novidadesVisto, videos |
| `Login` | acesso, atualizarSenha, definirSenha, esqueceuSenha, logout, sonda (públicas: a entrada e as senhas) |
| `Perfil` | editar, editarSenha, visualizar |
| `Progresso` | index |
