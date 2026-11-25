<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * =============================================================================
 * A VERSÃO DO SISTEMA — e a chave que faz o navegador recarregar.
 * =============================================================================
 *
 * ⛔ **O defeito que trouxe esta classa à existência (P-58).**
 *
 * O rodapé pedia os arquivos como `funcoes.js?v=<VERSAODEV>`, e `VERSAODEV` é
 * constante fixa no `core/Config.php` — que é **gitignored**. Trocar o número
 * ali muda a máquina de quem trocou e não chega a produção, e por isso ninguém
 * incrementava: o valor ficou congelado em `2.1.01130726` enquanto o
 * `funcoes.js` mudava por baixo. Medido com `curl -I`: o Apache serve os assets
 * **sem `Cache-Control` e sem `Expires`**, e nesse caso o navegador guarda por
 * *freshness* heurística — cerca de 10% da idade do arquivo.
 *
 * ⚠️ **HTML novo e JavaScript velho** é o pior estado possível: a tela tem o
 * botão, o modal abre, e nenhum handler responde. Não há erro para investigar.
 *
 * =============================================================================
 * ONDE A VERSÃO MORA — e por que não é mais uma constante
 * =============================================================================
 *
 * Nas tabelas `adms_versoes` e `adms_novidades` (migration 0041), portadas do
 * **vcostaedu**. A primeira versão desta classe guardava o número numa constante
 * e a lista de mudanças num array — e o usuário derrubou isso com a razão certa:
 * *"o seu próprio MUDANCAS já é a segunda lista da mesma coisa, e você mesmo
 * apontou que elas divergiriam no primeiro ajuste feito de um lado só. Trazer
 * depois significa migrar duas fontes em vez de uma."*
 *
 * ⓘ E alinhar as duas casas vale por si: quem mexe no vcostaedu mexe aqui, e o
 * padrão sendo o mesmo economiza o aprendizado.
 *
 * =============================================================================
 * DUAS PERGUNTAS DIFERENTES — e elas não se juntam
 * =============================================================================
 *
 * ⚠️ *"Estou atrás?"* é a versão do **SISTEMA**, e é o que esta classe responde.
 *
 * ⚠️ *"Falta aplicar alguma migration?"* é o estado do **ESQUEMA**, e quem sabe
 * é o runner: `database/deploy/aplicar.php` sobre a tabela `adms_migrations`.
 *
 * ⛔ **Não junte as duas** — instrução explícita do usuário, e a razão está no
 * `vcostaedu`: duplicar essa informação criaria duas respostas para a mesma
 * pergunta, e elas divergiriam no primeiro ajuste feito de um lado só. O caso
 * real que a separação pega: alguém sobe os arquivos por FTP e esquece de
 * aplicar as migrations — o sistema sobe dizendo 2.2 com o banco ainda em 2.1.
 *
 * =============================================================================
 * COMO SUBIR UMA VERSÃO
 * =============================================================================
 *
 * **Ao subir qualquer mudança em `assets/js/*.js` ou `assets/css/*.css`, escreva
 * uma migration nova** com a linha em `adms_versoes` (situação 1, e a anterior
 * para 2) e as linhas de `adms_novidades`. É a mesma disciplina de antes, no
 * lugar certo: a migration é versionada e roda em produção.
 *
 * ⚠️ Mudança só de PHP não precisa de versão nova — o PHP não é cacheado pelo
 * navegador. Esquecer numa mudança de JS é o erro que isto existe para impedir.
 * =============================================================================
 */
class AdmsVersao
{
    /**
     * ⚠️ **O VALOR DE RECUO, e só isso.**
     *
     * Usado quando a consulta não responde — banco fora do ar, ou a 0041 ainda
     * não aplicada num ambiente. Nunca é a fonte da verdade.
     *
     * ⛔ E ele NÃO é atualizado a cada entrega, de propósito: uma constante que
     * se atualiza volta a ser a segunda lista que acabamos de eliminar. Ele
     * marca o piso — "antes das tabelas existirem, era isto".
     */
    private const RECUO = '1.0.0'; // VCOSTAEDU: a numeração recomeça em 1.0.0 (E3a)

    /**
     * ⓘ Uma consulta por requisição, e não uma por asset.
     *
     * ⚠️ Isto importa: o `AdmsAsset::url()` é chamado sete vezes por página
     * (quatro JS e três CSS). Sem o cache estático seriam sete consultas para
     * responder sempre a mesma coisa.
     */
    private static $vigente = null;

    /**
     * A linha da versão vigente, ou `null`.
     *
     * ⚠️ **Ordena por `major`, `minor`, `patch` — nunca pelo texto `numero`.**
     * Ordenação textual põe a 1.10 antes da 1.9, e aqui isso não é cosmético: o
     * `numero` é o selo de cache, e escolher a versão errada como vigente
     * serviria JavaScript velho para todo mundo. Seria o P-58 de volta, por
     * outra porta.
     */
    public static function vigente(): ?array
    {
        if (self::$vigente !== null) {
            return self::$vigente ?: null;
        }

        /* ⛔ **try/catch obrigatório, e a razão é o momento em que isto roda.**
           Esta classe é chamada pelo `cabecalho.php` — antes de qualquer coisa
           da página existir. Uma exceção aqui não quebra uma tela: quebra
           TODAS, inclusive a de login, e o sistema fica inacessível por causa
           de um `?v=`. Falhando, o selo cai no valor de recuo e a página sobe. */
        try {
            $ler = new AdmsRead();
            $ler->fullRead(
                'SELECT id, numero, major, minor, patch, data_versao, resumo
                   FROM adms_versoes
                  ORDER BY major DESC, minor DESC, patch DESC, id DESC
                  LIMIT :limit',
                'limit=1'
            );
            $achado = $ler->getResultado();
            self::$vigente = empty($achado) ? false : $achado[0];
        } catch (\Throwable $e) {
            error_log('AdmsVersao: nao consegui ler adms_versoes -- ' . $e->getMessage());
            self::$vigente = false;
        }

        return self::$vigente ?: null;
    }

    /** O número, para o rodapé e para quem abre chamado. */
    public static function numero(): string
    {
        $v = self::vigente();

        return $v['numero'] ?? self::RECUO;
    }

    /**
     * O selo de cache dos arquivos estáticos.
     *
     * ⓘ Método próprio, e não `numero()` direto, porque os dois papéis são
     * diferentes e podem divergir um dia — o rodapé pode querer "2.2" e o selo
     * precisa ser único. Hoje são o mesmo valor, e está declarado que são.
     */
    public static function selo(): string
    {
        return self::numero();
    }

    /**
     * As novidades da versão vigente — título e texto, na ordem escrita.
     *
     * ⓘ É o que a futura tela de novidades mostra. Vazio não é erro: uma versão
     * pode entrar sem nada para anunciar a quem usa.
     */
    public static function novidades(): array
    {
        $v = self::vigente();
        if ($v === null) {
            return [];
        }

        try {
            $ler = new AdmsRead();
            $ler->fullRead(
                'SELECT titulo, texto FROM adms_novidades
                  WHERE adms_versao_id = :id ORDER BY ordem ASC, id ASC',
                'id=' . (int) $v['id']
            );

            return $ler->getResultado() ?: [];
        } catch (\Throwable $e) {
            error_log('AdmsVersao: nao consegui ler adms_novidades -- ' . $e->getMessage());

            return [];
        }
    }

    /**
     * A pessoa já leu as novidades da versão vigente?
     *
     * ⓘ A peça que o usuário destacou no vcostaedu: *"para não reavisar quem já
     * leu"*. A marcação é por VERSÃO, e não por novidade — quem abriu a página
     * leu todas as daquela versão.
     */
    public static function jaViu(int $usuarioId): bool
    {
        $v = self::vigente();
        if ($v === null || $usuarioId <= 0) {
            return true;
        }

        try {
            $ler = new AdmsRead();
            $ler->fullRead(
                'SELECT usuario_id FROM adms_novidades_vistas
                  WHERE adms_versao_id = :v AND usuario_id = :u LIMIT :limit',
                'v=' . (int) $v['id'] . '&u=' . $usuarioId . '&limit=1'
            );

            return !empty($ler->getResultado());
        } catch (\Throwable $e) {
            /* ⚠️ Falhando, responde "já viu". A alternativa seria mostrar a
               faixa de novidades a cada carregamento enquanto o defeito durar —
               e uma faixa que não some ensina a ignorar faixas. */
            error_log('AdmsVersao: nao consegui ler adms_novidades_vistas -- ' . $e->getMessage());

            return true;
        }
    }

    /** Marca as novidades da versão vigente como lidas por esta pessoa. */
    public static function marcarVisto(int $usuarioId): bool
    {
        $v = self::vigente();
        if ($v === null || $usuarioId <= 0) {
            return false;
        }

        try {
            $cad = new AdmsCreate();
            /* ⓘ A chave primária é (versão, usuário): uma segunda gravação é
               recusada pelo banco, e isso basta como idempotência. */
            $cad->exeCreate('adms_novidades_vistas', [
                'adms_versao_id' => (int) $v['id'],
                'usuario_id' => $usuarioId,
            ]);

            return true;
        } catch (\Throwable $e) {
            error_log('AdmsVersao: nao consegui marcar visto -- ' . $e->getMessage());

            return false;
        }
    }
}
