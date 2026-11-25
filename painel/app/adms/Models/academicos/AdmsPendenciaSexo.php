<?php

namespace App\adms\Models\academicos;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * PENDÊNCIA DO SEXO DO ALUNO (E1b, D-356). Até a 1.10.0 o cadastro gravava "Outros" (3) quando ninguém escolhia —
 * o padrão silencioso. Os documentos agora concordam com o sexo (ALUNA/ALUNO), então a secretaria precisa conferir
 * esses alunos. Pendente = aluno (nível 1) com sexo fora de Feminino/Masculino e que NUNCA teve o sexo escolhido na
 * tela (`genero_confirmado_em` vazio). Ao salvar a ficha (o campo agora é obrigatório), ele sai da lista.
 * Nenhum dado existente é alterado em lote.
 */
class AdmsPendenciaSexo
{
    private const WHERE = "u.col_niveis_acesso_id = 1 AND u.genero_confirmado_em IS NULL AND (u.genero_id IS NULL OR u.genero_id NOT IN (1, 2))";

    public static function listar(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT u.id, u.rm, u.nome, u.genero_id, g.nome AS genero, s.nome AS situacao, u.created
               FROM col_usuarios u
               LEFT JOIN adms_generos g ON g.id = u.genero_id
               LEFT JOIN col_situacao s ON s.id = u.col_sits_usuario_id
              WHERE " . self::WHERE . "
              ORDER BY u.nome"
        );
        return $ler->getResultado() ?: [];
    }

    public static function contar(): int
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT COUNT(*) AS n FROM col_usuarios u WHERE " . self::WHERE);
        return (int) ($ler->getResultado()[0]['n'] ?? 0);
    }
}
