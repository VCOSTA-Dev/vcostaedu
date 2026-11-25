<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsRead;

/**
 * AS TURMAS DA ESCOLA (docs/ESCOLA.md): a turma é do ano letivo, de um segmento (curso), da matriz e de uma série
 * (módulo), com nome, turno, sala, capacidade e a unidade (o polo). Criar a turma GERA as ofertas da série — uma por
 * componente curricular, pela mesma função de "Gerar oferta do ano" (AdmsGerarOferta) — com a turma da oferta
 * ("codtur") = "<série> <nome>" (o Moodle recebe a série e a turma no nome da disciplina); as ofertas ficam ligadas
 * à turma em esc_turma_ofertas. O professor de cada oferta se indica na tela da oferta, como sempre.
 * Renomear a turma renomeia a turma das ofertas, e o Moodle acompanha (AdmsMoodle::anotarNome).
 */
class AdmsTurmas
{
    public const NOME_MAX = 30;

    /** A turma das ofertas: "6º ano A". */
    public static function codtur(string $serie, string $nome): string
    {
        if (\App\adms\Models\helper\AdmsTipoInstituicao::ehLivre()) {
            return trim($nome); // D-106: no curso livre, a turma é só o nome (a série é a interna, nunca exibida)
        }
        return trim($serie . ' ' . $nome);
    }

    /** @return list<array> as turmas do ano (todas, com 0), com a série, o segmento e quantos alunos. A coordenação vê o segmento dela. */
    public static function listar(int $ano = 0): array
    {
        $ler = new AdmsRead();
        $ler->fullRead('SELECT t.*, c.nome AS segmento, m.codnome AS serie, m.ordem AS serie_ordem, p.nome AS turno_nome,
                (SELECT COUNT(*) FROM esc_turma_alunos a WHERE a.turma_id = t.id AND a.saida IS NULL) AS alunos,
                (SELECT COUNT(*) FROM esc_turma_ofertas o WHERE o.turma_id = t.id) AS ofertas
              FROM esc_turmas t INNER JOIN rm_cursos c ON c.id = t.curso_id INNER JOIN rm_cursos_turma_series m ON m.id = t.modulo_id
              LEFT JOIN rm_cursos_periodos p ON p.id = t.turno_id
             WHERE (:a = 0 OR t.ano = :b) ORDER BY t.ano DESC, c.nome, m.ordem, t.nome', "a={$ano}&b={$ano}");
        $escopo = \App\adms\Models\helper\AdmsEscopo::cursos();
        return array_values(array_filter($ler->getResultado() ?: [], static fn($t) => $escopo === null || in_array((int) $t['curso_id'], $escopo, true)));
    }

    /** Onda 8, correção, segunda leva, 2.3 (D-89): a turma existe, mas é de um segmento fora do recorte do coordenador? */
    public static function foraDoEscopo(int $id): bool
    {
        $escopo = \App\adms\Models\helper\AdmsEscopo::cursos();
        if ($escopo === null) {
            return false;
        }
        $ler = new AdmsRead();
        $ler->fullRead('SELECT curso_id FROM esc_turmas WHERE id = :id LIMIT :limit', "id={$id}&limit=1");
        $c = $ler->getResultado()[0]['curso_id'] ?? null;
        return $c !== null && !in_array((int) $c, $escopo, true);
    }

    public static function ver(int $id): ?array
    {
        $ler = new AdmsRead();
        $ler->fullRead('SELECT t.*, c.nome AS segmento, m.codnome AS serie, m.ordem AS serie_ordem, g.versao, p.nome AS turno_nome, po.nome AS unidade
              FROM esc_turmas t INNER JOIN rm_cursos c ON c.id = t.curso_id INNER JOIN rm_cursos_turma_series m ON m.id = t.modulo_id
              INNER JOIN rm_cursos_grades g ON g.id = t.grade_id LEFT JOIN rm_cursos_periodos p ON p.id = t.turno_id
              LEFT JOIN rm_polos po ON po.id = t.polo_id WHERE t.id = :id LIMIT :limit', "id={$id}&limit=1");
        $t = $ler->getResultado()[0] ?? null;
        if (!$t) {
            return null;
        }
        $escopo = \App\adms\Models\helper\AdmsEscopo::cursos();
        if ($escopo !== null && !in_array((int) $t['curso_id'], $escopo, true)) {
            return null;
        }
        $t['codtur'] = self::codtur((string) $t['serie'], (string) $t['nome']);
        return $t;
    }

    /** As ofertas da turma (um componente cada), com o professor e o estado no Moodle. */
    public static function ofertas(int $turmaId): array
    {
        $ler = new AdmsRead();
        $ler->fullRead("SELECT o.id, o.materia, o.codtur, o.ano, o.idMoodle, o.encerrada_em, o.ch_considerada,
                (SELECT GROUP_CONCAT(u.nome ORDER BY u.nome SEPARATOR ', ') FROM rm_professores_mat_liga lg INNER JOIN adms_usuarios u ON u.id = lg.usuario_id
                  WHERE lg.rm_prof_mat_id = o.id AND lg.situacao = 1) AS professores
              FROM esc_turma_ofertas x INNER JOIN rm_professores_materias o ON o.id = x.oferta_id
              LEFT JOIN uni_rh_cursos_disciplinas_grade l ON l.id = o.matriz_id
             WHERE x.turma_id = :t ORDER BY l.ordem, o.materia", "t={$turmaId}");
        return $ler->getResultado() ?: [];
    }

    /** As opções do formulário: anos, segmentos com as matrizes e as séries, turnos, unidades. */
    public static function opcoes(): array
    {
        $q = static function (string $sql) { $l = new AdmsRead(); $l->fullRead($sql); return $l->getResultado() ?: []; };
        return [
            'anos' => array_map('intval', array_column($q('SELECT ano FROM adms_periodos_letivos ORDER BY ano DESC'), 'ano')),
            'series' => $q('SELECT m.id, m.codnome, m.grade_id, g.versao, g.curso_id, c.nome AS segmento FROM rm_cursos_turma_series m
                INNER JOIN rm_cursos_grades g ON g.id = m.grade_id INNER JOIN rm_cursos c ON c.id = g.curso_id
                WHERE g.situacao <> 4 AND c.situacao = 1 ORDER BY c.nome, g.versao, m.ordem'),
            'turnos' => $q('SELECT id, nome FROM rm_cursos_periodos ORDER BY id'),
            'unidades' => $q('SELECT id, nome FROM rm_polos WHERE situacao = 1 ORDER BY nome'),
        ];
    }

    /** @return array{0: ?array, 1: string} os campos limpos, ou a recusa */
    private static function limpar(array $d): array
    {
        $nome = trim((string) ($d['nome'] ?? ''));
        if ($nome === '' || mb_strlen($nome) > self::NOME_MAX) {
            return [null, 'Dê um nome à turma (até ' . self::NOME_MAX . ' letras), por exemplo "A" ou "Manhã 1".'];
        }
        $cap = trim((string) ($d['capacidade'] ?? ''));
        if ($cap !== '' && (!preg_match('/^\d{1,3}$/', $cap) || (int) $cap < 1)) {
            return [null, 'A capacidade é um número de 1 a 999 (ou vazia).'];
        }
        $turno = (int) ($d['turno_id'] ?? 0);
        if ($turno !== 0 && !\App\adms\Models\vinculo\AdmsVinculoRegras::existe('rm_cursos_periodos', $turno)) {
            return [null, 'Turno inválido.'];
        }
        $sala = trim((string) ($d['sala'] ?? ''));
        if (mb_strlen($sala) > 60) {
            return [null, 'A sala tem até 60 letras.'];
        }
        return [['nome' => $nome, 'capacidade' => $cap === '' ? null : (int) $cap, 'turno_id' => $turno ?: null, 'sala' => $sala === '' ? null : $sala], ''];
    }

    /**
     * Cria a turma e gera as ofertas da série.
     * @return int|string o id da turma, ou a recusa
     */
    public static function criar(array $d)
    {
        [$campos, $erro] = self::limpar($d);
        if (!$campos) {
            return $erro;
        }
        $ano = (int) ($d['ano'] ?? 0);
        if (!\App\adms\Models\vinculo\AdmsVinculoRegras::anoLetivoExiste($ano)) {
            return 'Escolha um ano letivo cadastrado.';
        }
        if (!\App\adms\Models\helper\AdmsTipoInstituicao::ehLivre() && !AdmsEtapas::doAno($ano)) { // D-106: o curso livre não tem etapas
            return "Abra antes o ano letivo {$ano} com as etapas (Ensino → Ano letivo e etapas).";
        }
        $serie = \App\adms\Models\vinculo\AdmsVinculoRegras::modulo((int) ($d['modulo_id'] ?? 0));
        if (!$serie) {
            return 'Escolha a série.';
        }
        $escopo = \App\adms\Models\helper\AdmsEscopo::cursos();
        if ($escopo !== null && !in_array((int) $serie['curso_id'], $escopo, true)) {
            return 'Esta série não é de um segmento que você coordena.';
        }
        $polo = (int) ($d['polo_id'] ?? 0);
        if ($polo === 0) {
            $l = new AdmsRead();
            $l->fullRead("SELECT id FROM rm_polos WHERE situacao = 1 ORDER BY (nome = 'Sede') DESC, id LIMIT 1");
            $polo = (int) ($l->getResultado()[0]['id'] ?? 0);
        }
        $codtur = self::codtur((string) $serie['codnome'], $campos['nome']);
        $l = new AdmsRead();
        $l->fullRead('SELECT id FROM esc_turmas WHERE ano = :a AND modulo_id = :m AND nome = :n LIMIT :limit', "a={$ano}&m={$serie['id']}&n={$campos['nome']}&limit=1");
        if ($l->getResultado()) {
            return "Já existe a turma {$codtur} em {$ano}.";
        }
        $T = \Core\Transacao::class;
        $id = $T::executar(function () use ($T, $campos, $ano, $serie, $polo, $codtur) {
            $pdo = AdmsEscolaBanco::pdo();
            $pdo->prepare('INSERT INTO esc_turmas (ano, curso_id, grade_id, modulo_id, nome, turno_id, sala, capacidade, polo_id, situacao, criado_por, created)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())')
                ->execute([$ano, (int) $serie['curso_id'], (int) $serie['grade_id'], (int) $serie['id'], $campos['nome'], $campos['turno_id'], $campos['sala'], $campos['capacidade'], $polo, (int) ($_SESSION['colUsuario_id'] ?? 0)]);
            $turma = (int) $pdo->lastInsertId();
            $gerar = new \App\adms\Models\disciplinas\AdmsGerarOferta();
            /* D-106: sem módulos na matriz (o curso livre), a oferta é da grade: todas as linhas do módulo interno */
            $linhas = [];
            if (\App\adms\Models\helper\AdmsModuloInterno::ativo()) {
                $st = $pdo->prepare('SELECT id FROM uni_rh_cursos_disciplinas_grade WHERE grade_id = ? AND modulo_id = ?');
                $st->execute([(int) $serie['grade_id'], (int) $serie['id']]);
                $linhas = array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));
            }
            $gerar->gerar(['grade_id' => (int) $serie['grade_id'], 'modulos' => [(int) $serie['id']], 'linhas' => $linhas, 'ano' => $ano, 'codtur' => $codtur, 'codfilial' => $polo]);
            if (!$gerar->getResultado()) {
                $T::falhar(strip_tags((string) ($_SESSION['msg'] ?? 'as ofertas da série')));
            }
            $pdo->prepare('INSERT IGNORE INTO esc_turma_ofertas (turma_id, oferta_id)
                SELECT ?, o.id FROM rm_professores_materias o INNER JOIN uni_rh_cursos_disciplinas_grade l ON l.id = o.matriz_id
                 WHERE o.ano = ? AND o.codtur = ? AND l.modulo_id = ?')->execute([$turma, $ano, $codtur, (int) $serie['id']]);
            /* Onda 4, Etapa 4: na escola, o total de encontros da oferta é a carga horária do componente no ano (as horas):
               a frequência se mede no total de horas (D-49) */
            $pdo->prepare('UPDATE rm_professores_materias o INNER JOIN esc_turma_ofertas x ON x.oferta_id = o.id
                INNER JOIN uni_rh_cursos_disciplinas_grade l ON l.id = o.matriz_id SET o.encontros = l.cargahoraria
                WHERE x.turma_id = ? AND o.encontros IS NULL AND l.cargahoraria > 0')->execute([$turma]);
            /* Onda 6, Etapa 5: a fonte das faltas da escola (Regras da escola) nos componentes da turma nova */
            $pdo->prepare('UPDATE rm_professores_materias o INNER JOIN esc_turma_ofertas x ON x.oferta_id = o.id SET o.frequencia_fonte = ? WHERE x.turma_id = ?')
                ->execute([AdmsEscolaConfig::fonteDaOferta(), $turma]);
            return $turma;
        });
        unset($_SESSION['msg']); // a mensagem da geração de ofertas: a tela da turma diz a dela
        if (!$id) {
            return 'A turma não foi criada (' . $T::ultimoErro() . '). Nada foi gravado.';
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(513, 200, 1, "Turma criada: id={$id}; ano={$ano}; serie={$serie['id']}; turma={$codtur}");
        return (int) $id;
    }

    /**
     * Edita o nome, o turno, a sala e a capacidade. Nome novo: a turma das ofertas muda junto, e o Moodle acompanha.
     * @return true|string
     */
    public static function editar(int $id, array $d)
    {
        $t = self::ver($id);
        if (!$t) {
            return 'Turma não encontrada.';
        }
        [$campos, $erro] = self::limpar($d);
        if (!$campos) {
            return $erro;
        }
        $l = new AdmsRead();
        $l->fullRead('SELECT id FROM esc_turmas WHERE ano = :a AND modulo_id = :m AND nome = :n AND id <> :id LIMIT :limit', "a={$t['ano']}&m={$t['modulo_id']}&n={$campos['nome']}&id={$id}&limit=1");
        if ($l->getResultado()) {
            return 'Já existe outra turma com esse nome na série, no mesmo ano.';
        }
        $novo = self::codtur((string) $t['serie'], $campos['nome']);
        $ofertas = array_map(static fn($o) => (int) $o['id'], self::ofertas($id));
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($id, $campos, $novo, $ofertas) {
            $pdo = AdmsEscolaBanco::pdo();
            $pdo->prepare('UPDATE esc_turmas SET nome = ?, turno_id = ?, sala = ?, capacidade = ? WHERE id = ?')
                ->execute([$campos['nome'], $campos['turno_id'], $campos['sala'], $campos['capacidade'], $id]);
            if ($ofertas) {
                $pdo->prepare('UPDATE rm_professores_materias SET codtur = ? WHERE id IN (' . implode(',', $ofertas) . ')')->execute([$novo]);
            }
            return true;
        });
        if (!$ok) {
            return 'A turma não foi alterada (' . $T::ultimoErro() . ').';
        }
        if ($novo !== $t['codtur']) {
            foreach ($ofertas as $o) {
                \App\adms\Models\moodle\AdmsMoodle::anotarNome($o); // o nome da disciplina no Moodle acompanha a turma
            }
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(514, 200, 3, "Turma editada: id={$id}; turma={$novo}");
        return true;
    }
}
