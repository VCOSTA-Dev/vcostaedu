<?php

namespace App\adms\Models\faltas;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\notas\AdmsCalculoNotas as Calc;

/**
 * CHAMADA da oferta (E7, 1.14.0, D-405 a D-408).
 *
 * - FONTE da frequência por oferta (`rm_professores_materias.frequencia_fonte`): `lancamento` (faltas digitadas ou
 *   importadas — o de sempre) ou `chamada`. Uma fonte só por oferta: não há contagem dupla.
 * - A chamada é por ENCONTRO da oferta (data e horário opcional), com presença binária por aluno enturmado.
 * - SEM CÁLCULO PARALELO: a chamada só escreve o que o motor de média já lê — `rm_disciplina_faltas.faltas` (as
 *   ausências de cada aluno) e o total de encontros da oferta (`rm_professores_materias.encontros`, o número de encontros
 *   registrados). A frequência, o FS/FI e o resultado continuam saindo de core/MotorMedia.php, como antes:
 *   1 − (faltas − compensadas) ÷ encontros. A compensação de faltas continua valendo igual.
 * - Gravações numa transação (encontro + presenças + consolidação). Oferta encerrada: só leitura (a trava de sempre);
 *   reaberta, volta a ser editável.
 * Defeitos do sistema escolar de origem que NÃO voltaram (levantamento D.1): coluna vinda do GET no SQL, p1..p6, bimestre, compensadas
 * subtraídas duas vezes.
 */
class AdmsChamada
{
    public const FONTES = ['lancamento' => 'Lançamento de faltas', 'chamada' => 'Chamada por encontro'];

    /** O nome da fonte para a tela: na escola, a chamada é por aula (D-103, C2). */
    public static function rotuloFonte(string $fonte): string
    {
        if ($fonte === 'chamada' && \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola()) {
            return 'Chamada por aula';
        }
        return self::FONTES[$fonte] ?? $fonte;
    }

    private array $erros = [];
    private int $id = 0;

    public function getErros(): array
    {
        return $this->erros;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /** A fonte da frequência da oferta (`lancamento` se a coluna não existir ou estiver vazia). */
    public static function fonte(int $ofertaId): string
    {
        $o = Calc::dadosOferta($ofertaId);
        return ($o['frequencia_fonte'] ?? 'lancamento') === 'chamada' ? 'chamada' : 'lancamento';
    }

    /**
     * 1.17.2 (D-462): oferta por chamada com ENCONTROS PREVISTOS e um número diferente de encontros registrados →
     * "Registrados X de Y previstos." (aviso do encerramento; não bloqueia). Sem previstos, ou lançamento: null.
     */
    public static function avisoPrevistos(int $ofertaId): ?string
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT o.frequencia_fonte, o.encontros_previstos, (SELECT COUNT(*) FROM rm_oferta_encontros e WHERE e.rm_prof_mat_id = o.id) AS registrados
               FROM rm_professores_materias o WHERE o.id = :o LIMIT :limit",
            "o={$ofertaId}&limit=1"
        );
        $o = $ler->getResultado()[0] ?? null;
        if (!$o || $o['frequencia_fonte'] !== 'chamada' || $o['encontros_previstos'] === null || (int) $o['registrados'] === (int) $o['encontros_previstos']) {
            return null;
        }
        return "Registrados {$o['registrados']} de {$o['encontros_previstos']} previstos.";
    }

    /** Os encontros da oferta (mais recentes primeiro), com quantos presentes e ausentes. */
    public static function encontros(int $ofertaId): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT e.*, (SELECT COUNT(*) FROM rm_oferta_presencas p WHERE p.encontro_id = e.id AND p.presente = 1) AS presentes,
                    (SELECT COUNT(*) FROM rm_oferta_presencas p WHERE p.encontro_id = e.id AND p.presente = 0) AS ausentes
               FROM rm_oferta_encontros e WHERE e.rm_prof_mat_id = :o ORDER BY e.data DESC, e.hora_inicio DESC, e.id DESC",
            "o={$ofertaId}"
        );
        return $ler->getResultado() ?: [];
    }

    public static function encontro(int $id): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT * FROM rm_oferta_encontros WHERE id = :id LIMIT :limit", "id={$id}&limit=1");
        $e = $ler->getResultado()[0] ?? null;
        if ($e) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT rm, presente FROM rm_oferta_presencas WHERE encontro_id = :e", "e={$id}");
            $e['presencas'] = array_map('intval', array_column($ler->getResultado() ?: [], 'presente', 'rm'));
        }
        return $e;
    }

    /** Os alunos enturmados (a lista da chamada), por nome. */
    public static function alunos(int $ofertaId): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT rm, nome_aluno FROM portal_disciplinas_alunos WHERE rm_prof_mat_id = :o AND situacao = :s ORDER BY nome_aluno", "o={$ofertaId}&s=1");
        return $ler->getResultado() ?: [];
    }

    /** Recusa geral: oferta inexistente ou encerrada (só leitura). */
    private function podeMudar(int $ofertaId): ?array
    {
        $o = Calc::dadosOferta($ofertaId);
        if (!$o) {
            $this->erros[] = 'Disciplina não encontrada.';
            return null;
        }
        if (!empty($o['encerrada_em'])) {
            $this->erros[] = 'A oferta está ENCERRADA: a chamada fica só para leitura. O administrador reabre, com justificativa.';
            return null;
        }
        return $o;
    }

    /**
     * SALVAR o encontro (novo, ou o `$encontroId`) e as presenças: `data` (AAAA-MM-DD), `hora_inicio`/`hora_fim`
     * (opcionais), `titulo`, `conteudo` e `ausente[RA] = 1` para quem faltou (o padrão é todos presentes).
     */
    public function salvar(int $ofertaId, int $encontroId, array $d, int $usuario): bool
    {
        $this->erros = [];
        $o = $this->podeMudar($ofertaId);
        if (!$o) {
            return false;
        }
        if (self::fonte($ofertaId) !== 'chamada') {
            $this->erros[] = 'A frequência desta oferta é por lançamento de faltas. Para fazer chamada, troque a fonte para "' . self::rotuloFonte('chamada') . '" (sem faltas lançadas).';
            return false;
        }
        if ($encontroId) {
            $e = self::encontro($encontroId);
            if (!$e || (int) $e['rm_prof_mat_id'] !== $ofertaId) {
                $this->erros[] = 'Encontro não encontrado nesta oferta.';
                return false;
            }
        }
        $data = trim((string) ($d['data'] ?? ''));
        $dt = \DateTime::createFromFormat('Y-m-d', $data);
        if (!$dt || $dt->format('Y-m-d') !== $data) {
            $this->erros[] = 'Informe a data do encontro.';
        }
        $horas = [];
        foreach (['hora_inicio' => 'início', 'hora_fim' => 'fim'] as $k => $rot) {
            $hv = trim((string) ($d[$k] ?? ''));
            if ($hv !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hv)) {
                $this->erros[] = "Horário de {$rot} inválido (use HH:MM).";
            }
            $horas[$k] = $hv === '' ? null : $hv . ':00';
        }
        if ($horas['hora_inicio'] && $horas['hora_fim'] && $horas['hora_fim'] <= $horas['hora_inicio']) {
            $this->erros[] = 'O horário de fim deve ser depois do início.';
        }
        /* Onda 6, Etapa 5: na escola, a aula conta na etapa da data dela; etapa fechada não muda (nem a data nova, nem a de antes) */
        if (!$this->erros) {
            $trava = \App\adms\Models\escola\AdmsEtapaNotas::travaData($ofertaId, $data)
                ?? ($encontroId ? \App\adms\Models\escola\AdmsEtapaNotas::travaData($ofertaId, (string) ($e['data'] ?? '')) : null);
            if ($trava !== null) {
                $this->erros[] = $trava;
            }
        }
        $titulo = trim((string) ($d['titulo'] ?? ''));
        if (mb_strlen($titulo) > 150) {
            $this->erros[] = 'Título com até 150 caracteres.';
        }
        $alunos = self::alunos($ofertaId);
        if (!$alunos) {
            $this->erros[] = 'A oferta não tem aluno enturmado.';
        }
        $ausentes = array_map('strval', array_keys(array_filter((array) ($d['ausente'] ?? []), fn($v) => (string) $v === '1')));
        $rms = array_map('strval', array_column($alunos, 'rm'));
        foreach ($ausentes as $rm) {
            if (!in_array($rm, $rms, true)) {
                $this->erros[] = "RA {$rm} não está enturmado nesta oferta.";
            }
        }
        if ($this->erros) {
            return false;
        }
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $ofertaId, $encontroId, $data, $horas, $titulo, $d, $rms, $ausentes, $usuario) {
            $campos = ['data' => $data, 'hora_inicio' => $horas['hora_inicio'], 'hora_fim' => $horas['hora_fim'], 'titulo' => $titulo === '' ? null : $titulo,
                'conteudo' => trim((string) ($d['conteudo'] ?? '')) ?: null];
            /* Onda 2, Etapa 3: as presenças de antes (encontro existente), para a auditoria */
            $presAntes = [];
            if ($encontroId) {
                $lp = new \App\adms\Models\helper\AdmsRead();
                $lp->fullRead("SELECT rm, presente FROM rm_oferta_presencas WHERE encontro_id = :e", "e={$encontroId}");
                foreach ($lp->getResultado() ?: [] as $pp) {
                    $presAntes[(string) $pp['rm']] = (int) $pp['presente'] === 1;
                }
            }
            if ($encontroId) {
                $u = new \App\adms\Models\helper\AdmsUpdate();
                $u->exeUpdate('rm_oferta_encontros', $campos + ['usuario_mod' => $usuario], 'WHERE id = :id', "id={$encontroId}");
                $T::exigir($u->getResultado() === null ? null : true, 'encontro');
                $del = new \App\adms\Models\helper\AdmsDeletar();
                $del->exeDelete('rm_oferta_presencas', 'WHERE encontro_id = :e', "e={$encontroId}");
                $T::exigir($del->getResultado() === false ? null : true, 'presenças antigas');
                $id = $encontroId;
            } else {
                $c = new \App\adms\Models\helper\AdmsCreate();
                $c->exeCreate('rm_oferta_encontros', $campos + ['rm_prof_mat_id' => $ofertaId, 'usuario_cre' => $usuario]);
                $id = (int) $T::exigir($c->getResultado(), 'encontro');
            }
            foreach ($rms as $rm) {
                $c = new \App\adms\Models\helper\AdmsCreate();
                $c->exeCreate('rm_oferta_presencas', ['encontro_id' => $id, 'rm' => $rm, 'presente' => in_array($rm, $ausentes, true) ? 0 : 1, 'usuario_cre' => $usuario]);
                $T::exigir($c->getResultado(), 'presença do RA ' . $rm);
                \App\adms\Models\helper\AdmsAuditoria::presenca($ofertaId, (int) $id, $data, $rm, null, $presAntes[$rm] ?? null, !in_array($rm, $ausentes, true));
            }
            self::consolidar($ofertaId, $usuario);
            return $id;
        });
        if (!$ok) {
            $this->erros[] = 'A gravação foi desfeita: ' . $T::ultimoErro() . '.';
            return false;
        }
        $this->id = (int) $ok;
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(422, 200, 3, "Chamada salva: oferta={$ofertaId}; encontro={$this->id}; ausentes=" . count($ausentes));
        return true;
    }

    /**
     * Onda 8, correção, Parte 2.2 (D-75): UM clique da chamada, gravado na hora — a presença de um aluno num encontro que
     * já existe. As mesmas regras do salvar: a oferta encerrada e a etapa fechada recusam, o aluno tem de estar
     * enturmado; numa transação (o encontro travado: dois cliques juntos não se atropelam), a presença, a auditoria
     * (o antes e o depois) e a consolidação das faltas (a etapa, na escola). Clicar de novo no que já está gravado não
     * grava nada. @return bool (o motivo da recusa em getErros())
     */
    public function presenca(int $ofertaId, int $encontroId, string $rm, bool $presente, int $usuario): bool
    {
        $this->erros = [];
        if (!$this->podeMudar($ofertaId)) {
            return false;
        }
        if (self::fonte($ofertaId) !== 'chamada') {
            $this->erros[] = 'A frequência desta oferta é por lançamento de faltas: a chamada não grava.';
            return false;
        }
        $e = self::encontro($encontroId);
        if (!$e || (int) $e['rm_prof_mat_id'] !== $ofertaId) {
            $this->erros[] = 'Encontro não encontrado nesta oferta.';
            return false;
        }
        if (!in_array($rm, array_map('strval', array_column(self::alunos($ofertaId), 'rm')), true)) {
            $this->erros[] = "RA {$rm} não está enturmado nesta oferta.";
            return false;
        }
        if (($trava = \App\adms\Models\escola\AdmsEtapaNotas::travaData($ofertaId, (string) $e['data'])) !== null) {
            $this->erros[] = $trava;
            return false;
        }
        $T = \Core\Transacao::class;
        $mudou = false;
        $ok = $T::executar(function () use ($T, $ofertaId, $encontroId, $rm, $presente, $usuario, $e, &$mudou) {
            $pdo = \App\adms\Models\escola\AdmsEscolaBanco::pdo();
            $pdo->prepare('SELECT id FROM rm_oferta_encontros WHERE id = ? FOR UPDATE')->execute([$encontroId]);
            $q = $pdo->prepare('SELECT presente FROM rm_oferta_presencas WHERE encontro_id = ? AND rm = ?');
            $q->execute([$encontroId, $rm]);
            $v = $q->fetchColumn();
            $antes = $v === false ? null : (int) $v === 1;
            if ($antes === $presente) {
                return true;
            }
            $pdo->prepare('INSERT INTO rm_oferta_presencas (encontro_id, rm, presente, usuario_cre) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE presente = VALUES(presente)')
                ->execute([$encontroId, $rm, $presente ? 1 : 0, $usuario]);
            \App\adms\Models\helper\AdmsAuditoria::presenca($ofertaId, $encontroId, (string) $e['data'], $rm, null, $antes, $presente);
            self::consolidar($ofertaId, $usuario);
            $mudou = true;
            return true;
        });
        if (!$ok) {
            $this->erros[] = 'Nada foi gravado: ' . $T::ultimoErro() . '.';
            return false;
        }
        if ($mudou) {
            (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(422, 200, 3, "Chamada, um clique: oferta={$ofertaId}; encontro={$encontroId}; rm={$rm}; " . ($presente ? 'presente' : 'ausente'));
        }
        return true;
    }

    /** O jeito de começar a chamada de cada professor ("presentes" ou "ausentes"), lembrado do último encontro criado. */
    public static function inicio(int $usuario): string
    {
        return \App\adms\Models\helper\AdmsAmbiente::estado('chamada.inicio.' . $usuario, 'presentes') === 'ausentes' ? 'ausentes' : 'presentes';
    }

    public static function lembrarInicio(int $usuario, string $inicio): void
    {
        if (in_array($inicio, ['presentes', 'ausentes'], true) && $inicio !== self::inicio($usuario)) {
            \App\adms\Models\helper\AdmsAmbiente::gravarEstado('chamada.inicio.' . $usuario, $inicio);
        }
    }

    /** EXCLUIR um encontro (e as presenças dele), recalculando faltas e encontros. */
    public function excluir(int $encontroId, int $usuario): int
    {
        $this->erros = [];
        $e = self::encontro($encontroId);
        if (!$e) {
            $this->erros[] = 'Encontro não encontrado.';
            return 0;
        }
        $ofertaId = (int) $e['rm_prof_mat_id'];
        if (!$this->podeMudar($ofertaId)) {
            return $ofertaId;
        }
        if ($trava = \App\adms\Models\escola\AdmsEtapaNotas::travaData($ofertaId, (string) ($e['data'] ?? ''))) { // a escola (Onda 6, Etapa 5)
            $this->erros[] = $trava;
            return $ofertaId;
        }
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $encontroId, $ofertaId, $usuario, $e) {
            /* Onda 2, Etapa 3: cada presença apagada fica na auditoria */
            $lp = new \App\adms\Models\helper\AdmsRead();
            $lp->fullRead("SELECT rm, presente FROM rm_oferta_presencas WHERE encontro_id = :e", "e={$encontroId}");
            foreach ($lp->getResultado() ?: [] as $pp) {
                \App\adms\Models\helper\AdmsAuditoria::presenca($ofertaId, $encontroId, $e['data'] ?? null, (string) $pp['rm'], null, (int) $pp['presente'] === 1, null);
            }
            $d = new \App\adms\Models\helper\AdmsDeletar();
            $d->exeDelete('rm_oferta_presencas', 'WHERE encontro_id = :e', "e={$encontroId}");
            $T::exigir($d->getResultado() === false ? null : true, 'presenças');
            $d = new \App\adms\Models\helper\AdmsDeletar();
            $d->exeDelete('rm_oferta_encontros', 'WHERE id = :id', "id={$encontroId}");
            $T::exigir($d->getResultado() === false ? null : true, 'encontro');
            self::consolidar($ofertaId, $usuario);
            return true;
        });
        if (!$ok) {
            $this->erros[] = 'Nada foi excluído: ' . $T::ultimoErro() . '.';
            return $ofertaId;
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(423, 200, 3, "Encontro excluído: oferta={$ofertaId}; encontro={$encontroId}");
        return $ofertaId;
    }

    /**
     * Dentro da transação: o total de encontros REGISTRADOS vira o total de encontros da oferta (NULL sem nenhum) e as
     * ausências de cada aluno viram as faltas dele. Recusa (desfaz tudo) se as ausências ficarem abaixo das faltas já
     * compensadas.
     */
    private static function consolidar(int $ofertaId, int $usuario): void
    {
        if (Calc::turmaDaOferta($ofertaId)) {
            self::consolidarEscola($ofertaId);
            return;
        }
        $T = \Core\Transacao::class;
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT COUNT(*) AS n FROM rm_oferta_encontros WHERE rm_prof_mat_id = :o", "o={$ofertaId}");
        $n = (int) ($ler->getResultado()[0]['n'] ?? 0);
        $u = new \App\adms\Models\helper\AdmsUpdate();
        $u->exeUpdate('rm_professores_materias', ['encontros' => $n > 0 ? $n : null], 'WHERE id = :id', "id={$ofertaId}");
        $T::exigir($u->getResultado() === null ? null : true, 'total de encontros');
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT p.rm, SUM(p.presente = 0) AS ausencias FROM rm_oferta_presencas p INNER JOIN rm_oferta_encontros e ON e.id = p.encontro_id
                         WHERE e.rm_prof_mat_id = :o GROUP BY p.rm", "o={$ofertaId}");
        $aus = array_map('intval', array_column($ler->getResultado() ?: [], 'ausencias', 'rm'));
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, rm, faltas, faltas_compensadas FROM rm_disciplina_faltas WHERE rm_prof_mat_id = :o", "o={$ofertaId}");
        $linhas = [];
        foreach ($ler->getResultado() ?: [] as $l) {
            $linhas[(string) $l['rm']] = $l;
        }
        foreach (self::alunos($ofertaId) as $a) {
            $rm = (string) $a['rm'];
            $f = $aus[$rm] ?? 0;
            \App\adms\Models\helper\AdmsAuditoria::falta($ofertaId, $rm, $a['nome_aluno'] ?? null, isset($linhas[$rm]) ? (int) $linhas[$rm]['faltas'] : null, $f); // Onda 2, Etapa 3
            if (isset($linhas[$rm])) {
                if ((int) $linhas[$rm]['faltas_compensadas'] > $f) {
                    $T::falhar("RA {$rm}: {$f} ausência(s) é menos que as " . (int) $linhas[$rm]['faltas_compensadas'] . ' falta(s) já compensada(s) — remova a compensação antes');
                }
                $up = new \App\adms\Models\helper\AdmsUpdate();
                $up->exeUpdate('rm_disciplina_faltas', ['faltas' => $f, 'usuario_mod' => $usuario], 'WHERE id = :id', "id={$linhas[$rm]['id']}");
                $T::exigir($up->getResultado() === null ? null : true, 'faltas do RA ' . $rm);
            } else {
                $c = new \App\adms\Models\helper\AdmsCreate();
                $c->exeCreate('rm_disciplina_faltas', ['rm' => $rm, 'nome_aluno' => $a['nome_aluno'], 'rm_prof_mat_id' => $ofertaId, 'faltas' => $f, 'faltas_compensadas' => 0, 'usuario_cre' => $usuario]);
                $T::exigir($c->getResultado(), 'faltas do RA ' . $rm);
            }
        }
    }

    /**
     * NA ESCOLA (Onda 6, Etapa 5; D-55): a chamada por aula vira as faltas DA ETAPA (`esc_faltas_etapa`): as ausências de
     * cada aluno nas aulas cuja data cai na etapa (a etapa da data: a última que começou até ela). O total do componente
     * (`rm_disciplina_faltas`) é a soma das etapas, como no lançamento por etapa; os encontros do componente continuam
     * sendo as horas da matriz (o total do ano não muda de regra). Dentro da transação.
     */
    private static function consolidarEscola(int $ofertaId): void
    {
        $T = \Core\Transacao::class;
        $o = Calc::dadosOferta($ofertaId);
        $etapas = Calc::etapasDoAno((int) ($o['ano'] ?? 0));
        if (!$etapas) {
            $T::falhar('o ano letivo da escola não tem etapas (Ano letivo e etapas)');
        }
        $pdo = \App\adms\Models\escola\AdmsEscolaBanco::pdo();
        $st = $pdo->prepare('SELECT e.data, p.rm, p.presente FROM rm_oferta_presencas p INNER JOIN rm_oferta_encontros e ON e.id = p.encontro_id WHERE e.rm_prof_mat_id = ?');
        $st->execute([$ofertaId]);
        $porEtapa = []; // etapa => rm => ausências
        foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $p) {
            $et = Calc::etapaDaData($etapas, (string) $p['data']);
            $porEtapa[(int) $et['id']][(string) $p['rm']] = ($porEtapa[(int) $et['id']][(string) $p['rm']] ?? 0) + ((int) $p['presente'] === 0 ? 1 : 0);
        }
        $nomes = array_column($etapas, 'nome', 'id');
        foreach (self::alunos($ofertaId) as $a) {
            $rm = (string) $a['rm'];
            $total = 0;
            foreach ($etapas as $et) {
                $n = (int) ($porEtapa[(int) $et['id']][$rm] ?? 0);
                $total += $n;
                $pdo->prepare('INSERT INTO esc_faltas_etapa (oferta_id, etapa_id, rm, faltas) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE faltas = VALUES(faltas)')
                    ->execute([$ofertaId, (int) $et['id'], $rm, $n]);
            }
            $st = $pdo->prepare('SELECT faltas, faltas_compensadas FROM rm_disciplina_faltas WHERE rm_prof_mat_id = ? AND rm = ?');
            $st->execute([$ofertaId, $rm]);
            $antes = $st->fetch(\PDO::FETCH_ASSOC);
            if ($antes && (int) $antes['faltas_compensadas'] > $total) {
                $T::falhar("RA {$rm}: {$total} ausência(s) é menos que as " . (int) $antes['faltas_compensadas'] . ' falta(s) já compensada(s) — remova a compensação antes');
            }
            if (!$antes) {
                $pdo->prepare('INSERT INTO rm_disciplina_faltas (rm, nome_aluno, rm_prof_mat_id, faltas, faltas_compensadas, usuario_cre) VALUES (?, ?, ?, ?, 0, ?)')
                    ->execute([$rm, $a['nome_aluno'], $ofertaId, $total, (int) ($_SESSION['colUsuario_id'] ?? 0)]);
            } elseif ((int) $antes['faltas'] !== $total) {
                $pdo->prepare('UPDATE rm_disciplina_faltas SET faltas = ? WHERE rm_prof_mat_id = ? AND rm = ?')->execute([$total, $ofertaId, $rm]);
            }
            if ($antes ? (int) $antes['faltas'] !== $total : $total !== 0) {
                \App\adms\Models\helper\AdmsAuditoria::falta($ofertaId, $rm, $a['nome_aluno'] ?? null, $antes ? (int) $antes['faltas'] : null, $total, 'faltas', 'chamada, por etapa: '
                    . implode(', ', array_map(fn($et) => $nomes[(int) $et['id']] . ' ' . (int) ($porEtapa[(int) $et['id']][$rm] ?? 0), $etapas)));
            }
        }
    }

    /**
     * TROCAR a fonte (oferta aberta). Para `chamada`: recusada se há falta lançada (> 0). Para `lancamento`: recusada se
     * há encontro registrado. A mensagem diz o porquê; nada muda.
     */
    /**
     * Por que a disciplina não pode passar a fazer a chamada (null = pode): há falta lançada. Na escola (correção da
     * escola, Parte 2, B9; D-101), conta também a falta lançada por etapa: a primeira aula da chamada recalcula a falta de
     * cada etapa pelas aulas (consolidarEscola), e a lançada à mão se perderia. O texto vale para o ensino superior como
     * antes.
     */
    public static function motivoSemChamada(int $ofertaId): ?string
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT COUNT(*) AS n FROM rm_disciplina_faltas WHERE rm_prof_mat_id = :o AND faltas > 0", "o={$ofertaId}");
        $n = (int) ($ler->getResultado()[0]['n'] ?? 0);
        $escola = Calc::turmaDaOferta($ofertaId) !== null;
        if ($escola) {
            $ler->fullRead("SELECT COUNT(DISTINCT rm) AS n FROM esc_faltas_etapa WHERE oferta_id = :o AND faltas > 0", "o={$ofertaId}");
            $n = max($n, (int) ($ler->getResultado()[0]['n'] ?? 0));
        }
        if (!$n) {
            return null;
        }
        if ($escola) {
            return 'Não dá para passar a fazer a chamada: há falta lançada na etapa para ' . ($n === 1 ? '1 aluno' : "{$n} alunos")
                . '. A chamada recalcula a falta de cada etapa pelas aulas, e a lançada se perderia; continue lançando as faltas na tela das etapas, ou zere as lançadas antes de trocar.';
        }
        return "Não dá para trocar para chamada: há falta lançada para {$n} aluno(s). Uma fonte só por oferta evita contar a falta duas vezes; zere as faltas lançadas antes.";
    }

    public function trocarFonte(int $ofertaId, string $nova, int $usuario): bool
    {
        $this->erros = [];
        if (!isset(self::FONTES[$nova])) {
            $this->erros[] = 'Fonte inválida.';
            return false;
        }
        if (!$this->podeMudar($ofertaId)) {
            return false;
        }
        if (self::fonte($ofertaId) === $nova) {
            return true;
        }
        if ($nova === 'chamada') {
            if (($motivo = self::motivoSemChamada($ofertaId)) !== null) {
                $this->erros[] = $motivo;
                return false;
            }
        } else {
            $n = count(self::encontros($ofertaId));
            if ($n) {
                $this->erros[] = "Não dá para voltar ao lançamento de faltas: há {$n} encontro(s) registrado(s) na chamada. Exclua os encontros antes.";
                return false;
            }
        }
        $u = new \App\adms\Models\helper\AdmsUpdate();
        $u->exeUpdate('rm_professores_materias', ['frequencia_fonte' => $nova], 'WHERE id = :id', "id={$ofertaId}");
        if ($u->getResultado() === null) {
            $this->erros[] = 'Não foi possível trocar a fonte.';
            return false;
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(424, 200, 3, "Fonte da frequência: oferta={$ofertaId}; agora={$nova}");
        return true;
    }
}
