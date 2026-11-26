<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) { // E6 (D-376): o portal também carrega (atestado do aluno)
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * E8c (D-268) — MÓDULO INTERNO da grade. Para a instituição de origem não existe módulo: com o parâmetro "Organizar a matriz em
 * módulos" em Não, cada grade tem UM módulo (rm_cursos_turma_series), criado e mantido aqui e nunca exibido. As
 * ligações por modulo_id (linha da matriz, vínculo, oferta, boletim, histórico) seguem valendo sem migração.
 *
 * O interno é o primeiro módulo ATIVO da grade pela ordem (numa grade que já tinha vários módulos, as disciplinas dos
 * outros continuam onde estão e aparecem na mesma lista). Se a grade não tiver módulo, cria "Disciplinas" (ordem 1).
 * Não passa pela regra "só grade Aberta muda" de propósito: é estrutura técnica, não mudança da matriz.
 */
class AdmsModuloInterno
{
    public const NOME = 'Geral'; // E8d (P-9): com "Organizar em módulos" = Sim, o interno aparece com este nome, editável

    public static function ativo(): bool
    {
        return !AdmsParametro::usaModulos();
    }

    /** Id do módulo interno da grade (cria se não houver); 0 se a grade não existe. */
    public static function garantir(int $gradeId): int
    {
        $ler = new AdmsRead();
        $ler->fullRead("SELECT id FROM rm_cursos_turma_series WHERE grade_id = :g ORDER BY situacao = 1 DESC, ordem, id LIMIT :limit", "g={$gradeId}&limit=1");
        $id = (int) ($ler->getResultado()[0]['id'] ?? 0);
        if ($id > 0) {
            return $id;
        }
        $ler = new AdmsRead();
        $ler->fullRead("SELECT id, curso_id FROM rm_cursos_grades WHERE id = :g LIMIT :limit", "g={$gradeId}&limit=1");
        $g = $ler->getResultado()[0] ?? null;
        if (!$g) {
            return 0;
        }
        $novo = new AdmsCreate();
        $novo->exeCreate('rm_cursos_turma_series', [
            'curso_id' => (int) $g['curso_id'],
            'grade_id' => $gradeId,
            'periodo' => 1,
            'codnome' => self::NOME,
            'descricao' => 'Módulo interno da grade (a matriz não é organizada em módulos). Com módulos, é o módulo Geral.',
            'ordem' => 1,
            'situacao' => 1,
            'usuario_cre' => (int) ($_SESSION['colUsuario_id'] ?? 0),
        ]);
        return (int) $novo->getResultado();
    }

    /** Ids de todos os módulos da grade (a lista única junta as disciplinas de todos). */
    public static function daGrade(int $gradeId): array
    {
        $ler = new AdmsRead();
        $ler->fullRead("SELECT id FROM rm_cursos_turma_series WHERE grade_id = :g ORDER BY ordem", "g={$gradeId}");
        return array_map('intval', array_column($ler->getResultado() ?: [], 'id'));
    }
}
