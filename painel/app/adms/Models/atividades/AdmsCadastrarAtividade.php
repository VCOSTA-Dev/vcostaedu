<?php

namespace App\adms\Models\atividades;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsCadastrarAtividade
{
    private $dados;
    private $arquivo;
    private $alunos;
    private $dadosAtividade;
    private $dadosId;
    private $logCodigo;
    private $logDescricao;
    private $tipo;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function visDisciplina($dadosId, $tipo)
    {
        $this->dadosId = (int) $dadosId;
        $this->tipo = (int) $tipo;
        $verInfoCurso = new \App\adms\Models\helper\AdmsRead();
        $verInfoCurso->fullRead("SELECT m.id, m.periodo, m.codmat, m.codtur, m.materia, m.bimestre, m.ano, m.codfilial, m.tipo FROM rm_professores_materias m WHERE m.id =:id LIMIT :limit", "id={$this->dadosId}&limit=1");
        $this->resultado['info'] = $verInfoCurso->getResultado();

        $verGrupoAtividade = new \App\adms\Models\helper\AdmsRead();
        $verGrupoAtividade->fullRead("SELECT id, nomeAtividade, grupo FROM rm_disciplina_atividade_tipo WHERE id =:ti LIMIT :limit", "ti={$this->tipo}&limit=1");
        $grupos = $verGrupoAtividade->getResultado();
        $this->resultado['exercicios'] = $grupos;
        if(($grupos[0]['grupo'] == 2) || ($grupos[0]['grupo'] == 3)){
            $verlistaAtividades = new \App\adms\Models\helper\AdmsRead();
            $verlistaAtividades->fullRead("SELECT id, nome, nomeAtividade FROM rm_disciplina_atividade_tipo WHERE grupo in (2,3) AND (situacao = :sit OR (id = 6 AND :acr = 1))",
                "sit=1&acr=" . (\App\adms\Models\notas\AdmsRegraMedia::permiteAcrescimo((int) $this->dadosId) ? 1 : 0)); // Onda 4, Etapa 5
            $this->resultado['tipos'] = $verlistaAtividades->getResultado();
        } else {
            $verlistaAtividades = new \App\adms\Models\helper\AdmsRead();
            $verlistaAtividades->fullRead("SELECT id, nome, nomeAtividade FROM rm_disciplina_atividade_tipo WHERE grupo =:grupo AND situacao = :sit", "grupo={$grupos[0]['grupo']}&sit=1");
            $this->resultado['tipos'] = $verlistaAtividades->getResultado();
        }
        return $this->resultado;
    }
    

    public function cadAtividadeNota(array $dados)
    {
        $this->dados = $dados;
        $this->arquivo = $this->dados['documentos'] ?? null;
        unset($this->dados['documentos']);
        unset($this->dados['parent']); // E4: sem bimestre
        /* E4: etapa única — a atividade não pertence a bimestre (sai o limite de
           "acréscimo por bimestre"; o tipo Acréscimo está desativado). */
        if (!self::tipoAtivo((int) ($this->dados['tipo'] ?? 0), (int) ($this->dados['rm_prof_mat_id'] ?? 0))) { // Onda 4, Etapa 5: o acréscimo, pela regra da oferta
            $this->resultado = false;
            return;
        }
        if (!self::componenteOk($this->dados)) { // 1.13.0 (D-394)
            $this->resultado = false;
            return;
        }
        /* 1.14.0 (E9, D-414): a atividade e as linhas de nota dos alunos numa TRANSAÇÃO (antes, a falha numa linha de nota
           passava calada, com "Atividade criada com sucesso!") */
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () { $this->addAtividade(); return $this->resultado ? true : false; });
        if (!$ok) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: a atividade NÃO foi criada — nada mudou (" . htmlspecialchars($T::ultimoErro(), ENT_QUOTES, 'UTF-8') . ').</div>';
            $this->resultado = false;
        }
    }

    /**
     * E5: só tipo de atividade ATIVO (Acréscimo, Substitutiva e Recuperação estão desativados:
     * exame e recuperação são pelo Moodle, fora da v1). Recusa também o POST direto.
     */
    public static function tipoAtivo(int $tipo, int $ofertaId = 0): bool
    {
        /* Onda 4, Etapa 5: o acréscimo (tipo 6) vale quando a regra da oferta o permite */
        if ($tipo === 6 && $ofertaId > 0 && \App\adms\Models\notas\AdmsRegraMedia::permiteAcrescimo($ofertaId)) {
            return true;
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id FROM rm_disciplina_atividade_tipo WHERE id = :t AND situacao = :s LIMIT :limit", "t={$tipo}&s=1&limit=1");
        if ($ler->getResultado()) {
            return true;
        }
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: tipo de atividade desativado ou inexistente (Acréscimo, Substitutiva e Recuperação estão desativados; exame e recuperação são feitos no Moodle).</div>";
        return false;
    }


    /** 1.13.0 (D-394): componente e peso conferidos contra a regra que vale para a oferta (também no POST direto). */
    public static function componenteOk(array &$dados): bool
    {
        /* E10 (D-423): + o máximo do componente; na edição (`id`), mover para um componente cheio é recusado */
        $erros = \App\adms\Models\notas\AdmsRegraMedia::validarAtividade((int) ($dados['rm_prof_mat_id'] ?? 0), $dados, (int) ($dados['id'] ?? 0));
        if ($erros) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible' id='atividadeRecusada'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: "
                . htmlspecialchars(implode(' ', $erros), ENT_QUOTES, 'UTF-8') . '</div>';
            return false;
        }
        return true;
    }

    private function uploadAnexo()
    {

    }

    private function addAtividade()
    {
        $this->dados['usuario_cre'] = $_SESSION['colUsuario_id'];
        $this->dados['situacao'] = 1;
        $addItMenu = new \App\adms\Models\helper\AdmsCreate();
        $addItMenu->exeCreate("rm_disciplina_atividade", $this->dados);
        if($addItMenu->getResultado()){
            $identificador = $addItMenu->getResultado();
            $this->logCodigo = 200;
            $this->logDescricao = "Atividade criada com sucesso! id={$identificador}&nome={$this->dados['nomeAtividade']}&tipo={$this->dados['tipo']}";
            $this->salvarLog();
            $this->dadosAtividade['atividade_id'] = $identificador;
            $this->listaAlunos();
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Erro: não foi possível criar a atividade! nome={$this->dados['nomeAtividade']}&tipo={$this->dados['tipo']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível criar a atividade!</div>";
            $this->resultado = false; 
        }
    }

    private function listaAlunos()
    {
        $verAlunos = new \App\adms\Models\helperApi\AdmsAlunosDisciplinas;
        $this->alunos = $verAlunos->disciplinaAlunos($this->dados['rm_prof_mat_id']);
        if(isset($this->alunos) && !empty($this->alunos)){
            $this->geraLista();
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Erro: Não existem alunos para criar na disciplina! rm_prof_mat_id={$this->dados['rm_prof_mat_id']}&tipo={$this->dados['tipo']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Atividade criada com sucesso!</div>";
            $this->resultado = true;
        }
    }

    private function geraLista()
    {
        foreach($this->alunos as $al){
            $this->dadosAtividade['rm'] = $al['rm'];
            $this->dadosAtividade['ordenacao'] = $al['ordem'];
            $this->dadosAtividade['nome_aluno'] = $al['nome_aluno'];
            $this->criaLista();
        }
    }

    private function criaLista()
    {
        $this->dadosAtividade['rm_prof_mat_id'] = $this->dados['rm_prof_mat_id'];
        $this->dadosAtividade['tipo'] = $this->dados['tipo'];
        $this->dadosAtividade['peso'] = $this->dados['peso'];
        $this->dadosAtividade['alteracao'] = 1;
        $this->dadosAtividade['situacao'] = 1;
        $this->dadosAtividade['usuario_cre'] = $_SESSION['colUsuario_id'];
        $this->dadosAtividade['created'] = date("Y-m-d H:i:s");
        $addListAlunos = new \App\adms\Models\helper\AdmsCreate();
        $addListAlunos->exeCreate("rm_disciplina_atividade_notas", $this->dadosAtividade);
        \Core\Transacao::exigir($addListAlunos->getResultado(), 'linha de nota do RA ' . $this->dadosAtividade['rm']); // 1.14.0 (D-414)
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Atividade criada com sucesso!</div>";
        $this->resultado = true;
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(110, $this->logCodigo, 2, $this->logDescricao);
    }

}