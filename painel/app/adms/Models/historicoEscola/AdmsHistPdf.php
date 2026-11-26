<?php

namespace App\adms\Models\historicoEscola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * O PDF DO HISTÓRICO ESCOLAR (decisão 8: o gerador e a técnica que já existem): o mesmo Dompdf do histórico do
 * superior, com a fonte Helvetica (a métrica da Arial dos modelos), sem nada remoto (as imagens em data URI), A4. O
 * rodapé de cada página (a tarja da prévia, o código de verificação, a via e a página) é desenhado por `page_script`,
 * como no documento do superior. A conta de páginas é feita no PDF já com o rodapé.
 */
class AdmsHistPdf
{
    private static string $erro = '';

    public static function erro(): string
    {
        return self::$erro;
    }

    /**
     * @param array{tarja?: string, codigo?: string, via?: string, paginacao?: bool} $rodape
     */
    /** o maior x (em pontos) de tudo o que foi desenhado na última renderização: a conferência da largura */
    private static float $maiorX = 0.0;

    public static function maiorX(): float
    {
        return self::$maiorX;
    }

    public static function gerar(string $html, string $orientacao = 'portrait', array $rodape = []): ?string
    {
        $doc = \App\adms\Models\historico\AdmsHistoricoDocumento::class;
        if (!class_exists(\Dompdf\Dompdf::class)) {
            require_once $doc::raiz() . '/painel/vendor/autoload.php';
        }
        self::$erro = '';
        try {
            $o = new \Dompdf\Options();
            $o->set('isRemoteEnabled', false);
            $o->set('isPhpEnabled', false);
            $o->set('isJavascriptEnabled', false);
            $o->set('defaultFont', 'Helvetica');
            $fontes = $doc::storage() . 'fontes';
            if (!is_dir($fontes)) {
                @mkdir($fontes, 0775, true);
            }
            $o->set('fontDir', $fontes);
            $o->set('fontCache', $fontes);
            $o->setChroot([$doc::storage() . 'historico']);
            $d = new \Dompdf\Dompdf($o);
            $d->loadHtml($html, 'UTF-8');
            $d->setPaper('A4', $orientacao);
            $d->render();
            $tarja = (string) ($rodape['tarja'] ?? '');
            $codigo = (string) ($rodape['codigo'] ?? '');
            $via = (string) ($rodape['via'] ?? '');
            $paginacao = !empty($rodape['paginacao']);
            if ($tarja !== '' || $codigo !== '' || $via !== '' || $paginacao) {
                $alt = $orientacao === 'landscape' ? 595 : 842;
                $larg = $orientacao === 'landscape' ? 842 : 595;
                $d->getCanvas()->page_script(function ($pagina, $total, $canvas, $fm) use ($tarja, $codigo, $via, $paginacao, $alt, $larg) {
                    $negrito = $fm->getFont('Helvetica', 'bold');
                    $normal = $fm->getFont('Helvetica', 'normal');
                    if ($tarja !== '') {
                        $canvas->text(30, 12, $tarja, $negrito, 9, [0.75, 0.05, 0.1]);
                    }
                    $pe = implode(' · ', array_filter([
                        $codigo !== '' ? 'Código de verificação ' . $codigo : '',
                        $via,
                        $paginacao ? 'Página ' . $pagina . ' de ' . $total : '',
                    ]));
                    if ($pe !== '') {
                        $w = $fm->getTextWidth($pe, $normal, 7);
                        $canvas->text($larg - 37 - $w, $alt - 20, $pe, $normal, 7, [0.25, 0.25, 0.25]);
                    }
                });
            }
            $out = $d->output();
            self::$maiorX = self::maiorXdoPdf($out);
            return $out;
        } catch (\Throwable $e) {
            self::$erro = 'Falha ao gerar o PDF: ' . $e->getMessage();
            return null;
        }
    }

    /**
     * A conferência da largura: contar as páginas não detecta a tabela que passa da margem direita. Lê os desenhos de
     * cada página (os retângulos "re" e as linhas "m"/"l" dos conteúdos) e devolve o maior x, em pontos.
     */
    public static function maiorXdoPdf(string $pdf): float
    {
        $mx = 0.0;
        if (!preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $m)) {
            return 0.0;
        }
        foreach ($m[1] as $s) {
            $c = @gzuncompress($s);
            if ($c === false) {
                $c = $s;
            }
            if (preg_match_all('/(-?[\d.]+) (-?[\d.]+) (-?[\d.]+) (-?[\d.]+) re\b/', $c, $r, PREG_SET_ORDER)) {
                foreach ($r as $x) {
                    $mx = max($mx, (float) $x[1] + max(0.0, (float) $x[3]));
                }
            }
            if (preg_match_all('/(-?[\d.]+) (-?[\d.]+) [ml]\b/', $c, $r, PREG_SET_ORDER)) {
                foreach ($r as $x) {
                    $mx = max($mx, (float) $x[1]);
                }
            }
        }
        return $mx;
    }

    /** Quantas páginas o PDF tem ("/Type /Page" sem o "s" de "/Pages"). */
    public static function paginas(string $pdf): int
    {
        return preg_match_all('#/Type\s*/Page(?![a-zA-Z])#', $pdf);
    }

    public static function enviar(string $pdf, string $nome): void
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9_.-]/', '', $nome) . '"');
        header('Content-Length: ' . strlen($pdf));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        echo $pdf;
    }
}
