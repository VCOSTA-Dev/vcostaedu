<?php

/**
 * O TEMA CLARO E ESCURO (Onda 3, Etapa 10). Cada pessoa escolhe "claro", "escuro" ou "auto" (o do aparelho) no menu do
 * usuário; começa claro. A escolha fica na conta (adms_usuarios.tema, col_usuarios.tema) e num cookie (vc_tema), para
 * as telas de acesso, o instalador e o Sobre seguirem a última escolha deste navegador.
 *
 * script() vai no <head>, ANTES das folhas de estilo: põe data-bs-theme no <html> antes do primeiro desenho (sem pisca).
 * O "auto" acompanha o aparelho ao vivo. Na impressão, a página fica clara (documento é sempre claro).
 * Substitui o tabler-theme.js (que lia o tema do endereço e do localStorage, sem saber da conta).
 */
final class VcTema
{
    public const VALORES = ['claro', 'escuro', 'auto'];
    public const NOMES = ['claro' => 'Claro', 'escuro' => 'Escuro', 'auto' => 'Automático'];

    public static function valido(?string $t): ?string
    {
        return in_array($t, self::VALORES, true) ? $t : null;
    }

    /** @param string|null $daConta o tema de quem entrou (null: sem conta — vale o cookie, ou claro) */
    public static function script(?string $daConta, bool $sempreClaro = false): string
    {
        $t = $sempreClaro ? 'claro' : self::valido($daConta);
        $js = '(function(){var d=document.documentElement,t=' . json_encode($t) . ';'
            . 'if(!t){var m=document.cookie.match(/(?:^|;\\s*)vc_tema=(claro|escuro|auto)/);t=m?m[1]:"claro";}'
            . ($t && !$sempreClaro ? 'document.cookie="vc_tema="+t+";path=/;max-age=31536000;SameSite=Lax";' : '')
            . 'var mq=window.matchMedia?matchMedia("(prefers-color-scheme: dark)"):null;'
            . 'function aplica(){var e=t==="escuro"||(t==="auto"&&mq&&mq.matches);d.setAttribute("data-bs-theme",e?"dark":"light");d.setAttribute("data-vc-tema",t);}'
            . 'aplica();if(mq&&mq.addEventListener){mq.addEventListener("change",function(){if(t==="auto"){aplica();}});}'
            . 'window.vcTema=function(n){t=n;document.cookie="vc_tema="+n+";path=/;max-age=31536000;SameSite=Lax";aplica();};'
            . 'window.addEventListener("beforeprint",function(){d.setAttribute("data-bs-theme","light");});'
            . 'window.addEventListener("afterprint",aplica);})();';
        return '<script>' . $js . '</script>';
    }

    /**
     * A escolha no menu do usuário: três botões (o atual marcado). $url = o endereço que grava (POST tema=…).
     * O clique muda na hora (window.vcTema) e grava em segundo plano (assets/js/vcostaedu-tema.js).
     */
    public static function seletor(string $url, string $atual): string
    {
        $atual = self::valido($atual) ?? 'claro';
        $icones = ['claro' => 'fa-sun', 'escuro' => 'fa-moon', 'auto' => 'fa-circle-half-stroke'];
        $h = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $b = '';
        foreach (self::VALORES as $v) {
            $b .= '<button type="button" class="btn btn-sm vc-tema-opcao' . ($v === $atual ? ' active' : '') . '" data-vc-tema="' . $v . '" aria-pressed="' . ($v === $atual ? 'true' : 'false')
                . '" title="Tema ' . $h(mb_strtolower(self::NOMES[$v])) . '"><i class="fas ' . $icones[$v] . '" aria-hidden="true"></i> <span>' . $h(self::NOMES[$v]) . '</span></button>';
        }
        return '<div class="vc-tema-seletor" id="vcTemaSeletor" data-url="' . $h($url) . '" role="group" aria-label="Tema da tela">'
            . '<span class="vc-tema-rotulo small">Tema</span><div class="btn-group btn-group-sm w-100">' . $b . '</div></div>';
    }
}
