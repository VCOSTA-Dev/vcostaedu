<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * OS ROTEIROS DO "FAÇA COMIGO" — Onda 2, Etapa 4 (o AdmsTour do projeto de referência, em JSON).
 *
 * Um roteiro é UM arquivo de dados por procedimento: painel/app/adms/Roteiros/<slug>.json, com
 *   titulo, guia (o slug do guia em texto, ou vazio), inicio (a rota onde começa), rotas (TODAS as rotas que ele
 *   percorre — a pessoa precisa poder abrir cada uma), passos (o formato do motor: assets/js/vcostaedu-faca-comigo.js).
 * O mesmo arquivo serve ao motor no navegador, à prova do Playwright e, mais tarde, à gravação do vídeo.
 * As ações (`faz`) só vão para o navegador no modo "mostre", e esse modo só vale com a demonstração ligada.
 */
class AdmsFacaComigo
{
    public static function pasta(): string
    {
        return dirname(__DIR__, 2) . '/Roteiros/';
    }

    public static function carregar(string $slug): ?array
    {
        if (!preg_match('/^[a-z0-9-]{1,60}$/', $slug) || !is_file(self::pasta() . $slug . '.json')) {
            return null;
        }
        $r = json_decode((string) file_get_contents(self::pasta() . $slug . '.json'), true);
        return is_array($r) && !empty($r['passos']) ? $r + ['slug' => $slug] : null;
    }

    /** Os roteiros que existem, pelo slug do guia ligado: [guia => slug]. */
    public static function porGuia(): array
    {
        $m = [];
        foreach (glob(self::pasta() . '*.json') ?: [] as $f) {
            $r = json_decode((string) file_get_contents($f), true);
            if (!empty($r['guia'])) {
                $m[(string) $r['guia']] = basename($f, '.json');
            }
        }
        return $m;
    }

    /** A pessoa pode percorrer todas as rotas do roteiro? (a matriz e as travas da rota: só consulta, sensível) */
    public static function podeFazer(array $roteiro): bool
    {
        $nivel = AdmsNivel::nivel();
        foreach ((array) ($roteiro['rotas'] ?? []) as $rota) {
            [$mc, $mm] = array_pad(explode('/', strtolower(trim((string) $rota, '/'))), 2, 'index');
            $ler = new AdmsRead();
            $ler->fullRead("SELECT p.id FROM adms_paginas p LEFT JOIN adms_nivacs_pgs n ON n.adms_pagina_id = p.id AND n.adms_niveis_acesso_id = :n
                             WHERE p.menu_controller = :c AND p.menu_metodo = :m AND (p.lib_pub = 1 OR n.permissao = 1) LIMIT :limit",
                "n={$nivel}&c={$mc}&m={$mm}&limit=1");
            $p = $ler->getResultado()[0] ?? null;
            if (!$p || !\App\adms\Models\AdmsPaginas::nivelPode(\App\adms\Models\AdmsPaginas::marcasDaPagina((int) $p['id']))) {
                return false;
            }
        }
        return true;
    }

    /** O que vai para o navegador: sem as ações fora do modo "mostre" (só com a demonstração), com o {ano}. */
    public static function paraNavegador(array $r, string $modo): array
    {
        $mostre = $modo === 'mostre' && AdmsAmbiente::demonstracaoAtiva();
        $troca = static function ($v) use (&$troca) {
            if (is_array($v)) {
                return array_map($troca, $v);
            }
            return is_string($v) ? str_replace('{ano}', date('Y'), $v) : $v;
        };
        $passos = [];
        foreach ($r['passos'] as $p) {
            if (!$mostre) {
                unset($p['faz']);
            }
            $passos[] = $troca($p);
        }
        return [
            'guia' => $r['slug'],
            'titulo' => (string) $r['titulo'],
            'fim' => URLADM . (!empty($r['fim']) ? $r['fim'] : (!empty($r['guia']) ? 'ajuda/guia/' . $r['guia'] : 'primeiros-passos/index')),
            'ambiente' => AdmsAmbiente::demonstracaoAtiva() ? 'demonstracao' : 'real',
            'passos' => $passos,
        ];
    }
}
