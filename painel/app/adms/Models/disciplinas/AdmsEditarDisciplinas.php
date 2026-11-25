<?php

namespace App\adms\Models\disciplinas;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsEditarDisciplinas
{
    private $dados;
    private $dadosId;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function visDisciplina($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $verDisc = new \App\adms\Models\helper\AdmsRead();
        $verDisc->fullRead("SELECT * FROM rm_professores_materias WHERE id=:id LIMIT :limit", "id={$this->dadosId}&limit=1");
        $this->resultado = $verDisc->getResultado();

        return $this->resultado;
    }

    /** A oferta tem alunos enturmados ou atividades? Então a linha da matriz não muda. */
    public static function temUso(int $id): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT (SELECT COUNT(*) FROM portal_disciplinas_alunos WHERE rm_prof_mat_id = :a)
                  + (SELECT COUNT(*) FROM rm_disciplina_atividade WHERE rm_prof_mat_id = :b) AS n",
            "a={$id}&b={$id}"
        );
        return (int) ($ler->getResultado()[0]['n'] ?? 0) > 0;
    }

    public function editDisc(array $dados)
    {
        $id = (int) ($dados['id'] ?? 0);
        $atual = $this->visDisciplina($id);
        if (!$atual) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Erro: oferta não encontrada!</div>";
            $this->resultado = false;
            return;
        }
        $atual = $atual[0];
        if (self::temUso($id) && (int) ($dados['matriz_id'] ?? 0) !== (int) $atual['matriz_id']) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: a oferta já tem alunos ou atividades; a disciplina da matriz não muda.</div>";
            $this->resultado = false;
            return;
        }
        $oferta = AdmsOfertaMatriz::montar($dados, false); // E8d (P-8): editar oferta existente vale mesmo com curso/matéria inativos
        if (is_string($oferta)) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: " . htmlspecialchars($oferta, ENT_QUOTES, 'UTF-8') . "</div>";
            $this->resultado = false;
            return;
        }
        if (AdmsOfertaMatriz::duplicada($oferta['ano'], $oferta['matriz_id'], $oferta['codtur'], $id)) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: já existe oferta desta disciplina da matriz neste ano e nesta turma!</div>";
            $this->resultado = false;
            return;
        }
        if (($atual['frequencia_fonte'] ?? 'lancamento') === 'chamada') {
            unset($oferta['encontros']); // 1.14.0 (D-405): com chamada, o total de encontros é o dos encontros registrados
        } else {
            unset($oferta['encontros_previstos']); // 1.17.2 (D-462): os previstos só existem na oferta por chamada
        }
        $this->dados = $oferta + [
            'id' => $id,
            'bimestre' => 3 /* E4: etapa única (3 = Anual; 3 = Somente Atividades), inerte no cálculo */,
            'tipo' => 3 /* E4: etapa única (3 = Anual; 3 = Somente Atividades), inerte no cálculo */,
        ];
        $this->altDisciplina();
    }
    private function altDisciplina()
    {
        $this->dados['usuario_mod'] = $_SESSION['colUsuario_id'];
        $this->dados['modified'] = date("Y-m-d H:i:s");
        $altDisci = new \App\adms\Models\helper\AdmsUpdate();
        $altDisci->exeUpdate("rm_professores_materias", $this->dados, "WHERE id=:id", "id={$this->dados['id']}");
        if($altDisci->getResultado() !== null){
            \App\adms\Models\moodle\AdmsMoodle::anotarNome((int) $this->dados['id']); // 1.36.1: a disciplina já no Moodle acompanha o nome
            $this->logCodigo = 200;
            $this->logDescricao = "Disciplina editado com sucesso! id={$this->dados['id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Disciplina atualizado com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível editar o Disciplina! id={$this->dados['id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: ao tentar atualizar o Disciplina!</div>";
            $this->resultado = false;
        }

    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(93, $this->logCodigo, 3, $this->logDescricao);
    }

}