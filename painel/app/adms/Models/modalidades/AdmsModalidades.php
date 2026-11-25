<?php

namespace App\adms\Models\modalidades;

if (!defined('VCOSTAEDU')) { // E6 (D-376): o portal também carrega (atestado do aluno)
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * MODALIDADE (D-35, E3b): domínio uni_rh_cursos_modalidade (Presencial, EaD, Híbrido).
 * O curso tem a modalidade PADRÃO (rm_cursos.modalidade_id); a oferta tem a sua
 * (rm_professores_materias.modalidade_id), herdada do curso. O ato legal do
 * histórico (E6) será escolhido pela modalidade da oferta cursada.
 */
class AdmsModalidades
{
    public static function ativas(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nome FROM uni_rh_cursos_modalidade WHERE status = :s ORDER BY id", "s=1");
        return $ler->getResultado() ?: [];
    }

    /** E8d (P-2): a modalidade é EaD? (pelo nome, "EaD"; o turno não se aplica e fica escondido) */
    public static function ehEad(?int $id): bool
    {
        return $id !== null && $id > 0 && in_array($id, self::idsEad(), true);
    }

    /** Ids das modalidades EaD (para o JavaScript esconder o turno). */
    public static function idsEad(): array
    {
        static $ids = null;
        if ($ids === null) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT id FROM uni_rh_cursos_modalidade WHERE LOWER(nome) = :n", "n=ead");
            $ids = array_map('intval', array_column($ler->getResultado() ?: [], 'id'));
        }
        return $ids;
    }

    /** Turnos (rm_cursos_periodos) ativos: id => nome. */
    public static function turnos(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nome FROM rm_cursos_periodos ORDER BY id");
        $o = [];
        foreach ($ler->getResultado() ?: [] as $t) {
            $o[(int) $t['id']] = $t['nome'] === 'Verspertino' ? 'Vespertino' : $t['nome'];
        }
        return $o;
    }

    public static function existe(int $id): bool
    {
        foreach (self::ativas() as $m) {
            if ((int) $m['id'] === $id) {
                return true;
            }
        }
        return false;
    }

    /** Modalidade padrão do curso (1 = Presencial se o curso não existir). */
    public static function doCurso(int $cursoId): int
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT modalidade_id FROM rm_cursos WHERE id = :id LIMIT :limit", "id={$cursoId}&limit=1");
        return (int) ($ler->getResultado()[0]['modalidade_id'] ?? 1);
    }

    /** E6a: nome da modalidade, ou null. */
    public static function nome(int $id): ?string
    {
        foreach (self::ativas() as $m) {
            if ((int) $m['id'] === $id) {
                return (string) $m['nome'];
            }
        }
        return null;
    }
}
