<?php
/*
 * O CURSO LIVRE (Fechamento; D-106): o que muda quando o tipo da instituição é gravado, e o que o portal pergunta.
 *
 * O curso livre é um tipo de verdade (core/Vocabulario.php): as páginas do painel ganham a marca do tipo em
 * adms_paginas_modulo (a lista com vírgula) e as do portal em col_paginas_modulo (migration 0087). Aqui:
 *   - aplicar(): quem grava o tipo (o instalador e a troca pelo painel) chama; no curso livre, a entrada do Moodle vira
 *     item do menu do aluno (nos outros tipos, como sempre: o cartão da Página Inicial);
 *   - tipo() e portalLigada(): o portal lê o tipo e a marca da página pela conexão dele.
 * Nada aqui lê sessão.
 */
require_once __DIR__ . '/Vocabulario.php';

final class VcTipoLivre
{
    /** A página do portal "Entrar no Moodle" (col_paginas 26) e o nível do aluno (1). */
    public const PORTAL_MOODLE = 26;

    public static function aplicar(\PDO $pdo, string $tipo): void
    {
        try {
            $pdo->prepare('UPDATE col_nivacs_pgs SET lib_menu = ? WHERE adms_pagina_id = ? AND adms_niveis_acesso_id = 1')
                ->execute([$tipo === VcVocabulario::LIVRE ? 1 : 2, self::PORTAL_MOODLE]);
        } catch (\Throwable $e) {
            // a tabela do portal ainda não existe (antes da estrutura)
        }
    }

    /** Os recursos do curso livre, na instalação (o instalador): chave => '1'/'0' em adms_instalacao_estado. */
    public static function gravarRecursos(\PDO $pdo, array $ligados): void
    {
        $st = $pdo->prepare('INSERT INTO adms_instalacao_estado (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
        foreach (VcVocabulario::RECURSOS_LIVRE as $r => $x) {
            $st->execute(['livre.' . $r, !empty($ligados[$r]) ? '1' : '0']);
        }
    }

    /** O tipo gravado (sem a chave, ensino superior). */
    public static function tipo(\PDO $pdo): string
    {
        try {
            $v = $pdo->query("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo' LIMIT 1")->fetchColumn();
        } catch (\Throwable $e) {
            $v = false;
        }
        return is_string($v) && VcVocabulario::valido($v) ? $v : VcVocabulario::SUPERIOR;
    }

    /** @return array<int, string> página do portal → módulo (sem a tabela, nenhuma: todas do núcleo) */
    public static function modulosDoPortal(\PDO $pdo): array
    {
        try {
            $r = [];
            foreach ($pdo->query('SELECT col_pagina_id, modulo FROM col_paginas_modulo')->fetchAll(\PDO::FETCH_NUM) as [$id, $m]) {
                $r[(int) $id] = (string) $m;
            }
            return $r;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
