<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * E8c (R4, D-270) — "está em uso?" de cadastros que o sistema apagava de verdade (DELETE) sem conferir nada.
 * A MESMA razão serve à lista (botão Apagar desabilitado, com a razão na dica) e ao servidor (recusa).
 * Devolve null quando pode apagar; senão, a frase da razão.
 */
class AdmsUsos
{
    private static function conta(string $sql, string $params): int
    {
        $ler = new AdmsRead();
        $ler->fullRead($sql, $params);
        return (int) ($ler->getResultado()[0]['n'] ?? 0);
    }

    private static function frase(array $partes): ?string
    {
        $partes = array_filter($partes);
        return $partes ? 'Em uso: ' . implode(', ', $partes) . '. Não pode ser apagado.' : null;
    }

    /** Curso: grades, matrículas de alunos e ofertas. */
    public static function curso(int $id): ?string
    {
        $g = self::conta("SELECT COUNT(*) AS n FROM rm_cursos_grades WHERE curso_id = :id", "id={$id}");
        $m = self::conta("SELECT COUNT(*) AS n FROM rm_matriculas_curso WHERE curso_id = :id", "id={$id}");
        $o = self::conta("SELECT COUNT(*) AS n FROM rm_professores_materias WHERE curso_id = :id", "id={$id}");
        return self::frase([$g ? "{$g} grade(s)" : '', $m ? "{$m} matrícula(s)" : '', $o ? "{$o} oferta(s)" : '']);
    }

    /** Matéria do catálogo: linhas de matriz e ofertas. */
    public static function materia(int $id): ?string
    {
        $l = self::conta("SELECT COUNT(*) AS n FROM uni_rh_cursos_disciplinas_grade WHERE materia_id = :id", "id={$id}");
        $o = self::conta("SELECT COUNT(*) AS n FROM rm_professores_materias WHERE codmat = :id", "id={$id}");
        return self::frase([$l ? "{$l} grade(s) na matriz" : '', $o ? "{$o} oferta(s)" : '']);
    }
}
