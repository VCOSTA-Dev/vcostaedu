<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\academico\AdmsAtestadoAluno as Modelo;

/**
 * E6 (D-376): Acadêmico → Atestado de matrícula (só o nível Estudante, pelas páginas 21–23 de col_paginas).
 * O aluno é SEMPRE o da sessão; o número da matrícula que vem no POST só é aceito se for dele.
 */
class Atestado
{
    private $pagina = 21;
    private $dados = [];

    public function index(): void
    {
        $aluno = (int) ($_SESSION['academico_id'] ?? 0);
        $this->dados['paginaAtiva'] = $this->pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['matriculas'] = Modelo::matriculas($aluno);
        $this->dados['emissoes'] = Modelo::emissoes($aluno);
        (new \Core\ConfigView('adms/Views/atestado/atestado', $this->dados))->renderizar('tabler');
    }

    /** POST: emite o atestado da matrícula escolhida e abre a lista com o novo documento. */
    public function emitir(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && filter_input(INPUT_POST, 'EmitirAtestado')) {
            $a = new Modelo();
            if ($a->emitir((int) ($_SESSION['academico_id'] ?? 0), (string) ($_SESSION['academico_nome'] ?? ''), (int) filter_input(INPUT_POST, 'matricula', FILTER_VALIDATE_INT))) {
                $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='atestadoEmitido'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Atestado de matrícula emitido. Abra o PDF abaixo.</div>";
            } else {
                $_SESSION['msg'] = "<div class='alert alert-danger' id='atestadoRecusado'><b>O atestado não foi emitido:</b><ul class='mb-0'><li>"
                    . implode('</li><li>', array_map(fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8'), $a->getErros())) . '</li></ul></div>';
            }
        }
        header('Location: ' . URLACAD . 'atestado/index');
    }

    /** O PDF gravado — só de atestado do próprio aluno. */
    public function pdf($dadosId = null): void
    {
        $p = Modelo::pdf((int) ($_SESSION['academico_id'] ?? 0), (int) $dadosId);
        if (!$p) {
            http_response_code(404);
            echo 'Documento não encontrado.';
            return;
        }
        $pdf = (string) file_get_contents($p['arquivo']);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9_.-]/', '', $p['nome']) . '"');
        header('Content-Length: ' . strlen($pdf));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        echo $pdf;
    }
}
