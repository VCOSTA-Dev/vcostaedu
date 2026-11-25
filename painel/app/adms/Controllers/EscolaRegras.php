<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\escola\AdmsEscolaConfig as C;
use App\adms\Models\vinculo\AdmsVinculoRegras as R;

/**
 * AS REGRAS DA ESCOLA (Onda 4; docs/ESCOLA.md) — só na escola. O que vem pronto, com o padrão preenchido, e a escola
 * mantém ou ajusta: a média mínima, a recuperação da etapa, a composição da média anual e a frequência (a base e o mínimo).
 */
class EscolaRegras
{
    private array $dados = [];

    public function index(): void
    {
        $this->dados['paginaAtiva'] = 519;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['regras'] = C::todas();
        (new \Core\ConfigView('adms/Views/escolaRegras/index', $this->dados))->renderizar('tabler');
    }

    public function salvar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $r = self::validar($_POST);
            if (is_string($r)) {
                $_SESSION['msg'] = R::aviso('danger', $r);
            } else {
                $fonteAntes = C::valor('frequencia.fonte');
                C::gravar($r);
                $txt = 'As regras da escola foram gravadas.';
                if ($r['frequencia.fonte'] !== $fonteAntes) {
                    /* Onda 6, Etapa 5: a fonte nova vale para os componentes abertos (os que já têm falta lançada, ou chamada,
                       ficam como estão: uma fonte só por componente) e para as turmas criadas daqui em diante */
                    [$mudaram, $ficaram] = C::aplicarFonte($r['frequencia.fonte']);
                    $txt .= " As faltas vêm agora de: " . mb_strtolower(explode(':', C::FONTES[$r['frequencia.fonte']])[0]) . ". " . \App\adms\Models\helper\AdmsUi::plural($mudaram, 'disciplina aberta passou', 'disciplinas abertas passaram')
                        . ($ficaram ? "; {$ficaram} ficaram como estavam porque já têm faltas lançadas ou chamada (troque cada uma na tela da chamada da disciplina)" : '') . '.';
                }
                $_SESSION['msg'] = R::aviso('success', $txt);
            }
        }
        header('Location: ' . URLADM . 'escola-regras/index');
    }

    /** @return array|string os valores limpos, ou a recusa */
    public static function validar(array $d)
    {
        $saida = [];
        $min = str_replace(',', '.', trim((string) ($d['media_minima'] ?? C::valor('media.minima'))));
        if (!preg_match('/^\d{1,2}(\.\d{1,2})?$/', $min) || (float) $min <= 0 || (float) $min > 10) {
            return 'A média mínima é um número de 0 a 10.';
        }
        $saida['media.minima'] = number_format((float) $min, 2, '.', '');
        $rec = (string) ($d['recuperacao_regra'] ?? C::valor('recuperacao.regra'));
        if (!isset(C::RECUPERACAO[$rec])) {
            return 'Escolha a regra da recuperação.';
        }
        $saida['recuperacao.regra'] = $rec;
        $comp = (string) ($d['anual_composicao'] ?? C::valor('anual.composicao'));
        if (!isset(C::COMPOSICAO[$comp])) {
            return 'Escolha a composição da média anual.';
        }
        $saida['anual.composicao'] = $comp;
        $pesos = trim((string) ($d['anual_pesos'] ?? C::valor('anual.pesos')));
        if ($pesos !== '' && !preg_match('/^\d{1,2}(\s*,\s*\d{1,2})*$/', $pesos)) {
            return 'Os pesos das etapas: números inteiros separados por vírgula, na ordem das etapas (por exemplo, 1, 1, 2, 2).';
        }
        if ($comp === 'pesos' && $pesos === '') {
            return 'Com a média ponderada, informe o peso de cada etapa.';
        }
        $saida['anual.pesos'] = preg_replace('/\s+/', '', $pesos);
        $base = (string) ($d['frequencia_base'] ?? C::valor('frequencia.base'));
        if (!isset(C::FREQUENCIA[$base])) {
            return 'Escolha a base da frequência.';
        }
        $saida['frequencia.base'] = $base;
        $min = str_replace(',', '.', trim((string) ($d['frequencia_minima'] ?? C::valor('frequencia.minima'))));
        if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $min) || (float) $min <= 0 || (float) $min > 100) {
            return 'A frequência mínima é um percentual entre 0 e 100.';
        }
        $saida['frequencia.minima'] = number_format((float) $min, 2, '.', '');
        $fonte = (string) ($d['frequencia_fonte'] ?? C::valor('frequencia.fonte'));
        if (!isset(C::FONTES[$fonte])) {
            return 'Escolha de onde vêm as faltas.';
        }
        $saida['frequencia.fonte'] = $fonte;
        /* Onda 7, Etapa 2: os responsáveis */
        $saida['responsaveis.adicional'] = (string) ($d['responsaveis_adicional'] ?? C::valor('responsaveis.adicional')) === '0' ? '0' : '1';
        $lim = trim((string) ($d['responsaveis_adicional_limite'] ?? C::valor('responsaveis.adicional_limite')));
        if ($lim === '') {
            $lim = '0';
        }
        if (!preg_match('/^\d{1,2}$/', $lim)) {
            return 'O limite de acessos adicionais por aluno é um número de 0 a 99 (0 = sem limite).';
        }
        $saida['responsaveis.adicional_limite'] = (string) (int) $lim;
        $dias = trim((string) ($d['responsaveis_convite_dias'] ?? C::valor('responsaveis.convite_dias')));
        if (!preg_match('/^\d{1,2}$/', $dias) || (int) $dias < 1 || (int) $dias > 30) {
            return 'A validade do convite é de 1 a 30 dias.';
        }
        $saida['responsaveis.convite_dias'] = (string) (int) $dias;
        $msg = trim((string) ($d['responsaveis_mensagem'] ?? C::valor('responsaveis.mensagem')));
        if (!str_contains($msg, '{link}') || mb_strlen($msg) > 255) {
            return 'A mensagem do convite precisa ter {link} (o lugar do link) e no máximo 255 caracteres.';
        }
        $saida['responsaveis.mensagem'] = $msg;
        return $saida;
    }
}
