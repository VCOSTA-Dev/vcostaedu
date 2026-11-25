<?php

namespace App\adms\Models\portalAlunos;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * CONFIRMA a enturmação de uma posição no ano (E3b, D-00c): grava em
 * portal_disciplinas_alunos exatamente as ofertas marcadas, dentre as ofertas
 * elegíveis da posição (mesmo curso, mesma grade, mesmo ano).
 *
 * - Marcada e não enturmada → enturma (sem duplicar: checagem + índice `rm_oferta`).
 * - Enturmada e desmarcada → desenturma, SALVO se já tem nota, falta ou observação:
 *   aí fica, e a tela avisa.
 * - Grava quem confirmou e quando em rm_matriculas_ensino.
 * - E5: oferta ENCERRADA não muda: marcar uma em que o aluno não está é RECUSADO (nada é
 *   gravado) e quem já está nela fica, marcado ou não.
 */
class AdmsConfirmarEnturmacao
{
    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    public function confirmar(int $posicaoId, array $marcadas)
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT me.id, me.ano, me.ensino_id, me.grade_id, me.modulo_id, me.enturmacao_confirmada_em, me.usuario_id, u.rm, u.nome
               FROM rm_matriculas_ensino me INNER JOIN col_usuarios u ON u.id = me.usuario_id
              WHERE me.id = :id LIMIT :limit",
            "id={$posicaoId}&limit=1"
        );
        $pos = $ler->getResultado()[0] ?? null;
        if (!$pos || empty($pos['grade_id'])) {
            return $this->recusar('Posição do aluno no ano não encontrada (vincule o aluno ao curso antes).');
        }
        $elegiveis = AdmsEnturmacaoSugerida::ofertas($pos, (string) $pos['rm']);
        $porId = [];
        foreach ($elegiveis as $o) {
            $porId[(int) $o['id']] = $o;
        }
        $marcadas = array_values(array_unique(array_map('intval', $marcadas)));
        foreach ($marcadas as $id) {
            if (!isset($porId[$id])) {
                return $this->recusar('Há oferta marcada que não é do curso, da grade e do ano do aluno.');
            }
        }

        foreach ($porId as $id => $o) {
            if (!empty($o['encerrada']) && !$o['enturmado'] && in_array($id, $marcadas, true)) {
                return $this->recusar("{$o['materia']}: oferta encerrada — o administrador precisa reabrir. Nada foi gravado.");
            }
        }

        $entrou = 0; $saiu = 0; $mantidas = []; $entraram = []; $sairam = [];
        /* 1.14.0 (E9, D-350, D-414): entradas, saídas, linhas de nota e a confirmação numa TRANSAÇÃO — tudo ou nada */
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $porId, $marcadas, $pos, $posicaoId, &$entrou, &$saiu, &$mantidas, &$entraram, &$sairam) {
        foreach ($porId as $id => $o) {
            if (!empty($o['encerrada'])) {
                continue; // E5: encerrada não muda
            }
            $quer = in_array($id, $marcadas, true);
            if ($quer && !$o['enturmado']) {
                $ler = new \App\adms\Models\helper\AdmsRead();
                $ler->fullRead("SELECT IFNULL(MAX(ordem), 0) + 1 AS prox FROM portal_disciplinas_alunos WHERE rm_prof_mat_id = :o", "o={$id}");
                $add = new \App\adms\Models\helper\AdmsCreate();
                $add->exeCreate('portal_disciplinas_alunos', [
                    'rm' => (string) $pos['rm'],
                    'nome_aluno' => $pos['nome'],
                    'ordem' => (int) ($ler->getResultado()[0]['prox'] ?? 1),
                    'curso_id' => (int) $pos['ensino_id'],
                    'periodo' => (int) ($o['ordem'] ?? 0),
                    'grupo' => 1,
                    'ano' => (int) $pos['ano'],
                    'rm_prof_mat_id' => $id,
                    'situacao' => 1,
                    'usuario_cre' => $_SESSION['colUsuario_id'],
                ]);
                $T::exigir((int) $add->getResultado() > 0 ? true : null, 'enturmação na oferta ' . $id);
                $entrou++;
                $entraram[] = $id;
                // E8c (A12, resolve a 1ª limitação da D-133): o aluno que entra depois das atividades já ganha as linhas de nota
                \App\adms\Models\notas\AdmsAtualizarLista::sincronizar($id);
            } elseif (!$quer && $o['enturmado']) {
                if ($this->temRegistro((string) $pos['rm'], $id, (int) $o['enturmacaoId'])) {
                    $mantidas[] = $o['materia'];
                    continue;
                }
                $del = new \App\adms\Models\helper\AdmsDeletar();
                $del->exeDelete('portal_disciplinas_alunos', 'WHERE id = :id', "id={$o['enturmacaoId']}");
                $T::exigir($del->getResultado() ? true : null, 'saída da oferta ' . $id);
                $saiu++;
                $sairam[] = $id;
            }
        }
        $up = new \App\adms\Models\helper\AdmsUpdate();
        $up->exeUpdate('rm_matriculas_ensino', [
            'enturmacao_confirmada_por' => $_SESSION['colUsuario_id'],
            'enturmacao_confirmada_em' => date('Y-m-d H:i:s'),
            'enturmacao_modulo_id' => (int) $pos['modulo_id'], // E6a
        ], 'WHERE id = :id', "id={$posicaoId}");
        $T::exigir($up->getResultado() === null ? null : true, 'confirmação da enturmação');
        return true;
        });
        if (!$ok) {
            return $this->recusar('Enturmação NÃO gravada — nada mudou (' . $T::ultimoErro() . ').');
        }
        /* Onda 3, Etapa 8: quem entrou e quem saiu vai ao Moodle (no fim da requisição; nunca derruba a enturmação) */
        foreach ($entraram as $of) {
            \App\adms\Models\moodle\AdmsMoodle::anotar('enturmar', (int) $pos['usuario_id'], (int) $of);
        }
        foreach ($sairam as $of) {
            \App\adms\Models\moodle\AdmsMoodle::anotar('desenturmar', (int) $pos['usuario_id'], (int) $of);
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(381, 200, 3,
            "Enturmação confirmada: posicao={$posicaoId}; rm={$pos['rm']}; ano={$pos['ano']}; marcadas=" . implode(',', $marcadas) . "; entrou={$entrou}; saiu={$saiu}; mantidas=" . count($mantidas));
        $msg = "Enturmação de {$pos['nome']} em {$pos['ano']} confirmada: {$entrou} entrada(s), {$saiu} saída(s).";
        $tipo = 'success';
        if ($mantidas) {
            $msg .= ' Mantidas porque já têm nota, falta ou observação: ' . implode(', ', $mantidas) . '.';
            $tipo = 'warning';
        }
        $_SESSION['msg'] = "<div class='alert alert-{$tipo} alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
            . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</div>';
        return $this->resultado = true;
    }

    /** A enturmação já tem nota, falta ou observação? */
    private function temRegistro(string $rm, int $oferta, int $enturmacaoId): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT (SELECT COUNT(*) FROM rm_disciplina_atividade_notas WHERE rm = :r1 AND rm_prof_mat_id = :o1)
                  + (SELECT COUNT(*) FROM rm_disciplina_faltas WHERE rm = :r2 AND rm_prof_mat_id = :o2)
                  + (SELECT COUNT(*) FROM portal_disciplinas_alunos WHERE id = :e AND observacao IS NOT NULL AND observacao <> '') AS n",
            "r1={$rm}&o1={$oferta}&r2={$rm}&o2={$oferta}&e={$enturmacaoId}"
        );
        return (int) ($ler->getResultado()[0]['n'] ?? 0) > 0;
    }

    private function recusar(string $texto)
    {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: "
            . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</div>';
        return $this->resultado = false;
    }
}
