<?php

/**
 * O AVISO DE VERSÃO NOVA (Onda 3, Etapa 6).
 *
 * Pergunta a um endereço FIXO (VcLegal::URL_VERSAO_NOVA, ou a constante URL_VERSAO_NOVA da configuração) qual é a versão
 * mais nova, e diz ao Administrador se há uma mais nova que a instalada. As regras:
 *   - no máximo UMA consulta por dia (o resultado fica em adms_instalacao_estado e vale 24 horas);
 *   - tempo curto (3 segundos para conectar e responder): servidor fora do ar não atrasa a tela;
 *   - NADA de identificação: um GET sem parâmetro, sem cookie, sem a versão instalada, sem o endereço desta instalação;
 *   - NUNCA baixa nada: lê só um JSON pequeno ({"versao": "1.40.0", "notas": "https://…"}), até 16 KB;
 *   - desligável: o parâmetro "sistema.verificar_versao_nova" = 0 (ou o endereço vazio) e nada é consultado.
 */
require_once __DIR__ . '/Legal.php';

final class VcVersaoNova
{
    public const PARAMETRO = 'sistema.verificar_versao_nova';
    private const CACHE = 'versao_nova.consulta';
    private const VALIDADE = 86400;
    private const TEMPO = 3;
    private const LIMITE = 16384;

    public static function endereco(): string
    {
        return defined('URL_VERSAO_NOVA') ? (string) URL_VERSAO_NOVA : VcLegal::URL_VERSAO_NOVA;
    }

    /** Está ligado? (o parâmetro e o endereço) */
    public static function ligado(PDO $pdo): bool
    {
        if (self::endereco() === '') {
            return false;
        }
        $st = $pdo->prepare('SELECT valor FROM adms_parametros WHERE chave = ?');
        $st->execute([self::PARAMETRO]);
        $v = $st->fetchColumn();
        return $v === false || (string) $v !== '0';
    }

    /**
     * O que a tela mostra: ['ligado', 'novo' (a versão mais nova, se for maior que a instalada), 'notas', 'consultada_em',
     * 'erro' (a consulta não respondeu; nada é mostrado como novidade)].
     */
    public static function situacao(PDO $pdo, string $instalada, ?int $agora = null): array
    {
        $agora ??= time();
        if (!self::ligado($pdo)) {
            return ['ligado' => false, 'novo' => null, 'notas' => null, 'consultada_em' => null, 'erro' => null];
        }
        $st = $pdo->prepare('SELECT valor FROM adms_instalacao_estado WHERE chave = ?');
        $st->execute([self::CACHE]);
        $cache = json_decode((string) $st->fetchColumn(), true);
        if (!is_array($cache) || ($agora - (int) ($cache['em'] ?? 0)) >= self::VALIDADE || ($cache['endereco'] ?? '') !== self::endereco()) {
            $cache = ['em' => $agora, 'endereco' => self::endereco()] + self::consultar(self::endereco());
            $pdo->prepare('INSERT INTO adms_instalacao_estado (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)')
                ->execute([self::CACHE, json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        }
        $versao = (string) ($cache['versao'] ?? '');
        $maior = $versao !== '' && preg_match('/^\d+\.\d+\.\d+$/', $versao) && version_compare($versao, $instalada, '>');
        return ['ligado' => true, 'novo' => $maior ? $versao : null, 'notas' => $maior ? ($cache['notas'] ?? null) : null,
            'seguranca' => $maior && !empty($cache['seguranca']), // Onda 6, Etapa 7: a versão nova corrige falha de segurança
            'consultada_em' => date('Y-m-d H:i', (int) $cache['em']), 'erro' => $cache['erro'] ?? null];
    }

    /** Uma consulta (sem identificação, tempo curto, só o JSON pequeno). */
    private static function consultar(string $url): array
    {
        if (!preg_match('~^https?://[^\s?#]+$~i', $url)) {
            return ['erro' => 'endereço inválido'];
        }
        $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => self::TEMPO, 'ignore_errors' => true, 'follow_location' => 0,
            'header' => "Accept: application/json\r\nUser-Agent: VCOSTAEDU\r\nConnection: close\r\n"],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $corpo = @file_get_contents($url, false, $ctx, 0, self::LIMITE + 1);
        if ($corpo === false || strlen($corpo) > self::LIMITE) {
            return ['erro' => 'sem resposta'];
        }
        $j = json_decode($corpo, true);
        if (!is_array($j) || !isset($j['versao']) || !preg_match('/^\d+\.\d+\.\d+$/', (string) $j['versao'])) {
            return ['erro' => 'resposta inesperada'];
        }
        $notas = isset($j['notas']) && preg_match('#^https://\S+$#', (string) $j['notas']) ? (string) $j['notas'] : null;
        return ['versao' => (string) $j['versao'], 'notas' => $notas, 'seguranca' => ($j['seguranca'] ?? false) === true];
    }
}
