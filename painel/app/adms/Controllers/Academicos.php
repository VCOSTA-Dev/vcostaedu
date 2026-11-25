<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class Academicos
{
    private $pagina = 46;
    private $dados;
    private $dadosForm;
    private $dadosId;

    /** Lista e busca de alunos por nome, RA, CPF ou e-mail (?busca=). */
    public function listar($dadosId = null)
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $botao = ['vis_user' => ['menu_controller' => 'academicos', 'menu_metodo' => 'visualizar'],
        'cad_user' => ['menu_controller' => 'academicos', 'menu_metodo' => 'cadastrar'],
        'boletim' => ['menu_controller' => 'academicos', 'menu_metodo' => 'boletim'],
        'emitir_doc' => ['menu_controller' => 'historico-emissoes', 'menu_metodo' => 'emitir'], // E6 (D-373): o atestado
        'edit_user' => ['menu_controller' => 'academicos', 'menu_metodo' => 'editar'],
        'del_user' => ['menu_controller' => 'academicos', 'menu_metodo' => 'apagar'],];
        $visBotao = new \App\adms\Models\AdmsBotao();
        $this->dados['botao'] = $visBotao->valBotao($botao);

        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $busca = (string) (filter_input(INPUT_GET, 'busca', FILTER_DEFAULT) ?? '');
        $listUser = new \App\adms\Models\academicos\AdmsListarAcademico();
        $this->dados['listUsers'] = $listUser->listarAlunos($busca);

        $carregarView = new \Core\ConfigView('adms/Views/academicos/listAcademicos', $this->dados);
        $carregarView->renderizar('tabler');
    }

    /** E1b (D-356): alunos com o sexo do padrão antigo ("Outros" nunca escolhido na tela). Rota 416. */
    public function pendenciaSexo($dadosId = null)
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $this->dados['botao'] = (new \App\adms\Models\AdmsBotao())->valBotao(['edit_user' => ['menu_controller' => 'academicos', 'menu_metodo' => 'editar']]);
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['pendentes'] = \App\adms\Models\academicos\AdmsPendenciaSexo::listar();
        (new \Core\ConfigView('adms/Views/academicos/listPendenciaSexo', $this->dados))->renderizar('tabler');
    }

    public function cadastrar($dadosId = null)
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!empty($this->dadosForm['CadUsuarioAcad'])) {
            unset($this->dadosForm['CadUsuarioAcad']);
            $cadUser = new \App\adms\Models\academicos\AdmsCadastrarUsuarioAcademico();
            $cadUser->cadUserAcademico($this->dadosForm);
            if ($cadUser->getResultado()) {
                $urlDestino = URLADM . 'academicos/visualizar/' . (int) $cadUser->getIdUsuario();
                header("Location: $urlDestino");
            } else {
                $this->dados['form'] = $this->dadosForm;
                /* D-102: na escola, o número já usado volta como o seguinte da sequência (a mensagem diz) */
                $ra = trim((string) ($this->dadosForm['rm'] ?? ''));
                if (\App\adms\Models\helper\AdmsParametro::naEscola() && $ra !== '') {
                    $l = new \App\adms\Models\helper\AdmsRead();
                    $l->fullRead('SELECT id FROM col_usuarios WHERE rm = :r LIMIT :limit', 'r=' . $ra . '&limit=1');
                    if ($l->getResultado()) {
                        $this->dados['form']['rm'] = \App\adms\Models\academicos\AdmsValidaAluno::proximoNumero();
                    }
                }
                $this->cadastrarView();
            }
        } else {
            $this->cadastrarView();
        }
    }

    private function cadastrarView()
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $listarSelect = new \App\adms\Models\academicos\AdmsCadastrarUsuarioAcademico();
        $this->dados['select'] = $listarSelect->listarCadastro();

        $carregarView = new \Core\ConfigView("adms/Views/academicos/cadAcademico", $this->dados);
        $carregarView->renderizar('tabler');
    }

    public function visualizar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $tab = filter_input(INPUT_GET, "tab", FILTER_DEFAULT);
        if (!empty($this->dadosId)) {
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['edit_user' => ['menu_controller' => 'academicos', 'menu_metodo' => 'editar'],
            'enviar_link' => ['menu_controller' => 'academicos', 'menu_metodo' => 'enviar-link'],
            'edit_senha' => ['menu_controller' => 'academicos', 'menu_metodo' => 'editar-senha'],
            'vincular' => ['menu_controller' => 'academicos', 'menu_metodo' => 'vincular'],
            'editar_vinculo' => ['menu_controller' => 'academicos', 'menu_metodo' => 'editar-vinculo'],
            'transferencia' => ['menu_controller' => 'escola-transferencia', 'menu_metodo' => 'index'], // D-102 (A2)
            'posicionar' => ['menu_controller' => 'academicos', 'menu_metodo' => 'posicionar-modulo'],
            'enturmar' => ['menu_controller' => 'portal-alunos', 'menu_metodo' => 'visualizar'],
            'tcc' => ['menu_controller' => 'tcc', 'menu_metodo' => 'salvar'], // E6a
            'previa' => ['menu_controller' => 'historico', 'menu_metodo' => 'previa'],
            'emitir' => ['menu_controller' => 'historico-emissoes', 'menu_metodo' => 'emitir'], // E6b
            'turma' => ['menu_controller' => 'turmas', 'menu_metodo' => 'ver'],]; // 2.2 (D-88): a turma do ano, na ficha da escola
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);

            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();
            $this->dados['tabActive'] = (isset($tab) && !empty($tab) ? $tab : 'tabeInfo');

            /* E3b: vínculo do aluno (matrícula no curso + posição por ano/módulo). */
            $vinculos = new \App\adms\Models\vinculo\AdmsListarVinculos();
            $this->dados['vinculos'] = $vinculos->doAluno($this->dadosId);
            $this->dados['opcoesVinculo'] = $vinculos->opcoes();
            $this->dados['semModulos'] = \App\adms\Models\helper\AdmsModuloInterno::ativo(); // E8c (D-268)

            /* E6b: históricos emitidos do aluno (aba Históricos) */
            $this->dados['historicos'] = !empty($this->dados['botao']['emitir']) ? \App\adms\Models\historico\AdmsHistoricoEmissao::listar(['aluno' => $this->dadosId]) : [];

            $visUser = new \App\adms\Models\academicos\AdmsVerUsuarioAcademico();
            $this->dados['info_user'] = $visUser->visUsuario($this->dadosId);

            $carregarView = new \Core\ConfigView("adms/Views/academicos/verAcademico", $this->dados);
            $carregarView->renderizar('tabler');
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro:Usuário não encontrado!</div>";
            $urlDestino = URLADM . 'academicos/listar';
            header("Location: $urlDestino");
        }
    }

    public function editar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!empty($this->dadosId)) {
            $this->editPrev();
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Usuário acadêmico não encontrado!</div>";
            $urlDestino = URLADM . 'academicos/listar';
            header("Location: $urlDestino");
        }
    }

    private function editPrev()
    {
        if (!empty($this->dadosForm['EditUsuarioAcad'])) {
            unset($this->dadosForm['EditUsuarioAcad']);
            $this->dadosForm['imagem_nova'] = ($_FILES['imagem_nova'] ?? null);
            $altPg = new \App\adms\Models\academicos\AdmsEditarUsuarioAcademico();
            $altPg->editUsuarios($this->dadosForm);
            if ($altPg->getResultado()) {
                $urlDestino = URLADM . 'academicos/visualizar/' . $this->dadosId;
                header("Location: $urlDestino");
            } else {
                $this->dados['form'] = $this->dadosForm;
                $this->editView();
            }
        } else {
            $visUs = new \App\adms\Models\academicos\AdmsEditarUsuarioAcademico();
            $this->dados['form'] = $visUs->verUsuario($this->dadosId);
            $this->editView();
        }
    }

    private function editView()
    {
        if (!empty($this->dados['form'])) {
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['vis_user' => ['menu_controller' => 'academicos', 'menu_metodo' => 'visualizar'],
            'edit_senha' => ['menu_controller' => 'academicos', 'menu_metodo' => 'editar-senha'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);

            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();

            $visUser = new \App\adms\Models\academicos\AdmsVerUsuarioAcademico();
            $this->dados['info_user'] = $visUser->visUsuario($this->dadosId);

            $listarSelect = new \App\adms\Models\academicos\AdmsCadastrarUsuarioAcademico();
            $this->dados['select'] = $listarSelect->listarCadastro();

            $carregarView = new \Core\ConfigView("adms/Views/academicos/editAcademico", $this->dados);
            $carregarView->renderizar('tabler');
        } else {

            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro:Usuário acadêmico não encontrado!</div>";
            $urlDestino = URLADM . 'academicos/listar';
            header("Location: $urlDestino");
        }
    }

    public function apagar()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!empty($this->dadosForm['ApagarUsuarioAcad'])) {
            unset($this->dadosForm['ApagarUsuarioAcad']);
            $apgUsuarioAcad = new \App\adms\Models\academicos\AdmsApagarUsuarioAcademico();
            $apgUsuarioAcad->delUsuAcad($this->dadosForm);
            if ($apgUsuarioAcad->getResultado()) {
                $urlDestino = URLADM . 'academicos/listar';
                header("Location: $urlDestino");
            } else {
                $urlDestino = URLADM . 'academicos/listar';
                header("Location: $urlDestino");
            }
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro:Usuário não encontrado!</div>";
            $urlDestino = URLADM . 'academicos/listar';
            header("Location: $urlDestino");
        }
    }

    public function editarSenha($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!empty($this->dadosId)) {
            $this->editarSenhaPrev();
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro:Usuário acadêmico não encontrado!</div>";
            $urlDestino = URLADM . 'academicos/listar';
            header("Location: $urlDestino");
        }
    }

    private function editarSenhaPrev()
    {
        if (!empty($this->dadosForm['EditSenhaAcad'])) {
            unset($this->dadosForm['EditSenhaAcad']);
            $altSenhaUser = new \App\adms\Models\academicos\AdmsEditarSenhaAcademico();
            $altSenhaUser->editPassAcad($this->dadosForm);
            if ($altSenhaUser->getResultado()) {
                $urlDestino = URLADM . 'academicos/visualizar/' . $this->dadosId;
                header("Location: $urlDestino");
            } else {
                $urlDestino = URLADM . 'academicos/visualizar/' . $this->dadosId;
                header("Location: $urlDestino");
            }
        } else {
            $visUs = new \App\adms\Models\academicos\AdmsEditarUsuarioAcademico();
            $this->dados['form'] = $visUs->verUsuario($this->dadosId);
            $this->editarSenhaView();
        }
    }

    private function editarSenhaView()
    {
        if (!empty($this->dados['form'])) {
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['vis_user' => ['menu_controller' => 'academicos', 'menu_metodo' => 'visualizar'],
            'edit_user' => ['menu_controller' => 'academicos', 'menu_metodo' => 'editar'],
            'edit_senha' => ['menu_controller' => 'academicos', 'menu_metodo' => 'editar-senha'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);

            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();

            $visUser = new \App\adms\Models\academicos\AdmsVerUsuarioAcademico();
            $this->dados['info_user'] = $visUser->visUsuario($this->dadosId);

            $listarSelect = new \App\adms\Models\academicos\AdmsCadastrarUsuarioAcademico();
            $this->dados['select'] = $listarSelect->listarCadastro();

            $carregarView = new \Core\ConfigView("adms/Views/academicos/editSenhaAcademico", $this->dados);
            $carregarView->renderizar('tabler');
        } else {

            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Usuário acadêmico não encontrado!</div>";
            $urlDestino = URLADM . 'academicos/listar';
            header("Location: $urlDestino");
        }
    }

    /** Emite o link de definição de senha do aluno (só por POST, a partir da ficha). */
    public function enviarLink($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!empty($this->dadosId) && !empty($this->dadosForm['EnviarLink'])) {
            $enviar = new \App\adms\Models\academicos\AdmsEnviarLinkAcesso();
            $enviar->enviar($this->dadosId);
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: use o botão Enviar link de acesso na ficha do aluno.</div>";
        }
        $urlDestino = URLADM . 'academicos/visualizar/' . $this->dadosId;
        header("Location: $urlDestino");
    }

    /** E3b: vincula o aluno a curso + grade + módulo + ano, com ingresso (POST da ficha). */
    public function vincular($dadosId = null)
    {
        $alunoId = (int) $dadosId;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $alunoId > 0) {
            (new \App\adms\Models\vinculo\AdmsVincularAluno())->vincular($alunoId, filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: []);
        }
        header('Location: ' . URLADM . 'academicos/visualizar/' . $alunoId . '?tab=tabeMatricula');
    }

    /** E3b: edita ingresso e situação da matrícula no curso (id da matrícula no curso). */
    public function editarVinculo($dadosId = null)
    {
        $mc = \App\adms\Models\vinculo\AdmsVinculoRegras::matriculaCurso((int) $dadosId);
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $mc) {
            (new \App\adms\Models\vinculo\AdmsEditarVinculo())->editar((int) $dadosId, filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: []);
        }
        header('Location: ' . URLADM . ($mc ? 'academicos/visualizar/' . (int) $mc['usuario_id'] . '?tab=tabeMatricula' : 'academicos/listar'));
    }

    /** E3b: grava ou muda o módulo do aluno num ano (id da matrícula no curso). */
    public function posicionarModulo($dadosId = null)
    {
        $mc = \App\adms\Models\vinculo\AdmsVinculoRegras::matriculaCurso((int) $dadosId);
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $mc) {
            (new \App\adms\Models\vinculo\AdmsPosicionarModulo())->posicionar((int) $dadosId, filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: []);
        }
        header('Location: ' . URLADM . ($mc ? 'academicos/visualizar/' . (int) $mc['usuario_id'] . '?tab=tabeMatricula' : 'academicos/listar'));
    }

    /** E4: boletim POR CURSO do aluno (RA), uma etapa, agrupado por módulo — motor único. */
    public function boletim($dadosId = null)
    {
        $rm = preg_replace('/\D/', '', (string) $dadosId);
        if ($rm === '') {
            header('Location: ' . URLADM . 'academicos/listar');
            return;
        }
        $this->dados['paginaAtiva'] = $this->pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $b = \App\adms\Models\notas\AdmsBoletimCurso::doAluno($rm);
        /* E8c (A11): seletor de curso e ano; o PDF recebe o mesmo filtro */
        $this->dados['filtro'] = ['curso' => (int) (filter_input(INPUT_GET, 'filtro_curso', FILTER_VALIDATE_INT) ?: 0), 'ano' => (int) (filter_input(INPUT_GET, 'filtro_ano', FILTER_VALIDATE_INT) ?: 0)];
        $this->dados['opcoes'] = \App\adms\Models\notas\AdmsBoletimCurso::opcoes($b);
        $this->dados['boletim'] = \App\adms\Models\notas\AdmsBoletimCurso::filtrar($b, $this->dados['filtro']['curso'], $this->dados['filtro']['ano']);
        (new \Core\ConfigView("adms/Views/academicos/verBoletim", $this->dados))->renderizar('tabler');
    }

    /** E4: o mesmo boletim em PDF. */
    public function imprimirBoletim($dadosId = null)
    {
        $rm = preg_replace('/\D/', '', (string) $dadosId);
        if ($rm === '') {
            header('Location: ' . URLADM . 'academicos/listar');
            return;
        }
        (new \App\adms\Models\academicos\AdmsGerarBoletimPdf())->gerar($rm, (int) (filter_input(INPUT_GET, 'filtro_curso', FILTER_VALIDATE_INT) ?: 0), (int) (filter_input(INPUT_GET, 'filtro_ano', FILTER_VALIDATE_INT) ?: 0));
    }
}