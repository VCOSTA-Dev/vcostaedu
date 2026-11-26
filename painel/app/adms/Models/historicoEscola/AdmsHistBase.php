<?php

namespace App\adms\Models\historicoEscola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * O HISTÓRICO ESCOLAR DA EDUCAÇÃO BÁSICA (D-110 em diante; docs/HISTORICO_ESCOLAR.md) — o que as partes usam juntas:
 * a conexão, os níveis (Fundamental e Médio), o registro de alterações "de → para" (grava só o que mudou; nada é gravado
 * quando antes = depois) e o selo dos dados de prova. Só na escola.
 */
class AdmsHistBase
{
    public const NIVEIS = ['EF' => 'Ensino Fundamental', 'EM' => 'Ensino Médio'];
    /** o último número de série de cada nível */
    public const SERIES = ['EF' => 9, 'EM' => 3];

    public static function pdo(): \PDO
    {
        $pdo = \App\adms\Models\escola\AdmsEscolaBanco::pdo();
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    /** @return list<array> */
    public static function todos(string $sql, array $p = []): array
    {
        $s = self::pdo()->prepare($sql);
        $s->execute($p);
        return $s->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function um(string $sql, array $p = []): ?array
    {
        return self::todos($sql, $p)[0] ?? null;
    }

    public static function valor(string $sql, array $p = [])
    {
        $l = self::um($sql, $p);
        return $l ? reset($l) : null;
    }

    public static function exec(string $sql, array $p = []): int
    {
        $s = self::pdo()->prepare($sql);
        $s->execute($p);
        return $s->rowCount();
    }

    public static function usuario(): int
    {
        return (int) ($_SESSION['colUsuario_id'] ?? 0);
    }

    public static function nivelValido(string $n): bool
    {
        return isset(self::NIVEIS[$n]);
    }

    /** "6º ano" / "2ª série" a partir do nível e do número. */
    public static function nomeSerie(string $nivel, int $n): string
    {
        return $nivel === 'EM' ? "{$n}ª série" : "{$n}º ano";
    }

    /** Uma data dd/mm/aaaa ou aaaa-mm-dd → aaaa-mm-dd (ou null). */
    public static function data(?string $d): ?string
    {
        $d = trim((string) $d);
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $d, $m)) {
            $d = "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }
        return $d;
    }

    public static function br(?string $d): string
    {
        return $d && preg_match('/^\d{4}-\d{2}-\d{2}/', $d) ? date('d/m/Y', strtotime(substr($d, 0, 10))) : '';
    }

    /**
     * O REGISTRO DE ALTERAÇÕES: compara antes e depois campo a campo (os de `$rotulos`, na ordem dele) e grava uma linha
     * só com os campos que mudaram, com o texto legível "Rótulo: "de" → "para"". Nada mudou → nada é gravado (false).
     * `$antes` null = inclusão; `$depois` null = exclusão.
     */
    public static function registrar(string $tabela, int $registroId, int $alunoId, string $titulo, ?array $antes, ?array $depois, array $rotulos, string $acao = '', string $motivo = ''): bool
    {
        $a = [];
        $d = [];
        $partes = [];
        foreach ($rotulos as $campo => $rotulo) {
            $va = $antes === null ? null : self::texto($antes[$campo] ?? null);
            $vd = $depois === null ? null : self::texto($depois[$campo] ?? null);
            if ($va === $vd || ($antes === null && ($vd === null || $vd === '')) || ($depois === null && ($va === null || $va === ''))) {
                continue;
            }
            $a[$campo] = $va;
            $d[$campo] = $vd;
            $partes[] = $rotulo . ': ' . ($antes === null ? '' : '"' . self::curto($va) . '" → ') . ($depois === null ? '(excluído)' : '"' . self::curto($vd) . '"');
        }
        if (!$partes && $acao !== 'travar' && $acao !== 'destravar') {
            return false;
        }
        $acao = $acao !== '' ? $acao : ($antes === null ? 'incluir' : ($depois === null ? 'excluir' : 'alterar'));
        $texto = $titulo . ($partes ? ' — ' . implode('; ', $partes) : '') . ($motivo !== '' ? ' (motivo: ' . $motivo . ')' : '');
        self::exec('INSERT INTO esc_hist_alteracao (usuario_id, quando, tabela, registro_id, aluno_id, acao, antes, depois, motivo, texto) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)', [
            self::usuario(), $tabela, $registroId, $alunoId, $acao,
            $antes === null ? null : json_encode($a, JSON_UNESCAPED_UNICODE), $depois === null ? null : json_encode($d, JSON_UNESCAPED_UNICODE),
            mb_substr($motivo, 0, 500), $texto,
        ]);
        return true;
    }

    private static function texto($v): ?string
    {
        if ($v === null) {
            return null;
        }
        if (is_bool($v)) {
            return $v ? 'sim' : 'não';
        }
        $v = trim((string) $v);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return self::br($v);
        }
        return $v;
    }

    private static function curto(?string $v): string
    {
        $v = (string) $v;
        return mb_strlen($v) > 140 ? mb_substr($v, 0, 137) . '…' : $v;
    }

    /** As alterações (as mais novas primeiro), de uma tabela, de um aluno, ou todas. */
    public static function alteracoes(?string $tabela = null, int $alunoId = 0, int $limite = 100): array
    {
        $onde = [];
        $p = [];
        if ($tabela !== null) {
            $onde[] = 'a.tabela LIKE ?';
            $p[] = $tabela;
        }
        if ($alunoId) {
            $onde[] = 'a.aluno_id = ?';
            $p[] = $alunoId;
        }
        return self::todos('SELECT a.*, u.nome AS usuario FROM esc_hist_alteracao a LEFT JOIN adms_usuarios u ON u.id = a.usuario_id'
            . ($onde ? ' WHERE ' . implode(' AND ', $onde) : '') . ' ORDER BY a.id DESC LIMIT ' . (int) $limite, $p);
    }

    /** É dado de prova? (o selo PROVA) */
    public static function prova(string $tabela, int $id): bool
    {
        return (bool) self::valor('SELECT 1 FROM esc_hist_dado_prova WHERE tabela = ? AND registro_id = ?', [$tabela, $id]);
    }

    /** Os ids marcados como prova de uma tabela. @return array<int, true> */
    public static function provas(string $tabela): array
    {
        $r = [];
        foreach (self::todos('SELECT registro_id FROM esc_hist_dado_prova WHERE tabela = ?', [$tabela]) as $l) {
            $r[(int) $l['registro_id']] = true;
        }
        return $r;
    }

    /** O aviso padrão das telas (o mesmo das outras telas da escola). */
    public static function aviso(string $tipo, string $texto, string $id = ''): string
    {
        return "<div class='alert alert-{$tipo} alert-dismissible'" . ($id !== '' ? " id='{$id}'" : '') . " role='alert'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
            . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</div>';
    }
}
