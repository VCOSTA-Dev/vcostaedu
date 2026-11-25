<?php

/**
 * OS AVISOS LEGAIS — um ponto só, para o instalador, as telas de acesso, o painel, o portal e a página "Sobre".
 *
 * O rodapé diz: o nome e a versão; "Copyright © <início>-<ano corrente> VCOSTA" (o ano corrente é derivado, não
 * escrito); que é software livre, sem garantia, sob a AGPL-3.0; e leva à Licença (a página "Sobre", que mostra o
 * LICENSE), ao Código-fonte (só quando a constante URL_CODIGO_FONTE da configuração tem endereço) e ao Sobre.
 *
 * Não depende da configuração nem do banco: o instalador o usa antes de os dois existirem.
 */

declare(strict_types=1);

final class VcLegal
{
    public const NOME = 'VCOSTAEDU';
    public const TITULAR = 'VCOSTA';
    /** O ano de início da marca e do código (a decisão da terceira onda: "Copyright © 2019-<ano> VCOSTA"). */
    public const ANO_INICIO = 2019;
    public const LICENCA = 'AGPL-3.0';
    /**
     * Os endereços do projeto, fixos no código (Onda 3, Etapa 6; preenchidos na Onda 6, Etapa 7 — DECISIONS D-59). O
     * da versão nova responde o versao.json (docs/VERSAO_JSON.md); o do registro recebe o registro voluntário da
     * instalação. Enquanto o site do projeto não responder, tudo funciona como se estivessem vazios: nada aparece como
     * versão nova, os links de apoio e de serviços não aparecem (linkProjeto) e o registro fica pendente de envio. A
     * configuração pode sobrepor o da versão nova (URL_VERSAO_NOVA) e o do registro (URL_REGISTRO).
     */
    public const URL_VERSAO_NOVA = 'https://vcostaedu.com/versao.json';
    public const URL_APOIE = 'https://vcostaedu.com/apoie';
    public const URL_SERVICOS = 'https://vcostaedu.com/servicos';
    public const URL_REGISTRO = 'https://vcostaedu.com/registro';

    /**
     * O link de apoio ou de serviços, só depois que o site do projeto respondeu (a última consulta da versão nova deu
     * certo); antes disso, '' — como se o endereço estivesse vazio.
     */
    public static function linkProjeto(string $url, ?array $versaoNova): string
    {
        if ($url === '' || !$versaoNova || empty($versaoNova['ligado']) || !empty($versaoNova['erro']) || empty($versaoNova['consultada_em'])) {
            return '';
        }
        return $url;
    }

    /** "2019-<ano corrente>". */
    public static function anos(?int $agora = null): string
    {
        $agora ??= (int) date('Y');

        return $agora > self::ANO_INICIO ? self::ANO_INICIO . '-' . $agora : (string) self::ANO_INICIO;
    }

    public static function copyright(): string
    {
        return 'Copyright © ' . self::anos() . ' ' . self::TITULAR;
    }

    /**
     * A palavra VCOSTA do copyright, em imagem: a versão de texto preto para fundo claro e a de texto branco para fundo
     * escuro, trocadas pelo tema; o texto alternativo é "VCOSTA". Fora da AGPL (TRADEMARK.md).
     * Onda 3, Etapa 7: a régua é a altura das LETRAS — o CSS (.vc-legal-marca-vcosta) dá à imagem a altura de maiúscula
     * da fonte do rodapé (a unidade "cap"), assentada na linha de base; a altura do atributo só vale sem o CSS.
     */
    public static function marcaVcosta(string $raiz): string
    {
        $img = static function (string $arq, string $classe) use ($raiz): string {
            return '<img src="' . self::h($raiz . 'assets/marca/' . $arq . '.png') . '" srcset="' . self::h($raiz . 'assets/marca/' . $arq . '.png') . ' 1x, '
                . self::h($raiz . 'assets/marca/' . $arq . '@2x.png') . ' 2x" alt="VCOSTA" height="9" class="vc-legal-marca-vcosta ' . $classe . '">';
        };
        return $img('vcosta-wordmark', 'vc-marca-clara') . $img('vcosta-wordmark-branco', 'vc-marca-escura');
    }

    /**
     * A marca VCOSTAEDU do rodapé (Onda 3, Etapa 7): o desenho recortado na linha de base das letras
     * (assets/marca/vcostaedu-rodape*.svg), para as letras ficarem da altura de maiúscula da fonte, assentadas na linha do
     * texto, e o capelo subir acima dela; a versão de letras brancas no tema escuro.
     */
    public static function marcaVcostaedu(string $raiz): string
    {
        $img = static fn(string $arq, string $classe): string => '<img src="' . self::h($raiz . 'assets/marca/' . $arq . '.svg') . '" alt="' . self::NOME
            . '" height="15" class="vc-legal-marca vc-legal-marca-edu ' . $classe . '">';
        return $img('vcostaedu-rodape', 'vc-marca-clara') . $img('vcostaedu-rodape-branco', 'vc-marca-escura');
    }

    /**
     * As bibliotecas de terceiros, com a versão e a licença (Onda 3, Etapa 6: uma lista só, para a página Sobre e para
     * Manutenção → Bibliotecas): as de interface (core/creditos.php) e as PHP (o installed.json do Composer de cada app).
     * @return array<int, array{nome:string, versao:string, licenca:string}>
     */
    public static function bibliotecas(string $raiz): array
    {
        $raiz = rtrim($raiz, '/\\');
        $lista = [];
        foreach ((array) require $raiz . '/core/creditos.php' as $p) {
            $lista[] = ['nome' => (string) $p['nome'], 'versao' => self::versaoDaBiblioteca($p, $raiz), 'licenca' => (string) $p['licenca']];
        }
        $php = [];
        foreach (['painel', 'portal'] as $app) {
            $dados = json_decode((string) @file_get_contents($raiz . "/{$app}/vendor/composer/installed.json"), true);
            foreach ((is_array($dados) ? ($dados['packages'] ?? $dados) : []) as $p) {
                if (!is_array($p) || empty($p['name'])) {
                    continue;
                }
                $php[$p['name']] = ['nome' => (string) $p['name'], 'versao' => ltrim((string) ($p['version'] ?? ''), 'v'), 'licenca' => implode(', ', (array) ($p['license'] ?? []))];
            }
        }
        ksort($php);
        return array_merge($lista, array_values($php));
    }

    /** O endereço do código-fonte, da configuração (URL_CODIGO_FONTE); vazio quando não foi informado. */
    public static function urlCodigoFonte(): string
    {
        if (defined('URL_CODIGO_FONTE')) {
            $url = trim((string) constant('URL_CODIGO_FONTE'));
        } else {
            /* Fora do sistema (a página "Sobre", avulsa): a constante é lida do arquivo, sem carregar a configuração. */
            $config = (string) @file_get_contents(dirname(__DIR__) . '/painel/core/Config.php');
            $url = preg_match("/define\\(\\s*'URL_CODIGO_FONTE'\\s*,\\s*'([^']*)'\\s*\\)/", $config, $m) ? trim($m[1]) : '';
        }

        return preg_match('#^https?://#i', $url) ? $url : '';
    }

    /**
     * A versão dos ARQUIVOS: a maior versão que as migrations registram em adms_versoes. Serve onde ainda não há
     * banco (o instalador e a página "Sobre"); dentro do sistema vale a versão do banco (AdmsVersao).
     */
    /**
     * Onda 3, Etapa 1: a versão de uma biblioteca de interface, LIDA do próprio arquivo dela (core/creditos.php diz o
     * arquivo e a expressão). Sem o arquivo ou sem a marca, "não identificada" — nunca uma versão escrita à mão.
     */
    public static function versaoDaBiblioteca(array $credito, ?string $raiz = null): string
    {
        $raiz ??= dirname(__DIR__);
        $texto = (string) @file_get_contents($raiz . '/' . ($credito['arquivo'] ?? ''), false, null, 0, 1048576);
        return ($credito['padrao'] ?? '') !== '' && preg_match((string) $credito['padrao'], $texto, $m) ? $m[1] : 'não identificada';
    }

    public static function versaoDoPacote(?string $raiz = null): string
    {
        static $cache = [];
        $raiz ??= dirname(__DIR__);
        if (isset($cache[$raiz])) {
            return $cache[$raiz];
        }
        $maior = '';
        foreach (glob($raiz . '/database/migrations/*.sql') ?: [] as $arquivo) {
            $sql = (string) @file_get_contents($arquivo);
            if (!preg_match_all("/INSERT INTO `adms_versoes`[^;]*?(?:VALUES\\s*\\(|SELECT\\s+)\\d+\\s*,\\s*'(\\d+\\.\\d+\\.\\d+)'/", $sql, $m)) {
                continue;
            }
            foreach ($m[1] as $v) {
                if ($maior === '' || version_compare($v, $maior, '>')) {
                    $maior = $v;
                }
            }
        }

        return $cache[$raiz] = $maior;
    }

    private static function h(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * O rodapé. $raiz é o endereço da raiz do sistema (a constante URL, ou "../" no instalador), com barra no fim.
     */
    public static function rodape(string $versao, string $raiz, string $classe = ''): string
    {
        $itens = [
            /* a marca em imagem, as letras da altura da fonte (Onda 3, Etapa 7) — fora da AGPL (TRADEMARK.md); o nome escrito
               ao lado saiu (a marca já diz o nome; pedido do responsável pelo projeto): fica só a versão, quando há */
            self::marcaVcostaedu($raiz) . ($versao !== '' ? ' <span class="vc-legal-versao">' . self::h($versao) . '</span>' : ''),
            '<span class="vc-legal-copyright">Copyright © ' . self::h(self::anos()) . ' ' . self::marcaVcosta($raiz) . '</span>',
            '<span>Software livre, sem garantia, sob a licença ' . self::LICENCA . '</span>',
            '<a class="link-secondary" href="' . self::h($raiz . 'sobre.php#licenca') . '">Licença</a>',
        ];
        $fonte = self::urlCodigoFonte();
        if ($fonte !== '') {
            $itens[] = '<a class="link-secondary" href="' . self::h($fonte) . '" rel="noopener">Código-fonte</a>';
        }
        $itens[] = '<a class="link-secondary" href="' . self::h($raiz . 'sobre.php') . '">Sobre</a>';

        return '<div class="vc-legal small text-secondary ' . self::h($classe) . '">'
            . implode('<span class="vc-legal-sep" aria-hidden="true"> · </span>', $itens) . '</div>';
    }
}
