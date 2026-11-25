<?php

namespace App\adms\Models\atividades;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsEditarAtividade
{
    private $dados;
    private $arquivo;
    private $alunos;
    private $dadosAtividade;
    private $dadosNotas;
    private $dadosId;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function visAtividade($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $verInfoDisc = new \App\adms\Models\helper\AdmsRead();
        $verInfoDisc->fullRead("SELECT id, nomeAtividade, descricao, date(data_inicial) as data_inicial, date(data_final) as data_final, tipo, peso, componente_codigo, entrega_arquivo, rm_prof_mat_id, situacao FROM rm_disciplina_atividade WHERE id =:id LIMIT :limit", "id={$this->dadosId}&limit=1");
        $this->resultado = $verInfoDisc->getResultado();
        return $this->resultado;
    }
    
    public function editAtividadeNota(array $dados)
    {
        $this->dados = $dados;
        unset($this->dados['parent']); // E4: sem bimestre
        $this->arquivo = $this->dados['documentos'] ?? null;
        unset($this->dados['documentos']);
        if (!AdmsCadastrarAtividade::tipoAtivo((int) ($this->dados['tipo'] ?? 0))) {
            $this->resultado = false;
            return;
        }
        if (!AdmsCadastrarAtividade::componenteOk($this->dados)) { // 1.13.0 (D-394)
            $this->resultado = false;
            return;
        }
        $this->altAtividade();
    }

    private function uploadAnexo()
    {

    }

    private function altAtividade()
    {
        $this->dados['usuario_mod'] = $_SESSION['colUsuario_id'];
        $this->dados['modified'] = date("Y-m-d H:i:s");
        $altDisci = new \App\adms\Models\helper\AdmsUpdate();
        $altDisci->exeUpdate("rm_disciplina_atividade", $this->dados, "WHERE id=:id", "id={$this->dados['id']}");
        if($altDisci->getResultado()){
            $this->atualizarNotas();
            $this->logCodigo = 200;
            $this->logDescricao = "Atividade atualizada com sucesso! id={$this->dados['id']}&nome={$this->dados['nomeAtividade']}&tipo={$this->dados['tipo']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Atividade atualizada com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível editar a Atividade! id={$this->dados['id']}&nome={$this->dados['nomeAtividade']}&tipo={$this->dados['tipo']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: ao tentar editar a Atividade!</div>";
            $this->resultado = false;
        }
    }

    private function atualizarNotas()
    {
        $this->dadosNotas['atividade_id'] = $this->dados['id'];
        $this->dadosNotas['tipo'] = $this->dados['tipo'];
        $this->dadosNotas['usuario_mod'] = $_SESSION['colUsuario_id'];
        $this->dadosNotas['modified'] = date("Y-m-d H:i:s");
        $altNotasDisc = new \App\adms\Models\helper\AdmsUpdate();
        $altNotasDisc->exeUpdate("rm_disciplina_atividade_notas", $this->dadosNotas, "WHERE atividade_id =:atividade", "atividade={$this->dadosNotas['atividade_id']}");
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(112, $this->logCodigo, 3, $this->logDescricao);
    }

    public function listEdicao()
    {
        $listTipos = new \App\adms\Models\helper\AdmsRead();
        $listTipos->fullRead("SELECT id, nome FROM rm_disciplina_atividade_tipo WHERE situacao=:sit", "sit=1");
        $this->resultado['tipos'] = $listTipos->getResultado();

        return $this->resultado;
    }

}