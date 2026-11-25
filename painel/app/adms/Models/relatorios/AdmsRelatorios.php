<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * 1.17.0 (D-454, D-455) — O REGISTRO DOS RELATÓRIOS. Cada relatório é uma definição (uma classe filha de
 * AdmsRelatorioBase: código, nome, categoria, pergunta, filtros, ficha técnica e a consulta). Relatório novo =
 * definição nova + a rota em adms_paginas. O catálogo e as fichas estão em docs/RELATORIOS.md.
 *
 * Aqui também: o acesso (as permissões comuns de adms_nivacs_pgs, por rota), a permissão própria "ver CPF completo em
 * relatórios" (página 440), os filtros padrão (lidos da URL e validados), os relatórios salvos e a auditoria das
 * exportações.
 */
class AdmsRelatorios
{
    public const CATEGORIAS = [
        'A' => 'Painel gerencial', 'B' => 'Secretaria e cadastro', 'C' => 'Desempenho acadêmico', 'D' => 'Frequência',
        'E' => 'Evasão, risco e conclusão', 'F' => 'TCC', 'G' => 'Documentos', 'H' => 'Auditoria e governança',
        'I' => 'Financeiro', // Onda 8, financeiro, Parte 5 (D-98)
    ];

    /** código => classe (a rota é a constante SLUG da classe). */
    public const DEFINICOES = [
        'R-PAI-01' => RelPainelAcademico::class,
        'R-SEC-01' => RelAlunosSituacao::class,
        'R-SEC-02' => RelListaTurma::class,
        'R-SEC-03' => RelPrevisaoConclusao::class,
        'R-SEC-04' => RelQualidadeCadastro::class,
        'R-DES-01' => RelMapaNotas::class,
        'R-DES-02' => RelResultadoDisciplina::class,
        'R-DES-03' => RelDistribuicaoNotas::class,
        'R-DES-04' => RelPendenciasLancamento::class,
        'R-DES-05' => RelDesempenhoAluno::class,
        'R-FRE-01' => RelFrequenciaOferta::class,
        'R-FRE-02' => RelFrequenciaMinimo::class,
        'R-EVA-01' => RelAlunosRisco::class,
        'R-EVA-03' => RelAptosConcluir::class,
        'R-TCC-01' => RelSituacaoTcc::class,
        /* 1.17.1: onda 2 */
        'R-EVA-02' => RelCoorte::class,
        'R-EVA-04' => RelConcluintes::class,
        'R-DOC-01' => RelDocumentos::class,
        'R-AUD-01' => RelReaberturas::class,
        'R-AUD-02' => RelTrocasRegra::class,
        'R-AUD-03' => RelExportacoes::class,
        'R-AUD-04' => RelUsuariosAcesso::class,
        /* Onda 7: os responsáveis (só na escola) */
        'R-SEC-05' => RelResponsaveisFaltando::class,
        'R-AUD-05' => RelAcessosAdicionais::class,
        /* Onda 8, financeiro, Parte 5 (D-98): o caixa e os recebimentos */
        'R-FIN-01' => RelCaixa::class,
        'R-FIN-02' => RelRecebimentos::class,
    ];

    public const FORMATOS = ['excel', 'csv', 'pdf', 'imprimir'];

    private static $permitidas = null;
    private static $cpf = null;

    public static function definicao(string $codigo): ?AdmsRelatorioBase
    {
        $c = self::DEFINICOES[$codigo] ?? null;
        return $c ? new $c() : null;
    }

    /** As rotas (menu_metodo) de relatorios que o nível da sessão pode abrir. */
    private static function permitidas(): array
    {
        if (self::$permitidas === null) {
            self::$permitidas = [];
            { // Onda 3, Etapa 3: saiu a trava dos níveis do fornecedor (que a 0005 removeu)
                foreach (AdmsRelatorioFonte::ler(
                    "SELECT pg.id, pg.menu_metodo FROM adms_paginas pg INNER JOIN adms_nivacs_pgs n ON n.adms_pagina_id = pg.id
                      WHERE pg.menu_controller = 'relatorios' AND pg.adms_sits_pg_id = 1 AND n.permissao = 1 AND n.adms_niveis_acesso_id = :nivel",
                    ['nivel' => (int) ($_SESSION['colUsuario_nivel'] ?? 0)]
                ) as $r) {
                    /* Onda 4, Etapa 1: o relatório de um módulo desligado (os só do ensino superior, na escola) não aparece */
                    if (!\App\adms\Models\helper\AdmsTipoInstituicao::paginaLigada((int) $r['id'])) {
                        continue;
                    }
                    self::$permitidas[] = (string) $r['menu_metodo'];
                }
            }
        }
        return self::$permitidas;
    }

    public static function pode(string $rota): bool
    {
        return in_array($rota, self::permitidas(), true);
    }

    public static function podeRelatorio(string $codigo): bool
    {
        $c = self::DEFINICOES[$codigo] ?? null;
        return $c && self::pode($c::SLUG);
    }

    /** LGPD (D-457): CPF completo só com a permissão própria (rota relatorios/cpf-completo). */
    public static function verCpfCompleto(): bool
    {
        return self::$cpf ??= self::pode('cpf-completo');
    }

    public static function cpf(?string $cpf): string
    {
        $c = preg_replace('/\D/', '', (string) $cpf);
        if ($c === '') {
            return '';
        }
        return self::verCpfCompleto()
            ? \App\adms\Models\historico\AdmsHistoricoVariaveis::cpf($c)
            : \App\adms\Models\historico\AdmsHistoricoVariaveis::cpfMascarado($c);
    }

    public static function ehProfessor(): bool
    {
        return \App\adms\Models\helper\AdmsNivel::ehPapel('professor'); // Onda 2: pelo papel (antes: nível 6)
    }

    /** Os relatórios do perfil, por categoria: [letra => [definição, …]]. */
    public static function doPerfil(): array
    {
        $por = [];
        foreach (self::DEFINICOES as $codigo => $c) {
            if (self::pode($c::SLUG)) {
                $por[$c::CATEGORIA][] = new $c();
            }
        }
        ksort($por);
        return $por;
    }

    /* ---------------------------------------------------------------- filtros ---------------------------------------------------------------- */

    /** Os filtros PADRÃO (cada relatório declara quais usa, e pode acrescentar os seus). */
    public static function filtroPadrao(string $chave): array
    {
        switch ($chave) {
            case 'curso':
                return ['rotulo' => 'Curso', 'tipo' => 'select', 'opcoes' => self::opcoes("SELECT id, nome AS rotulo FROM rm_cursos WHERE 1 = 1" . \App\adms\Models\helper\AdmsEscopo::sql('id') . " ORDER BY nome")];
            case 'grade':
                return ['rotulo' => 'Grade', 'tipo' => 'select', 'depende' => 'curso',
                    'opcoes' => self::opcoes("SELECT g.id, CONCAT(c.nome, IF((SELECT COUNT(*) FROM rm_cursos_grades g2 WHERE g2.curso_id = c.id) > 1, CONCAT(' (grade ', g.versao, ')'), '')) AS rotulo FROM rm_cursos_grades g INNER JOIN rm_cursos c ON c.id = g.curso_id WHERE 1 = 1" . \App\adms\Models\helper\AdmsEscopo::sql('g.curso_id') . " ORDER BY c.nome, g.versao")];
            case 'oferta':
                $op = [];
                foreach (AdmsRelatorioFonte::ofertas([]) as $o) {
                    $op[(int) $o['id']] = AdmsRelatorioFonte::rotuloOferta($o) . ' (' . ($o['encerrada_em'] ? 'encerrada' : 'aberta') . ')';
                }
                return ['rotulo' => 'Oferta', 'tipo' => 'select', 'opcoes' => $op];
            case 'situacao':
                return ['rotulo' => 'Situação da matrícula', 'tipo' => 'select', 'opcoes' => self::opcoes("SELECT id, nome AS rotulo FROM rm_matriculas_situacao ORDER BY id")];
            case 'polo':
                return ['rotulo' => 'Polo', 'tipo' => 'select', 'opcoes' => self::opcoes("SELECT id, nome AS rotulo FROM rm_polos ORDER BY nome")];
            case 'coorte':
                $op = [];
                foreach (AdmsRelatorioFonte::ler("SELECT DISTINCT CONCAT(YEAR(data_ingresso), '/', IF(MONTH(data_ingresso) <= 6, 1, 2)) AS c FROM rm_matriculas_curso WHERE data_ingresso IS NOT NULL ORDER BY c DESC") as $r) {
                    $op[$r['c']] = $r['c'];
                }
                return ['rotulo' => 'Coorte de ingresso', 'tipo' => 'select', 'opcoes' => $op];
            case 'turma': // Onda 6, Etapa 5: a escola
                return ['rotulo' => 'Turma', 'tipo' => 'select',
                    'opcoes' => self::opcoes("SELECT t.id, CONCAT(m.codnome, ' ', t.nome, ' · ', t.ano) AS rotulo FROM esc_turmas t INNER JOIN rm_cursos_turma_series m ON m.id = t.modulo_id ORDER BY t.ano DESC, m.ordem, t.nome")];
            case 'etapa':
                return ['rotulo' => 'Etapa', 'tipo' => 'select',
                    'opcoes' => self::opcoes("SELECT id, CONCAT(nome, ' · ', ano) AS rotulo FROM esc_etapas ORDER BY ano DESC, ordem")];
            case 'de':
                return ['rotulo' => 'De', 'tipo' => 'data'];
            case 'ate':
                return ['rotulo' => 'Até', 'tipo' => 'data'];
        }
        return [];
    }

    /**
     * Onda 6, Etapa 5 (D-56): na escola, os relatórios que leem os alunos e as ofertas ganham a turma; os que leem as
     * notas e as faltas, também a etapa. Código => os filtros a mais.
     */
    public const FILTROS_ESCOLA = [
        'R-PAI-01' => ['turma', 'etapa'], 'R-SEC-01' => ['turma'], 'R-SEC-04' => ['turma'],
        'R-DES-01' => ['etapa'], 'R-DES-02' => ['turma', 'etapa'], 'R-DES-03' => ['turma', 'etapa'], 'R-DES-04' => ['turma', 'etapa'],
        'R-FRE-01' => ['etapa'], 'R-FRE-02' => ['turma', 'etapa'], 'R-EVA-01' => ['turma', 'etapa'],
    ];

    private static function opcoes(string $sql): array
    {
        $op = [];
        foreach (AdmsRelatorioFonte::ler($sql) as $r) {
            $op[(int) $r['id']] = (string) $r['rotulo'];
        }
        return $op;
    }

    /**
     * Os filtros do relatório lidos da URL (GET) e VALIDADOS contra as opções: valor que não está nas opções (ex.: a
     * oferta de outro professor) é descartado, com aviso. Datas AAAA-MM-DD.
     */
    public static function lerFiltros(array $defs, array &$avisos): array
    {
        $f = [];
        foreach ($defs as $k => $d) {
            $v = filter_input(INPUT_GET, $k, FILTER_DEFAULT);
            $v = is_string($v) ? trim($v) : '';
            if ($v === '') {
                if (isset($d['padrao'])) {
                    $f[$k] = (string) $d['padrao'];
                }
                continue;
            }
            if ($d['tipo'] === 'data') {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v)) {
                    $f[$k] = $v;
                }
            } elseif ($d['tipo'] === 'numero') {
                if (preg_match('/^\d{1,4}$/', $v)) {
                    $f[$k] = (string) (int) $v;
                }
            } elseif (array_key_exists($v, $d['opcoes'] ?? [])) {
                $f[$k] = $v;
            } else {
                $avisos[] = 'O filtro "' . $d['rotulo'] . '" recebeu um valor que não está disponível para o seu perfil e foi ignorado.';
            }
        }
        return $f;
    }

    /** "Curso: X; Situação: Y; De: 01/02/2026" — o cabeçalho do PDF e da impressão. */
    public static function textoFiltros(array $defs, array $f): string
    {
        $p = [];
        foreach ($defs as $k => $d) {
            if (!isset($f[$k]) || $f[$k] === '') {
                continue;
            }
            $v = $d['tipo'] === 'data' ? AdmsRelatorioFonte::data($f[$k]) : ($d['tipo'] === 'numero' ? $f[$k] : (string) ($d['opcoes'][$f[$k]] ?? $f[$k]));
            $p[] = $d['rotulo'] . ': ' . $v;
        }
        return $p ? implode('; ', $p) : 'nenhum (tudo)';
    }

    /* --------------------------------------------------------- relatórios salvos --------------------------------------------------------- */

    /** Os salvos que o usuário vê: os dele e os compartilhados com o perfil dele (só dos relatórios que ele pode abrir). */
    public static function salvos(?string $codigo = null): array
    {
        $p = ['u' => (int) ($_SESSION['colUsuario_id'] ?? 0), 'n' => (int) ($_SESSION['colUsuario_nivel'] ?? 0)];
        $w = '';
        if ($codigo) {
            $w = ' AND s.relatorio = :r';
            $p['r'] = $codigo;
        }
        $saida = [];
        foreach (AdmsRelatorioFonte::ler(
            "SELECT s.*, u.nome AS dono, na.nome AS perfil FROM rm_relatorios_salvos s
               LEFT JOIN adms_usuarios u ON u.id = s.usuario_id LEFT JOIN adms_niveis_acessos na ON na.id = s.nivel_acesso_id
              WHERE (s.usuario_id = :u OR s.nivel_acesso_id = :n){$w} ORDER BY s.relatorio, s.nome",
            $p
        ) as $s) {
            if (self::podeRelatorio((string) $s['relatorio'])) {
                $s['meu'] = (int) $s['usuario_id'] === $p['u'];
                $saida[] = $s;
            }
        }
        return $saida;
    }

    /** O link que abre o salvo: a rota do relatório + os filtros + as colunas visíveis. */
    public static function urlSalvo(array $s): string
    {
        $c = self::DEFINICOES[$s['relatorio']] ?? null;
        if (!$c) {
            return '#';
        }
        parse_str((string) $s['filtros'], $q);
        $q = array_diff_key($q, ['detalhe' => 1, 'colunas' => 1]);
        $col = json_decode((string) $s['colunas'], true);
        if (is_array($col) && $col) {
            $q['colunas'] = implode(',', $col);
        }
        $q['salvo'] = (int) $s['id'];
        return URLADM . 'relatorios/' . $c::SLUG . ($q ? '?' . http_build_query($q) : '');
    }

    /** Níveis com que se pode compartilhar (os que não são da VCOSTA). */
    public static function perfis(): array
    {
        $op = [];
        /* Onda 2: os níveis do fornecedor saíram (0005); todos os níveis podem receber a visão compartilhada */
        foreach (AdmsRelatorioFonte::ler("SELECT id, nome FROM adms_niveis_acessos ORDER BY ordem, id") as $r) {
            $op[(int) $r['id']] = $r['nome'];
        }
        return $op;
    }

    /** Salva (novo) ou, se `id` é um salvo DO USUÁRIO, atualiza. Quem só recebeu o compartilhamento não edita. */
    public static function salvar(array $d): array
    {
        $codigo = (string) ($d['relatorio'] ?? '');
        $nome = trim((string) ($d['nome'] ?? ''));
        if (!self::podeRelatorio($codigo)) {
            return [false, 'Relatório não disponível para o seu perfil.'];
        }
        if ($nome === '' || mb_strlen($nome) > 120) {
            return [false, 'Dê um nome ao relatório salvo (até 120 caracteres).'];
        }
        $nivel = (int) ($d['nivel_acesso_id'] ?? 0);
        if ($nivel && !isset(self::perfis()[$nivel])) {
            return [false, 'Perfil de compartilhamento inválido.'];
        }
        parse_str(ltrim((string) ($d['filtros'] ?? ''), '?'), $q);
        $q = array_diff_key(array_filter($q, fn($v) => is_string($v) && $v !== ''), ['detalhe' => 1, 'colunas' => 1, 'salvo' => 1, 'calc' => 1]);
        $colunas = array_values(array_filter(explode(',', (string) ($d['colunas'] ?? '')), fn($c) => preg_match('/^[a-z0-9_]{1,40}$/', $c)));
        $dados = [
            'nome' => $nome, 'relatorio' => $codigo, 'filtros' => http_build_query($q),
            'colunas' => $colunas ? json_encode($colunas) : null, 'nivel_acesso_id' => $nivel ?: null,
        ];
        $id = (int) ($d['id'] ?? 0);
        $uid = (int) ($_SESSION['colUsuario_id'] ?? 0);
        if ($id) {
            $dono = AdmsRelatorioFonte::ler("SELECT usuario_id FROM rm_relatorios_salvos WHERE id = :id", ['id' => $id])[0]['usuario_id'] ?? null;
            if ((int) $dono !== $uid) {
                return [false, 'Só quem criou o relatório salvo pode alterá-lo. Salve uma cópia com outro nome.'];
            }
            $up = new \App\adms\Models\helper\AdmsUpdate();
            $up->exeUpdate('rm_relatorios_salvos', $dados, 'WHERE id = :id', "id={$id}");
            return [$up->getResultado() !== null, 'Relatório salvo atualizado.', $id]; // false = nenhuma linha mudou (não é erro)
        }
        $cr = new \App\adms\Models\helper\AdmsCreate();
        $cr->exeCreate('rm_relatorios_salvos', $dados + ['usuario_id' => $uid, 'created' => date('Y-m-d H:i:s')]);
        return [(bool) $cr->getResultado(), 'Relatório salvo.', (int) $cr->getResultado()];
    }

    public static function apagarSalvo(int $id): array
    {
        $dono = AdmsRelatorioFonte::ler("SELECT usuario_id FROM rm_relatorios_salvos WHERE id = :id", ['id' => $id])[0]['usuario_id'] ?? null;
        if ($dono === null) {
            return [false, 'Relatório salvo não encontrado.'];
        }
        if ((int) $dono !== (int) ($_SESSION['colUsuario_id'] ?? 0)) {
            return [false, 'Só quem criou o relatório salvo pode apagá-lo.'];
        }
        $del = new \App\adms\Models\helper\AdmsDeletar();
        $del->exeDelete('rm_relatorios_salvos', 'WHERE id = :id', "id={$id}");
        return [(bool) $del->getResultado(), 'Relatório salvo apagado.'];
    }

    /* ------------------------------------------------------------- auditoria ------------------------------------------------------------- */

    /** LGPD (D-457): toda exportação grava quem, quando, relatório, formato, filtros e linhas (lido pelo R-AUD-03). */
    public static function registrarExportacao(array $d): bool
    {
        $codigo = (string) ($d['relatorio'] ?? '');
        $formato = (string) ($d['formato'] ?? '');
        if (!self::podeRelatorio($codigo) || !in_array($formato, self::FORMATOS, true)) {
            return false;
        }
        parse_str(ltrim((string) ($d['filtros'] ?? ''), '?'), $q);
        $q = array_filter($q, fn($v) => is_string($v) && $v !== '');
        $cr = new \App\adms\Models\helper\AdmsCreate();
        $cr->exeCreate('rm_relatorios_exportacoes', [
            'usuario_id' => (int) ($_SESSION['colUsuario_id'] ?? 0), 'relatorio' => $codigo, 'formato' => $formato,
            'filtros' => mb_substr(http_build_query($q), 0, 2000), 'linhas' => max(0, (int) ($d['linhas'] ?? 0)), 'created' => date('Y-m-d H:i:s'),
        ]);
        return (bool) $cr->getResultado();
    }
}
