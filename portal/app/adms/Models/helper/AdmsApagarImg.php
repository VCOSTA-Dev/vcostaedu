<?php

namespace App\adms\Models\helper;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsApagarImg
{

    private $nomeImg;
    private $diretorio;

    public function apagarImg($nomeImg, $diretorio = null)
    {
        $this->nomeImg = (string) $nomeImg;
        $this->diretorio = (string) $diretorio;
        $this->excluirImagem();
        if(!empty($this->diretorio)){
            $this->excluirdiretorio();
        }
    }

    private function excluirImagem()
    {
        if(file_exists($this->nomeImg)){
            unlink($this->nomeImg);
        }
    }
    
    private function excluirdiretorio()
    {
        if(file_exists($this->diretorio)){
            rmdir($this->diretorio);
        }
    }

}