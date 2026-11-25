<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * 1.17.0 (D-454) — A DEFINIÇÃO de um relatório. Cada relatório declara:
 *   CODIGO, NOME, CATEGORIA (A a H), PERGUNTA (a pergunta de decisão), SLUG (a rota relatorios/<slug>), QUEM (perfis);
 *   filtros()  as chaves dos filtros padrão e os próprios (['chave' => definição]);
 *   ficha()    a ficha técnica (as linhas de "Como é calculado"; as mesmas do docs/RELATORIOS.md);
 *   dados($f)  a consulta, que devolve:
 *     'avisos'   => [texto],
 *     'cartoes'  => [['rotulo', 'valor', 'detalhe' => chave|null, 'nota' => texto, 'cor' => classe]],
 *     'grafico'  => null | ['tipo' => 'bar', 'rotulos' => [], 'series' => [['rotulo', 'dados' => []]], 'sufixo' => '%'],
 *     'tabela'   => ['titulo', 'colunas' => [['chave', 'titulo', 'num' => bool, 'oculta' => bool]], 'linhas' => [[chave => célula]],
 *                    'rodape' => [chave => célula]],
 *     'detalhes' => [chave => ['titulo', 'colunas', 'linhas']]  — o DRILL-DOWN: a lista que compõe um número.
 * Célula = texto, ou ['t' => texto, 'url' => link, 'detalhe' => chave, 'classe' => css, 'o' => valor de ordenação].
 *
 * DRILL-DOWN (a soma da lista é o número): os números clicáveis são CONTADOS da própria lista do detalhe
 * (`self::contar`), nunca de uma consulta à parte.
 */
abstract class AdmsRelatorioBase
{
    public const CODIGO = '';
    public const NOME = '';
    public const CATEGORIA = '';
    public const PERGUNTA = '';
    public const SLUG = '';
    public const QUEM = '';

    /** Os filtros: 'curso' (padrão) ou 'chave' => ['rotulo', 'tipo' => select|data|numero, 'opcoes', 'padrao', 'obrigatorio']. */
    abstract public function filtros(): array;

    abstract public function ficha(): array;

    abstract public function dados(array $f): array;

    /** Os filtros resolvidos (padrão + próprios), na ordem declarada. */
    public function definicaoFiltros(): array
    {
        $saida = [];
        foreach ($this->filtros() as $k => $v) {
            if (is_int($k)) {
                $saida[$v] = AdmsRelatorios::filtroPadrao($v);
            } else {
                $saida[$k] = $v;
            }
        }
        /* Onda 6, Etapa 5: na escola, a turma e a etapa (AdmsRelatorios::FILTROS_ESCOLA) */
        if (\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola()) {
            foreach (AdmsRelatorios::FILTROS_ESCOLA[static::CODIGO] ?? [] as $k) {
                $saida[$k] ??= AdmsRelatorios::filtroPadrao($k);
            }
            /* Onda 8, correção, segunda leva, 2.5 (D-91): na escola, sem os filtros do ensino superior (a grade, o polo e a
               coorte de ingresso, quando são os padrão e não obrigatórios); o curso é o segmento da instituição */
            foreach (['grade', 'polo', 'coorte'] as $k) {
                if (isset($saida[$k]) && in_array($k, $this->filtros(), true) && empty($saida[$k]['obrigatorio'])) {
                    unset($saida[$k]);
                }
            }
            if (isset($saida['curso'])) {
                $saida['curso']['rotulo'] = 'Segmento';
            }
            if (($saida['de']['rotulo'] ?? '') === 'Ingresso de') {
                $saida['de']['rotulo'] = 'Matrícula de';
            }
        }
        return $saida;
    }

    /** Filtros obrigatórios que faltam (rótulos). */
    public function faltamObrigatorios(array $f): array
    {
        $faltam = [];
        foreach ($this->definicaoFiltros() as $k => $d) {
            if (!empty($d['obrigatorio']) && empty($f[$k])) {
                $faltam[] = $d['rotulo'];
            }
        }
        return $faltam;
    }

    /* --------------------------------------------------------- ajudas para as definições --------------------------------------------------------- */

    protected static function cel(string $t, array $o = []): array
    {
        return ['t' => $t] + $o;
    }

    protected static function col(string $chave, string $titulo, array $o = []): array
    {
        /* Correção da escola, Parte 4 (C1, C2; D-103): na escola, os rótulos da escola — o RM, o segmento, a disciplina, a
           unidade; no ensino superior, como sempre */
        if (\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola()) {
            $titulo = ['RA' => 'RM', 'Curso' => 'Segmento', 'Oferta' => 'Disciplina', 'Polo' => 'Unidade'][$titulo] ?? $titulo;
        }
        return ['chave' => $chave, 'titulo' => $titulo] + $o;
    }

    /** O nome do aluno com o atalho para a ficha (academicos/visualizar). */
    protected static function nomeAluno(array $r): array
    {
        return self::cel((string) $r['nome'], ['url' => URLADM . 'academicos/visualizar/' . (int) $r['usuario_id']]);
    }

    /** Quantos itens a lista do detalhe tem — o número do cartão/célula (a soma da lista é o número). */
    protected static function contar(array $detalhes, string $chave): int
    {
        return count($detalhes[$chave]['linhas'] ?? []);
    }

    /** Uma célula com o número que abre a lista (drill-down). */
    protected static function numero(array $detalhes, string $chave): array
    {
        $n = self::contar($detalhes, $chave);
        return self::cel((string) $n, $n > 0 ? ['detalhe' => $chave, 'o' => $n] : ['o' => 0]);
    }

    /** As colunas de uma lista de alunos (drill-down padrão). */
    protected static function colunasAluno(array $mais = []): array
    {
        return array_merge([self::col('ra', 'RA'), self::col('nome', 'Nome'), self::col('cpf', 'CPF'), self::col('curso', 'Curso')], $mais);
    }

    protected static function linhaAluno(array $mc, array $mais = []): array
    {
        return ['ra' => (string) $mc['rm'], 'nome' => self::nomeAluno($mc), 'cpf' => AdmsRelatorios::cpf($mc['cpf'] ?? null), 'curso' => (string) $mc['curso']] + $mais;
    }

    /** A oferta com o atalho para a tela de médias dela. */
    protected static function celOferta(array $o): array
    {
        return self::cel(AdmsRelatorioFonte::rotuloOferta($o), ['url' => URLADM . 'medias/final/' . (int) $o['id']]);
    }
}
