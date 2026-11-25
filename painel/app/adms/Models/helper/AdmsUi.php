<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * COMPONENTES COMUNS DE TELA (E8c). O mesmo arquivo no painel e no portal. Documentação e exemplos:
 * docs/USABILIDADE_COMPONENTES.md. As regras (R1 a R9) estão em docs/CHECKLIST_ENTREGA.md.
 *
 *  - acao()       botão de ação por linha ou de topo (R3): pequeno, com ícone, dica e cor fixa por tipo;
 *                 com $razao, sai DESABILITADO e a razão vai na dica (R4) — nunca um botão ativo que dá erro;
 *  - acaoPost()   o mesmo, para ações por POST (formulário de um botão), com confirmação opcional;
 *  - barraDisciplina()  a barra única do topo das telas da disciplina/oferta (R5);
 *  - faixaTrava() a faixa de tela travada: por quê, e como destravar (R6).
 * Todo texto é escapado aqui; quem chama passa texto puro.
 */
class AdmsUi
{
    /**
     * Correção da escola, Parte 4 (C4; D-103): a quantidade com a palavra certa — "1 aluno", "3 alunos" (nunca "aluno(s)").
     * $um e $varios já concordados ("aluno enturmado", "alunos enturmados").
     */
    public static function plural(int $n, string $um, string $varios): string
    {
        return $n . ' ' . ($n === 1 ? $um : $varios);
    }

    /** tipo => [classe do botão, ícone, rótulo padrão] — cores fixas (R3). */
    public const TIPOS = [
        'ver'       => ['btn-outline-primary', 'far fa-eye', 'Visualizar'],
        'editar'    => ['btn-outline-warning', 'far fa-edit', 'Editar'],
        'excluir'   => ['btn-outline-danger', 'far fa-trash-alt', 'Apagar'],
        'imprimir'  => ['btn-outline-secondary', 'fas fa-print', 'Imprimir'],
        'pdf'       => ['btn-outline-secondary', 'far fa-file-pdf', 'PDF'],
        'boletim'   => ['btn-outline-info', 'fas fa-file-lines', 'Ver boletim do aluno'], // E8d (A23): ícone de documento, dica clara
        'atestado'  => ['btn-outline-info', 'fas fa-file-signature', 'Emitir atestado de matrícula'], // E6 (D-373)
        'novo'      => ['btn-success', 'fas fa-plus', 'Cadastrar'],
        'voltar'    => ['btn-outline-dark', 'fas fa-arrow-left', 'Voltar'],
        'compensar' => ['btn-outline-info', 'fas fa-notes-medical', 'Compensar faltas'],
        'faltas'    => ['btn-outline-secondary', 'fas fa-user-clock', 'Faltas'],
        'liberar'   => ['btn-outline-warning', 'fas fa-unlock', 'Liberar'],
        'inativar'  => ['btn-outline-secondary', 'fas fa-ban', 'Inativar'],        // E8d (P-8)
        'reativar'  => ['btn-outline-success', 'fas fa-rotate-left', 'Reativar'],   // E8d (P-8)
        'acao'      => ['btn-outline-secondary', 'fas fa-cog', 'Ação'],
    ];

    private static function h($s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param string      $tipo    chave de TIPOS
     * @param string      $url     destino (GET)
     * @param string|null $titulo  dica; padrão: o rótulo do tipo
     * @param string|null $razao   se preenchida, o botão sai desabilitado e esta é a dica (R4)
     * @param array       $o       'rotulo' => texto ao lado do ícone; 'icone', 'classe' => troca o padrão; 'nova_aba' => true; 'id'; 'dados' => [nome => valor] (data-*)
     */
    public static function acao(string $tipo, string $url, ?string $titulo = null, ?string $razao = null, array $o = []): string
    {
        [$classe, $icone, $rotulo] = self::TIPOS[$tipo] ?? self::TIPOS['acao'];
        $classe = $o['classe'] ?? $classe;
        $icone = $o['icone'] ?? $icone;
        $titulo = $titulo ?? $rotulo;
        $texto = '<i class="' . self::h($icone) . '"></i>' . (!empty($o['rotulo']) ? ' ' . self::h($o['rotulo']) : '');
        $id = !empty($o['id']) ? ' id="' . self::h($o['id']) . '"' : '';
        if ($razao !== null) {
            return '<span class="acao-desabilitada" tabindex="0" title="' . self::h($razao) . '" data-bs-toggle="tooltip"' . $id . '>'
                . '<button type="button" class="btn btn-sm btn-acao ' . self::h($classe) . '" disabled aria-disabled="true">' . $texto . '</button></span>';
        }
        $alvo = !empty($o['nova_aba']) ? ' target="_blank" rel="noopener"' : '';
        foreach ($o['dados'] ?? [] as $k => $v) { // E8c: atributos data-* (ex.: o texto da confirmação)
            $alvo .= ' data-' . self::h($k) . '="' . self::h($v) . '"';
        }
        return '<a href="' . self::h($url) . '" class="btn btn-sm btn-acao ' . self::h($classe) . '" title="' . self::h($titulo) . '" aria-label="' . self::h($titulo) . '"' . $alvo . $id . '>' . $texto . '</a>';
    }

    /**
     * E8c (R3/R4): ação que ABRE UM MODAL já existente na tela (ex.: o "Apagar" com confirmação). $dados: atributos
     * data-* (sem o prefixo "data-"), escapados aqui. Com $razao, sai desabilitado com a razão na dica.
     */
    public static function acaoModal(string $tipo, string $alvo, array $dados, ?string $titulo = null, ?string $razao = null, array $o = []): string
    {
        if ($razao !== null) {
            return self::acao($tipo, '#', $titulo, $razao, $o);
        }
        [$classe, $icone, $rotulo] = self::TIPOS[$tipo] ?? self::TIPOS['acao'];
        $classe = $o['classe'] ?? $classe;
        $icone = $o['icone'] ?? $icone;
        $titulo = $titulo ?? $rotulo;
        $attrs = '';
        foreach ($dados as $k => $v) {
            $attrs .= ' data-' . self::h($k) . '="' . self::h($v) . '"';
        }
        return '<button type="button" class="btn btn-sm btn-acao ' . self::h($classe) . '" title="' . self::h($titulo) . '" aria-label="' . self::h($titulo) . '" data-bs-toggle="modal" data-bs-target="' . self::h($alvo) . '"' . $attrs . '>'
            . '<i class="' . self::h($icone) . '"></i>' . (!empty($o['rotulo']) ? ' ' . self::h($o['rotulo']) : '') . '</button>';
    }

    /**
     * Ação por POST: um formulário com um botão. $campos: nome => valor (ocultos). 'confirmar' => a pergunta da confirmação do sistema (data-vc-confirma).
     */
    public static function acaoPost(string $tipo, string $url, array $campos, ?string $titulo = null, ?string $razao = null, array $o = []): string
    {
        if ($razao !== null) {
            return self::acao($tipo, '#', $titulo, $razao, $o);
        }
        [$classe, $icone, $rotulo] = self::TIPOS[$tipo] ?? self::TIPOS['acao'];
        $classe = $o['classe'] ?? $classe;
        $icone = $o['icone'] ?? $icone;
        $titulo = $titulo ?? $rotulo;
        $conf = !empty($o['confirmar']) ? ' data-vc-confirma="' . self::h($o['confirmar']) . '"' : '';
        $ocultos = '';
        foreach ($campos as $k => $v) {
            $ocultos .= '<input type="hidden" name="' . self::h($k) . '" value="' . self::h($v) . '">';
        }
        return '<form method="POST" action="' . self::h($url) . '" class="d-inline"' . $conf . '>' . $ocultos
            . '<button type="submit" class="btn btn-sm btn-acao ' . self::h($classe) . '" title="' . self::h($titulo) . '" aria-label="' . self::h($titulo) . '">'
            . '<i class="' . self::h($icone) . '"></i>' . (!empty($o['rotulo']) ? ' ' . self::h($o['rotulo']) : '') . '</button></form>';
    }

    /**
     * R5 — a MESMA barra no topo de toda tela da disciplina (oferta): Voltar à disciplina · Atividades · Notas e
     * resultado · Faltas · Encerramento, com a tela atual marcada e as ações da tela à direita.
     * $atual: 'disciplina' | 'atividades' | 'notas' | 'faltas' | 'encerramento'.
     * $pode: [chave => true | 'razão'] — a permissão de cada destino para o perfil (sem a chave = pode).
     * $extras: HTML já pronto (botões feitos com acao()/acaoPost()) para a direita da barra.
     */
    public static function barraDisciplina(int $ofertaId, string $atual, string $titulo = '', array $pode = [], string $extras = ''): string
    {
        $base = defined('URLADM') ? URLADM : '';
        /* Onda 3, Etapa 5: as telas da disciplina em ABAS, com a atual destacada (o padrão do projeto de referência: nav-tabs no
           alto de um cartão) — antes, um botão escuro entre botões de contorno, sem dizer onde a pessoa estava. A
           primeira aba é a própria disciplina (a ficha); o caminho de volta à lista é a seta do título. */
        $itens = [
            'disciplina'   => ['disciplinas/visualizar/', 'fas fa-book-open', 'Disciplina'],
            'atividades'   => ['atividades/listar/', 'fas fa-tasks', 'Atividades'],
            'notas'        => ['medias/final/', 'fas fa-calculator', 'Notas e resultado'],
            'faltas'       => ['faltas/visualizar/', 'fas fa-user-clock', 'Faltas'],
            'chamada'      => ['chamada/listar/', 'fas fa-clipboard-check', 'Chamada'], // 1.14.0 (D-406)
            'encerramento' => ['disciplinas/encerrar/', 'fas fa-lock', 'Encerramento'],
            'regra'        => ['regra-media/oferta/', 'fas fa-sliders-h', 'Regra de média'], // 1.13.0 (D-398)
        ];
        /* Onda 8, correção, Parte 1.2 (D-72): na escola, as etapas do componente (a recuperação, as faltas da etapa, fechar a
           etapa) entram na barra, logo depois da chamada: o professor chega lá pela tela do componente */
        if (\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola()) {
            $itens = array_slice($itens, 0, 5, true) + ['etapas' => ['escola-notas/etapas/', 'fas fa-layer-group', 'Etapas']] + array_slice($itens, 5, null, true);
        }
        $html = '<div class="barra-disciplina vc-abas no-print">';
        if ($titulo !== '') {
            $html .= '<span class="titulo-oferta">' . self::h($titulo) . '</span>';
        }
        $html .= '<ul class="nav nav-tabs vc-abas-lista" role="navigation" aria-label="Telas da disciplina">';
        foreach ($itens as $k => [$rota, $icone, $rotulo]) {
            $ok = $pode[$k] ?? true;
            $rotuloHtml = '<i class="' . $icone . '" aria-hidden="true"></i> ' . self::h($rotulo);
            if ($ok !== true) {
                /* sem permissão: a aba fica, apagada, com a razão na dica */
                $html .= '<li class="nav-item"><span class="nav-link disabled acao-desabilitada" tabindex="0" title="' . self::h((string) $ok) . '" data-bs-toggle="tooltip" aria-disabled="true">' . $rotuloHtml . '</span></li>';
                continue;
            }
            $url = $base . $rota . $ofertaId;
            $html .= $k === $atual
                ? '<li class="nav-item"><span class="nav-link active" aria-current="page">' . $rotuloHtml . '</span></li>'
                : '<li class="nav-item"><a href="' . self::h($url) . '" class="nav-link">' . $rotuloHtml . '</a></li>';
        }
        $html .= '</ul>';
        if ($extras !== '') {
            $html .= '<div class="vc-abas-acoes">' . $extras . '</div>';
        }
        return $html . '</div>';
    }

    /**
     * R6 — tela travada: diz POR QUE e COMO destravar. $acao = [rotulo, url] leva à ação (para quem pode);
     * sem $acao, $como diz a quem pedir.
     */
    public static function faixaTrava(string $porque, string $como, ?array $acao = null, string $id = 'faixaTrava'): string
    {
        $botao = $acao ? '<a href="' . self::h($acao[1]) . '" class="btn btn-sm btn-warning"><i class="fas fa-unlock"></i> ' . self::h($acao[0]) . '</a>' : '';
        return '<div class="alert alert-secondary faixa-trava" id="' . self::h($id) . '" role="status"><i class="fas fa-lock fa-lg"></i>'
            . '<div class="texto"><b>' . self::h($porque) . '</b><br>' . self::h($como) . '</div>' . $botao . '</div>';
    }

    /** Etiqueta (badge) de situação: $cores = [texto => classe]; desconhecido = secondary. */
    public static function etiqueta(string $texto, array $cores = []): string
    {
        $padrao = ['Aberta' => 'success', 'Encerrada' => 'secondary', 'Aprovado' => 'success', 'Em curso' => 'info', 'Ativo' => 'success', 'Inativo' => 'secondary',
            'Cursando' => 'info', 'Concluído' => 'success', 'Trancado' => 'warning', 'Cancelado' => 'danger', 'Transferido' => 'dark', 'Válida' => 'success', 'Cancelada' => 'danger'];
        $c = $cores[$texto] ?? $padrao[$texto] ?? (str_starts_with($texto, 'Reprovado') ? 'danger' : 'secondary');
        return '<span class="badge bg-' . self::h($c) . '-lt">' . self::h($texto) . '</span>'; // VCOSTAEDU (D-08): tom claro
    }
}
