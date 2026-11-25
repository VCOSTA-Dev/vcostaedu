<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 5) . '/core/Vocabulario.php';
require_once dirname(__DIR__, 5) . '/core/HorarioPadrao.php';
require_once dirname(__DIR__, 5) . '/core/AtestadoEscola.php'; // D-102: o atestado da escola, só na escola
require_once dirname(__DIR__, 5) . '/core/TipoLivre.php'; // D-106: o curso livre

/**
 * O TIPO DA INSTITUIÇÃO e os MÓDULOS que ele liga (docs/ESCOLA.md, D-45).
 *
 * O tipo é a chave "instituicao.tipo" de adms_instalacao_estado (a migration 0025 grava "superior" em toda instalação
 * que já existia; o instalador grava o escolhido). Sem a chave — ou antes da 0025 —, superior.
 *
 * O MÓDULO de uma página está em adms_paginas_modulo ("escola" ou "superior"); a página que não está lá é do núcleo.
 * O tipo liga o módulo do mesmo nome e desliga o outro: a rota de um módulo desligado é recusada (AdmsPaginas) e o
 * item sai do menu (AdmsMenus). As fases dos Primeiros passos usam a coluna `modulo` de adms_fases do mesmo jeito.
 */
class AdmsTipoInstituicao extends AdmsConn
{
    public const CHAVE = 'instituicao.tipo';

    /** @var array<int, string>|null página → módulo */
    private static ?array $modulos = null;

    public static function atual(): string
    {
        $t = (string) AdmsAmbiente::estado(self::CHAVE, \VcVocabulario::SUPERIOR);
        $t = \VcVocabulario::valido($t) ? $t : \VcVocabulario::SUPERIOR;
        if (session_status() === PHP_SESSION_ACTIVE && ($_SESSION['vc_instituicao_tipo'] ?? '') !== $t) {
            $_SESSION['vc_instituicao_tipo'] = $t; // a página Sobre (que não lê o banco) mostra o tipo a quem entrou
        }
        return $t;
    }

    public static function ehEscola(): bool
    {
        return self::atual() === \VcVocabulario::ESCOLA;
    }

    public static function nome(): string
    {
        return \VcVocabulario::nome(self::atual());
    }

    /** Fechamento (D-106): a instalação é um curso livre? */
    public static function ehLivre(): bool
    {
        return self::atual() === \VcVocabulario::LIVRE;
    }

    /** D-106: a estrutura é por turma (a escola e o curso livre)? Os pontos estruturais da escola valem também no curso livre. */
    public static function porTurma(): bool
    {
        return \VcVocabulario::porTurma(self::atual());
    }

    /**
     * O módulo está ligado nesta instalação? (null = o núcleo, sempre ligado). D-106: o módulo é uma lista com vírgula
     * ("escola,livre": a página vale na escola e no curso livre; "escola,superior": em todos, menos no curso livre).
     */
    public static function moduloLigado(?string $modulo): bool
    {
        return \VcVocabulario::moduloNoTipo($modulo, self::atual());
    }

    /** A página está num módulo ligado e, no curso livre, num recurso ligado? (antes da 0025, todas) */
    public static function paginaLigada(int $paginaId): bool
    {
        if (!self::moduloLigado(self::modulos()[$paginaId] ?? null)) {
            return false;
        }
        $recurso = self::recursos()[$paginaId] ?? null;
        return $recurso === null || self::recursoLigado($recurso);
    }

    /** @var array<int, string>|null página → recurso (D-106) */
    private static ?array $recursos = null;

    /** Página → recurso do curso livre (adms_paginas_recurso, migration 0087). */
    public static function recursos(): array
    {
        if (self::$recursos !== null) {
            return self::$recursos;
        }
        self::$recursos = [];
        if (!AdmsAtualizacaoPendente::exige('0087')) {
            return self::$recursos;
        }
        try {
            $pdo = (new self())->connect();
            foreach ($pdo->query('SELECT adms_pagina_id, recurso FROM adms_paginas_recurso')->fetchAll(\PDO::FETCH_NUM) as [$id, $r]) {
                self::$recursos[(int) $id] = (string) $r;
            }
        } catch (\Throwable $e) {
            self::$recursos = [];
        }
        return self::$recursos;
    }

    /**
     * D-106: o recurso (financeiro, matricula) está ligado? Na escola e no ensino superior, sempre (nada muda); no curso
     * livre, a chave livre.<recurso> de adms_instalacao_estado ('1'/'0'; sem a chave, o padrão de RECURSOS_LIVRE).
     */
    public static function recursoLigado(string $recurso): bool
    {
        if (!self::ehLivre() || !isset(\VcVocabulario::RECURSOS_LIVRE[$recurso])) {
            return true;
        }
        $v = AdmsAmbiente::estado('livre.' . $recurso, null);
        return $v === null ? (bool) \VcVocabulario::RECURSOS_LIVRE[$recurso][2] : (string) $v === '1';
    }

    /** Grava os recursos do curso livre (o chamador conferiu a permissão). @param array<string, bool> $ligados */
    public static function gravarRecursos(array $ligados): void
    {
        foreach (\VcVocabulario::RECURSOS_LIVRE as $r => $x) {
            AdmsAmbiente::gravarEstado('livre.' . $r, !empty($ligados[$r]) ? '1' : '0');
        }
    }

    /** @return array<int, string> */
    public static function modulos(): array
    {
        if (self::$modulos !== null) {
            return self::$modulos;
        }
        self::$modulos = [];
        if (!AdmsAtualizacaoPendente::exige('0025')) {
            return self::$modulos;
        }
        try {
            $pdo = (new self())->connect();
            foreach ($pdo->query('SELECT adms_pagina_id, modulo FROM adms_paginas_modulo')->fetchAll(\PDO::FETCH_NUM) as [$id, $m]) {
                self::$modulos[(int) $id] = (string) $m;
            }
        } catch (\Throwable $e) {
            self::$modulos = [];
        }
        return self::$modulos;
    }

    /**
     * Correção da escola, Parte 4 (C1; D-103): o rótulo do número do aluno — "RM" (o número de matrícula) na escola,
     * "RA" no ensino superior. Um rótulo só para toda tela; o campo `ra` da interface de integração não muda.
     */
    public static function rotuloNumero(): string
    {
        return self::ehEscola() ? 'RM' : (self::ehLivre() ? 'Matrícula' : 'RA'); // D-106: no curso livre, a matrícula
    }

    /** O rótulo no vocabulário do tipo desta instalação (texto puro). */
    public static function traduzir(string $texto): string
    {
        return \VcVocabulario::traduzir($texto, self::atual());
    }

    /** O mesmo, num trecho de HTML (só o texto entre as marcas). */
    public static function traduzirHtml(string $html): string
    {
        return \VcVocabulario::traduzirHtml($html, self::atual());
    }

    public static function termo(string $conceito, bool $plural = false, bool $maiuscula = false): string
    {
        return \VcVocabulario::termo($conceito, self::atual(), $plural, $maiuscula);
    }

    /**
     * O tipo pode ser trocado? Só sem dado próprio — o mesmo critério que dispensa a senha ao montar a demonstração
     * (AdmsDemonstracaoMontagem::semDadoProprio) — e com a demonstração desligada (os dados dela são de um tipo).
     * @return array{pode: bool, motivo: string}
     */
    public static function podeTrocar(): array
    {
        if (AdmsAmbiente::demonstracaoAtiva()) {
            return ['pode' => false, 'motivo' => 'A demonstração está montada, com dados de um tipo de instituição. Remova a demonstração: depois, a escolha aparece aqui.'];
        }
        if (!(new \App\adms\Models\demonstracao\AdmsDemonstracaoMontagem())->semDadoProprio()) {
            return ['pode' => false, 'motivo' => 'A instalação já tem dados próprios (cursos, matérias, alunos ou pessoas da equipe), que dependem do tipo. Por isso o tipo não muda mais.'];
        }
        return ['pode' => true, 'motivo' => ''];
    }

    /** Grava o tipo (o chamador conferiu podeTrocar). */
    public static function gravar(string $tipo): void
    {
        if (!\VcVocabulario::valido($tipo)) {
            throw new \InvalidArgumentException('tipo de instituição desconhecido');
        }
        AdmsAmbiente::gravarEstado(self::CHAVE, $tipo);
        self::$modulos = null;
        self::$recursos = null;
        \VcTipoLivre::aplicar((new self())->connect(), $tipo); // D-106: o portal e a entrada do Moodle no curso livre
        \VcHorarioPadrao::aplicar((new self())->connect(), $tipo); // D-86: as aulas do dia padrão só na escola
        \VcAtestadoEscola::aplicar((new self())->connect(), $tipo); // D-102: o atestado de matrícula da escola
    }
}
