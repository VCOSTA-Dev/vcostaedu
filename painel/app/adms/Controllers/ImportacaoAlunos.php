<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\importacao\AdmsImportacaoAlunos as Imp;

/**
 * IMPORTAÇÃO DE ALUNOS POR PLANILHA (Onda 2, Etapa 5).
 *   listar    os lotes e o envio do CSV;          enviar    POST: lê o arquivo e cria o lote;
 *   conferir  as colunas, os valores e as linhas;  mapear    POST: o de-para das colunas;
 *   corrigir  POST: uma linha, sem reenviar;       aplicar   POST em JSON, em passos: grava o lote (as funções da tela);
 *   desfazer  POST, ação sensível;                 modelo    o CSV de exemplo.
 * Administrador, Supervisão de secretaria e Secretaria (a matriz da 0009); desfazer, só quem tem ações sensíveis.
 */
class ImportacaoAlunos
{
    private array $dados = [];

    private function disponivel(): bool
    {
        if (!\App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0009')) {
            \App\adms\Models\helper\AdmsAtualizacaoPendente::responderPendente();
            return false;
        }
        return true;
    }

    private static function aviso(string $tipo, string $html, string $id = ''): void
    {
        $_SESSION['msg'] = "<div class='alert alert-{$tipo} alert-dismissible'" . ($id !== '' ? " id='{$id}'" : '') . "><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>{$html}</div>";
    }

    private function botoes(): array
    {
        $b = (new \App\adms\Models\AdmsBotao())->valBotao([
            'enviar' => ['menu_controller' => 'importacao-alunos', 'menu_metodo' => 'enviar'],
            'mapear' => ['menu_controller' => 'importacao-alunos', 'menu_metodo' => 'mapear'],
            'corrigir' => ['menu_controller' => 'importacao-alunos', 'menu_metodo' => 'corrigir'],
            'aplicar' => ['menu_controller' => 'importacao-alunos', 'menu_metodo' => 'aplicar'],
            'desfazer' => ['menu_controller' => 'importacao-alunos', 'menu_metodo' => 'desfazer'],
        ]);
        $b['desfazer'] = !empty($b['desfazer']) && \App\adms\Models\helper\AdmsNivel::podeSensivel(); // ação sensível
        return $b;
    }

    public function listar(): void
    {
        if (!$this->disponivel()) {
            return;
        }
        $this->dados['paginaAtiva'] = 475;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['botao'] = $this->botoes();
        $this->dados['lotes'] = Imp::lotes();
        (new \Core\ConfigView('adms/Views/importacaoAlunos/listar', $this->dados))->renderizar('tabler');
    }

    public function enviar(): void
    {
        if (!$this->disponivel()) {
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($_FILES['arquivo']) || ($_FILES['arquivo']['error'] ?? 1) !== UPLOAD_ERR_OK) {
            self::aviso('danger', 'Escolha o arquivo CSV da planilha.');
            header('Location: ' . URLADM . 'importacao-alunos/listar');
            return;
        }
        $f = $_FILES['arquivo'];
        $nome = basename((string) $f['name']);
        if (!preg_match('/\.(csv|txt)$/i', $nome)) {
            self::aviso('danger', 'O arquivo precisa ser CSV (no Excel: Arquivo → Salvar como → CSV). Planilhas .xlsx não são lidas.');
            header('Location: ' . URLADM . 'importacao-alunos/listar');
            return;
        }
        if ((int) $f['size'] > \App\adms\Models\importacao\AdmsLeitorPlanilha::TAMANHO_MAXIMO) {
            self::aviso('danger', 'O arquivo passa de 2 MB. Divida-o em arquivos menores.');
            header('Location: ' . URLADM . 'importacao-alunos/listar');
            return;
        }
        $leitor = new \App\adms\Models\importacao\AdmsLeitorPlanilha();
        $leitura = $leitor->ler((string) file_get_contents($f['tmp_name']));
        if (!$leitura) {
            self::aviso('danger', htmlspecialchars($leitor->getErro(), ENT_QUOTES, 'UTF-8'));
            header('Location: ' . URLADM . 'importacao-alunos/listar');
            return;
        }
        $imp = new Imp();
        $id = $imp->criar($nome, $leitura);
        if (!$id) {
            self::aviso('danger', htmlspecialchars($imp->getErro(), ENT_QUOTES, 'UTF-8'));
            header('Location: ' . URLADM . 'importacao-alunos/listar');
            return;
        }
        self::aviso('info', 'Arquivo lido: ' . count($leitura['linhas']) . ' linha(s), ' . htmlspecialchars($leitura['codificacao'], ENT_QUOTES, 'UTF-8')
            . ', separador "' . htmlspecialchars($leitura['separador'], ENT_QUOTES, 'UTF-8') . '". <b>Nada foi gravado ainda:</b> confira e clique em Aplicar.', 'arquivoLido');
        header('Location: ' . URLADM . 'importacao-alunos/conferir/' . $id);
    }

    public function conferir($id = null): void
    {
        if (!$this->disponivel()) {
            return;
        }
        $lote = Imp::lote((int) $id);
        if (!$lote) {
            self::aviso('danger', 'Lote não encontrado.');
            header('Location: ' . URLADM . 'importacao-alunos/listar');
            return;
        }
        $this->dados['paginaAtiva'] = 477;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['botao'] = $this->botoes();
        $this->dados['lote'] = $lote;
        $this->dados['linhas'] = Imp::linhas((int) $id);
        $this->dados['valores'] = Imp::deParaValores($lote, $this->dados['linhas']);
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead('SELECT id, nome FROM rm_matricula_tipos WHERE situacao = :s ORDER BY id', 's=1');
        $this->dados['formas'] = $ler->getResultado() ?: [];
        if ((int) $lote['situacao'] === Imp::APLICADA) {
            $this->dados['impedimentos'] = Imp::impedimentosDoLote((int) $id);
        }
        (new \Core\ConfigView('adms/Views/importacaoAlunos/conferir', $this->dados))->renderizar('tabler');
    }

    public function mapear($id = null): void
    {
        if ($this->disponivel() && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $imp = new Imp();
            if ($imp->salvarMapa((int) $id, (array) ($_POST['mapa'] ?? []))) {
                self::aviso('success', 'Colunas ajustadas; as linhas foram conferidas de novo. O próximo arquivo com os mesmos cabeçalhos já vem com este ajuste.', 'mapaSalvo');
            } else {
                self::aviso('danger', htmlspecialchars($imp->getErro(), ENT_QUOTES, 'UTF-8'));
            }
        }
        header('Location: ' . URLADM . 'importacao-alunos/conferir/' . (int) $id);
    }

    public function corrigir($id = null): void
    {
        $numero = (int) ($_POST['numero'] ?? 0);
        if ($this->disponivel() && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $imp = new Imp();
            if ($imp->corrigir((int) $id, $numero, (array) ($_POST['dados'] ?? []))) {
                self::aviso('success', "Linha {$numero} corrigida; o lote foi conferido de novo.", 'linhaCorrigida');
            } else {
                self::aviso('danger', htmlspecialchars($imp->getErro(), ENT_QUOTES, 'UTF-8'));
            }
        }
        header('Location: ' . URLADM . 'importacao-alunos/conferir/' . (int) $id . '#linha-' . $numero);
    }

    /**
     * Aplicar, em passos (Onda 6, Etapa 4): POST em JSON, fase=iniciar (com a forma e a data de ingresso e o "aplicar sem
     * as linhas com erro") | passo — a janela de progresso. No fim (gravado, ou recusado e desfeito), a mensagem fica na
     * sessão e a janela abre a conferência do lote.
     */
    public function aplicar($id = null): void
    {
        $id = (int) $id;
        if (!$this->disponivel()) {
            return;
        }
        header('Content-Type: application/json; charset=UTF-8');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['erro' => 'Use o botão Aplicar a importação.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $imp = new Imp();
        $r = (string) ($_POST['fase'] ?? 'passo') === 'iniciar'
            ? $imp->iniciarAplicacao($id, (int) ($_POST['forma_ingresso_id'] ?? 0), (string) ($_POST['data_ingresso'] ?? ''), !empty($_POST['aceitar_erros']))
            : $imp->passoAplicacao($id);
        if (!empty($r['concluida'])) {
            $l = Imp::lote($id);
            self::aviso('success', "Importação aplicada: <b>{$l['aplicadas']}</b> aluno(s) cadastrado(s)" . ((int) $l['vinculos'] ? ", {$l['vinculos']} vinculado(s) ao curso" : '')
                . ((int) $l['puladas'] ? ", {$l['puladas']} pulado(s) (já cadastrados)" : '') . ((int) $l['erros'] ? ", {$l['erros']} linha(s) com erro de fora" : '') . '.', 'loteAplicado');
        } elseif (!empty($r['erro'])) {
            self::aviso('danger', htmlspecialchars((string) $r['erro'], ENT_QUOTES, 'UTF-8'), 'aplicarRecusado');
        }
        echo json_encode($r, JSON_UNESCAPED_UNICODE);
    }

    public function desfazer($id = null): void
    {
        if ($this->disponivel() && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $imp = new Imp();
            if ($imp->desfazer((int) $id, (string) ($_POST['motivo'] ?? ''))) {
                self::aviso('success', 'Importação desfeita: os alunos do lote foram retirados. O lote fica na lista, como desfeito.', 'loteDesfeito');
            } else {
                $html = htmlspecialchars($imp->getErro(), ENT_QUOTES, 'UTF-8');
                if ($imp->getImpedimentos()) {
                    $html .= '<ul class="mb-0 mt-1">';
                    foreach ($imp->getImpedimentos() as $i) {
                        $html .= '<li>RA ' . htmlspecialchars($i['rm'], ENT_QUOTES, 'UTF-8') . ' — ' . htmlspecialchars($i['nome'], ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars(implode('; ', $i['motivos']), ENT_QUOTES, 'UTF-8') . '</li>';
                    }
                    $html .= '</ul>';
                }
                self::aviso('danger', $html, 'desfazerRecusado');
            }
        }
        header('Location: ' . URLADM . 'importacao-alunos/conferir/' . (int) $id);
    }

    public function modelo(): void
    {
        if (!$this->disponivel()) {
            return;
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="modelo-importacao-alunos.csv"');
        echo Imp::modelo();
    }
}
