<?php

namespace App\adms\Models\materias;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsCadastrarMaterias
{
    private $dados;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    /** E6b (decisão 5): código da disciplina no histórico; vazio = NULL (o histórico usa o id). */
    public static function normalizarCodigo(array &$dados): void
    {
        if (array_key_exists('abreviacao', $dados)) {
            $c = mb_substr(trim(preg_replace('/\s+/', ' ', (string) $dados['abreviacao'])), 0, 20);
            $dados['abreviacao'] = $c === '' ? null : $c;
        }
    }

    /** 1.18.1 (D-481): só os campos do formulário entram na gravação (o POST não escolhe colunas). */
    public const CAMPOS = ['abreviacao', 'nome', 'carga_horaria', 'descricao'];

    /**
     * 1.18.1 (D-481): nome e carga horária são obrigatórios — na tela e AQUI, no servidor (um POST direto também é
     * recusado, sem gravar). Normaliza: nome sem espaços sobrando, CH inteira de 1 a 9999. Devolve os erros.
     */
    public static function validar(array &$dados): array
    {
        $erros = [];
        $dados['nome'] = trim(preg_replace('/\s+/u', ' ', (string) ($dados['nome'] ?? '')));
        if ($dados['nome'] === '') {
            $erros[] = 'Informe o nome da matéria.';
        } elseif (mb_strlen($dados['nome']) > 255) {
            $erros[] = 'O nome da matéria tem no máximo 255 caracteres.';
        }
        $ch = trim((string) ($dados['carga_horaria'] ?? ''));
        if ($ch === '') {
            $erros[] = 'Informe a carga horária da matéria.';
        } elseif (!ctype_digit($ch) || (int) $ch < 1 || (int) $ch > 9999) {
            $erros[] = 'A carga horária deve ser um número inteiro de horas, de 1 a 9999.';
        } else {
            $dados['carga_horaria'] = (string) (int) $ch;
        }
        return $erros;
    }

    /** A mensagem de recusa, com cada motivo. */
    public static function recusar(string $acao, array $erros): void
    {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: não foi possível {$acao} a matéria:<ul class='mb-0'><li>"
            . implode('</li><li>', array_map(fn($e) => htmlspecialchars($e, ENT_QUOTES, 'UTF-8'), $erros)) . '</li></ul></div>';
    }

    public function cadMateria(array $dados)
    {
        $this->dados = array_intersect_key($dados, array_flip(self::CAMPOS));
        self::normalizarCodigo($this->dados);
        $erros = self::validar($this->dados);
        if ($erros) {
            self::recusar('criar', $erros);
            $this->resultado = false;
            return;
        }
        $this->addMateria();
    }

    private function addMateria()
    {
        $this->dados['usuario_cre'] = $_SESSION['colUsuario_id'];
        $this->dados['situacao'] = 1;
        $addItMenu = new \App\adms\Models\helper\AdmsCreate();
        $addItMenu->exeCreate("rm_materias", $this->dados);
        if($addItMenu->getResultado()){
            $identificador = $addItMenu->getResultado();
            $this->logCodigo = 200;
            $this->logDescricao = "Matéria cadastrada com sucesso! id={$identificador}&nome={$this->dados['nome']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Matéria criada!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Erro: matéria não cadastrada! nome={$this->dados['nome']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: não foi possível criar a matéria!</div>";
            $this->resultado = false; 
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(81, $this->logCodigo, 2, $this->logDescricao);
    }

}