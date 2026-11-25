<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * DADOS DA INSTITUIÇÃO (E6a, D-09): a linha de adms_empresas marcada `instituicao = 1`,
 * cadastrada pelo administrador em Configurações → Dados da instituição. Os cabeçalhos
 * (boletim em PDF, telas, e-mails e, na E6b, o histórico) leem daqui; NOME_INSTITUICAO
 * da Config é só o fallback quando nada foi cadastrado. Arquivo IDÊNTICO no painel e no portal.
 */
class AdmsInstituicao
{
    /** Pasta pública (só imagens; nada executa) e tamanho máximo do logo. */
    public const PASTA_LOGO = 'assets/instituicao/';
    public const LOGO_MAX_BYTES = 1048576;

    private static $cache = null;

    public static function dados(): ?array
    {
        if (self::$cache === null) {
            $ler = new AdmsRead();
            $ler->fullRead("SELECT * FROM adms_empresas WHERE instituicao = :i AND situacao = :s ORDER BY id LIMIT :limit", "i=1&s=1&limit=1");
            self::$cache = $ler->getResultado()[0] ?? [];
        }
        return self::$cache ?: null;
    }

    public static function nome(): string
    {
        $d = self::dados();
        if ($d && trim((string) $d['nome']) !== '') {
            return (string) $d['nome'];
        }
        return defined('NOME_INSTITUICAO') ? NOME_INSTITUICAO : 'VCOSTAEDU';
    }

    /** URL do logo cadastrado; sem logo, o brasão neutro do sistema. */
    public static function logoUrl(): string
    {
        $d = self::dados();
        $raiz = dirname(__DIR__, 5) . '/';
        if ($d && !empty($d['logo']) && is_file($raiz . self::PASTA_LOGO . $d['logo'])) {
            return URL . self::PASTA_LOGO . rawurlencode((string) $d['logo']);
        }
        return URL . 'assets/marca/vcosta.png';
    }

    /** Há logo da instituição enviado pela tela? */
    public static function temLogo(): bool
    {
        $d = self::dados();
        return $d && !empty($d['logo']) && is_file(dirname(__DIR__, 5) . '/' . self::PASTA_LOGO . $d['logo']);
    }

    /** A instituição foi configurada pela tela (há a linha dela, com nome)? */
    public static function configurada(): bool
    {
        $d = self::dados();
        return $d !== null && trim((string) ($d['nome'] ?? '')) !== '';
    }

    /**
     * O que vai ao lado do selo, no topo do menu (Onda 3, Etapa 5): ['tipo' => 'vcostaedu'|'nome'|'logo', 'texto', 'logo'].
     * Sem instituição configurada, a palavra VCOSTAEDU; com ela, o nome escrito ou o logo (a escolha em Dados da
     * instituição; o logo, só se foi enviado).
     */
    public static function topoMenu(): array
    {
        if (!self::configurada()) {
            return ['tipo' => 'vcostaedu', 'texto' => 'VCOSTAEDU', 'logo' => ''];
        }
        $d = self::dados();
        $nome = (string) $d['nome'];
        if (($d['topo_menu'] ?? 'nome') === 'logo' && self::temLogo()) {
            return ['tipo' => 'logo', 'texto' => $nome, 'logo' => self::logoUrl()];
        }
        return ['tipo' => 'nome', 'texto' => $nome, 'logo' => ''];
    }

    /** O ícone da aba do navegador: o do VCOSTAEDU (o padrão) ou, se a instituição escolheu, o logo dela. */
    public static function iconeAbaUrl(): string
    {
        $d = self::dados();
        return (($d['icone_aba'] ?? 'vcostaedu') === 'instituicao' && self::temLogo()) ? self::logoUrl() : URL . 'assets/vcostaedu/favicon.png';
    }

    /** Endereço em uma linha (para cabeçalho), ou ''. */
    public static function endereco(): string
    {
        $d = self::dados() ?: [];
        $partes = array_filter([
            trim(($d['logradouro'] ?? '') . ((string) ($d['numero'] ?? '') !== '' ? ', ' . $d['numero'] : '')),
            $d['complemento'] ?? '', $d['bairro'] ?? '',
            trim(($d['cidade'] ?? '') . ((string) ($d['uf'] ?? '') !== '' ? '/' . $d['uf'] : '')),
            (string) ($d['cep'] ?? '') !== '' ? 'CEP ' . $d['cep'] : '',
        ], fn($p) => trim((string) $p) !== '');
        return implode(' — ', $partes);
    }

    /** E1a (D-339): quantas linhas o cabeçalho institucional aceita, e o tamanho de cada uma. */
    public const CABECALHO_LINHAS = 5;
    public const CABECALHO_MAX = 200;

    /** E1a: as linhas PREENCHIDAS do cabeçalho institucional (cabecalho_1 … _5), na ordem cadastrada. */
    public static function cabecalho(?array $d = null): array
    {
        $d = $d ?? (self::dados() ?: []);
        $linhas = [];
        for ($n = 1; $n <= self::CABECALHO_LINHAS; $n++) {
            $t = trim((string) ($d['cabecalho_' . $n] ?? ''));
            if ($t !== '') {
                $linhas[] = $t;
            }
        }
        return $linhas;
    }

    /** CNPJ: 14 dígitos (com ou sem máscara), não repetidos, dígitos verificadores certos. */
    public static function cnpjValido(string $cnpj): bool
    {
        $n = preg_replace('/\D/', '', $cnpj);
        if (strlen($n) !== 14 || preg_match('/^(\d)\1{13}$/', $n)) {
            return false;
        }
        foreach ([12, 13] as $t) {
            $pesos = $t === 12 ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2] : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            $s = 0;
            for ($i = 0; $i < $t; $i++) {
                $s += (int) $n[$i] * $pesos[$i];
            }
            $d = $s % 11 < 2 ? 0 : 11 - $s % 11;
            if ((int) $n[$t] !== $d) {
                return false;
            }
        }
        return true;
    }

    public static function mascaraCnpj(string $cnpj): string
    {
        $n = preg_replace('/\D/', '', $cnpj);
        return strlen($n) === 14 ? substr($n, 0, 2) . '.' . substr($n, 2, 3) . '.' . substr($n, 5, 3) . '/' . substr($n, 8, 4) . '-' . substr($n, 12, 2) : $cnpj;
    }
}
