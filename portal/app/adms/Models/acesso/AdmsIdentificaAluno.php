<?php

namespace App\adms\Models\acesso;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Encontra o aluno pelo campo de login escolhido pela instituição (D-32):
 *   ra    → col_usuarios.rm (só dígitos)
 *   cpf   → col_usuarios_informacoes.cpf (só dígitos; aceita com ou sem máscara)
 *   email → col_usuarios.email
 * Só alunos (nível acadêmico 1). Usado pelo login e pelo "esqueci a senha".
 */
class AdmsIdentificaAluno
{
    public static function normalizar(string $campo, string $valor): string
    {
        $valor = trim($valor);
        return in_array($campo, ['ra', 'cpf'], true) ? preg_replace('/\D/', '', $valor) : $valor;
    }

    /** Fechamento (D-107): o aluno pelo id (a entrada pelo e-mail já achou a conta), com as mesmas colunas de buscar(). */
    public static function porId(int $id): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT u.id, u.nome, u.email, u.senha, u.imagem, u.col_niveis_acesso_id, u.col_sits_usuario_id, nivac.ordem ordem_nivac
                          FROM col_usuarios u
                          INNER JOIN col_niveis_acessos nivac ON nivac.id = u.col_niveis_acesso_id
                         WHERE u.col_niveis_acesso_id = 1 AND u.id = :id LIMIT :limit", "id={$id}&limit=1");
        return $ler->getResultado()[0] ?? null;
    }

    public static function buscar(string $campo, string $valor): ?array
    {
        $valor = self::normalizar($campo, $valor);
        if ($valor === '' || ($campo === 'email' && !filter_var($valor, FILTER_VALIDATE_EMAIL))) {
            return null;
        }
        $sql = "SELECT u.id, u.nome, u.email, u.senha, u.imagem, u.col_niveis_acesso_id, u.col_sits_usuario_id, nivac.ordem ordem_nivac
                  FROM col_usuarios u
                  INNER JOIN col_niveis_acessos nivac ON nivac.id = u.col_niveis_acesso_id
                  LEFT JOIN col_usuarios_informacoes i ON i.col_user_id = u.id
                 WHERE u.col_niveis_acesso_id = 1 AND ";
        $sql .= ['ra' => 'u.rm = :v', 'cpf' => 'i.cpf = :v', 'email' => 'u.email = :v'][$campo] ?? 'u.rm = :v';
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead($sql . " LIMIT :limit", "v=" . urlencode($valor) . "&limit=1");
        return $ler->getResultado()[0] ?? null;
    }
}
