<?php

namespace App\adms\Models\historico;

if (!defined('VCOSTAEDU')) { // E6 (D-376): o portal também carrega (atestado do aluno)
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * HTML CONTROLADO das seções do modelo de histórico (E6b).
 *
 * Lista BRANCA de tags e atributos. Tudo o que não está nela sai: script, style, iframe,
 * object, embed, img, a, form, eventos on*, comentários e instruções de processamento
 * (`<?php`). Estilo inline só com text-align, font-weight, font-size e width, com valores
 * simples (nada de url(), expression(), var()).
 *
 * É aplicado AO SALVAR (quem salvou HTML com item proibido é RECUSADO, com a lista do que
 * seria removido) e AO RENDERIZAR (defesa contra linha alterada direto no banco). O modelo
 * não executa nada: variáveis são só texto trocado por `AdmsHistoricoVariaveis`.
 *
 * E8d (A24, D-301): para o editor visual, `$perigosos` separa o que é perigoso (recusa) da formatação
 * inofensiva que sai em silêncio; `class` (só as do documento) e `margin-left` entraram na lista.
 */
class AdmsHtmlControlado
{
    public const TAGS = [
        'p', 'br', 'b', 'strong', 'i', 'em', 'u', 'span', 'div', 'h1', 'h2', 'h3', 'h4',
        'table', 'thead', 'tbody', 'tr', 'th', 'td', 'ul', 'ol', 'li', 'hr', 'small', 'sup', 'sub',
    ];
    /** Tags removidas COM o conteúdo (o texto delas não é texto do documento). */
    private const REMOVER_COM_CONTEUDO = ['script', 'style', 'iframe', 'object', 'embed', 'applet', 'noscript', 'template', 'svg', 'math', 'head', 'title', 'meta', 'link', 'base', 'frame', 'frameset', 'form', 'textarea', 'select', 'button', 'input'];
    private const ATRIBUTOS = ['style', 'colspan', 'rowspan', 'class'];
    /** E8d (A24): classes aceitas — as do CSS do documento (o formato 1 convertido) e a da tabela do editor. Outras saem em silêncio. */
    private const CLASSES = '/^(cab|cab-logo|cab-texto|secao|s-[a-z_]{1,20}|rodape|qr|table|table-bordered|recuo|rodape-fixo)$/'; // E5 (D-367): recuo e rodape-fixo, do estilo oficial
    /** E8d (A24): o que torna o HTML PERIGOSO (o editor recusa ao salvar); o resto que sai é só formatação. */
    private const PERIGOSAS = ['script', 'style', 'iframe', 'object', 'embed', 'applet', 'noscript', 'template', 'svg', 'math', 'head', 'title', 'meta', 'link', 'base', 'frame', 'frameset', 'form', 'textarea', 'select', 'button', 'input', 'img', 'video', 'audio', 'source', 'picture'];
    private const URL_OU_CODIGO = '/(javascript|vbscript|data)\s*:|https?\s*:|\/\/|url\s*\(|expression\s*\(|@import/i';
    private const ESTILOS = [
        'text-align' => '/^(left|center|right|justify)$/',
        'font-weight' => '/^(normal|bold|[1-9]00)$/',
        'font-size' => '/^\d{1,2}(\.\d)?(pt|px|em|%)$/',
        'width' => '/^\d{1,3}(\.\d{1,2})?(%|pt|px|mm)$/',
        /* E8d (A24): o recuo do editor ("Recuar") */
        'margin-left' => '/^\d{1,3}(px|pt|em)$/',
        /* E5 (D-367): o recuo da primeira linha e a entrelinha do modelo Word (atestado: 2,9 cm e 1,5) */
        'text-indent' => '/^\d{1,2}(\.\d{1,2})?(mm|cm|pt|em)$/',
        'line-height' => '/^[1-3](\.\d{1,2})?$/',
    ];

    /**
     * Devolve o HTML limpo; `$removidos` recebe a lista (sem repetição) do que saiu e `$perigosos` só o que é
     * perigoso (script, evento, quadro externo, imagem ou endereço externo, PHP) — E8d (A24).
     */
    public static function limpar(string $html, ?array &$removidos = null, ?array &$perigosos = null): string
    {
        $removidos = [];
        $perigosos = [];
        if (trim($html) === '') {
            return '';
        }
        if (preg_match('/<\?(php|=)?/i', $html)) {
            $removidos[] = $perigosos[] = 'código PHP (<?php … ?>)';
            $html = preg_replace('/<\?(php|=)?.*?(\?>|$)/is', '', $html);
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $antes = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="__raiz">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($antes);
        $raiz = $dom->getElementById('__raiz');
        if (!$raiz) {
            $removidos[] = 'HTML inválido';
            return '';
        }
        self::limparNo($raiz, $removidos, $perigosos);
        $saida = '';
        foreach ($raiz->childNodes as $filho) {
            $saida .= $dom->saveHTML($filho);
        }
        $removidos = array_values(array_unique($removidos));
        $perigosos = array_values(array_unique($perigosos));
        return trim($saida);
    }

    private static function limparNo(\DOMNode $no, array &$removidos, array &$perigosos): void
    {
        foreach (iterator_to_array($no->childNodes) as $filho) {
            if ($filho instanceof \DOMText) {
                continue;
            }
            if ($filho instanceof \DOMComment) {
                $no->removeChild($filho);
                continue;
            }
            if ($filho instanceof \DOMProcessingInstruction) {
                $removidos[] = $perigosos[] = 'instrução de processamento (<?…?>)';
                $no->removeChild($filho);
                continue;
            }
            if (!$filho instanceof \DOMElement) {
                $no->removeChild($filho);
                continue;
            }
            $tag = strtolower($filho->nodeName);
            foreach (iterator_to_array($filho->attributes) as $attr) {
                $nomeAttr = strtolower($attr->nodeName);
                if (str_starts_with($nomeAttr, 'on')) {
                    $perigosos[] = "evento {$nomeAttr}";
                } elseif (preg_match(self::URL_OU_CODIGO, (string) $attr->nodeValue)) {
                    $perigosos[] = "endereço externo ou código ({$nomeAttr})";
                }
            }
            if (!in_array($tag, self::TAGS, true)) {
                $removidos[] = "<{$tag}>";
                if (in_array($tag, self::PERIGOSAS, true)) {
                    $perigosos[] = "<{$tag}>";
                }
                if (in_array($tag, self::REMOVER_COM_CONTEUDO, true)) {
                    $no->removeChild($filho);
                } else {
                    /* tag desconhecida inofensiva (ex.: <a>, <font>): sai a tag, fica o texto */
                    self::limparNo($filho, $removidos, $perigosos);
                    while ($filho->firstChild) {
                        $no->insertBefore($filho->firstChild, $filho);
                    }
                    $no->removeChild($filho);
                }
                continue;
            }
            foreach (iterator_to_array($filho->attributes) as $attr) {
                $nome = strtolower($attr->nodeName);
                if (!in_array($nome, self::ATRIBUTOS, true)) {
                    $removidos[] = str_starts_with($nome, 'on') ? "evento {$nome}" : "atributo {$nome}";
                    $filho->removeAttribute($attr->nodeName);
                    continue;
                }
                if ($nome === 'class') {
                    $ok = array_filter(preg_split('/\s+/', trim((string) $attr->nodeValue)), fn($c) => (bool) preg_match(self::CLASSES, $c));
                    $ok ? $filho->setAttribute('class', implode(' ', $ok)) : $filho->removeAttribute('class');
                } elseif ($nome === 'style') {
                    $limpo = self::estilo($attr->nodeValue, $removidos);
                    $limpo === '' ? $filho->removeAttribute('style') : $filho->setAttribute('style', $limpo);
                } elseif (!preg_match('/^\d{1,2}$/', trim($attr->nodeValue))) {
                    $removidos[] = "atributo {$nome} inválido";
                    $filho->removeAttribute($attr->nodeName);
                }
            }
            self::limparNo($filho, $removidos, $perigosos);
        }
    }

    private static function estilo(string $estilo, array &$removidos): string
    {
        $ok = [];
        foreach (explode(';', $estilo) as $decl) {
            if (trim($decl) === '') {
                continue;
            }
            [$prop, $valor] = array_pad(array_map('trim', explode(':', $decl, 2)), 2, '');
            $prop = strtolower($prop);
            $valor = strtolower($valor);
            if (isset(self::ESTILOS[$prop]) && preg_match(self::ESTILOS[$prop], $valor)) {
                $ok[] = "{$prop}: {$valor}";
            } else {
                $removidos[] = "estilo {$prop}" . (preg_match('/url\s*\(|expression|javascript:/i', $valor) ? ' (com URL/expressão)' : '');
            }
        }
        return implode('; ', $ok);
    }
}
