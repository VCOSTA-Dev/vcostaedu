<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorios as Reg;

/**
 * 1.17.0 — Menu RELATÓRIOS (D-453 a D-460). Rotas 435 a 455 e, na 1.17.1 (onda 2), 456 a 462 (adms_paginas); cada relatório é um método pequeno que
 * chama exibir(código), e a definição está em Models/relatorios. A permissão de cada rota é a comum (adms_nivacs_pgs):
 * sem ela, o GET é recusado antes de chegar aqui. Os relatórios só leem.
 */
class Relatorios
{
    private $dados = [];

    /** O hub: os relatórios do perfil, por categoria, e os salvos. */
    public function listar()
    {
        $this->base(435);
        $this->dados['porCategoria'] = Reg::doPerfil();
        $this->dados['salvos'] = Reg::salvos();
        $this->dados['podeParametros'] = Reg::pode('parametros');
        (new \Core\ConfigView('adms/Views/relatorios/hub', $this->dados))->renderizar('tabler');
    }

    public function painelAcademico() { $this->exibir('R-PAI-01', 441); }
    public function alunosSituacao() { $this->exibir('R-SEC-01', 442); }
    public function listaTurma() { $this->exibir('R-SEC-02', 443); }
    public function previsaoConclusao() { $this->exibir('R-SEC-03', 444); }
    public function qualidadeCadastro() { $this->exibir('R-SEC-04', 445); }
    public function mapaNotas() { $this->exibir('R-DES-01', 446); }
    public function resultadoDisciplina() { $this->exibir('R-DES-02', 447); }
    public function distribuicaoNotas() { $this->exibir('R-DES-03', 448); }
    public function pendenciasLancamento() { $this->exibir('R-DES-04', 449); }
    public function desempenhoAluno() { $this->exibir('R-DES-05', 450); }
    public function frequenciaOferta() { $this->exibir('R-FRE-01', 451); }
    public function frequenciaMinimo() { $this->exibir('R-FRE-02', 452); }
    public function alunosRisco() { $this->exibir('R-EVA-01', 453); }
    public function aptosConcluir() { $this->exibir('R-EVA-03', 454); }
    public function situacaoTcc() { $this->exibir('R-TCC-01', 455); }
    /* 1.17.1: onda 2 */
    public function coorteIngresso() { $this->exibir('R-EVA-02', 456); }
    public function concluintes() { $this->exibir('R-EVA-04', 457); }
    public function documentosEmitidos() { $this->exibir('R-DOC-01', 458); }
    public function reaberturas() { $this->exibir('R-AUD-01', 459); }
    public function trocasRegra() { $this->exibir('R-AUD-02', 460); }
    public function exportacoes() { $this->exibir('R-AUD-03', 461); }
    public function usuariosAcesso() { $this->exibir('R-AUD-04', 462); }
    /* Onda 7: os responsáveis (só na escola) */
    public function responsaveisFaltando() { $this->exibir('R-SEC-05', 550); }
    public function acessosAdicionais() { $this->exibir('R-AUD-05', 551); }
    /* Onda 8, financeiro, Parte 5 (D-98): o caixa do dia e os recebimentos por período */
    public function caixa() { $this->exibir('R-FIN-01', 573); }
    public function recebimentos() { $this->exibir('R-FIN-02', 574); }

    /** A permissão "ver CPF completo" não tem tela. */
    public function cpfCompleto()
    {
        header('Location: ' . URLADM . 'relatorios/listar');
    }

    private function base(int $pagina): void
    {
        $this->dados['paginaAtiva'] = $pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
    }

    private function exibir(string $codigo, int $pagina): void
    {
        $def = Reg::definicao($codigo);
        if (!$def) {
            header('Location: ' . URLADM . 'relatorios/listar');
            return;
        }
        $this->base($pagina);
        $avisos = [];
        $defs = $def->definicaoFiltros();
        $f = Reg::lerFiltros($defs, $avisos);
        $faltam = $def->faltamObrigatorios($f);
        \App\adms\Models\relatorios\AdmsRelatorioFonte::filtrosEscola($f); // Onda 6, Etapa 5: a etapa da escola
        /* Onda 6, Etapa 4: com muitas ofertas, as notas são calculadas em passos (a janela de progresso, POST no mesmo
           endereço do relatório: a mesma permissão), e a página abre com ?calc= (os cálculos guardados) */
        $Calc = \App\adms\Models\relatorios\AdmsRelatorioCalculo::class;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['fase'])) {
            header('Content-Type: application/json; charset=UTF-8');
            /* a execução vem no endereço da janela (?calc=): a janela só manda os campos no primeiro pedido, não nos passos */
            $x = (string) ($_POST['execucao'] ?? filter_input(INPUT_GET, 'calc', FILTER_DEFAULT) ?? '');
            $e = $Calc::estado($x);
            echo json_encode(($_POST['fase'] === 'iniciar' && $e) ? $Calc::publico($e) : $Calc::passo($x), JSON_UNESCAPED_UNICODE);
            return;
        }
        $calculadoEm = null;
        $execucao = (string) filter_input(INPUT_GET, 'calc', FILTER_DEFAULT);
        if (!$faltam && $execucao !== '') {
            $calculadoEm = $Calc::carregar($execucao);
        }
        if (!$faltam && $calculadoEm === null && ($nova = $Calc::preparar($codigo, fn() => $def->dados($f)))) {
            $q = array_diff_key($_GET, ['url' => 1, 'calc' => 1]) + ['calc' => $nova];
            $e = $Calc::estado($nova);
            $this->dados += ['def' => $def, 'execucao' => $nova, 'ofertas' => (int) $e['ofertas'], 'total' => (int) $e['total'],
                'urlRelatorio' => URLADM . 'relatorios/' . $def::SLUG . '?' . http_build_query($q), // os passos (POST), com a execução
                'urlFim' => URLADM . 'relatorios/' . $def::SLUG . '?' . http_build_query($q)];
            (new \Core\ConfigView('adms/Views/relatorios/calculando', $this->dados))->renderizar('tabler');
            return;
        }
        $inicio = microtime(true);
        /* Desempenho (D-460): a consulta roda numa conexão só (dentro de Transacao::executar todos os helpers usam o mesmo PDO,
           em vez de abrir uma conexão por consulta) e lê um retrato consistente do banco. O relatório só lê: nada é gravado. */
        $r = $faltam ? [] : \Core\Transacao::executar(fn() => $def->dados($f) ?: ['avisos' => []]);
        if ($r === false) {
            $r = ['avisos' => ['Não foi possível gerar o relatório: ' . \Core\Transacao::ultimoErro() . '.']];
        }
        $this->dados += [
            'def' => $def, 'defsFiltros' => $defs, 'filtros' => $f, 'faltam' => $faltam, 'r' => $r,
            'avisos' => array_merge($avisos, $r['avisos'] ?? []), 'textoFiltros' => Reg::textoFiltros($defs, $f),
            'detalhe' => preg_replace('/[^a-z0-9_:-]/', '', (string) filter_input(INPUT_GET, 'detalhe', FILTER_DEFAULT)),
            'colunasVisiveis' => array_filter(explode(',', (string) filter_input(INPUT_GET, 'colunas', FILTER_DEFAULT))),
            'salvos' => Reg::salvos($codigo), 'perfis' => Reg::perfis(), 'salvoAtual' => (int) filter_input(INPUT_GET, 'salvo', FILTER_VALIDATE_INT),
            'tempo' => microtime(true) - $inicio, 'carregarRelatorio' => true, 'cpfCompleto' => Reg::verCpfCompleto(),
            'calculadoEm' => $calculadoEm,
        ];
        (new \Core\ConfigView('adms/Views/relatorios/relatorio', $this->dados))->renderizar('tabler');
    }

    /** POST: salva o relatório (filtros + colunas visíveis), pessoal ou compartilhado com um perfil. */
    public function salvar()
    {
        $d = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        [$ok, $msg, $id] = Reg::salvar($d) + [2 => 0];
        $_SESSION['msg'] = "<div class='alert alert-" . ($ok ? 'success' : 'danger') . " alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>" . htmlspecialchars($msg) . '</div>';
        $def = Reg::definicao((string) ($d['relatorio'] ?? ''));
        if ($ok && $def) {
            parse_str(ltrim((string) ($d['filtros'] ?? ''), '?'), $q);
            $q = array_diff_key($q, ['salvo' => 1, 'calc' => 1]) + ['salvo' => $id]; // o cálculo em partes não se salva
            if (!empty($d['colunas'])) {
                $q['colunas'] = (string) $d['colunas'];
            }
            header('Location: ' . URLADM . 'relatorios/' . $def::SLUG . '?' . http_build_query($q));
            return;
        }
        header('Location: ' . URLADM . 'relatorios/' . ($def ? $def::SLUG . '?' . ltrim((string) ($d['filtros'] ?? ''), '?') : 'listar'));
    }

    /** POST: apaga um salvo (só o dono). */
    public function apagarSalvo()
    {
        [$ok, $msg] = Reg::apagarSalvo((int) filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT));
        $_SESSION['msg'] = "<div class='alert alert-" . ($ok ? 'success' : 'danger') . " alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>" . htmlspecialchars($msg) . '</div>';
        header('Location: ' . URLADM . 'relatorios/listar');
    }

    /** POST (JSON): registra a exportação ANTES de o navegador gerar o arquivo; sem registro, não exporta (D-457). */
    public function exportar()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false]);
            return;
        }
        $ok = Reg::registrarExportacao(filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: []);
        http_response_code($ok ? 200 : 403);
        echo json_encode(['ok' => $ok]);
    }

    /** Os limites do alerta de risco (R-EVA-01), em adms_parametros (risco.*). */
    public function parametros()
    {
        $chaves = ['risco.faltas_margem' => [0, 50], 'risco.min_notas' => [1, 50], 'risco.sem_nota_pct' => [1, 100]];
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $d = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
            $erros = [];
            foreach ($chaves as $k => [$min, $max]) {
                $campo = str_replace('.', '_', $k);
                $v = trim((string) ($d[$campo] ?? ''));
                if (!preg_match('/^\d{1,3}$/', $v) || (int) $v < $min || (int) $v > $max) {
                    $erros[] = "valor inválido em {$k} (de {$min} a {$max})";
                }
            }
            if (!$erros) {
                $ok = \Core\Transacao::executar(function () use ($chaves, $d) {
                    foreach (array_keys($chaves) as $k) {
                        \Core\Transacao::exigir(\App\adms\Models\helper\AdmsParametro::gravar($k, (string) (int) $d[str_replace('.', '_', $k)], (int) $_SESSION['colUsuario_id']), "gravar {$k}");
                    }
                    return true;
                });
                if ($ok) {
                    (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(439, 0, 2, 'Parâmetros do alerta de risco alterados');
                }
                $_SESSION['msg'] = $ok ? "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Parâmetros do alerta de risco salvos.</div>" : "<div class='alert alert-danger'>Não foi possível salvar: " . htmlspecialchars(\Core\Transacao::ultimoErro()) . '</div>';
            } else {
                $_SESSION['msg'] = "<div class='alert alert-danger'>" . htmlspecialchars(ucfirst(implode('; ', $erros))) . '.</div>';
            }
            header('Location: ' . URLADM . 'relatorios/parametros');
            return;
        }
        $this->base(439);
        $this->dados['valores'] = \App\adms\Models\relatorios\AdmsRelatorioFonte::risco();
        (new \Core\ConfigView('adms/Views/relatorios/parametros', $this->dados))->renderizar('tabler');
    }
}
