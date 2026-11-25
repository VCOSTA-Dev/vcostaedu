<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsRead;

/**
 * A ESTRUTURA DA ESCOLA (docs/ESCOLA.md): segmento = curso; série = módulo da matriz (com o rótulo dele, "6º ano",
 * "1ª série"); componente curricular = a matéria na linha da matriz, com a carga horária. Só leitura, pelas tabelas de
 * sempre; a coordenação vê o segmento que coordena.
 */
class AdmsEstruturaEscola
{
    /** @return list<array{id:int, nome:string, matrizes:list<array{id:int, versao:string, series:list<array{id:int, nome:string, componentes:list<array{nome:string, ch:string}>, horas:float}>}>}> */
    public function segmentos(): array
    {
        $escopo = \App\adms\Models\helper\AdmsEscopo::cursos();
        $ler = new AdmsRead();
        $ler->fullRead('SELECT id, nome, descricao FROM rm_cursos ORDER BY nome');
        $saida = [];
        foreach ($ler->getResultado() ?: [] as $c) {
            if ($escopo !== null && !in_array((int) $c['id'], $escopo, true)) {
                continue;
            }
            $saida[] = ['id' => (int) $c['id'], 'nome' => (string) $c['nome'], 'descricao' => (string) ($c['descricao'] ?? ''), 'matrizes' => $this->matrizes((int) $c['id'])];
        }
        return $saida;
    }

    private function matrizes(int $cursoId): array
    {
        $ler = new AdmsRead();
        $ler->fullRead('SELECT id, versao FROM rm_cursos_grades WHERE curso_id = :c ORDER BY id', "c={$cursoId}");
        $saida = [];
        foreach ($ler->getResultado() ?: [] as $g) {
            $series = new AdmsRead();
            $series->fullRead('SELECT id, codnome FROM rm_cursos_turma_series WHERE grade_id = :g ORDER BY ordem, id', 'g=' . (int) $g['id']);
            $lista = [];
            foreach ($series->getResultado() ?: [] as $s) {
                $comp = new AdmsRead();
                $comp->fullRead('SELECT m.nome, l.cargahoraria FROM uni_rh_cursos_disciplinas_grade l INNER JOIN rm_materias m ON m.id = l.materia_id
                    WHERE l.grade_id = :g AND l.modulo_id = :s ORDER BY l.ordem, m.nome', 'g=' . (int) $g['id'] . '&s=' . (int) $s['id']);
                $componentes = array_map(static fn($r) => ['nome' => (string) $r['nome'], 'ch' => (string) $r['cargahoraria']], $comp->getResultado() ?: []);
                $lista[] = ['id' => (int) $s['id'], 'nome' => (string) $s['codnome'], 'componentes' => $componentes,
                    'horas' => array_sum(array_map(static fn($x) => (float) str_replace(',', '.', $x['ch']), $componentes))];
            }
            $saida[] = ['id' => (int) $g['id'], 'versao' => (string) $g['versao'], 'series' => $lista];
        }
        return $saida;
    }
}
