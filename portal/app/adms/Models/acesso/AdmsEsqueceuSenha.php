<?php

namespace App\adms\Models\acesso;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * "Esqueci a senha" do portal (E2b). Usa o MESMO mecanismo do primeiro acesso
 * (AdmsTokenAcesso), no lugar do fluxo herdado (md5(id.data), sem expiração,
 * dizendo "enviado" mesmo quando falhava).
 *
 * A resposta ao público é SEMPRE a mesma, exista ou não a conta, tenha ou não
 * e-mail, envie ou não o e-mail. O link nunca aparece na tela pública.
 */
class AdmsEsqueceuSenha
{
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function recuperarLogin(array $dados)
    {
        /* E7 (D-73): cada pedido conta, por identificador e por IP */
        $ident = (string) ($dados['identificador'] ?? '');
        $espera = \App\adms\Models\helper\AdmsLimiteTentativas::espera('esqueci_portal', $ident);
        if ($espera > 0) {
            \App\adms\Models\helper\AdmsLimiteTentativas::logBloqueio('esqueci_portal', $ident, $espera);
            $_SESSION['msg'] = "<div class='alert alert-warning alert-dismissible' id='esqueciEspera'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>" . \App\adms\Models\helper\AdmsLimiteTentativas::mensagem($espera) . '</div>';
            $this->resultado = false;
            return;
        }
        \App\adms\Models\helper\AdmsLimiteTentativas::registrar('esqueci_portal', $ident);
        $campo = \App\adms\Models\helper\AdmsParametro::campoLoginPortal();
        /* Onda 8 (D-67): na escola, o aluno só pela matrícula digitada (só os números); o CPF e o e-mail são do responsável */
        $naEscola = \App\adms\Models\helper\AdmsParametro::naEscola();
        $aluno = (!$naEscola || preg_match('/^\d{1,9}$/', trim($ident))) ? AdmsIdentificaAluno::buscar($campo === 'email' && !$naEscola ? 'ra' : $campo, $ident) : null;
        /* Fechamento (D-107): no ensino superior e no curso livre, também pelo e-mail do cadastro (de uma conta só) */
        if (!$naEscola && str_contains($ident, '@')) {
            require_once dirname(__DIR__, 5) . '/core/Banco.php';
            require_once dirname(__DIR__, 5) . '/core/LoginPortal.php';
            $ids = \VcLoginPortal::alunosPorEmail(\VcBanco::daRequisicao(), $ident);
            $aluno = count($ids) === 1 ? AdmsIdentificaAluno::porId($ids[0]) : null;
        }

        if ($aluno && (int) $aluno['col_sits_usuario_id'] === 1 && filter_var((string) $aluno['email'], FILTER_VALIDATE_EMAIL)) {
            $tokens = new \App\adms\Models\helper\AdmsTokenAcesso();
            $token = $tokens->emitir((int) $aluno['id'], 'recuperacao', null);
            $motivo = null;
            $enviado = $tokens->enviarEmail((string) $aluno['email'], (string) $aluno['nome'], \App\adms\Models\helper\AdmsTokenAcesso::link($token), 'recuperacao', $motivo);
            $this->salvarLog((int) $aluno['id'], $enviado ? 200 : 202,
                'Pedido de redefinição de senha: link emitido; e-mail ' . ($enviado ? 'enviado' : 'NÃO enviado: ' . $motivo) . '.');
        } elseif (AdmsSenhaResponsavel::pedir((string) ($dados['identificador'] ?? ''))) {
            /* Onda 7: o responsável com e-mail (o registro fica na auditoria dos responsáveis) */
            $this->salvarLog(0, 200, 'Pedido de senha nova de responsável: link emitido e enviado (identificador não mostrado).');
        } else {
            $this->salvarLog(0, 404, 'Pedido de redefinição de senha sem conta elegível (identificador não mostrado).');
        }

        $horas = \App\adms\Models\helper\AdmsTokenAcesso::horasValidade();
        require_once dirname(__DIR__, 5) . '/core/ContaFamilia.php';
        $familia = \VcContaFamilia::ligada();
        $_SESSION['msg'] = "<div class='alert alert-info alert-dismissible' id='esqueciEnviado'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
            . ($familia
                ? "Se os dados corresponderem a um aluno ou a um responsável com e-mail cadastrado, enviamos um link para criar a senha. "
                  . "Confira sua caixa de entrada e o spam. O link do aluno vale por {$horas} horas; o do responsável, por " . \VcContaFamilia::HORAS_SENHA . ' horas. '
                  . 'Responsável sem e-mail: peça um novo convite à secretaria da escola.</div>'
                : "Se os dados corresponderem a um aluno com e-mail cadastrado, enviamos um link para definir a senha. "
                  . "Confira sua caixa de entrada e o spam. O link vale por {$horas} horas. Se não receber, procure a secretaria.</div>");
        $this->resultado = true;
    }

    private function salvarLog(int $alunoId, int $codigo, string $descricao)
    {
        $log = new \App\adms\Models\helper\AdmsSalvarLogs();
        $log->salvarLogs(['codigo' => $codigo, 'observacao' => $descricao, 'descricao' => json_encode(['aluno_id' => $alunoId]), 'pagina_id' => 4]);
    }
}
