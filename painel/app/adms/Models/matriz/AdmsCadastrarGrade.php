<?php

namespace App\adms\Models\matriz;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\matriz\AdmsMatrizRegras as Regras;

/**
 * Cadastra a GRADE de um curso (rm_cursos_grades), que nasce Aberta.
 *
 * ⚠️ O NÚMERO DIGITADO É RESPEITADO. No sistema de origem de ensino superior, `AdmsCadastrarGrade::ultimaGrade()`
 * sobrescrevia o número com MAX(grade)+1 do curso (LEVANTAMENTO §2.2). Aqui vale o
 * que foi digitado; repetido no mesmo curso é recusado (tela + índice `curso_versao`).
 */
class AdmsCadastrarGrade
{
    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    public function cadastrar(array $dados)
    {
        $cursoId = (int) ($dados['curso_id'] ?? 0);
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nome, situacao FROM rm_cursos WHERE id = :id LIMIT :limit", "id={$cursoId}&limit=1");
        $curso = $ler->getResultado()[0] ?? null;
        if (!$curso) {
            return $this->resultado = Regras::recusar('Escolha o curso da grade.');
        }
        if ((int) $curso['situacao'] !== 1) { // E8d (P-8)
            return $this->resultado = Regras::recusar("O curso {$curso['nome']} está inativo: reative-o em Cursos para criar uma grade nova.");
        }
        $numero = Regras::numeroGrade($dados['versao'] ?? '');
        if ($numero === null) {
            return $this->resultado = Regras::recusar('Informe o número da grade (inteiro de 1 a 9999).');
        }
        if (Regras::numeroGradeOcupado($cursoId, $numero, 0)) {
            return $this->resultado = Regras::recusar("A grade {$numero} já existe no curso {$curso['nome']}. O número da grade não se repete no mesmo curso.");
        }

        $add = new \App\adms\Models\helper\AdmsCreate();
        $add->exeCreate('rm_cursos_grades', [
            'curso_id' => $cursoId,
            'versao' => $numero,
            'descricao' => trim((string) ($dados['descricao'] ?? '')),
            'situacao' => Regras::ABERTA,
            'usuario_cre' => $_SESSION['colUsuario_id'],
        ]);
        $id = (int) $add->getResultado();
        if ($id < 1) {
            return $this->resultado = Regras::recusar('Não foi possível cadastrar a grade.');
        }
        Regras::historico($id, Regras::ABERTA, 'Grade criada.');
        Regras::log(369, 1, "Grade cadastrada: id={$id}; curso_id={$cursoId}; numero={$numero}");
        /* E8c (D-268): sem módulos, a grade nasce com o módulo interno (nunca exibido) */
        if (\App\adms\Models\helper\AdmsModuloInterno::ativo()) {
            \App\adms\Models\helper\AdmsModuloInterno::garantir($id);
        }
        $_SESSION['msg'] = Regras::aviso('success', "Grade {$numero} criada em {$curso['nome']}. " . (\App\adms\Models\helper\AdmsModuloInterno::ativo() ? 'Inclua as disciplinas.' : 'Inclua os módulos e as disciplinas.'));
        return $this->resultado = $id;
    }
}
