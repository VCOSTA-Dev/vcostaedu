<?php

namespace App\adms\Models\historico;

if (!defined('VCOSTAEDU')) { // E6 (D-376): o portal também carrega (atestado do aluno)
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * SEÇÕES do modelo de histórico (E6b): tipos, conteúdo padrão (neutro, sem dado de cliente),
 * colunas do quadro de disciplinas e a leitura do formulário do editor.
 *
 * Cada seção: tipo, ativa, ordem, html (HTML controlado com {{VARIÁVEIS}}) e as opções
 * próprias — cabeçalho (logo), quadro (colunas), TCC (só se houver), assinaturas (blocos),
 * rodapé (paginação, QR code).
 */
class AdmsHistoricoSecoes
{
    public const TIPOS = [
        'cabecalho' => 'Cabeçalho da instituição',
        'tipo_documento' => 'Tipo do documento',
        'aluno' => 'Identificação do aluno',
        'matricula' => 'Curso e matrícula',
        'ato' => 'Ato legal',
        'quadro' => 'Quadro de disciplinas',
        'tcc' => 'TCC',
        'totais' => 'Totais e período',
        'observacoes' => 'Observações',
        'assinaturas' => 'Assinaturas',
        'rodape' => 'Rodapé',
    ];

    public const COLUNAS = [
        'codigo' => 'Código', 'disciplina' => 'Disciplina', 'modulo' => 'Módulo', 'ch' => 'CH',
        'periodo' => 'Período de realização', 'docente' => 'Docente', 'titulacao' => 'Titulação',
        'nota' => 'Nota final', 'frequencia' => 'Frequência', 'situacao' => 'Situação',
    ];

    public const ALINHAMENTOS = ['left' => 'Esquerda', 'center' => 'Centro', 'right' => 'Direita'];
    public const MAX_ASSINATURAS = 6;

    /** O modelo padrão NEUTRO de cada tipo (o conteudo.sql traz os dois gerados daqui). */
    public static function padrao(string $tipo): array
    {
        $final = $tipo === 'FINAL';
        $colunas = [];
        $escolhidas = $final
            ? ['codigo' => '9%', 'disciplina' => '27%', 'ch' => '6%', 'periodo' => '15%', 'docente' => '17%', 'titulacao' => '9%', 'nota' => '6%', 'frequencia' => '6%', 'situacao' => '5%']
            : ['codigo' => '9%', 'disciplina' => '28%', 'ch' => '6%', 'periodo' => '15%', 'docente' => '18%', 'nota' => '8%', 'frequencia' => '8%', 'situacao' => '8%'];
        $o = 1;
        foreach ($escolhidas as $campo => $larg) {
            $colunas[] = ['campo' => $campo, 'rotulo' => self::COLUNAS[$campo], 'largura' => $larg, 'alinhamento' => in_array($campo, ['codigo', 'disciplina', 'docente', 'periodo', 'titulacao'], true) ? 'left' : 'center', 'ordem' => $o++];
        }
        $s = [
            ['tipo' => 'cabecalho', 'ativa' => true, 'logo' => true, 'html' =>
                '<p style="text-align: center"><b>{{INSTITUICAO_NOME}}</b></p><p style="text-align: center">{{CURSO_NOME}}</p><p style="text-align: center">Polo: {{POLO_NOME}}</p>'],
            ['tipo' => 'tipo_documento', 'ativa' => true, 'html' => '<h2 style="text-align: center">HISTÓRICO ESCOLAR ' . ($final ? 'FINAL' : 'PARCIAL') . '</h2>'],
            ['tipo' => 'aluno', 'ativa' => true, 'html' =>
                '<table><tr><td style="width: 60%"><b>Nome:</b> {{ALUNO_NOME}}</td><td><b>RA:</b> {{ALUNO_RA}}</td></tr>'
                . '<tr><td><b>Nascimento:</b> {{ALUNO_NASCIMENTO}} — {{ALUNO_NATURALIDADE}}</td><td><b>CPF:</b> {{ALUNO_CPF}}</td></tr></table>'],
            ['tipo' => 'matricula', 'ativa' => true, 'html' =>
                '<p><b>Curso:</b> {{CURSO_NOME}} — <b>Modalidade:</b> {{MODALIDADE}} — <b>Polo:</b> {{POLO_NOME}}</p>'
                . '<p><b>Ingresso:</b> {{MATRICULA_INGRESSO_FORMA}}, em {{MATRICULA_INGRESSO_DATA}} — <b>Situação:</b> {{MATRICULA_SITUACAO}}' . ($final ? ', em {{MATRICULA_CONCLUSAO}}' : '') . '</p>'],
            ['tipo' => 'ato', 'ativa' => true, 'html' => '<p><b>Ato legal:</b> {{ATO_DESCRICAO}}</p>'],
            ['tipo' => 'quadro', 'ativa' => true, 'html' => '', 'colunas' => $colunas],
            ['tipo' => 'tcc', 'ativa' => true, 'somenteSeHouver' => true, 'html' =>
                '<p><b>Trabalho de conclusão de curso:</b> {{TCC_TITULO}}</p><p>Orientação: {{TCC_ORIENTADOR}} ({{TCC_ORIENTADOR_TITULACAO}}) — Nota: {{TCC_NOTA}} — {{TCC_DATA}} — {{TCC_SITUACAO}}</p>'],
            ['tipo' => 'totais', 'ativa' => true, 'html' =>
                '<p><b>Carga horária:</b> exigida {{TOTAL_CH_EXIGIDA}} h; integralizada {{TOTAL_CH_INTEGRALIZADA}} h ({{TOTAL_APROVADAS}} de {{TOTAL_DISCIPLINAS}} disciplinas).</p>'
                . '<p><b>Período de realização:</b> {{PERIODO_INICIO}} a {{PERIODO_FIM}} — <b>duração total:</b> {{DURACAO_MESES}} meses.</p>'],
            ['tipo' => 'observacoes', 'ativa' => false, 'html' => ''],
            ['tipo' => 'assinaturas', 'ativa' => true, 'html' => '<p style="text-align: right">{{EMISSAO_DATA_EXTENSO}}</p>',
                'blocos' => [['nome' => '', 'cargo' => 'Secretaria acadêmica', 'imagem' => null], ['nome' => '', 'cargo' => 'Direção', 'imagem' => null]]],
            ['tipo' => 'rodape', 'ativa' => true, 'paginacao' => true, 'qr' => true, 'html' =>
                '<p style="font-size: 7pt">Emitido em {{EMISSAO_DATA}}. Código de verificação: <b>{{EMISSAO_CODIGO}}</b>. Confira a autenticidade em {{EMISSAO_URL_VERIFICACAO}}</p>'],
        ];
        foreach ($s as $i => $x) {
            $s[$i]['ordem'] = $i + 1;
        }
        return ['secoes' => $s];
    }

    /** Seções em ordem (ativas e inativas), completando o que faltar com o padrão. */
    public static function ordenadas(array $modelo, string $tipo): array
    {
        $por = [];
        foreach ($modelo['secoes'] ?? [] as $s) {
            if (isset(self::TIPOS[$s['tipo'] ?? ''])) {
                $por[$s['tipo']] = $s;
            }
        }
        foreach (self::padrao($tipo)['secoes'] as $s) {
            if (!isset($por[$s['tipo']])) {
                $s['ativa'] = false;
                $s['ordem'] = 100 + $s['ordem'];
                $por[$s['tipo']] = $s;
            }
        }
        $lista = array_values($por);
        usort($lista, fn($a, $b) => [(int) $a['ordem'], array_search($a['tipo'], array_keys(self::TIPOS))] <=> [(int) $b['ordem'], array_search($b['tipo'], array_keys(self::TIPOS))]);
        return $lista;
    }

    /**
     * Lê o formulário do editor. Recusa (em `$erros`) HTML com item proibido, variável
     * desconhecida, coluna inválida. `$atuais` = seções da versão atual (para as imagens
     * de assinatura já enviadas). `$imagens[i]` = nome do arquivo novo do bloco i.
     */
    public static function doFormulario(array $post, array $atuais, array $imagens, ?array &$erros = null): array
    {
        $erros = [];
        $blocosAtuais = [];
        foreach ($atuais['secoes'] ?? [] as $s) {
            if (($s['tipo'] ?? '') === 'assinaturas') {
                $blocosAtuais = $s['blocos'] ?? [];
            }
        }
        $secoes = [];
        foreach (array_keys(self::TIPOS) as $k => $tipo) {
            $f = $post[$tipo] ?? [];
            $rotulo = self::TIPOS[$tipo];
            $html = (string) ($f['html'] ?? '');
            $limpo = AdmsHtmlControlado::limpar($html, $removidos);
            if ($removidos) {
                $erros[] = "{$rotulo}: HTML não permitido — " . implode(', ', $removidos) . '.';
            }
            $desc = AdmsHistoricoVariaveis::desconhecidas($html);
            if ($desc) {
                $erros[] = "{$rotulo}: variável desconhecida — " . implode(', ', array_map(fn($v) => '{{' . $v . '}}', $desc)) . '.';
            }
            $ordem = (int) ($f['ordem'] ?? ($k + 1));
            $s = ['tipo' => $tipo, 'ativa' => !empty($f['ativa']), 'ordem' => max(1, min(99, $ordem)), 'html' => $limpo];
            if ($tipo === 'cabecalho') {
                $s['logo'] = !empty($f['logo']);
            } elseif ($tipo === 'tcc') {
                $s['somenteSeHouver'] = !empty($f['somenteSeHouver']);
            } elseif ($tipo === 'rodape') {
                $s['paginacao'] = !empty($f['paginacao']);
                $s['qr'] = !empty($f['qr']);
            } elseif ($tipo === 'quadro') {
                $s['colunas'] = [];
                foreach (self::COLUNAS as $campo => $padrao) {
                    $c = $f['colunas'][$campo] ?? [];
                    if (empty($c['incluir'])) {
                        continue;
                    }
                    $rot = trim(strip_tags((string) ($c['rotulo'] ?? '')));
                    $larg = trim((string) ($c['largura'] ?? ''));
                    $alin = (string) ($c['alinhamento'] ?? 'left');
                    if ($rot === '' || mb_strlen($rot) > 40) {
                        $erros[] = "Quadro, coluna {$padrao}: rótulo vazio ou com mais de 40 caracteres.";
                    }
                    if ($larg !== '' && !preg_match('/^\d{1,2}(\.\d)?%$/', $larg)) {
                        $erros[] = "Quadro, coluna {$padrao}: largura deve ser em % (ex.: 12%).";
                    }
                    if (!isset(self::ALINHAMENTOS[$alin])) {
                        $alin = 'left';
                    }
                    $s['colunas'][] = ['campo' => $campo, 'rotulo' => $rot, 'largura' => $larg, 'alinhamento' => $alin, 'ordem' => (int) ($c['ordem'] ?? 0)];
                }
                usort($s['colunas'], fn($a, $b) => [$a['ordem'], array_search($a['campo'], array_keys(self::COLUNAS))] <=> [$b['ordem'], array_search($b['campo'], array_keys(self::COLUNAS))]);
                foreach ($s['colunas'] as $i => $c) {
                    $s['colunas'][$i]['ordem'] = $i + 1;
                }
                if ($s['ativa'] && !$s['colunas']) {
                    $erros[] = 'Quadro de disciplinas: escolha ao menos uma coluna.';
                }
            } elseif ($tipo === 'assinaturas') {
                $s['blocos'] = [];
                foreach (array_values($f['blocos'] ?? []) as $i => $b) {
                    $nome = trim(strip_tags((string) ($b['nome'] ?? '')));
                    $cargo = trim(strip_tags((string) ($b['cargo'] ?? '')));
                    if ($nome === '' && $cargo === '') {
                        continue;
                    }
                    if (mb_strlen($nome) > 120 || mb_strlen($cargo) > 120) {
                        $erros[] = 'Assinaturas: nome e cargo com até 120 caracteres.';
                    }
                    $img = $imagens[$i] ?? null;
                    if (!$img && empty($b['removerImagem'])) {
                        $atual = (string) ($b['imagemAtual'] ?? '');
                        $valida = array_filter($blocosAtuais, fn($x) => ($x['imagem'] ?? '') === $atual);
                        $img = ($atual !== '' && $valida) ? $atual : null;
                    }
                    $s['blocos'][] = ['nome' => $nome, 'cargo' => $cargo, 'imagem' => $img];
                }
                if (count($s['blocos']) > self::MAX_ASSINATURAS) {
                    $erros[] = 'Assinaturas: no máximo ' . self::MAX_ASSINATURAS . ' blocos.';
                }
            }
            $secoes[] = $s;
        }
        usort($secoes, fn($a, $b) => [$a['ordem'], array_search($a['tipo'], array_keys(self::TIPOS))] <=> [$b['ordem'], array_search($b['tipo'], array_keys(self::TIPOS))]);
        foreach ($secoes as $i => $x) {
            $secoes[$i]['ordem'] = $i + 1;
        }
        return ['secoes' => $secoes];
    }
}
