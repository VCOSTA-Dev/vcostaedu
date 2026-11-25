<?php

namespace App\adms\Models\api;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\api\AdmsApiServidor as S;

/**
 * AS OPERAÇÕES DA API (Onda 2, Etapa 6). Cada escrita chama a função que a tela usa:
 *   aluno: AdmsCadastrarUsuarioAcademico / AdmsEditarUsuarioAcademico (com AdmsValidaAluno);
 *   matrícula: AdmsVincularAluno; situação: AdmsEditarVinculo; enturmação: AdmsConfirmarEnturmacao.
 * As consultas leem as mesmas tabelas que as telas mostram. Toda operação devolve [status, corpo, cabeçalhos].
 */
class AdmsApiRecursos
{
    private const SEXO_ENTRADA = ['f' => 1, 'feminino' => 1, 'm' => 2, 'masculino' => 2, 'o' => 3, 'outro' => 3, 'outros' => 3];
    private const SEXO_SAIDA = [1 => 'feminino', 2 => 'masculino', 3 => 'outros'];
    private const SITUACAO_ALUNO = [1 => 'ativo', 2 => 'inativo'];
    private const SITUACAO_MATRICULA = [1 => 'cursando', 2 => 'concluido', 3 => 'trancado', 4 => 'cancelado', 5 => 'transferido'];
    private const CURSANDO = 1;
    private const TRANCADO = 3;
    private const CANCELADO = 4;

    private static function ok(array $corpo, int $status = 200, array $cab = []): array
    {
        return [$status, $corpo, $cab];
    }

    private static function pagina(array $q): array
    {
        $pagina = max(1, (int) ($q['pagina'] ?? 1));
        $por = min(100, max(1, (int) ($q['por_pagina'] ?? 50)));
        return [$pagina, $por, ($pagina - 1) * $por];
    }

    /** O motivo que a função da tela deixou na sessão (o aviso dela), em texto. */
    private static function motivoDaTela(string $padrao): string
    {
        $m = trim(html_entity_decode(strip_tags(str_replace(['</li>', 'Fechar'], ['; ', ''], (string) ($_SESSION['msg'] ?? ''))), ENT_QUOTES, 'UTF-8'));
        unset($_SESSION['msg']);
        $m = preg_replace('/^(Erro:\s*)/u', '', preg_replace('/\s+/u', ' ', $m));
        return rtrim($m, '; ') ?: $padrao;
    }

    /** O campo de um motivo de recusa (para o "campo" do erro). */
    private static function campoDo(string $motivo): ?string
    {
        foreach (['RA' => 'ra', 'CPF' => 'cpf', 'mail' => 'email', 'sexo' => 'sexo', 'nome' => 'nome', 'nascimento' => 'nascimento', 'forma de ingresso' => 'forma_ingresso_id',
                  'data de ingresso' => 'data_ingresso', 'ano' => 'ano', 'Polo' => 'polo_id', 'polo' => 'polo_id', 'modalidade' => 'modalidade_id', 'grade' => 'grade_id', 'curso' => 'curso_id', 'previsão' => 'previsao_conclusao'] as $palavra => $campo) {
            if (mb_stripos($motivo, $palavra) !== false) {
                return $campo;
            }
        }
        return null;
    }

    // ------------------------------------------------------------------ alunos

    private const SQL_ALUNO = "SELECT u.id, u.rm, u.nome, u.nome_social, u.email, u.telefone, u.celular, u.genero_id, u.genero_confirmado_em, u.col_sits_usuario_id, u.created,
                                      i.cpf, i.nasc_data
                                 FROM col_usuarios u LEFT JOIN col_usuarios_informacoes i ON i.col_user_id = u.id
                                WHERE u.col_niveis_acesso_id = 1";

    private static function aluno(array $a): array
    {
        $sexo = ($a['genero_confirmado_em'] !== null || in_array((int) $a['genero_id'], [1, 2], true)) ? (self::SEXO_SAIDA[(int) $a['genero_id']] ?? null) : null;
        return [
            'ra' => (string) $a['rm'], 'nome' => $a['nome'], 'nome_social' => $a['nome_social'], 'cpf' => $a['cpf'] ?: null, 'email' => $a['email'],
            'nascimento' => $a['nasc_data'] ?: null, 'sexo' => $sexo, 'telefone' => $a['telefone'], 'celular' => $a['celular'],
            'situacao' => self::SITUACAO_ALUNO[(int) $a['col_sits_usuario_id']] ?? null, 'cadastrado_em' => $a['created'] ? date('c', strtotime((string) $a['created'])) : null,
        ];
    }

    private static function alunoPorRa(string $ra): ?array
    {
        return S::linha(self::SQL_ALUNO . ' AND u.rm = ? LIMIT 1', [(string) (int) $ra]);
    }

    public function alunosListar(array $p, array $q, array $d): array
    {
        [$pagina, $por, $desloc] = self::pagina($q);
        $where = '';
        $args = [];
        if (($q['ra'] ?? '') !== '') {
            $where .= ' AND u.rm = ?';
            $args[] = (string) (int) $q['ra'];
        }
        if (($q['cpf'] ?? '') !== '') {
            $where .= ' AND i.cpf = ?';
            $args[] = \App\adms\Models\helper\AdmsCpf::digitos((string) $q['cpf']);
        }
        if (($q['nome'] ?? '') !== '') {
            $where .= ' AND u.nome LIKE ?';
            $args[] = '%' . $q['nome'] . '%';
        }
        $total = (int) S::um('SELECT COUNT(*) FROM col_usuarios u LEFT JOIN col_usuarios_informacoes i ON i.col_user_id = u.id WHERE u.col_niveis_acesso_id = 1' . $where, $args);
        $linhas = S::todas(self::SQL_ALUNO . $where . " ORDER BY u.id LIMIT {$por} OFFSET {$desloc}", $args);
        return self::ok(['dados' => array_map([self::class, 'aluno'], $linhas), 'paginacao' => ['pagina' => $pagina, 'por_pagina' => $por, 'total' => $total]]);
    }

    public function alunosVer(array $p, array $q, array $d): array
    {
        $a = self::alunoPorRa($p['ra']);
        /* Onda 3, Etapa 9: o aluno no Moodle (para o sistema de fora mostrar o andamento até o "acesso pronto") */
        return $a ? self::ok(['dados' => self::aluno($a) + ['moodle' => \App\adms\Models\moodle\AdmsMoodle::estadoAluno((int) $a['id'])]])
            : S::erro(404, 'nao_encontrado', "Aluno com RA {$p['ra']} não encontrado.", 'ra');
    }

    /** O corpo do aluno para o formulário da tela; null com o erro preenchido. */
    private static function formularioAluno(array $d, ?array &$erro, bool $exigeSexo): ?array
    {
        $f = [];
        foreach (['nome', 'nome_social', 'email', 'telefone', 'celular'] as $c) {
            if (array_key_exists($c, $d)) {
                if (!is_scalar($d[$c]) && $d[$c] !== null) {
                    $erro = S::erro(422, 'validacao', "O campo {$c} precisa ser texto.", $c);
                    return null;
                }
                $f[$c] = trim((string) $d[$c]);
            }
        }
        if (array_key_exists('cpf', $d)) {
            $f['cpf'] = trim((string) $d['cpf']);
        }
        if (array_key_exists('nascimento', $d)) {
            $n = trim((string) $d['nascimento']);
            if ($n !== '' && (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $n, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1]) || $n > date('Y-m-d') || (int) $m[1] < 1900)) {
                $erro = S::erro(422, 'validacao', 'A data de nascimento precisa existir e vir como AAAA-MM-DD.', 'nascimento');
                return null;
            }
            $f['nasc_data'] = $n;
        }
        if (array_key_exists('sexo', $d) && $d['sexo'] !== null && $d['sexo'] !== '') {
            $s = self::SEXO_ENTRADA[mb_strtolower(trim((string) $d['sexo']))] ?? null;
            if ($s === null) {
                $erro = S::erro(422, 'validacao', 'O sexo precisa ser feminino, masculino ou outros.', 'sexo');
                return null;
            }
            $f['genero_id'] = $s;
        } elseif ($exigeSexo) {
            $erro = S::erro(422, 'validacao', 'Informe o sexo do aluno (feminino, masculino ou outros): os documentos concordam com ele.', 'sexo');
            return null;
        }
        if (array_key_exists('situacao', $d)) {
            $s = array_search(mb_strtolower(trim((string) $d['situacao'])), self::SITUACAO_ALUNO, true);
            if ($s === false) {
                $erro = S::erro(422, 'validacao', 'A situação do aluno precisa ser ativo ou inativo.', 'situacao');
                return null;
            }
            $f['col_sits_usuario_id'] = $s;
        }
        return $f;
    }

    public function alunosCriar(array $p, array $q, array $d): array
    {
        $ra = trim((string) ($d['ra'] ?? ''));
        /* Onda 8 (D-67): na escola, a matrícula é o login do aluno; o CPF válido é o login do responsável */
        if ($ra !== '' && \App\adms\Models\helper\AdmsParametro::naEscola()) {
            require_once dirname(__DIR__, 5) . '/core/LoginPortal.php';
            if (\VcLoginPortal::comoCpf($ra) !== null) {
                return S::erro(422, 'validacao', 'O número de matrícula não pode ser um CPF: na escola, o CPF é o login do responsável no portal. Use o número de matrícula da escola.', 'ra');
            }
        }
        if ($ra === '' || !preg_match('/^\d{1,9}$/', $ra)) {
            return S::erro(422, 'validacao', 'Informe o RA: só dígitos, até 9.', 'ra');
        }
        /* RA que já existe: devolve o existente, sem duplicar e sem mudar nada */
        if ($existente = self::alunoPorRa($ra)) {
            return self::ok(['dados' => self::aluno($existente), 'criado' => false]);
        }
        $erro = null;
        $f = self::formularioAluno($d, $erro, true);
        if ($f === null) {
            return $erro;
        }
        $f['rm'] = $ra;
        $cad = new \App\adms\Models\academicos\AdmsCadastrarUsuarioAcademico();
        $cad->cadUserAcademico($f, ['sem_sessao' => true]);
        if (!$cad->getResultado()) {
            $motivo = $cad->getErros()[0] ?? 'Não foi possível cadastrar o aluno.';
            return S::erro(422, 'validacao', $motivo, self::campoDo($motivo));
        }
        $a = self::alunoPorRa($ra);
        return self::ok(['dados' => self::aluno($a), 'criado' => true], 201, ['Location' => URL . 'api/v1/alunos/' . $a['rm']]);
    }

    public function alunosAtualizar(array $p, array $q, array $d): array
    {
        $a = self::alunoPorRa($p['ra']);
        if (!$a) {
            return S::erro(404, 'nao_encontrado', "Aluno com RA {$p['ra']} não encontrado.", 'ra');
        }
        if (array_key_exists('ra', $d) && (string) $d['ra'] !== (string) $a['rm']) {
            return S::erro(422, 'validacao', 'O RA não muda pela API.', 'ra');
        }
        $erro = null;
        $f = self::formularioAluno($d, $erro, false);
        if ($f === null) {
            return $erro;
        }
        $ed = new \App\adms\Models\academicos\AdmsEditarUsuarioAcademico();
        $atual = $ed->verUsuario((int) $a['id'])[0] ?? [];
        /* sem o sexo confirmado, editar pela tela o confirmaria — a API pede o sexo junto */
        if (!isset($f['genero_id']) && $atual['genero_confirmado_em'] === null && !in_array((int) $atual['genero_id'], [1, 2], true)) {
            return S::erro(422, 'validacao', 'Este aluno está sem o sexo informado: envie o sexo junto (feminino, masculino ou outros).', 'sexo');
        }
        $form = array_merge($atual, $f, ['id' => (int) $a['id']]);
        $ed->editUsuarios($form);
        if (!$ed->getResultado()) {
            $motivo = $ed->getErros()[0] ?? self::motivoDaTela('Não foi possível atualizar o aluno.');
            return S::erro(422, 'validacao', $motivo, self::campoDo($motivo));
        }
        return self::ok(['dados' => self::aluno(self::alunoPorRa($p['ra']))]);
    }

    // ------------------------------------------------------------------ cadastros de consulta

    public function cursosListar(array $p, array $q, array $d): array
    {
        $r = S::todas('SELECT id, nome, abreviatura, nivel, nome_extenso, modalidade_id, localidade, situacao, exige_tcc FROM rm_cursos ORDER BY nome');
        return self::ok(['dados' => array_map(fn($c) => [
            'id' => (int) $c['id'], 'nome' => $c['nome'], 'sigla' => $c['abreviatura'], 'nivel' => $c['nivel'], 'nome_extenso' => $c['nome_extenso'],
            'modalidade_id' => $c['modalidade_id'] !== null ? (int) $c['modalidade_id'] : null, 'polo_id' => (int) $c['localidade'] ?: null,
            'ativo' => \App\adms\Models\helper\AdmsInativar::ativo('curso', (int) $c['id']), 'exige_tcc' => (bool) $c['exige_tcc'],
        ], $r)]);
    }

    public function gradesListar(array $p, array $q, array $d): array
    {
        if (!S::um('SELECT COUNT(*) FROM rm_cursos WHERE id = ?', [(int) $p['id']])) {
            return S::erro(404, 'nao_encontrado', 'Curso não encontrado.', 'id');
        }
        $r = S::todas('SELECT g.id, g.versao, g.situacao, s.nome AS situacao_nome FROM rm_cursos_grades g LEFT JOIN uni_rh_cursos_grades_situacao s ON s.id = g.situacao WHERE g.curso_id = ? ORDER BY g.versao', [(int) $p['id']]);
        return self::ok(['dados' => array_map(fn($g) => ['id' => (int) $g['id'], 'versao' => (int) $g['versao'], 'situacao' => $g['situacao_nome'], 'aceita_vinculo' => (int) $g['situacao'] !== 4], $r)]);
    }

    public function anosListar(array $p, array $q, array $d): array
    {
        return self::ok(['dados' => array_map(fn($a) => ['ano' => (int) $a['ano']], S::todas('SELECT ano FROM adms_periodos_letivos ORDER BY ano'))]);
    }

    public function polosListar(array $p, array $q, array $d): array
    {
        return self::ok(['dados' => array_map(fn($x) => ['id' => (int) $x['id'], 'nome' => $x['nome'], 'cidade' => $x['cidade'], 'uf' => $x['uf'], 'ativo' => (int) $x['situacao'] === 1],
            S::todas('SELECT id, nome, cidade, uf, situacao FROM rm_polos ORDER BY nome'))]);
    }

    public function modalidadesListar(array $p, array $q, array $d): array
    {
        return self::ok(['dados' => array_map(fn($x) => ['id' => (int) $x['id'], 'nome' => $x['nome']], S::todas('SELECT id, nome FROM uni_rh_cursos_modalidade WHERE status = 1 ORDER BY id'))]);
    }

    public function formasListar(array $p, array $q, array $d): array
    {
        return self::ok(['dados' => array_map(fn($x) => ['id' => (int) $x['id'], 'nome' => $x['nome']], S::todas('SELECT id, nome FROM rm_matricula_tipos WHERE situacao = 1 ORDER BY id'))]);
    }

    public function disciplinasListar(array $p, array $q, array $d): array
    {
        return self::ok(['dados' => array_map(fn($x) => ['id' => (int) $x['id'], 'nome' => $x['nome'], 'sigla' => $x['abreviacao'], 'carga_horaria' => $x['carga_horaria'], 'ativa' => (int) $x['situacao'] === 1],
            S::todas('SELECT id, nome, abreviacao, carga_horaria, situacao FROM rm_materias ORDER BY nome'))]);
    }

    public function ofertasListar(array $p, array $q, array $d): array
    {
        [$pagina, $por, $desloc] = self::pagina($q);
        $where = ' WHERE 1 = 1';
        $args = [];
        foreach (['ano' => 'o.ano', 'curso_id' => 'o.curso_id'] as $k => $col) {
            if (($q[$k] ?? '') !== '') {
                $where .= " AND {$col} = ?";
                $args[] = (int) $q[$k];
            }
        }
        $total = (int) S::um('SELECT COUNT(*) FROM rm_professores_materias o' . $where, $args);
        $r = S::todas("SELECT o.id, o.curso_id, o.grade, o.codmat, o.materia, o.codtur, o.ano, o.ch_considerada, o.encerrada_em, o.data_inicio, o.data_fim FROM rm_professores_materias o{$where} ORDER BY o.ano DESC, o.id LIMIT {$por} OFFSET {$desloc}", $args);
        return self::ok(['dados' => array_map(fn($o) => [
            'id' => (int) $o['id'], 'curso_id' => (int) $o['curso_id'], 'grade_id' => (int) $o['grade'], 'disciplina_id' => (int) $o['codmat'], 'disciplina' => $o['materia'],
            'turma' => $o['codtur'], 'ano' => (int) $o['ano'], 'carga_horaria' => $o['ch_considerada'], 'encerrada' => $o['encerrada_em'] !== null,
            'data_inicio' => $o['data_inicio'], 'data_fim' => $o['data_fim'],
        ], $r), 'paginacao' => ['pagina' => $pagina, 'por_pagina' => $por, 'total' => $total]]);
    }

    // ------------------------------------------------------------------ matrículas

    private const SQL_MATRICULA = 'SELECT mc.*, u.rm, u.nome AS aluno, c.nome AS curso, g.versao
                                     FROM rm_matriculas_curso mc JOIN col_usuarios u ON u.id = mc.usuario_id
                                     JOIN rm_cursos c ON c.id = mc.curso_id JOIN rm_cursos_grades g ON g.id = mc.grade_id';

    private static function matricula(array $m): array
    {
        $anos = S::todas('SELECT id, ano FROM rm_matriculas_ensino WHERE matricula_curso_id = ? ORDER BY ano', [(int) $m['id']]);
        return [
            'id' => (int) $m['id'], 'ra' => (string) $m['rm'], 'aluno' => $m['aluno'], 'curso_id' => (int) $m['curso_id'], 'curso' => $m['curso'],
            'grade_id' => (int) $m['grade_id'], 'grade_versao' => (int) $m['versao'], 'situacao' => self::SITUACAO_MATRICULA[(int) $m['situacao_id']] ?? null,
            'forma_ingresso_id' => (int) $m['forma_ingresso_id'], 'data_ingresso' => $m['data_ingresso'], 'polo_id' => $m['polo_id'] !== null ? (int) $m['polo_id'] : null,
            'modalidade_id' => $m['modalidade_id'] !== null ? (int) $m['modalidade_id'] : null, 'previsao_conclusao' => $m['previsao_conclusao'] ?? null,
            'data_conclusao' => $m['data_conclusao'] ?? null, 'anos' => array_map(fn($a) => ['posicao_id' => (int) $a['id'], 'ano' => (int) $a['ano']], $anos),
        ];
    }

    private static function matriculaPorId(int $id): ?array
    {
        return S::linha(self::SQL_MATRICULA . ' WHERE mc.id = ?', [$id]);
    }

    public function matriculasListar(array $p, array $q, array $d): array
    {
        [$pagina, $por, $desloc] = self::pagina($q);
        $where = ' WHERE 1 = 1';
        $args = [];
        if (($q['ra'] ?? '') !== '') {
            $where .= ' AND u.rm = ?';
            $args[] = (string) (int) $q['ra'];
        }
        if (($q['curso_id'] ?? '') !== '') {
            $where .= ' AND mc.curso_id = ?';
            $args[] = (int) $q['curso_id'];
        }
        if (($q['situacao'] ?? '') !== '') {
            $s = array_search((string) $q['situacao'], self::SITUACAO_MATRICULA, true);
            if ($s === false) {
                return S::erro(422, 'validacao', 'Situação desconhecida: use ' . implode(', ', self::SITUACAO_MATRICULA) . '.', 'situacao');
            }
            $where .= ' AND mc.situacao_id = ?';
            $args[] = $s;
        }
        $total = (int) S::um('SELECT COUNT(*) FROM rm_matriculas_curso mc JOIN col_usuarios u ON u.id = mc.usuario_id' . $where, $args);
        $r = S::todas(self::SQL_MATRICULA . $where . " ORDER BY mc.id LIMIT {$por} OFFSET {$desloc}", $args);
        return self::ok(['dados' => array_map([self::class, 'matricula'], $r), 'paginacao' => ['pagina' => $pagina, 'por_pagina' => $por, 'total' => $total]]);
    }

    public function matriculasVer(array $p, array $q, array $d): array
    {
        $m = self::matriculaPorId((int) $p['id']);
        return $m ? self::ok(['dados' => self::matricula($m)]) : S::erro(404, 'nao_encontrado', 'Matrícula não encontrada.', 'id');
    }

    public function matriculasCriar(array $p, array $q, array $d): array
    {
        $a = self::alunoPorRa((string) ($d['ra'] ?? '0'));
        if (!$a) {
            return S::erro(422, 'validacao', 'Aluno não encontrado: informe o RA de um aluno cadastrado.', 'ra');
        }
        $curso = (int) ($d['curso_id'] ?? 0);
        if (!S::um('SELECT COUNT(*) FROM rm_cursos WHERE id = ?', [$curso])) {
            return S::erro(422, 'validacao', 'Curso não encontrado.', 'curso_id');
        }
        $grades = S::todas('SELECT id, versao, situacao FROM rm_cursos_grades WHERE curso_id = ?', [$curso]);
        $grade = null;
        if (isset($d['grade_id'])) {
            foreach ($grades as $g) {
                if ((int) $g['id'] === (int) $d['grade_id']) {
                    $grade = $g;
                }
            }
            if (!$grade) {
                return S::erro(422, 'validacao', 'A grade não é deste curso.', 'grade_id');
            }
        } else {
            $abertas = array_values(array_filter($grades, fn($g) => (int) $g['situacao'] !== 4));
            if (count($abertas) !== 1) {
                return S::erro(422, 'validacao', 'O curso tem ' . count($abertas) . ' grade(s) que aceitam vínculo: informe a grade_id.', 'grade_id');
            }
            $grade = $abertas[0];
        }
        $modulo = \App\adms\Models\helper\AdmsModuloInterno::garantir((int) $grade['id']);
        $dados = [
            'ano' => (int) ($d['ano'] ?? 0), 'modulo_id' => $modulo, 'forma_ingresso_id' => (int) ($d['forma_ingresso_id'] ?? 0),
            'data_ingresso' => (string) ($d['data_ingresso'] ?? ''), 'polo_id' => isset($d['polo_id']) ? (string) (int) $d['polo_id'] : '',
            'modalidade_id' => isset($d['modalidade_id']) ? (string) (int) $d['modalidade_id'] : '', 'previsao_conclusao' => (string) ($d['previsao_conclusao'] ?? ''),
            'observacao' => (string) ($d['observacao'] ?? ''),
        ];
        $mc = (new \App\adms\Models\vinculo\AdmsVincularAluno())->vincular((int) $a['id'], $dados);
        if (!$mc) {
            $motivo = self::motivoDaTela('Não foi possível gravar a matrícula.');
            return S::erro(str_contains($motivo, 'já tem matrícula') ? 409 : 422, str_contains($motivo, 'já tem matrícula') ? 'conflito' : 'validacao', $motivo, self::campoDo($motivo));
        }
        unset($_SESSION['msg']);
        return self::ok(['dados' => self::matricula(self::matriculaPorId((int) $mc))], 201, ['Location' => URL . 'api/v1/matriculas/' . (int) $mc]);
    }

    /** Muda a situação pela edição da tela, com os demais campos como estão. */
    private function situacao(int $id, int $nova, array $de, string $verbo): array
    {
        $m = self::matriculaPorId($id);
        if (!$m) {
            return S::erro(404, 'nao_encontrado', 'Matrícula não encontrada.', 'id');
        }
        if (!in_array((int) $m['situacao_id'], $de, true)) {
            return S::erro(409, 'conflito', "A matrícula está " . (self::SITUACAO_MATRICULA[(int) $m['situacao_id']] ?? '?') . ": não dá para {$verbo}.", 'situacao');
        }
        $ed = new \App\adms\Models\vinculo\AdmsEditarVinculo();
        $ed->editar($id, [
            'forma_ingresso_id' => (int) $m['forma_ingresso_id'], 'situacao_id' => $nova, 'data_ingresso' => (string) $m['data_ingresso'],
            'polo_id' => $m['polo_id'] !== null ? (string) $m['polo_id'] : '', 'modalidade_id' => $m['modalidade_id'] !== null ? (string) $m['modalidade_id'] : '',
            'previsao_conclusao' => (string) ($m['previsao_conclusao'] ?? ''), 'observacao' => (string) ($m['observacao'] ?? ''), 'data_conclusao' => '',
        ]);
        if (!$ed->getResultado()) {
            $motivo = self::motivoDaTela('Não foi possível mudar a situação.');
            return S::erro(422, 'validacao', $motivo, self::campoDo($motivo));
        }
        unset($_SESSION['msg']);
        return self::ok(['dados' => self::matricula(self::matriculaPorId($id))]);
    }

    public function matriculasTrancar(array $p, array $q, array $d): array
    {
        return $this->situacao((int) $p['id'], self::TRANCADO, [self::CURSANDO], 'trancar');
    }

    public function matriculasReativar(array $p, array $q, array $d): array
    {
        return $this->situacao((int) $p['id'], self::CURSANDO, [self::TRANCADO], 'reativar (só a trancada volta a cursando)');
    }

    public function matriculasCancelar(array $p, array $q, array $d): array
    {
        return $this->situacao((int) $p['id'], self::CANCELADO, [self::CURSANDO, self::TRANCADO], 'cancelar');
    }

    // ------------------------------------------------------------------ enturmação

    /** A posição da matrícula no ano pedido (ou no mais recente). */
    private static function posicao(int $mc, $ano): ?array
    {
        $sql = 'SELECT me.*, u.rm FROM rm_matriculas_ensino me JOIN col_usuarios u ON u.id = me.usuario_id WHERE me.matricula_curso_id = ?';
        $args = [$mc];
        if ($ano !== null && $ano !== '') {
            $sql .= ' AND me.ano = ?';
            $args[] = (int) $ano;
        }
        return S::linha($sql . ' ORDER BY me.ano DESC LIMIT 1', $args);
    }

    private static function enturmacao(array $pos): array
    {
        $ofertas = \App\adms\Models\portalAlunos\AdmsEnturmacaoSugerida::ofertas($pos, (string) $pos['rm']);
        return ['posicao_id' => (int) $pos['id'], 'ano' => (int) $pos['ano'], 'ofertas' => array_map(fn($o) => [
            'oferta_id' => (int) $o['id'], 'disciplina' => $o['materia'], 'turma' => $o['codtur'], 'enturmado' => (bool) $o['enturmado'],
            'sugerida' => (bool) $o['sugerida'], 'encerrada' => (bool) $o['encerrada'],
        ], $ofertas)];
    }

    public function enturmacaoVer(array $p, array $q, array $d): array
    {
        if (!self::matriculaPorId((int) $p['id'])) {
            return S::erro(404, 'nao_encontrado', 'Matrícula não encontrada.', 'id');
        }
        $pos = self::posicao((int) $p['id'], $q['ano'] ?? null);
        return $pos ? self::ok(['dados' => self::enturmacao($pos)]) : S::erro(404, 'nao_encontrado', 'A matrícula não tem posição neste ano.', 'ano');
    }

    public function enturmacaoGravar(array $p, array $q, array $d): array
    {
        if (!self::matriculaPorId((int) $p['id'])) {
            return S::erro(404, 'nao_encontrado', 'Matrícula não encontrada.', 'id');
        }
        $pos = self::posicao((int) $p['id'], $d['ano'] ?? null);
        if (!$pos) {
            return S::erro(404, 'nao_encontrado', 'A matrícula não tem posição neste ano.', 'ano');
        }
        if (!isset($d['ofertas']) || !is_array($d['ofertas']) || !array_is_list($d['ofertas'])) {
            return S::erro(422, 'validacao', 'Envie "ofertas": a lista dos ids das ofertas em que o aluno fica (a lista inteira, como na tela).', 'ofertas');
        }
        $ok = (new \App\adms\Models\portalAlunos\AdmsConfirmarEnturmacao())->confirmar((int) $pos['id'], array_map('intval', $d['ofertas']));
        if (!$ok) {
            $motivo = self::motivoDaTela('Não foi possível gravar a enturmação.');
            return S::erro(422, 'validacao', $motivo, 'ofertas');
        }
        $aviso = trim(strip_tags((string) ($_SESSION['msg'] ?? '')));
        unset($_SESSION['msg']);
        $pos = self::posicao((int) $p['id'], $pos['ano']);
        return self::ok(['dados' => self::enturmacao($pos), 'aviso' => str_contains($aviso, 'ficou') || str_contains($aviso, 'mantid') ? self::limpar($aviso) : null]);
    }

    // ------------------------------------------------------------------ turmas (Onda 4, Etapa 2: só na escola)

    private static function turma(array $t): array
    {
        $ofertas = \App\adms\Models\escola\AdmsTurmas::ofertas((int) $t['id']);
        return ['id' => (int) $t['id'], 'ano' => (int) $t['ano'], 'curso_id' => (int) $t['curso_id'], 'grade_id' => (int) $t['grade_id'],
            'serie_id' => (int) $t['modulo_id'], 'serie' => $t['serie'], 'nome' => $t['nome'],
            'turma' => \App\adms\Models\escola\AdmsTurmas::codtur((string) $t['serie'], (string) $t['nome']),
            'turno' => $t['turno_nome'] ?? null, 'sala' => $t['sala'], 'capacidade' => $t['capacidade'] === null ? null : (int) $t['capacidade'],
            'ofertas' => array_map(fn($o) => ['oferta_id' => (int) $o['id'], 'disciplina' => $o['materia']], $ofertas)];
    }

    public function turmasListar(array $p, array $q, array $d): array
    {
        $ano = (int) ($q['ano'] ?? 0);
        $lista = \App\adms\Models\escola\AdmsTurmas::listar($ano);
        if (($q['curso_id'] ?? '') !== '') {
            $lista = array_values(array_filter($lista, fn($t) => (int) $t['curso_id'] === (int) $q['curso_id']));
        }
        return self::ok(['dados' => array_map(fn($t) => self::turma($t) + ['alunos' => (int) $t['alunos']], $lista)]);
    }

    private static function alunosDaTurma(int $id): array
    {
        return array_map(fn($a) => ['ra' => (string) $a['rm'], 'nome' => $a['nome'], 'entrada' => $a['entrada']], \App\adms\Models\escola\AdmsTurmaAlunos::alunos($id));
    }

    public function turmaAlunosVer(array $p, array $q, array $d): array
    {
        $t = \App\adms\Models\escola\AdmsTurmas::ver((int) $p['id']);
        if (!$t) {
            return S::erro(404, 'nao_encontrado', 'Turma não encontrada.', 'id');
        }
        return self::ok(['dados' => self::turma($t) + ['alunos' => self::alunosDaTurma((int) $t['id'])]]);
    }

    /** Põe o aluno (pela RA) na turma: ele entra em todas as ofertas dela, pela mesma função da tela. */
    public function turmaAlunosEntrar(array $p, array $q, array $d): array
    {
        $t = \App\adms\Models\escola\AdmsTurmas::ver((int) $p['id']);
        if (!$t) {
            return S::erro(404, 'nao_encontrado', 'Turma não encontrada.', 'id');
        }
        $ra = trim((string) ($d['ra'] ?? ''));
        if ($ra === '') {
            return S::erro(422, 'validacao', 'Envie "ra": a RA do aluno que entra na turma.', 'ra');
        }
        $u = S::todas('SELECT id FROM col_usuarios WHERE rm = ? AND col_niveis_acesso_id = 1 LIMIT 1', [$ra]);
        if (!$u) {
            return S::erro(404, 'nao_encontrado', 'Aluno não encontrado.', 'ra');
        }
        $r = \App\adms\Models\escola\AdmsTurmaAlunos::entrar((int) $t['id'], (int) $u[0]['id']);
        if ($r !== true) {
            return S::erro(422, 'validacao', $r, 'ra');
        }
        return self::ok(['dados' => self::turma($t) + ['alunos' => self::alunosDaTurma((int) $t['id'])]]);
    }

    // ------------------------------------------------------------------ a inscrição numa chamada só (Onda 8, D-70)

    /**
     * POST /inscricoes: o cadastro, a matrícula e a turma (na escola) ou a enturmação nas ofertas (no ensino superior),
     * numa transação só — tudo ou nada —, pelas MESMAS funções das operações separadas (que não mudam). A mesma
     * matrícula de novo não duplica nada: a resposta diz o que já existia. Depois de gravar, o envio ao Moodle vai na
     * hora, dentro do orçamento de sempre, e a resposta traz o estado lá; o que não couber termina sozinho nas
     * requisições seguintes (AdmsMoodle::retomar).
     */
    public function inscricoesCriar(array $p, array $q, array $d): array
    {
        $al = $d['aluno'] ?? null;
        $dest = $d['destino'] ?? null;
        if (!is_array($al) || (array_is_list($al) && $al !== [])) {
            return S::erro(422, 'validacao', 'Envie "aluno": os dados da pessoa (ra e nome; sexo, email, celular, cpf e nascimento, se tiver).', 'aluno');
        }
        if (!is_array($dest) || (array_is_list($dest) && $dest !== [])) {
            return S::erro(422, 'validacao', 'Envie "destino": na escola, {"turma_id"}; no ensino superior, {"curso_id", "ano", "ofertas"}.', 'destino');
        }
        /* D-106: a escola e o curso livre inscrevem na turma; o CPF como matrícula é recusado só na escola (lá é o login do responsável) */
        $escola = \App\adms\Models\helper\AdmsParametro::porTurma();
        $ra = trim((string) ($al['ra'] ?? ''));
        if ($ra !== '' && \App\adms\Models\helper\AdmsParametro::naEscola()) {
            require_once dirname(__DIR__, 5) . '/core/LoginPortal.php';
            if (\VcLoginPortal::comoCpf($ra) !== null) {
                return S::erro(422, 'validacao', 'O número de matrícula não pode ser um CPF: na escola, o CPF é o login do responsável no portal. Use o número de matrícula da escola.', 'aluno.ra');
            }
        }
        if ($ra === '' || !preg_match('/^\d{1,9}$/', $ra)) {
            return S::erro(422, 'validacao', 'Informe o RA: só dígitos, até 9.', 'aluno.ra');
        }

        /* o destino, conferido antes de gravar qualquer coisa */
        $turma = null;
        $ofertas = [];
        if ($escola) {
            $turma = \App\adms\Models\escola\AdmsTurmas::ver((int) ($dest['turma_id'] ?? 0));
            if (!$turma) {
                return S::erro(404, 'nao_encontrado', 'Turma não encontrada: informe em "destino" o turma_id de uma turma da escola (GET /turmas).', 'destino.turma_id');
            }
            [$curso, $grade, $ano, $modulo] = [(int) $turma['curso_id'], (int) $turma['grade_id'], (int) $turma['ano'], (int) $turma['modulo_id']];
        } else {
            $curso = (int) ($dest['curso_id'] ?? 0);
            if (!S::um('SELECT COUNT(*) FROM rm_cursos WHERE id = ?', [$curso])) {
                return S::erro(422, 'validacao', 'Curso não encontrado.', 'destino.curso_id');
            }
            $grades = S::todas('SELECT id, situacao FROM rm_cursos_grades WHERE curso_id = ?', [$curso]);
            if (isset($dest['grade_id'])) {
                $g = array_values(array_filter($grades, fn($x) => (int) $x['id'] === (int) $dest['grade_id']));
                if (!$g) {
                    return S::erro(422, 'validacao', 'A grade não é deste curso.', 'destino.grade_id');
                }
            } else {
                $g = array_values(array_filter($grades, fn($x) => (int) $x['situacao'] !== 4));
                if (count($g) !== 1) {
                    return S::erro(422, 'validacao', 'O curso tem ' . count($g) . ' grade(s) que aceitam vínculo: informe a grade_id.', 'destino.grade_id');
                }
            }
            $grade = (int) $g[0]['id'];
            $ano = (int) ($dest['ano'] ?? 0);
            if (!isset($dest['ofertas']) || !is_array($dest['ofertas']) || !array_is_list($dest['ofertas']) || !$dest['ofertas']) {
                return S::erro(422, 'validacao', 'Envie em "destino" as "ofertas": os ids das ofertas em que a pessoa fica (GET /ofertas).', 'destino.ofertas');
            }
            $ofertas = array_values(array_unique(array_map('intval', $dest['ofertas'])));
            $modulo = \App\adms\Models\helper\AdmsModuloInterno::garantir($grade);
        }
        $forma = isset($dest['forma_ingresso_id']) ? (int) $dest['forma_ingresso_id'] : (int) S::um('SELECT id FROM rm_matricula_tipos WHERE situacao = 1 ORDER BY id LIMIT 1');
        $vinculo = ['ano' => $ano, 'modulo_id' => $modulo, 'forma_ingresso_id' => $forma, 'data_ingresso' => (string) ($dest['data_ingresso'] ?? date('Y-m-d')),
            'polo_id' => isset($dest['polo_id']) ? (string) (int) $dest['polo_id'] : '', 'modalidade_id' => isset($dest['modalidade_id']) ? (string) (int) $dest['modalidade_id'] : '',
            'previsao_conclusao' => (string) ($dest['previsao_conclusao'] ?? ''), 'observacao' => ''];

        /* a pessoa: a que já existe não muda; a nova passa pela mesma validação da tela (sem sexo, ela entra na lista
           "Alunos sem sexo informado", como na importação) */
        /* Onda 8, correção, segunda leva, 2.6 (D-92): a INSCRIÇÃO QUE NÃO DUPLICA — o mesmo e-mail na mesma turma é a mesma
           inscrição, ainda que venha com outro número de matrícula (o site gera um número por envio do formulário: a
           pessoa que se inscreve duas vezes mandaria dois). Uma depois da outra (a trava pelo e-mail e pela turma, antes da
           do RA); a segunda encontra a pessoa na turma e devolve a matrícula que existe, com sucesso, sem criar nada. */
        $emailInscricao = mb_strtolower(trim((string) ($al['email'] ?? '')));
        if ($escola && $emailInscricao !== '') {
            S::travar('inscricao', $emailInscricao . '|' . (int) $turma['id']);
            $naTurma = S::linha('SELECT u.rm FROM col_usuarios u INNER JOIN esc_turma_alunos a ON a.usuario_id = u.id AND a.turma_id = ? AND a.saida IS NULL
                WHERE LOWER(u.email) = ? ORDER BY u.id LIMIT 1', [(int) $turma['id'], $emailInscricao]);
            if ($naTurma && (string) $naTurma['rm'] !== '') {
                $ra = (string) $naTurma['rm'];
            }
        }
        /* a mesma matrícula ao mesmo tempo (dois sistemas, ou duas chaves de repetição): uma depois da outra — a segunda
           encontra a pessoa e diz o que já existia */
        S::travar('ra', $ra);
        $existente = self::alunoPorRa($ra);
        $form = null;
        if (!$existente) {
            $erro = null;
            $form = self::formularioAluno($al, $erro, false);
            if ($form === null) {
                $erro[1]['erro']['campo'] = 'aluno.' . ($erro[1]['erro']['campo'] ?? '');
                return $erro;
            }
            $form['rm'] = $ra;
        }

        $T = \Core\Transacao::class;
        $feito = [];
        $falha = null;
        $ids = ['aluno' => 0, 'mc' => 0];
        $ok = $T::executar(function () use ($T, $escola, $turma, $ofertas, $curso, $ano, $vinculo, $existente, $form, $ra, &$feito, &$falha, &$ids) {
            /* 1. o cadastro */
            if ($existente) {
                $ids['aluno'] = (int) $existente['id'];
                $feito['aluno'] = 'ja_existia';
            } else {
                $cad = new \App\adms\Models\academicos\AdmsCadastrarUsuarioAcademico();
                $cad->cadUserAcademico($form, ['sem_sessao' => true, 'sexo_pendente' => !isset($form['genero_id'])]);
                if (!$cad->getResultado()) {
                    $m = $cad->getErros()[0] ?? 'Não foi possível cadastrar a pessoa.';
                    $falha = [422, 'validacao', $m, 'aluno.' . (self::campoDo($m) ?? '')];
                    $T::falhar($m);
                }
                $ids['aluno'] = (int) $cad->getIdUsuario();
                $feito['aluno'] = 'criado';
            }
            /* 2. a matrícula no curso (a que já existe, cursando, vale) */
            $atual = S::linha('SELECT id, situacao_id FROM rm_matriculas_curso WHERE usuario_id = ? AND curso_id = ? AND situacao_id IN (1, 3) ORDER BY id DESC LIMIT 1', [$ids['aluno'], $curso]);
            if ($atual) {
                if ((int) $atual['situacao_id'] !== self::CURSANDO) {
                    $falha = [409, 'conflito', "A matrícula do RA {$ra} neste curso está trancada: reative-a antes (POST /matriculas/{$atual['id']}/reativar).", 'aluno.ra'];
                    $T::falhar('trancada');
                }
                $ids['mc'] = (int) $atual['id'];
                $feito['matricula'] = 'ja_existia';
            } else {
                $mc = (new \App\adms\Models\vinculo\AdmsVincularAluno())->vincular($ids['aluno'], $vinculo);
                if (!$mc) {
                    $m = self::motivoDaTela('Não foi possível gravar a matrícula.');
                    $falha = [422, 'validacao', $m, 'destino.' . (self::campoDo($m) ?? '')];
                    $T::falhar($m);
                }
                $ids['mc'] = (int) $mc;
                $feito['matricula'] = 'criada';
            }
            unset($_SESSION['msg']);
            /* 3. a turma (escola) ou a enturmação nas ofertas (ensino superior) */
            if ($escola) {
                $ja = \App\adms\Models\escola\AdmsTurmaAlunos::turmaDoAluno($ids['aluno'], $ano);
                if ($ja && (int) $ja['id'] === (int) $turma['id']) {
                    $feito['turma'] = 'ja_estava';
                } elseif ($ja) {
                    $falha = [409, 'conflito', "O RA {$ra} já está noutra turma neste ano ({$ja['nome']}): para mudar, a secretaria usa \"Trocar de turma\".", 'destino.turma_id'];
                    $T::falhar('outra turma');
                } else {
                    $r = \App\adms\Models\escola\AdmsTurmaAlunos::entrar((int) $turma['id'], $ids['aluno']);
                    if ($r !== true) {
                        $falha = [422, 'validacao', (string) $r, 'destino.turma_id'];
                        $T::falhar((string) $r);
                    }
                    $feito['turma'] = 'entrou';
                }
            } else {
                $pos = self::posicao($ids['mc'], $ano);
                if (!$pos) {
                    $falha = [422, 'validacao', 'A matrícula não tem posição neste ano.', 'destino.ano'];
                    $T::falhar('posição');
                }
                $ja = array_map(fn($o) => (int) $o['oferta_id'], array_filter(self::enturmacao($pos)['ofertas'], fn($o) => $o['enturmado']));
                if (!array_diff($ofertas, $ja)) {
                    $feito['enturmacao'] = 'ja_estava';
                } else {
                    $conf = new \App\adms\Models\portalAlunos\AdmsConfirmarEnturmacao();
                    if (!$conf->confirmar((int) $pos['id'], array_values(array_unique(array_merge($ja, $ofertas))))) {
                        $m = self::motivoDaTela('Não foi possível gravar a enturmação.');
                        $falha = [422, 'validacao', $m, 'destino.ofertas'];
                        $T::falhar($m);
                    }
                    unset($_SESSION['msg']);
                    $feito['enturmacao'] = 'gravada';
                }
            }
            return true;
        });
        unset($_SESSION['msg']);
        if (!$ok) {
            [$st, $cod, $msg, $campo] = $falha ?? [422, 'validacao', $T::ultimoErro() ?: 'Não foi possível concluir a inscrição.', null];
            return S::erro($st, $cod, 'Nada foi gravado: ' . $msg, rtrim((string) $campo, '.') ?: null);
        }

        /* o Moodle: na hora, dentro do orçamento de sempre (o resto fica na fila e termina sozinho depois) */
        \App\adms\Models\moodle\AdmsMoodle::aoTerminar();
        $a = self::alunoPorRa($ra);
        $corpo = ['dados' => ['aluno' => self::aluno($a), 'matricula' => self::matricula(self::matriculaPorId($ids['mc']))], 'feito' => $feito];
        if ($escola) {
            $comps = count(\App\adms\Models\escola\AdmsTurmas::ofertas((int) $turma['id']));
            $corpo['dados']['turma'] = self::turma($turma) + ['curso' => $turma['segmento'], 'componentes' => $comps];
        } else {
            $comps = count($ofertas);
            $corpo['dados']['enturmacao'] = self::enturmacao(self::posicao($ids['mc'], $ano));
        }
        $corpo['moodle'] = \App\adms\Models\moodle\AdmsMoodle::estadoAluno((int) $a['id']) + ['disciplinas_total' => $comps];
        $novo = in_array('criado', $feito, true) || in_array('criada', $feito, true) || in_array('entrou', $feito, true) || in_array('gravada', $feito, true);
        return self::ok($corpo, $novo ? 201 : 200, $novo ? ['Location' => URL . 'api/v1/alunos/' . $a['rm']] : []);
    }

    /**
     * POST /alunos/{ra}/primeiro-acesso: o PRIMEIRO ACESSO do aluno — o mesmo link que a secretaria manda pela ficha
     * (AdmsTokenAcesso: de uso único, com prazo, só o sha256 no banco; emitir um novo invalida os abertos). Um link por
     * chamada: com "enviar_email" (o padrão), vai no e-mail de boas-vindas, para o e-mail do cadastro; com
     * "devolver_link", o mesmo link volta na resposta (para o site mostrar o botão "Criar minha senha agora" — o que
     * deixa de confirmar que o e-mail é da pessoa). Só para quem ainda não criou a senha e está ativo: a conta em uso não
     * ganha link por aqui. A resposta não é guardada (nem com a chave de repetição: o link em claro nunca vai ao banco);
     * o registro das chamadas guarda só a rota e a situação; o log do sistema, como na tela.
     */
    public function primeiroAcesso(array $p, array $q, array $d): array
    {
        $a = self::alunoPorRa($p['ra']);
        if (!$a) {
            return S::erro(404, 'nao_encontrado', "Aluno com RA {$p['ra']} não encontrado.", 'ra');
        }
        $u = S::linha('SELECT nome, email, senha, col_sits_usuario_id FROM col_usuarios WHERE id = ?', [(int) $a['id']]);
        if ((int) ($u['col_sits_usuario_id'] ?? 0) !== 1) {
            return S::erro(409, 'conflito', 'O aluno está inativo: o primeiro acesso não é liberado.', 'ra');
        }
        if (!in_array(trim((string) ($u['senha'] ?? '')), ['', '-inexistente'], true)) {
            return S::erro(409, 'conflito', 'O aluno já criou a senha: ele entra pelo portal, com o número de matrícula e a senha (ou pelo "Esqueci a senha").', 'ra');
        }
        $enviar = !array_key_exists('enviar_email', $d) || !empty($d['enviar_email']);
        $devolver = !empty($d['devolver_link']);
        if (!$enviar && !$devolver) {
            return S::erro(422, 'validacao', 'Peça o e-mail de boas-vindas ("enviar_email": true), o link ("devolver_link": true) ou os dois.', 'enviar_email');
        }
        if ($enviar && !filter_var((string) $u['email'], FILTER_VALIDATE_EMAIL)) {
            return S::erro(422, 'validacao', 'O aluno não tem e-mail válido no cadastro: o e-mail de boas-vindas não tem para onde ir.', 'email');
        }
        $tokens = new \App\adms\Models\helper\AdmsTokenAcesso();
        $token = $tokens->emitir((int) $a['id'], 'primeiro_acesso', (int) ($_SESSION['colUsuario_id'] ?? 0) ?: null);
        $link = \App\adms\Models\helper\AdmsTokenAcesso::link($token);
        $horas = \App\adms\Models\helper\AdmsTokenAcesso::horasValidade();
        $motivo = null;
        $enviado = $enviar && $tokens->enviarEmail((string) $u['email'], (string) $u['nome'], $link, 'primeiro_acesso', $motivo);
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(366, $enviado || !$enviar ? 200 : 202, 2, "Link de acesso emitido pela interface para o aluno id={$a['id']} (primeiro_acesso); e-mail "
            . ($enviar ? ($enviado ? 'enviado' : 'NÃO enviado: ' . $motivo) : 'não pedido') . ($devolver ? '; o link devolvido ao sistema de fora' : '') . '.');
        $dados = ['email_enviado' => $enviado, 'valido_ate' => date('c', time() + $horas * 3600), 'uso_unico' => true];
        if ($enviar && !$enviado) {
            $dados['motivo'] = 'O e-mail não foi enviado: ' . (string) $motivo;
        }
        if ($devolver) {
            $dados['link'] = $link;
        }
        return self::ok(['dados' => $dados], 200, ['Cache-Control' => 'no-store']);
    }

    // ------------------------------------------------------------------ os responsáveis (Onda 7, Etapa 2: só na escola)

    private static function pessoaApi(?array $d): ?array
    {
        return $d ? ['nome' => $d['nome'], 'email' => $d['email'], 'celular' => $d['celular'], 'cpf' => $d['cpf'], 'parentesco' => $d['parentesco'] ?? null,
            'desde' => $d['inicio']] : null;
    }

    /** O financeiro e o titular do aluno no ano (o acesso adicional não sai pela interface: é só da tela). */
    public function responsaveisVer(array $p, array $q, array $d): array
    {
        $a = \App\adms\Models\escola\AdmsResponsaveis::alunoPorRa((string) $p['ra']);
        if (!$a) {
            return S::erro(404, 'nao_encontrado', "Aluno com RA {$p['ra']} não encontrado.", 'ra');
        }
        $ano = (int) ($q['ano'] ?? date('Y'));
        $r = \App\adms\Models\escola\AdmsResponsaveis::doAluno((int) $a['id'], $ano);
        return self::ok(['dados' => ['ra' => (string) $a['rm'], 'ano' => $ano, 'financeiro' => self::pessoaApi($r['financeiro']), 'titular' => self::pessoaApi($r['titular'])]]);
    }

    /**
     * Designa o financeiro ou o titular do aluno no ano, pela mesma função da tela: um financeiro só; a troca pede o
     * "motivo" e encerra o anterior na data; a pessoa que já existe (o mesmo e-mail ou CPF) não é cadastrada de novo.
     */
    public function responsaveisDesignar(array $p, array $q, array $d): array
    {
        $M = \App\adms\Models\escola\AdmsResponsaveis::class;
        $a = $M::alunoPorRa((string) $p['ra']);
        if (!$a) {
            return S::erro(404, 'nao_encontrado', "Aluno com RA {$p['ra']} não encontrado.", 'ra');
        }
        $papel = (string) ($d['papel'] ?? '');
        if ($papel === 'adicional') {
            return S::erro(422, 'validacao', 'O acesso acadêmico adicional não é liberado pela interface: exige o motivo e se libera na tela do aluno.', 'papel');
        }
        if (!in_array($papel, ['financeiro', 'titular'], true)) {
            return S::erro(422, 'validacao', 'Envie "papel": "financeiro" ou "titular".', 'papel');
        }
        $ano = (int) ($d['ano'] ?? 0);
        if (!in_array($ano, $M::anos(), true)) {
            return S::erro(422, 'validacao', 'Envie "ano": um ano letivo aberto na escola.', 'ano');
        }
        $pessoa = is_array($d['responsavel'] ?? null) ? $d['responsavel'] : [];
        [$c, $erro] = $M::limparPessoa($pessoa);
        if (!$c) {
            return S::erro(422, 'validacao', $erro, 'responsavel');
        }
        $existe = $M::procurar($c['email'], $c['cpf']);
        $alvo = $existe ? ['id' => (int) $existe['id']] : $pessoa;
        $alvo['parentesco'] = (string) ($pessoa['parentesco'] ?? '');
        $r = $M::designar((int) $a['id'], $ano, $papel, $alvo, isset($d['data']) ? (string) $d['data'] : null, isset($d['motivo']) ? (string) $d['motivo'] : null, 'api');
        if ($r !== true) {
            return S::erro(422, 'validacao', $r, str_contains($r, 'motivo') ? 'motivo' : 'papel');
        }
        return $this->responsaveisVer($p, ['ano' => $ano], []);
    }

    private static function limpar(string $t): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace('Fechar', '', html_entity_decode($t, ENT_QUOTES, 'UTF-8'))));
    }
}
