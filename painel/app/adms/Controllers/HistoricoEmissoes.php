<?php

namespace App\adms\Controllers;

use App\adms\Models\historico\AdmsHistoricoEmissao as Emissao;
use App\adms\Models\historico\AdmsHistoricoModelos as Modelos;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * HISTÓRICOS EMITIDOS (E6b; rotas 395–401). Administrador e secretaria: listar, emitir
 * (tipo e modelo → prévia → emitir), ver, baixar o PDF e 2ª via. Cancelar: só o administrador
 * (rota 401). O PDF fica em storage/ (negado à web) e só sai por aqui, com permissão.
 */
class HistoricoEmissoes
{
    private $dados = [];

    public function listar(): void
    {
        $f = ['busca' => trim((string) (filter_input(INPUT_GET, 'busca') ?? '')), 'tipo' => (string) (filter_input(INPUT_GET, 'tipo') ?? ''), 'situacao' => (int) (filter_input(INPUT_GET, 'situacao') ?? 0)];
        $this->dados['filtros'] = $f;
        $this->dados['emissoes'] = Emissao::listar($f);
        $this->dados['semGatilhos'] = \App\adms\Models\helper\AdmsNivel::ehAdministrador() && !Emissao::gatilhosAtivos(); // E6c (D-201)
        $this->dados['matriculas'] = [];
        if ($f['busca'] !== '') {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead(
                "SELECT mc.id, u.rm, u.nome, c.nome AS curso, s.nome AS situacao FROM rm_matriculas_curso mc INNER JOIN col_usuarios u ON u.id = mc.usuario_id
                   INNER JOIN rm_cursos c ON c.id = mc.curso_id LEFT JOIN rm_matriculas_situacao s ON s.id = mc.situacao_id
                  WHERE CAST(u.rm AS CHAR) = :rm OR u.nome LIKE :nome ORDER BY u.nome, mc.id LIMIT :limit",
                "rm=" . urlencode($f['busca']) . "&nome=" . urlencode('%' . addcslashes($f['busca'], '%_\\') . '%') . "&limit=50"
            );
            $this->dados['matriculas'] = $ler->getResultado() ?: [];
        }
        $this->render('adms/Views/historicoEmissoes/listEmissoes', 395);
    }

    /** Tela de emissão de UMA matrícula: tipo, modelo, alertas, prévia e EMITIR (POST). */
    public function emitir($dadosId = null): void
    {
        $mcId = (int) $dadosId;
        $vida = \App\adms\Models\historico\AdmsVidaAcademica::montar($mcId);
        if (!$vida) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Matrícula não encontrada.</div>";
            header('Location: ' . URLADM . self::voltar());
            return;
        }
        $tipo = strtoupper((string) (filter_input(INPUT_POST, 'tipo') ?? filter_input(INPUT_GET, 'tipo') ?? 'PARCIAL'));
        $tipo = isset(Modelos::TIPOS[$tipo]) ? $tipo : 'PARCIAL';
        /* Onda 8, correção, segunda leva, 2.1 (D-87): na escola, o painel emite só o atestado de matrícula (o histórico
           escolar da educação básica é uma etapa à parte): a tela de outro tipo abre o atestado; o envio de outro tipo
           chega ao modelo, que o recusa sem gravar */
        $tipoPedido = $tipo;
        if (\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola()) {
            $tipo = 'ATESTADO';
        }
        $modelos = Modelos::aplicaveis($tipo, (int) $vida['matricula']['curso_id']);
        $modeloId = (int) (filter_input(INPUT_POST, 'modelo') ?? filter_input(INPUT_GET, 'modelo') ?? 0);
        if (!in_array($modeloId, array_map('intval', array_column($modelos, 'id')), true)) {
            $modeloId = (int) ($modelos[0]['id'] ?? 0);
        }
        if (filter_input(INPUT_POST, 'EmitirHistorico')) {
            $e = new Emissao();
            if ($e->emitir($mcId, $tipoPedido, $modeloId, (bool) filter_input(INPUT_POST, 'confirmo_alertas'), (int) $_SESSION['colUsuario_id'], (string) $_SESSION['colUsuario_nome'])) {
                $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='emitido'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>" . Modelos::nomeDocumento($tipo) . ' emitido.</div>'; // E6
                header('Location: ' . URLADM . 'historico-emissoes/ver/' . $e->getId());
                return;
            }
            $_SESSION['msg'] = "<div class='alert alert-danger' id='emissaoRecusada'><b>Emissão recusada:</b><ul class='mb-0'><li>" . implode('</li><li>', array_map(fn($x) => htmlspecialchars($x, ENT_QUOTES, 'UTF-8'), $e->getErros())) . '</li></ul></div>';
        }
        $prep = null;
        if ($modeloId) {
            $prep = (new Emissao())->preparar($mcId, $tipo, $modeloId, 'PREVIA', (string) $_SESSION['colUsuario_nome']);
        }
        $this->dados += ['mcId' => $mcId, 'vida' => $vida, 'tipo' => $tipo, 'modelos' => $modelos, 'modeloId' => $modeloId, 'prep' => $prep,
            'anteriores' => Emissao::listar(['aluno' => $vida['aluno']['id']])];
        $this->render('adms/Views/historicoEmissoes/emitir', 395);
    }

    /** PRÉVIA da emissão: o mesmo documento, com a tarja; nada é gravado. */
    public function previa($dadosId = null): void
    {
        $tipo = strtoupper((string) (filter_input(INPUT_GET, 'tipo') ?? ''));
        $tipo = \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'ATESTADO' : $tipo; // 2.1 (D-87): na escola, só o atestado
        $e = new Emissao();
        $pdf = $e->previaPdf((int) $dadosId, $tipo, (int) (filter_input(INPUT_GET, 'modelo') ?? 0), (string) $_SESSION['colUsuario_nome']);
        if ($pdf === null) {
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Prévia</title></head><body style="font-family: sans-serif"><h3 id="previaRecusada">A prévia não foi gerada</h3><p>' . htmlspecialchars(implode(' ', $e->getErros()), ENT_QUOTES, 'UTF-8') . '</p></body></html>';
            return;
        }
        self::enviarPdf($pdf, $tipo === 'ATESTADO' ? 'previa_atestado.pdf' : 'previa_historico.pdf');
    }

    public function ver($dadosId = null): void
    {
        $e = Emissao::ver((int) $dadosId);
        if (!$e) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Emissão não encontrada.</div>";
            header('Location: ' . URLADM . self::voltar());
            return;
        }
        $e['integra'] = Emissao::integra($e);
        $arq = Emissao::arquivoPdf($e);
        $e['arquivoConfere'] = $arq && hash_equals($e['hash_pdf'], hash_file('sha256', $arq));
        $this->dados['e'] = $e;
        $this->dados['botao'] = (new \App\adms\Models\AdmsBotao())->valBotao(['cancelar' => ['menu_controller' => 'historico-emissoes', 'menu_metodo' => 'cancelar']]);
        $this->render('adms/Views/historicoEmissoes/verEmissao', 395);
    }

    /** O PDF gravado (via=1 original; via=N a N-ésima via). Só por aqui, com a permissão da rota. */
    public function pdf($dadosId = null): void
    {
        $e = Emissao::ver((int) $dadosId);
        $via = max(1, (int) (filter_input(INPUT_GET, 'via') ?? 1));
        $arq = $e ? Emissao::arquivoPdf($e, $via) : null;
        if (!$arq) {
            http_response_code(404);
            echo 'Arquivo não encontrado.';
            return;
        }
        self::enviarPdf((string) file_get_contents($arq), ($e['tipo'] === 'ATESTADO' ? 'atestado' : 'historico_' . strtolower($e['tipo'])) . '_' . $e['codigo'] . ($via > 1 ? '_via' . $via : '') . '.pdf');
    }

    public function segundaVia($dadosId = null): void
    {
        $id = (int) $dadosId;
        if (filter_input(INPUT_POST, 'SegundaVia')) {
            $e = new Emissao();
            if ($e->segundaVia($id, (int) $_SESSION['colUsuario_id'])) {
                $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='viaGerada'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>" . $e->getVia() . 'ª via gerada, com o mesmo código e o mesmo conteúdo.</div>';
            } else {
                $_SESSION['msg'] = "<div class='alert alert-danger' id='viaRecusada'>" . htmlspecialchars(implode(' ', $e->getErros()), ENT_QUOTES, 'UTF-8') . '</div>';
            }
        }
        header('Location: ' . URLADM . 'historico-emissoes/ver/' . $id);
    }

    public function cancelar($dadosId = null): void
    {
        $id = (int) $dadosId;
        if (filter_input(INPUT_POST, 'CancelarEmissao')) {
            $e = new Emissao();
            if ($e->cancelar($id, (string) (filter_input(INPUT_POST, 'motivo') ?? ''), (int) $_SESSION['colUsuario_id'])) {
                $_SESSION['msg'] = "<div class='alert alert-warning' id='cancelada'>Emissão CANCELADA. A verificação pública passa a mostrar \"cancelada\".</div>";
            } else {
                $_SESSION['msg'] = "<div class='alert alert-danger' id='cancelamentoRecusado'>" . htmlspecialchars(implode(' ', $e->getErros()), ENT_QUOTES, 'UTF-8') . '</div>';
            }
        }
        header('Location: ' . URLADM . 'historico-emissoes/ver/' . $id);
    }

    /** Para onde volta quem não achou: a lista das emissões (no ensino superior) ou a lista de alunos (na escola, sem ela). */
    private static function voltar(): string
    {
        return \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'academicos/listar' : 'historico-emissoes/listar';
    }

    private static function enviarPdf(string $pdf, string $nome): void
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9_.-]/', '', $nome) . '"');
        header('Content-Length: ' . strlen($pdf));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        echo $pdf;
    }

    private function render(string $view, int $pagina): void
    {
        $this->dados['paginaAtiva'] = $pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        (new \Core\ConfigView($view, $this->dados))->renderizar('tabler');
    }
}
