<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-AUD-02 Trocas de regra de média (docs/RELATORIOS.md, H; onda 2). */
class RelTrocasRegra extends AdmsRelatorioBase
{
    public const CODIGO = 'R-AUD-02';
    public const NOME = 'Trocas e edições de regra de média';
    public const CATEGORIA = 'H';
    public const PERGUNTA = 'Quem mudou a regra de média de uma oferta ou do catálogo, quando e por quê?';
    public const SLUG = 'trocas-regra';
    public const QUEM = 'administrador';

    public function filtros(): array
    {
        return ['curso', 'de' => ['rotulo' => 'De', 'tipo' => 'data'], 'ate' => ['rotulo' => 'até', 'tipo' => 'data']];
    }

    public function ficha(): array
    {
        return [
            'Trocas de regra por oferta: rm_media_regra_trocas (regra anterior, nova, justificativa, quem e quando).',
            'Edições e criações de regra no catálogo: o registro do sistema (adms_logs) das telas "Nova regra de média" e "Editar regra de média".',
            'O período filtra a data do registro. O filtro de curso vale só para as trocas por oferta.',
        ];
    }

    public function dados(array $f): array
    {
        $w = ['1 = 1'];
        $p = [];
        if (!empty($f['curso'])) {
            $w[] = 'o.curso_id = :curso';
            $p['curso'] = (int) $f['curso'];
        }
        foreach (['de' => '>=', 'ate' => '<='] as $k => $op) {
            if (!empty($f[$k])) {
                $w[] = "t.created {$op} :{$k}";
                $p[$k] = $f[$k] . ($k === 'de' ? ' 00:00:00' : ' 23:59:59');
            }
        }
        $trocas = [];
        foreach (F::ler(
            "SELECT t.*, o.id AS oid, o.materia, o.codtur, o.ano, c.nome AS curso, ra.nome AS de, rn.nome AS para, u.nome AS usuario
               FROM rm_media_regra_trocas t INNER JOIN rm_professores_materias o ON o.id = t.rm_prof_mat_id LEFT JOIN rm_cursos c ON c.id = o.curso_id
               LEFT JOIN rm_media_regras ra ON ra.id = t.regra_anterior_id LEFT JOIN rm_media_regras rn ON rn.id = t.regra_nova_id LEFT JOIN adms_usuarios u ON u.id = t.usuario_id
              WHERE " . implode(' AND ', $w) . " ORDER BY t.created DESC",
            $p
        ) as $t) {
            $trocas[] = ['quando' => self::cel(date('d/m/Y H:i', strtotime($t['created'])), ['o' => $t['created']]),
                'oferta' => self::cel(F::rotuloOferta($t), ['url' => URLADM . 'regra-media/oferta/' . (int) $t['oid']]), 'curso' => (string) $t['curso'],
                'de' => (string) ($t['de'] ?? 'a da disciplina'), 'para' => (string) ($t['para'] ?? 'a da disciplina'), 'justificativa' => (string) $t['justificativa'], 'usuario' => (string) $t['usuario']];
        }
        $wl = ['l.pagina_id IN (427, 428)'];
        $pl = [];
        foreach (['de' => '>=', 'ate' => '<='] as $k => $op) {
            if (!empty($f[$k])) {
                $wl[] = "l.created {$op} :{$k}";
                $pl[$k] = $f[$k] . ($k === 'de' ? ' 00:00:00' : ' 23:59:59');
            }
        }
        $edicoes = [];
        foreach (F::ler("SELECT l.created, l.pagina_id, l.descricao, u.nome AS usuario FROM adms_logs l LEFT JOIN adms_usuarios u ON u.id = l.usuario_id WHERE " . implode(' AND ', $wl) . " ORDER BY l.created DESC", $pl) as $l) {
            $edicoes[] = ['quando' => self::cel(date('d/m/Y H:i', strtotime($l['created'])), ['o' => $l['created']]), 'acao' => (int) $l['pagina_id'] === 428 ? 'Nova regra' : 'Edição de regra',
                'descricao' => (string) $l['descricao'], 'usuario' => (string) $l['usuario']];
        }
        $colT = [self::col('quando', 'Quando'), self::col('oferta', 'Oferta'), self::col('curso', 'Curso'), self::col('de', 'Regra anterior'), self::col('para', 'Regra nova'),
            self::col('justificativa', 'Justificativa'), self::col('usuario', 'Quem')];
        $colE = [self::col('quando', 'Quando'), self::col('acao', 'Ação'), self::col('descricao', 'Registro'), self::col('usuario', 'Quem')];
        $det = ['trocas' => ['titulo' => 'Trocas de regra por oferta', 'colunas' => $colT, 'linhas' => $trocas], 'edicoes' => ['titulo' => 'Criações e edições no catálogo', 'colunas' => $colE, 'linhas' => $edicoes]];
        return [
            'cartoes' => [['rotulo' => 'Trocas por oferta', 'valor' => count($trocas), 'detalhe' => 'trocas'], ['rotulo' => 'Criações e edições no catálogo', 'valor' => count($edicoes), 'detalhe' => 'edicoes']],
            'detalhes' => $det,
            'tabelas' => [['titulo' => 'Trocas de regra por oferta', 'colunas' => $colT, 'linhas' => $trocas, 'ordem' => [[0, 'desc']]],
                ['titulo' => 'Criações e edições no catálogo', 'colunas' => $colE, 'linhas' => $edicoes, 'ordem' => [[0, 'desc']]]],
        ];
    }
}
