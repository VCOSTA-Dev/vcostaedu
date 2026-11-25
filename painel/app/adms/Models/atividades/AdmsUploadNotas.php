<?php

namespace App\adms\Models\atividades;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsUploadNotas
{
    private $dados;
    private $linhas;
    private $dadosNotas;
    private $dadosId;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function visAtividade($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $verInfoDisc = new \App\adms\Models\helper\AdmsRead();
        $verInfoDisc->fullRead("SELECT a.id, a.nomeAtividade, a.descricao, date(a.data_inicial) as data_inicial, date(a.data_final) as data_final, a.tipo, a.peso, a.entrega_arquivo, a.rm_prof_mat_id, b.curso_id, (SELECT c.abreviatura FROM rm_cursos c WHERE c.id=b.curso_id) as nomeCurso, b.periodo, b.codtur, b.materia, b.ch_considerada, a.situacao FROM rm_disciplina_atividade a INNER JOIN rm_professores_materias b ON b.id=a.rm_prof_mat_id WHERE a.id=:id LIMIT :limit", "id={$this->dadosId}&limit=1");
        $this->resultado = $verInfoDisc->getResultado();
        return $this->resultado;
    }
    
    /**
     * 1.14.0 (E8, D-410): o CSV `rm;nota` pelo leitor comum (AdmsImportacaoCsv: BOM e Windows-1252, `;`, linha do
     * arquivo), conferido INTEIRO antes de gravar (RA inexistente, não enturmado, repetido, nota fora da escala ou em
     * formato inválido) e gravado numa TRANSAÇÃO: tudo ou nada. Mesma regra da nota digitada (0 a 10, até 2 casas,
     * vírgula ou ponto, gravada normalizada). Linha com a nota vazia é pulada, como antes.
     */
    public function upNota(array $dados)
    {
        $this->dados = $dados;
        $Csv = \App\adms\Models\importacao\AdmsImportacaoCsv::class;
        $lido = $Csv::ler($this->dados['arquivo'] ?? null, 'nota');
        $oferta = (int) $this->dados['rm_prof_mat_id'];
        [$validas, $erros] = $lido['erros'] ? [[], $lido['erros']] : $Csv::conferir($oferta, $lido['linhas'], 'nota', fn($v) => $Csv::erroNota($v));
        if (!$erros && !$validas) {
            $erros[] = 'Nenhuma nota no arquivo (as linhas com a nota vazia são puladas).';
        }
        if ($erros) {
            $_SESSION['msg'] = $Csv::caixaErros('Nenhuma nota foi gravada. Corrija as linhas abaixo e envie de novo:', $erros);
            $this->resultado = false;
            return;
        }
        $ativ = (int) $this->dados['atividade_id'];
        $usuario = (int) $_SESSION['colUsuario_id'];
        $T = \Core\Transacao::class;
        $ok = \App\adms\Models\helper\AdmsAuditoria::comOrigem(\App\adms\Models\helper\AdmsAuditoria::IMPORTACAO, fn() => $T::executar(function () use ($T, $validas, $ativ, $oferta, $usuario) {
            /* Onda 2, Etapa 3: o valor anterior de cada RA, para a auditoria */
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT rm, nome_aluno, nota FROM rm_disciplina_atividade_notas WHERE atividade_id = :a AND rm_prof_mat_id = :o", "a={$ativ}&o={$oferta}");
            $antes = [];
            foreach ($ler->getResultado() ?: [] as $l) {
                $antes[(string) $l['rm']] = $l;
            }
            foreach ($validas as $rm => $nota) {
                $c = \App\adms\Models\notas\AdmsCalculoNotas::centesimos($nota);
                $up = new \App\adms\Models\helper\AdmsUpdate();
                $up->exeUpdate('rm_disciplina_atividade_notas', ['nota' => number_format($c / 100, $c % 10 ? 2 : 1, '.', ''), 'usuario_alt' => $usuario, 'usuario_mod' => $usuario,
                    'data_alt' => date('Y-m-d H:i:s'), 'modified' => date('Y-m-d H:i:s')],
                    'WHERE atividade_id = :ativ AND rm_prof_mat_id = :o AND rm = :rm', 'ativ=' . $ativ . '&o=' . $oferta . '&rm=' . urlencode((string) $rm));
                $T::exigir($up->getResultado() === null ? null : true, 'nota do RA ' . $rm);
                $a = $antes[(string) $rm] ?? null;
                \App\adms\Models\helper\AdmsAuditoria::nota((int) $oferta, $ativ, (string) $rm, $a['nome_aluno'] ?? null, isset($a['nota']) && $a['nota'] !== null ? (string) $a['nota'] : null,
                    number_format($c / 100, $c % 10 ? 2 : 1, '.', ''));
            }
            return true;
        }));
        if (!$ok) {
            $_SESSION['msg'] = $Csv::caixaErros('Nenhuma nota foi gravada (a gravação foi desfeita):', [htmlspecialchars($T::ultimoErro(), ENT_QUOTES, 'UTF-8')]);
            $this->resultado = false;
            return;
        }
        $this->logCodigo = 200;
        $this->logDescricao = 'Notas importadas: atividade=' . $ativ . '; alunos=' . count($validas);
        $this->salvarLog();
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='importacaoGravada'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Notas importadas: " . count($validas) . " aluno(s). Tudo foi gravado.</div>";
        $this->resultado = true;
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(255, $this->logCodigo, 3, $this->logDescricao);
    }

}