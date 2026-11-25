<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * FALTAS da oferta (E4, etapa única). Rotas 96 (visualizar) e 105 (editar, POST).
 * Saíram as ações por bimestre e da chamada: atualizar-alunos (100), edit-faltas
 * (106, AJAX por bimestre), apagar (146), compensar (213) e editar-limite (219).
 * E8c (D-264, D-265): a compensação VOLTA com justificativa — 410 faltas/compensar e
 * 411 faltas/remover-compensacao (POST, só secretaria e administrador; AdmsCompensacoes).
 */
class Faltas
{
    private $paginaMenu = 91;
    private $normalpagina = 115;
    private $dados;

    public function visualizar($dadosId = null)
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
            'edit_falta' => ['menu_controller' => 'faltas', 'menu_metodo' => 'editar'],
            'notas' => ['menu_controller' => 'medias', 'menu_metodo' => 'final'],
            'boletim' => ['menu_controller' => 'academicos', 'menu_metodo' => 'boletim'],
            'importar' => ['menu_controller' => 'faltas', 'menu_metodo' => 'importar'], // 1.14.0
        ]);
        $this->dados['paginaAtiva'] = $this->dados['botao']['list_disc'] ? $this->paginaMenu : $this->normalpagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['calc'] = $calc;
        $this->dados['ficha'] = \App\adms\Models\notas\AdmsOfertaInfo::ficha($ofertaId);
        $this->dados['barra'] = \App\adms\Models\notas\AdmsOfertaInfo::permissoesBarra();
        $this->dados['situacoes'] = \App\adms\Models\notas\AdmsOfertaInfo::situacoesMatricula($ofertaId);
        $this->dados['podeCompensar'] = \App\adms\Models\faltas\AdmsCompensacoes::podeLancar();
        $this->dados['compensacoes'] = $this->dados['podeCompensar'] ? \App\adms\Models\faltas\AdmsCompensacoes::listar($ofertaId) : [];
        (new \Core\ConfigView('adms/Views/faltas/visFaltas', $this->dados))->renderizar('tabler');
    }

    /** E8c (D-264, D-265): lança uma compensação com tipo, justificativa e período. POST; só secretaria e administrador. */
    public function compensar($dadosId = null)
    {
        $ofertaId = (int) $dadosId;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $ofertaId > 0) {
            $m = new \App\adms\Models\faltas\AdmsCompensacoes();
            $ok = $m->lancar($ofertaId, filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: []);
            $this->aviso($ok, $ok ? 'Compensação lançada.' : '<b>Nada foi salvo:</b><ul class="mb-0"><li>' . implode('</li><li>', array_map(fn($e) => htmlspecialchars($e, ENT_QUOTES, 'UTF-8'), $m->getErros())) . '</li></ul>');
        }
        header('Location: ' . URLADM . 'faltas/visualizar/' . $ofertaId);
    }

    /** E8c: remove um lançamento de compensação (o total é recalculado). POST; só secretaria e administrador. */
    public function removerCompensacao($dadosId = null)
    {
        $ofertaId = (int) $dadosId;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $ofertaId > 0) {
            $m = new \App\adms\Models\faltas\AdmsCompensacoes();
            $ok = $m->remover($ofertaId, (int) (filter_input(INPUT_POST, 'id') ?? 0));
            $this->aviso($ok, $ok ? 'Compensação removida; o total do aluno foi recalculado.' : htmlspecialchars(implode(' ', $m->getErros()), ENT_QUOTES, 'UTF-8'));
        }
        header('Location: ' . URLADM . 'faltas/visualizar/' . $ofertaId);
    }

    private function aviso(bool $ok, string $html): void
    {
        $_SESSION['msg'] = "<div class='alert alert-" . ($ok ? 'success' : 'danger') . " alert-dismissible' id='avisoCompensacao'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>{$html}</div>";
    }

    /** 1.14.0 (E8, D-411): importa o CSV `rm;faltas` (POST com o arquivo). Tudo ou nada; só na fonte "lançamento". */
    public function importar($dadosId = null)
    {
        $ofertaId = (int) $dadosId;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $ofertaId > 0) {
            $m = new \App\adms\Models\faltas\AdmsImportarFaltas();
            $_SESSION['msg'] = $m->importar($ofertaId, $_FILES['arquivo'] ?? null, (int) $_SESSION['colUsuario_id'])
                ? "<div class='alert alert-success alert-dismissible' id='importacaoGravada'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Faltas importadas: " . $m->getGravados() . ' aluno(s). Tudo foi gravado.</div>'
                : \App\adms\Models\importacao\AdmsImportacaoCsv::caixaErros('Nenhuma falta foi gravada:', $m->getErros());
        }
        header('Location: ' . URLADM . 'faltas/visualizar/' . $ofertaId);
    }

    /** 1.14.0 (E8): o MODELO de planilha (`?tipo=notas` ou `faltas`): CSV com `;`, o cabeçalho e uma linha fictícia. */
    public function modelo($dadosId = null)
    {
        $tipo = (string) (filter_input(INPUT_GET, 'tipo') ?? 'faltas');
        [$cab, $exemplo, $nome] = \App\adms\Models\importacao\AdmsImportacaoCsv::MODELOS[$tipo] ?? \App\adms\Models\importacao\AdmsImportacaoCsv::MODELOS['faltas'];
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nome . '"');
        header('Cache-Control: no-store');
        echo "\xEF\xBB\xBF" . $cab . "\r\n" . $exemplo . "\r\n";
    }

    /** Só POST: grava faltas e compensadas da oferta (recusa se encerrada). */
    public function editar($dadosId = null)
    {
        $ofertaId = (int) $dadosId;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $ofertaId > 0) {
            (new \App\adms\Models\faltas\AdmsLancarFaltas())->salvar($ofertaId, filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: []);
        }
        header('Location: ' . URLADM . 'faltas/visualizar/' . $ofertaId);
    }
}
