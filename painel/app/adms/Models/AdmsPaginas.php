<?php

namespace App\adms\Models;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsPaginas
{
    private $resultado;
    private $urlController;
    private $urlMetodo;

    public function listarPaginas($urlController = null, $urlMetodo = null)
    {
        /* VCOSTAEDU (Etapa 2): a sessão é conferida contra o banco a cada requisição, como o sessaoAindaVale do
           vcostaedu — usuário apagado, inativado ou bloqueado, ou com o nível trocado, sai na requisição seguinte */
        self::conferirSessao();
        if(!isset($_SESSION['colUsuario_nivel'])){
            $_SESSION['colUsuario_nivel'] = null;
        }
        $this->urlController = (string) $urlController;
        $this->urlMetodo = (string) $urlMetodo;
        /* 1.18.4 (D-503): com a marca "Atualizar Dados", só a tela de atualização e o Sair (o resto é redirecionado) */
        \App\adms\Models\helper\AdmsAtualizarDados::portao($this->urlController, $this->urlMetodo);
        $listar = new \App\adms\Models\helper\AdmsRead();
        $listar->fullRead("SELECT pg.id, pg.lib_pub, pg.controller,
                tpg.tipo tipo_tpg
                FROM adms_paginas pg
                INNER JOIN adms_tps_pgs tpg ON tpg.id=pg.adms_tps_pg_id
                LEFT JOIN adms_nivacs_pgs nivpg ON nivpg.adms_pagina_id=pg.id AND nivpg.adms_niveis_acesso_id =:adms_niveis_acesso_id
                WHERE (pg.controller =:controller
                AND pg.metodo =:metodo) AND ((pg.lib_pub =:lib_pub) OR (nivpg.permissao =:permissao))
                LIMIT :limit", "adms_niveis_acesso_id={$_SESSION['colUsuario_nivel']}&controller={$this->urlController}&metodo={$this->urlMetodo}&lib_pub=1&permissao=1&limit=1");
        $this->resultado = $listar->getResultado();
        if (!$this->resultado && \App\adms\Models\helper\AdmsAtualizarDados::telaLiberada($this->urlController, $this->urlMetodo)) {
            $listar->fullRead("SELECT pg.id, pg.lib_pub, pg.controller, tpg.tipo tipo_tpg FROM adms_paginas pg INNER JOIN adms_tps_pgs tpg ON tpg.id=pg.adms_tps_pg_id
                WHERE pg.controller =:controller AND pg.metodo =:metodo LIMIT :limit", "controller={$this->urlController}&metodo={$this->urlMetodo}&limit=1");
            $this->resultado = $listar->getResultado();
        }
        /* Onda 2, Etapa 2: a trava no código, além da matriz do banco — o nível "só consulta" (Direção acadêmica,
           Coordenação de curso) não abre página que grava, e só quem tem as ações sensíveis abre a página sensível,
           mesmo que a permissão tenha sido dada pela tela. E a página diz o parâmetro que recebe (escopo), que o
           roteador confere com AdmsEscopo. */
        if ($this->resultado && (int) $this->resultado[0]['lib_pub'] !== 1) {
            $marcas = self::marcasDaPagina((int) $this->resultado[0]['id']);
            if (!self::nivelPode($marcas)) {
                $this->resultado = [];
            } else {
                $this->resultado[0]['escopo'] = $marcas['escopo'];
                $this->resultado[0]['grava'] = $marcas['grava']; // Onda 3, Etapa 1: o escopo distingue ler de gravar
            }
        }
        /* Onda 4, Etapa 1: a página de um módulo desligado (a escola numa instalação de ensino superior, e o contrário) não
           existe para esta instalação — recusada como página sem permissão (docs/ESCOLA.md) */
        if ($this->resultado && !\App\adms\Models\helper\AdmsTipoInstituicao::paginaLigada((int) $this->resultado[0]['id'])) {
            $this->resultado = [];
        }
        /* Onda 2, Etapa 7: A DEPENDÊNCIA RECURSIVA — a página da tela de atualização nasce na migration que a própria tela
           aplica (a 0011). Enquanto ela não está no banco, o Administrador abre a tela assim mesmo, por uma linha
           sintética; sem isso, o aviso de "atualização pendente" levaria a uma tela que a própria pendência esconde. */
        if (!$this->resultado && self::telaDeAtualizacao($this->urlController)) {
            $this->resultado = [['id' => 0, 'lib_pub' => 2, 'controller' => 'Atualizar', 'tipo_tpg' => 'adms', 'escopo' => null]];
        }
        return $this->resultado;
    }

    /** A tela de atualização, ainda sem a página dela no banco, pedida pelo Administrador. */
    private static function telaDeAtualizacao(string $controller): bool
    {
        if ($controller !== 'Atualizar' || !\App\adms\Models\helper\AdmsNivel::ehAdministrador()) {
            return false;
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id FROM adms_paginas WHERE controller = :c LIMIT :limit", 'c=Atualizar&limit=1');
        return empty($ler->getResultado());
    }

    /** As marcas da página (0005): grava, sensível e o escopo do parâmetro. Antes da 0005, nenhuma. */
    public static function marcasDaPagina(int $paginaId): array
    {
        if (!\App\adms\Models\helper\AdmsNivel::comPapeis()) {
            return ['grava' => 0, 'sensivel' => 0, 'escopo' => null];
        }
        /* D-100: a página que grava e que o perfil "só consulta" também abre (a direção reabre a etapa e libera o boletim) */
        $consulta = \App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0081') ? ', consulta_grava' : '';
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT grava, sensivel, escopo{$consulta} FROM adms_paginas WHERE id =:id LIMIT :limit", "id={$paginaId}&limit=1");
        $r = $ler->getResultado()[0] ?? [];
        return ['grava' => (int) ($r['grava'] ?? 0), 'sensivel' => (int) ($r['sensivel'] ?? 0), 'escopo' => ($r['escopo'] ?? '') !== '' ? (string) $r['escopo'] : null,
            'teto' => self::tetos()[$paginaId] ?? null, 'consulta_grava' => (int) ($r['consulta_grava'] ?? 0)];
    }

    /** @var array<int, string>|null */
    private static ?array $tetos = null;

    /**
     * O TETO da ação de cada página (0015, docs/PENTE_FINO.md): o perfil mais baixo que pode receber a ação. Uma
     * consulta por requisição; antes da 0015, nenhum teto (a matriz decide sozinha, como antes).
     * @return array<int, string>
     */
    public static function tetos(): array
    {
        if (self::$tetos !== null) {
            return self::$tetos;
        }
        self::$tetos = [];
        if (!\App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0015')) {
            return self::$tetos;
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT pg.id, a.teto FROM adms_paginas pg INNER JOIN adms_acoes a ON a.id = pg.acao_id");
        foreach ($ler->getResultado() ?: [] as $l) {
            self::$tetos[(int) $l['id']] = (string) $l['teto'];
        }
        return self::$tetos;
    }

    /** O nível da sessão pode abrir uma página com estas marcas? (A matriz já disse que sim.) */
    public static function nivelPode(array $marcas): bool
    {
        if ($marcas['grava'] === 1 && empty($marcas['consulta_grava']) && \App\adms\Models\helper\AdmsNivel::soConsulta()) {
            return false;
        }
        if ($marcas['sensivel'] === 1 && !\App\adms\Models\helper\AdmsNivel::podeSensivel()) {
            return false;
        }
        /* a ação acima do teto do perfil: recusada mesmo que a matriz a tenha dado */
        if (\App\adms\Models\helper\AdmsNivel::abaixoDoTeto($marcas['teto'] ?? null)) {
            return false;
        }
        return true;
    }

    /**
     * Relê a situação e o nível do usuário da sessão. Fica: situação Ativo (1) ou Atualizar Dados (a do portão
     * AdmsAtualizarDados) e o mesmo nível gravado no login. Qualquer outra coisa apaga as chaves de usuário da
     * sessão (as mesmas do logout em Controllers/Login.php), e a rota segue como de quem não entrou.
     */
    private static function conferirSessao(): void
    {
        if (empty($_SESSION['colUsuario_id'])) {
            return;
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        /* o nome do nível (o que o menu mostra) é relido aqui também, junto com a situação e o nível */
        $ler->fullRead("SELECT u.adms_sits_usuario_id, u.adms_niveis_acesso_id, u.nome, n.nome AS nome_nivel FROM adms_usuarios u
            LEFT JOIN adms_niveis_acessos n ON n.id = u.adms_niveis_acesso_id WHERE u.id =:id LIMIT :limit", "id=" . (int) $_SESSION['colUsuario_id'] . "&limit=1");
        $conta = $ler->getResultado();
        $vale = !empty($conta)
            && in_array((int) $conta[0]['adms_sits_usuario_id'], [1, \App\adms\Models\helper\AdmsAtualizarDados::SITUACAO], true)
            && (int) $conta[0]['adms_niveis_acesso_id'] === (int) ($_SESSION['colUsuario_nivel'] ?? 0);
        if (!$vale) {
            unset($_SESSION['colUsuario_id'], $_SESSION['colUsuario_nome'], $_SESSION['colUsuario_email'], $_SESSION['colUsuario_imagem'], $_SESSION['colUsuario_nivel'], $_SESSION['colUsuario_situacao'], $_SESSION['colUsuario_ordem'], $_SESSION['colUsuario_nivel_nome']);
            return;
        }
        $_SESSION['colUsuario_nivel_nome'] = (string) ($conta[0]['nome_nivel'] ?? '');
        $_SESSION['colUsuario_nome'] = (string) ($conta[0]['nome'] ?? ($_SESSION['colUsuario_nome'] ?? '')); // Onda 3, Etapa 5: o nome também
    }

}