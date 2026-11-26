<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\historicoEscola\AdmsHistBase as B;
use App\adms\Models\historicoEscola\AdmsHistCadastros as C;

/**
 * OS CADASTROS DO HISTÓRICO ESCOLAR (Parte A; D-110) — só na escola (páginas 620 a 628).
 *   cadastros          a tela, em abas (?aba=textos|observacoes|logo|escolas|disciplinas|series|registro; ?nivel=EF|EM)
 *   salvarTexto        POST: uma vigência dos textos (id = 0: a vigência nova)
 *   salvarObservacao   POST
 *   logo               POST: enviar (PNG/JPEG até 1 MB) ou retirar o logo de um nível
 *   salvarEscola       POST
 *   salvarDisciplinas  POST: a tabela inteira
 *   salvarSeries       POST: a tabela inteira
 *   aluno              os dados do aluno que só o histórico usa (o RA estadual, a certidão)
 *   salvarAluno        POST
 *   anoLetivo          os anos letivos, fechados ou abertos, com o histórico do marcador (Parte H; D-117)
 *   fecharAno          POST: o marcador do ano fechado (a secretaria e a direção)
 *   reabrirAno         POST: com o motivo
 * Abrem a secretaria, a supervisão de secretaria, a direção e o administrador; a direção só consulta (as páginas que
 * gravam a recusam no servidor, e a tela esconde os botões).
 */
class HistoricoEscola
{
    private array $dados = [];

    public const ABAS = ['textos' => 'Textos', 'observacoes' => 'Observações', 'logo' => 'Logo', 'escolas' => 'Escolas', 'disciplinas' => 'Disciplinas', 'series' => 'Segmentos e séries', 'registro' => 'Registro de alterações'];

    /** Quem pode gravar cada parte (a tela esconde o que não pode; a recusa de verdade é a da rota). */
    public static function pode(string $metodo, string $controller = 'historico-escola'): bool
    {
        return !empty((new \App\adms\Models\AdmsBotao())->valBotao(['x' => ['menu_controller' => $controller, 'menu_metodo' => $metodo]])['x']);
    }

    public function cadastros(): void
    {
        $aba = (string) ($_GET['aba'] ?? 'textos');
        $aba = isset(self::ABAS[$aba]) ? $aba : 'textos';
        $nivel = (string) ($_GET['nivel'] ?? 'EF');
        $nivel = B::nivelValido($nivel) ? $nivel : 'EF';
        $this->dados += ['aba' => $aba, 'nivel' => $nivel];
        if ($aba === 'textos') {
            $vig = C::vigencias($nivel);
            $abrir = (int) ($_GET['vigencia'] ?? 0);
            $atual = null;
            foreach ($vig as $v) {
                if ((int) $v['id'] === $abrir) {
                    $atual = $v;
                }
            }
            $nova = isset($_GET['nova']) || !$vig;
            if (!$atual && !$nova) {
                $atual = C::vigente($nivel) ?? $vig[0];
            }
            /* a vigência nova copia a que vale hoje (sem nenhuma, o texto de partida, neutro) */
            $base = $nova ? (C::vigente($nivel) ?? ($vig[0] ?? null)) : $atual;
            $this->dados += ['vigencias' => $vig, 'atual' => $atual, 'nova' => $nova, 'form' => $base ? array_intersect_key($base, C::CAMPOS_TEXTO) : C::padrao($nivel),
                'resolvido' => C::resolvido($nivel)];
            /* conferir: qual vigência vale numa data (a do documento) */
            $dc = B::data((string) ($_GET['conferir'] ?? ''));
            $this->dados['conferir'] = $dc ? ['data' => $dc, 'vigencia' => C::vigente($nivel, $dc)] : null;
        } elseif ($aba === 'observacoes') {
            $this->dados['observacoes'] = C::observacoes($nivel);
        } elseif ($aba === 'logo') {
            $this->dados['logos'] = ['EF' => C::logo('EF'), 'EM' => C::logo('EM')];
            $this->dados['logoUri'] = ['EF' => C::logoDataUri('EF'), 'EM' => C::logoDataUri('EM')];
        } elseif ($aba === 'escolas') {
            $this->dados['escolas'] = C::escolas();
        } elseif ($aba === 'disciplinas') {
            $this->dados['disciplinas'] = C::disciplinas();
        } elseif ($aba === 'series') {
            $this->dados['segmentos'] = C::segmentos();
        } else {
            $this->dados['alteracoes'] = B::alteracoes(null, 0, 200);
        }
        $this->dados['pode'] = [
            'texto' => self::pode('salvar-texto'), 'observacao' => self::pode('salvar-observacao'), 'logo' => self::pode('logo'),
            'escola' => self::pode('salvar-escola'), 'disciplinas' => self::pode('salvar-disciplinas'), 'series' => self::pode('salvar-series'),
        ];
        $this->render('adms/Views/historicoEscola/cadastros', 620);
    }

    public function salvarTexto(): void
    {
        $nivel = (string) ($_POST['nivel'] ?? '');
        $r = self::post() ? C::salvarTexto((int) ($_POST['id'] ?? 0), $nivel, (string) ($_POST['vigente_desde'] ?? ''), $_POST) : 0;
        $this->voltar(is_int($r) && $r > 0 ? ['success', 'Os textos foram gravados; só o que mudou foi para o registro de alterações.', 'textoGravado'] : ['danger', (string) $r, 'textoRecusado'],
            'textos', $nivel, is_int($r) && $r > 0 ? '&vigencia=' . $r : '');
    }

    public function salvarObservacao(): void
    {
        $nivel = (string) ($_POST['nivel'] ?? '');
        $r = self::post() ? C::salvarObservacao((int) ($_POST['id'] ?? 0), $nivel, $_POST) : 0;
        $this->voltar(is_int($r) && $r > 0 ? ['success', 'A observação foi gravada.', 'observacaoGravada'] : ['danger', (string) $r, 'observacaoRecusada'], 'observacoes', $nivel);
    }

    public function logo(): void
    {
        $nivel = (string) ($_POST['nivel'] ?? '');
        $msg = ['danger', 'Nada foi enviado.', 'logoRecusado'];
        if (self::post()) {
            if (!empty($_POST['retirar'])) {
                $msg = C::retirarLogo($nivel) ? ['success', 'O logo do nível foi retirado: vale o logo da instituição.', 'logoRetirado'] : ['info', 'Este nível já usa o logo da instituição.', 'logoRetirado'];
            } elseif (isset($_FILES['logo']) && (int) $_FILES['logo']['error'] === UPLOAD_ERR_OK && is_uploaded_file((string) $_FILES['logo']['tmp_name'])) {
                $r = C::enviarLogo($nivel, (string) $_FILES['logo']['tmp_name'], (int) $_FILES['logo']['size']);
                $msg = $r === true ? ['success', 'O logo foi gravado.', 'logoGravado'] : ['danger', (string) $r, 'logoRecusado'];
            } else {
                $msg = ['danger', 'Envie uma imagem PNG ou JPEG de até 1 MB.', 'logoRecusado'];
            }
        }
        $this->voltar($msg, 'logo', $nivel);
    }

    public function salvarEscola(): void
    {
        $r = self::post() ? C::salvarEscola((int) ($_POST['id'] ?? 0), $_POST) : 0;
        $this->voltar(is_int($r) && $r > 0 ? ['success', 'A escola foi gravada.', 'escolaGravada'] : ['danger', (string) $r, 'escolaRecusada'], 'escolas', 'EF');
    }

    public function salvarDisciplinas(): void
    {
        $r = self::post() ? C::salvarDisciplinas($_POST) : 0;
        $this->voltar(is_int($r) ? [$r ? 'success' : 'info', $r ? "As disciplinas foram gravadas ({$r} " . ($r === 1 ? 'mudou' : 'mudaram') . ').' : 'Nada mudou.', 'disciplinasGravadas'] : ['danger', $r, 'disciplinasRecusadas'], 'disciplinas', 'EF');
    }

    public function salvarSeries(): void
    {
        $r = self::post() ? C::salvarSeries((array) ($_POST['nivel'] ?? []), (array) ($_POST['numero'] ?? [])) : 0;
        $this->voltar(is_int($r) ? [$r ? 'success' : 'info', $r ? "Os segmentos e as séries foram gravados ({$r} " . ($r === 1 ? 'mudou' : 'mudaram') . ').' : 'Nada mudou.', 'seriesGravadas'] : ['danger', $r, 'seriesRecusadas'], 'series', 'EF');
    }

    public function aluno($id = null): void
    {
        $a = C::aluno((int) $id);
        if (!$a) {
            $_SESSION['msg'] = B::aviso('danger', 'Aluno não encontrado.');
            header('Location: ' . URLADM . 'academicos/listar');
            return;
        }
        $this->dados['aluno'] = $a;
        $this->dados['escolas'] = C::escolas(true);
        $this->dados['alteracoes'] = B::alteracoes('esc_hist_aluno', (int) $id, 50);
        $this->dados['pode'] = self::pode('salvar-aluno');
        $this->render('adms/Views/historicoEscola/aluno', 627);
    }

    public function salvarAluno($id = null): void
    {
        $id = (int) $id;
        $r = self::post() ? C::salvarAluno($id, $_POST) : 0;
        $_SESSION['msg'] = is_int($r) ? B::aviso($r ? 'success' : 'info', $r ? 'Os dados do aluno para o histórico foram gravados.' : 'Nada mudou.', 'alunoGravado') : B::aviso('danger', $r, 'alunoRecusado');
        $destino = ($_POST['voltar'] ?? '') === 'vida' ? 'historico-escola-vida/aluno/' : 'historico-escola/aluno/';
        header('Location: ' . URLADM . $destino . $id);
    }

    /** D-117: o ano letivo fechado — o marcador explícito, com o histórico. */
    public function anoLetivo(): void
    {
        $this->dados['anos'] = \App\adms\Models\historicoEscola\AdmsHistAnoLetivo::anos();
        $this->dados['historico'] = \App\adms\Models\historicoEscola\AdmsHistAnoLetivo::historico();
        $this->dados['podeFechar'] = self::pode('fechar-ano');
        $this->dados['podeReabrir'] = self::pode('reabrir-ano');
        $this->render('adms/Views/historicoEscola/anoLetivo', 656);
    }

    public function fecharAno(): void
    {
        $ano = (int) ($_POST['ano'] ?? 0);
        $r = self::post() ? \App\adms\Models\historicoEscola\AdmsHistAnoLetivo::fechar($ano) : 'Pedido inválido.';
        $_SESSION['msg'] = $r === true ? B::aviso('success', "O ano letivo de {$ano} foi fechado: a vida escolar propõe o resultado final das séries dele.", 'anoFechado') : B::aviso('danger', (string) $r, 'anoRecusado');
        header('Location: ' . URLADM . 'historico-escola/ano-letivo');
    }

    public function reabrirAno(): void
    {
        $ano = (int) ($_POST['ano'] ?? 0);
        $r = self::post() ? \App\adms\Models\historicoEscola\AdmsHistAnoLetivo::reabrir($ano, (string) ($_POST['motivo'] ?? '')) : 'Pedido inválido.';
        $_SESSION['msg'] = $r === true ? B::aviso('success', "O ano letivo de {$ano} foi reaberto; o motivo ficou no histórico.", 'anoReaberto') : B::aviso('danger', (string) $r, 'anoRecusado');
        header('Location: ' . URLADM . 'historico-escola/ano-letivo');
    }

    private static function post(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    private function voltar(array $msg, string $aba, string $nivel, string $extra = ''): void
    {
        $_SESSION['msg'] = B::aviso($msg[0], $msg[1], $msg[2] ?? '');
        header('Location: ' . URLADM . 'historico-escola/cadastros?aba=' . $aba . '&nivel=' . (B::nivelValido($nivel) ? $nivel : 'EF') . $extra);
    }

    private function render(string $view, int $pagina): void
    {
        $this->dados['paginaAtiva'] = $pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        (new \Core\ConfigView($view, $this->dados))->renderizar('tabler');
    }
}
