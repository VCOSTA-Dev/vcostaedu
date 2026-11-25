<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * O MOTOR DE FASES dos Primeiros passos — Onda 2, Etapa 4.
 *
 * As fases são DADO (adms_fases, migration 0008): a ordem, os textos, a tela, o roteiro do "Faça comigo", as
 * dependências e a CONDIÇÃO DE CONCLUÍDA — uma consulta que conta no banco o que a fase pede. O motor só lê: a fase
 * se marca sozinha quando a condição passa (nada é marcado à mão); pular e retomar ficam em adms_fases_estado. Fase
 * nova, de um módulo futuro, entra por migration, sem mexer aqui.
 *
 * Só o Administrador vê; some quando todas estão concluídas (ou puladas) ou quando ele dispensa, e não aparece com a
 * demonstração ligada (os dados fictícios já cumprem todas).
 */
class AdmsPrimeirosPassos extends AdmsConn
{
    private static ?array $fases = null;

    private static function pdo(): \PDO
    {
        $pdo = (new self())->connect();
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    public static function disponivel(): bool
    {
        return AdmsAtualizacaoPendente::exige('0008');
    }

    /**
     * As fases, na ordem, cada uma com: concluida (a condição no banco), pulada, bloqueada (falta uma dependência,
     * nem concluída nem pulada) e proxima (a primeira que se pode fazer agora).
     */
    public static function fases(bool $recarregar = false): array
    {
        if (self::$fases !== null && !$recarregar) {
            return self::$fases;
        }
        if (!self::disponivel()) {
            return self::$fases = [];
        }
        $pdo = self::pdo();
        $linhas = $pdo->query("SELECT f.*, e.pulada_em, e.concluida_em, e.anunciada_em FROM adms_fases f LEFT JOIN adms_fases_estado e ON e.fase_id = f.id
                                WHERE f.situacao = 1 ORDER BY f.ordem, f.id")->fetchAll(\PDO::FETCH_ASSOC);
        /* Onda 4, Etapa 2: a fase de um módulo desligado não é desta instalação (as da escola no ensino superior, e o contrário) */
        $linhas = array_values(array_filter($linhas, static fn($f) => AdmsTipoInstituicao::moduloLigado($f['modulo'] ?? null)));
        $porCodigo = [];
        foreach ($linhas as $f) {
            try {
                $f['concluida'] = (int) $pdo->query($f['condicao'])->fetchColumn() > 0;
            } catch (\Throwable $e) {
                error_log('[vcostaedu][primeiros-passos] condição da fase ' . $f['codigo'] . ' falhou: ' . $e->getMessage());
                $f['concluida'] = false;
            }
            $f['pulada'] = !$f['concluida'] && $f['pulada_em'] !== null;
            $porCodigo[$f['codigo']] = $f;
        }
        $proxima = null;
        /* Onda 3, Etapa 7: o número de cada passo (a posição na tela, a partir de 1), para o selo "Depois do passo N" */
        $numero = array_flip(array_keys($porCodigo));
        foreach ($porCodigo as $c => &$f) {
            $faltam = [];
            $depois = [];
            foreach (array_filter(array_map('trim', explode(',', (string) $f['depende_de']))) as $d) {
                if (isset($porCodigo[$d]) && !$porCodigo[$d]['concluida'] && !$porCodigo[$d]['pulada']) {
                    $faltam[] = $porCodigo[$d]['titulo'];
                    $depois[] = $numero[$d] + 1;
                }
            }
            sort($depois);
            $f['faltam'] = $faltam;
            $f['depois'] = $depois;
            $f['bloqueada'] = !$f['concluida'] && $faltam !== [];
            $f['proxima'] = false;
            if ($proxima === null && !$f['concluida'] && !$f['pulada'] && !$f['bloqueada']) {
                $proxima = $c;
                $f['proxima'] = true;
            }
        }
        unset($f);
        return self::$fases = array_values($porCodigo);
    }

    public static function progresso(): array
    {
        $f = self::fases();
        $feitas = count(array_filter($f, fn($x) => $x['concluida']));
        $puladas = count(array_filter($f, fn($x) => $x['pulada']));
        $proxima = array_values(array_filter($f, fn($x) => $x['proxima']))[0] ?? null;
        return ['total' => count($f), 'feitas' => $feitas, 'puladas' => $puladas, 'proxima' => $proxima, 'completas' => $f !== [] && $feitas + $puladas === count($f)];
    }

    /** Mostrar no menu e na Página Inicial? */
    public static function ativo(): bool
    {
        if (!self::disponivel() || !AdmsNivel::ehAdministrador() || AdmsAmbiente::demonstracaoAtiva() || AdmsAmbiente::estado('primeiros_passos.dispensado') === '1') {
            return false;
        }
        $p = self::progresso();
        return $p['total'] > 0 && $p['feitas'] < $p['total'];
    }

    /**
     * A conclusão que o motor acabou de ver: grava quando viu, e devolve a frase da última concluída ainda não
     * anunciada ("Você já configurou a instituição. Agora vamos criar o seu primeiro curso.").
     */
    public static function anunciar(): ?string
    {
        $pdo = self::pdo();
        $frase = null;
        $fases = self::fases();
        foreach ($fases as $i => $f) {
            if (!$f['concluida']) {
                continue;
            }
            if ($f['concluida_em'] === null || $f['anunciada_em'] === null) {
                $pdo->prepare("INSERT INTO adms_fases_estado (fase_id, concluida_em, anunciada_em, usuario_mod) VALUES (:f, NOW(), NOW(), :u)
                               ON DUPLICATE KEY UPDATE concluida_em = IFNULL(concluida_em, NOW()), anunciada_em = NOW(), pulada_em = NULL")
                    ->execute(['f' => $f['id'], 'u' => (int) ($_SESSION['colUsuario_id'] ?? 0)]);
                $frase = $f['feito'];
                $prox = array_values(array_filter($fases, fn($x) => !$x['concluida'] && !$x['pulada'] && $x['ordem'] > $f['ordem']))[0] ?? null;
                if ($prox) {
                    $frase .= ' ' . $prox['convite'];
                }
            }
        }
        self::$fases = null;
        return $frase;
    }

    /** pular | retomar uma fase; dispensar | voltar os Primeiros passos. */
    public static function acao(string $acao, string $codigo = ''): bool
    {
        $pdo = self::pdo();
        $u = (int) ($_SESSION['colUsuario_id'] ?? 0);
        if ($acao === 'dispensar' || $acao === 'voltar') {
            AdmsAmbiente::gravarEstado('primeiros_passos.dispensado', $acao === 'dispensar' ? '1' : null);
            return true;
        }
        $st = $pdo->prepare('SELECT id, pode_pular FROM adms_fases WHERE codigo = :c AND situacao = 1');
        $st->execute(['c' => $codigo]);
        $f = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$f || ($acao === 'pular' && (int) $f['pode_pular'] !== 1) || !in_array($acao, ['pular', 'retomar'], true)) {
            return false;
        }
        $pdo->prepare("INSERT INTO adms_fases_estado (fase_id, pulada_em, usuario_mod) VALUES (:f, " . ($acao === 'pular' ? 'NOW()' : 'NULL') . ", :u)
                       ON DUPLICATE KEY UPDATE pulada_em = " . ($acao === 'pular' ? 'NOW()' : 'NULL') . ", usuario_mod = :u2")
            ->execute(['f' => $f['id'], 'u' => $u, 'u2' => $u]);
        self::$fases = null;
        return true;
    }
}
