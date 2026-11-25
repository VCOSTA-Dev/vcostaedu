<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\escola\AdmsResponsaveis as M;
use App\adms\Models\escola\AdmsResponsaveisPlanilha as Planilha;
use App\adms\Models\escola\AdmsResponsaveisConvite as Convite;
use App\adms\Models\escola\AdmsResponsaveisConviteLote as Lote;
use App\adms\Models\vinculo\AdmsVinculoRegras as R;

/**
 * OS RESPONSÁVEIS (Onda 7, Etapa 2; docs/RESPONSAVEIS.md) — só na escola (módulo "escola").
 *   listar      a lista geral: a busca, os alunos de cada pessoa, o papel por ano, a situação do acesso;
 *   ver         a pessoa: o cadastro, os alunos e os papéis, o histórico;
 *   cadastrar   a pessoa nova (procura por e-mail e por CPF antes: a que já existe é oferecida);
 *   editar      o nome, o e-mail, o celular e o CPF;
 *   aluno       os responsáveis do aluno, por ano letivo: o financeiro, o titular, os acessos adicionais, os contatos,
 *               a cópia do ano anterior, o histórico;
 *   designar    POST: o financeiro ou o titular do ano (a troca encerra o anterior com data e motivo);
 *   encerrar    POST: encerra um papel ou uma liberação, com data e motivo;
 *   liberar     POST: o acesso acadêmico adicional, com o motivo e a referência do termo;
 *   contato     POST: o responsável para contato (cadastrar ou retirar);
 *   copiar      POST: grava a cópia do ano anterior, depois da conferência;
 *   procurar    JSON: a pessoa que já existe com o e-mail ou o CPF;
 *   importar    a planilha própria: enviar, conferir linha a linha, aplicar (tudo ou nada);
 *   modelo      o arquivo de exemplo da planilha.
 * Etapa 3 (o convite; D-63):
 *   convidar         POST: gera o convite (o link aparece uma vez, na ficha, para quem gerou);
 *   enviarConvite    POST: envia por e-mail o convite que acabou de ser gerado;
 *   cancelarConvite  POST: cancela o convite em aberto;
 *   bloquear         POST: bloqueia (com o motivo) ou desbloqueia o acesso ao portal;
 *   convidarTurma    os convites de uma turma, com o envio por e-mail em passos e a lista para o envio manual.
 * Onda 8 (o login; D-67):
 *   login            POST: troca o login do portal (o e-mail ou o CPF do cadastro), se não for o de outra conta.
 * Toda gravação é por POST (as proteções da Onda 6 valem: a origem do formulário, a sessão).
 */
class Responsaveis
{
    private array $dados = [];

    private function comum(int $pagina): void
    {
        $this->dados['paginaAtiva'] = $pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
    }

    private function post(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    private function voltar(string $rota): void
    {
        header('Location: ' . URLADM . $rota);
    }

    public function listar(): void
    {
        $this->comum(532);
        $this->dados['busca'] = trim((string) ($_GET['busca'] ?? ''));
        $this->dados['pessoas'] = M::listar($this->dados['busca']);
        (new \Core\ConfigView('adms/Views/responsaveis/listar', $this->dados))->renderizar('tabler');
    }

    public function ver($id = null): void
    {
        $p = M::pessoa((int) $id);
        if (!$p) {
            $_SESSION['msg'] = R::aviso('danger', 'Responsável não encontrado.');
            $this->voltar('responsaveis/listar');
            return;
        }
        $this->comum(533);
        $p['situacao'] = M::situacaoAcesso($p);
        $this->dados['pessoa'] = $p;
        $this->dados['alunos'] = M::alunosDaPessoa((int) $p['id']);
        $this->dados['historico'] = M::historico(null, (int) $p['id']);
        $this->dados['convite'] = M::conviteAberto((int) $p['id']);
        (new \Core\ConfigView('adms/Views/responsaveis/ver', $this->dados))->renderizar('tabler');
    }

    public function cadastrar(): void
    {
        $aluno = (int) ($_GET['aluno'] ?? $_POST['aluno'] ?? 0);
        if ($this->post()) {
            $r = M::criar($_POST);
            if (is_int($r)) {
                if ($aluno && M::aluno($aluno)) {
                    M::vincular($r, $aluno, (string) ($_POST['parentesco'] ?? ''));
                }
                $_SESSION['msg'] = R::aviso('success', 'Responsável cadastrado.' . ($aluno ? ' Agora, designe o papel dele no ano.' : ''));
                $this->voltar($aluno ? 'responsaveis/aluno/' . $aluno : 'responsaveis/ver/' . $r);
                return;
            }
            if (is_array($r)) {
                $this->dados['existe'] = $r['existe'];
                $_SESSION['msg'] = R::aviso('warning', 'Já existe uma pessoa com este e-mail ou este CPF: ' . $r['existe']['nome'] . '. Use o cadastro que já existe.');
            } else {
                $_SESSION['msg'] = R::aviso('danger', $r);
            }
            $this->dados['form'] = $_POST;
        }
        $this->comum(534);
        $this->dados['aluno'] = $aluno ? M::aluno($aluno) : null;
        (new \Core\ConfigView('adms/Views/responsaveis/form', $this->dados))->renderizar('tabler');
    }

    public function editar($id = null): void
    {
        $id = (int) $id;
        $p = M::pessoa($id);
        if (!$p) {
            $_SESSION['msg'] = R::aviso('danger', 'Responsável não encontrado.');
            $this->voltar('responsaveis/listar');
            return;
        }
        if ($this->post()) {
            $r = M::editar($id, $_POST);
            if ($r === true) {
                $_SESSION['msg'] = R::aviso('success', 'Cadastro alterado.');
                $this->voltar('responsaveis/ver/' . $id);
                return;
            }
            $_SESSION['msg'] = R::aviso('danger', $r);
        }
        $this->comum(535);
        $this->dados['pessoa'] = $p;
        $this->dados['form'] = $this->post() ? $_POST : $p;
        (new \Core\ConfigView('adms/Views/responsaveis/form', $this->dados))->renderizar('tabler');
    }

    public function aluno($id = null): void
    {
        $a = M::aluno((int) $id);
        if (!$a) {
            $_SESSION['msg'] = R::aviso('danger', 'Aluno não encontrado.');
            $this->voltar('academicos/listar');
            return;
        }
        $anos = M::anos();
        $ano = (int) ($_GET['ano'] ?? 0);
        if (!in_array($ano, $anos, true)) {
            $ano = in_array((int) date('Y'), $anos, true) ? (int) date('Y') : $anos[0];
        }
        $this->comum(536);
        $this->dados['aluno'] = $a;
        $this->dados['ano'] = $ano;
        $this->dados['anos'] = $anos;
        $this->dados['r'] = M::doAluno((int) $a['id'], $ano);
        $this->dados['copia'] = isset($_GET['copiar']) ? M::copiarPrevia((int) $a['id'], $ano) : null;
        $this->dados['historico'] = M::historico((int) $a['id']);
        $this->dados['adicionalPermitido'] = M::adicionalPermitido();
        $this->dados['adicionalLimite'] = M::adicionalLimite();
        (new \Core\ConfigView('adms/Views/responsaveis/aluno', $this->dados))->renderizar('tabler');
    }

    /** A pessoa do formulário: a escolhida na lista (id) ou os campos da nova. */
    private static function pessoaDoPost(): array
    {
        $id = (int) ($_POST['responsavel_id'] ?? 0);
        $p = $id ? ['id' => $id] : ['nome' => $_POST['nome'] ?? '', 'email' => $_POST['email'] ?? '', 'celular' => $_POST['celular'] ?? '', 'cpf' => $_POST['cpf'] ?? ''];
        $p['parentesco'] = (string) ($_POST['parentesco'] ?? '');
        return $p;
    }

    private function doAlunoPost(): array
    {
        return [(int) ($_POST['aluno'] ?? 0), (int) ($_POST['ano'] ?? 0)];
    }

    public function designar(): void
    {
        [$aluno, $ano] = $this->doAlunoPost();
        if ($this->post()) {
            $papel = (string) ($_POST['papel'] ?? '');
            $r = M::designar($aluno, $ano, $papel, self::pessoaDoPost(), (string) ($_POST['data'] ?? ''), (string) ($_POST['motivo'] ?? ''));
            $_SESSION['msg'] = $r === true ? R::aviso('success', (M::PAPEIS[$papel] ?? 'Responsável') . ' de ' . $ano . ' gravado.') : R::aviso('danger', $r);
        }
        $this->voltar('responsaveis/aluno/' . $aluno . '?ano=' . $ano);
    }

    public function encerrar(): void
    {
        [$aluno, $ano] = $this->doAlunoPost();
        if ($this->post()) {
            $r = M::encerrar((int) ($_POST['designacao'] ?? 0), (string) ($_POST['data'] ?? ''), (string) ($_POST['motivo'] ?? ''));
            $_SESSION['msg'] = $r === true ? R::aviso('success', 'Encerrado. O acesso dessa pessoa ao aluno neste ano acaba agora.') : R::aviso('danger', $r);
        }
        $this->voltar('responsaveis/aluno/' . $aluno . '?ano=' . $ano);
    }

    public function liberar(): void
    {
        [$aluno, $ano] = $this->doAlunoPost();
        if ($this->post()) {
            $r = M::liberar($aluno, $ano, self::pessoaDoPost(), (string) ($_POST['motivo'] ?? ''), (string) ($_POST['referencia'] ?? ''), (string) ($_POST['data'] ?? ''));
            $_SESSION['msg'] = $r === true ? R::aviso('success', 'Acesso acadêmico adicional liberado para ' . $ano . '.') : R::aviso('danger', $r);
        }
        $this->voltar('responsaveis/aluno/' . $aluno . '?ano=' . $ano);
    }

    public function contato(): void
    {
        [$aluno, $ano] = $this->doAlunoPost();
        if ($this->post()) {
            if (!empty($_POST['retirar'])) {
                M::contatoRetirar($aluno, (int) $_POST['retirar']);
                $_SESSION['msg'] = R::aviso('success', 'Contato retirado.');
            } else {
                $r = M::contatoAdicionar($aluno, $_POST);
                $_SESSION['msg'] = $r === true ? R::aviso('success', 'Contato cadastrado.') : R::aviso('danger', $r);
            }
        }
        $this->voltar('responsaveis/aluno/' . $aluno . '?ano=' . $ano);
    }

    public function copiar(): void
    {
        [$aluno, $ano] = $this->doAlunoPost();
        if ($this->post()) {
            $r = M::copiar($aluno, $ano);
            $_SESSION['msg'] = is_int($r) ? R::aviso($r ? 'success' : 'info', $r ? "{$r} papel(éis) copiado(s) de " . ($ano - 1) . '.' : 'Nada a copiar: os papéis do ano já estão preenchidos, ou o ano anterior não tem.') : R::aviso('danger', $r);
        }
        $this->voltar('responsaveis/aluno/' . $aluno . '?ano=' . $ano);
    }

    public function procurar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $p = M::procurar((string) ($_GET['email'] ?? ''), (string) ($_GET['cpf'] ?? ''));
        echo json_encode($p ? ['existe' => true, 'id' => (int) $p['id'], 'nome' => $p['nome']] : ['existe' => false], JSON_UNESCAPED_UNICODE);
    }

    /* ------------------------------------------------------------------ o convite (Etapa 3) */

    /** POST: gera o convite (o anterior deixa de valer). O link aparece uma vez, na ficha, para quem gerou. */
    public function convidar(): void
    {
        $pessoa = (int) ($_POST['responsavel_id'] ?? 0);
        if ($this->post()) {
            $r = Convite::gerar($pessoa);
            if (is_array($r)) {
                $_SESSION['vc_convite_gerado'] = ['pessoa' => $pessoa, 'id' => $r['id'], 'codigo' => $r['codigo']];
                $_SESSION['msg'] = R::aviso('success', 'Convite gerado. Ele vale até ' . date('d/m/Y', strtotime($r['expira'])) . ' e uma vez só; o convite anterior, se havia, deixou de valer.');
            } else {
                $_SESSION['msg'] = R::aviso('danger', $r);
            }
        }
        $this->voltar('responsaveis/ver/' . $pessoa);
    }

    /** POST: envia por e-mail o convite que acabou de ser gerado nesta sessão. */
    public function enviarConvite(): void
    {
        $pessoa = (int) ($_POST['responsavel_id'] ?? 0);
        if ($this->post()) {
            $g = $_SESSION['vc_convite_gerado'] ?? null;
            if (!$g || (int) $g['pessoa'] !== $pessoa) {
                $_SESSION['msg'] = R::aviso('warning', 'O link do convite só fica disponível logo depois de gerado. Gere um convite novo para enviar.');
            } else {
                $r = Convite::enviarEmail((int) $g['id'], Convite::link((string) $g['codigo']));
                $_SESSION['msg'] = $r === true ? R::aviso('success', 'Convite enviado por e-mail.') : R::aviso('danger', $r);
            }
        }
        $this->voltar('responsaveis/ver/' . $pessoa);
    }

    public function cancelarConvite(): void
    {
        $pessoa = (int) ($_POST['responsavel_id'] ?? 0);
        if ($this->post()) {
            unset($_SESSION['vc_convite_gerado']);
            $_SESSION['msg'] = Convite::cancelar($pessoa) ? R::aviso('success', 'Convite cancelado: o link deixou de valer.') : R::aviso('warning', 'Não havia convite em aberto.');
        }
        $this->voltar('responsaveis/ver/' . $pessoa);
    }

    /** POST: bloqueia (com o motivo) ou desbloqueia o acesso ao portal. */
    public function bloquear(): void
    {
        $pessoa = (int) ($_POST['responsavel_id'] ?? 0);
        if ($this->post()) {
            $bloquear = ($_POST['bloquear'] ?? '') === '1';
            $r = Convite::bloquear($pessoa, $bloquear, (string) ($_POST['motivo'] ?? ''));
            if ($r === true && $bloquear) {
                unset($_SESSION['vc_convite_gerado']);
            }
            $_SESSION['msg'] = $r === true ? R::aviso('success', $bloquear ? 'Acesso bloqueado: a pessoa não entra mais no portal, e o convite em aberto foi cancelado.' : 'Acesso desbloqueado.') : R::aviso('danger', $r);
        }
        $this->voltar('responsaveis/ver/' . $pessoa);
    }

    /** Onda 8 (D-67): POST — troca o login do portal (o e-mail ou o CPF do cadastro), se não for o de outra conta. */
    public function login(): void
    {
        $pessoa = (int) ($_POST['responsavel_id'] ?? 0);
        if ($this->post()) {
            $r = Convite::trocarLogin($pessoa, (string) ($_POST['login_tipo'] ?? ''));
            $_SESSION['msg'] = $r === true ? R::aviso('success', 'Login do portal gravado. A pessoa entra com ele e com a mesma senha.') : R::aviso('danger', $r);
        }
        $this->voltar('responsaveis/ver/' . $pessoa);
    }

    /**
     * Os convites de uma turma: GET mostra os responsáveis da turma no ano que ainda não têm acesso; POST (um por vez,
     * em passos) gera o convite de uma pessoa e o envia por e-mail; o que não foi por e-mail volta com o link, para o
     * envio manual (o link não fica guardado: só quem está na tela o vê).
     */
    public function convidarTurma(): void
    {
        if ($this->post()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            $l = new Lote();
            $fase = (string) ($_POST['fase'] ?? 'estado');
            if ($fase === 'iniciar') {
                [$ok, $msg] = $l->iniciar((int) ($_POST['turma'] ?? 0));
                echo json_encode($ok ? Lote::publico($l->estado()) : ['erro' => $msg], JSON_UNESCAPED_UNICODE);
                return;
            }
            if ($fase === 'passo') {
                session_write_close();
                @ignore_user_abort(true);
                echo json_encode($l->passo(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                return;
            }
            echo json_encode(Lote::publico($l->estado()) ?? ['nada' => true], JSON_UNESCAPED_UNICODE);
            return;
        }
        $this->comum(549);
        $anos = M::anos();
        $ano = (int) ($_GET['ano'] ?? 0);
        if (!in_array($ano, $anos, true)) {
            $ano = in_array((int) date('Y'), $anos, true) ? (int) date('Y') : ($anos[0] ?? (int) date('Y'));
        }
        $this->dados['ano'] = $ano;
        $this->dados['anos'] = $anos;
        $this->dados['turmas'] = Convite::turmas($ano);
        $this->dados['turma'] = (int) ($_GET['turma'] ?? 0);
        $this->dados['pendentes'] = $this->dados['turma'] ? Convite::pendentesDaTurma($this->dados['turma']) : [];
        $this->dados['envio'] = Convite::envioConfigurado();
        (new \Core\ConfigView('adms/Views/responsaveis/convidarTurma', $this->dados))->renderizar('tabler');
    }

    public function importar(): void
    {
        $this->comum(543);
        if ($this->post()) {
            if (($_POST['acao'] ?? '') === 'aplicar') {
                $r = Planilha::aplicar((string) ($_POST['lote'] ?? ''));
                if (is_array($r)) {
                    $_SESSION['msg'] = R::aviso('success', "Planilha aplicada: {$r['linhas']} linha(s), {$r['pessoas']} pessoa(s) nova(s), {$r['designacoes']} designação(ões).");
                    $this->voltar('responsaveis/listar');
                    return;
                }
                $_SESSION['msg'] = R::aviso('danger', $r);
            } else {
                $r = Planilha::conferir($_FILES['planilha'] ?? null);
                if (is_string($r)) {
                    $_SESSION['msg'] = R::aviso('danger', $r);
                } else {
                    $this->dados['conferencia'] = $r;
                }
            }
        }
        (new \Core\ConfigView('adms/Views/responsaveis/importar', $this->dados))->renderizar('tabler');
    }

    public function modelo(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="modelo_responsaveis.csv"');
        echo "\xEF\xBB\xBF" . Planilha::modelo();
    }
}
