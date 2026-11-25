<?php

namespace App\adms\Models\vinculo;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\vinculo\AdmsVinculoRegras as R;

/**
 * Vincula o aluno a um curso: cria a MATRÍCULA NO CURSO (curso, grade, ingresso,
 * situação Cursando) e a POSIÇÃO NO ANO (ano + módulo). O módulo escolhido
 * determina curso e grade.
 */
class AdmsVincularAluno
{
    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    public function vincular(int $usuarioId, array $dados)
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, rm, nome FROM col_usuarios WHERE id = :id AND col_niveis_acesso_id = :n LIMIT :limit", "id={$usuarioId}&n=1&limit=1");
        $aluno = $ler->getResultado()[0] ?? null;
        if (!$aluno) {
            return $this->resultado = R::recusar('Aluno não encontrado.');
        }
        $ano = (int) ($dados['ano'] ?? 0);
        if (!R::anoLetivoExiste($ano)) {
            return $this->resultado = R::recusar('Escolha um ano letivo cadastrado.');
        }
        $semModulos = \App\adms\Models\helper\AdmsModuloInterno::ativo(); // E8c (D-268)
        $modulo = R::modulo((int) ($dados['modulo_id'] ?? 0));
        if ($modulo && $semModulos) {
            $modulo = R::modulo(\App\adms\Models\helper\AdmsModuloInterno::garantir((int) $modulo['grade_id']));
        }
        if (!$modulo) {
            return $this->resultado = R::recusar($semModulos ? 'Escolha o curso e a grade.' : 'Escolha o curso, a grade e o módulo.');
        }
        if (!\App\adms\Models\helper\AdmsInativar::ativo('curso', (int) $modulo['curso_id'])) { // E8d (P-8)
            return $this->resultado = R::recusar("O curso {$modulo['curso']} está inativo e não aceita vínculo novo. Reative-o em Cursos.");
        }
        if ((int) $modulo['situacaoGrade'] === 4) {
            return $this->resultado = R::recusar("A grade {$modulo['versao']} de {$modulo['curso']} está Desativada e não aceita vínculo novo.");
        }
        $forma = (int) ($dados['forma_ingresso_id'] ?? 0);
        if (!R::existe('rm_matricula_tipos', $forma)) {
            return $this->resultado = R::recusar('Escolha a forma de ingresso.');
        }
        $data = trim((string) ($dados['data_ingresso'] ?? ''));
        if (!R::dataValida($data)) {
            return $this->resultado = R::recusar('Informe a data de ingresso.');
        }
        $previsao = R::previsao($dados, $data); // E1b (D-354)
        if ($previsao === false) {
            return $this->resultado = false;
        }
        [$polo, $modalidade] = R::poloModalidade($dados, (int) $modulo['curso_id']); // E6a (D-115)
        if ($polo === false || $modalidade === false) {
            return $this->resultado = R::recusar('Polo ou modalidade inválidos.');
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT id FROM rm_matriculas_curso WHERE usuario_id = :u AND curso_id = :c AND situacao_id IN (1,3) LIMIT :limit",
            "u={$usuarioId}&c={$modulo['curso_id']}&limit=1"
        );
        if ($ler->getResultado()) {
            return $this->resultado = R::recusar("O aluno já tem matrícula cursando ou trancada em {$modulo['curso']}. Edite essa matrícula (" . ($semModulos ? 'situação' : 'mudar de módulo, situação') . ") em vez de criar outra.");
        }

        /* E1b (D-345): a matrícula no curso e a posição no ano numa transação só (antes, uma podia ficar sem a outra) */
        $T = \Core\Transacao::class;
        $mc = (int) $T::executar(function () use ($T, $usuarioId, $modulo, $forma, $data, $polo, $modalidade, $previsao, $dados, $ano) {
        $add = new \App\adms\Models\helper\AdmsCreate();
        $add->exeCreate('rm_matriculas_curso', [
            'usuario_id' => $usuarioId,
            'curso_id' => (int) $modulo['curso_id'],
            'grade_id' => (int) $modulo['grade_id'],
            'forma_ingresso_id' => $forma,
            'data_ingresso' => $data,
            'situacao_id' => R::CURSANDO,
            'polo_id' => $polo,
            'modalidade_id' => $modalidade,
            'previsao_conclusao' => $previsao === '' ? null : $previsao,
            'observacao' => trim((string) ($dados['observacao'] ?? '')),
            'usuario_cre' => $_SESSION['colUsuario_id'],
        ]);
        $mc = (int) $T::exigir($add->getResultado(), 'matrícula no curso');
        if (!(new AdmsPosicionarModulo())->gravarPosicao($mc, $usuarioId, $ano, $modulo, R::CURSANDO, $forma)) {
            $T::falhar('posição no ano');
        }
        /* Onda 8, financeiro, Parte 1 (D-98): com o plano escolhido (e a permissão de aplicá-lo), as parcelas nascem
           com a matrícula, na mesma transação: ou as duas, ou nenhuma */
        $plano = (int) ($dados['plano_id'] ?? 0);
        if ($plano > 0) {
            $pode = !empty((new \App\adms\Models\AdmsBotao())->valBotao(['p' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'aplicar-plano']])['p']);
            /* Correção da escola, Parte 3 (A1; D-102): a partir de qual parcela cobrar — a escolhida na tela (1 = todas;
               0 = nenhuma); sem a escolha, a primeira que ainda não venceu. Nenhum título nasce vencido sem alguém escolher. */
            $vp = \App\adms\Models\financeiro\AdmsFinTitulos::vencimentosDoPlano($plano, (int) $ano);
            $aPartir = isset($dados['parcela_inicial']) && $dados['parcela_inicial'] !== '' ? (int) $dados['parcela_inicial'] : $vp['primeira'];
            if ($aPartir < 0 || $aPartir > count($vp['vencimentos'])) {
                $T::falhar('escolha a parcela a partir da qual cobrar');
            }
            $r = $aPartir === 0 ? ['erro' => ''] : ($pode ? \App\adms\Models\financeiro\AdmsFinTitulos::gerarDoPlano($mc, $plano, (int) $_SESSION['colUsuario_id'], (int) $ano, $aPartir) : ['erro' => 'sem a permissão de aplicar um plano de pagamento']);
            if ($r['erro'] !== '') {
                $T::falhar('as parcelas do plano: ' . $r['erro']);
            }
        }
        return $mc;
        });
        if ($mc < 1) {
            return $this->resultado = R::recusar('Não foi possível gravar a matrícula (' . \Core\Transacao::ultimoErro() . '). Nada foi salvo.');
        }
        R::log(375, 1, "Vínculo: aluno_id={$usuarioId}; matricula_curso={$mc}; grade_id={$modulo['grade_id']}; modulo_id={$modulo['id']}; ano={$ano}");
        $_SESSION['msg'] = R::aviso('success', "{$aluno['nome']} vinculado a {$modulo['curso']}" . ($semModulos ? '' : ", {$modulo['codnome']}") . " ({$ano}).");
        /* Correção da escola, Parte 3 (C8; D-102): a turma escolhida no mesmo passo — da série e do ano da matrícula; o aluno
           entra em todas as disciplinas dela (a enturmação de sempre). A matrícula já ficou gravada: se a turma recusar (a
           capacidade, por exemplo), a mensagem diz e a turma se escolhe depois, em Turmas. */
        $turmaId = (int) ($dados['turma_id'] ?? 0);
        if ($turmaId > 0) {
            $t = \App\adms\Models\escola\AdmsTurmas::ver($turmaId);
            if (!$t || (int) $t['ano'] !== $ano || (int) $t['modulo_id'] !== (int) $modulo['id']) {
                $_SESSION['msg'] .= R::aviso('warning', 'A turma escolhida não é da série e do ano da matrícula: o aluno ficou sem turma. Ponha-o numa turma em Turmas.');
            } else {
                $e = \App\adms\Models\escola\AdmsTurmaAlunos::entrar($turmaId, $usuarioId);
                $_SESSION['msg'] .= $e === true ? R::aviso('success', "{$aluno['nome']} entrou na turma {$t['codtur']}, em todas as disciplinas dela.") : R::aviso('warning', (string) $e . ' A matrícula ficou gravada; ponha o aluno numa turma em Turmas.');
            }
        }
        return $this->resultado = $mc;
    }
}
