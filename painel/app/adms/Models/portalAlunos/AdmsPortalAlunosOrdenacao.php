<?php

namespace App\adms\Models\portalAlunos;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsPortalAlunosOrdenacao
{
    private $dados;
    private $dadosMaiores;
    private $dadosNovos;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function visOrdenacao(array $dados)
    {
        $this->dados = $dados;
        $this->resultado['link'] = $this->dados['pagina'];
        $this->verOrdenacaoAlteracao();

    }

    private function verOrdenacaoAlteracao()
    {
        $verOrdemPortal = new \App\adms\Models\helper\AdmsRead();
        $verOrdemPortal->fullRead("SELECT rm, curso_id, periodo, ano, ordem FROM portal_disciplinas_alunos WHERE curso_id = :curs AND periodo =:per AND ano=:an AND ordem >=:ord AND rm<>:aluno ORDER BY ordem ASC", "curs={$this->dados['curso_id']}&per=" . urlencode((string) $this->dados['periodo']) . "&an={$this->dados['ano']}&ord={$this->dados['ordem']}&aluno=" . urlencode((string) $this->dados['rm']) . "");
        $this->dadosMaiores = $verOrdemPortal->getResultado();
        if(isset($this->dadosMaiores[0]['rm']) && !empty($this->dadosMaiores[0]['rm'])){
            $this->alunoOrdemNova();
            $this->alunosAlterados();
        }else{
            $this->alunoOrdemNova();
        }
    }

    private function alunoOrdemNova()
    {
        $this->dadosNovos['rm'] = $this->dados['rm'];
        $this->dadosNovos['curso_id'] = $this->dados['curso_id'];
        $this->dadosNovos['periodo'] = $this->dados['periodo'];
        $this->dadosNovos['ordem'] = $this->dados['ordem'];
        $this->dadosNovos['ano'] = $this->dados['ano'];
        $this->alteraOrdem();
    }

    private function alunosAlterados()
    {
        
        $ultimaOrdem = $this->dados['ordem'];
        foreach($this->dadosMaiores as $this->dadosNovos){
            $this->dadosNovos['ordem'] = $this->dadosNovos['ordem'] + 1;
            if(($this->dadosNovos['ordem'] - $ultimaOrdem) == 1){
                $ultimaOrdem = $this->dadosNovos['ordem'];
                $this->alteraOrdem();
            }else{
                $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Nº de chamada do(a) Aluno(a) alterado com sucesso!</div>";
                $this->resultado['situacao'] = true; 
            }
        }
    }

    private function alteraOrdem()
    {
        $this->dadosNovos['usuario_mod'] = $_SESSION['colUsuario_id'];
        $this->dadosNovos['modified'] = date("Y-m-d H:i:s");
        $upOrdem = new \App\adms\Models\helper\AdmsUpdate();
        $upOrdem->exeUpdate("portal_disciplinas_alunos", $this->dadosNovos, "WHERE rm=:rm AND curso_id=:curs AND periodo=:per AND ano=:an", "rm=" . urlencode((string) $this->dadosNovos['rm']) . "&curs={$this->dadosNovos['curso_id']}&per=" . urlencode((string) $this->dadosNovos['periodo']) . "&an={$this->dadosNovos['ano']}");
        if($upOrdem->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Nº de chamada do(a) Aluno(a) alterado com sucesso!--".json_encode($this->dadosNovos, true);
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Nº de chamada do(a) Aluno(a) alterado com sucesso!</div>";
            $this->resultado['situacao'] = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível alterar o nº de chamada do(a) aluno(a)!--".json_encode($this->dadosNovos, true);
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível alterar o nº de chamada do(a) aluno(a)!</div>";
            $this->resultado['situacao'] = false;
        }

    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(250, $this->logCodigo, 3, $this->logDescricao);
    }
}