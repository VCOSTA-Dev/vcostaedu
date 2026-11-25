<?php

namespace App\adms\Models\notas;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * ATUALIZAR LISTA da disciplina (E8c, A12 — volta o "Atualiza Lista" do sistema escolar de origem, medias/atualiza-lista).
 *
 * Sincroniza as linhas de nota com a ENTURMAÇÃO: para cada atividade ativa da oferta e cada aluno enturmado
 * (portal_disciplinas_alunos, situação 1) sem linha em rm_disciplina_atividade_notas, cria a linha (nota vazia).
 * Resolve a D-133: antes, o aluno enturmado depois de criada a atividade só ganhava a linha ao abrir a atividade.
 * Não apaga nada e não mexe em nota lançada. Oferta encerrada: recusa (os resultados já estão gravados).
 * Chamado pelo botão "Atualizar lista" (medias/atualizar-lista) e, sem mensagem, depois de confirmar a enturmação.
 */
class AdmsAtualizarLista
{
    /** Devolve quantas linhas criou; null se a oferta não existe ou está encerrada. */
    public static function sincronizar(int $ofertaId): ?int
    {
        $oferta = AdmsCalculoNotas::dadosOferta($ofertaId);
        if (!$oferta || !empty($oferta['encerrada_em'])) {
            return null;
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, peso, tipo FROM rm_disciplina_atividade WHERE rm_prof_mat_id = :o AND situacao = :s", "o={$ofertaId}&s=1");
        $atividades = $ler->getResultado() ?: [];
        $alunos = (new \App\adms\Models\helperApi\AdmsAlunosDisciplinas())->disciplinaAlunos($ofertaId) ?: [];
        if (!$atividades || !$alunos) {
            return 0;
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT atividade_id, rm FROM rm_disciplina_atividade_notas WHERE rm_prof_mat_id = :o", "o={$ofertaId}");
        $tem = [];
        foreach ($ler->getResultado() ?: [] as $n) {
            $tem[(int) $n['atividade_id'] . '|' . $n['rm']] = true;
        }
        $criadas = 0;
        foreach ($atividades as $at) {
            foreach ($alunos as $al) {
                if (isset($tem[(int) $at['id'] . '|' . $al['rm']])) {
                    continue;
                }
                (new \App\adms\Models\helper\AdmsCreate())->exeCreate('rm_disciplina_atividade_notas', [
                    'rm' => $al['rm'], 'nome_aluno' => $al['nome_aluno'], 'ordenacao' => $al['ordem'] ?? 0, 'rm_prof_mat_id' => $ofertaId,
                    'atividade_id' => (int) $at['id'], 'peso' => $at['peso'], 'alteracao' => 1, 'tipo' => $at['tipo'], 'situacao' => 1,
                    'usuario_cre' => (int) ($_SESSION['colUsuario_id'] ?? 0), 'created' => date('Y-m-d H:i:s'),
                ]);
                $criadas++;
            }
        }
        return $criadas;
    }
}
