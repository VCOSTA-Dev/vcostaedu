<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class Atividades
{
    private $paginaMenu = 91;
    private $pagina = 109;
    private $normalpagina = 115;
    private $dados;
    private $tipo;
    private $dadosId;
    private $dadosForm;
    
    public function listar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        if(!empty($this->dadosId)){
            $botao = ['vis_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'visualizar'],
            'cad_trab' => ['menu_controller' => 'atividades', 'menu_metodo' => 'cadastrar'],
            'vis_trab' => ['menu_controller' => 'atividades', 'menu_metodo' => 'visualizar'],
            'list_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'listar'],
            'edit_trab' => ['menu_controller' => 'atividades', 'menu_metodo' => 'editar'],
            'del_trab' => ['menu_controller' => 'atividades', 'menu_metodo' => 'apagar'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);
            $this->dados['paginaAtiva'] = ($this->dados['botao']['list_disc'] ? $this->paginaMenu : $this->normalpagina);
    
            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();

            $listAtivid = new \App\adms\Models\atividades\AdmsListarAtividades();
            $this->dados['info_dado'] = $listAtivid->listAtividadesNota($this->dadosId);
    
            $this->dados['ficha'] = \App\adms\Models\notas\AdmsOfertaInfo::ficha($this->dadosId); // E8c (A14)
            $this->dados['barra'] = \App\adms\Models\notas\AdmsOfertaInfo::permissoesBarra(); // E8c (R5)
            $carregarView = new \Core\ConfigView("adms/Views/atividades/listAtividades", $this->dados);
            $carregarView->renderizar('tabler');
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Página não encontrada!</div>";
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

    public function cadastrar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        if (\App\adms\Models\notas\AdmsCalculoNotas::travaEncerrada($this->dadosId)) { header('Location: ' . URLADM . 'atividades/listar/' . $this->dadosId); return; }
        $this->tipo = filter_input(INPUT_GET, "tipo", FILTER_SANITIZE_NUMBER_INT);
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        /* Onda 4, Etapa 3: na escola, a atividade nova numa etapa fechada é recusada (a etapa é a da data final) */
        if (!empty($this->dadosForm['CadAtividadeNota']) && ($trava = \App\adms\Models\escola\AdmsEtapaNotas::travaData($this->dadosId, (string) ($this->dadosForm['data_final'] ?? $this->dadosForm['data_inicial'] ?? '')))) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: " . htmlspecialchars($trava, ENT_QUOTES, 'UTF-8') . '</div>';
            header('Location: ' . URLADM . 'atividades/listar/' . $this->dadosId);
            return;
        }
        // E5: tipo desativado (Acréscimo, Substitutiva, Recuperação) nem abre o formulário.
        if (!empty($this->tipo) && !\App\adms\Models\atividades\AdmsCadastrarAtividade::tipoAtivo((int) $this->tipo, (int) $this->dadosId)) { header('Location: ' . URLADM . 'atividades/listar/' . $this->dadosId); return; }
        if(!empty($this->dadosId)){
            if(!empty($this->tipo)){
                if(!empty($this->dadosForm['CadAtividadeNota'])){
                    unset($this->dadosForm['CadAtividadeNota']);
                    $this->dadosForm['rm_prof_mat_id'] = $this->dadosId; // E4: a oferta é a da URL (checada pela trava)
                    $this->dadosForm['documentos'] = (isset($_FILES['arquivo']) ? $_FILES['arquivo'] : null);
                    $addAtividades = new \App\adms\Models\atividades\AdmsCadastrarAtividade();
                    $addAtividades->cadAtividadeNota($this->dadosForm);
                    if($addAtividades->getResultado()){
                        $urlDestino = URLADM . 'atividades/listar/'.$this->dadosId;
                        header("Location: $urlDestino"); 
                    }else{
                        $this->dados['form'] = $this->dadosForm;
                        $this->cadastrarView();
                    }
                }else{
                    $this->cadastrarView();
                }
            }else{
                $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar o tipo de atividade!</div>";
                $urlDestino = URLADM . 'atividades/listar/'.$this->dadosId;
                header("Location: $urlDestino"); 
            }
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a página da atividade!</div>";
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

    private function cadastrarView()
    {
        $this->dados['paginaAtiva'] = $this->pagina;

        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $verDiscCadastro = new \App\adms\Models\atividades\AdmsCadastrarAtividade();
        $this->dados['info'] = $verDiscCadastro->visDisciplina($this->dadosId, $this->tipo);

        $carregarView = new \Core\ConfigView("adms/Views/atividades/cadAtividades", $this->dados);
        $carregarView->renderizar('tabler');
    }

   public function visualizar($dadosId = null)
   {
        $this->dadosId = (int) $dadosId;
        if(!empty($this->dadosId)){
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['edit_ativi' => ['menu_controller' => 'atividades', 'menu_metodo' => 'editar'],
            'up_notas' => ['menu_controller' => 'atividades', 'menu_metodo' => 'upload-notas'],
            'edit_notas' => ['menu_controller' => 'atividades', 'menu_metodo' => 'edit-notas'], // Onda 2: quem grava nota
            'hist_nota' => ['menu_controller' => 'auditoria', 'menu_metodo' => 'nota'],]; // Onda 2, Etapa 3
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);

            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();

            $verAtividades = new \App\adms\Models\atividades\AdmsVisualizarAtividades();
            $this->dados['dados_info'] = $verAtividades->visAtividadeNota($this->dadosId);

            $carregarView = new \Core\ConfigView("adms/Views/atividades/visAtividades", $this->dados);
            $carregarView->renderizarEditInt('tabler');
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
        if (\App\adms\Models\notas\AdmsCalculoNotas::travaEncerrada(\App\adms\Models\notas\AdmsCalculoNotas::ofertaDaAtividade($this->dadosId))) { header('Location: ' . URLADM . 'atividades/visualizar/' . $this->dadosId); return; }
        /* Onda 4, Etapa 3: na escola, a atividade de uma etapa fechada não muda */
        if (\App\adms\Models\escola\AdmsEtapaNotas::travaAtividade($this->dadosId)) { header('Location: ' . URLADM . 'atividades/visualizar/' . $this->dadosId); return; }
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosId)){
            $this->editPrev();
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a atividade!</div>";
            $urlDestino = URLADM . 'atividades/listar';
            header("Location: $urlDestino"); 
        }
    }

    private function editPrev()
    {
        if(!empty($this->dadosForm['EditAtividades'])){
            unset($this->dadosForm['EditAtividades']);
            $this->dadosForm['id'] = $this->dadosId; // E4: a atividade é a da URL (checada pela trava)
            $this->dadosForm['rm_prof_mat_id'] = \App\adms\Models\notas\AdmsCalculoNotas::ofertaDaAtividade($this->dadosId);
            $altAtivid = new \App\adms\Models\atividades\AdmsEditarAtividade();
            $altAtivid->editAtividadeNota($this->dadosForm);
            if($altAtivid->getResultado()){
                $urlDestino = URLADM . 'atividades/visualizar/'.$this->dadosId;
                header("Location: $urlDestino"); 
            }else{
                $this->dados['form'] = $this->dadosForm;
                $this->editView();
            }
        }else{
            $visDiscAtivi = new \App\adms\Models\atividades\AdmsEditarAtividade();
            $this->dados['form'] = $visDiscAtivi->visAtividade($this->dadosId);
            $this->editView();
        }

    }

    private function editView()
    {
        if(!empty($this->dados['form'])){
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['vis_ativi' => ['menu_controller' => 'atividades', 'menu_metodo' => 'visualizar'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);

            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();

            $listTipificacao = new \App\adms\Models\atividades\AdmsEditarAtividade();
            $this->dados['select'] = $listTipificacao->listEdicao();
            
            $carregarView = new \Core\ConfigView("adms/Views/atividades/editAtividades", $this->dados);
            $carregarView->renderizar('tabler');
        
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a atividade!</div>";
            $urlDestino = URLADM . 'atividades/listar';
            header("Location: $urlDestino"); 
        }

    }

    public function apagar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        if (\App\adms\Models\notas\AdmsCalculoNotas::travaEncerrada($this->dadosId)) { header('Location: ' . URLADM . 'atividades/listar/' . $this->dadosId); return; }
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosId)){
            if(!empty($this->dadosForm['ApagarAtividades'])){
                unset($this->dadosForm['ApagarAtividades']);
                $this->dadosForm['rm_prof_mat_id'] = $this->dadosId; // E4: a oferta é a da URL (checada pela trava)
                $delAtividadeNota = new \App\adms\Models\atividades\AdmsApagarAtividades();
                $delAtividadeNota->delAtividade($this->dadosForm);
                if($delAtividadeNota->getResultado()){
                    $urlDestino = URLADM . 'atividades/listar/'.$this->dadosId;
                    header("Location: $urlDestino");
                }else{
                    $urlDestino = URLADM . 'atividades/listar/'.$this->dadosId;
                    header("Location: $urlDestino");
                }
            }else{
                $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a atividade da disciplina!</div>";
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
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a atividade da disciplina!</div>";
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

    public function editNotas()
    {

        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!empty($this->dadosForm['action'])) {
            unset($this->dadosForm['action']);
           $editNotasAtividade = new \App\adms\Models\atividades\AdmsEditNotas();
           $editNotasAtividade->editNotas($this->dadosForm);
           if($editNotasAtividade->getResultado()){
                $retorna = ['erro' => true, 'valor' => $this->dadosForm];
                unset($_SESSION['msg']);
            }else{
                /* 1.18.2 (D-490): a recusa devolve a nota GRAVADA, para a tela voltar a ela (antes, a célula ficava com o valor recusado) */
                $lerNota = new \App\adms\Models\helper\AdmsRead();
                $lerNota->fullRead("SELECT nota FROM rm_disciplina_atividade_notas WHERE id = :id LIMIT :limit", 'id=' . (int) ($this->dadosForm['id'] ?? 0) . '&limit=1');
                $gravada = $lerNota->getResultado()[0]['nota'] ?? null;
                $retorna = ['erro' => false, 'valor' => $this->dadosForm, 'msg' => $editNotasAtividade->getMsg(),
                    'anterior' => ($gravada === null || $gravada === '') ? '' : str_replace('.', ',', (string) $gravada)];
                unset($_SESSION['msg']);
            }
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Erro: Necessário selecionar um aluno!</div>";
            $retorna = ['erro' => false, 'valor' => $this->dadosForm];
        }

        echo json_encode($retorna);

    }

    public function uploadNotas($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        if (\App\adms\Models\notas\AdmsCalculoNotas::travaEncerrada(\App\adms\Models\notas\AdmsCalculoNotas::ofertaDaAtividade($this->dadosId))) { header('Location: ' . URLADM . 'atividades/visualizar/' . $this->dadosId); return; }
        if (\App\adms\Models\escola\AdmsEtapaNotas::travaAtividade((int) $this->dadosId)) { header('Location: ' . URLADM . 'atividades/visualizar/' . $this->dadosId); return; } // Onda 4, Etapa 3
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosId)){
            $this->uploadPrev();
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a atividade!</div>";
            $urlDestino = URLADM . 'atividades/listar';
            header("Location: $urlDestino"); 
        }
    }

    private function uploadPrev()
    {
        if(!empty($this->dadosForm['UpNotasCsv'])){
            unset($this->dadosForm['UpNotasCsv']);
            $this->dadosForm['arquivo'] = ($_FILES['arquivo'] ? $_FILES['arquivo'] : null);
            // E4: atividade e oferta vêm da URL (já checada contra a trava), não do formulário.
            $this->dadosForm['atividade_id'] = $this->dadosId;
            $this->dadosForm['rm_prof_mat_id'] = \App\adms\Models\notas\AdmsCalculoNotas::ofertaDaAtividade($this->dadosId);
            $altUpload = new \App\adms\Models\atividades\AdmsUploadNotas();
            $altUpload->upNota($this->dadosForm);
            if($altUpload->getResultado()){
                $urlDestino = URLADM . 'atividades/visualizar/'.$this->dadosId;
                header("Location: $urlDestino");
            }else{
                $this->uploadView();
            }
        }else{
            $this->uploadView();
        }

    }

    private function uploadView()
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $botao = ['vis_ativi' => ['menu_controller' => 'atividades', 'menu_metodo' => 'visualizar'],];
        $visBotao = new \App\adms\Models\AdmsBotao();
        $this->dados['botao'] = $visBotao->valBotao($botao);

        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $verAtividades = new \App\adms\Models\atividades\AdmsUploadNotas();
        $this->dados['dados_info'] = $verAtividades->visAtividade($this->dadosId);

        $carregarView = new \Core\ConfigView("adms/Views/atividades/uploadNotas", $this->dados);
        $carregarView->renderizar('tabler');

    }

}
?>