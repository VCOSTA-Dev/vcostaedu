<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * AS REGRAS DA ESCOLA (docs/ESCOLA.md): o que vem pronto e a escola mantém ou ajusta por tela. O padrão mora aqui; a
 * tabela esc_configuracao guarda só o que a escola mudou (numa instalação de ensino superior ela fica vazia).
 */
require_once dirname(__DIR__, 5) . '/core/MotorMedia.php';
require_once dirname(__DIR__, 5) . '/core/MediaEscola.php';

class AdmsEscolaConfig
{
    /** O padrão mora no core (\Core\MediaEscola::PADRAO): o portal e o cálculo leem o mesmo. */
    public const PADRAO = \Core\MediaEscola::PADRAO;

    public const RECUPERACAO = [
        'maior' => 'A maior entre a média da etapa e a nota da recuperação',
        'media' => 'A média entre a média da etapa e a nota da recuperação',
        'maior_teto' => 'A maior entre as duas, com teto na média mínima',
    ];
    public const COMPOSICAO = [
        'simples' => 'Média simples das etapas',
        'pesos' => 'Média ponderada, com um peso para cada etapa',
    ];
    public const FREQUENCIA = [
        'ano' => 'No total de horas do ano letivo (todas as disciplinas juntas)',
        'componente' => 'Em cada disciplina',
    ];
    /** De onde vêm as faltas (Onda 6, Etapa 5): o total do ano segue a mesma regra nas duas. */
    public const FONTES = [
        'etapa' => 'As faltas lançadas por etapa, na tela das etapas da disciplina',
        'chamada' => 'A chamada por aula, na disciplina: a falta da etapa é a soma das chamadas daquela etapa',
    ];

    /** @var array<string, string>|null */
    private static ?array $cache = null;

    public static function todas(): array
    {
        if (self::$cache === null) {
            self::$cache = self::PADRAO;
            try {
                $ler = new \App\adms\Models\helper\AdmsRead();
                $ler->fullRead('SELECT chave, valor FROM esc_configuracao');
                foreach ($ler->getResultado() ?: [] as $l) {
                    if (array_key_exists((string) $l['chave'], self::PADRAO)) {
                        self::$cache[(string) $l['chave']] = (string) $l['valor'];
                    }
                }
            } catch (\Throwable $e) {
                // antes da migration da escola: o padrão
            }
        }
        return self::$cache;
    }

    public static function valor(string $chave): string
    {
        return self::todas()[$chave] ?? (self::PADRAO[$chave] ?? '');
    }

    /** Grava o que difere do padrão (e apaga o que voltou ao padrão). */
    public static function gravar(array $valores): void
    {
        $pdo = AdmsEscolaBanco::pdo();
        foreach ($valores as $chave => $valor) {
            if (!array_key_exists($chave, self::PADRAO)) {
                continue;
            }
            if ((string) $valor === self::PADRAO[$chave]) {
                $pdo->prepare('DELETE FROM esc_configuracao WHERE chave = ?')->execute([$chave]);
            } else {
                $pdo->prepare('INSERT INTO esc_configuracao (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)')->execute([$chave, (string) $valor]);
            }
        }
        self::$cache = null;
    }

    /** A coluna da oferta para a fonte da escola (a chamada da oferta é a mesma do ensino superior). */
    public static function fonteDaOferta(?string $fonte = null): string
    {
        return ($fonte ?? self::valor('frequencia.fonte')) === 'chamada' ? 'chamada' : 'lancamento';
    }

    /**
     * A fonte nova nos componentes abertos da escola (Onda 6, Etapa 5), pela mesma troca da tela da chamada: o que já tem
     * falta lançada (ou chamada registrada) fica como está. @return array{0: int, 1: int} [mudaram, ficaram]
     */
    public static function aplicarFonte(string $fonte): array
    {
        $alvo = self::fonteDaOferta($fonte);
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT DISTINCT o.id FROM rm_professores_materias o INNER JOIN esc_turma_ofertas x ON x.oferta_id = o.id
                         WHERE o.encerrada_em IS NULL AND IFNULL(o.frequencia_fonte, 'lancamento') <> :f", "f={$alvo}");
        $mudaram = 0;
        $ficaram = 0;
        foreach ($ler->getResultado() ?: [] as $o) {
            $ch = new \App\adms\Models\faltas\AdmsChamada();
            $ch->trocarFonte((int) $o['id'], $alvo, (int) ($_SESSION['colUsuario_id'] ?? 0)) ? $mudaram++ : $ficaram++;
        }
        return [$mudaram, $ficaram];
    }

    /** Os pesos das etapas (a composição "pesos"); sem peso informado, 1. @return array<int, int> ordem → peso */
    public static function pesos(int $etapas): array
    {
        $lista = array_values(array_filter(array_map('trim', explode(',', self::valor('anual.pesos'))), fn($x) => $x !== ''));
        $saida = [];
        for ($i = 1; $i <= $etapas; $i++) {
            $p = (int) ($lista[$i - 1] ?? 1);
            $saida[$i] = $p > 0 ? $p : 1;
        }
        return $saida;
    }
}
