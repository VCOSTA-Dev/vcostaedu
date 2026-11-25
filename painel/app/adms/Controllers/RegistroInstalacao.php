<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\manutencao\AdmsRegistroInstalacao as R;

/**
 * REGISTRAR A INSTALAÇÃO (Onda 6, Etapa 7; D-59), em Manutenção, só o Administrador. Voluntário: a tela mostra
 * exatamente o que vai antes de enviar.
 *   index     a situação, as escolhas e o conteúdo do envio (o que vai sempre e o que vai só se a pessoa quiser);
 *   enviar    POST: registra (ou confirma de novo) e tenta enviar;
 *   cancelar  POST: cancela o registro (avisa o endereço do projeto e para os reenvios).
 */
class RegistroInstalacao
{
    private array $dados = [];

    private static function aviso(string $tipo, string $texto, string $id): void
    {
        $_SESSION['msg'] = "<div class='alert alert-{$tipo} alert-dismissible' id='{$id}'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
            . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</div>';
    }

    public function index(): void
    {
        unset($_SESSION['vc_convite_registro']); // o convite do instalador: visto
        $this->dados['paginaAtiva'] = 529;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $e = R::estado();
        /* a prévia: as escolhas que a pessoa marcar agora (o padrão: só o que vai sempre) */
        $esc = [
            'nome' => isset($_GET['previa']) ? !empty($_GET['nome']) : !empty($e['escolhas']['nome']),
            'site' => isset($_GET['previa']) ? !empty($_GET['site']) : !empty($e['escolhas']['site']),
            'email' => isset($_GET['previa']) ? trim((string) ($_GET['email'] ?? '')) : (string) ($e['escolhas']['email'] ?? ''),
            'listar' => isset($_GET['previa']) ? !empty($_GET['listar']) : !empty($e['escolhas']['listar']),
        ];
        $this->dados['estado'] = $e;
        $this->dados['escolhas'] = $esc;
        $this->dados['opcionais'] = R::opcionaisDeAgora();
        $this->dados['conteudo'] = R::conteudo($esc, isset($e['identificador']) && ($e['situacao'] ?? '') !== 'cancelado' ? (string) $e['identificador'] : null);
        $this->dados['endereco'] = R::endereco();
        (new \Core\ConfigView('adms/Views/manutencao/registro', $this->dados))->renderizar('tabler');
    }

    public function enviar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $r = R::registrar(['nome' => !empty($_POST['nome']), 'site' => !empty($_POST['site']), 'email' => (string) ($_POST['email'] ?? ''), 'listar' => !empty($_POST['listar'])]);
            if ($r !== true) {
                self::aviso('danger', $r, 'registroRecusado');
            } else {
                $sit = R::estado()['situacao'] ?? '';
                self::aviso($sit === 'enviado' ? 'success' : 'info', R::SITUACOES[$sit] ?? '', 'registroGravado');
                (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(530, 200, 1, "Registro da instalação: {$sit}");
            }
        }
        header('Location: ' . URLADM . 'registro-instalacao/index');
    }

    public function cancelar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $e = R::cancelar();
            self::aviso('info', R::SITUACOES[$e['situacao'] ?? 'nao'] ?? '', 'registroCancelado');
            (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(531, 200, 1, 'Registro da instalação cancelado');
        }
        header('Location: ' . URLADM . 'registro-instalacao/index');
    }
}
