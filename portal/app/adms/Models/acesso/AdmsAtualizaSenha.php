<?php

namespace App\adms\Models\acesso;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Definição de senha pelo link (primeiro acesso ou esqueci a senha), E2b.
 * O token vem SÓ da URL e é conferido pelo hash em col_usuarios_tokens:
 * uso único, com validade, e só o último emitido vale.
 */
class AdmsAtualizaSenha
{
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    /** Devolve os dados do token válido (id, aluno, finalidade, validade) ou null. */
    public function checarPermissao(string $token): ?array
    {
        return (new \App\adms\Models\helper\AdmsTokenAcesso())->validar($token);
    }

    public function trocarSenha(array $dados, string $token)
    {
        $senha = (string) ($dados['senha'] ?? '');
        if ($senha !== (string) ($dados['senha_repetido'] ?? '')) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>As duas senhas não conferem.</div>";
            $this->resultado = false;
            return;
        }
        $valSenha = new \App\adms\Models\helper\AdmsValSenha();
        $doToken = (new \App\adms\Models\helper\AdmsTokenAcesso())->validar($token);
        $valSenha->valSenha($senha, $doToken ? \App\adms\Models\helper\AdmsValSenha::pessoaisDoAluno((int) $doToken['col_usuario_id']) : []);
        if (!$valSenha->getResultado()) {
            $this->resultado = false;
            return;
        }
        if ((new \App\adms\Models\helper\AdmsTokenAcesso())->definirSenha($token, $senha)) {
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Senha definida. Entre com ela no portal.</div>";
            $this->resultado = true;
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Este link é inválido, já foi usado ou expirou. Peça um novo em <b>Esqueci a senha</b> ou procure a secretaria.</div>";
            $this->resultado = false;
        }
    }
}
