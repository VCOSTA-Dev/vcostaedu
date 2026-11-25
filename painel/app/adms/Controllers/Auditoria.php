<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * A AUDITORIA DE NOTAS E FALTAS (Onda 2, Etapa 3). Só leitura: não há aqui (nem em lugar nenhum) ação que altere ou
 * apague a auditoria.
 *   listar  o relatório, com os filtros e a exportação (Administrador, Direção, Supervisão; a Coordenação de curso só
 *           dos cursos dela — o escopo "relatorio" da página confere os filtros, e a consulta aplica o recorte);
 *   nota    o histórico de uma nota (a avaliação, pelo id na rota, e o aluno, pelo RA), aberto ao lado da nota;
 *           escopo "atividade".
 */
class Auditoria
{
    private $dados = [];

    private function comum(int $pagina): void
    {
        $this->dados['paginaAtiva'] = $pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
    }

    public function listar(): void
    {
        if (!\App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0006')) {
            \App\adms\Models\helper\AdmsAtualizacaoPendente::responderPendente();
            return;
        }
        $this->comum(465);
        $m = new \App\adms\Models\auditoria\AdmsAuditoriaConsulta();
        $this->dados['filtros'] = $m->filtrosDaUrl($_GET);
        $this->dados['opcoes'] = $m->opcoes();
        $this->dados['linhas'] = $m->listar($this->dados['filtros']);
        $this->dados['limite'] = $m::LIMITE;
        (new \Core\ConfigView('adms/Views/auditoria/listar', $this->dados))->renderizar('tabler');
    }

    public function nota($dadosId = null): void
    {
        if (!\App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0006')) {
            \App\adms\Models\helper\AdmsAtualizacaoPendente::responderPendente();
            return;
        }
        $this->comum(466);
        $atividade = (int) $dadosId;
        $rm = preg_replace('/\D/', '', (string) filter_input(INPUT_GET, 'rm', FILTER_DEFAULT));
        $m = new \App\adms\Models\auditoria\AdmsAuditoriaConsulta();
        $this->dados['nota'] = $m->historicoDaNota($atividade, $rm);
        (new \Core\ConfigView('adms/Views/auditoria/nota', $this->dados))->renderizar('tabler');
    }
}
