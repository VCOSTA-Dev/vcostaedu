<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class Disciplinas
{
    private $pagina = 91;
    private $normalpagina = 115;
    private $dados;
    private $dadosId;
    private $dadosForm;
    
    public function listar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['PesqPeriodo'])){
            $urlDestino = URLADM . 'disciplinas/listar/'.$this->dadosForm['pesqAno'];
            header("Location: $urlDestino");
        }else{
            
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['cad_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'cadastrar'],
            'edit_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'editar'],
            'vis_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'visualizar'],
            'list_horas' => ['menu_controller' => 'horario-aula', 'menu_metodo' => 'listar'],
            'add_prof' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'adiciona-professor'],
            'gerar_oferta' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'gerar-oferta'],
            'list_alunos' => ['menu_controller' => 'portal-alunos', 'menu_metodo' => 'listar'],
            'del_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'apagar'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);
    
            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();
            $ano = (!empty($this->dadosId) ? $this->dadosId : ANO_ATUAL);
            $listDisci = new \App\adms\Models\disciplinas\AdmsListarDisciplinas();
            $this->dados['listaDisciplinas'] = $listDisci->listaDisciplinas($ano);
    
            $listPesquisa = new \App\adms\Models\disciplinas\AdmsListarDisciplinas();
            $this->dados['select'] = $listPesquisa->listarCadastro();
    
            $carregarView = new \Core\ConfigView("adms/Views/disciplinas/listDisciplinas", $this->dados);
            $carregarView->renderizar('tabler');
        }
    }

    public function cadastrar()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['CadDisciplina'])){
            unset($this->dadosForm['CadDisciplina']);
            $cadastraDisciplina = new \App\adms\Models\disciplinas\AdmsCadastrarDisciplina();
            $cadastraDisciplina->cadDisc($this->dadosForm);
            if($cadastraDisciplina->getResultado()){
                $urlDestino = URLADM . 'disciplinas/listar';
                header("Location: $urlDestino");
            }else{
                $this->dados['form'] = $this->dadosForm;
                $this->cadastrarView();
            }
        }else{
            $this->cadastrarView();
        }
        
    }

    private function cadastrarView()
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        
        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $listSelect = new \App\adms\Models\disciplinas\AdmsCadastrarDisciplina();
        $this->dados['select'] = $listSelect->listarCadastro();

        $carregarView = new \Core\ConfigView("adms/Views/disciplinas/cadDisciplinas", $this->dados);
        $carregarView->renderizar('tabler');

    }

    public function visualizar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        if(!empty($this->dadosId)){
            $botao = ['edit_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'editar'],
            'vis_faltas' => ['menu_controller' => 'faltas', 'menu_metodo' => 'visualizar'],
            'encerrar' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'encerrar'],
            'list_ativi' => ['menu_controller' => 'atividades', 'menu_metodo' => 'listar'],
            'list_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'listar'],
            'my_disc' => ['menu_controller' => 'minhas-disciplinas', 'menu_metodo' => 'listar']];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);
            $this->dados['paginaAtiva'] = ($this->dados['botao']['list_disc'] ? $this->pagina : $this->normalpagina);
            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();
            
            $visDisci = new \App\adms\Models\disciplinas\AdmsVerDisciplina();
            $this->dados['info_dado'] = $visDisci->visDisciplina($this->dadosId);

            $this->dados['barra'] = \App\adms\Models\notas\AdmsOfertaInfo::permissoesBarra(); // E8c (R5)
            $carregarView = new \Core\ConfigView("adms/Views/disciplinas/verDisciplinas", $this->dados);
            $carregarView->renderizar('tabler');
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a disciplina!</div>";
            $pgerrada = ['lista' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'listar'],];
            $visBotaopg = new \App\adms\Models\AdmsBotao();
            $verificaPg = $visBotaopg->valBotao($pgerrada);
            if($verificaPg['lista']){
                $urlDestino = URLADM . 'disciplinas/listar';
            }else{
                $urlDestino = URLADM . 'minhas-disciplinas/listar';
            }
            header("Location: $urlDestino"); 
        }
    }

    public function editar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        if (\App\adms\Models\notas\AdmsCalculoNotas::travaEncerrada($this->dadosId)) { header('Location: ' . URLADM . 'disciplinas/visualizar/' . $this->dadosId); return; }
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosId)){
            $this->editPrev();
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a disciplina!</div>";
            $pgerrada = ['lista' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'listar'],];
            $visBotaopg = new \App\adms\Models\AdmsBotao();
            $verificaPg = $visBotaopg->valBotao($pgerrada);
            if($verificaPg['lista']){
                $urlDestino = URLADM . 'disciplinas/listar';
            }else{
                $urlDestino = URLADM . 'minhas-disciplinas/listar';
            }
            header("Location: $urlDestino"); 
        }
    }

    private function editPrev()
    {
        if(!empty($this->dadosForm['EditDisciplina'])){
            unset($this->dadosForm['EditDisciplina']);
            $altDisc = new \App\adms\Models\disciplinas\AdmsEditarDisciplinas();
            $altDisc->editDisc($this->dadosForm);
            if($altDisc->getResultado()){
                $urlDestino = URLADM . 'disciplinas/listar/'.$this->dadosForm['ano'];
                header("Location: $urlDestino"); 
            }else{
                $this->dados['form'] = $this->dadosForm;
                $this->editView();
            }
        }else{
            $visDisc = new \App\adms\Models\disciplinas\AdmsEditarDisciplinas();
            $this->dados['form'] = $visDisc->visDisciplina($this->dadosId);
            $this->editView();
        }

    }

    private function editView()
    {
        if(!empty($this->dados['form'])){
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['vis_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'visualizar'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);

            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();

            $listSelect = new \App\adms\Models\disciplinas\AdmsCadastrarDisciplina();
            $this->dados['select'] = $listSelect->listarCadastro();
            /* E3b: com alunos ou atividades, a linha da matriz da oferta não muda. */
            $this->dados['travaLinha'] = \App\adms\Models\disciplinas\AdmsEditarDisciplinas::temUso((int) ($this->dados['form'][0]['id'] ?? $this->dados['form']['id'] ?? 0));

            $carregarView = new \Core\ConfigView("adms/Views/disciplinas/editDisciplinas", $this->dados);
            $carregarView->renderizar('tabler');
        
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a disciplina!</div>";
            $pgerrada = ['lista' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'listar'],];
            $visBotaopg = new \App\adms\Models\AdmsBotao();
            $verificaPg = $visBotaopg->valBotao($pgerrada);
            if($verificaPg['lista']){
                $urlDestino = URLADM . 'disciplinas/listar';
            }else{
                $urlDestino = URLADM . 'minhas-disciplinas/listar';
            }
            header("Location: $urlDestino"); 
        }

    }

    public function apagar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['ApagarDisc'])){
            unset($this->dadosForm['ApagarDisc']);
            $delDisciplina = new \App\adms\Models\disciplinas\AdmsApagarDisciplina();
            $delDisciplina->delDisciplina($this->dadosForm);
            if($delDisciplina->getResultado()){
                $urlDestino = URLADM . 'disciplinas/listar/'.$this->dadosId;
                header("Location: $urlDestino");
            }else{
                $urlDestino = URLADM . 'disciplinas/listar/'.$this->dadosId;
                header("Location: $urlDestino");
            }
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a empresa!</div>";
            $pgerrada = ['lista' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'listar'],];
            $visBotaopg = new \App\adms\Models\AdmsBotao();
            $verificaPg = $visBotaopg->valBotao($pgerrada);
            if($verificaPg['lista']){
                $urlDestino = URLADM . 'disciplinas/listar';
            }else{
                $urlDestino = URLADM . 'minhas-disciplinas/listar';
            }
            header("Location: $urlDestino");
        }
    }

    public function adicionaProfessor($dadosId = null)
    {
        $this->dadosId = (string) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosId)){
            if(!empty($this->dadosForm['AdicionaProfessor'])){
                unset($this->dadosForm['AdicionaProfessor']);
                $cadProfessorDisc = new \App\adms\Models\disciplinas\AdmsAdicionarProfessor();
                $cadProfessorDisc->cadProfessor($this->dadosForm);
                if($cadProfessorDisc->getResultado()){
                    $urlDestino = URLADM . "disciplinas/listar/{$this->dadosId}";
                    header("Location: $urlDestino");
                }else{
                    $urlDestino = URLADM . "disciplinas/listar/{$this->dadosId}";
                    header("Location: $urlDestino");
                }
            }else{
                $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a disciplina!</div>";
                $urlDestino = URLADM . "disciplinas/listar/{$this->dadosId}";
                header("Location: $urlDestino");
            }

        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a disciplina!</div>";
            $pgerrada = ['lista' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'listar'],];
            $visBotaopg = new \App\adms\Models\AdmsBotao();
            $verificaPg = $visBotaopg->valBotao($pgerrada);
            if($verificaPg['lista']){
                $urlDestino = URLADM . 'disciplinas/listar';
            }else{
                $urlDestino = URLADM . 'minhas-disciplinas/listar';
            }
            header("Location: $urlDestino");
        }
    }

    public function verPolivalente($dadosId = null)
    {

        $this->dadosId = (int) $dadosId;
        if(!empty($this->dadosId)){
            $botao = ['vis_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'visualizar'],
            'list_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'listar'],
            'my_disc' => ['menu_controller' => 'minhas-disciplinas', 'menu_metodo' => 'listar'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);
            $this->dados['paginaAtiva'] = ($this->dados['botao']['list_disc'] ? $this->pagina : $this->normalpagina);
            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();
            
            $visDisci = new \App\adms\Models\disciplinas\AdmsVerDisciplina();
            $this->dados['info_dado'] = $visDisci->visDisciplina($this->dadosId);

            $listPolivalente = new \App\adms\Models\disciplinas\AdmsVerPolivalentes();
            $this->dados['list_disc'] = $listPolivalente->visPolis($this->dadosId);

            $carregarView = new \Core\ConfigView("adms/Views/disciplinas/verPolivalente", $this->dados);
            $carregarView->renderizar('tabler');
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a disciplina!</div>";
            $pgerrada = ['lista' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'listar'],];
            $visBotaopg = new \App\adms\Models\AdmsBotao();
            $verificaPg = $visBotaopg->valBotao($pgerrada);
            if($verificaPg['lista']){
                $urlDestino = URLADM . 'disciplinas/listar';
            }else{
                $urlDestino = URLADM . 'minhas-disciplinas/listar';
            }
            header("Location: $urlDestino"); 
        }

    }

    public function adicionaPolivalencia($dadosId = null)
    {
        $this->dadosId = (string) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosId)){
            if(!empty($this->dadosForm['AddDisciPoliva'])){
                unset($this->dadosForm['AddDisciPoliva']);
                $cadDisciPolivalentes = new \App\adms\Models\disciplinas\AdmsAdicionarPolivalencia();
                $cadDisciPolivalentes->cadDisciplinas($this->dadosForm);
                if($cadDisciPolivalentes->getResultado()){
                    $urlDestino = URLADM . "disciplinas/ver-polivalente/{$this->dadosId}";
                    header("Location: $urlDestino");
                }else{
                    $urlDestino = URLADM . "disciplinas/ver-polivalente/{$this->dadosId}";
                    header("Location: $urlDestino");
                }
            }else{
                $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a disciplina!</div>";
                $urlDestino = URLADM . "disciplinas/ver-polivalente/{$this->dadosId}";
                header("Location: $urlDestino");
            }

        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a disciplina!</div>";
            $pgerrada = ['lista' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'listar'],];
            $visBotaopg = new \App\adms\Models\AdmsBotao();
            $verificaPg = $visBotaopg->valBotao($pgerrada);
            if($verificaPg['lista']){
                $urlDestino = URLADM . 'disciplinas/listar';
            }else{
                $urlDestino = URLADM . 'minhas-disciplinas/listar';
            }
            header("Location: $urlDestino");
        }
    }

    public function apagarPolivalencia($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['ApagarPolivalente'])){
            unset($this->dadosForm['ApagarPolivalente']);
            $delDisciplinaPoliv = new \App\adms\Models\disciplinas\AdmsApagarPolivalencia();
            $delDisciplinaPoliv->delDiscPolivalente($this->dadosForm);
            if($delDisciplinaPoliv->getResultado()){
                $urlDestino = URLADM . 'disciplinas/ver-polivalente/'.$this->dadosId;
                header("Location: $urlDestino");
            }else{
                $urlDestino = URLADM . 'disciplinas/ver-polivalente/'.$this->dadosId;
                header("Location: $urlDestino");
            }
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a empresa!</div>";
            $pgerrada = ['lista' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'listar'],];
            $visBotaopg = new \App\adms\Models\AdmsBotao();
            $verificaPg = $visBotaopg->valBotao($pgerrada);
            if($verificaPg['lista']){
                $urlDestino = URLADM . 'disciplinas/listar';
            }else{
                $urlDestino = URLADM . 'minhas-disciplinas/listar';
            }
            header("Location: $urlDestino");
        }
    }

    /**
     * E4: ENCERRAMENTO da oferta. GET mostra a prévia (cálculo final do motor);
     * POST (ConfirmarEncerramento) grava o resultado e trava notas e faltas.
     * Secretaria e administrador (rota 382).
     */
    public function encerrar($dadosId = null)
    {
        $ofertaId = (int) $dadosId;
        $modelo = new \App\adms\Models\notas\AdmsEncerrarOferta();
        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        if (!empty($form['ConfirmarEncerramento'])) {
            $modelo->encerrar($ofertaId);
            header('Location: ' . URLADM . ($modelo->getResultado() ? 'medias/final/' : 'disciplinas/encerrar/') . $ofertaId);
            return;
        }
        $previa = $modelo->previa($ofertaId);
        if (!$previa) {
            header('Location: ' . URLADM . 'disciplinas/listar');
            return;
        }
        $this->dados['previa'] = $previa;
        $bt = (new \App\adms\Models\AdmsBotao())->valBotao([
            'encerrar' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'encerrar'],
            'reabrir' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'reabrir'],
        ]);
        $this->dados['podeEncerrar'] = !empty($bt['encerrar']);
        $this->dados['podeReabrir'] = !empty($bt['reabrir']) && \App\adms\Models\helper\AdmsNivel::podeSensivel(); // Onda 2: ação sensível
        $this->dados['barra'] = \App\adms\Models\notas\AdmsOfertaInfo::permissoesBarra(); // E8c (R5)
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT e.acao, e.observacao, e.created, u.nome AS usuario FROM rm_professores_materias_encerramentos e LEFT JOIN adms_usuarios u ON u.id = e.usuario_id WHERE e.rm_prof_mat_id = :o ORDER BY e.id DESC", "o={$ofertaId}");
        $this->dados['historico'] = $ler->getResultado() ?: [];
        /* 1.13.0 (D-396, D-397): os fechamentos da oferta (vigente e substituídos), com a regra usada */
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT f.*, v.versao AS regraVersao, rg.nome AS regraNome, u.nome AS fechadoPorNome, r.nome AS reabertoPorNome,
                (SELECT COUNT(*) FROM rm_media_fechamento_resultados x WHERE x.fechamento_id = f.id) AS guardados
               FROM rm_media_fechamentos f LEFT JOIN rm_media_regra_versoes v ON v.id = f.regra_versao_id LEFT JOIN rm_media_regras rg ON rg.id = v.regra_id
               LEFT JOIN adms_usuarios u ON u.id = f.fechado_por LEFT JOIN adms_usuarios r ON r.id = f.reaberto_por
              WHERE f.rm_prof_mat_id = :o ORDER BY f.numero DESC", "o=" . (int) $dadosId);
        $this->dados['fechamentos'] = $ler->getResultado() ?: [];
        $this->dados['paginaAtiva'] = $this->pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        (new \Core\ConfigView("adms/Views/disciplinas/encerrarOferta", $this->dados))->renderizar('tabler');
    }

    /** E4: REABRE a oferta encerrada — só o administrador, com observação (rota 383, POST). */
    public function reabrir($dadosId = null)
    {
        $ofertaId = (int) $dadosId;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            (new \App\adms\Models\notas\AdmsReabrirOferta())->reabrir($ofertaId, filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: []);
        }
        header('Location: ' . URLADM . 'disciplinas/encerrar/' . $ofertaId);
    }

    /**
     * E3b: GERAR OFERTA DO ANO a partir da matriz (rota 240, que era
     * `gerarNovoano` → `AdmsGerarNovaGrade`, classe que não existia nem na origem).
     */
    public function gerarOferta()
    {
        $gradeId = (int) filter_input(INPUT_GET, 'grade', FILTER_VALIDATE_INT);
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $gerar = new \App\adms\Models\disciplinas\AdmsGerarOferta();
            $gerar->gerar(filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: []);
            header('Location: ' . URLADM . 'disciplinas/gerar-oferta' . ($gradeId ? '?grade=' . $gradeId : ''));
            return;
        }
        $this->dados['paginaAtiva'] = $this->pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $listar = new \App\adms\Models\modulos\AdmsListarModulos();
        /* E8d (P-8): grade desativada ou de curso INATIVO não gera oferta */
        $this->dados['grades'] = array_values(array_filter($listar->grades(), fn($g) => (int) $g['situacao'] !== 4 && (int) $g['situacaoCurso'] === 1));
        $this->dados['grade'] = $gradeId ? \App\adms\Models\matriz\AdmsMatrizRegras::grade($gradeId) : null;
        if ($this->dados['grade'] && ((int) $this->dados['grade']['situacao'] === 4 || !in_array((int) $gradeId, array_map('intval', array_column($this->dados['grades'], 'id')), true))) {
            $this->dados['grade'] = null;
        }
        $this->dados['modulos'] = $this->dados['grade'] ? $listar->listar($gradeId) : [];
        /* E8c (D-268): sem módulos, a oferta é da grade — todas as disciplinas ou as escolhidas */
        $this->dados['semModulos'] = \App\adms\Models\helper\AdmsModuloInterno::ativo();
        $this->dados['disciplinasGrade'] = ($this->dados['grade'] && $this->dados['semModulos']) ? ((new \App\adms\Models\matriz\AdmsVerMatriz())->ver($gradeId)['disciplinas'] ?? []) : [];
        $this->dados['disciplinasGrade'] = array_values(array_filter($this->dados['disciplinasGrade'], fn($d) => (int) ($d['situacaoMateria'] ?? 1) === 1)); // E8d (P-8)
        $this->dados['anos'] = (new \App\adms\Models\vinculo\AdmsListarVinculos())->opcoes()['anos'];
        $carregarView = new \Core\ConfigView("adms/Views/disciplinas/gerarOferta", $this->dados);
        $carregarView->renderizar('tabler');
    }
}
?>