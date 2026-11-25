<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * O PAPEL DO NÍVEL DE ACESSO — Onda 2, Etapa 2 (docs/NIVEIS_PRODUTO_ABERTO.md).
 *
 * No produto aberto quem instala é o dono: não existe nível do fornecedor (os antigos 7, 8 e 9 saíram na migration
 * 0005, com o 2, que repetia o papel do administrador). O que o código pergunta deixou de ser o id do nível: é o
 * PAPEL, gravado no banco (adms_niveis_acessos.papel), e as duas marcas do nível — só consulta (so_consulta) e
 * ações sensíveis (acoes_sensiveis). Nível criado na tela nasce sem papel e sem marcas e segue só a matriz dele.
 *
 * Até a 0005 ser aplicada (os arquivos novos antes do banco, D-28), as respostas saem do id, como antes: 1 e 2
 * administram, 3 é a direção, 4 a secretaria, 5 a coordenação, 6 o professor, e ninguém é "só consulta".
 */
class AdmsNivel
{
    public const ADMINISTRADOR = 'administrador';
    public const DIRECAO = 'direcao';
    public const SUPERVISAO = 'supervisao_secretaria';
    public const SECRETARIA = 'secretaria';
    public const COORDENACAO = 'coordenacao';
    public const PROFESSOR = 'professor';

    /** O papel de cada id ANTES da 0005 (só para a instalação que ainda não aplicou a migration). */
    private const LEGADO = [1 => self::ADMINISTRADOR, 2 => self::ADMINISTRADOR, 3 => self::DIRECAO, 4 => self::SECRETARIA, 5 => self::COORDENACAO, 6 => self::PROFESSOR];

    /** @var array<int, array{papel:?string, so_consulta:bool, acoes_sensiveis:bool}> */
    private static array $cache = [];

    public static function nivel(): int
    {
        return (int) ($_SESSION['colUsuario_nivel'] ?? 0);
    }

    /** A 0005 (os papéis) já está no banco? */
    public static function comPapeis(): bool
    {
        return AdmsAtualizacaoPendente::exige('0005');
    }

    /** @return array{papel:?string, so_consulta:bool, acoes_sensiveis:bool} */
    public static function marcas(?int $nivel = null): array
    {
        $nivel ??= self::nivel();
        if (isset(self::$cache[$nivel])) {
            return self::$cache[$nivel];
        }
        if (!self::comPapeis()) {
            $papel = self::LEGADO[$nivel] ?? null;
            return self::$cache[$nivel] = ['papel' => $papel, 'so_consulta' => false, 'acoes_sensiveis' => $papel === self::ADMINISTRADOR];
        }
        $ler = new AdmsRead();
        $ler->fullRead("SELECT papel, so_consulta, acoes_sensiveis FROM adms_niveis_acessos WHERE id =:id LIMIT :limit", "id={$nivel}&limit=1");
        $r = $ler->getResultado()[0] ?? [];
        return self::$cache[$nivel] = [
            'papel' => isset($r['papel']) && $r['papel'] !== '' ? (string) $r['papel'] : null,
            'so_consulta' => (int) ($r['so_consulta'] ?? 0) === 1,
            'acoes_sensiveis' => (int) ($r['acoes_sensiveis'] ?? 0) === 1,
        ];
    }

    public static function papel(?int $nivel = null): ?string
    {
        return self::marcas($nivel)['papel'];
    }

    public static function ehPapel(string ...$papeis): bool
    {
        return in_array(self::papel(), $papeis, true);
    }

    /** O Administrador (o nível criado na instalação; vê e altera tudo). */
    public static function ehAdministrador(?int $nivel = null): bool
    {
        return self::papel($nivel) === self::ADMINISTRADOR;
    }

    /** Direção acadêmica e Coordenação de curso: consultam e veem relatórios; nenhuma rota que grava abre. */
    public static function soConsulta(?int $nivel = null): bool
    {
        return self::marcas($nivel)['so_consulta'];
    }

    /** As ações sensíveis (reabrir a disciplina encerrada, excluir registro acadêmico, desfazer importação). */
    public static function podeSensivel(?int $nivel = null): bool
    {
        return self::marcas($nivel)['acoes_sensiveis'];
    }

    /** @var array<string, int>|null */
    private static ?array $ordens = null;

    /** A posição de cada papel na ordem dos perfis (1 = Administrador, o mais alto). */
    public static function ordensDosPapeis(): array
    {
        if (self::$ordens === null) {
            self::$ordens = [];
            if (self::comPapeis()) {
                $ler = new AdmsRead();
                $ler->fullRead("SELECT papel, ordem FROM adms_niveis_acessos WHERE papel IS NOT NULL AND papel <> ''");
                foreach ($ler->getResultado() ?: [] as $l) {
                    self::$ordens[(string) $l['papel']] = (int) $l['ordem'];
                }
            }
        }
        return self::$ordens;
    }

    /**
     * O nível está ABAIXO do teto da ação (docs/PENTE_FINO.md)? O teto é o perfil mais baixo que pode receber a ação;
     * um perfil depois dele na ordem não pode, mesmo que a matriz diga que sim. O nível sem papel (criado pela tela)
     * não tem lugar na ordem dos perfis e segue só a matriz dele. Teto que não é papel conhecido: só o Administrador
     * passa.
     */
    public static function abaixoDoTeto(?string $teto, ?int $nivel = null): bool
    {
        if ($teto === null || $teto === '') {
            return false;
        }
        $papel = self::papel($nivel);
        if ($papel === null || $papel === self::ADMINISTRADOR) {
            return false;
        }
        $ordens = self::ordensDosPapeis();
        if (!isset($ordens[$teto], $ordens[$papel])) {
            return true;
        }
        return $ordens[$papel] > $ordens[$teto];
    }

    /** O id do nível de um papel (ex.: o do Administrador). Antes da 0005, pelo id legado. */
    public static function idDoPapel(string $papel): ?int
    {
        if (!self::comPapeis()) {
            $id = array_search($papel, self::LEGADO, true);
            return $id === false ? null : (int) $id;
        }
        $ler = new AdmsRead();
        $ler->fullRead("SELECT id FROM adms_niveis_acessos WHERE papel =:p LIMIT :limit", "p={$papel}&limit=1");
        $r = $ler->getResultado();
        return $r ? (int) $r[0]['id'] : null;
    }

}
