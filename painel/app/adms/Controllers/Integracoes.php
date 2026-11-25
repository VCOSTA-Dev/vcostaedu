<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\api\AdmsApiChaves as Chaves;

/**
 * INTEGRAÇÕES (Onda 2, Etapa 6) — só o Administrador (a matriz da 0010).
 *   chaves         as chaves da API: criar (a chave aparece UMA vez), revogar, o último uso;
 *   criar/revogar  POST;
 *   documentacao   o Swagger UI servido do próprio sistema (assets/plugins/swagger-ui), com o teste pela chave colada;
 *   chamadas       o registro de cada chamada (chave, operação, resultado, IP, quando);
 *   especificacao  a especificação OpenAPI 3 (docs/api/openapi.json), com o endereço desta instalação.
 */
class Integracoes
{
    private array $dados = [];

    private function disponivel(): bool
    {
        if (!\App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0010')) {
            \App\adms\Models\helper\AdmsAtualizacaoPendente::responderPendente();
            return false;
        }
        return true;
    }

    private static function aviso(string $tipo, string $html, string $id = ''): void
    {
        $_SESSION['msg'] = "<div class='alert alert-{$tipo} alert-dismissible'" . ($id !== '' ? " id='{$id}'" : '') . "><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>{$html}</div>";
    }

    private function base(int $pagina): void
    {
        $this->dados['paginaAtiva'] = $pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
    }

    public function chaves(): void
    {
        if (!$this->disponivel()) {
            return;
        }
        $this->base(483);
        $this->dados['chaves'] = Chaves::listar();
        $this->dados['nova'] = $_SESSION['vc_api_chave_nova'] ?? null; // a chave recém-criada: mostrada só nesta tela, uma vez
        unset($_SESSION['vc_api_chave_nova']);
        $this->dados['botao'] = (new \App\adms\Models\AdmsBotao())->valBotao([
            'criar' => ['menu_controller' => 'integracoes', 'menu_metodo' => 'criar'],
            'revogar' => ['menu_controller' => 'integracoes', 'menu_metodo' => 'revogar'],
        ]);
        (new \Core\ConfigView('adms/Views/integracoes/chaves', $this->dados))->renderizar('tabler');
    }

    public function criar(): void
    {
        if ($this->disponivel() && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $c = new Chaves();
            $chave = $c->criar((string) ($_POST['nome'] ?? ''), (array) ($_POST['permissao'] ?? []), (string) ($_POST['validade'] ?? ''), (int) $_SESSION['colUsuario_id']);
            if ($chave) {
                $_SESSION['vc_api_chave_nova'] = ['chave' => $chave, 'nome' => trim((string) $_POST['nome'])];
            } else {
                self::aviso('danger', htmlspecialchars($c->getErro(), ENT_QUOTES, 'UTF-8'), 'chaveRecusada');
            }
        }
        header('Location: ' . URLADM . 'integracoes/chaves');
    }

    public function revogar($id = null): void
    {
        if ($this->disponivel() && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $c = new Chaves();
            if ($c->revogar((int) $id, (int) $_SESSION['colUsuario_id'])) {
                self::aviso('success', 'Chave revogada: a partir de agora, ela é recusada pela API.', 'chaveRevogada');
            } else {
                self::aviso('danger', htmlspecialchars($c->getErro(), ENT_QUOTES, 'UTF-8'));
            }
        }
        header('Location: ' . URLADM . 'integracoes/chaves');
    }

    public function documentacao(): void
    {
        if (!$this->disponivel()) {
            return;
        }
        $this->base(486);
        (new \Core\ConfigView('adms/Views/integracoes/documentacao', $this->dados))->renderizar('tabler');
    }

    public function chamadas(): void
    {
        if (!$this->disponivel()) {
            return;
        }
        $this->base(487);
        $f = ['chave_id' => (int) ($_GET['chave'] ?? 0), 'resultado' => (string) ($_GET['resultado'] ?? '')];
        $where = ' WHERE 1 = 1';
        $args = [];
        if ($f['chave_id'] > 0) {
            $where .= ' AND c.chave_id = ?';
            $args[] = $f['chave_id'];
        }
        if ($f['resultado'] === 'erro') {
            $where .= ' AND c.status >= 400';
        } elseif ($f['resultado'] === 'ok') {
            $where .= ' AND c.status < 400';
        }
        $S = \App\adms\Models\api\AdmsApiServidor::class;
        $this->dados['filtro'] = $f;
        $this->dados['chamadas'] = $S::todas('SELECT c.*, k.nome AS chave_nome FROM adms_api_chamadas c LEFT JOIN adms_api_chaves k ON k.id = c.chave_id' . $where . ' ORDER BY c.id DESC LIMIT 500', $args);
        $this->dados['chaves'] = $S::todas('SELECT id, nome, prefixo FROM adms_api_chaves ORDER BY id DESC');
        (new \Core\ConfigView('adms/Views/integracoes/chamadas', $this->dados))->renderizar('tabler');
    }

    public function especificacao(): void
    {
        if (!$this->disponivel()) {
            return;
        }
        $spec = json_decode((string) file_get_contents(dirname(__DIR__, 4) . '/docs/api/openapi.json'), true) ?: [];
        $spec['servers'] = [['url' => URL . 'api/v1', 'description' => 'Esta instalação']];
        /* Onda 7: os assuntos só da escola (os responsáveis) não aparecem no ensino superior — lá o recurso não existe */
        $soEscola = \App\adms\Models\api\AdmsApiChaves::SO_ESCOLA;
        if (\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola()) {
            $spec['info']['description'] = str_replace('Cadastros de apoio —', 'Cadastros de apoio, Responsáveis —', (string) ($spec['info']['description'] ?? ''));
        } else {
            $nomes = [];
            foreach ($spec['paths'] ?? [] as $caminho => $ops) {
                foreach ($ops as $metodo => $op) {
                    if (is_array($op) && in_array($op['x-assunto'] ?? '', $soEscola, true)) {
                        foreach ($op['tags'] ?? [] as $t) {
                            $nomes[$t] = true;
                        }
                        unset($spec['paths'][$caminho][$metodo]);
                    }
                }
                if (empty($spec['paths'][$caminho])) {
                    unset($spec['paths'][$caminho]);
                }
            }
            $spec['tags'] = array_values(array_filter($spec['tags'] ?? [], static fn($t) => !isset($nomes[$t['name'] ?? ''])));
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($spec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
