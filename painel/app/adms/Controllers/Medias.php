<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * NOTAS, FREQUÊNCIA E RESULTADO da oferta (E4). Rota 135 (medias/final).
 *
 * Tudo vem do motor único (AdmsCalculoNotas): média parcial enquanto a oferta está
 * aberta; o gravado, depois de encerrada. Saíram as telas e ações por bimestre
 * (medias/bimestral, processa-bimestre, limpar) e os processadores helperNotas/AdmsProcessa{1,2,3}Tipo,
 * que calculavam a média cada um do seu jeito. E8c (A12): o "Atualiza Lista" VOLTA como
 * medias/atualizar-lista (rota 412, POST) — ver AdmsAtualizarLista.
 */
class Medias
{
    private $pagina = 91;
    private $menuPagina = 115;
    private $dados;

    public function final($dadosId = null)
    {
        $ofertaId = (int) $dadosId;
        $calc = $ofertaId > 0 ? \App\adms\Models\notas\AdmsCalculoNotas::oferta($ofertaId) : [];
        if (!$calc) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: disciplina não encontrada!</div>";
            header('Location: ' . URLADM . 'minhas-disciplinas/listar');
            return;
        }
        $this->dados['botao'] = (new \App\adms\Models\AdmsBotao())->valBotao([
            'list_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'listar'],
            'encerrar' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'encerrar'],
            'faltas' => ['menu_controller' => 'faltas', 'menu_metodo' => 'visualizar'],
            'atualizar' => ['menu_controller' => 'medias', 'menu_metodo' => 'atualizar-lista'],
            'boletim' => ['menu_controller' => 'academicos', 'menu_metodo' => 'boletim'],
        ]);
        $this->dados['paginaAtiva'] = $this->dados['botao']['list_disc'] ? $this->pagina : $this->menuPagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['calc'] = $calc;
        /* E8c: barra da disciplina (R5), ficha da oferta e situação da matrícula de cada aluno */
        $this->dados['ficha'] = \App\adms\Models\notas\AdmsOfertaInfo::ficha($ofertaId);
        $this->dados['barra'] = \App\adms\Models\notas\AdmsOfertaInfo::permissoesBarra();
        $this->dados['situacoes'] = \App\adms\Models\notas\AdmsOfertaInfo::situacoesMatricula($ofertaId);
        (new \Core\ConfigView("adms/Views/medias/visFinal", $this->dados))->renderizar('tabler');
    }

    /** E8c (A12): "Atualizar lista" — cria as linhas de nota que faltam para os alunos enturmados depois. POST. */
    public function atualizarLista($dadosId = null)
    {
        $ofertaId = (int) $dadosId;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $ofertaId > 0) {
            $n = \App\adms\Models\notas\AdmsAtualizarLista::sincronizar($ofertaId);
            $_SESSION['msg'] = $n === null
                ? "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>A disciplina está encerrada: a lista não muda. Só o administrador reabre.</div>"
                : "<div class='alert alert-success alert-dismissible' id='listaAtualizada'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Lista atualizada: "
                    . ($n ? "{$n} linha(s) de nota criada(s) para alunos enturmados depois das atividades." : 'nada a criar, todos os alunos enturmados já estão em todas as atividades.') . '</div>';
            if ($n) {
                (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(412, 200, 3, "Atualizar lista: oferta={$ofertaId}; linhas={$n}");
            }
        }
        header('Location: ' . URLADM . 'medias/final/' . $ofertaId);
    }
}
