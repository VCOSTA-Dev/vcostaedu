<?php

/**
 * A CONFERÊNCIA DO TETO DAS AÇÕES (docs/PENTE_FINO.md) — uma implementação só, para o instalador, a verificação do
 * ambiente e as verificações de segurança.
 *
 * Cada página aponta para a AÇÃO dela (adms_acoes, migration 0015), e cada ação tem um TETO: o perfil mais baixo que
 * pode recebê-la, pela ordem dos perfis. A matriz (adms_nivacs_pgs) que dá a ação a um perfil abaixo do teto está
 * errada: o motor recusa (AdmsPaginas::nivelPode) e esta conferência reprova. Nível sem papel (criado pela tela numa
 * instalação antiga) não tem lugar na ordem e não é julgado aqui; o Administrador recebe tudo.
 */
final class VcTeto
{
    /** A 0015 está no banco? */
    public static function temAcoes(\PDO $pdo): bool
    {
        return (int) $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'adms_paginas' AND COLUMN_NAME = 'acao_id'")->fetchColumn() === 1;
    }

    /**
     * As permissões acima do teto: "Perfil: ação (Controller/metodo)".
     * @return array<int, string>
     */
    public static function acimaDoTeto(\PDO $pdo): array
    {
        if (!self::temAcoes($pdo)) {
            return [];
        }
        $linhas = $pdo->query(
            "SELECT v.nome AS perfil, a.nome AS acao, CONCAT(p.controller, '/', p.metodo) AS rota
               FROM adms_nivacs_pgs n
               INNER JOIN adms_niveis_acessos v ON v.id = n.adms_niveis_acesso_id
               INNER JOIN adms_paginas p ON p.id = n.adms_pagina_id
               INNER JOIN adms_acoes a ON a.id = p.acao_id
               LEFT JOIN adms_niveis_acessos t ON t.papel = a.teto
              WHERE n.permissao = 1
                AND v.papel IS NOT NULL AND v.papel <> '' AND v.papel <> 'administrador'
                AND (t.id IS NULL OR v.ordem > t.ordem)
              ORDER BY v.ordem, a.ordem, p.id"
        )->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($l) => "{$l['perfil']}: {$l['acao']} ({$l['rota']})", $linhas ?: []);
    }

    /**
     * As telas (não públicas) que não apontam para nenhuma ação.
     * @return array<int, string>
     */
    public static function semAcao(\PDO $pdo): array
    {
        if (!self::temAcoes($pdo)) {
            return [];
        }
        return $pdo->query(
            "SELECT CONCAT(p.controller, '/', p.metodo) FROM adms_paginas p
              WHERE p.lib_pub <> 1 AND (p.acao_id IS NULL OR NOT EXISTS (SELECT 1 FROM adms_acoes a WHERE a.id = p.acao_id))
              ORDER BY p.id"
        )->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }
}
