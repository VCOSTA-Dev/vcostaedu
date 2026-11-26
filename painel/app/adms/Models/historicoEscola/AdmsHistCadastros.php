<?php

namespace App\adms\Models\historicoEscola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\historicoEscola\AdmsHistBase as B;

/**
 * OS CADASTROS DO HISTÓRICO ESCOLAR (Parte A; D-110): os textos do documento por nível e vigência, as observações, o
 * logo por nível, as escolas, o nome e a ordem de cada disciplina no histórico, o nível de cada segmento e o número de
 * cada série, e os dados do aluno que só o histórico usa (o RA estadual, a certidão). Toda gravação passa pelo registro
 * de alterações, que grava só o que mudou.
 *
 * Nenhum texto de estado ou rede vem pronto no código: o que um estado chama de RA, a linha do órgão, o rótulo do número
 * de publicação — tudo é texto do cadastro. A ordem de prioridade de cada texto do documento: o cadastro do histórico
 * (a vigência da data do documento) → os dados da instituição (o nome, o endereço, a cidade, o logo) → vazio, com aviso.
 */
class AdmsHistCadastros
{
    public const CAMPOS_TEXTO = [
        'orgao_linha1' => 'Linha 1 do órgão', 'orgao_linha2' => 'Linha 2 do órgão', 'orgao_linha3' => 'Linha 3 do órgão',
        'escola_nome' => 'Nome da escola', 'escola_endereco' => 'Endereço da escola', 'ato_escola' => 'Ato legal da escola',
        'ato_curso' => 'Ato legal do curso', 'turno' => 'Turno', 'escala' => 'Escala avaliatória',
        'fecho_declaracao' => 'Texto da declaração', 'fecho_certificado' => 'Texto do certificado', 'cidade' => 'Cidade',
        'secretaria_nome' => 'Secretária: nome', 'secretaria_doc' => 'Secretária: documento', 'titulo_secretaria' => 'Secretária: título',
        'diretora_nome' => 'Diretora: nome', 'diretora_doc' => 'Diretora: documento', 'titulo_diretora' => 'Diretora: título',
        'rotulo_registro' => 'Rótulo do registro do aluno na rede', 'rotulo_publicacao' => 'Rótulo do número de publicação',
        'unidade_carga' => 'Carga mostrada em',
    ];
    /** os campos longos (textarea) */
    public const LONGOS = ['escala', 'fecho_declaracao', 'fecho_certificado', 'ato_escola', 'ato_curso'];
    public const MARCADORES = ['{escola}', '{aluno}', '{ra}', '{rg}', '{serie}', '{ano}', '{etapa}'];
    public const LOGO_MAX = 1048576;

    /** O texto de partida de uma vigência nova sem nenhuma anterior: neutro, nacional (a LDB), sem nada de estado. */
    public static function padrao(string $nivel): array
    {
        $seg = $nivel === 'EM' ? 'do Ensino Médio' : 'do Ensino Fundamental';
        $p = array_fill_keys(array_keys(self::CAMPOS_TEXTO), '');
        $p['fecho_declaracao'] = 'A Direção de {escola}, de acordo com o inciso VII do artigo 24 da Lei nº 9.394/96, declara que {aluno} está apto a prosseguir os estudos no {serie} ' . $seg . ($nivel === 'EF' ? ' {etapa}' : '') . ' no ano letivo de {ano}.';
        $p['fecho_certificado'] = 'A Direção de {escola}, de acordo com o inciso VII do artigo 24 da Lei nº 9.394/96, certifica que {aluno} concluiu o {serie} ' . $seg . ' no ano letivo de {ano}.';
        $p['titulo_secretaria'] = 'Secretário(a) de escola';
        $p['titulo_diretora'] = 'Diretor(a) de escola';
        $p['unidade_carga'] = $nivel === 'EM' ? 'horas' : 'aulas';
        return $p;
    }

    /* ---------------------------------------------------------------- os textos */

    public static function vigencias(string $nivel): array
    {
        return B::todos('SELECT * FROM esc_hist_texto WHERE nivel = ? ORDER BY vigente_desde DESC', [$nivel]);
    }

    /** O texto que vale na data (o de maior "vigente desde" até ela), ou null. */
    public static function vigente(string $nivel, ?string $data = null): ?array
    {
        return B::um('SELECT * FROM esc_hist_texto WHERE nivel = ? AND vigente_desde <= ? ORDER BY vigente_desde DESC LIMIT 1', [$nivel, $data ?: date('Y-m-d')]);
    }

    /**
     * O texto do documento na data, com a ordem de prioridade aplicada: o cadastro → a instituição → vazio.
     * @return array campos + ['_vigencia' => ?string, '_semCadastro' => bool]
     */
    public static function resolvido(string $nivel, ?string $data = null): array
    {
        $t = self::vigente($nivel, $data);
        $r = $t ? array_intersect_key($t, self::CAMPOS_TEXTO) : self::padrao($nivel);
        $inst = \App\adms\Models\helper\AdmsInstituicao::dados() ?: [];
        if (trim((string) $r['escola_nome']) === '') {
            $r['escola_nome'] = \App\adms\Models\helper\AdmsInstituicao::nome();
        }
        if (trim((string) $r['escola_endereco']) === '') {
            $r['escola_endereco'] = \App\adms\Models\helper\AdmsInstituicao::endereco();
        }
        if (trim((string) $r['cidade']) === '') {
            $r['cidade'] = trim((string) ($inst['cidade'] ?? ''));
        }
        if (!in_array($r['unidade_carga'], ['aulas', 'horas'], true)) {
            $r['unidade_carga'] = $nivel === 'EM' ? 'horas' : 'aulas';
        }
        $r['_vigencia'] = $t['vigente_desde'] ?? null;
        $r['_semCadastro'] = $t === null;
        return $r;
    }

    /**
     * Grava uma vigência: `$id` > 0 corrige a que existe; 0 cria a vigência nova (a data não pode repetir no nível).
     * @return int|string o id, ou o motivo da recusa
     */
    public static function salvarTexto(int $id, string $nivel, string $vigencia, array $post)
    {
        if (!B::nivelValido($nivel)) {
            return 'Escolha o nível (Fundamental ou Médio).';
        }
        $vig = B::data($vigencia);
        if (!$vig) {
            return 'Informe a data "vigente desde" (dd/mm/aaaa).';
        }
        $novo = [];
        foreach (self::CAMPOS_TEXTO as $c => $rot) {
            $v = trim(str_replace("\r", '', (string) ($post[$c] ?? '')));
            $max = in_array($c, self::LONGOS, true) ? 4000 : ($c === 'escola_endereco' ? 300 : 200);
            if (mb_strlen($v) > $max) {
                return "{$rot}: até {$max} caracteres.";
            }
            $novo[$c] = $v;
        }
        $novo['unidade_carga'] = $novo['unidade_carga'] === 'horas' ? 'horas' : 'aulas';
        foreach (['fecho_declaracao', 'fecho_certificado'] as $c) {
            if (preg_match_all('/\{[^}]*\}/', $novo[$c], $m)) {
                $fora = array_diff($m[0], self::MARCADORES);
                if ($fora) {
                    return self::CAMPOS_TEXTO[$c] . ': o marcador ' . reset($fora) . ' não existe (use ' . implode(' ', self::MARCADORES) . ').';
                }
            }
        }
        $antes = $id ? B::um('SELECT * FROM esc_hist_texto WHERE id = ? AND nivel = ?', [$id, $nivel]) : null;
        if ($id && !$antes) {
            return 'A vigência não foi encontrada.';
        }
        $outra = B::valor('SELECT id FROM esc_hist_texto WHERE nivel = ? AND vigente_desde = ? AND id <> ?', [$nivel, $vig, $id]);
        if ($outra) {
            return 'Já existe uma vigência deste nível com essa data: abra-a para corrigir.';
        }
        $titulo = 'Textos do histórico (' . B::NIVEIS[$nivel] . ', vigente desde ' . B::br($vig) . ')';
        $rotulos = ['vigente_desde' => 'Vigente desde'] + self::CAMPOS_TEXTO;
        $depois = ['vigente_desde' => $vig] + $novo;
        $ok = \Core\Transacao::executar(function () use ($id, $nivel, $vig, $novo, $antes, $depois, $titulo, $rotulos) {
            $cols = array_keys($novo);
            if ($antes) {
                B::exec('UPDATE esc_hist_texto SET vigente_desde = ?, ' . implode(', ', array_map(fn($c) => "{$c} = ?", $cols)) . ' WHERE id = ?', array_merge([$vig], array_values($novo), [$id]));
                B::registrar('esc_hist_texto', $id, 0, $titulo, $antes, $depois, $rotulos);
                return $id;
            }
            B::exec('INSERT INTO esc_hist_texto (nivel, vigente_desde, ' . implode(', ', $cols) . ', por, em) VALUES (?, ?, ' . implode(', ', array_fill(0, count($cols), '?')) . ', ?, NOW())',
                array_merge([$nivel, $vig], array_values($novo), [B::usuario()]));
            $novoId = (int) B::pdo()->lastInsertId();
            B::registrar('esc_hist_texto', $novoId, 0, $titulo, null, $depois, $rotulos);
            return $novoId;
        });
        return $ok ?: 'Os textos não foram gravados (' . \Core\Transacao::ultimoErro() . ').';
    }

    /**
     * O texto do fecho com os marcadores trocados, limpo: espaços repetidos colapsados, espaço antes da pontuação
     * removido, linha só de pontuação descartada.
     */
    public static function preencher(string $modelo, array $valores): string
    {
        $t = strtr($modelo, $valores);
        return self::limpar($t);
    }

    public static function limpar(string $t): string
    {
        $linhas = [];
        foreach (preg_split('/\R/u', $t) as $l) {
            $l = preg_replace('/[ \t]+/u', ' ', $l);
            $l = preg_replace('/\s+([.,;:!?)])/u', '$1', $l);
            $l = trim($l);
            if ($l === '' || preg_match('/^[\p{P}\s]+$/u', $l)) {
                continue;
            }
            $linhas[] = $l;
        }
        return implode("\n", $linhas);
    }

    /* ---------------------------------------------------------------- as observações */

    public static function observacoes(string $nivel, bool $soAtivas = false): array
    {
        return B::todos('SELECT * FROM esc_hist_observacao WHERE nivel = ?' . ($soAtivas ? ' AND ativo = 1' : '') . ' ORDER BY ordem, id', [$nivel]);
    }

    /** @return int|string */
    public static function salvarObservacao(int $id, string $nivel, array $post)
    {
        if (!B::nivelValido($nivel)) {
            return 'Escolha o nível (Fundamental ou Médio).';
        }
        $d = [
            'chave' => strtolower(trim((string) ($post['chave'] ?? ''))),
            'titulo' => trim((string) ($post['titulo'] ?? '')),
            'texto' => trim(str_replace("\r", '', (string) ($post['texto'] ?? ''))),
            'tipo' => ($post['tipo'] ?? '') === 'selecionavel' ? 'selecionavel' : 'padrao',
            'ordem' => (int) ($post['ordem'] ?? 0),
            'ativo' => !empty($post['ativo']) ? 1 : 0,
        ];
        if (!preg_match('/^[a-z0-9_]{2,40}$/', $d['chave'])) {
            return 'A chave: de 2 a 40 letras minúsculas, números ou "_" (ex.: conselho, reclassificacao).';
        }
        if ($d['titulo'] === '' || mb_strlen($d['titulo']) > 120 || $d['texto'] === '' || mb_strlen($d['texto']) > 2000) {
            return 'Informe o título (até 120 caracteres) e o texto (até 2.000).';
        }
        $antes = $id ? B::um('SELECT * FROM esc_hist_observacao WHERE id = ? AND nivel = ?', [$id, $nivel]) : null;
        if ($id && !$antes) {
            return 'A observação não foi encontrada.';
        }
        if (B::valor('SELECT id FROM esc_hist_observacao WHERE nivel = ? AND chave = ? AND id <> ?', [$nivel, $d['chave'], $id])) {
            return 'Já existe uma observação com essa chave neste nível.';
        }
        $rot = ['chave' => 'Chave', 'titulo' => 'Título', 'texto' => 'Texto', 'tipo' => 'Tipo', 'ordem' => 'Ordem', 'ativo' => 'Ativa'];
        $titulo = 'Observação do histórico (' . B::NIVEIS[$nivel] . ', "' . $d['titulo'] . '")';
        return \Core\Transacao::executar(function () use ($id, $nivel, $d, $antes, $rot, $titulo) {
            if ($antes) {
                B::exec('UPDATE esc_hist_observacao SET chave = ?, titulo = ?, texto = ?, tipo = ?, ordem = ?, ativo = ? WHERE id = ?', [$d['chave'], $d['titulo'], $d['texto'], $d['tipo'], $d['ordem'], $d['ativo'], $id]);
                B::registrar('esc_hist_observacao', $id, 0, $titulo, $antes, $d, $rot);
                return $id;
            }
            B::exec('INSERT INTO esc_hist_observacao (nivel, chave, titulo, texto, tipo, ordem, ativo) VALUES (?, ?, ?, ?, ?, ?, ?)', [$nivel, $d['chave'], $d['titulo'], $d['texto'], $d['tipo'], $d['ordem'], $d['ativo']]);
            $novo = (int) B::pdo()->lastInsertId();
            B::registrar('esc_hist_observacao', $novo, 0, $titulo, null, $d, $rot);
            return $novo;
        }) ?: 'A observação não foi gravada (' . \Core\Transacao::ultimoErro() . ').';
    }

    /* ---------------------------------------------------------------- o logo */

    public static function logo(string $nivel): ?array
    {
        return B::um('SELECT nivel, mime, largura, altura, sha256, em FROM esc_hist_logo WHERE nivel = ?', [$nivel]);
    }

    /** O logo do nível como data URI; sem logo do nível, o da instituição; sem os dois, null. */
    public static function logoDataUri(string $nivel): ?string
    {
        $l = B::um('SELECT imagem, mime FROM esc_hist_logo WHERE nivel = ?', [$nivel]);
        if ($l) {
            return 'data:' . $l['mime'] . ';base64,' . base64_encode((string) $l['imagem']);
        }
        $doc = \App\adms\Models\historico\AdmsHistoricoDocumento::class;
        return $doc::dataUri($doc::arquivoLogo());
    }

    /** @return true|string */
    public static function enviarLogo(string $nivel, string $arquivo, int $tamanho)
    {
        if (!B::nivelValido($nivel)) {
            return 'Escolha o nível.';
        }
        if ($tamanho <= 0 || $tamanho > self::LOGO_MAX || !is_file($arquivo)) {
            return 'Envie uma imagem PNG ou JPEG de até 1 MB.';
        }
        $info = @getimagesize($arquivo);
        if (!$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            return 'O arquivo não é uma imagem PNG ou JPEG.';
        }
        $bin = (string) file_get_contents($arquivo);
        $sha = hash('sha256', $bin);
        $antes = self::logo($nivel);
        if ($antes && $antes['sha256'] === $sha) {
            return true;
        }
        $ok = \Core\Transacao::executar(function () use ($nivel, $bin, $info, $sha, $antes) {
            B::exec('REPLACE INTO esc_hist_logo (nivel, imagem, mime, largura, altura, sha256, por, em) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())', [$nivel, $bin, $info['mime'], (int) $info[0], (int) $info[1], $sha, B::usuario()]);
            B::registrar('esc_hist_logo', 0, 0, 'Logo do histórico (' . B::NIVEIS[$nivel] . ')', $antes ? ['logo' => 'imagem ' . substr($antes['sha256'], 0, 12)] : null,
                ['logo' => 'imagem ' . substr($sha, 0, 12) . " ({$info[0]}×{$info[1]})"], ['logo' => 'Logo']);
            return true;
        });
        return $ok ?: 'O logo não foi gravado.';
    }

    public static function retirarLogo(string $nivel): bool
    {
        $antes = self::logo($nivel);
        if (!$antes) {
            return false;
        }
        return (bool) \Core\Transacao::executar(function () use ($nivel, $antes) {
            B::exec('DELETE FROM esc_hist_logo WHERE nivel = ?', [$nivel]);
            B::registrar('esc_hist_logo', 0, 0, 'Logo do histórico (' . B::NIVEIS[$nivel] . ')', ['logo' => 'imagem ' . substr($antes['sha256'], 0, 12)], ['logo' => 'o logo da instituição'], ['logo' => 'Logo']);
            return true;
        });
    }

    /* ---------------------------------------------------------------- as escolas */

    public static function escolas(bool $soAtivas = false): array
    {
        return B::todos('SELECT * FROM esc_hist_escola' . ($soAtivas ? ' WHERE ativo = 1' : '') . ' ORDER BY esta_escola DESC, nome');
    }

    public static function estaEscola(): ?array
    {
        return B::um('SELECT * FROM esc_hist_escola WHERE esta_escola = 1 AND ativo = 1 ORDER BY id LIMIT 1');
    }

    /** @return int|string */
    public static function salvarEscola(int $id, array $post)
    {
        $d = [
            'nome' => trim((string) ($post['nome'] ?? '')), 'municipio' => trim((string) ($post['municipio'] ?? '')),
            'uf' => strtoupper(trim((string) ($post['uf'] ?? ''))), 'rede' => trim((string) ($post['rede'] ?? '')),
            'esta_escola' => !empty($post['esta_escola']) ? 1 : 0, 'ativo' => !array_key_exists('ativo', $post) || !empty($post['ativo']) ? 1 : 0,
        ];
        if ($d['nome'] === '' || mb_strlen($d['nome']) > 200 || $d['municipio'] === '' || mb_strlen($d['municipio']) > 120 || !preg_match('/^[A-Z]{2}$/', $d['uf'])) {
            return 'Informe o nome da escola, o município e a UF (duas letras): os três são obrigatórios.';
        }
        if (mb_strlen($d['rede']) > 30) {
            return 'A rede: até 30 caracteres.';
        }
        $antes = $id ? B::um('SELECT * FROM esc_hist_escola WHERE id = ?', [$id]) : null;
        if ($id && !$antes) {
            return 'A escola não foi encontrada.';
        }
        $rot = ['nome' => 'Nome', 'municipio' => 'Município', 'uf' => 'UF', 'rede' => 'Rede', 'esta_escola' => 'Esta escola', 'ativo' => 'Ativa'];
        return \Core\Transacao::executar(function () use ($id, $d, $antes, $rot) {
            if ($d['esta_escola']) { // "esta escola" é uma só
                foreach (B::todos('SELECT * FROM esc_hist_escola WHERE esta_escola = 1 AND id <> ?', [$id]) as $o) {
                    B::exec('UPDATE esc_hist_escola SET esta_escola = 0 WHERE id = ?', [(int) $o['id']]);
                    B::registrar('esc_hist_escola', (int) $o['id'], 0, 'Escola "' . $o['nome'] . '"', $o, ['esta_escola' => 0] + $o, ['esta_escola' => 'Esta escola']);
                }
            }
            if ($antes) {
                B::exec('UPDATE esc_hist_escola SET nome = ?, municipio = ?, uf = ?, rede = ?, esta_escola = ?, ativo = ? WHERE id = ?', [$d['nome'], $d['municipio'], $d['uf'], $d['rede'], $d['esta_escola'], $d['ativo'], $id]);
                B::registrar('esc_hist_escola', $id, 0, 'Escola "' . $antes['nome'] . '"', $antes, $d, $rot);
                return $id;
            }
            B::exec('INSERT INTO esc_hist_escola (nome, municipio, uf, rede, esta_escola, ativo) VALUES (?, ?, ?, ?, ?, ?)', [$d['nome'], $d['municipio'], $d['uf'], $d['rede'], $d['esta_escola'], $d['ativo']]);
            $novo = (int) B::pdo()->lastInsertId();
            B::registrar('esc_hist_escola', $novo, 0, 'Escola "' . $d['nome'] . '"', null, $d, $rot);
            return $novo;
        }) ?: 'A escola não foi gravada (' . \Core\Transacao::ultimoErro() . ').';
    }

    /* ---------------------------------------------------------------- os segmentos e as séries */

    /** Os segmentos da escola com o nível gravado (ou o proposto pelo nome) e as séries com o número. */
    public static function segmentos(): array
    {
        $cursos = B::todos('SELECT c.id, c.nome, s.nivel FROM rm_cursos c LEFT JOIN esc_hist_segmento s ON s.curso_id = c.id ORDER BY c.nome');
        foreach ($cursos as &$c) {
            $c['gravado'] = $c['nivel'] !== null;
            $c['nivel'] = $c['nivel'] ?? self::nivelProposto((string) $c['nome']);
            $c['series'] = B::todos('SELECT m.id, m.codnome, m.ordem, g.versao, s.numero FROM rm_cursos_turma_series m INNER JOIN rm_cursos_grades g ON g.id = m.grade_id
                LEFT JOIN esc_hist_serie s ON s.modulo_id = m.id WHERE m.curso_id = ? ORDER BY g.versao, m.ordem', [(int) $c['id']]);
            foreach ($c['series'] as &$m) {
                $m['gravado'] = $m['numero'] !== null;
                $m['numero'] = $m['numero'] !== null ? (int) $m['numero'] : self::numeroProposto((string) $m['codnome']);
            }
        }
        return $cursos;
    }

    public static function nivelProposto(string $nome): string
    {
        $n = mb_strtolower($nome);
        return str_contains($n, 'fundamental') ? 'EF' : (str_contains($n, 'médio') || str_contains($n, 'medio') ? 'EM' : '');
    }

    public static function numeroProposto(string $codnome): int
    {
        return preg_match('/(\d+)\s*[ºª°oa]?/u', $codnome, $m) ? (int) $m[1] : 0;
    }

    /** @return int|string quantas linhas mudaram, ou o motivo */
    public static function salvarSeries(array $niveis, array $numeros)
    {
        $segs = self::segmentos();
        $mud = [];
        foreach ($segs as $c) {
            $cid = (int) $c['id'];
            $nv = (string) ($niveis[$cid] ?? ($c['gravado'] ? $c['nivel'] : ''));
            if ($nv !== '' && !B::nivelValido($nv)) {
                return 'Nível inválido em ' . $c['nome'] . '.';
            }
            $antesNv = B::valor('SELECT nivel FROM esc_hist_segmento WHERE curso_id = ?', [$cid]);
            if ($antesNv === null ? array_key_exists($cid, $niveis) : $antesNv !== $nv) {
                $mud[] = ['seg', $cid, $antesNv, $nv, $c['nome']];
            }
            foreach ($c['series'] as $m) {
                $mid = (int) $m['id'];
                if (!array_key_exists($mid, $numeros)) {
                    continue;
                }
                $num = trim((string) $numeros[$mid]);
                if ($num !== '' && !preg_match('/^\d{1,2}$/', $num)) {
                    return "Número inválido em {$c['nome']}, {$m['codnome']}.";
                }
                if ($num === '' && B::valor('SELECT numero FROM esc_hist_serie WHERE modulo_id = ?', [$mid]) === null) {
                    continue; // sem número e nada gravado: fica como está
                }
                $num = (int) $num;
                $max = $nv !== '' ? B::SERIES[$nv] : 12;
                if ($num < 0 || $num > $max) {
                    return "O número de {$m['codnome']} ({$c['nome']}) vai de 1 a {$max}" . ($nv ? ' (' . B::NIVEIS[$nv] . ')' : '') . '.';
                }
                $antesNum = B::valor('SELECT numero FROM esc_hist_serie WHERE modulo_id = ?', [$mid]);
                if ($antesNum === null || (int) $antesNum !== $num) {
                    $mud[] = ['serie', $mid, $antesNum, $num, $c['nome'] . ', ' . $m['codnome']];
                }
            }
        }
        if (!$mud) {
            return 0;
        }
        $ok = \Core\Transacao::executar(function () use ($mud) {
            foreach ($mud as [$tipo, $id, $antes, $depois, $nome]) {
                if ($tipo === 'seg') {
                    B::exec('REPLACE INTO esc_hist_segmento (curso_id, nivel) VALUES (?, ?)', [$id, $depois]);
                    $f = fn($v) => $v === null ? null : ['nivel' => $v === '' ? 'fora do histórico' : B::NIVEIS[$v]];
                    B::registrar('esc_hist_segmento', $id, 0, 'Segmento "' . $nome . '"', $f($antes), $f($depois), ['nivel' => 'Nível no histórico']);
                } else {
                    B::exec('REPLACE INTO esc_hist_serie (modulo_id, numero) VALUES (?, ?)', [$id, $depois]);
                    B::registrar('esc_hist_serie', $id, 0, 'Série "' . $nome . '"', $antes === null ? null : ['numero' => (string) $antes], ['numero' => (string) $depois], ['numero' => 'Número no histórico']);
                }
            }
            return true;
        });
        return $ok ? count($mud) : 'Não foi gravado (' . \Core\Transacao::ultimoErro() . ').';
    }

    /** Os ids dos segmentos marcados com o nível. @return list<int> */
    public static function cursosDoNivel(string $nivel): array
    {
        return array_map('intval', array_column(B::todos('SELECT curso_id FROM esc_hist_segmento WHERE nivel = ?', [$nivel]), 'curso_id'));
    }

    /** O nível e o número da série (do cadastro). @return array{nivel: string, numero: int}|null */
    public static function serie(int $moduloId): ?array
    {
        $l = B::um('SELECT s.nivel, n.numero FROM rm_cursos_turma_series m INNER JOIN esc_hist_segmento s ON s.curso_id = m.curso_id
            LEFT JOIN esc_hist_serie n ON n.modulo_id = m.id WHERE m.id = ?', [$moduloId]);
        if (!$l || $l['nivel'] === '' || (int) $l['numero'] <= 0) {
            return null;
        }
        return ['nivel' => (string) $l['nivel'], 'numero' => (int) $l['numero']];
    }

    /* ---------------------------------------------------------------- as disciplinas */

    /**
     * As disciplinas do histórico: as das matrizes dos segmentos da escola (o nome do cadastro, ou o da matéria) e as
     * criadas na hora (ano cursado fora). Cada uma com o nome no histórico, a ordem no Fundamental e no Médio e a marca
     * do Ensino Religioso.
     */
    public static function disciplinas(): array
    {
        $r = [];
        foreach (B::todos('SELECT DISTINCT m.id, m.nome FROM uni_rh_cursos_disciplinas_grade l INNER JOIN rm_materias m ON m.id = l.materia_id
                INNER JOIN rm_cursos_grades g ON g.id = l.grade_id INNER JOIN esc_hist_segmento s ON s.curso_id = g.curso_id AND s.nivel <> \'\' ORDER BY m.nome') as $m) {
            $d = B::um('SELECT * FROM esc_hist_disciplina WHERE materia_id = ? ORDER BY id LIMIT 1', [(int) $m['id']]);
            $r[] = ['chave' => 'm' . $m['id'], 'id' => (int) ($d['id'] ?? 0), 'materia_id' => (int) $m['id'], 'materia' => $m['nome'], 'nome' => $d['nome'] ?? $m['nome'],
                'base' => $d['base'] ?? 'comum', 'religioso' => (int) ($d['religioso'] ?? (preg_match('/religios/i', (string) $m['nome']) ? 1 : 0)), 'ordem_ef' => (int) ($d['ordem_ef'] ?? 0), 'ordem_em' => (int) ($d['ordem_em'] ?? 0), 'gravado' => (bool) $d];
        }
        foreach (B::todos('SELECT * FROM esc_hist_disciplina WHERE materia_id = 0 ORDER BY nome') as $d) {
            $r[] = ['chave' => 'd' . $d['id'], 'id' => (int) $d['id'], 'materia_id' => 0, 'materia' => '', 'nome' => $d['nome'], 'base' => $d['base'], 'religioso' => (int) $d['religioso'],
                'ordem_ef' => (int) $d['ordem_ef'], 'ordem_em' => (int) $d['ordem_em'], 'gravado' => true];
        }
        return $r;
    }

    /** A linha do histórico de uma matéria (criada com o nome da matéria se ainda não existe). */
    public static function disciplinaDaMateria(int $materiaId): int
    {
        $id = (int) B::valor('SELECT id FROM esc_hist_disciplina WHERE materia_id = ? ORDER BY id LIMIT 1', [$materiaId]);
        if ($id) {
            return $id;
        }
        $nome = (string) B::valor('SELECT nome FROM rm_materias WHERE id = ?', [$materiaId]);
        B::exec('INSERT INTO esc_hist_disciplina (materia_id, nome, base, religioso) VALUES (?, ?, ?, ?)', [$materiaId, mb_substr($nome, 0, 160), 'comum', preg_match('/religios/i', $nome) ? 1 : 0]);
        return (int) B::pdo()->lastInsertId();
    }

    /** @return int|string quantas mudaram, ou o motivo */
    public static function salvarDisciplinas(array $post)
    {
        $mud = [];
        foreach (self::disciplinas() as $d) {
            $k = $d['chave'];
            if (!isset($post['nome'][$k])) {
                continue;
            }
            $novo = ['nome' => trim((string) $post['nome'][$k]), 'religioso' => !empty($post['religioso'][$k]) ? 1 : 0,
                'ordem_ef' => (int) ($post['ordem_ef'][$k] ?? 0), 'ordem_em' => (int) ($post['ordem_em'][$k] ?? 0)];
            if ($novo['nome'] === '' || mb_strlen($novo['nome']) > 160) {
                return 'O nome no histórico de ' . ($d['materia'] ?: $d['nome']) . ': de 1 a 160 caracteres.';
            }
            if ($novo['ordem_ef'] < 0 || $novo['ordem_em'] < 0 || $novo['ordem_ef'] > 999 || $novo['ordem_em'] > 999) {
                return 'A ordem vai de 0 a 999 (0 = na ordem da matriz).';
            }
            $antes = ['nome' => $d['nome'], 'religioso' => $d['religioso'], 'ordem_ef' => $d['ordem_ef'], 'ordem_em' => $d['ordem_em']];
            if ($antes != $novo || (!$d['gravado'] && $novo !== $antes)) {
                $mud[] = [$d, $antes, $novo];
            }
        }
        if (!$mud) {
            return 0;
        }
        $ok = \Core\Transacao::executar(function () use ($mud) {
            foreach ($mud as [$d, $antes, $novo]) {
                $id = $d['id'] ?: self::disciplinaDaMateria($d['materia_id']);
                B::exec('UPDATE esc_hist_disciplina SET nome = ?, religioso = ?, ordem_ef = ?, ordem_em = ? WHERE id = ?', [$novo['nome'], $novo['religioso'], $novo['ordem_ef'], $novo['ordem_em'], $id]);
                B::registrar('esc_hist_disciplina', $id, 0, 'Disciplina "' . ($d['materia'] ?: $d['nome']) . '" no histórico', $antes, $novo,
                    ['nome' => 'Nome no histórico', 'religioso' => 'Ensino Religioso', 'ordem_ef' => 'Ordem no Fundamental', 'ordem_em' => 'Ordem no Médio']);
            }
            return true;
        });
        return $ok ? count($mud) : 'Não foi gravado (' . \Core\Transacao::ultimoErro() . ').';
    }

    /* ---------------------------------------------------------------- o aluno */

    public static function aluno(int $alunoId): ?array
    {
        $a = B::um('SELECT u.id, u.rm, u.nome, i.rg, i.rg_org, i.rg_uf, i.nasc_local, i.nasc_uf, i.nasc_nac, i.nasc_data
            FROM col_usuarios u LEFT JOIN col_usuarios_informacoes i ON i.col_user_id = u.id WHERE u.id = ? AND u.col_niveis_acesso_id = 1', [$alunoId]);
        if (!$a) {
            return null;
        }
        $h = B::um('SELECT * FROM esc_hist_aluno WHERE aluno_id = ?', [$alunoId]) ?: ['ra' => '', 'certidao' => '', 'ef_conclusao_ano' => 0, 'ef_conclusao_escola_id' => 0, 'ef_publicacao' => '', 'obs_livre' => ''];
        unset($h['aluno_id']);
        return $a + $h + ['prova' => B::prova('col_usuarios', $alunoId)];
    }

    public const CAMPOS_ALUNO = ['ra' => 'RA (registro na rede estadual)', 'certidao' => 'Certidão de nascimento', 'obs_livre' => 'Observação livre',
        'ef_conclusao_ano' => 'Conclusão do Fundamental: ano', 'ef_conclusao_escola_id' => 'Conclusão do Fundamental: escola', 'ef_publicacao' => 'Conclusão do Fundamental: número de publicação'];

    /** @return int|string 1 gravou, 0 nada mudou, ou o motivo */
    public static function salvarAluno(int $alunoId, array $post)
    {
        $a = self::aluno($alunoId);
        if (!$a) {
            return 'Aluno não encontrado.';
        }
        $d = [
            'ra' => trim((string) ($post['ra'] ?? '')), 'certidao' => trim((string) ($post['certidao'] ?? '')),
            'obs_livre' => trim(str_replace("\r", '', (string) ($post['obs_livre'] ?? ''))),
            'ef_conclusao_ano' => (int) ($post['ef_conclusao_ano'] ?? 0), 'ef_conclusao_escola_id' => (int) ($post['ef_conclusao_escola_id'] ?? 0),
            'ef_publicacao' => trim((string) ($post['ef_publicacao'] ?? '')),
        ];
        if (mb_strlen($d['ra']) > 30 || mb_strlen($d['certidao']) > 300 || mb_strlen($d['obs_livre']) > 2000 || mb_strlen($d['ef_publicacao']) > 60) {
            return 'Algum campo passou do tamanho (RA até 30, certidão até 300, observação até 2.000, publicação até 60).';
        }
        if ($d['ef_conclusao_ano'] !== 0 && ($d['ef_conclusao_ano'] < 1950 || $d['ef_conclusao_ano'] > 2100)) {
            return 'O ano de conclusão do Fundamental é inválido.';
        }
        if ($d['ef_conclusao_escola_id'] && !B::valor('SELECT id FROM esc_hist_escola WHERE id = ?', [$d['ef_conclusao_escola_id']])) {
            return 'A escola de conclusão do Fundamental não está no cadastro de escolas.';
        }
        $antes = array_intersect_key($a, $d);
        $existe = (bool) B::valor('SELECT 1 FROM esc_hist_aluno WHERE aluno_id = ?', [$alunoId]);
        $nomesEscola = function (array $x) {
            $x['ef_conclusao_escola_id'] = $x['ef_conclusao_escola_id'] ? (string) B::valor('SELECT nome FROM esc_hist_escola WHERE id = ?', [$x['ef_conclusao_escola_id']]) : '';
            $x['ef_conclusao_ano'] = $x['ef_conclusao_ano'] ? (string) $x['ef_conclusao_ano'] : '';
            return $x;
        };
        $ok = \Core\Transacao::executar(function () use ($alunoId, $a, $d, $antes, $existe, $nomesEscola) {
            $registrou = B::registrar('esc_hist_aluno', $alunoId, $alunoId, 'Dados do aluno para o histórico (' . $a['nome'] . ')', $existe ? $nomesEscola($antes) : $nomesEscola(array_map(fn($v) => is_int($v) ? 0 : '', $antes)), $nomesEscola($d), self::CAMPOS_ALUNO);
            if (!$registrou) {
                return 0;
            }
            B::exec('REPLACE INTO esc_hist_aluno (aluno_id, ra, certidao, ef_conclusao_ano, ef_conclusao_escola_id, ef_publicacao, obs_livre) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$alunoId, $d['ra'], $d['certidao'], $d['ef_conclusao_ano'], $d['ef_conclusao_escola_id'], $d['ef_publicacao'], $d['obs_livre']]);
            return 1;
        });
        return $ok === false ? 'Não foi gravado (' . \Core\Transacao::ultimoErro() . ').' : (int) $ok;
    }
}
