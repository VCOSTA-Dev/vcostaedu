<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * TENTATIVAS DE ACESSO (E7; rotas 407 listar e 408 liberar, só o administrador): vê as contagens e os
 * bloqueios do limite de tentativas (login, esqueci a senha, verificação) e libera um bloqueio — pela
 * linha ou digitando o identificador/IP (a tabela guarda só o hash; a comparação é pelo hash).
 */
class TentativasAcesso
{
    public function listar(): void
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, escopo, tipo, dica, falhas, primeira, ultima, bloqueado_ate, (bloqueado_ate IS NOT NULL AND bloqueado_ate > NOW()) AS ativo
                          FROM seg_tentativas ORDER BY ativo DESC, ultima DESC LIMIT :limit", "limit=200");
        $dados = [
            'linhas' => $ler->getResultado() ?: [],
            'regras' => \App\adms\Models\helper\AdmsLimiteTentativas::regras(),
            'paginaAtiva' => 407,
            'menus' => (new \App\adms\Models\AdmsMenus())->itemMenu(),
        ];
        (new \Core\ConfigView('adms/Views/tentativasAcesso/listar', $dados))->renderizar('tabler');
    }

    public function liberar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $id = (int) (filter_input(INPUT_POST, 'id') ?? 0);
            $chave = trim((string) (filter_input(INPUT_POST, 'chave') ?? ''));
            $apagar = new \App\adms\Models\helper\AdmsDeletar();
            if ($id > 0) {
                $apagar->exeDelete('seg_tentativas', 'WHERE id = :id', "id={$id}");
            } elseif ($chave !== '') {
                $apagar->exeDelete('seg_tentativas', 'WHERE chave = :c', 'c=' . hash('sha256', mb_strtolower($chave)));
            }
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='liberado'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Contagem apagada: a pessoa (ou o IP) pode tentar de novo.</div>";
        }
        header('Location: ' . URLADM . 'tentativas-acesso/listar');
    }
}
