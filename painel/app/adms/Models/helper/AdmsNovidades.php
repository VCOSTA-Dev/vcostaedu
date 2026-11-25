<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * NOVIDADES por versão (E6c), na linha do projeto de referência: cada novidade (adms_novidades) diz a
 * que PERFIS interessa (administrador, secretaria, professor, aluno) e a que GUIA leva. O mesmo
 * arquivo existe no painel e no portal (o portal registra em col_novidades_vistas, porque tem
 * outro cadastro de usuários).
 *
 * O AVISO (E7, D-218 e D-219):
 * - aparece na página inicial UMA VEZ POR LOGIN (marca na sessão); só vira "visto" no clique em
 *   "Entendi" ou ao fechar — quem sair sem ler vê de novo no próximo login, não a cada tela;
 * - usuário NOVO (nenhuma versão vista) recebe BOAS-VINDAS com o link para o "Comece por aqui";
 *   ao dispensar, todas as versões até a atual contam como vistas.
 */
class AdmsNovidades
{
    private static function portal(): bool
    {
        return defined('VCOSTAEDU_APP_PORTAL');
    }

    private static function usuarioId(): int
    {
        return (int) (self::portal() ? ($_SESSION['academico_id'] ?? 0) : ($_SESSION['colUsuario_id'] ?? 0));
    }

    private static function tabela(): array
    {
        return self::portal() ? ['col_novidades_vistas', 'col_usuario_id'] : ['adms_novidades_vistas', 'usuario_id'];
    }

    /** Todas as versões (da mais nova para a mais antiga), cada uma com as novidades do perfil. */
    public static function listar(?string $perfil): array
    {
        if ($perfil === null) {
            return [];
        }
        $ler = new AdmsRead();
        $ler->fullRead(
            "SELECT v.id AS versao_id, v.numero, v.data_versao, v.resumo, v.situacao, n.id, n.titulo, n.texto, n.guia, n.ordem
               FROM adms_versoes v INNER JOIN adms_novidades n ON n.adms_versao_id = v.id
              WHERE FIND_IN_SET(:p, n.perfis) > 0
              ORDER BY v.major DESC, v.minor DESC, v.patch DESC, n.ordem, n.id",
            'p=' . urlencode($perfil)
        );
        $por = [];
        $guias = self::portal() ? [] : (AdmsCentralGuias::indice()['guias'] ?? []);
        foreach ($ler->getResultado() ?: [] as $r) {
            /* Onda 4: a novidade que leva a um guia de outro tipo de instituição (a escola, no ensino superior) não aparece */
            $tipoGuia = (string) ($guias[(string) $r['guia']]['tipo'] ?? '');
            if ($tipoGuia !== '' && !AdmsTipoInstituicao::moduloLigado($tipoGuia)) {
                continue;
            }
            $k = (int) $r['versao_id'];
            $por[$k] = $por[$k] ?? ['id' => $k, 'numero' => $r['numero'], 'data' => $r['data_versao'], 'atual' => (int) $r['situacao'] === 1, 'itens' => []];
            $por[$k]['itens'][] = ['titulo' => $r['titulo'], 'texto' => $r['texto'], 'guia' => $r['guia']];
        }
        return array_values($por);
    }

    /** A versão atual (situação 1). */
    private static function versaoAtual(): ?array
    {
        $ler = new AdmsRead();
        $ler->fullRead("SELECT id, numero, major, minor, patch FROM adms_versoes WHERE situacao = :s ORDER BY id DESC LIMIT :limit", "s=1&limit=1");
        return $ler->getResultado()[0] ?? null;
    }

    private static function jaViu(int $uid): array
    {
        [$tabela, $coluna] = self::tabela();
        $ler = new AdmsRead();
        $ler->fullRead("SELECT adms_versao_id FROM {$tabela} WHERE {$coluna} = :u", "u={$uid}");
        return array_map('intval', array_column($ler->getResultado() ?: [], 'adms_versao_id'));
    }

    /**
     * O aviso pendente, ou null: ['tipo' => 'boasvindas'] para quem nunca viu versão nenhuma,
     * ou ['tipo' => 'novidades', ...versão atual com os itens do perfil] se a atual não foi vista.
     * Não marca nada como visto. E8b (D-253, substitui a regra de sessão da D-218): aparece TODA VEZ que a
     * Página Inicial abre, até o usuário clicar em Entendi ou no X — os dois gravam o visto no banco.
     */
    public static function avisoPendente(?string $perfil): ?array
    {
        $uid = self::usuarioId();
        if ($perfil === null || $uid < 1) {
            return null;
        }
        $atual = self::versaoAtual();
        if (!$atual) {
            return null;
        }
        $vistas = self::jaViu($uid);
        $aviso = null;
        if (!$vistas) {
            $aviso = ['tipo' => 'boasvindas', 'id' => (int) $atual['id'], 'numero' => $atual['numero']];
        } elseif (!in_array((int) $atual['id'], $vistas, true)) {
            foreach (self::listar($perfil) as $v) {
                if ($v['id'] === (int) $atual['id']) {
                    $aviso = ['tipo' => 'novidades'] + $v;
                }
            }
        }
        return $aviso;
    }

    /**
     * "Entendi" (ou fechar): marca a versão atual como vista; para quem nunca viu nada
     * (boas-vindas), marca TODAS as versões até a atual (D-219).
     */
    public static function marcarVisto(): bool
    {
        $uid = self::usuarioId();
        $atual = self::versaoAtual();
        if ($uid < 1 || !$atual) {
            return false;
        }
        [$tabela, $coluna] = self::tabela();
        $vistas = self::jaViu($uid);
        $ler = new AdmsRead();
        $ler->fullRead(
            "SELECT id FROM adms_versoes WHERE (major < :ma) OR (major = :mb AND minor < :mi) OR (major = :mc AND minor = :md AND patch <= :pa)",
            "ma={$atual['major']}&mb={$atual['major']}&mi={$atual['minor']}&mc={$atual['major']}&md={$atual['minor']}&pa={$atual['patch']}"
        );
        $ate = array_map('intval', array_column($ler->getResultado() ?: [], 'id'));
        $marcar = $vistas ? [(int) $atual['id']] : $ate;
        foreach (array_diff($marcar, $vistas) as $v) {
            (new AdmsCreate())->exeCreate($tabela, ['adms_versao_id' => $v, $coluna => $uid, 'created' => date('Y-m-d H:i:s')]);
        }
        return true;
    }
}
