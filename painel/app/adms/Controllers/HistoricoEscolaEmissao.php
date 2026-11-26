<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\historicoEscola\AdmsHistBase as B;
use App\adms\Models\historicoEscola\AdmsHistCadastros as C;
use App\adms\Models\historicoEscola\AdmsHistDocumento as D;
use App\adms\Models\historicoEscola\AdmsHistEmissao as E;

/**
 * A PRÉVIA E A EMISSÃO DO HISTÓRICO ESCOLAR (Parte E; D-114) — só na escola (páginas 650 a 655).
 *   previa      a tela: os avisos, a data do documento, a publicação, os dias letivos e as aulas dadas do bloco de
 *               transferência; o botão da prévia em PDF; emitir; as vias emitidas (2ª via, cancelar, o PDF)
 *   previaPdf   o PDF da prévia (marcado PRÉVIA; nada é gravado)
 *   emitir      POST (a ação 131: a secretaria e a direção)
 *   segundaVia  POST
 *   cancelar    POST, com o motivo (Parte J; D-119: a ação própria 133, separada de emitir e da 2ª via, a 131)
 *   pdf         o PDF guardado (?via=N), só se o sha256 confere
 */
class HistoricoEscolaEmissao
{
    private array $dados = [];

    private static function nivel(): string
    {
        $n = (string) ($_GET['nivel'] ?? $_POST['nivel'] ?? 'EF');
        return B::nivelValido($n) ? $n : 'EF';
    }

    public function previa($id = null): void
    {
        $id = (int) $id;
        $a = C::aluno($id);
        if (!$a) {
            $_SESSION['msg'] = B::aviso('danger', 'Aluno não encontrado.');
            header('Location: ' . URLADM . 'historico-escola-vida/buscar');
            return;
        }
        $nivel = self::nivel();
        $op = E::opcoes($_GET);
        $m = D::montar($id, $nivel, $op);
        $this->dados += ['aluno' => $a, 'nivel' => $nivel, 'op' => $op, 'montagem' => $m, 'emissoes' => E::doAluno($id),
            'pode' => ['emitir' => HistoricoEscola::pode('emitir', 'historico-escola-emissao'), 'via' => HistoricoEscola::pode('segunda-via', 'historico-escola-emissao'),
                'cancelar' => HistoricoEscola::pode('cancelar', 'historico-escola-emissao')]];
        $this->render('adms/Views/historicoEscola/emissao', 650);
    }

    public function previaPdf($id = null): void
    {
        $e = new E();
        $p = $e->preparar((int) $id, self::nivel(), E::opcoes($_GET));
        if ($p['pdf'] === null) {
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Prévia</title></head><body style="font-family: sans-serif"><h3 id="previaRecusada">A prévia não foi gerada</h3><p>'
                . htmlspecialchars(implode(' ', $p['bloqueios']), ENT_QUOTES, 'UTF-8') . '</p></body></html>';
            return;
        }
        /* a conferência do encaixe: o degrau escolhido, as páginas e a maior largura desenhada (em pontos) */
        $P = \App\adms\Models\historicoEscola\AdmsHistPdf::class;
        header('X-Historico-Degrau: ' . (int) $p['degrau']);
        header('X-Historico-Paginas: ' . $P::paginas($p['pdf']));
        header('X-Historico-Largura: ' . number_format($P::maiorXdoPdf($p['pdf']), 1, '.', ''));
        if (($_GET['formato'] ?? '') === 'html') { // o mesmo documento em HTML (o do degrau escolhido), para conferir o texto
            header('Content-Type: text/html; charset=UTF-8');
            echo $p['html'];
            return;
        }
        $P::enviar($p['pdf'], 'previa_historico.pdf');
    }

    public function emitir($id = null): void
    {
        $id = (int) $id;
        $nivel = self::nivel();
        $op = E::opcoes($_POST);
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $e = new E();
            if ($e->emitir($id, $nivel, $op, !empty($_POST['confirmo']), (int) ($_SESSION['colUsuario_id'] ?? 0), (string) ($_SESSION['colUsuario_nome'] ?? ''))) {
                $_SESSION['msg'] = B::aviso('success', 'O histórico foi emitido (1ª via), com o código de verificação e a cópia guardada.', 'historicoEmitido');
            } else {
                $_SESSION['msg'] = B::aviso('danger', 'Emissão recusada: ' . implode(' ', $e->getErros()), 'emissaoRecusada');
            }
        }
        header('Location: ' . URLADM . 'historico-escola-emissao/previa/' . $id . '?' . http_build_query(['nivel' => $nivel] + array_filter($op, fn($v) => $v !== '' && $v !== [])));
    }

    public function segundaVia($id = null): void
    {
        $alunoId = (int) $id;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $e = new E();
            $_SESSION['msg'] = $e->segundaVia((int) ($_POST['emissao'] ?? 0), (int) ($_SESSION['colUsuario_id'] ?? 0))
                ? B::aviso('success', $e->getVia() . 'ª via gerada: o mesmo documento e o mesmo código, com o número da via.', 'viaGerada')
                : B::aviso('danger', implode(' ', $e->getErros()), 'viaRecusada');
        }
        header('Location: ' . URLADM . 'historico-escola-emissao/previa/' . $alunoId . '?nivel=' . self::nivel());
    }

    public function cancelar($id = null): void
    {
        $alunoId = (int) $id;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $e = new E();
            $_SESSION['msg'] = $e->cancelar((int) ($_POST['emissao'] ?? 0), (string) ($_POST['motivo'] ?? ''), (int) ($_SESSION['colUsuario_id'] ?? 0))
                ? B::aviso('warning', 'O histórico foi cancelado. A cópia fica guardada e a conferência pública passa a dizer "cancelado".', 'historicoCancelado')
                : B::aviso('danger', implode(' ', $e->getErros()), 'cancelamentoRecusado');
        }
        header('Location: ' . URLADM . 'historico-escola-emissao/previa/' . $alunoId . '?nivel=' . self::nivel());
    }

    public function pdf($id = null): void
    {
        $via = max(1, (int) ($_GET['via'] ?? 1));
        $pdf = E::pdfConferido((int) $id, $via);
        if ($pdf === null) {
            http_response_code(404);
            echo 'O arquivo não foi encontrado, ou não confere com o que foi emitido.';
            return;
        }
        \App\adms\Models\historicoEscola\AdmsHistPdf::enviar($pdf, 'historico_' . (int) $id . ($via > 1 ? '_via' . $via : '') . '.pdf');
    }

    private function render(string $view, int $pagina): void
    {
        $this->dados['paginaAtiva'] = $pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        (new \Core\ConfigView($view, $this->dados))->renderizar('tabler');
    }
}
