<?php

namespace App\adms\Models\historico;

if (!defined('VCOSTAEDU')) { // E6 (D-376): o portal também carrega (atestado do aluno)
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * MODELO NO FORMATO 2 (E8d, D-291/A24): UM documento por modelo, feito no editor visual (padrão do projeto de referência).
 *
 * {"formato":2, "documento":"<HTML controlado com {{VARIÁVEIS}} e {{BLOCOS}}>",
 *  "quadro":{"colunas":[…], "atividades":true, "emCurso":"Em curso"},
 *  "assinaturas":{"blocos":[{nome, cargo, imagem}]}, "opcoes":{"paginacao":true}}
 *
 * - Os BLOCOS são o que o sistema monta (quadro, assinaturas, logo, QR code, código de verificação); no editor
 *   aparecem como marcadores não editáveis e voltam a `{{BLOCO}}` ao salvar (no navegador E aqui, para o
 *   que vier do "Ver código").
 * - `{{SE_TCC}} … {{FIM_SE_TCC}}`: trecho que só sai quando o aluno tem TCC (o "só se houver" do formato 1).
 * - As opções do quadro e das assinaturas ficam fora do documento (abas do editor).
 * - O formato 1 (seções) continua sendo lido e renderizado: as versões antigas não mudam. `converter()` leva
 *   um modelo do formato 1 ao 2 com o MESMO resultado (mesmas classes do CSS do documento).
 */
class AdmsHistoricoEditor
{
    public const FORMATO = 2;

    /** Blocos do sistema: nome da variável → rótulo do marcador no editor. */
    public const BLOCOS = [
        'QUADRO_DISCIPLINAS' => 'Quadro de disciplinas',
        'ASSINATURAS' => 'Assinaturas',
        'LOGO_INSTITUICAO' => 'Logo da instituição',
        'INSTITUICAO_CABECALHO' => 'Cabeçalho institucional', // E1a (D-339): as linhas da instituição + a linha do curso
        'QR_CODE' => 'QR code da verificação',
        'CODIGO_VERIFICACAO' => 'Código de verificação',
    ];
    /** E2 (D-362): como a frequência sai no quadro de disciplinas (aba Quadro). */
    public const FREQUENCIA_EXIBICAO = ['percentual' => 'Percentual (93,33%)', 'sigla' => 'Sigla (FS/FI)', 'ambos' => 'Os dois (93,33% FS)'];
    public const FREQUENCIA_PADRAO = 'percentual';

    /** Marcadores de trecho condicional. */
    public const CONDICIONAIS = ['SE_TCC' => 'Início: só se houver TCC', 'FIM_SE_TCC' => 'Fim: só se houver TCC'];

    /* E5 (D-367, D-368): opções do modelo. Cada uma só é gravada quando difere do padrão — um modelo que não
       mudou continua com o mesmo JSON (sem versão nova) e o que não tem a chave sai como sempre saiu. */
    public const ESTILOS = ['padrao' => 'Padrão do sistema', 'oficial' => 'Documento oficial (medidas do modelo Word)'];
    public const FONTES = ['padrao' => 'DejaVu Sans (padrão do sistema)', 'liberation' => 'Liberation Sans (métrica da Arial)'];
    public const CASAS_NOTA = ['' => 'As casas de cálculo (padrão)', '1' => '1 casa (8,5)', '2' => '2 casas (8,50)'];
    public const TEXTO_CH = 'horas aula';
    /** E5 (D-370): rótulos das linhas finais do quadro (média final do aluno, TCC e situação). */
    public const LINHAS_FINAIS = ['media' => 'Média final do aluno', 'tcc' => 'TCC', 'situacao' => 'Situação'];

    /**
     * E5 (D-366): TRECHO CONDICIONAL GENÉRICO — `{{SE_VARIAVEL}} … {{FIM_SE_VARIAVEL}}` sai só quando a variável
     * está preenchida (o mesmo mecanismo do `SE_TCC`, que continua valendo). Todas as variáveis do catálogo podem
     * ser condição; os trechos podem ficar um dentro do outro. Variável vazia DENTRO de um trecho que saiu não conta
     * como vazia (não vira alerta nem bloqueio); fora de trecho condicional, tudo como antes.
     */
    public static function condicionais(): array
    {
        static $c = null;
        if ($c === null) {
            $c = self::CONDICIONAIS;
            foreach (AdmsHistoricoVariaveis::catalogo() as $vars) {
                foreach ($vars as $v => $desc) {
                    $c['SE_' . $v] = 'Início: só se houver «' . $desc . '»';
                    $c['FIM_SE_' . $v] = 'Fim: só se houver «' . $desc . '»';
                }
            }
        }
        return $c;
    }

    /** Colunas das atividades do PARCIAL e "Em curso": o padrão. */
    public const EM_CURSO = 'Em curso';

    public static function ehFormato2(?array $modelo): bool
    {
        return (int) ($modelo['formato'] ?? 1) === self::FORMATO;
    }

    /** Qualquer modelo (1 ou 2) no formato 2 — o que o editor mostra. */
    public static function paraEditor(array $modelo, string $tipo): array
    {
        return self::ehFormato2($modelo) ? $modelo : self::converter($modelo, $tipo);
    }

    /** O padrão neutro de cada tipo, no formato 2: o padrão das seções, convertido (um só texto padrão). */
    public static function padrao(string $tipo): array
    {
        if ($tipo === 'ATESTADO') {
            return self::padraoAtestado();
        }
        return self::converter(AdmsHistoricoSecoes::padrao($tipo), $tipo);
    }

    /** E6 (D-371): o atestado padrão, NEUTRO (só variáveis; sem quadro de disciplinas). O do cliente é montado pelo editor. */
    private static function padraoAtestado(): array
    {
        $doc = '<table class="cab"><tr><td class="cab-logo">{{LOGO_INSTITUICAO}}</td><td class="cab-texto">{{INSTITUICAO_CABECALHO}}</td></tr></table>'
            . '<h1 style="text-align: center">ATESTADO DE MATRÍCULA</h1>'
            . '<p style="text-align: justify">Atestamos que {{ALUNO_NOME}}, CPF {{ALUNO_CPF_MASCARADO}}, está {{ALUNO_MATRICULADO}} no curso {{CURSO_NOME}}, '
            . 'com carga horária total de {{TOTAL_CH_EXIGIDA}} horas, iniciado em {{MATRICULA_INGRESSO_MES_ANO}} e com previsão de término para {{MATRICULA_PREVISAO_MES_ANO}}.</p>'
            . '<p style="text-align: right">{{INSTITUICAO_CIDADE}}, {{EMISSAO_DATA_EXTENSO}}.</p><p>{{ASSINATURAS}}</p><p>{{CODIGO_VERIFICACAO}}</p>';
        return [
            'formato' => self::FORMATO, 'documento' => $doc,
            'quadro' => ['colunas' => [], 'atividades' => false, 'emCurso' => self::EM_CURSO],
            'assinaturas' => ['blocos' => []], 'opcoes' => ['paginacao' => true],
        ];
    }

    /**
     * Formato 1 → 2, com o mesmo resultado visual: cada seção ativa vira o mesmo invólucro que
     * AdmsHistoricoDocumento montava (div.secao.s-TIPO, table.cab, table.rodape), com os blocos no lugar.
     */
    public static function converter(array $modelo, string $tipo): array
    {
        if (self::ehFormato2($modelo)) {
            return $modelo;
        }
        $doc = '';
        $colunas = [];
        $blocos = [];
        $paginacao = false;
        foreach (AdmsHistoricoSecoes::ordenadas($modelo, $tipo) as $s) {
            if ($s['tipo'] === 'quadro') {
                $colunas = $s['colunas'] ?? [];
            } elseif ($s['tipo'] === 'assinaturas') {
                $blocos = $s['blocos'] ?? [];
            } elseif ($s['tipo'] === 'rodape') {
                $paginacao = !empty($s['ativa']) && !empty($s['paginacao']);
            }
            if (empty($s['ativa'])) {
                continue;
            }
            $html = AdmsHtmlControlado::limpar((string) ($s['html'] ?? ''));
            switch ($s['tipo']) {
                case 'cabecalho':
                    $doc .= '<table class="cab"><tr>' . (!empty($s['logo']) ? '<td class="cab-logo">{{LOGO_INSTITUICAO}}</td>' : '')
                        . '<td class="cab-texto">' . $html . '</td></tr></table>';
                    break;
                case 'quadro':
                    $doc .= '<div class="secao s-quadro">' . $html . '<p>{{QUADRO_DISCIPLINAS}}</p></div>';
                    break;
                case 'assinaturas':
                    $doc .= '<div class="secao s-assinaturas">' . $html . '<p>{{ASSINATURAS}}</p></div>';
                    break;
                case 'rodape':
                    $doc .= '<table class="rodape"><tr><td>' . $html . '</td>' . (!empty($s['qr']) ? '<td class="qr">{{QR_CODE}}</td>' : '') . '</tr></table>';
                    break;
                case 'tcc':
                    $div = '<div class="secao s-tcc">' . $html . '</div>';
                    $doc .= !empty($s['somenteSeHouver']) ? '<p>{{SE_TCC}}</p>' . $div . '<p>{{FIM_SE_TCC}}</p>' : $div;
                    break;
                default:
                    $doc .= '<div class="secao s-' . $s['tipo'] . '">' . $html . '</div>';
            }
        }
        return [
            'formato' => self::FORMATO,
            'documento' => $doc,
            'quadro' => ['colunas' => array_values($colunas), 'atividades' => true, 'emCurso' => self::EM_CURSO],
            'assinaturas' => ['blocos' => array_values($blocos)],
            'opcoes' => ['paginacao' => $paginacao],
        ];
    }

    /**
     * O que veio do "Ver código" ou de um navegador sem JavaScript: marcador de bloco (span data-bloco) → {{BLOCO}}.
     * Só o nome do bloco é lido; o resto do marcador é descartado.
     */
    public static function marcadoresParaVariaveis(string $html): string
    {
        return (string) preg_replace_callback(
            '#<span\b[^>]*\bdata-bloco\s*=\s*["\']?([A-Z_]+)["\']?[^>]*>.*?</span>#is',
            fn($m) => (isset(self::BLOCOS[$m[1]]) || isset(self::condicionais()[$m[1]])) ? '{{' . $m[1] . '}}' : '',
            $html
        );
    }

    /**
     * Lê o formulário do editor. Recusa (em `$erros`) item perigoso no HTML, variável desconhecida, marcador
     * de TCC sem par, coluna inválida, assinatura inválida. Formatação inofensiva que a lista branca não
     * aceita sai em silêncio (`$ajustes`, para o aviso).
     */
    public static function doFormulario(array $post, array $atual, array $imagens, string $tipo, ?array &$erros = null, ?array &$ajustes = null): array
    {
        $erros = [];
        $ajustes = [];
        $atual = $atual ? self::paraEditor($atual, $tipo) : [];
        $bruto = self::marcadoresParaVariaveis((string) ($post['documento'] ?? ''));
        $limpo = AdmsHtmlControlado::limpar($bruto, $removidos, $perigosos);
        if ($perigosos) {
            $erros[] = 'Documento: conteúdo não permitido — ' . implode(', ', $perigosos) . '. Nada foi salvo; tire esse conteúdo e salve de novo.';
        }
        $ajustes = array_values(array_diff($removidos, $perigosos));
        $desc = AdmsHistoricoVariaveis::desconhecidas($limpo);
        if ($desc) {
            $erros[] = 'Documento: variável desconhecida — ' . implode(', ', array_map(fn($v) => '{{' . $v . '}}', $desc)) . '. Use o painel de variáveis.';
        }
        if (!self::condicionaisOk($limpo)) {
            $erros[] = 'Documento: cada trecho condicional ("só se houver …") precisa de um início e de um fim da MESMA variável, nessa ordem.';
        }
        if (trim(strip_tags($limpo)) === '' && !preg_match('/\{\{/', $limpo)) {
            $erros[] = 'Documento: o modelo está vazio.';
        }
        $q = (array) ($post['quadro'] ?? []);
        $colunas = [];
        $atuaisCol = [];
        foreach ($atual['quadro']['colunas'] ?? [] as $c) {
            $atuaisCol[$c['campo']] = $c;
        }
        foreach (AdmsHistoricoSecoes::COLUNAS as $campo => $padrao) {
            $c = $q['colunas'][$campo] ?? null;
            if ($c === null) {
                /* E8c/E8d (P-10): a linha "Módulo" nem aparece na aba com "Não"; a escolha anterior fica guardada */
                if ($campo === 'modulo' && isset($atuaisCol['modulo'])) {
                    $colunas[] = $atuaisCol['modulo'];
                }
                continue;
            }
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
            if (!isset(AdmsHistoricoSecoes::ALINHAMENTOS[$alin])) {
                $alin = 'left';
            }
            $colunas[] = ['campo' => $campo, 'rotulo' => $rot, 'largura' => $larg, 'alinhamento' => $alin, 'ordem' => (int) ($c['ordem'] ?? 99)];
        }
        usort($colunas, fn($a, $b) => [$a['ordem'], array_search($a['campo'], array_keys(AdmsHistoricoSecoes::COLUNAS))] <=> [$b['ordem'], array_search($b['campo'], array_keys(AdmsHistoricoSecoes::COLUNAS))]);
        foreach ($colunas as $i => $c) {
            $colunas[$i]['ordem'] = $i + 1;
        }
        if (str_contains($limpo, '{{QUADRO_DISCIPLINAS}}') && !array_filter($colunas, fn($c) => $c['campo'] !== 'modulo')) {
            $erros[] = 'Quadro de disciplinas: escolha ao menos uma coluna (aba Quadro).';
        }
        $emCurso = trim(strip_tags((string) ($q['emCurso'] ?? self::EM_CURSO)));
        if ($emCurso === '' || mb_strlen($emCurso) > 30) {
            $erros[] = 'Quadro: o texto de "Em curso" deve ter de 1 a 30 caracteres.';
        }
        $quadro = ['colunas' => $colunas, 'atividades' => !empty($q['atividades']), 'emCurso' => $emCurso];
        /* E2 (D-362): como a frequência sai no quadro. "percentual" (o de sempre) NÃO é gravado: um modelo que não mudou
           continua com o mesmo JSON (sem versão nova) e o que não tem a chave sai em percentual. */
        $freq = (string) ($q['frequencia'] ?? self::FREQUENCIA_PADRAO);
        if (!isset(self::FREQUENCIA_EXIBICAO[$freq])) {
            $erros[] = 'Quadro: escolha como a frequência sai (sigla, percentual ou os dois).';
        } elseif ($freq !== self::FREQUENCIA_PADRAO) {
            $quadro['frequencia'] = $freq;
        }
        /* E5 (D-369): CH dentro do nome ("Nome (18 horas aula)", só o número em negrito) */
        if (!empty($q['chNoNome'])) {
            $txt = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) ($q['textoCh'] ?? self::TEXTO_CH))));
            if ($txt === '' || mb_strlen($txt) > 20) {
                $erros[] = 'Quadro: o texto depois da carga horária (ex.: "horas aula") deve ter de 1 a 20 caracteres.';
            }
            $quadro['chNoNome'] = true;
            $quadro['textoCh'] = $txt;
        }
        /* E5 (D-370): uma linha por disciplina (só a tentativa vigente), em andamento em branco, e as linhas finais */
        foreach (['soVigente', 'andamentoEmBranco', 'linhasFinais'] as $op) {
            if (!empty($q[$op])) {
                $quadro[$op] = true;
            }
        }
        $blocosAtuais = $atual['assinaturas']['blocos'] ?? [];
        $assin = [];
        foreach (array_values((array) ($post['assinaturas']['blocos'] ?? [])) as $i => $b) {
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
                $ant = (string) ($b['imagemAtual'] ?? '');
                $valida = array_filter($blocosAtuais, fn($x) => ($x['imagem'] ?? '') === $ant);
                $img = ($ant !== '' && $valida) ? $ant : null;
            }
            $assin[] = ['nome' => $nome, 'cargo' => $cargo, 'imagem' => $img];
        }
        if (count($assin) > AdmsHistoricoSecoes::MAX_ASSINATURAS) {
            $erros[] = 'Assinaturas: no máximo ' . AdmsHistoricoSecoes::MAX_ASSINATURAS . ' blocos.';
        }
        return [
            'formato' => self::FORMATO,
            'documento' => $limpo,
            'quadro' => $quadro,
            'assinaturas' => ['blocos' => $assin],
            'opcoes' => self::opcoesDoFormulario((array) ($post['opcoes'] ?? []), $erros),
        ];
    }

    /** E5 (D-367, D-368): paginação, estilo, fonte e casas decimais do documento; só o que difere do padrão é gravado. */
    private static function opcoesDoFormulario(array $o, array &$erros): array
    {
        $r = ['paginacao' => !empty($o['paginacao'])];
        $estilo = (string) ($o['estilo'] ?? 'padrao');
        $fonte = (string) ($o['fonte'] ?? 'padrao');
        $casas = (string) ($o['casasNota'] ?? '');
        if (!isset(self::ESTILOS[$estilo]) || !isset(self::FONTES[$fonte]) || !isset(self::CASAS_NOTA[$casas])) {
            $erros[] = 'Opções: escolha o estilo, a fonte e as casas decimais da lista.';
            return $r;
        }
        if ($estilo !== 'padrao') {
            $r['estilo'] = $estilo;
        }
        if ($fonte !== 'padrao') {
            $r['fonte'] = $fonte;
        }
        if ($casas !== '') {
            $r['casasNota'] = (int) $casas;
        }
        return $r;
    }
    /**
     * Início e fim em pares, da MESMA variável, na ordem. E5 (D-366): trechos podem ficar um dentro do outro (pilha);
     * o mesmo trecho aberto de novo antes de fechar continua recusado.
     */
    public static function condicionaisOk(string $html): bool
    {
        preg_match_all('/\{\{\s*(FIM_)?SE_([A-Z0-9_]+)\s*\}\}/', $html, $m, PREG_SET_ORDER);
        $pilha = [];
        foreach ($m as $x) {
            if ($x[1] === '') {
                if (in_array($x[2], $pilha, true)) {
                    return false;
                }
                $pilha[] = $x[2];
            } elseif (array_pop($pilha) !== $x[2]) {
                return false;
            }
        }
        return !$pilha;
    }

    /**
     * Tira ou mantém cada trecho condicional. O marcador sozinho num parágrafo sai com o parágrafo; no meio do texto,
     * só ele sai. `SE_TCC`: o aluno tem TCC. `SE_VARIAVEL` (E5, D-366): a variável está preenchida em `$valores` (os
     * mesmos valores que o documento usa). Trecho dentro de trecho: vale a pilha (sai só se todos os de fora saem).
     */
    public static function aplicarCondicionais(string $html, bool $temTcc, array $valores = []): string
    {
        $partes = preg_split('#(<p>\s*\{\{\s*(?:FIM_)?SE_[A-Z0-9_]+\s*\}\}\s*</p>|\{\{\s*(?:FIM_)?SE_[A-Z0-9_]+\s*\}\})#s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $saida = '';
        $pilha = [];
        foreach ($partes as $i => $p) {
            if ($i % 2 === 0) {
                if (!in_array(false, $pilha, true)) {
                    $saida .= $p;
                }
                continue;
            }
            preg_match('/\{\{\s*(FIM_)?SE_([A-Z0-9_]+)\s*\}\}/', $p, $m);
            if (($m[1] ?? '') !== '') {
                array_pop($pilha);
                continue;
            }
            $pilha[] = $m[2] === 'TCC' ? $temTcc : trim((string) ($valores[$m[2]] ?? '')) !== '';
        }
        return $saida;
    }
}
