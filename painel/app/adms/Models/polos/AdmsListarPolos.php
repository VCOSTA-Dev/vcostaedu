<?php

namespace App\adms\Models\polos;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * POLOS (D-34, E3b). O polo é a unidade onde a oferta acontece: a oferta aponta
 * para ele por `rm_professores_materias.codfilial`, o curso tem o polo padrão em
 * `rm_cursos.localidade` e a sala em `rm_salas.codfilial` — colunas do sistema escolar de origem que
 * já existiam (fixas em 1) e onde "Sede" aparecia cravado (D-43).
 */
class AdmsListarPolos
{
    public function listar(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT p.*, (SELECT COUNT(*) FROM rm_professores_materias o WHERE o.codfilial = p.id) AS ofertas,
                    (SELECT COUNT(*) FROM rm_cursos c WHERE c.localidade = p.id) AS cursos
               FROM rm_polos p ORDER BY p.situacao, p.nome"
        );
        return $ler->getResultado() ?: [];
    }

    public function ver(int $id): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT * FROM rm_polos WHERE id = :id LIMIT :limit", "id={$id}&limit=1");
        return $ler->getResultado()[0] ?? null;
    }

    /** Polos ativos, para os seletores (curso, oferta, gerar oferta). */
    public static function ativos(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nome, cidade, uf FROM rm_polos WHERE situacao = :s ORDER BY nome", "s=1");
        return $ler->getResultado() ?: [];
    }

    public static function ufs(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT nome FROM adms_uf ORDER BY nome");
        return array_column($ler->getResultado() ?: [], 'nome');
    }
}
