<?php

namespace App\adms\Models\academicos;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Regras do cadastro do aluno, iguais no CADASTRO e na EDIÇÃO (D-31):
 *
 * - RA (col_usuarios.rm): obrigatório, só dígitos (a coluna é INT), até 9
 *   dígitos, e ÚNICO. O banco também tem índice UNIQUE; esta checagem existe
 *   para devolver uma mensagem clara em vez de um erro de gravação.
 * - CPF: opcional; se vier, precisa ter os dígitos verificadores certos e ser
 *   ÚNICO. Gravado só com dígitos (UNIQUE no banco; vazio vira NULL).
 * - E-mail: opcional; se vier, formato válido e ÚNICO (UNIQUE no banco;
 *   vazio vira NULL).
 *
 * $idAluno é o id de col_usuarios na edição (para não acusar o próprio
 * registro de duplicado) e null no cadastro.
 */
class AdmsValidaAluno
{
    private array $erros = [];

    /**
     * Correção da escola, Parte 3 (decisão 3; D-102): o próximo número da sequência dos alunos — o maior número só de
     * algarismos, mais um (o primeiro aluno, 1). A secretaria pode trocar; o número continua único (a validação).
     */
    public static function proximoNumero(): string
    {
        $l = new \App\adms\Models\helper\AdmsRead();
        $l->fullRead("SELECT MAX(CAST(rm AS UNSIGNED)) AS m FROM col_usuarios WHERE col_niveis_acesso_id = :n AND rm REGEXP '^[0-9]{1,9}$'", 'n=1');
        $m = (int) ($l->getResultado()[0]['m'] ?? 0);
        return (string) ($m + 1);
    }
    public function getErros(): array
    {
        return $this->erros;
    }

    /**
     * $opcoes (Onda 2, Etapa 5 — a importação usa ESTA validação):
     *   'sexo_pendente' => true: o sexo pode faltar (o aluno entra na lista "Alunos sem sexo informado", como os de antes da E1b);
     *   'sem_sessao'    => true: os erros ficam só em getErros(), sem a mensagem da tela.
     */
    public function validar(array $dados, ?int $idAluno = null, array $opcoes = []): bool
    {
        $this->erros = [];
        $id = (int) ($idAluno ?? 0);

        if (trim((string) ($dados['nome'] ?? '')) === '') {
            $this->erros[] = 'Informe o nome do aluno.';
        }
        /* E1b (D-356): o sexo é OBRIGATÓRIO (os documentos concordam com ele); acabou o padrão silencioso "Outros" */
        $semSexo = (string) ($dados['genero_id'] ?? '') === '' && !empty($opcoes['sexo_pendente']);
        if (!$semSexo && !in_array((int) ($dados['genero_id'] ?? 0), [1, 2, 3], true)) {
            $this->erros[] = 'Escolha o sexo do aluno (Feminino, Masculino ou Outros): os documentos concordam com ele (ALUNA/ALUNO).';
        }

        $ra = trim((string) ($dados['rm'] ?? ''));
        /* Onda 8 (D-67): na escola, a matrícula é o LOGIN do aluno no portal — não pode ser um CPF válido (o CPF é o login
           do responsável) nem o login de outra conta */
        $naEscola = \App\adms\Models\helper\AdmsParametro::naEscola();
        if ($naEscola) {
            require_once dirname(__DIR__, 5) . '/core/LoginPortal.php';
        }
        if ($ra === '') {
            $this->erros[] = $naEscola ? 'Informe o RM (o número de matrícula).' : 'Informe o RA (registro acadêmico).';
        } elseif ($naEscola && \VcLoginPortal::comoCpf($ra) !== null) {
            $this->erros[] = 'O número de matrícula não pode ser um CPF: na escola, o CPF é o login do responsável no portal. Use o número de matrícula da escola.';
        } elseif (!preg_match('/^\d{1,9}$/', $ra)) {
            $this->erros[] = 'O ' . \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero() . ' deve ter só dígitos, até 9.';
        } elseif ($this->existe("SELECT id FROM col_usuarios WHERE rm = :v AND id <> :id LIMIT 1", $ra, $id)) {
            /* D-102: na escola, o número proposto pode ter sido salvo por outra pessoa antes; a tela traz o seguinte */
            $this->erros[] = $naEscola
                ? "O número {$ra} já foi usado por um cadastro salvo antes. O campo agora traz o seguinte, " . self::proximoNumero() . ': confira e salve de novo.'
                : "O RA {$ra} já pertence a outro aluno.";
        } elseif ($naEscola && ($q = \VcLoginPortal::emUso(\VcBanco::daRequisicao(), $ra, 0, $id)) && $q['tipo'] === 'responsavel') {
            $this->erros[] = \VcLoginPortal::mensagemEmUso($q, $ra);
        }

        $cpf = \App\adms\Models\helper\AdmsCpf::digitos($dados['cpf'] ?? '');
        if ($cpf !== '') {
            if (!\App\adms\Models\helper\AdmsCpf::valido($cpf)) {
                $this->erros[] = 'CPF inválido (dígitos verificadores não conferem).';
            } elseif ($this->existe("SELECT col_user_id AS id FROM col_usuarios_informacoes WHERE cpf = :v AND col_user_id <> :id LIMIT 1", $cpf, $id)) {
                $this->erros[] = 'Este CPF já está cadastrado para outro aluno.';
            }
        }

        $email = trim((string) ($dados['email'] ?? ''));
        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->erros[] = 'E-mail inválido.';
            } elseif (!$naEscola && $this->existe("SELECT id FROM col_usuarios WHERE email = :v AND id <> :id LIMIT 1", $email, $id)) {
                /* no ensino superior o e-mail pode ser o login do portal: único. Na escola (Onda 8, D-67) não é login, e
                   irmãos podem ter no cadastro o e-mail da mesma pessoa */
                $this->erros[] = 'Este e-mail já está cadastrado para outro aluno.';
            }
        }

        if ($this->erros && !empty($opcoes['sem_sessao'])) {
            return false;
        }
        if ($this->erros) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
                . "<b>Não foi possível salvar:</b><ul class='mb-0'><li>" . implode('</li><li>', array_map('htmlspecialchars', $this->erros)) . "</li></ul></div>";
            return false;
        }
        return true;
    }

    private function existe(string $sql, string $valor, int $id): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead($sql, "v=" . urlencode($valor) . "&id={$id}");
        return !empty($ler->getResultado());
    }
}
