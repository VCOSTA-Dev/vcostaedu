<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\banco\AdmsMigrations;
use Throwable;

/**
 * A ATUALIZAÇÃO — a política que as DUAS PORTAS compartilham (Onda 2, Etapa 7; o desenho D25 do vcostaedu).
 *
 * A tela "Atualizar o sistema" e o comando `php bin/migrar.php aplicar` atualizam o mesmo sistema. Se cada um aplicasse
 * do seu jeito, o dia em que a pessoa recorre ao comando — o dia em que a tela já deu problema — seria o dia em que os dois
 * discordam. Então esta classe é a DECISÃO (o que está pendente, o que aconteceu na falha) e as portas só perguntam e
 * mostram. Por baixo, quem aplica é o AdmsMigrations, com a trava contra duas atualizações ao mesmo tempo (GET_LOCK).
 *
 * Onda 3, Etapa 11: a TELA aplica EM PASSOS — uma etapa do banco por chamada do navegador, com a janela de progresso —,
 * e o tempo do servidor deixa de importar (saiu a política "cabe no tempo da tela, senão o comando"). O estado do lote
 * fica no banco (a tabela adms_atualizacao_lote, que só existe enquanto há uma atualização pela tela: a tabela do runner
 * pode ser a de uma versão antiga, e a adms_instalacao_estado pode ainda não existir); a página fechada ou a etapa que
 * falhou RETOMAM de onde pararam; a trava vale entre as chamadas (o dono do lote, vivo há menos de VIVO segundos) e,
 * dentro de cada uma, o GET_LOCK do runner. O COMANDO segue aplicando tudo em sequência — e recusa enquanto a tela
 * estiver atualizando.
 *
 * A instalação não passa por aqui: o instalador aplica pelo mesmo runner, mas num banco vazio não há backup a pedir nem
 * "já em dia" a dizer.
 */
class AdmsAtualizacao
{
    private const PAGINA = 489;

    /** O lote da tela em passos (Onda 3, Etapa 11): uma linha, enquanto houver atualização pela tela. */
    private const LOTE = 'adms_atualizacao_lote';

    /** Quanto tempo sem passo o lote ainda é de quem o começou (a outra pessoa espera; depois disso, pode retomar). */
    public const VIVO = 90;

    private ?AdmsMigrations $runner = null;

    /** @return array{ok:bool, emDia:bool, pendentes:string[], divergentes:string[], adiadas:string[], aplicadas:int, erro:string} */
    public function estado(): array
    {
        $vazio = ['ok' => false, 'emDia' => false, 'pendentes' => [], 'divergentes' => [], 'adiadas' => [], 'aplicadas' => 0, 'erro' => ''];
        try {
            $estado = $this->runner()->estado();
        } catch (Throwable $e) {
            error_log('[vcostaedu][atualizacao] ' . $e->getMessage());
            return array_merge($vazio, ['erro' => 'Não foi possível conferir o estado do banco de dados. O detalhe está no log do servidor.']);
        }
        $r = $vazio;
        $r['ok'] = true;
        foreach ($estado as $m) {
            if ($m['situacao'] === 'PENDENTE') {
                /* todas vão para a lista (e o runner aplica a que exige privilégio quando o usuário do banco o tem); a que
                   exige privilégio (a 0003 e a 0007, os gatilhos), sozinha, não tira o sistema de "em dia": sem o
                   privilégio ela fica adiada de propósito, e o sistema funciona sem ela */
                $r['pendentes'][] = $m['arquivo'];
                if ($this->exigePrivilegio((string) $m['arquivo'])) {
                    $r['adiadas'][] = $m['arquivo'];
                }
            } elseif ($m['situacao'] === 'DIVERGENTE') {
                $r['divergentes'][] = $m['arquivo'];
            } elseif ($m['situacao'] === 'APLICADA') {
                $r['aplicadas']++;
            }
        }
        $r['emDia'] = count($r['pendentes']) === count($r['adiadas']) && !$r['divergentes'];
        return $r;
    }

    /** A migration que exige privilégio ("-- vcostaedu:exige-privilegio", a primeira linha), como em AdmsAtualizacaoPendente. */
    private function exigePrivilegio(string $arquivo): bool
    {
        $h = @fopen(dirname(__DIR__, 5) . '/database/migrations/' . basename($arquivo), 'r');
        $primeira = $h ? (string) fgets($h) : '';
        if ($h) {
            fclose($h);
        }
        return (bool) preg_match('/^--\s*vcostaedu:exige-privilegio\b/', $primeira);
    }

    /* ------------------------------------------------------------ o lote da tela, em passos (Onda 3, Etapa 11) ---- */

    private ?\PDO $pdo = null;

    private function pdo(): \PDO
    {
        return $this->pdo ??= (new AdmsConexaoAvulsa())->pdo();
    }

    /** O lote da tela (null: nenhum). Com 'vivo': o último passo foi há menos de VIVO segundos. */
    public function lote(): ?array
    {
        try {
            $l = $this->pdo()->query('SELECT estado, TIMESTAMPDIFF(SECOND, atualizado_em, NOW()) parado FROM ' . self::LOTE . ' WHERE id = 1')->fetch(\PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return null; // a tabela só existe enquanto há atualização pela tela
        }
        if (!$l) {
            return null;
        }
        $e = json_decode((string) $l['estado'], true) ?: [];
        $e['vivo'] = (int) $l['parado'] < self::VIVO && empty($e['concluida']) && empty($e['falhou']);
        return $e;
    }

    private function gravarLote(array $e): void
    {
        unset($e['vivo']);
        $this->pdo()->exec('CREATE TABLE IF NOT EXISTS ' . self::LOTE . ' (`id` tinyint NOT NULL PRIMARY KEY, `estado` mediumtext NOT NULL,
            `atualizado_em` datetime NOT NULL) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci
            COMMENT \'a atualização pela tela, em passos, enquanto acontece (Onda 3, Etapa 11)\'');
        $this->pdo()->prepare('INSERT INTO ' . self::LOTE . ' (id, estado, atualizado_em) VALUES (1, ?, NOW()) ON DUPLICATE KEY UPDATE estado = VALUES(estado), atualizado_em = NOW()')
            ->execute([json_encode($e, JSON_UNESCAPED_UNICODE)]);
    }

    /** O fim do lote: a tela já mostrou o resultado; a tabela do lote sai (o banco fica como o do comando). */
    public function encerrarLote(): void
    {
        try {
            $this->pdo()->exec('DROP TABLE IF EXISTS ' . self::LOTE);
        } catch (Throwable $e) {
            error_log('[vcostaedu][atualizacao] encerrar o lote: ' . $e->getMessage());
        }
    }

    /** A atualização pela tela de OUTRA pessoa, em andamento agora (o nome dela), ou null. */
    public function emAndamentoPorOutro(string $dono): ?string
    {
        $l = $this->lote();
        return ($l && $l['vivo'] && ($l['dono'] ?? '') !== $dono) ? (string) ($l['quem'] ?? 'outra pessoa') : null;
    }

    /** O que a janela de progresso lê. */
    public static function publico(?array $e): ?array
    {
        if ($e === null) {
            return null;
        }
        $total = max(1, (int) $e['total']);
        $proxima = $e['falhou']['arquivo'] ?? ($e['proxima'] ?? '');
        return ['passo' => min((int) $e['feitos'], $total), 'total' => $total, 'grupoAtual' => $proxima, 'falhou' => $e['falhou'] ?? null,
            'concluida' => !empty($e['concluida'])];
    }

    /**
     * Começa — ou RETOMA o lote que parou (a etapa que falhou, a página fechada). Recusa enquanto outra pessoa estiver
     * atualizando pela tela. Devolve [ok, mensagem].
     */
    public function iniciarLote(string $dono, string $quem): array
    {
        $outro = $this->emAndamentoPorOutro($dono);
        if ($outro !== null) {
            return [false, 'Outra atualização está em andamento agora pela tela (' . $outro . '). Espere ela terminar. Nada foi aplicado.'];
        }
        $l = $this->lote();
        if ($l && empty($l['concluida'])) {
            $l['dono'] = $dono;
            $l['quem'] = $quem;
            $l['falhou'] = null;
            $this->gravarLote($l);
            return [true, ''];
        }
        $estado = $this->estado();
        if (!$estado['ok']) {
            return [false, $estado['erro']];
        }
        if ($estado['divergentes']) {
            return [false, 'Há etapas já aplicadas que foram alteradas nos arquivos. Nada foi aplicado.'];
        }
        if ($estado['emDia']) {
            return [false, 'O sistema já estava em dia. Nada foi alterado.'];
        }
        $this->gravarLote(['total' => count($estado['pendentes']), 'feitos' => 0, 'pendentes' => $estado['pendentes'], 'aplicadas' => [],
            'proxima' => $estado['pendentes'][0] ?? '', 'falhou' => null, 'concluida' => false, 'dono' => $dono, 'quem' => $quem,
            'inicio' => date('c'), 'versaoAntes' => self::versoes()['banco']]);
        $this->registrar(200, 'Atualização pela tela começou: ' . count($estado['pendentes']) . ' etapa(s) pendente(s).');
        return [true, ''];
    }

    /**
     * UM passo: uma etapa do banco. A falha para o lote (o que passou antes continua valendo, a que falhou fica pendente,
     * e "Tentar de novo" retoma por ela); a trava do runner tomada (o comando ou outro passo rodando) responde "ocupado"
     * e a janela espera.
     */
    public function passoLote(string $dono): array
    {
        $e = $this->lote();
        if (!$e) {
            return ['erro' => 'Não há atualização em andamento. Abra a tela "Atualizar o sistema" de novo.'];
        }
        if (!empty($e['concluida'])) {
            return self::publico($e);
        }
        if ($e['vivo'] && ($e['dono'] ?? '') !== $dono) {
            return ['erro' => 'Outra atualização está em andamento agora pela tela (' . ($e['quem'] ?? 'outra pessoa') . '). Espere ela terminar.'];
        }
        $e['dono'] = $dono;
        $e['falhou'] = null;
        $this->gravarLote($e); // o passo começou: o lote é de quem está aqui, vivo
        try {
            $r = $this->runner()->aplicarProxima();
        } catch (Throwable $x) {
            if (str_contains($x->getMessage(), 'em andamento')) {
                return ['ocupado' => true];
            }
            error_log('[vcostaedu][atualizacao] falha no passo: ' . $x->getMessage());
            $frase = $this->primeiraFrase($x->getMessage());
            $e['falhou'] = ['motivo' => $frase . ' O que passou antes continua valendo; parte desta etapa pode ter sido aplicada: o detalhe está no log do servidor, e o backup traz o banco de volta.',
                'arquivo' => $this->estado()['pendentes'][0] ?? ''];
            $this->gravarLote($e);
            $this->registrar(400, 'Atualização pela tela parou: ' . $frase);
            return self::publico($e);
        }
        $depois = $this->estado();
        if ($r !== null) {
            $e['aplicadas'][] = $r;
            $e['feitos'] = (int) $e['feitos'] + 1;
        }
        $e['proxima'] = $depois['pendentes'][0] ?? '';
        if ($r === null || $depois['emDia']) {
            $e['concluida'] = true;
            $e['feitos'] = (int) $e['total'];
            $e['versaoDepois'] = self::versoes()['banco'];
            AdmsAtualizacaoPendente::esquecer();
            $this->registrar(200, count($e['aplicadas']) . ' etapa(s) aplicada(s) na atualização pela tela.');
        }
        $this->gravarLote($e);
        return self::publico($e);
    }

    /**
     * Aplica o que falta (o COMANDO: tudo em sequência; recusa enquanto a tela estiver atualizando). O mesmo desfecho nas duas portas, inclusive na falha: o que passou antes dela continua valendo
     * e vem na lista; a causa vem numa frase; o que ficou pendente também.
     *
     * @return array{ok:bool, aplicadas:array, quantas:int, falhou:string, emAndamento:bool, pendentesDepois:string[]}
     */
    public function aplicar(): array
    {
        $runner = $this->runner();
        $l = $this->lote();
        if ($l && $l['vivo']) {
            $frase = 'Já há uma atualização em andamento neste banco de dados, pela tela (' . ($l['quem'] ?? 'outra pessoa') . '). Espere ela terminar antes de tentar de novo. Nada foi aplicado.';
            return ['ok' => false, 'aplicadas' => [], 'quantas' => 0, 'falhou' => $frase, 'emAndamento' => true, 'pendentesDepois' => $this->estado()['pendentes']];
        }
        try {
            $quantas = $runner->aplicar(false);
        } catch (Throwable $e) {
            error_log('[vcostaedu][atualizacao] falha ao aplicar: ' . $e->getMessage());
            $frase = $this->primeiraFrase($e->getMessage());
            $this->registrar(400, 'Atualização falhou: ' . $frase);
            return ['ok' => false, 'aplicadas' => $runner->resultados(), 'quantas' => count($runner->resultados()), 'falhou' => $frase,
                'emAndamento' => str_contains($e->getMessage(), 'em andamento'), 'pendentesDepois' => $this->estado()['pendentes']];
        }
        $this->registrar(200, $quantas . ' etapa(s) aplicada(s) na atualização.');
        if ($l) {
            $this->encerrarLote(); // o lote parado da tela: o comando terminou o que ele começou
        }
        return ['ok' => true, 'aplicadas' => $runner->resultados(), 'quantas' => $quantas, 'falhou' => '', 'emAndamento' => false, 'pendentesDepois' => $this->estado()['pendentes']];
    }

    /** A causa em uma frase, sem o rastro técnico (o erro do banco inteiro fica no log). */
    public function primeiraFrase(string $mensagem): string
    {
        $corte = strpos($mensagem, ': SQLSTATE');
        return $corte === false ? $mensagem : substr($mensagem, 0, $corte) . '.';
    }

    /** A versão registrada no banco (a da última migration de versão) e a dos arquivos (o pacote). */
    public static function versoes(): array
    {
        $doBanco = '';
        try {
            $ler = new AdmsRead();
            $ler->fullRead("SELECT numero, data_versao FROM adms_versoes WHERE situacao = :s ORDER BY id DESC LIMIT :limit", 's=1&limit=1');
            $r = $ler->getResultado()[0] ?? null;
            $doBanco = $r ? (string) $r['numero'] : '';
            $data = $r ? (string) $r['data_versao'] : '';
        } catch (Throwable $e) {
            $data = '';
        }
        require_once dirname(__DIR__, 5) . '/core/Legal.php'; // a versão dos arquivos (a ConfigView o carrega só ao montar a página)
        $doPacote = (string) \VcLegal::versaoDoPacote();
        return ['banco' => $doBanco, 'data' => $data, 'arquivos' => $doPacote];
    }

    private function runner(): AdmsMigrations
    {
        /* quatro níveis: Models/helper -> Models -> adms -> app -> painel; a raiz é um acima */
        return $this->runner ??= new AdmsMigrations(dirname(__DIR__, 5) . '/database/migrations');
    }

    private function registrar(int $codigo, string $descricao): void
    {
        try {
            (new AdmsSalvarLog())->salvarLogs(self::PAGINA, $codigo, 1, $descricao);
        } catch (Throwable $e) {
            error_log('[vcostaedu][atualizacao] log: ' . $e->getMessage());
        }
    }
}
