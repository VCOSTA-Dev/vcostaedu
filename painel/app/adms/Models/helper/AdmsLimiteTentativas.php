<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * LIMITE DE TENTATIVAS (E7, D-73). O mesmo arquivo existe no painel e no portal.
 *
 * Conta, em seg_tentativas, por ESCOPO e por CHAVE — o identificador digitado (usuário, e-mail, RA,
 * CPF) e o IP —, guardando só o sha256 da chave e uma dica mascarada (nunca a senha, nunca o token).
 * A contagem é do TEXTO digitado, exista ou não a conta: a espera não revela se o usuário existe.
 *
 * CURVA: até o limite, nada; a partir da falha número LIMITE, espera de 30 s, que dobra a cada nova
 * falha (30 s, 1 min, 2 min, 4 min, 8 min) até o teto de 15 min. A contagem recomeça depois da janela
 * sem bloqueio. Login certo ZERA o identificador (o IP segue a sua conta).
 *
 * Bloqueio tentado vai para col_tentativas_acesso (IP mascarado, escopo, espera) e para o error_log.
 */
class AdmsLimiteTentativas
{
    public const BASE = 30;
    public const TETO = 900;
    /** Limite por IDENTIFICADOR (fixo) e as janelas. O limite por IP vem de Configurações → Instituição (E8, D-237). */
    public const REGRAS = [
        'login_painel'   => ['id' => 5, 'janela_id' => 86400, 'ip_param' => 'login', 'janela_ip' => 3600],
        'login_portal'   => ['id' => 5, 'janela_id' => 86400, 'ip_param' => 'login', 'janela_ip' => 3600],
        'esqueci_painel' => ['id' => 3, 'janela_id' => 3600, 'ip_param' => 'esqueci', 'janela_ip' => 3600],
        'esqueci_portal' => ['id' => 3, 'janela_id' => 3600, 'ip_param' => 'esqueci', 'janela_ip' => 3600],
        'verificacao'    => ['id' => null, 'janela_id' => 0, 'ip_param' => 'verificacao', 'janela_ip' => 600],
        /* Onda 7: o link do convite do responsável (e o de senha nova) — o código é aleatório; conta o IP, como a verificação */
        'convite_portal' => ['id' => null, 'janela_id' => 0, 'ip_param' => 'verificacao', 'janela_ip' => 600],
    ];

    /** As regras em vigor: [escopo => [id, ip, janela_id, janela_ip]], com o limite por IP lido da instituição. */
    public static function regras(): array
    {
        $ip = AdmsParametro::limitesIp();
        $o = [];
        foreach (self::REGRAS as $e => $r) {
            $o[$e] = ['id' => $r['id'], 'ip' => $ip[$r['ip_param']], 'janela_id' => $r['janela_id'], 'janela_ip' => $r['janela_ip']];
        }
        return $o;
    }

    public static function ip(): string
    {
        return \Core\Seguranca::ipCliente(); // E8 (D-236): proxy confiável
    }

    public static function curva(int $falhas, int $limite): int
    {
        return $falhas < $limite ? 0 : (int) min(self::TETO, self::BASE * (2 ** ($falhas - $limite)));
    }

    /** As chaves do escopo: [tipo => [valor, limite, janela]] — identificador (se houver regra) e IP. */
    private static function chaves(string $escopo, ?string $ident): array
    {
        $r = self::regras()[$escopo];
        $c = [];
        if ($r['id'] !== null && $ident !== null && trim($ident) !== '') {
            $c['id'] = [mb_strtolower(trim($ident)), $r['id'], $r['janela_id']];
        }
        $c['ip'] = [self::ip(), $r['ip'], $r['janela_ip']];
        return $c;
    }

    private static function dica(string $tipo, string $v): string
    {
        if ($tipo === 'ip') {
            return preg_replace(['/\.\d+$/', '/:[0-9a-f]*$/i'], ['.x', ':x'], $v);
        }
        return mb_substr($v, 0, 2) . '…(' . mb_strlen($v) . ')';
    }

    private static function linha(string $escopo, string $tipo, string $hash): ?array
    {
        $ler = new AdmsRead();
        $ler->fullRead("SELECT * FROM seg_tentativas WHERE escopo = :e AND tipo = :t AND chave = :c LIMIT :limit", "e={$escopo}&t={$tipo}&c={$hash}&limit=1");
        return $ler->getResultado()[0] ?? null;
    }

    /** Segundos de espera que faltam (o maior entre identificador e IP); 0 = pode tentar. */
    public static function espera(string $escopo, ?string $ident): int
    {
        $max = 0;
        foreach (self::chaves($escopo, $ident) as $tipo => [$v]) {
            $l = self::linha($escopo, $tipo, hash('sha256', $v));
            if ($l && $l['bloqueado_ate'] && strtotime($l['bloqueado_ate']) > time()) {
                $max = max($max, strtotime($l['bloqueado_ate']) - time());
            }
        }
        return $max;
    }

    /** Conta uma tentativa (falha de login, pedido de link, consulta). Devolve a espera que passa a valer. */
    public static function registrar(string $escopo, ?string $ident): int
    {
        $agora = date('Y-m-d H:i:s');
        $max = 0;
        foreach (self::chaves($escopo, $ident) as $tipo => [$v, $limite, $janela]) {
            $hash = hash('sha256', $v);
            $l = self::linha($escopo, $tipo, $hash);
            $bloqueado = $l && $l['bloqueado_ate'] && strtotime($l['bloqueado_ate']) > time();
            $falhas = (!$l || (!$bloqueado && time() - strtotime($l['primeira']) > $janela)) ? 1 : (int) $l['falhas'] + 1;
            $espera = self::curva($falhas, $limite);
            $dados = ['falhas' => $falhas, 'ultima' => $agora, 'bloqueado_ate' => $espera ? date('Y-m-d H:i:s', time() + $espera) : null];
            if ($falhas === 1) {
                $dados['primeira'] = $agora;
            }
            if ($l) {
                (new AdmsUpdate())->exeUpdate('seg_tentativas', $dados, 'WHERE id = :id', "id={$l['id']}");
            } else {
                (new AdmsCreate())->exeCreate('seg_tentativas', $dados + ['escopo' => $escopo, 'tipo' => $tipo, 'chave' => $hash, 'dica' => self::dica($tipo, $v), 'primeira' => $agora]);
            }
            $max = max($max, $espera);
        }
        return $max;
    }

    /** Login certo: zera o identificador. */
    public static function zerar(string $escopo, string $ident): void
    {
        (new AdmsDeletar())->exeDelete('seg_tentativas', 'WHERE escopo = :e AND tipo = :t AND chave = :c', "e={$escopo}&t=id&c=" . hash('sha256', mb_strtolower(trim($ident))));
    }

    /** Registro de tentativa BLOQUEADA — sem senha, sem token, sem o identificador por inteiro. */
    public static function logBloqueio(string $escopo, ?string $ident, int $espera): void
    {
        $desc = "Tentativa bloqueada ({$escopo}); espera de {$espera} s" . ($ident !== null && $ident !== '' ? '; identificador ' . self::dica('id', mb_strtolower(trim($ident))) : '') . '.';
        (new AdmsCreate())->exeCreate('col_tentativas_acesso', ['usuario_ip' => self::dica('ip', self::ip()), 'observacao' => $escopo, 'descricao' => $desc, 'created' => date('Y-m-d H:i:s')]);
        error_log('[vcostaedu][limite] ' . $desc . ' ip=' . self::dica('ip', self::ip()));
    }

    /** A mensagem de espera (igual para quem existe e para quem não existe). */
    public static function mensagem(int $espera): string
    {
        $min = max(1, (int) ceil($espera / 60));
        return "Muitas tentativas. Aguarde {$min} minuto" . ($min > 1 ? 's' : '') . ' e tente de novo.';
    }
}
