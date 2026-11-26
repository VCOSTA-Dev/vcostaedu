<?php

namespace App\adms\Models\historicoEscola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\historicoEscola\AdmsHistBase as B;
use App\adms\Models\historicoEscola\AdmsHistCadastros as C;

/**
 * A MATRIZ CURRICULAR DO HISTÓRICO (Parte B; D-111; a decisão 12): uma matriz só — a grade do segmento, num ano letivo,
 * com os campos do histórico numa tabela própria da escola. Em cada linha da grade (série × disciplina): as aulas
 * semanais, o grupo, a área (Médio), o itinerário (Médio) e a ordem; na matriz do ano: as semanas letivas, a duração da
 * aula, os textos da folha. As aulas anuais (semanais × semanas) e as horas (aulas × minutos ÷ 60) são sempre
 * calculadas — nunca gravadas. A carga em horas da grade continua como está (alimenta a frequência).
 */
class AdmsHistMatriz
{
    public const GRUPOS = [
        'EF' => ['BNC' => 'Base nacional comum', 'PD' => 'Parte diversificada'],
        'EM' => ['FGB' => 'Formação geral básica', 'IT_COMUM' => 'Itinerário comum', 'IT_FORMATIVO' => 'Itinerário formativo'],
    ];
    public const CAMPOS = ['semanas' => 'Semanas letivas', 'minutos' => 'Duração da aula (minutos)', 'fundamento' => 'Fundamento legal', 'autorizacao' => 'Autorização',
        'periodo' => 'Período e horário', 'data_folha' => 'Data da folha'];

    /** As grades dos segmentos marcados (o nível de cada uma). */
    public static function grades(): array
    {
        return B::todos("SELECT g.id, g.versao, c.id AS curso_id, c.nome AS curso, s.nivel FROM rm_cursos_grades g INNER JOIN rm_cursos c ON c.id = g.curso_id
            INNER JOIN esc_hist_segmento s ON s.curso_id = c.id AND s.nivel <> '' ORDER BY s.nivel DESC, c.nome, g.versao");
    }

    /** As matrizes, com os totais de cada série. */
    public static function listar(): array
    {
        $r = B::todos("SELECT m.*, c.nome AS curso, g.versao, s.nivel FROM esc_hist_matriz m INNER JOIN rm_cursos_grades g ON g.id = m.grade_id
            INNER JOIN rm_cursos c ON c.id = g.curso_id LEFT JOIN esc_hist_segmento s ON s.curso_id = c.id ORDER BY m.ano DESC, s.nivel DESC, c.nome");
        $prova = B::provas('esc_hist_matriz');
        foreach ($r as &$m) {
            $m['totais'] = self::totais((int) $m['id']);
            $m['prova'] = isset($prova[(int) $m['id']]);
        }
        return $r;
    }

    public static function ver(int $id): ?array
    {
        $m = B::um("SELECT m.*, c.nome AS curso, c.id AS curso_id, g.versao, s.nivel FROM esc_hist_matriz m INNER JOIN rm_cursos_grades g ON g.id = m.grade_id
            INNER JOIN rm_cursos c ON c.id = g.curso_id LEFT JOIN esc_hist_segmento s ON s.curso_id = c.id WHERE m.id = ?", [$id]);
        if (!$m) {
            return null;
        }
        $m['nivel'] = (string) ($m['nivel'] ?? 'EF') ?: 'EF';
        $m['series'] = B::todos('SELECT t.id, t.codnome, t.ordem, n.numero FROM rm_cursos_turma_series t LEFT JOIN esc_hist_serie n ON n.modulo_id = t.id WHERE t.grade_id = ? ORDER BY t.ordem', [(int) $m['grade_id']]);
        $m['itens'] = self::itens($id);
        $m['itinerarios'] = B::todos('SELECT * FROM esc_hist_itinerario WHERE matriz_id = ? ORDER BY numero', [$id]);
        $m['prova'] = B::prova('esc_hist_matriz', $id);
        return $m;
    }

    /** As linhas da matriz (todas as linhas da grade, com o que a matriz do ano diz delas), na ordem da matriz. */
    public static function itens(int $matrizId): array
    {
        return B::todos('SELECT l.id AS linha_id, l.modulo_id, l.materia_id, l.cargahoraria, l.ordem AS ordem_grade, mt.nome AS materia,
                i.id, IFNULL(i.aulas_semanais, 0) AS aulas_semanais, IFNULL(i.grupo, \'\') AS grupo, IFNULL(i.area_id, 0) AS area_id, IFNULL(i.itinerario, 0) AS itinerario,
                IFNULL(i.ordem, 0) AS ordem, a.nome AS area, a.ordem AS area_ordem
              FROM esc_hist_matriz m INNER JOIN uni_rh_cursos_disciplinas_grade l ON l.grade_id = m.grade_id
              INNER JOIN rm_materias mt ON mt.id = l.materia_id
              LEFT JOIN esc_hist_matriz_item i ON i.matriz_id = m.id AND i.linha_id = l.id
              LEFT JOIN esc_hist_area a ON a.id = i.area_id
             WHERE m.id = ? ORDER BY l.modulo_id, IF(IFNULL(i.ordem, 0) = 0, 999999, i.ordem), l.ordem, mt.nome', [$matrizId]);
    }

    public static function anuais(int $semanais, int $semanas): int
    {
        return $semanais * $semanas;
    }

    /** As horas, em décimos (aulas × minutos ÷ 60, com a fração): 1000 aulas × 50 min = 833,3 h. */
    public static function horasTexto(int $aulas, int $minutos): string
    {
        $dec = intdiv($aulas * $minutos * 10 + 30, 60); // arredondado a uma casa
        return $dec % 10 === 0 ? number_format(intdiv($dec, 10), 0, ',', '.') : number_format($dec / 10, 1, ',', '.');
    }

    /**
     * Os totais de cada série: as aulas semanais (os itinerários formativos contam um só, o de mais aulas), as anuais e
     * as horas. @return array<int, array{codnome: string, semanais: int, anuais: int, horas: string}> por modulo_id
     */
    public static function totais(int $matrizId): array
    {
        $m = B::um('SELECT * FROM esc_hist_matriz WHERE id = ?', [$matrizId]);
        if (!$m) {
            return [];
        }
        $series = [];
        foreach (B::todos('SELECT id, codnome FROM rm_cursos_turma_series WHERE grade_id = ? ORDER BY ordem', [(int) $m['grade_id']]) as $s) {
            $series[(int) $s['id']] = ['codnome' => (string) $s['codnome'], 'base' => 0, 'it' => []];
        }
        foreach (self::itens($matrizId) as $i) {
            $mid = (int) $i['modulo_id'];
            if (!isset($series[$mid]) || (int) $i['aulas_semanais'] <= 0) {
                continue;
            }
            if ($i['grupo'] === 'IT_FORMATIVO') {
                $series[$mid]['it'][(int) $i['itinerario']] = ($series[$mid]['it'][(int) $i['itinerario']] ?? 0) + (int) $i['aulas_semanais'];
            } else {
                $series[$mid]['base'] += (int) $i['aulas_semanais'];
            }
        }
        $r = [];
        foreach ($series as $mid => $s) {
            $sem = $s['base'] + ($s['it'] ? max($s['it']) : 0);
            $anuais = self::anuais($sem, (int) $m['semanas']);
            $r[$mid] = ['codnome' => $s['codnome'], 'semanais' => $sem, 'anuais' => $anuais, 'horas' => self::horasTexto($anuais, (int) $m['minutos'])];
        }
        return $r;
    }

    /**
     * Cria a matriz do ano: vazia (as aulas propostas pela carga da grade) ou copiando outra do mesmo nível (as linhas
     * casadas pela série e pela disciplina). @return int|string o id, ou o motivo
     */
    public static function criar(int $gradeId, int $ano, int $copiarDe, bool $prova = false)
    {
        $g = null;
        foreach (self::grades() as $x) {
            if ((int) $x['id'] === $gradeId) {
                $g = $x;
            }
        }
        if (!$g) {
            return 'Escolha a grade de um segmento marcado como Fundamental ou Médio (Cadastros do histórico → Segmentos e séries).';
        }
        if ($ano < 1990 || $ano > 2100) {
            return 'Informe o ano letivo.';
        }
        if (B::valor('SELECT id FROM esc_hist_matriz WHERE grade_id = ? AND ano = ?', [$gradeId, $ano])) {
            return 'Já existe a matriz desta grade neste ano: abra-a para editar.';
        }
        $origem = null;
        if ($copiarDe) {
            $origem = self::ver($copiarDe);
            if (!$origem || $origem['nivel'] !== $g['nivel']) {
                return 'Copie de uma matriz do mesmo nível (' . B::NIVEIS[$g['nivel']] . ').';
            }
        }
        $ok = \Core\Transacao::executar(function () use ($g, $gradeId, $ano, $origem, $prova) {
            $par = $origem ? array_intersect_key($origem, self::CAMPOS) : ['semanas' => 40, 'minutos' => 50, 'fundamento' => '', 'autorizacao' => '', 'periodo' => '', 'data_folha' => null];
            B::exec('INSERT INTO esc_hist_matriz (grade_id, ano, semanas, minutos, fundamento, autorizacao, periodo, data_folha, situacao, origem, origem_id, por, em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [$gradeId, $ano, (int) $par['semanas'], (int) $par['minutos'], (string) $par['fundamento'], (string) $par['autorizacao'], (string) $par['periodo'], $par['data_folha'] ?: null, 'rascunho',
                    $origem ? 'copia' : 'nova', $origem ? (int) $origem['id'] : 0, B::usuario()]);
            $id = (int) B::pdo()->lastInsertId();
            $linhas = B::todos('SELECT l.id, l.modulo_id, l.materia_id, l.cargahoraria, t.ordem AS serie_ordem FROM uni_rh_cursos_disciplinas_grade l INNER JOIN rm_cursos_turma_series t ON t.id = l.modulo_id WHERE l.grade_id = ?', [$gradeId]);
            $deOrigem = [];
            if ($origem) {
                $ordemSerie = array_column(B::todos('SELECT id, ordem FROM rm_cursos_turma_series WHERE grade_id = ?', [(int) $origem['grade_id']]), 'ordem', 'id');
                foreach ($origem['itens'] as $i) {
                    $deOrigem[(int) ($ordemSerie[(int) $i['modulo_id']] ?? 0) . ':' . (int) $i['materia_id']] = $i;
                }
                foreach ($origem['itinerarios'] as $it) {
                    B::exec('INSERT INTO esc_hist_itinerario (matriz_id, numero, nome) VALUES (?, ?, ?)', [$id, (int) $it['numero'], $it['nome']]);
                }
            }
            $padrao = $g['nivel'] === 'EM' ? 'FGB' : 'BNC';
            foreach ($linhas as $l) {
                $o = $deOrigem[(int) $l['serie_ordem'] . ':' . (int) $l['materia_id']] ?? null;
                if ($o) {
                    $aulas = (int) $o['aulas_semanais'];
                    $item = [$o['grupo'] ?: $padrao, (int) $o['area_id'], (int) $o['itinerario'], (int) $o['ordem']];
                } else {
                    $aulas = $origem ? 0 : (int) round(((int) $l['cargahoraria']) * 60 / 50 / 40); // proposta: a carga da grade em aulas de 50 min, 40 semanas
                    $item = [$padrao, 0, 0, 0];
                }
                B::exec('INSERT INTO esc_hist_matriz_item (matriz_id, linha_id, aulas_semanais, grupo, area_id, itinerario, ordem) VALUES (?, ?, ?, ?, ?, ?, ?)',
                    array_merge([$id, (int) $l['id'], max(0, min(60, $aulas))], $item));
            }
            if ($prova) {
                B::exec('INSERT IGNORE INTO esc_hist_dado_prova (tabela, registro_id, rotulo) VALUES (?, ?, ?)', ['esc_hist_matriz', $id, 'matriz de prova']);
            }
            B::registrar('esc_hist_matriz', $id, 0, 'Matriz ' . $g['curso'] . ' ' . $ano, null, ['origem' => $origem ? 'cópia da matriz ' . $origem['curso'] . ' ' . $origem['ano'] : 'nova'], ['origem' => 'Criada']);
            return $id;
        });
        return $ok ?: 'A matriz não foi criada (' . \Core\Transacao::ultimoErro() . ').';
    }

    /**
     * Grava a tela: os parâmetros, os itinerários e as linhas. Travada, só com o motivo. Só o que mudou vai para o
     * registro, linha a linha ("6º ano · Matemática: aulas semanais "5" → "6"").
     * @return int|string quantas coisas mudaram, ou o motivo
     */
    public static function salvar(int $id, array $post, string $motivo = '')
    {
        $m = self::ver($id);
        if (!$m) {
            return 'A matriz não foi encontrada.';
        }
        $motivo = trim($motivo);
        $nivel = $m['nivel'];
        $par = [
            'semanas' => (int) ($post['semanas'] ?? 0), 'minutos' => (int) ($post['minutos'] ?? 0),
            'fundamento' => trim(str_replace("\r", '', (string) ($post['fundamento'] ?? ''))), 'autorizacao' => trim(str_replace("\r", '', (string) ($post['autorizacao'] ?? ''))),
            'periodo' => trim((string) ($post['periodo'] ?? '')), 'data_folha' => B::data((string) ($post['data_folha'] ?? '')),
        ];
        if ($par['semanas'] < 1 || $par['semanas'] > 60 || $par['minutos'] < 10 || $par['minutos'] > 120) {
            return 'As semanas letivas vão de 1 a 60 e a duração da aula de 10 a 120 minutos.';
        }
        if (mb_strlen($par['fundamento']) > 2000 || mb_strlen($par['autorizacao']) > 2000 || mb_strlen($par['periodo']) > 200) {
            return 'Algum texto da folha passou do tamanho.';
        }
        $its = [];
        foreach ((array) ($post['it_numero'] ?? []) as $k => $n) {
            $nome = trim((string) ($post['it_nome'][$k] ?? ''));
            if ($nome === '' && trim((string) $n) === '') {
                continue;
            }
            $n = (int) $n;
            if ($n < 0 || $n > 9 || $nome === '' || mb_strlen($nome) > 160 || isset($its[$n])) {
                return 'Cada itinerário tem um número (0 = o comum; 1 a 9 = os formativos), único, e um nome.';
            }
            $its[$n] = $nome;
        }
        $grupos = self::GRUPOS[$nivel];
        $areas = array_column(B::todos('SELECT id FROM esc_hist_area'), 'id', 'id');
        $nomeSerie = array_column($m['series'], 'codnome', 'id');
        $mud = [];
        foreach ($m['itens'] as $i) {
            $lid = (int) $i['linha_id'];
            if (!isset($post['aulas'][$lid])) {
                continue;
            }
            $a = trim((string) $post['aulas'][$lid]);
            if ($a !== '' && !preg_match('/^\d{1,2}$/', $a)) {
                return 'Aulas semanais inválidas em ' . ($nomeSerie[(int) $i['modulo_id']] ?? '') . ', ' . $i['materia'] . '.';
            }
            $novo = ['aulas_semanais' => (int) $a, 'grupo' => (string) ($post['grupo'][$lid] ?? ''), 'area_id' => (int) ($post['area'][$lid] ?? 0),
                'itinerario' => (int) ($post['itinerario'][$lid] ?? 0), 'ordem' => (int) ($post['ordem'][$lid] ?? 0)];
            if (!isset($grupos[$novo['grupo']])) {
                return 'Grupo inválido em ' . $i['materia'] . '.';
            }
            if ($novo['area_id'] && !isset($areas[$novo['area_id']])) {
                return 'Área inválida em ' . $i['materia'] . '.';
            }
            if ($novo['grupo'] !== 'IT_FORMATIVO') {
                $novo['itinerario'] = 0;
            } elseif (!isset($its[$novo['itinerario']]) || $novo['itinerario'] === 0) {
                return 'O item do itinerário formativo precisa de um itinerário formativo cadastrado (' . $i['materia'] . ').';
            }
            if ($nivel === 'EF') {
                $novo['area_id'] = 0;
            }
            $antes = ['aulas_semanais' => (int) $i['aulas_semanais'], 'grupo' => (string) $i['grupo'], 'area_id' => (int) $i['area_id'], 'itinerario' => (int) $i['itinerario'], 'ordem' => (int) $i['ordem']];
            if ($antes !== $novo || !$i['id']) {
                $mud[] = [$i, $antes, $novo];
            }
        }
        $parAntes = array_intersect_key($m, self::CAMPOS);
        $parAntes['semanas'] = (int) $parAntes['semanas'];
        $parAntes['minutos'] = (int) $parAntes['minutos'];
        $parAntes['fundamento'] = (string) $parAntes['fundamento'];
        $parAntes['autorizacao'] = (string) $parAntes['autorizacao'];
        $parMudou = $parAntes != $par;
        $itsAntes = array_column($m['itinerarios'], 'nome', 'numero');
        ksort($its);
        ksort($itsAntes);
        $itMudou = $itsAntes != $its;
        if (!$mud && !$parMudou && !$itMudou) {
            return 0;
        }
        if ($m['situacao'] === 'travada' && mb_strlen($motivo) < 5) {
            return 'A matriz está travada: informe o motivo da correção (ao menos 5 caracteres).';
        }
        $titulo = 'Matriz ' . $m['curso'] . ' ' . $m['ano'];
        $nomeArea = array_column(B::todos('SELECT id, nome FROM esc_hist_area'), 'nome', 'id');
        $ok = \Core\Transacao::executar(function () use ($id, $m, $par, $parAntes, $parMudou, $its, $itsAntes, $itMudou, $mud, $titulo, $motivo, $nomeSerie, $grupos, $nomeArea) {
            if ($parMudou) {
                B::exec('UPDATE esc_hist_matriz SET semanas = ?, minutos = ?, fundamento = ?, autorizacao = ?, periodo = ?, data_folha = ? WHERE id = ?',
                    [$par['semanas'], $par['minutos'], $par['fundamento'], $par['autorizacao'], $par['periodo'], $par['data_folha'], $id]);
                B::registrar('esc_hist_matriz', $id, 0, $titulo, $parAntes, $par, self::CAMPOS, '', $motivo);
            }
            if ($itMudou) {
                B::exec('DELETE FROM esc_hist_itinerario WHERE matriz_id = ?', [$id]);
                foreach ($its as $n => $nome) {
                    B::exec('INSERT INTO esc_hist_itinerario (matriz_id, numero, nome) VALUES (?, ?, ?)', [$id, $n, $nome]);
                }
                $f = fn($x) => ['itinerarios' => implode('; ', array_map(fn($k, $v) => "{$k} = {$v}", array_keys($x), $x))];
                B::registrar('esc_hist_matriz', $id, 0, $titulo, $f($itsAntes), $f($its), ['itinerarios' => 'Itinerários'], '', $motivo);
            }
            foreach ($mud as [$i, $antes, $novo]) {
                if ($i['id']) {
                    B::exec('UPDATE esc_hist_matriz_item SET aulas_semanais = ?, grupo = ?, area_id = ?, itinerario = ?, ordem = ? WHERE id = ?',
                        [$novo['aulas_semanais'], $novo['grupo'], $novo['area_id'], $novo['itinerario'], $novo['ordem'], (int) $i['id']]);
                } else {
                    B::exec('INSERT INTO esc_hist_matriz_item (matriz_id, linha_id, aulas_semanais, grupo, area_id, itinerario, ordem) VALUES (?, ?, ?, ?, ?, ?, ?)',
                        [$id, (int) $i['linha_id'], $novo['aulas_semanais'], $novo['grupo'], $novo['area_id'], $novo['itinerario'], $novo['ordem']]);
                }
                $leg = fn($x) => ['aulas_semanais' => (string) $x['aulas_semanais'], 'grupo' => $grupos[$x['grupo']] ?? $x['grupo'], 'area_id' => $x['area_id'] ? ($nomeArea[$x['area_id']] ?? '') : '',
                    'itinerario' => $x['itinerario'] ? (string) $x['itinerario'] : '', 'ordem' => $x['ordem'] ? (string) $x['ordem'] : ''];
                B::registrar('esc_hist_matriz_item', $id, 0, $titulo . ' · ' . ($nomeSerie[(int) $i['modulo_id']] ?? '') . ' · ' . $i['materia'], $leg($antes), $leg($novo),
                    ['aulas_semanais' => 'Aulas semanais', 'grupo' => 'Grupo', 'area_id' => 'Área', 'itinerario' => 'Itinerário', 'ordem' => 'Ordem'], '', $motivo);
            }
            return true;
        });
        return $ok ? count($mud) + (int) $parMudou + (int) $itMudou : 'A matriz não foi gravada (' . \Core\Transacao::ultimoErro() . ').';
    }

    /** @return true|string */
    public static function travar(int $id)
    {
        $m = self::ver($id);
        if (!$m) {
            return 'A matriz não foi encontrada.';
        }
        if ($m['situacao'] === 'travada') {
            return 'A matriz já está travada.';
        }
        return \Core\Transacao::executar(function () use ($id, $m) {
            B::exec("UPDATE esc_hist_matriz SET situacao = 'travada', travada_em = NOW(), travada_por = ? WHERE id = ?", [B::usuario(), $id]);
            B::registrar('esc_hist_matriz', $id, 0, 'Matriz ' . $m['curso'] . ' ' . $m['ano'], ['situacao' => 'rascunho'], ['situacao' => 'travada'], ['situacao' => 'Situação'], 'travar');
            return true;
        }) ? true : 'A matriz não foi travada.';
    }

    /** @return int|string */
    public static function salvarArea(int $id, string $nome, int $ordem)
    {
        $nome = trim($nome);
        if ($nome === '' || mb_strlen($nome) > 120) {
            return 'Informe o nome da área (até 120 caracteres).';
        }
        $antes = $id ? B::um('SELECT * FROM esc_hist_area WHERE id = ?', [$id]) : null;
        if (B::valor('SELECT id FROM esc_hist_area WHERE nome = ? AND id <> ?', [$nome, $id])) {
            return 'Já existe uma área com esse nome.';
        }
        return \Core\Transacao::executar(function () use ($id, $nome, $ordem, $antes) {
            if ($antes) {
                B::exec('UPDATE esc_hist_area SET nome = ?, ordem = ? WHERE id = ?', [$nome, $ordem, $id]);
            } else {
                B::exec('INSERT INTO esc_hist_area (nome, ordem) VALUES (?, ?)', [$nome, $ordem]);
                $id = (int) B::pdo()->lastInsertId();
            }
            B::registrar('esc_hist_area', $id, 0, 'Área de conhecimento', $antes, ['nome' => $nome, 'ordem' => (string) $ordem], ['nome' => 'Nome', 'ordem' => 'Ordem']);
            return $id;
        }) ?: 'A área não foi gravada.';
    }

    public static function areas(): array
    {
        return B::todos('SELECT * FROM esc_hist_area ORDER BY ordem, nome');
    }

    /**
     * A matriz que vale para a grade no ano: a do ano; sem ela, a do ano mais próximo da mesma grade (com aviso).
     * @return array{matriz: ?array, aviso: string}
     */
    public static function doAno(int $gradeId, int $ano): array
    {
        $id = (int) B::valor('SELECT id FROM esc_hist_matriz WHERE grade_id = ? ORDER BY ABS(ano - ?), ano DESC LIMIT 1', [$gradeId, $ano]);
        $m = $id ? self::ver($id) : null;
        $aviso = '';
        if (!$m) {
            $aviso = "não há matriz do histórico cadastrada para esta grade (ano {$ano})";
        } elseif ((int) $m['ano'] !== $ano) {
            $aviso = "a matriz de {$ano} não está cadastrada: foi usada a de {$m['ano']}";
        }
        if ($m && $m['prova']) {
            $aviso .= ($aviso ? '; ' : '') . 'a carga vem de uma matriz de PROVA';
        }
        return ['matriz' => $m, 'aviso' => $aviso];
    }

    /* ---------------------------------------------------------------- a folha em PDF */

    public static function folhaHtml(int $id): ?string
    {
        $m = self::ver($id);
        if (!$m) {
            return null;
        }
        $h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
        $t = C::resolvido($m['nivel'], $m['data_folha'] ?: date('Y-m-d'));
        $logo = C::logoDataUri($m['nivel']);
        $series = $m['series'];
        $tot = self::totais($id);
        $grupos = self::GRUPOS[$m['nivel']];
        /* as linhas: uma por disciplina (e área/itinerário), as séries em colunas */
        $linhas = [];
        foreach ($m['itens'] as $i) {
            if ((int) $i['aulas_semanais'] <= 0) {
                continue;
            }
            $k = $i['grupo'] . '|' . ($i['grupo'] === 'IT_FORMATIVO' ? $i['itinerario'] : '') . '|' . $i['materia_id'];
            $linhas[$k] ??= ['grupo' => $i['grupo'], 'area' => (string) $i['area'], 'area_ordem' => (int) $i['area_ordem'], 'itinerario' => (int) $i['itinerario'], 'materia' => $i['materia'], 'ordem' => (int) $i['ordem'] ?: 1000 + (int) $i['ordem_grade'], 'aulas' => []];
            $linhas[$k]['aulas'][(int) $i['modulo_id']] = (int) $i['aulas_semanais'];
            $linhas[$k]['ordem'] = min($linhas[$k]['ordem'], (int) $i['ordem'] ?: 1000 + (int) $i['ordem_grade']);
        }
        $ordemGrupo = array_flip(array_keys($grupos));
        uasort($linhas, fn($a, $b) => [$ordemGrupo[$a['grupo']] ?? 9, $a['itinerario'], $a['area_ordem'], $a['ordem']] <=> [$ordemGrupo[$b['grupo']] ?? 9, $b['itinerario'], $b['area_ordem'], $b['ordem']]);
        $nomeIt = array_column($m['itinerarios'], 'nome', 'numero');
        $paisagem = $m['nivel'] === 'EF';
        $css = '@page { margin: 12mm 12mm 16mm 12mm; } body { font-family: Helvetica, Arial, sans-serif; font-size: 8.5pt; color: #000; }'
            . 'table { border-collapse: collapse; width: 100%; } td, th { border: 0.6pt solid #000; padding: 1.4mm 1.2mm; } th { background: #eee; }'
            . '.cab td { border: 0; padding: 0; vertical-align: middle; } .c { text-align: center; } .tot td { font-weight: bold; } .grupo { background: #f4f4f4; font-weight: bold; }'
            . '.texto { margin: 2mm 0; } .assin { margin-top: 14mm; text-align: center; } .assin div { border-top: 0.6pt solid #000; width: 75mm; margin: 0 auto; padding-top: 1mm; }';
        $o = '<html><head><meta charset="UTF-8"><style>' . $css . '</style></head><body>';
        $o .= '<table class="cab"><tr><td style="width: 32mm">' . ($logo ? '<img src="' . $logo . '" style="max-height: 20mm; max-width: 30mm">' : '') . '</td><td class="c">'
            . implode('', array_map(fn($l) => $l !== '' ? '<div>' . $h($l) . '</div>' : '', [$t['orgao_linha1'], $t['orgao_linha2'], $t['orgao_linha3']]))
            . '<div style="font-size: 11pt; font-weight: bold; margin-top: 1mm">' . $h($t['escola_nome']) . '</div><div>' . $h($t['escola_endereco']) . '</div>'
            . ($t['ato_escola'] !== '' ? '<div>' . $h($t['ato_escola']) . '</div>' : '') . '</td><td style="width: 32mm"></td></tr></table>';
        $o .= '<h2 class="c" style="font-size: 11pt; margin: 3mm 0 2mm">MATRIZ CURRICULAR — ' . $h(mb_strtoupper(B::NIVEIS[$m['nivel']])) . ' — ' . (int) $m['ano'] . '</h2>';
        if ($m['fundamento'] !== '' && $m['fundamento'] !== null) {
            $o .= '<div class="texto"><b>Fundamento legal:</b> ' . nl2br($h($m['fundamento'])) . '</div>';
        }
        if ($m['autorizacao'] !== '' && $m['autorizacao'] !== null) {
            $o .= '<div class="texto"><b>Autorização:</b> ' . nl2br($h($m['autorizacao'])) . '</div>';
        }
        if ($m['periodo'] !== '') {
            $o .= '<div class="texto"><b>Período:</b> ' . $h($m['periodo']) . '</div>';
        }
        $o .= '<table><thead><tr><th style="width: 34mm">' . ($m['nivel'] === 'EM' ? 'Área' : 'Grupo') . '</th><th>Disciplina</th>';
        foreach ($series as $s) {
            $o .= '<th class="c">' . $h($s['codnome']) . '</th>';
        }
        $o .= '</tr></thead><tbody>';
        $grupoAtual = null;
        foreach ($linhas as $l) {
            $rot = $l['grupo'] === 'FGB' ? ($l['area'] !== '' ? $l['area'] : $grupos['FGB']) : ($l['grupo'] === 'IT_FORMATIVO' ? $grupos['IT_FORMATIVO'] . ' ' . $l['itinerario'] . (isset($nomeIt[$l['itinerario']]) ? ' — ' . $nomeIt[$l['itinerario']] : '') : $grupos[$l['grupo']]);
            $o .= '<tr><td>' . ($rot !== $grupoAtual ? $h($rot) : '') . '</td><td>' . $h($l['materia']) . '</td>';
            $grupoAtual = $rot;
            foreach ($series as $s) {
                $o .= '<td class="c">' . (isset($l['aulas'][(int) $s['id']]) ? (int) $l['aulas'][(int) $s['id']] : '') . '</td>';
            }
            $o .= '</tr>';
        }
        foreach (['semanais' => 'Total de aulas semanais', 'anuais' => 'Total de aulas anuais (' . (int) $m['semanas'] . ' semanas)', 'horas' => 'Total de horas anuais (aula de ' . (int) $m['minutos'] . ' minutos)'] as $k => $rot) {
            $o .= '<tr class="tot"><td colspan="2">' . $h($rot) . '</td>';
            foreach ($series as $s) {
                $v = $tot[(int) $s['id']][$k] ?? '';
                $o .= '<td class="c">' . (is_int($v) ? number_format($v, 0, ',', '.') : $h($v)) . '</td>';
            }
            $o .= '</tr>';
        }
        $o .= '</tbody></table>';
        $data = $m['data_folha'] ?: date('Y-m-d');
        $meses = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
        $o .= '<p style="text-align: right; margin-top: 6mm">' . $h(($t['cidade'] !== '' ? $t['cidade'] . ', ' : '') . (int) substr($data, 8, 2) . ' de ' . $meses[(int) substr($data, 5, 2)] . ' de ' . substr($data, 0, 4)) . '.</p>';
        $o .= '<div class="assin"><div>' . $h($t['diretora_nome'] !== '' ? $t['diretora_nome'] : ' ') . '<br>' . $h($t['titulo_diretora']) . ($t['diretora_doc'] !== '' ? '<br>' . $h($t['diretora_doc']) : '') . '</div></div>';
        $o .= '</body></html>';
        return ($paisagem ? '<!--paisagem-->' : '') . $o;
    }

    /** A folha em PDF (o Fundamental em paisagem, o Médio em retrato). */
    public static function folhaPdf(int $id): ?string
    {
        $html = self::folhaHtml($id);
        if ($html === null) {
            return null;
        }
        return AdmsHistPdf::gerar($html, str_starts_with($html, '<!--paisagem-->') ? 'landscape' : 'portrait');
    }
}
