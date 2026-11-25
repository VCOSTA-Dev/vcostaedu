<?php

namespace App\adms\Models\notas;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\notas\AdmsCalculoNotas as Calc;

/**
 * ENCERRAMENTO DA OFERTA (E4): calcula pelo motor, no modo FINAL, e GRAVA por aluno
 * a média final, a frequência, o resultado e o motivo em rm_disciplina_media_final
 * (é disso que o histórico da E6 lê). Marca a oferta como encerrada — nota, falta e
 * atividade ficam travadas — e registra no histórico da oferta.
 *
 * Recusa: oferta já encerrada; sem total de encontros; sem atividade; sem aluno.
 * Reencerrar (depois de o administrador reabrir) recalcula e regrava.
 *
 * 1.13.0 (E4, D-396): tudo numa TRANSAÇÃO (\Core\Transacao::executar). O encerramento grava um FECHAMENTO
 * (rm_media_fechamentos: número 1, 2, …; a regra EFETIVA copiada em JSON canônico, com o sha256 e a versão usada) e,
 * por aluno, a linha de sempre + o fechamento + a memória de cálculo. Mudar a regra depois não altera o fechado.
 */
class AdmsEncerrarOferta
{
    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    /** Prévia: o cálculo final por aluno e o que impede o encerramento. */
    public function previa(int $ofertaId): array
    {
        $calc = Calc::oferta($ofertaId, true);
        if (!$calc) {
            return [];
        }
        $calc['impedimentos'] = $this->impedimentos($calc);
        return $calc;
    }

    public function encerrar(int $ofertaId)
    {
        $calc = $this->previa($ofertaId);
        if (!$calc) {
            return $this->recusar('Disciplina não encontrada.');
        }
        if ($calc['impedimentos']) {
            return $this->recusar('Encerramento RECUSADO: ' . implode(' ', $calc['impedimentos']));
        }
        $rg = $calc['regras'];
        $ef = $calc['regraEfetiva'];
        $usuario = (int) $_SESSION['colUsuario_id'];
        /* a regra que valeu, COPIADA (com a frequência mínima e a origem): é o que o fechamento guarda */
        $regraJson = \App\adms\Models\historico\AdmsJsonCanonico::codificar(['origem' => $ef['origem'], 'versao' => $ef['versao'], 'versaoId' => $ef['versaoId'],
            'regra' => $ef['regra'], 'frequenciaMinima' => $rg['frequenciaMinima'], 'origemFrequencia' => $rg['origemFrequencia'] ?? 'instituicao']);
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $ofertaId, $calc, $rg, $ef, $regraJson, $usuario) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT COALESCE(MAX(numero), 0) AS n FROM rm_media_fechamentos WHERE rm_prof_mat_id = :o", "o={$ofertaId}");
            $numero = (int) ($ler->getResultado()[0]['n'] ?? 0) + 1;
            $f = new \App\adms\Models\helper\AdmsCreate();
            $f->exeCreate('rm_media_fechamentos', ['rm_prof_mat_id' => $ofertaId, 'numero' => $numero, 'regra_versao_id' => $ef['versaoId'] ?: null,
                'regra_origem' => $ef['origem'], 'regra_json' => $regraJson, 'regra_hash' => hash('sha256', $regraJson), 'situacao' => 'vigente',
                'fechado_por' => $usuario, 'fechado_em' => date('Y-m-d H:i:s')]);
            $fechamento = (int) $T::exigir($f->getResultado(), 'fechamento');
            $del = new \App\adms\Models\helper\AdmsDeletar();
            $del->exeDelete('rm_disciplina_media_final', 'WHERE rm_prof_mat_id = :o', "o={$ofertaId}");
            $T::exigir($del->getResultado() === false ? null : true, 'resultado anterior');
            /* Onda 6, Etapa 4: 200 alunos por comando, na mesma transação (5000 alunos: de 4 s para cerca de 1 s) */
            $linhas = [];
            foreach ($calc['alunos'] as $a) {
                $linhas[] = [
                    'rm' => $a['rm'],
                    'nome_aluno' => $a['nome'],
                    'rm_prof_mat_id' => $ofertaId,
                    'media_final' => $a['media'],
                    'atividades' => $a['atividades'],
                    'notas_lancadas' => $a['lancadas'],
                    'faltas' => $a['faltas'],
                    'faltas_compensadas' => $a['compensadas'],
                    'encontros' => $a['encontros'],
                    'frequencia' => $a['frequencia'],
                    'resultado' => $a['resultado'],
                    'motivo' => $a['motivo'],
                    'casas' => (int) $rg['casas'],
                    'media_minima' => $rg['mediaMinima'],
                    'frequencia_minima' => $rg['frequenciaMinima'],
                    'fechamento_id' => $fechamento,
                    'memoria' => json_encode($a['memoria'] ?? null, JSON_UNESCAPED_UNICODE),
                    'usuario_cre' => $usuario,
                ];
            }
            $pdo = $T::pdo();
            foreach (array_chunk($linhas, 200) as $parte) {
                $colunas = array_keys($parte[0]);
                $marca = '(' . implode(', ', array_fill(0, count($colunas), '?')) . ')';
                $st = $pdo->prepare('INSERT INTO rm_disciplina_media_final (`' . implode('`, `', $colunas) . '`) VALUES ' . implode(', ', array_fill(0, count($parte), $marca)));
                $valores = [];
                foreach ($parte as $l) {
                    array_push($valores, ...array_values($l));
                }
                try {
                    $st->execute($valores);
                } catch (\Throwable $ex) {
                    \VcBanco::anotar($ex);
                    $T::falhar('resultado dos alunos (' . $parte[0]['rm'] . ' a ' . end($parte)['rm'] . ')');
                }
            }
            $up = new \App\adms\Models\helper\AdmsUpdate();
            $up->exeUpdate('rm_professores_materias', ['encerrada_em' => date('Y-m-d H:i:s'), 'encerrada_por' => $usuario], 'WHERE id = :id', "id={$ofertaId}");
            $T::exigir($up->getResultado() === null ? null : true, 'oferta');
            $h = new \App\adms\Models\helper\AdmsCreate();
            $h->exeCreate('rm_professores_materias_encerramentos', ['rm_prof_mat_id' => $ofertaId, 'acao' => 'encerrar', 'observacao' => null, 'usuario_id' => $usuario]);
            $T::exigir($h->getResultado(), 'registro do encerramento');
            \App\adms\Models\helper\AdmsAuditoria::fechamento($ofertaId, 'encerrou'); // Onda 2, Etapa 3
            return true;
        });
        if (!$ok) {
            return $this->recusar('Encerramento NÃO gravado — nada mudou (' . $T::ultimoErro() . ').');
        }
        $aprov = count(array_filter($calc['alunos'], fn($a) => $a['resultado'] === Calc::APROVADO));
        $avisoPrev = \App\adms\Models\faltas\AdmsChamada::avisoPrevistos($ofertaId); // 1.17.2 (D-462): só aviso, não bloqueia
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(382, 200, 3, "Oferta encerrada: id={$ofertaId}; alunos=" . count($calc['alunos']) . "; aprovados={$aprov}");
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
            . "Oferta encerrada: " . count($calc['alunos']) . " aluno(s), {$aprov} aprovado(s). Notas e faltas estão travadas. Regra de média \"" . htmlspecialchars((string) ($ef['nome'] ?? ''), ENT_QUOTES, 'UTF-8') . "\" guardada no encerramento (" . htmlspecialchars(AdmsRegraMedia::ORIGENS[$ef['origem']] ?? '', ENT_QUOTES, 'UTF-8') . ").</div>"
            . ($avisoPrev ? "<div class='alert alert-warning' id='avisoPrevistos'>Encontros: " . htmlspecialchars($avisoPrev, ENT_QUOTES, 'UTF-8') . ' A frequência gravada é sobre os registrados.</div>' : ''); // 1.16.0: sem versão
        return $this->resultado = true;
    }

    private function impedimentos(array $calc): array
    {
        $o = $calc['oferta'];
        $i = [];
        if (!empty($o['encerrada_em'])) {
            $i[] = 'A oferta já está encerrada.';
        }
        if ($o['encontros'] === null || (int) $o['encontros'] < 1) {
            $i[] = ($o['frequencia_fonte'] ?? 'lancamento') === 'chamada' ? 'Registre ao menos um encontro na chamada (sem ele não há frequência).' : 'Informe o total de encontros da oferta (sem ele não há frequência).';
        }
        if (!$calc['atividades']) {
            $i[] = 'A oferta não tem atividade (sem elas não há média).';
        }
        if (!$calc['alunos']) {
            $i[] = 'A oferta não tem aluno enturmado.';
        }
        /* Onda 4, Etapa 3: na escola, o ano (o encerramento da oferta) só fecha com todas as etapas fechadas */
        if (empty($o['encerrada_em']) && \App\adms\Models\notas\AdmsCalculoNotas::turmaDaOferta((int) $o['id'])) {
            $abertas = [];
            foreach (\App\adms\Models\notas\AdmsCalculoNotas::etapasDoAno((int) $o['ano']) as $e) {
                if (!\App\adms\Models\escola\AdmsEtapaNotas::fechamento((int) $o['id'], (int) $e['id'])) {
                    $abertas[] = $e['nome'];
                }
            }
            if ($abertas) {
                $i[] = 'Feche antes as etapas: ' . implode(', ', $abertas) . '.';
            }
        }
        /* E10 (D-423): o PORTÃO da regra — componente obrigatório abaixo do mínimo ou componente acima do máximo, pela
           regra que vai valer no fechamento. Nada é gravado enquanto houver. */
        if (empty($o['encerrada_em'])) {
            $l = AdmsRegraMedia::limitesOferta((int) $o['id']);
            if ($l['faltam']) {
                $i[] = 'Falta cadastrar: ' . implode(', ', $l['faltam']) . '.';
            }
            if ($l['sobram']) {
                $i[] = 'Atividades acima do máximo: ' . implode('; ', $l['sobram']) . '.';
            }
            if (!$l['faltam'] && !$l['sobram'] && array_filter($calc['alunos'], fn($a) => !empty($a['motivosPendencia']))) {
                $i[] = 'Há aluno com a média pendente pela regra de média.';
            }
        }
        return $i;
    }

    private function recusar(string $texto)
    {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: "
            . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</div>';
        return $this->resultado = false;
    }
}
