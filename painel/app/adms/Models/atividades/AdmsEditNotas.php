<?php

namespace App\adms\Models\atividades;

use App\adms\Models\notas\AdmsCalculoNotas as Calc;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Lança a nota de UMA atividade de UM aluno (tabela editável de atividades/visualizar).
 * E4: a vírgula é normalizada ANTES de comparar ("7,5" era comparado como texto com 10);
 * aceita 0 a 10 com até 2 casas, grava normalizada com ponto ("7.5", "6.95") e recusa o
 * resto com mensagem. Vazio apaga a nota. Saiu o limite de 1 ponto do tipo Acréscimo
 * (tipo desativado na E4). Oferta encerrada: recusa.
 */
class AdmsEditNotas
{
    private $dados;
    private $logCodigo;
    private $logDescricao;
    private $resultado;
    private $msg = '';

    function getResultado()
    {
        return $this->resultado;
    }

    /** Motivo da recusa, para a tela. */
    function getMsg(): string
    {
        return $this->msg;
    }

    public function editNotas(array $dados)
    {
        $this->dados = ['id' => (int) ($dados['id'] ?? 0), 'nota' => trim((string) ($dados['nota'] ?? ''))];
        $this->resultado = false;
        $oferta = Calc::ofertaDaNota($this->dados['id']);
        if ($oferta === 0) {
            $this->msg = 'Lançamento de nota não encontrado.';
            return;
        }
        if (Calc::travaEncerrada($oferta)) {
            $this->msg = 'Oferta encerrada: a nota não pode mais ser alterada.';
            return;
        }
        /* Onda 4, Etapa 3: na escola, a nota de uma etapa fechada não muda */
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead('SELECT atividade_id FROM rm_disciplina_atividade_notas WHERE id = :id LIMIT :limit', "id={$this->dados['id']}&limit=1");
        $etapaNota = \App\adms\Models\escola\AdmsEtapaNotas::etapaDaAtividade((int) ($ler->getResultado()[0]['atividade_id'] ?? 0));
        if ($etapaNota && ($trava = \App\adms\Models\escola\AdmsEtapaNotas::trava($oferta, (int) $etapaNota['id']))) {
            $this->msg = $trava;
            return;
        }
        if ($this->dados['nota'] === '') {
            $this->dados['nota'] = null;
        } else {
            $c = Calc::centesimos($this->dados['nota']);
            if ($c === null) {
                $this->msg = 'Nota inválida: use um número de 0 a 10 com até 2 casas decimais (vírgula ou ponto).';
                $this->logCodigo = 400;
                $this->logDescricao = "Nota recusada: id={$this->dados['id']}&nota=" . mb_substr($this->dados['nota'], 0, 20);
                $this->salvarLog();
                return;
            }
            $this->dados['nota'] = number_format($c / 100, $c % 10 ? 2 : 1, '.', '');
        }
        $this->lancaNota();
    }

    private function lancaNota()
    {
        $this->dados['usuario_alt'] = $_SESSION['colUsuario_id'];
        $this->dados['usuario_mod'] = $_SESSION['colUsuario_id'];
        $this->dados['data_alt'] = date("Y-m-d H:i:s");
        $this->dados['modified'] = date("Y-m-d H:i:s");
        /* Onda 2, Etapa 3: o valor anterior, a gravação e a auditoria na MESMA transação */
        $dados = $this->dados;
        $gravou = \Core\Transacao::executar(function () use ($dados) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT rm, nome_aluno, rm_prof_mat_id, atividade_id, nota FROM rm_disciplina_atividade_notas WHERE id = :id LIMIT :limit", "id={$dados['id']}&limit=1");
            $antes = $ler->getResultado()[0] ?? null;
            $altChamada = new \App\adms\Models\helper\AdmsUpdate();
            $altChamada->exeUpdate("rm_disciplina_atividade_notas", $dados, "WHERE id=:id", "id={$dados['id']}");
            // false = nenhuma linha mudou (mesma nota no mesmo segundo); o lançamento existe (checado acima).
            \Core\Transacao::exigir($altChamada->getResultado() === null ? null : true, 'nota');
            if ($antes) {
                \App\adms\Models\helper\AdmsAuditoria::nota((int) $antes['rm_prof_mat_id'], (int) $antes['atividade_id'], (string) $antes['rm'], (string) $antes['nome_aluno'],
                    $antes['nota'] !== null ? (string) $antes['nota'] : null, $dados['nota']);
            }
            return true;
        });
        if($gravou){
            $this->logCodigo = 200;
            $this->logDescricao = "Nota alterada com sucesso! id={$this->dados['id']}&nota={$this->dados['nota']}";
            $this->salvarLog();
            $this->resultado = true;
        }else{
            $this->msg = 'Nota não gravada (erro no banco).';
            $this->logCodigo = 400;
            $this->logDescricao = "Erro: Não foi possível editar a nota! id={$this->dados['id']}&nota={$this->dados['nota']}";
            $this->salvarLog();
            $this->resultado = false;
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(114, $this->logCodigo, 3, $this->logDescricao);
    }
}
