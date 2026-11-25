<?php

namespace Core;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Carrega as views.
 *
 * VCOSTAEDU: o layout é o Tabler (app/adms/Views/include_tabler/) — head.php abre a página, desenha o rail e abre o
 * .page-wrapper; a view traz o _page_header e o .page-body; libraries_js.php fecha e carrega os scripts.
 * O parâmetro $layout das chamadas ficou da convivência com o AdminLTE (Etapas 3 a 7) e não escolhe mais nada:
 * o layout herdado (include/) saiu na Etapa 8.
 */
require_once dirname(__DIR__, 2) . '/core/Senha.php';
require_once dirname(__DIR__, 2) . '/core/Legal.php';

class ConfigView
{
    private const INC = 'app/adms/Views/include_tabler/';

    private $nome;
    private $dados;

    public function __construct($nome, array $dados = null)
    {
        $this->nome = $nome;
        $this->dados = $dados;
    }

    /** As telas de acesso (login, senhas): a página centrada, sem o rail. */
    public function renderizarLogin(string $layout = 'tabler')
    {
        require_once dirname(__DIR__, 2) . '/core/Mensagem.php';
        \VcMensagem::daSessao(); // Onda 3, Etapa 5: o × em toda mensagem
        if(file_exists("app/{$this->nome}.php")){
            include self::INC . "cabecalho_login.php";
            include "app/{$this->nome}.php";
            include self::INC . "rodapejs_login.php";
        }else{
            echo "Erro a carregar a View: {$this->nome}";
        }
    }

    public function renderizar(string $layout = 'tabler')
    {
        require_once dirname(__DIR__, 2) . '/core/Mensagem.php';
        \VcMensagem::daSessao(); // Onda 3, Etapa 5: o × em toda mensagem
        if(file_exists("app/{$this->nome}.php")){
            include self::INC . "head.php";
            include "app/{$this->nome}.php";
            include self::INC . "libraries_js.php";
        }else{
            echo "Erro a carregar a View: {$this->nome}";
        }
    }

    /** A tela com edição em linha (jquery-tabledit): o mesmo layout, com a flag que carrega o plugin. */
    public function renderizarEditInt(string $layout = 'tabler')
    {
        $this->dados['carregarEditInt'] = true;
        $this->renderizar();
    }

}
