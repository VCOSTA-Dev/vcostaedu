<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * AVISOS DO SISTEMA (Onda 3, Etapa 6), em Manutenção, só o Administrador: as versões (a dos arquivos e a do banco), as
 * etapas pendentes, a versão nova (core/VersaoNova.php), o resumo das verificações de segurança, o copyright e os links
 * do projeto (Apoie o projeto, Serviços — as constantes de core/Legal.php).
 */
class AvisosSistema
{
    private array $dados = [];

    public function index(): void
    {
        require_once dirname(__DIR__, 4) . '/core/Verificacoes.php';
        require_once dirname(__DIR__, 4) . '/core/VersaoNova.php';
        $pdo = (new \App\adms\Models\helper\AdmsConexaoAvulsa())->pdo();
        $versoes = \App\adms\Models\helper\AdmsAtualizacao::versoes();
        $this->dados['paginaAtiva'] = 494;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['versoes'] = $versoes;
        $this->dados['pendentes'] = \App\adms\Models\helper\AdmsAtualizacaoPendente::pendentes();
        $this->dados['versaoNova'] = \VcVersaoNova::situacao($pdo, (string) ($versoes['arquivos'] ?: $versoes['banco']));
        /* Onda 6, Etapa 7: o reenvio periódico do registro voluntário (no máximo uma tentativa por dia) */
        $this->dados['registro'] = \App\adms\Models\manutencao\AdmsRegistroInstalacao::periodico();
        $this->dados['seguranca'] = \VcVerificacoes::resumo(\VcVerificacoes::todas($pdo, dirname(__DIR__, 4), \Core\Requisicao::https()));
        /* Onda 3, Etapa 8: a saúde da conexão com o Moodle (só quando a integração está no banco) */
        $this->dados['moodle'] = \App\adms\Models\moodle\AdmsMoodle::disponivel() ? \App\adms\Models\moodle\AdmsMoodle::saude() : null;
        (new \Core\ConfigView('adms/Views/manutencao/avisos', $this->dados))->renderizar('tabler');
    }
}
