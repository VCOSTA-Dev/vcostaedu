<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class PortalAlunos
{
    private $pagina = 91;
    private $dados;
    private $dadosId;
    private $dadosForm;
    
    public function listar($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $ensino = filter_input(INPUT_GET, "ensino", FILTER_SANITIZE_NUMBER_INT);
        $turma = filter_input(INPUT_GET, "turma", FILTER_SANITIZE_NUMBER_INT);
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['PesqPeriodo'])){
            $urlDestino = URLADM . "portal-alunos/listar/{$this->dadosForm['ano']}?ensino={$this->dadosForm['ensino']}&turma={$this->dadosForm['turma']}";
            header("Location: $urlDestino");
        }else{
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['list_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'listar'],
            'vis_aluno' => ['menu_controller' => 'portal-alunos', 'menu_metodo' => 'visualizar'],
            'edit_obs' => ['menu_controller' => 'portal-alunos', 'menu_metodo' => 'add-observacao'],
            'ord_al' => ['menu_controller' => 'portal-alunos', 'menu_metodo' => 'ordenacao'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);
            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();

            if(!empty($this->dadosId)){
                $listAlPort = new \App\adms\Models\portalAlunos\AdmsListaPortalAlunos();
                $this->dados['listPortalAlunos'] = $listAlPort->listarAlunos($this->dadosId, $ensino, $turma);
            }
    
            $listPesquisa = new \App\adms\Models\portalAlunos\AdmsListaPortalAlunos();
            $this->dados['select'] = $listPesquisa->listarCadastro();
    
            $carregarView = new \Core\ConfigView("adms/Views/portalAlunos/listAlunos", $this->dados);
            $carregarView->renderizar('tabler');
        }

    }

    public function ordenacao()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['OrdenarPortalAluno'])){
            unset($this->dadosForm['OrdenarPortalAluno']);
            $altOrdemMenu = new \App\adms\Models\portalAlunos\AdmsPortalAlunosOrdenacao();
            $altOrdemMenu->visOrdenacao($this->dadosForm);
            $result = $altOrdemMenu->getResultado();
            if($result['situacao']){
                $urlDestino = URLADM . $result['link'];
                header("Location: $urlDestino"); 
            }else{
                $urlDestino = URLADM . $result['link'];
                header("Location: $urlDestino"); 
            }
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar o item de menu!</div>";
            $urlDestino = URLADM . 'portal-alunos/listar';
            header("Location: $urlDestino");
        } 
    }

    public function visualizar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $ano = filter_input(INPUT_GET, "ano", FILTER_SANITIZE_NUMBER_INT);
        if(!empty($this->dadosId)){
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['list_portal' => ['menu_controller' => 'portal-alunos', 'menu_metodo' => 'listar'],
            'confirmar' => ['menu_controller' => 'portal-alunos', 'menu_metodo' => 'confirmar-enturmacao'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);

            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();

            /* E3b: enturmação sugerida pela matriz a partir do vínculo (por id). */
            $sugerida = new \App\adms\Models\portalAlunos\AdmsEnturmacaoSugerida();
            $this->dados['dados_info'] = $sugerida->montar((string) $this->dadosId, (int) (!empty($ano) ? $ano : ANO_ATUAL));

            $carregarView = new \Core\ConfigView("adms/Views/portalAlunos/visAluno", $this->dados);
            $carregarView->renderizar('tabler');
            
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar o Aluno!</div>";
            $urlDestino = URLADM . 'portal-alunos/listar';
            header("Location: $urlDestino"); 
        }
    }

    /** E3b: confirma a enturmação de uma posição no ano (id em rm_matriculas_ensino). Só POST. */
    public function confirmarEnturmacao($dadosId = null)
    {
        $posicao = (int) $dadosId;
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT me.ano, u.rm FROM rm_matriculas_ensino me INNER JOIN col_usuarios u ON u.id = me.usuario_id WHERE me.id = :id LIMIT :limit", "id={$posicao}&limit=1");
        $pos = $ler->getResultado()[0] ?? null;
        $M = \App\adms\Models\moodle\AdmsMoodle::class;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['fase'])) {
            /* Onda 6, Etapa 4: a janela de passos do envio automático ao Moodle (os itens desta confirmação, na sessão) */
            header('Content-Type: application/json; charset=UTF-8');
            $e = $_SESSION['moodle.envio'][$posicao] ?? null;
            if (!$e) {
                echo json_encode(['passo' => 1, 'total' => 1, 'concluida' => true, 'grupoAtual' => 'Pronto'], JSON_UNESCAPED_UNICODE);
                return;
            }
            if ($_POST['fase'] === 'passo') {
                $r = $M::enviarItens($e['restam']);
                $e = ['restam' => $r['restam'], 'enviados' => $e['enviados'] + $r['enviados'], 'recusados' => $e['recusados'] + $r['recusados']] + $e;
                $_SESSION['moodle.envio'][$posicao] = $e;
                if ($r['parou'] !== null) {
                    unset($_SESSION['moodle.envio'][$posicao]); // o que faltou segue na fila (Envios ao Moodle)
                    echo json_encode(['passo' => $e['total'] - count($e['restam']), 'total' => $e['total'], 'falhou' => ['motivo' => $r['parou'] . ' O que faltou ficou na fila: Moodle → Envios ao Moodle.']], JSON_UNESCAPED_UNICODE);
                    return;
                }
            }
            $feitos = $e['total'] - count($e['restam']);
            if (!$e['restam']) {
                unset($_SESSION['moodle.envio'][$posicao]);
            }
            echo json_encode(['passo' => $feitos, 'total' => $e['total'], 'concluida' => !$e['restam'], 'grupoAtual' => $e['restam'] ? 'envio ' . ($feitos + 1) . ' de ' . $e['total'] : 'Pronto'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $pos) {
            $form = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
            /* o Moodle fica para a fila; o envio automático vem depois, em passos, na tela do aluno (sem esperar o Moodle aqui) */
            $M::soFila(true);
            (new \App\adms\Models\portalAlunos\AdmsConfirmarEnturmacao())->confirmar($posicao, (array) ($form['ofertas'] ?? []));
            $ids = $M::aoTerminar();
            $M::soFila(false);
            if ($ids) {
                $_SESSION['moodle.envio'][$posicao] = ['restam' => $ids, 'total' => count($ids), 'enviados' => 0, 'recusados' => 0];
            }
        }
        header('Location: ' . URLADM . ($pos ? 'portal-alunos/visualizar/' . $pos['rm'] . '?ano=' . (int) $pos['ano'] : 'portal-alunos/listar'));
    }

    /* E3b: saíram enturmar() e desenturmar() (rotas 253 e 254), uma oferta por vez. A enturmação
       é gravada só pela confirmação (confirmarEnturmacao), que não apaga enturmação com nota,
       falta ou observação — o desenturmar antigo apagava. */

}