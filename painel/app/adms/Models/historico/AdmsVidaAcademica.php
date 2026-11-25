<?php

namespace App\adms\Models\historico;

use App\adms\Models\notas\AdmsCalculoNotas as Calc;

if (!defined('VCOSTAEDU')) { // E6 (D-376): o portal também carrega (atestado do aluno)
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * FONTE ÚNICA DA VIDA ACADÊMICA (E6a). Dada uma matrícula no curso, devolve um objeto
 * normalizado com tudo o que o histórico (parcial e final, E6b) vai mostrar: aluno,
 * matrícula, instituição, ato legal aplicável, módulos da grade com as disciplinas e as
 * tentativas, TCC, totais e alertas.
 *
 * Os números de cada tentativa vêm de Calc::alunoNaOferta — o RESULTADO GRAVADO no
 * encerramento (rm_disciplina_media_final) quando a oferta está encerrada, e o motor único
 * (média parcial) quando está aberta. É a mesma chamada do boletim: nenhum cálculo paralelo.
 *
 * Tentativas: cada oferta do curso em que o aluno está enturmado, ligada à linha da matriz
 * (ou, no legado sem linha, à mesma matéria). VIGENTE = a última ENCERRADA (por data de
 * encerramento); sem nenhuma encerrada, a mais recente (em curso). O modelo FINAL (E6b) usa
 * só a vigente; o PARCIAL mostra todas, com as atividades.
 */
class AdmsVidaAcademica
{
    public const SITUACOES = ['Aprovado', 'Reprovado', 'Em curso', 'Não cursada'];

    /**
     * `$dataReferencia` (E6b, D-177): data de referência do ato legal. O PARCIAL passa hoje;
     * sem ela (FINAL e prévia técnica), vale a data de conclusão ou, sem conclusão, hoje.
     */
    public static function montar(int $mcId, ?string $dataReferencia = null): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT mc.*, c.nome AS curso, c.exige_tcc, c.nivel AS cursoNivel, c.nome_extenso AS cursoNomeExtenso,
                    c.cabecalho_documento AS cursoCabecalho, c.abreviatura AS cursoSigla, c.localidade AS poloCurso, c.modalidade_id AS modalidadeCurso,
                    g.versao AS grade, g.descricao AS gradeDescricao, t.nome AS formaIngresso, s.nome AS situacao, s.rotulo_documento AS situacaoDocumento
               FROM rm_matriculas_curso mc
               INNER JOIN rm_cursos c ON c.id = mc.curso_id
               INNER JOIN rm_cursos_grades g ON g.id = mc.grade_id
               LEFT JOIN rm_matricula_tipos t ON t.id = mc.forma_ingresso_id
               LEFT JOIN rm_matriculas_situacao s ON s.id = mc.situacao_id
              WHERE mc.id = :id LIMIT :limit",
            "id={$mcId}&limit=1"
        );
        $mc = $ler->getResultado()[0] ?? null;
        if (!$mc) {
            return null;
        }
        $aluno = self::aluno((int) $mc['usuario_id']);
        $rm = (string) $aluno['rm'];
        $alertas = [];
        /* E8c (A19/A15, D-267): dados pessoais que o histórico exige, valha o modelo que valer. Antes, só a variável
           usada e vazia virava alerta; um modelo sem {{ALUNO_CPF}} deixava sair FINAL sem CPF (contra a D-204). Fonte
           única: a prévia e a emissão leem estes alertas; no FINAL, qualquer alerta bloqueia. */
        $faltam = self::dadosPessoaisFaltando($aluno);
        if ($faltam) {
            $alertas[] = 'Dados pessoais do aluno incompletos (ficha do aluno → Editar): falta ' . implode(', ', $faltam) . '.';
        }

        /* matrícula: polo e modalidade da matrícula; vazio = os do curso (D-115) */
        $poloId = (int) ($mc['polo_id'] ?: $mc['poloCurso']);
        $modId = (int) ($mc['modalidade_id'] ?: $mc['modalidadeCurso']);
        $matricula = [
            'id' => (int) $mc['id'], 'curso_id' => (int) $mc['curso_id'], 'curso' => $mc['curso'],
            'grade_id' => (int) $mc['grade_id'], 'grade' => (int) $mc['grade'],
            'polo_id' => $poloId, 'polo' => self::nomePolo($poloId), 'poloDoCurso' => empty($mc['polo_id']),
            'modalidade_id' => $modId, 'modalidade' => \App\adms\Models\modalidades\AdmsModalidades::nome($modId), 'modalidadeDoCurso' => empty($mc['modalidade_id']),
            'formaIngresso' => $mc['formaIngresso'], 'dataIngresso' => $mc['data_ingresso'],
            'situacao_id' => (int) $mc['situacao_id'], 'situacao' => $mc['situacao'], 'dataConclusao' => $mc['data_conclusao'],
            /* E1b (D-354, D-355): previsão de conclusão e o rótulo da situação nos documentos (vazio = o nome em maiúsculas) */
            'previsaoConclusao' => $mc['previsao_conclusao'] ?? null,
            'situacaoDocumento' => trim((string) ($mc['situacaoDocumento'] ?? '')) !== '' ? $mc['situacaoDocumento'] : mb_strtoupper((string) $mc['situacao']),
            'exigeTcc' => (int) $mc['exige_tcc'] === 1,
            /* E1a (D-340): como o curso sai nos documentos */
            'cursoNivel' => $mc['cursoNivel'], 'cursoNomeExtenso' => $mc['cursoNomeExtenso'],
            'cursoCabecalho' => $mc['cursoCabecalho'], 'cursoSigla' => $mc['cursoSigla'],
        ];

        /* instituição e ato legal (pela modalidade da MATRÍCULA; data de referência = conclusão ou hoje) */
        $inst = \App\adms\Models\helper\AdmsInstituicao::dados();
        $instituicao = [
            'cadastrada' => (bool) $inst, 'nome' => \App\adms\Models\helper\AdmsInstituicao::nome(),
            'mantenedora' => $inst['razao_social'] ?? null, 'cnpj' => $inst['cnpj'] ?? null, 'codigoEmec' => $inst['codigo_emec'] ?? null,
            'endereco' => \App\adms\Models\helper\AdmsInstituicao::endereco(), 'logo' => \App\adms\Models\helper\AdmsInstituicao::logoUrl(),
            /* E1a (D-339): cidade, site, telefone, e-mail e as linhas do cabeçalho vão para o snapshot da emissão */
            'cidade' => $inst['cidade'] ?? null, 'site' => $inst['site'] ?? null, 'telefone' => $inst['telefone'] ?? null,
            'email' => $inst['email'] ?? null, 'cabecalho' => \App\adms\Models\helper\AdmsInstituicao::cabecalho($inst ?: []),
            /* 1.13.0 (D-389): as partes do endereço, para o rodapé no formato do Word (cada parte num trecho condicional) */
            'logradouro' => $inst['logradouro'] ?? null, 'numero' => $inst['numero'] ?? null, 'complemento' => $inst['complemento'] ?? null,
            'bairro' => $inst['bairro'] ?? null, 'cep' => $inst['cep'] ?? null, 'uf' => $inst['uf'] ?? null,
        ];
        if (!$inst) {
            $alertas[] = 'Dados da instituição não cadastrados (Configurações → Dados da instituição): o cabeçalho usa o nome da configuração do servidor.';
        }
        $dataRef = $dataReferencia ?: ($mc['data_conclusao'] ?: date('Y-m-d'));
        $v = \App\adms\Models\atosLegais\AdmsAtosLegais::vigente($modId, $dataRef);
        $ato = [
            'dataReferencia' => $dataRef, 'ato' => $v['ato'],
            'descricao' => $v['ato'] ? \App\adms\Models\atosLegais\AdmsAtosLegais::descrever($v['ato']) : null, 'alerta' => $v['alerta'],
        ];
        if ($v['alerta']) {
            $alertas[] = $v['alerta'];
        }

        /* módulos e disciplinas da grade, com as tentativas */
        $tentativasPorLinha = self::tentativas($rm, (int) $mc['curso_id'], (int) $mc['grade_id']);
        $modulos = [];
        $chExigida = 0;
        $chIntegralizada = 0;
        $datas = [];
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, codnome, ordem FROM rm_cursos_turma_series WHERE grade_id = :g ORDER BY ordem, id", "g={$mc['grade_id']}");
        foreach ($ler->getResultado() ?: [] as $m) {
            $ler2 = new \App\adms\Models\helper\AdmsRead();
            $ler2->fullRead(
                "SELECT l.id, l.materia_id, COALESCE(l.cargahoraria, mt.carga_horaria) AS ch, mt.nome, mt.abreviacao
                   FROM uni_rh_cursos_disciplinas_grade l INNER JOIN rm_materias mt ON mt.id = l.materia_id
                  WHERE l.modulo_id = :m ORDER BY l.ordem, mt.nome", // E1b (D-353): a ordem da grade; empate, o nome
                "m={$m['id']}"
            );
            $disciplinas = [];
            $chModulo = 0;
            foreach ($ler2->getResultado() ?: [] as $l) {
                $ch = (int) $l['ch'];
                $chModulo += $ch;
                $tent = $tentativasPorLinha[(int) $l['id']] ?? [];
                $vig = self::vigente($tent);
                foreach ($tent as $i => $t) {
                    $tent[$i]['vigente'] = $vig !== null && $t['oferta_id'] === $tent[$vig]['oferta_id'];
                    if ($t['periodoInicio']) {
                        $datas[] = $t['periodoInicio'];
                    }
                    if ($t['periodoFim']) {
                        $datas[] = $t['periodoFim'];
                    }
                }
                $vt = $vig !== null ? $tent[$vig] : null;
                $situacao = !$vt ? 'Não cursada' : (!$vt['encerrada'] ? 'Em curso' : ((int) $vt['resultado'] === Calc::APROVADO ? 'Aprovado' : 'Reprovado'));
                if ($situacao === 'Aprovado') {
                    $chIntegralizada += $ch;
                }
                $nomeDisc = $l['nome'];
                if ($vt) {
                    if (!$vt['docentes']) {
                        $alertas[] = "{$nomeDisc} ({$vt['turma']}/{$vt['ano']}): disciplina sem docente.";
                    }
                    foreach ($vt['docentes'] as $dc) {
                        if (!$dc['titulacao']) {
                            $alertas[] = "{$nomeDisc}: docente {$dc['nome']} sem titulação.";
                        }
                    }
                    if ((int) $vt['chOferta'] !== $ch) {
                        $alertas[] = "{$nomeDisc}: CH divergente (matriz {$ch} h × oferta {$vt['chOferta']} h).";
                    }
                    if ($vt['periodoOrigem'] !== 'oferta') {
                        $alertas[] = "{$nomeDisc} ({$vt['turma']}/{$vt['ano']}): oferta sem período de realização" . ($vt['periodoOrigem'] === 'atividades' ? ' (usadas as datas das atividades).' : '.');
                    }
                }
                $disciplinas[] = [
                    'linha_id' => (int) $l['id'], 'codigo' => $l['abreviacao'] ?: (string) $l['materia_id'], 'nome' => $nomeDisc, 'ch' => $ch,
                    'situacao' => $situacao, 'vigente' => $vt, 'tentativas' => $tent,
                ];
            }
            $chExigida += $chModulo;
            $modulos[] = ['id' => (int) $m['id'], 'ordem' => (int) $m['ordem'], 'nome' => $m['codnome'], 'ch' => $chModulo, 'disciplinas' => $disciplinas];
        }
        /* 1.16.2 (D-451): a nota mínima de cada disciplina DO DOCUMENTO tem de ser a da legenda (NOTA_MINIMA, a regra do TCC
           e dos documentos do curso); senão, o documento oficial diria uma regra falsa. Encerrada: a GRAVADA no fechamento.
           Um alerta como os outros (no FINAL, bloqueia; o atestado não lê estes alertas). */
        $doDocumento = [];
        foreach ($modulos as $mm) {
            foreach ($mm['disciplinas'] as $dd) {
                $doDocumento[] = ['nome' => $dd['nome'], 'linha_id' => $dd['linha_id'], 'oferta_id' => $dd['vigente']['oferta_id'] ?? null, 'encerrada' => !empty($dd['vigente']['encerrada'])];
            }
        }
        if (!class_exists(\App\adms\Models\notas\AdmsRegraMedia::class) && class_exists(\App\adms\Models\helper\AdmsPainelPonte::class)) {
            \App\adms\Models\helper\AdmsPainelPonte::carregar();
        }
        $alertaMin = \App\adms\Models\notas\AdmsRegraMedia::alertaDivergenciaDocumento((string) Calc::regras((int) $mc['curso_id'])['mediaMinima'], $doDocumento, (string) $rm);
        if ($alertaMin) {
            $alertas[] = $alertaMin;
        }
        $fora = $tentativasPorLinha[0] ?? [];
        foreach ($fora as $t) {
            $alertas[] = "{$t['materia']} ({$t['turma']}/{$t['ano']}): o aluno está enturmado, mas a oferta não pertence à grade da matrícula.";
        }

        /* TCC */
        $tcc = \App\adms\Models\tcc\AdmsTcc::daMatricula($mcId);
        if ($tcc) {
            $c = Calc::centesimos($tcc['nota']);
            $tcc['notaFormatada'] = $c === null ? null : Calc::formatar(number_format($c / 100, 2, '.', ''), $c % 10 ? 2 : 1);
            $tcc['situacaoNome'] = \App\adms\Models\tcc\AdmsTcc::SITUACOES[(int) $tcc['situacao']] ?? '?';
            if ($tcc['data_defesa']) {
                $datas[] = $tcc['data_defesa'];
            }
        }
        if ($matricula['exigeTcc'] && (!$tcc || (int) $tcc['situacao'] !== 2)) {
            $alertas[] = 'O curso exige TCC e ' . (!$tcc ? 'não há TCC cadastrado.' : 'o TCC está "' . $tcc['situacaoNome'] . '".');
        }

        sort($datas);
        $inicio = $datas[0] ?? null;
        $fim = $datas ? end($datas) : null;
        $meses = null;
        if ($inicio && $fim) {
            $a = new \DateTime($inicio);
            $b = new \DateTime($fim);
            $d = $a->diff($b);
            $meses = $d->y * 12 + $d->m + ($d->d > 0 ? 1 : 0);
        }
        /* E5 (D-314): MÉDIA FINAL DO ALUNO — só as disciplinas cuja tentativa vigente está ENCERRADA (a média gravada),
           aritmética simples ou ponderada pela CH (parâmetro da instituição). Guardam-se só as somas, em centésimos: o
           documento arredonda com as casas dele. Nada aqui mexe na média das disciplinas. */
        $mf = ['modo' => \App\adms\Models\helper\AdmsParametro::mediaFinalModo(), 'n' => 0, 'soma' => 0, 'somaPonderada' => 0, 'somaCh' => 0];
        foreach ($modulos as $mm) {
            foreach ($mm['disciplinas'] as $dd) {
                $vg = $dd['vigente'];
                $cm = $vg && $vg['encerrada'] && $vg['media'] !== null ? Calc::centesimos((string) $vg['media']) : null;
                if ($cm === null) {
                    continue;
                }
                $mf['n']++;
                $mf['soma'] += $cm;
                $mf['somaPonderada'] += $cm * (int) $dd['ch'];
                $mf['somaCh'] += (int) $dd['ch'];
            }
        }
        $totais = [            'chExigida' => $chExigida, 'chIntegralizada' => $chIntegralizada,
            'disciplinas' => array_sum(array_map(fn($m) => count($m['disciplinas']), $modulos)),
            'aprovadas' => array_sum(array_map(fn($m) => count(array_filter($m['disciplinas'], fn($d) => $d['situacao'] === 'Aprovado')), $modulos)),
            'periodoInicio' => $inicio, 'periodoFim' => $fim, 'duracaoMeses' => $meses,
        ];
        if ($matricula['situacao_id'] === 2 && !$matricula['dataConclusao']) {
            $alertas[] = 'Matrícula "Concluído" sem data de conclusão.';
        }

        return [
            'aluno' => $aluno, 'matricula' => $matricula, 'instituicao' => $instituicao, 'atoLegal' => $ato,
            'modulos' => $modulos, 'foraDaGrade' => $fora, 'tcc' => $tcc, 'totais' => $totais, 'mediaFinalAluno' => $mf, // E5 (D-314)
            'regras' => Calc::regras((int) $mc['curso_id']), 'alertas' => array_values(array_unique($alertas)), // E2 (D-360): a do curso, se houver
            'textos' => self::textos((int) ($aluno['genero'] ?? 0)), // E1b (D-357) e E2 (D-363): concordância e legenda
        ];
    }

    /**
     * O que falta para CONCLUIR: disciplinas da grade não aprovadas (na vigente) e o TCC, se exigido. 1.17.2 (D-464): a
     * conta é a de `pendenciasConclusaoLote` (uma implementação só), com uma matrícula.
     */
    public static function pendenciasConclusao(int $mcId): array
    {
        return self::pendenciasConclusaoLote([$mcId])[$mcId]['pendencias'] ?? ['Matrícula não encontrada.'];
    }

    /**
     * 1.17.2 (D-464) — O QUE FALTA PARA CONCLUIR, EM LOTE: a mesma regra de `montar()` + `pendenciasConclusao()`, para muitas
     * matrículas em 6 consultas (nenhuma por aluno, nenhum motor). Situação de cada disciplina da grade pela tentativa
     * VIGENTE (a última encerrada; sem encerrada, a mais recente): sem tentativa = Não cursada; aberta = Em curso;
     * encerrada = Aprovado só com o resultado GRAVADO 1 (é o que `alunoNaOferta` lê na encerrada), senão Reprovado.
     * Devolve [mcId => ['pendencias' => [...], 'chExigida', 'chIntegralizada', 'exigeTcc', 'tcc' => nome|null, 'notaTcc' => texto|null]].
     */
    public static function pendenciasConclusaoLote(array $mcIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $mcIds))));
        if (!$ids) {
            return [];
        }
        $lista = implode(',', $ids);
        $ler = fn(string $sql) => (function () use ($sql) { $l = new \App\adms\Models\helper\AdmsRead(); $l->fullRead($sql); return $l->getResultado() ?: []; })();
        /* as matrículas (as mesmas junções de montar: sem curso ou sem grade, "Matrícula não encontrada.") */
        $mcs = [];
        foreach ($ler("SELECT mc.id, mc.curso_id, mc.grade_id, c.exige_tcc, u.rm FROM rm_matriculas_curso mc INNER JOIN rm_cursos c ON c.id = mc.curso_id
                        INNER JOIN rm_cursos_grades g ON g.id = mc.grade_id LEFT JOIN col_usuarios u ON u.id = mc.usuario_id WHERE mc.id IN ({$lista})") as $m) {
            $mcs[(int) $m['id']] = $m;
        }
        if (!$mcs) {
            return [];
        }
        $grades = implode(',', array_unique(array_map(fn($m) => (int) $m['grade_id'], $mcs)));
        /* as linhas da grade, por módulo, na ordem do documento */
        $linhas = [];
        foreach ($ler("SELECT s.grade_id, s.codnome AS modulo, l.id, COALESCE(l.cargahoraria, mt.carga_horaria) AS ch, mt.nome
                         FROM rm_cursos_turma_series s INNER JOIN uni_rh_cursos_disciplinas_grade l ON l.modulo_id = s.id INNER JOIN rm_materias mt ON mt.id = l.materia_id
                        WHERE s.grade_id IN ({$grades}) ORDER BY s.ordem, s.id, l.ordem, mt.nome") as $l) {
            $linhas[(int) $l['grade_id']][] = $l;
        }
        /* as tentativas (enturmações ativas em ofertas do curso), com a linha a que pertencem — a mesma regra de tentativas() */
        $tent = [];
        $ofertas = [];
        foreach ($ler("SELECT mc.id AS mc, o.id, o.matriz_id, o.encerrada_em, l.grade_id,
                              (SELECT l2.id FROM uni_rh_cursos_disciplinas_grade l2 WHERE l2.grade_id = mc.grade_id AND l2.materia_id = o.codmat LIMIT 1) AS linhaPorMateria
                         FROM rm_matriculas_curso mc INNER JOIN col_usuarios u ON u.id = mc.usuario_id
                         INNER JOIN portal_disciplinas_alunos e ON e.rm = u.rm AND e.situacao = 1
                         INNER JOIN rm_professores_materias o ON o.id = e.rm_prof_mat_id AND o.curso_id = mc.curso_id
                         LEFT JOIN uni_rh_cursos_disciplinas_grade l ON l.id = o.matriz_id
                        WHERE mc.id IN ({$lista}) ORDER BY mc.id, o.ano, o.id") as $o) {
            $g = (int) $mcs[(int) $o['mc']]['grade_id'];
            if ($o['matriz_id'] !== null && (int) $o['grade_id'] === $g) {
                $linha = (int) $o['matriz_id'];
            } elseif ($o['matriz_id'] === null && $o['linhaPorMateria']) {
                $linha = (int) $o['linhaPorMateria'];
            } else {
                continue; // fora da grade: não conta para a conclusão
            }
            $tent[(int) $o['mc']][$linha][] = ['oferta' => (int) $o['id'], 'encerrada' => !empty($o['encerrada_em']), 'encerradaEm' => $o['encerrada_em']];
            $ofertas[(int) $o['id']] = true;
        }
        /* o resultado GRAVADO das ofertas encerradas (o que alunoNaOferta lê) */
        $res = [];
        if ($ofertas) {
            foreach ($ler("SELECT rm_prof_mat_id, rm, resultado FROM rm_disciplina_media_final WHERE rm_prof_mat_id IN (" . implode(',', array_keys($ofertas)) . ") ORDER BY id") as $r) {
                $res[(int) $r['rm_prof_mat_id']][(string) $r['rm']] ??= (int) $r['resultado'];
            }
        }
        /* o TCC (o primeiro da matrícula, como daMatricula) */
        $tccs = [];
        foreach ($ler("SELECT matricula_curso_id, situacao, nota FROM rm_tcc WHERE matricula_curso_id IN ({$lista}) ORDER BY id") as $t) {
            $tccs[(int) $t['matricula_curso_id']] ??= $t;
        }
        $semModulo = \App\adms\Models\helper\AdmsModuloInterno::ativo();
        $saida = [];
        foreach ($mcs as $id => $m) {
            $p = [];
            $chE = 0;
            $chI = 0;
            foreach ($linhas[(int) $m['grade_id']] ?? [] as $l) {
                $ts = $tent[$id][(int) $l['id']] ?? [];
                $vig = self::vigente($ts);
                $vt = $vig !== null ? $ts[$vig] : null;
                $sit = !$vt ? 'Não cursada' : (!$vt['encerrada'] ? 'Em curso' : (($res[$vt['oferta']][(string) $m['rm']] ?? null) === Calc::APROVADO ? 'Aprovado' : 'Reprovado'));
                $chE += (int) $l['ch'];
                if ($sit === 'Aprovado') {
                    $chI += (int) $l['ch'];
                } else {
                    $p[] = $semModulo ? "{$l['nome']}: {$sit}" : "{$l['nome']} ({$l['modulo']}): {$sit}";
                }
            }
            if (empty($linhas[(int) $m['grade_id']])) {
                $p[] = 'A grade não tem disciplinas.';
            }
            $t = $tccs[$id] ?? null;
            $exige = (int) $m['exige_tcc'] === 1;
            $nomeTcc = $t ? (\App\adms\Models\tcc\AdmsTcc::SITUACOES[(int) $t['situacao']] ?? '?') : null;
            if ($exige && (!$t || (int) $t['situacao'] !== 2)) {
                $p[] = 'TCC: ' . (!$t ? 'não cadastrado' : $nomeTcc) . ' (o curso exige TCC aprovado)';
            }
            $c = $t ? Calc::centesimos($t['nota']) : null;
            $saida[$id] = ['pendencias' => $p, 'chExigida' => $chE, 'chIntegralizada' => $chI, 'exigeTcc' => $exige, 'tcc' => $nomeTcc,
                'notaTcc' => $c === null ? null : Calc::formatar(number_format($c / 100, 2, '.', ''), $c % 10 ? 2 : 1)];
        }
        return $saida;
    }

    /**
     * E1b (D-357) e E2 (D-363): os TEXTOS que o documento usa, gravados no snapshot da emissão — concordância pelo sexo
     * do cadastro (1 Feminino: ALUNA, matriculada, a; 2 Masculino: ALUNO, matriculado, o; outro: as formas neutras dos
     * parâmetros) e as siglas/rótulos da frequência.
     */
    /**
     * E5 (D-314): a média final do aluno, arredondada (meio para cima) com `$casas` e formatada ("8,67"); '' sem
     * disciplina encerrada ou num snapshot anterior à 1.12.0. Aritmética: Σ médias ÷ n. Ponderada: Σ (média × CH) ÷ Σ CH.
     */
    public static function mediaFinalTexto(array $vida, int $casas): string
    {
        $mf = $vida['mediaFinalAluno'] ?? null;
        if (!$mf || (int) $mf['n'] === 0) {
            return '';
        }
        $v = ($mf['modo'] ?? 'aritmetica') === 'ponderada' && (int) $mf['somaCh'] > 0
            ? Calc::arredondar((int) $mf['somaPonderada'], (int) $mf['somaCh'] * 100, $casas)
            : Calc::arredondar((int) $mf['soma'], (int) $mf['n'] * 100, $casas);
        return Calc::formatar($v, $casas);
    }

    /** E5 (D-315): a nota do TCC com as casas do documento ("9,50"); '' sem TCC ou sem nota lançada. */
    public static function notaTccTexto(array $vida, int $casas): string
    {
        $c = Calc::centesimos($vida['tcc']['nota'] ?? null);
        return $c === null ? '' : Calc::formatar(number_format($c / 100, 2, '.', ''), $casas);
    }

    public static function textos(int $genero): array
    {
        $n = \App\adms\Models\helper\AdmsParametro::formasNeutras();
        $c = $genero === 1 ? ['rotulo' => 'ALUNA', 'matriculado' => 'matriculada', 'artigo' => 'a']
            : ($genero === 2 ? ['rotulo' => 'ALUNO', 'matriculado' => 'matriculado', 'artigo' => 'o'] : $n);
        return ['alunoRotulo' => $c['rotulo'], 'alunoMatriculado' => $c['matriculado'], 'alunoArtigo' => $c['artigo']]
            + \App\adms\Models\helper\AdmsParametro::textosFrequencia();
    }

    /** E8c (D-267): os dados pessoais que todo histórico exige; devolve os rótulos dos que faltam. */
    public static function dadosPessoaisFaltando(array $aluno): array
    {
        $exigidos = [
            'data de nascimento' => $aluno['nascimento'] ?? null, 'naturalidade' => $aluno['naturalidade'] ?? null,
            'nacionalidade' => $aluno['nacionalidade'] ?? null, 'RG' => $aluno['rg'] ?? null, 'CPF' => $aluno['cpf'] ?? null,
            'filiação (mãe ou pai)' => trim((string) ($aluno['mae'] ?? '')) . trim((string) ($aluno['pai'] ?? '')),
        ];
        return array_keys(array_filter($exigidos, fn($v) => trim((string) $v) === ''));
    }

    private static function aluno(int $usuarioId): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT u.id, u.rm, u.nome, u.nome_social, u.genero_id, i.nasc_data, i.nasc_local, i.nasc_uf, i.nasc_nac, i.rg, i.rg_org, i.rg_uf, i.cpf, i.nome_mae, i.nome_pai
               FROM col_usuarios u LEFT JOIN col_usuarios_informacoes i ON i.col_user_id = u.id WHERE u.id = :id LIMIT :limit",
            "id={$usuarioId}&limit=1"
        );
        $a = $ler->getResultado()[0] ?? [];
        return [
            'id' => (int) ($a['id'] ?? 0), 'rm' => $a['rm'] ?? '', 'nome' => $a['nome'] ?? '', 'nomeSocial' => $a['nome_social'] ?? null,
            'genero' => (int) ($a['genero_id'] ?? 0), // E1b (D-357): 1 Feminino, 2 Masculino, outro = forma neutra
            'nascimento' => $a['nasc_data'] ?? null,
            /* E8c: sem a cidade não há naturalidade (antes, só a UF virava "/DF" e passava como preenchida) */
            'naturalidade' => trim((string) ($a['nasc_local'] ?? '')) === '' ? null : trim($a['nasc_local'] . (($a['nasc_uf'] ?? '') !== '' ? '/' . $a['nasc_uf'] : '')),
            'nacionalidade' => $a['nasc_nac'] ?? null,
            'rg' => $a['rg'] ?? null, 'rgOrgao' => trim(($a['rg_org'] ?? '') . (($a['rg_uf'] ?? '') !== '' ? '/' . $a['rg_uf'] : '')) ?: null,
            'cpf' => $a['cpf'] ?? null, 'mae' => $a['nome_mae'] ?? null, 'pai' => $a['nome_pai'] ?? null,
        ];
    }

    private static function nomePolo(int $id): ?string
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT nome FROM rm_polos WHERE id = :id LIMIT :limit", "id={$id}&limit=1");
        return $ler->getResultado()[0]['nome'] ?? null;
    }

    /**
     * Tentativas do aluno no curso, por linha da matriz da grade (chave 0 = enturmado em
     * oferta do curso que não é desta grade).
     */
    private static function tentativas(string $rm, int $cursoId, int $gradeId): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT o.id, o.matriz_id, o.codmat, o.codtur, o.ano, o.materia, o.ch_considerada, o.data_inicio, o.data_fim, o.encerrada_em,
                    l.grade_id, (SELECT l2.id FROM uni_rh_cursos_disciplinas_grade l2 WHERE l2.grade_id = :g1 AND l2.materia_id = o.codmat LIMIT 1) AS linhaPorMateria
               FROM portal_disciplinas_alunos e
               INNER JOIN rm_professores_materias o ON o.id = e.rm_prof_mat_id
               LEFT JOIN uni_rh_cursos_disciplinas_grade l ON l.id = o.matriz_id
              WHERE e.rm = :rm AND e.situacao = :s AND o.curso_id = :c
              ORDER BY o.ano, o.id",
            "g1={$gradeId}&rm={$rm}&s=1&c={$cursoId}"
        );
        $por = [];
        foreach ($ler->getResultado() ?: [] as $o) {
            if ($o['matriz_id'] !== null && (int) $o['grade_id'] === $gradeId) {
                $linha = (int) $o['matriz_id'];
            } elseif ($o['matriz_id'] === null && $o['linhaPorMateria']) {
                $linha = (int) $o['linhaPorMateria']; // legado sem linha: mesma matéria da grade
            } else {
                $linha = 0;
            }
            $por[$linha][] = self::tentativa($o, $rm);
        }
        return $por;
    }

    private static function tentativa(array $o, string $rm): array
    {
        $d = Calc::alunoNaOferta((int) $o['id'], $rm) ?? [];
        $ofertaId = (int) $o['id'];
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT u.nome, ti.nome AS titulacao FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id = l.usuario_id
               LEFT JOIN uni_rh_titulacao ti ON ti.id = u.titulacao_id
              WHERE l.rm_prof_mat_id = :o AND l.acesso = :a AND l.situacao = :s ORDER BY u.nome",
            "o={$ofertaId}&a=1&s=1"
        );
        $docentes = $ler->getResultado() ?: [];
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT a.nomeAtividade, t.nome AS tipo, a.data_inicial, a.data_final, n.nota
               FROM rm_disciplina_atividade a LEFT JOIN rm_disciplina_atividade_tipo t ON t.id = a.tipo
               LEFT JOIN rm_disciplina_atividade_notas n ON n.atividade_id = a.id AND n.rm = :rm
              WHERE a.rm_prof_mat_id = :o AND a.situacao = :s ORDER BY a.data_inicial, a.id",
            "rm={$rm}&o={$ofertaId}&s=1"
        );
        $atividades = [];
        $dIni = null;
        $dFim = null;
        foreach ($ler->getResultado() ?: [] as $a) {
            $c = Calc::centesimos($a['nota']);
            $atividades[] = ['nome' => $a['nomeAtividade'], 'tipo' => $a['tipo'], 'data' => $a['data_inicial'] ? substr($a['data_inicial'], 0, 10) : null,
                'nota' => $a['nota'], 'notaFormatada' => $c === null ? null : Calc::formatar(number_format($c / 100, 2, '.', ''), $c % 10 ? 2 : 1)];
            $i = $a['data_inicial'] ? substr($a['data_inicial'], 0, 10) : null;
            $f = $a['data_final'] ? substr($a['data_final'], 0, 10) : $i;
            if ($i && (!$dIni || $i < $dIni)) {
                $dIni = $i;
            }
            if ($f && (!$dFim || $f > $dFim)) {
                $dFim = $f;
            }
        }
        $origem = ($o['data_inicio'] && $o['data_fim']) ? 'oferta' : ($dIni ? 'atividades' : null);
        return [
            'oferta_id' => $ofertaId, 'materia' => $o['materia'], 'turma' => $o['codtur'], 'ano' => (int) $o['ano'], 'chOferta' => (int) $o['ch_considerada'],
            'periodoInicio' => $origem === 'oferta' ? $o['data_inicio'] : $dIni, 'periodoFim' => $origem === 'oferta' ? $o['data_fim'] : $dFim, 'periodoOrigem' => $origem,
            'docentes' => $docentes, 'encerrada' => !empty($o['encerrada_em']), 'encerradaEm' => $o['encerrada_em'],
            'media' => $d['media'] ?? null, 'parcial' => (bool) ($d['parcial'] ?? true), 'casas' => (int) ($d['casas'] ?? Calc::regras()['casas']),
            'frequencia' => $d['frequencia'] ?? null, 'faltas' => $d['faltas'] ?? null, 'encontros' => $d['encontros'] ?? null,
            /* E2 (D-361): o mínimo do FECHAMENTO e FS/FI, decididos só no motor */
            'gravado' => !empty($d['gravado']), 'frequenciaMinima' => $d['frequenciaMinima'] ?? null, 'suficiente' => Calc::frequenciaSuficiente($d),
            'resultado' => $d['resultado'] ?? null, 'motivo' => $d['motivo'] ?? null, 'rotulo' => Calc::rotuloResultado($d['resultado'] ?? null, $d['motivo'] ?? null),
            'atividades' => $atividades, 'vigente' => false,
        ];
    }

    /** Índice da tentativa vigente: a última encerrada; sem encerrada, a mais recente. */
    private static function vigente(array $tentativas): ?int
    {
        if (!$tentativas) {
            return null;
        }
        $melhor = null;
        foreach ($tentativas as $i => $t) {
            if ($t['encerrada'] && ($melhor === null || !$tentativas[$melhor]['encerrada'] || $t['encerradaEm'] > $tentativas[$melhor]['encerradaEm'])) {
                $melhor = $i;
            }
        }
        return $melhor ?? array_key_last($tentativas);
    }
}
