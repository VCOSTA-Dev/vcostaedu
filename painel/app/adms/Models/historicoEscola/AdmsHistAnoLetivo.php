<?php

namespace App\adms\Models\historicoEscola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\historicoEscola\AdmsHistBase as B;

/**
 * O ANO LETIVO FECHADO (histórico escolar, Parte H; D-117): o marcador explícito. A secretaria ou a direção fecha o ano
 * letivo; reabrir pede o motivo; cada ação fica no histórico do marcador (esc_ano_letivo_fechamento_hist). Com o
 * marcador, o ano é fechado ou aberto por ele; sem o marcador, vale o critério de antes (todas as disciplinas da turma
 * encerradas). O ano fechado é o que faz a vida escolar propor o resultado final (aprovado, retido) em vez de "em curso".
 */
class AdmsHistAnoLetivo
{
    public static function existe(): bool
    {
        return \App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0095');
    }

    /** O marcador do ano: 'fechado', 'reaberto' ou null (sem marcador). */
    public static function marcador(int $ano): ?string
    {
        if (!self::existe()) {
            return null;
        }
        $s = B::valor('SELECT situacao FROM esc_ano_letivo_fechamento WHERE ano = ?', [$ano]);
        return $s === null || $s === false ? null : (string) $s;
    }

    /** Os anos letivos das turmas da escola, do mais novo ao mais antigo, com o marcador e as disciplinas encerradas. */
    public static function anos(): array
    {
        $r = [];
        foreach (B::todos('SELECT t.ano, COUNT(DISTINCT t.id) AS turmas, COUNT(o.id) AS ofertas, SUM(o.encerrada_em IS NOT NULL) AS encerradas
                FROM esc_turmas t LEFT JOIN esc_turma_ofertas x ON x.turma_id = t.id LEFT JOIN rm_professores_materias o ON o.id = x.oferta_id
                GROUP BY t.ano ORDER BY t.ano DESC') as $l) {
            $ano = (int) $l['ano'];
            $m = self::existe() ? B::um('SELECT f.situacao, f.em, u.nome AS por FROM esc_ano_letivo_fechamento f LEFT JOIN adms_usuarios u ON u.id = f.por WHERE f.ano = ?', [$ano]) : null;
            $todas = (int) $l['ofertas'] > 0 && (int) $l['ofertas'] === (int) $l['encerradas'];
            $r[] = ['ano' => $ano, 'turmas' => (int) $l['turmas'], 'ofertas' => (int) $l['ofertas'], 'encerradas' => (int) $l['encerradas'],
                'marcador' => $m ? (string) $m['situacao'] : '', 'em' => $m ? (string) $m['em'] : '', 'por' => $m ? (string) ($m['por'] ?? '') : '',
                'fechado' => $m ? $m['situacao'] === 'fechado' : $todas, 'pelo_criterio' => !$m];
        }
        return $r;
    }

    public static function historico(int $limite = 100): array
    {
        if (!self::existe()) {
            return [];
        }
        return B::todos('SELECT h.ano, h.acao, h.motivo, h.em, u.nome AS por FROM esc_ano_letivo_fechamento_hist h LEFT JOIN adms_usuarios u ON u.id = h.por ORDER BY h.id DESC LIMIT ' . max(1, $limite));
    }

    private static function anoValido(int $ano): bool
    {
        return $ano > 0 && (bool) B::valor('SELECT id FROM esc_turmas WHERE ano = ? LIMIT 1', [$ano]);
    }

    /** @return true|string */
    public static function fechar(int $ano)
    {
        if (!self::existe()) {
            return 'Falta a atualização do banco (0095).';
        }
        if (!self::anoValido($ano)) {
            return 'Não há turma neste ano letivo.';
        }
        if (self::marcador($ano) === 'fechado') {
            return "O ano letivo de {$ano} já está fechado.";
        }
        return \Core\Transacao::executar(function () use ($ano) {
            B::exec("INSERT INTO esc_ano_letivo_fechamento (ano, situacao, por, em) VALUES (?, 'fechado', ?, NOW()) ON DUPLICATE KEY UPDATE situacao = 'fechado', por = VALUES(por), em = VALUES(em)", [$ano, B::usuario()]);
            B::exec("INSERT INTO esc_ano_letivo_fechamento_hist (ano, acao, motivo, por, em) VALUES (?, 'fechar', '', ?, NOW())", [$ano, B::usuario()]);
            return true;
        }) ?: 'O ano letivo não foi fechado (' . \Core\Transacao::ultimoErro() . ').';
    }

    /** @return true|string */
    public static function reabrir(int $ano, string $motivo)
    {
        if (!self::existe()) {
            return 'Falta a atualização do banco (0095).';
        }
        $motivo = trim($motivo);
        if (self::marcador($ano) !== 'fechado') {
            return "O ano letivo de {$ano} não está fechado pelo marcador.";
        }
        if (mb_strlen($motivo) < 10) {
            return 'Diga o motivo da reabertura (ao menos 10 letras): ele fica no histórico do ano letivo.';
        }
        if (mb_strlen($motivo) > 500) {
            return 'O motivo vai até 500 letras.';
        }
        return \Core\Transacao::executar(function () use ($ano, $motivo) {
            B::exec("UPDATE esc_ano_letivo_fechamento SET situacao = 'reaberto', por = ?, em = NOW() WHERE ano = ?", [B::usuario(), $ano]);
            B::exec("INSERT INTO esc_ano_letivo_fechamento_hist (ano, acao, motivo, por, em) VALUES (?, 'reabrir', ?, ?, NOW())", [$ano, $motivo, B::usuario()]);
            return true;
        }) ?: 'O ano letivo não foi reaberto (' . \Core\Transacao::ultimoErro() . ').';
    }
}
