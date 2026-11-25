<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\horarioAula\AdmsAulasDoDia as Aulas;

/**
 * OS HORÁRIOS DE AULA (Onda 8, correção, Parte 1.1; D-71): as aulas do dia de cada turno, com o início e o fim.
 *   listar    a tela: um cartão por turno, com as aulas e o formulário de cadastro (e o de edição, por ?editar=id);
 *   salvar    POST: cadastra (sem id) ou edita;
 *   situacao  POST: ativa ou desativa.
 */
class HorariosAula
{
    private array $dados = [];

    public function listar(): void
    {
        $this->dados['paginaAtiva'] = 116;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['turnos'] = Aulas::turnos();
        $this->dados['aulas'] = Aulas::porTurno();
        $editar = (int) ($_GET['editar'] ?? 0);
        $this->dados['editando'] = $editar ? Aulas::uma($editar) : null;
        $this->dados['form'] = $_SESSION['form_horario'] ?? null;
        unset($_SESSION['form_horario']);
        (new \Core\ConfigView('adms/Views/horariosAula/listar', $this->dados))->renderizar('tabler');
    }

    public function salvar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . URLADM . 'horarios-aula/listar');
            return;
        }
        $d = ['id' => $_POST['id'] ?? 0, 'periodo' => $_POST['periodo'] ?? 0, 'nome' => $_POST['nome'] ?? '', 'inicio' => $_POST['inicio'] ?? '', 'termino' => $_POST['termino'] ?? ''];
        $erro = Aulas::salvar($d, (int) ($_SESSION['colUsuario_id'] ?? 0));
        if ($erro !== '') {
            $_SESSION['msg'] = "<div class='alert alert-danger' id='horarioRecusado'>" . htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') . '</div>';
            $_SESSION['form_horario'] = $d;
            header('Location: ' . URLADM . 'horarios-aula/listar' . ((int) $d['id'] ? '?editar=' . (int) $d['id'] : '') . '#turno' . (int) $d['periodo']);
            return;
        }
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='horarioSalvo'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Horário de aula salvo.</div>";
        header('Location: ' . URLADM . 'horarios-aula/listar#turno' . (int) $d['periodo']);
    }

    public function situacao($id = null): void
    {
        $id = (int) $id;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $sit = (int) ($_POST['situacao'] ?? 0);
            $erro = Aulas::situacao($id, $sit, (int) ($_SESSION['colUsuario_id'] ?? 0));
            $_SESSION['msg'] = $erro !== ''
                ? "<div class='alert alert-danger' id='horarioRecusado'>" . htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') . '</div>'
                : "<div class='alert alert-success alert-dismissible' id='horarioSalvo'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Horário de aula " . ($sit === 1 ? 'ativado' : 'desativado') . '.</div>';
        }
        $a = Aulas::uma($id);
        header('Location: ' . URLADM . 'horarios-aula/listar' . ($a ? '#turno' . (int) $a['periodo'] : ''));
    }
}
