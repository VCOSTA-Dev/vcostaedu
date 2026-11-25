<?php

namespace App\adms\Models\helper;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 5) . '/core/Senha.php';

/**
 * A conferência da senha no servidor — a casca do sistema para a regra ÚNICA (core/Senha.php: mínimo de 12
 * caracteres, sem a lista local de senhas comuns, sem o nome, o usuário ou o e-mail). Onda 2, Etapa 1: antes eram
 * 8 caracteres, sem espaço e sem aspas (a frase com espaços agora é aceita: a senha é guardada pelo password_hash).
 * O painel e o portal têm a mesma classe; só a guarda muda.
 */
class AdmsValSenha
{
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    /**
     * @param array<string,string> $pessoais ['nome' => ..., 'usuario' => ..., 'email' => ...]
     */
    public function valSenha($senha, array $pessoais = []): bool
    {
        $erros = \VcSenha::problemas((string) $senha, $pessoais);
        if ($erros !== []) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible' id='senhaRecusada'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
                . implode('<br>', array_map(static fn(string $e): string => htmlspecialchars($e, ENT_QUOTES, 'UTF-8'), $erros)) . '</div>';
        }

        return $this->resultado = ($erros === []);
    }

    /** Os dados pessoais de um usuário da equipe (adms_usuarios), para a conferência. */
    public static function pessoaisDoUsuario(int $id): array
    {
        $ler = new AdmsRead();
        $ler->fullRead("SELECT nome, apelido, usuario, email FROM adms_usuarios WHERE id =:id LIMIT :limit", "id={$id}&limit=1");
        $r = $ler->getResultado()[0] ?? [];

        return ['nome' => (string) ($r['nome'] ?? ''), 'apelido' => (string) ($r['apelido'] ?? ''), 'usuario' => (string) ($r['usuario'] ?? ''), 'email' => (string) ($r['email'] ?? '')];
    }

    /** Os dados pessoais de um aluno (col_usuarios), para a conferência. */
    public static function pessoaisDoAluno(int $id): array
    {
        $ler = new AdmsRead();
        $ler->fullRead("SELECT nome, apelido, usuario, email, rm FROM col_usuarios WHERE id =:id LIMIT :limit", "id={$id}&limit=1");
        $r = $ler->getResultado()[0] ?? [];

        return ['nome' => (string) ($r['nome'] ?? ''), 'apelido' => (string) ($r['apelido'] ?? ''), 'usuario' => (string) ($r['usuario'] ?? ''), 'email' => (string) ($r['email'] ?? ''), 'rm' => (string) ($r['rm'] ?? '')];
    }
}
