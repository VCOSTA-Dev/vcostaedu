<?php

namespace App\adms\Models\helper;

/* ⚠️ AS DUAS GUARDAS -- ver P-25. Este arquivo e' espelho entre painel/ e
   portal/, e cada aplicativo define a sua constante. Exigir so' uma mata o
   arquivo no outro lado, calado, com um redirecionamento. */
if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsSalvarLog
{

    private $dados;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function salvarLogs($pagina, $codigo, $acao, $descricao)
    {
        $this->dados['descricao'] = (string) $descricao;
        $this->dados['pagina_id'] = (int) $pagina;
        $this->dados['codigo'] = (int) $codigo;
        $this->dados['acao'] = (int) $acao;
        $this->dados['usuario_id'] = (isset($_SESSION['colUsuario_id']) ? $_SESSION['colUsuario_id'] : 0);
        $this->dados['usuario_ip'] = \Core\Seguranca::ipCliente(); // E8 (D-236)
        $addEstadoCivil = new \App\adms\Models\helper\AdmsCreate();
        $addEstadoCivil->exeCreate("adms_logs", $this->dados);
        if($addEstadoCivil->getResultado()){
            $this->resultado = true;
        }else{
            $this->resultado = false; 
        }
    }

}
?>