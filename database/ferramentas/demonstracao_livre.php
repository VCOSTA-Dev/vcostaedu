<?php
/*
 * A DEMONSTRAÇÃO DO CURSO LIVRE (Fechamento; D-106) — o Instituto Ventoalto (fictício), o mesmo do site de exemplo
 * (exemplos/curso-livre), numa instalação do tipo curso livre. No mesmo padrão das outras duas (demonstracao_passos.php,
 * demonstracao_escola.php): em passos curtos, PELAS TELAS do sistema (por HTTP), cada passo repetível sem duplicar, os
 * nomes fictícios marcados; montada e removida pela tela e pela linha de comando.
 *
 * O tamanho: o curso "IA na prática para professores", a matriz com os quatro módulos (as disciplinas, 10 horas), a
 * turma aberta do ano corrente com 500 vagas e o professor em cada módulo (o destino das inscrições do site), e dois
 * alunos fictícios na turma, com a senha definida pelo link, para entrar no portal pelo e-mail ou pela matrícula.
 *
 * Este arquivo só define a classe: incluí-lo não executa nada. Os passos usam VcDemoNavegador e VcDemoContexto
 * (demonstracao_passos.php).
 */
final class VcDemoLivre
{
    public const ANO = 2026;
    public const INSTITUICAO = 'Instituto Ventoalto (fictício)';
    public const CURSO = 'IA na prática para professores (demonstração)';
    public const TURMA = 'Turma aberta';
    public const CAPACIDADE = 500;
    public const CURSOS_DEMO = [self::CURSO];
    public const INSTITUICOES_DEMO = [self::INSTITUICAO];
    /** Os quatro módulos do curso (as disciplinas), com a carga horária: 10 horas, como no curso livre da escola. */
    public const MODULOS = VcDemoEscola::COMPONENTES[VcDemoEscola::CL];
    /** O professor dos quatro módulos. */
    public const PROFESSOR = 'prof.marcos.demo';
    /** Os alunos fictícios: matrícula => [nome, gênero]. */
    public const ALUNOS = [
        266001 => ['Alice Exemplo Ventoalto', 2],
        266002 => ['Bento Fictício Prado', 1],
    ];

    public const PASSOS = [
        'equipe'      => ['O instituto e a equipe', 'passoEquipe'],
        'instituicao' => ['O instituto e a equipe', 'passoInstituicao'],
        'estrutura'   => ['O curso e os quatro módulos', 'passoEstrutura'],
        'ano'         => ['A turma aberta', 'passoAno'],
        'turma'       => ['A turma aberta', 'passoTurma'],
        'alunos'      => ['Os alunos', 'passoAlunos'],
        'links'       => ['Os alunos', 'passoLinks'],
        'enturmar'    => ['Os alunos', 'passoEnturmar'],
    ];

    public static function rodar(string $chave, VcDemoContexto $c): void
    {
        if (!isset(self::PASSOS[$chave])) {
            VcDemoPassos::falha("passo desconhecido: {$chave}");
        }
        $m = self::PASSOS[$chave][1];
        self::$m($c);
    }

    /** As senhas fictícias: a equipe e os alunos. */
    public static function novasSenhas(): array
    {
        $s = [];
        foreach (array_keys(VcDemoPassos::EQUIPE) as $login) {
            $s[$login] = VcDemoPassos::senhaAleatoria();
        }
        foreach (array_keys(self::ALUNOS) as $ra) {
            $s[(string) $ra] = VcDemoPassos::senhaAleatoria();
        }
        return $s;
    }

    /** O e-mail fictício do aluno (o portal do curso livre aceita o e-mail ou a matrícula). */
    public static function emailDe(int $ra): string
    {
        return 'aluno.' . $ra . '@exemplo.invalid';
    }

    private static function um(VcDemoContexto $c, string $sql, array $p, string $oQue): int
    {
        return (int) $c->um($sql, $p) ?: VcDemoPassos::falha("{$oQue} não existe (o passo anterior não terminou).");
    }

    private static function curso(VcDemoContexto $c): int
    {
        return self::um($c, 'SELECT id FROM rm_cursos WHERE nome = ?', [self::CURSO], 'o curso');
    }

    private static function grade(VcDemoContexto $c): int
    {
        return self::um($c, 'SELECT id FROM rm_cursos_grades WHERE curso_id = ? AND versao = 1', [self::curso($c)], 'a matriz do curso');
    }

    /** O módulo interno da matriz (o curso livre não organiza a matriz em módulos: a turma fica nele). */
    private static function interno(VcDemoContexto $c): int
    {
        return self::um($c, 'SELECT id FROM rm_cursos_turma_series WHERE grade_id = ? ORDER BY situacao = 1 DESC, ordem, id LIMIT 1', [self::grade($c)], 'o módulo interno da matriz');
    }

    private static function turma(VcDemoContexto $c): int
    {
        return self::um($c, 'SELECT id FROM esc_turmas WHERE ano = ? AND curso_id = ? AND nome = ?', [self::ANO, self::curso($c), self::TURMA], 'a turma aberta');
    }

    /* ------------------------------------------------------------- 1. a equipe e o instituto */
    public static function passoEquipe(VcDemoContexto $c): void
    {
        $adm = $c->adm();
        $p = $c->painel();
        foreach (VcDemoPassos::EQUIPE as $login => [$nome, $papel, $genero]) {
            if ($c->um('SELECT id FROM adms_usuarios WHERE usuario = ?', [$login])) {
                continue;
            }
            $nivel = (int) $c->um('SELECT id FROM adms_niveis_acessos WHERE papel = ?', [$papel]) ?: VcDemoPassos::falha("o nível do papel {$papel} não existe");
            $adm->req($p . 'usuarios/cadastrar', [
                'nome' => $nome, 'genero_id' => (string) $genero, 'usuario' => $login, 'senha' => (string) $c->senhas[$login],
                'email' => $login . '@exemplo.invalid', 'adms_niveis_acesso_id' => (string) $nivel, 'adms_sits_usuario_id' => '1',
                'imagem_antiga' => '', 'imagem_nova' => new CURLStringFile('', '', 'application/octet-stream'), 'CadUsuario' => 'Cadastrar',
            ], true);
            VcDemoPassos::confere($adm, "cadastrar o usuário {$login}");
            $c->um('SELECT id FROM adms_usuarios WHERE usuario = ?', [$login]) ?: VcDemoPassos::falha("usuário {$login} não criado: " . $adm->alerta());
        }
        $c->log('A equipe da demonstração (direção, supervisão, secretaria, coordenação e os professores) cadastrada pelo administrador.');
    }

    public static function passoInstituicao(VcDemoContexto $c): void
    {
        $adm = $c->adm();
        $p = $c->painel();
        if (!$c->um('SELECT id FROM adms_empresas WHERE instituicao = 1 AND nome = ?', [self::INSTITUICAO])) {
            /* o logo fictício: neutro (o vento sobre a colina), as iniciais e "fictício" */
            $logo = imagecreatetruecolor(240, 120);
            imagefill($logo, 0, 0, imagecolorallocate($logo, 22, 66, 60));
            imagefilledellipse($logo, 60, 110, 120, 70, imagecolorallocate($logo, 106, 176, 140));
            imagefilledrectangle($logo, 20, 34, 100, 38, imagecolorallocate($logo, 233, 236, 239));
            imagefilledrectangle($logo, 36, 48, 96, 52, imagecolorallocate($logo, 233, 236, 239));
            imagestring($logo, 5, 120, 40, 'IV', imagecolorallocate($logo, 255, 255, 255));
            imagestring($logo, 3, 120, 64, 'ficticio', imagecolorallocate($logo, 200, 215, 220));
            $logoArq = (string) tempnam(sys_get_temp_dir(), 'logo');
            imagepng($logo, $logoArq);
            imagedestroy($logo);
            $adm->req($p . 'dados-instituicao/editar', [
                'nome' => self::INSTITUICAO, 'razao_social' => 'Instituto Ventoalto de Formação (fictício)', 'cnpj' => '11.222.333/0001-81',
                'codigo_emec' => '', 'site' => '', 'cep' => '88000-000', 'logradouro' => 'Rua das Demonstrações', 'numero' => '300', 'complemento' => '',
                'bairro' => 'Centro', 'cidade' => 'Cidade Exemplo', 'uf' => 'SC',
                'logo' => new CURLFile($logoArq, 'image/png', 'logo_ficticio.png'), 'SalvarInstituicao' => 'Salvar'], true);
            @unlink($logoArq);
            VcDemoPassos::confere($adm, 'os dados do instituto');
        }
        $c->um('SELECT id FROM adms_empresas WHERE instituicao = 1 AND nome = ?', [self::INSTITUICAO]) ?: VcDemoPassos::falha('os dados do instituto não foram gravados: ' . $adm->alerta());
        $c->log('O instituto fictício: ' . self::INSTITUICAO . '.');
    }

    /* ------------------------------------------------------------- 2. o curso, a matriz e os quatro módulos */
    public static function passoEstrutura(VcDemoContexto $c): void
    {
        $adm = $c->adm();
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $sede = (int) $c->um("SELECT id FROM rm_polos WHERE nome = 'Sede' LIMIT 1") ?: (int) $c->um('SELECT MIN(id) FROM rm_polos WHERE situacao = 1');
        if (!$c->um('SELECT id FROM rm_cursos WHERE nome = ?', [self::CURSO])) {
            $adm->req($p . 'cursos/cadastrar', ['nome' => self::CURSO, 'descricao' => 'Curso livre fictício de demonstração: 10 horas, em quatro módulos, o primeiro grátis (o site de exemplo vende este curso).',
                'periodo' => '1', 'modalidade_id' => '1', 'localidade' => $sede, 'filial' => '1', 'nivel' => 'Curso livre',
                'abreviatura' => 'IA', 'nome_extenso' => self::CURSO, 'cabecalho_documento' => self::INSTITUICAO, 'CadCurso' => 'Cadastrar']);
            VcDemoPassos::confere($adm, 'cadastrar o curso');
        }
        $curso = self::curso($c);
        if (!$c->um('SELECT id FROM rm_cursos_grades WHERE curso_id = ? AND versao = 1', [$curso])) {
            $sec->req($p . 'matriz/cadastrar-grade', ['curso_id' => $curso, 'versao' => '1', 'descricao' => 'Os quatro módulos do curso (10 horas)', 'SalvarGrade' => 'Salvar']);
            VcDemoPassos::confere($sec, 'cadastrar a matriz do curso');
        }
        $grade = self::grade($c);
        $k = 0;
        foreach (self::MODULOS as $nome => $ch) {
            if (!$c->um('SELECT id FROM rm_materias WHERE nome = ?', [$nome])) {
                $sec->gravar($p . 'materias/cadastrar', ['abreviacao' => 'IA' . str_pad((string) (++$k), 2, '0', STR_PAD_LEFT), 'nome' => $nome, 'carga_horaria' => (string) $ch,
                    'descricao' => 'Módulo do curso livre da demonstração', 'CadMaterias' => 'Cadastrar']);
                VcDemoPassos::confere($sec, "cadastrar a disciplina {$nome}");
            } else {
                ++$k;
            }
            $mat = self::um($c, 'SELECT id FROM rm_materias WHERE nome = ?', [$nome], "a disciplina {$nome}");
            if ($c->um('SELECT id FROM uni_rh_cursos_disciplinas_grade WHERE grade_id = ? AND materia_id = ?', [$grade, $mat])) {
                continue;
            }
            /* sem módulos na matriz: a disciplina vai para o módulo interno (a tela o cria na primeira) */
            $sec->gravar($p . "matriz/adicionar-disciplina/{$grade}", ['materia_id' => $mat, 'cargahoraria' => (string) $ch]);
            VcDemoPassos::confere($sec, "incluir {$nome} na matriz");
        }
        if ((int) $c->um('SELECT situacao FROM rm_cursos_grades WHERE id = ?', [$grade]) !== 3) {
            $adm->req($p . "matriz/situacao-grade/{$grade}", ['situacao' => '3', 'observacao' => 'Matriz de exemplo conferida (demonstração).']);
        }
        $c->log('O curso ' . self::CURSO . ': a matriz com os quatro módulos (' . array_sum(self::MODULOS) . ' horas).');
    }

    /* ------------------------------------------------------------- 3. o ano letivo e a turma aberta */
    public static function passoAno(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        if (!$c->um('SELECT id FROM adms_periodos_letivos WHERE ano = ?', [self::ANO])) {
            $sec->req($p . 'anos-letivos/cadastrar', ['ano' => (string) self::ANO, 'SalvarAnoLetivo' => 'Salvar']);
            VcDemoPassos::confere($sec, 'cadastrar o ano letivo ' . self::ANO);
        }
        $c->um('SELECT id FROM adms_periodos_letivos WHERE ano = ?', [self::ANO]) ?: VcDemoPassos::falha('o ano letivo não foi gravado: ' . $sec->alerta());
        $c->log('O ano letivo ' . self::ANO . ' (o curso livre não tem etapas).');
    }

    public static function passoTurma(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $mod = self::interno($c);
        if (!$c->um('SELECT id FROM esc_turmas WHERE ano = ? AND curso_id = ? AND nome = ?', [self::ANO, self::curso($c), self::TURMA])) {
            $sec->gravar($p . 'turmas/cadastrar', ['ano' => (string) self::ANO, 'modulo_id' => $mod, 'nome' => self::TURMA, 'turno_id' => '1', 'sala' => 'On-line', 'capacidade' => (string) self::CAPACIDADE]);
            VcDemoPassos::confere($sec, 'criar a turma aberta');
        }
        $t = self::turma($c);
        $prof = self::um($c, 'SELECT id FROM adms_usuarios WHERE usuario = ?', [self::PROFESSOR], 'o professor');
        $st = $c->pdo->prepare('SELECT o.id FROM esc_turma_ofertas x INNER JOIN rm_professores_materias o ON o.id = x.oferta_id WHERE x.turma_id = ?');
        $st->execute([$t]);
        $ofertas = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        count($ofertas) === count(self::MODULOS) ?: VcDemoPassos::falha('a turma aberta tem ' . count($ofertas) . ' disciplinas, e não as quatro.');
        foreach ($ofertas as $of) {
            if (!$c->um('SELECT id FROM rm_professores_mat_liga WHERE rm_prof_mat_id = ? AND usuario_id = ? AND situacao = 1', [$of, $prof])) {
                $sec->gravar($p . 'disciplinas/adiciona-professor/' . self::ANO, ['rm_prof_mat_id' => $of, 'professores' => [$prof], 'AdicionaProfessor' => 'Salvar']);
                VcDemoPassos::confere($sec, "o professor do módulo {$of}");
            }
        }
        $c->log('A turma aberta (' . self::CAPACIDADE . ' vagas, on-line), com os quatro módulos e o professor: o destino das inscrições do site de exemplo.');
    }

    /* ------------------------------------------------------------- 4. os alunos, a senha e a turma */
    public static function passoAlunos(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $mod = self::interno($c);
        foreach (self::ALUNOS as $ra => [$nome, $genero]) {
            if (!$c->um('SELECT id FROM col_usuarios WHERE rm = ?', [$ra])) {
                $sec->gravar($p . 'academicos/cadastrar', ['nome' => $nome, 'rm' => (string) $ra, 'email' => self::emailDe($ra), 'genero_id' => (string) $genero,
                    'col_sits_usuario_id' => '1', 'CadUsuarioAcad' => 'Cadastrar', 'nasc_data' => '1988-05-10', 'cpf' => VcDemoEscola::cpfDaDemonstracao($ra)]);
                VcDemoPassos::confere($sec, "cadastrar o aluno {$ra}");
            }
            $id = self::um($c, 'SELECT id FROM col_usuarios WHERE rm = ?', [$ra], "o aluno {$ra}");
            if (!$c->um('SELECT id FROM rm_matriculas_curso WHERE usuario_id = ? AND curso_id = ?', [$id, self::curso($c)])) {
                $sec->gravar($p . "academicos/vincular/{$id}", ['ano' => (string) self::ANO, 'modulo_id' => $mod, 'forma_ingresso_id' => '1', 'data_ingresso' => self::ANO . '-03-02',
                    'polo_id' => '', 'modalidade_id' => '', 'previsao_conclusao' => '']);
                VcDemoPassos::confere($sec, "matricular {$ra}");
            }
        }
        $c->log('Os dois alunos fictícios cadastrados e matriculados no curso.');
    }

    /** O link de acesso: cada aluno define a senha (como nas outras demonstrações). */
    public static function passoLinks(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        foreach (array_keys(self::ALUNOS) as $ra) {
            $id = self::um($c, 'SELECT id FROM col_usuarios WHERE rm = ?', [$ra], "o aluno {$ra}");
            if (str_starts_with((string) $c->um('SELECT senha FROM col_usuarios WHERE id = ?', [$id]), '$2')) {
                continue;
            }
            $sec->req($p . "academicos/enviar-link/{$id}", ['EnviarLink' => '1']);
            VcDemoPassos::confere($sec, "enviar o link a {$ra}");
            preg_match('#id="linkAcessoAluno" readonly value="([^"]+)"#', $sec->html, $m) ?: VcDemoPassos::falha("o link de acesso de {$ra} não apareceu: " . $sec->alerta());
            preg_match('/#t=([A-Za-z0-9]+)/', html_entity_decode($m[1]), $tk) ?: VcDemoPassos::falha("o link de {$ra} sem o token.");
            $senha = (string) $c->senhas[(string) $ra];
            $alu = new VcDemoNavegador();
            $alu->gravar($c->portal() . 'login/atualizar-senha', ['token' => $tk[1], 'senha' => $senha, 'senha_repetido' => $senha, 'AtualizaSenha' => 'Salvar']);
            VcDemoPassos::confere($alu, "o aluno {$ra} definir a senha");
        }
        $c->log('Os alunos definiram a senha pelo link de acesso.');
    }

    public static function passoEnturmar(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $t = self::turma($c);
        $faltam = [];
        foreach (array_keys(self::ALUNOS) as $ra) {
            if (!$c->um('SELECT id FROM esc_turma_alunos WHERE turma_id = ? AND rm = ? AND saida IS NULL', [$t, (string) $ra])) {
                $faltam[] = (int) $c->um('SELECT id FROM col_usuarios WHERE rm = ?', [$ra]);
            }
        }
        if ($faltam) {
            $sec->req($p . "turmas/enturmar/{$t}", ['fase' => 'iniciar', 'alunos' => implode(',', $faltam)]);
            for ($i = 0; $i < 20; $i++) {
                $sec->req($p . "turmas/enturmar/{$t}", ['fase' => 'passo']);
                $j = json_decode($sec->html, true);
                if (!empty($j['concluida'])) {
                    break;
                }
            }
            $sec->req($p . "turmas/ver/{$t}"); // o lote concluído sai do banco
        }
        (int) $c->um('SELECT COUNT(*) FROM esc_turma_alunos WHERE turma_id = ? AND saida IS NULL', [$t]) >= count(self::ALUNOS) ?: VcDemoPassos::falha('a turma aberta: os alunos não entraram.');
        $c->log('Os dois alunos na turma aberta, nos quatro módulos.');
    }
}
