<?php

namespace App\adms\Models\vinculo;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\vinculo\AdmsVinculoRegras as R;

/**
 * Edita a matrícula no curso: forma e data de ingresso, situação, observação e (E6a) polo,
 * modalidade e data de conclusão. Curso e grade não mudam (para trocar de grade, encerre esta
 * matrícula e crie outra). A situação é copiada para as posições por ano.
 *
 * CONCLUSÃO (E6a): a situação "Concluído" só é aceita com a data de conclusão e se TODAS as
 * disciplinas da grade estiverem Aprovadas (tentativa vigente, em oferta encerrada) e, se o
 * curso exigir, o TCC aprovado — as pendências vêm da fonte única (AdmsVidaAcademica) e a
 * recusa lista o que falta. Fora de "Concluído", a data de conclusão fica vazia.
 */
class AdmsEditarVinculo
{
    public const CONCLUIDO = 2;

    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    public function editar(int $id, array $dados)
    {
        $mc = R::matriculaCurso($id);
        if (!$mc) {
            return $this->resultado = R::recusar('Matrícula no curso não encontrada.');
        }
        /* Onda 8, correção, segunda leva, 2.2 (D-88): na escola, a ficha não mostra a forma de ingresso, o polo nem a
           modalidade (são do ensino superior); a matrícula guarda o que já tinha, sem mudar */
        $escola = \App\adms\Models\helper\AdmsTipoInstituicao::porTurma(); // D-106: e o curso livre
        $forma = $escola ? (int) $mc['forma_ingresso_id'] : (int) ($dados['forma_ingresso_id'] ?? 0);
        $situacao = (int) ($dados['situacao_id'] ?? 0);
        $data = trim((string) ($dados['data_ingresso'] ?? ''));
        if (!R::existe('rm_matricula_tipos', $forma) || !R::existe('rm_matriculas_situacao', $situacao) || !R::dataValida($data)) {
            return $this->resultado = R::recusar($escola ? 'Informe a data da matrícula e a situação válidas.' : 'Informe forma de ingresso, data de ingresso e situação válidas.');
        }
        if ($escola) {
            $polo = $mc['polo_id'] === null ? null : (int) $mc['polo_id'];
            $modalidade = $mc['modalidade_id'] === null ? null : (int) $mc['modalidade_id'];
        } else {
            [$polo, $modalidade] = R::poloModalidade($dados, (int) $mc['curso_id']);
            if ($polo === false || $modalidade === false) {
                return $this->resultado = R::recusar('Polo ou modalidade inválidos.');
            }
        }
        $previsao = R::previsao($dados, $data); // E1b (D-354)
        if ($previsao === false) {
            return $this->resultado = false;
        }
        $conclusao = trim((string) ($dados['data_conclusao'] ?? ''));
        if ($situacao === self::CONCLUIDO) {
            if (!R::dataValida($conclusao)) {
                return $this->resultado = R::recusar('Para "Concluído", informe a data de conclusão.');
            }
            if ($conclusao < $data) {
                return $this->resultado = R::recusar('A data de conclusão não pode ser antes do ingresso.');
            }
            $pend = \App\adms\Models\historico\AdmsVidaAcademica::pendenciasConclusao($id);
            if ($pend) {
                $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible' id='conclusaoRecusada'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button><b>Conclusão RECUSADA</b> — falta:<ul class='mb-0'><li>"
                    . implode('</li><li>', array_map(fn($p) => htmlspecialchars($p, ENT_QUOTES, 'UTF-8'), $pend)) . '</li></ul>Nada foi salvo.</div>';
                return $this->resultado = false;
            }
        } else {
            $conclusao = '';
        }
        /* E1b (D-345): matrícula no curso e posição no ano numa transação só */
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $id, $forma, $data, $situacao, $polo, $modalidade, $conclusao, $previsao, $dados) {
        $up = new \App\adms\Models\helper\AdmsUpdate();
        $up->exeUpdate('rm_matriculas_curso', [
            'forma_ingresso_id' => $forma,
            'data_ingresso' => $data,
            'situacao_id' => $situacao,
            'polo_id' => $polo,
            'modalidade_id' => $modalidade,
            'data_conclusao' => $conclusao === '' ? null : $conclusao,
            'previsao_conclusao' => $previsao === '' ? null : $previsao,
            'observacao' => trim((string) ($dados['observacao'] ?? '')),
            'usuario_mod' => $_SESSION['colUsuario_id'],
        ], 'WHERE id = :id', "id={$id}");
        if ($up->getResultado() === null) {
            $T::falhar('matrícula no curso');
        }
        $pos = new \App\adms\Models\helper\AdmsUpdate();
        $pos->exeUpdate('rm_matriculas_ensino', ['situacao' => $situacao], 'WHERE matricula_curso_id = :mc', "mc={$id}");
        if ($pos->getResultado() === null) {
            $T::falhar('posição no ano');
        }
        return true;
        });
        if (!$ok) {
            return $this->resultado = R::recusar('Não foi possível salvar a matrícula (' . \Core\Transacao::ultimoErro() . '). Nada foi salvo.');
        }
        R::log(376, 3, "Matrícula no curso editada: id={$id}; situacao={$situacao}; forma={$forma}; polo={$polo}; modalidade={$modalidade}" . ($conclusao ? "; conclusao={$conclusao}" : ''));
        \App\adms\Models\moodle\AdmsMoodle::anotar('situacao', (int) $mc['usuario_id']); // Onda 3, Etapa 8: o trancamento, o cancelamento e a reativação no Moodle
        $_SESSION['msg'] = R::aviso('success', "Matrícula de {$mc['aluno']} em {$mc['curso']} atualizada" . ($conclusao ? ': curso CONCLUÍDO em ' . date('d/m/Y', strtotime($conclusao)) : '') . '.');
        return $this->resultado = (int) $mc['usuario_id'];
    }
}
