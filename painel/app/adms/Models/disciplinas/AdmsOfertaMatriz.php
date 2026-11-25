<?php

namespace App\adms\Models\disciplinas;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * OFERTA LIGADA À MATRIZ (E3b, D-03). A oferta (rm_professores_materias) aponta
 * para a LINHA da matriz (`matriz_id` → uni_rh_cursos_disciplinas_grade), e dela
 * vêm curso, número da grade, módulo (`periodo` = ordem do módulo), matéria
 * (`codmat`) e CH. Polo = `codfilial` (rm_polos); modalidade herdada do curso.
 */
class AdmsOfertaMatriz
{
    /** Linhas de matriz de grades não desativadas, para o seletor. */
    public static function linhas(int $incluir = 0): array
    {
        /* E8d (P-8): curso ou matéria INATIVOS saem da escolha; a linha que a oferta já usa ($incluir) continua */
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT l.id, l.cargahoraria, mt.nome AS materia, m.codnome AS modulo, m.ordem, g.versao, g.situacao, c.nome AS curso
               FROM uni_rh_cursos_disciplinas_grade l
               INNER JOIN rm_materias mt ON mt.id = l.materia_id
               INNER JOIN rm_cursos_turma_series m ON m.id = l.modulo_id
               INNER JOIN rm_cursos_grades g ON g.id = l.grade_id
               INNER JOIN rm_cursos c ON c.id = g.curso_id
              WHERE (g.situacao <> :d AND c.situacao = :a AND mt.situacao = :a2) OR l.id = :inc
              ORDER BY c.nome, g.versao, m.ordem, mt.nome",
            "d=4&a=1&a2=1&inc={$incluir}"
        );
        return $ler->getResultado() ?: [];
    }

    /** Dados que a oferta herda da linha da matriz, ou null. */
    public static function linha(int $id): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT l.id, l.grade_id, l.modulo_id, l.materia_id, l.cargahoraria, mt.nome AS materia,
                    m.ordem, m.codnome AS modulo, g.versao, g.situacao AS situacaoGrade, g.curso_id,
                    mt.situacao AS situacaoMateria, (SELECT c.situacao FROM rm_cursos c WHERE c.id = g.curso_id) AS situacaoCurso, (SELECT c.nome FROM rm_cursos c WHERE c.id = g.curso_id) AS nomeCurso
               FROM uni_rh_cursos_disciplinas_grade l
               INNER JOIN rm_materias mt ON mt.id = l.materia_id
               INNER JOIN rm_cursos_turma_series m ON m.id = l.modulo_id
               INNER JOIN rm_cursos_grades g ON g.id = l.grade_id
              WHERE l.id = :id LIMIT :limit",
            "id={$id}&limit=1"
        );
        return $ler->getResultado()[0] ?? null;
    }

    /** idMoodle: vazio → null; inteiro positivo → int; outra coisa → false. */
    public static function idMoodle($valor)
    {
        $v = trim((string) $valor);
        if ($v === '') {
            return null;
        }
        return preg_match('/^[1-9]\d{0,9}$/', $v) && (int) $v <= 2147483647 ? (int) $v : false;
    }

    public static function poloAtivo(int $id): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id FROM rm_polos WHERE id = :id AND situacao = :s LIMIT :limit", "id={$id}&s=1&limit=1");
        return !empty($ler->getResultado());
    }

    /**
     * Valida e completa os dados da oferta a partir da linha da matriz.
     * @return array|string os campos da oferta, ou a mensagem de recusa
     */
    public static function montar(array $dados, bool $nova = true)
    {
        $linha = self::linha((int) ($dados['matriz_id'] ?? 0));
        if (!$linha) {
            return 'Escolha a disciplina na matriz (curso — grade — módulo — disciplina).';
        }
        /* E8d (P-8): oferta NOVA não usa curso nem matéria inativos (editar uma oferta existente continua valendo) */
        if ($nova && (int) $linha['situacaoCurso'] !== 1) {
            return "O curso {$linha['nomeCurso']} está inativo: reative-o em Cursos para ofertar.";
        }
        if ($nova && (int) $linha['situacaoMateria'] !== 1) {
            return "A matéria {$linha['materia']} está inativa: reative-a em Matérias para ofertar.";
        }
        if ((int) $linha['situacaoGrade'] === 4) {
            return "A grade {$linha['versao']} está Desativada: não recebe oferta nova.";
        }
        $codtur = trim((string) ($dados['codtur'] ?? ''));
        $ano = (int) ($dados['ano'] ?? 0);
        if ($codtur === '' || $ano < 2000 || $ano > 2100) {
            return 'Informe a turma e o ano da oferta.';
        }
        $polo = (int) ($dados['codfilial'] ?? 0);
        if (!self::poloAtivo($polo)) {
            return 'Escolha um polo ativo (Configurações → Polos).';
        }
        $modalidade = trim((string) ($dados['modalidade_id'] ?? ''));
        $modalidade = $modalidade === '' ? \App\adms\Models\modalidades\AdmsModalidades::doCurso((int) $linha['curso_id']) : (int) $modalidade;
        if (!\App\adms\Models\modalidades\AdmsModalidades::existe($modalidade)) {
            return 'Modalidade inválida.';
        }
        /* E4: total de encontros (frequência). Vazio = não informado; inteiro de 1 a 999. */
        $enc = trim((string) ($dados['encontros'] ?? ''));
        if ($enc !== '' && (!preg_match('/^\d{1,3}$/', $enc) || (int) $enc < 1)) {
            return 'O total de encontros deve ser um número inteiro de 1 a 999 (ou vazio).';
        }
        /* 1.17.2 (D-462): encontros PREVISTOS (só a oferta por chamada mostra o campo). Vazio = não informado; 1 a 999. É a
           base dos relatórios e do alerta de risco; a frequência oficial continua sobre os encontros registrados. */
        $prev = trim((string) ($dados['encontros_previstos'] ?? ''));
        if ($prev !== '' && (!preg_match('/^\d{1,3}$/', $prev) || (int) $prev < 1)) {
            return 'Os encontros previstos devem ser um número inteiro de 1 a 999 (ou vazio).';
        }
        /* E6a: período de realização da oferta (histórico). Vazio = não informado. */
        $ini = trim((string) ($dados['data_inicio'] ?? ''));
        $fim = trim((string) ($dados['data_fim'] ?? ''));
        foreach ([$ini, $fim] as $dt) {
            if ($dt !== '' && (!($o = \DateTime::createFromFormat('Y-m-d', $dt)) || $o->format('Y-m-d') !== $dt)) {
                return 'Data do período da oferta inválida.';
            }
        }
        if ($ini !== '' && $fim !== '' && $fim < $ini) {
            return 'O fim do período da oferta não pode ser antes do início.';
        }
        /* Onda 3, Etapa 8: o idMoodle deixou de ser digitado — quem o grava é a integração (criar ou ligar a disciplina
           no Moodle); editar a oferta não o toca */
        return [
            'matriz_id' => (int) $linha['id'],
            'curso_id' => (int) $linha['curso_id'],
            'grade' => (int) $linha['versao'],
            'periodo' => (int) $linha['ordem'],
            'codmat' => (int) $linha['materia_id'],
            'materia' => $linha['materia'],
            'ch_considerada' => (string) (int) $linha['cargahoraria'],
            'codtur' => $codtur,
            'ano' => $ano,
            'codfilial' => $polo,
            'modalidade_id' => $modalidade,
            'encontros' => $enc === '' ? null : (int) $enc,
            'data_inicio' => $ini === '' ? null : $ini,
            'data_fim' => $fim === '' ? null : $fim,
        ] + (array_key_exists('encontros_previstos', $dados) ? ['encontros_previstos' => $prev === '' ? null : (int) $prev] : []); // só quando o formulário traz o campo
    }

    /** Já existe oferta desta linha, neste ano e nesta turma (fora `$exceto`)? */
    public static function duplicada(int $ano, int $matrizId, string $codtur, int $exceto = 0): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT id FROM rm_professores_materias WHERE ano = :a AND matriz_id = :m AND codtur = :t AND id <> :id LIMIT :limit",
            "a={$ano}&m={$matrizId}&t={$codtur}&id={$exceto}&limit=1"
        );
        return !empty($ler->getResultado());
    }
}
