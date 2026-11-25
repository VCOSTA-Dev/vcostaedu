<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsRead;

/**
 * O ALUNO NA TURMA (docs/ESCOLA.md, D-47).
 *   - ENTRAR: o aluno com matrícula Cursando no segmento (a matriz da turma) entra na turma e é enturmado em TODAS as
 *     ofertas dela, de uma vez — a posição no ano vai para a série da turma (AdmsPosicionarModulo::gravarPosicao) e a
 *     enturmação passa pela mesma confirmação de sempre (AdmsConfirmarEnturmacao: a transação, as linhas de nota, o
 *     Moodle). A capacidade da turma é respeitada.
 *   - SAIR: sai de todas as ofertas da turma. O que já foi lançado (as notas de cada atividade, as faltas) fica guardado
 *     numa cópia (esc_lancamentos_trazidos, sem destino); as linhas de nota continuam no banco, como histórico.
 *   - TROCAR de turma na MESMA série: o lançado em cada componente vai junto — a cópia fica registrada para a oferta do
 *     mesmo componente na turma nova (esc_lancamentos_trazidos), e o cálculo das médias a usa.
 */
class AdmsTurmaAlunos
{
    /** Os alunos na turma (os que estão nela agora). */
    public static function alunos(int $turmaId): array
    {
        $ler = new AdmsRead();
        $ler->fullRead('SELECT a.id, a.usuario_id, a.rm, a.entrada, u.nome FROM esc_turma_alunos a INNER JOIN col_usuarios u ON u.id = a.usuario_id
            WHERE a.turma_id = :t AND a.saida IS NULL ORDER BY u.nome', "t={$turmaId}");
        return $ler->getResultado() ?: [];
    }

    /** A turma em que o aluno está no ano (ou null). */
    public static function turmaDoAluno(int $usuarioId, int $ano): ?array
    {
        $ler = new AdmsRead();
        $ler->fullRead('SELECT t.id, t.nome, t.modulo_id FROM esc_turma_alunos a INNER JOIN esc_turmas t ON t.id = a.turma_id
            WHERE a.usuario_id = :u AND t.ano = :a AND a.saida IS NULL LIMIT :limit', "u={$usuarioId}&a={$ano}&limit=1");
        return $ler->getResultado()[0] ?? null;
    }

    /**
     * Onda 8, correção, segunda leva, 2.2 (D-88): as turmas do aluno, uma por ano letivo (a atual, sem saída; senão, a
     * última de que saiu), com o turno e a sala — a aba Matrículas da ficha do aluno na escola.
     * @return array<int, array{id: int, nome: string, turno: ?string, sala: ?string}>
     */
    public static function turmasDoAluno(int $usuarioId): array
    {
        $ler = new AdmsRead();
        $ler->fullRead('SELECT t.id, t.ano, t.nome, t.sala, p.nome AS turno, a.saida FROM esc_turma_alunos a INNER JOIN esc_turmas t ON t.id = a.turma_id
              LEFT JOIN rm_cursos_periodos p ON p.id = t.turno_id
             WHERE a.usuario_id = :u ORDER BY t.ano, (a.saida IS NULL), a.id', "u={$usuarioId}");
        $porAno = [];
        foreach ($ler->getResultado() ?: [] as $t) {
            $porAno[(int) $t['ano']] = ['id' => (int) $t['id'], 'nome' => (string) $t['nome'], 'turno' => $t['turno'], 'sala' => $t['sala']];
        }
        return $porAno;
    }

    /** D-102 (C8): as turmas abertas dos anos letivos, para escolher no mesmo passo da matrícula (a tela filtra pela série e
        pelo ano escolhidos). */
    public static function turmasParaMatricula(): array
    {
        $ler = new AdmsRead();
        $ler->fullRead('SELECT t.id, t.ano, t.modulo_id, CONCAT(IFNULL(ms.codnome, \'\'), \' \', t.nome) AS codtur, tn.nome AS turno FROM esc_turmas t
              LEFT JOIN rm_cursos_turma_series ms ON ms.id = t.modulo_id LEFT JOIN rm_cursos_periodos tn ON tn.id = t.turno_id
             WHERE t.situacao = 1 ORDER BY t.ano DESC, ms.codnome, t.nome');
        return $ler->getResultado() ?: [];
    }

    /** Quem pode entrar: matrícula Cursando no segmento e na matriz da turma, sem turma no ano. Correção da escola, Parte 3
        (B13; D-102): e matriculado NA SÉRIE E NO ANO da turma (a posição do aluno no ano letivo) — antes, os de outras séries
        e de outros anos apareciam também, só ordenados depois. */
    public static function candidatos(array $turma): array
    {
        $ler = new AdmsRead();
        $ler->fullRead('SELECT u.id AS usuario_id, u.rm, u.nome, me.modulo_id, me.ano, ms.codnome AS serie_atual
              FROM rm_matriculas_curso mc INNER JOIN col_usuarios u ON u.id = mc.usuario_id
              INNER JOIN rm_matriculas_ensino me ON me.matricula_curso_id = mc.id AND me.ano = :a AND me.modulo_id = :m2
              LEFT JOIN rm_cursos_turma_series ms ON ms.id = me.modulo_id
             WHERE mc.curso_id = :c AND mc.grade_id = :g AND mc.situacao_id = 1
               AND NOT EXISTS (SELECT 1 FROM esc_turma_alunos x INNER JOIN esc_turmas t ON t.id = x.turma_id
                                WHERE x.usuario_id = u.id AND t.ano = :b AND x.saida IS NULL)
             ORDER BY (me.modulo_id = :m) DESC, u.nome',
            "a={$turma['ano']}&m2={$turma['modulo_id']}&c={$turma['curso_id']}&g={$turma['grade_id']}&b={$turma['ano']}&m={$turma['modulo_id']}");
        return $ler->getResultado() ?: [];
    }

    /**
     * Põe o aluno na turma: a posição no ano na série da turma e a enturmação em todas as ofertas dela.
     * @return true|string
     */
    public static function entrar(int $turmaId, int $usuarioId)
    {
        $t = AdmsTurmas::ver($turmaId);
        if (!$t) {
            return 'Turma não encontrada.';
        }
        if (self::turmaDoAluno($usuarioId, (int) $t['ano'])) {
            return 'O aluno já está numa turma neste ano (para mudar, use "Trocar de turma").';
        }
        if ($t['capacidade'] !== null && count(self::alunos($turmaId)) >= (int) $t['capacidade']) {
            return "A turma {$t['codtur']} está completa ({$t['capacidade']} alunos).";
        }
        $ler = new AdmsRead();
        $ler->fullRead('SELECT mc.id, mc.forma_ingresso_id, u.rm, u.nome FROM rm_matriculas_curso mc INNER JOIN col_usuarios u ON u.id = mc.usuario_id
            WHERE mc.usuario_id = :u AND mc.curso_id = :c AND mc.grade_id = :g AND mc.situacao_id = 1 LIMIT :limit',
            "u={$usuarioId}&c={$t['curso_id']}&g={$t['grade_id']}&limit=1");
        $mc = $ler->getResultado()[0] ?? null;
        if (!$mc) {
            return 'O aluno não tem matrícula Cursando neste segmento (vincule o aluno antes).';
        }
        $serie = \App\adms\Models\vinculo\AdmsVinculoRegras::modulo((int) $t['modulo_id']);
        $ofertas = array_map(static fn($o) => (int) $o['id'], AdmsTurmas::ofertas($turmaId));
        $msgAntes = $_SESSION['msg'] ?? null;
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $mc, $usuarioId, $t, $serie, $ofertas, $turmaId) {
            if (!(new \App\adms\Models\vinculo\AdmsPosicionarModulo())->gravarPosicao((int) $mc['id'], $usuarioId, (int) $t['ano'], $serie, 1, (int) $mc['forma_ingresso_id'])) {
                $T::falhar('a posição no ano');
            }
            $l = new AdmsRead();
            $l->fullRead('SELECT id FROM rm_matriculas_ensino WHERE matricula_curso_id = :mc AND ano = :a LIMIT :limit', "mc={$mc['id']}&a={$t['ano']}&limit=1");
            $pos = (int) ($l->getResultado()[0]['id'] ?? 0);
            $conf = new \App\adms\Models\portalAlunos\AdmsConfirmarEnturmacao();
            $conf->confirmar($pos, $ofertas);
            if (!$conf->getResultado()) {
                $T::falhar(strip_tags((string) ($_SESSION['msg'] ?? 'a enturmação')));
            }
            AdmsEscolaBanco::pdo()->prepare('INSERT INTO esc_turma_alunos (turma_id, usuario_id, rm, entrada, por) VALUES (?, ?, ?, NOW(), ?)')
                ->execute([$turmaId, $usuarioId, (string) $mc['rm'], (int) ($_SESSION['colUsuario_id'] ?? 0)]);
            return true;
        });
        if ($msgAntes === null) {
            unset($_SESSION['msg']);
        } else {
            $_SESSION['msg'] = $msgAntes;
        }
        return $ok ? true : "{$mc['nome']}: não entrou na turma (" . $T::ultimoErro() . ').';
    }

    /** O que o aluno tem lançado numa oferta: as notas de cada atividade e as faltas. */
    public static function retrato(string $rm, int $ofertaId): array
    {
        $ler = new AdmsRead();
        $ler->fullRead("SELECT a.id, a.nomeAtividade AS nome, a.data_final AS data, a.peso, a.componente_codigo AS componente, n.nota
              FROM rm_disciplina_atividade a LEFT JOIN rm_disciplina_atividade_notas n ON n.atividade_id = a.id AND n.rm = :rm
             WHERE a.rm_prof_mat_id = :o ORDER BY a.data_final, a.id", "rm={$rm}&o={$ofertaId}");
        $atividades = $ler->getResultado() ?: [];
        $ler = new AdmsRead();
        $ler->fullRead('SELECT faltas, faltas_compensadas FROM rm_disciplina_faltas WHERE rm = :rm AND rm_prof_mat_id = :o LIMIT :limit', "rm={$rm}&o={$ofertaId}&limit=1");
        $f = $ler->getResultado()[0] ?? [];
        return ['atividades' => $atividades, 'faltas' => (int) ($f['faltas'] ?? 0), 'compensadas' => (int) ($f['faltas_compensadas'] ?? 0)];
    }

    /** Tira o aluno das ofertas da turma, guardando o lançado (para a turma nova, ou sem destino). */
    private static function tirar(array $t, int $usuarioId, string $rm, ?array $destino): void
    {
        $pdo = AdmsEscolaBanco::pdo();
        $porLinha = [];
        if ($destino) {
            foreach (AdmsTurmas::ofertas((int) $destino['id']) as $o) {
                $l = new AdmsRead();
                $l->fullRead('SELECT matriz_id FROM rm_professores_materias WHERE id = :id LIMIT :limit', "id={$o['id']}&limit=1");
                $porLinha[(int) ($l->getResultado()[0]['matriz_id'] ?? 0)] = (int) $o['id'];
            }
        }
        foreach (AdmsTurmas::ofertas((int) $t['id']) as $o) {
            $r = self::retrato($rm, (int) $o['id']);
            $l = new AdmsRead();
            $l->fullRead('SELECT matriz_id FROM rm_professores_materias WHERE id = :id LIMIT :limit', "id={$o['id']}&limit=1");
            $linha = (int) ($l->getResultado()[0]['matriz_id'] ?? 0);
            $comNota = array_filter($r['atividades'], static fn($a) => $a['nota'] !== null && $a['nota'] !== '');
            if ($comNota || $r['faltas'] > 0) {
                $pdo->prepare('INSERT INTO esc_lancamentos_trazidos (oferta_destino_id, oferta_origem_id, rm, notas, faltas, compensadas, em, por) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)')
                    ->execute([$porLinha[$linha] ?? null, (int) $o['id'], $rm, json_encode(array_values($comNota), JSON_UNESCAPED_UNICODE), $r['faltas'], $r['compensadas'], (int) ($_SESSION['colUsuario_id'] ?? 0)]);
            }
            $pdo->prepare('DELETE FROM portal_disciplinas_alunos WHERE rm = ? AND rm_prof_mat_id = ?')->execute([$rm, (int) $o['id']]);
            \App\adms\Models\moodle\AdmsMoodle::anotar('desenturmar', $usuarioId, (int) $o['id']);
        }
    }

    /** O aluno sai da turma (de todas as ofertas dela). @return true|string */
    public static function sair(int $turmaId, int $usuarioId)
    {
        $t = AdmsTurmas::ver($turmaId);
        $na = $t ? self::turmaDoAluno($usuarioId, (int) $t['ano']) : null;
        if (!$t || !$na || (int) $na['id'] !== $turmaId) {
            return 'O aluno não está nesta turma.';
        }
        $rm = (string) (self::alunoNaTurma($turmaId, $usuarioId)['rm'] ?? '');
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($t, $usuarioId, $rm, $turmaId) {
            self::tirar($t, $usuarioId, $rm, null);
            AdmsEscolaBanco::pdo()->prepare('UPDATE esc_turma_alunos SET saida = NOW() WHERE turma_id = ? AND usuario_id = ? AND saida IS NULL')->execute([$turmaId, $usuarioId]);
            return true;
        });
        return $ok ? true : 'O aluno não saiu da turma (' . $T::ultimoErro() . ').';
    }

    /** Troca o aluno de turma, na mesma série: o lançado vai junto. @return true|string */
    public static function trocar(int $turmaId, int $usuarioId, int $destinoId)
    {
        $t = AdmsTurmas::ver($turmaId);
        $d = AdmsTurmas::ver($destinoId);
        if (!$t || !$d || $turmaId === $destinoId) {
            return 'Escolha a turma de destino.';
        }
        if ((int) $d['modulo_id'] !== (int) $t['modulo_id'] || (int) $d['ano'] !== (int) $t['ano']) {
            return 'A troca é entre turmas da mesma série, no mesmo ano.';
        }
        $aluno = self::alunoNaTurma($turmaId, $usuarioId);
        if (!$aluno) {
            return 'O aluno não está nesta turma.';
        }
        if ($d['capacidade'] !== null && count(self::alunos($destinoId)) >= (int) $d['capacidade']) {
            return "A turma {$d['codtur']} está completa ({$d['capacidade']} alunos).";
        }
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $t, $d, $usuarioId, $aluno, $turmaId, $destinoId) {
            self::tirar($t, $usuarioId, (string) $aluno['rm'], $d);
            AdmsEscolaBanco::pdo()->prepare('UPDATE esc_turma_alunos SET saida = NOW(), destino_turma_id = ? WHERE turma_id = ? AND usuario_id = ? AND saida IS NULL')
                ->execute([$destinoId, $turmaId, $usuarioId]);
            $r = self::entrar($destinoId, $usuarioId);
            if ($r !== true) {
                $T::falhar($r);
            }
            return true;
        });
        return $ok ? true : 'A troca não foi feita (' . $T::ultimoErro() . ').';
    }

    private static function alunoNaTurma(int $turmaId, int $usuarioId): ?array
    {
        $ler = new AdmsRead();
        $ler->fullRead('SELECT id, rm FROM esc_turma_alunos WHERE turma_id = :t AND usuario_id = :u AND saida IS NULL LIMIT :limit', "t={$turmaId}&u={$usuarioId}&limit=1");
        return $ler->getResultado()[0] ?? null;
    }

    /** O lançado trazido de outra turma para esta oferta (o cálculo das médias usa). */
    public static function trazidos(int $ofertaId, string $rm): array
    {
        $ler = new AdmsRead();
        $ler->fullRead('SELECT oferta_origem_id, notas, faltas, compensadas, em FROM esc_lancamentos_trazidos WHERE oferta_destino_id = :o AND rm = :rm ORDER BY id', "o={$ofertaId}&rm={$rm}");
        return array_map(static function ($r) {
            $r['notas'] = json_decode((string) $r['notas'], true) ?: [];
            return $r;
        }, $ler->getResultado() ?: []);
    }
}
