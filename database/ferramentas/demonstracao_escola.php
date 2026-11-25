<?php
/*
 * A DEMONSTRAÇÃO DA ESCOLA (Onda 4, Etapa 4; docs/ESCOLA.md) — o Colégio Horizonte (fictício), no mesmo padrão da
 * faculdade fictícia (demonstracao_passos.php): em passos curtos, PELAS TELAS do sistema (por HTTP), cada passo
 * repetível sem duplicar, os nomes fictícios marcados; montada e removida pela tela e pela linha de comando.
 *
 * A matriz segue a Base Nacional Comum Curricular como EXEMPLO (cada escola ajusta a sua; nada aqui afirma
 * conformidade legal) — as fontes de cada número estão em docs/ESCOLA.md, "A matriz da demonstração":
 *   - Ensino Fundamental, anos finais (6º ao 9º ano): Linguagens (Língua Portuguesa, Arte, Educação Física, Língua
 *     Inglesa), Matemática, Ciências da Natureza (Ciências), Ciências Humanas (Geografia, História) e Ensino Religioso;
 *     840 h por ano (o mínimo é 800 h e 200 dias letivos);
 *   - Ensino Médio (1ª à 3ª série): a formação geral básica por área (800 h por ano, 2.400 h nos três) e UM itinerário de
 *     exemplo cadastrado como componentes comuns (200 h por ano, 600 h nos três); 1.000 h por ano, 3.000 h no total.
 * O tamanho: no ano corrente, as turmas 6º ano A e B, 8º ano A, 9º ano A e 1ª série A, com dois bimestres fechados e o
 * terceiro aberto, com as notas; uma turma de 9º ano do ano anterior, concluída. Os casos: aprovado direto; recuperação
 * que salvou a etapa; recuperação que não salvou; reprovado por falta com nota; aprovado pelo conselho; aluno que trocou
 * de turma; aluno que entrou no meio do ano, com as notas de transferência; a Matemática do fundamental na chamada.
 *
 * Este arquivo só define a classe: incluí-lo não executa nada. Os passos usam VcDemoNavegador e VcDemoContexto
 * (demonstracao_passos.php).
 */
final class VcDemoEscola
{
    public const ANO = 2026;
    public const ANO_ANTERIOR = 2025;
    public const INSTITUICAO = 'Colégio Horizonte (fictício)';
    public const EF = 'Ensino Fundamental — Anos Finais (demonstração)';
    public const EM = 'Ensino Médio (demonstração)';
    /** Onda 8 (D-70): o curso livre que o site de exemplo vende — uma turma aberta, os quatro módulos como componentes. */
    public const CL = 'Curso livre: IA na prática para professores (demonstração)';
    public const TURMA_CL = 'Turma aberta';
    public const CAPACIDADE_CL = 500;
    public const CURSOS_DEMO = [self::EF, self::EM, self::CL];
    public const INSTITUICOES_DEMO = [self::INSTITUICAO];
    public const SERIES = [self::EF => ['6º ano', '7º ano', '8º ano', '9º ano'], self::EM => ['1ª série', '2ª série', '3ª série'], self::CL => ['Turma única']];
    /** Os componentes de cada segmento, com a carga horária do ano (o exemplo pela Base). */
    public const COMPONENTES = [
        self::EF => ['Língua Portuguesa' => 160, 'Arte' => 40, 'Educação Física' => 80, 'Língua Inglesa' => 80, 'Matemática' => 160,
            'Ciências' => 120, 'Geografia' => 80, 'História' => 80, 'Ensino Religioso' => 40],
        self::EM => ['Língua Portuguesa' => 120, 'Língua Inglesa' => 80, 'Arte' => 40, 'Educação Física' => 80, 'Matemática' => 120,
            'Biologia' => 80, 'Física' => 80, 'Química' => 80, 'História' => 40, 'Geografia' => 40, 'Filosofia' => 20, 'Sociologia' => 20,
            'Itinerário: Projeto de Ciência e Tecnologia (exemplo)' => 100, 'Itinerário: Laboratório de Investigação (exemplo)' => 100],
        /* os quatro módulos, 10 h no total: 3, 2, 3 e 2 horas na matriz (a página de venda diz só "10 horas, em quatro módulos", sem hora por módulo) */
        self::CL => ['Módulo 1 — Primeiros passos com IA na sala de aula' => 3, 'Módulo 2 — Planejar aulas e atividades' => 2,
            'Módulo 3 — Avaliar e dar retorno' => 3, 'Módulo 4 — Ética, privacidade e autoria' => 2],
    ];
    /** As turmas: [ano, segmento, série, nome] => os alunos (RA => [nome, gênero, perfil]). */
    public const TURMAS = [
        '2026-6A' => [self::ANO, self::EF, '6º ano', 'A', [
            265001 => ['Ana Exemplo Ribeiro', 2, 'bom'], 265002 => ['Bruno Fictício Alves', 1, 'salva'], 265003 => ['Carla Modelo Nunes', 2, 'nao_salva'],
            265004 => ['Davi Inventado Lopes', 1, 'troca'], 265005 => ['Eva Amostra Prado', 2, 'bom']]],
        '2026-6B' => [self::ANO, self::EF, '6º ano', 'B', [265011 => ['Felipe Protótipo Dias', 1, 'bom'], 265012 => ['Gabi Simulada Reis', 2, 'medio']]],
        '2026-9A' => [self::ANO, self::EF, '9º ano', 'A', [265021 => ['Hugo Hipotético Sá', 1, 'bom'], 265022 => ['Íris Exemplo Ribeiro Sá', 2, 'medio'],
            265023 => ['Júlio Fictício Cruz', 1, 'salva'], 265024 => ['Kátia Modelo Pires', 2, 'bom']]],
        '2026-1A' => [self::ANO, self::EM, '1ª série', 'A', [265031 => ['Lucas Inventado Rocha', 1, 'bom'], 265032 => ['Mara Amostra Ribeiro', 2, 'medio'],
            265033 => ['Nuno Protótipo Dias', 1, 'nao_salva'], 265034 => ['Olga Simulada Brito', 2, 'bom']]],
        '2025-9A' => [self::ANO_ANTERIOR, self::EF, '9º ano', 'A', [255001 => ['Paulo Hipotético Lima', 1, 'aprovado'], 255002 => ['Quésia Exemplo Torres', 2, 'aprovado'],
            255003 => ['Rui Fictício Moura', 1, 'falta'], 255004 => ['Sara Modelo Vieira', 2, 'conselho']]],
    ];
    /**
     * Correção da escola, Parte 5 (D-104, D1): a turma do 8º ano, montada por passos no fim da montagem (os passos de antes
     * usam só TURMAS: os números de tudo o que eles criam não mudam). O perfil "meio_ano": o aluno que entrou no início do
     * 3º bimestre, vindo de outra escola (as notas e as faltas do 1º e do 2º bimestre são as de transferência).
     */
    public const TURMAS_DEPOIS = [
        '2026-8A' => [self::ANO, self::EF, '8º ano', 'A', [265041 => ['Rita Exemplo Campos', 2, 'bom'], 265042 => ['Saulo Fictício Barros', 1, 'medio'],
            265043 => ['Tânia Modelo Freitas', 2, 'salva'], 265044 => ['Vítor Inventado Gomes', 1, 'meio_ano']]],
    ];
    /** O aluno de perfil "meio_ano" entra no início desta etapa (a ordem). */
    public const ETAPA_DE_ENTRADA = 3;
    /** A escola de origem das notas de transferência (fictícia). */
    public const ESCOLA_ORIGEM = 'Escola Municipal Exemplo (fictícia)';

    /** Todas as turmas: as de TURMAS e as montadas depois. */
    public static function todas(): array
    {
        return self::TURMAS + self::TURMAS_DEPOIS;
    }

    /** O professor de Matemática de cada segmento (a equipe é a da demonstração: demonstracao_passos.php). */
    public const PROFESSOR = [self::EF => 'prof.laura.demo', self::EM => 'prof.marcos.demo'];
    /**
     * Onda 8, correção, Parte 4 (D-84): um professor fictício para cada componente (o mesmo nos dois segmentos, quando o
     * componente se repete); a Matemática fica com a professora do fundamental e o professor do médio (PROFESSOR), e o
     * curso livre, com o professor do médio. Os professores a mais são só da escola: login => [nome, gênero].
     */
    public const EQUIPE_ESCOLA = [
        'prof.renata.demo'   => ['Profa. Renata Exemplo (fictícia)', 1],
        'prof.otavio.demo'   => ['Prof. Otávio Modelo (fictício)', 2],
        'prof.bianca.demo'   => ['Profa. Bianca Amostra (fictícia)', 1],
        'prof.wagner.demo'   => ['Prof. Wagner Inventado (fictício)', 2],
        'prof.celia.demo'    => ['Profa. Célia Protótipo (fictícia)', 1],
        'prof.gustavo.demo'  => ['Prof. Gustavo Simulado (fictício)', 2],
        'prof.heloisa.demo'  => ['Profa. Heloísa Hipotética (fictícia)', 1],
        'prof.ronaldo.demo'  => ['Prof. Ronaldo Exemplo (fictício)', 2],
        'prof.bruna.demo'    => ['Profa. Bruna Modelo (fictícia)', 1],
        'prof.flavio.demo'   => ['Prof. Flávio Amostra (fictício)', 2],
        'prof.quiteria.demo' => ['Profa. Quitéria Inventada (fictícia)', 1],
        'prof.teodoro.demo'  => ['Prof. Teodoro Protótipo (fictício)', 2],
        'prof.sofia.demo'    => ['Profa. Sofia Simulada (fictícia)', 1],
        'prof.ivo.demo'      => ['Prof. Ivo Hipotético (fictício)', 2],
        'prof.lara.demo'     => ['Profa. Lara Exemplo (fictícia)', 1],
    ];
    /** O componente => o professor (fora da Matemática). */
    public const PROFESSOR_COMPONENTE = [
        'Língua Portuguesa' => 'prof.renata.demo', 'Arte' => 'prof.otavio.demo', 'Educação Física' => 'prof.bianca.demo',
        'Língua Inglesa' => 'prof.wagner.demo', 'Ciências' => 'prof.celia.demo', 'Geografia' => 'prof.gustavo.demo',
        'História' => 'prof.heloisa.demo', 'Ensino Religioso' => 'prof.ronaldo.demo', 'Biologia' => 'prof.bruna.demo',
        'Física' => 'prof.flavio.demo', 'Química' => 'prof.quiteria.demo', 'Filosofia' => 'prof.teodoro.demo',
        'Sociologia' => 'prof.sofia.demo', 'Itinerário: Projeto de Ciência e Tecnologia (exemplo)' => 'prof.ivo.demo',
        'Itinerário: Laboratório de Investigação (exemplo)' => 'prof.lara.demo',
    ];

    /** O professor de um componente do segmento (o login). */
    public static function professorDe(string $seg, string $materia): string
    {
        return self::PROFESSOR_COMPONENTE[$materia] ?? self::PROFESSOR[$seg] ?? self::PROFESSOR[self::EM];
    }
    /** Quantas etapas ficam fechadas e até qual há nota: o ano corrente com dois fechados e o terceiro em andamento. */
    public const ETAPAS = [self::ANO => ['fechadas' => 2, 'com_nota' => 3], self::ANO_ANTERIOR => ['fechadas' => 4, 'com_nota' => 4]];

    public const PASSOS = [
        'equipe'          => ['A escola e a equipe', 'passoEquipe'],
        'instituicao'     => ['A escola e a equipe', 'passoInstituicao'],
        'estrutura_EF'    => ['Os segmentos, as séries e as disciplinas', 'passoEstrutura'],
        'estrutura_EM'    => ['Os segmentos, as séries e as disciplinas', 'passoEstrutura'],
        'anos'            => ['O ano letivo e as turmas', 'passoAnos'],
        'turmas'          => ['O ano letivo e as turmas', 'passoTurmas'],
        'alunos'          => ['Os alunos', 'passoAlunos'],
        'links'           => ['Os alunos', 'passoLinks'],
        'enturmar'        => ['Os alunos', 'passoEnturmar'],
        /* as notas: um passo por turma e bimestre; o fechamento: um passo por turma (cada passo bem abaixo do limite de uma requisição) */
        'notas_2026-6A_1' => ['As notas e as etapas', 'passoNotas'],
        'notas_2026-6A_2' => ['As notas e as etapas', 'passoNotas'],
        'notas_2026-6A_3' => ['As notas e as etapas', 'passoNotas'],
        'notas_2026-6B_1' => ['As notas e as etapas', 'passoNotas'],
        'notas_2026-6B_2' => ['As notas e as etapas', 'passoNotas'],
        'notas_2026-6B_3' => ['As notas e as etapas', 'passoNotas'],
        'notas_2026-9A_1' => ['As notas e as etapas', 'passoNotas'],
        'notas_2026-9A_2' => ['As notas e as etapas', 'passoNotas'],
        'notas_2026-9A_3' => ['As notas e as etapas', 'passoNotas'],
        'notas_2026-1A_1' => ['As notas e as etapas', 'passoNotas'],
        'notas_2026-1A_2' => ['As notas e as etapas', 'passoNotas'],
        'notas_2026-1A_3' => ['As notas e as etapas', 'passoNotas'],
        'notas_2025-9A_1' => ['O ano anterior, concluído', 'passoNotas'],
        'notas_2025-9A_2' => ['O ano anterior, concluído', 'passoNotas'],
        'notas_2025-9A_3' => ['O ano anterior, concluído', 'passoNotas'],
        'notas_2025-9A_4' => ['O ano anterior, concluído', 'passoNotas'],
        'fechar_2026-6A'  => ['As notas e as etapas', 'passoFechar'],
        'fechar_2026-6B'  => ['As notas e as etapas', 'passoFechar'],
        'fechar_2026-9A'  => ['As notas e as etapas', 'passoFechar'],
        'fechar_2026-1A'  => ['As notas e as etapas', 'passoFechar'],
        'troca'           => ['As notas e as etapas', 'passoTroca'],
        'fechar_2025-9A'  => ['O ano anterior, concluído', 'passoFechar'],
        'conselho'        => ['O ano anterior, concluído', 'passoConselho'],
        'boletim'         => ['O ano anterior, concluído', 'passoBoletim'],
        /* Onda 7: os responsáveis fictícios e o convite */
        'responsaveis'    => ['Os responsáveis', 'passoResponsaveis'],
        'convites'        => ['Os responsáveis', 'passoConvites'],
        /* Onda 8 (D-70): no fim (os ids de tudo o que vem antes não mudam), o curso livre do site de exemplo */
        'estrutura_CL'    => ['O curso livre', 'passoEstrutura'],
        'curso_livre'     => ['O curso livre', 'passoCursoLivre'],
        /* Onda 8, correção (D-71): no fim também, o horário da semana das quatro turmas de 2026 */
        'horario_2026-6A' => ['O horário das turmas', 'passoHorario'],
        'horario_2026-6B' => ['O horário das turmas', 'passoHorario'],
        'horario_2026-9A' => ['O horário das turmas', 'passoHorario'],
        'horario_2026-1A' => ['O horário das turmas', 'passoHorario'],
        /* Onda 8, correção, acréscimo A (D-79): um documento emitido, para o relatório de documentos */
        'documentos'      => ['Os documentos', 'passoDocumentos'],
        /* Onda 8, financeiro, Parte 8 (D-98): no fim, o financeiro de cada turma de 2026 */
        'financeiro_2026-6A' => ['O financeiro', 'passoFinanceiro'],
        'financeiro_2026-6B' => ['O financeiro', 'passoFinanceiro'],
        'financeiro_2026-9A' => ['O financeiro', 'passoFinanceiro'],
        'financeiro_2026-1A' => ['O financeiro', 'passoFinanceiro'],
        /* Correção da escola, Parte 5 (D-104): no fim (os números de tudo o que vem antes não mudam), o 8º ano A, o aluno
           que entrou no meio do ano e a Matemática do fundamental na chamada */
        'turmas_2026-8A'  => ['O 8º ano e a chamada', 'passoTurmas'],
        'alunos_2026-8A'  => ['O 8º ano e a chamada', 'passoAlunos'],
        'links_2026-8A'   => ['O 8º ano e a chamada', 'passoLinks'],
        'enturmar_2026-8A' => ['O 8º ano e a chamada', 'passoEnturmar'],
        'notas_2026-8A_1' => ['O 8º ano e a chamada', 'passoNotas'],
        'notas_2026-8A_2' => ['O 8º ano e a chamada', 'passoNotas'],
        'notas_2026-8A_3' => ['O 8º ano e a chamada', 'passoNotas'],
        'fechar_2026-8A'  => ['O 8º ano e a chamada', 'passoFechar'],
        'transferencia'   => ['O 8º ano e a chamada', 'passoTransferencia'],
        'horario_2026-8A' => ['O 8º ano e a chamada', 'passoHorario'],
        'chamada'         => ['O 8º ano e a chamada', 'passoChamada'],
        'financeiro_2026-8A' => ['O 8º ano e a chamada', 'passoFinanceiro'],
    ];

    public static function rodar(string $chave, VcDemoContexto $c): void
    {
        if (!isset(self::PASSOS[$chave])) {
            VcDemoPassos::falha("passo desconhecido: {$chave}");
        }
        $m = self::PASSOS[$chave][1];
        match ($m) {
            'passoEstrutura' => self::passoEstrutura($c, ['EF' => self::EF, 'EM' => self::EM, 'CL' => self::CL][substr($chave, 10)]),
            'passoNotas' => self::passoNotas($c, ...self::turmaEtapa(substr($chave, 6))),
            'passoFechar' => self::passoFechar($c, substr($chave, 7)),
            'passoHorario' => self::passoHorario($c, substr($chave, 8)),
            'passoFinanceiro' => self::passoFinanceiro($c, substr($chave, 11)),
            /* D-104: os passos das turmas montadas depois ("turmas_2026-8A" → só aquela turma) */
            'passoTurmas', 'passoAlunos', 'passoEnturmar' => self::$m($c, str_contains($chave, '_') ? [substr($chave, strpos($chave, '_') + 1) => self::TURMAS_DEPOIS[substr($chave, strpos($chave, '_') + 1)]] : self::TURMAS),
            'passoLinks' => self::passoLinks($c, str_contains($chave, '_') ? self::ras(self::TURMAS_DEPOIS) : self::ras(self::TURMAS)),
            default => self::$m($c),
        };
    }

    /** "2026-6A_2" → ["2026-6A", 2] (a turma e o bimestre do passo das notas). */
    private static function turmaEtapa(string $x): array
    {
        [$k, $e] = explode('_', $x, 2);
        return [$k, (int) $e];
    }

    /** As senhas fictícias: a equipe da demonstração e os alunos. */
    public static function novasSenhas(): array
    {
        $s = [];
        foreach (array_merge(VcDemoPassos::EQUIPE, self::EQUIPE_ESCOLA) as $login => $x) { // Onda 8 (D-84): os professores por componente
            $s[$login] = VcDemoPassos::senhaAleatoria();
        }
        foreach (self::todas() as [, , , , $alunos]) {
            foreach ($alunos as $ra => $x) {
                $s[(string) $ra] = VcDemoPassos::senhaAleatoria();
            }
        }
        /* Onda 7: os responsáveis que criam a senha pelo convite (a chave é o e-mail) */
        foreach (self::RESPONSAVEIS as $email => [, $acesso]) {
            if ($acesso === 'senha') {
                $s[$email] = VcDemoPassos::senhaAleatoria();
            }
        }
        return $s;
    }

    /* ------------------------------------------------------------- o que os passos anteriores criaram */
    private static function um(VcDemoContexto $c, string $sql, array $p, string $oQue): int
    {
        return (int) $c->um($sql, $p) ?: VcDemoPassos::falha("{$oQue} não existe (o passo anterior não terminou).");
    }

    /** A sigla do segmento (EF, EM) ou do curso livre (CL). */
    private static function sigla(string $seg): string
    {
        return [self::EF => 'EF', self::EM => 'EM', self::CL => 'CL'][$seg];
    }

    /**
     * Onda 8 (D-70): a turma aberta do curso livre — o destino das inscrições do site de exemplo: a série única, os quatro
     * módulos como componentes (cada um vira uma disciplina no Moodle), o professor e vagas livres. Sem aluno: quem chega
     * é quem se inscreve pelo site.
     */
    public static function passoCursoLivre(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $mod = self::serie($c, self::CL, 'Turma única');
        if (!$c->um('SELECT id FROM esc_turmas WHERE ano = ? AND modulo_id = ? AND nome = ?', [self::ANO, $mod, self::TURMA_CL])) {
            $sec->gravar($p . 'turmas/cadastrar', ['ano' => (string) self::ANO, 'modulo_id' => $mod, 'nome' => self::TURMA_CL, 'turno_id' => '1', 'sala' => 'On-line', 'capacidade' => (string) self::CAPACIDADE_CL]);
            VcDemoPassos::confere($sec, 'criar a turma do curso livre');
        }
        $t = self::um($c, 'SELECT id FROM esc_turmas WHERE ano = ? AND modulo_id = ? AND nome = ?', [self::ANO, $mod, self::TURMA_CL], 'a turma do curso livre');
        $prof = (int) $c->um('SELECT id FROM adms_usuarios WHERE usuario = ?', [self::PROFESSOR[self::EM]]);
        foreach (self::ofertas($c, $t) as $of) {
            if (!$c->um('SELECT id FROM rm_professores_mat_liga WHERE rm_prof_mat_id = ? AND usuario_id = ? AND situacao = 1', [$of, $prof])) {
                $sec->gravar($p . 'disciplinas/adiciona-professor/' . self::ANO, ['rm_prof_mat_id' => $of, 'professores' => [$prof], 'AdicionaProfessor' => 'Salvar']);
                VcDemoPassos::confere($sec, "o professor do módulo {$of}");
            }
        }
        $c->log('O curso livre do site de exemplo: a turma aberta (' . self::CAPACIDADE_CL . ' vagas), com os quatro módulos.');
    }

    private static function curso(VcDemoContexto $c, string $seg): int
    {
        return self::um($c, 'SELECT id FROM rm_cursos WHERE nome = ?', [$seg], "o segmento {$seg}");
    }

    private static function grade(VcDemoContexto $c, string $seg): int
    {
        return self::um($c, 'SELECT id FROM rm_cursos_grades WHERE curso_id = ? AND versao = 1', [self::curso($c, $seg)], "a matriz de {$seg}");
    }

    private static function serie(VcDemoContexto $c, string $seg, string $serie): int
    {
        return self::um($c, 'SELECT id FROM rm_cursos_turma_series WHERE grade_id = ? AND codnome = ?', [self::grade($c, $seg), $serie], "a série {$serie}");
    }

    private static function turma(VcDemoContexto $c, string $k): int
    {
        [$ano, $seg, $serie, $nome] = self::todas()[$k];
        return self::um($c, 'SELECT id FROM esc_turmas WHERE ano = ? AND modulo_id = ? AND nome = ?', [$ano, self::serie($c, $seg, $serie), $nome], "a turma {$k}");
    }

    /** As ofertas da turma: materia => id. */
    private static function ofertas(VcDemoContexto $c, int $turma): array
    {
        $st = $c->pdo->prepare('SELECT o.materia, o.id FROM esc_turma_ofertas x INNER JOIN rm_professores_materias o ON o.id = x.oferta_id WHERE x.turma_id = ?');
        $st->execute([$turma]);
        return $st->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    private static function etapas(VcDemoContexto $c, int $ano): array
    {
        $st = $c->pdo->prepare('SELECT ordem, id, inicio, fim FROM esc_etapas WHERE ano = ? ORDER BY ordem');
        $st->execute([$ano]);
        $e = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $e[(int) $r['ordem']] = $r;
        }
        count($e) === 4 ?: VcDemoPassos::falha("o ano {$ano} sem as 4 etapas.");
        return $e;
    }

    /**
     * A nota (texto, uma casa) do aluno de perfil $perfil no componente de índice $i, na etapa $e. Determinística.
     * Os casos: bom (7 a 9), medio (6 a 7,5), salva/nao_salva/troca (o 1º e o 2º bimestre de Matemática abaixo da média),
     * aprovado, falta (boa nota; as faltas vão à parte), conselho (abaixo da média em Matemática o ano todo).
     */
    public static function nota(string $perfil, string $comp, int $i, int $e): string
    {
        $base = ['bom' => 80, 'medio' => 68, 'salva' => 70, 'nao_salva' => 66, 'troca' => 72, 'aprovado' => 78, 'falta' => 82, 'conselho' => 70][$perfil] ?? 70;
        $v = $base + (($i * 7 + $e * 3) % 11) - 5;
        if ($comp === 'Matemática') {
            if (in_array($perfil, ['salva', 'nao_salva'], true) && $e === 1) {
                $v = 40;
            }
            if ($perfil === 'nao_salva' && $e === 2) {
                $v = 42;
            }
            if ($perfil === 'conselho') {
                $v = [1 => 44, 2 => 46, 3 => 50, 4 => 48][$e];
            }
        }
        return number_format(max(0, min(100, $v)) / 10, 1, '.', '');
    }

    /* ------------------------------------------------------------- 1. a equipe (a mesma da demonstração) e a escola */
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
        /* Onda 8, correção (D-84): os professores de cada componente, também pela tela */
        $nivelProf = (int) $c->um("SELECT id FROM adms_niveis_acessos WHERE papel = 'professor'") ?: VcDemoPassos::falha('o nível do professor não existe');
        foreach (self::EQUIPE_ESCOLA as $login => [$nome, $genero]) {
            if ($c->um('SELECT id FROM adms_usuarios WHERE usuario = ?', [$login])) {
                continue;
            }
            $adm->req($p . 'usuarios/cadastrar', [
                'nome' => $nome, 'genero_id' => (string) $genero, 'usuario' => $login, 'senha' => (string) $c->senhas[$login],
                'email' => $login . '@exemplo.invalid', 'adms_niveis_acesso_id' => (string) $nivelProf, 'adms_sits_usuario_id' => '1',
                'imagem_antiga' => '', 'imagem_nova' => new CURLStringFile('', '', 'application/octet-stream'), 'CadUsuario' => 'Cadastrar',
            ], true);
            VcDemoPassos::confere($adm, "cadastrar o professor {$login}");
            $c->um('SELECT id FROM adms_usuarios WHERE usuario = ?', [$login]) ?: VcDemoPassos::falha("professor {$login} não criado: " . $adm->alerta());
        }
        $c->log('A equipe da demonstração (direção, supervisão, secretaria, coordenação e um professor por disciplina) cadastrada pelo administrador.');
    }

    public static function passoInstituicao(VcDemoContexto $c): void
    {
        $adm = $c->adm();
        $p = $c->painel();
        if (!$c->um('SELECT id FROM adms_empresas WHERE instituicao = 1 AND nome = ?', [self::INSTITUICAO])) {
            /* o logo fictício: neutro (o horizonte), as iniciais e "fictício" */
            $logo = imagecreatetruecolor(240, 120);
            imagefill($logo, 0, 0, imagecolorallocate($logo, 29, 53, 87));
            imagefilledellipse($logo, 60, 66, 70, 70, imagecolorallocate($logo, 233, 196, 106));
            imagefilledrectangle($logo, 14, 66, 106, 104, imagecolorallocate($logo, 29, 53, 87));
            imagefilledrectangle($logo, 14, 66, 106, 69, imagecolorallocate($logo, 233, 236, 239));
            imagestring($logo, 5, 120, 40, 'CH', imagecolorallocate($logo, 255, 255, 255));
            imagestring($logo, 3, 120, 64, 'ficticio', imagecolorallocate($logo, 200, 215, 220));
            $logoArq = (string) tempnam(sys_get_temp_dir(), 'logo');
            imagepng($logo, $logoArq);
            imagedestroy($logo);
            $adm->req($p . 'dados-instituicao/editar', [
                'nome' => self::INSTITUICAO, 'razao_social' => 'Associação Horizonte de Ensino (fictícia)', 'cnpj' => '11.222.333/0001-81',
                'codigo_emec' => '', 'site' => '', 'cep' => '88000-000', 'logradouro' => 'Rua das Demonstrações', 'numero' => '200', 'complemento' => '',
                'bairro' => 'Centro', 'cidade' => 'Cidade Exemplo', 'uf' => 'SC',
                'logo' => new CURLFile($logoArq, 'image/png', 'logo_ficticio.png'), 'SalvarInstituicao' => 'Salvar'], true);
            @unlink($logoArq);
            VcDemoPassos::confere($adm, 'os dados da escola');
        }
        $c->um('SELECT id FROM adms_empresas WHERE instituicao = 1 AND nome = ?', [self::INSTITUICAO]) ?: VcDemoPassos::falha('os dados da escola não foram gravados: ' . $adm->alerta());
        $c->log('A escola fictícia: ' . self::INSTITUICAO . '.');
    }

    /* ------------------------------------------------------------- 2. o segmento, a matriz, as séries e os componentes */
    public static function passoEstrutura(VcDemoContexto $c, string $seg): void
    {
        $adm = $c->adm();
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $sede = (int) $c->um("SELECT id FROM rm_polos WHERE nome = 'Sede' LIMIT 1") ?: (int) $c->um('SELECT MIN(id) FROM rm_polos WHERE situacao = 1');
        if (!$c->um('SELECT id FROM rm_cursos WHERE nome = ?', [$seg])) {
            $livre = $seg === self::CL;
            $adm->req($p . 'cursos/cadastrar', ['nome' => $seg, 'descricao' => $livre ? 'Curso livre fictício de demonstração: 10 horas, em quatro módulos, o primeiro grátis (o site de exemplo vende este curso).'
                : 'Segmento fictício de demonstração: a matriz é um exemplo que segue a Base Nacional Comum Curricular.', 'periodo' => '1',
                'modalidade_id' => '1', 'localidade' => $sede, 'filial' => '1', 'nivel' => ['EF' => 'Ensino Fundamental', 'EM' => 'Ensino Médio', 'CL' => 'Curso livre'][self::sigla($seg)],
                'abreviatura' => self::sigla($seg), 'nome_extenso' => $seg, 'cabecalho_documento' => self::INSTITUICAO, 'CadCurso' => 'Cadastrar']);
            VcDemoPassos::confere($adm, "cadastrar o segmento {$seg}");
        }
        $curso = self::curso($c, $seg);
        if (!$c->um('SELECT id FROM rm_cursos_grades WHERE curso_id = ? AND versao = 1', [$curso])) {
            $sec->req($p . 'matriz/cadastrar-grade', ['curso_id' => $curso, 'versao' => '1', 'descricao' => $seg === self::CL ? 'Os quatro módulos do curso livre (10 horas)'
                : 'Matriz de exemplo pela Base Nacional Comum Curricular (cada escola ajusta a sua)', 'SalvarGrade' => 'Salvar']);
            VcDemoPassos::confere($sec, "cadastrar a matriz de {$seg}");
        }
        $grade = self::grade($c, $seg);
        foreach (self::SERIES[$seg] as $i => $nome) {
            if (!$c->um('SELECT id FROM rm_cursos_turma_series WHERE grade_id = ? AND codnome = ?', [$grade, $nome])) {
                $sec->gravar($p . 'modulos/cadastrar', ['grade_id' => $grade, 'codnome' => $nome, 'ordem' => (string) ($i + 1), 'descricao' => '', 'observacao' => '', 'situacao' => '1', 'SalvarModulo' => 'Salvar']);
                VcDemoPassos::confere($sec, "cadastrar a série {$nome}");
            }
        }
        $k = 0;
        foreach (self::COMPONENTES[$seg] as $nome => $ch) {
            if (!$c->um('SELECT id FROM rm_materias WHERE nome = ?', [$nome])) {
                $sec->gravar($p . 'materias/cadastrar', ['abreviacao' => ($seg === self::CL ? 'IA' : 'C') . str_pad((string) (++$k), 2, '0', STR_PAD_LEFT), 'nome' => $nome, 'carga_horaria' => (string) $ch,
                    'descricao' => $seg === self::CL ? 'Módulo do curso livre da demonstração' : 'Disciplina da demonstração', 'CadMaterias' => 'Cadastrar']);
                VcDemoPassos::confere($sec, "cadastrar a disciplina {$nome}");
            }
            $mat = self::um($c, 'SELECT id FROM rm_materias WHERE nome = ?', [$nome], "a disciplina {$nome}");
            foreach (self::SERIES[$seg] as $serie) {
                $mod = self::serie($c, $seg, $serie);
                if ($c->um('SELECT id FROM uni_rh_cursos_disciplinas_grade WHERE grade_id = ? AND modulo_id = ? AND materia_id = ?', [$grade, $mod, $mat])) {
                    continue;
                }
                $sec->gravar($p . "matriz/adicionar-disciplina/{$grade}", ['materia_id' => $mat, 'modulo_id' => $mod, 'cargahoraria' => (string) $ch]);
                VcDemoPassos::confere($sec, "incluir {$nome} na série {$serie}");
            }
        }
        if ((int) $c->um('SELECT situacao FROM rm_cursos_grades WHERE id = ?', [$grade]) !== 3) {
            $adm->req($p . "matriz/situacao-grade/{$grade}", ['situacao' => '3', 'observacao' => 'Matriz de exemplo conferida (demonstração).']);
        }
        /* Onda 8, correção, segunda leva, 2.3 (D-89): a coordenadora fictícia coordena os anos finais — vê as turmas, as
           matrizes e os relatórios desse segmento e é barrada no Ensino Médio. Pela tela do segmento, como no superior. */
        if ($seg === self::EF) {
            $coord = (int) $c->um("SELECT id FROM adms_usuarios WHERE usuario = 'coord.demo'");
            if ($coord && !$c->um('SELECT id FROM rm_cursos_coordenacao WHERE curso_id = ? AND usuario_id = ? AND situacao = 1', [$curso, $coord])) {
                $adm->req($p . 'cursos/cadastrar-usuario/' . $curso, ['curso_id' => (string) $curso, 'usuario_id' => (string) $coord, 'posicao_id' => '1', 'CadCoordenadorCurso' => 'Salvar']);
                VcDemoPassos::confere($adm, 'indicar a coordenadora dos anos finais');
                $c->um('SELECT id FROM rm_cursos_coordenacao WHERE curso_id = ? AND usuario_id = ? AND situacao = 1', [$curso, $coord])
                    ?: VcDemoPassos::falha('a coordenação dos anos finais não foi gravada: ' . $adm->alerta());
            }
        }
        $c->log("{$seg}: " . count(self::SERIES[$seg]) . ' séries, ' . count(self::COMPONENTES[$seg]) . ' disciplinas por série (' . array_sum(self::COMPONENTES[$seg]) . ' h por ano)'
            . ($seg === self::EF ? '; a coordenadora indicada no segmento.' : '.'));
    }

    /* ------------------------------------------------------------- 3. os anos letivos (bimestres) e as turmas */
    public static function passoAnos(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        foreach ([self::ANO_ANTERIOR, self::ANO] as $ano) {
            if (!$c->um('SELECT id FROM adms_periodos_letivos WHERE ano = ?', [$ano])) {
                $sec->req($p . 'anos-letivos/cadastrar', ['ano' => (string) $ano, 'SalvarAnoLetivo' => 'Salvar']);
                VcDemoPassos::confere($sec, "cadastrar o ano letivo {$ano}");
            }
            if (!$c->um('SELECT ano FROM esc_anos WHERE ano = ?', [$ano])) {
                $sec->req($p . 'escola-anos/abrir', ['ano' => (string) $ano, 'modelo' => 'bimestre']);
                VcDemoPassos::confere($sec, "abrir o ano {$ano} com bimestres");
            }
            self::etapas($c, $ano);
        }
        $c->log('Os anos letivos ' . self::ANO_ANTERIOR . ' e ' . self::ANO . ', em quatro bimestres.');
    }

    public static function passoTurmas(VcDemoContexto $c, array $turmas = self::TURMAS): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        foreach ($turmas as $k => [$ano, $seg, $serie, $nome]) {
            $mod = self::serie($c, $seg, $serie);
            if (!$c->um('SELECT id FROM esc_turmas WHERE ano = ? AND modulo_id = ? AND nome = ?', [$ano, $mod, $nome])) {
                $sec->gravar($p . 'turmas/cadastrar', ['ano' => (string) $ano, 'modulo_id' => $mod, 'nome' => $nome, 'turno_id' => '1', 'sala' => 'Sala ' . (10 + count($c->pdo->query('SELECT id FROM esc_turmas')->fetchAll())), 'capacidade' => '35']);
                VcDemoPassos::confere($sec, "criar a turma {$k}");
            }
            $t = self::turma($c, $k);
            foreach (self::ofertas($c, $t) as $materia => $of) {
                $prof = (int) $c->um('SELECT id FROM adms_usuarios WHERE usuario = ?', [self::professorDe($seg, (string) $materia)]); // Onda 8 (D-84)
                if (!$c->um('SELECT id FROM rm_professores_mat_liga WHERE rm_prof_mat_id = ? AND usuario_id = ? AND situacao = 1', [$of, $prof])) {
                    $sec->gravar($p . 'disciplinas/adiciona-professor/' . $ano, ['rm_prof_mat_id' => $of, 'professores' => [$prof], 'AdicionaProfessor' => 'Salvar']);
                    VcDemoPassos::confere($sec, "o professor da oferta {$of}");
                }
            }
        }
        $c->log(count($turmas) . (count($turmas) === 1 ? ' turma' : ' turmas') . ', cada uma com as disciplinas da série e o professor de cada uma.');
    }

    /* ------------------------------------------------------------- 4. os alunos, o vínculo e a turma */
    /**
     * Onda 8 (D-69): o CPF fictício do aluno da demonstração — claramente de demonstração: começa com 000, seguido da
     * matrícula (000.265.001-XX), e os dois verificadores válidos (o cadastro confere). Sem ele, o atestado não sai.
     */
    public static function cpfDaDemonstracao(int $ra): string
    {
        $n = '000' . str_pad((string) $ra, 6, '0', STR_PAD_LEFT);
        for ($t = 9; $t < 11; $t++) {
            $s = 0;
            for ($i = 0; $i < $t; $i++) {
                $s += (int) $n[$i] * (($t + 1) - $i);
            }
            $n .= ((10 * $s) % 11) % 10;
        }
        return $n;
    }

    /** Onda 8 (D-69): a previsão de conclusão coerente com a série: o fim do ano da última série do segmento. */
    public static function previsaoDaDemonstracao(int $ano, string $seg, string $serie): string
    {
        $series = self::SERIES[$seg];
        $falta = count($series) - 1 - (int) array_search($serie, $series, true);
        return ($ano + $falta) . '-12-15';
    }

    public static function passoAlunos(VcDemoContexto $c, array $turmas = self::TURMAS): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        foreach ($turmas as [$ano, $seg, $serie, , $alunos]) {
            $mod = self::serie($c, $seg, $serie);
            foreach ($alunos as $ra => [$nome, $genero, $perfil]) {
                if (!$c->um('SELECT id FROM col_usuarios WHERE rm = ?', [$ra])) {
                    $sec->gravar($p . 'academicos/cadastrar', ['nome' => $nome, 'rm' => (string) $ra, 'email' => 'aluno.' . $ra . '@exemplo.invalid', 'genero_id' => (string) $genero,
                        'col_sits_usuario_id' => '1', 'CadUsuarioAcad' => 'Cadastrar', 'nasc_data' => ($ano - 11 - (int) substr((string) $serie, 0, 1)) . '-05-10',
                        'cpf' => self::cpfDaDemonstracao($ra)]);
                    VcDemoPassos::confere($sec, "cadastrar o aluno {$ra}");
                }
                $id = self::um($c, 'SELECT id FROM col_usuarios WHERE rm = ?', [$ra], "o aluno {$ra}");
                if (!$c->um('SELECT id FROM rm_matriculas_curso WHERE usuario_id = ? AND curso_id = ?', [$id, self::curso($c, $seg)])) {
                    /* D-104: o aluno de perfil "meio_ano" entra no início do 3º bimestre (o 1º e o 2º ficam sem nota daqui: as de transferência) */
                    $entrada = $perfil === 'meio_ano' ? (string) self::etapas($c, $ano)[self::ETAPA_DE_ENTRADA]['inicio'] : $ano . '-02-02';
                    $sec->gravar($p . "academicos/vincular/{$id}", ['ano' => (string) $ano, 'modulo_id' => $mod, 'forma_ingresso_id' => '1', 'data_ingresso' => $entrada,
                        'polo_id' => '', 'modalidade_id' => '', 'previsao_conclusao' => self::previsaoDaDemonstracao($ano, $seg, $serie)]);
                    VcDemoPassos::confere($sec, "vincular {$ra}");
                }
            }
        }
        $c->log('Os alunos fictícios cadastrados e vinculados ao segmento, na série da turma.');
    }

    /** O link de acesso: cada aluno define a senha (como na faculdade fictícia). */
    public static function passoLinks(VcDemoContexto $c, ?array $ras = null): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        foreach ($ras ?? self::ras(self::TURMAS) as $ra) {
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

    public static function passoEnturmar(VcDemoContexto $c, array $turmas = self::TURMAS): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        foreach ($turmas as $k => [, , , , $alunos]) {
            $t = self::turma($c, $k);
            $faltam = [];
            foreach ($alunos as $ra => $x) {
                if (!$c->um('SELECT id FROM esc_turma_alunos WHERE turma_id = ? AND rm = ? AND saida IS NULL', [$t, (string) $ra])) {
                    $faltam[] = (int) $c->um('SELECT id FROM col_usuarios WHERE rm = ?', [$ra]);
                }
            }
            if (!$faltam) {
                continue;
            }
            $sec->req($p . "turmas/enturmar/{$t}", ['fase' => 'iniciar', 'alunos' => implode(',', $faltam)]);
            for ($i = 0; $i < 20; $i++) {
                $sec->req($p . "turmas/enturmar/{$t}", ['fase' => 'passo']);
                $j = json_decode($sec->html, true);
                if (!empty($j['concluida'])) {
                    break;
                }
            }
            $sec->req($p . "turmas/ver/{$t}"); // o lote concluído sai do banco
            (int) $c->um('SELECT COUNT(*) FROM esc_turma_alunos WHERE turma_id = ? AND saida IS NULL', [$t]) === count($alunos) ?: VcDemoPassos::falha("a turma {$k}: os alunos não entraram.");
        }
        $c->log('Cada aluno na turma dele, em todas as disciplinas (a enturmação em lote).');
    }

    /* ------------------------------------------------------------- 5. as notas (uma avaliação por etapa, por planilha) */
    public static function passoNotas(VcDemoContexto $c, string $k, int $so): void
    {
        [$ano, $seg, , , $alunos] = self::todas()[$k];
        $p = $c->painel();
        $t = self::turma($c, $k);
        $etapas = self::etapas($c, $ano);
        $comps = array_keys(self::COMPONENTES[$seg]);
        foreach (self::ofertas($c, $t) as $materia => $of) {
            $prof = $c->entrar(self::professorDe($seg, (string) $materia)); // Onda 8 (D-84): o professor do componente lança as notas e as faltas dele
            $i = (int) array_search($materia, $comps, true);
            for ($e = $so; $e <= $so; $e++) {
                $nomeAtiv = "Avaliação do {$e}º bimestre";
                if (!$c->um('SELECT id FROM rm_disciplina_atividade WHERE rm_prof_mat_id = ? AND nomeAtividade = ?', [$of, $nomeAtiv])) {
                    $dia = date('Y-m-d', strtotime($etapas[$e]['inicio'] . ' +20 days'));
                    $prof->gravar($p . "atividades/cadastrar/{$of}?tipo=5", ['rm_prof_mat_id' => $of, 'tipo' => 5, 'peso' => 10, 'nomeAtividade' => $nomeAtiv,
                        'descricao' => 'Avaliação fictícia de demonstração', 'entrega_arquivo' => 2, 'data_inicial' => $dia, 'data_final' => $dia, 'CadAtividadeNota' => 'Salvar']);
                    VcDemoPassos::confere($prof, "a avaliação {$e} de {$materia} ({$k})");
                }
                $ativ = self::um($c, 'SELECT id FROM rm_disciplina_atividade WHERE rm_prof_mat_id = ? AND nomeAtividade = ?', [$of, $nomeAtiv], "a avaliação {$e} de {$materia}");
                if ((int) $c->um('SELECT COUNT(*) FROM rm_disciplina_atividade_notas WHERE atividade_id = ? AND nota IS NOT NULL', [$ativ]) > 0) {
                    continue; // as notas já foram lançadas (o passo repetido)
                }
                $csv = "rm;nota\r\n";
                foreach ($alunos as $ra => [, , $perfil]) {
                    if ($perfil === 'troca' && $e > 1) {
                        continue; // trocou de turma depois do 1º bimestre (passoTroca)
                    }
                    if ($perfil === 'meio_ano' && $e < self::ETAPA_DE_ENTRADA) {
                        continue; // D-104: ainda não estava na escola (as notas da etapa são as de transferência)
                    }
                    $csv .= $ra . ';' . str_replace('.', ',', self::nota($perfil, $materia, $i, $e)) . "\r\n";
                }
                $prof->gravar($p . "atividades/upload-notas/{$ativ}", ['arquivo' => new CURLStringFile($csv, 'notas.csv', 'text/csv'), 'UpNotasCsv' => 'Enviar'], true);
                VcDemoPassos::confere($prof, "as notas da avaliação {$e} de {$materia} ({$k})");
                (int) $c->um('SELECT COUNT(*) FROM rm_disciplina_atividade_notas WHERE atividade_id = ? AND nota IS NOT NULL', [$ativ]) > 0
                    ?: VcDemoPassos::falha("as notas da avaliação {$e} de {$materia} ({$k}) não entraram: " . $prof->alerta());
            }
            /* as faltas: o aluno de perfil "falta" falta muito (a frequência no total de horas do ano fica abaixo de 75%) */
            foreach ($alunos as $ra => [, , $perfil]) {
                if ($perfil !== 'falta') {
                    continue;
                }
                for ($e = $so; $e <= $so; $e++) {
                    if (!$c->um('SELECT faltas FROM esc_faltas_etapa WHERE oferta_id = ? AND etapa_id = ? AND rm = ?', [$of, (int) $etapas[$e]['id'], (string) $ra])) {
                        $ch = (int) self::COMPONENTES[$seg][$materia];
                        $prof->gravar($p . "escola-notas/faltas/{$of}", ['etapa_id' => (int) $etapas[$e]['id'], 'faltas' => [$ra => (string) intdiv($ch * 30, 400)]]);
                        VcDemoPassos::confere($prof, "as faltas de {$ra} em {$materia}");
                    }
                }
            }
            /* Onda 8, correção, Parte 3.3 (D-78): no ano corrente, umas poucas faltas em cada etapa (0 a 2, pela matrícula, pelo
               componente e pela etapa: sempre as mesmas), sem ninguém perto da reprovação por falta. A Matemática do
               fundamental (a da professora de demonstração, em todas as turmas dela) fica sem faltas lançadas: é a que a
               demonstração põe na chamada (passoChamada; a troca para a chamada pede a oferta sem falta lançada — D-104, D3). */
            if ($ano === self::ANO && !($seg === self::EF && $materia === 'Matemática')
                && !$c->um('SELECT COUNT(*) FROM esc_faltas_etapa WHERE oferta_id = ? AND etapa_id = ?', [$of, (int) $etapas[$so]['id']])) {
                $faltas = [];
                foreach ($alunos as $ra => [, , $perfil]) {
                    if (($perfil === 'troca' && $so > 1) || ($perfil === 'meio_ano' && $so < self::ETAPA_DE_ENTRADA)) {
                        continue;
                    }
                    $faltas[$ra] = (string) ((((int) $ra % 7) + $i + $so) % 3);
                }
                $prof->gravar($p . "escola-notas/faltas/{$of}", ['etapa_id' => (int) $etapas[$so]['id'], 'faltas' => $faltas]);
                VcDemoPassos::confere($prof, "as faltas do {$so}º bimestre em {$materia} ({$k})");
            }
        }
        $c->log("{$k}, {$so}º bimestre: uma avaliação em cada disciplina, com as notas (por planilha) e as faltas.");
    }

    /* ------------------------------------------------------------- 6. a recuperação e o fechamento das etapas */
    public static function passoFechar(VcDemoContexto $c, string $so): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $ano = self::todas()[$so][0];
        $etapas = self::etapas($c, $ano);
        foreach (self::todas() as $k => [$anoT, $seg, , , $alunos]) {
            if ($k !== $so) {
                continue;
            }
            $prof = $c->entrar(self::PROFESSOR[$seg]);
            $t = self::turma($c, $k);
            foreach (self::ofertas($c, $t) as $materia => $of) {
                for ($e = 1; $e <= self::ETAPAS[$ano]['fechadas']; $e++) {
                    $eid = (int) $etapas[$e]['id'];
                    if ($c->um("SELECT id FROM esc_fechamentos WHERE oferta_id = ? AND etapa_id = ? AND situacao = 'vigente'", [$of, $eid])) {
                        continue;
                    }
                    /* a recuperação de Matemática: a que salva (6,5) e a que não salva (4,5) */
                    if ($materia === 'Matemática') {
                        $rec = [];
                        foreach ($alunos as $ra => [, , $perfil]) {
                            if ($perfil === 'salva' && $e === 1) {
                                $rec[$ra] = '6,5';
                            } elseif ($perfil === 'nao_salva' && $e <= 2) {
                                $rec[$ra] = '4,5';
                            }
                        }
                        if ($rec) {
                            $prof->gravar($p . "escola-notas/recuperacao/{$of}", ['etapa_id' => $eid, 'recuperacao' => $rec]);
                            VcDemoPassos::confere($prof, "a recuperação de Matemática ({$k}, etapa {$e})");
                        }
                    }
                    $sec->gravar($p . "escola-notas/fechar/{$of}", ['etapa_id' => $eid]);
                    VcDemoPassos::confere($sec, "fechar a etapa {$e} de {$materia} ({$k})");
                    $c->um("SELECT id FROM esc_fechamentos WHERE oferta_id = ? AND etapa_id = ? AND situacao = 'vigente'", [$of, $eid]) ?: VcDemoPassos::falha("a etapa {$e} de {$materia} ({$k}) não fechou: " . $sec->alerta());
                }
                /* o ano anterior: encerrado (o fim do ano), com as etapas fechadas */
                if ($ano === self::ANO_ANTERIOR && !$c->um('SELECT encerrada_em FROM rm_professores_materias WHERE id = ?', [$of])) {
                    $sec->gravar($p . "disciplinas/encerrar/{$of}", ['ConfirmarEncerramento' => '1']);
                    VcDemoPassos::confere($sec, "fechar o ano de {$materia} ({$k})");
                    $c->um('SELECT encerrada_em FROM rm_professores_materias WHERE id = ?', [$of]) ?: VcDemoPassos::falha("o ano de {$materia} ({$k}) não fechou: " . $sec->alerta());
                }
            }
        }
        $c->log($ano === self::ANO ? "{$so}: o 1º e o 2º bimestre fechados (com a recuperação); o 3º em andamento." : "{$so}: os 4 bimestres fechados e o ano encerrado.");
    }

    /* ------------------------------------------------------------- 7. a troca de turma, o conselho, o boletim */
    public static function passoTroca(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $a = self::turma($c, '2026-6A');
        $b = self::turma($c, '2026-6B');
        foreach (self::TURMAS['2026-6A'][4] as $ra => [, , $perfil]) {
            if ($perfil !== 'troca' || $c->um('SELECT id FROM esc_turma_alunos WHERE turma_id = ? AND rm = ? AND saida IS NULL', [$b, (string) $ra])) {
                continue;
            }
            $sec->req($p . "turmas/trocar/{$a}", ['usuario_id' => (int) $c->um('SELECT id FROM col_usuarios WHERE rm = ?', [$ra]), 'destino_id' => $b]);
            VcDemoPassos::confere($sec, "trocar {$ra} de turma");
            $c->um('SELECT id FROM esc_turma_alunos WHERE turma_id = ? AND rm = ? AND saida IS NULL', [$b, (string) $ra]) ?: VcDemoPassos::falha("{$ra} não mudou de turma: " . $sec->alerta());
        }
        $c->log('Davi trocou do 6º ano A para o 6º ano B depois do 1º bimestre: as notas foram junto.');
    }

    /**
     * Onda 8 (D-71): o horário da semana de uma turma de 2026, montado pela tela da turma (a secretaria escolhe o
     * componente de cada aula; cada escolha grava). As aulas do turno da turma (o horário padrão da instalação) de
     * segunda a sexta; o número de aulas de cada componente na semana é proporcional à carga horária dele na série (o
     * maior resto completa); a ordem espalha cada componente pelos dias. Repetível: a aula que já tem o componente
     * certo não é gravada de novo.
     */
    public static function passoHorario(VcDemoContexto $c, string $k): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $t = self::turma($c, $k);
        $turno = (int) ($c->um('SELECT turno_id FROM esc_turmas WHERE id = ?', [$t]) ?: 1);
        $aulas = $c->pdo->prepare('SELECT id FROM rm_professores_horario_aula WHERE periodo = ? AND situacao = 1 ORDER BY inicio, id');
        $aulas->execute([$turno]);
        $aulas = array_map('intval', $aulas->fetchAll(PDO::FETCH_COLUMN));
        $aulas ?: VcDemoPassos::falha("o turno da turma {$k} não tem horários de aula ativos");
        $of = $c->pdo->prepare('SELECT o.id, o.ch_considerada FROM esc_turma_ofertas x INNER JOIN rm_professores_materias o ON o.id = x.oferta_id
            LEFT JOIN uni_rh_cursos_disciplinas_grade l ON l.id = o.matriz_id WHERE x.turma_id = ? ORDER BY l.ordem, o.materia');
        $of->execute([$t]);
        $ofertas = $of->fetchAll(PDO::FETCH_KEY_PAIR);
        $ofertas ?: VcDemoPassos::falha("a turma {$k} não tem disciplinas");
        $dias = [2, 3, 4, 5, 6];
        $vagas = count($aulas) * count($dias);
        /* as aulas de cada componente na semana: proporcional à carga horária (pelo menos uma), o maior resto completa */
        $total = array_sum(array_map('floatval', $ofertas)) ?: 1;
        $conta = [];
        $resto = [];
        foreach ($ofertas as $id => $ch) {
            $x = $vagas * (float) $ch / $total;
            $conta[$id] = max(1, (int) floor($x));
            $resto[$id] = $x - floor($x);
        }
        arsort($resto);
        while (array_sum($conta) < $vagas) {
            foreach (array_keys($resto) as $id) {
                if (array_sum($conta) >= $vagas) {
                    break;
                }
                $conta[$id]++;
            }
        }
        while (array_sum($conta) > $vagas) {
            arsort($conta);
            $conta[array_key_first($conta)]--;
        }
        /* a fila: um de cada componente por vez (os de mais aulas primeiro), para espalhar pelos dias */
        arsort($conta);
        $fila = [];
        while (array_sum($conta) > 0) {
            foreach ($conta as $id => $n) {
                if ($n > 0) {
                    $fila[] = (int) $id;
                    $conta[$id]--;
                }
            }
        }
        $atual = $c->pdo->prepare('SELECT h.horario_id, h.dia_semana, h.rm_prof_mat_id FROM rm_professores_horarios h INNER JOIN esc_turma_ofertas x ON x.oferta_id = h.rm_prof_mat_id WHERE x.turma_id = ? AND h.situacao = 1');
        $atual->execute([$t]);
        $tem = [];
        foreach ($atual->fetchAll(PDO::FETCH_NUM) as [$a, $d, $o]) {
            $tem["{$a}-{$d}"] = (int) $o;
        }
        $i = 0;
        foreach ($aulas as $a) {
            foreach ($dias as $d) {
                $o = $fila[$i++];
                if (($tem["{$a}-{$d}"] ?? 0) === $o) {
                    continue;
                }
                $r = json_decode($sec->req($p . "turmas/gravar-horario/{$t}", ['dia' => $d, 'aula' => $a, 'oferta' => $o]), true);
                empty($r['ok']) && VcDemoPassos::falha("o horário da turma {$k} ({$d}, aula {$a}): " . ($r['erro'] ?? 'sem resposta'));
            }
        }
        $c->log("O horário da semana da turma {$k}: {$vagas} aulas, de segunda a sexta.");
    }

    /**
     * Onda 8, correção, acréscimo A (D-79): um documento emitido na montagem, para o relatório "Documentos emitidos" ter o
     * que mostrar — o atestado de matrícula de uma aluna de 2026. Desde a segunda leva da correção (2.1, D-87), pela
     * SECRETARIA, no painel (a tela de emissão da escola, só do atestado; o mesmo modelo e o mesmo código do portal). O
     * boletim não é documento registrado (é impresso da tela do aluno ou do portal), então não entra nesse relatório.
     * Repetível: o atestado já emitido não é emitido de novo.
     */
    public static function passoDocumentos(VcDemoContexto $c): void
    {
        $ra = '265001';
        $mc = self::um($c, 'SELECT mc.id FROM rm_matriculas_curso mc JOIN col_usuarios u ON u.id = mc.usuario_id WHERE u.rm = ? AND mc.situacao_id = 1 ORDER BY mc.id LIMIT 1', [$ra], 'a matrícula de ' . $ra);
        if (!$c->um("SELECT id FROM rm_historico_emissoes WHERE matricula_curso_id = ? AND tipo = 'ATESTADO' LIMIT 1", [$mc])) {
            $sec = $c->entrar('secretaria.demo');
            $sec->req($c->painel() . "historico-emissoes/emitir/{$mc}", ['tipo' => 'ATESTADO', 'EmitirHistorico' => 'Emitir atestado de matrícula']);
            $c->um("SELECT id FROM rm_historico_emissoes WHERE matricula_curso_id = ? AND tipo = 'ATESTADO' AND origem = 'secretaria' LIMIT 1", [$mc])
                ?: VcDemoPassos::falha("o atestado de {$ra} não foi emitido pela secretaria: " . $sec->alerta());
        }
        $c->log('A secretaria emitiu o atestado de matrícula de uma aluna, pelo painel (o relatório de documentos o mostra).');
    }


    /**
     * Onda 8, financeiro, Parte 8 (D-98): o financeiro da demonstração, pela tela da secretaria — as regras (2% de multa,
     * 1% ao mês, sem desconto), um plano por segmento (12 parcelas, a primeira em fevereiro, dia 10), as parcelas de cada
     * aluno de 2026 e os recebimentos: a maioria em dia (dois dias antes do vencimento), alguns com atraso (com os encargos),
     * algumas parcelas vencidas em aberto, as de dezembro e janeiro a vencer; uma baixa (bolsa), um cancelamento, uma
     * renegociação de três parcelas vencidas (D-105: o acordo parcelado com a entrada recebida) e até três atendimentos. Tudo fictício; sempre o mesmo (pela matrícula). Um passo
     * por turma (cada passo bem abaixo do limite de uma requisição). Repetível.
     */
    public const FIN_PLANOS = [self::EF => ['Anuidade do Fundamental (demonstração)', 65000], self::EM => ['Anuidade do Médio (demonstração)', 78000]];

    public static function passoFinanceiro(VcDemoContexto $c, string $k): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        [$ano, $seg, , , $alunos] = (self::TURMAS + self::TURMAS_DEPOIS)[$k]; // D-104: e o 8º ano A
        if ($k === '2026-6A') { // as regras e os planos, uma vez (o primeiro passo)
            $sec->gravar($p . 'fin-regras/salvar', ['multa' => '2', 'juros' => '1', 'desconto' => '0']);
            foreach (self::FIN_PLANOS as [$nome, $valor]) {
                if (!$c->um('SELECT id FROM fin_planos WHERE nome = ?', [$nome])) {
                    $sec->gravar($p . 'fin-planos/salvar', ['nome' => $nome, 'parcelas' => '12', 'valor' => number_format($valor / 100, 2, ',', '.'), 'dia_vencimento' => '10', 'mes_primeira' => '2']);
                    VcDemoPassos::confere($sec, "o plano {$nome}");
                }
            }
        }
        $plano = self::um($c, 'SELECT id FROM fin_planos WHERE nome = ?', [self::FIN_PLANOS[$seg][0]], 'o plano do segmento');
        $hoje = date('Y-m-d');
        /* o número do envio de cada formulário, fixo (a ação e o título ou o aluno): a montagem pela tela e a pela linha
           de comando ficam iguais na régua; montar de novo não grava duas vezes (o mesmo envio é recusado) */
        $envio = static fn(string $chave): string => 'demo-fin-' . $chave;
        foreach ($alunos as $ra => [$nomeAluno, , $perfil]) {
            $aluno = (int) $c->um('SELECT id FROM col_usuarios WHERE rm = ?', [$ra]);
            $mc = (int) $c->um('SELECT id FROM rm_matriculas_curso WHERE usuario_id = ? AND situacao_id = 1 ORDER BY id DESC LIMIT 1', [$aluno]);
            $entrada = $perfil === 'meio_ano' ? (string) self::etapas($c, (int) $ano)[self::ETAPA_DE_ENTRADA]['inicio'] : ''; // D-104
            if (!$aluno || !$mc) {
                continue;
            }
            self::financeiroDoAno($c, $sec, $aluno, $nomeAluno, (int) $ano, (int) $ra % 100); // D-99: todo aluno com título tem o financeiro do ano
            if (!$c->um('SELECT COUNT(*) FROM fin_titulos WHERE matricula_curso_id = ? AND plano_id = ?', [$mc, $plano])) {
                $sec->gravar($p . "financeiro/aplicar-plano/{$aluno}", ['matricula_curso_id' => (string) $mc, 'plano_id' => (string) $plano]);
                VcDemoPassos::confere($sec, "as parcelas de {$ra}");
            }
            $st = $c->pdo->prepare("SELECT id, parcela, vencimento, situacao FROM fin_titulos WHERE matricula_curso_id = ? AND plano_id = ? ORDER BY parcela");
            $st->execute([$mc, $plano]);
            $parc = $st->fetchAll(PDO::FETCH_ASSOC);
            if ($entrada !== '') { // D-104: quem entrou no meio do ano não paga as parcelas de antes da entrada (canceladas, com o motivo)
                foreach ($parc as $i => $t) {
                    if ($t['vencimento'] < $entrada && $t['situacao'] === 'aberto') {
                        $sec->gravar($p . "financeiro/cancelar/{$t['id']}", ['motivo' => 'Entrou no 3º bimestre: as parcelas de antes da entrada não são cobradas (demonstração).', 'envio' => $envio("cancelar-{$t['id']}")]);
                        $parc[$i]['situacao'] = 'cancelado';
                    }
                }
            }
            if ((int) $ra === self::FIN_FICHA_RA) { // a aluna da ficha: títulos em todas as situações, em dois anos (D-99)
                self::financeiroDaFicha($c, $sec, $aluno, $parc, $envio);
                continue;
            }
            $atrasa = (int) $ra % 5 === 1;              // dois recebimentos com atraso (5 dias)
            $deve = in_array((int) $ra % 5, [2], true);  // as duas últimas vencidas, em aberto
            $renegocia = (int) $ra === 265033;           // as três últimas vencidas, renegociadas
            $vencidas = array_values(array_filter($parc, static fn($t) => $t['vencimento'] < $hoje));
            $n = count($vencidas);
            foreach ($vencidas as $i => $t) {
                if ($t['situacao'] !== 'aberto') {
                    continue;
                }
                if (($deve && $i >= $n - 2) || ($renegocia && $i >= $n - 3)) {
                    continue; // fica vencida (ou vai para a renegociação)
                }
                if ((int) $ra === 265012 && (int) $t['parcela'] === 2) {
                    $sec->gravar($p . "financeiro/baixar/{$t['id']}", ['motivo' => 'Bolsa de 100% concedida neste mês pela direção (demonstração).', 'envio' => $envio("baixar-{$t['id']}")]);
                    continue;
                }
                $atraso = $atrasa && in_array((int) $t['parcela'], [3, 6], true);
                $data = date('Y-m-d', strtotime($t['vencimento'] . ($atraso ? ' +5 days' : ' -2 days')));
                $data = min($data, $hoje);
                $forma = ['pix', 'dinheiro', 'cartao'][(int) $t['parcela'] % 3];
                $sec->gravar($p . "financeiro/receber/{$aluno}", ['titulos' => [(string) $t['id']], 'data' => $data, 'modo' => 'unica',
                    'formas' => [self::formaDaDemonstracao($forma, (int) $ra, (int) $t['id'], $data)],
                    'observacao' => $atraso ? 'Pagou com atraso (demonstração).' : '', 'envio' => $envio("receber-{$t['id']}")]);
            }
            if ((int) $ra === 265024) { // o cancelamento: a parcela de janeiro (a vencer)
                $ult = end($parc);
                if ($ult && $ult['situacao'] === 'aberto') {
                    $sec->gravar($p . "financeiro/cancelar/{$ult['id']}", ['motivo' => 'Desconto de irmão: a última parcela foi dispensada (demonstração).', 'envio' => $envio("cancelar-{$ult['id']}")]);
                }
            }
            if ($renegocia && !$c->um("SELECT id FROM fin_renegociacoes WHERE aluno_id = ? AND situacao = 'vigente'", [$aluno])) {
                /* D-105: o acordo parcelado, pela tela de Renegociar — R$ 50,00 de desconto (com a justificativa), a entrada
                   de 20% hoje e três parcelas no dia 15; a à vista, com 5% de desconto, sem interesse da família */
                $ids = array_map(static fn($t) => (string) $t['id'], array_slice($vencidas, -3));
                $sec->gravar($p . "financeiro/renegociar/{$aluno}", ['titulos' => $ids, 'data' => $hoje, 'desconto' => '50,00', 'justificativa' => 'Desconto combinado com a direção para fechar o acordo (demonstração).',
                    'p_entrada_tipo' => 'pct', 'p_entrada' => '20', 'p_entrada_vencimento' => $hoje, 'p_parcelas' => '3', 'p_dia' => '15', 'p_interesse' => '1',
                    'v_desconto_tipo' => 'pct', 'v_desconto' => '5', 'v_vencimento' => $hoje, 'v_interesse' => '0', 'motivo_id' => '41',
                    'observacao' => 'A mãe prefere parcelar e paga a entrada hoje (demonstração).', 'acao' => 'parcelada', 'envio' => $envio("renegociar-{$aluno}")]);
                $c->um("SELECT id FROM fin_renegociacoes WHERE aluno_id = ? AND situacao = 'vigente'", [$aluno]) ?: VcDemoPassos::falha("o acordo de {$ra} não foi efetivado: " . $sec->alerta());
                $sec->gravar($p . "financeiro/atendimento/{$aluno}", ['data' => $hoje, 'texto' => 'A mãe ligou: combinamos o acordo das três parcelas em atraso, com entrada e mais três vezes, e R$ 50,00 de desconto (demonstração).', 'envio' => $envio("atendimento-renegociacao-{$aluno}")]);
            }
            /* a entrada do acordo, recebida (Pix) */
            $entrada = $renegocia ? (int) $c->um("SELECT t.id FROM fin_titulos t JOIN fin_renegociacoes r ON r.id = t.renegociacao_id WHERE r.aluno_id = ? AND r.situacao = 'vigente' AND t.parcela = 1 AND t.situacao = 'aberto'", [$aluno]) : 0;
            if ($entrada) {
                $sec->gravar($p . "financeiro/receber/{$aluno}", ['titulos' => [(string) $entrada], 'data' => $hoje, 'modo' => 'unica', 'formas' => [self::formaDaDemonstracao('pix', (int) $ra, $entrada, $hoje)],
                    'observacao' => 'A entrada do acordo (demonstração).', 'envio' => $envio("receber-entrada-{$aluno}")]);
            }
            /* os atendimentos da demonstração, poucos (o pedido do responsável: dois ou três): os de cobrança param em dois,
               e a renegociação acrescenta o dela */
            if ($deve && (int) $c->um("SELECT COUNT(*) FROM fin_operacoes WHERE tipo = 'atendimento'") < 2
                && !$c->um("SELECT id FROM fin_operacoes WHERE aluno_id = ? AND tipo = 'atendimento'", [$aluno])) {
                $sec->gravar($p . "financeiro/atendimento/{$aluno}", ['data' => $hoje, 'texto' => 'Avisamos a família das duas parcelas em atraso; o pai disse que paga até o fim do mês (demonstração).', 'envio' => $envio("atendimento-{$aluno}")]);
            }
        }
        $c->log("{$k}: o financeiro de cada aluno (as parcelas do plano, os recebimentos, os atrasos e os vencidos).");
    }

    /**
     * D-99: o celular e o endereço fictícios da pessoa responsável nº $n — o DDD 00 não existe (o número é claramente de
     * demonstração), e o endereço é de uma cidade que não existe.
     */
    public static function contatoDaDemonstracao(int $n): array
    {
        return ['celular' => '0090000' . sprintf('%04d', $n), 'cpf' => self::cpfDaDemonstracao(990000 + $n), 'endereco' => 'Rua dos Exemplos', 'numero' => (string) (100 + $n), 'complemento' => $n % 3 === 0 ? 'casa 2' : '',
            'bairro' => 'Bairro Modelo', 'cidade' => 'Cidade Fictícia', 'uf' => 'SP', 'cep' => '00000-' . sprintf('%03d', $n)];
    }

    /** D-99: as mães fictícias dos alunos que ainda não têm o responsável financeiro do ano (o sobrenome é o do aluno). */
    private const MAES = ['Rita', 'Vera', 'Lúcia', 'Marta', 'Célia', 'Teresa', 'Nair', 'Irene', 'Sílvia', 'Glória', 'Neide', 'Odete', 'Paula', 'Regina', 'Sueli'];

    /**
     * D-99: todo aluno com título tem o responsável financeiro no ano. Quem ainda não tem (a demonstração dos responsáveis
     * cuida de poucos) ganha uma mãe fictícia — cadastrada com o celular e o endereço de demonstração, vinculada e
     * designada financeira no ano, pela tela da secretaria. Repetível.
     */
    private static function financeiroDoAno(VcDemoContexto $c, $sec, int $aluno, string $nomeAluno, int $ano, int $k): void
    {
        $p = $c->painel();
        if ($c->um("SELECT id FROM esc_responsavel_designacoes WHERE aluno_id = ? AND ano = ? AND papel = 'financeiro' AND fim IS NULL", [$aluno, $ano])) {
            return;
        }
        $partes = preg_split('/\s+/', trim($nomeAluno));
        $nome = self::MAES[$k % count(self::MAES)] . ' ' . implode(' ', array_slice($partes, 1));
        $acha = static fn() => (int) $c->um('SELECT r.id FROM esc_responsaveis r JOIN esc_responsavel_alunos ra ON ra.responsavel_id = r.id WHERE r.nome = ? AND ra.aluno_id = ?', [$nome, $aluno]);
        $pid = $acha();
        if (!$pid) {
            $sec->gravar($p . 'responsaveis/cadastrar', array_merge(['nome' => $nome, 'email' => '', 'aluno' => (string) $aluno, 'parentesco' => 'Mãe'], self::contatoDaDemonstracao(10 + $k)));
            $pid = $acha() ?: VcDemoPassos::falha("a responsável {$nome} não foi cadastrada: " . $sec->alerta());
        }
        $sec->gravar($p . 'responsaveis/designar', ['aluno' => (string) $aluno, 'ano' => (string) $ano, 'papel' => 'financeiro', 'responsavel_id' => (string) $pid,
            'parentesco' => 'Mãe', 'data' => $ano . '-02-02', 'motivo' => '']);
        $c->um("SELECT id FROM esc_responsavel_designacoes WHERE aluno_id = ? AND ano = ? AND papel = 'financeiro' AND fim IS NULL", [$aluno, $ano])
            ?: VcDemoPassos::falha("o financeiro de {$nomeAluno} em {$ano} não foi designado: " . $sec->alerta());
    }

    /** D-99: a aluna cuja ficha financeira mostra tudo (dois anos letivos, todas as situações, os recibos). */
    public const FIN_FICHA_RA = 265022;

    /** Os dados de uma forma de pagamento da demonstração (fictícios: o Pix leva o CPF de demonstração, 000.…). */
    public static function formaDaDemonstracao(string $forma, int $ra, int $titulo, string $data, string $valor = ''): array
    {
        $f = ['forma' => $forma, 'valor' => $valor];
        if ($forma === 'pix') {
            $f['documento'] = self::cpfDaDemonstracao($ra);
        } elseif ($forma === 'cartao') {
            $f += ['cartao_tipo' => 'credito', 'cartao_parcelas' => '1', 'bandeira_id' => '1', 'cartao_final' => '4242', 'cartao_documento' => 'DEMO-' . $titulo, 'cartao_autorizacao' => 'AUT' . $titulo];
        }
        return $f;
    }

    /**
     * D-99: a ficha financeira da aluna da demonstração, para ser julgada: em 2026, duas parcelas recebidas (Pix e
     * dinheiro), duas renegociadas (o acordo em duas parcelas), uma baixada, uma recebida no cartão, desfeita (o recibo
     * estornado) e recebida de novo por Pix com os encargos, duas recebidas num recibo só (Pix e dinheiro, com
     * desconto e a justificativa), duas vencidas em aberto, uma a vencer e a última cancelada; em 2025, uma mensalidade
     * recebida e o material em aberto (a pendência do ano anterior). Pela tela da secretaria, com os envios fixos. Repetível.
     */
    private static function financeiroDaFicha(VcDemoContexto $c, $sec, int $aluno, array $parc, callable $envio): void
    {
        $p = $c->painel();
        $hoje = date('Y-m-d');
        $ra = self::FIN_FICHA_RA;
        $n = [];
        foreach ($parc as $t) {
            $n[(int) $t['parcela']] = (int) $t['id'];
        }
        $sit = static fn(int $id): string => (string) $c->um('SELECT situacao FROM fin_titulos WHERE id = ?', [$id]);
        $venc = static fn(int $id): string => (string) $c->um('SELECT vencimento FROM fin_titulos WHERE id = ?', [$id]);
        $dia = static fn(string $v, int $dias): string => min(date('Y-m-d', strtotime("{$v} {$dias} days")), $hoje);
        $receber = static function (array $ids, string $data, array $formas, string $chave, array $extra = []) use ($sec, $p, $aluno, $envio): void {
            $sec->gravar($p . "financeiro/receber/{$aluno}", ['titulos' => array_map('strval', $ids), 'data' => $data, 'modo' => count($formas) > 1 ? 'multiplo' : 'unica',
                'formas' => $formas, 'envio' => $envio($chave)] + $extra);
        };
        $um = static function (int $id, string $forma, int $dias, string $chave) use ($sit, $venc, $dia, $receber, $ra): void {
            if ($id && $sit($id) === 'aberto') {
                $d = $dia($venc($id), $dias);
                $receber([$id], $d, [self::formaDaDemonstracao($forma, $ra, $id, $d)], $chave);
            }
        };
        /* 1 e 2: recebidas em dia */
        $um($n[1] ?? 0, 'pix', -2, 'receber-' . ($n[1] ?? 0));
        $um($n[2] ?? 0, 'dinheiro', -2, 'receber-' . ($n[2] ?? 0));
        /* 3 e 4: renegociadas, num acordo de duas parcelas */
        if (isset($n[3], $n[4]) && $sit($n[3]) === 'aberto' && $sit($n[4]) === 'aberto') {
            /* D-105: pela tela de Renegociar, a condição parcelada sem entrada, em duas parcelas no dia 15 */
            $sec->gravar($p . "financeiro/renegociar/{$aluno}", ['titulos' => [(string) $n[3], (string) $n[4]], 'data' => $hoje, 'p_entrada' => '', 'p_parcelas' => '2', 'p_dia' => '15',
                'v_vencimento' => $hoje, 'motivo_id' => '41', 'acao' => 'parcelada', 'envio' => $envio("renegociar-{$aluno}")]);
        }
        /* 5: baixada */
        if (isset($n[5]) && $sit($n[5]) === 'aberto') {
            $sec->gravar($p . "financeiro/baixar/{$n[5]}", ['motivo' => 'Bolsa integral concedida neste mês pela direção (demonstração).', 'envio' => $envio("baixar-{$n[5]}")]);
        }
        /* 6: recebida no cartão, desfeita (o recibo fica estornado) e recebida de novo, por Pix, com os encargos */
        if (isset($n[6])) {
            $estornado = $c->um("SELECT r.id FROM fin_recibos r JOIN fin_recibo_titulos l ON l.recibo_id = r.id WHERE l.titulo_id = ? AND r.situacao = 'estornado'", [$n[6]]);
            if (!$estornado) {
                $um($n[6], 'cartao', -2, 'receber-' . $n[6]);
                $rec = (int) $c->um("SELECT r.id FROM fin_recibos r JOIN fin_recibo_titulos l ON l.recibo_id = r.id WHERE l.titulo_id = ? AND r.situacao = 'valido'", [$n[6]]);
                if ($rec) {
                    $c->entrar('supervisao.demo')->gravar($p . "financeiro/desfazer-recebimento/{$rec}", ['data' => $hoje, 'motivo_id' => '2',
                        'justificativa' => 'A secretaria lançou no cartão; a família tinha pago por Pix (demonstração).', 'envio' => $envio('estornar-' . $n[6])]);
                }
            }
            $um($n[6], 'pix', 3, 'receber-de-novo-' . $n[6]);
        }
        /* 7 e 8: um recibo só, em duas formas (Pix e dinheiro), com R$ 10,00 de desconto e a justificativa */
        if (isset($n[7], $n[8]) && $sit($n[7]) === 'aberto' && $sit($n[8]) === 'aberto') {
            $d = $dia($venc($n[8]), -2);
            $devido = 0;
            foreach ([$n[7], $n[8]] as $id) { // a conta do servidor: os encargos na data
                $devido += (int) (json_decode($sec->req($p . "financeiro/encargos/{$id}?data={$d}"), true)['total'] ?? 0);
            }
            $resto = $devido - 1000 - 50000;
            $receber([$n[7], $n[8]], $d, [self::formaDaDemonstracao('pix', $ra, $n[7], $d, '500,00'),
                self::formaDaDemonstracao('dinheiro', $ra, $n[8], $d, number_format($resto / 100, 2, ',', '.'))], "receber-{$n[7]}-{$n[8]}",
                ['desconto' => '10,00', 'justificativa' => 'Desconto combinado com a direção pelo atraso de agosto (demonstração).',
                    'observacao' => 'A mãe pagou uma parte por Pix e o resto em dinheiro (demonstração).']);
        }
        /* 9 e 10 ficam vencidas em aberto, com uma proposta de acordo salva (D-105: a família vai pensar — parcelada, com
           R$ 100,00 de entrada e duas parcelas no dia 20, ou à vista com 10% de desconto); 11 a vencer; 12, a de janeiro, cancelada */
        if (isset($n[9], $n[10]) && $sit($n[9]) === 'aberto' && $sit($n[10]) === 'aberto') {
            $sec->gravar($p . "financeiro/renegociar/{$aluno}", ['titulos' => [(string) $n[9], (string) $n[10]], 'data' => $hoje, 'p_entrada_tipo' => 'reais', 'p_entrada' => '100,00',
                'p_entrada_vencimento' => $hoje, 'p_parcelas' => '2', 'p_dia' => '20', 'v_desconto_tipo' => 'pct', 'v_desconto' => '10', 'v_vencimento' => $hoje, 'motivo_id' => '41',
                'observacao' => 'A família vai pensar e responde ainda hoje (demonstração).', 'acao' => 'salvar', 'envio' => $envio("proposta-{$aluno}")]);
            $c->um("SELECT id FROM fin_propostas WHERE aluno_id = ? AND situacao = 'salva'", [$aluno]) ?: VcDemoPassos::falha("a proposta de {$ra} não foi salva: " . $sec->alerta());
        }
        if (isset($n[12]) && $sit($n[12]) === 'aberto') {
            $sec->gravar($p . "financeiro/cancelar/{$n[12]}", ['motivo' => 'A família avisou a saída no fim do ano: a parcela de janeiro foi dispensada (demonstração).', 'envio' => $envio("cancelar-{$n[12]}")]);
        }
        /* o ano anterior: a mensalidade de dezembro recebida e o material em aberto (a pendência de antes) */
        $anoAnt = self::ANO_ANTERIOR;
        foreach ([['Mensalidade de dezembro (demonstração)', '620,00', "{$anoAnt}-12-10", 'dinheiro'], ['Material didático (demonstração)', '380,00', "{$anoAnt}-11-10", null]] as [$desc, $valor, $v, $forma]) {
            $id = (int) $c->um('SELECT id FROM fin_titulos WHERE aluno_id = ? AND ano = ? AND descricao = ?', [$aluno, $anoAnt, $desc]);
            if (!$id) {
                $sec->gravar($p . "financeiro/avulso/{$aluno}", ['descricao' => $desc, 'valor' => $valor, 'vencimento' => $v, 'ano' => (string) $anoAnt]);
                $id = (int) $c->um('SELECT id FROM fin_titulos WHERE aluno_id = ? AND ano = ? AND descricao = ?', [$aluno, $anoAnt, $desc]);
            }
            if ($forma) {
                $um($id, $forma, -2, 'receber-' . $id);
            }
        }
        /* sem atendimento próprio: a demonstração tem poucos (até três, o pedido do responsável), e os de cobrança já chegaram ao limite */
        /* a conferência: a ficha tem o que precisa ter */
        $conta = static fn(string $s): int => (int) $c->um('SELECT COUNT(*) FROM fin_titulos WHERE aluno_id = ? AND situacao = ?', [$aluno, $s]);
        foreach (['recebido' => 6, 'renegociado' => 2, 'baixado' => 1, 'cancelado' => 1] as $s => $min) {
            if ($conta($s) < $min) {
                VcDemoPassos::falha("a ficha de {$ra}: esperava ao menos {$min} título(s) {$s} e há " . $conta($s) . ': ' . $sec->alerta());
            }
        }
        if (!$c->um("SELECT id FROM fin_recibos WHERE aluno_id = ? AND situacao = 'estornado'", [$aluno])
            || !$c->um("SELECT r.id FROM fin_recibos r WHERE r.aluno_id = ? AND r.situacao = 'valido' AND (SELECT COUNT(*) FROM fin_recibo_formas f WHERE f.recibo_id = r.id) = 2", [$aluno])) {
            VcDemoPassos::falha("a ficha de {$ra}: faltou o recibo estornado ou o recibo em duas formas: " . $sec->alerta());
        }
    }

    public static function passoConselho(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $t = self::turma($c, '2025-9A');
        foreach (self::TURMAS['2025-9A'][4] as $ra => [, , $perfil]) {
            if ($perfil !== 'conselho' || $c->um("SELECT id FROM esc_conselho WHERE turma_id = ? AND rm = ? AND decisao = 'aprovar'", [$t, (string) $ra])) {
                continue;
            }
            $sec->req($p . "escola-conselho/registrar/{$t}", ['etapa_id' => '0', 'rm' => (string) $ra, 'decisao' => 'aprovar',
                'observacao' => 'O conselho considerou a evolução no segundo semestre e o desempenho nas outras disciplinas (demonstração).']);
            VcDemoPassos::confere($sec, "o conselho de classe de {$ra}");
        }
        $c->log('O conselho de classe aprovou Sara (reprovada em Matemática): a nota não mudou, a situação sim.');
    }

    public static function passoBoletim(VcDemoContexto $c): void
    {
        /* D-100: pela tela "Liberação do boletim", como a escola faz (quem libera é a supervisão de secretaria): as etapas
           fechadas no portal; no ano anterior (encerrado), também a média final e a situação final; no ano corrente, as
           duas ficam ocultas */
        $sup = $c->entrar('supervisao.demo');
        $p = $c->painel();
        foreach ([self::ANO_ANTERIOR => [4, true], self::ANO => [self::ETAPAS[self::ANO]['fechadas'], false]] as $ano => [$n, $finais]) {
            $item = [];
            foreach (self::etapas($c, $ano) as $ordem => $e) {
                if ($ordem <= $n) {
                    $item['etapa:' . (int) $e['id']] = ['exibir' => '1', 'a_partir' => ''];
                }
            }
            if ($finais) {
                $item['media_final:0'] = ['exibir' => '1', 'a_partir' => ''];
                $item['situacao_final:0'] = ['exibir' => '1', 'a_partir' => ''];
            }
            $sup->gravar($p . 'escola-liberacao/salvar', ['ano' => (string) $ano, 'item' => $item]);
            VcDemoPassos::confere($sup, "a liberação do boletim de {$ano}");
            $liberadas = (int) $c->um("SELECT COUNT(*) FROM esc_boletim_liberacao WHERE ano = ? AND item = 'etapa' AND exibir = 1", [$ano]);
            if ($liberadas !== $n) {
                throw new \RuntimeException("A liberação do boletim de {$ano} não gravou: {$liberadas} de {$n} etapas.");
            }
        }
        $c->log('O boletim das etapas fechadas liberado no portal (a média final e a situação final do ano corrente ficam ocultas).');
    }

    /** Os alunos da demonstração da escola (os RA), para a limpeza e o cartão "Conheça o sistema". */
    /**
     * Os responsáveis fictícios (Onda 7): e-mail => [nome, o acesso (senha | pendente | sem_convite)]. Os casos: uma
     * pessoa com dois filhos (financeira e titular de um, só titular do outro); um aluno com duas pessoas (a financeira e
     * a titular); o acesso acadêmico adicional, com o motivo; o papel que mudou de um ano para o outro (o financeiro de
     * 2025 não é o de 2026); um responsável sem convite e um com o convite pendente.
     */
    public const RESPONSAVEIS = [
        'helena.ribeiro@exemplo.invalid' => ['Helena Exemplo Ribeiro', 'senha'],
        'rogerio.sa@exemplo.invalid'     => ['Rogério Fictício Sá', 'senha'],
        'dora.ribeiro@exemplo.invalid'   => ['Dora Modelo Ribeiro', 'senha'],
        'caio.dias@exemplo.invalid'      => ['Caio Inventado Dias', 'sem_convite'],
        'lia.dias@exemplo.invalid'       => ['Lia Amostra Dias', 'pendente'],
    ];
    /** Os papéis: [e-mail, RA, ano, papel, parentesco, motivo do adicional]. */
    public const PAPEIS = [
        ['helena.ribeiro@exemplo.invalid', 265032, self::ANO, 'financeiro', 'Mãe', ''],
        ['helena.ribeiro@exemplo.invalid', 265032, self::ANO, 'titular', 'Mãe', ''],
        ['helena.ribeiro@exemplo.invalid', 265022, self::ANO, 'titular', 'Mãe', ''],
        ['rogerio.sa@exemplo.invalid', 265022, self::ANO, 'financeiro', 'Pai', ''],
        ['helena.ribeiro@exemplo.invalid', 265022, self::ANO_ANTERIOR, 'financeiro', 'Mãe', ''],
        ['dora.ribeiro@exemplo.invalid', 265032, self::ANO, 'adicional', 'Avó', 'A avó busca a neta na escola e acompanha as tarefas (exemplo).'],
        ['lia.dias@exemplo.invalid', 265033, self::ANO, 'financeiro', 'Mãe', ''],
        ['caio.dias@exemplo.invalid', 265033, self::ANO, 'titular', 'Pai', ''],
    ];

    /* ------------------------------------------------------------- 9. os responsáveis (Onda 7) */
    public static function passoResponsaveis(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $n = 0;
        foreach (self::RESPONSAVEIS as $email => [$nome]) {
            $n++;
            if (!$c->um('SELECT id FROM esc_responsaveis WHERE email = ?', [$email])) {
                /* D-99: com o celular e o endereço fictícios (o WhatsApp e o "Ver endereço" da ficha financeira) */
                $sec->gravar($p . 'responsaveis/cadastrar', array_merge(['nome' => $nome, 'email' => $email], self::contatoDaDemonstracao($n)));
                VcDemoPassos::confere($sec, "cadastrar o responsável {$nome}");
            }
            $c->um('SELECT id FROM esc_responsaveis WHERE email = ?', [$email]) ?: VcDemoPassos::falha("o responsável {$nome} não foi cadastrado: " . $sec->alerta());
        }
        foreach (self::PAPEIS as [$email, $ra, $ano, $papel, $parentesco, $motivo]) {
            $pid = (int) $c->um('SELECT id FROM esc_responsaveis WHERE email = ?', [$email]);
            $aid = self::um($c, 'SELECT id FROM col_usuarios WHERE rm = ?', [$ra], "o aluno {$ra}");
            if ($c->um('SELECT id FROM esc_responsavel_designacoes WHERE responsavel_id = ? AND aluno_id = ? AND ano = ? AND papel = ? AND fim IS NULL', [$pid, $aid, $ano, $papel])) {
                continue;
            }
            if ($papel === 'adicional') {
                $sec->gravar($p . 'responsaveis/liberar', ['aluno' => (string) $aid, 'ano' => (string) $ano, 'responsavel_id' => (string) $pid, 'parentesco' => $parentesco,
                    'motivo' => $motivo, 'referencia' => 'Termo assinado pela família (exemplo)']);
            } else {
                $sec->gravar($p . 'responsaveis/designar', ['aluno' => (string) $aid, 'ano' => (string) $ano, 'papel' => $papel, 'responsavel_id' => (string) $pid,
                    'parentesco' => $parentesco, 'data' => $ano . '-02-02', 'motivo' => '']);
            }
            VcDemoPassos::confere($sec, "o papel {$papel} de {$email} em {$ra} ({$ano})");
            $c->um('SELECT id FROM esc_responsavel_designacoes WHERE responsavel_id = ? AND aluno_id = ? AND ano = ? AND papel = ? AND fim IS NULL', [$pid, $aid, $ano, $papel])
                ?: VcDemoPassos::falha("o papel {$papel} de {$email} em {$ra} ({$ano}) não foi gravado: " . $sec->alerta());
        }
        $c->log('Os responsáveis fictícios cadastrados e designados: o financeiro e o titular de cada ano, um acesso adicional com o motivo, e o financeiro que mudou de 2025 para 2026.');
    }

    /** O convite: quem tem a senha da demonstração a cria pelo link (como a família faria); um fica com o convite pendente. */
    public static function passoConvites(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        foreach (self::RESPONSAVEIS as $email => [$nome, $acesso]) {
            if ($acesso === 'sem_convite') {
                continue;
            }
            $pid = (int) $c->um('SELECT id FROM esc_responsaveis WHERE email = ?', [$email]);
            if ($acesso === 'senha' && (string) $c->um('SELECT IFNULL(senha, \'\') FROM esc_responsaveis WHERE id = ?', [$pid]) !== '') {
                continue;
            }
            if ($acesso === 'pendente' && $c->um('SELECT id FROM esc_responsavel_convites WHERE responsavel_id = ? AND usado_em IS NULL AND cancelado_em IS NULL AND expira_em > NOW()', [$pid])) {
                continue;
            }
            $sec->req($p . 'responsaveis/convidar', ['responsavel_id' => (string) $pid]);
            VcDemoPassos::confere($sec, "gerar o convite de {$nome}");
            if ($acesso === 'pendente') {
                continue;
            }
            preg_match('#id="linkConvite" value="([^"]+)"#', $sec->html, $m) ?: VcDemoPassos::falha("o link do convite de {$nome} não apareceu: " . $sec->alerta());
            preg_match('/#c=([a-f0-9]{48})/', html_entity_decode($m[1]), $cod) ?: VcDemoPassos::falha("o link de {$nome} sem o código.");
            $senha = (string) $c->senhas[$email];
            $fam = new VcDemoNavegador();
            $fam->gravar($c->portal() . 'convite/definir', ['codigo' => $cod[1], 'senha' => $senha, 'senha_repetido' => $senha]);
            VcDemoPassos::confere($fam, "{$nome} criar a senha pelo convite");
            (string) $c->um('SELECT IFNULL(senha, \'\') FROM esc_responsaveis WHERE id = ?', [$pid]) !== '' ?: VcDemoPassos::falha("{$nome}: a senha não foi criada pelo convite.");
        }
        $c->log('Os convites: três responsáveis criaram a senha pelo link; um ficou com o convite pendente e um ainda sem convite.');
    }

    /**
     * Correção da escola, Parte 5 (D-104): as notas de transferência do aluno que entrou no meio do ano — a nota e as faltas
     * de cada disciplina nas etapas antes da entrada, trazidas da escola de origem (fictícia), pela tela da secretaria
     * (Matrículas → Notas de transferência). Repetível: o que já está lançado não é lançado de novo.
     */
    public static function passoTransferencia(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        foreach (self::TURMAS_DEPOIS as $k => [$ano, $seg, , , $alunos]) {
            $comps = array_keys(self::COMPONENTES[$seg]);
            $etapas = self::etapas($c, $ano);
            $ofertas = self::ofertas($c, self::turma($c, $k));
            foreach ($alunos as $ra => [, , $perfil]) {
                if ($perfil !== 'meio_ano') {
                    continue;
                }
                $aluno = self::um($c, 'SELECT id FROM col_usuarios WHERE rm = ?', [$ra], "o aluno {$ra}");
                if ($c->um('SELECT COUNT(*) FROM esc_notas_transferencia WHERE aluno_id = ?', [$aluno])) {
                    continue;
                }
                $nota = [];
                $faltas = [];
                foreach ($ofertas as $materia => $of) {
                    $i = (int) array_search($materia, $comps, true);
                    for ($e = 1; $e < self::ETAPA_DE_ENTRADA; $e++) {
                        $eid = (int) $etapas[$e]['id'];
                        $nota[$of][$eid] = str_replace('.', ',', self::nota('medio', (string) $materia, $i, $e));
                        $faltas[$of][$eid] = (string) (($i + $e) % 3);
                    }
                }
                $sec->gravar($p . "escola-transferencia/salvar/{$aluno}", ['ano' => (string) $ano, 'nota' => $nota, 'faltas' => $faltas,
                    'escola_origem' => self::ESCOLA_ORIGEM, 'documento' => 'Histórico parcial da escola de origem (exemplo)']);
                VcDemoPassos::confere($sec, "as notas de transferência de {$ra}");
                $c->um('SELECT COUNT(*) FROM esc_notas_transferencia WHERE aluno_id = ?', [$aluno]) ?: VcDemoPassos::falha("as notas de transferência de {$ra} não entraram: " . $sec->alerta());
            }
        }
        $c->log('Vítor entrou no início do 3º bimestre, vindo de outra escola: as notas e as faltas do 1º e do 2º bimestre são as de transferência.');
    }

    /**
     * Correção da escola, Parte 5 (D-104, D3): a Matemática de cada turma da professora de demonstração (o fundamental)
     * passa a fazer a chamada, e três aulas do 3º bimestre (aberto) ficam lançadas, cada uma com um ausente — o cartão
     * "Conheça o sistema" manda o professor fazer a chamada de uma aula. Pela tela da professora. Repetível.
     */
    public static function passoChamada(VcDemoContexto $c): void
    {
        $prof = $c->entrar(self::PROFESSOR[self::EF]);
        $p = $c->painel();
        $etapa = self::etapas($c, self::ANO)[self::ETAPA_DE_ENTRADA];
        /* três dias de semana do 3º bimestre (a 3ª, a 4ª e a 5ª semana), nunca depois de hoje */
        $dias = [];
        foreach ([15, 22, 29] as $mais) {
            $d = new \DateTimeImmutable($etapa['inicio'] . " +{$mais} days");
            while ((int) $d->format('N') > 5) {
                $d = $d->modify('+1 day');
            }
            if ($d->format('Y-m-d') <= min((string) $etapa['fim'], date('Y-m-d'))) {
                $dias[] = $d->format('Y-m-d');
            }
        }
        $n = 0;
        foreach (self::todas() as $k => [$ano, $seg, , , $alunos]) {
            if ($ano !== self::ANO || $seg !== self::EF) {
                continue;
            }
            $of = (int) (self::ofertas($c, self::turma($c, $k))['Matemática'] ?? 0) ?: VcDemoPassos::falha("a Matemática da turma {$k}");
            if ((string) $c->um('SELECT frequencia_fonte FROM rm_professores_materias WHERE id = ?', [$of]) !== 'chamada') {
                $prof->gravar($p . "chamada/fonte/{$of}", ['fonte' => 'chamada']);
                VcDemoPassos::confere($prof, "a chamada na Matemática de {$k}");
                (string) $c->um('SELECT frequencia_fonte FROM rm_professores_materias WHERE id = ?', [$of]) === 'chamada'
                    ?: VcDemoPassos::falha("a Matemática de {$k} não passou para a chamada: " . $prof->alerta());
            }
            /* o ausente é um dos alunos que estão na turma agora (o que trocou de turma já não está na de origem) */
            $t = self::turma($c, $k);
            $ras = array_values(array_filter(array_keys($alunos), static fn ($ra) => (bool) $c->um(
                'SELECT id FROM esc_turma_alunos WHERE turma_id = ? AND rm = ? AND saida IS NULL', [$t, (string) $ra])));
            $ras ?: VcDemoPassos::falha("a turma {$k} sem alunos para a chamada");
            foreach ($dias as $j => $dia) {
                if ($c->um('SELECT id FROM rm_oferta_encontros WHERE rm_prof_mat_id = ? AND data = ?', [$of, $dia])) {
                    continue;
                }
                $ausente = (string) $ras[($j + 1) % count($ras)];
                $prof->gravar($p . "chamada/encontro/{$of}", ['SalvarChamada' => '1', 'data' => $dia, 'titulo' => 'Aula ' . ($j + 1) . ' do 3º bimestre (demonstração)',
                    'conteudo' => 'Conteúdo fictício da aula (demonstração).', 'ausente' => [$ausente => '1']]);
                VcDemoPassos::confere($prof, "a aula de {$dia} na Matemática de {$k}");
                $c->um('SELECT id FROM rm_oferta_encontros WHERE rm_prof_mat_id = ? AND data = ?', [$of, $dia]) ?: VcDemoPassos::falha("a aula de {$dia} ({$k}) não foi gravada: " . $prof->alerta());
            }
            $n++;
        }
        $c->log("A Matemática das {$n} turmas do fundamental na chamada, com " . count($dias) . ' aulas do 3º bimestre lançadas.');
    }

    /** Os RA das turmas (sem argumento: de todas, as de TURMAS e as montadas depois). */
    public static function ras(?array $turmas = null): array
    {
        $r = [];
        foreach ($turmas ?? self::todas() as [, , , , $alunos]) {
            $r = array_merge($r, array_keys($alunos));
        }
        return $r;
    }
}
