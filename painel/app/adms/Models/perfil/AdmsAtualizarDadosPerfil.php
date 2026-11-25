<?php

namespace App\adms\Models\perfil;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * 1.18.4 (D-503) — "Atualize seus dados": grava os dados pessoais do PRÓPRIO usuário (os mesmos campos e a mesma
 * validação do "Meu perfil", por AdmsEditarPerfil), DESLIGA a marca "Atualizar Dados" e registra no log quem, quando e
 * quais campos mudaram (nunca valores). Tudo numa transação: se uma parte falha, nada fica gravado e a marca continua.
 */
class AdmsAtualizarDadosPerfil
{
    private const CAMPOS = ['nome', 'email', 'genero_id', 'usuario', 'telefone', 'celular', 'imagem'];

    private bool $resultado = false;

    public function getResultado(): bool
    {
        return $this->resultado;
    }

    public function atualizar(array $dados): void
    {
        $id = (int) ($_SESSION['colUsuario_id'] ?? 0);
        $ler = function () use ($id) {
            $r = new \App\adms\Models\helper\AdmsRead();
            $r->fullRead('SELECT nome, email, genero_id, usuario, telefone, celular, imagem, adms_sits_usuario_id FROM adms_usuarios WHERE id = :id LIMIT :limit', "id={$id}&limit=1");
            return $r->getResultado()[0] ?? null;
        };
        $msgErro = null;
        $ok = \Core\Transacao::executar(function () use ($id, $dados, $ler, &$msgErro) {
            $antes = $ler();
            if (!$antes || (int) $antes['adms_sits_usuario_id'] !== \App\adms\Models\helper\AdmsAtualizarDados::SITUACAO) {
                $msgErro = 'A marca "Atualizar Dados" já não está ligada para você.';
                return false;
            }
            $perfil = new AdmsEditarPerfil();
            $perfil->altPerfil($dados); // mesma validação e gravação do "Meu perfil" (só os campos do formulário, D-498)
            if (!$perfil->getResultado()) {
                return false; // a mensagem de erro do perfil já está na sessão
            }
            $desliga = new \App\adms\Models\helper\AdmsUpdate();
            $desliga->exeUpdate('adms_usuarios', ['adms_sits_usuario_id' => 1, 'usuario_mod' => $id, 'modified' => date('Y-m-d H:i:s')], 'WHERE id=:id AND adms_sits_usuario_id=:sit', "id={$id}&sit=" . \App\adms\Models\helper\AdmsAtualizarDados::SITUACAO);
            $depois = $ler();
            if (!$desliga->getResultado() || !$depois || (int) $depois['adms_sits_usuario_id'] !== 1) {
                \Core\Transacao::falhar('não foi possível desligar a marca');
            }
            $mudaram = array_values(array_filter(self::CAMPOS, fn($c) => (string) ($antes[$c] ?? '') !== (string) ($depois[$c] ?? '')));
            $log = new \App\adms\Models\helper\AdmsSalvarLog();
            $log->salvarLogs(464, 200, 3, "Atualizar Dados: o usuário id={$id} atualizou os dados e a marca foi desligada. Campos alterados: " . ($mudaram ? implode(', ', $mudaram) : 'nenhum (confirmou os dados)') . '.');
            return true;
        });
        if ($ok) {
            $_SESSION['colUsuario_situacao'] = 1;
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='dadosAtualizados'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Dados atualizados. Bem-vindo(a)!</div>";
            $this->resultado = true;
            return;
        }
        if ($msgErro !== null || empty($_SESSION['msg']) || str_contains((string) $_SESSION['msg'], 'alert-success')) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: " . htmlspecialchars($msgErro ?? 'não foi possível salvar os dados. Nada foi gravado; tente de novo.', ENT_QUOTES, 'UTF-8') . '</div>';
        }
        $this->resultado = false;
    }
}
