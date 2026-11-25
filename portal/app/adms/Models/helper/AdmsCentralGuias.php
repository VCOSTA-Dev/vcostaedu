<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * CENTRAL DE GUIAS (E6c). Os guias curtos por tarefa são escritos em docs/guia/tarefas/*.md e
 * GERADOS (database/ferramentas/guias/gerar_guias.php) em Views/ajuda/guias/: um fragmento HTML
 * por guia, já escapado pelo gerador, e o _indice.php com os metadados (título, resumo, perfis,
 * ordem, "Comece por aqui", relacionados, telas, versão). Nada de Markdown em tempo de requisição.
 *
 * Quem vê o quê: cada guia declara os perfis (administrador, secretaria, professor, aluno). O
 * painel mapeia o nível da sessão (1, 4, 6); o portal é sempre o aluno. O mesmo arquivo existe
 * no painel e no portal.
 */
class AdmsCentralGuias
{
    public const PERFIS_PAINEL = [1 => 'administrador', 2 => 'administrador', 4 => 'secretaria', 6 => 'professor'];
    public const ROTULOS = ['administrador' => 'Administrador', 'secretaria' => 'Secretaria', 'professor' => 'Professor', 'aluno' => 'Aluno'];

    private static $indice = null;

    private static function pasta(): string
    {
        return dirname(__DIR__, 2) . '/Views/ajuda/guias/';
    }

    public static function indice(): array
    {
        if (self::$indice === null) {
            $f = self::pasta() . '_indice.php';
            self::$indice = is_file($f) ? (array) include $f : ['guias' => []];
        }
        return self::$indice;
    }

    /** O perfil de guia da sessão, ou null (nível sem guias). */
    public static function perfil(): ?string
    {
        if (defined('VCOSTAEDU_APP_PORTAL')) {
            return !empty($_SESSION['academico_id']) ? 'aluno' : null;
        }
        return self::PERFIS_PAINEL[(int) ($_SESSION['colUsuario_nivel'] ?? 0)] ?? null;
    }

    /** Guias visíveis ao perfil, na ordem do índice (ordem, título). */
    public static function visiveis(?string $perfil): array
    {
        if ($perfil === null) {
            return [];
        }
        /* Fechamento (D-106): o guia de um tipo de instituição só aparece na instalação desse tipo, como no painel */
        $g = array_filter(self::indice()['guias'] ?? [], fn($m) => in_array($perfil, $m['perfis'], true)
            && AdmsTipoPortal::guiaLigado((string) ($m['tipo'] ?? '')));
        uasort($g, fn($a, $b) => [$a['ordem'], $a['titulo']] <=> [$b['ordem'], $b['titulo']]);
        return $g;
    }

    public static function podeVer(string $slug, ?string $perfil): bool
    {
        return isset(self::visiveis($perfil)[$slug]);
    }

    /** O caminho "Comece por aqui" do perfil, na ordem de uso. */
    public static function comece(?string $perfil): array
    {
        $g = array_filter(self::visiveis($perfil), fn($m) => isset($m['comece'][$perfil]));
        uasort($g, fn($a, $b) => $a['comece'][$perfil] <=> $b['comece'][$perfil]);
        return $g;
    }

    /** O slug limpo (só a-z, 0-9 e hífen). */
    public static function slug(?string $s): string
    {
        return preg_replace('/[^a-z0-9-]/', '', strtolower((string) $s));
    }

    /** O guia ligado à tela atual (controller/método da URL), se o perfil puder vê-lo. */
    public static function guiaDaTela(): ?string
    {
        $url = strtolower(trim((string) ($_GET['url'] ?? ''), '/'));
        $p = explode('/', $url);
        $tela = ($p[0] ?? '') . '/' . ($p[1] ?? 'index');
        $perfil = self::perfil();
        /* D-217: a mesma tela pode levar a guias diferentes pelo ?tipo= (ex.: emissão PARCIAL × FINAL) */
        $tipo = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) ($_GET['tipo'] ?? $_POST['tipo'] ?? '')));
        foreach ($tipo !== '' ? ["{$tela}?tipo={$tipo}", $tela] : [$tela] as $chave) {
            foreach (self::visiveis($perfil) as $slug => $m) {
                if (in_array($chave, $m['telas'] ?? [], true)) {
                    return $slug;
                }
            }
        }
        return null;
    }

    /**
     * O HTML do guia, com as bases de imagem e de link trocadas. Link para um guia que o perfil não
     * vê (um guia comum a dois perfis citando o de um só) vira texto, com a indicação — nunca link quebrado.
     */
    public static function conteudo(string $slug, string $baseImagem, string $baseGuia): string
    {
        $f = self::pasta() . self::slug($slug) . '.php';
        if (!is_file($f)) {
            return '';
        }
        ob_start();
        include $f;
        $html = (string) ob_get_clean();
        $perfil = self::perfil();
        $html = preg_replace_callback('#<a class="guia-interno" data-guia="([a-z0-9-]+)" href="[^"]*">(.*?)</a>#s', fn($m) => self::podeVer($m[1], $perfil) ? $m[0] : '<span class="guia-outro-perfil" title="Guia de outro perfil">' . $m[2] . ' <small class="text-muted">(com outro perfil)</small></span>', $html);
        return str_replace(['{{IMG}}', '{{GUIA}}'], [$baseImagem, $baseGuia], $html);
    }

    /**
     * 1.18.0 (D-475): o VÍDEO do guia — do índice (o gerador o leva do manifesto docs/guia/videos.json) e só se o mp4 está
     * na pasta assets/videos/guias/ (fora do git; sem o arquivo, o guia não mostra nada, nem um espaço vazio).
     */
    public static function video(string $slug): ?array
    {
        $v = self::indice()['guias'][self::slug($slug)]['video'] ?? null;
        if (!$v || !is_file(self::pastaVideos() . basename((string) $v['arquivo']))) {
            return null;
        }
        return $v;
    }

    /** A pasta dos vídeos no disco e o endereço dela (o Apache serve com pedido de trecho, Range). */
    public static function pastaVideos(): string
    {
        return dirname(__DIR__, 5) . '/assets/videos/guias/';
    }

    public static function urlVideos(): string
    {
        return URL . 'assets/videos/guias/';
    }

    /** Os guias do perfil que têm vídeo (para a página Vídeos): o aluno vê só os do portal; a equipe, os do painel. */
    public static function comVideo(?string $perfil): array
    {
        $o = [];
        foreach (self::visiveis($perfil) as $slug => $m) {
            if ($v = self::video($slug)) {
                $o[$slug] = $m + ['video' => $v];
            }
        }
        return $o;
    }

    /**
     * Caminho da imagem pedida, só se ela aparece num guia que o perfil pode ver. O roteador troca
     * "_" e "." por "-" na URL: a comparação é feita nessa mesma forma.
     */
    public static function imagem(?string $pedido, ?string $perfil): ?string
    {
        $pedido = strtr(strtolower((string) $pedido), '_.', '--');
        foreach (self::visiveis($perfil) as $m) {
            foreach ($m['imagens'] ?? [] as $img) {
                if (strtr($img, '_.', '--') === $pedido) {
                    $f = dirname(__DIR__, 5) . '/docs/guia/img/' . basename($img);
                    return is_file($f) ? $f : null;
                }
            }
        }
        return null;
    }
}
