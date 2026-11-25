<?php
/*
 * AJUDANTE DA PROVA DA ONDA 6, ETAPA 3 — o runner das migrations (o mesmo da tela, do comando e do instalador), sobre o
 * banco de PROVA e uma CÓPIA da pasta das migrations (a pasta do sistema não muda):
 *
 *   php etapa3_runner.php consulta     uma etapa que só tem consulta (SELECT) é aplicada e registrada (DUVIDAS 238)
 *   php etapa3_runner.php resumos      a 0001 e a 0002 registradas com o resumo ANTIGO (o de antes de os comentários
 *                                      perderem os nomes de origem) ficam APLICADAS; com um resumo qualquer, DIVERGENTE;
 *                                      e uma outra etapa com o resumo antigo da 0001 também é DIVERGENTE (DUVIDAS 240)
 * A última linha é JSON: {"ok": bool, "detalhe": "..."}. Tudo o que a prova muda no banco é desfeito no fim.
 * Só linha de comando; recusa banco que não termine em _teste.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$raiz = dirname(__DIR__, 3);
require $raiz . '/database/ferramentas/comum.php';
if (!str_ends_with(DBNAME, '_teste')) {
    fwrite(STDERR, "Recusado: o banco desta instalação não é de prova.\n");
    exit(2);
}
require_once $raiz . '/painel/app/adms/Models/banco/AdmsMigrations.php';
$pdo = vcostaedu_pdo();
$copia = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vcostaedu-migrations-' . getmypid();
@mkdir($copia);
foreach (glob($raiz . '/database/migrations/*.sql') as $f) {
    copy($f, $copia . DIRECTORY_SEPARATOR . basename($f));
}
$saida = ['ok' => false, 'detalhe' => ''];
$acao = $argv[1] ?? '';
try {
    if ($acao === 'consulta') {
        file_put_contents($copia . '/9999_prova_so_consulta.sql', "-- prova da Onda 6, Etapa 3: uma etapa só de consulta\nSELECT 1 AS um;\nSELECT COUNT(*) AS versoes FROM adms_versoes;\n");
        $r = new \App\adms\Models\banco\AdmsMigrations($copia, $pdo);
        try {
            $n = $r->aplicar(false);
            $sit = $r->estado()['9999']['situacao'] ?? '?';
            $saida = ['ok' => $n === 1 && $sit === 'APLICADA', 'detalhe' => "aplicadas: {$n}; a etapa só de consulta: {$sit}"];
        } catch (\Throwable $e) {
            $saida = ['ok' => false, 'detalhe' => 'recusada: ' . substr($e->getMessage(), 0, 160)];
        }
        vcostaedu_pdo()->exec("DELETE FROM adms_migrations WHERE versao = '9999'"); // numa conexão nova: a sabotada pode ter ficado presa
    } elseif ($acao === 'resumos') {
        $r = new \App\adms\Models\banco\AdmsMigrations($copia, $pdo);
        $antes = $pdo->query("SELECT versao, hash FROM adms_migrations WHERE versao IN ('0001', '0002', '0003')")->fetchAll(\PDO::FETCH_KEY_PAIR);
        $velho = ['0001' => '17cfb855007570076ebc90abe027e7c8dd144949ebed6fd012197935ece8c1d3', '0002' => '7b8aa2a6675f22036cf1cd68ac1c0dd1725f609172f9f6d4a06c13344f1f342c'];
        $sit = static function () use ($r): array {
            $e = $r->estado();
            return ['0001' => $e['0001']['situacao'] ?? '?', '0002' => $e['0002']['situacao'] ?? '?', '0003' => $e['0003']['situacao'] ?? '?'];
        };
        $grava = static function (string $v, string $h) use ($pdo): void {
            $pdo->prepare('UPDATE adms_migrations SET hash = ? WHERE versao = ?')->execute([$h, $v]);
        };
        try {
            $grava('0001', $velho['0001']);
            $grava('0002', $velho['0002']);
            $comAntigo = $sit();
            $grava('0001', str_repeat('a', 64));
            $grava('0003', $velho['0001']);
            $comOutro = $sit();
            $saida = ['ok' => $comAntigo['0001'] === 'APLICADA' && $comAntigo['0002'] === 'APLICADA' && $comOutro['0001'] === 'DIVERGENTE' && $comOutro['0003'] === 'DIVERGENTE',
                'detalhe' => "com o resumo antigo: 0001 {$comAntigo['0001']}, 0002 {$comAntigo['0002']}; com um resumo qualquer: 0001 {$comOutro['0001']}; outra etapa com o resumo antigo da 0001: 0003 {$comOutro['0003']}"];
        } finally {
            foreach ($antes as $v => $h) {
                $grava((string) $v, (string) $h);
            }
        }
    } elseif ($acao === 'agrupadas') {
        /* a consulta da frequência no total do ano (escola), tirada do próprio código do painel e do portal, roda no
           banco com o modo do sistema (o ONLY_FULL_GROUP_BY recusa na hora, mesmo sem dado: erro 1055) */
        $det = [];
        $ok = true;
        foreach (['painel', 'portal'] as $app) {
            $fonte = (string) file_get_contents($raiz . "/{$app}/app/adms/Models/notas/AdmsCalculoNotas.php");
            if (!preg_match('/fullRead\("(SELECT [a-z]\.rm, SUM\(.*?AS enc,.*?GROUP BY [a-z]\.rm)\', "t=/s', $fonte, $m)) {
                $ok = false;
                $det[] = "{$app}: a consulta não foi achada no código";
                continue;
            }
            $q = preg_replace('/"\s*\.\s*\(\$soRm[^)]*\)\s*\.\s*\'/', '', $m[1]);
            try {
                $c = $pdo->prepare($q);
                $c->execute([':t' => 0, ':t2' => 0]);
                $c->fetchAll();
                $det[] = "{$app}: aceita";
            } catch (\Throwable $e) {
                $ok = false;
                $det[] = "{$app}: recusada (" . (str_contains($e->getMessage(), '1055') ? 'erro 1055, fora do GROUP BY' : substr($e->getMessage(), 0, 80)) . ')';
            }
        }
        $saida = ['ok' => $ok, 'detalhe' => 'a frequência no ano da escola: ' . implode('; ', $det)];
    } else {
        $saida['detalhe'] = 'uso: consulta | resumos | agrupadas';
    }
} finally {
    foreach (glob($copia . '/*') as $f) {
        @unlink($f);
    }
    @rmdir($copia);
}
echo json_encode($saida, JSON_UNESCAPED_UNICODE) . "\n";
