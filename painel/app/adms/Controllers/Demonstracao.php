<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsAmbiente;
use App\adms\Models\demonstracao\AdmsDemonstracaoMontagem as Montagem;
use App\adms\Models\helper\AdmsTipoInstituicao as Tipo;

/**
 * CONHECER COM A DEMONSTRAÇÃO, OU COMEÇAR COM OS DADOS DA INSTITUIÇÃO (Onda 2, Etapa 4). Só o Administrador.
 *   comecar      a escolha (depois da instalação e no primeiro acesso);
 *   montar       POST, em JSON: montar e remover EM PASSOS CURTOS (Onda 3, Etapa 4) — fase=iniciar (com a senha, se a
 *                instalação já tem dado próprio; para remover, a confirmação escrita), fase=passo (o próximo, ou o que
 *                falhou, de novo) e fase=estado. Cada passo roda dentro do PHP desta requisição
 *                (Models/demonstracao/AdmsDemonstracaoMontagem.php); a linha de comando continua existindo
 *                (database/ferramentas/demonstracao.php);
 *   instituicao  "Começar com os dados da minha instituição": a confirmação escrita e a janela dos passos da remoção;
 *                sem reinstalar; no fim, os Primeiros passos. E o TIPO DA INSTITUIÇÃO (Onda 4, Etapa 1): mostrado sempre;
 *                trocado (POST em tipo) só sem dado próprio e com a demonstração desligada (AdmsTipoInstituicao::podeTrocar).
 */
class Demonstracao
{
    private $dados = [];
    public const FRASE = 'COMEÇAR';

    private function comum(int $pagina): void
    {
        $this->dados['paginaAtiva'] = $pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
    }

    private function msg(string $tipo, string $texto, string $id = ''): void
    {
        $_SESSION['msg'] = "<div class='alert alert-{$tipo} alert-dismissible'" . ($id ? " id='{$id}'" : '') . "><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>{$texto}</div>";
    }

    /** O que impede a demonstração: dado acadêmico que não é dela. */
    public static function dadosDaInstituicao(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT (SELECT COUNT(*) FROM rm_cursos WHERE nome NOT IN ('Pós-graduação em Gestão de Projetos', 'Pós-graduação em Docência no Ensino Superior', 'Graduação Fictícia de Demonstração', 'Especialização em Gestão Pública Exemplar', 'Especialização em Dados Públicos Exemplar', 'Ensino Fundamental — Anos Finais (demonstração)', 'Ensino Médio (demonstração)')) AS cursos,
                               (SELECT COUNT(*) FROM col_usuarios WHERE col_niveis_acesso_id = 1 AND rm NOT BETWEEN 250000 AND 269999) AS alunos");
        $r = $ler->getResultado()[0] ?? [];
        return ['cursos' => (int) ($r['cursos'] ?? 0), 'alunos' => (int) ($r['alunos'] ?? 0)];
    }

    public function comecar(): void
    {
        if (!\App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0008')) {
            \App\adms\Models\helper\AdmsAtualizacaoPendente::responderPendente();
            return;
        }
        if (($_GET['escolha'] ?? '') === 'instituicao') {
            AdmsAmbiente::gravarEstado('comeco.escolhido', 'instituicao');
            header('Location: ' . URLADM . 'primeiros-passos/index');
            return;
        }
        $this->comum(467);
        $this->dados['dadosInstituicao'] = self::dadosDaInstituicao();
        $this->dados['demonstracao'] = AdmsAmbiente::demonstracaoAtiva();
        $m = new Montagem();
        $this->dados['montagem'] = Montagem::publico($m->estado());
        $this->dados['pedeSenha'] = !$m->semDadoProprio();
        $this->dados['grupos'] = Montagem::grupos('montar');
        (new \Core\ConfigView('adms/Views/demonstracao/comecar', $this->dados))->renderizar('tabler');
    }

    /** Montar e remover em passos (JSON). */
    public function montar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !\App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0008')) {
            http_response_code(405);
            echo json_encode(['erro' => 'Use a tela "Como você quer começar".'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $m = new Montagem();
        $fase = (string) ($_POST['fase'] ?? 'estado');
        $operacao = (string) ($_POST['operacao'] ?? '');
        if ($fase === 'iniciar') {
            $atual = $m->estado();
            $operacao = $operacao !== '' ? $operacao : (string) ($atual['acao'] ?? 'montar');
            if ($atual === null && $operacao === 'montar' && !self::demonstracaoDisponivel()) {
                echo json_encode(['erro' => 'A demonstração com dados de escola ainda não está disponível nesta versão. Nada foi alterado.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            if ($atual === null && $operacao === 'montar' && !$m->semDadoProprio()) {
                $ler = new \App\adms\Models\helper\AdmsRead();
                $ler->fullRead("SELECT senha FROM adms_usuarios WHERE id = :id LIMIT :limit", "id=" . (int) $_SESSION['colUsuario_id'] . "&limit=1");
                $conta = $ler->getResultado()[0] ?? null;
                if (!$conta || !password_verify((string) ($_POST['senha'] ?? ''), (string) $conta['senha'])) {
                    echo json_encode(['erro' => 'A senha não confere. Nada foi alterado.'], JSON_UNESCAPED_UNICODE);
                    return;
                }
            }
            if ($atual === null && $operacao === 'remover' && trim((string) ($_POST['confirmacao'] ?? '')) !== self::FRASE) {
                echo json_encode(['erro' => 'Para confirmar, escreva ' . self::FRASE . ' no campo. Nada foi alterado.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            [$ok, $msg] = $m->iniciar($operacao);
            echo json_encode($ok ? Montagem::publico($m->estado()) : ['erro' => $msg], JSON_UNESCAPED_UNICODE);
            return;
        }
        if ($fase === 'passo') {
            /* as telas que o administrador faz entram com a sessão de quem pediu: ela é liberada antes (o PHP trava a
               sessão enquanto a requisição dura), e relida no fim para tirar a mensagem que as telas deixaram */
            $cookie = session_name() . '=' . session_id();
            session_write_close();
            @ignore_user_abort(true);
            $r = $m->executarPasso(URL, $cookie);
            if (@session_start()) {
                unset($_SESSION['msg']);
                if (!empty($r['concluida'])) {
                    $this->msg('success', $r['acao'] === 'montar' ? 'A demonstração está pronta. Explore à vontade: os dados são fictícios.' : 'A demonstração foi removida. Agora, os primeiros passos com os dados da sua instituição.',
                        $r['acao'] === 'montar' ? 'demonstracaoMontada' : 'demonstracaoRemovida');
                }
            }
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(Montagem::publico($m->estado()) ?? ['nada' => true], JSON_UNESCAPED_UNICODE);
    }

    public function instituicao(): void
    {
        if (!\App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0008')) {
            \App\adms\Models\helper\AdmsAtualizacaoPendente::responderPendente();
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            header('Location: ' . URLADM . 'demonstracao/instituicao'); // a remoção é pela janela dos passos (POST em demonstracao/montar)
            return;
        }
        $this->comum(469);
        $this->dados['demonstracao'] = AdmsAmbiente::demonstracaoAtiva();
        $this->dados['frase'] = self::FRASE;
        $this->dados['montagem'] = Montagem::publico((new Montagem())->estado());
        $this->dados['grupos'] = Montagem::grupos('remover');
        $this->dados['tipo'] = Tipo::atual();
        $this->dados['troca'] = Tipo::podeTrocar();
        (new \Core\ConfigView('adms/Views/demonstracao/instituicao', $this->dados))->renderizar('tabler');
    }

    /** A demonstração existe para o tipo desta instalação? (desde a 1.41.0, nos dois: a faculdade e o colégio fictícios) */
    public static function demonstracaoDisponivel(): bool
    {
        return true;
    }

    /** POST: troca o tipo da instituição, se ainda pode (sem dado próprio, sem a demonstração). */
    public function tipo(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $novo = (string) ($_POST['tipo'] ?? '');
            $troca = Tipo::podeTrocar();
            if (!\VcVocabulario::valido($novo)) {
                $this->msg('danger', 'Escolha um dos tipos de instituição.');
            } elseif (!$troca['pode']) {
                $this->msg('warning', htmlspecialchars($troca['motivo'], ENT_QUOTES, 'UTF-8'), 'tipoRecusado');
            } else {
                Tipo::gravar($novo);
                $this->msg('success', 'O tipo da instituição agora é <b>' . htmlspecialchars(\VcVocabulario::nome($novo), ENT_QUOTES, 'UTF-8') . '</b>.', 'tipoGravado');
            }
        }
        header('Location: ' . URLADM . 'demonstracao/instituicao');
    }
}
