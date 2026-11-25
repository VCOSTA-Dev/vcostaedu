<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsAtualizacao;

/**
 * A TELA DE ATUALIZAÇÃO (Onda 2, Etapa 7) — o "dia dois": quem instalou numa versão e trouxe os arquivos da seguinte.
 *
 * Só apresentação: toda decisão está em AdmsAtualizacao, que o comando `php bin/migrar.php aplicar` também usa (D25 do
 * vcostaedu). Só o Administrador: aplicar etapas é o ato mais consequente que existe aqui, e quem faz precisa ser quem
 * consegue restaurar o backup.
 *
 * Onda 3, Etapa 11: EM PASSOS — uma etapa do banco por chamada, com a janela de progresso (POST fase=iniciar|passo, JSON,
 * na mesma rota "aplicar"); a página fechada ou a etapa que falhou retomam de onde pararam ("Continuar a atualização").
 * Sem JavaScript, o formulário aplica UMA etapa por envio e volta à tela, que diz quantas faltam.
 *
 * A DEPENDÊNCIA RECURSIVA: a página desta tela nasce na migration que ela mesma aplica. Enquanto a página não está no
 * banco, o roteador (AdmsPaginas::listarPaginas) a abre para o Administrador assim mesmo — senão o aviso de "atualização
 * pendente" levaria a uma tela que a própria pendência esconde.
 */
class Atualizar
{
    private array $dados = [];

    private function soAdministrador(): bool
    {
        if (\App\adms\Models\helper\AdmsNivel::ehAdministrador()) {
            return true;
        }
        header('Location: ' . URLADM . 'home/index');
        return false;
    }

    private function menus(): void
    {
        try {
            $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        } catch (\Throwable $e) {
            $this->dados['menus'] = []; // num banco antigo o menu pode não montar: a tela de atualização abre assim mesmo
        }
    }

    private static function dono(): string
    {
        return session_id() !== '' ? session_id() : 'sem-sessao';
    }

    private static function quem(): string
    {
        return (string) ($_SESSION['colUsuario_nome'] ?? 'o Administrador');
    }

    public function index(): void
    {
        if (!$this->soAdministrador()) {
            return;
        }
        $m = new AdmsAtualizacao();
        $lote = $m->lote();
        $this->dados['paginaAtiva'] = 489;
        $this->menus();
        $this->dados['estado'] = $m->estado();
        $this->dados['versoes'] = AdmsAtualizacao::versoes();
        $this->dados['lote'] = $lote;
        $this->dados['outro'] = $m->emAndamentoPorOutro(self::dono());
        (new \Core\ConfigView('adms/Views/atualizar/index', $this->dados))->renderizar('tabler');
        if ($lote && !empty($lote['concluida'])) {
            $m->encerrarLote(); // o resultado foi mostrado: o banco fica como o do comando
        }
    }

    /**
     * Aplica. Só por POST e só com a confirmação do backup (um campo do formulário: não some sem JavaScript).
     * Com fase (a janela de progresso): JSON, um passo por chamada. Sem fase: uma etapa e a volta à tela.
     */
    public function aplicar(): void
    {
        $fase = (string) ($_POST['fase'] ?? '');
        if ($fase !== '') {
            $this->passos($fase);
            return;
        }
        if (!$this->soAdministrador()) {
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($_POST['Atualizar'])) {
            header('Location: ' . URLADM . 'atualizar/index');
            return;
        }
        $volta = function (string $tipo, string $texto, string $id) {
            $_SESSION['msg'] = "<div class='alert alert-{$tipo} alert-dismissible' id='{$id}'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>" . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</div>';
            header('Location: ' . URLADM . 'atualizar/index');
        };
        if (empty($_POST['fiz_backup'])) {
            $volta('danger', 'Confirme que você tem uma cópia (backup) do banco de dados antes de atualizar. Nada foi alterado.', 'atualizacaoSemBackup');
            return;
        }
        $m = new AdmsAtualizacao();
        [$ok, $msg] = $m->iniciarLote(self::dono(), self::quem());
        if (!$ok) {
            $emDia = str_contains($msg, 'em dia');
            $volta($emDia ? 'success' : 'danger', $msg, $emDia ? 'atualizacaoEmDia' : (str_contains($msg, 'andamento') ? 'atualizacaoEmAndamento' : 'atualizacaoErro'));
            return;
        }
        $r = $m->passoLote(self::dono());
        if (!empty($r['ocupado'])) {
            $volta('warning', 'Outra atualização está em andamento neste banco de dados (pela tela ou pelo comando). Espere ela terminar. Nada foi aplicado.', 'atualizacaoEmAndamento');
            return;
        }
        header('Location: ' . URLADM . 'atualizar/index');
    }

    /** A janela de progresso: fase=iniciar (com a confirmação do backup) e fase=passo. JSON. */
    private function passos(string $fase): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        if (!\App\adms\Models\helper\AdmsNivel::ehAdministrador() || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(403);
            echo json_encode(['erro' => 'Só o Administrador atualiza o sistema.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $m = new AdmsAtualizacao();
        $dono = self::dono();
        if ($fase === 'iniciar') {
            if (empty($_POST['fiz_backup'])) {
                echo json_encode(['erro' => 'Confirme que você tem uma cópia (backup) do banco de dados antes de atualizar. Nada foi alterado.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            [$ok, $msg] = $m->iniciarLote($dono, self::quem());
            echo json_encode($ok ? AdmsAtualizacao::publico($m->lote()) : ['erro' => $msg], JSON_UNESCAPED_UNICODE);
            return;
        }
        /* o passo: a sessão solta (outra aba não espera), e o passo termina mesmo se a página for fechada no meio dele */
        session_write_close();
        @ignore_user_abort(true);
        @set_time_limit(0);
        echo json_encode($m->passoLote($dono), JSON_UNESCAPED_UNICODE);
    }
}
