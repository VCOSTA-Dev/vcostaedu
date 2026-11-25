<?php

namespace App\adms\Controllers;

use App\adms\Models\helper\AdmsCentralGuias as Guias;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * CENTRAL DE AJUDA do painel (E6c; rotas 384 índice, 403 guia, 404 imagem, 463 vídeos (1.18.0) — administrador,
 * secretaria e professor). O índice abre com "Comece por aqui" do perfil; cada guia é uma página
 * do sistema, dentro do layout. Guia de outro perfil, aberto pela URL, é recusado.
 */
class Ajuda
{
    private $dados = [];

    public function index(): void
    {
        $perfil = Guias::perfil();
        $this->dados['perfil'] = $perfil;
        $this->dados['comece'] = Guias::comece($perfil);
        $this->dados['guias'] = Guias::visiveis($perfil);
        $this->render('adms/Views/ajuda/indice');
    }

    public function guia($slug = null): void
    {
        $slug = Guias::slug($slug);
        $perfil = Guias::perfil();
        if (!Guias::podeVer($slug, $perfil)) {
            $_SESSION['msg'] = "<div class='alert alert-danger' id='guiaRecusado'>Este guia não é do seu perfil (ou não existe). Veja abaixo os guias que você pode abrir.</div>";
            header('Location: ' . URLADM . 'ajuda/index');
            return;
        }
        $todos = Guias::visiveis($perfil);
        $comece = array_keys(Guias::comece($perfil));
        $pos = array_search($slug, $comece, true);
        $this->dados += [
            'slug' => $slug, 'meta' => $todos[$slug], 'perfil' => $perfil,
            'html' => Guias::conteudo($slug, URLADM . 'ajuda/imagem/', URLADM . 'ajuda/guia/'),
            'relacionados' => array_intersect_key($todos, array_flip($todos[$slug]['relacionados'] ?? [])),
            'anterior' => $pos !== false && $pos > 0 ? [$comece[$pos - 1], $todos[$comece[$pos - 1]]['titulo']] : null,
            'proximo' => $pos !== false && isset($comece[$pos + 1]) ? [$comece[$pos + 1], $todos[$comece[$pos + 1]]['titulo']] : null,
            'passo' => $pos === false ? null : $pos + 1,
        ];
        $this->render('adms/Views/ajuda/guia');
    }

    /** 1.18.0 (D-475; rota 463): os vídeos dos guias do perfil, com busca. */
    public function videos(): void
    {
        $perfil = Guias::perfil();
        $this->dados['perfil'] = $perfil;
        $this->dados['videos'] = Guias::comVideo($perfil);
        $this->render('adms/Views/ajuda/videos');
    }

    /** Imagem de um guia do perfil (docs/ não responde pela web). */
    public function imagem($nome = null): void
    {
        $f = Guias::imagem((string) $nome, Guias::perfil());
        if ($f === null) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Imagem não encontrada.';
            return;
        }
        header('Content-Type: image/png');
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        readfile($f);
    }

    private function render(string $view): void
    {
        $this->dados['rotas'] = ['indice' => URLADM . 'ajuda/index', 'guia' => URLADM . 'ajuda/guia/', 'novidades' => URLADM . 'novidades/listar', 'videos' => URLADM . 'ajuda/videos'];
        $this->dados['paginaAtiva'] = 384;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        (new \Core\ConfigView($view, $this->dados))->renderizar('tabler');
    }
}
