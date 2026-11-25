<?php

/**
 * O MARKDOWN DOS DOCUMENTOS DA RAIZ, para a tela (Onda 3, Etapa 7): o TRADEMARK.md aparece formatado no Sobre a partir
 * do próprio arquivo, que continua sendo a fonte única.
 *
 * Só o que esses documentos usam: títulos (#, ##, ###), parágrafos, listas (- e 1.), linha (---), **negrito**,
 * *itálico* e `código`. Todo o texto é escapado antes; nenhum HTML do arquivo passa.
 */
final class VcMarkdown
{
    /** @param int $nivel o nível do título "#" na página (o "##" fica um abaixo, e assim por diante) */
    public static function html(string $md, int $nivel = 2): string
    {
        $linhas = preg_split('/\R/', str_replace("\t", '    ', $md));
        $html = '';
        $par = [];
        $lista = null; // 'ul' | 'ol'
        $fecharPar = static function () use (&$par, &$html): void {
            if ($par) {
                $html .= '<p>' . self::linha(implode(' ', $par)) . "</p>\n";
                $par = [];
            }
        };
        $fecharLista = static function () use (&$lista, &$html): void {
            if ($lista) {
                $html .= "</{$lista}>\n";
                $lista = null;
            }
        };
        foreach ($linhas as $l) {
            $t = trim($l);
            if ($t === '') {
                $fecharPar();
                $fecharLista();
                continue;
            }
            if (preg_match('/^(#{1,4})\s+(.+)$/', $t, $m)) {
                $fecharPar();
                $fecharLista();
                $h = min(6, $nivel + strlen($m[1]) - 1);
                $html .= "<h{$h}>" . self::linha($m[2]) . "</h{$h}>\n";
                continue;
            }
            if (preg_match('/^(-{3,}|\*{3,})$/', $t)) {
                $fecharPar();
                $fecharLista();
                $html .= "<hr>\n";
                continue;
            }
            if (preg_match('/^(?:[-*]|(\d+)\.)\s+(.+)$/', $t, $m)) {
                $fecharPar();
                $tipo = $m[1] !== '' ? 'ol' : 'ul';
                if ($lista !== $tipo) {
                    $fecharLista();
                    $html .= "<{$tipo}>\n";
                    $lista = $tipo;
                }
                $html .= '<li>' . self::linha($m[2]) . "</li>\n";
                continue;
            }
            if ($lista && preg_match('/^\s{2,}\S/', $l)) { // continuação do item da lista
                $html = preg_replace('#</li>\n$#', ' ' . self::linha($t) . "</li>\n", $html);
                continue;
            }
            $fecharLista();
            $par[] = $t;
        }
        $fecharPar();
        $fecharLista();
        return $html;
    }

    /** O que vale dentro de uma linha: escapa e aplica `código`, **negrito** e *itálico*. */
    private static function linha(string $t): string
    {
        $t = htmlspecialchars($t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $t = preg_replace('/`([^`]+)`/', '<code>$1</code>', $t);
        $t = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $t);
        return preg_replace('/(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?![*\w])/', '<em>$1</em>', $t);
    }
}
