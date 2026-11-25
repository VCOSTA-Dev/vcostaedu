<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * O ESCOPO POR CURSO — Onda 2, Etapa 2. UMA função decide (cursos()); todo o resto pergunta a ela.
 *
 * A Coordenação de curso vê só os cursos em que é coordenadora (rm_cursos_coordenacao, situação 1 — o vínculo que a
 * tela do curso grava). Os demais papéis não têm recorte: cursos() devolve null.
 *
 * Onde vale:
 *   - nas ROTAS: a página diz qual parâmetro ela recebe (adms_paginas.escopo: oferta, atividade, curso, aluno,
 *     relatorio) e o roteador pergunta rota() antes de abrir o controller;
 *   - nas LISTAGENS e nos RELATÓRIOS: sql() devolve a condição que restringe a consulta (as listas de disciplinas e de
 *     cursos, e as duas fontes dos relatórios, AdmsRelatorioFonte::ofertas e ::matriculas, com as opções dos filtros);
 *   - nos DOCUMENTOS (o boletim do aluno e o PDF dele): pelo escopo "aluno" da rota.
 * Antes da 0005 (D-28), não há recorte (como antes).
 *
 * O PROFESSOR (Onda 3, Etapa 1): só alcança as ofertas a que está ligado (rm_professores_mat_liga, situação 1), na
 * LEITURA e na GRAVAÇÃO. O professor que também coordena um curso LÊ todas as ofertas desse curso e GRAVA só nas suas.
 * Quem decide é podeOferta($oferta, $gravar); a atividade, a nota e o encontro chegam à oferta deles. O roteador
 * pergunta rota($escopo, $parametro, $gravar) com a marca "grava" da página. Os demais papéis não mudam.
 */
class AdmsEscopo
{
    /** @var array<int>|null|false  false = ainda não lido */
    private static $cursos = false;
    /** @var array<int>|false  as ofertas do professor (false = ainda não lido) */
    private static $ligadas = false;
    /** @var array<int>|false  os cursos que o usuário coordena, qualquer que seja o papel (false = ainda não lido) */
    private static $coordenados = false;

    /** @return array<int>|null os cursos do usuário (null = todos) */
    public static function cursos(): ?array
    {
        if (self::$cursos !== false) {
            return self::$cursos;
        }
        if (!AdmsNivel::comPapeis() || AdmsNivel::papel() !== AdmsNivel::COORDENACAO) {
            return self::$cursos = null;
        }
        $ler = new AdmsRead();
        $ler->fullRead("SELECT DISTINCT curso_id FROM rm_cursos_coordenacao WHERE usuario_id =:u AND situacao =:s", "u=" . (int) ($_SESSION['colUsuario_id'] ?? 0) . "&s=1");
        return self::$cursos = array_values(array_map(static fn($r) => (int) $r['curso_id'], $ler->getResultado() ?: []));
    }

    public static function recortado(): bool
    {
        return self::cursos() !== null;
    }

    /** O papel Professor (com os papéis da 0005): o recorte é pelas ofertas a que ele está ligado. */
    public static function professor(): bool
    {
        return AdmsNivel::comPapeis() && AdmsNivel::papel() === AdmsNivel::PROFESSOR;
    }

    /** @return array<int> as ofertas a que o usuário está ligado como professor */
    public static function ofertasLigadas(): array
    {
        if (self::$ligadas !== false) {
            return self::$ligadas;
        }
        $ler = new AdmsRead();
        $ler->fullRead("SELECT DISTINCT rm_prof_mat_id FROM rm_professores_mat_liga WHERE usuario_id =:u AND situacao =:s", "u=" . (int) ($_SESSION['colUsuario_id'] ?? 0) . "&s=1");
        return self::$ligadas = array_values(array_map(static fn($r) => (int) $r['rm_prof_mat_id'], $ler->getResultado() ?: []));
    }

    /** @return array<int> os cursos que o usuário coordena (o vínculo da tela do curso), qualquer que seja o papel */
    public static function cursosCoordenados(): array
    {
        if (self::$coordenados !== false) {
            return self::$coordenados;
        }
        $ler = new AdmsRead();
        $ler->fullRead("SELECT DISTINCT curso_id FROM rm_cursos_coordenacao WHERE usuario_id =:u AND situacao =:s", "u=" . (int) ($_SESSION['colUsuario_id'] ?? 0) . "&s=1");
        return self::$coordenados = array_values(array_map(static fn($r) => (int) $r['curso_id'], $ler->getResultado() ?: []));
    }

    /** Há recorte para este usuário (o curso do coordenador, ou as ofertas do professor)? */
    public static function restrito(): bool
    {
        return self::recortado() || self::professor();
    }

    public static function podeCurso(int $cursoId): bool
    {
        $c = self::cursos();
        return $c === null || in_array($cursoId, $c, true);
    }

    /** " AND <coluna> IN (…)" para a consulta; vazio sem recorte; nenhum curso = nenhuma linha. */
    public static function sql(string $coluna): string
    {
        $c = self::cursos();
        if ($c === null) {
            return '';
        }
        return $c === [] ? ' AND 1 = 0' : ' AND ' . $coluna . ' IN (' . implode(',', array_map('intval', $c)) . ')';
    }

    private static function um(string $sql, string $parametros): ?int
    {
        $ler = new AdmsRead();
        $ler->fullRead($sql, $parametros);
        $r = $ler->getResultado();
        return $r ? (int) array_values($r[0])[0] : null;
    }

    /**
     * A oferta, para ler ($gravar = false) ou para gravar. A ÚNICA decisão do recorte por oferta:
     *   professor: ligado à oferta; para ler, também a oferta de um curso que ele coordena;
     *   coordenação: a oferta de um curso que ela coordena (como antes);
     *   os demais: tudo.
     */
    public static function podeOferta(int $ofertaId, bool $gravar = false): bool
    {
        if (self::professor()) {
            if (in_array($ofertaId, self::ofertasLigadas(), true)) {
                return true;
            }
            if ($gravar) {
                return false;
            }
            $curso = self::um("SELECT curso_id FROM rm_professores_materias WHERE id =:id LIMIT :limit", "id={$ofertaId}&limit=1");
            return $curso !== null && in_array($curso, self::cursosCoordenados(), true);
        }
        if (!self::recortado()) {
            return true;
        }
        $curso = self::um("SELECT curso_id FROM rm_professores_materias WHERE id =:id LIMIT :limit", "id={$ofertaId}&limit=1");
        return $curso !== null && self::podeCurso($curso);
    }

    public static function podeAtividade(int $atividadeId, bool $gravar = false): bool
    {
        if (!self::restrito()) {
            return true;
        }
        $oferta = self::um("SELECT rm_prof_mat_id FROM rm_disciplina_atividade WHERE id =:id LIMIT :limit", "id={$atividadeId}&limit=1");
        return $oferta !== null && self::podeOferta($oferta, $gravar);
    }

    /** A nota (a linha de rm_disciplina_atividade_notas que a célula grava): a oferta dela. */
    public static function podeNota(int $notaId, bool $gravar = false): bool
    {
        if (!self::restrito()) {
            return true;
        }
        $oferta = self::um("SELECT rm_prof_mat_id FROM rm_disciplina_atividade_notas WHERE id =:id LIMIT :limit", "id={$notaId}&limit=1");
        return $oferta !== null && self::podeOferta($oferta, $gravar);
    }

    /** O encontro da chamada: a oferta dele. */
    public static function podeEncontro(int $encontroId, bool $gravar = false): bool
    {
        if (!self::restrito()) {
            return true;
        }
        $oferta = self::um("SELECT rm_prof_mat_id FROM rm_oferta_encontros WHERE id =:id LIMIT :limit", "id={$encontroId}&limit=1");
        return $oferta !== null && self::podeOferta($oferta, $gravar);
    }

    /** O aluno (pelo RA) tem matrícula num dos cursos do usuário? */
    public static function podeAluno(string $rm): bool
    {
        if (!self::restrito()) {
            return true;
        }
        $rm = preg_replace('/\D/', '', $rm);
        if ($rm === '') {
            return false;
        }
        /* Onda 3, Etapa 2: o professor abre o boletim (tela e PDF) do aluno enturmado numa oferta a que ele está ligado,
           ou com matrícula num curso que ele coordena */
        if (self::professor()) {
            $ofertas = self::ofertasLigadas();
            if ($ofertas && self::um("SELECT COUNT(*) FROM portal_disciplinas_alunos WHERE rm =:rm AND rm_prof_mat_id IN (" . implode(',', $ofertas) . ")", "rm={$rm}") > 0) {
                return true;
            }
            $cursos = self::cursosCoordenados();
            return $cursos && self::um("SELECT COUNT(*) FROM rm_matriculas_curso mc INNER JOIN col_usuarios u ON u.id = mc.usuario_id WHERE u.rm =:rm AND mc.curso_id IN (" . implode(',', $cursos) . ")", "rm={$rm}") > 0;
        }
        return self::um("SELECT COUNT(*) FROM rm_matriculas_curso mc INNER JOIN col_usuarios u ON u.id = mc.usuario_id WHERE u.rm =:rm" . self::sql('mc.curso_id'), "rm={$rm}") > 0;
    }

    public static function podeMatricula(int $matriculaId): bool
    {
        if (!self::recortado()) {
            return true;
        }
        $curso = self::um("SELECT curso_id FROM rm_matriculas_curso WHERE id =:id LIMIT :limit", "id={$matriculaId}&limit=1");
        return $curso !== null && self::podeCurso($curso);
    }

    /** Os filtros do relatório (na URL) que apontam para um curso, uma oferta, uma grade ou uma matrícula. */
    public static function podeFiltros(array $get): bool
    {
        if (!self::restrito()) {
            return true;
        }
        if (!empty($get['curso']) && !self::podeCurso((int) $get['curso'])) {
            return false;
        }
        if (!empty($get['oferta']) && !self::podeOferta((int) $get['oferta'])) {
            return false;
        }
        if (!empty($get['matricula']) && !self::podeMatricula((int) $get['matricula'])) {
            return false;
        }
        if (!empty($get['grade'])) {
            $curso = self::um("SELECT curso_id FROM rm_cursos_grades WHERE id =:id LIMIT :limit", "id=" . (int) $get['grade'] . "&limit=1");
            if ($curso === null || !self::podeCurso($curso)) {
                return false;
            }
        }
        return true;
    }

    /**
     * A rota pode abrir com este parâmetro? $escopo é o adms_paginas.escopo da página; $gravar, a marca "grava" dela.
     * "nota": a nota vem no corpo da requisição (a célula da nota manda o id da linha por POST).
     */
    public static function rota(?string $escopo, $parametro, bool $gravar = false): bool
    {
        if (!self::restrito() || $escopo === null || $escopo === '') {
            return true;
        }
        $p = (string) $parametro;
        switch ($escopo) {
            case 'oferta':
                return self::podeOferta((int) $p, $gravar);
            case 'atividade':
                return self::podeAtividade((int) $p, $gravar);
            case 'nota':
                return self::podeNota((int) ($_POST['id'] ?? $p), $gravar);
            case 'encontro':
                return self::podeEncontro((int) $p, $gravar);
            case 'curso':
                return self::podeCurso((int) $p);
            case 'aluno':
                return self::podeAluno($p);
            case 'relatorio':
                return self::podeFiltros($_GET);
            default:
                return false;
        }
    }

    /** A recusa: a mensagem e a volta para as disciplinas do usuário (nada é gravado). */
    public static function recusar(): void
    {
        $texto = self::professor()
            ? 'Esta disciplina não é sua: você alcança as disciplinas em que dá aula (e, para consultar, as dos cursos que coordena).'
            : 'Este conteúdo é de um curso que você não coordena.';
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible' id='foraDoEscopo'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>{$texto}</div>";
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            http_response_code(403); // a célula da nota (AJAX): a recusa, sem a página inteira
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['erro' => false, 'msg' => $texto], JSON_UNESCAPED_UNICODE);
            unset($_SESSION['msg']);
            return;
        }
        header('Location: ' . URLADM . 'minhas-disciplinas/listar');
    }
}
