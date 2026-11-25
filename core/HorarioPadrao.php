<?php
/*
 * O HORÁRIO PADRÃO DE AULAS (Onda 8, correção; D-71 e D-86): as aulas do dia de cada turno com que a ESCOLA nasce, que
 * a secretaria edita em Horários de aula. Só na escola: o ensino superior não recebe essas aulas (a régua do superior
 * continua a mesma). Quem grava o tipo da instituição chama aplicar(): o instalador e a troca de tipo pelo painel
 * (AdmsTipoInstituicao::gravar); a migration 0048 faz o mesmo nas escolas que já existiam.
 *   - escola: grava o padrão nos turnos que ainda não têm nenhuma aula;
 *   - superior: tira as aulas do padrão que ninguém editou (usuario_mod vazio, os mesmos nome, turno, início e fim) e
 *     que nenhum horário de professor usa; o que a instituição cadastrou fica.
 */
final class VcHorarioPadrao
{
    /** [nome, turno, início, fim] */
    public const AULAS = [
        ['1ª aula', 1, '07:00:00', '07:50:00'], ['2ª aula', 1, '07:50:00', '08:40:00'], ['3ª aula', 1, '08:40:00', '09:30:00'],
        ['4ª aula', 1, '09:50:00', '10:40:00'], ['5ª aula', 1, '10:40:00', '11:30:00'], ['6ª aula', 1, '11:30:00', '12:20:00'],
        ['1ª aula', 2, '13:00:00', '13:50:00'], ['2ª aula', 2, '13:50:00', '14:40:00'], ['3ª aula', 2, '14:40:00', '15:30:00'],
        ['4ª aula', 2, '15:50:00', '16:40:00'], ['5ª aula', 2, '16:40:00', '17:30:00'], ['6ª aula', 2, '17:30:00', '18:20:00'],
        ['1ª aula', 3, '19:00:00', '19:50:00'], ['2ª aula', 3, '19:50:00', '20:40:00'], ['3ª aula', 3, '20:50:00', '21:40:00'],
        ['4ª aula', 3, '21:40:00', '22:30:00'],
    ];

    public static function aplicar(\PDO $pdo, string $tipo): void
    {
        try {
            $pdo->query('SELECT 1 FROM rm_professores_horario_aula LIMIT 1');
        } catch (\Throwable $e) {
            return; // a tabela ainda não existe (antes da estrutura)
        }
        if ($tipo === 'escola') {
            $tem = $pdo->query('SELECT DISTINCT periodo FROM rm_professores_horario_aula')->fetchAll(\PDO::FETCH_COLUMN);
            $tem = array_map('intval', $tem);
            $ins = $pdo->prepare('INSERT INTO rm_professores_horario_aula (nome, periodo, inicio, termino, situacao, usuario_cre, created) VALUES (?, ?, ?, ?, 1, 1, NOW())');
            foreach (self::AULAS as [$nome, $turno, $inicio, $fim]) {
                if (!in_array($turno, $tem, true)) {
                    $ins->execute([$nome, $turno, $inicio, $fim]);
                }
            }
            return;
        }
        $del = $pdo->prepare('DELETE FROM rm_professores_horario_aula WHERE nome = ? AND periodo = ? AND inicio = ? AND termino = ? AND usuario_mod IS NULL
            AND NOT EXISTS (SELECT 1 FROM rm_professores_horarios h WHERE h.horario_id = rm_professores_horario_aula.id)');
        foreach (self::AULAS as $a) {
            $del->execute($a);
        }
    }
}
