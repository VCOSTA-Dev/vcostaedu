<?php

namespace Core;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 2) . '/core/Requisicao.php';

class ConfigController extends Config
{
    private $url;
    private $urlConjunto;
    private $urlController;
    private $urlMetodo;
    private $urlParametro;
    private $slugController;
    private $slugMetodo;
    private $urlLimpa;
    private $paginas;
    private $classe;
    private $format;

    public function __construct()
    {
        $this->configAdm();
        Requisicao::definir(); // 1.9.1: URL, URLACAD, URLADM e HTTPS_ATIVO a partir dos parâmetros da Config
        if(!empty(filter_input(INPUT_GET, 'url', FILTER_DEFAULT))){
            $this->url = filter_input(INPUT_GET, 'url', FILTER_DEFAULT);
            $this->url = $this->limparUrl($this->url);
            $this->urlConjunto = explode("/", $this->url);
            if(isset($this->urlConjunto[0])){
                $this->urlController = $this->slugController($this->urlConjunto[0]);
            }else{
                $this->urlController = $this->slugController(CONTROLLER);
            }
            if(isset($this->urlConjunto[1])){
                $this->urlMetodo = $this->slugMetodo($this->urlConjunto[1]);
            }else{
                $this->urlController = $this->slugController(CONTROLLER);
                $this->urlMetodo = $this->slugMetodo(METODO);
            }
            if(isset($this->urlConjunto[2])){
                $this->urlParametro = $this->urlConjunto[2];
            }else{
                $this->urlParametro = "";
            }
        }else{
            $this->urlController = $this->slugController(CONTROLLER);
            $this->urlMetodo = $this->slugMetodo(METODO);
            $this->urlParametro = "";
        }

        // echo "Controller: {$this->urlController} <br>";
        // echo "Metodo: {$this->urlMetodo} <br>";
        // echo "Parametro: {$this->urlParametro} <br>";
    }

    private function slugController($slugController) {
        //Converter para minusculo
        $this->slugController = strtolower($slugController);
        //Converter o traço para espaço em braco
        $this->slugController = str_replace("-", " ", $this->slugController);
        //Converter a primeira letra de cada palavra para maiusculo
        $this->slugController = ucwords($this->slugController);
        //Retirar o espaço em branco
        $this->slugController = str_replace(" ", "", $this->slugController);
        return $this->slugController;
    }

    private function slugMetodo($slugMetodo)
    {
        $this->slugMetodo = $this->slugController($slugMetodo);
        $this->slugMetodo = lcfirst($this->slugMetodo);
        return $this->slugMetodo;
    }

    private function limparUrl($url)
    {
        $this->urlLimpa = strip_tags($url);
        $this->urlLimpa = trim($this->urlLimpa);
        $this->urlLimpa = rtrim($this->urlLimpa, "/");

        $this->format['a'] = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜüÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿRr"!@#$%&*()_-+={[}]?;:.,\\\'<>°ºª ';
        $this->format['b'] = 'aaaaaaaceeeeiiiidnoooooouuuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr--------------------------------';
        $this->urlLimpa = strtr(utf8_decode($this->urlLimpa), utf8_decode($this->format['a']), $this->format['b']);
        
        return $this->urlLimpa;
    }

    public function carregar()
    {
        $listarPg = new \App\adms\Models\AdmsPaginas();
        $this->paginas = $listarPg->listarPaginas($this->urlController, $this->urlMetodo);
        if ($this->paginas) {
            extract($this->paginas[0]);
            $this->classe = "\\App\\{$tipo_tpg}\\Controllers\\" . $this->urlController;
            if (class_exists($this->classe)) {
                /* Onda 2, Etapa 2: o parâmetro da rota dentro do escopo do usuário (o coordenador, os cursos dele);
                   Onda 3, Etapa 1: o professor, as ofertas dele — e, para gravar, só as dele */
                if (!\App\adms\Models\helper\AdmsEscopo::rota($this->paginas[0]['escopo'] ?? null, $this->urlParametro, (int) ($this->paginas[0]['grava'] ?? 0) === 1)) {
                    \App\adms\Models\helper\AdmsEscopo::recusar();
                    return;
                }
                $this->carregarMetodo();
            } else {
                $this->voltarAoPadrao();
            }
        } elseif ($this->paginaNovaSemMigration()) {
            \App\adms\Models\helper\AdmsAtualizacaoPendente::responderPendente();
        } elseif ($this->semPermissao()) {
            $this->responderSemAcesso();
        } else {
            $this->voltarAoPadrao();
        }

    }

    /**
     * Onda 8, correção, Parte 3.1 (D-76): quem ENTROU e abre uma página que existe, mas que o perfil não abre (sem a
     * permissão, ou com a trava do código: o "só consulta" numa página que grava). Antes, a resposta era a tela de
     * entrada, como se a pessoa tivesse sido desconectada (a sessão continuava). Quem não entrou continua indo ao login.
     */
    private function semPermissao(): bool
    {
        return !empty($_SESSION['colUsuario_id'])
            && \App\adms\Models\helper\AdmsAtualizacaoPendente::paginaExiste((string) $this->urlController, (string) $this->urlMetodo);
    }

    /** A página "Você não tem acesso a esta tela" (HTTP 403), com o caminho de volta; para o pedido em JSON, a recusa em JSON. */
    private function responderSemAcesso(): void
    {
        if (!headers_sent()) {
            http_response_code(403);
        }
        $texto = 'O seu perfil não tem acesso a esta ação.';
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
            header('Content-Type: application/json; charset=utf-8');
            /* "erro" = false é a recusa no formato herdado da gravação da nota; "ok" = false, no das telas novas */
            echo json_encode(['ok' => false, 'erro' => false, 'msg' => $texto, 'motivo' => $texto], JSON_UNESCAPED_UNICODE);
            return;
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead('SELECT id, nome_pagina FROM adms_paginas WHERE controller = :c AND metodo = :m LIMIT :limit', "c={$this->urlController}&m={$this->urlMetodo}&limit=1");
        $nome = (string) ($ler->getResultado()[0]['nome_pagina'] ?? '');
        /* 1.103.1 (D-134): a página é do financeiro e o financeiro está desligado — a recusa é a mesma, o texto diz o
           motivo; o caminho para ligar, só para quem abre Configurações → Recursos da instituição */
        $idPagina = (int) ($ler->getResultado()[0]['id'] ?? 0);
        $financeiroDesligado = $idPagina > 0 && (\App\adms\Models\helper\AdmsTipoInstituicao::recursos()[$idPagina] ?? null) === 'financeiro'
            && !\App\adms\Models\helper\AdmsTipoInstituicao::recursoLigado('financeiro');
        $podeLigar = $financeiroDesligado
            && !empty((new \App\adms\Models\AdmsBotao())->valBotao(['ligar' => ['menu_controller' => 'recursos-livre', 'menu_metodo' => 'index']])['ligar']);
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead('SELECT nome FROM adms_niveis_acessos WHERE id = :n LIMIT :limit', 'n=' . (int) ($_SESSION['colUsuario_nivel'] ?? 0) . '&limit=1');
        $perfil = (string) ($ler->getResultado()[0]['nome'] ?? '');
        /* o caminho de volta: a página de onde a pessoa veio, se é deste sistema e não é esta mesma */
        $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $aqui = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $voltar = str_starts_with($ref, URLADM) && (string) parse_url($ref, PHP_URL_PATH) !== $aqui ? $ref : '';
        $dados = ['menus' => (new \App\adms\Models\AdmsMenus())->itemMenu(), 'paginaAtiva' => 0, 'nomeTela' => $nome, 'perfil' => $perfil, 'voltar' => $voltar,
            'tela' => strtolower((string) $this->urlController) . '/' . (string) $this->urlMetodo,
            'financeiroDesligado' => $financeiroDesligado, 'podeLigar' => $podeLigar];
        (new \Core\ConfigView('adms/Views/erro/semAcesso', $dados))->renderizar('tabler');
    }

    /**
     * Onda 2, Etapa 1: o controller existe nos arquivos, mas a página dele ainda não está no banco e há migration
     * pendente — os arquivos novos chegaram antes da atualização do banco. Quem entrou recebe o aviso de "atualização
     * pendente" (nunca um erro do PHP); quem não entrou segue para o login, como em qualquer rota desconhecida.
     */
    private function paginaNovaSemMigration(): bool
    {
        if (empty($_SESSION['colUsuario_id']) || !class_exists("\\App\\adms\\Controllers\\" . $this->urlController)) {
            return false;
        }

        return \App\adms\Models\helper\AdmsAtualizacaoPendente::haPendente()
            && !\App\adms\Models\helper\AdmsAtualizacaoPendente::paginaExiste((string) $this->urlController, (string) $this->urlMetodo);
    }

    private function carregarMetodo()
    {
        $classeCarregar = new $this->classe;
        if(method_exists($classeCarregar, $this->urlMetodo)){
            if($this->urlParametro !== null){
                $classeCarregar->{$this->urlMetodo}($this->urlParametro);
            }else{
                $classeCarregar->{$this->urlMetodo}();
            }
        }else{
            $this->voltarAoPadrao();
        }
    }

    /**
     * Rota não encontrada: volta para a página padrão (login). E8 (D-246): se a própria página padrão não é
     * encontrada (banco sem tabelas, instalação incompleta), responde 503 e PARA — antes, carregar() chamava a
     * si mesmo sem fim, escrevendo no log a cada volta.
     */
    private function voltarAoPadrao()
    {
        $c = $this->slugController(CONTROLLER);
        $m = $this->slugMetodo(METODO);
        if ($this->urlController === $c && $this->urlMetodo === $m) {
            error_log('[vcostaedu][instalacao] a página padrão (' . CONTROLLER . '/' . METODO . ') não foi encontrada: banco sem as tabelas ou sem as rotas');
            if (!headers_sent()) {
                http_response_code(503);
                header('Content-Type: text/html; charset=utf-8');
            }
            exit('<!DOCTYPE html><html lang="pt-br"><meta charset="utf-8"><title>Sistema indisponível</title><p>O sistema está indisponível: a instalação não está completa. Avise o suporte.</p></html>');
        }
        $this->urlController = $c;
        $this->urlMetodo = $m;
        $this->carregar();
    }

}
