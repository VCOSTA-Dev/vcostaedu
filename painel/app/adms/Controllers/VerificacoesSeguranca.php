<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * VERIFICAÇÕES DE SEGURANÇA (Onda 3, Etapa 6), em Manutenção, só o Administrador: cada item OK, ALERTA ou FALHA, com o
 * que fazer. A mesma implementação da linha de comando (core/Verificacoes.php; implantacao/verificar_seguranca.php).
 */
class VerificacoesSeguranca
{
    private array $dados = [];

    public function index(): void
    {
        require_once dirname(__DIR__, 4) . '/core/Verificacoes.php';
        $pdo = (new \App\adms\Models\helper\AdmsConexaoAvulsa())->pdo();
        $this->dados['paginaAtiva'] = 495;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['itens'] = \VcVerificacoes::todas($pdo, dirname(__DIR__, 4), \Core\Requisicao::https());
        $this->dados['resumo'] = \VcVerificacoes::resumo($this->dados['itens']);
        (new \Core\ConfigView('adms/Views/manutencao/verificacoes', $this->dados))->renderizar('tabler');
    }
}
