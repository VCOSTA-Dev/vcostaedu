<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\escola\AdmsEtapaNotas as N;
use App\adms\Models\notas\AdmsCalculoNotas as Calc;
use App\adms\Models\vinculo\AdmsVinculoRegras as R;

/**
 * AS ETAPAS DE UM COMPONENTE (Onda 4, Etapa 3; docs/ESCOLA.md) — só na escola, sempre de UMA oferta (o professor só nas
 * dele: o escopo "oferta" da rota).
 *   etapas       os alunos × as etapas: a média, a recuperação, a nota da etapa, a média anual, a frequência e a situação;
 *                a etapa corrente em destaque, as fechadas travadas com a razão;
 *   recuperacao  POST: as notas da recuperação de uma etapa;
 *   faltas       POST: as faltas de uma etapa (o total da oferta é a soma);
 *   fechar       POST: fecha a etapa (a regra copiada, as notas guardadas);
 *   reabrir      POST: reabre a etapa, com o motivo (o fechamento substituído fica guardado).
 */
class EscolaNotas
{
    private array $dados = [];

    private function voltar(int $id, $r, string $ok, int $etapa = 0): void
    {
        $_SESSION['msg'] = R::aviso($r === true ? 'success' : 'danger', $r === true ? $ok : (string) $r);
        header('Location: ' . URLADM . 'escola-notas/etapas/' . $id . ($etapa ? '?etapa=' . $etapa : ''));
    }

    private function post(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    public function etapas($id = null): void
    {
        $id = (int) $id;
        $o = Calc::dadosOferta($id);
        if (!$o || !Calc::turmaDaOferta($id)) {
            $_SESSION['msg'] = R::aviso('danger', 'Disciplina não encontrada.');
            header('Location: ' . URLADM . 'turmas/listar');
            return;
        }
        $this->dados['paginaAtiva'] = 521;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['oferta'] = $o;
        $this->dados['turma'] = \App\adms\Models\escola\AdmsTurmas::ver((int) Calc::turmaDaOferta($id));
        $etapas = Calc::etapasDoAno((int) $o['ano']);
        foreach ($etapas as &$e) {
            $e['trava'] = N::trava($id, (int) $e['id']);
        }
        unset($e);
        $this->dados['etapas'] = $etapas;
        $corrente = Calc::etapaDaData($etapas, date('Y-m-d'));
        $this->dados['etapaAtual'] = (int) ($_GET['etapa'] ?? ($corrente['id'] ?? 0));
        $this->dados['corrente'] = (int) ($corrente['id'] ?? 0);
        $this->dados['quadro'] = N::quadro($id);
        /* D-100: o que foi digitado e recusado volta aos campos (uma vez) */
        $dig = $_SESSION['etapaDigitado'] ?? null;
        unset($_SESSION['etapaDigitado']);
        $this->dados['digitado'] = is_array($dig) && (int) $dig['oferta'] === $id && (int) $dig['etapa'] === $this->dados['etapaAtual'] ? (array) $dig['campos'] : [];
        $this->dados['conf'] = Calc::confEscola();
        $this->dados['barra'] = \App\adms\Models\notas\AdmsOfertaInfo::permissoesBarra(); // Onda 8, correção (D-72): a barra do componente
        $this->dados['atividades'] = N::atividadesPorEtapa($id, (int) $o['ano']); // Onda 8, correção (D-81): as atividades de cada etapa
        (new \Core\ConfigView('adms/Views/escolaNotas/etapas', $this->dados))->renderizar('tabler');
    }

    public function recuperacao($id = null): void
    {
        $id = (int) $id;
        $etapa = (int) ($_POST['etapa_id'] ?? 0);
        $r = $this->post() ? N::recuperacao($id, $etapa, (array) ($_POST['recuperacao'] ?? [])) : 'Use a tela.';
        /* Onda 8, correção (D-74): o campo da recuperação grava ao apertar Enter, em JSON (se gravou e, se não, o motivo);
           o formulário inteiro continua gravando como sempre */
        if (!empty($_POST['ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            if ($r !== true) {
                http_response_code(422);
            }
            /* a nota da etapa de cada aluno lançado, já com a recuperação (a tela troca a coluna sem recarregar) */
            $notas = [];
            if ($r === true) {
                foreach ((N::quadro($id)['calc']['alunos'] ?? []) as $a) {
                    if (!array_key_exists((string) $a['rm'], (array) ($_POST['recuperacao'] ?? []))) {
                        continue;
                    }
                    foreach ($a['etapas'] ?? [] as $x) {
                        if ((int) $x['id'] === $etapa) {
                            $notas[(string) $a['rm']] = \Core\MediaEscola::v($x['nota'] === null ? null : (string) $x['nota']);
                        }
                    }
                }
            }
            echo json_encode(['ok' => $r === true, 'erro' => $r === true ? '' : (string) $r, 'nota' => $notas], JSON_UNESCAPED_UNICODE);
            return;
        }
        $this->voltar($id, $r, 'As notas da recuperação foram gravadas.', $etapa);
    }

    /**
     * B3 (D-100): a recuperação e as faltas da etapa por um botão só. Cada lado pela permissão dele (a recuperação, as
     * faltas); a recuperação valida antes de gravar qualquer coisa. Se um lado é recusado, o que foi digitado nele volta
     * aos campos, com o motivo — nada some sem aviso.
     */
    public function gravar($id = null): void
    {
        $id = (int) $id;
        $etapa = (int) ($_POST['etapa_id'] ?? 0);
        if (!$this->post()) {
            $this->voltar($id, 'Use a tela.', '', $etapa);
            return;
        }
        $pode = (new \App\adms\Models\AdmsBotao())->valBotao([
            'recuperacao' => ['menu_controller' => 'escola-notas', 'menu_metodo' => 'recuperacao'],
            'faltas' => ['menu_controller' => 'escola-notas', 'menu_metodo' => 'faltas'],
        ]);
        $rec = (array) ($_POST['recuperacao'] ?? []);
        $fal = (array) ($_POST['faltas'] ?? []);
        /* só a falta que mudou vai gravar (o campo que ninguém tocou não vira linha nova) */
        $l = new \App\adms\Models\helper\AdmsRead();
        $l->fullRead('SELECT rm, faltas FROM esc_faltas_etapa WHERE oferta_id = :o AND etapa_id = :e', "o={$id}&e={$etapa}");
        $gravadas = array_column($l->getResultado() ?: [], 'faltas', 'rm');
        $fal = array_filter($fal, static fn($v, $rm) => trim((string) $v) !== (string) (int) ($gravadas[(string) $rm] ?? 0), ARRAY_FILTER_USE_BOTH);
        $porChamada = \App\adms\Models\faltas\AdmsChamada::fonte($id) === 'chamada';
        $ok = [];
        $erros = [];
        $digitado = [];
        if ($rec && !empty($pode['recuperacao'])) {
            $r = N::recuperacao($id, $etapa, $rec);
            if ($r === true) {
                $ok[] = 'a recuperação';
            } else {
                $erros[] = 'A recuperação não foi gravada: ' . $r;
                $digitado['recuperacao'] = array_map('strval', $rec);
            }
        }
        if ($fal && !empty($pode['faltas']) && !$porChamada) {
            if ($erros) { // a recuperação recusada: as faltas esperam, para gravar tudo junto depois da correção
                $digitado['faltas'] = array_map('strval', $fal);
                $erros[] = 'As faltas digitadas não foram gravadas ainda: corrija a recuperação e clique em Gravar de novo.';
            } else {
                $r = N::faltas($id, $etapa, $fal);
                if ($r === true) {
                    $ok[] = 'as faltas';
                } else {
                    $erros[] = 'As faltas não foram gravadas: ' . $r;
                    $digitado['faltas'] = array_map('strval', $fal);
                }
            }
        }
        if ($digitado) {
            $_SESSION['etapaDigitado'] = ['oferta' => $id, 'etapa' => $etapa, 'campos' => $digitado];
        }
        if ($erros) {
            $plural = count($ok) > 1 || $ok === ['as faltas'];
            $msg = ($ok ? ucfirst(implode(' e ', $ok)) . ($plural ? ' foram gravadas. ' : ' foi gravada. ') : '') . implode(' ', $erros);
            $this->voltar($id, $msg, '', $etapa);
            return;
        }
        $plural = count($ok) > 1 || $ok === ['as faltas'];
        $this->voltar($id, true, $ok ? ucfirst(implode(' e ', $ok)) . ' da etapa ' . ($plural ? 'foram gravadas.' : 'foi gravada.') : 'Nada para gravar nesta etapa.', $etapa);
    }

    public function faltas($id = null): void
    {
        $id = (int) $id;
        $etapa = (int) ($_POST['etapa_id'] ?? 0);
        $r = $this->post() ? N::faltas($id, $etapa, (array) ($_POST['faltas'] ?? [])) : 'Use a tela.';
        $this->voltar($id, $r, 'As faltas da etapa foram gravadas.', $etapa);
    }

    public function fechar($id = null): void
    {
        $id = (int) $id;
        $etapa = (int) ($_POST['etapa_id'] ?? 0);
        $r = $this->post() ? N::fechar($id, $etapa) : 'Use a tela.';
        $this->voltar($id, $r, 'A etapa foi fechada: as notas ficaram guardadas, com a regra.', $etapa);
    }

    public function reabrir($id = null): void
    {
        $id = (int) $id;
        $etapa = (int) ($_POST['etapa_id'] ?? 0);
        $r = $this->post() ? N::reabrir($id, $etapa, (string) ($_POST['motivo'] ?? '')) : 'Use a tela.';
        $this->voltar($id, $r, 'A etapa foi reaberta. O fechamento anterior ficou guardado.', $etapa);
    }
}
