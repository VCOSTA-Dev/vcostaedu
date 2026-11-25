<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsFacaComigo as R;

/**
 * O "FAÇA COMIGO" (Onda 2, Etapa 4; o Tour do projeto de referência).
 *   iniciar/<slug>  confere que a pessoa pode percorrer todas as rotas do roteiro e leva ao começo dele, com
 *                   ?tour=<slug>&modo=junto (o motor do navegador pega daí);
 *   passos/<slug>   os passos, em JSON, sem cache; 403 para quem não pode (e para o modo "mostre" sem a demonstração).
 */
class FacaComigo
{
    public function iniciar($slug = null): void
    {
        $r = R::carregar((string) $slug);
        if (!$r || !R::podeFazer($r)) {
            $_SESSION['msg'] = "<div class='alert alert-warning alert-dismissible' id='facaComigoRecusado'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Este Faça comigo não está disponível para o seu perfil.</div>";
            header('Location: ' . URLADM . 'home/index');
            return;
        }
        $modo = ($_GET['modo'] ?? '') === 'mostre' && \App\adms\Models\helper\AdmsAmbiente::demonstracaoAtiva() ? 'mostre' : 'junto';
        $inicio = str_replace('{ano}', date('Y'), (string) $r['inicio']); // a rota pode pedir o ano corrente (a enturmação lista por ano)
        header('Location: ' . URLADM . $inicio . (str_contains($inicio, '?') ? '&' : '?') . 'tour=' . rawurlencode($r['slug']) . '&modo=' . $modo);
    }

    public function passos($slug = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $r = R::carregar((string) $slug);
        $modo = (string) ($_GET['modo'] ?? 'junto');
        if (!$r || !R::podeFazer($r) || ($modo === 'mostre' && !\App\adms\Models\helper\AdmsAmbiente::demonstracaoAtiva())) {
            http_response_code(403);
            echo json_encode(['erro' => 'Este Faça comigo não está disponível para o seu perfil.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(R::paraNavegador($r, $modo), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
