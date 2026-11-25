<?php

namespace Core;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class CarregarPgAdm
{
    private $urlController;
    private $urlMetodo;
    private $urlParametro;
    private $classe;

    public function carregarPg($urlController = null, $urlMetodo = null, $urlParametro = null)
    {
        $this->urlController = $urlController;
        $this->urlMetodo = $urlMetodo;
        $this->urlParametro = $urlParametro;
        $this->pgPublica();
        $this->classe = "\\App\\adms\\Controllers\\" . $this->urlController;
        if(class_exists($this->classe)){
            $this->carregarMetodo();
        }else{
            $this->urlController = $this->slugController(CONTROLLER);
            $this->urlMetodo = $this->slugMetodo(METODO);
            $this->urlParametro = "";
            $this->classe = "\\App\\adms\\Controllers\\" . $this->urlController;
            $this->carregarMetodo();
        }

    }

    private function carregarMetodo()
    {
        $classCarregar = new $this->classe();
        if(method_exists($classCarregar, $this->urlMetodo)){
            $classCarregar->{$this->urlMetodo}();
        }else{
            die("Erro: Não existe");
        }
    }

    private function pgPublica()
    {
        $listarPg = new \App\adms\Models\AdmsPaginas();
        $this->paginas = $listarPg->listarPaginas($this->urlController, $this->urlMetodo);
        var_dump($this->paginas);

        // $this->pgPublica = ["Login"];
        // if(in_array($this->urlController))
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
}
