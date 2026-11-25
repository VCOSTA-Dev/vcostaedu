<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * 1.18.4 (D-503) — "ATUALIZAR DADOS" (situação 2 de adms_sits_usuarios) deixa de ser bloqueio: o usuário entra e, enquanto
 * a marca estiver ligada, TODA rota do painel leva à tela "Atualize seus dados" (perfil/atualizar-dados). Ficam livres
 * só essa tela (GET e o salvar, que é o POST na mesma rota) e o Sair; os arquivos estáticos não passam pelo roteador.
 *
 * `portao()` é a função única, chamada no ponto em que o RBAC é conferido (AdmsPaginas::listarPaginas): um GET ou POST
 * para outra rota é redirecionado, nunca executado. Ao salvar, a tela desliga a marca no banco e na sessão.
 */
class AdmsAtualizarDados
{
    /** adms_sits_usuarios.id da marca */
    public const SITUACAO = 2;

    /** Rotas livres enquanto a marca está ligada (controller/método, como em adms_paginas). */
    public const LIVRES = ['Perfil/atualizarDados', 'Login/logout'];

    public const TELA = 'perfil/atualizar-dados';

    /** O usuário da sessão está com a marca ligada? */
    public static function pendente(): bool
    {
        return !empty($_SESSION['colUsuario_id']) && (int) ($_SESSION['colUsuario_situacao'] ?? 0) === self::SITUACAO;
    }

    public static function livre(string $controller, string $metodo): bool
    {
        return in_array($controller . '/' . $metodo, self::LIVRES, true);
    }

    /** O PORTÃO: com a marca ligada, qualquer rota que não seja livre vai para a tela (e a requisição para aqui). */
    public static function portao(string $controller, string $metodo): void
    {
        if (!self::pendente() || self::livre($controller, $metodo)) {
            return;
        }
        $_SESSION['msg'] = "<div class='alert alert-warning' id='atualizeAntes'>Antes de continuar, confira e atualize seus dados.</div>";
        header('Location: ' . URLADM . self::TELA, true, 303);
        exit;
    }

    /** A tela de atualização abre para a marca mesmo num nível sem a permissão dela (ex.: nível criado depois pela tela). */
    public static function telaLiberada(string $controller, string $metodo): bool
    {
        return self::pendente() && $controller . '/' . $metodo === 'Perfil/atualizarDados';
    }
}
