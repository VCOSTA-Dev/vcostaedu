<?php

namespace App\adms\Models\academicos;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Cadastro do ALUNO pela secretaria (E2b).
 *
 * - O usuário do portal é o próprio aluno (D-00b): nível acadêmico 1
 *   (Estudante), fixo.
 * - RA digitado, obrigatório e único (D-31), em col_usuarios.rm. O campo de
 *   login legado col_usuarios.usuario espelha o RA.
 * - A senha NÃO é digitada aqui: o aluno nasce com o marcador '-inexistente'
 *   (nunca passa em password_verify) e define a própria senha pelo link de
 *   acesso (D-07 b).
 * - Grava em col_usuarios, col_usuarios_informacoes e col_usuarios_enderecos.
 *   Não cria matrícula nem enturmação (E3b).
 */
class AdmsCadastrarUsuarioAcademico
{
    private array $dados = [];
    private array $opcoes = [];
    private array $erros = [];
    private $idUsuario;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    function getIdUsuario()
    {
        return $this->idUsuario;
    }

    /** Os motivos da recusa (a importação os mostra na linha). */
    function getErros(): array
    {
        return $this->erros;
    }

    /** $opcoes: as de AdmsValidaAluno::validar ('sexo_pendente', 'sem_sessao') — a importação de alunos (Onda 2, Etapa 5). */
    public function cadUserAcademico(array $dados, array $opcoes = [])
    {
        $this->dados = array_map(fn($v) => is_string($v) ? trim($v) : $v, $dados);
        $this->opcoes = $opcoes;
        $this->erros = [];
        $valida = new AdmsValidaAluno();
        if (!$valida->validar($this->dados, null, $opcoes)) {
            $this->erros = $valida->getErros();
            $this->resultado = false;
            return;
        }
        /* E1b (D-345): usuário, dados pessoais e endereço numa transação só — antes, uma falha deixava o aluno pela metade */
        $ok = \Core\Transacao::executar(function () {
            $this->addUsuario();
            return $this->resultado === true;
        });
        if ($ok && $this->idUsuario) {
            /* Onda 3, Etapa 8: o aluno vai ao Moodle (no fim da requisição, depois da gravação; nunca derruba o cadastro) */
            \App\adms\Models\moodle\AdmsMoodle::anotar('aluno', (int) $this->idUsuario);
        }
        if (!$ok) {
            $this->idUsuario = null;
            $this->resultado = false;
            $this->erros = $this->erros ?: ['não foi possível cadastrar o aluno (' . (\Core\Transacao::ultimoErro() ?: 'erro ao gravar') . ')'];
            if (!empty($this->opcoes['sem_sessao'])) {
                return;
            }
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button><b>Nada foi salvo:</b> não foi possível cadastrar o aluno (" . htmlspecialchars(\Core\Transacao::ultimoErro() ?: 'erro ao gravar', ENT_QUOTES, 'UTF-8') . "). Tente de novo.</div>";
        }
    }

    private function nulo($v)
    {
        return ($v === null || $v === '') ? null : $v;
    }

    private function addUsuario()
    {
        $d = $this->dados;
        $ra = (string) (int) $d['rm'];
        $semSexo = (string) ($d['genero_id'] ?? '') === ''; // só passa na validação com 'sexo_pendente' (a importação)
        $usuario = [
            'rm' => $ra,
            'nome' => $d['nome'],
            'nome_social' => $this->nulo($d['nome_social'] ?? ''),
            /* E1b (D-356): obrigatório na tela; sem padrão silencioso. Sem o sexo (só a importação): a coluna não aceita vazio,
               então fica como os alunos de antes da E1b — 3 SEM a confirmação, o que põe o aluno na lista "Alunos sem sexo
               informado" (AdmsPendenciaSexo) até alguém escolher o sexo na ficha */
            'genero_id' => $semSexo ? 3 : (int) $d['genero_id'],
            'genero_confirmado_em' => $semSexo ? null : date('Y-m-d H:i:s'), // sem sexo: entra na lista "Alunos sem sexo informado"
            'usuario' => $ra,
            'senha' => '-inexistente',
            'email' => $this->nulo($d['email'] ?? ''),
            'telefone' => $this->nulo(preg_replace('/\D/', '', (string) ($d['telefone'] ?? ''))),
            'celular' => $this->nulo(preg_replace('/\D/', '', (string) ($d['celular'] ?? ''))),
            'col_niveis_acesso_id' => 1,
            'col_sits_usuario_id' => (int) ($d['col_sits_usuario_id'] ?? 1) ?: 1,
            'usuario_cre' => $_SESSION['colUsuario_id'],
        ];
        $addUsuario = new \App\adms\Models\helper\AdmsCreate();
        $addUsuario->exeCreate("col_usuarios", $usuario);
        if (!$addUsuario->getResultado()) {
            $this->falha("Não foi possível cadastrar o aluno.");
            return;
        }
        $this->idUsuario = $addUsuario->getResultado();

        $informacoes = [
            'col_user_id' => $this->idUsuario,
            'nasc_data' => $this->nulo($d['nasc_data'] ?? ''),
            'nasc_local' => $this->nulo($d['nasc_local'] ?? ''),
            'nasc_uf' => $this->nulo($d['nasc_uf'] ?? ''),
            'nasc_nac' => $this->nulo($d['nasc_nac'] ?? ''),
            'rg' => $this->nulo(preg_replace('/[^a-zA-Z0-9]/', '', (string) ($d['rg'] ?? ''))),
            'rg_org' => $this->nulo($d['rg_org'] ?? ''),
            'rg_uf' => $this->nulo($d['rg_uf'] ?? ''),
            'cpf' => $this->nulo(\App\adms\Models\helper\AdmsCpf::digitos($d['cpf'] ?? '')),
            'nome_mae' => $this->nulo($d['nome_mae'] ?? ''),
            'nome_pai' => $this->nulo($d['nome_pai'] ?? ''),
            'usuario_cre' => $_SESSION['colUsuario_id'],
        ];
        $addInfo = new \App\adms\Models\helper\AdmsCreate();
        $addInfo->exeCreate("col_usuarios_informacoes", $informacoes);

        $endereco = [
            'usuario_id' => $this->idUsuario,
            'cep' => $this->nulo(preg_replace('/\D/', '', (string) ($d['cep'] ?? ''))),
            'endereco' => $this->nulo($d['endereco'] ?? ''),
            'numero' => $this->nulo($d['numero'] ?? ''),
            'complemento' => $this->nulo($d['complemento'] ?? ''),
            'bairro' => $this->nulo($d['bairro'] ?? ''),
            'cidade' => $this->nulo($d['cidade'] ?? ''),
            'uf' => $this->nulo($d['uf'] ?? ''),
            'situacao' => 1,
            'usuario_cre' => $_SESSION['colUsuario_id'],
        ];
        $addEnd = new \App\adms\Models\helper\AdmsCreate();
        $addEnd->exeCreate("col_usuarios_enderecos", $endereco);

        if (!$addInfo->getResultado() || !$addEnd->getResultado()) {
            \Core\Transacao::falhar('dados pessoais ou endereço'); // E1b: desfaz o aluno inteiro
        }

        $this->logCodigo = 200;
        $this->logDescricao = "Aluno cadastrado com sucesso! id={$this->idUsuario}&ra={$ra}";
        $this->salvarLog();
        if (!empty($this->opcoes['sem_sessao'])) {
            $this->resultado = true;
            return;
        }
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Aluno cadastrado com sucesso! Para o primeiro acesso, use <b>Enviar link de acesso</b>.</div>";
        $this->resultado = true;
    }

    private function falha(string $msg)
    {
        $this->erros[] = $msg;
        $this->logCodigo = 400;
        $this->logDescricao = "Erro ao cadastrar aluno: {$msg}";
        $this->salvarLog();
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: {$msg}</div>";
        $this->resultado = false;
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(47, $this->logCodigo, 2, $this->logDescricao);
    }

    public function listarCadastro()
    {
        $listarSit = new \App\adms\Models\helper\AdmsRead();
        $listarSit->fullRead("SELECT id, nome FROM col_situacao WHERE id IN (1, 2) ORDER BY id");
        $this->resultado['situacoes'] = $listarSit->getResultado();

        $listGen = new \App\adms\Models\helper\AdmsRead();
        $listGen->fullRead("SELECT id, nome FROM adms_generos WHERE situacao=:situacao" , "situacao=1");
        $this->resultado['generos'] = $listGen->getResultado();

        $listUfs = new \App\adms\Models\helper\AdmsRead();
        $listUfs->fullRead("SELECT nome AS sigla, nome_completo FROM adms_uf ORDER BY nome asc");
        $this->resultado['ufs'] = $listUfs->getResultado();

        return $this->resultado;
    }

}
