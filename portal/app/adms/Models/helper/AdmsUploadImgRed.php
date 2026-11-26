<?php

namespace App\adms\Models\helper;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsUploadImgRed
{
    private $dadosImagem;
    private $diretorio;
    private $nomeImg;
    private $resultado;
    private $imagem;
    private $largura;
    private $altura;
    private $imgRedimens;
            
    function getResultado()
    {
        return $this->resultado;
    }

        public function uploadImagem(array $imagem, $diretorio, $nomeImg, $largura, $altura)
    {
        $this->dadosImagem = $imagem;
        $this->diretorio = $diretorio;
        $this->nomeImg = $nomeImg;
        $this->largura = $largura;
        $this->altura = $altura;
        $this->validarImagem();
        if($this->imagem){
            $this->resultado = true;
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger'>Erro: A extensão da imagem é inválida. Selecione um imagem JPEG ou PNG!</div>";
            $this->resultado = false;
        }
    }
    
    /**
     * Onda 6, Etapa 6 (docs/SEGURANCA_REVISAO.md): o tipo vem do CONTEÚDO (getimagesize), não do que o navegador diz, e
     * o nome gravado precisa ter a extensão desse tipo (.jpg/.jpeg ou .png) — um "foto.php" com uma imagem dentro é
     * recusado. A imagem é redesenhada (só os pixels ficam) e gravada no formato do tipo real.
     */
    private function validarImagem()
    {
        $this->imagem = null;
        $info = @getimagesize((string) ($this->dadosImagem['tmp_name'] ?? ''));
        $tipo = $info[2] ?? 0;
        $ext = strtolower(pathinfo((string) $this->nomeImg, PATHINFO_EXTENSION));
        if ($tipo === IMAGETYPE_JPEG && in_array($ext, ['jpg', 'jpeg'], true)) {
            $this->imagem = @imagecreatefromjpeg($this->dadosImagem['tmp_name']);
            if ($this->imagem) {
                $this->redimensImg();
                $this->valdiretorio();
                imagejpeg($this->imgRedimens, $this->diretorio . $this->nomeImg);
            }
        } elseif ($tipo === IMAGETYPE_PNG && $ext === 'png') {
            $this->imagem = @imagecreatefrompng($this->dadosImagem['tmp_name']);
            if ($this->imagem) {
                $this->redimensImg();
                $this->valdiretorio();
                imagepng($this->imgRedimens, $this->diretorio . $this->nomeImg);
            }
        }
    }

    private function valdiretorio()
    {
        if(!file_exists($this->diretorio) && !is_dir($this->diretorio)){
            mkdir($this->diretorio, 0755, true); // D-127 (1.102.1, C6.2): com as pastas de cima, se faltarem
        }
    }
    
    private function redimensImg()
    {
        $largura_original = imagesx($this->imagem);
        $altura_original = imagesy($this->imagem);
        
        $this->imgRedimens = imagecreatetruecolor($this->largura, $this->altura);
        
        imagecopyresampled($this->imgRedimens, $this->imagem, 0, 0, 0, 0, $this->largura, $this->altura, $largura_original, $altura_original);
    }
}