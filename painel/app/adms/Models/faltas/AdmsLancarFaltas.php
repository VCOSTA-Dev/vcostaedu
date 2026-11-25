<?php

namespace App\adms\Models\faltas;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * FALTAS da oferta (E4, etapa única): uma linha por aluno × oferta em
 * rm_disciplina_faltas (faltas e faltas compensadas), lançadas pelo professor ou
 * pela secretaria — o mesmo formato que a importação do Attendance do Moodle vai
 * preencher. Sem chamada, sem bimestre, sem limite cravado (saiu o
 * `curso_id == 1 → 50 faltas` de AdmsAtualizarAlunos).
 *
 * Recusa: oferta encerrada; aluno não enturmado; número inválido; compensadas
 * maiores que as faltas; faltas maiores que o total de encontros.
 */
class AdmsLancarFaltas
{
    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    public function salvar(int $ofertaId, array $dados)
    {
        $oferta = \App\adms\Models\notas\AdmsCalculoNotas::dadosOferta($ofertaId);
        if (!$oferta) {
            return $this->recusar('Disciplina não encontrada.');
        }
        if (!empty($oferta['encerrada_em'])) {
            return $this->recusar('A oferta está ENCERRADA: faltas não podem mudar. Só o administrador reabre.');
        }
        if (($oferta['frequencia_fonte'] ?? 'lancamento') === 'chamada') { // 1.14.0 (D-405): uma fonte só
            return $this->recusar('As faltas desta oferta vêm da CHAMADA: lance as ausências na chamada (ou troque a fonte, sem encontros registrados).');
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT rm, nome_aluno FROM portal_disciplinas_alunos WHERE rm_prof_mat_id = :o AND situacao = :s", "o={$ofertaId}&s=1");
        $enturmados = array_column($ler->getResultado() ?: [], 'nome_aluno', 'rm');
        $faltas = (array) ($dados['faltas'] ?? []);
        /* E8c (D-264): as compensadas NÃO vêm mais deste formulário; são a soma dos lançamentos com justificativa
           (faltas/compensar, só secretaria e administrador). Aqui só se confere que as faltas não ficam abaixo delas. */
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT rm, faltas_compensadas FROM rm_disciplina_faltas WHERE rm_prof_mat_id = :o", "o={$ofertaId}");
        $comp = array_column($ler->getResultado() ?: [], 'faltas_compensadas', 'rm');
        $encontros = $oferta['encontros'] !== null ? (int) $oferta['encontros'] : null;

        $erros = [];
        $linhas = [];
        foreach ($faltas as $rm => $v) {
            $rm = (string) $rm;
            if (!array_key_exists($rm, $enturmados)) {
                $erros[] = "RA {$rm} não está enturmado nesta oferta.";
                continue;
            }
            $f = trim((string) $v);
            $c = (string) (int) ($comp[$rm] ?? 0);
            $f = $f === '' ? '0' : $f;
            if (!preg_match('/^\d{1,3}$/', $f)) {
                $erros[] = "RA {$rm}: as faltas devem ser um número inteiro (0 ou mais).";
                continue;
            }
            if ((int) $c > (int) $f) {
                $erros[] = "RA {$rm}: {$f} falta(s) é menos que as {$c} já compensadas (remova a compensação antes).";
                continue;
            }
            if ($encontros !== null && (int) $f > $encontros) {
                $erros[] = "RA {$rm}: {$f} faltas é mais que o total de {$encontros} encontros.";
                continue;
            }
            $linhas[$rm] = [(int) $f, (int) $c];
        }
        if ($erros) {
            return $this->recusar('Nada foi salvo. ' . implode(' ', $erros));
        }
        /* 1.14.0 (E9, D-414): todas as linhas numa TRANSAÇÃO */
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $linhas, $ofertaId, $enturmados) {
        foreach ($linhas as $rm => [$f, $c]) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT id, faltas FROM rm_disciplina_faltas WHERE rm = :rm AND rm_prof_mat_id = :o LIMIT :limit", "rm={$rm}&o={$ofertaId}&limit=1");
            \App\adms\Models\helper\AdmsAuditoria::falta($ofertaId, (string) $rm, $enturmados[$rm] ?? null, isset($ler->getResultado()[0]) ? (int) $ler->getResultado()[0]['faltas'] : null, (int) $f); // Onda 2, Etapa 3
            if ($id = $ler->getResultado()[0]['id'] ?? null) {
                $up = new \App\adms\Models\helper\AdmsUpdate();
                $up->exeUpdate('rm_disciplina_faltas', ['faltas' => $f, 'usuario_mod' => $_SESSION['colUsuario_id']], 'WHERE id = :id', "id={$id}");
                $T::exigir($up->getResultado() === null ? null : true, 'faltas do RA ' . $rm);
            } else {
                $cr = new \App\adms\Models\helper\AdmsCreate();
                $cr->exeCreate('rm_disciplina_faltas', [
                    'rm' => $rm, 'nome_aluno' => $enturmados[$rm], 'rm_prof_mat_id' => $ofertaId,
                    'faltas' => $f, 'faltas_compensadas' => $c, 'usuario_cre' => $_SESSION['colUsuario_id'],
                ]);
                $T::exigir($cr->getResultado(), 'faltas do RA ' . $rm);
            }
        }
        return true;
        });
        if (!$ok) {
            return $this->recusar('Nada foi salvo (' . $T::ultimoErro() . ').');
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(105, 200, 3, "Faltas lançadas: oferta={$ofertaId}; alunos=" . count($linhas));
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Faltas salvas (" . count($linhas) . " aluno(s)).</div>";
        return $this->resultado = true;
    }

    private function recusar(string $texto)
    {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: "
            . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</div>';
        return $this->resultado = false;
    }
}
