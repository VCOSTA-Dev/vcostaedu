<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * =============================================================================
 * O ENDEREÇO DE UM ARQUIVO ESTÁTICO, com a versão do sistema no fim.
 * =============================================================================
 *
 * ⛔ **O problema que isto resolve custou uma sessão inteira.**
 *
 * O usuário reportou o Desfazer *"não acontece NADA. Nenhuma chamada aparece no
 * Network — nem erro, nem requisição"*. A prova automática passava 26/26,
 * clicando no botão real e abrindo o modal pelo gesto. As duas coisas eram
 * verdade ao mesmo tempo, e a diferença não estava no código.
 *
 * O rodapé pedia o arquivo assim:
 *
 *     assets/js/funcoes.js?v=<VERSAODEV>
 *
 * e `VERSAODEV` é uma **constante fixa** no `core/Config.php`, escrita à mão.
 * Enquanto ninguém a alterar, a URL do `funcoes.js` é a mesma de meses atrás —
 * e o Apache serve o arquivo **sem `Cache-Control` e sem `Expires`** (medido).
 * Sem essas duas linhas, o navegador aplica *freshness* heurística: guarda o
 * arquivo por cerca de 10% da idade dele. Para um arquivo de dois meses, isso é
 * quase uma semana servindo a cópia velha sem sequer perguntar ao servidor.
 *
 * ⚠️ **O HTML chega novo e o JavaScript chega velho.** É o pior estado
 * possível: a tela tem o botão, o modal abre, e nenhum handler responde. Para
 * quem olha, o sistema está travado — e não há erro nenhum para investigar.
 *
 * ⛔ **E a prova automática nunca vê isso**, porque o puppeteer sobe um perfil
 * novo a cada corrida: cache vazio, arquivo sempre fresco. A suíte respondia
 * "o código funciona", que era verdade, à pergunta "a tela funciona", que não
 * era. Nenhuma asserção sobre o DOM pega isso — a diferença está entre o
 * navegador e o disco.
 *
 * ⓘ A correção **não é automatizar o selo, é mudar de lugar o número.** O
 * controle manual foi mantido de propósito, a pedido do usuário: quem sobe a
 * correção decide que ela precisa recarregar a tela, troca o número e o
 * navegador de todo mundo obedece. Um `filemtime` automático trocaria a URL a
 * cada `git checkout` e a cada deploy que só reescreve datas, jogando fora o
 * cache de quem não precisava recarregar nada.
 *
 * ⚠️ O que estava errado não era o número escrito à mão — era o **arquivo**.
 * `VERSAODEV` mora no `core/Config.php`, que é gitignored: trocar o número ali
 * muda a máquina de quem trocou e não chega a produção. **Foi exatamente por
 * isso que ninguém incrementava.**
 *
 * ⓘ O selo sai de `adms_versoes` (migration 0041), pela `AdmsVersao` — uma
 * consulta por requisição, cacheada em estático, e com valor de recuo se o
 * banco não responder. Subir versão é escrever uma migration, que é versionada
 * e roda em produção.
 *
 * Uso, na view:
 *
 *     <script src="<?= AdmsAsset::url('assets/js/funcoes.js') ?>"></script>
 * =============================================================================
 */
class AdmsAsset
{
    /**
     * @param string $relativo caminho a partir da raiz do painel, sem barra inicial
     * @param string $base URLADM (padrão) ou URL, para os assets compartilhados
     */
    public static function url(string $relativo, string $base = URLADM): string
    {
        return $base . $relativo . '?v=' . AdmsVersao::selo();
    }

    /* ⓘ Não há mais leitura de disco aqui: o selo é uma constante, e a
       constante é a decisão de quem subiu a mudança. Ver `AdmsVersao`. */
}
