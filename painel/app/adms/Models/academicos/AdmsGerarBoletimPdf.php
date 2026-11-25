<?php

namespace App\adms\Models\academicos;

use Dompdf\Dompdf;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * BOLETIM EM PDF (E4): mesmo conteúdo do boletim do painel e do portal — por curso,
 * uma etapa, agrupado por módulo, Aprovado/Reprovado — a partir de AdmsBoletimCurso
 * (motor único). Saíram os 4 bimestres, o "RESULTADO: PROMOVIDO" e o controle de
 * exibição por bimestre.
 * Onda 8, correção, acréscimo C (D-80): os boletins de todos os alunos de uma turma num PDF só (gerarTurma), um aluno
 * por página, com o MESMO conteúdo do boletim de cada um (o corpo é o mesmo).
 */
class AdmsGerarBoletimPdf
{
    /** O logo da instituição (ou o brasão neutro) como data URI: o PDF não busca nada pela rede. */
    private static function logo(): string
    {
        $doc = \App\adms\Models\historico\AdmsHistoricoDocumento::class;
        return $doc::dataUri($doc::arquivoLogo()) ?? $doc::dataUri($doc::raiz() . '/assets/marca/vcosta.png') ?? '';
    }

    private const ESTILO = "body{font-family:Helvetica,Arial,sans-serif;font-size:10.5px;color:#222}
            table{width:100%;border-collapse:collapse}
            .tabela-boletim{margin-bottom:12px}
            .tabela-boletim th{background:#e9ecef;text-align:left;padding:4px 5px;border-bottom:1px solid #adb5bd}
            .tabela-boletim td{padding:3px 5px;border-bottom:1px solid #dee2e6}
            .tabela-boletim tbody tr:nth-child(even) td{background:#f7f7f7}
            .num{text-align:right}
            .abaixo{color:#c82333;font-weight:bold}
            tr.grupo td{background:#e9f1f7 !important;font-weight:bold}
            tr.grupo .ch{float:right;font-weight:normal}
            .etiqueta-parcial{font-size:8px;background:#fff3cd;color:#856404;padding:0 3px;border-radius:2px}
            .badge{font-size:9px;padding:1px 4px;border-radius:3px;color:#fff}
            .bg-success-lt{background:#28a745}.bg-danger-lt{background:#dc3545}.bg-secondary-lt{background:#6c757d}
            .faixa-aluno{background:#17607a;color:#fff;margin:8px 0 4px 0}
            .faixa-aluno td{padding:4px 7px;vertical-align:top}
            .faixa-aluno .rotulo{display:block;font-size:7.5px;text-transform:uppercase;color:#d7e7ec}
            .regras-boletim{font-size:9px;color:#495057;background:#f8f9fa;border-left:3px solid #adb5bd;padding:4px 6px;margin-top:6px}
            .pagina{page-break-after:always}
            .pagina:last-child{page-break-after:auto}";

    /** O boletim de um aluno (o cabeçalho da instituição e as tabelas), sem o <html> em volta. */
    private static function corpo(string $rm, int $curso, int $ano): string
    {
        $B = \App\adms\Models\notas\AdmsBoletimCurso::class;
        $b = $B::filtrar($B::doAluno($rm), $curso, $ano);
        $h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
        $rg = $b['regras'] ?? ['mediaMinima' => '7.00', 'frequenciaMinima' => '75.00'];
        $html = "<table><tr><td width='15%'><img src='" . self::logo() . "' alt='" . $h(\App\adms\Models\helper\AdmsInstituicao::nome()) . "' style='height:55px'></td>"
            . "<td style='text-align:center'><b style='font-size:15px'>" . $h(\App\adms\Models\helper\AdmsInstituicao::nome()) . "</b><br>Boletim" . ($ano ? ' — ' . (int) $ano : '') . "</td><td width='15%'></td></tr></table>";
        if (empty($b['aluno'])) {
            return $html . '<p>Aluno não encontrado.</p>';
        }
        if (empty($b['blocos'])) {
            $html .= $B::faixaAluno($b['aluno'], null) . '<p>Sem disciplina' . ($curso || $ano ? ' com este filtro' : '') . '.</p>';
        }
        foreach ($b['blocos'] as $bl) {
            $html .= $B::faixaAluno($b['aluno'], $bl['matricula']) . $B::tabela($bl, $rg, ['ano' => true, 'pdf' => true]);
        }
        return $html . $B::regrasTexto($rg, true, $b['blocos']); // E5: os mínimos do curso
    }

    /** O PDF, na tela (sem acesso remoto, como o histórico; o logo vai embutido). */
    private static function enviar(string $corpo, string $arquivo): void
    {
        $html = "<html><head><meta charset='utf-8'><style>" . self::ESTILO . "</style></head><body>" . $corpo
            . '<p style="font-size:8.5px;color:#666">Emitido em ' . date('d/m/Y H:i') . '.</p></body></html>';
        /* E6c (D-205): sem acesso remoto, como o histórico; o logo vai embutido (data URI). */
        $dompdf = new Dompdf();
        $options = $dompdf->getOptions();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('defaultFont', 'Helvetica');
        $dompdf->setOptions($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($arquivo, ['Attachment' => false]);
    }

    /** E8c (A11): o MESMO desenho do painel e do portal (AdmsBoletimCurso::faixaAluno/tabela), com o mesmo filtro de curso e ano. */
    public function gerar(string $rm, int $curso = 0, int $ano = 0): void
    {
        self::enviar(self::corpo($rm, $curso, $ano), 'boletim_' . preg_replace('/\D/', '', $rm) . '.pdf');
    }

    /**
     * Onda 8 (D-80): os boletins da turma (os alunos que estão nela hoje, pelo nome), do ano da turma, um por página.
     * @param list<string> $rms os RA, já na ordem
     */
    public function gerarTurma(int $turmaId, int $ano, array $rms): void
    {
        $corpo = '';
        foreach ($rms as $rm) {
            $corpo .= "<div class='pagina'>" . self::corpo((string) $rm, 0, $ano) . '</div>';
        }
        if ($corpo === '') {
            $corpo = '<p>A turma não tem alunos.</p>';
        }
        self::enviar($corpo, 'boletins_turma_' . $turmaId . '_' . $ano . '.pdf');
    }
}
