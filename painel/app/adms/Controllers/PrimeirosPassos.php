<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsPrimeirosPassos as Motor;

/**
 * OS PRIMEIROS PASSOS (Onda 2, Etapa 4) — a tela do motor de fases. Só o Administrador.
 *   index  as fases: concluída (medida no banco), pulada, bloqueada, a próxima; a frase de quando uma fase acaba
 *          de ser concluída; a prévia do logo (fase da instituição);
 *   fase   POST: pular, retomar, dispensar os Primeiros passos, voltar a mostrá-los.
 * Cada fase leva à tela que já existe (a fase grava pela MESMA função da tela) e ao "Faça comigo" dela.
 */
class PrimeirosPassos
{
    private $dados = [];

    public function index(): void
    {
        if (!Motor::disponivel()) {
            \App\adms\Models\helper\AdmsAtualizacaoPendente::responderPendente();
            return;
        }
        $this->dados['paginaAtiva'] = 470;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['anuncio'] = Motor::anunciar();
        $this->dados['fases'] = Motor::fases(true);
        $this->dados['progresso'] = Motor::progresso();
        $this->dados['dispensado'] = \App\adms\Models\helper\AdmsAmbiente::estado('primeiros_passos.dispensado') === '1';
        $this->dados['demonstracao'] = \App\adms\Models\helper\AdmsAmbiente::demonstracaoAtiva();
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT nome, logo FROM adms_empresas WHERE instituicao = 1 ORDER BY id LIMIT :limit", 'limit=1');
        $this->dados['instituicao'] = $ler->getResultado()[0] ?? null;
        (new \Core\ConfigView('adms/Views/primeirosPassos/index', $this->dados))->renderizar('tabler');
    }

    public function fase(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && Motor::disponivel()) {
            Motor::acao((string) ($_POST['acao'] ?? ''), (string) ($_POST['codigo'] ?? ''));
        }
        header('Location: ' . URLADM . (($_POST['acao'] ?? '') === 'dispensar' ? 'home/index' : 'primeiros-passos/index'));
    }
}
