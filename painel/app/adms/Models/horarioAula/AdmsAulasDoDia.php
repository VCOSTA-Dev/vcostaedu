<?php

namespace App\adms\Models\horarioAula;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsRead;

/**
 * AS AULAS DO DIA (Onda 8, correção, Parte 1.1; D-71): os horários de aula de cada turno (rm_professores_horario_aula:
 * o nome, o turno, o início e o fim). A instalação nasce com um horário padrão por turno (migração 0048), que a
 * secretaria edita aqui. O horário desativado sai da grade da turma e do portal; o que já foi montado nele fica guardado
 * e volta se ele for ativado de novo. Dois horários ativos do mesmo turno não se sobrepõem.
 */
class AdmsAulasDoDia
{
    public const NOME_MAX = 40;

    /** @return list<array> os turnos (rm_cursos_periodos), os ativos primeiro. */
    public static function turnos(): array
    {
        $l = new AdmsRead();
        $l->fullRead('SELECT id, nome, situacao FROM rm_cursos_periodos ORDER BY (situacao = 1) DESC, id');
        return $l->getResultado() ?: [];
    }

    /** @return array<int, list<array>> as aulas de cada turno (as desativadas também), pela hora de início. */
    public static function porTurno(): array
    {
        $l = new AdmsRead();
        $l->fullRead('SELECT id, nome, periodo, inicio, termino, situacao FROM rm_professores_horario_aula ORDER BY periodo, inicio, id');
        $saida = [];
        foreach ($l->getResultado() ?: [] as $a) {
            $saida[(int) $a['periodo']][] = $a;
        }
        return $saida;
    }

    /** @return list<array> as aulas ATIVAS de um turno, pela hora de início. */
    public static function ativas(int $turno): array
    {
        $l = new AdmsRead();
        $l->fullRead('SELECT id, nome, periodo, inicio, termino FROM rm_professores_horario_aula WHERE periodo = :p AND situacao = 1 ORDER BY inicio, id', "p={$turno}");
        return $l->getResultado() ?: [];
    }

    public static function uma(int $id): ?array
    {
        $l = new AdmsRead();
        $l->fullRead('SELECT id, nome, periodo, inicio, termino, situacao FROM rm_professores_horario_aula WHERE id = :id LIMIT :limit', "id={$id}&limit=1");
        return $l->getResultado()[0] ?? null;
    }

    /** "07:00" ou "07:00:00" → "07:00:00"; outra coisa → null. */
    private static function hora(string $x): ?string
    {
        $x = trim($x);
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $x, $m)) {
            return null;
        }
        return $m[1] . ':' . $m[2] . ':00';
    }

    /**
     * Cadastra (id 0) ou edita um horário de aula. @return string '' quando gravou; senão, o motivo.
     */
    public static function salvar(array $d, int $usuario): string
    {
        $id = (int) ($d['id'] ?? 0);
        $turno = (int) ($d['periodo'] ?? 0);
        $nome = trim((string) ($d['nome'] ?? ''));
        $inicio = self::hora((string) ($d['inicio'] ?? ''));
        $termino = self::hora((string) ($d['termino'] ?? ''));
        $turnos = array_column(self::turnos(), 'nome', 'id');
        if (!isset($turnos[$turno])) {
            return 'Escolha o turno.';
        }
        if ($nome === '' || mb_strlen($nome) > self::NOME_MAX) {
            return 'Dê um nome ao horário (por exemplo, "1ª aula"), com até ' . self::NOME_MAX . ' letras.';
        }
        if ($inicio === null || $termino === null) {
            return 'Informe o início e o fim no formato hora:minuto (por exemplo, 07:00).';
        }
        if ($termino <= $inicio) {
            return 'O fim tem de ser depois do início.';
        }
        $antes = $id ? self::uma($id) : null;
        if ($id && !$antes) {
            return 'Horário de aula não encontrado.';
        }
        /* dois horários ativos do mesmo turno não se sobrepõem (encostar pode: um termina às 07:50 e o outro começa às 07:50) */
        $l = new AdmsRead();
        $l->fullRead('SELECT nome, inicio, termino FROM rm_professores_horario_aula WHERE periodo = :p AND situacao = 1 AND id <> :id AND inicio < :fim AND termino > :ini LIMIT :limit',
            "p={$turno}&id={$id}&fim={$termino}&ini={$inicio}&limit=1");
        if ($c = ($l->getResultado()[0] ?? null)) {
            return 'Esse horário se sobrepõe a "' . $c['nome'] . '" (' . substr((string) $c['inicio'], 0, 5) . '–' . substr((string) $c['termino'], 0, 5) . ') no turno ' . $turnos[$turno] . '.';
        }
        $pdo = \App\adms\Models\escola\AdmsEscolaBanco::pdo();
        if ($id) {
            /* trocar o turno de um horário já usado na grade desmontaria as turmas daquele turno: recusa */
            if ((int) $antes['periodo'] !== $turno && self::usos($id) > 0) {
                return 'Esse horário já está no horário de alguma turma; para outro turno, cadastre um horário novo.';
            }
            $ok = \Core\Transacao::executar(static function () use ($nome, $turno, $inicio, $termino, $usuario, $id) {
                $t = \App\adms\Models\escola\AdmsEscolaBanco::pdo();
                $t->prepare('UPDATE rm_professores_horario_aula SET nome = ?, periodo = ?, inicio = ?, termino = ?, usuario_mod = ? WHERE id = ?')
                    ->execute([$nome, $turno, $inicio, $termino, $usuario, $id]);
                $t->prepare('UPDATE rm_professores_horarios SET periodo = ? WHERE horario_id = ?')->execute([$turno, $id]);
                return true;
            });
            if ($ok === false) {
                return 'Não foi possível gravar: ' . \Core\Transacao::ultimoErro() . '.';
            }
        } else {
            $pdo->prepare('INSERT INTO rm_professores_horario_aula (nome, periodo, inicio, termino, situacao, usuario_cre, created) VALUES (?, ?, ?, ?, 1, ?, NOW())')
                ->execute([$nome, $turno, $inicio, $termino, $usuario]);
            $id = (int) $pdo->lastInsertId();
        }
        $depois = ['nome' => $nome, 'turno' => $turno, 'inicio' => $inicio, 'termino' => $termino];
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(554, 200, $antes ? 3 : 2, 'Horário de aula ' . ($antes ? 'editado' : 'cadastrado') . ": id={$id}; antes="
            . json_encode($antes ? ['nome' => $antes['nome'], 'turno' => (int) $antes['periodo'], 'inicio' => $antes['inicio'], 'termino' => $antes['termino']] : null, JSON_UNESCAPED_UNICODE)
            . '; depois=' . json_encode($depois, JSON_UNESCAPED_UNICODE));
        return '';
    }

    /** Quantas aulas da grade (de todas as turmas e ofertas) usam o horário. */
    public static function usos(int $id): int
    {
        $l = new AdmsRead();
        $l->fullRead('SELECT COUNT(*) AS n FROM rm_professores_horarios WHERE horario_id = :h AND situacao = 1', "h={$id}");
        return (int) ($l->getResultado()[0]['n'] ?? 0);
    }

    /** Ativa (1) ou desativa (2). @return string '' quando gravou; senão, o motivo. */
    public static function situacao(int $id, int $sit, int $usuario): string
    {
        $a = self::uma($id);
        if (!$a) {
            return 'Horário de aula não encontrado.';
        }
        if (!in_array($sit, [1, 2], true)) {
            return 'Situação inválida.';
        }
        if ($sit === 1) {
            $l = new AdmsRead();
            $l->fullRead('SELECT nome FROM rm_professores_horario_aula WHERE periodo = :p AND situacao = 1 AND id <> :id AND inicio < :fim AND termino > :ini LIMIT :limit',
                "p={$a['periodo']}&id={$id}&fim={$a['termino']}&ini={$a['inicio']}&limit=1");
            if ($c = ($l->getResultado()[0] ?? null)) {
                return 'Não dá para ativar: o horário se sobrepõe a "' . $c['nome'] . '", que está ativo no mesmo turno.';
            }
        }
        \App\adms\Models\escola\AdmsEscolaBanco::pdo()->prepare('UPDATE rm_professores_horario_aula SET situacao = ?, usuario_mod = ? WHERE id = ?')->execute([$sit, $usuario, $id]);
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(555, 200, 3, 'Horário de aula ' . ($sit === 1 ? 'ativado' : 'desativado') . ": id={$id}; nome={$a['nome']}");
        return '';
    }
}
