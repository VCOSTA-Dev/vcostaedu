<?php

namespace App\adms\Models\vinculo;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\vinculo\AdmsVinculoRegras as R;

/**
 * Posição do aluno num ANO: grava ou muda o módulo (da grade da matrícula no curso)
 * em rm_matriculas_ensino. Uma linha por matrícula × ano (índice `curso_ano`).
 * `ensino_id` e `periodo` (curso e ordem do módulo) seguem preenchidos para os
 * leitores herdados que ainda os mostram.
 */
class AdmsPosicionarModulo
{
    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    public function posicionar(int $matriculaCursoId, array $dados)
    {
        $mc = R::matriculaCurso($matriculaCursoId);
        if (!$mc) {
            return $this->resultado = R::recusar('Matrícula no curso não encontrada.');
        }
        $semModulos = \App\adms\Models\helper\AdmsModuloInterno::ativo(); // E8c (D-268)
        if ((int) $mc['situacao_id'] !== R::CURSANDO) {
            return $this->resultado = R::recusar($semModulos ? 'Só uma matrícula Cursando é registrada num ano. Altere a situação antes.' : 'Só uma matrícula Cursando muda de módulo. Altere a situação antes.');
        }
        $ano = (int) ($dados['ano'] ?? 0);
        if (!R::anoLetivoExiste($ano)) {
            return $this->resultado = R::recusar('Escolha um ano letivo cadastrado.');
        }
        $modulo = R::modulo($semModulos ? \App\adms\Models\helper\AdmsModuloInterno::garantir((int) $mc['grade_id']) : (int) ($dados['modulo_id'] ?? 0));
        if (!$modulo || (int) $modulo['grade_id'] !== (int) $mc['grade_id']) {
            return $this->resultado = R::recusar('Escolha um módulo da grade da matrícula.');
        }
        if (!$this->gravarPosicao($matriculaCursoId, (int) $mc['usuario_id'], $ano, $modulo, R::CURSANDO, (int) $mc['forma_ingresso_id'])) {
            return $this->resultado = R::recusar('Não foi possível gravar a posição no ano.');
        }
        R::log(377, 3, "Posição no ano: matricula_curso={$matriculaCursoId}; ano={$ano}; modulo_id={$modulo['id']}");
        $_SESSION['msg'] = R::aviso('success', $semModulos ? "{$mc['aluno']}: registrado em {$ano}. Confira a enturmação sugerida." : "{$mc['aluno']}: {$ano} no {$modulo['codnome']}. Confira a enturmação sugerida.");
        return $this->resultado = (int) $mc['usuario_id'];
    }

    /** Insere ou atualiza a linha (matrícula, ano). */
    public function gravarPosicao(int $mcId, int $usuarioId, int $ano, array $modulo, int $situacao, int $tipo): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id FROM rm_matriculas_ensino WHERE matricula_curso_id = :mc AND ano = :a LIMIT :limit", "mc={$mcId}&a={$ano}&limit=1");
        $linha = [
            'ensino_id' => (int) $modulo['curso_id'],
            'periodo' => (int) $modulo['ordem'],
            'grade_id' => (int) $modulo['grade_id'],
            'modulo_id' => (int) $modulo['id'],
            'situacao' => $situacao,
        ];
        if ($atual = $ler->getResultado()) {
            $linha['usuario_mod'] = $_SESSION['colUsuario_id'];
            $up = new \App\adms\Models\helper\AdmsUpdate();
            $up->exeUpdate('rm_matriculas_ensino', $linha, 'WHERE id = :id', "id={$atual[0]['id']}");
            return $up->getResultado() !== null;
        }
        $add = new \App\adms\Models\helper\AdmsCreate();
        $add->exeCreate('rm_matriculas_ensino', $linha + [
            'usuario_id' => $usuarioId,
            'ano' => $ano,
            'matricula_curso_id' => $mcId,
            'tipo_id' => $tipo,
            'plano_antecipacao' => 0,
            'plano_id' => 0,
            'usuario_cre' => $_SESSION['colUsuario_id'],
        ]);
        return (int) $add->getResultado() > 0;
    }
}
