<?php

namespace App\adms\Models\historico;

if (!defined('VCOSTAEDU')) { // E6 (D-376): o portal também carrega (atestado do aluno)
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * VARIÁVEIS dos modelos de histórico (E6b), na linha do projeto de referência (AdmsVariaveisContrato):
 * `{{NOME}}` no texto das seções, trocado pelo valor já ESCAPADO para HTML.
 *
 * - catálogo agrupado, que o editor mostra e insere por clique;
 * - ao SALVAR o modelo, variável fora do catálogo é recusada (`desconhecidas`);
 * - ao EMITIR, variável usada e VAZIA vira alerta (`naoSubstituidas`, como no projeto de referência).
 *
 * Os valores vêm SÓ do objeto da fonte única (AdmsVidaAcademica::montar) e dos dados da
 * emissão — nenhum cálculo aqui além de formatar data e número.
 */
class AdmsHistoricoVariaveis
{
    public const MESES = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

    public static function catalogo(): array
    {
        return [
            'Aluno' => [
                'ALUNO_NOME' => 'Nome civil', 'ALUNO_NOME_SOCIAL' => 'Nome social', 'ALUNO_RA' => 'RA',
                'ALUNO_CPF' => 'CPF (formatado)', 'ALUNO_RG' => 'RG', 'ALUNO_RG_ORGAO' => 'Órgão emissor/UF do RG',
                'ALUNO_NASCIMENTO' => 'Data de nascimento', 'ALUNO_NATURALIDADE' => 'Naturalidade (cidade/UF)',
                'ALUNO_NACIONALIDADE' => 'Nacionalidade', 'ALUNO_MAE' => 'Nome da mãe', 'ALUNO_PAI' => 'Nome do pai',
                /* E1b (D-357, D-358): concordância pelo sexo do cadastro e o CPF mascarado */
                'ALUNO_CPF_MASCARADO' => 'CPF mascarado (123.xxx.xxx-45)', 'ALUNO_ROTULO' => 'ALUNA / ALUNO (concordância)',
                'ALUNO_MATRICULADO' => 'matriculada / matriculado (concordância)', 'ALUNO_O_A' => 'a / o (concordância)',
            ],
            'Matrícula e curso' => [
                'CURSO_NOME' => 'Curso', 'CURSO_GRADE' => 'Número da grade (matriz)', 'POLO_NOME' => 'Polo da matrícula',
                'MODALIDADE' => 'Modalidade da matrícula', 'MATRICULA_INGRESSO_FORMA' => 'Forma de ingresso',
                'MATRICULA_INGRESSO_DATA' => 'Data de ingresso', 'MATRICULA_SITUACAO' => 'Situação da matrícula',
                'MATRICULA_CONCLUSAO' => 'Data de conclusão',
                /* E1b (D-354, D-355, D-359) */
                'MATRICULA_SITUACAO_DOCUMENTO' => 'Situação no documento (ex.: EM CURSO)', 'MATRICULA_INGRESSO_MES_ANO' => 'Ingresso: mês de ano (novembro de 2025)',
                'MATRICULA_PREVISAO_MES_ANO' => 'Previsão de conclusão: mês de ano',
                /* Correção da escola, Parte 3 (A3; D-102): o atestado da escola usa a série e o ano letivo, não a previsão */
                'ESCOLA_SERIE' => 'Série no ano letivo (escola)', 'ESCOLA_ANO_LETIVO' => 'Ano letivo da matrícula (escola)',
                /* E1a (D-340): o curso nos documentos (cadastro do curso) */
                'CURSO_NIVEL' => 'Nível do curso', 'CURSO_NOME_EXTENSO' => 'Curso por extenso (corpo do texto)',
                'CURSO_CABECALHO' => 'Linha do curso no cabeçalho', 'CURSO_SIGLA' => 'Sigla do curso',
                'CURSO_NIVEL_TEXTO' => 'Nível do curso em minúsculas (corpo do texto: "especialização")', // 1.13.0 (D-388)
            ],
            'Instituição' => [
                'INSTITUICAO_NOME' => 'Nome', 'INSTITUICAO_MANTENEDORA' => 'Mantenedora', 'INSTITUICAO_CNPJ' => 'CNPJ',
                'INSTITUICAO_EMEC' => 'Código e-MEC', 'INSTITUICAO_ENDERECO' => 'Endereço completo',
                /* E1a (D-339): Dados da instituição */
                'INSTITUICAO_CIDADE' => 'Cidade', 'INSTITUICAO_SITE' => 'Site', 'INSTITUICAO_TELEFONE' => 'Telefone',
                'INSTITUICAO_EMAIL' => 'E-mail institucional',
                /* 1.13.0 (D-389): as partes do endereço (rodapé no formato do Word) */
                'INSTITUICAO_LOGRADOURO' => 'Endereço: logradouro', 'INSTITUICAO_NUMERO' => 'Endereço: número',
                'INSTITUICAO_COMPLEMENTO' => 'Endereço: complemento', 'INSTITUICAO_BAIRRO' => 'Endereço: bairro',
                'INSTITUICAO_CEP' => 'Endereço: CEP', 'INSTITUICAO_UF' => 'Endereço: UF',
                'INSTITUICAO_CABECALHO_1' => 'Cabeçalho: linha 1', 'INSTITUICAO_CABECALHO_2' => 'Cabeçalho: linha 2',
                'INSTITUICAO_CABECALHO_3' => 'Cabeçalho: linha 3', 'INSTITUICAO_CABECALHO_4' => 'Cabeçalho: linha 4',
                'INSTITUICAO_CABECALHO_5' => 'Cabeçalho: linha 5',
            ],
            'Ato legal' => [
                'ATO_DESCRICAO' => 'Ato completo (tipo, número, data, órgão, publicação)', 'ATO_ORGAO' => 'Órgão',
                'ATO_TIPO' => 'Tipo', 'ATO_NUMERO' => 'Número', 'ATO_DATA' => 'Data do ato',
            ],
            'Totais e datas' => [ // E8d (A24): era "Totais e período"
                'TOTAL_CH_EXIGIDA' => 'CH total do curso (soma da grade), em horas — a do atestado', 'TOTAL_CH_INTEGRALIZADA' => 'CH integralizada (aprovadas), em horas',
                'TOTAL_DISCIPLINAS' => 'Número de disciplinas da grade', 'TOTAL_APROVADAS' => 'Disciplinas aprovadas',
                'PERIODO_INICIO' => 'Início do período de realização', 'PERIODO_FIM' => 'Fim do período de realização',
                'DURACAO_MESES' => 'Duração total, em meses',
            ],
            'TCC' => [
                'TCC_TITULO' => 'Título', 'TCC_ORIENTADOR' => 'Orientador', 'TCC_ORIENTADOR_TITULACAO' => 'Titulação do orientador',
                'TCC_NOTA' => 'Nota', 'TCC_DATA' => 'Data da defesa/entrega', 'TCC_SITUACAO' => 'Situação',
                'MEDIA_FINAL_ALUNO' => 'Média final do aluno (só no FINAL)', // E5 (D-314)
            ],
            /* E2 (D-363): a legenda do quadro ("FS: Frequência Suficiente – … – Nota mínima: 7,0/10,0") */
            'Frequência e nota (legenda)' => [
                'FS_SIGLA' => 'Sigla: frequência suficiente (FS)', 'FS_ROTULO' => 'Rótulo: Frequência Suficiente',
                'FI_SIGLA' => 'Sigla: frequência insuficiente (FI)', 'FI_ROTULO' => 'Rótulo: Frequência Insuficiente',
                'FREQUENCIA_MINIMA' => 'Frequência mínima do curso (75%)', 'NOTA_MINIMA' => 'Nota mínima (7,0)',
            ],
            'Emissão' => [
                'EMISSAO_TIPO' => 'PARCIAL, FINAL ou ATESTADO', 'EMISSAO_CODIGO' => 'Código de verificação',
                'EMISSAO_URL_VERIFICACAO' => 'Endereço da verificação pública', 'EMISSAO_DATA' => 'Data da emissão',
                'EMISSAO_DATA_EXTENSO' => 'Data da emissão por extenso', 'EMISSAO_EMISSOR' => 'Quem emitiu',
                /* E6 (D-375): data e hora, e a origem (para os trechos condicionais do atestado) */
                'EMISSAO_DATA_HORA' => 'Data e hora da emissão', 'EMISSAO_PELO_PORTAL' => 'Emitido pelo aluno no portal ("sim" ou vazio)',
                'EMISSAO_PELA_SECRETARIA' => 'Emitido pela secretaria ("sim" ou vazio)',
            ],
        ];
    }

    /** Nomes aceitos no modelo: as variáveis do catálogo, os blocos do sistema e os marcadores do TCC (E8d, A24). */
    public static function nomes(): array
    {
        return array_merge(...array_map('array_keys', array_values(self::catalogo())), ...[array_keys(AdmsHistoricoEditor::BLOCOS), array_keys(AdmsHistoricoEditor::condicionais())]); // E5 (D-366): SE_/FIM_SE_ de qualquer variável
    }

    /** Variáveis usadas no texto (sem repetição). */
    public static function usadas(string $texto): array
    {
        preg_match_all('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', $texto, $m);
        return array_values(array_unique($m[1]));
    }

    /** Usadas que não existem no catálogo — recusa ao salvar. */
    public static function desconhecidas(string $texto): array
    {
        return array_values(array_diff(self::usadas($texto), self::nomes()));
    }

    public static function data(?string $d): string
    {
        return $d ? date('d/m/Y', strtotime($d)) : '';
    }

    public static function extenso(?string $d): string
    {
        if (!$d) {
            return '';
        }
        $t = strtotime($d);
        return (int) date('j', $t) . ' de ' . self::MESES[(int) date('n', $t)] . ' de ' . date('Y', $t);
    }

    /** E1b (D-359): "novembro de 2025" (mês por extenso, minúsculo); data vazia ou inválida → ''. */
    public static function mesAno(?string $d): string
    {
        $t = $d ? strtotime($d) : false;
        return $t ? self::MESES[(int) date('n', $t)] . ' de ' . date('Y', $t) : '';
    }

    /**
     * E1b (D-324, D-358): o CPF MASCARADO, 3 + 2 dígitos visíveis (123.xxx.xxx-45). A MESMA função serve o documento e a
     * verificação pública (AdmsHistoricoEmissao::cpfMascarado). CPF que não tem 11 dígitos → ''.
     */
    public static function cpfMascarado(?string $cpf): string
    {
        $c = preg_replace('/\D/', '', (string) $cpf);
        return strlen($c) === 11 ? substr($c, 0, 3) . '.xxx.xxx-' . substr($c, 9, 2) : '';
    }

    public static function cpf(?string $cpf): string
    {
        $c = preg_replace('/\D/', '', (string) $cpf);
        return strlen($c) === 11 ? substr($c, 0, 3) . '.' . substr($c, 3, 3) . '.' . substr($c, 6, 3) . '-' . substr($c, 9) : (string) $cpf;
    }

    /**
     * Valores em TEXTO puro (sem escapar), a partir do snapshot: ['vida' => montar(), 'emissao' => [...]].
     */
    public static function valores(array $snap, array $opcoesDoc = []): array
    {
        /* E5 (D-368): com as casas do DOCUMENTO (opção do modelo), TCC e média final saem com elas; sem, como sempre */
        $casasDoc = isset($opcoesDoc['casasNota']) ? (int) $opcoesDoc['casasNota'] : null;
        $v = $snap['vida'];
        $e = $snap['emissao'];
        $a = $v['aluno'];
        $m = $v['matricula'];
        $i = $v['instituicao'];
        $ato = $v['atoLegal']['ato'] ?? null;
        $t = $v['totais'];
        $c = $v['tcc'] ?? null;
        $x = $v['textos'] ?? []; // E1b/E2: concordância e textos da frequência (snapshot)
        $s = fn($x) => $x === null ? '' : trim((string) $x);
        return [
            'ALUNO_NOME' => $s($a['nome']), 'ALUNO_NOME_SOCIAL' => $s($a['nomeSocial']), 'ALUNO_RA' => $s($a['rm']),
            'ALUNO_CPF' => self::cpf($a['cpf'] ?? null), 'ALUNO_RG' => $s($a['rg']), 'ALUNO_RG_ORGAO' => $s($a['rgOrgao']),
            'ALUNO_NASCIMENTO' => self::data($a['nascimento'] ?? null), 'ALUNO_NATURALIDADE' => $s($a['naturalidade']),
            'ALUNO_NACIONALIDADE' => $s($a['nacionalidade']), 'ALUNO_MAE' => $s($a['mae']), 'ALUNO_PAI' => $s($a['pai']),
            'CURSO_NOME' => $s($m['curso']), 'CURSO_GRADE' => $s($m['grade']), 'POLO_NOME' => $s($m['polo']),
            'MODALIDADE' => $s($m['modalidade']), 'MATRICULA_INGRESSO_FORMA' => $s($m['formaIngresso']),
            'MATRICULA_INGRESSO_DATA' => self::data($m['dataIngresso'] ?? null), 'MATRICULA_SITUACAO' => $s($m['situacao']),
            'MATRICULA_CONCLUSAO' => self::data($m['dataConclusao'] ?? null),
            'INSTITUICAO_NOME' => $s($i['nome']), 'INSTITUICAO_MANTENEDORA' => $s($i['mantenedora']),
            'INSTITUICAO_CNPJ' => $i['cnpj'] ? \App\adms\Models\helper\AdmsInstituicao::mascaraCnpj((string) $i['cnpj']) : '',
            'INSTITUICAO_EMEC' => $s($i['codigoEmec']), 'INSTITUICAO_ENDERECO' => $s($i['endereco']),
            'ATO_DESCRICAO' => $s($v['atoLegal']['descricao'] ?? null), 'ATO_ORGAO' => $s($ato['orgao'] ?? null),
            'ATO_TIPO' => $s($ato['tipo'] ?? null), 'ATO_NUMERO' => $s($ato['numero'] ?? null), 'ATO_DATA' => self::data($ato['data_ato'] ?? null),
            'TOTAL_CH_EXIGIDA' => (string) (int) $t['chExigida'], 'TOTAL_CH_INTEGRALIZADA' => (string) (int) $t['chIntegralizada'],
            'TOTAL_DISCIPLINAS' => (string) (int) $t['disciplinas'], 'TOTAL_APROVADAS' => (string) (int) $t['aprovadas'],
            'PERIODO_INICIO' => self::data($t['periodoInicio'] ?? null), 'PERIODO_FIM' => self::data($t['periodoFim'] ?? null),
            'DURACAO_MESES' => $t['duracaoMeses'] === null ? '' : (string) (int) $t['duracaoMeses'],
            'TCC_TITULO' => $s($c['titulo'] ?? null), 'TCC_ORIENTADOR' => $s($c['orientador'] ?? null),
            'TCC_ORIENTADOR_TITULACAO' => $s($c['orientadorTitulacao'] ?? null), 'TCC_NOTA' => $casasDoc !== null ? AdmsVidaAcademica::notaTccTexto($v, $casasDoc) : $s($c['notaFormatada'] ?? null),
            'TCC_DATA' => self::data($c['data_defesa'] ?? null), 'TCC_SITUACAO' => $s($c['situacaoNome'] ?? null),
            'EMISSAO_TIPO' => $s($e['tipo']), 'EMISSAO_CODIGO' => $s($e['codigo']), 'EMISSAO_URL_VERIFICACAO' => $s($e['url']),
            'EMISSAO_DATA' => self::data($e['data']), 'EMISSAO_DATA_EXTENSO' => self::extenso($e['data']), 'EMISSAO_EMISSOR' => $s($e['emissor']),
            /* E5/E6: com `??`, snapshot anterior (sem origem) conta como da secretaria */
            'MEDIA_FINAL_ALUNO' => ($e['tipo'] ?? '') === 'FINAL' ? AdmsVidaAcademica::mediaFinalTexto($v, $casasDoc ?? (int) ($v['regras']['casas'] ?? 1)) : '',
            'EMISSAO_DATA_HORA' => $e['data'] ? date('d/m/Y \\à\\s H:i', strtotime($e['data'])) : '',
            'EMISSAO_PELO_PORTAL' => ($e['origem'] ?? 'secretaria') === 'portal' ? 'sim' : '',
            'EMISSAO_PELA_SECRETARIA' => ($e['origem'] ?? 'secretaria') === 'portal' ? '' : 'sim',
            /* E1a: com `??`, um snapshot anterior à 1.10.0 (sem estas chaves) só deixa as variáveis vazias */
            'CURSO_NIVEL' => $s($m['cursoNivel'] ?? null), 'CURSO_NOME_EXTENSO' => $s($m['cursoNomeExtenso'] ?? null),
            'CURSO_CABECALHO' => $s($m['cursoCabecalho'] ?? null), 'CURSO_SIGLA' => $s($m['cursoSigla'] ?? null),
            'INSTITUICAO_CIDADE' => $s($i['cidade'] ?? null), 'INSTITUICAO_SITE' => $s($i['site'] ?? null),
            'INSTITUICAO_TELEFONE' => $s($i['telefone'] ?? null), 'INSTITUICAO_EMAIL' => $s($i['email'] ?? null),
            /* 1.13.0 (D-388, D-389): com `??`, um snapshot anterior só deixa estas variáveis vazias */
            'CURSO_NIVEL_TEXTO' => mb_strtolower($s($m['cursoNivel'] ?? null), 'UTF-8'),
            'INSTITUICAO_LOGRADOURO' => $s($i['logradouro'] ?? null), 'INSTITUICAO_NUMERO' => $s($i['numero'] ?? null),
            'INSTITUICAO_COMPLEMENTO' => $s($i['complemento'] ?? null), 'INSTITUICAO_BAIRRO' => $s($i['bairro'] ?? null),
            'INSTITUICAO_CEP' => $s($i['cep'] ?? null), 'INSTITUICAO_UF' => $s($i['uf'] ?? null),
            /* E1b/E2: com `??`, um snapshot anterior à 1.11.0 (sem estas chaves) só deixa as variáveis vazias */
            'ALUNO_CPF_MASCARADO' => self::cpfMascarado($a['cpf'] ?? null),
            'ALUNO_ROTULO' => $s($x['alunoRotulo'] ?? null), 'ALUNO_MATRICULADO' => $s($x['alunoMatriculado'] ?? null), 'ALUNO_O_A' => $s($x['alunoArtigo'] ?? null),
            'MATRICULA_SITUACAO_DOCUMENTO' => $s($m['situacaoDocumento'] ?? null),
            'MATRICULA_INGRESSO_MES_ANO' => self::mesAno($m['dataIngresso'] ?? null), 'MATRICULA_PREVISAO_MES_ANO' => self::mesAno($m['previsaoConclusao'] ?? null),
            'ESCOLA_SERIE' => $s($m['escolaSerie'] ?? null), 'ESCOLA_ANO_LETIVO' => $s(isset($m['escolaAno']) ? (string) $m['escolaAno'] : null), // D-102
            'FS_SIGLA' => $s($x['siglaSuficiente'] ?? null), 'FS_ROTULO' => $s($x['rotuloSuficiente'] ?? null),
            'FI_SIGLA' => $s($x['siglaInsuficiente'] ?? null), 'FI_ROTULO' => $s($x['rotuloInsuficiente'] ?? null),
            'FREQUENCIA_MINIMA' => isset($v['regras']['frequenciaMinima']) ? \App\adms\Models\notas\AdmsCalculoNotas::numero((string) $v['regras']['frequenciaMinima']) . '%' : '',
            'NOTA_MINIMA' => isset($v['regras']['mediaMinima']) ? \App\adms\Models\notas\AdmsCalculoNotas::numero((string) $v['regras']['mediaMinima'], 1) : '',
        ] + self::linhasCabecalho($i['cabecalho'] ?? []);
    }

    /** E1a: INSTITUICAO_CABECALHO_1 … _5 = as linhas preenchidas, na ordem (a 1ª preenchida é a linha 1). */
    private static function linhasCabecalho(array $linhas): array
    {
        $v = [];
        for ($n = 1; $n <= 5; $n++) {
            $v['INSTITUICAO_CABECALHO_' . $n] = trim((string) ($linhas[$n - 1] ?? ''));
        }
        return $v;
    }

    /**
     * Troca as variáveis (valor escapado). `$vazias` recebe as usadas sem valor — o
     * naoSubstituidas do projeto de referência: a emissão as trata como alerta.
     */
    public static function substituir(string $html, array $valores, ?array &$vazias = null): string
    {
        $vazias = [];
        return preg_replace_callback('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', function ($m) use ($valores, &$vazias) {
            $v = $valores[$m[1]] ?? null;
            if ($v === null || $v === '') {
                $vazias[] = $m[1];
                return '';
            }
            return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
        }, $html);
    }

    /** D-102: o rótulo de uma variável, para a mensagem (nenhuma mensagem mostra o nome interno {{...}}). */
    public static function rotulo(string $nome): string
    {
        foreach (self::catalogo() as $grupo) {
            if (isset($grupo[$nome])) {
                return (string) $grupo[$nome];
            }
        }
        return mb_strtolower(str_replace('_', ' ', $nome));
    }
}
