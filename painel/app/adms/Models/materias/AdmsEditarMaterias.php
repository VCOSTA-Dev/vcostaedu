<?php

namespace App\adms\Models\materias;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsEditarMaterias
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

    public function visMaterias($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $verCursoedicao = new \App\adms\Models\helper\AdmsRead();
        $verCursoedicao->fullRead("SELECT * FROM rm_materias WHERE id=:id LIMIT :limit", "id={$this->dadosId}&limit=1");
        $this->resultado = $verCursoedicao->getResultado();

        return $this->resultado;
    }

    public function editMateria(array $dados)
    {
        /* 1.18.1 (D-481): só os campos do formulário (e o id); nome e carga horária obrigatórios também no servidor */
        $this->dados = array_intersect_key($dados, array_flip(array_merge(['id'], AdmsCadastrarMaterias::CAMPOS)));
        $this->dados['id'] = (int) ($this->dados['id'] ?? 0);
        AdmsCadastrarMaterias::normalizarCodigo($this->dados);
        $erros = AdmsCadastrarMaterias::validar($this->dados);
        if ($this->dados['id'] <= 0) {
            $erros[] = 'Matéria não encontrada.';
        }
        if ($erros) {
            AdmsCadastrarMaterias::recusar('alterar', $erros);
            $this->resultado = false;
            return;
        }
        $this->altDisc();
    }

    private function altDisc()
    {
        $this->dados['usuario_mod'] = $_SESSION['colUsuario_id'];
        $this->dados['modified'] = date("Y-m-d H:i:s");
        /* Onda 8, correção, segunda leva, 2.9 (D-95): a oferta guarda uma cópia do nome da disciplina
           (rm_professores_materias.materia), que as telas, o portal e o boletim mostram. O nome novo vale também nas
           ofertas que já existem — as da disciplina pela matriz, com o nome de antes (a oferta com outro nome, dado à
           mão, fica como está) —, na mesma transação. Os documentos já emitidos guardam o retrato e não mudam. */
        $antes = (new self())->visMaterias($this->dados['id']);
        $nomeAntes = $antes ? (string) $antes[0]['nome'] : null;
        $ofertas = 0;
        $T = \Core\Transacao::class;
        $altPg = new \App\adms\Models\helper\AdmsUpdate();
        $gravou = $T::executar(function () use ($T, $altPg, $nomeAntes, &$ofertas) {
            $altPg->exeUpdate("rm_materias", $this->dados, "WHERE id=:id", "id={$this->dados['id']}");
            if (!$altPg->getResultado()) {
                return false;
            }
            $novo = (string) ($this->dados['nome'] ?? '');
            if ($nomeAntes !== null && $novo !== '' && $novo !== $nomeAntes) {
                $st = $T::pdo()->prepare('UPDATE rm_professores_materias pm JOIN uni_rh_cursos_disciplinas_grade g ON g.id = pm.matriz_id
                                             SET pm.materia = ? WHERE g.materia_id = ? AND pm.materia = ?');
                $st->execute([$novo, (int) $this->dados['id'], $nomeAntes]);
                $ofertas = $st->rowCount();
            }
            return true;
        });
        if ($gravou) {
            $this->logCodigo = 200;
            $this->logDescricao = "Matéria alterada com sucesso! id={$this->dados['id']}" . ($ofertas ? "; o nome novo em {$ofertas} oferta(s)" : '');
            $this->salvarLog();
            $nasOfertas = $ofertas ? " O nome novo vale também nas {$ofertas} oferta(s) que já existiam." : '';
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Matéria alterada!{$nasOfertas}</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Erro: não foi possível alterar a matéria! id={$this->dados['id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: não foi possível alterar a matéria!</div>";
            $this->resultado = false;
        }

    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(83, $this->logCodigo, 3, $this->logDescricao);
    }

}