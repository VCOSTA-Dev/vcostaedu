<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Configurações da instituição em adms_parametros (chave / valor / grupo /
 * descrição). Arquivo IDÊNTICO no painel e no portal.
 *
 * Chaves em uso (E2b):
 *   portal.login.campo       ra | cpf | email  — campo do login do portal
 *   portal.senha.link_horas  horas de validade do link de definição de senha
 *   (E4) nota.casas, nota.media_minima, frequencia.minima
 *   (E5) portal.media_parcial 1 | 0 — o portal mostra a média parcial da oferta aberta
 *   (E8c) matriz.modulos 1 | 0 — organizar a matriz em módulos (padrão 0: sem módulo, D-268)
 */
class AdmsParametro extends AdmsConn
{
    public static function ler(string $chave, ?string $padrao = null): ?string
    {
        $pdo = (new self())->connect();
        $st = $pdo->prepare('SELECT valor FROM adms_parametros WHERE chave = :c LIMIT 1');
        $st->execute(['c' => $chave]);
        $v = $st->fetchColumn();
        return ($v === false || $v === null) ? $padrao : (string) $v;
    }

    /** A instalação é uma escola (adms_instalacao_estado, instituicao.tipo)? */
    public static function naEscola(): bool
    {
        try {
            $pdo = (new self())->connect();
            $st = $pdo->prepare("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo' LIMIT 1");
            $st->execute();
            return (string) $st->fetchColumn() === 'escola';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Fechamento (D-106): o tipo da instituição (escola, superior ou livre; sem a chave, superior). */
    public static function tipoInstituicao(): string
    {
        try {
            $pdo = (new self())->connect();
            $v = (string) $pdo->query("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo' LIMIT 1")->fetchColumn();
            return in_array($v, ['escola', 'superior', 'livre'], true) ? $v : 'superior';
        } catch (\Throwable $e) {
            return 'superior';
        }
    }

    /** D-106: a instalação é um curso livre? */
    public static function noLivre(): bool
    {
        return self::tipoInstituicao() === 'livre';
    }

    /** D-106: a estrutura é por turma (a escola e o curso livre)? */
    public static function porTurma(): bool
    {
        return in_array(self::tipoInstituicao(), ['escola', 'livre'], true);
    }

    public static function gravar(string $chave, string $valor, int $usuarioId): bool
    {
        $pdo = (new self())->connect();
        $st = $pdo->prepare('UPDATE adms_parametros SET valor = :v, usuario_mod = :u WHERE chave = :c');
        $st->execute(['v' => $valor, 'u' => $usuarioId, 'c' => $chave]);
        return $st->rowCount() === 1 || self::ler($chave) === $valor;
    }

    /**
     * Fechamento: o valor confirmado sem mudança (o padrão, salvo como está) — o valor não é regravado (D-103, B7), mas a
     * primeira confirmação fica registrada (quem confirmou), como pede a fase "As regras" dos Primeiros passos.
     */
    public static function confirmar(string $chave, int $usuarioId): void
    {
        $pdo = (new self())->connect();
        $st = $pdo->prepare('UPDATE adms_parametros SET usuario_mod = :u WHERE chave = :c AND usuario_mod IS NULL');
        $st->execute(['u' => $usuarioId, 'c' => $chave]);
    }

    /** Campo de login do portal: ra (padrão), cpf ou email. Na escola (Onda 8, D-67), sempre a matrícula. */
    public static function campoLoginPortal(): string
    {
        if (self::naEscola()) {
            return 'ra';
        }
        $v = self::ler('portal.login.campo', 'ra');
        return in_array($v, ['ra', 'cpf', 'email'], true) ? $v : 'ra';
    }

    /**
     * Regras de nota e frequência (E4): casas da média final (1 ou 2), média mínima
     * (0 a 10) e frequência mínima (%). Valores fora da faixa voltam ao padrão.
     */
    public static function regrasNotas(): array
    {
        $casas = (int) self::ler("nota.casas", "1");
        $media = (float) self::ler("nota.media_minima", "7.0");
        $freq = (float) self::ler("frequencia.minima", "75");
        return [
            "casas" => in_array($casas, [1, 2], true) ? $casas : 1,
            "mediaMinima" => ($media >= 0 && $media <= 10) ? number_format($media, 2, ".", "") : "7.00",
            "frequenciaMinima" => ($freq >= 0 && $freq <= 100) ? number_format($freq, 2, ".", "") : "75.00",
        ];
    }
    /** E2 (D-361): siglas e rótulos da frequência suficiente/insuficiente (Configurações → Instituição). */
    public static function textosFrequencia(): array
    {
        static $t = null;
        return $t ??= [
            'siglaSuficiente' => self::ler('frequencia.sigla_suficiente', 'FS'), 'rotuloSuficiente' => self::ler('frequencia.rotulo_suficiente', 'Frequência Suficiente'),
            'siglaInsuficiente' => self::ler('frequencia.sigla_insuficiente', 'FI'), 'rotuloInsuficiente' => self::ler('frequencia.rotulo_insuficiente', 'Frequência Insuficiente'),
        ];
    }

    /** E5 (D-314): como a MÉDIA FINAL DO ALUNO é calculada: aritmética simples (padrão) ou ponderada pela CH. */
    public const MEDIA_FINAL_MODOS = ['aritmetica' => 'Aritmética simples das disciplinas', 'ponderada' => 'Ponderada pela carga horária'];

    public static function mediaFinalModo(): string
    {
        $v = (string) self::ler('documento.media_final', 'aritmetica');
        return isset(self::MEDIA_FINAL_MODOS[$v]) ? $v : 'aritmetica';
    }

    /** E1b (D-357): formas NEUTRAS da concordância (sexo Outros), editáveis em Configurações → Instituição. */
    public static function formasNeutras(): array
    {
        return [
            'rotulo' => self::ler('aluno.rotulo_neutro', 'ESTUDANTE'), 'matriculado' => self::ler('aluno.matriculado_neutro', 'com matrícula regular'),
            'artigo' => self::ler('aluno.artigo_neutro', 'o(a)'),
        ];
    }

    /** E5: o portal mostra ao aluno a média parcial da oferta ABERTA? Padrão: sim. A final aparece sempre. */
    public static function mostrarMediaParcial(): bool
    {
        return self::ler('portal.media_parcial', '1') !== '0';
    }

    /**
     * E8c (D-268): "Organizar a matriz em módulos". Padrão: NÃO — curso → grade → disciplinas, e cada grade tem um
     * módulo INTERNO, nunca exibido (AdmsModuloInterno). Com Sim, tudo como na 1.7.2. Lido uma vez por requisição.
     */
    public static function usaModulos(): bool
    {
        static $v = null;
        /* Onda 4, Etapa 2: na escola, a série é o módulo da matriz — os módulos valem sempre (o parâmetro é do ensino superior) */
        return $v ??= AdmsTipoInstituicao::ehEscola() || self::ler('matriz.modulos', '0') === '1';
    }

    /**
     * E8 (D-237): limites por IP do limite de tentativas (o IP pode ser de uma rede compartilhada: laboratório,
     * escola). Faixas e padrões em LIMITES_IP; valor fora da faixa volta ao padrão.
     */
    public const LIMITES_IP = [
        'login'       => ['chave' => 'limite.ip.login', 'padrao' => 50, 'min' => 10, 'max' => 1000],
        'esqueci'     => ['chave' => 'limite.ip.esqueci', 'padrao' => 20, 'min' => 5, 'max' => 500],
        'verificacao' => ['chave' => 'limite.ip.verificacao', 'padrao' => 60, 'min' => 10, 'max' => 1000],
    ];

    /**
     * Onda 8, correção, segunda leva, 2.7 (D-93): o limite de chamadas por minuto de cada chave da API, em Parâmetros.
     * A faixa vale para a tela; a API lê o parâmetro como sempre (no mínimo 1).
     */
    public const API_LIMITE = ['chave' => 'api.limite_por_minuto', 'padrao' => 120, 'min' => 10, 'max' => 10000];

    /** O limite por minuto de cada chave da API (o valor guardado; vazio ou não numérico, o padrão). */
    public static function apiLimitePorMinuto(): int
    {
        $v = (string) self::ler(self::API_LIMITE['chave'], (string) self::API_LIMITE['padrao']);
        return preg_match('/^\d{1,6}$/', $v) ? max(1, (int) $v) : self::API_LIMITE['padrao'];
    }

    public static function limitesIp(): array
    {
        $o = [];
        foreach (self::LIMITES_IP as $k => $d) {
            $v = self::ler($d['chave'], (string) $d['padrao']);
            $o[$k] = (preg_match('/^\d{1,4}$/', (string) $v) && (int) $v >= $d['min'] && (int) $v <= $d['max']) ? (int) $v : $d['padrao'];
        }
        return $o;
    }

    public static function rotuloCampoLogin(string $campo): string
    {
        return ['ra' => 'RA', 'cpf' => 'CPF', 'email' => 'E-mail'][$campo] ?? 'RA';
    }
}
