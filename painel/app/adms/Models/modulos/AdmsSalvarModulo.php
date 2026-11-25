<?php

namespace App\adms\Models\modulos;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\matriz\AdmsMatrizRegras as Regras;

/**
 * Cadastra (`$id = 0`) ou edita um módulo de uma grade ABERTA.
 *
 * - A ordem é única dentro da grade.
 * - A grade de um módulo não muda na edição.
 * - ⚠️ Matrícula e oferta acham o módulo por (curso, ordem) — `periodo` = `ordem`.
 *   Por isso a ordem de um módulo em uso não muda.
 */
class AdmsSalvarModulo
{
    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    /** @return int|false o id do módulo, ou false com a recusa em $_SESSION['msg'] */
    public function salvar(array $dados, int $id = 0)
    {
        $atual = null;
        if ($id > 0) {
            $atual = Regras::modulo($id);
            if (!$atual) {
                return $this->resultado = Regras::recusar('Módulo não encontrado.');
            }
            $gradeId = (int) $atual['grade_id'];
        } else {
            $gradeId = (int) ($dados['grade_id'] ?? 0);
        }
        $grade = Regras::grade($gradeId);
        if ($grade === null) {
            return $this->resultado = Regras::recusar('Escolha a grade do módulo.');
        }
        if ($motivo = Regras::recusaEdicao($grade)) {
            return $this->resultado = Regras::recusar($motivo);
        }

        $nome = trim((string) ($dados['codnome'] ?? ''));
        $ordemTxt = trim((string) ($dados['ordem'] ?? ''));
        if ($nome === '' || !preg_match('/^\d{1,3}$/', $ordemTxt) || (int) $ordemTxt < 1) {
            return $this->resultado = Regras::recusar('Informe o nome do módulo e a ordem (número inteiro a partir de 1).');
        }
        $ordem = (int) $ordemTxt;

        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            'SELECT codnome FROM rm_cursos_turma_series WHERE grade_id = :g AND ordem = :o AND id <> :id LIMIT :limit',
            "g={$gradeId}&o={$ordem}&id={$id}&limit=1"
        );
        if ($outro = $ler->getResultado()) {
            return $this->resultado = Regras::recusar("A ordem {$ordem} já é do módulo «{$outro[0]['codnome']}» nesta grade.");
        }
        if ($atual && (int) $atual['ordem'] !== $ordem) {
            $usos = Regras::usosDoModulo($atual);
            if ($usos['matriculas'] > 0 || $usos['ofertas'] > 0) {
                return $this->resultado = Regras::recusar("A ordem do módulo «{$atual['codnome']}» não pode mudar: "
                    . "{$usos['matriculas']} matrícula(s) e {$usos['ofertas']} oferta(s) o acham pela ordem.");
            }
        }

        $linha = [
            'curso_id' => (int) $grade['curso_id'],
            'grade_id' => $gradeId,
            'codnome' => $nome,
            'descricao' => trim((string) ($dados['descricao'] ?? '')),
            'periodo' => $ordem,
            'ordem' => $ordem,
            'observacao' => trim((string) ($dados['observacao'] ?? '')),
            'situacao' => (int) ($dados['situacao'] ?? 1) === 2 ? 2 : 1,
        ];

        if ($atual) {
            $linha['usuario_mod'] = $_SESSION['colUsuario_id'];
            $alt = new \App\adms\Models\helper\AdmsUpdate();
            $alt->exeUpdate('rm_cursos_turma_series', $linha, 'WHERE id = :id', "id={$id}");
            if ($alt->getResultado() === null) {
                return $this->resultado = Regras::recusar('Não foi possível salvar o módulo.');
            }
            Regras::log(334, 3, "Módulo atualizado: id={$id}; grade_id={$gradeId}; ordem={$ordem}");
            $_SESSION['msg'] = Regras::aviso('success', "Módulo «{$nome}» atualizado.");
            return $this->resultado = $id;
        }

        $linha['usuario_cre'] = $_SESSION['colUsuario_id'];
        $add = new \App\adms\Models\helper\AdmsCreate();
        $add->exeCreate('rm_cursos_turma_series', $linha);
        $novo = (int) $add->getResultado();
        if ($novo < 1) {
            return $this->resultado = Regras::recusar('Não foi possível cadastrar o módulo.');
        }
        Regras::log(333, 1, "Módulo cadastrado: id={$novo}; grade_id={$gradeId}; ordem={$ordem}");
        $_SESSION['msg'] = Regras::aviso('success', "Módulo «{$nome}» incluído na grade {$grade['versao']}.");
        return $this->resultado = $novo;
    }
}
