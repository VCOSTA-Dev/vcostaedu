<?php

namespace App\adms\Models\acesso;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}
class AdmsLogin
{
    private $dados;
    private $dadosLogs;
    private $resultado;
    private $resultUser;
    private $dadosUsuarios;

    function getResultado()
    {
        return $this->resultado;
    }

    public function login(array $dados)
    {
        $this->dados = $dados;
        $valCampoVazio = new \App\adms\Models\helper\AdmsValCampoVazio();
        $valCampoVazio->validarDados($this->dados);
        if($valCampoVazio->getResultado()){
            $this->validarUsuario();
        }else{
            $this->resultado = false;
        }
    }

    private function validarUsuario()
    {
        /* E7 (D-73): limite de tentativas por identificador e por IP */
        $espera = \App\adms\Models\helper\AdmsLimiteTentativas::espera('login_portal', (string) $this->dados['usuario']);
        if ($espera > 0) {
            \App\adms\Models\helper\AdmsLimiteTentativas::logBloqueio('login_portal', (string) $this->dados['usuario'], $espera);
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible' id='loginEspera'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>" . \App\adms\Models\helper\AdmsLimiteTentativas::mensagem($espera) . '</div>';
            $this->resultado = false;
            return;
        }
        /* Onda 8 (D-67): na escola, cada conta pelo próprio login — o CPF válido ou o que tem "@" é o login de um
           responsável; o resto, a matrícula do aluno. Um caminho só por entrada: nunca o aluno e, se falhar, o responsável. */
        if (($conta = $this->contaDaEscola()) !== null) {
            $this->entrarNaEscola($conta);
            return;
        }
        /* Fechamento (D-107): no ensino superior e no curso livre, o aluno entra pelo e-mail do cadastro OU pela matrícula.
           Com "@", o e-mail: uma conta, a entrada de sempre; o mesmo e-mail em mais de um cadastro, com a senha de um deles,
           a tela avisa e pede a matrícula (sem a senha certa, a falha de sempre, sem dizer o que existe). Sem "@", a
           matrícula (o RA; o CPF, se a instituição escolheu o CPF). Na escola, nunca o e-mail do aluno (D-67). */
        $digitado = trim((string) $this->dados['usuario']);
        if (str_contains($digitado, '@') && !\App\adms\Models\helper\AdmsParametro::naEscola()) {
            $this->entrarComEmail($digitado);
            return;
        }
        // E2b: o login lê o campo configurado em adms_parametros (portal.login.campo); o "e-mail" de lá vale como o RA aqui (o e-mail entrou acima).
        $campo = \App\adms\Models\helper\AdmsParametro::campoLoginPortal();
        $campo = $campo === 'email' && !\App\adms\Models\helper\AdmsParametro::naEscola() ? 'ra' : $campo;
        $aluno = AdmsIdentificaAluno::buscar($campo, (string) $this->dados['usuario']);
        $this->resultUser = $aluno ? [$aluno] : [];
        if (!empty($this->resultUser)) {
            $this->dadosUsuarios = $this->resultUser[0];
            unset($this->dadosUsuarios['senha'], $this->dadosUsuarios['imagem']);
            $this->validarSenha();
        } elseif ($this->entrarResponsavel()) {
            return;
        } else {
            password_verify((string) $this->dados['senha'], '$2y$10$x1mTqNqndU4u9NOyqHkHDOo8v9LdwDFOFE14tXt4/g9NgHn2koI2S'); // E7: mesmo tempo de resposta
            $this->falhaGenerica();
        }
    }

    /** Fechamento (D-107): a entrada pelo e-mail do cadastro (o ensino superior e o curso livre). */
    private function entrarComEmail(string $digitado): void
    {
        require_once dirname(__DIR__, 5) . '/core/Banco.php';
        require_once dirname(__DIR__, 5) . '/core/LoginPortal.php';
        $ids = \VcLoginPortal::alunosPorEmail(\VcBanco::daRequisicao(), $digitado);
        if (count($ids) === 1 && ($aluno = AdmsIdentificaAluno::porId($ids[0]))) {
            $this->resultUser = [$aluno];
            $this->dadosUsuarios = $aluno;
            unset($this->dadosUsuarios['senha'], $this->dadosUsuarios['imagem']);
            $this->validarSenha(false);
            return;
        }
        if (count($ids) > 1) {
            foreach ($ids as $id) {
                $a = AdmsIdentificaAluno::porId($id);
                if ($a && password_verify((string) $this->dados['senha'], (string) $a['senha'])) {
                    $_SESSION['msg'] = "<div class='alert alert-warning alert-dismissible' id='emailRepetido'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Este e-mail está em mais de um cadastro. Entre com o número de matrícula.</div>";
                    $this->resultado = false;
                    return;
                }
            }
        }
        password_verify((string) $this->dados['senha'], '$2y$10$x1mTqNqndU4u9NOyqHkHDOo8v9LdwDFOFE14tXt4/g9NgHn2koI2S'); // o mesmo tempo
        $this->falhaGenerica();
    }

    /** A conta da família, quando a instalação é uma escola com os responsáveis (senão, null: o caminho de sempre). */
    private function contaDaEscola(): ?\VcContaFamilia
    {
        require_once dirname(__DIR__, 5) . '/core/Banco.php';
        require_once dirname(__DIR__, 5) . '/core/ContaFamilia.php';
        $conta = new \VcContaFamilia(\VcBanco::daRequisicao());
        return ($conta->ehEscola() && $conta->pronto()) ? $conta : null;
    }

    /** A entrada na escola (D-67): o responsável pelo login dele; o aluno só pela matrícula. A mesma falha para os dois. */
    private function entrarNaEscola(\VcContaFamilia $conta): void
    {
        $digitado = trim((string) $this->dados['usuario']);
        if (\VcLoginPortal::comoCpf($digitado) !== null || str_contains($digitado, '@')) {
            if (!$this->entrarResponsavel()) {
                password_verify((string) $this->dados['senha'], '$2y$10$x1mTqNqndU4u9NOyqHkHDOo8v9LdwDFOFE14tXt4/g9NgHn2koI2S'); // o mesmo tempo
                $this->falhaGenerica();
            }
            return;
        }
        /* a matrícula como foi digitada: só os números (nada de tirar letras ou sinais para achar um aluno) */
        $aluno = preg_match('/^\d{1,9}$/', $digitado) ? AdmsIdentificaAluno::buscar('ra', $digitado) : null;
        if (!$aluno) {
            password_verify((string) $this->dados['senha'], '$2y$10$x1mTqNqndU4u9NOyqHkHDOo8v9LdwDFOFE14tXt4/g9NgHn2koI2S');
            $this->falhaGenerica();
            return;
        }
        $this->resultUser = [$aluno];
        $this->dadosUsuarios = $aluno;
        unset($this->dadosUsuarios['senha'], $this->dadosUsuarios['imagem']);
        $this->validarSenha(false);
    }

    /**
     * Onda 7 (D-63): na escola, o RESPONSÁVEL entra na mesma tela, com o e-mail ou o CPF e a senha que criou pelo convite.
     * O aluno é procurado primeiro; o responsável, quando não há aluno com o identificador ou a senha do aluno não confere.
     * @return bool true se a entrada foi resolvida aqui (entrou, ou está bloqueado); false: segue a falha genérica
     */
    private function entrarResponsavel(): bool
    {
        require_once dirname(__DIR__, 5) . '/core/Banco.php';
        require_once dirname(__DIR__, 5) . '/core/ContaFamilia.php';
        $conta = new \VcContaFamilia(\VcBanco::daRequisicao());
        if (!$conta->ehEscola() || !$conta->pronto()) {
            return false;
        }
        $r = $conta->entrar((string) $this->dados['usuario'], (string) $this->dados['senha']);
        if ($r === null) {
            return false;
        }
        if (!empty($r['bloqueado'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible' id='responsavelBloqueado'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>O seu acesso ao portal está bloqueado. Procure a secretaria da escola.</div>";
            $this->resultado = false;
            return true;
        }
        $p = $r['ok'];
        session_regenerate_id(true);
        unset($_SESSION['academico_id'], $_SESSION['academico_email'], $_SESSION['academico_imagem'], $_SESSION['academico_ordem'], $_SESSION['vc_familia_filho']);
        $_SESSION['vc_responsavel_id'] = (int) $p['id'];
        $_SESSION['academico_nome'] = (string) $p['nome'];
        $_SESSION['academico_nivel'] = 2; // o nível "Responsável" do portal: só as telas da família
        $_SESSION['academico_situacao'] = 1;
        \App\adms\Models\helper\AdmsLimiteTentativas::zerar('login_portal', (string) $this->dados['usuario']);
        $this->resultado = true;
        return true;
    }

    /** E7: a MESMA mensagem para usuário inexistente e senha errada; conta a falha e, passado o limite, avisa a espera. */
    private function falhaGenerica(): void
    {
        $espera = \App\adms\Models\helper\AdmsLimiteTentativas::registrar('login_portal', (string) $this->dados['usuario']);
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible' id='loginFalha'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Usuário ou senha incorretos." . ($espera > 0 ? ' ' . \App\adms\Models\helper\AdmsLimiteTentativas::mensagem($espera) : '') . '</div>';
        $this->resultado = false;
    }

    /** $doResponsavel: no ensino superior de antes (sem responsáveis, devolve falso); na escola, false (D-67). */
    private function validarSenha(bool $doResponsavel = true)
    {
        if (password_verify($this->dados['senha'], $this->resultUser[0]['senha'])) {
            if($this->resultUser[0]['col_sits_usuario_id'] == 1){
                /* VCOSTAEDU (Etapa 2): identificador de sessão novo a cada login — fecha a fixação de sessão */
                session_regenerate_id(true);
                $_SESSION['academico_id'] = $this->resultUser[0]['id'];
                $_SESSION['academico_nome'] = $this->resultUser[0]['nome'];
                $_SESSION['academico_email'] = $this->resultUser[0]['email'];
                $_SESSION['academico_imagem'] = $this->resultUser[0]['imagem'];
                $_SESSION['academico_nivel'] = $this->resultUser[0]['col_niveis_acesso_id'];
                $_SESSION['academico_situacao'] = $this->resultUser[0]['col_sits_usuario_id'];
                $_SESSION['academico_ordem'] = $this->resultUser[0]['ordem_nivac'];
                \App\adms\Models\helper\AdmsLimiteTentativas::zerar('login_portal', (string) $this->dados['usuario']);
                $this->dadosLogs['codigo'] = 200;
                $this->dadosLogs['observacao'] = "Usuário acessou o sistema!";
                $this->salvarLog();
                $this->resultado = true;
            }else{
                $this->dadosLogs['codigo'] = 400;
                $this->dadosLogs['observacao'] = "Erro: Usuário com acesso bloqueado!";
                $this->salvarLog();
                $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Usuário bloqueado!</div>";
                $this->resultado = false;
            }
        } elseif ($doResponsavel && $this->entrarResponsavel()) {
            return; // Onda 7: o identificador é também de um responsável, e a senha é a dele
        } else {
            $this->dadosLogs['codigo'] = 400;
            $this->dadosLogs['observacao'] = "Erro: Usuário ou senha incorretos.";
            $this->salvarLog();
            $this->falhaGenerica();
        }
    }

    private function salvarLog()
    {
        $this->dadosLogs['descricao'] = json_encode($this->dadosUsuarios, true);
        $this->dadosLogs['pagina_id'] = 1;
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLogs();
        $valLogsalvar->salvarLogs($this->dadosLogs);
    }
}