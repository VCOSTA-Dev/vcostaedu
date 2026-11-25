<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\financeiro\AdmsFinanceiro as F;
use App\adms\Models\financeiro\AdmsFinFicha as Ficha;
use App\adms\Models\financeiro\AdmsFinOperacoes as O;
use App\adms\Models\financeiro\AdmsFinRecibos as R;
use App\adms\Models\financeiro\AdmsFinRenegociacao as RN;
use App\adms\Models\financeiro\AdmsFinTitulos as T;

/**
 * A FICHA FINANCEIRA DO ALUNO (Onda 8, financeiro refeito; D-98, D-99; docs/FINANCEIRO.md).
 *   ficha                o desenho da de origem: as abas do alto (Ficha Financeira, Renegociações, Histórico), o aluno e os
 *                        responsáveis do ano, os totais, o resumo, as abas da lista, os filtros, o cartão Em lote e a tabela;
 *   receberPagamento     a tela de Receber, de um título (pelo menu) ou de vários (pelo modo lote);
 *   receber              POST: grava o recibo (os títulos, a data, o acréscimo, o desconto, a justificativa, as formas);
 *   recibo               o recibo, para imprimir, em duas vias (o estornado sai com a faixa);
 *   desfazerRecebimento  o recibo inteiro: a data, o motivo da lista e a justificativa;
 *   titulo               Visualizar: os dados do título, os recibos e o histórico dele;
 *   buscar               JSON: outro aluno, pelo nome, pelo RA ou pelo responsável;
 *   renegociarTitulos    a tela de Renegociar (D-105): os títulos marcados no modo lote, a data, os quadros e as duas condições;
 *   renegociar           POST: salvar a proposta ou efetivar uma das condições; efetivarProposta: a proposta salva, depois;
 *   impressoRenegociacao o PDF da proposta, do acordo efetivado ou do termo de confissão de dívida;
 *   avulso, aplicarPlano, baixar, cancelar, desfazer, desfazerRenegociacao, atendimento: como na 1.92.0
 *                        (baixar e cancelar aceitam também os títulos marcados no modo lote).
 */
class Financeiro
{
    private array $dados = [];

    private function aluno(int $id): ?array
    {
        $st = F::pdo()->prepare('SELECT id, nome, nome_social, rm FROM col_usuarios WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    private function usuario(): int
    {
        return (int) ($_SESSION['colUsuario_id'] ?? 0);
    }

    private function post(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    private function mensagem(string $erro, string $ok, string $id): void
    {
        $_SESSION['msg'] = $erro !== ''
            ? "<div class='alert alert-danger' id='financeiroRecusado'>" . htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') . '</div>'
            : "<div class='alert alert-success alert-dismissible' id='{$id}'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>" . htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') . '</div>';
    }

    /** De volta à ficha (com a aba do alto e o recorte de onde veio, quando vieram no formulário). */
    private function voltar(int $aluno, string $erro, string $ok, string $id, string $ver = ''): void
    {
        $this->mensagem($erro, $ok, $id);
        $volta = (string) ($_POST['voltar'] ?? '');
        $q = $volta !== '' && preg_match('/^[a-z_=&0-9%\-]+$/i', $volta) ? '?' . $volta : ($ver !== '' ? '?ver=' . $ver : '');
        header('Location: ' . URLADM . 'financeiro/ficha/' . $aluno . $q);
    }

    private function base(int $pagina = 46): void
    {
        $this->dados['paginaAtiva'] = $pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
    }

    public function ficha($id = null): void
    {
        $a = $this->aluno((int) $id);
        if (!$a) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Aluno não encontrado.</div>";
            header('Location: ' . URLADM . 'academicos/listar');
            return;
        }
        $this->base();
        $ver = in_array($_GET['ver'] ?? '', ['renegociacoes', 'historico'], true) ? $_GET['ver'] : 'ficha';
        $this->dados['aluno'] = $a;
        $this->dados['ver'] = $ver;
        $this->dados['ficha'] = Ficha::montar((int) $a['id'], $_GET);
        $this->dados['matriculas'] = T::matriculas((int) $a['id']);
        $this->dados['planos'] = T::planos(true);
        $this->dados['regras'] = F::regras();
        $this->dados['todosTitulos'] = Ficha::titulos((int) $a['id'], date('Y-m-d'));
        $this->dados['renegociacoes'] = RN::lista((int) $a['id']); // D-105: os acordos e as propostas
        $this->dados['motivosDesfazerRen'] = R::motivos('desfazer_renegociacao');
        $this->dados['historico'] = O::historico((int) $a['id']);
        (new \Core\ConfigView('adms/Views/financeiro/ficha', $this->dados))->renderizar('tabler');
    }

    public function avulso($id = null): void
    {
        $aluno = (int) $id;
        if (!$this->post()) {
            header('Location: ' . URLADM . 'financeiro/ficha/' . $aluno);
            return;
        }
        $erro = T::avulso($aluno, $_POST, $this->usuario());
        $this->voltar($aluno, $erro, 'Título lançado.', 'avulsoLancado');
    }

    public function aplicarPlano($id = null): void
    {
        $aluno = (int) $id;
        if (!$this->post()) {
            header('Location: ' . URLADM . 'financeiro/ficha/' . $aluno);
            return;
        }
        $mc = (int) ($_POST['matricula_curso_id'] ?? 0);
        $ok = in_array($mc, array_map('intval', array_column(T::matriculas($aluno), 'id')), true);
        $r = $ok ? T::gerarDoPlano($mc, (int) ($_POST['plano_id'] ?? 0), $this->usuario()) : ['criadas' => 0, 'erro' => 'Escolha uma matrícula do aluno.'];
        $this->voltar($aluno, $r['erro'], $r['criadas'] . ' parcela(s) gerada(s)' . ($r['criadas'] === 0 ? ' (as do plano já estavam na ficha)' : '') . '.', 'planoAplicado');
    }

    /** GET, em JSON: os encargos do título numa data (a tela recalcula ao mudar a data; o servidor recalcula de novo ao gravar). */
    public function encargos($id = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $st = F::pdo()->prepare('SELECT valor_centavos, vencimento, situacao, sem_multa FROM fin_titulos WHERE id = ?');
        $st->execute([(int) $id]);
        $t = $st->fetch(\PDO::FETCH_ASSOC);
        $data = (string) ($_GET['data'] ?? date('Y-m-d'));
        if (!$t || !\DateTime::createFromFormat('Y-m-d', $data)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'erro' => 'Título ou data inválidos.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $e = F::encargosTitulo($t, $data); // a parcela do acordo não paga multa (D-105)
        echo json_encode(['ok' => true] + $e + ['texto' => ['original' => F::brl($e['original']), 'multa' => F::brl($e['multa']), 'juros' => F::brl($e['juros']),
            'desconto' => F::brl($e['desconto']), 'total' => F::brl($e['total'])]], JSON_UNESCAPED_UNICODE);
    }

    /** O aluno de um título (a ação só vale para um título dele). */
    private function tituloDoAluno(int $tituloId): int
    {
        $st = F::pdo()->prepare('SELECT aluno_id FROM fin_titulos WHERE id = ?');
        $st->execute([$tituloId]);
        return (int) $st->fetchColumn();
    }

    /** RECEBER PAGAMENTO (a tela): ?titulos=1,2,3 (do menu, um; do modo lote, vários) e ?data=AAAA-MM-DD. */
    public function receberPagamento($id = null): void
    {
        $a = $this->aluno((int) $id);
        $ids = array_values(array_filter(array_map('intval', explode(',', (string) ($_GET['titulos'] ?? '')))));
        $data = (string) ($_GET['data'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || $data > date('Y-m-d')) {
            $data = date('Y-m-d');
        }
        if (!$a) {
            header('Location: ' . URLADM . 'academicos/listar');
            return;
        }
        $c = R::conta((int) $a['id'], $ids, $data);
        if (!$c['ok']) {
            $this->mensagem($c['erro'], '', '');
            header('Location: ' . URLADM . 'financeiro/ficha/' . (int) $a['id']);
            return;
        }
        $this->base();
        $this->dados['aluno'] = $a;
        $this->dados['conta'] = $c;
        $this->dados['data'] = $data;
        $this->dados['cabecalho'] = Ficha::anoDoAluno((int) $a['id'], (int) $c['ano']);
        $this->dados['pagador'] = R::pagador((int) $a['id'], (int) $c['ano']);
        $this->dados['bandeiras'] = F::pdo()->query('SELECT id, nome FROM fin_bandeiras WHERE situacao = 1 ORDER BY ordem, nome')->fetchAll(\PDO::FETCH_ASSOC);
        (new \Core\ConfigView('adms/Views/financeiro/receber', $this->dados))->renderizar('tabler');
    }

    /** POST: Receber (um ou vários títulos; grava o recibo). "emitir" leva ao recibo; senão, de volta à ficha. */
    public function receber($id = null): void
    {
        $aluno = (int) $id;
        if (!$this->post() || !$this->aluno($aluno)) {
            header('Location: ' . URLADM . ($aluno ? 'financeiro/ficha/' . $aluno : 'academicos/listar'));
            return;
        }
        $r = R::receber($aluno, $_POST, $this->usuario());
        if (!$r['ok']) {
            $this->mensagem($r['erro'], '', '');
            $ids = implode(',', array_map('intval', (array) ($_POST['titulos'] ?? [])));
            header('Location: ' . URLADM . 'financeiro/receber-pagamento/' . $aluno . '?titulos=' . $ids . '&data=' . urlencode((string) ($_POST['data'] ?? '')));
            return;
        }
        $rec = R::recibo($r['recibo']);
        $ok = 'Recebimento gravado: recibo nº ' . $rec['numero'] . ', R$ ' . F::brl((int) $rec['total_centavos']) . '. Os títulos estão recebidos e entraram no caixa de hoje de quem recebeu.';
        if (($_POST['depois'] ?? '') === 'recibo') {
            $this->mensagem('', $ok, 'recebimentoGravado');
            header('Location: ' . URLADM . 'financeiro/recibo/' . $r['recibo'] . '?novo=1');
            return;
        }
        $this->voltar($aluno, '', $ok, 'recebimentoGravado');
    }

    /** O recibo, para imprimir (duas vias). */
    public function recibo($id = null): void
    {
        $r = R::recibo((int) $id);
        if (!$r) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Recibo não encontrado.</div>";
            header('Location: ' . URLADM . 'academicos/listar');
            return;
        }
        $this->dados['recibo'] = $r;
        $this->dados['instituicao'] = $this->instituicao();
        /* a página de imprimir é inteira e sem o menu (duas vias numa folha) */
        include 'app/adms/Views/financeiro/recibo.php';
    }

    /** Os dados da instituição (o cadastro dela) para o recibo. */
    private function instituicao(): array
    {
        return \App\adms\Models\helper\AdmsInstituicao::dados() ?: [];
    }

    /** DESFAZER RECEBIMENTO: GET a tela (o recibo e o formulário); POST desfaz o recibo inteiro. */
    public function desfazerRecebimento($id = null): void
    {
        $r = R::recibo((int) $id);
        if (!$r) {
            header('Location: ' . URLADM . 'academicos/listar');
            return;
        }
        $aluno = (int) $r['aluno_id'];
        if ($this->post()) {
            $res = R::estornar((int) $r['id'], $_POST, $this->usuario());
            if (!$res['ok']) {
                $this->mensagem($res['erro'], '', '');
                header('Location: ' . URLADM . 'financeiro/desfazer-recebimento/' . (int) $r['id']);
                return;
            }
            $this->voltar($aluno, '', 'Desfazer recebimento: feito. O recibo nº ' . $r['numero'] . ' ficou estornado e os títulos dele voltaram a aberto.', 'desfazerGravado');
            return;
        }
        $this->base();
        $this->dados['recibo'] = $r;
        $this->dados['motivos'] = R::motivos('desfazer_recebimento');
        (new \Core\ConfigView('adms/Views/financeiro/desfazerRecebimento', $this->dados))->renderizar('tabler');
    }

    /** VISUALIZAR: os dados do título, os recibos e o histórico dele. */
    public function titulo($id = null): void
    {
        $st = F::pdo()->prepare('SELECT t.*, p.nome AS plano, u.nome AS criador FROM fin_titulos t LEFT JOIN fin_planos p ON p.id = t.plano_id LEFT JOIN adms_usuarios u ON u.id = t.criado_por WHERE t.id = ?');
        $st->execute([(int) $id]);
        $t = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$t) {
            header('Location: ' . URLADM . 'academicos/listar');
            return;
        }
        $this->base();
        $this->dados['titulo'] = $t;
        $this->dados['aluno'] = $this->aluno((int) $t['aluno_id']);
        $this->dados['recibos'] = R::doTitulo((int) $t['id']);
        $st = F::pdo()->prepare('SELECT o.*, u.nome AS usuario FROM fin_operacoes o LEFT JOIN adms_usuarios u ON u.id = o.usuario_id WHERE o.titulo_id = ? ORDER BY o.id DESC');
        $st->execute([(int) $t['id']]);
        $this->dados['operacoes'] = $st->fetchAll(\PDO::FETCH_ASSOC);
        (new \Core\ConfigView('adms/Views/financeiro/titulo', $this->dados))->renderizar('tabler');
    }

    /** GET, em JSON: a busca de outro aluno (o nome, o RA ou o responsável). */
    public function buscar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => true, 'alunos' => Ficha::buscar((string) ($_GET['q'] ?? ''))], JSON_UNESCAPED_UNICODE);
    }

    /** Baixar ou cancelar: um título (o id na rota) ou, do modo lote, os marcados (titulos[] e o aluno). */
    private function baixarOuCancelar(string $acao, $id, string $ok, string $idMsg): void
    {
        if (!$this->post()) {
            header('Location: ' . URLADM . 'academicos/listar');
            return;
        }
        if (!empty($_POST['titulos'])) {
            $aluno = (int) ($_POST['aluno'] ?? 0);
            if (!$this->aluno($aluno)) {
                header('Location: ' . URLADM . 'academicos/listar');
                return;
            }
            $r = O::baixarOuCancelarLote($acao, $aluno, $_POST, $this->usuario());
            $this->voltar($aluno, $r['erro'], $ok, $idMsg);
            return;
        }
        $aluno = $this->tituloDoAluno((int) $id);
        if (!$aluno) {
            header('Location: ' . URLADM . 'academicos/listar');
            return;
        }
        $r = O::baixarOuCancelar($acao, (int) $id, $_POST, $this->usuario());
        $this->voltar($aluno, $r['erro'], $ok, $idMsg);
    }

    /** POST: Baixar (sem entrada de dinheiro; o motivo é obrigatório). */
    public function baixar($id = null): void
    {
        $this->baixarOuCancelar('baixar', $id, 'Baixa gravada (sem entrada no caixa).', 'baixaGravada');
    }

    /** POST: Cancelar (o título deixa de ser devido; o motivo é obrigatório). */
    public function cancelar($id = null): void
    {
        $this->baixarOuCancelar('cancelar', $id, 'Cancelamento gravado.', 'cancelamentoGravado');
    }

    /** POST: Desfazer baixa ou cancelamento (tipo; o motivo é obrigatório; a permissão é a de desfazer). */
    public function desfazer($id = null): void
    {
        $tituloId = (int) $id;
        $aluno = $this->tituloDoAluno($tituloId);
        if (!$this->post() || !$aluno) {
            header('Location: ' . URLADM . ($aluno ? 'financeiro/ficha/' . $aluno : 'academicos/listar'));
            return;
        }
        $tipo = (string) ($_POST['tipo'] ?? '');
        $r = O::desfazer($tipo, $tituloId, $_POST, $this->usuario());
        $this->voltar($aluno, $r['erro'], (O::NOMES[$tipo] ?? 'Desfazer') . ': feito. O título voltou a aberto (a situação sai da data de hoje).', 'desfazerGravado');
    }

    /** RENEGOCIAR TÍTULOS (a tela, D-105): ?titulos=1,2,3 (os marcados no modo lote) e ?data=AAAA-MM-DD. */
    public function renegociarTitulos($id = null): void
    {
        $a = $this->aluno((int) $id);
        if (!$a) {
            header('Location: ' . URLADM . 'academicos/listar');
            return;
        }
        $ids = array_values(array_filter(array_map('intval', explode(',', (string) ($_GET['titulos'] ?? '')))));
        $data = (string) ($_GET['data'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || $data > date('Y-m-d')) {
            $data = date('Y-m-d');
        }
        $k = RN::conta((int) $a['id'], $ids, $data);
        if (!$k['ok']) {
            $this->mensagem($k['erro'], '', '');
            header('Location: ' . URLADM . 'financeiro/ficha/' . (int) $a['id'] . '?modo=renegociar');
            return;
        }
        $this->base();
        $this->dados['aluno'] = $a;
        $this->dados['conta'] = $k;
        $this->dados['cabecalho'] = Ficha::anoDoAluno((int) $a['id'], (int) $k['conta']['ano']);
        $this->dados['pagador'] = R::pagador((int) $a['id'], (int) $k['conta']['ano']);
        $this->dados['motivos'] = R::motivos('renegociar');
        (new \Core\ConfigView('adms/Views/financeiro/renegociar', $this->dados))->renderizar('tabler');
    }

    /** POST, da tela Renegociar títulos: salvar a proposta (acao=salvar) ou efetivar a condição (avista ou parcelada). */
    public function renegociar($id = null): void
    {
        $aluno = (int) $id;
        if (!$this->post() || !$this->aluno($aluno)) {
            header('Location: ' . URLADM . ($aluno ? 'financeiro/ficha/' . $aluno : 'academicos/listar'));
            return;
        }
        $r = RN::gravar($aluno, $_POST, $this->usuario());
        if (!$r['ok']) {
            $this->mensagem($r['erro'], '', '');
            $ids = implode(',', array_map('intval', (array) ($_POST['titulos'] ?? [])));
            header('Location: ' . URLADM . 'financeiro/renegociar-titulos/' . $aluno . '?titulos=' . $ids . '&data=' . urlencode((string) ($_POST['data'] ?? '')));
            return;
        }
        if ($r['renegociacao']) {
            $ac = RN::acordo($r['renegociacao']);
            $this->mensagem('', 'Acordo nº ' . $ac['numero'] . ' efetivado (' . mb_strtolower(RN::CONDICOES[$ac['condicao']] ?? '') . '): os títulos escolhidos ficaram "renegociado" e as parcelas do acordo estão na ficha.', 'renegociacaoGravada');
        } else {
            $p = RN::proposta($r['proposta']);
            $this->mensagem('', 'Proposta nº ' . $p['id'] . ' salva: vale até ' . date('d/m/Y', strtotime($p['data_renegociacao'])) . '. Para efetivá-la, use "Efetivar acordo" aqui, na aba Renegociações.', 'propostaSalva');
        }
        header('Location: ' . URLADM . 'financeiro/ficha/' . $aluno . '?ver=renegociacoes');
    }

    /** POST: Efetivar acordo — a proposta salva, numa das condições (condicao), sem refazer a conta. */
    public function efetivarProposta($id = null): void
    {
        $st = F::pdo()->prepare('SELECT aluno_id FROM fin_propostas WHERE id = ?');
        $st->execute([(int) $id]);
        $aluno = (int) $st->fetchColumn();
        if (!$this->post() || !$aluno) {
            header('Location: ' . URLADM . ($aluno ? 'financeiro/ficha/' . $aluno : 'academicos/listar'));
            return;
        }
        $r = RN::efetivarProposta((int) $id, $_POST, $this->usuario());
        $ac = $r['ok'] && $r['renegociacao'] ? RN::acordo($r['renegociacao']) : null;
        $this->voltar($aluno, $r['erro'], $ac ? 'Acordo nº ' . $ac['numero'] . ' efetivado, pela proposta nº ' . (int) $id . ': os títulos ficaram "renegociado" e as parcelas do acordo estão na ficha.' : '', 'acordoEfetivado', 'renegociacoes');
    }

    /** O PDF: ?tipo=proposta (o id é o da proposta), acordo ou termo (o id é o do acordo). */
    public function impressoRenegociacao($id = null): void
    {
        $tipo = in_array($_GET['tipo'] ?? '', ['proposta', 'acordo', 'termo'], true) ? (string) $_GET['tipo'] : 'acordo';
        $obj = $tipo === 'proposta' ? RN::proposta((int) $id) : RN::acordo((int) $id);
        $a = $obj ? $this->aluno((int) $obj['aluno_id']) : null;
        if (!$obj || !$a) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Renegociação não encontrada.</div>";
            header('Location: ' . URLADM . 'academicos/listar');
            return;
        }
        $ano = $tipo === 'proposta' ? (int) $obj['ano'] : (int) F::pdo()->query('SELECT MAX(ano) FROM fin_titulos WHERE renegociacao_id = ' . (int) $obj['id'])->fetchColumn();
        $pag = R::pagador((int) $a['id'], $ano ?: null);
        $pag['cpf'] = '';
        $pag['endereco'] = '';
        if ($pag['responsavel_id']) {
            $pessoa = \App\adms\Models\escola\AdmsResponsaveis::pessoa((int) $pag['responsavel_id']) ?: [];
            $pag['cpf'] = !empty($pessoa['cpf']) ? \App\adms\Models\escola\AdmsResponsaveis::cpfFormatado($pessoa['cpf']) : '';
            $pag['endereco'] = $pessoa ? \App\adms\Models\escola\AdmsResponsaveis::enderecoEmLinha($pessoa) : '';
        } else {
            $u = F::pdo()->prepare('SELECT * FROM col_usuarios WHERE id = ?');
            $u->execute([(int) $a['id']]);
            $linha = $u->fetch(\PDO::FETCH_ASSOC) ?: [];
            $pag['cpf'] = !empty($linha['cpf']) ? F::documentoFormatado((string) $linha['cpf']) : '';
        }
        $imp = ['tipo' => $tipo, 'aluno' => $a, 'pagador' => $pag, 'instituicao' => $this->instituicao(), 'rotuloNumero' => Ficha::rotuloNumero(),
            'demonstracao' => \App\adms\Models\helper\AdmsAmbiente::atual() === 'demonstracao',
            'proposta' => $tipo === 'proposta' ? $obj : null, 'acordo' => $tipo === 'proposta' ? null : $obj];
        $html = '';
        include 'app/adms/Views/financeiro/impressoRenegociacao.php';
        $d = new \Dompdf\Dompdf();
        $o = $d->getOptions();
        $o->set('isRemoteEnabled', false);
        $o->set('isPhpEnabled', false);
        $o->set('isJavascriptEnabled', false);
        $o->set('defaultFont', 'Helvetica');
        $d->setOptions($o);
        $d->loadHtml($html);
        $d->setPaper('A4', 'portrait');
        $d->render();
        $nome = ['proposta' => 'proposta-de-acordo-', 'acordo' => 'acordo-', 'termo' => 'termo-de-confissao-de-divida-'][$tipo] . ($tipo === 'proposta' ? (int) $obj['id'] : (int) $obj['numero']) . '.pdf';
        $d->stream($nome, ['Attachment' => false]);
    }

    /** POST: Desfazer renegociação (o id da renegociação; o motivo é obrigatório; a permissão é a de desfazer). */
    public function desfazerRenegociacao($id = null): void
    {
        $st = F::pdo()->prepare('SELECT aluno_id FROM fin_renegociacoes WHERE id = ?');
        $st->execute([(int) $id]);
        $aluno = (int) $st->fetchColumn();
        if (!$this->post() || !$aluno) {
            header('Location: ' . URLADM . ($aluno ? 'financeiro/ficha/' . $aluno : 'academicos/listar'));
            return;
        }
        $r = O::desfazerRenegociacao((int) $id, $_POST, $this->usuario());
        $this->voltar($aluno, $r['erro'], 'Desfazer renegociação: feito. As parcelas do acordo em aberto foram canceladas e os títulos originais voltaram a aberto.', 'renegociacaoDesfeita', 'renegociacoes');
    }

    /** POST: um atendimento (a data, o texto e, se quiser, o título). */
    public function atendimento($id = null): void
    {
        $aluno = (int) $id;
        if (!$this->post() || !$this->aluno($aluno)) {
            header('Location: ' . URLADM . 'financeiro/ficha/' . $aluno);
            return;
        }
        $r = O::atendimento($aluno, $_POST, $this->usuario());
        $this->voltar($aluno, $r['erro'], 'Atendimento registrado no histórico.', 'atendimentoGravado', 'historico');
    }
}
