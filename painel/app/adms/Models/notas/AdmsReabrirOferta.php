<?php

namespace App\adms\Models\notas;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * REABRIR a oferta encerrada (E4): só o ADMINISTRADOR (nível 1), com JUSTIFICATIVA registrada no histórico da oferta.
 *
 * 1.13.0 (E4, D-397): reabrir NÃO APAGA mais o resultado (o defeito apontado no levantamento, F.1). Numa transação:
 * - as linhas de rm_disciplina_media_final vão para rm_media_fechamento_resultados (imutável por gatilho);
 * - o fechamento vigente fica `substituido`, com quem reabriu, quando e a justificativa (encerramento anterior à
 *   1.13.0, sem fechamento gravado: nasce um fechamento "legado" já substituído, com os mínimos das próprias linhas);
 * - a oferta volta a aberta. Reencerrar gera o fechamento seguinte (número 2, 3…), com a regra que valer então.
 * O histórico, o boletim e o portal leem só rm_disciplina_media_final (o fechamento vigente), como antes.
 */
class AdmsReabrirOferta
{
    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    public function reabrir(int $ofertaId, array $dados)
    {
        if (!\App\adms\Models\helper\AdmsNivel::podeSensivel()) { // Onda 2, Etapa 2: ação sensível (Administrador e Supervisão de secretaria)
            return $this->recusar('Só o administrador ou a supervisão da secretaria reabre uma oferta encerrada.');
        }
        $o = AdmsCalculoNotas::dadosOferta($ofertaId);
        if (!$o || empty($o['encerrada_em'])) {
            return $this->recusar('A oferta não está encerrada.');
        }
        $obs = trim((string) ($dados['observacao'] ?? ''));
        if (mb_strlen($obs) < 5) {
            return $this->recusar('Escreva a justificativa da reabertura (ela fica no registro da disciplina).');
        }
        $usuario = (int) $_SESSION['colUsuario_id'];
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $ofertaId, $o, $obs, $usuario) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT * FROM rm_disciplina_media_final WHERE rm_prof_mat_id = :o ORDER BY id", "o={$ofertaId}");
            $linhas = $ler->getResultado() ?: [];
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT * FROM rm_media_fechamentos WHERE rm_prof_mat_id = :o AND situacao = :s ORDER BY numero DESC LIMIT :limit", "o={$ofertaId}&s=vigente&limit=1");
            $fech = $ler->getResultado()[0] ?? null;
            if (!$fech) {
                /* encerramento anterior à 1.13.0: registra o fechamento que existiu (legado), com os mínimos das linhas */
                $ler = new \App\adms\Models\helper\AdmsRead();
                $ler->fullRead("SELECT COALESCE(MAX(numero), 0) AS n FROM rm_media_fechamentos WHERE rm_prof_mat_id = :o", "o={$ofertaId}");
                $l0 = $linhas[0] ?? [];
                $json = \App\adms\Models\historico\AdmsJsonCanonico::codificar(['origem' => 'legado', 'legado' => true,
                    'casas' => isset($l0['casas']) ? (int) $l0['casas'] : null, 'mediaMinima' => $l0['media_minima'] ?? null, 'frequenciaMinima' => $l0['frequencia_minima'] ?? null]);
                $c = new \App\adms\Models\helper\AdmsCreate();
                $c->exeCreate('rm_media_fechamentos', ['rm_prof_mat_id' => $ofertaId, 'numero' => (int) ($ler->getResultado()[0]['n'] ?? 0) + 1, 'regra_versao_id' => null,
                    'regra_origem' => 'legado', 'regra_json' => $json, 'regra_hash' => hash('sha256', $json), 'situacao' => 'vigente',
                    'fechado_por' => $o['encerrada_por'] ?: null, 'fechado_em' => $o['encerrada_em']]);
                $fech = ['id' => (int) $T::exigir($c->getResultado(), 'fechamento legado')];
            }
            foreach ($linhas as $l) {
                $c = new \App\adms\Models\helper\AdmsCreate();
                $c->exeCreate('rm_media_fechamento_resultados', [
                    'fechamento_id' => (int) $fech['id'], 'media_final_id' => (int) $l['id'], 'rm' => $l['rm'], 'nome_aluno' => $l['nome_aluno'],
                    'rm_prof_mat_id' => $ofertaId, 'media_final' => $l['media_final'], 'atividades' => $l['atividades'], 'notas_lancadas' => $l['notas_lancadas'],
                    'faltas' => $l['faltas'], 'faltas_compensadas' => $l['faltas_compensadas'], 'encontros' => $l['encontros'], 'frequencia' => $l['frequencia'],
                    'resultado' => $l['resultado'], 'motivo' => $l['motivo'], 'casas' => $l['casas'], 'media_minima' => $l['media_minima'],
                    'frequencia_minima' => $l['frequencia_minima'], 'memoria' => $l['memoria'] ?? null, 'usuario_cre' => $l['usuario_cre'], 'created' => $l['created'],
                ]);
                $T::exigir($c->getResultado(), 'guardar o resultado do RA ' . $l['rm']);
            }
            $d = new \App\adms\Models\helper\AdmsDeletar();
            $d->exeDelete('rm_disciplina_media_final', 'WHERE rm_prof_mat_id = :o', "o={$ofertaId}");
            $T::exigir($d->getResultado() === false ? null : true, 'tirar o resultado vigente');
            $u = new \App\adms\Models\helper\AdmsUpdate();
            $u->exeUpdate('rm_media_fechamentos', ['situacao' => 'substituido', 'reaberto_por' => $usuario, 'reaberto_em' => date('Y-m-d H:i:s'), 'justificativa_reabertura' => $obs],
                'WHERE id = :id', "id={$fech['id']}");
            $T::exigir($u->getResultado() === null ? null : true, 'marcar o fechamento como substituído');
            $u = new \App\adms\Models\helper\AdmsUpdate();
            $u->exeUpdate('rm_professores_materias', ['encerrada_em' => null, 'encerrada_por' => null], 'WHERE id = :id', "id={$ofertaId}");
            $T::exigir($u->getResultado() === null ? null : true, 'reabrir a oferta');
            $c = new \App\adms\Models\helper\AdmsCreate();
            $c->exeCreate('rm_professores_materias_encerramentos', ['rm_prof_mat_id' => $ofertaId, 'acao' => 'reabrir', 'observacao' => $obs, 'usuario_id' => $usuario]);
            $T::exigir($c->getResultado(), 'registro da reabertura');
            \App\adms\Models\helper\AdmsAuditoria::fechamento($ofertaId, 'reabriu', $obs); // Onda 2, Etapa 3
            return true;
        });
        if (!$ok) {
            return $this->recusar('Reabertura NÃO gravada — nada mudou (' . $T::ultimoErro() . ').');
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(383, 200, 3, "Oferta reaberta: id={$ofertaId}");
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Oferta reaberta. O resultado anterior ficou guardado como fechamento substituído. Notas e faltas voltam a ser lançadas; reencerre para gravar o resultado novo.</div>";
        return $this->resultado = true;
    }

    private function recusar(string $texto)
    {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: "
            . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</div>';
        return $this->resultado = false;
    }
}
