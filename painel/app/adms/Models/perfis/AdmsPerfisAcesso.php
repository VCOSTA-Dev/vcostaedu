<?php

namespace App\adms\Models\perfis;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * A MATRIZ POR AÇÃO (docs/PENTE_FINO.md): os perfis (pela ordem), as ações (pelo grupo) e, em cada cruzamento, se o
 * perfil tem a ação — tem quando a matriz lhe dá ao menos uma das telas da ação e a ação não está acima do teto dele
 * (o mesmo que o motor deixa abrir).
 */
class AdmsPerfisAcesso
{
    /** @return array{perfis: array<int, array>, grupos: array<string, array<int, array>>} */
    public function matriz(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nome, papel, ordem FROM adms_niveis_acessos ORDER BY ordem, id");
        $perfis = $ler->getResultado() ?: [];
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nome, grupo, teto FROM adms_acoes ORDER BY ordem, id");
        $acoes = $ler->getResultado() ?: [];
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT DISTINCT p.acao_id, n.adms_niveis_acesso_id AS nivel FROM adms_nivacs_pgs n
            INNER JOIN adms_paginas p ON p.id = n.adms_pagina_id WHERE n.permissao = 1 AND p.acao_id IS NOT NULL");
        $tem = [];
        foreach ($ler->getResultado() ?: [] as $l) {
            $tem[(int) $l['acao_id']][(int) $l['nivel']] = true;
        }
        $nomeDoPapel = [];
        foreach ($perfis as $p) {
            if (($p['papel'] ?? '') !== '') {
                $nomeDoPapel[$p['papel']] = $p['nome'];
            }
        }
        $grupos = [];
        foreach ($acoes as $a) {
            $linha = ['id' => (int) $a['id'], 'nome' => $a['nome'], 'teto' => $nomeDoPapel[$a['teto']] ?? $a['teto'], 'perfis' => []];
            foreach ($perfis as $p) {
                $linha['perfis'][(int) $p['id']] = !empty($tem[(int) $a['id']][(int) $p['id']])
                    && !\App\adms\Models\helper\AdmsNivel::abaixoDoTeto($a['teto'], (int) $p['id']);
            }
            $grupos[$a['grupo']][] = $linha;
        }
        return ['perfis' => $perfis, 'grupos' => $grupos];
    }
}
