<?php

/**
 * O ENDEREÇO DE UM ARQUIVO ESTÁTICO, COM A MARCA DA ÚLTIMA ALTERAÇÃO.
 *
 * ============================= O QUE FOI MEDIDO =============================
 *
 * O Apache desta instalação devolve os arquivos de `assets/` **sem
 * `Cache-Control` e sem `Expires`** — só `Last-Modified` e `ETag`. Medido:
 *
 *     HTTP/1.1 200 OK
 *     Last-Modified: ...
 *     ETag: "950a-..."
 *
 * Sem instrução de validade, o navegador aplica **cache heurístico**: guarda o
 * arquivo por cerca de 10% do tempo decorrido desde o `Last-Modified` e, nesse
 * intervalo, **nem pergunta ao servidor**. O `ETag` só entra em cena depois —
 * quando o navegador resolve perguntar, o `304` confirma e ele reaproveita.
 *
 * Junte a isso um `<link>` sem versão na URL, e o resultado é o defeito que
 * custou esta função: uma correção de folha de estilo foi feita, medida e
 * conferida no servidor — e a tela de quem já tinha aberto o sistema continuou
 * exatamente igual. A correção estava lá; o navegador é que não foi buscá-la.
 *
 * ======================= POR QUE ISTO IMPORTA MAIS AQUI =====================
 *
 * Este sistema **entrega atualizações** (D25). A versão nova troca arquivos de
 * `assets/`, e a pessoa que atualiza abre o navegador para conferir. Se ela vir
 * a folha de estilo antiga, vai concluir que a atualização não funcionou — e o
 * caminho natural dali é tentar de novo, ou desfazer. É exatamente o desfecho
 * que o atualizador existe para não ter.
 *
 * ============================ POR QUE `filemtime` ===========================
 *
 * A alternativa era carimbar a versão do sistema (`?v=1.2.0`). Foi recusada por
 * duas razões: o instalador roda **antes de existir banco**, e portanto antes de
 * existir versão; e durante o desenvolvimento a versão não muda a cada edição,
 * então o problema voltaria em silêncio para quem está trabalhando no projeto.
 *
 * `filemtime` muda quando, e somente quando, o arquivo muda. É uma chamada ao
 * sistema de arquivos por asset, cacheada pelo próprio PHP dentro da requisição.
 *
 * Arquivo ausente devolve o endereço **sem** marca: aqui não é o lugar de
 * derrubar a página. Quem denuncia arquivo que falta é a tela, que fica sem
 * estilo — e isso é visível.
 *
 * ========================== USADO PELOS DOIS LADOS ==========================
 *
 * O instalador e o sistema chamam a MESMA função, e é de propósito: são duas
 * telas com o mesmo problema, e dois helpers iguais viveriam até o dia em que
 * alguém corrigisse um só.
 *
 * Este arquivo não depende de nada — nem de constante, nem de classe, nem de
 * banco. É o que permite o instalador usá-lo sem ferir a D10: ele continua
 * podendo rodar quando o sistema não sobe.
 */

if (!function_exists('assetUrl')) {
    /**
     * @param string $relativo caminho a partir da raiz do projeto,
     *                         ex.: 'assets/css/vcostable-tabler.css'
     * @param string $prefixo  o que vem antes na URL: `URL` no sistema,
     *                         `'../'` no instalador (que não tem `URL` ainda)
     */
    function assetUrl(string $relativo, string $prefixo): string
    {
        $caminho = dirname(__DIR__) . '/' . $relativo;
        $marca = is_file($caminho) ? filemtime($caminho) : false;

        return $prefixo . $relativo . ($marca === false ? '' : '?v=' . $marca);
    }
}
