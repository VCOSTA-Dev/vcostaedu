<?php

/**
 * O × EM TODA MENSAGEM (Onda 3, Etapa 5): a mensagem que uma ação deixa para a tela seguinte ($_SESSION['msg']) — de
 * sucesso, de erro ou de aviso — sai sempre com o botão de fechar. As ações escrevem a mensagem de muitos jeitos
 * (herdados); em vez de mexer em cada uma, a tela confere ao desenhar (o ConfigView do painel e o do portal).
 */
final class VcMensagem
{
    private const BOTAO = "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>";

    /** Cada alerta da mensagem sem o botão de fechar ganha o botão (e a classe que o posiciona). */
    public static function comFechar(string $html): string
    {
        if ($html === '' || stripos($html, 'alert') === false) {
            return $html;
        }
        /* os blocos: cada abertura de <div class="alert …"> até a próxima */
        $partes = preg_split('/(?=<div\s+class=([\'"])alert\b)/i', $html);
        $saida = '';
        foreach ($partes as $p) {
            if (!preg_match('/^<div\s+class=([\'"])(alert\b[^\'"]*)\1([^>]*)>/i', $p, $m)) {
                $saida .= $p;
                continue;
            }
            if (stripos($p, 'btn-close') !== false) {
                $saida .= $p;
                continue;
            }
            $classes = $m[2];
            if (!preg_match('/\balert-dismissible\b/', $classes)) {
                $classes .= ' alert-dismissible';
            }
            $saida .= '<div class=' . $m[1] . $classes . $m[1] . $m[3] . '>' . self::BOTAO . substr($p, strlen($m[0]));
        }
        return $saida;
    }

    /** Aplica na mensagem da sessão, se houver. */
    public static function daSessao(): void
    {
        if (isset($_SESSION['msg']) && is_string($_SESSION['msg'])) {
            $_SESSION['msg'] = self::comFechar($_SESSION['msg']);
        }
    }
}
