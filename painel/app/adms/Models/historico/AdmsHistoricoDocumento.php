<?php

namespace App\adms\Models\historico;

use App\adms\Models\notas\AdmsCalculoNotas as Calc;

if (!defined('VCOSTAEDU')) { // E6 (D-376): o portal também carrega (atestado do aluno)
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * O DOCUMENTO do histórico (E6b): monta o HTML a partir das seções do modelo e do snapshot
 * (vida acadêmica + emissão) e o transforma em PDF.
 *
 * - UM montador para prévia, emissão e 2ª via (a lição do projeto de referência: prévia copiada vira
 *   outro documento na primeira correção).
 * - Os números vêm do snapshot, que vem de AdmsVidaAcademica::montar: nada é calculado aqui,
 *   só formatado — com as mesmas funções da prévia técnica da E6a (Calc::formatar).
 * - O HTML das seções passa de novo pelo AdmsHtmlControlado AO RENDERIZAR.
 * - Logo, assinaturas e QR code entram EMBUTIDOS (data URI): o HTML gravado na emissão é
 *   autossuficiente, e a 2ª via sai igual mesmo se o logo mudar depois.
 * - Dompdf com acesso remoto DESLIGADO; tarja da prévia / marca da via, código e paginação
 *   desenhados por page_script em toda página (position:fixed perde páginas no dompdf).
 */
class AdmsHistoricoDocumento
{
    public const TARJA_PREVIA = 'PRÉVIA — documento não emitido, sem validade.';

    private $erro = '';

    public function getErro(): string
    {
        return $this->erro;
    }

    /** E5 (D-367): a família da Liberation Sans no CSS do documento. */
    public const FONTE_LIBERATION = 'Liberation Sans';

    /** E5 (D-367): registra as 4 variantes da Liberation Sans (assets/fontes/liberation-sans, SIL OFL 1.1) no Dompdf. */
    private static function registrarLiberation(\Dompdf\Dompdf $d): void
    {
        $dir = self::raiz() . '/assets/fontes/liberation-sans/';
        $fm = $d->getFontMetrics();
        foreach ([['normal', 'normal', 'Regular'], ['bold', 'normal', 'Bold'], ['normal', 'italic', 'Italic'], ['bold', 'italic', 'BoldItalic']] as [$peso, $estilo, $arq]) {
            if (is_file($dir . 'LiberationSans-' . $arq . '.ttf')) {
                $fm->registerFont(['family' => self::FONTE_LIBERATION, 'weight' => $peso, 'style' => $estilo], 'file://' . str_replace('\\', '/', $dir) . 'LiberationSans-' . $arq . '.ttf');
            }
        }
    }

    /** E6c (D-202): a pasta dos arquivos privados (constante PASTA_STORAGE da Config), com barra no fim. */
    public static function storage(): string
    {
        $p = defined('PASTA_STORAGE') ? (string) PASTA_STORAGE : self::raiz() . '/storage/';
        return rtrim(str_replace('\\', '/', $p), '/') . '/';
    }

    public static function raiz(): string
    {
        return str_replace('\\', '/', dirname(__DIR__, 5));
    }

    /** Arquivo local → data URI (só PNG/JPEG existentes); null se não houver. */
    public static function dataUri(?string $arquivo): ?string
    {
        if (!$arquivo || !is_file($arquivo)) {
            return null;
        }
        $info = @getimagesize($arquivo);
        if (!$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            return null;
        }
        return 'data:' . $info['mime'] . ';base64,' . base64_encode((string) file_get_contents($arquivo));
    }

    /**
     * HTML completo. `$vazias` recebe as variáveis usadas sem valor (alerta na emissão).
     */
    public function montarHtml(array $modelo, array $snap, ?array &$vazias = null): string
    {
        $vazias = [];
        $tipo = $snap['emissao']['tipo'];
        $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        if (AdmsHistoricoEditor::ehFormato2($modelo)) {
            $valores = AdmsHistoricoVariaveis::valores($snap, $modelo['opcoes'] ?? []); // E5: as casas do documento
            $corpo = $this->documento2($modelo, $snap, $valores, $h, $vazias);
            return '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>' . $h(self::titulo($tipo)) . '</title><style>' . self::css($modelo['opcoes'] ?? []) . '</style></head><body>'
                . $corpo . '</body></html>';
        }
        $valores = AdmsHistoricoVariaveis::valores($snap);
        $corpo = '';
        foreach (AdmsHistoricoSecoes::ordenadas($modelo, $tipo) as $s) {
            if (empty($s['ativa'])) {
                continue;
            }
            if ($s['tipo'] === 'tcc' && !empty($s['somenteSeHouver']) && empty($snap['vida']['tcc'])) {
                continue;
            }
            $html = AdmsHtmlControlado::limpar((string) ($s['html'] ?? ''));
            $conteudo = AdmsHistoricoVariaveis::substituir($html, $valores, $v);
            $vazias = array_merge($vazias, $v);
            switch ($s['tipo']) {
                case 'cabecalho':
                    $logo = !empty($s['logo']) ? self::dataUri(self::arquivoLogo()) : null;
                    $corpo .= '<table class="cab"><tr>' . ($logo ? '<td class="cab-logo"><img src="' . $logo . '" alt=""></td>' : '')
                        . '<td class="cab-texto">' . $conteudo . '</td></tr></table>';
                    break;
                case 'quadro':
                    $corpo .= '<div class="secao s-quadro">' . $conteudo . $this->quadro($s['colunas'] ?? [], $snap, $h) . '</div>';
                    break;
                case 'assinaturas':
                    $corpo .= '<div class="secao s-assinaturas">' . $conteudo . $this->assinaturas($s['blocos'] ?? [], $h) . '</div>';
                    break;
                case 'rodape':
                    $qr = !empty($s['qr']) ? self::qr((string) $snap['emissao']['url']) : null;
                    $corpo .= '<table class="rodape"><tr><td>' . $conteudo . '</td>' . ($qr ? '<td class="qr"><img src="' . $qr . '" alt=""></td>' : '') . '</tr></table>';
                    break;
                default:
                    $corpo .= '<div class="secao s-' . $s['tipo'] . '">' . $conteudo . '</div>';
            }
        }
        $vazias = array_values(array_unique($vazias));
        return '<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Histórico escolar ' . $h($tipo) . '</title><style>' . self::css() . '</style></head><body>'
            . $corpo . '</body></html>';
    }

    /**
     * E8d (A24): o documento do FORMATO 2. O HTML passa de novo pela lista branca; o trecho do TCC sai quando
     * não há TCC (e as variáveis dele não contam como vazias); os blocos entram por marcas que a troca das
     * variáveis não alcança; um bloco sozinho num parágrafo troca o parágrafo inteiro (tabela não vai em <p>).
     */
    private function documento2(array $modelo, array $snap, array $valores, callable $h, array &$vazias): string
    {
        $html = AdmsHtmlControlado::limpar(AdmsHistoricoEditor::marcadoresParaVariaveis((string) ($modelo['documento'] ?? '')));
        $html = AdmsHistoricoEditor::aplicarCondicionais($html, !empty($snap['vida']['tcc']), $valores); // E5 (D-366)
        $marcas = [];
        $html = (string) preg_replace_callback('#(<p>\s*)?\{\{\s*(' . implode('|', array_keys(AdmsHistoricoEditor::BLOCOS)) . ')\s*\}\}(\s*</p>)?#', function ($m) use (&$marcas) {
            $marca = "\x01BLOCO" . count($marcas) . "\x01";
            $marcas[$marca] = $m[2];
            $abre = $m[1] ?? '';
            $fecha = $m[3] ?? '';
            if ($abre !== '' && $fecha !== '') {
                return $marca; /* sozinho no parágrafo: o bloco toma o lugar do <p> */
            }
            return $abre . $marca . $fecha;
        }, $html);
        $html = AdmsHistoricoVariaveis::substituir($html, $valores, $vazias);
        foreach ($marcas as $marca => $bloco) {
            $html = str_replace($marca, $this->bloco($bloco, $modelo, $snap, $h), $html);
        }
        return '<div class="doc">' . $html . '</div>';
    }

    private function bloco(string $bloco, array $modelo, array $snap, callable $h): string
    {
        switch ($bloco) {
            case 'QUADRO_DISCIPLINAS':
                return $this->quadro($modelo['quadro']['colunas'] ?? [], $snap, $h, $modelo['quadro'] ?? [], $modelo['opcoes'] ?? []);
            case 'ASSINATURAS':
                return $this->assinaturas($modelo['assinaturas']['blocos'] ?? [], $h);
            case 'LOGO_INSTITUICAO':
                $logo = self::dataUri(self::arquivoLogo());
                return $logo ? '<img class="logo-inst" src="' . $logo . '" alt="">' : '';
            case 'INSTITUICAO_CABECALHO':
                /* E1a (D-339): as linhas preenchidas da instituição e, por último, a linha do curso da matrícula — do
                   SNAPSHOT (o documento emitido não muda se o cadastro mudar depois). Nada preenchido: bloco vazio. */
                $linhas = array_merge((array) ($snap['vida']['instituicao']['cabecalho'] ?? []), [(string) ($snap['vida']['matricula']['cursoCabecalho'] ?? '')]);
                $linhas = array_values(array_filter(array_map('trim', $linhas), fn($l) => $l !== ''));
                return $linhas ? '<div class="cab-inst">' . implode('<br>', array_map($h, $linhas)) . '</div>' : '';
            case 'QR_CODE':
                $qr = self::qr((string) $snap['emissao']['url']);
                return $qr ? '<img class="qr-img" src="' . $qr . '" alt="">' : '';
            case 'CODIGO_VERIFICACAO':
                return '<span class="codigo-verif">Código de verificação: <b>' . $h($snap['emissao']['codigo']) . '</b> — confira em ' . $h($snap['emissao']['url']) . '</span>';
        }
        return '';
    }

    /** E8d (A24): paginação do modelo, nos dois formatos (formato 1: a opção do rodapé ativo). */
    public static function paginacao(array $modelo): bool
    {
        if (AdmsHistoricoEditor::ehFormato2($modelo)) {
            return !empty($modelo['opcoes']['paginacao']);
        }
        foreach ($modelo['secoes'] ?? [] as $s) {
            if (($s['tipo'] ?? '') === 'rodape') {
                return !empty($s['ativa']) && !empty($s['paginacao']);
            }
        }
        return false;
    }

    /** E6 (D-374): o título do HTML (aba do navegador / propriedades do PDF). */
    public static function titulo(string $tipo): string
    {
        return $tipo === 'ATESTADO' ? 'Atestado de matrícula' : 'Histórico escolar ' . $tipo;
    }

    /**
     * CSS do documento. E5 (D-367): `estilo` = "oficial" dá as medidas do modelo Word da instituição de origem (A.0 do levantamento:
     * margens, cabeçalho sem filete, quadro com bordas pretas de 0,5 pt, linhas finais com fundo #B2B2B2); `fonte` =
     * "liberation" troca a família (a métrica da Arial). Sem as opções (todos os modelos anteriores), o CSS de sempre.
     */
    public static function css(array $opcoes = []): string
    {
        $familia = ($opcoes['fonte'] ?? '') === 'liberation' ? "'" . self::FONTE_LIBERATION . "', DejaVu Sans, sans-serif" : 'DejaVu Sans, sans-serif';
        if (($opcoes['estilo'] ?? '') === 'oficial') {
            return self::cssOficial($familia);
        }
        $css = self::cssPadrao();
        return $familia === 'DejaVu Sans, sans-serif' ? $css : $css . 'body { font-family: ' . $familia . '; }';
    }

    /** E5 (D-367): as medidas do .docx da instituição de origem (twips → mm/pt; A.0). */
    private static function cssOficial(string $familia): string
    {
        return '@page { margin: 11.3mm 23.1mm 12mm 26.8mm; }'
            . 'body { font-family: ' . $familia . '; font-size: 11pt; color: #000; line-height: 1.15; }'
            . 'p { margin: 0; } h1 { font-size: 12pt; font-weight: bold; text-align: center; margin: 0; } h2 { font-size: 12pt; margin: 0; } h3 { font-size: 11pt; margin: 0; } h4 { font-size: 10pt; margin: 0; }'
            . 'table { width: 100%; border-collapse: collapse; }'
            . '.cab { margin-left: -18.7mm; width: 185mm; border: none; margin-bottom: 0; } .cab td { vertical-align: middle; padding: 0 1.9mm; border: none; }'
            . '.cab-logo { width: 32.5mm; } .cab-logo img, img.logo-inst { width: 29.5mm; max-width: 29.5mm; max-height: 19.1mm; }'
            . '.cab-texto, .cab-inst { font-size: 9pt; line-height: 1.2; } .cab-texto p { margin: 0; }'
            . '.quadro { margin-left: 1mm; } .quadro th, .quadro td { border: 0.5pt solid #000; padding: 0.4mm 1mm; font-size: 9pt; vertical-align: middle; line-height: 1.15; }'
            . '.quadro th { font-weight: bold; text-align: center; height: 9mm; background: none; } .quadro tr.linha-final td { background: #B2B2B2; }'
            . '.quadro td.rotulo-final { text-align: right; } .quadro small { color: #333; }'
            . '.ativ th, .ativ td { border: 0.4pt solid #666; font-size: 7pt; padding: 1pt 2pt; text-align: center; }'
            . '.assin td { text-align: center; vertical-align: bottom; padding: 0; font-size: inherit; border: none; } .assin img { max-height: 34pt; max-width: 130pt; } .assin .linha { border: none; padding: 0; }'
            . '.recuo { text-indent: 29mm; } .table-bordered td, .table-bordered th { border: 0.5pt solid #000; padding: 1mm; }'
            . 'img.qr-img { width: 62pt; } .codigo-verif { font-size: 8pt; }'
            . '.rodape-fixo { position: fixed; left: 0; right: 0; bottom: -2mm; text-align: center; font-size: 9pt; line-height: 1.3; }';
    }

    private static function cssPadrao(): string
    {
        return '@page { margin: 16mm 13mm 18mm 13mm; }'
            . 'body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #111; line-height: 1.3; }'
            . 'p { margin: 0 0 2pt 0; } h1 { font-size: 13pt; margin: 4pt 0; } h2 { font-size: 11.5pt; margin: 4pt 0; } h3 { font-size: 10pt; margin: 4pt 0; } h4 { font-size: 9pt; margin: 3pt 0; }'
            . 'table { width: 100%; border-collapse: collapse; } .secao { margin: 0 0 7pt 0; }'
            . '.cab { margin-bottom: 6pt; border-bottom: 0.8pt solid #333; } .cab td { vertical-align: middle; padding-bottom: 4pt; }'
            . '.cab-logo { width: 18%; } .cab-logo img { max-height: 58pt; max-width: 110pt; } .cab-texto p { margin: 0; }'
            . '.s-aluno td, .s-matricula td { padding: 1pt 2pt; }'
            . '.quadro th, .quadro td { border: 0.5pt solid #777; padding: 2pt 3pt; font-size: 7.3pt; vertical-align: top; }'
            . '.quadro th { background: #e8e8e8; } .quadro tr.modulo td { background: #f2f2f2; font-weight: bold; }'
            . '.quadro tr.tentativa-anterior td { color: #555; } .quadro small { color: #555; }'
            . '.ativ th, .ativ td { border: 0.4pt solid #999; font-size: 6.4pt; padding: 1pt 2pt; text-align: center; } .ativ th { background: #f6f6f6; font-weight: normal; }'
            . '.assin td { text-align: center; vertical-align: bottom; padding: 22pt 6pt 0 6pt; font-size: 8pt; } .assin img { max-height: 34pt; max-width: 130pt; }'
            . '.assin .linha { border-top: 0.6pt solid #000; padding-top: 2pt; }'
            . '.doc > div.secao, .doc > p { margin-bottom: 7pt; } .table-bordered td, .table-bordered th { border: 0.5pt solid #777; padding: 2pt 3pt; }'
            . 'img.logo-inst { max-height: 58pt; max-width: 110pt; } img.qr-img { width: 62pt; } .codigo-verif { font-size: 7.5pt; }'
            . '.rodape { margin-top: 6pt; border-top: 0.6pt solid #999; } .rodape td { vertical-align: top; padding-top: 3pt; } .rodape .qr { width: 70pt; text-align: right; } .rodape .qr img { width: 62pt; }';
    }

    public static function arquivoLogo(): ?string
    {
        $d = \App\adms\Models\helper\AdmsInstituicao::dados();
        $logo = basename((string) ($d['logo'] ?? ''));
        return $logo !== '' ? self::raiz() . '/assets/instituicao/' . $logo : null;
    }

    public static function qr(string $url): ?string
    {
        if ($url === '' || !class_exists('\chillerlan\QRCode\QRCode')) {
            return null;
        }
        try {
            $o = new \chillerlan\QRCode\QROptions(['outputType' => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG, 'eccLevel' => \chillerlan\QRCode\QRCode::ECC_M, 'scale' => 4, 'imageBase64' => true]);
            return (new \chillerlan\QRCode\QRCode($o))->render($url);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** O quadro de disciplinas, agrupado por módulo com a CH do módulo (com o parâmetro de módulos em Sim). */
    private function quadro(array $colunas, array $snap, callable $h, array $opcoes = [], array $opDoc = []): string
    {
        /* E8d (A24): opções do formato 2 (aba Quadro); o formato 1 fica com o comportamento de sempre */
        $comAtividades = !array_key_exists('atividades', $opcoes) || !empty($opcoes['atividades']);
        /* E5 (D-368 a D-370): casas do documento, CH no nome, só a tentativa vigente, em andamento em branco */
        $fmt = ['casas' => isset($opDoc['casasNota']) ? (int) $opDoc['casasNota'] : null, 'chNoNome' => !empty($opcoes['chNoNome']),
            'textoCh' => (string) ($opcoes['textoCh'] ?? AdmsHistoricoEditor::TEXTO_CH), 'branco' => !empty($opcoes['andamentoEmBranco'])];
        $soVigente = !empty($opcoes['soVigente']);
        $modoFreq = (string) ($opcoes['frequencia'] ?? AdmsHistoricoEditor::FREQUENCIA_PADRAO); // E2 (D-362)
        $emCurso = (string) ($opcoes['emCurso'] ?? '') !== '' ? (string) $opcoes['emCurso'] : 'Em curso';
        /* E8c (D-268): sem módulos, o quadro é uma lista simples — sem a linha do módulo, sem CH por módulo e sem a
           coluna "Módulo" (um modelo antigo que a tenha continua válido: a coluna só não sai). */
        $semMod = \App\adms\Models\helper\AdmsModuloInterno::ativo();
        if ($semMod) {
            $colunas = array_values(array_filter($colunas, fn($c) => $c['campo'] !== 'modulo'));
        }
        if (!$colunas) {
            return '';
        }
        $parcial = $snap['emissao']['tipo'] === 'PARCIAL';
        $n = count($colunas);
        $out = '<table class="quadro"><thead><tr>';
        foreach ($colunas as $c) {
            $out .= '<th style="' . ($c['largura'] !== '' ? 'width: ' . $h($c['largura']) . '; ' : '') . 'text-align: ' . $h($c['alinhamento']) . '">' . $h($c['rotulo']) . '</th>';
        }
        $out .= '</tr></thead><tbody>';
        foreach ($snap['vida']['modulos'] as $m) {
            if (!$semMod) {
                $out .= '<tr class="modulo"><td colspan="' . $n . '">' . $h($m['nome']) . ' — CH ' . (int) $m['ch'] . ' h</td></tr>';
            }
            foreach ($m['disciplinas'] as $d) {
                $linhas = ($parcial && !$soVigente) ? $d['tentativas'] : ($d['vigente'] ? [$d['vigente']] : []);
                if (!$linhas) {
                    $out .= $this->linha($colunas, $m, $d, null, $parcial, $h, $emCurso, $modoFreq, $fmt);
                    continue;
                }
                foreach ($linhas as $t) {
                    $out .= $this->linha($colunas, $m, $d, $t, $parcial && !$soVigente && count($d['tentativas']) > 1, $h, $emCurso, $modoFreq, $fmt);
                    if ($parcial && $comAtividades && !empty($t['atividades'])) {
                        $out .= '<tr class="atividades" data-oferta="' . (int) $t['oferta_id'] . '"><td colspan="' . $n . '">' . $this->atividades($t['atividades'], $h) . '</td></tr>';
                    }
                }
            }
        }
        if (!empty($opcoes['linhasFinais'])) {
            $out .= $this->linhasFinais($colunas, $snap, $h, $fmt['casas']);
        }
        return $out . '</tbody></table>';
    }

    /**
     * E5 (D-370, D-314, D-315): as 3 linhas finais do quadro, com fundo cinza: o rótulo na 1ª coluna (à direita) e o
     * valor na 2ª. Média final do aluno só no FINAL (no PARCIAL, em branco); TCC com as casas do documento (em branco
     * sem TCC lançado); Situação = o rótulo de documento da situação da matrícula.
     */
    private function linhasFinais(array $colunas, array $snap, callable $h, ?int $casas): string
    {
        $v = $snap['vida'];
        $casas = $casas ?? (int) ($v['regras']['casas'] ?? 1);
        $valores = [
            'media' => $snap['emissao']['tipo'] === 'FINAL' ? AdmsVidaAcademica::mediaFinalTexto($v, $casas) : '',
            'tcc' => AdmsVidaAcademica::notaTccTexto($v, $casas),
            'situacao' => (string) ($v['matricula']['situacaoDocumento'] ?? ''),
        ];
        $n = count($colunas);
        $out = '';
        foreach (AdmsHistoricoEditor::LINHAS_FINAIS as $k => $rot) {
            $out .= '<tr class="linha-final" data-final="' . $k . '"><td class="rotulo-final">' . $h($rot) . '</td>';
            if ($n > 1) {
                $out .= '<td class="valor-final" style="text-align: left">' . $h($valores[$k]) . '</td>' . str_repeat('<td></td>', $n - 2);
            }
            $out .= '</tr>';
        }
        return $out;
    }

    private function linha(array $colunas, array $m, array $d, ?array $t, bool $mostrarTurma, callable $h, string $emCurso = 'Em curso', string $modoFreq = 'percentual', array $fmt = []): string
    {
        /* E5 (D-331): disciplina em andamento com nota e frequência em branco (modelo da instituição de origem) */
        $branco = !empty($fmt['branco']) && $t && !$t['encerrada'];        $dt = fn($x) => $x ? date('d/m/Y', strtotime($x)) : '';
        $attrs = $t ? ' class="tentativa' . (!$t['vigente'] ? ' tentativa-anterior' : '') . '" data-oferta="' . (int) $t['oferta_id'] . '" data-vigente="' . ($t['vigente'] ? 1 : 0) . '"'
            : ' class="nao-cursada" data-linha="' . (int) $d['linha_id'] . '"';
        $out = '<tr' . $attrs . '>';
        foreach ($colunas as $c) {
            switch ($c['campo']) {
                case 'codigo': $v = $h($d['codigo']); break;
                case 'disciplina': $v = $h($d['nome']) . (!empty($fmt['chNoNome']) ? ' (<b>' . (int) $d['ch'] . '</b> ' . $h($fmt['textoCh']) . ')' : '') // E5 (D-369)
                    . ($t && $mostrarTurma ? '<br><small>Turma ' . $h($t['turma']) . '/' . (int) $t['ano'] . '</small>' : ''); break;
                case 'modulo': $v = $h($m['nome']); break;
                case 'ch': $v = (int) $d['ch'] . ' h'; break;
                case 'periodo': $v = $t && $t['periodoInicio'] ? $dt($t['periodoInicio']) . ' a ' . $dt($t['periodoFim']) : ''; break;
                case 'docente': $v = $t ? $h(implode('; ', array_map(fn($x) => $x['nome'], $t['docentes']))) : ''; break;
                case 'titulacao': $v = $t ? $h(implode('; ', array_map(fn($x) => $x['titulacao'] ?: '—', $t['docentes']))) : ''; break;
                case 'nota': $v = $t && !$branco ? $h(self::nota($t, $fmt['casas'] ?? null)) : ''; break;
                case 'frequencia': $v = $t && !$branco ? $h(self::frequencia($t, $modoFreq)) : ''; break;
                case 'situacao': $v = $h(self::situacao($t, $emCurso)); break;
                default: $v = '';
            }
            $out .= '<td class="c-' . $c['campo'] . '" style="text-align: ' . $h($c['alinhamento']) . '">' . $v . '</td>';
        }
        return $out . '</tr>';
    }

    /** As mesmas fórmulas de exibição da prévia técnica (Views/historico/previa.php). */
    public static function nota(array $t, ?int $casasDocumento = null): string
    {
        /* E5 (D-368): as casas do DOCUMENTO só formatam o número (a média é a gravada/calculada, com as casas de cálculo) */
        return $t['media'] === null ? '—' : Calc::formatar($t['media'], $casasDocumento ?? (int) $t['casas']) . ($t['parcial'] && !$t['encerrada'] ? ' (parcial)' : '');
    }

    /**
     * A frequência da tentativa no quadro. E2 (D-362): `percentual` (o de sempre), `sigla` (FS/FI: só com a oferta
     * encerrada; aberta ou sem encontros, em branco) ou `ambos`. A sigla vem SÓ do motor (Calc::siglaFrequencia).
     */
    public static function frequencia(array $t, string $modo = 'percentual'): string
    {
        $pct = $t['frequencia'] === null ? 'não informada' : Calc::formatar($t['frequencia'], 2) . '%';
        if ($modo === 'percentual') {
            return $pct;
        }
        $sigla = Calc::siglaFrequencia($t);
        if ($modo === 'sigla') {
            return $sigla;
        }
        return trim($pct . ' ' . $sigla);
    }

    public static function situacao(?array $t, string $emCurso = 'Em curso'): string
    {
        if (!$t) {
            return 'Não cursada';
        }
        return $t['encerrada'] ? (string) $t['rotulo'] : $emCurso;
    }

    private function atividades(array $atividades, callable $h): string
    {
        $cab = '';
        $val = '';
        foreach ($atividades as $a) {
            $cab .= '<th>' . $h($a['tipo'] ?: 'Atividade') . '<br>' . ($a['data'] ? date('d/m/Y', strtotime($a['data'])) : 's/d') . '</th>';
            $val .= '<td>' . $h($a['notaFormatada'] ?? '—') . '</td>';
        }
        return '<table class="ativ"><tr>' . $cab . '</tr><tr>' . $val . '</tr></table>';
    }

    private function assinaturas(array $blocos, callable $h): string
    {
        if (!$blocos) {
            return '';
        }
        $out = '<table class="assin">';
        foreach (array_chunk($blocos, 3) as $linha) {
            $out .= '<tr>';
            foreach ($linha as $b) {
                $img = !empty($b['imagem']) ? self::dataUri(AdmsHistoricoModelos::pastaAssinaturas() . basename($b['imagem'])) : null;
                $out .= '<td style="width: ' . floor(100 / count($linha)) . '%">' . ($img ? '<img src="' . $img . '" alt=""><br>' : '')
                    . '<div class="linha">' . $h($b['nome']) . ($b['nome'] !== '' && $b['cargo'] !== '' ? '<br>' : '') . $h($b['cargo']) . '</div></td>';
            }
            $out .= '</tr>';
        }
        return $out . '</table>';
    }

    /**
     * HTML → PDF. `$tarja` (prévia) ou `$marcaVia` ("2ª VIA …") saem no topo de toda página;
     * `$codigo` e a paginação, no pé (quando o rodapé do modelo pede paginação).
     */
    public function pdf(string $html, string $tarja = '', string $marcaVia = '', string $codigo = '', bool $paginacao = true): ?string
    {
        if (!class_exists(\Dompdf\Dompdf::class)) { // E6 (D-376): no portal o Dompdf vem da ponte (AdmsPainelPonte), sem o App\ do painel
            require_once self::raiz() . '/painel/vendor/autoload.php';
        }
        try {
            $o = new \Dompdf\Options();
            $o->set('isRemoteEnabled', false);
            $o->set('isPhpEnabled', false);
            $o->set('isJavascriptEnabled', false);
            $o->set('defaultFont', 'DejaVu Sans');
            /* E5 (D-367): as métricas de fonte ficam na pasta privada (gravável), não em vendor/; a Liberation Sans
               (assets/fontes, licença SIL OFL ao lado) é registrada só quando o documento a usa. */
            $fontes = self::storage() . 'fontes';
            if (!is_dir($fontes)) {
                @mkdir($fontes, 0775, true);
            }
            $o->set('fontDir', $fontes);
            $o->set('fontCache', $fontes);
            $o->setChroot([self::storage() . 'historico', self::raiz() . '/assets/fontes']);
            $d = new \Dompdf\Dompdf($o);
            if (str_contains($html, self::FONTE_LIBERATION)) {
                self::registrarLiberation($d);
            }
            $d->loadHtml($html, 'UTF-8');
            $d->setPaper('A4', 'portrait');
            $d->render();
            $d->getCanvas()->page_script(function ($pagina, $total, $canvas, $fm) use ($tarja, $marcaVia, $codigo, $paginacao) {
                $negrito = $fm->getFont('DejaVu Sans', 'bold');
                $normal = $fm->getFont('DejaVu Sans', 'normal');
                if ($tarja !== '') {
                    $canvas->text(37, 16, $tarja, $negrito, 9, [0.75, 0.05, 0.1]);
                }
                if ($marcaVia !== '') {
                    $canvas->text(37, $tarja !== '' ? 27 : 16, $marcaVia, $negrito, 9, [0.1, 0.2, 0.55]);
                }
                if ($paginacao) {
                    $pe = ($codigo !== '' ? 'Código de verificação ' . $codigo . ' · ' : '') . 'Página ' . $pagina . ' de ' . $total;
                    $larg = $fm->getTextWidth($pe, $normal, 7);
                    $canvas->text(558 - $larg, 818, $pe, $normal, 7, [0.3, 0.3, 0.3]);
                }
            });
            return $d->output();
        } catch (\Throwable $e) {
            $this->erro = 'Falha ao gerar o PDF: ' . $e->getMessage();
            return null;
        }
    }
}
