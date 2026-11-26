<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\moodle\AdmsMoodle as M;

/**
 * CONECTAR AO MOODLE (Onda 3, Etapa 8), em Integrações, só o Administrador: o endereço e a chave (cifrada no banco;
 * nunca volta inteira à tela), o "Testar conexão" (o nome e a versão do site, as 17 funções do serviço, o que fazer em
 * cada falha), as escolhas da instituição, a saúde da conexão e o glossário. Desconectar apaga a chave e para os envios,
 * sem apagar os vínculos.
 */
class Moodle
{
    private array $dados = [];

    private function disponivel(): bool
    {
        if (!M::disponivel()) {
            \App\adms\Models\helper\AdmsAtualizacaoPendente::responderPendente();
            return false;
        }
        return true;
    }

    private static function aviso(string $tipo, string $html, string $id = ''): void
    {
        $_SESSION['msg'] = "<div class='alert alert-{$tipo} alert-dismissible'" . ($id !== '' ? " id='{$id}'" : '') . "><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>{$html}</div>";
    }

    private static function h(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    public function conexao(): void
    {
        if (!$this->disponivel()) {
            return;
        }
        $c = M::conexao(true);
        $this->dados['paginaAtiva'] = 497;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        unset($c['chave'], $c['chave_cifrada']); // a chave nunca vai para a tela
        $this->dados['conexao'] = $c;
        $this->dados['temChave'] = !empty($c['chave_fim']);
        $this->dados['conectado'] = M::conectado();
        $this->dados['saude'] = M::saude();
        $this->dados['teste'] = $_SESSION['vc_moodle_teste'] ?? null;
        unset($_SESSION['vc_moodle_teste']);
        $this->dados['demonstracao'] = \App\adms\Models\helper\AdmsAmbiente::demonstracaoAtiva();
        /* as categorias do Moodle, para a escolha (uma chamada; sem resposta, fica a já escolhida) */
        $this->dados['categorias'] = [];
        if (M::conectado()) {
            $r = M::cliente()->chamar('core_course_get_categories', [], 'categorias');
            if ($r['ok'] && is_array($r['dados'])) {
                foreach ($r['dados'] as $cat) {
                    $this->dados['categorias'][(int) $cat['id']] = (string) $cat['name'];
                }
            }
        }
        (new \Core\ConfigView('adms/Views/moodle/conexao', $this->dados))->renderizar('tabler');
    }

    /** Salva o endereço, a chave (em branco = mantém a que está) e as escolhas. Trocar o endereço ou a chave pede novo teste. */
    public function salvar(): void
    {
        if (!$this->disponivel() || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . URLADM . 'moodle/conexao');
            return;
        }
        $pdo = M::pdo();
        $atual = M::conexao(true);
        $url = rtrim(trim((string) ($_POST['url'] ?? '')), '/');
        $chave = trim((string) ($_POST['chave'] ?? ''));
        $motivo = $url !== '' ? \VcMoodle::enderecoInvalido($url) : 'Informe o endereço do Moodle.';
        if ($motivo !== '') {
            self::aviso('danger', self::h($motivo), 'moodleRecusado');
            header('Location: ' . URLADM . 'moodle/conexao');
            return;
        }
        if ($chave === '' && empty($atual['chave_cifrada'])) {
            self::aviso('danger', 'Cole a chave que o Moodle mostrou em "Conectar ao VCOSTAEDU".', 'moodleRecusado');
            header('Location: ' . URLADM . 'moodle/conexao');
            return;
        }
        if ($chave !== '' && !preg_match('/^[A-Za-z0-9]{16,128}$/', $chave)) {
            self::aviso('danger', 'A chave do Moodle tem só letras e números (copie inteira, sem espaços).', 'moodleRecusado');
            header('Location: ' . URLADM . 'moodle/conexao');
            return;
        }
        $escolhas = [
            'disciplina_modo' => in_array($_POST['disciplina_modo'] ?? '', ['criar', 'vincular'], true) ? $_POST['disciplina_modo'] : 'criar',
            'trancamento' => in_array($_POST['trancamento'] ?? '', ['suspender', 'remover', 'nada'], true) ? $_POST['trancamento'] : 'suspender',
            'professor_enviar' => ($_POST['professor_enviar'] ?? '1') === '1' ? 1 : 0,
            'professor_papel' => ($_POST['professor_papel'] ?? 'edicao') === 'sem_edicao' ? 'sem_edicao' : 'edicao',
            'envio' => ($_POST['envio'] ?? 'automatico') === 'botao' ? 'botao' : 'automatico',
        ];
        /* Onda 8, Etapa 2 (D-68): o modo do e-mail do aluno no Moodle; o formulário de antes (a opção "E-mail para aluno sem
           e-mail", Onda 6) vale como o modo equivalente */
        $modoAntes = M::emailModo();
        $dominioAntes = mb_strtolower(trim(M::parametro('moodle.email_dominio', '')));
        $modo = (string) ($_POST['email_modo'] ?? '');
        if (!in_array($modo, M::MODOS, true)) {
            $modo = isset($_POST['email_montado']) ? (($_POST['email_montado'] === '1') ? 'aluno_ou_escola' : 'aluno') : $modoAntes;
        }
        $dominio = mb_strtolower(trim((string) ($_POST['email_dominio'] ?? $dominioAntes)));
        if ($modo !== 'aluno' && !preg_match(M::DOMINIO, $dominio)) {
            self::aviso('danger', 'Para usar o e-mail da escola, informe o domínio dele (por exemplo, alunos.escola.exemplo.invalid). Ou escolha "Só o e-mail do aluno".', 'moodleRecusado');
            header('Location: ' . URLADM . 'moodle/conexao');
            return;
        }
        if ($dominio !== '' && !preg_match(M::DOMINIO, $dominio)) {
            $dominio = $dominioAntes; // no modo "só o do aluno" o domínio não é usado: o inválido não é gravado
        }
        \App\adms\Models\helper\AdmsParametro::gravar('moodle.email_modo', $modo, (int) $_SESSION['colUsuario_id']);
        \App\adms\Models\helper\AdmsParametro::gravar('moodle.email_dominio', $dominio, (int) $_SESSION['colUsuario_id']);
        /* o endereço dos alunos muda: os que o sistema criou no Moodle voltam à fila, com o e-mail novo */
        $endereco = static fn ($m, $d) => $m === 'aluno' ? 'aluno' : $m . '|' . $d;
        $reenviados = $endereco($modo, $dominio) !== $endereco($modoAntes, $dominioAntes) ? M::reenviarCriados() : 0;
        $mudouConexao = $url !== (string) ($atual['url'] ?? '') || $chave !== '';
        $sets = 'url = ?, disciplina_modo = ?, trancamento = ?, professor_enviar = ?, professor_papel = ?, envio = ?';
        $vals = [$url, $escolhas['disciplina_modo'], $escolhas['trancamento'], $escolhas['professor_enviar'], $escolhas['professor_papel'], $escolhas['envio']];
        if ($chave !== '') {
            try {
                $sets .= ', chave_cifrada = ?, chave_fim = ?';
                array_push($vals, \VcMoodle::cifrar($chave), substr($chave, -4));
            } catch (\Throwable $e) {
                error_log('[vcostaedu][moodle] cifrar: ' . $e->getMessage());
                self::aviso('danger', 'Não foi possível guardar a chave com segurança: a pasta dos arquivos privados não aceita o arquivo de segredo. Veja Verificações de segurança.', 'moodleRecusado');
                header('Location: ' . URLADM . 'moodle/conexao');
                return;
            }
        }
        if ($mudouConexao) {
            $sets .= ', testada_em = NULL, funcoes_ok = 0';
        }
        /* a categoria: uma das do Moodle, ou uma nova (criada lá, com a conexão já testada) */
        $nova = trim((string) ($_POST['categoria_nova'] ?? ''));
        $cat = (int) ($_POST['categoria_id'] ?? 0);
        if (!$mudouConexao && M::conectado() && $nova !== '') {
            $r = M::cliente()->chamar('core_course_create_categories', ['categories' => [['name' => mb_substr($nova, 0, 255), 'parent' => 0]]], 'categoria');
            if ($r['ok'] && !empty($r['dados'][0]['id'])) {
                $cat = (int) $r['dados'][0]['id'];
                $_POST['categoria_nome'] = $nova;
            } else {
                [$o, $f] = \VcMoodle::explicar($r['codigo']);
                self::aviso('danger', 'A categoria não foi criada no Moodle: ' . self::h($o . ' ' . $f), 'moodleRecusado');
                header('Location: ' . URLADM . 'moodle/conexao');
                return;
            }
        }
        if ($cat > 0) {
            $sets .= ', categoria_id = ?, categoria_nome = ?';
            array_push($vals, $cat, mb_substr(trim((string) ($_POST['categoria_nome'] ?? '')), 0, 255) ?: null);
        }
        $vals[] = 1;
        $pdo->prepare("UPDATE adms_moodle_conexao SET {$sets} WHERE id = ?")->execute($vals);
        $msg = $mudouConexao ? 'Salvo. Agora clique em <b>Testar conexão</b>: os envios começam depois de um teste que dê certo.' : 'As escolhas foram salvas.';
        if ($reenviados > 0) {
            $msg .= ' O e-mail dos alunos no Moodle mudou: ' . $reenviados . ($reenviados === 1 ? ' aluno criado' : ' alunos criados') . ' pelo sistema no Moodle ' . ($reenviados === 1 ? 'vai' : 'vão') . ' com o endereço novo (as contas que já existiam lá ficam como estão).';
        }
        self::aviso('success', $msg, 'moodleSalvo');
        header('Location: ' . URLADM . 'moodle/conexao');
    }

    /** Testa: o nome e a versão do site, as 17 funções no serviço; dando certo, a conexão fica valendo. */
    public function testar(): void
    {
        if (!$this->disponivel() || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . URLADM . 'moodle/conexao');
            return;
        }
        $c = M::conexao(true);
        $teste = ['ok' => false, 'site' => '', 'versao' => '', 'faltam' => [], 'codigo' => '', 'ms' => 0];
        if (empty($c['url']) || ($c['chave'] ?? '') === '') {
            $teste['codigo'] = 'nao_conectado';
        } else {
            $r = M::cliente($c)->chamar('core_webservice_get_site_info', [], 'teste');
            $teste['ms'] = $r['ms'];
            if (!$r['ok']) {
                $teste['codigo'] = $r['codigo'];
            } else {
                $d = (array) $r['dados'];
                $tem = array_column((array) ($d['functions'] ?? []), 'name');
                $teste['site'] = (string) ($d['sitename'] ?? '');
                $teste['versao'] = (string) ($d['release'] ?? '');
                $teste['faltam'] = array_values(array_diff(\VcMoodle::FUNCOES, $tem));
                $teste['ok'] = $teste['faltam'] === [];
                $teste['codigo'] = $teste['ok'] ? '' : 'accessexception';
                M::pdo()->prepare('UPDATE adms_moodle_conexao SET site_nome = ?, site_versao = ?, funcoes_ok = ?, testada_em = IF(?, NOW(), NULL) WHERE id = 1')
                    ->execute([mb_substr($teste['site'], 0, 255), mb_substr($teste['versao'], 0, 80), count(\VcMoodle::FUNCOES) - count($teste['faltam']), $teste['ok'] ? 1 : 0]);
            }
        }
        if (!$teste['ok'] && $teste['codigo'] !== '' && $teste['codigo'] !== 'accessexception') {
            M::pdo()->exec('UPDATE adms_moodle_conexao SET testada_em = NULL WHERE id = 1');
        }
        $_SESSION['vc_moodle_teste'] = $teste;
        header('Location: ' . URLADM . 'moodle/conexao');
    }

    /** Desconecta: apaga a chave e para os envios; os vínculos e o idMoodle das disciplinas ficam. */
    public function desconectar(): void
    {
        if ($this->disponivel() && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            M::pdo()->exec('UPDATE adms_moodle_conexao SET chave_cifrada = NULL, chave_fim = NULL, testada_em = NULL, funcoes_ok = 0 WHERE id = 1');
            self::aviso('success', 'Desconectado: a chave foi apagada e os envios pararam. O que já estava ligado ao Moodle continua ligado.', 'moodleDesconectado');
        }
        header('Location: ' . URLADM . 'moodle/conexao');
    }
}
