# O esqueleto herdado: o que ainda descende do material de curso

Gerado por `php database/ferramentas/origem_do_codigo.php`; não edite à mão. É só o inventário: nenhum crédito ou aviso foi
mudado por ele. A origem do código, por pasta e por licença, está em `docs/ORIGEM.md`.

## Como foi medido

- **O ponto de partida** é a cópia do sistema de origem importada no começo deste repositório (a versão 1.18.4).
- **Descende do esqueleto** o arquivo PHP do painel ou do portal que, naquela cópia, trazia a guarda de acesso do
  esqueleto MVC do material de curso no começo do arquivo: o roteador, a montagem da tela, os ajudantes de banco, o
  login, o menu, e os controllers, models e views escritos sobre ele.
- **Reescrito** é a parte das linhas de hoje com conteúdo próprio (sem as vazias, as só de símbolo e as curtas) que
  não existe na cópia de origem. 100% é um arquivo todo novo; 0%, o arquivo como veio. A conta é por linha: trocar
  um nome numa linha conta a linha como reescrita.

## O resumo

- **457 arquivos** ainda descendem do esqueleto, com 37.879 linhas hoje; 18% dessas linhas já foram reescritas.
- Por faixa: reescrito: 1; mais da metade reescrita: 16; em parte: 218; como veio: 222.
- 80 arquivos que vinham do esqueleto saíram do repositório.

## Por pasta

| pasta | arquivos | reescrito (média) |
|---|---|---|
| `painel/app/adms/Controllers` | 36 | 11% |
| `painel/app/adms/Models` | 186 | 10% |
| `painel/app/adms/Views` | 148 | 23% |
| `painel/core/CarregarPgAdm.php` | 1 | 2% |
| `painel/core/Config.example.php` | 1 | 16% |
| `painel/core/ConfigController.php` | 1 | 33% |
| `painel/core/ConfigView.php` | 1 | 62% |
| `painel/core/Seguranca.php` | 1 | 30% |
| `portal/app/adms/Controllers` | 6 | 13% |
| `portal/app/adms/Models` | 41 | 15% |
| `portal/app/adms/Views` | 31 | 23% |
| `portal/core/Config.example.php` | 1 | 14% |
| `portal/core/ConfigController.php` | 1 | 25% |
| `portal/core/ConfigView.php` | 1 | 62% |
| `portal/core/Seguranca.php` | 1 | 30% |

## Arquivo por arquivo

Do menos ao mais reescrito.

| arquivo | linhas hoje | reescrito | faixa |
|---|---|---|---|
| `painel/app/adms/Models/historico/AdmsVidaAcademica.php` | 389 | 0% | como veio |
| `painel/app/adms/Models/anosLetivos/AdmsAnosLetivos.php` | 154 | 1% | como veio |
| `painel/app/adms/Models/atividades/AdmsVisualizarAtividades.php` | 75 | 1% | como veio |
| `painel/app/adms/Models/helper/AdmsVersao.php` | 141 | 1% | como veio |
| `painel/app/adms/Models/historico/AdmsHistoricoDocumento.php` | 339 | 1% | como veio |
| `painel/app/adms/Models/historico/AdmsHistoricoEditor.php` | 257 | 1% | como veio |
| `painel/app/adms/Models/historico/AdmsHistoricoSecoes.php` | 147 | 1% | como veio |
| `painel/app/adms/Models/historico/AdmsHtmlControlado.php` | 122 | 1% | como veio |
| `painel/app/adms/Models/importacao/AdmsImportacaoCsv.php` | 99 | 1% | como veio |
| `painel/app/adms/Models/matriz/AdmsVerMatriz.php` | 67 | 1% | como veio |
| `painel/app/adms/Models/modulos/AdmsSalvarModulo.php` | 70 | 1% | como veio |
| `painel/app/adms/Models/relatorios/RelDocumentos.php` | 70 | 1% | como veio |
| `painel/app/adms/Models/relatorios/RelFrequenciaOferta.php` | 77 | 1% | como veio |
| `painel/app/adms/Models/relatorios/RelPendenciasLancamento.php` | 79 | 1% | como veio |
| `painel/app/adms/Models/relatorios/RelQualidadeCadastro.php` | 67 | 1% | como veio |
| `painel/app/adms/Models/relatorios/RelResultadoDisciplina.php` | 96 | 1% | como veio |
| `painel/app/adms/Models/vinculo/AdmsListarVinculos.php` | 81 | 1% | como veio |
| `portal/app/adms/Models/academico/AdmsAtestadoAluno.php` | 72 | 1% | como veio |
| `painel/app/adms/Controllers/Verificar.php` | 55 | 2% | como veio |
| `painel/app/adms/Models/atosLegais/AdmsAtosLegais.php` | 97 | 2% | como veio |
| `painel/app/adms/Models/disciplinas/AdmsListarDisciplinas.php` | 42 | 2% | como veio |
| `painel/app/adms/Models/helper/AdmsAsset.php` | 49 | 2% | como veio |
| `painel/app/adms/Models/matriz/AdmsMatrizRegras.php` | 144 | 2% | como veio |
| `painel/app/adms/Models/matriz/AdmsMoverDisciplinaMatriz.php` | 49 | 2% | como veio |
| `painel/app/adms/Models/modalidades/AdmsModalidades.php` | 47 | 2% | como veio |
| `painel/app/adms/Models/relatorios/RelAptosConcluir.php` | 44 | 2% | como veio |
| `painel/app/adms/Models/relatorios/RelConcluintes.php` | 61 | 2% | como veio |
| `painel/app/adms/Models/relatorios/RelCoorte.php` | 65 | 2% | como veio |
| `painel/app/adms/Models/relatorios/RelDistribuicaoNotas.php` | 65 | 2% | como veio |
| `painel/app/adms/Models/relatorios/RelExportacoes.php` | 54 | 2% | como veio |
| `painel/app/adms/Models/relatorios/RelListaTurma.php` | 53 | 2% | como veio |
| `painel/app/adms/Models/relatorios/RelPainelAcademico.php` | 129 | 2% | como veio |
| `painel/app/adms/Models/relatorios/RelPrevisaoConclusao.php` | 53 | 2% | como veio |
| `painel/app/adms/Models/relatorios/RelSituacaoTcc.php` | 65 | 2% | como veio |
| `painel/app/adms/Models/relatorios/RelTrocasRegra.php` | 53 | 2% | como veio |
| `painel/app/adms/Models/relatorios/RelUsuariosAcesso.php` | 53 | 2% | como veio |
| `painel/app/adms/Models/vinculo/AdmsPosicionarModulo.php` | 55 | 2% | como veio |
| `painel/app/adms/Models/vinculo/AdmsVinculoRegras.php` | 87 | 2% | como veio |
| `painel/core/CarregarPgAdm.php` | 47 | 2% | como veio |
| `painel/app/adms/Models/cursos/AdmsVerCursos.php` | 31 | 3% | como veio |
| `painel/app/adms/Models/disciplinas/AdmsGerarOferta.php` | 93 | 3% | como veio |
| `painel/app/adms/Models/helper/AdmsHierarquia.php` | 59 | 3% | como veio |
| `painel/app/adms/Models/helper/AdmsModuloInterno.php` | 40 | 3% | como veio |
| `painel/app/adms/Models/helper/AdmsSendPhpMailer.php` | 68 | 3% | como veio |
| `painel/app/adms/Models/historico/AdmsProgressoAluno.php` | 31 | 3% | como veio |
| `painel/app/adms/Models/horarioAula/AdmsListarHorarioAula.php` | 38 | 3% | como veio |
| `painel/app/adms/Models/matriz/AdmsEditarGrade.php` | 38 | 3% | como veio |
| `painel/app/adms/Models/matriz/AdmsListarMatriz.php` | 35 | 3% | como veio |
| `painel/app/adms/Models/matriz/AdmsOrdenarDisciplinasMatriz.php` | 39 | 3% | como veio |
| `painel/app/adms/Models/matriz/AdmsRemoverDisciplinaMatriz.php` | 31 | 3% | como veio |
| `painel/app/adms/Models/portalAlunos/AdmsEnturmacaoSugerida.php` | 73 | 3% | como veio |
| `painel/app/adms/Models/tcc/AdmsTcc.php` | 86 | 3% | como veio |
| `painel/app/adms/Models/usuarios/AdmsEditarUsuario.php` | 86 | 3% | como veio |
| `portal/app/adms/Models/helper/AdmsPainelPonte.php` | 36 | 3% | como veio |
| `portal/app/adms/Models/helper/AdmsSendPhpMailer.php` | 68 | 3% | como veio |
| `painel/app/adms/Controllers/Ajuda.php` | 55 | 4% | como veio |
| `painel/app/adms/Controllers/Buscar.php` | 23 | 4% | como veio |
| `painel/app/adms/Controllers/Historico.php` | 46 | 4% | como veio |
| `painel/app/adms/Controllers/Matriz.php` | 114 | 4% | como veio |
| `painel/app/adms/Models/academicos/AdmsPendenciaSexo.php` | 25 | 4% | como veio |
| `painel/app/adms/Models/disciplinas/AdmsOfertaMatriz.php` | 113 | 4% | como veio |
| `painel/app/adms/Models/disciplinas/AdmsVerPolivalentes.php` | 26 | 4% | como veio |
| `painel/app/adms/Models/helper/AdmsAtualizarDados.php` | 28 | 4% | como veio |
| `painel/app/adms/Models/helper/AdmsRead.php` | 45 | 4% | como veio |
| `painel/app/adms/Models/helper/AdmsSalvarLog.php` | 24 | 4% | como veio |
| `painel/app/adms/Models/helper/AdmsUsos.php` | 26 | 4% | como veio |
| `painel/app/adms/Models/horarioAula/AdmsVerHorarioAula.php` | 50 | 4% | como veio |
| `painel/app/adms/Models/matriz/AdmsAdicionarDisciplinaMatriz.php` | 49 | 4% | como veio |
| `painel/app/adms/Models/modulos/AdmsApagarModulo.php` | 28 | 4% | como veio |
| `painel/app/adms/Models/notas/AdmsOfertaInfo.php` | 53 | 4% | como veio |
| `painel/app/adms/Models/notas/AdmsRegraMedia.php` | 834 | 4% | como veio |
| `painel/app/adms/Models/polos/AdmsSalvarPolo.php` | 57 | 4% | como veio |
| `painel/app/adms/Models/relatorios/RelAlunosRisco.php` | 51 | 4% | como veio |
| `painel/app/adms/Models/relatorios/RelFrequenciaMinimo.php` | 74 | 4% | como veio |
| `painel/app/adms/Views/ajuda/guias/ano-letivo-passo-a-passo.php` | 26 | 4% | como veio |
| `painel/app/adms/Views/ajuda/guias/editar-modelo-de-historico.php` | 27 | 4% | como veio |
| `painel/app/adms/Views/ajuda/guias/portal-dentro-do-moodle.php` | 23 | 4% | como veio |
| `painel/app/adms/Views/ajuda/guias/regras-de-nota-e-frequencia.php` | 25 | 4% | como veio |
| `painel/app/adms/Views/ajuda/guias/tentativas-de-acesso.php` | 26 | 4% | como veio |
| `portal/app/adms/Models/AdmsBotao.php` | 27 | 4% | como veio |
| `portal/app/adms/Models/AdmsPaginas.php` | 24 | 4% | como veio |
| `portal/app/adms/Models/helper/AdmsNovidades.php` | 85 | 4% | como veio |
| `portal/app/adms/Models/helper/AdmsSalvarLog.php` | 24 | 4% | como veio |
| `portal/app/adms/Models/helper/AdmsSalvarLogs.php` | 26 | 4% | como veio |
| `painel/app/adms/Controllers/HorarioAula.php` | 64 | 5% | como veio |
| `painel/app/adms/Models/acesso/AdmsAtualizaSenha.php` | 62 | 5% | como veio |
| `painel/app/adms/Models/atividades/AdmsEditarAtividade.php` | 65 | 5% | como veio |
| `painel/app/adms/Models/cursos/AdmsIdentidadeCurso.php` | 37 | 5% | como veio |
| `painel/app/adms/Models/disciplinas/AdmsVerDisciplina.php` | 20 | 5% | como veio |
| `painel/app/adms/Models/faltas/AdmsCompensacoes.php` | 125 | 5% | como veio |
| `painel/app/adms/Models/faltas/AdmsImportarFaltas.php` | 73 | 5% | como veio |
| `painel/app/adms/Models/helper/AdmsApagarImg.php` | 19 | 5% | como veio |
| `painel/app/adms/Models/helper/AdmsCpf.php` | 21 | 5% | como veio |
| `painel/app/adms/Models/helper/AdmsInativar.php` | 40 | 5% | como veio |
| `painel/app/adms/Models/helper/AdmsLimiteTentativas.php` | 86 | 5% | como veio |
| `painel/app/adms/Models/matriz/AdmsCadastrarGrade.php` | 43 | 5% | como veio |
| `painel/app/adms/Models/matriz/AdmsSituacaoGrade.php` | 37 | 5% | como veio |
| `painel/app/adms/Models/modulos/AdmsListarModulos.php` | 40 | 5% | como veio |
| `painel/app/adms/Models/notas/AdmsAtualizarLista.php` | 38 | 5% | como veio |
| `painel/app/adms/Models/perfil/AdmsEditarPerfil.php` | 80 | 5% | como veio |
| `painel/app/adms/Views/ajuda/guias/cadastrar-aluno-e-enviar-link.php` | 20 | 5% | como veio |
| `painel/app/adms/Views/ajuda/guias/gerar-a-oferta-do-ano.php` | 19 | 5% | como veio |
| `painel/app/adms/Views/ajuda/guias/montar-a-matriz.php` | 21 | 5% | como veio |
| `painel/app/adms/Views/ajuda/guias/previa-do-historico.php` | 20 | 5% | como veio |
| `painel/app/adms/Views/ajuda/guias/vincular-e-enturmar.php` | 19 | 5% | como veio |
| `portal/app/adms/Models/academico/AdmsProgressoPortal.php` | 20 | 5% | como veio |
| `portal/app/adms/Models/helper/AdmsApagarImg.php` | 19 | 5% | como veio |
| `portal/app/adms/Models/helper/AdmsCentralGuias.php` | 99 | 5% | como veio |
| `portal/app/adms/Models/helper/AdmsLimiteTentativas.php` | 86 | 5% | como veio |
| `portal/app/adms/Models/helper/AdmsRead.php` | 43 | 5% | como veio |
| `painel/app/adms/Controllers/Disciplinas.php` | 309 | 6% | como veio |
| `painel/app/adms/Controllers/Parametros.php` | 32 | 6% | como veio |
| `painel/app/adms/Controllers/RegraMedia.php` | 211 | 6% | como veio |
| `painel/app/adms/Models/atividades/AdmsListarAtividades.php` | 48 | 6% | como veio |
| `painel/app/adms/Models/configEmail/AdmsEditarConfigEmail.php` | 52 | 6% | como veio |
| `painel/app/adms/Models/cursos/AdmsCadastrarCurso.php` | 47 | 6% | como veio |
| `painel/app/adms/Models/faltas/AdmsLancarFaltas.php` | 79 | 6% | como veio |
| `painel/app/adms/Models/helper/AdmsDeletar.php` | 33 | 6% | como veio |
| `painel/app/adms/Models/helper/AdmsSlug.php` | 17 | 6% | como veio |
| `painel/app/adms/Models/helper/AdmsTokenAcesso.php` | 125 | 6% | como veio |
| `painel/app/adms/Models/helperApi/AdmsAlunosDisciplinas.php` | 18 | 6% | como veio |
| `painel/app/adms/Models/historico/AdmsJsonCanonico.php` | 17 | 6% | como veio |
| `painel/app/adms/Models/horarioAula/AdmsCadastrarSala.php` | 86 | 6% | como veio |
| `painel/app/adms/Models/horarioAula/AdmsEditarHorarioAula.php` | 88 | 6% | como veio |
| `painel/app/adms/Models/materias/AdmsCadastrarMaterias.php` | 65 | 6% | como veio |
| `painel/app/adms/Models/usuarios/AdmsListarUsuarios.php` | 17 | 6% | como veio |
| `painel/app/adms/Views/ajuda/guias/compensar-faltas.php` | 18 | 6% | como veio |
| `painel/app/adms/Views/ajuda/guias/conferir-um-documento.php` | 17 | 6% | como veio |
| `painel/app/adms/Views/ajuda/guias/emitir-historico-final.php` | 17 | 6% | como veio |
| `painel/app/adms/Views/ajuda/guias/emitir-historico-parcial.php` | 17 | 6% | como veio |
| `painel/app/adms/Views/ajuda/guias/exportar-e-imprimir-listas.php` | 16 | 6% | como veio |
| `painel/app/adms/Views/ajuda/guias/relatorios-e-risco-conclusao.php` | 17 | 6% | como veio |
| `painel/app/adms/Views/ajuda/guias/situacao-da-grade.php` | 17 | 6% | como veio |
| `painel/app/adms/Views/ajuda/guias/usar-a-ajuda.php` | 18 | 6% | como veio |
| `portal/app/adms/Models/helper/AdmsDeletar.php` | 33 | 6% | como veio |
| `portal/app/adms/Models/helper/AdmsSlug.php` | 17 | 6% | como veio |
| `portal/app/adms/Models/helper/AdmsTokenAcesso.php` | 125 | 6% | como veio |
| `portal/app/adms/Models/helper/AdmsVersao.php` | 17 | 6% | como veio |
| `portal/app/adms/Views/ajuda/guias/conferir-um-documento.php` | 17 | 6% | como veio |
| `portal/app/adms/Views/ajuda/guias/ler-o-boletim.php` | 18 | 6% | como veio |
| `portal/app/adms/Views/ajuda/guias/usar-a-ajuda.php` | 18 | 6% | como veio |
| `painel/app/adms/Controllers/Cursos.php` | 175 | 7% | como veio |
| `painel/app/adms/Controllers/Faltas.php` | 75 | 7% | como veio |
| `painel/app/adms/Controllers/HistoricoModelos.php` | 82 | 7% | como veio |
| `painel/app/adms/Controllers/MinhasDisciplinas.php` | 28 | 7% | como veio |
| `painel/app/adms/Controllers/Modulos.php` | 76 | 7% | como veio |
| `painel/app/adms/Models/academicos/AdmsEditarUsuarioAcademico.php` | 134 | 7% | como veio |
| `painel/app/adms/Models/acesso/AdmsEsqueceuSenha.php` | 88 | 7% | como veio |
| `painel/app/adms/Models/buscar/AdmsListTurmaSerie.php` | 27 | 7% | como veio |
| `painel/app/adms/Models/configEmail/AdmsCadastrarConfigEmail.php` | 41 | 7% | como veio |
| `painel/app/adms/Models/historico/AdmsHistoricoVariaveis.php` | 186 | 7% | como veio |
| `painel/app/adms/Models/perfil/AdmsAtualizarDadosPerfil.php` | 43 | 7% | como veio |
| `painel/app/adms/Models/polos/AdmsListarPolos.php` | 29 | 7% | como veio |
| `painel/app/adms/Models/portalAlunos/AdmsPortalAlunosOrdenacao.php` | 60 | 7% | como veio |
| `painel/app/adms/Views/ajuda/guias/cancelar-uma-emissao.php` | 15 | 7% | como veio |
| `painel/app/adms/Views/ajuda/guias/como-usar-os-videos-da-ajuda.php` | 15 | 7% | como veio |
| `painel/app/adms/Views/ajuda/guias/emitir-atestado-de-matricula.php` | 27 | 7% | como veio |
| `painel/app/adms/Views/ajuda/guias/esqueci-a-senha.php` | 14 | 7% | como veio |
| `painel/app/adms/Views/ajuda/guias/relatorios-b-secretaria.php` | 14 | 7% | como veio |
| `painel/app/adms/Views/ajuda/guias/relatorios-d-frequencia.php` | 15 | 7% | como veio |
| `portal/app/adms/Controllers/Perfil.php` | 73 | 7% | como veio |
| `portal/app/adms/Models/helper/AdmsUi.php` | 123 | 7% | como veio |
| `portal/app/adms/Views/ajuda/guias/como-usar-os-videos-da-ajuda.php` | 15 | 7% | como veio |
| `portal/app/adms/Views/ajuda/guias/emitir-atestado-de-matricula.php` | 27 | 7% | como veio |
| `portal/app/adms/Views/ajuda/guias/esqueci-a-senha.php` | 14 | 7% | como veio |
| `painel/app/adms/Controllers/AnosLetivos.php` | 59 | 8% | como veio |
| `painel/app/adms/Controllers/AtosLegais.php` | 37 | 8% | como veio |
| `painel/app/adms/Controllers/Materias.php` | 112 | 8% | como veio |
| `painel/app/adms/Controllers/Polos.php` | 36 | 8% | como veio |
| `painel/app/adms/Controllers/SalasDeAula.php` | 107 | 8% | como veio |
| `painel/app/adms/Controllers/Tcc.php` | 12 | 8% | como veio |
| `painel/app/adms/Controllers/Usuarios.php` | 137 | 8% | como veio |
| `painel/app/adms/Models/academicos/AdmsEnviarLinkAcesso.php` | 40 | 8% | como veio |
| `painel/app/adms/Models/configEmail/AdmsVerConfigEmail.php` | 13 | 8% | como veio |
| `painel/app/adms/Models/cursos/AdmsApagarUsuario.php` | 37 | 8% | como veio |
| `painel/app/adms/Models/cursos/AdmsEditarUsuario.php` | 37 | 8% | como veio |
| `painel/app/adms/Models/disciplinas/AdmsAdicionarPolivalencia.php` | 51 | 8% | como veio |
| `painel/app/adms/Models/disciplinas/AdmsAdicionarProfessor.php` | 86 | 8% | como veio |
| `painel/app/adms/Models/disciplinas/AdmsApagarPolivalencia.php` | 37 | 8% | como veio |
| `painel/app/adms/Models/helper/AdmsTitulacao.php` | 25 | 8% | como veio |
| `painel/app/adms/Models/historico/AdmsHistoricoEmissao.php` | 320 | 8% | como veio |
| `painel/app/adms/Models/materias/AdmsVerMaterias.php` | 13 | 8% | como veio |
| `painel/app/adms/Models/notas/AdmsReabrirOferta.php` | 77 | 8% | como veio |
| `painel/app/adms/Models/relatorios/RelDesempenhoAluno.php` | 60 | 8% | como veio |
| `painel/app/adms/Models/salaAula/AdmsEditarSala.php` | 39 | 8% | como veio |
| `painel/app/adms/Models/salaAula/AdmsVisualizarSalaAula.php` | 13 | 8% | como veio |
| `painel/app/adms/Models/usuarios/AdmsApagarUsuario.php` | 38 | 8% | como veio |
| `painel/app/adms/Models/usuarios/AdmsEditarSenhaUsuario.php` | 48 | 8% | como veio |
| `painel/app/adms/Models/usuarios/AdmsVerUsuario.php` | 13 | 8% | como veio |
| `painel/app/adms/Views/ajuda/guias/concluir-o-curso-e-tcc.php` | 13 | 8% | como veio |
| `painel/app/adms/Views/ajuda/guias/email-smtp.php` | 13 | 8% | como veio |
| `painel/app/adms/Views/ajuda/guias/emitir-2a-via.php` | 13 | 8% | como veio |
| `painel/app/adms/Views/ajuda/guias/relatorios-f-tcc.php` | 12 | 8% | como veio |
| `painel/app/adms/Views/ajuda/guias/relatorios-g-documentos.php` | 12 | 8% | como veio |
| `painel/app/adms/Views/ajuda/guias/relatorios-h-auditoria.php` | 13 | 8% | como veio |
| `portal/app/adms/Controllers/Atestado.php` | 38 | 8% | como veio |
| `portal/app/adms/Models/academico/AdmsHorarioAluno.php` | 48 | 8% | como veio |
| `portal/app/adms/Views/ajuda/guias/meu-progresso.php` | 13 | 8% | como veio |
| `portal/app/adms/Views/ajuda/guias/notas-das-atividades.php` | 12 | 8% | como veio |
| `painel/app/adms/Controllers/BoletimAluno.php` | 11 | 9% | como veio |
| `painel/app/adms/Controllers/Perfil.php` | 88 | 9% | como veio |
| `painel/app/adms/Models/academicos/AdmsApagarUsuarioAcademico.php` | 34 | 9% | como veio |
| `painel/app/adms/Models/atividades/AdmsApagarAtividades.php` | 53 | 9% | como veio |
| `painel/app/adms/Models/buscar/AdmsListaBusca.php` | 22 | 9% | como veio |
| `painel/app/adms/Models/configEmail/AdmsListarConfigEmail.php` | 11 | 9% | como veio |
| `painel/app/adms/Models/cursos/AdmsCadastrarUsuario.php` | 68 | 9% | como veio |
| `painel/app/adms/Models/disciplinas/AdmsApagarDisciplina.php` | 45 | 9% | como veio |
| `painel/app/adms/Models/helper/AdmsNovidades.php` | 90 | 9% | como veio |
| `painel/app/adms/Models/helper/AdmsUpdate.php` | 44 | 9% | como veio |
| `painel/app/adms/Models/historico/AdmsHistoricoModelos.php` | 206 | 9% | como veio |
| `painel/app/adms/Models/instituicao/AdmsSalvarInstituicao.php` | 141 | 9% | como veio |
| `painel/app/adms/Models/materias/AdmsListarMaterias.php` | 11 | 9% | como veio |
| `painel/app/adms/Models/perfil/AdmsMeuPerfil.php` | 11 | 9% | como veio |
| `painel/app/adms/Models/relatorios/RelAlunosSituacao.php` | 54 | 9% | como veio |
| `painel/app/adms/Models/salaAula/AdmsApagarSala.php` | 45 | 9% | como veio |
| `painel/app/adms/Models/salaAula/AdmsCadastrarSala.php` | 33 | 9% | como veio |
| `painel/app/adms/Models/salaAula/AdmsListarSalaAula.php` | 11 | 9% | como veio |
| `painel/app/adms/Models/usuarios/AdmsCadastrarUsuarios.php` | 114 | 9% | como veio |
| `painel/app/adms/Views/ajuda/guias/encerrar-uma-disciplina.php` | 22 | 9% | como veio |
| `portal/app/adms/Controllers/Progresso.php` | 22 | 9% | como veio |
| `portal/app/adms/Models/helper/AdmsUpdate.php` | 44 | 9% | como veio |
| `portal/app/adms/Models/perfil/AdmsMeuPerfil.php` | 11 | 9% | como veio |
| `painel/app/adms/Controllers/Novidades.php` | 21 | 10% | em parte |
| `painel/app/adms/Models/academicos/AdmsEditarSenhaAcademico.php` | 41 | 10% | em parte |
| `painel/app/adms/Models/academicos/AdmsVerUsuarioAcademico.php` | 21 | 10% | em parte |
| `painel/app/adms/Models/configEmail/AdmsApagarConfigEmail.php` | 40 | 10% | em parte |
| `painel/app/adms/Models/cursos/AdmsApagarCurso.php` | 41 | 10% | em parte |
| `painel/app/adms/Models/disciplinas/AdmsCadastrarDisciplina.php` | 86 | 10% | em parte |
| `painel/app/adms/Models/disciplinas/AdmsEditarDisciplinas.php` | 69 | 10% | em parte |
| `painel/app/adms/Models/helper/AdmsCentralGuias.php` | 103 | 10% | em parte |
| `painel/app/adms/Models/helper/AdmsConn.php` | 21 | 10% | em parte |
| `painel/app/adms/Models/helper/AdmsValEmailUnico.php` | 29 | 10% | em parte |
| `painel/app/adms/Models/materias/AdmsApagarMaterias.php` | 41 | 10% | em parte |
| `painel/app/adms/Views/ajuda/guias/dados-da-instituicao-e-atos-legais.php` | 20 | 10% | em parte |
| `painel/app/adms/Views/ajuda/guias/inativar-curso-ou-materia.php` | 20 | 10% | em parte |
| `painel/app/adms/Views/ajuda/guias/relatorios-visao-geral.php` | 20 | 10% | em parte |
| `portal/app/adms/Controllers/Home.php` | 243 | 10% | em parte |
| `portal/app/adms/Models/helper/AdmsConn.php` | 21 | 10% | em parte |
| `portal/app/adms/Models/helper/AdmsValEmailUnico.php` | 29 | 10% | em parte |
| `painel/app/adms/Controllers/Academicos.php` | 236 | 11% | em parte |
| `painel/app/adms/Controllers/Atividades.php` | 234 | 11% | em parte |
| `painel/app/adms/Controllers/DadosInstituicao.php` | 19 | 11% | em parte |
| `painel/app/adms/Controllers/Medias.php` | 45 | 11% | em parte |
| `painel/app/adms/Controllers/TentativasAcesso.php` | 28 | 11% | em parte |
| `painel/app/adms/Models/acesso/AdmsLogin.php` | 73 | 11% | em parte |
| `painel/app/adms/Models/atividades/AdmsCadastrarAtividade.php` | 123 | 11% | em parte |
| `painel/app/adms/Models/cursos/AdmsEditarCurso.php` | 47 | 11% | em parte |
| `painel/app/adms/Models/cursos/AdmsListarCursos.php` | 19 | 11% | em parte |
| `painel/app/adms/Models/helper/AdmsCreate.php` | 38 | 11% | em parte |
| `painel/app/adms/Models/helper/AdmsValUsuarioLogin.php` | 27 | 11% | em parte |
| `painel/app/adms/Views/ajuda/guias/lancar-faltas.php` | 18 | 11% | em parte |
| `painel/app/adms/Views/ajuda/videos.php` | 57 | 11% | em parte |
| `painel/app/adms/Views/verificar/verificar.php` | 141 | 11% | em parte |
| `portal/app/adms/Models/helper/AdmsCreate.php` | 37 | 11% | em parte |
| `portal/app/adms/Models/helper/AdmsValUsuarioLogin.php` | 28 | 11% | em parte |
| `portal/app/adms/Views/ajuda/videos.php` | 57 | 11% | em parte |
| `painel/app/adms/Models/helper/AdmsValCampoVazio.php` | 17 | 12% | em parte |
| `painel/app/adms/Models/portalAlunos/AdmsConfirmarEnturmacao.php` | 112 | 12% | em parte |
| `painel/app/adms/Views/ajuda/guias/lancar-notas.php` | 26 | 12% | em parte |
| `painel/app/adms/Views/ajuda/guias/ver-e-imprimir-o-boletim.php` | 17 | 12% | em parte |
| `portal/app/adms/Models/helper/AdmsValCampoVazio.php` | 17 | 12% | em parte |
| `portal/app/adms/Views/ajuda/guia.php` | 50 | 12% | em parte |
| `portal/app/adms/Views/home/notas_atividades.php` | 151 | 12% | em parte |
| `painel/app/adms/Models/helper/AdmsValAcesso.php` | 38 | 13% | em parte |
| `painel/app/adms/Models/helper/AdmsValEmail.php` | 15 | 13% | em parte |
| `painel/app/adms/Models/relatorios/AdmsRelatorios.php` | 240 | 13% | em parte |
| `painel/app/adms/Views/ajuda/_video.php` | 71 | 13% | em parte |
| `painel/app/adms/Views/ajuda/guias/acompanhar-notas-e-resultado.php` | 16 | 13% | em parte |
| `painel/app/adms/Views/ajuda/guias/cadastrar-materias.php` | 15 | 13% | em parte |
| `painel/app/adms/Views/ajuda/guias/polos-e-cursos.php` | 16 | 13% | em parte |
| `painel/app/adms/Views/ajuda/guias/relatorios-c-desempenho.php` | 16 | 13% | em parte |
| `painel/app/adms/Views/relatorios/hub.php` | 76 | 13% | em parte |
| `portal/app/adms/Models/helper/AdmsValEmail.php` | 15 | 13% | em parte |
| `portal/app/adms/Views/ajuda/_video.php` | 71 | 13% | em parte |
| `painel/app/adms/Models/vinculo/AdmsEditarVinculo.php` | 76 | 14% | em parte |
| `portal/app/adms/Models/acesso/AdmsAtualizaSenha.php` | 29 | 14% | em parte |
| `portal/app/adms/Views/home/boletim_aluno.php` | 44 | 14% | em parte |
| `portal/core/Config.example.php` | 65 | 14% | em parte |
| `painel/app/adms/Controllers/HistoricoEmissoes.php` | 116 | 15% | em parte |
| `painel/app/adms/Models/parametros/AdmsParametrosInstituicao.php` | 187 | 15% | em parte |
| `painel/app/adms/Views/academicos/verBoletim.php` | 47 | 15% | em parte |
| `painel/app/adms/Views/materias/listMaterias.php` | 91 | 15% | em parte |
| `portal/app/adms/Models/acesso/AdmsIdentificaAluno.php` | 34 | 15% | em parte |
| `portal/app/adms/Models/perfil/AdmsEditarPerfil.php` | 88 | 15% | em parte |
| `portal/app/adms/Views/ajuda/guias/_indice.php` | 631 | 15% | em parte |
| `painel/app/adms/Views/ajuda/guias/configurar-a-regra-de-media.php` | 43 | 16% | em parte |
| `painel/app/adms/Views/ajuda/guias/entrar-no-painel.php` | 19 | 16% | em parte |
| `painel/core/Config.example.php` | 69 | 16% | em parte |
| `portal/app/adms/Views/home/horario_aluno.php` | 89 | 16% | em parte |
| `portal/app/adms/Views/novidades/aviso.php` | 25 | 16% | em parte |
| `painel/app/adms/Controllers/Login.php` | 84 | 17% | em parte |
| `painel/app/adms/Models/academicos/AdmsListarAcademico.php` | 36 | 17% | em parte |
| `painel/app/adms/Models/perfil/AdmsEditarSenhaPerfil.php` | 42 | 17% | em parte |
| `painel/app/adms/Views/cursos/listCursos.php` | 135 | 17% | em parte |
| `painel/app/adms/Views/historico/previa.php` | 127 | 17% | em parte |
| `painel/app/adms/Models/atividades/AdmsUploadNotas.php` | 66 | 18% | em parte |
| `painel/app/adms/Views/ajuda/guias/primeiro-acesso-do-aluno.php` | 17 | 18% | em parte |
| `painel/app/adms/Views/atividades/uploadNotas.php` | 88 | 18% | em parte |
| `painel/app/adms/Views/atosLegais/listAtos.php` | 45 | 18% | em parte |
| `portal/app/adms/Views/ajuda/guias/primeiro-acesso-do-aluno.php` | 17 | 18% | em parte |
| `painel/app/adms/Views/ajuda/guias/relatorios-a-painel.php` | 16 | 19% | em parte |
| `painel/app/adms/Views/anosLetivos/listAnos.php` | 75 | 19% | em parte |
| `painel/app/adms/Views/disciplinas/encerrarOferta.php` | 103 | 19% | em parte |
| `painel/app/adms/Views/disciplinas/listDisciplinas.php` | 145 | 19% | em parte |
| `painel/app/adms/Models/AdmsBotao.php` | 35 | 20% | em parte |
| `painel/app/adms/Models/helper/AdmsUi.php` | 137 | 20% | em parte |
| `painel/app/adms/Models/notas/AdmsEncerrarOferta.php` | 132 | 20% | em parte |
| `painel/app/adms/Models/relatorios/RelMapaNotas.php` | 122 | 20% | em parte |
| `painel/app/adms/Views/academicos/cadAcademico.php` | 30 | 20% | em parte |
| `painel/app/adms/Views/ajuda/guias/_indice.php` | 3963 | 20% | em parte |
| `painel/app/adms/Views/atividades/_componentePeso.php` | 44 | 20% | em parte |
| `painel/app/adms/Views/cursos/verCurso.php` | 208 | 20% | em parte |
| `painel/app/adms/Views/horarioAula/listHorarioAula.php` | 140 | 20% | em parte |
| `painel/app/adms/Views/relatorios/relatorio.php` | 216 | 20% | em parte |
| `painel/app/adms/Views/salaAula/listSalas.php` | 81 | 20% | em parte |
| `painel/app/adms/Views/usuarios/listUsuarios.php` | 87 | 20% | em parte |
| `portal/app/adms/Models/perfil/AdmsEditarSenhaPerfil.php` | 45 | 20% | em parte |
| `painel/app/adms/Models/academicos/AdmsCadastrarUsuarioAcademico.php` | 135 | 21% | em parte |
| `painel/app/adms/Models/relatorios/AdmsRelatorioBase.php` | 84 | 21% | em parte |
| `painel/app/adms/Views/academicos/listAcademicos.php` | 107 | 21% | em parte |
| `painel/app/adms/Views/academicos/listPendenciaSexo.php` | 48 | 21% | em parte |
| `painel/app/adms/Views/ajuda/guias/completar-a-oferta.php` | 14 | 21% | em parte |
| `painel/app/adms/Views/ajuda/indice.php` | 68 | 21% | em parte |
| `painel/app/adms/Views/materias/verMaterias.php` | 43 | 21% | em parte |
| `painel/app/adms/Views/novidades/aviso.php` | 28 | 21% | em parte |
| `painel/app/adms/Views/novidades/listar.php` | 28 | 21% | em parte |
| `painel/app/adms/Views/regraMedia/regra.php` | 307 | 21% | em parte |
| `portal/app/adms/Controllers/Login.php` | 92 | 21% | em parte |
| `portal/app/adms/Models/AdmsMenus.php` | 24 | 21% | em parte |
| `portal/app/adms/Views/ajuda/indice.php` | 68 | 21% | em parte |
| `portal/app/adms/Views/novidades/listar.php` | 28 | 21% | em parte |
| `painel/app/adms/Models/helper/AdmsInstituicao.php` | 90 | 22% | em parte |
| `painel/app/adms/Views/ajuda/guias/usuarios-e-niveis.php` | 18 | 22% | em parte |
| `painel/app/adms/Views/matriz/listMatriz.php` | 74 | 22% | em parte |
| `portal/app/adms/Controllers/Erro.php` | 9 | 22% | em parte |
| `portal/app/adms/Models/helper/AdmsInstituicao.php` | 90 | 22% | em parte |
| `painel/app/adms/Views/configEmail/listConfigEmail.php` | 81 | 23% | em parte |
| `painel/app/adms/Views/faltas/visFaltas.php` | 149 | 23% | em parte |
| `painel/app/adms/Views/portalAlunos/listAlunos.php` | 162 | 23% | em parte |
| `portal/app/adms/Models/helper/AdmsParametro.php` | 98 | 23% | em parte |
| `portal/app/adms/Views/atestado/atestado.php` | 48 | 23% | em parte |
| `painel/app/adms/Controllers/Relatorios.php` | 147 | 24% | em parte |
| `painel/app/adms/Views/ajuda/guias/minhas-disciplinas-e-atividades.php` | 21 | 24% | em parte |
| `painel/app/adms/Views/disciplinas/editDisciplinas.php` | 50 | 24% | em parte |
| `painel/app/adms/Views/matriz/formGrade.php` | 58 | 24% | em parte |
| `painel/app/adms/Views/matriz/verMatriz.php` | 261 | 24% | em parte |
| `painel/app/adms/Views/modulos/listModulos.php` | 85 | 24% | em parte |
| `painel/app/adms/Views/polos/listPolos.php` | 41 | 24% | em parte |
| `painel/app/adms/Views/regraMedia/aplicar.php` | 72 | 24% | em parte |
| `painel/app/adms/Views/salaAula/verSala.php` | 45 | 24% | em parte |
| `painel/app/adms/Controllers/ConfiguracaoEmail.php` | 126 | 25% | em parte |
| `painel/app/adms/Views/academicos/editAcademico.php` | 63 | 25% | em parte |
| `painel/app/adms/Views/disciplinas/verPolivalente.php` | 176 | 25% | em parte |
| `painel/app/adms/Views/medias/visFinal.php` | 92 | 25% | em parte |
| `painel/app/adms/Views/regraMedia/oferta.php` | 61 | 25% | em parte |
| `painel/app/adms/Views/usuarios/editSenhaUsuario.php` | 51 | 25% | em parte |
| `portal/core/ConfigController.php` | 122 | 25% | em parte |
| `painel/app/adms/Models/atividades/AdmsEditNotas.php` | 77 | 26% | em parte |
| `painel/app/adms/Models/helper/AdmsUploadImgRed.php` | 54 | 26% | em parte |
| `painel/app/adms/Views/ajuda/guia.php` | 61 | 26% | em parte |
| `painel/app/adms/Views/atividades/listAtividades.php` | 136 | 26% | em parte |
| `painel/app/adms/Views/configEmail/verConfigEmail.php` | 47 | 26% | em parte |
| `painel/app/adms/Views/disciplinas/cadDisciplinas.php` | 53 | 26% | em parte |
| `painel/app/adms/Views/historicoEmissoes/verEmissao.php` | 68 | 26% | em parte |
| `painel/app/adms/Views/regraMedia/lista.php` | 70 | 26% | em parte |
| `portal/app/adms/Models/helper/AdmsUploadImgRed.php` | 54 | 26% | em parte |
| `painel/app/adms/Controllers/PortalAlunos.php` | 103 | 27% | em parte |
| `painel/app/adms/Models/vinculo/AdmsVincularAluno.php` | 97 | 27% | em parte |
| `painel/app/adms/Views/anosLetivos/formAno.php` | 55 | 27% | em parte |
| `painel/app/adms/Views/disciplinas/listMinhasDisciplinas.php` | 75 | 27% | em parte |
| `painel/app/adms/Models/notas/AdmsBoletimCurso.php` | 355 | 28% | em parte |
| `painel/app/adms/Models/relatorios/AdmsRelatorioFonte.php` | 345 | 28% | em parte |
| `painel/app/adms/Views/horarioAula/verHorarioAula.php` | 46 | 28% | em parte |
| `portal/app/adms/Models/notas/AdmsBoletimCurso.php` | 355 | 28% | em parte |
| `painel/app/adms/Models/disciplinas/AdmsListarMinhasDisciplinas.php` | 48 | 29% | em parte |
| `painel/app/adms/Models/portalAlunos/AdmsListaPortalAlunos.php` | 44 | 30% | em parte |
| `painel/app/adms/Views/historicoModelos/formModelo.php` | 215 | 30% | em parte |
| `painel/app/adms/Views/materias/cadMaterias.php` | 53 | 30% | em parte |
| `painel/app/adms/Views/materias/editMaterias.php` | 64 | 30% | em parte |
| `painel/core/Seguranca.php` | 124 | 30% | em parte |
| `portal/app/adms/Views/home/aluno.php` | 79 | 30% | em parte |
| `portal/app/adms/Views/progresso/progresso.php` | 56 | 30% | em parte |
| `portal/core/Seguranca.php` | 124 | 30% | em parte |
| `painel/app/adms/Controllers/Chamada.php` | 89 | 31% | em parte |
| `painel/app/adms/Views/ajuda/guias/reabrir-uma-disciplina.php` | 13 | 31% | em parte |
| `portal/app/adms/Views/perfil/meuPerfil.php` | 67 | 31% | em parte |
| `painel/app/adms/Views/ajuda/guias/fazer-a-chamada.php` | 25 | 32% | em parte |
| `painel/app/adms/Views/modulos/formModulo.php` | 77 | 32% | em parte |
| `painel/app/adms/Models/faltas/AdmsChamada.php` | 353 | 33% | em parte |
| `painel/app/adms/Models/helper/AdmsParametro.php` | 112 | 33% | em parte |
| `painel/app/adms/Views/acesso/definirSenha.php` | 27 | 33% | em parte |
| `painel/app/adms/Views/historicoModelos/listModelos.php` | 39 | 33% | em parte |
| `painel/app/adms/Views/tentativasAcesso/listar.php` | 42 | 33% | em parte |
| `painel/core/ConfigController.php` | 138 | 33% | em parte |
| `portal/app/adms/Views/acesso/definirSenha.php` | 27 | 33% | em parte |
| `portal/app/adms/Views/ajuda/guias/entrar-no-portal.php` | 15 | 33% | em parte |
| `portal/app/adms/Views/perfil/editSenhaPerfil.php` | 54 | 33% | em parte |
| `painel/app/adms/Views/academicos/vinculoAluno.php` | 211 | 34% | em parte |
| `painel/app/adms/Views/historicoEmissoes/emitir.php` | 82 | 34% | em parte |
| `painel/app/adms/Views/historicoEmissoes/listEmissoes.php` | 56 | 34% | em parte |
| `painel/app/adms/Models/academicos/AdmsGerarBoletimPdf.php` | 78 | 35% | em parte |
| `painel/app/adms/Models/materias/AdmsEditarMaterias.php` | 68 | 35% | em parte |
| `painel/app/adms/Views/atividades/cadAtividades.php` | 85 | 36% | em parte |
| `painel/app/adms/Views/cursos/editCursos.php` | 107 | 36% | em parte |
| `painel/app/adms/Views/perfil/atualizarDados.php` | 77 | 36% | em parte |
| `painel/app/adms/Views/usuarios/cadUsuario.php` | 110 | 36% | em parte |
| `painel/app/adms/Views/academicos/editSenhaAcademico.php` | 71 | 37% | em parte |
| `painel/app/adms/Views/cursos/cadCursos.php` | 97 | 37% | em parte |
| `painel/app/adms/Views/usuarios/verUsuario.php` | 115 | 37% | em parte |
| `painel/app/adms/Controllers/Home.php` | 32 | 38% | em parte |
| `painel/app/adms/Views/academicos/historicosAluno.php` | 39 | 38% | em parte |
| `painel/app/adms/Views/atividades/editAtividades.php` | 95 | 38% | em parte |
| `painel/app/adms/Views/disciplinas/camposMatriz.php` | 68 | 38% | em parte |
| `painel/app/adms/Views/disciplinas/gerarOferta.php` | 84 | 38% | em parte |
| `painel/app/adms/Views/relatorios/parametros.php` | 40 | 38% | em parte |
| `painel/app/adms/Views/salaAula/editSala.php` | 74 | 38% | em parte |
| `painel/app/adms/Views/academicos/verAcademico.php` | 142 | 39% | em parte |
| `painel/app/adms/Views/perfil/editPerfil.php` | 130 | 39% | em parte |
| `painel/app/adms/Views/polos/formPolo.php` | 46 | 39% | em parte |
| `portal/app/adms/Views/perfil/editPerfil.php` | 92 | 39% | em parte |
| `painel/app/adms/Models/relatorios/RelReaberturas.php` | 77 | 40% | em parte |
| `painel/app/adms/Views/configEmail/editConfigEmail.php` | 87 | 40% | em parte |
| `painel/app/adms/Views/cursos/_identidadeCurso.php` | 40 | 40% | em parte |
| `painel/app/adms/Views/perfil/meuPerfil.php` | 108 | 40% | em parte |
| `painel/app/adms/Views/salaAula/cadSala.php` | 65 | 40% | em parte |
| `portal/app/adms/Models/academico/AdmsNotasAtividades.php` | 78 | 40% | em parte |
| `portal/app/adms/Models/acesso/AdmsEsqueceuSenha.php` | 57 | 40% | em parte |
| `portal/app/adms/Models/notas/AdmsCalculoNotas.php` | 511 | 40% | em parte |
| `painel/app/adms/Models/notas/AdmsCalculoNotas.php` | 524 | 41% | em parte |
| `painel/app/adms/Views/instituicao/formInstituicao.php` | 133 | 41% | em parte |
| `painel/app/adms/Views/portalAlunos/visAluno.php` | 79 | 41% | em parte |
| `painel/app/adms/Views/usuarios/editUsuario.php` | 138 | 41% | em parte |
| `painel/app/adms/Models/academicos/AdmsValidaAluno.php` | 80 | 43% | em parte |
| `painel/app/adms/Views/chamada/listChamada.php` | 83 | 43% | em parte |
| `painel/app/adms/Views/parametros/listParametros.php` | 171 | 44% | em parte |
| `painel/app/adms/Views/academicos/formAluno.php` | 155 | 45% | em parte |
| `painel/app/adms/Views/acesso/atualizaSenha.php` | 33 | 45% | em parte |
| `painel/app/adms/Views/home/home.php` | 98 | 45% | em parte |
| `painel/app/adms/Views/configEmail/cadConfigEmail.php` | 72 | 46% | em parte |
| `painel/app/adms/Views/acesso/esqueceuSenha.php` | 34 | 47% | em parte |
| `painel/app/adms/Views/disciplinas/verDisciplinas.php` | 127 | 50% | mais da metade reescrita |
| `painel/app/adms/Views/atosLegais/formAto.php` | 47 | 51% | mais da metade reescrita |
| `portal/app/adms/Views/acesso/atualizaSenha.php` | 39 | 51% | mais da metade reescrita |
| `portal/app/adms/Views/acesso/login.php` | 73 | 51% | mais da metade reescrita |
| `painel/app/adms/Views/acesso/login.php` | 42 | 52% | mais da metade reescrita |
| `portal/app/adms/Models/acesso/AdmsLogin.php` | 153 | 52% | mais da metade reescrita |
| `painel/app/adms/Models/AdmsMenus.php` | 47 | 53% | mais da metade reescrita |
| `painel/app/adms/Views/chamada/encontro.php` | 151 | 53% | mais da metade reescrita |
| `portal/app/adms/Views/acesso/esqueceuSenha.php` | 40 | 55% | mais da metade reescrita |
| `painel/core/ConfigView.php` | 39 | 62% | mais da metade reescrita |
| `portal/core/ConfigView.php` | 39 | 62% | mais da metade reescrita |
| `painel/app/adms/Views/atividades/visAtividades.php` | 197 | 65% | mais da metade reescrita |
| `painel/app/adms/Models/AdmsPaginas.php` | 113 | 73% | mais da metade reescrita |
| `painel/app/adms/Models/helper/AdmsValSenha.php` | 32 | 78% | mais da metade reescrita |
| `portal/app/adms/Models/helper/AdmsValSenha.php` | 32 | 78% | mais da metade reescrita |
| `portal/app/adms/Views/erro/pgErro.php` | 17 | 88% | mais da metade reescrita |
| `painel/app/adms/Models/helper/AdmsNivel.php` | 90 | 92% | reescrito |

## Os que saíram

- `painel/app/adms/Controllers/AcessoAcademico.php`
- `painel/app/adms/Controllers/Liberar.php`
- `painel/app/adms/Controllers/LiberarAcademico.php`
- `painel/app/adms/Controllers/Menu.php`
- `painel/app/adms/Controllers/NivelAcesso.php`
- `painel/app/adms/Controllers/Paginas.php`
- `painel/app/adms/Controllers/PaginasAcademico.php`
- `painel/app/adms/Models/acessoAcademico/AdmsApagarAcessoAcademico.php`
- `painel/app/adms/Models/acessoAcademico/AdmsCadastrarAcessoAcademico.php`
- `painel/app/adms/Models/acessoAcademico/AdmsEditarAcessoAcademico.php`
- `painel/app/adms/Models/acessoAcademico/AdmsEditarItemMenuAcademico.php`
- `painel/app/adms/Models/acessoAcademico/AdmsListarAcessoAcademico.php`
- `painel/app/adms/Models/acessoAcademico/AdmsListarPermissaoPerfil.php`
- `painel/app/adms/Models/acessoAcademico/AdmsVerAcessoAcademico.php`
- `painel/app/adms/Models/liberar/AdmsLibDropdown.php`
- `painel/app/adms/Models/liberar/AdmsLibMenu.php`
- `painel/app/adms/Models/liberar/AdmsLibPermissao.php`
- `painel/app/adms/Models/liberarAcademico/AdmsLibDropdownAcad.php`
- `painel/app/adms/Models/liberarAcademico/AdmsLibMenuAcad.php`
- `painel/app/adms/Models/liberarAcademico/AdmsLibPermissaoAcad.php`
- `painel/app/adms/Models/menus/AdmsApagarMenu.php`
- `painel/app/adms/Models/menus/AdmsCadastrarMenu.php`
- `painel/app/adms/Models/menus/AdmsEditarMenus.php`
- `painel/app/adms/Models/menus/AdmsEditarOrdenacao.php`
- `painel/app/adms/Models/menus/AdmsListarMenus.php`
- `painel/app/adms/Models/menus/AdmsVerMenus.php`
- `painel/app/adms/Models/nivelAcesso/AdmsApagarNivel.php`
- `painel/app/adms/Models/nivelAcesso/AdmsAtualizarPermissao.php`
- `painel/app/adms/Models/nivelAcesso/AdmsCadastrarNivelAcesso.php`
- `painel/app/adms/Models/nivelAcesso/AdmsEditarItemMenu.php`
- `painel/app/adms/Models/nivelAcesso/AdmsEditarNivelAcesso.php`
- `painel/app/adms/Models/nivelAcesso/AdmsEditarOrdenacao.php`
- `painel/app/adms/Models/nivelAcesso/AdmsListarNivelAcesso.php`
- `painel/app/adms/Models/nivelAcesso/AdmsListarPermissao.php`
- `painel/app/adms/Models/nivelAcesso/AdmsVerNivelAcesso.php`
- `painel/app/adms/Models/paginas/AdmsCadastrarPaginas.php`
- `painel/app/adms/Models/paginas/AdmsEditarMenu.php`
- `painel/app/adms/Models/paginas/AdmsEditarPaginas.php`
- `painel/app/adms/Models/paginas/AdmsListarPaginas.php`
- `painel/app/adms/Models/paginas/AdmsVerPaginas.php`
- `painel/app/adms/Models/pgAcademico/AdmsCadastrarPgAcademico.php`
- `painel/app/adms/Models/pgAcademico/AdmsEditarMenuPgAcademico.php`
- `painel/app/adms/Models/pgAcademico/AdmsEditarPgAcademico.php`
- `painel/app/adms/Models/pgAcademico/AdmsListarPgAcademico.php`
- `painel/app/adms/Models/pgAcademico/AdmsVerPgAcademico.php`
- `painel/app/adms/Views/acessoAcademico/editAcessoAcad.php`
- `painel/app/adms/Views/acessoAcademico/listAcessoAcad.php`
- `painel/app/adms/Views/acessoAcademico/verAcessoAcad.php`
- `painel/app/adms/Views/include/cabecalho.php`
- `painel/app/adms/Views/include/cabecalho_ajax.php`
- `painel/app/adms/Views/include/cabecalho_login.php`
- `painel/app/adms/Views/include/footer.php`
- `painel/app/adms/Views/include/head_menu.php`
- `painel/app/adms/Views/include/menu.php`
- `painel/app/adms/Views/include/rodapejs.php`
- `painel/app/adms/Views/include/rodapejs_editint.php`
- `painel/app/adms/Views/include/rodapejs_login.php`
- `painel/app/adms/Views/menu/cadMenus.php`
- `painel/app/adms/Views/menu/editMenus.php`
- `painel/app/adms/Views/menu/listMenu.php`
- `painel/app/adms/Views/menu/verMenu.php`
- `painel/app/adms/Views/nivelAcesso/editNivelAcesso.php`
- `painel/app/adms/Views/nivelAcesso/listNivelAcesso.php`
- `painel/app/adms/Views/nivelAcesso/listPermissao.php`
- `painel/app/adms/Views/nivelAcesso/verNivelAcesso.php`
- `painel/app/adms/Views/paginas/cadPages.php`
- `painel/app/adms/Views/paginas/editPages.php`
- `painel/app/adms/Views/paginas/listPages.php`
- `painel/app/adms/Views/paginas/verPages.php`
- `painel/app/adms/Views/pgAcademicos/cadpgAcademicos.php`
- `painel/app/adms/Views/pgAcademicos/editpgAcademicos.php`
- `painel/app/adms/Views/pgAcademicos/listpgAcademicos.php`
- `painel/app/adms/Views/pgAcademicos/verpgAcademicos.php`
- `portal/app/adms/Views/include/cabecalho.php`
- `portal/app/adms/Views/include/cabecalho_login.php`
- `portal/app/adms/Views/include/footer.php`
- `portal/app/adms/Views/include/head_menu.php`
- `portal/app/adms/Views/include/menu.php`
- `portal/app/adms/Views/include/rodapejs.php`
- `portal/app/adms/Views/include/rodapejs_login.php`
