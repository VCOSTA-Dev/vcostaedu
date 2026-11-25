<?php

namespace App\adms\Controllers;

use App\adms\Models\historico\AdmsHistoricoModelos as Modelos;
use App\adms\Models\historico\AdmsHistoricoEditor as Editor;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * MODELOS DE HISTÓRICO (E6b; rotas 391–394, só o administrador): lista, cadastro, edição e PRÉVIA (PDF com
 * tarja, sem gravar nada) com um aluno escolhido. E8d (A24): a edição é o EDITOR VISUAL de um documento só
 * (padrão do projeto de referência), com o painel de variáveis, os blocos do sistema e as abas de opções.
 */
class HistoricoModelos
{
    private $dados = [];

    public function listar(): void
    {
        $this->dados['modelos'] = Modelos::listar();
        $this->render('adms/Views/historicoModelos/listModelos', 391);
    }

    public function cadastrar(): void
    {
        $this->formulario(0);
    }

    public function editar($dadosId = null): void
    {
        $id = (int) $dadosId;
        if (!Modelos::ver($id)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Modelo não encontrado.</div>";
            header('Location: ' . URLADM . 'historico-modelos/listar');
            return;
        }
        $this->formulario($id);
    }

    private function formulario(int $id): void
    {
        $post = (is_array($_POST) ? $_POST : []);
        if (!empty($post['SalvarModelo'])) {
            $m = new Modelos();
            if ($m->salvar($id, $post, $_FILES['assinatura_img'] ?? [], (int) $_SESSION['colUsuario_id'])) {
                $ajustes = $m->getAjustes();
                $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='modeloSalvo'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Modelo salvo" . ($m->criouVersao() && $id > 0 ? ' — <b>nova versão criada</b>; as emissões anteriores continuam com a versão delas' : '') . '.'
                    . ($ajustes ? '<br><small id="modeloAjustes">Formatação que o documento não usa foi retirada: ' . htmlspecialchars(implode(', ', $ajustes), ENT_QUOTES, 'UTF-8') . '.</small>' : '') . '</div>';
                header('Location: ' . URLADM . 'historico-modelos/editar/' . $m->getId());
                return;
            }
            $_SESSION['msg'] = "<div class='alert alert-danger' id='modeloRecusado'><b>Nada foi salvo:</b><ul class='mb-0'><li>" . implode('</li><li>', array_map(fn($e) => htmlspecialchars($e, ENT_QUOTES, 'UTF-8'), $m->getErros())) . '</li></ul></div>';
            $this->dados['form'] = $post;
        }
        $modelo = $id > 0 ? Modelos::ver($id) : null;
        $pedido = (string) ($post['tipo'] ?? filter_input(INPUT_GET, 'tipo') ?? '');
        $tipo = $modelo['tipo'] ?? (isset(Modelos::TIPOS[$pedido]) ? $pedido : 'PARCIAL'); // E6: e ATESTADO
        $this->dados['id'] = $id;
        $this->dados['modeloAtual'] = $modelo;
        $this->dados['tipo'] = $tipo;
        $base = Editor::paraEditor($modelo['modelo'] ?? Editor::padrao($tipo), $tipo);
        if (isset($this->dados['form'])) {
            /* recusado: volta o que foi feito (já sem o HTML proibido), para corrigir */
            $base = Editor::doFormulario($post, $modelo['modelo'] ?? [], [], $tipo, $ignorar);
        }
        $this->dados['editor'] = $base;
        $this->dados['carregarEditor'] = true; /* rodapejs: o Summernote local (assets/plugins/summernote) */
        $this->dados['cursos'] = Modelos::cursos();
        $this->dados['matriculas'] = self::matriculas();
        $this->render('adms/Views/historicoModelos/formModelo', 391, 'tabler');
    }

    /** PRÉVIA do editor: o formulário como está (sem salvar) → PDF com tarja. */
    public function previa($dadosId = null): void
    {
        $post = (is_array($_POST) ? $_POST : []);
        $id = (int) $dadosId;
        $atual = $id > 0 ? Modelos::ver($id) : null;
        $tipo = $atual['tipo'] ?? (isset(Modelos::TIPOS[$post['tipo'] ?? '']) ? $post['tipo'] : 'PARCIAL'); // E6: e ATESTADO
        $secoes = Editor::doFormulario($post, $atual['modelo'] ?? [], [], $tipo, $erros);
        $mcId = (int) ($post['previa_matricula'] ?? 0);
        if (!$mcId) {
            $erros[] = 'Escolha o aluno (matrícula) da prévia.';
        }
        $pdf = null;
        if (!$erros) {
            $e = new \App\adms\Models\historico\AdmsHistoricoEmissao();
            $pdf = $e->previaPdf($mcId, $tipo, 0, (string) $_SESSION['colUsuario_nome'], $secoes);
            $erros = $e->getErros();
        }
        if ($pdf === null) {
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Prévia recusada</title></head><body style="font-family: sans-serif"><h3 id="previaRecusada">A prévia não foi gerada</h3><ul><li>'
                . implode('</li><li>', array_map(fn($x) => htmlspecialchars($x, ENT_QUOTES, 'UTF-8'), $erros)) . '</li></ul><p>Nada foi gravado. Feche esta aba e corrija o modelo.</p></body></html>';
            return;
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="previa_historico.pdf"');
        header('X-Content-Type-Options: nosniff');
        echo $pdf;
    }

    public static function matriculas(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT mc.id, u.rm, u.nome, c.nome AS curso, s.nome AS situacao FROM rm_matriculas_curso mc INNER JOIN col_usuarios u ON u.id = mc.usuario_id
               INNER JOIN rm_cursos c ON c.id = mc.curso_id LEFT JOIN rm_matriculas_situacao s ON s.id = mc.situacao_id ORDER BY u.nome, mc.id LIMIT :limit",
            "limit=500"
        );
        return $ler->getResultado() ?: [];
    }

    private function render(string $view, int $pagina, string $layout = 'tabler'): void
    {
        $this->dados['paginaAtiva'] = $pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        (new \Core\ConfigView($view, $this->dados))->renderizar($layout);
    }
}
