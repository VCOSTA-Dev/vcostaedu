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
        /* Fechamento (D-106): a página que não vale neste tipo de instituição (no curso livre, o boletim, o horário, o
           atestado…) não abre: quem entrou volta à Página Inicial, sem mensagem; quem não entrou segue para a entrada */
        if ($this->paginas && !\App\adms\Models\helper\AdmsTipoPortal::paginaLigada((int) ($this->paginas[0]['id'] ?? 0))) {
            if (!empty($_SESSION['academico_id'])) {
                header('Location: ' . URLACAD . 'home/index');
                exit;
            }
            $this->paginas = [];
        }
        if ($this->paginas) {
            extract($this->paginas[0]);
            $this->classe = "\\App\\{$tipo_tpg}\\Controllers\\" . $this->urlController;
            if (class_exists($this->classe)) {
                $this->carregarMetodo();
            } else {
                $this->voltarAoPadrao();
            }
        } elseif ($this->paginaNovaSemMigration()) {
            \App\adms\Models\helper\AdmsAtualizacaoPendente::responderPendente();
        } elseif ($this->semPermissao()) {
            $this->naoPermitido();
        } else {
            $this->voltarAoPadrao();
        }

    }

    /**
     * Onda 2, Etapa 1: o controller existe nos arquivos, mas a página dele ainda não está no banco e há migration
     * pendente — os arquivos novos chegaram antes da atualização do banco. Quem entrou recebe o aviso de "atualização
     * pendente" (nunca um erro do PHP); quem não entrou segue para o login, como em qualquer rota desconhecida.
     */
    private function paginaNovaSemMigration(): bool
    {
        if (empty($_SESSION['academico_id']) || !class_exists("\\App\\adms\\Controllers\\" . $this->urlController)) {
            return false;
        }

        return \App\adms\Models\helper\AdmsAtualizacaoPendente::haPendente()
            && !\App\adms\Models\helper\AdmsAtualizacaoPendente::paginaExiste((string) $this->urlController, (string) $this->urlMetodo);
    }

    /**
     * Correção da escola, Parte 4 (B18; D-103): quem entrou e pede uma página que EXISTE, mas que não é do perfil dele (o
     * aluno num endereço do portal da família, por exemplo), recebe "não permitido" (403) — antes, a tela de entrada,
     * como se a sessão tivesse caído. Nenhum dado vazava; a resposta é que estava errada.
     */
    private function semPermissao(): bool
    {
        return !empty($_SESSION['academico_id'])
            && \App\adms\Models\helper\AdmsAtualizacaoPendente::paginaExiste((string) $this->urlController, (string) $this->urlMetodo);
    }

    private function naoPermitido(): void
    {
        if (!headers_sent()) {
            http_response_code(403);
        }
        $dados = ['menus' => class_exists(\App\adms\Models\AdmsMenus::class) ? (new \App\adms\Models\AdmsMenus())->itemMenu() : []];
        (new \Core\ConfigView('adms/Views/erro/naoPermitido', $dados))->renderizar('tabler');
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
