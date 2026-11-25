<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsRead;
use App\adms\Models\notas\AdmsCalculoNotas as Calc;

/**
 * A LIBERAÇÃO DO BOLETIM (correção da escola, Parte 1; D-100; docs/ESCOLA.md), por ano letivo, no desenho da tela do
 * sistema de origem: uma linha por etapa, a "Média final" e a "Situação final". Cada linha: "Exibir no portal", "Liberar
 * a partir de" (data e hora; vazio = já), a situação agora em palavras e a última alteração (quem e quando).
 *   - o portal obedece: a etapa não liberada sai como "—"; sem a média final, nenhuma média anual em lugar nenhum; sem a
 *     situação final, nem aprovado nem reprovado (AdmsCalculoNotas::etapasLiberadas e ::finalLiberado);
 *   - o painel mostra tudo o que está fechado, como sempre;
 *   - sem ordem obrigatória entre as etapas; sem liberação por aluno;
 *   - cada alteração vai para o histórico (esc_boletim_liberacao_hist), com o antes e o depois.
 */
class AdmsLiberacaoBoletim
{
    public const FINAIS = ['media_final' => 'Média final', 'situacao_final' => 'Situação final'];

    /** Os anos letivos com etapas (o mais novo primeiro). @return list<int> */
    public static function anos(): array
    {
        $l = new AdmsRead();
        $l->fullRead('SELECT DISTINCT ano FROM esc_etapas ORDER BY ano DESC');
        return array_map('intval', array_column($l->getResultado() ?: [], 'ano'));
    }

    /**
     * As linhas do ano: cada etapa (na ordem), a média final e a situação final, com o gravado e a situação agora.
     * @return list<array{chave: string, item: string, etapa_id: int, nome: string, exibir: bool, a_partir: ?string, estado: string, texto: string, por: string, em: ?string, abertas: int}>
     */
    public static function linhas(int $ano): array
    {
        $gravado = [];
        $l = new AdmsRead();
        $l->fullRead('SELECT b.*, u.nome AS por_nome FROM esc_boletim_liberacao b LEFT JOIN adms_usuarios u ON u.id = b.alterado_por WHERE b.ano = :a', "a={$ano}");
        foreach ($l->getResultado() ?: [] as $r) {
            $gravado[$r['item'] . ':' . (int) $r['etapa_id']] = $r;
        }
        $abertas = self::abertasPorEtapa($ano);
        $saida = [];
        foreach (Calc::etapasDoAno($ano) as $e) {
            $saida[] = self::linha('etapa', (int) $e['id'], (string) $e['nome'], $gravado['etapa:' . (int) $e['id']] ?? null, $abertas[(int) $e['id']] ?? 0);
        }
        if ($saida) {
            foreach (self::FINAIS as $item => $nome) {
                $saida[] = self::linha($item, 0, $nome, $gravado[$item . ':0'] ?? null, 0);
            }
        }
        return $saida;
    }

    private static function linha(string $item, int $etapaId, string $nome, ?array $r, int $abertas): array
    {
        $exibir = (int) ($r['exibir'] ?? 0) === 1;
        $aPartir = $r['a_partir'] ?? null;
        [$estado, $texto] = self::estado($exibir, $aPartir, $item);
        return ['chave' => $item . ':' . $etapaId, 'item' => $item, 'etapa_id' => $etapaId, 'nome' => $nome, 'exibir' => $exibir, 'a_partir' => $aPartir,
            'estado' => $estado, 'texto' => $texto, 'por' => (string) ($r['por_nome'] ?? ''), 'em' => $r['alterado_em'] ?? null, 'abertas' => $abertas];
    }

    /** A situação agora, em palavras: [oculto | visivel | programado, o texto]. */
    public static function estado(bool $exibir, ?string $aPartir, string $item = 'etapa'): array
    {
        $o = $item === 'etapa' ? 'as notas desta etapa' : ($item === 'media_final' ? 'a média anual' : 'a situação final (aprovado ou reprovado)');
        if (!$exibir) {
            return ['oculto', 'A família e o aluno não veem ' . $o . '.'];
        }
        if ($aPartir !== null && $aPartir !== '' && strtotime($aPartir) > time()) {
            return ['programado', 'Aparece no portal em ' . date('d/m/Y', strtotime($aPartir)) . ' às ' . date('H:i', strtotime($aPartir)) . '.'];
        }
        return ['visivel', 'A família e o aluno veem ' . $o . ' no portal.'];
    }

    /**
     * Quantas disciplinas das turmas do ano ainda têm cada etapa aberta (sem o fechamento vigente).
     * @return array<int, int> etapa => disciplinas abertas
     */
    public static function abertasPorEtapa(int $ano): array
    {
        $l = new AdmsRead();
        $l->fullRead("SELECT e.id, COUNT(DISTINCT x.oferta_id) AS abertas
              FROM esc_etapas e INNER JOIN esc_turmas t ON t.ano = e.ano INNER JOIN esc_turma_ofertas x ON x.turma_id = t.id
             WHERE e.ano = :a AND NOT EXISTS (SELECT 1 FROM esc_fechamentos f WHERE f.oferta_id = x.oferta_id AND f.etapa_id = e.id AND f.situacao = 'vigente')
             GROUP BY e.id", "a={$ano}");
        $saida = [];
        foreach ($l->getResultado() ?: [] as $r) {
            $saida[(int) $r['id']] = (int) $r['abertas'];
        }
        return $saida;
    }

    /**
     * Grava a tela inteira do ano. $dados: chave "item:etapa" => [exibir => '1'|'', a_partir => 'AAAA-MM-DDTHH:MM'|''].
     * Só grava (e só põe no histórico) a linha que mudou.
     * @return int|string quantas linhas mudaram, ou o motivo da recusa
     */
    public static function salvar(int $ano, array $dados)
    {
        $linhas = self::linhas($ano);
        if (!$linhas) {
            return "O ano letivo {$ano} não tem etapas.";
        }
        $novas = [];
        foreach ($linhas as $ln) {
            $d = (array) ($dados[$ln['chave']] ?? []);
            $exibir = !empty($d['exibir']);
            $ap = trim((string) ($d['a_partir'] ?? ''));
            $aPartir = null;
            if ($ap !== '') {
                $t = \DateTime::createFromFormat('Y-m-d\TH:i', $ap) ?: \DateTime::createFromFormat('Y-m-d H:i', $ap) ?: \DateTime::createFromFormat('Y-m-d H:i:s', $ap);
                if (!$t) {
                    return "A data de \"{$ln['nome']}\" não é válida: use o dia e a hora.";
                }
                $aPartir = $t->format('Y-m-d H:i:00');
            }
            if (!$exibir) {
                $aPartir = null; // sem exibir, a data não vale
            }
            $antes = $ln['a_partir'] === null ? null : date('Y-m-d H:i:00', strtotime((string) $ln['a_partir']));
            if ($exibir === $ln['exibir'] && $aPartir === $antes) {
                continue;
            }
            $novas[] = [$ln, $exibir, $aPartir, $antes];
        }
        if (!$novas) {
            return 0;
        }
        $por = (int) ($_SESSION['colUsuario_id'] ?? 0);
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($novas, $ano, $por) {
            $pdo = AdmsEscolaBanco::pdo();
            foreach ($novas as [$ln, $exibir, $aPartir, $antes]) {
                $pdo->prepare('INSERT INTO esc_boletim_liberacao (ano, item, etapa_id, exibir, a_partir, alterado_por, alterado_em) VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE exibir = VALUES(exibir), a_partir = VALUES(a_partir), alterado_por = VALUES(alterado_por), alterado_em = NOW()')
                    ->execute([$ano, $ln['item'], $ln['etapa_id'], $exibir ? 1 : 0, $aPartir, $por ?: null]);
                $pdo->prepare('INSERT INTO esc_boletim_liberacao_hist (ano, item, etapa_id, exibir_antes, a_partir_antes, exibir, a_partir, por, em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())')
                    ->execute([$ano, $ln['item'], $ln['etapa_id'], $ln['exibir'] ? 1 : 0, $antes, $exibir ? 1 : 0, $aPartir, $por ?: null]);
            }
            return true;
        });
        if (!$ok) {
            return 'A liberação não foi gravada (' . $T::ultimoErro() . '). Nada mudou.';
        }
        foreach ($novas as [$ln, $exibir, $aPartir]) {
            (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(589, 200, 1, "Liberação do boletim: ano={$ano}; {$ln['chave']}; exibir=" . ($exibir ? 1 : 0) . '; a_partir=' . ($aPartir ?? ''));
        }
        return count($novas);
    }

    /** O histórico do ano (as alterações, da mais nova para a mais antiga). */
    public static function historico(int $ano, int $limite = 50): array
    {
        $nomes = [];
        foreach (Calc::etapasDoAno($ano) as $e) {
            $nomes['etapa:' . (int) $e['id']] = (string) $e['nome'];
        }
        foreach (self::FINAIS as $k => $n) {
            $nomes[$k . ':0'] = $n;
        }
        $l = new AdmsRead();
        $l->fullRead('SELECT h.*, u.nome AS por_nome FROM esc_boletim_liberacao_hist h LEFT JOIN adms_usuarios u ON u.id = h.por WHERE h.ano = :a ORDER BY h.id DESC LIMIT :limit', "a={$ano}&limit={$limite}");
        $saida = [];
        foreach ($l->getResultado() ?: [] as $r) {
            $depois = self::estado((int) $r['exibir'] === 1, $r['a_partir'], (string) $r['item']);
            $saida[] = ['nome' => $nomes[$r['item'] . ':' . (int) $r['etapa_id']] ?? (string) $r['item'], 'por' => (string) ($r['por_nome'] ?? ''), 'em' => (string) $r['em'],
                'antes' => (int) $r['exibir_antes'] === 1 ? ($r['a_partir_antes'] ? 'liberada a partir de ' . date('d/m/Y H:i', strtotime((string) $r['a_partir_antes'])) : 'exibida') : 'oculta',
                'depois' => (int) $r['exibir'] === 1 ? ($r['a_partir'] ? 'liberada a partir de ' . date('d/m/Y H:i', strtotime((string) $r['a_partir'])) : 'exibida') : 'oculta',
                'estado' => $depois[0]];
        }
        return $saida;
    }

    /** A etapa liberada para a família agora (a tela das etapas da disciplina diz "liberada para a família"). */
    public static function etapaVisivel(int $etapaId): bool
    {
        return in_array($etapaId, Calc::etapasLiberadas(), true);
    }
}
