<?php

namespace App\adms\Models\disciplinas;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * GERAR OFERTA DO ANO a partir da matriz (E3b; substitui a rota 240, cujo
 * `AdmsGerarNovaGrade` não existia nem na origem).
 *
 * Entrada: grade + módulo(s) + ano + turma (codtur) + polo + modalidade.
 * Saída: uma oferta em rm_professores_materias por linha da matriz dos módulos
 * escolhidos, com a CH da linha. IDEMPOTENTE: a oferta (ano, linha, turma) que
 * já existe é pulada (checagem + índice único `ano_linha_turma`).
 * O professor é vinculado depois, na tela da disciplina.
 * `bimestre` = 3 (Anual) e `tipo` = 3 (Somente Atividades), os mesmos da
 * demonstração; a regra de etapas é da E4.
 */
class AdmsGerarOferta
{
    private $resultado;
    private array $contagem = ['criadas' => 0, 'existentes' => 0];

    public function getResultado()
    {
        return $this->resultado;
    }

    public function getContagem(): array
    {
        return $this->contagem;
    }

    public function gerar(array $dados)
    {
        $grade = \App\adms\Models\matriz\AdmsMatrizRegras::grade((int) ($dados['grade_id'] ?? 0));
        if (!$grade) {
            return $this->recusar('Escolha a grade.');
        }
        if ((int) $grade['situacao'] === 4) {
            return $this->recusar("A grade {$grade['versao']} está Desativada: não gera oferta.");
        }
        if (!\App\adms\Models\helper\AdmsInativar::ativo('curso', (int) $grade['curso_id'])) { // E8d (P-8)
            return $this->recusar("O curso {$grade['curso']} está inativo: reative-o em Cursos para gerar oferta.");
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id FROM rm_cursos_turma_series WHERE grade_id = :g", "g={$grade['id']}");
        $daGrade = array_map('intval', array_column($ler->getResultado() ?: [], 'id'));
        /* E8c (D-268): sem módulos, a oferta é da GRADE: todas as disciplinas ou as escolhidas (linhas[]) */
        $semModulos = \App\adms\Models\helper\AdmsModuloInterno::ativo();
        $escolhidas = array_values(array_filter(array_map('intval', (array) ($dados['linhas'] ?? []))));
        if ($semModulos) {
            $modulos = $daGrade;
            if (!$escolhidas) {
                return $this->recusar('Escolha ao menos uma disciplina.');
            }
            if (!$modulos) {
                return $this->recusar('A grade não tem disciplinas na matriz.');
            }
        } else {
            $modulos = array_map('intval', (array) ($dados['modulos'] ?? []));
            if (!$modulos) {
                return $this->recusar('Escolha ao menos um módulo.');
            }
            if (array_diff($modulos, $daGrade)) {
                return $this->recusar('Há módulo que não é desta grade.');
            }
        }
        $ano = (int) ($dados['ano'] ?? 0);
        if (!\App\adms\Models\vinculo\AdmsVinculoRegras::anoLetivoExiste($ano)) {
            return $this->recusar('Escolha um ano letivo cadastrado.');
        }

        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT l.id FROM uni_rh_cursos_disciplinas_grade l
               INNER JOIN rm_cursos_turma_series m ON m.id = l.modulo_id
               INNER JOIN rm_materias mt ON mt.id = l.materia_id AND mt.situacao = 1
              WHERE l.grade_id = :g AND l.modulo_id IN (" . implode(',', $modulos) . ")"
                . ($semModulos ? " AND l.id IN (" . implode(',', $escolhidas) . ")" : '') . "
              ORDER BY m.ordem, l.id",
            "g={$grade['id']}"
        );
        $linhas = array_column($ler->getResultado() ?: [], 'id');
        if ($semModulos && count($linhas) !== count(array_unique($escolhidas))) {
            return $this->recusar('Há disciplina escolhida que não é desta grade ou cuja matéria está inativa.');
        }
        if (!$linhas) {
            return $this->recusar($semModulos ? 'Escolha ao menos uma disciplina da grade.' : 'Os módulos escolhidos não têm disciplinas na matriz.');
        }

        foreach ($linhas as $linhaId) {
            $oferta = AdmsOfertaMatriz::montar([
                'matriz_id' => $linhaId,
                'codtur' => $dados['codtur'] ?? '',
                'ano' => $ano,
                'codfilial' => $dados['codfilial'] ?? 0,
                'modalidade_id' => $dados['modalidade_id'] ?? '',
                'encontros' => $dados['encontros'] ?? '',
            ]);
            if (is_string($oferta)) {
                return $this->recusar($oferta . ($this->contagem['criadas'] ? " ({$this->contagem['criadas']} oferta(s) já tinham sido criadas.)" : ''));
            }
            if (AdmsOfertaMatriz::duplicada($ano, (int) $linhaId, $oferta['codtur'])) {
                $this->contagem['existentes']++;
                continue;
            }
            $add = new \App\adms\Models\helper\AdmsCreate();
            $add->exeCreate('rm_professores_materias', $oferta + [
                'bimestre' => 3,
                'tipo' => 3,
                'situacao' => 1,
                'usuario_cre' => $_SESSION['colUsuario_id'],
            ]);
            if ((int) $add->getResultado() > 0) {
                $this->contagem['criadas']++;
            } else {
                $this->contagem['existentes']++; // o índice único recusou: já existia
            }
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(240, 200, 1,
            "Oferta gerada: grade_id={$grade['id']}; " . ($semModulos ? 'linhas=' . implode(',', $linhas) : 'modulos=' . implode(',', $modulos)) . "; ano={$ano}; turma={$dados['codtur']}; criadas={$this->contagem['criadas']}; existentes={$this->contagem['existentes']}");
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
            . "Oferta {$ano} de {$grade['curso']} (grade {$grade['versao']}): <b>{$this->contagem['criadas']}</b> criada(s), <b>{$this->contagem['existentes']}</b> já existia(m). Vincule os professores na tela de cada disciplina.</div>";
        return $this->resultado = true;
    }

    private function recusar(string $texto)
    {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: "
            . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</div>';
        return $this->resultado = false;
    }
}
