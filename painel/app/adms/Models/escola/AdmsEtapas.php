<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsRead;

/**
 * O ANO LETIVO DA ESCOLA E AS SUAS ETAPAS (docs/ESCOLA.md). Ao abrir o ano, a escola escolhe o modelo: quatro bimestres
 * (o padrão) ou três trimestres; as etapas nascem com nome, ordem, datas e a data do conselho, e a escola ajusta as
 * datas. Depois que há lançamento (nota ou falta) numa oferta do ano, o modelo não muda.
 */
class AdmsEtapas
{
    public const MODELOS = [
        'bimestre' => ['quantas' => 4, 'nome' => 'Quatro bimestres', 'etapa' => 'bimestre'],
        'trimestre' => ['quantas' => 3, 'nome' => 'Três trimestres', 'etapa' => 'trimestre'],
    ];

    /** O ano letivo da escola (o modelo e as etapas), ou null se ainda não foi aberto. */
    public static function doAno(int $ano): ?array
    {
        $ler = new AdmsRead();
        $ler->fullRead('SELECT ano, modelo, aberto_em FROM esc_anos WHERE ano = :a LIMIT :limit', "a={$ano}&limit=1");
        $a = $ler->getResultado()[0] ?? null;
        if (!$a) {
            return null;
        }
        $ler = new AdmsRead();
        $ler->fullRead('SELECT id, ordem, nome, inicio, fim, conselho FROM esc_etapas WHERE ano = :a ORDER BY ordem', "a={$ano}");
        $a['etapas'] = $ler->getResultado() ?: [];
        $a['lancamento'] = self::temLancamento($ano);
        return $a;
    }

    /** Os anos letivos cadastrados, com o da escola de cada um (aberto ou não). */
    public static function anos(): array
    {
        $ler = new AdmsRead();
        $ler->fullRead('SELECT ano FROM adms_periodos_letivos ORDER BY ano DESC');
        return array_map(static fn($r) => ['ano' => (int) $r['ano'], 'escola' => self::doAno((int) $r['ano'])], $ler->getResultado() ?: []);
    }

    /** Há nota ou falta lançada numa oferta do ano? */
    public static function temLancamento(int $ano): bool
    {
        $ler = new AdmsRead();
        $ler->fullRead("SELECT (SELECT COUNT(*) FROM rm_disciplina_atividade_notas n INNER JOIN rm_professores_materias o ON o.id = n.rm_prof_mat_id
                                 WHERE o.ano = :a AND n.nota IS NOT NULL AND n.nota <> '')
                             + (SELECT COUNT(*) FROM rm_disciplina_faltas f INNER JOIN rm_professores_materias o ON o.id = f.rm_prof_mat_id
                                 WHERE o.ano = :b AND f.faltas > 0)
                             + (SELECT COUNT(*) FROM rm_oferta_encontros e INNER JOIN rm_professores_materias o ON o.id = e.rm_prof_mat_id WHERE o.ano = :c) AS n",
            "a={$ano}&b={$ano}&c={$ano}");
        return (int) ($ler->getResultado()[0]['n'] ?? 0) > 0;
    }

    /** As etapas sugeridas para o modelo: o ano de fevereiro a meados de dezembro, em partes iguais; o conselho uma semana depois do fim. */
    public static function sugeridas(int $ano, string $modelo): array
    {
        $m = self::MODELOS[$modelo];
        $ini = new \DateTimeImmutable("{$ano}-02-01");
        $fim = new \DateTimeImmutable("{$ano}-12-15");
        $dias = (int) $ini->diff($fim)->days + 1;
        $etapas = [];
        for ($i = 1; $i <= $m['quantas']; $i++) {
            $a = $ini->modify('+' . intdiv($dias * ($i - 1), $m['quantas']) . ' days');
            $b = $i === $m['quantas'] ? $fim : $ini->modify('+' . (intdiv($dias * $i, $m['quantas']) - 1) . ' days');
            $etapas[] = ['ordem' => $i, 'nome' => "{$i}º {$m['etapa']}", 'inicio' => $a->format('Y-m-d'), 'fim' => $b->format('Y-m-d'),
                'conselho' => $b->modify('+7 days')->format('Y-m-d')];
        }
        return $etapas;
    }

    /**
     * Abre o ano letivo da escola com o modelo (ou troca o modelo, enquanto não há lançamento no ano).
     * @return string|true a recusa, ou true
     */
    public static function abrir(int $ano, string $modelo)
    {
        if (!isset(self::MODELOS[$modelo])) {
            return 'Escolha bimestres ou trimestres.';
        }
        if (!\App\adms\Models\vinculo\AdmsVinculoRegras::anoLetivoExiste($ano)) {
            return 'Cadastre o ano letivo antes (Ensino → Anos letivos).';
        }
        $atual = self::doAno($ano);
        if ($atual && $atual['modelo'] !== $modelo && $atual['lancamento']) {
            return "O ano {$ano} já tem notas ou faltas lançadas: o modelo das etapas não muda mais.";
        }
        if ($atual && $atual['modelo'] === $modelo) {
            return true;
        }
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $ano, $modelo) {
            $pdo = AdmsEscolaBanco::pdo();
            $pdo->prepare('DELETE FROM esc_etapas WHERE ano = ?')->execute([$ano]);
            $pdo->prepare('INSERT INTO esc_anos (ano, modelo, aberto_em, aberto_por) VALUES (?, ?, NOW(), ?) ON DUPLICATE KEY UPDATE modelo = VALUES(modelo)')
                ->execute([$ano, $modelo, (int) ($_SESSION['colUsuario_id'] ?? 0)]);
            $st = $pdo->prepare('INSERT INTO esc_etapas (ano, ordem, nome, inicio, fim, conselho) VALUES (?, ?, ?, ?, ?, ?)');
            foreach (self::sugeridas($ano, $modelo) as $e) {
                $T::exigir($st->execute([$ano, $e['ordem'], $e['nome'], $e['inicio'], $e['fim'], $e['conselho']]) ? true : null, 'etapa ' . $e['ordem']);
            }
            return true;
        });
        return $ok ? true : 'Não foi possível abrir o ano (' . $T::ultimoErro() . '). Nada foi gravado.';
    }

    /**
     * Grava os nomes e as datas das etapas do ano (a quantidade é a do modelo).
     * @param array<int, array{nome:string, inicio:string, fim:string, conselho:string}> $etapas ordem → campos
     * @return string|true
     */
    public static function salvar(int $ano, array $etapas)
    {
        $atual = self::doAno($ano);
        if (!$atual) {
            return 'Abra o ano letivo da escola antes.';
        }
        $data = static fn($d) => ($o = \DateTime::createFromFormat('Y-m-d', (string) $d)) && $o->format('Y-m-d') === $d;
        $limpas = [];
        $antes = '';
        foreach ($atual['etapas'] as $e) {
            $o = (int) $e['ordem'];
            $nova = $etapas[$o] ?? [];
            $nome = trim((string) ($nova['nome'] ?? ''));
            [$ini, $fim, $cons] = [(string) ($nova['inicio'] ?? ''), (string) ($nova['fim'] ?? ''), trim((string) ($nova['conselho'] ?? ''))];
            if ($nome === '' || mb_strlen($nome) > 40) {
                return "Dê um nome à etapa {$o} (até 40 letras).";
            }
            if (!$data($ini) || !$data($fim) || $fim < $ini) {
                return "As datas de \"{$nome}\": o início e o fim são obrigatórios, e o fim não vem antes do início.";
            }
            if ((int) substr($ini, 0, 4) !== $ano || (int) substr($fim, 0, 4) !== $ano) {
                return "As datas de \"{$nome}\" ficam dentro de {$ano}.";
            }
            if ($antes !== '' && $ini <= $antes) {
                return "\"{$nome}\" começa antes do fim da etapa anterior: as etapas não se sobrepõem.";
            }
            if ($cons !== '' && (!$data($cons) || $cons < $fim)) {
                return "A data do conselho de \"{$nome}\" vem depois do fim da etapa (ou fica vazia).";
            }
            $antes = $fim;
            $limpas[(int) $e['id']] = [$nome, $ini, $fim, $cons === '' ? null : $cons];
        }
        $pdo = AdmsEscolaBanco::pdo();
        $st = $pdo->prepare('UPDATE esc_etapas SET nome = ?, inicio = ?, fim = ?, conselho = ? WHERE id = ?');
        foreach ($limpas as $id => [$n, $i, $f, $c]) {
            $st->execute([$n, $i, $f, $c, $id]);
        }
        return true;
    }

    /** A etapa do ano em que a data cai (ou null). */
    public static function daData(int $ano, string $data): ?array
    {
        foreach ((self::doAno($ano)['etapas'] ?? []) as $e) {
            if ($data >= $e['inicio'] && $data <= $e['fim']) {
                return $e;
            }
        }
        return null;
    }

    /** As etapas liberadas para o portal agora (D-100: a liberação é a da tela Liberação do boletim). */
    public static function liberadas(): array
    {
        return \App\adms\Models\notas\AdmsCalculoNotas::etapasLiberadas();
    }
}
